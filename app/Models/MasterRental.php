<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class MasterRental extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail, \App\Http\Traits\AutoFilterable;

    protected $fillable = [
        'rental_code',
        'rental_name',
        'alias',
        'description',
        'service_frequency_id',
        'category',
        'rental_type',
        'daily_price',
        'monthly_price',
        'lost_unit_price',
        'install_duration',
        'service_duration',
        'unit',
        'has_activation_component',
        'notes',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'daily_price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'lost_unit_price' => 'decimal:2',
        'install_duration' => 'integer',
        'service_duration' => 'integer',
        'has_activation_component' => 'boolean'
    ];

    // Relationships
    public function serviceFrequency()
    {
        return $this->belongsTo(RentalServiceFrequency::class, 'service_frequency_id');
    }

    public function rentalDetails()
    {
        return $this->hasMany(RentalDetail::class);
    }

    public function rentalPrices()
    {
        return $this->hasMany(RentalPrice::class);
    }

    public function contractRentals()
    {
        return $this->hasMany(ContractRental::class);
    }

    public function jobSchedules()
    {
        return $this->hasMany(JobSchedule::class);
    }

    public function materialIssues()
    {
        return $this->hasMany(MaterialIssue::class);
    }

    public function roomRentalUnits()
    {
        return $this->hasMany(RoomRentalUnit::class);
    }

    public function unitOnWalls()
    {
        return $this->hasMany(UnitOnWall::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function rentalComponents()
    {
        return $this->hasMany(RentalComponent::class);
    }

    public function activationComponents()
    {
        return $this->hasMany(RentalComponent::class)->where('is_activation_component', true);
    }

    public function bottomPrices()
    {
        return $this->hasMany(RentalBottomPrice::class);
    }

    public function bottomPricesByBranch($branchId)
    {
        return $this->hasMany(RentalBottomPrice::class)->where('branch_id', $branchId);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByServiceFrequency($query, $frequency)
    {
        return $query->where('service_frequency', $frequency);
    }

    // Business Logic Methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }

    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }
    
    // Alias untuk rental_price -> monthly_price
    public function getRentalPriceAttribute()
    {
        return $this->monthly_price;
    }

    public function getDailyPriceFormattedAttribute()
    {
        return 'Rp ' . number_format($this->daily_price, 2, ',', '.');
    }

    public function getMonthlyPriceFormattedAttribute()
    {
        return 'Rp ' . number_format($this->monthly_price, 2, ',', '.');
    }

    public function getRentalTypeTextAttribute()
    {
        return match($this->rental_type) {
            'unit_refill' => 'Unit + Refill',
            'unit_only' => 'Unit Only',
            'refill_only' => 'Refill Only',
            default => 'Unknown'
        };
    }

    public function getHasActivationComponentTextAttribute()
    {
        return $this->has_activation_component ? 'Yes' : 'No';
    }

    public function getComponentCountAttribute()
    {
        return $this->rentalComponents()->count();
    }

    public function getActivationComponentCountAttribute()
    {
        return $this->activationComponents()->count();
    }

    public function hasActivationComponent()
    {
        return $this->activationComponents()->exists();
    }

    public function getBottomPriceForBranch($branchId)
    {
        return $this->bottomPricesByBranch($branchId)->first();
    }

    public function getTotalComponentReplacementPrice()
    {
        return $this->rentalComponents()->sum('replacement_price');
    }

    public function getFormattedTotalComponentReplacementPriceAttribute()
    {
        return 'Rp ' . number_format($this->getTotalComponentReplacementPrice(), 0, ',', '.');
    }
}
