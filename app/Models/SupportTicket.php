<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrail;

class SupportTicket extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'subject',
        'description',
        'priority',
        'status',
        'assigned_to',
        'created_by',
        'updated_by'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id');
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'closed')
                    ->where('created_at', '<', now()->subDays(7));
    }

    // Accessors
    public function getPriorityColorAttribute()
    {
        $colors = [
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'urgent' => 'red'
        ];
        
        return $colors[$this->priority] ?? 'gray';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'open' => 'blue',
            'in_progress' => 'yellow',
            'resolved' => 'green',
            'closed' => 'gray'
        ];
        
        return $colors[$this->status] ?? 'gray';
    }

    public function getPriorityIconAttribute()
    {
        $icons = [
            'low' => 'arrow-down',
            'medium' => 'minus',
            'high' => 'arrow-up',
            'urgent' => 'exclamation-triangle'
        ];
        
        return $icons[$this->priority] ?? 'question';
    }

    public function getStatusIconAttribute()
    {
        $icons = [
            'open' => 'clock',
            'in_progress' => 'play',
            'resolved' => 'check',
            'closed' => 'lock'
        ];
        
        return $icons[$this->status] ?? 'question';
    }

    public function getShortDescriptionAttribute()
    {
        return strlen($this->description) > 100 
            ? substr($this->description, 0, 100) . '...' 
            : $this->description;
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getLastMessageAttribute()
    {
        return $this->messages()->latest()->first();
    }

    public function getMessageCountAttribute()
    {
        return $this->messages()->count();
    }

    public function getIsOverdueAttribute()
    {
        return $this->status !== 'closed' && $this->created_at < now()->subDays(7);
    }

    public function getDaysOpenAttribute()
    {
        return $this->created_at->diffInDays(now());
    }

    // Helper Methods
    public function assignTo($userId)
    {
        $this->update(['assigned_to' => $userId]);
    }

    public function updateStatus($status)
    {
        $this->update(['status' => $status]);
    }

    public function addMessage($message, $messageType = 'staff', $sentBy = null)
    {
        return $this->messages()->create([
            'message' => $message,
            'message_type' => $messageType,
            'sent_by' => $sentBy ?? auth()->id()
        ]);
    }

    // Static Methods
    public static function getPriorities()
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent'
        ];
    }

    public static function getStatuses()
    {
        return [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed'
        ];
    }

    public static function generateTicketNumber()
    {
        $prefix = 'TKT';
        $date = now()->format('Ymd');
        $lastTicket = static::where('ticket_number', 'like', $prefix . $date . '%')
                          ->orderBy('ticket_number', 'desc')
                          ->first();
        
        if ($lastTicket) {
            $lastNumber = (int) substr($lastTicket->ticket_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public static function getStats()
    {
        return [
            'total' => static::count(),
            'open' => static::open()->count(),
            'closed' => static::closed()->count(),
            'overdue' => static::overdue()->count(),
            'high_priority' => static::highPriority()->count()
        ];
    }
}
