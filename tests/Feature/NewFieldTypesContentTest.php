<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('New Field Types Content', function () {
    beforeEach(function () {
        $this->schemaManager = new SchemaManager;
        $this->user = User::factory()->create(['is_admin' => true]);
    });

    it('can store and retrieve json, enum, and email fields', function () {
        // 1. Create Schema
        $this->schemaManager->createType('Customer', [
            ['name' => 'preferences', 'type' => 'json'],
            ['name' => 'role', 'type' => 'enum', 'settings' => ['options' => ['admin', 'user', 'guest']]],
            ['name' => 'contact_email', 'type' => 'email'],
        ], true, true); // Public, Has Ownership

        // 2. Create Content via API
        actingAs($this->user);

        $payload = [
            'preferences' => ['theme' => 'dark', 'notifications' => true],
            'role' => 'admin',
            'contact_email' => 'admin@example.com',
            'status' => 'published',
        ];

        $response = postJson('/api/content/customer', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.preferences.theme', 'dark');
        $response->assertJsonPath('data.role', 'admin');
        $response->assertJsonPath('data.contact_email', 'admin@example.com');

        // 3. Verify Database
        assertDatabaseHas('customers', [
            'contact_email' => 'admin@example.com',
            'role' => 'admin',
            // JSON matching in SQLite/MySQL varies, checking key fields is usually enough for feature test
        ]);

        $customer = \Illuminate\Support\Facades\DB::table('customers')->first();
        expect(json_decode($customer->preferences, true))->toBe(['theme' => 'dark', 'notifications' => true]);
    });

    it('validates enum values', function () {
        $this->schemaManager->createType('Status', [
            ['name' => 'state', 'type' => 'enum', 'settings' => ['options' => ['active', 'inactive']]],
        ]);

        actingAs($this->user);

        $response = postJson('/api/content/status', [
            'state' => 'pending', // Invalid value
        ]);

        // Note: Currently we haven't implemented API-level validation for Enums in ContentRequest/DynamicEntity yet.
        // We need to implement that next if this fails.
        // For now, let's see if it fails (it should NOT fail yet effectively, or maybe it fails with DB error if DB enforced it, but we used string).

        // Actually, my plan said "Add validation for new field types". I haven't done that part in DynamicEntity/Request yet.
        // So I expect this test to FAIL (it will accept 'pending' because it's just a string in DB).
        // I will write this test to EXPECT validation failure, which serves as TDD.

        $response->assertInvalid(['state']);
    });
});
