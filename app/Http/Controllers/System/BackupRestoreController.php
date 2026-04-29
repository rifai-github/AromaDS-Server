<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\System\BackupRestoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BackupRestoreController extends Controller
{
    public function __construct(protected BackupRestoreService $backupRestoreService)
    {
    }

    public function index()
    {
        $modules = $this->backupRestoreService->listModules();
        $moduleGroups = $this->groupModules($modules);

        return view('system.backup-restore.index', compact('modules', 'moduleGroups'));
    }

    public function downloadAllTemplates()
    {
        return $this->backupRestoreService->downloadAllTemplates();
    }

    public function exportAll()
    {
        return $this->backupRestoreService->exportAllData();
    }

    public function downloadTemplate(string $module)
    {
        return $this->backupRestoreService->downloadTemplate($module);
    }

    public function export(string $module)
    {
        return $this->backupRestoreService->exportData($module);
    }

    public function import(Request $request, string $module): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $result = $this->backupRestoreService->importData($module, $request->file('file'), $request->user());

            return back()->with('success', $this->buildImportMessage($result));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    public function importAll(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:51200',
        ]);

        try {
            $result = $this->backupRestoreService->importAllData($request->file('file'), $request->user());

            return back()->with('success', $this->buildBulkImportMessage($result));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import semua modul gagal: ' . $e->getMessage());
        }
    }

    public function destroy(string $module): RedirectResponse
    {
        try {
            $result = $this->backupRestoreService->deleteModuleData($module, request()->user());

            return back()->with('success', $this->buildDeleteMessage($result));
        } catch (\Throwable $e) {
            return back()->with('error', 'Hapus data gagal: ' . $e->getMessage());
        }
    }

    public function destroyAll(): RedirectResponse
    {
        try {
            $result = $this->backupRestoreService->deleteAllModuleData(request()->user());

            return back()->with('success', $this->buildBulkDeleteMessage($result));
        } catch (\Throwable $e) {
            return back()->with('error', 'Hapus semua modul gagal: ' . $e->getMessage());
        }
    }

    protected function buildImportMessage(array $result): string
    {
        $parts = [
            "Created: {$result['created']}",
            "Updated: {$result['updated']}",
            "Restored: {$result['restored']}",
            "Skipped: {$result['skipped']}",
        ];

        if (!empty($result['warnings'])) {
            $parts[] = 'Warning: ' . implode(' | ', $result['warnings']);
        }

        return implode(' | ', $parts);
    }

    protected function buildDeleteMessage(array $result): string
    {
        $message = "Deleted: {$result['deleted']}";

        if (!empty($result['warnings'])) {
            $message .= ' | Warning: ' . implode(' | ', $result['warnings']);
        }

        return $message;
    }

    protected function buildBulkImportMessage(array $result): string
    {
        $message = "Modules OK: {$result['processed_modules']} | Created: {$result['created']} | Updated: {$result['updated']} | Restored: {$result['restored']} | Skipped Rows: {$result['skipped_rows']}";

        if (!empty($result['failed_modules'])) {
            $message .= ' | Failed: ' . implode(', ', array_keys($result['failed_modules']));
        }

        if (!empty($result['warnings'])) {
            $message .= ' | Warning: ' . implode(' | ', array_slice($result['warnings'], 0, 5));
        }

        return $message;
    }

    protected function buildBulkDeleteMessage(array $result): string
    {
        $message = "Modules OK: {$result['processed_modules']} | Deleted: {$result['deleted']}";

        if (!empty($result['failed_modules'])) {
            $message .= ' | Failed: ' . implode(', ', array_keys($result['failed_modules']));
        }

        if (!empty($result['warnings'])) {
            $message .= ' | Warning: ' . implode(' | ', array_slice($result['warnings'], 0, 5));
        }

        return $message;
    }

    protected function groupModules(array $modules): array
    {
        $definitions = [
            [
                'key' => 'system',
                'label' => 'System',
                'description' => 'Data kontrol inti aplikasi: user, role, akses, lokasi, dan audit.',
                'rules' => [
                    'Import paling aman dimulai dari Department -> Roles -> Users -> Hirarki Data.',
                    'Audit trail sebaiknya diproses terakhir karena sifatnya histori dan volume datanya besar.',
                ],
                'module_keys' => ['departments', 'users', 'roles', 'access-control', 'master-location', 'audit-trails'],
            ],
            [
                'key' => 'marketing',
                'label' => 'Marketing',
                'description' => 'Data prospek sampai kontrak dan perubahan pasca kontrak.',
                'rules' => [
                    'Restore paling aman urutannya Customer/Building/Room -> Pipeline -> Survey -> Quotation -> Contract.',
                    'Modul turunan seperti Job Advice, Contract Switching, Aroma Change, dan Termination sebaiknya diimport setelah contract sudah ada.',
                ],
                'module_keys' => ['customers', 'customer-contacts', 'customer-taxes', 'customer-types', 'buildings', 'master-rooms', 'pipelines', 'surveys', 'quotations', 'contracts', 'job-advices', 'contract-assigned', 'contract-terminations', 'contract-switchings', 'aroma-changes'],
            ],
            [
                'key' => 'finance',
                'label' => 'Finance',
                'description' => 'Data invoice, follow up, bank, VA, dan rule pajak.',
                'rules' => [
                    'Pastikan contract/billing group sudah ada sebelum import invoice dan follow up.',
                    'Tax Setting dan Kode Pajak idealnya diimport lebih dulu sebelum invoice.',
                ],
                'module_keys' => ['tax-codes', 'tax-settings', 'master-banks', 'bank-payments', 'company-virtual-accounts', 'master-price-slabs', 'invoices', 'invoice-follow-ups', 'lost-unit-reports'],
            ],
            [
                'key' => 'operational',
                'label' => 'Operational',
                'description' => 'Data team, job schedule, dan material assign untuk operasional lapangan.',
                'rules' => [
                    'Job Schedule dan Material Assign saling terkait dengan contract, job advice, team, dan warehouse.',
                    'Untuk restore aman, pastikan Team dan master warehouse/product sudah tersedia lebih dulu.',
                ],
                'module_keys' => ['master-teams', 'job-schedules', 'material-assign'],
            ],
            [
                'key' => 'warehouse',
                'label' => 'Warehouse',
                'description' => 'Master produk/rental sampai transaksi request, issuing, receiving, stock, serial number, dan logistik.',
                'rules' => [
                    'Import paling aman dimulai dari master referensi: Product Structure/Type/Variant -> Master Product -> Master Rental -> Warehouse.',
                    'Transaksi Inventory Request/Issuing/Receiving, Stock Opname/Adjustment, dan Logistics sebaiknya diimport setelah master dan serial number siap.',
                ],
                'module_keys' => ['product-categories', 'product-types', 'brand-variants', 'rental-service-frequencies', 'master-products', 'master-rentals', 'warehouses', 'serial-numbers', 'unit-on-walls', 'inventory-requests', 'inventory-issuings', 'inventory-receivings', 'stock-opnames', 'stock-adjustments', 'logistics-trackings', 'berita-acara', 'purchasing-requests'],
            ],
            [
                'key' => 'company',
                'label' => 'Company',
                'description' => 'Referensi company dan branch yang dipakai lintas modul.',
                'rules' => [
                    'Company dan Branch idealnya tersedia sebelum restore users, warehouses, dan contract.',
                ],
                'module_keys' => ['companies', 'branches'],
            ],
            [
                'key' => 'other',
                'label' => 'Other',
                'description' => 'Referensi umum lintas modul.',
                'rules' => [
                    'Master Options aman diimport lebih awal karena dipakai banyak modul sebagai referensi dropdown.',
                ],
                'module_keys' => ['master-options'],
            ],
        ];

        $moduleMap = collect($modules)->keyBy('key');
        $usedKeys = [];
        $groups = [];

        foreach ($definitions as $definition) {
            $groupModules = collect($definition['module_keys'])
                ->filter(fn ($key) => $moduleMap->has($key))
                ->map(function ($key) use ($moduleMap, &$usedKeys) {
                    $usedKeys[] = $key;
                    return $moduleMap[$key];
                })
                ->values()
                ->all();

            if (empty($groupModules)) {
                continue;
            }

            $definition['modules'] = $groupModules;
            $definition['module_count'] = count($groupModules);
            $groups[] = $definition;
        }

        $remaining = collect($modules)
            ->reject(fn ($module) => in_array($module['key'], $usedKeys, true))
            ->values()
            ->all();

        if (!empty($remaining)) {
            $groups[] = [
                'key' => 'uncategorized',
                'label' => 'Lainnya',
                'description' => 'Modul tambahan yang belum masuk kategori utama.',
                'rules' => [
                    'Cek dependensi modulnya sebelum import massal.',
                ],
                'modules' => $remaining,
                'module_count' => count($remaining),
            ];
        }

        return $groups;
    }
}
