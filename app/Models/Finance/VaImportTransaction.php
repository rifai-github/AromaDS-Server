<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class VaImportTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'virtual_account_import_id',
        'virtual_account_number',
        'customer_name',
        'amount',
        'transaction_date',
        'reference_number',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // Relationships
    public function virtualAccountImport()
    {
        return $this->belongsTo(VirtualAccountImport::class, 'virtual_account_import_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByImport($query, $importId)
    {
        return $query->where('virtual_account_import_id', $importId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'processed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'processed' => 'Processed',
            'failed' => 'Failed',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedTransactionDateAttribute()
    {
        return $this->transaction_date->format('d M Y');
    }
}
