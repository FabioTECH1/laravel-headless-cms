<?php

use App\Models\User;
use App\Services\SchemaManager;

describe('API Security', function () {
    it('accessible public content without token', function () {
        $schemaManager = new SchemaManager;

        // 1. Create Public Type
        $type = $schemaManager->createType('PublicPost', [
            ['name' => 'title', 'type' => 'text'],
        ]);
        // Update is_public manually since createType defaults to false usually
        $type->update(['is_public' => true]);

        // 2. GET request
        $response = $this->getJson('/api/content/'.$type->slug);

        // 3. Assert OK
        $response->assertOk();
    });

    it('requires token for private content', function () {
        $schemaManager = new SchemaManager;

        // 1. Create Private Type
        $type = $schemaManager->createType('SecretDiary', [
            ['name' => 'title', 'type' => 'text'],
        ]);
        // Default is private (false)

        // 2. GET request (Unauthenticated)
        $response = $this->getJson('/api/content/'.$type->slug);

        // 3. Assert 401
        $response->assertUnauthorized();
    });

    it('allows access to private content with token', function () {
        $schemaManager = new SchemaManager;

        // 1. Create Private Type
        $type = $schemaManager->createType('SecretReport', [
            ['name' => 'title', 'type' => 'text'],
        ]);

        // 2. Create User & Token
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // 3. GET request with Token
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/content/'.$type->slug);

        // 4. Assert OK
        $response->assertOk();
    });

    it('always requires token for writing content even if public', function () {
        $schemaManager = new SchemaManager;

        // 1. Create Public Type
        $type = $schemaManager->createType('PublicNote', [
            ['name' => 'title', 'type' => 'text'],
        ]);
        $type->update(['is_public' => true]);

        // 2. POST request (Unauthenticated)
        $response = $this->postJson('/api/content/'.$type->slug, ['title' => 'Hacked']);

        // 3. Assert 401
        $response->assertUnauthorized();
    });
});
