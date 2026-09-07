<?php

namespace App\Services\Operational;

use App\Models\ContractRental;
use App\Models\JobAdvice;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\MasterRental;
use App\Models\UnitOnWall;
use App\Models\User;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Applies the contract-side effects of a completed Change Rental (Ganti Rental) job.
 *
 * A Change Rental Job Advice only ever wrote the new rental onto its OWN
 * job_advice_rooms row (JobAdviceController::updateRoomRental). Finishing the field
 * job therefore left the contract, the remaining service periods and the old unit
 * untouched: the contract still billed the old rental, service period 3..N still
 * pulled the old rental's BOM, and the replaced unit stayed "on the wall" forever
 * because nothing ever asked for it back.
 *
 * On completion this service:
 *   1. moves the contract_rentals row for that room onto the new rental,
 *   2. moves the not-yet-executed service periods onto the new rental,
 *   3. auto-creates a Remove (RV) job that returns the replaced unit to stock.
 *
 * NOTE on job types: a "Change Rental" Job Advice (type `change_rental`) generates a
 * JobSchedule whose type is normalised to `change` (JobAdviceController), which is the
 * SAME schedule type a "Change Unit" JA produces. Only the Job Advice type separates
 * them, so every gate here is keyed on the JA type — a Change Unit swaps the physical
 * device while keeping the rental, and must never reach this code.
 */
class ChangeRentalCompletionService
{
    private const CHANGE_SCHEDULE_TYPES = ['change', 'change_rental', 'change_unit'];

    private const COMPLETED_STATUSES = ['done_job', 'completed', 'selesai'];

    private const SERVICE_SCHEDULE_TYPES = ['service', 'servis', 'service_first', 'service_routine'];

    private const OPEN_SCHEDULE_STATUSES_EXCLUDED = ['done_job', 'completed', 'selesai', 'cancelled', 'undone'];

    public function __construct(private DocumentNumberService $documentNumberService) {}

    /**
     * Is this schedule the field job of a Change Rental Job Advice?
     */
    public static function isChangeRentalJob(?JobSchedule $job, $jobAdvice = null): bool
    {
        if (! $job) {
            return false;
        }

        $type = strtolower(trim(str_replace([' ', '-'], '_', (string) $job->type)));
        if (! in_array($type, self::CHANGE_SCHEDULE_TYPES, true)) {
            return false;
        }

        $jobAdvice = $jobAdvice ?: $job->jobAdvice;
        $jaType = strtolower(trim(str_replace([' ', '-'], '_', (string) ($jobAdvice->type ?? ''))));

        return $jaType === 'change_rental';
    }

    /**
     * Entry point: safe to call for ANY completed job — it no-ops unless the job is a
     * completed Change Rental job, and it is idempotent per changed room.
     */
    public function handleCompletedJob(?JobSchedule $job, $jobAdvice = null): void
    {
        if (! $job) {
            return;
        }

        $jobAdvice = $jobAdvice ?: $job->jobAdvice;

        if (! self::isChangeRentalJob($job, $jobAdvice)) {
            return;
        }

        if (! in_array(strtolower(trim((string) $job->status)), self::COMPLETED_STATUSES, true)) {
            return;
        }

        if (! $jobAdvice->contract_id) {
            Log::warning("Change Rental job {$job->job_number}: JA {$jobAdvice->job_advice_number} has no contract. Skipping rental change.");

            return;
        }

        $changedRooms = JobScheduleRoom::where('job_schedule_id', $job->id)
            ->where('status', JobScheduleRoom::STATUS_COMPLETED)
            ->whereNotNull('job_advice_room_id')
            ->get();

        if ($changedRooms->isEmpty()) {
            return;
        }

        foreach ($changedRooms as $scheduleRoom) {
            $newJaRoom = JobAdviceRoom::with(['rentalProduct', 'contractRoom'])->find($scheduleRoom->job_advice_room_id);

            if (! $newJaRoom) {
                continue;
            }

            try {
                DB::transaction(fn () => $this->applyForRoom($job, $jobAdvice, $newJaRoom, $scheduleRoom));
            } catch (\Throwable $e) {
                // Never let this break the technician's completion flow — the job itself is
                // already done; a failed rental swap is repairable, a rolled back completion
                // is not.
                Log::error("Change Rental job {$job->job_number}: failed to apply rental change for JA room {$newJaRoom->id}: ".$e->getMessage());
            }
        }
    }

