<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class Room extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $table = 'master_rooms';

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
        'area' => 'decimal:2',
        'rental_rate' => 'decimal:2',
        'capacity' => 'integer'
    ];

    // Relationships
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function contractRooms()
    {
        return $this->hasMany(ContractRoom::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('room_type', $type);
    }

    public function scopeByFloor($query, $floor)
    {
        return $query->where('floor', $floor);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Accessors
    public function getFormattedAreaAttribute()
    {
        return number_format($this->area, 2) . ' m²';
    }

    public function getFormattedRentalRateAttribute()
    {
        return 'Rp ' . number_format($this->rental_rate, 0, ',', '.');
    }

    public function getIsAvailableAttribute()
    {
        return $this->status === 'available';
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
