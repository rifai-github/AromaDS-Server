<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Building;
use App\Models\User;

class BillingGroupBuilding extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'billing_group_id',
        'building_id',
        'billing_amount',
        'notes',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'billing_amount' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function billingGroup()
    {
        return $this->belongsTo(BillingGroup::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBillingGroup($query, $billingGroupId)
    {
        return $query->where('billing_group_id', $billingGroupId);
    }

    public function scopeByBuilding($query, $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    // Accessors
    public function getFormattedBillingAmountAttribute()
    {
        return 'Rp ' . number_format($this->billing_amount, 0, ',', '.');
    }

    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getStatusBadgeAttribute()
    {
        return $this->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    }
}