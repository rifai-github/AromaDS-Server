<?php

namespace App\Http\Middleware;

use App\Services\SingleSessionManager;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CheckMultiLogin
{
    public function __construct(
        protected SingleSessionManager $singleSessionManager
    ) {
    }

    /**
     * Handle an incoming request.
     * 
     * For users with multi_login=false:
     * - On login: The current session becomes the only active web session
     * - On each request: Verify the current session still matches the active session record
     * - If another login replaced it, force logout
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // If user is not logged in, proceed
        if (!$request->user()) {
            return $next($request);
        }

        $user = $request->user();

        if (!$this->singleSessionManager->isCurrentSessionValid($user, $request)) {
            $currentSessionId = Session::getId();

            // Session was deleted (someone else logged in), force logout
            \Log::warning('CheckMultiLogin: Session ' . substr($currentSessionId, 0, 8) . '... for user ' . $user->id . ' no longer exists - forcing logout');

            $this->singleSessionManager->forgetCurrentSession($user, $currentSessionId);
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            return redirect()->route('login')->with('error', 'Anda telah login di perangkat lain. Sesi ini telah diakhiri.');
        }

        $this->singleSessionManager->touchCurrentSession($user, $request);

        return $next($request);
    }
}


