<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_can_create_webhook()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->post('/admin/webhooks', [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['content.create', 'content.update'],
            'enabled' => true,
        ]);

        $this->assertDatabaseHas('webhooks', [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
        ]);
    }

    public function test_can_update_webhook()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $webhook = Webhook::create([
            'name' => 'Original Name',
            'url' => 'https://example.com/webhook',
            'events' => ['content.create'],
            'enabled' => true,
        ]);

        $response = $this->actingAs($user)->put("/admin/webhooks/{$webhook->id}", [
            'name' => 'Updated Name',
            'url' => 'https://example.com/updated',
            'events' => ['content.update', 'content.delete'],
            'enabled' => false,
        ]);

        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'name' => 'Updated Name',
            'url' => 'https://example.com/updated',
        ]);
    }

    public function test_can_delete_webhook()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $webhook = Webhook::create([
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['content.create'],
            'enabled' => true,
        ]);

        $response = $this->actingAs($user)->delete("/admin/webhooks/{$webhook->id}");

        $this->assertDatabaseMissing('webhooks', [
            'id' => $webhook->id,
        ]);
    }

    public function test_webhook_service_triggers_enabled_webhooks()
    {
        Http::fake();

        $webhook = Webhook::create([
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['content.create', 'content.update'],
            'enabled' => true,
        ]);

        WebhookService::trigger('content.create', ['id' => 1, 'title' => 'Test']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook' &&
                   $request['event'] === 'content.create' &&
                   $request['data']['title'] === 'Test';
        });
    }

    public function test_webhook_service_does_not_trigger_disabled_webhooks()
    {
        Http::fake();

        $webhook = Webhook::create([
            'name' => 'Disabled Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['content.create'],
            'enabled' => false,
        ]);

        WebhookService::trigger('content.create', ['id' => 1]);

        Http::assertNothingSent();
    }

    public function test_webhook_service_only_triggers_matching_events()
    {
        Http::fake();

        $webhook = Webhook::create([
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['content.create'],
            'enabled' => true,
        ]);

        WebhookService::trigger('content.update', ['id' => 1]);

        Http::assertNothingSent();
    }

    public function test_webhook_test_sends_sample_payload()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $webhook = Webhook::create([
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['content.create'],
            'enabled' => true,
        ]);

        $result = WebhookService::test($webhook);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status']);
    }
}
