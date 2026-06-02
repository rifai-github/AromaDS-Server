<?php

namespace App\Console\Commands;

use App\Models\JobSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairOrphanRemoveJobAdviceLink extends Command
{
    protected $signature = 'mobile:repair-orphan-remove-job-advice
                            {--job-no= : Specific remove job number}
                            {--job-id= : Specific remove job schedule ID}
                            {--job-advice-id= : Force a specific Job Advice ID when auto-detection is ambiguous}
                            {--apply : Apply the repair (default is dry-run)}
                            {--limit=100 : Limit rows when scanning without job-no/job-id}';

    protected $description = 'Repair remove/RV jobs that are hidden from the mobile app because job_advice_id is empty';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $forcedJobAdviceId = $this->option('job-advice-id') ? (int) $this->option('job-advice-id') : null;

        if (!$apply) {
            $this->info('DRY RUN mode. No database changes will be made. Re-run with --apply to persist repairs.');
        }

        $jobs = $this->loadJobs();
        if ($jobs->isEmpty()) {
            $this->warn('No orphan remove/RV jobs found.');

            return self::SUCCESS;
        }

        $rows = [];
        $plans = [];
        $skipped = 0;

        foreach ($jobs as $job) {
            $analysis = $this->analyzeJob($job, $forcedJobAdviceId);

            if ($analysis['repairable']) {
                $plans[] = [$job, $analysis];
            } else {
                $skipped++;
            }

            $rows[] = [
                $analysis['repairable'] ? ($apply ? 'FIX' : 'PLAN') : 'SKIP',
                $job->id,
                $job->job_number,
                $job->type,
                $job->status,
                $job->getRawOriginal('room_name') ?: ($job->room?->room_name ?? '-'),
                $analysis['target_job_advice_id'] ?: '-',
                $analysis['target_job_advice_no'] ?: '-',
                $analysis['target_room'] ?: '-',
                $analysis['note'],
            ];
        }

        $applied = 0;
        if ($apply) {
            foreach ($plans as [$job, $analysis]) {
                DB::transaction(function () use ($job, $analysis, &$applied) {
                    $this->applyRepair($job, $analysis);
                    $applied++;
                });
            }
        }

        $this->table(
            ['Status', 'Job ID', 'Job No', 'Type', 'Current Status', 'Room', 'Target JA ID', 'Target JA No', 'Target Room', 'Note'],
            $rows
        );

        $this->line('Scanned jobs   : ' . $jobs->count());
        $this->line('Repair plans   : ' . count($plans));
        $this->line('Applied repairs: ' . ($apply ? $applied : 'dry-run'));
        $this->line('Skipped        : ' . $skipped);

        if (!$apply) {
            $this->line('Dry run only. Re-run with --apply after reviewing PLAN rows.');
        }

        return self::SUCCESS;
    }

    private function loadJobs()
    {
        $query = JobSchedule::with(['room', 'jobScheduleRooms.rentals'])
            ->whereNull('job_advice_id')
            ->whereNotNull('job_number')
            ->whereIn(DB::raw('LOWER(TRIM(type))'), ['remove', 'rv', 'remove_free', 'remove free', 'rf']);

        if ($this->option('job-id')) {
            $query->whereKey((int) $this->option('job-id'));
        }

        if ($this->option('job-no')) {
            $query->where('job_number', trim((string) $this->option('job-no')));
        }

        return $query
            ->orderByDesc('id')
            ->limit(max((int) $this->option('limit'), 1))
            ->get();
    }

    private function analyzeJob(JobSchedule $job, ?int $forcedJobAdviceId): array
    {
        if ($forcedJobAdviceId) {
            $forced = DB::table('job_advices')->where('id', $forcedJobAdviceId)->first();

            return [
                'repairable' => (bool) $forced,
                'target_job_advice_id' => $forced?->id,
                'target_job_advice_no' => $forced?->job_advice_number,
                'target_room_id' => null,
                'target_room' => null,
                'note' => $forced ? 'forced by --job-advice-id' : 'forced Job Advice not found',
            ];
        }

        $candidates = collect()
            ->merge($this->directRoomCandidates($job))
            ->merge($this->unitOnWallCandidates($job))
            ->merge($this->jobContextCandidates($job))
            ->filter(fn ($candidate) => !empty($candidate['job_advice_id']))
            ->unique(fn ($candidate) => $candidate['job_advice_id'] . ':' . ($candidate['job_advice_room_id'] ?? '0'))
            ->values();

        if ($candidates->isEmpty()) {
            return $this->emptyAnalysis('no matching Job Advice candidate found');
        }

        $groupedByAdvice = $candidates->groupBy('job_advice_id');
        if ($groupedByAdvice->count() > 1) {
            return $this->emptyAnalysis('ambiguous: multiple Job Advice candidates (' . $groupedByAdvice->keys()->implode(', ') . ')');
        }

        $target = $candidates
            ->sortByDesc(fn ($candidate) => $candidate['score'])
            ->first();

        return [
            'repairable' => true,
            'target_job_advice_id' => (int) $target['job_advice_id'],
            'target_job_advice_no' => $target['job_advice_number'] ?? null,
            'target_room_id' => $target['job_advice_room_id'] ?? null,
            'target_room' => $target['room_name'] ?? null,
            'note' => $target['source'] ?? 'matched candidate',
        ];
    }

    private function directRoomCandidates(JobSchedule $job)
    {
        $scheduleRoomIds = DB::table('job_schedule_rooms')
            ->where('job_schedule_id', $job->id)
            ->pluck('id');

        $direct = DB::table('job_schedule_rooms as jsr')
            ->join('job_advice_rooms as jar', 'jar.id', '=', 'jsr.job_advice_room_id')
            ->join('job_advices as ja', 'ja.id', '=', 'jar.job_advice_id')
            ->where('jsr.job_schedule_id', $job->id)
            ->selectRaw('ja.id as job_advice_id, ja.job_advice_number, jar.id as job_advice_room_id, jar.room_name, 100 as score')
            ->get()
            ->map(fn ($row) => $this->candidate($row, 'linked from existing Job Schedule Room'));

        $pivot = collect();
        if ($scheduleRoomIds->isNotEmpty() && Schema::hasTable('job_schedule_room_rentals')) {
            $pivot = DB::table('job_schedule_room_rentals as jsrr')
                ->join('job_advice_rooms as jar', 'jar.id', '=', 'jsrr.job_advice_room_id')
                ->join('job_advices as ja', 'ja.id', '=', 'jar.job_advice_id')
                ->whereIn('jsrr.job_schedule_room_id', $scheduleRoomIds)
                ->selectRaw('ja.id as job_advice_id, ja.job_advice_number, jar.id as job_advice_room_id, jar.room_name, 100 as score')
                ->get()
                ->map(fn ($row) => $this->candidate($row, 'linked from existing room-rental pivot'));
        }

        $removeLink = DB::table('job_advice_rooms as jar')
            ->join('job_advices as ja', 'ja.id', '=', 'jar.job_advice_id')
            ->where('jar.remove_job_schedule_id', $job->id)
            ->selectRaw('ja.id as job_advice_id, ja.job_advice_number, jar.id as job_advice_room_id, jar.room_name, 100 as score')
            ->get()
            ->map(fn ($row) => $this->candidate($row, 'linked from Job Advice Room remove_job_schedule_id'));

        return $direct->merge($pivot)->merge($removeLink);
    }

    private function unitOnWallCandidates(JobSchedule $job)
    {
        if (!Schema::hasTable('unit_on_walls')) {
            return collect();
        }

        $roomName = $this->roomName($job);

        $units = DB::table('unit_on_walls')
            ->when($job->building_id, fn ($query) => $query->where('building_id', $job->building_id))
            ->when($job->room_id, fn ($query) => $query->where('room_id', $job->room_id))
            ->when(!$job->room_id && $roomName, fn ($query) => $query->whereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim($roomName))]))
            ->whereIn(DB::raw('LOWER(TRIM(status))'), ['active', 'installed', 'on_wall', 'on wall', 'onwall', 'removed'])
            ->get();

        if ($units->isEmpty()) {
            return collect();
        }

        $unitIds = $units->pluck('id')->filter()->unique()->values();
        $customerIds = $units->pluck('customer_id')->filter()->unique()->values();

        return DB::table('job_advice_rooms as jar')
            ->join('job_advices as ja', 'ja.id', '=', 'jar.job_advice_id')
            ->leftJoin('contract_rooms as cr', 'cr.id', '=', 'jar.contract_room_id')
            ->leftJoin('quotation_rooms as qr', 'qr.id', '=', 'jar.quotation_room_id')
            ->where(function ($query) use ($unitIds, $customerIds, $job, $roomName) {
                $query->whereIn('jar.existing_unit_on_wall_id', $unitIds);

                if ($customerIds->isNotEmpty()) {
                    $query->orWhere(function ($customerQuery) use ($customerIds, $job, $roomName) {
                        $customerQuery->whereIn('ja.customer_id', $customerIds);
                        $this->applyRoomMatch($customerQuery, $job, $roomName);
                    });
                }
            })
            ->selectRaw('ja.id as job_advice_id, ja.job_advice_number, jar.id as job_advice_room_id, jar.room_name, 90 as score')
            ->get()
            ->map(fn ($row) => $this->candidate($row, 'matched from Unit On Wall/customer/room'));
    }

    private function jobContextCandidates(JobSchedule $job)
    {
        $roomName = $this->roomName($job);

        return DB::table('job_advice_rooms as jar')
            ->join('job_advices as ja', 'ja.id', '=', 'jar.job_advice_id')
            ->leftJoin('contract_rooms as cr', 'cr.id', '=', 'jar.contract_room_id')
            ->leftJoin('quotation_rooms as qr', 'qr.id', '=', 'jar.quotation_room_id')
            ->leftJoin('contracts as c', 'c.id', '=', 'ja.contract_id')
            ->leftJoin('quotations as q', 'q.id', '=', 'ja.quotation_id')
            ->where(function ($query) use ($job, $roomName) {
                if ($job->contract_number) {
                    $query->orWhere('c.contract_number', $job->contract_number);
                }

                if ($job->quotation_number) {
                    $query->orWhere('q.quotation_number', $job->quotation_number);
                }

                if ($job->schedule_date) {
                    $query->orWhereDate('ja.remove_date', $job->schedule_date);
                }
            })
            ->where(function ($query) use ($job, $roomName) {
                $this->applyRoomMatch($query, $job, $roomName);
            })
            ->selectRaw('ja.id as job_advice_id, ja.job_advice_number, jar.id as job_advice_room_id, jar.room_name, 70 as score')
            ->get()
            ->map(fn ($row) => $this->candidate($row, 'matched from job context'));
    }

    private function applyRoomMatch($query, JobSchedule $job, ?string $roomName): void
    {
        $query->where(function ($roomQuery) use ($job, $roomName) {
            if ($job->room_id) {
                $roomQuery->orWhere('cr.room_id', $job->room_id)
                    ->orWhere('qr.room_id', $job->room_id);
            }

            if ($roomName) {
                $roomQuery->orWhereRaw('LOWER(TRIM(jar.room_name)) = ?', [strtolower(trim($roomName))]);
            }
        });
    }

    private function applyRepair(JobSchedule $job, array $analysis): void
    {
        $job->forceFill([
            'job_advice_id' => $analysis['target_job_advice_id'],
            'updated_by' => auth()->id(),
        ])->save();

        if ($analysis['target_room_id']) {
            DB::table('job_advice_rooms')
                ->where('id', $analysis['target_room_id'])
                ->where(function ($query) use ($job) {
                    $query->whereNull('remove_job_schedule_id')
                        ->orWhere('remove_job_schedule_id', $job->id);
                })
                ->update([
                    'remove_job_schedule_id' => $job->id,
                    'updated_at' => now(),
                ]);

            $room = DB::table('job_advice_rooms as jar')
                ->leftJoin('contract_rooms as cr', 'cr.id', '=', 'jar.contract_room_id')
                ->leftJoin('quotation_rooms as qr', 'qr.id', '=', 'jar.quotation_room_id')
                ->where('jar.id', $analysis['target_room_id'])
                ->selectRaw('jar.id, jar.room_name, COALESCE(cr.room_id, qr.room_id) as room_id')
                ->first();

            DB::table('job_schedule_rooms')->updateOrInsert(
                [
                    'job_schedule_id' => $job->id,
                    'job_advice_room_id' => $analysis['target_room_id'],
                ],
                [
                    'room_name' => $room?->room_name ?: $this->roomName($job),
                    'room_id' => $room?->room_id ?: $job->room_id,
                    'status' => 'pending',
                    'material_return_status' => 'not_required',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function roomName(JobSchedule $job): ?string
    {
        return $job->getRawOriginal('room_name') ?: $job->room?->room_name;
    }

    private function candidate($row, string $source): array
    {
        return [
            'job_advice_id' => (int) $row->job_advice_id,
            'job_advice_number' => $row->job_advice_number,
            'job_advice_room_id' => $row->job_advice_room_id ? (int) $row->job_advice_room_id : null,
            'room_name' => $row->room_name,
            'score' => (int) $row->score,
            'source' => $source,
        ];
    }

    private function emptyAnalysis(string $note): array
    {
        return [
            'repairable' => false,
            'target_job_advice_id' => null,
            'target_job_advice_no' => null,
            'target_room_id' => null,
            'target_room' => null,
            'note' => $note,
        ];
    }
}
