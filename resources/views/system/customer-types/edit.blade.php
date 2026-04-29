@extends('layouts.app')

@section('title', 'Edit Customer Category')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Category: {{ $customerType->name }}
                        </h3>
                        <a href="{{ route('system.customer-types.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Back to List
                        </a>
                    </div>
                </div>
                <form action="{{ route('system.customer-types.update', $customerType) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">
                                        Category Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $customerType->name) }}" 
                                           placeholder="e.g., Hotel, Hospital, Restaurant"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active" class="form-label">Status</label>
                                    <select class="form-control @error('is_active') is-invalid @enderror" 
                                            id="is_active" 
                                            name="is_active">
                                        <option value="1" {{ old('is_active', $customerType->is_active) == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active', $customerType->is_active) == '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('is_active')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="3" 
                                              placeholder="Enter a description for this customer type">{{ old('description', $customerType->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('system.customer-types.index') }}" class="btn btn-secondary mr-2">
                                <i class="fas fa-times mr-1"></i>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>
                                Update Category
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
