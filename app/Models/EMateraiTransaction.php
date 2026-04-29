<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class EMateraiTransaction extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'tax_invoice_id',
        'transaction_id',
        'status',
        'amount',
        'materai_type',
        'peruri_reference',
        'api_request',
        'api_response',
        'original_file_path',
        'stamped_file_path',
        'requested_at',
        'completed_at',
        'error_message',
        'retry_count',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'api_request' => 'array',
        'api_response' => 'array',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'retry_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relationships
    public function taxInvoice()
    {
        return $this->belongsTo(TaxInvoice::class);
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByMateraiType($query, $type)
    {
        return $query->where('materai_type', $type);
    }

    public function scopeByTaxInvoice($query, $invoiceId)
    {
        return $query->where('tax_invoice_id', $invoiceId);
    }

    public function scopeByPeruriReference($query, $reference)
    {
        return $query->where('peruri_reference', $reference);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getFormattedRequestedAtAttribute()
    {
        return $this->requested_at ? $this->requested_at->format('d/M/Y H:i') : '-';
    }

    public function getFormattedCompletedAtAttribute()
    {
        return $this->completed_at ? $this->completed_at->format('d/M/Y H:i') : '-';
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d/M/Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d/M/Y H:i');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'processing' => '<span class="badge badge-info">Processing</span>',
            'completed' => '<span class="badge badge-success">Completed</span>',
            'failed' => '<span class="badge badge-danger">Failed</span>',
            'cancelled' => '<span class="badge badge-secondary">Cancelled</span>'
        ];
        
        return $badges[$this->status] ?? '<span class="badge badge-secondary">Unknown</span>';
    }

    public function getMateraiTypeLabelAttribute()
    {
        $labels = [
            '3000' => 'Rp 3,000',
            '6000' => 'Rp 6,000',
            '10000' => 'Rp 10,000',
            '30000' => 'Rp 30,000',
            '50000' => 'Rp 50,000'
        ];
        
        return $labels[$this->materai_type] ?? 'Rp ' . number_format($this->materai_type, 0, ',', '.');
    }

    public function getDurationAttribute()
    {
        if (!$this->requested_at || !$this->completed_at) {
            return '-';
        }
        
        $duration = $this->requested_at->diffInMinutes($this->completed_at);
        
        if ($duration < 60) {
            return $duration . ' minutes';
        } else {
            $hours = floor($duration / 60);
            $minutes = $duration % 60;
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function canBeRetried()
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function markAsProcessing()
    {
        $this->update([
            'status' => 'processing',
            'requested_at' => now()
        ]);
    }

    public function markAsCompleted($stampedFilePath, $peruriReference = null)
    {
        $this->update([
            'status' => 'completed',
            'stamped_file_path' => $stampedFilePath,
            'peruri_reference' => $peruriReference,
            'completed_at' => now()
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1
        ]);
    }

    public function markAsCancelled()
    {
        $this->update([
            'status' => 'cancelled'
        ]);
    }

    public function getApiRequestData()
    {
        return $this->api_request ?? [];
    }

    public function getApiResponseData()
    {
        return $this->api_response ?? [];
    }

    public function setApiRequest($requestData)
    {
        $this->update(['api_request' => $requestData]);
    }

    public function setApiResponse($responseData)
    {
        $this->update(['api_response' => $responseData]);
    }

    public function getOriginalFilePath()
    {
        return $this->original_file_path;
    }

    public function getStampedFilePath()
    {
        return $this->stamped_file_path;
    }

    public function hasStampedFile()
    {
        return !empty($this->stamped_file_path) && file_exists(storage_path('app/' . $this->stamped_file_path));
    }

    public function getDownloadUrl()
    {
        if ($this->hasStampedFile()) {
            return route('e-materai.download', $this->id);
        }
        
        return null;
    }
}