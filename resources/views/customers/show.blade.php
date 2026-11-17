@extends('layouts.app')
@section('title', $customer->first_name.' '.$customer->last_name.' — Borrower Profile')
@section('content')
<style>
.px-profile{--accent:#ff0844;color:#192235}.px-profile .px-panel{background:#fff;border:1px solid #e9edf3;border-radius:18px;box-shadow:0 2px 7px rgba(20,30,50,.045)}.px-profile .px-muted{color:#8390a5}.px-profile .px-pill{border-radius:9px;padding:5px 10px;font-size:.78rem;font-weight:700;background:#f2f4f8}.px-profile .px-stat{padding:20px;min-height:126px}.px-profile .px-stat .value{font-size:1.55rem;font-weight:800;overflow-wrap:anywhere}.px-profile .px-section{padding:22px}.px-profile .nav-pills .nav-link{color:#637087;font-weight:600;border-radius:0;border-bottom:2px solid transparent}.px-profile .nav-pills .nav-link.active{background:transparent;color:var(--accent);border-color:var(--accent)}.px-profile .px-label{font-size:.75rem;color:#8590a4;margin-bottom:3px}.px-profile .px-field{font-weight:600;overflow-wrap:anywhere}.px-profile .table thead th{background:#f8f9fb;color:#7c8799;font-size:.74rem;text-transform:uppercase;white-space:nowrap}.px-profile .table td{vertical-align:middle}.px-profile .btn-danger{background:#ff0844;border-color:#ff0844}.px-profile .px-scroll{overflow-x:auto}.px-profile .px-progress{height:8px;background:#edf0f4;border-radius:10px;overflow:hidden}.px-profile .px-progress span{display:block;height:100%;background:#12b981}.px-profile details summary{cursor:pointer;font-weight:700}

.px-profile .px-meter{height:13px;border-radius:999px;background:#e9eef3;overflow:hidden}
.px-profile .px-meter span{display:block;height:100%;border-radius:999px;transition:width .4s ease}
.px-profile .px-export{position:relative}.px-profile .px-export summary{list-style:none;cursor:pointer}
.px-profile .px-export-list{position:absolute;right:0;top:calc(100% + 6px);z-index:30;background:#fff;border:1px solid #bbf7d0;border-radius:16px;box-shadow:0 15px 40px #0f172a2b;list-style:none;padding:8px;min-width:180px}
.px-profile .px-export-list a{display:block;padding:10px;border-radius:10px;text-decoration:none}.px-profile .px-export-list a:hover{background:#ecfdf5}
.px-profile .tab-pane{display:none}.px-profile .tab-pane.px-visible{display:block;opacity:1}
.px-profile .nav-pills{gap:6px}.px-profile .nav-pills .nav-link{border-radius:10px;padding:12px 15px}.px-profile .nav-pills .nav-link.active{background:#ecfdf5;color:#047857;border-bottom:2px solid #059669}
.px-document-overlay{position:fixed;inset:0;z-index:9999;background:#0f172acc;display:flex;align-items:center;justify-content:center;padding:20px}
.px-document-box{background:white;border:2px solid #10b981;border-radius:20px;width:min(960px,96vw);height:min(850px,90vh);display:flex;flex-direction:column;overflow:hidden}
.px-document-box header{padding:12px 18px;display:flex;align-items:center;justify-content:space-between;gap:10px}
.px-document-box iframe,.px-document-box img{width:100%;flex:1;min-height:0;object-fit:contain;border:0}

.px-profile .px-stat{border-left:4px solid #0f766e;background:linear-gradient(145deg,#fff,#f6fbfa);transition:transform .2s,box-shadow .2s}.px-profile .px-stat:hover{transform:translateY(-3px);box-shadow:0 12px 25px #0f172a12}
.px-risk-gauge{width:240px;height:120px;margin:auto;position:relative;overflow:hidden;background:conic-gradient(from 270deg at 50% 100%,#059669 0deg,#84cc16 55deg,#facc15 95deg,#f97316 135deg,#dc2626 180deg,transparent 180deg);border-radius:130px 130px 0 0}
.px-risk-gauge:before{content:"";position:absolute;left:30px;right:30px;bottom:0;height:90px;background:white;border-radius:100px 100px 0 0}
.px-risk-pointer{position:absolute;width:96px;height:4px;background:#1e293b;left:24px;bottom:0;transform-origin:96px center;transform:rotate(var(--risk-angle));z-index:2;border-radius:5px}.px-risk-center{position:absolute;width:17px;height:17px;bottom:-8px;left:calc(50% - 8px);border-radius:50%;background:#1e293b;z-index:3}
</style>
<div class="px-profile">
@include('partials.alerts')
<a href="{{ route('customers.index') }}" class="text-decoration-none px-muted d-inline-block mb-3">← Back to Borrowers &amp; KYC</a>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
 <div><h1 class="h3 fw-bold mb-1">{{ $customer->first_name }} {{ $customer->middle_name }} {{ $customer->last_name }} <span class="px-pill {{ $customer->verified_at ? 'text-success' : 'text-warning' }}">{{ $customer->verified_at ? 'KYC Verified' : 'KYC Pending' }}</span></h1><div class="px-muted">{{ $customer->customer_number }} · {{ $customer->type }} · {{ $customer->status->value }}</div></div>
 <div class="d-flex flex-wrap gap-2"><details class="px-export"><summary class="btn btn-outline-secondary">Export profile ▾</summary><ul class="px-export-list"><li><a class="dropdown-item" href="{{ route('customers.profile.export',[$customer,'format'=>'pdf']) }}">Download PDF</a></li><li><a class="dropdown-item" href="{{ route('customers.profile.export',[$customer,'format'=>'xlsx']) }}">Download Excel</a></li></ul></details>@can('customer.update')<a href="{{ route('customers.edit',$customer) }}" class="btn btn-outline-secondary">Edit borrower</a>@endcan @can('loan.application.create')<a href="{{ route('loan-applications.create',['customer_id'=>$customer->id]) }}" class="btn btn-danger">+ New loan application</a>@endcan</div>
</div>
<div class="px-panel px-section mb-4"><div class="row g-4 align-items-center"><div class="col-md-auto"><div class="d-flex align-items-center justify-content-center fw-bold" style="width:82px;height:82px;background:#ffe6ec;color:#ff0844;border-radius:18px;font-size:1.7rem">{{ strtoupper(mb_substr($customer->first_name,0,1).mb_substr($customer->last_name,0,1)) }}</div></div><div class="col"><div class="row g-3"><div class="col-sm-6 col-lg-3"><div class="px-label">Phone</div><div class="px-field">{{ $customer->phone }}</div></div><div class="col-sm-6 col-lg-3"><div class="px-label">Branch</div><div class="px-field">{{ $customer->branch?->name ?? 'Unassigned' }}</div></div><div class="col-sm-6 col-lg-3"><div class="px-label">Assigned officer</div><div class="px-field">{{ $customer->assignedOfficer?->full_name ?? 'Unassigned' }}</div></div><div class="col-sm-6 col-lg-3"><div class="px-label">KYC risk</div><div class="px-field">{{ $customer->kyc_risk_rating ?? 'Not assessed' }}</div></div></div></div></div></div>
<div class="row g-3 mb-4">@foreach (['Total loans'=>number_format($portfolio['total_loans']), 'Active loans'=>number_format($portfolio['active_loans']), 'Principal (TZS)'=>number_format($portfolio['principal'],2), 'Outstanding (TZS)'=>number_format($portfolio['outstanding'],2), 'Arrears (TZS)'=>number_format($portfolio['arrears'],2), 'Repayments (TZS)'=>number_format($portfolio['repayments'],2)] as $label=>$value)<div class="col-sm-6 col-xl-4"><div class="px-panel px-stat"><div class="px-muted text-uppercase small fw-bold mb-3">{{ $label }}</div><div class="value">{{ $value }}</div></div></div>@endforeach</div>
@php
    $portfolioOutstanding = max(0, (float) ($portfolio['outstanding'] ?? 0));
    $portfolioArrears = max(0, (float) ($portfolio['arrears'] ?? 0));
    $portfolioRepayments = max(0, (float) ($portfolio['repayments'] ?? 0));
    $arrearsShare = $portfolioOutstanding > 0
        ? min(100, round($portfolioArrears / $portfolioOutstanding * 100))
        : 0;
    $collectionBase = $portfolioOutstanding + $portfolioRepayments;
    $collectionShare = $collectionBase > 0
        ? min(100, round($portfolioRepayments / $collectionBase * 100))
        : 0;
@endphp
<div class="px-panel px-section mb-4">
    <div class="row align-items-center g-4">
        <div class="col-md-5 text-center">
            <h5 class="mb-3">Arrears exposure gauge</h5>
            <div class="px-risk-gauge" style="--risk-angle:{{ $arrearsShare * 1.8 }}deg" role="img" aria-label="Arrears exposure {{ $arrearsShare }} percent">
                <div class="px-risk-pointer"></div><div class="px-risk-center"></div>
            </div>
            <div class="fw-bold mt-2">{{ $arrearsShare }}% of outstanding</div>
            <span class="badge {{ $arrearsShare > 30 ? 'bg-danger' : ($arrearsShare > 10 ? 'bg-warning text-dark' : 'bg-success') }}">{{ $portfolioOutstanding <= 0 ? 'No outstanding balance' : ($arrearsShare > 30 ? 'High arrears exposure' : ($arrearsShare > 10 ? 'Moderate arrears exposure' : 'Low arrears exposure')) }}</span>
        </div>
        <div class="col-md-7">
            <h5>Borrower portfolio health</h5>
            <p class="px-muted">The pointer represents arrears divided by outstanding balance. It is an exposure indicator, not a credit score or prediction of default.</p>
            <div class="d-flex justify-content-between py-2 border-bottom"><span>Outstanding balance</span><strong>TZS {{ number_format($portfolioOutstanding, 2) }}</strong></div>
            <div class="d-flex justify-content-between py-2 border-bottom"><span>Arrears</span><strong>TZS {{ number_format($portfolioArrears, 2) }}</strong></div>
            <div class="d-flex justify-content-between py-2"><span>Recorded repayments</span><strong>TZS {{ number_format($portfolioRepayments, 2) }}</strong></div>
        </div>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="px-panel px-section h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Arrears exposure</strong>
                <span class="px-pill {{ $arrearsShare > 30 ? 'text-danger' : 'text-success' }}">{{ $arrearsShare }}%</span>
            </div>
            <div class="px-meter"><span style="width:{{ $arrearsShare }}%;background:{{ $arrearsShare > 30 ? '#dc2626' : '#059669' }}"></span></div>
            <p class="small px-muted mt-2 mb-0">Arrears as a share of outstanding balance. A higher percentage signals greater collection risk.</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="px-panel px-section h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Recorded repayment share</strong>
                <span class="px-pill text-success">{{ $collectionShare }}%</span>
            </div>
            <div class="px-meter"><span style="width:{{ $collectionShare }}%;background:#059669"></span></div>
            <p class="small px-muted mt-2 mb-0">Repayments divided by repayments plus current outstanding. This is not an on-time repayment rate.</p>
        </div>
    </div>
</div>
<div class="px-panel mb-4"><ul class="nav nav-pills px-3 pt-2 flex-wrap" id="borrowerTabs" role="tablist">@foreach (['loans'=>'Loans','profile'=>'Profile & KYC','payments'=>'Repayments','guarantors'=>'Guarantors & collateral','history'=>'Customer activity & KYC'] as $key=>$label)<li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-profile-tab="true" data-bs-target="#px-{{ $key }}" type="button">{{ $label }}</button></li>@endforeach</ul><div class="tab-content px-section">
<div class="tab-pane fade show active" id="px-loans"><div class="px-scroll"><table class="table table-hover"><thead><tr><th>Loan number</th><th>Status</th><th>Principal</th><th>Outstanding</th><th>Arrears</th><th>Disbursed</th><th>Maturity</th></tr></thead><tbody>@forelse($customer->loans as $loan)<tr><td class="fw-semibold">{{ $loan->loan_number }}</td><td><span class="px-pill">{{ $loan->status->value }}</span></td><td>{{ number_format((float)$loan->principal_amount,2) }}</td><td>{{ number_format((float)$loan->outstanding_principal,2) }}</td><td>{{ number_format((float)$loan->arrears_amount,2) }}</td><td>{{ $loan->disbursed_at?->format('d M Y') ?? '—' }}</td><td>{{ $loan->maturity_date?->format('d M Y') ?? '—' }}</td></tr>@empty<tr><td colspan="7" class="text-center py-4 px-muted">No loans recorded</td></tr>@endforelse</tbody></table></div><div class="small px-muted mt-2">Outstanding and arrears reflect recorded loan balances. Repayment totals exclude reversals and failed transactions where identifiable.</div></div>
<div class="tab-pane fade" id="px-profile"><div class="row g-3 mb-4">@foreach(['Full name'=>trim($customer->first_name.' '.$customer->middle_name.' '.$customer->last_name),'Email'=>$customer->email,'Gender'=>$customer->gender,'Date of birth'=>$customer->date_of_birth?->format('d M Y'),'National ID'=>$customer->identification_number,'Member number'=>$customer->customer_number,'KYC verified at'=>$customer->verified_at?->format('d M Y H:i')] as $label=>$value)<div class="col-md-6 col-xl-4"><div class="px-label">{{ $label }}</div><div class="px-field">{{ $value ?: '—' }}</div></div>@endforeach</div><h5>KYC readiness</h5><div class="d-flex justify-content-between mb-2"><span>{{ $kycReadiness['completed'] }} / {{ $kycReadiness['total'] }} checks</span><span class="px-pill">{{ $kycReadiness['ready'] ? 'Ready for review' : 'Action required' }}</span></div><div class="px-progress mb-3"><span style="width:{{ $kycReadiness['total'] ? round($kycReadiness['completed']/$kycReadiness['total']*100) : 0 }}%"></span></div><div class="row g-2">@foreach($kycReadiness['checks'] as $check)<div class="col-md-6"><div class="border rounded p-3 h-100"><strong class="{{ $check['passed'] ? 'text-success' : 'text-danger' }}">{{ $check['passed'] ? '✓' : '✕' }} {{ $check['label'] }}</strong><div class="small px-muted">{{ $check['detail'] }}</div></div></div>@endforeach</div><h5 class="mt-4">Documents</h5><div class="px-scroll"><table class="table"><thead><tr><th>Type</th><th>Filename</th><th>Verification</th><th></th></tr></thead><tbody>@forelse($customer->documents as $doc)<tr><td>{{ $doc->type }}</td><td>{{ $doc->original_name }}</td><td>{{ $doc->verified_at ? 'Verified' : 'Pending' }}</td><td><div class="d-flex align-items-center gap-2">@can('customer.document.view')<button type="button" class="btn btn-sm btn-outline-primary" data-preview-document="{{ route('customer-documents.download',$doc) }}" data-preview-name="{{ $doc->original_name }}">Preview</button><a href="{{ route('customer-documents.download',$doc) }}">Download</a>@endcan @if(! $doc->verified_at)@can('customer.verify')<form method="POST" action="{{ route('customer-documents.verify',$doc) }}">@csrf<button type="submit" class="btn btn-sm btn-success">Verify document</button></form>@endcan @endif</div></td></tr>@empty<tr><td colspan="4">No documents</td></tr>@endforelse</tbody></table></div></div>
<div class="tab-pane fade" id="px-payments"><div class="px-scroll"><table class="table"><thead><tr><th>Receipt</th><th>Loan</th><th>Amount (TZS)</th><th>Status</th><th>Method</th><th>Paid at</th></tr></thead><tbody>@forelse($recentRepayments as $payment)<tr><td>{{ $payment->receipt_number }}</td><td>{{ $payment->loan?->loan_number ?? $customer->loans->firstWhere('id',$payment->loan_id)?->loan_number }}</td><td>{{ number_format((float)$payment->amount,2) }}</td><td>{{ $payment->status }}</td><td>{{ $payment->payment_method }}</td><td>{{ $payment->paid_at?->format('d M Y H:i') ?? '—' }}</td></tr>@empty<tr><td colspan="6" class="text-center py-4">No repayments recorded</td></tr>@endforelse</tbody></table></div></div>
<div class="tab-pane fade" id="px-guarantors"><div class="row g-3"><div class="col-lg-6"><h5>Guarantors</h5>@forelse($customer->registeredGuarantors as $g)<div class="border rounded p-3 mb-2"><strong>{{ $g->first_name }} {{ $g->last_name }}</strong><div class="px-muted">{{ $g->phone }} · {{ $g->status }}</div></div>@empty<p class="px-muted">No guarantors registered</p>@endforelse</div><div class="col-lg-6"><h5>Collateral</h5>@forelse($customer->collaterals as $c)<div class="border rounded p-3 mb-2"><strong>{{ $c->collateral_number }}</strong><div class="px-muted">{{ $c->description }} · {{ $c->status }}</div></div>@empty<p class="px-muted">No collateral registered</p>@endforelse</div></div></div>
<div class="tab-pane fade" id="px-history"><h5>KYC decisions</h5><div class="px-scroll"><table class="table"><thead><tr><th>Decision</th><th>Risk</th><th>Reason</th><th>Reviewer</th><th>When</th></tr></thead><tbody>@forelse($customer->kycDecisions->sortByDesc('id') as $decision)<tr><td><span class="px-pill">{{ ucfirst($decision->decision) }}</span></td><td>{{ $decision->risk_rating ?? '—' }}</td><td>{{ $decision->reason ?? '—' }}</td><td>{{ $decision->reviewer?->full_name ?? 'Unknown' }}</td><td>{{ $decision->created_at?->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="5">No recorded KYC decisions. Historical approvals may predate the audit log.</td></tr>@endforelse</tbody></table></div></div>

</div></div>
</div>
<script>
(() => {
    const root = document.querySelector('.px-profile');
    if (!root) return;
    const tabs = Array.from(root.querySelectorAll('#borrowerTabs [data-bs-target]'));
    function activateTab(target) {
        tabs.forEach(button => {
            const active = button.dataset.bsTarget === target;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', String(active));
        });
        root.querySelectorAll('.tab-pane').forEach(pane => {
            const active = '#' + pane.id === target;
            pane.classList.toggle('px-visible', active);
            pane.classList.toggle('show', active);
            pane.classList.toggle('active', active);
        });
    }
    tabs.forEach(button => button.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        activateTab(button.dataset.bsTarget);
    }, true));
    activateTab(tabs[0]?.dataset.bsTarget || '#px-loans');

    root.addEventListener('click', async event => {
        const trigger = event.target.closest('[data-preview-document]');
        if (!trigger) return;
        const overlay = document.createElement('div');
        overlay.className = 'px-document-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        const box = document.createElement('div');
        box.className = 'px-document-box';
        const header = document.createElement('header');
        const name = document.createElement('strong');
        name.textContent = trigger.dataset.previewName || 'Document preview';
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn btn-outline-secondary';
        close.textContent = 'Close';
        header.append(name, close);
        const message = document.createElement('p');
        message.className = 'p-4';
        message.textContent = 'Loading document…';
        box.append(header, message);
        overlay.append(box);
        document.body.append(overlay);
        let objectUrl;
        const dismiss = () => {
            overlay.remove();
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            document.removeEventListener('keydown', onEscape);
        };
        const onEscape = keyEvent => { if (keyEvent.key === 'Escape') dismiss(); };
        close.addEventListener('click', dismiss);
        overlay.addEventListener('click', clickEvent => { if (clickEvent.target === overlay) dismiss(); });
        document.addEventListener('keydown', onEscape);
        try {
            const response = await fetch(trigger.dataset.previewDocument, {credentials:'same-origin'});
            if (!response.ok) throw new Error('Preview unavailable (' + response.status + ')');
            const blob = await response.blob();
            const mime = blob.type.toLowerCase();
            if (!mime.startsWith('image/') && mime !== 'application/pdf') {
                throw new Error('This file type cannot be previewed. Use Download instead.');
            }
            objectUrl = URL.createObjectURL(blob);
            const viewer = document.createElement(mime.startsWith('image/') ? 'img' : 'iframe');
            viewer.src = objectUrl;
            viewer.title = name.textContent;
            message.replaceWith(viewer);
        } catch (error) {
            message.textContent = error.message;
        }
    });
})();
</script>
@endsection
