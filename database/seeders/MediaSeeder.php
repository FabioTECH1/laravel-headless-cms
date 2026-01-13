<?php

namespace Database\Seeders;

use App\Models\MediaFile;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $folder = \App\Models\MediaFolder::create(['name' => 'Demo Images']);

        $images = [
            [
                'filename' => 'mountain-landscape.jpg',
                'disk' => 'public',
                'path' => 'uploads/mountain-landscape.jpg', // In a real app, we'd copy a file here
                'mime_type' => 'image/jpeg',
                'size' => 1024 * 150, // 150KB
                'alt_text' => 'Beautiful mountain landscape at sunset',
                'width' => 1920,
                'height' => 1080,
                'folder_id' => $folder->id,
            ],
            [
                'filename' => 'coding-laptop.png',
                'disk' => 'public',
                'path' => 'uploads/coding-laptop.png',
                'mime_type' => 'image/png',
                'size' => 1024 * 450, // 450KB
                'alt_text' => 'Developer working on code on a laptop',
                'width' => 1200,
                'height' => 800,
                'folder_id' => $folder->id,
            ],
            [
                'filename' => 'office-meeting.jpg',
                'disk' => 'public',
                'path' => 'uploads/office-meeting.jpg',
                'mime_type' => 'image/jpeg',
                'size' => 1024 * 200,
                'alt_text' => 'Team brainstorming in a modern office',
                'width' => 1600,
                'height' => 900,
                'folder_id' => null,
            ],
        ];

        foreach ($images as $image) {
            MediaFile::firstOrCreate(
                ['filename' => $image['filename']],
                $image
            );
        }
    }
}
