<?php

namespace App\Console\Commands;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\JobScheduleRoomRental;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QA batch 4 (27 Nov 2026, JA ADS-JA/26-11/0007 / kontrak BLP-CA/26-11/0006): ruangan dengan
 * beberapa rental berfrekuensi service berbeda dulu hanya mendapat seri service lanjutan menurut
 * rental PERTAMA, dan job seri itu menautkan semua rental ruangan. Command ini:
 *
 *  1. melepas rental berfrekuensi lain dari job lanjutan lama itu (hanya job yang belum bernomor,
 *     masih scheduled/new_job, dan belum di-assign tim) — job itu tetap milik rental yang dulu
 *     menentukan tanggalnya;
 *  2. membuat seri service yang hilang untuk rental lainnya lewat generator yang sama
 *     (JobScheduleController::generateAllRemainingServices);
 *  3. menyamakan created_at baris baru dengan baris lama, supaya Job View mengurutkan keduanya
 *     per bulan (bukan blok seri baru di atas).
 *
 * Default dry-run. Job yang sudah bernomor/diproses dilaporkan MANUAL dan seluruh service pertama
 * itu dilewati.
 */
class RepairMultiFrequencyRoomServices extends Command
{
    protected $signature = 'operational:repair-multi-frequency-room-services
        {--contract=* : Nomor atau id kontrak, boleh berulang}
        {--job-advice=* : Nomor JA, boleh berulang}
        {--all : Pindai semua JA yang punya service pertama selesai}
        {--apply : Terapkan perbaikan. Default dry-run}';

    protected $description = 'Buat seri service lanjutan per rental untuk ruangan berisi rental dengan frekuensi service berbeda (QA batch 4, 27 Nov 2026)';

    private const COMPLETED_STATUSES = ['completed', 'done_job', 'dpf', 'selesai'];

    private const SERVICE_TYPES = ['service', 'service_first', 'service_routine'];

    private const DETACHABLE_STATUSES = ['scheduled', 'new_job'];

