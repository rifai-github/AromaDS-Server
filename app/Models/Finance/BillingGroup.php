<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Building;
use App\Models\Finance\Invoice;
use Carbon\Carbon;

class BillingGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'billing_group_name',
        'customer_id',      // Parent dari Billing Group adalah Customer
        'contract_id',      // Contract yang menggunakan billing group ini (nullable, karena bisa reuse)
        'billing_frequency',
        'billing_start_date',
        'billing_end_date',
        'billing_amount',
        'is_active',
        'pic_name',
        'pic_phone',
        'pic_email',
        'pic_address',
        'npwp_number',
        'npwp_name',
        'npwp_address',
        'tax_type',      // NPWP, NIK, or NITKU
        'tax_number',   // Nomor NPWP/NIK/NITKU yang dipilih
        'ppn_code',
        'npwp',
        'nitku',
        'nik',
        'invoice_type',
        'payment_method',
        'virtual_account_number',
        'bank_name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'billing_start_date' => 'date',
        'billing_end_date' => 'date',
        'billing_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function buildings()
    {
        return $this->belongsToMany(Building::class, 'billing_group_buildings', 'billing_group_id', 'building_id')
                    ->withPivot('billing_amount', 'notes', 'is_active', 'created_by', 'updated_by', 'created_at', 'updated_at')
                    ->wherePivotNull('deleted_at')
                    ->withTimestamps();
    }

    public function billingGroupBuildings()
    {
        return $this->hasMany(BillingGroupBuilding::class);
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

    public function scopeMonthly($query)
    {
        return $query->where('billing_frequency', 'monthly');
    }

    public function scopeQuarterly($query)
    {
        return $query->where('billing_frequency', 'quarterly');
    }

    public function scopeYearly($query)
    {
        return $query->where('billing_frequency', 'yearly');
    }

    public function scopeOneTime($query)
    {
        return $query->where('billing_frequency', 'one_time');
    }

    // Accessors & Mutators
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->billing_amount, 0, ',', '.');
    }

    public function getFrequencyBadgeAttribute()
    {
        $badges = [
            'monthly' => 'bg-blue-100 text-blue-800',
            'quarterly' => 'bg-green-100 text-green-800',
            'yearly' => 'bg-purple-100 text-purple-800',
            'one_time' => 'bg-yellow-100 text-yellow-800',
        ];

        return $badges[$this->billing_frequency] ?? 'bg-gray-100 text-gray-800';
    }

    public function getFrequencyLabelAttribute()
    {
        $labels = [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            'one_time' => 'One Time',
        ];

        return $labels[$this->billing_frequency] ?? ucfirst($this->billing_frequency);
    }

    public function getStatusBadgeAttribute()
    {
        return $this->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    }

    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getDurationAttribute()
    {
        if (!$this->billing_end_date) {
            return 'Ongoing';
        }

        return $this->billing_start_date->format('d/m/Y') . ' - ' . $this->billing_end_date->format('d/m/Y');
    }

    public function getFormattedStartDateAttribute()
    {
        return $this->billing_start_date ? $this->billing_start_date->format('d/m/Y') : '-';
    }

    public function getFormattedEndDateAttribute()
    {
        return $this->billing_end_date ? $this->billing_end_date->format('d/m/Y') : '-';
    }

    // New accessors for enhanced fields
    public function getInvoiceTypeBadgeAttribute()
    {
        $badges = [
            'hard_copy' => 'bg-blue-100 text-blue-800',
            'soft_copy' => 'bg-green-100 text-green-800',
            'both' => 'bg-purple-100 text-purple-800',
            'manual' => 'bg-yellow-100 text-yellow-800',
        ];

        return $badges[$this->invoice_type] ?? 'bg-gray-100 text-gray-800';
    }

    public function getInvoiceTypeLabelAttribute()
    {
        $labels = [
            'hard_copy' => 'Hard Copy',
            'soft_copy' => 'Soft Copy',
            'both' => 'Both',
            'manual' => 'Manual',
        ];

        return $labels[$this->invoice_type] ?? ucfirst($this->invoice_type);
    }

    public function getPaymentMethodBadgeAttribute()
    {
        $badges = [
            'va_bca' => 'bg-blue-100 text-blue-800',
            'va_mandiri' => 'bg-red-100 text-red-800',
            'transfer' => 'bg-green-100 text-green-800',
            'cash' => 'bg-yellow-100 text-yellow-800',
        ];

        return $badges[$this->payment_method] ?? 'bg-gray-100 text-gray-800';
    }

    public function getPaymentMethodLabelAttribute()
    {
        $labels = [
            'va_bca' => 'Virtual Account BCA',
            'va_mandiri' => 'Virtual Account Mandiri',
            'transfer' => 'Bank Transfer',
            'cash' => 'Cash',
        ];

        return $labels[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    public function getHasPicInfoAttribute()
    {
        return !empty($this->pic_name) && !empty($this->pic_phone);
    }

    public function getHasNpwpInfoAttribute()
    {
        return !empty($this->npwp_number) && !empty($this->npwp_name);
    }

    public function getHasPaymentInfoAttribute()
    {
        return !empty($this->payment_method) && !empty($this->virtual_account_number);
    }

    public function getTotalBuildingsAttribute()
    {
        return $this->buildings()->count();
    }

    public function getTotalBuildingsAmountAttribute()
    {
        return $this->billingGroupBuildings()->sum('billing_amount');
    }
}
