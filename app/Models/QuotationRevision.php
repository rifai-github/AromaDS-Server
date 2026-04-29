<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class QuotationRevision extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'quotation_id',
        'revision_number',
        'revision_notes',
        'status',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'terms_conditions',
        'rental_period',
        'terms_of_payment',
        'approved_by',
        'date_approved',
        'internal_notes',
        'additional_notes',
        'is_latest',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'date_approved' => 'date',
        'is_latest' => 'boolean'
    ];

    // Relationships
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvals()
    {
        return $this->hasMany(QuotationApproval::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeLatest($query)
    {
        return $query->where('is_latest', true);
    }

    public function scopeByQuotation($query, $quotationId)
    {
        return $query->where('quotation_id', $quotationId);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'draft');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'draft' => 'Draft',
            'sent' => 'Terkirim',
            'approved' => 'Disetujui Manager',
            'accepted' => 'Disetujui Pelanggan',
            'rejected' => 'Ditolak',
            'expired' => 'Kadaluarsa',
            'contract' => 'Contract'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getFormattedTotalAmountAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getFormattedGrandTotalAttribute()
    {
        return 'Rp ' . number_format($this->grand_total, 0, ',', '.');
    }

    public function getFormattedDateApprovedAttribute()
    {
        return $this->date_approved ? $this->date_approved->format('d/m/Y') : '-';
    }

    // Methods
    public function makeLatest()
    {
        // Set all other revisions of this quotation to not latest
        static::where('quotation_id', $this->quotation_id)
            ->where('id', '!=', $this->id)
            ->update(['is_latest' => false]);

        // Set this revision as latest
        $this->update(['is_latest' => true]);
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPending()
    {
        return in_array($this->status, ['draft', 'sent']);
    }
}