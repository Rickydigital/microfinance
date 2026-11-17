@extends('layouts.app')
@section('title', 'Borrowers & KYC')
@section('content')
@include('partials.alerts')
<style>
.borrowers-ui{--accent:#fa174e;--line:#e8ebf1;--muted:#798398;color:#172033}.borrowers-ui .card{border:1px solid var(--line)!important;border-radius:18px;box-shadow:0 2px 4px #1720330a!important}.borrowers-ui .metric{padding:23px;min-height:130px}.borrowers-ui .metric .number{font-size:29px;font-weight:750}.borrowers-ui .metric .label{font-size:12px;text-transform:uppercase;color:var(--muted);font-weight:700;letter-spacing:.04em}.borrowers-ui .btn-accent{background:var(--accent);border-color:var(--accent);color:white}.borrowers-ui .btn-accent:hover{background:#d90e40;color:white}.borrowers-ui .table thead th{background:#f7f8fa;color:#697488;font-size:12px;text-transform:uppercase;letter-spacing:.03em;padding:18px 12px;white-space:nowrap}.borrowers-ui .table tbody td{padding:18px 12px;vertical-align:middle}.borrowers-ui .form-control,.borrowers-ui .form-select{border-radius:11px;min-height:44px}.borrowers-ui .avatar{display:inline-flex;width:42px;height:42px;border-radius:50%;background:#ffe6ec;color:#fa174e;align-items:center;justify-content:center;font-weight:700}.borrowers-ui .modal-content{border:0;border-radius:18px}.borrowers-ui .modal-header,.borrowers-ui .modal-footer{background:white}.borrowers-ui .modal-body{max-height:65vh;overflow-y:auto}.borrowers-ui .dropdown-menu{border:1px solid var(--line);border-radius:12px;box-shadow:0 8px 30px #17203320}.borrowers-ui .section-label{font-size:12px;letter-spacing:.06em;color:#8993a5;font-weight:700;text-transform:uppercase}

/* Customer dashboard refinement */
.borrowers-ui .customer-kpi{padding:18px 20px;min-height:155px;background:linear-gradient(145deg,#fff,#fafbff);overflow:hidden}
.borrowers-ui .customer-kpi-top,.borrowers-ui .customer-kpi-main{display:flex;align-items:center;justify-content:space-between;gap:12px}
.borrowers-ui .customer-kpi-icon{width:35px;height:35px;display:grid;place-items:center;border-radius:12px;background:color-mix(in srgb,var(--kpi-color) 11%,white);color:var(--kpi-color);font-size:20px}
.borrowers-ui .customer-kpi-share{font-size:12px;font-weight:750;color:var(--kpi-color)}
.borrowers-ui .customer-kpi-main{margin-top:12px}.borrowers-ui .customer-kpi-label{font-size:12px;font-weight:650;color:#64748b}.borrowers-ui .customer-kpi-value{font-size:28px;font-weight:800;line-height:1.2;margin:4px 0;color:#172033}.borrowers-ui .customer-kpi-foot{font-size:11px;color:#94a3b8}
.borrowers-ui .customer-gauge{width:78px;height:78px;border-radius:50%;flex-shrink:0;background:conic-gradient(var(--kpi-color) var(--kpi-progress),#e9edf5 0);display:grid;place-items:center;position:relative}.borrowers-ui .customer-gauge:before{content:'';position:absolute;inset:10px;background:white;border-radius:50%}.borrowers-ui .customer-gauge span{position:relative;font-size:17px;font-weight:800;color:var(--kpi-color)}.borrowers-ui .customer-gauge small{font-size:10px}
.borrowers-ui .modal{position:fixed;inset:0;z-index:1060;overflow-y:auto;background:#1018278c;padding:24px 12px}.borrowers-ui .modal.show{display:block!important}.borrowers-ui .modal-dialog{margin:24px auto;max-width:520px}.borrowers-ui .modal-dialog.modal-xl{max-width:min(1060px,96vw)}.borrowers-ui .modal-content{background:#fff;box-shadow:0 24px 80px #0f172a45}.borrowers-ui .modal-header{display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid #e9edf5}.borrowers-ui .modal-body{padding:24px}.borrowers-ui .modal-footer{padding:16px 24px;border-top:1px solid #e9edf5;display:flex;justify-content:flex-end;gap:8px}
.borrowers-ui .dropdown{position:relative}.borrowers-ui .dropdown-menu.show{display:block!important;position:fixed;z-index:1080;min-width:180px;background:#fff;padding:7px 0}.borrowers-ui .dropdown-item{display:block;width:100%;padding:9px 15px;text-align:left;background:transparent;border:0;color:#253148;text-decoration:none}.borrowers-ui .dropdown-item:hover{background:#f2f5fb}.borrowers-ui .btn-close{border:0;background:transparent;cursor:pointer;font-size:22px}.borrowers-ui .btn-close:after{content:'×'}
@media(max-width:640px){.borrowers-ui .customer-kpi{min-height:140px;padding:15px}.borrowers-ui .customer-gauge{width:64px;height:64px}.borrowers-ui .customer-kpi-value{font-size:23px}.borrowers-ui .modal-dialog{margin:0 auto}.borrowers-ui .modal{padding:10px}}

/* Consistent two-column, rounded and accessible customer dialogs */
.borrowers-ui .modal-dialog.modal-xl{max-width:min(680px,calc(100vw - 32px))!important;width:100%!important;margin:24px auto!important}
.borrowers-ui .modal-content{border:1px solid #e4e9f2!important;border-radius:24px!important;overflow:hidden!important;box-shadow:0 24px 80px #10182738!important;max-height:calc(100dvh - 48px)!important;display:flex!important;flex-direction:column!important}
.borrowers-ui .modal-content>form{display:flex;flex-direction:column;min-height:0;flex:1}
.borrowers-ui .modal-header,.borrowers-ui .modal-footer{flex-shrink:0;background:#fff;padding:18px 24px!important}
.borrowers-ui .modal-body{overflow-y:auto!important;min-height:0;overscroll-behavior:contain;padding:24px!important}
.borrowers-ui .modal-body .row>[class*=col-md-]{min-width:0}
.borrowers-ui .modal-body .form-control,.borrowers-ui .modal-body .form-select{border-radius:12px!important;min-height:45px}
.borrowers-ui .dropdown-menu{position:fixed!important;z-index:99999!important;max-height:min(460px,75vh);overflow-y:auto;box-shadow:0 14px 40px #15243a2e;border-radius:14px!important}
@media(max-width:767px){.borrowers-ui .modal-dialog.modal-xl{margin:8px auto!important;max-width:calc(100vw - 16px)!important}.borrowers-ui .modal-content{max-height:calc(100dvh - 16px)!important;border-radius:18px!important}}

.borrowers-ui .dropdown-menu{border-radius:18px!important;min-width:225px;padding:8px;border:1px solid #a7f3d0;box-shadow:0 16px 38px #0f172a28;animation:menuAppear .16s ease-out}.borrowers-ui .dropdown-menu .dropdown-item{border-radius:10px;padding:10px 14px;transition:background .18s,color .18s}.borrowers-ui .dropdown-menu .dropdown-item:hover{background:#ecfdf5;color:#047857}.borrowers-ui .dropdown-menu .text-danger:hover{background:#fef2f2;color:#b91c1c}.borrowers-ui .dropdown-menu .text-success:hover{background:#ecfdf5;color:#047857}.borrowers-ui .modal-content{border-radius:26px!important}.borrowers-ui .modal-dialog.modal-xl{max-width:min(680px,calc(100vw - 32px))!important}@keyframes menuAppear{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}@media(prefers-reduced-motion:reduce){.borrowers-ui .dropdown-menu{animation:none}}
/* Compact controls and clear action affordances */
.borrowers-ui .customer-filter-card .card-body{padding:15px 18px}
.borrowers-ui .customer-filter-card .form-control,.borrowers-ui .customer-filter-card .form-select{border:1px solid #dce3ec;background-color:#fff}
.borrowers-ui .customer-filter-card .form-control:focus,.borrowers-ui .customer-filter-card .form-select:focus{border-color:#10b981;box-shadow:0 0 0 3px #10b9811c}
.borrowers-ui .customer-kin-row{padding:14px;margin:0 0 14px!important;border:1px solid #e2e8f0;border-radius:15px;background:#f8fafc;row-gap:14px!important}
.borrowers-ui [data-customer-menu-toggle]{border-radius:12px;min-width:38px;min-height:36px;font-size:21px;line-height:1}
body>.dropdown-menu.show{border-radius:17px;border:1px solid #bbf7d0;box-shadow:0 16px 45px #0f172a33;padding:8px;min-width:230px;max-height:70vh;overflow:auto;background:white;animation:customerMenuIn .14s ease-out}
body>.dropdown-menu.show .dropdown-item{border-radius:10px;padding:10px 13px;color:#334155}
body>.dropdown-menu.show .dropdown-item:hover{background:#eff6ff;color:#1d4ed8}
body>.dropdown-menu.show .text-danger{color:#b91c1c!important}body>.dropdown-menu.show .text-danger:hover{background:#fef2f2;color:#b91c1c!important}
body>.dropdown-menu.show .text-success{color:#047857!important}body>.dropdown-menu.show .text-success:hover{background:#ecfdf5;color:#047857!important}
@keyframes customerMenuIn{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
@media(prefers-reduced-motion:reduce){body>.dropdown-menu.show{animation:none}}
</style>
<style>
/* Phase 10: compact filters, reliable floating menus and dependent fields */
.borrowers-ui .customer-filter-card .form-select{padding-right:2.1rem;text-overflow:ellipsis;appearance:auto!important;background-image:none!important}
.borrowers-ui .customer-filter-card select::-ms-expand{display:block}
.borrowers-ui .customer-filter-card .form-select:focus{outline:2px solid #047857;outline-offset:1px}
.borrowers-ui .customer-filter-card .form-select{min-width:0}
.borrowers-ui .customer-filter-card .card-body{overflow:visible}
.borrowers-ui .customer-filter-card .row>[class*=col-]{min-width:0}
.borrowers-ui .customer-filter-feedback{font-size:12px;color:#047857;margin:7px 4px;min-height:18px}
.borrowers-ui .customer-filter-card.is-loading{opacity:.8}
body>.dropdown-menu.show{display:block!important;visibility:visible!important;opacity:1!important;pointer-events:auto!important;min-width:220px;border-radius:16px!important}
body>.dropdown-menu.show .dropdown-item{white-space:normal}
.borrowers-ui .customer-location select option[disabled]{display:none}
</style>
<div class="borrowers-ui">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="h3 fw-bold mb-1">Borrowers &amp; KYC</h1><p class="text-muted mb-0">Manage borrowers, identity checks and account status</p></div><div class="d-flex flex-wrap gap-2">
<div class="dropdown"><button class="btn btn-outline-secondary dropdown-toggle" data-customer-menu-toggle="true">Export</button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="{{ route('customers.export', array_merge(['format'=>'pdf'], request()->only(['search','kyc','status','type']))) }}">Export PDF</a></li><li><a class="dropdown-item" href="{{ route('customers.export', array_merge(['format'=>'xlsx'], request()->only(['search','kyc','status','type']))) }}">Export Excel</a></li></ul></div>
@can('customer.create')<button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importCustomers">Import customers</button><button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#registerCustomer">+ Register customer</button>@endcan
</div></div>
@php
$borrowerTotal = max(1, (int) ($metrics['total'] ?? 0));
$customerKpis = [
 ['key'=>'total','title'=>'Total borrowers','icon'=>'mdi-account-group-outline','color'=>'#4f46e5','base'=>$borrowerTotal],
 ['key'=>'verified','title'=>'Verified KYC','icon'=>'mdi-shield-check-outline','color'=>'#059669','base'=>$borrowerTotal],
 ['key'=>'pending','title'=>'Pending KYC','icon'=>'mdi-timer-sand','color'=>'#d97706','base'=>$borrowerTotal],
 ['key'=>'active','title'=>'Active accounts','icon'=>'mdi-account-check-outline','color'=>'#0284c7','base'=>$borrowerTotal],
];
@endphp
<div class="row g-3 mb-4 customer-kpi-grid">
@foreach($customerKpis as $kpi)
@php $value=(int)($metrics[$kpi['key']] ?? 0); $percentage=min(100,max(0,round($value/$kpi['base']*100))); @endphp
<div class="col-sm-6 col-xl-3"><div class="card customer-kpi h-100" style="--kpi-color:{{ $kpi['color'] }};--kpi-progress:{{ $percentage }}%">
<div class="customer-kpi-top"><span class="customer-kpi-icon"><i class="mdi {{ $kpi['icon'] }}"></i></span><span class="customer-kpi-share">{{ $percentage }}%</span></div>
<div class="customer-kpi-main"><div><div class="customer-kpi-label">{{ $kpi['title'] }}</div><div class="customer-kpi-value">{{ number_format($value) }}</div><div class="customer-kpi-foot">{{ $kpi['key']==='total' ? 'Registered borrower records' : 'Share of all borrowers' }}</div></div><div class="customer-gauge" role="img" aria-label="{{ $percentage }} percent"><span>{{ $percentage }}<small>%</small></span></div></div>
</div></div>
@endforeach
</div>
<div class="card mb-3 customer-filter-card"><div class="card-body"><form id="customerFilters" method="GET" action="{{ route('customers.index') }}" class="row g-2 align-items-center"><div class="col-lg-5"><input class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Search name, phone, member number, NIDA..."></div><div class="col-sm-4 col-lg-2"><select name="kyc" class="form-select"><option value="">All KYC</option><option value="verified" @selected(request('kyc')==='verified')>Verified</option><option value="pending" @selected(request('kyc')==='pending')>Pending</option></select></div><div class="col-sm-4 col-lg-2"><select name="status" class="form-select"><option value="">All statuses</option><option value="active" @selected(request('status')==='active')>Active</option><option value="other" @selected(request('status')==='other')>Other</option></select></div><div class="col-sm-4 col-lg-2"><select name="type" class="form-select"><option value="">All types</option>@foreach($customerTypes as $type)<option value="{{ $type->code }}" @selected(request('type')===$type->code)>{{ $type->name }}</option>@endforeach</select></div><div class="col-lg-1 d-grid"><a class="btn btn-outline-secondary" href="{{ route('customers.index') }}" title="Reset filters">Reset</a></div></form></div></div>
<div class="card customer-results"><div class="table-responsive customer-listing"><table class="table table-hover mb-0"><thead><tr><th>Borrower</th><th>Member ID</th><th>Contact</th><th>National ID</th><th>Branch</th><th>Type</th><th>KYC</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($customers as $customer)
<tr><td><div class="d-flex align-items-center gap-2"><span class="avatar">{{ strtoupper(substr($customer->first_name,0,1)) }}</span><div><strong>{{ $customer->first_name }} {{ $customer->last_name }}</strong><div class="text-muted small">{{ $customer->assignedOfficer?->full_name ?? 'No officer' }}</div></div></div></td><td>{{ $customer->customer_number }}</td><td>{{ $customer->phone }}<div class="small text-muted">{{ $customer->email }}</div></td><td>{{ $customer->identification_number ?: '—' }}</td><td>{{ $customer->branch?->name ?? 'Unassigned' }}</td><td>{{ $customer->type }}</td><td><span class="badge {{ $customer->verified_at ? 'bg-success' : 'bg-warning text-dark' }}">{{ $customer->verified_at ? 'Verified' : 'Pending' }}</span></td><td><span class="badge bg-light text-dark">{{ $customer->status->value }}</span></td><td><div class="dropdown"><button class="btn btn-sm btn-outline-secondary" type="button" data-customer-menu-toggle="true" aria-expanded="false" aria-label="Borrower actions">&#8942;</button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="{{ route('customers.show',$customer) }}">Full profile &amp; KYC</a></li>@can('customer.verify')@if(!$customer->verified_at)<li><button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#kyc-{{ $customer->id }}">Review KYC</button></li>@endif @endcan<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#quick-{{ $customer->id }}">Quick view</button></li>@can('customer.document.create')<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#document-{{ $customer->id }}">Add KYC document</button></li>@endcan @can('customer.update')<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-{{ $customer->id }}">Edit borrower</button></li>@endcan @can('customer.update')<li><form method="POST" action="{{ route('customers.status',$customer) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $customer->status === \App\CustomerStatus::Active ? 'Suspended' : 'Active' }}"><button class="dropdown-item {{ $customer->status === \App\CustomerStatus::Active ? 'text-danger' : 'text-success' }}" @if($customer->status !== \App\CustomerStatus::Active && !$customer->verified_at) disabled title="Approve KYC first" @endif>{{ $customer->status === \App\CustomerStatus::Active ? 'Deactivate customer' : 'Activate customer' }}</button></form></li>@endcan
@can('customer.update')<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#password-{{ $customer->id }}">Reset portal password</button></li>@endcan @can('customer.delete')<li><hr class="dropdown-divider"></li><li><form method="POST" action="{{ route('customers.destroy',$customer) }}" onsubmit="return confirm('Delete this borrower? This is blocked when financial records exist.')">@csrf @method('DELETE')<button class="dropdown-item text-danger">Delete (if eligible)</button></form></li>@endcan</ul></div></td></tr>
@empty<tr><td colspan="9" class="text-center text-muted py-5">No borrowers match your filters.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $customers->links() }}</div></div>
<div id="customerRowModals">
@foreach($customers as $customer)
<div class="modal fade" id="quick-{{ $customer->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ $customer->first_name }} {{ $customer->last_name }}</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><p><span class="section-label">Member ID</span><br><strong>{{ $customer->customer_number }}</strong></p><p><span class="section-label">Phone</span><br>{{ $customer->phone }}</p><p><span class="section-label">Branch</span><br>{{ $customer->branch?->name ?? '—' }}</p><p><span class="section-label">KYC</span><br>{{ $customer->verified_at ? 'Verified' : 'Pending verification' }}</p></div><div class="modal-footer"><a class="btn btn-accent" href="{{ route('customers.show',$customer) }}">Open full profile</a></div></div></div></div>
@can('customer.verify')@if(!$customer->verified_at)
<div class="modal fade" id="kyc-{{ $customer->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">KYC decision · {{ $customer->customer_number }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><p class="text-muted small">Inspect identity, address, economic information and evidence on the full profile before approving. Approval is blocked by the server until all required checks pass.</p><a class="btn btn-outline-secondary btn-sm mb-3" href="{{ route('customers.show', $customer) }}">Review evidence on full profile</a><form method="POST" action="{{ route('customers.verify', $customer) }}">@csrf<label class="form-label" for="risk-{{ $customer->id }}">KYC risk rating</label><select id="risk-{{ $customer->id }}" class="form-select mb-2" name="kyc_risk_rating" required><option value="">Select rating</option><option>Low</option><option>Medium</option><option>High</option></select><button type="submit" class="btn btn-success w-100">Approve verified KYC</button></form><hr><form method="POST" action="{{ route('customers.kyc.reject', $customer) }}">@csrf<label class="form-label" for="reason-{{ $customer->id }}">Reason for rejection</label><textarea id="reason-{{ $customer->id }}" name="reason" class="form-control mb-2" rows="3" minlength="10" maxlength="2000" required placeholder="Explain what must be corrected"></textarea><button type="submit" class="btn btn-outline-danger w-100">Reject KYC</button></form></div></div></div></div>
@endif @endcan
@can('customer.document.create')<div class="modal fade" id="document-{{ $customer->id }}" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" enctype="multipart/form-data" action="{{ route('customers.documents.store',$customer) }}">@csrf<div class="modal-header"><h5 class="modal-title">Add KYC evidence</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Document type</label><select class="form-select mb-3" name="type" required><option value="">Select type</option>@foreach($documentTypes as $docType)<option value="{{ $docType->code }}">{{ $docType->name }}</option>@endforeach</select><label class="form-label">Document</label><input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div><div class="modal-footer"><button type="submit" class="btn btn-accent">Upload evidence</button></div></form></div></div></div>@endcan
@can('customer.update')<div class="modal fade" id="password-{{ $customer->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('customers.portal-password.reset',$customer) }}">@csrf<div class="modal-header"><h5 class="modal-title">Reset portal password</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><p class="text-muted small">Only active and verified customers can access the portal. A new temporary password will be sent to their registered contact.</p><input type="hidden" name="reset_mode" value="generate"><button type="submit" class="btn btn-accent">Generate and send temporary password</button></div></form></div></div></div>@endcan
@can('customer.update')<div class="modal fade" id="edit-{{ $customer->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content"><form method="POST" enctype="multipart/form-data" action="{{ route('customers.update',$customer) }}">@csrf @method('PUT')<div class="modal-header"><h5 class="modal-title">Edit borrower · {{ $customer->customer_number }}</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">@include('customers._modal_fields',['editingCustomer'=>$customer])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-accent">Save changes</button></div></form></div></div></div>@endcan
@endforeach
</div>
@can('customer.create')<div class="modal fade" id="registerCustomer" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content"><form method="POST" enctype="multipart/form-data" action="{{ route('customers.store') }}">@csrf<div class="modal-header"><h5 class="modal-title">Register new borrower</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">@include('customers._modal_fields',['editingCustomer'=>null])<div class="section-label mt-4 mb-3">KYC evidence — optional at registration</div>
<div class="alert alert-light border small">Attach up to five documents (PDF/JPG/PNG, maximum 10 MB each). Files are stored privately and remain <strong>unverified</strong> until an authorized officer reviews them.</div>
<div id="registrationDocuments"><div class="row g-2 mb-2 document-row"><div class="col-md-5"><select name="kyc_documents[0][type]" class="form-select"><option value="">Select document type</option>@foreach($documentTypes as $docType)<option value="{{ $docType->code }}" data-customer-type="{{ $docType->customer_type_code }}">{{ $docType->name }}</option>@endforeach</select></div><div class="col-md-7"><input type="file" name="kyc_documents[0][file]" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div></div></div>
<button type="button" class="btn btn-outline-secondary btn-sm" id="addRegistrationDocument">+ Add another document</button></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-accent">Register borrower</button></div></form></div></div></div>@endcan
@can('customer.create')
<div class="modal fade" id="importCustomers" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><div><h5 class="modal-title">Import customers</h5><div class="text-muted small">Upload, validate, preview and confirm without leaving this page</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body"><div id="customerImportContent">
<p class="text-muted">Import up to 2,000 borrowers per spreadsheet. Keep phone numbers as text to preserve leading zeroes.</p>
<a href="{{ route('customers.import.template') }}" class="btn btn-outline-success btn-sm mb-3">Download Excel template</a>
<form method="POST" action="{{ route('customers.import.store') }}" enctype="multipart/form-data" data-customer-import-form>@csrf
<label class="form-label" for="import-customer-file">Excel or CSV spreadsheet</label><input id="import-customer-file" type="file" name="file" class="form-control" accept=".xlsx,.csv" required>
<button type="submit" class="btn btn-accent mt-3">Validate and preview</button></form></div>
<div id="customerImportError" class="alert alert-danger mt-3 d-none" role="alert"></div></div>
<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
</div></div></div>
@endcan
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
 const container = document.getElementById('registrationDocuments');
 const add = document.getElementById('addRegistrationDocument');
 if (!container || !add) return;
 add.addEventListener('click', function () {
   const index = Number(container.dataset.nextIndex || '1');
   if (container.querySelectorAll('.document-row').length >= 5) return;
   const row = container.querySelector('.document-row').cloneNode(true);row.querySelectorAll('select').forEach(el=>{el.disabled=false;el.removeAttribute('data-select2-id');el.classList.remove('select2-hidden-accessible');});row.querySelectorAll('.select2-container,.choices').forEach(el=>el.remove());
   row.querySelectorAll('select,input').forEach(el => {
     el.name = el.name.replace(/\[\d+\]/, '[' + index + ']');el.removeAttribute('id');
     el.value = '';
   });
   const remove=document.createElement('button');remove.type='button';remove.className='btn btn-sm btn-outline-danger mt-2';remove.textContent='Remove document';remove.addEventListener('click',()=>{row.remove();add.disabled=false;});row.appendChild(remove);container.appendChild(row);container.dataset.nextIndex=String(index+1);add.disabled=container.querySelectorAll('.document-row').length>=5;
 });
});
</script>


<script>
/* PixelMkopo customer controls: initialized once, after DOM is ready. */
document.addEventListener('DOMContentLoaded', () => {
 const root=document.querySelector('.borrowers-ui'); if(!root)return;
 const form=document.getElementById('customerFilters');
 let active=null, owner=null, aborter=null, serial=0, timer=null;
 const closeMenu=()=>{if(active){active.classList.remove('show');active.style.display='none';if(owner){owner.appendChild(active);owner.querySelector('[data-customer-menu-toggle]')?.setAttribute('aria-expanded','false');}}active=null;owner=null;};
 function toggleMenu(btn){
   const wrapper=btn.closest('.dropdown'), menu=wrapper?.querySelector('.dropdown-menu'); if(!menu)return;
   if(active===menu){closeMenu();return;}closeMenu();owner=wrapper;active=menu;
   document.body.appendChild(menu);menu.style.cssText='display:block!important;position:fixed!important;z-index:2147483000!important;visibility:visible!important;';menu.classList.add('show');
   const r=btn.getBoundingClientRect(),w=menu.offsetWidth,h=menu.offsetHeight;
   menu.style.left=Math.max(8,Math.min(window.innerWidth-w-8,r.right-w))+'px';
   menu.style.top=(r.bottom+h+8>window.innerHeight?Math.max(8,r.top-h-6):r.bottom+5)+'px';btn.setAttribute('aria-expanded','true');
 }
 function showModal(modal){if(!modal)return;closeMenu();if(window.bootstrap?.Modal){window.bootstrap.Modal.getOrCreateInstance(modal).show();return;}modal.style.display='block';modal.classList.add('show');modal.setAttribute('aria-hidden','false');document.body.classList.add('modal-open');}
 function hideModal(modal){if(!modal)return;if(window.bootstrap?.Modal){window.bootstrap.Modal.getOrCreateInstance(modal).hide();return;}modal.classList.remove('show');modal.style.display='none';modal.setAttribute('aria-hidden','true');document.body.classList.remove('modal-open');}
 document.addEventListener('click',event=>{
   const button=event.target.closest('[data-customer-menu-toggle]');
   if(button&&root.contains(button)){event.preventDefault();event.stopImmediatePropagation();toggleMenu(button);return;}
   const modalButton=event.target.closest('[data-bs-toggle="modal"]');
   if(modalButton&&(root.contains(modalButton)||active?.contains(modalButton))){event.preventDefault();event.stopImmediatePropagation();const target=modalButton.getAttribute('data-bs-target');showModal(target?document.querySelector(target):null);return;}
   const dismiss=event.target.closest('[data-bs-dismiss="modal"]');if(dismiss&&root.contains(dismiss)){event.preventDefault();hideModal(dismiss.closest('.modal'));return;}
   if(active&&!active.contains(event.target))closeMenu();
 },true);
 document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeMenu();const modal=root.querySelector('.modal.show');if(modal)hideModal(modal);}});
 window.addEventListener('resize',closeMenu);
 document.addEventListener('scroll',e=>{if(active&&!active.contains(e.target))closeMenu();},true);
 const message=document.createElement('div');message.className='customer-filter-feedback';message.setAttribute('role','status');message.setAttribute('aria-live','polite');form?.after(message);
 async function refresh(urlOverride){
   if(!form)return;
   const url=urlOverride?new URL(urlOverride,location.href):new URL(form.action,location.href);
   if(!urlOverride){url.search='';new FormData(form).forEach((value,key)=>{if(String(value).trim())url.searchParams.set(key,String(value).trim());});}
   aborter?.abort();aborter=new AbortController();const id=++serial;
   message.textContent='Searching…';form.classList.add('is-loading');
   try{
     const res=await fetch(url.toString(),{credentials:'same-origin',signal:aborter.signal,headers:{'Accept':'text/html','X-Requested-With':'XMLHttpRequest'}});
     if(!res.ok)throw Error('Server returned '+res.status);
     const doc=new DOMParser().parseFromString(await res.text(),'text/html');
     const rows=doc.querySelector('.customer-results'),modals=doc.querySelector('#customerRowModals');
     if(!rows||!modals)throw Error('Unexpected response.');if(id!==serial)return;
     closeMenu();root.querySelector('.customer-results').replaceWith(rows);root.querySelector('#customerRowModals').replaceWith(modals);
     history.replaceState({},'',url.toString());message.textContent='Results updated';
   }catch(err){if(err.name==='AbortError')return;message.textContent='Unable to update results. Please retry.';console.error('Customer filters:',err);}
   finally{if(id===serial)form.classList.remove('is-loading');}
 }
 if(form){
   form.addEventListener('submit',e=>{e.preventDefault();refresh();});
   form.querySelectorAll('select').forEach(select=>select.addEventListener('change',()=>refresh()));
   form.querySelector('input[name="search"]')?.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(()=>refresh(),350);});
   const reset=form.querySelector('[title="Reset filters"]');reset?.addEventListener('click',e=>{e.preventDefault();form.reset();form.querySelectorAll('select').forEach(s=>{s.value='';});form.querySelector('input[name="search"]').value='';refresh();});
   root.addEventListener('click',e=>{const page=e.target.closest('.customer-results .pagination a');if(page){e.preventDefault();refresh(page.href);}});
 }
 function filterLocation(wrap){
   const names=['region','district','ward','street'];
   for(let i=1;i<names.length;i++){
     const select=wrap.querySelector('[data-location="'+names[i]+'"]'),parent=wrap.querySelector('[data-location="'+names[i-1]+'"]');if(!select||!parent)continue;
     [...select.options].forEach(opt=>{if(!opt.value)return;const allowed=String(opt.dataset.parent)===String(parent.value)&&!!parent.value;opt.hidden=!allowed;opt.disabled=!allowed;});
   }
 }
 root.querySelectorAll('.customer-location').forEach(filterLocation);
 root.addEventListener('change',e=>{const sel=e.target.closest('[data-location]');if(!sel)return;const wrap=sel.closest('.customer-location');if(!wrap)return;
   const keys=['region','district','ward','street'],i=keys.indexOf(sel.dataset.location);
   keys.slice(i+1).forEach(k=>{const child=wrap.querySelector('[data-location="'+k+'"]');if(child)child.value='';});filterLocation(wrap);
 });
 root.addEventListener('click',e=>{
   if(e.target.closest('[data-add-kin]')){const modal=e.target.closest('.modal'),list=modal?.querySelector('.customer-kin-list');if(!list||list.children.length>=5)return;const row=list.querySelector('.customer-kin-row')?.cloneNode(true);if(!row)return;const key=Date.now();row.querySelectorAll('input,select').forEach(el=>{el.value='';el.name=el.name.replace(/kin\[\d+\]/,'kin['+key+']');});list.appendChild(row);}
   if(e.target.closest('[data-remove-kin]')){const row=e.target.closest('.customer-kin-row'),list=row?.parentElement;if(list?.children.length>1)row.remove();else row?.querySelectorAll('input').forEach(i=>i.value='');}
 });
 @if($errors->any()) showModal(document.getElementById('registerCustomer')); @endif
});
</script>
@endsection
