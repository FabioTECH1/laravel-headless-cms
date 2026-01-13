<?php

namespace App\Services;

use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Trigger webhooks for a specific event.
     */
    public static function trigger(string $event, array $payload): void
    {
        $webhooks = Webhook::where('enabled', true)
            ->get()
            ->filter(fn ($webhook) => in_array($event, $webhook->events));

        foreach ($webhooks as $webhook) {
            self::send($webhook, $event, $payload);
        }
    }

    /**
     * Send HTTP request to webhook URL.
     */
    public static function send(Webhook $webhook, string $event, array $payload): void
    {
        try {
            $headers = $webhook->headers ?? [];
            $headers['Content-Type'] = 'application/json';
            $headers['X-Webhook-Event'] = $event;

            $payloadData = [
                'event' => $event,
                'data' => $payload,
                'timestamp' => now()->toIso8601String(),
            ];

            if ($webhook->secret) {
                $signature = hash_hmac('sha256', json_encode($payloadData), $webhook->secret);
                $headers['X-Hub-Signature-256'] = "sha256={$signature}";
            }

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($webhook->url, $payloadData);

            Log::info("Webhook sent: {$webhook->name}", [
                'event' => $event,
                'url' => $webhook->url,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::error("Webhook failed: {$webhook->name}", [
                'event' => $event,
                'url' => $webhook->url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Test webhook by sending a sample payload.
     */
    public static function test(Webhook $webhook): array
    {
        try {
            $headers = $webhook->headers ?? [];
            $headers['Content-Type'] = 'application/json';
            $headers['X-Webhook-Event'] = 'test';

            $payloadData = [
                'event' => 'test',
                'data' => [
                    'message' => 'This is a test webhook from your Laravel CMS',
                ],
                'timestamp' => now()->toIso8601String(),
            ];

            if ($webhook->secret) {
                $signature = hash_hmac('sha256', json_encode($payloadData), $webhook->secret);
                $headers['X-Hub-Signature-256'] = "sha256={$signature}";
            }

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($webhook->url, $payloadData);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'body' => $response->body(),
                'error' => $response->successful() ? null : "HTTP {$response->status()} - ".substr($response->body(), 0, 100),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
