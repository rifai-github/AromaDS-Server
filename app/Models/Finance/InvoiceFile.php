<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class InvoiceFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'file_name',
        'file_path',
        'file_type',
        'description',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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
    public function scopePdf($query)
    {
        return $query->where('file_type', 'pdf');
    }

    public function scopeImage($query)
    {
        return $query->whereIn('file_type', ['jpg', 'jpeg', 'png', 'gif']);
    }

    public function scopeDocument($query)
    {
        return $query->whereIn('file_type', ['doc', 'docx', 'xls', 'xlsx']);
    }

    // Accessors & Mutators
    public function getFileTypeBadgeAttribute()
    {
        $badges = [
            'pdf' => 'bg-red-100 text-red-800',
            'jpg' => 'bg-blue-100 text-blue-800',
            'jpeg' => 'bg-blue-100 text-blue-800',
            'png' => 'bg-blue-100 text-blue-800',
            'gif' => 'bg-blue-100 text-blue-800',
            'doc' => 'bg-green-100 text-green-800',
            'docx' => 'bg-green-100 text-green-800',
            'xls' => 'bg-yellow-100 text-yellow-800',
            'xlsx' => 'bg-yellow-100 text-yellow-800',
        ];

        return $badges[$this->file_type] ?? 'bg-gray-100 text-gray-800';
    }

    public function getFileTypeLabelAttribute()
    {
        return strtoupper($this->file_type);
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

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }
}
