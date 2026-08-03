@extends('layouts.app')

@section('title', 'Job Assign Material Issue - Operational')
@section('breadcrumb', 'Home / Operational / Job Assign Material Issue')

@section('content')
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
<style>
    /* Flatpickr custom styles */
    .flatpickr-input {
        background-color: white !important;
        cursor: pointer;
    }
    .flatpickr-input:read-only {
        background-color: white !important;
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

    .btn-success {
        background-color: #16a34a;
        color: white;
    }

    .btn-success:hover {
        background-color: #15803d;
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
        margin: 0;
        padding: 0;
        width: 100%;
        max-height: 65vh !important; /* CRITICAL for sticky */
        height: auto;
        overflow-x: auto !important;
        overflow-y: auto !important;
        position: relative;
        border: 1px solid #e5e7eb;
        background: #fff;
        display: block;
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
        min-width: 1800px; /* Increased for wider columns */
        table-layout: fixed; /* FIXED to respect column widths */
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
        font-size: 14px;
        line-height: 1.4;
    }
    
    .responsive-table {
        width: 100%;
        border-collapse: separate; /* Required for sticky borders */
        border-spacing: 0;
        table-layout: auto; /* Allow columns to expand based on content */
    }

    .responsive-table th {
        background-color: #214589 !important;
        color: white !important;
        font-weight: 600;
        font-size: 13px;
        position: sticky !important;
        top: 0 !important;
        z-index: 200 !important; /* Extremely high to stay above everything */
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 12px 10px !important;
        height: 50px;
        box-sizing: border-box;
        border: 1px solid #e5e7eb;
    }

    /* Column sticky horizontally must also be sticky vertically */
    .responsive-table tr th:nth-child(-n+4),
    .responsive-table tr td:nth-child(-n+4) {
        position: sticky !important;
        z-index: 40 !important;
        background-clip: padding-box;
    }

    /* Corner intersections (Top 4 columns) */
    .responsive-table thead tr:nth-child(1) th:nth-child(-n+4) {
        z-index: 250 !important; 
    }
    
    .responsive-table thead tr.filter-row th:nth-child(-n+4) {
        z-index: 245 !important; 
    }
    
    /* Horizontal offsets for sticky columns */
    .responsive-table tr th:nth-child(1), .responsive-table tr td:nth-child(1) { left: 0 !important; min-width: 50px !important; width: 50px !important; text-align: center; }
    .responsive-table tr th:nth-child(2), .responsive-table tr td:nth-child(2) { left: 50px !important; min-width: 200px !important; width: 200px !important; }
    .responsive-table tr th:nth-child(3), .responsive-table tr td:nth-child(3) { left: 250px !important; min-width: 250px !important; width: 250px !important; }
    .responsive-table tr th:nth-child(4), .responsive-table tr td:nth-child(4) { left: 500px !important; min-width: 150px !important; width: 150px !important; }

    /* Filter row sticky vertical position */
    .responsive-table thead tr.filter-row th {
        top: 50px !important; /* Exactly below the 50px high main header */
        background-color: #f3f4f6 !important;
        padding: 5px 8px !important;
        z-index: 190 !important; /* Slightly below main header but above body */
        height: 45px;
        border-bottom: 2px solid #e5e7eb;
    }

    /* BUG #18: the filter row's sticky <th> cells extend slightly below their
       visible background, so a body row scrolled to just beneath the filter
       row can sit in that dead zone — a click there hits the (visually empty)
       <th> instead of the row's Material combobox underneath, since the <th>
       has a higher z-index. Disable hit-testing on the <th> wrapper and only
       re-enable it on the actual filter input/select, so clicks outside those
       controls always pass through to whatever body cell is really there.
       This was happening at the time the row data + qty cell are sticky too
       so it's not a single product type — it depends on scroll position. */
    .responsive-table thead tr.filter-row th {
        pointer-events: none !important;
    }
    .responsive-table thead tr.filter-row th .column-filter {
        pointer-events: auto !important;
    }

    .responsive-table tr td {
        background-color: white;
    }

    .responsive-table tr:nth-child(even) td {
        background-color: #f9fafb;
    }

    /* Ensure filter row cells stay sticky when scrolling horizontally */
    .responsive-table thead tr.filter-row th:nth-child(1) { left: 0 !important; }
    .responsive-table thead tr.filter-row th:nth-child(2) { left: 50px !important; }
    .responsive-table thead tr.filter-row th:nth-child(3) { left: 250px !important; }
    .responsive-table thead tr.filter-row th:nth-child(4) { left: 500px !important; }

    /* Filter input styling */
    .responsive-table thead tr.filter-row input,
    .responsive-table thead tr.filter-row select {
        width: 100%;
        padding: 4px 6px;
        font-size: 11px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        background-color: white;
        color: #111827;
        height: 28px;
    }
        min-width: 200px; 
        max-width: 200px;
    }
    /* Column 4: Team */
    .responsive-table th:nth-child(4), 
    .responsive-table td:nth-child(4) { 
        left: 450px; 
        width: 150px; 
        min-width: 150px; 
        max-width: 150px;
        border-right: 2px solid #e5e7eb; /* Visual separator for freeze */
    }

    .responsive-table td {
        overflow: hidden;
        text-overflow: ellipsis;
    }
    

    
    /* CRITICAL: Disable row click events completely */
    .responsive-table tbody tr {
        /* cursor: pointer; - REMOVED */
        pointer-events: none; /* Disable ALL click events on row */
    }
    
    /* But allow clicks on interactive elements */
    .responsive-table tbody tr input,
    .responsive-table tbody tr button,
    .responsive-table tbody tr select,
    .responsive-table tbody tr a {
        pointer-events: auto; /* Re-enable clicks on form elements */
    }

    /* Select2 renders a sibling span for searchable selects. Keep that span clickable too. */
    .responsive-table tbody tr .select2-container,
    .responsive-table tbody tr .select2-selection,
    .responsive-table tbody tr .select2-selection__rendered,
    .responsive-table tbody tr .select2-selection__arrow {
        pointer-events: auto;
    }
    
    .responsive-table tbody {
        height: auto;
    }

    
    /* Column widths for other columns */
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 150px; min-width: 150px; } /* Job No */
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 100px; min-width: 100px; } /* Tanggal Job */
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 120px; min-width: 120px; } /* Ruangan */
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 150px; min-width: 150px; } /* Rental */
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 220px; min-width: 220px; } /* Material */
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 100px; min-width: 100px; text-align: center; } /* Qty Issue */
    .responsive-table th:nth-child(11), .responsive-table td:nth-child(11) { width: 150px; min-width: 150px; } /* Warehouse */
    .responsive-table th:nth-child(12), .responsive-table td:nth-child(12) { width: 120px; min-width: 120px; text-align: center; } /* Warehouse Stock */
    .responsive-table th:nth-child(13), .responsive-table td:nth-child(13) { width: 150px; min-width: 150px; } /* Tipe Material */
    .responsive-table th:nth-child(14), .responsive-table td:nth-child(14) { width: 120px; min-width: 120px; text-align: center; } /* Qty BOM */
    .responsive-table th:nth-child(15), .responsive-table td:nth-child(15) { width: 100px; min-width: 100px; text-align: center; } /* Status */
    .responsive-table th:nth-child(16), .responsive-table td:nth-child(16) { width: 200px; min-width: 200px; } /* Op. Notes */
    .responsive-table th:nth-child(17), .responsive-table td:nth-child(17) { width: 120px; min-width: 120px; } /* Created By */
    .responsive-table th:nth-child(18), .responsive-table td:nth-child(18) { width: 150px; min-width: 150px; } /* Created At */
    .responsive-table th:nth-child(19), .responsive-table td:nth-child(19) { width: 120px; min-width: 120px; } /* Last Updated By */
    .responsive-table th:nth-child(20), .responsive-table td:nth-child(20) { width: 150px; min-width: 150px; } /* Last Updated At */
    .responsive-table th:nth-child(21), .responsive-table td:nth-child(21) { width: 100px; min-width: 100px; text-align: center; } /* Action */

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

    /* Modal Sections */
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
            .grid.grid-cols-1.lg\:grid-cols-4 {
                grid-template-columns: repeat(4, 1fr);
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
        padding: 4px 8px;
        border-radius: 4px;
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
    
    .status-issued {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .status-rejected {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .status-received {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-returned {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-lost {
        background-color: #fee2e2;
        color: #991b1b;
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div></div> <!-- Spacer since title is removed -->
        </div>
        
        <!-- Filter Row - Responsive -->
        <div class="w-full bg-white p-4 border-b">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Range Date - From -->
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-gray-700">Range Date - From:</label>
                    <input type="text" id="filterDateFrom" name="date_from" 
                           class="flatpickr-date px-3 py-1.5 border border-gray-300 rounded text-sm w-full" 
                           data-date-value="{{ request('date_from', now()->toDateString()) }}" 
                           data-filter-active="{{ request('date_from') ? 'true' : 'false' }}"
                           placeholder="Select date..." readonly>
                </div>
                
                <!-- Range Date - To (Default: +14 days) -->
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-gray-700">Range Date - To:</label>
                    <input type="text" id="filterDateTo" name="date_to" 
                           class="flatpickr-date px-3 py-1.5 border border-gray-300 rounded text-sm w-full" 
                           data-date-value="{{ request('date_to', now()->addDays(14)->toDateString()) }}" 
                           data-filter-active="{{ request('date_to') ? 'true' : 'false' }}"
                           placeholder="Select date..." readonly>
                </div>
                
                <!-- Tanggal MA (Issued Date) -->
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-gray-700">Tanggal MA (Issued Date):</label>
                    <input type="text" id="filterIssuedDate" name="issue_date"
                           class="flatpickr-date px-3 py-1.5 border border-gray-300 rounded text-sm w-full"
                           data-date-value="{{ request('issue_date') }}"
                           data-filter-active="{{ request('issue_date') ? 'true' : 'false' }}"
                           placeholder="Select date..." readonly>
                </div>
                
                <!-- Filter Building Name -->
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-gray-700">Filter Nama Gedung:</label>
                    <input type="text" id="filterBuildingName" name="building_name"
                           class="px-3 py-1.5 border border-gray-300 rounded text-sm w-full"
                           value="{{ request('building_name') }}"
                           placeholder="Ketik nama gedung..."
                           onkeydown="if (event.key === 'Enter') applyFilters()">
                </div>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white border-b">
            <div class="flex flex-row justify-start items-center w-full">
                <!-- Select all moved to table header -->
                
                @if($canPrepareMaterial)
                    <button class="btn btn-primary btn-sm ml-4" onclick="submitIssue()" id="btnSubmitIssue" disabled>
                        <i class="fas fa-paper-plane"></i>
                        <span>Submit Issue</span>
                    </button>
                @endif

                @if($canDeleteMaterialIssue)
                    <button class="btn btn-secondary btn-sm ml-4" onclick="deleteSelected()">
                        <i class="fas fa-trash"></i>
                        <span>Delete</span>
                    </button>
                @endif
            </div>
        </div>
        
        <div class="table-container shadow-sm border rounded-lg">
            <table class="responsive-table" data-filter-enhanced="1">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th style="width: 50px; min-width: 50px;">
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" onchange="toggleSelectAll()">
                        </th>
                        <th data-column="customer_name" style="width: 200px; min-width: 200px;">Customer</th>
                        <th data-column="building_name" style="width: 250px; min-width: 250px;">Gedung</th>
                        <th data-column="team_name" style="width: 250px; min-width: 250px;">Team</th>
                        <th data-column="job_number" style="width: 150px; min-width: 150px;">Job No</th>
                        <th data-column="issue_date" data-type="date" style="width: 120px; min-width: 120px;">Tanggal Job</th>
                        <th style="width: 150px; min-width: 150px;">Ruangan</th>
                        <th style="width: 150px; min-width: 150px;">Rental</th>
                        <th style="width: 200px; min-width: 200px;">Material</th>
                        <th style="width: 150px; min-width: 150px;">Qty Issue (kemasan)</th>
                        <th style="width: 150px; min-width: 150px;">Warehouse</th>
                        <th style="width: 120px; min-width: 120px;">Warehouse Stock</th>
                        <th style="width: 120px; min-width: 120px;">Tipe Material</th>
                        <th style="width: 120px; min-width: 120px;">Qty BOM / Target</th>
                        <th data-column="status" style="width: 100px; min-width: 100px;">Status</th>
                        <th style="width: 200px; min-width: 200px;">Op. Notes</th>
                        <th style="width: 150px; min-width: 150px;">Created By</th>
                        <th style="width: 150px; min-width: 150px;">Created At</th>
                        <th style="width: 150px; min-width: 150px;">Last Updated By</th>
                        <th style="width: 150px; min-width: 150px;">Last Updated At</th>
                        <th style="width: 100px; min-width: 100px;">Action</th>
                    </tr>
                    <!-- Filter Row -->
                    <tr class="filter-row">
                        <th></th>
                        <th><input type="text" class="column-filter" name="customer_name" value="{{ request('customer_name') }}" placeholder="Customer"></th>
                        <th><input type="text" class="column-filter" name="building_name" value="{{ request('building_name') }}" placeholder="Gedung"></th>
                        <th>
                            <select class="column-filter" name="team_name">
                                <option value="">Team</option>
                                @foreach($teams as $team)
                                <option value="{{ $team->team_name }}" {{ request('team_name') == $team->team_name ? 'selected' : '' }}>{{ $team->team_name }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th><input type="text" class="column-filter" name="job_number" value="{{ request('job_number') }}" placeholder="Job No"></th>
                        <th><input type="date" class="column-filter" name="issue_date" value="{{ request('issue_date') }}"></th>
                        <th><input type="text" class="column-filter" name="room_name" value="{{ request('room_name') }}" placeholder="Room"></th>
                        <th><input type="text" class="column-filter" name="rental_name" value="{{ request('rental_name') }}" placeholder="Rental"></th>
                        <th><input type="text" class="column-filter" name="material_name" value="{{ request('material_name') }}" placeholder="Material"></th>
                        <th></th> <!-- Qty -->
                        <th><input type="text" class="column-filter" name="warehouse_name" value="{{ request('warehouse_name') }}" placeholder="Warehouse"></th>
                        <th></th> <!-- Stock -->
                        <th><input type="text" class="column-filter" name="product_type" value="{{ request('product_type') }}" placeholder="Type"></th>
                        <th></th> <!-- Qty BOM -->
                        <th>
                            <select class="column-filter" name="status">
                                <option value="">Status</option>
                                @foreach($statuses ?? [] as $status)
                                @php
                                    $val = is_object($status) ? ($status->option_name ?? $status->name ?? '') : $status;
                                    $lbl = is_object($status) ? ($status->option_name ?? $status->name ?? $val) : $status;
                                @endphp
                                <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th><input type="text" class="column-filter" name="notes_operation" value="{{ request('notes_operation') }}" placeholder="Notes"></th>
                        <th><input type="text" class="column-filter" name="created_by_name" value="{{ request('created_by_name') }}" placeholder="User"></th>
                        <th></th> <!-- Created At -->
                        <th><input type="text" class="column-filter" name="updated_by_name" value="{{ request('updated_by_name') }}" placeholder="User"></th>
                        <th></th> <!-- Updated At -->
                        <th></th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($materialIssues ?? [] as $issue)
                    @php
                        $jobSchedule = $issue->jobAssignSchedule->jobSchedule ?? null;
                        $materialIssue = $issue->materialIssue ?? null;
                        $team = $issue->jobAssignSchedule->team ?? $materialIssue->team ?? null;
                        $items = $materialIssue && $materialIssue->items ? $materialIssue->items : collect();
                        
                        // Group key for checkbox: job_number + team_id
                        $groupKey = ($jobSchedule ? $jobSchedule->job_number : 'unknown') . '_' . ($team ? $team->id : '0');
                    @endphp
                    
                    @if($items->count() > 0)
                        @foreach($items as $item)
                        @php
                            // MOM: Filter items by job_assign_schedule_id to prevent duplicate rows for multi-room jobs
                            // For backward compatibility, if job_assign_schedule_id is null, match by room_name
                            $itemScheduleId = $item->job_assign_schedule_id;
                            $currentScheduleId = $issue->job_assign_schedule_id;
                            
                            $showItem = false;
                            if ($itemScheduleId) {
                                // Priority 1: Match by direct ID link
                                $showItem = ($itemScheduleId == $currentScheduleId);
                            } else {
                                // Priority 2: Fallback for old data match by room name
                                // Get current room name from JAS
                                $currentRoomName = $issue->jobAssignSchedule->room_name ?? ($issue->jobAssignSchedule->jobAdviceRoom ? $issue->jobAssignSchedule->jobAdviceRoom->room_name : null);
                                if ($currentRoomName && $item->room_name) {
                                    $showItem = (trim(strtolower($currentRoomName)) === trim(strtolower($item->room_name)));
                                } else {
                                    // If no room info available, show it (safer fallback)
                                    $showItem = true;
                                }
                            }
                            
                            if (!$showItem) continue;
                        @endphp
                        @php
                            // Extract rental name from notes (format: "Room: X, Rental: Y, ComponentID: Z")
                            $rentalName = '-';
                            if ($item->notes && preg_match('/Rental:\s*([^,]+)/', $item->notes, $matches)) {
                                $rentalName = trim($matches[1]);
                            }
                            
                            // Extract rental detail ID from notes (support both labels for backward compatibility)
                            $rentalDetailId = null;
                            if ($item->notes && preg_match('/(?:RentalDetailID|ComponentID):\s*(\d+)/', $item->notes, $matches)) {
                                $rentalDetailId = $matches[1];
                            }
                            
                            // FALLBACK: If ID not found in notes, try to find it by matching Master Rental + Product Type
                            if (!$rentalDetailId && $rentalName !== '-' && $item->product && $item->product->product_type_id) {
                                $fallbackRental = $rentalDetailFallbackMap[$rentalName . '|' . $item->product->product_type_id] ?? null;
                                $rentalDetailId = $fallbackRental['id'] ?? null;
                            }
                            
                            $productType = $item->product && $item->product->productType ? $item->product->productType->name : '-';
                            $materialName = $item->product ? $item->product->name : '-';

                            $activeRoomFilter = trim((string) request('room_name', ''));
                            if ($activeRoomFilter !== '' && stripos((string) $item->room_name, $activeRoomFilter) === false) {
                                continue;
                            }

                            $activeRentalFilter = trim((string) request('rental_name', ''));
                            if ($activeRentalFilter !== '' && stripos($rentalName, $activeRentalFilter) === false) {
                                continue;
                            }

                            $activeMaterialFilter = trim((string) request('material_name', ''));
                            if ($activeMaterialFilter !== '' && stripos($materialName, $activeMaterialFilter) === false) {
                                continue;
                            }

                            $activeProductTypeFilter = trim((string) request('product_type', ''));
                            if ($activeProductTypeFilter !== '' && stripos($productType, $activeProductTypeFilter) === false) {
                                continue;
                            }

                            $productBomQty = 0;
                            if ($item->product) {
                                $packagingName = optional($item->product->packagingSize)->name;
                                if ($packagingName && preg_match('/(\d+(?:\.\d+)?)\s*ml/i', $packagingName, $matches)) {
                                    $productBomQty = (float) $matches[1];
                                } else {
                                    $productBomQty = (float) ($item->product->bom_quantity ?? 0);
                                }
                            }
                            $qtyBom = ($item->quantity ?? 0) * $productBomQty;

                            // Warehouse Stock Logic moved up
                            $building = $jobSchedule ? $jobSchedule->building : null;
                            $branch = $building
                                ? \App\Services\OperationalAreaService::resolveServiceBranchForBuilding($building)
                                : null;
                            if (!$branch && $team && $team->branch) {
                                $branch = $team->branch;
                            }
                            
                            $warehouseStock = 0;
                            $warehouse = $materialIssue ? $materialIssue->warehouse : null;
                            $warehouseInfo = "";

                            // If not found from material issue, try to find from branch logic (for pending/new items)
                            if (!$warehouse) {
                                if ($branch) {
                                    $warehouse = $branchWarehouseLookup[$branch->id] ?? null;
                                }
                            }
                            
                            // If we have a warehouse, get the stock
                            if ($warehouse && $item->product) {
                                $warehouseStock = $warehouseStockLookup[$warehouse->id . ':' . $item->product_id] ?? 0;
                            }

                            if (!$warehouse) {
                                if (!$jobSchedule || !$jobSchedule->building) {
                                    $warehouseInfo = "No building";
                                } elseif (!$jobSchedule->building->city) {
                                    $warehouseInfo = "Building has no city";
                                } elseif (!$team) {
                                    $warehouseInfo = "No team assigned";
                                } else {
                                    $warehouseInfo = "No branch found";
                                }
                            }

                            // --- TARGET MET CALCULATION (Phase 7) ---
                            $rentalDetail = $rentalDetailId ? ($rentalDetailById[$rentalDetailId] ?? null) : null;
                            $bomRentalQty = $rentalDetail ? $rentalDetail->bom_rental_qty : 0;

                            // QA "1 Rental banyak Qty": scale the BOM target by the rental qty so
                            // it matches the generated material (kemasan × qty) and the server-side
                            // BOM validation. Key = jobAssignScheduleId|room|masterRentalId.
                            $rentalQtyMultiplier = 1;
                            if ($bomRentalQty > 0 && $rentalDetail && ($rentalDetail->master_rental_id ?? null) && ($item->job_assign_schedule_id ?? null)) {
                                $qtyKey = $item->job_assign_schedule_id . '|'
                                    . strtolower(trim((string) $item->room_name)) . '|'
                                    . (int) $rentalDetail->master_rental_id;
                                $rentalQtyMultiplier = max(1, (int) (($rentalQtyMap ?? [])[$qtyKey] ?? 1));
                            }
                            $bomRentalQty = $bomRentalQty * $rentalQtyMultiplier;

                            $groupTotalVolume = 0;
                            if ($rentalDetailId && $items) {
                                $groupTotalVolume = $items->sum(function($it) use ($rentalDetailId) {
                                    if ($it->notes && preg_match('/(?:RentalDetailID|ComponentID):\s*' . $rentalDetailId . '/', $it->notes)) {
                                        $packagingName = optional($it->product->packagingSize)->name;
                                        if ($packagingName && preg_match('/(\d+(?:\.\d+)?)\s*ml/i', $packagingName, $matches)) {
                                            return ($it->quantity ?? 0) * (float) $matches[1];
                                        }

                                        return ($it->quantity ?? 0) * (float) ($it->product->bom_quantity ?? 0);
                                    }
                                    return 0;
                                });
                            }
                            $targetMet = $rentalDetailId && ($groupTotalVolume >= $bomRentalQty) && ($bomRentalQty > 0);

                            $copyMaterialHaystack = strtolower(implode(' ', array_filter([
                                $item->product?->productType?->name ?? null,
                                $item->product?->productCategory?->name ?? null,
                                $item->product?->name ?? null,
                                $item->product?->sku ?? null,
                                $item->product?->variant_name ?? null,
                                $item->product?->brand_line ?? null,
                            ])));
                            $isHandSanitizerCopyMaterial = str_contains($copyMaterialHaystack, 'hand sanitizer')
                                || str_contains($copyMaterialHaystack, 'sanitizer')
                                || preg_match('/\bhs\s*refill\b/', $copyMaterialHaystack)
                                || preg_match('/\bhsr[-\s]/', $copyMaterialHaystack)
                                || preg_match('/\bhsd[-\s]/', $copyMaterialHaystack);
                            $isCopyablePackageMaterial = $item->product
                                && !$isHandSanitizerCopyMaterial
                                && (
                                    str_contains($copyMaterialHaystack, 'aroma')
                                    || str_contains($copyMaterialHaystack, 'refill')
                                    || str_contains($copyMaterialHaystack, 'variant')
                                    || str_contains($copyMaterialHaystack, 'fragrance')
                                    || str_contains($copyMaterialHaystack, 'scent')
                                    || str_contains($copyMaterialHaystack, 'squash')
                                    || str_contains($copyMaterialHaystack, 'essence')
                                    || preg_match('/\boil\b/', $copyMaterialHaystack)
                                    || str_contains($copyMaterialHaystack, 'signature')
                                    || str_contains($copyMaterialHaystack, 'artisan')
                                    || str_contains($copyMaterialHaystack, 'luxo')
                                );
                            $canCopyMaterialItem = $materialIssue
                                && !in_array($materialIssue->status, ['issued', 'received', 'sent'], true)
                                && $isCopyablePackageMaterial;
                        @endphp
                        <tr class="job-group"
                            data-group="{{ $groupKey }}"
                            data-id="{{ $issue->id }}"
                            data-job-number="{{ $jobSchedule ? $jobSchedule->job_number : '' }}"
                            data-job-type="{{ $jobSchedule ? strtolower($jobSchedule->type) : '' }}"
                            data-product-bom-qty="{{ $productBomQty }}"
                            data-rental-detail-id="{{ $rentalDetailId ?? '' }}"
                            data-product-type="{{ $productType }}"
                            data-material-name="{{ $materialName }}"
                            data-warehouse-stock="{{ $warehouseStock ?? 0 }}">
                            <!-- 1. Checkbox -->
                            <td class="text-center">
                                <input type="checkbox" 
                                       class="group-checkbox row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" 
                                       data-group="{{ $groupKey }}" 
                                       value="{{ $issue->id }}" 
                                       data-job-number="{{ $jobSchedule ? $jobSchedule->job_number : '' }}" 
                                       onclick="event.stopPropagation()" 
                                       onchange="handleGroupCheckbox(this)">
                            </td>

                            <!-- 2. Customer -->
                            <td>{{ $jobSchedule && $jobSchedule->jobAdvice && $jobSchedule->jobAdvice->customer ? $jobSchedule->jobAdvice->customer->name : '-' }}</td>

                            <!-- 3. Gedung -->
                            <td>{{ $jobSchedule && $jobSchedule->building ? $jobSchedule->building->nama_gedung : '-' }}</td>

                            <!-- 4. Team -->
                            <td style="width: 250px; min-width: 250px;">{{ $team ? $team->team_name : '-' }}</td>
                            
                            <!-- 5. Job No -->
                            <td>{{ $jobSchedule ? $jobSchedule->job_number : '-' }}</td>
                            
                            <!-- 6. Tanggal Job -->
                            <td>{{ $materialIssue && $materialIssue->issue_date ? $materialIssue->issue_date->format('d/M/Y') : ($jobSchedule && $jobSchedule->schedule_date ? $jobSchedule->schedule_date->format('d/M/Y') : '-') }}</td>
                            
                            <!-- 7. Ruangan -->
                            <td>{{ $item->room_name ?? '-' }}</td>
                            
                            <!-- 8. Rental -->
                            <td>{{ $rentalName }}</td>
                            
                            <!-- 9. Material (Product Name) -->
                            <td>
                                @php
                                    // MOM11: editable while pending, approved, or out_of_stock — locked once issued/received/sent
                                    $isEditableStatus = $materialIssue && in_array($materialIssue->status, ['pending', 'approved', 'out_of_stock'], true);
                                @endphp

                                @if($canPrepareMaterial && ($item->is_copied || $isEditableStatus))
                                    @php
                                        // STRICT FILTERING LOGIC
                                        $currentProduct = $item->product;
                                        $currentProductTypeName = $currentProduct && $currentProduct->productType ? (string) $currentProduct->productType->name : '';
                                        $currentProductCategoryName = $currentProduct && $currentProduct->productCategory ? (string) $currentProduct->productCategory->name : '';

                                        // QA bug: variant_name on master_products is a generic brand-line code
                                        // (e.g. "Luxo GHI", "Artisan DEF") shared by MULTIPLE distinct aromas
                                        // within the same brand line (e.g. "Loco Floral" and "Ginger Blossom"
                                        // both carry variant_name "Luxo GHI"). Filtering by variant_name alone
                                        // therefore mixed unrelated aromas into the same dropdown — the
                                        // requirement is "show only size (ml) variants of THIS SAME aroma,
                                        // never switch to a different aroma." The product NAME (with the size
                                        // suffix stripped) is the only field that actually identifies which
                                        // aroma this is, so that's what we group by.
                                        $currentBaseName = $currentProduct ? trim(preg_replace([
                                            '/\b\d+(?:[\.,]\d+)?\s*ml\b/i',
                                            '/[-_\[\]\(\)]+/',
                                            '/\s+/',
                                        ], [
                                            '',
                                            ' ',
                                            ' ',
                                        ], $currentProduct->name ?? '')) : null;
                                        $normalizedCurrentBaseName = $currentBaseName ? strtolower($currentBaseName) : null;

                                        $currentVariant = $currentProduct ? $currentProduct->variant_name : null;
                                        if (!$currentVariant && $currentProduct) {
                                            $currentVariant = $currentBaseName;
                                        }
                                        
                                        // Detect if current item is an Aroma/Fragrance type
                                        $isAromaType = false;
                                        if ($currentProduct) {
                                            $productDetectionHaystack = strtolower(implode(' ', array_filter([
                                                $currentProductTypeName,
                                                $currentProductCategoryName,
                                                $currentProduct->name ?? '',
                                                $currentProduct->variant_name ?? '',
                                                $currentProduct->brand_line ?? '',
                                            ])));
                                            $isHandSanitizerType = str_contains($productDetectionHaystack, 'hand sanitizer')
                                                || str_contains($productDetectionHaystack, 'sanitizer')
                                                || preg_match('/\bhs\s*refill\b/', $productDetectionHaystack)
                                                || preg_match('/\bhsr[-\s]/', $productDetectionHaystack)
                                                || preg_match('/\bhsd[-\s]/', $productDetectionHaystack);
                                            $isAromaType = !$isHandSanitizerType && (str_contains($productDetectionHaystack, 'aroma')
                                                || str_contains($productDetectionHaystack, 'variant')
                                                || str_contains($productDetectionHaystack, 'fragrance')
                                                || str_contains($productDetectionHaystack, 'scent')
                                                || str_contains($productDetectionHaystack, 'refill')
                                                || str_contains($productDetectionHaystack, 'squash')
                                                || str_contains($productDetectionHaystack, 'essence')
                                                || str_contains($productDetectionHaystack, 'oil')
                                                || str_contains($productDetectionHaystack, 'signature')
                                                || str_contains($productDetectionHaystack, 'artisan')
                                                || str_contains($productDetectionHaystack, 'luxo'));
                                        }

                                        $allowedProductIds = [];
                                        if ($rentalDetailId && isset($allowedProductIdsByRentalDetailId[$rentalDetailId])) {
                                            $allowedProductIds = array_map('intval', $allowedProductIdsByRentalDetailId[$rentalDetailId]);
                                        }

                                        $normalizedCurrentVariant = $currentVariant
                                            ? strtolower(trim(preg_replace('/\s+/', ' ', $currentVariant)))
                                            : null;
                                        $normalizedCurrentBrandLine = $currentProduct && $currentProduct->brand_line
                                            ? strtolower(trim(preg_replace('/\s+/', ' ', $currentProduct->brand_line)))
                                            : null;
                                        $hasSpecificVariant = $isAromaType && !empty($normalizedCurrentVariant);
                                        $hasStrictAllowedProductList = $rentalDetailId && !empty($allowedProductIds);

                                        // Filter products list to the checked Material List for this rental detail.
                                        // Aroma/refill variants are expanded by BASE PRODUCT NAME (size suffix
                                        // stripped) so all packaging sizes of the SAME aroma appear, never a
                                        // different aroma — variant_name is too coarse for this (see note above).
                                        $filteredProducts = $products->filter(function($p) use ($isAromaType, $currentVariant, $normalizedCurrentVariant, $normalizedCurrentBaseName, $normalizedCurrentBrandLine, $hasSpecificVariant, $hasStrictAllowedProductList, $item, $allowedProductIds, $rentalDetailId) {
                                            $productBrandLine = $p->brand_line
                                                ? strtolower(trim(preg_replace('/\s+/', ' ', $p->brand_line)))
                                                : null;
                                            $productVariant = $p->variant_name
                                                ? strtolower(trim(preg_replace('/\s+/', ' ', $p->variant_name)))
                                                : null;
                                            $productName = strtolower($p->name ?? '');
                                            $productBaseName = trim(preg_replace([
                                                '/\b\d+(?:[\.,]\d+)?\s*ml\b/i',
                                                '/[-_\[\]\(\)]+/',
                                                '/\s+/',
                                            ], [
                                                '',
                                                ' ',
                                                ' ',
                                            ], $p->name ?? ''));
                                            $normalizedProductBaseName = strtolower($productBaseName);
                                            $sameVariant = $normalizedCurrentBaseName
                                                ? $normalizedProductBaseName === $normalizedCurrentBaseName
                                                : ($normalizedCurrentVariant && (
                                                    $productVariant === $normalizedCurrentVariant
                                                    || str_contains($productName, $normalizedCurrentVariant)
                                                ));

                                            if ($hasStrictAllowedProductList) {
                                                if ($hasSpecificVariant) {
                                                    if ((int) $p->id === (int) $item->product_id) {
                                                        return true;
                                                    }

                                                    if (!$sameVariant) {
                                                        return false;
                                                    }

                                                    if ($normalizedCurrentBrandLine && $productBrandLine && $normalizedCurrentBrandLine !== $productBrandLine) {
                                                        return false;
                                                    }

                                                    return true;
                                                }

                                                if (in_array((int) $p->id, $allowedProductIds, true) || (int) $p->id === (int) $item->product_id) {
                                                    return true;
                                                }

                                                return false;
                                            }

                                            // Keep current saved product only when no exact rental material list exists.
                                            if ($p->id == $item->product_id) return true;
                                            $hasGenericVariant = empty($productVariant);
                                            $isTestProduct = str_contains($productName, 'test');

                                            if ($hasSpecificVariant) {
                                                if ($normalizedCurrentBrandLine && $productBrandLine && $normalizedCurrentBrandLine !== $productBrandLine) {
                                                    return false;
                                                }

                                                // When a room already has a specific aroma variant, hide generic oil placeholders.
                                                if ($hasGenericVariant && !$sameVariant) {
                                                    return false;
                                                }

                                                if ($isTestProduct) {
                                                    return false;
                                                }
                                            } elseif ($normalizedCurrentBrandLine && $productBrandLine && $normalizedCurrentBrandLine !== $productBrandLine) {
                                                return false;
                                            }

                                            if ($rentalDetailId) {
                                                if (in_array((int) $p->id, $allowedProductIds, true) && (!$hasSpecificVariant || $sameVariant)) {
                                                    return true;
                                                }

                                                if ($isAromaType && $normalizedCurrentVariant) {
                                                    return $sameVariant;
                                                }

                                                return false;
                                            }
                                            
                                            if ($isAromaType && $currentVariant) {
                                                return $sameVariant;
                                            }
                                            return true; 
                                        });

                                        // Sort: Same variant first, then by name
                                        $sortedProducts = $filteredProducts->sortBy(function($p) use ($item) {
                                            $pVariant = $p->variant_name ?? '';
                                            $iVariant = $item->product ? ($item->product->variant_name ?? '') : '';
                                            if ($p->id == $item->product_id) return 0;
                                            if ($pVariant && $pVariant === $iVariant) return 1;
                                            return 2 . $p->name; 
                                        });
                                    @endphp
                                    <select class="material-select px-2 py-1 border border-gray-300 rounded text-sm"
                                            data-item-id="{{ $item->id }}"
                                            data-rental-detail-id="{{ $rentalDetailId }}"
                                            data-current-product-id="{{ $item->product_id }}"
                                            data-force-select2="1"
                                            style="width: 200px;"
                                            onclick="event.stopPropagation()">
                                        <option value="">Select Material...</option>
                                        @foreach($sortedProducts as $product)
                                            @php
                                                $displayName = $product->name;
                                                if ($product->packagingSize && stripos($displayName, $product->packagingSize->name) === false) {
                                                    $displayName .= " [{$product->packagingSize->name}]";
                                                } elseif ($product->packagingSize) {
                                                    $displayName .= " [{$product->packagingSize->name}]";
                                                }
                                            @endphp
                                            <option value="{{ $product->id }}" 
                                                    data-bom-qty="{{ $product->bom_quantity ?? 0 }}"
                                                    data-variant="{{ $product->variant_name }}"
                                                    data-product-type-id="{{ $product->product_type_id }}"
                                                    {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                {{ $displayName }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    {{ $materialName }}
                                @endif
                            </td>
                            
                            <!-- 10. Qty Issue (kemasan) -->
                            <td class="text-center">
                                <input type="number" 
                                       class="qty-issue-input px-2 py-1 border border-gray-300 rounded text-sm text-center" 
                                       style="width: 70px;" 
                                       data-item-id="{{ $item->id }}" 
                                       data-original-value="{{ $item->quantity ?? 0 }}"
                                       value="{{ $item->quantity ?? 0 }}" 
                                       min="0" 
                                       step="1"
                                       onclick="event.stopPropagation()"
                                       onfocus="event.stopPropagation()"
                                       @if(!$canPrepareMaterial) disabled @endif>
                            </td>
                            
                            <!-- 11. Warehouse -->
                            <td>
                                @if($materialIssue && $materialIssue->warehouse)
                                    {{ $materialIssue->warehouse->name }}
                                @elseif($warehouse)
                                    {{ $warehouse->name }}
                                @else
                                    <span class="text-gray-400 text-xs italic" title="{{ $warehouseInfo }}">{{ $warehouseInfo }}</span>
                                @endif
                            </td>
                            
                            <!-- 12. Warehouse Stock -->
                            <td class="text-center stock-display" style="color: {{ $warehouseStock > 0 ? '#059669' : '#dc2626' }}; font-weight: 600;">
                                @if($warehouse)
                                    {{ number_format($warehouseStock, 0) }}
                                @else
                                    -
                                @endif
                            </td>

                            <!-- 13. Tipe Material -->
                            <td>{{ $productType }}</td>
                            
                            <!-- 14. Qty BOM / Target -->
                            <td class="text-center bom-qty-display">
                                <span title="BOM Rental Qty: {{ number_format($bomRentalQty, 0) }}">{{ number_format($qtyBom, 0) }}</span>
                                <div class="text-[10px] text-blue-600 font-bold">Target: {{ number_format($bomRentalQty, 0) }}</div>
                                <input type="hidden" class="target-bom-qty" value="{{ $bomRentalQty }}">
                            </td>
                            
                            <!-- 15. Status -->
                            <td class="text-center">
                                @php
                                    $status = $materialIssue ? $materialIssue->status : 'Draft';
                                    $checkStatus = strtolower($status);
                                    $statusClass = 'status-pending'; 
                                    $displayStatus = ucfirst(str_replace('_', ' ', $status));
                                    
                                    if ($checkStatus === 'approved') {
                                        $statusClass = 'status-approved';
                                    } else if ($checkStatus === 'issued') {
                                        $statusClass = 'status-issued';
                                    } else if ($checkStatus === 'rejected') {
                                        $statusClass = 'status-rejected';
                                    } else if ($checkStatus === 'received') {
                                        $statusClass = 'status-received';
                                    } else if ($checkStatus === 'returned') {
                                        $statusClass = 'status-returned';
                                    } else if ($checkStatus === 'lost') {
                                        $statusClass = 'status-lost';
                                    }
                                    
                                    if (in_array($checkStatus, ['pending', 'draft', 'out_of_stock', 'out of stock'])) {
                                        if (isset($item->quantity) && $item->quantity > 0) {
                                            if ($warehouseStock < $item->quantity) {
                                                $statusClass = 'status-rejected'; 
                                                $displayStatus = 'OUT OF STOCK';
                                            } else {
                                                if ($checkStatus === 'out_of_stock' || $checkStatus === 'out of stock') {
                                                    $statusClass = 'status-pending';
                                                    $displayStatus = 'Pending';
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <span class="status-badge {{ $statusClass }}">
                                    {{ $displayStatus }}
                                </span>
                            </td>

                            <!-- 16. Op. Notes -->
                            <td class="text-center">
                                {{ $jobSchedule?->jobAdvice?->contract?->notes_operation ?? '-' }}
                            </td>
                            
                            <!-- 17. Created By -->
                            <td class="text-center">{{ $issue->createdBy ? $issue->createdBy->name : '-' }}</td>
                            
                            <!-- 18. Created At -->
                            <td class="text-center">
                                @if($issue->created_at)
                                    {{ $issue->created_at->format('d/M/Y') }}<br>
                                    at {{ $issue->created_at->format('H.i') }} WIB
                                @else
                                    -
                                @endif
                            </td>
                            
                            <!-- 19. Last Updated By -->
                            <td class="text-center">{{ $issue->updatedBy ? $issue->updatedBy->name : '-' }}</td>
                            
                            <!-- 20. Last Updated At -->
                            <td class="text-center">
                                @if($issue->updated_at)
                                    {{ $issue->updated_at->format('d/M/Y') }}<br>
                                    at {{ $issue->updated_at->format('H.i') }} WIB
                                @else
                                    -
                                @endif
                            </td>
                            
                            <!-- 21. Action -->
                            <td class="text-center">
                                @if($canPrepareMaterial && $canCopyMaterialItem)
                                <button class="btn-copy px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs" 
                                        onclick="copyMaterial({{ $item->id }})" 
                                        title="Copy Material for Package Conversion">
                                    📋
                                </button>
                                @endif
                                @if($canPrepareMaterial && $item->is_copied)
                                <button class="btn-delete px-2 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-xs ml-1" 
                                        onclick="deleteCopiedMaterial({{ $item->id }})" 
                                        title="Delete Copied Material">
                                    🗑️
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <!-- Fallback if no items -->
                        <tr data-id="{{ $issue->id }}" data-job-number="{{ $jobSchedule ? $jobSchedule->job_number : '' }}" data-job-type="{{ $jobSchedule ? strtolower($jobSchedule->type) : '' }}">
                            <td class="text-center">
                                <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $issue->id }}" data-job-number="{{ $jobSchedule ? $jobSchedule->job_number : '' }}" onclick="event.stopPropagation()" onchange="handleCheckboxChange(this)">
                            </td>
                            <td>{{ $jobSchedule && $jobSchedule->jobAdvice && $jobSchedule->jobAdvice->customer ? $jobSchedule->jobAdvice->customer->name : '-' }}</td>
                            <td>{{ $jobSchedule && $jobSchedule->building ? $jobSchedule->building->nama_gedung : '-' }}</td>
                            <td style="width: 250px; min-width: 250px;">{{ $team ? $team->team_name : '-' }}</td>
                            <td>{{ $jobSchedule ? $jobSchedule->job_number : '-' }}</td>
                            <td>{{ $jobSchedule && $jobSchedule->schedule_date ? $jobSchedule->schedule_date->format('d M') : '-' }}</td>
                            <td colspan="15" class="text-center text-gray-500">No materials configured</td>
                        </tr>
                    @endif
                    
                    @empty
                    <tr>
                        <td colspan="15" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No material issues found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if(isset($materialIssues) && $materialIssues->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            {{ $materialIssues->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Material Issue</h2>
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
            <svg class="delete-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h3 class="delete-modal-title">Hide Material Issue</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this material issue? This action can be undone later.</p>
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
            <svg class="delete-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h3 class="delete-modal-title" style="color: #ef4444;">Hmm... Something Went Wrong</h3>
        <p class="delete-modal-description" id="errorMessage">We couldn't hide the material issue. Please try again.</p>
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
            <svg class="delete-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h3 class="delete-modal-title" style="color: #10b981;">All Set!</h3>
        <p class="delete-modal-description" id="successMessage">The material issue has been successfully hidden.</p>
    </div>
</div>

<!-- Variant Change Modal -->
<div id="variantChangeModalOverlay" class="modal-overlay" onclick="closeVariantChangeModal()">
    <div class="modal-container" style="max-width: 600px;" onclick="event.stopPropagation()">
        <div class="modal-header" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);">
            <h2 class="modal-title"><i class="fas fa-exchange-alt" style="margin-right: 10px;"></i>Request Variant Change</h2>
            <button class="modal-close" onclick="closeVariantChangeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="variantChangeModalBody" style="padding: 24px;">
            <form id="variantChangeForm">
                <input type="hidden" id="vcf_material_issue_id" name="material_issue_id">
                <input type="hidden" id="vcf_room_name" name="room_name">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="font-weight: 600; color: #374151;">Room</label>
                    <div id="vcf_room_display" style="padding: 10px 14px; background: #f9fafb; border-radius: 8px; font-weight: 500;"></div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 16px; align-items: center; margin-bottom: 20px;">
                    <div>
                        <label class="form-label" style="font-weight: 600; color: #374151;">Current Variant</label>
                        <div id="vcf_current_variant" style="padding: 10px 14px; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; font-weight: 500; text-align: center;"></div>
                    </div>
                    <div style="padding-top: 24px;">
                        <i class="fas fa-arrow-right" style="color: #9ca3af; font-size: 20px;"></i>
                    </div>
                    <div>
                        <label class="form-label" style="font-weight: 600; color: #374151;">New Variant <span style="color: #ef4444;">*</span></label>
                        <select id="vcf_new_variant" name="new_variant" class="form-input" style="width: 100%;" required>
                            <option value="">-- Select Variant --</option>
                        </select>
                    </div>
                </div>
                
                <div id="vcf_same_variant_notice" style="display: none; padding: 12px 16px; background: #d1fae5; border: 2px solid #10b981; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle" style="color: #10b981; margin-right: 8px;"></i>
                    <span style="color: #065f46; font-weight: 500;">Same variant selected - will be auto-approved</span>
                </div>
                
                <div id="vcf_different_variant_notice" style="display: none; padding: 12px 16px; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle" style="color: #f59e0b; margin-right: 8px;"></i>
                    <span style="color: #92400e; font-weight: 500;">Different variant - requires Manager approval</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; color: #374151;">Reason for Change</label>
                    <textarea id="vcf_change_reason" name="change_reason" class="form-input" rows="3" placeholder="e.g., Customer request, stock availability, etc."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb;">
            <button type="button" class="btn btn-secondary" onclick="closeVariantChangeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitVariantChangeRequest()" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); border: none;">
                <i class="fas fa-paper-plane" style="margin-right: 6px;"></i>Submit Request
            </button>
        </div>
    </div>
</div>

<!-- Pending Variant Changes Modal -->
<div id="pendingVariantChangesModalOverlay" class="modal-overlay" onclick="closePendingVariantChangesModal()">
    <div class="modal-container" style="max-width: 800px;" onclick="event.stopPropagation()">
        <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);">
            <h2 class="modal-title"><i class="fas fa-clock" style="margin-right: 10px;"></i>Pending Variant Changes</h2>
            <button class="modal-close" onclick="closePendingVariantChangesModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="pendingVariantChangesModalBody" style="padding: 24px;">
            <!-- Content loaded dynamically -->
        </div>
        <div class="modal-footer" style="padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb;">
            <button type="button" class="btn btn-secondary" onclick="closePendingVariantChangesModal()">Close</button>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

// Pass dynamic options to JavaScript
const requestReasons = @json($requestReasons ?? []);
const priorities = @json($priorities ?? []);
const statuses = @json($statuses ?? []);
const products = @json($products ?? []);
window.products = products;

function getProductBomPerUnit(product) {
    if (!product) {
        return 0;
    }

    const packagingName = product.packagingSize?.name ||
        product.packaging_size?.name ||
        product.packaging_size_name ||
        product.packaging_size ||
        '';
    const packagingMatch = String(packagingName).match(/(\d+(?:\.\d+)?)\s*ml/i);
    if (packagingMatch) {
        return parseFloat(packagingMatch[1]) || 0;
    }

    return parseFloat(product.bom_quantity || 0);
}

function getProductPackagingSizeId(product) {
    return product?.packaging_size_id ||
        product?.packagingSize?.id ||
        product?.packaging_size?.id ||
        '';
}

function getProductPackagingName(product) {
    return product?.packagingSize?.name ||
        product?.packaging_size?.name ||
        product?.packaging_size_name ||
        product?.packaging_size ||
        '';
}

function formatMaterialOptionLabel(product) {
    if (!product) {
        return 'Unknown product';
    }

    const sku = product.sku || product.code;
    const packaging = getProductPackagingName(product);
    const suffix = [
        sku ? `SKU: ${sku}` : '',
        packaging ? `Size: ${packaging}` : '',
    ].filter(Boolean).join(' | ');

    return suffix ? `${product.name} (${suffix})` : product.name;
}

function findProductForPackagingSize(currentProduct, candidateProducts, packagingSizeId) {
    if (!packagingSizeId) {
        return null;
    }

    const currentFamily = normalizePackageMaterialFamily(currentProduct);
    const currentBrandLine = normalizeProductBrandLine(currentProduct);

    return candidateProducts.find(product => {
        if (String(getProductPackagingSizeId(product)) !== String(packagingSizeId)) {
            return false;
        }

        if (currentFamily && normalizePackageMaterialFamily(product) !== currentFamily) {
            return false;
        }

        const productBrandLine = normalizeProductBrandLine(product);
        return !currentBrandLine || !productBrandLine || currentBrandLine === productBrandLine;
    }) || null;
}

function normalizeProductBrandLine(product) {
    return String(product?.brand_line || '').trim().toLowerCase().replace(/\s+/g, ' ') || null;
}

function normalizePackageMaterialFamily(product) {
    const source = String(product?.variant_name || product?.name || '').trim();
    if (!source) {
        return null;
    }

    return source
        .toLowerCase()
        .replace(/\b\d+(?:\.\d+)?\s*(ml|liter|ltr|l)\b/gi, ' ')
        .replace(/\[[^\]]*\]|\([^\)]*\)/g, ' ')
        .replace(/[-_]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim() || null;
}

function isPackageConversionMaterialProduct(product) {
    if (!product) {
        return false;
    }

    const haystack = [
        product.productType?.name,
        product.product_type?.name,
        product.productCategory?.name,
        product.product_category?.name,
        product.name,
        product.sku,
        product.variant_name,
        product.brand_line,
    ].filter(Boolean).join(' ').toLowerCase();

    if (haystack.includes('hand sanitizer') || haystack.includes('sanitizer') || /\bhs\s*refill\b/.test(haystack) || /\bhsr[-\s]/.test(haystack) || /\bhsd[-\s]/.test(haystack)) {
        return false;
    }

    return ['aroma', 'refill', 'variant', 'fragrance', 'scent', 'squash', 'essence', 'signature', 'artisan', 'luxo'].some(keyword => haystack.includes(keyword))
        || /\boil\b/.test(haystack);
}

function filterSamePackageMaterialFamily(currentProduct, productList) {
    if (!isPackageConversionMaterialProduct(currentProduct)) {
        return productList;
    }

    const currentFamily = normalizePackageMaterialFamily(currentProduct);
    const currentBrandLine = normalizeProductBrandLine(currentProduct);

    if (!currentFamily) {
        return productList;
    }

    return productList.filter(product => {
        if (String(product.id) === String(currentProduct.id)) {
            return true;
        }

        if (normalizePackageMaterialFamily(product) !== currentFamily) {
            return false;
        }

        const productBrandLine = normalizeProductBrandLine(product);
        return !currentBrandLine || !productBrandLine || currentBrandLine === productBrandLine;
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
    document.getElementById('modalFooter').innerHTML = '';
}

// CRUD Modal functions

// MOM10: Show Material Issue detail when row is clicked
function showMaterialIssue(id) {
    openModal('View Material Issue');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    document.getElementById('modalFooter').innerHTML = '';
    
    fetch(`/operational/job-assign-material-issues/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const issue = data.data;
                const rentalProducts = issue.rental_products || [];
                
                // Helper function to get variant for a room from metadata
                const getVariantForRoom = (roomName) => {
                    const requests = issue.metadata?.variant_change_requests || [];
                    // Find the latest approved or pending request for this room
                    const roomRequests = requests.filter(r => r.room_name === roomName);
                    if (roomRequests.length === 0) return null;
                    
                    // Get the most recent request (approved first, then pending)
                    const approved = roomRequests.find(r => r.status === 'approved');
                    if (approved) return { variant: approved.new_variant, status: 'approved' };
                    
                    const pending = roomRequests.find(r => r.status === 'pending_approval');
                    if (pending) return { variant: pending.new_variant, status: 'pending' };
                    
                    return null;
                };
                
                let rentalProductsHtml = '';
                if (rentalProducts.length > 0) {
                    rentalProductsHtml = `
                        <div style="margin-top: 20px;">
                            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #374151;">Detail Produk dari Rental</h3>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                                    <thead>
                                        <tr style="background-color: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                            <th style="padding: 10px; text-align: left; font-weight: 600;">Room</th>
                                            <th style="padding: 10px; text-align: left; font-weight: 600;"><i class="fas fa-palette" style="margin-right: 4px; color: #7c3aed;"></i>Variant</th>
                                            <th style="padding: 10px; text-align: left; font-weight: 600;">Rental</th>
                                            <th style="padding: 10px; text-align: left; font-weight: 600;">Component</th>
                                            <th style="padding: 10px; text-align: left; font-weight: 600;">Product</th>
                                            <th style="padding: 10px; text-align: left; font-weight: 600;">Package Size</th>
                                            <th style="padding: 10px; text-align: right; font-weight: 600;">Qty</th>
                                            <th style="padding: 10px; text-align: right; font-weight: 600;">Stock</th>
                                            <th style="padding: 10px; text-align: left; font-weight: 600;">Warehouse</th>
                                            <th style="padding: 10px; text-align: right; font-weight: 600;">Qty Convert</th>
                                            <th style="padding: 10px; text-align: right; font-weight: 600;">BOM</th>
                                            <th style="padding: 10px; text-align: left; font-weight: 600;">Updated Date</th>
                                            <th style="padding: 10px; text-align: left; font-weight: 600;">Updated By</th>
                                            <th style="padding: 10px; text-align: left; font-weight: 600;">Notes Perubahan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rentalProducts.map(rp => {
                                            const savedVariant = getVariantForRoom(rp.room_name);
                                            const displayVariant = savedVariant ? savedVariant.variant : (rp.product?.variant_name || rp.variant_name || 'Not Set');
                                            const variantStatus = savedVariant ? savedVariant.status : null;
                                            const bgColor = variantStatus === 'pending' ? 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)' : 'linear-gradient(135deg, #7c3aed 0%, #a855f7 100%)';
                                            const statusIcon = variantStatus === 'pending' ? '<i class="fas fa-clock" style="margin-left: 4px;"></i>' : (variantStatus === 'approved' ? '<i class="fas fa-check" style="margin-left: 4px;"></i>' : '');
                                            
                                            return `
                                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                                <td style="padding: 10px;">${rp.room_name || '-'}</td>
                                                <td style="padding: 10px;">
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <span style="padding: 4px 10px; background: ${bgColor}; color: white; border-radius: 6px; font-size: 12px; font-weight: 500;">
                                                            ${displayVariant}${statusIcon}
                                                        </span>
                                                        <button type="button" onclick="event.stopPropagation(); openVariantChangeModal(${issue.id}, '${rp.room_name}', '${displayVariant}')" class="btn btn-sm" style="padding: 4px 8px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 4px; font-size: 11px; cursor: pointer;" title="Change Variant">
                                                            <i class="fas fa-exchange-alt" style="color: #7c3aed;"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td style="padding: 10px;">${rp.rental_name || '-'}</td>
                                                <td style="padding: 10px;">${rp.component_name || '-'}</td>
                                                <td style="padding: 10px;">
                                                    <div>${rp.product.name || '-'}</div>
                                                    <div style="font-size: 12px; color: #6b7280;">${rp.product.code || ''} ${rp.product.product_type ? `(${rp.product.product_type})` : ''}</div>
                                                </td>
                                                <td style="padding: 10px;">${rp.product.packaging_size || '-'}</td>
                                                <td style="padding: 10px; text-align: right;">${rp.quantity || 0}</td>
                                                <td style="padding: 10px; text-align: right; color: ${rp.stock > 0 ? '#059669' : '#dc2626'}; font-weight: 600;">${rp.stock || 0}</td>
                                                <td style="padding: 10px;">${rp.warehouse.name || '-'}</td>
                                                <td style="padding: 10px; text-align: right;">${rp.convert !== undefined && rp.convert !== null ? rp.convert : '-'}</td>
                                                <td style="padding: 10px; text-align: right;">${rp.bom_quantity !== undefined && rp.bom_quantity !== null ? rp.bom_quantity : '-'}</td>
                                                <td style="padding: 10px; text-align: left;">${rp.updated_at ? new Date(rp.updated_at).toLocaleDateString('id-ID', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : '-'}</td>
                                                <td style="padding: 10px; text-align: left;">${rp.updated_by_name || '-'}</td>
                                                <td style="padding: 10px; text-align: left; max-width: 200px; word-wrap: break-word;">${issue.product_change_note || '-'}</td>
                                            </tr>
                                        `}).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                } else {
                    rentalProductsHtml = '<div style="margin-top: 20px; padding: 20px; background-color: #f9fafb; border-radius: 8px; text-align: center; color: #6b7280;">Tidak ada produk dari rental untuk ditampilkan.</div>';
                }
                
                // MOM12: Build product change history section
                let changeHistoryHtml = '';
                if (issue.previous_product_id || issue.product_change_note) {
                    changeHistoryHtml = `
                        <div style="margin-top: 20px; padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #f59e0b; border-radius: 10px;">
                            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #92400e; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-history" style="color: #f59e0b;"></i>
                                Riwayat Perubahan Produk
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                ${issue.previous_product ? `
                                <div>
                                    <label style="font-size: 12px; color: #92400e; display: block; margin-bottom: 4px; font-weight: 600;">Produk Sebelumnya</label>
                                    <div style="font-size: 14px; font-weight: 500; color: #dc2626; background: white; padding: 8px 12px; border-radius: 6px;">
                                        <i class="fas fa-times-circle" style="margin-right: 6px;"></i>
                                        ${issue.previous_product.name}
                                    </div>
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #92400e; display: block; margin-bottom: 4px; font-weight: 600;">Produk Saat Ini</label>
                                    <div style="font-size: 14px; font-weight: 500; color: #059669; background: white; padding: 8px 12px; border-radius: 6px;">
                                        <i class="fas fa-check-circle" style="margin-right: 6px;"></i>
                                        ${issue.product?.name || '-'}
                                    </div>
                                </div>
                                ` : ''}
                                <div>
                                    <label style="font-size: 12px; color: #92400e; display: block; margin-bottom: 4px; font-weight: 600;">Tanggal Perubahan</label>
                                    <div style="font-size: 14px; font-weight: 500; background: white; padding: 8px 12px; border-radius: 6px;">
                                        <i class="fas fa-calendar-alt" style="margin-right: 6px; color: #6b7280;"></i>
                                        ${issue.product_changed_at ? new Date(issue.product_changed_at).toLocaleDateString('id-ID', {year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'}) : '-'}
                                    </div>
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #92400e; display: block; margin-bottom: 4px; font-weight: 600;">Diubah Oleh</label>
                                    <div style="font-size: 14px; font-weight: 500; background: white; padding: 8px 12px; border-radius: 6px;">
                                        <i class="fas fa-user" style="margin-right: 6px; color: #6b7280;"></i>
                                        ${issue.product_changed_by?.name || '-'}
                                    </div>
                                </div>
                            </div>
                            ${issue.product_change_note ? `
                            <div style="margin-top: 12px;">
                                <label style="font-size: 12px; color: #92400e; display: block; margin-bottom: 4px; font-weight: 600;">Alasan Perubahan</label>
                                <div style="font-size: 14px; background: white; padding: 12px; border-radius: 6px; border-left: 4px solid #f59e0b; white-space: pre-wrap;">
                                    <i class="fas fa-comment-alt" style="margin-right: 6px; color: #f59e0b;"></i>
                                    ${issue.product_change_note}
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    `;
                }
                
                document.getElementById('modalBody').innerHTML = `
                    <div style="padding: 20px;">
                        <div style="margin-bottom: 20px;">
                            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #374151;">Informasi Material Issue</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div>
                                    <label style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Issue Number</label>
                                    <div style="font-size: 14px; font-weight: 500;">${issue.issue_number || '-'}</div>
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Job Number</label>
                                    <div style="font-size: 14px; font-weight: 500;">${issue.job_assign_schedule?.job_schedule?.job_number || '-'}</div>
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Customer</label>
                                    <div style="font-size: 14px; font-weight: 500;">${issue.job_assign_schedule?.job_schedule?.customer?.name || '-'}</div>
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Team</label>
                                    <div style="font-size: 14px; font-weight: 500;">${issue.team?.team_name || '-'}</div>
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Warehouse</label>
                                    <div style="font-size: 14px; font-weight: 500;">${issue.warehouse?.name || '-'}</div>
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #6b7280; display: block; margin-bottom: 4px;">Status</label>
                                    <div style="font-size: 14px; font-weight: 500;">
                                        <span class="status-badge status-${(issue.status || 'pending').replace('_', '-')}">
                                            ${(issue.status || 'Pending').charAt(0).toUpperCase() + (issue.status || 'Pending').slice(1).replace('_', ' ')}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        ${changeHistoryHtml}
                        ${rentalProductsHtml}
                    </div>
                `;
                // Build footer buttons based on status
                let footerButtons = `<button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>`;
                
                // Add Pending Variant Changes button (always show if user has permission)
                const pendingRequests = (issue.metadata?.variant_change_requests || []).filter(r => r.status === 'pending_approval');
                const pendingCount = pendingRequests.length;
                footerButtons += `<button type="button" class="btn" style="background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white; border: none;" onclick="openPendingVariantChangesModal(${id})">
                    <i class="fas fa-clock"></i> Pending Approvals ${pendingCount > 0 ? '<span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 6px;">' + pendingCount + '</span>' : ''}
                </button>`;
                
                // Add Approve button if status is draft or pending
                if (issue.status === 'draft' || issue.status === 'pending') {
                    footerButtons += `<button type="button" class="btn btn-success" onclick="approveMaterialIssue(${id})">
                        <i class="fas fa-check"></i> Approve
                    </button>`;
                }
                
                // Add Edit button only if NOT approved or issued
                if (issue.status !== 'approved' && issue.status !== 'issued') {
                    footerButtons += `<button type="button" class="btn btn-primary" onclick="openEditModal(${id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>`;
                }
                
                document.getElementById('modalFooter').innerHTML = footerButtons;
            } else {
                document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading material issue details.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading material issue details.</div>';
        });
}

function openEditModal(id) {
    openModal('Edit Material Issue');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    document.getElementById('modalFooter').innerHTML = '';
    
    fetch(`/operational/job-assign-material-issues/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            const today = new Date().toISOString().split('T')[0]; // Default hari ini
            const rentalProducts = data.data.rental_products || [];
            const availableProducts = data.data.products || [];
            const productTypes = data.data.product_types || []; // MOM12: Component types
            
            // Store globally for component change handler
            window.availableProducts = availableProducts;
            window.productTypes = productTypes;
            
            // Get status badge class
            const statusBadgeClass = data.data.status === 'issued' ? 'status-issued' : 
                                    data.data.status === 'approved' ? 'status-approved' : 
                                    data.data.status === 'pending' ? 'status-pending' : 'status-rejected';
            
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update material issue details.</p>
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <div class="modal-section">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="form-label">Warehouse</label>
                                <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${data.data.warehouse?.name || 'N/A'}</div>
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <div><span class="status-badge ${statusBadgeClass}">${(data.data.status || '').toUpperCase()}</span></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Issue Date *</label>
                            <input type="date" name="issue_date" class="form-input" value="${today}" required>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <h3 class="modal-section-title">Detail Produk dari Rental</h3>
                        <div style="overflow-x: auto;">
                            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb; background: #f9fafb;">Room</th>
                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb; background: #f9fafb;">Rental</th>
                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb; background: #f9fafb;">Component</th>
                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb; background: #f9fafb;">Product</th>
                                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb; background: #f9fafb;">Package Size</th>
                                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e5e7eb; background: #f9fafb;">Qty</th>
                                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e5e7eb; background: #f9fafb;">Convert</th>
                                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e5e7eb; background: #f9fafb;">BOM</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rentalProducts.map((item, index) => {
                                        // MOM12: Check if this is a unit product. Component (jenis komponen)
                                        // stays locked for unit rows, but Product (the specific unit variant)
                                        // is now editable - bug #18 (confirmed with client 2026-06-22): a unit
                                        // CAN be swapped for a different variant in the SAME category (e.g.
                                        // "Diffuser W300 Black" -> "Diffuser W300 White"), just not to a
                                        // different unit category entirely.
                                        const isUnit = item.is_unit || false;
                                        const productTypeId = item.product_type_id || null;
                                        const productCategoryId = item.product_category_id || null;
                                        const allowedProductIds = (item.allowed_product_ids || []).map(id => String(id));

                                        // Material options must follow the exact Material List selected in Rental Detail.
                                        let filteredProducts = availableProducts;
                                        if (isUnit) {
                                            // Unit swap is restricted to the SAME unit category (e.g. only other
                                            // Diffuser variants), never across categories (e.g. Diffuser -> Dispenser).
                                            filteredProducts = availableProducts.filter(p => {
                                                const isSameCategoryUnit = productCategoryId
                                                    ? (p.product_category_id == productCategoryId || (p.productCategory && p.productCategory.id == productCategoryId))
                                                    : false;
                                                return isSameCategoryUnit;
                                            });
                                        } else if (allowedProductIds.length > 0) {
                                            filteredProducts = availableProducts.filter(p => allowedProductIds.includes(String(p.id)));
                                        } else if (productTypeId) {
                                            filteredProducts = availableProducts.filter(p => p.product_type_id == productTypeId || (p.productType && p.productType.id == productTypeId));
                                        } else if (productCategoryId) {
                                            filteredProducts = availableProducts.filter(p => p.product_category_id == productCategoryId || (p.productCategory && p.productCategory.id == productCategoryId));
                                        }

                                        if (!isUnit) {
                                            filteredProducts = filterSamePackageMaterialFamily(item.product, filteredProducts);
                                        }

                                        // Unit row styling
                                        const rowStyle = isUnit ? 'background-color: #f9fafb;' : '';
                                        const unitBadge = isUnit ? '<span style="display: inline-block; padding: 2px 6px; background: #dbeafe; color: #1e40af; border-radius: 4px; font-size: 10px; margin-left: 4px;">UNIT</span>' : '';

                                        // MOM12: Build component cell - dropdown for non-unit, always locked for unit
                                        let componentCell = '';
                                        if (isUnit) {
                                            componentCell = '<div style="padding: 8px 12px; background: #f3f4f6; border-radius: 6px; color: #6b7280;"><i class="fas fa-lock" style="margin-right: 6px; font-size: 11px;"></i>' + (item.component_name || 'N/A') + '</div>' + unitBadge;
                                        } else {
                                            let componentOptions = '';
                                            productTypes.forEach(pt => {
                                                const selected = pt.id == productTypeId ? 'selected' : '';
                                                componentOptions += '<option value="' + pt.id + '" ' + selected + '>' + pt.name + '</option>';
                                            });
                                            componentCell = '<select name="rental_products[' + index + '][product_type_id]" class="form-input component-select" data-row-index="' + index + '" onchange="handleComponentChange(this, ' + index + ')" style="min-width: 150px;">' + componentOptions + '</select>';
                                        }

                                        // Build product cell content - dropdown for both unit (same-category
                                        // variants only) and non-unit rows.
                                        let optionProducts = filteredProducts.slice();
                                        const currentProductAllowed = optionProducts.some(p => String(p.id) === String(item.product.id));
                                        if (!currentProductAllowed) {
                                            optionProducts.unshift(item.product);
                                        }

                                        let options = '';
                                        if (!currentProductAllowed) {
                                            options += '<option value="" selected>-- Select Material --</option>';
                                        }
                                        optionProducts.forEach(p => {
                                            const selected = String(p.id) === String(item.product.id) ? ' selected' : '';
                                            options += '<option value="' + p.id + '"' + selected + '>' + formatMaterialOptionLabel(p) + '</option>';
                                        });
                                        const productCell = '<select name="rental_products[' + index + '][product_id]" id="product_select_' + index + '" class="form-input product-select" data-original-product-id="' + item.product.id + '" data-component-id="' + item.component_id + '" data-product-type-id="' + (productTypeId || '') + '" data-product-category-id="' + (productCategoryId || '') + '" data-allowed-product-ids="' + allowedProductIds.join(',') + '" data-is-unit="' + (isUnit ? '1' : '0') + '" onchange="handleRentalProductChange(this, ' + index + ')" style="min-width: 200px;">' + options + '</select>';
                                        
                                        // Build packaging size cell
                                        let packagingCell = '<span style="color: #9ca3af;">-</span>';
                                        if (item.packaging_sizes && item.packaging_sizes.length > 0 && !isUnit) {
                                            let psOptions = '';
                                            item.packaging_sizes.forEach(ps => {
                                                const selected = ps.id == item.product.packaging_size_id ? 'selected' : '';
                                                psOptions += '<option value="' + ps.id + '" ' + selected + '>' + ps.name + '</option>';
                                            });
                                            packagingCell = '<select name="rental_products[' + index + '][packaging_size_id]" id="packaging_select_' + index + '" class="form-input package-size-select" data-row-index="' + index + '" data-current-packaging-size-id="' + (item.product.packaging_size_id || '') + '" onchange="handlePackagingSizeChange(this, ' + index + ')" style="min-width: 150px;">' + psOptions + '</select>';
                                        }
                                        
                                        // Build Qty cell - editable for non-unit
                                        let qtyCell = '';
                                        if (isUnit) {
                                            qtyCell = '<span style="color: #6b7280;">' + (item.quantity || 0) + '</span><input type="hidden" name="rental_products[' + index + '][quantity]" value="' + (item.quantity || 0) + '">';
                                        } else {
                                            qtyCell = '<input type="number" name="rental_products[' + index + '][quantity]" class="form-input" value="' + (item.quantity || 0) + '" min="0" style="width: 70px; text-align: center;">';
                                        }
                                        
                                        // Build Convert cell - editable for non-unit
                                        let convertCell = '';
                                        if (isUnit) {
                                            convertCell = '<span style="color: #6b7280;">' + (item.convert || 1) + '</span><input type="hidden" name="rental_products[' + index + '][convert]" value="' + (item.convert || 1) + '">';
                                        } else {
                                            convertCell = '<input type="number" name="rental_products[' + index + '][convert]" class="form-input" value="' + (item.convert || 1) + '" min="1" step="0.01" style="width: 70px; text-align: center;">';
                                        }
                                        
                                        // Build BOM cell - editable for non-unit
                                        let bomCell = '';
                                        if (isUnit) {
                                            bomCell = '<span style="color: #6b7280;">' + (item.bom_quantity || 0) + '</span><input type="hidden" name="rental_products[' + index + '][bom_quantity]" value="' + (item.bom_quantity || 0) + '">';
                                        } else {
                                            bomCell = '<input type="number" name="rental_products[' + index + '][bom_quantity]" class="form-input" value="' + (item.bom_quantity || 0) + '" min="0" step="0.01" style="width: 70px; text-align: center;">';
                                        }
                                        
                                        return '<tr style="' + rowStyle + '">' +
                                            '<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">' + (item.room_name || 'N/A') + '</td>' +
                                            '<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">' + (item.rental_name || 'N/A') + '</td>' +
                                            '<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">' + componentCell + '</td>' +
                                            '<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">' + productCell + '<input type="hidden" name="rental_products[' + index + '][component_id]" value="' + item.component_id + '"><input type="hidden" name="rental_products[' + index + '][room_name]" value="' + item.room_name + '"></td>' +
                                            '<td style="padding: 12px; border-bottom: 1px solid #e5e7eb;">' + packagingCell + '</td>' +
                                            '<td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">' + qtyCell + '</td>' +
                                            '<td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">' + convertCell + '</td>' +
                                            '<td style="padding: 12px; text-align: center; border-bottom: 1px solid #e5e7eb;">' + bomCell + '</td>' +
                                        '</tr>';
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="form-group" id="product_change_note_group" style="display: none; padding: 16px; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                <i class="fas fa-exclamation-triangle" style="color: #f59e0b; font-size: 20px;"></i>
                                <label class="form-label" style="margin: 0; font-weight: 600; color: #92400e;">Alasan Perubahan Produk <span class="text-danger">*</span></label>
                            </div>
                            <textarea name="product_change_note" id="product_change_note" class="form-input form-textarea" placeholder="Wajib diisi ketika produk diubah. Contoh: Aroma diganti karena stok habis, atau preferensi customer, dll." rows="3" style="border-color: #f59e0b;"></textarea>
                            <small style="color: #92400e; font-weight: 500;">⚠️ Field ini wajib diisi karena Anda mengubah produk</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-input form-textarea" placeholder="Enter notes" rows="4">${data.data.notes || ''}</textarea>
                        </div>
                    </div>
                    
                    ${data.data.previous_product_id ? `
                    <div class="modal-section">
                        <h3 class="modal-section-title">Riwayat Perubahan Produk</h3>
                        <div class="form-group">
                            <label class="form-label">Produk Sebelumnya</label>
                            <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${data.data.previous_product?.name || 'N/A'}</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Perubahan</label>
                            <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px;">${data.data.product_changed_at ? new Date(data.data.product_changed_at).toLocaleDateString('id-ID') : 'N/A'}</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alasan Perubahan</label>
                            <div style="padding: 8px 12px; background: #f9fafb; border-radius: 6px; white-space: pre-wrap;">${data.data.product_change_note || 'N/A'}</div>
                        </div>
                    </div>
                    ` : ''}
                </form>
            `;
            
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Material Issue</button>
            `;
            
            // Setup product change handler for rental products
            setTimeout(() => {
                // Check if any product has been changed
                const productSelects = document.querySelectorAll('select[name^="rental_products"]');
                productSelects.forEach(select => {
                    select.addEventListener('change', function() {
                        checkProductChanges();
                    });
                });
            }, 100);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            `;
        });
}

// MOM9: Handle product change - show/hide note field
function handleProductChange(selectElement, originalProductId) {
    const selectedProductId = selectElement.value;
    const noteGroup = document.getElementById('product_change_note_group');
    const noteField = document.getElementById('product_change_note');
    
    // Show note field if product is changed (different from original)
    if (selectedProductId && originalProductId && selectedProductId != originalProductId) {
        if (noteGroup) noteGroup.style.display = 'block';
        if (noteField) noteField.setAttribute('required', 'required');
    } else {
        if (noteGroup) noteGroup.style.display = 'none';
        if (noteField) {
            noteField.removeAttribute('required');
            noteField.value = '';
        }
    }
}

// Check if any rental product has been changed
function checkProductChanges() {
    const productSelects = document.querySelectorAll('select[name^="rental_products"][name$="[product_id]"]');
    let hasChanges = false;
    
    productSelects.forEach(select => {
        const originalProductId = select.getAttribute('data-original-product-id');
        const selectedProductId = select.value;
        if (originalProductId && selectedProductId && originalProductId != selectedProductId) {
            hasChanges = true;
        }
    });
    
    const noteGroup = document.getElementById('product_change_note_group');
    const noteField = document.getElementById('product_change_note');
    
    if (hasChanges) {
        if (noteGroup) noteGroup.style.display = 'block';
        if (noteField) noteField.setAttribute('required', 'required');
    } else {
        if (noteGroup) noteGroup.style.display = 'none';
        if (noteField) {
            noteField.removeAttribute('required');
            noteField.value = '';
        }
    }
}

// MOM12: Handle component change - filter products dropdown
function handleComponentChange(selectElement, rowIndex) {
    const newProductTypeId = selectElement.value;
    const productSelect = document.getElementById('product_select_' + rowIndex);
    
    if (!productSelect || !window.availableProducts) {
        console.error('Product select or availableProducts not found');
        return;
    }
    
    const allowedProductIds = (productSelect.dataset.allowedProductIds || '')
        .split(',')
        .map(id => id.trim())
        .filter(Boolean);
    const productCategoryId = productSelect.dataset.productCategoryId || '';
    const originalProduct = window.availableProducts.find(p => String(p.id) === String(productSelect.dataset.originalProductId));

    let filteredProducts = window.availableProducts;
    if (allowedProductIds.length > 0) {
        filteredProducts = window.availableProducts.filter(p => allowedProductIds.includes(String(p.id)));
    } else if (newProductTypeId) {
        filteredProducts = window.availableProducts.filter(p => p.product_type_id == newProductTypeId || (p.productType && p.productType.id == newProductTypeId));
    } else if (productCategoryId) {
        filteredProducts = window.availableProducts.filter(p => p.product_category_id == productCategoryId || (p.productCategory && p.productCategory.id == productCategoryId));
    }

    filteredProducts = filterSamePackageMaterialFamily(originalProduct, filteredProducts);
    
    // Rebuild options
    let options = '<option value="">-- Select Product --</option>';
    filteredProducts.forEach(p => {
        options += '<option value="' + p.id + '">' + formatMaterialOptionLabel(p) + '</option>';
    });
    
    productSelect.innerHTML = options;
    productSelect.dataset.productTypeId = newProductTypeId;
    
    // Show notification about filter change
    
    // Check for product changes
    checkProductChanges();
}

// Handle rental product change
function handleRentalProductChange(selectElement, index) {
    const selectedProduct = window.availableProducts?.find(p => String(p.id) === String(selectElement.value));
    const packagingSelect = document.getElementById('packaging_select_' + index);

    if (selectedProduct && packagingSelect) {
        const packagingSizeId = getProductPackagingSizeId(selectedProduct);
        if (packagingSizeId) {
            packagingSelect.value = packagingSizeId;
            packagingSelect.setAttribute('data-current-packaging-size-id', packagingSizeId);
        }
    }

    checkProductChanges();
}

function handlePackagingSizeChange(selectElement, index) {
    const productSelect = document.getElementById('product_select_' + index);
    if (!productSelect || !window.availableProducts) {
        return;
    }

    const selectedPackagingSizeId = selectElement.value;
    const previousPackagingSizeId = selectElement.getAttribute('data-current-packaging-size-id') || '';
    const currentProduct = window.availableProducts.find(p => String(p.id) === String(productSelect.value)) ||
        window.availableProducts.find(p => String(p.id) === String(productSelect.dataset.originalProductId));

    const allowedProductIds = (productSelect.dataset.allowedProductIds || '')
        .split(',')
        .map(id => id.trim())
        .filter(Boolean);
    const productTypeId = productSelect.dataset.productTypeId || '';
    const productCategoryId = productSelect.dataset.productCategoryId || '';
    const isUnit = productSelect.dataset.isUnit === '1';

    let candidates = window.availableProducts;
    if (isUnit && productCategoryId) {
        candidates = candidates.filter(p => p.product_category_id == productCategoryId || (p.productCategory && p.productCategory.id == productCategoryId));
    } else if (allowedProductIds.length > 0) {
        candidates = candidates.filter(p => allowedProductIds.includes(String(p.id)));
    } else if (productTypeId) {
        candidates = candidates.filter(p => p.product_type_id == productTypeId || (p.productType && p.productType.id == productTypeId));
    } else if (productCategoryId) {
        candidates = candidates.filter(p => p.product_category_id == productCategoryId || (p.productCategory && p.productCategory.id == productCategoryId));
    }

    if (!isUnit) {
        candidates = filterSamePackageMaterialFamily(currentProduct, candidates);
    }

    const replacement = findProductForPackagingSize(currentProduct, candidates, selectedPackagingSizeId);
    if (!replacement) {
        selectElement.value = previousPackagingSizeId;
        Swal.fire({
            icon: 'warning',
            title: 'Produk Tidak Ditemukan',
            text: 'Tidak ada material dengan ukuran tersebut untuk aroma/unit yang sama.',
            confirmButtonColor: '#214589'
        });
        return;
    }

    if (!Array.from(productSelect.options).some(option => String(option.value) === String(replacement.id))) {
        productSelect.add(new Option(formatMaterialOptionLabel(replacement), replacement.id));
    }

    productSelect.value = replacement.id;
    selectElement.setAttribute('data-current-packaging-size-id', selectedPackagingSizeId);
    handleRentalProductChange(productSelect, index);
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // MOM12: Properly parse nested form data (rental_products[0][product_id] -> rental_products: [{product_id: ...}])
    const data = {};
    const rentalProducts = {};
    
    for (let [key, value] of formData.entries()) {
        // Check if this is a rental_products field
        const match = key.match(/^rental_products\[(\d+)\]\[(\w+)\]$/);
        if (match) {
            const index = match[1];
            const field = match[2];
            if (!rentalProducts[index]) {
                rentalProducts[index] = {};
            }
            rentalProducts[index][field] = value;
        } else {
            data[key] = value;
        }
    }
    
    // Convert rentalProducts object to array
    if (Object.keys(rentalProducts).length > 0) {
        data.rental_products = Object.values(rentalProducts);
    }
    
    // MOM9: Validate product_change_note if any product is changed
    const productChangeNoteGroup = document.getElementById('product_change_note_group');
    const productChangeNote = document.getElementById('product_change_note');
    
    if (productChangeNoteGroup && productChangeNoteGroup.style.display !== 'none') {
        if (!productChangeNote.value.trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Alasan Perubahan Produk Diperlukan',
                text: 'Silakan isi alasan perubahan produk sebelum menyimpan.',
                confirmButtonColor: '#214589'
            });
            productChangeNote.focus();
            return;
        }
    }
    
    const url = id ? `/operational/job-assign-material-issues/${id}` : '/operational/job-assign-material-issues';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    
    // MOM12 DEBUG: Log actual select values before submit
    document.querySelectorAll('select[name^="rental_products"]').forEach(sel => {
    });
    
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
        
        if (!response.ok) {
            return response.text().then(text => {
                try {
                    const err = JSON.parse(text);
                    throw new Error(JSON.stringify(err));
                } catch (e) {
                    throw new Error('Server returned HTML: ' + text.substring(0, 200));
                }
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: result.message || 'Material issue berhasil diupdate.',
                confirmButtonColor: '#214589'
            }).then(() => {
                closeModal();
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: result.message || 'Terjadi kesalahan.',
                confirmButtonColor: '#214589'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        try {
            const errorData = JSON.parse(error.message);
            if (errorData.errors) {
                let errorList = '<ul class="text-left list-disc list-inside">';
                for (const field in errorData.errors) {
                    errorList += `<li>${errorData.errors[field].join(', ')}</li>`;
                }
                errorList += '</ul>';
                
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: errorList,
                    confirmButtonColor: '#214589'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorData.message || 'Terjadi kesalahan.',
                    confirmButtonColor: '#214589'
                });
            }
        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Terjadi kesalahan yang tidak terduga.',
                confirmButtonColor: '#214589'
            });
        }
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this material issue? This action can be undone later.'
        : `Are you sure you want to hide ${count} material issues? This action can be undone later.`;
    
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
    
    fetch('/operational/job-assign-material-issues/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            let message = result.message || `${result.count} material issue(s) berhasil dihapus.`;
            if (result.issued_count > 0) {
                message += `\n\n${result.issued_count} material issue(s) tidak bisa dihapus karena sudah ter-issue. Silakan unissue terlebih dahulu.`;
            }
            if (result.errors && result.errors.length > 0) {
                message += '\n\nDetail:\n' + result.errors.slice(0, 5).join('\n');
                if (result.errors.length > 5) {
                    message += `\n... dan ${result.errors.length - 5} error lainnya.`;
                }
            }
            showSuccessModal(result.count, message);
        } else {
            // Show detailed error message
            let errorMessage = result.message || 'Gagal menghapus material issues.';
            if (result.errors && result.errors.length > 0) {
                errorMessage += '\n\nDetail:\n' + result.errors.slice(0, 5).join('\n');
                if (result.errors.length > 5) {
                    errorMessage += `\n... dan ${result.errors.length - 5} error lainnya.`;
                }
            }
            showErrorModal(errorMessage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Network error occurred');
    });
}

// Success Modal functions
function showSuccessModal(count, customMessage = null) {
    const message = customMessage || (count === 1 
        ? 'Material issue berhasil dihapus.'
        : `${count} material issues berhasil dihapus.`);
    
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
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t hide the material issue. Please try again.';
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

// Apply filters function
function applyFilters() {
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    const buildingName = document.getElementById('filterBuildingName').value;
    const issueDate = document.getElementById('filterIssuedDate').value;
    const perPage = document.getElementById('perPage')?.value;
    
    const params = new URLSearchParams(window.location.search);
    dateFrom ? params.set('date_from', dateFrom) : params.delete('date_from');
    dateTo ? params.set('date_to', dateTo) : params.delete('date_to');
    buildingName ? params.set('building_name', buildingName) : params.delete('building_name');
    issueDate ? params.set('issue_date', issueDate) : params.delete('issue_date');
    perPage ? params.set('per_page', perPage) : params.delete('per_page');
    params.set('page', '1');
    
    window.location.href = '{{ route("operational.job-assign-material-issues.index") }}?' + params.toString();
}

// Toggle select all function
function toggleSelectAll() {
    const headerSelectAll = document.getElementById('headerSelectAll');
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    
    // Get checked state from either checkbox (whichever triggered the event)
    const isChecked = (headerSelectAll && headerSelectAll.checked) || (selectAll && selectAll.checked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = isChecked;
    });
    
    // Sync both select all checkboxes
    if (headerSelectAll) {
        headerSelectAll.checked = isChecked;
        headerSelectAll.indeterminate = false;
    }
    if (selectAll) {
        selectAll.checked = isChecked;
        selectAll.indeterminate = false;
    }
    
    updateSubmitIssueButton();
}

/**
 * Handle checkbox change - select individual item only
 */
function handleCheckboxChange(checkbox) {
    // Update submit button state and select all checkbox state
    updateSubmitIssueButton();
    updateSelectAllCheckbox();
}

function updateSelectAllCheckbox() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const selectAllCheckbox = document.getElementById('selectAll');
    const headerSelectAllCheckbox = document.getElementById('headerSelectAll');
    
    if (checkboxes.length === 0) return;
    
    const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
    const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = anyChecked && !allChecked;
    }
    if (headerSelectAllCheckbox) {
        headerSelectAllCheckbox.checked = allChecked;
        headerSelectAllCheckbox.indeterminate = anyChecked && !allChecked;
    }
}

// Update submit issue button state
function updateSubmitIssueButton() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const btnSubmitIssue = document.getElementById('btnSubmitIssue');
    const btnUnissue = document.getElementById('btnUnissue');
    
    if (btnSubmitIssue) {
        btnSubmitIssue.disabled = checkboxes.length === 0;
    }
    
    // Enable unissue button only if there are checked items with status "issued"
    if (btnUnissue) {
        const checkedIssued = Array.from(checkboxes).filter(cb => {
            const row = cb.closest('tr');
            if (!row) return false;
            const statusBadge = row.querySelector('.status-badge.status-issued');
            return statusBadge !== null;
        });
        btnUnissue.disabled = checkedIssued.length === 0;
    }
}

function getSelectedSubmitIssueRows() {
    return Array.from(document.querySelectorAll('.row-checkbox:checked'))
        .map(cb => cb.closest('tr'))
        .filter(Boolean);
}

function getSelectedMaterialIssueIds(rows) {
    const ids = rows
        .map(row => row.querySelector('.row-checkbox')?.value)
        .filter(Boolean);

    return [...new Set(ids)];
}

function getSelectedSubmitUnitTotal(rows) {
    return rows.reduce((total, row) => {
        const qtyInput = row.querySelector('.qty-issue-input');
        const quantity = parseFloat(qtyInput?.value || 0);

        return total + (Number.isFinite(quantity) ? quantity : 0);
    }, 0);
}

function formatSubmitUnitTotal(total) {
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 2
    }).format(total);
}

// Submit issue function
function submitIssue(forceContinue = false) {
    const selectedRows = getSelectedSubmitIssueRows();
    if (selectedRows.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian!',
            text: 'Silakan pilih minimal satu material issue untuk di-submit.',
            confirmButtonColor: '#214589'
        });
        return;
    }
    
    const materialIssueIds = getSelectedMaterialIssueIds(selectedRows);
    const totalSubmitUnits = getSelectedSubmitUnitTotal(selectedRows);
    
    if (!forceContinue) {
        Swal.fire({
            icon: 'question',
            title: 'Submit Material Issue',
            html: `Apakah Anda yakin ingin submit total <strong>${formatSubmitUnitTotal(totalSubmitUnits)}</strong> unit dari <strong>${materialIssueIds.length}</strong> material issue?<br><br><small>Material akan di-issue dan stok warehouse akan diperbarui.</small>`,
            showCancelButton: true,
            confirmButtonText: 'Ya, Submit!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#214589',
            cancelButtonColor: '#6b7280'
        }).then((result) => {
            if (result.isConfirmed) {
                executeSubmitIssue(materialIssueIds, forceContinue);
            }
        });
        return;
    }
    
    executeSubmitIssue(materialIssueIds, forceContinue);
}

