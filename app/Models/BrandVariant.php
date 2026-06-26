<?php

namespace App\Models;

use App\Traits\HasComprehensiveAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandVariant extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $table = 'product_brand_variants';

    protected $fillable = [
        'brand_line_id',
        'name',
        'description',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function brandLine()
    {
        return $this->belongsTo(OptionDetail::class, 'brand_line_id');
    }

    public function products()
    {
        return $this->hasMany(MasterProduct::class, 'brand_variant_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
