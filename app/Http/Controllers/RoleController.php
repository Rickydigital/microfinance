<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:admin.role.view', only: ['index']),
            new Middleware('can:admin.role.create', only: ['create', 'store']),
            new Middleware('can:admin.role.update', only: ['edit', 'update']),
            new Middleware('can:admin.role.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $roles = Role::query()->withCount(['users', 'permissions'])->orderBy('name')->get();

        return view('roles.index', ['roles' => $roles, 'permissionGroups' => $this->permissionGroups()]);
    }

    public function create(): View
    {
        return view('roles.create', ['role' => null, 'permissionGroups' => $this->permissionGroups()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRole($request);
        $role = Role::query()->create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions']);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', ['role' => $role, 'permissionGroups' => $this->permissionGroups()]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'System Administrator', 422, 'The system administrator role is managed by the access-control seeder.');
        $validated = $this->validateRole($request, $role);
        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions']);

        return redirect()->route('roles.index')->with('success', 'Role permissions updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->name === 'System Administrator' || $role->users()->exists(), 422, 'System or assigned roles cannot be deleted.');
        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }

    /** @return array{name: string, permissions: array<int, string>} */
    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role)],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);
    }

    /** @return Collection<string, EloquentCollection<int, Permission>> */
    private function permissionGroups(): Collection
    {
        return Permission::query()->orderBy('name')->get()->groupBy(
            fn (Permission $permission): string => str($permission->name)->before('.')->toString(),
        );
    }
}
