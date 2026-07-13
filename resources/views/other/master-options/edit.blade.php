@extends('layouts.app')

@section('title', 'Edit Master Option')
@section('breadcrumb', 'Home / Company / Master Options / Edit')

@section('content')
<style>
    .form-container {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .form-section {
        margin-bottom: 30px;
    }

    .form-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #214589;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e5e7eb;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
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
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        min-height: 100px;
        resize: vertical;
        transition: border-color 0.2s ease;
    }

    .form-textarea:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .radio-group {
        display: flex;
        gap: 20px;
        margin-top: 8px;
    }

    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .radio-option input[type="radio"] {
        width: 16px;
        height: 16px;
        accent-color: #214589;
    }

    .radio-option label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
    }

    .btn-primary {
        background-color: #214589;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .btn-primary:hover {
        background-color: #1e3a8a;
    }

    .btn-secondary {
        background-color: #6b7280;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-secondary:hover {
        background-color: #4b5563;
        color: white;
        text-decoration: none;
    }

    .options-section {
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }

    .options-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 20px;
    }

    .add-option-btn {
        background-color: #10b981;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .add-option-btn:hover {
        background-color: #059669;
    }

    .option-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 15px;
        align-items: end;
        margin-bottom: 15px;
        padding: 15px;
        background-color: white;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
    }

    .remove-option-btn {
        background-color: #ef4444;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .remove-option-btn:hover {
        background-color: #dc2626;
    }

    .error-message {
        color: #dc2626;
        font-size: 12px;
        margin-top: 4px;
    }

    .required {
        color: #dc2626;
    }

    .existing-option {
        background-color: #f0f9ff;
        border-color: #0ea5e9;
    }
</style>

<div class="flex flex-col   w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">

        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
            <div class="flex flex-row justify-start items-center w-full">
                <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Edit Master Option</p>
            </div>
        </div>

        <!-- Form Container -->
        <div class="form-container w-full">
            <form action="{{ route('other.master-options.update', $masterOption->id) }}" method="POST" id="masterOptionForm">
                @csrf
                @method('PUT')

                <!-- Basic Info Section -->
                <div class="form-section">
                    <h3 class="form-section-title">Basic Info</h3>
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Nama <span class="required">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $masterOption->name) }}" 
                               class="form-input @error('name') border-red-500 @enderror" 
                               placeholder="Enter option name" required>
                        @error('name')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" 
                                  class="form-textarea @error('description') border-red-500 @enderror" 
                                  placeholder="Enter description">{{ old('description', $masterOption->description) }}</textarea>
                        @error('description')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">System Reserved</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="system_reserved_yes" name="system_reserved" value="1" 
                                       {{ old('system_reserved', $masterOption->system_reserved) == '1' ? 'checked' : '' }}>
                                <label for="system_reserved_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="system_reserved_no" name="system_reserved" value="0" 
                                       {{ old('system_reserved', $masterOption->system_reserved) == '0' ? 'checked' : '' }}>
                                <label for="system_reserved_no">No</label>
                            </div>
                        </div>
                        @error('system_reserved')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status Active</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="is_active_yes" name="is_active" value="1" 
                                       {{ old('is_active', $masterOption->is_active) == '1' ? 'checked' : '' }}>
                                <label for="is_active_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="is_active_no" name="is_active" value="0" 
                                       {{ old('is_active', $masterOption->is_active) == '0' ? 'checked' : '' }}>
                                <label for="is_active_no">No</label>
                            </div>
                        </div>
                        @error('is_active')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Options Section -->
                <div class="form-section">
                    <h3 class="form-section-title">Options</h3>
                    
                    <div class="options-section">
                        <div class="options-header">
                            <h4 class="text-[14px] font-medium text-[#374151]">Option Details</h4>
                            <button type="button" class="add-option-btn" onclick="addOptionRow()">
                                <i class="fas fa-plus mr-1"></i> Add Option
                            </button>
                        </div>

                        <div id="optionsContainer">
                            <!-- Existing options -->
                            @foreach($masterOption->optionDetails as $index => $optionDetail)
                                <div class="option-row existing-option" id="option-row-{{ $index }}">
                                    <div class="form-group">
                                        <label class="form-label">Option Name</label>
                                        <input type="text" name="options[{{ $index }}][option_name]" 
                                               value="{{ $optionDetail->option_name }}"
                                               class="form-input" placeholder="Enter option name" required>
                                        <input type="hidden" name="options[{{ $index }}][id]" value="{{ $optionDetail->id }}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Label</label>
                                        <input type="text" name="options[{{ $index }}][label]" 
                                               value="{{ $optionDetail->label }}"
                                               class="form-input" placeholder="Enter label" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Code</label>
                                        <input type="text" name="options[{{ $index }}][code]" 
                                               value="{{ $optionDetail->code }}"
                                               class="form-input" placeholder="Enter code">
                                    </div>
                                    <button type="button" class="remove-option-btn" onclick="removeOptionRow({{ $index }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-4 mt-6">
                    <a href="{{ route('other.master-options.index') }}" class="btn-secondary">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save mr-2"></i> Save Records
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let optionCounter = {{ $masterOption->optionDetails->count() }};

function addOptionRow() {
    const container = document.getElementById('optionsContainer');
    const row = document.createElement('div');
    row.className = 'option-row';
    row.id = `option-row-${optionCounter}`;
    
    row.innerHTML = `
        <div class="form-group">
            <label class="form-label">Option Name</label>
            <input type="text" name="options[${optionCounter}][option_name]" 
                   class="form-input" placeholder="Enter option name" required>
        </div>
        <div class="form-group">
            <label class="form-label">Label</label>
            <input type="text" name="options[${optionCounter}][label]" 
                   class="form-input" placeholder="Enter label" required>
        </div>
        <div class="form-group">
            <label class="form-label">Code</label>
            <input type="text" name="options[${optionCounter}][code]" 
                   class="form-input" placeholder="Enter code">
        </div>
        <button type="button" class="remove-option-btn" onclick="removeOptionRow(${optionCounter})">
            <i class="fas fa-trash"></i>
        </button>
    `;
    
    container.appendChild(row);
    optionCounter++;
}

function removeOptionRow(index) {
    const row = document.getElementById(`option-row-${index}`);
    if (row) {
        row.remove();
    }
}

// Form validation
document.getElementById('masterOptionForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const optionRows = document.querySelectorAll('.option-row');
    
    if (!name) {
        e.preventDefault();
        alert('Silakan isi nama master option.');
        return;
    }
    
    if (optionRows.length === 0) {
        e.preventDefault();
        alert('Silakan tambahkan minimal satu option.');
        return;
    }
    
    // Validate option rows
    let hasValidOptions = false;
    optionRows.forEach(row => {
        const optionName = row.querySelector('input[name*="[option_name]"]').value.trim();
        const label = row.querySelector('input[name*="[label]"]').value.trim();
        
        if (optionName && label) {
            hasValidOptions = true;
        }
    });
    
    if (!hasValidOptions) {
        e.preventDefault();
        alert('Silakan isi minimal satu option lengkap. Nama dan label wajib diisi.');
        return;
    }
});
</script>
@endsection
