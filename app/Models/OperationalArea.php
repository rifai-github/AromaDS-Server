<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasComprehensiveAuditTrail;

class OperationalArea extends Model
{
    use HasFactory, HasComprehensiveAuditTrail;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'area_type',
        'province_id',
        'city_id',
        'district_id',
        'subdistrict_id',
        'city',
        'province',
        'district',
        'subdistrict',
        'postal_code',
        'latitude',
        'longitude',
        'radius_km',
        'is_active',
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
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    
    public function province()
    {
        return $this->belongsTo(\App\Models\Province::class);
    }
    
    // Alias for province relation (to avoid conflict with 'province' string column)
    public function provinceRelation()
    {
        return $this->belongsTo(\App\Models\Province::class, 'province_id');
    }
    
    public function city()
    {
        return $this->belongsTo(\App\Models\City::class);
    }
    
    // Alias for city relation (to avoid conflict with 'city' string column)
    public function cityRelation()
    {
        return $this->belongsTo(\App\Models\City::class, 'city_id');
    }
    
    public function district()
    {
        return $this->belongsTo(\App\Models\District::class);
    }
    
    public function subdistrict()
    {
        return $this->belongsTo(\App\Models\Subdistrict::class);
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

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByAreaType($query, $areaType)
    {
        return $query->where('area_type', $areaType);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    public function scopeByProvince($query, $province)
    {
        return $query->where('province', 'like', "%{$province}%");
    }

    public function scopeWithinRadius($query, $latitude, $longitude, $radiusKm = 50)
    {
        return $query->whereRaw("
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
            sin(radians(latitude)))) <= ?
        ", [$latitude, $longitude, $latitude, $radiusKm]);
    }

    // Accessors
    public function getAreaTypeTextAttribute()
    {
        $types = [
            'city' => 'Kota',
            'district' => 'Kecamatan',
            'province' => 'Provinsi',
            'region' => 'Wilayah'
        ];
        
        return $types[$this->area_type] ?? ucfirst($this->area_type);
    }

    public function getFullLocationAttribute()
    {
        $location = [];
        if ($this->city) {
            $location[] = $this->city;
        }
        if ($this->province) {
            $location[] = $this->province;
        }
        return implode(', ', $location);
    }

    public function getFormattedRadiusAttribute()
    {
        return $this->radius_km . ' km';
    }

    // Business Logic Methods
    public function isWithinServiceArea($latitude, $longitude)
    {
        if (!$this->latitude || !$this->longitude) {
            return false;
        }

        $distance = $this->calculateDistance($latitude, $longitude);
        return $distance <= $this->radius_km;
    }

    public function calculateDistance($latitude, $longitude)
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        $latDiff = deg2rad($latitude - $this->latitude);
        $lonDiff = deg2rad($longitude - $this->longitude);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    // Static Methods
    public static function getAreaTypes()
    {
        return [
            'city' => 'Kota',
            'district' => 'Kecamatan',
            'province' => 'Provinsi',
            'region' => 'Wilayah'
        ];
    }

    public static function findNearestArea($latitude, $longitude, $branchId = null)
    {
        $query = self::active();
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get()
            ->map(function ($area) use ($latitude, $longitude) {
                $area->distance = $area->calculateDistance($latitude, $longitude);
                return $area;
            })
            ->filter(function ($area) {
                return $area->distance !== null;
            })
            ->sortBy('distance')
            ->first();
    }
}
