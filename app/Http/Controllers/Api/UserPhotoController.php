<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class UserPhotoController extends Controller
{
    /**
     * Serve user profile photo with proper CORS headers
     */
    public function show(Request $request, $userId = null)
    {
        try {
            // Check authentication first
            if (!$request->user()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $user = $request->user();
            
            // If userId is provided, check if it matches authenticated user
            // Otherwise, serve current user's photo
            $targetUserId = $userId ?? $user->id;
            
            // Only allow users to access their own photos
            if ($targetUserId != $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }
            
            if (!$user->photo_file_path || empty(trim($user->photo_file_path))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Photo not found'
                ], 404);
            }

            // Langsung ke public/uploads/ tanpa Storage disk
            $filePath = public_path('uploads/' . $user->photo_file_path);
            
            // Check if file exists
            if (!file_exists($filePath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Photo file not found: ' . $user->photo_file_path
                ], 404);
            }

            // Get file mime type
            $mimeType = mime_content_type($filePath);
            if (!$mimeType) {
                $mimeType = 'image/jpeg'; // Default fallback
            }

            // Read file content
            $fileContent = @file_get_contents($filePath);
            
            if ($fileContent === false) {
                \Log::error('UserPhotoController: Cannot read file', ['path' => $filePath]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot read photo file'
                ], 500);
            }

            // Return file dengan CORS headers menggunakan response() biasa
            $response = response($fileContent, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Length', strlen($fileContent))
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type');

            return $response;

        } catch (\Exception $e) {
            \Log::error('UserPhotoController: Exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}

