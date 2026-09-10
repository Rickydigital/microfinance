@extends('layouts.app')
@section('title', 'Edit ledger account')
@section('content')
    @include('partials.alerts')
    <form class="card card-body border-0 shadow-sm" method="POST" action="{{ route('finance.accounts.update', $account) }}">
        @csrf @method('PUT')
        <h1 class="h3">Edit ledger account</h1>
        <p class="text-muted">Journal history remains unchanged. Normal balance controls how its balance is displayed.</p>
        <div class="row g-3">
            <div class="col-md-3"><label class="form-label">Code</label><input class="form-control" name="code" value="{{ old('code', $account->code) }}" required></div>
            <div class="col-md-5"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ old('name', $account->name) }}" required></div>
            <div class="col-md-4"><label class="form-label">Parent</label><select class="form-select" name="parent_id"><option value="">No parent</option>@foreach ($parents as $parent)<option value="{{ $parent->id }}" @selected((string) old('parent_id', $account->parent_id) === (string) $parent->id)>{{ $parent->code }} — {{ $parent->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Type</label><select class="form-select" name="type">@foreach (['Asset','Liability','Equity','Income','Expense'] as $type)<option @selected(old('type', $account->type) === $type)>{{ $type }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Normal balance</label><select class="form-select" name="normal_balance">@foreach (['Debit','Credit'] as $balance)<option @selected(old('normal_balance', $account->normal_balance) === $balance)>{{ $balance }}</option>@endforeach</select></div>
            <div class="col-md-2 form-check ms-2"><input class="form-check-input" type="checkbox" id="edit_manual" name="allows_manual_posting" value="1" @checked(old('allows_manual_posting', $account->allows_manual_posting))><label class="form-check-label" for="edit_manual">Manual posting</label></div>
            <div class="col-md-2 form-check"><input class="form-check-input" type="checkbox" id="edit_control" name="is_control_account" value="1" @checked(old('is_control_account', $account->is_control_account))><label class="form-check-label" for="edit_control">Control account</label></div>
        </div>
        <div class="mt-4"><button class="btn btn-primary">Save changes</button> <a class="btn btn-outline-secondary" href="{{ route('finance.accounts.index') }}">Cancel</a></div>
    </form>
@endsection
