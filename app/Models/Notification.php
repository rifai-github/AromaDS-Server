<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pada',
        'title',
        'message',
        'platform',
        'user_id',
        'is_read',
        'read_at',
        'type',
        'data',
        'created_by'
    ];

    protected $casts = [
        'pada' => 'datetime',
        'read_at' => 'datetime',
        'is_read' => 'boolean',
        'data' => 'array'
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

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('pada', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('pada', today());
    }

    // Accessors
    public function getIsUnreadAttribute()
    {
        return !$this->is_read;
    }

    public function getFormattedDateAttribute()
    {
        return $this->pada ? $this->pada->format('d/m/Y H:i') : '-';
    }

    public function getShortMessageAttribute()
    {
        return strlen($this->message) > 100 ? substr($this->message, 0, 100) . '...' : $this->message;
    }

    // Methods
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null
        ]);
    }
}
