<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class InventoryIssuing extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'issuing_number',
        'inventory_request_id',
        'branch_id',
        'warehouse_id',
        'issue_date',
        'reference_no',
        'requested_by',
        'issued_by',
        'received_by',
        'team_id',
        'status',
        'remarks',
        'issued_at',
        'received_at',
        'cancelled_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'issued_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function inventoryRequest()
    {
        return $this->belongsTo(InventoryRequest::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(InventoryIssuingItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
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

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    public function scopeByReference($query, $referenceNo)
    {
        return $query->where('reference_no', 'like', "%{$referenceNo}%");
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'badge-warning',
            'processed' => 'badge-info',
            'sent' => 'badge-primary',
            'received' => 'badge-success',
            'cancelled' => 'badge-danger',
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getStatusTextAttribute()
    {
        $texts = [
            'pending' => 'Material in Prep',
            'processed' => 'Material Ready',
            'sent' => 'Material Issued', // Sesuai permintaan
            'received' => 'Received',
            'cancelled' => 'Cancelled',
        ];

        return $texts[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedIssueDateAttribute()
    {
        return $this->issue_date->format('d M Y');
    }

    public function getFormattedIssuedAtAttribute()
    {
        return $this->issued_at ? $this->issued_at->format('d M Y H:i') : '-';
    }

    public function getFormattedReceivedAtAttribute()
    {
        return $this->received_at ? $this->received_at->format('d M Y H:i') : '-';
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d M Y H:i');
    }

    public function getTotalQuantityAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function getTotalItemsAttribute()
    {
        return $this->items->count();
    }

    // Methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessed()
    {
        return $this->status === 'processed';
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }

    public function isReceived()
    {
        return $this->status === 'received';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function canBeProcessed()
    {
        return $this->status === 'pending';
    }

    public function canBeSent()
    {
        return $this->status === 'processed';
    }

    public function canBeReceived()
    {
        return $this->status === 'sent';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'processed', 'sent']);
    }

    // MOM11: Removed getIssuingNumberAttribute accessor
    // Now using actual issuing_number from database (format: [BRANCH]-IIS/YY-MM/NNNN)
    // Old format ISU-000012 was overriding the correct format from DocumentNumberService

    public function getDurationAttribute()
    {
        if ($this->issued_at && $this->received_at) {
            return $this->issued_at->diffInDays($this->received_at);
        }
        return null;
    }

    public function getFormattedDurationAttribute()
    {
        $duration = $this->duration;
        if ($duration !== null) {
            return $duration . ' day(s)';
        }
        return '-';
    }
}
