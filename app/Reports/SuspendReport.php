<?php

namespace App\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Report Suspend (spec 24 Nov 2026): customer mana yang job / kontraknya sudah ter-suspend.
 * Jawaban klien: dua-duanya ditampilkan:
 *  - job berstatus `suspend` (kode SUS; nomor job tetap nomor CSR aslinya),
 *  - ruangan kontrak yang sedang di-suspend (contract_room_suspensions yang belum di-unsuspend,
 *    termasuk hasil suspend otomatis grace period).
 */
class SuspendReport extends BusinessReport
{
    public function __construct(private ReportData $data) {}

    public function key(): string
    {
        return 'suspend';
    }

    public function title(): string
    {
        return 'Report Suspend';
    }

    public function description(): string
    {
        return 'Customer yang job atau ruangan kontraknya sudah ter-suspend: job berstatus SUS, dan ruangan kontrak '
            .'yang sedang di-suspend (manual atau otomatis karena lewat grace period) dan belum di-unsuspend.';
    }

    public function dateLabel(): string
    {
        return 'Tanggal suspend';
    }

    public function columns(): array
    {
        return [
            'customer' => 'FP Customer Name',
            'customer_code' => 'Customer Code',
            'branch' => 'Branch',
            'contract_number' => 'Nomor Kontrak',
            'kind' => 'Jenis',
            'suspend' => 'Suspend',
            'suspend_date' => 'Tanggal Suspend',
            'notes' => 'Keterangan',
        ];
    }

    public function extraFilters(): array
    {
        return [
            'kind' => ['label' => 'Jenis', 'options' => ['job' => 'Job SUS', 'room' => 'Ruangan kontrak']],
        ];
    }

    public function rows(ReportFilters $filters): Collection
    {
        $from = $filters->dateFrom->toDateString();
        $to = $filters->dateTo->toDateString();
        $contractIds = $this->data->contracts()->select('contracts.id');
        $rows = collect();

        if ($filters->extra('kind') !== 'room') {
            $jobs = DB::table('job_schedules as js')
                ->join('job_advices as ja', 'ja.id', '=', 'js.job_advice_id')
                ->join('contracts as c', 'c.id', '=', 'ja.contract_id')
                ->leftJoin('quotations as q', 'q.id', '=', 'c.quotation_id')
                ->leftJoin('customers as cu', 'cu.id', '=', 'c.customer_id')
                ->whereIn('c.id', $contractIds)
                ->where('js.status', 'suspend')
                ->whereNull('js.deleted_at')
                ->whereNull('ja.deleted_at')
                ->whereRaw('COALESCE(js.ba_date, DATE(js.updated_at)) BETWEEN ? AND ?', [$from, $to])
                ->get([
                    'js.job_number', 'js.building_name', 'js.ba_date', 'js.updated_at',
                    'c.contract_number', 'q.branch_id as quotation_branch_id',
                    'cu.name as customer_name', 'cu.customer_code',
                ]);

            foreach ($jobs as $job) {
                $branch = $this->data->branch($job->quotation_branch_id, $job->contract_number);
                $rows->push([
                    '_branch_id' => $branch?->id,
                    '_sort' => $job->ba_date ?: substr((string) $job->updated_at, 0, 10),
                    'customer' => $job->customer_name ?: '-',
                    'customer_code' => $job->customer_code ?: '-',
                    'branch' => $branch?->name ?? '-',
                    'contract_number' => $job->contract_number,
                    'kind' => 'Job SUS',
                    'suspend' => $job->job_number ?: '-',
                    'suspend_date' => $this->date($job->ba_date ?: $job->updated_at),
                    'notes' => $this->data->limit($job->building_name, 60),
                ]);
            }
        }

        if ($filters->extra('kind') !== 'job') {
            $rooms = DB::table('contract_room_suspensions as s')
                ->join('contracts as c', 'c.id', '=', 's.contract_id')
                ->leftJoin('quotations as q', 'q.id', '=', 'c.quotation_id')
                ->leftJoin('customers as cu', 'cu.id', '=', 'c.customer_id')
                ->leftJoin('contract_rooms as cr', 'cr.id', '=', 's.contract_room_id')
                ->leftJoin('master_rooms as mr', 'mr.id', '=', DB::raw('COALESCE(s.room_id, cr.room_id)'))
                ->whereIn('c.id', $contractIds)
                ->whereNull('s.unsuspended_at')
                ->whereBetween('s.suspend_date', [$from, $to])
                ->get([
                    's.suspend_date', 's.notes',
                    'mr.room_name',
                    'c.contract_number', 'q.branch_id as quotation_branch_id',
                    'cu.name as customer_name', 'cu.customer_code',
                ]);

            foreach ($rooms as $room) {
                $branch = $this->data->branch($room->quotation_branch_id, $room->contract_number);
                $rows->push([
                    '_branch_id' => $branch?->id,
                    '_sort' => (string) $room->suspend_date,
                    'customer' => $room->customer_name ?: '-',
                    'customer_code' => $room->customer_code ?: '-',
                    'branch' => $branch?->name ?? '-',
                    'contract_number' => $room->contract_number,
                    'kind' => 'Ruangan kontrak',
                    'suspend' => $room->room_name ?: '-',
                    'suspend_date' => $this->date($room->suspend_date),
                    'notes' => $this->data->limit($room->notes, 80),
                ]);
            }
        }

        return $rows
            ->filter(fn (array $row) => ! $filters->branchId || $row['_branch_id'] === $filters->branchId)
            ->sortBy([['customer', 'asc'], ['contract_number', 'asc'], ['_sort', 'asc']])
            ->values();
    }
}
