<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContentRequest;
use App\Http\Resources\DynamicContentResource;
use App\Models\ContentType;
use App\Models\DynamicEntity;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function index(Request $request, string $slug)
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();

        if (! $contentType->is_public && ! auth('sanctum')->check()) {
            abort(401, 'Unauthenticated.');
        }

        $entity = (new DynamicEntity)->bind($slug);

        $query = $entity->newQuery();

        if ($request->query('status') === 'draft') {
            // For now, allow viewing drafts if requested (in future, add permission check)
        } else {
            $query->published();
        }

        // Eager load if needed, but for now we rely on lazy loading in Resource or pre-load if we knew fields.
        // DynamicEntity doesn't expose fields easily to query builder without iteration.

        return DynamicContentResource::collection($query->paginate());
    }

    public function store(ContentRequest $request, string $slug)
    {
        // Need ContentType for other logic (ownership), but validation is done.
        $contentType = ContentType::where('slug', $slug)->firstOrFail();

        $attributes = $request->validated();

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

        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->create($attributes);

        return (new DynamicContentResource($item))
            ->response($request)
            ->setStatusCode(201);
    }

    public function show(string $slug, string $id)
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();

        if (! $contentType->is_public && ! auth('sanctum')->check()) {
            abort(401, 'Unauthenticated.');
        }

        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);

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
