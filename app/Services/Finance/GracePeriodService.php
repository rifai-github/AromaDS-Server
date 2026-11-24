<?php

namespace App\Services\Finance;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aturan grace period (jawaban klien 24 Nov 2026, spec Halaman Report):
 *  - grace period ada di master customer (customers.grace_period_days) dan dihitung dari
 *    TANGGAL INVOICE TERBIT, bukan dari jatuh tempo;
 *  - grace kosong / 0 memakai default (config contract.grace_default_days, 60 hari);
 *  - lewat tanggal invoice + grace dan belum lunas -> semua job kontrak itu di-suspend;
 *  - suspend otomatis hanya untuk invoice yang terbit mulai contract.grace_auto_suspend_from
 *    (1 Jan 2027) supaya invoice lama hasil import tidak men-suspend massal.
 *
 * Dipakai command contracts:suspend-past-grace dan laporan Potensi Lock Service.
 */
class GracePeriodService
{
    public const ISSUED_STATUSES = ['approved', 'tax_approved', 'sent', 'overdue'];

    public const CLOSED_STATUSES = ['paid', 'cancelled', 'Paid', 'Cancelled', 'void'];

    public function defaultGraceDays(): int
    {
        return max(1, (int) config('contract.grace_default_days', 60));
    }

    public function graceDays(mixed $customerGraceDays): int
    {
        $days = (int) ($customerGraceDays ?? 0);

        return $days > 0 ? $days : $this->defaultGraceDays();
    }

    /** Tanggal invoice mulai berlaku suspend otomatis, atau null bila tanpa batas. */
    public function autoSuspendFrom(): ?Carbon
    {
        $from = config('contract.grace_auto_suspend_from');

        return $from ? Carbon::parse($from)->startOfDay() : null;
    }

    /** Hari terakhir masih dalam grace: tanggal invoice + grace. Lewat hari ini = kena suspend. */
    public function graceEndsOn(string|\DateTimeInterface $invoiceDate, mixed $customerGraceDays): Carbon
    {
        return Carbon::parse($invoiceDate)->startOfDay()->addDays($this->graceDays($customerGraceDays));
    }

    /**
     * Invoice terbit yang belum lunas, lengkap dengan tanggal invoice efektif, grace customer, dan
     * batas grace. Diurutkan tanggal invoice terlama dulu.
     *
     * @param  bool  $onlyAutoSuspendScope  true = hanya invoice mulai tanggal batas suspend otomatis
     * @return Collection<int, object>
     */
    public function openInvoices(bool $onlyAutoSuspendScope = true, ?array $contractIds = null): Collection
    {
        $query = $this->openInvoiceQuery()
            ->select(
                'i.id',
                'i.invoice_number',
                'i.contract_id',
                'i.customer_id',
                'i.due_date',
                DB::raw('COALESCE(i.invoice_date, DATE(i.created_at)) as effective_invoice_date'),
                DB::raw('COALESCE(i.grand_total, i.total_amount, 0) as amount'),
                'c.name as customer_name',
                'c.grace_period_days'
            )
            ->orderByRaw('COALESCE(i.invoice_date, DATE(i.created_at))')
            ->orderBy('i.id');

        if ($onlyAutoSuspendScope && ($from = $this->autoSuspendFrom())) {
            $query->whereRaw('COALESCE(i.invoice_date, DATE(i.created_at)) >= ?', [$from->toDateString()]);
        }
        if ($contractIds !== null) {
            $query->whereIn('i.contract_id', $contractIds ?: [0]);
        }

        return $query->get()->map(function ($invoice) {
            $invoice->grace_days = $this->graceDays($invoice->grace_period_days);
            $invoice->grace_ends_on = $this->graceEndsOn($invoice->effective_invoice_date, $invoice->grace_period_days);

            return $invoice;
        });
    }

    /** Invoice yang sudah lewat grace per tanggal acuan (yang memicu suspend). */
    public function invoicesPastGrace(Carbon $today): Collection
    {
        $today = $today->copy()->startOfDay();

        return $this->openInvoices()->filter(fn ($invoice) => $today->gt($invoice->grace_ends_on))->values();
    }

    private function openInvoiceQuery(): Builder
    {
        return DB::table('invoices as i')
            ->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->whereNull('i.deleted_at')
            ->whereNotNull('i.contract_id')
            ->whereNotIn('i.status', self::CLOSED_STATUSES)
            ->whereNotIn('i.invoice_status', self::CLOSED_STATUSES)
            ->where(function ($query) {
                $query->whereIn('i.status', self::ISSUED_STATUSES)
                    ->orWhereIn('i.invoice_status', self::ISSUED_STATUSES);
            });
    }
}
