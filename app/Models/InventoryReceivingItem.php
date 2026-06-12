<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReceivingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_receiving_id',
        'master_product_id',
        'quantity',
        'quantity_received',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_received' => 'integer',
    ];

    /**
     * Get the inventory receiving that owns the item.
     */
    public function inventoryReceiving(): BelongsTo
    {
        return $this->belongsTo(InventoryReceiving::class);
    }

    /**
     * Get the product that owns the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }

    /**
     * Get the total value of this item.
     */
    public function getTotalValueAttribute(): float
    {
        return 0.0;
    }
}
