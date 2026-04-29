<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'document_type',
        'document_name',
        'file_path',
        'file_size',
        'mime_type',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByDocumentType($query, $documentType)
    {
        return $query->where('document_type', $documentType);
    }

    public function scopeByMimeType($query, $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopePdfs($query)
    {
        return $query->where('mime_type', 'application/pdf');
    }

    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }

    // Accessors
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDocumentTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->document_type));
    }

    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getFileExtensionAttribute()
    {
        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }

    public function getFileNameAttribute()
    {
        return pathinfo($this->file_path, PATHINFO_FILENAME);
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
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }

    // Business Logic Methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }

    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    public function getFullFilePath()
    {
        return storage_path('app/' . $this->file_path);
    }

    public function getPublicUrl()
    {
        return asset('storage/' . $this->file_path);
    }

    public function exists()
    {
        return file_exists($this->getFullFilePath());
    }

    public function deleteFile()
    {
        if ($this->exists()) {
            return unlink($this->getFullFilePath());
        }
        return false;
    }

    // Static Methods
    public static function getDocumentTypes()
    {
        return [
            'contract' => 'Contract',
            'invoice' => 'Invoice',
            'certificate' => 'Certificate',
            'other' => 'Other'
        ];
    }

    public static function getAllowedMimeTypes()
    {
        return [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv'
        ];
    }

    public static function getMaxFileSize()
    {
        return 10 * 1024 * 1024; // 10MB
    }

    public static function getTotalSizeByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->where('is_active', true)
                  ->sum('file_size');
    }

    public static function getCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->where('is_active', true)
                  ->count();
    }

    public static function getCountByDocumentType($companyId, $documentType)
    {
        return self::where('company_id', $companyId)
                  ->where('document_type', $documentType)
                  ->where('is_active', true)
                  ->count();
    }
}