function executeSubmitIssue(materialIssueIds, forceContinue) {
    // BOM Rental Qty Validation
    if (!forceContinue) {
        let bomValidationErrors = [];
        let groupedVolumes = {}; // Group volumes by JobNumber + RoomName + RentalDetailID
        
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        
        checkboxes.forEach(cb => {
            const row = cb.closest('tr');
            if (!row) return;
            
            const jobNumber = row.getAttribute('data-job-number') || '-';
            const jobType = (row.getAttribute('data-job-type') || '').toLowerCase();
            const roomName = row.querySelector('td:nth-child(7)')?.textContent?.trim() || '-';
            const rentalDetailId = row.getAttribute('data-rental-detail-id') || 'no-id';
            const targetBomQty = parseFloat(row.querySelector('.target-bom-qty')?.value || 0);
            
            const qtyIssueInput = row.querySelector('.qty-issue-input');
            const qtyIssueKemasan = parseFloat(qtyIssueInput?.value || 0);
            
            // The row attribute is the source of truth because it is rendered and
            // updated with the package-aware BOM per unit shown in the table.
            let productBomQty = parseFloat(row.getAttribute('data-product-bom-qty') || 0);
            const materialSelect = row.querySelector('.material-select');
            const productId = materialSelect ? materialSelect.value : null;
            
            if ((!productBomQty || productBomQty <= 0) && productId && window.products) {
                const product = window.products.find(p => p.id == productId);
                if (product) productBomQty = getProductBomPerUnit(product);
            }
            
            const lineVolume = qtyIssueKemasan * productBomQty;
            const productType = row.getAttribute('data-product-type') ||
                row.querySelector('td:nth-child(13)')?.textContent?.trim() ||
                '-';
            const materialName = row.getAttribute('data-material-name') ||
                row.querySelector('.material-select option:checked')?.textContent?.trim() ||
                row.querySelector('td:nth-child(9)')?.textContent?.trim() ||
                '-';
            const componentKey = [
                rentalDetailId,
                targetBomQty,
                productType.toLowerCase()
            ].join('|');
            const groupKey = `${jobNumber}_${roomName}_${componentKey}`;
            
            if (!groupedVolumes[groupKey]) {
                groupedVolumes[groupKey] = {
                    jobNumber: jobNumber,
                    jobType: jobType,
                    roomName: roomName,
                    productType: productType,
                    materialName: materialName,
                    targetBomQty: targetBomQty,
                    totalVolume: 0
                };
            }

            groupedVolumes[groupKey].totalVolume += lineVolume;
        });

        // Job types allowed to issue under the BOM Rental Qty target (IF / Extra / Complaint
        // don't need to fully cover the rental BOM the way an Install/Service job does).
        const bomUnderIssueExemptTypes = ['install_free', 'extra', 'complain'];

        // Finalize validation after grouping
        Object.values(groupedVolumes).forEach(group => {
            const { jobNumber, jobType, roomName, productType, targetBomQty, totalVolume } = group;
            const componentLabel = productType && productType !== '-' ? ` - ${productType}` : '';
            const diff = totalVolume - targetBomQty;

            if (Math.abs(diff) <= 0.01) return;

            // Under-target is allowed for IF/Extra/Complaint jobs; over-target is still blocked.
            if (diff < 0 && bomUnderIssueExemptTypes.includes(jobType)) return;

            bomValidationErrors.push(`${jobNumber} (${roomName}${componentLabel}): Total volume (${totalVolume}) tidak sesuai BOM Rental Qty (${targetBomQty})`);
        });
        
        if (bomValidationErrors.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Qty tidak sesuai BOM Rental Qty',
                html: '<ul class="text-left list-disc list-inside">' + bomValidationErrors.map(e => `<li>${e}</li>`).join('') + '</ul>',
                confirmButtonColor: '#214589'
            });
            return;
        }
    }
    // Show loading state
    const btnSubmitIssue = document.getElementById('btnSubmitIssue');
    const originalText = btnSubmitIssue.innerHTML;
    btnSubmitIssue.disabled = true;
    btnSubmitIssue.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    
    fetch('{{ route("operational.job-assign-material-issues.submit-issue") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            material_issue_ids: materialIssueIds,
            force_continue: forceContinue
        })
    })
    .then(response => response.json())
    .then(data => {
        btnSubmitIssue.innerHTML = originalText;
        btnSubmitIssue.disabled = false;
        
        if (data.status === 'success') {
            let message = data.message || 'Material issue berhasil di-submit!';
            let warningsHtml = '';
            
            // Show warnings if any
            if (data.data && data.data.warnings && data.data.warnings.length > 0) {
                warningsHtml = '<div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded"><strong>Warnings:</strong><ul class="mt-2 list-disc list-inside text-sm">' + 
                    data.data.warnings.map(w => `<li>${w}</li>`).join('') + 
                    '</ul></div>';
            }
            
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                html: message + warningsHtml,
                confirmButtonColor: '#214589'
            }).then(() => {
                location.reload();
            });
        } else if (data.status === 'error') {
            let errorMessage = data.message || 'Gagal submit material issue.';
            let errorsHtml = '';
            let warningsHtml = '';
            const responseErrors = data.errors || data.data?.errors || [];
            const responseWarnings = data.warnings || data.data?.warnings || [];
            
            // Show errors if any
            if (responseErrors.length > 0) {
                // Convert \n to <br> for better HTML display
                errorsHtml = '<div class="mt-3 p-3 bg-red-50 border border-red-200 rounded"><strong>Detail Error:</strong><ul class="mt-2 list-disc list-inside text-sm">' + 
                    responseErrors.map(e => {
                        // Convert newlines to <br> and preserve formatting
                        let formattedError = e.replace(/\n/g, '<br>');
                        return `<li style="white-space: pre-wrap;">${formattedError}</li>`;
                    }).join('') + 
                    '</ul></div>';
            }
            
            // Show warnings if any
            if (responseWarnings.length > 0) {
                warningsHtml = '<div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded"><strong>Warnings:</strong><ul class="mt-2 list-disc list-inside text-sm">' + 
                    responseWarnings.map(w => `<li>${w}</li>`).join('') +
                    '</ul></div>';
            }
            
            // If requires confirmation, ask user
            if (data.requires_confirmation) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Konfirmasi Diperlukan',
                    html: errorMessage + errorsHtml + warningsHtml,
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Lanjutkan!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#214589',
                    cancelButtonColor: '#6b7280'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitIssue(true); // Retry with force_continue = true
                    }
                });
            } else {
                // Check if it's stock-related error for better title
                let title = 'Gagal!';
                let icon = 'error';
                if (responseErrors.some(e => e.includes('Stock tidak cukup') || e.includes('stock'))) {
                    title = 'Stock Tidak Mencukupi!';
                    icon = 'warning';
                }
                
                Swal.fire({
                    icon: icon,
                    title: title,
                    html: errorMessage + errorsHtml + warningsHtml,
                    confirmButtonColor: '#214589',
                    width: '800px'
                });
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: data.message || 'Gagal submit material issue.',
                confirmButtonColor: '#214589'
            });
        }
    })
    .catch(error => {
        btnSubmitIssue.innerHTML = originalText;
        btnSubmitIssue.disabled = false;
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Terjadi kesalahan saat submit material issue.',
            confirmButtonColor: '#214589'
        });
    });
}

