<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckFrozenAccount
{
    /**
     * Handle an incoming request.
     * Check if the authenticated user's account is frozen and logout if so.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Refresh user model to get latest is_frozen status
            $user->refresh();
            
            // Check if user is frozen
            if ($user->is_frozen) {
                // Logout user
                Auth::logout();
                
                // Invalidate session
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
                
                // Delete Sanctum tokens for API sessions
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
                
                // Return appropriate response
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Akun Anda telah dibekukan. Silakan hubungi administrator.',
                        'redirect' => route('login')
                    ], 403);
                }
                
                return redirect()->route('login')->with('error', 'Akun Anda telah dibekukan. Silakan hubungi administrator.');
            }
        }
        
        return $next($request);
    }
}
