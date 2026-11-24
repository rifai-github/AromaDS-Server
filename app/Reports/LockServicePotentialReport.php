<?php

namespace App\Reports;

use App\Models\Contract;
use App\Services\Finance\GracePeriodService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Potensi Lock Service (spec 24 Nov 2026): kontrak yang punya invoice belum lunas dan BELUM
 * di-suspend, beserta tanggal service yang akan terkunci bila invoice tetap tidak dibayar.
 *
 * Aturan grace dari GracePeriodService (jawaban klien 24 Nov 2026): grace dihitung dari tanggal
 * invoice terbit, grace kosong/0 = 60 hari, suspend otomatis hanya untuk invoice mulai
 * 1 Jan 2027. "Lock Per" = jadwal service berikutnya (belum dikerjakan) setelah grace invoice
 * terlama habis; jadwal sebelum itu masih boleh dikerjakan.
 */
class LockServicePotentialReport extends BusinessReport
{
    private const SERVICE_TYPES = ['service', 'service_first', 'service_routine'];

    private const FINAL_JOB_STATUSES = ['completed', 'done_job', 'cancelled', 'terminated', 'suspend', 'dpf'];

    public function __construct(private ReportData $data, private GracePeriodService $grace) {}

    public function key(): string
    {
        return 'lock-service';
    }

    public function title(): string
    {
        return 'Potensi Lock Service';
    }

    public function description(): string
    {
        $from = $this->grace->autoSuspendFrom();

        return 'Kontrak dengan invoice belum lunas yang belum di-suspend. Lock Per = jadwal service berikutnya yang akan terkunci '
            .'bila invoice terlama tetap belum dibayar setelah grace period (dihitung dari tanggal invoice; grace kosong = '
            .$this->grace->defaultGraceDays().' hari).'
            .($from ? ' Suspend otomatis hanya untuk invoice terbit mulai '.$from->format('d/m/Y').'.' : '');
    }

    public function dateLabel(): string
    {
        return 'Lock Per';
    }

    public function columns(): array
    {
        return [
            'department' => 'Department',
            'sales' => 'Sales Name',
            'contract_number' => 'Nomor Contract',
            'top' => 'TOP',
            'customer' => 'Customer Name',
            'customer_code' => 'Customer Code',
            'branch' => 'Branch',
            'building' => 'Building',
            'schedule' => 'Schedule',
            'tinv' => 'TInv',
            'tout' => 'TOut',
            'oldest_invoice_date' => 'Tgl Inv Terkecil',
            'grace' => 'Grace Period',
            'lock_per' => 'Lock Per',
        ];
    }

    public function numericColumns(): array
    {
        return ['tinv', 'tout', 'grace'];
    }

    public function extraFilters(): array
    {
        $from = $this->grace->autoSuspendFrom();

        return $from ? [
            'invoice_scope' => [
                'label' => 'Invoice',
                'placeholder' => 'Sesuai aturan suspend (mulai '.$from->format('d/m/Y').')',
                'options' => ['all' => 'Termasuk invoice sebelum '.$from->format('d/m/Y')],
            ],
        ] : [];
    }

