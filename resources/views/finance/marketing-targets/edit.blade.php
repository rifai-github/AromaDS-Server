@extends('layouts.app')

@section('title', 'Edit Marketing Target')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Edit Marketing Target</h1>
                    <p class="text-muted">Update marketing target</p>
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
                    @if($marketingTarget->is_locked)
                    <div class="alert alert-warning">
                        <i class="fas fa-lock"></i> This target is locked and cannot be edited. Please unlock it first.
                    </div>
                    @endif

                    <form method="POST" action="{{ route('finance.marketing-targets.update', $marketingTarget) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Marketing User</label>
                                    <input type="text" class="form-control" value="{{ $marketingTarget->user->name ?? 'N/A' }}" disabled>
                                    <small class="form-text text-muted">User cannot be changed</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Achievement Period</label>
                                    <input type="text" class="form-control" value="{{ $marketingTarget->achievementPeriod->period_name ?? 'N/A' }}" disabled>
                                    <small class="form-text text-muted">Period cannot be changed</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Target Type</label>
                                    <input type="text" class="form-control" value="{{ ucfirst($marketingTarget->target_type) }}" disabled>
                                    <small class="form-text text-muted">Type cannot be changed</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="target_amount" class="form-label">Target Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('target_amount') is-invalid @enderror" 
                                           id="target_amount" name="target_amount" value="{{ old('target_amount', $marketingTarget->target_amount) }}" 
                                           {{ $marketingTarget->is_locked ? 'disabled' : 'required' }}>
                                    @error('target_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="4" {{ $marketingTarget->is_locked ? 'disabled' : '' }}>{{ old('notes', $marketingTarget->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" {{ $marketingTarget->is_locked ? 'disabled' : '' }}>
                                <i class="fas fa-save"></i> Update Marketing Target
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

