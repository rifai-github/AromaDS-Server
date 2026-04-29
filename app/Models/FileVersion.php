<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class FileVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'version_number',
        'original_name',
        'stored_name',
        'file_path',
        'mime_type',
        'file_extension',
        'file_size',
        'file_hash',
        'storage_driver',
        'metadata',
        'version_notes',
        'created_by'
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer'
    ];

    /**
     * Get the file that owns this version
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Get the user who created this version
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file URL
     */
    public function getUrl()
    {
        return Storage::disk($this->storage_driver)->url($this->file_path);
    }

    /**
     * Download this version
     */
    public function download()
    {
        return Storage::disk($this->storage_driver)->download($this->file_path, $this->original_name);
    }
}