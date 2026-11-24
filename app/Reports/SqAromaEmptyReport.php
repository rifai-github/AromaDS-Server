<?php

namespace App\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SQ Aroma Kosong (klien 24 Nov 2026): Pak Hengky perlu melihat SQ mana yang aroma ruangannya
 * masih kosong (umumnya SQ hasil import Catalyst). Selama kosong, Material Assign membolehkan
 * semua aroma sekategori; aroma SQ terisi otomatis begitu inventory issuing dari Material Assign
 * terbit (App\Services\Operational\QuotationAromaGapService).
 *
 * Hanya SQ yang dipakai kontrak aktif/berjalan, karena hanya itu yang masih menghasilkan job.
 */
class SqAromaEmptyReport extends BusinessReport
{
    public function __construct(private ReportData $data) {}

    public function key(): string
    {
        return 'sq-aroma-empty';
    }

    public function title(): string
    {
        return 'SQ Aroma Kosong';
    }

    public function description(): string
    {
        return 'Ruangan SQ (kontrak aktif yang belum berakhir) yang aromanya masih kosong. Di Material Assign ruangan ini boleh memilih semua aroma '
            .'sekategori; aroma SQ terisi otomatis setelah inventory issuing terbit.';
    }

    public function dateLabel(): string
    {
        return 'Tanggal mulai kontrak';
    }

    /** Data import mencakup kontrak lama: default 5 tahun ke belakang s/d 1 tahun ke depan. */
    public function defaultRange(): array
    {
        return [now()->subYears(5)->startOfDay(), now()->addYear()->startOfDay()];
    }

    public function columns(): array
    {
        return [
            'quotation_number' => 'Nomor SQ',
            'contract_number' => 'Nomor Kontrak',
            'customer' => 'Customer',
            'branch' => 'Branch',
            'building' => 'Gedung',
            'room' => 'Ruangan',
            'start_date' => 'Mulai Kontrak',
            'end_date' => 'Akhir Kontrak',
        ];
    }

    public function rows(ReportFilters $filters): Collection
    {
        $rows = $this->data->contracts()
            ->join('quotations as q', 'q.id', '=', 'contracts.quotation_id')
            ->join('quotation_rooms as qr', 'qr.quotation_id', '=', 'q.id')
            ->leftJoin('customers as cu', 'cu.id', '=', 'contracts.customer_id')
            ->leftJoin('master_rooms as mr', 'mr.id', '=', 'qr.room_id')
            ->leftJoin('buildings as b', 'b.id', '=', 'mr.building_id')
            ->whereIn('contracts.contract_status', ['active', 'running'])
            // Status kontrak import sering masih "active" walau masa kontraknya sudah habis.
            ->where('contracts.end_date', '>=', now()->toDateString())
            ->whereNull('q.deleted_at')
            ->whereNull('qr.deleted_at')
            ->whereNull('qr.aroma_product_id')
            ->where(fn ($query) => $query->whereNull('qr.aroma_variant')->orWhere('qr.aroma_variant', ''))
            ->whereBetween('contracts.start_date', [$filters->dateFrom->toDateString(), $filters->dateTo->toDateString()])
            ->orderBy('q.quotation_number')
            ->orderBy('qr.room_name')
            // Satu ruangan bisa punya beberapa baris quotation_rooms (per rental): tampil sekali.
            ->distinct()
            ->get([
                'q.quotation_number', 'q.branch_id as quotation_branch_id',
                'contracts.contract_number', 'contracts.start_date', 'contracts.end_date',
                'cu.name as customer_name', 'qr.room_name', 'b.nama_gedung', 'b.name as building_raw_name',
            ]);

        return $rows->map(function ($row) {
            $branch = $this->data->branch($row->quotation_branch_id, $row->contract_number);

            return [
                '_branch_id' => $branch?->id,
                'quotation_number' => $row->quotation_number ?: '-',
                'contract_number' => $row->contract_number,
                'customer' => $row->customer_name ?: '-',
                'branch' => $branch?->name ?? '-',
                'building' => $row->nama_gedung ?: ($row->building_raw_name ?: '-'),
                'room' => $row->room_name ?: '-',
                'start_date' => $this->date($row->start_date),
                'end_date' => $this->date($row->end_date),
            ];
        })->filter(fn (array $row) => ! $filters->branchId || $row['_branch_id'] === $filters->branchId)->values();
    }
}
