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

        $rooms = DB::table('job_advice_rooms as jar')
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
