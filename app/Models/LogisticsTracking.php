<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;
use App\Http\Traits\AutoFilterable;

class LogisticsTracking extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail, AutoFilterable;

    protected $table = 'logistics_tracking';

    protected $fillable = [
        'tracking_number',
        'inventory_request_id',
        'from_warehouse_id',
        'to_branch_id',
        'status',
        'resi_number',
        'courier_name',
        'notes',
        'requested_at',
        'preparing_at',
        'shipped_at',
        'delivered_at',
        'returned_at',
        'cancelled_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'preparing_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    // Relationships
    public function inventoryRequest()
    {
        return $this->belongsTo(InventoryRequest::class);
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function beritaAcara()
    {
        return $this->hasMany(BeritaAcara::class);
    }

    public function purchasingRequests()
    {
        return $this->hasMany(PurchasingRequest::class);
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
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('from_warehouse_id', $warehouseId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('to_branch_id', $branchId);
    }

    public function scopeByResiNumber($query, $resiNumber)
    {
        return $query->where('resi_number', 'like', '%' . $resiNumber . '%');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'requested' => 'warning',
            'preparing' => 'info',
            'shipped' => 'primary',
            'delivered' => 'success',
            'returned' => 'secondary',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }

    public function getProgressPercentageAttribute()
    {
        return match($this->status) {
            'requested' => 25,
            'preparing' => 50,
            'shipped' => 75,
            'delivered' => 100,
            'returned' => 100,
            'cancelled' => 0,
            default => 0
        };
    }

    public function getCanBeCancelledAttribute()
    {
        return in_array($this->status, ['requested', 'preparing']);
    }

    public function getCanBeReturnedAttribute()
    {
        return $this->status === 'delivered';
    }

    // Helper methods

    public function getEstimatedDeliveryDateAttribute()
    {
        if ($this->shipped_at) {
            return $this->shipped_at->addDays(3); // Assuming 3 days delivery
        }
        return null;
    }

    public function getDaysInTransitAttribute()
    {
        if ($this->shipped_at && $this->status !== 'delivered') {
            return $this->shipped_at->diffInDays(now());
        }
        return 0;
    }

    public function isOverdue()
    {
        if ($this->status === 'shipped' && $this->shipped_at) {
            return $this->shipped_at->addDays(5)->isPast(); // Overdue after 5 days
        }
        return false;
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['requested', 'preparing']);
    }

    public function canBeReturned()
    {
        return $this->status === 'delivered';
    }

    public function updateStatus($status, $notes = null)
    {
        $this->update([
            'status' => $status,
            'notes' => $notes,
            'updated_by' => auth()->id()
        ]);

        // Update timestamp based on status
        $timestampField = $status . '_at';
        if (in_array($timestampField, ['requested_at', 'preparing_at', 'shipped_at', 'delivered_at', 'returned_at', 'cancelled_at'])) {
            $this->update([$timestampField => now()]);
        }
    }

    public function generateTrackingNumber()
    {
        $prefix = 'TRK';
        $date = now()->format('Ymd');
        $sequence = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $date . $sequence;
    }

    public static function createTracking($inventoryRequestId, $fromWarehouseId, $toBranchId)
    {
        $tracking = new self();
        $tracking->tracking_number = $tracking->generateTrackingNumber();
        $tracking->inventory_request_id = $inventoryRequestId;
        $tracking->from_warehouse_id = $fromWarehouseId;
        $tracking->to_branch_id = $toBranchId;
        $tracking->status = 'requested';
        $tracking->requested_at = now();
        $tracking->created_by = auth()->id();
        $tracking->updated_by = auth()->id();
        $tracking->save();

        return $tracking;
    }
}