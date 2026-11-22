<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * UAT 19 Nov 2026: Customer PO Number di kontrak, role PIC Info, audit trail hapus.
 */
class Uat19NovContractAndAuditTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['widgets', 'audit_logs', 'invoices', 'contracts', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    private function createUsersAndAudit(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('password')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action');
            $t->string('model_type');
            $t->unsignedBigInteger('model_id');
            $t->longText('old_values')->nullable();
            $t->longText('new_values')->nullable();
            $t->longText('changed_fields')->nullable();
            $t->string('ip_address')->nullable();
            $t->text('user_agent')->nullable();
            $t->string('page_name')->nullable();
            $t->string('module_name')->nullable();
            $t->timestamps();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Admin', 'password' => 'secret']);
        \App\Support\DeletionAuditLogger::resetCache();
    }

    public function test_deleting_a_model_without_the_audit_trait_is_recorded(): void
    {
        $this->createUsersAndAudit();
        Schema::create('widgets', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('password')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('widgets')->insert(['id' => 5, 'name' => 'Rak A', 'password' => 'rahasia']);

        $widget = new class extends Model {
            use SoftDeletes;
            protected $table = 'widgets';
            protected $guarded = [];
        };

        $this->actingAs(User::findOrFail(1));
        $widget->newQuery()->findOrFail(5)->delete();

        $log = DB::table('audit_logs')->where('action', 'deleted')->first();
        $this->assertNotNull($log);
        $this->assertSame(5, (int) $log->model_id);
        $this->assertSame(1, (int) $log->user_id);
        $this->assertStringContainsString('Rak A', $log->old_values);
        $this->assertStringNotContainsString('rahasia', $log->old_values);
    }

    public function test_deletion_without_login_is_not_recorded_and_never_blocks_the_delete(): void
    {
        $this->createUsersAndAudit();
        Schema::create('widgets', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        DB::table('widgets')->insert(['id' => 6, 'name' => 'Rak B']);

        $widget = new class extends Model {
            protected $table = 'widgets';
            protected $guarded = [];
        };
        $widget->newQuery()->findOrFail(6)->delete();

        $this->assertSame(0, DB::table('widgets')->count());
        $this->assertSame(0, DB::table('audit_logs')->count());
    }

    public function test_contract_po_number_endpoint_updates_contract_and_open_invoices_only(): void
    {
        $this->createUsersAndAudit();
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number')->nullable();
            $t->string('contract_status')->nullable();
            $t->string('status')->nullable();
            $t->string('po_number')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id');
            $t->string('po_number')->nullable();
            $t->string('status')->default('draft');
            $t->string('invoice_status')->default('draft');
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('contracts')->insert(['id' => 1, 'contract_number' => 'JKT-CA/26-11/0001', 'contract_status' => 'active', 'status' => 'active', 'po_number' => 'PO-OLD']);
        DB::table('invoices')->insert([
            ['id' => 1, 'contract_id' => 1, 'po_number' => 'PO-OLD', 'status' => 'sent', 'invoice_status' => 'sent'],
            ['id' => 2, 'contract_id' => 1, 'po_number' => null, 'status' => 'draft', 'invoice_status' => 'draft'],
            ['id' => 3, 'contract_id' => 1, 'po_number' => 'PO-OLD', 'status' => 'paid', 'invoice_status' => 'paid'],
            ['id' => 4, 'contract_id' => 1, 'po_number' => 'PO-MANUAL', 'status' => 'draft', 'invoice_status' => 'draft'],
        ]);

        $this->actingAs(User::findOrFail(1));
        $request = \Illuminate\Http\Request::create('/marketing/contracts/1/update-po-number', 'POST', ['po_number' => ' PO-2026-77 ']);
        $response = app(\App\Http\Controllers\Marketing\ContractController::class)
            ->updatePoNumber($request, \App\Models\Contract::findOrFail(1));

        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame('PO-2026-77', DB::table('contracts')->where('id', 1)->value('po_number'));
        $this->assertSame('PO-2026-77', DB::table('invoices')->where('id', 1)->value('po_number'));
        $this->assertSame('PO-2026-77', DB::table('invoices')->where('id', 2)->value('po_number'));
        $this->assertSame('PO-OLD', DB::table('invoices')->where('id', 3)->value('po_number'), 'Invoice lunas tidak diubah');
        $this->assertSame('PO-MANUAL', DB::table('invoices')->where('id', 4)->value('po_number'), 'PO yang diisi manual di invoice tidak ditimpa');
    }

    public function test_contract_show_has_po_field_and_gates_pic_contact_details(): void
    {
        $view = file_get_contents(View::getFinder()->find('marketing.contracts.show'));

        $this->assertStringContainsString('onclick="saveContractPoNumber({{ $contract->id }})"', $view);
        $this->assertStringContainsString("auth()->user()->hasPermission('marketing.contract-pic-info.view')", $view);
        $this->assertMatchesRegularExpression('/@if\(\$canSeePicInfo\)\s*<td>\{\{ \$email \}\}<\/td>\s*<td>\{\{ \$phone1 \}\}<\/td>\s*@endif/', str_replace("\r\n", "\n", $view));
        // 3 di tabel Billing Group + 1 di modal edit Additional Info (PIC Service Email).
        $this->assertSame(4, substr_count($view, '@if($canSeePicInfo)'));
        $this->assertMatchesRegularExpression('/@if\(\$canSeePicInfo\)\s*<input type="email" name="pic_service_email"/', $view);

        // Tanpa izin, server tidak menimpa pic_service_email saat Additional Info disimpan.
        $controller = file_get_contents(app_path('Http/Controllers/Marketing/ContractController.php'));
        $this->assertMatchesRegularExpression('/if \(\$request->exists\(\'pic_service_email\'\)\) \{\s*\$payload\[\'pic_service_email\'\]/', $controller);

        $print = file_get_contents(View::getFinder()->find('finance.invoices.print_template'));
        $this->assertStringContainsString('Customer PO', $print);
    }

    // MOM 19 Nov 2026 p4: Basis SQ di Contract Information, boleh diubah walau kontrak aktif.
    public function test_contract_price_basis_can_be_changed_on_an_active_contract(): void
    {
        $this->createUsersAndAudit();
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number')->nullable();
            $t->string('contract_status')->nullable();
            $t->string('status')->nullable();
            $t->string('price_basis', 20)->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        DB::table('contracts')->insert(['id' => 1, 'contract_number' => 'JKT-CA/26-11/0001', 'contract_status' => 'active', 'status' => 'active', 'price_basis' => 'rental']);

        $this->actingAs(User::findOrFail(1));
        $request = \Illuminate\Http\Request::create('/marketing/contracts/1/update-price-basis', 'POST', ['price_basis' => 'room']);
        $response = app(\App\Http\Controllers\Marketing\ContractController::class)
            ->updatePriceBasis($request, \App\Models\Contract::findOrFail(1));

        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame('room', DB::table('contracts')->where('id', 1)->value('price_basis'));

        $view = file_get_contents(View::getFinder()->find('marketing.contracts.show'));
        $this->assertStringContainsString('id="contractPriceBasis"', $view);
        $this->assertStringContainsString('update-price-basis', $view);
    }
}
