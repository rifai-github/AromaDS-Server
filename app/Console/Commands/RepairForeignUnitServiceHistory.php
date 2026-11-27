<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\JobSchedule;
use App\Models\UnitOnWall;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * QA 27 Nov 2026 (Customer Testing Indo): menyelesaikan CSR kontrak BLP-CA/26-11/0006 mencatat
 * riwayat "Service by job ..." dan last_service_date ke SEMUA unit aktif di ruangan, termasuk
 * unit kontrak lain (DIS005S2611230016 milik BLP-CA/26-11/0004) dan unit trial SQ lain
 * (DIS005S2611230007 milik trial BLP-SQ/26-11/0003). Kode sudah dibatasi ke unit kontrak job
 * (JobScheduleController::autoUpdateUnitOnWallLastServiceDate). Command ini membersihkan baris
 * riwayat yang terlanjur tertulis.
 *
 * Konservatif: baris hanya dianggap salah bila unitnya TERBUKTI milik sumber lain - contract_id
 * menunjuk kontrak hidup di luar rantai renewal/switching kontrak job, atau unit tanpa kontrak
 * yang dipasang Install Free SQ lain (UnitOnWall::scopeNotOwnedByOtherSource()). Unit lama tanpa
 * kontrak tidak disentuh. last_service_date unit hanya dihitung ulang bila nilainya sama dengan
 * tanggal riwayat yang dibuang; nilai baru = action_date service terakhir yang tersisa (atau NULL).
 */
class RepairForeignUnitServiceHistory extends Command
{
    protected $signature = 'unit-on-wall:repair-foreign-service-history
        {--apply : Hapus baris riwayat yang salah dan hitung ulang last_service_date. Default dry-run}
        {--customer= : Batasi ke customer_id unit tertentu}
        {--job= : Batasi ke job_schedule_id tertentu}';

    protected $description = 'Hapus riwayat service Unit On Wall yang dicatat oleh job service kontrak/SQ lain (dry-run default)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->warn('DRY RUN. Tidak ada perubahan. Jalankan ulang dengan --apply untuk menyimpan.');
        }

        $histories = DB::table('unit_on_wall_histories')
            ->where('action', 'service')
            ->whereNotNull('job_schedule_id')
            ->when($this->option('customer'), fn ($query, $customerId) => $query->whereIn(
                'unit_on_wall_id',
                UnitOnWall::withTrashed()->where('customer_id', (int) $customerId)->select('id')
            ))
            ->when($this->option('job'), fn ($query, $jobId) => $query->where('job_schedule_id', (int) $jobId))
            ->orderBy('id')
            ->get();

        $ownershipByJob = [];
        $wrong = [];

        foreach ($histories as $history) {
            if (! array_key_exists($history->job_schedule_id, $ownershipByJob)) {
                $job = JobSchedule::withTrashed()->with('jobAdvice')->find($history->job_schedule_id);
                $contractId = $job?->jobAdvice?->contract_id;
                $ownershipByJob[$history->job_schedule_id] = $contractId
                    ? Contract::unitOwnershipLineageIds($contractId)
                    : null;
            }

            $ownership = $ownershipByJob[$history->job_schedule_id];
            if (! $ownership) {
                continue;
            }

            $belongsToJob = UnitOnWall::withTrashed()
                ->whereKey($history->unit_on_wall_id)
                ->notOwnedByOtherSource($ownership)
                ->exists();

            if (! $belongsToJob && UnitOnWall::withTrashed()->whereKey($history->unit_on_wall_id)->exists()) {
                $wrong[] = $history;
            }
        }

        if (empty($wrong)) {
            $this->info('Tidak ada riwayat service yang salah.');

            return self::SUCCESS;
        }

        $units = UnitOnWall::withTrashed()
            ->whereIn('id', collect($wrong)->pluck('unit_on_wall_id')->unique()->all())
            ->get()
            ->keyBy('id');
        $wrongIds = collect($wrong)->pluck('id')->all();

        $rows = [];
        $unitUpdates = [];

        foreach (collect($wrong)->groupBy('unit_on_wall_id') as $unitId => $unitHistories) {
            $unit = $units->get($unitId);
            $current = $unit?->last_service_date?->toDateString();
            $wrongDates = $unitHistories->map(fn ($history) => substr((string) $history->action_date, 0, 10))->all();

            $newDate = $current;
            if ($current && in_array($current, $wrongDates, true)) {
                $remaining = DB::table('unit_on_wall_histories')
                    ->where('unit_on_wall_id', $unitId)
                    ->where('action', 'service')
                    ->whereNotIn('id', $wrongIds)
                    ->max('action_date');
                $newDate = $remaining ? substr((string) $remaining, 0, 10) : null;
                if ($newDate !== $current) {
                    $unitUpdates[$unitId] = $newDate;
                }
            }

            foreach ($unitHistories as $history) {
                $rows[] = [
                    $history->id,
                    $unitId,
                    $unit?->serial_number ?: '-',
                    $unit?->contract_id ?: '-',
                    $history->job_schedule_number ?: $history->job_schedule_id,
                    implode(',', $ownershipByJob[$history->job_schedule_id] ?? []),
                    substr((string) $history->action_date, 0, 10),
                    ($current ?? 'NULL').' -> '.(array_key_exists($unitId, $unitUpdates) ? ($unitUpdates[$unitId] ?? 'NULL') : 'tetap'),
                ];
            }
        }

        $this->table(
            ['history_id', 'unit_id', 'serial_number', 'unit_contract_id', 'job', 'job_contract_lineage', 'action_date', 'last_service_date'],
            $rows
        );
        $this->line(count($wrong).' baris riwayat salah, '.count($unitUpdates).' unit perlu last_service_date dihitung ulang.');

        if (! $apply) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($wrongIds, $unitUpdates) {
            DB::table('unit_on_wall_histories')->whereIn('id', $wrongIds)->delete();

            foreach ($unitUpdates as $unitId => $date) {
                DB::table('unit_on_walls')->where('id', $unitId)->update([
                    'last_service_date' => $date,
                    'updated_at' => now(),
                ]);
            }
        });

        $this->info('Diterapkan: '.count($wrongIds).' baris riwayat dihapus, '.count($unitUpdates).' unit diperbarui.');

        return self::SUCCESS;
    }
}
