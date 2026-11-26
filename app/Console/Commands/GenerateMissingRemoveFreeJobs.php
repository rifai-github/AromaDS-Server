<?php

namespace App\Console\Commands;

use App\Http\Controllers\Marketing\JobAdviceController;
use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use Illuminate\Console\Command;

/**
 * QA 26 Nov 2026 (JKT-JA/26-11/0003 Ruang-1 & Ruang-6): Install Free sudah BA dan unitnya
 * tercatat di Unit On Wall, tetapi Remove Free tidak dibuat (syarat lama "unit baru dibuat saat
 * BA" selalu gagal sejak unit dicatat per ruangan). Syaratnya sudah diperbaiki; perintah ini
 * membuatkan Remove Free yang terlewat untuk job yang SUDAH selesai, dengan syarat yang sama
 * persis (JobScheduleController::installFreeNeedsRemoveFree) dan pembuat yang sama
 * (JobAdviceController::generateRemoveFreeSchedule, idempoten per ruangan). Dry-run default.
 */
class GenerateMissingRemoveFreeJobs extends Command
{
    protected $signature = 'install-free:generate-missing-remove-free
        {--job-advice= : Hanya satu nomor Job Advice}
        {--apply : Buat job Remove Free (default dry-run)}';

    protected $description = 'Buat Remove Free yang terlewat untuk Install Free yang sudah selesai (dry-run default)';

    public function handle(JobScheduleController $jobSchedules): int
    {
        $apply = (bool) $this->option('apply');

        $query = JobSchedule::query()
            ->whereIn('type', ['install_free', 'install free', 'install', 'Install', 'Install Free'])
            ->whereIn('status', ['done_job', 'completed'])
            ->whereHas('jobAdvice', fn ($q) => $q->whereIn('type', ['install_free', 'install free'])->whereNotNull('remove_date'));

        if ($number = $this->option('job-advice')) {
            $query->whereHas('jobAdvice', fn ($q) => $q->where('job_advice_number', $number));
        }

        $rows = [];
        $created = 0;

        foreach ($query->orderBy('id')->get() as $install) {
            $jobAdvice = JobAdvice::with(['rooms', 'quotation'])->find($install->job_advice_id);
            if (! $jobAdvice || ! $jobSchedules->installFreeNeedsRemoveFree($install, $jobAdvice)) {
                continue;
            }

            $completedJaRoomIds = $install->jobScheduleRooms()
                ->where('status', \App\Models\JobScheduleRoom::STATUS_COMPLETED)
                ->whereNotNull('job_advice_room_id')
                ->pluck('job_advice_room_id');

            $missing = $jobAdvice->rooms
                ->whereIn('id', $completedJaRoomIds)
                ->filter(fn ($room) => empty($room->remove_job_schedule_id));

            if ($missing->isEmpty()) {
                continue;
            }

            $result = '-';
            if ($apply) {
                $response = app(JobAdviceController::class)->generateRemoveFreeSchedule($jobAdvice, $install);
                $payload = method_exists($response, 'getData') ? $response->getData(true) : [];
                $result = ($payload['success'] ?? false) ? 'dibuat' : ('gagal: '.($payload['message'] ?? '?'));
                if ($payload['success'] ?? false) {
                    $created++;
                }
            }

            $rows[] = [$jobAdvice->job_advice_number, $install->job_number, $missing->pluck('room_name')->implode(', '), $apply ? $result : 'akan dibuat'];
        }

        $this->table(['Job Advice', 'Job Install Free', 'Ruangan tanpa Remove Free', 'Hasil'], $rows);
        $this->info($apply ? "Remove Free dibuat untuk {$created} job." : count($rows).' job Install Free perlu Remove Free. Jalankan dengan --apply.');

        return self::SUCCESS;
    }
}
