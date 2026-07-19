<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TaxFileImport extends Model
{
    use HasFactory, SoftDeletes;

    public const DELIMITER_COMMA = ',';

    public const DELIMITER_SEMICOLON = ';';

    public const DELIMITER_TAB = '\t';

    public const DELIMITERS = [
        self::DELIMITER_COMMA,
        self::DELIMITER_SEMICOLON,
        self::DELIMITER_TAB,
    ];

    protected $fillable = [
        'import_number',
        'file_name',
        'import_date',
        'bank_id',
        'file_format',
        'total_records',
        'success_count',
        'failed_count',
        'success_rate',
        'auto_process',
        'skip_header',
        'delimiter',
        'notes',
        'error_log',
        'status',
        'processed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'import_date' => 'date',
        'total_records' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
        'success_rate' => 'decimal:2',
        'auto_process' => 'boolean',
        'skip_header' => 'boolean',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function details()
    {
        return $this->hasMany(\App\Models\Finance\TaxFileImportDetail::class);
    }

    public function bank()
    {
        return $this->belongsTo(\App\Models\Bank::class);
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
    public function scopeByImportNumber($query, $importNumber)
    {
        return $query->where('import_number', 'like', "%{$importNumber}%");
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('import_date', [$startDate, $endDate]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    public function scopeByFileFormat($query, $format)
    {
        return $query->where('file_format', $format);
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

    public function scopeWithErrors($query)
    {
        return $query->where('failed_count', '>', 0);
    }

    public function scopeWithSuccess($query)
    {
        return $query->where('success_count', '>', 0);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case 'pending':
                return 'status-pending';
            case 'processing':
                return 'status-processing';
            case 'completed':
                return 'status-completed';
            case 'failed':
                return 'status-failed';
            default:
                return 'status-pending';
        }
    }

    public function getFormattedImportDateAttribute()
    {
        return $this->import_date ? $this->import_date->format('d/M/Y') : '-';
    }

    public function getFormattedProcessedAtAttribute()
    {
        return $this->processed_at ? $this->processed_at->format('d/M/Y H:i') : '-';
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d/M/Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d/M/Y H:i');
    }

    public function getFormattedSuccessRateAttribute()
    {
        return number_format($this->success_rate, 2) . '%';
    }

    public function getFormatLabelAttribute()
    {
        switch ($this->file_format) {
            case 'csv':
                return 'CSV';
            case 'xlsx':
                return 'Excel';
            case 'xls':
                return 'Excel (Legacy)';
            default:
                return strtoupper($this->file_format);
        }
    }

    public function getDelimiterLabelAttribute()
    {
        switch ($this->delimiter) {
            case self::DELIMITER_COMMA:
                return 'Comma (,)';
            case self::DELIMITER_SEMICOLON:
                return 'Semicolon (;)';
            case self::DELIMITER_TAB:
            case "\t":
                return 'Tab';
            default:
                return $this->delimiter;
        }
    }

    // Methods
    public function hasErrors()
    {
        return $this->failed_count > 0;
    }

    public function hasSuccess()
    {
        return $this->success_count > 0;
    }

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

    public function canProcess()
    {
        return $this->status === 'pending';
    }

    public function canDelete()
    {
        return in_array($this->status, ['pending', 'failed']);
    }

    public function canDownload()
    {
        return $this->status === 'completed' && $this->file_name;
    }

    public function calculateSuccessRate()
    {
        if ($this->total_records == 0) {
            return 0;
        }
        return round(($this->success_count / $this->total_records) * 100, 2);
    }

    public function updateSuccessRate()
    {
        $this->success_rate = $this->calculateSuccessRate();
        $this->save();
    }

    // Static methods
    public static function generateImportNumber()
    {
        $prefix = 'TFI';
        $date = date('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        
        return $prefix . '-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // Mutators
    public function setImportDateAttribute($value)
    {
        $this->attributes['import_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }
}
