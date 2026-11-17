<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('microfinance.institution_name') }}</title>
    @include('layouts.partials.design-system')
</head>
<body>
<div class="shell">

    <aside class="sidebar">
        <div class="brand">
            @php($logoPath = config('microfinance.logo_path'))
            @if($logoPath && file_exists($logoPath))
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('microfinance.institution_name') }} logo">
            @endif
            <div>
                <div class="brand-name">{{ config('microfinance.institution_name') }}</div>
                <div class="brand-tag">Client portal</div>
            </div>
        </div>

        <nav class="nav-group">
            <a href="{{ route('portal.dashboard') }}" class="nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 11.5 12 4l8 7.5"/><path d="M6 10v9h12v-9"/></svg>
                Dashboard
            </a>
            <a href="{{ route('portal.applications.index') }}" class="nav-link {{ request()->routeIs('portal.applications.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4"/><path d="M9.5 13h5M9.5 16.5h5"/></svg>
                Applications
            </a>
            <a href="{{ route('portal.loans.index') }}" class="nav-link {{ request()->routeIs('portal.loans.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/></svg>
                My loans
            </a>
            <a href="{{ route('portal.payments.index') }}" class="nav-link {{ request()->routeIs('portal.payments.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M12 13v4M10 15h4"/></svg>
                Payments
            </a>
            <a href="{{ route('portal.products') }}" class="nav-link {{ request()->routeIs('portal.products.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 3 8l9 5 9-5-9-5Z"/><path d="M3 13l9 5 9-5"/></svg>
                Loan products
            </a>
        </nav>

        <div class="sidebar-foot">
            <div class="user-chip">
                <div class="avatar">{{ strtoupper(substr($customer->first_name ?? auth('customer')->user()->first_name ?? 'C',0,1)) }}</div>
                <div>
                    <div class="user-chip-name">{{ ($customer->first_name ?? auth('customer')->user()->first_name ?? '') }} {{ ($customer->last_name ?? auth('customer')->user()->last_name ?? '') }}</div>
                    <div class="user-chip-sub">{{ $customer->customer_number ?? auth('customer')->user()->customer_number ?? '' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('portal.logout') }}">
                @csrf
                <button type="submit" class="logout-link" style="background:none;border:none;width:100%;text-align:left;cursor:pointer;">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div class="topbar-greeting">{{ now()->format('l, d F Y') }}</div>
            <a href="{{ route('portal.applications.create') }}" class="btn btn-accent">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Apply for a loan
            </a>
        </div>
        <div class="content">
            @if(session('status'))
                <div class="alert alert-info">{{ session('status') }}</div>
            @endif
            @if(session('success'))<div class="alert alert-info">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </div>
    </div>

</div>
</body>
</html>
