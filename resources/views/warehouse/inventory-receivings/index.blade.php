@extends('layouts.app')

@section('title', 'Inventory Receivings')
@section('breadcrumb', 'Home / Warehouse / Inventory Receivings')

@section('content')
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">

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

    /* Column widths for better layout */
    .responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 50px; min-width: 50px; }
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 200px; min-width: 200px; }

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
        display: flex;
        flex-direction: column;
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
        flex-shrink: 0;
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
        flex: 1;
        min-height: 0;
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
        flex-shrink: 0;
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
        
        /* Filter Row Responsive */
        .grid.grid-cols-1 {
            grid-template-columns: 1fr;
        }
        
        @media (min-width: 768px) {
            .grid.grid-cols-1.md\:grid-cols-2 {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .grid.grid-cols-1.lg\:grid-cols-3 {
                grid-template-columns: repeat(3, 1fr);
            }
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

    /* Status Badge Styles */
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-approved {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-rejected {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .status-completed {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .status-received {
        background-color: #e0e7ff;
        color: #3730a3;
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Module Header - Improved -->
        <div class="w-full bg-gradient-to-r from-[#214589] to-[#1e3a8a] rounded-t-[10px] shadow-lg">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-6">
                <div class="flex items-center gap-3 flex-1">
                    <div class="bg-white/10 backdrop-blur-sm p-3 rounded-lg">
                        <i class="fas fa-box-open text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Inventory Receivings</h1>
                        <p class="text-blue-100 text-sm mt-1">Track and manage incoming inventory from central warehouse</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-lg whitespace-nowrap">
                    <i class="fas fa-info-circle text-white"></i>
                    <span class="text-white text-sm font-medium">Auto-created from requests</span>
                </div>
            </div>
        </div>
        
        <!-- Clean Filter Section -->
        <div id="filterSection" class="w-full bg-white border-b border-gray-200" style="padding: 24px 40px;">
            <div class="flex flex-wrap items-center justify-end" style="gap: 48px;">
                <!-- Date From Group -->
                <div class="flex items-center" style="gap: 20px;">
                    <span style="font-size: 14px; font-weight: 600; color: #374151;">Dari</span>
                    <input type="text" id="filterDateFrom" 
                        class="cursor-pointer"
                        style="background: #f9fafb; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #374151; width: 160px; outline: none;"
                        data-date="{{ request('date_from', now()->toDateString()) }}"
                        readonly>
                </div>

                <!-- Date To Group -->
                <div class="flex items-center" style="gap: 20px;">
                    <span style="font-size: 14px; font-weight: 600; color: #374151;">Sampai</span>
                    <input type="text" id="filterDateTo" 
                        class="cursor-pointer"
                        style="background: #f9fafb; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #374151; width: 160px; outline: none;"
                        data-date="{{ request('date_to', now()->addDays(14)->toDateString()) }}"
                        readonly>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center" style="gap: 24px;">
                    <button type="button" 
                        style="background: #214589; color: white; padding: 14px 40px; font-size: 14px; font-weight: 700; border-radius: 16px; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);"
                        onclick="applyFilters()">
                        Apply
                    </button>
                    
                    <button type="button" 
                        style="background: #3b82f6; color: white; padding: 14px 40px; font-size: 14px; font-weight: 700; border-radius: 16px; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);"
                        onclick="resetFilters()">
                        Reset
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white controls-row">
            <div class="flex flex-row justify-start items-center w-full controls-left">
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
        
        <!-- Table Container -->
        <div class="w-full bg-white rounded-b-[10px] table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="receiving_number">Receiving No</th>
                        <th data-column="id">Kode</th>
                        <th data-column="branch__name">Cabang</th>
                        <th data-column="status">Status</th>
                        <th data-column="created_at" data-type="date">Tanggal Transaksi</th>
                        <th data-column="receive_date" data-type="date">Tanggal Penerimaan</th>
                        <th data-column="reference_no">Nomor Referensi</th>
                        <th data-column="received_from">Diterima Oleh</th>
                        <th data-column="notes">Catatan Tambahan</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($receivings ?? [] as $receiving)
                    <tr data-id="{{ $receiving->id }}" onclick="window.location='{{ route('warehouse.inventory-receivings.show', $receiving->id) }}'">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $receiving->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $receiving->receiving_number ?? '-' }}</td>
                        <td>{{ $receiving->id }}</td>
                        <td>{{ $receiving->branch_name }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $receiving->status == 'received' ? 'bg-green-100 text-green-800' : ($receiving->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : ($receiving->status == 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }}">
                                {{ ucfirst($receiving->status ?: 'Unknown') }}
                            </span>
                        </td>
                        <td>
                            @if($receiving->created_at)
                                {{ \Carbon\Carbon::parse($receiving->created_at)->format('d F Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($receiving->status === 'received')
                                {{ ($receiving->receive_date ?: $receiving->updated_at)?->format('d F Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $receiving->reference_no ?: '-' }}</td>
                        <td>{{ $receiving->receivedFrom?->name ?: '-' }}</td>
                        <td class="max-w-xs truncate">{{ $receiving->notes ?: '-' }}</td>
                        <td>{{ $receiving->createdBy?->name ?? '-' }}</td>
                        <td>
                            @if($receiving->created_at)
                                {{ \Carbon\Carbon::parse($receiving->created_at)->format('d M Y') }}<br>
                                at {{ \Carbon\Carbon::parse($receiving->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $receiving->updatedBy?->name ?? '-' }}</td>
                        <td>
                            @if($receiving->updated_at)
                                {{ \Carbon\Carbon::parse($receiving->updated_at)->format('d M Y') }}<br>
                                at {{ \Carbon\Carbon::parse($receiving->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No inventory receivings found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if(isset($receivings) && $receivings->currentPage() > 1)
                    <a href="{{ $receivings->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if(isset($receivings) && $receivings->hasPages())
                    @php
                        $start = max(1, $receivings->currentPage() - 2);
                        $end = min($receivings->lastPage(), $receivings->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $receivings->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $receivings->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $receivings->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $receivings->lastPage())
                            @if($end < $receivings->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $receivings->url($receivings->lastPage()) }}" class="page-number">{{ $receivings->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if(isset($receivings) && $receivings->hasMorePages())
                    <a href="{{ $receivings->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $receivings->currentPage() ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $receivings->lastPage() ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Inventory Receiving</h2>
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

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon">
            <!-- SVG icon -->
        </div>
        <h3 class="delete-modal-title">Hide Inventory Receiving</h3>
        <p class="delete-modal-description" id="deleteMessage">Apakah Anda yakin ingin menyembunyikan receiving ini? Tindakan ini masih bisa dibatalkan nanti.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Ya, Sembunyikan</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="error-icon"></div>
        <h3 class="error-modal-title">Ups... Terjadi Kendala</h3>
        <p class="error-modal-description" id="errorMessage">Receiving tidak berhasil disembunyikan. Silakan coba lagi.</p>
        <div class="error-modal-buttons">
            <button class="btn btn-error-close" onclick="closeErrorModal()">Tutup</button>
            <button class="btn btn-error-retry" onclick="retryDelete()">Coba Lagi</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="success-icon"></div>
        <h3 class="success-modal-title">Berhasil</h3>
        <p class="success-modal-description" id="successMessage">Receiving berhasil disembunyikan.</p>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;
let modalData = null;

// Helper functions
function getStatusBadgeClass(status) {
    const badges = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'received': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800'
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
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
    openModal('Create New Inventory Receiving');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch('/api/warehouse/inventory-receivings/modal-data')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                modalData = data.data;
                document.getElementById('modalBody').innerHTML = `
                    <form id="form" onsubmit="submitForm(event)">
                        <div class="modal-section">
                            <div class="modal-section-title">Receiving Information</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="form-label">Reference No *</label>
                                    <input type="text" name="reference_no" class="form-input" placeholder="Enter reference number" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Receive Date *</label>
                                    <input type="date" name="receive_date" class="form-input" value="${new Date().toISOString().split('T')[0]}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-section">
                            <div class="modal-section-title">Receiving Details</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="form-label">Branch *</label>
                                    <select name="branch_id" class="form-input" required>
                                        <option value="">Select Branch</option>
                                        ${modalData.branches.map(branch => 
                                            `<option value="${branch.id}">${branch.name}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Received From *</label>
                                    <select name="received_from" class="form-input" required>
                                        <option value="">Select User</option>
                                        ${modalData.users.map(user => 
                                            `<option value="${user.id}">${user.name} (${user.email})</option>`
                                        ).join('')}
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-section">
                            <div class="modal-section-title">Receiving Items</div>
                            <div id="itemsContainer">
                                <div class="item-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 12px; margin-bottom: 12px; align-items: end;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Product</label>
                                        <select name="items[0][master_product_id]" class="form-input" required>
                                            <option value="">Select Product</option>
                                            ${modalData.products.map(product => 
                                                `<option value="${product.id}">${product.name} - ${product.sku || ''}</option>`
                                            ).join('')}
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="items[0][quantity]" class="form-input" min="0.01" step="0.01" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Notes</label>
                                        <input type="text" name="items[0][notes]" class="form-input" placeholder="Optional">
                                    </div>
                                    <div style="padding-bottom: 0;">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeItemReceiving(this)" style="visibility: hidden;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addItemReceiving()" style="margin-top: 12px;">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                        
                        <div class="modal-section">
                            <div class="modal-section-title">Additional Information</div>
                            <div class="grid grid-cols-1 gap-6">
                                <div class="form-group">
                                    <label class="form-label">Inventory Issuing (Optional)</label>
                                    <select name="issuing_id" class="form-input">
                                        <option value="">Select Inventory Issuing (Optional)</option>
                                        ${modalData.issuings.map(issuing => 
                                            `<option value="${issuing.id}">${issuing.issuing_number} - ${issuing.branch?.name || 'N/A'}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-input" rows="3" placeholder="Enter notes (optional)"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                `;
                
                // Store products data globally for add item function
                window.productsDataReceiving = modalData.products;
                window.itemCounterReceiving = 1;
                
                // Add modal footer
                document.getElementById('modalFooter').innerHTML = `
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" form="form" class="btn btn-primary">Create Receiving</button>
                `;
            } else {
                document.getElementById('modalBody').innerHTML = '<div class="text-center text-red-600">Error loading form data</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div class="text-center text-red-600">Error loading form data</div>';
        });
}

function openViewModal(id) {
    openModal('View Inventory Receiving');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/api/warehouse/inventory-receivings/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const receiving = data.data;
                document.getElementById('modalBody').innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Receiving Number</label>
                            <p class="detail-value">${receiving.receiving_number || '-'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reference No</label>
                            <p class="detail-value">${receiving.reference_no || '-'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Receive Date</label>
                            <p class="detail-value">${receiving.receive_date ? new Date(receiving.receive_date).toLocaleDateString() : '-'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Branch</label>
                            <p class="detail-value">${receiving.branch_name || '-'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Received From</label>
                            <p class="detail-value">${receiving.received_from_name || '-'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Received By</label>
                            <p class="detail-value">${receiving.received_by_name || '-'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full ${getStatusBadgeClass(receiving.status)}">
                                    ${receiving.status ? receiving.status.charAt(0).toUpperCase() + receiving.status.slice(1) : 'Unknown'}
                                </span>
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Issuing Number</label>
                            <p class="detail-value">${receiving.issuing_number || '-'}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <p class="detail-value">${receiving.notes || '-'}</p>
                    </div>
                `;
            } else {
                document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
        });
}

function openEditModal(id) {
    openModal('Edit Inventory Receiving');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    // Load both modal data and receiving data
    Promise.all([
        fetch('/api/warehouse/inventory-receivings/modal-data').then(r => r.json()),
        fetch(`/api/warehouse/inventory-receivings/${id}/edit`).then(r => r.json())
    ])
    .then(([modalResponse, editResponse]) => {
        if (modalResponse.status === 'success' && editResponse.status === 'success') {
            modalData = modalResponse.data;
            const receiving = editResponse.data.receiving;
            
            document.getElementById('modalBody').innerHTML = `
                <form id="editForm" onsubmit="submitForm(event, ${id})">
                    <div class="modal-section">
                        <div class="modal-section-title">Receiving Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Reference No *</label>
                                <input type="text" name="reference_no" class="form-input" value="${receiving.reference_no || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Receive Date *</label>
                                <input type="date" name="receive_date" class="form-input" value="${receiving.receive_date || ''}" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Receiving Details</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Branch *</label>
                                <select name="branch_id" class="form-input" required>
                                    <option value="">Select Branch</option>
                                    ${modalData.branches.map(branch => 
                                        `<option value="${branch.id}" ${branch.id == receiving.branch_id ? 'selected' : ''}>${branch.name}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Received From *</label>
                                <select name="received_from" class="form-input" required>
                                    <option value="">Select User</option>
                                    ${modalData.users.map(user => 
                                        `<option value="${user.id}" ${user.id == receiving.received_from ? 'selected' : ''}>${user.name} (${user.email})</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Additional Information</div>
                        <div class="grid grid-cols-1 gap-6">
                            <div class="form-group">
                                <label class="form-label">Inventory Issuing (Optional)</label>
                                <select name="issuing_id" class="form-input">
                                    <option value="">Select Inventory Issuing (Optional)</option>
                                    ${modalData.issuings.map(issuing => 
                                        `<option value="${issuing.id}" ${issuing.id == receiving.issuing_id ? 'selected' : ''}>${issuing.issuing_number} - ${issuing.branch?.name || 'N/A'}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-input" rows="3" placeholder="Enter notes (optional)">${receiving.notes || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="editForm" class="btn btn-primary">Update Receiving</button>
            `;
        } else {
            document.getElementById('modalBody').innerHTML = '<div class="text-center text-red-600">Error loading form data</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = '<div class="text-center text-red-600">Error loading form data</div>';
    });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/warehouse/inventory-receivings/${id}` : '/warehouse/inventory-receivings';
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
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Apakah Anda yakin ingin menyembunyikan receiving ini? Tindakan ini masih bisa dibatalkan nanti.'
        : `Apakah Anda yakin ingin menyembunyikan ${count} receiving? Tindakan ini masih bisa dibatalkan nanti.`;
    
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
    
    fetch('/api/warehouse/inventory-receivings/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            showSuccessModal(selectedIdsForRetry.length);
        } else {
            showErrorModal(result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan jaringan.');
    });
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'Receiving berhasil disembunyikan.'
        : `${count} receiving berhasil disembunyikan.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'Receiving tidak berhasil disembunyikan. Silakan coba lagi.';
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

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Perhatian', 'Silakan pilih minimal satu receiving yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

function deleteSingle(id) {
    selectedIdsForRetry = [id];
    openDeleteModal();
}

// Add/Remove Items Functions for Receiving
function addItemReceiving() {
    const container = document.getElementById('itemsContainer');
    const itemIndex = window.itemCounterReceiving++;
    
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 12px; margin-bottom: 12px; align-items: end;';
    
    const productOptions = window.productsDataReceiving.map(product => 
        `<option value="${product.id}">${product.name} - ${product.sku || ''}</option>`
    ).join('');
    
    newRow.innerHTML = `
        <div class="form-group" style="margin-bottom: 0;">
            <select name="items[${itemIndex}][master_product_id]" class="form-input" required>
                <option value="">Select Product</option>
                ${productOptions}
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <input type="number" name="items[${itemIndex}][quantity]" class="form-input" min="0.01" step="0.01" required>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <input type="text" name="items[${itemIndex}][notes]" class="form-input" placeholder="Optional">
        </div>
        <div style="padding-bottom: 0;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="removeItemReceiving(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(newRow);
}

function removeItemReceiving(button) {
    const row = button.closest('.item-row');
    row.remove();
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

function applyFilters() {
    const filterFromEl = document.getElementById('filterDateFrom');
    const filterToEl = document.getElementById('filterDateTo');
    
    // Get date values from data-date attribute (set by Flatpickr)
    const dateFrom = filterFromEl ? (filterFromEl.getAttribute('data-date') || filterFromEl.value || '') : '';
    const dateTo = filterToEl ? (filterToEl.getAttribute('data-date') || filterToEl.value || '') : '';
    
    // Use existing URLSearchParams to preserve column filters and sorting
    const params = new URLSearchParams(window.location.search);
    
    if (dateFrom) {
        params.set('date_from', dateFrom);
    } else {
        params.delete('date_from');
    }

    if (dateTo) {
        params.set('date_to', dateTo);
    } else {
        params.delete('date_to');
    }
    
    // Always reset to page 1 when changing filters
    params.set('page', '1');
    
    window.location.href = window.location.pathname + '?' + params.toString();
}

function resetFilters() {
    // Navigate to plain index to clear all filters
    window.location.href = window.location.pathname;
}

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

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// Initialize Flatpickr for filter dates
document.addEventListener('DOMContentLoaded', function() {
    // Filter Date From
    const filterFromEl = document.getElementById('filterDateFrom');
    if (filterFromEl) {
        flatpickr(filterFromEl, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j M Y',
            defaultDate: filterFromEl.getAttribute('data-date'),
            onChange: function(selectedDates, dateStr) {
                filterFromEl.setAttribute('data-date', dateStr);
            }
        });
    }
    
    // Filter Date To
    const filterToEl = document.getElementById('filterDateTo');
    if (filterToEl) {
        flatpickr(filterToEl, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j M Y',
            defaultDate: filterToEl.getAttribute('data-date'),
            onChange: function(selectedDates, dateStr) {
                filterToEl.setAttribute('data-date', dateStr);
            }
        });
    }
});
</script>
@endsection
