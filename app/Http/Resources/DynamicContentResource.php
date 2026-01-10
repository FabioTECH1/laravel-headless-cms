<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DynamicContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        $attributes = $this->getAttributes();

        foreach ($attributes as $key => $value) {
            // Skip standard fields we already added
            if (in_array($key, array_keys($data))) {
                continue;
            }

            // If the field is 'cover_image_id', the relation name is 'cover_image'.
            if (str_ends_with($key, '_id')) {
                $relationName = substr($key, 0, -3);

                // If the relation is loaded, use it.
                if ($this->relationLoaded($relationName)) {
                    $data[$relationName] = $this->$relationName;

                    continue;
                }
            }

            $data[$key] = $value;
        }

        return $data;
    }
}
