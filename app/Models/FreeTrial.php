<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class FreeTrial extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'quotation_id',
        'room_id',
        'master_rental_id',
        'trial_number',
        'trial_start_date',
        'trial_end_date',
        'trial_notes',
        'status',
        'requested_by',
        'approved_by',
        'requested_at',
        'approved_at',
        'started_at',
        'completed_at',
        'approval_notes',
        'completion_notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'trial_start_date' => 'date',
        'trial_end_date' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Relationships
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
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

    public function scopeByQuotation($query, $quotationId)
    {
        return $query->where('quotation_id', $quotationId);
    }

    public function scopeByRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('trial_start_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'active' => 'Aktif',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getFormattedTrialStartDateAttribute()
    {
        return $this->trial_start_date ? $this->trial_start_date->format('d/m/Y') : '-';
    }

    public function getFormattedTrialEndDateAttribute()
    {
        return $this->trial_end_date ? $this->trial_end_date->format('d/m/Y') : '-';
    }

    public function getFormattedRequestedAtAttribute()
    {
        return $this->requested_at ? $this->requested_at->format('d/m/Y H:i') : '-';
    }

    public function getFormattedApprovedAtAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('d/m/Y H:i') : '-';
    }

    public function getTrialDurationAttribute()
    {
        if ($this->trial_start_date && $this->trial_end_date) {
            return $this->trial_start_date->diffInDays($this->trial_end_date);
        }
        return null;
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
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

    public function start()
    {
        $this->update([
            'status' => 'active',
            'started_at' => now()
        ]);
    }

    public function complete($notes = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_notes' => $notes
        ]);
    }

    public function cancel()
    {
        $this->update([
            'status' => 'cancelled'
        ]);
    }

    public function isExpired()
    {
        return $this->trial_end_date && $this->trial_end_date < now();
    }

    public function getRemainingDays()
    {
        if ($this->trial_end_date && $this->trial_end_date > now()) {
            return now()->diffInDays($this->trial_end_date);
        }
        return 0;
    }
}