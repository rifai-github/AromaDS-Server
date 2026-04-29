<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrail;

class CustomerCampaign extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'campaign_name',
        'campaign_type',
        'target_segment',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    // Relationships
    public function targetSegment()
    {
        return $this->belongsTo(CustomerSegment::class, 'target_segment');
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
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->where('status', 'active');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now())
                    ->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    // Accessors
    public function getStatusColorAttribute()
    {
        $colors = [
            'draft' => 'gray',
            'active' => 'green',
            'paused' => 'yellow',
            'completed' => 'blue',
            'cancelled' => 'red'
        ];
        
        return $colors[$this->status] ?? 'gray';
    }

    public function getStatusIconAttribute()
    {
        $icons = [
            'draft' => 'edit',
            'active' => 'play',
            'paused' => 'pause',
            'completed' => 'check',
            'cancelled' => 'times'
        ];
        
        return $icons[$this->status] ?? 'question';
    }

    public function getDurationAttribute()
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    public function getIsExpiredAttribute()
    {
        return $this->end_date < now();
    }

    public function getIsUpcomingAttribute()
    {
        return $this->start_date > now() && $this->status === 'active';
    }

    public function getProgressAttribute()
    {
        if ($this->is_expired) {
            return 100;
        }
        
        if ($this->is_upcoming) {
            return 0;
        }
        
        $total = $this->start_date->diffInDays($this->end_date);
        $elapsed = $this->start_date->diffInDays(now());
        
        return min(100, round(($elapsed / $total) * 100));
    }

    // Static Methods
    public static function getCampaignTypes()
    {
        return [
            'email' => 'Email Campaign',
            'sms' => 'SMS Campaign',
            'push' => 'Push Notification',
            'social' => 'Social Media',
            'direct_mail' => 'Direct Mail',
            'other' => 'Other'
        ];
    }

    public static function getStatuses()
    {
        return [
            'draft' => 'Draft',
            'active' => 'Active',
            'paused' => 'Paused',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];
    }
}
