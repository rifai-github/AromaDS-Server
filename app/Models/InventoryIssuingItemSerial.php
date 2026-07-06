<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * QA "1 Rental banyak Qty": links N distinct serial numbers to one
 * inventory_issuing_items row (used for unit products where quantity_requested > 1).
 */
class InventoryIssuingItemSerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_issuing_item_id',
        'serial_number_id',
        'unit_index',
        'created_by',
    ];

    protected $casts = [
        'unit_index' => 'integer',
    ];

    public function inventoryIssuingItem()
    {
        return $this->belongsTo(InventoryIssuingItem::class);
    }

    public function serialNumber()
    {
        return $this->belongsTo(SerialNumber::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
