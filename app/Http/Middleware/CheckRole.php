<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  ...$roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admin and Management roles bypass all role checks
        if ($user->hasRole('Admin') || $user->hasRole('super_admin') || $user->hasRoleStartingWith('Management')) {
            return $next($request);
        }
        
        // Also check directly from relationship and column
        $userRole = $user->roles()->first();
        if ($userRole) {
            $roleName = strtolower($userRole->name);
            if (strpos($roleName, 'admin') !== false || 
                strpos($roleName, 'super_admin') !== false || 
                strpos($roleName, 'management') === 0) {
                return $next($request);
            }
        }
        
        $rolesColumn = $user->getAttributes()['roles'] ?? null;
        if ($rolesColumn && is_string($rolesColumn)) {
            $roleNameLower = strtolower($rolesColumn);
            $decoded = json_decode($rolesColumn, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
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

        if (!$user->hasAnyRole($roles)) {
            abort(403, 'Unauthorized. You do not have the required role to access this resource.');
        }

        return $next($request);
    }
}
