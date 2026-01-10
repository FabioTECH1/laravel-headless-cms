<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('Logout', function () {
    it('deletes token on token-based logout', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->assertCount(1, $user->tokens);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        $response->assertOk();
        $this->assertCount(0, $user->fresh()->tokens);
    });

    it('does not crash on session-based logout', function () {
        $user = User::factory()->create();

        // Simulate session-based auth (SPA)
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/auth/logout');

        $response->assertOk();
        // Tokens should remain untouched or irrelevant, but mainly no crash
    });
});
