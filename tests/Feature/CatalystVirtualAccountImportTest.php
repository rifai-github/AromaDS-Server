<?php

namespace Tests\Feature;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Mengunci import VA customer dari Catalyst (MsVirtualAccount).
 *
 * Kolom `company_virtual_accounts.bank_payment_id` sengaja dibuat nullable oleh
 * migrasi 2026_09_15 supaya "baris warisan yang tidak bisa dipetakan tetap
 * tersimpan". Tapi step importnya justru membuang baris yang banknya belum punya
 * rekening di `bank_payments` — dan di Catalyst SEMUA VA memakai bank BC001, yang
 * di DB hasil bootstrap baru memang belum punya rekening. Akibatnya seluruh VA
 * customer hilang dari hasil import (dilaporkan 20 Sep 2026).
 */
class CatalystVirtualAccountImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });

        Schema::create('bank_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id');
            $table->boolean('is_default_va')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('virtual_account')->nullable();
            $table->timestamps();
        });

        Schema::create('company_virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('bank_payment_id')->nullable();
            $table->string('account_number');
            $table->string('account_name')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('source_import_maps', function (Blueprint $table) {
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

        DB::table('companies')->insert(['id' => 7, 'name' => 'ADS']);
        DB::table('customers')->insert([
            ['id' => 3478, 'name' => 'PT. RAUDHA PRIMA LESTARI'],
            ['id' => 99, 'name' => 'Customer Lain'],
        ]);
        DB::table('contracts')->insert([
            ['id' => 500, 'contract_number' => 'JKT-AG/24-01/0001', 'customer_id' => 3478, 'virtual_account' => null],
            ['id' => 501, 'contract_number' => 'JKT-AG/24-01/0002', 'customer_id' => 3478, 'virtual_account' => '88997000001'],
            ['id' => 502, 'contract_number' => 'JKT-AG/24-01/0003', 'customer_id' => 99, 'virtual_account' => null],
        ]);
        DB::table('source_import_maps')->insert([
            ['source_system' => 'catalyst', 'source_table' => 'MsCustomer', 'source_key' => '04144', 'target_table' => 'customers', 'target_id' => 3478],
            ['source_system' => 'catalyst', 'source_table' => 'MsBank', 'source_key' => 'BC001', 'target_table' => 'banks', 'target_id' => 17],
            ['source_system' => 'catalyst', 'source_table' => 'MKTContractHd', 'source_key' => 'JKT-AG/24-01/0001', 'target_table' => 'contracts', 'target_id' => 500],
            ['source_system' => 'catalyst', 'source_table' => 'MKTContractHd', 'source_key' => 'JKT-AG/24-01/0002', 'target_table' => 'contracts', 'target_id' => 501],
            ['source_system' => 'catalyst', 'source_table' => 'MKTContractHd', 'source_key' => 'JKT-AG/24-01/0003', 'target_table' => 'contracts', 'target_id' => 502],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['source_import_maps', 'company_virtual_accounts', 'contracts', 'bank_payments', 'customers', 'companies'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    /**
     * @param  list<array<string, mixed>>  $sourceRows
     */
    private function importer(array $sourceRows): object
    {
        $importer = new class($sourceRows) extends CatalystMasterDataImporter
        {
            /** @var list<string> */
            public array $warnings = [];

            public function __construct(private array $sourceRows) {}

            // Sumbernya SQL Server; test menyuntik barisnya langsung.
            protected function runStep(string $step, string $sourceTable, string $orderBy, callable $mapper, ?callable $scope = null): array
            {
                return $this->runRows($step, $sourceTable, $this->sourceRows, $mapper);
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

            public function runVirtualAccounts(): array
            {
                return $this->company_virtual_accounts();
            }
        };

        foreach (['apply' => true, 'heartbeatEvery' => 1000, 'batchId' => 1] as $name => $value) {
            $property = new ReflectionProperty(CatalystMasterDataImporter::class, $name);
            $property->setAccessible(true);
            $property->setValue($importer, $value);
        }

        return $importer;
    }

    private function row(array $overrides = []): array
    {
        return array_merge([
            'VirtualAccount' => '001614',
            'Bank' => 'BC001',
            'Customer' => '04144',
            'FgActive' => 'Y',
            'ContractNo' => null,
            'BranchCode' => 'ADS',
            'FgGetVA' => 'Y',
        ], $overrides);
    }

    public function test_it_imports_the_va_even_when_the_bank_has_no_bank_payments_account(): void
    {
        $importer = $this->importer([$this->row()]);

        $result = $importer->runVirtualAccounts();

        $this->assertSame(1, $result['stats']['inserted']);

        $va = DB::table('company_virtual_accounts')->where('account_number', '001614')->first();
        $this->assertNotNull($va, 'VA Catalyst harus tetap tersimpan walau banknya belum punya rekening.');
        $this->assertNull($va->bank_payment_id);
        $this->assertSame(3478, (int) $va->customer_id);
        $this->assertSame(7, (int) $va->company_id);
        $this->assertSame('PT. RAUDHA PRIMA LESTARI', $va->account_name);

        $this->assertNotEmpty(array_filter(
            $importer->warnings,
            fn ($message) => str_contains($message, 'bank_payments')
        ), 'Bank tanpa rekening harus dicatat sebagai peringatan, bukan dibuang diam-diam.');
    }

    public function test_it_still_links_the_bank_payment_when_the_bank_has_one(): void
    {
        DB::table('bank_payments')->insert(['id' => 9, 'bank_id' => 17, 'is_default_va' => false, 'is_active' => true]);

        $this->importer([$this->row()])->runVirtualAccounts();

        $this->assertSame(9, (int) DB::table('company_virtual_accounts')->where('account_number', '001614')->value('bank_payment_id'));
    }

    public function test_it_keeps_the_va_when_the_customer_cannot_be_mapped(): void
    {
        $importer = $this->importer([$this->row(['Customer' => '99999'])]);

        $importer->runVirtualAccounts();

        $va = DB::table('company_virtual_accounts')->where('account_number', '001614')->first();
        $this->assertNotNull($va);
        $this->assertNull($va->customer_id);
    }

    public function test_it_stamps_the_contract_named_by_contractno(): void
    {
        $this->importer([$this->row(['ContractNo' => 'JKT-AG/24-01/0001'])])->runVirtualAccounts();

        $this->assertSame('001614', DB::table('contracts')->where('id', 500)->value('virtual_account'));
    }

    public function test_it_never_overwrites_a_va_the_contract_already_has(): void
    {
        $this->importer([$this->row(['ContractNo' => 'JKT-AG/24-01/0002'])])->runVirtualAccounts();

        $this->assertSame('88997000001', DB::table('contracts')->where('id', 501)->value('virtual_account'));
    }

    public function test_it_refuses_to_stamp_a_contract_that_belongs_to_another_customer(): void
    {
        $importer = $this->importer([$this->row(['ContractNo' => 'JKT-AG/24-01/0003'])]);

        $importer->runVirtualAccounts();

        $this->assertNull(DB::table('contracts')->where('id', 502)->value('virtual_account'));
        $this->assertNotEmpty(array_filter(
            $importer->warnings,
            fn ($message) => str_contains($message, 'customer lain')
        ));
    }

    public function test_the_step_runs_after_contracts_so_contractno_can_resolve(): void
    {
        $steps = new ReflectionProperty(CatalystMasterDataImporter::class, 'steps');
        $steps->setAccessible(true);
        $order = $steps->getValue(app(CatalystMasterDataImporter::class));

        $this->assertGreaterThan(
            array_search('contracts', $order, true),
            array_search('company_virtual_accounts', $order, true),
            'company_virtual_accounts harus jalan setelah contracts.'
        );

        // billing_groups menyalin contracts.virtual_account saat dibuat.
        $this->assertLessThan(
            array_search('billing_groups', $order, true),
            array_search('company_virtual_accounts', $order, true),
            'company_virtual_accounts harus jalan sebelum billing_groups.'
        );
    }
}
