<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class ProductType extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'product_category_id', // Untuk struktur hierarki Category → Type → Product
        'name',
        'description',
        'unit',
        'sku_prefix',
        'has_serial_number',
        'is_unit',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'has_serial_number' => 'boolean',
        'is_unit' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function masterProducts()
    {
        return $this->hasMany(MasterProduct::class);
    }

    public function productTypeAttributes()
    {
        return $this->hasMany(ProductTypeAttribute::class);
    }

    public function rentalDetails()
    {
        return $this->hasMany(RentalDetail::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithSerialNumber($query)
    {
        return $query->where('has_serial_number', true);
    }

    public function scopeIsUnit($query)
    {
        return $query->where('is_unit', true);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    public function getHasSerialNumberTextAttribute()
    {
        return $this->has_serial_number ? 'Yes' : 'No';
    }

    public function getIsUnitTextAttribute()
    {
        return $this->is_unit ? 'Yes' : 'No';
    }
}
