<?php

namespace App\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * List Kontrak / Contract Management (spec 24 Nov 2026): kontrak mana yang Contract Balik-nya
 * sudah diunggah dan diverifikasi, dan status operasional (kode job terakhir) tiap kontrak.
 *
 * Tanggal kontrak = tanggal efektif = BA date pertama job awal kontrak (sama dengan
 * Contract::actual_start_date); kontrak yang belum punya BA memakai start_date.
 */
class ContractListReport extends BusinessReport
{
    /** Sama dengan Contract::START_DATE_JOB_TYPES. */
    private const START_JOB_TYPES = ['install', 'install_free', 'service', 'service_first', 'service_routine'];

    private const HIDDEN_STATUSES = ['draft', 'waiting_for_approval', 'rejected'];

    public function __construct(private ReportData $data) {}

    public function key(): string
    {
        return 'contract-list';
    }

    public function title(): string
    {
        return 'List Kontrak';
    }

    public function description(): string
    {
        return 'Status kontrak: Contract Balik (kontrak bertanda tangan + e-Materai) sudah diunggah dan diverifikasi atau belum, '
            .'serta status operasional dari job terakhir (CSR, IR, IF, RV, SUS, RMC = cancelled, dll; kosong bila belum ada job).';
    }

    public function dateLabel(): string
    {
        return 'Tanggal efektif (BA)';
    }

    public function columns(): array
    {
        return [
            'branch' => 'Branch',
            'department' => 'Department',
            'sales' => 'Sales Name',
            'contract_number' => 'Transaksi Number',
            'contract_type' => 'Contract Type',
            'customer' => 'Customer',
            'effective_date' => 'Tanggal Kontrak',
            'end_date' => 'Berakhir',
            'upload' => 'Upload',
            'confirm' => 'Confirm',
            'confirm_date' => 'Confirm Date',
            'status_ops' => 'Status Ops',
            'contract_note' => 'Contract Note',
            'internal_memo' => 'Internal Memo',
        ];
    }

    public function extraFilters(): array
    {
        return [
            'upload' => ['label' => 'Upload', 'options' => ['sudah' => 'Sudah', 'belum' => 'Belum']],
            'confirm' => ['label' => 'Confirm', 'options' => ['sudah' => 'Sudah', 'belum' => 'Belum']],
        ];
    }

    public function rows(ReportFilters $filters): Collection
    {
        $firstBa = DB::table('job_schedules as js')
            ->join('job_advices as ja', 'ja.id', '=', 'js.job_advice_id')
            ->whereNull('js.deleted_at')
            ->whereNull('ja.deleted_at')
            ->whereNotNull('js.ba_date')
            ->whereIn('js.type', self::START_JOB_TYPES)
            ->groupBy('ja.contract_id')
            ->select('ja.contract_id', DB::raw('MIN(js.ba_date) as first_ba_date'));

        $contracts = $this->data->contracts()
            ->leftJoinSub($firstBa, 'fba', 'fba.contract_id', '=', 'contracts.id')
            ->leftJoin('quotations as q', 'q.id', '=', 'contracts.quotation_id')
            ->leftJoin('customers as cu', 'cu.id', '=', 'contracts.customer_id')
            ->whereNotIn('contracts.contract_status', self::HIDDEN_STATUSES)
            ->whereRaw('COALESCE(fba.first_ba_date, contracts.start_date) BETWEEN ? AND ?', [
                $filters->dateFrom->toDateString(),
                $filters->dateTo->toDateString(),
            ])
            ->orderByRaw('COALESCE(fba.first_ba_date, contracts.start_date)')
            ->orderBy('contracts.contract_number')
            ->get([
                'contracts.id',
                'contracts.contract_number',
                'contracts.contract_type',
                'contracts.start_date',
                'contracts.end_date',
                'contracts.notes',
                'contracts.internal_remark',
                'contracts.marketing_id',
                'fba.first_ba_date',
                'q.branch_id as quotation_branch_id',
                'q.quotation_type',
                'q.existing_contract_id',
                'cu.name as customer_name',
            ]);

        $ids = $contracts->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sales = $this->data->salesPeople($contracts->pluck('marketing_id')->all());
        $balik = $this->data->contractBalik($ids);
        $jobs = $this->data->latestJobs($ids, now()->toDateString());

        return $contracts->map(function ($contract) use ($sales, $balik, $jobs) {
            $branch = $this->data->branch($contract->quotation_branch_id, $contract->contract_number);
            $person = $sales->get($contract->marketing_id);
            $file = $balik->get($contract->id);
            $job = $jobs->get($contract->id);

            return [
                '_branch_id' => $branch?->id,
                'branch' => $branch?->name ?? '-',
                'department' => $person?->department ?: '-',
                'sales' => $person?->name ?: '-',
                'contract_number' => $contract->contract_number,
                'contract_type' => $this->contractType($contract),
                'customer' => $contract->customer_name ?: '-',
                'effective_date' => $this->date($contract->first_ba_date ?: $contract->start_date),
                'end_date' => $this->date($this->endDate($contract)),
                'upload' => $this->yesNo((bool) $file?->uploaded),
                'confirm' => $this->yesNo((bool) $file?->verified),
                'confirm_date' => $this->date($file?->verified_at),
                'status_ops' => $job ? ($this->data->jobStatusCode($job->status, $job->job_number) ?? '-') : '-',
                'contract_note' => $this->data->limit($contract->notes, 120),
                'internal_memo' => $this->data->limit($contract->internal_remark, 120),
            ];
        })->filter(function (array $row) use ($filters) {
            if ($filters->branchId && $row['_branch_id'] !== $filters->branchId) {
                return false;
            }
            if (($upload = $filters->extra('upload')) && $row['upload'] !== ucfirst($upload)) {
                return false;
            }
            if (($confirm = $filters->extra('confirm')) && $row['confirm'] !== ucfirst($confirm)) {
                return false;
            }

            return true;
        })->values();
    }

    /** Renewal = quotation renewal (atau kontrak lama terisi), fallback contract_type hasil import. */
    private function contractType(object $contract): string
    {
        $isRenewal = $contract->quotation_type === 'renewal'
            || ! empty($contract->existing_contract_id)
            || $contract->contract_type === 'renewal';

        return $isRenewal ? 'Renewal' : 'New';
    }

    /** Sama dengan Contract::actual_end_date: BA pertama + durasi kontrak - 1 hari, fallback end_date. */
    private function endDate(object $contract): ?string
    {
        if ($contract->first_ba_date && $contract->start_date && $contract->end_date) {
            // Rumus sama persis dengan Contract::getActualEndDateAttribute (bulan penuh + sisa hari).
            $start = Carbon::parse($contract->start_date);
            $endExclusive = Carbon::parse($contract->end_date)->addDay();
            $months = (int) floor($start->diffInMonths($endExclusive));
            $leftoverDays = (int) $start->copy()->addMonthsNoOverflow($months)->diffInDays($endExclusive);

            return Carbon::parse($contract->first_ba_date)->addMonthsNoOverflow($months)->addDays($leftoverDays)->subDay()->toDateString();
        }

        return $contract->end_date;
    }
}
