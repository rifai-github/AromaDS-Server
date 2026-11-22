<?php

namespace App\Services\Operational;

use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\ContractRoom;
use App\Models\ContractRoomSuspension;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MOM 19 Nov 2026 p6: Suspend/Unsuspend ROOM kontrak. Room yang sedang di-suspend terkunci:
 * job-nya tidak boleh dikerjakan di aplikasi teknisi maupun web sampai di-unsuspend. Setiap
 * aksi dicatat (contract_room_suspensions + audit log).
 *
 * Bukan status job 'suspend' yang lama (job selesai tanpa invoice).
 */
class ContractRoomSuspensionService
{
    /** @var array<int, ?bool> cache per request: contract_room_id => suspended */
    private array $roomCache = [];

    private ?bool $tableExists = null;

    /**
     * @param  array<int>  $contractRoomIds
     * @return int jumlah room yang baru di-suspend
     */
    public function suspend(Contract $contract, array $contractRoomIds, string $suspendDate, ?int $userId = null, ?string $notes = null): int
    {
        $rooms = $this->contractRooms($contract, $contractRoomIds);
        $date = Carbon::parse($suspendDate)->toDateString();
        $created = 0;

        DB::transaction(function () use ($rooms, $contract, $date, $userId, $notes, &$created) {
            foreach ($rooms as $room) {
                $alreadyOpen = ContractRoomSuspension::query()
                    ->where('contract_room_id', $room->id)
                    ->open()
                    ->exists();

                if ($alreadyOpen) {
                    continue;
                }

                $suspension = ContractRoomSuspension::create([
                    'contract_id' => $contract->id,
                    'contract_room_id' => $room->id,
                    'room_id' => $room->room_id,
                    'suspend_date' => $date,
                    'suspended_by' => $userId,
                    'suspended_at' => now(),
                    'notes' => $notes,
                ]);

                AuditLog::log('contract_room_suspended', $suspension, null, [
                    'contract_number' => $contract->contract_number,
                    'contract_room_id' => $room->id,
                    'room' => $room->room?->room_name,
                    'suspend_date' => $date,
                ], $userId);

                $created++;
            }
        });

        $this->roomCache = [];

        return $created;
    }

    /**
     * @param  array<int>  $contractRoomIds
     * @return int jumlah room yang di-unsuspend
     */
    public function unsuspend(Contract $contract, array $contractRoomIds, ?int $userId = null): int
    {
        $rooms = $this->contractRooms($contract, $contractRoomIds);
        $released = 0;

        DB::transaction(function () use ($rooms, $contract, $userId, &$released) {
            $suspensions = ContractRoomSuspension::query()
                ->whereIn('contract_room_id', $rooms->pluck('id'))
                ->open()
                ->get();

            foreach ($suspensions as $suspension) {
                $suspension->update([
                    'unsuspended_by' => $userId,
                    'unsuspended_at' => now(),
                ]);

                AuditLog::log('contract_room_unsuspended', $suspension, [
                    'suspend_date' => $suspension->suspend_date?->toDateString(),
                ], [
                    'contract_number' => $contract->contract_number,
                    'contract_room_id' => $suspension->contract_room_id,
                ], $userId);

                $released++;
            }
        });

        $this->roomCache = [];

        return $released;
    }

    /** Suspensi terbuka (aktif atau terjadwal) per contract_room_id untuk satu kontrak. */
    public function openSuspensionsForContract(Contract $contract): Collection
    {
        if (! $this->available()) {
            return collect();
        }

        return ContractRoomSuspension::query()
            ->where('contract_id', $contract->id)
            ->open()
            ->get()
            ->keyBy('contract_room_id');
    }

    public function isContractRoomSuspended(?int $contractRoomId, $date = null): bool
    {
        if (! $contractRoomId || ! $this->available()) {
            return false;
        }

        $cacheKey = $date ? null : $contractRoomId;
        if ($cacheKey !== null && array_key_exists($cacheKey, $this->roomCache)) {
            return (bool) $this->roomCache[$cacheKey];
        }

        $suspended = ContractRoomSuspension::query()
            ->where('contract_room_id', $contractRoomId)
            ->inEffect($date)
            ->exists();

        if ($cacheKey !== null) {
            $this->roomCache[$cacheKey] = $suspended;
        }

        return $suspended;
    }

    /** Fallback untuk baris tanpa contract_room_id: kontrak + master room. */
    public function isMasterRoomSuspended(?int $contractId, ?int $masterRoomId, $date = null): bool
    {
        if (! $contractId || ! $masterRoomId || ! $this->available()) {
            return false;
        }

        return ContractRoomSuspension::query()
            ->where('contract_id', $contractId)
            ->where('room_id', $masterRoomId)
            ->inEffect($date)
            ->exists();
    }

