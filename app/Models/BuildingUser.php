<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class BuildingUser extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'building_id',
        'user_id',
        'role',
        'is_primary',
        'is_active',
        'assigned_at',
        'unassigned_at',
        'notes',
        'assigned_by',
        'unassigned_by'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime'
    ];

    // Relationships
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function unassignedBy()
    {
        return $this->belongsTo(User::class, 'unassigned_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBuilding($query, $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeNonPrimary($query)
    {
        return $query->where('is_primary', false);
    }

    // Accessors
    public function getRoleTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->role));
    }

    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getIsPrimaryTextAttribute()
    {
        return $this->is_primary ? 'Yes' : 'No';
    }

    // Helper methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function isPrimary()
    {
        return $this->is_primary;
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update([
            'is_active' => false,
            'unassigned_at' => now(),
            'unassigned_by' => auth()->id()
        ]);
    }

    public function setAsPrimary()
    {
        // First, remove primary status from other users of this building
        static::where('building_id', $this->building_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this user as primary
        $this->update(['is_primary' => true]);
    }

    public function removeAsPrimary()
    {
        $this->update(['is_primary' => false]);
    }

    // Static methods
    public static function assignUserToBuilding($buildingId, $userId, $role = 'user', $isPrimary = false, $notes = null)
    {
        // Check if user is already assigned to this building
        $existing = static::where('building_id', $buildingId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            // Reactivate if inactive
            if (!$existing->is_active) {
                $existing->update([
                    'is_active' => true,
                    'role' => $role,
                    'is_primary' => $isPrimary,
                    'notes' => $notes,
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id(),
                    'unassigned_at' => null,
                    'unassigned_by' => null
                ]);
                return $existing;
            }
            return $existing;
        }

        // If setting as primary, remove primary status from other users
        if ($isPrimary) {
            static::where('building_id', $buildingId)
                ->update(['is_primary' => false]);
        }

        return static::create([
            'building_id' => $buildingId,
            'user_id' => $userId,
            'role' => $role,
            'is_primary' => $isPrimary,
            'is_active' => true,
            'assigned_at' => now(),
            'notes' => $notes,
            'assigned_by' => auth()->id()
        ]);
    }

    public static function getBuildingUsers($buildingId, $activeOnly = true)
    {
        $query = static::where('building_id', $buildingId)
            ->with(['user', 'assignedBy']);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('is_primary', 'desc')
            ->orderBy('assigned_at', 'asc')
            ->get();
    }

    public static function getUserBuildings($userId, $activeOnly = true)
    {
        $query = static::where('user_id', $userId)
            ->with(['building', 'assignedBy']);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('is_primary', 'desc')
            ->orderBy('assigned_at', 'asc')
            ->get();
    }

    public static function getPrimaryUser($buildingId)
    {
        return static::where('building_id', $buildingId)
            ->where('is_primary', true)
            ->where('is_active', true)
            ->with('user')
            ->first();
    }

    public static function getBuildingUserCount($buildingId, $activeOnly = true)
    {
        $query = static::where('building_id', $buildingId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->count();
    }

    public static function getUsersByRole($buildingId, $role, $activeOnly = true)
    {
        $query = static::where('building_id', $buildingId)
            ->where('role', $role)
            ->with('user');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }
}