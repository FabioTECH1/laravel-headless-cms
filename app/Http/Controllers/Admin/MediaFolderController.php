<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFolder;
use Illuminate\Http\Request;

class MediaFolderController extends Controller
{
    public function index()
    {
        $folders = MediaFolder::with('parent')->withCount('files')->get();

        return response()->json($folders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:media_folders,id',
        ]);

        $folder = MediaFolder::create($request->only(['name', 'parent_id']));

        return response()->json($folder, 201);
    }

    public function update(Request $request, string $id)
    {
        $folder = MediaFolder::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:media_folders,id',
        ]);

        // Prevent circular references
        if ($request->parent_id == $id) {
            return response()->json(['message' => 'A folder cannot be its own parent.'], 422);
        }

        $folder->update($request->only(['name', 'parent_id']));

        return response()->json($folder);
    }

    public function destroy(string $id)
    {
        $folder = MediaFolder::withCount('files')->findOrFail($id);

        // Prevent deletion if folder has files
        if ($folder->files_count > 0) {
            return response()->json([
                'message' => 'Cannot delete folder with files. Please move or delete files first.',
            ], 422);
        }

        // Move child folders to root
        MediaFolder::where('parent_id', $id)->update(['parent_id' => null]);

        $folder->delete();

        return response()->noContent();
    }
}
