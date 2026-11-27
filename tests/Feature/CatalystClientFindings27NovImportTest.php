<?php

namespace Tests\Feature;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Temuan klien (Pak Hengky, 27 Nov 2026, contoh kontrak ADS-AG/26-07/0096) atas hasil import
 * Catalyst, dikunci sebelum reset + Full Migration 29 Nov 2026.
 *
 * Sumber Catalyst (SQL Server) diganti koneksi SQLite test: tabel MKT* / Ms* dibuat di DB yang
 * sama dengan tabel target, persis seperti CatalystUat19NovImportTest.
 */
class CatalystClientFindings27NovImportTest extends TestCase
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
        Schema::dropIfExists($table);
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

    private function map(string $sourceTable, string $sourceKey, string $targetTable, int $targetId): void
    {
        DB::table('source_import_maps')->insert([
            'source_system' => 'catalyst',
            'source_table' => $sourceTable,
            'source_key' => $sourceKey,
            'target_table' => $targetTable,
            'target_id' => $targetId,
        ]);
    }

    /**
     * @param  array<string, true>|null  $eligibleContracts  null = filter klien mati
     */
    private function importer(?array $eligibleContracts = null, bool $apply = true): object
    {
        $importer = new class($eligibleContracts) extends CatalystMasterDataImporter
        {
            /** @var list<string> */
            public array $warnings = [];

            public function __construct(private ?array $eligibleContracts) {}

            protected function source()
            {
                return DB::connection();
            }

            // Query filter klien memakai DATEADD (SQL Server); di test hasilnya disuntik.
            protected function eligibleContractNumbers(): ?array
            {
                return $this->eligibleContracts;
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

    private function stepOrder(): array
    {
        $steps = new ReflectionProperty(CatalystMasterDataImporter::class, 'steps');
        $steps->setAccessible(true);

        return $steps->getValue(app(CatalystMasterDataImporter::class));
    }

    // ── 3. Basis harga per ruangan ──────────────────────────────────────────

    private function createContractStepTables(): void
    {
        $this->create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->nullable();
            $table->string('name')->nullable();
        });
        $this->create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('quotation_date')->nullable();
            $table->string('price_basis')->nullable()->default('rental');
        });
        $this->create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number');
            $table->string('po_number')->nullable();
            $table->string('price_basis')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('marketing_id')->nullable();
            $table->date('contract_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('contract_value', 18, 2)->nullable();
            $table->decimal('net_value', 18, 2)->nullable();
            $table->string('term_of_payment')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('npwp_number')->nullable();
            $table->string('status')->nullable();
            $table->string('contract_status')->nullable();
            $table->string('contract_type')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_posted')->default(false);
            $table->boolean('is_contract')->default(false);
            $table->unsignedBigInteger('internal_signing_id')->nullable();
            $table->unsignedBigInteger('customer_signing_1_id')->nullable();
            $table->text('internal_remark')->nullable();
            $table->text('external_remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::table('customers')->insert(['id' => 11886, 'customer_code' => 'ADS-ADSH0001', 'name' => 'PT. HAIRNERDS STUDIO INDONESIA']);
        $this->map('MsCustomer', 'ADS-ADSH0001', 'customers', 11886);
    }

    public function test_contract_price_basis_comes_from_print_invoice_by(): void
    {
        $this->createImportMaps();
        $this->createContractStepTables();
        $this->createContractHeaderSource([
            $this->contractHeader(['TransNmbr' => 'ADS-AG/26-11/0001', 'SQNo' => 'ADS-SQ/26-10/0001', 'PrintInvoice_By' => 'Rental']),
        ]);

        $this->importer()->call('contracts');

        $this->assertSame('room', DB::table('contracts')->where('contract_number', 'ADS-AG/26-07/0096')->value('price_basis'));
        $this->assertSame('rental', DB::table('contracts')->where('contract_number', 'ADS-AG/26-11/0001')->value('price_basis'));
    }

    public function test_quotation_price_basis_follows_the_contract_it_became(): void
    {
        $this->createContractHeaderSource();
        $importer = $this->importer();

        $this->assertSame('room', $importer->call('sourceQuotationPriceBasis', 'ADS-SQ/26-07/1544', 'ADS-AG/26-07/0096'));
        // Tanpa SOContractNo: dicari lewat MKTContractHd.SQNo.
        $this->assertSame('room', $importer->call('sourceQuotationPriceBasis', 'ADS-SQ/26-07/1544', null));
        // SQ yang belum jadi kontrak tidak dikarang - default DB yang berlaku.
        $this->assertNull($importer->call('sourceQuotationPriceBasis', 'ADS-SQ/26-11/9999', null));

        $this->assertSame('room', $importer->call('normalizeCatalystPriceBasis', 'Room'));
        $this->assertSame('rental', $importer->call('normalizeCatalystPriceBasis', 'Rental'));
        $this->assertNull($importer->call('normalizeCatalystPriceBasis', null));
    }

    // ── 2. Hanya kontrak posted & lolos filter ──────────────────────────────

    public function test_client_filter_query_only_accepts_posted_contracts(): void
    {
        $source = file_get_contents(app_path('Services/Imports/Catalyst/CatalystMasterDataImporter.php'));
        $start = strpos($source, 'protected function eligibleContractNumbers(): ?array');
        $body = substr($source, $start, strpos($source, 'protected function contractPassesClientFilter', $start) - $start);

        $this->assertStringContainsString("WHERE H.Status = 'P'", $body);
        $this->assertStringContainsString("NOT LIKE '%test%'", $body);
        $this->assertStringContainsString('DATEADD(DAY, -1, DATEADD(MONTH, H.ContractPeriod, H.TransDate))', $body);
    }

    public function test_child_rows_of_excluded_contracts_are_not_imported(): void
    {
        $importer = $this->importer(['ADS-AG/26-07/0096' => true]);

        $rows = $importer->call('groupContractRentalRows', [
            ['TransNmbr' => 'ADS-AG/26-07/0096', 'Building' => 'B1', 'FloorCode' => '010', 'RoomCode' => '018', 'Room' => 'R1', 'Product' => 'A305200', 'Qty' => 1, 'QtyFree' => 0, 'PriceForex' => 100],
            // Status D (deleted) / T (terminate) -> tidak ada di daftar eligible.
            ['TransNmbr' => 'ADS-AG/26-07/0097', 'Building' => 'B1', 'FloorCode' => '010', 'RoomCode' => '018', 'Room' => 'R1', 'Product' => 'A305200', 'Qty' => 1, 'QtyFree' => 0, 'PriceForex' => 100],
            ['TransNmbr' => '-ADS-AG/26-07/0096', 'Building' => 'B1', 'FloorCode' => '010', 'RoomCode' => '018', 'Room' => 'R1', 'Product' => 'A305200', 'Qty' => 1, 'QtyFree' => 0, 'PriceForex' => 100],
        ]);

        $this->assertCount(1, $rows);
        $this->assertSame('ADS-AG/26-07/0096', $rows[0]['TransNmbr']);
    }

    private function createJobAdviceTables(): void
    {
        $this->create('MKTContractHd', function (Blueprint $table) {
            $table->string('TransNmbr');
            $table->string('Status')->nullable();
        });
        $this->create('MKTQuotationHd', function (Blueprint $table) {
            $table->string('TransNmbr');
            $table->integer('Revisi')->default(0);
            $table->string('Status')->nullable();
            $table->string('FgActive')->nullable();
            $table->string('SOContractNo')->nullable();
        });
        $this->create('MKTContractJobOut', function (Blueprint $table) {
            $table->string('ContractNo');
        });
        $this->create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });
        $this->create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('company_name')->nullable();
            $table->date('quotation_date')->nullable();
            $table->unsignedBigInteger('marketing_id')->nullable();
        });
        $this->create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->string('job_advice_number');
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        DB::table('MKTContractHd')->insert([
            ['TransNmbr' => 'ADS-AG/26-07/0096', 'Status' => 'P'],
            ['TransNmbr' => 'ADS-AG/25-01/0001', 'Status' => 'T'],
        ]);
        DB::table('MKTQuotationHd')->insert([
            ['TransNmbr' => 'ADS-SQ/25-01/0001', 'Revisi' => 0, 'Status' => 'P', 'FgActive' => 'Y', 'SOContractNo' => 'ADS-AG/25-01/0001'],
            ['TransNmbr' => 'ADS-SQ/26-11/0001', 'Revisi' => 0, 'Status' => 'P', 'FgActive' => 'Y', 'SOContractNo' => null],
        ]);
        DB::table('MKTContractJobOut')->insert([
            ['ContractNo' => 'ADS-SQ/25-01/0001'],
            ['ContractNo' => 'ADS-SQ/26-11/0001'],
        ]);
        DB::table('customers')->insert(['id' => 1, 'name' => 'PT A']);
        DB::table('quotations')->insert([
            ['id' => 10, 'quotation_number' => 'ADS-SQ/25-01/0001', 'customer_id' => 1],
            ['id' => 11, 'quotation_number' => 'ADS-SQ/26-11/0001', 'customer_id' => 1],
        ]);
        $this->map('MKTQuotationHd', 'ADS-SQ/25-01/0001', 'quotations', 10);
        $this->map('MKTQuotationHd', 'ADS-SQ/26-11/0001', 'quotations', 11);
    }

    public function test_job_advice_from_a_quotation_whose_contract_is_excluded_is_skipped(): void
    {
        $this->createImportMaps();
        $this->createJobAdviceTables();

        $this->importer(['ADS-AG/26-07/0096' => true])->call('job_advices');

        // SQ yang kontraknya terminate tidak boleh hidup lagi lewat JA berbasis quotation;
        // SQ yang belum jadi kontrak (mis. trial) tetap dapat JA.
        $this->assertSame(['JA-CATALYST-ADS-SQ/26-11/0001'], DB::table('job_advices')->pluck('job_advice_number')->all());
    }

    // ── 1. Bank payment sebelum kontrak ──────────────────────────────────────

    private function createBankTables(): void
    {
        $this->create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code');
            $table->string('bank_name');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        $this->create('bank_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('branch_name')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->boolean('is_default_va')->default(false);
            $table->string('bank_va_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        $this->create('MsPayType', function (Blueprint $table) {
            $table->string('PayCode');
            $table->string('PayName')->nullable();
            $table->string('FgMode')->nullable();
            $table->string('Bank')->nullable();
            $table->string('NoRekening')->nullable();
            $table->string('NamaRekening')->nullable();
            $table->string('BankBranch')->nullable();
            $table->string('BankAddr')->nullable();
            $table->string('BankPhone')->nullable();
            $table->string('BankFax')->nullable();
            $table->string('FgActive')->nullable();
        });

        DB::table('banks')->insert([
            ['id' => 42, 'bank_code' => 'BC001', 'bank_name' => 'Bank BCA', 'name' => 'Bank BCA'],
            ['id' => 51, 'bank_code' => 'BM001', 'bank_name' => 'Bank Mandiri', 'name' => 'Bank Mandiri'],
        ]);
        $this->map('MsBank', 'BC001', 'banks', 42);
        $this->map('MsBank', 'BM001', 'banks', 51);

        // Baris asli MsPayType Catalyst (dicek read-only 27 Nov 2026).
        DB::table('MsPayType')->insert([
            ['PayCode' => 'VABCA', 'PayName' => 'BCA VA 3727777116', 'FgMode' => 'B', 'Bank' => 'BC001', 'NoRekening' => '372.7777.116', 'NamaRekening' => 'PT PINK SERVICES INDONESIA', 'BankBranch' => 'KCP KEDOYA PERMAI', 'BankAddr' => null, 'BankPhone' => null, 'BankFax' => null, 'FgActive' => 'Y'],
            ['PayCode' => 'PMMKB', 'PayName' => 'BANK MANDIRI 1170005801899', 'FgMode' => 'B', 'Bank' => 'BM001', 'NoRekening' => '117-00-0580189-9', 'NamaRekening' => 'PT PINK SERVICES INDONESIA', 'BankBranch' => 'KCP JAKARTA TAMAN KEBON JERUK', 'BankAddr' => null, 'BankPhone' => null, 'BankFax' => null, 'FgActive' => 'Y'],
            // Kolom Bank kosong -> bank dari PayName.
            ['PayCode' => 'PMBCA', 'PayName' => 'BCA 3727777566', 'FgMode' => 'B', 'Bank' => '', 'NoRekening' => '372-7777-566', 'NamaRekening' => 'PINK SERVICES INDONESIA', 'BankBranch' => 'KCP KEDOYA PERMAI', 'BankAddr' => null, 'BankPhone' => null, 'BankFax' => null, 'FgActive' => 'Y'],
            // NoRekening kosong, nomor di ujung PayName (pola PayType 00001 "BANK DANAMON 0066585829").
            ['PayCode' => '00001', 'PayName' => 'BANK MANDIRI 0066585829', 'FgMode' => 'B', 'Bank' => 'BM001', 'NoRekening' => '', 'NamaRekening' => 'PT PINK SERVICES INDONESIA', 'BankBranch' => 'JAKARTA PURI KENCANA', 'BankAddr' => null, 'BankPhone' => null, 'BankFax' => null, 'FgActive' => 'Y'],
            // Bukan rekening bank.
            ['PayCode' => 'KAS01', 'PayName' => 'KAS HEAD OFFICE', 'FgMode' => 'B', 'Bank' => '', 'NoRekening' => '', 'NamaRekening' => '', 'BankBranch' => '', 'BankAddr' => null, 'BankPhone' => null, 'BankFax' => null, 'FgActive' => 'Y'],
            ['PayCode' => 'KBDG', 'PayName' => 'KAS BANDUNG', 'FgMode' => 'K', 'Bank' => '', 'NoRekening' => '', 'NamaRekening' => '', 'BankBranch' => '', 'BankAddr' => null, 'BankPhone' => null, 'BankFax' => null, 'FgActive' => 'Y'],
            ['PayCode' => 'TEST1', 'PayName' => 'TEST 20240307 1159', 'FgMode' => 'B', 'Bank' => 'BC001', 'NoRekening' => '', 'NamaRekening' => '', 'BankBranch' => '', 'BankAddr' => null, 'BankPhone' => null, 'BankFax' => null, 'FgActive' => 'N'],
        ]);
    }

    public function test_bank_payments_step_imports_company_accounts_from_mspaytype(): void
    {
        $this->createImportMaps();
        $this->createBankTables();

        $result = $this->importer()->call('bank_payments');

        $this->assertSame(4, $result['stats']['inserted']);
        $this->assertTrue(DB::table('bank_payments')->where('account_number', '0066585829')->exists(), 'Nomor rekening dari PayName.');

        $va = DB::table('bank_payments')->where('account_number', '372.7777.116')->first();
        $this->assertNotNull($va);
        $this->assertSame(42, (int) $va->bank_id);
        $this->assertSame('PT PINK SERVICES INDONESIA', $va->account_name);
        $this->assertSame(1, (int) $va->is_default_va, 'PayCode ber-VA = rekening VA.');

        $this->assertSame(51, (int) DB::table('bank_payments')->where('account_number', '117-00-0580189-9')->value('bank_id'));
        $this->assertSame(42, (int) DB::table('bank_payments')->where('account_number', '372-7777-566')->value('bank_id'), 'Bank kosong ditebak dari PayName "BCA ...".');
        $this->assertSame(0, (int) DB::table('bank_payments')->where('account_number', '372-7777-566')->value('is_default_va'));

        $this->assertSame((int) $va->id, (int) DB::table('source_import_maps')->where('source_table', 'MsPayType')->where('source_key', 'VABCA')->value('target_id'));

        // Rerun tidak membuat dobel.
        $this->importer()->call('bank_payments');
        $this->assertSame(4, DB::table('bank_payments')->count());
    }

    public function test_bank_account_number_is_normalised(): void
    {
        $importer = $this->importer();

        $this->assertSame('070.128.4496', $importer->call('normalizeBankAccountNumber', 'A/C : 070.128.4496'));
        $this->assertSame('1181003868', $importer->call('normalizeBankAccountNumber', '1181 003 868'));
        $this->assertSame('10900040471', $importer->call('normalizeBankAccountNumber', 'Bank Mandiri Batam 10900040471'));
        $this->assertNull($importer->call('normalizeBankAccountNumber', ''));
    }

    public function test_bank_payments_runs_before_customers_contracts_and_billing_groups(): void
    {
        $order = $this->stepOrder();
        $position = array_search('bank_payments', $order, true);

        $this->assertNotFalse($position);
        $this->assertGreaterThan(array_search('banks', $order, true), $position);
        foreach (['customers', 'contracts', 'company_virtual_accounts', 'billing_groups'] as $later) {
            $this->assertLessThan(array_search($later, $order, true), $position, "bank_payments harus sebelum {$later}.");
        }

        $this->assertNotContains('bank_payments', CatalystMasterDataImporter::DISABLED_STEPS);
    }

    /**
     * MKTContractHd dengan kolom yang dibaca importer, berisi baris asli ADS-AG/26-07/0096
     * (dicek read-only di Catalyst 27 Nov 2026).
     */
    private function createContractHeaderSource(array $extraRows = []): void
    {
        $this->create('MKTContractHd', function (Blueprint $table) {
            $table->string('TransNmbr');
            $table->dateTime('TransDate')->nullable();
            $table->string('Status')->nullable();
            $table->string('ContractType')->nullable();
            $table->string('CustPONo')->nullable();
            $table->string('Sales')->nullable();
            $table->string('Sales2')->nullable();
            $table->string('Customer')->nullable();
            $table->integer('ContractPeriod')->nullable();
            $table->string('BankPayment')->nullable();
            $table->string('VirtualAccount')->nullable();
            $table->string('Terms')->nullable();
            $table->decimal('BaseForex', 18, 4)->nullable();
            $table->string('InternalMemo')->nullable();
            $table->string('ContractNote')->nullable();
            $table->string('SQNo')->nullable();
            $table->string('FgTerminate')->nullable();
            $table->string('EmpSign')->nullable();
            $table->string('NPWP')->nullable();
            $table->string('CustContractSign')->nullable();
            $table->string('PrintInvoice_By')->nullable();
        });

        DB::table('MKTContractHd')->insert(array_merge([[
            'TransNmbr' => 'ADS-AG/26-07/0096', 'TransDate' => '2026-07-18 00:00:00', 'Status' => 'P', 'ContractType' => 'Renewal',
            'CustPONo' => '', 'Sales' => 'EMP094', 'Sales2' => 'EMP094', 'Customer' => 'ADS-ADSH0001', 'ContractPeriod' => 12,
            'BankPayment' => 'VABCA', 'VirtualAccount' => '002033', 'Terms' => '00', 'BaseForex' => 64800000,
            'InternalMemo' => 'RENEWAL GANTI UNIT DARI 005s/200 ML KE W300/200 ML', 'ContractNote' => '', 'SQNo' => 'ADS-SQ/26-07/1544',
            'FgTerminate' => null, 'EmpSign' => 'EMP111', 'NPWP' => '', 'CustContractSign' => '1', 'PrintInvoice_By' => 'Room',
        ]], $extraRows));
    }

    /** Baris MKTContractHd tambahan (kolom yang tak disebut = kosong). */
    private function contractHeader(array $overrides): array
    {
        return array_merge([
            'TransNmbr' => null, 'TransDate' => '2026-11-01 00:00:00', 'Status' => 'P', 'ContractType' => 'New',
            'CustPONo' => '', 'Sales' => 'EMP094', 'Sales2' => '', 'Customer' => 'ADS-ADSH0001', 'ContractPeriod' => 12,
            'BankPayment' => 'VABCA', 'VirtualAccount' => '', 'Terms' => '00', 'BaseForex' => 0, 'InternalMemo' => '',
            'ContractNote' => '', 'SQNo' => null, 'FgTerminate' => null, 'EmpSign' => '', 'NPWP' => '',
            'CustContractSign' => '', 'PrintInvoice_By' => 'Room',
        ], $overrides);
    }

    private function createBillingGroupTables(): void
    {
        $this->create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->nullable();
            $table->string('name')->nullable();
        });
        $this->create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('virtual_account')->nullable();
        });
        $this->create('billing_groups', function (Blueprint $table) {
            $table->id();
            $table->string('billing_group_name')->nullable();
            $table->string('transaction_number')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('billing_frequency')->nullable();
            $table->date('billing_start_date')->nullable();
            $table->date('billing_end_date')->nullable();
            $table->decimal('billing_amount', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('pic_name')->nullable();
            $table->string('pic_phone')->nullable();
            $table->string('pic_email')->nullable();
            $table->text('pic_address')->nullable();
            $table->string('npwp_name')->nullable();
            $table->string('npwp_number')->nullable();
            $table->string('invoice_type')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('virtual_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        $this->create('MsBillingGroup', function (Blueprint $table) {
            $table->string('BillingCode');
            $table->string('Address')->nullable();
            $table->string('Attn')->nullable();
            $table->string('Telp')->nullable();
            $table->string('Phone')->nullable();
            $table->string('EmailBilling')->nullable();
        });
        $this->createContractHeaderSource();
        $this->create('MKTContractDt', function (Blueprint $table) {
            $table->string('TransNmbr');
            $table->string('Building')->nullable();
            $table->string('Product')->nullable();
            $table->decimal('Qty', 15, 4)->nullable();
            $table->decimal('QtyFree', 15, 4)->nullable();
            $table->decimal('PriceForex', 15, 4)->nullable();
            $table->decimal('AmountForex', 15, 4)->nullable();
            $table->string('FloorCode')->nullable();
            $table->string('RoomCode')->nullable();
            $table->string('Room')->nullable();
            $table->string('BillingGroup')->nullable();
            $table->string('NPWP')->nullable();
        });

        DB::table('customers')->insert(['id' => 11886, 'customer_code' => 'ADS-ADSH0001', 'name' => 'PT. HAIRNERDS STUDIO INDONESIA']);
        DB::table('contracts')->insert(['id' => 13258, 'contract_number' => 'ADS-AG/26-07/0096', 'customer_id' => 11886, 'start_date' => '2026-07-18', 'end_date' => '2027-07-17']);
        $this->map('MKTContractHd', 'ADS-AG/26-07/0096', 'contracts', 13258);
        DB::table('MKTContractDt')->insert([
            ['TransNmbr' => 'ADS-AG/26-07/0096', 'Building' => 'ADS-ADSH0008', 'Product' => 'A305200', 'Qty' => 1, 'QtyFree' => 0, 'PriceForex' => 10800000, 'AmountForex' => 10800000, 'FloorCode' => '010', 'RoomCode' => '018', 'Room' => 'Services Area 1', 'BillingGroup' => 'ADS-BIL-12/8138', 'NPWP' => '0430314344036000'],
        ]);
    }

    public function test_billing_group_carries_the_contract_bank_payment(): void
    {
        $this->createImportMaps();
        $this->createBankTables();
        $this->createBillingGroupTables();

        $importer = $this->importer();
        $importer->call('bank_payments');
        $importer->call('billing_groups');

        $billingGroup = DB::table('billing_groups')->where('contract_id', 13258)->first();
        $this->assertNotNull($billingGroup);
        $this->assertSame('Bank BCA', $billingGroup->bank_name);
        // Format yang dipakai wizard kontrak dan dicocokkan form Edit Billing Group.
        $this->assertSame('Bank BCA - PT PINK SERVICES INDONESIA (372.7777.116)', $billingGroup->virtual_account_number);
        $this->assertSame('va_bca', $billingGroup->payment_method);
    }

    public function test_billing_group_without_imported_bank_payment_warns_instead_of_guessing(): void
    {
        $this->createImportMaps();
        $this->createBankTables();
        $this->createBillingGroupTables();

        $importer = $this->importer();
        $importer->call('billing_groups');

        $billingGroup = DB::table('billing_groups')->where('contract_id', 13258)->first();
        $this->assertNull($billingGroup->bank_name);
        $this->assertNotEmpty(array_filter($importer->warnings, fn ($m) => str_contains($m, 'step bank_payments')));
    }

    // ── 4. Marketing phone & department ─────────────────────────────────────

    private function createEmployeeSource(): void
    {
        $this->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->nullable();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->string('handphone_1')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('department_name')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('position_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        $this->create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        $this->create('MsEmployee', function (Blueprint $table) {
            $table->string('EmpNumb');
            $table->string('EmpName');
            $table->string('HandPhone')->nullable();
            $table->string('ResPhone')->nullable();
            $table->string('OriPhone')->nullable();
            $table->string('Email')->nullable();
            $table->string('Department')->nullable();
            $table->string('Branch')->nullable();
            $table->string('JobTitle')->nullable();
            $table->string('FgActive')->nullable();
        });
        $this->create('MsDepartment', function (Blueprint $table) {
            $table->string('DeptCode');
            $table->string('DeptName');
        });
        foreach (['SAUsers' => ['EmpNumb', 'UserId', 'UserName', 'FgActive', 'BranchCode', 'CreateDate'], 'MsBranchUser' => ['UserId', 'BranchCode', 'FgActive'], 'MsDeptUser' => ['UserId', 'Department', 'FgActive'], 'MsJobTitle' => ['JobTtlCode', 'JobTtlName']] as $name => $columns) {
            $this->create($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
            });
        }

        DB::table('departments')->insert(['id' => 121, 'name' => 'MKT-JKT-1']);
        $this->map('MsDepartment', '44', 'departments', 121);
        DB::table('MsDepartment')->insert(['DeptCode' => '44', 'DeptName' => 'MKT-JKT-1']);
        DB::table('MsEmployee')->insert([
            // Data asli Catalyst: EMP094 (marketing ADS-AG/26-07/0096) tidak punya nomor HP sama sekali.
            ['EmpNumb' => 'EMP094', 'EmpName' => 'Fahrul Hidayat', 'HandPhone' => null, 'ResPhone' => null, 'OriPhone' => null, 'Email' => null, 'Department' => '44', 'Branch' => 'ADS', 'JobTitle' => null, 'FgActive' => 'Y'],
            ['EmpNumb' => '20560', 'EmpName' => 'Rendra Wicaksono Driyo', 'HandPhone' => '085163050627', 'ResPhone' => null, 'OriPhone' => null, 'Email' => null, 'Department' => '44', 'Branch' => 'ADS', 'JobTitle' => null, 'FgActive' => 'Y'],
        ]);
    }

    public function test_employee_department_name_and_phone_reach_the_marketing_info(): void
    {
        $this->createImportMaps();
        $this->createEmployeeSource();
        // Nomor yang sudah diisi manual di KGI tidak boleh dikosongkan Catalyst yang kosong.
        DB::table('users')->insert(['id' => 782, 'nik' => 'EMP094', 'name' => 'Fahrul Hidayat', 'username' => 'fahrul hidayat', 'email' => 'empemp094@pinkads.internal', 'handphone_1' => '0811111111']);
        $this->map('MsEmployee', 'EMP094', 'users', 782);

        $this->importer()->call('users');

        $fahrul = DB::table('users')->where('nik', 'EMP094')->first();
        $this->assertSame('MKT-JKT-1', $fahrul->department_name);
        $this->assertSame(121, (int) $fahrul->department_id);
        $this->assertSame('0811111111', $fahrul->handphone_1);

        $rendra = DB::table('users')->where('nik', '20560')->first();
        $this->assertSame('085163050627', $rendra->handphone_1);
        $this->assertSame('MKT-JKT-1', $rendra->department_name);
    }

    // ── 5. TTD Customer dari MsContractSigning ───────────────────────────────

    public function test_contract_signing_becomes_customer_signing_1(): void
    {
        $this->createImportMaps();
        $this->createContractHeaderSource([
            $this->contractHeader(['TransNmbr' => 'ADS-AG/26-11/0001', 'CustContractSign' => '2']),
            // Kontrak di luar filter klien tidak disentuh.
            $this->contractHeader(['TransNmbr' => 'ADS-AG/25-01/0009', 'Status' => 'D', 'CustContractSign' => '1']),
        ]);
        $this->create('MsContractSigning', function (Blueprint $table) {
            $table->string('CustCode');
            $table->string('CSCode');
            $table->string('CSName')->nullable();
            $table->string('Title')->nullable();
            $table->string('Phone')->nullable();
            $table->string('Email')->nullable();
            $table->string('FgActive')->nullable();
        });
        $this->create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        $this->create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('customer_signing_1_id')->nullable();
            $table->timestamps();
        });

        // Baris asli MsContractSigning customer ADS-ADSH0001.
        DB::table('MsContractSigning')->insert([
            ['CustCode' => 'ADS-ADSH0001', 'CSCode' => '1', 'CSName' => 'Aldika Riyandi', 'Title' => 'Head of Operations', 'Phone' => '081210067754', 'Email' => 'aldikariyandi27@gmail.com', 'FgActive' => 'Y'],
            ['CustCode' => 'ADS-ADSH0001', 'CSCode' => '2', 'CSName' => 'ole', 'Title' => 'Head of Operations', 'Phone' => '081210067754', 'Email' => 'aldikariyandi27@gmail.com', 'FgActive' => 'Y'],
            ['CustCode' => 'ZZZ', 'CSCode' => '1', 'CSName' => 'Orang Lain', 'Title' => 'Owner', 'Phone' => null, 'Email' => null, 'FgActive' => 'Y'],
        ]);
        DB::table('contracts')->insert([
            ['id' => 13258, 'contract_number' => 'ADS-AG/26-07/0096', 'customer_id' => 11886],
            ['id' => 13300, 'contract_number' => 'ADS-AG/26-11/0001', 'customer_id' => 11886],
        ]);
        $this->map('MKTContractHd', 'ADS-AG/26-07/0096', 'contracts', 13258);
        $this->map('MKTContractHd', 'ADS-AG/26-11/0001', 'contracts', 13300);
        // "Ole" sudah ada sebagai kontak (MsCustContact) - dipakai ulang, bukan dibuat dobel.
        DB::table('customer_contacts')->insert(['id' => 500, 'customer_id' => 11886, 'name' => 'Ole', 'position' => null, 'phone' => '0800', 'email' => null]);

        $result = $this->importer(['ADS-AG/26-07/0096' => true, 'ADS-AG/26-11/0001' => true])->call('contract_signings');

        $this->assertSame(2, $result['stats']['updated']);

        $aldika = DB::table('customer_contacts')->where('name', 'Aldika Riyandi')->first();
        $this->assertNotNull($aldika, 'Penandatangan yang belum jadi kontak dibuat sebagai kontak customer.');
        $this->assertSame(11886, (int) $aldika->customer_id);
        $this->assertSame('Head of Operations', $aldika->position);
        $this->assertSame((int) $aldika->id, (int) DB::table('contracts')->where('id', 13258)->value('customer_signing_1_id'));

        $this->assertSame(500, (int) DB::table('contracts')->where('id', 13300)->value('customer_signing_1_id'));
        $ole = DB::table('customer_contacts')->where('id', 500)->first();
        $this->assertSame('0800', $ole->phone, 'Data kontak yang sudah ada tidak ditimpa.');
        $this->assertSame('Head of Operations', $ole->position, 'Kolom kosong dilengkapi dari MsContractSigning.');
        $this->assertSame(2, DB::table('customer_contacts')->count());

        // Rerun idempoten.
        $again = $this->importer(['ADS-AG/26-07/0096' => true, 'ADS-AG/26-11/0001' => true])->call('contract_signings');
        $this->assertSame(0, $again['stats']['updated']);
        $this->assertSame(2, DB::table('customer_contacts')->count());
    }

    public function test_contract_signings_step_runs_after_contracts_and_contacts(): void
    {
        $order = $this->stepOrder();

        $this->assertGreaterThan(array_search('contracts', $order, true), array_search('contract_signings', $order, true));
        $this->assertGreaterThan(array_search('customer_contacts', $order, true), array_search('contract_signings', $order, true));
    }

    // ── 6. Room Type & Floor ────────────────────────────────────────────────

    private function createRoomTables(): void
    {
        $this->create('master_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_code');
            $table->string('room_name');
            $table->unsignedBigInteger('building_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('floor')->nullable();
            $table->string('room_type')->nullable();
            $table->string('room_floor', 50)->nullable();
            $table->integer('room_qty')->nullable();
            $table->decimal('room_length', 8, 2)->nullable();
            $table->decimal('room_width', 8, 2)->nullable();
            $table->decimal('room_height', 8, 2)->nullable();
            $table->text('room_remark')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        $this->create('MsRoom', function (Blueprint $table) {
            $table->string('RoomCode');
            $table->string('RoomName');
            $table->string('FgActive')->nullable();
        });
        $this->create('MsFloor', function (Blueprint $table) {
            $table->string('FloorCode');
            $table->string('FloorName');
            $table->string('FgActive')->nullable();
        });
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
            $table->timestamps();
            $table->softDeletes();
        });

        // Isi asli MsRoom / MsFloor Catalyst + detail Master Option prod.
        DB::table('MsRoom')->insert([
            ['RoomCode' => '-', 'RoomName' => '-', 'FgActive' => 'N'],
            ['RoomCode' => '005', 'RoomName' => 'LOBBY', 'FgActive' => 'Y'],
            ['RoomCode' => '018', 'RoomName' => 'ROOM', 'FgActive' => 'Y'],
        ]);
        DB::table('MsFloor')->insert([
            ['FloorCode' => '000', 'FloorName' => '-', 'FgActive' => 'N'],
            ['FloorCode' => '004', 'FloorName' => 'GROUND', 'FgActive' => 'Y'],
            ['FloorCode' => '010', 'FloorName' => 'LANTAI 1', 'FgActive' => 'Y'],
        ]);
        DB::table('master_options')->insert([['id' => 1, 'name' => 'Room Type'], ['id' => 2, 'name' => 'Floor']]);
        DB::table('option_details')->insert([
            ['master_option_id' => 1, 'option_name' => 'Lobby'],
            ['master_option_id' => 1, 'option_name' => 'Room'],
            ['master_option_id' => 2, 'option_name' => 'Ground'],
        ]);
    }

    public function test_imported_room_gets_room_type_and_floor_names(): void
    {
        $this->createImportMaps();
        $this->createRoomTables();
        $importer = $this->importer();

        $servicesArea = $importer->call('ensureMasterRoomId', 15956, 11886, 'Services Area 1', '010', '018');
        $lobby = $importer->call('ensureMasterRoomId', 15956, 11886, 'Lobby - ADS', '004', '005');
        $unknown = $importer->call('ensureMasterRoomId', 15956, 11886, 'Gudang', '000', '-');

        $room = DB::table('master_rooms')->find($servicesArea);
        $this->assertSame('Room', $room->room_type, 'Ejaan Master Option "Room Type" dipakai.');
        $this->assertSame('LANTAI 1', $room->room_floor, 'Nama lantai, bukan kode "010".');
        $this->assertSame('LANTAI 1', $room->floor);

        $room = DB::table('master_rooms')->find($lobby);
        $this->assertSame('Lobby', $room->room_type);
        $this->assertSame('Ground', $room->room_floor);

        $room = DB::table('master_rooms')->find($unknown);
        $this->assertNull($room->room_type, 'Placeholder "-" bukan room type; dulu selalu "service".');
        $this->assertSame('000', $room->room_floor);
    }

    public function test_rooms_from_an_earlier_import_are_relabelled_but_manual_values_kept(): void
    {
        $this->createImportMaps();
        $this->createRoomTables();
        DB::table('master_rooms')->insert([
            ['id' => 27563, 'room_code' => '018', 'room_name' => 'Services Area 1', 'building_id' => 15956, 'room_type' => 'service', 'floor' => '010', 'room_floor' => '010'],
            ['id' => 27564, 'room_code' => '018', 'room_name' => 'Services Area 2', 'building_id' => 15956, 'room_type' => 'Meeting Room', 'floor' => 'Lantai Dua', 'room_floor' => 'Lantai Dua'],
        ]);
        $this->map('CATALYST_ROOM', '15956||010||018||Services Area 1', 'master_rooms', 27563);
        $this->map('CATALYST_ROOM', '15956||010||018||Services Area 2', 'master_rooms', 27564);

        $importer = $this->importer();
        $this->assertSame(27563, (int) $importer->call('ensureMasterRoomId', 15956, 11886, 'Services Area 1', '010', '018'));
        $importer->call('ensureMasterRoomId', 15956, 11886, 'Services Area 2', '010', '018');

        $fixed = DB::table('master_rooms')->find(27563);
        $this->assertSame('Room', $fixed->room_type);
        $this->assertSame('LANTAI 1', $fixed->room_floor);

        $manual = DB::table('master_rooms')->find(27564);
        $this->assertSame('Meeting Room', $manual->room_type);
        $this->assertSame('Lantai Dua', $manual->room_floor);
    }

    // ── 7. Kontrak aktif tampil "Expired" ───────────────────────────────────

    private function createQuotationSource(): void
    {
        $this->create('MKTQuotationHd', function (Blueprint $table) {
            $table->string('TransNmbr');
            $table->integer('Revisi')->default(0);
            $table->dateTime('TransDate')->nullable();
            $table->string('Status')->nullable();
            $table->string('Branch')->nullable();
            $table->string('SalesType')->nullable();
            $table->string('Sales')->nullable();
            $table->string('Customer')->nullable();
            $table->string('ContactName')->nullable();
            $table->integer('ContractPeriod')->nullable();
            $table->decimal('BaseForex', 18, 4)->nullable();
            $table->decimal('DiscForex', 18, 4)->nullable();
            $table->decimal('PPnForex', 18, 4)->nullable();
            $table->decimal('TotalForex', 18, 4)->nullable();
            $table->string('Remark')->nullable();
            $table->string('RemarkInternal')->nullable();
            $table->string('FgActive')->nullable();
            $table->string('DoneContractPrice')->nullable();
            $table->string('Term')->nullable();
            $table->string('FgDoneSOContract')->nullable();
            $table->string('SOContractNo')->nullable();
            $table->string('BillTo')->nullable();
        });
        $this->create('MKTQuotationRental', function (Blueprint $table) {
            $table->string('TransNmbr');
            $table->integer('Revisi')->default(0);
            $table->string('Building')->nullable();
            $table->string('Product')->nullable();
            $table->string('FrequencyService')->nullable();
            $table->decimal('QtyInstall', 15, 4)->nullable();
            $table->decimal('QtyTotal', 15, 4)->nullable();
            $table->decimal('QtyFree', 15, 4)->nullable();
            $table->decimal('PriceForex', 18, 4)->nullable();
            $table->decimal('AmountForex', 18, 4)->nullable();
            $table->string('OldContractNo')->nullable();
            $table->decimal('QtyContract', 15, 4)->nullable();
            $table->string('FloorCode')->nullable();
            $table->string('RoomCode')->nullable();
            $table->string('Room')->nullable();
            $table->decimal('Panjang', 8, 2)->nullable();
            $table->decimal('Lebar', 8, 2)->nullable();
            $table->decimal('Tinggi', 8, 2)->nullable();
        });

        // ADS-SQ/26-07/1544 apa adanya di Catalyst: 3 revisi, revisi 0 & 1 sudah FgActive N.
        $header = [
            'TransNmbr' => 'ADS-SQ/26-07/1544', 'TransDate' => '2026-07-06 00:00:00', 'Status' => 'P', 'Branch' => 'ADS',
            'SalesType' => 'Service Renew', 'Sales' => 'EMP094', 'Customer' => 'ADS-ADSH0001', 'ContactName' => 'Ole',
            'ContractPeriod' => 12, 'DiscForex' => 0, 'Term' => '00', 'FgDoneSOContract' => 'Y',
            'SOContractNo' => 'ADS-AG/26-07/0096', 'BillTo' => 'ADS-ADSH0001', 'Remark' => null, 'RemarkInternal' => null,
        ];
        DB::table('MKTQuotationHd')->insert([
            $header + ['Revisi' => 0, 'FgActive' => 'N', 'DoneContractPrice' => 'N', 'BaseForex' => 43200000, 'PPnForex' => 4752000, 'TotalForex' => 47952000],
            $header + ['Revisi' => 1, 'FgActive' => 'N', 'DoneContractPrice' => null, 'BaseForex' => 64800000, 'PPnForex' => 7128000, 'TotalForex' => 71928000],
            $header + ['Revisi' => 2, 'FgActive' => 'Y', 'DoneContractPrice' => null, 'BaseForex' => 64800000, 'PPnForex' => 7128000, 'TotalForex' => 71928000],
        ]);

        $rental = [
            'TransNmbr' => 'ADS-SQ/26-07/1544', 'FrequencyService' => '1XM', 'QtyFree' => 0, 'OldContractNo' => '',
            'RoomCode' => '018', 'Panjang' => 15, 'Lebar' => 10, 'Tinggi' => 4, 'QtyContract' => null,
        ];
        DB::table('MKTQuotationRental')->insert([
            $rental + ['Revisi' => 0, 'Building' => 'ADS-ADSH0011', 'Product' => 'ADS05-12-200', 'QtyInstall' => 1, 'QtyTotal' => 1, 'PriceForex' => 10800000, 'AmountForex' => 10800000, 'FloorCode' => '010', 'Room' => 'Services Area 1'],
            $rental + ['Revisi' => 1, 'Building' => 'ADS-ADSH0008', 'Product' => 'A305200', 'QtyInstall' => 1, 'QtyTotal' => 1, 'PriceForex' => 10800000, 'AmountForex' => 10800000, 'FloorCode' => '010', 'Room' => 'Services Area 1'],
            $rental + ['Revisi' => 2, 'Building' => 'ADS-ADSH0008', 'Product' => 'A305200', 'QtyInstall' => 1, 'QtyTotal' => 1, 'PriceForex' => 10800000, 'AmountForex' => 10800000, 'FloorCode' => '010', 'Room' => 'Services Area 1'],
            $rental + ['Revisi' => 2, 'Building' => 'ADS-ADSH0008', 'Product' => 'ADS05-12-200', 'QtyInstall' => 1, 'QtyTotal' => 0, 'PriceForex' => 0, 'AmountForex' => 0, 'FloorCode' => '011', 'Room' => 'Services Area 2'],
            $rental + ['Revisi' => 2, 'Building' => 'ADS-ADSH0008', 'Product' => 'W300200', 'QtyInstall' => 0, 'QtyTotal' => 1, 'PriceForex' => 10800000, 'AmountForex' => 10800000, 'FloorCode' => '011', 'Room' => 'Services Area 2'],
        ]);
    }

    public function test_quotation_uses_its_latest_revision_and_shows_contract_status(): void
    {
        $this->createImportMaps();
        $this->createContractStepTables();
        $this->createContractHeaderSource();
        $this->createQuotationSource();
        Schema::table('quotations', function (Blueprint $table) {
            $table->unsignedBigInteger('prospect_id')->nullable();
            $table->unsignedBigInteger('survey_id')->nullable();
            $table->string('company_name')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status')->nullable();
            $table->decimal('total_amount', 18, 2)->nullable();
            $table->decimal('grand_total', 18, 2)->nullable();
            $table->integer('top_months')->nullable();
            $table->string('terms_of_payment')->nullable();
        });

        $this->importer()->call('quotations');

        $quotation = DB::table('quotations')->where('quotation_number', 'ADS-SQ/26-07/1544')->first();
        $this->assertSame('contract', $quotation->status, 'Dulu "expired" karena memakai revisi 0 yang FgActive N.');
        $this->assertSame(64800000.0, (float) $quotation->total_amount);
        $this->assertSame(71928000.0, (float) $quotation->grand_total);
        $this->assertSame('room', $quotation->price_basis);
    }

    public function test_quotation_rental_rows_come_from_the_latest_revision_only(): void
    {
        $this->createQuotationSource();

        $rows = $this->importer()->call('sourceQuotationRentalRows');

        $this->assertCount(3, $rows);
        $this->assertSame([2], array_values(array_unique(array_map(fn ($row) => (int) $row->Revisi, $rows))));
    }

    public function test_valid_contract_is_imported_active_with_catalyst_end_date(): void
    {
        $this->createImportMaps();
        $this->createContractStepTables();
        $this->createContractHeaderSource();

        $this->importer(['ADS-AG/26-07/0096' => true])->call('contracts');

        $contract = DB::table('contracts')->where('contract_number', 'ADS-AG/26-07/0096')->first();
        $this->assertSame('active', $contract->status);
        $this->assertSame('active', $contract->contract_status);
        $this->assertSame(1, (int) $contract->is_posted);
        $this->assertSame('2026-07-18', $contract->start_date);
        // Catalyst tidak punya EndDate: TransDate + ContractPeriod bulan - 1 hari.
        $this->assertSame('2027-07-17', $contract->end_date);
    }

    // ── 8. Harga per TOP ────────────────────────────────────────────────────

    public function test_catalyst_annual_price_is_converted_to_price_per_top(): void
    {
        $importer = $this->importer();

        // Kasus klien: 10.800.000 / tahun, TOP "00" (1 bulan), kontrak 12 bulan -> 900.000.
        $this->assertSame(900000.0, $importer->catalystPricePerTop(10800000, '00', 12));
        // Harga Catalyst per TAHUN (BaseForex = SUM(AmountForex) x periode / 12), jadi kontrak
        // 24 bulan TOP 1 bulan tetap 900.000, bukan 450.000.
        $this->assertSame(900000.0, $importer->catalystPricePerTop(10800000, '00', 24));
        // Asumsi, menunggu konfirmasi klien: TOP 3 bulan -> 3/12.
        $this->assertSame(2700000.0, $importer->catalystPricePerTop(10800000, '3XM', 12));
        // "1x" (In Advance) = sekali bayar seluruh periode: 10 bulan -> 10/12.
        $this->assertSame(9000000.0, $importer->catalystPricePerTop(10800000, '1x', 10));
        // "2x per periode", 12 bulan -> tiap 6 bulan.
        $this->assertSame(5400000.0, $importer->catalystPricePerTop(10800000, '2x', 12));
        // Kode tak dikenal -> 1 bulan (default Contract::top_interval_months).
        $this->assertSame(900000.0, $importer->catalystPricePerTop(10800000, null, 12));
    }

    private function createContractRentalTables(): void
    {
        $this->create('master_rentals', function (Blueprint $table) {
            $table->id();
            $table->string('rental_code');
            $table->softDeletes();
        });
        $this->create('contract_rentals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('master_rental_id');
            $table->string('rental_alias')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->integer('qty_free')->default(0);
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->decimal('total_price', 18, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        DB::table('master_rentals')->insert([
            ['id' => 35, 'rental_code' => 'A305200'],
            ['id' => 43, 'rental_code' => 'ADS05-12-200'],
            ['id' => 332, 'rental_code' => 'W300200'],
        ]);
    }

    public function test_contract_rental_price_is_stored_per_top(): void
    {
        $this->createImportMaps();
        $this->createBillingGroupTables();
        $this->createContractRentalTables();
        DB::table('MKTContractDt')->insert([
            ['TransNmbr' => 'ADS-AG/26-07/0096', 'Building' => 'ADS-ADSH0008', 'Product' => 'W300200', 'Qty' => 2, 'QtyFree' => 0, 'PriceForex' => 10800000, 'AmountForex' => 21600000, 'FloorCode' => '011', 'RoomCode' => '018', 'Room' => 'Services Area 2', 'BillingGroup' => 'ADS-BIL-12/8138', 'NPWP' => ''],
        ]);

        $this->importer(['ADS-AG/26-07/0096' => true])->call('contract_rentals');

        $a305 = DB::table('contract_rentals')->where('master_rental_id', 35)->first();
        $this->assertSame(900000.0, (float) $a305->unit_price);
        $this->assertSame(900000.0, (float) $a305->total_price);

        $w300 = DB::table('contract_rentals')->where('master_rental_id', 332)->first();
        $this->assertSame(900000.0, (float) $w300->unit_price);
        $this->assertSame(1800000.0, (float) $w300->total_price);
    }

    public function test_quotation_rental_price_uses_the_sq_term_and_period(): void
    {
        $this->createQuotationSource();

        $this->assertSame(900000.0, $this->importer()->call('sourceQuotationPricePerTop', 'ADS-SQ/26-07/1544', 10800000.0));
    }

    // ── 9. QTY + QTY free < 1 tidak diimport ────────────────────────────────

    public function test_contract_rows_without_quantity_are_not_imported(): void
    {
        $this->createImportMaps();
        $this->createBillingGroupTables();
        $this->createContractRentalTables();
        $this->create('contract_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('billing_group_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        $base = ['TransNmbr' => 'ADS-AG/26-07/0096', 'Building' => 'ADS-ADSH0008', 'RoomCode' => '018', 'BillingGroup' => 'ADS-BIL-12/8138', 'NPWP' => ''];
        DB::table('MKTContractDt')->insert([
            // Unit lama yang diganti saat renewal: Qty 0, QtyFree 0 (asli ADS-AG/26-07/0096).
            $base + ['Product' => 'ADS05-12-200', 'Qty' => 0, 'QtyFree' => 0, 'PriceForex' => 0, 'AmountForex' => 0, 'FloorCode' => '011', 'Room' => 'Services Area 2'],
            $base + ['Product' => 'W300200', 'Qty' => 1, 'QtyFree' => 0, 'PriceForex' => 10800000, 'AmountForex' => 10800000, 'FloorCode' => '011', 'Room' => 'Services Area 2'],
            // Ruangan yang hanya berisi baris qty 0.
            $base + ['Product' => 'ADS05-12-200', 'Qty' => 0, 'QtyFree' => 0, 'PriceForex' => 0, 'AmountForex' => 0, 'FloorCode' => '012', 'Room' => 'Gudang'],
            // Qty 0 tapi QtyFree 1 = unit gratis, tetap masuk.
            $base + ['Product' => 'A305200', 'Qty' => 0, 'QtyFree' => 1, 'PriceForex' => 0, 'AmountForex' => 0, 'FloorCode' => '013', 'Room' => 'Lobby'],
        ]);
        DB::table('source_import_maps')->insert(['source_system' => 'catalyst', 'source_table' => 'MsBuilding', 'source_key' => 'ADS-ADSH0008', 'target_table' => 'buildings', 'target_id' => 15956]);
        $this->createRoomTables();

        $importer = $this->importer(['ADS-AG/26-07/0096' => true]);
        $importer->call('contract_rooms');
        $importer->call('contract_rentals');

        $this->assertSame(
            ['A305200', 'A305200', 'W300200'],
            DB::table('contract_rentals')->orderBy('rental_alias')->pluck('rental_alias')->all(),
            'ADS05-12-200 (qty 0 + free 0) tidak boleh jadi rental kontrak.'
        );
        $this->assertSame(
            ['Lobby', 'Services Area 1', 'Services Area 2'],
            DB::table('contract_rooms')->join('master_rooms', 'master_rooms.id', '=', 'contract_rooms.room_id')->orderBy('room_name')->pluck('room_name')->all(),
            'Ruangan "Gudang" hanya berisi baris qty 0.'
        );
    }

    public function test_quotation_rows_without_quantity_are_skipped(): void
    {
        $importer = $this->importer();

        $this->assertFalse($importer->call('sourceRowHasQuantity', ['QtyTotal' => 0, 'QtyFree' => 0], true));
        $this->assertTrue($importer->call('sourceRowHasQuantity', ['QtyTotal' => 0, 'QtyFree' => 1], true));
        $this->assertTrue($importer->call('sourceRowHasQuantity', ['QtyTotal' => 1, 'QtyFree' => 0], true));
        $this->assertFalse($importer->call('sourceRowHasQuantity', ['Qty' => 0, 'QtyFree' => 0]));
    }

    // ── 10. Jenis invoice default Hard Copy ─────────────────────────────────

    public function test_imported_billing_group_defaults_to_hard_copy_without_overwriting_existing_choice(): void
    {
        $this->createImportMaps();
        $this->createBankTables();
        $this->createBillingGroupTables();

        $this->importer()->call('billing_groups');
        $billingGroup = DB::table('billing_groups')->where('contract_id', 13258)->first();
        $this->assertSame('hard_copy', $billingGroup->invoice_type);

        // Diubah user lalu import ulang: pilihan user tetap.
        DB::table('billing_groups')->where('id', $billingGroup->id)->update(['invoice_type' => 'soft_copy']);
        $this->importer()->call('billing_groups');
        $this->assertSame('soft_copy', DB::table('billing_groups')->where('id', $billingGroup->id)->value('invoice_type'));
    }

    // ── 11. Nama customer = FPCustKode, alias = CustName ────────────────────

    public function test_customer_name_comes_from_fpcustkode_and_alias_from_custname(): void
    {
        $this->createImportMaps();
        $this->create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->nullable();
            $table->string('name')->nullable();
            $table->string('label_alias')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('grace_period_days')->default(0);
            $table->timestamps();
        });
        $this->create('MsCustomer', function (Blueprint $table) {
            $table->string('CustCode');
            $table->string('CustName')->nullable();
            $table->string('FPCustKode')->nullable();
            $table->string('FgActive')->nullable();
        });
        DB::table('MsCustomer')->insert([
            // Asli Catalyst: CustName & FPCustKode berisi baris baru di ujung.
            ['CustCode' => 'ADS-ADSH0001', 'CustName' => "Hairnerd Studio\r\n", 'FPCustKode' => "PT. HAIRNERDS  STUDIO\r\nINDONESIA\r\n\r\n", 'FgActive' => 'Y'],
            ['CustCode' => '00017', 'CustName' => 'Hotel Contoh', 'FPCustKode' => '  ', 'FgActive' => 'Y'],
            ['CustCode' => '00018', 'CustName' => 'Toko B', 'FPCustKode' => 'SESUAI ATAS NAMA DI NPWP', 'FgActive' => 'Y'],
        ]);
        // Customer lama dengan kode sama dipakai ulang, tidak dibuat dobel.
        DB::table('customers')->insert(['id' => 11886, 'customer_code' => 'ADS-ADSH0001', 'name' => 'Hairnerd Studio']);

        $this->importer()->call('customers');

        $this->assertSame(3, DB::table('customers')->count());
        $hairnerd = DB::table('customers')->find(11886);
        $this->assertSame('PT. HAIRNERDS STUDIO INDONESIA', $hairnerd->name);
        $this->assertSame('Hairnerd Studio', $hairnerd->label_alias);

        $hotel = DB::table('customers')->where('customer_code', '00017')->first();
        $this->assertSame('Hotel Contoh', $hotel->name, 'FPCustKode kosong -> nama = CustName.');
        $this->assertSame('Hotel Contoh', $hotel->label_alias);

        // Dibiarkan apa adanya sampai klien memutuskan.
        $this->assertSame('SESUAI ATAS NAMA DI NPWP', DB::table('customers')->where('customer_code', '00018')->value('name'));
    }

    // ── Tambahan: PIC & bank billing group kosong (BG 21013/21014) ──────────

    private function seedBillingGroupPicSources(): void
    {
        // Data asli Catalyst untuk ADS-BIL-12/8138: Attn '', Phone '', Telp NULL, EmailBilling NULL.
        DB::table('MsBillingGroup')->insert([
            ['BillingCode' => 'ADS-BIL-12/8138', 'Address' => 'RUKO FRANKFURT, 2 Jl. Boulevard Raya Gading Serpong No.C/8', 'Attn' => '', 'Telp' => null, 'Phone' => '', 'EmailBilling' => null],
            ['BillingCode' => 'ADS-BIL-12/9000', 'Address' => '-', 'Attn' => 'Budi', 'Telp' => '', 'Phone' => '(022) 7314307', 'EmailBilling' => '-'],
        ]);
        $this->create('MsCustContact', function (Blueprint $table) {
            $table->string('CustCode');
            $table->integer('ItemNo');
            $table->string('ContactName')->nullable();
            $table->string('Phone')->nullable();
            $table->string('Handphone')->nullable();
            $table->string('Email')->nullable();
            $table->string('Type')->nullable();
            $table->string('FgActive')->nullable();
        });
        DB::table('MsCustContact')->insert([
            ['CustCode' => 'ADS-ADSH0001', 'ItemNo' => 1, 'ContactName' => 'Ole', 'Phone' => '(it)', 'Handphone' => '(it)', 'Email' => '(it)', 'Type' => 'Sales', 'FgActive' => 'Y'],
            ['CustCode' => 'ADS-ADSH0001', 'ItemNo' => 2, 'ContactName' => 'Ole', 'Phone' => '(it)', 'Handphone' => '(it)', 'Email' => '(it)', 'Type' => 'Finance', 'FgActive' => 'Y'],
        ]);
        $this->create('MsCustomer', function (Blueprint $table) {
            $table->string('CustCode');
            $table->string('Phone')->nullable();
            $table->string('Email')->nullable();
        });
        DB::table('MsCustomer')->insert(['CustCode' => 'ADS-ADSH0001', 'Phone' => '0811127029', 'Email' => 'hairnerds.studio@gmail.com']);
    }

    public function test_billing_group_pic_and_bank_are_filled_for_contract_0096(): void
    {
        $this->createImportMaps();
        $this->createBankTables();
        $this->createBillingGroupTables();
        $this->seedBillingGroupPicSources();
        DB::table('MKTContractDt')->insert([
            ['TransNmbr' => 'ADS-AG/26-07/0096', 'Building' => 'ADS-ADSH0009', 'Product' => 'W300200', 'Qty' => 1, 'QtyFree' => 0, 'PriceForex' => 10800000, 'AmountForex' => 10800000, 'FloorCode' => '010', 'RoomCode' => '018', 'Room' => 'R', 'BillingGroup' => 'ADS-BIL-12/9000', 'NPWP' => ''],
        ]);

        $importer = $this->importer();
        $importer->call('bank_payments');
        $importer->call('billing_groups');

        $bg = DB::table('billing_groups')->where('billing_group_name', 'ADS-BIL-12/8138')->first();
        $this->assertSame('Ole', $bg->pic_name, 'MsBillingGroup.Attn kosong -> kontak Finance customer.');
        $this->assertSame('0811127029', $bg->pic_phone, '"(it)" bukan nomor -> telepon MsCustomer.');
        $this->assertSame('hairnerds.studio@gmail.com', $bg->pic_email);
        $this->assertSame('RUKO FRANKFURT, 2 Jl. Boulevard Raya Gading Serpong No.C/8', $bg->pic_address);
        $this->assertSame('Bank BCA', $bg->bank_name);
        $this->assertSame('va_bca', $bg->payment_method);

        $other = DB::table('billing_groups')->where('billing_group_name', 'ADS-BIL-12/9000')->first();
        $this->assertSame('Budi', $other->pic_name);
        $this->assertSame('(022) 7314307', $other->pic_phone, "Telp '' (bukan NULL) jatuh ke Phone.");
        $this->assertSame('hairnerds.studio@gmail.com', $other->pic_email, 'EmailBilling "-" adalah placeholder.');
        $this->assertNull($other->pic_address, 'Alamat "-" -> NULL.');
    }

    public function test_placeholder_values_become_null(): void
    {
        $importer = $this->importer();

        foreach (['-', '.', '0', '/N', '/n', 'N/A', ',', ' , ', '..', '', '(it)'] as $placeholder) {
            $this->assertNull($importer->call('cleanPlaceholder', $placeholder), "'{$placeholder}'");
            $this->assertNull($importer->call('normalizePhoneLike', $placeholder), "'{$placeholder}'");
            $this->assertNull($importer->call('cleanEmailList', $placeholder), "'{$placeholder}'");
        }

        $this->assertSame('a@x.com, b@y.com', $importer->call('cleanEmailList', 'a@x.com, b@y.com'));
        $this->assertSame('Budi', $importer->call('cleanPlaceholder', ' Budi '));
        $this->assertSame('0811127029', $importer->call('normalizePhoneLike', '0811127029'));
    }
}
