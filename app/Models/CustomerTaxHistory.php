<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class CustomerTaxHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_tax_history';

    protected $fillable = [
        'customer_id',
        'tax_type',
        'tax_number',
        'effective_date',
        'end_date',
        'is_active',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeEffectiveOn($query, $date)
    {
        $date = Carbon::parse($date);
        return $query->where('effective_date', '<=', $date)
                    ->where(function($q) use ($date) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $date);
                    });
    }

    // Static Methods
    public static function getTaxForDate($customerId, $date)
    {
        return self::forCustomer($customerId)
            ->effectiveOn($date)
            ->where('is_active', true)
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    public static function createNewTaxNumber($customerId, $taxType, $taxNumber, $effectiveDate, $notes = null, $createdBy = null)
    {
        // Deactivate previous active records
        self::forCustomer($customerId)
            ->active()
            ->update([
                'is_active' => false,
                'end_date' => Carbon::parse($effectiveDate)->subDay()
            ]);

        // Create new record
        return self::create([
            'customer_id' => $customerId,
            'tax_type' => $taxType,
            'tax_number' => $taxNumber,
            'effective_date' => $effectiveDate,
            'is_active' => true,
            'notes' => $notes,
            'created_by' => $createdBy ?? auth()->id()
        ]);
    }

    // Accessors
    public function getFormattedEffectiveDateAttribute()
    {
        return $this->effective_date ? $this->effective_date->format('d M Y') : '-';
    }

    public function getFormattedEndDateAttribute()
    {
        return $this->end_date ? $this->end_date->format('d M Y') : 'Current';
    }

    public function getTaxTypeTextAttribute()
    {
        return $this->tax_type === 'NPWP' ? 'NPWP' : 'NIK';
    }

    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }
}

