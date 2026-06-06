<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class SerialNumber extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    public const CONDITION_NEW = 'new';
    public const CONDITION_SECOND_READY = 'second_ready';
    public const CONDITION_DAMAGED = 'damaged';

    public const CONDITION_LABELS = [
        self::CONDITION_NEW => 'Baru',
        self::CONDITION_SECOND_READY => 'Bekas / Siap Pakai',
        self::CONDITION_DAMAGED => 'Rusak',
    ];

    protected $fillable = [
        'serial_number',
        'status',
        'condition_status',
        'location_type',
        'location_id',
        'notes',
        'warehouse_id',
        'master_product_id',
        'inventory_receiving_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'status' => 'string',
        'condition_status' => 'string',
        'location_type' => 'string',
        'location_id' => 'integer'
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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function inventoryReceiving()
    {
        return $this->belongsTo(InventoryReceiving::class);
    }

    public function unitOnWalls()
    {
        return $this->hasMany(UnitOnWall::class);
    }

    public function inventoryIssuingItems()
    {
        return $this->hasMany(InventoryIssuingItem::class);
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

    public function scopeBySerialNumber($query, $serialNumber)
    {
        return $query->where('serial_number', 'like', "%{$serialNumber}%");
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByLocationType($query, $locationType)
    {
        return $query->where('location_type', $locationType);
    }

    public function scopeByLocation($query, $locationType, $locationId)
    {
        return $query->where('location_type', $locationType)->where('location_id', $locationId);
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeBroken($query)
    {
        return $query->where('status', 'broken');
    }

    public function scopeOnService($query)
    {
        return $query->where('status', 'on_service');
    }

    // Legacy support - map available to ready
    public function scopeAvailable($query)
    {
        return $query->whereIn('status', ['ready', 'available']);
    }

    public function scopeInUse($query)
    {
        return $query->where('status', 'in_use');
    }

    public function scopeDamaged($query)
    {
        return $query->whereIn('status', ['broken', 'damaged']);
    }

    public function scopeByConditionStatus($query, string $conditionStatus)
    {
        return $query->where('condition_status', $conditionStatus);
    }

    public function scopeConditionInstallable($query)
    {
        return $query->where(function ($nested) {
            $nested->whereNull('condition_status')
                ->orWhereIn('condition_status', [
                    self::CONDITION_NEW,
                    self::CONDITION_SECOND_READY,
                ]);
        });
    }

    public function scopeInWarehouse($query)
    {
        return $query->where('location_type', 'warehouse');
    }

    public function scopeAtCustomer($query)
    {
        return $query->where('location_type', 'customer');
    }

    public function scopeWithTechnician($query)
    {
        return $query->where('location_type', 'technician');
    }

    public function scopeOnHand($query)
    {
        return $query->where('status', 'on_hand');
    }

    public function scopeOnHandRemove($query)
    {
        return $query->where('status', 'on_hand_remove');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'ready' => 'In Warehouse',
            'available' => 'In Warehouse',
            'broken' => 'Broken',
            'damaged' => 'Broken',
            'on_service' => 'On Service',
            'maintenance' => 'On Service',
            'in_use' => 'In Customer',
            'retired' => 'Retired',
            'on_hand' => 'On Hand - Teknisi',
            'on_hand_remove' => 'On Hand Remove - Teknisi'
        ];
        return $statuses[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getEffectiveConditionStatusAttribute(): string
    {
        if (in_array($this->status, ['broken', 'damaged', 'retired'], true)) {
            return self::CONDITION_DAMAGED;
        }

        $conditionStatus = $this->condition_status;

        if ($conditionStatus === 'used') {
            return self::CONDITION_SECOND_READY;
        }

        if (array_key_exists((string) $conditionStatus, self::CONDITION_LABELS)) {
            return $conditionStatus;
        }

        return self::CONDITION_NEW;
    }

    public function getConditionLabelAttribute(): string
    {
        return self::CONDITION_LABELS[$this->effective_condition_status] ?? self::CONDITION_LABELS[self::CONDITION_NEW];
    }

    public function getCanInstallAttribute(): bool
    {
        return $this->effective_condition_status !== self::CONDITION_DAMAGED;
    }

    public function getInstallBlockReasonAttribute(): ?string
    {
        if ($this->can_install) {
            return null;
        }

        return 'Unit dalam kondisi Rusak sehingga tidak boleh dipasang. Proses retur ke pusat diperlukan.';
    }

    public function getLocationTypeTextAttribute()
    {
        $types = [
            'warehouse' => 'Warehouse',
            'customer' => 'Customer',
            'technician' => 'Technician'
        ];
        return $types[$this->location_type] ?? $this->location_type;
    }

    /**
     * Get effective location type, deriving from relationships if location_type is null
     * This helps handle legacy data where location_type was not set
     */
    public function getEffectiveLocationTypeAttribute()
    {
        // If location_type is already set, return it
        if ($this->location_type) {
            return $this->location_type;
        }

        // Derive from relationships:
        // 1. If SN is in an active unit_on_wall (installed at customer), location is 'customer'
        // Check if relationship is already loaded first (to avoid N+1 queries)
        if ($this->relationLoaded('unitOnWalls')) {
            $activeUnitOnWall = $this->unitOnWalls->first(function($uow) {
                return $uow->status === 'active';
            });
        } else {
            $activeUnitOnWall = $this->unitOnWalls()->where('status', 'active')->first();
        }
        
        if ($activeUnitOnWall) {
            return 'customer';
        }

        // 2. If SN has a warehouse_id, location is 'warehouse'
        if ($this->warehouse_id) {
            return 'warehouse';
        }

        // 3. Default to 'warehouse' if nothing else matches
        return 'warehouse';
    }

    /**
     * Get effective location type display text
     */
    public function getEffectiveLocationTypeTextAttribute()
    {
        $types = [
            'warehouse' => 'Warehouse',
            'customer' => 'Customer',
            'technician' => 'Technician'
        ];
        $effectiveType = $this->effective_location_type;
        return $types[$effectiveType] ?? ucfirst($effectiveType);
    }

    public function getIsReadyAttribute()
    {
        return in_array($this->status, ['ready', 'available']); // Legacy support
    }

    public function getIsAvailableAttribute()
    {
        return in_array($this->status, ['ready', 'available']); // Legacy support
    }

    public function getIsInUseAttribute()
    {
        return $this->status === 'in_use';
    }

    public function getIsBrokenAttribute()
    {
        return in_array($this->status, ['broken', 'damaged']); // Legacy support
    }

    public function getIsDamagedAttribute()
    {
        return in_array($this->status, ['broken', 'damaged']); // Legacy support
    }

    public function getIsOnServiceAttribute()
    {
        return in_array($this->status, ['on_service', 'maintenance']); // Legacy support
    }

    public function getIsInWarehouseAttribute()
    {
        return $this->location_type === 'warehouse';
    }

    public function getIsAtCustomerAttribute()
    {
        return $this->location_type === 'customer';
    }

    public function getIsWithTechnicianAttribute()
    {
        return $this->location_type === 'technician';
    }

    public function getIsOnHandAttribute()
    {
        return $this->status === 'on_hand';
    }

    public function getIsOnHandRemoveAttribute()
    {
        return $this->status === 'on_hand_remove';
    }

    public function getFullNameAttribute()
    {
        return $this->serial_number . ' - ' . ($this->masterProduct->name ?? 'Unknown') . ' - ' . ($this->warehouse->name ?? 'Unknown');
    }
}
