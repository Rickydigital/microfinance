@extends('layouts.app')
@section('title', $repayment->receipt_number)
@section('content')
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 760px"><div class="card-body p-4">
        <div class="text-center border-bottom pb-3 mb-4"><h1 class="h4">Payment receipt</h1><strong>{{ $repayment->receipt_number }}</strong><div class="mt-2"><a class="btn btn-sm btn-outline-primary" href="{{ route('repayments.pdf', $repayment) }}">Download A4 PDF</a></div></div>
        <dl class="row"><dt class="col-4">Customer</dt><dd class="col-8">{{ $repayment->loan->customer->first_name }} {{ $repayment->loan->customer->last_name }}</dd><dt class="col-4">Loan</dt><dd class="col-8">{{ $repayment->loan->loan_number }}</dd><dt class="col-4">Amount</dt><dd class="col-8">{{ config('microfinance.currency') }} {{ number_format((float) $repayment->amount, 2) }}</dd><dt class="col-4">Received by</dt><dd class="col-8">{{ $repayment->receiver->full_name }}</dd></dl>
        <h2 class="h6 mt-4">Allocation</h2><table class="table"><thead><tr><th>Component</th><th class="text-end">Amount</th></tr></thead><tbody>@foreach ($repayment->allocations as $allocation)<tr><td>{{ ucfirst($allocation->component) }}</td><td class="text-end">{{ number_format((float) $allocation->amount, 2) }}</td></tr>@endforeach</tbody></table>
        @if ($repayment->status === \App\TransactionStatus::Posted->value)
            @can('repayment.reverse')<form class="border-top pt-4 mt-4" method="POST" action="{{ route('repayments.reverse', $repayment) }}">@csrf<label class="form-label" for="reason">Reversal reason</label><textarea class="form-control" id="reason" name="reason" rows="3" minlength="10" required></textarea><button class="btn btn-danger mt-3" type="submit">Reverse repayment</button></form>@endcan
        @endif
    </div></div>
@endsection
