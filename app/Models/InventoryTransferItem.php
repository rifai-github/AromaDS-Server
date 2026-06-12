<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryTransferItem extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'inventory_transfer_items';

    protected $fillable = [
        'inventory_transfer_id',
        'master_product_id',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'integer'
    ];

    // Relationships
    public function inventoryTransfer()
    {
        return $this->belongsTo(InventoryTransfer::class);
    }

    public function product()
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
