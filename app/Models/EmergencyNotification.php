<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_log_id',
        'emergency_contact_id',
        'notification_type',
        'status',
        'message',
        'recipient_phone',
        'recipient_email',
        'sent_at',
        'delivered_at',
        'read_at',
        'error_message',
        'metadata'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Get the emergency log
     */
    public function emergencyLog()
    {
        return $this->belongsTo(EmergencyLog::class);
    }

    /**
     * Get the emergency contact
     */
    public function emergencyContact()
    {
        return $this->belongsTo(EmergencyContact::class);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'bg-gray-100 text-gray-800',
            'sent' => 'bg-blue-100 text-blue-800',
            'delivered' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'read' => 'bg-green-100 text-green-800'
        ];
        return $colors[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get notification type icon
     */
    public function getNotificationTypeIconAttribute()
    {
        $icons = [
            'sms' => 'fas fa-sms',
            'email' => 'fas fa-envelope',
            'whatsapp' => 'fab fa-whatsapp',
            'push' => 'fas fa-bell'
        ];
        return $icons[$this->notification_type] ?? 'fas fa-bell';
    }

    /**
     * Mark as sent
     */
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered()
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);
    }

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        $this->update([
            'status' => 'read',
            'read_at' => now()
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Get delivery time
     */
    public function getDeliveryTimeAttribute()
    {
        if ($this->delivered_at && $this->sent_at) {
            return $this->sent_at->diffInSeconds($this->delivered_at);
        }
        return null;
    }

    /**
     * Scope for successful notifications
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', ['sent', 'delivered', 'read']);
    }

    /**
     * Scope for failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for notifications by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('notification_type', $type);
    }
}