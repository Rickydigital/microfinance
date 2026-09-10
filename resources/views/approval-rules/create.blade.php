@extends('layouts.app')

@section('title', 'Create approval rule')

@section('content')
    @include('partials.alerts')
    <div class="mb-4"><h1 class="h3 mb-1">Create approval rule</h1><p class="text-muted mb-0">Add one authority level for an amount and risk band.</p></div>
    <form class="card border-0 shadow-sm" method="POST" action="{{ route('approval-rules.store') }}">
        @csrf
        <div class="card-body">@include('approval-rules._form')</div>
    </form>
@endsection
