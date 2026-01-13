<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaRequest;
use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = MediaFile::with('folder')->latest();

        if ($request->has('folder_id')) {
            if ($request->folder_id === 'null' || $request->folder_id === '') {
                $query->whereNull('folder_id');
            } else {
                $query->where('folder_id', $request->folder_id);
            }
        }

        if ($request->has('search')) {
            $query->where('filename', 'like', '%'.$request->search.'%');
        }

        $media = $query->paginate(24);

        return response()->json($media);
    }

    public function store(MediaRequest $request)
    {
        $file = $request->file('file');

        $path = $file->store('uploads', 'public');

        $data = [
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => 'public',
            'folder_id' => $request->folder_id,
            'alt_text' => $request->alt_text,
            'caption' => $request->caption,
        ];

        // Extract image dimensions if it's an image
        if (str_starts_with($file->getMimeType(), 'image/')) {
            try {
                [$width, $height] = getimagesize($file->getRealPath());
                $data['width'] = $width;
                $data['height'] = $height;
            } catch (\Exception $e) {
                // If we can't get dimensions, just continue without them
            }
        }

        $media = MediaFile::create($data);

        return response()->json([
            'id' => $media->id,
            'url' => $media->url,
            'filename' => $media->filename,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
        ]);
    }

    public function update(MediaRequest $request, string $id)
    {
        $media = MediaFile::findOrFail($id);

        $media->update($request->only(['alt_text', 'caption', 'folder_id']));

        return response()->json($media->load('folder'));
    }

    public function destroy(string $id)
    {
        $media = MediaFile::findOrFail($id);

        // Delete the physical file
        Storage::disk($media->disk)->delete($media->path);

        $media->delete();

        return response()->noContent();
    }
}
