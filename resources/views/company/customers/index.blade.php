@extends('layouts.app')

@section('title', 'Customers - Company')
@section('breadcrumb', 'Home / Company / Customers')

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

    /* Form Section Styles */
    .form-section {
        background: #ffffff;
        padding: 24px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }

    .form-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        font-size: 18px;
    }

    /* Consistent Form Input Styles - Ensure all inputs have same height */
    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="date"],
    input[type="number"],
    textarea,
    select {
        height: 44px !important;
        padding: 10px 16px !important;
        font-size: 14px !important;
        line-height: 1.5 !important;
    }

    textarea {
        height: auto !important;
        min-height: 88px !important;
    }

    /* Ensure select dropdowns align properly */
    select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
        background-position: right 12px center;
        background-repeat: no-repeat;
        background-size: 20px;
        padding-right: 40px !important;
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
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 100px; min-width: 100px; }
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
        max-width: 95vw;
        max-height: 95vh;
        width: 800px;
        overflow: hidden;
        position: relative;
    }
    
    .modal-container.large {
        width: 1000px;
        max-width: 98vw;
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
        margin-bottom: 4px;
    }
    
    .form-value {
        font-size: 14px;
        color: #111827;
        margin: 0;
        padding: 8px 0;
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
        
        <!-- Customers Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Customers</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <!-- Status Filter -->
                @include('components.filter-status')

                <button class="btn btn-secondary" onclick="openInfoModal()">
                    <i class="fas fa-info-circle"></i>
                    <span class="hidden md:inline">How to Create Customer</span>
                    <span class="md:hidden">Info</span>
                </button>
                @if(auth()->user() && auth()->user()->hasPermission('company.customers.create'))
                <button class="btn btn-secondary" onclick="openCustomerImportModal()">
                    <i class="fas fa-file-import"></i>
                    <span class="hidden md:inline">Import Excel/CSV</span>
                    <span class="md:hidden">Import</span>
                </button>
                @endif
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Customer</span>
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
                        <th data-column="customer_code">Customer Code</th>
                        <th data-column="company_type">Badan Hukum</th>
                        <th data-column="name">Name</th>
                        <th data-column="classification__option_name">Classification</th>
                        <th data-column="label_alias">Label / Alias</th>
                        <th data-column="customerType__name">Category</th>
                        <th data-column="email">Email</th>
                        <th data-column="phone">Phone</th>
                        <th data-column="assignedTo__name">Assigned To</th>
                        <th data-column="is_active">Status</th>
                        <th data-column="is_pkp">PKP</th>
                        <th data-no-filter>Actions</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="creator__name">Created By</th>
                        <th data-column="updater__name">Updated By</th>
                        <th data-column="updated_at" data-type="date">Updated At</th>
                    </tr>
                </thead>
            
                <!-- Table Body -->
                <tbody>
                    @forelse($customers ?? [] as $customer)
                    <tr data-id="{{ $customer->id }}" onclick="window.location='{{ route('company.customers.show', $customer->id) }}'" class="cursor-pointer">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $customer->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $customer->customer_code ?? '-' }}</td>
                        <td>{{ $customer->company_type ? ucfirst($customer->company_type) : '-' }}</td>
                        <td>{{ $customer->name ?? '-' }}</td>
                        <td>{{ $customer->classification->option_name ?? '-' }}</td>
                        <td>{{ $customer->label_alias ?? '-' }}</td>
                        <td>{{ $customer->customerType->name ?? '-' }}</td>
                        <td>{{ $customer->email ?? '-' }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td>
                            @if($customer->contacts && $customer->contacts->count() > 0)
                                @foreach($customer->contacts as $contact)
                                    <div class="flex items-center gap-1 mb-1 last:mb-0">
                                        <i class="fas fa-user-circle text-gray-400 text-xs"></i>
                                        <span>{{ ($contact->salutation ? $contact->salutation . ' ' : '') . $contact->name }}</span>
                                        @if($contact->pivot && $contact->pivot->is_primary)
                                            <span class="text-[10px] bg-green-100 text-green-700 px-1 rounded">Primary</span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                {{ $customer->assignedTo->name ?? '-' }}
                            @endif
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $customer->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $customer->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $customer->is_pkp ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $customer->is_pkp ? 'PKP' : 'Non-PKP' }}
                            </span>
                        </td>
                        <td onclick="event.stopPropagation()">
                            <div class="flex gap-1">
                                <button class="btn btn-sm btn-secondary" onclick="openStatusModal({{ $customer->id }}, '{{ $customer->name }}', 'is_pkp', {{ $customer->is_pkp ? 'true' : 'false' }})" title="Update PKP Status">
                                    <i class="fas fa-receipt"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="openStatusModal({{ $customer->id }}, '{{ $customer->name }}', 'is_active', {{ $customer->is_active ? 'true' : 'false' }})" title="Update Active Status">
                                    <i class="fas fa-toggle-on"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            @if($customer->created_at)
                                {{ \Carbon\Carbon::parse($customer->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($customer->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $customer->createdBy->name ?? '-' }}</td>
                        <td>{{ $customer->updatedBy->name ?? '-' }}</td>
                        <td>
                            @if($customer->updated_at)
                                @php
                                    $date = \Carbon\Carbon::parse($customer->updated_at);
                                    $day = $date->format('d');
                                    $month = $date->format('M');
                                    $year = $date->format('Y');
                                @endphp
                                {{ $day }}/{{ $month }}/{{ $year }}<br>
                                at {{ \Carbon\Carbon::parse($customer->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-users text-4xl mb-3"></i>
                                <p class="text-lg">No customers found</p>
                                <button class="btn btn-primary mt-2" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Add New Customer
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($customers->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $customers->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="40" cy="40" r="38" stroke="#1e40af" stroke-width="4" fill="none"/>
                <text x="40" y="52" text-anchor="middle" font-size="40" fill="#1e40af">!</text>
            </svg>
        </div>
        <h3 class="delete-modal-title">Are You Sure?</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide the selected customer(s)? This action can be undone later.</p>
        <div class="delete-modal-buttons">
            <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn-hide" onclick="confirmDelete()">Yes, Hide</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="40" cy="40" r="38" stroke="#16a34a" stroke-width="4" fill="none"/>
                <path d="M25 42L35 52L55 32" stroke="#16a34a" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h3 class="delete-modal-title" style="color: #16a34a;">Success!</h3>
        <p class="delete-modal-description" id="successMessage">Operation completed successfully.</p>
        <div class="delete-modal-buttons">
            <button type="button" class="btn-hide" style="background-color: #16a34a; border-color: #16a34a;" onclick="closeSuccessModal()">OK</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="40" cy="40" r="38" stroke="#dc2626" stroke-width="4" fill="none"/>
                <text x="40" y="52" text-anchor="middle" font-size="40" fill="#dc2626">!</text>
            </svg>
        </div>
        <h3 class="delete-modal-title" style="color: #dc2626;">Hmm... Something Went Wrong</h3>
        <p class="delete-modal-description" id="errorMessage">An error occurred. Please try again.</p>
        <div class="delete-modal-buttons">
            <button type="button" class="btn-hide" style="background-color: #dc2626; border-color: #dc2626;" onclick="closeErrorModal()">OK, I'll Try Again</button>
        </div>
    </div>
</div>

<!-- Information Modal - Customer Creation Process -->
<div id="infoModalOverlay" class="modal-overlay" onclick="closeInfoModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Customer Creation Process</h2>
            <button class="modal-close" onclick="closeInfoModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
                    <div>
                        <h3 class="text-lg font-semibold text-blue-800">Automatic Customer Creation</h3>
                        <p class="text-blue-700 mt-1">Customers are created automatically through the marketing pipeline process.</p>
                    </div>
                </div>
            </div>
            
            <div class="space-y-4">
                <h4 class="text-lg font-semibold text-gray-800">Customer Creation Workflow:</h4>
                
                <div class="space-y-3">
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold mr-3">1</div>
                        <div>
                            <h5 class="font-semibold text-gray-800">Prospect Entry</h5>
                            <p class="text-gray-600 text-sm">Marketing staff creates a new prospect</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold mr-3">2</div>
                        <div>
                            <h5 class="font-semibold text-gray-800">Location Survey</h5>
                            <p class="text-gray-600 text-sm">Conduct survey and input location data</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold mr-3">3</div>
                        <div>
                            <h5 class="font-semibold text-gray-800">Quotation Creation</h5>
                            <p class="text-gray-600 text-sm">Create quotation based on survey results</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-semibold mr-3">4</div>
                        <div>
                            <h5 class="font-semibold text-gray-800">Contract Approval</h5>
                            <p class="text-gray-600 text-sm">When quotation is approved, contract is auto-generated</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-semibold mr-3">5</div>
                        <div>
                            <h5 class="font-semibold text-gray-800">Customer Creation</h5>
                            <p class="text-gray-600 text-sm">Customer is automatically created from prospect data</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-lg mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-yellow-800">Important Note</h4>
                            <p class="text-yellow-700 text-sm mt-1">To create a new customer, please follow the marketing pipeline: Prospect → Survey → Quotation → Contract</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="closeInfoModal()">Understood</button>
        </div>
    </div>
</div>

<!-- Create Customer Modal -->
<div id="createModalOverlay" class="modal-overlay" onclick="closeCreateModal()">
    <div class="modal-container large" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Add New Customer</h2>
            <button class="modal-close" onclick="closeCreateModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
            <form id="createForm">
                <!-- Basic Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-building text-blue-500"></i>
                        Basic Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <!-- Row 1: Badan Hukum & Customer Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Badan Hukum *</label>
                            <select id="create_company_type" name="company_type" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                <option value="">Pilih Badan Hukum</option>
                                <!-- Options loaded dynamically from MasterOption ID 14 -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name *</label>
                            <input type="text" id="create_name" name="name" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="Enter customer name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Classification</label>
                            <select id="create_classification_id" name="classification_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Classification</option>
                                @foreach($classificationOptions as $option)
                                    <option value="{{ $option->id }}">{{ $option->option_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Row 2: Label/Alias & Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Label / Alias</label>
                            <input type="text" id="create_label_alias" name="label_alias" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Short name or alias">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select id="create_customer_category_id" name="customer_category_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih Category</option>
                                <!-- Options loaded dynamically from /system/customer-types/api/list -->
                            </select>
                        </div>

                        <!-- Customer Code field - Hidden because auto-generated -->
                        <div style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer Code</label>
                            <input type="text" id="create_customer_code" name="customer_code" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed" readonly placeholder="Auto-generated">
                            <small class="text-gray-500 text-xs mt-1 block">Customer code will be auto-generated</small>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-address-book text-purple-500"></i>
                        Contact Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" id="create_email" name="email" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="email@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="tel" id="create_phone" name="phone" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="08123456789" pattern="[0-9\-\+\(\)\s]+" oninput="this.value = this.value.replace(/[^0-9\-\+\(\)\s]/g, '')">
                            <small class="text-gray-500 text-xs mt-1 block">Only numbers, +, -, (, ), and spaces allowed</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select id="create_status" name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                <option value="">Select Status</option>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Payment & Tax Information Section (NEW) -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-file-invoice-dollar text-green-500"></i>
                        Payment Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer Group</label>
                            <input type="text" id="create_customer_group" name="customer_group" maxlength="50" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., Sinarmas, Unilever, Indofood">
                            <small class="text-gray-500 text-xs mt-1 block">Grup perusahaan customer (max 50 karakter)</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Default Bank Payment</label>
                            <select id="create_default_bank_payment_id" name="default_bank_payment_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih Bank Payment</option>
                                @foreach($bankPayments as $bankPayment)
                                    <option value="{{ $bankPayment->id }}">{{ $bankPayment->bank->name ?? '' }} - {{ $bankPayment->account_name }} ({{ $bankPayment->account_number }})</option>
                                @endforeach
                            </select>
                            <small class="text-gray-500 text-xs mt-1 block">Bank default untuk pembayaran</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">NIB (Nomor Induk Berusaha)</label>
                            <input type="text" id="create_nib" name="nib" maxlength="50" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nomor Induk Berusaha">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Multi PIC (Person In Charge)</label>
                            <div class="flex gap-2 items-start">
                                <select id="create_contact_ids" name="contact_ids[]" multiple class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" style="min-height: 120px;">
                                    @foreach($allContacts as $contact)
                                        <option value="{{ $contact->id }}">{{ $contact->name }} - {{ $contact->position ?? 'No Position' }} ({{ $contact->phone }})</option>
                                    @endforeach
                                </select>
                                <button type="button" onclick="openCreateContactModal()" class="px-3 bg-green-500 text-white rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors flex items-center justify-center border-2 border-green-600 shadow-md" title="Add New Contact" style="min-width: 44px; height: 44px; background-color: #22c55e !important;">
                                    <i class="fas fa-plus" style="font-size: 16px; color: white;"></i>
                                </button>
                            </div>
                            <small class="text-gray-500 text-xs mt-1 block">Tekan Ctrl+Click untuk pilih lebih dari satu. Klik <i class="fas fa-plus text-green-600"></i> untuk tambah contact baru.</small>
                        </div>
                    </div>
                </div>

                <!-- Location & Address Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-map-marker-alt text-red-500"></i>
                        Location & Address
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Province</label>
                            <select id="create_province_id" name="province_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="loadCitiesForCreate(this.value)">
                                <option value="">Select Province</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                            <select id="create_city_id" name="city_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="loadDistrictsForCreate(this.value)">
                                <option value="">Select City</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">District</label>
                            <select id="create_district_id" name="district_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="loadSubdistrictsForCreate(this.value); clearPostalCodeForCreate();">
                                <option value="">Select District</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Subdistrict</label>
                            <select id="create_subdistrict_id" name="subdistrict_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="loadPostalCodeForCreate(this.value)">
                                <option value="">Select Subdistrict</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                            <input type="text" id="create_postal_code" name="postal_code" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50" readonly placeholder="Auto-filled">
                            <small class="text-gray-500 text-xs mt-1 block">Auto-filled from subdistrict</small>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Address *</label>
                            <textarea id="create_address" name="address" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="3" required placeholder="Enter complete address"></textarea>
                        </div>
                    </div>
                </div>



                <!-- Settings Section -->
                <div class="form-section mb-0">
                    <h3 class="section-title">
                        <i class="fas fa-cog text-gray-500"></i>
                        Settings
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <input type="checkbox" id="create_is_pkp" name="is_pkp" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3">
                            <label for="create_is_pkp" class="text-sm font-medium text-gray-700 cursor-pointer">
                                PKP (Pengusaha Kena Pajak)
                            </label>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <input type="checkbox" id="create_is_active" name="is_active" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3" checked>
                            <label for="create_is_active" class="text-sm font-medium text-gray-700 cursor-pointer">
                                Active Status
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Create Customer</button>
        </div>
    </div>
</div>

<!-- Create Contact Modal (Inline) -->
<div id="createContactModalOverlay" class="modal-overlay" onclick="closeCreateContactModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Add New Contact Person</h2>
            <button class="modal-close" onclick="closeCreateContactModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="createContactForm">
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user text-purple-500"></i>
                        Contact Person Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Salutation</label>
                            <select id="contact_salutation_id" name="salutation" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Salutation</option>
                                <!-- Options loaded dynamically from salutations API -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" id="contact_name" name="name" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="Enter full name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" id="contact_email" name="email" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="email@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="tel" id="contact_phone" name="phone" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="08123456789" pattern="[0-9\-\+\(\)\s]+" oninput="this.value = this.value.replace(/[^0-9\-\+\(\)\s]/g, '')">
                            <small class="text-gray-500 text-xs mt-1 block">Only numbers, +, -, (, ), and spaces allowed</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                            <select id="contact_position_id" name="position" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Position</option>
                                <!-- Options loaded dynamically from positions API -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
                            <input type="text" id="contact_customer_name" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100" readonly placeholder="Will be linked after customer is created">
                            <input type="hidden" id="contact_customer_id" name="customer_id">
                            <small class="text-gray-500 text-xs mt-1 block">This contact will be automatically assigned to the new customer after creation</small>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCreateContactModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateContactForm()">Add Contact</button>
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModalOverlay" class="modal-overlay" onclick="closeViewModal()">
    <div class="modal-container large" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">View Customer</h2>
            <button class="modal-close" onclick="closeViewModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <!-- Basic Information Section -->
            <div class="modal-section">
                <h3 class="modal-section-title">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Customer Code</label>
                        <p id="viewCustomerCode" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">Name</label>
                        <p id="viewName" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">Classification</label>
                        <p id="viewClassification" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">Label / Alias</label>
                        <p id="viewLabelAlias" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">Category</label>
                        <p id="viewCategory" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">Badan Hukum</label>
                        <p id="viewCompany" class="form-value">-</p>
                    </div>

                    <!-- Category field hidden - no longer used -->
                </div>
            </div>

            <!-- Payment & Tax Information Section -->
            <div class="modal-section">
                <h3 class="modal-section-title">Payment Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">NIB</label>
                        <p id="viewNib" class="form-value">-</p>
                    </div>

                    <div>
                        <label class="form-label">Customer Group</label>
                        <p id="viewCustomerGroup" class="form-value">-</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Default Bank Payment</label>
                        <p id="viewDefaultBankPayment" class="form-value">-</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Multi PIC</label>
                        <div id="viewMultiPic" class="text-sm space-y-1">-</div>
                    </div>
                    <div class="md:col-span-2 border-t pt-2 mt-2">
                         <h4 class="text-sm font-semibold mb-2">Tax Data List</h4>
                         <div id="viewTaxList" class="space-y-2">
                             <!-- Tax Items render here -->
                         </div>
                    </div>
                </div>
            </div>

            <!-- Location & Address Section -->
            <div class="modal-section">
                <h3 class="modal-section-title">Location & Address</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="form-label">Address</label>
                        <p id="viewAddress" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">Province</label>
                        <p id="viewProvince" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">City</label>
                        <p id="viewCity" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">District</label>
                        <p id="viewDistrict" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">Subdistrict</label>
                        <p id="viewSubdistrict" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">Postal Code</label>
                        <p id="viewPostalCode" class="form-value">-</p>
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="modal-section">
                <h3 class="modal-section-title">Contact Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Email</label>
                        <p id="viewEmail" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">Phone</label>
                        <p id="viewPhone" class="form-value">-</p>
                    </div>
                </div>
            </div>

            <!-- Assignment & Status Section -->
            <div class="modal-section">
                <h3 class="modal-section-title">Assignment & Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <label class="form-label">Status</label>
                        <p id="viewStatus" class="form-value">-</p>
                    </div>
                </div>
            </div>

            <!-- System Information Section -->
            <div class="modal-section">
                <h3 class="modal-section-title">System Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Created By</label>
                        <p id="viewCreatedBy" class="form-value">-</p>
                    </div>
                    <div>
                        <label class="form-label">Updated By</label>
                        <p id="viewUpdatedBy" class="form-value">-</p>
                    </div>
                </div>
            </div>
            
            <!-- Buildings Section -->
            <div class="modal-section">
                <h3 class="modal-section-title">Associated Buildings</h3>
                <div id="viewBuildings">
                    <!-- Buildings will be loaded here -->
                </div>
            </div>
            
            <!-- Customer Contacts Section (Pegawai/Staff) -->
            <div class="modal-section">
                <h3 class="modal-section-title">Customer Contacts (Pegawai/Staff)</h3>
                <div id="viewContacts">
                    <!-- Customer contacts will be loaded here -->
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="closeViewModal(); openEditModal(currentCustomerId);">
                <i class="fas fa-edit"></i> Edit Customer
            </button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModalOverlay" class="modal-overlay" onclick="closeEditModal()">
    <div class="modal-container large" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Edit Customer</h2>
            <button class="modal-close" onclick="closeEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
            <form id="editForm">
                <!-- Basic Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-building text-blue-500"></i>
                        Basic Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <!-- Row 1: Badan Hukum & Customer Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Badan Hukum *</label>
                            <select id="edit_company_type_input" name="company_type" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                <option value="">Pilih Badan Hukum</option>
                                <!-- Options loaded dynamically from /system/customer-types/api/list -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name *</label>
                            <input type="text" id="edit_name" name="name" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Classification</label>
                            <select id="edit_classification_id" name="classification_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select Classification</option>
                                @foreach($classificationOptions as $option)
                                    <option value="{{ $option->id }}">{{ $option->option_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Row 2: Customer Code & Label/Alias -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer Code</label>
                            <input type="text" id="edit_customer_code" name="customer_code" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" readonly style="background-color: #f9fafb;">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Label / Alias</label>
                            <input type="text" id="edit_label_alias" name="label_alias" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Row 3: Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select id="edit_customer_category_input" name="customer_category_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih Category</option>
                                <!-- Options loaded dynamically from /system/customer-types/api/list -->
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Payment & Tax Information Section (NEW) -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-file-invoice-dollar text-green-500"></i>
                        Payment Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer Group</label>
                            <input type="text" id="edit_customer_group" name="customer_group" maxlength="50" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., Sinarmas, Unilever, Indofood">
                            <small class="text-gray-500 text-xs mt-1 block">Grup perusahaan customer (max 50 karakter)</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Default Bank Payment</label>
                            <select id="edit_default_bank_payment_id" name="default_bank_payment_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Pilih Bank Payment</option>
                                @foreach($bankPayments as $bankPayment)
                                    <option value="{{ $bankPayment->id }}">{{ $bankPayment->bank->name ?? '' }} - {{ $bankPayment->account_name }} ({{ $bankPayment->account_number }})</option>
                                @endforeach
                            </select>
                            <small class="text-gray-500 text-xs mt-1 block">Bank default untuk pembayaran</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">NIB</label>
                            <input type="text" id="edit_nib" name="nib" maxlength="50" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nomor Induk Berusaha">
                            <small class="text-gray-500 text-xs mt-1 block">Max 50 characters</small>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Multi PIC (Person In Charge)</label>
                            <div class="flex gap-2 items-start">
                                <select id="edit_contact_ids" name="contact_ids[]" multiple class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" style="min-height: 120px;">
                                    @foreach($allContacts as $contact)
                                        <option value="{{ $contact->id }}">{{ $contact->name }} - {{ $contact->position ?? 'No Position' }} ({{ $contact->phone }})</option>
                                    @endforeach
                                </select>
                                <button type="button" onclick="openCreateContactModal(true)" class="px-3 bg-green-500 text-white rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors flex items-center justify-center border-2 border-green-600 shadow-md" title="Add New Contact" style="min-width: 44px; height: 44px; background-color: #22c55e !important;">
                                    <i class="fas fa-plus" style="font-size: 16px; color: white;"></i>
                                </button>
                            </div>
                            <small class="text-gray-500 text-xs mt-1 block">Tekan Ctrl+Click untuk pilih lebih dari satu. Klik <i class="fas fa-plus text-green-600"></i> untuk tambah contact baru.</small>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-address-book text-purple-500"></i>
                        Contact Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" id="edit_email" name="email" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="text" id="edit_phone" name="phone" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select id="edit_status" name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- Location Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-map-marker-alt text-red-500"></i>
                        Location & Address
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Province</label>
                            <select id="edit_province_id" name="province_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="loadCities(this.value)">
                                <option value="">Select Province</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                            <select id="edit_city_id" name="city_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="loadDistricts(this.value)">
                                <option value="">Select City</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">District</label>
                            <select id="edit_district_id" name="district_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="loadSubdistricts(this.value); clearPostalCode();">
                                <option value="">Select District</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Subdistrict</label>
                            <select id="edit_subdistrict_id" name="subdistrict_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="loadPostalCode(this.value)">
                                <option value="">Select Subdistrict</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                            <input type="text" id="edit_postal_code" name="postal_code" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50" readonly>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Address *</label>
                            <textarea id="edit_address" name="address" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="3" required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Settings Section -->
                <div class="form-section mb-0">
                    <h3 class="section-title">
                        <i class="fas fa-cog text-gray-500"></i>
                        Settings
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <input type="checkbox" id="edit_is_pkp" name="is_pkp" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3">
                            <label for="edit_is_pkp" class="text-sm font-medium text-gray-700 cursor-pointer">
                                PKP (Pengusaha Kena Pajak)
                            </label>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <input type="checkbox" id="edit_is_active" name="is_active" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3">
                            <label for="edit_is_active" class="text-sm font-medium text-gray-700 cursor-pointer">
                                Active Status
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitEditForm()">Update Customer</button>
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
        <h3 class="delete-modal-title">Deactivate Customer</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to deactivate this customer? They will be moved to Inactive status.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Yes, Deactivate</button>
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
        <p class="delete-modal-description" id="errorMessage">We couldn't hide the customer. Please try again.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-primary" onclick="closeErrorModal()">OK, I'll Try Again</button>
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
        <p class="delete-modal-description" id="successMessage">The customer has been successfully hidden.</p>
    </div>
</div>

<script>
// Global variables
let currentCustomerId = null;
let currentAction = null;

// Delete selected function - Bulk delete via checkbox
let selectedIdsToDelete = [];

function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Perhatian', 'Silakan pilih minimal satu customer yang ingin dinonaktifkan.');
        return;
    }
    
    // Store selected IDs
    selectedIdsToDelete = Array.from(checkboxes).map(cb => cb.value);
    
    // Update modal message
    const deleteMessage = document.getElementById('deleteMessage');
    if (deleteMessage) {
        deleteMessage.textContent = `Apakah Anda yakin ingin menonaktifkan ${selectedIdsToDelete.length} customer? Tindakan ini masih bisa dibatalkan nanti.`;
    }
    
    // Show delete confirmation modal
    document.getElementById('deleteModalOverlay').style.display = 'flex';
}

function confirmDelete() {
    console.log('🗑️ confirmDelete called');
    
    // Case 1: Bulk Delete (via Checkboxes)
    if (selectedIdsToDelete && selectedIdsToDelete.length > 0) {
        console.log('📝 Performing BULK delete for IDs:', selectedIdsToDelete);
        
        // Close delete modal
        if (typeof closeDeleteModal === 'function') closeDeleteModal();
        else document.getElementById('deleteModalOverlay').style.display = 'none';
        
        console.log('📤 Sending bulk DELETE request');
        
        // Send bulk delete request
        fetch('/company/customers/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ customer_ids: selectedIdsToDelete })
        })
        .then(response => response.json())
        .then(data => {
            console.log('📥 Response:', data);
            if (data.success || data.status === 'success') {
                // Show success modal
                const successMessage = document.getElementById('successMessage');
                if (successMessage) {
                    successMessage.textContent = `${selectedIdsToDelete.length} customer berhasil dinonaktifkan.`;
                }
                const successOverlay = document.getElementById('successModalOverlay');
                if (successOverlay) successOverlay.style.display = 'flex';
                else showSuccessDialog('Berhasil', `${selectedIdsToDelete.length} customer berhasil dinonaktifkan.`);
                
                // Clear selection
                selectedIdsToDelete = [];
                
                // Reload page after 1.5 seconds
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                // Show error modal
                const errorMessage = document.getElementById('errorMessage');
                if (errorMessage) {
                    errorMessage.textContent = data.message || 'Customer tidak berhasil dinonaktifkan. Silakan coba lagi.';
                }
                const errorOverlay = document.getElementById('errorModalOverlay');
                if (errorOverlay) errorOverlay.style.display = 'flex';
                else showErrorDialog('Gagal', data.message || 'Customer tidak berhasil dinonaktifkan.');
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
            // Show error modal
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage) {
                errorMessage.textContent = 'Terjadi kesalahan jaringan. Silakan periksa koneksi Anda lalu coba lagi.';
            }
            const errorOverlay = document.getElementById('errorModalOverlay');
            if (errorOverlay) errorOverlay.style.display = 'flex';
            else showErrorDialog('Gagal', 'Terjadi kesalahan jaringan. Silakan periksa koneksi Anda lalu coba lagi.');
        });
        
        return;
    }
    
    // Case 2: Single Delete (via currentCustomerId)
    if (typeof currentCustomerId !== 'undefined' && currentCustomerId) {
        console.log('📝 Performing SINGLE delete for ID:', currentCustomerId);
        
        // Close delete modal
        if (typeof closeDeleteModal === 'function') closeDeleteModal();
        else document.getElementById('deleteModalOverlay').style.display = 'none';
        
        console.log('📤 Sending DELETE request to:', `/company/customers/${currentCustomerId}`);
        
        // Send delete request
        fetch(`/company/customers/${currentCustomerId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const successMessage = document.getElementById('successMessage');
                if (successMessage) {
                    successMessage.textContent = data.message || 'Customer berhasil dinonaktifkan.';
                }
                const successOverlay = document.getElementById('successModalOverlay');
                if (successOverlay) successOverlay.style.display = 'flex';
                else showSuccessDialog('Berhasil', data.message || 'Customer berhasil dinonaktifkan.');
                
                // Reload page after 2 seconds
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                 const errorMessage = document.getElementById('errorMessage');
                if (errorMessage) {
                    errorMessage.textContent = data.message || 'Customer tidak berhasil dinonaktifkan.';
                }
                const errorOverlay = document.getElementById('errorModalOverlay');
                if (errorOverlay) errorOverlay.style.display = 'flex';
                else showErrorDialog('Gagal', data.message || 'Customer tidak berhasil dinonaktifkan.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
             const errorMessage = document.getElementById('errorMessage');
            if (errorMessage) {
                errorMessage.textContent = 'Terjadi kesalahan saat menonaktifkan customer.';
            }
            const errorOverlay = document.getElementById('errorModalOverlay');
            if (errorOverlay) errorOverlay.style.display = 'flex';
            else showErrorDialog('Gagal', 'Terjadi kesalahan saat menonaktifkan customer.');
        });
        
        return;
    }
    
    // No selection
    showWarningDialog('Perhatian', 'Tidak ada customer yang dipilih.');
    if (typeof closeDeleteModal === 'function') closeDeleteModal();
    else document.getElementById('deleteModalOverlay').style.display = 'none';
}

// Close modal functions
function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').style.display = 'none';
}

function closeSuccessModal() {
    document.getElementById('successModalOverlay').style.display = 'none';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').style.display = 'none';
}

// Modal functions
function openInfoModal() {
    // Show information modal about customer creation process
    document.getElementById('infoModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// ============================================
// LOAD CUSTOMER TYPES FROM SYSTEM API
// ============================================
function openCreateModal() {
    // Show create customer modal
    currentAction = 'create';
    
    // Load dropdown data
    loadCustomerTypes('create_company_type'); // Load Badan Hukum (Master Option 14)
    loadCustomerCategories('create_customer_category_id'); // Load Categories
    loadCustomerContactsForCreate(); // Load from Customer Contacts
    loadProvincesForCreate();
    
    document.getElementById('createModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function openViewModal(customerId) {
    currentAction = 'view';
    currentCustomerId = customerId;
    
    fetch(`/company/customers/${customerId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            populateViewModal(data.data);
            document.getElementById('viewModalOverlay').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to load customer data');
    });
}

function openEditModal(customerId) {
    currentAction = 'edit';
    currentCustomerId = customerId;
    
    // Load customer types first, then load customer data
    Promise.all([
        loadCustomerTypes('edit_company_type_input'), // Load Badan Hukum
        loadCustomerCategories('edit_customer_category_input'), // Load Categories
        fetch(`/company/customers/${customerId}/edit`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(response => response.json()),
        
        fetch('/api/customer-contacts', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(response => {
            if (!response.ok) {
                console.warn('Failed to load customer contacts, using empty list');
                return [];
            }
            return response.json();
        })
    ])
    .then(([_, __, customerData, contactsData]) => {
        if (customerData.status === 'success') {
            const contacts = Array.isArray(contactsData) ? contactsData : (contactsData.data || []);
            populateEditModal(customerData.data, contacts, customerData.provinces, customerData.cities, customerData.districts, customerData.subdistricts);
            
            // Set classification
            if (customerData.data.classification_id) {
                document.getElementById('edit_classification_id').value = customerData.data.classification_id;
            } else {
                document.getElementById('edit_classification_id').value = '';
            }
            
            document.getElementById('editModalOverlay').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to load customer data');
    });
}

function openDeleteModal(customerId) {
    currentCustomerId = customerId;
    document.getElementById('deleteModalOverlay').style.display = 'flex';
}

// Close modal functions
function closeInfoModal() {
    document.getElementById('infoModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function closeCreateModal() {
    document.getElementById('createModalOverlay').classList.remove('show');
    document.getElementById('createForm').reset();
    document.body.style.overflow = 'auto';
}

function closeViewModal() {
    document.getElementById('viewModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function populateViewModal(customer) {
    // Populate customer data in view modal
    document.getElementById('viewCustomerCode').textContent = customer.customer_code || '-';
    document.getElementById('viewName').textContent = customer.name || '-';
    document.getElementById('viewClassification').textContent = customer.classification ? customer.classification.option_name : '-';
    document.getElementById('viewLabelAlias').textContent = customer.label_alias || '-';
    document.getElementById('viewCategory').textContent = customer.customer_type ? customer.customer_type.name : '-';
    document.getElementById('viewCompany').textContent = customer.company_type ? customer.company_type.toUpperCase() : '-';
    document.getElementById('viewCompany').textContent = customer.company_type ? customer.company_type.toUpperCase() : '-';
    
    // NIB (moved to Payment section)
    document.getElementById('viewNib').textContent = customer.nib || '-';
    
    // Payment & Tax fields
    document.getElementById('viewPpnCode').textContent = customer.ppn_code || '-';
    document.getElementById('viewCustomerGroup').textContent = customer.customer_group || '-';
    
    // Default Bank Payment
    if (customer.default_bank_payment && customer.default_bank_payment.bank) {
        document.getElementById('viewDefaultBankPayment').textContent = 
            `${customer.default_bank_payment.bank.name} - ${customer.default_bank_payment.account_name} (${customer.default_bank_payment.account_number})`;
    } else {
        document.getElementById('viewDefaultBankPayment').textContent = '-';
    }
    
    // Multi PIC
    const multiPicContainer = document.getElementById('viewMultiPic');
    if (customer.contacts && customer.contacts.length > 0) {
        multiPicContainer.innerHTML = customer.contacts.map(contact => 
            `<div class="flex items-center"><i class="fas fa-user-circle mr-2 text-gray-500"></i>${contact.salutation ? contact.salutation + ' ' : ''}${contact.name} (${contact.phone || '-'})</div>`
        ).join('');
    } else {
        multiPicContainer.textContent = '-';
    }

    // Populate Tax List
    const taxListContainer = document.getElementById('viewTaxList');
    if (customer.customer_tax_settings && customer.customer_tax_settings.length > 0) {
        // Create table structure with forced spacing
        const tableHtml = `
            <div class="overflow-x-auto rounded-lg border border-gray-200 mt-2">
                <table class="min-w-full divide-y divide-gray-200" style="border-collapse: separate; border-spacing: 0;">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" style="min-width: 120px; padding-left: 24px; padding-right: 24px;">Tax Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" style="min-width: 150px; padding-left: 24px; padding-right: 24px;">Tax Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" style="min-width: 160px; padding-left: 24px; padding-right: 24px;">Tax Number</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider" style="min-width: 200px; padding-left: 24px; padding-right: 24px;">Address</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${customer.customer_tax_settings.map(tax => `
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium" style="padding-left: 24px; padding-right: 24px;">
                                    ${tax.tax_type ? `<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">${tax.tax_type.toUpperCase()}</span>` : '-'}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" style="padding-left: 24px; padding-right: 24px;">
                                    ${tax.tax_name || '-'}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-700" style="padding-left: 24px; padding-right: 24px;">
                                    ${tax.tax_number || '-'}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600" style="padding-left: 24px; padding-right: 24px;">
                                    <div class="truncate" style="max-width: 250px;" title="${tax.tax_address || ''}">
                                        ${tax.tax_address || '-'}
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
        taxListContainer.innerHTML = tableHtml;
    } else {
        taxListContainer.innerHTML = '<div class="text-sm text-gray-400 italic p-4 bg-gray-50 rounded border border-gray-100 text-center">No active tax records found.</div>';
    }
    
    // Location & Address fields
    console.log('🌍 Debug location data:', {
        province: customer.province,
        city: customer.city,
        district: customer.district,
        subdistrict: customer.subdistrict,
        postal_code: customer.postal_code
    });
    
    document.getElementById('viewAddress').textContent = customer.address || '-';
    document.getElementById('viewProvince').textContent = customer.province ? customer.province.name : '-';
    document.getElementById('viewCity').textContent = customer.city ? customer.city.name : '-';
    document.getElementById('viewDistrict').textContent = customer.district ? customer.district.name : '-';
    document.getElementById('viewSubdistrict').textContent = customer.subdistrict ? customer.subdistrict.name : '-';
    document.getElementById('viewPostalCode').textContent = customer.postal_code || '-';
    
    // Contact information
    document.getElementById('viewEmail').textContent = customer.email || '-';
    document.getElementById('viewPhone').textContent = customer.phone || '-';
    document.getElementById('viewPhone').textContent = customer.phone || '-';
    // Removed Assigned To field
    
    // Populate status with badge
    const statusElement = document.getElementById('viewStatus');
    if (customer.status) {
        const status = customer.status.charAt(0).toUpperCase() + customer.status.slice(1);
        const statusClass = customer.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        statusElement.innerHTML = `<span class="px-2 py-1 text-xs rounded-full ${statusClass}">${status}</span>`;
    } else {
        statusElement.textContent = '-';
    }
    
    document.getElementById('viewCreatedBy').textContent = customer.created_by ? customer.created_by.name : '-';
    document.getElementById('viewUpdatedBy').textContent = customer.updated_by ? customer.updated_by.name : '-';
    
    // Populate buildings data
    populateBuildingsSection(customer.building_customers || []);
    
    // Populate customer contacts data (Pegawai/Staff)
    populateContactsSection(customer.customer_contacts || []);
}

function populateBuildingsSection(buildings) {
    const buildingsContainer = document.getElementById('viewBuildings');
    
    if (!buildings || buildings.length === 0) {
        buildingsContainer.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <p>No buildings found for this customer</p>
            </div>
        `;
        return;
    }
    
    buildingsContainer.innerHTML = buildings.map((building, index) => {
        // Build location parts (province, city, district, subdistrict)
        const locationParts = [];
        if (building.province && building.province.name) locationParts.push(building.province.name);
        if (building.city && building.city.name) locationParts.push(building.city.name);
        if (building.district && building.district.name) locationParts.push(building.district.name);
        if (building.subdistrict && building.subdistrict.name) locationParts.push(building.subdistrict.name);
        const locationText = locationParts.length > 0 ? locationParts.join(', ') : '-';
        
        // Build address parts
        const addressParts = [];
        if (building.alamat_1 || building.address) {
            addressParts.push(building.alamat_1 || building.address);
        }
        if (building.alamat_2) {
            addressParts.push(building.alamat_2);
        }
        const addressText = addressParts.length > 0 ? addressParts.join(', ') : 'No address provided';
        
        // Postal code
        const postalCode = building.kode_pos || building.postal_code || '-';
        
        // Contact info
        const phone1 = building.phone_1 || '';
        const phone2 = building.phone_2 || '';
        const email = building.email || '';
        
        // Location detail (if exists)
        const locationDetail = building.location_detail || '';
        
        return `
        <div class="border border-gray-200 rounded-lg p-4 mb-3 bg-white">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h4 class="font-semibold text-gray-800 text-lg">${building.nama_gedung || building.name || 'Unnamed Building'}</h4>
                    <p class="text-sm text-gray-500">Building #${index + 1}</p>
                </div>
                <span class="px-2 py-1 text-xs rounded-full ${building.status_update ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                    ${building.status_update ? 'Active' : 'Inactive'}
                </span>
            </div>
            
            <div class="space-y-2 text-sm">
                ${addressText !== 'No address provided' ? `
                <div class="text-gray-700">
                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                    <strong>Alamat:</strong> ${addressText}
                </div>
                ` : ''}
                
                ${locationText !== '-' ? `
                <div class="text-gray-700">
                    <i class="fas fa-map text-blue-500 mr-2"></i>
                    <strong>Lokasi:</strong> ${locationText}
                </div>
                ` : ''}
                
                ${postalCode !== '-' ? `
                <div class="text-gray-700">
                    <i class="fas fa-mail-bulk text-purple-500 mr-2"></i>
                    <strong>Kode Pos:</strong> ${postalCode}
                </div>
                ` : ''}
                
                ${locationDetail ? `
                <div class="text-gray-700">
                    <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                    <strong>Lokasi Persis:</strong> ${locationDetail}
                </div>
                ` : ''}
                
                ${phone1 || phone2 || email ? `
                <div class="border-t border-gray-200 pt-2 mt-2">
                    <div class="text-gray-700 font-semibold mb-1">Kontak:</div>
                    ${phone1 ? `
                    <div class="text-gray-600 ml-4">
                        <i class="fas fa-phone text-green-500 mr-2"></i>
                        <strong>Telp 1:</strong> ${phone1}
                    </div>
                    ` : ''}
                    ${phone2 ? `
                    <div class="text-gray-600 ml-4">
                        <i class="fas fa-phone text-green-500 mr-2"></i>
                        <strong>Telp 2:</strong> ${phone2}
                    </div>
                    ` : ''}
                    ${email ? `
                    <div class="text-gray-600 ml-4">
                        <i class="fas fa-envelope text-blue-500 mr-2"></i>
                        <strong>Email:</strong> ${email}
                    </div>
                    ` : ''}
                </div>
                ` : ''}
                
                ${building.total_floors || building.total_area ? `
                <div class="flex flex-wrap gap-2 mt-2">
                    ${building.total_floors ? `<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">${building.total_floors} Lantai</span>` : ''}
                    ${building.total_area ? `<span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">${building.total_area} m²</span>` : ''}
                </div>
                ` : ''}
            </div>
        </div>
    `;
    }).join('');
}

function populateContactsSection(contacts) {
    const contactsContainer = document.getElementById('viewContacts');
    
    if (!contacts || contacts.length === 0) {
        contactsContainer.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <p>No contacts found for this customer</p>
                <small class="text-xs">Customer contacts are the employees/staff of the company</small>
            </div>
        `;
        return;
    }
    
    contactsContainer.innerHTML = contacts.map((contact, index) => `
        <div class="border border-gray-200 rounded-lg p-4 mb-3 bg-white">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h4 class="font-semibold text-gray-800">
                        ${contact.salutation ? contact.salutation + ' ' : ''}${contact.name || 'Unnamed Contact'}
                    </h4>
                    <p class="text-sm text-gray-500">${contact.position || 'No position specified'}</p>
                </div>
                <span class="px-2 py-1 text-xs rounded-full ${contact.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                    ${contact.is_active ? 'Active' : 'Inactive'}
                </span>
            </div>
            
            <div class="space-y-2">
                <div class="text-sm text-gray-600">
                    <i class="fas fa-envelope mr-2"></i>
                    <strong>Email:</strong> ${contact.email || '-'}
                </div>
                <div class="text-sm text-gray-600">
                    <i class="fas fa-phone mr-2"></i>
                    <strong>Phone:</strong> ${contact.phone || '-'}
                </div>
            </div>
        </div>
    `).join('');
}

function populateEditModal(customer, contacts, provinces, cities, districts, subdistricts) {
    // Populate basic fields
    document.getElementById('edit_customer_code').value = customer.customer_code || '';
    document.getElementById('edit_name').value = customer.name || '';
    document.getElementById('edit_label_alias').value = customer.label_alias || '';
    document.getElementById('edit_email').value = customer.email || '';
    document.getElementById('edit_phone').value = customer.phone || '';
    document.getElementById('edit_address').value = customer.address || '';
    
    // Populate NIB (moved to Payment section)
    document.getElementById('edit_nib').value = customer.nib || '';
    
    
    // Populate Payment & Tax Information
    console.log('💰 Populating Payment & Tax Info:', {
        ppn_code: customer.ppn_code,
        group: customer.customer_group,
        bank_id: customer.default_bank_payment_id,
        contacts: customer.contacts
    });

    // PPN TYPE
    const ppnCodeSelect = document.getElementById('edit_ppn_code');
    if (ppnCodeSelect) {
        // Try native
        ppnCodeSelect.value = customer.ppn_code || '';
        // Try jQuery explicitly for Select2
        if (typeof jQuery !== 'undefined') {
            jQuery(ppnCodeSelect).val(customer.ppn_code || '').trigger('change');
        }
    }

    document.getElementById('edit_customer_group').value = customer.customer_group || '';
    
    // BANK PAYMENT
    const bankPaymentSelect = document.getElementById('edit_default_bank_payment_id');
    if (bankPaymentSelect) {
        // Try native
        bankPaymentSelect.value = customer.default_bank_payment_id || '';
        // Try jQuery explicitly for Select2
        if (typeof jQuery !== 'undefined') {
             jQuery(bankPaymentSelect).val(customer.default_bank_payment_id || '').trigger('change');
        }
    }
    
    // Populate Multi PIC
    const contactEditSelect = document.getElementById('edit_contact_ids');
    if (contactEditSelect) {
        const contactIds = (customer.contacts && customer.contacts.length > 0) 
            ? customer.contacts.map(c => String(c.id)) 
            : [];
            
        console.log('👥 Setting contacts:', contactIds);

        // Native multiselect population
        Array.from(contactEditSelect.options).forEach(opt => {
            opt.selected = contactIds.includes(String(opt.value));
        });
        
        // jQuery/Select2 update
        if (typeof jQuery !== 'undefined') {
            jQuery(contactEditSelect).val(contactIds).trigger('change');
        }
    }

    console.log('🔍 DEBUG populateEditModal:', {
        company_type: customer.company_type,
        category_id: customer.customer_category_id,
        customer_id: customer.id
    });

    // Populate company type
    const companyTypeField = document.getElementById('edit_company_type_input');
    if (companyTypeField) {
        // Visual Debug: color validation
        companyTypeField.style.border = '2px solid red'; // Start red
        if (customer.company_type) {
            const companyTypeValue = customer.company_type.toLowerCase().trim();
            console.log('🎯 Attempting to set Company Type to:', companyTypeValue);
            
            // Check available options
            const options = Array.from(companyTypeField.options).map(o => o.value);
            console.log('📋 Available Company Types:', options);
            
            // Try to set value
            companyTypeField.value = companyTypeValue;
            
            // Verify
            if (companyTypeField.value !== companyTypeValue) {
                console.warn('⚠️ Failed to set Company Type via .value. Trying loop...');
                
                // Fallback: Loop and set selected=true (most robust)
                let found = false;
                for (let i = 0; i < companyTypeField.options.length; i++) {
                    // Loose comparison for flexibility
                    if (companyTypeField.options[i].value == companyTypeValue) {
                        companyTypeField.options[i].selected = true;
                        found = true;
                        break;
                    }
                }
                
                if (!found) {
                     console.error('❌ Could not find option for:', companyTypeValue);
                } else {
                     console.log('✅ Company Type set via selected=true');
                     companyTypeField.style.border = '2px solid green'; // Confirm Success
                     companyTypeField.style.backgroundColor = '#f0fff4';
                     
                     // FORCE UI REFRESH (Standard for Select2/Choices/etc)
                     triggerChange(companyTypeField);
                }
            } else {
                 console.log('✅ Company Type set string-match successfully');
                 companyTypeField.style.border = '2px solid green';
                 companyTypeField.style.backgroundColor = '#f0fff4';
                 
                 // FORCE UI REFRESH
                 triggerChange(companyTypeField);
            }

            // check persistence
            setTimeout(() => {
                console.log('👀 Persistence Check (500ms) - Company Type:', companyTypeField.value);
            }, 500);
        } else {
            console.warn('⚠️ customer.company_type is null/empty');
            companyTypeField.value = "";
        }
    } else {
        console.error('❌ edit_company_type_input element not found');
    }
    
    // Populate customer category
    const categorySelect = document.getElementById('edit_customer_category_input');
    if (categorySelect) {
        if (customer.customer_category_id) {
             console.log('🎯 Attempting to set Category ID to:', customer.customer_category_id);
             
             // Check available options
             const catOptions = Array.from(categorySelect.options).map(o => o.value);
             console.log('📋 Available Categories:', catOptions);
             
             categorySelect.value = customer.customer_category_id;
             
             if (categorySelect.value != customer.customer_category_id) {
                 console.warn('⚠️ Category ID mismatch or not found in options');
             } else {
                 console.log('✅ Category set successfully');
                 categorySelect.style.border = '2px solid green';
                 categorySelect.style.backgroundColor = '#f0fff4';
                 
                 // FORCE UI REFRESH
                 triggerChange(categorySelect);
             }
        } else {
             console.warn('⚠️ customer.customer_category_id is null/empty');
             categorySelect.value = "";
        }
    } else {
         console.error('❌ edit_customer_category_input element not found');
    }
    
    // Populate status
    document.getElementById('edit_status').value = customer.status || '';
    
    // Populate checkboxes
    document.getElementById('edit_is_pkp').checked = customer.is_pkp == 1;
    document.getElementById('edit_is_active').checked = customer.is_active == 1;
    
    // Populate assigned contact (customer contact person in charge)
    // Populate assigned contact removed (replaced by Multi PIC)
    
    // assigned_to logic removed (replaced by Multi PIC)
    
    // Populate provinces
    const provinceSelect = document.getElementById('edit_province_id');
    provinceSelect.innerHTML = '<option value="">Select Province</option>';
    if (provinces) {
        provinces.forEach(province => {
            const option = document.createElement('option');
            option.value = province.id;
            option.textContent = province.name;
            if (customer.province_id == province.id) {
                option.selected = true;
            }
            provinceSelect.appendChild(option);
        });
    }
    
    // Populate cities
    const citySelect = document.getElementById('edit_city_id');
    citySelect.innerHTML = '<option value="">Select City</option>';
    console.log('🏙️ Cities data:', {
        count: cities ? cities.length : 0,
        customer_city_id: customer.city_id,
        cities_list: cities
    });
    if (cities && cities.length > 0) {
        cities.forEach(city => {
            const option = document.createElement('option');
            option.value = city.id;
            option.textContent = city.name;
            if (String(customer.city_id) === String(city.id)) {
                option.selected = true;
                console.log(`✅ Selected city: ${city.name} (ID: ${city.id})`);
            }
            citySelect.appendChild(option);
        });
    } else {
        console.warn('⚠️ No cities available for province:', customer.province_id);
    }
    
    // Populate districts
    const districtSelect = document.getElementById('edit_district_id');
    districtSelect.innerHTML = '<option value="">Select District</option>';
    console.log('🏘️ Districts data:', {
        count: districts ? districts.length : 0,
        customer_district_id: customer.district_id,
        districts_list: districts
    });
    if (districts && districts.length > 0) {
        districts.forEach(district => {
            const option = document.createElement('option');
            option.value = district.id;
            option.textContent = district.name;
            if (String(customer.district_id) === String(district.id)) {
                option.selected = true;
                console.log(`✅ Selected district: ${district.name} (ID: ${district.id})`);
            }
            districtSelect.appendChild(option);
        });
    } else {
        console.warn('⚠️ No districts available for city:', customer.city_id);
    }
    
    // Populate subdistricts
    const subdistrictSelect = document.getElementById('edit_subdistrict_id');
    subdistrictSelect.innerHTML = '<option value="">Select Subdistrict</option>';
    if (subdistricts) {
        subdistricts.forEach(subdistrict => {
            const option = document.createElement('option');
            option.value = subdistrict.id;
            option.textContent = subdistrict.name;
            if (customer.subdistrict_id == subdistrict.id) {
                option.selected = true;
            }
            subdistrictSelect.appendChild(option);
        });
    }
    
    // Populate postal code
    document.getElementById('edit_postal_code').value = customer.postal_code || '';
}

function closeEditModal() {
    document.getElementById('editModalOverlay').classList.remove('show');
    document.getElementById('editForm').reset();
    document.body.style.overflow = 'auto';
}

// Helper to trigger events for 3rd party libraries (Select2, etc)
function triggerChange(element) {
    // 1. Native Event
    element.dispatchEvent(new Event('change', { bubbles: true }));
    element.dispatchEvent(new Event('input', { bubbles: true }));
    
    // 2. jQuery Event (if available) - Critical for Select2
    if (typeof jQuery !== 'undefined') {
        jQuery(element).trigger('change');
        jQuery(element).trigger('change.select2'); // Specific for Select2
    }
}



function retryDelete() {
    closeErrorModal();
    // DO NOT retry automatically - this is dangerous
    // User should manually retry their action
    console.log('⚠️ Error occurred. Please retry your action manually.');
}

// Data loading functions

// Load Badan Hukum (Company Types) from Master Option ID 14
function loadCustomerTypes(selectId) {
    return fetch('/other/master-options/14', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to load badan hukum options');
        }
        return response.json();
    })
    .then(data => {
        const select = document.getElementById(selectId);
        if (!select) {
            console.error(`❌ Select element with ID '${selectId}' not found`);
            return;
        }
        
        select.innerHTML = '<option value="">Pilih Badan Hukum</option>';
        
        // Get option_details from the master option (MasterOption ID 14)
        const customerTypes = data.optionDetails || data.option_details || [];
        
        if (customerTypes && customerTypes.length > 0) {
            customerTypes.forEach(type => {
                // Only show active options
                if (type.is_active !== false) {
                    const option = document.createElement('option');
                    // Use the option_name as both value and display
                    const optionName = type.option_name || type.name || type;
                    option.value = optionName.toLowerCase();
                    option.textContent = optionName.toUpperCase();
                    select.appendChild(option);
                }
            });
            console.log(`✅ Loaded ${customerTypes.length} badan hukum options`);
        } else {
            console.log('⚠️ No badan hukum options available');
        }
    })
    .catch(error => {
        console.error('❌ Error loading badan hukum options:', error);
        // Fallback: leave empty with placeholder
    });
}

// Load Customer Categories from CustomerType model
function loadCustomerCategories(selectId) {
    return fetch('/system/customer-types/api/list', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to load customer categories');
        }
        return response.json();
    })
    .then(data => {
        const select = document.getElementById(selectId);
        if (!select) {
            console.error(`❌ Select element with ID '${selectId}' not found`);
            return;
        }
        
        select.innerHTML = '<option value="">Pilih Category</option>';
        
        // Handle both array and object response
        const categories = Array.isArray(data) ? data : (data.data || []);
        
        if (categories && categories.length > 0) {
            categories.forEach(category => {
                // Only show active categories
                if (category.is_active !== false) {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    select.appendChild(option);
                }
            });
            console.log(`✅ Loaded ${categories.length} customer categories`);
        } else {
            console.log('⚠️ No customer categories available');
        }
    })
    .catch(error => {
        console.error('❌ Error loading customer categories:', error);
    });
}

function loadUsers() {
    fetch('/api/users', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        const select = document.getElementById('assigned_to');
        select.innerHTML = '<option value="">Select User</option>';
        data.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.name;
            select.appendChild(option);
        });
    });
}

function loadProvinces() {
    fetch('/api/v1/location/provinces')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('edit_province_id');
            if (select) {
                select.innerHTML = '<option value="">Select Province</option>';
                const provinces = Array.isArray(data) ? data : (data.data || []);
                provinces.forEach(province => {
                    const option = document.createElement('option');
                    option.value = province.id;
                    option.textContent = province.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading provinces:', error);
        });
}

function loadCities(provinceId) {
    if (!provinceId) return;
    
    fetch(`/api/v1/location/cities?province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            const citySelect = document.getElementById('edit_city_id');
            if (citySelect) {
                citySelect.innerHTML = '<option value="">Select City</option>';
                const cities = Array.isArray(data) ? data : (data.data || []);
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    citySelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading cities:', error);
        });
}

function loadDistricts(cityId) {
    if (!cityId) return;
    
    fetch(`/api/v1/location/districts?city_id=${cityId}`)
        .then(response => response.json())
        .then(data => {
            const districtSelect = document.getElementById('edit_district_id');
            if (districtSelect) {
                districtSelect.innerHTML = '<option value="">Select District</option>';
                const districts = Array.isArray(data) ? data : (data.data || []);
                districts.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.id;
                    option.textContent = district.name;
                    districtSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading districts:', error);
        });
}

function loadSubdistricts(districtId) {
    if (!districtId) return;
    
    fetch(`/api/v1/location/subdistricts?district_id=${districtId}`)
        .then(response => response.json())
        .then(data => {
            const subdistrictSelect = document.getElementById('edit_subdistrict_id');
            if (subdistrictSelect) {
                subdistrictSelect.innerHTML = '<option value="">Select Subdistrict</option>';
                const subdistricts = Array.isArray(data) ? data : (data.data || []);
                subdistricts.forEach(subdistrict => {
                    const option = document.createElement('option');
                    option.value = subdistrict.id;
                    option.textContent = subdistrict.name;
                    if (subdistrict.postal_code) {
                        option.setAttribute('data-postal-code', subdistrict.postal_code);
                    }
                    subdistrictSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading subdistricts:', error);
        });
}

function loadPostalCode(subdistrictId) {
    if (!subdistrictId) return;
    
    // Get postal code from the selected option
    const subdistrictSelect = document.getElementById('edit_subdistrict_id');
    if (subdistrictSelect) {
        const selectedOption = subdistrictSelect.options[subdistrictSelect.selectedIndex];
        const postalCode = selectedOption.getAttribute('data-postal-code');
        
        const postalCodeInput = document.getElementById('edit_postal_code');
        if (postalCodeInput && postalCode) {
            postalCodeInput.value = postalCode;
        }
    }
}

function clearPostalCode() {
    const postalCodeInput = document.getElementById('edit_postal_code');
    if (postalCodeInput) {
        postalCodeInput.value = '';
    }
}

// Data loading functions for CREATE modal
// Data loading functions for CREATE modal
function loadCustomerContactsForCreate() {
    return fetch('/company/customer-contacts/get-customer-contacts')
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to load customer contacts');
        }
        return response.json();
    })
    .then(data => {
        const select = document.getElementById('create_assigned_to');
        if (select) {
            select.innerHTML = '<option value="">Select Contact</option>';
            
            // Handle both array and paginated response
            const contacts = Array.isArray(data) ? data : (data.data || []);
            
            if (contacts && contacts.length > 0) {
                contacts.forEach(contact => {
                    const option = document.createElement('option');
                    option.value = contact.id;
                    // Show contact name with customer name if available
                    const displayText = contact.customer ? 
                        `${contact.name} (${contact.customer.name})` : 
                        contact.name;
                    option.textContent = displayText;
                    select.appendChild(option);
                });
                console.log(`✅ Loaded ${contacts.length} customer contacts`);
            } else {
                console.log('⚠️ No customer contacts available');
            }
        }
    })
    .catch(error => {
        console.error('❌ Error loading customer contacts:', error);
    });
}

function loadProvincesForCreate() {
    fetch('/api/v1/location/provinces')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('create_province_id');
            if (select) {
                select.innerHTML = '<option value="">Select Province</option>';
                const provinces = Array.isArray(data) ? data : (data.data || []);
                provinces.forEach(province => {
                    const option = document.createElement('option');
                    option.value = province.id;
                    option.textContent = province.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading provinces:', error);
        });
}

function loadCitiesForCreate(provinceId) {
    if (!provinceId) return;
    
    fetch(`/api/v1/location/cities?province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            const citySelect = document.getElementById('create_city_id');
            if (citySelect) {
                citySelect.innerHTML = '<option value="">Select City</option>';
                const cities = Array.isArray(data) ? data : (data.data || []);
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    citySelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading cities:', error);
        });
}

function loadDistrictsForCreate(cityId) {
    if (!cityId) return;
    
    fetch(`/api/v1/location/districts?city_id=${cityId}`)
        .then(response => response.json())
        .then(data => {
            const districtSelect = document.getElementById('create_district_id');
            if (districtSelect) {
                districtSelect.innerHTML = '<option value="">Select District</option>';
                const districts = Array.isArray(data) ? data : (data.data || []);
                districts.forEach(district => {
                    const option = document.createElement('option');
                    option.value = district.id;
                    option.textContent = district.name;
                    districtSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading districts:', error);
        });
}

function loadSubdistrictsForCreate(districtId) {
    if (!districtId) return;
    
    fetch(`/api/v1/location/subdistricts?district_id=${districtId}`)
        .then(response => response.json())
        .then(data => {
            const subdistrictSelect = document.getElementById('create_subdistrict_id');
            if (subdistrictSelect) {
                subdistrictSelect.innerHTML = '<option value="">Select Subdistrict</option>';
                const subdistricts = Array.isArray(data) ? data : (data.data || []);
                subdistricts.forEach(subdistrict => {
                    const option = document.createElement('option');
                    option.value = subdistrict.id;
                    option.textContent = subdistrict.name;
                    // Store postal_code in data attribute for auto-fill
                    if (subdistrict.postal_code) {
                        option.setAttribute('data-postal-code', subdistrict.postal_code);
                    }
                    subdistrictSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading subdistricts:', error);
        });
}

function loadPostalCodeForCreate(subdistrictId) {
    if (!subdistrictId) return;
    
    // Get postal code from the selected option's data attribute
    const subdistrictSelect = document.getElementById('create_subdistrict_id');
    if (subdistrictSelect) {
        const selectedOption = subdistrictSelect.options[subdistrictSelect.selectedIndex];
        const postalCode = selectedOption.getAttribute('data-postal-code');
        
        const postalCodeInput = document.getElementById('create_postal_code');
        if (postalCodeInput && postalCode) {
            postalCodeInput.value = postalCode;
            console.log(`✅ Postal code auto-filled: ${postalCode}`);
        } else if (postalCodeInput) {
            postalCodeInput.value = '';
            console.log('⚠️ No postal code available for this subdistrict');
        }
    }
}

function clearPostalCodeForCreate() {
    const postalCodeInput = document.getElementById('create_postal_code');
    if (postalCodeInput) {
        postalCodeInput.value = '';
    }
}

// Inline Contact Creation Functions
function openCreateContactModal(isEditMode = false) {
    // Load salutations and positions
    loadSalutations();
    loadPositions();
    
    document.getElementById('createContactForm').reset();
    
    if (isEditMode && currentCustomerId) {
        // Edit Mode: Pre-fill customer info
        const customerName = document.getElementById('edit_name').value;
        document.getElementById('contact_customer_name').value = customerName;
        document.getElementById('contact_customer_id').value = currentCustomerId;
        console.log('Open Contact Modal in EDIT mode for customer:', currentCustomerId);
    } else {
        // Create Mode
        const customerName = document.getElementById('create_name').value;
        if (customerName) {
            document.getElementById('contact_customer_name').value = customerName;
        } else {
            document.getElementById('contact_customer_name').value = 'New Customer (will be created)';
        }
        document.getElementById('contact_customer_id').value = '';
        console.log('Open Contact Modal in CREATE mode');
    }
    
    // Show modal
    document.getElementById('createContactModalOverlay').classList.add('show');
}

function closeCreateContactModal() {
    document.getElementById('createContactModalOverlay').classList.remove('show');
}

function loadSalutations() {
    // Fetch salutations from master option ID 13
    fetch('/other/master-options/13', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        const select = document.getElementById('contact_salutation_id');
        select.innerHTML = '<option value="">Select Salutation</option>';
        
        // Get option_details from the master option
        const salutations = data.optionDetails || data.option_details || [];
        
        salutations.forEach(salutation => {
            // Only show active salutations
            if (salutation.is_active !== false) {
                const option = document.createElement('option');
                option.value = salutation.option_name || salutation.name || salutation.title;
                option.textContent = salutation.option_name || salutation.name || salutation.title;
                select.appendChild(option);
            }
        });
        
        console.log(`✅ Loaded ${salutations.length} salutations`);
    })
    .catch(error => {
        console.error('❌ Error loading salutations:', error);
    });
}

function loadPositions() {
    // Fetch positions from master option ID 1
    fetch('/other/master-options/1', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        const select = document.getElementById('contact_position_id');
        select.innerHTML = '<option value="">Select Position</option>';
        
        // Get option_details from the master option
        const positions = data.optionDetails || data.option_details || [];
        
        positions.forEach(position => {
            // Only show active positions
            if (position.is_active !== false) {
                const option = document.createElement('option');
                option.value = position.option_name || position.name || position.title;
                option.textContent = position.option_name || position.name || position.title;
                select.appendChild(option);
            }
        });
        
        console.log(`✅ Loaded ${positions.length} positions`);
    })
    .catch(error => {
        console.error('❌ Error loading positions:', error);
    });
}

function submitCreateContactForm() {
    const form = document.getElementById('createContactForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    
    // For new customer creation, customer_id will be null
    // Contact will be created without customer assignment first
    const customerId = document.getElementById('contact_customer_id').value;
    if (!customerId || customerId === '') {
        formData.delete('customer_id');
        console.log('ℹ️ Creating contact without customer assignment (will be available for all customers)');
    }
    
    fetch('/company/customer-contacts', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success || data.status === 'success') {
            showSuccess(data.message || 'Contact created successfully!');
            closeCreateContactModal();
            
            // Reload customer contacts dropdown and THEN select the new contact
            // Reload customer contacts dropdowns
            loadCustomerContactsForCreate().then(() => {
                // Update CREATE modal dropdown if it exists
                const createSelect = document.getElementById('create_contact_ids'); // Multi PIC
                const createSelectAssigned = document.getElementById('create_assigned_to'); // Legacy?
                
                // Add new option to CREATE Multi PIC
                if (createSelect && data.data) {
                    const newOption = new Option(`${data.data.name} - ${data.data.position || ''} (${data.data.phone})`, data.data.id, true, true);
                    createSelect.appendChild(newOption);
                    triggerChange(createSelect);
                }
                
                // Update EDIT modal dropdown if it exists
                const editSelect = document.getElementById('edit_contact_ids');
                if (editSelect && data.data) {
                    const newOption = new Option(`${data.data.name} - ${data.data.position || ''} (${data.data.phone})`, data.data.id, true, true);
                    editSelect.appendChild(newOption);
                    triggerChange(editSelect);
                }
                
                console.log(`✅ Auto-selected new contact: ${data.data.name} (ID: ${data.data.id})`);
            });
        } else {
            showError(data.message || 'Failed to create contact');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while creating contact');
    });
}

// Form submission functions
function submitCreateForm() {
    const form = document.getElementById('createForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    
    fetch('/company/customers', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
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
                        showError(errorMessage);
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
                    showError(msg);
                }
                throw new Error(msg);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            showSuccess(data.message || 'Customer berhasil dibuat.');
            closeCreateModal();
            window.location.reload(); // Reload the page to show new customer
        } else {
            const msg = data.message || 'Customer tidak berhasil dibuat.';
            if (typeof Swal !== 'undefined') {
                 Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: msg,
                    confirmButtonColor: '#214589',
                });
            } else {
                showError(msg);
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
                    text: error.message || 'Terjadi kesalahan.',
                    confirmButtonColor: '#214589',
                });
            } else {
                showError('Terjadi kesalahan saat membuat customer: ' + error.message);
            }
        }
    });
}

function submitEditForm() {
    console.log('🚀 submitEditForm called');
    console.log('📝 Current Customer ID:', currentCustomerId);
    
    const form = document.getElementById('editForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    
    // Convert FormData to JSON for PUT request
    const jsonData = {};
    for (let [key, value] of formData.entries()) {
        jsonData[key] = value;
    }
    
    // Handle checkboxes explicitly (unchecked checkboxes don't submit)
    jsonData.is_pkp = document.getElementById('edit_is_pkp').checked ? 1 : 0;
    jsonData.is_active = document.getElementById('edit_is_active').checked ? 1 : 0;
    
    // Ensure empty strings for null location fields
    if (!jsonData.province_id || jsonData.province_id === '') jsonData.province_id = null;
    if (!jsonData.city_id || jsonData.city_id === '') jsonData.city_id = null;
    if (!jsonData.district_id || jsonData.district_id === '') jsonData.district_id = null;
    if (!jsonData.subdistrict_id || jsonData.subdistrict_id === '') jsonData.subdistrict_id = null;
    if (!jsonData.subdistrict_id || jsonData.subdistrict_id === '') jsonData.subdistrict_id = null;
    // json.assigned_to removed
    
    console.log('📤 Sending PUT request to:', `/company/customers/${currentCustomerId}`);
    console.log('📦 Data:', jsonData);
    
    fetch(`/company/customers/${currentCustomerId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify(jsonData)
    })
    .then(response => {
        console.log('✅ Response received:', response.status, response.statusText);
        if (!response.ok) {
            return response.json().then(data => {
                console.error('❌ Response NOT OK:', data);
                
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
                        showError(errorMessage);
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
                    showError(msg);
                }
                throw new Error(msg);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('📥 Response data:', data);
        if (data.status === 'success') {
            console.log('✅ UPDATE SUCCESS!');
            showSuccess(data.message || 'Customer berhasil diperbarui.');
            closeEditModal();
            window.location.reload(); // Reload to refresh data
        } else {
            console.error('⚠️ UPDATE FAILED:', data.message);
            const msg = data.message || 'Customer tidak berhasil diperbarui.';
            if (typeof Swal !== 'undefined') {
                 Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: msg,
                    confirmButtonColor: '#214589',
                });
            } else {
                showError(msg);
            }
        }
    })
    .catch(error => {
        if (error.message !== 'Validation failed') {
            console.error('❌ ERROR caught:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: error.message || 'Terjadi kesalahan.',
                    confirmButtonColor: '#214589',
                });
            } else {
                showError(error.message || 'Terjadi kesalahan saat memperbarui customer.');
            }
        }
    });
}

// Utility functions
function showSuccess(message) {
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModalOverlay').style.display = 'flex';
    setTimeout(() => {
        document.getElementById('successModalOverlay').style.display = 'none';
    }, 3000);
}

function showError(message, errors = null) {
    const errorMsgElement = document.getElementById('errorMessage');
    errorMsgElement.innerHTML = ''; // Clear existing
    
    // Create main message div
    const msgDiv = document.createElement('div');
    msgDiv.textContent = message;
    errorMsgElement.appendChild(msgDiv);
    
    // If we have detailed errors, append them as a list
    if (errors && Array.isArray(errors) && errors.length > 0) {
        const ul = document.createElement('ul');
        ul.style.textAlign = 'left';
        ul.style.marginTop = '10px';
        ul.style.fontSize = '0.9em';
        ul.style.listStyleType = 'disc';
        ul.style.paddingLeft = '20px';
        
        errors.forEach(err => {
            const li = document.createElement('li');
            li.textContent = err;
            ul.appendChild(li);
        });
        errorMsgElement.appendChild(ul);
    }
    
    document.getElementById('errorModalOverlay').style.display = 'flex';
}

function closeSuccessModal() {
    document.getElementById('successModalOverlay').style.display = 'none';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').style.display = 'none';
}

// ===== MULTI PIC HELPER FUNCTIONS =====
// Note: Using native multi-select (no Select2) for better compatibility

// Refresh Multi PIC dropdown after creating a new contact
function refreshMultiPicContacts(newContactId, newContactName) {
    // Add new option to the select
    const selectElement = document.getElementById('create_contact_ids');
    if (selectElement) {
        const newOption = document.createElement('option');
        newOption.value = newContactId;
        newOption.textContent = newContactName;
        newOption.selected = true; // Auto-select the new contact
        selectElement.appendChild(newOption);
        
        // Trigger Select2 to update
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            jQuery('#create_contact_ids').trigger('change');
        }
        
        console.log('✅ Added new contact to Multi PIC:', newContactId, newContactName);
    }
}

// Override the contact creation success handler to refresh Multi PIC
// This function should be called after successful contact creation
function onContactCreated(contact) {
    if (contact && contact.id) {
        const contactLabel = `${contact.name} - ${contact.position || 'No Position'} (${contact.phone})`;
        refreshMultiPicContacts(contact.id, contactLabel);
    }
}

// ===== Import Excel/CSV functions =====
function openCustomerImportModal() {
    document.getElementById('customerImportOverlay').classList.remove('hidden');
    document.getElementById('customerImportFile').value = '';
    document.getElementById('custImportPreviewSection').style.display = 'none';
    document.getElementById('custImportConfirmBtn').style.display = 'none';
    document.getElementById('custImportPreviewBtn').disabled = true;
    document.body.style.overflow = 'hidden';
}

function closeCustomerImportModal() {
    document.getElementById('customerImportOverlay').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function custImportFileSelect(event) {
    document.getElementById('custImportPreviewBtn').disabled = !(event.target.files && event.target.files.length > 0);
    document.getElementById('custImportPreviewSection').style.display = 'none';
    document.getElementById('custImportConfirmBtn').style.display = 'none';
}

function customerPreviewImport(event) {
    if (event) event.preventDefault();
    const formData = new FormData(document.getElementById('customerImportForm'));
    const previewBtn = document.getElementById('custImportPreviewBtn');
    const section = document.getElementById('custImportPreviewSection');
    const content = document.getElementById('custImportPreviewContent');

    if (!formData.get('file') || !formData.get('file').name) { alert('Silakan pilih file terlebih dahulu.'); return; }

    previewBtn.disabled = true; previewBtn.textContent = 'Memuat...';
    section.style.display = 'block';
    content.innerHTML = '<div class="text-center py-3 text-sm text-gray-500">Menganalisis file...</div>';

    fetch('/company/customers/import-preview', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(result => {
        previewBtn.disabled = false; previewBtn.textContent = 'Preview';
        if (result.status !== 'success') { content.innerHTML = '<div class="text-red-600 text-sm">' + (result.message || 'Gagal membaca file.') + '</div>'; return; }
        const p = result.preview;
        let html = '<div class="text-sm space-y-1"><div>Total baris: <strong>' + p.total_rows + '</strong></div>';
        html += '<div>Baru: <strong class="text-green-700">' + p.new + '</strong> &middot; Nama sudah ada: <strong class="text-gray-600">' + p.existing + '</strong></div>';
        if (p.errors && p.errors.length) {
            html += '<div class="mt-2 text-red-600"><strong>' + p.errors.length + ' peringatan:</strong><ul class="list-disc list-inside">';
            p.errors.slice(0, 10).forEach(e => { html += '<li>' + e + '</li>'; });
            if (p.errors.length > 10) html += '<li>...dan ' + (p.errors.length - 10) + ' lainnya</li>';
            html += '</ul></div>';
        }
        html += '</div>';
        content.innerHTML = html;
        document.getElementById('custImportConfirmBtn').style.display = (p.total_rows > 0) ? 'inline-flex' : 'none';
    })
    .catch(() => { previewBtn.disabled = false; previewBtn.textContent = 'Preview'; content.innerHTML = '<div class="text-red-600 text-sm">Terjadi kesalahan saat memproses file.</div>'; });
}

function customerConfirmImport() {
    const formData = new FormData(document.getElementById('customerImportForm'));
    const confirmBtn = document.getElementById('custImportConfirmBtn');
    confirmBtn.disabled = true; confirmBtn.textContent = 'Mengimpor...';

    fetch('/company/customers/import', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(result => {
        const content = document.getElementById('custImportPreviewContent');
        const s = result.stats || {};
        let html = '<div class="text-sm space-y-1"><div class="font-semibold text-green-700">' + (result.message || 'Import selesai') + '</div>';
        if (s.errors && s.errors.length) {
            html += '<div class="mt-2 text-red-600"><strong>Baris gagal:</strong><ul class="list-disc list-inside">';
            s.errors.slice(0, 20).forEach(e => { html += '<li>Baris ' + e.row + ': ' + e.error + '</li>'; });
            if (s.errors.length > 20) html += '<li>...dan ' + (s.errors.length - 20) + ' lainnya</li>';
            html += '</ul></div>';
        }
        html += '</div>';
        content.innerHTML = html;
        confirmBtn.style.display = 'none';
        setTimeout(() => { window.location.reload(); }, 1800);
    })
    .catch(() => { confirmBtn.disabled = false; confirmBtn.textContent = 'Mulai Import'; alert('Terjadi kesalahan saat mengimpor.'); });
}
</script>

<!-- Customer Import Modal -->
<div id="customerImportOverlay" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4" onclick="if(event.target===this) closeCustomerImportModal()">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b px-5 py-3">
            <h2 class="text-lg font-semibold text-[#214589]">Import Customer dari Excel/CSV</h2>
            <button class="text-gray-400 hover:text-gray-600" onclick="closeCustomerImportModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="px-5 py-4">
            <form id="customerImportForm" onsubmit="customerPreviewImport(event)">
                <div class="form-group">
                    <label class="form-label">Pilih File Excel / CSV *</label>
                    <input type="file" name="file" id="customerImportFile" class="form-input" accept=".csv,.txt,.xlsx,.xls" required onchange="custImportFileSelect(event)">
                    <small class="text-muted">Format: .xlsx, .xls, atau .csv. Maksimum 10MB.</small>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 my-3">
                    <div class="font-semibold text-blue-900 mb-1">Unduh contoh format:</div>
                    <a href="/company/customers/import-template?format=xlsx" class="text-blue-700 underline mr-3">Template Excel (.xlsx)</a>
                    <a href="/company/customers/import-template?format=csv" class="text-blue-700 underline">Template CSV</a>
                </div>
                <div id="custImportPreviewSection" style="display:none;">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 my-3">
                        <div class="font-semibold mb-2">Hasil Preview:</div>
                        <div id="custImportPreviewContent"></div>
                    </div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <div class="font-semibold text-yellow-900 mb-1">📋 Ketentuan kolom:</div>
                    <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                        <li><strong>Wajib:</strong> name</li>
                        <li><strong>Opsional:</strong> phone, email, address, company_type (default PT), is_pkp (Y/N), customer_category, province, city, district, subdistrict, postal_code, npwp, customer_group, label_alias, is_active (Y/N)</li>
                        <li>customer_category & wilayah dicocokkan dengan nama yang sudah terdaftar; jika diisi tapi tidak ditemukan, baris dilaporkan gagal</li>
                        <li>customer_code dibuat otomatis. Import PIC/kontak belum termasuk (menyusul)</li>
                    </ul>
                </div>
            </form>
        </div>
        <div class="flex justify-end gap-2 border-t px-5 py-3">
            <button type="button" class="btn btn-secondary" onclick="closeCustomerImportModal()">Batal</button>
            <button type="button" class="btn btn-info" onclick="customerPreviewImport(event)" id="custImportPreviewBtn" disabled>Preview</button>
            <button type="button" class="btn btn-primary" onclick="customerConfirmImport()" id="custImportConfirmBtn" style="display:none;">Mulai Import</button>
        </div>
    </div>
</div>
@include('company.customers.status-modal')
@endsection
