<?php

namespace App\Models;

use App\Http\Traits\AutoFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class Branch extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail, AutoFilterable;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'address_type',
        'is_head_office',
        'address_1',
        'address_2',
        'phone_1',
        'phone_2',
        'fax',
        'email',
        'postal_code',
        'province_id',
        'city_id',
        'district_id',
        'subdistrict_id',
        'has_warehouse',
        'is_taxable',
        'invoice_authorized_by_user_id',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'has_warehouse' => 'boolean',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
        'is_head_office' => 'boolean'
    ];

    // Relationships
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

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function branchWarehouses()
    {
        return $this->hasMany(BranchWarehouse::class);
    }

    public function branchSettings()
    {
        return $this->hasMany(BranchSetting::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class, 'branch_office');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function invoiceAuthorizedByUser()
    {
        return $this->belongsTo(User::class, 'invoice_authorized_by_user_id');
    }

    public function bankPayments()
    {
        return $this->hasMany(BankPayment::class);
    }

    /**
     * Multi-Branch: Users assigned to this branch (many-to-many)
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'branch_user')
            ->withPivot('is_primary', 'created_by', 'updated_by')
            ->withTimestamps();
    }

    public function rentalPrices()
    {
        return $this->hasMany(RentalPrice::class);
    }

    public function jobSchedules()
    {
        return $this->hasMany(JobSchedule::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function salesActivities()
    {
        return $this->hasMany(SalesActivity::class);
    }

    public function operationalAreas()
    {
        return $this->hasMany(OperationalArea::class);
    }

    public function pics()
    {
        return $this->hasMany(BranchPic::class);
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

    public function scopeByBranchName($query, $branchName)
    {
        return $query->where('name', 'like', "%{$branchName}%");
    }

    public function scopeByBranchCode($query, $branchCode)
    {
        return $query->where('code', 'like', "%{$branchCode}%");
    }

    public function scopeByAddressType($query, $addressType)
    {
        return $query->where('address_type', $addressType);
    }

    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }

    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeWithWarehouse($query)
    {
        return $query->where('has_warehouse', true);
    }

    public function scopeWithoutWarehouse($query)
    {
        return $query->where('has_warehouse', false);
    }

    public function scopeTaxable($query)
    {
        return $query->where('is_taxable', true);
    }

    public function scopeNonTaxable($query)
    {
        return $query->where('is_taxable', false);
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where(function($q) use ($phone) {
            $q->where('phone_1', 'like', "%{$phone}%")
              ->orWhere('phone_2', 'like', "%{$phone}%");
        });
    }

    // Accessors
    public function getAddressTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->address_type));
    }

    public function getFormattedAddressAttribute()
    {
        $address = $this->address_1;
        if ($this->address_2) {
            $address .= ', ' . $this->address_2;
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
            $address .= ', ' . $this->province->province_name;
        }
        if ($this->postal_code) {
            $address .= ' ' . $this->postal_code;
        }
        return $address;
    }

    public function getFormattedPhoneAttribute()
    {
        $phones = [];
        if ($this->phone_1) {
            $phones[] = $this->phone_1;
        }
        if ($this->phone_2) {
            $phones[] = $this->phone_2;
        }
        return implode(' / ', $phones);
    }

    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getHasWarehouseTextAttribute()
    {
        return $this->has_warehouse ? 'Ya' : 'Tidak';
    }

    public function getIsTaxableTextAttribute()
    {
        return $this->is_taxable ? 'Ya' : 'Tidak';
    }

    public function getFullLocationAttribute()
    {
        $location = [];
        if ($this->city) {
            $location[] = $this->city->name;
        }
        if ($this->province) {
            $location[] = $this->province->province_name;
        }
        return implode(', ', $location);
    }

    // Mutators
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = strtoupper($value);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucwords($value);
    }

    public function setAddress1Attribute($value)
    {
        $this->attributes['address_1'] = ucwords($value);
    }

    public function setAddress2Attribute($value)
    {
        $this->attributes['address_2'] = $value ? ucwords($value) : null;
    }

    // Business Logic Methods
    public function getTotalUsers()
    {
        return $this->users()->count();
    }

    public function getActiveUsers()
    {
        return $this->users()->where('is_active', true)->count();
    }

    public function getTotalTeams()
    {
        return $this->teams()->count();
    }

    public function getActiveTeams()
    {
        return $this->teams()->where('is_active', true)->count();
    }

    public function getTotalWarehouses()
    {
        return $this->warehouses()->count();
    }

    public function getActiveWarehouses()
    {
        return $this->warehouses()->where('is_active', true)->count();
    }

    public function getTotalJobSchedules()
    {
        return $this->jobSchedules()->count();
    }

    public function getActiveJobSchedules()
    {
        return $this->jobSchedules()->where('status', '!=', 'cancelled')->count();
    }

    public function getTotalSalesActivities()
    {
        return $this->salesActivities()->count();
    }

    public function getCompletedSalesActivities()
    {
        return $this->salesActivities()->where('status', 'completed')->count();
    }

    public function getTotalBankPayments()
    {
        return $this->bankPayments()->count();
    }

    public function getActiveBankPayments()
    {
        return $this->bankPayments()->where('is_active', true)->count();
    }

    public function getPrimaryWarehouse()
    {
        return $this->branchWarehouses()->where('is_primary', true)->first();
    }

    public function getSetting($key, $default = null)
    {
        $setting = $this->branchSettings()->where('setting_key', $key)->where('is_active', true)->first();
        return $setting ? $setting->setting_value : $default;
    }

    public function setSetting($key, $value, $description = null)
    {
        $setting = $this->branchSettings()->where('setting_key', $key)->first();
        
        if ($setting) {
            $setting->setting_value = $value;
            if ($description) {
                $setting->description = $description;
            }
            $setting->save();
        } else {
            $this->branchSettings()->create([
                'setting_key' => $key,
                'setting_value' => $value,
                'description' => $description,
                'is_active' => true
            ]);
        }
    }

    // Validation Methods
    public function hasValidPhone()
    {
        return ($this->phone_1 && preg_match('/^[0-9+\-\s()]+$/', $this->phone_1)) ||
               ($this->phone_2 && preg_match('/^[0-9+\-\s()]+$/', $this->phone_2));
    }

    public function hasValidFax()
    {
        return !$this->fax || preg_match('/^[0-9+\-\s()]+$/', $this->fax);
    }

    public function hasValidPostalCode()
    {
        return !$this->postal_code || preg_match('/^[0-9]{5}$/', $this->postal_code);
    }

    public function hasCompleteAddress()
    {
        return $this->address_1 && $this->province_id && $this->city_id;
    }

    // Status Methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function isInactive()
    {
        return !$this->is_active;
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

    public function hasWarehouse()
    {
        return $this->has_warehouse;
    }

    public function isTaxable()
    {
        return $this->is_taxable;
    }

    // Static Methods
    public static function getAddressTypes()
    {
        return [
            'office' => 'Office',
            'warehouse' => 'Warehouse',
            'both' => 'Both Office & Warehouse'
        ];
    }

    public static function generateBranchCode($name)
    {
        $words = explode(' ', strtoupper($name));
        $code = '';
        
        foreach ($words as $word) {
            if (strlen($word) >= 2) {
                $code .= substr($word, 0, 2);
            } else {
                $code .= $word;
            }
        }
        
        // Ensure code is unique
        $originalCode = $code;
        $counter = 1;
        
        while (self::where('code', $code)->exists()) {
            $code = $originalCode . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }
        
        return $code;
    }
}
