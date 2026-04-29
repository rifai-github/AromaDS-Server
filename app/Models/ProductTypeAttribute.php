<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTypeAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type_id',
        'input_type',
        'label',
        'default_value',
        'is_required',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
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

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    // Business Logic Methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function isRequired()
    {
        return $this->is_required;
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

    public function getRequiredTextAttribute()
    {
        return $this->is_required ? 'Yes' : 'No';
    }
}
