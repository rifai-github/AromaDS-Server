<?php

namespace App\Models;

use App\Http\Traits\AutoFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\HasComprehensiveAuditTrail;

class Bank extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable, HasComprehensiveAuditTrail;

    protected $fillable = [
        'bank_code',
        'bank_name',
        'name',
        'swift_code',
        'bank_address',
        'phone',
        'email',
        'website',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function virtualAccounts()
    {
        return $this->hasMany(VirtualAccount::class);
    }

    public function bankPayments()
    {
        return $this->hasMany(BankPayment::class);
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

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->bank_name} ({$this->bank_code})";
    }

    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }
}
