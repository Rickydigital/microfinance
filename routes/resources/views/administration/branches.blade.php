@extends('layouts.app')

@section('title', 'Branches')

@section('content')
    @include('partials.alerts')
    <div class="row g-4">
        <div class="col-lg-4">
            <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('branches.store') }}">
                @csrf
                <h1 class="h5 mb-3">Create branch</h1>
                <div class="mb-3"><label class="form-label" for="code">Branch code</label><input class="form-control" id="code" name="code" value="{{ old('code') }}" required></div>
                <div class="mb-3"><label class="form-label" for="name">Name</label><input class="form-control" id="name" name="name" value="{{ old('name') }}" required></div>
                <div class="mb-3"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" value="{{ old('phone') }}"></div>
                <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}"></div>
                <div class="mb-3"><label class="form-label" for="address">Address</label><textarea class="form-control" id="address" name="address" rows="3">{{ old('address') }}</textarea></div>
                <button class="btn btn-primary" type="submit">Save branch</button>
            </form>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h2 class="h5 mb-0">Institution branches</h2></div>
                <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Branch</th><th>Employees</th><th>Customers</th><th>Loans</th><th>Status</th></tr></thead><tbody>@foreach ($branches as $branch)<tr><td><strong>{{ $branch->name }}</strong><div class="small text-muted">{{ $branch->code }}</div></td><td>{{ $branch->users_count }}</td><td>{{ $branch->customers_count }}</td><td>{{ $branch->loans_count }}</td><td>{{ $branch->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach</tbody></table></div>
                <div class="card-body">{{ $branches->links() }}</div>
            </div>
        </div>
    </div>
@endsection
