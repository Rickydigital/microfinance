@extends('layouts.app')
@section('title', 'Borrowers & KYC')
@section('content')
@include('partials.alerts')
<style>
    .borrowers-ui {
        --accent: #fa174e;
        --line: #e8ebf1;
        --muted: #798398;
        color: #172033
    }

    .borrowers-ui .card {
        border: 1px solid var(--line) !important;
        border-radius: 18px;
        box-shadow: 0 2px 4px #1720330a !important
    }

    .borrowers-ui .metric {
        padding: 23px;
        min-height: 130px
    }

    .borrowers-ui .metric .number {
        font-size: 29px;
        font-weight: 750
    }

    .borrowers-ui .metric .label {
        font-size: 12px;
        text-transform: uppercase;
        color: var(--muted);
        font-weight: 700;
        letter-spacing: .04em
    }

    .borrowers-ui .btn-accent {
        background: var(--accent);
        border-color: var(--accent);
        color: white
    }

    .borrowers-ui .btn-accent:hover {
        background: #d90e40;
        color: white
    }

    .borrowers-ui .table thead th {
        background: #f7f8fa;
        color: #697488;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .03em;
        padding: 18px 12px;
        white-space: nowrap
    }

    .borrowers-ui .table tbody td {
        padding: 18px 12px;
        vertical-align: middle
    }

    .borrowers-ui .form-control,
    .borrowers-ui .form-select {
        border-radius: 11px;
        min-height: 44px
    }

    .borrowers-ui .avatar {
        display: inline-flex;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #ffe6ec;
        color: #fa174e;
        align-items: center;
        justify-content: center;
        font-weight: 700
    }

    .borrowers-ui .modal-content {
        border: 0;
        border-radius: 18px
    }

    .borrowers-ui .modal-header,
    .borrowers-ui .modal-footer {
        background: white
    }

    .borrowers-ui .modal-body {
        max-height: 65vh;
        overflow-y: auto
    }

    .borrowers-ui .dropdown-menu {
        border: 1px solid var(--line);
        border-radius: 12px;
        box-shadow: 0 8px 30px #17203320
    }

    .borrowers-ui .section-label {
        font-size: 12px;
        letter-spacing: .06em;
        color: #8993a5;
        font-weight: 700;
        text-transform: uppercase
    }

    /* Customer dashboard refinement */
    .borrowers-ui .customer-kpi {
        padding: 18px 20px;
        min-height: 155px;
        background: linear-gradient(145deg, #fff, #fafbff);
        overflow: hidden
    }

    .borrowers-ui .customer-kpi-top,
    .borrowers-ui .customer-kpi-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px
    }

    .borrowers-ui .customer-kpi-icon {
        width: 35px;
        height: 35px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: color-mix(in srgb, var(--kpi-color) 11%, white);
        color: var(--kpi-color);
        font-size: 20px
    }

    .borrowers-ui .customer-kpi-share {
        font-size: 12px;
        font-weight: 750;
        color: var(--kpi-color)
    }

    .borrowers-ui .customer-kpi-main {
        margin-top: 12px
    }

    .borrowers-ui .customer-kpi-label {
        font-size: 12px;
        font-weight: 650;
        color: #64748b
    }

    .borrowers-ui .customer-kpi-value {
        font-size: 28px;
        font-weight: 800;
        line-height: 1.2;
        margin: 4px 0;
        color: #172033
    }

    .borrowers-ui .customer-kpi-foot {
        font-size: 11px;
        color: #94a3b8
    }

    .borrowers-ui .customer-gauge {
        width: 78px;
        height: 78px;
        border-radius: 50%;
        flex-shrink: 0;
        background: conic-gradient(var(--kpi-color) var(--kpi-progress), #e9edf5 0);
        display: grid;
        place-items: center;
        position: relative
    }

    .borrowers-ui .customer-gauge:before {
        content: '';
        position: absolute;
        inset: 10px;
        background: white;
        border-radius: 50%
    }

    .borrowers-ui .customer-gauge span {
        position: relative;
        font-size: 17px;
        font-weight: 800;
        color: var(--kpi-color)
    }

    .borrowers-ui .customer-gauge small {
        font-size: 10px
    }

    .borrowers-ui .modal {
        position: fixed;
        inset: 0;
        z-index: 1060;
        overflow-y: auto;
        background: #1018278c;
        padding: 24px 12px
    }

    .borrowers-ui .modal.show {
        display: block !important
    }

    .borrowers-ui .modal-dialog {
        margin: 24px auto;
        max-width: 520px
    }

    .borrowers-ui .modal-dialog.modal-xl {
        max-width: min(1060px, 96vw)
    }

    .borrowers-ui .modal-content {
        background: #fff;
        box-shadow: 0 24px 80px #0f172a45
    }

    .borrowers-ui .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid #e9edf5
    }

    .borrowers-ui .modal-body {
        padding: 24px
    }

    .borrowers-ui .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #e9edf5;
        display: flex;
        justify-content: flex-end;
        gap: 8px
    }

    .borrowers-ui .dropdown {
        position: relative
    }

    .borrowers-ui .dropdown-menu.show {
        display: block !important;
        position: fixed;
        z-index: 1080;
        min-width: 180px;
        background: #fff;
        padding: 7px 0
    }

    .borrowers-ui .dropdown-item {
        display: block;
        width: 100%;
        padding: 9px 15px;
        text-align: left;
        background: transparent;
        border: 0;
        color: #253148;
        text-decoration: none
    }

    .borrowers-ui .dropdown-item:hover {
        background: #f2f5fb
    }

    .borrowers-ui .btn-close {
        border: 0;
        background: transparent;
        cursor: pointer;
        font-size: 22px
    }

    .borrowers-ui .btn-close:after {
        content: '×'
    }

    @media(max-width:640px) {
        .borrowers-ui .customer-kpi {
            min-height: 140px;
            padding: 15px
        }

        .borrowers-ui .customer-gauge {
            width: 64px;
            height: 64px
        }

        .borrowers-ui .customer-kpi-value {
            font-size: 23px
        }

        .borrowers-ui .modal-dialog {
            margin: 0 auto
        }

        .borrowers-ui .modal {
            padding: 10px
        }
    }

    /* Consistent two-column, rounded and accessible customer dialogs */
    .borrowers-ui .modal-dialog.modal-xl {
        max-width: min(680px, calc(100vw - 32px)) !important;
        width: 100% !important;
        margin: 24px auto !important
    }

    .borrowers-ui .modal-content {
        border: 1px solid #e4e9f2 !important;
        border-radius: 24px !important;
        overflow: hidden !important;
        box-shadow: 0 24px 80px #10182738 !important;
        max-height: calc(100dvh - 48px) !important;
        display: flex !important;
        flex-direction: column !important
    }

    .borrowers-ui .modal-content>form {
        display: flex;
        flex-direction: column;
        min-height: 0;
        flex: 1
    }

    .borrowers-ui .modal-header,
    .borrowers-ui .modal-footer {
        flex-shrink: 0;
        background: #fff;
        padding: 18px 24px !important
    }

    .borrowers-ui .modal-body {
        overflow-y: auto !important;
        min-height: 0;
        overscroll-behavior: contain;
        padding: 24px !important
    }

    .borrowers-ui .modal-body .row>[class*=col-md-] {
        min-width: 0
    }

    .borrowers-ui .modal-body .form-control,
    .borrowers-ui .modal-body .form-select {
        border-radius: 12px !important;
        min-height: 45px
    }

    .borrowers-ui .dropdown-menu {
        position: fixed !important;
        z-index: 99999 !important;
        max-height: min(460px, 75vh);
        overflow-y: auto;
        box-shadow: 0 14px 40px #15243a2e;
        border-radius: 14px !important
    }

    @media(max-width:767px) {
        .borrowers-ui .modal-dialog.modal-xl {
            margin: 8px auto !important;
            max-width: calc(100vw - 16px) !important
        }

        .borrowers-ui .modal-content {
            max-height: calc(100dvh - 16px) !important;
            border-radius: 18px !important
        }
    }

    .borrowers-ui .dropdown-menu {
        border-radius: 18px !important;
        min-width: 225px;
        padding: 8px;
        border: 1px solid #a7f3d0;
        box-shadow: 0 16px 38px #0f172a28;
        animation: menuAppear .16s ease-out
    }

    .borrowers-ui .dropdown-menu .dropdown-item {
        border-radius: 10px;
        padding: 10px 14px;
        transition: background .18s, color .18s
    }

    .borrowers-ui .dropdown-menu .dropdown-item:hover {
        background: #ecfdf5;
        color: #047857
    }

    .borrowers-ui .dropdown-menu .text-danger:hover {
        background: #fef2f2;
        color: #b91c1c
    }

    .borrowers-ui .dropdown-menu .text-success:hover {
        background: #ecfdf5;
        color: #047857
    }

    .borrowers-ui .modal-content {
        border-radius: 26px !important
    }

    .borrowers-ui .modal-dialog.modal-xl {
        max-width: min(680px, calc(100vw - 32px)) !important
    }

    @keyframes menuAppear {
        from {
            opacity: 0;
            transform: translateY(-5px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    @media(prefers-reduced-motion:reduce) {
        .borrowers-ui .dropdown-menu {
            animation: none
        }
    }

    /* Compact controls and clear action affordances */
    .borrowers-ui .customer-filter-card .card-body {
        padding: 15px 18px
    }

    .borrowers-ui .customer-filter-card .form-control,
    .borrowers-ui .customer-filter-card .form-select {
        border: 1px solid #dce3ec;
        background-color: #fff
    }

    .borrowers-ui .customer-filter-card .form-control:focus,
    .borrowers-ui .customer-filter-card .form-select:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px #10b9811c
    }

    .borrowers-ui .customer-kin-row {
        padding: 14px;
        margin: 0 0 14px !important;
        border: 1px solid #e2e8f0;
        border-radius: 15px;
        background: #f8fafc;
        row-gap: 14px !important
    }

    .borrowers-ui [data-customer-menu-toggle] {
        border-radius: 12px;
        min-width: 38px;
        min-height: 36px;
        font-size: 21px;
        line-height: 1
    }

    body>.dropdown-menu.show {
        border-radius: 17px;
        border: 1px solid #bbf7d0;
        box-shadow: 0 16px 45px #0f172a33;
        padding: 8px;
        min-width: 230px;
        max-height: 70vh;
        overflow: auto;
        background: white;
        animation: customerMenuIn .14s ease-out
    }

    body>.dropdown-menu.show .dropdown-item {
        border-radius: 10px;
        padding: 10px 13px;
        color: #334155
    }

    body>.dropdown-menu.show .dropdown-item:hover {
        background: #eff6ff;
        color: #1d4ed8
    }

    body>.dropdown-menu.show .text-danger {
        color: #b91c1c !important
    }

    body>.dropdown-menu.show .text-danger:hover {
        background: #fef2f2;
        color: #b91c1c !important
    }

    body>.dropdown-menu.show .text-success {
        color: #047857 !important
    }

    body>.dropdown-menu.show .text-success:hover {
        background: #ecfdf5;
        color: #047857 !important
    }

    @keyframes customerMenuIn {
        from {
            opacity: 0;
            transform: translateY(-5px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    @media(prefers-reduced-motion:reduce) {
        body>.dropdown-menu.show {
            animation: none
        }
    }

</style>
<style>
    /* Phase 10: compact filters, reliable floating menus and dependent fields */
    .borrowers-ui .customer-filter-card .form-select {
        padding-right: 2.1rem;
        text-overflow: ellipsis;
        appearance: auto !important;
        background-image: none !important
    }

    .borrowers-ui .customer-filter-card select::-ms-expand {
        display: block
    }

    .borrowers-ui .customer-filter-card .form-select:focus {
        outline: 2px solid #047857;
        outline-offset: 1px
    }

    .borrowers-ui .customer-filter-card .form-select {
        min-width: 0
    }

    .borrowers-ui .customer-filter-card .card-body {
        overflow: visible
    }

    .borrowers-ui .customer-filter-card .row>[class*=col-] {
        min-width: 0
    }

    .borrowers-ui .customer-filter-feedback {
        font-size: 12px;
        color: #047857;
        margin: 7px 4px;
        min-height: 18px
    }

    .borrowers-ui .customer-filter-card.is-loading {
        opacity: .8
    }

    body>.dropdown-menu.show {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        min-width: 220px;
        border-radius: 16px !important
    }

    body>.dropdown-menu.show .dropdown-item {
        white-space: normal
    }

    .borrowers-ui .customer-location select option[disabled] {
        display: none
    }

</style>
<style>
    body>.customer-menu-portal {
        display: block !important;
        position: fixed !important;
        z-index: 2147483640 !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        background: #fff;
        border: 2px solid #10b981;
        border-radius: 18px;
        min-width: 225px;
        padding: 8px;
        box-shadow: 0 18px 48px #0f172a30;
    }

    body>.customer-menu-portal .dropdown-item {
        display: block;
        padding: 10px 14px;
        border-radius: 10px;
    }

    body>.customer-menu-portal .dropdown-item:hover {
        background: #ecfdf5;
    }

    .borrowers-ui .customer-location select {
        appearance: auto !important;
    }

    .borrowers-ui .customer-location select:disabled {
        background: #f1f5f9;
        cursor: not-allowed;
    }

</style>
<style>
    /* Native controls are intentionally independent of Bootstrap JS. */
    .borrowers-ui .modal:target {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        pointer-events: auto !important;
        overflow: auto !important
    }

    .borrowers-ui .modal:target .modal-dialog {
        transform: none !important;
        pointer-events: auto !important
    }

    .borrowers-ui .modal:target:before {
        content: "";
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .6);
        z-index: -1
    }

    .borrowers-ui .modal {
        z-index: 2000 !important
    }

    .borrowers-ui .modal-dialog.modal-xl {
        max-width: min(640px, calc(100vw - 24px)) !important
    }

    .customer-export-menu {
        position: relative
    }

    .customer-export-menu summary {
        list-style: none;
        cursor: pointer
    }

    .customer-export-menu summary::-webkit-details-marker {
        display: none
    }

    .customer-native-menu {
        position: absolute;
        top: calc(100% + 6px);
        right: 0;
        z-index: 1000;
        min-width: 185px;
        list-style: none;
        margin: 0;
        padding: 8px;
        background: white;
        border: 1px solid #a7f3d0;
        border-radius: 16px;
        box-shadow: 0 14px 38px #0f172a30
    }

    .customer-native-menu a {
        display: block;
        padding: 11px 14px;
        border-radius: 9px;
        text-decoration: none;
        color: #1e293b
    }

    .customer-native-menu a:hover {
        background: #ecfdf5
    }

    .customer-popover-menu:popover-open {
        display: block;
        position: fixed;
        inset: auto 18px auto auto;
        top: 20%;
        margin: 0;
        min-width: 235px;
        max-height: 65vh;
        overflow: auto;
        padding: 9px;
        list-style: none;
        border: 2px solid #10b981;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 20px 55px #0f172a42;
        z-index: 2147483647
    }

    .customer-popover-menu .dropdown-item {
        display: block;
        width: 100%;
        border: 0;
        background: transparent;
        text-align: left;
        padding: 10px 12px;
        border-radius: 10px;
        color: #1e293b;
        text-decoration: none
    }

    .customer-popover-menu .dropdown-item:hover {
        background: #ecfdf5
    }

    .customer-popover-menu .text-danger {
        color: #b91c1c !important
    }

    .customer-popover-menu .text-success {
        color: #047857 !important
    }

    .borrowers-ui .customer-location select {
        appearance: auto !important
    }

    .borrowers-ui .customer-filter-card .form-select {
        appearance: auto !important
    }

    .borrowers-ui .customer-quick-modal .modal-dialog.modal-xl {
        max-width: min(640px, calc(100vw - 24px)) !important
    }

    .borrowers-ui .customer-quick-modal .modal-body {
        max-height: 72vh !important
    }

    .borrowers-ui .customer-quick-heading {
        font-size: 12px;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 800;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 10px;
        margin: 12px 0 16px
    }

    .borrowers-ui .customer-loan-stat {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px;
        background: #f8fafc;
        height: 100%
    }

    .borrowers-ui .customer-loan-stat strong {
        display: block;
        font-size: 24px;
        color: #0f172a
    }

    .borrowers-ui .customer-loan-stat span {
        font-size: 12px;
        color: #64748b
    }

    /* Polished filtering and accessible focus styles */
    .borrowers-ui .customer-filter-card {
        border-radius: 22px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 8px 28px #0f172a0a !important
    }

    .borrowers-ui .customer-filter-card .card-body {
        padding: 22px !important
    }

    .borrowers-ui .customer-filter-card .form-control,
    .borrowers-ui .customer-filter-card .form-select {
        height: 48px;
        border-radius: 13px !important;
        border: 1px solid #cbd5e1 !important
    }

    .borrowers-ui .customer-filter-card .btn {
        min-height: 42px;
        border-radius: 12px !important
    }

    .borrowers-ui .modal:target .modal-content {
        border: 2px solid transparent !important;
        background: linear-gradient(#fff, #fff) padding-box, conic-gradient(from var(--border-spin), #10b981, #bbf7d0, #0ea5e9, #10b981) border-box !important;
        animation: modalBorderSpin 5s linear infinite
    }

    @property --border-spin {
        syntax: '<angle>';
        initial-value: 0deg;
        inherits: false
    }

    @keyframes modalBorderSpin {
        to {
            --border-spin: 360deg
        }
    }

    @media(prefers-reduced-motion:reduce) {
        .borrowers-ui .modal:target .modal-content {
            animation: none
        }
    }

</style>
<div class="borrowers-ui" id="customer-top">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Borrowers &amp; KYC</h1>
            <p class="text-muted mb-0">Manage borrowers, identity checks and account status</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <details class="customer-export-menu">
                <summary class="btn btn-outline-secondary">Export <span aria-hidden="true">▾</span></summary>
                <ul class="customer-native-menu">
                    <li><a class="dropdown-item" href="{{ route('customers.export', array_merge(['format'=>'pdf'], request()->only(['search','kyc','status','type']))) }}">Export
                            PDF</a></li>
                    <li><a class="dropdown-item" href="{{ route('customers.export', array_merge(['format'=>'xlsx'], request()->only(['search','kyc','status','type']))) }}">Export
                            Excel</a></li>
                </ul>
            </details>
            @can('customer.create')<a type="button" class="btn btn-outline-secondary" href="#importCustomers">Import
                customers</a><a type="button" class="btn btn-accent" href="#registerCustomer">+ Register
                customer</a>@endcan
        </div>
    </div>
    @php
    $borrowerTotal = max(1, (int) ($metrics['total'] ?? 0));
    $customerKpis = [
    ['key'=>'total','title'=>'Total
    borrowers','icon'=>'mdi-account-group-outline','color'=>'#4f46e5','base'=>$borrowerTotal],
    ['key'=>'verified','title'=>'Verified
    KYC','icon'=>'mdi-shield-check-outline','color'=>'#059669','base'=>$borrowerTotal],
    ['key'=>'pending','title'=>'Pending KYC','icon'=>'mdi-timer-sand','color'=>'#d97706','base'=>$borrowerTotal],
    ['key'=>'active','title'=>'Active
    accounts','icon'=>'mdi-account-check-outline','color'=>'#0284c7','base'=>$borrowerTotal],
    ];
    @endphp
    <div class="row g-3 mb-4 customer-kpi-grid">
        @foreach($customerKpis as $kpi)
        @php $value=(int)($metrics[$kpi['key']] ?? 0); $percentage=min(100,max(0,round($value/$kpi['base']*100)));
        @endphp
        <div class="col-sm-6 col-xl-3">
            <div class="card customer-kpi h-100" style="--kpi-color:{{ $kpi['color'] }};--kpi-progress:{{ $percentage }}%">
                <div class="customer-kpi-top"><span class="customer-kpi-icon"><i class="mdi {{ $kpi['icon'] }}"></i></span><span class="customer-kpi-share">{{ $percentage
                        }}%</span></div>
                <div class="customer-kpi-main">
                    <div>
                        <div class="customer-kpi-label">{{ $kpi['title'] }}</div>
                        <div class="customer-kpi-value">{{ number_format($value) }}</div>
                        <div class="customer-kpi-foot">{{ $kpi['key']==='total' ? 'Registered borrower records' : 'Share
                            of all borrowers' }}</div>
                    </div>
                    <div class="customer-gauge" role="img" aria-label="{{ $percentage }} percent"><span>{{ $percentage
                            }}<small>%</small></span></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="card mb-3 customer-filter-card">
        <div class="card-body">
            <form id="customerFilters" method="GET" action="{{ route('customers.index') }}" class="row g-2 align-items-center">
                <div class="col-lg-5"><input class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Search name, phone, member number, NIDA..."></div>
                <div class="col-sm-4 col-lg-2"><select name="kyc" class="form-select">
                        <option value="">All KYC</option>
                        <option value="verified" @selected(request('kyc')==='verified' )>Verified</option>
                        <option value="pending" @selected(request('kyc')==='pending' )>Pending</option>
                    </select></div>
                <div class="col-sm-4 col-lg-2"><select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status')==='active' )>Active</option>
                        <option value="other" @selected(request('status')==='other' )>Other</option>
                    </select></div>
                <div class="col-sm-4 col-lg-2"><select name="type" class="form-select">
                        <option value="">All types</option>@foreach($customerTypes as $type)<option value="{{ $type->code }}" @selected(request('type')===$type->code)>{{ $type->name }}
                        </option>@endforeach
                    </select></div>
                <div class="col-12 d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary px-4" href="{{ route('customers.index') }}" title="Reset filters">Reset</a><button type="submit" class="btn btn-success px-4">Apply filters</button></div>
            </form>
        </div>
    </div>
    <div class="card customer-results">
        <div class="table-responsive customer-listing">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Borrower</th>
                        <th>Member ID</th>
                        <th>Contact</th>
                        <th>National ID</th>
                        <th>Branch</th>
                        <th>Type</th>
                        <th>KYC</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2"><span class="avatar">{{
                                    strtoupper(substr($customer->first_name,0,1)) }}</span>
                                <div><strong>{{ $customer->first_name }} {{ $customer->last_name }}</strong>
                                    <div class="text-muted small">{{ $customer->assignedOfficer?->full_name ?? 'No
                                        officer' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $customer->customer_number }}</td>
                        <td>{{ $customer->phone }}
                            <div class="small text-muted">{{ $customer->email }}</div>
                        </td>
                        <td>{{ $customer->identification_number ?: '—' }}</td>
                        <td>{{ $customer->branch?->name ?? 'Unassigned' }}</td>
                        <td>{{ $customer->type }}</td>
                        <td><span class="badge {{ $customer->verified_at ? 'bg-success' : 'bg-warning text-dark' }}">{{
                                $customer->verified_at ? 'Verified' : 'Pending' }}</span></td>
                        <td><span class="badge bg-light text-dark">{{ $customer->status->value }}</span></td>
                        <td>
                            <div class="customer-actions"><button class="btn btn-sm btn-outline-secondary" type="button" popovertarget="borrower-actions-{{ $customer->id }}" aria-label="Borrower actions">&#8942;</button>
                                <ul id="borrower-actions-{{ $customer->id }}" popover="auto" class="customer-popover-menu">
                                    <li><a class="dropdown-item" href="{{ route('customers.show',$customer) }}">Full
                                            profile &amp; KYC</a></li>
                                    @can('customer.verify')@if(!$customer->verified_at)<li><a class="dropdown-item" type="button" href="#kyc-{{ $customer->id }}">Review KYC</a></li>@endif
                                    @endcan<li><a type="button" class="dropdown-item" href="#quick-{{ $customer->id }}">Quick view</a></li>
                                    @can('customer.document.create')<li><a type="button" class="dropdown-item" href="#document-{{ $customer->id }}">Add KYC document</a></li>@endcan
                                    @can('customer.update')<li><a type="button" class="dropdown-item" href="#edit-{{ $customer->id }}">Edit borrower</a></li>@endcan
                                    @can('customer.update')<li>
                                        <form method="POST" action="{{ route('customers.status',$customer) }}">@csrf
                                            @method('PATCH')<input type="hidden" name="status" value="{{ $customer->status === \App\CustomerStatus::Active ? 'Suspended' : 'Active' }}"><button class="dropdown-item {{ $customer->status === \App\CustomerStatus::Active ? 'text-danger' : 'text-success' }}" @if($customer->status !== \App\CustomerStatus::Active &&
                                                !$customer->verified_at) disabled title="Approve KYC first" @endif>{{
                                                $customer->status === \App\CustomerStatus::Active ? 'Deactivate
                                                customer' : 'Activate customer' }}</button></form>
                                    </li>@endcan
                                    @can('customer.update')<li><a type="button" class="dropdown-item" href="#password-{{ $customer->id }}">Reset portal password</a></li>@endcan
                                    @can('customer.delete')<li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('customers.destroy',$customer) }}" onsubmit="return confirm('Delete this borrower? This is blocked when financial records exist.')">
                                            @csrf @method('DELETE')<button class="dropdown-item text-danger">Delete (if
                                                eligible)</button></form>
                                    </li>@endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty<tr>
                        <td colspan="9" class="text-center text-muted py-5">No borrowers match your filters.</td>
                    </tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $customers->links() }}</div>
    </div>
    <div id="customerRowModals">
        @foreach($customers as $customer)
        <div class="modal fade customer-quick-modal" id="quick-{{ $customer->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    @php
                    $primaryAddress = $customer->addresses?->firstWhere('is_primary', true) ??
                    $customer->addresses?->last();
                    $currentEconomic = $customer->economicProfiles?->whereNull('effective_to')->last();
                    $nextOfKin = $customer->relationships?->where('type', 'Next of Kin') ?? collect();
                    $customerLoans = $customer->loans ?? collect();
                    @endphp
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title fw-bold">{{ trim($customer->first_name.' '.$customer->middle_name.'
                                '.$customer->last_name) }}</h5>
                            <small class="text-muted">{{ $customer->customer_number }} · {{ $customer->type }}</small>
                        </div>
                        <a href="#customer-top" class="btn-close" aria-label="Close"></a>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge {{ $customer->verified_at ? 'bg-success' : 'bg-warning text-dark' }}">{{
                                $customer->verified_at ? 'KYC Verified' : 'KYC Pending' }}</span>
                            <span class="badge bg-light text-dark">{{ $customer->status->value }}</span>
                        </div>
                        <h6 class="customer-quick-heading">Personal & membership</h6>
                        <div class="row g-3 mb-4">
                            @php
                            $quickPersonalFields = ['Customer number' => $customer->customer_number, 'Customer type' =>
                            $customer->type, 'First name' => $customer->first_name, 'Middle name' =>
                            $customer->middle_name, 'Last name' => $customer->last_name, 'Phone' => $customer->phone,
                            'Email' => $customer->email, 'Gender' => $customer->gender, 'Date of birth' =>
                            $customer->date_of_birth?->format('d M Y'), 'Identification number' =>
                            $customer->identification_number, 'Branch' => $customer->branch?->name, 'Assigned officer'
                            => $customer->assignedOfficer?->full_name, 'KYC verified' =>
                            $customer->verified_at?->format('d M Y H:i')];
                            @endphp
                            @foreach ($quickPersonalFields as $label => $value)
                            <div class="col-sm-6">
                                <div class="small text-muted">{{ $label }}</div>
                                <div class="fw-semibold text-break">{{ $value ?: '—' }}</div>
                            </div>
                            @endforeach
                        </div>
                        <h6 class="customer-quick-heading">Residential address</h6>
                        <div class="row g-3 mb-4">
                            @php
                            $quickAddressFields = ['Region' => $primaryAddress?->region,
                            'District' => $primaryAddress?->district, 'Ward' => $primaryAddress?->ward,
                            'Street' => $primaryAddress?->street, 'Address description' =>
                            $primaryAddress?->description];
                            @endphp
                            @foreach ($quickAddressFields as $label => $value)
                            <div class="col-sm-6">
                                <div class="small text-muted">{{ $label }}</div>
                                <div class="fw-semibold text-break">{{ $value ?: '—' }}</div>
                            </div>
                            @endforeach
                        </div>
                        <h6 class="customer-quick-heading">Economic profile</h6>
                        <div class="row g-3 mb-4">
                            @if($currentEconomic)
                            @php
                            $quickEconomicFields = ['Occupation' => $currentEconomic->occupation, 'Income source' =>
                            $currentEconomic->income_source, 'Monthly income (TZS)' => $currentEconomic->monthly_income,
                            'Monthly expenses (TZS)' => $currentEconomic->monthly_expenses, 'Existing debt (TZS)' =>
                            $currentEconomic->existing_debt, 'Business turnover (TZS)' =>
                            $currentEconomic->business_turnover];
                            @endphp
                            @foreach ($quickEconomicFields as $label => $value)
                            <div class="col-sm-6">
                                <div class="small text-muted">{{ $label }}</div>
                                <div class="fw-semibold text-break">{{ $value ?? '—' }}</div>
                            </div>
                            @endforeach
                            @else <div class="col-12 text-muted">No economic profile recorded.</div> @endif
                        </div>
                        @php
                        $loanStatuses = $customerLoans->map(fn ($loan) => $loan->status?->value ?? (string)
                        $loan->status);
                        $loanSummary = [
                        'Total loans' => $customerLoans->count(),
                        'Active' => $loanStatuses->filter(fn ($status) => in_array($status, ['Active', 'Restructured'],
                        true))->count(),
                        'In arrears' => $loanStatuses->filter(fn ($status) => $status === 'In Arrears')->count(),
                        'Pending' => $loanStatuses->filter(fn ($status) => $status === 'Pending Disbursement')->count(),
                        'Completed' => $loanStatuses->filter(fn ($status) => in_array($status, ['Settled', 'Closed'],
                        true))->count(),
                        ];
                        @endphp
                        <h6 class="customer-quick-heading">Loan summary</h6>
                        <div class="row g-2 mb-4">
                            @foreach($loanSummary as $label => $count)
                            <div class="col-6">
                                <div class="customer-loan-stat"><span>{{ $label }}</span><strong>{{ $count }}</strong>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <h6 class="customer-quick-heading">Next of kin ({{ $nextOfKin->count() }})</h6>
                        <div class="row g-3 mb-4">@forelse($nextOfKin as $kin)
                            <div class="col-sm-6">
                                <div class="rounded-3 border p-3 h-100"><strong>{{ $kin->full_name ?? $kin->name ??
                                        'Next of kin' }}</strong>
                                    <div class="small text-muted">{{ $kin->relationship ?? '—' }}</div>
                                    <div>{{ $kin->phone ?? '—' }}</div>
                                    <div class="small">{{ $kin->address ?? '' }}</div>
                                </div>
                            </div>
                            @empty <div class="col-12 text-muted">No next of kin recorded.</div> @endforelse
                        </div>
                        <h6 class="customer-quick-heading">KYC status</h6>
                        <div class="mb-3"><span class="badge {{ $customer->verified_at ? 'bg-success' : 'bg-warning text-dark' }}">{{
                                $customer->verified_at ? 'KYC Verified' : 'KYC Pending' }}</span></div>
                    </div>
                    <div class="modal-footer"><a href="#customer-top" class="btn btn-outline-secondary">Close</a><a class="btn btn-success" href="{{ route('customers.show', $customer) }}">Open full borrower
                            profile</a></div>
                </div>
            </div>
        </div>
        @can('customer.verify')@if(!$customer->verified_at)
        <div class="modal fade" id="kyc-{{ $customer->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">KYC decision · {{ $customer->customer_number }}</h5><a href="#customer-top" type="button" class="btn-close" aria-label="Close"></a>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Inspect identity, address, economic information and evidence on the
                            full profile before approving. Approval is blocked by the server until all required checks
                            pass.</p><a class="btn btn-outline-secondary btn-sm mb-3" href="{{ route('customers.show', $customer) }}">Review evidence on full profile</a>
                        <form method="POST" action="{{ route('customers.verify', $customer) }}">@csrf<label class="form-label" for="risk-{{ $customer->id }}">KYC risk rating</label><select id="risk-{{ $customer->id }}" class="form-select mb-2" name="kyc_risk_rating" required>
                                <option value="">Select rating</option>
                                <option>Low</option>
                                <option>Medium</option>
                                <option>High</option>
                            </select><button type="submit" class="btn btn-success w-100">Approve verified KYC</button>
                        </form>
                        <hr>
                        <form method="POST" action="{{ route('customers.kyc.reject', $customer) }}">@csrf<label class="form-label" for="reason-{{ $customer->id }}">Reason for
                                rejection</label><textarea id="reason-{{ $customer->id }}" name="reason" class="form-control mb-2" rows="3" minlength="10" maxlength="2000" required placeholder="Explain what must be corrected"></textarea><button type="submit" class="btn btn-outline-danger w-100">Reject KYC</button></form>
                    </div>
                </div>
            </div>
        </div>
        @endif @endcan
        @can('customer.document.create')
        <div class="modal fade" id="document-{{ $customer->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data" action="{{ route('customers.documents.store',$customer) }}">
                        @csrf<div class="modal-header">
                            <h5 class="modal-title">Add KYC evidence</h5>
                            <a href="#customer-top" type="button" class="btn-close" aria-label="Close"></a>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Document type</label>
                            <select class="form-select mb-3" name="type" required>
                                <option value="">Select type</option>
                            @foreach($documentTypes as $docType)
                                <option value="{{ $docType->code }}">{{ $docType->name }}</option>
                            @endforeach
                            </select><label class="form-label">Document</label>
                            <input type="file"
                                name="document"
                                class="form-control"
                                accept=".pdf,.jpg,.jpeg,.png"
                                required>
                            </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-accent">Upload evidence</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>@endcan
        @can('customer.update')<div class="modal fade" id="password-{{ $customer->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('customers.portal-password.reset',$customer) }}">@csrf<div class="modal-header">
                            <h5 class="modal-title">Reset portal password</h5><a href="#customer-top" type="button" class="btn-close" aria-label="Close"></a>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">Only active and verified customers can access the portal. A new
                                temporary password will be sent to their registered contact.</p><input type="hidden" name="reset_mode" value="generate"><button type="submit" class="btn btn-accent">Generate
                                and send temporary password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>@endcan
        @can('customer.update')<div class="modal fade" id="edit-{{ $customer->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data" action="{{ route('customers.update',$customer) }}">@csrf @method('PUT')<div class="modal-header">
                            <h5 class="modal-title">Edit borrower · {{ $customer->customer_number }}</h5><a href="#customer-top" class="btn-close" aria-label="Close"></a>
                        </div>
                        <div class="modal-body">@include('customers._modal_fields',['editingCustomer'=>$customer])</div>
                        <div class="modal-footer"><a href="#customer-top" type="button" class="btn btn-light">Cancel</a><button class="btn btn-accent">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>@endcan
        @endforeach
    </div>
    @can('customer.create')<div class="modal fade" id="registerCustomer" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" action="{{ route('customers.store') }}">@csrf<div class="modal-header">
                        <h5 class="modal-title">Register new borrower</h5><a href="#customer-top" class="btn-close" aria-label="Close"></a>
                    </div>
                    <div class="modal-body">@include('customers._modal_fields',['editingCustomer'=>null])<div class="section-label mt-4 mb-3">KYC evidence — optional at registration</div>
                        <div class="alert alert-light border small">Attach up to five documents (PDF/JPG/PNG, maximum 10
                            MB each). Files are stored privately and remain <strong>unverified</strong> until an
                            authorized officer reviews them.</div>
                        <div id="registrationDocuments">
                            <div class="row g-2 mb-2 document-row">
                                <div class="col-md-5"><select name="kyc_documents[0][type]" class="form-select">
                                        <option value="">Select document type</option>
                                        @foreach($documentTypes as $docType)
                                        <option value="{{ $docType->code }}" data-customer-type="{{ $docType->customer_type_code }}">{{ $docType->name }}
                                        </option>
                                        @endforeach
                                    </select></div>
                                <div class="col-md-7"><input type="file" name="kyc_documents[0][file]" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="addRegistrationDocument">+
                            Add another document</button>
                    </div>
                    <div class="modal-footer"><a href="#customer-top" type="button" class="btn btn-light">Cancel</a><button class="btn btn-accent">Register borrower</button>
                    </div>
                </form>
            </div>
        </div>
    </div>@endcan
    @can('customer.create')
    <div class="modal fade" id="importCustomers" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Import customers</h5>
                        <div class="text-muted small">Upload, validate, preview and confirm without leaving this page
                        </div>
                    </div><a href="#customer-top" type="button" class="btn-close" aria-label="Close"></a>
                </div>
                <div class="modal-body">
                    <div id="customerImportContent">
                        <p class="text-muted">Import up to 2,000 borrowers per spreadsheet. Keep phone numbers as text
                            to preserve leading zeroes.</p>
                        <a href="{{ route('customers.import.template') }}" class="btn btn-outline-success btn-sm mb-3">Download Excel template</a>
                        <form method="POST" action="{{ route('customers.import.store') }}" enctype="multipart/form-data" data-customer-import-form>@csrf
                            <label class="form-label" for="import-customer-file">Excel or CSV spreadsheet</label><input id="import-customer-file" type="file" name="file" class="form-control" accept=".xlsx,.csv" required>
                            <button type="submit" class="btn btn-accent mt-3">Validate and preview</button>
                        </form>
                    </div>
                    <div id="customerImportError" class="alert alert-danger mt-3 d-none" role="alert"></div>
                </div>
                <div class="modal-footer"><a href="#customer-top" type="button" class="btn btn-outline-secondary">Close</a></div>
            </div>
        </div>
    </div>
    @endcan
</div>
<script>
    (() => {
        'use strict';
        const page = document.querySelector('.borrowers-ui');
        if (!page || page.dataset.customerScriptBound === '1') return;
        page.dataset.customerScriptBound = '1';

        /* =====================================================================
         * LOCATION CASCADE  (Region -> District -> Ward -> Street)
         * One implementation only. Rules:
         *  - the native <select> is the single source of truth
         *  - after every programmatic change Select2 is told to re-render
         *  - every cascade has an "epoch"; any stale async work is discarded
         *  - initialization is idempotent (one run per .customer-location group)
         * ===================================================================== */
        const LEVELS = ['region', 'district', 'ward', 'street'];
        const ENDPOINTS = {
            district: @json(route('locations.districts'))
            , ward: @json(route('locations.wards'))
            , street: @json(route('locations.streets'))
        , };
        const PARAMS = {
            district: 'region_id'
            , ward: 'district_id'
            , street: 'ward_id'
        };
        const groupState = new WeakMap(); // group -> { epoch, controllers, initialized }

        const stateOf = group => {
            let state = groupState.get(group);
            if (!state) {
                state = {
                    epoch: 0
                    , controllers: new Map()
                    , initialized: false
                };
                groupState.set(group, state);
            }
            return state;
        };
        const levelSelect = (group, level) => group.querySelector(`select[data-location="${level}"]`);

        // Make Select2 re-render from the native <select> (no-op without Select2).
        function syncSelect2(select) {
            const jq = window.jQuery;
            if (jq && jq(select).data('select2')) jq(select).trigger('change.select2');
        }

        function fillSelect(select, rows, placeholder, selected = '') {
            select.replaceChildren(new Option(placeholder, ''));
            for (const row of rows) select.add(new Option(row.name, String(row.id)));
            const wanted = String(selected ? ? '');
            select.value = wanted && Array.from(select.options).some(o => o.value === wanted) ? wanted : '';
            syncSelect2(select);
        }

        // Empty + disable every level from `fromIndex` down, cancelling their requests.
        function clearFrom(group, fromIndex) {
            const state = stateOf(group);
            for (let i = Math.max(1, fromIndex); i < LEVELS.length; i++) {
                const level = LEVELS[i];
                const select = levelSelect(group, level);
                if (!select) continue;
                state.controllers.get(level) ? .abort();
                state.controllers.delete(level);
                select.dataset.selected = '';
                fillSelect(select, [], `Select ${LEVELS[i - 1]} first`);
                select.disabled = true;
                syncSelect2(select);
            }
        }

        // Load one level using the CURRENT value of its parent.
        // Returns true only when the level now has a selected value.
        async function loadLevel(group, level, selected, epoch) {
            const state = stateOf(group);
            const index = LEVELS.indexOf(level);
            const parent = levelSelect(group, LEVELS[index - 1]);
            const select = levelSelect(group, level);
            if (!parent || !select || !parent.value) return false;

            const parentValue = parent.value;
            state.controllers.get(level) ? .abort();
            const controller = new AbortController();
            state.controllers.set(level, controller);

            fillSelect(select, [], `Loading ${level}s...`);
            select.disabled = true;
            syncSelect2(select);
            try {
                const url = new URL(ENDPOINTS[level], window.location.href);
                url.searchParams.set(PARAMS[level], parentValue);
                const response = await fetch(url, {
                    signal: controller.signal
                    , credentials: 'same-origin'
                    , headers: {
                        Accept: 'application/json'
                    }
                , });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const payload = await response.json();
                const rows = Array.isArray(payload) ? payload : payload ? .data;
                if (!Array.isArray(rows)) throw new Error(`Unexpected ${level} API response`);

                // Stale response? Newer selection happened meanwhile -> discard.
                if (controller.signal.aborted || state.epoch !== epoch || parent.value !== parentValue) return false;

                fillSelect(select, rows, rows.length ? `Select ${level}` : `No ${level}s available`, selected);
                select.disabled = rows.length === 0;
                syncSelect2(select);
                return Boolean(select.value);
            } catch (error) {
                if (error.name === 'AbortError') return false;
                if (state.epoch === epoch) {
                    console.error(`Failed to load ${level}:`, error);
                    fillSelect(select, [], `Unable to load ${level}`);
                    select.disabled = true;
                    syncSelect2(select);
                }
                return false;
            } finally {
                if (state.controllers.get(level) === controller) state.controllers.delete(level);
            }
        }

        // User changed a location select -> clear children, load next level.
        async function onLocationChange(select) {
            const group = select.closest('.customer-location');
            const index = LEVELS.indexOf(select.dataset.location);
            if (!group || index < 0 || index === LEVELS.length - 1) return;
            const state = stateOf(group);
            const epoch = ++state.epoch; // invalidates all in-flight work
            clearFrom(group, index + 1);
            if (select.value) await loadLevel(group, LEVELS[index + 1], '', epoch);
        }

        // First-time setup of a modal: restores saved values (Edit / old() input).
        // Idempotent: safe to call from hashchange, load and shown.bs.modal.
        function initLocations(scope) {
            const group = scope ? .querySelector ? .('.customer-location');
            if (!group) return;
            const state = stateOf(group);
            if (state.initialized) return;
            const region = levelSelect(group, 'region');
            if (!region) return;
            state.initialized = true;

            const saved = Object.fromEntries(LEVELS.map(l => [l, levelSelect(group, l) ? .dataset.selected || '']));
            const epoch = ++state.epoch;
            syncSelect2(region); // Select2 text must match native value
            clearFrom(group, 1); // dependents start empty / disabled

            (async () => {
                for (let i = 1; i < LEVELS.length; i++) {
                    const parent = levelSelect(group, LEVELS[i - 1]);
                    if (!parent ? .value) break;
                    const ok = await loadLevel(group, LEVELS[i], saved[LEVELS[i]], epoch);
                    if (!ok || state.epoch !== epoch) break;
                }
            })();
        }

        function initActiveModal() {
            const target = document.querySelector('.borrowers-ui .modal:target');
            if (target) initLocations(target);
        }

        function bindLocationEvents() {
            const jq = window.jQuery;
            if (jq) {
                // jQuery handlers receive BOTH native events and Select2's
                // jQuery-triggered events, so this single binding is enough.
                jq(document).off('.customerLocation')
                    .on('change.customerLocation', '.customer-location select[data-location]', function() {
                        onLocationChange(this);
                    })
                    .on('shown.bs.modal.customerLocation', '#registerCustomer, [id^="edit-"]', function() {
                        initLocations(this);
                    });
            } else {
                document.addEventListener('change', event => {
                    const select = event.target.closest ? .('.customer-location select[data-location]');
                    if (select) onLocationChange(select);
                });
            }
            const register = document.getElementById('registerCustomer');
            if (register) initLocations(register);
            initActiveModal();
        }

        // jQuery/Select2 are usually loaded AFTER page content scripts, so wait.
        if (window.jQuery || document.readyState === 'complete') bindLocationEvents();
        else window.addEventListener('load', bindLocationEvents, {
            once: true
        });

        // Edit modals are initialized lazily when opened (avoids ~3 requests per row on load).
        window.addEventListener('hashchange', initActiveModal);

        /* =====================================================================
         * SEARCH / FILTERS / PAGINATION
         * ===================================================================== */
        const filters = document.getElementById('customerFilters');
        let pendingSearch;
        let activeRequest;

        async function searchCustomers(url) {
            activeRequest ? .abort();
            const controller = new AbortController();
            activeRequest = controller;
            try {
                const response = await fetch(url, {
                    signal: controller.signal
                    , credentials: 'same-origin'
                    , headers: {
                        Accept: 'text/html'
                    }
                , });
                if (!response.ok) throw new Error(`Search failed: ${response.status}`);
                const result = new DOMParser().parseFromString(await response.text(), 'text/html');
                const results = result.querySelector('.customer-results');
                const modals = result.querySelector('#customerRowModals');
                if (!results || !modals) throw new Error('Missing customer results');
                if (controller !== activeRequest) return;
                page.querySelector('.customer-results').replaceWith(results);
                page.querySelector('#customerRowModals').replaceWith(modals);
                const newLinks = result.querySelectorAll('.customer-export-menu a');
                page.querySelectorAll('.customer-export-menu a').forEach((a, i) => {
                    if (newLinks[i]) a.href = newLinks[i].href;
                });
                history.replaceState(null, '', url);
                initActiveModal();
            } catch (error) {
                if (error.name === 'AbortError') return;
                console.error(error);
                window.location.assign(url);
            }
        }

        function applyFilters() {
            if (!filters) return;
            clearTimeout(pendingSearch);
            const url = new URL(filters.action, window.location.href);
            for (const [name, value] of new FormData(filters)) {
                if (String(value).trim()) url.searchParams.set(name, String(value).trim());
            }
            searchCustomers(url.toString());
        }

        filters ? .addEventListener('submit', event => {
            event.preventDefault();
            applyFilters();
        });
        filters ? .querySelector('input[name="search"]') ? .addEventListener('input', () => {
            clearTimeout(pendingSearch);
            pendingSearch = setTimeout(applyFilters, 400);
        });
        // Auto-apply when a dropdown changes (native and Select2).
        // jQuery loads later, so bind again on window load.
        function bindFilterSelects() {
            filters ? .querySelectorAll('select').forEach(sel => {
                sel.addEventListener('change', applyFilters);
                window.jQuery ? .(sel).off('change.liveFilter').on('change.liveFilter', applyFilters);
            });
        }
        bindFilterSelects();
        if (document.readyState === 'complete') bindFilterSelects();
        else window.addEventListener('load', bindFilterSelects, {
            once: true
        });

        /* =====================================================================
         * CLICK DELEGATION: pagination, next of kin, registration documents
         * ===================================================================== */
        page.addEventListener('click', event => {
            const pagination = event.target.closest('.customer-results .pagination a');
            if (pagination) {
                event.preventDefault();
                searchCustomers(pagination.href);
            }

            const addKin = event.target.closest('[data-add-kin]');
            if (addKin) {
                const list = addKin.closest('.modal') ? .querySelector('.customer-kin-list');
                const row = list ? .querySelector('.customer-kin-row');
                if (!row || list.children.length >= 5) return;
                const clone = row.cloneNode(true);
                const index = Date.now();
                clone.querySelectorAll('input,select').forEach(field => {
                    field.value = '';
                    field.name = field.name.replace(/kin\[\d+\]/, `kin[${index}]`);
                });
                list.appendChild(clone);
            }

            const removeKin = event.target.closest('[data-remove-kin]');
            if (removeKin) {
                const row = removeKin.closest('.customer-kin-row');
                if (row ? .parentElement ? .children.length > 1) row.remove();
                else row ? .querySelectorAll('input').forEach(input => input.value = '');
            }

            if (event.target.closest('#addRegistrationDocument')) {
                const container = document.getElementById('registrationDocuments');
                if (!container || container.children.length >= 5) return;
                const clone = container.querySelector('.document-row').cloneNode(true);
                clone.querySelectorAll('.select2-container').forEach(node => node.remove());
                clone.querySelectorAll('select,input').forEach(field => {
                    field.classList.remove('select2-hidden-accessible');
                    field.removeAttribute('data-select2-id');
                    field.removeAttribute('id');
                    field.disabled = false;
                    field.style.display = '';
                    field.value = '';
                    field.name = field.name.replace(/\[\d+\]/, `[${Date.now()}]`);
                });
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-outline-danger btn-sm mt-2';
                remove.textContent = 'Remove document';
                remove.addEventListener('click', () => clone.remove());
                clone.appendChild(remove);
                container.appendChild(clone);
            }
        });

        /* =====================================================================
         * INCOME SOURCE "OTHER" FIELD
         * ===================================================================== */
        page.addEventListener('change', event => {
            const income = event.target.closest('[data-income-source-select]');
            if (!income) return;
            const wrapper = income.closest('.customer-income-source');
            const other = wrapper ? .querySelector('[data-income-source-other]');
            const value = wrapper ? .querySelector('[data-income-source-value]');
            if (other) other.hidden = income.value !== 'other';
            if (value) value.value = income.value === 'other' ? (other ? .value || '') : income.value;
        });
        page.addEventListener('input', event => {
            if (event.target.matches('[data-income-source-other]')) {
                const value = event.target.closest('.customer-income-source') ? .querySelector('[data-income-source-value]');
                if (value) value.value = event.target.value;
            }
        });

        /* =====================================================================
         * CLOSE :target MODALS (backdrop click / Escape)
         * ===================================================================== */
        document.addEventListener('click', event => {
            if (event.target.matches ? .('.borrowers-ui .modal:target')) {
                window.location.hash = 'customer-top';
            }
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && document.querySelector('.borrowers-ui .modal:target')) {
                window.location.hash = 'customer-top';
            }
        });
    })();

</script>
@endsection
