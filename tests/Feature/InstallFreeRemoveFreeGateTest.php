<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 26 Nov 2026 (JKT-JA/26-11/0003): Ruang-1 dan Ruang-6 sudah BA Install Free tetapi Remove
 * Free tidak pernah muncul. Syarat lama "Unit On Wall baru dibuat di panggilan BA" selalu false
 * sejak unit dicatat per ruangan saat ruangan selesai (25 Nov).
 */
class InstallFreeRemoveFreeGateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['unit_on_walls', 'contracts', 'quotations'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::create('unit_on_walls', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('install_job_schedule_id')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('quotation_number')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quotation_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    private function gate(int $installJobId, bool $createdNow = false, ?string $removeDate = '2026-11-28'): bool
    {
        $job = new JobSchedule();
        $job->id = $installJobId;
        $job->quotation_number = 'JKT-SQ/26-11/0003';

        $jobAdvice = (object) [
            'remove_date' => $removeDate,
            'quotation' => (object) ['quotation_number' => 'JKT-SQ/26-11/0003'],
        ];

        return app(JobScheduleController::class)->installFreeNeedsRemoveFree($job, $jobAdvice, $createdNow);
    }

    public function test_unit_recorded_earlier_at_room_completion_still_triggers_remove_free(): void
    {
        DB::table('unit_on_walls')->insert(['install_job_schedule_id' => 59848, 'status' => 'active']);

        $this->assertTrue($this->gate(59848));
    }

    public function test_no_unit_on_the_wall_means_no_remove_free(): void
    {
        $this->assertFalse($this->gate(59848));

        DB::table('unit_on_walls')->insert(['install_job_schedule_id' => 59848, 'status' => 'removed']);
        $this->assertFalse($this->gate(59848));
    }

    public function test_unit_created_in_the_same_call_keeps_working(): void
    {
        $this->assertTrue($this->gate(59848, true));
    }

    public function test_no_remove_date_means_no_remove_free(): void
    {
        DB::table('unit_on_walls')->insert(['install_job_schedule_id' => 59848, 'status' => 'active']);

        $this->assertFalse($this->gate(59848, true, null));
    }

    public function test_trial_already_turned_into_a_contract_gets_no_remove_free(): void
    {
        DB::table('unit_on_walls')->insert(['install_job_schedule_id' => 59848, 'status' => 'active']);
        $quotationId = DB::table('quotations')->insertGetId(['quotation_number' => 'JKT-SQ/26-11/0003']);
        DB::table('contracts')->insert(['quotation_id' => $quotationId]);

        $this->assertFalse($this->gate(59848));
        $this->assertFalse($this->gate(59848, true));
    }

    public function test_all_three_completion_paths_use_the_gate(): void
    {
        $web = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));
        $mobile = file_get_contents(app_path('Http/Controllers/Api/Mobile/JobController.php'));

        $this->assertSame(2, substr_count($web, '$this->installFreeNeedsRemoveFree('));
        $this->assertSame(1, substr_count($mobile, '$jobScheduleController->installFreeNeedsRemoveFree('));
        $this->assertStringNotContainsString('$unitCreated && $isInstallTypeSchedule', $mobile);
    }
}
