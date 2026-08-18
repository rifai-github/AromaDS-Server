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

    /**
     * Master options that have their own dedicated management screen elsewhere
     * in the app. They still live in this table (other code reads them via
     * MasterOption::firstOrCreate/where('name', ...)), but editing them through
     * the generic Master Options CRUD is unsafe: that screen only knows about
     * option_name/label/code and has no fields for the structured data
     * (e.g. Term of Payment's billing_mode/months/payment_count JSON) the
     * dedicated screen manages, so a generic edit can desync label vs behavior.
     * Hide them from the generic list/edit/update routes and point users to
     * the dedicated route name instead.
     */
    public const MANAGED_ELSEWHERE = [
        'Term of Payment' => 'system.master-term-of-payments.index',
    ];

    /**
     * Names looked up directly via MasterOption::where('name', ...) elsewhere in the app
     * (dropdowns in wizards, product/rental forms, employee profile fields, etc). Deleting
     * one of these would silently break whatever reads it, so deletion is blocked for these
     * regardless of the system_reserved flag, which is manually set and can be wrong.
     */
    public const PROTECTED_NAMES = [
        'Product Units',
        'Brand Lines',
        'Product Variants',
        'Position',
        'Salutation',
        'Gender',
        'Marital Status',
        'Religion',
        'Identity Type',
        'Blood Type',
        'Rhesus',
        'Employee Status',
        'Floor',
        'Scent Intensity',
        'Installation Type',
        'Customer Classification',
        'Price Category',
        'Contract Status',
        'Contract Type',
        'Payment Terms',
        'Contract File Type',
        'Termination Reason',
        'Room Type',
        'rental_alias',
        'Term of Payment',
        'Billing Method',
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

    public function getIsInUseAttribute(): bool
    {
        return in_array($this->name, self::PROTECTED_NAMES, true);
    }


}
