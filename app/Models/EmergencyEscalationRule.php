<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyEscalationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_template_id',
        'delay_minutes',
        'escalation_type',
        'escalation_targets',
        'escalation_message',
        'is_active',
        'priority_order'
    ];

    protected $casts = [
        'delay_minutes' => 'integer',
        'escalation_targets' => 'array',
        'is_active' => 'boolean',
        'priority_order' => 'integer'
    ];

    /**
     * Get the emergency template
     */
    public function emergencyTemplate()
    {
        return $this->belongsTo(EmergencyTemplate::class);
    }

    /**
     * Get escalation type badge color
     */
    public function getEscalationTypeColorAttribute()
    {
        $colors = [
            'contact_manager' => 'bg-blue-100 text-blue-800',
            'contact_department' => 'bg-green-100 text-green-800',
            'contact_emergency_services' => 'bg-red-100 text-red-800',
            'notify_all_contacts' => 'bg-orange-100 text-orange-800'
        ];
        return $colors[$this->escalation_type] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get formatted delay
     */
    public function getFormattedDelayAttribute()
    {
        if ($this->delay_minutes < 60) {
            return $this->delay_minutes . ' minutes';
        }

        $hours = floor($this->delay_minutes / 60);
        $minutes = $this->delay_minutes % 60;
        
        if ($minutes > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        
        return $hours . ' hour' . ($hours > 1 ? 's' : '');
    }

    /**
     * Check if escalation should trigger
     */
    public function shouldTrigger($emergencyLog)
    {
        if (!$this->is_active) {
            return false;
        }

        $timeSinceTrigger = $emergencyLog->triggered_at->diffInMinutes(now());
        return $timeSinceTrigger >= $this->delay_minutes;
    }

    /**
     * Get escalation targets
     */
    public function getTargets()
    {
        return $this->escalation_targets ?? [];
    }

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for rules by template
     */
    public function scopeByTemplate($query, $templateId)
    {
        return $query->where('emergency_template_id', $templateId);
    }

    /**
     * Scope for rules by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('escalation_type', $type);
    }

    /**
     * Scope for rules ordered by priority
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority_order', 'asc');
    }
}