<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Finance\Bank;
use App\Models\Company\Branch;

class BankPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'account_name',
        'account_number',
        'branch_name',
        'address',
        'phone',
        'fax',
        'is_default_va',
        'bank_va_number',
        'start_number',
        'end_number',
        'length',
        'current_number',
        'is_active',
    ];

    protected $casts = [
        'is_default_va' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function virtualAccounts()
    {
        return $this->hasMany(VirtualAccount::class);
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

    public function scopeDefaultVa($query)
    {
        return $query->where('is_default_va', true);
    }

    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Accessors & Mutators
    public function getStatusBadgeAttribute()
    {
        return $this->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    }

    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getDefaultVaBadgeAttribute()
    {
        return $this->is_default_va ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800';
    }

    public function getDefaultVaLabelAttribute()
    {
        return $this->is_default_va ? 'Default VA' : 'Regular';
    }

    public function getDisplayNameAttribute()
    {
        return $this->account_name . ' (' . $this->account_number . ')';
    }

    public function getFormattedAccountNumberAttribute()
    {
        return $this->account_number;
    }

    // Get company name for invoice purposes
    public function getCompanyNameForInvoice()
    {
        $company = \App\Models\Company::where('is_active', true)->first();
        return $company ? $company->name : $this->account_name;
    }

    // Get full account info for invoice
    public function getAccountInfoForInvoice()
    {
        $company = \App\Models\Company::where('is_active', true)->first();
        return [
            'account_name' => $company ? $company->name : $this->account_name,
            'account_number' => $this->account_number,
            'bank_name' => $this->bank ? $this->bank->bank_name : null,
            'branch_name' => $this->branch_name,
        ];
    }
}
