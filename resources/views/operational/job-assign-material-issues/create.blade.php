@extends('layouts.app')

@section('title', 'Create Job Assign Material Issue - Operational')
@section('breadcrumb', 'Home / Operational / Job Assign Material Issue / Create')

@section('content')
<div class="w-full">
    <!-- Module Header -->
    <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
        <div class="flex flex-row justify-start items-center w-full">
            <h1 class="text-xl font-semibold text-[#214589]">Create Job Assign Material Issue</h1>
        </div>
        
        <div class="flex flex-row justify-end items-center">
            <a href="{{ route('operational.job-assign-material-issues.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                <span class="hidden md:inline">Back to List</span>
                <span class="md:hidden">Back</span>
            </a>
        </div>
    </div>

    <!-- Content Container -->
    <div class="w-full bg-white rounded-b-[10px] p-6">
        <form id="materialIssueForm" method="POST" action="{{ route('operational.job-assign-material-issues.store') }}" enctype="multipart/form-data">
            @csrf
            
            <!-- Basic Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Job Assign Schedule -->
                    <div class="form-group">
                        <label for="job_assign_schedule_id" class="form-label">
                            Job Assign Schedule <span class="text-red-500">*</span>
                        </label>
                        <select class="form-input @error('job_assign_schedule_id') border-red-500 @enderror" 
                                id="job_assign_schedule_id" 
                                name="job_assign_schedule_id" 
                                required>
                            <option value="">Select Job Assign Schedule</option>
                            @foreach($jobAssignSchedules ?? [] as $schedule)
                                <option value="{{ $schedule->id }}" {{ old('job_assign_schedule_id') == $schedule->id ? 'selected' : '' }}>
                                    {{ $schedule->jobSchedule->job_number ?? 'No Job Number' }} - {{ $schedule->jobSchedule->customer->name ?? 'No Customer' }}
                                </option>
                            @endforeach
                        </select>
                        @error('job_assign_schedule_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Team -->
                    <div class="form-group">
                        <label for="team_id" class="form-label">
                            Team <span class="text-red-500">*</span>
                        </label>
                        <select class="form-input @error('team_id') border-red-500 @enderror" 
                                id="team_id" 
                                name="team_id" 
                                required>
                            <option value="">Select Team</option>
                            @foreach($teams ?? [] as $team)
                                <option value="{{ $team->id }}" {{ old('team_id') == $team->id ? 'selected' : '' }}>
                                    {{ $team->team_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('team_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Product -->
                    <div class="form-group">
                        <label for="product_id" class="form-label">
                            Product <span class="text-red-500">*</span>
                        </label>
                        <select class="form-input @error('product_id') border-red-500 @enderror" 
                                id="product_id" 
                                name="product_id" 
                                required>
                            <option value="">Select Product</option>
                            @foreach($products ?? [] as $product)
                                <option value="{{ $product->id }}" 
                                        data-price="{{ $product->last_unit_price ?? 0 }}"
                                        data-stock="{{ $product->stock ?? 0 }}"
                                        {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} (Stock: {{ $product->stock ?? 0 }})
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Warehouse -->
                    <div class="form-group">
                        <label for="warehouse_id" class="form-label">
                            Gudang Cabang
                        </label>
                        <select class="form-input @error('warehouse_id') border-red-500 @enderror" 
                                id="warehouse_id" 
                                name="warehouse_id"
                                hidden>
                            <option value="">Select Warehouse</option>
                            @foreach($warehouses ?? [] as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-input bg-gray-100 text-gray-700">Otomatis mengikuti warehouse aktif branch job</div>
                        @error('warehouse_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Issue Date -->
                    <div class="form-group">
                        <label for="issue_date" class="form-label">
                            Issue Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               class="form-input @error('issue_date') border-red-500 @enderror" 
                               id="issue_date" 
                               name="issue_date" 
                               value="{{ old('issue_date', date('Y-m-d')) }}" 
                               required>
                        @error('issue_date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Quantity -->
                    <div class="form-group">
                        <label for="quantity" class="form-label">
                            Quantity <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               class="form-input @error('quantity') border-red-500 @enderror" 
                               id="quantity" 
                               name="quantity" 
                               value="{{ old('quantity') }}" 
                               min="1" 
                               required>
                        @error('quantity')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unit Price -->
                    <div class="form-group">
                        <label for="unit_price" class="form-label">
                            Unit Price (IDR)
                        </label>
                        <input type="number" 
                               class="form-input @error('unit_price') border-red-500 @enderror" 
                               id="unit_price" 
                               name="unit_price" 
                               value="{{ old('unit_price') }}" 
                               min="0" 
                               step="1000"
                               readonly>
                        @error('unit_price')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Total Amount -->
                    <div class="form-group">
                        <label for="total_amount" class="form-label">
                            Total Amount (IDR)
                        </label>
                        <input type="number" 
                               class="form-input @error('total_amount') border-red-500 @enderror" 
                               id="total_amount" 
                               name="total_amount" 
                               value="{{ old('total_amount') }}" 
                               min="0" 
                               step="1000"
                               readonly>
                        @error('total_amount')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Request Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Requested By -->
                    <div class="form-group">
                        <label for="requested_by" class="form-label">
                            Requested By <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               class="form-input @error('requested_by') border-red-500 @enderror" 
                               id="requested_by" 
                               name="requested_by" 
                               value="{{ old('requested_by', auth()->user()->name ?? '') }}" 
                               required>
                        @error('requested_by')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Request Reason -->
                    <div class="form-group">
                        <label for="request_reason" class="form-label">
                            Request Reason <span class="text-red-500">*</span>
                        </label>
                        <select class="form-input @error('request_reason') border-red-500 @enderror" 
                                id="request_reason" 
                                name="request_reason" 
                                required>
                            <option value="">Select Reason</option>
                            <option value="installation" {{ old('request_reason') == 'installation' ? 'selected' : '' }}>Installation</option>
                            <option value="maintenance" {{ old('request_reason') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="repair" {{ old('request_reason') == 'repair' ? 'selected' : '' }}>Repair</option>
                            <option value="replacement" {{ old('request_reason') == 'replacement' ? 'selected' : '' }}>Replacement</option>
                            <option value="emergency" {{ old('request_reason') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                            <option value="other" {{ old('request_reason') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('request_reason')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label for="status" class="form-label">
                            Status
                        </label>
                        <select class="form-input @error('status') border-red-500 @enderror" 
                                id="status" 
                                name="status">
                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="issued" {{ old('status') == 'issued' ? 'selected' : '' }}>Issued</option>
                            <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        @error('status')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Priority -->
                    <div class="form-group">
                        <label for="priority" class="form-label">
                            Priority
                        </label>
                        <select class="form-input @error('priority') border-red-500 @enderror" 
                                id="priority" 
                                name="priority">
                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                        @error('priority')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- Description -->
                    <div class="form-group">
                        <label for="description" class="form-label">
                            Description
                        </label>
                        <textarea class="form-input form-textarea @error('description') border-red-500 @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="4">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label for="notes" class="form-label">
                            Notes
                        </label>
                        <textarea class="form-input form-textarea @error('notes') border-red-500 @enderror" 
                                  id="notes" 
                                  name="notes" 
                                  rows="4">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-row justify-end items-center w-full gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('operational.job-assign-material-issues.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Create Material Issue
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Button Styles */
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

.form-input, .form-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    background-color: white;
    box-sizing: border-box;
}

.form-input:focus, .form-textarea:focus {
    outline: none;
    border-color: #214589;
    box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
}

.form-textarea {
    min-height: 100px;
    resize: vertical;
}

.form-input.border-red-500, .form-textarea.border-red-500 {
    border-color: #ef4444;
}

.form-input.border-red-500:focus, .form-textarea.border-red-500:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* Grid Layout */
.grid {
    display: grid;
}

.grid-cols-1 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

.md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.gap-6 {
    gap: 1.5rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .md\:grid-cols-2 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
    
    .flex.flex-row.justify-between {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .flex.flex-row.justify-between > div:first-child {
        width: 100%;
    }
    
    .flex.flex-row.justify-between > div:last-child {
        width: 100%;
        justify-content: flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('materialIssueForm');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-500');
                isValid = false;
            } else {
                field.classList.remove('border-red-500');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Real-time validation
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('border-red-500');
            } else {
                this.classList.remove('border-red-500');
            }
        });
        
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('border-red-500');
            }
        });
    });

    // Auto-calculate total amount
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unit_price');
    const totalAmountInput = document.getElementById('total_amount');
    
    function calculateTotal() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const unitPrice = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        const quantity = parseFloat(quantityInput.value) || 0;
        
        unitPriceInput.value = unitPrice;
        totalAmountInput.value = (unitPrice * quantity).toFixed(0);
    }
    
    productSelect.addEventListener('change', calculateTotal);
    quantityInput.addEventListener('input', calculateTotal);
});
</script>
@endsection
