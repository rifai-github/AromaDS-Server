<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ContractRoom extends Model
{
    use HasFactory;

    protected bool $rentalProductResolved = false;
    protected mixed $rentalProductResolvedValue = null;

    protected $fillable = [
        'contract_id',
        'room_id',
        'billing_group_id',
        'source_contract_id',
        'source_contract_room_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        // No additional casts needed for current table structure
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    /**
     * Contract asal room ini sebelum di-merge
     */
    public function sourceContract()
    {
        return $this->belongsTo(Contract::class, 'source_contract_id');
    }

    /**
     * Apakah room ini berasal dari merge?
     */
    public function getIsMergedRoomAttribute()
    {
        return !is_null($this->source_contract_id);
    }

    // Accessor to get building
    public function getBuildingAttribute()
    {
        // 1. Try to get from MasterRoom (most accurate for multi-building contracts)
        if ($this->room && $this->room->building) {
            return $this->room->building;
        }

        // 2. Fallback to contract's quotation's survey (legacy/single building contracts)
        if ($this->contract && $this->contract->quotation && $this->contract->quotation->survey) {
            return $this->contract->quotation->survey->building;
        }
        
        return null;
    }

    public function billingGroup()
    {
        return $this->belongsTo(\App\Models\Finance\BillingGroup::class);
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
        return $query->where('status', 'active');
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    // Accessors
    public function getFormattedRentalRateAttribute()
    {
        return 'Rp ' . number_format($this->rental_rate, 0, ',', '.');
    }

    public function getFormattedAreaAttribute()
    {
        return number_format($this->area, 2) . ' m²';
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    /**
     * Get the rental product associated with this contract room via ContractRental.
     * Calculated based on matching contract_id and room_id.
     */
    public function getRentalProductAttribute()
    {
        if ($this->rentalProductResolved) {
            return $this->rentalProductResolvedValue;
        }

        // 1. Try exact match (Contract + Room)
        $contractRental = \App\Models\ContractRental::where('contract_id', $this->contract_id)
            ->where('room_id', $this->room_id)
            ->first();

        // 2. Fallback: Try match with room_id = NULL (common in old data or unit-only contracts)
        if (!$contractRental) {
            $contractRental = \App\Models\ContractRental::where('contract_id', $this->contract_id)
                ->whereNull('room_id')
                ->first();
        }

        // 3. Fallback: If contract has ONLY ONE rental, assume it belongs to this room
        if (!$contractRental) {
            $allRentals = \App\Models\ContractRental::where('contract_id', $this->contract_id)->get();
            if ($allRentals->count() === 1) {
                $contractRental = $allRentals->first();
            }
        }

        // Return the associated MasterRental if found
        $this->rentalProductResolvedValue = $contractRental ? $contractRental->masterRental : null;
        $this->rentalProductResolved = true;

        return $this->rentalProductResolvedValue;
    }
}