    private function applyForRoom(JobSchedule $job, JobAdvice $jobAdvice, JobAdviceRoom $newJaRoom, JobScheduleRoom $scheduleRoom): void
    {
        $newRental = $newJaRoom->rentalProduct;

        if (! $newRental) {
            Log::warning("Change Rental job {$job->job_number}: JA room {$newJaRoom->id} has no rental_product_id. Skipping.");

            return;
        }

        $roomId = $newJaRoom->contractRoom?->room_id
            ?? $newJaRoom->room_id
            ?? $scheduleRoom->room_id;

        if (! $roomId) {
            Log::warning("Change Rental job {$job->job_number}: cannot resolve the physical room for JA room {$newJaRoom->id}. Skipping.");

            return;
        }

        if ($this->alreadyApplied($newJaRoom)) {
            return;
        }

        $replacedJaRoom = $this->resolveReplacedJobAdviceRoom($jobAdvice, $newJaRoom, (int) $roomId, (int) $newRental->id);
        $replacedUnit = $this->resolveReplacedUnitOnWall($jobAdvice, (int) $roomId, (int) $newRental->id);
        $contractRental = $this->resolveContractRental($jobAdvice, (int) $roomId, $replacedJaRoom, $replacedUnit);

        $oldRental = $replacedJaRoom?->rentalProduct
            ?? ($replacedUnit?->rental_id ? MasterRental::find($replacedUnit->rental_id) : null)
            ?? $contractRental?->masterRental;

        if ($oldRental && (int) $oldRental->id === (int) $newRental->id) {
            Log::info("Change Rental job {$job->job_number}: JA room {$newJaRoom->id} already sits on rental {$newRental->id}. Nothing to change.");

            return;
        }

        // How much of the line is actually moving. A room can hold several units of the
        // same rental and the customer may swap only some of them: QA 6 Sep 2026 changed
        // ONE of the two units in "Ruang Ganti Rental Qty 2" and lost the other, because
        // every step below treated the change as replacing the whole line.
        $changedQuantity = max(1, (int) ($newJaRoom->quantity ?: 1));
        $lineQuantity = (int) ($contractRental?->quantity ?: $replacedJaRoom?->quantity ?: 0);
        $remainingQuantity = $lineQuantity > $changedQuantity ? $lineQuantity - $changedQuantity : 0;

        $newContractRental = $this->syncContractRental(
            $jobAdvice,
            (int) $roomId,
            $contractRental,
            $newRental,
            $newJaRoom,
            $changedQuantity,
            $remainingQuantity
        );

        $removeJob = null;
        if ($oldRental) {
            $removeJob = $this->createRemoveJobForReplacedRental(
                $job,
                $jobAdvice,
                $newJaRoom,
                $replacedJaRoom,
                $oldRental,
                (int) $roomId,
                $changedQuantity
            );
        } else {
            Log::warning("Change Rental job {$job->job_number}: could not identify the replaced rental for room {$roomId}. No Remove job created.");
        }

        // Done last: it overwrites the rental the steps above read from.
        $this->moveRemainingServicesToNewRental(
            $jobAdvice,
            (int) $roomId,
            $newJaRoom,
            $newRental,
            $job,
            $changedQuantity,
            $remainingQuantity,
            $newContractRental
        );

        Log::info(sprintf(
            'Change Rental job %s: room %d moved from rental %s to rental %s%s.',
            $job->job_number,
            $roomId,
            $oldRental?->rental_name ?? '(unknown)',
            $newRental->rental_name,
            $removeJob ? ", Remove job {$removeJob->job_number} created" : ''
        ));
    }

    /**
     * The Remove job carries a marker tying it back to the Change Rental JA room, so a
     * re-completion (undone → done again, offline replay, sibling propagation) never
     * creates a second RV for the same swap.
     */
    private function alreadyApplied(JobAdviceRoom $newJaRoom): bool
    {
        return JobSchedule::whereIn(DB::raw('LOWER(type)'), ['remove', 'removal'])
            ->where('internal_notes', 'like', '%'.$this->changeMarker($newJaRoom).'%')
            ->whereNotIn(DB::raw('LOWER(status)'), ['cancelled'])
            ->exists();
    }

