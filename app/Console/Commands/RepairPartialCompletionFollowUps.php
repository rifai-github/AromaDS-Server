<?php

namespace App\Console\Commands;

use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\JobScheduleRoomRental;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairPartialCompletionFollowUps extends Command
{
    protected $signature = 'jobs:repair-partial-followups {jobNumber : Original grouped job number} {--apply : Persist the repair}';

    protected $description = 'Repair partial mobile verification follow-up jobs and cancelled room statuses for a grouped job number.';

    public function handle(): int
    {
        $jobNumber = (string) $this->argument('jobNumber');
        $apply = (bool) $this->option('apply');

        $sourceJobs = JobSchedule::with(['jobScheduleRooms.rentals'])
            ->where('job_number', $jobNumber)
            ->whereNotIn('type', ['remove', 'remove_free', 'remove free'])
            ->orderBy('id')
            ->get();

        if ($sourceJobs->isEmpty()) {
            $this->error("No job schedules found for {$jobNumber}.");
            return self::FAILURE;
        }

        $this->info(($apply ? 'APPLY' : 'DRY RUN') . " repair for {$jobNumber}");

        DB::transaction(function () use ($sourceJobs, $jobNumber, $apply) {
            foreach ($sourceJobs as $sourceJob) {
                $rooms = $sourceJob->jobScheduleRooms
                    ->filter(function ($room) {
                        // Bug #28 (QA, live case job_schedule 198 "SBY-IR/26-06/0018"):
                        // this command only excluded CANCELLED rooms, so a room that
                        // had already been finished (status COMPLETED, e.g. "Ruang
                        // Meeting VIP" completed via mobile) was still treated as
                        // "needs a follow-up" and dragged into outstanding alongside
                        // the genuinely unfinished room ("Toilet VIP"). Mirror
                        // JobWebCompletionService::handlePartialCompletion(), which
                        // correctly skips both COMPLETED and CANCELLED rooms.
                        if ($room->status === JobScheduleRoom::STATUS_COMPLETED) {
                            return false;
                        }

                        return $room->status !== JobScheduleRoom::STATUS_CANCELLED
                            || str_contains((string) $room->notes, 'Pekerjaan tidak selesai');
                    })
                    ->values();

                foreach ($rooms as $room) {
                    $newJob = $this->findExistingFollowUp($sourceJob, $room);
                    $action = $newJob ? 'reuse' : 'create';

                    $this->line(sprintf(
                        '- source job #%d room #%d "%s": %s follow-up, old room %s => cancelled',
                        $sourceJob->id,
                        $room->id,
                        $room->room_name,
                        $action,
                        $room->status
                    ));

                    if (!$apply) {
                        continue;
                    }

                    if (!$newJob) {
                        $newJob = $this->createFollowUpJob($sourceJob, $room);
                    } else {
                        $this->syncFollowUpScheduleContext($sourceJob, $newJob);
                    }

                    $newRoom = JobScheduleRoom::firstOrCreate(
                        [
                            'job_schedule_id' => $newJob->id,
                            'job_advice_room_id' => $room->job_advice_room_id,
                        ],
                        [
                            'room_name' => $room->room_name,
                            'room_id' => $room->room_id,
                            'status' => JobScheduleRoom::STATUS_PENDING,
                            'material_return_status' => JobScheduleRoom::MATERIAL_RETURN_NOT_REQUIRED,
                            'notes' => "Pindahan dari Job {$jobNumber}",
                            'created_by' => $this->actorId($sourceJob),
                            'updated_by' => $this->actorId($sourceJob),
                        ]
                    );

                    $this->syncRentals($room, $newRoom);

                    $room->update([
                        'status' => JobScheduleRoom::STATUS_CANCELLED,
                        'notes' => 'Pekerjaan tidak selesai, dipindahkan ke Job baru.',
                        'updated_by' => $this->actorId($sourceJob),
                    ]);
                }

                if ($apply) {
                    $sourceJob->update([
                        'status' => 'meninggalkan_lokasi',
                        'completed_at' => null,
                        'updated_by' => $this->actorId($sourceJob),
                    ]);
                }
            }
        });

        if (!$apply) {
            $this->warn('Dry run only. Re-run with --apply to persist changes.');
        }

        return self::SUCCESS;
    }

    private function findExistingFollowUp(JobSchedule $sourceJob, JobScheduleRoom $room): ?JobSchedule
    {
        $jobAdviceRoomIds = $this->jobAdviceRoomIds($room);

        return JobSchedule::where('job_advice_id', $sourceJob->job_advice_id)
            ->where('building_id', $sourceJob->building_id)
            ->where('type', $sourceJob->type)
            ->where('internal_notes', 'like', "Lanjutan dari Job {$sourceJob->job_number}%")
            ->whereNotIn('status', ['cancelled', 'done_job', 'completed', 'selesai'])
            ->whereHas('jobScheduleRooms', function ($query) use ($jobAdviceRoomIds) {
                $query->whereIn('job_advice_room_id', $jobAdviceRoomIds)
                    ->orWhereHas('rentals', function ($rentalQuery) use ($jobAdviceRoomIds) {
                        $rentalQuery->whereIn('job_advice_room_id', $jobAdviceRoomIds);
                    });
            })
            ->latest('id')
            ->first();
    }

    private function createFollowUpJob(JobSchedule $sourceJob, JobScheduleRoom $room): JobSchedule
    {
        $newJob = new JobSchedule();
        if (Schema::hasColumn('job_schedules', 'customer_id')) {
            $newJob->customer_id = $sourceJob->customer_id;
        }
        $newJob->building_id = $sourceJob->building_id;
        $newJob->building_name = $sourceJob->building_name;
        $newJob->company_name = $sourceJob->company_name;
        $newJob->job_advice_id = $sourceJob->job_advice_id;
        $newJob->contract_number = $sourceJob->contract_number;
        $newJob->quotation_number = $sourceJob->quotation_number;
        $newJob->type = $sourceJob->type;
        $newJob->status = 'new_job';
        $newJob->schedule_date = now()->toDateString();
        $newJob->expected_date = $sourceJob->expected_date;
        $newJob->job_number = null;
        $newJob->internal_notes = "Lanjutan dari Job {$sourceJob->job_number} (Pekerjaan tidak selesai). Room: {$room->room_name}.";
        $newJob->created_by = $this->actorId($sourceJob);
        $newJob->updated_by = $this->actorId($sourceJob);
        $this->syncFollowUpScheduleContext($sourceJob, $newJob, false);
        $newJob->save();

        return $newJob;
    }

    private function syncFollowUpScheduleContext(JobSchedule $sourceJob, JobSchedule $followUpJob, bool $save = true): void
    {
        foreach ([
            'period',
            'service_frequency',
            'service_period_type',
            'service_interval_days',
            'next_service_date',
            'reference_number',
            'job_reference_number',
            'day',
            'material_checked',
            'material_checked_at',
        ] as $column) {
            if (Schema::hasColumn('job_schedules', $column)) {
                $followUpJob->{$column} = $sourceJob->{$column};
            }
        }

        if ($save && $followUpJob->isDirty()) {
            $followUpJob->updated_by = $this->actorId($sourceJob);
            $followUpJob->save();
        }
    }

    private function syncRentals(JobScheduleRoom $sourceRoom, JobScheduleRoom $targetRoom): void
    {
        $sourceRoom->loadMissing('rentals');
        $rentals = $sourceRoom->rentals;

        if ($rentals->isEmpty() && $sourceRoom->job_advice_room_id) {
            $rentals = collect([(object) [
                'job_advice_room_id' => $sourceRoom->job_advice_room_id,
                'is_primary' => true,
            ]]);
        }

        foreach ($rentals as $rental) {
            $link = JobScheduleRoomRental::withTrashed()->firstOrNew([
                'job_schedule_room_id' => $targetRoom->id,
                'job_advice_room_id' => $rental->job_advice_room_id,
            ]);
            $link->is_primary = (bool) $rental->is_primary;
            $link->save();

            if (method_exists($link, 'trashed') && $link->trashed()) {
                $link->restore();
            }
        }
    }

    private function jobAdviceRoomIds(JobScheduleRoom $room): array
    {
        $room->loadMissing('rentals');

        return $room->rentals
            ->pluck('job_advice_room_id')
            ->push($room->job_advice_room_id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function actorId(JobSchedule $sourceJob): ?int
    {
        return auth()->id() ?: $sourceJob->updated_by ?: $sourceJob->created_by;
    }
}
