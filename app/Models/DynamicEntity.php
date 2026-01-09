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
}
