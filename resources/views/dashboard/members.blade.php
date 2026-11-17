@php
    $memberChartMaximum = max(1, (int) $memberMonthlyTrend->max('total'));
    $borrowerChartMaximum = max(1, (int) $newReturningTrend->flatMap(fn ($month) => [$month['first'], $month['returning']])->max());
    $memberDistributionTotal = max(1, (int) $memberAnalytics['total']);
@endphp
<section class="dashboard-panel" data-dashboard-panel="members" hidden>
    <section class="insight-kpis">
        @foreach ([
            ['Members', $memberAnalytics['total'], 'Registered customer profiles', '#4f46e5', '#eef2ff', 'mdi-account-group-outline'],
            ['Borrowing now', $memberAnalytics['borrowing'], 'Customers with an open loan', '#0f766e', '#e7f7f3', 'mdi-account-cash-outline'],
            ['Active youth', $memberAnalytics['youth'], 'Customers aged 35 or younger', '#db2777', '#fdf2f8', 'mdi-account-star-outline'],
            ['Never borrowed', $memberAnalytics['neverBorrowed'], 'Profiles with no released loan', '#64748b', '#f1f5f9', 'mdi-account-minus-outline'],
            ['Joined this year', $memberAnalytics['joined'], 'New customer registrations', '#2563eb', '#eff6ff', 'mdi-account-plus-outline'],
            ['Growth rate', number_format($memberAnalytics['growthRate'],1).'%', 'Members gained versus year opening', '#059669', '#ecfdf5', 'mdi-trending-up'],
            ['Inactive 90+ days', $memberAnalytics['inactive'], 'No recently released loan', '#d97706', '#fff7ed', 'mdi-weather-night'],
            ['Active borrowing rate', number_format($memberAnalytics['activeRate'],1).'%', 'Share currently borrowing', '#7c3aed', '#f5f3ff', 'mdi-chart-donut'],
        ] as [$label,$value,$caption,$tone,$soft,$icon])
            <article class="surface insight-kpi" style="--tone:{{ $tone }};--soft:{{ $soft }}"><div class="insight-kpi-head"><span>{{ $label }}</span><i class="mdi {{ $icon }}"></i></div><strong>{{ is_numeric($value) ? number_format($value) : $value }}</strong><small>{{ $caption }}</small></article>
        @endforeach
    </section>

    <div class="insight-chart-grid">
        <section class="surface insight-panel"><div class="section-title"><div><h2>Member growth</h2><p>Registered members at month end and newly joined members.</p></div><div class="chart-legend"><span><i style="background:#4f46e5"></i>Total</span><span><i style="background:#10b981"></i>Joined</span></div></div><div class="single-bars">@foreach ($memberMonthlyTrend as $month)<div class="single-bar"><div class="bars"><i style="height:{{ $month['total'] / $memberChartMaximum * 100 }}%;background:#4f46e5" title="{{ $month['total'] }} total"></i><i style="height:{{ $month['joined'] / $memberChartMaximum * 100 }}%;background:#10b981" title="{{ $month['joined'] }} joined"></i></div><small>{{ $month['label'] }}</small></div>@endforeach</div></section>
        <section class="surface insight-panel"><div class="section-title"><div><h2>New versus returning borrowers</h2><p>First-time and repeat borrowing by month.</p></div><div class="chart-legend"><span><i style="background:#0284c7"></i>First loan</span><span><i style="background:#e11d48"></i>Returning</span></div></div><div class="single-bars">@foreach ($newReturningTrend as $month)<div class="single-bar"><div class="bars"><i style="height:{{ $month['first'] / $borrowerChartMaximum * 100 }}%;background:#0284c7" title="{{ $month['first'] }} first loans"></i><i style="height:{{ $month['returning'] / $borrowerChartMaximum * 100 }}%;background:#e11d48" title="{{ $month['returning'] }} returning"></i></div><small>{{ $month['label'] }}</small></div>@endforeach</div></section>
    </div>

    <div class="insight-chart-grid">
        @foreach ([['Members by gender',$memberGender],['Members by age',$memberAge],['Customer type',$memberTypes]] as [$title,$distribution])
            <section class="surface insight-panel"><div class="section-title"><div><h2>{{ $title }}</h2><p>Profile distribution across registered customers.</p></div></div><div class="distribution-list">@forelse ($distribution as $label=>$count)<div class="distribution-row"><header><span>{{ $label }}</span><strong>{{ $count }} · {{ number_format($count / $memberDistributionTotal * 100,1) }}%</strong></header><div class="distribution-track"><i style="width:{{ $count / $memberDistributionTotal * 100 }}%;background:{{ $loop->index % 3 === 0 ? '#4f46e5' : ($loop->index % 3 === 1 ? '#10b981' : '#f59e0b') }}"></i></div></div>@empty<p class="text-muted">No profile data available.</p>@endforelse</div></section>
        @endforeach
    </div>
</section>
