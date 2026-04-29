<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'preference_key',
        'preference_value'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('preference_key', $key);
    }

    // Helper Methods
    public static function getPreference($customerId, $key, $default = null)
    {
        $preference = static::where('customer_id', $customerId)
            ->where('preference_key', $key)
            ->first();
            
        return $preference ? $preference->preference_value : $default;
    }

    public static function setPreference($customerId, $key, $value)
    {
        return static::updateOrCreate(
            [
                'customer_id' => $customerId,
                'preference_key' => $key
            ],
            [
                'preference_value' => $value
            ]
        );
    }

    public static function getPreferences($customerId)
    {
        return static::where('customer_id', $customerId)
            ->pluck('preference_value', 'preference_key')
            ->toArray();
    }

    public static function setPreferences($customerId, array $preferences)
    {
        foreach ($preferences as $key => $value) {
            static::setPreference($customerId, $key, $value);
        }
    }
}
