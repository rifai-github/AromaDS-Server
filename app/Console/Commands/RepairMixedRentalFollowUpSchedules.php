<?php

namespace App\Console\Commands;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoomRental;
use App\Services\DocumentNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RepairMixedRentalFollowUpSchedules extends Command
{
    protected $signature = 'operational:repair-mixed-rental-follow-up-schedules
        {--job-advice=* : Specific Job Advice number, repeatable}
        {--contract-number=* : Specific contract number, repeatable}
        {--apply : Apply the repair. Default is dry-run}';

    protected $description = 'Generate missing Unit Only checks and Refill/Unit+Refill CSR periods, then repair old mixed-rental check scope and numbering';

    private const COMPLETED_STATUSES = ['completed', 'done_job', 'dpf', 'selesai'];

    private const TERMINAL_STATUSES = ['completed', 'done_job', 'dpf', 'selesai', 'cancelled', 'suspend', 'undone'];

    private const MATERIAL_STATUSES = ['assign_material', 'barang_dipersiapkan', 'material_issue'];

    private const SERVICE_TYPES = ['service', 'service_first', 'service_routine'];

    public function handle(JobScheduleController $jobScheduleController, DocumentNumberService $documentNumberService): int
    {
        $jobAdviceNumbers = collect($this->option('job-advice'))->filter()->map(fn ($value) => trim((string) $value))->values();
        $contractNumbers = collect($this->option('contract-number'))->filter()->map(fn ($value) => trim((string) $value))->values();
        $apply = (bool) $this->option('apply');

        if ($jobAdviceNumbers->isEmpty() && $contractNumbers->isEmpty()) {
            $this->error('Use --job-advice or --contract-number. This repair intentionally does not run without a specific target.');

            return self::FAILURE;
        }

        if (! $apply) {
            $this->warn('DRY RUN mode. No database changes will be made. Re-run with --apply to persist repairs.');
        }

        $jobAdvices = JobAdvice::query()
            ->with([
                'contract',
                'rooms.rentalProduct.serviceFrequency',
                'rooms.contractRoom.room',
                'rooms.quotationRoom.room',
            ])
            ->when($jobAdviceNumbers->isNotEmpty(), fn ($query) => $query->whereIn('job_advice_number', $jobAdviceNumbers->all()))
            ->when($contractNumbers->isNotEmpty(), function ($query) use ($contractNumbers) {
                $query->whereHas('contract', fn ($contractQuery) => $contractQuery->whereIn('contract_number', $contractNumbers->all()));
            })
            ->orderBy('id')
            ->get();

        $rows = [];
        $stats = ['scanned' => 0, 'generated' => 0, 'scope' => 0, 'renumbered' => 0, 'status' => 0, 'skipped' => 0];

        foreach ($jobAdvices as $jobAdvice) {
            $stats['scanned']++;
            $groups = $this->mixedPhysicalRoomGroups($jobAdvice);

            if ($groups->isEmpty()) {
                $stats['skipped']++;
                $rows[] = ['SKIP', $jobAdvice->job_advice_number, '-', '-', 'Job Advice does not contain Unit Only plus service/refill rental in the same room'];

                continue;
            }

            $completedInstall = $this->findCompletedInstall($jobAdvice);
            $completedFirstService = $this->findCompletedFirstService($jobAdvice, $groups);

            if ($completedInstall) {
                $rows[] = [$apply ? 'RUN' : 'PLAN', $jobAdvice->job_advice_number, $completedInstall->job_number ?: "#{$completedInstall->id}", 'Check', 'generate missing Unit Only check periods idempotently'];
            } else {
                $rows[] = ['SKIP', $jobAdvice->job_advice_number, '-', 'Check', 'completed install job was not found'];
                $stats['skipped']++;
            }

            if ($completedFirstService) {
                $rows[] = [$apply ? 'RUN' : 'PLAN', $jobAdvice->job_advice_number, $completedFirstService->job_number ?: "#{$completedFirstService->id}", 'CSR', 'generate missing Refill/Unit+Refill service periods idempotently'];
            } else {
                $rows[] = ['SKIP', $jobAdvice->job_advice_number, '-', 'CSR', 'completed first CSR/service job was not found'];
                $stats['skipped']++;
            }

            if ($apply) {
                DB::transaction(function () use (
                    $jobScheduleController,
                    $jobAdvice,
                    $completedInstall,
                    $completedFirstService,
                    $groups,
                    $documentNumberService,
                    &$stats,
                    &$rows
                ) {
                    $beforeCount = JobSchedule::where('job_advice_id', $jobAdvice->id)->count();

                    if ($completedInstall) {
                        $jobScheduleController->generateUnitOnlyCheckSchedulesAfterInstall($completedInstall->fresh(), $jobAdvice->fresh());
                    }

                    if ($completedFirstService) {
                        $jobScheduleController->generateAllRemainingServices($completedFirstService->fresh(), $jobAdvice->fresh());
                    }

                    $afterCount = JobSchedule::where('job_advice_id', $jobAdvice->id)->count();
                    $stats['generated'] += max(0, $afterCount - $beforeCount);

                    $this->repairActiveScheduleScopes($jobAdvice->fresh(), $groups, $rows, $stats);
                    $this->repairActiveUnitOnlyCheckNumbersAndStatuses($jobAdvice->fresh(), $documentNumberService, $rows, $stats);
                });
            } else {
                $this->previewActiveScheduleRepairs($jobAdvice, $groups, $rows);
            }
        }

        $this->table(['Status', 'Job Advice', 'Job', 'Flow', 'Note'], $rows);
        $this->line('Scanned Job Advices : '.$stats['scanned']);
        $this->line('Generated schedules : '.($apply ? $stats['generated'] : 'dry-run'));
        $this->line('Repaired scopes     : '.($apply ? $stats['scope'] : 'dry-run'));
        $this->line('Renumbered checks   : '.($apply ? $stats['renumbered'] : 'dry-run'));
        $this->line('Repaired statuses   : '.($apply ? $stats['status'] : 'dry-run'));
        $this->line('Skipped             : '.$stats['skipped']);

        if (! $apply) {
            $this->line('Dry run only. Re-run with --apply after reviewing PLAN rows.');
        }

        return self::SUCCESS;
    }

    private function mixedPhysicalRoomGroups(JobAdvice $jobAdvice)
    {
        return $jobAdvice->rooms
            ->groupBy(fn ($room) => $this->physicalRoomKey($room))
            ->filter(function ($rooms) {
                $types = $rooms->map(fn ($room) => $this->rentalType($room))->filter();

                return $types->contains('unit_only')
                    && $types->contains(fn ($type) => in_array($type, ['refill_only', 'unit_refill'], true));
            });
    }

    private function findCompletedInstall(JobAdvice $jobAdvice): ?JobSchedule
    {
        return JobSchedule::query()
            ->where('job_advice_id', $jobAdvice->id)
            ->whereIn(DB::raw("LOWER(COALESCE(type, ''))"), ['install', 'installation', 'installation_report'])
            ->whereIn('status', self::COMPLETED_STATUSES)
            ->orderBy('schedule_date')
            ->orderBy('id')
            ->first();
    }

    private function findCompletedFirstService(JobAdvice $jobAdvice, $groups): ?JobSchedule
    {
        $serviceRoomIds = $groups
            ->flatten()
            ->filter(fn ($room) => in_array($this->rentalType($room), ['refill_only', 'unit_refill'], true))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return JobSchedule::query()
            ->where('job_advice_id', $jobAdvice->id)
            ->whereIn(DB::raw("LOWER(COALESCE(type, ''))"), self::SERVICE_TYPES)
            ->whereIn('status', self::COMPLETED_STATUSES)
            ->where(function ($query) {
                $query->whereNull('period')->orWhere('period', 1);
            })
            ->where(function ($query) use ($serviceRoomIds) {
                $query->whereHas('jobScheduleRooms.rentals', fn ($rentalQuery) => $rentalQuery->whereIn('job_advice_room_id', $serviceRoomIds->all()))
                    ->orWhereHas('jobAdvice.rooms', function ($roomQuery) use ($serviceRoomIds) {
                        $roomQuery->whereIn('id', $serviceRoomIds->all())
                            ->whereColumn('job_advice_rooms.service_job_schedule_id', 'job_schedules.id');
                    });
            })
            ->orderBy('schedule_date')
            ->orderBy('id')
            ->first();
    }

    private function previewActiveScheduleRepairs(JobAdvice $jobAdvice, $groups, array &$rows): void
    {
        foreach ($this->activeServiceSchedules($jobAdvice) as $schedule) {
            if (! $this->scheduleIsUnitOnlyCheck($schedule)) {
                continue;
            }

            if ($schedule->job_number && ! Str::contains(Str::upper($schedule->job_number), '-IR/')) {
                $rows[] = ['PLAN', $jobAdvice->job_advice_number, $schedule->job_number, 'Check', 'replace incorrect non-IR job number when no dependent transaction exists'];
            }

            if (in_array((string) $schedule->status, self::MATERIAL_STATUSES, true) && ! $this->hasMaterialIssue($schedule)) {
                $rows[] = ['PLAN', $jobAdvice->job_advice_number, $schedule->job_number ?: "#{$schedule->id}", 'Check', 'restore material status to assigned/scheduled because Unit Only check has no material issue'];
            }

            foreach ($schedule->jobScheduleRooms as $scheduleRoom) {
                $group = $this->groupForScheduleRoom($groups, $scheduleRoom);
                if (! $group) {
                    continue;
                }

                $desiredIds = $group->filter(fn ($room) => $this->rentalType($room) === 'unit_only')->pluck('id')->map(fn ($id) => (int) $id);
                $currentIds = $scheduleRoom->rentals->pluck('job_advice_room_id')->map(fn ($id) => (int) $id);

                if ($currentIds->sort()->values()->all() !== $desiredIds->sort()->values()->all()) {
                    $rows[] = ['PLAN', $jobAdvice->job_advice_number, $schedule->job_number ?: "#{$schedule->id}", 'Check', 'scope rental links to Unit Only only'];
                }
            }
        }
    }

    private function repairActiveScheduleScopes(JobAdvice $jobAdvice, $groups, array &$rows, array &$stats): void
    {
        foreach ($this->activeServiceSchedules($jobAdvice) as $schedule) {
            $flow = $this->scheduleIsUnitOnlyCheck($schedule)
                ? 'Check'
                : (Str::contains(Str::lower((string) $schedule->internal_notes), 'service period') ? 'CSR' : null);

            if (! $flow) {
                continue;
            }

            foreach ($schedule->jobScheduleRooms as $scheduleRoom) {
                $group = $this->groupForScheduleRoom($groups, $scheduleRoom);
                if (! $group) {
                    continue;
                }

                $desiredRooms = $flow === 'Check'
                    ? $group->filter(fn ($room) => $this->rentalType($room) === 'unit_only')
                    : $group->filter(fn ($room) => in_array($this->rentalType($room), ['refill_only', 'unit_refill'], true));
                $desiredIds = $desiredRooms->pluck('id')->map(fn ($id) => (int) $id)->values();
                $currentIds = $scheduleRoom->rentals->pluck('job_advice_room_id')->map(fn ($id) => (int) $id)->values();

                if ($currentIds->sort()->values()->all() === $desiredIds->sort()->values()->all() || $desiredIds->isEmpty()) {
                    continue;
                }

                JobScheduleRoomRental::where('job_schedule_room_id', $scheduleRoom->id)
                    ->whereNotIn('job_advice_room_id', $desiredIds->all())
                    ->delete();

                foreach ($desiredIds as $index => $jobAdviceRoomId) {
                    $link = JobScheduleRoomRental::withTrashed()->firstOrNew([
                        'job_schedule_room_id' => $scheduleRoom->id,
                        'job_advice_room_id' => $jobAdviceRoomId,
                    ]);
                    $link->is_primary = $index === 0;
                    $link->save();
                    if ($link->trashed()) {
                        $link->restore();
                    }
                }

                $scheduleRoom->update([
                    'job_advice_room_id' => $desiredIds->first(),
                    'updated_by' => auth()->id(),
                ]);
                $stats['scope']++;
                $rows[] = ['FIXED', $jobAdvice->job_advice_number, $schedule->job_number ?: "#{$schedule->id}", $flow, "rental scope repaired to {$desiredIds->count()} eligible rental(s)"];
            }
        }
    }

    private function repairActiveUnitOnlyCheckNumbersAndStatuses(JobAdvice $jobAdvice, DocumentNumberService $documentNumberService, array &$rows, array &$stats): void
    {
        foreach ($this->activeServiceSchedules($jobAdvice) as $schedule) {
            if (! $this->scheduleIsUnitOnlyCheck($schedule)) {
                continue;
            }

            $hasMaterialIssue = $this->hasMaterialIssue($schedule);

            if (in_array((string) $schedule->status, self::MATERIAL_STATUSES, true) && ! $hasMaterialIssue) {
                $hasAssignment = $schedule->jobAssignSchedules()->where('status', '!=', 'cancelled')->exists();
                $schedule->update([
                    'status' => $hasAssignment ? 'assign_team' : 'scheduled',
                    'material_checked' => true,
                    'material_checked_at' => $schedule->material_checked_at ?: now(),
                    'updated_by' => auth()->id(),
                ]);
                $stats['status']++;
                $rows[] = ['FIXED', $jobAdvice->job_advice_number, $schedule->job_number ?: "#{$schedule->id}", 'Check', 'material status restored because Unit Only check has no material issue'];
            }

            if (! $schedule->job_number || Str::contains(Str::upper($schedule->job_number), '-IR/')) {
                continue;
            }

            $reason = $this->renumberBlockReason($schedule);
            if ($reason) {
                $stats['skipped']++;
                $rows[] = ['SKIP', $jobAdvice->job_advice_number, $schedule->job_number, 'Check', "job number was not changed: {$reason}"];

                continue;
            }

            $oldJobNumber = $schedule->job_number;
            $newJobNumber = $documentNumberService->generate(
                'installation_report',
                null,
                $schedule->building_id,
                $jobAdvice->contract_id,
                $jobAdvice->quotation_id,
                null,
                null,
                null,
                $schedule->schedule_date
            );

            $schedule->update(['job_number' => $newJobNumber, 'updated_by' => auth()->id()]);

            if (
                Schema::hasTable('job_assignments')
                && JobSchedule::where('job_number', $oldJobNumber)->where('id', '!=', $schedule->id)->doesntExist()
            ) {
                DB::table('job_assignments')->where('job_number', $oldJobNumber)->update([
                    'job_number' => $newJobNumber,
                    'updated_at' => now(),
                ]);
            }

            $stats['renumbered']++;
            $rows[] = ['FIXED', $jobAdvice->job_advice_number, $newJobNumber, 'Check', "incorrect {$oldJobNumber} replaced with IR number"];
        }
    }

    private function activeServiceSchedules(JobAdvice $jobAdvice)
    {
        return JobSchedule::query()
            ->with(['jobScheduleRooms.rentals.jobAdviceRoom.rentalProduct', 'jobAssignSchedules.jobAssignMaterialIssues'])
            ->where('job_advice_id', $jobAdvice->id)
            ->whereIn(DB::raw("LOWER(COALESCE(type, ''))"), self::SERVICE_TYPES)
            ->whereNotIn('status', self::TERMINAL_STATUSES)
            ->orderBy('period')
            ->orderBy('id')
            ->get();
    }

    private function scheduleIsUnitOnlyCheck(JobSchedule $schedule): bool
    {
        if (Str::contains(Str::lower((string) $schedule->internal_notes), 'check period')) {
            return true;
        }

        $types = $schedule->jobScheduleRooms
            ->flatMap(fn ($room) => $room->rentals)
            ->map(fn ($link) => strtolower(trim(str_replace('-', '_', (string) $link->jobAdviceRoom?->rentalProduct?->rental_type))))
            ->filter()
            ->unique();

        return $types->isNotEmpty() && $types->every(fn ($type) => $type === 'unit_only');
    }

    private function hasMaterialIssue(JobSchedule $schedule): bool
    {
        return $schedule->jobAssignSchedules
            ->flatMap(fn ($assignment) => $assignment->jobAssignMaterialIssues)
            ->isNotEmpty();
    }

    private function renumberBlockReason(JobSchedule $schedule): ?string
    {
        if ($this->hasMaterialIssue($schedule)) {
            return 'material issue already exists';
        }

        $checks = [
            ['inventory_issuings', 'reference_no', $schedule->job_number, 'inventory issuing already exists'],
            ['inventory_receivings', 'reference_no', $schedule->job_number, 'inventory receiving already exists'],
            ['invoice_rental_details', 'job_no', $schedule->job_number, 'invoice rental detail already exists'],
            ['job_reports', 'job_schedule_id', $schedule->id, 'job report already exists'],
            ['job_schedule_ba_files', 'job_schedule_id', $schedule->id, 'BA file already exists'],
        ];

        foreach ($checks as [$table, $column, $value, $reason]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && DB::table($table)->where($column, $value)->exists()) {
                return $reason;
            }
        }

        return null;
    }

    private function groupForScheduleRoom($groups, $scheduleRoom)
    {
        return $groups->first(function ($group) use ($scheduleRoom) {
            return $group->contains(function ($jobAdviceRoom) use ($scheduleRoom) {
                $roomId = $jobAdviceRoom->contractRoom?->room_id
                    ?? $jobAdviceRoom->quotationRoom?->room_id
                    ?? null;

                return $scheduleRoom->room_id && (int) $roomId === (int) $scheduleRoom->room_id;
            });
        });
    }

    private function physicalRoomKey($room): string
    {
        $roomId = $room->contractRoom?->room_id ?? $room->quotationRoom?->room_id ?? null;

        return $roomId ? "room:{$roomId}" : 'name:'.strtolower(trim((string) $room->room_name));
    }

    private function rentalType($room): string
    {
        return strtolower(trim(str_replace('-', '_', (string) $room->rentalProduct?->rental_type)));
    }
}
