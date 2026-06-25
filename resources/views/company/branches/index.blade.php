@extends('layouts.app')

@section('title', 'Master Branch')
@section('breadcrumb', 'Home / Company / Master Branch')

@section('content')
<style>
    .table-row-hover:hover {
        background-color: #eff6ff !important; /* Light blue background */
        transition: background-color 0.2s ease;
    }
    
    .delete-btn {
        background-color: #f3f4f6 !important; /* Light gray background */
        color: #6b7280 !important; /* Dark gray text */
        border: 1px solid #d1d5db !important; /* Light gray border */
        padding: 8px 16px !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        transition: background-color 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        outline: none !important;
        box-shadow: none !important;
    }
    
    .delete-btn:hover {
        background-color: #e5e7eb !important; /* Slightly darker gray on hover */
        color: #4b5563 !important;
    }
    
    .delete-btn:focus {
        background-color: #e5e7eb !important;
        color: #4b5563 !important;
        outline: none !important;
    }
    
    .delete-btn i {
        color: #6b7280 !important; /* Dark gray icon */
    }
    
    .delete-btn span {
        color: #6b7280 !important; /* Dark gray text */
    }
    
    .add-btn {
        background-color: #214589 !important;
        color: white !important;
        border: none !important;
        padding: 8px 16px !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        transition: background-color 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        outline: none !important;
        box-shadow: none !important;
    }
    
    .add-btn:hover {
        background-color: #1e3a8a !important;
        color: white !important;
    }
    
    .add-btn:focus {
        background-color: #1e3a8a !important;
        color: white !important;
        outline: none !important;
    }
    
    .add-btn i {
        color: white !important;
    }
    
    .add-btn span {
        color: white !important;
    }
    
    /* Action Buttons */
    .btn-sm {
        padding: 6px 12px !important;
        font-size: 12px !important;
        border-radius: 4px !important;
        border: none !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        text-decoration: none !important;
        outline: none !important;
        box-shadow: none !important;
    }
    
    .btn-sm.btn-info {
        background-color: #0ea5e9 !important;
        color: white !important;
    }
    
    .btn-sm.btn-info:hover {
        background-color: #0284c7 !important;
        color: white !important;
    }
    
    .btn-sm.btn-warning {
        background-color: #f59e0b !important;
        color: white !important;
    }
    
    .btn-sm.btn-warning:hover {
        background-color: #d97706 !important;
        color: white !important;
    }
    
    .btn-sm.btn-primary {
        background-color: #214589 !important;
        color: white !important;
    }
    
    .btn-sm.btn-primary:hover {
        background-color: #1e3a8a !important;
        color: white !important;
    }
    
    .flex {
        display: flex !important;
    }
    
    .gap-2 {
        gap: 8px !important;
    }
    
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
        min-width: 1800px;
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
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(11), .responsive-table td:nth-child(11) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(12), .responsive-table td:nth-child(12) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(13), .responsive-table td:nth-child(13) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(14), .responsive-table td:nth-child(14) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(15), .responsive-table td:nth-child(15) { width: 120px; min-width: 120px; }

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
        margin-top: 2rem;
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
        margin-bottom: 30px;
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
        
        <!-- Master Branch Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Branch</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-3">
                <a href="{{ route('company.branches.user-assignments') }}" class="btn" style="background-color: #6366f1; color: white; padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-users-cog"></i>
                    <span class="hidden md:inline">Multi-Branch Users</span>
                    <span class="md:hidden">Multi Branch</span>
                </a>
                <button id="bulkDeleteBtn" class="btn btn-danger" onclick="openDeleteModal()" style="display: none;">
                    <i class="fas fa-trash"></i>
                    <span class="hidden md:inline">Hide Selected</span>
                    <span class="md:hidden">Hide</span>
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Create Branch</span>
                    <span class="md:hidden">Create</span>
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
            <table class="responsive-table" id="branchesTable">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="code">Branch Code</th>
                        <th data-column="name">Branch Name</th>
                        <!-- Company column hidden - default to PT Pink Services Indonesia -->
                        <th data-column="address_type">Address Type</th>
                        <th data-column="province__name">Province</th>
                        <th data-column="city__name">City</th>
                        <th data-column="phone_1|phone_2">Phone</th>
                        <th data-no-filter>Employee Count</th>
                        <th data-column="has_warehouse">Has Warehouse</th>
                        <th data-column="is_taxable">Wajib Pungut</th>
                        <th data-no-filter>Penanda Tangan Invoice</th>
                        <th data-column="is_active">Status</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                        <th data-no-filter>Actions</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($branches ?? [] as $branch)
                    <tr data-id="{{ $branch->id }}" onclick="openViewModal({{ $branch->id }})">
                        <td class="text-center">
                            <input type="checkbox" name="selected_items" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $branch->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $branch->code ?? '-' }}</td>
                        <td>{{ $branch->name ?? '-' }}</td>
                        <!-- Company column hidden - default to PT Pink Services Indonesia -->
                        <td>
                            @php
                                $addressTypeColors = [
                                    'office' => 'bg-blue-100 text-blue-800',
                                    'warehouse' => 'bg-yellow-100 text-yellow-800',
                                    'both' => 'bg-green-100 text-green-800'
                                ];
                                $addressTypeColor = $addressTypeColors[$branch->address_type] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full {{ $addressTypeColor }}">
                                {{ ucfirst($branch->address_type ?? '-') }}
                            </span>
                        </td>
                        <td>{{ $branch->province->name ?? '-' }}</td>
                        <td>{{ $branch->city->name ?? '-' }}</td>
                        <td>{{ $branch->phone_1 ?? '-' }}</td>
                        <td>{{ $branch->users_count ?? 0 }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $branch->has_warehouse ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $branch->has_warehouse ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $branch->is_taxable ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $branch->is_taxable ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td>{{ $branch->invoiceAuthorizedByUser->name ?? 'Default: Manager Finance' }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $branch->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $branch->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $branch->createdBy->name ?? '-' }}</td>
                        <td>
                            @if($branch->created_at)
                                @php
                                    $createdDate = \Carbon\Carbon::parse($branch->created_at);
                                @endphp
                                {{ $createdDate->format('d/M/Y') }}<br>
                                at {{ $createdDate->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $branch->updatedBy->name ?? '-' }}</td>
                        <td>
                            @if($branch->updated_at)
                                @php
                                    $date = \Carbon\Carbon::parse($branch->updated_at);
                                @endphp
                                {{ $date->format('d/M/Y') }}<br>
                                at {{ $date->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <button class="btn btn-sm btn-info" onclick="event.stopPropagation(); window.location.href='{{ route('company.branches.operational-areas', $branch->id) }}'" title="Operational Areas">
                                    <i class="fas fa-map-marked-alt"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); window.location.href='{{ route('company.branches.pics', $branch->id) }}'" title="Branch PICs">
                                    <i class="fas fa-users"></i> PICs
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); openViewModal({{ $branch->id }})" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="16" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No branches found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($branches->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $branches->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Branch</h2>
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
        <h3 class="delete-modal-title">Hide Branch</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this branch? This action can be undone later.</p>
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
        <p class="delete-modal-description" id="errorMessage">We couldn't hide the branch. Please try again.</p>
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
        <p class="delete-modal-description" id="successMessage">The branch has been successfully hidden.</p>
    </div>
