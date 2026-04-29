@extends('layouts.app')

@section('title', 'Edit Renewal Contract Assignment')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Edit Renewal Contract Assignment</h1>
                    <p class="text-muted">Update renewal contract assignment</p>
                </div>
                <div>
                    <a href="{{ route('finance.renewal-contract-assignments.index') }}" class="btn btn-secondary">
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
                    <h6 class="m-0 font-weight-bold text-primary">Renewal Contract Assignment Information</h6>
                </div>
                <div class="card-body">
                    @if($renewalContractAssignment->is_locked)
                    <div class="alert alert-warning">
                        <i class="fas fa-lock"></i> This assignment is locked and cannot be edited. Please unlock it first.
                    </div>
                    @endif

                    <form method="POST" action="{{ route('finance.renewal-contract-assignments.update', $renewalContractAssignment) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Marketing User</label>
                                    <input type="text" class="form-control" value="{{ $renewalContractAssignment->user->name ?? 'N/A' }}" disabled>
                                    <small class="form-text text-muted">User cannot be changed</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Achievement Period</label>
                                    <input type="text" class="form-control" value="{{ $renewalContractAssignment->achievementPeriod->period_name ?? 'N/A' }}" disabled>
                                    <small class="form-text text-muted">Period cannot be changed</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contract_number_from" class="form-label">Contract Number From</label>
                                    <input type="text" class="form-control @error('contract_number_from') is-invalid @enderror" 
                                           id="contract_number_from" name="contract_number_from" 
                                           value="{{ old('contract_number_from', $renewalContractAssignment->contract_number_from) }}"
                                           {{ $renewalContractAssignment->is_locked ? 'disabled' : '' }}>
                                    @error('contract_number_from')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contract_number_to" class="form-label">Contract Number To</label>
                                    <input type="text" class="form-control @error('contract_number_to') is-invalid @enderror" 
                                           id="contract_number_to" name="contract_number_to" 
                                           value="{{ old('contract_number_to', $renewalContractAssignment->contract_number_to) }}"
                                           {{ $renewalContractAssignment->is_locked ? 'disabled' : '' }}>
                                    @error('contract_number_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="target_amount" class="form-label">Target Amount</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('target_amount') is-invalid @enderror" 
                                           id="target_amount" name="target_amount" 
                                           value="{{ old('target_amount', $renewalContractAssignment->target_amount) }}"
                                           {{ $renewalContractAssignment->is_locked ? 'disabled' : '' }}>
                                    @error('target_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="4" {{ $renewalContractAssignment->is_locked ? 'disabled' : '' }}>{{ old('notes', $renewalContractAssignment->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" {{ $renewalContractAssignment->is_locked ? 'disabled' : '' }}>
                                <i class="fas fa-save"></i> Update Assignment
                            </button>
                            <a href="{{ route('finance.renewal-contract-assignments.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

