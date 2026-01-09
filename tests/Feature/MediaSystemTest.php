<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SchemaManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $schemaManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->schemaManager = new SchemaManager;
        Storage::fake('public');
    }

    /** @test */
    public function it_can_upload_a_file()
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertOk()
            ->assertJsonStructure(['id', 'url', 'filename']);

        $this->assertDatabaseHas('media_files', [
            'filename' => 'photo.jpg',
        ]);
    }

    /** @test */
    public function it_can_attach_media_to_content()
    {
        // 1. Create 'Post' Type with 'hero_image' (media)
        $this->schemaManager->createType('Post', [
            ['name' => 'title', 'type' => 'text'],
            ['name' => 'hero_image', 'type' => 'media'],
        ]);

        // 2. Upload File
        $file = UploadedFile::fake()->image('hero.jpg');
        $uploadResponse = $this->actingAs($this->admin)->postJson(route('admin.media.store'), [
            'file' => $file,
        ]);
        $mediaId = $uploadResponse->json('id');

        // 3. Create Content with Media ID
        $response = $this->actingAs($this->admin)->post(route('admin.content.store', 'post'), [
            'title' => 'My Best Shot',
            'hero_image_id' => $mediaId,
        ]);

        $response->assertRedirect();

        // 4. Assert Database
        $this->assertDatabaseHas('posts', [
            'title' => 'My Best Shot',
            'hero_image_id' => $mediaId,
        ]);
    }
}
