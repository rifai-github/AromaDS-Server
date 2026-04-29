<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'message',
        'message_type',
        'sent_by'
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // Scopes
    public function scopeByTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    public function scopeBySender($query, $userId)
    {
        return $query->where('sent_by', $userId);
    }

    public function scopeCustomer($query)
    {
        return $query->where('message_type', 'customer');
    }

    public function scopeStaff($query)
    {
        return $query->where('message_type', 'staff');
    }

    public function scopeSystem($query)
    {
        return $query->where('message_type', 'system');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getMessageTypeColorAttribute()
    {
        $colors = [
            'customer' => 'blue',
            'staff' => 'green',
            'system' => 'gray'
        ];
        
        return $colors[$this->message_type] ?? 'gray';
    }

    public function getMessageTypeIconAttribute()
    {
        $icons = [
            'customer' => 'user',
            'staff' => 'headset',
            'system' => 'cog'
        ];
        
        return $icons[$this->message_type] ?? 'question';
    }

    public function getShortMessageAttribute()
    {
        return strlen($this->message) > 100 
            ? substr($this->message, 0, 100) . '...' 
            : $this->message;
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getSenderNameAttribute()
    {
        if ($this->message_type === 'customer') {
            return $this->ticket->customer->name ?? 'Customer';
        } elseif ($this->message_type === 'staff') {
            return $this->sender->name ?? 'Staff';
        } else {
            return 'System';
        }
    }

    public function getIsFromCustomerAttribute()
    {
        return $this->message_type === 'customer';
    }

    public function getIsFromStaffAttribute()
    {
        return $this->message_type === 'staff';
    }

    public function getIsFromSystemAttribute()
    {
        return $this->message_type === 'system';
    }

    // Static Methods
    public static function getMessageTypes()
    {
        return [
            'customer' => 'Customer',
            'staff' => 'Staff',
            'system' => 'System'
        ];
    }

    public static function getRecentMessages($ticketId, $limit = 10)
    {
        return static::where('ticket_id', $ticketId)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public static function getMessageStats($ticketId = null)
    {
        $query = static::query();
        
        if ($ticketId) {
            $query->where('ticket_id', $ticketId);
        }
        
        return $query->selectRaw('message_type, COUNT(*) as count')
                    ->groupBy('message_type')
                    ->pluck('count', 'message_type');
    }
}
