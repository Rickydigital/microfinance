@include('partials.alerts')

<div class="mb-4">
    <label class="form-label" for="name">Role name</label>
    <input class="form-control" id="name" name="name" value="{{ old('name', $role?->name) }}" required {{ $role?->name === 'System Administrator' ? 'disabled' : '' }}>
</div>

@foreach ($permissionGroups as $group => $permissions)
    <section class="border rounded p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h6 text-capitalize mb-0">{{ str_replace('-', ' ', $group) }}</h2><span class="badge bg-light text-dark">{{ $permissions->count() }} permissions</span></div>
        <div class="row g-2">
            @foreach ($permissions as $permission)
                <div class="col-md-6 col-xl-4"><div class="form-check"><input class="form-check-input" id="permission_{{ $permission->id }}" type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, old('permissions', $role?->permissions->pluck('name')->all() ?? []), true))><label class="form-check-label" for="permission_{{ $permission->id }}">{{ str($permission->name)->after('.')->replace(['.', '-'], ' ')->title() }}</label></div></div>
            @endforeach
        </div>
    </section>
@endforeach

@if ($role?->name !== 'System Administrator')
    <div class="d-flex gap-2"><button class="btn btn-primary" type="submit">Save role</button><a class="btn btn-light" href="{{ route('roles.index') }}">Cancel</a></div>
@endif
