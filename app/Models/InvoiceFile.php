<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'file_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'description',
        'is_public',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'uploaded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes
    public function scopeByInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeByFileType($query, $fileType)
    {
        return $query->where('file_type', $fileType);
    }

    public function scopeByUploadedBy($query, $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('uploaded_at', [$startDate, $endDate]);
    }

    public function scopeByMimeType($query, $mimeType)
    {
        return $query->where('mime_type', 'like', "%{$mimeType}%");
    }

    // Accessors
    public function getFileSizeFormattedAttribute()
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $size = (int) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('uploads/' . $this->file_path) : null;
    }

    public function getUploadedAtFormattedAttribute()
    {
        return $this->uploaded_at ? $this->uploaded_at->format('d M Y H:i') : 'N/A';
    }

    public function getFileTypeLabelAttribute()
    {
        $fileTypes = [
            'invoice_document' => 'Invoice Document',
            'receipt' => 'Receipt',
            'attachment' => 'Attachment',
            'payment_proof' => 'Payment Proof',
            'tax_document' => 'Tax Document',
            'other' => 'Other',
        ];

        return $fileTypes[$this->file_type] ?? ucfirst(str_replace('_', ' ', $this->file_type));
    }

    public function getMimeTypeLabelAttribute()
    {
        $mimeTypes = [
            'application/pdf' => 'PDF',
            'application/msword' => 'Word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word',
            'application/vnd.ms-excel' => 'Excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel',
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'image/gif' => 'GIF',
        ];

        return $mimeTypes[$this->mime_type] ?? $this->mime_type;
    }

    public function getIsImageAttribute()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function getIsPdfAttribute()
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getIsDocumentAttribute()
    {
        return str_contains($this->mime_type, 'word') || str_contains($this->mime_type, 'excel');
    }

    // Methods
    public function canDownload()
    {
        return file_exists(public_path('uploads/' . $this->file_path));
    }

    public function canDelete()
    {
        return auth()->id() === $this->uploaded_by || auth()->user()->hasRole('admin');
    }

    public function canView()
    {
        return $this->is_public || auth()->id() === $this->uploaded_by || auth()->user()->hasRole('admin');
    }

    public function getFileExistsAttribute()
    {
        return file_exists(public_path('uploads/' . $this->file_path));
    }

    public function getInvoiceInfo()
    {
        return $this->invoice;
    }

    public function getUploaderInfo()
    {
        return $this->uploadedBy;
    }

    public function getCustomerInfo()
    {
        return $this->invoice->customer ?? null;
    }

    public function getContractInfo()
    {
        return $this->invoice->contract ?? null;
    }

    public function togglePublic()
    {
        $this->update(['is_public' => !$this->is_public]);
    }

    public function isInvoiceDocument()
    {
        return $this->file_type === 'invoice_document';
    }

    public function isReceipt()
    {
        return $this->file_type === 'receipt';
    }

    public function isPaymentProof()
    {
        return $this->file_type === 'payment_proof';
    }

    public function isTaxDocument()
    {
        return $this->file_type === 'tax_document';
    }
}
