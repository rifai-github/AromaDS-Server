<?php

namespace App\Services\Operational;

use App\Models\Contract;
use App\Models\ContractRental;
use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\MasterRental;
use App\Models\MasterRoom;
use App\Models\UnitOnWall;
use App\Models\User;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Raises the Remove (RV) jobs for whatever a renewal dropped.
 *
 * When a contract is renewed the customer often keeps less than before: a room is taken out
 * entirely, or a rental's quantity goes from 2 down to 1. Nothing asked for those units back.
 * ContractRenewal::complete() only stamped a status, so the units stayed on the wall forever
 * and the warehouse never saw them again.
 *
 * Client spec (28 Aug 2026):
 *   - fires once the renewal contract is ACTIVE,
 *   - covers a room removed outright AND a quantity that was reduced,
 *   - takes the unit that has been installed longest, technician may swap on site,
 *   - schedules the RV on the renewal contract's start date - the units are still in use
 *     until the old contract runs out,
 *   - additions are NOT auto-installed: adding a room or quantity keeps going through Job
 *     Advice, which is the flow the team already wants.
 *
 * The Job Advice is created against the NEW contract on purpose. The units belong to the old
 * one, and JobScheduleController::resolveRenewalSourceContractForJobAdvice() walks the
 * renewal quotation back to it, so both contracts end up in scope when
 * UnitOnWall::scopeScopedToContracts() decides which units this Remove may touch.
 */
class ContractRenewalRemovalService
{
    private const ACTIVE_UNIT_STATUSES = ['active', 'installed', 'on_wall', 'on wall', 'onwall'];

    public function __construct(private DocumentNumberService $documentNumberService) {}

    /**
     * Entry point. Safe to call more than once: every RV job it creates carries a marker
     * naming the exact room+rental it covers, and an existing marker means the work is done.
     *
     * Returns the number of RV jobs raised.
     */
    public function handleActivatedRenewal(?Contract $newContract, ?Contract $oldContract): int
    {
        if (! $newContract || ! $oldContract || (int) $newContract->id === (int) $oldContract->id) {
            return 0;
        }

        $shortfalls = $this->resolveShortfalls($newContract, $oldContract);

        if (empty($shortfalls)) {
            return 0;
        }

        $created = 0;
        $jobAdvice = null;

        foreach ($shortfalls as $shortfall) {
            try {
                $units = $this->unitsToRemove($oldContract, $shortfall['room_id'], $shortfall['rental_id'], $shortfall['drop']);

                if ($units->isEmpty()) {
                    Log::info(sprintf(
                        'Renewal %s: room %d rental %d dropped %d unit(s) but none are on the wall - nothing to remove.',
                        $newContract->contract_number,
                        $shortfall['room_id'],
                        $shortfall['rental_id'],
                        $shortfall['drop']
                    ));

                    continue;
                }

                if ($this->removeJobAlreadyRaised($oldContract, $shortfall)) {
                    continue;
                }

                $jobAdvice = $jobAdvice ?: $this->resolveJobAdvice($newContract, $oldContract);

                if (! $jobAdvice) {
                    return $created;
                }

                if ($this->createRemoveJob($newContract, $oldContract, $jobAdvice, $shortfall, $units)) {
                    $created++;
                }
            } catch (\Throwable $e) {
                // A renewal must never fail to activate because a follow-up job could not be
                // raised. Log the gap loudly and carry on with the remaining rooms.
                Log::error(sprintf(
                    'Renewal %s: failed to raise Remove job for room %d rental %d: %s',
                    $newContract->contract_number,
                    $shortfall['room_id'],
                    $shortfall['rental_id'],
                    $e->getMessage()
                ));
            }
        }

        if ($created > 0) {
            Log::info("Renewal {$newContract->contract_number}: raised {$created} Remove job(s) for dropped rooms/quantities.");
        }

        return $created;
    }

    /**
     * What the renewal has less of, per room + rental.
     *
     * A room that disappears entirely is just the case where the new quantity is zero, so it
     * needs no branch of its own.
     */
    private function resolveShortfalls(Contract $newContract, Contract $oldContract): array
    {
        $old = $this->committedQuantities($oldContract->id);
        $new = $this->committedQuantities($newContract->id);

        $shortfalls = [];

        foreach ($old as $key => $oldQty) {
            $drop = $oldQty - ($new[$key] ?? 0);

            if ($drop <= 0) {
                continue;
            }

            [$roomId, $rentalId] = array_map('intval', explode(':', $key));

            $shortfalls[] = [
                'room_id' => $roomId,
                'rental_id' => $rentalId,
                'drop' => $drop,
            ];
        }

        return $shortfalls;
    }

    /**
     * Units the contract committed to, keyed "roomId:rentalId". Rows without a room cannot be
     * matched to anything on a wall, so they are skipped rather than guessed at.
     */
    private function committedQuantities(int $contractId): array
    {
        $quantities = [];

        ContractRental::where('contract_id', $contractId)
            ->get(['room_id', 'master_rental_id', 'quantity'])
            ->each(function ($rental) use (&$quantities) {
                if (! $rental->room_id || ! $rental->master_rental_id) {
                    return;
                }

                $key = ((int) $rental->room_id) . ':' . ((int) $rental->master_rental_id);
                $quantities[$key] = ($quantities[$key] ?? 0) + max(0, (int) $rental->quantity);
            });

        return $quantities;
    }

