<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class JobAssignSchedule extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'job_schedule_id',
        'team_id',
        'assigned_by',
        'assigned_date',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function jobAssignMaterialIssues()
    {
        return $this->hasMany(JobAssignMaterialIssue::class);
    }

    /**
     * STUDY CASE B2: Relationship with room assignments (for global assignment)
     */
    public function roomAssignments()
    {
        return $this->hasMany(JobScheduleRoomAssignment::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('assigned_date', [$startDate, $endDate]);
    }

    public function scopeByAssignedBy($query, $assignedById)
    {
        return $query->where('assigned_by', $assignedById);
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'assigned' => 'badge-info',
            'in_progress' => 'badge-warning',
            'completed' => 'badge-success',
            'cancelled' => 'badge-danger',
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getAssignedDateFormattedAttribute()
    {
        return $this->assigned_date ? $this->assigned_date->format('d M Y') : '-';
    }

    public function getTeamNameAttribute()
    {
        return $this->team ? $this->team->team_name : '-';
    }

    public function getJobNumberAttribute()
    {
        return $this->jobSchedule && $this->jobSchedule->jobAdvice ? $this->jobSchedule->jobAdvice->job_advice_number : '-';
    }

    public function getCustomerNameAttribute()
    {
        return $this->jobSchedule && $this->jobSchedule->jobAdvice && $this->jobSchedule->jobAdvice->customer ? $this->jobSchedule->jobAdvice->customer->name : '-';
    }

    public function getBuildingNameAttribute()
    {
        return $this->jobSchedule && $this->jobSchedule->building ? $this->jobSchedule->building->building_name : '-';
    }

    // Methods
    public function canStart()
    {
        return $this->status === 'assigned';
    }

    public function canComplete()
    {
        return $this->status === 'in_progress';
    }

    public function canCancel()
    {
        return in_array($this->status, ['assigned', 'in_progress']);
    }

    public function start()
    {
        $this->update(['status' => 'in_progress']);
    }

    public function complete()
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function getRelatedJobAdvice()
    {
        return $this->jobSchedule ? $this->jobSchedule->jobAdvice : null;
    }

    public function getRelatedCustomer()
    {
        return $this->jobSchedule ? $this->jobSchedule->jobAdvice->customer : null;
    }

    public function getRelatedBuilding()
    {
        return $this->jobSchedule ? $this->jobSchedule->building : null;
    }
}
