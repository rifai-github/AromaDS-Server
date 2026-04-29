<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasComprehensiveAuditTrail;

class BranchPic extends Model
{
    use HasFactory, HasComprehensiveAuditTrail;

    protected $fillable = [
        'branch_id',
        'user_id',
        'position',
        'department',
        'phone',
        'email',
        'is_primary',
        'is_active',
        'assigned_date',
        'end_date',
        'notes',
        'assigned_by',
        'created_by',
        'updated_by',
        'update_by_1',
        'update_at_1',
        'update_by_2',
        'update_at_2',
        'delete_by',
        'delete_at'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'assigned_date' => 'date',
        'end_date' => 'date'
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', 'like', "%{$department}%");
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    // Accessors
    public function getPositionTextAttribute()
    {
        $positions = [
            'manager' => 'Manager',
            'supervisor' => 'Supervisor',
            'coordinator' => 'Coordinator',
            'admin' => 'Administrator',
            'lead' => 'Team Lead',
            'specialist' => 'Specialist'
        ];
        
        return $positions[$this->position] ?? ucfirst($this->position);
    }

    public function getStatusTextAttribute()
    {
        if ($this->end_date && $this->end_date < now()) {
            return 'Expired';
        }
        
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getDurationAttribute()
    {
        if (!$this->assigned_date) {
            return null;
        }

        $endDate = $this->end_date ?? now();
        return $this->assigned_date->diffInDays($endDate);
    }

    public function getFormattedDurationAttribute()
    {
        $duration = $this->duration;
        if (!$duration) {
            return null;
        }

        if ($duration < 30) {
            return $duration . ' hari';
        } elseif ($duration < 365) {
            $months = floor($duration / 30);
            return $months . ' bulan';
        } else {
            $years = floor($duration / 365);
            $months = floor(($duration % 365) / 30);
            return $years . ' tahun ' . $months . ' bulan';
        }
    }

    // Business Logic Methods
    public function isCurrent()
    {
        return $this->is_active && 
               (!$this->end_date || $this->end_date >= now());
    }

    public function isExpired()
    {
        return $this->end_date && $this->end_date < now();
    }

    public function isPrimary()
    {
        return $this->is_primary;
    }

    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }

    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    public function setPrimary()
    {
        // Unset other primary PICs for this branch
        self::where('branch_id', $this->branch_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->is_primary = true;
        $this->save();
    }

    public function endAssignment($endDate = null)
    {
        $this->end_date = $endDate ?? now();
        $this->is_active = false;
        $this->save();
    }

    // Static Methods
    public static function getPositions()
    {
        return [
            'manager' => 'Manager',
            'supervisor' => 'Supervisor',
            'coordinator' => 'Coordinator',
            'admin' => 'Administrator',
            'lead' => 'Team Lead',
            'specialist' => 'Specialist'
        ];
    }

    public static function getDepartments()
    {
        return [
            'operations' => 'Operations',
            'sales' => 'Sales',
            'marketing' => 'Marketing',
            'finance' => 'Finance',
            'hr' => 'Human Resources',
            'it' => 'Information Technology',
            'logistics' => 'Logistics',
            'customer_service' => 'Customer Service'
        ];
    }

    public static function getPrimaryPic($branchId)
    {
        return self::where('branch_id', $branchId)
            ->where('is_primary', true)
            ->where('is_active', true)
            ->current()
            ->first();
    }

    public static function getActivePics($branchId)
    {
        return self::where('branch_id', $branchId)
            ->where('is_active', true)
            ->current()
            ->orderBy('is_primary', 'desc')
            ->orderBy('position')
            ->get();
    }

    public static function getPicsByPosition($branchId, $position)
    {
        return self::where('branch_id', $branchId)
            ->where('position', $position)
            ->where('is_active', true)
            ->current()
            ->get();
    }
}
