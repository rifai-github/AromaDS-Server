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
        'contract_id',
        'contract_room_id',
        'install_job_schedule_id',
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

    /**
     * Contract the unit was physically installed under.
     * Populated at install time - do not infer the contract from customer+room,
     * one room can hold units from several different contracts.
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function contractRoom()
    {
        return $this->belongsTo(ContractRoom::class, 'contract_room_id');
    }

    public function installJobSchedule()
    {
        return $this->belongsTo(JobSchedule::class, 'install_job_schedule_id');
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

    /**
     * Narrow a Unit On Wall query to units that belong to the given contract(s).
     *
     * customer + building + room + rental is NOT enough to identify a contract's units: the
     * same customer can rent the same rental into the same room under several contracts at
     * once, and every one of those units matches all four filters. QA room 460 held 8 active
     * units across contracts SBY-CA/26-08/0007..0010, all on rental_id 4 - which is how a
     * Remove job for a 2-unit contract listed 8 serial numbers (fixed in 8ce6d11) and how a
     * Lost Unit Report can retire units belonging to someone else's contract.
     *
     * The probe is deliberate. Rows predating the unit_on_walls.contract_id backfill
     * (5fc9b84), and units whose room was moved to another contract by Contract Switching,
     * carry no matching contract_id. Tightening unconditionally would make those units
     * unreachable to the very processes that exist to take them off the wall, so when
     * nothing in the candidate set carries a matching contract the query is left untouched.
     */
    public function scopeScopedToContracts($query, array $contractIds)
    {
        $contractIds = collect($contractIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($contractIds)) {
            return $query;
        }

        if (! (clone $query)->whereIn('contract_id', $contractIds)->exists()) {
            return $query;
        }

        return $query->whereIn('contract_id', $contractIds);
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
        if ($this->building) $location[] = $this->building->building_name;
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
