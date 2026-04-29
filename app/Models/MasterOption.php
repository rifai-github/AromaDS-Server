<?php

namespace App\Models;

use App\Http\Traits\AutoFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class MasterOption extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable, HasComprehensiveAuditTrail;

    protected $fillable = [
        'name',
        'description',
        'system_reserved',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'system_reserved' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function optionDetails()
    {
        return $this->hasMany(OptionDetail::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'position_id');
    }

    public function priceCategories()
    {
        return $this->hasMany(User::class, 'price_category_id');
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

    public function scopeSystemReserved($query)
    {
        return $query->where('system_reserved', true);
    }

    // Accessors
    public function getIsSystemAttribute()
    {
        return $this->system_reserved;
    }


}
