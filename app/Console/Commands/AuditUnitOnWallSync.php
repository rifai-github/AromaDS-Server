<?php

namespace App\Console\Commands;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobSchedule;
use App\Models\SerialNumber;
use App\Models\UnitOnWall;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ReflectionClass;

/**
 * SerialNumber.status flips eagerly at scan/room-completion time; UnitOnWall
 * only updates once a job reaches done_job (autoCreateUnitOnWall() /
 * autoRemoveUnitOnWall() in JobScheduleController). A job that never gets
 * verified, or a completion automation call that silently no-ops, leaves the
 * two permanently disagreeing. This surfaces both directions without ever
 * fabricating a UnitOnWall row from unverified data: --apply only re-runs the
 * same trusted automation, and only for jobs that already reached done_job.
 */
class AuditUnitOnWallSync extends Command
{
    protected $signature = 'warehouse:audit-unit-on-wall-sync {--apply : Re-run completion automation for jobs that already reached done_job}';

    protected $description = 'Audit SerialNumber vs UnitOnWall divergence (installed units with no wall record, wall records whose unit already moved on).';

    private const DONE_JOB_STATUSES = ['completed', 'done_job', 'selesai'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->auditInstalledWithoutWallRecord($apply);
        $this->newLine();
        $this->auditWallRecordsWhoseUnitMovedOn($apply);

        return self::SUCCESS;
    }

    /**
     * SN status=in_use on a unique-serial (is_unit) product with no matching
     * UnitOnWall row at all.
     */
    private function auditInstalledWithoutWallRecord(bool $apply): void
    {
        $rows = SerialNumber::query()
            ->where('status', 'in_use')
            ->whereDoesntHave('unitOnWalls')
            ->with('masterProduct.productCategory', 'masterProduct.productType')
            ->get()
            ->filter(fn (SerialNumber $sn) => $sn->masterProduct?->requiresUniqueSerialNumber());

        if ($rows->isEmpty()) {
            $this->info('No installed unit-type serials missing a Unit On Wall record.');

            return;
        }

        $table = [];
        foreach ($rows as $sn) {
            $job = $this->findJobForScannedSerial($sn->serial_number, installSide: true);
            $table[] = [
                $sn->serial_number,
                $sn->masterProduct?->name,
                $job?->job_number ?? '-',
                $job?->status ?? '-',
                $this->resolveAction($apply, $job, fn (JobSchedule $j) => $this->invokeAutoCreateUnitOnWall($j)),
            ];
        }

        $this->warn(sprintf('%d installed unit-type serial(s) with no Unit On Wall record:', count($table)));
        $this->table(['Serial', 'Product', 'Job', 'Job Status', 'Action'], $table);
    }

    /**
     * UnitOnWall rows still 'active' whose linked SerialNumber already moved
     * off in_use (on_hand_remove, retired, ready, ...) — the remove job
     * completed (or the SN was otherwise moved) but the wall record was never
     * flipped to 'removed'.
     */
    private function auditWallRecordsWhoseUnitMovedOn(bool $apply): void
    {
        $rows = UnitOnWall::query()
            ->where('status', 'active')
            ->whereHas('serialNumber', fn ($q) => $q->where('status', '!=', 'in_use'))
            ->with('serialNumber')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No Unit On Wall record disagrees with its serial number status.');

            return;
        }

        $table = [];
        foreach ($rows as $unit) {
            $sn = $unit->serialNumber;
            $job = $sn ? $this->findJobForScannedSerial($sn->serial_number, installSide: false) : null;
            $table[] = [
                $sn?->serial_number ?? '-',
                $unit->room_name,
                $unit->company_name,
                $sn?->status ?? '-',
                $job?->job_number ?? '-',
                $this->resolveAction($apply, $job, fn (JobSchedule $j) => $this->invokeAutoRemoveUnitOnWall($j) > 0),
            ];
        }

        $this->warn(sprintf('%d active Unit On Wall record(s) whose serial number already moved on:', count($table)));
        $this->table(['Serial', 'Room', 'Customer', 'SN Status', 'Remove Job', 'Action'], $table);
    }

    /**
     * @param  callable(JobSchedule):bool  $apply_fn
     */
    private function resolveAction(bool $apply, ?JobSchedule $job, callable $applyFn): string
    {
        if (! $job) {
            return 'no job found for this serial — needs manual investigation';
        }

        if (! in_array(strtolower((string) $job->status), self::DONE_JOB_STATUSES, true)) {
            return "job not verified yet (status={$job->status}) — will not touch";
        }

        if (! $apply) {
            return 'job already done_job — ready, re-run with --apply';
        }

        return $applyFn($job) ? 'applied' : 'apply ran but made no change';
    }

    private function findJobForScannedSerial(string $serialCode, bool $installSide): ?JobSchedule
    {
        $types = $installSide
            ? ['install', 'ir', 'install_free', 'install free', 'if']
            : ['remove', 'rv', 'remove_free', 'remove free', 'rf'];

        $candidateJobIds = DB::table('job_schedule_units')
            ->whereRaw('TRIM(mac) = ?', [trim($serialCode)])
            ->orderByDesc('scanned_at')
            ->pluck('job_schedule_id');

        foreach ($candidateJobIds as $id) {
            $job = JobSchedule::find($id);
            if ($job && in_array(strtolower((string) $job->type), $types, true)) {
                return $job;
            }
        }

        return null;
    }

    private function invokeAutoCreateUnitOnWall(JobSchedule $job): bool
    {
        $job->loadMissing('jobAdvice');
        if (! $job->jobAdvice) {
            return false;
        }

        return (bool) $this->invokePrivate($job, 'autoCreateUnitOnWall');
    }

    private function invokeAutoRemoveUnitOnWall(JobSchedule $job): int
    {
        $job->loadMissing('jobAdvice');
        if (! $job->jobAdvice) {
            return 0;
        }

        return (int) $this->invokePrivate($job, 'autoRemoveUnitOnWall');
    }

    private function invokePrivate(JobSchedule $job, string $methodName)
    {
        $controller = new JobScheduleController;
        $method = (new ReflectionClass($controller))->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invoke($controller, $job, $job->jobAdvice);
    }
}
