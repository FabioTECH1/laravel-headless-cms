<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class DynamicEntity extends Model
{
    use HasUlids;

    protected $guarded = [];

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    protected $contentType;

    public function bind(string $slug): self
    {
        $this->contentType = ContentType::where('slug', $slug)->with('fields')->first();

        if (! $this->contentType) {
            throw new ModelNotFoundException("Content Type with slug [{$slug}] not found.");
        }

        // Logic must match SchemaManager's table naming convention
        $tableName = Str::plural(Str::snake($this->contentType->name));
        $this->setTable($tableName);

        foreach ($this->contentType->fields as $field) {
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
        return $this;
    }

    /**
     * Map dynamic relationships.
     */
    public function __call($method, $parameters)
    {
        if ($this->contentType) {
            $field = $this->contentType->fields->where('name', $method)->first();

            if ($field && $field->type === 'media') {
                return $this->belongsTo(MediaFile::class, $method.'_id');
            }
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Create a new instance of the given model.
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = parent::newInstance($attributes, $exists);

        $model->setTable($this->getTable());

        if (isset($this->contentType)) {
            $model->contentType = $this->contentType;
            // Re-apply casts if needed, though bind() logic did it.
            // Since newInstance doesn't call bind, we might lose casts if not careful.
            // Let's copy casts too.
            $model->mergeCasts($this->casts);
        }

        return $model;
    }
}
