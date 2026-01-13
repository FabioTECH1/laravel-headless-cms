<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchemaRequest;
use App\Models\ContentType;
use App\Services\SchemaManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchemaController extends Controller
{
    public function index()
    {
        return Inertia::render('Schema/Index', [
            'types' => ContentType::latest()->get(),
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Schema/Create', [
            'existingTypes' => ContentType::select('id', 'name', 'is_component')->get(),
            'isComponent' => $request->boolean('is_component'),
        ]);
    }

    public function store(SchemaRequest $request, SchemaManager $manager)
    {
        // Validation handled by SchemaRequest

        try {
            $manager->createType(
                $request->name,
                $request->fields,
                $request->boolean('is_public', false),
                $request->boolean('has_ownership', false),
                $request->boolean('is_component', false),
                $request->boolean('is_single', false),
                $request->boolean('is_localized', false)
            );
        } catch (\Exception $e) {
            return back()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('admin.dashboard')->with('success', 'Schema created successfully.');
    }

    public function edit(string $slug)
    {
        $contentType = ContentType::where('slug', $slug)->with('fields')->firstOrFail();

        return Inertia::render('Schema/Edit', [
            'contentType' => $contentType,
            'existingTypes' => ContentType::select('id', 'name', 'is_component')->get(),
        ]);
    }

    public function update(SchemaRequest $request, string $slug, SchemaManager $manager)
    {
        // Validation handled by SchemaRequest

        try {
            $manager->updateType(
                $slug,
                $request->fields ?? [],
                $request->boolean('is_public'),
                $request->boolean('has_ownership'),
                $request->boolean('is_component'),
                $request->boolean('is_single'),
                $request->boolean('is_localized')
            );
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.schema.index')->with('success', 'Schema updated successfully.');
    }

    public function destroy(string $slug, SchemaManager $manager)
    {
        try {
            $manager->deleteType($slug);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('admin.schema.index')->with('success', 'Schema deleted successfully.');
    }
}
