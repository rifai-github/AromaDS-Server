<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxFileImportDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_file_import_id',
        'invoice_number',
        'tax_number',
        'buyer_npwp',
        'buyer_name',
        'tax_date',
        'tax_amount',
        'dpp',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tax_date' => 'date',
        'tax_amount' => 'decimal:2',
        'dpp' => 'decimal:2',
    ];

    // Relationships
    public function taxFileImport()
    {
        return $this->belongsTo(TaxFileImport::class);
    }

    /**
     * The invoice this faktur pajak was matched against, if any — resolves to
     * null for rejected rows, where invoice_number is the literal 'N/A'.
     */
    public function matchedInvoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_number', 'invoice_number');
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

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeWarning($query)
    {
        return $query->where('status', 'warning');
    }

    public function scopeByImport($query, $importId)
    {
        return $query->where('tax_file_import_id', $importId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tax_date', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getFormattedTaxAmountAttribute()
    {
        return 'Rp '.number_format($this->tax_amount, 0, ',', '.');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'warning' => 'bg-orange-100 text-orange-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'warning' => 'Warning',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedTaxDateAttribute()
    {
        return $this->tax_date->format('d M Y');
    }

    public function getFormattedDppAttribute()
    {
        return $this->dpp === null ? '-' : 'Rp '.number_format((float) $this->dpp, 0, ',', '.');
    }
}
