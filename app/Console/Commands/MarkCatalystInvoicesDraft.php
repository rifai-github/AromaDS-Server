<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Klien 24 Nov 2026: semua invoice hasil import Catalyst bersifat draft. Importer sudah diubah
 * (ImportCatalystInvoicesFromExport::mapInvoiceStatus); command ini merapikan invoice yang
 * telanjur diimport sebagai approved/tax_approved/sent.
 *
 * Hanya invoice yang tercatat di source_import_maps (FINCustInvHd) dan belum ada pembayaran di
 * ERP (total_paid 0/NULL). Cancelled dan paid tidak disentuh. Default dry-run.
 */
class MarkCatalystInvoicesDraft extends Command
{
    protected $signature = 'catalyst:invoices-mark-draft {--apply : Ubah status ke draft}';

    protected $description = 'Ubah invoice import Catalyst yang masih approved/tax_approved/sent menjadi draft';

    private const FROM_STATUSES = ['approved', 'tax_approved', 'sent'];

    public function handle(): int
    {
        $query = DB::table('invoices as i')
            ->join('source_import_maps as m', function ($join) {
                $join->on('m.target_id', '=', 'i.id')
                    ->where('m.source_system', 'catalyst')
                    ->where('m.source_table', 'FINCustInvHd')
                    ->where('m.target_table', 'invoices');
            })
            ->whereNull('i.deleted_at')
            ->whereIn('i.invoice_status', self::FROM_STATUSES)
            ->where(fn ($q) => $q->whereNull('i.total_paid')->orWhere('i.total_paid', '<=', 0));

        $byStatus = (clone $query)->select('i.invoice_status', DB::raw('COUNT(*) as total'))->groupBy('i.invoice_status')->pluck('total', 'invoice_status');
        $total = (int) $byStatus->sum();

        $this->info('Invoice import Catalyst yang akan jadi draft: '.$total
            .($total ? ' ('.$byStatus->map(fn ($n, $status) => "{$status}={$n}")->implode(', ').')' : ''));

        $paidSkipped = DB::table('invoices as i')
            ->join('source_import_maps as m', fn ($join) => $join->on('m.target_id', '=', 'i.id')
                ->where('m.source_system', 'catalyst')->where('m.source_table', 'FINCustInvHd'))
            ->whereNull('i.deleted_at')
            ->whereIn('i.invoice_status', self::FROM_STATUSES)
            ->where('i.total_paid', '>', 0)
            ->count();
        if ($paidSkipped) {
            $this->warn("{$paidSkipped} invoice dilewati karena sudah ada pembayaran di ERP.");
        }

        if (! $this->option('apply')) {
            $this->warn('Dry-run: belum ada yang diubah. Jalankan ulang dengan --apply.');

            return self::SUCCESS;
        }

        $updated = DB::table('invoices')
            ->whereIn('id', $query->pluck('i.id'))
            ->update(['invoice_status' => 'draft', 'updated_at' => now()]);

        $this->info("Selesai: {$updated} invoice diubah ke draft.");

        return self::SUCCESS;
    }
}