// Approve material issue function
function approveMaterialIssue(id) {
    Swal.fire({
        icon: 'question',
        title: 'Approve Material Issue',
        html: 'Apakah Anda yakin ingin meng-approve material issue ini?<br><br><small>Setelah di-approve, material issue dapat di-submit untuk di-issue.</small>',
        showCancelButton: true,
        confirmButtonText: 'Ya, Approve!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Memproses...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`/operational/job-assign-material-issues/${id}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message || 'Material issue berhasil di-approve.',
                        confirmButtonColor: '#16a34a'
                    }).then(() => {
                        closeModal();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message || 'Gagal meng-approve material issue.',
                        confirmButtonColor: '#214589'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat meng-approve material issue.',
                    confirmButtonColor: '#214589'
                });
            });
        }
    });
}

// Select All functionality - using event listeners to avoid conflicts
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const headerSelectAllCheckbox = document.getElementById('headerSelectAll');
    
    if (selectAllCheckbox) {
        // Remove existing event listener and add new one
        selectAllCheckbox.removeEventListener('change', toggleSelectAll);
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const isChecked = this.checked;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            if (headerSelectAllCheckbox) {
                headerSelectAllCheckbox.checked = isChecked;
                headerSelectAllCheckbox.indeterminate = false;
            }
            
            updateSubmitIssueButton();
        });
    }
    
    if (headerSelectAllCheckbox) {
        // Remove existing event listener and add new one
        headerSelectAllCheckbox.removeEventListener('change', toggleSelectAll);
        headerSelectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const isChecked = this.checked;
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = isChecked;
                selectAllCheckbox.indeterminate = false;
            }
            
            updateSubmitIssueButton();
        });
    }
});

