<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContentIndexRequest;
use App\Http\Requests\ContentRequest;
use App\Http\Resources\DynamicContentResource;
use App\Models\ContentType;
use App\Models\DynamicEntity;
use App\Services\QueryBuilderService;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function __construct(private QueryBuilderService $queryBuilder) {}

    public function index(ContentIndexRequest $request, string $slug)
    {
        $validated = $request->validated();
        $contentType = ContentType::where('slug', $slug)->firstOrFail();

        if (! $contentType->is_public && ! auth('sanctum')->check()) {
            abort(401, 'Unauthenticated.');
        }

        $entity = (new DynamicEntity)->bind($slug);

        $query = $entity->newQuery();

        if (isset($validated['status']) && $validated['status'] === 'draft') {
            // For now, allow viewing drafts if requested (in future, add permission check)
        } else {
            $query->published();
        }

        if ($contentType->is_localized) {
            $locale = $request->query('locale', 'en');
            $query->where('locale', $locale);
        }

        // Apply QueryBuilder params (filters, sort, fields, populate)
        $query = $this->queryBuilder->apply($query, $validated);

        if ($contentType->is_single) {
            $item = $query->first();

            if (! $item) {
                return response()->json(['data' => null]);
            }

            return new DynamicContentResource($item);
        }

        $perPage = $validated['per_page'] ?? 10;
        $query = $query->paginate($perPage);

        return response()->json([
            'data' => DynamicContentResource::collection($query->items())->resolve(),
            'pagination' => [
                'current_page' => $query->currentPage(),
                'total_pages' => $query->lastPage(),
                'total_items' => $query->total(),
                'per_page' => $query->perPage(),
            ],
        ]);
    }

    public function store(ContentRequest $request, string $slug)
    {
        // Need ContentType for other logic (ownership), but validation is done.
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();

        $attributes = $request->validated();
        $relations = [];

        foreach ($contentType->fields as $field) {
            if ($field->type === 'relation' && ($field->settings['multiple'] ?? false)) {
                if (array_key_exists($field->name, $attributes)) {
                    $relations[$field->name] = $attributes[$field->name];
                    unset($attributes[$field->name]);
                }
            }
        }

        // Handle status -> published_at mapping
        if (isset($attributes['status'])) {
            if ($attributes['status'] === 'published' && empty($attributes['published_at'])) {
                $attributes['published_at'] = now();
            } elseif ($attributes['status'] === 'draft') {
                $attributes['published_at'] = null;
            }
            unset($attributes['status']);
        }

        // Auto-assign user_id if ownership is enabled
        if ($contentType->has_ownership) {
            $attributes['user_id'] = $request->user()->id;
        }

        if ($contentType->is_localized) {
            $attributes['locale'] = $request->input('locale', 'en');
        }

        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->create($attributes);

        foreach ($relations as $name => $ids) {
            $item->$name()->sync($ids);
        }

        return (new DynamicContentResource($item))
            ->response($request)
            ->setStatusCode(201);
    }

    public function show(Request $request, string $slug, string $id)
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();

        if (! $contentType->is_public && ! auth('sanctum')->check()) {
            abort(401, 'Unauthenticated.');
        }

        $entity = (new DynamicEntity)->bind($slug);

        $query = $entity->newQuery();

        if ($contentType->is_localized) {
            $locale = $request->query('locale', 'en');
            // Enforce locale check?
            // Strapi: findOne(id) returns entry.
            // If we want to support ?locale=fr, we should filter by it if entry has locale.
            // But ID is unique.
            // Let's just allow reading by ID for now, ignoring locale filter, or verify it matches query.
            // Verification:
            if ($request->has('locale')) {
                $query->where('locale', $request->query('locale'));
            }
        }

        // Apply QueryBuilder params (fields, populate only usually for show)
        // We pass $request->all() directly as show isn't using a FormRequest with validation rules yet
        $this->queryBuilder->apply($query, $request->all());

        $item = $query->findOrFail($id);

        return new DynamicContentResource($item);
    }

    public function update(ContentRequest $request, string $slug, string $id)
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();
        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);

        // Enforce ownership if enabled
        if ($contentType->has_ownership) {
            $user = $request->user();
            abort_unless($user, 401, 'Authentication required.');

            $isOwner = $item->user_id === $user->id;
            $isAdmin = $user->is_admin;

            abort_unless($isOwner || $isAdmin, 403, 'You do not have permission to update this content.');
        }

        $attributes = $request->validated();
        $relations = [];

        foreach ($contentType->fields as $field) {
            if ($field->type === 'relation' && ($field->settings['multiple'] ?? false)) {
                if (array_key_exists($field->name, $attributes)) {
                    $relations[$field->name] = $attributes[$field->name];
                    unset($attributes[$field->name]);
                }
            }
        }

        // Handle status -> published_at mapping
        if (isset($attributes['status'])) {
            if ($attributes['status'] === 'published') {
                // Only set published_at if it wasn't already published, or if specifically requested?
                // Usually "Publish" action sets it to now if null.
                if (empty($attributes['published_at']) && is_null($item->published_at)) {
                    $attributes['published_at'] = now();
                }
                // If already published, do we keep original date? Yes, typically.
            } elseif ($attributes['status'] === 'draft') {
                $attributes['published_at'] = null;
            }
            unset($attributes['status']);
        }

        // Prevent changing user_id
        unset($attributes['user_id']);

        $item->update($attributes);

        foreach ($relations as $name => $ids) {
            $item->$name()->sync($ids);
        }

        return new DynamicContentResource($item);
    }

    public function destroy(Request $request, string $slug, string $id)
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();
        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);

        // Enforce ownership if enabled
        if ($contentType->has_ownership) {
            $user = $request->user();
            abort_unless($user, 401, 'Authentication required.');

            $isOwner = $item->user_id === $user->id;
            $isAdmin = $user->is_admin;

            abort_unless($isOwner || $isAdmin, 403, 'You do not have permission to delete this content.');
        }

        $item->delete();

        return response()->noContent();
    }
}
