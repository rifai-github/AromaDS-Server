<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\BankPaymentController;
use App\Http\Controllers\Company\CompanyVirtualAccountController;
use App\Http\Controllers\Company\CustomerTaxController;
use App\Http\Controllers\Marketing\MarketingPipelineController;
use App\Models\BankPayment;
use App\Models\CompanyVirtualAccount;
use App\Models\CustomerTax;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * UAT 17 Nov 2026 (sesi siang): Bank Payment, VA, Customer, Customer Tax, Pipeline.
 */
class Uat17NovCompanyMasterFixesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('bank_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('branch_name')->nullable();
            $table->boolean('is_default_va')->default(false);
            $table->string('bank_va_number')->nullable();
            $table->string('start_number')->nullable();
            $table->string('end_number')->nullable();
            $table->integer('length')->nullable();
            $table->string('current_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('company_virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('bank_payment_id')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('daily_limit', 15, 2)->nullable();
            $table->decimal('monthly_limit', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('label')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('nitku')->nullable();
            $table->string('tax_name')->nullable();
            $table->string('tax_type')->nullable();
            $table->string('ppn_code')->nullable();
            $table->decimal('tax_rate', 8, 2)->nullable();
            $table->date('effective_date')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        foreach ([
            'quotations' => ['quotation_number', 'status'],
            'surveys' => ['survey_number', 'status'],
            'contracts' => ['contract_number', 'contract_status'],
            'job_advices' => ['job_advice_number', 'status'],
        ] as $tableName => [$number, $status]) {
            Schema::create($tableName, function (Blueprint $table) use ($number, $status) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string($number)->nullable();
                $table->string($status)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    protected function tearDown(): void
    {
        foreach (['bank_payments', 'company_virtual_accounts', 'customers', 'users', 'customer_tax_settings', 'quotations', 'surveys', 'contracts', 'job_advices'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function callPrivate(object $object, string $method, array $args = [])
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    private function viewSource(string $view): string
    {
        return file_get_contents(View::getFinder()->find($view));
    }

    // ---- Item 1: hapus Bank Payment / VA = nonaktifkan ------------------------------

    public function test_bank_payment_destroy_and_bulk_delete_deactivate_instead_of_soft_delete(): void
    {
        $a = BankPayment::create(['account_name' => 'BCA A', 'is_active' => true]);
        $b = BankPayment::create(['account_name' => 'BCA B', 'is_active' => true]);
        $c = BankPayment::create(['account_name' => 'BCA C', 'is_active' => true]);

        $controller = app(BankPaymentController::class);

        $response = $controller->destroy($a->id);
        $this->assertSame('success', $response->getData(true)['status']);
        $this->assertStringContainsString('dinonaktifkan', $response->getData(true)['message']);

        $controller->bulkDelete(Request::create('/x', 'POST', ['ids' => [$b->id, $c->id]]));

        // Tetap ada (tidak soft delete) tapi nonaktif.
        $this->assertSame(3, BankPayment::count());
        $this->assertSame(0, BankPayment::onlyTrashed()->count());
        $this->assertSame(0, BankPayment::where('is_active', true)->count());
    }

    public function test_virtual_account_destroy_and_bulk_delete_deactivate_instead_of_soft_delete(): void
    {
        $va1 = CompanyVirtualAccount::create(['account_number' => '111', 'is_active' => true]);
        $va2 = CompanyVirtualAccount::create(['account_number' => '222', 'is_active' => true]);

        $controller = app(CompanyVirtualAccountController::class);

        $controller->destroy($va1);

        $request = Request::create('/x', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], json_encode(['ids' => [$va2->id]]));
        $response = $controller->bulkDelete($request);

        $this->assertSame('success', $response->getData(true)['status']);
        $this->assertSame(2, CompanyVirtualAccount::count());
        $this->assertSame(0, CompanyVirtualAccount::onlyTrashed()->count());
        $this->assertSame(0, CompanyVirtualAccount::where('is_active', true)->count());
    }

    // ---- Item 2 & 3: normalisasi nomor VA saat edit + rentang start/end -------------

    private function bank(array $attributes = []): BankPayment
    {
        $bank = new BankPayment(array_merge([
            'bank_va_number' => '88997',
            'length' => 6,
        ], $attributes));
        $bank->id = $attributes['id'] ?? 1;

        return $bank;
    }

    private function existingVa(string $number, int $bankId = 1): CompanyVirtualAccount
    {
        $va = new CompanyVirtualAccount(['account_number' => $number, 'bank_payment_id' => $bankId]);
        $va->id = 10;

        return $va;
    }

    public function test_edit_without_changing_a_legacy_va_number_is_accepted_as_is(): void
    {
        $controller = app(CompanyVirtualAccountController::class);
        $bank = $this->bank();

        // VA lama Catalyst tanpa prefix bank: dulu gagal "Panjang Suffix VA harus 6 digit".
        $legacy = $this->existingVa('7001234567');
        $this->assertSame('7001234567', $this->callPrivate($controller, 'normalizeAccountNumberForBank', ['7001234567', $bank, $legacy]));

        // VA 6 digit: dulu jadi 12 digit karena prefix ditempel lagi.
        $sixDigit = $this->existingVa('123456');
        $this->assertSame('123456', $this->callPrivate($controller, 'normalizeAccountNumberForBank', ['123456', $bank, $sixDigit]));
    }

    public function test_edit_form_sending_only_the_suffix_keeps_the_stored_full_number(): void
    {
        $controller = app(CompanyVirtualAccountController::class);
        $bank = $this->bank(['start_number' => '500000']);

        // Nomor lama di bawah start_number tetap boleh disimpan ulang tanpa diubah.
        $stored = $this->existingVa('88997000001');
        $this->assertSame('88997000001', $this->callPrivate($controller, 'normalizeAccountNumberForBank', ['000001', $bank, $stored]));
        $this->assertSame('88997000001', $this->callPrivate($controller, 'normalizeAccountNumberForBank', ['88997000001', $bank, $stored]));
    }

    public function test_full_number_is_not_double_prefixed(): void
    {
        $controller = app(CompanyVirtualAccountController::class);
        $bank = $this->bank();

        $this->assertSame('88997123456', $this->callPrivate($controller, 'normalizeAccountNumberForBank', ['88997123456', $bank]));
        $this->assertSame('88997123456', $this->callPrivate($controller, 'normalizeAccountNumberForBank', ['123456', $bank]));
    }

    public function test_suffix_outside_bank_payment_start_and_end_number_is_rejected(): void
    {
        $controller = app(CompanyVirtualAccountController::class);
        $bank = $this->bank(['start_number' => '100000', 'end_number' => '199999']);

        $this->assertSame('88997150000', $this->callPrivate($controller, 'normalizeAccountNumberForBank', ['150000', $bank]));

        try {
            $this->callPrivate($controller, 'normalizeAccountNumberForBank', ['099999', $bank]);
            $this->fail('Suffix di bawah start_number harus ditolak.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Start Number', $e->getMessage());
        }

        try {
            $this->callPrivate($controller, 'normalizeAccountNumberForBank', ['200000', $bank]);
            $this->fail('Suffix di atas end_number harus ditolak.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('End Number', $e->getMessage());
        }

        // Nomor yang benar-benar diubah tetap divalidasi walau dari form edit.
        $this->expectException(\Exception::class);
        $this->callPrivate($controller, 'normalizeAccountNumberForBank', ['000002', $bank, $this->existingVa('88997150000')]);
    }

    public function test_va_edit_lists_linked_inactive_customer_and_bank(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Company/CompanyVirtualAccountController.php'));

        $this->assertStringContainsString("\$q->orWhere('id', \$companyVirtualAccount->customer_id);", $controller);
        $this->assertStringContainsString("\$q->orWhere('id', \$companyVirtualAccount->bank_payment_id);", $controller);
        $this->assertStringContainsString('function splitEditVaNumber(account, banks)', $this->viewSource('company.company-virtual-accounts.index'));
    }

    // ---- Item 4, 5, 8: Customer -------------------------------------------------------

    public function test_customer_category_uses_customer_categories_not_customer_types(): void
    {
        $index = $this->viewSource('company.customers.index');
        $controller = file_get_contents(app_path('Http/Controllers/Company/CustomerController.php'));

        $this->assertStringContainsString('<th data-column="customerCategory__name">Category</th>', $index);
        $this->assertStringContainsString("\$customer->customerCategory->name ?? '-'", $index);
        $this->assertStringNotContainsString("\$customer->customerType->name", $index);
        $this->assertStringNotContainsString("fetch('/system/customer-types/api/list'", $index);
        $this->assertStringContainsString('customer.customer_category ? customer.customer_category.name', $index);

        $this->assertStringNotContainsString("'customer_category_id' => 'nullable|exists:customer_types,id'", $controller);
        $this->assertStringNotContainsString('$categories = CustomerType::active()', $controller);

        // Modal create customer dari Pipeline memakai validasi store() yang sama.
        $partial = $this->viewSource('company.customers.partials.create-modal');
        $this->assertStringNotContainsString('@foreach($customerTypes as $type)', $partial);
        $this->assertStringContainsString('@foreach(($categories ?? collect()) as $category)', $partial);
    }

    public function test_customer_show_lists_multi_pic_once_from_pivot_and_legacy_contacts(): void
    {
        $show = $this->viewSource('company.customers.show');

        $this->assertStringNotContainsString('<div class="info-label mt-4 mb-2">Multi PIC</div>', $show);
        $this->assertStringContainsString('->concat($customer->customerContacts ?? [])', $show);
        $this->assertStringContainsString('@forelse($picContacts as $staff)', $show);
    }

    public function test_customer_index_no_longer_renders_every_contact_as_option(): void
    {
        $index = $this->viewSource('company.customers.index');
        $controller = file_get_contents(app_path('Http/Controllers/Company/CustomerController.php'));
        $contacts = file_get_contents(app_path('Http/Controllers/Company/CustomerContactController.php'));

        $this->assertStringNotContainsString('@foreach($allContacts as $contact)', $index);
        $this->assertStringContainsString("initMultiPicSelect('create_contact_ids')", $index);
        $this->assertStringContainsString('ensureMultiPicOptions(contactEditSelect', $index);
        $this->assertStringNotContainsString('$allContacts = CustomerContact::active()->orderBy(\'name\')->get();'."\r\n\r\n        // Return JSON", $controller);
        // orWhere pencarian contact dibungkus grup supaya is_active & filter akses tetap berlaku.
        $this->assertMatchesRegularExpression('/\$q->where\(function \(\$inner\) use \(\$query\) \{\s*\$inner->where\(\'name\'/', $contacts);
    }

    // ---- Item 6: Customer Tax -----------------------------------------------------------

    public function test_customer_tax_destroy_and_bulk_delete_set_inactive(): void
    {
        $one = CustomerTax::create(['customer_id' => 1, 'tax_name' => 'NPWP', 'status' => 'active', 'is_active' => true]);
        $two = CustomerTax::create(['customer_id' => 1, 'tax_name' => 'NIK', 'status' => 'active', 'is_active' => true]);

        $controller = app(CustomerTaxController::class);
        $controller->destroy($one);
        $controller->bulkDelete(Request::create('/x', 'POST', ['ids' => [$two->id]]));

        $this->assertSame(2, CustomerTax::count());
        $this->assertSame(0, CustomerTax::onlyTrashed()->count());
        $this->assertSame(['inactive', 'inactive'], CustomerTax::orderBy('id')->pluck('status')->all());
        $this->assertSame(0, CustomerTax::where('is_active', true)->count());
    }

    public function test_customer_tax_index_honours_header_sort_including_customer_name(): void
    {
        DB::table('customers')->insert([
            ['id' => 1, 'name' => 'Zeta Hotel'],
            ['id' => 2, 'name' => 'Alpha Resto'],
        ]);
        CustomerTax::create(['customer_id' => 1, 'tax_name' => 'NPWP', 'tax_number' => '2', 'status' => 'active']);
        CustomerTax::create(['customer_id' => 2, 'tax_name' => 'NIK', 'tax_number' => '1', 'status' => 'inactive']);

        $controller = app(CustomerTaxController::class);

        $sorted = function (array $params) use ($controller) {
            $query = CustomerTax::query();
            $this->callPrivate($controller, 'applyCustomerTaxSort', [$query, Request::create('/x', 'GET', $params)]);

            return $query->pluck('customer_id')->all();
        };

        $this->assertSame([2, 1], $sorted(['sort' => 'customer__name', 'direction' => 'asc']));
        $this->assertSame([1, 2], $sorted(['sort' => 'customer__name', 'direction' => 'desc']));
        $this->assertSame([2, 1], $sorted(['sort' => 'tax_number', 'direction' => 'asc']));
        // Kolom tak dikenal / injeksi diabaikan -> default created_at desc, id desc.
        $this->assertSame([2, 1], $sorted(['sort' => 'id; drop table users', 'direction' => 'asc']));
    }

    // ---- Item 7: Pipeline Related Document --------------------------------------------

    public function test_pipeline_documents_endpoint_returns_only_the_customers_non_draft_docs_plus_selected(): void
    {
        DB::table('quotations')->insert([
            ['id' => 1, 'customer_id' => 5, 'quotation_number' => 'SQ-1', 'status' => 'approved'],
            ['id' => 2, 'customer_id' => 5, 'quotation_number' => 'SQ-2', 'status' => 'draft'],
            ['id' => 3, 'customer_id' => 6, 'quotation_number' => 'SQ-3', 'status' => 'approved'],
            ['id' => 4, 'customer_id' => 5, 'quotation_number' => 'SQ-4', 'status' => 'draft'],
        ]);
        DB::table('contracts')->insert([
            ['id' => 7, 'customer_id' => 5, 'contract_number' => 'CT-7', 'contract_status' => 'active'],
        ]);
        DB::table('surveys')->insert([
            ['id' => 8, 'customer_id' => 6, 'survey_number' => 'SV-8', 'status' => 'completed'],
        ]);
        DB::table('job_advices')->insert([
            ['id' => 9, 'customer_id' => 5, 'job_advice_number' => 'JA-9', 'status' => 'approved'],
        ]);

        $request = Request::create('/marketing/pipeline/documents/by-customer', 'GET', [
            'customer_id' => 5,
            'selected' => ['quotations' => [4]],
        ]);

        $data = app(MarketingPipelineController::class)->documentsByCustomer($request)->getData(true)['data'];

        $this->assertEqualsCanonicalizing([1, 4], array_column($data['quotations'], 'id'));
        $this->assertSame([7], array_column($data['contracts'], 'id'));
        $this->assertSame([], $data['surveys']);
        $this->assertSame([9], array_column($data['job_advices'], 'id'));
        $this->assertSame('CT-7', $data['contracts'][0]['number']);
    }

    public function test_pipeline_view_loads_related_documents_via_ajax(): void
    {
        $source = $this->viewSource('marketing.pipeline.index');

        $this->assertStringContainsString("route('marketing.pipeline.documents.by-customer')", $source);
        $this->assertStringContainsString('class="form-input flex-1 quotation-select" data-selected="${item}"', $source);
        $this->assertStringContainsString('filterDocumentsByCustomer(data.customer_id);', $source);
        $this->assertSame(4, substr_count($source, 'const customerId = currentPipelineCustomerId();'));
    }
}
