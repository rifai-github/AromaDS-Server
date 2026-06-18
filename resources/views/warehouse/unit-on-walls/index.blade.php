@extends('layouts.app')

@section('title', 'Unit On Wall')
@section('breadcrumb', 'Home / Warehouse / Unit On Wall')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }

    *, *::before, *::after {
        box-sizing: border-box;
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

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Delete Button Hover - Blue */
    .btn-secondary:hover {
        background-color: #214589 !important;
        color: white !important;
        border-color: #214589 !important;
    }

    /* Table Container */
    .table-container {
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border-radius: 0 0 10px 10px;
        position: relative;
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }

    /* Custom scrollbar */
    .table-container::-webkit-scrollbar {
        height: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Scroll indicator */
    .table-container::after {
        content: '← Scroll horizontally to see more →';
        position: absolute;
        bottom: -25px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 12px;
        color: #666;
        opacity: 0.7;
    }

    /* Responsive Table */
    .responsive-table {
        min-width: 1200px;
        table-layout: auto;
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
        height: auto;
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
        background-color: #214589;
        color: white;
        font-weight: 600;
        font-size: 13px;
        position: sticky;
        top: 0;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: visible;
        text-overflow: unset;
    }

    .responsive-table td {
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
    }
    
    .responsive-table tbody {
        height: auto;
    }

    /* Column widths for better layout */
    .responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 50px; min-width: 50px; }
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 120px; min-width: 120px; }

    /* Pagination Specific Styles */
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: nowrap;
        white-space: nowrap;
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
    }

    .pagination-btn {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid #d1d5db;
        background-color: #f9fafb;
        color: #374151;
    }

    .pagination-btn:hover:not(:disabled) {
        background-color: #f3f4f6;
        border-color: #9ca3af;
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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
        width: 800px;
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
    
    /* Delete Confirmation Modal */
    .delete-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }

    .delete-modal-overlay.show {
        display: flex;
    }

    .delete-modal-container {
        background: #f0f9ff;
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

    .delete-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }

    .btn-cancel {
        background-color: white;
        color: #1e40af;
        border: 2px solid #1e40af;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-cancel:hover {
        background-color: #f8fafc;
        border-color: #1e3a8a;
        color: #1e3a8a;
    }

    .btn-hide {
        background-color: #1e40af;
        color: white;
        border: 2px solid #1e40af;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-hide:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
    }

    /* Error Modal */
    .error-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 3000;
        align-items: center;
        justify-content: center;
    }
    
    .error-modal-overlay.show {
        display: flex;
    }

    .error-modal-container {
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

    /* Form Input Styling */
    input[type="date"], input[type="text"], input[type="number"], select, textarea {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    input[type="date"]:focus, input[type="text"]:focus, input[type="number"]:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
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

    /* Modal Section Styles */
    .modal-section {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        background: #f9fafb;
    }

    .modal-section:last-child {
        margin-bottom: 0;
    }

    .modal-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 1px solid #d1d5db;
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
        background-color: #f3f4f6;
        color: #6b7280;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 1200px;
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
            max-width: 100%;
            overflow-x: hidden;
        }
        
        .page-dropdown-container {
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Header responsive */
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

    /* Tablet and small screen responsive */
    @media (max-width: 1024px) and (min-width: 769px) {
        .flex-wrap {
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .flex-wrap > div {
            flex: 1 1 calc(50% - 0.5rem);
            min-width: 200px;
        }
    }
</style>
<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Unit On Wall Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Unit On Wall</h1>
            </div>
            
            {{-- Button Add New dihapus karena Unit On Wall otomatis ter-create dari Install Job --}}
        </div>

        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row justify-start items-center w-full">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center w-auto">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        <div class="flex flex-row justify-start items-center w-full px-2">
                            <p class="text-sm font-normal text-gray-700 w-auto ml-2 cursor-pointer" onclick="document.getElementById('selectAll').click()">Select all</p>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Button -->
                <button class="btn btn-secondary btn-sm ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
            
        </div>

        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="serialNumber__serial_number">Serial Number</th>
                        <th data-column="customer__name">Customer</th>
                        <th data-column="building__nama_gedung">Building</th>
                        <th data-column="room__room_name">Room</th>
                        <th data-column="rental__rental_name">Rental</th>
                        <th data-column="product__name">Product</th>
                        <th data-column="status">Status</th>
                        <th data-column="install_date" data-type="date">Install Date</th>
                        <th data-column="last_service_date" data-type="date">Last Service</th>
                        <th data-column="temperature" data-type="numeric">Temperature</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="createdBy.name">Created By</th>
                        <th data-column="updated_at" data-type="date">Updated At</th>
                        <th data-column="updatedBy.name">Updated By</th>
                    </tr>
                </thead>
                <!-- Table Body -->
                <tbody>
                    @forelse($units ?? [] as $unit)
                    <tr data-id="{{ $unit->id }}" onclick="window.location.href='{{ route('warehouse.unit-on-walls.show', $unit->id) }}'" style="cursor: pointer;">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $unit->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td class="font-medium">{{ $unit->serial_number ?? '-' }}</td>
                        <td>{{ $unit->customer_name ?? '-' }}</td>
                        <td>
                            @if($unit->building)
                                <div>
                                    <div class="font-medium">{{ $unit->building->nama_gedung ?? '-' }}</div>
                                    <div class="text-sm text-gray-500">{{ $unit->building->alamat_1 ?? '-' }}</div>
                                </div>
                            @else
                                <span>-</span>
                            @endif
                        </td>
                        <td>{{ $unit->room_name ?? '-' }}</td>
                        <td>{{ $unit->rental_name ?? '-' }}</td>
                        <td>{{ $unit->product_name ?? '-' }}</td>
                        <td>
                            <span class="status-badge {{ $unit->status_badge_class }}">{{ $unit->status_text }}</span>
                        </td>
                        <td>{{ $unit->formatted_install_date }}</td>
                        <td>{{ $unit->formatted_last_service_date }}</td>
                        <td>{{ $unit->temperature ? $unit->temperature . '°C' : '-' }}</td>
                        <td>{{ $unit->created_at ? $unit->created_at->format('d/M/Y H:i') : '-' }}</td>
                        <td>{{ $unit->createdBy->name ?? '-' }}</td>
                        <td>{{ $unit->updated_at ? $unit->updated_at->format('d/M/Y H:i') : '-' }}</td>
                        <td>{{ $unit->updatedBy->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-cube text-4xl mb-3"></i>
                                <p class="text-lg">No units found</p>
                                <button class="btn btn-primary mt-2" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Add First Unit
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($units->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $units->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Unit On Wall</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content will be loaded here -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Modal footer content will be loaded here -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Sembunyikan Unit On Wall</h3>
        <p class="delete-modal-description" id="deleteMessage">Apakah kamu yakin ingin menyembunyikan unit ini? Tindakan ini masih bisa dibatalkan nanti.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Ya, Sembunyikan</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Ups... Terjadi Kendala</h3>
        <p class="delete-modal-description" id="errorMessage">Unit belum berhasil disembunyikan. Silakan coba lagi.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeErrorModal()">Tutup</button>
            <button class="btn btn-hide" onclick="retryDelete()">Coba Lagi</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Berhasil</h3>
        <p class="delete-modal-description" id="successMessage">Unit berhasil disembunyikan.</p>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;

// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    document.getElementById('headerSelectAll').checked = this.checked;
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

// Modal functions
function openModal(title) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
    document.getElementById('modalBody').innerHTML = '';
    document.getElementById('modalFooter').innerHTML = '';
}

// CRUD Modal functions
function openCreateModal() {
    openModal('Create New Unit On Wall');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Basic Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Serial Number</label>
                        <div class="flex gap-2">
                            <input type="text" name="serial_number" class="form-input flex-1" placeholder="Enter serial number or scan QR code">
                            <button type="button" class="btn btn-secondary" onclick="startQRScan()">
                                <i class="fas fa-qrcode"></i>
                                <span class="hidden md:inline">Scan QR</span>
                            </button>
                        </div>
                        <small class="text-gray-500 text-xs">Click Scan QR button to scan QR code</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Customer *</label>
                        <select name="customer_id" class="form-input" required>
                            <option value="">Select Customer</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Location Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Building *</label>
                        <select name="building_id" class="form-input" required onchange="loadFloorsForUnitOnWall(this.value)">
                            <option value="">Select Building</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Floor</label>
                        <select name="floor_id" class="form-input" onchange="loadUnitsForUnitOnWall(this.value)">
                            <option value="">Select Floor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit</label>
                        <select name="unit_id" class="form-input" onchange="loadRoomsForUnitOnWall(this.value)">
                            <option value="">Select Unit (Optional)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room</label>
                        <select name="room_id" class="form-input">
                            <option value="">Select Room</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Product Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Rental</label>
                        <select name="rental_id" class="form-input">
                            <option value="">Select Rental</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Product</label>
                        <select name="product_id" class="form-input">
                            <option value="">Select Product</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Service Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Install Date *</label>
                        <input type="date" name="install_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Service Date</label>
                        <input type="date" name="last_service_date" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-input" required>
                            <option value="">Select Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Under Maintenance</option>
                            <option value="removed">Removed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Temperature (°C)</label>
                        <input type="number" name="temperature" class="form-input" step="0.01" min="-10" max="60" placeholder="Enter temperature">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Warranty Expires At</label>
                        <input type="date" name="warranty_expires_at" class="form-input">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Additional Information</div>
                <div class="grid grid-cols-1 gap-6">
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input form-textarea" placeholder="Enter additional notes"></textarea>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    // Load dynamic data
    loadCustomers();
    loadBuildings();
    loadRooms();
    loadRentals();
    loadProducts();
    
    // Add modal footer
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" form="form" class="btn btn-primary">Create Unit</button>
    `;
}

function openViewModal(id) {
    openModal('View Unit On Wall');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/warehouse/unit-on-walls/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const formatDate = (date) => {
                if (!date) return '-';
                const d = new Date(date);
                const day = d.getDate().toString().padStart(2, '0');
                const month = (d.getMonth() + 1).toString().padStart(3, '0');
                const year = d.getFullYear();
                return `${day}/${month}/${year}`;
            };

            const formatDateTime = (date) => {
                if (!date) return '-';
                const d = new Date(date);
                const day = d.getDate().toString().padStart(2, '0');
                const month = (d.getMonth() + 1).toString().padStart(3, '0');
                const year = d.getFullYear();
                const hours = d.getHours().toString().padStart(2, '0');
                const minutes = d.getMinutes().toString().padStart(2, '0');
                return `${day}/${month}/${year} ${hours}:${minutes}`;
            };

            const getStatusBadge = (status) => {
                const badges = {
                    'active': 'status-active',
                    'inactive': 'status-inactive',
                    'maintenance': 'status-warning',
                    'removed': 'status-danger'
                };
                const texts = {
                    'active': 'Active',
                    'inactive': 'Inactive',
                    'maintenance': 'Under Maintenance',
                    'removed': 'Removed'
                };
                return `<span class="status-badge ${badges[status] || 'status-inactive'}">${texts[status] || 'Unknown'}</span>`;
            };

            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">ID</label>
                            <p class="detail-value">${data.data.id || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Serial Number</label>
                            <p class="detail-value">${data.data.serial_number || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Customer</label>
                            <p class="detail-value">${data.data.customer?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value">${getStatusBadge(data.data.status)}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Location Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Building</label>
                            <p class="detail-value">${data.data.building?.nama_gedung || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Room</label>
                            <p class="detail-value">${data.data.room?.room_name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Building Address</label>
                            <p class="detail-value">${data.data.building?.alamat_1 || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Product Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Rental</label>
                            <p class="detail-value">${data.data.rental?.rental_name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Product</label>
                            <p class="detail-value">${data.data.product?.name || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Service Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Install Date</label>
                            <p class="detail-value">${formatDate(data.data.install_date)}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Last Service Date</label>
                            <p class="detail-value">${formatDate(data.data.last_service_date)}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Temperature</label>
                            <p class="detail-value">${data.data.temperature ? data.data.temperature + '°C' : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Warranty Expires At</label>
                            <p class="detail-value">${formatDate(data.data.warranty_expires_at)}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Last Seen At</label>
                            <p class="detail-value">${formatDateTime(data.data.last_seen_at)}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Additional Information</div>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Notes</label>
                            <p class="detail-value">${data.data.notes || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Audit Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Created By</label>
                            <p class="detail-value">${data.data.created_by?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated By</label>
                            <p class="detail-value">${data.data.updated_by?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Created At</label>
                            <p class="detail-value">${formatDateTime(data.data.created_at)}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated At</label>
                            <p class="detail-value">${formatDateTime(data.data.updated_at)}</p>
                        </div>
                    </div>
                </div>
            `;
        
        // Add modal footer for view modal
            document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit Unit</button>
        `;
        })
        .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
        `;
        });
}

function openEditModal(id) {
    openModal('Edit Unit On Wall');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/warehouse/unit-on-walls/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <input type="hidden" name="id" value="${data.data.id}">
                    <div class="modal-section">
                        <div class="modal-section-title">Basic Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Serial Number</label>
                                <input type="text" name="serial_number" class="form-input" value="${data.data.serial_number || ''}" placeholder="Enter serial number">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Customer *</label>
                                <select name="customer_id" class="form-input" required>
                                    <option value="">Select Customer</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Location Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Building *</label>
                                <select name="building_id" class="form-input" required onchange="loadFloorsForUnitOnWallEdit(this.value)">
                                    <option value="">Select Building</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Floor</label>
                                <select name="floor_id" class="form-input" onchange="loadUnitsForUnitOnWallEdit(this.value)">
                                    <option value="">Select Floor</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Unit</label>
                                <select name="unit_id" class="form-input" onchange="loadRoomsForUnitOnWallEdit(this.value)">
                                    <option value="">Select Unit (Optional)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Room</label>
                                <select name="room_id" class="form-input">
                                    <option value="">Select Room</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Product Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Rental</label>
                                <select name="rental_id" class="form-input">
                                    <option value="">Select Rental</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Product</label>
                                <select name="product_id" class="form-input">
                                    <option value="">Select Product</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Service Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Install Date *</label>
                                <input type="date" name="install_date" class="form-input" value="${data.data.install_date ? new Date(data.data.install_date).toISOString().slice(0, 10) : ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Service Date</label>
                                <input type="date" name="last_service_date" class="form-input" value="${data.data.last_service_date ? new Date(data.data.last_service_date).toISOString().slice(0, 10) : ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-input" required>
                                    <option value="">Select Status</option>
                                    <option value="active" ${data.data.status === 'active' ? 'selected' : ''}>Active</option>
                                    <option value="inactive" ${data.data.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                    <option value="maintenance" ${data.data.status === 'maintenance' ? 'selected' : ''}>Under Maintenance</option>
                                    <option value="removed" ${data.data.status === 'removed' ? 'selected' : ''}>Removed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Temperature (°C)</label>
                                <input type="number" name="temperature" class="form-input" step="0.01" min="-10" max="60" value="${data.data.temperature || ''}" placeholder="Enter temperature">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Warranty Expires At</label>
                                <input type="date" name="warranty_expires_at" class="form-input" value="${data.data.warranty_expires_at ? new Date(data.data.warranty_expires_at).toISOString().slice(0, 10) : ''}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Additional Information</div>
                        <div class="grid grid-cols-1 gap-6">
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-input form-textarea" placeholder="Enter additional notes">${data.data.notes || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Load dynamic data and set current values
            loadCustomers(data.data.customer_id);
            loadBuildings(data.data.building_id);
            loadRooms(data.data.room_id);
            loadRentals(data.data.rental_id);
            loadProducts(data.data.product_id);
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Unit</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            `;
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/warehouse/unit-on-walls/${id}` : '/warehouse/unit-on-walls';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', result.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan.');
    });
}

// Delete Modal functions
function openDeleteModal(id = null) {
    if (id) {
        selectedIdsForRetry = [id];
    }
    
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Apakah Anda yakin ingin menyembunyikan unit ini? Tindakan ini masih bisa dibatalkan nanti.'
        : `Apakah Anda yakin ingin menyembunyikan ${count} unit? Tindakan ini masih bisa dibatalkan nanti.`;
    
    document.getElementById('deleteMessage').textContent = message;
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function confirmDelete() {
    closeDeleteModal();
    
    fetch('/warehouse/unit-on-walls/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ unit_ids: selectedIdsForRetry })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessModal(result.count);
        } else {
            showErrorModal(result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan jaringan.');
    });
}

// Bulk operations
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu unit yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'Unit berhasil disembunyikan.'
        : `${count} unit berhasil disembunyikan.`;
    
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Auto close after 3 seconds
    successModalTimer = setTimeout(() => {
        closeSuccessModal();
        location.reload();
    }, 3000);
}

function closeSuccessModal() {
    if (successModalTimer) {
        clearTimeout(successModalTimer);
        successModalTimer = null;
    }
    document.getElementById('successModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Error Modal functions
function showErrorModal(message) {
    document.getElementById('errorMessage').textContent = message || 'Unit tidak berhasil disembunyikan. Silakan coba lagi.';
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retryDelete() {
    closeErrorModal();
    confirmDelete();
}

// Event listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
        closeErrorModal();
        closeSuccessModal();
    }
});

// Dynamic loading functions for dropdowns
function loadCustomers(selectedId = null) {
    fetch('/api/customers', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const select = document.querySelector('select[name="customer_id"]');
        if (select) {
            select.innerHTML = '<option value="">Select Customer</option>';
            data.data.forEach(customer => {
                const option = document.createElement('option');
                option.value = customer.id;
                option.textContent = customer.name;
                if (selectedId && customer.id == selectedId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
    })
    .catch(error => console.error('Error loading customers:', error));
}

function loadBuildings(selectedId = null) {
    fetch('/api/buildings', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const select = document.querySelector('select[name="building_id"]');
        if (select) {
            select.innerHTML = '<option value="">Select Building</option>';
            data.data.forEach(building => {
                const option = document.createElement('option');
                option.value = building.id;
                option.textContent = building.nama_gedung;
                if (selectedId && building.id == selectedId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
    })
    .catch(error => console.error('Error loading buildings:', error));
}

function loadRooms(selectedId = null) {
    fetch('/api/master-rooms', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const select = document.querySelector('select[name="room_id"]');
        if (select) {
            select.innerHTML = '<option value="">Select Room</option>';
            data.data.forEach(room => {
                const option = document.createElement('option');
                option.value = room.id;
                option.textContent = room.room_name;
                if (selectedId && room.id == selectedId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
    })
    .catch(error => console.error('Error loading rooms:', error));
}

// QR Code Scanning functions
function startQRScan() {
    // Check if device has camera access
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        Swal.fire({
            title: 'Masukkan Nilai QR',
            input: 'text',
            inputLabel: 'Nilai QR Code',
            inputPlaceholder: 'Masukkan nilai QR code',
            showCancelButton: true,
            confirmButtonText: 'Gunakan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed || !result.value) {
                return;
            }

            const serialNumberInput = document.querySelector('input[name="serial_number"]');
            if (serialNumberInput) {
                serialNumberInput.value = result.value;
                serialNumberInput.focus();
            }
        });
    } else {
        showInfoDialog('Info', 'Pemindaian QR tidak didukung di perangkat ini. Silakan masukkan serial number secara manual.');
    }
}

function loadRentals(selectedId = null) {
    fetch('/api/master-rentals', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const select = document.querySelector('select[name="rental_id"]');
        if (select) {
            select.innerHTML = '<option value="">Select Rental</option>';
            data.data.forEach(rental => {
                const option = document.createElement('option');
                option.value = rental.id;
                option.textContent = rental.rental_name;
                if (selectedId && rental.id == selectedId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
    })
    .catch(error => console.error('Error loading rentals:', error));
}

function loadProducts(selectedId = null) {
    fetch('/api/master-products', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const select = document.querySelector('select[name="product_id"]');
        if (select) {
            select.innerHTML = '<option value="">Select Product</option>';
            data.data.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = product.name;
                if (selectedId && product.id == selectedId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
    })
    .catch(error => console.error('Error loading products:', error));
}

// Add CSS for loading spinner
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Load floors for Unit On Wall create form
function loadFloorsForUnitOnWall(buildingId, selectedFloorId = null) {
    const floorSelect = document.querySelector('#createForm select[name="floor_id"]');
    const unitSelect = document.querySelector('#createForm select[name="unit_id"]');
    const roomSelect = document.querySelector('#createForm select[name="room_id"]');
    
    // Reset dependent dropdowns
    if (floorSelect) floorSelect.innerHTML = '<option value="">Select Floor</option>';
    if (unitSelect) unitSelect.innerHTML = '<option value="">Select Unit (Optional)</option>';
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room</option>';
    
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

// Load units for Unit On Wall create form
function loadUnitsForUnitOnWall(floorId, selectedUnitId = null) {
    const unitSelect = document.querySelector('#createForm select[name="unit_id"]');
    const roomSelect = document.querySelector('#createForm select[name="room_id"]');
    
    // Reset dependent dropdowns
    if (unitSelect) unitSelect.innerHTML = '<option value="">Select Unit (Optional)</option>';
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room</option>';
    
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

// Load rooms for Unit On Wall create form
function loadRoomsForUnitOnWall(unitId, selectedRoomId = null) {
    const roomSelect = document.querySelector('#createForm select[name="room_id"]');
    
    // Reset room dropdown
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room</option>';
    
    if (!unitId) return;
    
    fetch(`/api/units/${unitId}/rooms`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && roomSelect) {
                data.data.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = `${room.room_name} - ${room.room_code || ''}`;
                    if (selectedRoomId && room.id == selectedRoomId) {
                        option.selected = true;
                    }
                    roomSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading rooms:', error));
}

// Load floors for Unit On Wall edit form
function loadFloorsForUnitOnWallEdit(buildingId, selectedFloorId = null) {
    const floorSelect = document.querySelector('#editForm select[name="floor_id"]');
    const unitSelect = document.querySelector('#editForm select[name="unit_id"]');
    const roomSelect = document.querySelector('#editForm select[name="room_id"]');
    
    // Reset dependent dropdowns
    if (floorSelect) floorSelect.innerHTML = '<option value="">Select Floor</option>';
    if (unitSelect) unitSelect.innerHTML = '<option value="">Select Unit (Optional)</option>';
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room</option>';
    
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

// Load units for Unit On Wall edit form
function loadUnitsForUnitOnWallEdit(floorId, selectedUnitId = null) {
    const unitSelect = document.querySelector('#editForm select[name="unit_id"]');
    const roomSelect = document.querySelector('#editForm select[name="room_id"]');
    
    // Reset dependent dropdowns
    if (unitSelect) unitSelect.innerHTML = '<option value="">Select Unit (Optional)</option>';
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room</option>';
    
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

// Load rooms for Unit On Wall edit form
function loadRoomsForUnitOnWallEdit(unitId, selectedRoomId = null) {
    const roomSelect = document.querySelector('#editForm select[name="room_id"]');
    
    // Reset room dropdown
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room</option>';
    
    if (!unitId) return;
    
    fetch(`/api/units/${unitId}/rooms`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && roomSelect) {
                data.data.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = `${room.room_name} - ${room.room_code || ''}`;
                    if (selectedRoomId && room.id == selectedRoomId) {
                        option.selected = true;
                    }
                    roomSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading rooms:', error));
}
</script>
@endsection
