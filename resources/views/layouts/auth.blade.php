<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ config('microfinance.institution_name') }}</title>
    <link rel="stylesheet" href="{{ asset('app-assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app-assets/css/icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app-assets/css/microfinance.css') }}">
    <link rel="stylesheet" href="{{ asset('app-assets/css/staff-design-system.css') }}">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-brand-panel">
            <div class="auth-brand-content">
                <span class="brand-mark">CM</span>
                <span class="eyebrow text-white-50">Core microfinance management</span>
                <h1>{{ config('microfinance.institution_name') }}</h1>
                <p>Secure employee access to customers, credit, collections, cashier operations, accounting and audit.</p>
            </div>
        </section>
        <section class="auth-form-panel">
            <div class="auth-card">
                @yield('content')
            </div>
        </section>
    </main>
</body>
</html>
