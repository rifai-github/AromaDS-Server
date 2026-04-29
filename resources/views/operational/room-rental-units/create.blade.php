@extends('layouts.app')

@section('title', 'Create Room Rental Unit - Operational')
@section('breadcrumb', 'Home / Operational / Room Rental Unit / Create')

@section('content')
<div class="w-full">
    <!-- Header dengan judul dan button -->
    <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
        <div class="flex flex-row justify-start items-center w-full">
            <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Create Room Rental Unit</p>
        </div>
        
        <div class="flex flex-row gap-2">
            <a href="{{ route('operational.room-rental-units.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Back to List</span>
            </a>
        </div>
    </div>

    <!-- Content Container -->
    <div class="content-container w-full bg-white rounded-b-[10px] p-[7px] md:p-[10px] lg:p-[14px]">
        <form id="rentalUnitForm" method="POST" action="{{ route('operational.room-rental-units.store') }}" enctype="multipart/form-data">
            @csrf
            
            <!-- Basic Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Customer -->
                    <div class="form-group">
                        <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Customer <span class="text-red-500">*</span>
                        </label>
                        <select class="form-select @error('customer_id') border-red-500 @enderror" 
                                id="customer_id" 
                                name="customer_id" 
                                required>
                            <option value="">Select Customer</option>
                            @foreach($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Building -->
                    <div class="form-group">
                        <label for="building_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Building <span class="text-red-500">*</span>
                        </label>
                        <select class="form-select @error('building_id') border-red-500 @enderror" 
                                id="building_id" 
                                name="building_id" 
                                required>
                            <option value="">Select Building</option>
                            @foreach($buildings ?? [] as $building)
                                <option value="{{ $building->id }}" {{ old('building_id') == $building->id ? 'selected' : '' }}>
                                    {{ $building->building_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('building_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Room -->
                    <div class="form-group">
                        <label for="room_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Room <span class="text-red-500">*</span>
                        </label>
                        <select class="form-select @error('room_id') border-red-500 @enderror" 
                                id="room_id" 
                                name="room_id" 
                                required>
                            <option value="">Select Room</option>
                            @foreach($rooms ?? [] as $room)
                                <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                    {{ $room->room_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('room_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rental -->
                    <div class="form-group">
                        <label for="rental_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Rental <span class="text-red-500">*</span>
                        </label>
                        <select class="form-select @error('rental_id') border-red-500 @enderror" 
                                id="rental_id" 
                                name="rental_id" 
                                required>
                            <option value="">Select Rental</option>
                            @foreach($rentals ?? [] as $rental)
                                <option value="{{ $rental->id }}" {{ old('rental_id') == $rental->id ? 'selected' : '' }}>
                                    {{ $rental->rental_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('rental_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Reference Number -->
                    <div class="form-group">
                        <label for="reference_no" class="block text-sm font-medium text-gray-700 mb-2">
                            Reference Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               class="form-input @error('reference_no') border-red-500 @enderror" 
                               id="reference_no" 
                               name="reference_no" 
                               value="{{ old('reference_no') }}" 
                               required>
                        @error('reference_no')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Expected Install Date -->
                    <div class="form-group">
                        <label for="expected_install_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Expected Install Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               class="form-input @error('expected_install_date') border-red-500 @enderror" 
                               id="expected_install_date" 
                               name="expected_install_date" 
                               value="{{ old('expected_install_date') }}" 
                               required>
                        @error('expected_install_date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Install Date -->
                    <div class="form-group">
                        <label for="install_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Install Date
                        </label>
                        <input type="date" 
                               class="form-input @error('install_date') border-red-500 @enderror" 
                               id="install_date" 
                               name="install_date" 
                               value="{{ old('install_date') }}">
                        @error('install_date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Remove Date -->
                    <div class="form-group">
                        <label for="remove_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Remove Date
                        </label>
                        <input type="date" 
                               class="form-input @error('remove_date') border-red-500 @enderror" 
                               id="remove_date" 
                               name="remove_date" 
                               value="{{ old('remove_date') }}">
                        @error('remove_date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Last Service Date -->
                    <div class="form-group">
                        <label for="last_service_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Last Service Date
                        </label>
                        <input type="date" 
                               class="form-input @error('last_service_date') border-red-500 @enderror" 
                               id="last_service_date" 
                               name="last_service_date" 
                               value="{{ old('last_service_date') }}">
                        @error('last_service_date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- Notes -->
                    <div class="form-group">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Notes
                        </label>
                        <textarea class="form-textarea @error('notes') border-red-500 @enderror" 
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
                <a href="{{ route('operational.room-rental-units.index') }}" class="btn-secondary">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Create Rental Unit
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.content-container {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
    text-decoration: none;
    display: inline-block;
}

.btn-primary:hover {
    background-color: #1a365d;
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
}

.form-group {
    margin-bottom: 1rem;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #214589;
    box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
}

.form-input.border-red-500, .form-select.border-red-500, .form-textarea.border-red-500 {
    border-color: #ef4444;
}

.form-input.border-red-500:focus, .form-select.border-red-500:focus, .form-textarea.border-red-500:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('rentalUnitForm');
    
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

    // Dynamic room loading based on building selection
    const buildingSelect = document.getElementById('building_id');
    const roomSelect = document.getElementById('room_id');
    const customerSelect = document.getElementById('customer_id');
    
    // Load rooms when building changes
    buildingSelect.addEventListener('change', function() {
        const buildingId = this.value;
        
        // Clear room options
        roomSelect.innerHTML = '<option value="">Select Room</option>';
        
        if (buildingId) {
            // Fetch rooms for selected building
            fetch(`/api/buildings/${buildingId}/rooms`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(room => {
                        const option = document.createElement('option');
                        option.value = room.id;
                        option.textContent = room.room_name;
                        roomSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching rooms:', error);
                });
        }
    });

    // Load buildings when customer changes
    customerSelect.addEventListener('change', function() {
        const customerId = this.value;
        
        // Clear building options
        buildingSelect.innerHTML = '<option value="">Select Building</option>';
        
        if (customerId) {
            // Fetch buildings for selected customer
            fetch(`/api/customers/${customerId}/buildings`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(building => {
                        const option = document.createElement('option');
                        option.value = building.id;
                        option.textContent = building.name;
                        buildingSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching buildings:', error);
                });
        }
    });
});
</script>
@endsection
