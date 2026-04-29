@extends('layouts.app')

@section('title', 'Create Renewal Contract Assignment')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Create Renewal Contract Assignment</h1>
                    <p class="text-muted">Assign renewal contracts to marketing user</p>
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
                    <form method="POST" action="{{ route('finance.renewal-contract-assignments.store') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="user_id" class="form-label">Marketing User <span class="text-danger">*</span></label>
                                    <select class="form-control @error('user_id') is-invalid @enderror" 
                                            id="user_id" name="user_id" required>
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
                                    <label for="achievement_period_id" class="form-label">Achievement Period <span class="text-danger">*</span></label>
                                    <select class="form-control @error('achievement_period_id') is-invalid @enderror" 
                                            id="achievement_period_id" name="achievement_period_id" required>
                                        <option value="">Select Period</option>
                                        @foreach($periods as $period)
                                            <option value="{{ $period->id }}" {{ old('achievement_period_id') == $period->id ? 'selected' : '' }}>
                                                {{ $period->period_name }} ({{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }})
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contract_number_from" class="form-label">Contract Number From</label>
                                    <input type="text" class="form-control @error('contract_number_from') is-invalid @enderror" 
                                           id="contract_number_from" name="contract_number_from" value="{{ old('contract_number_from') }}">
                                    @error('contract_number_from')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Leave empty for all contracts</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="contract_number_to" class="form-label">Contract Number To</label>
                                    <input type="text" class="form-control @error('contract_number_to') is-invalid @enderror" 
                                           id="contract_number_to" name="contract_number_to" value="{{ old('contract_number_to') }}">
                                    @error('contract_number_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="target_amount" class="form-label">Target Amount</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('target_amount') is-invalid @enderror" 
                                           id="target_amount" name="target_amount" value="{{ old('target_amount') }}">
                                    @error('target_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="4">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Assignment
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

