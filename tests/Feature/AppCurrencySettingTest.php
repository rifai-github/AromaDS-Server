<?php

namespace Tests\Feature;

use App\Http\Controllers\Company\CompanyController;
use App\Models\Company;
use App\Models\Finance\Invoice;
use App\Support\AppCurrency;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Jawaban klien #12 (UAT 17-19 Nov 2026): mata uang diatur di Master Company, default Rupiah,
 * berlaku untuk semua customer. Pajak dan e-Materai tetap Rupiah.
 */
class AppCurrencySettingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('currencies');
        Schema::dropIfExists('companies');
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->string('npwp')->nullable();
            $t->string('nik')->nullable();
            $t->string('nitku')->nullable();
            $t->string('label_alias')->nullable();
            $t->string('status')->default('active');
            $t->string('company_type')->default('pt');
            $t->boolean('is_active')->default(true);
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->text('footer_line_1')->nullable();
            $t->text('footer_line_2')->nullable();
            $t->text('footer_line_3')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        (require database_path('migrations/2026_11_22_000008_add_currency_code_to_companies_table.php'))->up();

        DB::table('companies')->insert([
            ['id' => 1, 'name' => 'PT Pink Services Indonesia', 'code' => 'PSI'],
            ['id' => 2, 'name' => 'PT Cabang Lain', 'code' => 'OTH'],
        ]);
        AppCurrency::forget();
    }

    public function test_default_is_rupiah(): void
    {
        $this->assertSame('IDR', currency_code());
        $this->assertSame('Rp', currency_symbol());
        $this->assertSame('RUPIAH', AppCurrency::word());
    }

    public function test_falls_back_to_rupiah_when_column_missing(): void
    {
        Schema::dropIfExists('companies');
        AppCurrency::forget();

        $this->assertSame('IDR', currency_code());
        $this->assertSame('Rp', currency_symbol());
    }

    public function test_master_company_update_switches_currency_for_every_company(): void
    {
        $request = Request::create('/company/companies/1', 'PUT', [
            'name' => 'PT Pink Services Indonesia',
            'status' => 'active',
            'company_type' => 'pt',
            'currency_code' => 'MYR',
        ]);

        $response = app(CompanyController::class)->update($request, Company::findOrFail(1));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame(['MYR', 'MYR'], DB::table('companies')->orderBy('id')->pluck('currency_code')->all());
        $this->assertSame('RM', currency_symbol());
        $this->assertSame('RINGGIT', AppCurrency::word());

        // Accessor model ikut berubah.
        $invoice = new Invoice(['total_amount' => 1500000]);
        $this->assertSame('RM 1.500.000', $invoice->formatted_total_amount);
    }

    public function test_unknown_currency_is_rejected(): void
    {
        $request = Request::create('/company/companies/1', 'PUT', [
            'name' => 'PT Pink Services Indonesia',
            'status' => 'active',
            'company_type' => 'pt',
            'currency_code' => 'XYZ',
        ]);

        $response = app(CompanyController::class)->update($request, Company::findOrFail(1));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('IDR', currency_code());
    }

    public function test_master_currency_rows_extend_the_options(): void
    {
        Schema::create('currencies', function (Blueprint $t) {
            $t->id();
            $t->string('code', 3);
            $t->string('name');
            $t->string('symbol', 10);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        DB::table('currencies')->insert(['code' => 'THB', 'name' => 'Baht Thailand', 'symbol' => '฿', 'is_active' => true]);
        DB::table('companies')->update(['currency_code' => 'THB']);
        AppCurrency::forget();

        $this->assertSame('฿', currency_symbol());
        $this->assertSame('BAHT THAILAND', AppCurrency::word());
    }

    public function test_no_hardcoded_rupiah_label_left_outside_tax_and_materai(): void
    {
        $offenders = [];
        foreach ([app_path(), resource_path('views')] as $root) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                $path = str_replace('\\', '/', $file->getPathname());
                if (! str_ends_with($path, '.php')
                    || preg_match('#/views/tax/|Tax|tax-file|EMaterai|e-materai|index-datatable-example|AutoFilterable|AppCurrency#', $path)) {
                    continue;
                }
                foreach (file($path) as $no => $line) {
                    $code = trim($line);
                    if (str_starts_with($code, '//') || str_starts_with($code, '*') || str_starts_with($code, '{{--')) {
                        continue;
                    }
                    if (preg_match('/\bRp\b/', $line)) {
                        $offenders[] = substr($path, strlen(base_path()) + 1).':'.($no + 1);
                    }
                }
            }
        }

        $this->assertSame([], $offenders, 'Pakai currency_symbol() alih-alih "Rp" hardcoded.');
    }
}
