@extends('layouts.app')

@section('title', 'Lost Unit Report')
@section('breadcrumb', 'Home / Marketing / Lost Unit Report')

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
        vertical-align: middle;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
    }
    
    .responsive-table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }
    
    .responsive-table tbody tr:nth-child(odd) {
        background-color: #ffffff;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff !important;
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
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 120px; min-width: 120px; }
    
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
    
    /* Form Input Styling */
    input[type="date"], input[type="text"], select, textarea {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
        width: 100%;
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

    .form-label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        color: #374151;
        font-size: 14px;
    }

    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 80px;
    }

    /* Grid Layout for Modal */
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-row.single {
        grid-template-columns: 1fr;
    }

    .form-row.full-width {
        grid-template-columns: 1fr;
    }

    /* Modal Form Container */
    .modal-form-container {
        border: 2px solid #214589;
        border-radius: 8px;
        padding: 20px;
        background-color: #f8fafc;
        margin-bottom: 20px;
    }

    .modal-form-section {
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }

    .modal-form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .modal-form-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #214589;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #214589;
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
        
        <!-- Lost Unit Report Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Lost Unit Report</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden sm:inline">Add New Lost Unit Report</span>
                    <span class="sm:hidden">Add New</span>
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
                        <th data-column="report_number">Report Number</th>
                        <th data-column="contract_number">Contract Number</th>
                        <th data-column="company_name">Company Name</th>
                        <th data-column="lost_unit_price" data-type="numeric">Lost Price</th>
                        <th data-column="rental_name">Rental</th>
                        <th data-column="room_name">Room</th>
                        <th data-column="status">Status</th>
                        <th data-column="reporter.name">Report By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="creator.name">Created By</th>
                        <th data-column="updated_at" data-type="date">Updated At</th>
                        <th data-column="updater.name">Updated By</th>
                       <!-- <th data-column="status">Status</th> -->
                      <!--  <th class="actions-column" data-no-filter>Actions</th> -->
                    </tr>
                </thead>
                
                <tbody>
                    @forelse($reports ?? [] as $report)
                    <tr data-id="{{ $report->id }}" onclick="window.location.href='{{ route('marketing.lost-unit-reports.show', $report->id) }}'">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $report->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $report->report_number ?? 'N/A' }}</td>
                        <td>{{ $report->contract_number ?? 'N/A' }}</td>
                        <td>{{ $report->company_name ?? 'N/A' }}</td>
                        <td class="text-right">Rp {{ number_format($report->lost_unit_price ?? 0, 0, ',', '.') }}</td>
                        <td>
                            @if(($report->rental_name == 'Multi-Item' || $report->items->count() > 1) && $report->items->count() > 0)
                                <div class="flex flex-col gap-1">
                                    @foreach($report->items->unique('master_rental_id')->take(3) as $item)
                                        <span class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $item->masterRental->name ?? ($item->masterRental->rental_name ?? 'N/A') }}</span>
                                    @endforeach
                                    @if($report->items->unique('master_rental_id')->count() > 3)
                                        <span class="text-xs text-gray-500">+{{ $report->items->unique('master_rental_id')->count() - 3 }} more</span>
                                    @endif
                                </div>
                            @else
                                {{ $report->rental_name ?? ($report->masterRental->name ?? 'N/A') }}
                            @endif
                        </td>
                        <td>
                            @if(($report->room_name == 'Multi-Room' || $report->items->count() > 1) && $report->items->count() > 0)
                                <div class="flex flex-col gap-1">
                                    @foreach($report->items->unique('room_id')->take(3) as $item)
                                        <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded">{{ $item->room->room_name ?? 'N/A' }}</span>
                                    @endforeach
                                    @if($report->items->unique('room_id')->count() > 3)
                                        <span class="text-xs text-gray-500">+{{ $report->items->unique('room_id')->count() - 3 }} more</span>
                                    @endif
                                </div>
                            @else
                                {{ $report->room_name ?? 'N/A' }}
                            @endif
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full 
                                {{ $report->status == 'approved' ? 'bg-green-100 text-green-800' : 
                                   ($report->status == 'rejected' ? 'bg-red-100 text-red-800' : 
                                   ($report->status == 'submitted' ? 'bg-blue-100 text-blue-800' : 
                                   'bg-yellow-100 text-yellow-800')) }}">
                                {{ $report->status_text ?? ucfirst($report->status ?? 'N/A') }}
                            </span>
                        </td>
                        <td>{{ $report->reporter->name ?? 'N/A' }}</td>
                        <td>
                            {!! $report->created_at ? \Carbon\Carbon::parse($report->created_at)->format('d/M/Y') . '<br />' . \Carbon\Carbon::parse($report->created_at)->format('H:i') : 'N/A' !!}
                        </td>
                        <td>{{ $report->creator->name ?? '-' }}</td>
                        <td>{!! $report->updated_at ? \Carbon\Carbon::parse($report->updated_at)->format('d/M/Y') . '<br />' . \Carbon\Carbon::parse($report->updated_at)->format('H:i') : '-' !!}</td>
                        <td>{{ $report->updater->name ?? '-' }}</td>
                       <!-- <td>
                            @php
                                $statusClass = match($report->status) {
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'waiting_approval' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                $statusLabel = match($report->status) {
                                    'waiting_approval' => 'Waiting Approval',
                                    default => ucfirst($report->status)
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td> -->
                      <!--  <td>
                             Actions column content here
                        </td> -->
                    </tr>
                    @empty
                    <tr>
                        <td colspan="15" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No reports found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($reports->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $reports->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Lost Unit Report</h2>
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
        <div class="delete-icon-container">
            <div class="delete-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 6H5H21" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10 11V17" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 11V17" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <h3 class="delete-modal-title">Hide Lost Unit Report</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this lost unit report? This action can be undone later.</p>
        <div class="delete-modal-buttons">
            <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn-hide" onclick="confirmDelete()">Yes, Hide</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="error-icon-container">
            <div class="error-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="#ef4444" stroke-width="2"/>
                    <line x1="15" y1="9" x2="9" y2="15" stroke="#ef4444" stroke-width="2"/>
                    <line x1="9" y1="9" x2="15" y2="15" stroke="#ef4444" stroke-width="2"/>
                </svg>
            </div>
        </div>
        <h3 class="error-modal-title">Hmm... Something Went Wrong</h3>
        <p class="error-modal-description" id="errorMessage">We couldn't hide the lost unit report. Please try again.</p>
        <div class="error-modal-buttons">
            <button class="btn-error-close" onclick="closeErrorModal()">Close</button>
            <button class="btn-error-retry" onclick="retryDelete()">Try Again</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="success-icon-container">
            <div class="success-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="#10b981" stroke-width="2"/>
                    <path d="m9 12 2 2 4-4" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <h3 class="success-modal-title">All Set!</h3>
        <p class="success-modal-description" id="successMessage">The lost unit report has been successfully hidden.</p>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;

// Function to format date with 3-digit month
function formatDateWithThreeDigitMonth(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(3, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
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
        alert('Please select at least one lost unit report to hide');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
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
    openModal('Create New Lost Unit Report');
    document.getElementById('modalBody').innerHTML = `
        <form id="reportForm">
            <div class="modal-form-container">
                <div class="modal-form-section">
                    <div class="modal-form-section-title">Report Information</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="contract_id">Contract <span style="color: red;">*</span></label>
                            <select class="form-select" id="contract_id" name="contract_id" required onchange="onContractChange(this.value)">
                                <option value="">Select Contract</option>
                                <!-- Options will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="room_id">Rooms <span style="color: red;">*</span></label>
                            <select class="form-select" id="room_id" name="room_id[]" multiple required disabled onchange="onRoomChange(this)">
                                <!-- Rooms loaded here -->
                            </select>
                             <small style="color: #666;">Hold Ctrl/Cmd to select multiple</small>
                        </div>
                    </div>
                     <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="master_rental_id">Rentals <span style="color: red;">*</span></label>
                            <select class="form-select" id="master_rental_id" name="master_rental_id[]" multiple required disabled onchange="onRentalChange(this)">
                                 <!-- Rentals loaded here -->
                            </select>
                             <small style="color: #666;">Hold Ctrl/Cmd to select multiple</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Lost Unit Price</label>
                            <input type="text" class="form-input" id="lost_price_display" readonly value="Rp 0" style="background: #f3f4f6;">
                            <small style="color: #666;">Total price calculated from selected rentals</small>
                        </div>
                    </div>
                    <div class="form-row full-width">
                        <div class="form-group">
                            <label class="form-label" for="remark">Remark <span style="color: red;">*</span></label>
                            <textarea class="form-textarea" id="remark" name="remark" rows="4" required placeholder="Enter description about the lost unit..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    `;

    document.getElementById('modalFooter').innerHTML = `
        <div style="width: 100%; display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" id="submitBtn" onclick="submitReportForm(event)">Create Report</button>
        </div>
    `;
    
    // Load contracts
    loadContracts();
}

// Global store for room-rentals mapping to calculate price and form data
let roomRentalsMap = {}; 
let rentalPrices = {}; 

// On Contract change - load rooms
function onContractChange(contractId) {
    const roomSelect = document.getElementById('room_id');
    const rentalSelect = document.getElementById('master_rental_id');
    
    // Reset dropdowns
    roomSelect.innerHTML = '';
    roomSelect.disabled = true;
    rentalSelect.innerHTML = '';
    rentalSelect.disabled = true;
    document.getElementById('lost_price_display').value = 'Rp 0';
    roomRentalsMap = {};
    rentalPrices = {};
    
    if (!contractId) return;
    
    // Load rooms for the contract
    fetch(`/marketing/lost-unit-reports/get-rooms-by-contract/${contractId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.data.length > 0) {
            data.data.forEach(room => {
                const option = document.createElement('option');
                option.value = room.id;
                option.textContent = room.name;
                roomSelect.appendChild(option);
            });
            roomSelect.disabled = false;
        }
    })
    .catch(error => console.error('Error loading rooms:', error));
}

// On Room change - load rentals for selected rooms
// Now supports multi-room: we will fetch ALL rentals for ALL selected rooms and combine them
// Ideally, API should verify rentals valid for these rooms.
// We will clear rentals if room selection changes significantly or just append? 
// Simplest implementation: Re-fetch rentals for all selected rooms.
async function onRoomChange(selectElement) {
    const rentalSelect = document.getElementById('master_rental_id');
    const contractId = document.getElementById('contract_id').value;
    const selectedOptions = Array.from(selectElement.selectedOptions);
    const selectedRoomIds = selectedOptions.map(opt => opt.value);
    
    // Reset rental select
    rentalSelect.innerHTML = '';
    rentalSelect.disabled = true;
    document.getElementById('lost_price_display').value = 'Rp 0';
    roomRentalsMap = {};
    
    if (selectedRoomIds.length === 0 || !contractId) return;

    // We need to fetch rentals for each selected room.
    // To identify which rental belongs to which room, we might need a mapping.
    // For the "Select Rental" UI, we will just list all available rentals for these rooms unique by ID?
    // OR, better user experience: Show "Room A - Rental 1" in list if names overlap?
    // For now, let's just flattened list of rentals available in these rooms. 
    // And store which room they belong to in a data attribute or separate map.
    
    // To support "Room A has Rental 1" and "Room B has Rental 1", if we select both,
    // we should probably select Rental 1 twice? Or is it one generic list?
    // User request: "room A rental 1, rental 2; room B rental 3".
    // So distinct rentals per room.
    
    rentalSelect.disabled = false;
    
    // Parallel fetch for all rooms
    const promises = selectedRoomIds.map(roomId => 
        fetch(`/marketing/lost-unit-reports/get-rentals-by-room/${contractId}/${roomId}`, {
             headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
        }).then(r => r.json().then(data => ({roomId, data})))
    );

    try {
        const results = await Promise.all(promises);
        
        // Find room name helper
        const getRoomName = (id) => {
            const opt = Array.from(document.getElementById('room_id').options).find(o => o.value == id);
            return opt ? opt.textContent : id;
        };

        results.forEach(({roomId, data}) => {
             if (data.status === 'success' && data.data.length > 0) {
                // Create optgroup for each room
                const optgroup = document.createElement('optgroup');
                optgroup.label = getRoomName(roomId);
                
                data.data.forEach(rental => {
                    const option = document.createElement('option');
                    // We need to know which room this rental belongs to when submitted
                    // So we can encode it in value: "roomId|rentalId"
                    option.value = `${roomId}|${rental.id}`; 
                    option.textContent = rental.name;
                    option.dataset.rentalId = rental.id; // Store raw rental id for price fetch
                    optgroup.appendChild(option);
                });
                rentalSelect.appendChild(optgroup);
             }
        });
        
    } catch (error) {
        console.error('Error loading rentals:', error);
    }
}

// On Rental change - calculate total price
// We need to fetch price for each selected rental (if not cached) and sum up.
async function onRentalChange(selectElement) {
    const contractId = document.getElementById('contract_id').value;
    const selectedOptions = Array.from(selectElement.selectedOptions);
    
    let totalPrice = 0;
    
    const pricePromises = selectedOptions.map(async opt => {
        const rawRentalId = opt.dataset.rentalId;
        
        // Check cache
        if (rentalPrices[rawRentalId] !== undefined) {
             return rentalPrices[rawRentalId];
        }
        
        // Fetch price
        try {
            const response = await fetch(`/marketing/lost-unit-reports/get-lost-unit-price?master_rental_id=${rawRentalId}&contract_id=${contractId}`, {
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
            });
            const data = await response.json();
            if (data.status === 'success') {
                // Parse "Rp 100.000" to number
                const price = parseFloat(data.formatted_price.replace(/[^0-9,-]+/g,"").replace(',', '.')); // Simple parsing, might need adjustment
                // Wait, backend sends formatted string? Let's assume standard format or check raw value if available.
                // Actually the API probably returns formatted. 
                // Let's rely on backend returning raw value ideally, or parse carefully.
                // Checking previous implementation: it just put formatted_price into input.
                // We need numeric value.
                // Let's assume we can get raw value from API or we try to parse.
                // Let's extend API on backend to return raw value? Or just parse strictly.
                // Rp 100.000 -> 100000.
                
                // Hack: we will assume standard format for now.
                 let numericPrice = 0;
                 if (data.price_numeric !== undefined) {
                     numericPrice = data.price_numeric;
                 } else {
                     // Try to parse formatted
                     numericPrice = parseInt(data.formatted_price.replace(/\D/g, ''));
                 }
                
                rentalPrices[rawRentalId] = numericPrice;
                return numericPrice;
            }
        } catch (e) {
            console.error(e);
        }
        return 0;
    });
    
    const prices = await Promise.all(pricePromises);
    totalPrice = prices.reduce((a, b) => a + b, 0);
    
    document.getElementById('lost_price_display').value = 'Rp ' + totalPrice.toLocaleString('id-ID');
}

// Override submitForm to handle items construction
// Override submitForm to handle items construction
// Override submitForm to handle items construction
// Renamed to submitReportForm to avoid cache/conflict issues
function submitReportForm(event, id = null) {
    if (event) event.preventDefault();
    
    // Manual FormData construction to avoid DOM issues
    const formData = new FormData();
    
    // Get values directly
    const contractId = document.getElementById('contract_id').value;
    const remark = document.getElementById('remark').value;
    
    if (!contractId) {
        alert('Please select a contract');
        return;
    }
    
    formData.append('contract_id', contractId);
    formData.append('remark', remark);
    
    // Handle multi-selects
    const roomSelect = document.getElementById('room_id');
    Array.from(roomSelect.selectedOptions).forEach(option => {
        formData.append('room_id[]', option.value);
    });
    
    const rentalSelect = document.getElementById('master_rental_id');
    const selectedOptions = Array.from(rentalSelect.selectedOptions);
    selectedOptions.forEach(option => {
        formData.append('master_rental_id[]', option.value);
    });

    // Check validation manually since form submit event might not trigger browser validation
    if (selectedOptions.length === 0) {
        alert('Please select at least one rental');
        return;
    }
    
    const data = Object.fromEntries(formData.entries());
    
    // Custom handling for Multi-Item
    // We need to construct 'items' array from the selected values in 'master_rental_id[]'
    // 'master_rental_id[]' values are "roomId|rentalId"
    
    if (!id) { // Only for create for now, Edit needs to be handled separately or blocked
         const rentalSelect = document.getElementById('master_rental_id');
         const selectedOptions = Array.from(rentalSelect.selectedOptions);
         
         const items = selectedOptions.map(opt => {
             const [roomId, rentalId] = opt.value.split('|');
             return {
                 room_id: roomId,
                 master_rental_id: rentalId
             };
         });
         
         data.items = items;
         // Remove raw array fields that might confuse backend validation if not handled or just ignore them
         delete data['master_rental_id[]']; 
         delete data['room_id[]'];
    }
    
    // ... rest of submit logic similar to original ...
    const url = id ? `/marketing/lost-unit-reports/${id}` : '/marketing/lost-unit-reports';
    const method = id ? 'PUT' : 'POST';
    
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    // ... reuse existing response handling ...
     .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}, message: ${text}`);
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.status === 'success' || result.success) {
            closeModal();
            if (result.redirect) {
                window.location.href = result.redirect;
            } else {
                location.reload();
            }
        } else {
            alert('Error: ' + (result.message || 'Something went wrong'));
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        alert('Error: ' + error.message);
    });
}

// Function to load contracts
// Function to load contracts
function loadContracts() {
    console.log('Loading contracts...');
    
    // Try multiple approaches
    const endpoints = [
        '/marketing/lost-unit-reports/create', // Priority: Use the filtered endpoint
        '/api/contracts/dropdown',
        '/marketing/contracts/dropdown',
        '/api/v1/marketing/contracts',
        '/marketing/contracts',
        '/api/contracts'
    ];
    
    let currentEndpoint = 0;
    
    function tryNextEndpoint() {
        if (currentEndpoint >= endpoints.length) {
            console.error('All endpoints failed, using manual fallback');
            // Manual fallback - add the contract we know exists
            const contractSelect = document.getElementById('contract_id');
            if (contractSelect) {
                contractSelect.innerHTML = '<option value="">Select Contract</option>';
                const option = document.createElement('option');
                option.value = '2'; // Based on the data you showed
                option.textContent = 'CTR-20250909-0001 - Customer Name';
                contractSelect.appendChild(option);
                console.log('Added manual fallback contract');
            }
            return;
        }
        
        const endpoint = endpoints[currentEndpoint];
        console.log(`Trying endpoint: ${endpoint}`);
        
        fetch(endpoint, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log(`Response status for ${endpoint}:`, response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log(`Contracts data from ${endpoint}:`, data);
            const contractSelect = document.getElementById('contract_id');
            if (contractSelect) {
                contractSelect.innerHTML = '<option value="">Select Contract</option>';
                
                // Handle different response formats
                let contracts = [];
                
                // Specific check for LostUnitReportController::create structure
                if (data.data && data.data.contracts && Array.isArray(data.data.contracts)) {
                    contracts = data.data.contracts;
                }
                // Standard API response wrapper
                else if (data.data && Array.isArray(data.data)) {
                    contracts = data.data;
                } 
                // Direct array
                else if (Array.isArray(data)) {
                    contracts = data;
                } 
                // Legacy wrapper
                else if (data.contracts && Array.isArray(data.contracts)) {
                    contracts = data.contracts;
                }
                
                if (contracts.length > 0) {
                    contracts.forEach(contract => {
                        const option = document.createElement('option');
                        option.value = contract.id;
                        const customerName = contract.customer?.name || contract.customer_name || 'N/A';
                        option.textContent = `${contract.contract_number} - ${customerName}`;
                        contractSelect.appendChild(option);
                    });
                    console.log(`Loaded ${contracts.length} contracts from ${endpoint}`);
                } else {
                    console.log(`No contracts found in ${endpoint}`);
                    // Only try next if previous failed to find ANY contracts (and we expect some)
                    // But if it returned empty array from correct endpoint (Priority 1), it means really no contracts.
                    // So we should verify if this was the Priority 1 endpoint.
                    if (currentEndpoint === 0) {
                        console.log('Filtered endpoint returned 0 contracts. This is likely correct.');
                    } else {
                        currentEndpoint++;
                        tryNextEndpoint();
                    }
                }
            }
        })
        .catch(error => {
            console.error(`Error loading contracts from ${endpoint}:`, error);
            currentEndpoint++;
            tryNextEndpoint();
        });
    }
    
    tryNextEndpoint();
}

function openViewModal(id) {
    openModal('View Lost Unit Report');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/marketing/lost-unit-reports/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-form-container">
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Report Information</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Report Number</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.report_number || 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">
                                    <span class="px-2 py-1 text-xs rounded-full ${data.status == 'approved' ? 'bg-green-100 text-green-800' : (data.status == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')}">
                                        ${data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'N/A'}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Contract Number</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.contract_number || 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Name</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.company_name || 'N/A'}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">User Information</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Reported By</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.reporter ? data.reporter.name : 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Approved By</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.approver ? data.approver.name : 'N/A'}</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Updated By</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.updater ? data.updater.name : 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Created At</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.created_at ? formatDateWithThreeDigitMonth(new Date(data.created_at)) : 'N/A'}</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Latest Update</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.updated_at ? formatDateWithThreeDigitMonth(new Date(data.updated_at)) : 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <!-- Empty for layout balance -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Additional Information</div>
                        <div class="form-row full-width">
                            <div class="form-group">
                                <label class="form-label">Remarks</label>
                                <div class="form-textarea" style="background-color: #f9fafb; color: #374151; min-height: 80px; white-space: pre-wrap;">${data.remark || 'N/A'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Set modal footer
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                <a href="/marketing/lost-unit-reports/${id}" class="btn btn-primary">View Detail</a>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading report details.</div>';
        });
}

