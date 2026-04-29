<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $module
     * @param  string  $action
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $module, string $action = 'view')
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admin and Management roles bypass all module access checks
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

        // Check module access based on action
        switch ($action) {
            case 'view':
            case 'read':
                if (!$user->canAccessModule($module)) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Unauthorized. You do not have access to view {$module} module.",
                            'error' => 'module_access_denied'
                        ], 403);
                    }
                    abort(403, "Unauthorized. You do not have access to view {$module} module.");
                }
                break;
                
            case 'create':
                if (!$user->canCreateInModule($module)) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Unauthorized. You do not have permission to create in {$module} module.",
                            'error' => 'module_create_denied'
                        ], 403);
                    }
                    abort(403, "Unauthorized. You do not have permission to create in {$module} module.");
                }
                break;
                
            case 'edit':
            case 'update':
                if (!$user->canEditInModule($module)) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Unauthorized. You do not have permission to edit in {$module} module.",
                            'error' => 'module_edit_denied'
                        ], 403);
                    }
                    abort(403, "Unauthorized. You do not have permission to edit in {$module} module.");
                }
                break;
                
            case 'delete':
                if (!$user->canDeleteInModule($module)) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Unauthorized. You do not have permission to delete in {$module} module.",
                            'error' => 'module_delete_denied'
                        ], 403);
                    }
                    abort(403, "Unauthorized. You do not have permission to delete in {$module} module.");
                }
                break;
                
            default:
                if (!$user->hasPermission("{$module}.{$action}")) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Unauthorized. You do not have permission to {$action} in {$module} module.",
                            'error' => 'module_permission_denied'
                        ], 403);
                    }
                    abort(403, "Unauthorized. You do not have permission to {$action} in {$module} module.");
                }
                break;
        }

        return $next($request);
    }
}
