<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceSlab extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slab_code',
        'slab_name',
        'description',
        'min_quantity',
        'max_quantity',
        'discount_percentage',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function masterProducts()
    {
        return $this->hasMany(MasterProduct::class);
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

    public function scopeByQuantity($query, $quantity)
    {
        return $query->where('min_quantity', '<=', $quantity)
                    ->where('max_quantity', '>=', $quantity);
    }

    public function scopeOrderedByQuantity($query)
    {
        return $query->orderBy('min_quantity', 'asc');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->slab_name} ({$this->slab_code})";
    }

    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getQuantityRangeAttribute()
    {
        if ($this->max_quantity) {
            return "{$this->min_quantity} - {$this->max_quantity}";
        }
        return "{$this->min_quantity}+";
    }

    public function getDiscountFormattedAttribute()
    {
        return number_format($this->discount_percentage, 2) . '%';
    }

    // Methods
    public function isApplicableForQuantity($quantity)
    {
        return $quantity >= $this->min_quantity && 
               ($this->max_quantity === null || $quantity <= $this->max_quantity);
    }

    public function calculateDiscount($basePrice, $quantity)
    {
        if (!$this->isApplicableForQuantity($quantity)) {
            return 0;
        }

        $totalPrice = $basePrice * $quantity;
        return ($totalPrice * $this->discount_percentage) / 100;
    }

    public function calculateDiscountedPrice($basePrice, $quantity)
    {
        $discount = $this->calculateDiscount($basePrice, $quantity);
        return ($basePrice * $quantity) - $discount;
    }

    public static function findApplicableSlab($quantity)
    {
        return static::active()
                    ->where('min_quantity', '<=', $quantity)
                    ->where(function ($query) use ($quantity) {
                        $query->whereNull('max_quantity')
                              ->orWhere('max_quantity', '>=', $quantity);
                    })
                    ->orderBy('min_quantity', 'desc')
                    ->first();
    }
}
