<?php

namespace App\Services;

use App\Models\InventoryIssuingItem;
use App\Models\InventoryIssuingItemSerial;
use App\Models\SerialNumber;
use Illuminate\Support\Facades\Log;

/**
 * Temporary SN trial-mode bypass, gated by SN_BYPASS_ENABLED (config('features.sn_bypass_enabled')).
 *
 * While enabled, the mobile SN checks (install scan, swap unit, material pickup) accept
 * serial numbers the warehouse never pre-registered/linked, instead of rejecting them.
 * The scanned SN is still written to `serial_numbers` (and linked to the relevant
 * InventoryIssuingItem where applicable) so downstream flows - job completion,
 * Unit On Wall, stock movement - behave exactly as if the SN had been registered normally.
 */
class SerialNumberBypassService
{
    public static function isEnabled(): bool
    {
        return (bool) config('features.sn_bypass_enabled', false);
    }

    /**
     * Find-or-create a SerialNumber row for $serialNumberInput and make sure it is
     * linked to $issuingItem (via inventory_issuing_item_serials), mirroring what the
     * warehouse "scan SN for issuing" flow does (see InventoryIssuingController).
     */
    public static function registerAndLinkSerial(
        string $serialNumberInput,
        InventoryIssuingItem $issuingItem,
        string $status = 'on_hand',
        ?string $locationType = 'technician',
        ?int $locationId = null,
        ?int $actorId = null
    ): SerialNumber {
        $normalized = SerialNumber::normalizeSerialCode($serialNumberInput);
        $actorId = $actorId ?? auth()->id();

        $serialNumber = SerialNumber::whereNormalizedSerialNumber($normalized)->first();

        if (! $serialNumber) {
            $serialNumber = SerialNumber::create([
                'serial_number' => $normalized,
                'master_product_id' => $issuingItem->product_id,
                'warehouse_id' => $issuingItem->inventoryIssuing?->warehouse_id,
                'status' => $status,
                'condition_status' => SerialNumber::CONDITION_NEW,
                'location_type' => $locationType,
                'location_id' => $locationId,
                'notes' => 'Auto-registered via SN bypass trial mode',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            Log::warning('SN bypass: auto-registered unknown serial number', [
                'serial_number' => $normalized,
                'inventory_issuing_item_id' => $issuingItem->id,
                'master_product_id' => $issuingItem->product_id,
            ]);
        }

        $alreadyLinked = InventoryIssuingItemSerial::where('inventory_issuing_item_id', $issuingItem->id)
            ->where('serial_number_id', $serialNumber->id)
            ->exists();

        if (! $alreadyLinked) {
            $nextUnitIndex = (InventoryIssuingItemSerial::where('inventory_issuing_item_id', $issuingItem->id)->max('unit_index') ?? 0) + 1;

            InventoryIssuingItemSerial::create([
                'inventory_issuing_item_id' => $issuingItem->id,
                'serial_number_id' => $serialNumber->id,
                'unit_index' => $nextUnitIndex,
                'created_by' => $actorId,
            ]);

            if (! $issuingItem->serial_number_id) {
                $issuingItem->update(['serial_number_id' => $serialNumber->id, 'updated_by' => $actorId]);
            }

            Log::info('SN bypass: linked serial number to inventory issuing item', [
                'serial_number_id' => $serialNumber->id,
                'inventory_issuing_item_id' => $issuingItem->id,
                'unit_index' => $nextUnitIndex,
            ]);
        }

        return $serialNumber;
    }
}
