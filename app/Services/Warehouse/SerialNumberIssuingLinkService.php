<?php

namespace App\Services\Warehouse;

use App\Models\InventoryIssuingItem;
use App\Models\SerialNumber;

class SerialNumberIssuingLinkService
{
    public function isReadyInWarehouse(SerialNumber $serialNumber): bool
    {
        if (!in_array($serialNumber->status, ['ready', 'available'], true)) {
            return false;
        }

        if (! $serialNumber->can_install) {
            return false;
        }

        if ($serialNumber->location_type && $serialNumber->location_type !== 'warehouse') {
            return false;
        }

        return ! $serialNumber->unitOnWalls()
            ->where('status', 'active')
            ->exists();
    }

    public function requiresExclusiveLink(SerialNumber $serialNumber): bool
    {
        return (bool) ($serialNumber->masterProduct?->requiresUniqueSerialNumber() ?? true);
    }

    public function releaseStaleLinks(SerialNumber $serialNumber, ?int $exceptItemId = null, ?int $actorId = null): int
    {
        if (! $this->isReadyInWarehouse($serialNumber)) {
            return 0;
        }

        // QA "1 Rental banyak Qty": a unit SN may be linked as the 2nd/3rd slot of a row
        // only via the pivot table, so stale links must be released from both places.
        $items = InventoryIssuingItem::with('inventoryIssuing')
            ->where(function ($query) use ($serialNumber) {
                $query->where('serial_number_id', $serialNumber->id)
                    ->orWhereHas('serialLinks', function ($linkQuery) use ($serialNumber) {
                        $linkQuery->where('serial_number_id', $serialNumber->id);
                    });
            })
            ->when($exceptItemId, fn ($query) => $query->where('id', '!=', $exceptItemId))
            ->whereHas('inventoryIssuing', function ($query) {
                $query->whereIn('status', ['sent', 'received']);
            })
            ->lockForUpdate()
            ->get();

        foreach ($items as $item) {
            $notes = trim((string) $item->notes);
            $repairNote = 'Released stale SN link on ' . now()->format('Y-m-d H:i:s')
                . ' | SN: ' . $serialNumber->serial_number
                . ' | WI: ' . ($item->inventoryIssuing?->issuing_number ?? '-');

            $item->serialLinks()->where('serial_number_id', $serialNumber->id)->delete();

            $item->update([
                'serial_number_id' => $item->serial_number_id === $serialNumber->id
                    ? ($item->serialLinks()->orderBy('unit_index')->value('serial_number_id'))
                    : $item->serial_number_id,
                'updated_by' => $actorId ?: $item->updated_by,
                'notes' => $notes !== '' ? "{$notes} | {$repairNote}" : $repairNote,
            ]);
        }

        return $items->count();
    }

    public function findPreparedLink(int $serialNumberId, ?int $exceptItemId = null): ?InventoryIssuingItem
    {
        return InventoryIssuingItem::with('inventoryIssuing')
            ->where(function ($query) use ($serialNumberId) {
                $query->where('serial_number_id', $serialNumberId)
                    ->orWhereHas('serialLinks', function ($linkQuery) use ($serialNumberId) {
                        $linkQuery->where('serial_number_id', $serialNumberId);
                    });
            })
            ->when($exceptItemId, fn ($query) => $query->where('id', '!=', $exceptItemId))
            ->whereHas('inventoryIssuing', function ($query) {
                $query->whereIn('status', ['pending', 'processed']);
            })
            ->latest('id')
            ->first();
    }
}