    public function handle(JobScheduleController $controller): int
    {
        $contracts = collect($this->option('contract'))->filter()->map(fn ($value) => trim((string) $value))->values();
        $jobAdviceNumbers = collect($this->option('job-advice'))->filter()->map(fn ($value) => trim((string) $value))->values();
        $apply = (bool) $this->option('apply');

        if ($contracts->isEmpty() && $jobAdviceNumbers->isEmpty() && ! $this->option('all')) {
            $this->error('Pakai --contract, --job-advice, atau --all.');

            return self::FAILURE;
        }

        if (! $apply) {
            $this->warn('DRY RUN. Tidak ada perubahan database. Jalankan ulang dengan --apply setelah meninjau baris PLAN.');
        }

        $jobAdvices = JobAdvice::query()
            ->with(['contract', 'rooms.rentalProduct.serviceFrequency', 'rooms.contractRoom.room', 'rooms.quotationRoom.room'])
            ->when($contracts->isNotEmpty(), function ($query) use ($contracts) {
                $numericIds = $contracts->filter(fn ($value) => ctype_digit($value))->map(fn ($value) => (int) $value)->all();
                $query->where(function ($inner) use ($contracts, $numericIds) {
                    $inner->whereHas('contract', fn ($contractQuery) => $contractQuery->whereIn('contract_number', $contracts->all()));
                    if (! empty($numericIds)) {
                        $inner->orWhereIn('contract_id', $numericIds);
                    }
                });
            })
            ->when($jobAdviceNumbers->isNotEmpty(), fn ($query) => $query->whereIn('job_advice_number', $jobAdviceNumbers->all()))
            ->whereHas('jobSchedules', function ($query) {
                $query->whereIn('type', self::SERVICE_TYPES)->whereIn('status', self::COMPLETED_STATUSES);
            })
            ->orderBy('id')
            ->get();

        $hasIntervalColumn = Schema::hasColumn((new JobSchedule)->getTable(), 'service_interval_days');
        $rows = [];
        $stats = ['first_services' => 0, 'affected' => 0, 'manual' => 0, 'detached_jobs' => 0, 'created' => 0];

        foreach ($jobAdvices as $jobAdvice) {
            foreach ($this->completedFirstServices($jobAdvice) as $firstService) {
                $stats['first_services']++;
                $series = $controller->describeServiceFrequencySeries($firstService, $jobAdvice, true);

                if (count($series) < 2) {
                    continue;
                }

                $conflictIds = collect($series)->flatMap(fn ($plan) => $plan['conflicts'])->unique()->values();
                $plannedCount = collect($series)->sum(fn ($plan) => $plan['skip'] ? 0 : count($plan['rows']));

                if ($conflictIds->isEmpty() && $plannedCount === 0) {
                    continue;
                }

                $stats['affected']++;
                $label = ($jobAdvice->contract?->contract_number ?? '-').' / '.$jobAdvice->job_advice_number.' / '.($firstService->job_number ?: "#{$firstService->id}");

                foreach ($series as $plan) {
                    $rows[] = [
                        $apply ? 'RUN' : 'PLAN',
                        $label,
                        $this->seriesLabel($plan),
                        $plan['skip'] === 'no_total'
                            ? 'tidak ada service lanjutan (frekuensi/periode kontrak)'
                            : 'ada periode '.($plan['existing_periods'] ? implode(',', $plan['existing_periods']) : '-')
                                .'; buat '.count($plan['rows']).' baris'
                                .($plan['rows'] ? ': '.collect($plan['rows'])->map(fn ($row) => 'P'.$row['period'].' '.$row['date']->format('Y-m-d'))->implode(', ') : ''),
                    ];
                }

                $conflictJobs = JobSchedule::query()
                    ->with(['jobScheduleRooms.rentals', 'jobAssignSchedules'])
                    ->whereIn('id', $conflictIds->all())
                    ->orderBy('id')
                    ->get();

                $unsafe = $conflictJobs->filter(fn (JobSchedule $job) => ! $this->isDetachable($job));
                foreach ($conflictJobs as $job) {
                    $detachable = ! $unsafe->contains('id', $job->id);
                    $foreign = $this->foreignJobAdviceRoomIds($job, $series);
                    $rows[] = [
                        $detachable ? ($apply ? 'RUN' : 'PLAN') : 'MANUAL',
                        $label,
                        "job #{$job->id} P{$job->period} ".optional($job->schedule_date)->format('Y-m-d'),
                        ($detachable ? 'lepas' : 'TIDAK bisa otomatis melepas')
                            .' rental JA room '.implode(',', $foreign)
                            .($detachable ? '' : " (job_number={$job->job_number}, status={$job->status})"),
                    ];
                }

                if ($unsafe->isNotEmpty()) {
                    $stats['manual']++;

                    continue;
                }

                if (! $apply) {
                    continue;
                }

                DB::transaction(function () use ($controller, $jobAdvice, $firstService, $series, $conflictJobs, $hasIntervalColumn, &$stats, &$rows, $label) {
                    $anchorCreatedAt = null;

                    foreach ($conflictJobs as $job) {
                        $this->detachForeignRentals($job, $series, $hasIntervalColumn);
                        $stats['detached_jobs']++;
                        $anchorCreatedAt = $anchorCreatedAt === null || $job->created_at < $anchorCreatedAt
                            ? $job->created_at
                            : $anchorCreatedAt;
                    }

                    $anchorCreatedAt ??= $this->existingFollowUpCreatedAt($jobAdvice, $firstService, $series);

                    $created = collect($controller->generateAllRemainingServices($firstService->fresh(), $jobAdvice->fresh()) ?? []);
                    $stats['created'] += $created->count();

                    if ($created->isNotEmpty() && $anchorCreatedAt) {
                        foreach ($created as $job) {
                            DB::table($job->getTable())->where('id', $job->id)->update([
                                'created_at' => $anchorCreatedAt,
                                'internal_notes' => trim(($job->internal_notes ?? '')."\n[Repair multi-frekuensi ".now()->format('Y-m-d').']'),
                            ]);
                        }
                    }

                    $rows[] = ['DONE', $label, '-', "{$created->count()} baris service dibuat: ".$created->pluck('id')->implode(',')];
                });
            }
        }

        $this->table(['Status', 'Kontrak / JA / Service pertama', 'Seri / Job', 'Catatan'], $rows);
        $this->line('Service pertama dipindai : '.$stats['first_services']);
        $this->line('Perlu diperbaiki        : '.$stats['affected']);
        $this->line('Perlu manual            : '.$stats['manual']);
        $this->line('Job lama dirapikan      : '.($apply ? $stats['detached_jobs'] : 'dry-run'));
        $this->line('Baris service dibuat    : '.($apply ? $stats['created'] : 'dry-run'));

        return self::SUCCESS;
    }

    /** @return Collection<int, JobSchedule> */
    private function completedFirstServices(JobAdvice $jobAdvice): Collection
    {
        return JobSchedule::query()
            ->where('job_advice_id', $jobAdvice->id)
            ->whereIn('type', self::SERVICE_TYPES)
            ->whereIn('status', self::COMPLETED_STATUSES)
            ->where(function ($query) {
                $query->whereNull('period')->orWhere('period', '')->orWhere('period', 1)->orWhere('period', '1');
            })
            ->orderBy('id')
            ->get();
    }

