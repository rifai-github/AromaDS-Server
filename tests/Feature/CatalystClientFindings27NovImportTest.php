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

        $this->assertSame(3, $result['stats']['inserted']);

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
        $this->assertSame(3, DB::table('bank_payments')->count());
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
}
