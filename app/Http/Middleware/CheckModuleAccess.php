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

        // Admin sistem & Management melewati semua cek. Nama role dicocokkan persis
        // (User::bypassesPermissionChecks); dulu role apa pun yang namanya MENGANDUNG "admin"
        // (mis. "Admin Cabang", "Marketing Admin") ikut lolos — Audit Master Role 27 Nov 2026.
        if ($user->bypassesPermissionChecks()) {
            return $next($request);
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