    private function changeMarker(JobAdviceRoom $newJaRoom): string
    {
        return "[change-rental-room:{$newJaRoom->id}]";
    }

    /**
     * The job_advice_rooms row that still carries the OLD rental for this physical room —
     * i.e. the one the remaining service periods hang off. Resolved from the open service
     * schedules first (that is exactly the data we must move), then from the unit that is
     * physically on the wall.
     */
    private function resolveReplacedJobAdviceRoom(JobAdvice $jobAdvice, JobAdviceRoom $newJaRoom, int $roomId, int $newRentalId): ?JobAdviceRoom
    {
        $candidateIds = $this->openServiceJobAdviceRoomIds($jobAdvice, $roomId);

        $fromServices = JobAdviceRoom::with('rentalProduct')
            ->whereIn('id', $candidateIds)
            ->where('id', '!=', $newJaRoom->id)
            ->where('rental_product_id', '!=', $newRentalId)
            ->orderByDesc('id')
            ->first();

        if ($fromServices) {
            return $fromServices;
        }

        $replacedUnit = $this->resolveReplacedUnitOnWall($jobAdvice, $roomId, $newRentalId);

        if ($replacedUnit && $replacedUnit->install_job_schedule_id) {
            $installJaRoomIds = JobScheduleRoom::where('job_schedule_id', $replacedUnit->install_job_schedule_id)
                ->whereNotNull('job_advice_room_id')
                ->pluck('job_advice_room_id');

            $fromUnit = JobAdviceRoom::with('rentalProduct')
                ->whereIn('id', $installJaRoomIds)
                ->where('id', '!=', $newJaRoom->id)
                ->where('rental_product_id', $replacedUnit->rental_id)
                ->orderByDesc('id')
                ->first();

            if ($fromUnit) {
                return $fromUnit;
            }
        }

        // Last resort: another JA of the same contract that installed into this room.
        return JobAdviceRoom::with('rentalProduct')
            ->where('contract_room_id', $newJaRoom->contract_room_id)
            ->where('id', '!=', $newJaRoom->id)
            ->where('job_advice_id', '!=', $newJaRoom->job_advice_id)
            ->where('rental_product_id', '!=', $newRentalId)
            ->whereNotNull('install_job_schedule_id')
            ->orderByDesc('id')
            ->first();
    }

