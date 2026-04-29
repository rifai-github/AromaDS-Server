<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AccessControlService;
use Symfony\Component\HttpFoundation\Response;

class CheckLoginRestriction
{
    protected $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Skip login restriction check for Admin and Management
            if ($user->hasRole('Admin') || $user->hasRole('super_admin') || $user->hasRoleStartingWith('Management')) {
                return $next($request);
            }

            // Check if user is allowed to login at this time/day
            if (!$this->accessControlService->canLoginAtTime($user)) {
                // Logout user
                Auth::logout();
                
                // Invalidate session
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
                
                // Return appropriate response
                $message = 'Waktu login Anda tidak diizinkan saat ini. Silakan hubungi administrator.';
                
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $message,
                        'redirect' => route('login')
                    ], 403);
                }
                
                return redirect()->route('login')->with('error', $message);
            }
        }
        
        return $next($request);
    }
}
