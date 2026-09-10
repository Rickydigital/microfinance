@can('customer.update')
    <div class="card card-body border-0 shadow-sm mt-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div><h2 class="h5 mb-1">Customer portal access</h2><p class="text-muted mb-0">Reset access without revealing the existing password.</p></div>
            <span class="badge {{ $customer->password ? 'bg-success' : 'bg-secondary' }}">{{ $customer->password ? 'Portal account configured' : 'No portal password' }}</span>
        </div>
        @if ($customer->status === \App\CustomerStatus::Active && $customer->verified_at)
            <form method="POST" action="{{ route('customers.portal-password.reset', $customer) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Reset method</label><select class="form-select" name="reset_mode" id="portal_reset_mode"><option value="generate">Generate and notify</option><option value="manual">Enter temporary password</option></select></div>
                    <div class="col-md-3"><label class="form-label">Temporary password</label><input class="form-control" type="password" name="password" autocomplete="new-password" placeholder="Required for manual mode"></div>
                    <div class="col-md-3"><label class="form-label">Confirm password</label><input class="form-control" type="password" name="password_confirmation" autocomplete="new-password"></div>
                    <div class="col-md-3 form-check align-self-end mb-2"><input class="form-check-input" id="notify_portal_password" type="checkbox" name="notify_customer" value="1"><label class="form-check-label" for="notify_portal_password">Notify customer for manual reset</label></div>
                </div>
                <p class="small text-muted mt-3">Generated passwords are sent through configured email/SMS channels. All temporary passwords must be changed immediately after login.</p>
                <button class="btn btn-primary">Reset portal password</button>
            </form>
        @else
            <div class="alert alert-warning mb-0">Complete staff KYC verification and activate this customer before creating or resetting portal access.</div>
        @endif
    </div>
@endcan
