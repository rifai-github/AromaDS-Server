<?php

namespace App\Models;

use App\Http\Traits\AutoFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\HasComprehensiveAuditTrail;

class OptionDetail extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable, HasComprehensiveAuditTrail;

    protected $fillable = [
        'master_option_id',
        'parent_option_id', // For Brand Line -> Variant relationship
        'option_name',
        'option_description',
        'label',
        'code',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function masterOption()
    {
        return $this->belongsTo(MasterOption::class);
    }

    /**
     * Parent option (e.g., Brand Line for a Variant)
     */
    public function parentOption()
    {
        return $this->belongsTo(OptionDetail::class, 'parent_option_id');
    }

    /**
     * Child options (e.g., Variants under a Brand Line)
     */
    public function childOptions()
    {
        return $this->hasMany(OptionDetail::class, 'parent_option_id');
    }

    // Scopes
    public function scopeByMasterOption($query, $masterOptionId)
    {
        return $query->where('master_option_id', $masterOptionId);
    }

    /**
     * Scope to get options by parent (Brand Line)
     */
    public function scopeByParent($query, $parentOptionId)
    {
        return $query->where('parent_option_id', $parentOptionId);
    }

    /**
     * Scope to get root options (no parent)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_option_id');
    }

    /**
     * Check if this option has children
     */
    public function hasChildren()
    {
        return $this->childOptions()->count() > 0;
    }

    /**
     * Get full hierarchy path (e.g., "Signature > Lemon Squash")
     */
    public function getHierarchyPathAttribute()
    {
        if ($this->parentOption) {
            return $this->parentOption->option_name . ' > ' . $this->option_name;
        }
        return $this->option_name;
    }
}
