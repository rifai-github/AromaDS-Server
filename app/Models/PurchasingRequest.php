<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;
use App\Http\Traits\AutoFilterable;

class PurchasingRequest extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail, AutoFilterable;

    protected $fillable = [
        'request_number',
        'logistics_tracking_id',
        'warehouse_id',
        'reason',
        'priority',
        'status',
        'estimated_cost',
        'notes',
        'requested_by',
        'approved_by',
        'purchasing_by',
        'requested_at',
        'approved_at',
        'purchasing_at',
        'received_at',
        'completed_at',
        'approval_notes',
        'rejection_reason',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'purchasing_at' => 'datetime',
        'received_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Relationships
    public function logisticsTracking()
    {
        return $this->belongsTo(LogisticsTracking::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchasingBy()
    {
        return $this->belongsTo(User::class, 'purchasing_by');
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

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByRequester($query, $requesterId)
    {
        return $query->where('requested_by', $requesterId);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['approved', 'purchasing']);
    }

    // Helper methods
    public function getPriorityTextAttribute()
    {
        return match($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => 'Unknown'
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'purchasing' => 'Purchasing',
            'received' => 'Received',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => 'Unknown'
        };
    }

    public function getPriorityBadgeClassAttribute()
    {
        return match($this->priority) {
            'low' => 'badge-success',
            'medium' => 'badge-info',
            'high' => 'badge-warning',
            'urgent' => 'badge-danger',
            default => 'badge-light'
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'draft' => 'badge-secondary',
            'submitted' => 'badge-warning',
            'approved' => 'badge-success',
            'purchasing' => 'badge-primary',
            'received' => 'badge-info',
            'completed' => 'badge-success',
            'cancelled' => 'badge-danger',
            default => 'badge-light'
        };
    }

    public function getFormattedEstimatedCostAttribute()
    {
        return 'Rp ' . number_format($this->estimated_cost, 0, ',', '.');
    }

    public function getDaysSinceRequestedAttribute()
    {
        return $this->requested_at->diffInDays(now());
    }

    public function isPendingApproval()
    {
        return $this->status === 'submitted';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isInProgress()
    {
        return in_array($this->status, ['approved', 'purchasing']);
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function canBeApproved()
    {
        return $this->status === 'submitted';
    }

    public function canBeRejected()
    {
        return $this->status === 'submitted';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['draft', 'submitted', 'approved']);
    }

    public function approve($approverId, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_notes' => $notes,
            'updated_by' => auth()->id()
        ]);
    }

    public function reject($approverId, $reason)
    {
        $this->update([
            'status' => 'cancelled',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
            'updated_by' => auth()->id()
        ]);
    }

    public function startPurchasing($purchasingUserId)
    {
        $this->update([
            'status' => 'purchasing',
            'purchasing_by' => $purchasingUserId,
            'purchasing_at' => now(),
            'updated_by' => auth()->id()
        ]);
    }

    public function markAsReceived()
    {
        $this->update([
            'status' => 'received',
            'received_at' => now(),
            'updated_by' => auth()->id()
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'updated_by' => auth()->id()
        ]);
    }

    public function generateRequestNumber()
    {
        $prefix = 'PR';
        $date = now()->format('Ymd');
        $sequence = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $date . $sequence;
    }

    public static function createPurchasingRequest($logisticsTrackingId, $warehouseId, $reason, $priority = 'medium', $estimatedCost = 0)
    {
        $request = new self();
        $request->request_number = $request->generateRequestNumber();
        $request->logistics_tracking_id = $logisticsTrackingId;
        $request->warehouse_id = $warehouseId;
        $request->reason = $reason;
        $request->priority = $priority;
        $request->status = 'draft';
        $request->estimated_cost = $estimatedCost;
        $request->requested_by = auth()->id();
        $request->requested_at = now();
        $request->created_by = auth()->id();
        $request->updated_by = auth()->id();
        $request->save();

        return $request;
    }
}