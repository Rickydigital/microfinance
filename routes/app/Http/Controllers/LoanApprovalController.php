<?php

namespace App\Http\Controllers;

use App\Models\LoanApproval;
use App\Services\ApprovalWorkflowService;
use App\Services\CustomerCommunicationService;
use App\Services\LoanQuotationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class LoanApprovalController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:loan.approval.view', only: ['index']),
            new Middleware('can:loan.approval.decide', only: ['update']),
        ];
    }

    public function index(Request $request): View
    {
        $roles = $request->user()->getRoleNames();
        $approvals = LoanApproval::query()->with(['application.customer', 'application.productVersion.product'])
            ->when(! $request->user()->hasRole('System Administrator'), fn ($query) => $query->whereIn('required_role', $roles))
            ->when($request->user()->branch_id, fn ($query, $branchId) => $query->whereHas('application', fn ($application) => $application->where('branch_id', $branchId)))
            ->where('status', 'Pending')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('loan_approvals as earlier')
                    ->whereColumn('earlier.loan_application_id', 'loan_approvals.loan_application_id')
                    ->whereColumn('earlier.sequence', '<', 'loan_approvals.sequence')
                    ->where('earlier.status', '!=', 'Approved');
            })
            ->orderBy('created_at')->paginate(20);

        return view('loan-approvals.index', compact('approvals'));
    }

    public function update(Request $request, LoanApproval $loanApproval, ApprovalWorkflowService $workflow, CustomerCommunicationService $communication, LoanQuotationService $quotation): RedirectResponse
    {
        abort_if($request->user()->branch_id !== null && $loanApproval->application()->value('branch_id') !== $request->user()->branch_id, 403);
        $validated = $request->validate(['decision' => ['required', 'in:Approved,Rejected,Returned'], 'comments' => ['required', 'string', 'max:3000'], 'conditions' => ['nullable', 'string', 'max:3000']]);

        try {
            $workflow->decide($loanApproval, $request->user(), $validated['decision'], $validated['comments'], $validated['conditions'] ?? null);
        } catch (DomainException $exception) {
            return back()->withErrors(['decision' => $exception->getMessage()]);
        }

        $application = $loanApproval->application()->with(['customer', 'productVersion.fees'])->firstOrFail();
        if ($application->approved_amount !== null) {
            $quote = $quotation->quote($application->productVersion, (float) $application->approved_amount, $application->requested_term, (float) $application->declared_monthly_income, (float) $application->declared_monthly_expenses, (float) $application->existing_debt_payment);
            $communication->approvalDecision($application->customer, $application->application_number, $application->status->value, (float) $application->approved_amount, (float) $quote['total_fees'], (float) $quote['estimated_net_disbursement']);
        } else {
            $communication->approvalDecision($application->customer, $application->application_number, $validated['decision']);
        }

        return back()->with('success', 'Approval decision recorded successfully.');
    }
}
