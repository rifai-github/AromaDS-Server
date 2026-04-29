<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class FileUploadHelper
{
    /**
     * Ensure directory exists and create if not
     */
    public static function ensureDirectoryExists($disk, $path)
    {
        $fullPath = Storage::disk($disk)->path($path);
        
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        return $fullPath;
    }
    
    /**
     * Store file with auto directory creation
     */
    public static function storeFile($file, $path, $disk = 'public')
    {
        try {
            // Ensure directory exists
            self::ensureDirectoryExists($disk, dirname($path));
            
            // Store the file
            $storedPath = $file->store(dirname($path), $disk);
            
            // Log for debugging
            \Log::info('FileUploadHelper::storeFile', [
                'original_path' => $path,
                'stored_path' => $storedPath,
                'disk' => $disk,
                'file_size' => $file->getSize(),
                'file_name' => $file->getClientOriginalName()
            ]);
            
            return $storedPath;
            
        } catch (\Exception $e) {
            \Log::error('FileUploadHelper::storeFile failed', [
                'path' => $path,
                'disk' => $disk,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
