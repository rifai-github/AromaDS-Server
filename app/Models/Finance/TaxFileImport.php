<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;

class TaxFileImport extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'import_number',
        'import_date',
        'file_path',
        'status',
        'record_count',
        'not_approved',
        'rejected',
        'approval_success',
        'warning',
        'internal_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'import_date' => 'date',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function taxFileImportDetails()
    {
        return $this->hasMany(TaxFileImportDetail::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
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
            'processed' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'processed' => 'Processed',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedImportDateAttribute()
    {
        return $this->import_date->format('d M Y');
    }

    public function getSuccessRateAttribute()
    {
        if ($this->record_count == 0) {
            return 0;
        }

        return round(($this->approval_success / $this->record_count) * 100, 2);
    }

    public function getFormattedSuccessRateAttribute()
    {
        return $this->success_rate . '%';
    }
}
