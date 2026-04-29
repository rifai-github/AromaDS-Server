<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'relationship',
        'phone_number',
        'email',
        'address',
        'contact_type',
        'is_active',
        'can_receive_sms',
        'can_receive_email',
        'can_receive_whatsapp',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'can_receive_sms' => 'boolean',
        'can_receive_email' => 'boolean',
        'can_receive_whatsapp' => 'boolean'
    ];

    /**
     * Get the user that owns this emergency contact
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get emergency logs for this contact
     */
    public function emergencyLogs()
    {
        return $this->hasMany(EmergencyLog::class);
    }

    /**
     * Get emergency notifications sent to this contact
     */
    public function emergencyNotifications()
    {
        return $this->hasMany(EmergencyNotification::class);
    }

    /**
     * Get formatted phone number
     */
    public function getFormattedPhoneAttribute()
    {
        $phone = $this->phone_number;
        if (strlen($phone) > 10) {
            return substr($phone, 0, 4) . '-' . substr($phone, 4, 4) . '-' . substr($phone, 8);
        }
        return $phone;
    }

    /**
     * Get contact type badge color
     */
    public function getContactTypeColorAttribute()
    {
        $colors = [
            'primary' => 'bg-blue-100 text-blue-800',
            'secondary' => 'bg-gray-100 text-gray-800',
            'backup' => 'bg-yellow-100 text-yellow-800'
        ];
        return $colors[$this->contact_type] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get relationship badge color
     */
    public function getRelationshipColorAttribute()
    {
        $colors = [
            'spouse' => 'bg-pink-100 text-pink-800',
            'parent' => 'bg-purple-100 text-purple-800',
            'sibling' => 'bg-green-100 text-green-800',
            'friend' => 'bg-blue-100 text-blue-800',
            'colleague' => 'bg-orange-100 text-orange-800'
        ];
        return $colors[$this->relationship] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Check if contact can receive notifications
     */
    public function canReceiveNotification($type)
    {
        switch ($type) {
            case 'sms':
                return $this->can_receive_sms && $this->phone_number;
            case 'email':
                return $this->can_receive_email && $this->email;
            case 'whatsapp':
                return $this->can_receive_whatsapp && $this->phone_number;
            default:
                return false;
        }
    }

    /**
     * Scope for active contacts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for primary contacts
     */
    public function scopePrimary($query)
    {
        return $query->where('contact_type', 'primary');
    }

    /**
     * Scope for contacts by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('contact_type', $type);
    }

    /**
     * Scope for contacts by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}