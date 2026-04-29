<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceFormFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_form_id',
        'file_type',
        'file_path',
        'file_name',
        'file_size',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // Relationships
    public function invoiceForm()
    {
        return $this->belongsTo(InvoiceForm::class);
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    // Accessors
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFileTypeLabelAttribute()
    {
        $labels = [
            'invoice' => 'Invoice',
            'receipt' => 'Receipt',
            'contract' => 'Contract',
            'other' => 'Other',
        ];
        return $labels[$this->file_type] ?? ucfirst($this->file_type);
    }
}
