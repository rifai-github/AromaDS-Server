@extends('layouts.app')

@section('title', 'Contract')
@section('breadcrumb', 'Home / Marketing / Contract')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }
    
    /* Ensure all elements use border-box */
    *, *::before, *::after {
        box-sizing: border-box;
    }
    
    /* Responsive Table */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 10px 10px;
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        position: relative;
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
    }
    
    /* Scroll indicator */
    .table-container::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 20px;
        background: linear-gradient(to left, rgba(255,255,255,0.8), transparent);
        pointer-events: none;
        z-index: 5;
        opacity: var(--scroll-indicator-opacity, 0);
        transition: opacity 0.3s ease;
    }
    
    /* Show scroll indicator when content is scrollable */
    .table-container:hover::after {
        opacity: var(--scroll-indicator-opacity, 0);
    }
    
    /* Custom scrollbar styling */
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
    
    .responsive-table {
        min-width: 3000px; /* Increased from 2400px for 3 new notes columns */
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
        margin: 0;
        padding: 0;
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
        overflow: visible;
        text-overflow: unset;
    }
    
    .responsive-table td {
        overflow: hidden;
        text-overflow: ellipsis;
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
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
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
            flex-wrap: nowrap;
            gap: 5px;
            max-width: 100%;
            overflow-x: auto;
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
        position: relative;
        z-index: 10;
        cursor: pointer;
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
    
    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }
    
    .space-y-4 > * + * {
        margin-top: 1rem;
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
        padding: 12px 16px;
        border: 2px solid #214589;
        border-radius: 8px;
        font-size: 14px;
        background-color: #ffffff;
        color: #1f2937;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #1e3a8a;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
        background-color: #ffffff;
    }
    
    .form-input::placeholder {
        color: #9ca3af;
    }
    
    /* Contract number field - readonly styling */
    .form-input[name="contract_number"] {
        background-color: #f9fafb;
        color: #6b7280;
        border-color: #d1d5db;
        cursor: not-allowed;
    }
    
    /* All other readonly fields should look normal */
    .form-input:read-only:not([name="contract_number"]) {
        background-color: #ffffff;
        color: #1f2937;
        border-color: #214589;
        cursor: text;
    }
    
    .form-textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    select.form-input {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 12px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 40px;
        appearance: none;
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
    
    .modal-overlay.active {
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
    
    /* Pagination button styles */
    .pagination-btn {
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid #e5e7eb;
        background-color: #f9fafb;
        color: #374151;
    }
    
    .pagination-btn:hover {
        background-color: #f3f4f6;
        border-color: #d1d5db;
        color: #1f2937;
    }
    
    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .pagination-btn:disabled:hover {
        background-color: #f9fafb;
        border-color: #e5e7eb;
        color: #374151;
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
    }
    
    .delete-modal-container {
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
    }
    
    .error-modal-container {
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
    }
    
    .success-modal-container {
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
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .success-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }
    
    .btn-success-close {
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
    
    .btn-success-close:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
    }
    
    /* Hide Surveyor Column */
    .contract-surveyor-column {
        display: none !important;
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Contract Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Contract</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <a href="{{ route('marketing.contracts.merge-wizard') }}" class="btn" style="background:#7c3aed;color:white;gap:6px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3M16 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3"/><line x1="12" y1="3" x2="12" y2="21"/></svg>
                    <span class="hidden md:inline">Merge Contracts</span>
                    <span class="md:hidden">Merge</span>
                </a>
                <a href="{{ route('marketing.contracts.wizard.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Contract</span>
                    <span class="md:hidden">Add New</span>
                </a>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full bg-white p-4">
            <div class="flex flex-row justify-start items-center">
                <div class="flex flex-row justify-start items-center">
                    <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                    <label for="selectAll" class="ml-2 text-sm text-gray-700 cursor-pointer">Select all</label>
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
            <table class="responsive-table" id="contractsTable">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="contract_number" style="width: 140px;">Contract Number</th>
                        <th data-no-filter style="display: none;">Old Contract</th>
                        <th data-no-filter style="display: none;">Current Contract</th>
                        <th data-column="status" style="width: 130px;">Status</th>
                        <th data-column="contract_date" data-type="date" style="width: 110px;">Contract Date</th>
                        <th data-column="quotation.quotation_number" data-relation="quotation" style="width: 140px;">Sales Quotation No</th>
                        <th data-column="quotation.quotation_type" data-relation="quotation" style="width: 110px;">Contract Type</th>
                        <th data-no-filter style="width: 100px;">Contract Period</th>
                        <th data-column="marketing.name" data-relation="marketing" style="width: 140px;">Sales Name</th>
                        <th data-column="customer.name" style="width: 240px;">Customer Name</th>
                        <th data-column="quotation.pic_name" style="display: none;">PIC Name</th>
                        <th data-column="customer.email" style="display: none;">Email</th>
                        <th data-column="quotation.survey.surveyor.name" class="contract-surveyor-column" style="display: none;">Surveyor</th>
                        <th data-column="approver.name" data-relation="approver" style="display: none;">Approver</th>
                        <th data-column="contract_value" data-type="numeric" style="display: none;">Contract Value</th>
                        <th data-column="start_date" data-type="date" style="display: none;">Start Date</th>
                        <th data-column="end_date" data-type="date" style="display: none;">End Date</th>
                        <!-- <th data-column="contract_terms">Contract Terms</th> -->
                        <th data-column="notes" style="display: none;">Internal Notes</th>
                        <th data-column="notes_operation" style="display: none;">Notes Operation</th>
                        <th data-column="notes_finance" style="display: none;">Notes Finance</th>
                        <th data-column="notes_sales" style="display: none;">Notes Sales</th>
                        <th data-column="creator.name" data-relation="creator" style="display: none;">Created By</th>
                        <th data-column="created_at" data-type="date" style="display: none;">Created At</th>
                        <th data-column="updater.name" data-relation="updater" style="display: none;">Last Updated By</th>
                        <th data-column="updated_at" data-type="date" style="display: none;">Last Updated At</th>
                        <th data-no-filter style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($contracts ?? [] as $contract)
                    <tr class="table-row-hover cursor-pointer border-b border-gray-200" data-id="{{ $contract->id }}" onclick="window.location.href='{{ route('marketing.contracts.show', $contract->id) }}'">
                        <td class="w-[50px] p-2 text-center">
                            <input type="checkbox" class="row-checkbox w-[10px] h-[10px] md:w-[15px] md:h-[15px] lg:w-[20px] lg:h-[20px] bg-white border border-[#888888] rounded-[4px] cursor-pointer" value="{{ $contract->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td class="w-[140px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->contract_number ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[150px] p-2" style="display: none;">
                            @php
                                // Old Contract: jika contract ini dibuat dari renewal, tampilkan contract lama
                                $oldContractNumber = $contract->contract_number;
                                $oldContractId = null;
                                if ($contract->quotation && $contract->quotation->existing_contract_id) {
                                    $oldContractNumber = $contract->quotation->existingContract->contract_number ?? $contract->contract_number;
                                    $oldContractId = $contract->quotation->existing_contract_id;
                                }
                            @endphp
                            @if($oldContractId)
                                <a href="{{ route('marketing.contracts.show', $oldContractId) }}" 
                                   class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-blue-600 hover:text-blue-800 hover:underline break-words"
                                   onclick="event.stopPropagation()" title="Lihat contract lama">
                                    {{ $oldContractNumber }}
                                </a>
                            @else
                                <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $oldContractNumber }}</p>
                            @endif
                        </td>
                        <td class="w-[150px] p-2" style="display: none;">
                            @php
                                // Current Contract: cek apakah ada contract lain yang merupakan renewal dari contract ini
                                $currentContractNumber = $contract->contract_number;
                                $currentContractId = null;
                                
                                // Cara 1: Via relasi renewedByContract (Quotation.existing_contract_id)
                                if ($contract->renewedByContract) {
                                    $currentContractNumber = $contract->renewedByContract->contract_number;
                                    $currentContractId = $contract->renewedByContract->id;
                                }
                                // Cara 2: Fallback via ContractRenewal record
                                elseif ($contract->renewals->isNotEmpty()) {
                                    $completedRenewal = $contract->renewals->where('status', 'completed')->whereNotNull('new_contract_id')->first();
                                    if ($completedRenewal && $completedRenewal->newContract) {
                                        $currentContractNumber = $completedRenewal->newContract->contract_number;
                                        $currentContractId = $completedRenewal->new_contract_id;
                                    }
                                }
                            @endphp
                            @if($currentContractId)
                                <a href="{{ route('marketing.contracts.show', $currentContractId) }}" 
                                   class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-blue-600 hover:text-blue-800 hover:underline break-words"
                                   onclick="event.stopPropagation()" title="Lihat contract terbaru">
                                    {{ $currentContractNumber }}
                                </a>
                            @else
                                <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $currentContractNumber }}</p>
                            @endif
                        </td>
                        <td class="w-[130px] p-2">
                            @php
                                $statusColors = [
                                    'active' => 'background-color: #dcfce7; color: #166534;',
                                    'expired' => 'background-color: #fef2f2; color: #991b1b;',
                                    'terminated' => 'background-color: #fef2f2; color: #991b1b;',
                                    'cancelled' => 'background-color: #fef2f2; color: #991b1b;',
                                    'pending' => 'background-color: #ffedd5; color: #c2410c;',
                                    'waiting_for_approval' => 'background-color: #ffedd5; color: #c2410c;',
                                    'rejected' => 'background-color: #fef2f2; color: #991b1b;',
                                    'draft' => 'background-color: #e5e7eb; color: #4b5563;',
                                ];
                                $style = $statusColors[$contract->contract_status] ?? 'background-color: #f3f4f6; color: #374151;';
                            @endphp
                            <span style="padding: 4px 8px; font-size: 12px; border-radius: 9999px; {{ $style }}">
                                {{ $contract->status_text ?? ucfirst($contract->contract_status ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="w-[110px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->contract_date ? \Carbon\Carbon::parse($contract->contract_date)->format('d F Y') : 'N/A' }}</p>
                        </td>
                        <!-- Sales Quotation No -->
                        <td class="w-[140px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->quotation->quotation_number ?? 'N/A' }}</p>
                        </td>
                        <!-- Contract Type -->
                        <td class="w-[110px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ ucfirst($contract->quotation->quotation_type ?? 'N/A') }}</p>
                        </td>
                        <!-- Contract Period -->
                        <td class="w-[100px] p-2">
                            @php
                                // Contract Period: prefer quotation.rental_period (e.g. "12 bulan"),
                                // fall back to computing months from start_date/end_date.
                                $periodDisplay = '-';
                                if (!empty($contract->quotation?->rental_period)) {
                                    $periodDisplay = $contract->quotation->rental_period;
                                } elseif ($contract->start_date && $contract->end_date) {
                                    $months = \Carbon\Carbon::parse($contract->start_date)
                                        ->diffInMonths(\Carbon\Carbon::parse($contract->end_date));
                                    $periodDisplay = $months > 0 ? ($months . ' bulan') : '-';
                                }
                            @endphp
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $periodDisplay }}</p>
                        </td>
                        <!-- Sales Name (marketing) -->
                        <td class="w-[140px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->quotation->marketing->name ?? $contract->marketing->name ?? 'N/A' }}</p>
                        </td>
                        <!-- Customer Name -->
                        <td class="w-[240px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->customer->name ?? $contract->quotation->prospect->company_name ?? 'N/A' }}</p>
                        </td>
                        <!-- Hidden: PIC Name -->
                        <td class="w-[150px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->quotation->pic_name ?? 'N/A' }}</p>
                        </td>
                        <!-- Hidden: Email -->
                        <td class="w-[180px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->customer->email ?? $contract->quotation->prospect->email ?? 'N/A' }}</p>
                        </td>
                        <!-- Hidden: Surveyor -->
                        <td class="w-[150px] p-2 contract-surveyor-column" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->quotation->survey->surveyor->name ?? 'N/A' }}</p>
                        </td>
                        <!-- Hidden: Approver -->
                        <td class="w-[150px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->quotation->approver->name ?? 'N/A' }}</p>
                        </td>
                        <!-- Hidden: Contract Value -->
                        <td class="w-[120px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->formatted_contract_value ?? 'N/A' }}</p>
                        </td>
                        <!-- Hidden: Start Date -->
                        <td class="w-[120px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->actual_start_date ? \Carbon\Carbon::parse($contract->actual_start_date)->format('d F Y') : '-' }}</p>
                        </td>
                        <!-- Hidden: End Date -->
                        <td class="w-[120px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->actual_end_date ? \Carbon\Carbon::parse($contract->actual_end_date)->format('d F Y') : '-' }}</p>
                        </td>
                        <!-- <td class="w-[200px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->contract_terms ?? 'N/A' }}</p>
                        </td> -->
                        <!-- Hidden: Internal Notes -->
                        <td class="w-[200px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ $contract->notes ?: $contract->internal_remark ?: 'N/A' }}</p>
                        </td>
                        <!-- Hidden: Notes Operation -->
                        <td class="w-[200px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#888888] break-words italic" title="{{ $contract->notes_operation ?? 'No notes' }}">{{ $contract->notes_operation ? \Illuminate\Support\Str::limit($contract->notes_operation, 50) : '-' }}</p>
                        </td>
                        <!-- Hidden: Notes Finance -->
                        <td class="w-[200px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#888888] break-words italic" title="{{ $contract->notes_finance ?? 'No notes' }}">{{ $contract->notes_finance ? \Illuminate\Support\Str::limit($contract->notes_finance, 50) : '-' }}</p>
                        </td>
                        <!-- Hidden: Notes Sales -->
                        <td class="w-[200px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#888888] break-words italic" title="{{ $contract->notes_sales ?? 'No notes' }}">{{ $contract->notes_sales ? \Illuminate\Support\Str::limit($contract->notes_sales, 50) : '-' }}</p>
                        </td>
                        <!-- Hidden: Created By -->
                        <td class="w-[120px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->creator->name ?? 'N/A' }}</p>
                        </td>
                        <!-- Hidden: Created At -->
                        <td class="w-[150px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">
                                {!! $contract->created_at ? \Carbon\Carbon::parse($contract->created_at)->format('d F Y') . '<br />at ' . \Carbon\Carbon::parse($contract->created_at)->format('H.i') . ' WIB' : 'N/A' !!}
                            </p>
                        </td>
                        <!-- Hidden: Last Updated By -->
                        <td class="w-[120px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $contract->updater->name ?? 'N/A' }}</p>
                        </td>
                        <!-- Hidden: Last Updated At -->
                        <td class="w-[150px] p-2" style="display: none;">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">
                                {!! $contract->updated_at ? \Carbon\Carbon::parse($contract->updated_at)->format('d F Y') . '<br />at ' . \Carbon\Carbon::parse($contract->updated_at)->format('H.i') . ' WIB' : 'N/A' !!}
                            </p>
                        </td>
                        <td class="w-[140px] p-2">
                            <div class="flex flex-row gap-1">
                                @if(auth()->user()->hasPermission('contracts.download'))
                                <a href="{{ route('marketing.contracts.download', $contract->id) }}" 
                                   class="btn btn-primary btn-sm" 
                                   title="Download PDF"
                                   onclick="event.stopPropagation()">
                                    <i class="fas fa-download"></i>
                                </a>
                                @endif
                                
                                @if(auth()->user()->hasPermission('contracts.print'))
                                <a href="{{ route('marketing.contracts.print', $contract->id) }}" 
                                   class="btn btn-secondary btn-sm" 
                                   title="Print PDF"
                                   target="_blank"
                                   onclick="event.stopPropagation()">
                                    <i class="fas fa-print"></i>
                                </a>
                                @endif
                                
                                @if(auth()->user()->hasPermission('contracts.update'))
                                <button 
                                   onclick="event.stopPropagation(); openNotesModal({{ $contract->id }});" 
                                   class="btn btn-sm" 
                                   style="background: #f59e0b; border-color: #f59e0b; color: white;"
                                   title="Edit Contract Notes">
                                    <i class="fas fa-sticky-note"></i>
                                </button>
                                @endif
                                
                                @if(!auth()->user()->hasPermission('contracts.download') && !auth()->user()->hasPermission('contracts.print') && !auth()->user()->hasPermission('contracts.update'))
                                <span class="text-xs text-gray-500">No Access</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="24" class="p-8 text-center">
                            <p class="text-[14px] md:text-[16px] lg:text-[18px] font-inter font-normal text-center text-[#666]">No contracts found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if(isset($pagination) && $pagination['current_page'] > 1)
                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                    
                @if(isset($pagination) && $pagination['last_page'] > 0)
                    @php
                        $start = max(1, $pagination['current_page'] - 2);
                        $end = min($pagination['last_page'], $pagination['current_page'] + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $pagination['current_page'])
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $pagination['last_page'])
                            @if($end < $pagination['last_page'] - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['last_page']]) }}" class="page-number">{{ $pagination['last_page'] }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if(isset($pagination) && $pagination['current_page'] < $pagination['last_page'])
                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $pagination['current_page'] ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $pagination['last_page'] ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Contract Details</h2>
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
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 6H5H21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 11V17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 11V17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h3 class="delete-title">Sembunyikan Kontrak</h3>
        <p class="delete-message" id="deleteMessage">Apakah Anda yakin ingin menyembunyikan kontrak ini? Tindakan ini masih bisa dibatalkan nanti.</p>
        <div class="delete-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
            <button class="btn btn-confirm" onclick="confirmDelete()">Ya, Sembunyikan</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="error-icon"></div>
        <h3 class="error-title">Ups... Terjadi Kendala</h3>
        <p class="error-message" id="errorMessage">Kontrak tidak berhasil disembunyikan. Silakan coba lagi.</p>
        <div class="error-buttons">
            <button class="btn btn-close" onclick="closeErrorModal()">Tutup</button>
            <button class="btn btn-retry" onclick="retryDelete()">Coba Lagi</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="success-icon"></div>
        <h3 class="success-title">Berhasil</h3>
        <p class="success-message" id="successMessage">Kontrak berhasil disembunyikan.</p>
    </div>
