<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualAccountExport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_payment_id',
        'export_type',
        'start_date',
        'end_date',
        'export_format',
        'file_path',
        'total_records',
        'export_status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function bankPayment()
    {
        return $this->belongsTo(BankPayment::class);
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
        return $query->where('export_status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('export_type', $type);
    }

    public function scopeByFormat($query, $format)
    {
        return $query->where('export_format', $format);
    }

    public function scopeByBankPayment($query, $bankPaymentId)
    {
        return $query->where('bank_payment_id', $bankPaymentId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }

    public function scopeByBank($query, $bankId)
    {
        return $query->whereHas('bankPayment', function($q) use ($bankId) {
            $q->where('bank_id', $bankId);
        });
    }

    public function scopePending($query)
    {
        return $query->where('export_status', 'Pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('export_status', 'Processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('export_status', 'Completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('export_status', 'Failed');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'Pending' => 'badge-warning',
            'Processing' => 'badge-info',
            'Completed' => 'badge-success',
            'Failed' => 'badge-danger',
        ];

        return $badges[$this->export_status] ?? 'badge-secondary';
    }

    public function getFormatIconAttribute()
    {
        $icons = [
            'Excel' => 'fas fa-file-excel',
            'CSV' => 'fas fa-file-csv',
            'PDF' => 'fas fa-file-pdf',
        ];

        return $icons[$this->export_format] ?? 'fas fa-file';
    }

    public function getFormattedStartDateAttribute()
    {
        return $this->start_date->format('d M Y');
    }

    public function getFormattedEndDateAttribute()
    {
        return $this->end_date->format('d M Y');
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d M Y H:i');
    }

    public function getFileNameAttribute()
    {
        if ($this->file_path) {
            return basename($this->file_path);
        }
        return null;
    }

    public function getDateRangeAttribute()
    {
        return $this->start_date->format('d M Y') . ' - ' . $this->end_date->format('d M Y');
    }

    // Methods
    public function isPending()
    {
        return $this->export_status === 'Pending';
    }

    public function isProcessing()
    {
        return $this->export_status === 'Processing';
    }

    public function isCompleted()
    {
        return $this->export_status === 'Completed';
    }

    public function isFailed()
    {
        return $this->export_status === 'Failed';
    }

    public function canBeRegenerated()
    {
        return in_array($this->export_status, ['Completed', 'Failed']);
    }

    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return asset('uploads/' . $this->file_path);
        }
        return null;
    }

    public function getFileSizeAttribute()
    {
        if ($this->file_path && file_exists(public_path('uploads/' . $this->file_path))) {
            $size = filesize(public_path('uploads/' . $this->file_path));
            return $this->formatBytes($size);
        }
        return '0 B';
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }

    public function getExportTypeLabelAttribute()
    {
        $labels = [
            'Daily' => 'Harian',
            'Weekly' => 'Mingguan',
            'Monthly' => 'Bulanan',
            'Custom' => 'Kustom',
        ];

        return $labels[$this->export_type] ?? $this->export_type;
    }

    public function getExportFormatLabelAttribute()
    {
        $labels = [
            'Excel' => 'Microsoft Excel',
            'CSV' => 'Comma Separated Values',
            'PDF' => 'Portable Document Format',
        ];

        return $labels[$this->export_format] ?? $this->export_format;
    }
}
