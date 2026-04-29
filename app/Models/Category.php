<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_code',
        'category_name',
        'description',
        'parent_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

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

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->category_name} ({$this->category_code})";
    }

    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getIsParentAttribute()
    {
        return is_null($this->parent_id);
    }

    public function getIsChildAttribute()
    {
        return !is_null($this->parent_id);
    }

    // Methods
    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }

    public function getAllChildren()
    {
        return $this->children()->with('children');
    }

    public function getFullPathAttribute()
    {
        $path = [$this->category_name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->category_name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }
}
