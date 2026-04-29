<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class UnitOnWall extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'customer_id',
        'building_id',
        'room_id',
        'rental_id',
        'product_id',
        'serial_number_id',
        'serial_number',
        'install_date',
        'last_service_date',
        'status',
        'notes',
        'temperature',
        'last_seen_at',
        'warranty_expires_at',
        'company_name', // Add company_name to fillable
        'room_name', // Add room_name to fillable
        'rental_name', // Add rental_name to fillable
        'product_name', // Add product_name to fillable
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'install_date' => 'date',
        'last_service_date' => 'date',
        'last_seen_at' => 'datetime',
        'warranty_expires_at' => 'date',
        'temperature' => 'decimal:2'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function rental()
    {
        return $this->belongsTo(MasterRental::class, 'rental_id');
    }

    public function product()
    {
        return $this->belongsTo(MasterProduct::class, 'product_id');
    }

    public function serialNumber()
    {
        return $this->belongsTo(SerialNumber::class, 'serial_number_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function roomRentalUnit()
    {
        return $this->belongsTo(RoomRentalUnit::class);
    }

    public function histories()
    {
        return $this->hasMany(UnitOnWallHistory::class)->orderBy('action_date', 'desc')->orderBy('created_at', 'desc');
    }

    public function installHistories()
    {
        return $this->histories()->whereIn('action', ['install', 'remove']);
    }

    public function serviceHistories()
    {
        return $this->histories()->where('action', 'service');
    }

    public function repairHistories()
    {
        return $this->histories()->where('action', 'repair');
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByBuilding($query, $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    public function scopeByRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeByRental($query, $rentalId)
    {
        return $query->where('rental_id', $rentalId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeBySerialNumber($query, $serialNumber)
    {
        return $query->where('serial_number', 'like', "%{$serialNumber}%");
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByInstallDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('install_date', [$startDate, $endDate]);
    }

    public function scopeByLastServiceRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('last_service_date', [$startDate, $endDate]);
    }

    public function scopeInstalled($query)
    {
        return $query->whereNotNull('install_date');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByTemperatureRange($query, $minTemp, $maxTemp)
    {
        return $query->whereBetween('temperature', [$minTemp, $maxTemp]);
    }

    // Accessors
    public function getFormattedInstallDateAttribute()
    {
        return $this->install_date ? $this->install_date->format('d/011/Y') : '-';
    }

    public function getFormattedLastServiceDateAttribute()
    {
        return $this->last_service_date ? $this->last_service_date->format('d/011/Y') : '-';
    }

    public function getFormattedWarrantyExpiresAtAttribute()
    {
        return $this->warranty_expires_at ? $this->warranty_expires_at->format('d/011/Y') : '-';
    }

    public function getIsInstalledAttribute()
    {
        return !is_null($this->install_date);
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'maintenance' => 'Under Maintenance',
            'removed' => 'Removed',
            default => 'Unknown'
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'active' => 'status-active',
            'inactive' => 'status-inactive',
            'maintenance' => 'status-warning',
            'removed' => 'status-danger',
            default => 'status-inactive'
        };
    }

    public function getFullLocationAttribute()
    {
        $location = [];
        if ($this->building) $location[] = $this->building->nama_gedung;
        if ($this->room) $location[] = $this->room->room_name;
        return implode(' - ', $location) ?: '-';
    }

    public function getCustomerNameAttribute()
    {
        return $this->customer ? $this->customer->name : '-';
    }

    public function getProductNameAttribute()
    {
        return $this->product ? $this->product->name : '-';
    }

    public function getRentalNameAttribute()
    {
        return $this->rental ? $this->rental->rental_name : '-';
    }

    public function getRoomNameAttribute()
    {
        return $this->room ? $this->room->room_name : '-';
    }
}