// Unissue selected function
async function unissueSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Silakan pilih minimal satu material issue yang sudah ter-issue untuk di-unissue.');
        return;
    }
    
    // Filter only issued items
    const issuedIds = Array.from(checkboxes).filter(cb => {
        const row = cb.closest('tr');
        if (!row) return false;
        const statusBadge = row.querySelector('.status-badge.status-issued');
        return statusBadge !== null;
    }).map(cb => cb.value);
    
    if (issuedIds.length === 0) {
        showWarningDialog('Tidak ada material issue yang ter-issue. Hanya material issue dengan status "ISSUED" yang bisa di-unissue.');
        return;
    }
    
    if (issuedIds.length < checkboxes.length) {
        const partialConfirm = await showConfirmDialog({
            title: 'Lanjutkan Unissue?',
            text: `${issuedIds.length} dari ${checkboxes.length} material issue yang dipilih bisa di-unissue. Lanjutkan?`,
            icon: 'warning',
            confirmButtonText: 'Ya, lanjutkan',
            cancelButtonText: 'Batal'
        });
        if (!partialConfirm.isConfirmed) {
            return;
        }
    }
    
    const confirmResult = await showConfirmDialog({
        title: 'Unissue Material Issue?',
        text: `Apakah Anda yakin ingin unissue ${issuedIds.length} material issue? Status akan berubah dari "ISSUED" menjadi "UNISSUED".`,
        icon: 'warning',
        confirmButtonText: 'Ya, unissue',
        cancelButtonText: 'Batal'
    });
    if (!confirmResult.isConfirmed) {
        return;
    }
    
    // Show loading state
    const btnUnissue = document.getElementById('btnUnissue');
    const originalText = btnUnissue.innerHTML;
    btnUnissue.disabled = true;
    btnUnissue.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    
    fetch('{{ route("operational.job-assign-material-issues.bulk-unissue") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: issuedIds })
    })
    .then(response => response.json())
    .then(result => {
        btnUnissue.innerHTML = originalText;
        btnUnissue.disabled = false;
        
        if (result.success) {
            let message = result.message || `${result.count} material issue(s) berhasil di-unissue.`;
            if (result.errors && result.errors.length > 0) {
                message += '\n\nDetail:\n' + result.errors.slice(0, 5).join('\n');
                if (result.errors.length > 5) {
                    message += `\n... dan ${result.errors.length - 5} error lainnya.`;
                }
            }
            showSuccessDialog(message).then(() => location.reload());
        } else {
            let errorMessage = result.message || 'Gagal unissue material issues.';
            if (result.errors && result.errors.length > 0) {
                errorMessage += '\n\nDetail:\n' + result.errors.slice(0, 5).join('\n');
                if (result.errors.length > 5) {
                    errorMessage += `\n... dan ${result.errors.length - 5} error lainnya.`;
                }
            }
            showErrorDialog(errorMessage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btnUnissue.innerHTML = originalText;
        btnUnissue.disabled = false;
        showErrorDialog('Gagal unissue material issues: ' + (error.message || 'Terjadi kesalahan'));
    });
}

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu material issue yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// MOM10: Add click event listener to table rows (using event delegation for pagination)
document.addEventListener('DOMContentLoaded', function() {
    // Use event delegation on tbody to handle dynamically loaded rows
    const tbody = document.querySelector('.responsive-table tbody');
    if (tbody) {
        tbody.addEventListener('click', function(e) {
            // Find the closest row with data-id
            const row = e.target.closest('tr[data-id]');
            if (!row) return;
            
            // Don't trigger if clicking on checkbox, button, or any inline-editable
            // form control. Bug #18 follow-up (QA, 2026-06-27): the Material
            // dropdown's `<select>` carries onclick="event.stopPropagation()", but
            // select2 hides that real <select> and renders a sibling
            // .select2-selection span in its place - clicks on the visible span
            // never reach the <select>, so stopPropagation() never fired and the
            // click fell through to this row handler, popping the "View Material
            // Issue" modal open on top of (and blocking) the dropdown the user was
            // trying to use.
            if (e.target.closest('.row-checkbox')
                || e.target.closest('button')
                || e.target.closest('a')
                || e.target.closest('select')
                || e.target.closest('input')
                || e.target.closest('.select2-container')
                || e.target.closest('.select2-dropdown')) {
                return;
            }
            
            const id = row.getAttribute('data-id');
            if (id) {
                showMaterialIssue(id);
            }
        });
    }
});

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

// Auto-fill unit price when product is selected
function setupProductPriceAutofill() {
    const productSelect = document.getElementById('product_id');
    const unitPriceInput = document.getElementById('unit_price');
    
    if (productSelect && unitPriceInput) {
        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price') || 0;
            unitPriceInput.value = price;
        });
    }
}

