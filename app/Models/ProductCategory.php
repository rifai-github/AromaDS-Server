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

    /**
     * Unit yang wajib dipakai Master Product di category ini, atau null kalau
     * category belum punya unit (produk boleh mengisi unit sendiri).
     *
     * Aturan klien 26 Nov 2026: unit Master Product mengikuti Product Category
     * dan hanya bisa diubah lewat category.
     */
    public static function enforcedUnitFor($categoryId): ?string
    {
        if (empty($categoryId) || !is_numeric($categoryId)) {
            return null;
        }

        $unit = trim((string) static::query()->whereKey($categoryId)->value('unit'));

        return $unit !== '' ? $unit : null;
    }

    /**
     * Samakan unit semua Master Product (termasuk yang soft-deleted) di category
     * ini dengan unit category. Hanya teks unit yang diganti; qty stok tidak
     * dikonversi. Mengembalikan jumlah produk yang berubah.
     */
    public function cascadeUnitToProducts(): int
    {
        $unit = trim((string) $this->unit);
        if ($unit === '') {
            return 0;
        }

        // Bandingkan di PHP (case-sensitive): collation MySQL menganggap 'PCS' = 'pcs'.
        $ids = MasterProduct::withTrashed()
            ->where('product_category_id', $this->id)
            ->get(['id', 'unit'])
            ->filter(fn ($product) => (string) $product->unit !== $unit)
            ->pluck('id');

        $values = ['unit' => $unit, 'updated_at' => now()];
        if (auth()->id()) {
            $values['updated_by'] = auth()->id();
        }

        foreach ($ids->chunk(500) as $chunk) {
            MasterProduct::withTrashed()
                ->whereIn('id', $chunk->all())
                ->update($values);
        }

        return $ids->count();
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

    /**
     * Categories that can be selected as a master-rental detail.
     *
     * Child categories remain selectable as before. A root category is also
     * selectable when products are assigned directly to it, because in that
     * case it acts as both a hierarchy parent and an operational category.
     */
    public function scopeAvailableForRentalDetail($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($categoryQuery) {
                $categoryQuery
                    ->whereNotNull('parent_id')
                    ->orWhereHas('masterProducts', fn ($productQuery) => $productQuery->where('is_active', true));
            });
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

        // Klien 23 Nov 2026: produk yang sekadar punya data SN tidak lagi dianggap wajib SN,
        // jadi hanya Product Type ber-flag SN yang ikut membuat kategori tampil "Yes".
        return $this->masterProducts()
            ->whereHas('productType', fn ($typeQuery) => $typeQuery->where('has_serial_number', true))
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
