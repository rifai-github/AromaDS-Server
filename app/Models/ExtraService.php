<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class ExtraService extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        // Reference
        'service_number',
        'contract_id',
        'contract_room_id',
        'building_id',
        'room_id',
        
        // Service Details
        'service_type',
        'service_description',
        'service_reason',
        
        // Scheduling
        'requested_date',
        'scheduled_date',
        'completed_date',
        
        // Invoicing
        'with_invoice',
        'service_fee',
        'invoice_notes',
        
        // Materials
        'with_materials',
        'materials_notes',
        
        // Status & Approval
        'status',
        'approval_notes',
        
        // Job Advice
        'job_advice_id',
        
        // User Tracking
        'requested_by',
        'approved_by',
        'completed_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'requested_date' => 'date',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'with_invoice' => 'boolean',
        'with_materials' => 'boolean',
        'service_fee' => 'decimal:2'
    ];

    // Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_CLEANING = 'cleaning';
    const TYPE_REFILL = 'refill';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_REPAIR = 'repair';
    const TYPE_OTHER = 'other';

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function contractRoom()
    {
        return $this->belongsTo(ContractRoom::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function jobAdvice()
    {
        return $this->belongsTo(JobAdvice::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
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

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeWithInvoice($query)
    {
        return $query->where('with_invoice', true);
    }

    public function scopeWithMaterials($query)
    {
        return $query->where('with_materials', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_DRAFT => 'badge-secondary',
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_SCHEDULED => 'badge-info',
            self::STATUS_IN_PROGRESS => 'badge-primary',
            self::STATUS_COMPLETED => 'badge-success',
            self::STATUS_CANCELLED => 'badge-dark'
        ];
        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getServiceTypeTextAttribute()
    {
        $types = [
            self::TYPE_CLEANING => 'Cleaning',
            self::TYPE_REFILL => 'Refill',
            self::TYPE_MAINTENANCE => 'Maintenance',
            self::TYPE_REPAIR => 'Repair',
            self::TYPE_OTHER => 'Other'
        ];
        return $types[$this->service_type] ?? ucfirst($this->service_type);
    }

    // Boolean Accessors
    public function getIsPendingAttribute()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getHasInvoiceAttribute()
    {
        return $this->with_invoice;
    }

    public function getHasMaterialsAttribute()
    {
        return $this->with_materials;
    }

    // Methods
    public function submitForApproval()
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'requested_date' => now()
        ]);
    }

    public function approve($approvedBy, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approval_notes' => $notes
        ]);
        
        \Log::info("Extra Service approved: {$this->service_number}");
    }

    public function reject($approvedBy, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approvedBy,
            'approval_notes' => $notes
        ]);
        
        \Log::info("Extra Service rejected: {$this->service_number}");
    }

    public function schedule($scheduledDate)
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED,
            'scheduled_date' => $scheduledDate
        ]);
    }

    public function markAsInProgress()
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS
        ]);
    }

    public function complete($completedBy)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_by' => $completedBy,
            'completed_date' => now()
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
    }

    /**
     * Generate unique service number (MOM6 Format: ES-YYYYMMDD-XXXX)
     */
    public static function generateServiceNumber()
    {
        $prefix = 'ES-' . date('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}

