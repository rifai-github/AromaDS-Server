<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class JobMaterialTransfer extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'job_material_id',
        'from_team_id',
        'to_team_id',
        'transfer_quantity',
        'status',
        'transfer_reason',
        'approval_notes',
        'requested_by',
        'approved_by',
        'requested_at',
        'approved_at',
        'completed_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Relationships
    public function jobMaterial()
    {
        return $this->belongsTo(JobMaterial::class);
    }

    public function fromTeam()
    {
        return $this->belongsTo(Team::class, 'from_team_id');
    }

    public function toTeam()
    {
        return $this->belongsTo(Team::class, 'to_team_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeByFromTeam($query, $teamId)
    {
        return $query->where('from_team_id', $teamId);
    }

    public function scopeByToTeam($query, $teamId)
    {
        return $query->where('to_team_id', $teamId);
    }

    public function scopeByJobMaterial($query, $jobMaterialId)
    {
        return $query->where('job_material_id', $jobMaterialId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'badge-warning',
            'approved' => 'badge-info',
            'rejected' => 'badge-danger',
            'completed' => 'badge-success'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getFormattedRequestedAtAttribute()
    {
        return $this->requested_at ? $this->requested_at->format('d M Y H:i') : '-';
    }

    public function getFormattedApprovedAtAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('d M Y H:i') : '-';
    }

    public function getFormattedCompletedAtAttribute()
    {
        return $this->completed_at ? $this->completed_at->format('d M Y H:i') : '-';
    }

    // Methods
    public function canApprove()
    {
        return $this->status === 'pending';
    }

    public function canReject()
    {
        return $this->status === 'pending';
    }

    public function canComplete()
    {
        return $this->status === 'approved';
    }

    public function approve($approvalNotes = null, $userId = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approval_notes' => $approvalNotes,
            'approved_by' => $userId ?? auth()->id(),
            'updated_by' => $userId ?? auth()->id()
        ]);
    }

    public function reject($approvalNotes = null, $userId = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_at' => now(),
            'approval_notes' => $approvalNotes,
            'approved_by' => $userId ?? auth()->id(),
            'updated_by' => $userId ?? auth()->id()
        ]);
    }

    public function complete($userId = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'updated_by' => $userId ?? auth()->id()
        ]);
    }
}