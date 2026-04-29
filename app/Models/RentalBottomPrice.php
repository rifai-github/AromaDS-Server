<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class RentalBottomPrice extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'master_rental_id',
        'branch_id',
        'bottom_price',
        'replacement_price',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'bottom_price' => 'decimal:2',
        'replacement_price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByMasterRental($query, $masterRentalId)
    {
        return $query->where('master_rental_id', $masterRentalId);
    }

    // Helper methods
    public function getFormattedBottomPriceAttribute()
    {
        return 'Rp ' . number_format($this->bottom_price, 0, ',', '.');
    }

    public function getFormattedReplacementPriceAttribute()
    {
        return 'Rp ' . number_format($this->replacement_price, 0, ',', '.');
    }

    public function getPriceDifferenceAttribute()
    {
        return $this->replacement_price - $this->bottom_price;
    }

    public function getFormattedPriceDifferenceAttribute()
    {
        $difference = $this->price_difference;
        $sign = $difference >= 0 ? '+' : '';
        return $sign . 'Rp ' . number_format(abs($difference), 0, ',', '.');
    }

    public function isReplacementMoreExpensive()
    {
        return $this->replacement_price > $this->bottom_price;
    }

    public function getMarginPercentageAttribute()
    {
        if ($this->bottom_price == 0) {
            return 0;
        }
        
        return (($this->replacement_price - $this->bottom_price) / $this->bottom_price) * 100;
    }

    public function getFormattedMarginPercentageAttribute()
    {
        return number_format($this->margin_percentage, 2) . '%';
    }
}