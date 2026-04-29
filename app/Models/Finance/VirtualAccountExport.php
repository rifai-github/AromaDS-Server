<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;
use App\Models\Finance\Bank;
use Carbon\Carbon;

class VirtualAccountExport extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'export_number',
        'export_date',
        'bank_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'date_from',
        'date_to',
        'status_filter',
        'limit_records',
        'include_header',
        'delimiter',
        'include_columns',
        'total_records',
        'status',
        'auto_process',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'export_date' => 'date',
        'date_from' => 'date',
        'date_to' => 'date',
        'file_size' => 'integer',
        'limit_records' => 'integer',
        'total_records' => 'integer',
        'include_header' => 'boolean',
        'include_columns' => 'array',
        'auto_process' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relationships
    public function bank()
    {
        return $this->belongsTo(Bank::class);
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

    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    public function scopeByFileType($query, $fileType)
    {
        return $query->where('file_type', $fileType);
    }

    public function scopeByStatusFilter($query, $statusFilter)
    {
        return $query->where('status_filter', $statusFilter);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('export_date', [$startDate, $endDate]);
    }

    public function scopeByExportDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_from', [$startDate, $endDate])
                    ->orWhereBetween('date_to', [$startDate, $endDate]);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('export_number', 'like', "%{$search}%")
              ->orWhere('file_name', 'like', "%{$search}%")
              ->orWhere('notes', 'like', "%{$search}%");
        });
    }

    // Accessors & Mutators
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="status-badge status-pending">Pending</span>',
            'processing' => '<span class="status-badge status-processing">Processing</span>',
            'completed' => '<span class="status-badge status-completed">Completed</span>',
            'failed' => '<span class="status-badge status-failed">Failed</span>'
        ];

        return $badges[$this->status] ?? '<span class="status-badge status-pending">Unknown</span>';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed'
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFileTypeLabelAttribute()
    {
        $labels = [
            'csv' => 'CSV',
            'xlsx' => 'Excel',
            'txt' => 'Text'
        ];

        return $labels[$this->file_type] ?? strtoupper($this->file_type);
    }

    public function getStatusFilterLabelAttribute()
    {
        $labels = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'expired' => 'Expired',
            'all' => 'All'
        ];

        return $labels[$this->status_filter] ?? ucfirst($this->status_filter);
    }

    public function getFormattedExportDateAttribute()
    {
        return $this->export_date ? $this->export_date->format('d/M/Y') : '-';
    }

    public function getFormattedDateFromAttribute()
    {
        return $this->date_from ? $this->date_from->format('d/M/Y') : '-';
    }

    public function getFormattedDateToAttribute()
    {
        return $this->date_to ? $this->date_to->format('d/M/Y') : '-';
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d/M/Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d/M/Y H:i');
    }

    public function getFormattedFileSizeAttribute()
    {
        if ($this->file_size) {
            $bytes = $this->file_size;
            $units = ['B', 'KB', 'MB', 'GB'];
            
            for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                $bytes /= 1024;
            }
            
            return round($bytes, 2) . ' ' . $units[$i];
        }
        
        return '-';
    }

    public function getIncludeColumnsLabelAttribute()
    {
        if (is_array($this->include_columns)) {
            return implode(', ', $this->include_columns);
        }
        
        return $this->include_columns ?? '-';
    }

    // Mutators
    public function setExportDateAttribute($value)
    {
        $this->attributes['export_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setDateFromAttribute($value)
    {
        $this->attributes['date_from'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setDateToAttribute($value)
    {
        $this->attributes['date_to'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function setFileSizeAttribute($value)
    {
        $this->attributes['file_size'] = $value ? (int) $value : null;
    }

    public function setLimitRecordsAttribute($value)
    {
        $this->attributes['limit_records'] = $value ? (int) $value : null;
    }

    public function setTotalRecordsAttribute($value)
    {
        $this->attributes['total_records'] = $value ? (int) $value : 0;
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

    public function canBeProcessed()
    {
        return $this->isPending() || $this->isFailed();
    }

    public function canBeDownloaded()
    {
        return $this->isCompleted() && $this->file_path && file_exists(storage_path('app/' . $this->file_path));
    }

    public function canBeDeleted()
    {
        return !$this->isProcessing();
    }

    public function getProgressPercentage()
    {
        if ($this->total_records > 0) {
            return round(($this->total_records / max($this->total_records, 1)) * 100, 2);
        }
        
        return 0;
    }
}