</div>

<!-- MOM6: Contract Notes Modal -->
<div id="contractNotesModal" class="modal-overlay" onclick="closeNotesModal()">
    <div class="modal-container" style="max-width: 800px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">📝 Edit Contract Notes</h2>
            <button class="modal-close" onclick="closeNotesModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="max-height: 600px; overflow-y: auto;">
            <form id="contractNotesForm">
                <input type="hidden" id="notesContractId" value="">
                
                <!-- Notes Operation -->
                <div class="form-group mb-4">
                    <label class="form-label" style="font-weight: 600; color: #2563eb;">
                        <i class="fas fa-tools mr-2"></i>Notes Operation
                    </label>
                    <small class="text-gray-600 block mb-2">Catatan untuk operation team (install, service, dll)</small>
                    <textarea 
                        id="notes_operation" 
                        name="notes_operation" 
                        rows="4" 
                        class="form-input w-full border border-gray-300 rounded-lg p-3"
                        placeholder="Contoh: Install unit di ruang meeting lantai 5, pastikan selesai sebelum jam 3 sore"
                    ></textarea>
                </div>

                <!-- Notes Finance -->
                <div class="form-group mb-4">
                    <label class="form-label" style="font-weight: 600; color: #059669;">
                        <i class="fas fa-dollar-sign mr-2"></i>Notes Finance
                    </label>
                    <small class="text-gray-600 block mb-2">Catatan untuk finance/invoice team</small>
                    <textarea 
                        id="notes_finance" 
                        name="notes_finance" 
                        rows="4" 
                        class="form-input w-full border border-gray-300 rounded-lg p-3"
                        placeholder="Contoh: TOP 30 hari, invoice dikirim setiap tanggal 1, PIC: Ibu Siti"
                    ></textarea>
                </div>

                <!-- Notes Sales -->
                <div class="form-group mb-4">
                    <label class="form-label" style="font-weight: 600; color: #dc2626;">
                        <i class="fas fa-chart-line mr-2"></i>Notes Sales
                    </label>
                    <small class="text-gray-600 block mb-2">Catatan untuk sales/renewal (muncul pop-up saat renewal)</small>
                    <textarea 
                        id="notes_sales" 
                        name="notes_sales" 
                        rows="4" 
                        class="form-input w-full border border-gray-300 rounded-lg p-3"
                        placeholder="Contoh: Renewal reminder 2 bulan sebelum expire. Customer prefer komunikasi via WA: 081234567890"
                    ></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="justify-content: center; gap: 16px;">
            <button type="button" class="btn btn-outline" onclick="closeNotesModal()">
                <i class="fas fa-times mr-2"></i>Cancel
            </button>
            <button type="button" class="btn btn-primary" onclick="saveContractNotes()" style="background: #3b82f6; border-color: #3b82f6;">
                <i class="fas fa-save mr-2"></i>Save Notes
            </button>
        </div>
    </div>
