<?php

namespace Tests\Feature;

use Tests\TestCase;

class CsrfTokenRefreshTest extends TestCase
{
    public function test_csrf_token_endpoint_returns_current_session_token(): void
    {
        $response = $this->getJson('/csrf-token');

        $response->assertOk()
            ->assertJsonStructure(['token'])
            ->assertHeader('X-CSRF-TOKEN');

        $this->assertNotEmpty($response->json('token'));
    }
}
