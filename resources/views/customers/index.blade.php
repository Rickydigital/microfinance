@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    @include('partials.alerts')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="h3 mb-1">Customers</h1><p class="text-muted mb-0">Customer registration and KYC records</p></div>
        <a class="btn btn-primary" href="{{ route('customers.create') }}">Add customer</a>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form class="row g-2 mb-3" method="GET">
                <div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search number, name or phone"></div>
                <div class="col-auto"><button class="btn btn-outline-primary">Search</button></div>
            </form>
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead><tr><th>Customer</th><th>Phone</th><th>Officer</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($customers as $customer)
                    <tr><td><strong>{{ $customer->first_name }} {{ $customer->last_name }}</strong><br><small>{{ $customer->customer_number }}</small></td><td>{{ $customer->phone }}</td><td>{{ $customer->assignedOfficer?->full_name ?? 'Unassigned' }}</td><td><span class="badge bg-secondary">{{ $customer->status->value }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('customers.show', $customer) }}">View</a></td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No customers found.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            {{ $customers->links() }}
        </div>
    </div>
@endsection