</div>

<script>
// Test if JavaScript is working
console.log('Contract page JavaScript loaded');

// Contract Signing Enhancement Functions
function openDigitalSignatureModal(contractId) {
    document.getElementById('modalTitle').textContent = 'Add Digital Signature';
    document.getElementById('modalBody').innerHTML = `
        <div class="space-y-4">
            <div class="form-group">
                <label class="form-label">Digital Signature</label>
                <canvas id="signatureCanvas" width="400" height="200" style="border: 1px solid #ddd; cursor: crosshair;"></canvas>
                <div class="mt-2">
                    <button type="button" class="btn btn-outline btn-sm" onclick="clearSignature()">Clear</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="saveSignature()">Save</button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Position</label>
                <input type="text" id="signaturePosition" class="form-input" placeholder="Enter your position" value="Staff">
            </div>
            <div class="form-group">
                <label class="form-label">Signature File (Optional)</label>
                <input type="file" id="signatureFile" class="form-input" accept="image/*">
            </div>
        </div>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary bg-yellow-500 hover:bg-yellow-600 text-white border-yellow-500 hover:border-yellow-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200 shadow-md hover:shadow-lg" onclick="submitDigitalSignature(${contractId})">
                <i class="fas fa-check mr-2"></i>Add Signature
            </button>
        </div>
    `;
    openModal();
    initializeSignatureCanvas();
}

