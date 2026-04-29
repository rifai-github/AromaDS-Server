<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class RoomRentalUnit extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'customer',
        'gedung',
        'nama_ruangan',
        'rental',
        'reference_no',
        'expected_install_date',
        'install_date',
        'remove_date',
        'last_service_date',
        'remarks',
        'customer_id',
        'building_id',
        'master_room_id',
        'master_rental_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'expected_install_date' => 'date',
        'install_date' => 'date',
        'remove_date' => 'date',
        'last_service_date' => 'date'
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

    public function masterRoom()
    {
        return $this->belongsTo(MasterRoom::class);
    }

    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function jobSchedules()
    {
        return $this->hasMany(JobSchedule::class);
    }

    public function unitOnWalls()
    {
        return $this->hasMany(UnitOnWall::class);
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

    public function scopeByMasterRoom($query, $masterRoomId)
    {
        return $query->where('master_room_id', $masterRoomId);
    }

    public function scopeByMasterRental($query, $masterRentalId)
    {
        return $query->where('master_rental_id', $masterRentalId);
    }

    public function scopeByReferenceNo($query, $referenceNo)
    {
        return $query->where('reference_no', 'like', "%{$referenceNo}%");
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expected_install_date', [$startDate, $endDate]);
    }

    public function scopeByInstallDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('install_date', [$startDate, $endDate]);
    }

    public function scopeByRemoveDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('remove_date', [$startDate, $endDate]);
    }

    public function scopeInstalled($query)
    {
        return $query->whereNotNull('install_date');
    }

    public function scopeNotInstalled($query)
    {
        return $query->whereNull('install_date');
    }

    public function scopeRemoved($query)
    {
        return $query->whereNotNull('remove_date');
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('install_date')->whereNull('remove_date');
    }

    // Accessors
    public function getFormattedExpectedInstallDateAttribute()
    {
        return $this->expected_install_date ? $this->expected_install_date->format('d/m/Y') : '-';
    }

    public function getFormattedInstallDateAttribute()
    {
        return $this->install_date ? $this->install_date->format('d/m/Y') : '-';
    }

    public function getFormattedRemoveDateAttribute()
    {
        return $this->remove_date ? $this->remove_date->format('d/m/Y') : '-';
    }

    public function getFormattedLastServiceDateAttribute()
    {
        return $this->last_service_date ? $this->last_service_date->format('d/m/Y') : '-';
    }

    public function getIsInstalledAttribute()
    {
        return !is_null($this->install_date);
    }

    public function getIsRemovedAttribute()
    {
        return !is_null($this->remove_date);
    }

    public function getIsActiveAttribute()
    {
        return $this->is_installed && !$this->is_removed;
    }

    public function getFullNameAttribute()
    {
        return $this->customer . ' - ' . $this->gedung . ' - ' . $this->nama_ruangan . ' - ' . $this->rental;
    }
}
