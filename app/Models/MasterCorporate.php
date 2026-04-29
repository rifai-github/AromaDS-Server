<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\DocumentNumberService;

class MasterCorporate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'customer_id',
        'master_rental_id',
        'price',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_WAITING_APPROVAL = 'waiting_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(MasterCorporate::class, 'code', 'code');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeWaitingApproval($query)
    {
        return $query->where('status', self::STATUS_WAITING_APPROVAL);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'info',
            self::STATUS_WAITING_APPROVAL => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_WAITING_APPROVAL => 'Waiting Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => ucfirst(str_replace('_', ' ', $this->status))
        };
    }

    // Methods
    public function submitForApproval()
    {
        $this->update(['status' => self::STATUS_WAITING_APPROVAL]);
    }

    public function approve($userId, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'approval_notes' => $notes
        ]);
    }

    public function reject($userId, $notes)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'approval_notes' => $notes
        ]);
    }

    // Auto-generate Code
    public static function generateCode()
    {
        $service = new DocumentNumberService();
        return $service->generate('master_corporate');
    }
}
