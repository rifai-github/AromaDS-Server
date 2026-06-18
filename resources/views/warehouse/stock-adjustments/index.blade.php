@extends('layouts.app')

@section('title', 'Stock Adjustments')
@section('breadcrumb', 'Home / Warehouse / Stock Adjustments')

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
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 120px; min-width: 120px; }

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

    /* Adjustment Type Badge Styles */
    .adjustment-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .adjustment-increase {
        background-color: #d1fae5;
        color: #065f46;
    }

    .adjustment-decrease {
        background-color: #fee2e2;
        color: #991b1b;
    }

    /* Quantity Badge Styles */
    .quantity-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }

    .quantity-positive {
        background-color: #d1fae5;
        color: #065f46;
    }

    .quantity-negative {
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
        
        <!-- Stock Adjustments Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Stock Adjustments</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Adjustment</span>
                    <span class="md:hidden">Add</span>
                </button>
            </div>
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
                        <th data-column="adjustment_no">Adjustment No</th>
                        <th data-column="adjustment_date" data-type="date">Adjustment Date</th>
                        <th data-column="warehouse__name">Warehouse</th>
                        <th data-column="items_count">Items</th>
                        <th data-column="total_change">Total Change</th>
                        <th data-column="status">Status</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Updated By</th>
                        <th data-column="updated_at" data-type="date">Updated At</th>
                        <th data-column="reason">Reason</th>
                    </tr>
                </thead>
                <!-- Table Body -->
                <tbody>
                    @forelse($adjustments ?? [] as $adjustment)
                    <tr data-id="{{ $adjustment->id }}" onclick="window.location.href='{{ route('warehouse.stock-adjustments.show', $adjustment->id) }}'" class="cursor-pointer">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $adjustment->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $adjustment->adjustment_no ?? '-' }}</td>
                        <td>{{ $adjustment->adjustment_date ? \Carbon\Carbon::parse($adjustment->adjustment_date)->format('d/M/Y') : '-' }}</td>
                        <td>{{ $adjustment->warehouse->name ?? '-' }}</td>
                        <td>{{ $adjustment->items()->count() }} Items</td>
                        <td>
                            @php
                                $totals = $adjustment->formatted_adjustment_totals;
                            @endphp
                            <span class="text-green-600">+{{ $totals['increase'] }}</span> / 
                            <span class="text-red-600">-{{ $totals['decrease'] }}</span>
                        </td>
                        <td>
                            @php
                                $statusClass = 'bg-gray-100 text-gray-800';
                                if($adjustment->status === 'approved') $statusClass = 'status-active';
                                elseif($adjustment->status === 'rejected') $statusClass = 'status-inactive';
                                elseif($adjustment->status === 'waiting for approval') $statusClass = 'bg-yellow-100 text-yellow-800';
                                elseif($adjustment->status === 'draft') $statusClass = 'bg-blue-100 text-blue-800';
                            @endphp
                            <span class="status-badge {{ $statusClass }}">
                                {{ strtoupper($adjustment->status ?? 'DRAFT') }}
                            </span>
                        </td>
                        <td>{{ $adjustment->createdBy->name ?? '-' }}</td>
                        <td>{{ $adjustment->created_at ? $adjustment->created_at->format('d/M/Y H:i') : '-' }}</td>
                        <td>{{ $adjustment->updatedBy->name ?? '-' }}</td>
                        <td>{{ $adjustment->updated_at ? $adjustment->updated_at->format('d/M/Y H:i') : '-' }}</td>
                        <td>{{ $adjustment->reason ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-inbox text-4xl mb-3"></i>
                                <p class="text-lg">No stock adjustments found</p>
                                <button class="btn btn-primary mt-2" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Add First Adjustment
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($adjustments->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $adjustments->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Stock Adjustment</h2>
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
        <h3 class="delete-modal-title">Sembunyikan Stock Adjustment</h3>
        <p class="delete-modal-description" id="deleteMessage">Apakah kamu yakin ingin menyembunyikan stock adjustment ini? Tindakan ini masih bisa dibatalkan nanti.</p>
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
        <p class="delete-modal-description" id="errorMessage">Stock adjustment belum berhasil disembunyikan. Silakan coba lagi.</p>
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
        <p class="delete-modal-description" id="successMessage">Stock adjustment berhasil disembunyikan.</p>
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
    openModal('Create New Stock Adjustment');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Stock Adjustment Header</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Warehouse *</label>
                        <select name="warehouse_id" id="modal_warehouse_id" class="form-input" required>
                            <option value="">Loading warehouses...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adjustment Date *</label>
                        <div class="flex items-center" style="gap: 12px;">
                            <input type="text" id="adjustmentDateInput" name="adjustment_date" class="form-input cursor-pointer" 
                                placeholder="Select Date" readonly required style="flex: 1;" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                    </div>
                </div>
                <div class="form-group mt-4">
                    <label class="form-label">Reason *</label>
                    <textarea name="reason" class="form-input form-textarea" placeholder="Enter reason for adjustment" required></textarea>
                </div>
                <div class="form-group mt-4">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input form-textarea" placeholder="Enter additional notes (optional)"></textarea>
                </div>
            </div>
            <div class="bg-blue-50 p-4 rounded-lg mt-4 border border-blue-100">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    After saving this header, you will be able to add multiple products to this adjustment.
                </p>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-end gap-4">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" form="form" class="btn btn-primary">Save Header & Continue</button>
        </div>
    `;

    loadAdjustmentOptions();
    if (typeof initModalDatePicker === 'function') {
        initModalDatePicker();
    }
}
function openViewModal(id) {
    openModal('View Stock Adjustment');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/warehouse/stock-adjustments/${id}`, {
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
            const items = data.data.items || [];
            const isAdmin = {{ auth()->user()->canViewAllData() ? 'true' : 'false' }};
            const isApprover = {{ auth()->user()->hasPermission('warehouse.stock-adjustments.approve') ? 'true' : 'false' }};
            const status = data.data.status;
            const canEdit = status === 'draft' || status === 'waiting for approval';

            let itemsHtml = `
                <div class="modal-section mt-6">
                    <div class="modal-section-title flex justify-between items-center">
                        <span>Adjustment Items</span>
                        ${canEdit ? `<button class="btn btn-primary btn-sm" onclick="showAddItemModal(${id}, ${data.data.warehouse_id})"><i class="fas fa-plus mr-1"></i> Add Product</button>` : ''}
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="border p-2 text-left">Item Summary</th>
                                    <th class="border p-2 text-center">Type</th>
                                    <th class="border p-2 text-right">Qty</th>
                                    <th class="border p-2 text-left">Serial Numbers</th>
                                    <th class="border p-2 text-left">Notes</th>
                                </tr>
                            </thead>
                            <tbody id="adjustment-items-body">
                                ${items.map(item => `
                                    <tr>
                                        <td class="border p-2">${item.master_product?.name || '-'}</td>
                                        <td class="border p-2 text-center">
                                            <span class="adjustment-badge ${item.adjustment_type === 'increase' ? 'adjustment-increase' : 'adjustment-decrease'}">
                                                ${item.adjustment_type.toUpperCase()}
                                            </span>
                                        </td>
                                        <td class="border p-2 text-right">${item.adjustment_qty}</td>
                                        <td class="border p-2">${Array.isArray(item.serial_numbers) && item.serial_numbers.length ? item.serial_numbers.map(sn => `<span class="badge badge-info mr-1">${sn}</span>`).join('') : '-'}</td>
                                        <td class="border p-2">${item.notes || '-'}</td>
                                    </tr>
                                `).join('')}
                                ${items.length === 0 ? '<tr><td colspan="5" class="p-4 text-center text-gray-500">No items added yet.</td></tr>' : ''}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Header Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Adjustment No</label>
                            <p class="detail-value">${data.data.adjustment_no || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Warehouse</label>
                            <p class="detail-value">${data.data.warehouse?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value text-uppercase">
                                <span class="status-badge ${status === 'approved' ? 'status-active' : (status === 'rejected' ? 'status-inactive' : 'bg-yellow-100 text-yellow-800')}">
                                    ${status.toUpperCase()}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Date</label>
                            <p class="detail-value">${data.data.adjustment_date ? new Date(data.data.adjustment_date).toLocaleDateString('id-ID') : '-'}</p>
                        </div>
                    </div>
                </div>
                ${itemsHtml}
            `;
        
            let footerHtml = `<button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>`;
            if (status === 'draft' || status === 'waiting for approval') {
                footerHtml += `<button type="button" class="btn btn-primary" onclick="submitAdjustmentForApproval(${id})">Submit for Approval</button>`;
            }
            if (status === 'waiting for approval' && isApprover) {
                footerHtml += `<button type="button" class="btn btn-success" onclick="approveAdjustment(${id})">Approve & Update Stok</button>`;
            }

            document.getElementById('modalFooter').innerHTML = footerHtml;
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
    openModal('Edit Stock Adjustment');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/warehouse/stock-adjustments/${id}/edit`, {
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
                                <label class="form-label">Adjustment No</label>
                                <input type="text" name="adjustment_no" class="form-input" value="${data.data.adjustment_no || ''}" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Adjustment Date *</label>
                                <input type="text" id="editAdjustmentDateInput" name="adjustment_date" class="form-input cursor-pointer" 
                                    value="${data.data.adjustment_date || ''}" required readonly>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Warehouse *</label>
                                <select name="warehouse_id" class="form-input" required>
                                    <option value="">Select Warehouse</option>
                                    <option value="${data.data.warehouse_id}" selected>${data.data.warehouse?.name || 'Current Warehouse'}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-input">
                                    <option value="draft" ${data.data.status === 'draft' ? 'selected' : ''}>Draft</option>
                                    <option value="waiting for approval" ${data.data.status === 'waiting for approval' ? 'selected' : ''}>Waiting for Approval</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reason *</label>
                            <textarea name="reason" class="form-input form-textarea" placeholder="Enter reason for adjustment" required>${data.data.reason || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-input form-textarea" placeholder="Enter additional notes (optional)">${data.data.notes || ''}</textarea>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Header</button>
            `;
            
            if (typeof initModalDatePicker === 'function') {
                initModalDatePicker();
            }
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

    const form = event.target;
    if (form.dataset.submitting === 'true') {
        return;
    }

    form.dataset.submitting = 'true';
    const submitButton = document.querySelector(`button[type="submit"][form="${form.id}"]`);
    const originalSubmitHtml = submitButton ? submitButton.innerHTML : '';
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/warehouse/stock-adjustments/${id}` : '/warehouse/stock-adjustments';
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
    })
    .finally(() => {
        if (!form.isConnected) {
            return;
        }

        form.dataset.submitting = 'false';
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalSubmitHtml;
        }
    });
}

function loadAdjustmentOptions() {
    const warehouseSelect = document.getElementById('modal_warehouse_id');
    if (!warehouseSelect) return;

    console.log('Fetching warehouses...');
    fetch('/warehouse/stock-adjustments/create', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        console.log('Warehouses loaded:', data);
        warehouseSelect.innerHTML = '<option value="">Pilih Warehouse</option>';
        if (data && data.data && data.data.warehouses) {
            data.data.warehouses.forEach(warehouse => {
                const option = document.createElement('option');
                option.value = warehouse.id;
                option.textContent = (warehouse.warehouse_code || '') + ' - ' + warehouse.name;
                warehouseSelect.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Error loading options:', error);
        warehouseSelect.innerHTML = '<option value="">Warehouse tidak berhasil dimuat</option>';
    });
}

function loadProductsByWarehouse(warehouseId) {
    const productSelect = document.getElementById('modal_product_id');
    if (!productSelect) return;

    console.log('Loading products for warehouse:', warehouseId);

    if (!warehouseId) {
        productSelect.innerHTML = '<option value="">Pilih warehouse terlebih dahulu</option>';
        productSelect.disabled = true;
        return;
    }

    productSelect.innerHTML = '<option value="">Memuat produk...</option>';
    productSelect.disabled = true;

    fetch(`/warehouse/stock-adjustments/create?warehouse_id=${warehouseId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        console.log('Products loaded:', data);
        productSelect.innerHTML = '<option value="">Pilih Produk</option>';
        productSelect.disabled = false;
        
        if (data && data.data && data.data.products && data.data.products.length > 0) {
            data.data.products.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                const sku = product.sku ? ` (${product.sku})` : '';
                option.textContent = product.name + sku;
                productSelect.appendChild(option);
            });
        } else {
            productSelect.innerHTML = '<option value="">Tidak ada produk di warehouse ini</option>';
        }
    })
    .catch(error => {
        console.error('Error loading products:', error);
        productSelect.innerHTML = '<option value="">Produk tidak berhasil dimuat</option>';
        productSelect.disabled = false;
    });
}

