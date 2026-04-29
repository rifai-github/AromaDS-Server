<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmergencyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'emergency_contact_id',
        'emergency_type',
        'title',
        'description',
        'severity',
        'status',
        'triggered_at',
        'notified_at',
        'responded_at',
        'resolved_at',
        'response_time_minutes',
        'resolution_time_minutes',
        'location',
        'metadata',
        'created_by'
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'notified_at' => 'datetime',
        'responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
        'response_time_minutes' => 'integer',
        'resolution_time_minutes' => 'integer'
    ];

    /**
     * Get the user who triggered this emergency
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the emergency contact
     */
    public function emergencyContact()
    {
        return $this->belongsTo(EmergencyContact::class);
    }

    /**
     * Get the user who created this log
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get emergency notifications for this log
     */
    public function emergencyNotifications()
    {
        return $this->hasMany(EmergencyNotification::class);
    }

    /**
     * Get response actions for this emergency
     */
    public function responseActions()
    {
        return $this->hasMany(EmergencyResponseAction::class);
    }

    /**
     * Get emergency template
     */
    public function emergencyTemplate()
    {
        return $this->belongsTo(EmergencyTemplate::class, 'emergency_type', 'emergency_type')
                    ->where('severity', $this->severity);
    }

    /**
     * Get severity badge color
     */
    public function getSeverityColorAttribute()
    {
        $colors = [
            'low' => 'bg-green-100 text-green-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'high' => 'bg-orange-100 text-orange-800',
            'critical' => 'bg-red-100 text-red-800'
        ];
        return $colors[$this->severity] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'bg-gray-100 text-gray-800',
            'notified' => 'bg-blue-100 text-blue-800',
            'responded' => 'bg-yellow-100 text-yellow-800',
            'resolved' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800'
        ];
        return $colors[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get emergency type badge color
     */
    public function getEmergencyTypeColorAttribute()
    {
        $colors = [
            'medical' => 'bg-red-100 text-red-800',
            'safety' => 'bg-orange-100 text-orange-800',
            'security' => 'bg-purple-100 text-purple-800',
            'technical' => 'bg-blue-100 text-blue-800',
            'other' => 'bg-gray-100 text-gray-800'
        ];
        return $colors[$this->emergency_type] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Calculate response time
     */
    public function calculateResponseTime()
    {
        if ($this->responded_at && $this->triggered_at) {
            $this->response_time_minutes = $this->triggered_at->diffInMinutes($this->responded_at);
            $this->save();
        }
    }

    /**
     * Calculate resolution time
     */
    public function calculateResolutionTime()
    {
        if ($this->resolved_at && $this->triggered_at) {
            $this->resolution_time_minutes = $this->triggered_at->diffInMinutes($this->resolved_at);
            $this->save();
        }
    }

    /**
     * Mark as notified
     */
    public function markAsNotified()
    {
        $this->update([
            'status' => 'notified',
            'notified_at' => now()
        ]);
    }

    /**
     * Mark as responded
     */
    public function markAsResponded()
    {
        $this->update([
            'status' => 'responded',
            'responded_at' => now()
        ]);
        $this->calculateResponseTime();
    }

    /**
     * Mark as resolved
     */
    public function markAsResolved()
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now()
        ]);
        $this->calculateResolutionTime();
    }

    /**
     * Check if emergency is overdue
     */
    public function isOverdue()
    {
        if ($this->status === 'resolved' || $this->status === 'cancelled') {
            return false;
        }

        $overdueMinutes = [
            'low' => 60,      // 1 hour
            'medium' => 30,   // 30 minutes
            'high' => 15,     // 15 minutes
            'critical' => 5   // 5 minutes
        ];

        $threshold = $overdueMinutes[$this->severity] ?? 30;
        return $this->triggered_at->diffInMinutes(now()) > $threshold;
    }

    /**
     * Get formatted response time
     */
    public function getFormattedResponseTimeAttribute()
    {
        if (!$this->response_time_minutes) {
            return 'N/A';
        }

        if ($this->response_time_minutes < 60) {
            return $this->response_time_minutes . ' minutes';
        }

        $hours = floor($this->response_time_minutes / 60);
        $minutes = $this->response_time_minutes % 60;
        
        return $hours . 'h ' . $minutes . 'm';
    }

    /**
     * Get formatted resolution time
     */
    public function getFormattedResolutionTimeAttribute()
    {
        if (!$this->resolution_time_minutes) {
            return 'N/A';
        }

        if ($this->resolution_time_minutes < 60) {
            return $this->resolution_time_minutes . ' minutes';
        }

        $hours = floor($this->resolution_time_minutes / 60);
        $minutes = $this->resolution_time_minutes % 60;
        
        return $hours . 'h ' . $minutes . 'm';
    }

    /**
     * Scope for active emergencies
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'notified', 'responded']);
    }

    /**
     * Scope for emergencies by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('emergency_type', $type);
    }

    /**
     * Scope for emergencies by severity
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for emergencies by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for overdue emergencies
     */
    public function scopeOverdue($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'pending')
              ->orWhere('status', 'notified')
              ->orWhere('status', 'responded');
        })->where('triggered_at', '<', now()->subMinutes(30));
    }

    /**
     * Scope for recent emergencies
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('triggered_at', '>=', now()->subDays($days));
    }
}