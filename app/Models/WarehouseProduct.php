<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'master_product_id',
        'quantity',
        'minimum_stock',
        'maximum_stock',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer'
    ];

    // Relationships
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function masterProduct()
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'master_product_id', 'master_product_id')
                    ->where('warehouse_id', $this->warehouse_id);
    }

    // Scopes
    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByMasterProduct($query, $masterProductId)
    {
        return $query->where('master_product_id', $masterProductId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'minimum_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    public function scopeOverStock($query)
    {
        return $query->whereColumn('quantity', '>', 'maximum_stock');
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    // Accessors
    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 0, ',', '.');
    }

    public function getFormattedMinStockAttribute()
    {
        return number_format($this->minimum_stock, 0, ',', '.');
    }

    public function getFormattedMaxStockAttribute()
    {
        return number_format($this->maximum_stock, 0, ',', '.');
    }

    public function getIsLowStockAttribute()
    {
        return $this->quantity <= $this->minimum_stock;
    }

    public function getIsOutOfStockAttribute()
    {
        return $this->quantity <= 0;
    }

    public function getIsOverStockAttribute()
    {
        return $this->quantity > $this->maximum_stock;
    }

    public function getStockStatusAttribute()
    {
        if ($this->is_out_of_stock) {
            return 'out_of_stock';
        } elseif ($this->is_low_stock) {
            return 'low_stock';
        } elseif ($this->is_over_stock) {
            return 'over_stock';
        } else {
            return 'normal';
        }
    }

    public function getStockStatusTextAttribute()
    {
        $statuses = [
            'out_of_stock' => 'Out of Stock',
            'low_stock' => 'Low Stock',
            'over_stock' => 'Over Stock',
            'normal' => 'Normal'
        ];
        return $statuses[$this->stock_status] ?? 'Unknown';
    }

    public function getStockValueAttribute()
    {
        return $this->quantity * 0; // No average_cost column in database
    }

    public function getFormattedStockValueAttribute()
    {
        return 'Rp ' . number_format($this->stock_value, 0, ',', '.');
    }
}
