@extends('layouts.app')
@section('title', 'Import historical loans')
@section('content')
    @include('partials.alerts')
    <h1 class="h3">Import historical loans</h1>
    <p>Upload original loan schedules and amounts already paid. Customers and product versions must exist first.</p>
    <div class="card card-body mb-4">
        <p><a href="{{ route('loan-imports.template') }}">Download Excel template</a></p>
        <ul>
            <li>Use one row per installment, including fully paid installments. Repeat the same loan details on every row.</li>
            <li>Use customer number, product code and version from the system. Loan numbers must be new and unique.</li>
            <li>Enter original principal, actual net cash disbursed, and upfront income already earned (deducted fees plus separately collected non-refundable assurance). Enter 0 when no upfront income was earned.</li>
            <li>Use YYYY-MM-DD dates or Excel dates, and plain amounts without commas or formulas. Paid amounts cannot exceed their corresponding amounts due.</li>
            <li>Maximum 2,000 installment rows, one worksheet, XLSX or CSV, 5 MB.</li>
        </ul>
        <p class="text-muted">These are historical opening records, not new cash movements. Import updates loan balances and the lending dashboard without creating receipts or general-ledger entries. Historical expenses and owner capital must be recorded separately in accounting; do not post the imported loan income again.</p>
        <form method="POST" action="{{ route('loan-imports.store') }}" enctype="multipart/form-data">
            @csrf
            <label class="form-label">Completed spreadsheet</label>
            <input class="form-control mb-3" type="file" name="file" accept=".xlsx,.csv" required>
            <button class="btn btn-primary">Validate and preview</button>
        </form>
    </div>
    @isset($preview)
        <div class="card card-body">
            <h2 class="h5">Review {{ $preview->count() }} loans before importing</h2>
            <div class="table-responsive"><table class="table"><thead><tr><th>Loan</th><th>Customer</th><th>Original principal</th><th>Principal paid</th><th>Installments</th></tr></thead><tbody>
            @foreach ($preview as $number => $item)
                <tr><td>{{ $number }}</td><td>{{ $item['customer'] }}</td><td>{{ number_format((float) $item['principal'], 2) }}</td><td>{{ number_format($item['principal_paid'], 2) }}</td><td>{{ $item['installments'] }}</td></tr>
            @endforeach
            </tbody></table></div>
            <form method="POST" action="{{ route('loan-imports.store') }}">@csrf<input type="hidden" name="action" value="confirm"><input type="hidden" name="token" value="{{ $token }}"><button class="btn btn-success">Confirm import</button></form>
        </div>
    @endisset
@endsection
