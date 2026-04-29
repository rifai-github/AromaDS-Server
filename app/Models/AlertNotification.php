<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_id',
        'user_id',
        'notification_sent'
    ];

    protected $casts = [
        'notification_sent' => 'boolean'
    ];

    // Relationships
    public function alert()
    {
        return $this->belongsTo(ReportAlert::class, 'alert_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeSent($query)
    {
        return $query->where('notification_sent', true);
    }

    public function scopePending($query)
    {
        return $query->where('notification_sent', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return $this->notification_sent ? 'Sent' : 'Pending';
    }
}
