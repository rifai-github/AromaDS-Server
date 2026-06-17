<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Traits\HasComprehensiveAuditTrail;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail, AutoFilterable;

    protected $fillable = [
        'code',
        'name',
        'description',
        'parent_id',
        'sort_order',
        'icon',
        'color',
        'sku_prefix',
        'unit',
        'has_serial_number',
        'is_unit',
        'is_active',
        'created_by',
        'updated_by',
        'update_by_1',
        'update_at_1',
        'update_by_2',
        'update_at_2',
        'delete_by',
        'delete_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_serial_number' => 'boolean',
        'is_unit' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function productTypes()
    {
        return $this->hasMany(ProductType::class, 'product_category_id');
    }

    public function masterProducts()
    {
        return $this->hasMany(MasterProduct::class, 'product_category_id');
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

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeIsUnit($query)
    {
        return $query->where('is_unit', true);
    }

    public function scopeWithSerialNumber($query)
    {
        return $query->where('has_serial_number', true);
    }

    /**
     * Scope: hanya kategori yang memiliki data teknis (child/leaf categories)
     */
    public function scopeHasTechnicalData($query)
    {
        return $query->whereNotNull('sku_prefix');
    }

    // Helper methods
    public function getFullPathAttribute()
    {
        $path = collect([$this->name]);
        $parent = $this->parent;
        
        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }
        
        return $path->implode(' > ');
    }

    public function getLevelAttribute()
    {
        $level = 0;
        $parent = $this->parent;
        
        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }
        
        return $level;
    }

    public function getIndentedNameAttribute()
    {
        $indent = str_repeat('— ', $this->level);
        return $indent . $this->name;
    }

    public function requiresSerialNumber(): bool
    {
        if ((bool) $this->has_serial_number) {
            return true;
        }

        return self::hasMandatorySerialPolicy($this->code, $this->name);
    }

    public static function hasMandatorySerialPolicy(?string $code, ?string $name): bool
    {
        $normalizedCode = strtoupper(trim((string) $code));
        $normalizedName = strtolower(trim((string) $name));

        return in_array($normalizedCode, ['AROMA', 'DIS'], true)
            || in_array($normalizedName, ['aroma', 'dispenser'], true);
    }

    public function getEffectiveHasSerialNumberAttribute(): bool
    {
        if ($this->requiresSerialNumber()) {
            return true;
        }

        if (array_key_exists('serial_required_products_exists', $this->attributes)) {
            return (bool) $this->attributes['serial_required_products_exists'];
        }

        return $this->masterProducts()
            ->where(function ($query) {
                $query->whereHas('serialNumbers')
                    ->orWhereHas('productType', fn ($typeQuery) => $typeQuery->where('has_serial_number', true));
            })
            ->exists();
    }

    public function hasChildren()
    {
        return $this->children()->exists();
    }

    public function getAllDescendants()
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }
        
        return $descendants;
    }

    public function getAllAncestors()
    {
        $ancestors = collect();
        $parent = $this->parent;
        
        while ($parent) {
            $ancestors->prepend($parent);
            $parent = $parent->parent;
        }
        
        return $ancestors;
    }
}
