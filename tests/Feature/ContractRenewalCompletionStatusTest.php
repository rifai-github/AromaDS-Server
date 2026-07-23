<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractController;
use App\Models\Contract;
use App\Models\ContractRenewal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class ContractRenewalCompletionStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'job_schedules',
            'job_advices',
            'contract_renewals',
            'contracts',
            'quotations',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_completing_renewal_marks_old_contract_completed(): void
    {
        DB::table('quotations')->insert([
            'id' => 1,
            'quotation_type' => 'renewal',
            'existing_contract_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('contracts')->insert([
            [
                'id' => 1,
                'contract_number' => 'JKT-CA/26-05/0001',
                'quotation_id' => null,
                'contract_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'contract_number' => 'JKT-CA/26-05/0002',
                'quotation_id' => 1,
                'contract_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('contract_renewals')->insert([
            'id' => 1,
            'contract_id' => 1,
            'status' => ContractRenewal::STATUS_APPROVED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_advices')->insert([
            'id' => 1,
            'contract_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('job_schedules')->insert([
            'id' => 1,
            'job_advice_id' => 1,
            'contract_number' => 'JKT-CA/26-05/0001',
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $method = new ReflectionMethod(ContractController::class, 'completeRenewalSourceContractLink');
        $method->setAccessible(true);
        $method->invoke(app(ContractController::class), Contract::findOrFail(2));

        $this->assertSame('completed', Contract::findOrFail(1)->contract_status);
        $this->assertDatabaseHas('contract_renewals', [
            'id' => 1,
            'status' => ContractRenewal::STATUS_COMPLETED,
            'new_contract_id' => 2,
        ]);
        $this->assertDatabaseHas('job_schedules', [
            'id' => 1,
            'status' => 'cancelled',
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_type')->nullable();
            $table->foreignId('existing_contract_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->nullable();
            $table->foreignId('quotation_id')->nullable();
            $table->string('contract_status')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contract_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('new_contract_id')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advice_id')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('status')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
