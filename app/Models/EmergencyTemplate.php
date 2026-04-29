<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'emergency_type',
        'severity',
        'title_template',
        'message_template',
        'notification_channels',
        'escalation_delay_minutes',
        'is_active',
        'description'
    ];

    protected $casts = [
        'notification_channels' => 'array',
        'escalation_delay_minutes' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Get escalation rules for this template
     */
    public function escalationRules()
    {
        return $this->hasMany(EmergencyEscalationRule::class);
    }

    /**
     * Get emergency logs using this template
     */
    public function emergencyLogs()
    {
        return $this->hasMany(EmergencyLog::class, 'emergency_type', 'emergency_type')
                    ->where('severity', $this->severity);
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
     * Process template with variables
     */
    public function processTemplate($variables = [])
    {
        $title = $this->title_template;
        $message = $this->message_template;

        foreach ($variables as $key => $value) {
            $title = str_replace('{' . $key . '}', $value, $title);
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return [
            'title' => $title,
            'message' => $message
        ];
    }

    /**
     * Get available notification channels
     */
    public function getAvailableChannelsAttribute()
    {
        return $this->notification_channels ?? ['sms', 'email'];
    }

    /**
     * Check if channel is available
     */
    public function hasChannel($channel)
    {
        return in_array($channel, $this->available_channels);
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for templates by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('emergency_type', $type);
    }

    /**
     * Scope for templates by severity
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for templates by code
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }
}