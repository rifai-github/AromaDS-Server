<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'setting_key',
        'setting_value',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('setting_key', $key);
    }

    // Static Methods
    public static function get($companyId, $key, $default = null)
    {
        $setting = self::where('company_id', $companyId)
                      ->where('setting_key', $key)
                      ->where('is_active', true)
                      ->first();
        
        return $setting ? $setting->setting_value : $default;
    }

    public static function set($companyId, $key, $value, $description = null)
    {
        $setting = self::where('company_id', $companyId)
                      ->where('setting_key', $key)
                      ->first();
        
        if ($setting) {
            $setting->setting_value = $value;
            if ($description) {
                $setting->description = $description;
            }
            $setting->save();
        } else {
            self::create([
                'company_id' => $companyId,
                'setting_key' => $key,
                'setting_value' => $value,
                'description' => $description,
                'is_active' => true
            ]);
        }
    }

    public static function getAll($companyId)
    {
        return self::where('company_id', $companyId)
                  ->where('is_active', true)
                  ->pluck('setting_value', 'setting_key')
                  ->toArray();
    }
}
