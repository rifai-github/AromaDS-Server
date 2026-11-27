<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\JobSchedule;
use App\Models\Quotation;
use App\Services\DocumentNumberService;
use App\Services\Finance\InvoiceGenerationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 26 Nov 2026 (BLP-CA/26-11/0004, After Service): Ruang-1 Unit Only lanjut dari trial, tanpa IR.
 * Job check periode 1-nya tidak pernah dianggap pemicu invoice, jadi periode 1 Ruang-1 tak tertagih.
 */
class UnitOnlyTrialContinuationInvoiceTriggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['job_schedules', 'job_schedule_rooms', 'job_advices'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('job_advices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->string('type')->nullable();
            $t->string('status')->nullable();
            $t->string('period')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedule_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id');
            $t->unsignedBigInteger('room_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        DB::table('job_advices')->insert(['id' => 1, 'contract_id' => 21134]);
        DB::table('job_schedules')->insert([
            ['id' => 59876, 'job_advice_id' => 1, 'type' => 'service_first', 'status' => 'scheduled', 'period' => '1'], // Ruang-1 check
            ['id' => 59877, 'job_advice_id' => 1, 'type' => 'service_first', 'status' => 'scheduled', 'period' => '1'], // ruang dengan IR
            ['id' => 59880, 'job_advice_id' => 1, 'type' => 'install', 'status' => 'done', 'period' => '1'],
        ]);
        DB::table('job_schedule_rooms')->insert([
            ['job_schedule_id' => 59876, 'room_id' => 1001],
            ['job_schedule_id' => 59877, 'room_id' => 1002],
            ['job_schedule_id' => 59880, 'room_id' => 1002],
        ]);
    }

    private function canTrigger(string $paymentMethod, int $jobId, bool $periodHasInstallTrigger = true): bool
    {
        $contract = new Contract;
        $contract->id = 21134;
        $contract->exists = true;
        $contract->setRelation('quotation', new Quotation(['payment_method' => $paymentMethod]));

        $method = new \ReflectionMethod(InvoiceGenerationService::class, 'jobTypeCanInvoiceRentalType');
        $method->setAccessible(true);

        return $method->invoke(
            new InvoiceGenerationService(new DocumentNumberService),
            'service_first',
            'unit_only',
            $contract,
            JobSchedule::findOrFail($jobId),
            $periodHasInstallTrigger
        );
    }

    public function test_unit_only_room_without_ir_bills_period_one_off_its_check_job(): void
    {
        $this->assertTrue($this->canTrigger('After Service', 59876));
        $this->assertTrue($this->canTrigger('Before Service', 59876), 'IR ruangan lain tak boleh menutup ruangan ini.');
    }

    public function test_unit_only_room_with_ir_still_bills_off_the_ir_only(): void
    {
        $this->assertFalse($this->canTrigger('After Service', 59877));
        $this->assertFalse($this->canTrigger('Before Service', 59877));
    }

    public function test_cancelled_ir_does_not_count(): void
    {
        DB::table('job_schedules')->where('id', 59880)->update(['status' => 'cancelled']);

        $this->assertTrue($this->canTrigger('After Service', 59877));
    }
}
