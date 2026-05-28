<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class MasterProduct extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail, \App\Http\Traits\AutoFilterable;
    
    protected $table = 'master_products';

    protected $fillable = [
        'product_type_id',
        'product_category_id',
        'name',
        'variant_name',
        'dimensions', // Product dimensions (P x L x T)
        'product_photos', // Array of product photo URLs
        'product_photo', // Single product photo URL
        'packaging_size',
        'packaging_size_id',
        'brand_line',
        'sku',
        'part_no', // Part number from CSV (PartNo)
        'universal_code_type', // Universal code type (barcode type)
        'universal_code', // Universal code value (barcode)
        'description',
        'description_2', // Additional description field
        'unit',
        'unit_order', // Unit for ordering from CSV (UnitOrder)
        'net_weight', // Net weight from CSV (NetWeight)
        'gross_weight', // Gross weight from CSV (GrossWeight)
        'lifetime', // Product lifetime from CSV (LifeTime)
        'frequency_service', // Service frequency from CSV (FrequencyService)
        'minimum_stock',
        'maximum_stock',
        'unit_price',
        'last_unit_price', // Last unit price
        'bom_quantity', // BOM quantity
        'is_active',
        'is_trading', // Trading flag from CSV (FgTrading)
        'is_stock_substitute', // Stock substitute flag from CSV (FgStockSubstitute)
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'last_unit_price' => 'decimal:2',
        'bom_quantity' => 'decimal:2',
        'net_weight' => 'decimal:3',
        'gross_weight' => 'decimal:3',
        'lifetime' => 'integer',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer',
        'is_active' => 'boolean',
        'is_trading' => 'boolean',
        'is_stock_substitute' => 'boolean',
        'product_photos' => 'array', // Cast JSON to array
    ];

    // Relationships
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'product_suppliers', 'product_id', 'supplier_id')
                    ->withPivot('supplier_product_code', 'supplier_price', 'currency', 'lead_time_days', 'is_preferred', 'is_active', 'notes')
                    ->withTimestamps();
    }

    public function warehouseProducts()
    {
        return $this->hasMany(WarehouseProduct::class);
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_products', 'master_product_id', 'warehouse_id')
                    ->withPivot('quantity', 'minimum_stock', 'maximum_stock')
                    ->withTimestamps();
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class);
    }

    public function serialNumbers()
    {
        return $this->hasMany(SerialNumber::class);
    }

    public function unitOnWalls()
    {
        return $this->hasMany(UnitOnWall::class);
    }

    public function rentalDetails()
    {
        return $this->hasMany(RentalDetail::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function packagingSize()
    {
        return $this->belongsTo(PackagingSize::class);
    }

    public function photos()
    {
        return $this->hasMany(ProductPhoto::class);
    }

    public function primaryPhoto()
    {
        return $this->hasOne(ProductPhoto::class)->where('is_primary', true);
    }

    public function activePhotos()
    {
        return $this->hasMany(ProductPhoto::class)->where('is_active', true)->orderBy('sort_order');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProductType($query, $productTypeId)
    {
        return $query->where('product_type_id', $productTypeId);
    }

    public function scopeByProductCategory($query, $productCategoryId)
    {
        return $query->where('product_category_id', $productCategoryId);
    }

    /**
     * Helper: Cek is_unit dari Category terlebih dahulu, fallback ke ProductType
     */
    public function getIsUnitFromCategoryAttribute()
    {
        if ($this->productCategory && $this->productCategory->is_unit !== null) {
            return $this->productCategory->is_unit;
        }
        return $this->productType ? $this->productType->is_unit : false;
    }

    public function requiresSerialNumber(): bool
    {
        return (bool) (
            optional($this->productCategory)->has_serial_number
            || optional($this->productType)->has_serial_number
        );
    }

    public function requiresUniqueSerialNumber(): bool
    {
        return $this->requiresSerialNumber() && (bool) (
            optional($this->productCategory)->is_unit
            || optional($this->productType)->is_unit
        );
    }

    public function getRequiresSerialNumberAttribute(): bool
    {
        return $this->requiresSerialNumber();
    }

    public function scopeBySku($query, $sku)
    {
        return $query->where('sku', 'like', "%{$sku}%");
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
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

    public function getUnitPriceFormattedAttribute()
    {
        return $this->unit_price ? 'Rp ' . number_format($this->unit_price, 2, ',', '.') : '-';
    }

    public function getTotalStockAttribute()
    {
        return $this->warehouseProducts()->sum('quantity');
    }

    public function getFormattedTotalStockAttribute()
    {
        return number_format($this->total_stock, 0, ',', '.');
    }
}
