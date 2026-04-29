<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MobileAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'code' => 401
            ], 401);
        }

        // Get the authenticated user
        $user = Auth::guard('sanctum')->user();
        
        // Refresh user model to get latest status
        $user->refresh();
        
        // Check if user is active
        if (!$user->is_active) {
            // Delete token
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'status' => 'error',
                'message' => 'Account is deactivated',
                'code' => 403
            ], 403);
        }

        // Check if user is frozen
        if ($user->is_frozen) {
            // Delete token
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda telah dibekukan. Silakan hubungi administrator.',
                'code' => 403
            ], 403);
        }

        // Add user to request for easy access
        $request->merge(['user' => $user]);

        return $next($request);
    }
}
