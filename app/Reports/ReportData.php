<?php

namespace App\Reports;

use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Contract;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pencarian data bersama untuk laporan menu Report.
 *
 * Fakta skema yang dipakai (dicek 24 Nov 2026):
 *  - kontrak tak punya branch_id: branch = cabang quotation-nya, fallback prefix nomor kontrak
 *    (JKT-CA/...) yang dicocokkan ke branches.code (sama dengan Contract::display_branch_name);
 *  - job tak punya contract_id: job_schedules.job_advice_id -> job_advices.contract_id;
 *  - kode dokumen job ada di job_number ([BRANCH]-[KODE]/yy-mm/nnnn), status suspend/dpf/cancelled
 *    hanya ada di kolom status;
 *  - gedung kontrak: contract_rooms.room_id -> master_rooms.building_id -> buildings.
 */
class ReportData
{
    use AccessControlFilterTrait;

    private ?Collection $branchesById = null;

    private ?Collection $branchesByCode = null;

    /**
     * Kontrak yang boleh dilihat user, dengan aturan hirarki data yang sama seperti halaman
     * daftar kontrak. Tabel utama tetap bernama `contracts` (tanpa alias).
     */
    public function contracts(): Builder
    {
        $allowed = Contract::query();
        $allowed = $this->applyAccessControlFilter($allowed, null, 'created_by', 'marketing_id', $this->explicitBranchAccessField('quotation.branch_id'), null, null);

        // Filter akses dijalankan sebagai subquery terpisah: kolomnya (created_by, marketing_id)
        // tidak diberi nama tabel, jadi ambigu bila langsung digabung dengan join laporan.
        return DB::table('contracts')
            ->whereNull('contracts.deleted_at')
            ->whereIn('contracts.id', $allowed->select('contracts.id')->toBase());
    }

    /** @return object{id:int,name:string,code:?string}|null */
    public function branch(?int $quotationBranchId, ?string $documentNumber): ?object
    {
        $this->loadBranches();

        if ($quotationBranchId && $this->branchesById->has($quotationBranchId)) {
            return $this->branchesById->get($quotationBranchId);
        }

        $code = $this->prefix($documentNumber);

        return $code !== null ? $this->branchesByCode->get($code) : null;
    }

