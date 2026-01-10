<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function create()
    {
        return Inertia::render('Schema/Create', [
            'existingTypes' => ContentType::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request, SchemaManager $manager)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'alpha_num'],
            'is_public' => ['boolean'],
            'has_ownership' => ['boolean'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.name' => ['required', 'string', 'alpha_dash'],
            'fields.*.type' => ['required', 'in:text,longtext,integer,boolean,datetime,media,relation'],
            'fields.*.settings.related_content_type_id' => ['required_if:fields.*.type,relation', 'nullable', 'exists:content_types,id'],
        ]);

        try {
            $manager->createType(
                $request->name,
                $request->fields,
                $request->boolean('is_public', false),
                $request->boolean('has_ownership', false)
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
            'existingTypes' => ContentType::select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, string $slug, SchemaManager $manager)
    {
        $request->validate([
            'is_public' => ['boolean'],
            'has_ownership' => ['boolean'],
            'fields' => ['array'],
            'fields.*.name' => ['required', 'string', 'alpha_dash'],
            'fields.*.type' => ['required', 'in:text,longtext,integer,boolean,datetime,media,relation'],
            'fields.*.settings.related_content_type_id' => ['required_if:fields.*.type,relation', 'nullable', 'exists:content_types,id'],
        ]);

        try {
            $manager->updateType(
                $slug,
                $request->fields ?? [],
                $request->boolean('is_public'),
                $request->boolean('has_ownership')
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
