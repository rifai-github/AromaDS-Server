@extends('layouts.app')

@section('title', 'Inventory Transfers')
@section('breadcrumb', 'Home / Warehouse / Inventory Transfers')

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
    
    /* Specific delete button styling */
    .btn-delete:hover {
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

    /* Column widths for better layout - Adjust per module */
    .responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 50px; min-width: 50px; }
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 200px; min-width: 200px; }
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
    input[type="date"], input[type="text"], select {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    input[type="date"]:focus, input[type="text"]:focus, select:focus {
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
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }

    .form-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    .form-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .detail-item {
        margin-bottom: 16px;
    }

    .detail-value {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
        padding: 8px 12px;
        background-color: white;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
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
        
        <!-- Inventory Transfers Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Inventory Transfers</h1>
                </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Transfer</span>
                    <span class="md:hidden">Add New</span>
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
                            <p class="text-sm font-normal text-gray-700 w-auto ml-2 cursor-pointer" onclick="document.getElementById('selectAll').click()">Pilih semua</p>
                    </div>
                    </div>
            </div>

                <!-- Delete Button -->
                <button class="btn btn-secondary btn-sm ml-4" onclick="deleteSelected()">
                        <i class="fas fa-trash"></i>
                        <span>Hapus</span>
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
                            <th data-column="transfer_number">Transfer Number</th>
                            <th data-column="transfer_date" data-type="date">Transfer Date</th>
                            <th data-column="fromWarehouse.name">From Warehouse</th>
                            <th data-column="toWarehouse.name">To Warehouse</th>
                            <th data-column="status">Status</th>
                            <th data-column="notes">Notes</th>
                            <th data-column="createdBy__name">Created By</th>
                            <th data-column="created_at" data-type="date">Created At</th>
                            <th data-column="updatedBy__name">Last Updated By</th>
                            <th data-column="updated_at" data-type="date">Last Updated At</th>
                        </tr>
                    </thead>
                
                <!-- Table Body -->
                    <tbody>
                    @forelse($paginatedTransfers ?? [] as $transfer)
                    <tr data-id="{{ $transfer->id }}" onclick="openViewModal({{ $transfer->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $transfer->id }}" onclick="event.stopPropagation()">
                                </td>
                        <td>{{ $transfer->transfer_number ?? '-' }}</td>
                        <td>{{ $transfer->transfer_date ? \Carbon\Carbon::parse($transfer->transfer_date)->format('M d, Y') : '-' }}</td>
                        <td>{{ $transfer->fromWarehouse->name ?? '-' }}</td>
                        <td>{{ $transfer->toWarehouse->name ?? '-' }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $transfer->status == 'received' ? 'bg-green-100 text-green-800' : ($transfer->status == 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ ucfirst(str_replace('-', ' ', $transfer->status ?? 'N/A')) }}
                                    </span>
                                </td>
                        <td class="max-w-xs truncate">{{ $transfer->notes ?? '-' }}</td>
                        <td>{{ $transfer->createdBy->name ?? '-' }}</td>
                        <td>
                            @if($transfer->created_at)
                                {{ \Carbon\Carbon::parse($transfer->created_at)->format('d M Y') }}<br>
                                at {{ \Carbon\Carbon::parse($transfer->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $transfer->updatedBy->name ?? '-' }}</td>
                        <td>
                            @if($transfer->updated_at)
                                {{ \Carbon\Carbon::parse($transfer->updated_at)->format('d M Y') }}<br>
                                at {{ \Carbon\Carbon::parse($transfer->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                            </tr>
                        @empty
                            <tr>
                        <td colspan="7" class="p-8 text-center">
                            <p class="text-lg text-gray-600">Belum ada inventory transfer</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if(isset($paginatedTransfers) && $paginatedTransfers->currentPage() > 1)
                    <a href="{{ $paginatedTransfers->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if(isset($paginatedTransfers) && $paginatedTransfers->hasPages())
                    @php
                        $start = max(1, $paginatedTransfers->currentPage() - 2);
                        $end = min($paginatedTransfers->lastPage(), $paginatedTransfers->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $paginatedTransfers->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $paginatedTransfers->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $paginatedTransfers->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $paginatedTransfers->lastPage())
                            @if($end < $paginatedTransfers->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $paginatedTransfers->url($paginatedTransfers->lastPage()) }}" class="page-number">{{ $paginatedTransfers->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if(isset($paginatedTransfers) && $paginatedTransfers->hasMorePages())
                    <a href="{{ $paginatedTransfers->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $paginatedTransfers->currentPage() ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $paginatedTransfers->lastPage() ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Lihat Inventory Transfer</h2>
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
        <h3 class="delete-modal-title">Sembunyikan Inventory Transfer</h3>
        <p class="delete-modal-description" id="deleteMessage">Apakah Anda yakin ingin menyembunyikan inventory transfer ini? Tindakan ini masih bisa dibatalkan nanti.</p>
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
        <p class="delete-modal-description" id="errorMessage">Inventory transfer gagal disembunyikan. Silakan coba lagi.</p>
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
        <p class="delete-modal-description" id="successMessage">Inventory transfer berhasil disembunyikan.</p>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;

// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    console.log('🔍 Debug - Select All clicked, checking all:', this.checked);
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    document.getElementById('headerSelectAll').checked = this.checked;
});

document.getElementById('headerSelectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    console.log('🔍 Debug - Header Select All clicked, checking all:', this.checked);
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
    console.log('🔍 Debug - Checked checkboxes:', checkboxes.length);
    
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu inventory transfer yang ingin disembunyikan.');
        return;
    }
    
    // Only get IDs from actually checked checkboxes
    selectedIdsForRetry = Array.from(checkboxes)
        .filter(cb => cb.checked)  // Double check that they are actually checked
        .map(cb => cb.value);
    
    console.log('🔍 Debug - Selected IDs:', selectedIdsForRetry);
    console.log('🔍 Debug - Selected IDs count:', selectedIdsForRetry.length);
    openDeleteModal();
}

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
    openModal('Tambah Inventory Transfer');
    
    // Reset addedItems
    addedItems = [];
    
    // Show loading state
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    // Load dynamic data
    fetch('{{ route("warehouse.inventory-transfers.api.warehouses") }}', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(warehousesResponse => {
        const warehouses = warehousesResponse.data || [];
        
        let warehousesOptions = '<option value="">Pilih Warehouse</option>';
        warehouses.forEach(warehouse => {
            warehousesOptions += `<option value="${warehouse.id}">${warehouse.name}</option>`;
        });
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Transfer Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Transfer Number *</label>
                        <input type="text" name="transfer_number" class="form-input" placeholder="Auto-generated" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Transfer Date *</label>
                        <input type="date" name="transfer_date" class="form-input" required>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Warehouse Details</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">From Warehouse *</label>
                        <select name="from_warehouse_id" id="from_warehouse_id" class="form-input" required onchange="loadTransferWarehouses()">
                            ${warehousesOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">To Warehouse *</label>
                        <select name="to_warehouse_id" id="to_warehouse_id" class="form-input" required>
                            <option value="">Pilih Warehouse Asal Terlebih Dahulu</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Transfer Items</div>
                
                <!-- Add New Item Form -->
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-4">Add New Item</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="form-group">
                            <label class="form-label">Product *</label>
                            <select id="new-product-select" class="form-input" onchange="updateNewItemStock(this)">
                                <option value="">Pilih Produk</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Available Stock</label>
                            <input type="text" id="new-available-stock" class="form-input" readonly placeholder="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Quantity *</label>
                            <input type="number" id="new-quantity" class="form-input" step="0.01" min="0.01" placeholder="0">
                        </div>
                        <div class="form-group">
                            <button type="button" class="btn btn-primary btn-sm" onclick="addItemToList()">
                                <i class="fas fa-plus"></i> Tambah Item
                            </button>
                        </div>
                    </div>
                </div>
                
            <!-- Added Items List -->
            <div id="transfer-items-container">
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-700">Item untuk Ditransfer</h4>
                        <span id="items-count" class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">0 item</span>
                    </div>
                </div>
                <div id="items-list" class="space-y-2">
                    <p class="text-gray-500 text-center py-4" id="no-items-message">Belum ada item yang ditambahkan</p>
                </div>
            </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Transfer Details</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <option value="draft">Draft</option>
                            <option value="transferred">Transferred</option>
                            <option value="received">Received</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="3" placeholder="Masukkan catatan transfer"></textarea>
                </div>
            </div>
        </form>
    `;
        
        // Add modal footer
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button type="submit" form="form" class="btn btn-primary">Tambah Transfer</button>
        `;
    })
    .catch(error => {
        console.error('Error loading data:', error);
        document.getElementById('modalBody').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <p style="color: #e74c3c;">Gagal memuat data. Silakan coba lagi.</p>
                <button onclick="openCreateModal()" class="btn btn-primary">Coba Lagi</button>
            </div>
        `;
    });
}

function openViewModal(id) {
    openModal('Lihat Inventory Transfer');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    // Debug: Test basic data access (for troubleshooting)
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        fetch(`{{ url('debug-transfer') }}/${id}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(debugData => {
            console.log('🔍 Debug - Basic data access:', debugData);
        })
        .catch(error => {
            console.error('❌ Debug - Basic data error:', error);
        });
    }
    
    // Fetch actual data from API
    fetch(`{{ url('warehouse/inventory-transfers/api/get-transfer') }}/${id}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(response => {
        console.log('🔍 API Response:', response);
        if (response.status === 'success') {
            const data = response.data;
            console.log('🔍 Transfer Data:', data);
            console.log('🔍 Transfer Items:', data.transfer_items);
        
                // Build transfer items HTML
                let transferItemsHtml = '';
                if (data.transfer_items && data.transfer_items.length > 0) {
                    transferItemsHtml = `
                        <div class="modal-section">
                            <div class="modal-section-title">Transfer Items</div>
                            <div class="space-y-4">
                    `;
                    
                    data.transfer_items.forEach((item, index) => {
                        transferItemsHtml += `
                            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="space-y-2">
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Product</label>
                                        <p class="text-sm font-medium text-gray-900 leading-relaxed">${item.product?.name || '-'}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">SKU Code</label>
                                        <p class="text-sm text-gray-600 font-mono leading-relaxed">${item.product?.sku_code || '-'}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantity</label>
                                        <div class="flex justify-start md:justify-end">
                                            <span class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                                ${item.quantity || '0'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    transferItemsHtml += `
                            </div>
                        </div>
                    `;
                } else {
                    transferItemsHtml = `
                        <div class="modal-section">
                            <div class="modal-section-title">Transfer Items</div>
                            <p class="text-gray-500 text-center py-4">Belum ada item transfer</p>
                        </div>
                    `;
                }

                document.getElementById('modalBody').innerHTML = `
                    <div class="modal-section">
                        <div class="modal-section-title">Transfer Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="detail-item">
                                <label class="form-label">Transfer Number</label>
                                <p class="detail-value">${data.transfer_number || '-'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Transfer Date</label>
                                <p class="detail-value">${data.transfer_date ? new Date(data.transfer_date).toLocaleDateString() : '-'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Warehouse Details</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="detail-item">
                                <label class="form-label">From Warehouse</label>
                                <p class="detail-value">${data.from_warehouse?.name || data.fromWarehouse?.name || '-'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">To Warehouse</label>
                                <p class="detail-value">${data.to_warehouse?.name || data.toWarehouse?.name || '-'}</p>
                            </div>
                        </div>
                    </div>
                    
                    ${transferItemsHtml}
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Transfer Details</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="detail-item">
                                <label class="form-label">Status</label>
                                <p class="detail-value">
                                    <span class="px-2 py-1 text-xs rounded-full ${data.status == 'received' ? 'bg-green-100 text-green-800' : (data.status == 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800')}">
                                        ${data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1).replace('-', ' ') : '-'}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Notes</label>
                            <p class="detail-value">${data.notes || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Created At</label>
                            <p class="detail-value">${data.created_at ? new Date(data.created_at).toLocaleString() : '-'}</p>
                        </div>
                    </div>
                `;
        
            // Add modal footer for view modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit Transfer</button>
            `;
        } else {
            document.getElementById('modalBody').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <p style="color: #e74c3c;">Gagal memuat data transfer.</p>
                    <button onclick="openViewModal(${id})" class="btn btn-primary">Coba Lagi</button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading transfer:', error);
        document.getElementById('modalBody').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <p style="color: #e74c3c;">Gagal memuat data transfer. Silakan coba lagi.</p>
                <button onclick="openViewModal(${id})" class="btn btn-primary">Coba Lagi</button>
            </div>
        `;
    });
}

function openEditModal(id) {
    openModal('Edit Inventory Transfer');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    // Load dynamic data
    Promise.all([
        fetch(`{{ url('warehouse/inventory-transfers/api/get-transfer') }}/${id}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        }),
        fetch('{{ route("warehouse.inventory-transfers.api.warehouses") }}', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
    ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([transferResponse, warehousesResponse]) => {
        if (transferResponse.status === 'success') {
            const data = transferResponse.data;
            const warehouses = warehousesResponse.data || [];
        
            let warehousesOptions = '<option value="">Pilih Warehouse</option>';
            warehouses.forEach(warehouse => {
                const selected = (data.from_warehouse_id == warehouse.id || data.fromWarehouse?.id == warehouse.id) ? 'selected' : '';
                warehousesOptions += `<option value="${warehouse.id}" ${selected}>${warehouse.name}</option>`;
            });
            
            let toWarehousesOptions = '<option value="">Pilih Warehouse</option>';
            warehouses.forEach(warehouse => {
                const selected = (data.to_warehouse_id == warehouse.id || data.toWarehouse?.id == warehouse.id) ? 'selected' : '';
                toWarehousesOptions += `<option value="${warehouse.id}" ${selected}>${warehouse.name}</option>`;
            });
            
        
            document.getElementById('modalBody').innerHTML = `
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="modal-section">
                        <div class="modal-section-title">Transfer Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Transfer Number</label>
                                <input type="text" name="transfer_number" class="form-input" value="${data.transfer_number || ''}" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Transfer Date *</label>
                                <input type="date" name="transfer_date" class="form-input" value="${data.transfer_date ? data.transfer_date.split('T')[0] : ''}" required>
                            </div>
                        </div>
                    </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Warehouse Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">From Warehouse *</label>
                            <select name="from_warehouse_id" id="edit_from_warehouse_id" class="form-input" required onchange="loadEditTransferWarehouses()">
                                ${warehousesOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">To Warehouse *</label>
                            <select name="to_warehouse_id" id="edit_to_warehouse_id" class="form-input" required>
                                ${toWarehousesOptions}
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Transfer Items</div>
                    <div id="edit-transfer-items-container">
                        ${buildEditTransferItemsHtml(data.transfer_items || [])}
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addEditTransferItem()">
                        <i class="fas fa-plus"></i> Tambah Item
                    </button>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Transfer Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input">
                                <option value="draft" ${data.status == 'draft' ? 'selected' : ''}>Draft</option>
                                <option value="transferred" ${data.status == 'transferred' ? 'selected' : ''}>Transferred</option>
                                <option value="received" ${data.status == 'received' ? 'selected' : ''}>Received</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="3" placeholder="Masukkan catatan transfer">${data.notes || ''}</textarea>
                    </div>
                </div>
            </form>
        `;
        
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" form="form" class="btn btn-primary">Perbarui Transfer</button>
            `;
        } else {
            document.getElementById('modalBody').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <p style="color: #e74c3c;">Gagal memuat data transfer.</p>
                    <button onclick="openEditModal(${id})" class="btn btn-primary">Coba Lagi</button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading data:', error);
        document.getElementById('modalBody').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <p style="color: #e74c3c;">Gagal memuat data. Silakan coba lagi.</p>
                <button onclick="openEditModal(${id})" class="btn btn-primary">Coba Lagi</button>
            </div>
        `;
    });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    // Use addedItems for create modal, or collect from form for edit modal
    let items = [];
    
    if (typeof addedItems !== 'undefined' && addedItems.length > 0) {
        // Create modal - use addedItems
        items = addedItems.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity
        }));
    } else {
        // Edit modal - collect from form
        const itemRows = document.querySelectorAll('.transfer-item-row');
        itemRows.forEach((row, index) => {
            const productSelect = row.querySelector('.product-select');
            const quantityInput = row.querySelector('input[name*="[quantity]"]');
            
            if (productSelect.value && quantityInput.value) {
                items.push({
                    product_id: productSelect.value,
                    quantity: parseFloat(quantityInput.value)
                });
            }
        });
    }
    
    // Add items to data
    data.items = items;
    
    // Validate items
    if (items.length === 0) {
        showWarningDialog('Tambahkan minimal satu item transfer.');
        return;
    }
    
    const url = id ? `{{ url('warehouse/inventory-transfers/api') }}/${id}/update` : '{{ route('warehouse.inventory-transfers.api.store') }}';
    const method = id ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
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
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Apakah Anda yakin ingin menyembunyikan inventory transfer ini? Tindakan ini masih bisa dibatalkan nanti.'
        : `Apakah Anda yakin ingin menyembunyikan ${count} inventory transfer? Tindakan ini masih bisa dibatalkan nanti.`;
    
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
    
    console.log('🔍 Debug - Sending delete request for IDs:', selectedIdsForRetry);
    
    fetch('{{ route('warehouse.inventory-transfers.api.bulk-delete') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => {
        console.log('🔍 Debug - Response status:', response.status);
        return response.json();
    })
    .then(result => {
        console.log('🔍 Debug - Response result:', result);
        if (result.success) {
            showSuccessModal(result.count);
        } else {
            showErrorModal(result.message);
        }
    })
    .catch(error => {
        console.error('❌ Debug - Delete error:', error);
        showErrorModal('Terjadi kesalahan jaringan.');
    });
}

function deleteSingle(id) {
    // Uncheck all checkboxes first
    const allCheckboxes = document.querySelectorAll('.row-checkbox');
    allCheckboxes.forEach(cb => cb.checked = false);
    
    // Uncheck select all checkboxes
    document.getElementById('selectAll').checked = false;
    document.getElementById('headerSelectAll').checked = false;
    
    // Set only the target checkbox as selected
    const checkbox = document.querySelector(`input[value="${id}"]`);
    if (checkbox) {
        checkbox.checked = true;
    }
    
    // Set only this ID for deletion
    selectedIdsForRetry = [id];
    console.log('🔍 Debug - Single delete for ID:', id);
    openDeleteModal();
}

function retryDelete() {
    closeErrorModal();
    confirmDelete();
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'Inventory transfer berhasil disembunyikan.'
        : `${count} inventory transfer berhasil disembunyikan.`;
    
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Reset all checkboxes and select all
    resetAllCheckboxes();
    
    // Auto close after 3 seconds
    successModalTimer = setTimeout(() => {
        closeSuccessModal();
        location.reload();
    }, 3000);
}

// Function to reset all checkboxes
function resetAllCheckboxes() {
    const allCheckboxes = document.querySelectorAll('.row-checkbox');
    allCheckboxes.forEach(cb => cb.checked = false);
    
    document.getElementById('selectAll').checked = false;
    document.getElementById('headerSelectAll').checked = false;
    
    selectedIdsForRetry = [];
    console.log('🔍 Debug - All checkboxes reset');
}

// Transfer Items Functions
let transferItemIndex = 0;

function loadTransferWarehouses() {
    const fromWarehouseId = document.getElementById('from_warehouse_id').value;
    const toWarehouseSelect = document.getElementById('to_warehouse_id');
    
    if (!fromWarehouseId) {
        toWarehouseSelect.innerHTML = '<option value="">Pilih Warehouse Asal Terlebih Dahulu</option>';
        return;
    }
    
    // Load transfer warehouses based on business rules
    fetch(`{{ url('warehouse/inventory-transfers/api/transfer-warehouses') }}/${fromWarehouseId}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            const warehouses = result.data || [];
            let options = '<option value="">Pilih Warehouse Tujuan</option>';
            warehouses.forEach(warehouse => {
                options += `<option value="${warehouse.id}">${warehouse.name}</option>`;
            });
            toWarehouseSelect.innerHTML = options;
            
            // Load products for the from warehouse
            loadProductsForWarehouse(fromWarehouseId);
        }
    })
    .catch(error => {
        console.error('Error loading transfer warehouses:', error);
    });
}

function loadProductsForWarehouse(warehouseId) {
    const productSelect = document.getElementById('new-product-select');
    
    fetch(`{{ url('warehouse/inventory-transfers/api/products') }}/${warehouseId}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            const products = result.data || [];
            let options = '<option value="">Pilih Produk</option>';
            products.forEach(product => {
                options += `<option value="${product.master_product_id}" data-stock="${product.quantity}" data-name="${product.master_product.name}">${product.master_product.name} (${product.quantity})</option>`;
            });
            
            productSelect.innerHTML = options;
        }
    })
    .catch(error => {
        console.error('Error loading products:', error);
    });
}

function updateNewItemStock(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const stock = selectedOption.getAttribute('data-stock') || '0';
    document.getElementById('new-available-stock').value = stock;
    document.getElementById('new-quantity').max = stock;
}

// Global variable to store added items
let addedItems = [];

function addItemToList() {
    const productSelect = document.getElementById('new-product-select');
    const quantityInput = document.getElementById('new-quantity');
    const availableStock = document.getElementById('new-available-stock');
    
    // Validation
    if (!productSelect.value) {
        showWarningDialog('Pilih produk terlebih dahulu.');
        return;
    }
    
    if (!quantityInput.value || parseFloat(quantityInput.value) <= 0) {
        showWarningDialog('Masukkan jumlah yang valid.');
        return;
    }
    
    const quantity = parseFloat(quantityInput.value);
    const stock = parseFloat(availableStock.value);
    
    if (quantity > stock) {
        showWarningDialog(`Jumlah tidak boleh melebihi stok tersedia (${stock}).`);
        return;
    }
    
    // Check if product already added
    const existingItem = addedItems.find(item => item.product_id === productSelect.value);
    if (existingItem) {
        showWarningDialog('Produk ini sudah ditambahkan ke daftar.');
        return;
    }
    
    // Get product info
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const productName = selectedOption.getAttribute('data-name');
    
    // Add to items list
    const newItem = {
        product_id: productSelect.value,
        product_name: productName,
        quantity: quantity,
        available_stock: stock
    };
    
    addedItems.push(newItem);
    
    // Update UI
    updateItemsList();
    
    // Clear form
    productSelect.value = '';
    document.getElementById('new-available-stock').value = '';
    document.getElementById('new-quantity').value = '';
}

function updateItemsList() {
    const itemsList = document.getElementById('items-list');
    const itemsCount = document.getElementById('items-count');
    
    // Update items counter
    if (itemsCount) {
        const count = addedItems.length;
        itemsCount.textContent = `${count} item${count !== 1 ? 's' : ''}`;
    }
    
    if (addedItems.length === 0) {
        itemsList.innerHTML = '<p class="text-gray-500 text-center py-4" id="no-items-message">Belum ada item yang ditambahkan</p>';
        return;
    }
    
    let html = '';
    addedItems.forEach((item, index) => {
        html += `
            <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg p-4 mb-3">
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 text-sm">${item.product_name}</p>
                            <p class="text-xs text-gray-500 mt-1">Available: ${item.available_stock}</p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="font-semibold text-blue-600 text-sm">Qty Transfer: ${item.quantity}</p>
                        </div>
                    </div>
                </div>
                <button type="button" class="ml-4 text-red-600 hover:text-red-800 p-2 hover:bg-red-50 rounded-full transition-colors" onclick="removeItemFromList(${index})" title="Remove item">
                    <i class="fas fa-trash text-sm"></i>
                </button>
            </div>
        `;
    });
    
    itemsList.innerHTML = html;
}

function removeItemFromList(index) {
    if (index >= 0 && index < addedItems.length) {
        addedItems.splice(index, 1);
        updateItemsList();
    }
}

function validateStock(inputElement) {
    const quantity = parseFloat(inputElement.value) || 0;
    const stockInput = inputElement.closest('.transfer-item-row').querySelector('.available-stock');
    const availableStock = parseFloat(stockInput.value) || 0;
    
    if (quantity > availableStock) {
        showWarningDialog(`Jumlah tidak boleh melebihi stok tersedia (${availableStock}).`);
        inputElement.value = availableStock;
    }
}

function addTransferItem() {
    transferItemIndex++;
    const container = document.getElementById('transfer-items-container');
    const newRow = document.createElement('div');
    newRow.className = 'transfer-item-row grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4';
    newRow.innerHTML = `
        <div class="form-group">
            <label class="form-label">Product *</label>
            <select name="items[${transferItemIndex}][product_id]" class="form-input product-select" required onchange="updateAvailableStock(this)">
                <option value="">Pilih Produk</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Available Stock</label>
            <input type="text" class="form-input available-stock" readonly placeholder="0">
        </div>
        <div class="form-group">
            <label class="form-label">Quantity *</label>
            <input type="number" name="items[${transferItemIndex}][quantity]" class="form-input" step="0.01" min="0.01" required onchange="validateStock(this)">
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeTransferItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(newRow);
    
    // Load products for the new select
    const fromWarehouseId = document.getElementById('from_warehouse_id').value;
    if (fromWarehouseId) {
        loadProductsForWarehouse(fromWarehouseId);
    }
    
    // Show delete buttons for all rows
    document.querySelectorAll('.transfer-item-row .btn-danger').forEach(btn => {
        btn.style.display = 'block';
    });
}

function removeTransferItem(buttonElement) {
    const row = buttonElement.closest('.transfer-item-row');
    row.remove();
    
    // Hide delete button if only one row left
    const remainingRows = document.querySelectorAll('.transfer-item-row');
    if (remainingRows.length === 1) {
        remainingRows[0].querySelector('.btn-danger').style.display = 'none';
    }
}

// Edit Modal Functions
let editTransferItemIndex = 0;

function buildEditTransferItemsHtml(transferItems) {
    if (!transferItems || transferItems.length === 0) {
        return `
            <div class="transfer-item-row grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4">
                <div class="form-group">
                    <label class="form-label">Product *</label>
                    <select name="items[0][product_id]" class="form-input product-select" required onchange="updateAvailableStock(this)">
                        <option value="">Pilih Produk</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Available Stock</label>
                    <input type="text" class="form-input available-stock" readonly placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="items[0][quantity]" class="form-input" step="0.01" min="0.01" required onchange="validateStock(this)">
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeEditTransferItem(this)" style="display: none;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    let html = '';
    transferItems.forEach((item, index) => {
        editTransferItemIndex = Math.max(editTransferItemIndex, index);
        html += `
            <div class="transfer-item-row grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4">
                <div class="form-group">
                    <label class="form-label">Product *</label>
                    <select name="items[${index}][product_id]" class="form-input product-select" required onchange="updateAvailableStock(this)">
                        <option value="">Pilih Produk</option>
                        <option value="${item.product_id}" selected>${item.product?.name || 'Unknown Product'}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Available Stock</label>
                    <input type="text" class="form-input available-stock" readonly placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="items[${index}][quantity]" class="form-input" step="0.01" min="0.01" value="${item.quantity || 0}" required onchange="validateStock(this)">
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeEditTransferItem(this)" ${transferItems.length === 1 ? 'style="display: none;"' : ''}>
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    return html;
}

function loadEditTransferWarehouses() {
    const fromWarehouseId = document.getElementById('edit_from_warehouse_id').value;
    const toWarehouseSelect = document.getElementById('edit_to_warehouse_id');
    
    if (!fromWarehouseId) {
        toWarehouseSelect.innerHTML = '<option value="">Pilih Warehouse Asal Terlebih Dahulu</option>';
        return;
    }
    
    // Load transfer warehouses based on business rules
    fetch(`{{ url('warehouse/inventory-transfers/api/transfer-warehouses') }}/${fromWarehouseId}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            const warehouses = result.data || [];
            let options = '<option value="">Pilih Warehouse Tujuan</option>';
            warehouses.forEach(warehouse => {
                const selected = document.getElementById('edit_to_warehouse_id').dataset.selectedValue == warehouse.id ? 'selected' : '';
                options += `<option value="${warehouse.id}" ${selected}>${warehouse.name}</option>`;
            });
            toWarehouseSelect.innerHTML = options;
            
            // Load products for the from warehouse
            loadEditProductsForWarehouse(fromWarehouseId);
        }
    })
    .catch(error => {
        console.error('Error loading transfer warehouses:', error);
    });
}

function loadEditProductsForWarehouse(warehouseId) {
    const productSelects = document.querySelectorAll('#edit-transfer-items-container .product-select');
    
    fetch(`{{ url('warehouse/inventory-transfers/api/products') }}/${warehouseId}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            const products = result.data || [];
            let options = '<option value="">Pilih Produk</option>';
            products.forEach(product => {
                options += `<option value="${product.master_product_id}" data-stock="${product.quantity}">${product.master_product.name} (${product.quantity})</option>`;
            });
            
            productSelects.forEach(select => {
                const currentValue = select.value;
                select.innerHTML = options;
                if (currentValue) {
                    select.value = currentValue;
                }
            });
        }
    })
    .catch(error => {
        console.error('Error loading products:', error);
    });
}

function addEditTransferItem() {
    editTransferItemIndex++;
    const container = document.getElementById('edit-transfer-items-container');
    const newRow = document.createElement('div');
    newRow.className = 'transfer-item-row grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-4';
    newRow.innerHTML = `
        <div class="form-group">
            <label class="form-label">Product *</label>
            <select name="items[${editTransferItemIndex}][product_id]" class="form-input product-select" required onchange="updateAvailableStock(this)">
                <option value="">Pilih Produk</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Available Stock</label>
            <input type="text" class="form-input available-stock" readonly placeholder="0">
        </div>
        <div class="form-group">
            <label class="form-label">Quantity *</label>
            <input type="number" name="items[${editTransferItemIndex}][quantity]" class="form-input" step="0.01" min="0.01" required onchange="validateStock(this)">
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeEditTransferItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(newRow);
    
    // Load products for the new select
    const fromWarehouseId = document.getElementById('edit_from_warehouse_id').value;
    if (fromWarehouseId) {
        loadEditProductsForWarehouse(fromWarehouseId);
    }
    
    // Show delete buttons for all rows
    document.querySelectorAll('#edit-transfer-items-container .transfer-item-row .btn-danger').forEach(btn => {
        btn.style.display = 'block';
    });
}

function removeEditTransferItem(buttonElement) {
    const row = buttonElement.closest('.transfer-item-row');
    row.remove();
    
    // Hide delete button if only one row left
    const remainingRows = document.querySelectorAll('#edit-transfer-items-container .transfer-item-row');
    if (remainingRows.length === 1) {
        remainingRows[0].querySelector('.btn-danger').style.display = 'none';
    }
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
    document.getElementById('errorMessage').textContent = message || 'Inventory transfer gagal disembunyikan. Silakan coba lagi.';
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
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

// Click outside to close modals
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('deleteModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

document.getElementById('errorModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeErrorModal();
    }
});

document.getElementById('successModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuccessModal();
        location.reload();
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
