@extends('layouts.app')

@section('title', 'Customer Taxes')
@section('breadcrumb', 'Home / Company / Customer Taxes')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 250px; min-width: 250px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 120px; min-width: 120px; }

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
    input[type="date"], input[type="text"], select, textarea {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    input[type="date"]:focus, input[type="text"]:focus, select:focus, textarea:focus {
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
        
        <!-- Customer Taxes Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Customer Tax Settings</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Tax Setting</span>
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
                        <th data-column="customer__name">Customer</th>
                        <th data-column="label">Label / Cabang</th>
                        <th data-column="tax_name">Tax Name</th>
                        <th data-column="tax_number">Tax Number</th>
                        <th data-column="nitku">NITKU</th>
                        <th data-column="tax_type">Tax Type</th>
                        <th data-column="ppn_code">PPN Code</th>
                        <th data-column="tax_rate" data-type="numeric">Tax Rate (%)</th>
                        <th data-column="effective_date" data-type="date">Effective Date</th>
                        <th data-column="is_active">Status</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                <!-- Table Body -->
                <tbody>
                    @forelse($customerTaxes ?? [] as $tax)
                    <tr data-id="{{ $tax->id }}" onclick="openViewModal({{ $tax->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $tax->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $tax->customer->name ?? '-' }}</td>
                        <td>{{ $tax->label ?? '-' }}</td>
                        <td>{{ $tax->tax_name ?? '-' }}</td>
                        <td>{{ $tax->tax_number ?? '-' }}</td>
                        <td>{{ $tax->nitku ?? '-' }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ strtoupper($tax->tax_type ?? '-') }}
                            </span>
                        </td>
                        <td>{{ $tax->ppn_code ?? '-' }}</td>
                        <td>{{ $tax->tax_rate ? number_format($tax->tax_rate, 2) . '%' : '-' }}</td>
                        <td>{{ $tax->effective_date ? $tax->effective_date->format('d/M/Y') : '-' }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $tax->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($tax->status ?? 'Inactive') }}
                            </span>
                        </td>
                        <td>{{ $tax->createdBy->name ?? '-' }}</td>
                        <td>
                            @if($tax->created_at)
                                {{ \Carbon\Carbon::parse($tax->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($tax->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $tax->updatedBy->name ?? '-' }}</td>
                        <td>
                            @if($tax->updated_at)
                                {{ \Carbon\Carbon::parse($tax->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($tax->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-receipt text-4xl mb-3"></i>
                                <p class="text-lg">No customer tax settings found</p>
                                <button class="btn btn-primary mt-2" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Add First Tax Setting
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($customerTaxes->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $customerTaxes->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Customer Tax Setting</h2>
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
        <h3 class="delete-modal-title">Hide Customer Tax Setting</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this customer tax setting? This action can be undone later.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Yes, Hide</button>
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
        <h3 class="delete-modal-title">Hmm... Something Went Wrong</h3>
        <p class="delete-modal-description" id="errorMessage">We couldn't hide the customer tax setting. Please try again.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeErrorModal()">Close</button>
            <button class="btn btn-hide" onclick="retryDelete()">Try Again</button>
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
        <h3 class="delete-modal-title">All Set!</h3>
        <p class="delete-modal-description" id="successMessage">The customer tax setting has been successfully hidden.</p>
    </div>
</div>
@endsection

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;
const customerTaxCodes = @json($financeTaxCodeOptions ?? []);
const customerTaxDefaultVatRate = Number(@json((float) ($defaultVatSetting->tax_rate ?? 0)));

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[char]);
}

function formatCustomerTaxRate(rate) {
    const number = Number(rate || 0);
    return Number.isFinite(number) ? number.toFixed(2) : '0.00';
}

function buildCustomerTaxCodeOptions(selectedCode = '') {
    if (!customerTaxCodes.length) {
        return '<option value="">Tidak ada kode transaksi aktif</option>';
    }

    return '<option value="">Pilih Kode Transaksi</option>' + customerTaxCodes.map(taxCode => {
        const label = taxCode.description || taxCode.customer_status || '';
        const selected = String(taxCode.code) === String(selectedCode) ? 'selected' : '';

        return `<option value="${escapeHtml(taxCode.code)}" ${selected} data-zero-tax="${taxCode.zero_tax ? '1' : '0'}" data-description="${escapeHtml(label)}">${escapeHtml(taxCode.code)} - ${escapeHtml(label)}</option>`;
    }).join('');
}

function syncCustomerTaxCodeFields(prefix = 'create') {
    const select = document.getElementById(`${prefix}_tax_type`);
    if (!select) {
        return;
    }

    const selectedOption = select.options[select.selectedIndex];
    const selectedCode = select.value;
    const rate = selectedCode && selectedOption?.dataset.zeroTax === '1' ? 0 : customerTaxDefaultVatRate;
    const formattedRate = formatCustomerTaxRate(rate);
    const ppnCodeInput = document.getElementById(`${prefix}_ppn_code`);
    const taxRateInput = document.getElementById(`${prefix}_tax_rate`);
    const taxRateDisplay = document.getElementById(`${prefix}_tax_rate_display`);
    const description = document.getElementById(`${prefix}_tax_code_description`);

    if (ppnCodeInput) {
        ppnCodeInput.value = selectedCode;
    }
    if (taxRateInput) {
        taxRateInput.value = formattedRate;
    }
    if (taxRateDisplay) {
        taxRateDisplay.value = `${formattedRate}%`;
    }
    if (description) {
        description.textContent = selectedCode ? (selectedOption?.dataset.description || '') : '';
    }
}

// Select All functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllElement = document.getElementById('selectAll');
    const headerSelectAllElement = document.getElementById('headerSelectAll');
    
    if (selectAllElement) {
        selectAllElement.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            if (headerSelectAllElement) {
                headerSelectAllElement.checked = this.checked;
            }
        });
    }
    
    if (headerSelectAllElement) {
        headerSelectAllElement.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            if (selectAllElement) {
                selectAllElement.checked = this.checked;
            }
        });
    }
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
    openModal('Create New Customer Tax Setting');
    
    // Get customers from Blade variable
    const customers = @json($customers ?? []);
    let customerOptions = '<option value="">Select Customer</option>';
    customers.forEach(customer => {
        customerOptions += `<option value="${customer.id}">${customer.name}</option>`;
    });
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Customer Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Customer *</label>
                        <select name="customer_id" class="form-input" required onchange="fetchCustomerTaxInfo(this.value, 'create')">
                            ${customerOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Label / Nama Cabang (e.g. Pusat, Cabang Sudirman)</label>
                        <input type="text" name="label" class="form-input" placeholder="Masukkan nama cabang untuk membedakan NITKU">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Tax Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Tax Name *</label>
                        <select id="create_tax_name" name="tax_name" class="form-input" required onchange="updateTaxNumberMaxLength('create')">
                            <option value="">Pilih Tax Name</option>
                            <option value="NPWP">NPWP</option>
                            <option value="NIK">NIK</option>
                            <option value="NITKU">NITKU</option>
                            <option value="KITAS/PASSPORT/KTP WNA">KITAS/PASSPORT/KTP WNA</option>
                            <option value="OTHER">OTHER</option>
                        </select>
                        <small class="text-gray-600 mt-1">NPWP: 15-16 digit, NIK: 16 digit</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tax Number *</label>
                        <input type="text" id="create_tax_number" name="tax_number" class="form-input" placeholder="Enter tax number" required oninput="updateTaxNumberCounter('create'); this.value = this.value.replace(/[^0-9]/g, '');" maxlength="30">
                        <small class="text-gray-600 mt-1">
                            Length: <span id="create_tax_number_counter" class="font-semibold text-blue-600">0</span> / <span id="create_tax_number_max" class="font-semibold">30</span> characters
                        </small>
                    </div>

                    <!-- NITKU Field (Hidden by default, shown for NPWP) -->
                    <div class="form-group" id="create_nitku_group" style="display:none;">
                        <label class="form-label">NITKU (6 Digit)</label>
                        <input type="text" id="create_nitku" name="nitku" class="form-input" placeholder="000000" maxlength="6" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        <small class="text-gray-600 mt-1">Default 000000 if empty (NPWP)</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kode Transaksi PPN *</label>
                        <select name="tax_type" id="create_tax_type" class="form-input" required onchange="syncCustomerTaxCodeFields('create')">
                            ${buildCustomerTaxCodeOptions()}
                        </select>
                        <div id="create_tax_code_description" class="text-gray-600 mt-2" style="font-size: 12px; line-height: 1.45; white-space: normal; overflow-wrap: anywhere;"></div>
                        <input type="hidden" name="ppn_code" id="create_ppn_code">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tax Rate (%) *</label>
                        <input type="text" id="create_tax_rate_display" class="form-input bg-gray-100 cursor-not-allowed" readonly tabindex="-1" value="${formatCustomerTaxRate(customerTaxDefaultVatRate)}%">
                        <input type="hidden" name="tax_rate" id="create_tax_rate" value="${formatCustomerTaxRate(customerTaxDefaultVatRate)}">
                    </div>
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
                    <label class="form-label">Tax Address *</label>
                    <textarea name="tax_address" class="form-input form-textarea" placeholder="Enter tax address" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input form-textarea" placeholder="Enter description"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </form>
    `;
    
    // Add modal footer
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" form="form" class="btn btn-primary">Create Tax Setting</button>
    `;

    syncCustomerTaxCodeFields('create');
}

function openViewModal(id) {
    openModal('View Customer Tax Setting');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/company/customer-taxes/${id}`, {
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
                <div class="modal-section">
                    <div class="modal-section-title">Customer Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Customer</label>
                            <p class="detail-value">${data.data.customer?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Tax Name</label>
                            <p class="detail-value">${data.data.tax_name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Tax Number</label>
                            <p class="detail-value">${data.data.tax_number || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Label / Cabang</label>
                            <p class="detail-value">${data.data.label || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">NITKU</label>
                            <p class="detail-value">${data.data.nitku || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Kode Transaksi PPN</label>
                            <p class="detail-value">${data.data.ppn_code || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Tax Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Tax Type</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    ${data.data.tax_type ? data.data.tax_type.toUpperCase() : '-'}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Tax Rate</label>
                            <p class="detail-value">${data.data.tax_rate ? data.data.tax_rate + '%' : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Effective Date</label>
                            <p class="detail-value">${data.data.effective_date ? new Date(data.data.effective_date).toLocaleDateString('id-ID') : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Expiry Date</label>
                            <p class="detail-value">${data.data.expiry_date ? new Date(data.data.expiry_date).toLocaleDateString('id-ID') : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full ${data.data.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${data.data.status ? data.data.status.charAt(0).toUpperCase() + data.data.status.slice(1) : 'Inactive'}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Created By</label>
                            <p class="detail-value">${data.data.created_by?.name || '-'}</p>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Tax Address</label>
                        <p class="detail-value">${data.data.tax_address || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Description</label>
                        <p class="detail-value">${data.data.description || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Created At</label>
                        <p class="detail-value">${data.data.created_at ? new Date(data.data.created_at).toLocaleString('id-ID') : '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Updated At</label>
                        <p class="detail-value">${data.data.updated_at ? new Date(data.data.updated_at).toLocaleString('id-ID') : '-'}</p>
                    </div>
                </div>
            `;
        
        // Add modal footer for view modal
            document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit Tax Setting</button>
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
    openModal('Edit Customer Tax Setting');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/company/customer-taxes/${id}/edit`, {
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
                <form id="form" onsubmit="submitEditForm(event, ${id})">
                    <input type="hidden" name="id" value="${data.data.id}">
                    <div class="modal-section">
                        <div class="modal-section-title">Customer Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Customer *</label>
                                    <select name="customer_id" class="form-input" required onchange="fetchCustomerTaxInfo(this.value, 'edit')">
                                    <option value="">Select Customer</option>
                                    ${data.customers ? data.customers.map(customer => 
                                        `<option value="${customer.id}" ${customer.id == data.data.customer_id ? 'selected' : ''}>${customer.name}</option>`
                                    ).join('') : ''}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Label / Nama Cabang</label>
                                <input type="text" name="label" class="form-input" value="${data.data.label || ''}" placeholder="e.g. Pusat, Cabang Sudirman">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tax Name *</label>
                                <select id="edit_tax_name" name="tax_name" class="form-input" required onchange="updateTaxNumberMaxLength('edit')">
                                    <option value="NPWP" ${data.data.tax_name === 'NPWP' ? 'selected' : ''}>NPWP</option>
                                    <option value="NIK" ${data.data.tax_name === 'NIK' ? 'selected' : ''}>NIK</option>
                                    <option value="NITKU" ${data.data.tax_name === 'NITKU' ? 'selected' : ''}>NITKU</option>
                                    <option value="KITAS/PASSPORT/KTP WNA" ${data.data.tax_name === 'KITAS/PASSPORT/KTP WNA' ? 'selected' : ''}>KITAS/PASSPORT/KTP WNA</option>
                                    <option value="OTHER" ${data.data.tax_name === 'OTHER' ? 'selected' : ''}>OTHER</option>
                                </select>
                                <small class="text-gray-600 mt-1">NPWP: 15-16 digit, NIK: 16 digit</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tax Number *</label>
                                <input type="text" id="edit_tax_number" name="tax_number" class="form-input" value="${data.data.tax_number || ''}" placeholder="Enter tax number" required oninput="updateTaxNumberCounter('edit'); this.value = this.value.replace(/[^0-9]/g, '');" maxlength="30">
                                <small class="text-gray-600 mt-1">
                                    Length: <span id="edit_tax_number_counter" class="font-semibold text-blue-600">0</span> / <span id="edit_tax_number_max" class="font-semibold">30</span> characters
                                </small>
                            </div>
                            
                            <!-- NITKU Field (Hidden by default, shown for NPWP) -->
                            <div class="form-group" id="edit_nitku_group" style="display:none;">
                                <label class="form-label">NITKU (6 Digit)</label>
                                <input type="text" id="edit_nitku" name="nitku" class="form-input" value="${data.data.nitku || ''}" placeholder="000000" maxlength="6" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                <small class="text-gray-600 mt-1">Default 000000 if empty (NPWP)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Tax Information</div>
                        <div class="grid grid-cols-1 gap-6">
                            <div class="form-group">
                                <label class="form-label">Kode Transaksi PPN *</label>
                                <select name="tax_type" id="edit_tax_type" class="form-input" required onchange="syncCustomerTaxCodeFields('edit')">
                                    ${buildCustomerTaxCodeOptions(data.data.tax_type || data.data.ppn_code || '')}
                                </select>
                                <div id="edit_tax_code_description" class="text-gray-600 mt-2" style="font-size: 12px; line-height: 1.45; white-space: normal; overflow-wrap: anywhere;"></div>
                                <input type="hidden" name="ppn_code" id="edit_ppn_code" value="${data.data.ppn_code || data.data.tax_type || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tax Rate (%) *</label>
                                <input type="text" id="edit_tax_rate_display" class="form-input bg-gray-100 cursor-not-allowed" readonly tabindex="-1" value="${formatCustomerTaxRate(data.data.tax_rate)}%">
                                <input type="hidden" name="tax_rate" id="edit_tax_rate" value="${formatCustomerTaxRate(data.data.tax_rate)}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Effective Date *</label>
                                <input type="date" name="effective_date" class="form-input" value="${data.data.effective_date ? data.data.effective_date.split('T')[0] : ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-input" value="${data.data.expiry_date ? data.data.expiry_date.split('T')[0] : ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tax Address *</label>
                                <textarea name="tax_address" class="form-input form-textarea" placeholder="Enter tax address" required>${data.data.tax_address || ''}</textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-input">
                                    <option value="active" ${data.data.is_active ? 'selected' : ''}>Active</option>
                                    <option value="inactive" ${!data.data.is_active ? 'selected' : ''}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Tax Setting</button>
            `;

            syncCustomerTaxCodeFields('edit');
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            `;
        });
}

function submitEditForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/company/customer-taxes/${id}` : '/company/customer-taxes';
    const method = id ? 'PUT' : 'POST';
    
    console.log('Form submission:', { url, method, id, data });
    console.log('Form data entries:', Array.from(formData.entries()));
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-HTTP-Method-Override': method,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                // Handle validation errors
                if (data.errors) {
                    let errorMessage = '';
                    Object.keys(data.errors).forEach(key => {
                        errorMessage += `${data.errors[key].join('\n')}\n`;
                    });
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            text: errorMessage,
                            confirmButtonColor: '#214589',
                        });
                    } else {
                        showErrorDialog(errorMessage);
                    }
                    throw new Error('Validation failed');
                }
                
                // Generic error (e.g. from Exception)
                const msg = data.message || 'Server error';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: msg,
                        confirmButtonColor: '#214589',
                    });
                } else {
                    showErrorDialog(msg);
                }
                throw new Error(msg);
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.status === 'success') {
            closeModal();
            location.reload();
        } else {
            const msg = result.message || 'Something went wrong';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: msg,
                            confirmButtonColor: '#214589',
                        });
            } else {
                showErrorDialog(msg);
            }
        }
    })
    .catch(error => {
        if (error.message !== 'Validation failed') {
            console.error('Error:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: error.message || 'Terjadi kesalahan',
                    confirmButtonColor: '#214589',
                });
            } else {
                showErrorDialog(error.message);
            }
        }
    });
}

