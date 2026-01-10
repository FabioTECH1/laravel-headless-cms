<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function token_based_logout_deletes_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->assertCount(1, $user->tokens);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        $response->assertOk();
        $this->assertCount(0, $user->fresh()->tokens);
    }

    /** @test */
    public function session_based_logout_does_not_crash()
    {
        $user = User::factory()->create();

        // Simulate session-based auth (SPA)
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/auth/logout');

        $response->assertOk();
        // Tokens should remain untouched or irrelevant, but mainly no crash
    }
}
