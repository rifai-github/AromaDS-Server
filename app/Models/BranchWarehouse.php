<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchWarehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'is_primary',
        'is_active'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // Accessors
    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getIsPrimaryTextAttribute()
    {
        return $this->is_primary ? 'Ya' : 'Tidak';
    }

    // Business Logic Methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function isPrimary()
    {
        return $this->is_primary;
    }

    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }

    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    public function setAsPrimary()
    {
        // Remove primary status from other warehouses in the same branch
        self::where('branch_id', $this->branch_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);
        
        $this->is_primary = true;
        $this->save();
    }

    public function removePrimaryStatus()
    {
        $this->is_primary = false;
        $this->save();
    }

    // Static Methods
    public static function getPrimaryWarehouse($branchId)
    {
        return self::where('branch_id', $branchId)
                  ->where('is_primary', true)
                  ->where('is_active', true)
                  ->first();
    }

    public static function getActiveWarehouses($branchId)
    {
        return self::where('branch_id', $branchId)
                  ->where('is_active', true)
                  ->with('warehouse')
                  ->get();
    }

    public static function assignWarehouseToBranch($branchId, $warehouseId, $isPrimary = false)
    {
        // If setting as primary, remove primary status from other warehouses
        if ($isPrimary) {
            self::where('branch_id', $branchId)
                ->update(['is_primary' => false]);
        }

        return self::create([
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'is_primary' => $isPrimary,
            'is_active' => true
        ]);
    }

    public static function removeWarehouseFromBranch($branchId, $warehouseId)
    {
        return self::where('branch_id', $branchId)
                  ->where('warehouse_id', $warehouseId)
                  ->delete();
    }
}
