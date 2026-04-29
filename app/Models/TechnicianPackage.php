<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TechnicianPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_schedule_id',
        'technician_id',
        'package_name',
        'package_description',
        'total_items',
        'completed_items',
        'completion_percentage',
        'status',
        'completed_at',
        'completed_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'completion_percentage' => 'decimal:2',
        'completed_at' => 'datetime'
    ];

    // Relationships
    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function items()
    {
        return $this->hasMany(TechnicianPackageItem::class, 'package_id');
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
    public function scopeByTechnician($query, $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeByJobSchedule($query, $jobScheduleId)
    {
        return $query->where('job_schedule_id', $jobScheduleId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReadyToComplete($query)
    {
        return $query->where('status', 'ready_to_complete');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'ready_to_complete' => 'Ready to Complete',
            'completed' => 'Completed'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'badge-secondary',
            'in_progress' => 'badge-warning',
            'ready_to_complete' => 'badge-info',
            'completed' => 'badge-success'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getCompletionPercentageFormattedAttribute()
    {
        return $this->completion_percentage . '%';
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getCanCompleteAttribute()
    {
        $requiredItems = $this->items()->where('is_required', true)->count();
        $completedRequiredItems = $this->items()->where('is_required', true)->where('is_completed', true)->count();
        
        return $requiredItems > 0 && $completedRequiredItems === $requiredItems;
    }

    // Methods
    public function updateCompletionStatus()
    {
        $totalItems = $this->items()->count();
        $completedItems = $this->items()->where('is_completed', true)->count();
        
        $this->update([
            'completed_items' => $completedItems,
            'completion_percentage' => $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 2) : 0
        ]);

        // Update status based on completion
        if ($completedItems === $totalItems) {
            $this->update(['status' => 'ready_to_complete']);
        } elseif ($completedItems > 0) {
            $this->update(['status' => 'in_progress']);
        } else {
            $this->update(['status' => 'pending']);
        }

        return $this;
    }

    public function complete($userId = null)
    {
        if (!$this->can_complete) {
            throw new \Exception("Cannot complete package: not all required items are completed");
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $userId ?? auth()->id()
        ]);

        return $this;
    }

    public function getProgressData()
    {
        $items = $this->items()->orderBy('item_order')->get();
        
        return [
            'total_items' => $items->count(),
            'completed_items' => $items->where('is_completed', true)->count(),
            'required_items' => $items->where('is_required', true)->count(),
            'completed_required_items' => $items->where('is_required', true)->where('is_completed', true)->count(),
            'completion_percentage' => $this->completion_percentage,
            'can_complete' => $this->can_complete,
            'items' => $items
        ];
    }
}