    /** Kode dokumen job dari job_number, mis. "SBY-CSR/26-09/0028" -> "CSR". */
    public function jobCode(?string $jobNumber): ?string
    {
        if ($jobNumber && preg_match('/^[A-Z0-9]+-([A-Z\-]+)\//', strtoupper(trim($jobNumber)), $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Status ops sebuah job (spec List Kontrak): status suspend/dpf/cancelled menang atas kode
     * di nomor job karena nomor job tidak berubah saat job di-suspend.
     */
    public function jobStatusCode(?string $status, ?string $jobNumber, ?string $type = null): ?string
    {
        return match ($status) {
            'suspend' => 'SUS',
            'dpf' => 'DPF',
            'cancelled' => 'RMC',
            // Job baru (New Job) dan job historis hasil generate jadwal Catalyst belum punya
            // nomor job (nomor dibuat saat assign team): kodenya diambil dari tipe job.
            default => $this->jobCode($jobNumber) ?? $this->jobTypeCode($type),
        };
    }

    /** Kode dokumen dari tipe job (sama dengan peta DocumentNumberService). */
    public function jobTypeCode(?string $type): ?string
    {
        return match (strtolower(trim((string) $type))) {
            'install' => 'IR',
            'install_free' => 'IF',
            'service', 'service_first', 'service_routine' => 'CSR',
            'remove' => 'RV',
            'remove_free' => 'RF',
            'extra', 'change' => 'EXT',
            'complain' => 'NR',
            default => null,
        };
    }

    /**
     * Sales + departemen per user id.
     *
     * @param  array<int, int>  $userIds
     * @return Collection<int, object{name:?string,department:?string}>
     */
    public function salesPeople(array $userIds): Collection
    {
        $userIds = array_values(array_unique(array_filter($userIds)));
        if ($userIds === []) {
            return collect();
        }

        return DB::table('users as u')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->whereIn('u.id', $userIds)
            ->get(['u.id', 'u.name', DB::raw('COALESCE(d.name, u.department_name) as department')])
            ->keyBy('id');
    }

    /**
     * Nama gedung per kontrak (gabungan bila lebih dari satu).
     *
     * @param  array<int, int>  $contractIds
     * @return Collection<int, string>
     */
    public function buildingNames(array $contractIds): Collection
    {
        if ($contractIds === []) {
            return collect();
        }

        return DB::table('contract_rooms as cr')
            ->join('master_rooms as mr', 'mr.id', '=', 'cr.room_id')
            ->join('buildings as b', 'b.id', '=', 'mr.building_id')
            ->whereIn('cr.contract_id', $contractIds)
            ->select('cr.contract_id', 'b.nama_gedung', 'b.name')
            ->distinct()
            ->get()
            ->groupBy('contract_id')
            ->map(fn (Collection $rows) => $rows
                ->map(fn ($row) => trim((string) ($row->nama_gedung ?: $row->name)))
                ->filter()
                ->unique()
                ->implode(', '));
    }

    /**
     * Job terakhir (jadwal paling akhir s/d tanggal acuan) per kontrak.
     *
     * @param  array<int, int>  $contractIds
     * @return Collection<int, object>
     */
    public function latestJobs(array $contractIds, string $untilDate): Collection
    {
        if ($contractIds === []) {
            return collect();
        }

        return DB::table('job_schedules as js')
            ->join('job_advices as ja', 'ja.id', '=', 'js.job_advice_id')
            ->whereIn('ja.contract_id', $contractIds)
            ->whereNull('js.deleted_at')
            ->whereNull('ja.deleted_at')
            ->where('js.schedule_date', '<=', $untilDate)
            ->orderBy('js.schedule_date')
            ->orderBy('js.id')
            ->get(['ja.contract_id', 'js.job_number', 'js.status', 'js.type', 'js.schedule_date'])
            ->keyBy('contract_id'); // baris terakhir per kontrak menang
    }

    /**
     * Status file Contract Balik per kontrak: diunggah (bukan ditolak) dan terverifikasi.
     *
     * @param  array<int, int>  $contractIds
     * @return Collection<int, object{uploaded:bool,verified:bool,verified_at:?string}>
     */
    public function contractBalik(array $contractIds): Collection
    {
        if ($contractIds === []) {
            return collect();
        }

        return DB::table('contract_files')
            ->whereIn('contract_id', $contractIds)
            ->where('file_type', \App\Models\ContractFile::TYPE_CONTRACT_BALIK)
            ->whereNull('deleted_at')
            ->get(['contract_id', 'verification_status', 'verified_at'])
            ->groupBy('contract_id')
            ->map(function (Collection $files) {
                $verified = $files->where('verification_status', 'verified');

                return (object) [
                    'uploaded' => $files->contains(fn ($file) => $file->verification_status !== 'rejected'),
                    'verified' => $verified->isNotEmpty(),
                    'verified_at' => $verified->max('verified_at'),
                ];
            });
    }

    public function limit(?string $text, int $length = 50): string
    {
        $text = trim((string) $text);

        return $text === '' ? '-' : Str::limit($text, $length, '...');
    }

    private function prefix(?string $documentNumber): ?string
    {
        $documentNumber = strtoupper(trim((string) $documentNumber));
        $position = strpos($documentNumber, '-');

        return $position ? substr($documentNumber, 0, $position) : null;
    }

    private function loadBranches(): void
    {
        if ($this->branchesById !== null) {
            return;
        }

        $branches = DB::table('branches')->whereNull('deleted_at')->orderBy('id')->get(['id', 'name', 'code']);
        $this->branchesById = $branches->keyBy('id');
        $this->branchesByCode = $branches->filter(fn ($branch) => $branch->code)
            ->groupBy(fn ($branch) => strtoupper($branch->code))
            ->map(fn (Collection $group) => $group->first());
    }
}