    /**
     * The longest-installed units first: the client's rule for which of several identical
     * units comes off when a quantity is reduced. The technician can still swap on site.
     */
    private function unitsToRemove(Contract $oldContract, int $roomId, int $rentalId, int $limit)
    {
        return UnitOnWall::where('contract_id', $oldContract->id)
            ->where('room_id', $roomId)
            ->where('rental_id', $rentalId)
            ->whereIn('status', self::ACTIVE_UNIT_STATUSES)
            ->orderByRaw('install_date IS NULL, install_date ASC')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    private function marker(Contract $oldContract, array $shortfall): string
    {
        return sprintf(
            '[renewal-removal:%d:%d:%d]',
            $oldContract->id,
            $shortfall['room_id'],
            $shortfall['rental_id']
        );
    }

    private function removeJobAlreadyRaised(Contract $oldContract, array $shortfall): bool
    {
        return JobSchedule::where('internal_notes', 'like', '%' . $this->marker($oldContract, $shortfall) . '%')->exists();
    }

    /**
     * One Remove Job Advice per renewal, reused across every dropped room.
     */
    private function resolveJobAdvice(Contract $newContract, Contract $oldContract): ?JobAdvice
    {
        $existing = JobAdvice::where('contract_id', $newContract->id)
            ->whereRaw('LOWER(type) = ?', ['remove'])
            ->where('reference_number', $oldContract->contract_number)
            ->first();

        if ($existing) {
            return $existing;
        }

        $number = $this->documentNumberService->generate('job_advice', null, null, $newContract->id);

        return JobAdvice::create([
            'job_advice_number' => $number,
            'type' => 'Remove',
            'contract_id' => $newContract->id,
            'customer_id' => $newContract->customer_id,
            'company_name' => $newContract->customer->name ?? null,
            'reference_number' => $oldContract->contract_number,
            'remark' => "Penarikan unit untuk ruangan/qty yang tidak dilanjutkan pada renewal {$newContract->contract_number} (dari {$oldContract->contract_number}).",
            'status' => 'approved',
            'date_approval' => now(),
            'with_invoicing' => false,
            'with_materials' => false,
            'created_by' => $this->actorId(),
            'updated_by' => $this->actorId(),
        ]);
    }

    private function createRemoveJob(
        Contract $newContract,
        Contract $oldContract,
        JobAdvice $jobAdvice,
        array $shortfall,
        $units
    ): bool {
        $room = MasterRoom::find($shortfall['room_id']);
        $rental = MasterRental::find($shortfall['rental_id']);

        if (! $room || ! $rental) {
            Log::warning(sprintf(
                'Renewal %s: cannot raise Remove job, room %d or rental %d not found.',
                $newContract->contract_number,
                $shortfall['room_id'],
                $shortfall['rental_id']
            ));

            return false;
        }

        // The units are still in use until the old contract runs out, so the RV belongs on the
        // day the renewal takes over - not the day somebody happened to activate it.
        $scheduleDate = $newContract->start_date
            ? \Illuminate\Support\Carbon::parse($newContract->start_date)->toDateString()
            : now()->toDateString();

        $contractRoom = $oldContract->contractRooms()->where('room_id', $room->id)->first();

        return DB::transaction(function () use (
            $newContract, $oldContract, $jobAdvice, $shortfall, $units, $room, $rental, $scheduleDate, $contractRoom
        ) {
            $jobNumber = $this->documentNumberService->generate(
                'remove',
                null,
                $room->building_id,
                null,
                null,
                null,
                null,
                null,
                $scheduleDate
            );

            $removeJob = JobSchedule::create([
                'job_number' => $jobNumber,
                'type' => 'remove',
                'status' => 'new_job',
                'job_advice_id' => $jobAdvice->id,
                'building_id' => $room->building_id,
                'building_name' => $room->building->building_name ?? null,
                'company_name' => $newContract->customer->name ?? null,
                'contract_number' => $oldContract->contract_number,
                'room_id' => $room->id,
                'room_name' => $room->room_name,
                'schedule_date' => $scheduleDate,
                'expected_date' => $scheduleDate,
                // Remove jobs take their units from Unit On Wall, never from Material Assign.
                'material_checked' => true,
                'material_checked_at' => now(),
                'internal_notes' => sprintf(
                    'Auto-created Remove job: renewal %s tidak melanjutkan %d unit "%s" di %s (kontrak lama %s). SN: %s. %s',
                    $newContract->contract_number,
                    $units->count(),
                    $rental->rental_name,
                    $room->room_name,
                    $oldContract->contract_number,
                    $units->pluck('serial_number')->filter()->implode(', ') ?: '-',
                    $this->marker($oldContract, $shortfall)
                ),
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ]);

            $jaRoom = JobAdviceRoom::create([
                'job_advice_id' => $jobAdvice->id,
                'contract_room_id' => $contractRoom?->id,
                'rental_product_id' => $rental->id,
                'room_name' => $room->room_name,
                'rental_name' => $rental->rental_name,
                'quantity' => $units->count(),
                'qty_free' => 0,
                'status' => JobAdviceRoom::STATUS_SCHEDULED,
                // All three pointers name this Remove job. A NULL pointer means "belongs to
                // every job of this JA", which would leak this room into the other rooms'
                // Remove jobs raised by the same renewal.
                'install_job_schedule_id' => $removeJob->id,
                'service_job_schedule_id' => $removeJob->id,
                'remove_job_schedule_id' => $removeJob->id,
                'notes' => "Tidak dilanjutkan pada renewal {$newContract->contract_number}.",
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ]);

            JobScheduleRoom::create([
                'job_schedule_id' => $removeJob->id,
                'job_advice_room_id' => $jaRoom->id,
                'room_name' => $room->room_name,
                'room_id' => $room->id,
                'status' => JobScheduleRoom::STATUS_PENDING,
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ]);

            Log::info("Renewal {$newContract->contract_number}: Remove job {$jobNumber} raised for {$units->count()} unit(s) in {$room->room_name}.");

            return true;
        });
    }

    private function actorId(): ?int
    {
        return auth()->id() ?? User::query()->value('id');
    }
}
