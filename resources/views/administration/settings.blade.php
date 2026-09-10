@extends('layouts.app')

@section('title', 'System settings')

@section('content')
    @include('partials.alerts')
    <div class="mb-4"><h1 class="h3 mb-1">System settings</h1><p class="text-muted mb-0">Configuration used by lending, finance, security and notifications.</p></div>
    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')
        <div class="row g-4">
            @foreach ($settings as $group => $groupSettings)
                <div class="col-lg-6"><div class="card card-body border-0 shadow-sm h-100"><h2 class="h5 text-capitalize mb-3">{{ str_replace('_', ' ', $group) }}</h2>@foreach ($groupSettings as $setting)<div class="mb-3"><label class="form-label" for="setting_{{ $setting->id }}">{{ str($setting->key)->replace('_', ' ')->title() }}</label><input class="form-control" id="setting_{{ $setting->id }}" name="settings[{{ $setting->id }}]" value="{{ old('settings.'.$setting->id, $setting->value) }}"></div>@endforeach</div></div>
            @endforeach
        </div>
        <div class="mt-4"><button class="btn btn-primary" type="submit">Save settings</button></div>
    </form>
@endsection
