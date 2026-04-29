<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobReport extends Model
{
    protected $fillable = [
        'job_schedule_id',
        'job_reference_number', // Job Reference Enhancement
        'technician_id',
        'job_type',
        'temperature',
        'condition',
        'refill_status',
        'notes',
        'completed_at',
        // GPS/Location fields
        'latitude',
        'longitude',
        'location_address',
        'location_updated_at',
        // Photo Documentation fields
        'photos',
        'photo_before',
        'photo_after',
        'photo_pic',
        // Digital Signature fields
        'signature_data',
        'signature_file',
        'pic_name',
        'pic_position',
        'signature_at',
        // QR Code Integration fields
        'unit_serial_number',
        'unit_mac_address',
        'material_qr_codes',
        'qr_scan_at',
        // Mandatory QR Scan fields
        'mandatory_qr_scan_required',
        'mandatory_qr_scan_completed',
        'mandatory_qr_scan_data',
        'mandatory_qr_scan_at',
        'qr_scan_validation_status',
        'qr_scan_validation_message',
        // Device Control fields
        'device_snapshot',
        'device_online_status',
        'device_liquid_level',
        'device_fan_level'
    ];

    protected $casts = [
        'photos' => 'array',
        'material_qr_codes' => 'array',
        'device_snapshot' => 'array',
        'mandatory_qr_scan_data' => 'array',
        'completed_at' => 'datetime',
        'location_updated_at' => 'datetime',
        'signature_at' => 'datetime',
        'qr_scan_at' => 'datetime',
        'mandatory_qr_scan_at' => 'datetime',
        'mandatory_qr_scan_required' => 'boolean',
        'mandatory_qr_scan_completed' => 'boolean',
        'temperature' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    /**
     * Get the job schedule that owns the report.
     */
    public function jobSchedule(): BelongsTo
    {
        return $this->belongsTo(JobSchedule::class);
    }

    /**
     * Get the technician who created the report.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Get the temperature records for this job.
     */
    public function temperatureRecords(): HasMany
    {
        return $this->hasMany(TemperatureRecord::class, 'job_schedule_id', 'job_schedule_id');
    }

    /**
     * Get the job signatures for this job.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(JobSignature::class, 'job_schedule_id', 'job_schedule_id');
    }

    /**
     * Scope for completed reports.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope for pending reports.
     */
    public function scopePending($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Scope for reports requiring mandatory QR scan.
     */
    public function scopeRequiringQRScan($query)
    {
        return $query->where('mandatory_qr_scan_required', true)
                    ->where('mandatory_qr_scan_completed', false);
    }

    /**
     * Scope for reports with completed QR scan.
     */
    public function scopeWithCompletedQRScan($query)
    {
        return $query->where('mandatory_qr_scan_completed', true);
    }

    /**
     * Check if mandatory QR scan is required for this job type.
     */
    public function requiresMandatoryQRScan(): bool
    {
        return in_array($this->job_type, ['install', 'service']) && $this->mandatory_qr_scan_required;
    }

    /**
     * Check if mandatory QR scan is completed.
     */
    public function isMandatoryQRScanCompleted(): bool
    {
        return $this->mandatory_qr_scan_completed;
    }

    /**
     * Mark mandatory QR scan as completed.
     */
    public function markQRScanCompleted(array $qrData = []): void
    {
        $this->update([
            'mandatory_qr_scan_completed' => true,
            'mandatory_qr_scan_data' => $qrData,
            'mandatory_qr_scan_at' => now(),
            'qr_scan_validation_status' => 'valid'
        ]);
    }

    /**
     * Mark mandatory QR scan as failed.
     */
    public function markQRScanFailed(string $message = ''): void
    {
        $this->update([
            'qr_scan_validation_status' => 'failed',
            'qr_scan_validation_message' => $message
        ]);
    }

    /**
     * Get QR scan validation status badge color.
     */
    public function getQRScanStatusBadgeColorAttribute(): string
    {
        return match($this->qr_scan_validation_status) {
            'valid' => 'success',
            'invalid' => 'danger',
            'failed' => 'danger',
            'pending' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Get QR scan validation status text.
     */
    public function getQRScanStatusTextAttribute(): string
    {
        return match($this->qr_scan_validation_status) {
            'valid' => 'Valid',
            'invalid' => 'Invalid',
            'failed' => 'Failed',
            'pending' => 'Pending',
            default => 'Unknown'
        };
    }

    // Job Reference Enhancement Methods
    public function getJobReferenceNumberAttribute()
    {
        return $this->job_reference_number ?: $this->jobSchedule?->job_reference_number;
    }

    public function hasJobReference()
    {
        return !empty($this->job_reference_number) || !empty($this->jobSchedule?->job_reference_number);
    }

    public function getJobReferenceDisplayAttribute()
    {
        return $this->getJobReferenceNumberAttribute() ?: 'Not Assigned';
    }

    public function scopeWithJobReference($query)
    {
        return $query->whereNotNull('job_reference_number');
    }

    public function scopeWithoutJobReference($query)
    {
        return $query->whereNull('job_reference_number');
    }

    public function scopeByJobReference($query, $referenceNumber)
    {
        return $query->where('job_reference_number', $referenceNumber);
    }
}