function openNPWPModal(contractId) {
    document.getElementById('modalTitle').textContent = 'Verify NPWP';
    document.getElementById('modalBody').innerHTML = `
        <div class="space-y-4">
            <div class="form-group">
                <label class="form-label">NPWP Number</label>
                <input type="text" id="npwpNumber" class="form-input" placeholder="Enter NPWP number (15 digits)" maxlength="15">
                <small class="text-gray-500">Enter 15-digit NPWP number</small>
            </div>
            <div class="form-group">
                <label class="form-label">Verification Data (Optional)</label>
                <textarea id="verificationData" class="form-input" rows="3" placeholder="Additional verification information"></textarea>
            </div>
        </div>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" style="background-color: #3b82f6; color: white; border: 2px solid #3b82f6; padding: 8px 16px; border-radius: 6px; font-weight: 500; cursor: pointer;" onclick="submitNPWPVerification(${contractId})">
                <i class="fas fa-check-circle mr-2"></i>Verify NPWP
            </button>
        </div>
    `;
    openModal();
}

async function generateSchedule(contractId) {
    const confirmed = await showConfirmDialog(
        'Generate Schedule',
        'Apakah Anda yakin ingin membuat schedule untuk kontrak ini?',
        'Ya, Generate',
        'Batal'
    );

    if (!confirmed) {
        return;
    }

    fetch(`/marketing/contracts/${contractId}/generate-schedule`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('Berhasil', 'Schedule berhasil dibuat.');
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Schedule tidak berhasil dibuat.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan saat membuat schedule.');
    });
}

