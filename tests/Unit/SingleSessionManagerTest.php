<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserSession;
use App\Models\UserLoginRestriction;
use App\Services\SingleSessionManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SingleSessionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetHasUserSessionsTableCache();
        Cache::flush();

        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->integer('last_activity')->nullable();
            $table->timestamps();
        });

        Schema::create('user_login_restrictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->json('allowed_days')->nullable();
            $table->integer('idle_timeout')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_login_restrictions');
        Schema::dropIfExists('user_sessions');

        Cache::flush();
        $this->resetHasUserSessionsTableCache();

        parent::tearDown();
    }

    public function test_single_login_user_with_active_session_is_blocked_from_second_login(): void
    {
        UserSession::create([
            'user_id' => 1,
            'session_id' => 'active-session',
            'last_activity' => now()->timestamp,
        ]);

        $user = new User(['multi_login' => false]);
        $user->id = 1;

        $this->assertTrue(app(SingleSessionManager::class)->hasActiveWebSession($user));
    }

    public function test_single_login_user_with_expired_session_can_login_again(): void
    {
        UserSession::create([
            'user_id' => 1,
            'session_id' => 'expired-session',
            'last_activity' => now()->subMinutes(31)->timestamp,
        ]);

        $user = new User(['multi_login' => false]);
        $user->id = 1;

        $this->assertFalse(app(SingleSessionManager::class)->hasActiveWebSession($user));
    }

    public function test_single_login_user_uses_configured_idle_timeout_for_blocking_second_login(): void
    {
        UserLoginRestriction::create([
            'user_id' => 1,
            'idle_timeout' => 15,
            'is_active' => true,
        ]);

        UserSession::create([
            'user_id' => 1,
            'session_id' => 'idle-session',
            'last_activity' => now()->subMinutes(16)->timestamp,
        ]);

        $user = new User(['multi_login' => false]);
        $user->id = 1;

        $this->assertFalse(app(SingleSessionManager::class)->hasActiveWebSession($user));
    }

    public function test_multi_login_user_is_not_blocked_by_existing_session(): void
    {
        UserSession::create([
            'user_id' => 1,
            'session_id' => 'active-session',
            'last_activity' => now()->timestamp,
        ]);

        $user = new User(['multi_login' => true]);
        $user->id = 1;

        $this->assertFalse(app(SingleSessionManager::class)->hasActiveWebSession($user));
    }

    public function test_session_idle_beyond_default_timeout_is_reported_idle(): void
    {
        UserSession::create([
            'user_id' => 1,
            'session_id' => str_repeat('a', 40),
            'last_activity' => now()->subMinutes(31)->timestamp,
        ]);

        $user = new User(['multi_login' => false]);
        $user->id = 1;

        $request = $this->requestWithSessionId(str_repeat('a', 40));

        $this->assertTrue(app(SingleSessionManager::class)->isSessionIdle($user, $request));
    }

    public function test_session_within_configured_idle_timeout_is_not_idle(): void
    {
        UserLoginRestriction::create([
            'user_id' => 1,
            'idle_timeout' => 60,
            'is_active' => true,
        ]);

        UserSession::create([
            'user_id' => 1,
            'session_id' => str_repeat('b', 40),
            'last_activity' => now()->subMinutes(31)->timestamp,
        ]);

        $user = new User(['multi_login' => false]);
        $user->id = 1;

        $request = $this->requestWithSessionId(str_repeat('b', 40));

        $this->assertFalse(app(SingleSessionManager::class)->isSessionIdle($user, $request));
    }

    public function test_multi_login_user_session_is_still_subject_to_idle_timeout(): void
    {
        UserSession::create([
            'user_id' => 1,
            'session_id' => str_repeat('a', 40),
            'last_activity' => now()->subMinutes(31)->timestamp,
        ]);

        $user = new User(['multi_login' => true]);
        $user->id = 1;

        $request = $this->requestWithSessionId(str_repeat('a', 40));

        $this->assertTrue(app(SingleSessionManager::class)->isSessionIdle($user, $request));
    }

    private function requestWithSessionId(string $sessionId): Request
    {
        $store = new \Illuminate\Session\Store('test', new \Illuminate\Session\ArraySessionHandler(120));
        $store->setId($sessionId);

        $request = Request::create('/dashboard');
        $request->setLaravelSession($store);

        return $request;
    }

    private function resetHasUserSessionsTableCache(): void
    {
        $property = new \ReflectionProperty(SingleSessionManager::class, 'hasUserSessionsTable');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
