<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class StockOpname extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'opname_no',
        'opname_number',
        'branch_id',
        'warehouse_id',
        'person_responsible',
        'opname_date',
        'status',
        'notes',
        'started_at',
        'completed_at',
        'completion_date',
        'completion_notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'opname_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'completion_date' => 'datetime'
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

    public function personResponsible()
    {
        return $this->belongsTo(User::class, 'person_responsible');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function stockOpnameDetails()
    {
        return $this->hasMany(StockOpnameDetail::class);
    }

    // Scopes
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByPersonResponsible($query, $personId)
    {
        return $query->where('person_responsible', $personId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('opname_date', [$startDate, $endDate]);
    }

    public function scopeByCreator($query, $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in-progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'draft' => 'Draft',
            'in-progress' => 'In Progress',
            'completed' => 'Completed',
            'waiting for approval' => 'Waiting for Approval',
            'approved' => 'Approved'
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedOpnameDateAttribute()
    {
        return $this->opname_date ? $this->opname_date->format('d/m/Y') : '-';
    }

    public function getFormattedStartedAtAttribute()
    {
        return $this->started_at ? $this->started_at->format('d/m/Y H:i') : '-';
    }

    public function getFormattedCompletedAtAttribute()
    {
        return $this->completed_at ? $this->completed_at->format('d/m/Y H:i') : '-';
    }

    public function getFormattedCompletionDateAttribute()
    {
        return $this->completion_date ? $this->completion_date->format('d/m/Y H:i') : '-';
    }

    public function getIsDraftAttribute()
    {
        return $this->status === 'draft';
    }

    public function getIsInProgressAttribute()
    {
        return $this->status === 'in-progress';
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === 'approved';
    }

    public function getDurationAttribute()
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInHours($this->completed_at);
        }
        return null;
    }

    public function getFormattedDurationAttribute()
    {
        if ($this->duration !== null) {
            return $this->duration . ' hours';
        }
        return '-';
    }

    // Business Logic Methods
    public function start()
    {
        $this->update([
            'status' => 'in-progress',
            'started_at' => now(),
            'updated_by' => auth()->id()
        ]);
    }

    public function complete($completionNotes = null)
    {
        // Complete → Waiting for Approval (bukan langsung completed)
        // Pusat akan approve dulu sebelum stock adjustment
        $this->update([
            'status' => 'waiting for approval',
            'completed_at' => now(),
            'completion_date' => now(),
            'completion_notes' => $completionNotes,
            'updated_by' => auth()->id()
        ]);
    }

    public function approve()
    {
        $this->update([
            'status' => 'approved',
            'updated_by' => auth()->id()
        ]);
    }

    public function submitForApproval()
    {
        $this->update([
            'status' => 'waiting for approval',
            'updated_by' => auth()->id()
        ]);
    }

    public function getIsWaitingForApprovalAttribute()
    {
        return $this->status === 'waiting for approval';
    }

    public function getTotalItems()
    {
        return $this->stockOpnameDetails()->count();
    }

    public function getVarianceItems()
    {
        return $this->stockOpnameDetails()->where('qty_variance', '!=', 0)->count();
    }

    // Auto-generate opname number
    public static function generateOpnameNumber($branchId)
    {
        $documentService = app(\App\Services\DocumentNumberService::class);
        $branch = Branch::find($branchId);
        $branchCode = $branch ? $branch->code : null;

        return $documentService->generate(
            'stock_opname',
            $branchCode,
            null, // building
            null, // contract
            null, // quotation
            null, // survey
            null  // warehouse (optional, since we already pass branch code)
        );
    }
}