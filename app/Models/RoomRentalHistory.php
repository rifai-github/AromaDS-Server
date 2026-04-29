<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class RoomRentalHistory extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'room_id',
        'quotation_number',
        'contract_number',
        'installation_date',
        'last_service_date',
        'removal_date',
        'status',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'installation_date' => 'datetime',
        'last_service_date' => 'datetime',
        'removal_date' => 'datetime'
    ];

    // Relationships
    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeByQuotation($query, $quotationNumber)
    {
        return $query->where('quotation_number', $quotationNumber);
    }

    public function scopeByContract($query, $contractNumber)
    {
        return $query->where('contract_number', $contractNumber);
    }

    public function scopeInstalled($query)
    {
        return $query->where('status', 'installed');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRemoved($query)
    {
        return $query->where('status', 'removed');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'installed' => 'Installed',
            'active' => 'Active',
            'removed' => 'Removed',
            default => 'Unknown'
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'installed' => 'badge-info',
            'active' => 'badge-success',
            'removed' => 'badge-danger',
            default => 'badge-light'
        };
    }

    public function getFormattedInstallationDateAttribute()
    {
        return $this->installation_date->format('d M Y H:i');
    }

    public function getFormattedLastServiceDateAttribute()
    {
        return $this->last_service_date ? $this->last_service_date->format('d M Y H:i') : 'N/A';
    }

    public function getFormattedRemovalDateAttribute()
    {
        return $this->removal_date ? $this->removal_date->format('d M Y H:i') : 'N/A';
    }

    public function getDaysSinceInstallationAttribute()
    {
        return $this->installation_date->diffInDays(now());
    }

    public function getDaysSinceLastServiceAttribute()
    {
        return $this->last_service_date ? $this->last_service_date->diffInDays(now()) : null;
    }

    // Helper methods
    public function isInstalled()
    {
        return $this->status === 'installed';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isRemoved()
    {
        return $this->status === 'removed';
    }

    public function canBeActivated()
    {
        return $this->status === 'installed';
    }

    public function canBeRemoved()
    {
        return in_array($this->status, ['installed', 'active']);
    }
}
