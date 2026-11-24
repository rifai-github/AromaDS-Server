<?php

namespace App\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rental Report (spec 24 Nov 2026; jawaban klien: "total rental" = rental yang sudah di-service):
 * jumlah rental yang di-service per tanggal per branch.
 *
 * Service selesai = job service (service_first/service/service_routine) berstatus completed /
 * done_job / dpf, tanggal = BA date. Rental per job = baris job_advice_rooms di ruangan job itu
 * (rental utama job_schedule_rooms.job_advice_room_id + rental tambahan di
 * job_schedule_room_rentals), sama seperti InvoiceGenerationService. Ruangan job yang dibatalkan
 * atau ditandai [SUSPEND] tidak dihitung.
 */
class RentalServiceReport extends BusinessReport
{
    private const SERVICE_TYPES = ['service', 'service_first', 'service_routine'];

    private const DONE_STATUSES = ['completed', 'done_job', 'dpf'];

    public function __construct(private ReportData $data) {}

    public function key(): string
    {
        return 'rental-service';
    }

    public function title(): string
    {
        return 'Rental Report';
    }

    public function description(): string
    {
        return 'Jumlah rental yang sudah di-service per tanggal (BA date) per branch. Day = tanggal ke berapa, '
            .'Day Name = hari apa. Total Unit = jumlah unit dari rental yang di-service.';
    }

    public function dateLabel(): string
    {
        return 'Tanggal service (BA)';
    }

    public function columns(): array
    {
        return [
            'branch' => 'Branch',
            'date' => 'Tanggal',
            'day' => 'Day',
            'day_name' => 'Day Name',
            'jobs' => 'Jumlah Job',
            'total_rental' => 'Total Rental',
            'total_unit' => 'Total Unit',
        ];
    }

    public function numericColumns(): array
    {
        return ['day', 'jobs', 'total_rental', 'total_unit'];
    }

    public function rows(ReportFilters $filters): Collection
    {
        $roomBase = fn () => DB::table('job_schedule_rooms as jsr')
            ->whereNull('jsr.deleted_at')
            ->where(fn ($query) => $query->whereNull('jsr.status')->orWhere('jsr.status', '!=', 'cancelled'))
            ->where(fn ($query) => $query->whereNull('jsr.notes')->orWhereRaw('UPPER(jsr.notes) NOT LIKE ?', ['%[SUSPEND]%']));

        $primary = $roomBase()
            ->whereNotNull('jsr.job_advice_room_id')
            ->select('jsr.job_schedule_id', 'jsr.job_advice_room_id');
        $extra = $roomBase()
            ->join('job_schedule_room_rentals as rr', 'rr.job_schedule_room_id', '=', 'jsr.id')
            ->select('jsr.job_schedule_id', 'rr.job_advice_room_id');

        $lines = DB::query()
            ->fromSub($primary->union($extra), 'l') // UNION membuang rental ganda dalam satu job
            ->join('job_schedules as js', 'js.id', '=', 'l.job_schedule_id')
            ->join('job_advices as ja', 'ja.id', '=', 'js.job_advice_id')
            ->leftJoin('job_advice_rooms as jar', 'jar.id', '=', 'l.job_advice_room_id')
            ->leftJoin('buildings as b', 'b.id', '=', 'js.building_id')
            ->leftJoin('contracts as c', 'c.id', '=', 'ja.contract_id')
            ->leftJoin('quotations as q', 'q.id', '=', 'c.quotation_id')
            ->whereIn('ja.contract_id', $this->data->contracts()->select('contracts.id'))
            ->whereIn('js.type', self::SERVICE_TYPES)
            ->whereIn('js.status', self::DONE_STATUSES)
            ->whereNull('js.deleted_at')
            ->whereNull('ja.deleted_at')
            ->whereBetween('js.ba_date', [$filters->dateFrom->toDateString(), $filters->dateTo->toDateString()])
            ->get([
                'js.id as job_id', 'js.job_number', 'js.ba_date', 'b.branch_id as building_branch_id',
                'c.contract_number', 'q.branch_id as quotation_branch_id', 'l.job_advice_room_id', 'jar.quantity',
            ]);

        $rows = collect();
        foreach ($lines as $line) {
            // Branch job = prefix nomor job (cabang operasional), fallback cabang gedung.
            // Job tanpa nomor (job historis hasil generate jadwal Catalyst) memakai cabang
            // gedung, lalu cabang kontraknya (quotation / prefix nomor kontrak).
            $branch = $this->data->branch(null, $line->job_number)
                ?? $this->data->branch($line->building_branch_id, null)
                ?? $this->data->branch($line->quotation_branch_id, $line->contract_number);
            if ($filters->branchId && $branch?->id !== $filters->branchId) {
                continue;
            }

            $date = substr((string) $line->ba_date, 0, 10);
            $key = ($branch?->id ?? 0).'|'.$date;
            $row = $rows->get($key, [
                '_sort' => $date.'|'.($branch?->name ?? ''),
                '_jobs' => [],
                'branch' => $branch?->name ?? '-',
                'date' => $this->date($date),
                'day' => (int) substr($date, 8, 2),
                'day_name' => Carbon::parse($date)->locale('id')->isoFormat('dddd'),
                'jobs' => 0,
                'total_rental' => 0,
                'total_unit' => 0,
            ]);
            $row['_jobs'][$line->job_id] = true;
            $row['jobs'] = count($row['_jobs']);
            $row['total_rental']++;
            $row['total_unit'] += (int) ($line->quantity ?? 0);
            $rows->put($key, $row);
        }

        return $rows->sortBy('_sort')
            ->map(fn (array $row) => array_diff_key($row, ['_jobs' => true]))
            ->values();
    }
}
