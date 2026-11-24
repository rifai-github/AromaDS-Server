<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractRoom;
use App\Services\Finance\GracePeriodService;
use App\Services\Operational\ContractRoomSuspensionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * MOM 19 Nov 2026 p3: grace period ada di level customer (customers.grace_period_days). Bila
 * invoice belum lunas melewati grace period, kontraknya di-suspend: semua room kontrak itu
 * dikunci (ContractRoomSuspensionService) sehingga job-nya off sampai di-unsuspend admin di
 * tab Room & Rental.
 *
 * Jawaban klien 24 Nov 2026: grace dihitung dari TANGGAL INVOICE (bukan jatuh tempo), grace
 * kosong/0 = 60 hari, dan hanya invoice terbit mulai 1 Jan 2027 yang memicu suspend. Aturannya
 * ada di GracePeriodService.
 *
 * Default dry-run. Tulis ke database hanya dengan --apply. Penjadwalan harian hanya aktif bila
 * GRACE_AUTO_SUSPEND=true (lihat config/contract.php) supaya invoice lama hasil import tak
 * langsung men-suspend massal.
 */
class SuspendContractsPastGracePeriod extends Command
{
    protected $signature = 'contracts:suspend-past-grace
        {--apply : Suspend room kontrak yang invoice-nya lewat grace period}
        {--date= : Tanggal acuan (default hari ini)}
        {--limit=30 : Jumlah baris yang ditampilkan}';

    protected $description = 'Suspend semua room kontrak yang punya invoice belum lunas melewati tanggal invoice + grace period customer';

    public function handle(ContractRoomSuspensionService $suspensions, GracePeriodService $grace): int
    {
        $apply = (bool) $this->option('apply');
        $today = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : now()->startOfDay();

        $invoices = $grace->invoicesPastGrace($today);

        $this->info('Kontrak dengan invoice lewat grace period per '.$today->format('d/m/Y').' - mode '.($apply ? 'apply' : 'dry-run').'.');
        $from = $grace->autoSuspendFrom();
        $this->line('Grace = tanggal invoice + grace customer (kosong/0 = '.$grace->defaultGraceDays().' hari)'
            .($from ? '; hanya invoice terbit mulai '.$from->format('d/m/Y').'.' : '.'));

        if ($invoices->isEmpty()) {
            $this->info('Tidak ada invoice yang melewati grace period.');

            return self::SUCCESS;
        }

        $rows = [];
        $contractsSuspended = 0;
        $roomsSuspended = 0;
        $limit = max(0, (int) $this->option('limit'));

        foreach ($invoices->groupBy('contract_id') as $contractId => $contractInvoices) {
            $contract = Contract::find($contractId);
            if (! $contract) {
                continue;
            }

            $openRoomIds = $suspensions->openSuspensionsForContract($contract)->keys()->map(fn ($id) => (int) $id)->all();
            $roomIds = ContractRoom::where('contract_id', $contract->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => in_array($id, $openRoomIds, true))
                ->values()
                ->all();

            $oldest = $contractInvoices->first();
            $graceDays = $oldest->grace_days;

            if ($apply && $roomIds) {
                $note = "Otomatis: invoice {$oldest->invoice_number} tanggal ".Carbon::parse($oldest->effective_invoice_date)->format('d/m/Y')
                    ." lewat grace period {$graceDays} hari (batas ".$oldest->grace_ends_on->format('d/m/Y').').';
                $roomsSuspended += $suspensions->suspend($contract, $roomIds, $today->toDateString(), null, $note);
                $contractsSuspended++;
            } elseif ($roomIds) {
                $roomsSuspended += count($roomIds);
                $contractsSuspended++;
            }

            if (count($rows) < $limit) {
                $rows[] = [
                    $contract->contract_number,
                    $oldest->customer_name,
                    $oldest->invoice_number,
                    Carbon::parse($oldest->effective_invoice_date)->format('d/m/Y'),
                    $graceDays,
                    $oldest->grace_ends_on->format('d/m/Y'),
                    $contractInvoices->count(),
                    $roomIds ? count($roomIds) : 'sudah suspend',
                ];
            }
        }

        $this->table(['Kontrak', 'Customer', 'Invoice tertua', 'Tgl invoice', 'Grace (hari)', 'Batas grace', 'Invoice lewat', 'Room di-suspend'], $rows);

        $this->line("Kontrak: {$contractsSuspended}; room: {$roomsSuspended}.");

        if ($apply) {
            $this->info('Selesai. Unsuspend dilakukan admin di tab Room & Rental setelah pembayaran diterima.');
        } else {
            $this->warn('Dry-run: belum ada yang di-suspend. Jalankan ulang dengan --apply untuk menyimpan.');
        }

        return self::SUCCESS;
    }
}
