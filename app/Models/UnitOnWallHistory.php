<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitOnWallHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_on_wall_id',
        'action',
        'customer_id',
        'customer_name',
        'location',
        'action_date',
        'technician_id',
        'technician_name',
        'job_schedule_id',
        'job_schedule_number',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'action_date' => 'date',
        'metadata' => 'array',
    ];

    // Relationships
    public function unitOnWall()
    {
        return $this->belongsTo(UnitOnWall::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper methods
    public function getActionBadgeClass()
    {
        return match($this->action) {
            'install' => 'success',
            'remove' => 'warning',
            'service' => 'info',
            'repair' => 'danger',
            default => 'secondary',
        };
    }

    public function getActionLabel()
    {
        return match($this->action) {
            'install' => 'Installed',
            'remove' => 'Removed',
            'service' => 'Serviced',
            'repair' => 'Repaired',
            default => ucfirst($this->action),
        };
    }
}
