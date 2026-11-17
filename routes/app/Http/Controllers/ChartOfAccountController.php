<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ChartOfAccountController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:accounting.account.view', only: ['index']), new Middleware('can:accounting.account.manage', only: ['store', 'edit', 'update', 'destroy'])];
    }

    public function index(): View
    {
        $accounts = ChartOfAccount::query()->with('parent')->withSum('journalLines', 'debit')->withSum('journalLines', 'credit')->orderBy('code')->get();

        return view('finance.accounts', compact('accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['parent_id' => ['nullable', 'exists:chart_of_accounts,id'], 'code' => ['required', 'string', 'max:30', 'unique:chart_of_accounts,code'], 'name' => ['required', 'string', 'max:150'], 'type' => ['required', 'in:Asset,Liability,Equity,Income,Expense'], 'normal_balance' => ['required', 'in:Debit,Credit'], 'is_control_account' => ['nullable', 'boolean'], 'allows_manual_posting' => ['nullable', 'boolean']]);
        ChartOfAccount::query()->create($validated + ['is_control_account' => $request->boolean('is_control_account'), 'allows_manual_posting' => $request->boolean('allows_manual_posting'), 'is_active' => true]);

        return back()->with('success', 'Ledger account created.');
    }

    public function edit(ChartOfAccount $account): View
    {
        $parents = ChartOfAccount::query()->whereKeyNot($account->id)->where('is_active', true)->orderBy('code')->get();

        return view('finance.account-edit', compact('account', 'parents'));
    }

    public function update(Request $request, ChartOfAccount $account): RedirectResponse
    {
        $validated = $request->validate(['parent_id' => ['nullable', 'exists:chart_of_accounts,id', 'not_in:'.$account->id], 'code' => ['required', 'string', 'max:30', 'unique:chart_of_accounts,code,'.$account->id], 'name' => ['required', 'string', 'max:150'], 'type' => ['required', 'in:Asset,Liability,Equity,Income,Expense'], 'normal_balance' => ['required', 'in:Debit,Credit'], 'is_control_account' => ['nullable', 'boolean'], 'allows_manual_posting' => ['nullable', 'boolean']]);
        abort_if($validated['parent_id'] && ChartOfAccount::query()->where('parent_id', $account->id)->whereKey($validated['parent_id'])->exists(), 422, 'An account cannot be parented by its own child.');
        $account->update($validated + ['is_control_account' => $request->boolean('is_control_account'), 'allows_manual_posting' => $request->boolean('allows_manual_posting')]);

        return redirect()->route('finance.accounts.index')->with('success', 'Ledger account updated.');
    }

    public function destroy(ChartOfAccount $account): RedirectResponse
    {
        $account->update(['is_active' => ! $account->is_active]);

        return back()->with('success', $account->is_active ? 'Ledger account activated.' : 'Ledger account deactivated.');
    }
}
