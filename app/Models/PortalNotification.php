<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'notification_type',
        'title',
        'message',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean'
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

    public function scopeByType($query, $type)
    {
        return $query->where('notification_type', $type);
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
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    public function markAsUnread()
    {
        $this->update(['is_read' => false]);
    }

    // Accessors
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getShortMessageAttribute()
    {
        return strlen($this->message) > 100 
            ? substr($this->message, 0, 100) . '...' 
            : $this->message;
    }
}
