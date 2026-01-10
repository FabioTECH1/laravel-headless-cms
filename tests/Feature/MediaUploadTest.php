<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('Media Upload', function () {
    it('can upload a file', function () {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($user)
            ->postJson(route('admin.media.store'), [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'url',
                'filename',
            ]);

        Storage::disk('public')->assertExists('uploads/'.$file->hashName());

        // Clean up
        Storage::disk('public')->delete('uploads/'.$file->hashName());
    });

    it('validates uploaded file', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('admin.media.store'), [
                'file' => 'not-a-file',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    });
});
