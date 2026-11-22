<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * MOM 19 Nov 2026 p3: grace period di level customer. Invoice yang lewat jatuh tempo + grace
 * period dan belum lunas membuat semua room kontraknya di-suspend (job off sampai di-unsuspend).
 */
class ContractGracePeriodSuspendTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['contract_room_suspensions', 'audit_logs', 'invoices', 'contract_rooms', 'master_rooms', 'contracts', 'customers'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->integer('grace_period_days')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('master_rooms', function (Blueprint $t) {
            $t->id();
            $t->string('room_name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id');
            $t->unsignedBigInteger('room_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_number')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->date('due_date')->nullable();
            $t->string('status')->default('draft');
            $t->string('invoice_status')->default('draft');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('action')->nullable();
            $t->string('model_type')->nullable();
            $t->unsignedBigInteger('model_id')->nullable();
            $t->text('old_values')->nullable();
            $t->text('new_values')->nullable();
            $t->text('changed_fields')->nullable();
            $t->string('ip_address')->nullable();
            $t->text('user_agent')->nullable();
            $t->string('page_name')->nullable();
            $t->string('module_name')->nullable();
            $t->timestamps();
        });
        (require database_path('migrations/2026_11_22_000004_create_contract_room_suspensions_table.php'))->up();

        DB::table('customers')->insert([
            ['id' => 1, 'name' => 'PT Tenggang 7', 'grace_period_days' => 7],
            ['id' => 2, 'name' => 'PT Tanpa Tenggang', 'grace_period_days' => 0],
        ]);
        DB::table('contracts')->insert([
            ['id' => 1, 'contract_number' => 'CT-1'],
            ['id' => 2, 'contract_number' => 'CT-2'],
            ['id' => 3, 'contract_number' => 'CT-3'],
        ]);
        DB::table('master_rooms')->insert([['id' => 1, 'room_name' => 'Lobby'], ['id' => 2, 'room_name' => 'Meeting']]);
        DB::table('contract_rooms')->insert([
            ['id' => 11, 'contract_id' => 1, 'room_id' => 1],
            ['id' => 12, 'contract_id' => 1, 'room_id' => 2],
            ['id' => 21, 'contract_id' => 2, 'room_id' => 1],
            ['id' => 31, 'contract_id' => 3, 'room_id' => 1],
        ]);
        DB::table('invoices')->insert([
            // Jatuh tempo 10 Nov + grace 7 = 17 Nov -> per 22 Nov lewat.
            ['invoice_number' => 'INV-1', 'customer_id' => 1, 'contract_id' => 1, 'due_date' => '2026-11-10', 'status' => 'sent', 'invoice_status' => 'sent'],
            // Jatuh tempo 18 Nov + grace 7 = 25 Nov -> masih dalam tenggang.
            ['invoice_number' => 'INV-2', 'customer_id' => 1, 'contract_id' => 2, 'due_date' => '2026-11-18', 'status' => 'sent', 'invoice_status' => 'sent'],
            // Sudah lunas -> tak dihitung walau lewat.
            ['invoice_number' => 'INV-3', 'customer_id' => 2, 'contract_id' => 3, 'due_date' => '2026-10-01', 'status' => 'paid', 'invoice_status' => 'paid'],
        ]);
    }

    public function test_dry_run_lists_but_does_not_suspend(): void
    {
        $this->artisan('contracts:suspend-past-grace', ['--date' => '2026-11-22'])
            ->expectsOutputToContain('CT-1')
            ->assertSuccessful();

        $this->assertSame(0, DB::table('contract_room_suspensions')->count());
    }

    public function test_apply_suspends_every_room_of_the_contract_past_grace_only(): void
    {
        $this->artisan('contracts:suspend-past-grace', ['--date' => '2026-11-22', '--apply' => true])->assertSuccessful();

        $this->assertEqualsCanonicalizing([11, 12], DB::table('contract_room_suspensions')->pluck('contract_room_id')->map(fn ($id) => (int) $id)->all());
        $this->assertStringContainsString('INV-1', (string) DB::table('contract_room_suspensions')->value('notes'));
        $this->assertSame(0, DB::table('contract_room_suspensions')->whereIn('contract_id', [2, 3])->count());

        // Dijalankan lagi tidak menggandakan suspend.
        $this->artisan('contracts:suspend-past-grace', ['--date' => '2026-11-23', '--apply' => true])->assertSuccessful();
        $this->assertSame(2, DB::table('contract_room_suspensions')->count());
    }

    public function test_customer_edit_without_the_field_keeps_the_grace_period(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Company/CustomerController.php'));

        $this->assertStringContainsString("\$request->exists('grace_period_days')", $controller);
        $this->assertStringNotContainsString("'grace_period_days' => \$request->grace_period_days ?? 0", $controller);
    }
}
