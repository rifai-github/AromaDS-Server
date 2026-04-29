<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class TaxReport extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'report_number',
        'report_type',
        'report_name',
        'period_start',
        'period_end',
        'status',
        'report_data',
        'total_ppn',
        'total_pph',
        'total_tax',
        'total_invoices',
        'e_spt_data',
        'e_spt_file_path',
        'e_spt_submitted_at',
        'e_spt_reference',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_ppn' => 'decimal:2',
        'total_pph' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_invoices' => 'integer',
        'report_data' => 'array',
        'e_spt_data' => 'array',
        'e_spt_submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
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
    public function scopeByReportType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
                    ->where('period_end', '<=', $endDate);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeGenerated($query)
    {
        return $query->where('status', 'generated');
    }

    // Accessors
    public function getFormattedPeriodStartAttribute()
    {
        return $this->period_start ? $this->period_start->format('d/M/Y') : '-';
    }

    public function getFormattedPeriodEndAttribute()
    {
        return $this->period_end ? $this->period_end->format('d/M/Y') : '-';
    }

    public function getFormattedTotalPpnAttribute()
    {
        return 'Rp ' . number_format($this->total_ppn, 0, ',', '.');
    }

    public function getFormattedTotalPphAttribute()
    {
        return 'Rp ' . number_format($this->total_pph, 0, ',', '.');
    }

    public function getFormattedTotalTaxAttribute()
    {
        return 'Rp ' . number_format($this->total_tax, 0, ',', '.');
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d/M/Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d/M/Y H:i');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => '<span class="badge badge-secondary">Draft</span>',
            'generated' => '<span class="badge badge-info">Generated</span>',
            'submitted' => '<span class="badge badge-warning">Submitted</span>',
            'approved' => '<span class="badge badge-success">Approved</span>',
            'rejected' => '<span class="badge badge-danger">Rejected</span>'
        ];
        
        return $badges[$this->status] ?? '<span class="badge badge-secondary">Unknown</span>';
    }

    public function getReportTypeLabelAttribute()
    {
        $labels = [
            'monthly' => 'Monthly Report',
            'quarterly' => 'Quarterly Report',
            'annual' => 'Annual Report',
            'custom' => 'Custom Report'
        ];
        
        return $labels[$this->report_type] ?? ucfirst($this->report_type);
    }

    public function getPeriodDurationAttribute()
    {
        if (!$this->period_start || !$this->period_end) {
            return '-';
        }
        
        return $this->period_start->format('d/M/Y') . ' - ' . $this->period_end->format('d/M/Y');
    }

    // Helper methods
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isGenerated()
    {
        return $this->status === 'generated';
    }

    public function isSubmitted()
    {
        return $this->status === 'submitted';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function canBeEdited()
    {
        return in_array($this->status, ['draft', 'generated']);
    }

    public function canBeSubmitted()
    {
        return $this->status === 'generated';
    }

    public function canBeApproved()
    {
        return $this->status === 'submitted';
    }

    public function canBeRejected()
    {
        return $this->status === 'submitted';
    }

    public function generateReportData()
    {
        // Generate report data based on period and type
        $startDate = $this->period_start;
        $endDate = $this->period_end;
        
        // Get tax invoices for the period
        $taxInvoices = TaxInvoice::whereBetween('invoice_date', [$startDate, $endDate])
                                ->where('status', '!=', 'cancelled')
                                ->get();
        
        $reportData = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'summary' => [
                'total_invoices' => $taxInvoices->count(),
                'total_subtotal' => $taxInvoices->sum('subtotal'),
                'total_tax' => $taxInvoices->sum('tax_amount'),
                'total_amount' => $taxInvoices->sum('total_amount')
            ],
            'tax_breakdown' => [
                'ppn' => $taxInvoices->where('tax_status', 'applied')->sum('tax_amount'),
                'pph' => 0, // Calculate PPH separately
                'exempt' => $taxInvoices->where('tax_status', 'exempt')->count()
            ],
            'invoices' => $taxInvoices->map(function($invoice) {
                return [
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name ?? 'Unknown',
                    'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                    'subtotal' => $invoice->subtotal,
                    'tax_amount' => $invoice->tax_amount,
                    'total_amount' => $invoice->total_amount,
                    'tax_status' => $invoice->tax_status
                ];
            })
        ];
        
        $this->update([
            'report_data' => $reportData,
            'total_invoices' => $reportData['summary']['total_invoices'],
            'total_ppn' => $reportData['tax_breakdown']['ppn'],
            'total_pph' => $reportData['tax_breakdown']['pph'],
            'total_tax' => $reportData['summary']['total_tax'],
            'status' => 'generated'
        ]);
        
        return $reportData;
    }

    public function generateESPTData()
    {
        // Generate e-SPT compatible data
        $eSPTData = [
            'header' => [
                'report_number' => $this->report_number,
                'period' => $this->period_start->format('Y-m-d') . ' to ' . $this->period_end->format('Y-m-d'),
                'generated_at' => now()->format('Y-m-d H:i:s')
            ],
            'summary' => [
                'total_ppn' => $this->total_ppn,
                'total_pph' => $this->total_pph,
                'total_tax' => $this->total_tax,
                'total_invoices' => $this->total_invoices
            ],
            'details' => $this->report_data['invoices'] ?? []
        ];
        
        $this->update([
            'e_spt_data' => $eSPTData,
            'status' => 'generated'
        ]);
        
        return $eSPTData;
    }
}