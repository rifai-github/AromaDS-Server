<?php

namespace App\Services\Warehouse;

use App\Models\InventoryReceiving;
use App\Models\InventoryReceivingItem;
use App\Models\SerialNumber;

/**
 * Un-queues a serial number from an Inventory Receiving that has not been finalized yet.
 *
 * `serial_numbers.inventory_receiving_id` is doing double duty: it is the queue marker
 * JobScheduleController::queueRemovedUnitReceiving() writes when a unit comes off a wall,
 * AND it is what the Receiving detail "Serial Numbers" tab lists. So when a unit goes back
 * OUT into the field before the warehouse finalizes that RR, the pointer has to be cleared —
 * otherwise the unit stays visible on an open RR it will never physically arrive at, and the
 * RR item quantity (counted from these same rows) stays inflated.
 *
 * Confirmed live 24 Aug 2026 on RR SBY-IRC/26-08/0010: DW300W2606014 was queued out at
 * 16:25, re-installed via Swap Unit at 16:46, and stayed listed on that RR as `In Use`
 * with the Diffuser W300 item showing qty 2 instead of 1.
 *
 * An RR that is already `received` is left alone: there the link is a completed historical
 * record of what the warehouse actually took in, not a pending queue entry.
 */
class SerialNumberReceivingQueueService
{
    public function release(?SerialNumber $serialNumber): bool
    {
        if (! $serialNumber || ! $serialNumber->inventory_receiving_id) {
            return false;
        }

        $receiving = InventoryReceiving::find($serialNumber->inventory_receiving_id);
        if (! $receiving || $receiving->status === 'received') {
            return false;
        }

        $productId = $serialNumber->master_product_id;

        $serialNumber->update([
            'inventory_receiving_id' => null,
            'notes' => $this->stripQueueNotes($serialNumber->notes, $receiving->receiving_number),
        ]);

        $this->recalculateItemQuantities($receiving, $productId);

        return true;
    }

    /**
     * Re-derive an RR item's quantity from the serial numbers still queued to it, mirroring
     * the count in JobScheduleController::queueRemovedUnitReceiving(). An auto-return item
     * that no longer has any serial number behind it is dropped entirely — keeping it would
     * ask the warehouse to receive a unit that is back in the field.
     */
    public function recalculateItemQuantities(InventoryReceiving $receiving, ?int $productId = null): void
    {
        $items = InventoryReceivingItem::where('inventory_receiving_id', $receiving->id)
            ->where('notes', 'like', 'Auto-return dari %')
            ->when($productId, fn ($query) => $query->where('master_product_id', $productId))
            ->get();

        foreach ($items as $item) {
            $queuedQty = SerialNumber::where('inventory_receiving_id', $receiving->id)
                ->where('master_product_id', $item->master_product_id)
                ->count();

            if ($queuedQty < 1) {
                $item->delete();

                continue;
            }

            if ((int) $item->quantity !== $queuedQty) {
                $item->update(['quantity' => $queuedQty]);
            }
        }
    }

    private function stripQueueNotes(?string $notes, string $receivingNumber): ?string
    {
        if (! $notes) {
            return $notes;
        }

        $kept = array_filter(
            preg_split('/\r\n|\r|\n/', $notes),
            fn ($line) => ! str_contains($line, "Queued to RR {$receivingNumber}")
        );

        $cleaned = trim(implode("\n", $kept));

        return $cleaned === '' ? null : $cleaned;
    }
}
