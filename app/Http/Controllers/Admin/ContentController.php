<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContentRequest;
use App\Models\ContentType;
use App\Models\DynamicEntity;
use Inertia\Inertia;

class ContentController extends Controller
{
    public function index(string $slug)
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();

        if ($contentType->is_component) {
            abort(404);
        }

        $entity = (new DynamicEntity)->bind($slug);

        if ($contentType->is_single) {
            $existing = $entity->first();
            if ($existing) {
                return redirect()->route('admin.content.edit', ['slug' => $slug, 'id' => $existing->id]);
            }

            return redirect()->route('admin.content.create', ['slug' => $slug]);
        }

        $with = [];
        foreach ($contentType->fields as $field) {
            if ($field->type === 'media') {
                $with[] = $field->name;
            }
        }

        // MVP: Simple pagination, latest first
        // Explicitly set 'from' to ensure pagination count query uses the correct table
        $query = $entity->from($entity->getTable())->with($with)->latest();

        if ($contentType->is_localized) {
            $locale = request('locale', 'en');
            $query->where('locale', $locale);
        }

        $items = $query->paginate(10);

        return Inertia::render('Content/Index', [
            'contentType' => $contentType,
            'items' => $items,
            'currentLocale' => request('locale', 'en'),
        ]);
    }

    public function create(string $slug)
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();

        if ($contentType->is_component) {
            abort(404);
        }

        if ($contentType->is_single) {
            $entity = (new DynamicEntity)->bind($slug);
            $existing = $entity->first();
            if ($existing) {
                return redirect()->route('admin.content.edit', ['slug' => $slug, 'id' => $existing->id]);
            }
        }

        $options = $this->getRelationOptions($contentType);

        return Inertia::render('Content/Form', [
            'contentType' => $contentType,
            'options' => $options,
            'components' => $this->getComponents(),
        ]);
    }

    public function store(ContentRequest $request, string $slug)
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();

        if ($contentType->is_component) {
            abort(404);
        }

        $entity = (new DynamicEntity)->bind($slug);

        if ($contentType->is_single && $entity->count() > 0) {
            abort(400, 'Single type already exists.');
        }

        $attributes = $request->all();
        $relations = [];

        foreach ($contentType->fields as $field) {
            if ($field->type === 'relation' && ($field->settings['multiple'] ?? false)) {
                if (array_key_exists($field->name, $attributes)) {
                    $relations[$field->name] = $attributes[$field->name];
                    unset($attributes[$field->name]);
                }
            }
        }

        if ($contentType->is_localized) {
            $attributes['locale'] = $request->input('locale', 'en');
        }

        $item = $entity->create($attributes);

        foreach ($relations as $name => $ids) {
            $item->$name()->sync($ids);
        }

        return redirect()->route('admin.content.index', $slug)->with('success', 'Content created successfully.');
    }

    public function edit(string $slug, string $id)
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();

        if ($contentType->is_component) {
            abort(404);
        }

        $entity = (new DynamicEntity)->bind($slug);

        $with = [];
        foreach ($contentType->fields as $field) {
            if ($field->type === 'media') {
                $with[] = $field->name;
            }
            if ($field->type === 'relation' && ($field->settings['multiple'] ?? false)) {
                $with[] = $field->name;
            }
        }

        if (! empty($with)) {
            $entity = $entity->with($with);
        }

        $item = $entity->findOrFail($id);

        $options = $this->getRelationOptions($contentType);

        return Inertia::render('Content/Form', [
            'contentType' => $contentType,
            'item' => $item,
            'options' => $options,
            'components' => $this->getComponents(),
        ]);
    }

    public function update(ContentRequest $request, string $slug, string $id)
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();

        if ($contentType->is_component) {
            abort(404);
        }

        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);

        $attributes = $request->all();
        $relations = [];

        foreach ($contentType->fields as $field) {
            if ($field->type === 'relation' && ($field->settings['multiple'] ?? false)) {
                if (array_key_exists($field->name, $attributes)) {
                    $relations[$field->name] = $attributes[$field->name];
                    unset($attributes[$field->name]);
                }
            }
        }

        if ($contentType->is_localized) {
            $attributes['locale'] = $request->input('locale', 'en');
        }

        $item->update($attributes);

        foreach ($relations as $name => $ids) {
            $item->$name()->sync($ids);
        }

        if ($contentType->is_single) {
            return redirect()->back()->with('success', 'Content updated successfully.');
        }

        return redirect()->route('admin.content.index', $slug)->with('success', 'Content updated successfully.');
    }

    public function destroy(string $slug, string $id)
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();

        if ($contentType->is_component) {
            abort(404);
        }

        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Content deleted successfully.');
    }

    protected function getRelationOptions(ContentType $contentType): array
    {
        $options = [];

        foreach ($contentType->fields as $field) {
            if ($field->type === 'relation' && ! empty($field->settings['related_content_type_id'])) {
                $relatedType = ContentType::with('fields')->find($field->settings['related_content_type_id']);

                if ($relatedType) {
                    $relatedEntity = (new DynamicEntity)->bind($relatedType->slug);

                    // Find a suitable label field (first text field or ID)
                    $labelField = $relatedType->fields->firstWhere('type', 'text')?->name ?? 'id';

                    $options[$field->name] = $relatedEntity->select('id', "$labelField as label")->get();
                }
            }
        }

        return $options;
    }

    protected function getComponents()
    {
        return ContentType::where('is_component', true)->with('fields')->get();
    }
}
