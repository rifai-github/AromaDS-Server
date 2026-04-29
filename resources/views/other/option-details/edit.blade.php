@extends('layouts.app')

@section('title', 'Edit Option Detail - ' . $optionDetail->option_name)
@section('breadcrumb', 'Home / Other / Master Options / ' . $optionDetail->masterOption->name . ' / Edit Detail')

@section('content')
<style>
    /* Form Styles */
    .form-container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .form-header {
        background: linear-gradient(135deg, #214589 0%, #1e3a8a 100%);
        color: white;
        padding: 24px;
        text-align: center;
    }

    .form-title {
        font-size: 20px;
        font-weight: 600;
        margin: 0 0 8px 0;
    }

    .form-subtitle {
        font-size: 14px;
        opacity: 0.9;
        margin: 0;
    }

    .form-body {
        padding: 24px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
        font-size: 14px;
    }

    .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-textarea {
        min-height: 100px;
        resize: vertical;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary {
        background-color: #214589;
        color: white;
    }

    .btn-primary:hover {
        background-color: #1e3a8a;
    }

    .btn-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
        background-color: #e5e7eb;
        color: #4b5563;
    }

    .error-message {
        color: #dc2626;
        font-size: 12px;
        margin-top: 4px;
    }

    .required {
        color: #dc2626;
    }
</style>

<div class="form-container">
    <div class="form-header">
        <h1 class="form-title">Edit Option Detail</h1>
        <p class="form-subtitle">Update {{ $optionDetail->option_name }} for {{ $optionDetail->masterOption->name }}</p>
    </div>

    <div class="form-body">
        <form action="{{ route('other.option-details.update', $optionDetail) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label for="option_name" class="form-label">
                    Option Name <span class="required">*</span>
                </label>
                <input type="text" 
                       id="option_name" 
                       name="option_name" 
                       class="form-input @error('option_name') border-red-500 @enderror"
                       value="{{ old('option_name', $optionDetail->option_name) }}" 
                       placeholder="Enter option name" 
                       required>
                @error('option_name')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="option_description" class="form-label">Description</label>
                <textarea id="option_description" 
                          name="option_description" 
                          class="form-input form-textarea @error('option_description') border-red-500 @enderror"
                          placeholder="Enter description (optional)">{{ old('option_description', $optionDetail->option_description) }}</textarea>
                @error('option_description')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="is_active" class="form-label">Status</label>
                <select id="is_active" 
                        name="is_active" 
                        class="form-input @error('is_active') border-red-500 @enderror">
                    <option value="1" {{ old('is_active', $optionDetail->is_active) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('is_active', $optionDetail->is_active) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('is_active')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-actions">
                <a href="{{ route('other.master-options.details', $optionDetail->masterOption) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Option Detail
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
