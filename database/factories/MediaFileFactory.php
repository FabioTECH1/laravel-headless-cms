<?php

namespace Database\Factories;

use App\Models\MediaFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFileFactory extends Factory
{
    protected $model = MediaFile::class;

    public function definition(): array
    {
        return [
            'filename' => $this->faker->word().'.'.$this->faker->fileExtension(),
            'path' => 'uploads/'.$this->faker->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(1000, 5000000),
            'disk' => 'public',
            'width' => $this->faker->numberBetween(800, 1920),
            'height' => $this->faker->numberBetween(600, 1080),
        ];
    }
}
