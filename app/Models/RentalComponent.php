<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class RentalComponent extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'master_rental_id',
        'component_name',
        'description',
        'quantity',
        'unit',
        'replacement_frequency_months',
        'replacement_price',
        'is_activation_component',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'replacement_frequency_months' => 'integer',
        'replacement_price' => 'decimal:2',
        'is_activation_component' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Relationships
    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
    }

    public function componentProducts()
    {
        return $this->hasMany(RentalComponentProduct::class);
    }

    public function allowedProducts()
    {
        return $this->belongsToMany(MasterProduct::class, 'rental_component_products', 'rental_component_id', 'master_product_id')
                    ->withPivot('is_preferred', 'is_active', 'created_by', 'updated_by')
                    ->withTimestamps();
    }

    public function preferredProducts()
    {
        return $this->belongsToMany(MasterProduct::class, 'rental_component_products', 'rental_component_id', 'master_product_id')
                    ->wherePivot('is_preferred', true)
                    ->wherePivot('is_active', true)
                    ->withPivot('is_preferred', 'is_active', 'created_by', 'updated_by')
                    ->withTimestamps();
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

    public function scopeActivationComponents($query)
    {
        return $query->where('is_activation_component', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('component_name');
    }

    // Helper methods
    public function getFormattedReplacementPriceAttribute()
    {
        return 'Rp ' . number_format($this->replacement_price, 0, ',', '.');
    }

    public function getTotalReplacementPriceAttribute()
    {
        return $this->quantity * $this->replacement_price;
    }

    public function getFormattedTotalReplacementPriceAttribute()
    {
        return 'Rp ' . number_format($this->total_replacement_price, 0, ',', '.');
    }

    public function hasAllowedProducts()
    {
        return $this->allowedProducts()->exists();
    }

    public function getPreferredProduct()
    {
        return $this->preferredProducts()->first();
    }

    public function addAllowedProduct(MasterProduct $product, $isPreferred = false)
    {
        return $this->allowedProducts()->attach($product->id, [
            'is_preferred' => $isPreferred,
            'is_active' => true,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
        ]);
    }

    public function removeAllowedProduct(MasterProduct $product)
    {
        return $this->allowedProducts()->detach($product->id);
    }

    public function setPreferredProduct(MasterProduct $product)
    {
        // Remove preferred status from all products
        $this->componentProducts()->update(['is_preferred' => false]);
        
        // Set new preferred product
        $this->componentProducts()
            ->where('master_product_id', $product->id)
            ->update(['is_preferred' => true, 'updated_by' => auth()->id()]);
    }
}