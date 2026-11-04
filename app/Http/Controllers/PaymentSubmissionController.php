<?php

namespace App\Http\Controllers;

use App\LoanApplicationStatus;
use App\LoanStatus;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\PaymentSubmission;
use App\Services\PaymentSubmissionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PaymentSubmissionController extends Controller
{
    public function storeRepayment(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($loan->customer_id === $request->user('customer')->id, 404);
        abort_unless(in_array($loan->status, [LoanStatus::Active, LoanStatus::InArrears], true), 422, 'This loan is not accepting payments.');

        return $this->store($request, $loan->application, $loan);
    }

    public function storeAssurance(Request $request, LoanApplication $loanApplication): RedirectResponse
    {
        abort_unless($loanApplication->customer_id === $request->user('customer')->id, 404);
        abort(422, 'Assurance is included in loan installments. Submit a loan repayment after disbursement.');
    }

    private function store(Request $request, LoanApplication $application, ?Loan $loan = null): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99'],
            'payment_method' => ['required', 'in:Cash,Bank,Mobile Money'],
            'external_reference' => ['required', 'string', 'max:150'],
            'paid_at' => ['required', 'date', 'before_or_equal:now'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $file = $request->file('attachment');
        $path = $file?->store('payment-evidence', 'local');
        abort_if($file !== null && $path === false, 500, 'Payment evidence could not be stored.');
        $referenceKey = hash('sha256', $validated['payment_method'].'|'.strtoupper(trim($validated['external_reference'])));
        try {
            DB::transaction(function () use ($validated, $application, $loan, $path, $referenceKey): void {
                $application = LoanApplication::query()->lockForUpdate()->findOrFail($application->id);
                $reference = trim($validated['external_reference']);
                if ($reference === '') {
                    throw ValidationException::withMessages(['external_reference' => 'Enter the payment reference.']);
                }
                $key = $referenceKey;
                if (PaymentSubmission::query()->where('reference_key', $key)->exists()) {
                    throw ValidationException::withMessages(['external_reference' => 'This payment reference has already been submitted.']);
                }
                $purpose = $loan ? 'Repayment' : 'Assurance';
                if ($loan === null) {
                    $reserved = (float) $application->paymentSubmissions()->where('purpose', 'Assurance')->whereIn('status', ['Pending', 'Approved'])->sum('amount');
                    if ($application->status !== LoanApplicationStatus::Approved || round($reserved + (float) $validated['amount'], 2) > $application->assuranceAmount()) {
                        throw ValidationException::withMessages(['amount' => 'The amount exceeds the remaining assurance, including pending submissions.']);
                    }
                }
                PaymentSubmission::query()->create([
                    'customer_id' => $application->customer_id, 'loan_application_id' => $application->id, 'loan_id' => $loan?->id,
                    'purpose' => $purpose, 'amount' => $validated['amount'], 'payment_method' => $validated['payment_method'],
                    'external_reference' => $reference, 'reference_key' => $key, 'paid_at' => $validated['paid_at'], 'attachment_path' => $path, 'status' => 'Pending',
                ]);
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        app(PaymentSubmissionService::class)->notifyReviewers(PaymentSubmission::query()->where('reference_key', $referenceKey)->firstOrFail());

        return back()->with('success', 'Payment submitted. It will count only after staff approval.');
    }

    public function index(Request $request): View
    {
        $submissions = PaymentSubmission::query()->with(['customer', 'application', 'loan', 'repayment'])
            ->whereHas('application', fn ($query) => $query->when($request->user()->branch_id, fn ($query, $branch) => $query->where('branch_id', $branch)))
            ->orderByRaw("CASE WHEN status = 'Pending' THEN 0 ELSE 1 END")->latest()->paginate(20);

        return view('repayments.submissions', compact('submissions'));
    }

    public function review(Request $request, PaymentSubmission $paymentSubmission, PaymentSubmissionService $service): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:Approved,Rejected'], 'review_notes' => ['required_if:decision,Rejected', 'nullable', 'string', 'max:2000']]);
        try {
            $service->review($paymentSubmission, $request->user(), $data['decision'], $data['review_notes'] ?? null);
        } catch (DomainException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return back()->with('success', 'Payment '.$data['decision'].'.');
    }

    public function attachment(Request $request, PaymentSubmission $paymentSubmission): StreamedResponse
    {
        abort_if($paymentSubmission->attachment_path === null, 404);

        if ($request->routeIs('portal.*')) {
            abort_unless($paymentSubmission->customer_id === $request->user('customer')->id, 404);
        } else {
            abort_if($request->user()->branch_id && $request->user()->branch_id !== $paymentSubmission->application->branch_id, 404);
        }

        return Storage::disk('local')->download($paymentSubmission->attachment_path);
    }
}