// Delete Modal functions
function openDeleteModal(id = null) {
    if (id) {
        selectedIdsForRetry = [id];
    }
    
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Apakah Anda yakin ingin menyembunyikan stock adjustment ini? Tindakan ini masih bisa dibatalkan nanti.'
        : `Apakah Anda yakin ingin menyembunyikan ${count} stock adjustment? Tindakan ini masih bisa dibatalkan nanti.`;
    
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
    
    fetch('/warehouse/stock-adjustments/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
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
        showWarningDialog('Pilih minimal satu stock adjustment yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'Stock adjustment berhasil disembunyikan.'
        : `${count} stock adjustment berhasil disembunyikan.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'Stock adjustment tidak berhasil disembunyikan. Silakan coba lagi.';
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

window.stockAdjustmentAddItemProducts = window.stockAdjustmentAddItemProducts || {};

function parseStockAdjustmentSerialNumbers(value) {
    return (value || '')
        .split(/[\s,;]+/)
        .map(sn => sn.trim().toUpperCase())
        .filter(Boolean);
}

function renderAdjustmentSerialFields(productSelectId, typeSelectId, qtyInputId, wrapperId, textareaId, selectId, labelId, helpId) {
    const product = (window.stockAdjustmentAddItemProducts[productSelectId] || {})[document.getElementById(productSelectId).value];
    const type = document.getElementById(typeSelectId).value;
    const qty = parseInt(document.getElementById(qtyInputId).value || '0', 10);
    const wrapper = document.getElementById(wrapperId);
    const textarea = document.getElementById(textareaId);
    const select = document.getElementById(selectId);
    const label = document.getElementById(labelId);
    const help = document.getElementById(helpId);

    textarea.style.display = 'none';
    select.style.display = 'none';
    wrapper.style.display = 'none';
    help.textContent = '';

    if (!product || !product.requires_serial_number) {
        return;
    }

    wrapper.style.display = 'block';
    label.textContent = type === 'increase'
        ? `Serial Numbers Baru (${qty || 0} SN wajib)`
        : `Serial Numbers yang Dikeluarkan (${qty || 0} SN wajib)`;

    if (type === 'increase') {
        textarea.style.display = 'block';
        help.textContent = 'Masukkan SN unit tambahan yang belum pernah terdaftar. Pisahkan dengan baris baru, koma, atau spasi.';
        return;
    }

    select.style.display = 'block';
    select.innerHTML = '';
    (product.available_serial_numbers || []).forEach(sn => {
        const serialNumber = typeof sn === 'string' ? sn : sn.serial_number;
        const serialId = typeof sn === 'string' ? null : sn.id;
        const option = document.createElement('option');
        option.value = serialNumber;
        option.textContent = serialId ? `${serialNumber} (#${serialId})` : serialNumber;
        select.appendChild(option);
    });
    help.textContent = 'Pilih SN/batch ready di warehouse yang akan dikeluarkan dari stok.';
}

function showAddItemModal(adjustmentId, warehouseId) {
    const productSelectId = `product-select-${Date.now()}`;
    const qtyInputId = `qty-input-${Date.now()}`;
    const typeSelectId = `type-select-${Date.now()}`;
    const notesInputId = `notes-input-${Date.now()}`;
    const serialWrapperId = `serial-wrapper-${Date.now()}`;
    const serialTextareaId = `serial-textarea-${Date.now()}`;
    const serialSelectId = `serial-select-${Date.now()}`;
    const serialLabelId = `serial-label-${Date.now()}`;
    const serialHelpId = `serial-help-${Date.now()}`;

    const modalHtml = `
        <div id="addItemModal" class="modal-overlay show" style="z-index: 1060;">
            <div class="modal-container" onclick="event.stopPropagation()" style="max-width: 500px;">
                <div class="modal-header">
                    <h2 class="modal-title">Tambah Produk ke Adjustment</h2>
                    <button class="modal-close" onclick="document.getElementById('addItemModal').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Produk</label>
                        <select id="${productSelectId}" class="form-input"></select>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="form-group">
                            <label class="form-label">Tipe</label>
                            <select id="${typeSelectId}" class="form-input">
                                <option value="increase">Tambah (+)</option>
                                <option value="decrease">Kurang (-)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jumlah</label>
                            <input type="number" id="${qtyInputId}" class="form-input" min="0.01" step="0.01">
                        </div>
                    </div>
                    <div class="form-group mt-4">
                        <label class="form-label">Catatan</label>
                        <input type="text" id="${notesInputId}" class="form-input">
                    </div>
                    <div id="${serialWrapperId}" class="form-group mt-4" style="display:none;">
                        <label id="${serialLabelId}" class="form-label">Serial Numbers</label>
                        <textarea id="${serialTextareaId}" class="form-input" rows="4" style="display:none;" placeholder="Masukkan SN baru, 1 SN per baris"></textarea>
                        <select id="${serialSelectId}" class="form-input" multiple size="7" style="display:none;"></select>
                        <small id="${serialHelpId}" class="text-gray-500 block mt-1"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="document.getElementById('addItemModal').remove()">Batal</button>
                    <button class="btn btn-primary" onclick="submitAddItem(${adjustmentId}, '${productSelectId}', '${typeSelectId}', '${qtyInputId}', '${notesInputId}', '${serialTextareaId}', '${serialSelectId}')">Tambah Item</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    window.stockAdjustmentAddItemProducts[productSelectId] = {};

    [productSelectId, typeSelectId, qtyInputId].forEach(id => {
        const eventName = id === qtyInputId ? 'input' : 'change';
        document.getElementById(id).addEventListener(eventName, () => {
            renderAdjustmentSerialFields(productSelectId, typeSelectId, qtyInputId, serialWrapperId, serialTextareaId, serialSelectId, serialLabelId, serialHelpId);
        });
    });

    // Initialize products
    fetch(`/warehouse/stock-adjustments/create?warehouse_id=${warehouseId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        const select = document.getElementById(productSelectId);
        select.innerHTML = '<option value="">Pilih Produk</option>';
        data.data.products.forEach(p => {
            window.stockAdjustmentAddItemProducts[productSelectId][p.id] = p;
            select.innerHTML += `<option value="${p.id}">${p.name} (${p.sku || ''})</option>`;
        });
        renderAdjustmentSerialFields(productSelectId, typeSelectId, qtyInputId, serialWrapperId, serialTextareaId, serialSelectId, serialLabelId, serialHelpId);
    });
}

