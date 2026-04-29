<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'session_token',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeByToken($query, $token)
    {
        return $query->where('session_token', $token);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Helper Methods
    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function extend($minutes = 120)
    {
        $this->update([
            'expires_at' => now()->addMinutes($minutes)
        ]);
    }
}
