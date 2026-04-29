<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class MasterPriceSlab extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'slab_code',
        'slab_name',
        'description',
        'min_quantity',
        'max_quantity',
        'unit_price',
        'discount_percentage',
        'status',
        'master_rental_id',
        'is_active',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
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

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByRental($query, $rentalId)
    {
        return $query->where('master_rental_id', $rentalId);
    }

    public function scopeByQuantity($query, $quantity)
    {
        return $query->where('min_quantity', '<=', $quantity)
                    ->where('max_quantity', '>=', $quantity);
    }

    // Accessors
    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }

    public function getFormattedDiscountAttribute()
    {
        return number_format($this->discount_percentage, 2) . '%';
    }

    public function getQuantityRangeAttribute()
    {
        if ($this->max_quantity === null) {
            return $this->min_quantity . '+';
        }
        return $this->min_quantity . ' - ' . $this->max_quantity;
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

    public function isApplicableForQuantity($quantity)
    {
        return $quantity >= $this->min_quantity && 
               ($this->max_quantity === null || $quantity <= $this->max_quantity);
    }

    public function calculateDiscount($amount)
    {
        return $amount * ($this->discount_percentage / 100);
    }

    public function calculateDiscountedAmount($amount)
    {
        return $amount - $this->calculateDiscount($amount);
    }

    public function calculateTotalPrice($quantity)
    {
        $unitPrice = $this->unit_price ?? 0;
        $discountedPrice = $this->calculateDiscountedAmount($unitPrice);
        return $discountedPrice * $quantity;
    }

    public function isEffective()
    {
        return $this->is_active && $this->status === 'active';
    }

    public function isExpired()
    {
        return !$this->is_active;
    }

    // Static Methods
    public static function getApplicableSlab($rentalId, $quantity)
    {
        return self::where('master_rental_id', $rentalId)
                   ->where('is_active', true)
                   ->where('status', 'active')
                   ->where('min_quantity', '<=', $quantity)
                   ->where(function($query) use ($quantity) {
                       $query->whereNull('max_quantity')
                             ->orWhere('max_quantity', '>=', $quantity);
                   })
                   ->orderBy('discount_percentage', 'desc')
                   ->first();
    }

    public static function getSlabsByRental($rentalId)
    {
        return self::where('master_rental_id', $rentalId)
                   ->where('is_active', true)
                   ->orderBy('min_quantity', 'asc')
                   ->get();
    }

    public static function getActiveSlabs()
    {
        return self::where('is_active', true)
                   ->where('status', 'active')
                   ->orderBy('min_quantity', 'asc')
                   ->get();
    }
}
