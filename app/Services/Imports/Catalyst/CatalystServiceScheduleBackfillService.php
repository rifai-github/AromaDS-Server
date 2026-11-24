<?php

namespace App\Services\Imports\Catalyst;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Jadwal service untuk kontrak hasil import Catalyst (permintaan klien 24 Nov 2026, "import
 * contract dan outstanding service"). Job service Catalyst sendiri TIDAK diimport (struktur beda);
 * jadwalnya dibangun ulang dari BA date service pertama kontrak:
 *
 *  - anchor = BA date service pertama kontrak itu. Urutan sumber: BASDate terawal di detail
 *    invoice Catalyst (FINCustInvDt) -> tanggal CSR periode 1 -> InspectedInstall terawal di detail
 *    kontrak (MKTContractDt) -> start_date kontrak;
 *  - tanggal berikutnya mengikuti frekuensi service rental (rental_service_frequencies), dengan
 *    rumus yang sama dengan JobScheduleController::calculateScheduleDateForPeriod, dibatasi
 *    start_date..end_date kontrak;
 *  - tanggal yang sudah lewat = selesai (done_job, BA date = tanggal jadwal, ditandai
 *    catalyst_backfill_at supaya TIDAK ditagihkan ulang oleh InvoiceGenerationService);
 *    tanggal hari ini dan sesudahnya = outstanding (scheduled, alur normal: Material Assign,
 *    assign team, nomor job saat assign).
 *
 * Satu job per (gedung, frekuensi, alur unit only / bukan) per periode, berisi semua ruangan
 * rental di grup itu, sama dengan job lanjutan generateAllRemainingServices. Rental Unit Only
 * dipisah supaya rantainya tetap Job Check (IR) dan tidak tercampur rantai CSR.
 *
 * Hanya menyentuh kontrak yang job advice Catalyst-nya (JA-CATALYST-*) belum punya job sama
 * sekali, jadi aman dijalankan ulang.
 */
class CatalystServiceScheduleBackfillService
{
    public const JA_PREFIX = 'JA-CATALYST-';

    public const ANCHOR_BAS = 'bas_date';

    public const ANCHOR_CSR_PERIOD_1 = 'csr_period_1';

    public const ANCHOR_INSPECTED_INSTALL = 'inspected_install';

    public const ANCHOR_START_DATE = 'start_date';

    /** @var array<string, string> nomor kontrak -> Y-m-d */
    private array $basDates = [];

    /** @var array<string, string> */
    private array $csrPeriodOneDates = [];

    /** @var array<string, string> */
    private array $inspectedInstallDates = [];

    /**
     * Baca tanggal anchor dari export CSV Catalyst. File yang tidak ada dilewati (sumber
     * berikutnya yang dipakai).
     */
    public function loadAnchorSources(?string $invoiceDetailsPath, ?string $contractDetailsPath): void
    {
        if ($invoiceDetailsPath && is_file($invoiceDetailsPath)) {
            foreach ($this->readCsv($invoiceDetailsPath) as $row) {
                $contract = trim((string) ($row['sono'] ?? ''));
                if ($contract === '') {
                    continue;
                }

                $this->keepEarliest($this->basDates, $contract, $row['basdate'] ?? null);

                $isCsr = strtoupper(trim((string) ($row['refftype'] ?? ''))) === 'CSR';
                if ($isCsr && trim((string) ($row['periodinvoice'] ?? '')) === '1') {
                    $this->keepEarliest($this->csrPeriodOneDates, $contract, $row['reffdate'] ?? null);
                }
            }
        }

        if ($contractDetailsPath && is_file($contractDetailsPath)) {
            foreach ($this->readCsv($contractDetailsPath) as $row) {
                $contract = trim((string) ($row['transnmbr'] ?? ''));
                if ($contract !== '') {
                    $this->keepEarliest($this->inspectedInstallDates, $contract, $row['inspectedinstall'] ?? null);
                }
            }
        }
    }

    /**
     * Kontrak Catalyst yang perlu dibuatkan jadwal: punya JA-CATALYST-*, JA itu belum punya job,
     * status aktif, dan (kecuali $includeExpired) belum berakhir.
     *
     * @return Collection<int, object>
     */
    public function candidates(?string $contractNumber = null, bool $includeExpired = false, ?int $limit = null): Collection
    {
        $query = DB::table('job_advices as ja')
            ->join('contracts as c', 'c.id', '=', 'ja.contract_id')
            ->where('ja.job_advice_number', 'like', self::JA_PREFIX.'%')
            ->whereNull('ja.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereIn('c.contract_status', ['active', 'running'])
            ->whereNotNull('c.start_date')
            ->whereNotNull('c.end_date')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('job_schedules as js')
                ->whereColumn('js.job_advice_id', 'ja.id')
                ->whereNull('js.deleted_at'))
            ->orderBy('c.contract_number')
            ->select(
                'ja.id as job_advice_id', 'ja.job_advice_number', 'ja.company_name',
                'c.id as contract_id', 'c.contract_number', 'c.start_date', 'c.end_date'
            );

        if ($contractNumber) {
            $query->where('c.contract_number', $contractNumber);
        }
        if (! $includeExpired) {
            $query->where('c.end_date', '>=', now()->toDateString());
        }
        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Kontrak import Catalyst (ada peta MKTContractHd) yang aktif tapi TIDAK punya job advice sama
     * sekali. Step importer `job_advices` hanya membuat JA-CATALYST-* dari tabel Catalyst
     * MKTContractJobOut, jadi kontrak yang tak punya baris JobOut (cek prod 25 Nov 2026: 465
     * kontrak aktif) tidak pernah dapat JA dan dilewati candidates().
     *
     * Bentuknya sama dengan candidates() tapi job_advice_id = null: plan() lalu membaca rental
     * langsung dari contract_rentals, dan createJobAdvice() membuat JA-nya saat --apply.
     *
     * @return Collection<int, object>
     */
    public function contractsWithoutJobAdvice(?string $contractNumber = null, bool $includeExpired = false, ?int $limit = null): Collection
    {
        $query = DB::table('contracts as c')
            ->leftJoin('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->whereNull('c.deleted_at')
            ->whereIn('c.contract_status', ['active', 'running'])
            ->whereNotNull('c.start_date')
            ->whereNotNull('c.end_date')
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('source_import_maps as m')
                ->where('m.source_system', 'catalyst')
                ->where('m.source_table', 'MKTContractHd')
                ->where('m.target_table', 'contracts')
                ->whereColumn('m.target_id', 'c.id'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('job_advices as ja')
                ->whereColumn('ja.contract_id', 'c.id')
                ->whereNull('ja.deleted_at'))
            ->orderBy('c.contract_number')
            ->select(
                DB::raw('NULL as job_advice_id'), 'cu.name as company_name',
                'c.id as contract_id', 'c.contract_number', 'c.start_date', 'c.end_date',
                'c.quotation_id', 'c.customer_id', 'c.marketing_id'
            );

        if ($contractNumber) {
            $query->where('c.contract_number', $contractNumber);
        }
        if (! $includeExpired) {
            $query->where('c.end_date', '>=', now()->toDateString());
        }
        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->each(function ($candidate) {
            $candidate->job_advice_number = self::JA_PREFIX.$candidate->contract_number;
        });
    }

    /**
     * Buat JA-CATALYST-* + job_advice_rooms dari rental kontrak, dengan isi yang sama dengan step
     * importer `job_advices` / `job_advice_rooms`. Mengembalikan candidate yang sudah punya
     * job_advice_id, siap untuk plan() + apply().
     */
    public function createJobAdvice(object $candidate, ?int $userId = null): object
    {
        $now = now();
        $actorId = $candidate->marketing_id ?: $userId;

        $jobAdviceId = DB::table('job_advices')->insertGetId([
            'job_advice_number' => $candidate->job_advice_number,
            'type' => 'install',
            'reference_number' => $candidate->contract_number,
            'company_name' => $candidate->company_name,
            'contract_id' => $candidate->contract_id,
            'quotation_id' => $candidate->quotation_id,
            'customer_id' => $candidate->customer_id,
            'request_by' => $actorId,
            'submitted_by' => $actorId,
            'submitted_at' => $now,
            'expected_date' => substr((string) $candidate->start_date, 0, 10),
            'status' => 'approved',
            'date_approval' => $now,
            'approved_by' => $actorId,
            'with_invoicing' => false,
            'with_materials' => true,
            'notes' => "ImportedFrom: Catalyst MKTContractHd (tanpa MKTContractJobOut)\nContractNo: {$candidate->contract_number}",
            'created_by' => $actorId,
            'updated_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($this->contractRentalRooms($candidate->contract_id) as $rental) {
            DB::table('job_advice_rooms')->insert([
                'job_advice_id' => $jobAdviceId,
                'contract_room_id' => $rental->contract_room_id,
                'contract_rental_id' => $rental->contract_rental_id,
                'rental_product_id' => $rental->rental_product_id,
                'room_name' => $rental->room_name,
                'rental_name' => $rental->rental_name,
                'quantity' => $rental->quantity,
                'qty_free' => $rental->qty_free,
                'rental_has_installation' => true,
                'rental_has_service' => true,
                'status' => 'pending',
                'is_trial' => false,
                'unit_already_installed' => false,
                'notes' => "ImportedFrom: Catalyst MKTContractHd\nContractRentalId: {$rental->contract_rental_id}",
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $created = clone $candidate;
        $created->job_advice_id = $jobAdviceId;

        return $created;
    }

    /**
     * Rental kontrak dalam bentuk baris job_advice_rooms (belum tersimpan). Ruangan kontrak dicari
     * seperti CatalystMasterDataImporter::findContractRoomForRental: contract_rooms dengan room_id
     * yang sama, fallback ruangan kontrak pertama.
     *
     * @return Collection<int, object>
     */
    public function contractRentalRooms(int $contractId): Collection
    {
        $contractRooms = DB::table('contract_rooms')->where('contract_id', $contractId)->orderBy('id')->get(['id', 'room_id']);

        $rentals = DB::table('contract_rentals as r')
            ->leftJoin('master_rentals as mrt', 'mrt.id', '=', 'r.master_rental_id')
            ->leftJoin('rental_service_frequencies as f', 'f.id', '=', 'mrt.service_frequency_id')
            ->where('r.contract_id', $contractId)
            ->orderBy('r.id')
            ->get([
                'r.id', 'r.room_id as rental_room_id', 'r.master_rental_id', 'r.rental_alias', 'r.quantity', 'r.qty_free',
                'mrt.rental_name as master_rental_name', 'mrt.rental_type',
                'f.id as frequency_id', 'f.name as frequency_name', 'f.frequency_months', 'f.frequency_times_per_month',
            ]);

        $roomIds = $contractRooms->pluck('room_id')->filter()->unique()->values();
        $masterRooms = DB::table('master_rooms as mr')
            ->leftJoin('buildings as b', 'b.id', '=', 'mr.building_id')
            ->whereIn('mr.id', $roomIds)
            ->get(['mr.id', 'mr.room_name', 'mr.building_id', 'b.nama_gedung', 'b.name as building_raw_name', 'b.postal_code'])
            ->keyBy('id');

        return $rentals->map(function ($rental) use ($contractRooms, $masterRooms) {
            $contractRoom = ($rental->rental_room_id ? $contractRooms->firstWhere('room_id', $rental->rental_room_id) : null)
                ?? $contractRooms->first();
            $masterRoom = $contractRoom?->room_id ? $masterRooms->get($contractRoom->room_id) : null;
            $name = fn (?string $value) => trim((string) $value) !== '' ? trim((string) $value) : null;

            return (object) [
                // Kunci sementara (belum ada job_advice_rooms.id); hanya dipakai saat dry-run.
                'id' => 'cr'.$rental->id,
                'contract_rental_id' => (int) $rental->id,
                'contract_room_id' => $contractRoom?->id,
                'room_id' => $contractRoom?->room_id,
                'room_name' => $name($masterRoom?->room_name) ?? 'General',
                'rental_product_id' => $rental->master_rental_id,
                'rental_name' => $name($rental->rental_alias) ?? $name($rental->master_rental_name) ?? 'Rental',
                'quantity' => (int) max(1, ceil((float) ($rental->quantity ?? 0))),
                'qty_free' => (int) max(0, ceil((float) ($rental->qty_free ?? 0))),
                'building_id' => $masterRoom?->building_id,
                'nama_gedung' => $masterRoom?->nama_gedung,
                'building_raw_name' => $masterRoom?->building_raw_name,
                'postal_code' => $masterRoom?->postal_code,
                'rental_type' => $rental->rental_type,
                'frequency_id' => $rental->frequency_id,
                'frequency_name' => $rental->frequency_name,
                'frequency_months' => $rental->frequency_months,
                'frequency_times_per_month' => $rental->frequency_times_per_month,
            ];
        });
    }

    /** @return array{0: ?string, 1: string} [Y-m-d, sumber] */
    public function anchorFor(object $candidate): array
    {
        $number = $candidate->contract_number;

        foreach ([
            self::ANCHOR_BAS => $this->basDates[$number] ?? null,
            self::ANCHOR_CSR_PERIOD_1 => $this->csrPeriodOneDates[$number] ?? null,
            self::ANCHOR_INSPECTED_INSTALL => $this->inspectedInstallDates[$number] ?? null,
        ] as $source => $date) {
            if ($date) {
                return [$date, $source];
            }
        }

        return [substr((string) $candidate->start_date, 0, 10), self::ANCHOR_START_DATE];
    }

    /**
     * Rencana jadwal satu kontrak (tanpa menulis apa pun).
     *
     * @return array{anchor: string, anchor_source: string, groups: array<int, array>, skipped_rooms: int}
     */
    public function plan(object $candidate, ?Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();
        [$anchor, $source] = $this->anchorFor($candidate);

        // Kontrak tanpa JA (dry-run --create-missing-job-advice): rental dibaca dari kontrak.
        $rooms = $candidate->job_advice_id === null ? $this->contractRentalRooms((int) $candidate->contract_id) : DB::table('job_advice_rooms as jar')
            ->leftJoin('contract_rooms as cr', 'cr.id', '=', 'jar.contract_room_id')
            ->leftJoin('master_rooms as mr', 'mr.id', '=', 'cr.room_id')
            ->leftJoin('buildings as b', 'b.id', '=', 'mr.building_id')
            ->leftJoin('master_rentals as mrt', 'mrt.id', '=', 'jar.rental_product_id')
            ->leftJoin('rental_service_frequencies as f', 'f.id', '=', 'mrt.service_frequency_id')
            ->where('jar.job_advice_id', $candidate->job_advice_id)
            ->whereNull('jar.deleted_at')
            ->get([
                'jar.id', 'jar.room_name', 'jar.contract_room_id', 'cr.room_id',
                'mr.building_id', 'b.nama_gedung', 'b.name as building_raw_name', 'b.postal_code',
                'mrt.rental_type', 'f.id as frequency_id', 'f.name as frequency_name',
                'f.frequency_months', 'f.frequency_times_per_month',
            ]);

        $skipped = $rooms->filter(fn ($room) => ! $room->frequency_id || ! $room->building_id)->count();
        $groups = [];

        $start = Carbon::parse($candidate->start_date)->startOfDay();
        $end = Carbon::parse($candidate->end_date)->startOfDay();

        $grouped = $rooms->filter(fn ($room) => $room->frequency_id && $room->building_id)
            ->groupBy(fn ($room) => $room->building_id.'|'.$room->frequency_id.'|'.($room->rental_type === 'unit_only' ? 'unit_only' : 'other'));

        foreach ($grouped as $groupRooms) {
            $first = $groupRooms->first();
            $dates = $this->scheduleDates(
                Carbon::parse($anchor),
                $start,
                $end,
                max(1, (int) $first->frequency_months),
                max(1, (int) $first->frequency_times_per_month)
            );
            if ($dates === []) {
                continue;
            }

            $groups[] = [
                'building_id' => (int) $first->building_id,
                'building_name' => trim((string) ($first->nama_gedung ?: $first->building_raw_name)),
                'postal_code' => $first->postal_code,
                'frequency_name' => $first->frequency_name,
                'frequency_times_per_month' => max(1, (int) $first->frequency_times_per_month),
                'unit_only' => $first->rental_type === 'unit_only',
                'rooms' => $groupRooms->groupBy(fn ($room) => $room->contract_room_id ?: 'jar'.$room->id)->values()->all(),
                'dates' => $dates,
                'done' => count(array_filter($dates, fn (string $date) => Carbon::parse($date)->lt($today))),
            ];
        }

        return ['anchor' => $anchor, 'anchor_source' => $source, 'groups' => $groups, 'skipped_rooms' => $skipped];
    }

    /**
     * Tulis job dari rencana. Satu transaksi per kontrak.
     *
     * @return array{done: int, outstanding: int}
     */
    public function apply(object $candidate, array $plan, ?int $userId = null, ?Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $now = now();
        $counts = ['done' => 0, 'outstanding' => 0];

        DB::transaction(function () use ($candidate, $plan, $userId, $today, $now, &$counts) {
            foreach ($plan['groups'] as $group) {
                $nextJobId = null;
                $lastJobId = null;
                $total = count($group['dates']);

                foreach ($group['dates'] as $index => $date) {
                    $period = $index + 1;
                    $isDone = Carbon::parse($date)->lt($today);
                    $roomCount = count($group['rooms']);
                    $singleRoom = $roomCount === 1 ? $group['rooms'][0][0] : null;

                    $jobId = DB::table('job_schedules')->insertGetId([
                        'job_number' => null, // nomor job dibuat saat assign team, sama dengan alur normal
                        'type' => $period === 1 ? 'service_first' : 'service',
                        'status' => $isDone ? 'done_job' : 'scheduled',
                        'job_advice_id' => $candidate->job_advice_id,
                        'building_id' => $group['building_id'],
                        'building_name' => $group['building_name'],
                        'room_id' => $singleRoom?->room_id,
                        'room_name' => $singleRoom?->room_name,
                        'company_name' => $candidate->company_name,
                        'contract_number' => $candidate->contract_number,
                        'schedule_date' => $date,
                        'expected_date' => $date,
                        'ba_date' => $isDone ? $date : null,
                        'completed_at' => $isDone ? $date.' 00:00:00' : null,
                        'catalyst_backfill_at' => $isDone ? $now : null,
                        'material_checked' => $isDone,
                        'material_checked_at' => $isDone ? $now : null,
                        'period' => $period,
                        'service_frequency' => $group['frequency_times_per_month'],
                        'service_period_type' => $group['frequency_name'],
                        'reference_number' => $candidate->job_advice_number,
                        'postal_code' => $group['postal_code'],
                        'internal_notes' => "Catalyst service schedule {$period}/{$total} (anchor {$plan['anchor']} dari {$plan['anchor_source']})"
                            .($isDone ? ' - riwayat selesai, tidak ditagihkan ulang' : ''),
                        'created_by' => $userId,
                        'updated_by' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    foreach ($group['rooms'] as $roomRentals) {
                        $primary = $roomRentals[0];
                        $scheduleRoomId = DB::table('job_schedule_rooms')->insertGetId([
                            'job_schedule_id' => $jobId,
                            'job_advice_room_id' => $primary->id,
                            'room_name' => $primary->room_name,
                            'room_id' => $primary->room_id,
                            'status' => $isDone ? 'completed' : 'pending',
                            'completed_at' => $isDone ? $date.' 00:00:00' : null,
                            'material_return_status' => 'not_required',
                            'notes' => 'Rentals in this room: '.count($roomRentals),
                            'created_by' => $userId,
                            'updated_by' => $userId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        foreach ($roomRentals as $rentalIndex => $rental) {
                            DB::table('job_schedule_room_rentals')->insert([
                                'job_schedule_room_id' => $scheduleRoomId,
                                'job_advice_room_id' => $rental->id,
                                'is_primary' => $rentalIndex === 0,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }

                    $counts[$isDone ? 'done' : 'outstanding']++;
                    $lastJobId = $jobId;
                    if (! $isDone && $nextJobId === null) {
                        $nextJobId = $jobId;
                    }
                }

                // Seperti alur normal: rental menunjuk ke job service yang sedang berjalan.
                $rentalIds = collect($group['rooms'])->flatten(1)->pluck('id')->all();
                DB::table('job_advice_rooms')->whereIn('id', $rentalIds)->update([
                    'service_job_schedule_id' => $nextJobId ?? $lastJobId,
                    'updated_at' => $now,
                ]);
            }
        });

        return $counts;
    }

    /**
     * Tanggal service dari anchor, rumus sama dengan JobScheduleController::calculateScheduleDateForPeriod,
     * hanya yang jatuh di dalam masa kontrak.
     *
     * @return array<int, string>
     */
    public function scheduleDates(Carbon $anchor, Carbon $start, Carbon $end, int $months, int $timesPerMonth): array
    {
        $dates = [];
        for ($k = 0; $k < 1000; $k++) {
            $target = $anchor->copy()->addMonthsNoOverflow(intdiv($k, $timesPerMonth) * $months);
            $indexInMonth = $k % $timesPerMonth;
            if ($timesPerMonth > 1 && $indexInMonth > 0) {
                $target->addDays($indexInMonth * (int) floor($target->daysInMonth / $timesPerMonth));
            }

            if ($target->gt($end)) {
                break;
            }
            if ($target->gte($start)) {
                $dates[] = $target->toDateString();
            }
        }

        return $dates;
    }

    /** @param array<string, string> $map */
    private function keepEarliest(array &$map, string $key, mixed $value): void
    {
        $date = $this->parseCatalystDate($value);
        if ($date && (! isset($map[$key]) || $date < $map[$key])) {
            $map[$key] = $date;
        }
    }

    /** Format export Catalyst: "8/2/2024 12:00:00 AM" (m/d/Y). */
    private function parseCatalystDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '1/1/1900')) {
            return null;
        }

        foreach (['n/j/Y g:i:s A', 'n/j/Y', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false && $date->year > 2000) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }

        return null;
    }

    /** @return \Generator<int, array<string, string>> kolom di-lowercase */
    private function readCsv(string $path): \Generator
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return;
        }
        // BOM di depan tanda kutip membuat fgetcsv menyisakan kutipnya di nama kolom pertama.
        $header = array_map(fn ($column) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $column), " \t\"")), $header);

        $width = count($header);
        while (($row = fgetcsv($handle)) !== false) {
            // Export Catalyst kadang diakhiri koma (kolom kosong ekstra): samakan lebarnya.
            $row = array_slice(array_pad($row, $width, ''), 0, $width);
            yield array_combine($header, $row);
        }

        fclose($handle);
    }
}
