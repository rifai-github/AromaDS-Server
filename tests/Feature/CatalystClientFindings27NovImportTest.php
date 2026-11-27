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
        $this->create('MKTContractHd', function (Blueprint $table) {
            $table->string('TransNmbr');
            $table->string('Status')->nullable();
            $table->string('Customer')->nullable();
            $table->string('BankPayment')->nullable();
            $table->string('VirtualAccount')->nullable();
            $table->string('PrintInvoice_By')->nullable();
            $table->string('CustContractSign')->nullable();
            $table->string('Terms')->nullable();
            $table->integer('ContractPeriod')->nullable();
        });
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
        DB::table('MKTContractHd')->insert([
            'TransNmbr' => 'ADS-AG/26-07/0096', 'Status' => 'P', 'Customer' => 'ADS-ADSH0001', 'BankPayment' => 'VABCA',
            'VirtualAccount' => '002033', 'PrintInvoice_By' => 'Room', 'CustContractSign' => '1', 'Terms' => '00', 'ContractPeriod' => 12,
        ]);
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
}
