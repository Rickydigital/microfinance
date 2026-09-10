<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ config('microfinance.institution_name') }}</title>
    <link rel="stylesheet" href="{{ asset('app-assets/css/bootstrap.min.css') }}">
    {{-- Icon font served from CDN: the local icons.min.css asset was failing to load
         glyphs on some screens, which is why icons were invisible. This CDN copy of
         Material Design Icons keeps every existing "mdi mdi-*" class working as-is. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="{{ asset('app-assets/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app-assets/css/microfinance.css') }}">
    <link rel="stylesheet" href="{{ asset('app-assets/css/staff-design-system.css') }}">
</head>
<body>
    <div class="app-shell">
        <div class="sidebar-backdrop" data-sidebar-backdrop></div>

        <aside class="sidebar" id="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark">CM</span>
                <span class="brand-text">
                    <strong>{{ config('microfinance.institution_name') }}</strong>
                    <small>Core banking system</small>
                </span>
            </a>
            <nav class="sidebar-nav" aria-label="Primary navigation">
                <span class="nav-heading">Operations</span>
                @foreach (config('microfinance.navigation') as $item)
                    @continue(isset($item['permission']) && auth()->user()->cannot($item['permission']))
                    @if (isset($item['route']))
                        <a class="nav-item {{ request()->routeIs($item['route']) ? 'active' : '' }}"
                           href="{{ route($item['route']) }}"
                           data-tooltip="{{ $item['label'] }}">
                            <i class="mdi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
                        </a>
                    @else
                        <details class="nav-group">
                            <summary class="nav-item" data-tooltip="{{ $item['label'] }}">
                                <i class="mdi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span><i class="mdi mdi-chevron-down arrow"></i>
                            </summary>
                            <div class="nav-children">
                                @foreach ($item['items'] as $child)
                                    @continue(isset($child['permission']) && auth()->user()->cannot($child['permission']))
                                    <a href="{{ route($child['route'], $child['parameters'] ?? []) }}">{{ $child['label'] }}</a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                @endforeach
            </nav>
        </aside>

        <div class="main-panel">
            <header class="topbar">
                <button class="menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false" data-sidebar-toggle>
                    <i class="mdi mdi-menu"></i>
                </button>
                <div class="topbar-context"><strong>Head Office</strong><span>Business date: {{ now()->format('d M Y') }}</span></div>
                <div class="topbar-actions">
                    <a class="icon-button" href="{{ route('notifications.index') }}" aria-label="Notifications"><i class="mdi mdi-bell-outline"></i><span></span></a>
                    <div class="user-menu">
                        <button class="user-chip" type="button" data-user-menu aria-expanded="false">
                            <span class="avatar">{{ strtoupper(substr(auth()->user()->first_name, 0, 1).substr(auth()->user()->last_name, 0, 1)) }}</span>
                            <span>
                                <strong>{{ auth()->user()->full_name }}</strong>
                                <small>{{ auth()->user()->getRoleNames()->first() ?? 'Employee' }}</small>
                            </span>
                            <i class="mdi mdi-chevron-down"></i>
                        </button>
                        <div class="user-menu-dropdown" data-user-menu-dropdown>
                            <a href="{{ route('profile.edit') }}"><i class="mdi mdi-account-outline"></i>My profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"><i class="mdi mdi-logout-variant"></i>Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>
            <main class="content-area">
                @yield('content')
            </main>

            <footer class="app-footer">
                &copy; {{ now()->year }} {{ config('microfinance.institution_name') }}.
                Financial operations are audit controlled.
            </footer>
        </div>
    </div>
    <script src="{{ asset('app-assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/app.min.js') }}"></script>
    <script src="{{ asset('app-assets/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/microfinance.js') }}"></script>
</body>
</html>
