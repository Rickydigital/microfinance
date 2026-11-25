<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRule;
use App\Models\Branch;
use App\Models\LoanProduct;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class ApprovalRuleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:loan.approval.view', only: ['index', 'show']),
            new Middleware('can:admin.configuration.update', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $approvalRules = ApprovalRule::query()
            ->with(['product:id,code,name', 'branch:id,code,name'])
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->string('status')->value() === 'active'))
            ->orderBy('loan_product_id')
            ->orderBy('branch_id')
            ->orderBy('minimum_amount')
            ->orderBy('sequence')
            ->paginate(25)
            ->withQueryString();

        return view('approval-rules.index', $this->formOptions() + compact('approvalRules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('approval-rules.create', $this->formOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request);
        $this->ensureRuleDoesNotOverlap($attributes);

        $approvalRule = DB::transaction(fn (): ApprovalRule => ApprovalRule::query()->create($attributes));

        activity()->performedOn($approvalRule)->causedBy($request->user())->withProperties(['attributes' => $attributes])->log('Approval rule created');

        return redirect()->route('approval-rules.index')->with('success', 'Approval rule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ApprovalRule $approvalRule): View
    {
        $approvalRule->load(['product:id,code,name', 'branch:id,code,name']);

        return view('approval-rules.show', compact('approvalRule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ApprovalRule $approvalRule): View
    {
        return view('approval-rules.edit', [
            'approvalRule' => $approvalRule,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ApprovalRule $approvalRule): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request, $approvalRule);
        $this->ensureRuleDoesNotOverlap($attributes, $approvalRule);

        DB::transaction(fn () => $approvalRule->update($attributes));

        activity()->performedOn($approvalRule)->causedBy($request->user())->withProperties(['attributes' => $attributes])->log('Approval rule updated');

        return redirect()->route('approval-rules.index')->with('success', 'Approval rule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, ApprovalRule $approvalRule): RedirectResponse
    {
        if ($approvalRule->loanApprovals()->exists()) {
            return back()->with('error', 'This rule has approval history and cannot be deleted. Deactivate it instead.');
        }

        activity()->performedOn($approvalRule)->causedBy($request->user())->withProperties(['attributes' => $approvalRule->toArray()])->log('Approval rule deleted');
        $approvalRule->delete();

        return redirect()->route('approval-rules.index')->with('success', 'Approval rule deleted successfully.');
    }

    /** @return array{products: \Illuminate\Database\Eloquent\Collection<int, LoanProduct>, branches: \Illuminate\Database\Eloquent\Collection<int, Branch>, roles: Collection<int, string>} */
    private function formOptions(): array
    {
        return [
            'products' => LoanProduct::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ];
    }

    /** @return array<string, mixed> */
    private function validatedAttributes(Request $request, ?ApprovalRule $approvalRule = null): array
    {
        $attributes = $request->validate([
            'loan_product_id' => ['nullable', 'integer', Rule::exists('loan_products', 'id')->where('is_active', true)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'minimum_amount' => ['required', 'numeric', 'min:0'],
            'maximum_amount' => ['nullable', 'numeric', 'gt:minimum_amount'],
            'risk_grade' => ['nullable', 'string', 'max:50'],
            'role_name' => ['required', 'string', Rule::exists('roles', 'name')],
            'sequence' => ['required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $attributes['is_active'] = $request->boolean('is_active');

        return $attributes;
    }

    /** @param array<string, mixed> $attributes */
    private function ensureRuleDoesNotOverlap(array $attributes, ?ApprovalRule $approvalRule = null): void
    {
        if (! $attributes['is_active']) {
            return;
        }

        $overlapExists = ApprovalRule::query()
            ->where('is_active', true)
            ->where('role_name', $attributes['role_name'])
            ->where('sequence', $attributes['sequence'])
            ->where(fn (Builder $query) => $attributes['loan_product_id'] === null ? $query->whereNull('loan_product_id') : $query->where('loan_product_id', $attributes['loan_product_id']))
            ->where(fn (Builder $query) => $attributes['branch_id'] === null ? $query->whereNull('branch_id') : $query->where('branch_id', $attributes['branch_id']))
            ->when($approvalRule, fn (Builder $query) => $query->whereKeyNot($approvalRule->getKey()))
            ->where('minimum_amount', '<=', $attributes['maximum_amount'] ?? PHP_INT_MAX)
            ->where(fn (Builder $query) => $query->whereNull('maximum_amount')->orWhere('maximum_amount', '>=', $attributes['minimum_amount']))
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages([
                'minimum_amount' => 'This amount band overlaps an active rule at the same approval level.',
            ]);
        }
    }
}
