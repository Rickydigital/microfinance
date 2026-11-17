<?php

namespace App\Http\Controllers;

use App\Models\LoanApplication;
use App\Models\LoanDisbursement;
use App\Services\CustomerCommunicationService;
use App\Services\LoanDisbursementService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LoanDisbursementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:loan.disbursement.view', only: ['index']),
            new Middleware('can:loan.disbursement.create', only: ['store']),
            new Middleware('can:loan.disbursement.authorize', only: ['authorize']),
        ];
    }

    public function index(Request $request): View
    {
        $applications = LoanApplication::query()->with(['customer', 'productVersion.product'])->where('status', 'Approved')->whereDoesntHave('loan')->whereDoesntHave('disbursement')->where('branch_id', $request->user()->branch_id)->get();
        $period = in_array($request->string('period')->toString(), ['all', 'month', 'today'], true) ? $request->string('period')->toString() : 'all';
        $disbursementScope = LoanDisbursement::query()
            ->where('branch_id', $request->user()->branch_id)
            ->when($period === 'month', fn ($query) => $query->whereBetween('disbursed_at', [today()->startOfMonth(), today()->endOfMonth()]))
            ->when($period === 'today', fn ($query) => $query->whereDate('disbursed_at', today()));
        $disbursements = (clone $disbursementScope)->with(['application.customer', 'creator', 'authorizer'])->latest()->paginate(20)->withQueryString();
        $metrics = [
            'ready' => $applications->count(),
            'pending' => LoanDisbursement::query()->where('branch_id', $request->user()->branch_id)->where('status', 'Pending Authorization')->count(),
            'gross' => (clone $disbursementScope)->where('status', 'Disbursed')->sum('gross_amount'),
            'net' => (clone $disbursementScope)->where('status', 'Disbursed')->sum('net_amount'),
        ];

        return view('loan-disbursements.index', compact('applications', 'disbursements', 'metrics', 'period'));
    }

    public function store(Request $request, LoanDisbursementService $service, CustomerCommunicationService $communication): RedirectResponse
    {
        $validated = $request->validate(['loan_application_id' => ['required', 'exists:loan_applications,id'], 'method' => ['required', 'in:Cash,Bank,Mobile Money'], 'destination_reference' => ['nullable', 'string', 'max:255']]);
        $application = LoanApplication::query()->where('branch_id', $request->user()->branch_id)->findOrFail($validated['loan_application_id']);

        try {
            $disbursement = $service->prepare($application, $request->user(), $validated['method'], $validated['destination_reference'] ?? null);
        } catch (DomainException $exception) {
            return back()->withErrors(['disbursement' => $exception->getMessage()]);
        }

        $communication->disbursementPrepared($application->customer, $application->application_number, (float) $disbursement->gross_amount, (float) $disbursement->deducted_fees, (float) $disbursement->net_amount);

        return back()->with('success', 'Disbursement prepared for authorization.');
    }

    public function authorize(Request $request, LoanDisbursement $loanDisbursement, LoanDisbursementService $service, CustomerCommunicationService $communication): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $loanDisbursement->branch_id !== $request->user()->branch_id, 403);
        $validated = $request->validate(['first_due_date' => ['required', 'date', 'after:today']]);

        try {
            $loan = $service->authorize($loanDisbursement, $request->user(), CarbonImmutable::parse($validated['first_due_date']));
        } catch (DomainException $exception) {
            return back()->withErrors(['authorization' => $exception->getMessage()]);
        }

        $loan->load('customer');
        $communication->loanDisbursed($loan->customer, $loan->loan_number, (float) $loanDisbursement->net_amount);

        return redirect()->route('loans.show', $loan)->with('success', 'Loan disbursed and activated successfully.');
    }
}
