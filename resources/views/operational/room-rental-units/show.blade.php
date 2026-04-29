@extends('layouts.app')

@section('title', 'View Room Rental Unit - Operational')
@section('breadcrumb', 'Home / Operational / Room Rental Unit / View')

@section('content')
<div class="w-full">
    <!-- Header dengan judul dan button -->
    <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
        <div class="flex flex-row justify-start items-center w-full">
            <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">View Room Rental Unit</p>
        </div>
        
        <div class="flex flex-row gap-2">
            <a href="{{ route('operational.room-rental-units.edit', $rentalUnit->id) }}" class="btn-primary">
                <i class="fas fa-edit text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Edit Rental Unit</span>
            </a>
            <a href="{{ route('operational.room-rental-units.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left text-white text-[7px] md:text-[10px] lg:text-[14px] mr-2"></i>
                <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Back to List</span>
            </a>
        </div>
    </div>

    <!-- Content Container -->
    <div class="content-container w-full bg-white rounded-b-[10px] p-[7px] md:p-[10px] lg:p-[14px]">
        
        <!-- Basic Information Section -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Building -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Building</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->room->building->name ?? 'N/A' }}
                    </p>
                </div>

                <!-- Room -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Room</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->room->room_name ?? 'N/A' }}
                    </p>
                </div>

                <!-- Unit Name -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unit Name</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->unit_name ?? 'N/A' }}
                    </p>
                </div>

                <!-- Unit Type -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unit Type</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ ucwords(str_replace('_', ' ', $rentalUnit->unit_type)) ?? 'N/A' }}
                    </p>
                </div>

                <!-- Status -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rentalUnit->status == 'active' ? 'bg-green-100 text-green-800' : ($rentalUnit->status == 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ ucfirst($rentalUnit->status ?? 'N/A') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Rental Information Section -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Rental Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Rental Price -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rental Price</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->rental_price ? 'Rp ' . number_format($rentalUnit->rental_price, 0, ',', '.') : 'N/A' }}
                    </p>
                </div>

                <!-- Rental Period -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rental Period</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ ucfirst($rentalUnit->rental_period) ?? 'N/A' }}
                    </p>
                </div>

                <!-- Installation Date -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Installation Date</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->installation_date ? \Carbon\Carbon::parse($rentalUnit->installation_date)->format('d/mmm/Y') : 'N/A' }}
                    </p>
                </div>

                <!-- Maintenance Date -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Next Maintenance Date</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->maintenance_date ? \Carbon\Carbon::parse($rentalUnit->maintenance_date)->format('d/mmm/Y') : 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Technical Specifications Section -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Technical Specifications</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Model -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->model ?? 'N/A' }}
                    </p>
                </div>

                <!-- Serial Number -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Serial Number</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->serial_number ?? 'N/A' }}
                    </p>
                </div>

                <!-- Capacity -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacity</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->capacity ? $rentalUnit->capacity . ' ml' : 'N/A' }}
                    </p>
                </div>

                <!-- Power Consumption -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Power Consumption</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->power_consumption ? $rentalUnit->power_consumption . ' W' : 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Additional Information Section -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
            
            <div class="grid grid-cols-1 gap-6">
                <!-- Description -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md min-h-[60px]">
                        {{ $rentalUnit->description ?? 'No description available' }}
                    </p>
                </div>

                <!-- Notes -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md min-h-[60px]">
                        {{ $rentalUnit->notes ?? 'No notes available' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- System Information Section -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Created At -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Created At</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->created_at ? $rentalUnit->created_at->format('d/mmm/Y, H:i:s') : 'N/A' }}
                    </p>
                </div>

                <!-- Updated At -->
                <div class="info-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Updated</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md">
                        {{ $rentalUnit->updated_at ? $rentalUnit->updated_at->format('d/mmm/Y, H:i:s') : 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-row justify-end items-center w-full gap-4 pt-6 border-t border-gray-200">
            <a href="{{ route('operational.room-rental-units.index') }}" class="btn-secondary">
                <i class="fas fa-list mr-2"></i>
                Back to List
            </a>
            <a href="{{ route('operational.room-rental-units.edit', $rentalUnit->id) }}" class="btn-primary">
                <i class="fas fa-edit mr-2"></i>
                Edit Rental Unit
            </a>
        </div>
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

.info-group {
    margin-bottom: 1rem;
}

.info-group label {
    font-weight: 500;
    color: #374151;
}

.info-group p {
    margin: 0;
    word-wrap: break-word;
}
</style>
@endsection
