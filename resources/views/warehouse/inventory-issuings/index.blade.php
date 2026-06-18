@extends('layouts.app')

@section('title', 'Inventory Issuing')
@section('breadcrumb', 'Home / Warehouse / Inventory Issuing')

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

    /* Column widths for better layout */
    .responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 50px; min-width: 50px; }
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 200px; min-width: 200px; }

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

    .sm\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .gap-6 {
        gap: 1.5rem;
    }

    .gap-4 {
        gap: 1rem;
    }

    /* Issuing Information Card Responsive */
    .issuing-info-card {
        width: 100%;
        padding: 1rem;
    }

    .issuing-info-card .detail-item {
        margin-bottom: 0.75rem;
    }

    .issuing-info-card .form-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.25rem;
        display: block;
    }

    .issuing-info-card .detail-value {
        font-size: 0.875rem;
        color: #111827;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* Responsive breakpoints for issuing info card */
    @media (max-width: 640px) {
        .issuing-info-card {
            padding: 0.75rem;
        }
        
        .issuing-info-card .grid {
            grid-template-columns: 1fr !important;
            gap: 1rem;
        }
        
        .issuing-info-card .detail-item {
            margin-bottom: 0.5rem;
        }
        
        .issuing-info-card .form-label {
            font-size: 0.8125rem;
        }
        
        .issuing-info-card .detail-value {
            font-size: 0.8125rem;
        }
    }

    @media (min-width: 641px) and (max-width: 768px) {
        .issuing-info-card .grid {
            grid-template-columns: 1fr !important;
            gap: 1.25rem;
        }
    }

    @media (min-width: 769px) {
        .issuing-info-card .grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem;
        }
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

    .error-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }

    .error-icon {
        width: 80px;
        height: 80px;
    }

    .error-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }

    .error-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    .error-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }

    .btn-error-close {
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

    .btn-error-close:hover {
        background-color: #f8fafc;
        border-color: #1e3a8a;
        color: #1e3a8a;
    }

    .btn-error-retry {
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

    .btn-error-retry:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
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

    .success-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }

    .success-icon {
        width: 80px;
        height: 80px;
    }

    .success-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }

    .success-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
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
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Inventory Issuing Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <h1 class="text-xl font-semibold text-[#214589]">Inventory Issuing</h1>
            <button onclick="openManualIssuingModal()" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Manual Issuing
            </button>
        </div>
        
        <!-- Filter Row - Responsive -->
        <div class="w-full bg-white p-4 border-b">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Cabang -->
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-gray-700">Cabang:</label>
                    <select id="filterBranch" class="px-3 py-1.5 border border-gray-300 rounded text-sm w-full" onchange="applyFilters()">
                        <option value="">Semua Cabang</option>
                        @foreach(\App\Models\Branch::where('is_active', true)->orderBy('name')->get() as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Sejak -->
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-gray-700">Sejak:</label>
                    <input type="date" id="filterDateFrom" class="px-3 py-1.5 border border-gray-300 rounded text-sm w-full" value="{{ request('date_from') }}" onchange="applyFilters()">
                </div>
                
                <!-- Ke -->
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-gray-700">Ke:</label>
                    <input type="date" id="filterDateTo" class="px-3 py-1.5 border border-gray-300 rounded text-sm w-full" value="{{ request('date_to') }}" onchange="applyFilters()">
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
        
        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="issuing_number">Issuing No</th>
                        <th data-column="id">Kode</th>
                        <th data-column="branch__name">Cabang</th>
                        <th data-column="issue_date" data-type="date">Issue Date</th>
                        <th data-column="status">Status</th>
                        <th data-column="reference_no">Reference No</th>
                        <th data-column="requestedBy__name">Request By</th>
                        <th data-column="issuedBy__name">Diserahkan Oleh</th>
                        <th data-column="receivedBy__name">Diterima Oleh</th>
                        <th data-column="remarks">Catatan Tambahan</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($issuings ?? [] as $issuing)
                    <tr data-id="{{ $issuing->id }}" onclick="window.location='{{ route('warehouse.inventory-issuings.show', $issuing->id) }}'">
                        <td class="text-center">
                            @if($issuing->status === 'pending')
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $issuing->id }}" onclick="event.stopPropagation()">
                            @endif
                        </td>
                        <td>{{ $issuing->issuing_number ?? 'N/A' }}</td>
                        <td>{{ $issuing->id }}</td>
                        <td>{{ $issuing->branch_name }}</td>
                        <td>{{ $issuing->issue_date ? $issuing->issue_date->format('d/M/Y') : 'N/A' }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $issuing->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($issuing->status === 'processed' ? 'bg-blue-100 text-blue-800' : ($issuing->status === 'sent' ? 'bg-purple-100 text-purple-800' : ($issuing->status === 'received' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'))) }}">
                                {{ $issuing->status_text }}
                            </span>
                        </td>
                        <td>{{ $issuing->reference_no ?? '-' }}</td>
                        <td>{{ $issuing->requestedBy?->name ?? 'N/A' }}</td>
                        <td>{{ $issuing->status !== 'pending' ? ($issuing->issuedBy?->name ?? '-') : '-' }}</td>
                        <td>{{ $issuing->receivedBy?->name ?? '-' }}</td>
                        <td class="max-w-xs truncate">{{ $issuing->remarks ?? '-' }}</td>
                        <td>{{ $issuing->createdBy?->name ?? '-' }}</td>
                        <td>
                            @if($issuing->created_at)
                                {{ \Carbon\Carbon::parse($issuing->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($issuing->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $issuing->updatedBy?->name ?? '-' }}</td>
                        <td>
                            @if($issuing->updated_at)
                                {{ \Carbon\Carbon::parse($issuing->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($issuing->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No inventory issuings found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($issuings->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $issuings->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Manual Inventory Issuing Modal (Static HTML) -->
<div id="manualIssuingModal" class="modal-overlay" style="z-index: 1050;">
    <div class="modal-container" style="max-width: 900px; width: 90vw;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Buat Manual Inventory Issuing</h3>
            <button type="button" class="modal-close" onclick="closeManualIssuingModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="manualIssuingForm">
                <!-- Step 1: Informasi Dasar -->
                <div style="margin-bottom: 20px; padding: 10px; background: #f9fafb; border-radius: 6px;">
                    <p class="text-sm text-gray-600"><i class="fas fa-info-circle"></i> Buatlah inventory issuing manual untuk mengeluarkan produk dari gudang tanpa melalui Inventory Request.</p>
                </div>

                <!-- Step 2: Pilih Produk removed - will be added in Detail page -->

                <!-- Step 3: Pilih Received By & Team -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label" for="receivedBySelect">Diberikan Kepada <span style="color: red;">*</span></label>
                        <select id="receivedBySelect" name="received_by" class="form-input" required onchange="loadUserTeams()">
                            <option value="">-- Pilih User --</option>
                            @foreach(\App\Models\User::where('is_active', true)->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="teamSelect">Nama Team <span style="color: red;">*</span></label>
                        <select id="teamSelect" name="team_id" class="form-input" required disabled>
                            <option value="">-- Pilih Team --</option>
                        </select>
                    </div>
                </div>

                <!-- Warehouse Selection -->
                <div class="form-group">
                    <label class="form-label" for="warehouseSelect">Gudang Cabang</label>
                    <select id="warehouseSelect" name="warehouse_id" class="form-input d-none">
                        <option value="">-- Pilih Warehouse --</option>
                        @foreach(\App\Models\Warehouse::where('is_active', true)->get() as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-input" style="background: #f3f4f6; color: #374151;">Otomatis mengikuti warehouse aktif branch yang dipilih</div>
                </div>

                <!-- Remarks -->
                <div class="form-group">
                    <label class="form-label" for="remarksInput">Catatan Tambahan</label>
                    <textarea id="remarksInput" name="remarks" class="form-input" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeManualIssuingModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="submitManualIssuing()">
                <i class="fas fa-save"></i> Buat Inventory Issuing
            </button>
        </div>
    </div>
</div>

<!-- QR Scanner Modal -->
<div id="qrScannerModal" class="modal-overlay" style="z-index: 2000;">
    <div class="modal-container" style="max-width: 600px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title">Scan QR Code Serial Number</h3>
            <button type="button" class="modal-close" onclick="closeQRScanner()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="qrReader" style="width: 100%;"></div>
            <div style="margin-top: 12px; text-align: center; font-size: 14px; color: #666;">
                <i class="fas fa-info-circle"></i> Arahkan kamera ke QR Code
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Inventory Issuing</h2>
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
        <div class="delete-modal-content">
            <!-- Trash Icon -->
            <div class="delete-icon-container">
                <svg class="delete-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="trashGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1E40AF;stop-opacity:1" />
                        </linearGradient>
                        <filter id="shadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <!-- Trash Can Body -->
                    <rect x="25" y="35" width="50" height="45" rx="3" fill="url(#trashGradient)" filter="url(#shadow)"/>
                    <!-- Trash Can Lid -->
                    <rect x="20" y="30" width="60" height="8" rx="4" fill="url(#trashGradient)" filter="url(#shadow)"/>
                    <!-- Lid Handle -->
                    <rect x="45" y="25" width="10" height="8" rx="2" fill="url(#trashGradient)" filter="url(#shadow)"/>
                    <!-- Lid Slightly Open -->
                    <rect x="20" y="32" width="60" height="2" rx="1" fill="#1E40AF" opacity="0.3"/>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 id="deleteModalTitle" class="delete-modal-title">Sembunyikan 0 Data?</h2>
            
            <!-- Description -->
            <p class="delete-modal-description">
                These records won't show up on this page anymore, but don't worry—they'll stay safe in the database.
            </p>
            
            <!-- Buttons -->
            <div class="delete-modal-buttons">
                <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
                <button class="btn btn-hide" onclick="confirmDelete()">Ya, Sembunyikan</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="error-modal-content">
            <!-- Error Icon -->
            <div class="error-icon-container">
                <svg class="error-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="errorTrashGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1E40AF;stop-opacity:1" />
                        </linearGradient>
                        <filter id="errorShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <!-- Trash Can Body -->
                    <rect x="25" y="35" width="50" height="45" rx="3" fill="url(#errorTrashGradient)" filter="url(#errorShadow)"/>
                    <!-- Trash Can Lid -->
                    <rect x="20" y="30" width="60" height="8" rx="4" fill="url(#errorTrashGradient)" filter="url(#errorShadow)"/>
                    <!-- Lid Handle -->
                    <rect x="45" y="25" width="10" height="8" rx="2" fill="url(#errorTrashGradient)" filter="url(#errorShadow)"/>
                    <!-- Lid Slightly Open -->
                    <rect x="20" y="32" width="60" height="2" rx="1" fill="#1E40AF" opacity="0.3"/>
                    <!-- Error X Circle -->
                    <circle cx="75" cy="65" r="12" fill="#EF4444" filter="url(#errorShadow)"/>
                    <text x="75" y="70" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="16" font-weight="bold">×</text>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 class="error-modal-title">Ups... Terjadi Kendala</h2>
            
            <!-- Description -->
            <p class="error-modal-description">
                We couldn't hide the records just now, but your data's still safe. Give it another shot later.
            </p>
            
            <!-- Buttons -->
            <div class="error-modal-buttons">
                <button class="btn btn-error-close" onclick="closeErrorModal()">Tutup</button>
                <button class="btn btn-error-retry" onclick="retryDelete()">Coba Lagi</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="success-modal-content">
            <!-- Success Icon -->
            <div class="success-icon-container">
                <svg class="success-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="successGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#10B981;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                        </linearGradient>
                        <filter id="successShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <!-- Success Checkmark Circle -->
                    <circle cx="50" cy="50" r="40" fill="url(#successGradient)" filter="url(#successShadow)"/>
                    <path d="M35 50 L45 60 L65 40" stroke="white" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 id="successModalTitle" class="success-modal-title">Berhasil</h2>
            
            <!-- Description -->
            <p id="successModalDescription" class="success-modal-description">
                Operasi berhasil diselesaikan.
            </p>
        </div>
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
        'processed': 'bg-blue-100 text-blue-800',
        'sent': 'bg-purple-100 text-purple-800',
        'received': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800'
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
}

function updateWarehouses(branchId) {
    const warehouseSelect = document.querySelector('select[name="warehouse_id"]');
    if (!warehouseSelect || !modalData) return;
    
    // Clear existing options except the first one
    warehouseSelect.innerHTML = '<option value="">Select Warehouse</option>';
    
    // Filter warehouses by branch
    const filteredWarehouses = modalData.warehouses.filter(warehouse => 
        warehouse.branch_id == branchId
    );
    
    // Add filtered options
    filteredWarehouses.forEach(warehouse => {
        const option = document.createElement('option');
        option.value = warehouse.id;
        option.textContent = `${warehouse.name} - ${warehouse.branch.name}`;
        warehouseSelect.appendChild(option);
    });
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
}

// CRUD Modal functions
function openCreateModal() {
    openModal('Create New Inventory Issuing');
    document.getElementById('modalBody').innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 16px; color: #666;">Loading form data...</p>
        </div>
    `;
    
    // Load modal data from API
    fetch('/api/warehouse/inventory-issuings/modal-data')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                modalData = data.data;
                document.getElementById('modalBody').innerHTML = `
                    <p class="text-gray-600 mb-6 text-center">Let's add your new inventory issuing details and make sure nothing gets missed.</p>
                    <form id="form" onsubmit="submitForm(event)">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Left Column -->
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="form-label">Inventory Request</label>
                                    <select name="inventory_request_id" class="form-input">
                                        <option value="">Select Inventory Request (Optional)</option>
                                        ${modalData.inventory_requests.map(req => 
                                            `<option value="${req.id}">${req.request_number} - ${req.branch.name}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Branch *</label>
                                    <select name="branch_id" class="form-input" required onchange="updateWarehouses(this.value)">
                                        <option value="">Select Branch</option>
                                        ${modalData.branches.map(branch => 
                                            `<option value="${branch.id}">${branch.name}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Warehouse *</label>
                                    <select name="warehouse_id" class="form-input" required>
                                        <option value="">Select Warehouse</option>
                                        ${modalData.warehouses.map(warehouse => 
                                            `<option value="${warehouse.id}" data-branch="${warehouse.branch_id}">${warehouse.name} - ${warehouse.branch.name}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Issue Date *</label>
                                    <input type="date" name="issue_date" class="form-input" value="${new Date().toISOString().split('T')[0]}" required>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="form-label">Reference No</label>
                                    <input type="text" name="reference_no" class="form-input" placeholder="Enter reference number (optional)">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Requested By *</label>
                                    <select name="requested_by" class="form-input" required>
                                        <option value="">Select User</option>
                                        ${modalData.users.map(user => 
                                            `<option value="${user.id}">${user.name} (${user.email})</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Status *</label>
                                    <select name="status" class="form-input" required>
                                        <option value="pending" selected>Material Assign</option>
                                        <option value="processed">Material Issue</option>
                                        <option value="sent">Material Taken</option>
                                        <option value="received">Received</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Issued By</label>
                                    <select name="issued_by" class="form-input">
                                        <option value="">Select User (Optional)</option>
                                        ${modalData.users.map(user => 
                                            `<option value="${user.id}">${user.name} (${user.email})</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Received By</label>
                                    <select name="received_by" class="form-input">
                                        <option value="">Select User (Optional)</option>
                                        ${modalData.users.map(user => 
                                            `<option value="${user.id}">${user.name} (${user.email})</option>`
                                        ).join('')}
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Remarks -->
                        <div class="form-group mb-6">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-input" rows="3" placeholder="Enter any additional remarks (optional)"></textarea>
                        </div>
                        
                        <!-- Items Section -->
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold mb-4">Items</h3>
                            <div id="items-container">
                                <div class="item-row border border-gray-200 rounded-lg p-4 mb-4">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div class="form-group">
                                            <label class="form-label">Product *</label>
                                            <select name="items[0][product_id]" class="form-input" required onchange="onProductChange(0, this.value)">
                                                <option value="">Select Product</option>
                                                <!-- Products will be loaded dynamically -->
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Available Stock</label>
                                            <input type="text" id="stock_0" class="form-input" readonly placeholder="Select product first">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Quantity *</label>
                                            <input type="number" name="items[0][quantity_requested]" class="form-input" step="0.01" min="0.01" required placeholder="0.00">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Unit Price</label>
                                            <input type="number" name="items[0][unit_price]" class="form-input" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Notes</label>
                                            <input type="text" name="items[0][notes]" class="form-input" placeholder="Optional notes">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline" onclick="addItemRow()">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex justify-center gap-6 mt-6">
                            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">Create Issuing</button>
                        </div>
                    </form>
                `;
                
                // Load products for the first item
                loadProductsForItem(0);
                
                // Add form validation event listener
                const form = document.getElementById('form');
                if (form) {
                    form.addEventListener('invalid', function(e) {
                        console.log('Form validation failed:', e.target.name, e.target.validationMessage);
                        showWarningDialog('Perhatian', `Silakan isi field wajib: ${e.target.name}`);
                    }, true);
                    
                    // Validate "Received By" field before submit
                    const receivedBySelect = form.querySelector('select[name="received_by"]');
                    const submitBtn = document.getElementById('submitBtn');
                    
                    // Disable submit button initially if received_by is empty
                    function checkReceivedBy() {
                        if (receivedBySelect && submitBtn) {
                            if (!receivedBySelect.value || receivedBySelect.value === '') {
                                submitBtn.disabled = true;
                                submitBtn.style.opacity = '0.5';
                                submitBtn.style.cursor = 'not-allowed';
                                submitBtn.title = 'Silakan pilih user pada field "Received By"';
                            } else {
                                submitBtn.disabled = false;
                                submitBtn.style.opacity = '1';
                                submitBtn.style.cursor = 'pointer';
                                submitBtn.title = '';
                            }
                        }
                    }
                    
                    // Check on load
                    checkReceivedBy();
                    
                    // Check on change
                    if (receivedBySelect) {
                        receivedBySelect.addEventListener('change', checkReceivedBy);
                    }
                    
                    // Prevent form submission if received_by is empty
                    form.addEventListener('submit', function(e) {
                        if (!receivedBySelect.value || receivedBySelect.value === '') {
                            e.preventDefault();
                            showWarningDialog('Perhatian', 'Silakan pilih user pada field "Received By" sebelum melanjutkan.');
                            receivedBySelect.focus();
                            return false;
                        }
                    });
                }
            } else {
                document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading form data.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading form data.</div>';
        });
    
    document.getElementById('modalFooter').innerHTML = ``;
}

function openViewModal(id) {
    openModal('View Inventory Issuing');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/api/warehouse/inventory-issuings/${id}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const issuing = data.data;
                document.getElementById('modalBody').innerHTML = `
                    <div class="issuing-info-card">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                            <!-- Left Column -->
                            <div class="space-y-3 md:space-y-4">
                                <div class="detail-item">
                                    <label class="form-label">Issuing Number</label>
                                    <p class="detail-value">${issuing.issuing_number || 'N/A'}</p>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Inventory Request</label>
                                    <p class="detail-value">${issuing.inventory_request ? issuing.inventory_request.request_number : 'N/A'}</p>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Branch</label>
                                    <p class="detail-value">${issuing.branch ? issuing.branch.name : 'N/A'}</p>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Warehouse</label>
                                    <p class="detail-value">${issuing.warehouse ? issuing.warehouse.name : 'N/A'}</p>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Issue Date</label>
                                    <p class="detail-value">${issuing.issue_date ? new Date(issuing.issue_date).toLocaleDateString() : 'N/A'}</p>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div class="space-y-3 md:space-y-4">
                                <div class="detail-item">
                                    <label class="form-label">Reference No</label>
                                    <p class="detail-value">${issuing.reference_no || 'N/A'}</p>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Requested By</label>
                                    <p class="detail-value">${issuing.requested_by ? issuing.requested_by.name : 'N/A'}</p>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Issued By</label>
                                    <p class="detail-value">${issuing.issued_by ? issuing.issued_by.name : 'N/A'}</p>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Received By</label>
                                    <p class="detail-value">${issuing.received_by ? issuing.received_by.name : 'N/A'}</p>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Status</label>
                                    <p class="detail-value">
                                        <span class="px-2 py-1 text-xs rounded-full ${getStatusBadgeClass(issuing.status)}">
                                            ${issuing.status ? issuing.status.charAt(0).toUpperCase() + issuing.status.slice(1) : 'N/A'}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    ${issuing.remarks ? `
                    <div class="mt-6">
                        <div class="detail-item">
                            <label class="form-label">Remarks</label>
                            <p class="detail-value">${issuing.remarks}</p>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${issuing.items && issuing.items.length > 0 ? `
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold mb-4">Issuing Items</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty Requested</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty Issued</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${issuing.items.map(item => `
                                        <tr class="border-t">
                                            <td class="px-4 py-2">${item.product ? item.product.name : 'N/A'}</td>
                                            <td class="px-4 py-2">${item.product ? item.product.sku : 'N/A'}</td>
                                            <td class="px-4 py-2">${item.quantity_requested || 0}</td>
                                            <td class="px-4 py-2">${item.quantity_issued || 0}</td>
                                            <td class="px-4 py-2">${item.unit_price ? 'Rp ' + new Intl.NumberFormat('id-ID').format(item.unit_price) : 'N/A'}</td>
                                            <td class="px-4 py-2">${item.total_price ? 'Rp ' + new Intl.NumberFormat('id-ID').format(item.total_price) : 'N/A'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    ` : ''}
                `;
            } else {
                document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            }
            
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
        });
}

function openEditModal(id) {
    openModal('Edit Inventory Issuing');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    // Load both modal data and issuing details
    Promise.all([
        fetch('/api/warehouse/inventory-issuings/modal-data').then(response => response.json()),
        fetch(`/api/warehouse/inventory-issuings/${id}/details`).then(response => response.json())
    ])
    .then(([modalResponse, issuingResponse]) => {
        if (modalResponse.status === 'success' && issuingResponse.status === 'success') {
            modalData = modalResponse.data;
            const issuing = issuingResponse.data;
            
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update your inventory issuing details and make sure nothing gets missed.</p>
                <form id="editForm" onsubmit="console.log('Form submitted'); event.preventDefault(); console.log('About to call submitEditForm'); submitEditForm(); console.log('submitEditForm called');">
                    <input type="hidden" name="id" value="${issuing.id}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Inventory Request</label>
                                <select name="inventory_request_id" class="form-input">
                                    <option value="">Select Inventory Request (Optional)</option>
                                    ${modalData.inventory_requests.map(req => 
                                        `<option value="${req.id}" ${issuing.inventory_request_id == req.id ? 'selected' : ''}>${req.request_number} - ${req.branch.name}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Branch *</label>
                                <select name="branch_id" class="form-input" required onchange="updateWarehouses(this.value)">
                                    <option value="">Select Branch</option>
                                    ${modalData.branches.map(branch => 
                                        `<option value="${branch.id}" ${issuing.branch_id == branch.id ? 'selected' : ''}>${branch.name}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Warehouse *</label>
                                <select name="warehouse_id" class="form-input" required>
                                    <option value="">Select Warehouse</option>
                                    ${modalData.warehouses.map(warehouse => 
                                        `<option value="${warehouse.id}" data-branch="${warehouse.branch_id}" ${issuing.warehouse_id == warehouse.id ? 'selected' : ''}>${warehouse.name} - ${warehouse.branch.name}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Issue Date *</label>
                                <input type="date" name="issue_date" class="form-input" value="${issuing.issue_date ? new Date(issuing.issue_date).toISOString().split('T')[0] : ''}" required>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Reference No</label>
                                <input type="text" name="reference_no" class="form-input" placeholder="Enter reference number (optional)" value="${issuing.reference_no || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Requested By *</label>
                                <select name="requested_by" class="form-input" required>
                                    <option value="">Select User</option>
                                    ${modalData.users.map(user => 
                                        `<option value="${user.id}" ${issuing.requested_by == user.id ? 'selected' : ''}>${user.name} (${user.email})</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Issued By</label>
                                <select name="issued_by" class="form-input">
                                    <option value="">Select User (Optional)</option>
                                    ${modalData.users.map(user => 
                                        `<option value="${user.id}" ${issuing.issued_by == user.id ? 'selected' : ''}>${user.name} (${user.email})</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Team Project (Optional)</label>
                                <select name="team_id" class="form-input">
                                    <option value="">Select Team</option>
                                    ${modalData.teams ? modalData.teams.map(team => 
                                        `<option value="${team.id}" ${issuing.team_id == team.id ? 'selected' : ''}>${team.team_name}</option>`
                                    ).join('') : ''}
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Assigning a team will update the associated Job Schedule.</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Received By (Diberikan Kepada)</label>
                                <select name="received_by" class="form-input">
                                    <option value="">Select User (Optional)</option>
                                    ${modalData.users.map(user => 
                                        `<option value="${user.id}" ${issuing.received_by == user.id ? 'selected' : ''}>${user.name} (${user.email})</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-input">
                                    <option value="pending" ${issuing.status === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="processed" ${issuing.status === 'processed' ? 'selected' : ''}>Processed</option>
                                    <option value="sent" ${issuing.status === 'sent' ? 'selected' : ''}>Sent</option>
                                    <option value="received" ${issuing.status === 'received' ? 'selected' : ''}>Received</option>
                                    <option value="cancelled" ${issuing.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items Section -->
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold mb-4">Items</h3>
                        <div id="items-container">
                            ${issuing.items.map((item, index) => `
                                <div class="item-row border border-gray-200 rounded-lg p-4 mb-4">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div class="form-group">
                                            <label class="form-label">Product *</label>
                                            <select name="items[${index}][product_id]" class="form-input" required onchange="onProductChange(${index}, this.value)">
                                                <option value="">Select Product</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Available Stock</label>
                                            <input type="text" id="stock_${index}" class="form-input" readonly placeholder="Select product first">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Quantity *</label>
                                            <input type="number" name="items[${index}][quantity_requested]" class="form-input" step="0.01" min="0.01" required placeholder="0.00" value="${item.quantity_requested || ''}">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Unit Price</label>
                                            <input type="number" name="items[${index}][unit_price]" class="form-input" step="0.01" min="0" placeholder="0.00" value="${item.unit_price || ''}">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Notes</label>
                                            <input type="text" name="items[${index}][notes]" class="form-input" placeholder="Optional notes" value="${item.notes || ''}">
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <button type="button" class="btn btn-outline" onclick="addItemRow()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                    
                    <!-- Remarks -->
                    <div class="form-group mb-6">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-input" rows="3" placeholder="Enter any additional remarks (optional)">${issuing.remarks || ''}</textarea>
                    </div>
                    
                    <!-- Form buttons inside the form -->
                    <div class="flex justify-center gap-6 mt-6">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editSubmitBtn" onclick="console.log('Button clicked')">Update Issuing</button>
                    </div>
                </form>
            `;
            
            // Set the current branch to trigger warehouse filtering
            const branchSelect = document.querySelector('select[name="branch_id"]');
            if (branchSelect && branchSelect.value) {
                updateWarehouses(branchSelect.value);
                // Re-select the warehouse after filtering
                setTimeout(() => {
                    const warehouseSelect = document.querySelector('select[name="warehouse_id"]');
                    if (warehouseSelect) {
                        warehouseSelect.value = issuing.warehouse_id;
                    }
                }, 100);
            }
            
            // Validate "Received By" field before submit (for edit form)
            const editForm = document.getElementById('editForm');
            if (editForm) {
                const receivedBySelect = editForm.querySelector('select[name="received_by"]');
                const editSubmitBtn = document.getElementById('editSubmitBtn');
                
                // Disable submit button initially if received_by is empty
                function checkReceivedByEdit() {
                    if (receivedBySelect && editSubmitBtn) {
                        if (!receivedBySelect.value || receivedBySelect.value === '') {
                            editSubmitBtn.disabled = true;
                            editSubmitBtn.style.opacity = '0.5';
                            editSubmitBtn.style.cursor = 'not-allowed';
                            editSubmitBtn.title = 'Silakan pilih user pada field "Received By"';
                        } else {
                            editSubmitBtn.disabled = false;
                            editSubmitBtn.style.opacity = '1';
                            editSubmitBtn.style.cursor = 'pointer';
                            editSubmitBtn.title = '';
                        }
                    }
                }
                
                // Check on load
                checkReceivedByEdit();
                
                // Check on change
                if (receivedBySelect) {
                    receivedBySelect.addEventListener('change', checkReceivedByEdit);
                }
                
                // Prevent form submission if received_by is empty
                editForm.addEventListener('submit', function(e) {
                    if (!receivedBySelect.value || receivedBySelect.value === '') {
                        e.preventDefault();
                            showWarningDialog('Perhatian', 'Silakan pilih user pada field "Received By" sebelum melanjutkan.');
                        receivedBySelect.focus();
                        return false;
                    }
                });
            }
            
            // Load products for each item and set selected values
            issuing.items.forEach((item, index) => {
                loadProductsForItem(index).then(() => {
                    const select = document.querySelector(`select[name="items[${index}][product_id]"]`);
                    if (select && item.product_id) {
                        select.value = item.product_id;
                        // Trigger onProductChange to load stock and price
                        onProductChange(index, item.product_id);
                    }
                });
            });
        } else {
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading form data.</div>';
        }
        
        // Clear modal footer since buttons are now inside the form
        document.getElementById('modalFooter').innerHTML = '';
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading form data.</div>';
    });
}

let itemIndex = 1;
let productsData = [];

function addItemRow() {
    const container = document.getElementById('items-container');
    const newRow = document.createElement('div');
    newRow.className = 'item-row border border-gray-200 rounded-lg p-4 mb-4';
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="form-group">
                <label class="form-label">Product *</label>
                <select name="items[${itemIndex}][product_id]" class="form-input" required onchange="onProductChange(${itemIndex}, this.value)">
                    <option value="">Select Product</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Available Stock</label>
                <input type="text" id="stock_${itemIndex}" class="form-input" readonly placeholder="Select product first">
            </div>
            <div class="form-group">
                <label class="form-label">Quantity *</label>
                <input type="number" name="items[${itemIndex}][quantity_requested]" class="form-input" step="0.01" min="0.01" required placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label">Unit Price</label>
                <input type="number" name="items[${itemIndex}][unit_price]" class="form-input" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <input type="text" name="items[${itemIndex}][notes]" class="form-input" placeholder="Optional notes">
            </div>
        </div>
        <button type="button" class="btn btn-outline btn-sm mt-2" onclick="removeItemRow(this)">
            <i class="fas fa-trash"></i> Remove
        </button>
    `;
    container.appendChild(newRow);
    loadProductsForItem(itemIndex);
    itemIndex++;
}

function removeItemRow(button) {
    button.closest('.item-row').remove();
}

function loadProductsForItem(index) {
    return fetch('/api/products')
        .then(response => response.json())
        .then(data => {
            const select = document.querySelector(`select[name="items[${index}][product_id]"]`);
            if (select && data.status === 'success' && data.data) {
                // Store products data globally
                if (productsData.length === 0) {
                    productsData = data.data;
                }

                data.data.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = `${product.name} (${product.sku})`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
        });
}

function onProductChange(index, productId) {
    if (!productId) {
        // Clear fields if no product selected
        document.getElementById(`stock_${index}`).value = '';
        document.querySelector(`input[name="items[${index}][unit_price]"]`).value = '';
        return;
    }
    
    // Find product data
    const product = productsData.find(p => p.id == productId);
    if (product) {
        // Update stock display
        document.getElementById(`stock_${index}`).value = `${product.total_stock || 0} units`;
        
        // Auto-fill unit price
        if (product.unit_price) {
            document.querySelector(`input[name="items[${index}][unit_price]"]`).value = product.unit_price;
        }
    }
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    console.log('Form submitted!');
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    // Process items array properly
    const items = [];
    let itemIndex = 0;
    while (data[`items[${itemIndex}][product_id]`]) {
        items.push({
            product_id: data[`items[${itemIndex}][product_id]`],
            quantity_requested: data[`items[${itemIndex}][quantity_requested]`],
            unit_price: data[`items[${itemIndex}][unit_price]`] || 0,
            notes: data[`items[${itemIndex}][notes]`] || ''
        });
        itemIndex++;
    }
    
    // Remove items from data and add processed items
    Object.keys(data).forEach(key => {
        if (key.startsWith('items[')) {
            delete data[key];
        }
    });
    data.items = items;
    
    // Check if items array is properly formatted
    if (!data.items || !Array.isArray(data.items) || data.items.length === 0) {
        showWarningDialog('Perhatian', 'Tidak ada item yang dipilih. Silakan pilih minimal satu produk.');
        return;
    }
    
    const url = id ? `/api/warehouse/inventory-issuings/${id}` : '/api/warehouse/inventory-issuings';
    const method = id ? 'PUT' : 'POST';
    
    console.log('URL:', url, 'Method:', method);
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
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
        console.log('Response result:', result);
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

function submitEditForm() {
    console.log('submitEditForm called');
    const form = document.getElementById('editForm');
    if (!form) {
        console.error('Form editForm not found');
        return;
    }
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    console.log('Form data:', data);
    
    // Process items array properly
    const items = [];
    let itemIndex = 0;
    while (data[`items[${itemIndex}][product_id]`]) {
        items.push({
            product_id: data[`items[${itemIndex}][product_id]`],
            quantity_requested: data[`items[${itemIndex}][quantity_requested]`],
            unit_price: data[`items[${itemIndex}][unit_price]`] || 0,
            notes: data[`items[${itemIndex}][notes]`] || ''
        });
        itemIndex++;
    }
    
    // Remove items from data and add processed items
    Object.keys(data).forEach(key => {
        if (key.startsWith('items[')) {
            delete data[key];
        }
    });
    data.items = items;
    
    // Check if items array is properly formatted
    if (!data.items || !Array.isArray(data.items) || data.items.length === 0) {
        showWarningDialog('Perhatian', 'Tidak ada item yang dipilih. Silakan pilih minimal satu produk.');
        return;
    }
    
    const id = data.id;
    delete data.id; // Remove id from data
    
    console.log('ID:', id);
    console.log('Final data:', data);
    console.log('URL:', `/api/warehouse/inventory-issuings/${id}`);
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error('CSRF token not found');
        showErrorDialog('Gagal', 'Token CSRF tidak ditemukan.');
        return;
    }
    console.log('CSRF token:', csrfToken.getAttribute('content'));
    
    fetch(`/api/warehouse/inventory-issuings/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.json();
    })
    .then(result => {
        console.log('Response result:', result);
        if (result.status === 'success') {
            showSuccessDialog('Berhasil', result.message || 'Inventory issuing berhasil diperbarui.');
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', result.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', error.message || 'Terjadi kesalahan.');
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this inventory issuing? This action can be undone later.'
        : `Are you sure you want to hide ${count} inventory issuings? This action can be undone later.`;
    
    document.getElementById('deleteModalTitle').textContent = `Hide ${count} Record${count > 1 ? 's' : ''}?`;
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function confirmDelete() {
    closeDeleteModal();
    
    fetch('/api/warehouse/inventory-issuings/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessModal(result.count, 'deleted');
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
function showSuccessModal(count, action = 'deleted') {
    let title = 'Berhasil';
    let message = '';
    
    if (action === 'deleted') {
        title = 'Inventory Issuing Berhasil Dihapus';
        message = count === 1 
            ? 'Inventory issuing berhasil dihapus.'
            : `${count} inventory issuing berhasil dihapus.`;
    } else if (action === 'added') {
        title = 'Inventory Issuing Berhasil Ditambahkan';
        message = 'Inventory issuing berhasil disimpan dan siap diproses lebih lanjut.<br>Data sudah tersimpan dengan aman.';
    } else if (action === 'hidden') {
        title = 'Inventory Issuing Berhasil Disembunyikan';
        message = count === 1 
            ? 'Inventory issuing berhasil disembunyikan.'
            : `${count} inventory issuing berhasil disembunyikan.`;
    } else {
        message = count === 1 
            ? `Inventory issuing berhasil ${action}.`
            : `${count} inventory issuing berhasil ${action}.`;
    }
    
    document.getElementById('successModalTitle').textContent = title;
    document.getElementById('successModalDescription').innerHTML = message;
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
    const errorDescription = document.querySelector('.error-modal-description');
    if (errorDescription) {
        errorDescription.textContent = message || 'Data belum berhasil diproses saat ini, tetapi tetap aman. Silakan coba lagi nanti.';
    }
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

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Perhatian', 'Silakan pilih minimal satu inventory issuing yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
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

// Initialize functionality
document.addEventListener('DOMContentLoaded', function() {
    // Select All functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const headerSelectAllCheckbox = document.getElementById('headerSelectAll');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            if (headerSelectAllCheckbox) {
                headerSelectAllCheckbox.checked = this.checked;
            }
        });
    }
    
    if (headerSelectAllCheckbox) {
        headerSelectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = this.checked;
            }
        });
    }
    
    // Individual checkbox functionality
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            const headerSelectAllCheckbox = document.getElementById('headerSelectAll');
            
            const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
            const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
            
            if (selectAllCheckbox) selectAllCheckbox.checked = allChecked;
            if (headerSelectAllCheckbox) headerSelectAllCheckbox.checked = allChecked;
            if (selectAllCheckbox) selectAllCheckbox.indeterminate = anyChecked && !allChecked;
            if (headerSelectAllCheckbox) headerSelectAllCheckbox.indeterminate = anyChecked && !allChecked;
        }
    });
});

function applyFilters() {
    const branchId = document.getElementById('filterBranch').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    
    const url = new URL(window.location.href);
    
    if (branchId) url.searchParams.set('branch_id', branchId);
    else url.searchParams.delete('branch_id');
    
    if (dateFrom) url.searchParams.set('date_from', dateFrom);
    else url.searchParams.delete('date_from');
    
    if (dateTo) url.searchParams.set('date_to', dateTo);
    else url.searchParams.delete('date_to');
    
    // Reset pagination
    url.searchParams.set('page', '1');
    
    window.location.href = url.toString();
}

// Global initTableFilters from layouts/app.blade.php will handle the data-column headers automatically

// ===== MANUAL INVENTORY ISSUING MODAL =====
let selectedProducts = [];
let qrCodeScanner = null;
let currentScanningProductId = null;
let lockedWarehouseId = null; // Warehouse yang ter-lock berdasarkan SN pertama yang dipilih
let serialDataCache = {}; // Cache serial data per productId untuk lookup QR scan

async function openManualIssuingModal() {
    // Simply show the static HTML modal
    document.getElementById('manualIssuingModal').classList.add('show');
    // Reset form
    document.getElementById('manualIssuingForm').reset();
    document.getElementById('teamSelect').disabled = true;
    document.getElementById('warehouseSelect').disabled = false;
    selectedProducts = [];
    lockedWarehouseId = null;
    serialDataCache = {};

    // Handle initial onchange for warehouse select
    document.getElementById('warehouseSelect').onchange = function() {
        const val = this.value;
        if (val) {
            lockedWarehouseId = parseInt(val);
            // Refresh serials for all selected products
            selectedProducts.forEach(productId => loadAvailableSerials(productId));
        } else {
            lockedWarehouseId = null;
        }
    };

    // Load Modal Data for context (Warehouse lock)
    try {
        const response = await fetch('{{ route("warehouse.inventory-issuings.modal-data") }}');
        const data = await response.json();
        if (data.status === 'success') {
            const context = data.data.user_context;
            if (context.is_warehouse_manager && context.managed_warehouse_id) {
                const warehouseSelect = document.getElementById('warehouseSelect');
                warehouseSelect.value = context.managed_warehouse_id;
                warehouseSelect.disabled = true;
                lockedWarehouseId = parseInt(context.managed_warehouse_id);
            }
        }
    } catch (e) {
        console.error('Error fetching modal data for context:', e);
    }
}

function closeManualIssuingModal() {
    document.getElementById('manualIssuingModal').classList.remove('show');
    selectedProducts = [];
    lockedWarehouseId = null;
    serialDataCache = {};
}

async function loadAllProducts() {
    try {
        const response = await fetch(`{{ route('warehouse.inventory-issuings.all-products') }}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            const productList = document.getElementById('productList');
            productList.innerHTML = '';
            
            data.data.forEach(product => {
                const productHTML = `
                    <div class="border-b pb-3 mb-3 last:border-b-0" data-product-id="${product.id}">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" class="product-checkbox mt-1" value="${product.id}" 
                                   onchange="toggleProductSelection(${product.id}, this.checked)">
                            <div class="flex-1">
                                <div class="font-semibold">${product.name}</div>
                                <div class="text-sm text-gray-600">Code: ${product.code} | Type: ${product.type_name}</div>
                                
                                <!-- Serial Number Selection (shown when checked) -->
                                <div id="serial-section-${product.id}" class="mt-2" style="display: none;">
                                    <label class="text-sm font-medium">Serial Number:</label>
                                    <div class="flex gap-2 mt-1">
                                        <select id="serial-select-${product.id}" class="form-input flex-1 text-sm">
                                            <option value="">-- Pilih Serial Number --</option>
                                        </select>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="openQRScanner(${product.id})">
                                            <i class="fas fa-qrcode"></i> Scan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                productList.innerHTML += productHTML;
            });
            
            document.getElementById('productSelectionSection').style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading all products:', error);
        showErrorDialog('Gagal memuat produk.');
    }
}

async function toggleProductSelection(productId, isChecked) {
    const serialSection = document.getElementById(`serial-section-${productId}`);
    
    if (isChecked) {
        serialSection.style.display = 'block';
        // Load available serial numbers for this product
        await loadAvailableSerials(productId);
        
        if (!selectedProducts.includes(productId)) {
            selectedProducts.push(productId);
        }
    } else {
        serialSection.style.display = 'none';
        selectedProducts = selectedProducts.filter(id => id !== productId);
    }
}

async function loadAvailableSerials(productId) {
    try {
        // Build URL with warehouse filter
        const warehouseId = document.getElementById('warehouseSelect').value;
        let url = `{{ url('warehouse/inventory-issuings/products') }}/${productId}/serials`;
        if (warehouseId || lockedWarehouseId) {
            url += `?warehouse_id=${warehouseId || lockedWarehouseId}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.status === 'success') {
            const serialSelect = document.getElementById(`serial-select-${productId}`);
            serialSelect.innerHTML = '<option value="">-- Pilih Serial Number --</option>';
            
            // Cache serial data for QR scan lookup
            serialDataCache[productId] = data.data;
            
            data.data.forEach(serial => {
                const option = document.createElement('option');
                option.value = serial.id;
                option.dataset.warehouseId = serial.warehouse_id;
                option.dataset.warehouseName = serial.warehouse_name;
                option.dataset.serialNumber = serial.serial_number;
                option.textContent = `${serial.serial_number} (${serial.warehouse_name})`;
                serialSelect.appendChild(option);
            });
            
            // Add onchange handler for warehouse lock
            serialSelect.onchange = function() {
                onSerialNumberChange(productId, this);
            };
        }
    } catch (error) {
        console.error('Error loading serials:', error);
    }
}

// Handle serial number selection - lock warehouse based on first selection
function onSerialNumberChange(productId, selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    
    // If user cleared the selection for this specific product
    if (!selectedOption.value) {
        // If no other SNs are selected, unlock the warehouse
        if (getSelectedSNCount() === 0) {
            unlockWarehouse();
        }
        return;
    }
    
    const warehouseId = selectedOption.dataset.warehouseId;
    const warehouseName = selectedOption.dataset.warehouseName;
    
    // Always update logic: if current locked warehouse is different from newly selected SN
    // and we only have ONE SN selected, we can pivot the warehouse lock
    if (warehouseId && (lockedWarehouseId !== parseInt(warehouseId))) {
        // If we have more than 1 SN selected, we should probably warn or block, 
        // but for usability, if changing the ONLY selected SN, just pivot the lock.
        if (getSelectedSNCount() <= 1) {
            lockWarehouse(parseInt(warehouseId), warehouseName, productId);
        } else {
            // Revert selection if trying to pick from different warehouse while others are picked
            showWarningDialog('Perhatian', 'Anda sudah memilih produk dari warehouse lain. Semua produk dalam satu issuing harus berasal dari warehouse yang sama.');
            selectElement.value = "";
        }
    }
}

function getSelectedSNCount() {
    let count = 0;
    selectedProducts.forEach(pid => {
        const select = document.getElementById(`serial-select-${pid}`);
        if (select && select.value) {
            count++;
        }
    });
    return count;
}

function lockWarehouse(warehouseId, warehouseName, triggerProductId) {
    lockedWarehouseId = warehouseId;
    
    const warehouseSelect = document.getElementById('warehouseSelect');
    warehouseSelect.value = String(lockedWarehouseId);
    
    // Trigger Select2 refresh
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery('#warehouseSelect').val(String(lockedWarehouseId)).trigger('change');
    }
    
    warehouseSelect.disabled = true;
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery('#warehouseSelect').prop('disabled', true).trigger('change');
    }
    
    // Reload all other product serial dropdowns with warehouse filter
    reloadOtherProductSerials(triggerProductId);
    console.log(`Warehouse locked to: ${warehouseName} (ID: ${lockedWarehouseId})`);
}

function unlockWarehouse() {
    lockedWarehouseId = null;
    const warehouseSelect = document.getElementById('warehouseSelect');
    warehouseSelect.disabled = false;
    
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery('#warehouseSelect').prop('disabled', false).trigger('change');
    }
    
    // Reload all product serial dropdowns without filter
    selectedProducts.forEach(pid => {
        loadAvailableSerials(pid);
    });
    
    console.log('Warehouse unlocked');
}

// Reload serial numbers for other products with locked warehouse filter
async function reloadOtherProductSerials(excludeProductId) {
    for (const pid of selectedProducts) {
        if (pid !== excludeProductId) {
            await loadAvailableSerials(pid);
        }
    }
}

function toggleSelectAllProducts() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
        const productId = parseInt(cb.value);
        toggleProductSelection(productId, cb.checked);
    });
}

async function loadUserTeams() {
    const userId = document.getElementById('receivedBySelect').value;
    const teamSelect = document.getElementById('teamSelect');
    
    if (!userId) {
        teamSelect.disabled = true;
        teamSelect.innerHTML = '<option value="">-- Pilih Team --</option>';
        return;
    }

    try {
        const response = await fetch(`{{ url('warehouse/inventory-issuings/users') }}/${userId}/teams`);
        const data = await response.json();
        
        if (data.status === 'success') {
            teamSelect.innerHTML = '<option value="">-- Pilih Team --</option>';
            data.data.teams.forEach(team => {
                teamSelect.innerHTML += `<option value="${team.id}">${team.team_name} (${team.team_code})</option>`;
            });
            teamSelect.disabled = false;
        }
    } catch (error) {
        console.error('Error loading user teams:', error);
        showErrorDialog('Gagal memuat team user.');
    }
}

function openQRScanner(productId) {
    currentScanningProductId = productId;
    document.getElementById('qrScannerModal').classList.add('show');
    
    // Add/reset error message element in scanner modal
    let errorDiv = document.getElementById('qrScanError');
    if (!errorDiv) {
        const modalBody = document.querySelector('#qrScannerModal .modal-body');
        errorDiv = document.createElement('div');
        errorDiv.id = 'qrScanError';
        errorDiv.style.cssText = 'color: red; font-weight: bold; text-align: center; margin-top: 10px; display: none;';
        modalBody.appendChild(errorDiv);
    }
    errorDiv.style.display = 'none';
    
    // Initialize QR Scanner
    if (!qrCodeScanner) {
        qrCodeScanner = new Html5Qrcode("qrReader");
    }
    
    qrCodeScanner.start(
        { facingMode: "environment" },
        { 
            fps: 20, 
            qrbox: (viewfinderWidth, viewfinderHeight) => {
                let minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                let qrboxSize = Math.floor(minEdge * 0.7);
                return { width: qrboxSize, height: qrboxSize };
            }
        },
        (decodedText) => {
            // QR Code scanned successfully - lookup SN
            const serialSelect = document.getElementById(`serial-select-${currentScanningProductId}`);
            const scannedSN = decodedText.toUpperCase().trim();
            
            // Trigger visual feedback in modal if possible
            if (errorDiv) {
                errorDiv.textContent = `✓ Mendeteksi SN: ${scannedSN}`;
                errorDiv.style.color = '#10b981';
                errorDiv.style.display = 'block';
            }

            // Find matching serial number in dropdown options
            let found = false;
            const options = serialSelect.options;
            for (let i = 0; i < options.length; i++) {
                const optionSN = options[i].dataset.serialNumber || '';
                if (optionSN.toUpperCase() === scannedSN || options[i].text.toUpperCase().includes(scannedSN)) {
                    serialSelect.selectedIndex = i;
                    
                    // Manually trigger change event for select element
                    serialSelect.dispatchEvent(new Event('change', { bubbles: true }));

                    found = true;
                    
                    // Trigger warehouse lock
                    if (serialSelect.onchange) {
                        serialSelect.onchange();
                    }
                    
                    closeQRScanner();
                    showSuccessDialog('Berhasil', `Serial Number ${scannedSN} berhasil dipilih!`);
                    break;
                }
            }
            
            // If not found, show red error message
            if (!found) {
                errorDiv.textContent = `✗ SN "${scannedSN}" tidak ditemukan atau tidak tersedia di warehouse ini!`;
                errorDiv.style.color = 'red';
                errorDiv.style.display = 'block';
                
                // Keep scanning, don't close modal
                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 3000);
            }
        },
        (errorMessage) => {
            // QR Code scan error (ignore, continuous scanning)
        }
    ).catch(err => {
        console.error('QR Scanner error:', err);
        showErrorDialog('Gagal membuka kamera. Pastikan izin kamera sudah diberikan.');
    });
}

function closeQRScanner() {
    if (qrCodeScanner) {
        qrCodeScanner.stop().then(() => {
            qrCodeScanner.clear();
        }).catch(err => console.error('Error stopping scanner:', err));
    }
    document.getElementById('qrScannerModal').classList.remove('show');
    currentScanningProductId = null;
}

async function submitManualIssuing() {
    // Validation
    const receivedBy = document.getElementById('receivedBySelect').value;
    const teamId = document.getElementById('teamSelect').value;
    const warehouseId = document.getElementById('warehouseSelect').value;
    const remarks = document.getElementById('remarksInput').value;

    if (!receivedBy) {
        showWarningDialog('Perhatian', 'Silakan pilih "Diberikan Kepada".');
        return;
    }

    if (!teamId) {
        showWarningDialog('Perhatian', 'Silakan pilih team.');
        return;
    }

    if (!warehouseId) {
        showWarningDialog('Perhatian', 'Silakan pilih warehouse.');
        return;
    }

    const formData = {
        received_by: receivedBy,
        team_id: teamId,
        warehouse_id: warehouseId,
        remarks: remarks
    };

    try {
        const response = await fetch('{{ route("warehouse.inventory-issuings.store-manual") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.status === 'success') {
            showSuccessDialog('Berhasil', 'Manual Inventory Issuing berhasil dibuat!');
            window.location.href = data.data.redirect_url;
        } else {
            showErrorDialog('Gagal membuat inventory issuing: ' + (data.message || 'Terjadi kesalahan.'));
        }
    } catch (error) {
        console.error('Error submitting manual issuing:', error);
        showErrorDialog('Terjadi kesalahan saat membuat inventory issuing.');
    }
}
</script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
@endsection
