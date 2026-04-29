<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TaxFileExport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'export_number',
        'export_date',
        'export_type',
        'period_from',
        'period_to',
        'file_format',
        'file_path',
        'status',
        'total_records',
        'file_size',
        'include_details',
        'notes',
        'filter_parameters',
        'exported_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'export_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'filter_parameters' => 'array',
        'exported_at' => 'datetime',
        'include_details' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
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

    public function scopeByFormat($query, $format)
    {
        return $query->where('file_format', $format);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('export_date', [$startDate, $endDate]);
    }

    public function scopeByPeriodRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_from', [$startDate, $endDate])
                    ->orWhereBetween('period_to', [$startDate, $endDate]);
    }

    public function scopeByCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
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

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'status-pending',
            'processing' => 'status-processing',
            'completed' => 'status-completed',
            'failed' => 'status-failed',
        ];

        return $badges[$this->status] ?? 'status-pending';
    }

    public function getFormatLabelAttribute()
    {
        $formats = [
            'csv' => 'CSV',
            'xlsx' => 'Excel (XLSX)',
            'pdf' => 'PDF',
        ];

        return $formats[$this->file_format] ?? $this->file_format;
    }

    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) {
            return '-';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFormattedExportDateAttribute()
    {
        return $this->export_date ? $this->export_date->format('d/m/Y') : '-';
    }

    public function getFormattedPeriodFromAttribute()
    {
        return $this->period_from ? $this->period_from->format('d/m/Y') : '-';
    }

    public function getFormattedPeriodToAttribute()
    {
        return $this->period_to ? $this->period_to->format('d/m/Y') : '-';
    }

    public function getFormattedExportedAtAttribute()
    {
        return $this->exported_at ? $this->exported_at->format('d/m/Y H:i') : '-';
    }

    // Mutators
    public function setExportDateAttribute($value)
    {
        $this->attributes['export_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setPeriodFromAttribute($value)
    {
        $this->attributes['period_from'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setPeriodToAttribute($value)
    {
        $this->attributes['period_to'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    // Helper methods
    public function getFileExistsAttribute()
    {
        return $this->file_path && file_exists(public_path('uploads/' . $this->file_path));
    }

    public function getDownloadUrlAttribute()
    {
        return $this->fileExists ? route('finance.tax-file-exports.download', $this->id) : null;
    }

    // Auto-generate export number
    public static function generateExportNumber()
    {
        $prefix = 'TFE';
        $date = date('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        
        return $prefix . '-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // Helper methods
    public function canDownload()
    {
        return $this->status === 'completed' && $this->fileExists;
    }

    public function canDelete()
    {
        return in_array($this->status, ['pending', 'failed']);
    }

    public function getFilterParametersArray()
    {
        return is_array($this->filter_parameters) ? $this->filter_parameters : [];
    }
}