function submitAddItem(adjId, pId, tId, qId, nId, serialTextareaId, serialSelectId) {
    const product = (window.stockAdjustmentAddItemProducts[pId] || {})[document.getElementById(pId).value];
    const type = document.getElementById(tId).value;
    const qty = parseInt(document.getElementById(qId).value || '0', 10);
    const serialNumbers = product?.requires_serial_number
        ? (type === 'decrease'
            ? Array.from(document.getElementById(serialSelectId).selectedOptions).map(option => option.value)
            : parseStockAdjustmentSerialNumbers(document.getElementById(serialTextareaId).value))
        : [];

    if (product?.requires_serial_number && serialNumbers.length !== qty) {
        showErrorDialog('Gagal', `Produk ini wajib ${qty} serial number. Saat ini terisi ${serialNumbers.length}.`);
        return;
    }

    const data = {
        master_product_id: document.getElementById(pId).value,
        adjustment_type: type,
        adjustment_qty: qty,
        notes: document.getElementById(nId).value,
        serial_numbers: serialNumbers,
    };

    fetch(`/warehouse/stock-adjustments/${adjId}/add-item`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            document.getElementById('addItemModal').remove();
            openViewModal(adjId); // Refresh view modal
        } else {
            showErrorDialog('Gagal', 'Item tidak berhasil ditambahkan: ' + res.message);
        }
    });
}

function approveAdjustment(id) {
    showConfirmDialog(
        'Setujui Stock Adjustment?',
        'Stok akan diperbarui setelah adjustment disetujui.',
        'Ya, setujui',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;

        fetch(`/warehouse/stock-adjustments/${id}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') location.reload();
            else showErrorDialog('Gagal', res.message);
        });
    });
}

function submitAdjustmentForApproval(id) {
    fetch(`/warehouse/stock-adjustments/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status: 'waiting for approval' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') location.reload();
        else showErrorDialog('Gagal', res.message);
    });
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

// Add CSS for loading spinner
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .flatpickr-calendar {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }
    .flatpickr-day.selected {
        background: #214589 !important;
        border-color: #214589 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// Filter functions and others already in index.blade.php
// Adding flatpickr initialization for modal
function initModalDatePicker() {
    setTimeout(() => {
        flatpickr('#adjustmentDateInput', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/M/Y',
            defaultDate: new Date(),
            allowInput: false
        });
        
        // Also for edit if it exists
        const editDateInput = document.getElementById('editAdjustmentDateInput');
        if (editDateInput) {
            flatpickr(editDateInput, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/M/Y',
                allowInput: false
            });
        }
    }, 100);
}
</script>
@endpush
