<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;
use App\Http\Traits\AutoFilterable;

class Customer extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail, AutoFilterable;

    protected $fillable = [
        'customer_code',
        'name',
        'label_alias',
        'status',
        'customer_type',
        'company_type',
        'tax_code',
        'ppn_code',  // Kode Transaksi PPn (01, 02, 03, etc.)
        'customer_group', // Group name (Sinarmas, Unilever, etc.)
        'npwp',  // Nomor Pokok Wajib Pajak (MOM6 requirement)
        'npwp_address', // Alamat Pajak NPWP
        'npwp_registration_date', // Tanggal Registrasi NPWP
        'nik',   // Nomor Induk Kependudukan (MOM6 requirement)
        'nitku', // Nomor Identitas Tempat Kegiatan Usaha (MOM6 requirement)
        'nib',   // Nomor Induk Berusaha (ijin usaha customer)
        'nib_number',
        'is_pkp',
        'is_active',
        'grace_period_days',
        'default_payment',
        'default_bank_payment_id', // Default Bank Payment FK
        'member_since',
        'balance',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'customer_category_id',
        'assigned_to',
        'province_id',
        'district_id',
        'subdistrict_id',
        'website',
        'industry',
        'employee_count',
        'annual_revenue',
        'classification_id', // Added classification field
        'description',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_pkp' => 'boolean',
        'is_active' => 'boolean',
        'member_since' => 'date',
        'npwp_registration_date' => 'date',
        'balance' => 'decimal:2',
        'employee_count' => 'integer',
        'annual_revenue' => 'decimal:2',
        'classification_id' => 'integer'
    ];

    // Relationships

    public function classification()
    {
        return $this->belongsTo(OptionDetail::class, 'classification_id');
    }

    public function customerCategory()
    {
        return $this->belongsTo(CustomerCategory::class);
    }

    // Customer Type relationship for Category (links to /system/customer-types)
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_category_id');
    }

    public function assignedTo()
    {
        // Changed from User to CustomerContact (MOM6)
        // "Assigned To" now refers to customer contact person (PIC at customer side)
        return $this->belongsTo(CustomerContact::class, 'assigned_to');
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

    // Removed: buildings() hasMany - Building tidak punya customer_id lagi (many-to-many relationship)
    // Use: buildingCustomers() or activeBuildings() instead
    
    // Many-to-many relationship with buildings (with details: floor, unit_number, notes)
    public function buildingCustomers()
    {
        return $this->belongsToMany(Building::class, 'building_customers')
            ->withPivot('unit_number', 'floor', 'notes', 'is_active')
            ->withTimestamps();
    }

    public function activeBuildings()
    {
        return $this->belongsToMany(Building::class, 'building_customers')
            ->wherePivot('is_active', true)
            ->withPivot('unit_number', 'floor', 'notes')
            ->withTimestamps();
    }

    public function customerContacts()
    {
        // Legacy: One-to-many (contacts that belong directly to this customer)
        return $this->hasMany(CustomerContact::class);
    }

    /**
     * Multi PIC: Many-to-many relationship with CustomerContact
     * A customer can have multiple PICs (contacts), and a contact can serve multiple customers
     */
    public function contacts()
    {
        return $this->belongsToMany(CustomerContact::class, 'customer_customer_contact')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get primary PIC from Multi PIC relationship
     */
    public function primaryContact()
    {
        return $this->contacts()->wherePivot('is_primary', true)->first();
    }

    /**
     * Default Bank Payment relationship
     */
    public function defaultBankPayment()
    {
        return $this->belongsTo(BankPayment::class, 'default_bank_payment_id');
    }

    public function creditLimits()
    {
        return $this->hasMany(CustomerCreditLimit::class);
    }

    public function paymentTerms()
    {
        return $this->hasMany(CustomerPaymentTerm::class);
    }

    public function virtualAccounts()
    {
        return $this->hasMany(VirtualAccount::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function customerTaxSettings()
    {
        return $this->hasMany(CustomerTax::class);
    }

    public function prospects()
    {
        return $this->hasMany(Prospect::class, 'customer_id');
    }

    public function taxHistory()
    {
        return $this->hasMany(CustomerTaxHistory::class);
    }

    public function activeTaxNumber($date = null)
    {
        $date = $date ?? now();
        return $this->hasOne(CustomerTaxHistory::class)
            ->where('is_active', true)
            ->where('effective_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->orderBy('effective_date', 'desc');
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
        return $query->where('customer_type', $type);
    }

    public function scopePKP($query)
    {
        return $query->where('is_pkp', true);
    }

    public function scopeNonPKP($query)
    {
        return $query->where('is_pkp', false);
    }

    public function scopeByCustomerName($query, $customerName)
    {
        return $query->where('name', 'like', "%{$customerName}%");
    }

    public function scopeByCustomerCode($query, $customerCode)
    {
        return $query->where('customer_code', 'like', "%{$customerCode}%");
    }

    public function scopeByTaxCode($query, $taxCode)
    {
        return $query->where('tax_code', $taxCode);
    }

    public function scopeByNibNumber($query, $nibNumber)
    {
        return $query->where('nib_number', $nibNumber);
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('email', 'like', "%{$email}%");
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', 'like', "%{$phone}%");
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->customer_type));
    }

    public function getCompanyTypeTextAttribute()
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

    public function getDefaultPaymentTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->default_payment));
    }

    // Mutators
    public function setCustomerCodeAttribute($value)
    {
        $this->attributes['customer_code'] = strtoupper($value);
    }

    public function setTaxCodeAttribute($value)
    {
        $this->attributes['tax_code'] = $value ? strtoupper($value) : null;
    }

    public function setNibNumberAttribute($value)
    {
        $this->attributes['nib_number'] = $value ? strtoupper($value) : null;
    }

    // Business Logic Methods
    public function getAvailableCredit()
    {
        $creditLimit = $this->creditLimits()->where('is_active', true)->first();
        if (!$creditLimit) {
            return 0;
        }
        
        return $creditLimit->available_credit;
    }

    public function getCreditLimit()
    {
        $creditLimit = $this->creditLimits()->where('is_active', true)->first();
        return $creditLimit ? $creditLimit->credit_limit : 0;
    }

    public function getUsedCredit()
    {
        $creditLimit = $this->creditLimits()->where('is_active', true)->first();
        return $creditLimit ? $creditLimit->used_credit : 0;
    }

    public function updateCreditUsage($amount)
    {
        $creditLimit = $this->creditLimits()->where('is_active', true)->first();
        if ($creditLimit) {
            $creditLimit->used_credit += $amount;
            $creditLimit->available_credit = $creditLimit->credit_limit - $creditLimit->used_credit;
            $creditLimit->save();
        }
    }

    public function getPaymentTermDays()
    {
        $paymentTerm = $this->paymentTerms()->where('is_active', true)->first();
        return $paymentTerm ? $paymentTerm->payment_term_days : 30;
    }

    public function getDiscountPercentage()
    {
        $paymentTerm = $this->paymentTerms()->where('is_active', true)->first();
        return $paymentTerm ? $paymentTerm->discount_percentage : 0;
    }

    // Static Methods
    public static function generateCustomerCode($customerName = null)
    {
                // Get creator's branch code (max 3 chars)
        $user = auth()->user();
        $branchCode = $user && $user->branch ? strtoupper(substr($user->branch->code, 0, 3)) : 'HQ';
        
        // Get first letter of customer name (uppercase)
        // If name not provided, use 'X' as placeholder
        $firstLetter = 'X';
        if ($customerName) {
            // Get first letter from the name (skip spaces and special chars)
            $cleanName = preg_replace('/[^a-zA-Z]/', '', $customerName);
            if (strlen($cleanName) > 0) {
                $firstLetter = strtoupper(substr($cleanName, 0, 1));
            }
        }
        
        // Format: ADS-{branchCode}{firstLetter}{4digit}
        // Example: ADS-JKTC0001 (Jakarta, Cahyadi, sequence 1)
        // Example: ADS-MKSJ0087 (Makassar, Joko, sequence 87)
        $prefix = "ADS-{$branchCode}{$firstLetter}";
        
        // Get last customer for this prefix (ADS- + branchCode + firstLetter combination)
        $lastCustomer = self::withTrashed()
                          ->where('customer_code', 'like', "{$prefix}%")
                          ->orderBy('customer_code', 'desc')
                          ->first();
        
        if ($lastCustomer) {
            // Extract number part (everything after prefix)
            $lastNumber = (int) preg_replace('/\D/', '', substr($lastCustomer->customer_code, strlen($prefix)));
            $sequence = $lastNumber + 1;
        } else {
            $sequence = 1;
        }
        
        // Use 4-digit padding, but grow if needed (9999 → 10000)
        $padding = max(4, strlen((string)$sequence));
        
        return $prefix . str_pad($sequence, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * Get list of PPn Transaction Codes (Kode Transaksi PPN)
     * Based on Indonesian tax regulations
     */
    public static function getPpnCodes()
    {
        return [
            '01' => '01 - Kepada Pihak yang bukan Pemungut PPN',
            '02' => '02 - Kepada Pemungut Bendaharawan',
            '03' => '03 - Kepada Pemungut Selain Bendaharawan',
            '04' => '04 - DPP Nilai Lain',
            '05' => '05 - Besaran Tertentu',
            '06' => '06 - Penyerahan Lainnya',
            '07' => '07 - Penyerahan yang PPN-nya Tidak Dipungut',
            '08' => '08 - Penyerahan yang Dibebaskan dari PPN',
            '09' => '09 - Penyerahan Aktiva (Pasal 16D)',
        ];
    }
}