// Store all products for reset functionality
const allProducts = @json($products ?? []);
let currentProducts = allProducts; // Track current products in dropdown

// Auto-fill team and products when job assign schedule is selected
function setupJobAssignScheduleAutofill() {
    const jobAssignScheduleSelect = document.querySelector('select[name="job_assign_schedule_id"]');
    const teamSelect = document.querySelector('select[name="team_id"]');
    const productSelect = document.getElementById('product_id');
    
    if (jobAssignScheduleSelect && teamSelect && productSelect) {
        jobAssignScheduleSelect.addEventListener('change', function() {
            const selectedId = this.value;
            if (selectedId) {
                // Fetch job assign schedule details
                fetch(`/operational/job-assign-material-issues/job-assign-schedule/${selectedId}/details`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Auto-fill team
                            if (data.data.team_id) {
                                teamSelect.value = data.data.team_id;
                            }
                            
                            // Auto-populate products from rental
                            if (data.data.products && data.data.products.length > 0) {
                                // Clear existing options except the first one (placeholder)
                                productSelect.innerHTML = '<option value="">Select Product</option>';
                                
                                // Update current products
                                currentProducts = data.data.products;
                                
                                // Add products from rental
                                data.data.products.forEach(product => {
                                    const option = document.createElement('option');
                                    option.value = product.id;
                                    option.setAttribute('data-price', product.last_unit_price || 0);
                                    option.textContent = `${product.name}${product.code ? ' (' + product.code + ')' : ''} (Price: ${new Intl.NumberFormat('id-ID').format(product.last_unit_price || 0)})`;
                                    productSelect.appendChild(option);
                                });
                                
                                // Show hint
                                const productHint = document.getElementById('productHint');
                                if (productHint) {
                                    productHint.textContent = `Products from rental: ${data.data.rental_name || 'N/A'}`;
                                    productHint.style.display = 'block';
                                }
                                
                                // Show rental name in console for debugging
                                if (data.data.rental_name) {
                                }
                            } else {
                                // If no products from rental, keep all products
                                resetProductsToAll();
                                const productHint = document.getElementById('productHint');
                                if (productHint) {
                                    productHint.style.display = 'none';
                                }
                            }
                            
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching job assign schedule details:', error);
                    });
            } else {
                // Reset selections
                teamSelect.value = '';
                resetProductsToAll();
            }
        });
    }
}

