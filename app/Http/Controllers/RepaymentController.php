<?php

namespace App\Http\Controllers;

use App\Data\RepaymentData;
use App\Models\Loan;
use App\Models\NumberSequence;
use App\Models\Repayment;
use App\Services\CustomerCommunicationService;
use App\Services\RepaymentPostingService;
use App\Services\RepaymentReversalService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class RepaymentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:repayment.view', only: ['index', 'show']),
            new Middleware('can:repayment.collect', only: ['create', 'store']),
            new Middleware('can:repayment.reverse', only: ['reverse']),
        ];
    }

    public function index(Request $request): View
    {
        $repaymentScope = Repayment::query()->whereHas('loan', fn ($query) => $query->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId)));
        $repayments = (clone $repaymentScope)->with(['loan.customer', 'receiver'])
            ->whereHas('loan', fn ($query) => $query->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId)))
            ->when(trim($request->string('search')->toString()) !== '', function ($query) use ($request): void {
                foreach (preg_split('/\s+/', trim($request->string('search')->toString())) as $word) {
                    $like = '%'.addcslashes($word, '%_\\').'%';
                    $query->where(function ($sub) use ($like): void {
                        $sub->where('receipt_number', 'like', $like)
                            ->orWhere('external_reference', 'like', $like)
                            ->orWhereHas('loan', fn ($loan) => $loan->where(function ($l) use ($like): void {
                                $l->where('loan_number', 'like', $like)
                                    ->orWhereHas('customer', fn ($customer) => $customer->where(function ($c) use ($like): void {
                                        $c->where('first_name', 'like', $like)
                                            ->orWhere('last_name', 'like', $like)
                                            ->orWhere('phone', 'like', $like);
                                    }));
                            }));
                    });
                }
            })
            ->latest('paid_at')->paginate(20)->withQueryString();

        $loans = Loan::query()->with(['customer', 'installments'])
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->whereIn('status', ['Active', 'In Arrears', 'Restructured'])
            ->whereHas('installments', fn ($query) => $query->whereRaw('(principal_due + interest_due + fees_due + penalties_due) > (principal_paid + interest_paid + fees_paid + penalties_paid)'))
            ->orderBy('loan_number')->get();
        $metrics = [
            'today' => (clone $repaymentScope)->whereDate('paid_at', today())->where('status', 'Posted')->sum('amount'),
            'month' => (clone $repaymentScope)->whereBetween('paid_at', [today()->startOfMonth(), today()->endOfMonth()])->where('status', 'Posted')->sum('amount'),
            'total' => (clone $repaymentScope)->where('status', 'Posted')->sum('amount'),
            'count' => (clone $repaymentScope)->where('status', 'Posted')->count(),
            'unpaid_loans' => $loans->count(),
        ];

        return view('repayments.index', compact('repayments', 'loans', 'metrics'));
    }

    public function create(Request $request): View
    {
        $loans = Loan::query()->with(['customer', 'installments'])
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->whereIn('status', ['Active', 'In Arrears', 'Restructured'])
            ->whereHas('installments', fn ($query) => $query->whereRaw('(principal_due + interest_due + fees_due + penalties_due) > (principal_paid + interest_paid + fees_paid + penalties_paid)'))
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('loan_number', 'like', "%{$search}%")->orWhereHas('customer', fn ($customer) => $customer->where('customer_number', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))))
            ->orderBy('loan_number')->paginate(20)->withQueryString();

        return view('repayments.create', compact('loans'));
    }

    public function store(Request $request, RepaymentPostingService $posting, CustomerCommunicationService $communication): RedirectResponse
    {
        $validated = $request->validate([
            'loan_id' => ['required', 'exists:loans,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'in:Cash,Bank,Mobile Money'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
        ]);
        $loan = Loan::query()->where('branch_id', $request->user()->branch_id)->findOrFail($validated['loan_id']);

        try {
            $repayment = $posting->post(new RepaymentData(
                loanId: $loan->id,
                receivedBy: $request->user()->id,
                receiptNumber: NumberSequence::issue('repayment'),
                transactionNumber: NumberSequence::issue('transaction'),
                amount: (string) $validated['amount'],
                paymentMethod: $validated['payment_method'],
                paidAt: CarbonImmutable::parse($validated['paid_at']),
                externalReference: $validated['external_reference'] ?? null,
                idempotencyKey: $request->header('Idempotency-Key'),
            ));
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        $communication->repaymentReceived($loan->customer, $loan->loan_number, $repayment->receipt_number, number_format((float) $repayment->amount, 2));

        return redirect()->route('repayments.show', $repayment)->with('success', 'Repayment posted successfully.');
    }

    public function show(Repayment $repayment): View
    {
        $repayment->load(['loan.customer', 'receiver', 'allocations.installment']);

        return view('repayments.show', compact('repayment'));
    }

    public function reverse(Request $request, Repayment $repayment, RepaymentReversalService $service): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);

        try {
            $service->reverse($repayment, $request->user(), $validated['reason']);
        } catch (DomainException $exception) {
            return back()->withErrors(['reversal' => $exception->getMessage()]);
        }

        return back()->with('success', 'Repayment reversed through linked counter-transactions.');
    }
}
