@extends('layouts.app')

@section('title', 'Create Marketing Target')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Create Marketing Target</h1>
                    <p class="text-muted">Set a new marketing target</p>
                </div>
                <div>
                    <a href="{{ route('finance.marketing-targets.index') }}" class="btn btn-secondary">
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
                    <h6 class="m-0 font-weight-bold text-primary">Marketing Target Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('finance.marketing-targets.store') }}">
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
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="target_type" class="form-label">Target Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('target_type') is-invalid @enderror" 
                                            id="target_type" name="target_type" required>
                                        <option value="">Select Type</option>
                                        <option value="new" {{ old('target_type') == 'new' ? 'selected' : '' }}>New Contract</option>
                                        <option value="renewal" {{ old('target_type') == 'renewal' ? 'selected' : '' }}>Renewal Contract</option>
                                    </select>
                                    @error('target_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="target_amount" class="form-label">Target Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('target_amount') is-invalid @enderror" 
                                           id="target_amount" name="target_amount" value="{{ old('target_amount') }}" required>
                                    @error('target_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Target amount in Rupiah</small>
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
                                <i class="fas fa-save"></i> Create Marketing Target
                            </button>
                            <a href="{{ route('finance.marketing-targets.index') }}" class="btn btn-secondary">
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

