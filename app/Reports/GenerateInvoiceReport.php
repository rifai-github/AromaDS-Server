<?php

namespace App\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Report Generate Invoice (spec 24 Nov 2026): job yang sudah selesai (ada BA date) dan
 * seharusnya sudah punya nomor invoice. Job selesai tanpa invoice disorot supaya diperiksa.
 *
 * Job selesai = status completed / done_job / dpf dengan BA date (status yang ditagih
 * InvoiceGenerationService). Job gratis (Install Free / Remove Free) tidak ditagih, jadi tidak
 * ditampilkan. Satu nomor job bisa punya beberapa baris job_schedules (per ruangan/gedung);
 * laporan ini satu baris per nomor job. Nomor invoice dari invoice_rental_details.job_no.
 */
class GenerateInvoiceReport extends BusinessReport
{
    private const DONE_STATUSES = ['completed', 'done_job', 'dpf'];

    private const FREE_TYPES = ['install_free', 'remove_free'];

    public function __construct(private ReportData $data) {}

    public function key(): string
    {
        return 'generate-invoice';
    }

    public function title(): string
    {
        return 'Report Generate Invoice';
    }

    public function description(): string
    {
        return 'Job yang sudah selesai (ada BA date) dan seharusnya sudah punya nomor invoice. Baris kuning = job selesai '
            .'tetapi belum ada nomor invoice. Qty Free = jumlah unit free rental di job itu (- bila job tak punya data rental).';
    }

    public function dateLabel(): string
    {
        return 'BA date';
    }

    public function columns(): array
    {
        return [
            'job_number' => 'Nomor Job',
            'contract_number' => 'Nomor Kontrak',
            'customer' => 'Customer',
            'branch' => 'Branch',
            'job_code' => 'Jenis',
            'qty_free' => 'Qty Free',
            'ba_date' => 'BA Date',
            'invoice_number' => 'Nomor Invoice',
        ];
    }

    public function numericColumns(): array
    {
        return ['qty_free'];
    }

    public function extraFilters(): array
    {
        return [
            'invoice' => ['label' => 'Nomor Invoice', 'options' => ['ada' => 'Sudah ada', 'belum' => 'Belum ada (-)']],
        ];
    }

    public function highlightRow(array $row): bool
    {
        return ($row['invoice_number'] ?? '-') === '-';
    }

    public function rows(ReportFilters $filters): Collection
    {
        $jobs = DB::table('job_schedules as js')
            ->join('job_advices as ja', 'ja.id', '=', 'js.job_advice_id')
            ->join('contracts as c', 'c.id', '=', 'ja.contract_id')
            ->leftJoin('quotations as q', 'q.id', '=', 'c.quotation_id')
            ->leftJoin('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->whereIn('c.id', $this->data->contracts()->select('contracts.id'))
            ->whereIn('js.status', self::DONE_STATUSES)
            ->whereNotIn('js.type', self::FREE_TYPES)
            ->whereNotNull('js.job_number')
            ->whereNull('js.deleted_at')
            ->whereNull('ja.deleted_at')
            ->whereBetween('js.ba_date', [$filters->dateFrom->toDateString(), $filters->dateTo->toDateString()])
            ->orderBy('js.ba_date')
            ->orderBy('js.job_number')
            ->get([
                'js.id', 'js.job_number', 'js.status', 'js.ba_date',
                'c.contract_number', 'q.branch_id as quotation_branch_id', 'cu.name as customer_name',
            ]);

        if ($jobs->isEmpty()) {
            return collect();
        }

        $qtyFree = $this->qtyFreePerJob($jobs->pluck('id')->all());
        $invoices = $this->invoiceNumbers($jobs->pluck('job_number')->unique()->values()->all());

        return $jobs->groupBy('job_number')->map(function (Collection $rows, string $jobNumber) use ($qtyFree, $invoices) {
            $job = $rows->first();
            $branch = $this->data->branch(null, $jobNumber) ?? $this->data->branch($job->quotation_branch_id, $job->contract_number);

            $hasRentalData = $rows->contains(fn ($row) => $qtyFree->has((int) $row->id));
            $free = $rows->sum(fn ($row) => (int) $qtyFree->get((int) $row->id, 0));

            return [
                '_branch_id' => $branch?->id,
                'job_number' => $jobNumber,
                'contract_number' => $job->contract_number,
                'customer' => $job->customer_name ?: '-',
                'branch' => $branch?->name ?? '-',
                'job_code' => $this->data->jobStatusCode($job->status, $jobNumber) ?? '-',
                'qty_free' => $hasRentalData ? $free : '-',
                'ba_date' => $this->date($rows->min('ba_date')),
                'invoice_number' => $invoices->get($jobNumber) ?: '-',
            ];
        })->filter(function (array $row) use ($filters) {
            if ($filters->branchId && $row['_branch_id'] !== $filters->branchId) {
                return false;
            }
            if ($filters->extra('invoice') === 'ada' && $row['invoice_number'] === '-') {
                return false;
            }
            if ($filters->extra('invoice') === 'belum' && $row['invoice_number'] !== '-') {
                return false;
            }

            return true;
        })->values();
    }

    /**
     * Jumlah qty_free rental per job_schedules.id (hanya job yang punya data rental).
     *
     * @param  array<int, int>  $jobIds
     * @return Collection<int, int>
     */
    private function qtyFreePerJob(array $jobIds): Collection
    {
        $primary = DB::table('job_schedule_rooms as jsr')
            ->whereIn('jsr.job_schedule_id', $jobIds)
            ->whereNull('jsr.deleted_at')
            ->whereNotNull('jsr.job_advice_room_id')
            ->select('jsr.job_schedule_id', 'jsr.job_advice_room_id');
        $extra = DB::table('job_schedule_rooms as jsr')
            ->join('job_schedule_room_rentals as rr', 'rr.job_schedule_room_id', '=', 'jsr.id')
            ->whereIn('jsr.job_schedule_id', $jobIds)
            ->whereNull('jsr.deleted_at')
            ->select('jsr.job_schedule_id', 'rr.job_advice_room_id');

        return DB::query()
            ->fromSub($primary->union($extra), 'l')
            ->leftJoin('job_advice_rooms as jar', 'jar.id', '=', 'l.job_advice_room_id')
            ->groupBy('l.job_schedule_id')
            ->select('l.job_schedule_id', DB::raw('COALESCE(SUM(jar.qty_free), 0) as qty_free'))
            ->pluck('qty_free', 'job_schedule_id')
            ->mapWithKeys(fn ($qty, $jobId) => [(int) $jobId => (int) $qty]);
    }

    /**
     * Nomor invoice (tidak dibatalkan) per nomor job, digabung bila lebih dari satu.
     *
     * @param  array<int, string>  $jobNumbers
     * @return Collection<string, string>
     */
    private function invoiceNumbers(array $jobNumbers): Collection
    {
        return DB::table('invoice_rental_details as d')
            ->join('invoices as i', 'i.id', '=', 'd.invoice_id')
            ->whereIn('d.job_no', $jobNumbers)
            ->whereNull('i.deleted_at')
            ->where(fn ($query) => $query->whereNull('i.invoice_status')->orWhere('i.invoice_status', '!=', 'cancelled'))
            ->select('d.job_no', 'i.invoice_number')
            ->distinct()
            ->orderBy('i.invoice_number')
            ->get()
            ->groupBy('job_no')
            ->map(fn (Collection $rows) => $rows->pluck('invoice_number')->filter()->unique()->implode(', '));
    }
}
