<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class UnitRepair extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'unit_id',
        'room_id',
        'building_id',
        'contract_id',
        'repair_number',
        'problem_description',
        'diagnosis',
        'repair_work_performed',
        'parts_replaced',
        'photos',
        'attachments',
        'repair_cost',
        'repair_duration_minutes',
        'repair_status',
        'warranty_status',
        'warranty_expiry',
        'warranty_notes',
        'reported_at',
        'diagnosed_at',
        'started_at',
        'completed_at',
        'reported_by',
        'diagnosed_by',
        'repaired_by',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'photos' => 'array',
        'attachments' => 'array',
        'repair_cost' => 'decimal:2',
        'warranty_expiry' => 'date',
        'reported_at' => 'datetime',
        'diagnosed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Constants for repair status
    const STATUS_REPORTED = 'reported';
    const STATUS_DIAGNOSED = 'diagnosed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Constants for warranty status
    const WARRANTY_UNDER = 'under_warranty';
    const WARRANTY_OUT = 'out_of_warranty';
    const WARRANTY_EXTENDED = 'extended_warranty';

    // Relationships
    public function unit()
    {
        return $this->belongsTo(SerialNumber::class, 'unit_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function diagnosedBy()
    {
        return $this->belongsTo(User::class, 'diagnosed_by');
    }

    public function repairedBy()
    {
        return $this->belongsTo(User::class, 'repaired_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('repair_status', $status);
    }

    public function scopeByWarrantyStatus($query, $status)
    {
        return $query->where('warranty_status', $status);
    }

    public function scopeByRepairedBy($query, $userId)
    {
        return $query->where('repaired_by', $userId);
    }

    public function scopeReported($query)
    {
        return $query->where('repair_status', self::STATUS_REPORTED);
    }

    public function scopeDiagnosed($query)
    {
        return $query->where('repair_status', self::STATUS_DIAGNOSED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('repair_status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('repair_status', self::STATUS_COMPLETED);
    }

    public function scopeUnderWarranty($query)
    {
        return $query->where('warranty_status', self::WARRANTY_UNDER);
    }

    public function scopeOutOfWarranty($query)
    {
        return $query->where('warranty_status', self::WARRANTY_OUT);
    }

    // Accessors
    public function getDurationFormattedAttribute()
    {
        if (!$this->repair_duration_minutes) return null;
        
        $hours = floor($this->repair_duration_minutes / 60);
        $minutes = $this->repair_duration_minutes % 60;
        
        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }
        
        return "{$minutes}m";
    }

    public function getIsCompletedAttribute()
    {
        return $this->repair_status === self::STATUS_COMPLETED;
    }

    public function getIsInProgressAttribute()
    {
        return $this->repair_status === self::STATUS_IN_PROGRESS;
    }

    public function getIsUnderWarrantyAttribute()
    {
        return $this->warranty_status === self::WARRANTY_UNDER;
    }

    // Methods
    public function markAsDiagnosed($diagnosedBy, $diagnosis)
    {
        $this->update([
            'repair_status' => self::STATUS_DIAGNOSED,
            'diagnosed_by' => $diagnosedBy,
            'diagnosis' => $diagnosis,
            'diagnosed_at' => now()
        ]);
    }

    public function markAsStarted($repairedBy)
    {
        $this->update([
            'repair_status' => self::STATUS_IN_PROGRESS,
            'repaired_by' => $repairedBy,
            'started_at' => now()
        ]);
    }

    public function markAsCompleted($workPerformed = null, $partsReplaced = null)
    {
        $this->update([
            'repair_status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'repair_work_performed' => $workPerformed,
            'parts_replaced' => $partsReplaced
        ]);
    }

    public function markAsFailed()
    {
        $this->update([
            'repair_status' => self::STATUS_FAILED,
            'completed_at' => now()
        ]);
    }

    public function cancel()
    {
        $this->update([
            'repair_status' => self::STATUS_CANCELLED
        ]);
    }
}
