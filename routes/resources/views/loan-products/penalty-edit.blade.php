@extends('layouts.app')

@section('title', 'Edit penalty rule')

@section('content')
    @include('partials.alerts')

    <div class="mb-4">
        <h1 class="h3 mb-1">Edit penalty rule</h1>
        <p class="text-muted mb-0">
            {{ $productPenaltyRule->productVersion->product->name }} · Version {{ $productPenaltyRule->productVersion->version }}
        </p>
    </div>

    @if ($productPenaltyRule->charges()->exists())
        <div class="alert alert-warning">
            This rule has already been applied to a loan. Its calculation fields are locked to preserve the audit history. You may deactivate it from the product configuration and create a replacement rule.
        </div>
    @else
        <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('product-penalty-rules.update', $productPenaltyRule) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Rule name</label>
                    <input class="form-control" name="name" value="{{ old('name', $productPenaltyRule->name) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Calculation method</label>
                    <select class="form-select" name="calculation_method" required>
                        @foreach (['Fixed', 'Percentage'] as $method)
                            <option @selected(old('calculation_method', $productPenaltyRule->calculation_method) === $method)>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Value</label>
                    <input class="form-control" type="number" min="0.000001" step="0.000001" name="value" value="{{ old('value', $productPenaltyRule->value) }}" required>
                    <small class="text-muted">For percentage, use 0.01 for 1%.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Calculation basis</label>
                    <select class="form-select" name="calculation_basis" required>
                        @foreach (['Overdue Principal', 'Overdue Installment', 'Total Arrears'] as $basis)
                            <option @selected(old('calculation_basis', $productPenaltyRule->calculation_basis) === $basis)>{{ $basis }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Frequency</label>
                    <select class="form-select" name="frequency" required>
                        @foreach (['One-time', 'Daily'] as $frequency)
                            <option @selected(old('frequency', $productPenaltyRule->frequency) === $frequency)>{{ $frequency }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Grace days</label>
                    <input class="form-control" type="number" min="0" name="grace_days" value="{{ old('grace_days', $productPenaltyRule->grace_days) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Maximum charge</label>
                    <input class="form-control" type="number" min="0.01" step="0.01" name="maximum_amount" value="{{ old('maximum_amount', $productPenaltyRule->maximum_amount) }}">
                </div>
                <div class="col-12 form-check ms-2">
                    <input class="form-check-input" id="penalty_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $productPenaltyRule->is_active))>
                    <label class="form-check-label" for="penalty_active">Rule active</label>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary">Save penalty rule</button>
                <a class="btn btn-outline-secondary" href="{{ route('loan-products.show', $productPenaltyRule->productVersion->product) }}">Cancel</a>
            </div>
        </form>
    @endif
@endsection
