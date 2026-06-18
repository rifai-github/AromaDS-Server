@extends('layouts.app')

@section('title', 'Job Schedule List')
@section('breadcrumb', 'Home / Operational / Job Schedule List')

@section('content')
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">

<style>
    /* Custom Flatpickr styles to match premium design */
    .flatpickr-input {
        background-color: white !important;
    }
    .flatpickr-day.selected {
        background: #214589 !important;
        border-color: #214589 !important;
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

    /* Specific delete button styling */
    .btn-delete:hover {
        background-color: #214589 !important;
        color: white !important;
        border-color: #214589 !important;
    }

    /* Table Container */
    .table-container {
        --job-schedule-select-col-width: 50px;
        --job-schedule-job-col-width: 200px;
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
    
    /* Header Row (Titles) */
    .responsive-table thead tr:first-child th {
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

    /* Filter Row (Inputs) */
    .responsive-table thead tr:not(:first-child) th {
        background-color: white;
        color: #374151;
        font-weight: normal;
        position: static; /* Prevent overlap with sticky header */
        z-index: 9;
        padding: 8px;
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
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 120px; min-width: 120px; }

    .responsive-table th:nth-child(1),
    .responsive-table td:nth-child(1) {
        position: sticky !important;
        left: 0 !important;
        z-index: 12;
        background-color: white;
    }

    .responsive-table th:nth-child(2),
    .responsive-table td:nth-child(2) {
        position: sticky !important;
        left: var(--job-schedule-select-col-width) !important;
        z-index: 12;
        background-color: white;
        box-shadow: 8px 0 12px -12px rgba(15, 23, 42, 0.75);
    }

    .responsive-table thead tr:first-child th:nth-child(1),
    .responsive-table thead tr:first-child th:nth-child(2) {
        position: sticky !important;
        background-color: #214589;
        z-index: 24;
    }

    .responsive-table thead tr:first-child th:nth-child(1) {
        left: 0 !important;
    }

    .responsive-table thead tr:first-child th:nth-child(2) {
        left: var(--job-schedule-select-col-width) !important;
    }

    .responsive-table thead tr:not(:first-child) th:nth-child(1),
    .responsive-table thead tr:not(:first-child) th:nth-child(2) {
        position: sticky !important;
        background-color: white;
        z-index: 18;
    }

    .responsive-table thead tr:not(:first-child) th:nth-child(1) {
        left: 0 !important;
    }

    .responsive-table thead tr:not(:first-child) th:nth-child(2) {
        left: var(--job-schedule-select-col-width) !important;
    }

    .responsive-table tbody tr:hover td:nth-child(1),
    .responsive-table tbody tr:hover td:nth-child(2) {
        background-color: #eff6ff;
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
    
    /* Status Badge Styles */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .status-scheduled, .status-new-job {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .status-assign-team {
        background-color: #e0e7ff;
        color: #3730a3;
    }

    .status-assign-material {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-barang-dipersiapkan {
        background-color: #fed7aa;
        color: #9a3412;
    }

    .status-barang-diambil {
        background-color: #fde68a;
        color: #78350f;
    }

    .status-teknisi-tiba-dilokasi {
        background-color: #c7d2fe;
        color: #312e81;
    }

    .status-teknisi-sedang-pengerjaan {
        background-color: #fbbf24;
        color: #78350f;
    }

    .status-teknisi-selesai-pengerjaan {
        background-color: #86efac;
        color: #14532d;
    }

    .status-meninggalkan-lokasi {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-done-job, .status-completed {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-in-progress {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-cancelled {
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
        
        @media (min-width: 1280px) {
            .grid.grid-cols-1.xl\:grid-cols-4 {
                grid-template-columns: repeat(4, 1fr);
            }
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
        
        <!-- Unified Job Schedule Header (Final Polish v2) -->
        <div class="flex flex-col w-full bg-white rounded-t-[10px] border-b">
            
            <!-- Row 1: Date Filters -->
            <div class="flex flex-row items-center justify-between p-3 border-b bg-gray-50 rounded-t-[10px] overflow-x-auto">
                <div class="flex items-center gap-4 min-w-max">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-700 whitespace-nowrap">Date:</span>
                        <input type="text" id="filterDateFrom" name="date_from" class="flatpickr-date px-2 py-1 border border-gray-300 rounded text-xs w-[110px]" value="{{ request('date_from') }}" placeholder="Select date..." readonly title="Dari Tanggal">
                        <span class="text-xs text-gray-500 whitespace-nowrap">s/d</span>
                        <input type="text" id="filterDateTo" name="date_to" class="flatpickr-date px-2 py-1 border border-gray-300 rounded text-xs w-[110px]" value="{{ request('date_to') }}" placeholder="Select date..." readonly title="Sampai Tanggal">
                    </div>
                    <button type="button" id="btnCariDate" class="btn btn-primary btn-xs px-3 py-1 flex items-center gap-1 shadow-sm whitespace-nowrap" onclick="applyFilters()" title="Apply Filters">
                        <i class="fas fa-search"></i> <span>Cari</span>
                    </button>
                    <button type="button" class="btn btn-secondary btn-xs px-3 py-1 flex items-center gap-1 shadow-sm whitespace-nowrap" onclick="clearDateFilters()" title="Reset Filters">
                         <i class="fas fa-undo"></i> <span>Reset Filters</span>
                    </button>
                </div>

                 <!-- Change Mod (Moved to Top Right) -->
                 <div class="flex items-center gap-2 min-w-max">
                    <span class="text-xs font-bold text-gray-700 whitespace-nowrap">Change Mod:</span>
                    <select class="px-2 py-1 border border-gray-300 rounded text-xs font-bold text-[#214589]" 
                            onchange="window.location.href=this.value">
                        <option value="{{ request()->fullUrlWithQuery(['view_mode' => 'job']) }}" {{ ($viewMode ?? 'job') === 'job' ? 'selected' : '' }}>Job View</option>
                        <option value="{{ request()->fullUrlWithQuery(['view_mode' => 'room']) }}" {{ ($viewMode ?? 'job') === 'room' ? 'selected' : '' }}>Room View</option>
                    </select>
                </div>
            </div>

            <!-- Row 2: Controls & Actions (Justified & Responsive) -->
            <div class="flex flex-row flex-wrap items-center justify-between gap-4 p-3 w-full bg-white">
                
                <!-- Left Group: Select All & Action -->
                <div class="flex items-center gap-6">
                    <!-- 1. Select All -->
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 text-blue-600 border-gray-300 rounded cursor-pointer" onchange="toggleSelectAll()">
                        <label for="selectAll" class="text-xs font-bold text-gray-700 cursor-pointer whitespace-nowrap">Select All</label>
                    </div>

                    <!-- 2. Action & Apply -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-700 whitespace-nowrap">Action:</span>
                        <select id="actionType" class="px-2 py-1 border border-gray-300 rounded text-xs w-[140px]" onchange="applySuspendDpf()">
                            <option value="">Choose Action...</option>
                            @if($canSuspend || $canDPF)
                            <optgroup label="Status">
                                @if($canSuspend)<option value="suspend">Suspend</option>@endif
                                @if($canSuspend)<option value="unsuspend">Unsuspend</option>@endif
                                @if($canDPF)<option value="dpf">DPF</option>@endif
                            </optgroup>
                            @endif

                            @if($canUnpostBA || $canUnpostIssue || $canUnassignTeam || $canMaterialAssign || $canUnassignMaterial || $canPrint)
                            <optgroup label="Job Actions">
                                @if($canUnpostBA)<option value="unpost_ba">Unpost BA</option>@endif
                                @if($canUnpostIssue)<option value="unpost_issue">Unpost Issue</option>@endif
                                @if($canUnassignTeam)<option value="unassign_team">Unassign Team</option>@endif
                                @if($canMaterialAssign)<option value="material_assign">Material Assign</option>@endif
                                @if($canUnassignMaterial)<option value="unassign_material">Unassign Material</option>@endif
                                @if($canPrint)<option value="print_csr">Print</option>@endif
                            </optgroup>
                            @endif

                            @if($canAssignTeam)
                            <optgroup label="Teams">
                                @foreach($teams ?? [] as $team)
                                    <option value="assign_team_{{ $team->id }}" data-team-name="{{ $team->team_name }}">{{ $team->team_name }}</option>
                                @endforeach
                            </optgroup>
                            @endif

                            @if(($viewMode ?? 'job') === 'room')
                              <optgroup label="Room Actions">
                                 <option value="suspend_room">Suspend Room</option>
                              </optgroup>
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Right Group: Schedules, Extend, Show -->
                <div class="flex items-center gap-6 flex-wrap md:flex-nowrap">
                    <!-- 3. Change Schedule Date -->
                     <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-700 whitespace-nowrap">Change Schedule Date:</span>
                        <input type="text" id="filterScheduleDate" name="schedule_date" class="flatpickr-date px-2 py-1 border border-gray-300 rounded text-xs w-[110px]" placeholder="Select date..." readonly>
                    </div>

                    <!-- 4. Adjustment -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-700 whitespace-nowrap">Extend:</span>
                        <div class="flex items-center">
                            <select id="adjustmentType" class="px-2 py-1 border border-gray-300 rounded-l text-xs w-[50px]">
                                <option value="plus">+</option>
                                <option value="minus">-</option>
                            </select>
                            <input type="number" id="adjustmentDays" class="px-2 py-1 border-y border-r border-gray-300 text-xs w-[45px] text-center" placeholder="0" min="1" max="365" value="1">
                            <button type="button" class="btn btn-primary btn-xs px-2 py-1 rounded-r shadow-sm ml-1" onclick="applyAdjustmentDay()" title="Apply Extension">
                                <i class="fas fa-play"></i>
                            </button>
                        </div>
                    </div>

                     <!-- 5. Pagination -->
                     <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-gray-700 whitespace-nowrap">Show:</span>
                        <select id="perPageSelector" class="px-2 py-1 border border-gray-300 rounded text-xs" onchange="updatePerPage(this.value)">
                            <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 || !request('per_page') ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                            <option value="250" {{ request('per_page') == 250 ? 'selected' : '' }}>250</option>
                            <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                            <option value="1000" {{ request('per_page') == 1000 ? 'selected' : '' }}>1000</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" onchange="toggleSelectAll()">
                        </th>
                        <!-- 1. Job No -->
                        <th data-column="job_number">Job No</th>
                        
                        <!-- 2. Type -->
                        <th data-column="type">Type</th>
                        
                        <!-- 3. Reference (JA, Complain, Extra) -->
                        <th data-column="reference_number">Reference</th>
                        
                        <!-- 4. Status -->
                        <th data-column="status">Status</th>
                        
                        <!-- 5. Nama Team -->
                        <th data-column="jobAssignSchedules.team.team_name">Nama Team</th>
                        
                        <!-- 6. Customer Name -->
                        <th data-column="company_name">Customer Name</th>
                        
                        <!-- 7. Nomor Contract -->
                        <th data-column="contract_number">Nomor Contract</th>
                        
                        <!-- 8. Building Name -->
                        <th data-column="building.nama_gedung">Building Name</th>
                        
                        <!-- 9. Branch Service -->
                        <th data-column="building.city.name">Branch Service</th>
                        
                        <!-- 10. P.Service -->
                        <th data-column="period">P.Service</th>
                        
                        <!-- 11. P.Invoice -->
                        <th data-column="p_invoice">P.Invoice</th>
                        
                        @if(($viewMode ?? 'job') === 'room')
                            <!-- 12. Floor (Room Mode Only) -->
                            <th data-column="room.room_floor">Floor</th>
                            
                            <!-- 13. Room (Room Mode Only) -->
                            <th data-column="room.room_name">Room</th>
                        @endif
                        
                        <!-- 14/12. Schedule Date -->
                        <th data-column="schedule_date" data-type="date">Schedule Date</th>
                        
                        <!-- 15/13. Expected Date -->
                        <th data-column="expected_date" data-type="date">Expected Date</th>
                        
                        <!-- 16/14. BA Date -->
                        <th data-column="ba_date" data-type="date">BA Date</th>
                        
                        <!-- 17/15. Assign Date -->
                        <th data-column="assign_date" data-type="date">Assign Date</th>
                        
                        <!-- 18/16. Issue Date -->
                        <th data-column="issue_date" data-type="date">Issue Date</th>
                        
                        <!-- 19/17. Receive Date -->
                        <!-- 19/17. Receive Date -->
                        <th data-column="created_at" data-type="date">Receive Date</th>
                        
                        <!-- 20/18. Kode Pos -->
                        <th data-column="postal_code">Kode Pos</th>
                        
                        <!-- 21/19. Area/Kecamatan -->
                        <th data-column="district">Area/Kecamatan</th>
                        
                        <!-- 22/20. Op. Notes from Contract -->
                        <th data-column="jobAdvice__contract__notes_operation">Op. Notes</th>
                        
                        <!-- 23/21. Remark Job/Ruangan -->
                        <th data-column="internal_notes">Remark</th>
                        
                        <th data-column="createdBy.name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy.name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                    <tr class="filter-row">
                        <th></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="job_number" placeholder="Job No..." value="{{ request('filter.job_number') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="type" placeholder="Type..." value="{{ request('filter.type') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="reference_number" placeholder="Ref..." value="{{ request('filter.reference_number') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="status" placeholder="Status..." value="{{ request('filter.status') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="jobAssignSchedules__team__team_name" placeholder="Team..." value="{{ request('filter.jobAssignSchedules__team__team_name') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="company_name" placeholder="Customer..." value="{{ request('filter.company_name') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="contract_number" placeholder="Contract..." value="{{ request('filter.contract_number') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="building__nama_gedung" placeholder="Building..." value="{{ request('filter.building__nama_gedung') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="building__city__name" placeholder="Branch..." value="{{ request('filter.building__city__name') ?? request('filter.building__district__name') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="period" placeholder="Period..." value="{{ request('filter.period') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="p_invoice" placeholder="Inv..." value="{{ request('filter.p_invoice') }}"></th>
                        
                        @if(($viewMode ?? 'job') === 'room')
                            <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="room__room_floor" placeholder="Floor..." value="{{ request('filter.room__room_floor') }}"></th>
                            <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="room__room_name" placeholder="Room..." value="{{ request('filter.room__room_name') }}"></th>
                        @endif
                        
                        <th><input type="text" class="column-filter column-filter-date w-full px-1 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="schedule_date" value="{{ request('filter.schedule_date') }}"></th>
                        <th><input type="text" class="column-filter column-filter-date w-full px-1 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="expected_date" value="{{ request('filter.expected_date') }}"></th>
                        <th><input type="text" class="column-filter column-filter-date w-full px-1 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="ba_date" value="{{ request('filter.ba_date') }}"></th>
                        <th><input type="text" class="column-filter column-filter-date w-full px-1 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="assign_date" value="{{ request('filter.assign_date') }}"></th>
                        <th><input type="text" class="column-filter column-filter-date w-full px-1 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="issue_date" value="{{ request('filter.issue_date') }}"></th>
                        <th><input type="text" class="column-filter column-filter-date w-full px-1 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="created_at" value="{{ request('filter.created_at') }}"></th>
                        
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="postal_code" placeholder="Postal..." value="{{ request('filter.postal_code') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="district" placeholder="Area..." value="{{ request('filter.district') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="jobAdvice__contract__notes_operation" placeholder="Notes..." value="{{ request('filter.jobAdvice__contract__notes_operation') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="internal_notes" placeholder="Remark..." value="{{ request('filter.internal_notes') }}"></th>
                        
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="createdBy__name" placeholder="By..." value="{{ request('filter.createdBy__name') }}"></th>
                        <th><input type="text" class="column-filter column-filter-date w-full px-1 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="created_at" value="{{ request('filter.created_at') }}"></th>
                        <th><input type="text" class="column-filter w-full px-2 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="updatedBy__name" placeholder="By..." value="{{ request('filter.updatedBy__name') }}"></th>
                        <th><input type="text" class="column-filter column-filter-date w-full px-1 py-1 text-[11px] border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 outline-none" data-column="updated_at" value="{{ request('filter.updated_at') }}"></th>
                    </tr>
                    
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($jobSchedules ?? [] as $schedule)
                    @php
                        $isRoomView = ($viewMode ?? 'job') === 'room';
                        $job = $isRoomView ? $schedule->jobSchedule : $schedule;
                        
                        // Safety check for orphaned records
                        if (!$job) continue;
                        
                        // MOM: Align status labels between Job and Room View
                        // Use the JobSchedule's enriched status (New Job, Assign Team, etc.) in both views
                        $status = $job->status;
                        $statusText = $job->status_text;
                        $statusBadgeClass = 'status-' . str_replace('_', '-', $status);
                        
                        // Logic for Reference: Prefer Job Advice Number, then Reference Number (for Complain/Extra)
                        $referenceSource = '-';
                        if ($job) {
                            if ($job->jobAdvice) {
                                 $referenceSource = $job->jobAdvice->job_advice_number; // JA Number
                            } elseif ($job->reference_number) {
                                 $referenceSource = $job->reference_number; // Complain/Extra Reference
                            } elseif ($job->type === 'complain') {
                                 $referenceSource = 'Complain (No Ref)';
                            } else {
                                 // Fallback attempts
                                 $referenceSource = $job->job_reference_number ?? 'Extra';
                            }
                        }
                        
                        // Branch Service follows the registered service branch for the building city.
                        $branchService = \App\Services\OperationalAreaService::getServiceBranchLabelForBuilding($job->building);
                        
                        // Logic for P.Invoice (From Quotation)
                        $pInvoice = $job->invoice_period ?? '-';
                        $jobTypeKey = strtolower(str_replace(' ', '_', trim((string) ($job->type ?? ''))));
                        $jobAdviceTypeKey = strtolower(str_replace(' ', '_', trim((string) ($job->jobAdvice?->type ?? ''))));
                        $isInstallFreeJob = in_array($jobTypeKey, ['install_free'], true)
                            || in_array($jobAdviceTypeKey, ['install_free'], true);
                        $quotationDisplayNumber = $job->quotation_number
                            ?: ($job->jobAdvice?->quotation?->quotation_number
                                ?: ($job->jobAdvice?->contract?->quotation?->quotation_number ?? null));
                        $contractDisplayNumber = ($isInstallFreeJob && filled($quotationDisplayNumber))
                            ? $quotationDisplayNumber
                            : ($job->contract_number ?: '-');

                        // Check if this job employs granular room-level assignments
                        $hasAnyRoomAssignment = false;
                        if ($job->allGroupedRooms) {
                            // Using whereNotNull to avoid loading relations if possible, or just checking the relation
                            foreach ($job->allGroupedRooms as $gr) {
                                if ($gr->roomAssignment) {
                                    $hasAnyRoomAssignment = true;
                                    break;
                                }
                            }
                        }

                        // Team Name Logic
                        if ($isRoomView) {
                            if ($hasAnyRoomAssignment) {
                                $teamName = $schedule->roomAssignment?->team?->team_name ?? 'unassign';
                            } else {
                                $teamName = $job->jobAssignSchedules->where('status', '!=', 'cancelled')->sortByDesc('id')->first()?->team?->team_name ?? 'unassign';
                            }
                        } else {
                            $teamName = $job->jobAssignSchedules->where('status', '!=', 'cancelled')->sortByDesc('id')->first()?->team?->team_name ?? 'unassign';
                        }

                        $jobViewRows = collect();
                        $jobViewStatuses = collect();
                        $jobViewTeams = collect();
                        if (!$isRoomView && $job->allGroupedRooms && $job->allGroupedRooms->count() > 0) {
                            foreach ($job->allGroupedRooms as $room) {
                                $roomJob = $room->jobSchedule;
                                $displayJobNumber = $roomJob?->job_number ?? 'unassign';
                                $displayRoomName = $room->room_name ?? 'No Room';

                                $jobViewRows->push([
                                    'key' => $displayJobNumber . '|' . $displayRoomName,
                                    'job_number' => $displayJobNumber,
                                    'room_name' => $displayRoomName,
                                ]);

                                $jobViewStatuses->push([
                                    'key' => ($room->status_text ?? '') . '|' . ($room->status_badge_class ?? ''),
                                    'text' => $room->status_text,
                                    'class' => $room->status_badge_class,
                                ]);

                                if ($hasAnyRoomAssignment) {
                                    $displayTeamName = $room->roomAssignment?->team?->team_name ?? 'unassign';
                                } else {
                                    $displayTeamName = $roomJob?->jobAssignSchedules?->where('status', '!=', 'cancelled')->sortByDesc('id')->first()?->team?->team_name ?? 'unassign';
                                }

                                $jobViewTeams->push($displayTeamName);
                            }

                            $jobViewRows = $jobViewRows->unique('key')->values();
                            $jobViewStatuses = $jobViewStatuses->unique('key')->values();
                            $jobViewTeams = $jobViewTeams
                                ->filter()
                                ->unique()
                                ->values();

                            $assignedJobViewTeams = $jobViewTeams->reject(fn($name) => strtolower(trim((string) $name)) === 'unassign')->values();
                            if ($assignedJobViewTeams->isNotEmpty()) {
                                $jobViewTeams = $assignedJobViewTeams;
                                $teamName = $jobViewTeams->first();
                            } elseif ($jobViewTeams->isEmpty()) {
                                $jobViewTeams = collect(['unassign']);
                            }
                        }
                    @endphp
                    <tr data-id="{{ $schedule->id }}" data-view-mode="{{ $viewMode ?? 'job' }}" 
                        onclick="window.location.href = '{{ route('operational.job-schedules.show', $job->id) }}?view_mode={{ $viewMode ?? 'job' }}&{{ $isRoomView ? 'room_id=' . ($schedule->room_id ?? '') : 'building_id=' . ($job->building_id ?? '') }}'" 
                        class="cursor-pointer">
                        <td class="text-center" onclick="event.stopPropagation()">
                            @php
                                // MOM-Fix: Hilangkan pencegahan seleksi baris pada status Done Job
                                // Hal ini diperlukan agar user bisa memilih baris untuk aksi "Print CSR" 
                                // Keamanan untuk aksi lain (Assign, Material, dll) tetap dijaga di level JS (applySuspendDpf)
                                $isDisabled = false;
                                $groupedRoomIds = (!$isRoomView && $job->allGroupedRooms && $job->allGroupedRooms->count() > 0)
                                    ? $job->allGroupedRooms->pluck('id')->filter()->values()->implode(',')
                                    : '';
                            @endphp
                            <input type="checkbox" 
                                class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer {{ $isDisabled ? 'opacity-50 cursor-not-allowed' : '' }}" 
                                value="{{ $schedule->id }}" 
                                data-job-id="{{ $job->id }}" 
                                data-room-ids="{{ $groupedRoomIds }}"
                                data-room-name="{{ $isRoomView ? ($schedule->room->room_name ?? 'Unknown Room') : 'All Rooms' }}"
                                data-current-team="{{ $teamName }}"
                                data-status="{{ $job->status }}"
                                data-type="{{ $job->type }}"
                                data-display-type="{{ $job->display_type ?? ($job->type ? ucfirst($job->type) : '-') }}"
                                onchange="updateProposeTeamButton()" 
                                onclick="event.stopPropagation()"
                                {{ $isDisabled ? 'disabled' : '' }}>
                        </td>
                        
                        <!-- 1. Job No -->
                        <td onclick="event.stopPropagation()">
                            <a href="{{ route('operational.job-schedules.show', $job->id) }}?view_mode={{ $viewMode ?? 'job' }}&{{ $isRoomView ? 'room_id=' . ($schedule->room_id ?? '') : 'building_id=' . ($job->building_id ?? '') }}" class="text-blue-600 hover:underline">
                                @if(!$isRoomView)
                                    @if($jobViewRows->isNotEmpty())
                                        @foreach($jobViewRows as $index => $row)
                                            <div class="{{ $index > 0 ? 'mt-1 pt-1 border-t border-gray-100' : '' }}">
                                                {{ $row['job_number'] }} - {{ $row['room_name'] }}
                                            </div>
                                        @endforeach
                                    @else
                                        {{ $job->job_number ?: 'unassign' }} - {{ $job->room->room_name ?? 'No Room' }}
                                    @endif
                                @else
                                    {{ $job->job_number ?? '-' }} - {{ $schedule->room->room_name ?? '-' }}
                                @endif
                            </a>
                        </td>
                        
                        <!-- 2. Type -->
                        <td>{{ $job->display_type ?? ($job->type ? ucfirst($job->type) : '-') }}</td>
                        
                        <!-- 3. Reference -->
                        <td>{{ $referenceSource }}</td>
                        
                        <!-- 4. Status -->
                        <td>
                            @if(!$isRoomView && $jobViewStatuses->isNotEmpty())
                                @foreach($jobViewStatuses as $index => $statusRow)
                                    <div class="{{ $index > 0 ? 'mt-1 pt-1 border-t border-gray-100' : '' }}">
                                        <span class="status-badge {{ $statusRow['class'] }}">
                                            {{ $statusRow['text'] }}
                                        </span>
                                    </div>
                                @endforeach
                            @elseif($isRoomView)
                                <span class="status-badge {{ $schedule->status_badge_class }}">
                                    {{ $schedule->status_text }}
                                </span>
                            @else
                                <span class="status-badge {{ $job->status_badge_class }}">
                                    {{ $job->status_text }}
                                </span>
                            @endif
                        </td>
                        
                        <!-- 5. Nama Team -->
                        <td>
                             @if(!$isRoomView && $jobViewTeams->isNotEmpty())
                                @foreach($jobViewTeams as $index => $displayTeamName)
                                    <div class="{{ $index > 0 ? 'mt-1 pt-1 border-t border-gray-100' : '' }}">
                                        {{ $displayTeamName }}
                                    </div>
                                @endforeach
                            @else
                                {{ $teamName }}
                            @endif
                        </td>
                        
                        <!-- 6. Customer Name -->
                        <td>{{ $job->jobAdvice?->customer?->name ?? $job->company_name ?? '-' }}</td>
                        
                        <!-- 7. Nomor Contract -->
                        <td>{{ $contractDisplayNumber }}</td>
                        
                        <!-- 8. Building Name -->
                        <td>{{ $job->building?->nama_gedung ?? '-' }}</td>
                        
                        <!-- 9. Branch Service -->
                        <td>{{ $branchService }}</td>
                        
                        <!-- 10. P.Service -->
                        <td>{{ $job->period ?? '-' }}</td>
                        
                        <!-- 11. P.Invoice -->
                        <td>{{ $pInvoice }}</td>
                        
                        @if($isRoomView)
                            <!-- 12. Floor -->
                            <td>{{ $schedule->room->room_floor ?? '-' }}</td>
                            <!-- 13. Room -->
                            <td>{{ $schedule->room->room_name ?? '-' }}</td>
                        @endif
                        
                        <!-- 14. Schedule Date -->
                        <td>{{ $job->schedule_date ? \Carbon\Carbon::parse($job->schedule_date)->format('d/M/Y') : '-' }}</td>
                        
                        <!-- 15. Expected Date -->
                        <td>{{ $job->expected_date ? \Carbon\Carbon::parse($job->expected_date)->format('d/M/Y') : '-' }}</td>
                        
                        <!-- 16. BA Date -->
                        <td>{{ $job->ba_date ? \Carbon\Carbon::parse($job->ba_date)->format('d/M/Y') : '-' }}</td>
                        
                        <!-- 17. Assign Date -->
                        <td>{{ $job->assign_date ? \Carbon\Carbon::parse($job->assign_date)->format('d/M/Y') : '-' }}</td>
                        
                        <!-- 18. Issue Date -->
                        <td>{{ $job->issue_date ? \Carbon\Carbon::parse($job->issue_date)->format('d/M/Y') : '-' }}</td>
                        
                        <!-- 19. Receive Date (Created At) -->
                        <td>
                            @if($job->created_at)
                                {{ $job->created_at->format('d/M/Y') }}<br>
                                at {{ $job->created_at->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        
                        <!-- 20. Kode Pos -->
                        <td>{{ $job->postal_code ?? '-' }}</td>
                        
                        <!-- 21. Area/Kecamatan -->
                        <td>{{ $job->district ?? '-' }}</td>
                        
                        <!-- 22. Op. Notes from Contract -->
                        <td>{{ $job->jobAdvice?->contract?->notes_operation ?? '-' }}</td>
                        
                        <!-- 23. Remark -->
                        <td>{{ \Illuminate\Support\Str::limit($job->internal_notes, 30) ?? '-' }}</td>
                        
                        <!-- Audit Columns (Moved to end) -->
                        <td>{{ $job->createdBy->name ?? '-' }}</td>
                        <td>
                            @if($job->created_at)
                                {{ $job->created_at->format('d/M/Y') }}<br>
                                at {{ $job->created_at->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $job->updatedBy->name ?? '-' }}</td>
                        <td>
                            @if($job->updated_at)
                                {{ $job->updated_at->format('d/M/Y') }}<br>
                                at {{ $job->updated_at->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ ($viewMode ?? 'job') === 'room' ? 27 : 25 }}" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No job schedules found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if(isset($jobSchedules) && $jobSchedules->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            {{ $jobSchedules->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Job Schedule</h2>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z" fill="#1e40af"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Hide Job Schedule</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this job schedule? This action can be undone later.</p>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" fill="#ef4444"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Hmm... Something Went Wrong</h3>
        <p class="delete-modal-description" id="errorMessage">We couldn't hide the job schedule. Please try again.</p>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" fill="#10b981"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">All Set!</h3>
        <p class="delete-modal-description" id="successMessage">The job schedule has been successfully hidden.</p>
    </div>
</div>

<!-- Force Majeure Modal -->
<div id="forceMajeureModal" class="modal-overlay hidden">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Report Force Majeure</h3>
            <button onclick="closeForceMajeureModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="forceMajeureForm">
                <input type="hidden" id="forceMajeureJobId" name="job_id">
                
                <div class="modal-section">
                    <div class="modal-section-title">Force Majeure Details</div>
                    <div class="form-group">
                        <label class="form-label">Force Majeure Type <span class="text-red-500">*</span></label>
                        <select name="force_majeure_status" id="forceMajeureStatus" class="form-input" required>
                            <option value="">Select Force Majeure Type</option>
                            <option value="technician_unavailable">Technician Unavailable</option>
                            <option value="material_shortage">Material Shortage</option>
                            <option value="weather">Weather Conditions</option>
                            <option value="emergency">Emergency</option>
                            <option value="equipment_failure">Equipment Failure</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reason <span class="text-red-500">*</span></label>
                        <textarea name="force_majeure_reason" id="forceMajeureReason" class="form-input" rows="3" placeholder="Please provide detailed reason..." required></textarea>
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title">Material Management</div>
                    <div class="form-group">
                        <label class="form-label">Material Status</label>
                        <select name="material_status" id="materialStatus" class="form-input">
                            <option value="none">No Material Issues</option>
                            <option value="pending_return">Pending Return</option>
                            <option value="returned">Returned</option>
                            <option value="lost">Lost</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Material Return Notes</label>
                        <textarea name="material_return_notes" id="materialReturnNotes" class="form-input" rows="2" placeholder="Additional notes about material status..."></textarea>
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title">Emergency Contact</div>
                    <div class="form-group">
                        <label class="form-label">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" id="emergencyContactName" class="form-input" placeholder="Contact person name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Emergency Contact Phone</label>
                        <input type="tel" name="emergency_contact_phone" id="emergencyContactPhone" class="form-input" placeholder="Contact phone number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Emergency Notes</label>
                        <textarea name="emergency_notes" id="emergencyNotes" class="form-input" rows="2" placeholder="Additional emergency information..."></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer" id="forceMajeureModalFooter">
            <button onclick="closeForceMajeureModal()" class="btn btn-secondary">Cancel</button>
            <button onclick="submitForceMajeure()" class="btn btn-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Report Force Majeure
            </button>
        </div>
    </div>
</div>

<!-- Reassign Modal -->
<div id="reassignModal" class="modal-overlay hidden">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Reassign Job</h3>
            <button onclick="closeReassignModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="reassignForm">
                <input type="hidden" id="reassignJobId" name="job_id">
                
                <div class="modal-section">
                    <div class="modal-section-title">Reassignment Details</div>
                    <div class="form-group">
                        <label class="form-label">Backup Technician <span class="text-red-500">*</span></label>
                        <select name="backup_technician_id" id="backupTechnicianId" class="form-input" required>
                            <option value="">Select Backup Technician</option>
                            @foreach($technicians as $technician)
                                <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reassignment Notes</label>
                        <textarea name="reassignment_notes" id="reassignmentNotes" class="form-input" rows="3" placeholder="Reason for reassignment..."></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer" id="reassignModalFooter">
            <button onclick="closeReassignModal()" class="btn btn-secondary">Cancel</button>
            <button onclick="submitReassign()" class="btn btn-info">
                <i class="fas fa-user-exchange"></i>
                Reassign Job
            </button>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div id="rescheduleModal" class="modal-overlay hidden">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Reschedule Job</h3>
            <button onclick="closeRescheduleModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="rescheduleForm">
                <input type="hidden" id="rescheduleJobId" name="job_id">
                
                <div class="modal-section">
                    <div class="modal-section-title">Reschedule Details</div>
                    <div class="form-group">
                        <label class="form-label">New Schedule Date <span class="text-red-500">*</span></label>
                        <input type="date" name="reschedule_date" id="rescheduleDate" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Schedule Time</label>
                        <input type="time" name="reschedule_time" id="rescheduleTime" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reschedule Reason <span class="text-red-500">*</span></label>
                        <textarea name="reschedule_reason" id="rescheduleReason" class="form-input" rows="3" placeholder="Reason for rescheduling..." required></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer" id="rescheduleModalFooter">
            <button onclick="closeRescheduleModal()" class="btn btn-secondary">Cancel</button>
            <button onclick="submitReschedule()" class="btn btn-secondary">
                <i class="fas fa-calendar-alt"></i>
                Reschedule Job
            </button>
        </div>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;
const viewMode = '{{ $viewMode ?? 'job' }}';

// Function to format date with 3-digit month
function formatDateWithThreeDigitMonth(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(3, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
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
    // Function disabled - Job Schedules are auto-generated from Job Advice
    console.log('openCreateModal called - DISABLED');
    return;
    
    openModal('Create New Job Schedule');
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Job Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Job Advice *</label>
                        <select name="job_advice_id" class="form-input" required>
                            <option value="">Select Job Advice</option>
                            @foreach($job_advices ?? [] as $advice)
                                <option value="{{ $advice->id }}">{{ $advice->job_advice_number }} - {{ $advice->customer->name ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Job Type *</label>
                        <select name="type" class="form-input" required>
                            <option value="">Select Job Type</option>
                            <option value="install">Install</option>
                            <option value="service">Service</option>
                            <option value="removal">Removal</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="trial">Trial</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Building *</label>
                        <select name="building_id" class="form-input" required onchange="loadFloorsForJobSchedule(this.value)">
                            <option value="">Select Building</option>
                            @foreach($buildings ?? [] as $building)
                                <option value="{{ $building->id }}">{{ $building->nama_gedung }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Floor</label>
                        <select name="floor_id" class="form-input" onchange="loadUnitsForJobSchedule(this.value)">
                            <option value="">Select Floor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit</label>
                        <select name="unit_id" class="form-input" onchange="loadRoomsForJobSchedule(this.value)">
                            <option value="">Select Unit (Optional)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room</label>
                        <select name="room_id" class="form-input">
                            <option value="">Select Room</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Schedule Details</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Schedule Date *</label>
                        <input type="date" name="schedule_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expected Date</label>
                        <input type="date" name="expected_date" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assigned Technician</label>
                        <select name="assigned_technician_id" class="form-input">
                            <option value="">Select Technician</option>
                            @foreach($technicians ?? [] as $technician)
                                <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <option value="new_job">New Job</option>
                            <option value="assign_team">Assign Team</option>
                            <option value="assign_material">Assign Material/Barang</option>
                            <option value="barang_dipersiapkan">Barang dipersiapkan</option>
                            <option value="barang_siap_diambil">Barang siap diambil</option>
                            <option value="barang_diambil">Barang diambil</option>
                            <option value="teknisi_tiba_dilokasi">Teknisi tiba dilokasi</option>
                            <option value="teknisi_sedang_pengerjaan">Teknisi sedang pengerjaan</option>
                            <option value="teknisi_selesai_pengerjaan">Teknisi selesai pengerjaan</option>
                            <option value="meninggalkan_lokasi">Meninggalkan lokasi</option>
                            <option value="done_job" disabled>Done Job (setelah On Progress)</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Service Frequency</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Service Frequency</label>
                        <select name="service_frequency" class="form-input" onchange="calculateServiceInterval()">
                            <option value="">Select Frequency</option>
                            <option value="1">Once per month</option>
                            <option value="2">Twice per month</option>
                            <option value="3">Three times per month</option>
                            <option value="4">Four times per month</option>
                            <option value="6">Every 2 months</option>
                            <option value="9">Every 3 months</option>
                            <option value="12">Every 4 months</option>
                            <option value="15">Every 5 months</option>
                            <option value="18">Every 6 months</option>
                            <option value="36">Once per year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Service Period Type</label>
                        <select name="service_period_type" class="form-input">
                            <option value="monthly">Monthly</option>
                            <option value="bi_monthly">Bi-Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="semi_annually">Semi-Annually</option>
                            <option value="annually">Annually</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Additional Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Period</label>
                        <input type="text" name="period" class="form-input" placeholder="Enter period">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="reference_number" class="form-input" placeholder="Enter reference number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Day</label>
                        <input type="text" name="day" class="form-input" placeholder="Enter day">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Postal Code</label>
                        <input type="text" name="postal_code" class="form-input" placeholder="Enter postal code">
                    </div>
                    <div class="form-group">
                        <label class="form-label">District</label>
                        <input type="text" name="district" class="form-input" placeholder="Enter district">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sub District</label>
                        <input type="text" name="sub_district" class="form-input" placeholder="Enter sub district">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Internal Notes</label>
                    <textarea name="internal_notes" class="form-input form-textarea" placeholder="Enter internal notes"></textarea>
                </div>
            </div>
        </form>
    `;
    
    // Add modal footer
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" form="form" class="btn btn-primary">Create Job Schedule</button>
    `;
}

function openViewModal(id) {
    openModal('View Job Schedule');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/operational/job-schedules/${id}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Job Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Job Number</label>
                            <p class="detail-value">${data.data.job_number || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Job Type</label>
                            <p class="detail-value">${data.data.type ? data.data.type.charAt(0).toUpperCase() + data.data.type.slice(1) : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value">
                                <span class="status-badge status-${data.data.status ? data.data.status.replace('_', '-') : 'scheduled'}">
                                    ${data.data.status ? data.data.status.charAt(0).toUpperCase() + data.data.status.slice(1).replace('_', ' ') : 'Scheduled'}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Contract Number</label>
                            <p class="detail-value">${data.data.contract_number || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Assignment Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Customer</label>
                            <p class="detail-value">${data.data.job_advice?.customer?.name || data.data.company_name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Building</label>
                            <p class="detail-value">${data.data.building?.nama_gedung || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Room</label>
                            <p class="detail-value">${data.data.room?.room_name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Assigned Technician</label>
                            <p class="detail-value">${data.data.assigned_technician?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Schedule Date</label>
                            <p class="detail-value">${data.data.schedule_date ? formatDateWithThreeDigitMonth(new Date(data.data.schedule_date)) : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Expected Date</label>
                            <p class="detail-value">${data.data.expected_date ? formatDateWithThreeDigitMonth(new Date(data.data.expected_date)) : '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Additional Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Internal Notes</label>
                            <p class="detail-value">${data.data.internal_notes || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Created By</label>
                            <p class="detail-value">${data.data.created_by?.name || '-'}</p>
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
            
            // Add modal footer for view modal with enhanced actions
            const materialButton = `
                <button type="button" class="btn btn-success" onclick='openMaterialAction(${data.data.id}, ${JSON.stringify(data.data.type || '')}, ${JSON.stringify(data.data.display_type || '')})' title="Materials">
                    <i class="fas fa-boxes"></i> Material Assign
                </button>
            `;

            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-primary" onclick="openTeamAssignmentModal(${data.data.id})" title="Assign Team">
                    <i class="fas fa-users"></i> Team Assign
                </button>
                ${materialButton}
                <button type="button" class="btn btn-warning" onclick="openForceMajeureModal(${data.data.id})" title="Report Force Majeure">
                    <i class="fas fa-exclamation-triangle"></i> Force Majeure
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
        });
}

function openEditModal(id) {
    // Function disabled - Job Schedules are auto-generated from Job Advice
    console.log('openEditModal called - DISABLED');
    return;
    openModal('Edit Job Schedule');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/operational/job-schedules/${id}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <div class="modal-section">
                        <div class="modal-section-title">Job Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Job Advice *</label>
                                <select name="job_advice_id" class="form-input" required>
                                    <option value="">Select Job Advice</option>
                                    @foreach($job_advices ?? [] as $advice)
                                        <option value="{{ $advice->id }}" ${data.data.job_advice_id == {{ $advice->id }} ? 'selected' : ''}>{{ $advice->job_advice_number }} - {{ $advice->customer->name ?? 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Job Type *</label>
                                <select name="type" class="form-input" required>
                                    <option value="">Select Job Type</option>
                                    <option value="install" ${data.data.type === 'install' ? 'selected' : ''}>Install</option>
                                    <option value="service" ${data.data.type === 'service' ? 'selected' : ''}>Service</option>
                                    <option value="removal" ${data.data.type === 'removal' ? 'selected' : ''}>Removal</option>
                                    <option value="maintenance" ${data.data.type === 'maintenance' ? 'selected' : ''}>Maintenance</option>
                                    <option value="trial" ${data.data.type === 'trial' ? 'selected' : ''}>Trial</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Building *</label>
                                <select name="building_id" class="form-input" required>
                                    <option value="">Select Building</option>
                                    @foreach($buildings ?? [] as $building)
                                        <option value="{{ $building->id }}" ${data.data.building_id == {{ $building->id }} ? 'selected' : ''}>{{ $building->nama_gedung }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Room</label>
                                <select name="room_id" class="form-input">
                                    <option value="">Select Room</option>
                                    @foreach($rooms ?? [] as $room)
                                        <option value="{{ $room->id }}" ${data.data.room_id == {{ $room->id }} ? 'selected' : ''}>{{ $room->room_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Schedule Details</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Schedule Date *</label>
                                <input type="date" name="schedule_date" class="form-input" value="${data.data.schedule_date ? new Date(data.data.schedule_date).toISOString().split('T')[0] : ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Expected Date</label>
                                <input type="date" name="expected_date" class="form-input" value="${data.data.expected_date ? new Date(data.data.expected_date).toISOString().split('T')[0] : ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Assigned Technician</label>
                                <select name="assigned_technician_id" class="form-input">
                                    <option value="">Select Technician</option>
                                    @foreach($technicians ?? [] as $technician)
                                        <option value="{{ $technician->id }}" ${data.data.assigned_technician_id == {{ $technician->id }} ? 'selected' : ''}>{{ $technician->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-input">
                                    <option value="new_job" ${data.data.status === 'new_job' || data.data.status === 'scheduled' ? 'selected' : ''}>New Job</option>
                                    <option value="assign_team" ${data.data.status === 'assign_team' ? 'selected' : ''}>Assign Team</option>
                                    <option value="assign_material" ${data.data.status === 'assign_material' ? 'selected' : ''}>Assign Material/Barang</option>
                                    <option value="barang_dipersiapkan" ${data.data.status === 'barang_dipersiapkan' ? 'selected' : ''}>Barang dipersiapkan</option>
                                    <option value="barang_siap_diambil" ${data.data.status === 'barang_siap_diambil' ? 'selected' : ''}>Barang siap diambil</option>
                                    <option value="barang_diambil" ${data.data.status === 'barang_diambil' ? 'selected' : ''}>Barang diambil</option>
                                    <option value="teknisi_tiba_dilokasi" ${data.data.status === 'teknisi_tiba_dilokasi' ? 'selected' : ''}>Teknisi tiba dilokasi</option>
                                    <option value="teknisi_sedang_pengerjaan" ${data.data.status === 'teknisi_sedang_pengerjaan' ? 'selected' : ''}>Teknisi sedang pengerjaan</option>
                                    <option value="teknisi_selesai_pengerjaan" ${data.data.status === 'teknisi_selesai_pengerjaan' ? 'selected' : ''}>Teknisi selesai pengerjaan</option>
                                    <option value="meninggalkan_lokasi" ${data.data.status === 'meninggalkan_lokasi' ? 'selected' : ''}>Meninggalkan lokasi</option>
                                    <option value="done_job" ${data.data.status === 'done_job' || data.data.status === 'completed' ? 'selected' : ''} ${['in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_selesai_pengerjaan', 'done_job', 'completed'].includes(data.data.status) ? '' : 'disabled'}>Done Job (setelah On Progress)</option>
                                    <option value="cancelled" ${data.data.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                    <option value="pending" ${data.data.status === 'pending' ? 'selected' : ''}>Pending</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Additional Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Period</label>
                                <input type="text" name="period" class="form-input" value="${data.data.period || ''}" placeholder="Enter period">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-input" value="${data.data.reference_number || ''}" placeholder="Enter reference number">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Day</label>
                                <input type="text" name="day" class="form-input" value="${data.data.day || ''}" placeholder="Enter day">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-input" value="${data.data.postal_code || ''}" placeholder="Enter postal code">
                            </div>
                            <div class="form-group">
                                <label class="form-label">District</label>
                                <input type="text" name="district" class="form-input" value="${data.data.district || ''}" placeholder="Enter district">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Sub District</label>
                                <input type="text" name="sub_district" class="form-input" value="${data.data.sub_district || ''}" placeholder="Enter sub district">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Internal Notes</label>
                            <textarea name="internal_notes" class="form-input form-textarea" placeholder="Enter internal notes">${data.data.internal_notes || ''}</textarea>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Job Schedule</button>
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
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/operational/job-schedules/${id}` : '/operational/job-schedules';
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
            showErrorDialog('Gagal menyimpan data: ' + (result.message || 'Terjadi kesalahan.'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Terjadi kesalahan sistem.');
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this job schedule? This action can be undone later.'
        : `Are you sure you want to hide ${count} job schedules? This action can be undone later.`;
    
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
    
    fetch('/operational/job-schedules/bulk-delete', {
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
        ? 'The job schedule has been successfully hidden.'
        : `${count} job schedules have been successfully hidden.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t hide the job schedule. Please try again.';
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

// Force Majeure Modal Functions
function openForceMajeureModal(jobId) {
    document.getElementById('forceMajeureJobId').value = jobId;
    document.getElementById('forceMajeureModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeForceMajeureModal() {
    document.getElementById('forceMajeureModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    document.getElementById('forceMajeureForm').reset();
}

function submitForceMajeure() {
    const form = document.getElementById('forceMajeureForm');
    const formData = new FormData(form);
    const jobId = document.getElementById('forceMajeureJobId').value;
    
    // Add CSRF token
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    fetch(`/operational/job-schedules/${jobId}/force-majeure`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeForceMajeureModal();
            showSuccessModal(1);
            // Reload page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorModal(data.message || 'Gagal melaporkan force majeure.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Network error occurred');
    });
}

// Reassign Modal Functions
function openReassignModal(jobId) {
    document.getElementById('reassignJobId').value = jobId;
    document.getElementById('reassignModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeReassignModal() {
    document.getElementById('reassignModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    document.getElementById('reassignForm').reset();
}

function submitReassign() {
    const form = document.getElementById('reassignForm');
    const formData = new FormData(form);
    const jobId = document.getElementById('reassignJobId').value;
    
    // Add CSRF token
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    fetch(`/operational/job-schedules/${jobId}/reassign`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeReassignModal();
            showSuccessModal(1);
            // Reload page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorModal(data.message || 'Error reassigning job');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Network error occurred');
    });
}

// Reschedule Modal Functions
function openRescheduleModal(jobId) {
    document.getElementById('rescheduleJobId').value = jobId;
    document.getElementById('rescheduleModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeRescheduleModal() {
    document.getElementById('rescheduleModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    document.getElementById('rescheduleForm').reset();
}

function submitReschedule() {
    const form = document.getElementById('rescheduleForm');
    const formData = new FormData(form);
    const jobId = document.getElementById('rescheduleJobId').value;
    
    // Add CSRF token
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    fetch(`/operational/job-schedules/${jobId}/reschedule`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeRescheduleModal();
            showSuccessModal(1);
            // Reload page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorModal(data.message || 'Error rescheduling job');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Network error occurred');
    });
}

// Load floors for Job Schedule
function loadFloorsForJobSchedule(buildingId, selectedFloorId = null) {
    const floorSelect = document.querySelector('select[name="floor_id"]');
    const unitSelect = document.querySelector('select[name="unit_id"]');
    const roomSelect = document.querySelector('select[name="room_id"]');
    
    // Reset dependent dropdowns
    if (floorSelect) floorSelect.innerHTML = '<option value="">Select Floor</option>';
    if (unitSelect) unitSelect.innerHTML = '<option value="">Select Unit (Optional)</option>';
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room</option>';
    
    if (!buildingId) return;
    
    fetch(`/api/buildings/${buildingId}/floors`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && floorSelect) {
                data.data.forEach(floor => {
                    const option = document.createElement('option');
                    option.value = floor.id;
                    option.textContent = `Floor ${floor.floor_number} - ${floor.floor_name || 'No Name'}`;
                    if (selectedFloorId && floor.id == selectedFloorId) {
                        option.selected = true;
                    }
                    floorSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading floors:', error));
}

// Load units for Job Schedule
function loadUnitsForJobSchedule(floorId, selectedUnitId = null) {
    const unitSelect = document.querySelector('select[name="unit_id"]');
    const roomSelect = document.querySelector('select[name="room_id"]');
    
    // Reset dependent dropdowns
    if (unitSelect) unitSelect.innerHTML = '<option value="">Select Unit (Optional)</option>';
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room</option>';
    
    if (!floorId) return;
    
    fetch(`/api/floors/${floorId}/units`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && unitSelect) {
                data.data.forEach(unit => {
                    const option = document.createElement('option');
                    option.value = unit.id;
                    option.textContent = `Unit ${unit.unit_number} - ${unit.unit_name}`;
                    if (selectedUnitId && unit.id == selectedUnitId) {
                        option.selected = true;
                    }
                    unitSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading units:', error));
}

// Load rooms for Job Schedule
function loadRoomsForJobSchedule(unitId, selectedRoomId = null) {
    const roomSelect = document.querySelector('select[name="room_id"]');
    
    // Reset room dropdown
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room</option>';
    
    if (!unitId) return;
    
    fetch(`/api/units/${unitId}/rooms`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && roomSelect) {
                data.data.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = `${room.room_name} - ${room.room_code || ''}`;
                    if (selectedRoomId && room.id == selectedRoomId) {
                        option.selected = true;
                    }
                    roomSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading rooms:', error));
    }

    // Enhanced Job Management Functions
    function openTeamAssignmentModal(jobScheduleId) {
        // Direct redirect to team assignment page
        window.location.href = `/operational/job-schedules/${jobScheduleId}/assignments`;
    }

    function openMaterialModal(jobScheduleId) {
        // Direct redirect to materials page
        window.location.href = `/operational/job-schedules/${jobScheduleId}/materials`;
    }

    function openMaterialAction(jobScheduleId, type = '', displayType = '') {
        if (skipsMaterialAssignment(type, displayType)) {
            Swal.fire({
                title: 'Aksi Tidak Sesuai',
                text: 'Job Check/Remove tidak menggunakan alur material. Silakan gunakan Assign Team atau Unassign Team sesuai kebutuhan.',
                icon: 'warning',
                confirmButtonColor: '#214589'
            });
            return;
        }

        openMaterialModal(jobScheduleId);
    }

    function openForceMajeureModal(jobScheduleId) {
        // Open Force Majeure modal
        openModal('Report Force Majeure');
        document.getElementById('modalBody').innerHTML = `
            <form id="forceMajeureForm" onsubmit="reportForceMajeure(event, ${jobScheduleId})">
                <div class="form-group">
                    <label class="form-label">Force Majeure Type *</label>
                    <select name="force_majeure_type" class="form-input" required>
                        <option value="">Select Type</option>
                        <option value="weather">Weather</option>
                        <option value="equipment_failure">Equipment Failure</option>
                        <option value="access_denied">Access Denied</option>
                        <option value="safety_concern">Safety Concern</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="force_majeure_description" class="form-input" rows="4" required placeholder="Describe the force majeure situation"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Expected Resolution</label>
                    <input type="datetime-local" name="expected_resolution" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Additional Notes</label>
                    <textarea name="additional_notes" class="form-input" rows="3" placeholder="Any additional information"></textarea>
                </div>
            </form>
        `;
        
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" form="forceMajeureForm" class="btn btn-warning">Report Force Majeure</button>
        `;
    }

    function reportForceMajeure(event, jobScheduleId) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        fetch(`/operational/job-schedules/${jobScheduleId}/force-majeure`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                force_majeure_type: formData.get('force_majeure_type'),
                force_majeure_description: formData.get('force_majeure_description'),
                expected_resolution: formData.get('expected_resolution'),
                additional_notes: formData.get('additional_notes')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('Laporan force majeure berhasil dikirim.', 'success');
                closeModal();
                loadData();
            } else {
                showNotification(data.message || 'Gagal melaporkan force majeure.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Gagal melaporkan force majeure.', 'error');
        });
    }

    function openAddMaterialModal(jobScheduleId) {
        openModal('Add Material Requirement');
        document.getElementById('modalBody').innerHTML = `
            <form id="addMaterialForm" onsubmit="addMaterial(event, ${jobScheduleId})">
                <div class="modal-section">
                    <div class="modal-section-title">Material Information</div>
                    <div class="form-group">
                        <label class="form-label">Product *</label>
                        <select name="master_product_id" class="form-input" required>
                            <option value="">Select Product</option>
                            <!-- Products will be loaded via AJAX -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Required Quantity *</label>
                        <input type="number" name="required_quantity" class="form-input" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="3" placeholder="Optional notes for this material"></textarea>
                    </div>
                </div>
            </form>
        `;
        
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" form="addMaterialForm" class="btn btn-primary">Add Material</button>
        `;
        
        // Load products
        loadProducts();
    }

    function openCreatePeriodicJobModal() {
        // Close any existing modals first
        const existingModal = document.getElementById('modal');
        if (existingModal) {
            existingModal.style.display = 'none';
        }
        
        openModal('Create Periodic Job');
        document.getElementById('modalBody').innerHTML = `
            <form id="createPeriodicJobForm" onsubmit="createPeriodicJob(event)">
                <div class="modal-section">
                    <div class="modal-section-title">Periodic Job Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Contract *</label>
                            <select name="contract_id" class="form-input" required>
                                <option value="">Select Contract</option>
                                <!-- Contracts will be loaded via AJAX -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Building *</label>
                            <select name="building_id" class="form-input" required disabled>
                                <option value="">Will be auto-filled from contract</option>
                                <!-- Buildings will be loaded automatically from selected contract -->
                            </select>
                            <small class="text-gray-500">Building will be automatically selected based on the contract</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Master Rental *</label>
                            <select name="master_rental_id" class="form-input" required disabled>
                                <option value="">Will be auto-filled from contract</option>
                                <!-- Master Rentals will be loaded automatically from selected contract -->
                            </select>
                            <small class="text-gray-500">Master Rental will be automatically selected based on the contract</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Job Type *</label>
                            <select name="job_type" class="form-input" required>
                                <option value="">Select Job Type</option>
                                <option value="install">Install</option>
                                <option value="service">Service</option>
                                <option value="remove">Remove</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Service Frequency (Months) *</label>
                            <input type="number" name="service_frequency_months" class="form-input" min="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Start Date *</label>
                            <input type="date" name="start_date" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Auto Generate</label>
                            <select name="auto_generate" class="form-input">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="3" placeholder="Optional notes for this periodic job"></textarea>
                    </div>
                </div>
            </form>
        `;
        
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" form="createPeriodicJobForm" class="btn btn-primary">Create Periodic Job</button>
        `;
        
        // Load contracts dropdown
        loadContracts();
    }

    // AJAX Functions
    function loadTeams() {
        fetch('/api/v1/operational/teams')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const select = document.querySelector('select[name="team_id"]');
                if (select) {
                    select.innerHTML = '<option value="">Select Team</option>';
                    if (data.data && Array.isArray(data.data)) {
                        data.data.forEach(team => {
                            select.innerHTML += `<option value="${team.id}">${team.team_name}</option>`;
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error loading teams:', error);
                const select = document.querySelector('select[name="team_id"]');
                if (select) {
                    select.innerHTML = '<option value="">Error loading teams</option>';
                }
            });
    }

    function loadProducts() {
        fetch('/api/v1/warehouse/master-products')
            .then(response => response.json())
            .then(data => {
                const select = document.querySelector('select[name="master_product_id"]');
                select.innerHTML = '<option value="">Select Product</option>';
                data.data.forEach(product => {
                    select.innerHTML += `<option value="${product.id}">${product.name}</option>`;
                });
            })
            .catch(error => console.error('Error loading products:', error));
    }

    function loadJobMaterials(jobScheduleId) {
        fetch(`/operational/job-schedules/${jobScheduleId}/materials`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const container = document.getElementById('materialsList');
                if (container) {
                    if (data.data && data.data.length > 0) {
                        container.innerHTML = data.data.map(material => `
                            <div class="border rounded p-3 mb-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="font-semibold">${material.master_product ? material.master_product.name : 'Unknown Product'}</h4>
                                        <p class="text-sm text-gray-600">Required: ${material.required_quantity || 0} | Issued: ${material.issued_quantity || 0} | Used: ${material.used_quantity || 0}</p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="issueMaterial(${material.id})" class="btn btn-sm btn-primary">Issue</button>
                                        <button onclick="returnMaterial(${material.id})" class="btn btn-sm btn-secondary">Return</button>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<p class="text-center text-gray-500">No materials found</p>';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading materials:', error);
                const container = document.getElementById('materialsList');
                if (container) {
                    container.innerHTML = '<p class="text-center text-red-500">Error loading materials</p>';
                }
            });
    }

    function loadContracts() {
        fetch('/api/contracts/dropdown', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
                const select = document.querySelector('select[name="contract_id"]');
                select.innerHTML = '<option value="">Select Contract</option>';
                if (data.data && Array.isArray(data.data)) {
                    data.data.forEach(contract => {
                        select.innerHTML += `<option value="${contract.id}">${contract.contract_number} - ${contract.customer_name}</option>`;
                    });
                }
            })
            .catch(error => console.error('Error loading contracts:', error));
    }

    // Function to load contract details and auto-populate building and rental data
    function loadContractDetails(contractId) {
        if (!contractId) {
            // Clear building and rental fields if no contract selected
            const buildingSelect = document.querySelector('select[name="building_id"]');
            const rentalSelect = document.querySelector('select[name="master_rental_id"]');
            if (buildingSelect) {
                buildingSelect.innerHTML = '<option value="">Select Building</option>';
                buildingSelect.disabled = false;
            }
            if (rentalSelect) {
                rentalSelect.innerHTML = '<option value="">Select Master Rental</option>';
                rentalSelect.disabled = false;
            }
            return;
        }

        fetch(`/api/contracts/${contractId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
                // Auto-populate building data from contract
                const buildingSelect = document.querySelector('select[name="building_id"]');
                if (buildingSelect && data.building) {
                    buildingSelect.innerHTML = '<option value="">Select Building</option>';
                    buildingSelect.innerHTML += `<option value="${data.building.id}" selected>${data.building.nama_gedung}</option>`;
                    buildingSelect.disabled = true; // Make readonly since it's from contract
                }

                // Auto-populate rental data from contract
                const rentalSelect = document.querySelector('select[name="master_rental_id"]');
                if (rentalSelect && data.rentals && data.rentals.length > 0) {
                    rentalSelect.innerHTML = '<option value="">Select Master Rental</option>';
                    data.rentals.forEach(rental => {
                        rentalSelect.innerHTML += `<option value="${rental.id}">${rental.rental_name}</option>`;
                    });
                    rentalSelect.disabled = true; // Make readonly since it's from contract
                }
            })
            .catch(error => console.error('Error loading contract details:', error));
    }

    // Add event listener for contract selection
    document.addEventListener('DOMContentLoaded', function() {
        const contractSelect = document.querySelector('select[name="contract_id"]');
        if (contractSelect) {
            contractSelect.addEventListener('change', function() {
                loadContractDetails(this.value);
            });
        }
    });

    function loadBuildings() {
        fetch('/api/buildings', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
                const select = document.querySelector('select[name="building_id"]');
                select.innerHTML = '<option value="">Select Building</option>';
                if (data.data && data.data.data && Array.isArray(data.data.data)) {
                    // Handle paginated response
                    data.data.data.forEach(building => {
                        select.innerHTML += `<option value="${building.id}">${building.nama_gedung}</option>`;
                    });
                } else if (data.data && Array.isArray(data.data)) {
                    // Handle direct array response
                    data.data.forEach(building => {
                        select.innerHTML += `<option value="${building.id}">${building.nama_gedung}</option>`;
                    });
                }
            })
            .catch(error => console.error('Error loading buildings:', error));
    }

    function loadMasterRentals() {
        fetch('/api/master-rentals', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
                const select = document.querySelector('select[name="master_rental_id"]');
                select.innerHTML = '<option value="">Select Master Rental</option>';
                if (data.data && Array.isArray(data.data)) {
                    data.data.forEach(rental => {
                        select.innerHTML += `<option value="${rental.id}">${rental.rental_name}</option>`;
                    });
                }
            })
            .catch(error => console.error('Error loading master rentals:', error));
    }

    // Form Submission Functions
    function assignTeam(event, jobScheduleId) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        fetch(`/operational/job-schedules/${jobScheduleId}/assign-team`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                team_id: formData.get('team_id'),
                assignment_notes: formData.get('assignment_notes')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('Team berhasil ditugaskan.', 'success');
                closeModal();
                loadData();
            } else {
                showNotification(data.message || 'Gagal menugaskan team.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Gagal menugaskan team.', 'error');
        });
    }

    function addMaterial(event, jobScheduleId) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        fetch(`/operational/job-schedules/${jobScheduleId}/materials`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                master_product_id: formData.get('master_product_id'),
                required_quantity: formData.get('required_quantity'),
                notes: formData.get('notes')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('Material berhasil ditambahkan.', 'success');
                closeModal();
                loadJobMaterials(jobScheduleId);
            } else {
                showNotification(data.message || 'Gagal menambahkan material.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Gagal menambahkan material.', 'error');
        });
    }

    function createPeriodicJob(event) {
        event.preventDefault();
        
        // Enable disabled fields temporarily to get their values
        const buildingSelect = document.querySelector('select[name="building_id"]');
        const rentalSelect = document.querySelector('select[name="master_rental_id"]');
        
        if (buildingSelect) buildingSelect.disabled = false;
        if (rentalSelect) rentalSelect.disabled = false;
        
        const formData = new FormData(event.target);
        
        // Re-disable the fields
        if (buildingSelect) buildingSelect.disabled = true;
        if (rentalSelect) rentalSelect.disabled = true;
        
        fetch('/operational/periodic-jobs', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                contract_id: formData.get('contract_id'),
                building_id: formData.get('building_id'),
                master_rental_id: formData.get('master_rental_id'),
                job_type: formData.get('job_type'),
                service_frequency_months: formData.get('service_frequency_months'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date'),
                auto_generate: formData.get('auto_generate'),
                notes: formData.get('notes')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('Periodic job created successfully!', 'success');
                closeModal();
                loadData();
            } else {
                showNotification(data.message || 'Error creating periodic job', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error creating periodic job', 'error');
        });
    }

    async function generatePeriodicJobs() {
        const result = await showConfirmDialog({
            title: 'Generate Job Periodik?',
            text: 'Aksi ini akan membuat job schedule periodik baru.',
            icon: 'question',
            confirmButtonText: 'Ya, generate',
            cancelButtonText: 'Batal'
        });

        if (!result.isConfirmed) {
            return;
        }
        
        fetch('/operational/periodic-jobs/generate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showNotification('success', `Berhasil membuat ${data.data.generated_count} job periodik.`);
                loadData();
            } else {
                showNotification('error', data.message || 'Gagal membuat job periodik.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Gagal membuat job periodik.');
        });
    }

    async function issueMaterial(materialId) {
        const result = await Swal.fire({
            title: 'Masukkan Jumlah Issue',
            input: 'number',
            inputLabel: 'Jumlah material yang akan di-issue',
            inputAttributes: {
                min: 1,
                step: 1
            },
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value || isNaN(value) || Number(value) <= 0) {
                    return 'Masukkan jumlah yang valid.';
                }
            }
        });

        const quantity = result.value;
        if (result.isConfirmed && quantity && !isNaN(quantity)) {
            fetch(`/operational/job-materials/${materialId}/issue`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    issued_quantity: parseInt(quantity),
                    received_by: 1 // This should be the current user ID
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification('success', 'Material berhasil di-issue.');
                } else {
                    showNotification('error', data.message || 'Gagal issue material.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Gagal issue material.');
            });
        }
    }

    async function returnMaterial(materialId) {
        const result = await Swal.fire({
            title: 'Masukkan Jumlah Return',
            input: 'number',
            inputLabel: 'Jumlah material yang akan dikembalikan',
            inputAttributes: {
                min: 1,
                step: 1
            },
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value || isNaN(value) || Number(value) <= 0) {
                    return 'Masukkan jumlah yang valid.';
                }
            }
        });

        const quantity = result.value;
        if (result.isConfirmed && quantity && !isNaN(quantity)) {
            fetch(`/operational/job-materials/${materialId}/return`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    returned_quantity: parseInt(quantity),
                    notes: 'Returned via web interface'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification('success', 'Material berhasil dikembalikan.');
                } else {
                    showNotification('error', data.message || 'Gagal mengembalikan material.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Gagal mengembalikan material.');
            });
        }
    }

    // Filter and Action Functions


    // Add listener to row checkboxes to uncheck 'Select All' if one is unchecked
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            if (selectAll) { 
                selectAll.checked = allChecked; 
                selectAll.indeterminate = !allChecked && Array.from(checkboxes).some(cb => cb.checked);
            }
            updateApplyButton();
        }
    });

    function applyFilters() {
        const params = new URLSearchParams(window.location.search); // Start with existing params
        
        // Remove existing date/column filters to avoid duplicates if re-adding
        const preservedParams = ['view_mode', 'per_page', 'sort', 'direction'];
        const newParams = new URLSearchParams();
        preservedParams.forEach(key => {
            if (params.has(key)) newParams.set(key, params.get(key));
        });
        
        // Date Filters
        const dateFrom = document.getElementById('filterDateFrom')?.value;
        const dateTo = document.getElementById('filterDateTo')?.value;
        if (dateFrom) newParams.set('date_from', dateFrom);
        if (dateTo) newParams.set('date_to', dateTo);
        
        // Column Filters (The new inputs)
        const outputParams = new URLSearchParams(newParams);
        document.querySelectorAll('.column-filter').forEach(input => {
            const val = input.value.trim();
            const col = input.getAttribute('data-column');
            if (val && col) {
                outputParams.set(`filter[${col}]`, val);
            }
        });

        window.location.href = '{{ route("operational.job-schedules.index") }}?' + outputParams.toString();
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const headerSelectAll = document.getElementById('headerSelectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');
        
        const isChecked = selectAll?.checked || headerSelectAll?.checked;
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        
        if (selectAll) selectAll.checked = isChecked;
        if (headerSelectAll) headerSelectAll.checked = isChecked;
        
        updateProposeTeamButton();
        updateApplyButton();
    }

    function updateProposeTeamButton() {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        const btnProposeTeam = document.getElementById('btnProposeTeam');
        const teamCode = document.getElementById('filterTeamCode')?.value;
        const actionSelect = document.getElementById('actionType');
        const materialAssignOption = actionSelect?.querySelector('option[value="material_assign"]');
        
        if (btnProposeTeam) {
            btnProposeTeam.disabled = !(checkboxes.length > 0 && teamCode);
        }

        if (materialAssignOption) {
            materialAssignOption.disabled = false;
            materialAssignOption.title = '';
        }
    }

    function updateApplyButton() {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        const btnApplySuspendDpf = document.getElementById('btnApplySuspendDpf');
        const actionType = document.getElementById('actionType')?.value;
        
        if (btnApplySuspendDpf) {
            btnApplySuspendDpf.disabled = !(checkboxes.length > 0 && actionType);
        }
    }

    function updateApplyButton() {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        const btnApplySuspendDpf = document.getElementById('btnApplySuspendDpf');
        const actionType = document.getElementById('actionType')?.value;
        
        if (btnApplySuspendDpf) {
            btnApplySuspendDpf.disabled = !(checkboxes.length > 0 && actionType);
        }
    }

    async function proposeTeam() {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        const teamSelect = document.getElementById('filterTeamCode');
        
        if (checkboxes.length === 0) {
            showWarningDialog('Pilih minimal satu job schedule.');
            return;
        }
        
        if (!teamSelect || !teamSelect.value) {
            showWarningDialog('Pilih team terlebih dahulu.');
            return;
        }
        
        const selectedOption = teamSelect.options[teamSelect.selectedIndex];
        const teamId = selectedOption.value;
        const teamName = selectedOption.getAttribute('data-team-name') || selectedOption.text; // MOM8: use team_name, not team_code
        
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);
        
        const confirmation = await showConfirmDialog({
            title: 'Ajukan Team?',
            text: `Assign team ${teamName} ke ${selectedIds.length} job schedule terpilih?`,
            icon: 'question',
            confirmButtonText: 'Ya, assign',
            cancelButtonText: 'Batal'
        });

        if (!confirmation.isConfirmed) {
            return;
        }
        
        // Create JobAssignSchedule for each selected job schedule
        Promise.all(selectedIds.map(jobScheduleId => {
            return fetch(`/operational/job-assign-schedules`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    job_schedule_id: jobScheduleId,
                    team_id: teamId,
                    assigned_date: new Date().toISOString().split('T')[0],
                    notes: `Proposed via job schedule list`
                })
            });
        }))
        .then(responses => Promise.all(responses.map(r => r.json())))
        .then(results => {
            const successCount = results.filter(r => r.status === 'success').length;
            if (successCount === selectedIds.length) {
                showSuccessDialog(`Team ${teamName} berhasil di-assign ke ${successCount} job schedule.`, 'Berhasil').then(() => location.reload());
            } else {
                const errors = results.filter(r => r.status !== 'success').map(r => r.message || 'Terjadi kesalahan yang tidak diketahui').join(', ');
                showWarningDialog(`Berhasil sebagian: ${successCount} dari ${selectedIds.length} assignment berhasil dibuat. Detail: ${errors}`, 'Berhasil Sebagian');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal assign team. Silakan coba lagi.');
        });
    }

    function isRemoveJobType(type) {
        return ['remove', 'remove_free', 'remove free', 'removal'].includes((type || '').toString().toLowerCase().trim());
    }

    function isCheckJobType(type, displayType = '') {
        const normalizedType = (type || '').toString().toLowerCase().trim();
        const normalizedDisplayType = (displayType || '').toString().toLowerCase().trim();

        return normalizedType === 'check'
            || normalizedDisplayType === 'check'
            || normalizedDisplayType.includes('check')
            || normalizedDisplayType.includes('chk');
    }

    function skipsMaterialAssignment(type, displayType = '') {
        return isRemoveJobType(type) || isCheckJobType(type, displayType);
    }

    function getSelectedJobTypes() {
        return Array.from(document.querySelectorAll('.row-checkbox:checked'))
            .map(cb => ({
                type: cb.getAttribute('data-type') || '',
                displayType: cb.getAttribute('data-display-type') || ''
            }))
            .filter(Boolean);
    }

    function applySuspendDpf() {
        const actionSelect = document.getElementById('actionType');
        const actionType = actionSelect.value;
        const viewMode = new URLSearchParams(window.location.search).get('view_mode') || 'job';

        // Determine IDs based on action scope
        let ids = [];
        // Define which actions are job-level (should use Job IDs even in Room View)
        const jobLevelActions = ['unassign_team', 'unpost_issue', 'unpost_ba', 'suspend', 'unsuspend', 'dpf', 'material_assign', 'unassign_material'];
        
        // Capture specific room IDs selected in the main table.
        // In Job View one row can visually represent multiple JobScheduleRoom rows.
        const selectedRoomIdsFromTable = [...new Set(Array.from(document.querySelectorAll('.row-checkbox:checked'))
            .flatMap(cb => {
                if (viewMode === 'room') {
                    return [cb.value];
                }

                const roomIds = (cb.getAttribute('data-room-ids') || '')
                    .split(',')
                    .map(id => id.trim())
                    .filter(Boolean);

                return (actionType === 'material_assign' || actionType === 'unassign_material' || actionType.startsWith('assign_team_')) ? roomIds : [];
            }))];
        
        if (viewMode === 'room') {
            if (jobLevelActions.includes(actionType)) {
                // Job-level actions in Room View use Job IDs (data-job-id), deduplicated
                const rawIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.getAttribute('data-job-id'));
                ids = [...new Set(rawIds)];
            } else {
                // Room-level actions (including team assignment) use Room IDs
                ids = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
            }
        } else {
             // Job View: all actions use Job IDs
             const rawIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
             ids = [...new Set(rawIds)];
        }

        if (!actionType) return;

        if (ids.length === 0) {
            Swal.fire('Gagal', 'Silakan pilih setidaknya satu item.', 'error');
            actionSelect.value = "";
            return;
        }

        const selectedJobTypes = getSelectedJobTypes();
        const hasSelectedMateriallessJob = selectedJobTypes.some(item => skipsMaterialAssignment(item.type, item.displayType));

        if (['material_assign', 'unpost_issue', 'unassign_material'].includes(actionType) && hasSelectedMateriallessJob) {
            const actionLabels = {
                material_assign: 'Material Assign',
                unpost_issue: 'Unpost Issue',
                unassign_material: 'UnAssign Material'
            };

            Swal.fire({
                title: 'Aksi Tidak Sesuai',
                text: `Job Check/Remove tidak menggunakan alur material, sehingga ${actionLabels[actionType]} tidak dapat dilakukan. Silakan gunakan Assign Team atau Unassign Team sesuai kebutuhan.`,
                icon: 'warning',
                confirmButtonColor: '#214589'
            });
            actionSelect.value = "";
            return;
        }

            // Handle Print Action (was Print CSR)
        if (actionType === 'print_csr') {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            let invalidCount = 0;
            
            for (const cb of checkedBoxes) {
                const status = cb.getAttribute('data-status');
                
                const statusLower = (status || '').toLowerCase();

                const isPrintable = !['cancelled', 'terminated'].includes(statusLower);
                
                if (!isPrintable) {
                    invalidCount++;
                }
            }

            if (invalidCount > 0) {
                Swal.fire({
                    title: 'Validasi Gagal',
                    text: 'Laporan cetak tidak bisa dibuat untuk job Cancelled/Terminated.',
                    icon: 'warning'
                });
                actionSelect.value = "";
                return;
            }

            // If valid, open print route in new tab
            const idString = ids.join(',');
            // Using a simple GET route for prototype or form submit for POST
            // Let's use window.open with query param for simplicity first
            const url = `{{ route('operational.job-schedules.print-csr') }}?ids=${idString}&view_mode=${viewMode}`;
            window.open(url, '_blank');
            
            actionSelect.value = ""; // Reset dropdown
            return;
        }

        try {
            // 1. Handle Team Assignment (Dynamic Action)
            if (actionType.startsWith('assign_team_')) {
                const teamId = actionType.replace('assign_team_', '');
                const selectedOption = actionSelect.options[actionSelect.selectedIndex];
                const teamName = selectedOption.getAttribute('data-team-name') || selectedOption.text.trim(); 
                
                // For room view, use simple room selection logic 
                if (viewMode === 'room') {
                    const willAssign = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => ({
                        room_name: cb.getAttribute('data-room-name') || 'Unknown',
                        current_team: cb.getAttribute('data-current-team') || 'Unassigned'
                    }));

                    let html = `
                        <div class="text-left">
                            <p class="mb-2"><strong>Team yang di assign adalah:</strong></p>
                            <p class="text-lg font-bold text-blue-600 mb-4">${teamName}</p>
                            
                            <p class="mb-2"><strong>Room yang terpilih (${willAssign.length}):</strong></p>
                            <ul class="list-disc pl-5 mb-4 text-sm max-h-48 overflow-y-auto">
                                ${willAssign.map(i => `<li>${i.room_name} (Current: ${i.current_team})</li>`).join('')}
                            </ul>
                        </div>
                    `;

                    Swal.fire({
                        title: 'Konfirmasi Assign Room',
                        html: html,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Konfirmasi Assign',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#214589',
                        width: '600px'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            processBatchActions(ids, 'assign_team_room', { 
                                team_id: teamId 
                            });
                        } else {
                            actionSelect.value = "";
                        }
                    });
                    return;
                }

                // For Job View (default) - Use Check Bulk Assignments
                Swal.fire({
                    title: 'Memeriksa Assignment...',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                const assignmentCheckPayload = { job_ids: ids };
                if (selectedRoomIdsFromTable.length > 0) {
                    assignmentCheckPayload.selected_room_ids = selectedRoomIdsFromTable;
                    assignmentCheckPayload.strict_selection = true;
                }

                fetch('{{ route("operational.job-schedules.check-bulk-assignments") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(assignmentCheckPayload)
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.status === 'success') {
                        const rooms = data.data;
                        const groupedRooms = [];
                        const groupedRoomMap = new Map();

                        rooms.forEach(i => {
                            const key = [
                                i.job_number || 'No Job No',
                                i.room_name || '',
                                i.job_status || '',
                                i.status || '',
                                i.team_id || ''
                            ].join('|');

                            if (!groupedRoomMap.has(key)) {
                                const groupedItem = {
                                    ...i,
                                    related_room_ids: [i.id]
                                };
                                groupedRoomMap.set(key, groupedItem);
                                groupedRooms.push(groupedItem);
                                return;
                            }

                            groupedRoomMap.get(key).related_room_ids.push(i.id);
                        });

                        const allItems = groupedRooms.map(i => {
                            const isDone = i.job_status === 'done_job' || i.job_status === 'completed';
                            
                            // MOM: if status is 'can_reassign', it means team is assigned but material NOT yet checked by tech.
                            // So we allow re-assignment by NOT disabling it.
                            let checked = false;
                            if (i.status === 'will_assign') checked = !isDone;
                            if (i.status === 'can_reassign') checked = false; // user must manually check to reassign

                            return {
                                ...i,
                                checked: checked,
                                isDone: isDone
                            };
                        });

                        let html = `
                            <div class="text-left">
                                <p class="mb-2"><strong>Team yang di assign adalah:</strong></p>
                                <p class="text-lg font-bold text-blue-600 mb-4">${teamName}</p>
                                
                                <div class="mb-2 flex items-center justify-between">
                                    <strong>Pilih Room:</strong>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="swal-check-all" class="mr-2 h-4 w-4 bg-white border border-gray-300 rounded cursor-pointer" onchange="toggleSwalChecks(this)">
                                        <label for="swal-check-all" class="text-sm cursor-pointer select-none">Pilih Semua</label>
                                    </div>
                                </div>
                                
                                <div class="max-h-60 overflow-y-auto border rounded p-2 bg-gray-50">
                                    <table class="w-full text-sm">
                        `;
                        
                        if (allItems.length > 0) {
                            allItems.forEach(item => {
                                const isDisabled = item.status === 'already_assigned' || item.isDone;
                                const disabledClass = isDisabled ? 'opacity-50 bg-gray-100' : '';
                                
                                let statusLabel = '';
                                if (item.isDone) {
                                    statusLabel = `<span class="text-red-500 text-xs ml-2 font-bold">(DONE JOB)</span>`;
                                } else if (item.status === 'already_assigned') {
                                    statusLabel = `<span class="text-blue-500 text-xs ml-2">(Assigned: ${item.team_name} - Terkunci)</span>`;
                                } else if (item.status === 'can_reassign') {
                                    statusLabel = `<span class="text-orange-500 text-xs ml-2 font-semibold">(Assigned: ${item.team_name} - Bisa Reassign)</span>`;
                                }
                                
                                html += `
                                    <tr class="${disabledClass} border-b last:border-0 hover:bg-gray-100">
                                        <td class="p-2 w-8">
                                            <input type="checkbox" 
                                                class="swal-room-check h-4 w-4 bg-white border border-gray-300 rounded cursor-pointer" 
                                                value="${item.id}" 
                                                data-room-ids="${(item.related_room_ids || [item.id]).join(',')}"
                                                ${item.checked ? 'checked' : ''} 
                                                ${isDisabled ? 'disabled' : ''}>
                                        </td>
                                        <td class="p-2">
                                            <div class="flex flex-col">
                                                <span>${item.room_name}</span>
                                                <span class="text-xs text-gray-500">${item.job_number}</span>
                                            </div>
                                            ${statusLabel}
                                        </td>
                                    </tr>
                                `;
                            });
                        } else {
                            html += `<tr><td class="p-4 text-center text-gray-400">Tidak ada room ditemukan.</td></tr>`;
                        }

                        html += `
                                    </table>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">* Room yang tidak dicentang tidak akan ikut dalam assignment ini.</p>
                                <p class="text-xs text-gray-500">* Room yang sudah ter-assign akan dinonaktifkan.</p>
                            </div>
                        `;

                        Swal.fire({
                            title: 'Konfirmasi Assignment',
                            html: html,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Assign yang Dipilih',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#214589',
                            cancelButtonColor: '#d33',
                            width: '600px',
                            preConfirm: () => {
                                const checkedBoxes = document.querySelectorAll('.swal-room-check:checked');
                                const selectedDisplayCount = checkedBoxes.length;
                                const selectedIds = [...new Set(Array.from(checkedBoxes).flatMap(cb => {
                                    return (cb.getAttribute('data-room-ids') || cb.value)
                                        .split(',')
                                        .map(id => id.trim())
                                        .filter(Boolean);
                                }))];
                                if (selectedIds.length === 0) {
                                    Swal.showValidationMessage('Pilih minimal satu room.');
                                    return false;
                                }
                                return {
                                    roomIds: selectedIds,
                                    displayCount: selectedDisplayCount
                                };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const selectedRoomIds = result.value.roomIds;
                                const selectedDisplayCount = result.value.displayCount;
                                
                                Swal.fire({
                                    title: 'Memproses...',
                                    text: 'Sedang assign team ke room terpilih',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                fetch('{{ route("operational.job-schedules.bulk-update-room-assignment") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        room_ids: selectedRoomIds,
                                        team_id: teamId,
                                        selected_display_count: selectedDisplayCount,
                                        notes: 'Bulk assignment via Job Schedule View'
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    const success = data.status === 'success' || data.success === true;
                                    if(success) {
                                        Swal.fire(
                                            'Berhasil',
                                            data.message || 'Team berhasil di-assign.',
                                            'success'
                                        ).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire('Gagal', data.message || 'Gagal assign team.', 'error');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire('Gagal', 'Terjadi kesalahan yang tidak terduga.', 'error');
                                });
                            } else {
                                actionSelect.value = "";
                            }
                        });

                    } else {
                        Swal.fire('Gagal', data.message || 'Gagal memeriksa assignment.', 'error');
                        actionSelect.value = "";
                    }
                })
                .catch(e => {
                    console.error(e);
                    Swal.fire('Gagal', 'Gagal terhubung ke server.', 'error');
                    actionSelect.value = "";
                });
                
                return; 
            }

            // 2. Handle Static Actions
            let actionText = '';
            let confirmBtnColor = '#214589';
            
            switch(actionType) {
                case 'suspend': actionText = 'Suspend Job (Tidak Ditagih)'; confirmBtnColor = '#374151'; break;
                case 'unsuspend': actionText = 'Unsuspend Job (Reset ke New Job)'; confirmBtnColor = '#2563eb'; break;
                case 'dpf': actionText = 'DPF (Done But Force-charged)'; confirmBtnColor = '#d97706'; break;
                case 'unassign_team': actionText = 'Unassign Team (Reset to New Job)'; confirmBtnColor = '#dc2626'; break;
                case 'unpost_issue': actionText = 'Unpost Issue (Revert to Material Assign)'; confirmBtnColor = '#f59e0b'; break;
                case 'unpost_ba': actionText = 'Unpost BA & Cancel'; confirmBtnColor = '#f59e0b'; break;
                case 'suspend_room': actionText = 'Suspend Room'; break;
                case 'unassign_material': actionText = 'Unassign Material (Reset ke New Job)'; confirmBtnColor = '#dc2626'; break;
            }

            // Special handling for material_assign with room selection
            if (actionType === 'material_assign') {
                 // Fetch assignments first to show room list (similar to assign_team)
                 Swal.fire({
                    title: 'Memeriksa Job...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch('/operational/job-schedules/check-bulk-assignments', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        job_ids: ids, 
                        selected_room_ids: selectedRoomIdsFromTable,
                        strict_selection: true,
                        expand_grouped_rows: viewMode !== 'room'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Show room selection dialog
                         let detailsHtml = `
                            <div class="text-left text-sm mb-2">Pilih room yang akan diproses Material Assign:</div>
                            <div class="max-h-60 overflow-y-auto border border-gray-200 rounded">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 sticky top-0">
                                        <tr>
                                            <th class="px-3 py-2 text-center w-10">
                                                <input type="checkbox" id="checkAllRoomsVerify" onclick="toggleAllRoomsVerify(this)" checked>
                                            </th>
                                            <th class="px-3 py-2">Job No</th>
                                            <th class="px-3 py-2">Room</th>
                                            <th class="px-3 py-2">Product</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                        `;

                        if (data.data && Array.isArray(data.data)) {
                            if (data.data.length === 0) {
                                detailsHtml += `<tr><td colspan="4" class="px-3 py-2 text-center text-gray-500">No rooms found.</td></tr>`;
                            } else {
                                data.data.forEach(item => {
                                    const isDone = item.job_status === 'done_job' || item.job_status === 'completed';
                                    const alreadyHasMaterial = item.job_status !== 'scheduled' && item.job_status !== 'new_job';
                                    const isDisabled = item.status === 'already_assigned' || isDone || alreadyHasMaterial;
                                    const isSelectedInTable = selectedRoomIdsFromTable.length === 0 || selectedRoomIdsFromTable.includes(item.id.toString());
                                    detailsHtml += `
                                        <tr class="hover:bg-gray-50 ${isDisabled ? 'opacity-50 bg-gray-50' : ''}">
                                            <td class="px-3 py-2 text-center">
                                                <input type="checkbox" class="room-verify-checkbox-safe cursor-pointer" 
                                                    value="${item.id}" 
                                                    id="check_room_${item.id}"
                                                    ${isDisabled ? 'disabled' : (isSelectedInTable ? 'checked' : '')}>
                                            </td>
                                            <td class="px-3 py-2 font-medium">
                                                ${item.job_number}
                                                ${isDone ? '<span class="text-red-500 text-[10px] font-bold ml-1">(DONE)</span>' : ''}
                                            </td>
                                            <td class="px-3 py-2">${item.room_name}</td>
                                            <td class="px-3 py-2 text-gray-500">${item.team_name || '-'}</td>
                                        </tr>
                                    `;
                                });
                            }
                        } else {
                            detailsHtml += `<tr><td colspan="4" class="px-3 py-2 text-center text-red-500">Invalid data format received.</td></tr>`;
                        }

                        detailsHtml += `
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">* Hanya room yang dicentang yang akan diproses.</div>
                        `;

                        Swal.fire({
                            title: 'Konfirmasi Material Assign',
                            html: detailsHtml,
                            width: '600px',
                            showCancelButton: true,
                            confirmButtonText: 'Proses Material Assign',
                            confirmButtonColor: '#214589',
                            cancelButtonText: 'Batal',
                            didOpen: () => {
                                // Re-bind verify checkbox logic if needed
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Collect selected room IDs (scoped to active modal)
                                const container = Swal.getHtmlContainer();
                                const selectedRoomIds = Array.from(container.querySelectorAll('.room-verify-checkbox-safe:checked')).map(cb => cb.value);
                                
                                if (selectedRoomIds.length === 0) {
                            Swal.fire('Gagal', 'Pilih setidaknya satu room.', 'error');
                                    return;
                                }

                                processBatchActions(ids, 'material_assign', { room_ids: selectedRoomIds });
                            } else {
                                document.getElementById('actionType').value = "";
                            }
                        });


                    } else {
                        Swal.fire('Gagal', data.message || 'Gagal mengambil detail job.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Gagal', 'Terjadi kesalahan saat mengambil detail.', 'error');
                });
                
                return; // Stop execution to wait for async flow
            }

            if (actionType === 'unassign_team') {
                const checkedBoxes = Array.from(document.querySelectorAll('.row-checkbox:checked'));
                let hasDoneJob = false;
                
                for (const cb of checkedBoxes) {
                    const status = cb.getAttribute('data-status');
                    if (status === 'done_job' || status === 'completed') {
                        hasDoneJob = true;
                        break;
                    }
                }

                if (hasDoneJob) {
                    Swal.fire({
                        title: 'Aksi Ditolak',
                        text: 'Terdapat job yang sudah berstatus Done Job. Job yang sudah selesai tidak bisa di-unassign team.',
                        icon: 'error'
                    });
                    actionSelect.value = "";
                    return;
                }

                // Fetch rooms for selection
                Swal.fire({
                    title: 'Memeriksa Job...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const unassignTeamCheckPayload = {
                    job_ids: ids,
                    strict_selection: true
                };
                if (selectedRoomIdsFromTable.length > 0) {
                    unassignTeamCheckPayload.selected_room_ids = selectedRoomIdsFromTable;
                }

                fetch('/operational/job-schedules/check-bulk-assignments', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(unassignTeamCheckPayload)
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.status === 'success') {
                        const roomsToDisplay = selectedRoomIdsFromTable.length > 0
                            ? data.data.filter(item => selectedRoomIdsFromTable.includes(item.id.toString()))
                            : data.data;

                        let detailsHtml = `
                            <div class="text-left text-sm mb-2">Pilih room yang akan dilepas team-nya (Unassign):</div>
                            <div class="max-h-60 overflow-y-auto border border-gray-200 rounded">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 sticky top-0">
                                        <tr>
                                            <th class="px-3 py-2 text-center w-10">
                                                <input type="checkbox" id="checkAllUnassign" onclick="toggleAllRoomsVerify(this)" checked>
                                            </th>
                                            <th class="px-3 py-2">Job No</th>
                                            <th class="px-3 py-2">Room</th>
                                            <th class="px-3 py-2">Team Aktif</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                        `;

                        if (roomsToDisplay.length === 0) {
                            detailsHtml += `<tr><td colspan="4" class="px-3 py-2 text-center text-gray-500">No rooms found.</td></tr>`;
                        }

                        roomsToDisplay.forEach(item => {
                            const isDone = item.job_status === 'done_job' || item.job_status === 'completed';
                            const isSelectedInTable = selectedRoomIdsFromTable.length === 0 || selectedRoomIdsFromTable.includes(item.id.toString());
                            detailsHtml += `
                                <tr class="hover:bg-gray-50 ${isDone ? 'opacity-50 bg-gray-50' : ''}">
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox" class="room-unassign-checkbox-safe cursor-pointer" 
                                            value="${item.id}" 
                                            name="room_ids[]"
                                            ${isDone ? 'disabled' : (isSelectedInTable ? 'checked' : '')}>
                                    </td>
                                    <td class="px-3 py-2 font-medium">
                                        ${item.job_number}
                                        ${isDone ? '<span class="text-red-500 text-[10px] font-bold ml-1">(DONE)</span>' : ''}
                                    </td>
                                    <td class="px-3 py-2">${item.room_name}</td>
                                    <td class="px-3 py-2 text-blue-600">${item.team_name || 'unassign'}</td>
                                </tr>
                            `;
                        });

                        detailsHtml += `</tbody></table></div>`;

                        Swal.fire({
                            title: 'Konfirmasi Unassign Team',
                            html: detailsHtml,
                            width: '600px',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Unassign',
                            confirmButtonColor: '#dc2626',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Fix: Scope selector to active modal to prevent selecting ghost elements
                                const container = Swal.getHtmlContainer();
                                const selectedRoomCheckboxes = Array.from(container.querySelectorAll('.room-unassign-checkbox-safe:checked'));
                                const selectedRoomIds = selectedRoomCheckboxes
                                    .map(cb => cb.value)
                                    .filter(value => /^\d+$/.test(value));
                                
                                if (selectedRoomCheckboxes.length === 0) {
                        Swal.fire('Gagal', 'Pilih setidaknya satu room.', 'error');
                                    return;
                                }

                                processBatchActions(
                                    ids,
                                    'unassign_team',
                                    {
                                        strict_selection: true,
                                        ...(selectedRoomIds.length > 0 ? { room_ids: selectedRoomIds } : {})
                                    }
                                );
                            } else {
                                actionSelect.value = "";
                            }
                        });
                    } else {
                        Swal.fire('Gagal', data.message || 'Gagal mengambil data room.', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Gagal', 'Terjadi kesalahan sistem.', 'error');
                });
                return;
            }

            if (actionType === 'unassign_material') {
                // Fetch rooms for selection
                Swal.fire({
                    title: 'Memeriksa Job...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch('/operational/job-schedules/check-bulk-assignments', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        job_ids: ids,
                        strict_selection: true,
                        ...(selectedRoomIdsFromTable.length > 0 ? { selected_room_ids: selectedRoomIdsFromTable } : {})
                    })
                })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.status === 'success') {
                        let detailsHtml = `
                            <div class="text-left text-sm mb-2">Pilih room yang akan dibatalkan Material Assign-nya:</div>
                            <div class="max-h-60 overflow-y-auto border border-gray-200 rounded">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 sticky top-0">
                                        <tr>
                                            <th class="px-3 py-2 text-center w-10">
                                                <input type="checkbox" id="checkAllRoomsUnassignMaterial" onclick="toggleAllRoomsUnassignMaterial(this)" checked>
                                            </th>
                                            <th class="px-3 py-2">Job No / Room</th>
                                            <th class="px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                        `;

                        const roomsToDisplay = (viewMode === 'room' && selectedRoomIdsFromTable.length > 0)
                            ? data.data.filter(item => selectedRoomIdsFromTable.includes(item.id.toString()))
                            : data.data;

                        const normalizeMaterialUnassignStatus = (status) => (status || '')
                            .toString()
                            .trim()
                            .toLowerCase()
                            .replace(/[\s-]+/g, '_');
                        const materialUnassignableStatuses = [
                            'assign_material',
                            'material_assign',
                            'barang_dipersiapkan',
                            'material_prepare',
                            'material_in_prep'
                        ];

                        roomsToDisplay.forEach(item => {
                            const jobStatus = normalizeMaterialUnassignStatus(item.job_status);
                            const canUnassign = materialUnassignableStatuses.includes(jobStatus);
                            const isSelectedInTable = selectedRoomIdsFromTable.length === 0 || selectedRoomIdsFromTable.includes(item.id.toString());
                            detailsHtml += `
                                <tr class="hover:bg-gray-50 ${!canUnassign ? 'opacity-50' : ''}">
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox" class="room-unassign-material-checkbox cursor-pointer" 
                                            value="${item.id}" 
                                            ${canUnassign ? (isSelectedInTable ? 'checked' : '') : 'disabled'}>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="font-medium">${item.job_number}</div>
                                        <div class="text-xs text-gray-500">${item.room_name}</div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="text-xs px-2 py-0.5 rounded ${canUnassign ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'}">
                                            ${jobStatus === 'scheduled' ? 'NEW JOB' : (item.job_status || '-').replace(/_/g, ' ').toUpperCase()}
                                        </span>
                                    </td>
                                </tr>
                            `;
                        });

                        detailsHtml += `</tbody></table></div>
                        <div class="text-xs text-gray-500 mt-2 text-left">
                            * Hanya room dengan status <strong>MATERIAL ASSIGN</strong> atau <strong>BARANG DIPERSIAPKAN</strong> yang dapat dibatalkan.
                        </div>`;

                        Swal.fire({
                            title: 'Konfirmasi Unassign Material',
                            html: detailsHtml,
                            width: '600px',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Unassign Material',
                            confirmButtonColor: '#dc2626',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const container = Swal.getHtmlContainer();
                                const selectedRoomIds = Array.from(container.querySelectorAll('.room-unassign-material-checkbox:checked')).map(cb => cb.value);
                                
                                if (selectedRoomIds.length === 0) {
                                    Swal.fire('Gagal', 'Pilih setidaknya satu room.', 'error');
                                    return;
                                }
                                processBatchActions(ids, 'unassign_material', { room_ids: selectedRoomIds });
                            } else {
                                actionSelect.value = "";
                            }
                        });
                    } else {
                        Swal.fire('Gagal', data.message || 'Gagal mengambil data room.', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Gagal', 'Terjadi kesalahan sistem.', 'error');
                });
                return;
            }

            if (actionText) {
                Swal.fire({
                    title: 'Konfirmasi Aksi',
                    text: `Apakah anda yakin ingin melakukan ${actionText} pada ${ids.length} item yang dipilih?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: confirmBtnColor,
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Lanjutkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        processBatchActions(ids, actionType);
                    } else {
                        actionSelect.value = ""; // Reset if cancelled
                    }
                });
            }

        } catch (error) {
            console.error('Error in applySuspendDpf:', error);
            showErrorDialog('Terjadi kesalahan: ' + error.message);
            document.getElementById('actionType').value = ""; // Reset on error
        }
    }

    function showStandardAssignment(ids, teamName, teamId) {
        Swal.fire({
            title: 'Tugaskan Team',
            text: `Assign ${ids.length} job ke team ${teamName}?`,
            input: 'textarea',
            inputPlaceholder: 'Catatan opsional...',
            showCancelButton: true,
            confirmButtonText: 'Assign',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#214589',
        }).then((result) => {
            if (result.isConfirmed) {
                processBatchActions(ids, 'assign_team', { 
                    team_id: teamId, 
                    assignment_notes: result.value 
                });
            }
        });
    }

    async function processBatchActions(ids, actionType, extraData = {}) {
        let successCount = 0;
        let failCount = 0;
        
        // Show loading state
        Swal.fire({
            title: 'Memproses...',
            html: 'Mohon tunggu...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Handle bulk unassign team & bulk room assignment (single API call)
        if (actionType === 'unassign_team' || actionType === 'assign_team_room' || actionType === 'material_assign' || actionType === 'unassign_material') {
            try {
                let url = '/operational/job-schedules/bulk-unassign-team';
                let body = { ids: ids };

                if (actionType === 'unassign_team' && extraData.room_ids) {
                    body.room_ids = extraData.room_ids;
                    body.strict_selection = true;
                } else if (actionType === 'unassign_team' && extraData.strict_selection) {
                    body.strict_selection = true;
                } else if (actionType === 'assign_team_room') {
                    url = '/operational/job-schedules/bulk-update-room-assignment';
                    body = {
                        room_ids: ids,
                        team_id: extraData.team_id,
                        notes: extraData.assignment_notes || 'Bulk Room Assignment'
                    };
                } else if (actionType === 'material_assign') {
                    url = '/operational/job-schedules/bulk-material-assign';
                    body.strict_selection = true;
                    if (extraData.room_ids) {
                        body.room_ids = extraData.room_ids;
                    }
                } else if (actionType === 'unassign_material') {
                    url = '/operational/job-schedules/bulk-unassign-material';
                    body.strict_selection = true;
                    if (extraData.room_ids) {
                        body.room_ids = extraData.room_ids;
                    }
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                
                const data = await response.json();
                
                Swal.close();
                
                if (data.success || data.status === 'success') {
                    let message = data.message;
                    if (data.errors && data.errors.length > 0) {
                        message += '\n\nDetail:\n' + data.errors.join('\n');
                        Swal.fire('Berhasil Sebagian', message, 'warning');
                    } else {
                        Swal.fire('Berhasil', message, 'success');
                    }
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Gagal', data.message || 'Gagal melepas assignment team.', 'error');
                }
                
                document.getElementById('actionType').value = '';
                return;
            } catch (error) {
                Swal.close();
                Swal.fire('Gagal', 'Gagal melepas assignment team: ' + error.message, 'error');
                document.getElementById('actionType').value = '';
                return;
            }
        }

        let errorMessages = [];
        
        for (const id of ids) {
            try {
                let url = '';
                let method = 'POST';
                let body = {};
                
                if (actionType === 'suspend' || actionType === 'dpf') {
                    // Update Status via PUT
                    url = `/operational/job-schedules/${id}`;
                    method = 'PUT'; // Laravel resource update uses PUT/PATCH
                    body = { 
                        status: actionType,
                        _token: '{{ csrf_token() }}',
                        _method: 'PUT' // Spoof method for Laravel
                    };
                } else if (actionType === 'unsuspend') {
                    url = `/operational/job-schedules/${id}/unsuspend`;
                } else if (actionType === 'unassign_team') {
                    // Handled by bulk endpoint above
                    continue;
                } else if (actionType === 'unpost_issue') {
                    url = `/operational/job-schedules/${id}/unpost-issue`;
                } else if (actionType === 'unpost_ba') {
                    url = `/operational/job-schedules/${id}/undone`; // unpostBA alias
                } else if (actionType === 'suspend_room') {
                    url = `/operational/job-schedules/${id}/suspend-room`;
                } else if (actionType === 'assign_team') {
                    // Update Status via PUT with team_id
                    url = `/operational/job-schedules/${id}`;
                    method = 'PUT';
                    body = {
                        status: 'assign_team',
                        team_id: extraData.team_id,
                        internal_notes: extraData.assignment_notes,
                        target_job_ids: extraData.target_job_ids || [], // Pass sibling IDs for selective propagation
                        _token: '{{ csrf_token() }}',
                        _method: 'PUT'
                    };
                }
                
                // Common options
                const options = {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                };
                
                if (method !== 'GET') {
                    options.body = JSON.stringify(body.status ? body : { ...body, _token: '{{ csrf_token() }}' });
                }

                const response = await fetch(url, options);
                const data = await response.json();
                
                if (response.ok && (data.status === 'success' || data.success)) {
                    successCount++;
                } else {
                    failCount++;
                    const msg = data.message || data.error || 'Terjadi kesalahan yang tidak diketahui';
                    if (!errorMessages.includes(msg)) {
                        errorMessages.push(msg);
                    }
                    console.error(`Failed ${actionType} for ${id}:`, data);
                }
            } catch (error) {
                failCount++;
                console.error(`Error ${actionType} for ${id}:`, error);
            }
        }
        
        // Show Report
        let finalHtml = `Berhasil: ${successCount}, Gagal: ${failCount}`;
        if (errorMessages.length > 0) {
            finalHtml += `<br><br><strong>Alasan kegagalan:</strong><ul class="text-left text-xs list-disc pl-5 mt-2">`;
            errorMessages.forEach(m => finalHtml += `<li>${m}</li>`);
            finalHtml += `</ul>`;
        }

        Swal.fire({
            title: 'Selesai!',
            html: finalHtml,
            icon: failCount > 0 ? 'warning' : 'success'
        }).then(() => {
            window.location.reload();
        });
    }
    
    // Extend Day Modal
    function showAssignTeamModal(ids) {
        // Teams data passed from controller
        const teams = @json($teams ?? []);
        let options = {};
        teams.forEach(t => options[t.id] = t.team_name);

        Swal.fire({
            title: 'Ajukan / Assign Team',
            text: `Assign ${ids.length} job ke team berikut:`,
            html: `
                <select id="swal-team-select" class="swal2-input">
                    <option value="">Pilih Team</option>
                    ${Object.entries(options).map(([id, name]) => `<option value="${id}">${name}</option>`).join('')}
                </select>
                <textarea id="swal-notes" class="swal2-textarea" placeholder="Catatan opsional..."></textarea>
            `,
            showCancelButton: true,
            confirmButtonText: 'Assign',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const teamId = document.getElementById('swal-team-select').value;
                const notes = document.getElementById('swal-notes').value;
                if (!teamId) {
                    Swal.showValidationMessage('Pilih team terlebih dahulu.');
                }
                return { teamId: teamId, notes: notes };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                processBatchActions(ids, 'assign_team', { 
                    team_id: result.value.teamId, 
                    assignment_notes: result.value.notes 
                });
            }
        });
    }
    function showExtendDayModal() {
        const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
             Swal.fire('Informasi', 'Pilih job yang ingin di-extend terlebih dahulu.', 'info');
             return;
        }
        
        Swal.fire({
            title: 'Perpanjang Hari / Ubah Expected Date',
            html: '<input type="date" id="swal-extend-date" class="swal2-input">',
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            preConfirm: () => {
                const date = document.getElementById('swal-extend-date').value;
                if (!date) {
                    Swal.showValidationMessage('Tanggal wajib diisi');
                }
                return date;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const newDate = result.value;
                 const ids = Array.from(selectedCheckboxes).map(cb => cb.value);
                 batchExtendDay(ids, newDate);
            }
        });
    }
    
    async function batchExtendDay(ids, newDate) {
         Swal.fire({
            title: 'Memproses...',
            didOpen: () => Swal.showLoading()
        });
        
        let success = 0;
        for (const id of ids) {
            try {
                await fetch(`/operational/job-schedules/${id}/extend-day`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ new_date: newDate })
                });
                success++;
            } catch(e) { console.error(e); }
        }
        
        Swal.fire('Selesai', `Berhasil memperbarui ${success} item.`, 'success').then(() => location.reload());
    }

    function normalizeScheduleDateStatus(status) {
        return (status || '')
            .toString()
            .trim()
            .toLowerCase()
            .replace(/[\s-]+/g, '_');
    }

    function canChangeScheduleDateForStatus(status) {
        return ['scheduled', 'new_job'].includes(normalizeScheduleDateStatus(status));
    }

    function hasLockedTeamForScheduleDate(checkboxes) {
        return Array.from(checkboxes).some(cb => {
            const team = (cb.getAttribute('data-current-team') || '').trim().toLowerCase();
            const hasAssignedTeam = team !== '' && team !== 'unassign' && team !== '-';

            return hasAssignedTeam && !canChangeScheduleDateForStatus(cb.getAttribute('data-status'));
        });
    }

    function applyAdjustmentDay() {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        
        if (checkboxes.length === 0) {
            showWarningDialog('Pilih minimal satu job schedule.');
            return;
        }
        
        // MOM8: Check if any selected job schedule has assigned team
        // Read from checkbox's data-current-team attribute (robust against column reorder).
        // Unassigned rows have data-current-team="unassign"; assigned rows have the team name.
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);
        const hasAssignedTeam = hasLockedTeamForScheduleDate(checkboxes);

        if (hasAssignedTeam) {
            showWarningDialog('Maaf, schedule date sudah tidak dapat diubah.');
            return;
        }

        const adjustmentType = document.getElementById('adjustmentType').value;
        const adjustmentDaysInput = document.getElementById('adjustmentDays').value;
        
        // Validate input
        if (!adjustmentDaysInput || adjustmentDaysInput <= 0) {
            showWarningDialog('Masukkan jumlah hari yang valid dan lebih dari 0.');
            return;
        }
        
        const days = parseInt(adjustmentDaysInput);
        
        // Calculate days based on type (plus or minus)
        const finalDays = adjustmentType === 'plus' ? days : -days;
        
        // Call the existing adjustScheduleDate function
        adjustScheduleDate(finalDays);
    }

    async function adjustScheduleDate(days) {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        
        if (checkboxes.length === 0) {
            showWarningDialog('Pilih minimal satu job schedule.');
            return;
        }
        
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);
        const sign = days > 0 ? '+' : '';
        const dayText = Math.abs(days) === 1 ? 'hari' : 'hari';
        
        const confirmation = await showConfirmDialog({
            title: 'Adjust Schedule Date?',
            text: `Ubah schedule date ${sign}${Math.abs(days)} ${dayText} untuk ${selectedIds.length} job schedule terpilih?`,
            icon: 'question',
            confirmButtonText: 'Ya, ubah',
            cancelButtonText: 'Batal'
        });

        if (!confirmation.isConfirmed) {
            return;
        }
        
        // Get current schedule dates and adjust them
        Promise.all(selectedIds.map(jobScheduleId => {
            // First, get the current schedule date
            return fetch(`/operational/job-schedules/${jobScheduleId}`, {
                method: 'GET',
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
                if (data.status === 'success' && data.data) {
                    // MOM8: Check if team is already assigned
                    const hasAssignedTeam = data.data.job_assign_schedules
                        && data.data.job_assign_schedules.length > 0
                        && !canChangeScheduleDateForStatus(data.data.status);
                    if (hasAssignedTeam) {
                        throw new Error('Maaf sudah tidak dapat merubah schedule date nya');
                    }
                    
                    // Handle schedule_date - could be string or date object
                    let scheduleDate = data.data.schedule_date;
                    
                    // If schedule_date is null or undefined, use current date as fallback
                    if (!scheduleDate) {
                    console.warn(`Job Schedule ${jobScheduleId} tidak memiliki schedule_date, menggunakan tanggal hari ini`);
                        scheduleDate = new Date().toISOString().split('T')[0];
                    }
                    
                    // Parse date string
                    const currentDate = new Date(scheduleDate);
                    if (isNaN(currentDate.getTime())) {
                        throw new Error(`Format schedule_date tidak valid: ${scheduleDate}`);
                    }
                    
                    // Adjust date
                    currentDate.setDate(currentDate.getDate() + days);
                    const adjustedDate = currentDate.toISOString().split('T')[0];
                    
                    // Update schedule date
                    return fetch(`/operational/job-schedules/${jobScheduleId}`, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            schedule_date: adjustedDate
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(errData => {
                                throw new Error(errData.message || `HTTP error! status: ${response.status}`);
                            });
                        }
                        return response.json();
                    })
                    .catch(error => {
                        console.error(`Error updating job schedule ${jobScheduleId}:`, error);
                        throw error;
                    });
                } else {
                    throw new Error(`Gagal mengambil schedule date untuk job schedule ${jobScheduleId}: ${data.message || 'Terjadi kesalahan yang tidak diketahui'}`);
                }
            })
            .catch(error => {
                console.error(`Error fetching job schedule ${jobScheduleId}:`, error);
                throw error;
            });
        }))
        .then(responses => {
            // All responses should be JSON now
            return Promise.all(responses.map(r => {
                if (typeof r === 'string') {
                    return JSON.parse(r);
                }
                return r;
            }));
        })
        .then(results => {
            const successCount = results.filter(r => r && r.status === 'success').length;
            const errors = results.filter(r => r && r.status !== 'success').map(r => r.message || 'Terjadi kesalahan yang tidak diketahui').join(', ');
            
            if (successCount === selectedIds.length) {
                showSuccessDialog(`Berhasil menyesuaikan schedule date untuk ${successCount} job schedule.`, 'Berhasil').then(() => location.reload());
            } else if (successCount > 0) {
                showWarningDialog(`Berhasil sebagian: ${successCount} dari ${selectedIds.length} update berhasil. Detail: ${errors}`, 'Berhasil Sebagian');
            } else {
                showErrorDialog(`Gagal menyesuaikan schedule date. Detail: ${errors}`);
            }
        })
        .catch(error => {
            console.error('Error adjusting schedule date:', error);
            showErrorDialog(`Gagal menyesuaikan schedule date: ${error.message || 'Silakan coba lagi.'}`);
        });
    }

    async function changeScheduleDate() {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        const scheduleDate = document.getElementById('filterScheduleDate')?.value;
        
        if (checkboxes.length === 0) {
            showWarningDialog('Pilih minimal satu job schedule.');
            return;
        }
        
        if (!scheduleDate) {
            showWarningDialog('Pilih tanggal schedule terlebih dahulu.');
            return;
        }
        
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);

        // MOM8: Check if any selected job schedule has assigned team
        // Read from checkbox's data-current-team attribute (robust against column reorder).
        // Unassigned rows have data-current-team="unassign"; assigned rows have the team name.
        const hasAssignedTeam = hasLockedTeamForScheduleDate(checkboxes);

        if (hasAssignedTeam) {
            showWarningDialog('Maaf, schedule date sudah tidak dapat diubah.');
            return;
        }

        const confirmation = await showConfirmDialog({
            title: 'Ganti Schedule Date?',
            text: `Ubah schedule date menjadi ${scheduleDate} untuk ${selectedIds.length} job schedule terpilih?`,
            icon: 'question',
            confirmButtonText: 'Ya, ubah',
            cancelButtonText: 'Batal'
        });

        if (!confirmation.isConfirmed) {
            return;
        }
        
        // Update schedule date for each selected job schedule
        Promise.all(selectedIds.map(jobScheduleId => {
            // Update schedule date
            return fetch(`/operational/job-schedules/${jobScheduleId}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    schedule_date: scheduleDate
                })
            });
        }))
        .then(responses => Promise.all(responses.map(r => r.json())))
        .then(results => {
            const successCount = results.filter(r => r.status === 'success').length;
            const errorCount = results.length - successCount;
            
            if (successCount === selectedIds.length) {
                showSuccessDialog(`Berhasil mengubah schedule date untuk ${successCount} job schedule.`, 'Berhasil').then(() => location.reload());
            } else if (successCount > 0) {
                const errors = results.filter(r => r.status !== 'success').map(r => r.message || 'Terjadi kesalahan yang tidak diketahui').join(', ');
                showWarningDialog(`Berhasil sebagian: ${successCount} dari ${selectedIds.length} update berhasil. Detail: ${errors}`, 'Berhasil Sebagian');
            } else {
                const errors = results.map(r => r.message || 'Terjadi kesalahan yang tidak diketahui').join(', ');
                showErrorDialog(`Gagal mengubah schedule date. Detail: ${errors}`);
            }
        })
        .catch(error => {
            console.error('Error changing schedule date:', error);
            showErrorDialog(`Gagal mengubah schedule date: ${error.message || 'Silakan coba lagi.'}`);
        });
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        const filterTeamCode = document.getElementById('filterTeamCode');
        if (filterTeamCode) {
            filterTeamCode.addEventListener('change', updateProposeTeamButton);
        }
    });

    // Service Frequency Calculation
    function calculateServiceInterval() {
        const serviceFrequency = document.querySelector('select[name="service_frequency"]').value;
        const scheduleDate = document.querySelector('input[name="schedule_date"]').value;
        
        if (!serviceFrequency || !scheduleDate) {
            return;
        }
        
        // Formula from CLIENT-FEEDBACK-ENHANCEMENT.md
        const startDate = new Date(scheduleDate);
        const endOfMonth = new Date(startDate);
        endOfMonth.setMonth(endOfMonth.getMonth() + 1);
        
        // Calculate difference in days
        const daysDifference = Math.ceil((endOfMonth - startDate) / (1000 * 60 * 60 * 24));
        
        // Divide by service frequency
        const interval = daysDifference / serviceFrequency;
        
        // Round up the result
        const intervalDays = Math.ceil(interval);
        
        // Get frequency label
        const frequencyLabels = {
            '1': 'Once per month',
            '2': 'Twice per month', 
            '3': 'Three times per month',
            '4': 'Four times per month',
            '6': 'Every 2 months',
            '9': 'Every 3 months',
            '12': 'Every 4 months',
            '15': 'Every 5 months',
            '18': 'Every 6 months',
            '36': 'Once per year'
        };
        
        const frequencyLabel = frequencyLabels[serviceFrequency] || `${serviceFrequency} times`;
        
        // Show calculation result
        console.log(`Service Frequency: ${frequencyLabel}`);
        console.log(`Days in month: ${daysDifference}`);
        console.log(`Interval: ${intervalDays} days between services`);
        
        // You can add a display element to show the calculated interval
        let intervalDisplay = document.getElementById('service-interval-display');
        if (!intervalDisplay) {
            intervalDisplay = document.createElement('div');
            intervalDisplay.id = 'service-interval-display';
            intervalDisplay.className = 'mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800';
            document.querySelector('select[name="service_frequency"]').parentNode.appendChild(intervalDisplay);
        }
        
        intervalDisplay.innerHTML = `
            <strong>Calculated Service Interval:</strong> Every ${intervalDays} days<br>
            <small>Based on ${frequencyLabel}</small>
        `;
    }

    function clearDateFilters() {
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        applyFilters();
    }

    function updatePerPage(perPage) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage);
        url.searchParams.set('page', 1); // Reset to first page
        window.location.href = url.toString();
    }

    window.toggleSwalChecks = function(source) {
        document.querySelectorAll('.swal-room-check:not(:disabled)').forEach(cb => {
            cb.checked = source.checked;
        });
    }

    window.toggleAllRoomsVerify = function(source) {
        document.querySelectorAll('.room-verify-checkbox-safe:not(:disabled)').forEach(cb => {
            cb.checked = source.checked;
        });
        document.querySelectorAll('.room-unassign-checkbox-safe:not(:disabled)').forEach(cb => {
            cb.checked = source.checked;
        });
    }

    window.toggleAllRoomsUnassignMaterial = function(source) {
        const container = Swal.getHtmlContainer();
        if (container) {
            container.querySelectorAll('.room-unassign-material-checkbox:not(:disabled)').forEach(cb => {
                cb.checked = source.checked;
            });
        }
    }

    // Initialize Flatpickr for Header Filters
    document.addEventListener('DOMContentLoaded', function() {
        // Common config
        const flatpickrConfig = {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/M/Y",
            allowInput: false,
        };

        // Date Created Filters (Header - Manual Trigger only via 'Cari' button)
        if (document.getElementById('filterDateFrom')) {
            flatpickr('#filterDateFrom', {
                ...flatpickrConfig
            });
        }

        if (document.getElementById('filterDateTo')) {
            flatpickr('#filterDateTo', {
                ...flatpickrConfig
            });
        }

        // Column Date Filters (Automatic Trigger on change)
        flatpickr('.column-filter-date', {
            ...flatpickrConfig,
            onChange: function(selectedDates, dateStr) {
                applyFilters();
            }
        });

        // Change Schedule Date Action
        if (document.getElementById('filterScheduleDate')) {
            flatpickr('#filterScheduleDate', {
                ...flatpickrConfig,
                onChange: function(selectedDates, dateStr) {
                    changeScheduleDate();
                }
            });
        }

        // Handle ENTER key for column filters
        document.querySelectorAll('.column-filter').forEach(filter => {
            if (filter.tagName === 'INPUT') {
                filter.addEventListener('keyup', function(e) {
                    if (e.key === 'Enter') {
                        applyFilters();
                    }
                });
            } else if (filter.tagName === 'SELECT') {
                filter.addEventListener('change', function() {
                    applyFilters();
                });
            }
        });
    });
    function toggleAllRoomsUnassignMaterial(source) {
        const checkboxes = document.querySelectorAll('.room-unassign-material-checkbox:not(:disabled)');
        checkboxes.forEach(cb => cb.checked = source.checked);
    }
</script>
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endsection
