@include('partials.alerts')

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="staff_number">Staff number</label>
        <input class="form-control" id="staff_number" name="staff_number" value="{{ old('staff_number', $user?->staff_number) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="branch_id">Branch</label>
        <select class="form-select" id="branch_id" name="branch_id" required>
            <option value="">Select branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $user?->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="department_id">Department</label>
        <select class="form-select" id="department_id" name="department_id">
            <option value="">Select department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $user?->department_id) === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="first_name">First name</label>
        <input class="form-control" id="first_name" name="first_name" value="{{ old('first_name', $user?->first_name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="middle_name">Middle name</label>
        <input class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name', $user?->middle_name) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="last_name">Last name</label>
        <input class="form-control" id="last_name" name="last_name" value="{{ old('last_name', $user?->last_name) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="gender">Gender</label>
        <select class="form-select" id="gender" name="gender">
            <option value="">Select gender</option>
            @foreach (['Male', 'Female', 'Other'] as $gender)
                <option value="{{ $gender }}" @selected(old('gender', $user?->gender) === $gender)>{{ $gender }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="date_of_birth">Date of birth</label>
        <input class="form-control" id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $user?->date_of_birth?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="phone">Phone</label>
        <input class="form-control" id="phone" name="phone" value="{{ old('phone', $user?->phone) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="role">Role</label>
        <select class="form-select" id="role" name="role" required>
            <option value="">Select role</option>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}" @selected(old('role', $user?->getRoleNames()->first()) === $role->name)>{{ $role->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="password">Password {{ $user ? '(leave blank to retain)' : '' }}</label>
        <input class="form-control" id="password" type="password" name="password" {{ $user ? '' : 'required' }}>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="password_confirmation">Confirm password</label>
        <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" {{ $user ? '' : 'required' }}>
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="is_active" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $user?->is_active ?? true))>
            <label class="form-check-label" for="is_active">Employee can sign in</label>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Save employee</button>
    <a class="btn btn-light" href="{{ route('users.index') }}">Cancel</a>
</div>
