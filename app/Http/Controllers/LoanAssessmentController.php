<?php

namespace App\Http\Controllers;

use App\Models\LoanApplication;
use App\Services\ApprovalWorkflowService;
use App\Services\CreditAssessmentService;
use App\Services\CustomerCommunicationService;
use App\Services\LoanQuotationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class LoanAssessmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:loan.assessment.create'];
    }

    public function store(Request $request, LoanApplication $loanApplication, CreditAssessmentService $assessments, ApprovalWorkflowService $approvals, LoanQuotationService $quotation, CustomerCommunicationService $communication): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $loanApplication->branch_id !== $request->user()->branch_id, 403);
        $validated = $request->validate([
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'monthly_expenses' => ['required', 'numeric', 'min:0'],
            'existing_debt_payment' => ['required', 'numeric', 'min:0'],
            'risk_grade' => ['required', 'in:A,B,C,D,E'],
            'recommendation' => ['required', 'in:Recommended,Recommended with Conditions,Not Recommended'],
            'recommended_amount' => ['nullable', 'numeric', 'gt:0'],
            'conditions' => ['nullable', 'string', 'max:2000'],
            'field_visit_notes' => ['nullable', 'string', 'max:5000'],
            'rationale' => ['required', 'string', 'max:5000'],
        ]);

        $quote = $quotation->quote(
            $loanApplication->productVersion,
            (float) $loanApplication->requested_amount,
            (int) $loanApplication->requested_term,
            (float) $validated['monthly_income'],
            (float) $validated['monthly_expenses'],
            (float) $validated['existing_debt_payment'],
        );
        $validated['proposed_installment'] = $quote['installment'];
        $validated['recommended_amount'] = $validated['recommended_amount'] ?? $quote['recommended_amount'];

        if ($validated['recommendation'] !== 'Not Recommended' && (float) $validated['recommended_amount'] > $quote['recommended_amount']) {
            return back()->withInput()->withErrors(['recommended_amount' => 'Recommended amount exceeds the affordability-based maximum of '.number_format($quote['recommended_amount'], 2).'.']);
        }

        try {
            $assessments->assess($loanApplication, $request->user(), $validated);

            $approvals->initialize($loanApplication);
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['assessment' => $exception->getMessage()]);
        }

        $communication->assessmentCompleted($loanApplication->customer, $loanApplication->application_number, $validated['recommendation'], (float) $validated['recommended_amount']);

        return back()->with('success', 'Credit assessment recorded successfully.');
    }
}