</div>

<script>
const invoiceSignatoryUsers = @json($invoiceSignatoryUsers ?? []);

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderInvoiceAuthorizedByOptions(selectedId = null) {
    const normalizedSelectedId = selectedId ? String(selectedId) : '';
    let options = '<option value="">Default: Manager Finance</option>';

    invoiceSignatoryUsers.forEach(user => {
        const selected = String(user.id) === normalizedSelectedId ? 'selected' : '';
        const position = user.position_name ? ` - ${user.position_name}` : '';
        const email = user.email ? ` (${user.email})` : '';
        options += `<option value="${user.id}" ${selected}>${escapeHtml(user.name)}${escapeHtml(position)}${escapeHtml(email)}</option>`;
    });

    return options;
}

// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkDeleteButton();
});

document.getElementById('headerSelectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    document.getElementById('selectAll').checked = this.checked;
    updateBulkDeleteButton();
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
        
        updateBulkDeleteButton();
    }
});

// Update bulk delete button visibility
function updateBulkDeleteButton() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    if (checkboxes.length > 0) {
        bulkDeleteBtn.style.display = 'inline-flex';
        bulkDeleteBtn.innerHTML = `
            <i class="fas fa-trash"></i>
            <span class="hidden md:inline">Hide Selected (${checkboxes.length})</span>
            <span class="md:hidden">Hide (${checkboxes.length})</span>
        `;
    } else {
        bulkDeleteBtn.style.display = 'none';
    }
}

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one item to delete');
        return;
    }
    
    // Use the existing bulk delete modal system
    openDeleteModal();
}

