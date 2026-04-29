<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  ...$permissions
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admin and Management roles bypass all permission checks
        // Check multiple ways to ensure compatibility with both relationship and column-based roles
        
        // Method 1: Check using helper methods
        $isAdmin = $user->hasRole('Admin') || $user->hasRole('super_admin');
        $isManagement = $user->hasRoleStartingWith('Management');
        
        if ($isAdmin || $isManagement) {
            return $next($request);
        }
        
        // Method 2: Direct check from relationship (user_roles)
        $userRole = $user->roles()->first();
        if ($userRole) {
            $roleName = strtolower($userRole->name);
            if (strpos($roleName, 'admin') !== false || 
                strpos($roleName, 'super_admin') !== false || 
                strpos($roleName, 'management') === 0) {
                return $next($request);
            }
        }
        
        // Method 3: Fallback to database column (for backward compatibility)
        $rolesColumn = $user->getAttributes()['roles'] ?? null;
        if ($rolesColumn && is_string($rolesColumn)) {
            $roleNameLower = strtolower($rolesColumn);
            // Check if it's JSON
            $decoded = json_decode($rolesColumn, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // If JSON, check first role name
                $firstRole = $decoded[0]['name'] ?? $decoded['name'] ?? null;
                if ($firstRole) {
                    $roleNameLower = strtolower($firstRole);
                }
            }
            
            if (strpos($roleNameLower, 'admin') !== false || 
                strpos($roleNameLower, 'super_admin') !== false || 
                strpos($roleNameLower, 'management') === 0) {
                return $next($request);
            }
        }

        if (!$user->hasAnyPermission($permissions)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You do not have the required permission to access this resource.',
                    'error' => 'permission_denied'
                ], 403);
            }
            abort(403, 'Unauthorized. You do not have the required permission to access this resource.');
        }

        return $next($request);
    }
}
