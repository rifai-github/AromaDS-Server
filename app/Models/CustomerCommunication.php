<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerCommunication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'communication_type',
        'subject',
        'message',
        'sent_at',
        'sent_by'
    ];

    protected $casts = [
        'sent_at' => 'datetime'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('communication_type', $type);
    }

    public function scopeBySender($query, $userId)
    {
        return $query->where('sent_by', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('sent_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sent_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('sent_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sent_at', now()->month)
                    ->whereYear('sent_at', now()->year);
    }

    // Accessors
    public function getCommunicationTypeColorAttribute()
    {
        $colors = [
            'email' => 'blue',
            'sms' => 'green',
            'call' => 'yellow',
            'push' => 'purple',
            'other' => 'gray'
        ];
        
        return $colors[$this->communication_type] ?? 'gray';
    }

    public function getCommunicationTypeIconAttribute()
    {
        $icons = [
            'email' => 'envelope',
            'sms' => 'comment',
            'call' => 'phone',
            'push' => 'bell',
            'other' => 'question'
        ];
        
        return $icons[$this->communication_type] ?? 'question';
    }

    public function getShortMessageAttribute()
    {
        return strlen($this->message) > 100 
            ? substr($this->message, 0, 100) . '...' 
            : $this->message;
    }

    public function getTimeAgoAttribute()
    {
        return $this->sent_at->diffForHumans();
    }

    public function getSentDateFormattedAttribute()
    {
        return $this->sent_at->format('d F Y H:i');
    }

    // Static Methods
    public static function getCommunicationTypes()
    {
        return [
            'email' => 'Email',
            'sms' => 'SMS',
            'call' => 'Phone Call',
            'push' => 'Push Notification',
            'other' => 'Other'
        ];
    }

    public static function getStats($customerId = null, $days = 30)
    {
        $query = static::recent($days);
        
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        
        return $query->selectRaw('communication_type, COUNT(*) as count')
                    ->groupBy('communication_type')
                    ->pluck('count', 'communication_type');
    }
}
