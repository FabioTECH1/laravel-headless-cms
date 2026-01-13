<?php

namespace Database\Seeders;

use App\Models\Webhook;
use Illuminate\Database\Seeder;

class WebhookSeeder extends Seeder
{
    public function run(): void
    {
        Webhook::firstOrCreate(
            ['name' => 'Netlify Build Hook'],
            [
                'url' => env('NETLIFY_WEBHOOK', 'https://api.netlify.com/build_hooks/example'),
                'events' => ['content.publish', 'content.delete'],
                'enabled' => true,
                'secret' => 'whsec_demo_netlify_secret_key_123',
            ]
        );

        Webhook::firstOrCreate(
            ['name' => 'Slack Notifications'],
            [
                'url' => env('SLACK_WEBHOOK', 'https://hooks.slack.com/services/YOUR_WEBHOOK_HERE'),
                'events' => ['content.create', 'media.upload'],
                'enabled' => false,
                'secret' => 'whsec_demo_slack_secret_key_456',
            ]
        );
    }
}
