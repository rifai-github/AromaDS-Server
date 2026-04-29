<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'export_name',
        'export_type',
        'query',
        'parameters',
        'file_format',
        'status',
        'file_path',
        'created_by'
    ];

    protected $casts = [
        'parameters' => 'array'
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('export_type', $type);
    }

    public function scopeByFormat($query, $format)
    {
        return $query->where('file_format', $format);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('export_name', 'like', "%{$name}%");
    }

    // Accessors
    public function getTypeTextAttribute()
    {
        $types = [
            'standard' => 'Standard',
            'custom' => 'Custom'
        ];
        return $types[$this->export_type] ?? $this->export_type;
    }

    public function getFormatTextAttribute()
    {
        $formats = [
            'csv' => 'CSV',
            'excel' => 'Excel',
            'pdf' => 'PDF',
            'json' => 'JSON'
        ];
        return $formats[$this->file_format] ?? $this->file_format;
    }

    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed'
        ];
        return $statuses[$this->status] ?? $this->status;
    }

    public function getFileSizeAttribute()
    {
        if ($this->file_path && file_exists($this->file_path)) {
            return filesize($this->file_path);
        }
        return 0;
    }

    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
