@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add New Position</h3>
                    <div class="card-tools">
                        <a href="{{ route('system.positions.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('system.positions.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="option_name">Position <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('option_name') is-invalid @enderror" 
                                   id="option_name" name="option_name" value="{{ old('option_name') }}" required>
                            @error('option_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="option_description">Description</label>
                            <textarea class="form-control @error('option_description') is-invalid @enderror" 
                                      id="option_description" name="option_description" rows="3">{{ old('option_description') }}</textarea>
                            @error('option_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Position
                            </button>
                            <a href="{{ route('system.positions.index') }}" class="btn btn-secondary">
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
