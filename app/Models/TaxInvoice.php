<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tax_setting_id',
        'no_faktur',
        'reserved',
        'used',
        'approved',
        'tax_status',
        'receipt_no',
        'invoice_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'reserved' => 'boolean',
        'used' => 'boolean',
        'approved' => 'boolean'
    ];

    // Relationships
    public function taxSetting()
    {
        return $this->belongsTo(TaxSetting::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    // Scopes
    public function scopeByTaxSetting($query, $taxSettingId)
    {
        return $query->where('tax_setting_id', $taxSettingId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('tax_status', $status);
    }

    public function scopeByInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeReserved($query)
    {
        return $query->where('reserved', true);
    }

    public function scopeNotReserved($query)
    {
        return $query->where('reserved', false);
    }

    public function scopeUsed($query)
    {
        return $query->where('used', true);
    }

    public function scopeNotUsed($query)
    {
        return $query->where('used', false);
    }

    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }

    public function scopeNotApproved($query)
    {
        return $query->where('approved', false);
    }

    public function scopeAvailable($query)
    {
        return $query->where('reserved', false)->where('used', false);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->tax_status));
    }

    public function getIsReservedAttribute()
    {
        return $this->reserved;
    }

    public function getIsUsedAttribute()
    {
        return $this->used;
    }

    public function getIsApprovedAttribute()
    {
        return $this->approved;
    }

    public function getIsAvailableAttribute()
    {
        return !$this->reserved && !$this->used;
    }

    public function getUsageStatusAttribute()
    {
        if ($this->used) {
            return 'used';
        } elseif ($this->reserved) {
            return 'reserved';
        } else {
            return 'available';
        }
    }

    public function getUsageStatusTextAttribute()
    {
        $statuses = [
            'used' => 'Used',
            'reserved' => 'Reserved',
            'available' => 'Available'
        ];
        return $statuses[$this->usage_status] ?? 'Unknown';
    }

    // Methods
    public function reserve()
    {
        $this->update(['reserved' => true]);
    }

    public function unreserve()
    {
        $this->update(['reserved' => false]);
    }

    public function markAsUsed($invoiceId = null)
    {
        $this->update([
            'used' => true,
            'invoice_id' => $invoiceId
        ]);
    }

    public function markAsUnused()
    {
        $this->update([
            'used' => false,
            'invoice_id' => null
        ]);
    }

    public function approve()
    {
        $this->update(['approved' => true]);
    }

    public function unapprove()
    {
        $this->update(['approved' => false]);
    }
}
