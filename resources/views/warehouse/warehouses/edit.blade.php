@extends('layouts.app')

@section('title', 'Edit Warehouse')
@section('breadcrumb', 'Home / Warehouse / Master Warehouse / Edit')

@section('content')
<style>
    /* Global styles */
    .btn {
        padding: 8px 16px;
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
        color: white;
    }

    .btn-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
        border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
        background-color: #214589;
        color: white;
        border-color: #214589;
    }

    /* Card */
    .info-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }

    .form-label.required::after {
        content: ' *';
        color: #ef4444;
    }

    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
        box-sizing: border-box;
    }

    .form-textarea {
        min-height: 100px;
        resize: vertical;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-input:disabled,
    .form-select:disabled {
        background-color: #f3f4f6;
        cursor: not-allowed;
    }

    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }

    /* Badge */
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .badge-info {
        background-color: #dbeafe;
        color: #1e40af;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header Section -->
        <div class="info-card" style="width: 100%;">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#214589]">
                        Edit Warehouse: {{ $warehouse->warehouse_code }} - {{ $warehouse->name }}
                    </h1>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('warehouse.warehouses.show', $warehouse->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <!-- Form -->
            <form id="editWarehouseForm" method="POST" action="{{ route('warehouse.warehouses.update', $warehouse->id) }}">
                @csrf
                @method('PUT')

                <div class="info-grid">
                    <!-- Warehouse Code -->
                    <div class="form-group">
                        <label class="form-label required">Warehouse Code</label>
                        <input type="text" 
                               name="warehouse_code" 
                               class="form-input" 
                               value="{{ old('warehouse_code', $warehouse->warehouse_code) }}" 
                               placeholder="Enter warehouse code">
                        @error('warehouse_code')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Warehouse Name -->
                    <div class="form-group">
                        <label class="form-label required">Warehouse Name</label>
                        <input type="text" 
                               name="name" 
                               class="form-input" 
                               value="{{ old('name', $warehouse->name) }}" 
                               placeholder="Enter warehouse name" 
                               required>
                        @error('name')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Branch -->
                    <div class="form-group">
                        <label class="form-label required">Branch</label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" 
                                        {{ old('branch_id', $warehouse->branch_id) == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name ?? $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Type (Center/Branch) -->
                    <div class="form-group">
                        <label class="form-label required">Type</label>
                        @php
                            $hasCenterWarehouse = \App\Models\Warehouse::where('is_center', true)->where('id', '!=', $warehouse->id)->exists();
                        @endphp
                        @if($hasCenterWarehouse && !$warehouse->is_center)
                            <div class="p-3 bg-gray-100 rounded-lg text-gray-600 text-sm mb-2">
                                Center warehouse already exists
                            </div>
                            <input type="hidden" name="is_center" value="0">
                            <input type="text" class="form-input" value="Branch Warehouse" disabled>
                        @else
                            <select name="is_center" class="form-select" required>
                                <option value="0" {{ old('is_center', $warehouse->is_center) == 0 ? 'selected' : '' }}>
                                    Branch Warehouse
                                </option>
                                <option value="1" {{ old('is_center', $warehouse->is_center) == 1 ? 'selected' : '' }}>
                                    Center Warehouse
                                </option>
                            </select>
                        @endif
                        @error('is_center')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label class="form-label required">Status</label>
                        <select name="is_active" class="form-select" required>
                            <option value="1" {{ old('is_active', $warehouse->is_active) == 1 ? 'selected' : '' }}>
                                Active
                            </option>
                            <option value="0" {{ old('is_active', $warehouse->is_active) == 0 ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>
                        @error('is_active')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Manager -->
                    <div class="form-group">
                        <label class="form-label">Manager</label>
                        <select name="manager" class="form-select">
                            <option value="">Select Manager</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" 
                                        {{ old('manager', $warehouse->manager) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('manager')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Warehouse Admins -->
                    <div class="form-group" style="grid-column: span 1;">
                        <label class="form-label">Warehouse Admins</label>
                        <select name="admins[]" id="admins_select" class="form-select" multiple>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" 
                                        {{ (collect(old('admins', $warehouse->admins->pluck('id')))->contains($user->id)) ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-gray-500">Admins have the same data access as the Manager.</small>
                        @error('admins')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" 
                               name="phone" 
                               class="form-input" 
                               value="{{ old('phone', $warehouse->phone) }}" 
                               placeholder="Enter phone number">
                        @error('phone')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Address (Full Width) -->
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" 
                              class="form-textarea" 
                              placeholder="Enter warehouse address">{{ old('address', $warehouse->address) }}</textarea>
                    @error('address')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-2 mt-6">
                    <a href="{{ route('warehouse.warehouses.show', $warehouse->id) }}" class="btn btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Warehouse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#admins_select').select2({
        placeholder: "Select Admins",
        allowClear: true,
        width: '100%'
    });
});

document.getElementById('editWarehouseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Disable button and show loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Redirect to show page
            window.location.href = '{{ route("warehouse.warehouses.show", $warehouse->id) }}';
        } else {
            // Show errors
            if (data.errors) {
                let errorMessage = 'Validation errors:\n';
                Object.keys(data.errors).forEach(key => {
                    errorMessage += data.errors[key][0] + '\n';
                });
                alert(errorMessage);
            } else {
                alert(data.message || 'Error updating warehouse');
            }
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the warehouse');
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
});
</script>
@endsection

