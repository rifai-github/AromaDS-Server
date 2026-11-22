<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractRoom;
use App\Services\Operational\ContractRoomSuspensionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * MOM 19 Nov 2026 p3: grace period ada di level customer (customers.grace_period_days). Bila
 * invoice lewat jatuh tempo + grace period dan belum lunas, kontraknya di-suspend: semua room
 * kontrak itu dikunci (ContractRoomSuspensionService) sehingga job-nya off sampai di-unsuspend
 * admin di tab Room & Rental.
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

    protected $description = 'Suspend semua room kontrak yang punya invoice belum lunas melewati jatuh tempo + grace period customer';

    private const ISSUED_STATUSES = ['approved', 'tax_approved', 'sent', 'overdue'];

    private const CLOSED_STATUSES = ['paid', 'cancelled', 'Paid', 'Cancelled', 'void'];

    public function handle(ContractRoomSuspensionService $suspensions): int
    {
        $apply = (bool) $this->option('apply');
        $today = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : now()->startOfDay();

        $invoices = DB::table('invoices as i')
            ->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->whereNull('i.deleted_at')
            ->whereNotNull('i.contract_id')
            ->whereNotNull('i.due_date')
            ->whereNotIn('i.status', self::CLOSED_STATUSES)
            ->whereNotIn('i.invoice_status', self::CLOSED_STATUSES)
            ->where(function ($query) {
                $query->whereIn('i.status', self::ISSUED_STATUSES)
                    ->orWhereIn('i.invoice_status', self::ISSUED_STATUSES);
            })
            ->select('i.id', 'i.invoice_number', 'i.contract_id', 'i.due_date', 'c.name as customer_name', 'c.grace_period_days')
            ->orderBy('i.due_date')
            ->get()
            ->filter(function ($invoice) use ($today) {
                $limit = Carbon::parse($invoice->due_date)->startOfDay()->addDays((int) ($invoice->grace_period_days ?? 0));

                return $today->gt($limit);
            });

        $this->info('Kontrak dengan invoice lewat grace period per '.$today->format('d/m/Y').' - mode '.($apply ? 'apply' : 'dry-run').'.');

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
            $grace = (int) ($oldest->grace_period_days ?? 0);

            if ($apply && $roomIds) {
                $note = "Otomatis: invoice {$oldest->invoice_number} jatuh tempo ".Carbon::parse($oldest->due_date)->format('d/m/Y')
                    ." lewat grace period {$grace} hari.";
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
                    Carbon::parse($oldest->due_date)->format('d/m/Y'),
                    $grace,
                    $contractInvoices->count(),
                    $roomIds ? count($roomIds) : 'sudah suspend',
                ];
            }
        }

        $this->table(['Kontrak', 'Customer', 'Invoice tertua', 'Jatuh tempo', 'Grace (hari)', 'Invoice lewat', 'Room di-suspend'], $rows);

        $this->line("Kontrak: {$contractsSuspended}; room: {$roomsSuspended}.");

        if ($apply) {
            $this->info('Selesai. Unsuspend dilakukan admin di tab Room & Rental setelah pembayaran diterima.');
        } else {
            $this->warn('Dry-run: belum ada yang di-suspend. Jalankan ulang dengan --apply untuk menyimpan.');
        }

        return self::SUCCESS;
    }
}
