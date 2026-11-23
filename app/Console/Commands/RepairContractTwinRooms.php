<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * QA 23 Nov 2026: sebelum 000cd99 wizard kontrak menolak quotation_rooms.room_id bila Master Room
 * sudah di-rename lalu membuat MasterRoom BARU (tanpa customer, room_code "XXX-{quotation_room_id}").
 * Akibatnya kontrak/JA/JS menunjuk room kembar, rental kontrak terpisah dari ruangannya, dan JA
 * memakai rental MasterRental::first(). Command ini mengarahkan ulang semua referensi satu kontrak ke
 * Master Room asli. Default dry-run; --apply menyimpan.
 */
class RepairContractTwinRooms extends Command
{
    protected $signature = 'contracts:repair-twin-rooms
                            {--contract=* : ID kontrak (wajib, boleh lebih dari satu)}
                            {--apply : Simpan perubahan (default dry-run)}
                            {--delete-orphans : Soft-delete room kembar yang tak dipakai lagi (butuh --apply)}';

    protected $description = 'Arahkan ulang room kembar hasil auto-create wizard kontrak ke Master Room asli (kontrak, rental, JA, JS, UoW, suspend)';

    private array $lines = [];

    public function handle(): int
    {
        $contractIds = array_values(array_filter(array_map('intval', (array) $this->option('contract'))));
        if (empty($contractIds)) {
            $this->error('Sebutkan kontrak: --contract=21130 --contract=21131');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'Mode APPLY - perubahan disimpan.' : 'Mode DRY-RUN - tidak ada yang disimpan.');

        $twinIdsTouched = [];
        foreach ($contractIds as $contractId) {
            $contract = DB::table('contracts')->where('id', $contractId)->first(['id', 'contract_number', 'quotation_id', 'customer_id']);
            if (! $contract) {
                $this->warn("Kontrak #{$contractId} tidak ditemukan.");

                continue;
            }

            $this->line('');
            $this->info("== {$contract->contract_number} (#{$contract->id}) ==");

            DB::beginTransaction();
            try {
                $twinIdsTouched = array_merge($twinIdsTouched, $this->repairContract($contract, $apply));
                $apply ? DB::commit() : DB::rollBack();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("Gagal: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        $this->reportOrphans(array_values(array_unique($twinIdsTouched)), $apply && (bool) $this->option('delete-orphans'));

        if (! $apply) {
            $this->line('');
            $this->line('Dry-run selesai. Periksa baris PLAN/UNRESOLVED, lalu jalankan ulang dengan --apply.');
        }

        return self::SUCCESS;
    }

    /** @return int[] id room kembar yang referensinya dipindah */
    private function repairContract(object $contract, bool $apply): array
    {
        $quotationRooms = $contract->quotation_id
            ? DB::table('quotation_rooms')->where('quotation_id', $contract->quotation_id)
                ->when(Schema::hasColumn('quotation_rooms', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->get(['id', 'room_id', 'room_name'])
            : collect();
        $realRoomIds = $quotationRooms->pluck('room_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $quotationRoomById = $quotationRooms->keyBy('id');

        $rooms = fn (array $ids) => DB::table('master_rooms')->whereIn('id', $ids)
            ->get(['id', 'room_name', 'room_code', 'building_id', 'customer_id', 'deleted_at'])->keyBy('id');
        $realRooms = $rooms($realRoomIds);

        // 1. contract_rooms -> room asli
        $contractRooms = DB::table('contract_rooms')->where('contract_id', $contract->id)
            ->when(Schema::hasColumn('contract_rooms', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->get(['id', 'room_id']);
        $contractMasterRooms = $rooms($contractRooms->pluck('room_id')->filter()->map(fn ($id) => (int) $id)->all());

        $twinMap = []; // twin master room id => real master room id
        $usedReal = $contractRooms->pluck('room_id')->map(fn ($id) => (int) $id)->intersect($realRoomIds)->all();

        foreach ($contractRooms as $contractRoom) {
            $roomId = (int) $contractRoom->room_id;
            if (in_array($roomId, $realRoomIds, true)) {
                $this->say('OK', "contract_room #{$contractRoom->id} -> room #{$roomId} {$this->roomLabel($realRooms->get($roomId))}");

                continue;
            }

            $twin = $contractMasterRooms->get($roomId);
            $realId = $this->resolveRealRoom($twin, $quotationRoomById, $realRooms, $usedReal);

            if (! $realId) {
                $this->say('UNRESOLVED', "contract_room #{$contractRoom->id} -> room #{$roomId} {$this->roomLabel($twin)}: tak ada pasangan di quotation");

                continue;
            }

            if (in_array($realId, $usedReal, true)) {
                $this->say('UNRESOLVED', "contract_room #{$contractRoom->id} room #{$roomId} kembar dari #{$realId}, tapi room asli itu sudah punya contract_room lain (perlu digabung manual)");
                $twinMap[$roomId] = $realId; // rental/JS tetap boleh diarahkan ke room asli

                continue;
            }

            $twinMap[$roomId] = $realId;
            $usedReal[] = $realId;
            $this->say('PLAN', "contract_room #{$contractRoom->id}: room #{$roomId} {$this->roomLabel($twin)} -> #{$realId} {$this->roomLabel($realRooms->get($realId))}");
            if ($apply) {
                DB::table('contract_rooms')->where('id', $contractRoom->id)->update(['room_id' => $realId, 'updated_at' => now()]);
            }
        }

        $contractRoomIdsAfter = DB::table('contract_rooms')->where('contract_id', $contract->id)
            ->when(Schema::hasColumn('contract_rooms', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->pluck('room_id')->map(fn ($id) => (int) $id)->all();
        $finalRoomIds = array_values(array_unique(array_map(fn ($id) => $twinMap[$id] ?? $id, $contractRoomIdsAfter)));
        $finalRooms = $rooms($finalRoomIds);

        // 2. contract_rentals -> ruangan kontrak
        $rentals = DB::table('contract_rentals')->where('contract_id', $contract->id)
            ->when(Schema::hasColumn('contract_rentals', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->get(['id', 'room_id', 'master_rental_id', 'quantity', 'qty_free']);
        $rentalRooms = $rooms($rentals->pluck('room_id')->filter()->map(fn ($id) => (int) $id)->all());

        foreach ($rentals as $rental) {
            $roomId = $rental->room_id ? (int) $rental->room_id : null;
            if ($roomId && in_array($roomId, $finalRoomIds, true)) {
                continue;
            }

            $target = $roomId ? ($twinMap[$roomId] ?? null) : null;
            if (! $target && $roomId) {
                $target = $this->resolveRealRoom($rentalRooms->get($roomId), $quotationRoomById, $finalRooms, []);
                $target = $target && in_array($target, $finalRoomIds, true) ? $target : null;
            }
            if (! $target && count($finalRoomIds) === 1) {
                $target = $finalRoomIds[0];
            }

            if (! $target) {
                $this->say('UNRESOLVED', "contract_rental #{$rental->id} (rental #{$rental->master_rental_id}, qty {$rental->quantity}+{$rental->qty_free}) room #".($roomId ?? 'NULL').' tak cocok dengan ruangan kontrak');

                continue;
            }

            if ($roomId) {
                $twinMap[$roomId] = $twinMap[$roomId] ?? $target;
            }
            $this->say('PLAN', "contract_rental #{$rental->id} (rental #{$rental->master_rental_id}): room #".($roomId ?? 'NULL')." -> #{$target} {$this->roomLabel($finalRooms->get($target))}");
            if ($apply) {
                DB::table('contract_rentals')->where('id', $rental->id)->update(['room_id' => $target, 'updated_at' => now()]);
            }
        }

        // 3. Job schedule rooms, Unit On Wall, suspend log milik kontrak ini
        $jobAdviceIds = DB::table('job_advices')->where('contract_id', $contract->id)->pluck('id')->all();
        $jobScheduleIds = empty($jobAdviceIds) ? [] : DB::table('job_schedules')->whereIn('job_advice_id', $jobAdviceIds)->pluck('id')->all();

        foreach ($twinMap as $twinId => $realId) {
            $this->repoint('job_schedule_rooms', 'job_schedule_id', $jobScheduleIds, $twinId, $realId, $apply);
            $this->repoint('unit_on_walls', 'contract_id', [$contract->id], $twinId, $realId, $apply);
            $this->repoint('contract_room_suspensions', 'contract_id', [$contract->id], $twinId, $realId, $apply);
        }

        // 4. JA rooms dengan rental yang bukan milik kontrak (fallback lama MasterRental::first())
        $this->repairJobAdviceRentals($contract, $jobAdviceIds, $apply);

        return array_keys($twinMap);
    }

    private function resolveRealRoom(?object $twin, $quotationRoomById, $candidateRooms, array $exclude): ?int
    {
        if (! $twin) {
            return null;
        }

        // Pola auto-create wizard: room_code "XXX-{quotation_room_id}".
        if (preg_match('/-(\d+)$/', (string) $twin->room_code, $m) && ($qr = $quotationRoomById->get((int) $m[1])) && $qr->room_id) {
            return (int) $qr->room_id;
        }

        // Gedung sama + nama sama (setelah normalisasi).
        $key = $this->roomKey($twin);
        $matches = $candidateRooms->filter(fn ($room) => $this->roomKey($room) === $key && (int) $room->id !== (int) $twin->id)
            ->reject(fn ($room) => in_array((int) $room->id, $exclude, true));

        return $matches->count() === 1 ? (int) $matches->first()->id : null;
    }

    private function repairJobAdviceRentals(object $contract, array $jobAdviceIds, bool $apply): void
    {
        if (empty($jobAdviceIds)) {
            return;
        }

        $contractRentals = DB::table('contract_rentals')->where('contract_id', $contract->id)
            ->when(Schema::hasColumn('contract_rentals', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->get(['id', 'room_id', 'master_rental_id']);
        $contractRentalIds = $contractRentals->pluck('master_rental_id')->map(fn ($id) => (int) $id)->unique()->all();

        $jaRooms = DB::table('job_advice_rooms')->whereIn('job_advice_id', $jobAdviceIds)
            ->when(Schema::hasColumn('job_advice_rooms', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->get(['id', 'job_advice_id', 'contract_room_id', 'contract_rental_id', 'rental_product_id', 'room_name']);

        foreach ($jaRooms as $jaRoom) {
            if ($jaRoom->contract_rental_id || in_array((int) $jaRoom->rental_product_id, $contractRentalIds, true)) {
                continue;
            }

            $contractRoom = $jaRoom->contract_room_id
                ? DB::table('contract_rooms')->where('id', $jaRoom->contract_room_id)->first(['room_id'])
                : null;
            $candidates = $contractRoom
                ? $contractRentals->where('room_id', (int) $contractRoom->room_id)->values()
                : collect();
            if ($candidates->isEmpty() && $contractRentals->count() === 1) {
                $candidates = $contractRentals;
            }

            if ($candidates->count() !== 1) {
                $this->say('UNRESOLVED', "job_advice_room #{$jaRoom->id} ({$jaRoom->room_name}) rental #{$jaRoom->rental_product_id} bukan rental kontrak; kandidat: {$candidates->count()}");

                continue;
            }

            $source = $candidates->first();
            $this->say('PLAN', "job_advice_room #{$jaRoom->id} ({$jaRoom->room_name}): rental #{$jaRoom->rental_product_id} -> #{$source->master_rental_id} (contract_rental #{$source->id})");
            if ($apply) {
                DB::table('job_advice_rooms')->where('id', $jaRoom->id)->update([
                    'rental_product_id' => $source->master_rental_id,
                    'contract_rental_id' => $source->id,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function repoint(string $table, string $scopeColumn, array $scopeIds, int $twinId, int $realId, bool $apply): void
    {
        if (empty($scopeIds) || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'room_id')) {
            return;
        }

        $query = DB::table($table)->whereIn($scopeColumn, $scopeIds)->where('room_id', $twinId);
        $count = (clone $query)->count();
        if ($count === 0) {
            return;
        }

        $this->say('PLAN', "{$table}: {$count} baris room #{$twinId} -> #{$realId}");
        if ($apply) {
            $query->update(['room_id' => $realId]);
        }
    }

    private function reportOrphans(array $twinIds, bool $delete): void
    {
        if (empty($twinIds)) {
            return;
        }

        $this->line('');
        $this->info('== Room kembar sesudah perbaikan ==');
        $refs = [
            'contract_rooms' => 'room_id', 'contract_rentals' => 'room_id', 'quotation_rooms' => 'room_id',
            'survey_details' => 'room_id', 'job_schedule_rooms' => 'room_id', 'unit_on_walls' => 'room_id',
            'contract_room_suspensions' => 'room_id',
        ];

        foreach (DB::table('master_rooms')->whereIn('id', $twinIds)->whereNull('deleted_at')->get() as $room) {
            $used = [];
            foreach ($refs as $table => $column) {
                if (Schema::hasTable($table) && DB::table($table)->where($column, $room->id)->exists()) {
                    $used[] = $table;
                }
            }

            if ($used) {
                $this->say('KEEP', "room #{$room->id} {$this->roomLabel($room)} masih dipakai: ".implode(', ', $used));

                continue;
            }

            if ($room->customer_id) {
                $this->say('KEEP', "room #{$room->id} {$this->roomLabel($room)} tak dipakai tapi punya customer - tidak dihapus otomatis");

                continue;
            }

            $this->say($delete ? 'DELETED' : 'ORPHAN', "room #{$room->id} {$this->roomLabel($room)} tak dipakai lagi".($delete ? '' : ' (hapus dengan --apply --delete-orphans)'));
            if ($delete) {
                DB::table('master_rooms')->where('id', $room->id)->update(['deleted_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    private function roomKey(?object $room): ?string
    {
        if (! $room) {
            return null;
        }

        return ((int) $room->building_id).'|'.preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $room->room_name)));
    }

    private function roomLabel(?object $room): string
    {
        if (! $room) {
            return '(room tak ada)';
        }

        return '"'.$room->room_name.'" gedung #'.($room->building_id ?? '-').($room->customer_id ? '' : ' tanpa customer');
    }

    private function say(string $status, string $message): void
    {
        $this->line(str_pad("[{$status}]", 13).$message);
        Log::info("contracts:repair-twin-rooms [{$status}] {$message}");
    }
}
