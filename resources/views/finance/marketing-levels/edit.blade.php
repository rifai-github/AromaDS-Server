@extends('layouts.app')

@section('title', 'Edit Marketing Level')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Edit Marketing Level</h1>
                    <p class="text-muted">Update marketing level</p>
                </div>
                <div>
                    <a href="{{ route('finance.marketing-levels.index') }}" class="btn btn-secondary">
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
                    <h6 class="m-0 font-weight-bold text-primary">Marketing Level Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('finance.marketing-levels.update', $marketingLevel) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="level_code" class="form-label">Level Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('level_code') is-invalid @enderror" 
                                           id="level_code" name="level_code" value="{{ old('level_code', $marketingLevel->level_code) }}" required>
                                    @error('level_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="level_name" class="form-label">Level Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('level_name') is-invalid @enderror" 
                                           id="level_name" name="level_name" value="{{ old('level_name', $marketingLevel->level_name) }}" required>
                                    @error('level_name')
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
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', $marketingLevel->sort_order) }}">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active" class="form-label">Status</label>
                                    <select class="form-control @error('is_active') is-invalid @enderror" 
                                            id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', $marketingLevel->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active', $marketingLevel->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
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
                                      id="description" name="description" rows="4">{{ old('description', $marketingLevel->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Marketing Level
                            </button>
                            <a href="{{ route('finance.marketing-levels.index') }}" class="btn btn-secondary">
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

