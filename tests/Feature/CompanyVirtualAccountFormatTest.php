<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\CompanyVirtualAccountController;
use App\Models\CompanyVirtualAccount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompanyVirtualAccountFormatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('customer_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bank_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('bank_va_number')->nullable();
            $table->unsignedInteger('length')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('company_virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('bank_payment_id')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('daily_limit', 15, 2)->nullable();
            $table->decimal('monthly_limit', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('customers')->insert([
            'id' => 10,
            'name' => 'Customer VA',
            'customer_code' => 'CUST-VA',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('bank_payments')->insert([
            'id' => 20,
            'account_name' => 'BCA VA',
            'bank_va_number' => '88997',
            'length' => 6,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('company_virtual_accounts');
        Schema::dropIfExists('users');
        Schema::dropIfExists('bank_payments');
        Schema::dropIfExists('customers');

        parent::tearDown();
    }

    public function test_create_stores_bank_prefix_plus_suffix_as_full_va_number(): void
    {
        $response = app(CompanyVirtualAccountController::class)->store($this->ajaxRequest('POST', [
            'customer_id' => 10,
            'bank_payment_id' => 20,
            'account_number' => '000123',
            'account_name' => 'Customer VA',
            'is_active' => 1,
        ]));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('88997000123', DB::table('company_virtual_accounts')->value('account_number'));
    }

    public function test_update_accepts_full_va_without_duplicating_bank_prefix(): void
    {
        $virtualAccount = CompanyVirtualAccount::create([
            'customer_id' => 10,
            'bank_payment_id' => 20,
            'account_number' => '88997000123',
            'account_name' => 'Customer VA',
            'is_active' => true,
        ]);

        $response = app(CompanyVirtualAccountController::class)->update($this->ajaxRequest('PUT', [
            'customer_id' => 10,
            'bank_payment_id' => 20,
            'account_number' => '88997000456',
            'account_name' => 'Customer VA Updated',
            'is_active' => 1,
        ]), $virtualAccount);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('88997000456', $virtualAccount->fresh()->account_number);
    }

    public function test_update_accepts_suffix_and_stores_full_va_number(): void
    {
        $virtualAccount = CompanyVirtualAccount::create([
            'customer_id' => 10,
            'bank_payment_id' => 20,
            'account_number' => '88997000123',
            'account_name' => 'Customer VA',
            'is_active' => true,
        ]);

        $response = app(CompanyVirtualAccountController::class)->update($this->ajaxRequest('PUT', [
            'customer_id' => 10,
            'bank_payment_id' => 20,
            'account_number' => '000789',
            'account_name' => 'Customer VA Updated',
            'is_active' => 1,
        ]), $virtualAccount);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('88997000789', $virtualAccount->fresh()->account_number);
    }

    private function ajaxRequest(string $method, array $data): Request
    {
        return Request::create('/company/company-virtual-accounts', $method, $data, [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
    }
}
