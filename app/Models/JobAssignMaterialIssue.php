<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class JobAssignMaterialIssue extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'job_assign_schedule_id',
        'material_issue_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function jobAssignSchedule()
    {
        return $this->belongsTo(JobAssignSchedule::class);
    }

    public function materialIssue()
    {
        return $this->belongsTo(MaterialIssue::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Access to related data through relationships
    public function getJobSchedule()
    {
        return $this->jobAssignSchedule ? $this->jobAssignSchedule->jobSchedule : null;
    }

    public function getTeam()
    {
        return $this->materialIssue ? $this->materialIssue->team : null;
    }

    public function getProduct()
    {
        return $this->materialIssue ? $this->materialIssue->product : null;
    }

    public function getCustomer()
    {
        $jobSchedule = $this->getJobSchedule();
        return $jobSchedule ? $jobSchedule->customer : null;
    }

    public function getBuilding()
    {
        $jobSchedule = $this->getJobSchedule();
        return $jobSchedule ? $jobSchedule->building : null;
    }

    // Scopes
    public function scopeByJobAssignSchedule($query, $jobAssignScheduleId)
    {
        return $query->where('job_assign_schedule_id', $jobAssignScheduleId);
    }

    public function scopeByMaterialIssue($query, $materialIssueId)
    {
        return $query->where('material_issue_id', $materialIssueId);
    }

    public function scopeByCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Accessors
    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at ? $this->created_at->format('d M Y H:i') : 'N/A';
    }

    public function getUpdatedAtFormattedAttribute()
    {
        return $this->updated_at ? $this->updated_at->format('d M Y H:i') : 'N/A';
    }

    // Methods
    public function getRelatedJobAssignSchedule()
    {
        return $this->jobAssignSchedule;
    }

    public function getRelatedMaterialIssue()
    {
        return $this->materialIssue;
    }

    public function getCreatedByUser()
    {
        return $this->createdBy;
    }

    public function getUpdatedByUser()
    {
        return $this->updatedBy;
    }
}