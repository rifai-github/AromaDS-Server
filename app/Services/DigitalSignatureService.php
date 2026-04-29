<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DigitalSignatureService
{
    private $storagePath = 'public/job-reports/signatures';

    /**
     * Save digital signature
     */
    public function saveSignature($signatureData, $picName = null, $picPosition = null)
    {
        try {
            // Generate unique filename
            $filename = 'signature_' . now()->format('Y-m-d_H-i-s') . '_' . Str::random(8) . '.png';
            $path = $this->storagePath . '/' . $filename;

            // Decode base64 signature data
            $imageData = $this->decodeSignatureData($signatureData);
            
            if (!$imageData) {
                return [
                    'success' => false,
                    'message' => 'Invalid signature data'
                ];
            }

            // Save signature file
            Storage::put($path, $imageData);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $path,
                'url' => Storage::url($path),
                'pic_name' => $picName,
                'pic_position' => $picPosition,
                'signed_at' => now()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to save signature: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate signature data
     */
    public function validateSignature($signatureData)
    {
        // Check if it's valid base64
        if (!base64_decode($signatureData, true)) {
            return false;
        }

        // Check if it's a valid image
        $imageData = base64_decode($signatureData);
        $imageInfo = getimagesizefromstring($imageData);
        
        return $imageInfo !== false;
    }

    /**
     * Get signature URL
     */
    public function getSignatureUrl($filename)
    {
        $path = $this->storagePath . '/' . $filename;
        return Storage::url($path);
    }

    /**
     * Delete signature
     */
    public function deleteSignature($filename)
    {
        try {
            $path = $this->storagePath . '/' . $filename;
            if (Storage::exists($path)) {
                Storage::delete($path);
                return true;
            }
        } catch (\Exception $e) {
            // Log error
        }

        return false;
    }

    /**
     * Decode signature data from base64
     */
    private function decodeSignatureData($signatureData)
    {
        // Remove data URL prefix if present
        if (strpos($signatureData, 'data:image/png;base64,') === 0) {
            $signatureData = substr($signatureData, 22);
        }

        return base64_decode($signatureData);
    }

    /**
     * Create signature thumbnail
     */
    public function createSignatureThumbnail($filename, $width = 200, $height = 100)
    {
        try {
            $originalPath = $this->storagePath . '/' . $filename;
            $thumbnailPath = $this->storagePath . '/thumbnails/' . $filename;

            if (Storage::exists($originalPath)) {
                $image = \Intervention\Image\Facades\Image::make(storage_path('app/' . $originalPath));
                $image->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $image->save(storage_path('app/' . $thumbnailPath), 90);

                return Storage::url($thumbnailPath);
            }
        } catch (\Exception $e) {
            // Log error
        }

        return null;
    }

    /**
     * Get signature metadata
     */
    public function getSignatureMetadata($filename)
    {
        try {
            $path = $this->storagePath . '/' . $filename;
            if (Storage::exists($path)) {
                return [
                    'size' => Storage::size($path),
                    'created_at' => Storage::lastModified($path),
                    'url' => Storage::url($path)
                ];
            }
        } catch (\Exception $e) {
            // Log error
        }

        return null;
    }

    /**
     * Verify signature authenticity (basic check)
     */
    public function verifySignature($filename, $expectedPicName = null)
    {
        try {
            $path = $this->storagePath . '/' . $filename;
            
            if (!Storage::exists($path)) {
                return false;
            }

            // Check file size (signatures should be reasonable size)
            $size = Storage::size($path);
            if ($size < 1000 || $size > 100000) { // 1KB to 100KB
                return false;
            }

            // Additional verification logic can be added here
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
}
