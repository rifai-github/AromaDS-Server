<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class Team extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'branch_office',
        'team_code',
        'team_name',
        'description',
        'active_status',
        'team_head_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'active_status' => 'boolean',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_office', 'id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
                    ->withPivot('role', 'is_active', 'joined_date', 'left_date', 'notes')
                    ->withTimestamps();
    }

    public function teamHead()
    {
        return $this->belongsTo(User::class, 'team_head_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function jobAssignments()
    {
        return $this->hasMany(JobAssignment::class);
    }

    public function jobMaterialTransfersFrom()
    {
        return $this->hasMany(JobMaterialTransfer::class, 'from_team_id');
    }

    public function jobMaterialTransfersTo()
    {
        return $this->hasMany(JobMaterialTransfer::class, 'to_team_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active_status', true);
    }

    public function scopeByBranch($query, $branchCode)
    {
        return $query->where('branch_office', $branchCode);
    }

    // Auto-generate team code
    public static function generateTeamCode($branchId = null)
    {
        $prefix = 'TM';
        $year = date('Y');
        $month = date('m');
        $datePrefix = "{$prefix}{$year}{$month}";
        
        // Loop to ensure uniqueness (simple collision avoidance)
        $attempts = 0;
        $maxAttempts = 5;
        
        do {
            // Get the last team code for this month (including soft-deleted)
            $lastTeam = self::withTrashed()
                ->where('team_code', 'like', "{$datePrefix}%")
                ->orderBy('team_code', 'desc')
                ->lockForUpdate() // Lock to prevent race conditions if in transaction
                ->first();
            
            if ($lastTeam) {
                // Extract number from code like TM202403001
                $lastNumber = (int) substr($lastTeam->team_code, strlen($datePrefix));
                $newNumber = $lastNumber + 1 + $attempts;
            } else {
                $newNumber = 1 + $attempts;
            }
            
            $code = $datePrefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            
            // Check if this specific code exists (extra safety)
            $exists = self::withTrashed()->where('team_code', $code)->exists();
            $attempts++;
        } while ($exists && $attempts < $maxAttempts);
        
        return $code;
    }

    // Accessor for members count
    public function getMembersCountAttribute()
    {
        return $this->users()->count();
    }
}
