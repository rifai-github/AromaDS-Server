<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractBuilding extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_id',
        'building_id'
    ];

    // Relationships
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function billingGroup()
    {
        return $this->belongsTo(BillingGroup::class, 'billing_id', 'billing_id');
    }

    // Scopes
    public function scopeByBillingId($query, $billingId)
    {
        return $query->where('billing_id', $billingId);
    }

    public function scopeByBuilding($query, $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }
}
