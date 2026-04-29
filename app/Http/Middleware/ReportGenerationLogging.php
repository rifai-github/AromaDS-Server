<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class ReportGenerationLogging
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

        // Log report generation after processing
        if ($this->isReportGeneration($request, $response)) {
            $this->logReportGeneration($request, $response);
        }

        return $response;
    }

    /**
     * Check if this is a report generation request
     */
    protected function isReportGeneration(Request $request, $response)
    {
        if (!Auth::check()) {
            return false;
        }

        // Check for report-related routes
        $reportRoutes = [
            'reports/*',
            'export/*',
            'generate-report',
            'print-report',
            'download-report'
        ];

        foreach ($reportRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        // Check for report generation in URL parameters
        if ($request->has('report_type') || $request->has('export_type')) {
            return true;
        }

        // Check if response contains report data
        $contentType = $response->headers->get('Content-Type');
        if (in_array($contentType, [
            'application/pdf',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv'
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Log report generation activity
     */
    protected function logReportGeneration(Request $request, $response)
    {
        $user = Auth::user();
        $reportType = $this->determineReportType($request);
        $fileSize = $response->headers->get('Content-Length');
        $contentType = $response->headers->get('Content-Type');

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'report_generated',
            'model_type' => 'ReportGeneration',
            'model_id' => 0,
            'old_values' => null,
            'new_values' => [
                'report_type' => $reportType,
                'file_size' => $fileSize,
                'content_type' => $contentType,
                'generation_url' => $request->fullUrl(),
                'parameters' => $request->except(['_token', '_method']),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'generation_time' => now()->toISOString()
            ],
            'changed_fields' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Determine report type from request
     */
    protected function determineReportType(Request $request)
    {
        // Try to get from route parameter
        if ($request->route('report_type')) {
            return $request->route('report_type');
        }

        // Try to get from query parameter
        if ($request->has('report_type')) {
            return $request->get('report_type');
        }

        // Try to get from export type
        if ($request->has('export_type')) {
            return 'export_' . $request->get('export_type');
        }

        // Determine from URL path
        $path = $request->path();
        if (str_contains($path, 'customer')) return 'customer_report';
        if (str_contains($path, 'invoice')) return 'invoice_report';
        if (str_contains($path, 'contract')) return 'contract_report';
        if (str_contains($path, 'sales')) return 'sales_report';
        if (str_contains($path, 'financial')) return 'financial_report';

        return 'unknown_report';
    }
}
