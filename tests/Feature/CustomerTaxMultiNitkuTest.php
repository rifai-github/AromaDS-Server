<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\CustomerTaxController;
use App\Models\CustomerTax;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * MOM 19 Nov 2026 p5: satu NPWP bisa punya beberapa NITKU dengan alamat berbeda. Form NPWP
 * punya tombol (+) untuk NITKU tambahan; tiap NITKU wajib beralamat sendiri.
 */
class CustomerTaxMultiNitkuTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['customer_tax_settings', 'customers'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('customer_tax_settings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id');
            $t->string('label')->nullable();
            $t->string('tax_number')->nullable();
            $t->string('nitku')->nullable();
            $t->string('tax_name')->nullable();
            $t->text('tax_address')->nullable();
            $t->string('tax_type')->nullable();
            $t->string('ppn_code')->nullable();
            $t->decimal('tax_rate', 5, 2)->nullable();
            $t->date('effective_date')->nullable();
            $t->date('expiry_date')->nullable();
            $t->text('description')->nullable();
            $t->string('status')->default('active');
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('customers')->insert([['id' => 1, 'name' => 'PT Satu'], ['id' => 2, 'name' => 'PT Dua']]);
        DB::table('customer_tax_settings')->insert([
            'id' => 1, 'customer_id' => 1, 'tax_number' => '0123456789012345', 'nitku' => '000000',
            'tax_name' => 'NPWP', 'tax_address' => 'Jl. Pusat', 'tax_type' => '040', 'ppn_code' => '040',
            'tax_rate' => 11, 'effective_date' => '2026-01-01', 'status' => 'active', 'is_active' => true,
        ]);
    }

    private function storeAdditional(array $rows): int
    {
        $request = Request::create('/company/customer-taxes', 'POST', ['additional_nitkus' => $rows]);
        $method = new \ReflectionMethod(CustomerTaxController::class, 'storeAdditionalNitkus');
        $method->setAccessible(true);

        return $method->invoke(app(CustomerTaxController::class), $request, CustomerTax::findOrFail(1));
    }

    public function test_additional_nitkus_become_rows_with_their_own_address(): void
    {
        $count = $this->storeAdditional([
            ['nitku' => '000001', 'address' => 'Jl. Cabang Bandung'],
            ['nitku' => '000002', 'address' => 'Jl. Cabang Surabaya'],
        ]);

        $this->assertSame(2, $count);
        $row = CustomerTax::where('nitku', '000001')->sole();
        $this->assertSame('0123456789012345', $row->tax_number);
        $this->assertSame('Jl. Cabang Bandung', $row->tax_address);
        $this->assertSame('040', $row->ppn_code);
        $this->assertSame(1, (int) $row->customer_id);
        $this->assertSame(3, CustomerTax::where('tax_number', '0123456789012345')->count());
    }

    public function test_duplicate_nitku_in_the_same_request_is_rejected(): void
    {
        $this->expectExceptionMessage('lebih dari sekali');
        $this->storeAdditional([
            ['nitku' => '000001', 'address' => 'A'],
            ['nitku' => '000001', 'address' => 'B'],
        ]);
    }

    public function test_nitku_already_owned_by_another_row_is_rejected(): void
    {
        DB::table('customer_tax_settings')->insert([
            'customer_id' => 2, 'tax_number' => '0123456789012345', 'nitku' => '000009', 'tax_name' => 'NPWP',
            'status' => 'active', 'is_active' => true,
        ]);

        $this->expectExceptionMessage('PT Dua');
        $this->storeAdditional([['nitku' => '000009', 'address' => 'X']]);
    }

    public function test_form_has_add_nitku_button_for_create_and_edit(): void
    {
        $view = file_get_contents(View::getFinder()->find('company.customer-taxes.index'));

        $this->assertStringContainsString("addAdditionalNitkuRow('create')", $view);
        $this->assertStringContainsString("addAdditionalNitkuRow('edit')", $view);
        $this->assertStringContainsString('additional_nitkus[${index}][address]', $view);
    }
}
