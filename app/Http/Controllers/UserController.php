<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:admin.user.view', only: ['index', 'show']),
            new Middleware('can:admin.user.create', only: ['create', 'store']),
            new Middleware('can:admin.user.update', only: ['edit', 'update']),
            new Middleware('can:admin.user.deactivate', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $users = User::query()->with(['branch', 'department', 'roles'])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('staff_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })->orderBy('first_name')->paginate(20)->withQueryString();

        return view('users.index', $this->formOptions() + compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('users.create', $this->formOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = User::create(Arr::except($data, ['role']));
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.show', $user)->with('success', 'Employee account created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        $user->load(['branch', 'department', 'roles', 'tillSessions']);

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        return view('users.edit', $this->formOptions() + compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update(Arr::except($data, ['role']));
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.show', $user)->with('success', 'Employee account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot deactivate your own account.');
        $user->update(['is_active' => false]);

        return redirect()->route('users.index')->with('success', 'Employee account deactivated.');
    }

    /** @return array{branches: mixed, departments: mixed, roles: mixed} */
    private function formOptions(): array
    {
        return [
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'roles' => Role::query()->orderBy('name')->get(),
        ];
    }
}
