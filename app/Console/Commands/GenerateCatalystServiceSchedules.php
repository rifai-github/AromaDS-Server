<?php

namespace App\Console\Commands;

use App\Services\Imports\Catalyst\CatalystServiceScheduleBackfillService;
use Illuminate\Console\Command;

/**
 * Permintaan klien 24 Nov 2026: buat jadwal service kontrak hasil import Catalyst dari BA date
 * service pertama. Tanggal lewat = selesai (tidak ditagihkan ulang), sisanya outstanding.
 * Lihat CatalystServiceScheduleBackfillService.
 *
 * Default dry-run. Tulis ke database hanya dengan --apply.
 */
class GenerateCatalystServiceSchedules extends Command
{
    protected $signature = 'catalyst:generate-service-schedules
        {--apply : Tulis job ke database}
        {--contract= : Hanya satu nomor kontrak}
        {--include-expired : Ikutkan kontrak yang end_date-nya sudah lewat}
        {--limit= : Batasi jumlah kontrak}
        {--invoice-details=storage/app/catalyst/invoices/invoice_details_export.csv : Export FINCustInvDt (BASDate, CSR)}
        {--contract-details=storage/app/catalyst/contracts/contract_details_export.csv : Export MKTContractDt (InspectedInstall)}
        {--show=30 : Jumlah baris contoh yang ditampilkan}';

    protected $description = 'Buat jadwal service (selesai + outstanding) untuk kontrak import Catalyst dari BA date service pertama';

    public function handle(CatalystServiceScheduleBackfillService $service): int
    {
        $apply = (bool) $this->option('apply');
        $service->loadAnchorSources(base_path($this->option('invoice-details')), base_path($this->option('contract-details')));

        foreach (['invoice-details', 'contract-details'] as $option) {
            if (! is_file(base_path($this->option($option)))) {
                $this->warn("File {$option} tidak ditemukan: ".$this->option($option).' (sumber anchor ini dilewati).');
            }
        }

        $candidates = $service->candidates(
            $this->option('contract') ?: null,
            (bool) $this->option('include-expired'),
            $this->option('limit') ? (int) $this->option('limit') : null
        );

        $this->info('Kontrak Catalyst tanpa jadwal service: '.$candidates->count().' - mode '.($apply ? 'APPLY' : 'dry-run').'.');
        if ($candidates->isEmpty()) {
            return self::SUCCESS;
        }

        $stats = ['contracts' => 0, 'no_group' => 0, 'jobs_done' => 0, 'jobs_outstanding' => 0, 'skipped_rooms' => 0];
        $anchorSources = [];
        $rows = [];
        $show = max(0, (int) $this->option('show'));
        $userId = \App\Models\User::where('email', 'admin@aroma.com')->value('id');

        foreach ($candidates as $candidate) {
            $plan = $service->plan($candidate);
            $anchorSources[$plan['anchor_source']] = ($anchorSources[$plan['anchor_source']] ?? 0) + 1;
            $stats['skipped_rooms'] += $plan['skipped_rooms'];

            if ($plan['groups'] === []) {
                $stats['no_group']++;

                continue;
            }

            $done = array_sum(array_column($plan['groups'], 'done'));
            $total = array_sum(array_map(fn ($group) => count($group['dates']), $plan['groups']));

            if ($apply) {
                $counts = $service->apply($candidate, $plan, $userId);
                $done = $counts['done'];
                $total = $counts['done'] + $counts['outstanding'];
            }

            $stats['contracts']++;
            $stats['jobs_done'] += $done;
            $stats['jobs_outstanding'] += $total - $done;

            if (count($rows) < $show) {
                $nextDates = collect($plan['groups'])->flatMap(fn ($group) => $group['dates'])
                    ->filter(fn ($date) => $date >= now()->toDateString())->sort()->first();
                $rows[] = [
                    $candidate->contract_number,
                    $plan['anchor'].' ('.$plan['anchor_source'].')',
                    count($plan['groups']),
                    $done,
                    $total - $done,
                    $nextDates ?? '-',
                ];
            }
        }

        $this->table(['Kontrak', 'Anchor (sumber)', 'Grup', 'Selesai', 'Outstanding', 'Service berikutnya'], $rows);
        $this->line('Sumber anchor: '.collect($anchorSources)->map(fn ($n, $source) => "{$source}={$n}")->implode(', '));
        $this->line("Kontrak dijadwalkan: {$stats['contracts']}; job selesai: {$stats['jobs_done']}; job outstanding: {$stats['jobs_outstanding']}.");
        if ($stats['no_group'] || $stats['skipped_rooms']) {
            $this->warn("Dilewati: {$stats['no_group']} kontrak tanpa rental yang bisa dijadwalkan; {$stats['skipped_rooms']} rental tanpa frekuensi service atau tanpa gedung.");
        }

        if (! $apply) {
            $this->warn('Dry-run: belum ada yang ditulis. Jalankan ulang dengan --apply untuk menyimpan.');
        }

        return self::SUCCESS;
    }
}
