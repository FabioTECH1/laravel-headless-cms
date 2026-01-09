<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class DynamicEntity extends Model
{
    protected $guarded = [];

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function bind(string $slug): self
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->first();

        if (! $contentType) {
            throw new ModelNotFoundException("Content Type with slug [{$slug}] not found.");
        }

        // Logic must match SchemaManager's table naming convention
        $tableName = Str::plural(Str::snake($contentType->name));
        $this->setTable($tableName);

        foreach ($contentType->fields as $field) {
            $type = match ($field->type) {
                'boolean' => 'boolean',
                'integer' => 'integer',
                'datetime' => 'datetime',
                default => null,
            };

            if ($type) {
                $this->casts[$field->name] = $type;
            }
        }

        return $this;
    }

    public function resolveRelations()
    {
        // Simple way to hydrate related models if they are loaded or foreign keys exist.
        // For MVP, we might rely on naming convention if we want 'post->image' to work.
        // But for now, let's just facilitate the creation and basic access.

        // Dynamic Relationship Accessor (Magic Method) could go here.
        return $this;
    }

    /**
     * Map dynamic relationships.
     */
    public function __call($method, $parameters)
    {
        // Check if method matches a field name that is a relation/media type
        // This requires knowing the current schema fields. But we only check slug on bind.

        return parent::__call($method, $parameters);
    }
}
