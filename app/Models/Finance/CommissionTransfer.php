<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;
use App\Models\Contract;

class CommissionTransfer extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $table = 'commission_transfers';

    protected $fillable = [
        'contract_id',
        'from_user_id',
        'to_user_id',
        'commission_calculation_id',
        'commission_amount',
        'status',
        'reason',
        'approved_by',
        'approved_at',
        'approval_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function commissionCalculation()
    {
        return $this->belongsTo(CommissionCalculation::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
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
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByFromUser($query, $userId)
    {
        return $query->where('from_user_id', $userId);
    }

    public function scopeByToUser($query, $userId)
    {
        return $query->where('to_user_id', $userId);
    }

    // Accessors
    public function getFormattedCommissionAmountAttribute()
    {
        return 'Rp ' . number_format($this->commission_amount, 0, ',', '.');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
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

    public function reject($reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'approval_notes' => $reason
        ]);
    }

    public function complete()
    {
        $this->update(['status' => 'completed']);
    }
}
