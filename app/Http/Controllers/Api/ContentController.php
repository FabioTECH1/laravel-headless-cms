<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DynamicEntity;
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

    public function store(Request $request, string $slug)
    {
        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->create($request->all());

        return response()->json($item, 201);
    }

    public function show(string $slug, string $id)
    {
        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, string $slug, string $id)
    {
        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);
        $item->update($request->all());

        return response()->json($item);
    }

    public function destroy(string $slug, string $id)
    {
        $entity = (new DynamicEntity)->bind($slug);

        $item = $entity->findOrFail($id);
        $item->delete();

        return response()->noContent();
    }
}
