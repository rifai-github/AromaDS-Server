<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuildingCustomer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'building_customer';

    protected $fillable = [
        'building_id',
        'customer_id',
        'floor_number',
        'tenant_number',
        'unit_number',
        'area_size',
        'notes',
        'is_active',
        'association_date',
        'created_by'
    ];

    protected $casts = [
        'area_size' => 'decimal:2',
        'is_active' => 'boolean',
        'association_date' => 'date'
    ];

    // Relationships
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBuilding($query, $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Accessors
    public function getFullLocationAttribute()
    {
        $parts = array_filter([
            $this->building->building_name ?? '',
            $this->floor_number ? "Floor {$this->floor_number}" : '',
            $this->tenant_number ? "Tenant {$this->tenant_number}" : '',
            $this->unit_number ? "Unit {$this->unit_number}" : ''
        ]);

        return implode(', ', $parts);
    }

    public function getFormattedAreaAttribute()
    {
        return $this->area_size ? number_format($this->area_size, 2) . ' sqm' : '-';
    }

    // Methods
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }
}
