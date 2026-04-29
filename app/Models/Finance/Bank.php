<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Bank extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_code',
        'bank_name',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function bankPayments()
    {
        return $this->hasMany(BankPayment::class);
    }

    public function bankReceipts()
    {
        return $this->hasMany(BankReceipt::class);
    }

    public function virtualAccountImports()
    {
        return $this->hasMany(VirtualAccountImport::class);
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

    public function scopeByCode($query, $code)
    {
        return $query->where('bank_code', $code);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('bank_name', 'like', '%' . $name . '%');
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

    public function getDisplayNameAttribute()
    {
        return $this->bank_code . ' - ' . $this->bank_name;
    }
}