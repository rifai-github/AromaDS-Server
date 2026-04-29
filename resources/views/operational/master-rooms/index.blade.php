@extends('layouts.app')

@section('title', 'Master Room - Operational')
@section('breadcrumb', 'Home / Operational / Master Room')

@section('content')
<style>
    /* Responsive Table */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 10px 10px;
    }
    
    .responsive-table {
        min-width: 1500px;
        width: 100%;
        border-collapse: collapse;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 12px 8px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .responsive-table th {
        background-color: #225fd3;
        color: white;
        font-weight: 600;
        font-size: 13px;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
    }
    
    /* Form Styles */
    .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
        box-sizing: border-box;
        background-color: white !important;
        pointer-events: auto !important;
        position: relative;
        z-index: 10;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
        background-color: white !important;
    }
    
    .form-input:hover {
        border-color: #9ca3af;
    }
    
    .form-textarea {
        min-height: 100px;
        resize: vertical;
        background-color: white !important;
        pointer-events: auto !important;
        position: relative;
        z-index: 10;
    }
    
    /* Success Modal */
    .success-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 4000;
        align-items: center;
        justify-content: center;
    }

    .success-modal-overlay.show {
        display: flex;
    }

    .success-modal-container {
        background: #f0fdf4;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    .delete-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }

    .delete-icon {
        width: 80px;
        height: 80px;
    }

    .delete-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }

    .delete-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 1000px;
        }
        
        .controls-row {
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
        }
        
        .controls-left {
            justify-content: space-between;
        }
        
        .pagination-controls {
            justify-content: center;
            flex-wrap: wrap;
            gap: 5px;
        }
    }
    
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
    
    .btn-outline {
        background-color: white;
        color: #214589;
        border: 2px solid #214589;
        font-weight: 500;
    }
    
    .btn-outline:hover {
        background-color: #214589;
        color: white;
    }
    
    .btn-danger {
        background-color: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background-color: #dc2626;
    }
    
    /* Delete Button Hover - Blue */
    .btn-secondary:hover {
        background-color: #214589 !important;
        color: white !important;
        border-color: #214589 !important;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        backdrop-filter: blur(2px);
    }
    
    .modal-overlay.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 90vw;
        max-height: 90vh;
        width: 600px;
        overflow: hidden;
        position: relative;
    }
    
    .modal-header {
        background: #214589;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 20;
    }
    
    .modal-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }
    
    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s ease;
    }
    
    .modal-close:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .modal-body {
        padding: 20px;
        overflow-y: auto;
        max-height: calc(90vh - 140px);
        position: relative;
        z-index: 1;
    }
    
    .modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: center;
        gap: 20px;
        position: sticky;
        bottom: 0;
    }
    
    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
        position: relative;
        z-index: 1;
    }
    
    .space-y-4 > * + * {
        margin-top: 1rem;
    }
    
    .space-y-6 > * + * {
        margin-top: 1.5rem;
    }
    
    /* Grid Layout for Modal */
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
    
    /* Pagination Specific Styles */
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    
    .page-number {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .page-number.active {
        background-color: #214589;
        color: white;
    }
    
    .page-number:not(.active) {
        color: #6b7280;
    }
    
    .page-number:not(.active):hover {
        background-color: #f3f4f6;
        color: #214589;
    }
    
    .page-dropdown-container {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }
    
    .page-dropdown-container span {
        display: inline;
        white-space: nowrap;
    }
    
    /* Detail View Styles */
    .detail-item {
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .detail-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .detail-value {
        color: #6b7280;
        font-size: 14px;
        line-height: 1.5;
        margin-top: 4px;
        word-wrap: break-word;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }
    
    .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
        box-sizing: border-box;
        background-color: white !important;
        pointer-events: auto !important;
        position: relative;
        z-index: 10;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
        background-color: white !important;
    }
    
    .form-input:hover {
        border-color: #9ca3af;
    }
    
    .form-textarea {
        min-height: 100px;
        resize: vertical;
        background-color: white !important;
        pointer-events: auto !important;
        position: relative;
        z-index: 10;
    }
    
    /* Mobile Modal Adjustments */
    @media (max-width: 768px) {
        .modal-container {
            width: 95vw;
            max-height: 95vh;
        }
        
        .modal-header {
            padding: 15px;
        }
        
        .modal-body {
            padding: 15px;
            max-height: calc(95vh - 120px);
        }
        
        .modal-footer {
            padding: 15px;
            flex-direction: column;
        }
        
        .modal-footer .btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    /* Status Badge Styles */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .status-active {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-inactive {
        background-color: #fee2e2;
        color: #991b1b;
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Master Room Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Room</h1>
            </div>
            
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Add New Room</span>
            </button>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white controls-row">
            <div class="flex flex-row justify-start items-center w-full controls-left">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        <label for="selectAll" class="ml-2 text-sm text-[#3d3d3d] cursor-pointer">Select all</label>
                    </div>
                </div>
                
                <button class="btn btn-secondary ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
            
        </div>
        
        <!-- Table Container -->
        <div class="w-full bg-white rounded-b-[10px] table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th class="w-[50px]" data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th class="w-[150px]" data-column="customer.name" data-relation="customer">Customer</th>
                        <th class="w-[150px]" data-column="building.name" data-relation="building">Building Name</th>
                        <th class="w-[150px]" data-column="room_name">Room Name</th>
                        <th class="w-[120px]" data-column="room_type">Room Type</th>
                        <th class="w-[100px]" data-column="room_floor">Floor</th>
                        <th class="w-[80px]" data-column="room_qty" data-type="numeric">Qty</th>
                        <th class="w-[100px]" data-column="room_temperature" data-type="numeric">Temperature (°C)</th>
                        <th class="w-[120px]" data-column="room_intensity">Intensity</th>
                        <th class="w-[120px]" data-column="room_installation_type">Installation Type</th>
                        <th class="w-[100px]" data-column="room_length" data-type="numeric">Length (M)</th>
                        <th class="w-[100px]" data-column="room_width" data-type="numeric">Width (M)</th>
                        <th class="w-[100px]" data-column="room_height" data-type="numeric">Height (M)</th>
                        <th class="w-[100px]" data-column="is_active">Status</th>
                        <th class="w-[120px]" data-column="created_by">Created By</th>
                        <th class="w-[150px]" data-column="created_at" data-type="date">Created At</th>
                        <th class="w-[120px]" data-column="updated_by">Last Updated By</th>
                        <th class="w-[150px]" data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($rooms as $room)
                    <tr onclick="openViewModal({{ $room->id }})" data-id="{{ $room->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $room->id }}">
                        </td>
                        <td>{{ $room->customer->name ?? 'N/A' }}</td>
                        <td>{{ $room->building->nama_gedung ?? $room->building->name ?? 'N/A' }}</td>
                        <td>{{ $room->room_name ?? 'N/A' }}</td>
                        <td>{{ $room->room_type ?? 'N/A' }}</td>
                        <td>{{ $room->room_floor ?? 'N/A' }}</td>
                        <td>{{ $room->room_qty ?? 'N/A' }}</td>
                        <td>{{ $room->room_temperature ?? 'N/A' }}</td>
                        <td>{{ $room->room_intensity ?? 'N/A' }}</td>
                        <td>{{ $room->room_installation_type ?? 'N/A' }}</td>
                        <td>{{ $room->room_length ?? 'N/A' }}</td>
                        <td>{{ $room->room_width ?? 'N/A' }}</td>
                        <td>{{ $room->room_height ?? 'N/A' }}</td>
                        <td>
                            <span class="status-badge {{ $room->is_active ? 'status-active' : 'status-inactive' }}">
                                {{ $room->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $room->creator->name ?? 'N/A' }}</td>
                        <td>
                            @if($room->created_at)
                                {{ \Carbon\Carbon::parse($room->created_at)->format('d M Y') }}<br>
                                at {{ \Carbon\Carbon::parse($room->created_at)->format('H.i') }} WIB
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $room->updater->name ?? 'N/A' }}</td>
                        <td>
                            @if($room->updated_at)
                                {{ \Carbon\Carbon::parse($room->updated_at)->format('d M Y') }}<br>
                                at {{ \Carbon\Carbon::parse($room->updated_at)->format('H.i') }} WIB
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="16" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No rooms found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if(isset($rooms) && $rooms->currentPage() > 1)
                    <a href="{{ $rooms->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if(isset($rooms) && $rooms->lastPage() > 0)
                    @php
                        $start = max(1, $rooms->currentPage() - 2);
                        $end = min($rooms->lastPage(), $rooms->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $rooms->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $rooms->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $rooms->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $rooms->lastPage())
                            @if($end < $rooms->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $rooms->url($rooms->lastPage()) }}" class="page-number">{{ $rooms->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if(isset($rooms) && $rooms->hasMorePages())
                    <a href="{{ $rooms->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $rooms->currentPage() ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $rooms->lastPage() ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Room Details</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content will be loaded here -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Modal footer buttons will be loaded here -->
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" fill="#10b981"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">All Set!</h3>
        <p class="delete-modal-description" id="successMessage">The room has been successfully created.</p>
    </div>
</div>

<script>
// Buildings data for JavaScript
const buildingsData = @json($buildings ?? []);
console.log('Buildings data:', buildingsData);

// Function to populate building dropdown
function populateBuildingDropdown(selectElement, selectedBuildingId = null) {
    console.log('Populating building dropdown with data:', buildingsData);
    console.log('Selected building ID:', selectedBuildingId);
    
    // Clear existing options except the first one
    selectElement.innerHTML = '<option value="">Select Building</option>';
    
    // Add building options
    buildingsData.forEach(building => {
        const option = document.createElement('option');
        option.value = building.id;
        option.textContent = building.nama_gedung || building.name || 'Unknown Building';
        if (selectedBuildingId && building.id == selectedBuildingId) {
            option.selected = true;
        }
        selectElement.appendChild(option);
    });
    
    console.log('Building dropdown populated with', buildingsData.length, 'options');
}

// Function to populate building dropdown from AJAX data
function populateBuildingDropdownFromData(selectElement, buildingsData, selectedBuildingId = null) {
    console.log('Populating building dropdown with AJAX data:', buildingsData);
    console.log('Selected building ID:', selectedBuildingId);
    
    // Clear existing options except the first one
    selectElement.innerHTML = '<option value="">Select Building</option>';
    
    // Add building options
    buildingsData.forEach(building => {
        const option = document.createElement('option');
        option.value = building.id;
        option.textContent = building.nama_gedung || building.name || 'Unknown Building';
        if (selectedBuildingId && building.id == selectedBuildingId) {
            option.selected = true;
        }
        selectElement.appendChild(option);
    });
    
    console.log('Building dropdown populated with', buildingsData.length, 'options');
}

// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

document.getElementById('headerSelectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    document.getElementById('selectAll').checked = this.checked;
});

