<?php

namespace App\Services\Operational;

use App\Models\JobSchedule;
use App\Models\UnitOnWall;
use App\Models\UnitOnWallHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CancelledRemoveFreeSerialRestoreService
{
    private const REMOVE_FREE_TYPES = ['remove_free', 'remove free'];
    private const ACTIVE_UNIT_STATUSES = ['active', 'installed', 'on_wall', 'on wall', 'onwall'];

    public function restore(JobSchedule $removeJob, bool $apply = false): Collection
    {
        if (!$this->isEligibleRemoveFree($removeJob)) {
            return collect();
        }

        $removeJob->loadMissing([
            'jobAdvice',
            'jobScheduleRooms.rentals.jobAdviceRoom',
            'jobScheduleRooms.jobAdviceRoom',
        ]);

        $units = $this->queryScannedActiveUnits($removeJob);

        $criteria = $this->buildUnitCriteria($removeJob);
        if ($criteria->isNotEmpty()) {
            $units = $units
                ->merge($this->queryActiveUnits($removeJob, $criteria)->get())
                ->unique('id')
                ->values();
        }

        $rows = collect();

        foreach ($units as $unit) {
            $serialNumber = $unit->serialNumber;
            if (!$serialNumber || $serialNumber->status !== 'on_hand_remove') {
                continue;
            }

            $customerId = $unit->customer_id ?: $removeJob->jobAdvice?->customer_id;

            $rows->push([
                'job_schedule_id' => $removeJob->id,
                'job_number' => $removeJob->job_number,
                'unit_on_wall_id' => $unit->id,
                'serial_number_id' => $serialNumber->id,
                'serial_number' => $serialNumber->serial_number,
                'room_id' => $unit->room_id,
                'rental_id' => $unit->rental_id,
                'from_status' => $serialNumber->status,
                'to_status' => 'in_use',
                'location_type' => 'customer',
                'location_id' => $customerId,
                'applied' => $apply,
            ]);

            if ($apply) {
                $serialNumber->update([
                    'status' => 'in_use',
                    'location_type' => 'customer',
                    'location_id' => $customerId,
                    'updated_by' => Auth::id() ?? $removeJob->updated_by ?? $removeJob->created_by,
                    'updated_at' => now(),
                ]);
            }
        }

        return $rows;
    }

    public function restoreConfirmedSerials(
        JobSchedule $removeJob,
        array $serialNumbers,
        bool $reactivateRemovedUnit = false,
        bool $apply = false
    ): Collection {
        if (!$this->isEligibleRemoveFree($removeJob)) {
            return collect();
        }

        $serialNumbers = collect($serialNumbers)
            ->map(fn ($serial) => trim((string) $serial))
            ->filter()
            ->unique()
            ->values();

        if ($serialNumbers->isEmpty()) {
            return collect();
        }

        $removeJob->loadMissing([
            'jobAdvice',
            'jobScheduleRooms.rentals.jobAdviceRoom',
            'jobScheduleRooms.jobAdviceRoom',
        ]);

        $criteria = $this->buildUnitCriteria($removeJob);
        $units = $this->queryConfirmedUnits($removeJob, $criteria, $serialNumbers->all())->get();
        $rows = collect();

        foreach ($units as $unit) {
            $serialNumber = $unit->serialNumber;
            if (!$serialNumber) {
                continue;
            }

            $unitStatus = strtolower(trim((string) $unit->status));
            $canActivateUnit = in_array($unitStatus, self::ACTIVE_UNIT_STATUSES, true)
                || ($reactivateRemovedUnit && $unitStatus === 'removed');

            $activeConflicts = $this->findActiveUnitConflicts($unit);
            $canApply = $canActivateUnit && $activeConflicts->isEmpty();
            $customerId = $unit->customer_id ?: $removeJob->jobAdvice?->customer_id;

            $rows->push([
                'job_schedule_id' => $removeJob->id,
                'job_number' => $removeJob->job_number,
                'unit_on_wall_id' => $unit->id,
                'serial_number_id' => $serialNumber->id,
                'serial_number' => $serialNumber->serial_number,
                'room_id' => $unit->room_id,
                'rental_id' => $unit->rental_id,
                'from_status' => $serialNumber->status,
                'from_unit_status' => $unit->status,
                'to_status' => 'in_use',
                'to_unit_status' => 'active',
                'location_type' => 'customer',
                'location_id' => $customerId,
                'reactivate_unit' => $unitStatus === 'removed',
                'conflict_unit_ids' => $activeConflicts->pluck('id')->all(),
                'can_apply' => $canApply,
                'applied' => $apply && $canApply,
            ]);

            if ($apply && $canApply) {
                $updaterId = Auth::id() ?? $removeJob->updated_by ?? $removeJob->created_by;

                $unit->update([
                    'status' => 'active',
                    'updated_by' => $updaterId,
                    'updated_at' => now(),
                ]);

                $serialNumber->update([
                    'status' => 'in_use',
                    'location_type' => 'customer',
                    'location_id' => $customerId,
                    'updated_by' => $updaterId,
                    'updated_at' => now(),
                ]);

                UnitOnWallHistory::create([
                    'unit_on_wall_id' => $unit->id,
                    'action' => 'repair',
                    'customer_id' => $customerId,
                    'customer_name' => $unit->customer?->company_name ?? $unit->customer?->name,
                    'location' => trim(($unit->building?->building_name ?? '') . ' - ' . ($unit->room?->room_name ?? ''), ' -'),
                    'action_date' => now()->toDateString(),
                    'job_schedule_id' => $removeJob->id,
                    'job_schedule_number' => $removeJob->job_number,
                    'notes' => 'Repair RF cancelled: confirmed physical SN still installed; restore Unit On Wall and SN to In Use.',
                    'metadata' => [
                        'serial_number' => $serialNumber->serial_number,
                        'previous_serial_status' => $serialNumber->getOriginal('status'),
                        'previous_unit_status' => $unit->getOriginal('status'),
                        'source' => 'jobs:repair-cancelled-remove-free-sn',
                    ],
                    'created_by' => $updaterId,
                ]);
            }
        }

        return $rows;
    }

    private function isEligibleRemoveFree(JobSchedule $removeJob): bool
    {
        $type = strtolower(trim((string) $removeJob->type));

        return $removeJob->status === 'cancelled'
            && in_array($type, self::REMOVE_FREE_TYPES, true);
    }

    private function buildUnitCriteria(JobSchedule $removeJob): Collection
    {
        $criteria = collect();

        foreach ($removeJob->jobScheduleRooms as $scheduleRoom) {
            if (!$scheduleRoom->room_id) {
                continue;
            }

            $rentalIds = $scheduleRoom->rentals
                ->map(fn ($rentalLink) => $rentalLink->jobAdviceRoom?->rental_product_id)
                ->push($scheduleRoom->jobAdviceRoom?->rental_product_id)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $criteria->push([
                'room_id' => (int) $scheduleRoom->room_id,
                'rental_ids' => $rentalIds->all(),
            ]);
        }

        if ($criteria->isEmpty() && $removeJob->room_id) {
            $criteria->push([
                'room_id' => (int) $removeJob->room_id,
                'rental_ids' => [],
            ]);
        }

        return $criteria
            ->unique(fn ($item) => $item['room_id'] . ':' . implode(',', $item['rental_ids']))
            ->values();
    }

    private function queryScannedActiveUnits(JobSchedule $removeJob): Collection
    {
        $unitIds = DB::table('job_schedule_units')
            ->where('job_schedule_id', $removeJob->id)
            ->whereNotNull('unit_on_wall_id')
            ->pluck('unit_on_wall_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($unitIds->isEmpty()) {
            return collect();
        }

        return UnitOnWall::with('serialNumber')
            ->whereIn('id', $unitIds->all())
            ->whereIn('status', self::ACTIVE_UNIT_STATUSES)
            ->whereNotNull('serial_number_id')
            ->whereHas('serialNumber', function ($serialQuery) {
                $serialQuery->where('status', 'on_hand_remove');
            })
            ->orderBy('room_id')
            ->orderBy('rental_id')
            ->orderBy('id')
            ->get();
    }

    private function queryActiveUnits(JobSchedule $removeJob, Collection $criteria)
    {
        $query = UnitOnWall::with('serialNumber')
            ->whereIn('status', self::ACTIVE_UNIT_STATUSES)
            ->whereNotNull('serial_number_id')
            ->whereHas('serialNumber', function ($serialQuery) {
                $serialQuery->where('status', 'on_hand_remove');
            });

        if ($removeJob->jobAdvice?->customer_id) {
            $query->where('customer_id', $removeJob->jobAdvice->customer_id);
        }

        if ($removeJob->building_id) {
            $query->where('building_id', $removeJob->building_id);
        }

        $query->where(function ($criteriaQuery) use ($criteria) {
            foreach ($criteria as $criterion) {
                $criteriaQuery->orWhere(function ($unitQuery) use ($criterion) {
                    $unitQuery->where('room_id', $criterion['room_id']);

                    if (!empty($criterion['rental_ids'])) {
                        $unitQuery->whereIn('rental_id', $criterion['rental_ids']);
                    }
                });
            }
        });

        return $query->orderBy('room_id')->orderBy('rental_id')->orderBy('id');
    }

    private function queryConfirmedUnits(JobSchedule $removeJob, Collection $criteria, array $serialNumbers)
    {
        $query = UnitOnWall::with(['serialNumber', 'customer', 'building', 'room'])
            ->whereNotNull('serial_number_id')
            ->whereHas('serialNumber', function ($serialQuery) use ($serialNumbers) {
                $serialQuery->whereIn('serial_number', $serialNumbers)
                    ->whereIn('status', ['on_hand_remove', 'on_hand', 'in_use']);
            })
            ->whereIn('status', array_merge(self::ACTIVE_UNIT_STATUSES, ['removed']));

        if ($removeJob->jobAdvice?->customer_id) {
            $query->where('customer_id', $removeJob->jobAdvice->customer_id);
        }

        if ($removeJob->building_id) {
            $query->where('building_id', $removeJob->building_id);
        }

        if ($criteria->isNotEmpty()) {
            $query->where(function ($criteriaQuery) use ($criteria) {
                foreach ($criteria as $criterion) {
                    $criteriaQuery->orWhere(function ($unitQuery) use ($criterion) {
                        $unitQuery->where('room_id', $criterion['room_id']);

                        if (!empty($criterion['rental_ids'])) {
                            $unitQuery->whereIn('rental_id', $criterion['rental_ids']);
                        }
                    });
                }
            });
        }

        return $query->orderBy('room_id')->orderBy('rental_id')->orderBy('id');
    }

    private function findActiveUnitConflicts(UnitOnWall $unit): Collection
    {
        $query = UnitOnWall::query()
            ->whereKeyNot($unit->id)
            ->whereIn('status', self::ACTIVE_UNIT_STATUSES)
            ->where('customer_id', $unit->customer_id)
            ->where('building_id', $unit->building_id)
            ->where('room_id', $unit->room_id);

        if ($unit->rental_id) {
            $query->where('rental_id', $unit->rental_id);
        } else {
            $query->whereNull('rental_id');
        }

        return $query->orderBy('id')->get(['id', 'serial_number_id']);
    }
}
