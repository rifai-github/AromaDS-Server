<?php

namespace App\Services\Operational;

use App\Models\Contract;
use App\Models\ContractRoom;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * QA 26 Nov 2026 (Customer Testing Indo, JKT-RF/26-11/0004): trial SQ-2 hanya membawa Ruang-1/2/4/5/6
 * ke kontrak BLP-CA/26-11/0003, tetapi Remove Free Ruang-3 ikut dibatalkan karena pembatalan
 * dulu memakai nomor SQ saja ("semua Remove Free SQ ini"). Aturannya per ruangan:
 * unit trial tetap di lokasi HANYA untuk ruangan yang masuk kontrak; ruangan yang tidak ikut
 * kontrak tetap harus dicabut (Remove Free tetap jalan).
 *
 * Bila ruangan job tidak bisa dibaca sama sekali (data lama tanpa room_id), perilaku lama
 * dipertahankan supaya tidak ada yang "kesenggol".
 */
class InstallFreeContractRoomScope
{
    /**
     * Ruangan master (room_id) yang sudah masuk kontrak (belum dihapus) dari nomor SQ ini.
     */
    public function contractedRoomIdsForQuotationNumber(?string $quotationNumber): Collection
    {
        if (! $quotationNumber) {
            return collect();
        }

        return ContractRoom::query()
            ->whereHas('contract.quotation', fn ($query) => $query->where('quotation_number', $quotationNumber))
            ->whereNotNull('room_id')
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    public function contractRoomIds(Contract $contract): Collection
    {
        return ContractRoom::where('contract_id', $contract->id)
            ->whereNotNull('room_id')
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * room_id fisik sebuah JobScheduleRoom (langsung, atau lewat JA room → contract/quotation room).
     */
    public function physicalRoomId(?JobScheduleRoom $room): ?int
    {
        if (! $room) {
            return null;
        }

        if ($room->room_id) {
            return (int) $room->room_id;
        }

        return $this->jobAdviceRoomPhysicalId($room->jobAdviceRoom);
    }

    public function jobAdviceRoomPhysicalId(?JobAdviceRoom $jaRoom): ?int
    {
        if (! $jaRoom) {
            return null;
        }

        $id = $jaRoom->room_id
            ?? $jaRoom->contractRoom?->room_id
            ?? $jaRoom->quotationRoom?->room_id;

        return $id ? (int) $id : null;
    }

    /**
     * @param  array<int, string>  $statuses  status Remove Free yang masih boleh dibatalkan
     * @param  Collection|null  $extraRoomIds  jalur lama ContractOnWallCsrService: job ber-room_id ruangan kontrak
     * @return int jumlah job Remove Free yang dibatalkan penuh
     */
    public function cancelPendingRemoveFreeForContract(Contract $contract, array $statuses, string $note, ?Collection $extraRoomIds = null): int
    {
        $quotationNumber = $contract->quotation?->quotation_number;
        $extraRoomIds = ($extraRoomIds ?? collect())->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if (! $quotationNumber && $extraRoomIds->isEmpty()) {
            return 0;
        }

        $contractRoomIds = $this->contractRoomIds($contract);

        $jobs = JobSchedule::whereIn('type', ['remove_free', 'remove free'])
            ->whereIn('status', $statuses)
            ->where(function ($query) use ($quotationNumber, $extraRoomIds) {
                if ($quotationNumber) {
                    $query->where('quotation_number', $quotationNumber);
                }
                if ($extraRoomIds->isNotEmpty()) {
                    $query->orWhereIn('room_id', $extraRoomIds);
                }
            })
            ->get();

        $cancelled = 0;
        foreach ($jobs as $job) {
            $rooms = JobScheduleRoom::with('jobAdviceRoom.contractRoom', 'jobAdviceRoom.quotationRoom')
                ->where('job_schedule_id', $job->id)
                ->get();

            $roomIdsByJsr = $rooms->mapWithKeys(fn ($room) => [$room->id => $this->physicalRoomId($room)]);
            $knownRoomIds = $roomIdsByJsr->filter();

            // Data lama: ruangan tak terbaca → perilaku lama (batalkan seluruh job).
            $legacy = $rooms->isEmpty() || $knownRoomIds->count() !== $rooms->count() || $contractRoomIds->isEmpty();

            $carried = $legacy
                ? $rooms
                : $rooms->filter(fn ($room) => $contractRoomIds->contains($roomIdsByJsr[$room->id]));

            if ($legacy || $carried->count() === $rooms->count()) {
                $job->update([
                    'status' => 'cancelled',
                    'internal_notes' => trim(($job->internal_notes ? $job->internal_notes."\n" : '').$note),
                ]);
                $cancelled++;

                continue;
            }

            if ($carried->isEmpty()) {
                Log::info('Remove Free tetap dijalankan: ruangannya tidak masuk kontrak', [
                    'job_schedule_id' => $job->id,
                    'contract_id' => $contract->id,
                ]);

                continue;
            }

            // Sebagian ruangan masuk kontrak: lepas hanya ruangan itu dari Remove Free.
            foreach ($carried as $room) {
                if ($room->jobAdviceRoom && (int) $room->jobAdviceRoom->remove_job_schedule_id === (int) $job->id) {
                    $room->jobAdviceRoom->update(['remove_job_schedule_id' => null]);
                }
                $room->delete();
            }

            $job->update([
                'internal_notes' => trim(($job->internal_notes ? $job->internal_notes."\n" : '').
                    'Ruangan '.$carried->pluck('room_name')->filter()->implode(', ').
                    " dilepas dari Remove Free karena masuk kontrak {$contract->contract_number}."),
            ]);
        }

        return $cancelled;
    }
}
