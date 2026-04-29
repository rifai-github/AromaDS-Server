<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\AutoFilterable;
use App\Models\MasterRental;

class ContractRental extends Model
{
    use HasFactory, AutoFilterable;

    protected $fillable = [
        'contract_id',
        'master_rental_id',
        'rental_alias',
        'room_id',
        'quantity',
        'unit_price',
        'total_price',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity' => 'decimal:2'
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
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

    public function scopeByRentalType($query, $type)
    {
        return $query->where('rental_type', $type);
    }

    // Accessors
    public function getFormattedUnitPriceAttribute()
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getFormattedTotalPriceAttribute()
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    // Mutators
    public function setTotalPriceAttribute($value)
    {
        $this->attributes['total_price'] = $this->quantity * $this->unit_price;
    }
}
