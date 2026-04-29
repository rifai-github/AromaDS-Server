<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;

class CrVariable extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $table = 'cr_variables';

    protected $fillable = [
        'name',
        'cr_days',
        'description',
        'is_active',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cr_days' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
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

    public function commissionCalculations()
    {
        return $this->hasMany(CommissionCalculation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Methods
    public static function getDefault()
    {
        return self::active()->default()->first();
    }

    public function setAsDefault()
    {
        // Unset other defaults
        self::where('is_default', true)->update(['is_default' => false]);
        
        // Set this as default
        $this->update(['is_default' => true]);
    }

    public function calculateDueDate($invoiceDate)
    {
        return \Carbon\Carbon::parse($invoiceDate)->addDays($this->cr_days);
    }
}
