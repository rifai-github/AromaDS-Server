<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class QuotationApproval extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'quotation_id',
        'quotation_revision_id',
        'approval_type',
        'status',
        'approval_notes',
        'rejection_reason',
        'requested_by',
        'approved_by',
        'requested_at',
        'approved_at',
        'approval_data',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'approval_data' => 'array'
    ];

    // Relationships
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function quotationRevision()
    {
        return $this->belongsTo(QuotationRevision::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
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

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByApprovalType($query, $approvalType)
    {
        return $query->where('approval_type', $approvalType);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByQuotation($query, $quotationId)
    {
        return $query->where('quotation_id', $quotationId);
    }

    public function scopeByRequestedBy($query, $userId)
    {
        return $query->where('requested_by', $userId);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getApprovalTypeTextAttribute()
    {
        $types = [
            'bottom_price' => 'Validasi Harga Terendah',
            'term_payment' => 'Validasi Term of Payment',
            'free_trial' => 'Persetujuan Free Trial',
            'general' => 'Persetujuan Umum'
        ];

        return $types[$this->approval_type] ?? $this->approval_type;
    }

    public function getFormattedRequestedAtAttribute()
    {
        return $this->requested_at ? $this->requested_at->format('d/m/Y H:i') : '-';
    }

    public function getFormattedApprovedAtAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('d/m/Y H:i') : '-';
    }

    // Methods
    public function approve($approvedBy, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'approval_notes' => $notes
        ]);
    }

    public function reject($approvedBy, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'rejection_reason' => $reason
        ]);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function getApprovalDuration()
    {
        if ($this->approved_at && $this->requested_at) {
            return $this->requested_at->diffInHours($this->approved_at);
        }
        return null;
    }
}