<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class CustomerTax extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $table = 'customer_tax_settings';

    protected $fillable = [
        'customer_id',
        'label',
        'tax_number',
        'nitku',
        'tax_name',
        'tax_address',
        'tax_type',
        'ppn_code',
        'tax_rate',
        'effective_date',
        'expiry_date',
        'description',
        'status',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tax_rate' => 'decimal:2',
        'effective_date' => 'date',
        'expiry_date' => 'date'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
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

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // Accessors
    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
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
}
