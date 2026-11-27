<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\ContractController;
use App\Http\Controllers\Marketing\JobAdviceController;
use App\Models\Contract;
use App\Services\Operational\OrphanedJobScheduleCleaner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 27 Nov 2026 (Customer Testing Indo, BLP-SQ/26-11/0133).
 *
 * Bug 1: kontrak BLP-CA/26-11/0005 disetujui → Job Check Unit Only Ruang-1/Ruang-3 (job 59939-59984)
 * dibuat di JA TRIAL ADS-JA/26-11/0001 (contract_id NULL). JA kontrak di-unpost & dihapus, kontrak
 * di-unpost & dihapus, Job Check tetap ada di Job Schedule.
 *
 * Bug 2: Install Free ADS-IF/26-11/0002 Ruang-4 tidak selesai → job lanjutan 59937 (new_job). Ruang-4
 * masuk kontrak (dipasang lewat JA Install kontrak), tetapi job lanjutan trial tetap menggantung.
 */
class OrphanedJobScheduleCleanupTest extends TestCase
{
    private const TABLES = [
        'quotations', 'quotation_rooms', 'contracts', 'contract_rooms', 'job_advices', 'job_advice_rooms',
        'job_schedules', 'job_schedule_rooms', 'job_schedule_room_rentals', 'job_schedule_room_assignments',
        'job_assign_schedules',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('quotation_number')->nullable();
            $t->string('status')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('quotation_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->id();
            $t->string('contract_number')->nullable();
            $t->string('contract_status')->nullable();
            $t->unsignedBigInteger('quotation_id')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('contract_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('contract_id');
            $t->unsignedBigInteger('room_id')->nullable();
            $t->timestamps();
        });
        Schema::create('job_advices', function (Blueprint $t) {
            $t->id();
            $t->string('job_advice_number')->nullable();
            $t->string('type')->nullable();
            $t->string('status')->nullable();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->unsignedBigInteger('quotation_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_advice_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->unsignedBigInteger('contract_room_id')->nullable();
            $t->unsignedBigInteger('quotation_room_id')->nullable();
            $t->unsignedBigInteger('install_job_schedule_id')->nullable();
            $t->unsignedBigInteger('service_job_schedule_id')->nullable();
            $t->unsignedBigInteger('remove_job_schedule_id')->nullable();
            $t->string('room_name')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('job_number')->nullable();
            $t->string('type')->nullable();
            $t->string('status')->nullable();
            $t->unsignedBigInteger('job_advice_id')->nullable();
            $t->string('quotation_number')->nullable();
            $t->string('contract_number')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->string('room_name')->nullable();
            $t->integer('period')->nullable();
            $t->date('ba_date')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->text('internal_notes')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedule_rooms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id');
            $t->unsignedBigInteger('job_advice_room_id')->nullable();
            $t->unsignedBigInteger('room_id')->nullable();
            $t->string('room_name')->nullable();
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedule_room_rentals', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_room_id');
            $t->unsignedBigInteger('job_advice_room_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_schedule_room_assignments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('job_assign_schedules', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_schedule_id');
            $t->string('status')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $this->seedProdLikeCase();
    }

    protected function tearDown(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    /**
     * Data meniru prod 27 Nov 2026 (id asli dipakai supaya mudah dicocokkan).
     */
    private function seedProdLikeCase(): void
    {
        DB::table('quotations')->insert([
            ['id' => 30576, 'quotation_number' => 'BLP-SQ/26-11/0133', 'status' => 'contract'],
        ]);
        DB::table('contracts')->insert([
            ['id' => 21791, 'contract_number' => 'BLP-CA/26-11/0005', 'contract_status' => 'active', 'quotation_id' => 30576],
            ['id' => 21792, 'contract_number' => 'BLP-CA/26-11/0006', 'contract_status' => 'active', 'quotation_id' => 30576],
        ]);
        DB::table('contract_rooms')->insert([
            ['id' => 73491, 'contract_id' => 21791, 'room_id' => 45027],
        ]);

        DB::table('job_advices')->insert([
            // JA trial (tetap approved)
            ['id' => 27674, 'job_advice_number' => 'ADS-JA/26-11/0001', 'type' => 'install_free', 'status' => 'approved', 'contract_id' => null, 'quotation_id' => 30576, 'deleted_at' => null],
            // JA CSR aktivasi kontrak — sudah di-unpost & dihapus QA
            ['id' => 27675, 'job_advice_number' => 'ADS-JA/26-11/0002', 'type' => 'service', 'status' => 'draft', 'contract_id' => 21791, 'quotation_id' => 30576, 'deleted_at' => '2026-11-27 14:58:27'],
        ]);
        DB::table('job_advice_rooms')->insert([
            ['id' => 81145, 'job_advice_id' => 27674, 'room_id' => 45024, 'room_name' => 'Ruang-1', 'service_job_schedule_id' => null],
            ['id' => 81146, 'job_advice_id' => 27674, 'room_id' => 45026, 'room_name' => 'Ruang-3', 'service_job_schedule_id' => 59950],
            ['id' => 81148, 'job_advice_id' => 27674, 'room_id' => 45027, 'room_name' => 'Ruang-4', 'service_job_schedule_id' => null],
        ]);

        $trial = ['job_advice_id' => 27674, 'quotation_number' => 'BLP-SQ/26-11/0133'];
        DB::table('job_schedules')->insert([
            $trial + ['id' => 59930, 'job_number' => 'ADS-IF/26-11/0001', 'type' => 'install_free', 'status' => 'done_job', 'contract_number' => null, 'room_id' => 45024, 'room_name' => 'Ruang-1', 'period' => null, 'ba_date' => '2026-11-27', 'internal_notes' => null],
            $trial + ['id' => 59932, 'job_number' => 'ADS-IF/26-11/0002', 'type' => 'install_free', 'status' => 'meninggalkan_lokasi', 'contract_number' => null, 'room_id' => 45027, 'room_name' => 'Ruang-4', 'period' => null, 'ba_date' => '2026-11-27', 'internal_notes' => 'renovasi room'],
            // Bug 2: job lanjutan trial Ruang-4
            $trial + ['id' => 59937, 'job_number' => null, 'type' => 'install_free', 'status' => 'new_job', 'contract_number' => null, 'room_id' => null, 'room_name' => null, 'period' => null, 'ba_date' => null, 'internal_notes' => 'Lanjutan dari Job ADS-IF/26-11/0002 (Pekerjaan tidak selesai). Room: Ruang-4.'],
            // Bug 1: Job Check aktivasi kontrak 0005 di JA trial
            $trial + ['id' => 59939, 'job_number' => null, 'type' => 'service_routine', 'status' => 'scheduled', 'contract_number' => 'BLP-CA/26-11/0005', 'room_id' => 45024, 'room_name' => 'Ruang-1', 'period' => 2, 'ba_date' => null, 'internal_notes' => 'Auto-generated Check period 2/12 after install job ADS-IF/26-11/0001'],
            $trial + ['id' => 59950, 'job_number' => null, 'type' => 'service_routine', 'status' => 'scheduled', 'contract_number' => 'BLP-CA/26-11/0005', 'room_id' => 45026, 'room_name' => 'Ruang-3', 'period' => 2, 'ba_date' => null, 'internal_notes' => 'Auto-generated Check period 2/36 after install job ADS-IF/26-11/0001'],
            // Job Check kontrak 0005 yang sudah ditugaskan: tidak boleh disentuh
            $trial + ['id' => 59960, 'job_number' => 'ADS-IR/26-11/0099', 'type' => 'service_routine', 'status' => 'assign_team', 'contract_number' => 'BLP-CA/26-11/0005', 'room_id' => 45026, 'room_name' => 'Ruang-3', 'period' => 12, 'ba_date' => null, 'internal_notes' => null],
            // Job Check kontrak lain yang masih aktif: tidak boleh disentuh
            $trial + ['id' => 59999, 'job_number' => null, 'type' => 'service_routine', 'status' => 'scheduled', 'contract_number' => 'BLP-CA/26-11/0006', 'room_id' => 45024, 'room_name' => 'Ruang-1', 'period' => 2, 'ba_date' => null, 'internal_notes' => null],
        ]);
        DB::table('job_schedule_rooms')->insert([
            ['id' => 164428, 'job_schedule_id' => 59932, 'job_advice_room_id' => 81148, 'room_id' => 45027, 'room_name' => 'Ruang-4', 'status' => 'cancelled'],
            ['id' => 164435, 'job_schedule_id' => 59937, 'job_advice_room_id' => 81148, 'room_id' => 45027, 'room_name' => 'Ruang-4', 'status' => 'pending'],
            ['id' => 164437, 'job_schedule_id' => 59939, 'job_advice_room_id' => 81145, 'room_id' => 45024, 'room_name' => 'Ruang-1', 'status' => 'pending'],
            ['id' => 164448, 'job_schedule_id' => 59950, 'job_advice_room_id' => 81146, 'room_id' => 45026, 'room_name' => 'Ruang-3', 'status' => 'pending'],
            ['id' => 164500, 'job_schedule_id' => 59960, 'job_advice_room_id' => 81146, 'room_id' => 45026, 'room_name' => 'Ruang-3', 'status' => 'pending'],
            ['id' => 164600, 'job_schedule_id' => 59999, 'job_advice_room_id' => 81145, 'room_id' => 45024, 'room_name' => 'Ruang-1', 'status' => 'pending'],
        ]);
        DB::table('job_schedule_room_rentals')->insert([
            ['id' => 1, 'job_schedule_room_id' => 164435, 'job_advice_room_id' => 81148],
            ['id' => 2, 'job_schedule_room_id' => 164437, 'job_advice_room_id' => 81145],
            ['id' => 3, 'job_schedule_room_id' => 164448, 'job_advice_room_id' => 81146],
        ]);
        DB::table('job_assign_schedules')->insert([
            ['job_schedule_id' => 59960, 'status' => 'assigned'],
        ]);
    }

    private function isLive(int $jobId): bool
    {
        return DB::table('job_schedules')->where('id', $jobId)->whereNull('deleted_at')->exists();
    }

    public function test_contract_unpost_removes_unit_only_checks_left_on_the_trial_job_advice(): void
    {
        $response = app(ContractController::class)->unpost(Request::create('/', 'POST'), Contract::find(21791));
        $payload = $response->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertStringContainsString('2 Job Schedule yang belum dimulai ikut dihapus', $payload['message']);
        $this->assertStringContainsString('1 Job Schedule yang sudah berjalan tidak diubah', $payload['message']);
        $this->assertSame('draft', Contract::find(21791)->contract_status);

        $this->assertFalse($this->isLive(59939));
        $this->assertFalse($this->isLive(59950));
        $this->assertNotNull(DB::table('job_schedule_rooms')->where('id', 164437)->value('deleted_at'));
        $this->assertNotNull(DB::table('job_schedule_room_rentals')->where('id', 2)->value('deleted_at'));
        $this->assertNull(DB::table('job_advice_rooms')->where('id', 81146)->value('service_job_schedule_id'));
        $this->assertStringContainsString('[AUTO-REMOVED] Contract BLP-CA/26-11/0005 di-unpost ke Draft', DB::table('job_schedules')->where('id', 59939)->value('internal_notes'));

        // Sudah ditugaskan, trial, dan kontrak lain: tetap.
        $this->assertTrue($this->isLive(59960));
        $this->assertTrue($this->isLive(59930));
        $this->assertTrue($this->isLive(59937));
        $this->assertTrue($this->isLive(59999));
        $this->assertNull(DB::table('job_advice_rooms')->where('id', 81145)->value('deleted_at'));
    }

    public function test_contract_unpost_is_still_blocked_by_an_active_contract_job_advice(): void
    {
        DB::table('job_advices')->insert(['id' => 27680, 'job_advice_number' => 'ADS-JA/26-11/0007', 'type' => 'Install', 'status' => 'approved', 'contract_id' => 21791]);

        $response = app(ContractController::class)->unpost(Request::create('/', 'POST'), Contract::find(21791));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('active', Contract::find(21791)->contract_status);
        $this->assertTrue($this->isLive(59939));
        $this->assertTrue($this->isLive(59950));
    }

    public function test_deleting_a_draft_contract_removes_checks_left_by_an_earlier_unpost(): void
    {
        // Prod: kontrak 0005 di-unpost dengan kode lama (Job Check tertinggal), lalu dihapus.
        DB::table('contracts')->where('id', 21791)->update(['contract_status' => 'draft']);

        $response = app(ContractController::class)->destroy(Contract::find(21791));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull(Contract::find(21791));
        $this->assertFalse($this->isLive(59939));
        $this->assertFalse($this->isLive(59950));
        $this->assertTrue($this->isLive(59960));
        $this->assertTrue($this->isLive(59999));
    }

    public function test_bulk_deleting_draft_contracts_removes_their_checks_too(): void
    {
        DB::table('contracts')->where('id', 21791)->update(['contract_status' => 'draft']);

        $response = app(ContractController::class)->bulkDelete(Request::create('/', 'POST', ['ids' => [21791]]));

        $this->assertTrue($response->getData(true)['success']);
        $this->assertFalse($this->isLive(59939));
        $this->assertFalse($this->isLive(59950));
        $this->assertTrue($this->isLive(59999));
    }

    public function test_contract_number_still_used_by_another_live_contract_is_left_alone(): void
    {
        DB::table('contracts')->insert(['id' => 21800, 'contract_number' => 'BLP-CA/26-11/0005', 'contract_status' => 'active', 'quotation_id' => 30576]);
        DB::table('contracts')->where('id', 21791)->update(['contract_status' => 'draft']);

        app(ContractController::class)->destroy(Contract::find(21791));

        $this->assertTrue($this->isLive(59939));
        $this->assertTrue($this->isLive(59950));
    }

    public function test_trial_follow_up_is_removed_once_its_room_is_taken_over_by_a_contract(): void
    {
        // Ruangan trial lain (Ruang-5) yang tidak masuk kontrak: lanjutannya tetap.
        DB::table('job_schedules')->insert([
            'id' => 59938, 'job_advice_id' => 27674, 'quotation_number' => 'BLP-SQ/26-11/0133', 'type' => 'install_free', 'status' => 'new_job',
            'internal_notes' => 'Lanjutan dari Job ADS-IF/26-11/0002 (Pekerjaan tidak selesai). Room: Ruang-5.',
        ]);
        DB::table('job_schedule_rooms')->insert(['id' => 164436, 'job_schedule_id' => 59938, 'room_id' => 45028, 'room_name' => 'Ruang-5', 'status' => 'pending']);

        $removed = app(OrphanedJobScheduleCleaner::class)->removeTrialFollowUpsTakenOverByContract(Contract::find(21791), 'Ruangan trial masuk Contract BLP-CA/26-11/0005');

        $this->assertSame([59937], $removed);
        $this->assertFalse($this->isLive(59937));
        $this->assertNotNull(DB::table('job_schedule_rooms')->where('id', 164435)->value('deleted_at'));
        $this->assertNotNull(DB::table('job_schedule_room_rentals')->where('id', 1)->value('deleted_at'));
        $this->assertTrue($this->isLive(59938));
        // Job sumber (tidak selesai) dan BA-nya tetap sebagai riwayat.
        $this->assertTrue($this->isLive(59932));
        $this->assertSame('cancelled', DB::table('job_schedule_rooms')->where('id', 164428)->value('status'));
    }

    public function test_trial_follow_up_already_assigned_to_a_team_is_kept(): void
    {
        DB::table('job_assign_schedules')->insert(['job_schedule_id' => 59937, 'status' => 'assigned']);

        $removed = app(OrphanedJobScheduleCleaner::class)->removeTrialFollowUpsTakenOverByContract(Contract::find(21791), 'x');

        $this->assertSame([], $removed);
        $this->assertTrue($this->isLive(59937));
    }

    public function test_job_advice_bulk_delete_removes_its_not_started_schedules(): void
    {
        DB::table('job_advices')->insert(['id' => 27690, 'job_advice_number' => 'ADS-JA/26-11/0090', 'type' => 'Install', 'status' => 'draft', 'contract_id' => 21792]);
        DB::table('job_schedules')->insert(['id' => 60100, 'job_advice_id' => 27690, 'type' => 'install', 'status' => 'new_job', 'contract_number' => 'BLP-CA/26-11/0006']);

        $response = app(JobAdviceController::class)->bulkDelete(Request::create('/', 'POST', ['ids' => [27690]]));

        $this->assertTrue($response->getData(true)['success']);
        $this->assertNotNull(DB::table('job_advices')->where('id', 27690)->value('deleted_at'));
        $this->assertFalse($this->isLive(60100));
    }

    public function test_repair_command_is_dry_run_by_default_and_apply_cleans_all_three_cases(): void
    {
        // Kondisi prod sekarang: kontrak 0005 sudah dihapus (Job Check tertinggal), Ruang-4 masuk
        // kontrak aktif 0006, plus sisa job di JA yang sudah dihapus.
        DB::table('contracts')->where('id', 21791)->update(['contract_status' => 'draft', 'deleted_at' => '2026-11-27 15:01:19']);
        DB::table('contract_rooms')->insert(['contract_id' => 21792, 'room_id' => 45027]);
        DB::table('job_schedules')->insert(['id' => 59938, 'job_advice_id' => 27675, 'type' => 'service_first', 'status' => 'scheduled', 'contract_number' => 'BLP-CA/26-11/0005', 'room_id' => 45029, 'room_name' => 'Ruang-6', 'period' => 1]);

        $this->artisan('job-schedules:cleanup-orphaned')
            ->expectsOutputToContain('Akan dihapus (soft delete): 4 job')
            ->expectsOutputToContain('Dry-run')
            ->assertSuccessful();

        foreach ([59937, 59938, 59939, 59950] as $id) {
            $this->assertTrue($this->isLive($id), "dry-run must not touch {$id}");
        }

        $this->artisan('job-schedules:cleanup-orphaned', ['--apply' => true])->assertSuccessful();

        foreach ([59937, 59938, 59939, 59950] as $id) {
            $this->assertFalse($this->isLive($id), "{$id} should be removed");
        }
        foreach ([59930, 59932, 59960, 59999] as $id) {
            $this->assertTrue($this->isLive($id), "{$id} must stay");
        }

        $this->artisan('job-schedules:cleanup-orphaned')
            ->expectsOutputToContain('Tidak ada job yatim')
            ->assertSuccessful();
    }
}
