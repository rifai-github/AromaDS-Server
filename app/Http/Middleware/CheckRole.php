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

        // Admin sistem & Management melewati semua cek. Nama role dicocokkan persis
        // (User::bypassesPermissionChecks); dulu role apa pun yang namanya MENGANDUNG "admin"
        // (mis. "Admin Cabang", "Marketing Admin") ikut lolos — Audit Master Role 27 Nov 2026.
        if ($user->bypassesPermissionChecks()) {
            return $next($request);
        }

        if (!$user->hasAnyRole($roles)) {
            abort(403, 'Unauthorized. You do not have the required role to access this resource.');
        }

        return $next($request);
    }
}
