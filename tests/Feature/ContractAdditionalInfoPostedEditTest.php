<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractController;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Kontrak yang sudah di-post masih boleh dikoreksi lewat tab Additional Info,
 * tapi hanya untuk PIC service, TTD internal/ADS + customer, dan kedua catatan.
 * Kode PPN, Tanggal Install, dan Tanggal Service Pertama tetap terkunci.
 */
class ContractAdditionalInfoPostedEditTest extends TestCase
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

        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('finance_tax_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->string('contract_status')->nullable();
            $table->string('ppn_code')->nullable();
            $table->foreignId('customer_signing_1_id')->nullable();
            $table->foreignId('customer_signing_2_id')->nullable();
            $table->foreignId('customer_signing_3_id')->nullable();
            $table->foreignId('customer_signing_4_id')->nullable();
            $table->foreignId('internal_signing_id')->nullable();
            $table->date('install_date')->nullable();
            $table->date('first_service_date')->nullable();
            $table->string('pic_service_email')->nullable();
            $table->text('external_remark')->nullable();
            $table->text('internal_remark')->nullable();
            $table->boolean('is_installed')->default(false);
            $table->date('installed_date')->nullable();
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
            ['id' => 1, 'name' => 'Staff Lama'],
            ['id' => 2, 'name' => 'Staff Baru'],
        ]);

        DB::table('customer_contacts')->insert([
            ['id' => 1, 'customer_id' => 1, 'name' => 'PIC Lama', 'is_active' => true],
            ['id' => 2, 'customer_id' => 1, 'name' => 'PIC Baru', 'is_active' => true],
        ]);

        DB::table('finance_tax_codes')->insert([
            ['id' => 1, 'code' => '04'],
            ['id' => 2, 'code' => '01'],
        ]);

        $this->actingAs(User::find(1));
    }

    private function seedContract(string $status): Contract
    {
        DB::table('contracts')->insert([
            'id' => 1,
            'contract_number' => 'ADS-AG/26-10/0008',
            'contract_status' => $status,
            'ppn_code' => '04',
            'customer_signing_1_id' => 1,
            'internal_signing_id' => 1,
            'install_date' => '2026-06-18',
            'first_service_date' => '2026-06-18',
            'pic_service_email' => 'lama@ssm.ac',
            'external_remark' => 'catatan lama',
            'internal_remark' => 'internal lama',
            'is_installed' => true,
        ]);

        return Contract::find(1);
    }

    private function editRequest(array $overrides = []): Request
    {
        return Request::create('/marketing/contracts/1/update-additional-info', 'POST', array_merge([
            'customer_signing_1_id' => 2,
            'internal_signing_id' => 2,
            'pic_service_email' => 'baru@ssm.ac',
            'external_remark' => 'catatan baru',
            'internal_remark' => 'internal baru',
        ], $overrides));
    }

    public function test_posted_contract_can_still_update_pic_signings_and_remarks(): void
    {
        $contract = $this->seedContract('active');

        $response = app(ContractController::class)->updateAdditionalInfo($this->editRequest(), $contract);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);

        $this->assertDatabaseHas('contracts', [
            'id' => 1,
            'customer_signing_1_id' => 2,
            'internal_signing_id' => 2,
            'pic_service_email' => 'baru@ssm.ac',
            'external_remark' => 'catatan baru',
            'internal_remark' => 'internal baru',
        ]);
    }

    public function test_posted_contract_ignores_ppn_code_and_dates_even_if_sent(): void
    {
        $contract = $this->seedContract('active');

        $response = app(ContractController::class)->updateAdditionalInfo($this->editRequest([
            'ppn_code' => '01',
            'install_date' => '2026-01-01',
            'first_service_date' => '2026-01-01',
        ]), $contract);

        $this->assertSame(200, $response->getStatusCode());

        $fresh = DB::table('contracts')->find(1);
        $this->assertSame('04', $fresh->ppn_code);
        $this->assertStringStartsWith('2026-06-18', (string) $fresh->install_date);
        $this->assertStringStartsWith('2026-06-18', (string) $fresh->first_service_date);
    }

    public function test_draft_contract_still_updates_ppn_code_and_dates(): void
    {
        $contract = $this->seedContract('draft');

        $response = app(ContractController::class)->updateAdditionalInfo($this->editRequest([
            'ppn_code' => '01',
            'install_date' => '2026-01-05',
            'first_service_date' => '2026-02-05',
        ]), $contract);

        $this->assertSame(200, $response->getStatusCode());

        $fresh = DB::table('contracts')->find(1);
        $this->assertSame('01', $fresh->ppn_code);
        $this->assertStringStartsWith('2026-01-05', (string) $fresh->install_date);
        $this->assertStringStartsWith('2026-02-05', (string) $fresh->first_service_date);
    }

    public function test_draft_contract_still_requires_the_dates(): void
    {
        $contract = $this->seedContract('draft');

        $response = app(ContractController::class)->updateAdditionalInfo($this->editRequest(), $contract);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('install_date', $response->getData(true)['errors']);
    }
}
