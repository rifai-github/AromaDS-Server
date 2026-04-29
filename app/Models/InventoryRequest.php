<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Http\Traits\AutoFilterable;
use App\Traits\HasComprehensiveAuditTrail;

class InventoryRequest extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable, HasComprehensiveAuditTrail;

    protected $fillable = [
        'request_number',
        'warehouse_id',
        'branch_id',
        'requested_by',
        'request_date',
        'required_date',
        'priority',
        'reason',
        'status',
        'shipping_tracking_number',
        'shipping_date',
        'shipped_at',
        'notes',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'completed_at',
        'processed_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'request_date' => 'date',
        'required_date' => 'date',
        'shipping_date' => 'date',
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
        'processed_date' => 'datetime',
    ];

    /**
     * Get the warehouse that owns the inventory request.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the branch that owns the inventory request.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get branch name with fallback to warehouse branch.
     */
    public function getBranchNameAttribute()
    {
        if ($this->branch) {
            return $this->branch->name;
        }
        if ($this->warehouse && $this->warehouse->branch) {
            return $this->warehouse->branch->name;
        }
        return 'N/A';
    }

    /**
     * Get the user who requested the inventory.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved the inventory request.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }


    /**
     * Get the user who created the inventory request.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the inventory request.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the inventory request items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryRequestItem::class);
    }

    /**
     * Get the inventory issuing related to this request.
     */
    public function inventoryIssuing()
    {
        return $this->hasOne(InventoryIssuing::class, 'inventory_request_id');
    }

    /**
     * Get the inventory receivings related to this request through issuing.
     */
    public function inventoryReceivings()
    {
        return $this->hasManyThrough(InventoryReceiving::class, InventoryIssuing::class, 'inventory_request_id', 'issuing_id');
    }

    public function logisticsTracking()
    {
        return $this->hasOne(LogisticsTracking::class);
    }

    /**
     * Scope a query to only include pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to only include completed requests.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Get the total quantity of items in this request.
     */
    public function getTotalQuantityAttribute(): int
    {
        return 0; // Disabled to prevent N+1 queries
    }

    /**
     * Check if the request can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request can be rejected.
     */
    public function canBeRejected(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request can be completed.
     */
    public function canBeCompleted(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Get priority badge class.
     */
    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'badge-danger',
            'high' => 'badge-warning',
            'medium' => 'badge-info',
            'low' => 'badge-secondary',
            default => 'badge-secondary',
        };
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'badge-warning',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            'completed' => 'badge-info',
            default => 'badge-secondary',
        };
    }
}
