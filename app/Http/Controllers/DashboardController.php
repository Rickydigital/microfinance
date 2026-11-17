<?php

namespace App\Http\Controllers;

use App\LoanApplicationStatus;
use App\LoanStatus;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanGuarantor;
use App\Models\LoanInstallment;
use App\Models\LoanProduct;
use App\Models\Repayment;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:dashboard.view'];
    }

    public function __invoke(Request $request, DashboardMetricsService $metrics): View
    {
        $branchId = $request->user()->branch_id;
        $period = $request->string('period')->toString() ?: 'this_year';
        [$dateFrom, $dateTo] = match ($period) {
            'today' => [today(), today()->endOfDay()],
            'last_7_days' => [today()->subDays(6), now()],
            'this_month' => [now()->startOfMonth(), now()],
            'last_3_months' => [today()->subMonths(3)->startOfDay(), now()],
            'last_6_months' => [today()->subMonths(6)->startOfDay(), now()],
            'all_time' => [null, null],
            'custom' => [
                $request->filled('date_from') ? Carbon::parse($request->input('date_from'))->startOfDay() : now()->startOfYear(),
                $request->filled('date_to') ? Carbon::parse($request->input('date_to'))->endOfDay() : now(),
            ],
            default => [now()->startOfYear(), now()],
        };
        $customerFilters = function ($query) use ($request): void {
            $query->when($request->filled('gender'), fn ($query) => $query->where('gender', $request->input('gender')))
                ->when($request->filled('customer_type'), fn ($query) => $query->where('type', $request->input('customer_type')))
                ->when($request->filled('officer_id'), fn ($query) => $query->where('assigned_officer_id', $request->integer('officer_id')))
                ->when($request->input('age') === 'under_25', fn ($query) => $query->where('date_of_birth', '>', today()->subYears(25)))
                ->when($request->input('age') === '25_34', fn ($query) => $query->whereBetween('date_of_birth', [today()->subYears(35)->addDay(), today()->subYears(25)]))
                ->when($request->input('age') === '35_44', fn ($query) => $query->whereBetween('date_of_birth', [today()->subYears(45)->addDay(), today()->subYears(35)]))
                ->when($request->input('age') === '45_54', fn ($query) => $query->whereBetween('date_of_birth', [today()->subYears(55)->addDay(), today()->subYears(45)]))
                ->when($request->input('age') === '55_plus', fn ($query) => $query->where('date_of_birth', '<=', today()->subYears(55)));
        };
        $customerScope = Customer::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo));
        $customerFilters($customerScope);
        $loanScope = Loan::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereHas('customer', $customerFilters)
            ->when($request->filled('loan_product_id'), fn ($query) => $query->whereHas('application.productVersion', fn ($query) => $query->where('loan_product_id', $request->integer('loan_product_id'))))
            ->when($request->filled('officer_id'), fn ($query) => $query->whereHas('application', fn ($query) => $query->where('created_by', $request->integer('officer_id'))))
            ->when($dateFrom, fn ($query) => $query->whereDate('disbursed_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('disbursed_at', '<=', $dateTo));
        $loans = (clone $loanScope)->with(['customer', 'application.creator', 'application.productVersion.product'])->get();
        $openLoans = $loans->whereIn('status', [LoanStatus::Active, LoanStatus::InArrears, LoanStatus::Restructured]);
        $yearLoans = $loans;
        $yearRepayments = Repayment::query()->with(['loan.customer', 'receiver'])
            ->where('status', 'Posted')->whereNull('reversed_repayment_id')
            ->whereIn('loan_id', $loans->pluck('id'))
            ->when($dateFrom, fn ($query) => $query->whereDate('paid_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('paid_at', '<=', $dateTo))->get();
        $outstandingInstallments = LoanInstallment::query()
            ->where('status', '!=', 'Paid')
            ->whereIn('loan_id', $loans->pluck('id'))
            ->get();
        $installmentBalance = fn (LoanInstallment $installment): float => collect(['principal', 'interest', 'fees', 'penalties'])
            ->sum(fn (string $component): float => max(0, (float) $installment->getAttribute("{$component}_due") - (float) $installment->getAttribute("{$component}_paid")));
        $monthlyTrend = collect(range(1, now()->month))->map(fn (int $month): array => [
            'label' => now()->month($month)->format('M'),
            'disbursed' => (float) $yearLoans->filter(fn (Loan $loan): bool => $loan->disbursed_at?->month === $month)->sum('principal_amount'),
            'collected' => (float) $yearRepayments->filter(fn (Repayment $repayment): bool => $repayment->paid_at?->month === $month)->sum('amount'),
        ]);
        $riskBuckets = collect([
            ['label' => 'Current', 'minimum' => 0, 'maximum' => 0, 'color' => '#0f766e'],
            ['label' => '1–30 days', 'minimum' => 1, 'maximum' => 30, 'color' => '#65a30d'],
            ['label' => '31–60 days', 'minimum' => 31, 'maximum' => 60, 'color' => '#d97706'],
            ['label' => '61–90 days', 'minimum' => 61, 'maximum' => 90, 'color' => '#ea580c'],
            ['label' => '91–180 days', 'minimum' => 91, 'maximum' => 180, 'color' => '#e11d48'],
            ['label' => 'Over 180 days', 'minimum' => 181, 'maximum' => PHP_INT_MAX, 'color' => '#881337'],
        ])->map(function (array $bucket) use ($openLoans): array {
            $matchingLoans = $openLoans->filter(fn (Loan $loan): bool => $loan->days_past_due >= $bucket['minimum'] && $loan->days_past_due <= $bucket['maximum']);

            return $bucket + ['amount' => (float) $matchingLoans->sum('outstanding_principal'), 'count' => $matchingLoans->count()];
        });
        $applicationScope = LoanApplication::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereHas('customer', $customerFilters)
            ->when($request->filled('loan_product_id'), fn ($query) => $query->whereHas('productVersion', fn ($query) => $query->where('loan_product_id', $request->integer('loan_product_id'))))
            ->when($request->filled('officer_id'), fn ($query) => $query->where('created_by', $request->integer('officer_id')))
            ->when($dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dateTo));
        $pendingReviews = (clone $applicationScope)->with('customer')->where('status', LoanApplicationStatus::PendingApproval)->latest()->limit(5)->get();
        $recentCollections = Repayment::query()->with('loan.customer')->where('status', 'Posted')->whereNull('reversed_repayment_id')
            ->whereIn('loan_id', $loans->pluck('id'))
            ->latest('paid_at')->limit(5)->get();
        $customers = (clone $customerScope)->with('loans')->get();
        $memberTotal = max(1, $customers->count());
        $memberMonthlyTrend = collect(range(1, now()->month))->map(function (int $month) use ($customers): array {
            $monthEnd = now()->copy()->month($month)->endOfMonth();

            return [
                'label' => $monthEnd->format('M'),
                'total' => $customers->filter(fn (Customer $customer): bool => $customer->created_at->lte($monthEnd))->count(),
                'joined' => $customers->filter(fn (Customer $customer): bool => $customer->created_at->year === now()->year && $customer->created_at->month === $month)->count(),
            ];
        });
        $newReturningTrend = collect(range(1, now()->month))->map(function (int $month) use ($loans, $yearLoans): array {
            $monthLoans = $yearLoans->filter(fn (Loan $loan): bool => $loan->disbursed_at?->month === $month);
            $first = $monthLoans->filter(function (Loan $loan) use ($loans): bool {
                $firstLoan = $loans->where('customer_id', $loan->customer_id)->sortBy('disbursed_at')->first();

                return $firstLoan?->id === $loan->id;
            })->count();

            return ['label' => now()->month($month)->format('M'), 'first' => $first, 'returning' => $monthLoans->count() - $first];
        });
        $memberGender = $customers->groupBy(fn (Customer $customer): string => $customer->gender ?: 'Not recorded')->map->count();
        $memberAge = collect(['Under 25' => 0, '25–34' => 0, '35–44' => 0, '45–54' => 0, '55 and over' => 0, 'Not recorded' => 0]);
        $customers->each(function (Customer $customer) use ($memberAge): void {
            $age = $customer->date_of_birth?->age;
            $bucket = $age === null ? 'Not recorded' : ($age < 25 ? 'Under 25' : ($age < 35 ? '25–34' : ($age < 45 ? '35–44' : ($age < 55 ? '45–54' : '55 and over'))));
            $memberAge->put($bucket, $memberAge->get($bucket) + 1);
        });
        $memberTypes = $customers->groupBy(fn (Customer $customer): string => $customer->type ?: 'Not recorded')->map->count();
        $borrowingCustomerIds = $openLoans->pluck('customer_id')->unique();
        $joinedThisYear = $customers->filter(fn (Customer $customer): bool => $customer->created_at->isCurrentYear())->count();
        $membersAtYearStart = max(0, $customers->count() - $joinedThisYear);
        $inactiveMembers = $customers->filter(function (Customer $customer): bool {
            $latestLoan = $customer->loans->sortByDesc('disbursed_at')->first();

            return $latestLoan !== null && $latestLoan->disbursed_at?->lt(now()->subDays(90));
        })->count();

        $productInsights = $yearLoans->groupBy(fn (Loan $loan): string => $loan->application?->productVersion?->product?->name ?? 'Unclassified')->map(function ($productLoans, string $name): array {
            return [
                'name' => $name,
                'loans' => $productLoans->count(),
                'defaults' => $productLoans->filter(fn (Loan $loan): bool => $loan->status === LoanStatus::WrittenOff || ($loan->maturity_date?->isPast() && (float) $loan->outstanding_principal > 0))->count(),
                'amount' => (float) $productLoans->sum('principal_amount'),
                'outstanding' => (float) $productLoans->sum('outstanding_principal'),
            ];
        })->sortByDesc('amount')->values();
        $productNames = $productInsights->pluck('name')->values();
        $productDisbursementTrend = collect(range(1, now()->month))->map(function (int $month) use ($yearLoans, $productNames): array {
            $monthLoans = $yearLoans->filter(fn (Loan $loan): bool => $loan->disbursed_at?->month === $month);

            return [
                'label' => now()->month($month)->format('M'),
                'products' => $productNames->mapWithKeys(fn (string $product): array => [$product => (float) $monthLoans->filter(fn (Loan $loan): bool => ($loan->application?->productVersion?->product?->name ?? 'Unclassified') === $product)->sum('principal_amount')]),
            ];
        });
        $guarantorExposure = LoanGuarantor::query()->with(['guarantor', 'application.loan'])
            ->whereHas('application', fn ($query) => $query->when($branchId, fn ($query) => $query->where('branch_id', $branchId)))
            ->get()->groupBy('guarantor_id')->map(function ($guarantees): array {
                $guarantor = $guarantees->first()->guarantor;

                return [
                    'name' => trim($guarantor->first_name.' '.$guarantor->last_name),
                    'phone' => $guarantor->phone,
                    'loans' => $guarantees->count(),
                    'exposure' => (float) $guarantees->sum('guaranteed_amount'),
                    'arrears' => $guarantees->filter(fn ($guarantee): bool => ($guarantee->application?->loan?->days_past_due ?? 0) > 0)->count(),
                ];
            })->sortByDesc('exposure')->take(10)->values();

        return view('dashboard', [
            'metrics' => $metrics->summary($branchId),
            'watchlist' => (clone $loanScope)->with('customer')->where('days_past_due', '>', 0)->orderByDesc('days_past_due')->limit(6)->get(),
            'openLoans' => $openLoans,
            'yearLoans' => $yearLoans,
            'yearRepayments' => $yearRepayments,
            'monthlyTrend' => $monthlyTrend,
            'riskBuckets' => $riskBuckets,
            'pendingReviews' => $pendingReviews,
            'recentCollections' => $recentCollections,
            'applicationStatuses' => (clone $applicationScope)->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status'),
            'genderDistribution' => $yearLoans->groupBy(fn (Loan $loan): string => $loan->customer?->gender ?: 'Unknown')->map->count(),
            'memberAnalytics' => [
                'total' => $customers->count(),
                'borrowing' => $borrowingCustomerIds->count(),
                'youth' => $customers->filter(fn (Customer $customer): bool => $customer->date_of_birth !== null && $customer->date_of_birth->age <= 35)->count(),
                'neverBorrowed' => $customers->filter(fn (Customer $customer): bool => $customer->loans->isEmpty())->count(),
                'joined' => $joinedThisYear,
                'growthRate' => $membersAtYearStart > 0 ? $joinedThisYear / $membersAtYearStart * 100 : ($joinedThisYear > 0 ? 100 : 0),
                'inactive' => $inactiveMembers,
                'activeRate' => $memberTotal > 0 ? $borrowingCustomerIds->count() / $memberTotal * 100 : 0,
            ],
            'memberMonthlyTrend' => $memberMonthlyTrend,
            'newReturningTrend' => $newReturningTrend,
            'memberGender' => $memberGender,
            'memberAge' => $memberAge,
            'memberTypes' => $memberTypes,
            'productInsights' => $productInsights,
            'productNames' => $productNames,
            'productDisbursementTrend' => $productDisbursementTrend,
            'guarantorExposure' => $guarantorExposure,
            'dashboardFilters' => [
                'period' => $period,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'loanProducts' => LoanProduct::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'loanOfficers' => User::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->where('is_active', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
                'customerTypes' => Customer::query()->when($branchId, fn ($query) => $query->where('branch_id', $branchId))->whereNotNull('type')->distinct()->orderBy('type')->pluck('type'),
            ],
            'dashboardStats' => [
                'customers' => (clone $customerScope)->count(),
                'expectedToday' => $outstandingInstallments->filter(fn (LoanInstallment $installment): bool => $installment->due_date->isToday())->sum($installmentBalance),
                'missedPayments' => $outstandingInstallments->filter(fn (LoanInstallment $installment): bool => $installment->due_date->isBefore(today()))->sum($installmentBalance),
                'pendingReview' => $pendingReviews->count(),
                'newBorrowersYear' => (clone $customerScope)->whereYear('created_at', now()->year)->count(),
                'newBorrowersMonth' => (clone $customerScope)->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
                'newBorrowersWeek' => (clone $customerScope)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'settledLoansYear' => $yearLoans->where('status', LoanStatus::Settled)->count(),
                'onSchedule' => $openLoans->where('days_past_due', 0)->count(),
                'overdueAccounts' => $openLoans->where('days_past_due', '>', 0)->count(),
            ],
        ]);
    }
}
