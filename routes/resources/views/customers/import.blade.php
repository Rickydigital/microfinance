@extends('layouts.app')

@section('title', 'Import Customers')

@section('content')
    @include('partials.alerts')

    <div class="page-heading">
        <div>
            <span class="eyebrow">Customer management</span>
            <h1>Import customers</h1>
            <p>Upload a spreadsheet, review validation results, then confirm the valid customers.</p>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('customers.index') }}">Back to customers</a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h5">Step 1 · Upload a file</h2>
            <p class="text-muted">Maximum 2,000 rows. Keep phone numbers as text to preserve leading zeroes.</p>
            <a class="btn btn-outline-success mb-3" href="{{ route('customers.import.template') }}">
                <i class="mdi mdi-file-excel-outline"></i> Download Excel template
            </a>
            <form action="{{ route('customers.import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <label for="customer-file" class="form-label">Customer spreadsheet (.xlsx or .csv)</label>
                <input id="customer-file" type="file" name="file" accept=".xlsx,.csv" required
                       class="form-control @error('file') is-invalid @enderror">
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <button class="btn btn-primary mt-3" type="submit">Validate and preview</button>
            </form>
        </div>
    </div>

    @isset($results)
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5">Step 2 · Review the import</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><div class="alert alert-info mb-0">Total: {{ count($results['valid']) + count($results['failed']) }}</div></div>
                    <div class="col-md-4"><div class="alert alert-success mb-0">Valid: {{ count($results['valid']) }}</div></div>
                    <div class="col-md-4"><div class="alert alert-warning mb-0">Failed: {{ count($results['failed']) }}</div></div>
                </div>

                @if (count($results['failed']))
                    <h3 class="h6">Rows requiring correction</h3>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead><tr><th>Row</th><th>Full name</th><th>Phone</th><th>Issue</th></tr></thead>
                            <tbody>
                                @foreach (array_slice($results['failed'], 0, 50) as $row)
                                    <tr>
                                        <td>{{ $row['_row'] }}</td>
                                        <td>{{ $row['full_name'] }}</td>
                                        <td>{{ $row['phone'] }}</td>
                                        <td class="text-danger">{{ $row['errors'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a class="btn btn-outline-danger mb-3" href="{{ route('customers.import.errors') }}">
                        <i class="mdi mdi-download"></i> Download all failed rows (CSV)
                    </a>
                @endif

                @if (count($results['valid']))
                    <h3 class="h6">Valid customers (first 20)</h3>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Row</th><th>First</th><th>Middle</th><th>Last</th><th>Phone</th><th>Gender</th></tr></thead>
                            <tbody>
                                @foreach (array_slice($results['valid'], 0, 20) as $row)
                                    <tr>
                                        <td>{{ $row['_row'] }}</td>
                                        <td>{{ $row['first_name'] }}</td>
                                        <td>{{ $row['middle_name'] ?: '—' }}</td>
                                        <td>{{ $row['last_name'] }}</td>
                                        <td>{{ $row['phone'] }}</td>
                                        <td>{{ $row['gender'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <form method="POST" action="{{ route('customers.import.store') }}">
                        @csrf
                        <input type="hidden" name="action" value="confirm">
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button class="btn btn-success" type="submit" onclick="return confirm('Import the validated customers?')">
                            Confirm import of {{ count($results['valid']) }} customers
                        </button>
                    </form>
                @else
                    <p class="text-muted">No valid rows. Correct the spreadsheet and upload again.</p>
                @endif
            </div>
        </div>
    @endisset
@endsection
