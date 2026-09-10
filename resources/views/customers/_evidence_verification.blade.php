<div class="card card-body border-0 shadow-sm mt-4">
    <h2 class="h5">Identification and address verification</h2>
    <p class="text-muted">
        Every entry remains pending until an authorized officer reviews the evidence and records verification.
    </p>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Evidence</th>
                    <th>Details</th>
                    <th>Status</th>
                    <th>Verified by</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customer->identifications as $identification)
                    <tr>
                        <td>Identification</td>
                        <td>
                            {{ $identification->type }} — {{ $identification->number }}
                            <br>
                            <small>Expires: {{ $identification->expires_at?->format('d M Y') ?? 'No expiry' }}</small>
                        </td>
                        <td>
                            @if ($identification->verified_at)
                                <span class="badge bg-success">Verified</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending verification</span>
                            @endif
                        </td>
                        <td>
                            {{ $identification->verifier?->full_name ?? '—' }}
                            @if ($identification->verified_at)
                                <br>
                                <small>{{ $identification->verified_at->format('d M Y H:i') }}</small>
                            @endif
                        </td>
                        <td>
                            @if (! $identification->verified_at)
                                @can('customer.verify')
                                    <form method="POST" action="{{ route('customers.identifications.verify', [$customer, $identification]) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success" type="submit">Verify identification</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No identification has been added.</td>
                    </tr>
                @endforelse

                @forelse ($customer->addresses as $address)
                    <tr>
                        <td>{{ $address->is_primary ? 'Primary address' : 'Address' }}</td>
                        <td>
                            {{ $address->type }} —
                            {{ collect([$address->region, $address->district, $address->ward, $address->street])->filter()->implode(', ') }}
                        </td>
                        <td>
                            @if ($address->verified_at)
                                <span class="badge bg-success">Verified</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending verification</span>
                            @endif
                        </td>
                        <td>
                            {{ $address->verifier?->full_name ?? '—' }}
                            @if ($address->verified_at)
                                <br>
                                <small>{{ $address->verified_at->format('d M Y H:i') }}</small>
                            @endif
                        </td>
                        <td>
                            @if (! $address->verified_at)
                                @can('customer.verify')
                                    <form method="POST" action="{{ route('customers.addresses.verify', [$customer, $address]) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success" type="submit">Verify address</button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No address has been added.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
