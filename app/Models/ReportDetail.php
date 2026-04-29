<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'report_id',
        'report_type',
        'sub_type',
        'parameters',
        'columns',
        'data',
        'file_path',
        'format',
        'status',
        'generated_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'columns' => 'array',
        'data' => 'array',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    // Scopes
    public function scopeByReport($query, $reportId)
    {
        return $query->where('report_id', $reportId);
    }

    public function scopeByType($query, $reportType)
    {
        return $query->where('report_type', $reportType);
    }

    public function scopeBySubType($query, $subType)
    {
        return $query->where('sub_type', $subType);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByFormat($query, $format)
    {
        return $query->where('format', $format);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('generated_at', [$startDate, $endDate]);
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
            'pending' => 'badge-warning',
            'processing' => 'badge-info',
            'completed' => 'badge-success',
            'failed' => 'badge-danger',
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }

    public function getFormatLabelAttribute()
    {
        $formats = [
            'pdf' => 'PDF',
            'excel' => 'Excel',
            'csv' => 'CSV',
        ];

        return $formats[$this->format] ?? strtoupper($this->format);
    }

    public function getReportTypeLabelAttribute()
    {
        $types = [
            'warehouse' => 'Warehouse',
            'operational' => 'Operational',
            'finance' => 'Finance',
            'marketing' => 'Marketing',
        ];

        return $types[$this->report_type] ?? ucfirst($this->report_type);
    }

    public function getSubTypeLabelAttribute()
    {
        $subTypes = [
            'stock_opname' => 'Stock Opname',
            'stock' => 'Stock',
            'customer_service' => 'Customer Service',
            'unit_on_wall' => 'Unit on Wall',
            'inventory_movement' => 'Inventory Movement',
            'invoice' => 'Invoice',
            'contract' => 'Contract',
            'prospect' => 'Prospect',
            'survey' => 'Survey',
        ];

        return $subTypes[$this->sub_type] ?? ucfirst(str_replace('_', ' ', $this->sub_type));
    }

    public function getGeneratedAtFormattedAttribute()
    {
        return $this->generated_at ? $this->generated_at->format('d M Y H:i') : 'N/A';
    }

    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('uploads/' . $this->file_path) : null;
    }

    public function getFileSizeAttribute()
    {
        if ($this->file_path && file_exists(public_path('uploads/' . $this->file_path))) {
            return number_format(filesize(public_path('uploads/' . $this->file_path)) / 1024, 2) . ' KB';
        }
        return 'N/A';
    }

    public function getFileExistsAttribute()
    {
        return $this->file_path && file_exists(public_path('uploads/' . $this->file_path));
    }

    public function getParametersArrayAttribute()
    {
        return is_array($this->parameters) ? $this->parameters : [];
    }

    public function getColumnsArrayAttribute()
    {
        return is_array($this->columns) ? $this->columns : [];
    }

    public function getDataArrayAttribute()
    {
        return is_array($this->data) ? $this->data : [];
    }

    // Methods
    public function canDownload()
    {
        return $this->status === 'completed' && $this->fileExists;
    }

    public function canDelete()
    {
        return in_array($this->status, ['pending', 'failed']);
    }

    public function canRegenerate()
    {
        return in_array($this->status, ['failed']);
    }

    public function getReportInfo()
    {
        return $this->report;
    }

    public function getParametersValue($key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }

    public function getColumnsValue($key, $default = null)
    {
        return $this->columns[$key] ?? $default;
    }

    public function getDataValue($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function setParametersValue($key, $value)
    {
        $parameters = $this->parameters ?? [];
        $parameters[$key] = $value;
        $this->parameters = $parameters;
    }

    public function setColumnsValue($key, $value)
    {
        $columns = $this->columns ?? [];
        $columns[$key] = $value;
        $this->columns = $columns;
    }

    public function setDataValue($key, $value)
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->data = $data;
    }

    public function markAsProcessing()
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'generated_at' => now(),
        ]);
    }

    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
    }

    public function isWarehouseReport()
    {
        return $this->report_type === 'warehouse';
    }

    public function isOperationalReport()
    {
        return $this->report_type === 'operational';
    }

    public function isFinanceReport()
    {
        return $this->report_type === 'finance';
    }

    public function isMarketingReport()
    {
        return $this->report_type === 'marketing';
    }
}
