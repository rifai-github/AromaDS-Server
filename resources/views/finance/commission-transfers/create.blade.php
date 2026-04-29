@extends('layouts.app')

@section('title', 'Request Commission Transfer')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Request Commission Transfer</h1>
                    <p class="text-muted">Transfer commission to another marketing user</p>
                </div>
                <div>
                    <a href="{{ route('finance.commission-transfers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Commission Transfer Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('finance.commission-transfers.store') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="from_user_id" class="form-label">From User <span class="text-danger">*</span></label>
                                    <select class="form-control @error('from_user_id') is-invalid @enderror" 
                                            id="from_user_id" name="from_user_id" required>
                                        <option value="">Select User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('from_user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('from_user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="to_user_id" class="form-label">To User <span class="text-danger">*</span></label>
                                    <select class="form-control @error('to_user_id') is-invalid @enderror" 
                                            id="to_user_id" name="to_user_id" required>
                                        <option value="">Select User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('to_user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('to_user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contract_id" class="form-label">Contract <span class="text-danger">*</span></label>
                                    <select class="form-control @error('contract_id') is-invalid @enderror" 
                                            id="contract_id" name="contract_id" required>
                                        <option value="">Select Contract</option>
                                        @foreach($contracts as $contract)
                                            <option value="{{ $contract->id }}" {{ old('contract_id') == $contract->id ? 'selected' : '' }}>
                                                {{ $contract->contract_number }} - {{ $contract->customer->name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('contract_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="commission_calculation_id" class="form-label">Commission Calculation <span class="text-danger">*</span></label>
                                    <select class="form-control @error('commission_calculation_id') is-invalid @enderror" 
                                            id="commission_calculation_id" name="commission_calculation_id" required>
                                        <option value="">Select Commission Calculation</option>
                                    </select>
                                    @error('commission_calculation_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Select after choosing contract</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="commission_amount" class="form-label">Commission Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('commission_amount') is-invalid @enderror" 
                                           id="commission_amount" name="commission_amount" value="{{ old('commission_amount') }}" required>
                                    @error('commission_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('reason') is-invalid @enderror" 
                                      id="reason" name="reason" rows="4" required>{{ old('reason') }}</textarea>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Explain why commission should be transferred</small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Request Transfer
                            </button>
                            <a href="{{ route('finance.commission-transfers.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#from_user_id').on('change', function() {
        const userId = $(this).val();
        const contractSelect = $('#contract_id');
        const calculationSelect = $('#commission_calculation_id');
        
        // Reset children
        contractSelect.empty().append('<option value="">Loading...</option>');
        calculationSelect.empty().append('<option value="">Select Commission Calculation</option>');
        
        if (!userId) {
            contractSelect.empty().append('<option value="">Select Contract</option>');
            return;
        }
        
        // Fetch contracts
        $.ajax({
            url: `{{ url('finance/commission-transfers/contracts') }}/${userId}`,
            type: 'GET',
            success: function(response) {
                contractSelect.empty().append('<option value="">Select Contract</option>');
                
                if (response.status === 'success' && response.data.length > 0) {
                    response.data.forEach(function(contract) {
                        const customerName = contract.customer ? contract.customer.name : 'N/A';
                        contractSelect.append(`<option value="${contract.id}">${contract.contract_number} - ${customerName}</option>`);
                    });
                } else {
                    contractSelect.append('<option value="" disabled>No active contracts found for this user</option>');
                }
            },
            error: function() {
                contractSelect.empty().append('<option value="">Error loading contracts</option>');
                alert('Failed to load contracts');
            }
        });
    });

    $('#contract_id').on('change', function() {
        const contractId = $(this).val();
        const userId = $('#from_user_id').val();
        const calculationSelect = $('#commission_calculation_id');
        
        // Reset and show loading
        calculationSelect.empty().append('<option value="">Loading...</option>');
        
        if (!contractId || !userId) {
            calculationSelect.empty().append('<option value="">Select Commission Calculation</option>');
            return;
        }
        
        // Fetch calculations
        $.ajax({
            url: `{{ url('finance/commission-transfers/calculations') }}/${contractId}`,
            type: 'GET',
            data: { user_id: userId },
            success: function(response) {
                calculationSelect.empty().append('<option value="">Select Commission Calculation</option>');
                
                if (response.status === 'success' && response.data.length > 0) {
                    response.data.forEach(function(calc) {
                        const date = new Date(calc.calculation_date).toLocaleDateString();
                        const amount = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(calc.final_amount);
                        calculationSelect.append(`<option value="${calc.id}">${calc.calculation_type.toUpperCase()} - ${date} (${amount})</option>`);
                    });
                } else {
                    calculationSelect.append('<option value="" disabled>No calculations found for this contract and user</option>');
                }
            },
            error: function() {
                calculationSelect.empty().append('<option value="">Error loading data</option>');
                alert('Failed to load commission calculations');
            }
        });
    });

    $('#from_user_id, #to_user_id').on('change', function() {
        const fromId = $('#from_user_id').val();
        const toId = $('#to_user_id').val();
        
        if (fromId && toId && fromId === toId) {
            alert('From user and To user cannot be the same');
            $('#to_user_id').val('');
        }
    });

    // Trigger change if from_user_id is already selected (e.g., from old input)
    if ($('#from_user_id').val()) {
        $('#from_user_id').trigger('change');
    }
});
</script>
@endpush
@endsection

