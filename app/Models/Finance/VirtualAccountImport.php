<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;
use App\Models\Finance\Bank;
use Illuminate\Support\Str;
use Carbon\Carbon;

class VirtualAccountImport extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'import_number',
        'import_date',
        'bank_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'skip_header',
        'delimiter',
        'encoding',
        'va_number_column',
        'customer_name_column',
        'amount_column',
        'due_date_column',
        'total_records',
        'processed_records',
        'success_count',
        'failed_count',
        'status',
        'auto_process',
        'description',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'import_date' => 'date',
        'skip_header' => 'boolean',
        'auto_process' => 'boolean',
        'file_size' => 'integer',
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
    ];

    // Relationships
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function vaImportTransactions()
    {
        return $this->hasMany(VaImportTransaction::class, 'virtual_account_import_id');
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

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('import_date', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedImportDateAttribute()
    {
        return $this->import_date->format('d M Y');
    }

    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getSuccessRateAttribute()
    {
        if ($this->total_records == 0) {
            return 0;
        }

        return round(($this->success_count / $this->total_records) * 100, 2);
    }

    public function getFormattedSuccessRateAttribute()
    {
        return $this->success_rate . '%';
    }

    // Auto-generation methods
    public static function generateImportNumber()
    {
        $prefix = 'VA-IMP';
        $date = Carbon::now()->format('Ymd');
        $counter = 1;
        
        do {
            $importNumber = $prefix . '-' . $date . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
            $counter++;
        } while (self::where('import_number', $importNumber)->exists());
        
        return $importNumber;
    }

    // Additional accessors for BRD compliance
    public function getImportDateFormattedAttribute()
    {
        return $this->import_date ? $this->import_date->format('d/M/Y') : 'N/A';
    }

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at ? $this->created_at->format('d/M/Y H:i') : 'N/A';
    }

    // Business logic methods
    public function canProcess()
    {
        return $this->status === 'pending';
    }

    public function canRetry()
    {
        return $this->status === 'failed';
    }

    public function canDelete()
    {
        return in_array($this->status, ['pending', 'failed']);
    }

    public function markAsProcessing()
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
    }

    public function updateProcessedRecords($count)
    {
        $this->update(['processed_records' => $count]);
    }

    public function incrementSuccessCount()
    {
        $this->increment('success_count');
    }

    public function incrementFailedCount()
    {
        $this->increment('failed_count');
    }

    // Boot method for auto-generation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->import_number)) {
                $model->import_number = self::generateImportNumber();
            }
            if (empty($model->import_date)) {
                $model->import_date = Carbon::now();
            }
            if (empty($model->status)) {
                $model->status = 'pending';
            }
            if (empty($model->total_records)) {
                $model->total_records = 0;
            }
            if (empty($model->processed_records)) {
                $model->processed_records = 0;
            }
            if (empty($model->success_count)) {
                $model->success_count = 0;
            }
            if (empty($model->failed_count)) {
                $model->failed_count = 0;
            }
        });
    }
}