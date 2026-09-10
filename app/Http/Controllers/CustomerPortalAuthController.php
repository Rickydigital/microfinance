<?php

namespace App\Http\Controllers;

use App\CustomerStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\NumberSequence;
use App\Services\CustomerCommunicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CustomerPortalAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'The provided customer credentials are incorrect.']);
        }

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        if ($customer->status !== CustomerStatus::Active || $customer->verified_at === null) {
            Auth::guard('customer')->logout();

            return back()->withInput($request->only('email'))->withErrors(['email' => 'Your profile is awaiting staff KYC verification. You can sign in after activation.']);
        }

        $request->session()->regenerate();
        $customer->update(['portal_last_login_at' => now()]);

        if ($customer->must_change_password) {
            return redirect()->route('portal.password.edit');
        }

        return redirect()->intended(route('portal.dashboard'));
    }

    public function showRegistration(): View
    {
        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get();
        $customerTypes = CustomerType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('portal.auth.register', compact('branches', 'customerTypes'));
    }

    public function register(Request $request, CustomerCommunicationService $communication): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'], 'type' => ['required', 'exists:customer_types,code'],
            'first_name' => ['required', 'string', 'max:100'], 'middle_name' => ['nullable', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:Male,Female,Other'], 'date_of_birth' => ['required', 'date', 'before:-18 years'],
            'phone' => ['required', 'string', 'max:30', 'unique:customers,phone'], 'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        $branch = Branch::query()->where('is_active', true)->findOrFail($validated['branch_id']);
        $customer = Customer::query()->create($validated + ['branch_id' => $branch->id, 'customer_number' => NumberSequence::issue('customer'), 'status' => CustomerStatus::PendingVerification, 'portal_registered_at' => now()]);
        $communication->customerRegistered($customer);

        return redirect()->route('portal.login')->with('success', 'Registration received. Staff must complete and verify your KYC before you can sign in.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function editPassword(): View
    {
        return view('portal.auth.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate(['password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()]]);
        $request->user('customer')->update(['password' => $validated['password'], 'must_change_password' => false]);

        return redirect()->route('portal.dashboard')->with('success', 'Your portal password has been changed.');
    }
}