// Dynamic data loading functions
function loadCustomers() {
    fetch('/company/customers', {
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
                select.appendChild(option);
            });
        }
    })
    .catch(error => console.error('Error loading customers:', error));
}


// Form submission function
function submitForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Convert boolean values
    if (data.status) {
        data.is_active = data.status === 'active' ? '1' : '0';
    }
    
    const isEdit = form.dataset.edit === 'true';
    const url = isEdit ? `/company/customer-taxes/${form.dataset.id}` : '/company/customer-taxes';
    const method = isEdit ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                // Handle validation errors
                if (data.errors) {
                    let errorMessage = '';
                    Object.keys(data.errors).forEach(key => {
                        errorMessage += `${data.errors[key].join('\n')}\n`;
                    });
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            text: errorMessage,
                            confirmButtonColor: '#214589',
                        });
                    } else {
                        showErrorDialog(errorMessage);
                    }
                    throw new Error('Validation failed');
                }
                
                // Generic error
                const msg = data.message || 'Server error';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: msg,
                        confirmButtonColor: '#214589',
                    });
                } else {
                    showErrorDialog(msg);
                }
                throw new Error(msg);
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.status === 'success') {
            closeModal();
            location.reload();
        } else {
             const msg = result.message || 'Unknown error';
             if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: msg,
                    confirmButtonColor: '#214589',
                });
            } else {
                showErrorDialog(msg);
            }
        }
    })
    .catch(error => {
        if (error.message !== 'Validation failed') {
            console.error('Error:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: error.message || 'Terjadi kesalahan',
                    confirmButtonColor: '#214589',
                });
            } else {
                showErrorDialog(error.message);
            }
        }
    });
}

