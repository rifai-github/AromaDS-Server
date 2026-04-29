<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\File;
use App\Models\FileCategory;
use App\Models\FileAccessLog;

class FileController extends Controller
{
    /**
     * Display a listing of files
     */
    public function index(Request $request)
    {
        $query = File::with(['category', 'uploader'])->notExpired();

        // Filter by category
        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('original_name', 'like', "%{$search}%");
        }

        $files = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $files,
                'categories' => FileCategory::active()->get()
            ]);
        }

        return view('files.index', compact('files'));
    }

    /**
     * Store a newly uploaded file
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:500000', // 500MB max
            'category_id' => 'required|exists:file_categories,id',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $uploadedFile = $request->file('file');
            $category = FileCategory::findOrFail($request->category_id);

            // Validate file extension
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            if (!$category->isExtensionAllowed($extension)) {
                return response()->json([
                    'success' => false,
                    'message' => "File extension '{$extension}' is not allowed for this category."
                ], 422);
            }

            // Validate file size
            if (!$category->isSizeAllowed($uploadedFile->getSize())) {
                return response()->json([
                    'success' => false,
                    'message' => "File size exceeds the maximum allowed size of {$category->formatted_max_size}."
                ], 422);
            }

            // Generate unique filename
            $originalName = $uploadedFile->getClientOriginalName();
            $storedName = Str::uuid() . '.' . $extension;
            $filePath = 'files/' . date('Y/m/d') . '/' . $storedName;

            // Store file
            $storedPath = $uploadedFile->storeAs('files/' . date('Y/m/d'), $storedName, 'public');

            // Calculate file hash
            $fileHash = hash_file('sha256', $uploadedFile->getRealPath());

            // Create file record
            $file = File::create([
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'file_path' => $storedPath,
                'mime_type' => $uploadedFile->getMimeType(),
                'file_extension' => $extension,
                'file_size' => $uploadedFile->getSize(),
                'file_hash' => $fileHash,
                'category_id' => $category->id,
                'uploaded_by' => auth()->id(),
                'metadata' => [
                    'description' => $request->description,
                    'upload_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                'is_public' => $request->boolean('is_public', false),
            ]);

            // Log upload
            FileAccessLog::create([
                'file_id' => $file->id,
                'user_id' => auth()->id(),
                'action' => 'upload',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'data' => $file->load(['category', 'uploader'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download the specified file
     */
    public function download(File $file)
    {
        // Check access permission
        if (!$file->canAccess(auth()->user())) {
            abort(403, 'You do not have permission to download this file.');
        }

        // Log download
        FileAccessLog::create([
            'file_id' => $file->id,
            'user_id' => auth()->id(),
            'action' => 'download',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $file->download();
    }

    /**
     * Remove the specified file
     */
    public function destroy(File $file): JsonResponse
    {
        // Check if user can delete
        if ($file->uploaded_by !== auth()->id() && !auth()->user()->hasPermission('admin.delete')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya dapat menghapus file milik sendiri atau memerlukan permission Admin Delete.'
            ], 403);
        }

        try {
            // Delete physical file
            Storage::disk('public')->delete($file->file_path);

            // Log deletion
            FileAccessLog::create([
                'file_id' => $file->id,
                'user_id' => auth()->id(),
                'action' => 'delete',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Soft delete file record
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file categories
     */
    public function categories(): JsonResponse
    {
        $categories = FileCategory::active()->get();
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}