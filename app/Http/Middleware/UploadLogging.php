<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class UploadLogging
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Log upload before processing
        if ($this->hasFileUpload($request)) {
            $this->logUpload($request);
        }

        $response = $next($request);

        return $response;
    }

    /**
     * Check if request has file upload
     */
    protected function hasFileUpload(Request $request)
    {
        return $request->hasFile('file') || 
               $request->hasFile('document') || 
               $request->hasFile('image') ||
               $request->hasFile('attachment') ||
               $request->hasFile('upload');
    }

    /**
     * Log upload activity
     */
    protected function logUpload(Request $request)
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $uploadedFiles = [];

        // Get all uploaded files
        foreach ($request->allFiles() as $fieldName => $files) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    $uploadedFiles[] = [
                        'field_name' => $fieldName,
                        'original_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'extension' => $file->getClientOriginalExtension()
                    ];
                }
            } else {
                $uploadedFiles[] = [
                    'field_name' => $fieldName,
                    'original_name' => $files->getClientOriginalName(),
                    'file_size' => $files->getSize(),
                    'mime_type' => $files->getMimeType(),
                    'extension' => $files->getClientOriginalExtension()
                ];
            }
        }

        foreach ($uploadedFiles as $fileInfo) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'file_uploaded',
                'model_type' => 'FileUpload',
                'model_id' => 0,
                'old_values' => null,
                'new_values' => [
                    'file_name' => $fileInfo['original_name'],
                    'file_size' => $fileInfo['file_size'],
                    'file_type' => $fileInfo['mime_type'],
                    'file_extension' => $fileInfo['extension'],
                    'upload_field' => $fileInfo['field_name'],
                    'upload_url' => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip()
                ],
                'changed_fields' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }
}
