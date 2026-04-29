<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class Floor extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'building_id',
        'floor_number',
        'floor_name',
        'description',
        'area',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBuilding($query, $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    public function scopeByFloorNumber($query, $floorNumber)
    {
        return $query->where('floor_number', $floorNumber);
    }

    // Accessors
    public function getFormattedAreaAttribute()
    {
        return $this->area ? number_format($this->area, 2) . ' m²' : 'N/A';
    }

    public function getFullNameAttribute()
    {
        return $this->building ? $this->building->name . ' - Floor ' . $this->floor_number : 'Floor ' . $this->floor_number;
    }

    public function getUnitCountAttribute()
    {
        return $this->units()->count();
    }
}
