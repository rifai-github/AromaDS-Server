<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class BankPayment extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

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
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default_va' => 'boolean',
    ];

    // Relationships
    public function bank()
    {
        return $this->belongsTo(\App\Models\Bank::class, 'bank_id');
    }


    public function virtualAccounts()
    {
        return $this->hasMany(VirtualAccount::class);
    }

    public function virtualAccountImports()
    {
        return $this->hasMany(VirtualAccountImport::class);
    }

    public function virtualAccountExports()
    {
        return $this->hasMany(VirtualAccountExport::class);
    }

    public function virtualAccountImportTransactions()
    {
        return $this->hasMany(VirtualAccountImportTransaction::class, 'bank_id', 'bank_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }


    public function scopeDefaultVa($query)
    {
        return $query->where('is_default_va', true);
    }

    // Additional helper methods for better functionality
    public function getFormattedAccountNumber()
    {
        return $this->account_number ? preg_replace('/(.{4})/', '$1 ', $this->account_number) : '-';
    }

    public function getFullBankInfo()
    {
        return $this->bank ? "{$this->bank->name} - {$this->account_name}" : '-';
    }

    public function isDefaultVa()
    {
        return $this->is_default_va;
    }

    public function isActive()
    {
        return $this->is_active;
    }
}
