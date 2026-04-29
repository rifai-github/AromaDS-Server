<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_number',
        'team_id',
        'assigned_by',
        'assigned_date',
        'status',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'assigned_date' => 'date',
    ];

    // Relationships
    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class, 'job_number', 'job_number');
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

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByJobSchedule($query, $jobScheduleId)
    {
        // Get job_number from JobSchedule
        $jobSchedule = JobSchedule::find($jobScheduleId);
        if ($jobSchedule) {
            return $query->where('job_number', $jobSchedule->job_number);
        }
        return $query->where('id', 0); // Return empty result if job schedule not found
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'assigned' => 'badge-info',
            'accepted' => 'badge-warning',
            'in_progress' => 'badge-primary',
            'completed' => 'badge-success',
            'cancelled' => 'badge-danger'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getFormattedAssignedDateAttribute()
    {
        return $this->assigned_date ? $this->assigned_date->format('d M Y') : '-';
    }

    // Methods
    public function canAccept()
    {
        return $this->status === 'assigned';
    }

    public function canStart()
    {
        return $this->status === 'accepted';
    }

    public function canComplete()
    {
        return $this->status === 'in_progress';
    }

    public function canCancel()
    {
        return in_array($this->status, ['assigned', 'accepted', 'in_progress']);
    }

    public function accept($userId = null)
    {
        $this->update([
            'status' => 'accepted',
            'updated_by' => $userId ?? auth()->id()
        ]);
    }

    public function start($userId = null)
    {
        $this->update([
            'status' => 'in_progress',
            'updated_by' => $userId ?? auth()->id()
        ]);
    }

    public function complete($completionNotes = null, $userId = null)
    {
        $this->update([
            'status' => 'completed',
            'notes' => $completionNotes,
            'updated_by' => $userId ?? auth()->id()
        ]);
    }

    public function cancel($reason = null, $userId = null)
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason,
            'updated_by' => $userId ?? auth()->id()
        ]);
    }
}