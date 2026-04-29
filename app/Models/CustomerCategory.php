<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class CustomerCategory extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean'
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

    public function customers()
    {
        return $this->hasMany(Customer::class, 'customer_category_id');
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

    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    // Accessors
    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getCustomersCountAttribute()
    {
        return $this->customers()->count();
    }

    public function getActiveCustomersCountAttribute()
    {
        return $this->customers()->where('is_active', true)->count();
    }

    // Mutators
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords($value);
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

    public function canBeDeleted()
    {
        return $this->customers()->count() === 0;
    }
}
