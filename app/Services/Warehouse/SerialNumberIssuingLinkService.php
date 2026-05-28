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

        $items = InventoryIssuingItem::with('inventoryIssuing')
            ->where('serial_number_id', $serialNumber->id)
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

            $item->update([
                'serial_number_id' => null,
                'updated_by' => $actorId ?: $item->updated_by,
                'notes' => $notes !== '' ? "{$notes} | {$repairNote}" : $repairNote,
            ]);
        }

        return $items->count();
    }

    public function findPreparedLink(int $serialNumberId, ?int $exceptItemId = null): ?InventoryIssuingItem
    {
        return InventoryIssuingItem::with('inventoryIssuing')
            ->where('serial_number_id', $serialNumberId)
            ->when($exceptItemId, fn ($query) => $query->where('id', '!=', $exceptItemId))
            ->whereHas('inventoryIssuing', function ($query) {
                $query->whereIn('status', ['pending', 'processed']);
            })
            ->latest('id')
            ->first();
    }
}
