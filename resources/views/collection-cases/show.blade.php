@extends('layouts.app')

@section('title', $collectionCase->case_number)

@section('content')
    @include('partials.alerts')

    <div class="mb-4">
        <h1 class="h3 mb-1">{{ $collectionCase->case_number }}</h1>
        <p class="text-muted mb-0">
            Loan {{ $collectionCase->loan->loan_number }} ·
            {{ $collectionCase->loan->customer->first_name }}
            {{ $collectionCase->loan->customer->last_name }}
        </p>
    </div>

    <div class="row g-4">
        @can('collection.case.assign')
            <div class="col-12"><form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('collection-cases.assign', $collectionCase) }}">@csrf<div class="row align-items-end g-3"><div class="col-md-5"><label class="form-label">Recovery officer</label><select class="form-select" name="assigned_to" required><option value="">Select officer</option>@foreach ($recoveryOfficers as $officer)<option value="{{ $officer->id }}" @selected($collectionCase->assigned_to === $officer->id)>{{ $officer->full_name }}</option>@endforeach</select></div><div class="col-md-3"><button class="btn btn-primary">Assign case</button></div></div></form></div>
        @endcan
        <div class="col-lg-5">
            <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('collection-cases.actions.store', $collectionCase) }}">
                @csrf
                <h2 class="h5 mb-3">Record follow-up</h2>
                <div class="mb-3">
                    <label class="form-label" for="type">Action type</label>
                    <select class="form-select" id="type" name="type" required>
                        @foreach (['Phone Call', 'SMS', 'Field Visit', 'Guarantor Contact', 'Demand Letter', 'Promise to Pay', 'Legal Action'] as $type)
                            <option>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3"><label class="form-label" for="result">Result</label><input class="form-control" id="result" name="result"></div>
                <div class="row g-3">
                    <div class="col-6"><label class="form-label" for="promise_to_pay_date">Promise date</label><input class="form-control" id="promise_to_pay_date" type="date" name="promise_to_pay_date"></div>
                    <div class="col-6"><label class="form-label" for="promise_to_pay_amount">Promise amount</label><input class="form-control" id="promise_to_pay_amount" type="number" step="0.01" name="promise_to_pay_amount"></div>
                </div>
                <div class="my-3"><label class="form-label" for="notes">Notes</label><textarea class="form-control" id="notes" name="notes" rows="4"></textarea></div>
                <div class="form-check mb-3"><input class="form-check-input" id="notify_customer" type="checkbox" name="notify_customer" value="1"><label class="form-check-label" for="notify_customer">Send notes to customer by SMS/email</label></div>
                <button class="btn btn-primary" type="submit">Save action</button>
            </form>
        </div>
        <div class="col-lg-7">
            <div class="card card-body border-0 shadow-sm">
                <h2 class="h5">Recovery history</h2>
                @forelse ($collectionCase->actions as $action)
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between"><strong>{{ $action->type }}</strong><small>{{ $action->performed_at->format('d M Y H:i') }}</small></div>
                        <p class="mb-0">{{ $action->notes ?: $action->result }}</p>
                    </div>
                @empty
                    <p class="text-muted mb-0">No recovery actions recorded.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
