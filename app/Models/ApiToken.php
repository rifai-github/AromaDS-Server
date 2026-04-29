<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at'
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    protected $hidden = [
        'token'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeRecentlyUsed($query, $days = 7)
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }

    public function scopeNeverUsed($query)
    {
        return $query->whereNull('last_used_at');
    }

    // Helper methods
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at <= now();
    }

    public function isActive()
    {
        return !$this->isExpired();
    }

    public function hasAbility($ability)
    {
        if (!$this->abilities) {
            return false;
        }

        return in_array('*', $this->abilities) || in_array($ability, $this->abilities);
    }

    public function hasAnyAbility(array $abilities)
    {
        if (!$this->abilities) {
            return false;
        }

        if (in_array('*', $this->abilities)) {
            return true;
        }

        return !empty(array_intersect($abilities, $this->abilities));
    }

    public function hasAllAbilities(array $abilities)
    {
        if (!$this->abilities) {
            return false;
        }

        if (in_array('*', $this->abilities)) {
            return true;
        }

        return empty(array_diff($abilities, $this->abilities));
    }

    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    public function getIsExpiringSoonAttribute()
    {
        return $this->expires_at && $this->days_until_expiry <= 7 && $this->days_until_expiry > 0;
    }

    public function getFormattedAbilitiesAttribute()
    {
        if (!$this->abilities) {
            return 'No abilities';
        }

        if (in_array('*', $this->abilities)) {
            return 'All abilities';
        }

        return implode(', ', $this->abilities);
    }

    public function getLastUsedFormattedAttribute()
    {
        if (!$this->last_used_at) {
            return 'Never used';
        }

        return $this->last_used_at->diffForHumans();
    }

    public function getStatusAttribute()
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->is_expiring_soon) {
            return 'expiring_soon';
        }

        if (!$this->last_used_at) {
            return 'never_used';
        }

        return 'active';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'active' => 'green',
            'expiring_soon' => 'yellow',
            'expired' => 'red',
            'never_used' => 'gray'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getStatusTextAttribute()
    {
        $texts = [
            'active' => 'Active',
            'expiring_soon' => 'Expiring Soon',
            'expired' => 'Expired',
            'never_used' => 'Never Used'
        ];

        return $texts[$this->status] ?? 'Unknown';
    }

    // Static methods
    public static function generateToken($length = 64)
    {
        return bin2hex(random_bytes($length / 2));
    }

    public static function createToken($userId, $name, $abilities = ['*'], $expiresAt = null)
    {
        return self::create([
            'user_id' => $userId,
            'name' => $name,
            'token' => self::generateToken(),
            'abilities' => $abilities,
            'expires_at' => $expiresAt
        ]);
    }

    public static function getDefaultAbilities()
    {
        return [
            'read',
            'write',
            'delete',
            'admin'
        ];
    }

    public static function getAvailableAbilities()
    {
        return [
            '*' => 'All Abilities',
            'read' => 'Read Only',
            'write' => 'Read & Write',
            'delete' => 'Read, Write & Delete',
            'admin' => 'Administrative Access',
            'api:users:read' => 'Read Users',
            'api:users:write' => 'Write Users',
            'api:products:read' => 'Read Products',
            'api:products:write' => 'Write Products',
            'api:orders:read' => 'Read Orders',
            'api:orders:write' => 'Write Orders',
            'api:reports:read' => 'Read Reports',
            'api:system:read' => 'Read System Info',
            'api:system:write' => 'Write System Settings'
        ];
    }
}
