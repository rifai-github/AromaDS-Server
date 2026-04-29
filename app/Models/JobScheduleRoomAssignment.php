<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobScheduleRoomAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_schedule_id',
        'job_schedule_room_id',
        'job_advice_room_id',
        'team_id',
        'job_assign_schedule_id',
        'is_custom',
        'assigned_by',
        'assigned_date',
        'notes',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_custom' => 'boolean',
        'assigned_date' => 'date',
    ];

    // Status constants
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

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

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function jobAssignSchedule()
    {
        return $this->belongsTo(JobAssignSchedule::class);
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

    /**
     * Scopes
     */
    
    public function scopeByJobSchedule($query, $jobScheduleId)
    {
        return $query->where('job_schedule_id', $jobScheduleId);
    }

    public function scopeByRoom($query, $jobScheduleRoomId)
    {
        return $query->where('job_schedule_room_id', $jobScheduleRoomId);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    public function scopeGlobal($query)
    {
        return $query->where('is_custom', false);
    }

    public function scopeByTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Methods
     */
    
    /**
     * Check if this is a custom assignment
     */
    public function isCustom()
    {
        return $this->is_custom === true;
    }

    /**
     * Check if this is a global assignment
     */
    public function isGlobal()
    {
        return $this->is_custom === false;
    }

    /**
     * Get effective team (from custom assignment or global assignment)
     */
    public function getEffectiveTeam()
    {
        if ($this->is_custom && $this->team_id) {
            return $this->team;
        } elseif ($this->jobAssignSchedule && $this->jobAssignSchedule->team_id) {
            return $this->jobAssignSchedule->team;
        }
        return null;
    }
}