// Modal Functions
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Branch';
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="space-y-6">
                <div class="modal-section">
                    <h3 class="modal-section-title">Basic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Company field hidden - default to PT Pink Services Indonesia (ID: 7) -->
                        <input type="hidden" name="company_id" value="7">
                        <div class="form-group">
                            <label class="form-label">Branch Code *</label>
                            <input type="text" name="code" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Branch Name *</label>
                            <input type="text" name="name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address Type *</label>
                            <select name="address_type" class="form-input" required>
                                <option value="">Select Address Type</option>
                                <option value="office">Office</option>
                                <option value="warehouse">Warehouse</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Penanda Tangan Invoice</label>
                            <select name="invoice_authorized_by_user_id" class="form-input">
                                ${renderInvoiceAuthorizedByOptions()}
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Kosongkan untuk memakai default: Manager Finance.</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h3 class="modal-section-title">Address Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Address 1 *</label>
                            <input type="text" name="address_1" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address 2</label>
                            <input type="text" name="address_2" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Province *</label>
                            <select name="province_id" class="form-input" required onchange="loadCities(this.value)">
                                <option value="">Select Province</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">City *</label>
                            <select name="city_id" class="form-input" required onchange="loadDistricts(this.value)">
                                <option value="">Select City</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">District</label>
                            <select name="district_id" class="form-input" onchange="loadSubdistricts(this.value); clearPostalCode();">
                                <option value="">Select District</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subdistrict</label>
                            <select name="subdistrict_id" class="form-input" onchange="loadPostalCode(this.value)">
                                <option value="">Select Subdistrict</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" class="form-input">
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h3 class="modal-section-title">Contact Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Phone 1 *</label>
                            <input type="text" name="phone_1" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone 2</label>
                            <input type="text" name="phone_2" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fax</label>
                            <input type="text" name="fax" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="modal-section">
                    <h3 class="modal-section-title">Settings</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="has_warehouse" value="1"> Has Warehouse
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="is_taxable" value="1"> Is Taxable
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="is_active" value="1" checked> Is Active
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" form="form" class="btn btn-primary">Create Branch</button>
    `;
    
    document.getElementById('modalOverlay').classList.add('show');
    
    // Load companies and provinces
    loadCompaniesAndProvinces();
}

function openViewModal(id) {
    fetch(`/company/branches/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const branch = data.data;
                document.getElementById('modalTitle').textContent = 'View Branch';
                document.getElementById('modalBody').innerHTML = `
                    <div class="space-y-6">
                        <div class="modal-section">
                            <h3 class="modal-section-title">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Company field hidden - default to PT Pink Services Indonesia -->
                                <div class="detail-item">
                                    <label class="form-label">Branch Code</label>
                                    <div class="detail-value">${branch.code || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Branch Name</label>
                                    <div class="detail-value">${branch.name || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Address Type</label>
                                    <div class="detail-value">${branch.address_type ? branch.address_type.charAt(0).toUpperCase() + branch.address_type.slice(1) : '-'}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-section">
                            <h3 class="modal-section-title">Address Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="detail-item">
                                    <label class="form-label">Address 1</label>
                                    <div class="detail-value">${branch.address_1 || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Address 2</label>
                                    <div class="detail-value">${branch.address_2 || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Province</label>
                                    <div class="detail-value">${branch.province?.name || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">City</label>
                                    <div class="detail-value">${branch.city?.name || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">District</label>
                                    <div class="detail-value">${branch.district?.name || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Subdistrict</label>
                                    <div class="detail-value">${branch.subdistrict?.name || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Postal Code</label>
                                    <div class="detail-value">${branch.postal_code || '-'}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-section">
                            <h3 class="modal-section-title">Contact Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="detail-item">
                                    <label class="form-label">Phone 1</label>
                                    <div class="detail-value">${branch.phone_1 || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Phone 2</label>
                                    <div class="detail-value">${branch.phone_2 || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Fax</label>
                                    <div class="detail-value">${branch.fax || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Email</label>
                                    <div class="detail-value">${branch.email || '-'}</div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-section">
                            <h3 class="modal-section-title">Settings</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="detail-item">
                                    <label class="form-label">Has Warehouse</label>
                                    <div class="detail-value">${branch.has_warehouse ? 'Yes' : 'No'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Is Taxable</label>
                                    <div class="detail-value">${branch.is_taxable ? 'Yes' : 'No'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Penanda Tangan Invoice</label>
                                    <div class="detail-value">${branch.invoice_authorized_by_user?.name || 'Default: Manager Finance'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Is Active</label>
                                    <div class="detail-value">${branch.is_active ? 'Yes' : 'No'}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-section">
                            <h3 class="modal-section-title">Branch PICs</h3>
                            <div class="space-y-3">
                                ${branch.pics && branch.pics.length > 0 ? 
                                    branch.pics.map(pic => `
                                        <div class="bg-gray-50 p-4 rounded-lg border">
                                            <div class="flex justify-between items-start mb-3">
                                                <div class="flex-1">
                                                    <div class="font-semibold text-lg text-gray-900 mb-2">${pic.user?.name || '-'}</div>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Position:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${pic.position || '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Department:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${pic.department || '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Phone:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${pic.phone || '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Email:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${pic.email || '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Assigned:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${pic.assigned_date ? new Date(pic.assigned_date).toLocaleDateString('en-GB') : '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Assigned By:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${pic.assigned_by_user?.name || '-'}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col items-end space-y-2">
                                                    ${pic.is_primary ? '<span class="bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full font-medium">Primary PIC</span>' : ''}
                                                    <span class="text-xs px-3 py-1 rounded-full font-medium ${pic.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                                        ${pic.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('') : 
                                    '<div class="text-gray-500 text-center py-4">No PICs assigned</div>'
                                }
                            </div>
                        </div>
                        
                        <div class="modal-section" style="margin-top: 2rem;">
                            <h3 class="modal-section-title">Operational Areas</h3>
                            <div class="space-y-3">
                                ${branch.operational_areas && branch.operational_areas.length > 0 ? 
                                    branch.operational_areas.map(area => `
                                        <div class="bg-gray-50 p-4 rounded-lg border">
                                            <div class="flex justify-between items-start mb-3">
                                                <div class="flex-1">
                                                    <div class="font-semibold text-lg text-gray-900 mb-2">${area.name || '-'}</div>
                                                    <div class="text-sm text-gray-600 mb-3">${area.description || '-'}</div>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Type:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${area.area_type ? area.area_type.charAt(0).toUpperCase() + area.area_type.slice(1) : '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Province:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${area.province || '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">City:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${area.city || '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">District:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${area.district || '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Subdistrict:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${area.subdistrict || '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Postal Code:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${area.postal_code || '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Radius:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${area.radius_km ? area.radius_km + ' km' : '-'}</span>
                                                        </div>
                                                        <div class="flex items-center">
                                                            <span class="text-sm font-medium text-gray-600 w-20">Coordinates:</span>
                                                            <span class="text-sm text-gray-900 ml-2">${area.latitude && area.longitude ? area.latitude + ', ' + area.longitude : '-'}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col items-end space-y-2">
                                                    <span class="text-xs px-3 py-1 rounded-full font-medium ${area.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                                        ${area.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('') : 
                                    '<div class="text-gray-500 text-center py-4">No operational areas defined</div>'
                                }
                            </div>
                        </div>
                        
                        <div class="modal-section" style="margin-top: 2rem;">
                            <h3 class="modal-section-title">Employee Count</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="detail-item">
                                    <label class="form-label">Total Employees</label>
                                    <div class="detail-value">${branch.users_count || 0} employees</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Active Employees</label>
                                    <div class="detail-value">${branch.active_users_count || 0} employees</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-section" style="margin-top: 2rem;">
                            <h3 class="modal-section-title">Audit Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="detail-item">
                                    <label class="form-label">Created By</label>
                                    <div class="detail-value">${branch.created_by?.name || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Updated By</label>
                                    <div class="detail-value">${branch.updated_by?.name || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Created At</label>
                                    <div class="detail-value">${branch.created_at ? new Date(branch.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Updated At</label>
                                    <div class="detail-value">${branch.updated_at ? new Date(branch.updated_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('modalFooter').innerHTML = `
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                    <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                `;
                
                document.getElementById('modalOverlay').classList.add('show');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load branch data');
        });
}

function openEditModal(id) {
    fetch(`/company/branches/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const branch = data.data;
                document.getElementById('modalTitle').textContent = 'Edit Branch';
                document.getElementById('modalBody').innerHTML = `
                    <form id="form" onsubmit="submitForm(event, ${id})">
                        <div class="space-y-6">
                            <div class="modal-section">
                                <h3 class="modal-section-title">Basic Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Company field hidden - default to PT Pink Services Indonesia (ID: 7) -->
                                    <input type="hidden" name="company_id" value="7">
                                    <div class="form-group">
                                        <label class="form-label">Branch Code *</label>
                                        <input type="text" name="code" class="form-input" value="${branch.code || ''}" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Branch Name *</label>
                                        <input type="text" name="name" class="form-input" value="${branch.name || ''}" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Address Type *</label>
                                        <select name="address_type" class="form-input" required>
                                            <option value="">Select Address Type</option>
                                            <option value="office" ${branch.address_type === 'office' ? 'selected' : ''}>Office</option>
                                            <option value="warehouse" ${branch.address_type === 'warehouse' ? 'selected' : ''}>Warehouse</option>
                                            <option value="both" ${branch.address_type === 'both' ? 'selected' : ''}>Both</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Penanda Tangan Invoice</label>
                                        <select name="invoice_authorized_by_user_id" class="form-input">
                                            ${renderInvoiceAuthorizedByOptions(branch.invoice_authorized_by_user_id)}
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">Kosongkan untuk memakai default: Manager Finance.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal-section">
                                <h3 class="modal-section-title">Address Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-group">
                                        <label class="form-label">Address 1 *</label>
                                        <input type="text" name="address_1" class="form-input" value="${branch.address_1 || ''}" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Address 2</label>
                                        <input type="text" name="address_2" class="form-input" value="${branch.address_2 || ''}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Province *</label>
                                        <select name="province_id" class="form-input" required onchange="loadCities(this.value)">
                                            <option value="">Select Province</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">City *</label>
                                        <select name="city_id" class="form-input" required onchange="loadDistricts(this.value)">
                                            <option value="">Select City</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">District</label>
                                        <select name="district_id" class="form-input" onchange="loadSubdistricts(this.value); clearPostalCode();">
                                            <option value="">Select District</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Subdistrict</label>
                                        <select name="subdistrict_id" class="form-input" onchange="loadPostalCode(this.value)">
                                            <option value="">Select Subdistrict</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Postal Code</label>
                                        <input type="text" name="postal_code" class="form-input" value="${branch.postal_code || ''}">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal-section">
                                <h3 class="modal-section-title">Contact Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-group">
                                        <label class="form-label">Phone 1 *</label>
                                        <input type="text" name="phone_1" class="form-input" value="${branch.phone_1 || ''}" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Phone 2</label>
                                        <input type="text" name="phone_2" class="form-input" value="${branch.phone_2 || ''}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Fax</label>
                                        <input type="text" name="fax" class="form-input" value="${branch.fax || ''}">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-input" value="${branch.email || ''}">
                                    </div>
                                </div>
                            </div>

                            <div class="modal-section">
                                <h3 class="modal-section-title">Settings</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="form-group">
                                        <label class="form-label">
                                            <input type="checkbox" name="has_warehouse" value="1" ${branch.has_warehouse ? 'checked' : ''}> Has Warehouse
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">
                                            <input type="checkbox" name="is_taxable" value="1" ${branch.is_taxable ? 'checked' : ''}> Is Taxable
                                        </label>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">
                                            <input type="checkbox" name="is_active" value="1" ${branch.is_active ? 'checked' : ''}> Is Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                `;
                
                document.getElementById('modalFooter').innerHTML = `
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" form="form" class="btn btn-primary">Update Branch</button>
                `;
                
                document.getElementById('modalOverlay').classList.add('show');
                
                // Load companies and provinces with selected values
                loadCompaniesAndProvinces(branch.company_id, branch.province_id, branch.city_id, branch.district_id, branch.subdistrict_id);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load branch data');
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const editBranchId = params.get('edit_branch');

    if (editBranchId) {
        openEditModal(editBranchId);
    }
});

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    // Convert checkboxes to string '1' or '0' (don't convert to boolean)
    // If checkbox is unchecked, FormData won't include it, so we default to '0'
    // The controller will handle the conversion
    if (data.has_warehouse === '1' || data.has_warehouse === 1 || data.has_warehouse === true) {
        data.has_warehouse = '1';
    } else {
        data.has_warehouse = '0';
    }
    
    if (data.is_taxable === '1' || data.is_taxable === 1 || data.is_taxable === true) {
        data.is_taxable = '1';
    } else {
        data.is_taxable = '0';
    }
    
    if (data.is_active === '1' || data.is_active === 1 || data.is_active === true) {
        data.is_active = '1';
    } else {
        data.is_active = '0';
    }
    
    const url = id ? `/company/branches/${id}` : '/company/branches';
    const method = id ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Something went wrong');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            location.reload();
        } else {
            // Show detailed error message
            let errorMsg = data.message || 'Something went wrong';
            if (data.errors) {
                const errorList = Object.values(data.errors).flat().join('\n');
                errorMsg = errorMsg + '\n\n' + errorList;
            }
            Swal.fire({
                icon: 'error',
                title: 'Validasi Gagal',
                text: errorMsg,
                confirmButtonColor: '#214589'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Failed to save branch. Please check the console for details.',
            confirmButtonColor: '#214589'
        });
    });
}

