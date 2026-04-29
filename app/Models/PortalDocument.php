<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrail;

class PortalDocument extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'customer_id',
        'document_type',
        'document_name',
        'file_path',
        'file_size',
        'mime_type',
        'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    // Accessors
    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    public function getFileExtensionAttribute()
    {
        return pathinfo($this->document_name, PATHINFO_EXTENSION);
    }

    public function getIsImageAttribute()
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function getIsPdfAttribute()
    {
        return $this->mime_type === 'application/pdf';
    }
}