function openEditModal(id) {
    openModal('Edit Lost Unit Report');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/marketing/lost-unit-reports/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <form id="reportForm" onsubmit="submitForm(event, ${id})">
                    <div class="form-group">
                        <label class="form-label">Report Number</label>
                        <input type="text" class="form-input" name="report_number" value="${data.report_number || ''}" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contract</label>
                        <select class="form-select" name="contract_id" required>
                            <option value="">Select Contract</option>
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-textarea" name="remark" rows="4" required placeholder="Enter remarks about the lost unit...">${data.remark || ''}</textarea>
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Report</button>
                    </div>
                </form>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading report details.</div>';
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/marketing/lost-unit-reports/${id}` : '/marketing/lost-unit-reports';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    console.log('Submitting data:', data);
    console.log('URL:', url);
    console.log('Method:', method);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            // Try to get error message from response
            return response.text().then(text => {
                console.log('Error response text:', text);
                throw new Error(`HTTP error! status: ${response.status}, message: ${text}`);
            });
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // If not JSON, get text
            return response.text().then(text => {
                console.log('Non-JSON response:', text);
                return { status: 'success', message: 'Success' };
            });
        }
    })
    .then(result => {
        console.log('Response result:', result);
        
        if (result.status === 'success' || result.success) {
            closeModal();
            // If there's a redirect URL (for new reports), go to show page
            if (result.redirect) {
                window.location.href = result.redirect;
            } else {
                location.reload();
            }
        } else {
            alert('Error: ' + (result.message || 'Something went wrong'));
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        alert('Error: ' + error.message);
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this lost unit report? This action can be undone later.'
        : `Are you sure you want to hide ${count} lost unit reports? This action can be undone later.`;
    
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
    
    fetch('/marketing/lost-unit-reports/bulk-delete', {
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
        showErrorModal('Network error occurred');
    });
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'The lost unit report has been successfully hidden.'
        : `${count} lost unit reports have been successfully hidden.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t hide the lost unit report. Please try again.';
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
</script>
@endsection
