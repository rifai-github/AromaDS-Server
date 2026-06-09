<?php

namespace Tests\Feature;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use App\Services\Operational\JobWebCompletionService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Locks the status-flow invariants for the WEB "Done Job with BA" fallback so the
 * dashboard cannot skip technician steps and stays consistent with the mobile
 * verifyJob() gate. Follows the in-memory (no-DB) style used across this suite
 * since database/migrations/ is empty in this checkout.
 */
class JobWebCompletionTest extends TestCase
{
    /**
     * Done Job (and therefore Done-Job-with-BA) must only be reachable from the
     * on-progress statuses — the same gate the mobile/web flow enforces.
     */
    public function test_done_job_blocked_before_on_progress_status(): void
    {
        $blockedStatuses = ['new_job', 'assign_team', 'barang_diambil', 'teknisi_tiba_dilokasi', 'meninggalkan_lokasi'];

        foreach ($blockedStatuses as $status) {
            $job = new JobSchedule(['type' => 'service', 'status' => $status]);
            $result = $this->validateWebCompletionTransition($job, 'done_job');

            $this->assertIsArray($result, "Expected status '{$status}' to be blocked for Done Job");
            $this->assertSame('error', $result['status']);
        }
    }

    public function test_done_job_allowed_from_on_progress_statuses(): void
    {
        $allowed = ['in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_selesai_pengerjaan'];

        foreach ($allowed as $status) {
            $job = new JobSchedule(['type' => 'service', 'status' => $status]);

            $this->assertTrue(
                $this->validateWebCompletionTransition($job, 'done_job'),
                "Expected status '{$status}' to allow Done Job"
            );
        }
    }

    /**
     * An already-completed job is a no-op transition (idempotent), not an error —
     * the web BA path must not error when re-submitted on a done job.
     */
    public function test_already_done_job_is_idempotent_transition(): void
    {
        $job = new JobSchedule(['type' => 'service', 'status' => 'done_job']);

        $this->assertTrue($this->validateWebCompletionTransition($job, 'done_job'));
    }

    /**
     * Phase 2: startWork must only proceed from teknisi_tiba_dilokasi or
     * barang_diambil (mirrors mobile startWork gate). The rejection branch
     * returns before any DB write, so it is safe to assert in-memory.
     */
    public function test_start_work_rejected_from_invalid_status(): void
    {
        $service = app(JobWebCompletionService::class);

        foreach (['new_job', 'assign_team', 'meninggalkan_lokasi', 'done_job'] as $status) {
            $job = new JobSchedule(['type' => 'service', 'status' => $status]);
            $res = $service->startWork($job, null);

            $this->assertFalse($res['ok'], "Expected startWork to reject status '{$status}'");
            $this->assertNotNull($res['message']);
        }
    }

    /**
     * Phase 3: confirmMaterials must only proceed from 'barang_siap_diambil'
     * (mirrors mobile confirmMaterials gate). Both rejection branches return
     * before any DB write, so they are safe to assert in-memory.
     */
    public function test_confirm_materials_rejected_from_invalid_status(): void
    {
        $service = app(JobWebCompletionService::class);

        // 'undone' is blocked with 423; other non-ready statuses with 400.
        $cases = [
            'undone' => 423,
            'new_job' => 400,
            'barang_dipersiapkan' => 400,
            'barang_diambil' => 400,
            'done_job' => 400,
        ];

        foreach ($cases as $status => $expectedCode) {
            $job = new JobSchedule(['type' => 'service', 'status' => $status]);
            $res = $service->confirmMaterials($job, null);

            $this->assertFalse($res['ok'], "Expected confirmMaterials to reject status '{$status}'");
            $this->assertSame($expectedCode, $res['code'], "Wrong code for status '{$status}'");
        }
    }

    /**
     * Phase 3: verifyMaterials must reject remove jobs (no material verification —
     * units come from Unit On Wall) and undone jobs, before touching the DB.
     */
    public function test_verify_materials_rejects_remove_and_undone(): void
    {
        $service = app(JobWebCompletionService::class);

        $undone = new JobSchedule(['type' => 'service', 'status' => 'undone']);
        $resUndone = $service->verifyMaterials($undone, null);
        $this->assertFalse($resUndone['ok']);
        $this->assertSame(423, $resUndone['code']);

        foreach (['remove', 'remove_free', 'remove free'] as $type) {
            $job = new JobSchedule(['type' => $type, 'status' => 'barang_siap_diambil']);
            $res = $service->verifyMaterials($job, null);

            $this->assertFalse($res['ok'], "Expected verifyMaterials to reject type '{$type}'");
            $this->assertSame(422, $res['code'], "Wrong code for type '{$type}'");
        }
    }

    /**
     * Phase 3: saveScannedUnit must reject undone jobs before any DB write.
     */
    public function test_save_scanned_unit_rejects_undone(): void
    {
        $service = app(JobWebCompletionService::class);

        $undone = new JobSchedule(['type' => 'service', 'status' => 'undone']);
        $undone->id = 999999;
        $res = $service->saveScannedUnit($undone, 1, 'AA:BB', 'SmartScent', null, null, null, null);

        $this->assertFalse($res['ok']);
        $this->assertSame(423, $res['code']);
        $this->assertNull($res['job_schedule_unit_id']);
    }

    private function validateWebCompletionTransition(JobSchedule $job, string $target)
    {
        $method = new ReflectionMethod(JobScheduleController::class, 'validateWebCompletionTransition');
        $method->setAccessible(true);

        return $method->invoke(app(JobScheduleController::class), $job, $target);
    }
}
