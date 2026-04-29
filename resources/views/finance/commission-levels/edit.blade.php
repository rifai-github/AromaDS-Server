@extends('layouts.app')

@section('title', 'Edit Commission Level')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Edit Commission Level</h1>
                    <p class="text-muted">Update commission level configuration</p>
                </div>
                <div>
                    <a href="{{ route('finance.commission-levels.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Commission Level Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('finance.commission-levels.update', $commissionLevel) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Level Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $commissionLevel->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="target_type" class="form-label">Target Type <span class="text-danger">*</span></label>
                                    <select class="form-control @error('target_type') is-invalid @enderror" 
                                            id="target_type" name="target_type" required>
                                        <option value="">Select Type</option>
                                        <option value="new" {{ old('target_type', $commissionLevel->target_type) == 'new' ? 'selected' : '' }}>New Contract</option>
                                        <option value="renewal" {{ old('target_type', $commissionLevel->target_type) == 'renewal' ? 'selected' : '' }}>Renewal Contract</option>
                                        <option value="both" {{ old('target_type', $commissionLevel->target_type) == 'both' ? 'selected' : '' }}>Both</option>
                                    </select>
                                    @error('target_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="min_percentage" class="form-label">Min Achievement % <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control @error('min_percentage') is-invalid @enderror" 
                                           id="min_percentage" name="min_percentage" value="{{ old('min_percentage', $commissionLevel->min_percentage) }}" required>
                                    @error('min_percentage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_percentage" class="form-label">Max Achievement %</label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control @error('max_percentage') is-invalid @enderror" 
                                           id="max_percentage" name="max_percentage" value="{{ old('max_percentage', $commissionLevel->max_percentage) }}">
                                    @error('max_percentage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="commission_rate" class="form-label">Commission Rate % <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control @error('commission_rate') is-invalid @enderror" 
                                           id="commission_rate" name="commission_rate" value="{{ old('commission_rate', $commissionLevel->commission_rate) }}" required>
                                    @error('commission_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" 
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', $commissionLevel->sort_order) }}">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Lower number = higher priority</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active" class="form-label">Status</label>
                                    <select class="form-control @error('is_active') is-invalid @enderror" 
                                            id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', $commissionLevel->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active', $commissionLevel->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('is_active')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4">{{ old('description', $commissionLevel->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Commission Level
                            </button>
                            <a href="{{ route('finance.commission-levels.index') }}" class="btn btn-secondary">
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
    // Validate max_percentage >= min_percentage
    $('#min_percentage, #max_percentage').on('change', function() {
        const min = parseFloat($('#min_percentage').val()) || 0;
        const max = parseFloat($('#max_percentage').val());
        
        if (max && max < min) {
            alert('Max percentage must be greater than or equal to min percentage');
            $('#max_percentage').val('');
        }
    });
});
</script>
@endpush
@endsection