// Individual checkbox functionality
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('row-checkbox')) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        const headerSelectAllCheckbox = document.getElementById('headerSelectAll');
        
        const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
        const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
        
        selectAllCheckbox.checked = allChecked;
        headerSelectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = anyChecked && !allChecked;
        headerSelectAllCheckbox.indeterminate = anyChecked && !allChecked;
    }
});

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one item to delete');
        return;
    }
    
    if (confirm('Are you sure you want to delete the selected rooms?')) {
        const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        fetch('/operational/master-rooms/bulk-delete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ ids: selectedIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccessModal('Selected rooms have been successfully deleted.');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                alert('Error deleting rooms: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting rooms');
        });
    }
}

// Modal functions
function openModal() {
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function showSuccessModal(message = 'The room has been successfully created.') {
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSuccessModal() {
    document.getElementById('successModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Ruangan';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Buat ruangan baru dengan semua detail yang diperlukan.</p>
        <form id="createForm">
            <!-- Basic Information Section -->
            <div class="mb-6">
                <h6 class="text-primary mb-3 font-weight-bold">
                    <i class="fas fa-door-open mr-2"></i>
                    Informasi Dasar
                </h6>
                <hr class="mt-2 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Nama Ruangan *</label>
                        <input type="text" name="room_name" class="form-input" placeholder="Masukkan nama ruangan" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Ruangan *</label>
                        <select name="room_type" class="form-input" required>
                            <option value="">Pilih Jenis Ruangan</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->option_name }}">{{ $type->option_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lantai *</label>
                        <select name="room_floor" class="form-input" required>
                            <option value="">Pilih Lantai</option>
                            @foreach($floors as $floor)
                                <option value="{{ $floor->option_name }}">{{ $floor->option_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Qty *</label>
                        <input type="number" name="room_qty" class="form-input" min="1" placeholder="Masukkan jumlah" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Temperatur (°C) *</label>
                        <input type="number" name="room_temperature" class="form-input" step="0.1" placeholder="Masukkan temperatur" required>
                        <small class="form-text text-muted">Temperatur ruangan dalam derajat Celsius</small>
                    </div>
                </div>
            </div>

            <!-- Scent & Installation Section -->
            <div class="mb-6">
                <h6 class="text-primary mb-3 font-weight-bold">
                    <i class="fas fa-wind mr-2"></i>
                    Konfigurasi Wangi & Instalasi
                </h6>
                <hr class="mt-2 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Intensitas Wangi *</label>
                        <select name="room_intensity" class="form-input" required>
                            <option value="">Pilih Intensitas Wangi</option>
                            @foreach($intensities as $intensity)
                                <option value="{{ $intensity->option_name }}">{{ $intensity->option_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Installation Type *</label>
                        <select name="room_installation_type" class="form-input" required>
                            <option value="">Pilih Installation Type</option>
                            @foreach($installationTypes as $type)
                                <option value="{{ $type->option_name }}">{{ $type->option_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Dimensions Section -->
            <div class="mb-6">
                <h6 class="text-primary mb-3 font-weight-bold">
                    <i class="fas fa-ruler-combined mr-2"></i>
                    Dimensi Ruangan
                </h6>
                <hr class="mt-2 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label">Panjang (M) *</label>
                        <input type="number" name="room_length" class="form-input" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lebar (M) *</label>
                        <input type="number" name="room_width" class="form-input" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tinggi (M) *</label>
                        <input type="number" name="room_height" class="form-input" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="mb-6">
                <h6 class="text-primary mb-3 font-weight-bold">
                    <i class="fas fa-sticky-note mr-2"></i>
                    Informasi Tambahan
                </h6>
                <hr class="mt-2 mb-4">
                <div class="form-group">
                    <label class="form-label">Remark</label>
                    <textarea name="room_remark" class="form-input form-textarea" rows="3" placeholder="Masukkan catatan tambahan (opsional)"></textarea>
                </div>
            </div>

            <!-- Status -->
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-input">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Simpan</button>
        </div>
    `;
    
    // Populate dropdowns
    // Dropdowns are now populated from database in Blade template
    // No need to populate them with JavaScript
    
    openModal();
}

function openViewModal(id) {
    window.location.href = `/operational/master-rooms/${id}`;
}

function openEditModal(id) {
    // Load data via AJAX
    fetch(`/operational/master-rooms/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Ruangan';
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update detail ruangan.</p>
                <form id="editForm">
                    <input type="hidden" name="id" value="${data.data.masterRoom.id}">
                    
                    <!-- Basic Information Section -->
                    <div class="mb-6">
                        <h6 class="text-primary mb-3 font-weight-bold">
                            <i class="fas fa-door-open mr-2"></i>
                            Informasi Dasar
                        </h6>
                        <hr class="mt-2 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Nama Ruangan *</label>
                                <input type="text" name="room_name" class="form-input" value="${data.data.masterRoom.room_name || ''}" placeholder="Masukkan nama ruangan" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Jenis Ruangan *</label>
                                <select name="room_type" class="form-input" required>
                                    <option value="">Pilih Jenis Ruangan</option>
                                    @foreach($roomTypes as $type)
                                        <option value="{{ $type->option_name }}">{{ $type->option_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Lantai *</label>
                                <select name="room_floor" class="form-input" required>
                                    <option value="">Pilih Lantai</option>
                                    @foreach($floors as $floor)
                                        <option value="{{ $floor->option_name }}">{{ $floor->option_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Qty *</label>
                                <input type="number" name="room_qty" class="form-input" value="${data.data.masterRoom.room_qty || ''}" min="1" placeholder="Masukkan jumlah" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Temperatur (°C) *</label>
                                <input type="number" name="room_temperature" class="form-input" value="${data.data.masterRoom.room_temperature || ''}" step="0.1" placeholder="Masukkan temperatur" required>
                                <small class="form-text text-muted">Temperatur ruangan dalam derajat Celsius</small>
                            </div>
                        </div>
                    </div>

                    <!-- Scent & Installation Section -->
                    <div class="mb-6">
                        <h6 class="text-primary mb-3 font-weight-bold">
                            <i class="fas fa-wind mr-2"></i>
                            Konfigurasi Wangi & Instalasi
                        </h6>
                        <hr class="mt-2 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Intensitas Wangi *</label>
                                <select name="room_intensity" class="form-input" required>
                                    <option value="">Pilih Intensitas Wangi</option>
                                    @foreach($intensities as $intensity)
                                        <option value="{{ $intensity->option_name }}">{{ $intensity->option_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Installation Type *</label>
                                <select name="room_installation_type" class="form-input" required>
                                    <option value="">Pilih Installation Type</option>
                                    @foreach($installationTypes as $type)
                                        <option value="{{ $type->option_name }}">{{ $type->option_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dimensions Section -->
                    <div class="mb-6">
                        <h6 class="text-primary mb-3 font-weight-bold">
                            <i class="fas fa-ruler-combined mr-2"></i>
                            Dimensi Ruangan
                        </h6>
                        <hr class="mt-2 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group">
                                <label class="form-label">Panjang (M) *</label>
                                <input type="number" name="room_length" class="form-input" value="${data.data.masterRoom.room_length || ''}" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Lebar (M) *</label>
                                <input type="number" name="room_width" class="form-input" value="${data.data.masterRoom.room_width || ''}" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tinggi (M) *</label>
                                <input type="number" name="room_height" class="form-input" value="${data.data.masterRoom.room_height || ''}" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information Section -->
                    <div class="mb-6">
                        <h6 class="text-primary mb-3 font-weight-bold">
                            <i class="fas fa-sticky-note mr-2"></i>
                            Informasi Tambahan
                        </h6>
                        <hr class="mt-2 mb-4">
                        <div class="form-group">
                            <label class="form-label">Remark</label>
                            <textarea name="room_remark" class="form-input form-textarea" rows="3" placeholder="Masukkan catatan tambahan (opsional)">${data.data.masterRoom.room_remark || ''}</textarea>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-input">
                            <option value="1" ${data.data.masterRoom.is_active ? 'selected' : ''}>Active</option>
                            <option value="0" ${!data.data.masterRoom.is_active ? 'selected' : ''}>Inactive</option>
                        </select>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Update</button>
                </div>
            `;
            
            // Populate dropdowns with selected values
    // Set selected values for edit modal dropdowns
    setSelectedDropdownValue('room_type', data.data.masterRoom.room_type);
    setSelectedDropdownValue('room_floor', data.data.masterRoom.room_floor);
    setSelectedDropdownValue('room_intensity', data.data.masterRoom.room_intensity);
    setSelectedDropdownValue('room_installation_type', data.data.masterRoom.room_installation_type);
            
            openModal();
        })
        .catch(error => {
            console.error('Error loading room data:', error);
            alert('Error loading room data');
        });
}

// ==================== DROPDOWN POPULATION FUNCTIONS ====================

// Set selected values for edit modal dropdowns
function setSelectedDropdownValue(selectName, selectedValue) {
    const select = document.querySelector(`#editForm select[name="${selectName}"]`);
    if (select && selectedValue) {
        select.value = selectedValue;
    }
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    console.log('Form data:', Object.fromEntries(formData));
    
    fetch('/operational/master-rooms', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.status === 'success') {
            closeModal();
            showSuccessModal('The room has been successfully created.');
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            alert('Error creating room: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        alert('Error creating room: ' + error.message);
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    fetch(`/operational/master-rooms/${id}`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            showSuccessModal('The room has been successfully updated.');
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            alert('Error updating room: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating room');
    });
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Load floors based on selected building
function loadFloors(buildingId, selectedFloorId = null) {
    const floorSelect = document.querySelector('#createForm select[name="floor_id"]') || document.querySelector('#editForm select[name="floor_id"]');
    const unitSelect = document.querySelector('#createForm select[name="unit_id"]') || document.querySelector('#editForm select[name="unit_id"]');
    
    // Reset dependent dropdowns
    if (floorSelect) floorSelect.innerHTML = '<option value="">Select Floor</option>';
    if (unitSelect) unitSelect.innerHTML = '<option value="">Select Unit (Optional)</option>';
    
    if (!buildingId) return;
    
    fetch(`/api/buildings/${buildingId}/floors`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && floorSelect) {
                data.data.forEach(floor => {
                    const option = document.createElement('option');
                    option.value = floor.id;
                    option.textContent = `Floor ${floor.floor_number} - ${floor.floor_name || 'No Name'}`;
                    if (selectedFloorId && floor.id == selectedFloorId) {
                        option.selected = true;
                    }
                    floorSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading floors:', error));
}

// Load units based on selected floor
function loadUnits(floorId, selectedUnitId = null) {
    const unitSelect = document.querySelector('#createForm select[name="unit_id"]') || document.querySelector('#editForm select[name="unit_id"]');
    
    // Reset unit dropdown
    if (unitSelect) unitSelect.innerHTML = '<option value="">Select Unit (Optional)</option>';
    
    if (!floorId) return;
    
    fetch(`/api/floors/${floorId}/units`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && unitSelect) {
                data.data.forEach(unit => {
                    const option = document.createElement('option');
                    option.value = unit.id;
                    option.textContent = `Unit ${unit.unit_number} - ${unit.unit_name}`;
                    if (selectedUnitId && unit.id == selectedUnitId) {
                        option.selected = true;
                    }
                    unitSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading units:', error));
}

// Load floors for edit form
function loadFloorsForEdit(buildingId, selectedFloorId = null) {
    loadFloors(buildingId, selectedFloorId);
}

// Load units for edit form  
function loadUnitsForEdit(floorId, selectedUnitId = null) {
    loadUnits(floorId, selectedUnitId);
}
</script>
@endsection

