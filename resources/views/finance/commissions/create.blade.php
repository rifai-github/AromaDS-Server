@extends('layouts.app')

@section('title', 'Create Commission Calculation')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Create Commission Calculation</h1>
                    <p class="text-muted">Add new commission calculation</p>
                </div>
                <div>
                    <a href="{{ route('commissions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Commission Details</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('commissions.store') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="user_id">User <span class="text-danger">*</span></label>
                                    <select name="user_id" id="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                                        <option value="">Select User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="achievement_period_id">Achievement Period <span class="text-danger">*</span></label>
                                    <select name="achievement_period_id" id="achievement_period_id" class="form-control @error('achievement_period_id') is-invalid @enderror" required>
                                        <option value="">Select Period</option>
                                        @foreach($periods as $period)
                                            <option value="{{ $period->id }}" {{ old('achievement_period_id') == $period->id ? 'selected' : '' }}>
                                                {{ $period->period_name }} ({{ $period->start_date->format('d/M/Y') }} - {{ $period->end_date->format('d/M/Y') }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('achievement_period_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contract_id">Contract</label>
                                    <select name="contract_id" id="contract_id" class="form-control @error('contract_id') is-invalid @enderror">
                                        <option value="">Select Contract (Optional)</option>
                                        @foreach($contracts as $contract)
                                            <option value="{{ $contract->id }}" {{ old('contract_id') == $contract->id ? 'selected' : '' }}>
                                                {{ $contract->contract_number }} - {{ $contract->customer->company_name }}
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
                                    <label for="calculation_type">Calculation Type <span class="text-danger">*</span></label>
                                    <select name="calculation_type" id="calculation_type" class="form-control @error('calculation_type') is-invalid @enderror" required>
                                        <option value="">Select Type</option>
                                        <option value="automatic" {{ old('calculation_type') == 'automatic' ? 'selected' : '' }}>Automatic</option>
                                        <option value="manual" {{ old('calculation_type') == 'manual' ? 'selected' : '' }}>Manual</option>
                                        <option value="adjustment" {{ old('calculation_type') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                                    </select>
                                    @error('calculation_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="base_amount">Base Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" name="base_amount" id="base_amount" 
                                               class="form-control @error('base_amount') is-invalid @enderror" 
                                               value="{{ old('base_amount') }}" 
                                               step="0.01" min="0" required>
                                    </div>
                                    @error('base_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="commission_rate">Commission Rate (%) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="commission_rate" id="commission_rate" 
                                               class="form-control @error('commission_rate') is-invalid @enderror" 
                                               value="{{ old('commission_rate') }}" 
                                               step="0.01" min="0" max="100" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    @error('commission_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bonus_amount">Bonus Amount</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" name="bonus_amount" id="bonus_amount" 
                                               class="form-control @error('bonus_amount') is-invalid @enderror" 
                                               value="{{ old('bonus_amount', 0) }}" 
                                               step="0.01" min="0">
                                    </div>
                                    @error('bonus_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="penalty_amount">Penalty Amount</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" name="penalty_amount" id="penalty_amount" 
                                               class="form-control @error('penalty_amount') is-invalid @enderror" 
                                               value="{{ old('penalty_amount', 0) }}" 
                                               step="0.01" min="0">
                                    </div>
                                    @error('penalty_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="calculation_notes">Calculation Notes</label>
                            <textarea name="calculation_notes" id="calculation_notes" 
                                      class="form-control @error('calculation_notes') is-invalid @enderror" 
                                      rows="3" placeholder="Enter calculation notes...">{{ old('calculation_notes') }}</textarea>
                            @error('calculation_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Commission
                            </button>
                            <a href="{{ route('commissions.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preview Card -->
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Calculation Preview</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Base Amount</small>
                            <div id="previewBaseAmount">Rp 0</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Commission Rate</small>
                            <div id="previewCommissionRate">0%</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Commission Amount</small>
                            <div id="previewCommissionAmount">Rp 0</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Bonus Amount</small>
                            <div id="previewBonusAmount">Rp 0</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Penalty Amount</small>
                            <div id="previewPenaltyAmount">Rp 0</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Final Amount</small>
                            <div id="previewFinalAmount" class="font-weight-bold text-primary">Rp 0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Real-time calculation preview
    $('#base_amount, #commission_rate, #bonus_amount, #penalty_amount').on('input', function() {
        updatePreview();
    });
    
    // Initial preview update
    updatePreview();
});

function updatePreview() {
    const baseAmount = parseFloat($('#base_amount').val()) || 0;
    const commissionRate = parseFloat($('#commission_rate').val()) || 0;
    const bonusAmount = parseFloat($('#bonus_amount').val()) || 0;
    const penaltyAmount = parseFloat($('#penalty_amount').val()) || 0;
    
    const commissionAmount = baseAmount * (commissionRate / 100);
    const finalAmount = commissionAmount + bonusAmount - penaltyAmount;
    
    $('#previewBaseAmount').text('Rp ' + baseAmount.toLocaleString());
    $('#previewCommissionRate').text(commissionRate + '%');
    $('#previewCommissionAmount').text('Rp ' + commissionAmount.toLocaleString());
    $('#previewBonusAmount').text('Rp ' + bonusAmount.toLocaleString());
    $('#previewPenaltyAmount').text('Rp ' + penaltyAmount.toLocaleString());
    $('#previewFinalAmount').text('Rp ' + finalAmount.toLocaleString());
}
</script>
@endpush
