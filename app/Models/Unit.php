<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class Unit extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'floor_id',
        'unit_number',
        'unit_name',
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
    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function building()
    {
        return $this->hasOneThrough(Building::class, Floor::class, 'id', 'id', 'floor_id', 'building_id');
    }

    public function rooms()
    {
        return $this->hasMany(MasterRoom::class, 'unit_id');
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

    public function scopeByFloor($query, $floorId)
    {
        return $query->where('floor_id', $floorId);
    }

    public function scopeByUnitNumber($query, $unitNumber)
    {
        return $query->where('unit_number', $unitNumber);
    }

    // Accessors
    public function getFormattedAreaAttribute()
    {
        return $this->area ? number_format($this->area, 2) . ' m²' : 'N/A';
    }

    public function getFullNameAttribute()
    {
        return $this->floor ? $this->floor->full_name . ' - Unit ' . $this->unit_number : 'Unit ' . $this->unit_number;
    }

    public function getRoomCountAttribute()
    {
        return $this->rooms()->count();
    }
}
