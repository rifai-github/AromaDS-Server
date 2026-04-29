<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'npwp',  // Nomor Pokok Wajib Pajak (MOM6 requirement)
        'nik',   // Nomor Induk Kependudukan (MOM6 requirement)
        'nitku', // Nomor Identitas Tempat Kegiatan Usaha (MOM6 requirement)
        'label_alias',
        'status',
        'company_type',
        'tax_code',
        'nib_number',
        'is_pkp',
        'is_active',
        'grace_period_days',
        'default_payment',
        'member_since',
        'balance',
        'email',
        'phone',
        'website',
        'address',
        'province_id',
        'city_id',
        'district_id',
        'subdistrict_id',
        'postal_code',
        'industry',
        'employee_count',
        'annual_revenue',
        'description',
        'footer_line_1',
        'footer_line_2',
        'footer_line_3',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_pkp' => 'boolean',
        'is_active' => 'boolean',
        'member_since' => 'date',
        'balance' => 'decimal:2',
        'employee_count' => 'integer',
        'annual_revenue' => 'decimal:2'
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

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }


    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function companyTagAssignments()
    {
        return $this->hasMany(CompanyTagAssignment::class);
    }

    public function settings()
    {
        return $this->hasOne(CompanySetting::class);
    }

    public function documents()
    {
        return $this->hasMany(CompanyDocument::class);
    }

    public function notes()
    {
        return $this->hasMany(CompanyNote::class);
    }

    public function relationships()
    {
        return $this->hasMany(CompanyRelationship::class);
    }

    public function activities()
    {
        return $this->hasMany(CompanyActivity::class);
    }

    public function communications()
    {
        return $this->hasMany(CompanyCommunication::class);
    }

    // taxSettings relationship removed as CompanyTax model doesn't exist

    // companyAddresses and companyContacts relationships removed as models don't exist

    public function virtualAccounts()
    {
        return $this->hasMany(VirtualAccount::class);
    }

    public function jobSchedules()
    {
        return $this->hasMany(JobSchedule::class);
    }


    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('company_type', $type);
    }

    public function scopePKP($query)
    {
        return $query->where('is_pkp', true);
    }

    public function scopeNonPKP($query)
    {
        return $query->where('is_pkp', false);
    }

    public function scopeByCompanyName($query, $companyName)
    {
        return $query->where('name', 'like', "%{$companyName}%");
    }

    public function scopeByTaxCode($query, $taxCode)
    {
        return $query->where('tax_code', $taxCode);
    }

    public function scopeByNibNumber($query, $nibNumber)
    {
        return $query->where('nib_number', $nibNumber);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->company_type));
    }

    public function getFormattedMemberSinceAttribute()
    {
        return $this->member_since ? $this->member_since->format('d/m/Y') : '-';
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 2);
    }

    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getIsPKPTextAttribute()
    {
        return $this->is_pkp ? 'Ya' : 'Tidak';
    }
}
