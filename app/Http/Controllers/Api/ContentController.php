<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentType;
use App\Models\DynamicEntity;
use App\Services\ContentValidator;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function index(Request $request, string $slug)
    {
        $entity = (new DynamicEntity)->bind($slug);

        $query = $entity->newQuery();

        if ($request->query('status') === 'draft') {
            // For now, allow viewing drafts if requested (in future, add permission check)
        } else {
            $query->published();
        }

        return response()->json($query->paginate());
    }

    public function store(Request $request, string $slug, ContentValidator $validator)
    {
        // Need ContentType for validation
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();

        $rules = $validator->getRules($contentType);
        $attributes = $request->validate($rules);

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
        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, string $slug, string $id, ContentValidator $validator)
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();
        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);

        // Enforce ownership if enabled
        if ($contentType->has_ownership) {
            $user = $request->user();
            // Since route is protected by auth:sanctum, user should be present.
            // But we check to be safe or satisfy static analysis
            abort_unless($user, 401, 'Authentication required.');

            $isOwner = $item->user_id === $user->id;
            $isAdmin = $user->is_admin;

            abort_unless($isOwner || $isAdmin, 403, 'You do not have permission to update this content.');
        }

        $rules = $validator->getRules($contentType, $id);
        $attributes = $request->validate($rules);

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
