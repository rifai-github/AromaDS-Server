<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndonesiaRegion extends Model
{
    protected $fillable = [
        'code',
        'name',
        'level',
        'parent_code'
    ];

    protected $casts = [
        'code' => 'string',
        'name' => 'string',
        'level' => 'string',
        'parent_code' => 'string'
    ];

    /**
     * Get the parent region
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(IndonesiaRegion::class, 'parent_code', 'code');
    }

    /**
     * Get the child regions
     */
    public function children(): HasMany
    {
        return $this->hasMany(IndonesiaRegion::class, 'parent_code', 'code');
    }

    /**
     * Get provinces
     */
    public static function getProvinces()
    {
        return self::where('level', 'province')->orderBy('name')->get();
    }

    /**
     * Get cities by province code
     */
    public static function getCitiesByProvince($provinceCode)
    {
        return self::where('level', 'city')
                   ->where('parent_code', $provinceCode)
                   ->orderBy('name')
                   ->get();
    }

    /**
     * Get districts by city code
     */
    public static function getDistrictsByCity($cityCode)
    {
        return self::where('level', 'district')
                   ->where('parent_code', $cityCode)
                   ->orderBy('name')
                   ->get();
    }

    /**
     * Get villages by district code
     */
    public static function getVillagesByDistrict($districtCode)
    {
        return self::where('level', 'village')
                   ->where('parent_code', $districtCode)
                   ->orderBy('name')
                   ->get();
    }
}
