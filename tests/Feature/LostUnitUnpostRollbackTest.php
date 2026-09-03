<?php

namespace Tests\Feature;

use App\Http\Controllers\Marketing\LostUnitReportController;
use App\Models\JobSchedule;
use App\Models\LostUnitReport;
use ReflectionMethod;
use Tests\TestCase;

/**
 * QA, 30 Aug 2026: a lost unit report was unposted and approved again, and the same lost unit
 * then appeared twice in the Job Advice and Job Schedule lists. The serial number of the lost
 * unit also stayed "Hilang" after the unpost, when it should have gone back to In Customer.
 *
 * Unposting only flipped the status back to draft - it undid nothing that approving had done.
 * The pieces the rollback is built on are covered here:
 *
 * 1. The loss marker written onto the unit and serial notes on approval is the only trace
 *    linking them back to the report, so it has to be written and matched identically, and
 *    stripped cleanly on the way back without eating notes somebody else wrote.
 * 2. A replacement job may only be withdrawn while it is still untouched. One a technician has
 *    been assigned to - or finished - blocks the unpost instead of being deleted.
 */
class LostUnitUnpostRollbackTest extends TestCase
{
    private function invokeControllerMethod(string $method, array $args)
    {
        $reflection = new ReflectionMethod(LostUnitReportController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(new LostUnitReportController, ...$args);
    }

    private function report(string $reportNumber = 'SBY-RPT/26-08/0002'): LostUnitReport
    {
        return new LostUnitReport(['report_number' => $reportNumber]);
    }

    private function schedule(?string $status, ?string $jobNumber): JobSchedule
    {
        $schedule = new JobSchedule;
        $schedule->status = $status;
        $schedule->job_number = $jobNumber;

        return $schedule;
    }

    private function isWithdrawable(?string $status, ?string $jobNumber): bool
    {
        return $this->invokeControllerMethod(
            'replacementJobIsWithdrawable',
            [$this->schedule($status, $jobNumber)]
        );
    }

    public function test_the_marker_written_on_approval_is_the_one_the_rollback_looks_for(): void
    {
        $report = $this->report();
        $marker = $this->invokeControllerMethod('lossMarker', [$report]);

        $this->assertSame('Dilaporkan hilang pada SBY-RPT/26-08/0002.', $marker);
        $this->assertStringContainsString(
            $marker,
            $this->invokeControllerMethod('appendLossMarker', [null, $report])
        );
    }

    public function test_unposting_removes_the_marker_but_keeps_the_notes_around_it(): void
    {
        $report = $this->report();
        $marker = $this->invokeControllerMethod('lossMarker', [$report]);

        $notes = $this->invokeControllerMethod('appendLossMarker', ['Unit dipasang 12/08/2026.', $report]);
        $restored = $this->invokeControllerMethod('stripLossMarker', [$notes, $marker]);

        $this->assertSame('Unit dipasang 12/08/2026.', $restored);
    }

    public function test_a_note_that_only_held_the_marker_comes_back_empty(): void
    {
        $report = $this->report();
        $marker = $this->invokeControllerMethod('lossMarker', [$report]);

        $notes = $this->invokeControllerMethod('appendLossMarker', [null, $report]);

        $this->assertNull($this->invokeControllerMethod('stripLossMarker', [$notes, $marker]));
    }

    public function test_another_reports_marker_is_left_alone(): void
    {
        $marker = $this->invokeControllerMethod('lossMarker', [$this->report()]);
        $otherNote = $this->invokeControllerMethod(
            'appendLossMarker',
            [null, $this->report('SBY-RPT/26-08/0009')]
        );

        $this->assertSame($otherNote, $this->invokeControllerMethod('stripLossMarker', [$otherNote, $marker]));
    }

    public function test_an_unassigned_replacement_job_can_be_withdrawn(): void
    {
        $this->assertTrue($this->isWithdrawable('new_job', null));
        $this->assertTrue($this->isWithdrawable('scheduled', ''));
    }

    public function test_a_replacement_job_already_numbered_or_worked_on_blocks_the_unpost(): void
    {
        $this->assertFalse($this->isWithdrawable('new_job', 'SBY-IR/26-08/0014'));
        $this->assertFalse($this->isWithdrawable('assign_team', null));
        $this->assertFalse($this->isWithdrawable('teknisi_sedang_pengerjaan', 'SBY-IR/26-08/0014'));
        $this->assertFalse($this->isWithdrawable('done_job', 'SBY-IR/26-08/0014'));
    }
}
