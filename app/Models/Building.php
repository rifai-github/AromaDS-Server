<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class Building extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'building_type',
        // 'customer_id', // Removed: Building tidak relasi langsung ke Customer (many-to-many via building_customers)
        'nama_gedung',
        'name',
        'kode_pos',
        'postal_code',
        'alamat_1',
        'address',
        'alamat_2',
        'province_id',
        'city_id',
        'branch_id',
        'district_id',
        'subdistrict_id',
        'total_floors',
        'total_area',
        'phone_1',
        'phone_2',
        'fax',
        'email',
        'description',
        'notes',
        'status_update',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'status_update' => 'boolean'
    ];

    // Relationships
    // Many-to-many relationship with customers (via building_customers pivot table)
    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'building_customers')
            ->withPivot('unit_number', 'floor', 'notes', 'is_active')
            ->withTimestamps();
    }

    public function activeCustomers()
    {
        return $this->belongsToMany(Customer::class, 'building_customers')
            ->wherePivot('is_active', true)
            ->withPivot('unit_number', 'floor', 'notes')
            ->withTimestamps();
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function jobSchedules()
    {
        return $this->hasMany(JobSchedule::class);
    }

    public function roomRentalUnits()
    {
        return $this->hasMany(RoomRentalUnit::class);
    }

    public function masterRooms()
    {
        return $this->hasMany(MasterRoom::class);
    }

    public function unitOnWalls()
    {
        return $this->hasMany(UnitOnWall::class);
    }

    public function contractRooms()
    {
        return $this->hasMany(ContractRoom::class);
    }

    public function contractRentals()
    {
        return $this->hasMany(ContractRental::class);
    }

    public function invoiceRentals()
    {
        return $this->hasMany(InvoiceRental::class);
    }

    // Note: Surveys are connected through prospects, not directly to buildings
    // If you need to get surveys for a building, you should query through prospects

    public function contractBuildings()
    {
        return $this->hasMany(ContractBuilding::class);
    }
    
    // Note: Contracts are connected through contract_buildings table, not directly
    // If you need to get contracts for a building, you should query through contract_buildings

    public function rooms()
    {
        return $this->hasMany(MasterRoom::class);
    }

    public function floors()
    {
        return $this->hasMany(Floor::class);
    }

    // Building Multi-User Enhancement relationships
    public function buildingUsers()
    {
        return $this->hasMany(BuildingUser::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'building_users')
            ->withPivot('role', 'is_primary', 'is_active', 'assigned_at', 'unassigned_at', 'notes', 'assigned_by', 'unassigned_by')
            ->withTimestamps();
    }

    public function activeUsers()
    {
        return $this->belongsToMany(User::class, 'building_users')
            ->wherePivot('is_active', true)
            ->withPivot('role', 'is_primary', 'assigned_at', 'notes', 'assigned_by')
            ->withTimestamps();
    }

    public function primaryUser()
    {
        return $this->belongsToMany(User::class, 'building_users')
            ->wherePivot('is_primary', true)
            ->wherePivot('is_active', true)
            ->withPivot('role', 'assigned_at', 'notes', 'assigned_by')
            ->withTimestamps();
    }

    // Building Marketing Enhancement relationships
    public function marketingPipelines()
    {
        return $this->belongsToMany(MarketingPipeline::class, 'marketing_pipeline_buildings')
            ->withPivot('assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    public function usersByRole($role)
    {
        return $this->belongsToMany(User::class, 'building_users')
            ->wherePivot('role', $role)
            ->wherePivot('is_active', true)
            ->withPivot('is_primary', 'assigned_at', 'notes', 'assigned_by')
            ->withTimestamps();
    }

    // Accessors
    public function getBuildingNameAttribute()
    {
        return $this->name ?: $this->nama_gedung;
    }

    public function getBuildingAddressAttribute()
    {
        return $this->address ?: $this->alamat_1;
    }

    public function getBuildingPostalCodeAttribute()
    {
        return $this->postal_code ?: $this->kode_pos;
    }

    // Removed: getCustomerNameAttribute() - Building tidak punya customer relationship lagi
    // Use: $building->customers->pluck('name')->implode(', ') untuk list customer names

    public function getStatusTextAttribute()
    {
        return $this->status_update ? 'Aktif' : 'Nonaktif';
    }

    // Scopes
    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }

    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeByDistrict($query, $districtId)
    {
        return $query->where('district_id', $districtId);
    }

    public function scopeBySubdistrict($query, $subdistrictId)
    {
        return $query->where('subdistrict_id', $subdistrictId);
    }

    // Removed: scopeByCustomer - Building tidak punya customer_id lagi (many-to-many relationship)
    // Use: Building::whereHas('customers', function($q) use ($customerId) { $q->where('customers.id', $customerId); })

    public function scopeActive($query)
    {
        return $query->where('status_update', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('status_update', false);
    }

    public function scopeByPostalCode($query, $postalCode)
    {
        return $query->where('kode_pos', $postalCode);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('nama_gedung', 'like', "%{$name}%");
    }

    // Additional Accessors
    public function getFullAddressAttribute()
    {
        $address = $this->alamat_1;
        if ($this->alamat_2) {
            $address .= ', ' . $this->alamat_2;
        }
        if ($this->subdistrict) {
            $address .= ', ' . $this->subdistrict->name;
        }
        if ($this->district) {
            $address .= ', ' . $this->district->name;
        }
        if ($this->city) {
            $address .= ', ' . $this->city->name;
        }
        if ($this->province) {
            $address .= ', ' . $this->province->name;
        }
        if ($this->kode_pos) {
            $address .= ' ' . $this->kode_pos;
        }
        return $address;
    }

    public function getIsActiveAttribute()
    {
        return $this->status_update;
    }

    // Building Multi-User Enhancement helper methods
    public function assignUser($userId, $role = 'user', $isPrimary = false, $notes = null)
    {
        return BuildingUser::assignUserToBuilding($this->id, $userId, $role, $isPrimary, $notes);
    }

    public function removeUser($userId)
    {
        $buildingUser = BuildingUser::where('building_id', $this->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if ($buildingUser) {
            $buildingUser->deactivate();
            return true;
        }

        return false;
    }

    public function setPrimaryUser($userId)
    {
        $buildingUser = BuildingUser::where('building_id', $this->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if ($buildingUser) {
            $buildingUser->setAsPrimary();
            return true;
        }

        return false;
    }

    public function getActiveUsers()
    {
        return BuildingUser::getBuildingUsers($this->id, true);
    }

    public function getPrimaryUser()
    {
        return BuildingUser::getPrimaryUser($this->id);
    }

    public function getUserCount()
    {
        return BuildingUser::getBuildingUserCount($this->id, true);
    }

    public function getUsersByRole($role)
    {
        return BuildingUser::getUsersByRole($this->id, $role, true);
    }

    public function hasUser($userId)
    {
        return BuildingUser::where('building_id', $this->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->exists();
    }

    public function getUserRole($userId)
    {
        $buildingUser = BuildingUser::where('building_id', $this->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        return $buildingUser ? $buildingUser->role : null;
    }

    public function isUserPrimary($userId)
    {
        return BuildingUser::where('building_id', $this->id)
            ->where('user_id', $userId)
            ->where('is_primary', true)
            ->where('is_active', true)
            ->exists();
    }
}
