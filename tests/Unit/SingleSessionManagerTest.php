<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserSession;
use App\Services\SingleSessionManager;
use Illuminate\Database\Schema\Blueprint;
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
    }

    protected function tearDown(): void
    {
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
            'last_activity' => now()->subMinutes(config('session.lifetime', 120) + 1)->timestamp,
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

    private function resetHasUserSessionsTableCache(): void
    {
        $property = new \ReflectionProperty(SingleSessionManager::class, 'hasUserSessionsTable');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
