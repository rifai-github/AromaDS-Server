@extends('layouts.app')

@section('title', 'Master Rentals')
@section('breadcrumb', 'Home / Warehouse / Master Rentals')

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
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 80px; min-width: 80px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(11), .responsive-table td:nth-child(11) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(12), .responsive-table td:nth-child(12) { width: 120px; min-width: 120px; }

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

    /* Modal Overlay Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1050;
        justify-content: center;
        align-items: center;
    }

    .modal-overlay.show {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        max-width: 90vw;
        max-height: 90vh;
        width: 800px;
        display: flex;
        flex-direction: column;
    }

    .modal-header {
        background: linear-gradient(135deg, #214589 0%, #1e3a8a 100%);
        color: white;
        padding: 20px;
        border-radius: 8px 8px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .modal-title {
        margin: 0;
        font-weight: 600;
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
    }

    .modal-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
        max-height: calc(90vh - 160px);
    }

    .modal-footer {
        padding: 15px 20px;
        border-top: 1px solid #dee2e6;
        background: #f8f9fa;
        border-radius: 0 0 8px 8px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        flex-shrink: 0;
    }

    .form-control-plaintext {
        padding: 0.375rem 0;
        margin-bottom: 0;
        line-height: 1.5;
        color: #495057;
        background-color: transparent;
        border: solid transparent;
        border-width: 1px 0;
    }

    /* Ensure form fields are accessible */
    .modal-overlay .form-control,
    .modal-overlay .form-select,
    .modal-overlay input,
    .modal-overlay textarea {
        pointer-events: auto !important;
        user-select: text !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
    }

    .modal-overlay .form-control:focus,
    .modal-overlay .form-select:focus {
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25) !important;
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Master Rentals Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Rentals</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-3">
                <a href="{{ route('warehouse.rental-management.service-frequencies') }}" class="btn btn-secondary">
                    <i class="fas fa-cog"></i>
                    <span class="hidden md:inline">Manage Freq</span>
                    <span class="md:hidden">Freq</span>
                </a>
                <button class="btn btn-primary" onclick="openAddRentalModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Rental</span>
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
                
                <!-- Search Input -->
                <div class="flex flex-row justify-start items-center w-auto ml-4">
                    <div class="relative">
                        <input type="text" id="searchInput" class="form-input pl-10 pr-4 py-2 w-64" placeholder="Search rentals..." value="{{ request('search') }}" onkeyup="handleSearch(event)">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Button -->
                <button class="btn btn-secondary btn-sm ml-4" onclick="bulkDeleteRentals()">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
            
        </div>

        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table" id="masterRentalsTable">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="rental_code">Rental Code</th>
                        <th data-column="rental_name">Rental Name</th>
                        <th data-column="serviceFrequency__name">Service Frequency</th>
                        <th data-column="category">Category</th>
                        <th data-column="is_active">Is Active</th>
                        <th data-column="daily_price" data-type="numeric">Daily Price</th>
                        <th data-column="monthly_price" data-type="numeric">Monthly Price</th>
                        <th data-column="install_duration" data-type="numeric">Durasi Install</th>
                        <th data-column="service_duration" data-type="numeric">Durasi Service</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="creator.name">Created By</th>
                        <th data-column="updated_at" data-type="date">Last Updated</th>
                        <th data-column="updater.name">Updated By</th>
                    </tr>
                </thead>
                <!-- Table Body -->
                <tbody>
                    @forelse($rentals ?? [] as $rental)
                    <tr data-id="{{ $rental->id }}" onclick="window.location.href='{{ route('warehouse.master-rentals.show', $rental->id) }}'" style="cursor: pointer;">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $rental->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ $rental->rental_code ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $rental->rental_name ?? '-' }}</td>
                        <td>{{ $rental->serviceFrequency ? $rental->serviceFrequency->name . ' (' . $rental->serviceFrequency->frequency_months . ' months, ' . $rental->serviceFrequency->frequency_times_per_month . ' times)' : '-' }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                {{ strtoupper($rental->category ?? '-') }}
                            </span>
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $rental->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $rental->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $rental->daily_price ? 'Rp ' . number_format($rental->daily_price, 2, ',', '.') : '-' }}</td>
                        <td>{{ $rental->monthly_price ? 'Rp ' . number_format($rental->monthly_price, 2, ',', '.') : '-' }}</td>
                        <td>{{ $rental->install_duration ? $rental->install_duration . ' menit' : '-' }}</td>
                        <td>{{ $rental->service_duration ? $rental->service_duration . ' menit' : '-' }}</td>
                        <td>
                            @if($rental->created_at)
                                @php
                                    $date = \Carbon\Carbon::parse($rental->created_at);
                                    $day = $date->format('d');
                                    $month = str_pad($date->format('n'), 2, '0', STR_PAD_LEFT);
                                    $year = $date->format('Y');
                                @endphp
                                {{ $day }}/{{ $month }}/{{ $year }}<br>
                                at {{ \Carbon\Carbon::parse($rental->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $rental->createdBy->name ?? '-' }}</td>
                        <td>
                            @if($rental->updated_at)
                                @php
                                    $date = \Carbon\Carbon::parse($rental->updated_at);
                                    $day = $date->format('d');
                                    $month = str_pad($date->format('n'), 2, '0', STR_PAD_LEFT);
                                    $year = $date->format('Y');
                                @endphp
                                {{ $day }}/{{ $month }}/{{ $year }}<br>
                                at {{ \Carbon\Carbon::parse($rental->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $rental->updatedBy->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-box-open text-4xl mb-3"></i>
                                <p class="text-lg">No master rentals found</p>
                                <button class="btn btn-primary mt-2" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Add First Rental
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                <!-- Previous Button -->
                @if(isset($rentals) && $rentals->currentPage() > 1)
                    <a href="{{ $rentals->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                <!-- Page Numbers -->
                @if(isset($rentals) && $rentals->hasPages())
                    @php
                        $start = max(1, $rentals->currentPage() - 2);
                        $end = min($rentals->lastPage(), $rentals->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $rentals->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $rentals->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $rentals->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $rentals->lastPage())
                            @if($end < $rentals->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $rentals->url($rentals->lastPage()) }}" class="page-number">{{ $rentals->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                <!-- Next Button -->
                @if(isset($rentals) && $rentals->hasMorePages())
                    <a href="{{ $rentals->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <!-- Page Dropdown -->
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $rentals->currentPage() ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $rentals->lastPage() ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Rental</h2>
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
        <h3 class="delete-modal-title">Sembunyikan Rental</h3>
        <p class="delete-modal-description" id="deleteMessage">Apakah Anda yakin ingin menyembunyikan rental ini? Tindakan ini masih bisa dibatalkan nanti.</p>
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
        <p class="delete-modal-description" id="errorMessage">Rental tidak berhasil disembunyikan. Silakan coba lagi.</p>
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
        <p class="delete-modal-description" id="successMessage">Rental berhasil disembunyikan.</p>
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
    openModal('Create New Rental');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="console.log('Form submit triggered!'); submitForm(event); return false;">
            <div class="modal-section">
                <div class="modal-section-title">Basic Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Rental Code</label>
                        <input type="text" name="rental_code" class="form-input" placeholder="Auto-generated if empty">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rental Name *</label>
                        <input type="text" name="rental_name" class="form-input" placeholder="Enter rental name" required>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Service Details</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Service Frequency *</label>
                        <select name="service_frequency_id" class="form-input" required>
                            <option value="">Select Service Frequency</option>
                            @foreach($serviceFrequencies as $frequency)
                                <option value="{{ $frequency->id }}">{{ $frequency->name }} ({{ $frequency->frequency_months }} months, {{ $frequency->frequency_times_per_month }} times)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" class="form-input" required>
                            <option value="">Select Category</option>
                            <option value="unit-refill">Unit + Refill</option>
                            <option value="unit-only">Unit Only</option>
                            <option value="refill-only">Refill Only</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Pricing</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-group">
                        <label class="form-label">Daily Price</label>
                        <input type="number" name="daily_price" class="form-input" min="0" step="0.01" placeholder="Enter daily price">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Monthly Price</label>
                        <input type="number" name="monthly_price" class="form-input" min="0" step="0.01" placeholder="Enter monthly price">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lost Unit Price</label>
                        <input type="number" name="lost_unit_price" class="form-input" min="0" step="0.01" placeholder="Enter lost unit price">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Durasi Kerja</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Durasi Install (menit)</label>
                        <input type="number" name="install_duration" class="form-input" min="0" step="1" placeholder="Masukkan durasi instalasi">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Durasi Service (menit)</label>
                        <input type="number" name="service_duration" class="form-input" min="0" step="1" placeholder="Masukkan durasi service">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Additional Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Unit</label>
                        <select name="unit" class="form-input">
                            <option value="">Select Unit</option>
                            @if($unitOptions && $unitOptions->optionDetails)
                                @foreach($unitOptions->optionDetails as $unit)
                                    <option value="{{ $unit->option_name }}">{{ $unit->option_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Active Status</label>
                        <select name="is_active" class="form-input">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Description</div>
                <div class="grid grid-cols-1 gap-6">
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input form-textarea" placeholder="Enter rental description"></textarea>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    // Add modal footer
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" form="form" class="btn btn-primary">Create Rental</button>
    `;
}

function openViewModal(id) {
    openModal('View Rental');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/warehouse/master-rentals/${id}`, {
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
                    <div class="modal-section-title">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Rental Code</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    ${data.data.rental_code || '-'}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Rental Name</label>
                            <p class="detail-value">${data.data.rental_name || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Service Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Service Frequency</label>
                            <p class="detail-value">${data.data.service_frequency ? data.data.service_frequency.name + ' (' + data.data.service_frequency.frequency_months + ' months, ' + data.data.service_frequency.frequency_times_per_month + ' times)' : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Category</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                    ${data.data.category ? data.data.category.toUpperCase() : '-'}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Pricing</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Daily Price</label>
                            <p class="detail-value">${data.data.daily_price ? 'Rp ' + data.data.daily_price.toLocaleString('id-ID', {minimumFractionDigits: 2}) : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Monthly Price</label>
                            <p class="detail-value">${data.data.monthly_price ? 'Rp ' + data.data.monthly_price.toLocaleString('id-ID', {minimumFractionDigits: 2}) : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Lost Unit Price</label>
                            <p class="detail-value">${data.data.lost_unit_price ? 'Rp ' + data.data.lost_unit_price.toLocaleString('id-ID', {minimumFractionDigits: 2}) : '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Durasi Standar</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Durasi Install</label>
                            <p class="detail-value">${data.data.install_duration ? data.data.install_duration + ' menit' : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Durasi Service</label>
                            <p class="detail-value">${data.data.service_duration ? data.data.service_duration + ' menit' : '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Additional Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Unit</label>
                            <p class="detail-value">${data.data.unit || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Active Status</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full ${data.data.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${data.data.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Description</div>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Description</label>
                            <p class="detail-value">${data.data.description || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Status & Information</div>
                    <div class="grid grid-cols-1 gap-6">
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
                            <p class="detail-value">${data.data.created_at ? new Date(data.data.created_at).toLocaleString('id-ID') : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated At</label>
                            <p class="detail-value">${data.data.updated_at ? new Date(data.data.updated_at).toLocaleString('id-ID') : '-'}</p>
                        </div>
                    </div>
                </div>
            `;
        
        // Add modal footer for view modal
            document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit Rental</button>
        `;
        })
        .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Gagal memuat detail.</div>';
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
        `;
        });
}

function openEditModal(id) {
    openModal('Edit Rental');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/warehouse/master-rentals/${id}/edit`, {
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
                <form id="form" onsubmit="console.log('Form submit triggered with ID:', ${id}); submitForm(event, ${id}); return false;">
                    <input type="hidden" name="id" value="${data.data.id}">
                    <div class="modal-section">
                        <div class="modal-section-title">Basic Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Rental Code *</label>
                                <input type="text" name="rental_code" class="form-input" value="${data.data.rental_code || ''}" placeholder="Enter rental code" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rental Name *</label>
                                <input type="text" name="rental_name" class="form-input" value="${data.data.rental_name || ''}" placeholder="Enter rental name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Service Details</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Service Frequency *</label>
                                <select name="service_frequency_id" class="form-input" required>
                                    <option value="">Select Service Frequency</option>
                                    @foreach($serviceFrequencies as $frequency)
                                        <option value="{{ $frequency->id }}" \${data.data.service_frequency_id == '{{ $frequency->id }}' ? 'selected' : ''}>{{ $frequency->name }} ({{ $frequency->frequency_months }} months, {{ $frequency->frequency_times_per_month }} times)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-input" required>
                                    <option value="">Select Category</option>
                                    <option value="unit-refill" ${data.data.category === 'unit-refill' ? 'selected' : ''}>Unit + Refill</option>
                                    <option value="unit-only" ${data.data.category === 'unit-only' ? 'selected' : ''}>Unit Only</option>
                                    <option value="refill-only" ${data.data.category === 'refill-only' ? 'selected' : ''}>Refill Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Pricing</div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="form-group">
                                <label class="form-label">Daily Price</label>
                                <input type="number" name="daily_price" class="form-input" value="${data.data.daily_price || ''}" min="0" step="0.01" placeholder="Enter daily price">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Monthly Price</label>
                                <input type="number" name="monthly_price" class="form-input" value="${data.data.monthly_price || ''}" min="0" step="0.01" placeholder="Enter monthly price">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Lost Unit Price</label>
                                <input type="number" name="lost_unit_price" class="form-input" value="${data.data.lost_unit_price || ''}" min="0" step="0.01" placeholder="Enter lost unit price">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Durasi Standar</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Durasi Install (menit)</label>
                                <input type="number" name="install_duration" class="form-input" value="${data.data.install_duration || ''}" min="0" step="1" placeholder="Masukkan durasi instalasi">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Durasi Service (menit)</label>
                                <input type="number" name="service_duration" class="form-input" value="${data.data.service_duration || ''}" min="0" step="1" placeholder="Masukkan durasi service">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Additional Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Unit</label>
                                <select name="unit" class="form-input" id="edit_unit">
                                    <option value="">Select Unit</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Active Status</label>
                                <select name="is_active" class="form-input">
                                    <option value="1" ${data.data.is_active ? 'selected' : ''}>Active</option>
                                    <option value="0" ${!data.data.is_active ? 'selected' : ''}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Description</div>
                        <div class="grid grid-cols-1 gap-6">
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-input form-textarea" placeholder="Enter rental description">${data.data.description || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" form="form" class="btn btn-primary">Update Rental</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Gagal memuat detail.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            `;
        });
}

function submitForm(event, id = null) {
    console.log('=== submitForm CALLED ===');
    console.log('Event:', event);
    console.log('ID:', id);
    
    if (!event) {
        console.error('No event object!');
        return;
    }
    
    event.preventDefault();
    console.log('Event prevented default');
    
    // Show loading state first
    const submitButton = event.target.querySelector('button[type="submit"]') || 
                        document.querySelector('button[form="form"]');
    const originalButtonText = submitButton?.textContent;
    console.log('Submit button found:', !!submitButton);
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Menyimpan...';
    }
    
    // Get CSRF token from meta tag
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = metaTag ? metaTag.getAttribute('content') : null;
    
    // Debug logging
    console.log('=== CSRF TOKEN DEBUG ===');
    console.log('Meta token:', csrfToken ? csrfToken.substring(0, 20) + '...' : 'NOT FOUND');
    console.log('Full token length:', csrfToken ? csrfToken.length : 0);
    console.log('Meta tag exists:', !!metaTag);
    
    if (!csrfToken) {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
        showErrorDialog('Gagal', 'CSRF token tidak ditemukan. Silakan refresh halaman (Ctrl+Shift+R) dan coba lagi.');
        return;
    }
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/warehouse/master-rentals/${id}` : '/warehouse/master-rentals';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token (exact same as master-products which works)
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    // Convert boolean strings to actual booleans
    if (data.is_active !== undefined) {
        data.is_active = data.is_active === '1' || data.is_active === true || data.is_active === 'true';
    }
    
    // Debug: Log what we're sending
    console.log('Request URL:', url);
    console.log('Request data keys:', Object.keys(data));
    console.log('Token in data._token:', data._token ? data._token.substring(0, 20) + '...' : 'NOT FOUND');
    console.log('Token in header X-CSRF-TOKEN:', csrfToken ? csrfToken.substring(0, 20) + '...' : 'NOT FOUND');
    
    // Use exact same pattern as master-products (which works)
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            if (response.status === 422) {
                return response.json().then(data => {
                    console.error('Validation error:', data);
                    throw new Error('Validation failed: ' + JSON.stringify(data));
                });
            }
            if (response.status === 419) {
                console.error('419 Error - CSRF token mismatch!');
                console.error('Response text:', response.text());
                throw new Error('Session expired. Please refresh the page (Ctrl+Shift+R) and try again.');
            }
            return response.text().then(text => {
                console.error('Server error response:', text);
                throw new Error('Server error: ' + text);
            });
        }
        return response.json();
    })
    .then(result => {
        // Restore button state
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
        
        if (result.status === 'success') {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: result.message || 'Master Rental berhasil dibuat.',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: result?.message || 'Terjadi kesalahan.'
            });
        }
    })
    .catch(error => {
        // Restore button state
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
        
        console.error('Error:', error);
        
        if (error.message && error.message.includes('Session expired')) {
            Swal.fire({
                icon: 'warning',
                title: 'Sesi Berakhir',
                text: error.message,
                confirmButtonText: 'Tutup'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: error.message || 'Terjadi kesalahan.'
            });
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
        ? 'Are you sure you want to hide this rental? This action can be undone later.'
        : `Are you sure you want to hide ${count} rentals? This action can be undone later.`;
    
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
    
    fetch('/warehouse/master-rentals/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
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
    .then(result => {
        if (result.status === 'success') {
            showSuccessModal(selectedIdsForRetry.length);
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showErrorModal(result.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan jaringan: ' + error.message);
    });
}

// Bulk operations
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Perhatian', 'Silakan pilih minimal satu rental yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'Rental berhasil disembunyikan.'
        : `${count} rental berhasil disembunyikan.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'Rental tidak berhasil disembunyikan. Silakan coba lagi.';
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

// Bulk delete functionality
function bulkDeleteRentals() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Perhatian', 'Silakan pilih minimal satu rental yang ingin dihapus.');
        return;
    }
    
    const ids = Array.from(checkboxes).map(cb => cb.value);

    showConfirmDialog(
        'Hapus Rental',
        `Apakah Anda yakin ingin menghapus ${ids.length} rental?`,
        'Ya, Hapus',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) {
            return;
        }

        fetch('/warehouse/master-rentals/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ ids: ids })
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
        .then(result => {
            if (result.status === 'success') {
                showSuccessModal(ids.length);
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showErrorModal(result.message || 'Terjadi kesalahan.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Terjadi kesalahan jaringan: ' + error.message);
        });
    });
}

// Toggle status functionality
function toggleStatus(id) {
    showConfirmDialog(
        'Ubah Status Rental',
        'Apakah Anda yakin ingin mengubah status rental ini?',
        'Ya, Ubah',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) {
            return;
        }

        fetch(`/warehouse/master-rentals/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
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
        .then(result => {
            if (result.status === 'success') {
                showSuccessModal(1);
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showErrorModal(result.message || 'Terjadi kesalahan.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Terjadi kesalahan jaringan: ' + error.message);
        });
    });
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

// Modal Control Functions
let currentRentalId = null;

function openAddRentalModal() {
    document.getElementById('rentalModalTitle').textContent = 'Tambah Master Rental';
    document.getElementById('rentalSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Simpan Rental';
    document.getElementById('rentalForm').reset();
    document.getElementById('rentalForm').action = '{{ route("warehouse.master-rentals.store") }}';
    document.getElementById('rentalForm').method = 'POST';
    currentRentalId = null;
    document.getElementById('rentalModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeRentalModal() {
    document.getElementById('rentalModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function showRental(id) {
    fetch(`/warehouse/master-rentals/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const rental = data.data;
                console.log('Rental data:', rental);
                console.log('Service frequency:', rental.service_frequency);
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Rental Code</label>
                                <p class="form-control-plaintext">${rental.rental_code}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Rental Name</label>
                                <p class="form-control-plaintext">${rental.rental_name}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">Rental Type</label>
                                <p class="form-control-plaintext">${rental.rental_type_text || rental.rental_type || '-'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <p class="form-control-plaintext">${rental.description || 'No description'}</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Service Frequency</label>
                                <p class="form-control-plaintext">${rental.service_frequency ? rental.service_frequency.name : 'N/A'}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <p class="form-control-plaintext">${rental.category}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Daily Price</label>
                                <p class="form-control-plaintext">${rental.daily_price ? 'Rp ' + rental.daily_price.toLocaleString() : 'N/A'}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Monthly Price</label>
                                <p class="form-control-plaintext">${rental.monthly_price ? 'Rp ' + rental.monthly_price.toLocaleString() : 'N/A'}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Unit</label>
                                <p class="form-control-plaintext">${rental.unit || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <p class="form-control-plaintext">
                            <span class="badge ${rental.is_active ? 'badge-success' : 'badge-danger'}">
                                ${rental.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </p>
                    </div>
                `;
                
                document.getElementById('showRentalContent').innerHTML = content;
                document.getElementById('showRentalModal').classList.add('show');
                document.body.style.overflow = 'hidden';
                currentRentalId = id;
            } else {
                showErrorDialog('Gagal', 'Detail rental tidak berhasil dimuat.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan.');
        });
}

function editRentalFromShow() {
    if (currentRentalId) {
        editRental(currentRentalId);
    }
}

function editRental(id) {
    fetch(`/warehouse/master-rentals/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const rental = data.data;
                const serviceFrequencies = data.serviceFrequencies;
                const productCategories = data.productCategories;
                const unitOptions = data.unitOptions;
                
                // Reset form first
                document.getElementById('rentalForm').reset();
                
                // Fill form with rental data
                document.getElementById('rental_code').value = rental.rental_code;
                document.getElementById('rental_name').value = rental.rental_name;
                document.getElementById('rental_type').value = rental.rental_type || 'unit_refill';
                document.getElementById('daily_price').value = rental.daily_price || '';
                document.getElementById('monthly_price').value = rental.monthly_price || '';
                document.getElementById('lost_unit_price').value = rental.lost_unit_price || '';
                document.getElementById('install_duration').value = rental.install_duration || '';
                document.getElementById('service_duration').value = rental.service_duration || '';
                document.getElementById('is_active').checked = rental.is_active;
                
                // Update service frequency dropdown
                const serviceFrequencySelect = document.getElementById('service_frequency_id');
                serviceFrequencySelect.innerHTML = '<option value="">Select Service Frequency</option>';
                serviceFrequencies.forEach(frequency => {
                    const option = document.createElement('option');
                    option.value = frequency.id;
                    option.textContent = `${frequency.name} (${frequency.frequency_months} months, ${frequency.frequency_times_per_month} times)`;
                    if (frequency.id == rental.service_frequency_id) {
                        option.selected = true;
                    }
                    serviceFrequencySelect.appendChild(option);
                });

                // Update category dropdown
                const categorySelect = document.getElementById('category');
                categorySelect.innerHTML = '<option value="">Select Category</option>';
                productCategories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.name;
                    option.textContent = category.name;
                    if (category.name === rental.category) {
                        option.selected = true;
                    }
                    categorySelect.appendChild(option);
                });

                // Update modal title and submit button
                document.getElementById('rentalModalTitle').textContent = 'Edit Master Rental';
                document.getElementById('rentalSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Update Rental';
                
                // Update form action
                document.getElementById('rentalForm').action = `/warehouse/master-rentals/${id}`;
                document.getElementById('rentalForm').method = 'POST';
                
                // Add hidden input for PUT method
                let methodInput = document.getElementById('_method');
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.id = '_method';
                    document.getElementById('rentalForm').appendChild(methodInput);
                }
                methodInput.value = 'PUT';
                
                // Close show modal and open edit modal
                closeShowRentalModal();
                document.getElementById('rentalModal').classList.add('show');
                document.body.style.overflow = 'hidden';
                currentRentalId = id;
                
                // Ensure form fields are enabled and focusable
                const formFields = document.querySelectorAll('#rentalForm input, #rentalForm textarea, #rentalForm select');
                formFields.forEach(field => {
                    field.disabled = false;
                    field.readOnly = false;
                    field.style.pointerEvents = 'auto';
                });
            } else {
                showErrorDialog('Gagal', 'Data rental tidak berhasil dimuat.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan.');
        });
}

function closeShowRentalModal() {
    document.getElementById('showRentalModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}


</script>

<!-- Add/Edit Rental Modal -->
<div class="modal-overlay" id="rentalModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="rentalModalTitle">Tambah Master Rental</h5>
            <button type="button" class="modal-close" onclick="closeRentalModal()">&times;</button>
        </div>
        <form id="rentalForm" action="{{ route('warehouse.master-rentals.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="rental_code" class="form-label">Rental Code</label>
                            <input type="text" class="form-control" id="rental_code" name="rental_code" placeholder="Auto-generated if empty">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="rental_name" class="form-label">Rental Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="rental_name" name="rental_name" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="rental_type" class="form-label">Rental Type (Spesifikasi) <span class="text-danger">*</span></label>
                            <select class="form-control" id="rental_type" name="rental_type" required>
                                <option value="unit_refill" selected>Unit + Refill</option>
                                <option value="unit_only">Unit Only</option>
                                <option value="refill_only">Refill Only</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="service_frequency_id" class="form-label">Service Frequency <span class="text-danger">*</span></label>
                            <select class="form-control" id="service_frequency_id" name="service_frequency_id" required>
                                <option value="">Select Service Frequency</option>
                                @foreach($serviceFrequencies as $frequency)
                                    <option value="{{ $frequency->id }}">{{ $frequency->name }} ({{ $frequency->frequency_months }} months, {{ $frequency->frequency_times_per_month }} times)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Select Category</option>
                                @foreach($productCategories as $category)
                                    <option value="{{ $category->name }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="daily_price" class="form-label">Daily Price</label>
                            <input type="number" class="form-control" id="daily_price" name="daily_price" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="monthly_price" class="form-label">Monthly Price</label>
                            <input type="number" class="form-control" id="monthly_price" name="monthly_price" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="lost_unit_price" class="form-label">Lost Unit Price</label>
                            <input type="number" class="form-control" id="lost_unit_price" name="lost_unit_price" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="install_duration" class="form-label">Durasi Install (menit)</label>
                            <input type="number" class="form-control" id="install_duration" name="install_duration" step="1" min="0" placeholder="Masukkan durasi instalasi">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="service_duration" class="form-label">Durasi Service (menit)</label>
                            <input type="number" class="form-control" id="service_duration" name="service_duration" step="1" min="0" placeholder="Masukkan durasi service">
                        </div>
                    </div>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRentalModal()">Batal</button>
                <button type="button" class="btn btn-primary" id="rentalSubmitBtn" onclick="handleRentalSubmit(event)">
                    <i class="fas fa-save me-1"></i>
                    Simpan Rental
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Show Rental Modal -->
<div class="modal-overlay" id="showRentalModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Detail Rental</h5>
            <button type="button" class="modal-close" onclick="closeShowRentalModal()">&times;</button>
        </div>
        <div class="modal-body" id="showRentalContent">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeShowRentalModal()">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="editRentalFromShow()">
                <i class="fas fa-edit me-1"></i>
                Edit Rental
            </button>
        </div>
    </div>
</div>

<script>
// Direct function handler - bypasses all event propagation issues
function handleRentalSubmit(event) {
    console.log('=== handleRentalSubmit CALLED ===');
    event.preventDefault();
    event.stopPropagation();
    
    const rentalForm = document.getElementById('rentalForm');
    if (!rentalForm) {
        console.error('ERROR: rentalForm not found!');
        showErrorDialog('Gagal', 'Form tidak ditemukan.');
        return;
    }
    
    console.log('Form found, preparing submission...');
    
    const formData = new FormData(rentalForm);
    const url = rentalForm.action;
    const method = rentalForm.method;
    
    // Handle checkbox properly
    const isActiveCheckbox = document.getElementById('is_active');
    if (isActiveCheckbox) {
        if (isActiveCheckbox.checked) {
            formData.set('is_active', '1');
        } else {
            formData.set('is_active', '0');
        }
    }
    
    console.log('Submitting to:', url);
    console.log('Method:', method);
    console.log('Form data:', Object.fromEntries(formData));
    
    // Disable button to prevent double submission
    const submitBtn = document.getElementById('rentalSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
    }
    
    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            return response.text().then(text => {
                console.log('Response text:', text);
                throw new Error(`HTTP ${response.status}: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.status === 'success') {
            console.log('Success! Closing modal and reloading...');
            closeRentalModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Terjadi kesalahan.');
            // Re-enable button on error
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Simpan Rental';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan saat menyimpan rental: ' + error.message);
        // Re-enable button on error
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Simpan Rental';
        }
    });
}

console.log('✅ handleRentalSubmit function defined');
</script>

@endsection
