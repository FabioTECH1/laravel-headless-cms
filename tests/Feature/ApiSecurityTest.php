<?php

namespace Tests\Feature;

use App\Models\ContentType;
use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $schemaManager;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaManager = new SchemaManager;
    }

    /** @test */
    public function public_content_is_accessible_without_token()
    {
        // 1. Create Public Type
        $this->schemaManager->createType('PublicPost', [
            ['name' => 'title', 'type' => 'text'],
        ]);
        // Update is_public manually since createType defaults to false usually (or add arg if updated)
        // Adjust based on current implementation - assumming database default is false.
        $type = ContentType::where('slug', 'public-post')->first();
        $type->update(['is_public' => true]);

        // 2. GET request
        $response = $this->getJson('/api/content/public-post');

        // 3. Assert OK
        $response->assertOk();
    }

    /** @test */
    public function private_content_requires_token()
    {
        // 1. Create Private Type
        $this->schemaManager->createType('SecretDiary', [
            ['name' => 'title', 'type' => 'text'],
        ]);
        // Default is private (false)

        // 2. GET request (Unauthenticated)
        $response = $this->getJson('/api/content/secret-diary');

        // 3. Assert 401
        $response->assertUnauthorized();
    }

    /** @test */
    public function token_allows_access_to_private_content()
    {
        // 1. Create Private Type
        $this->schemaManager->createType('SecretReport', [
            ['name' => 'title', 'type' => 'text'],
        ]);

        // 2. Create User & Token
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // 3. GET request with Token
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/content/secret-report');

        // 4. Assert OK
        $response->assertOk();
    }

    /** @test */
    public function writing_content_always_requires_token_even_if_public()
    {
        // 1. Create Public Type
        $this->schemaManager->createType('PublicNote', [
            ['name' => 'title', 'type' => 'text'],
        ]);
        $type = ContentType::where('slug', 'public-note')->first();
        $type->update(['is_public' => true]);

        // 2. POST request (Unauthenticated)
        $response = $this->postJson('/api/content/public-note', ['title' => 'Hacked']);

        // 3. Assert 401
        $response->assertUnauthorized();
    }
}
