<?php

namespace App\Http\Controllers;

use App\Models\CollectionCase;
use App\Models\JournalLine;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\Repayment;
use App\Services\CustomerKycReadinessService;
use App\Services\LoanApplicationPresentationService;
use App\Services\LoanQuotationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PdfDocumentController extends Controller
{
    public function loanApplication(Request $request, LoanApplication $loanApplication, LoanQuotationService $quotation, CustomerKycReadinessService $kycReadiness, LoanApplicationPresentationService $presentation): Response
    {
        abort_unless($request->user()->can('loan.application.view'), 403);
        abort_if($request->user()->branch_id !== null && $loanApplication->branch_id !== $request->user()->branch_id, 403);
        $loanApplication->load(['branch', 'creator', 'customer.addresses', 'customer.identifications', 'customer.documents', 'customer.loans', 'customer.loanApplications', 'productVersion.product', 'productVersion.fees', 'assessments.assessor', 'approvals.decisionMaker', 'checklistItems.completedBy', 'guarantors.guarantor', 'collaterals.collateral', 'disbursement', 'loan.installments']);
        $requestedQuote = $quotation->quote($loanApplication->productVersion, (float) $loanApplication->requested_amount, $loanApplication->requested_term, (float) $loanApplication->declared_monthly_income, (float) $loanApplication->declared_monthly_expenses, (float) $loanApplication->existing_debt_payment);
        $approvedQuote = $loanApplication->approved_amount ? $quotation->quote($loanApplication->productVersion, (float) $loanApplication->approved_amount, $loanApplication->requested_term, (float) $loanApplication->declared_monthly_income, (float) $loanApplication->declared_monthly_expenses, (float) $loanApplication->existing_debt_payment) : null;
        $kyc = $kycReadiness->evaluate($loanApplication->customer);
        $dossier = $presentation->build($loanApplication, $approvedQuote ?? $requestedQuote, $kyc);

        return Pdf::loadView('pdf.loan-application', compact('loanApplication', 'requestedQuote', 'approvedQuote', 'kyc', 'dossier'))->setPaper('a4')->download($loanApplication->application_number.'-dossier.pdf');
    }

    public function repayment(Request $request, Repayment $repayment): Response
    {
        abort_unless($request->user()->can('repayment.receipt.print'), 403);
        $repayment->load(['loan.customer', 'receiver', 'allocations.installment']);
        abort_if($request->user()->branch_id !== null && $repayment->loan->branch_id !== $request->user()->branch_id, 403);

        return Pdf::loadView('pdf.repayment', compact('repayment'))->setPaper('a4')->download($repayment->receipt_number.'.pdf');
    }

    public function report(Request $request, string $report): Response
    {
        $definition = $this->reportDefinition($request, $report);
        $branchId = $request->user()->branch_id;
        $metrics = ['active_loans' => Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('status', 'Active')->count(), 'outstanding_principal' => Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->sum('outstanding_principal'), 'collections' => Repayment::query()->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->where('status', 'Posted')->sum('amount'), 'open_cases' => CollectionCase::query()->when($branchId, fn ($query) => $query->whereHas('loan', fn ($loan) => $loan->where('branch_id', $branchId)))->whereNotIn('status', ['Closed', 'Resolved'])->count(), 'debits' => JournalLine::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->sum('debit'), 'credits' => JournalLine::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->sum('credit')];

        return Pdf::loadView('pdf.report', compact('report', 'definition', 'metrics'))->setPaper('a4')->download($report.'-report-'.today()->format('Ymd').'.pdf');
    }

    /** @return array{label: string, dataset: string, group: string, group_label: string, permission: string} */
    private function reportDefinition(Request $request, string $report): array
    {
        $report = [
            'operational' => 'branch-performance',
            'portfolio' => 'portfolio-summary',
            'financial' => 'balance-sheet',
            'risk' => 'portfolio-quality-par',
            'collections' => 'collection-performance',
            'audit' => 'audit-trail',
        ][$report] ?? $report;

        foreach (config('microfinance.reports') as $groupKey => $group) {
            foreach ($group['reports'] as $slug => $definition) {
                if ($slug !== $report) {
                    continue;
                }

                $permission = $definition['permission'] ?? $group['permission'];
                abort_unless($request->user()->can($permission), 403);

                return $definition + [
                    'group' => $groupKey,
                    'group_label' => $group['label'],
                    'permission' => $permission,
                ];
            }
        }

        abort(404);
    }
}
