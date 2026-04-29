<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'movement_no',
        'movement_type',
        'warehouse_id',
        'master_product_id',
        'quantity',
        'unit_price',
        'total_value',
        'movement_date',
        'reference_no',
        'reference_type',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
        'movement_date' => 'date'
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

    // Scopes
    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByMasterProduct($query, $masterProductId)
    {
        return $query->where('master_product_id', $masterProductId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('movement_date', [$startDate, $endDate]);
    }

    public function scopeByReferenceType($query, $referenceType)
    {
        return $query->where('reference_type', $referenceType);
    }

    public function scopeByReferenceNo($query, $referenceNo)
    {
        return $query->where('reference_no', 'like', "%{$referenceNo}%");
    }

    public function scopeInbound($query)
    {
        return $query->whereIn('movement_type', ['purchase', 'transfer_in', 'adjustment_in', 'return']);
    }

    public function scopeOutbound($query)
    {
        return $query->whereIn('movement_type', ['sale', 'transfer_out', 'adjustment_out', 'issue']);
    }

    // Accessors
    public function getTypeTextAttribute()
    {
        $types = [
            'purchase' => 'Purchase',
            'sale' => 'Sale',
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
            'adjustment_in' => 'Adjustment In',
            'adjustment_out' => 'Adjustment Out',
            'issue' => 'Issue',
            'return' => 'Return'
        ];
        return $types[$this->movement_type] ?? $this->movement_type;
    }

    public function getFormattedMovementDateAttribute()
    {
        return $this->movement_date ? $this->movement_date->format('d/m/Y') : '-';
    }

    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 0, ',', '.');
    }

    public function getFormattedUnitPriceAttribute()
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getFormattedTotalValueAttribute()
    {
        return 'Rp ' . number_format($this->total_value, 0, ',', '.');
    }

    public function getIsInboundAttribute()
    {
        return in_array($this->movement_type, ['purchase', 'transfer_in', 'adjustment_in', 'return']);
    }

    public function getIsOutboundAttribute()
    {
        return in_array($this->movement_type, ['sale', 'transfer_out', 'adjustment_out', 'issue']);
    }
}