<?php

namespace App\Services\System;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\City;
use App\Models\Department;
use App\Models\District;
use App\Models\Permission;
use App\Models\Province;
use App\Models\Role;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\UserAccessLevel;
use App\Models\UserLoginRestriction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupRestoreService
{
    public function listModules(): array
    {
        return array_merge([
            $this->buildDepartmentModule(),
            $this->buildUserModule(),
            $this->buildRoleModule(),
            $this->buildAccessControlModule(),
            $this->buildLocationModule(),
            $this->buildAuditTrailModule(),
        ], $this->buildSnapshotModules());
    }

    public function getModule(string $moduleKey): array
    {
        return collect($this->listModules())
            ->firstOrFail(fn (array $module) => $module['key'] === $moduleKey);
    }

    public function downloadTemplate(string $moduleKey): StreamedResponse
    {
        $module = $this->getModule($moduleKey);

        return $this->streamCsv(
            sprintf('%s_template_%s.csv', $moduleKey, now()->format('Ymd_His')),
            $module['headers'],
            []
        );
    }

    public function exportData(string $moduleKey): StreamedResponse
    {
        $module = $this->getModule($moduleKey);
        $rows = $this->exportRows($moduleKey);

        return $this->streamCsv(
            sprintf('%s_export_%s.csv', $moduleKey, now()->format('Ymd_His')),
            $module['headers'],
            $rows
        );
    }

    public function importData(string $moduleKey, UploadedFile $file, User $actor): array
    {
        [$headers, $rows] = $this->parseCsv($file);
        $module = $this->getModule($moduleKey);
        $missingHeaders = array_diff($module['required_headers'], $headers);

        if (!empty($missingHeaders)) {
            throw new \RuntimeException(
                'Header CSV tidak sesuai template. Kolom wajib yang hilang: ' . implode(', ', $missingHeaders)
            );
        }

        return DB::transaction(fn () => $this->importRows($moduleKey, $rows, $actor));
    }

    public function deleteModuleData(string $moduleKey, User $actor): array
    {
        return DB::transaction(fn () => $this->deleteRows($moduleKey, $actor));
    }

    public function downloadAllTemplates()
    {
        $modules = $this->listModules();
        $zipPath = $this->buildModuleZip($modules, 'template');

        return response()->download(
            $zipPath,
            'backup_templates_all_' . now()->format('Ymd_His') . '.zip'
        )->deleteFileAfterSend(true);
    }

    public function exportAllData()
    {
        $modules = $this->listModules();
        $zipPath = $this->buildModuleZip($modules, 'export');

        return response()->download(
            $zipPath,
            'backup_export_all_' . now()->format('Ymd_His') . '.zip'
        )->deleteFileAfterSend(true);
    }

    public function importAllData(UploadedFile $file, User $actor): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi ZipArchive tidak tersedia di server.');
        }

        $zip = new \ZipArchive();
        $opened = $zip->open($file->getRealPath());

        if ($opened !== true) {
            throw new \RuntimeException('File ZIP tidak valid atau tidak bisa dibuka.');
        }

        $moduleMap = collect($this->listModules())->keyBy('key');
        $filesByModule = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);

            if (!$entryName || str_ends_with($entryName, '/')) {
                continue;
            }

            $basename = pathinfo($entryName, PATHINFO_BASENAME);
            $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

            if (!in_array($extension, ['csv', 'txt'], true)) {
                continue;
            }

            $moduleKey = $this->detectModuleKeyFromFilename($basename, $moduleMap->keys()->all());

            if ($moduleKey === null || !$moduleMap->has($moduleKey)) {
                continue;
            }

            $filesByModule[$moduleKey] = $entryName;
        }

        if (empty($filesByModule)) {
            $zip->close();
            throw new \RuntimeException('ZIP tidak berisi file CSV backup yang dikenali.');
        }

        $summary = [
            'processed_modules' => 0,
            'failed_modules' => [],
            'skipped_modules' => [],
            'created' => 0,
            'updated' => 0,
            'restored' => 0,
            'skipped_rows' => 0,
            'warnings' => [],
            'module_results' => [],
        ];

        try {
            foreach ($moduleMap as $moduleKey => $module) {
                if (!isset($filesByModule[$moduleKey])) {
                    continue;
                }

                $stream = $zip->getStream($filesByModule[$moduleKey]);

                if ($stream === false) {
                    $summary['failed_modules'][$moduleKey] = 'File ZIP tidak bisa dibaca.';
                    continue;
                }

                $tmpPath = storage_path('app/' . $moduleKey . '_bulk_import_' . Str::random(8) . '.csv');
                $target = fopen($tmpPath, 'wb');

                if ($target === false) {
                    fclose($stream);
                    $summary['failed_modules'][$moduleKey] = 'File sementara tidak bisa dibuat.';
                    continue;
                }

                stream_copy_to_stream($stream, $target);
                fclose($stream);
                fclose($target);

                try {
                    $uploadedFile = new UploadedFile($tmpPath, basename($tmpPath), 'text/csv', null, true);
                    $result = $this->importData($moduleKey, $uploadedFile, $actor);

                    $summary['processed_modules']++;
                    $summary['created'] += $result['created'] ?? 0;
                    $summary['updated'] += $result['updated'] ?? 0;
                    $summary['restored'] += $result['restored'] ?? 0;
                    $summary['skipped_rows'] += $result['skipped'] ?? 0;
                    $summary['warnings'] = array_merge($summary['warnings'], $result['warnings'] ?? []);
                    $summary['module_results'][$moduleKey] = $result;
                } catch (\Throwable $e) {
                    $summary['failed_modules'][$moduleKey] = $e->getMessage();
                } finally {
                    @unlink($tmpPath);
                }
            }
        } finally {
            $zip->close();
        }

        return $summary;
    }

    public function deleteAllModuleData(User $actor): array
    {
        $modules = array_reverse($this->listModules());
        $summary = [
            'processed_modules' => 0,
            'failed_modules' => [],
            'deleted' => 0,
            'warnings' => [],
            'module_results' => [],
        ];

        foreach ($modules as $module) {
            try {
                $result = $this->deleteModuleData($module['key'], $actor);
                $summary['processed_modules']++;
                $summary['deleted'] += $result['deleted'] ?? 0;
                $summary['warnings'] = array_merge($summary['warnings'], $result['warnings'] ?? []);
                $summary['module_results'][$module['key']] = $result;
            } catch (\Throwable $e) {
                $summary['failed_modules'][$module['key']] = $e->getMessage();
            }
        }

        return $summary;
    }

    protected function exportRows(string $moduleKey): iterable
    {
        if ($this->isSnapshotModule($moduleKey)) {
            return $this->exportSnapshotRows($moduleKey);
        }

        return match ($moduleKey) {
            'departments' => $this->exportDepartmentRows(),
            'users' => $this->exportUserRows(),
            'roles' => $this->exportRoleRows(),
            'access-control' => $this->exportAccessControlRows(),
            'master-location' => $this->exportLocationRows(),
            'audit-trails' => $this->exportAuditTrailRows(),
            default => throw new \InvalidArgumentException("Unsupported backup module [{$moduleKey}]"),
        };
    }

    protected function importRows(string $moduleKey, array $rows, User $actor): array
    {
        if ($this->isSnapshotModule($moduleKey)) {
            return $this->importSnapshotRows($moduleKey, $rows, $actor);
        }

        return match ($moduleKey) {
            'departments' => $this->importDepartments($rows, $actor),
            'users' => $this->importUsers($rows, $actor),
            'roles' => $this->importRoles($rows, $actor),
            'access-control' => $this->importAccessControl($rows, $actor),
            'master-location' => $this->importLocations($rows, $actor),
            'audit-trails' => $this->importAuditTrails($rows, $actor),
            default => throw new \InvalidArgumentException("Unsupported backup module [{$moduleKey}]"),
        };
    }

    protected function deleteRows(string $moduleKey, User $actor): array
    {
        if ($this->isSnapshotModule($moduleKey)) {
            return $this->deleteSnapshotRows($moduleKey, $actor);
        }

        return match ($moduleKey) {
            'departments' => $this->deleteDepartments(),
            'users' => $this->deleteUsers($actor),
            'roles' => $this->deleteRoles($actor),
            'access-control' => $this->deleteAccessControl(),
            'master-location' => $this->deleteLocations(),
            'audit-trails' => $this->deleteAuditTrails(),
            default => throw new \InvalidArgumentException("Unsupported backup module [{$moduleKey}]"),
        };
    }

    protected function buildSnapshotModules(): array
    {
        return collect($this->snapshotModuleConfigs())
            ->map(fn (array $config) => $this->buildSnapshotModule($config))
            ->filter()
            ->values()
            ->all();
    }

    protected function snapshotModuleConfigs(): array
    {
        return [
            [
                'key' => 'pipelines',
                'label' => 'Pipeline',
                'route_key' => 'pipeline',
                'root_table' => 'marketing_pipelines',
                'business_key' => 'pipeline_number',
                'summary_unit' => 'pipeline',
                'description' => 'Backup pipeline marketing lengkap, termasuk assignment gedung prospek.',
                'notes' => [
                    'Semua kolom tabel pipeline ikut dibawa, ditambah snapshot gedung pada kolom marketing_pipeline_buildings_json.',
                    'Delete module melakukan soft delete pada data pipeline aktif.',
                ],
                'relations' => [
                    ['header' => 'marketing_pipeline_buildings_json', 'table' => 'marketing_pipeline_buildings', 'foreign_key' => 'marketing_pipeline_id'],
                ],
            ],
            [
                'key' => 'surveys',
                'label' => 'Survey',
                'route_key' => 'surveys',
                'root_table' => 'surveys',
                'business_key' => 'survey_number',
                'summary_unit' => 'survey',
                'description' => 'Backup survey utama beserta detail ruangan hasil survey.',
                'notes' => [
                    'Detail survey dibawa pada kolom survey_details_json agar hasil ruangan dan spesifikasi tetap utuh.',
                    'Kolom id tetap dipertahankan untuk menjaga relasi lintas modul saat restore.',
                ],
                'relations' => [
                    ['header' => 'survey_details_json', 'table' => 'survey_details', 'foreign_key' => 'survey_id'],
                ],
            ],
            [
                'key' => 'quotations',
                'label' => 'Quotation',
                'route_key' => 'quotations',
                'root_table' => 'quotations',
                'business_key' => 'quotation_number',
                'summary_unit' => 'quotation',
                'description' => 'Backup quotation lengkap berikut survey pivot, rooms, rentals, PIC, dan detail harga.',
                'notes' => [
                    'Ruangan dan rental quotation dibawa sebagai paket utuh agar restore tidak kehilangan struktur penawaran.',
                    'Kolom JSON child disiapkan supaya backup satu file tetap cukup untuk restore modul quotation.',
                ],
                'relations' => [
                    ['header' => 'quotation_details_json', 'table' => 'quotation_details', 'foreign_key' => 'quotation_id'],
                    ['header' => 'quotation_surveys_json', 'table' => 'quotation_surveys', 'foreign_key' => 'quotation_id'],
                    ['header' => 'quotation_rooms_json', 'table' => 'quotation_rooms', 'foreign_key' => 'quotation_id'],
                    ['header' => 'quotation_rentals_json', 'table' => 'quotation_rentals', 'foreign_key' => 'quotation_id'],
                    ['header' => 'quotation_pics_json', 'table' => 'quotation_pics', 'foreign_key' => 'quotation_id'],
                ],
            ],
            [
                'key' => 'contracts',
                'label' => 'Contract',
                'route_key' => 'contracts',
                'root_table' => 'contracts',
                'business_key' => 'contract_number',
                'summary_unit' => 'contract',
                'description' => 'Backup contract berikut surveys, rooms, rentals, billing groups, dan file contract.',
                'notes' => [
                    'Billing group dibawa lengkap dengan assignment building di dalam billing_groups_json.',
                    'Restore mempertahankan id asli untuk meminimalkan mismatch antar relasi room, rental, dan invoice.',
                ],
                'relations' => [
                    ['header' => 'contract_surveys_json', 'table' => 'contract_surveys', 'foreign_key' => 'contract_id'],
                    ['header' => 'contract_rooms_json', 'table' => 'contract_rooms', 'foreign_key' => 'contract_id'],
                    ['header' => 'contract_rentals_json', 'table' => 'contract_rentals', 'foreign_key' => 'contract_id'],
                    ['header' => 'contract_files_json', 'table' => 'contract_files', 'foreign_key' => 'contract_id'],
                    ['header' => 'billing_groups_json', 'mode' => 'billing_groups'],
                ],
            ],
            [
                'key' => 'invoices',
                'label' => 'Invoice',
                'route_key' => 'invoices',
                'root_table' => 'invoices',
                'business_key' => 'invoice_number',
                'summary_unit' => 'invoice',
                'description' => 'Backup invoice lengkap berikut detail item, rental detail, file, activity, dan follow up.',
                'notes' => [
                    'Invoice otomatis maupun manual tetap dibawa sebagai satu paket data invoice.',
                    'File fisik di storage tidak ikut tersalin; backup ini menyimpan metadata database invoice_files.',
                ],
                'relations' => [
                    ['header' => 'invoice_details_json', 'table' => 'invoice_details', 'foreign_key' => 'invoice_id'],
                    ['header' => 'invoice_rental_details_json', 'table' => 'invoice_rental_details', 'foreign_key' => 'invoice_id'],
                    ['header' => 'invoice_files_json', 'table' => 'invoice_files', 'foreign_key' => 'invoice_id'],
                    ['header' => 'invoice_activities_json', 'table' => 'invoice_activities', 'foreign_key' => 'invoice_id'],
                    ['header' => 'invoice_follow_ups_json', 'table' => 'invoice_follow_ups', 'foreign_key' => 'invoice_id'],
                ],
            ],
            [
                'key' => 'master-products',
                'label' => 'Master Product',
                'route_key' => 'master-products',
                'root_table' => 'master_products',
                'business_key' => 'sku',
                'summary_unit' => 'product',
                'description' => 'Backup master product berikut supplier relation dan foto produk.',
                'notes' => [
                    'Relasi brand, variant, category, type, dan packaging tetap aman karena foreign key dan field turunannya ikut diexport.',
                    'Template menampilkan seluruh kolom master_products plus child JSON untuk supplier dan foto.',
                ],
                'relations' => [
                    ['header' => 'product_suppliers_json', 'table' => 'product_suppliers', 'foreign_key' => 'master_product_id'],
                    ['header' => 'product_photos_json', 'table' => 'product_photos', 'foreign_key' => 'master_product_id'],
                ],
            ],
            [
                'key' => 'master-rentals',
                'label' => 'Master Rental',
                'route_key' => 'master-rentals',
                'root_table' => 'master_rentals',
                'business_key' => 'rental_code',
                'summary_unit' => 'rental',
                'description' => 'Backup master rental beserta rental detail, harga, component, dan bottom price.',
                'notes' => [
                    'Komponen rental dibawa lengkap dengan allowed products di dalam rental_components_json.',
                    'Restore menjaga relasi harga dan komponen agar paket rental tetap utuh.',
                ],
                'relations' => [
                    ['header' => 'rental_details_json', 'table' => 'rental_details', 'foreign_key' => 'master_rental_id'],
                    ['header' => 'rental_prices_json', 'table' => 'rental_prices', 'foreign_key' => 'master_rental_id'],
                    ['header' => 'rental_bottom_prices_json', 'table' => 'rental_bottom_prices', 'foreign_key' => 'master_rental_id'],
                    ['header' => 'rental_components_json', 'mode' => 'rental_components'],
                ],
            ],
            [
                'key' => 'unit-on-walls',
                'label' => 'Unit On Wall',
                'route_key' => 'unit-on-walls',
                'root_table' => 'unit_on_walls',
                'business_key' => 'serial_number',
                'summary_unit' => 'unit',
                'description' => 'Backup unit on wall lengkap dengan histori install, remove, service, dan repair.',
                'notes' => [
                    'History tindakan dibawa pada unit_on_wall_histories_json.',
                    'Delete module melakukan soft delete pada unit aktif, bukan menghapus file histori fisik lain.',
                ],
                'relations' => [
                    ['header' => 'unit_on_wall_histories_json', 'table' => 'unit_on_wall_histories', 'foreign_key' => 'unit_on_wall_id'],
                ],
            ],
            [
                'key' => 'master-options',
                'label' => 'Master Options',
                'route_key' => 'master-options',
                'root_table' => 'master_options',
                'business_key' => 'name',
                'summary_unit' => 'option',
                'description' => 'Backup master option berikut option details yang menjadi referensi banyak modul.',
                'notes' => [
                    'Option detail dibawa sebagai satu paket agar relasi brand line, variant, dan option lain tetap konsisten.',
                ],
                'relations' => [
                    ['header' => 'option_details_json', 'table' => 'option_details', 'foreign_key' => 'master_option_id'],
                ],
            ],
            [
                'key' => 'master-banks',
                'label' => 'Master Bank',
                'route_key' => 'master-banks',
                'root_table' => 'banks',
                'business_key' => 'bank_code',
                'summary_unit' => 'bank',
                'description' => 'Backup master bank untuk kebutuhan finance dan VA.',
                'notes' => [
                    'Data bank payment diproses pada modul terpisah agar restore lebih fleksibel.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'bank-payments',
                'label' => 'Bank Payment',
                'route_key' => 'bank-payments',
                'root_table' => 'bank_payments',
                'business_key' => 'account_number',
                'summary_unit' => 'bank payment',
                'description' => 'Backup data rekening bank payment dan VA range per bank.',
                'notes' => [
                    'Relasi bank dipertahankan lewat bank_id dan data rekening utama.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'job-advices',
                'label' => 'Job Advice',
                'route_key' => 'job-advices',
                'root_table' => 'job_advices',
                'business_key' => 'job_advice_number',
                'summary_unit' => 'job advice',
                'description' => 'Backup job advice berikut room dan rental sumbernya.',
                'notes' => [
                    'Job advice rooms dibawa di job_advice_rooms_json agar struktur room tetap utuh.',
                ],
                'relations' => [
                    ['header' => 'job_advice_rooms_json', 'table' => 'job_advice_rooms', 'foreign_key' => 'job_advice_id'],
                ],
            ],
            [
                'key' => 'lost-unit-reports',
                'label' => 'Lost Unit Report',
                'route_key' => 'lost-unit-reports',
                'root_table' => 'lost_unit_reports',
                'business_key' => 'report_number',
                'summary_unit' => 'report',
                'description' => 'Backup lost unit report berikut item detailnya.',
                'notes' => [
                    'Item report dibawa di lost_unit_report_items_json.',
                ],
                'relations' => [
                    ['header' => 'lost_unit_report_items_json', 'table' => 'lost_unit_report_items', 'foreign_key' => 'lost_unit_report_id'],
                ],
            ],
            [
                'key' => 'contract-terminations',
                'label' => 'Contract Termination',
                'route_key' => 'contract-terminations',
                'root_table' => 'contract_terminations',
                'business_key' => 'termination_number',
                'summary_unit' => 'termination',
                'description' => 'Backup data contract termination dan approval field terkait.',
                'notes' => [
                    'Modul ini tidak punya child table utama, jadi backup fokus pada root record termination.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'contract-switchings',
                'label' => 'Contract Switching',
                'route_key' => 'contract-switchings',
                'root_table' => 'contract_switchings',
                'business_key' => 'switching_number',
                'summary_unit' => 'switching',
                'description' => 'Backup data contract switching/customer transfer.',
                'notes' => [
                    'Root table switching diexport utuh untuk menjaga histori perubahan customer dan contract baru.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'aroma-changes',
                'label' => 'Aroma Change',
                'route_key' => 'aroma-changes',
                'root_table' => 'aroma_changes',
                'business_key' => 'change_number',
                'summary_unit' => 'aroma change',
                'description' => 'Backup request aroma change lengkap dengan referensi room dan product type.',
                'notes' => [
                    'Data change diexport dari root table aroma_changes yang sudah memuat snapshot aroma lama dan baru.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'customers',
                'label' => 'Master Customer',
                'route_key' => 'customers',
                'root_table' => 'customers',
                'business_key' => 'customer_code',
                'summary_unit' => 'customer',
                'description' => 'Backup master customer beserta relasi building dan multi PIC customer.',
                'notes' => [
                    'Pivot building customer dan relasi customer contact ikut dibawa agar struktur customer tetap lengkap.',
                ],
                'relations' => [
                    ['header' => 'building_customers_json', 'table' => 'building_customers', 'foreign_key' => 'customer_id'],
                    ['header' => 'customer_contacts_link_json', 'table' => 'customer_customer_contact', 'foreign_key' => 'customer_id'],
                ],
            ],
            [
                'key' => 'customer-contacts',
                'label' => 'Customer Contacts',
                'route_key' => 'customer-contacts',
                'root_table' => 'customer_contacts',
                'business_key' => 'email',
                'summary_unit' => 'contact',
                'description' => 'Backup PIC customer berikut relasi multi customer pada pivot customer_contact.',
                'notes' => [
                    'Relasi many-to-many ke customer ikut dibawa di customer_contacts_link_json.',
                ],
                'relations' => [
                    ['header' => 'customer_contacts_link_json', 'table' => 'customer_customer_contact', 'foreign_key' => 'customer_contact_id'],
                ],
            ],
            [
                'key' => 'customer-taxes',
                'label' => 'Master Customer Tax',
                'route_key' => 'customer-taxes',
                'root_table' => 'customer_tax_settings',
                'business_key' => 'tax_number',
                'summary_unit' => 'tax setting',
                'description' => 'Backup pengaturan tax customer per customer.',
                'notes' => [
                    'Dipakai untuk kebutuhan tax address, ppn code, dan pengaturan pajak customer.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'customer-types',
                'label' => 'Master Customer Category',
                'route_key' => 'customer-types',
                'root_table' => 'customer_types',
                'business_key' => 'name',
                'summary_unit' => 'category',
                'description' => 'Backup kategori customer yang dipakai pada master customer.',
                'notes' => [
                    'Modul ringan tanpa child table, aman untuk import/export cepat.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'company-virtual-accounts',
                'label' => 'Company Virtual Account',
                'route_key' => 'company-virtual-accounts',
                'root_table' => 'company_virtual_accounts',
                'business_key' => 'account_number',
                'summary_unit' => 'virtual account',
                'description' => 'Backup company virtual account per company/customer.',
                'notes' => [
                    'Root table VA dibackup utuh untuk menjaga nomor account dan relasi bank payment.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'buildings',
                'label' => 'Master Building',
                'route_key' => 'buildings',
                'root_table' => 'buildings',
                'business_key' => 'name',
                'summary_unit' => 'building',
                'description' => 'Backup master building beserta pivot customer gedung.',
                'notes' => [
                    'Relasi building ke customer ikut dibawa pada building_customers_json.',
                ],
                'relations' => [
                    ['header' => 'building_customers_json', 'table' => 'building_customers', 'foreign_key' => 'building_id'],
                ],
            ],
            [
                'key' => 'master-rooms',
                'label' => 'Master Room',
                'route_key' => 'master-rooms',
                'root_table' => 'master_rooms',
                'business_key' => 'room_code',
                'summary_unit' => 'room',
                'description' => 'Backup master room per building.',
                'notes' => [
                    'Dipakai sebagai referensi quotation, contract, job advice, dan unit on wall.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'branches',
                'label' => 'Master Branch',
                'route_key' => 'branches',
                'root_table' => 'branches',
                'business_key' => 'code',
                'summary_unit' => 'branch',
                'description' => 'Backup master branch berikut assignment user multi branch.',
                'notes' => [
                    'Pivot branch_user ikut dibawa pada branch_users_json.',
                ],
                'relations' => [
                    ['header' => 'branch_users_json', 'table' => 'branch_user', 'foreign_key' => 'branch_id'],
                ],
            ],
            [
                'key' => 'product-types',
                'label' => 'Product Type',
                'route_key' => 'product-types',
                'root_table' => 'product_types',
                'business_key' => 'name',
                'summary_unit' => 'product type',
                'description' => 'Backup product type berikut atribut dinamisnya.',
                'notes' => [
                    'Atribut product type ikut dibawa pada product_type_attributes_json.',
                ],
                'relations' => [
                    ['header' => 'product_type_attributes_json', 'table' => 'product_type_attributes', 'foreign_key' => 'product_type_id'],
                ],
            ],
            [
                'key' => 'brand-variants',
                'label' => 'Brand Variant',
                'route_key' => 'brand-variants',
                'root_table' => 'product_brand_variants',
                'business_key' => 'name',
                'summary_unit' => 'brand variant',
                'description' => 'Backup brand variant untuk kebutuhan master product.',
                'notes' => [
                    'Relasi brand line tetap aman lewat brand_line_id.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'product-categories',
                'label' => 'Product Structure',
                'route_key' => 'product-structure',
                'root_table' => 'product_categories',
                'business_key' => 'code',
                'summary_unit' => 'category',
                'description' => 'Backup struktur kategori produk yang menjadi induk product type dan master product.',
                'notes' => [
                    'Hierarki parent-child dipertahankan lewat parent_id pada root table category.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'rental-service-frequencies',
                'label' => 'Rental Service Frequency',
                'route_key' => 'rental-service-frequencies',
                'root_table' => 'rental_service_frequencies',
                'business_key' => 'code',
                'summary_unit' => 'frequency',
                'description' => 'Backup master frequency service yang dipakai pada master rental.',
                'notes' => [
                    'Ini menjawab kebutuhan modul frequency yang kamu sebut tadi.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'contract-assigned',
                'label' => 'Contract Assigned',
                'route_key' => 'contract-assigned',
                'root_table' => 'contract_assigned',
                'business_key' => 'switching_number',
                'summary_unit' => 'assignment',
                'description' => 'Backup transfer contract ke marketing lain pada modul Contract Assigned.',
                'notes' => [
                    'Table contract_assigned dibackup utuh sebagai histori perpindahan marketing contract.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'master-price-slabs',
                'label' => 'Master Price Slab',
                'route_key' => 'master-price-slabs',
                'root_table' => 'master_price_slabs',
                'business_key' => 'slab_code',
                'summary_unit' => 'price slab',
                'description' => 'Backup aturan price slab yang terkait ke master rental.',
                'notes' => [
                    'Root table cukup untuk menjaga range qty, harga, dan diskon tiap slab.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'master-teams',
                'label' => 'Master Team',
                'route_key' => 'teams',
                'root_table' => 'teams',
                'business_key' => 'team_code',
                'summary_unit' => 'team',
                'description' => 'Backup master team berikut anggota tim aktif dan histori pivot team_members.',
                'notes' => [
                    'Pivot team_members ikut dibawa pada team_members_json.',
                ],
                'relations' => [
                    ['header' => 'team_members_json', 'table' => 'team_members', 'foreign_key' => 'team_id'],
                ],
            ],
            [
                'key' => 'job-schedules',
                'label' => 'Job Schedule',
                'route_key' => 'job-schedules',
                'root_table' => 'job_schedules',
                'business_key' => 'job_number',
                'summary_unit' => 'job',
                'description' => 'Backup job schedule lengkap berikut room, assignment room, room rental, report, photo, dan BA file.',
                'notes' => [
                    'Child utama job dibawa supaya restore tidak hanya membuat header job kosong.',
                    'File fisik tetap tidak ikut tersalin; backup ini menyimpan metadata database job photo dan BA file.',
                ],
                'relations' => [
                    ['header' => 'job_schedule_rooms_json', 'table' => 'job_schedule_rooms', 'foreign_key' => 'job_schedule_id'],
                    ['header' => 'job_schedule_room_assignments_json', 'table' => 'job_schedule_room_assignments', 'foreign_key' => 'job_schedule_id'],
                    ['header' => 'job_schedule_room_rentals_json', 'mode' => 'job_schedule_room_rentals'],
                    ['header' => 'job_reports_json', 'table' => 'job_reports', 'foreign_key' => 'job_schedule_id'],
                    ['header' => 'job_photos_json', 'table' => 'job_photos', 'foreign_key' => 'job_schedule_id'],
                    ['header' => 'job_schedule_ba_files_json', 'table' => 'job_schedule_ba_files', 'foreign_key' => 'job_schedule_id'],
                ],
            ],
            [
                'key' => 'material-assign',
                'label' => 'Material Assign',
                'route_key' => 'job-assign-material-issues',
                'root_table' => 'material_issues',
                'business_key' => 'issue_number',
                'summary_unit' => 'material issue',
                'description' => 'Backup material assign berikut item detail dan pivot ke job assign schedule.',
                'notes' => [
                    'Material issue items dan mapping job_assign_material_issues ikut dibawa agar struktur assign tetap utuh.',
                ],
                'relations' => [
                    ['header' => 'material_issue_items_json', 'table' => 'material_issue_items', 'foreign_key' => 'material_issue_id'],
                    ['header' => 'job_assign_material_issues_json', 'table' => 'job_assign_material_issues', 'foreign_key' => 'material_issue_id'],
                ],
            ],
            [
                'key' => 'invoice-follow-ups',
                'label' => 'Invoice Follow Up',
                'route_key' => 'invoice-follow-ups',
                'root_table' => 'invoice_follow_ups',
                'business_key' => 'id',
                'summary_unit' => 'follow up',
                'description' => 'Backup histori follow up invoice untuk email, telepon, visit, dan letter.',
                'notes' => [
                    'Modul ini berdiri sendiri agar histori follow up bisa dibackup tanpa harus meng-export semua invoice.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'tax-settings',
                'label' => 'Tax Setting',
                'route_key' => 'tax-settings',
                'root_table' => 'tax_settings',
                'business_key' => 'tax_code',
                'summary_unit' => 'tax setting',
                'description' => 'Backup aturan tax setting lengkap untuk invoice dan quotation.',
                'notes' => [
                    'Tax code, rate, metode kalkulasi, dan range minimum-maksimum ikut dibawa utuh.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'tax-codes',
                'label' => 'Kode Pajak',
                'route_key' => 'tax-codes',
                'root_table' => 'finance_tax_codes',
                'business_key' => 'code',
                'summary_unit' => 'tax code',
                'description' => 'Backup master kode pajak transaksi finance yang dipakai untuk status invoice, faktur, dan perlakuan PPN.',
                'notes' => [
                    'Mengambil data dari tabel finance_tax_codes yang berisi 9 kode transaksi pajak finance.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'warehouses',
                'label' => 'Warehouses',
                'route_key' => 'warehouses',
                'root_table' => 'warehouses',
                'business_key' => 'warehouse_code',
                'summary_unit' => 'warehouse',
                'description' => 'Backup master warehouse berikut admin warehouse.',
                'notes' => [
                    'Pivot warehouse_admins ikut dibawa pada warehouse_admins_json.',
                ],
                'relations' => [
                    ['header' => 'warehouse_admins_json', 'table' => 'warehouse_admins', 'foreign_key' => 'warehouse_id'],
                ],
            ],
            [
                'key' => 'inventory-requests',
                'label' => 'Inventory Request',
                'route_key' => 'inventory-requests',
                'root_table' => 'inventory_requests',
                'business_key' => 'request_number',
                'summary_unit' => 'request',
                'description' => 'Backup inventory request berikut item permintaan per produk.',
                'notes' => [
                    'Item request dibawa di inventory_request_items_json.',
                ],
                'relations' => [
                    ['header' => 'inventory_request_items_json', 'table' => 'inventory_request_items', 'foreign_key' => 'inventory_request_id'],
                ],
            ],
            [
                'key' => 'inventory-issuings',
                'label' => 'Inventory Issuing',
                'route_key' => 'inventory-issuings',
                'root_table' => 'inventory_issuings',
                'business_key' => 'issuing_number',
                'summary_unit' => 'issuing',
                'description' => 'Backup inventory issuing berikut item issued dan serial number yang terkait.',
                'notes' => [
                    'Item issuing dibawa di inventory_issuing_items_json.',
                ],
                'relations' => [
                    ['header' => 'inventory_issuing_items_json', 'table' => 'inventory_issuing_items', 'foreign_key' => 'inventory_issuing_id'],
                ],
            ],
            [
                'key' => 'inventory-receivings',
                'label' => 'Inventory Receiving',
                'route_key' => 'inventory-receivings',
                'root_table' => 'inventory_receivings',
                'business_key' => 'receiving_number',
                'summary_unit' => 'receiving',
                'description' => 'Backup inventory receiving berikut item penerimaan per produk.',
                'notes' => [
                    'Item receiving dibawa di inventory_receiving_items_json.',
                ],
                'relations' => [
                    ['header' => 'inventory_receiving_items_json', 'table' => 'inventory_receiving_items', 'foreign_key' => 'inventory_receiving_id'],
                ],
            ],
            [
                'key' => 'stock-opnames',
                'label' => 'Stock Opname',
                'route_key' => 'stock-opnames',
                'root_table' => 'stock_opnames',
                'business_key' => 'opname_number',
                'summary_unit' => 'opname',
                'description' => 'Backup stock opname berikut detail variance dan serial number scan.',
                'notes' => [
                    'Detail opname dibawa di stock_opname_details_json.',
                ],
                'relations' => [
                    ['header' => 'stock_opname_details_json', 'table' => 'stock_opname_details', 'foreign_key' => 'stock_opname_id'],
                ],
            ],
            [
                'key' => 'stock-adjustments',
                'label' => 'Stock Adjustment',
                'route_key' => 'stock-adjustments',
                'root_table' => 'stock_adjustments',
                'business_key' => 'adjustment_no',
                'summary_unit' => 'adjustment',
                'description' => 'Backup stock adjustment berikut item penyesuaian per produk.',
                'notes' => [
                    'Detail adjustment dibawa di stock_adjustment_items_json.',
                ],
                'relations' => [
                    ['header' => 'stock_adjustment_items_json', 'table' => 'stock_adjustment_items', 'foreign_key' => 'stock_adjustment_id'],
                ],
            ],
            [
                'key' => 'serial-numbers',
                'label' => 'Serial Number',
                'route_key' => 'serial-numbers',
                'root_table' => 'serial_numbers',
                'business_key' => 'serial_number',
                'summary_unit' => 'serial number',
                'description' => 'Backup master serial number untuk tracking unit, receiving, dan issuing.',
                'notes' => [
                    'Root serial number cukup untuk menjaga histori lokasi, status, dan keterkaitan receiving.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'logistics-trackings',
                'label' => 'Logistics Tracking',
                'route_key' => 'inventory-logistics',
                'root_table' => 'logistics_tracking',
                'business_key' => 'tracking_number',
                'summary_unit' => 'tracking',
                'description' => 'Backup logistics tracking perpindahan barang antar warehouse dan branch.',
                'notes' => [
                    'Modul ini membackup root tracking logistik; BA dan purchasing request tetap tersedia sebagai modul terpisah.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'berita-acara',
                'label' => 'Berita Acara',
                'route_key' => 'berita-acara',
                'root_table' => 'berita_acara',
                'business_key' => 'berita_acara_number',
                'summary_unit' => 'berita acara',
                'description' => 'Backup berita acara logistik/receiving untuk loss, damage, dan discrepancy.',
                'notes' => [
                    'Root table berita_acara dibawa utuh berikut nomor BA, status, dan referensi receiving/logistics.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'purchasing-requests',
                'label' => 'Purchasing Requests',
                'route_key' => 'purchasing-requests',
                'root_table' => 'purchasing_requests',
                'business_key' => 'request_number',
                'summary_unit' => 'purchasing request',
                'description' => 'Backup purchasing request dari alur logistics dan warehouse.',
                'notes' => [
                    'Root request dibawa utuh untuk menjaga histori approval dan purchasing flow.',
                ],
                'relations' => [],
            ],
            [
                'key' => 'companies',
                'label' => 'Master Company',
                'route_key' => 'companies',
                'root_table' => 'companies',
                'business_key' => 'code',
                'summary_unit' => 'company',
                'description' => 'Backup data master company.',
                'notes' => [
                    'Dipakai sebagai referensi branch, company VA, dan setting company lainnya.',
                ],
                'relations' => [],
            ],
        ];
    }

    protected function buildSnapshotModule(array $config): ?array
    {
        if (!$this->tableExists($config['root_table'])) {
            return null;
        }

        $count = $this->snapshotRootQuery($config['root_table'])->count();
        $headers = array_merge(
            $this->getTableColumns($config['root_table']),
            collect($config['relations'] ?? [])->pluck('header')->all()
        );

        return [
            'key' => $config['key'],
            'label' => $config['label'],
            'route_key' => $config['route_key'],
            'description' => $config['description'],
            'notes' => $config['notes'],
            'headers' => $headers,
            'required_headers' => ['id'],
            'record_count' => $count,
            'record_summary' => $count . ' ' . ($config['summary_unit'] ?? 'data'),
        ];
    }

    protected function isSnapshotModule(string $moduleKey): bool
    {
        return $this->getSnapshotModuleConfig($moduleKey) !== null;
    }

    protected function getSnapshotModuleConfig(string $moduleKey): ?array
    {
        foreach ($this->snapshotModuleConfigs() as $config) {
            if (($config['key'] ?? null) === $moduleKey && $this->tableExists($config['root_table'])) {
                return $config;
            }
        }

        return null;
    }

    protected function exportSnapshotRows(string $moduleKey): iterable
    {
        $config = $this->getSnapshotModuleConfig($moduleKey);

        if ($config === null) {
            throw new \InvalidArgumentException("Unsupported backup module [{$moduleKey}]");
        }

        $query = $this->snapshotRootQuery($config['root_table']);

        foreach ($this->iterateSnapshotQuery($query, $config['root_table']) as $record) {
            $row = (array) $record;

            foreach ($config['relations'] ?? [] as $relation) {
                $row[$relation['header']] = $this->exportSnapshotRelation($relation, $row);
            }

            yield $row;
        }
    }

    protected function importSnapshotRows(string $moduleKey, array $rows, User $actor): array
    {
        $config = $this->getSnapshotModuleConfig($moduleKey);

        if ($config === null) {
            throw new \InvalidArgumentException("Unsupported backup module [{$moduleKey}]");
        }

        $stats = $this->blankStats();
        $table = $config['root_table'];
        $columns = $this->getTableColumns($table);

        foreach ($rows as $row) {
            $relationPayloads = [];

            foreach ($config['relations'] ?? [] as $relation) {
                $relationPayloads[$relation['header']] = $this->decodeJsonColumn($row[$relation['header']] ?? null) ?? [];
            }

            $rootData = [];

            foreach ($columns as $column) {
                if (array_key_exists($column, $row)) {
                    $rootData[$column] = $this->normalizeSnapshotValue($row[$column]);
                }
            }

            if ($this->columnExists($table, 'created_by') && empty($rootData['created_by'])) {
                $rootData['created_by'] = $actor->id;
            }

            if ($this->columnExists($table, 'updated_by') && empty($rootData['updated_by'])) {
                $rootData['updated_by'] = $actor->id;
            }

            if ($this->columnExists($table, 'deleted_at')) {
                $rootData['deleted_at'] = null;
            }

            $identifier = $this->resolveSnapshotRootIdentifier($config, $rootData);

            if ($identifier === null) {
                $stats['skipped']++;
                $stats['warnings'][] = "Baris {$config['label']} dilewati karena id/business key kosong.";
                continue;
            }

            $existing = DB::table($table)->where($identifier['column'], $identifier['value'])->first();

            if ($existing) {
                $wasSoftDeleted = $this->columnExists($table, 'deleted_at') && !empty($existing->deleted_at);
                DB::table($table)->where('id', $existing->id)->update($rootData);
                $rootId = (int) $existing->id;
                $stats['updated']++;

                if ($wasSoftDeleted) {
                    $stats['restored']++;
                }
            } else {
                if (isset($rootData['id']) && $rootData['id'] !== null) {
                    DB::table($table)->insert($rootData);
                    $rootId = (int) $rootData['id'];
                } else {
                    $rootId = (int) DB::table($table)->insertGetId($rootData);
                }

                $stats['created']++;
            }

            $this->clearSnapshotRelations($config, $rootId);

            foreach ($config['relations'] ?? [] as $relation) {
                $this->importSnapshotRelation($relation, $rootId, $relationPayloads[$relation['header']] ?? [], $actor);
            }
        }

        return $stats;
    }

    protected function deleteSnapshotRows(string $moduleKey, User $actor): array
    {
        $config = $this->getSnapshotModuleConfig($moduleKey);

        if ($config === null) {
            throw new \InvalidArgumentException("Unsupported backup module [{$moduleKey}]");
        }

        $table = $config['root_table'];
        $query = $this->snapshotRootQuery($table);
        $count = (clone $query)->count();

        if ($this->columnExists($table, 'deleted_at')) {
            $payload = ['deleted_at' => now()];

            if ($this->columnExists($table, 'updated_at')) {
                $payload['updated_at'] = now();
            }

            if ($this->columnExists($table, 'updated_by')) {
                $payload['updated_by'] = $actor->id;
            }

            $query->update($payload);

            return $this->deleteStats($count, ['Delete dilakukan sebagai soft delete pada root module.']);
        }

        $query->delete();

        return $this->deleteStats($count);
    }

    protected function snapshotRootQuery(string $table)
    {
        $query = DB::table($table);

        if ($this->columnExists($table, 'deleted_at')) {
            $query->whereNull("{$table}.deleted_at");
        }

        return $query;
    }

    protected function iterateSnapshotQuery($query, string $table): iterable
    {
        if ($this->columnExists($table, 'id')) {
            return $query->orderBy("{$table}.id")->lazyById(200, 'id');
        }

        return $query->cursor();
    }

    protected function exportSnapshotRelation(array $relation, array $rootRow): string
    {
        return match ($relation['mode'] ?? 'table') {
            'billing_groups' => json_encode($this->exportBillingGroupsRelation((int) $rootRow['id']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'job_schedule_room_rentals' => json_encode($this->exportJobScheduleRoomRentalsRelation((int) $rootRow['id']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'rental_components' => json_encode($this->exportRentalComponentsRelation((int) $rootRow['id']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => json_encode($this->exportDirectSnapshotRelation($relation, (int) $rootRow['id']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        } ?: '[]';
    }

    protected function exportDirectSnapshotRelation(array $relation, int $rootId): array
    {
        if (!$this->tableExists($relation['table'])) {
            return [];
        }

        $query = DB::table($relation['table'])->where($relation['foreign_key'], $rootId);

        if ($this->columnExists($relation['table'], 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query
            ->orderBy($this->columnExists($relation['table'], 'id') ? 'id' : $relation['foreign_key'])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    protected function exportBillingGroupsRelation(int $contractId): array
    {
        if (!$this->tableExists('billing_groups')) {
            return [];
        }

        return DB::table('billing_groups')
            ->where('contract_id', $contractId)
            ->when($this->columnExists('billing_groups', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('id')
            ->get()
            ->map(function ($billingGroup) {
                $group = (array) $billingGroup;
                $group['billing_group_buildings'] = $this->tableExists('billing_group_buildings')
                    ? DB::table('billing_group_buildings')
                        ->where('billing_group_id', $group['id'])
                        ->when($this->columnExists('billing_group_buildings', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
                        ->orderBy('id')
                        ->get()
                        ->map(fn ($row) => (array) $row)
                        ->all()
                    : [];

                return $group;
            })
            ->all();
    }

    protected function exportRentalComponentsRelation(int $masterRentalId): array
    {
        if (!$this->tableExists('rental_components')) {
            return [];
        }

        return DB::table('rental_components')
            ->where('master_rental_id', $masterRentalId)
            ->when($this->columnExists('rental_components', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('id')
            ->get()
            ->map(function ($component) {
                $row = (array) $component;
                $row['component_products'] = $this->tableExists('rental_component_products')
                    ? DB::table('rental_component_products')
                        ->where('rental_component_id', $row['id'])
                        ->when($this->columnExists('rental_component_products', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
                        ->orderBy('id')
                        ->get()
                        ->map(fn ($item) => (array) $item)
                        ->all()
                    : [];

                return $row;
            })
            ->all();
    }

    protected function exportJobScheduleRoomRentalsRelation(int $jobScheduleId): array
    {
        if (!$this->tableExists('job_schedule_room_rentals') || !$this->tableExists('job_schedule_rooms')) {
            return [];
        }

        $roomIds = DB::table('job_schedule_rooms')
            ->where('job_schedule_id', $jobScheduleId)
            ->when($this->columnExists('job_schedule_rooms', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->pluck('id');

        if ($roomIds->isEmpty()) {
            return [];
        }

        return DB::table('job_schedule_room_rentals')
            ->whereIn('job_schedule_room_id', $roomIds)
            ->when($this->columnExists('job_schedule_room_rentals', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    protected function clearSnapshotRelations(array $config, int $rootId): void
    {
        foreach (array_reverse($config['relations'] ?? []) as $relation) {
            match ($relation['mode'] ?? 'table') {
                'billing_groups' => $this->clearBillingGroupsRelation($rootId),
                'job_schedule_room_rentals' => $this->clearJobScheduleRoomRentalsRelation($rootId),
                'rental_components' => $this->clearRentalComponentsRelation($rootId),
                default => $this->clearDirectSnapshotRelation($relation, $rootId),
            };
        }
    }

    protected function clearDirectSnapshotRelation(array $relation, int $rootId): void
    {
        if (!$this->tableExists($relation['table'])) {
            return;
        }

        DB::table($relation['table'])->where($relation['foreign_key'], $rootId)->delete();
    }

    protected function clearBillingGroupsRelation(int $contractId): void
    {
        if (!$this->tableExists('billing_groups')) {
            return;
        }

        $billingGroupIds = DB::table('billing_groups')->where('contract_id', $contractId)->pluck('id');

        if ($this->tableExists('billing_group_buildings') && $billingGroupIds->isNotEmpty()) {
            DB::table('billing_group_buildings')->whereIn('billing_group_id', $billingGroupIds)->delete();
        }

        DB::table('billing_groups')->where('contract_id', $contractId)->delete();
    }

    protected function clearRentalComponentsRelation(int $masterRentalId): void
    {
        if (!$this->tableExists('rental_components')) {
            return;
        }

        $componentIds = DB::table('rental_components')->where('master_rental_id', $masterRentalId)->pluck('id');

        if ($this->tableExists('rental_component_products') && $componentIds->isNotEmpty()) {
            DB::table('rental_component_products')->whereIn('rental_component_id', $componentIds)->delete();
        }

        DB::table('rental_components')->where('master_rental_id', $masterRentalId)->delete();
    }

    protected function clearJobScheduleRoomRentalsRelation(int $jobScheduleId): void
    {
        if (!$this->tableExists('job_schedule_room_rentals') || !$this->tableExists('job_schedule_rooms')) {
            return;
        }

        $roomIds = DB::table('job_schedule_rooms')->where('job_schedule_id', $jobScheduleId)->pluck('id');

        if ($roomIds->isEmpty()) {
            return;
        }

        DB::table('job_schedule_room_rentals')->whereIn('job_schedule_room_id', $roomIds)->delete();
    }

    protected function importSnapshotRelation(array $relation, int $rootId, array $payload, User $actor): void
    {
        match ($relation['mode'] ?? 'table') {
            'billing_groups' => $this->importBillingGroupsRelation($rootId, $payload, $actor),
            'job_schedule_room_rentals' => $this->importJobScheduleRoomRentalsRelation($rootId, $payload, $actor),
            'rental_components' => $this->importRentalComponentsRelation($rootId, $payload, $actor),
            default => $this->importDirectSnapshotRelation($relation, $rootId, $payload, $actor),
        };
    }

    protected function importDirectSnapshotRelation(array $relation, int $rootId, array $payload, User $actor): void
    {
        if (!$this->tableExists($relation['table']) || empty($payload)) {
            return;
        }

        $columns = $this->getTableColumns($relation['table']);

        foreach ($payload as $row) {
            if (!is_array($row)) {
                continue;
            }

            $insert = [];

            foreach ($columns as $column) {
                if (array_key_exists($column, $row)) {
                    $insert[$column] = $this->normalizeSnapshotValue($row[$column]);
                }
            }

            $insert[$relation['foreign_key']] = $rootId;

            if ($this->columnExists($relation['table'], 'deleted_at')) {
                $insert['deleted_at'] = null;
            }

            if ($this->columnExists($relation['table'], 'created_by') && empty($insert['created_by'])) {
                $insert['created_by'] = $actor->id;
            }

            if ($this->columnExists($relation['table'], 'updated_by') && empty($insert['updated_by'])) {
                $insert['updated_by'] = $actor->id;
            }

            if ($relation['table'] === 'invoice_rental_details' && empty($insert['room_name'])) {
                $insert['room_name'] = '-';
            }

            DB::table($relation['table'])->insert($insert);
        }
    }

    protected function importBillingGroupsRelation(int $contractId, array $payload, User $actor): void
    {
        if (!$this->tableExists('billing_groups') || empty($payload)) {
            return;
        }

        $billingGroupColumns = $this->getTableColumns('billing_groups');
        $buildingColumns = $this->tableExists('billing_group_buildings') ? $this->getTableColumns('billing_group_buildings') : [];

        foreach ($payload as $billingGroup) {
            if (!is_array($billingGroup)) {
                continue;
            }

            $buildings = is_array($billingGroup['billing_group_buildings'] ?? null) ? $billingGroup['billing_group_buildings'] : [];
            unset($billingGroup['billing_group_buildings']);

            $insert = [];

            foreach ($billingGroupColumns as $column) {
                if (array_key_exists($column, $billingGroup)) {
                    $insert[$column] = $this->normalizeSnapshotValue($billingGroup[$column]);
                }
            }

            $insert['contract_id'] = $contractId;

            if ($this->columnExists('billing_groups', 'deleted_at')) {
                $insert['deleted_at'] = null;
            }

            if ($this->columnExists('billing_groups', 'created_by') && empty($insert['created_by'])) {
                $insert['created_by'] = $actor->id;
            }

            if ($this->columnExists('billing_groups', 'updated_by') && empty($insert['updated_by'])) {
                $insert['updated_by'] = $actor->id;
            }

            DB::table('billing_groups')->insert($insert);
            $billingGroupId = (int) ($insert['id'] ?? 0);

            if ($billingGroupId === 0 || empty($buildings) || empty($buildingColumns)) {
                continue;
            }

            foreach ($buildings as $building) {
                if (!is_array($building)) {
                    continue;
                }

                $buildingInsert = [];

                foreach ($buildingColumns as $column) {
                    if (array_key_exists($column, $building)) {
                        $buildingInsert[$column] = $this->normalizeSnapshotValue($building[$column]);
                    }
                }

                $buildingInsert['billing_group_id'] = $billingGroupId;

                if ($this->columnExists('billing_group_buildings', 'deleted_at')) {
                    $buildingInsert['deleted_at'] = null;
                }

                if ($this->columnExists('billing_group_buildings', 'created_by') && empty($buildingInsert['created_by'])) {
                    $buildingInsert['created_by'] = $actor->id;
                }

                if ($this->columnExists('billing_group_buildings', 'updated_by') && empty($buildingInsert['updated_by'])) {
                    $buildingInsert['updated_by'] = $actor->id;
                }

                DB::table('billing_group_buildings')->insert($buildingInsert);
            }
        }
    }

    protected function importRentalComponentsRelation(int $masterRentalId, array $payload, User $actor): void
    {
        if (!$this->tableExists('rental_components') || empty($payload)) {
            return;
        }

        $componentColumns = $this->getTableColumns('rental_components');
        $componentProductColumns = $this->tableExists('rental_component_products') ? $this->getTableColumns('rental_component_products') : [];

        foreach ($payload as $component) {
            if (!is_array($component)) {
                continue;
            }

            $products = is_array($component['component_products'] ?? null) ? $component['component_products'] : [];
            unset($component['component_products']);

            $insert = [];

            foreach ($componentColumns as $column) {
                if (array_key_exists($column, $component)) {
                    $insert[$column] = $this->normalizeSnapshotValue($component[$column]);
                }
            }

            $insert['master_rental_id'] = $masterRentalId;

            if ($this->columnExists('rental_components', 'deleted_at')) {
                $insert['deleted_at'] = null;
            }

            if ($this->columnExists('rental_components', 'created_by') && empty($insert['created_by'])) {
                $insert['created_by'] = $actor->id;
            }

            if ($this->columnExists('rental_components', 'updated_by') && empty($insert['updated_by'])) {
                $insert['updated_by'] = $actor->id;
            }

            DB::table('rental_components')->insert($insert);
            $componentId = (int) ($insert['id'] ?? 0);

            if ($componentId === 0 || empty($products) || empty($componentProductColumns)) {
                continue;
            }

            foreach ($products as $product) {
                if (!is_array($product)) {
                    continue;
                }

                $productInsert = [];

                foreach ($componentProductColumns as $column) {
                    if (array_key_exists($column, $product)) {
                        $productInsert[$column] = $this->normalizeSnapshotValue($product[$column]);
                    }
                }

                $productInsert['rental_component_id'] = $componentId;

                if ($this->columnExists('rental_component_products', 'deleted_at')) {
                    $productInsert['deleted_at'] = null;
                }

                if ($this->columnExists('rental_component_products', 'created_by') && empty($productInsert['created_by'])) {
                    $productInsert['created_by'] = $actor->id;
                }

                if ($this->columnExists('rental_component_products', 'updated_by') && empty($productInsert['updated_by'])) {
                    $productInsert['updated_by'] = $actor->id;
                }

                DB::table('rental_component_products')->insert($productInsert);
            }
        }
    }

    protected function importJobScheduleRoomRentalsRelation(int $jobScheduleId, array $payload, User $actor): void
    {
        if (!$this->tableExists('job_schedule_room_rentals') || empty($payload)) {
            return;
        }

        $columns = $this->getTableColumns('job_schedule_room_rentals');
        $roomIds = DB::table('job_schedule_rooms')->where('job_schedule_id', $jobScheduleId)->pluck('id')->flip();

        foreach ($payload as $row) {
            if (!is_array($row)) {
                continue;
            }

            $roomId = (int) ($row['job_schedule_room_id'] ?? 0);

            if ($roomId === 0 || !$roomIds->has($roomId)) {
                continue;
            }

            $insert = [];

            foreach ($columns as $column) {
                if (array_key_exists($column, $row)) {
                    $insert[$column] = $this->normalizeSnapshotValue($row[$column]);
                }
            }

            $insert['job_schedule_room_id'] = $roomId;

            if ($this->columnExists('job_schedule_room_rentals', 'deleted_at')) {
                $insert['deleted_at'] = null;
            }

            if ($this->columnExists('job_schedule_room_rentals', 'created_by') && empty($insert['created_by'])) {
                $insert['created_by'] = $actor->id;
            }

            if ($this->columnExists('job_schedule_room_rentals', 'updated_by') && empty($insert['updated_by'])) {
                $insert['updated_by'] = $actor->id;
            }

            DB::table('job_schedule_room_rentals')->insert($insert);
        }
    }

    protected function resolveSnapshotRootIdentifier(array $config, array $rootData): ?array
    {
        if (isset($rootData['id']) && $rootData['id'] !== null && $rootData['id'] !== '') {
            return ['column' => 'id', 'value' => (int) $rootData['id']];
        }

        $businessKey = $config['business_key'] ?? null;

        if ($businessKey && isset($rootData[$businessKey]) && $rootData[$businessKey] !== null && $rootData[$businessKey] !== '') {
            return ['column' => $businessKey, 'value' => $rootData[$businessKey]];
        }

        return null;
    }

    protected function normalizeSnapshotValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed === '' ? null : $trimmed;
        }

        return $value;
    }

    protected function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    protected function columnExists(string $table, string $column): bool
    {
        return in_array($column, $this->getTableColumns($table), true);
    }

    protected function getTableColumns(string $table): array
    {
        static $columns = [];

        if (!array_key_exists($table, $columns)) {
            $columns[$table] = $this->tableExists($table)
                ? DB::getSchemaBuilder()->getColumnListing($table)
                : [];
        }

        return $columns[$table];
    }

    protected function buildDepartmentModule(): array
    {
        $count = Department::count();

        return [
            'key' => 'departments',
            'label' => 'Master Department',
            'route_key' => 'departments',
            'description' => 'Backup data departemen termasuk parent department dan status aktif.',
            'notes' => [
                'Template memakai relasi parent lewat kolom parent_department_name.',
                'Hapus hanya melakukan soft delete, jadi masih aman untuk restore ulang.',
            ],
            'headers' => [
                'department_name',
                'parent_department_name',
                'description',
                'system_reserved',
                'is_active',
            ],
            'required_headers' => ['department_name'],
            'record_count' => $count,
            'record_summary' => $count . ' department',
        ];
    }

    protected function buildUserModule(): array
    {
        $count = User::count();

        return [
            'key' => 'users',
            'label' => 'Master Users',
            'route_key' => 'users',
            'description' => 'Backup user utama beserta role relation, primary branch, dan multi-branch assignment.',
            'notes' => [
                'Export menyertakan password_hash supaya restore bisa penuh tanpa isi ulang password.',
                'Untuk restore lintas environment, branch disocokkan lewat branch_code atau branch_name.',
                'Import user paling aman dilakukan setelah import Department dan Role.',
            ],
            'headers' => [
                'nik',
                'name',
                'email',
                'username',
                'password',
                'password_hash',
                'department_name',
                'branch_code',
                'branch_name',
                'primary_branch_code',
                'assigned_branch_codes',
                'position_name',
                'salutation',
                'phone',
                'handphone_1',
                'handphone_2',
                'join_date',
                'employee_status',
                'gender',
                'marital_status',
                'religion',
                'identity_type',
                'identity_number',
                'npwp_number',
                'bpjs_number',
                'bpjs_date',
                'blood_type',
                'rhesus',
                'address_1',
                'address_2',
                'emergency_contact_name',
                'emergency_contact_number',
                'bank_name',
                'bank_account_number',
                'bank_account_holder',
                'is_active',
                'multi_login',
                'is_frozen',
                'screenshot_allowed',
                'is_commission_achiever',
                'roles',
                'role_names',
            ],
            'required_headers' => ['name', 'email', 'username'],
            'record_count' => $count,
            'record_summary' => $count . ' user',
        ];
    }

    protected function buildRoleModule(): array
    {
        $count = Role::count();

        return [
            'key' => 'roles',
            'label' => 'Master Roles',
            'route_key' => 'roles',
            'description' => 'Backup role dan permission relation yang aktif pada tiap role.',
            'notes' => [
                'Permission diexport sebagai nama permission agar tetap aman lintas database.',
                'Role system_reserved tidak ikut dihapus demi menjaga akses inti aplikasi.',
            ],
            'headers' => [
                'role_name',
                'description',
                'permission_names',
                'system_reserved',
                'is_active',
            ],
            'required_headers' => ['role_name'],
            'record_count' => $count,
            'record_summary' => $count . ' role',
        ];
    }

    protected function buildAccessControlModule(): array
    {
        $levels = UserAccessLevel::count();
        $restrictions = UserLoginRestriction::count();

        return [
            'key' => 'access-control',
            'label' => 'Hirarki Data',
            'route_key' => 'access-control',
            'description' => 'Backup hierarchy access, peer access, branch access, login restriction, dan flag keamanan user.',
            'notes' => [
                'Kolom access_targets dipakai sesuai access_type: email user untuk hierarchical/peer, branch code untuk branch.',
                'Import akan replace konfigurasi access-control untuk user yang ada di file.',
            ],
            'headers' => [
                'user_email',
                'user_username',
                'user_name',
                'multi_login',
                'is_frozen',
                'screenshot_allowed',
                'access_type',
                'access_targets',
                'access_is_active',
                'login_start_time',
                'login_end_time',
                'allowed_days',
                'idle_timeout',
                'restriction_is_active',
            ],
            'required_headers' => ['user_email'],
            'record_count' => $levels + $restrictions,
            'record_summary' => "{$levels} access level / {$restrictions} restriction",
        ];
    }

    protected function buildLocationModule(): array
    {
        $provinceCount = Province::count();
        $cityCount = City::count();
        $districtCount = District::count();
        $subdistrictCount = Subdistrict::count();

        return [
            'key' => 'master-location',
            'label' => 'Master Location',
            'route_key' => 'provinces',
            'description' => 'Backup master lokasi lengkap dari province, city, district, sampai subdistrict/postal code.',
            'notes' => [
                'Satu baris CSV mewakili satu jalur lokasi province -> city -> district -> subdistrict.',
                'Delete module akan soft delete hierarchy lokasi dalam urutan anak ke parent.',
            ],
            'headers' => [
                'province_name',
                'province_code',
                'country',
                'province_description',
                'city_name',
                'city_type',
                'district_name',
                'subdistrict_name',
                'postal_code',
            ],
            'required_headers' => ['province_name', 'province_code', 'country'],
            'record_count' => $provinceCount + $cityCount + $districtCount + $subdistrictCount,
            'record_summary' => "{$provinceCount} prov / {$cityCount} city / {$districtCount} district / {$subdistrictCount} subdistrict",
        ];
    }

    protected function buildAuditTrailModule(): array
    {
        $count = AuditLog::count();

        return [
            'key' => 'audit-trails',
            'label' => 'Audit Trails',
            'route_key' => 'audit-trails',
            'description' => 'Backup log audit trail utama, termasuk action, model, payload perubahan, dan metadata request.',
            'notes' => [
                'Field JSON seperti old_values_json dan new_values_json akan diimport ulang apa adanya.',
                'Delete module akan mengosongkan audit log, bukan login history.',
            ],
            'headers' => [
                'user_email',
                'action',
                'model_type',
                'model_id',
                'page_name',
                'module_name',
                'ip_address',
                'user_agent',
                'old_values_json',
                'new_values_json',
                'changed_fields_json',
                'created_at',
            ],
            'required_headers' => ['action'],
            'record_count' => $count,
            'record_summary' => $count . ' audit log',
        ];
    }

    protected function exportDepartmentRows(): array
    {
        return Department::with('subDepartment')
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department) => [
                'department_name' => $department->name,
                'parent_department_name' => $department->subDepartment?->name,
                'description' => $department->description,
                'system_reserved' => $this->boolToString($department->system_reserved),
                'is_active' => $this->boolToString($department->is_active),
            ])
            ->all();
    }

    protected function exportUserRows(): array
    {
        return User::with(['branch:id,code,name', 'roles:id,name', 'assignedBranches:id,code,name'])
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $primaryBranch = $user->assignedBranches->firstWhere('pivot.is_primary', true) ?: $user->branch;
                $roleNames = $user->roles->pluck('name')->filter()->values();

                return [
                    'nik' => $user->nik,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'password' => '',
                    'password_hash' => $user->getRawOriginal('password'),
                    'department_name' => $user->department_name,
                    'branch_code' => $user->branch?->code,
                    'branch_name' => $user->branch_name ?: $user->branch?->name,
                    'primary_branch_code' => $primaryBranch?->code,
                    'assigned_branch_codes' => $user->assignedBranches->pluck('code')->filter()->implode('|'),
                    'position_name' => $user->position_name,
                    'salutation' => $user->salutation,
                    'phone' => $user->phone,
                    'handphone_1' => $user->handphone_1,
                    'handphone_2' => $user->handphone_2,
                    'join_date' => $user->getRawOriginal('join_date'),
                    'employee_status' => $user->employee_status,
                    'gender' => $user->gender,
                    'marital_status' => $user->marital_status,
                    'religion' => $user->religion,
                    'identity_type' => $user->identity_type,
                    'identity_number' => $user->identity_number,
                    'npwp_number' => $user->npwp_number,
                    'bpjs_number' => $user->bpjs_number,
                    'bpjs_date' => $user->getRawOriginal('bpjs_date'),
                    'blood_type' => $user->blood_type,
                    'rhesus' => $user->rhesus,
                    'address_1' => $user->address_1,
                    'address_2' => $user->address_2,
                    'emergency_contact_name' => $user->emergency_contact_name,
                    'emergency_contact_number' => $user->emergency_contact_number,
                    'bank_name' => $user->bank_name,
                    'bank_account_number' => $user->bank_account_number,
                    'bank_account_holder' => $user->bank_account_holder,
                    'is_active' => $this->boolToString($user->is_active),
                    'multi_login' => $this->boolToString($user->multi_login),
                    'is_frozen' => $this->boolToString($user->is_frozen),
                    'screenshot_allowed' => $this->boolToString($user->screenshot_allowed),
                    'is_commission_achiever' => $this->boolToString($user->is_commission_achiever),
                    'roles' => $user->getRolesColumnValue(),
                    'role_names' => $roleNames->implode('|'),
                ];
            })
            ->all();
    }

    protected function exportRoleRows(): array
    {
        return Role::with('rolePermissions.permission:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'role_name' => $role->name,
                'description' => $role->description,
                'permission_names' => $role->rolePermissions
                    ->pluck('permission.name')
                    ->filter()
                    ->implode('|'),
                'system_reserved' => $this->boolToString($role->system_reserved),
                'is_active' => $this->boolToString($role->is_active),
            ])
            ->all();
    }

    protected function exportAccessControlRows(): array
    {
        return User::with(['accessLevels', 'loginRestrictions'])
            ->orderBy('name')
            ->get()
            ->flatMap(function (User $user) {
                $restriction = $user->loginRestrictions->first();
                $rows = [];
                $accessLevels = $user->accessLevels;

                if ($accessLevels->isEmpty()) {
                    $rows[] = $this->buildAccessControlRow($user, null, $restriction);
                } else {
                    foreach ($accessLevels as $accessLevel) {
                        $rows[] = $this->buildAccessControlRow($user, $accessLevel, $restriction);
                    }
                }

                return $rows;
            })
            ->all();
    }

    protected function exportLocationRows(): array
    {
        $provinces = Province::with(['cities.districts.subdistricts'])
            ->orderBy('country')
            ->orderBy('name')
            ->get();

        $rows = [];

        foreach ($provinces as $province) {
            if ($province->cities->isEmpty()) {
                $rows[] = $this->buildLocationRow($province);
                continue;
            }

            foreach ($province->cities as $city) {
                if ($city->districts->isEmpty()) {
                    $rows[] = $this->buildLocationRow($province, $city);
                    continue;
                }

                foreach ($city->districts as $district) {
                    if ($district->subdistricts->isEmpty()) {
                        $rows[] = $this->buildLocationRow($province, $city, $district);
                        continue;
                    }

                    foreach ($district->subdistricts as $subdistrict) {
                        $rows[] = $this->buildLocationRow($province, $city, $district, $subdistrict);
                    }
                }
            }
        }

        return $rows;
    }

    protected function exportAuditTrailRows(): iterable
    {
        return AuditLog::with('user:id,email')
            ->orderBy('id')
            ->lazyById(500, 'id')
            ->map(fn (AuditLog $log) => [
                'user_email' => $log->user?->email,
                'action' => $log->action,
                'model_type' => $log->model_type,
                'model_id' => $log->model_id,
                'page_name' => $log->page_name,
                'module_name' => $log->module_name,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'old_values_json' => $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE) : null,
                'new_values_json' => $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : null,
                'changed_fields_json' => $log->changed_fields ? json_encode($log->changed_fields, JSON_UNESCAPED_UNICODE) : null,
                'created_at' => optional($log->created_at)->format('Y-m-d H:i:s'),
            ]);
    }

    protected function importDepartments(array $rows, User $actor): array
    {
        $stats = $this->blankStats();
        $pendingParents = [];

        foreach ($rows as $row) {
            $name = $this->cleanString($row['department_name'] ?? null);

            if ($name === null) {
                $stats['skipped']++;
                continue;
            }

            $department = Department::withTrashed()->firstOrNew(['name' => $name]);
            $isNew = !$department->exists;
            $wasTrashed = $department->exists && method_exists($department, 'trashed') && $department->trashed();

            if ($wasTrashed) {
                $department->restore();
                $stats['restored']++;
            }

            $department->fill([
                'description' => $this->cleanString($row['description'] ?? null),
                'system_reserved' => $this->parseBool($row['system_reserved'] ?? false),
                'is_active' => $this->parseBool($row['is_active'] ?? true, true),
                'updated_by' => $actor->id,
            ]);

            if ($isNew) {
                $department->created_by = $actor->id;
            }

            $department->save();
            $pendingParents[$department->id] = $this->cleanString($row['parent_department_name'] ?? null);
            $stats[$isNew ? 'created' : 'updated']++;
        }

        foreach ($pendingParents as $departmentId => $parentName) {
            $department = Department::find($departmentId);

            if (!$department) {
                continue;
            }

            if ($parentName === null) {
                $department->update(['sub_department' => null]);
                continue;
            }

            $parent = Department::where('name', $parentName)->first();

            if (!$parent) {
                $stats['warnings'][] = "Parent department '{$parentName}' tidak ditemukan untuk department '{$department->name}'.";
                continue;
            }

            if ($parent->id === $department->id) {
                $stats['warnings'][] = "Department '{$department->name}' tidak boleh menjadi parent dirinya sendiri.";
                continue;
            }

            $department->update(['sub_department' => $parent->id]);
        }

        Cache::forget('department:index:active');

        return $stats;
    }

    protected function importUsers(array $rows, User $actor): array
    {
        $stats = $this->blankStats();

        foreach ($rows as $row) {
            $identifier = $this->resolveUserIdentifier($row);

            if (!$identifier) {
                $stats['skipped']++;
                continue;
            }

            $user = User::withTrashed()
                ->where($identifier['column'], $identifier['value'])
                ->first();

            $isNew = !$user;
            $wasTrashed = $user?->trashed() ?? false;
            $roleNames = $this->parseDelimitedValues($row['role_names'] ?? null);

            if (!$user) {
                $user = new User();
                $user->{$identifier['column']} = $identifier['value'];
                $user->created_by = $actor->id;
            } elseif ($wasTrashed) {
                $user->restore();
                $stats['restored']++;
            }

            $departmentName = $this->cleanString($row['department_name'] ?? null);
            $department = null;

            if ($departmentName) {
                $department = Department::withTrashed()->firstOrCreate(
                    ['name' => $departmentName],
                    [
                        'description' => 'Auto-created from Backup Import',
                        'system_reserved' => false,
                        'is_active' => true,
                        'created_by' => $actor->id,
                        'updated_by' => $actor->id,
                    ]
                );

                if ($department->trashed()) {
                    $department->restore();
                }
            }

            $primaryBranch = $this->resolveBranch(
                $row['primary_branch_code'] ?? null,
                $row['branch_name'] ?? null,
                $row['branch_code'] ?? null
            );

            $passwordValue = $this->cleanString($row['password_hash'] ?? null)
                ?: $this->cleanString($row['password'] ?? null);

            if ($isNew && !$passwordValue) {
                $stats['warnings'][] = "User '{$identifier['value']}' dilewati karena password/password_hash kosong.";
                $stats['skipped']++;
                continue;
            }

            $user->fill([
                'nik' => $this->cleanString($row['nik'] ?? null),
                'name' => $this->cleanString($row['name'] ?? null),
                'email' => $this->cleanString($row['email'] ?? null),
                'username' => $this->cleanString($row['username'] ?? null),
                'department_id' => $department?->id,
                'department_name' => $departmentName,
                'branch_id' => $primaryBranch?->id,
                'branch_name' => $primaryBranch?->name ?: $this->cleanString($row['branch_name'] ?? null),
                'position_name' => $this->cleanString($row['position_name'] ?? null),
                'salutation' => $this->cleanString($row['salutation'] ?? null),
                'phone' => $this->cleanString($row['phone'] ?? null),
                'handphone_1' => $this->cleanString($row['handphone_1'] ?? null),
                'handphone_2' => $this->cleanString($row['handphone_2'] ?? null),
                'join_date' => $this->cleanString($row['join_date'] ?? null),
                'employee_status' => $this->cleanString($row['employee_status'] ?? null),
                'gender' => $this->cleanString($row['gender'] ?? null),
                'marital_status' => $this->cleanString($row['marital_status'] ?? null),
                'religion' => $this->cleanString($row['religion'] ?? null),
                'identity_type' => $this->cleanString($row['identity_type'] ?? null),
                'identity_number' => $this->cleanString($row['identity_number'] ?? null),
                'npwp_number' => $this->cleanString($row['npwp_number'] ?? null),
                'bpjs_number' => $this->cleanString($row['bpjs_number'] ?? null),
                'bpjs_date' => $this->cleanString($row['bpjs_date'] ?? null),
                'blood_type' => $this->cleanString($row['blood_type'] ?? null),
                'rhesus' => $this->cleanString($row['rhesus'] ?? null),
                'address_1' => $this->cleanString($row['address_1'] ?? null),
                'address_2' => $this->cleanString($row['address_2'] ?? null),
                'emergency_contact_name' => $this->cleanString($row['emergency_contact_name'] ?? null),
                'emergency_contact_number' => $this->cleanString($row['emergency_contact_number'] ?? null),
                'bank_name' => $this->cleanString($row['bank_name'] ?? null),
                'bank_account_number' => $this->cleanString($row['bank_account_number'] ?? null),
                'bank_account_holder' => $this->cleanString($row['bank_account_holder'] ?? null),
                'is_active' => $this->parseBool($row['is_active'] ?? true, true),
                'multi_login' => $this->parseBool($row['multi_login'] ?? false),
                'is_frozen' => $this->parseBool($row['is_frozen'] ?? false),
                'screenshot_allowed' => $this->parseBool($row['screenshot_allowed'] ?? false),
                'is_commission_achiever' => $this->parseBool($row['is_commission_achiever'] ?? false),
                'roles' => $this->cleanString($row['roles'] ?? implode(', ', $roleNames)),
                'updated_by' => $actor->id,
            ]);

            if ($passwordValue) {
                $user->password = $passwordValue;
            }

            $user->save();
            $this->syncUserBranches($user, $primaryBranch?->id, $row['assigned_branch_codes'] ?? null, $actor->id);
            $this->syncUserRoles($user, $roleNames, $stats);
            $stats[$isNew ? 'created' : 'updated']++;
        }

        Cache::forget('user:index:departments');
        Cache::forget('user:index:branches');
        Cache::forget('user:index:roles');

        return $stats;
    }

    protected function importRoles(array $rows, User $actor): array
    {
        $stats = $this->blankStats();

        foreach ($rows as $row) {
            $name = $this->cleanString($row['role_name'] ?? null);

            if ($name === null) {
                $stats['skipped']++;
                continue;
            }

            $role = Role::withTrashed()->firstOrNew(['name' => $name]);
            $isNew = !$role->exists;
            $wasTrashed = $role->exists && $role->trashed();

            if ($wasTrashed) {
                $role->restore();
                $stats['restored']++;
            }

            $role->fill([
                'description' => $this->cleanString($row['description'] ?? null),
                'system_reserved' => $this->parseBool($row['system_reserved'] ?? false),
                'is_active' => $this->parseBool($row['is_active'] ?? true, true),
                'updated_by' => $actor->id,
            ]);

            if ($isNew) {
                $role->created_by = $actor->id;
            }

            $role->save();

            $permissionNames = $this->parseDelimitedValues($row['permission_names'] ?? null);
            $permissions = Permission::whereIn('name', $permissionNames)->pluck('id', 'name');
            $missingPermissions = array_diff($permissionNames, $permissions->keys()->all());

            if (!empty($missingPermissions)) {
                $stats['warnings'][] = "Permission tidak ditemukan untuk role '{$name}': " . implode(', ', $missingPermissions);
            }

            $role->rolePermissions()->delete();

            foreach ($permissions->values() as $permissionId) {
                $role->rolePermissions()->create(['permission_id' => $permissionId]);
            }

            $stats[$isNew ? 'created' : 'updated']++;
        }

        Cache::forget('role:index:permissions');

        return $stats;
    }

    protected function importAccessControl(array $rows, User $actor): array
    {
        $stats = $this->blankStats();
        $groupedRows = collect($rows)
            ->groupBy(function (array $row) {
                return $this->cleanString($row['user_email'] ?? null)
                    ?: $this->cleanString($row['user_username'] ?? null)
                    ?: '__missing__';
            });

        foreach ($groupedRows as $identifier => $userRows) {
            if ($identifier === '__missing__') {
                $stats['skipped'] += $userRows->count();
                continue;
            }

            $firstRow = $userRows->first();
            $user = User::where('email', $this->cleanString($firstRow['user_email'] ?? null))
                ->orWhere('username', $this->cleanString($firstRow['user_username'] ?? null))
                ->first();

            if (!$user) {
                $stats['warnings'][] = "User access-control '{$identifier}' tidak ditemukan.";
                $stats['skipped'] += $userRows->count();
                continue;
            }

            $user->update([
                'multi_login' => $this->parseBool($firstRow['multi_login'] ?? false),
                'is_frozen' => $this->parseBool($firstRow['is_frozen'] ?? false),
                'screenshot_allowed' => $this->parseBool($firstRow['screenshot_allowed'] ?? false),
                'updated_by' => $actor->id,
            ]);

            $user->accessLevels()->delete();
            $user->loginRestrictions()->delete();

            foreach ($userRows as $row) {
                $accessType = $this->cleanString($row['access_type'] ?? null);

                if ($accessType) {
                    UserAccessLevel::create([
                        'user_id' => $user->id,
                        'access_type' => $accessType,
                        'access_config' => $this->buildAccessConfig($accessType, $this->parseDelimitedValues($row['access_targets'] ?? null), $stats, $user->email),
                        'is_active' => $this->parseBool($row['access_is_active'] ?? true, true),
                    ]);
                }
            }

            $restrictionRow = $userRows->first(function (array $row) {
                return $this->cleanString($row['login_start_time'] ?? null)
                    || $this->cleanString($row['login_end_time'] ?? null)
                    || $this->cleanString($row['allowed_days'] ?? null)
                    || $this->cleanString($row['idle_timeout'] ?? null);
            });

            if ($restrictionRow) {
                UserLoginRestriction::create([
                    'user_id' => $user->id,
                    'start_time' => $this->cleanString($restrictionRow['login_start_time'] ?? null),
                    'end_time' => $this->cleanString($restrictionRow['login_end_time'] ?? null),
                    'allowed_days' => $this->parseDelimitedValues($restrictionRow['allowed_days'] ?? null, true),
                    'idle_timeout' => $this->parseInteger($restrictionRow['idle_timeout'] ?? null),
                    'is_active' => $this->parseBool($restrictionRow['restriction_is_active'] ?? true, true),
                ]);
            }

            $stats['updated']++;
        }

        Cache::forget('access-control:all-users');
        Cache::forget('access-control:all-branches');

        return $stats;
    }

    protected function importLocations(array $rows, User $actor): array
    {
        $stats = $this->blankStats();

        foreach ($rows as $row) {
            $provinceName = $this->cleanString($row['province_name'] ?? null);
            $provinceCode = $this->cleanString($row['province_code'] ?? null);
            $country = $this->cleanString($row['country'] ?? null);

            if (!$provinceName || !$provinceCode || !$country) {
                $stats['skipped']++;
                continue;
            }

            $province = Province::withTrashed()->firstOrNew(['code' => strtoupper($provinceCode)]);
            $provinceWasNew = !$province->exists;
            $provinceWasTrashed = $province->exists && $province->trashed();

            if ($provinceWasTrashed) {
                $province->restore();
                $stats['restored']++;
            }

            $province->fill([
                'name' => $provinceName,
                'country' => $country,
                'description' => $this->cleanString($row['province_description'] ?? null),
                'updated_by' => $actor->id,
            ]);

            if ($provinceWasNew) {
                $province->created_by = $actor->id;
            }

            $province->save();
            $this->syncLocationChildren($province, $row, $stats);
            $stats[$provinceWasNew ? 'created' : 'updated']++;
        }

        $this->forgetProvinceLookupCache();

        return $stats;
    }

    protected function importAuditTrails(array $rows, User $actor): array
    {
        $stats = $this->blankStats();

        foreach ($rows as $row) {
            if ($this->cleanString($row['action'] ?? null) === null) {
                $stats['skipped']++;
                continue;
            }

            $userId = null;

            if ($email = $this->cleanString($row['user_email'] ?? null)) {
                $userId = User::where('email', $email)->value('id');

                if (!$userId) {
                    $stats['warnings'][] = "User email '{$email}' untuk audit trail tidak ditemukan. Log tetap diimport tanpa user_id.";
                }
            }

            AuditLog::create([
                'user_id' => $userId,
                'action' => $this->cleanString($row['action'] ?? null),
                'model_type' => $this->cleanString($row['model_type'] ?? null),
                'model_id' => $this->parseInteger($row['model_id'] ?? null),
                'page_name' => $this->cleanString($row['page_name'] ?? null),
                'module_name' => $this->cleanString($row['module_name'] ?? null),
                'ip_address' => $this->cleanString($row['ip_address'] ?? null),
                'user_agent' => $this->cleanString($row['user_agent'] ?? null),
                'old_values' => $this->decodeJsonColumn($row['old_values_json'] ?? null),
                'new_values' => $this->decodeJsonColumn($row['new_values_json'] ?? null),
                'changed_fields' => $this->decodeJsonColumn($row['changed_fields_json'] ?? null),
                'created_at' => $this->cleanString($row['created_at'] ?? null) ?: now(),
                'updated_at' => $this->cleanString($row['created_at'] ?? null) ?: now(),
            ]);

            $stats['created']++;
        }

        return $stats;
    }

    protected function deleteDepartments(): array
    {
        $count = Department::where('system_reserved', false)->delete();
        Cache::forget('department:index:active');

        return $this->deleteStats($count, ['System reserved department tidak ikut dihapus.']);
    }

    protected function deleteUsers(User $actor): array
    {
        $count = User::where('id', '!=', $actor->id)->delete();

        return $this->deleteStats($count, ['User yang sedang login tidak ikut dihapus demi menjaga akses.']);
    }

    protected function deleteRoles(User $actor): array
    {
        $actorRoleIds = $actor->roles()->pluck('roles.id')->all();
        $roles = Role::where('system_reserved', false)
            ->whereNotIn('id', $actorRoleIds)
            ->get();

        $deleted = 0;

        foreach ($roles as $role) {
            $role->users()->detach();
            $role->rolePermissions()->delete();
            $role->delete();
            $deleted++;
        }

        Cache::forget('role:index:permissions');

        return $this->deleteStats($deleted, ['Role system_reserved dan role yang sedang dipakai user login tidak ikut dihapus.']);
    }

    protected function deleteAccessControl(): array
    {
        $levels = UserAccessLevel::query()->delete();
        $restrictions = UserLoginRestriction::query()->delete();

        User::query()->update([
            'multi_login' => false,
            'is_frozen' => false,
            'screenshot_allowed' => false,
            'updated_at' => now(),
        ]);

        Cache::forget('access-control:all-users');
        Cache::forget('access-control:all-branches');

        return $this->deleteStats($levels + $restrictions, ['Flag multi_login, is_frozen, dan screenshot_allowed direset ke default false.']);
    }

    protected function deleteLocations(): array
    {
        $deleted = 0;
        $deleted += Subdistrict::query()->delete();
        $deleted += District::query()->delete();
        $deleted += City::query()->delete();
        $deleted += Province::query()->delete();

        $this->forgetProvinceLookupCache();

        return $this->deleteStats($deleted);
    }

    protected function deleteAuditTrails(): array
    {
        $count = AuditLog::query()->delete();

        return $this->deleteStats($count, ['Login history tidak ikut dihapus dari modul ini.']);
    }

    protected function buildAccessControlRow(User $user, ?UserAccessLevel $accessLevel, ?UserLoginRestriction $restriction): array
    {
        $targets = '';

        if ($accessLevel) {
            $config = $accessLevel->access_config ?? [];

            if ($accessLevel->access_type === 'branch') {
                $targets = Branch::whereIn('id', $config['allowed_branches'] ?? [])
                    ->pluck('code')
                    ->filter()
                    ->implode('|');
            } else {
                $userIds = $accessLevel->access_type === 'hierarchical'
                    ? ($config['subordinates'] ?? [])
                    : ($config['peer_users'] ?? []);

                $targets = User::whereIn('id', $userIds)
                    ->pluck('email')
                    ->filter()
                    ->implode('|');
            }
        }

        return [
            'user_email' => $user->email,
            'user_username' => $user->username,
            'user_name' => $user->name,
            'multi_login' => $this->boolToString($user->multi_login),
            'is_frozen' => $this->boolToString($user->is_frozen),
            'screenshot_allowed' => $this->boolToString($user->screenshot_allowed),
            'access_type' => $accessLevel?->access_type,
            'access_targets' => $targets,
            'access_is_active' => $this->boolToString($accessLevel?->is_active ?? true),
            'login_start_time' => $restriction?->start_time,
            'login_end_time' => $restriction?->end_time,
            'allowed_days' => collect($restriction?->allowed_days ?? [])->implode('|'),
            'idle_timeout' => $restriction?->idle_timeout,
            'restriction_is_active' => $this->boolToString($restriction?->is_active ?? true),
        ];
    }

    protected function buildLocationRow(
        Province $province,
        ?City $city = null,
        ?District $district = null,
        ?Subdistrict $subdistrict = null
    ): array {
        return [
            'province_name' => $province->name,
            'province_code' => $province->code,
            'country' => $province->country,
            'province_description' => $province->description,
            'city_name' => $city?->name,
            'city_type' => $city?->type,
            'district_name' => $district?->name,
            'subdistrict_name' => $subdistrict?->name,
            'postal_code' => $subdistrict?->postal_code,
        ];
    }

    protected function buildAccessConfig(string $accessType, array $targets, array &$stats, string $userEmail): array
    {
        return match ($accessType) {
            'hierarchical' => [
                'subordinates' => User::whereIn('email', $targets)->pluck('id')->values()->all(),
            ],
            'peer' => [
                'peer_users' => User::whereIn('email', $targets)->pluck('id')->values()->all(),
            ],
            'branch' => [
                'allowed_branches' => Branch::whereIn('code', $targets)->pluck('id')->values()->all(),
            ],
            'company', 'none' => [],
            default => $this->warnUnsupportedAccessType($accessType, $stats, $userEmail),
        };
    }

    protected function warnUnsupportedAccessType(string $accessType, array &$stats, string $userEmail): array
    {
        $stats['warnings'][] = "Access type '{$accessType}' untuk '{$userEmail}' tidak dikenali, config dikosongkan.";

        return [];
    }

    protected function resolveBranch(?string $primaryBranchCode, ?string $branchName, ?string $branchCode): ?Branch
    {
        $candidates = array_filter([
            $this->cleanString($primaryBranchCode),
            $this->cleanString($branchCode),
        ]);

        foreach ($candidates as $code) {
            $branch = Branch::where('code', $code)->first();

            if ($branch) {
                return $branch;
            }
        }

        if ($name = $this->cleanString($branchName)) {
            return Branch::where('name', $name)->first();
        }

        return null;
    }

    protected function resolveUserIdentifier(array $row): ?array
    {
        foreach (['email', 'username', 'nik'] as $column) {
            $value = $this->cleanString($row[$column] ?? null);

            if ($value !== null) {
                return ['column' => $column, 'value' => $value];
            }
        }

        return null;
    }

    protected function syncUserBranches(User $user, ?int $primaryBranchId, mixed $assignedBranchCodesValue, int $actorId): void
    {
        $assignedBranchCodes = $this->parseDelimitedValues($assignedBranchCodesValue);
        $branchIds = collect($assignedBranchCodes)
            ->map(fn (string $code) => Branch::where('code', $code)->value('id'))
            ->filter()
            ->values();

        if ($primaryBranchId && !$branchIds->contains($primaryBranchId)) {
            $branchIds->prepend($primaryBranchId);
        }

        if ($branchIds->isEmpty()) {
            return;
        }

        $syncBranches = [];

        foreach ($branchIds as $branchId) {
            $syncBranches[$branchId] = [
                'is_primary' => $primaryBranchId === $branchId,
                'updated_by' => $actorId,
                'created_by' => $actorId,
            ];
        }

        $user->assignedBranches()->sync($syncBranches);
    }

    protected function syncUserRoles(User $user, array $roleNames, array &$stats): void
    {
        if (empty($roleNames)) {
            return;
        }

        $resolvedRoles = Role::whereIn('name', $roleNames)->pluck('id', 'name');
        $missingRoles = array_diff($roleNames, $resolvedRoles->keys()->all());

        if (!empty($missingRoles)) {
            $stats['warnings'][] = "Role tidak ditemukan untuk user '{$user->email}': " . implode(', ', $missingRoles);
        }

        $user->roles()->sync($resolvedRoles->values()->all());
    }

    protected function syncLocationChildren(Province $province, array $row, array &$stats): void
    {
        $city = null;
        $district = null;

        if ($cityName = $this->cleanString($row['city_name'] ?? null)) {
            $city = City::withTrashed()->firstOrNew([
                'province_id' => $province->id,
                'name' => $cityName,
            ]);

            if ($city->exists && $city->trashed()) {
                $city->restore();
                $stats['restored']++;
            }

            $city->fill(['type' => $this->cleanString($row['city_type'] ?? null)]);
            $city->save();
        }

        if ($city && ($districtName = $this->cleanString($row['district_name'] ?? null))) {
            $district = District::withTrashed()->firstOrNew([
                'city_id' => $city->id,
                'name' => $districtName,
            ]);

            if ($district->exists && $district->trashed()) {
                $district->restore();
                $stats['restored']++;
            }

            $district->save();
        }

        if ($district && ($subdistrictName = $this->cleanString($row['subdistrict_name'] ?? null))) {
            $subdistrict = Subdistrict::withTrashed()->firstOrNew([
                'district_id' => $district->id,
                'name' => $subdistrictName,
            ]);

            if ($subdistrict->exists && $subdistrict->trashed()) {
                $subdistrict->restore();
                $stats['restored']++;
            }

            $subdistrict->fill([
                'postal_code' => $this->cleanString($row['postal_code'] ?? null),
            ]);
            $subdistrict->save();
        }
    }

    protected function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new \RuntimeException('File CSV tidak bisa dibuka.');
        }

        $headerRow = fgetcsv($handle, 0, ',', '"', '');

        if ($headerRow === false) {
            fclose($handle);
            throw new \RuntimeException('File CSV kosong atau tidak valid.');
        }

        $headers = collect($headerRow)
            ->map(fn ($value) => $this->normalizeHeader($value))
            ->all();

        $rows = [];

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $normalizedRow = array_pad($row, count($headers), null);
            $rows[] = array_combine($headers, $normalizedRow);
        }

        fclose($handle);

        return [$headers, $rows];
    }

    protected function renderCsvContent(array $headers, iterable $rows): string
    {
        $file = fopen('php://temp', 'w+');
        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($file, $headers, ',', '"', '');

        foreach ($rows as $row) {
            $ordered = [];

            foreach ($headers as $header) {
                $ordered[] = $row[$header] ?? null;
            }

            fputcsv($file, $ordered, ',', '"', '');
        }

        rewind($file);
        $contents = stream_get_contents($file) ?: '';
        fclose($file);

        return $contents;
    }

    protected function buildModuleZip(array $modules, string $mode): string
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi ZipArchive tidak tersedia di server.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'backup_restore_');

        if ($zipPath === false) {
            throw new \RuntimeException('File ZIP sementara tidak bisa dibuat.');
        }

        $zip = new \ZipArchive();
        $opened = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($opened !== true) {
            @unlink($zipPath);
            throw new \RuntimeException('ZIP backup tidak bisa dibuat.');
        }

        foreach ($modules as $module) {
            $headers = $module['headers'];
            $rows = $mode === 'template' ? [] : $this->exportRows($module['key']);
            $contents = $this->renderCsvContent($headers, $rows);
            $suffix = $mode === 'template' ? 'template' : 'export';
            $zip->addFromString($module['key'] . '_' . $suffix . '.csv', $contents);
        }

        $zip->close();

        return $zipPath;
    }

    protected function detectModuleKeyFromFilename(string $basename, array $moduleKeys): ?string
    {
        $normalized = Str::lower(pathinfo($basename, PATHINFO_FILENAME));
        $sortedKeys = collect($moduleKeys)
            ->sortByDesc(fn ($key) => strlen($key))
            ->values()
            ->all();

        foreach ($sortedKeys as $moduleKey) {
            $key = Str::lower($moduleKey);

            if ($normalized === $key || Str::startsWith($normalized, $key . '_') || Str::startsWith($normalized, $key . '-')) {
                return $moduleKey;
            }
        }

        return null;
    }

    protected function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return Response::streamDownload(function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers, ',', '"', '');

            foreach ($rows as $row) {
                $ordered = [];

                foreach ($headers as $header) {
                    $ordered[] = $row[$header] ?? null;
                }

                fputcsv($file, $ordered, ',', '"', '');
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function blankStats(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'restored' => 0,
            'skipped' => 0,
            'warnings' => [],
        ];
    }

    protected function deleteStats(int $deleted, array $warnings = []): array
    {
        return [
            'deleted' => $deleted,
            'warnings' => $warnings,
        ];
    }

    protected function normalizeHeader(?string $header): string
    {
        $header = (string) $header;
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);

        return trim(Str::lower($header));
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->cleanString($value) !== null) {
                return false;
            }
        }

        return true;
    }

    protected function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function parseBool(mixed $value, bool $default = false): bool
    {
        $value = $this->cleanString($value);

        if ($value === null) {
            return $default;
        }

        $normalized = Str::lower($value);

        return in_array($normalized, ['1', 'true', 'yes', 'y', 'aktif', 'active'], true);
    }

    protected function boolToString(?bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    protected function parseDelimitedValues(mixed $value, bool $castInt = false): array
    {
        $value = $this->cleanString($value);

        if ($value === null) {
            return [];
        }

        $items = collect(explode('|', $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values();

        if ($castInt) {
            return $items->map(fn (string $item) => (int) $item)->all();
        }

        return $items->all();
    }

    protected function parseInteger(mixed $value): ?int
    {
        $value = $this->cleanString($value);

        return $value === null ? null : (int) $value;
    }

    protected function decodeJsonColumn(mixed $value): ?array
    {
        $value = $this->cleanString($value);

        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function forgetProvinceLookupCache(): void
    {
        foreach ([
            'province:index',
            'province:lookup',
            'customer:create:provinces',
            'branch:create:provinces',
        ] as $cacheKey) {
            Cache::forget($cacheKey);
        }
    }
}
