<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserScreenshotAccessTest extends TestCase
{
    public function test_administrator_role_still_respects_screenshot_allowed_flag(): void
    {
        $user = new User([
            'roles' => 'Administrator',
            'screenshot_allowed' => false,
        ]);

        $this->assertFalse($user->isAlwaysAllowedScreenshot());
        $this->assertFalse($user->canTakeScreenshot());
    }

    public function test_screenshot_allowed_flag_allows_screenshot_for_any_user(): void
    {
        $user = new User([
            'roles' => 'Administrator',
            'screenshot_allowed' => true,
        ]);

        $this->assertTrue($user->canTakeScreenshot());
    }
}
