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

        $tableName = Str::plural(Str::snake($this->contentType->name));
        $this->setTable($tableName);

        foreach ($this->contentType->fields as $field) {
            $type = match ($field->type) {
                'boolean' => 'boolean',
                'integer' => 'integer',
                'datetime' => 'datetime',
                'json' => 'array',
                'component' => 'array',
                'dynamic_zone' => 'array',
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

            if ($field && $field->type === 'relation') {
                $relatedTypeId = $field->settings['related_content_type_id'] ?? null;
                $relatedType = ContentType::find($relatedTypeId);

                if ($relatedType) {
                    $relatedModel = new DynamicEntity;
                    $relatedModel->bind($relatedType->slug);

                    if ($field->settings['multiple'] ?? false) {
                        $slugs = [Str::singular($this->contentType->slug), Str::singular($relatedType->slug)];
                        sort($slugs);
                        $pivotTableName = implode('_', $slugs);

                        $relation = $this->belongsToMany(DynamicEntity::class, $pivotTableName, $slugs[0].'_id', $slugs[1].'_id');

                        $relation->getRelated()->bind($relatedType->slug);

                        return $relation;
                    }

                    return $this->belongsTo(DynamicEntity::class, $method.'_id');
                }
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
            $model->mergeCasts($this->casts);
        }

        return $model;
    }
}
