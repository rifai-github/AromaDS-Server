<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'setting_value' => 'array'
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
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

    public function scopeByType($query, $type)
    {
        return $query->where('setting_type', $type);
    }

    // Accessors
    public function getSettingTypeTextAttribute()
    {
        $types = [
            'string' => 'String',
            'integer' => 'Integer',
            'boolean' => 'Boolean',
            'json' => 'JSON',
            'array' => 'Array'
        ];
        return $types[$this->setting_type] ?? $this->setting_type;
    }

    // Methods
    public function getValue()
    {
        switch ($this->setting_type) {
            case 'boolean':
                return (bool) $this->setting_value;
            case 'integer':
                return (int) $this->setting_value;
            case 'json':
            case 'array':
                return is_array($this->setting_value) ? $this->setting_value : json_decode($this->setting_value, true);
            default:
                return $this->setting_value;
        }
    }

    public function setValue($value)
    {
        switch ($this->setting_type) {
            case 'json':
            case 'array':
                $this->setting_value = is_array($value) ? $value : json_decode($value, true);
                break;
            default:
                $this->setting_value = $value;
        }
    }
}
