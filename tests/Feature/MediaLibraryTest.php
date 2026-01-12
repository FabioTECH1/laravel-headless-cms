<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_admin' => true]);
        Storage::fake('public');
    }

    public function test_can_upload_file()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)
            ->post(route('admin.media.store'), [
                'file' => $file,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('media_files', [
            'filename' => 'test.jpg',
        ]);

        Storage::disk('public')->assertExists('uploads/'.$file->hashName());
    }

    public function test_can_upload_file_to_folder()
    {
        $folder = MediaFolder::create(['name' => 'Images']);
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)
            ->post(route('admin.media.store'), [
                'file' => $file,
                'folder_id' => $folder->id,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('media_files', [
            'filename' => 'test.jpg',
            'folder_id' => $folder->id,
        ]);
    }

    public function test_can_list_media()
    {
        MediaFile::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.media.index'));

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_media_by_folder()
    {
        $folder = MediaFolder::create(['name' => 'Images']);
        MediaFile::factory()->create(['folder_id' => $folder->id]);
        MediaFile::factory()->create(['folder_id' => null]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.media.index', ['folder_id' => $folder->id]));

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_search_media()
    {
        MediaFile::factory()->create(['filename' => 'cat.jpg']);
        MediaFile::factory()->create(['filename' => 'dog.jpg']);

        $response = $this->actingAs($this->user)
            ->get(route('admin.media.index', ['search' => 'cat']));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.filename', 'cat.jpg');
    }

    public function test_can_update_media_metadata()
    {
        $media = MediaFile::factory()->create();

        $response = $this->actingAs($this->user)
            ->put(route('admin.media.update', $media->id), [
                'alt_text' => 'A beautiful image',
                'caption' => 'This is a caption',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('media_files', [
            'id' => $media->id,
            'alt_text' => 'A beautiful image',
            'caption' => 'This is a caption',
        ]);
    }

    public function test_can_delete_media()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg');
        $path = $file->store('uploads', 'public');

        $media = MediaFile::create([
            'filename' => 'test.jpg',
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'disk' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.media.destroy', $media->id));

        $response->assertNoContent();
        $this->assertDatabaseMissing('media_files', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_can_create_folder()
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.media-folders.store'), [
                'name' => 'Images',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('media_folders', ['name' => 'Images']);
    }

    public function test_can_create_nested_folder()
    {
        $parent = MediaFolder::create(['name' => 'Photos']);

        $response = $this->actingAs($this->user)
            ->post(route('admin.media-folders.store'), [
                'name' => 'Vacation',
                'parent_id' => $parent->id,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('media_folders', [
            'name' => 'Vacation',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_cannot_delete_folder_with_files()
    {
        $folder = MediaFolder::create(['name' => 'Images']);
        MediaFile::factory()->create(['folder_id' => $folder->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.media-folders.destroy', $folder->id));

        $response->assertStatus(422);
        $this->assertDatabaseHas('media_folders', ['id' => $folder->id]);
    }
}
