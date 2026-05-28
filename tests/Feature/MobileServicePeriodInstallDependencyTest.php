<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Mobile\JobController;
use Tests\TestCase;

class MobileServicePeriodInstallDependencyTest extends TestCase
{
    public function test_service_period_after_first_is_not_blocked_by_pending_install_dependency(): void
    {
        $controller = new JobController();
        $method = new \ReflectionMethod($controller, 'shouldBlockServiceByPendingInstall');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, (object) [
            'type' => 'service',
            'period' => 2,
        ]));

        $this->assertFalse($method->invoke($controller, (object) [
            'type' => 'service_routine',
            'period' => null,
        ]));
    }

    public function test_first_service_still_waits_for_pending_install_dependency(): void
    {
        $controller = new JobController();
        $method = new \ReflectionMethod($controller, 'shouldBlockServiceByPendingInstall');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, (object) [
            'type' => 'service',
            'period' => 1,
        ]));

        $this->assertTrue($method->invoke($controller, (object) [
            'type' => 'csr',
            'period' => null,
        ]));
    }
}
