<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'return_number',
        'job_schedule_id',
        'job_schedule_room_id',
        'job_advice_room_id',
        'material_issue_id',
        'warehouse_id',
        'team_id',
        'status',
        'return_date',
        'return_reason',
        'return_reason_category',
        'notes',
        'approved_by',
        'approved_at',
        'approval_notes',
        'returned_by',
        'returned_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'return_date' => 'date',
        'approved_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_RETURNED = 'returned';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED = 'rejected';

    /**
     * Relationships
     */
    
    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class);
    }

    public function jobScheduleRoom()
    {
        return $this->belongsTo(JobScheduleRoom::class);
    }

    public function jobAdviceRoom()
    {
        return $this->belongsTo(JobAdviceRoom::class);
    }

    public function materialIssue()
    {
        return $this->belongsTo(MaterialIssue::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function items()
    {
        return $this->hasMany(MaterialReturnItem::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    
    public function scopeByJobSchedule($query, $jobScheduleId)
    {
        return $query->where('job_schedule_id', $jobScheduleId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeReturned($query)
    {
        return $query->where('status', self::STATUS_RETURNED);
    }

    /**
     * Methods
     */
    
    /**
     * Generate return number
     */
    public static function generateReturnNumber($jobScheduleId = null, $buildingId = null)
    {
        // Use DocumentNumberService for consistent format: [BRANCH_CODE]-ADS-RTR/[YY]-[MM]/[NNNN]
        $documentNumberService = app(\App\Services\DocumentNumberService::class);
        
        // Get branch from job schedule or building
        $branchCode = null;
        if ($jobScheduleId) {
            $jobSchedule = \App\Models\JobSchedule::find($jobScheduleId);
            if ($jobSchedule && $jobSchedule->building_id) {
                $building = \App\Models\Building::find($jobSchedule->building_id);
                if ($building && $building->branch_id) {
                    $branch = \App\Models\Branch::find($building->branch_id);
                    if ($branch) {
                        $branchCode = $branch->code;
                    }
                }
            }
        } elseif ($buildingId) {
            $building = \App\Models\Building::find($buildingId);
            if ($building && $building->branch_id) {
                $branch = \App\Models\Branch::find($building->branch_id);
                if ($branch) {
                    $branchCode = $branch->code;
                }
            }
        }
        
        return $documentNumberService->generate('material_return', $branchCode);
    }

    /**
     * Approve return
     */
    public function approve($userId = null, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId ?? auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $notes
        ]);
    }

    /**
     * Mark as returned
     */
    public function markAsReturned($userId = null)
    {
        $this->update([
            'status' => self::STATUS_RETURNED,
            'returned_by' => $userId ?? auth()->id(),
            'returned_at' => now()
        ]);
    }
}
