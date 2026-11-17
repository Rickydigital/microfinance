<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Services\DashboardMetricsService;
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
        return view('dashboard', [
            'metrics' => $metrics->summary($request->user()->branch_id),
            'watchlist' => Loan::query()->with('customer')->when($request->user()->branch_id, fn ($query, $branch) => $query->where('branch_id', $branch))->where('days_past_due', '>', 0)->orderByDesc('days_past_due')->limit(6)->get(),
        ]);
    }
}
