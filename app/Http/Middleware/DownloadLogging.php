<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class DownloadLogging
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
        $response = $next($request);

        // Check if this is a download response
        if ($this->isDownloadResponse($response)) {
            $this->logDownload($request, $response);
        }

        return $response;
    }

    /**
     * Check if response is a download
     */
    protected function isDownloadResponse($response)
    {
        $contentDisposition = $response->headers->get('Content-Disposition');
        $contentType = $response->headers->get('Content-Type');
        
        return $contentDisposition && 
               (str_contains($contentDisposition, 'attachment') || 
                str_contains($contentDisposition, 'download')) ||
               in_array($contentType, [
                   'application/pdf',
                   'application/vnd.ms-excel',
                   'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                   'text/csv',
                   'application/zip',
                   'application/x-zip-compressed'
               ]);
    }

    /**
     * Log download activity
     */
    protected function logDownload(Request $request, $response)
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $fileName = $this->extractFileName($response);
        $fileType = $response->headers->get('Content-Type');
        $fileSize = $response->headers->get('Content-Length');

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'file_downloaded',
            'model_type' => 'FileDownload',
            'model_id' => 0,
            'old_values' => null,
            'new_values' => [
                'file_name' => $fileName,
                'file_type' => $fileType,
                'file_size' => $fileSize,
                'download_url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip()
            ],
            'changed_fields' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Extract filename from response headers
     */
    protected function extractFileName($response)
    {
        $contentDisposition = $response->headers->get('Content-Disposition');
        
        if (preg_match('/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $contentDisposition, $matches)) {
            $fileName = $matches[1];
            // Remove quotes if present
            $fileName = trim($fileName, '"\'');
            return $fileName;
        }
        
        return 'unknown_file';
    }
}
