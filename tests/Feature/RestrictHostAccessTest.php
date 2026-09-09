<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The ERP must only answer on the server IP. A third party's domain
 * ("www.wappin.id") points at the production IP, which got the app crawled
 * and indexed by Google. This locks the rule in.
 */
class RestrictHostAccessTest extends TestCase
{
    public function test_it_blocks_requests_arriving_through_a_hostname(): void
    {
        $response = $this->get('http://www.wappin.id/up');

        $response->assertNotFound();
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->assertSame('Not Found', $response->getContent());
    }

    public function test_it_allows_the_server_ip(): void
    {
        $this->get('http://103.93.130.145/up')->assertOk();
        $this->get('http://103.247.11.46/up')->assertOk();
        $this->get('http://127.0.0.1/up')->assertOk();
    }

    public function test_it_allows_local_development_hosts(): void
    {
        $this->get('http://localhost/up')->assertOk();
        $this->get('http://aroma.test/up')->assertOk();
    }

    public function test_it_allows_hostnames_listed_in_allowed_hosts(): void
    {
        config(['features.allowed_hosts' => 'erp.aroma.co.id, www.erp.aroma.co.id']);

        $this->get('http://erp.aroma.co.id/up')->assertOk();
        $this->get('http://www.erp.aroma.co.id/up')->assertOk();
        $this->get('http://www.wappin.id/up')->assertNotFound();
    }

    public function test_the_kill_switch_disables_the_restriction(): void
    {
        config(['features.block_hostname_access' => false]);

        $this->get('http://www.wappin.id/up')->assertOk();
    }
}
