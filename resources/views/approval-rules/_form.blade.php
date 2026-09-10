@php($rule = $approvalRule ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="loan_product_id">Loan product</label>
        <select class="form-select" id="loan_product_id" name="loan_product_id">
            <option value="">All loan products</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected((string) old('loan_product_id', $rule?->loan_product_id) === (string) $product->id)>
                    {{ $product->code }} — {{ $product->name }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Leave blank to make the rule a default for every product.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="branch_id">Branch</label>
        <select class="form-select" id="branch_id" name="branch_id">
            <option value="">All branches</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $rule?->branch_id) === (string) $branch->id)>
                    {{ $branch->code }} — {{ $branch->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="minimum_amount">Minimum amount</label>
        <input class="form-control" id="minimum_amount" name="minimum_amount" type="number" min="0" step="0.01" value="{{ old('minimum_amount', $rule?->minimum_amount ?? 0) }}" required>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="maximum_amount">Maximum amount</label>
        <input class="form-control" id="maximum_amount" name="maximum_amount" type="number" min="0" step="0.01" value="{{ old('maximum_amount', $rule?->maximum_amount) }}">
        <div class="form-text">Leave blank when there is no upper limit.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="risk_grade">Risk grade</label>
        <input class="form-control" id="risk_grade" name="risk_grade" value="{{ old('risk_grade', $rule?->risk_grade) }}" placeholder="Optional, e.g. A">
    </div>

    <div class="col-md-8">
        <label class="form-label" for="role_name">Required approver role</label>
        <select class="form-select" id="role_name" name="role_name" required>
            <option value="">Select a role</option>
            @foreach ($roles as $roleName)
                <option value="{{ $roleName }}" @selected(old('role_name', $rule?->role_name) === $roleName)>{{ $roleName }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="sequence">Approval level</label>
        <input class="form-control" id="sequence" name="sequence" type="number" min="1" max="100" value="{{ old('sequence', $rule?->sequence ?? 1) }}" required>
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input name="is_active" type="hidden" value="0">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $rule?->is_active ?? true))>
            <label class="form-check-label" for="is_active">Active and available to new applications</label>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button class="btn btn-primary" type="submit">Save approval rule</button>
    <a class="btn btn-outline-secondary" href="{{ route('approval-rules.index') }}">Cancel</a>
</div>