async function postContract(contractId) {
    const confirmed = await showConfirmDialog(
        'Post Contract',
        'Apakah Anda yakin ingin mem-posting kontrak ini? Kontrak akan menjadi aktif.',
        'Ya, Posting',
        'Batal'
    );

    if (!confirmed) {
        return;
    }

    fetch(`/marketing/contracts/${contractId}/post`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('Berhasil', 'Kontrak berhasil diposting.');
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Kontrak tidak berhasil diposting.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan saat mem-posting kontrak.');
    });
}

// Digital Signature Canvas Functions
let signatureCanvas, signatureCtx, isDrawing = false;

function initializeSignatureCanvas() {
    signatureCanvas = document.getElementById('signatureCanvas');
    signatureCtx = signatureCanvas.getContext('2d');
    
    signatureCanvas.addEventListener('mousedown', startDrawing);
    signatureCanvas.addEventListener('mousemove', draw);
    signatureCanvas.addEventListener('mouseup', stopDrawing);
    signatureCanvas.addEventListener('mouseout', stopDrawing);
}

function startDrawing(e) {
    isDrawing = true;
    const rect = signatureCanvas.getBoundingClientRect();
    signatureCtx.beginPath();
    signatureCtx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
}

function draw(e) {
    if (!isDrawing) return;
    const rect = signatureCanvas.getBoundingClientRect();
    signatureCtx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
    signatureCtx.stroke();
}

function stopDrawing() {
    isDrawing = false;
}

function clearSignature() {
    signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
}

function saveSignature() {
    const signatureData = signatureCanvas.toDataURL();
    localStorage.setItem('tempSignature', signatureData);
    showSuccessDialog('Berhasil', 'Tanda tangan berhasil disimpan.');
}

function submitDigitalSignature(contractId) {
    const signatureData = signatureCanvas.toDataURL();
    const position = document.getElementById('signaturePosition').value;
    const signatureFile = document.getElementById('signatureFile').files[0];
    
    if (!signatureData || signatureData === 'data:,') {
        showWarningDialog('Perhatian', 'Silakan gambar tanda tangan terlebih dahulu.');
        return;
    }
    
    const formData = new FormData();
    formData.append('signature_data', signatureData);
    formData.append('position', position);
    if (signatureFile) {
        formData.append('signature_file', signatureFile);
    }
    
    fetch(`/marketing/contracts/${contractId}/digital-signature`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('Berhasil', 'Tanda tangan digital berhasil ditambahkan.');
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Tanda tangan digital tidak berhasil ditambahkan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan saat menambahkan tanda tangan digital.');
    });
}

function submitNPWPVerification(contractId) {
    const npwpNumber = document.getElementById('npwpNumber').value;
    const verificationData = document.getElementById('verificationData').value;
    
    if (!npwpNumber || npwpNumber.length !== 15) {
        showWarningDialog('Perhatian', 'Silakan masukkan NPWP 15 digit yang valid.');
        return;
    }
    
    const data = {
        npwp_number: npwpNumber,
        verification_data: verificationData ? { notes: verificationData } : {}
    };
    
    fetch(`/marketing/contracts/${contractId}/verify-npwp`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('Berhasil', 'NPWP berhasil diverifikasi.');
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'NPWP tidak berhasil diverifikasi.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan saat memverifikasi NPWP.');
    });
}

// Global variables
let selectedIdsForRetry = [];