    public function rows(ReportFilters $filters): Collection
    {
        $today = now()->startOfDay();
        $accessible = array_flip($this->data->contracts()->pluck('contracts.id')->map(fn ($id) => (int) $id)->all());

        $invoices = $this->grace->openInvoices($filters->extra('invoice_scope') !== 'all')
            ->filter(fn ($invoice) => isset($accessible[(int) $invoice->contract_id]));
        if ($invoices->isEmpty()) {
            return collect();
        }

        // Kontrak yang ruangannya sudah di-suspend (berlaku hari ini) bukan lagi "potensi".
        $suspended = DB::table('contract_room_suspensions')
            ->whereIn('contract_id', $invoices->pluck('contract_id')->unique()->all())
            ->whereNull('unsuspended_at')
            ->where('suspend_date', '<=', $today->toDateString())
            ->pluck('contract_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $byContract = $invoices->groupBy('contract_id')->reject(fn ($rows, $contractId) => $suspended->has((int) $contractId));
        $contractIds = $byContract->keys()->map(fn ($id) => (int) $id)->all();
        if ($contractIds === []) {
            return collect();
        }

        $contracts = Contract::with(['customer:id,name,customer_code', 'quotation:id,branch_id,terms_of_payment'])
            ->whereIn('id', $contractIds)
            ->get()
            ->keyBy('id');
        $sales = $this->data->salesPeople($contracts->pluck('marketing_id')->all());
        $buildings = $this->data->buildingNames($contractIds);
        $schedules = $this->pendingServiceDates($contractIds, $today);

        $rows = collect();
        foreach ($byContract as $contractId => $contractInvoices) {
            $contract = $contracts->get((int) $contractId);
            if (! $contract) {
                continue;
            }

            $oldest = $contractInvoices->first();
            $dates = $schedules->get((int) $contractId, collect());
            $nextSchedule = $dates->first();
            // Hari terakhir grace = tgl invoice + grace; service sesudahnya terkunci bila belum bayar.
            $lockPer = $dates->first(fn (string $date) => Carbon::parse($date)->gt($oldest->grace_ends_on));

            if (! $lockPer || ! Carbon::parse($lockPer)->betweenIncluded($filters->dateFrom, $filters->dateTo)) {
                continue;
            }

            $branch = $this->data->branch($contract->quotation?->branch_id, $contract->contract_number);
            if ($filters->branchId && $branch?->id !== $filters->branchId) {
                continue;
            }
            $person = $sales->get($contract->marketing_id);

            $rows->push([
                '_lock' => $lockPer,
                'department' => $person?->department ?: '-',
                'sales' => $person?->name ?: '-',
                'contract_number' => $contract->contract_number,
                'top' => $contract->display_term_of_payment ?: '-',
                'customer' => $contract->customer?->name ?? '-',
                'customer_code' => $contract->customer?->customer_code ?? '-',
                'branch' => $branch?->name ?? '-',
                'building' => $this->data->limit($buildings->get((int) $contractId), 50),
                'schedule' => $this->date($nextSchedule),
                'tinv' => $contractInvoices->count(),
                'tout' => number_format((float) $contractInvoices->sum('amount'), 0, ',', '.'),
                'oldest_invoice_date' => $this->date($oldest->effective_invoice_date),
                'grace' => $oldest->grace_days.' hari',
                'lock_per' => $this->date($lockPer),
            ]);
        }

        return $rows->sortBy([['_lock', 'asc'], ['contract_number', 'asc']])->values();
    }

    /**
     * Tanggal jadwal service yang belum dikerjakan, mulai hari ini, per kontrak (urut naik).
     *
     * @param  array<int, int>  $contractIds
     * @return Collection<int, Collection<int, string>>
     */
    private function pendingServiceDates(array $contractIds, Carbon $today): Collection
    {
        return DB::table('job_schedules as js')
            ->join('job_advices as ja', 'ja.id', '=', 'js.job_advice_id')
            ->whereIn('ja.contract_id', $contractIds)
            ->whereIn('js.type', self::SERVICE_TYPES)
            ->whereNotIn('js.status', self::FINAL_JOB_STATUSES)
            ->whereNull('js.deleted_at')
            ->whereNull('ja.deleted_at')
            ->where('js.schedule_date', '>=', $today->toDateString())
            ->orderBy('js.schedule_date')
            ->get(['ja.contract_id', 'js.schedule_date'])
            ->groupBy(fn ($row) => (int) $row->contract_id)
            ->map(fn (Collection $rows) => $rows->pluck('schedule_date')->map(fn ($date) => substr((string) $date, 0, 10))->unique()->values());
    }
}