// Reset products dropdown to show all products
function resetProductsToAll() {
    const productSelect = document.getElementById('product_id');
    if (productSelect) {
        productSelect.innerHTML = '<option value="">Select Product (will be filtered by rental)</option>';
        currentProducts = allProducts;
        
        allProducts.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.setAttribute('data-price', product.last_unit_price || 0);
            option.textContent = `${product.name} (Price: ${new Intl.NumberFormat('id-ID').format(product.last_unit_price || 0)})`;
            productSelect.appendChild(option);
        });
        
        // Hide hint
        const productHint = document.getElementById('productHint');
        if (productHint) {
            productHint.style.display = 'none';
        }
    }
}

// ========================================
// VARIANT CHANGE FUNCTIONS
// ========================================

let currentMaterialIssueId = null;
let availableVariants = [];
let currentVariantForRoom = '';

// Open variant change modal
function openVariantChangeModal(materialIssueId, roomName, currentVariant) {
    currentMaterialIssueId = materialIssueId;
    currentVariantForRoom = currentVariant || '';
    
    document.getElementById('vcf_material_issue_id').value = materialIssueId;
    document.getElementById('vcf_room_name').value = roomName;
    document.getElementById('vcf_room_display').textContent = roomName;
    document.getElementById('vcf_current_variant').textContent = currentVariant || 'Not Set';
    document.getElementById('vcf_change_reason').value = '';
    
    // Hide notices initially
    document.getElementById('vcf_same_variant_notice').style.display = 'none';
    document.getElementById('vcf_different_variant_notice').style.display = 'none';
    
    // Load variants from API
    loadAvailableVariants();
    
    document.getElementById('variantChangeModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeVariantChangeModal() {
    document.getElementById('variantChangeModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Load available variants from API
function loadAvailableVariants() {
    const select = document.getElementById('vcf_new_variant');
    select.innerHTML = '<option value="">Loading...</option>';
    
    fetch('/operational/job-assign-material-issues/brand-lines-variants', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        
        if (data.status === 'success') {
            availableVariants = data.data.variants || [];
            
            let options = '<option value="">-- Select Variant --</option>';
            
            // Show variants directly (simpler approach)
            if (availableVariants.length > 0) {
                availableVariants.forEach(v => {
                    options += `<option value="${v.value}">${v.value}</option>`;
                });
            } else {
                options = '<option value="">No variants available</option>';
            }
            
            select.innerHTML = options;
        } else {
            select.innerHTML = '<option value="">Failed to load variants</option>';
        }
    })
    .catch(error => {
        console.error('Error loading variants:', error);
        select.innerHTML = '<option value="">Error loading variants</option>';
    });
}

// Handle variant selection change
document.addEventListener('change', function(e) {
    if (e.target.id === 'vcf_new_variant') {
        const newVariant = e.target.value;
        const sameNotice = document.getElementById('vcf_same_variant_notice');
        const diffNotice = document.getElementById('vcf_different_variant_notice');
        
        if (newVariant && currentVariantForRoom) {
            if (newVariant.toLowerCase() === currentVariantForRoom.toLowerCase()) {
                sameNotice.style.display = 'block';
                diffNotice.style.display = 'none';
            } else {
                sameNotice.style.display = 'none';
                diffNotice.style.display = 'block';
            }
        } else {
            sameNotice.style.display = 'none';
            diffNotice.style.display = 'none';
        }
    }
});

// Submit variant change request
function submitVariantChangeRequest() {
    const materialIssueId = document.getElementById('vcf_material_issue_id').value;
    const roomName = document.getElementById('vcf_room_name').value;
    const newVariant = document.getElementById('vcf_new_variant').value;
    const changeReason = document.getElementById('vcf_change_reason').value;
    
    if (!newVariant) {
        Swal.fire({
            icon: 'warning',
            title: 'Variant Required',
            text: 'Please select a new variant.',
            confirmButtonColor: '#7c3aed'
        });
        return;
    }
    
    // Show loading
    Swal.fire({
        title: 'Submitting...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(`/operational/job-assign-material-issues/${materialIssueId}/request-variant-change`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            room_name: roomName,
            current_variant: currentVariantForRoom,
            new_variant: newVariant,
            change_reason: changeReason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeVariantChangeModal();
            
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                confirmButtonColor: '#7c3aed'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to submit request',
                confirmButtonColor: '#7c3aed'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An unexpected error occurred',
            confirmButtonColor: '#7c3aed'
        });
    });
}

