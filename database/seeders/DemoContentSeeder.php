<?php

namespace Database\Seeders;

use App\Services\SchemaManager;
use Illuminate\Database\Seeder;

class DemoContentSeeder extends Seeder
{
    public function run(SchemaManager $manager): void
    {
        try {
            $manager->createType(
                'Demo Gallery',
                [
                    [
                        'name' => 'title',
                        'type' => 'text',
                        'settings' => [],
                    ],
                    [
                        'name' => 'cover_image',
                        'type' => 'media',
                        'settings' => [],
                    ],
                    [
                        'name' => 'description',
                        'type' => 'longtext',
                        'settings' => [],
                    ],
                ],
                true, // isPublic
                true  // hasOwnership
            );
        } catch (\Exception $e) {
            // Ignore if already exists
            if (! str_contains($e->getMessage(), 'already exists')) {
                throw $e;
            }
        }
    }
}
