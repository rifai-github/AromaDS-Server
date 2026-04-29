<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyResponseAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_log_id',
        'responder_id',
        'action_type',
        'description',
        'notes',
        'action_time',
        'duration_minutes',
        'outcome',
        'metadata'
    ];

    protected $casts = [
        'action_time' => 'datetime',
        'duration_minutes' => 'integer',
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
     * Get the responder
     */
    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }

    /**
     * Get action type badge color
     */
    public function getActionTypeColorAttribute()
    {
        $colors = [
            'call' => 'bg-blue-100 text-blue-800',
            'visit' => 'bg-green-100 text-green-800',
            'assist' => 'bg-yellow-100 text-yellow-800',
            'escalate' => 'bg-orange-100 text-orange-800',
            'resolve' => 'bg-green-100 text-green-800',
            'other' => 'bg-gray-100 text-gray-800'
        ];
        return $colors[$this->action_type] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get outcome badge color
     */
    public function getOutcomeColorAttribute()
    {
        $colors = [
            'successful' => 'bg-green-100 text-green-800',
            'partial' => 'bg-yellow-100 text-yellow-800',
            'failed' => 'bg-red-100 text-red-800',
            'escalated' => 'bg-orange-100 text-orange-800'
        ];
        return $colors[$this->outcome] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration_minutes) {
            return 'N/A';
        }

        if ($this->duration_minutes < 60) {
            return $this->duration_minutes . ' minutes';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        return $hours . 'h ' . $minutes . 'm';
    }

    /**
     * Scope for actions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope for actions by outcome
     */
    public function scopeByOutcome($query, $outcome)
    {
        return $query->where('outcome', $outcome);
    }

    /**
     * Scope for actions by responder
     */
    public function scopeByResponder($query, $responderId)
    {
        return $query->where('responder_id', $responderId);
    }

    /**
     * Scope for recent actions
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('action_time', '>=', now()->subDays($days));
    }
}