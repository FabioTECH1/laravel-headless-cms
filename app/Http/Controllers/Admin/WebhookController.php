<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WebhookController extends Controller
{
    public function index()
    {
        $webhooks = Webhook::latest()->get();

        return Inertia::render('Settings/Webhooks', [
            'webhooks' => $webhooks,
            'availableEvents' => $this->getAvailableEvents(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'headers' => 'nullable|array',
            'enabled' => 'boolean',
        ]);

        Webhook::create($validated);

        return redirect()->back()->with('success', 'Webhook created successfully.');
    }

    public function update(Request $request, Webhook $webhook)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
            'headers' => 'nullable|array',
            'enabled' => 'boolean',
        ]);

        $webhook->update($validated);

        return redirect()->back()->with('success', 'Webhook updated successfully.');
    }

    public function destroy(Webhook $webhook)
    {
        $webhook->delete();

        return redirect()->back()->with('success', 'Webhook deleted successfully.');
    }

    /**
     * Test webhook by sending a sample payload.
     */
    public function test(Webhook $webhook)
    {
        $result = WebhookService::test($webhook);

        if ($result['success']) {
            return redirect()->back()->with('success', "Webhook test successful! Status: {$result['status']}");
        }

        return redirect()->back()->withErrors(['error' => "Webhook test failed: {$result['error']}"]);
    }

    /**
     * Get list of available webhook events.
     */
    private function getAvailableEvents(): array
    {
        return [
            [
                'category' => 'Content',
                'events' => [
                    ['value' => 'content.create', 'label' => 'Content Created'],
                    ['value' => 'content.update', 'label' => 'Content Updated'],
                    ['value' => 'content.delete', 'label' => 'Content Deleted'],
                    ['value' => 'content.publish', 'label' => 'Content Published'],
                ],
            ],
            [
                'category' => 'Media',
                'events' => [
                    ['value' => 'media.upload', 'label' => 'Media Uploaded'],
                    ['value' => 'media.delete', 'label' => 'Media Deleted'],
                ],
            ],
        ];
    }
}
