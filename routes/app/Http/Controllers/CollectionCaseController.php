<?php

namespace App\Http\Controllers;

use App\Models\CollectionCase;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CollectionCaseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:collection.case.view', only: ['index', 'show']), new Middleware('can:collection.case.assign', only: ['assign'])];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $collectionCases = CollectionCase::query()->with(['loan.customer', 'assignee'])
            ->whereHas('loan', fn ($query) => $query->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId)))
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('case_number', 'like', "%{$search}%")->orWhereHas('loan', fn ($loan) => $loan->where('loan_number', 'like', "%{$search}%")->orWhereHas('customer', fn ($customer) => $customer->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")))))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->latest()->paginate(20)->withQueryString();

        return view('collection-cases.index', compact('collectionCases'));
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CollectionCase $collectionCase): View
    {
        $collectionCase->load(['loan.customer', 'assignee', 'actions']);
        $recoveryOfficers = User::query()->role('Recovery Officer')->where('branch_id', $collectionCase->loan->branch_id)->where('is_active', true)->get();

        return view('collection-cases.show', compact('collectionCase', 'recoveryOfficers'));
    }

    public function assign(Request $request, CollectionCase $collectionCase): RedirectResponse
    {
        $validated = $request->validate(['assigned_to' => ['required', 'exists:users,id']]);
        $officer = User::query()->where('branch_id', $collectionCase->loan->branch_id)->findOrFail($validated['assigned_to']);
        abort_unless($officer->hasRole('Recovery Officer'), 422, 'Selected employee is not a recovery officer.');
        $collectionCase->update(['assigned_to' => $officer->id, 'status' => 'In Progress']);

        return back()->with('success', 'Recovery case assigned successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CollectionCase $collectionCase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CollectionCase $collectionCase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CollectionCase $collectionCase)
    {
        //
    }
}
