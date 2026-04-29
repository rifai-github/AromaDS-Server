<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\LoginHistory;
use Illuminate\Support\Facades\Auth;

class LoginLogging
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

        // Log successful login
        if (Auth::check() && $request->is('login')) {
            $this->logLogin($request, true);
        }

        return $response;
    }

    /**
     * Log login attempt
     */
    protected function logLogin(Request $request, $successful = true, $failureReason = null)
    {
        $user = Auth::user();
        
        if (!$user) {
            return;
        }

        // Get location from IP (you can integrate with a geolocation service)
        $location = $this->getLocationFromIp($request->ip());

        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'location' => $location,
            'login_at' => now(),
            'is_successful' => $successful,
            'failure_reason' => $failureReason
        ]);
    }

    /**
     * Get location from IP address
     * You can integrate with services like ipapi.co, ipgeolocation.io, etc.
     */
    protected function getLocationFromIp($ipAddress)
    {
        // For now, return a placeholder
        // In production, you would call a geolocation API
        if ($ipAddress === '127.0.0.1' || $ipAddress === '::1') {
            return 'Local Development';
        }
        
        return 'Unknown Location';
    }
}
