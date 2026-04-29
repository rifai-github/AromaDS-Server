<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractRevision extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'revision_number',
        'revision_reason',
        'changed_fields',
        'old_values',
        'new_values',
        'status',
        'approval_notes',
        'requested_by',
        'approved_by',
        'requested_at',
        'approved_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime'
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
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

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        $statuses = [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'bg-gray-100 text-gray-800',
            'pending_approval' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800'
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getChangedFieldsCountAttribute()
    {
        return count($this->changed_fields ?? []);
    }

    public function getFormattedRequestedAtAttribute()
    {
        return $this->requested_at ? $this->requested_at->format('d/m/Y H:i') : '-';
    }

    public function getFormattedApprovedAtAttribute()
    {
        return $this->approved_at ? $this->approved_at->format('d/m/Y H:i') : '-';
    }
}