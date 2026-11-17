@extends('layouts.app')

@section('title', 'Edit approval rule')

@section('content')
    @include('partials.alerts')
    <div class="mb-4"><h1 class="h3 mb-1">Edit approval rule</h1><p class="text-muted mb-0">Changes apply only when future approval routes are initialized.</p></div>
    <form class="card border-0 shadow-sm" method="POST" action="{{ route('approval-rules.update', $approvalRule) }}">
        @csrf
        @method('PUT')
        <div class="card-body">@include('approval-rules._form')</div>
    </form>
@endsection
