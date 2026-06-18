@extends('layouts.app')

@section('title', 'Master Price Slabs')
@section('breadcrumb', 'Home / Company / Master Price Slabs')

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
        min-width: 1600px;
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
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 250px; min-width: 250px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 150px; min-width: 150px; }
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
        background-color: #fee2e2;
        color: #991b1b;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 1600px;
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
        
        <!-- Master Price Slabs Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Price Slabs</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()" id="addPriceSlabBtn">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Price Slab</span>
                    <span class="md:hidden">Add</span>
                </button>
            </div>
        </div>
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row justify-start items-center w-full">
                <!-- Search Input -->
                <div class="flex flex-row justify-start items-center w-auto mr-4">
                    <div class="relative">
                        <input type="text" id="searchInput" class="form-input pl-10 pr-4 py-2 w-64" placeholder="Search price slabs..." value="{{ request('search') }}" onkeyup="handleSearch(event)">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
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
                        <th data-column="slab_code">Slab Code</th>
                        <th data-column="slab_name">Slab Name</th>
                        <th data-column="masterRental__rental_name">Rental</th>
                        <th data-column="min_quantity" data-type="numeric">Min Quantity</th>
                        <th data-column="max_quantity" data-type="numeric">Max Quantity</th>
                        <th data-column="unit_price" data-type="numeric">Unit Price</th>
                        <th data-column="discount_percentage" data-type="numeric">Discount %</th>
                        <th data-no-filter>Effective Date</th>
                        <th data-column="is_active">Status</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                <!-- Table Body -->
                <tbody>
                    @forelse($priceSlabs ?? [] as $slab)
                    <tr data-id="{{ $slab->id }}" onclick="openViewModal({{ $slab->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $slab->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $slab->slab_code ?? '-' }}</td>
                        <td>{{ $slab->slab_name ?? '-' }}</td>
                        <td>{{ $slab->masterRental->rental_name ?? '-' }}</td>
                        <td>{{ $slab->min_quantity ?? '-' }}</td>
                        <td>{{ $slab->max_quantity ?? 'Tanpa Batas' }}</td>
                        <td>{{ $slab->unit_price ? 'Rp ' . number_format($slab->unit_price, 2, ',', '.') : '-' }}</td>
                        <td>{{ $slab->discount_percentage ?? '-' }}%</td>
                        <td>{{ $slab->effective_date ? $slab->effective_date->format('d/M/Y') : '-' }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $slab->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $slab->status === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </td>
                        <td>{{ $slab->createdBy->name ?? '-' }}</td>
                        <td>
                            @if($slab->created_at)
                                {{ \Carbon\Carbon::parse($slab->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($slab->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $slab->updatedBy->name ?? '-' }}</td>
                        <td>
                            @if($slab->updated_at)
                                {{ \Carbon\Carbon::parse($slab->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($slab->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-tags text-4xl mb-3"></i>
                                <p class="text-lg">Belum ada price slab</p>
                                <button class="btn btn-primary mt-2" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Add First Price Slab
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($priceSlabs->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $priceSlabs->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Lihat Price Slab</h2>
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
        <h3 class="delete-modal-title">Sembunyikan Price Slab</h3>
        <p class="delete-modal-description" id="deleteMessage">Apakah kamu yakin ingin menyembunyikan price slab ini? Aksi ini masih bisa dibatalkan nanti.</p>
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
        <h3 class="delete-modal-title">Ups, Terjadi Kesalahan</h3>
        <p class="delete-modal-description" id="errorMessage">Price slab tidak dapat disembunyikan. Silakan coba lagi.</p>
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
        <p class="delete-modal-description" id="successMessage">Price slab berhasil disembunyikan.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentSlabId = null;
let currentAction = null;

document.addEventListener('DOMContentLoaded', function() {
    // Header checkbox functionality
    const headerSelectAll = document.getElementById('headerSelectAll');
    if (headerSelectAll) {
        headerSelectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Individual checkbox functionality
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) {
            const totalCheckboxes = document.querySelectorAll('.row-checkbox').length;
            const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked').length;
            const headerCheckbox = document.getElementById('headerSelectAll');
            if (headerCheckbox) {
                headerCheckbox.checked = totalCheckboxes === checkedCheckboxes;
            }
        }
    });

    // Search functionality
    function handleSearch(event) {
        if (event.key === 'Enter') {
            performSearch();
        }
    }

    function performSearch() {
        const searchTerm = document.getElementById('searchInput').value;
        const currentUrl = new URL(window.location);
        
        if (searchTerm.trim()) {
            currentUrl.searchParams.set('search', searchTerm);
        } else {
            currentUrl.searchParams.delete('search');
        }
        
        window.location.href = currentUrl.toString();
    }

    // Filter functionality
    const filterButtons = document.querySelectorAll('.btn-group button');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const filter = this.getAttribute('data-filter');
            window.location.href = "{{ route('company.master-price-slabs.index') }}?filter=" + filter;
        });
    });
});

// Modal Functions
function openCreateModal() {
    currentAction = 'create';
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    
    if (modalTitle) modalTitle.textContent = 'Tambah Price Slab';
    if (modalBody) modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch('/company/master-price-slabs/create', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        const modalBody = document.getElementById('modalBody');
        const modalFooter = document.getElementById('modalFooter');
        const modalOverlay = document.getElementById('modalOverlay');
        
        if (modalBody) modalBody.innerHTML = `
        <form id="createForm">
            <div class="modal-section">
                <h3 class="modal-section-title">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Master Rental *</label>
                        <select name="master_rental_id" class="form-input" required>
                            <option value="">Pilih Rental</option>
                            ${(data.data?.rentals || []).map(rental => '<option value="' + rental.id + '">' + rental.rental_name + '</option>').join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Slab Code</label>
                        <input type="text" name="slab_code" class="form-input" placeholder="Auto-generated if empty">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Slab Name *</label>
                        <input type="text" name="slab_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-input" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Is Active *</label>
                        <select name="is_active" class="form-input" required>
                            <option value="1">Ya</option>
                            <option value="0">Tidak</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input form-textarea" rows="3"></textarea>
                </div>
            </div>
            
            <div class="modal-section">
                <h3 class="modal-section-title">Quantity & Pricing</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Min Quantity *</label>
                        <input type="number" name="min_quantity" class="form-input" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Quantity</label>
                        <input type="number" name="max_quantity" class="form-input" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit Price *</label>
                        <input type="number" name="unit_price" class="form-input" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount Percentage</label>
                        <input type="number" name="discount_percentage" class="form-input" step="0.01" min="0" max="100">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <h3 class="modal-section-title">Date & Notes</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Effective Date *</label>
                        <input type="date" name="effective_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input form-textarea" rows="3"></textarea>
                </div>
            </div>
        </form>
        `;
        if (modalFooter) modalFooter.innerHTML = `
            <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button class="btn btn-primary" onclick="submitForm()">Tambah Price Slab</button>
        `;
        if (modalOverlay) modalOverlay.classList.add('show');
    })
    .catch(error => {
        console.error('Error:', error);
        const modalBody = document.getElementById('modalBody');
        const modalFooter = document.getElementById('modalFooter');
        const modalOverlay = document.getElementById('modalOverlay');
        
        if (modalBody) modalBody.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Gagal memuat data formulir.</div>';
        if (modalFooter) modalFooter.innerHTML = `
            <button class="btn btn-secondary" onclick="closeModal()">Tutup</button>
        `;
        if (modalOverlay) modalOverlay.classList.add('show');
    });
}

function openViewModal(id) {
    currentAction = 'view';
    currentSlabId = id;
    const modalTitle = document.getElementById('modalTitle');
    
    if (modalTitle) modalTitle.textContent = 'Lihat Price Slab';
    
    fetch("{{ route('company.master-price-slabs.index') }}/" + id, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(response => {
        const slab = response.data;
        const modalBody = document.getElementById('modalBody');
        const modalFooter = document.getElementById('modalFooter');
        const modalOverlay = document.getElementById('modalOverlay');
        
        if (modalBody) modalBody.innerHTML = `
                <div class="modal-section">
                    <h3 class="modal-section-title">Basic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Slab Code</label>
                            <div class="detail-value">${slab.slab_code || '-'}</div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Slab Name</label>
                            <div class="detail-value">${slab.slab_name || '-'}</div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Master Rental</label>
                            <div class="detail-value">${(slab.master_rental && slab.master_rental.rental_name) || '-'}</div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <div class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full ${slab.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${slab.status === 'active' ? 'Aktif' : 'Tidak Aktif'}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Description</label>
                        <div class="detail-value">${slab.description || '-'}</div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h3 class="modal-section-title">Quantity & Pricing</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Min Quantity</label>
                            <div class="detail-value">${slab.min_quantity || '-'}</div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Max Quantity</label>
                            <div class="detail-value">${slab.max_quantity || 'Tanpa Batas'}</div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Unit Price</label>
                            <div class="detail-value">${slab.unit_price ? 'Rp ' + new Intl.NumberFormat('id-ID').format(slab.unit_price) : '-'}</div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Discount Percentage</label>
                            <div class="detail-value">${slab.discount_percentage || '-'}%</div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h3 class="modal-section-title">Date & Notes</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Effective Date</label>
                            <div class="detail-value">${slab.effective_date ? new Date(slab.effective_date).toLocaleDateString('id-ID') : '-'}</div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Expiry Date</label>
                            <div class="detail-value">${slab.expiry_date ? new Date(slab.expiry_date).toLocaleDateString('id-ID') : '-'}</div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Created By</label>
                            <div class="detail-value">${(slab.created_by && slab.created_by.name) || '-'}</div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated By</label>
                            <div class="detail-value">${(slab.updated_by && slab.updated_by.name) || '-'}</div>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Notes</label>
                        <div class="detail-value">${slab.notes || '-'}</div>
                    </div>
                </div>
            `;
            if (modalFooter) modalFooter.innerHTML = `
                <button class="btn btn-secondary" onclick="closeModal()">Tutup</button>
                <button class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
            `;
            if (modalOverlay) modalOverlay.classList.add('show');
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal memuat data price slab.');
        });
}

function openEditModal(id) {
    currentAction = 'edit';
    currentSlabId = id;
    const modalTitle = document.getElementById('modalTitle');
    
    if (modalTitle) modalTitle.textContent = 'Edit Price Slab';
    
    fetch("{{ route('company.master-price-slabs.index') }}/" + id + "/edit", {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(response => {
        const slab = response.data;
        const rentals = response.rentals;
        const modalBody = document.getElementById('modalBody');
        const modalFooter = document.getElementById('modalFooter');
        const modalOverlay = document.getElementById('modalOverlay');
        
        let rentalOptions = '<option value="">Pilih Rental</option>';
        (rentals || []).forEach(rental => {
            rentalOptions += '<option value="' + rental.id + '"' + (rental.id == slab.master_rental_id ? ' selected' : '') + '>' + rental.rental_name + '</option>';
        });
        
        if (modalBody) modalBody.innerHTML = `
                <form id="editForm">
                    <input type="hidden" name="_method" value="PUT">
                    <div class="modal-section">
                        <h3 class="modal-section-title">Basic Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Master Rental *</label>
                                <select name="master_rental_id" class="form-input" required>
                                    ${rentalOptions}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Slab Code</label>
                                <input type="text" name="slab_code" class="form-input" value="${slab.slab_code || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Slab Name *</label>
                                <input type="text" name="slab_name" class="form-input" value="${slab.slab_name || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-input" required>
                                    <option value="active" ${slab.status === 'active' ? 'selected' : ''}>Aktif</option>
                                    <option value="inactive" ${slab.status === 'inactive' ? 'selected' : ''}>Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input form-textarea" rows="3">${slab.description || ''}</textarea>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <h3 class="modal-section-title">Quantity & Pricing</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Min Quantity *</label>
                                <input type="number" name="min_quantity" class="form-input" min="0" value="${slab.min_quantity || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Max Quantity</label>
                                <input type="number" name="max_quantity" class="form-input" min="0" value="${slab.max_quantity || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Unit Price *</label>
                                <input type="number" name="unit_price" class="form-input" step="0.01" min="0" value="${slab.unit_price || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Discount Percentage</label>
                                <input type="number" name="discount_percentage" class="form-input" step="0.01" min="0" max="100" value="${slab.discount_percentage || ''}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <h3 class="modal-section-title">Date & Notes</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Effective Date *</label>
                                <input type="date" name="effective_date" class="form-input" value="${slab.effective_date || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-input" value="${slab.expiry_date || ''}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-input form-textarea" rows="3">${slab.notes || ''}</textarea>
                        </div>
                    </div>
                </form>
            `;
            if (modalFooter) modalFooter.innerHTML = `
                <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button class="btn btn-primary" onclick="submitForm()">Perbarui Price Slab</button>
            `;
            if (modalOverlay) modalOverlay.classList.add('show');
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal memuat data price slab.');
        });
}

function openDeleteModal(id) {
    currentSlabId = id;
    const deleteModalOverlay = document.getElementById('deleteModalOverlay');
    if (deleteModalOverlay) deleteModalOverlay.classList.add('show');
}

function closeModal() {
    const modalOverlay = document.getElementById('modalOverlay');
    if (modalOverlay) modalOverlay.classList.remove('show');
    currentAction = null;
    currentSlabId = null;
}

function closeDeleteModal() {
    const deleteModalOverlay = document.getElementById('deleteModalOverlay');
    if (deleteModalOverlay) deleteModalOverlay.classList.remove('show');
    currentSlabId = null;
}

function closeErrorModal() {
    const errorModalOverlay = document.getElementById('errorModalOverlay');
    if (errorModalOverlay) errorModalOverlay.classList.remove('show');
}

function closeSuccessModal() {
    const successModalOverlay = document.getElementById('successModalOverlay');
    if (successModalOverlay) successModalOverlay.classList.remove('show');
}

function submitForm() {
    const formId = currentAction === 'create' ? 'createForm' : 'editForm';
    const form = document.getElementById(formId);
    const url = currentAction === 'create' 
        ? "{{ route('company.master-price-slabs.store') }}"
        : "{{ route('company.master-price-slabs.index') }}/" + currentSlabId;
    
    if (!form) {
        showErrorDialog('Gagal', 'Form tidak ditemukan.');
        return;
    }
    
    const formData = new FormData(form);
    
    // Add method spoofing for PUT requests
    if (currentAction === 'edit') {
        formData.append('_method', 'PUT');
    }
    
    fetch(url, {
        method: 'POST', // Always use POST for method spoofing
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 422) {
                return response.json().then(data => {
                    throw new Error('Validation failed: ' + JSON.stringify(data));
                });
            }
            return response.text().then(text => {
                throw new Error('Server error: ' + text);
            });
        }
        return response.json();
    })
    .then(response => {
        if (response.status === 'success') {
            closeModal();
            showSuccessDialog(
                currentAction === 'create'
                    ? 'Price slab berhasil dibuat.'
                    : 'Price slab berhasil diperbarui.'
            );
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showErrorDialog('Gagal', response.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', error.message);
    });
}

function confirmDelete() {
    if (!currentSlabId) return;
    
    fetch("{{ route('company.master-price-slabs.index') }}/" + currentSlabId, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(response => {
        closeDeleteModal();
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        closeDeleteModal();
        showErrorDialog('Gagal', 'Terjadi kesalahan saat menghapus price slab.');
    });
}

function deleteSelected() {
    const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
    const selectedIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
    
    if (selectedIds.length === 0) {
        showWarningDialog('Pilih minimal satu price slab yang ingin dihapus.');
        return;
    }
    
    showConfirmDialog(
        'Hapus price slab yang dipilih?',
        `${selectedIds.length} price slab akan dihapus.`
    ).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        fetch("{{ route('company.master-price-slabs.bulk-delete') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ ids: selectedIds })
        })
        .then(response => response.json())
        .then(response => {
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan saat menghapus price slab.');
        });
    });
}

function retryDelete() {
    closeErrorModal();
    if (currentSlabId) {
        confirmDelete();
    }
}
</script>
@endpush
