<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryIssuingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_issuing_id',
        'job_assign_schedule_id',
        'room_name',
        'product_id',
        'serial_number_id',
        'quantity_requested',
        'quantity_issued',
        'quantity_received',
        'unit_price',
        'total_price',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity_requested' => 'integer',
        'quantity_issued' => 'integer',
        'quantity_received' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function inventoryIssuing()
    {
        return $this->belongsTo(InventoryIssuing::class);
    }

    public function product()
    {
        return $this->belongsTo(MasterProduct::class, 'product_id');
    }

    public function serialNumber()
    {
        return $this->belongsTo(SerialNumber::class);
    }

    /**
     * QA "1 Rental banyak Qty": all serial numbers linked to this row (unit products
     * with quantity_requested > 1 need one distinct SN per unit). serial_number_id
     * stays in sync with the first linked row for backward compatibility.
     */
    public function serialLinks()
    {
        return $this->hasMany(InventoryIssuingItemSerial::class)->orderBy('unit_index');
    }

    public function serialNumbers()
    {
        return $this->belongsToMany(
            SerialNumber::class,
            'inventory_issuing_item_serials',
            'inventory_issuing_item_id',
            'serial_number_id'
        )->withPivot(['unit_index'])->orderBy('inventory_issuing_item_serials.unit_index');
    }

    /**
     * How many distinct serial numbers this row needs. Unit products need one SN per
     * physical unit (quantity_requested); aroma/refill batch products only ever need 1
     * SN regardless of qty (one batch code covers the whole quantity).
     */
    public function requiredSerialCount(): int
    {
        $product = $this->product;
        if (!$product || !$product->requiresSerialNumber()) {
            return 0;
        }

        if (!$product->requiresUniqueSerialNumber()) {
            return 1;
        }

        return max(1, (int) round((float) ($this->quantity_requested ?? 1)));
    }

    public function linkedSerialCount(): int
    {
        return $this->relationLoaded('serialLinks')
            ? $this->serialLinks->count()
            : $this->serialLinks()->count();
    }

    public function hasAllRequiredSerials(): bool
    {
        if ($this->usesBatchSerial()) {
            return $this->linkedSerialCount() > 0 && $this->coveredBatchQuantity() >= $this->serialQuantity();
        }

        return $this->linkedSerialCount() >= $this->requiredSerialCount();
    }

    /**
     * Still waiting for a mandatory serial: a unit row short of one SN per unit, or a
     * batch row whose linked batches do not yet cover its whole quantity.
     */
    public function isAwaitingSerial(): bool
    {
        return $this->requiredSerialCount() > 0 && ! $this->hasAllRequiredSerials();
    }

    /**
     * Aroma/refill product: SN wajib tapi tidak unik (satu kode batch = banyak botol).
     */
    public function usesBatchSerial(): bool
    {
        $product = $this->product;

        return $product && $product->requiresSerialNumber() && ! $product->requiresUniqueSerialNumber();
    }

    /**
     * Physical units this row hands over, as the serial allocation counts them.
     */
    public function serialQuantity(): int
    {
        foreach (['quantity_received', 'quantity_issued', 'quantity_requested'] as $field) {
            $quantity = (float) ($this->{$field} ?? 0);
            if ($quantity > 0) {
                return max(1, (int) ceil($quantity));
            }
        }

        return 1;
    }

    /**
     * Jawaban klien #7 (UAT 18 Nov 2026): a batch row may draw its quantity from several
     * batch codes (batch 1 sisa 1 botol, batch 2 sisa 1, ...). Each linked batch carries
     * its own bottle count; a NULL count means "whatever the other batches leave", which
     * is exactly the old single-batch behaviour (one code covers the whole quantity).
     *
     * @return \Illuminate\Support\Collection<int, array{link_id: ?int, unit_index: int, serial_number_id: int, serial_number: ?SerialNumber, quantity: int, explicit: bool}>
     */
    public function batchSerialAllocations(): \Illuminate\Support\Collection
    {
        $total = $this->serialQuantity();
        $links = $this->relationLoaded('serialLinks') ? $this->serialLinks : $this->serialLinks()->with('serialNumber')->get();
        $links = $links->sortBy('unit_index')->values();

        if ($links->isEmpty()) {
            if (! $this->serial_number_id) {
                return collect();
            }

            return collect([[
                'link_id' => null,
                'unit_index' => 1,
                'serial_number_id' => (int) $this->serial_number_id,
                'serial_number' => $this->serialNumber,
                'quantity' => $total,
                'explicit' => false,
            ]]);
        }

        $explicitSum = (int) $links->whereNotNull('quantity')->sum('quantity');
        $remainder = max(0, $total - $explicitSum);
        $left = $total;

        return $links->map(function ($link) use (&$left, &$remainder) {
            $explicit = $link->quantity !== null;
            $quantity = $explicit ? (int) $link->quantity : $remainder;
            if (! $explicit) {
                $remainder = 0; // Only one link can take the remainder.
            }

            // Never hand over more than the row's quantity, even if counts were edited.
            $quantity = max(0, min($quantity, $left));
            $left -= $quantity;

            return [
                'link_id' => (int) $link->id,
                'unit_index' => (int) $link->unit_index,
                'serial_number_id' => (int) $link->serial_number_id,
                'serial_number' => $link->relationLoaded('serialNumber') ? $link->serialNumber : $link->serialNumber()->first(),
                'quantity' => $quantity,
                'explicit' => $explicit,
            ];
        })->values();
    }

    public function coveredBatchQuantity(): int
    {
        return (int) $this->batchSerialAllocations()->sum('quantity');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByIssuing($query, $issuingId)
    {
        return $query->where('inventory_issuing_id', $issuingId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByQuantityRange($query, $minQuantity, $maxQuantity)
    {
        return $query->whereBetween('quantity_requested', [$minQuantity, $maxQuantity]);
    }

    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('unit_price', [$minPrice, $maxPrice]);
    }

    public function scopeFullyIssued($query)
    {
        return $query->whereRaw('quantity_issued >= quantity_requested');
    }

    public function scopePartiallyIssued($query)
    {
        return $query->whereRaw('quantity_issued > 0 AND quantity_issued < quantity_requested');
    }

    public function scopeNotIssued($query)
    {
        return $query->where('quantity_issued', 0);
    }

    public function scopeFullyReceived($query)
    {
        return $query->whereRaw('quantity_received >= quantity_issued');
    }

    public function scopePartiallyReceived($query)
    {
        return $query->whereRaw('quantity_received > 0 AND quantity_received < quantity_issued');
    }

    public function scopeNotReceived($query)
    {
        return $query->where('quantity_received', 0);
    }

    // Accessors
    public function getQuantityRequestedFormattedAttribute()
    {
        return number_format($this->quantity_requested, 0, ',', '.');
    }

    public function getQuantityIssuedFormattedAttribute()
    {
        return number_format($this->quantity_issued, 0, ',', '.');
    }

    public function getQuantityReceivedFormattedAttribute()
    {
        return number_format($this->quantity_received, 0, ',', '.');
    }

    public function getUnitPriceFormattedAttribute()
    {
        return $this->unit_price ? currency_symbol().' ' . number_format($this->unit_price, 0, ',', '.') : 'N/A';
    }

    public function getTotalPriceFormattedAttribute()
    {
        return $this->total_price ? currency_symbol().' ' . number_format($this->total_price, 0, ',', '.') : 'N/A';
    }

    public function getProductNameFormattedAttribute()
    {
        return ucwords($this->product_name);
    }

    public function getProductTypeFormattedAttribute()
    {
        return $this->product_type ? ucwords($this->product_type) : 'N/A';
    }

    public function getIssuingProgressAttribute()
    {
        if ($this->quantity_requested == 0) {
            return 0;
        }
        return round(($this->quantity_issued / $this->quantity_requested) * 100, 2);
    }

    public function getReceivingProgressAttribute()
    {
        if ($this->quantity_issued == 0) {
            return 0;
        }
        return round(($this->quantity_received / $this->quantity_issued) * 100, 2);
    }

    public function getIssuingStatusAttribute()
    {
        if ($this->quantity_issued >= $this->quantity_requested) {
            return 'completed';
        } elseif ($this->quantity_issued > 0) {
            return 'partial';
        } else {
            return 'pending';
        }
    }

    public function getReceivingStatusAttribute()
    {
        if ($this->quantity_received >= $this->quantity_issued) {
            return 'completed';
        } elseif ($this->quantity_received > 0) {
            return 'partial';
        } else {
            return 'pending';
        }
    }

    public function getIssuingStatusBadgeAttribute()
    {
        $badges = [
            'completed' => 'badge-success',
            'partial' => 'badge-warning',
            'pending' => 'badge-secondary',
        ];

        return $badges[$this->issuing_status] ?? 'badge-secondary';
    }

    public function getReceivingStatusBadgeAttribute()
    {
        $badges = [
            'completed' => 'badge-success',
            'partial' => 'badge-warning',
            'pending' => 'badge-secondary',
        ];

        return $badges[$this->receiving_status] ?? 'badge-secondary';
    }

    public function getRemainingToIssueAttribute()
    {
        return max(0, $this->quantity_requested - $this->quantity_issued);
    }

    public function getRemainingToReceiveAttribute()
    {
        return max(0, $this->quantity_issued - $this->quantity_received);
    }

    // Methods
    public function canIssue($quantity = null)
    {
        $remaining = $this->remaining_to_issue;
        if ($quantity === null) {
            return $remaining > 0;
        }
        return $remaining >= $quantity;
    }

    public function canReceive($quantity = null)
    {
        $remaining = $this->remaining_to_receive;
        if ($quantity === null) {
            return $remaining > 0;
        }
        return $remaining >= $quantity;
    }

    public function issue($quantity)
    {
        if ($this->canIssue($quantity)) {
            $this->quantity_issued += $quantity;
            $this->calculateTotalPrice();
            $this->save();
            return true;
        }
        return false;
    }

    public function receive($quantity)
    {
        if ($this->canReceive($quantity)) {
            $this->quantity_received += $quantity;
            $this->save();
            return true;
        }
        return false;
    }

    public function calculateTotalPrice()
    {
        if ($this->unit_price) {
            $this->total_price = $this->unit_price * $this->quantity_issued;
        }
    }

    public function recalculateTotalPrice()
    {
        $this->calculateTotalPrice();
        $this->save();
    }

    public function getIssuingInfo()
    {
        return $this->inventoryIssuing;
    }

    public function getProductInfo()
    {
        return $this->product;
    }

    public function getProductTypeInfo()
    {
        return $this->productType;
    }

    public function getWarehouseStock()
    {
        return $this->warehouseProduct;
    }

    public function getStockAvailability()
    {
        $warehouseProduct = $this->warehouseProduct;
        return $warehouseProduct ? $warehouseProduct->stock_quantity : 0;
    }

    public function hasSufficientStock()
    {
        return $this->getStockAvailability() >= $this->remaining_to_issue;
    }

    public function isFullyIssued()
    {
        return $this->issuing_status === 'completed';
    }

    public function isFullyReceived()
    {
        return $this->receiving_status === 'completed';
    }

    public function isPartiallyIssued()
    {
        return $this->issuing_status === 'partial';
    }

    public function isPartiallyReceived()
    {
        return $this->receiving_status === 'partial';
    }

    public function isPendingIssue()
    {
        return $this->issuing_status === 'pending';
    }

    public function isPendingReceive()
    {
        return $this->receiving_status === 'pending';
    }
}