    private function resolveReplacedUnitOnWall(JobAdvice $jobAdvice, int $roomId, int $newRentalId): ?UnitOnWall
    {
        return UnitOnWall::where('room_id', $roomId)
            ->where('contract_id', $jobAdvice->contract_id)
            ->whereIn('status', ['active', 'installed', 'on_wall', 'on wall', 'onwall'])
            ->where(function ($query) use ($newRentalId) {
                $query->whereNull('rental_id')->orWhere('rental_id', '!=', $newRentalId);
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * job_advice_rooms ids feeding every service period of this room that has NOT run yet,
     * across every Job Advice of the contract.
     */
    private function openServiceJobAdviceRoomIds(JobAdvice $jobAdvice, int $roomId): array
    {
        $contractJobAdviceIds = JobAdvice::where('contract_id', $jobAdvice->contract_id)->pluck('id');

        return JobScheduleRoom::where('room_id', $roomId)
            ->whereNotNull('job_advice_room_id')
            ->whereHas('jobSchedule', function ($query) use ($contractJobAdviceIds) {
                $query->whereIn('job_advice_id', $contractJobAdviceIds)
                    ->whereIn(DB::raw('LOWER(type)'), self::SERVICE_SCHEDULE_TYPES)
                    ->whereNotIn(DB::raw('LOWER(status)'), self::OPEN_SCHEDULE_STATUSES_EXCLUDED);
            })
            ->pluck('job_advice_room_id')
            ->unique()
            ->values()
            ->all();
    }

    private function resolveContractRental(JobAdvice $jobAdvice, int $roomId, ?JobAdviceRoom $replacedJaRoom, ?UnitOnWall $replacedUnit): ?ContractRental
    {
        $base = fn () => ContractRental::where('contract_id', $jobAdvice->contract_id)->where('room_id', $roomId);

        if ($replacedJaRoom?->contract_rental_id) {
            $byId = ContractRental::find($replacedJaRoom->contract_rental_id);
            if ($byId && (int) $byId->contract_id === (int) $jobAdvice->contract_id) {
                return $byId;
            }
        }

        foreach ([$replacedJaRoom?->rental_product_id, $replacedUnit?->rental_id] as $rentalId) {
            if (! $rentalId) {
                continue;
            }

            $match = $base()->where('master_rental_id', $rentalId)->first();
            if ($match) {
                return $match;
            }
        }

        return $base()->first();
    }

    /**
     * Point the contract at the new rental. The contract row is updated in place: it is
     * what invoicing reads for the NEXT period, and already-issued invoices keep their own
     * snapshot in invoice_rental_details.
     *
     * When only part of a multi-unit line moves, the old row is kept at the remaining
     * quantity and the new rental gets a row of its own instead - otherwise the units that
     * were never touched stop being billed and disappear from every later service.
     *
     * @return ContractRental|null the row the NEW rental now lives on
     */
    private function syncContractRental(
        JobAdvice $jobAdvice,
        int $roomId,
        ?ContractRental $contractRental,
        MasterRental $newRental,
        JobAdviceRoom $newJaRoom,
        int $changedQuantity = 1,
        int $remainingQuantity = 0
    ): ?ContractRental {
        $quantity = max(1, $changedQuantity);
        $qtyFree = (int) ($newJaRoom->qty_free ?? $contractRental?->qty_free ?? 0);

        // The Change Rental JA has no negotiated price of its own, so the new rental's
        // master price is the only automatic source. Keep the contract's existing price
        // when the master rental has none (all master prices are 0 on a fresh bootstrap).
        $unitPrice = (float) ($newRental->monthly_price ?? 0);
        if ($unitPrice <= 0) {
            $unitPrice = (float) ($contractRental?->unit_price ?? 0);
        }

        $payload = [
            'master_rental_id' => $newRental->id,
            // The old alias described the old rental; drop it so displays fall back to the
            // real (new) rental name instead of silently keeping the replaced label.
            'rental_alias' => null,
            'quantity' => $quantity,
            'qty_free' => $qtyFree,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'updated_by' => $this->actorId(),
        ];

        if ($contractRental && $remainingQuantity > 0) {
            $oldUnitPrice = (float) ($contractRental->unit_price ?? 0);

            $contractRental->update([
                'quantity' => $remainingQuantity,
                'total_price' => $oldUnitPrice * $remainingQuantity,
                'updated_by' => $this->actorId(),
            ]);

            return ContractRental::create(array_merge($payload, [
                'contract_id' => $jobAdvice->contract_id,
                'room_id' => $roomId,
                'created_by' => $this->actorId(),
            ]));
        }

        if ($contractRental) {
            $contractRental->update($payload);

            return $contractRental;
        }

        return ContractRental::create(array_merge($payload, [
            'contract_id' => $jobAdvice->contract_id,
            'room_id' => $roomId,
            'created_by' => $this->actorId(),
        ]));
    }

    /**
     * Move every service period that has not run yet onto the new rental, so material
     * assign and the CSR document both follow the change.
     */
    private function moveRemainingServicesToNewRental(
        JobAdvice $jobAdvice,
        int $roomId,
        JobAdviceRoom $newJaRoom,
        MasterRental $newRental,
        JobSchedule $job,
        int $changedQuantity = 1,
        int $remainingQuantity = 0,
        ?ContractRental $newContractRental = null
    ): void {
        $jaRoomIds = collect($this->openServiceJobAdviceRoomIds($jobAdvice, $roomId))
            ->reject(fn ($id) => (int) $id === (int) $newJaRoom->id)
            ->values();

        if ($jaRoomIds->isEmpty()) {
            return;
        }

        $rooms = JobAdviceRoom::whereIn('id', $jaRoomIds)
            ->where('rental_product_id', '!=', $newRental->id)
            ->get();

        if ($rooms->isEmpty()) {
            return;
        }

        if ($remainingQuantity > 0) {
            $this->splitRemainingServicesForPartialChange(
                $jobAdvice,
                $roomId,
                $rooms,
                $newRental,
                $job,
                $changedQuantity,
                $remainingQuantity,
                $newContractRental
            );

            return;
        }

        foreach ($rooms as $room) {
            $room->update([
                'rental_product_id' => $newRental->id,
                'rental_name' => $newRental->rental_name,
                'quantity' => (int) ($newJaRoom->quantity ?: $room->quantity ?: 1),
                'qty_free' => (int) ($newJaRoom->qty_free ?? $room->qty_free ?? 0),
                'updated_by' => $this->actorId(),
            ]);
        }
    }

    /**
     * Only some of the room's units moved, so the later service periods must carry BOTH
     * rentals: the old one at its reduced quantity, and a sibling row for the new one.
     *
     * The sibling is modelled exactly like a room that was sold with two rentals from the
     * start - one job_advice_rooms row per rental, one job_schedule_rooms row per service
     * period - which is the shape the mobile card list, material assign and the BA already
     * understand. service_job_schedule_id names the latest period by convention; ownership
     * of a period is decided by its job_schedule_rooms row, not by that pointer.
     */
    private function splitRemainingServicesForPartialChange(
        JobAdvice $jobAdvice,
        int $roomId,
        $oldRooms,
        MasterRental $newRental,
        JobSchedule $job,
        int $changedQuantity,
        int $remainingQuantity,
        ?ContractRental $newContractRental
    ): void {
        foreach ($oldRooms as $room) {
            $room->update([
                'quantity' => $remainingQuantity,
                'updated_by' => $this->actorId(),
            ]);
        }

        $scheduleRooms = $this->openServiceScheduleRooms($jobAdvice, $roomId);

        if ($scheduleRooms->isEmpty()) {
            return;
        }

        $template = $oldRooms->first();

        $siblingRoom = JobAdviceRoom::create([
            'job_advice_id' => $template->job_advice_id,
            'contract_room_id' => $template->contract_room_id,
            'contract_rental_id' => $newContractRental?->id,
            'rental_product_id' => $newRental->id,
            'room_name' => $template->room_name,
            'rental_name' => $newRental->rental_name,
            'quantity' => $changedQuantity,
            'qty_free' => 0,
            'status' => JobAdviceRoom::STATUS_SCHEDULED,
            'service_job_schedule_id' => (int) $scheduleRooms->max('job_schedule_id'),
            'notes' => "Rental baru dari Change Rental {$job->job_number}.",
            'created_by' => $this->actorId(),
            'updated_by' => $this->actorId(),
        ]);

        foreach ($scheduleRooms->groupBy('job_schedule_id') as $scheduleId => $rows) {
            $existing = JobScheduleRoom::where('job_schedule_id', $scheduleId)
                ->where('job_advice_room_id', $siblingRoom->id)
                ->exists();

            if ($existing) {
                continue;
            }

            JobScheduleRoom::create([
                'job_schedule_id' => $scheduleId,
                'job_advice_room_id' => $siblingRoom->id,
                'room_name' => $template->room_name,
                'room_id' => $roomId,
                'status' => JobScheduleRoom::STATUS_PENDING,
                'created_by' => $this->actorId(),
                'updated_by' => $this->actorId(),
            ]);
        }

        Log::info(sprintf(
            'Change Rental job %s: room %d split - %d unit(s) stay on the old rental, %d moved to %s (JA room %d).',
            $job->job_number,
            $roomId,
            $remainingQuantity,
            $changedQuantity,
            $newRental->rental_name,
            $siblingRoom->id
        ));
    }

    /**
     * The job_schedule_rooms rows of every service period of this room that has not run yet.
     */
    private function openServiceScheduleRooms(JobAdvice $jobAdvice, int $roomId)
    {
        $contractJobAdviceIds = JobAdvice::where('contract_id', $jobAdvice->contract_id)->pluck('id');

        return JobScheduleRoom::where('room_id', $roomId)
            ->whereNotNull('job_advice_room_id')
            ->whereHas('jobSchedule', function ($query) use ($contractJobAdviceIds) {
                $query->whereIn('job_advice_id', $contractJobAdviceIds)
                    ->whereIn(DB::raw('LOWER(type)'), self::SERVICE_SCHEDULE_TYPES)
                    ->whereNotIn(DB::raw('LOWER(status)'), self::OPEN_SCHEDULE_STATUSES_EXCLUDED);
            })
            ->get();
    }

    /**
     * Create the RV job that takes the replaced unit off the wall and back into stock.
     *
     * The replaced rental is frozen onto a dedicated job_advice_rooms row instead of being
     * read from the room's live row, because that live row is about to become the NEW
     * rental — and JobScheduleController::autoRemoveUnitOnWall() picks which unit to pull
     * by `unit_on_walls.rental_id = jobAdviceRoom.rentalProduct->id`. Without the frozen
     * row the RV would hunt for the new unit and leave the old one on the wall.
     */
    private function createRemoveJobForReplacedRental(
        JobSchedule $changeJob,
        JobAdvice $jobAdvice,
        JobAdviceRoom $newJaRoom,
        ?JobAdviceRoom $replacedJaRoom,
        MasterRental $oldRental,
        int $roomId,
        int $changedQuantity = 1
    ): ?JobSchedule {
        $scheduleDate = $changeJob->schedule_date ?? now()->toDateString();

        $jobNumber = $this->documentNumberService->generate(
            'remove',
            null,
            $changeJob->building_id,
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
            'building_id' => $changeJob->building_id,
            'building_name' => $changeJob->building_name,
            'company_name' => $changeJob->company_name,
            'contract_number' => $changeJob->contract_number,
            'quotation_number' => $changeJob->quotation_number,
            'room_id' => $roomId,
            'room_name' => $newJaRoom->room_name,
            'schedule_date' => $scheduleDate,
            'expected_date' => $scheduleDate,
            // Remove jobs take their units from Unit On Wall, never from Material Assign.
            'material_checked' => true,
            'material_checked_at' => now(),
            'internal_notes' => sprintf(
                'Auto-created Remove job for rental "%s" replaced by Change Rental %s (JA %s). %s',
                $oldRental->rental_name,
                $changeJob->job_number,
                $jobAdvice->job_advice_number,
                $this->changeMarker($newJaRoom)
            ),
            'created_by' => $this->actorId(),
            'updated_by' => $this->actorId(),
        ]);

        $removalJaRoom = JobAdviceRoom::create([
            'job_advice_id' => $jobAdvice->id,
            'contract_room_id' => $newJaRoom->contract_room_id,
            'contract_rental_id' => $replacedJaRoom?->contract_rental_id,
            'rental_product_id' => $oldRental->id,
            'room_name' => $newJaRoom->room_name,
            'rental_name' => $oldRental->rental_name,
            // Only the units that were actually swapped come off the wall. Taking the whole
            // line's quantity pulled BOTH units of a qty-2 room when one was replaced
            // (QA 6 Sep 2026, SBY-RV/26-09/0006 emptied "Ruang Ganti Rental Qty 2").
            'quantity' => max(1, min($changedQuantity, (int) ($replacedJaRoom?->quantity ?: $changedQuantity))),
            'qty_free' => (int) ($replacedJaRoom?->qty_free ?? 0),
            'status' => JobAdviceRoom::STATUS_SCHEDULED,
            // All three pointers deliberately name the Remove job: this row exists only to
            // hand the replaced rental to that job. JobController::jobAdviceRoomBelongsToJobSchedule()
            // treats a NULL pointer as "belongs to every job of this JA", which would leak the
            // replaced rental back into the Change job's own room list and documents.
            'install_job_schedule_id' => $removeJob->id,
            'service_job_schedule_id' => $removeJob->id,
            'remove_job_schedule_id' => $removeJob->id,
            'notes' => "Rental lama, digantikan oleh Change Rental {$changeJob->job_number}.",
            'created_by' => $this->actorId(),
            'updated_by' => $this->actorId(),
        ]);

        JobScheduleRoom::create([
            'job_schedule_id' => $removeJob->id,
            'job_advice_room_id' => $removalJaRoom->id,
            'room_name' => $newJaRoom->room_name,
            'room_id' => $roomId,
            'status' => JobScheduleRoom::STATUS_PENDING,
            'created_by' => $this->actorId(),
            'updated_by' => $this->actorId(),
        ]);

        return $removeJob;
    }

    private function actorId(): ?int
    {
        return auth()->id() ?? User::query()->value('id');
    }
}
