<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsrfSessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function SanctumCsrfCookieEndpointIsAvailable(): void
    {
        $response = $this->withHeader('Origin', 'http://localhost:3000')
            ->get('/sanctum/csrf-cookie');

        $response->assertNoContent();
    }

    #[Test]
    public function StatefulWriteRequestWithoutCsrfTokenIsRejected(): void
    {
        $this->app->instance('env', 'local');

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeader('Origin', 'http://localhost:3000')
            ->postJson('/api/logout');

        $response->assertStatus(419);
    }
}
