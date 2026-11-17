<div class="card card-pad" id="payment-form" style="margin:22px 0;scroll-margin-top:24px;">
    <h2>{{ $paymentTitle }}</h2>
    <p>After making a payment, submit its details here. Pending payments do not reduce your balance.</p>
    <form method="POST" action="{{ $paymentAction }}" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-3">
            <label>Amount<input class="input" type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required></label>
            <label>Payment method<select class="input" name="payment_method" required>@foreach (['Bank', 'Mobile Money', 'Cash'] as $method)<option @selected(old('payment_method') === $method)>{{ $method }}</option>@endforeach</select></label>
            <label>Payment date<input class="input" type="date" name="paid_at" max="{{ today()->toDateString() }}" value="{{ old('paid_at', today()->toDateString()) }}" required></label>
            <label>Payment reference<input class="input" name="external_reference" maxlength="150" value="{{ old('external_reference') }}" required></label>
            <label>Receipt / evidence (optional; PDF, JPG or PNG; max 5 MB)<input class="input" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"></label>
        </div>
        <button class="btn btn-accent" style="margin-top:16px;">Submit payment for approval</button>
    </form>
</div>
