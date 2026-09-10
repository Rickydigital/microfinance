<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BranchController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:admin.branch.view', only: ['index', 'show']),
            new Middleware('can:admin.branch.create', only: ['create', 'store']),
            new Middleware('can:admin.branch.update', only: ['edit', 'update', 'destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $branches = Branch::query()->withCount(['users', 'customers', 'loans'])->orderBy('name')->paginate(20);

        return view('administration.branches', compact('branches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:30', 'unique:branches,code'], 'name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email'], 'address' => ['nullable', 'string', 'max:500']]);
        Branch::create($validated + ['institution_id' => Institution::query()->value('id'), 'is_active' => true]);

        return back()->with('success', 'Branch created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        //
    }
}
