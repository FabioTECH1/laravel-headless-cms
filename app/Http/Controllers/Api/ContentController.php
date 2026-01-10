<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContentRequest;
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

        return response()->json($query->paginate());
    }

    public function store(ContentRequest $request, string $slug)
    {
        // Need ContentType for other logic (ownership), but validation is done.
        $contentType = ContentType::where('slug', $slug)->firstOrFail();

        $attributes = $request->validated();

        // Auto-assign user_id if ownership is enabled
        if ($contentType->has_ownership) {
            $attributes['user_id'] = $request->user()->id;
        }

        $entity = (new DynamicEntity)->bind($slug);
        $item = $entity->create($attributes);

        return response()->json($item, 201);
    }

    public function show(string $slug, string $id)
    {
        $contentType = ContentType::where('slug', $slug)->firstOrFail();

        if (! $contentType->is_public && ! auth('sanctum')->check()) {
            abort(401, 'Unauthenticated.');
        }

        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);

        return response()->json($item);
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

        // Prevent changing user_id
        unset($attributes['user_id']);

        $item->update($attributes);

        return response()->json($item);
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
