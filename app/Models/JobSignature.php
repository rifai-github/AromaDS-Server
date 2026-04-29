<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSignature extends Model
{
    protected $fillable = [
        'job_schedule_id',
        'signature_data',
        'signed_by',
        'signed_at',
        'signature_type',
        'notes'
    ];

    protected $casts = [
        'signed_at' => 'datetime'
    ];

    /**
     * Get the job schedule that owns the signature.
     */
    public function jobSchedule(): BelongsTo
    {
        return $this->belongsTo(JobSchedule::class);
    }

    /**
     * Get the user who signed.
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    /**
     * Scope for customer signatures.
     */
    public function scopeCustomerSignatures($query)
    {
        return $query->where('signature_type', 'customer');
    }

    /**
     * Scope for technician signatures.
     */
    public function scopeTechnicianSignatures($query)
    {
        return $query->where('signature_type', 'technician');
    }

    /**
     * Scope for supervisor signatures.
     */
    public function scopeSupervisorSignatures($query)
    {
        return $query->where('signature_type', 'supervisor');
    }

    /**
     * Get the signature type badge color.
     */
    public function getSignatureTypeBadgeColorAttribute(): string
    {
        return match($this->signature_type) {
            'customer' => 'primary',
            'technician' => 'success',
            'supervisor' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Check if signature is recent (within 24 hours).
     */
    public function isRecentSignature(): bool
    {
        return $this->signed_at->isAfter(now()->subDay());
    }

    /**
     * Get the signature age in hours.
     */
    public function getSignatureAgeAttribute(): int
    {
        return $this->signed_at->diffInHours(now());
    }
}
