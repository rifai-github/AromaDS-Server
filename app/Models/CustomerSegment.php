<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrail;

class CustomerSegment extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'segment_name',
        'segment_criteria',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function campaigns()
    {
        return $this->hasMany(CustomerCampaign::class, 'target_segment');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('segment_name', 'like', '%' . $name . '%');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getCustomerCountAttribute()
    {
        // This would need to be implemented based on the criteria
        // For now, return 0
        return 0;
    }

    // Helper Methods
    public function getCustomers()
    {
        // This would need to be implemented based on the segment_criteria
        // For now, return empty collection
        return collect();
    }

    // Static Methods
    public static function getActiveSegments()
    {
        return static::active()->get();
    }
}
