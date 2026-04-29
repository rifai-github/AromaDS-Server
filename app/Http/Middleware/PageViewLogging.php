<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;

class PageViewLogging
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

        // Log page view after processing
        if ($this->shouldLogPageView($request, $response)) {
            $this->logPageView($request, $response);
        }

        return $response;
    }

    /**
     * Check if page view should be logged
     */
    protected function shouldLogPageView(Request $request, $response)
    {
        // Only log for authenticated users
        if (!Auth::check()) {
            return false;
        }

        // Skip AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return false;
        }

        // Skip API routes
        if ($request->is('api/*')) {
            return false;
        }

        // Skip asset requests
        if ($request->is('css/*') || $request->is('js/*') || $request->is('images/*')) {
            return false;
        }

        // Only log successful responses
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        // Skip certain routes
        $skipRoutes = [
            'login',
            'logout',
            'password/reset',
            'password/email',
            'audit-trails/*',
            'access-control/*'
        ];

        foreach ($skipRoutes as $route) {
            if ($request->is($route)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Log page view activity
     */
    protected function logPageView(Request $request, $response)
    {
        $user = Auth::user();
        
        // Check if we already logged this page view recently (within 5 minutes)
        $recentLog = AuditLog::where('user_id', $user->id)
            ->where('action', 'page_view')
            ->where('ip_address', $request->ip())
            ->where('created_at', '>=', now()->subMinutes(5))
            ->whereJsonContains('new_values->page', $request->path())
            ->first();

        if ($recentLog) {
            return; // Skip duplicate logging
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'page_view',
            'model_type' => 'PageView',
            'model_id' => 0,
            'old_values' => null,
            'new_values' => [
                'page' => $request->path(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'referrer' => $request->header('referer'),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'session_id' => session()->getId()
            ],
            'changed_fields' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'page_name' => $this->getCurrentPageName($request),
            'module_name' => $this->getCurrentModuleName($request),
        ]);
    }

    /**
     * Get current page name from request
     */
    protected function getCurrentPageName(Request $request)
    {
        $route = $request->route();
        
        if ($route) {
            $routeName = $route->getName();
            if ($routeName) {
                // Extract page name from route name
                $parts = explode('.', $routeName);
                
                // If the last part is "index" and we have more parts, use the second to last part
                // e.g. marketing.pipeline.index -> pipeline
                if (end($parts) === 'index' && count($parts) >= 2) {
                    $pageName = $parts[count($parts) - 2];
                    return $pageName;
                }
                
                return end($parts);
            }
        }
        
        // Fallback to URL path
        $path = $request->path();
        $segments = explode('/', $path);
        
        // Remove empty segments
        $segments = array_filter($segments);
        
        if (empty($segments)) {
            return 'dashboard';
        }
        
        return end($segments);
    }

    /**
     * Get current module name from request
     */
    protected function getCurrentModuleName(Request $request)
    {
        $route = $request->route();
        
        if ($route) {
            $routeName = $route->getName();
            if ($routeName) {
                // Extract module name from route name
                $parts = explode('.', $routeName);
                if (count($parts) >= 2) {
                    return $parts[0]; // First part is usually the module
                }
            }
        }
        
        // Fallback to URL path
        $path = $request->path();
        $segments = explode('/', $path);
        
        // Remove empty segments
        $segments = array_filter($segments);
        
        if (empty($segments)) {
            return 'dashboard';
        }
        
        // Map common paths to modules
        $moduleMap = [
            'dashboard' => 'dashboard',
            'users' => 'system',
            'roles' => 'system',
            'permissions' => 'system',
            'audit-trails' => 'system',
            'customers' => 'marketing',
            'prospects' => 'marketing',
            'surveys' => 'marketing',
            'quotations' => 'sales',
            'invoices' => 'finance',
            'reports' => 'reports',
            'settings' => 'system'
        ];
        
        $firstSegment = $segments[0];
        return $moduleMap[$firstSegment] ?? $firstSegment;
    }
}
