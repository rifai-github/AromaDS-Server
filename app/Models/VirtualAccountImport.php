<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VirtualAccountImport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_payment_id',
        'bank_report_no',
        'import_status',
        'report_date',
        'file_path',
        'total_transactions',
        'total_amount',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function bankPayment()
    {
        return $this->belongsTo(BankPayment::class);
    }

    public function transactions()
    {
        return $this->hasMany(VirtualAccountImportTransaction::class);
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
        return $query->where('import_status', $status);
    }

    public function scopeByBankPayment($query, $bankPaymentId)
    {
        return $query->where('bank_payment_id', $bankPaymentId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }

    public function scopeByBank($query, $bankId)
    {
        return $query->whereHas('bankPayment', function($q) use ($bankId) {
            $q->where('bank_id', $bankId);
        });
    }

    public function scopePending($query)
    {
        return $query->where('import_status', 'Pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('import_status', 'Processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('import_status', 'Completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('import_status', 'Failed');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'Pending' => 'badge-warning',
            'Processing' => 'badge-info',
            'Completed' => 'badge-success',
            'Failed' => 'badge-danger',
            'Warning' => 'badge-warning',
        ];

        return $badges[$this->import_status] ?? 'badge-secondary';
    }

    public function getFormattedTotalAmountAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getFormattedReportDateAttribute()
    {
        return $this->report_date->format('d M Y');
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

    // Methods
    public function isPending()
    {
        return $this->import_status === 'Pending';
    }

    public function isProcessing()
    {
        return $this->import_status === 'Processing';
    }

    public function isCompleted()
    {
        return $this->import_status === 'Completed';
    }

    public function isFailed()
    {
        return $this->import_status === 'Failed';
    }

    public function isWarning()
    {
        return $this->import_status === 'Warning';
    }

    public function canBeProcessed()
    {
        return in_array($this->import_status, ['Pending', 'Failed']);
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
}