// Open pending variant changes modal
function openPendingVariantChangesModal(materialIssueId) {
    document.getElementById('pendingVariantChangesModalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #f59e0b; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    document.getElementById('pendingVariantChangesModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    fetch(`/operational/job-assign-material-issues/${materialIssueId}/pending-variant-changes`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const requests = data.data.all_requests || [];
            
            if (requests.length === 0) {
                document.getElementById('pendingVariantChangesModalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;"><i class="fas fa-check-circle" style="font-size: 48px; color: #10b981; margin-bottom: 16px;"></i><p>No variant change requests.</p></div>';
                return;
            }
            
            let html = '<div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse;">';
            html += '<thead><tr style="background: #f9fafb;">';
            html += '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;">Room</th>';
            html += '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;">Current</th>';
            html += '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;">New</th>';
            html += '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;">Requested By</th>';
            html += '<th style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;">Status</th>';
            html += '<th style="padding: 12px; text-align: center; border-bottom: 2px solid #e5e7eb;">Actions</th>';
            html += '</tr></thead><tbody>';
            
            requests.forEach(req => {
                const statusColor = req.status === 'pending_approval' ? '#f59e0b' : 
                                  req.status === 'approved' ? '#10b981' : '#ef4444';
                const statusText = req.status === 'pending_approval' ? 'Pending' : 
                                 req.status.charAt(0).toUpperCase() + req.status.slice(1);
                
                html += `<tr style="border-bottom: 1px solid #e5e7eb;">`;
                html += `<td style="padding: 12px;">${req.room_name || '-'}</td>`;
                html += `<td style="padding: 12px;">${req.current_variant || '-'}</td>`;
                html += `<td style="padding: 12px; font-weight: 600; color: #7c3aed;">${req.new_variant || '-'}</td>`;
                html += `<td style="padding: 12px;">${req.requested_by_name || '-'}<br><small style="color: #9ca3af;">${req.requested_at ? new Date(req.requested_at).toLocaleDateString('id-ID') : ''}</small></td>`;
                html += `<td style="padding: 12px;"><span style="padding: 4px 8px; background: ${statusColor}20; color: ${statusColor}; border-radius: 4px; font-weight: 500; font-size: 12px;">${statusText}</span></td>`;
                
                if (req.status === 'pending_approval' && req.needs_approval) {
                    html += `<td style="padding: 12px; text-align: center;">
                        <button onclick="approveVariantChange(${materialIssueId}, '${req.id}', 'approve')" class="btn btn-sm" style="background: #10b981; color: white; margin-right: 4px; padding: 4px 8px; font-size: 12px;"><i class="fas fa-check"></i></button>
                        <button onclick="approveVariantChange(${materialIssueId}, '${req.id}', 'reject')" class="btn btn-sm" style="background: #ef4444; color: white; padding: 4px 8px; font-size: 12px;"><i class="fas fa-times"></i></button>
                    </td>`;
                } else {
                    html += `<td style="padding: 12px; text-align: center; color: #9ca3af;">-</td>`;
                }
                
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            document.getElementById('pendingVariantChangesModalBody').innerHTML = html;
        } else {
            document.getElementById('pendingVariantChangesModalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Failed to load requests.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('pendingVariantChangesModalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading requests.</div>';
    });
}

function closePendingVariantChangesModal() {
    document.getElementById('pendingVariantChangesModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Approve or reject variant change
function approveVariantChange(materialIssueId, requestId, action) {
    const actionText = action === 'approve' ? 'approve' : 'reject';
    
    Swal.fire({
        title: `${action === 'approve' ? 'Approve' : 'Reject'} Request?`,
        text: `Are you sure you want to ${actionText} this variant change request?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: action === 'approve' ? '#10b981' : '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Yes, ${actionText}`
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(`/operational/job-assign-material-issues/${materialIssueId}/approve-variant-change`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    request_id: requestId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#7c3aed'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to process request',
                        confirmButtonColor: '#7c3aed'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An unexpected error occurred',
                    confirmButtonColor: '#214589'
                });
            });
        }
    });
}

// ===== NEW FUNCTIONS FOR MATERIAL ISSUE REDESIGN =====

/**
 * Handle Grouped Checkbox Logic
 * When one checkbox in a group is checked, all checkboxes in the same group should be checked/unchecked
 */
function handleGroupCheckbox(checkbox) {
    const group = checkbox.dataset.group;
    const isChecked = checkbox.checked;
    
    // Find all checkboxes in the same group
    const allGroupCheckboxes = document.querySelectorAll(`.group-checkbox[data-group="${group}"]`);
    
    // Sync all checkboxes in the group
    allGroupCheckboxes.forEach(cb => {
        cb.checked = isChecked;
    });
    
    // Update button states
    handleCheckboxChange(checkbox);
}

/**
 * Copy Material Item for Package Conversion
 * Creates a duplicate row with same product but allows user to change quantity for different package size
 */
function copyMaterial(itemId) {
    fetch('{{ url('/operational/job-assign-material-issues/copy-material') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ item_id: itemId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Material Copied!',
                text: data.message || 'Material copied and automated successfully.',
                confirmButtonColor: '#214589',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload(); // Reload to show new row
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to copy material',
                confirmButtonColor: '#214589'
            });
        }
    })
    .catch(error => {
        console.error('Error copying material:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An unexpected error occurred',
            confirmButtonColor: '#214589'
        });
    });
}

