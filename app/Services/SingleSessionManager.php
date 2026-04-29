<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SingleSessionManager
{
    protected static ?bool $hasUserSessionsTable = null;

    public function registerCurrentSession(User $user, Request $request): void
    {
        $sessionId = $request->session()->getId();

        if (!$user->multi_login) {
            $this->forgetOtherSessions($user, $sessionId);
            Cache::put($this->cacheKey($user), $sessionId, now()->addMinutes(config('session.lifetime', 120)));
        }

        if (!$this->hasUserSessionsTable()) {
            return;
        }

        $this->syncUserSessionRecord($user, $request, $sessionId);
    }

    public function touchCurrentSession(User $user, Request $request): void
    {
        $sessionId = $request->session()->getId();

        if (!$user->multi_login) {
            Cache::put($this->cacheKey($user), $sessionId, now()->addMinutes(config('session.lifetime', 120)));
        }

        if (!$this->hasUserSessionsTable()) {
            return;
        }

        try {
            $updated = UserSession::where('session_id', $sessionId)
                ->where('user_id', $user->id)
                ->update($this->userSessionPayload($user, $request));

            if ($updated === 0) {
                $this->syncUserSessionRecord($user, $request, $sessionId);
            }
        } catch (\Throwable $e) {
            \Log::warning('SingleSessionManager: Failed to touch user session', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function isCurrentSessionValid(User $user, Request $request): bool
    {
        if ($user->multi_login) {
            return true;
        }

        $sessionId = $request->session()->getId();
        $expectedSessionId = Cache::get($this->cacheKey($user));

        if (!empty($expectedSessionId)) {
            return hash_equals((string) $expectedSessionId, (string) $sessionId);
        }

        if (!$this->hasUserSessionsTable()) {
            return true;
        }

        return UserSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function forgetCurrentSession(?User $user, ?string $sessionId): void
    {
        if (!$user || !$sessionId) {
            return;
        }

        if (!$user->multi_login) {
            $expectedSessionId = Cache::get($this->cacheKey($user));

            if ((string) $expectedSessionId === (string) $sessionId) {
                Cache::forget($this->cacheKey($user));
            }
        }

        if ($this->hasUserSessionsTable()) {
            try {
                UserSession::where('session_id', $sessionId)
                    ->where('user_id', $user->id)
                    ->delete();
            } catch (\Throwable $e) {
                \Log::warning('SingleSessionManager: Failed to forget current user session', [
                    'user_id' => $user->id,
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function forgetOtherSessions(User $user, string $currentSessionId): void
    {
        if ($this->hasUserSessionsTable()) {
            try {
                UserSession::where('user_id', $user->id)
                    ->where('session_id', '!=', $currentSessionId)
                    ->delete();
            } catch (\Throwable $e) {
                \Log::warning('SingleSessionManager: Failed to forget other user sessions', [
                    'user_id' => $user->id,
                    'session_id' => $currentSessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function syncUserSessionRecord(User $user, Request $request, string $sessionId): void
    {
        try {
            UserSession::updateOrCreate(
                ['session_id' => $sessionId],
                $this->userSessionPayload($user, $request)
            );
        } catch (\Throwable $e) {
            \Log::warning('SingleSessionManager: Failed to sync user session record', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function userSessionPayload(User $user, Request $request): array
    {
        return [
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            // The existing user_sessions table stores last_activity as an integer unix timestamp.
            'last_activity' => now()->timestamp,
        ];
    }

    protected function cacheKey(User $user): string
    {
        return 'single_login_active_session:' . $user->id;
    }

    protected function hasUserSessionsTable(): bool
    {
        if (self::$hasUserSessionsTable !== null) {
            return self::$hasUserSessionsTable;
        }

        return self::$hasUserSessionsTable = Schema::hasTable('user_sessions');
    }
}
