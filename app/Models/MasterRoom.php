<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterRoom extends Model
{
    use HasFactory, SoftDeletes, \App\Http\Traits\AutoFilterable;

    protected $fillable = [
        'building_id',
        'room_name',
        'room_type',
        'room_floor',
        'room_qty',
        'room_temperature',
        'room_intensity',
        'room_installation_type',
        'room_length',
        'room_width',
        'room_height',
        'room_remark',
        'is_active',
        'customer_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'area' => 'decimal:2',
        'ahu_quantity' => 'integer',
        'capacity' => 'integer',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function roomRentalUnits()
    {
        return $this->hasMany(RoomRentalUnit::class);
    }

    public function contractRooms()
    {
        return $this->hasMany(ContractRoom::class, 'room_id');
    }

    public function unitOnWalls()
    {
        return $this->hasMany(UnitOnWall::class, 'room_id');
    }

    /* 
    public function jobAssignSchedules()
    {
        return $this->hasMany(JobAssignSchedule::class);
    }
    */

    // Scopes
    public function scopeByBuilding($query, $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('is_active', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('room_type', $type);
    }

    public function scopeByFloor($query, $floor)
    {
        return $query->where('floor', $floor);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('room_name', 'like', "%{$name}%");
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->room_type));
    }

    public function getCalculatedAreaAttribute()
    {
        if ($this->length && $this->width) {
            return $this->length * $this->width;
        }
        return $this->area;
    }

    public function getVolumeAttribute()
    {
        if ($this->length && $this->width && $this->height) {
            return $this->length * $this->width * $this->height;
        }
        return null;
    }

    public function getFormattedAreaAttribute()
    {
        return number_format($this->calculated_area, 2) . ' m²';
    }

    public function getFormattedVolumeAttribute()
    {
        return $this->volume ? number_format($this->volume, 2) . ' m³' : 'N/A';
    }

    public function getFullNameAttribute()
    {
        return ($this->building ? $this->building->name : 'Unknown') . ' - ' . $this->room_name;
    }

    // Auto-generate room code if not provided
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($room) {
            if (empty($room->room_code)) {
                // Generate room code based on count (ignoring soft deletes)
                $count = static::count() + 1;
                $room->room_code = 'RM' . str_pad($count, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
