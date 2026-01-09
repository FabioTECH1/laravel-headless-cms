<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SchemaManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchemaController extends Controller
{
    public function index()
    {
        return Inertia::render('Schema/Index', [
            'types' => \App\Models\ContentType::latest()->get(),
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
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.name' => ['required', 'string', 'alpha_dash'],
            'fields.*.type' => ['required', 'in:text,longtext,integer,boolean,datetime'],
        ]);

        try {
            $manager->createType($request->name, $request->fields);
        } catch (\Exception $e) {
            return back()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('admin.dashboard')->with('success', 'Schema created successfully.');
    }
}