    public function isJobAdviceRoomSuspended(?JobAdviceRoom $room): bool
    {
        if (! $room || ! $this->available()) {
            return false;
        }

        if ($room->contract_room_id) {
            return $this->isContractRoomSuspended((int) $room->contract_room_id);
        }

        $contractId = $room->jobAdvice?->contract_id;
        $masterRoomId = $room->contractRoom?->room_id ?? $room->quotationRoom?->room_id ?? null;

        return $this->isMasterRoomSuspended($contractId ? (int) $contractId : null, $masterRoomId ? (int) $masterRoomId : null);
    }

    public function isJobScheduleRoomSuspended(?JobScheduleRoom $room, ?JobSchedule $job = null): bool
    {
        if (! $room || ! $this->available()) {
            return false;
        }

        if ($room->jobAdviceRoom && $this->isJobAdviceRoomSuspended($room->jobAdviceRoom)) {
            return true;
        }

        $job ??= $room->jobSchedule;
        $contractId = $job?->jobAdvice?->contract_id;

        return $this->isMasterRoomSuspended($contractId ? (int) $contractId : null, $room->room_id ? (int) $room->room_id : null);
    }

    public function roomBlockMessage(?string $roomName = null): string
    {
        return 'Room '.($roomName ? "\"{$roomName}\" " : '').'sedang di-SUSPEND di kontrak; job room ini tidak bisa dikerjakan sampai di-unsuspend.';
    }

    /**
     * Alasan job tidak boleh dikerjakan karena suspend room, atau null.
     *  - $requireAll = true: tolak hanya bila SEMUA room job di-suspend (aksi level job: tiba,
     *    mulai). Job dengan sebagian room aktif tetap bisa dikerjakan untuk room lainnya.
     *  - $requireAll = false: tolak bila ADA room yang di-suspend (aksi yang menyelesaikan semua
     *    room sekaligus: Done Job / BA dari web).
     */
    public function jobBlockReason(?JobSchedule $job, bool $requireAll = true): ?string
    {
        if (! $job || ! $this->available()) {
            return null;
        }

        $states = $this->jobRoomStates($job);
        if ($states->isEmpty()) {
            return null;
        }

        $suspended = $states->filter(fn ($state) => $state['suspended']);
        if ($suspended->isEmpty()) {
            return null;
        }

        if ($requireAll && $suspended->count() < $states->count()) {
            return null;
        }

        $names = $suspended->pluck('name')->filter()->unique()->implode(', ');

        return $requireAll
            ? 'Semua room job ini sedang di-SUSPEND di kontrak'.($names ? " ({$names})" : '').'; job tidak bisa dikerjakan sampai di-unsuspend.'
            : 'Ada room yang sedang di-SUSPEND di kontrak'.($names ? " ({$names})" : '').'; job tidak bisa diselesaikan sampai room itu di-unsuspend.';
    }

    /** @return Collection<int, array{name: ?string, suspended: bool}> */
    private function jobRoomStates(JobSchedule $job): Collection
    {
        $job->loadMissing(['jobScheduleRooms.jobAdviceRoom.contractRoom.room', 'jobAdvice']);
        $contractId = $job->jobAdvice?->contract_id ? (int) $job->jobAdvice->contract_id : null;

        if ($job->jobScheduleRooms->isNotEmpty()) {
            return $job->jobScheduleRooms
                ->reject(fn ($room) => $room->status === 'cancelled')
                ->map(fn ($room) => [
                    'name' => $room->jobAdviceRoom?->contractRoom?->room?->room_name ?? $room->room?->room_name ?? null,
                    'suspended' => $this->isJobScheduleRoomSuspended($room, $job),
                ])
                ->values();
        }

        if ($job->room_id) {
            return collect([[
                'name' => $job->room?->room_name,
                'suspended' => $this->isMasterRoomSuspended($contractId, (int) $job->room_id),
            ]]);
        }

        return collect();
    }

    private function contractRooms(Contract $contract, array $contractRoomIds): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $contractRoomIds))));

        $rooms = ContractRoom::with('room')
            ->where('contract_id', $contract->id)
            ->whereIn('id', $ids)
            ->get();

        if ($rooms->isEmpty()) {
            throw new \InvalidArgumentException('Pilih minimal satu room kontrak ini.');
        }

        return $rooms;
    }

    private function available(): bool
    {
        return $this->tableExists ??= Schema::hasTable('contract_room_suspensions');
    }
}
