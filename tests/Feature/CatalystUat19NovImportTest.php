<?php

namespace Tests\Feature;

use App\Services\Imports\Catalyst\CatalystEmployeeProfileMapper;
use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use App\Services\Imports\Catalyst\CatalystSalutation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;
use Tests\TestCase;

/**
 * UAT 19 Nov 2026 (Database Mapping & Bug Hunting) - perubahan importer Catalyst:
 * filter FgActive, detail Master Option dari tabel Ms*, salutation Bapak/Ibu, pilihan akun
 * SAUsers, profil employee, kecamatan gedung, dan rekening VA.
 *
 * Sumber Catalyst (SQL Server) diganti koneksi SQLite test: tabel Ms* dibuat di DB yang sama.
 */
class CatalystUat19NovImportTest extends TestCase
{
    /** @var list<string> */
    private array $createdTables = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->createdTables) as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function create(string $table, callable $definition): void
    {
        Schema::create($table, $definition);
        $this->createdTables[] = $table;
    }

    private function createImportMaps(): void
    {
        $this->create('source_import_maps', function (Blueprint $table) {
            $table->id();
            $table->string('source_system');
            $table->string('source_table');
            $table->string('source_key');
            $table->string('target_table');
            $table->unsignedBigInteger('target_id');
            $table->string('source_hash')->nullable();
            $table->unsignedBigInteger('last_batch_id')->nullable();
            $table->timestamp('last_imported_at')->nullable();
            $table->timestamps();
        });
    }

    private function createMasterOptions(array $names): void
    {
        $this->create('master_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        $this->create('option_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_option_id');
            $table->string('option_name');
            $table->string('label')->nullable();
            $table->text('option_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        foreach ($names as $id => $name) {
            DB::table('master_options')->insert(['id' => $id, 'name' => $name]);
        }
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $optionalRows
     */
    private function importer(array $optionalRows = [], bool $apply = true): object
    {
        $importer = new class($optionalRows) extends CatalystMasterDataImporter
        {
            /** @var list<string> */
            public array $warnings = [];

            public function __construct(private array $optionalRows) {}

            // "Sumber Catalyst" = koneksi default (SQLite in-memory test).
            protected function source()
            {
                return DB::connection();
            }

            protected function optionalSourceRows(string $step, string $table): array
            {
                if (array_key_exists($table, $this->optionalRows)) {
                    return $this->optionalRows[$table];
                }

                return parent::optionalSourceRows($step, $table);
            }

            protected function log(string $step, string $level, string $message, array $context = []): void
            {
                $this->warnings[] = $message;
            }

            protected function heartbeat(string $step, array $stats, int $totalRows, bool $final = false): void {}

            protected function actorId(): ?int
            {
                return null;
            }

            public function call(string $method, ...$args)
            {
                return $this->{$method}(...$args);
            }
        };

        foreach (['apply' => $apply, 'heartbeatEvery' => 1000, 'batchId' => 1] as $name => $value) {
            $property = new ReflectionProperty(CatalystMasterDataImporter::class, $name);
            $property->setAccessible(true);
            $property->setValue($importer, $value);
        }

        return $importer;
    }

    // ── 1. FgActive ──────────────────────────────────────────────────────────

    public function test_master_steps_skip_rows_flagged_inactive_but_keep_null_flags(): void
    {
        $this->createImportMaps();
        $this->create('MsBank', function (Blueprint $table) {
            $table->string('BankCode');
            $table->string('BankName');
            $table->string('FgActive')->nullable();
        });
        $this->create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('MsBank')->insert([
            ['BankCode' => 'BC001', 'BankName' => 'BCA', 'FgActive' => 'Y'],
            ['BankCode' => 'BC002', 'BankName' => 'Bank Lama', 'FgActive' => 'N'],
            ['BankCode' => 'BC003', 'BankName' => 'Mandiri', 'FgActive' => null],
        ]);

        $result = $this->importer()->call('banks');

        $this->assertSame(2, $result['stats']['processed']);
        $this->assertSame(['BC001', 'BC003'], DB::table('banks')->orderBy('bank_code')->pluck('bank_code')->all());
    }

    public function test_active_filter_can_be_switched_off_and_never_applies_to_employees(): void
    {
        $this->assertNotContains('users', CatalystMasterDataImporter::ACTIVE_ONLY_STEPS);
        foreach (CatalystMasterDataImporter::DISABLED_STEPS as $disabled) {
            $this->assertNotContains($disabled, CatalystMasterDataImporter::ACTIVE_ONLY_STEPS);
        }

        $this->createImportMaps();
        $this->create('MsBank', function (Blueprint $table) {
            $table->string('BankCode');
            $table->string('BankName');
            $table->string('FgActive')->nullable();
        });
        $this->create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code')->nullable();
            $table->string('bank_name')->nullable();
            $table->boolean('is_active')->default(true);
        });
        DB::table('MsBank')->insert(['BankCode' => 'BC002', 'BankName' => 'Bank Lama', 'FgActive' => 'N']);

        config(['catalyst-import.active_only' => false]);
        $this->importer()->call('banks');

        $this->assertSame(0, (int) DB::table('banks')->where('bank_code', 'BC002')->value('is_active'));
    }

    // ── 2. Detail Master Option ──────────────────────────────────────────────

    public function test_master_option_details_upsert_by_name_without_duplicates_or_inactive_rows(): void
    {
        $this->createImportMaps();
        $this->createMasterOptions([1 => 'Position', 38 => 'Room Type', 46 => 'Product Units', 50 => 'rental_alias', 39 => 'Floor']);
        DB::table('option_details')->insert(['master_option_id' => 38, 'option_name' => 'Basement', 'is_active' => false]);

        $importer = $this->importer([
            'MsJobTitle' => [
                ['JobTtlCode' => 'J1', 'JobTtlName' => 'Technician', 'FgActive' => 'Y'],
                ['JobTtlCode' => 'J2', 'JobTtlName' => 'TECHNICIAN ', 'FgActive' => 'Y'],
                ['JobTtlCode' => 'J3', 'JobTtlName' => 'Old Title', 'FgActive' => 'N'],
            ],
            'MsRoom' => [
                ['RoomCode' => 'R1', 'RoomName' => 'basement'],
                ['RoomCode' => 'R2', 'RoomName' => 'Factory'],
            ],
            'MsUnit' => [['UnitCode' => 'PCS', 'UnitName' => 'Pieces']],
            'MsProductCategoryInfo' => [['CategoryCode' => 'C1', 'CategoryName' => 'Aroma DeliverySys Svc', 'FgActive' => 'Y']],
            'MsFloor' => [['FloorCode' => 'LT1']],
            'MsReasonTerminate' => [['ReasonCode' => 'X', 'ReasonName' => 'Pindah lokasi']],
        ]);

        $importer->call('master_option_details');

        $this->assertSame(['Technician'], DB::table('option_details')->where('master_option_id', 1)->pluck('option_name')->all());
        // Detail lama tidak diduplikasi dan status aktifnya tidak diubah.
        $this->assertSame(1, DB::table('option_details')->where('master_option_id', 38)->whereRaw('LOWER(option_name) = ?', ['basement'])->count());
        $this->assertSame(0, (int) DB::table('option_details')->where('master_option_id', 38)->where('option_name', 'Basement')->value('is_active'));
        $this->assertTrue(DB::table('option_details')->where('master_option_id', 38)->where('option_name', 'Factory')->exists());
        $this->assertTrue(DB::table('option_details')->where('master_option_id', 46)->where('option_name', 'Pieces')->exists());
        $this->assertTrue(DB::table('option_details')->where('master_option_id', 50)->where('option_name', 'Aroma DeliverySys Svc')->exists());
        // MsFloor tanpa FloorName jatuh ke kandidat FloorCode.
        $this->assertTrue(DB::table('option_details')->where('master_option_id', 39)->where('option_name', 'LT1')->exists());
        // Master option "Termination Reason" tidak ada di DB test: dilewati + dicatat, bukan dibuat.
        $this->assertFalse(DB::table('master_options')->where('name', 'Termination Reason')->exists());
        $this->assertNotEmpty(array_filter($importer->warnings, fn ($m) => str_contains($m, 'Termination Reason')));

        // Idempoten.
        $before = DB::table('option_details')->count();
        $this->importer(['MsJobTitle' => [['JobTtlName' => 'Technician']]])->call('master_option_details');
        $this->assertSame($before, DB::table('option_details')->count());
    }

    public function test_master_option_details_step_runs_before_users_and_is_not_disabled(): void
    {
        $steps = new ReflectionProperty(CatalystMasterDataImporter::class, 'steps');
        $steps->setAccessible(true);
        $order = $steps->getValue(app(CatalystMasterDataImporter::class));

        $this->assertContains('master_option_details', $order);
        $this->assertLessThan(array_search('users', $order, true), array_search('master_option_details', $order, true));
        $this->assertNotContains('master_option_details', CatalystMasterDataImporter::DISABLED_STEPS);
    }

    // ── 3. Salutation ────────────────────────────────────────────────────────

    public function test_salutation_maps_mr_to_bapak_and_mrs_miss_to_ibu(): void
    {
        foreach (['Mr', 'mr.', 'MR'] as $value) {
            $this->assertSame('Bapak', CatalystSalutation::normalize($value));
        }
        foreach (['Mrs', 'Mrs.', 'Miss', 'miss.', 'Ms.'] as $value) {
            $this->assertSame('Ibu', CatalystSalutation::normalize($value));
        }
        $this->assertSame('dr.', CatalystSalutation::normalize(' dr. '));
        $this->assertNull(CatalystSalutation::normalize(''));
    }

    public function test_normalize_contact_salutations_command_is_dry_run_by_default_and_idempotent(): void
    {
        $this->createMasterOptions([13 => 'Salutation']);
        DB::table('option_details')->insert(['master_option_id' => 13, 'option_name' => 'bapak']);
        $this->create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('salutation')->nullable();
            $table->timestamps();
        });
        DB::table('customer_contacts')->insert([
            ['name' => 'A', 'salutation' => 'Mr'],
            ['name' => 'B', 'salutation' => 'Mrs.'],
            ['name' => 'C', 'salutation' => 'Miss'],
            ['name' => 'D', 'salutation' => 'dr.'],
        ]);

        $this->artisan('catalyst:normalize-contact-salutations')->assertSuccessful();
        $this->assertSame('Mr', DB::table('customer_contacts')->where('name', 'A')->value('salutation'));
        $this->assertFalse(DB::table('option_details')->where('option_name', 'Ibu')->exists());

        $this->artisan('catalyst:normalize-contact-salutations', ['--apply' => true])->assertSuccessful();
        $this->assertSame(
            ['A' => 'Bapak', 'B' => 'Ibu', 'C' => 'Ibu', 'D' => 'dr.'],
            DB::table('customer_contacts')->orderBy('name')->pluck('salutation', 'name')->all()
        );
        // "bapak" sudah ada (beda huruf) -> tidak dobel; "Ibu" ditambahkan.
        $this->assertSame(1, DB::table('option_details')->whereRaw('LOWER(option_name) = ?', ['bapak'])->count());
        $this->assertTrue(DB::table('option_details')->where('master_option_id', 13)->where('option_name', 'Ibu')->exists());

        $this->artisan('catalyst:normalize-contact-salutations', ['--apply' => true])->assertSuccessful();
        $this->assertSame(1, DB::table('option_details')->where('option_name', 'Ibu')->count());
    }

    // ── 4. Employee / users ──────────────────────────────────────────────────

    public function test_sa_users_prefers_active_then_most_recent_account(): void
    {
        $picked = $this->importer()->call('pickPreferredSaUsers', [
            ['EmpNumb' => 'E1', 'UserId' => 'old', 'FgActive' => 'Y', 'CreateDate' => '2020-01-01'],
            ['EmpNumb' => 'E1', 'UserId' => 'new', 'FgActive' => 'Y', 'CreateDate' => '2024-01-01'],
            ['EmpNumb' => 'E1', 'UserId' => 'zzz', 'FgActive' => 'N', 'CreateDate' => '2025-06-01'],
            ['EmpNumb' => 'E2', 'UserId' => 'only-inactive', 'FgActive' => 'N', 'CreateDate' => null],
        ]);

        $this->assertSame('new', $picked['E1']['UserId']);
        $this->assertSame('only-inactive', $picked['E2']['UserId']);
    }

    public function test_employee_profile_is_mapped_to_master_option_names(): void
    {
        $mapper = new CatalystEmployeeProfileMapper([
            'gender' => [['option_name' => 'Male', 'option_description' => 'Laki-laki'], ['option_name' => 'Female', 'option_description' => 'Perempuan']],
            'marital_status' => [['option_name' => 'Single'], ['option_name' => 'Married']],
            'religion' => [['option_name' => 'Islam'], ['option_name' => 'Catholic', 'option_description' => 'Katolik']],
            'identity_type' => [['option_name' => 'KTP']],
            'blood_type' => [['option_name' => 'A'], ['option_name' => 'O']],
            'rhesus' => [['option_name' => 'Positive'], ['option_name' => 'Negative']],
            'employee_status' => [['option_name' => 'Permanent', 'option_description' => 'Tetap']],
        ]);

        $this->assertEquals([
            'gender' => 'Female',
            'marital_status' => 'Married',
            'religion' => 'Catholic',
            'blood_type' => 'O',
            'rhesus' => 'Negative',
            'employee_status' => 'Permanent',
        ], $mapper->map(
            ['sex' => 'P', 'MaritalStatus' => 'K/2', 'Religion' => 'KATHOLIK', 'BloodType' => 'O-', 'EmpStatus' => 'T1'],
            ['t1' => 'Karyawan Tetap']
        ));

        // Nilai yang tidak ada pilihannya tidak ditulis; kolom kosong diabaikan.
        $this->assertSame([], $mapper->map(['Religion' => 'Kepercayaan', 'Sex' => '', 'IDType' => 'KITAS']));
    }

    // ── 5. Kecamatan gedung ─────────────────────────────────────────────────

    public function test_building_district_matches_area_service_name_within_city_then_postal_code(): void
    {
        $this->create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('province_id')->nullable();
        });
        $this->create('districts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('city_id');
            $table->string('name');
            $table->softDeletes();
        });
        $this->create('subdistricts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('district_id');
            $table->string('name');
            $table->string('postal_code')->nullable();
        });
        DB::table('cities')->insert([['id' => 1, 'name' => 'Jakarta Utara', 'province_id' => 9], ['id' => 2, 'name' => 'Bandung', 'province_id' => 10]]);
        DB::table('districts')->insert([
            ['id' => 10, 'city_id' => 1, 'name' => 'PENJARINGAN'],
            ['id' => 11, 'city_id' => 1, 'name' => 'Kelapa Gading'],
            ['id' => 20, 'city_id' => 2, 'name' => 'Penjaringan'],
        ]);
        DB::table('subdistricts')->insert([
            ['id' => 100, 'district_id' => 10, 'name' => 'Pluit', 'postal_code' => '14450'],
            ['id' => 110, 'district_id' => 11, 'name' => 'Kelapa Gading Barat', 'postal_code' => '14240'],
        ]);

        $importer = $this->importer();

        $this->assertSame(['district_id' => 10, 'subdistrict_id' => 100], $importer->call('resolveBuildingDistrict', 'Kec. Penjaringan', 1, '14450'));
        $this->assertSame(['district_id' => 10, 'subdistrict_id' => null], $importer->call('resolveBuildingDistrict', 'kecamatan penjaringan', 1, null));
        // Nama tidak ketemu -> kode pos.
        $this->assertSame(['district_id' => 11, 'subdistrict_id' => 110], $importer->call('resolveBuildingDistrict', 'Area Lain', 1, '14240'));
        // Kode pos milik kota lain tidak dipakai.
        $this->assertSame(['district_id' => null, 'subdistrict_id' => null], $importer->call('resolveBuildingDistrict', 'Area Lain', 2, '14240'));
    }

    // ── 6. Rekening VA ───────────────────────────────────────────────────────

    public function test_va_prefers_the_specific_paytype_account_of_the_same_bank(): void
    {
        $this->createImportMaps();
        $this->create('bank_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id');
            $table->boolean('is_default_va')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
        DB::table('bank_payments')->insert([
            ['id' => 1, 'bank_id' => 17, 'is_default_va' => true],
            ['id' => 2, 'bank_id' => 17, 'is_default_va' => false],
            ['id' => 3, 'bank_id' => 99, 'is_default_va' => false],
        ]);
        DB::table('source_import_maps')->insert([
            ['source_system' => 'catalyst', 'source_table' => 'MsPayType', 'source_key' => 'BCA02', 'target_table' => 'bank_payments', 'target_id' => 2],
            ['source_system' => 'catalyst', 'source_table' => 'MsPayType', 'source_key' => 'MDR01', 'target_table' => 'bank_payments', 'target_id' => 3],
        ]);

        $importer = $this->importer();

        $this->assertSame(2, $importer->call('resolveBankPaymentIdForVirtualAccountRow', ['PayCode' => 'BCA02'], 17));
        // Rekening bank lain ditolak; tanpa referensi -> null (pemanggil fallback ke level bank).
        $this->assertNull($importer->call('resolveBankPaymentIdForVirtualAccountRow', ['PayCode' => 'MDR01'], 17));
        $this->assertNull($importer->call('resolveBankPaymentIdForVirtualAccountRow', [], 17));
    }

    public function test_bank_payment_import_relinks_vas_imported_before_their_bank_account_existed(): void
    {
        $this->createImportMaps();
        $this->create('bank_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id');
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('branch_name')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('bank_va_number')->nullable();
            $table->boolean('is_default_va')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        $this->create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('default_bank_payment_id')->nullable();
            $table->timestamps();
        });
        $this->create('company_virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_payment_id')->nullable();
            $table->string('account_number');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        DB::table('source_import_maps')->insert([
            'source_system' => 'catalyst', 'source_table' => 'MsBank', 'source_key' => 'BC001', 'target_table' => 'banks', 'target_id' => 17,
        ]);
        DB::table('company_virtual_accounts')->insert([
            ['account_number' => '001614', 'bank_payment_id' => null, 'description' => "BankCode: BC001\r\nBranchCode: ADS"],
            ['account_number' => '001615', 'bank_payment_id' => null, 'description' => 'BankCode: XX999'],
            ['account_number' => '001616', 'bank_payment_id' => 77, 'description' => 'BankCode: BC001'],
        ]);

        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'catalyst-uat19-'.uniqid();
        mkdir($dir);
        $paytypes = $dir.DIRECTORY_SEPARATOR.'paytypes.csv';
        $customers = $dir.DIRECTORY_SEPARATOR.'customers.csv';
        file_put_contents($paytypes, "PayCode,Bank,NoRekening,NamaRekening,BankBranch,PayName,FgActive\nBCAVA,BC001,123456,PT ADS,KCU,BCA VA,Y\n");
        file_put_contents($customers, "CustCode,PaymentTo\n");

        try {
            $this->artisan('catalyst:import-bank-payments-export', [
                '--paytypes-file' => $paytypes,
                '--customers-file' => $customers,
                '--apply' => true,
            ])->assertSuccessful();
        } finally {
            @unlink($paytypes);
            @unlink($customers);
            @rmdir($dir);
        }

        $bankPaymentId = (int) DB::table('bank_payments')->where('bank_id', 17)->value('id');
        $this->assertGreaterThan(0, $bankPaymentId);
        $this->assertSame($bankPaymentId, (int) DB::table('company_virtual_accounts')->where('account_number', '001614')->value('bank_payment_id'));
        $this->assertNull(DB::table('company_virtual_accounts')->where('account_number', '001615')->value('bank_payment_id'));
        $this->assertSame(77, (int) DB::table('company_virtual_accounts')->where('account_number', '001616')->value('bank_payment_id'));
    }
}
