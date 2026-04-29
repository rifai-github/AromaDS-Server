<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;
use App\Http\Traits\AutoFilterable;

class BeritaAcara extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail, AutoFilterable;

    protected $table = 'berita_acara';

    protected $fillable = [
        'berita_acara_number',
        'logistics_tracking_id',
        'inventory_receiving_id',
        'type',
        'description',
        'action_taken',
        'estimated_value',
        'status',
        'reported_by',
        'approved_by',
        'reported_at',
        'approved_at',
        'processed_at',
        'approval_notes',
        'rejection_reason',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'reported_at' => 'datetime',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime'
    ];

    // Relationships
    public function logisticsTracking()
    {
        return $this->belongsTo(LogisticsTracking::class);
    }

    public function inventoryReceiving()
    {
        return $this->belongsTo(InventoryReceiving::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
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
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByReporter($query, $reporterId)
    {
        return $query->where('reported_by', $reporterId);
    }

    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approved_by', $approverId);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Helper methods
    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'loss' => 'Loss',
            'damage' => 'Damage',
            'discrepancy' => 'Discrepancy',
            default => 'Unknown'
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'processed' => 'Processed',
            default => 'Unknown'
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'draft' => 'badge-secondary',
            'submitted' => 'badge-warning',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            'processed' => 'badge-info',
            default => 'badge-light'
        };
    }

    public function getFormattedEstimatedValueAttribute()
    {
        return 'Rp ' . number_format($this->estimated_value, 0, ',', '.');
    }

    public function getDaysSinceReportedAttribute()
    {
        return $this->reported_at->diffInDays(now());
    }

    public function isPendingApproval()
    {
        return $this->status === 'submitted';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isProcessed()
    {
        return $this->status === 'processed';
    }

    public function canBeApproved()
    {
        return $this->status === 'submitted';
    }

    public function canBeRejected()
    {
        return $this->status === 'submitted';
    }

    public function canBeProcessed()
    {
        return $this->status === 'approved';
    }

    public function approve($approverId, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_notes' => $notes,
            'updated_by' => auth()->id()
        ]);
    }

    public function reject($approverId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
            'updated_by' => auth()->id()
        ]);
    }

    public function process()
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
            'updated_by' => auth()->id()
        ]);
    }

    public function generateBeritaAcaraNumber()
    {
        $prefix = 'BA';
        $date = now()->format('Ymd');
        $sequence = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $date . $sequence;
    }

    public static function createBeritaAcara($logisticsTrackingId, $inventoryReceivingId, $type, $description, $actionTaken, $estimatedValue)
    {
        $beritaAcara = new self();
        $beritaAcara->berita_acara_number = $beritaAcara->generateBeritaAcaraNumber();
        $beritaAcara->logistics_tracking_id = $logisticsTrackingId;
        $beritaAcara->inventory_receiving_id = $inventoryReceivingId;
        $beritaAcara->type = $type;
        $beritaAcara->description = $description;
        $beritaAcara->action_taken = $actionTaken;
        $beritaAcara->estimated_value = $estimatedValue;
        $beritaAcara->status = 'draft';
        $beritaAcara->reported_by = auth()->id();
        $beritaAcara->reported_at = now();
        $beritaAcara->created_by = auth()->id();
        $beritaAcara->updated_by = auth()->id();
        $beritaAcara->save();

        return $beritaAcara;
    }
}