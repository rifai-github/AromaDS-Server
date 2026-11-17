<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;
    
    protected $table = 'warehouses';

    /*
     * A branch may have several equivalent warehouses. `is_default` marks the
     * single active one the system picks when nobody chose a warehouse
     * (auto-created material issues etc.); admins can always pick another
     * warehouse of the same branch. See docs/warehouse-opsi3-implementation-plan.md.
     */
    protected $fillable = [
        'warehouse_code',
        'name',
        'branch_id',
        'warehouse_type_id',
        'is_default',
        'address',
        'phone',
        'manager',
        'is_active',
        'is_center',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_center' => 'boolean',
        'is_default' => 'boolean',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouseType()
    {
        return $this->belongsTo(WarehouseType::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function managerUser()
    {
        return $this->belongsTo(User::class, 'manager', 'id');
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'warehouse_admins', 'warehouse_id', 'user_id')->withTimestamps();
    }

    public function warehouseProducts()
    {
        return $this->hasMany(WarehouseProduct::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class);
    }

    public function serialNumbers()
    {
        return $this->hasMany(SerialNumber::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function inventoryIssuings()
    {
        return $this->hasMany(InventoryIssuing::class);
    }

    public function inventoryReceivings()
    {
        return $this->hasMany(InventoryReceiving::class);
    }

    public function inventoryRequests()
    {
        return $this->hasMany(InventoryRequest::class);
    }

    // Scopes
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBranch($query)
    {
        return $query->where('is_center', false);
    }

    public function scopeDefaultForBranch($query)
    {
        return $query->where('is_default', true);
    }

    public function isDefault(): bool
    {
        return (bool) $this->is_default;
    }

    // Business Logic Methods
    public function isCenter()
    {
        return $this->is_center;
    }

    public function isBranch()
    {
        return !$this->is_center;
    }

    public function isSameBranchAs(?Warehouse $other): bool
    {
        return $other
            && $this->branch_id
            && (int) $this->branch_id === (int) $other->branch_id;
    }

    /**
     * Branch-to-branch transfers go through central approval, except between
     * two warehouses of the same branch: a branch may run several equivalent
     * warehouses and moving stock between them is an internal matter.
     */
    public function transferNeedsCentralApproval(Warehouse $targetWarehouse): bool
    {
        return $this->isBranch()
            && $targetWarehouse->isBranch()
            && ! $this->isSameBranchAs($targetWarehouse);
    }

    public function canTransferTo($targetWarehouse)
    {
        // Center can transfer to any warehouse, including a center warehouse.
        // A branch must always route its transfer through a center warehouse.
        if ($this->isCenter()) {
            return true;
        }

        return $targetWarehouse->isCenter();
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('warehouse_code', 'like', "%{$code}%");
    }

    public function scopeCenter($query)
    {
        return $query->where('is_center', true);
    }

    public static function hasCenterWarehouse()
    {
        return self::where('is_center', true)->exists();
    }

    public static function getCenterWarehouse()
    {
        return self::where('is_center', true)->first();
    }

    public static function generateWarehouseCode($branchId, $warehouseTypeId)
    {
        $branch = Branch::find($branchId);
        $warehouseType = WarehouseType::find($warehouseTypeId);
        
        if (!$branch || !$warehouseType) {
            throw new \Exception('Branch or Warehouse Type not found');
        }
        
        $branchCode = strtoupper(substr($branch->name, 0, 3));
        $typeCode = $warehouseType->code;
        
        // Get the last warehouse code for this branch and type
        $lastWarehouse = self::where('branch_id', $branchId)
            ->where('warehouse_type_id', $warehouseTypeId)
            ->where('warehouse_code', 'like', "{$branchCode}-{$typeCode}-%")
            ->orderBy('warehouse_code', 'desc')
            ->first();
        
        if ($lastWarehouse) {
            // Extract the number from the last code
            $lastNumber = (int) substr($lastWarehouse->warehouse_code, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $branchCode . '-' . $typeCode . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    // Accessors

    public function getFullNameAttribute()
    {
        return $this->warehouse_code . ' - ' . $this->name;
    }

    public function getManagerNameAttribute()
    {
        if ($this->manager) {
            $user = User::find($this->manager);
            return $user ? $user->name : $this->manager;
        }
        return null;
    }
}