// Delete Modal functions
function openDeleteModal(id = null) {
    if (id) {
        selectedIdsForRetry = [id];
    }
    
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Apakah Anda yakin ingin menyembunyikan pengaturan pajak customer ini? Tindakan ini masih bisa dibatalkan nanti.'
        : `Apakah Anda yakin ingin menyembunyikan ${count} pengaturan pajak customer? Tindakan ini masih bisa dibatalkan nanti.`;
    
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
    
    // Create a form and submit it
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/company/customer-taxes/bulk-delete';
    
    // Add CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfToken);
    
    // Add IDs
    selectedIdsForRetry.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    // Submit form
    document.body.appendChild(form);
    form.submit();
}

// Bulk operations
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu pengaturan pajak customer yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'Pengaturan pajak customer berhasil disembunyikan.'
        : `${count} pengaturan pajak customer berhasil disembunyikan.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'Pengaturan pajak customer gagal disembunyikan. Silakan coba lagi.';
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

// Add CSS for loading spinner
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Tax Number Character Counter per report-mom5.md
function updateTaxNumberCounter(mode) {
    const inputId = mode === 'create' ? 'create_tax_number' : 'edit_tax_number';
    const counterId = mode === 'create' ? 'create_tax_number_counter' : 'edit_tax_number_counter';
    const maxId = mode === 'create' ? 'create_tax_number_max' : 'edit_tax_number_max';
    
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    const maxDisplay = document.getElementById(maxId);
    
    if (input && counter) {
        const length = input.value.length;
        const maxLength = parseInt(input.getAttribute('maxlength') || 25);
        counter.textContent = length;
        
        // Change color based on whether reached expected length
        if (length === maxLength) {
            counter.className = 'font-semibold text-green-600';
        } else if (length > 0) {
            counter.className = 'font-semibold text-blue-600';
        } else {
            counter.className = 'font-semibold text-gray-600';
        }
    }
}

// Update Tax Number maxlength based on Tax Name selection
function updateTaxNumberMaxLength(mode) {
    const taxNameId = mode === 'create' ? 'create_tax_name' : 'edit_tax_name';
    const taxNumberId = mode === 'create' ? 'create_tax_number' : 'edit_tax_number';
    const maxDisplayId = mode === 'create' ? 'create_tax_number_max' : 'edit_tax_number_max';
    const nitkuGroupId = mode === 'create' ? 'create_nitku_group' : 'edit_nitku_group';
    const nitkuInputId = mode === 'create' ? 'create_nitku' : 'edit_nitku';
    
    const taxNameSelect = document.getElementById(taxNameId);
    const taxNumberInput = document.getElementById(taxNumberId);
    const maxDisplay = document.getElementById(maxDisplayId);
    const nitkuGroup = document.getElementById(nitkuGroupId);
    const nitkuInput = document.getElementById(nitkuInputId);
    
    if (taxNameSelect && taxNumberInput) {
        const taxName = taxNameSelect.value;
        let maxLength = 30; // Default for OTHER
        let showNitku = false;
        
        switch(taxName) {
            case 'NPWP':
                maxLength = 16;
                showNitku = true;
                break;
            case 'NITKU':
                maxLength = 16;
                showNitku = true;
                break;
            case 'NIK':
                maxLength = 16;
                showNitku = false; // NITKU is auto 000000
                break;
            case 'KITAS/PASSPORT/KTP WNA':
                maxLength = 30;
                showNitku = false;
                break;
            default:
                maxLength = 30;
                showNitku = false;
                break;
        }
        
        taxNumberInput.setAttribute('maxlength', maxLength);
        
        // Show/Hide NITKU
        if (nitkuGroup) {
            nitkuGroup.style.display = showNitku ? 'block' : 'none';
            if (nitkuInput) {
                // NITKU is no longer strict required, backend will handle defaults
                if (showNitku && !nitkuInput.value) {
                    nitkuInput.value = '000000'; // Set default for NPWP UI
                }
                if (!showNitku) {
                    nitkuInput.value = ''; // Clear if hidden (e.g. NIK)
                }
            }
        }
        
        // Update max display
        if (maxDisplay) {
            maxDisplay.textContent = maxLength;
        }
        
        // Truncate current value if exceeds new maxlength
        if (taxNumberInput.value.length > maxLength) {
            taxNumberInput.value = taxNumberInput.value.substring(0, maxLength);
        }
        
        // Update counter display
        updateTaxNumberCounter(mode);
        
        console.log(`Tax Name: ${taxName}, Max Length set to: ${maxLength}, NITKU shown: ${showNitku}`);
    }
}

// Initialize counter when edit modal opens
const originalOpenEditModal = openEditModal;
openEditModal = function(id) {
    originalOpenEditModal(id);
    
    // Update counter and maxlength after modal data is loaded
    setTimeout(() => {
        updateTaxNumberMaxLength('edit');
        updateTaxNumberCounter('edit');
    }, 500);
};

// Fetch Customer Tax Info for Auto-fill
function fetchCustomerTaxInfo(customerId, mode) {
    if (!customerId) return;
    
    // Show some loading indicator if needed, or just silent update
    
    fetch(`/company/customer-taxes/get-info/${customerId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.data) {
            const data = result.data;
            const prefix = mode === 'create' ? 'create_' : 'edit_';
            
            // Auto-fill Mapping
            // 1. Tax Name -> Set to NPWP if the source is NPWP
            const taxNameSelect = document.getElementById(`${prefix}tax_name`);
            if (taxNameSelect && data.tax_name === 'NPWP') {
                taxNameSelect.value = 'NPWP';
                // Trigger event to update MaxLength and show NITKU
                taxNameSelect.dispatchEvent(new Event('change')); 
            }
            
            // 2. Tax Number
            const taxNumberInput = document.getElementById(`${prefix}tax_number`);
            if (taxNumberInput) {
                taxNumberInput.value = data.tax_number || '';
                // Update counter
                updateTaxNumberCounter(mode);
            }
            
            // 2.1 NITKU
            const nitkuInput = document.getElementById(`${prefix}nitku`);
            if (nitkuInput && data.nitku) {
                nitkuInput.value = data.nitku;
            }
            
            // 3. Tax Address
            const activeForm = document.getElementById('form');
            const taxAddressInput = activeForm ? activeForm.querySelector('[name="tax_address"]') : null;
            
            if (taxAddressInput) {
                 taxAddressInput.value = data.tax_address || '';
            }
            
            // 4. Label (Optional) - User might want to differentiation
            // But we can suggest "Cabang [Address City]" or just leave it.
            // Requirement was just tax_number. I'll stick to tax_address as well as it's helpful.
            
            // Toast or visual cue?
            // "Data NPWP ditemukan dan diisi otomatis."
        }
    })
    .catch(error => console.error('Error fetching customer tax info:', error));
}
</script>
