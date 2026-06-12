<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'master_rental_id',
        'item_type', // 'product_category', 'product', or 'product_type' (legacy)
        'item_id', // polymorphic ID
        'product_type_id', // Legacy - kept for backward compatibility
        'product_category_id',
        'master_product_id',
        'service_frequency_multiplier',
        'quantity',
        'bom_rental_qty',
        'auto_expand', // Auto expand product_category to products
        'unit',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'service_frequency_multiplier' => 'integer',
        'quantity' => 'integer',
    ];

    // Relationships
    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function masterProduct()
    {
        return $this->belongsTo(MasterProduct::class);
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
    public function scopeByProductType($query, $productTypeId)
    {
        return $query->where('product_type_id', $productTypeId);
    }

    public function scopeByProductCategory($query, $productCategoryId)
    {
        return $query->where('product_category_id', $productCategoryId);
    }

    public function scopeByMasterProduct($query, $masterProductId)
    {
        return $query->where('master_product_id', $masterProductId);
    }

    // Many-to-Many relationship for allowed products (Material List)
    public function allowedProducts()
    {
        return $this->belongsToMany(
            MasterProduct::class,
            'rental_detail_materials',
            'rental_detail_id',
            'master_product_id'
        )
        ->withPivot(['is_selected', 'sort_order'])
        ->withTimestamps()
        ->orderBy('rental_detail_materials.sort_order');
    }

    // Get only selected products from material list
    public function selectedProducts()
    {
        return $this->allowedProducts()->wherePivot('is_selected', true);
    }

    // Polymorphic relationship for item (ProductCategory/ProductType or MasterProduct)
    public function item()
    {
        if ($this->item_type === 'product_category') {
            return $this->belongsTo(ProductCategory::class, 'item_id');
        }
        if ($this->item_type === 'product_type') {
            return $this->belongsTo(ProductType::class, 'item_id');
        }
        return $this->belongsTo(MasterProduct::class, 'item_id');
    }

    // Get the polymorphic item instance
    public function getItemInstance()
    {
        if ($this->item_type === 'product_category') {
            return ProductCategory::find($this->item_id);
        }
        if ($this->item_type === 'product_type') {
            return ProductType::find($this->item_id);
        }
        return MasterProduct::find($this->item_id);
    }

    // Scope for product type items
    public function scopeProductTypeItems($query)
    {
        return $query->where('item_type', 'product_type');
    }

    // Scope for product items
    public function scopeProductItems($query)
    {
        return $query->where('item_type', 'product');
    }

    // Scope for auto-expandable items
    public function scopeAutoExpandable($query)
    {
        return $query->where('auto_expand', true);
    }
}
