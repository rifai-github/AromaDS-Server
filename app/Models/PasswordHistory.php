<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'password_hash'
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

    public function scopeRecent($query, $count = 5)
    {
        return $query->orderBy('created_at', 'desc')->limit($count);
    }

    // Helper methods
    public function isRecent($days = 30)
    {
        return $this->created_at > now()->subDays($days);
    }

    public function getAgeAttribute()
    {
        return $this->created_at->diffInDays(now());
    }

    public function getFormattedAgeAttribute()
    {
        $days = $this->age;
        
        if ($days < 1) {
            return 'Today';
        } elseif ($days < 7) {
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($days < 30) {
            $weeks = floor($days / 7);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } elseif ($days < 365) {
            $months = floor($days / 30);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        } else {
            $years = floor($days / 365);
            return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
        }
    }
}
