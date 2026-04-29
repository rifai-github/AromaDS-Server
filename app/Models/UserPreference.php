<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPreference extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'preference_key',
        'preference_value',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'preference_value' => 'array'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('preference_key', $key);
    }

    // Methods
    public function getValue()
    {
        return $this->preference_value;
    }

    public function setValue($value)
    {
        $this->preference_value = $value;
    }

    // Static methods for easy preference management
    public static function getPreference($userId, $key, $default = null)
    {
        $preference = static::where('user_id', $userId)
            ->where('preference_key', $key)
            ->first();

        return $preference ? $preference->getValue() : $default;
    }

    public static function setPreference($userId, $key, $value)
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'preference_key' => $key
            ],
            [
                'preference_value' => $value,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]
        );
    }
}
