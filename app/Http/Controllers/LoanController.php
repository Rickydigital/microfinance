<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class LoanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:loan.view'];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $portfolioScope = Loan::query()
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('loan_number', 'like', "%{$search}%")->orWhereHas('customer', fn ($customer) => $customer->where('customer_number', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status));
        $loans = (clone $portfolioScope)->with(['customer', 'application.productVersion.product'])
            ->latest()->paginate(20)->withQueryString();
        $summary = [
            'accounts' => (clone $portfolioScope)->count(),
            'principal' => (clone $portfolioScope)->sum('principal_amount'),
            'outstanding' => (clone $portfolioScope)->sum('outstanding_principal'),
            'arrears' => (clone $portfolioScope)->sum('arrears_amount'),
        ];
        $statusDistribution = (clone $portfolioScope)->selectRaw('status, COUNT(*) as total, SUM(outstanding_principal) as outstanding')->groupBy('status')->orderByDesc('total')->get();
        $productDistribution = (clone $portfolioScope)->with('application.productVersion.product')->get()->groupBy(fn (Loan $loan): string => $loan->application?->productVersion?->product?->name ?? 'Unclassified')->map(fn ($items, string $name): array => ['name' => $name, 'count' => $items->count(), 'amount' => (float) $items->sum('principal_amount')])->sortByDesc('amount')->values();

        return view('loans.index', compact('loans', 'summary', 'statusDistribution', 'productDistribution'));
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
    public function show(Loan $loan): View
    {
        $loan->load(['customer', 'application.productVersion.product', 'installments.allocations', 'repayments.receiver', 'repayments.allocations', 'transactions', 'collectionCases.assignee', 'collectionCases.actions.performer']);

        return view('loans.show', compact('loan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Loan $loan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Loan $loan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Loan $loan)
    {
        //
    }
}
