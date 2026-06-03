@extends('layouts.app')

@section('title', 'Job Reports')
@section('breadcrumb', 'Home / Operational / Job Reports')

@section('content')
<!-- Signature Pad Library -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
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
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(11), .responsive-table td:nth-child(11) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(12), .responsive-table td:nth-child(12) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(13), .responsive-table td:nth-child(13) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(14), .responsive-table td:nth-child(14) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(15), .responsive-table td:nth-child(15) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(16), .responsive-table td:nth-child(16) { width: 150px; min-width: 150px; }

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
        background: #fef2f2;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    .error-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #dc2626;
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
        color: #dc2626;
        border: 2px solid #dc2626;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-error-close:hover {
        background-color: #fef2f2;
        border-color: #b91c1c;
        color: #b91c1c;
    }

    .btn-error-retry {
        background-color: #dc2626;
        color: white;
        border: 2px solid #dc2626;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-error-retry:hover {
        background-color: #b91c1c;
        border-color: #b91c1c;
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

    .success-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #16a34a;
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
        background-color: #f9fafb;
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
        border-bottom: 1px solid #e5e7eb;
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
    
    .status-completed {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-in-progress {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .status-good {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-fair {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-poor {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .status-full {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-half {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-empty {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .status-not_applicable {
        background-color: #f3f4f6;
        color: #6b7280;
    }
    
    /* Job Type Badge Styles */
    .job-type-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .job-type-install {
        background-color: #e0e7ff;
        color: #3730a3;
    }
    
    .job-type-service {
        background-color: #f0fdf4;
        color: #166534;
    }
    
    .job-type-maintenance {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .job-type-remove {
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

        .modal-container {
            width: 95vw;
            max-height: 95vh;
        }
        
        .modal-header {
            padding: 15px;
        }
        
        .modal-body {
            padding: 15px;
            max-height: calc(95vh - 120px);
        }
        
        .modal-footer {
            padding: 15px;
            flex-direction: column;
        }
        
        .modal-footer .btn {
            width: 100%;
            justify-content: center;
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
        
        <!-- Job Reports Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Job Reports</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Report</span>
                    <span class="md:hidden">Add New</span>
            </button>
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
                        <th data-column="jobSchedule.job_number">Job Schedule</th>
                        <th data-column="technician.name">Technician</th>
                        <th data-column="job_type">Job Type</th>
                        <th data-column="temperature" data-type="numeric">Temperature</th>
                        <th data-column="condition">Condition</th>
                        <th data-column="refill_status">Refill Status</th>
                        <th data-column="photos">Photos</th>
                        <th data-column="device_info">Device Info</th>
                        <th data-column="device_status">Device Status</th>
                        <th data-column="location">Location</th>
                        <th data-column="qr_scan">QR Scan</th>
                        <th data-column="status">Status</th>
                        <th data-column="completed_at" data-type="date">Completed At</th>
                        <th data-column="notes">Notes</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($jobReports as $report)
                    <tr data-id="{{ $report->id }}" onclick="openViewModal({{ $report->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $report->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $report->jobSchedule->job_number ?? '-' }}</td>
                        <td>{{ $report->technician->name ?? '-' }}</td>
                        <td>
                            @if($report->job_type)
                            <span class="job-type-badge job-type-{{ $report->job_type }}">
                                {{ ucfirst($report->job_type) }}
                            </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $report->temperature ? $report->temperature . '°C' : '-' }}</td>
                        <td>
                            @if($report->condition)
                                <span class="status-badge status-{{ $report->condition }}">
                                    {{ ucfirst($report->condition) }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($report->refill_status)
                                <span class="status-badge status-{{ $report->refill_status }}">
                                    {{ ucfirst(str_replace('_', ' ', $report->refill_status)) }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-col gap-1">
                                @if($report->photo_before)
                                    <span class="text-xs text-green-600"><i class="fas fa-check"></i> Before</span>
                                @else
                                    <span class="text-xs text-red-600"><i class="fas fa-times"></i> Before</span>
                                @endif
                                @if($report->photo_after)
                                    <span class="text-xs text-green-600"><i class="fas fa-check"></i> After</span>
                                @else
                                    <span class="text-xs text-red-600"><i class="fas fa-times"></i> After</span>
                                @endif
                                @if($report->photo_pic)
                                    <span class="text-xs text-green-600"><i class="fas fa-check"></i> PIC</span>
                                @else
                                    <span class="text-xs text-red-600"><i class="fas fa-times"></i> PIC</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col gap-1">
                                @if($report->unit_mac_address)
                                    <span class="text-xs font-medium">MAC: {{ $report->unit_mac_address }}</span>
                                @else
                                    <span class="text-xs text-gray-400">No MAC</span>
                                @endif
                                @if($report->unit_serial_number)
                                    <span class="text-xs font-medium">SN: {{ $report->unit_serial_number }}</span>
                                @else
                                    <span class="text-xs text-gray-400">No Serial</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col gap-1">
                                @if($report->device_online_status !== null)
                                    <span class="text-xs {{ $report->device_online_status ? 'text-green-600' : 'text-red-600' }}">
                                        <i class="fas {{ $report->device_online_status ? 'fa-wifi' : 'fa-wifi-slash' }}"></i>
                                        {{ $report->device_online_status ? 'Online' : 'Offline' }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">Unknown</span>
                                @endif
                                @if($report->device_liquid_level !== null)
                                    <span class="text-xs {{ $report->device_liquid_level > 2 ? 'text-green-600' : ($report->device_liquid_level > 0 ? 'text-yellow-600' : 'text-red-600') }}">
                                        <i class="fas fa-tint"></i>
                                        @if($report->device_liquid_level == 0)
                                            Empty
                                        @elseif($report->device_liquid_level == 1)
                                            >25%
                                        @elseif($report->device_liquid_level == 2)
                                            >10%
                                        @elseif($report->device_liquid_level == 3)
                                            ≤10%
                                        @else
                                            {{ $report->device_liquid_level }}%
                                        @endif
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">No Data</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($report->latitude && $report->longitude)
                                <button class="btn btn-sm btn-secondary" onclick="openGoogleMaps({{ $report->latitude }}, {{ $report->longitude }}, '{{ $report->location_address ?? 'Job Location' }}')">
                                    <i class="fas fa-map-marker-alt"></i>
                                    View Map
                                </button>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-col gap-1">
                                @if($report->qr_scan_at)
                                    <span class="text-xs text-green-600">
                                        <i class="fas fa-qrcode"></i>
                                        {{ $report->qr_scan_at->format('H:i') }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ $report->qr_scan_at->format('M d') }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">Not Scanned</span>
                                @endif
                                @if($report->material_qr_codes && count($report->material_qr_codes) > 0)
                                    <span class="text-xs text-blue-600">
                                        <i class="fas fa-boxes"></i>
                                        {{ count($report->material_qr_codes) }} items
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="status-badge {{ $report->completed_at ? 'status-completed' : 'status-pending' }}">
                                {{ $report->completed_at ? 'Completed' : 'Pending' }}
                            </span>
                        </td>
                        <td>
                            @if($report->completed_at)
                                {{ \Carbon\Carbon::parse($report->completed_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($report->completed_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $report->notes ? Str::limit($report->notes, 50) : '-' }}</td>
                        <td>
                            @if($report->created_at)
                                {{ \Carbon\Carbon::parse($report->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($report->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($report->updated_at)
                                {{ \Carbon\Carbon::parse($report->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($report->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="16" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No job reports found</p>
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
                @if(isset($jobReports) && $jobReports->currentPage() > 1)
                    <a href="{{ $jobReports->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                <!-- Page Numbers -->
                @if(isset($jobReports) && $jobReports->hasPages())
                    @php
                        $start = max(1, $jobReports->currentPage() - 2);
                        $end = min($jobReports->lastPage(), $jobReports->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $jobReports->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $jobReports->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $jobReports->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $jobReports->lastPage())
                            @if($end < $jobReports->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $jobReports->url($jobReports->lastPage()) }}" class="page-number">{{ $jobReports->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                <!-- Next Button -->
                @if(isset($jobReports) && $jobReports->hasMorePages())
                    <a href="{{ $jobReports->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <!-- Page Dropdown -->
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $jobReports->currentPage() ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $jobReports->lastPage() ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Job Report</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Modal content will be loaded here -->
        </div>
        <div class="modal-footer" id="modalFooter">
            <!-- Modal footer will be loaded here -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Hide Job Report</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this job report? This action can be undone later.</p>
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
        <h3 class="error-modal-title">Hmm... Something Went Wrong</h3>
        <p class="error-modal-description" id="errorMessage">We couldn't hide the job report. Please try again.</p>
        <div class="error-modal-buttons">
            <button class="btn btn-error-close" onclick="closeErrorModal()">Close</button>
            <button class="btn btn-error-retry" onclick="retryDelete()">Try Again</button>
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
        <h3 class="success-modal-title">All Set!</h3>
        <p class="success-modal-description" id="successMessage">The job report has been successfully hidden.</p>
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

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one job report to hide');
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
    openModal('Create New Job Report');
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Basic Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Job Schedule *</label>
                        <select name="job_schedule_id" class="form-input" required>
                            <option value="">Select Job Schedule</option>
                            @foreach($jobSchedules as $schedule)
                                        <option value="{{ $schedule->id }}">{{ $schedule->job_number ?? 'Schedule #' . $schedule->id }} - {{ $schedule->company_name ?? 'Company' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Technician *</label>
                        <select name="technician_id" class="form-input" required>
                            <option value="">Select Technician</option>
                            @foreach($technicians as $technician)
                                <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Job Details</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Job Type *</label>
                        <select name="job_type" class="form-input" required>
                            <option value="">Select Job Type</option>
                            <option value="install">Install</option>
                            <option value="service">Service</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="remove">Remove</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Temperature (°C)</label>
                        <input type="number" name="temperature" class="form-input" step="0.01" min="-50" max="100" placeholder="Enter temperature">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Status & Condition</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Condition</label>
                        <select name="condition" class="form-input">
                            <option value="">Select Condition</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Refill Status</label>
                        <select name="refill_status" class="form-input">
                            <option value="">Select Refill Status</option>
                            <option value="full">Full</option>
                            <option value="half">Half</option>
                            <option value="empty">Empty</option>
                            <option value="not_applicable">Not Applicable</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Photo Documentation</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-group">
                        <label class="form-label">Photo Before Work *</label>
                        <input type="file" name="photo_before" class="form-input" accept="image/*" required>
                        <small class="text-gray-500">Mandatory: Photo of unit/location before work</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Photo After Work *</label>
                        <input type="file" name="photo_after" class="form-input" accept="image/*" required>
                        <small class="text-gray-500">Mandatory: Photo of unit/location after work</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Photo PIC *</label>
                        <input type="file" name="photo_pic" class="form-input" accept="image/*" required>
                        <small class="text-gray-500">Mandatory: Photo of Person in Charge</small>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">GPS Location</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Latitude</label>
                        <input type="number" name="latitude" class="form-input" step="0.000001" placeholder="Auto-detected from GPS">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude</label>
                        <input type="number" name="longitude" class="form-input" step="0.000001" placeholder="Auto-detected from GPS">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Location Address</label>
                    <input type="text" name="location_address" class="form-input" placeholder="Auto-generated from coordinates">
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">QR Code & Device Info</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Unit Serial Number</label>
                        <input type="text" name="unit_serial_number" class="form-input" placeholder="From QR scan">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit MAC Address</label>
                        <input type="text" name="unit_mac_address" class="form-input" placeholder="From QR scan">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Mandatory QR Scan <span class="text-red-500">*</span></div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                        <span class="font-semibold text-yellow-800">QR Scan Required</span>
                    </div>
                    <p class="text-sm text-yellow-700">For installation and service jobs, QR code scanning is mandatory before completion.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">QR Code <span class="text-red-500">*</span></label>
                        <input type="text" name="mandatory_qr_code" id="mandatory-qr-code" class="form-input" placeholder="Scan QR code from device" required>
                        <small class="text-gray-500">Scan the QR code on the device to proceed</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">QR Scan Status</label>
                        <div id="qr-scan-status" class="flex items-center gap-2 p-2 bg-gray-100 rounded">
                            <span class="text-sm text-gray-600">Not scanned</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="button" id="validate-qr-scan" class="btn btn-primary">
                        <i class="fas fa-qrcode"></i>
                        Validate QR Scan
                    </button>
                    <button type="button" id="clear-qr-scan" class="btn btn-secondary ml-2">
                        <i class="fas fa-times"></i>
                        Clear QR Scan
                    </button>
                </div>
                <div id="qr-scan-result" class="mt-4 hidden">
                    <!-- QR scan result will be displayed here -->
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Digital Signature</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">PIC Name</label>
                        <input type="text" name="pic_name" class="form-input" placeholder="Person in Charge name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PIC Position</label>
                        <input type="text" name="pic_position" class="form-input" placeholder="PIC position/title">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Digital Signature</label>
                    <canvas id="signature-pad" style="border: 1px solid #d1d5db; border-radius: 6px; width: 100%; height: 150px; background: white; cursor: crosshair;"></canvas>
                    <input type="hidden" name="signature_data" id="signature-data">
                    <div class="mt-2">
                        <button type="button" id="clear-signature" class="btn btn-secondary" style="font-size: 12px; padding: 4px 8px;">Clear Signature</button>
                    </div>
                    <small class="text-gray-500">Draw signature above (optional for admin creation)</small>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Device Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Device Online Status</label>
                        <select name="device_online_status" class="form-input">
                            <option value="">Select Status</option>
                            <option value="1">Online</option>
                            <option value="0">Offline</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Liquid Level</label>
                        <select name="device_liquid_level" class="form-input">
                            <option value="">Select Level</option>
                            <option value="0">Empty</option>
                            <option value="1">>25%</option>
                            <option value="2">>10%</option>
                            <option value="3">≤10%</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fan Level</label>
                        <select name="device_fan_level" class="form-input">
                            <option value="">Select Level</option>
                            <option value="0">Off</option>
                            <option value="1">Level 1</option>
                            <option value="2">Level 2</option>
                            <option value="3">Level 3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">QR Scan Time</label>
                        <input type="datetime-local" name="qr_scan_at" class="form-input">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Additional Information</div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input form-textarea" placeholder="Enter any additional notes or observations"></textarea>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" form="form" class="btn btn-primary">Create Job Report</button>
    `;
}

function openViewModal(id) {
    openModal('View Job Report');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    document.getElementById('modalFooter').innerHTML = '';
    
    fetch(`/api/v1/operational/job-reports/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Basic Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Job Schedule</label>
                            <p class="detail-value">${data.data.job_schedule?.job_number || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Technician</label>
                            <p class="detail-value">${data.data.technician?.name || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Job Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Job Type</label>
                            <p class="detail-value">${data.data.job_type ? data.data.job_type.charAt(0).toUpperCase() + data.data.job_type.slice(1) : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Temperature</label>
                            <p class="detail-value">${data.data.temperature ? data.data.temperature + '°C' : '-'}</p>
                        </div>
                        </div>
                    </div>
                    
                <div class="modal-section">
                    <div class="modal-section-title">Status & Condition</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Condition</label>
                            <p class="detail-value">${data.data.condition ? data.data.condition.charAt(0).toUpperCase() + data.data.condition.slice(1) : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Refill Status</label>
                            <p class="detail-value">${data.data.refill_status ? data.data.refill_status.replace('_', ' ').charAt(0).toUpperCase() + data.data.refill_status.replace('_', ' ').slice(1) : '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Photo Documentation</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Photo Before Work</label>
                            <div class="detail-value">
                                ${data.data.photo_before ? 
                                    '<img src="/storage/job-reports/photos/' + data.data.photo_before + '" style="max-width: 100%; height: 150px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;" onclick="openImageModal(this.src)">' : 
                                    '<span class="text-gray-400">No photo</span>'
                                }
                            </div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Photo After Work</label>
                            <div class="detail-value">
                                ${data.data.photo_after ? 
                                    '<img src="/storage/job-reports/photos/' + data.data.photo_after + '" style="max-width: 100%; height: 150px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;" onclick="openImageModal(this.src)">' : 
                                    '<span class="text-gray-400">No photo</span>'
                                }
                            </div>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Photo PIC</label>
                            <div class="detail-value">
                                ${data.data.photo_pic ? 
                                    '<img src="/storage/job-reports/photos/' + data.data.photo_pic + '" style="max-width: 100%; height: 150px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;" onclick="openImageModal(this.src)">' : 
                                    '<span class="text-gray-400">No photo</span>'
                                }
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">GPS Location</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Coordinates</label>
                            <p class="detail-value">${data.data.latitude && data.data.longitude ? 
                                data.data.latitude + ', ' + data.data.longitude + 
                                '<br><button class="btn btn-sm btn-secondary mt-2" onclick="openGoogleMaps(' + data.data.latitude + ', ' + data.data.longitude + ', \'' + (data.data.location_address || 'Job Location') + '\')"><i class="fas fa-map-marker-alt"></i> View Map</button>' : 
                                '-'
                            }</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Location Address</label>
                            <p class="detail-value">${data.data.location_address || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">QR Code & Device Info</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Unit Serial Number</label>
                            <p class="detail-value">${data.data.unit_serial_number || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Unit MAC Address</label>
                            <p class="detail-value">${data.data.unit_mac_address || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Digital Signature</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">PIC Name</label>
                            <p class="detail-value">${data.data.pic_name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">PIC Position</label>
                            <p class="detail-value">${data.data.pic_position || '-'}</p>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Digital Signature</label>
                        <div class="detail-value">
                            ${data.data.signature_file ? 
                                '<img src="/storage/job-reports/signatures/' + data.data.signature_file + '" style="max-width: 200px; height: 100px; object-fit: contain; border: 1px solid #e5e7eb; border-radius: 6px;">' : 
                                '<span class="text-gray-400">No signature</span>'
                            }
                        </div>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Signature Time</label>
                        <p class="detail-value">${data.data.signature_at ? new Date(data.data.signature_at).toLocaleString() : '-'}</p>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Device Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Device Online Status</label>
                            <p class="detail-value">
                                ${data.data.device_online_status !== null ? 
                                    '<span class="' + (data.data.device_online_status ? 'text-green-600' : 'text-red-600') + '">' +
                                    '<i class="fas ' + (data.data.device_online_status ? 'fa-wifi' : 'fa-wifi-slash') + '"></i> ' +
                                    (data.data.device_online_status ? 'Online' : 'Offline') + '</span>' : 
                                    '<span class="text-gray-400">Unknown</span>'
                                }
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Liquid Level</label>
                            <p class="detail-value">
                                ${data.data.device_liquid_level !== null ? 
                                    '<span class="' + (data.data.device_liquid_level > 2 ? 'text-green-600' : (data.data.device_liquid_level > 0 ? 'text-yellow-600' : 'text-red-600')) + '">' +
                                    '<i class="fas fa-tint"></i> ' +
                                    (data.data.device_liquid_level == 0 ? 'Empty' : 
                                     data.data.device_liquid_level == 1 ? '>25%' :
                                     data.data.device_liquid_level == 2 ? '>10%' :
                                     data.data.device_liquid_level == 3 ? '≤10%' :
                                     data.data.device_liquid_level + '%') + '</span>' : 
                                    '<span class="text-gray-400">No Data</span>'
                                }
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Fan Level</label>
                            <p class="detail-value">
                                ${data.data.device_fan_level !== null ? 
                                    '<span class="text-blue-600"><i class="fas fa-fan"></i> Level ' + data.data.device_fan_level + '</span>' : 
                                    '<span class="text-gray-400">No Data</span>'
                                }
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">QR Scan Time</label>
                            <p class="detail-value">${data.data.qr_scan_at ? new Date(data.data.qr_scan_at).toLocaleString() : '-'}</p>
                        </div>
                    </div>
                    ${data.data.device_snapshot ? 
                        '<div class="detail-item"><label class="form-label">Device Snapshot</label><pre class="detail-value bg-gray-100 p-3 rounded text-xs overflow-auto">' + JSON.stringify(data.data.device_snapshot, null, 2) + '</pre></div>' : 
                        ''
                    }
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Timeline</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Created At</label>
                            <p class="detail-value">${data.data.created_at ? new Date(data.data.created_at).toLocaleString('id-ID') : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Completed At</label>
                            <p class="detail-value">${data.data.completed_at ? new Date(data.data.completed_at).toLocaleString('id-ID') : '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Additional Information</div>
                        <div class="detail-item">
                            <label class="form-label">Notes</label>
                        <p class="detail-value">${data.data.notes || '-'}</p>
                    </div>
                </div>
            `;
            
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
        });
}

function openEditModal(id) {
    openModal('Edit Job Report');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    document.getElementById('modalFooter').innerHTML = '';
    
    fetch(`/api/v1/operational/job-reports/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <div class="modal-section">
                        <div class="modal-section-title">Basic Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Job Schedule *</label>
                                <select name="job_schedule_id" class="form-input" required>
                                    <option value="">Select Job Schedule</option>
                                    @foreach($jobSchedules as $schedule)
                                        <option value="{{ $schedule->id }}" ${data.data.job_schedule_id == {{ $schedule->id }} ? 'selected' : ''}>{{ $schedule->job_number ?? 'Schedule #' . $schedule->id }} - {{ $schedule->company_name ?? 'Company' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Technician *</label>
                                <select name="technician_id" class="form-input" required>
                                    <option value="">Select Technician</option>
                                    @foreach($technicians as $technician)
                                        <option value="{{ $technician->id }}" ${data.data.technician_id == {{ $technician->id }} ? 'selected' : ''}>{{ $technician->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Job Details</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Job Type *</label>
                                <select name="job_type" class="form-input" required>
                                    <option value="">Select Job Type</option>
                                    <option value="install" ${data.data.job_type === 'install' ? 'selected' : ''}>Install</option>
                                    <option value="service" ${data.data.job_type === 'service' ? 'selected' : ''}>Service</option>
                                    <option value="maintenance" ${data.data.job_type === 'maintenance' ? 'selected' : ''}>Maintenance</option>
                                    <option value="remove" ${data.data.job_type === 'remove' ? 'selected' : ''}>Remove</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Temperature (°C)</label>
                                <input type="number" name="temperature" class="form-input" step="0.01" min="-50" max="100" value="${data.data.temperature || ''}" placeholder="Enter temperature">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Status & Condition</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Condition</label>
                                <select name="condition" class="form-input">
                                    <option value="">Select Condition</option>
                                    <option value="good" ${data.data.condition === 'good' ? 'selected' : ''}>Good</option>
                                    <option value="fair" ${data.data.condition === 'fair' ? 'selected' : ''}>Fair</option>
                                    <option value="poor" ${data.data.condition === 'poor' ? 'selected' : ''}>Poor</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Refill Status</label>
                                <select name="refill_status" class="form-input">
                                    <option value="">Select Refill Status</option>
                                    <option value="full" ${data.data.refill_status === 'full' ? 'selected' : ''}>Full</option>
                                    <option value="half" ${data.data.refill_status === 'half' ? 'selected' : ''}>Half</option>
                                    <option value="empty" ${data.data.refill_status === 'empty' ? 'selected' : ''}>Empty</option>
                                    <option value="not_applicable" ${data.data.refill_status === 'not_applicable' ? 'selected' : ''}>Not Applicable</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Device Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Device Online Status</label>
                                <select name="device_online_status" class="form-input">
                                    <option value="">Select Status</option>
                                    <option value="1" ${data.data.device_online_status == 1 ? 'selected' : ''}>Online</option>
                                    <option value="0" ${data.data.device_online_status == 0 ? 'selected' : ''}>Offline</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Liquid Level</label>
                                <select name="device_liquid_level" class="form-input">
                                    <option value="">Select Level</option>
                                    <option value="0" ${data.data.device_liquid_level == 0 ? 'selected' : ''}>Empty</option>
                                    <option value="1" ${data.data.device_liquid_level == 1 ? 'selected' : ''}>>25%</option>
                                    <option value="2" ${data.data.device_liquid_level == 2 ? 'selected' : ''}>>10%</option>
                                    <option value="3" ${data.data.device_liquid_level == 3 ? 'selected' : ''}>≤10%</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Fan Level</label>
                                <select name="device_fan_level" class="form-input">
                                    <option value="">Select Level</option>
                                    <option value="0" ${data.data.device_fan_level == 0 ? 'selected' : ''}>Off</option>
                                    <option value="1" ${data.data.device_fan_level == 1 ? 'selected' : ''}>Level 1</option>
                                    <option value="2" ${data.data.device_fan_level == 2 ? 'selected' : ''}>Level 2</option>
                                    <option value="3" ${data.data.device_fan_level == 3 ? 'selected' : ''}>Level 3</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">QR Scan Time</label>
                                <input type="datetime-local" name="qr_scan_at" class="form-input" value="${data.data.qr_scan_at ? new Date(data.data.qr_scan_at).toISOString().slice(0, 16) : ''}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Additional Information</div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input form-textarea" placeholder="Enter any additional notes or observations">${data.data.notes || ''}</textarea>
                        </div>
                    </div>
                </form>
            `;
            
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Job Report</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // Add CSRF token
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    if (id) {
        formData.append('_method', 'PUT');
    }
    
    const url = id ? `/api/v1/operational/job-reports/${id}` : '/api/v1/operational/job-reports';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid JSON response');
            }
        });
    })
    .then(result => {
        if (result.status === 'success') {
            closeModal();
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Something went wrong'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this job report? This action can be undone later.'
        : `Are you sure you want to hide ${count} job reports? This action can be undone later.`;
    
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
    
    fetch('/api/v1/operational/job-reports/bulk-delete', {
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
        ? 'The job report has been successfully hidden.'
        : `${count} job reports have been successfully hidden.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t hide the job report. Please try again.';
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

// Google Maps integration
function openGoogleMaps(latitude, longitude, address) {
    // Get Google Maps API key from settings
    fetch('/api/v1/system/google-maps/api-key')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data.api_key) {
                // Open Google Maps in new tab with embedded map
                const mapUrl = `https://www.google.com/maps/embed/v1/view?key=${data.data.api_key}&center=${latitude},${longitude}&zoom=18&maptype=roadmap`;
                
                // Create modal for embedded map
                showMapModal(latitude, longitude, address, mapUrl);
            } else {
                // Fallback to Google Maps website
                const mapsUrl = `https://www.google.com/maps?q=${latitude},${longitude}&t=m&z=18`;
                window.open(mapsUrl, '_blank');
            }
        })
        .catch(error => {
            console.error('Error getting API key:', error);
            // Fallback to Google Maps website
            const mapsUrl = `https://www.google.com/maps?q=${latitude},${longitude}&t=m&z=18`;
            window.open(mapsUrl, '_blank');
        });
}

// Show map modal
function showMapModal(latitude, longitude, address, mapUrl) {
    const modalHtml = `
        <div id="mapModalOverlay" class="modal-overlay" onclick="closeMapModal()">
            <div class="modal-container" style="width: 90vw; max-width: 1000px; height: 80vh;" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2 class="modal-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Job Location
                    </h2>
                    <button class="modal-close" onclick="closeMapModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" style="padding: 0; height: calc(100% - 140px);">
                    <div style="height: 100%; position: relative;">
                        <iframe 
                            src="${mapUrl}" 
                            width="100%" 
                            height="100%" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                        <div style="position: absolute; top: 10px; left: 10px; background: white; padding: 10px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <div style="font-weight: 600; color: #1f2937;">${address}</div>
                            <div style="font-size: 12px; color: #6b7280;">${latitude}, ${longitude}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeMapModal()">Close</button>
                    <button type="button" class="btn btn-primary" onclick="openInNewTab('${latitude}', '${longitude}')">
                        <i class="fas fa-external-link-alt"></i>
                        Open in New Tab
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.getElementById('mapModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close map modal
function closeMapModal() {
    const modal = document.getElementById('mapModalOverlay');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

// Open in new tab
function openInNewTab(latitude, longitude) {
    const mapsUrl = `https://www.google.com/maps?q=${latitude},${longitude}&t=m&z=18`;
    window.open(mapsUrl, '_blank');
    closeMapModal();
}

// Open image modal
function openImageModal(imageSrc) {
    const modalHtml = `
        <div id="imageModalOverlay" class="modal-overlay" onclick="closeImageModal()">
            <div class="modal-container" style="width: 90vw; max-width: 800px; height: 80vh;" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2 class="modal-title">
                        <i class="fas fa-image"></i>
                        Photo Preview
                    </h2>
                    <button class="modal-close" onclick="closeImageModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" style="padding: 20px; text-align: center;">
                    <img src="${imageSrc}" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 6px;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeImageModal()">Close</button>
                    <button type="button" class="btn btn-primary" onclick="downloadImage('${imageSrc}')">
                        <i class="fas fa-download"></i>
                        Download
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.getElementById('imageModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Close image modal
function closeImageModal() {
    const modal = document.getElementById('imageModalOverlay');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

// Download image
function downloadImage(imageSrc) {
    const link = document.createElement('a');
    link.href = imageSrc;
    link.download = 'job-report-photo.jpg';
    link.click();
}

// Signature Pad functionality
let signaturePad = null;

function initializeSignaturePad() {
    const canvas = document.getElementById('signature-pad');
    if (canvas && typeof SignaturePad !== 'undefined') {
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(0, 0, 0)',
            minWidth: 1,
            maxWidth: 3,
            throttle: 16,
            minDistance: 5
        });
        
        // Clear signature button
        const clearBtn = document.getElementById('clear-signature');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                signaturePad.clear();
                document.getElementById('signature-data').value = '';
            });
        }
        
        // Auto-save signature data
        signaturePad.addEventListener('endStroke', function() {
            if (!signaturePad.isEmpty()) {
                const dataURL = signaturePad.toDataURL();
                document.getElementById('signature-data').value = dataURL;
            }
        });
    }
}

// Initialize signature pad when modal opens
function openCreateModal() {
    openModal('Create New Job Report');
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Basic Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Job Schedule *</label>
                        <select name="job_schedule_id" class="form-input" required>
                            <option value="">Select Job Schedule</option>
                            @foreach($jobSchedules as $schedule)
                                        <option value="{{ $schedule->id }}">{{ $schedule->job_number ?? 'Schedule #' . $schedule->id }} - {{ $schedule->company_name ?? 'Company' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Job Type *</label>
                        <select name="job_type" class="form-input" required>
                            <option value="">Select Job Type</option>
                            <option value="install">Install</option>
                            <option value="service">Service</option>
                            <option value="remove">Remove</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Temperature (°C)</label>
                        <input type="number" name="temperature" class="form-input" step="0.1" placeholder="Room temperature">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Condition</label>
                        <select name="condition" class="form-input">
                            <option value="">Select Condition</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                            <option value="needs_repair">Needs Repair</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Refill Status</label>
                        <select name="refill_status" class="form-input">
                            <option value="">Select Status</option>
                            <option value="full">Full</option>
                            <option value="half">Half</option>
                            <option value="low">Low</option>
                            <option value="empty">Empty</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit Serial Number</label>
                        <input type="text" name="unit_serial_number" class="form-input" placeholder="Unit serial number">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-textarea" rows="3" placeholder="Additional notes..."></textarea>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">GPS Location</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Latitude</label>
                        <input type="number" name="latitude" class="form-input" step="0.0000001" placeholder="GPS latitude">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude</label>
                        <input type="number" name="longitude" class="form-input" step="0.0000001" placeholder="GPS longitude">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Location Address</label>
                    <input type="text" name="location_address" class="form-input" placeholder="Full address">
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Photo Documentation</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-group">
                        <label class="form-label">Photo Before</label>
                        <input type="file" name="photo_before" class="form-input" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Photo After</label>
                        <input type="file" name="photo_after" class="form-input" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Photo PIC</label>
                        <input type="file" name="photo_pic" class="form-input" accept="image/*">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Digital Signature</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">PIC Name</label>
                        <input type="text" name="pic_name" class="form-input" placeholder="Person in Charge name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PIC Position</label>
                        <input type="text" name="pic_position" class="form-input" placeholder="PIC position/title">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Digital Signature</label>
                    <canvas id="signature-pad" style="border: 1px solid #d1d5db; border-radius: 6px; width: 100%; height: 150px; background: white; cursor: crosshair;"></canvas>
                    <input type="hidden" name="signature_data" id="signature-data">
                    <div class="mt-2">
                        <button type="button" id="clear-signature" class="btn btn-secondary" style="font-size: 12px; padding: 4px 8px;">Clear Signature</button>
                    </div>
                    <small class="text-gray-500">Draw signature above (optional for admin creation)</small>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Device Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Device Online Status</label>
                        <select name="device_online_status" class="form-input">
                            <option value="">Select Status</option>
                            <option value="1">Online</option>
                            <option value="0">Offline</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Device Liquid Level</label>
                        <select name="device_liquid_level" class="form-input">
                            <option value="">Select Level</option>
                            <option value="100">100%</option>
                            <option value="75">75%</option>
                            <option value="50">50%</option>
                            <option value="25">25%</option>
                            <option value="0">0%</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Device Fan Level</label>
                        <select name="device_fan_level" class="form-input">
                            <option value="">Select Level</option>
                            <option value="1">Level 1</option>
                            <option value="2">Level 2</option>
                            <option value="3">Level 3</option>
                            <option value="4">Level 4</option>
                            <option value="5">Level 5</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit MAC Address</label>
                        <input type="text" name="unit_mac_address" class="form-input" placeholder="Unit MAC address">
                    </div>
                </div>
            </div>
        </form>
    `;
    
    // Initialize signature pad after modal content is loaded
    setTimeout(() => {
        initializeSignaturePad();
        initializeMandatoryQRScan();
    }, 100);
    
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" form="form">Create Job Report</button>
    `;
}

// Mandatory QR Scan functionality
function initializeMandatoryQRScan() {
    const validateBtn = document.getElementById('validate-qr-scan');
    const clearBtn = document.getElementById('clear-qr-scan');
    const qrCodeInput = document.getElementById('mandatory-qr-code');
    const statusDiv = document.getElementById('qr-scan-status');
    const resultDiv = document.getElementById('qr-scan-result');
    
    if (validateBtn) {
        validateBtn.addEventListener('click', validateMandatoryQRScan);
    }
    
    if (clearBtn) {
        clearBtn.addEventListener('click', clearMandatoryQRScan);
    }
    
    if (qrCodeInput) {
        qrCodeInput.addEventListener('input', function() {
            if (this.value.trim()) {
                statusDiv.innerHTML = `
                    <span class="text-sm text-blue-600">
                        <i class="fas fa-qrcode"></i>
                        Ready to validate
                    </span>
                `;
            } else {
                statusDiv.innerHTML = `
                    <span class="text-sm text-gray-600">Not scanned</span>
                `;
            }
        });
    }
}

function validateMandatoryQRScan() {
    const qrCode = document.getElementById('mandatory-qr-code').value.trim();
    const statusDiv = document.getElementById('qr-scan-status');
    const resultDiv = document.getElementById('qr-scan-result');
    
    if (!qrCode) {
        alert('Please enter a QR code to validate');
        return;
    }
    
    // Show loading state
    statusDiv.innerHTML = `
        <span class="text-sm text-blue-600">
            <i class="fas fa-spinner fa-spin"></i>
            Validating QR code...
        </span>
    `;
    
    // Validate QR code via API
    fetch('/operational/job-reports/validate-qr', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            qr_code: qrCode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.data.valid) {
            // QR code is valid
            statusDiv.innerHTML = `
                <span class="text-sm text-green-600">
                    <i class="fas fa-check-circle"></i>
                    QR code validated
                </span>
            `;
            
            // Show device information
            resultDiv.innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-check-circle text-green-600"></i>
                        <span class="font-semibold text-green-800">QR Code Valid</span>
                    </div>
                    <div class="text-sm text-green-700">
                        <p><strong>Device Type:</strong> ${data.data.device?.deviceType || 'Unknown'}</p>
                        <p><strong>MAC Address:</strong> ${data.data.mac || 'Unknown'}</p>
                        <p><strong>Status:</strong> ${data.data.device?.onlineStatus || 'Unknown'}</p>
                    </div>
                </div>
            `;
            resultDiv.classList.remove('hidden');
            
            // Auto-fill unit information if available
            if (data.data.device) {
                const unitSerialInput = document.querySelector('input[name="unit_serial_number"]');
                const unitMacInput = document.querySelector('input[name="unit_mac_address"]');
                
                if (unitSerialInput && data.data.device.serialNumber) {
                    unitSerialInput.value = data.data.device.serialNumber;
                }
                if (unitMacInput && data.data.mac) {
                    unitMacInput.value = data.data.mac;
                }
            }
            
        } else {
            // QR code is invalid
            statusDiv.innerHTML = `
                <span class="text-sm text-red-600">
                    <i class="fas fa-times-circle"></i>
                    Invalid QR code
                </span>
            `;
            
            resultDiv.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-times-circle text-red-600"></i>
                        <span class="font-semibold text-red-800">QR Code Invalid</span>
                    </div>
                    <div class="text-sm text-red-700">
                        <p>${data.message || 'The QR code could not be validated. Please check the code and try again.'}</p>
                    </div>
                </div>
            `;
            resultDiv.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Error validating QR code:', error);
        statusDiv.innerHTML = `
            <span class="text-sm text-red-600">
                <i class="fas fa-exclamation-triangle"></i>
                Validation failed
            </span>
        `;
        
        resultDiv.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                    <span class="font-semibold text-red-800">Validation Error</span>
                </div>
                <div class="text-sm text-red-700">
                    <p>An error occurred while validating the QR code. Please try again.</p>
                </div>
            </div>
        `;
        resultDiv.classList.remove('hidden');
    });
}

function clearMandatoryQRScan() {
    document.getElementById('mandatory-qr-code').value = '';
    document.getElementById('qr-scan-status').innerHTML = `
        <span class="text-sm text-gray-600">Not scanned</span>
    `;
    document.getElementById('qr-scan-result').innerHTML = '';
    document.getElementById('qr-scan-result').classList.add('hidden');
}
</script>
@endsection
