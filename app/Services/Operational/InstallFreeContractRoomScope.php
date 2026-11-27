<?php

namespace App\Services\Operational;

use App\Models\Contract;
use App\Models\ContractRoom;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\UnitOnWall;
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
    private const DONE_JOB_STATUSES = ['completed', 'done_job', 'selesai'];

    private const ACTIVE_UNIT_STATUSES = ['active', 'installed', 'on_wall', 'on wall', 'onwall'];

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
     * QA 27 Nov 2026 (Customer Testing Indo, BLP-SQ/26-11/0133 → BLP-CA/26-11/0005): Ruang-1 sudah
     * Remove Free (ADS-RF/26-11/0001 done, unit dicabut), Ruang-3 masih di lokasi. Saat kontrak
     * disetujui, Ruang-1 tetap dibuatkan Job Check 2–12 seolah unitnya masih terpasang.
     *
     * Ruangan fisik (room_id) yang unit trial-nya dari nomor SQ ini SUDAH dicabut: Remove Free SQ
     * ini selesai untuk ruangan itu, dan tidak ada unit aktif di ruangan itu yang dipasang job SQ
     * ini (mis. dipasang ulang). Ruangan ini tidak boleh dapat job lanjutan (CSR / Job Check);
     * kalau masuk kontrak, pemasangannya lewat JA Install biasa.
     */
    public function trialRemovedRoomIds(?string $quotationNumber): Collection
    {
        if (! $quotationNumber) {
            return collect();
        }

        $removeJobs = JobSchedule::whereIn('type', ['remove_free', 'remove free'])
            ->where('quotation_number', $quotationNumber)
            ->whereNotIn('status', ['cancelled', 'canceled', 'undone'])
            ->get(['id', 'status', 'room_id']);

        if ($removeJobs->isEmpty()) {
            return collect();
        }

        $removedRoomIds = collect();
        foreach ($removeJobs as $job) {
            $jobDone = in_array(strtolower(trim((string) $job->status)), self::DONE_JOB_STATUSES, true);
            $rooms = JobScheduleRoom::with('jobAdviceRoom.contractRoom', 'jobAdviceRoom.quotationRoom')
                ->where('job_schedule_id', $job->id)
                ->get();

            if ($rooms->isEmpty()) {
                if ($jobDone && $job->room_id) {
                    $removedRoomIds->push((int) $job->room_id);
                }

                continue;
            }

            foreach ($rooms as $room) {
                if (! $jobDone && strtolower((string) $room->status) !== JobScheduleRoom::STATUS_COMPLETED) {
                    continue;
                }

                $roomId = $this->physicalRoomId($room);
                if ($roomId) {
                    $removedRoomIds->push($roomId);
                }
            }
        }

        $removedRoomIds = $removedRoomIds->unique()->values();
        if ($removedRoomIds->isEmpty()) {
            return $removedRoomIds;
        }

        // Unit SQ ini yang masih aktif di ruangan itu (dipasang ulang) → ruangan tetap di lokasi.
        // Unit trial SQ lain di ruangan yang sama tidak dihitung.
        $stillOnWallRoomIds = UnitOnWall::query()
            ->whereIn('status', self::ACTIVE_UNIT_STATUSES)
            ->whereIn('room_id', $removedRoomIds->all())
            ->whereHas('installJobSchedule', fn ($query) => $query->where('quotation_number', $quotationNumber))
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        return $removedRoomIds->diff($stillOnWallRoomIds)->values();
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
