<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TechnicianPackageItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'package_id',
        'item_name',
        'item_description',
        'item_order',
        'is_required',
        'is_completed',
        'completed_at',
        'completed_by'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime'
    ];

    // Relationships
    public function package()
    {
        return $this->belongsTo(TechnicianPackage::class, 'package_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // Scopes
    public function scopeByPackage($query, $packageId)
    {
        return $query->where('package_id', $packageId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('item_order');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return $this->is_completed ? 'Completed' : 'Pending';
    }

    public function getStatusBadgeAttribute()
    {
        return $this->is_completed ? 'badge-success' : 'badge-secondary';
    }

    public function getIsRequiredTextAttribute()
    {
        return $this->is_required ? 'Required' : 'Optional';
    }

    public function getCompletedAtFormattedAttribute()
    {
        return $this->completed_at ? $this->completed_at->format('d/m/Y H:i:s') : '-';
    }

    // Methods
    public function complete($userId = null)
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by' => $userId ?? auth()->id()
        ]);

        // Update package completion status
        $this->package->updateCompletionStatus();

        return $this;
    }

    public function uncomplete()
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null
        ]);

        // Update package completion status
        $this->package->updateCompletionStatus();

        return $this;
    }

    public function toggleCompletion($userId = null)
    {
        if ($this->is_completed) {
            return $this->uncomplete();
        } else {
            return $this->complete($userId);
        }
    }
}