// Function to format date with 3-digit month
function formatDateWithThreeDigitMonth(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(3, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}
let successModalTimer = null;

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    
    // Select All functionality
    const selectAllElement = document.getElementById('selectAll');
    if (selectAllElement) {
        selectAllElement.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            document.getElementById('headerSelectAll').checked = this.checked;
        });
    }

    const headerSelectAllElement = document.getElementById('headerSelectAll');
    if (headerSelectAllElement) {
        headerSelectAllElement.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            document.getElementById('selectAll').checked = this.checked;
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

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    console.log('Delete selected called, found checkboxes:', checkboxes.length);
    
    if (checkboxes.length === 0) {
        showWarningDialog('Perhatian', 'Silakan pilih minimal satu kontrak yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    console.log('Selected IDs for deletion:', selectedIdsForRetry);
    openDeleteModal();
}

// Modal functions
function openModal(title) {
    console.log('openModal called with title:', title);
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
    document.body.style.overflow = 'auto';
    document.getElementById('modalBody').innerHTML = '';
}

// CRUD Modal functions
// openCreateModal function removed - contracts are auto-generated from quotations
function openCreateModal() {
    // Function disabled - contracts are auto-generated from quotations
    console.log('openCreateModal called - DISABLED');
    return;
    
    // Generate contract number
    const currentDate = new Date();
    const year = currentDate.getFullYear();
    const month = String(currentDate.getMonth() + 1).padStart(2, '0');
    const day = String(currentDate.getDate()).padStart(2, '0');
    const timestamp = Date.now().toString().slice(-4);
    const contractNumber = `CTR-${year}${month}${day}-${timestamp}`;
    
    document.getElementById('modalTitle').textContent = 'Create New Contract';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Let's create a new contract with all the necessary details.</p>
        <form id="createForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Contract Number *</label>
                        <input type="text" name="contract_number" class="form-input" value="${contractNumber}" readonly style="background-color: #f9fafb; color: #6b7280;">
                    </div>
                    <!-- Contract Date field hidden - auto-generated on creation -->
                    <div class="form-group">
                        <label class="form-label">Customer *</label>
                        <select name="customer_id" class="form-input" required>
                            <option value="">Select Customer</option>
                            @foreach($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quotation (Approved Only)</label>
                        <select name="quotation_id" class="form-input">
                            <option value="">Select Quotation</option>
                            @foreach($quotations ?? [] as $quotation)
                                <option value="{{ $quotation->id }}">{{ $quotation->quotation_number }} - {{ $quotation->company_name ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Contract Type *</label>
                        <select name="contract_type" class="form-input" required>
                            <option value="">Select Type</option>
                            <option value="rental">Rental</option>
                            <option value="service">Service</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-input" required>
                            <option value="">Select Status</option>
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-input">
                    </div>
                </div>
            </div>
            
            <!-- Full Width Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="form-group">
                    <label class="form-label">Contract Value</label>
                    <input type="number" name="contract_value" class="form-input" placeholder="0" step="0.01">
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Terms</label>
                    <select name="payment_terms" class="form-input">
                        <option value="">Select Payment Terms</option>
                        <option value="cash">Cash</option>
                        <option value="credit_30">Credit 30 Days</option>
                        <option value="credit_60">Credit 60 Days</option>
                        <option value="credit_90">Credit 90 Days</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Contract Terms</label>
                <textarea name="contract_terms" class="form-input form-textarea" placeholder="Contract terms and conditions" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Internal Notes</label>
                <textarea name="internal_notes" class="form-input form-textarea" placeholder="Internal notes and comments" rows="3"></textarea>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Create Contract</button>
        </div>
    `;
    openModal('Create New Contract');
}

function openViewModal(id) {
    document.getElementById('modalTitle').textContent = 'View Contract';
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/marketing/contracts/${id}`)
        .then(response => response.json())
        .then(response => {
            const data = response.data;
            // Add Contract Signing Enhancement data to data object
            data.can_be_signed = response.can_be_signed;
            data.npwp_status = response.npwp_status;
            data.digital_signature_status = response.digital_signature_status;
            data.can_generate_schedule = response.can_generate_schedule;
            data.is_ready_for_posting = response.is_ready_for_posting;
            data.has_digital_signature = response.has_digital_signature;
            data.is_npwp_verified = response.is_npwp_verified;
            data.schedule_generated = response.schedule_generated;
            document.getElementById('modalBody').innerHTML = `
                <!-- Contract Information Section -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Contract Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div class="detail-item">
                                <label class="form-label">Contract Number</label>
                                <p class="detail-value">${data.contract_number || 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Contract Date</label>
                                <p class="detail-value">${data.contract_date ? formatDateWithThreeDigitMonth(new Date(data.contract_date)) : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Contract Type</label>
                                <p class="detail-value">${data.contract_type || 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Status</label>
                                <p class="detail-value">${data.contract_status || 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Start Date</label>
                                <p class="detail-value">${data.start_date ? formatDateWithThreeDigitMonth(new Date(data.start_date)) : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">End Date</label>
                                <p class="detail-value">${data.end_date ? formatDateWithThreeDigitMonth(new Date(data.end_date)) : 'N/A'}</p>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="detail-item">
                                <label class="form-label">Contract Value</label>
                                <p class="detail-value">${data.formatted_contract_value || (data.contract_value ? 'Rp ' + new Intl.NumberFormat('id-ID').format(data.contract_value) : 'N/A')}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Payment Terms</label>
                                <p class="detail-value">${data.payment_terms_text || data.payment_terms || 'N/A'}</p>
                            </div>
                            
                            <!-- Contract Signing Enhancement Section -->
                            <div class="detail-item">
                                <label class="form-label">Digital Signature Status</label>
                                <p class="detail-value">
                                    <span class="badge ${data.digital_signature_status === 'signed' ? 'badge-success' : data.digital_signature_status === 'pending' ? 'badge-warning' : 'badge-secondary'}">
                                        ${data.digital_signature_status === 'signed' ? 'Signed' : data.digital_signature_status === 'pending' ? 'Pending' : 'Not Signed'}
                                    </span>
                                </p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">NPWP Status</label>
                                <p class="detail-value">
                                    <span class="badge ${data.npwp_status === 'verified' ? 'badge-success' : data.npwp_status === 'pending_verification' ? 'badge-warning' : 'badge-secondary'}">
                                        ${data.npwp_status === 'verified' ? 'Verified' : data.npwp_status === 'pending_verification' ? 'Pending' : 'Not Provided'}
                                    </span>
                                </p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Schedule Generated</label>
                                <p class="detail-value">
                                    <span class="badge ${data.schedule_generated ? 'badge-success' : 'badge-secondary'}">
                                        ${data.schedule_generated ? 'Generated' : 'Not Generated'}
                                    </span>
                                </p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Created By</label>
                                <p class="detail-value">${data.creator ? data.creator.name : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Updated By</label>
                                <p class="detail-value">${data.updater ? data.updater.name : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Marketing Name</label>
                                <p class="detail-value">${data.marketing ? data.marketing.name : (data.quotation && data.quotation.marketing ? data.quotation.marketing.name : 'N/A')}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Approver</label>
                                <p class="detail-value">${data.quotation && data.quotation.approver ? data.quotation.approver.name : 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Information Section -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Customer Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div class="detail-item">
                                <label class="form-label">Customer Name</label>
                                <p class="detail-value">${data.customer ? data.customer.name : (data.quotation && data.quotation.prospect ? data.quotation.prospect.company_name : 'N/A')}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">PIC Name</label>
                                <p class="detail-value">${data.quotation && data.quotation.prospect ? data.quotation.prospect.pic_name : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Email</label>
                                <p class="detail-value">${data.customer ? data.customer.email : (data.quotation && data.quotation.prospect ? data.quotation.prospect.email : 'N/A')}</p>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="detail-item">
                                <label class="form-label">Phone</label>
                                <p class="detail-value">${data.customer ? data.customer.phone : (data.quotation && data.quotation.prospect ? data.quotation.prospect.phone : 'N/A')}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Address</label>
                                <p class="detail-value">${data.customer ? data.customer.address : (data.quotation && data.quotation.prospect ? data.quotation.prospect.address : 'N/A')}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Tax Code</label>
                                <p class="detail-value">${data.customer ? data.customer.tax_code : (data.quotation && data.quotation.prospect ? data.quotation.prospect.tax_code : 'N/A')}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quotation Information Section -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Quotation Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div class="detail-item">
                                <label class="form-label">Quotation Number</label>
                                <p class="detail-value">${data.quotation ? data.quotation.quotation_number : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Quotation Date</label>
                                <p class="detail-value">${data.quotation && data.quotation.quotation_date ? formatDateWithThreeDigitMonth(new Date(data.quotation.quotation_date)) : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Valid Until</label>
                                <p class="detail-value">${data.quotation && data.quotation.valid_until ? formatDateWithThreeDigitMonth(new Date(data.quotation.valid_until)) : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Quotation Status</label>
                                <p class="detail-value">${data.quotation ? data.quotation.status : 'N/A'}</p>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="detail-item">
                                <label class="form-label">Quotation Marketing</label>
                                <p class="detail-value">${data.quotation && data.quotation.marketing ? data.quotation.marketing.name : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Quotation Approver</label>
                                <p class="detail-value">${data.quotation && data.quotation.approver ? data.quotation.approver.name : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Date Approved</label>
                                <p class="detail-value">${data.quotation && data.quotation.date_approved ? formatDateWithThreeDigitMonth(new Date(data.quotation.date_approved)) : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Total Amount</label>
                                <p class="detail-value">${data.quotation ? (data.quotation.formatted_grand_total || (data.quotation.grand_total ? 'Rp ' + new Intl.NumberFormat('id-ID').format(data.quotation.grand_total) : 'N/A')) : 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Survey Information Section -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Survey Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div class="detail-item">
                                <label class="form-label">Surveyor</label>
                                <p class="detail-value">${data.quotation && data.quotation.survey && data.quotation.survey.surveyor ? data.quotation.survey.surveyor.name : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Survey Date</label>
                                <p class="detail-value">${data.quotation && data.quotation.survey && data.quotation.survey.survey_date ? formatDateWithThreeDigitMonth(new Date(data.quotation.survey.survey_date)) : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Building Name</label>
                                <p class="detail-value">${data.quotation && data.quotation.survey ? (data.quotation.survey.building_name || 'N/A') : 'N/A'}</p>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="detail-item">
                                <label class="form-label">Survey Location</label>
                                <p class="detail-value">${data.quotation && data.quotation.survey ? (data.quotation.survey.survey_location || 'N/A') : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Survey Result</label>
                                <p class="detail-value">${data.quotation && data.quotation.survey ? (data.quotation.survey.survey_result || 'N/A') : 'N/A'}</p>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Temperature</label>
                                <p class="detail-value">${data.quotation && data.quotation.survey ? (data.quotation.survey.temperature ? data.quotation.survey.temperature + '°C' : 'N/A') : 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Full Width Notes -->
                <div class="mt-6">
                    <div class="detail-item">
                        <label class="form-label">Contract Terms</label>
                        <p class="detail-value">${data.contract_terms || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Internal Notes</label>
                        <p class="detail-value">${data.internal_notes || 'N/A'}</p>
                    </div>
                </div>
            `;
            // Debug logging
            console.log('Contract Signing Enhancement Debug:');
            console.log('can_be_signed:', data.can_be_signed);
            console.log('npwp_status:', data.npwp_status);
            console.log('can_generate_schedule:', data.can_generate_schedule);
            console.log('is_ready_for_posting:', data.is_ready_for_posting);
            console.log('npwp_status !== verified:', data.npwp_status !== 'verified');
            
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Close</button>
                    <!-- Contract Signing Enhancement Actions -->
                    ${data.can_be_signed ? `<button type="button" class="btn btn-warning bg-yellow-500 hover:bg-yellow-600 text-white border-yellow-500 hover:border-yellow-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200 shadow-md hover:shadow-lg" onclick="openDigitalSignatureModal(${data.id})">
                        <i class="fas fa-signature mr-2"></i>Add Digital Signature
                    </button>` : ''}
                    ${data.npwp_status !== 'verified' ? `<button type="button" class="btn btn-primary" style="background-color: #3b82f6; color: white; border: 2px solid #3b82f6; padding: 8px 16px; border-radius: 6px; font-weight: 500; cursor: pointer;" onclick="openNPWPModal(${data.id})">
                        <i class="fas fa-id-card mr-2"></i>Verify NPWP
                    </button>` : ''}
                    ${data.can_generate_schedule ? `<button type="button" class="btn btn-success bg-green-500 hover:bg-green-600 text-white border-green-500 hover:border-green-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200 shadow-md hover:shadow-lg" onclick="generateSchedule(${data.id})">
                        <i class="fas fa-calendar-plus mr-2"></i>Generate Schedule
                    </button>` : ''}
                    ${data.is_ready_for_posting ? `<button type="button" class="btn btn-primary bg-purple-500 hover:bg-purple-600 text-white border-purple-500 hover:border-purple-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200 shadow-md hover:shadow-lg" onclick="postContract(${data.id})">
                        <i class="fas fa-paper-plane mr-2"></i>Post Contract
                    </button>` : ''}
                </div>
            `;
            openModal();
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading contract details.</div>';
        });
}

function openEditModal(id) {
    // Function disabled - contracts are auto-generated from quotations
    console.log('openEditModal called - DISABLED');
    return;
    
    document.getElementById('modalTitle').textContent = 'Edit Contract';
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/marketing/contracts/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update the contract information below.</p>
                <form id="editForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Contract Number *</label>
                                <input type="text" name="contract_number" class="form-input" value="${data.contract_number || ''}" readonly style="background-color: #f9fafb; color: #6b7280;">
                            </div>
                            <!-- Contract Date field hidden - auto-generated on creation -->
                            <div class="form-group">
                                <label class="form-label">Company Name *</label>
                                <select name="customer_id" class="form-input" required>
                                    <option value="">Select Company</option>
                                    @foreach($customers ?? [] as $customer)
                                        <option value="{{ $customer->id }}" ${data.customer_id == {{ $customer->id }} ? 'selected' : ''}>{{ $customer->company_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Quotation *</label>
                                <select name="quotation_id" class="form-input" required>
                                    <option value="">Select Quotation</option>
                                    @foreach($quotations ?? [] as $quotation)
                                        <option value="{{ $quotation->id }}" ${data.quotation_id == {{ $quotation->id }} ? 'selected' : ''}>{{ $quotation->quotation_number }} - {{ $quotation->customer->company_name ?? 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Contract Type *</label>
                                <select name="contract_type" class="form-input" required>
                                    <option value="">Select Type</option>
                                    <option value="rental" ${data.contract_type == 'rental' ? 'selected' : ''}>Rental</option>
                                    <option value="service" ${data.contract_type == 'service' ? 'selected' : ''}>Service</option>
                                    <option value="maintenance" ${data.contract_type == 'maintenance' ? 'selected' : ''}>Maintenance</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-input" required>
                                    <option value="">Select Status</option>
                                    <option value="draft" ${data.status == 'draft' ? 'selected' : ''}>Draft</option>
                                    <option value="active" ${data.status == 'active' ? 'selected' : ''}>Active</option>
                                    <option value="expired" ${data.status == 'expired' ? 'selected' : ''}>Expired</option>
                                    <option value="terminated" ${data.status == 'terminated' ? 'selected' : ''}>Terminated</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-input" value="${data.start_date || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-input" value="${data.end_date || ''}">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Full Width Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="form-group">
                            <label class="form-label">Contract Value</label>
                            <input type="number" name="contract_value" class="form-input" value="${data.contract_value || ''}" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Payment Terms</label>
                            <select name="payment_terms" class="form-input">
                                <option value="">Select Payment Terms</option>
                                <option value="cash" ${data.payment_terms == 'cash' ? 'selected' : ''}>Cash</option>
                                <option value="credit_30" ${data.payment_terms == 'credit_30' ? 'selected' : ''}>Credit 30 Days</option>
                                <option value="credit_60" ${data.payment_terms == 'credit_60' ? 'selected' : ''}>Credit 60 Days</option>
                                <option value="credit_90" ${data.payment_terms == 'credit_90' ? 'selected' : ''}>Credit 90 Days</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Contract Terms</label>
                        <textarea name="contract_terms" class="form-input form-textarea" placeholder="Contract terms and conditions" rows="3">${data.contract_terms || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Internal Notes</label>
                        <textarea name="internal_notes" class="form-input form-textarea" placeholder="Internal notes and comments" rows="3">${data.internal_notes || ''}</textarea>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm(${id})">Update Contract</button>
                </div>
            `;
            openModal();
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading contract details.</div>';
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/marketing/contracts/${id}` : '/marketing/contracts';
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

function submitCreateForm() {
    // Function disabled - contracts are auto-generated from quotations
    console.log('submitCreateForm called - DISABLED');
    return;
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('/marketing/contracts', {
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
            showSuccessModal('Kontrak berhasil dibuat.');
            closeModal();
            location.reload();
        } else {
            showErrorModal(result.message || 'Kontrak tidak berhasil dibuat.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showConnectionErrorModal();
    });
}

function submitEditForm(id) {
    // Function disabled - contracts are auto-generated from quotations
    console.log('submitEditForm called - DISABLED');
    return;
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = 'PUT';
    
    fetch(`/marketing/contracts/${id}`, {
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
            showSuccessModal('Kontrak berhasil diperbarui.');
            closeModal();
            location.reload();
        } else {
            showErrorModal(result.message || 'Kontrak tidak berhasil diperbarui.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showConnectionErrorModal();
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Apakah Anda yakin ingin menyembunyikan kontrak ini? Tindakan ini masih bisa dibatalkan nanti.'
        : `Apakah Anda yakin ingin menyembunyikan ${count} kontrak? Tindakan ini masih bisa dibatalkan nanti.`;
    
    document.getElementById('deleteMessage').textContent = message;
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function confirmDelete() {
    console.log('Confirm delete called with IDs:', selectedIdsForRetry);
    closeDeleteModal();
    
    fetch('/marketing/contracts/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => {
        console.log('Delete response status:', response.status);
        return response.json();
    })
    .then(result => {
        console.log('Delete response data:', result);
        if (result.success) {
            showSuccessModal(result.count);
        } else {
            showErrorModal(result.message);
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        showErrorModal('Terjadi kesalahan jaringan.');
    });
}

// Success Modal functions
function showSuccessModal(payload) {
    const message = typeof payload === 'string'
        ? payload
        : payload === 1
            ? 'Kontrak berhasil disembunyikan.'
            : `${payload} kontrak berhasil disembunyikan.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'Kontrak tidak berhasil disembunyikan. Silakan coba lagi.';
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

// MOM6: Contract Notes Functions
function openNotesModal(contractId) {
    // Fetch contract data
    fetch(`/marketing/contracts/${contractId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('notesContractId').value = contractId;
            document.getElementById('notes_operation').value = data.notes_operation || '';
            document.getElementById('notes_finance').value = data.notes_finance || '';
            document.getElementById('notes_sales').value = data.notes_sales || '';
            
            // Show modal
            document.getElementById('contractNotesModal').classList.add('show');
        })
        .catch(error => {
            console.error('Error loading contract notes:', error);
            showErrorDialog('Gagal', 'Catatan kontrak tidak berhasil dimuat. Silakan coba lagi.');
        });
}

function closeNotesModal() {
    document.getElementById('contractNotesModal').classList.remove('show');
}

function saveContractNotes() {
    const contractId = document.getElementById('notesContractId').value;
    const notes_operation = document.getElementById('notes_operation').value;
    const notes_finance = document.getElementById('notes_finance').value;
    const notes_sales = document.getElementById('notes_sales').value;
    
    // Show loading
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
    
    // Send update request
    fetch(`/marketing/contracts/${contractId}/update-notes`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            notes_operation: notes_operation,
            notes_finance: notes_finance,
            notes_sales: notes_sales
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Close modal
            closeNotesModal();
            
            // Show success message
            showSuccessDialog('Berhasil', 'Catatan kontrak berhasil diperbarui.');
            
            // Reload page to show updated notes
            window.location.reload();
        } else {
            throw new Error(data.message || 'Catatan kontrak tidak berhasil diperbarui.');
        }
    })
    .catch(error => {
        console.error('Error saving notes:', error);
        showErrorDialog('Gagal', error.message || 'Catatan kontrak tidak berhasil disimpan.');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Close modal when clicking outside
document.getElementById('contractNotesModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeNotesModal();
    }
});
</script>
@endsection