    private function seriesLabel(array $plan): string
    {
        $rental = $plan['rental'];
        $frequency = $rental?->serviceFrequency;

        return ($rental?->rental_name ?? '?')
            .' ['.($frequency?->name ?? 'tanpa frekuensi').'] JA room '.implode(',', $plan['ja_room_ids'])
            .' total '.$plan['total'];
    }

    private function isDetachable(JobSchedule $job): bool
    {
        if (filled($job->job_number) || $job->trashed()) {
            return false;
        }

        if (! in_array(strtolower((string) $job->status), self::DETACHABLE_STATUSES, true)) {
            return false;
        }

        return $job->jobAssignSchedules->every(fn ($assign) => strtolower((string) $assign->status) === 'cancelled');
    }

    /** @return array<int, int> seri pemilik (indeks terkecil yang ditautkan job) => indeks */
    private function seriesIndexByJobAdviceRoomId(array $series): array
    {
        $map = [];
        foreach ($series as $plan) {
            foreach ($plan['ja_room_ids'] as $jaRoomId) {
                $map[(int) $jaRoomId] = (int) $plan['series_index'];
            }
        }

        return $map;
    }

    /** @return array<int, int> */
    private function foreignJobAdviceRoomIds(JobSchedule $job, array $series): array
    {
        $map = $this->seriesIndexByJobAdviceRoomId($series);
        $linked = $job->jobScheduleRooms
            ->flatMap(fn ($room) => $room->rentals->pluck('job_advice_room_id'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => array_key_exists($id, $map))
            ->unique()
            ->values();

        if ($linked->isEmpty()) {
            return [];
        }

        $owner = $linked->map(fn ($id) => $map[$id])->min();

        return $linked->filter(fn ($id) => $map[$id] !== $owner)->values()->all();
    }

    private function detachForeignRentals(JobSchedule $job, array $series, bool $hasIntervalColumn): void
    {
        $map = $this->seriesIndexByJobAdviceRoomId($series);
        $foreign = $this->foreignJobAdviceRoomIds($job, $series);
        if (empty($foreign)) {
            return;
        }

        $ownerJaRoomIds = $job->jobScheduleRooms
            ->flatMap(fn ($room) => $room->rentals->pluck('job_advice_room_id'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => array_key_exists($id, $map) && ! in_array($id, $foreign, true))
            ->unique()
            ->values();
        $ownerIndex = $ownerJaRoomIds->map(fn ($id) => $map[$id])->min();

        foreach ($job->jobScheduleRooms as $scheduleRoom) {
            JobScheduleRoomRental::query()
                ->where('job_schedule_room_id', $scheduleRoom->id)
                ->whereIn('job_advice_room_id', $foreign)
                ->get()
                ->each->delete();

            if (! in_array((int) $scheduleRoom->job_advice_room_id, $foreign, true)) {
                continue;
            }

            $remaining = JobScheduleRoomRental::query()
                ->where('job_schedule_room_id', $scheduleRoom->id)
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->get();

            if ($remaining->isEmpty()) {
                $scheduleRoom->delete();

                continue;
            }

            $scheduleRoom->forceFill(['job_advice_room_id' => $remaining->first()->job_advice_room_id])->save();
            $remaining->first()->forceFill(['is_primary' => true])->save();
        }

        if ($hasIntervalColumn && $ownerIndex !== null) {
            $ownerPlan = collect($series)->firstWhere('series_index', $ownerIndex);
            $frequency = $ownerPlan['rental']?->serviceFrequency;
            $intervalDays = $ownerPlan['interval_days']
                ?? ($frequency ? JobSchedule::serviceSeriesIntervalDays($frequency->frequency_months, $frequency->frequency_times_per_month) : null);

            if ($intervalDays) {
                DB::table($job->getTable())->where('id', $job->id)->update(['service_interval_days' => $intervalDays]);
            }
        }
    }

    /** created_at baris lanjutan lama milik service pertama ini, sebagai patokan urutan Job View. */
    private function existingFollowUpCreatedAt(JobAdvice $jobAdvice, JobSchedule $firstService, array $series)
    {
        $jaRoomIds = collect($series)->flatMap(fn ($plan) => $plan['ja_room_ids'])->all();

        return JobSchedule::query()
            ->where('job_advice_id', $jobAdvice->id)
            ->where('id', '!=', $firstService->id)
            ->where('building_id', $firstService->building_id)
            ->whereIn('type', self::SERVICE_TYPES)
            ->where('period', '>', 1)
            ->whereHas('jobScheduleRooms.rentals', fn ($query) => $query->whereIn('job_advice_room_id', $jaRoomIds))
            ->min('created_at');
    }
}
