<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentType;
use App\Models\DynamicEntity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContentController extends Controller
{
    public function index(string $slug)
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();
        $entity = (new DynamicEntity)->bind($slug);

        // MVP: Simple pagination, latest first
        $items = $entity->latest()->paginate(10);

        return Inertia::render('Content/Index', [
            'contentType' => $contentType,
            'items' => $items,
        ]);
    }

    public function create(string $slug)
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();

        $options = $this->getRelationOptions($contentType);

        return Inertia::render('Content/Form', [
            'contentType' => $contentType,
            'options' => $options,
        ]);
    }

    public function store(Request $request, string $slug)
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();
        $entity = (new DynamicEntity)->bind($slug);

        // Basic validation based on fields presence (can be expanded later)
        $attributes = $request->all();

        $entity->create($attributes);

        return redirect()->route('admin.content.index', $slug)->with('success', 'Content created successfully.');
    }

    public function edit(string $slug, string $id)
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();
        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);

        $options = $this->getRelationOptions($contentType);

        return Inertia::render('Content/Form', [
            'contentType' => $contentType,
            'item' => $item,
            'options' => $options,
        ]);
    }

    public function update(Request $request, string $slug, string $id)
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();
        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);
        $item->update($request->all());

        return redirect()->route('admin.content.index', $slug)->with('success', 'Content updated successfully.');
    }

    public function destroy(string $slug, string $id)
    {
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
}
