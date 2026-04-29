<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;

class TaxFileExport extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'export_number',
        'export_date',
        'export_type',
        'period_from',
        'period_to',
        'file_format',
        'total_records',
        'file_size',
        'include_details',
        'notes',
        'filter_parameters',
        'exported_at',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'export_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'exported_at' => 'datetime',
        'filter_parameters' => 'array',
        'include_details' => 'boolean',
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

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeGenerated($query)
    {
        return $query->where('status', 'generated');
    }

    public function scopeDownloaded($query)
    {
        return $query->where('status', 'downloaded');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('export_date', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'generated' => 'bg-blue-100 text-blue-800',
            'downloaded' => 'bg-green-100 text-green-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'generated' => 'Generated',
            'downloaded' => 'Downloaded',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedExportDateAttribute()
    {
        return $this->export_date->format('d M Y');
    }

    public function getFileSizeAttribute()
    {
        if (file_exists($this->file_path)) {
            $bytes = filesize($this->file_path);
            $units = ['B', 'KB', 'MB', 'GB'];
            
            for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
                $bytes /= 1024;
            }
            
            return round($bytes, 2) . ' ' . $units[$i];
        }
        
        return 'Unknown';
    }

    public function getInvoiceListAttribute()
    {
        if (!$this->filter_parameters || !isset($this->filter_parameters['invoice_ids'])) {
            return [];
        }

        $invoiceIds = $this->filter_parameters['invoice_ids'];
        if (empty($invoiceIds)) {
            return [];
        }

        // Get invoice numbers from TaxInvoice model
        $invoices = \App\Models\Finance\TaxInvoice::whereIn('id', $invoiceIds)
            ->pluck('invoice_number')
            ->toArray();

        return $invoices;
    }

    public function getFormattedInvoiceListAttribute()
    {
        $invoices = $this->invoice_list;
        
        if (empty($invoices)) {
            return 'All (Date Range)';
        }

        $count = count($invoices);
        if ($count <= 3) {
            return implode(', ', $invoices);
        }

        $visible = array_slice($invoices, 0, 2);
        $remaining = $count - 2;
        return implode(', ', $visible) . ", +{$remaining} others";
    }

    // Static helper methods
    public static function generateExportNumber()
    {
        $prefix = 'EXP';
        $date = now()->format('Ymd');
        $lastExport = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastExport) {
            $sequence = 1;
        } else {
            // Extract sequence from last export number
            $lastNumber = $lastExport->export_number;
            $parts = explode('-', $lastNumber);
            $sequence = isset($parts[2]) ? intval($parts[2]) + 1 : 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