/**
 * Delete Copied Material Item
 * Only copied items (is_copied = true) can be deleted via this function
 */
function deleteCopiedMaterial(itemId) {
    Swal.fire({
        title: 'Delete Copied Material?',
        text: 'This will remove the copied material row. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`{{ url('/operational/job-assign-material-issues/delete-copied-material') }}/${itemId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: data.message || 'Copied material has been deleted.',
                        confirmButtonColor: '#214589'
                    }).then(() => {
                        location.reload(); // Reload to hide deleted row
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to delete copied material',
                        confirmButtonColor: '#214589'
                    });
                }
            })
            .catch(error => {
                console.error('Error deleting material:', error);
                Swal.fire({
                    icon: 'error',
                  title: 'Error',
                    text: 'An unexpected error occurred',
                    confirmButtonColor: '#214589'
                });
            });
        }
    });
}

/**
 * Update Qty Issue with Autosave
 * Updates quantity and recalculates BOM quantity in real-time
 */
function updateQtyIssue(itemId, inputElement) {
    const quantity = parseFloat(inputElement.value) || 0;
    
    // Show loading indicator on input
    inputElement.style.opacity = '0.6';
    inputElement.disabled = true;
    
    fetch(`{{ url('/operational/job-assign-material-issues/update-qty') }}/${itemId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ quantity: quantity })
    })
    .then(response => response.json())
    .then(data => {
        inputElement.style.opacity = '1';
        inputElement.disabled = false;
        
        if (data.status === 'success') {
            // Update BOM Qty display in the same row
            const row = inputElement.closest('tr');
            const bomQtyCell = row.querySelector('.bom-qty-display');
            if (bomQtyCell && data.bom_qty !== undefined) {
                // Update only the span, keep the 'Target' div
                const span = bomQtyCell.querySelector('span');
                if (span) {
                    span.textContent = new Intl.NumberFormat().format(data.bom_qty);
                } else {
                    bomQtyCell.textContent = data.bom_qty.toFixed(0); 
                }
            }

            refreshMaterialRowStatus(row, data);
            
            // Show subtle success feedback
            inputElement.style.backgroundColor = '#d4edda';
            setTimeout(() => {
                inputElement.style.backgroundColor = '';
            }, 1000);

            // Trigger suggestion for the rest of the group
            const rentalDetailId = row.getAttribute('data-rental-detail-id');
            if (rentalDetailId) {
                const groupRows = Array.from(document.querySelectorAll(`tr[data-rental-detail-id="${rentalDetailId}"]`));
                const indexInGroup = groupRows.indexOf(row);
                suggestQtyForGroup(rentalDetailId, indexInGroup + 1);
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to update quantity',
                confirmButtonColor: '#214589'
            });
            // Revert to original value
            inputElement.focus();
        }
    })
    .catch(error => {
        console.error('Error updating qty:', error);
        inputElement.style.opacity = '1';
        inputElement.disabled = false;
        
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An unexpected error occurred while updating quantity',
            confirmButtonColor: '#214589'
        });
    });
}

/**
 * Update Material for Copied Items
 * Changes product and recalculates BOM quantity
 */
function updateMaterial(itemId, selectElement) {
    const productId = selectElement.value;
    if (!productId) return;
    const previousValue = selectElement.getAttribute('data-current-product-id') || selectElement.defaultValue || '';
    if (previousValue && productId === previousValue) return;
    const lastRequestedProductId = selectElement.getAttribute('data-last-requested-product-id') || '';
    if (selectElement.dataset.materialUpdateInFlight === '1' && lastRequestedProductId === productId) {
        return;
    }
    selectElement.dataset.materialUpdateInFlight = '1';
    selectElement.setAttribute('data-last-requested-product-id', productId);
    
    // Show loading
    selectElement.style.opacity = '0.6';
    selectElement.disabled = true;
    
    fetch(`{{ url('/operational/job-assign-material-issues/update-material') }}/${itemId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ product_id: productId })
    })
    .then(response => response.json())
    .then(data => {
        selectElement.style.opacity = '1';
        selectElement.disabled = false;
        
        if (data.status === 'success') {
            const row = selectElement.closest('tr');
            selectElement.setAttribute('data-current-product-id', productId);
            
            // 1. Update metadata in row attributes
            row.setAttribute('data-product-bom-qty', data.bom_per_unit || 0);
            row.setAttribute('data-warehouse-stock', data.stock || 0);
            
            // 2. Update stock display in UI
            const stockDisplay = row.querySelector('.stock-display');
            if (stockDisplay) {
                stockDisplay.textContent = new Intl.NumberFormat().format(data.stock || 0);
                stockDisplay.style.color = (data.stock || 0) > 0 ? '#059669' : '#dc2626';
            }

            // 3. Update Product Type display (Column 13 - Tipe Material)
            const typeCell = row.cells[12]; // 0-indexed column 12
            if (typeCell && data.product_type) {
                typeCell.textContent = data.product_type;
                row.setAttribute('data-product-type', data.product_type);
            }

            row.setAttribute(
                'data-material-name',
                data.product_name || selectElement.options[selectElement.selectedIndex]?.text?.trim() || ''
            );

            const bomQtyCell = row.querySelector('.bom-qty-display');
            const qtyIssueInput = row.querySelector('.qty-issue-input');
            const targetBomQty = row.querySelector('.target-bom-qty')?.value || 0;
            if (bomQtyCell && qtyIssueInput) {
                const qtyIssue = parseFloat(qtyIssueInput.value || 0);
                const bomQty = data.bom_qty !== undefined
                    ? parseFloat(data.bom_qty || 0)
                    : qtyIssue * parseFloat(data.bom_per_unit || 0);
                bomQtyCell.innerHTML = `
                    <span title="BOM Rental Qty: ${new Intl.NumberFormat().format(parseFloat(targetBomQty || 0))}">${new Intl.NumberFormat().format(bomQty)}</span>
                    <div class="text-[10px] text-blue-600 font-bold">Target: ${new Intl.NumberFormat().format(parseFloat(targetBomQty || 0))}</div>
                    <input type="hidden" class="target-bom-qty" value="${parseFloat(targetBomQty || 0)}">
                `;
            }

            refreshMaterialRowStatus(row, data);

            // 4. Trigger auto-suggestion starting from this row
            const rentalDetailId = row.getAttribute('data-rental-detail-id');
            if (rentalDetailId) {
                const groupRows = Array.from(document.querySelectorAll(`tr[data-rental-detail-id="${rentalDetailId}"]`));
                const indexInGroup = groupRows.indexOf(row);
                suggestQtyForGroup(rentalDetailId, indexInGroup + 1);
            }
            
            // Success feedback
            selectElement.style.backgroundColor = '#d4edda';
            setTimeout(() => { selectElement.style.backgroundColor = ''; }, 1000);

            Swal.fire({
                icon: 'success',
                title: 'Material Updated',
                text: 'Calculating suggested quantity...',
                confirmButtonColor: '#214589',
                timer: 1000,
                showConfirmButton: false
            });
        } else {
            if (previousValue) {
                selectElement.value = previousValue;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to update material',
                confirmButtonColor: '#214589'
            });
        }
    })
    .catch(error => {
        console.error('Error updating material:', error);
        selectElement.style.opacity = '1';
        selectElement.disabled = false;
        if (previousValue) {
            selectElement.value = previousValue;
        }
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An unexpected error occurred',
            confirmButtonColor: '#214589'
        });
    })
    .finally(() => {
        selectElement.style.opacity = '1';
        selectElement.disabled = false;
        selectElement.dataset.materialUpdateInFlight = '0';
    });
}

function refreshMaterialRowStatus(row, data = {}) {
    if (!row) return;

    const qtyInput = row.querySelector('.qty-issue-input');
    const qty = parseFloat(qtyInput?.value || 0);
    const stock = parseFloat(row.getAttribute('data-warehouse-stock') || data.stock || 0);
    const serverStatus = (data.material_issue_status || 'pending').toString().toLowerCase();
    let displayStatus = data.display_status || 'Pending';
    let statusClass = data.status_class || 'status-pending';

    if (['pending', 'draft', 'out_of_stock', 'out of stock'].includes(serverStatus)) {
        if (qty > 0 && stock < qty) {
            displayStatus = 'OUT OF STOCK';
            statusClass = 'status-rejected';
        } else {
            displayStatus = 'Pending';
            statusClass = 'status-pending';
        }
    }

    const badge = row.querySelector('.status-badge');
    if (badge) {
        badge.className = `status-badge ${statusClass}`;
        badge.textContent = displayStatus;
    }
}

/**
 * Auto-suggestion Logic
 * Aggressively fills remaining target across rows in a group
 */
function suggestQtyForGroup(rentalDetailId, startFromIndex = 0) {
    if (!rentalDetailId) return;
    
    const rows = Array.from(document.querySelectorAll(`tr[data-rental-detail-id="${rentalDetailId}"]`));
    if (rows.length === 0) return;
    
    const targetBomInput = rows[0].querySelector('.target-bom-qty');
    const targetBom = parseFloat(targetBomInput ? targetBomInput.value : 0);
    let remainingTarget = targetBom;
    
    
    rows.forEach((row, index) => {
        const qtyInput = row.querySelector('.qty-issue-input');
        const bomPerUnit = parseFloat(row.getAttribute('data-product-bom-qty') || 0);

        if (index < startFromIndex) {
            // Volume already committed in previous rows
            const currentQty = parseFloat(qtyInput.value || 0);
            remainingTarget -= (currentQty * bomPerUnit);
        } else {
            // Suggest for this row
            if (bomPerUnit <= 0) {
                if (parseFloat(qtyInput.value) !== 0) {
                    qtyInput.value = 0;
                    updateQtyIssue(qtyInput.getAttribute('data-item-id'), qtyInput);
                }
                return;
            }
            
            // How many units of this product do we need to fulfill (or get closer to) remainingTarget?
            let suggestedQty = 0;
            if (remainingTarget > 0) {
                 suggestedQty = Math.floor(remainingTarget / bomPerUnit);
                 if (suggestedQty < 0) suggestedQty = 0;
            }
            
            const oldQty = parseFloat(qtyInput.value || 0);
            if (oldQty !== suggestedQty) {
                qtyInput.value = suggestedQty;
                
                // Auto-save to server
                updateQtyIssue(qtyInput.getAttribute('data-item-id'), qtyInput);
            }
            
            remainingTarget -= (suggestedQty * bomPerUnit);
        }
    });
    
}

// Initialize event listeners when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initial suggestions for empty Qty Issue rows
    const uniqueGroups = [...new Set(Array.from(document.querySelectorAll('tr[data-rental-detail-id]')).map(tr => tr.getAttribute('data-rental-detail-id')))];
    uniqueGroups.forEach(groupId => {
        if (groupId) {
            // Only suggest if first row of group is 0? 
            // Or let the system decide. User said "otomatis".
            // Let's only suggest if CURRENT values are 0 to avoid overwriting existing issues.
            const firstInput = document.querySelector(`tr[data-rental-detail-id="${groupId}"] .qty-issue-input`);
            if (firstInput && parseFloat(firstInput.value) === 0) {
                suggestQtyForGroup(groupId, 0);
            }
        }
    });

    // Qty Issue autosave on blur
    document.querySelectorAll('.qty-issue-input').forEach(input => {
        input.addEventListener('blur', function() {
            const itemId = this.getAttribute('data-item-id');
            if (itemId) {
                updateQtyIssue(itemId, this);
            }
        });
        
        // Also trigger on Enter key
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur(); // Trigger blur event
            }
        });
    });
    
    // Material dropdown autosave on change. Delegated binding keeps this working
    // after Select2/global UI enhancement wraps or reinitializes table selects.
    document.addEventListener('change', function(e) {
        if (!e.target.matches('.material-select')) {
            return;
        }

        const itemId = e.target.getAttribute('data-item-id');
        if (itemId) {
            updateMaterial(itemId, e.target);
        }
    });

    if (window.jQuery) {
        jQuery(document).on('select2:select change.select2', '.material-select', function() {
            const itemId = this.getAttribute('data-item-id');
            if (itemId) {
                updateMaterial(itemId, this);
            }
        });
    }
    
    // Initialize Flatpickr for date filters (moved to unified block below)

    // Unified filter initialization
    
        // Flatpickr initialization for date inputs
        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('.flatpickr-date').forEach(el => {
                const isActive = el.dataset.filterActive === 'true';
                const dateValue = el.dataset.dateValue || '';

                flatpickr(el, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/M/Y',
                    allowInput: true,
                    defaultDate: isActive && dateValue ? dateValue : null,
                    onChange: function() {
                        applyFilters();
                    }
                });
            });

            document.querySelectorAll('input[type="date"]').forEach(el => {
                flatpickr(el, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/M/Y',
                    allowInput: true
                });
            });
        }

        const table = document.querySelector('table[data-filter-enhanced="1"]');
        if (!table) return;

        const filters = table.querySelectorAll('.column-filter');
        
        function applyAllFilters() {
            const url = new URL(window.location.href);
            filters.forEach(filter => {
                const name = filter.getAttribute('name');
                const value = filter.value.trim();
                
                if (value) {
                    url.searchParams.set(name, value);
                } else {
                    url.searchParams.delete(name);
                }
            });
            
            // Also preserve common params like per_page
            const perPage = document.getElementById('perPage');
            if (perPage && perPage.value) {
                url.searchParams.set('per_page', perPage.value);
            }

            // Reset to page 1 on filter change
            url.searchParams.set('page', '1');
            window.location.assign(url.toString());
        }

        // DEBUG: Log filter elements
        
        // Attach events to each filter explicitly
        filters.forEach((filter, index) => {
            
            if (filter.tagName === 'SELECT') {
                filter.addEventListener('change', function() {
                    applyAllFilters();
                });
            } else if (filter.tagName === 'INPUT') {
                // Listen for Enter key
                filter.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        applyAllFilters();
                    }
                });
            }
        });

        // Event for perPage dropdown
        const perPageEl = document.getElementById('perPage');
        if (perPageEl) {
            perPageEl.addEventListener('change', applyAllFilters);
        }
        
    
    // === JAVASCRIPT STICKY HEADER ===
    // Force sticky by ensuring parent containers don't break it
    var tableContainer = document.querySelector('.table-container');
    if (tableContainer) {
        // Force the container to be the scroll context
        tableContainer.style.maxHeight = '65vh';
        tableContainer.style.overflowY = 'auto';
        tableContainer.style.overflowX = 'auto';
        tableContainer.style.position = 'relative';
        
        // Force thead to be sticky
        var thead = tableContainer.querySelector('thead');
        if (thead) {
            thead.style.position = 'sticky';
            thead.style.top = '0';
            thead.style.zIndex = '100';
            
            // Force all th in thead to be sticky
            var ths = thead.querySelectorAll('th');
            ths.forEach(function(th) {
                th.style.position = 'sticky';
                th.style.top = '0';
                th.style.zIndex = '100';
            });
            
            // Force filter row (second tr) to be sticky below header
            var filterRow = thead.querySelector('tr:nth-child(2)');
            if (filterRow) {
                var filterThs = filterRow.querySelectorAll('th');
                filterThs.forEach(function(th) {
                    th.style.top = '50px';
                    th.style.zIndex = '90';
                });
            }
        }
    }
});
</script>
@endsection
