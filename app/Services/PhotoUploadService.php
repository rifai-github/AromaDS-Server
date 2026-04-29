<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class PhotoUploadService
{
    private $storagePath = 'public/job-reports/photos';
    private $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    private $maxFileSize = 5 * 1024 * 1024; // 5MB

    /**
     * Upload photo with validation and optimization
     */
    public function uploadPhoto(UploadedFile $file, $type = 'general')
    {
        // Validate file
        if (!$this->validateFile($file)) {
            return [
                'success' => false,
                'message' => 'Invalid file format or size'
            ];
        }

        try {
            // Generate unique filename
            $filename = $this->generateFilename($file, $type);
            $path = $this->storagePath . '/' . $filename;

            // Optimize and save image
            $image = Image::make($file);
            
            // Resize if too large (max 1920x1080)
            if ($image->width() > 1920 || $image->height() > 1080) {
                $image->resize(1920, 1080, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Add watermark (optional)
            $this->addWatermark($image);

            // Save optimized image
            $image->save(storage_path('app/' . $path), 85); // 85% quality

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $path,
                'url' => Storage::url($path),
                'size' => $image->filesize(),
                'dimensions' => $image->width() . 'x' . $image->height()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload multiple photos
     */
    public function uploadMultiplePhotos(array $files, $type = 'general')
    {
        $results = [];
        
        foreach ($files as $file) {
            $result = $this->uploadPhoto($file, $type);
            $results[] = $result;
        }

        return $results;
    }

    /**
     * Delete photo
     */
    public function deletePhoto($filename)
    {
        try {
            $path = $this->storagePath . '/' . $filename;
            if (Storage::exists($path)) {
                Storage::delete($path);
                return true;
            }
        } catch (\Exception $e) {
            // Log error but don't throw
        }

        return false;
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrl($filename)
    {
        $path = $this->storagePath . '/' . $filename;
        return Storage::url($path);
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file)
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            return false;
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            return false;
        }

        // Check if it's actually an image
        if (!getimagesize($file->getPathname())) {
            return false;
        }

        return true;
    }

    /**
     * Generate unique filename
     */
    private function generateFilename(UploadedFile $file, $type)
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        return "{$type}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Add watermark to image
     */
    private function addWatermark($image)
    {
        // Add timestamp watermark
        $timestamp = now()->format('Y-m-d H:i:s');
        
        $image->text($timestamp, $image->width() - 10, $image->height() - 10, function($font) {
            $font->file(public_path('fonts/arial.ttf')); // You need to add this font
            $font->size(12);
            $font->color('#ffffff');
            $font->align('right');
            $font->valign('bottom');
        });
    }

    /**
     * Create thumbnail
     */
    public function createThumbnail($filename, $width = 300, $height = 200)
    {
        try {
            $originalPath = $this->storagePath . '/' . $filename;
            $thumbnailPath = $this->storagePath . '/thumbnails/' . $filename;

            if (Storage::exists($originalPath)) {
                $image = Image::make(storage_path('app/' . $originalPath));
                $image->fit($width, $height);
                $image->save(storage_path('app/' . $thumbnailPath), 80);

                return Storage::url($thumbnailPath);
            }
        } catch (\Exception $e) {
            // Log error
        }

        return null;
    }

    /**
     * Get photo metadata
     */
    public function getPhotoMetadata($filename)
    {
        try {
            $path = $this->storagePath . '/' . $filename;
            if (Storage::exists($path)) {
                $image = Image::make(storage_path('app/' . $path));
                
                return [
                    'width' => $image->width(),
                    'height' => $image->height(),
                    'size' => Storage::size($path),
                    'mime_type' => $image->mime(),
                    'created_at' => Storage::lastModified($path)
                ];
            }
        } catch (\Exception $e) {
            // Log error
        }

        return null;
    }
}
