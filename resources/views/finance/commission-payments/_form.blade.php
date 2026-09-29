@php($payment = $commissionPayment ?? null)
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="commission_calculation_id" class="form-label">Commission Calculation <span class="text-danger">*</span></label>
            <select class="form-control @error('commission_calculation_id') is-invalid @enderror"
                    id="commission_calculation_id" name="commission_calculation_id" required>
                <option value="">Select Calculation</option>
                @foreach($calculations as $calculation)
                    <option value="{{ $calculation->id }}"
                            data-user-id="{{ $calculation->user_id }}"
                            data-amount="{{ $calculation->final_amount }}"
                            {{ old('commission_calculation_id', $payment->commission_calculation_id ?? null) == $calculation->id ? 'selected' : '' }}>
                        #{{ $calculation->id }} - {{ $calculation->user->name ?? '-' }}
                        @if($calculation->achievementPeriod) ({{ $calculation->achievementPeriod->period_name }}) @endif
                        - Rp {{ number_format((float) $calculation->final_amount, 0, ',', '.') }}
                    </option>
                @endforeach
            </select>
            @if($calculations->isEmpty())
                <small class="text-muted">No approved commission calculations yet.</small>
            @endif
            @error('commission_calculation_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="user_id" class="form-label">Recipient <span class="text-danger">*</span></label>
            <select class="form-control @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                <option value="">Select User</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id', $payment->user_id ?? null) == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            @error('user_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" class="form-control @error('amount') is-invalid @enderror"
                   id="amount" name="amount" value="{{ old('amount', $payment->amount ?? null) }}" required>
            @error('amount')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
            <select class="form-control @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" required>
                @foreach(['bank_transfer' => 'Bank Transfer', 'cash' => 'Cash', 'check' => 'Check', 'other' => 'Other'] as $value => $label)
                    <option value="{{ $value }}" {{ old('payment_method', $payment->payment_method ?? 'bank_transfer') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('payment_method')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control @error('payment_date') is-invalid @enderror"
                   id="payment_date" name="payment_date"
                   value="{{ old('payment_date', optional($payment->payment_date ?? null)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
            @error('payment_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="payment_reference" class="form-label">Payment Reference</label>
            <input type="text" class="form-control @error('payment_reference') is-invalid @enderror"
                   id="payment_reference" name="payment_reference" value="{{ old('payment_reference', $payment->payment_reference ?? null) }}">
            @error('payment_reference')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="bank_name" class="form-label">Bank Name</label>
            <input type="text" class="form-control @error('bank_name') is-invalid @enderror"
                   id="bank_name" name="bank_name" value="{{ old('bank_name', $payment->bank_name ?? null) }}">
            @error('bank_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="bank_account" class="form-label">Bank Account</label>
            <input type="text" class="form-control @error('bank_account') is-invalid @enderror"
                   id="bank_account" name="bank_account" value="{{ old('bank_account', $payment->bank_account ?? null) }}">
            @error('bank_account')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="payment_notes" class="form-label">Notes</label>
    <textarea class="form-control @error('payment_notes') is-invalid @enderror"
              id="payment_notes" name="payment_notes" rows="3">{{ old('payment_notes', $payment->payment_notes ?? null) }}</textarea>
    @error('payment_notes')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Picking a calculation fills in its recipient and final amount.
    $('#commission_calculation_id').on('change', function() {
        const selected = $(this).find('option:selected');
        if (!selected.val()) return;
        $('#user_id').val(selected.data('user-id')).trigger('change');
        $('#amount').val(selected.data('amount'));
    });
});
</script>
@endpush
