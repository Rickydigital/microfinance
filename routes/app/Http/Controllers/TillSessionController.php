<?php

namespace App\Http\Controllers;

use App\Models\TillSession;
use App\Services\TillSessionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class TillSessionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:till.view', only: ['index']),
            new Middleware('can:till.open', only: ['open']),
            new Middleware('can:till.close', only: ['close']),
            new Middleware('can:till.authorize-difference', only: ['authorizeDifference']),
        ];
    }

    public function index(Request $request): View
    {
        $sessions = TillSession::query()->where('user_id', $request->user()->id)->latest()->paginate(20);
        $openSession = TillSession::query()->with(['cashTransactions' => fn ($query) => $query->latest('posted_at')])->where('user_id', $request->user()->id)->where('status', 'Open')->first();
        $pendingDifferences = $request->user()->can('till.authorize-difference')
            ? TillSession::query()->with('user')->where('branch_id', $request->user()->branch_id)->where('status', 'Pending Difference Approval')->get()
            : collect();

        return view('till-sessions.index', compact('sessions', 'openSession', 'pendingDifferences'));
    }

    public function open(Request $request, TillSessionService $service): RedirectResponse
    {
        $validated = $request->validate(['opening_balance' => ['required', 'numeric', 'min:0']]);

        try {
            $service->open($request->user(), (string) $validated['opening_balance']);
        } catch (DomainException $exception) {
            return back()->withErrors(['opening_balance' => $exception->getMessage()]);
        }

        return back()->with('success', 'Till opened successfully.');
    }

    public function close(Request $request, TillSession $tillSession, TillSessionService $service): RedirectResponse
    {
        abort_unless($tillSession->user_id === $request->user()->id, 403);
        $validated = $request->validate([
            'actual_balance' => ['required', 'numeric', 'min:0'],
            'difference_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->close($tillSession, (string) $validated['actual_balance'], $validated['difference_reason'] ?? null);
        } catch (DomainException $exception) {
            return back()->withErrors(['actual_balance' => $exception->getMessage()]);
        }

        return back()->with('success', 'Till closed successfully.');
    }

    public function authorizeDifference(TillSession $tillSession, TillSessionService $service, Request $request): RedirectResponse
    {
        try {
            $service->authorizeDifference($tillSession, $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors(['difference' => $exception->getMessage()]);
        }

        return back()->with('success', 'Till difference authorized and session closed.');
    }
}
