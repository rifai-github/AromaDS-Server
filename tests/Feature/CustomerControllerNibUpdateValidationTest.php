<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\CustomerController;
use App\Models\Customer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerControllerNibUpdateValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('company_type')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('customer_group')->nullable();
            $table->unsignedBigInteger('default_bank_payment_id')->nullable();
            $table->string('nib')->nullable();
            $table->string('nib_number')->nullable();
            $table->boolean('is_pkp')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('grace_period_days')->nullable();
            $table->string('default_payment')->nullable();
            $table->date('member_since')->nullable();
            $table->decimal('balance', 15, 2)->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('customer_type')->nullable();
            $table->unsignedBigInteger('province_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('subdistrict_id')->nullable();
            $table->string('website')->nullable();
            $table->string('industry')->nullable();
            $table->integer('employee_count')->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('customer_category_id')->nullable();
            $table->unsignedBigInteger('classification_id')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customers');

        parent::tearDown();
    }

    public function test_update_rejects_duplicate_nib_from_another_customer(): void
    {
        Customer::create([
            'customer_code' => 'ADS-A',
            'name' => 'Customer A',
            'nib' => '02 010 0100 0877',
        ]);

        $customerB = Customer::create([
            'customer_code' => 'ADS-B',
            'name' => 'Customer B',
        ]);

        $request = Request::create('/company/customers/'.$customerB->id, 'PUT', [
            'customer_code' => 'ADS-B',
            'name' => 'Customer B',
            'nib' => '0201001000877',
        ]);

        $controller = app(CustomerController::class);

        $this->expectException(ValidationException::class);

        $controller->update($request, $customerB);
    }
}
