<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrail;

class CustomerInteraction extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'customer_id',
        'interaction_type',
        'subject',
        'description',
        'interaction_date',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'interaction_date' => 'datetime'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('interaction_type', $type);
    }

    public function scopeByCreator($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('interaction_date', '>=', now()->subDays($days));
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('interaction_date', now()->month)
                    ->whereYear('interaction_date', now()->year);
    }

    // Accessors
    public function getInteractionDateFormattedAttribute()
    {
        return $this->interaction_date ? $this->interaction_date->format('d F Y H:i') : null;
    }

    public function getShortDescriptionAttribute()
    {
        return strlen($this->description) > 100 
            ? substr($this->description, 0, 100) . '...' 
            : $this->description;
    }

    // Static Methods
    public static function getInteractionTypes()
    {
        return [
            'call' => 'Phone Call',
            'email' => 'Email',
            'meeting' => 'Meeting',
            'visit' => 'Site Visit',
            'chat' => 'Chat/WhatsApp',
            'other' => 'Other'
        ];
    }
}
