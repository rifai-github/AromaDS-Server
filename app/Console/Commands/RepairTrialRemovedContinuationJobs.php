<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Services\Operational\InstallFreeContractRoomScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * QA 27 Nov 2026 (BLP-CA/26-11/0005): saat kontrak disetujui, Ruang-1 yang unit trial-nya sudah
 * dicabut (Remove Free selesai) tetap dibuatkan Job Check periode 2-12 (job 59939-59949). Kode
 * pembuatnya sudah diperbaiki (b757ccd); perintah ini membersihkan job lanjutan yang terlanjur dibuat.
 *
 * Yang dihapus (soft delete) hanya job service/Job Check yang BELUM DIMULAI (scheduled/new_job,
 * tanpa BA) milik kontrak itu, untuk ruangan yang unit trial-nya sudah dicabut. Job yang berisi
 * ruangan lain hanya dilepas ruangan tercabutnya. Dry-run default.
 */
class RepairTrialRemovedContinuationJobs extends Command
{
    protected $signature = 'contracts:repair-trial-removed-continuation-jobs
        {--contract= : Hanya satu nomor kontrak}
        {--apply : Tulis perubahan (default dry-run)}';

    protected $description = 'Hapus job lanjutan kontrak untuk ruangan trial yang unitnya sudah dicabut (dry-run default)';

    private const SERVICE_TYPES = ['service', 'service_first', 'service_routine'];

    private const NOT_STARTED = ['scheduled', 'new_job'];

    public function handle(InstallFreeContractRoomScope $scope): int
    {
        $apply = (bool) $this->option('apply');

        $quotationNumbers = DB::table('job_schedules')
            ->whereIn('type', ['remove_free', 'remove free'])
            ->whereNull('deleted_at')
            ->whereNotNull('quotation_number')
            ->distinct()
            ->pluck('quotation_number');

        $contracts = Contract::with('quotation')
            ->whereHas('quotation', fn ($q) => $q->whereIn('quotation_number', $quotationNumbers))
            ->when($this->option('contract'), fn ($q, $number) => $q->where('contract_number', $number))
            ->get();

        $rows = [];
        $jobsDeleted = 0;
        $roomsDetached = 0;

        foreach ($contracts as $contract) {
            $removedRoomIds = $scope->trialRemovedRoomIds($contract->quotation?->quotation_number)
                ->map(fn ($id) => (int) $id)
                ->all();
            if (empty($removedRoomIds)) {
                continue;
            }

            $jobs = DB::table('job_schedules as js')
                ->leftJoin('job_advices as ja', 'ja.id', '=', 'js.job_advice_id')
                ->whereNull('js.deleted_at')
                ->whereIn('js.type', self::SERVICE_TYPES)
                ->whereIn('js.status', self::NOT_STARTED)
                ->whereNull('js.ba_date')
                ->where(function ($q) use ($contract) {
                    $q->where('ja.contract_id', $contract->id)
                        ->orWhere('js.contract_number', $contract->contract_number);
                })
                // Hanya job lanjutan yang menempel di JA TRIAL (Install Free / JA tanpa kontrak).
                // Job dari JA milik kontrak sendiri (mis. JA Install kontrak, prod 27 Nov:
                // ADS-JA/26-11/0007 → Job Check Ruang-1 59999-60009) bukan sisa trial.
                ->where(function ($q) {
                    $q->whereNull('ja.contract_id')
                        ->orWhereRaw("LOWER(REPLACE(COALESCE(ja.type, ''), ' ', '_')) = ?", ['install_free']);
                })
                ->whereExists(function ($q) use ($removedRoomIds) {
                    $q->selectRaw('1')->from('job_schedule_rooms as r')
                        ->whereColumn('r.job_schedule_id', 'js.id')
                        ->whereNull('r.deleted_at')
                        ->whereIn('r.room_id', $removedRoomIds);
                })
                ->orderBy('js.id')
                ->get(['js.id', 'js.job_number', 'js.type', 'js.status', 'js.period']);

            foreach ($jobs as $job) {
                $rooms = DB::table('job_schedule_rooms')
                    ->where('job_schedule_id', $job->id)
                    ->whereNull('deleted_at')
                    ->get(['id', 'room_id', 'room_name']);
                $removed = $rooms->filter(fn ($room) => in_array((int) $room->room_id, $removedRoomIds, true));
                $wholeJob = $removed->count() === $rooms->count();

                $rows[] = [
                    $contract->contract_number,
                    $job->id,
                    $job->job_number ?: '-',
                    $job->type,
                    $job->period ?? '-',
                    $removed->pluck('room_name')->unique()->implode(', '),
                    $wholeJob ? 'hapus job' : 'lepas ruangan',
                ];

                if ($apply) {
                    DB::transaction(function () use ($job, $removed, $wholeJob) {
                        $now = now();
                        $roomIds = $removed->pluck('id')->all();
                        DB::table('job_schedule_room_rentals')->whereIn('job_schedule_room_id', $roomIds)->whereNull('deleted_at')
                            ->update(['deleted_at' => $now, 'updated_at' => $now]);
                        DB::table('job_schedule_rooms')->whereIn('id', $roomIds)
                            ->update(['deleted_at' => $now, 'updated_at' => $now]);
                        if ($wholeJob) {
                            DB::table('job_schedules')->where('id', $job->id)
                                ->update(['deleted_at' => $now, 'updated_at' => $now]);
                        }
                    });
                }

                $wholeJob ? $jobsDeleted++ : $roomsDetached++;
            }
        }

        if (empty($rows)) {
            $this->info('Tidak ada job lanjutan untuk ruangan trial yang sudah dicabut.');

            return self::SUCCESS;
        }

        $this->table(['Kontrak', 'Job ID', 'No. Job', 'Tipe', 'Periode', 'Ruangan dicabut', 'Aksi'], $rows);
        $this->info(($apply ? 'Diperbaiki' : 'Akan diperbaiki').": {$jobsDeleted} job dihapus, {$roomsDetached} job dilepas ruangannya.");
        if (! $apply) {
            $this->warn('Dry-run: belum ada yang ditulis. Jalankan dengan --apply.');
        }

        return self::SUCCESS;
    }
}
