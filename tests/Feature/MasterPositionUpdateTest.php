<?php

namespace Tests\Feature;

use App\Http\Controllers\System\PositionController;
use App\Models\OptionDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Master Position > Edit selalu gagal disimpan (QA "Revisi 1", 21 Sep 2026).
 *
 * Checkbox Active di blade-nya tidak punya atribut value, jadi browser mengirim "on",
 * dan rule `boolean` di update() menolaknya - justru saat Active dicentang. Test ini
 * mengunci kedua sisinya: payload checkbox yang sah harus tersimpan, dan baris yang
 * checkbox-nya tidak dicentang harus jadi non-aktif, bukan gagal validasi.
 */
class MasterPositionUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('master_options', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('option_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_option_id')->nullable();
            $table->foreignId('parent_option_id')->nullable();
            $table->string('option_name')->nullable();
            $table->string('option_description')->nullable();
            $table->string('label')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('action')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('changed_fields')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('page_name')->nullable();
            $table->string('module_name')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Administrator',
            'email' => 'admin@example.test',
            'password' => 'password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_options')->insert([
            'id' => 7,
            'name' => 'Position',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('option_details')->insert([
            'id' => 70,
            'master_option_id' => 7,
            'option_name' => 'Teknisi',
            'option_description' => 'Lama',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::login(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        foreach (['audit_logs', 'option_details', 'master_options', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    /**
     * @testWith ["1"]
     *           ["on"]
     */
    public function test_checked_active_checkbox_is_saved(string $checkboxValue): void
    {
        // "1" adalah nilai yang dikirim blade-nya sekarang; "on" adalah yang dikirim
        // browser kalau atribut value-nya hilang lagi - keduanya harus tersimpan.
        $request = Request::create('/system/positions/70', 'PUT', [
            'option_name' => 'baru',
            'option_description' => 'baru',
            'is_active' => $checkboxValue,
        ]);

        app(PositionController::class)->update($request, OptionDetail::findOrFail(70));

        $this->assertDatabaseHas('option_details', [
            'id' => 70,
            'option_name' => 'baru',
            'option_description' => 'baru',
            'is_active' => true,
        ]);
    }

    public function test_unchecked_active_checkbox_deactivates_instead_of_failing(): void
    {
        // Checkbox yang tidak dicentang tidak ikut terkirim sama sekali.
        $request = Request::create('/system/positions/70', 'PUT', [
            'option_name' => 'baru',
            'option_description' => 'baru',
        ]);

        app(PositionController::class)->update($request, OptionDetail::findOrFail(70));

        $this->assertDatabaseHas('option_details', [
            'id' => 70,
            'option_name' => 'baru',
            'is_active' => false,
        ]);
    }
}
