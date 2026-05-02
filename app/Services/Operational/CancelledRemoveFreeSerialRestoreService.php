<?php

namespace App\Services\Operational;

use App\Models\JobSchedule;
use App\Models\UnitOnWall;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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

        $criteria = $this->buildUnitCriteria($removeJob);
        if ($criteria->isEmpty()) {
            return collect();
        }

        $units = $this->queryActiveUnits($removeJob, $criteria)->get();

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
}