// Load provinces dynamically (company is defaulted to PT Pink Services Indonesia)
function loadCompaniesAndProvinces(selectedCompanyId = null, selectedProvinceId = null, selectedCityId = null, selectedDistrictId = null, selectedSubdistrictId = null) {
    // Company is defaulted to PT Pink Services Indonesia (ID: 7) - no need to load companies
    
    // Load provinces
    fetch('/api/v1/location/provinces')
        .then(response => response.json())
        .then(data => {
            const provinceSelect = document.querySelector('select[name="province_id"]');
            if (provinceSelect) {
                provinceSelect.innerHTML = '<option value="">Select Province</option>';
                // Handle both direct array response and wrapped data response
                const provinces = Array.isArray(data) ? data : (data.data || []);
                if (provinces.length > 0) {
                    provinces.forEach(province => {
                        const option = document.createElement('option');
                        option.value = province.id;
                        option.textContent = province.name;
                        if (selectedProvinceId && province.id == selectedProvinceId) {
                            option.selected = true;
                            loadCities(province.id, selectedCityId, selectedDistrictId, selectedSubdistrictId);
                        }
                        provinceSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading provinces:', error);
            const provinceSelect = document.querySelector('select[name="province_id"]');
            if (provinceSelect) {
                provinceSelect.innerHTML = '<option value="">Select Province</option>';
            }
        });
}

function loadCities(provinceId, selectedCityId = null, selectedDistrictId = null, selectedSubdistrictId = null) {
    if (!provinceId) return;
    
    fetch(`/api/cities/${provinceId}`)
        .then(response => response.json())
        .then(data => {
            const citySelect = document.querySelector('select[name="city_id"]');
            if (citySelect) {
                citySelect.innerHTML = '<option value="">Select City</option>';
                if (data && data.length > 0) {
                    data.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.name;
                        if (selectedCityId && city.id == selectedCityId) {
                            option.selected = true;
                            loadDistricts(city.id, selectedDistrictId, selectedSubdistrictId);
                        }
                        citySelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading cities:', error);
            const citySelect = document.querySelector('select[name="city_id"]');
            if (citySelect) {
                citySelect.innerHTML = '<option value="">Select City</option>';
            }
        });
}

function loadDistricts(cityId, selectedDistrictId = null, selectedSubdistrictId = null) {
    if (!cityId) return;
    
    fetch(`/api/districts/${cityId}`)
        .then(response => response.json())
        .then(data => {
            const districtSelect = document.querySelector('select[name="district_id"]');
            if (districtSelect) {
                districtSelect.innerHTML = '<option value="">Select District</option>';
                if (data && data.length > 0) {
                    data.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district.id;
                        option.textContent = district.name;
                        if (selectedDistrictId && district.id == selectedDistrictId) {
                            option.selected = true;
                            loadSubdistricts(district.id, selectedSubdistrictId);
                        }
                        districtSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading districts:', error);
            const districtSelect = document.querySelector('select[name="district_id"]');
            if (districtSelect) {
                districtSelect.innerHTML = '<option value="">Select District</option>';
            }
        });
}

function loadSubdistricts(districtId, selectedSubdistrictId = null) {
    if (!districtId) return;
    
    fetch(`/api/subdistricts/${districtId}`)
        .then(response => response.json())
        .then(data => {
            const subdistrictSelect = document.querySelector('select[name="subdistrict_id"]');
            if (subdistrictSelect) {
                subdistrictSelect.innerHTML = '<option value="">Select Subdistrict</option>';
                if (data && data.length > 0) {
                    data.forEach(subdistrict => {
                        const option = document.createElement('option');
                        option.value = subdistrict.id;
                        option.textContent = subdistrict.name;
                        if (selectedSubdistrictId && subdistrict.id == selectedSubdistrictId) {
                            option.selected = true;
                            // Auto-fill postal code when subdistrict is pre-selected
                            loadPostalCode(subdistrict.id);
                        }
                        subdistrictSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading subdistricts:', error);
            const subdistrictSelect = document.querySelector('select[name="subdistrict_id"]');
            if (subdistrictSelect) {
                subdistrictSelect.innerHTML = '<option value="">Select Subdistrict</option>';
            }
        });
}

function loadPostalCode(subdistrictId) {
    if (!subdistrictId) {
        // Clear postal code if no subdistrict selected
        clearPostalCode();
        return;
    }
    
    // Get the district_id from the current district selection
    const districtSelect = document.querySelector('select[name="district_id"]');
    if (!districtSelect || !districtSelect.value) {
        return;
    }
    
    fetch(`/api/subdistricts/${subdistrictId}/postal-code`)
        .then(response => response.json())
        .then(data => {
            if (data.postal_code) {
                const postalCodeInput = document.querySelector('input[name="postal_code"]');
                if (postalCodeInput) {
                    postalCodeInput.value = data.postal_code;
                }
            }
        })
        .catch(error => {
            console.error('Error loading postal code:', error);
        });
}

function clearPostalCode() {
    const postalCodeInput = document.querySelector('input[name="postal_code"]');
    if (postalCodeInput) {
        postalCodeInput.value = '';
    }
}

// Delete modal functions
function openDeleteModal(id) {
    if (id) {
        // Single delete
        window.currentDeleteId = id;
        window.selectedIdsForRetry = [id];
    } else {
        // Bulk delete
        const selectedCheckboxes = document.querySelectorAll('input[name="selected_items"]:checked');
        const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
        
        if (selectedIds.length === 0) {
            alert('Please select at least one branch to hide.');
            return;
        }
        
        window.currentDeleteId = null;
        window.selectedIdsForRetry = selectedIds;
    }
    
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    window.currentDeleteId = null;
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
}

function retryDelete() {
    closeErrorModal();
    if (window.currentDeleteId) {
        confirmDelete();
    }
}

function confirmDelete() {
    closeDeleteModal();
    
    if (window.currentDeleteId) {
        // Single delete
        fetch(`/company/branches/${window.currentDeleteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccessModal(1);
            } else {
                showErrorModal(data.message || 'We couldn\'t hide the branch. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Network error occurred');
        });
    } else if (window.selectedIdsForRetry && window.selectedIdsForRetry.length > 0) {
        // Bulk delete
        fetch('/company/branches/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ ids: window.selectedIdsForRetry })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessModal(data.count);
            } else {
                showErrorModal(data.message || 'We couldn\'t hide the branches. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Network error occurred');
        });
    }
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'The branch has been successfully hidden.'
        : `${count} branches have been successfully hidden.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t hide the branch. Please try again.';
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Global variables
let successModalTimer = null;

// Event listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
        closeErrorModal();
        closeSuccessModal();
    }
});

// Ensure rows are clickable
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.style.cursor = 'pointer';
    });
});
</script>
@endsection
