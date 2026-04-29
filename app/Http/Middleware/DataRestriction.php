<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataRestriction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $restrictionType
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $restrictionType = 'auto')
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Add user data restriction info to request
        $request->merge([
            'user_data_restriction' => $user->data_restriction,
            'user_branch_id' => $user->branch_id,
            'user_department_id' => $user->department_id,
            'user_id' => $user->id,
        ]);

        // Add helper methods to request
        $request->merge([
            'can_view_all_data' => $user->canViewAllData(),
            'can_view_branch_data' => $user->canViewBranchData(),
            'can_view_department_data' => $user->canViewDepartmentData(),
            'can_view_own_data' => $user->canViewOwnData(),
        ]);

        return $next($request);
    }
}
