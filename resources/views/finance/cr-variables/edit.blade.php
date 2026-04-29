@extends('layouts.app')

@section('title', 'Edit CR Variable')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Edit CR Variable</h1>
                    <p class="text-muted">Update Cash Receipt period variable</p>
                </div>
                <div>
                    <a href="{{ route('finance.cr-variables.index') }}" class="btn btn-secondary">
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
                    <h6 class="m-0 font-weight-bold text-primary">CR Variable Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('finance.cr-variables.update', $crVariable) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Variable Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $crVariable->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cr_days" class="form-label">CR Days <span class="text-danger">*</span></label>
                                    <input type="number" min="1" max="365" class="form-control @error('cr_days') is-invalid @enderror" 
                                           id="cr_days" name="cr_days" value="{{ old('cr_days', $crVariable->cr_days) }}" required>
                                    @error('cr_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_default" class="form-label">Set as Default</label>
                                    <select class="form-control @error('is_default') is-invalid @enderror" 
                                            id="is_default" name="is_default">
                                        <option value="0" {{ old('is_default', $crVariable->is_default) == 0 ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('is_default', $crVariable->is_default) == 1 ? 'selected' : '' }}>Yes</option>
                                    </select>
                                    @error('is_default')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active" class="form-label">Status</label>
                                    <select class="form-control @error('is_active') is-invalid @enderror" 
                                            id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', $crVariable->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active', $crVariable->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
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
                                      id="description" name="description" rows="4">{{ old('description', $crVariable->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update CR Variable
                            </button>
                            <a href="{{ route('finance.cr-variables.index') }}" class="btn btn-secondary" onclick="return confirm('Are you sure you want to cancel? Any unsaved changes will be lost.');">
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
// Prevent form submission on Enter key if user is not focused on submit button
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action*="cr-variables"]');
    if (form) {
        form.addEventListener('keydown', function(e) {
            // If Enter is pressed and focus is not on submit button, prevent default
            if (e.key === 'Enter' && e.target.tagName !== 'BUTTON' && e.target.type !== 'submit') {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>
@endpush
@endsection

