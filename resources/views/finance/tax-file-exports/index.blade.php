@extends('layouts.app')

@section('title', 'Tax File Exports')
@section('breadcrumb', 'Home / Finance / Tax File Exports')

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
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 120px; min-width: 120px; }

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

    /* Statistics Cards */
    .statistics-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
        margin-bottom: 24px;
        width: 100%;
    }
    
    .stat-card {
        border-radius: 16px;
        padding: 24px;
        color: white;
        position: relative;
        overflow: hidden;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .stat-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        z-index: 1;
    }
    
    .stat-info h3 {
        font-size: 32px;
        font-weight: 800;
        margin: 0;
        line-height: 1;
    }
    
    .stat-info p {
        font-size: 14px;
        font-weight: 600;
        margin: 8px 0 0 0;
        opacity: 0.9;
    }
    
    .stat-icon {
        font-size: 40px;
        opacity: 0.3;
        transform: rotate(-15deg);
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
    
    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-processing {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .status-completed {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-failed {
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

        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 16px;
        }

        .stat-card {
            padding: 12px;
        }

        .stat-number {
            font-size: 20px;
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

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    /* Extra small screens */
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 8px;
            padding: 12px;
        }
        
        .stat-card {
            padding: 8px;
        }
        
        .stat-number {
            font-size: 18px;
        }
        
        .stat-label {
            font-size: 11px;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Statistics Cards -->
        <div class="w-full bg-white p-4 rounded-t-[10px]">
            <div class="statistics-grid">
                <!-- Total Exports -->
                <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3>{{ $statistics['total'] ?? 0 }}</h3>
                            <p>Total Exports</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-file-export"></i>
                        </div>
                    </div>
                </div>

                <!-- Pending -->
                <div class="stat-card" style="background: linear-gradient(135deg, #eab308 0%, #ea580c 100%);">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3>{{ $statistics['pending'] ?? 0 }}</h3>
                            <p>Pending</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>

                <!-- Processing -->
                <div class="stat-card" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3>{{ $statistics['processing'] ?? 0 }}</h3>
                            <p>Processing</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-sync"></i>
                        </div>
                    </div>
                </div>

                <!-- Completed -->
                <div class="stat-card" style="background: linear-gradient(135deg, #22c55e 0%, #059669 100%);">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3>{{ $statistics['completed'] ?? 0 }}</h3>
                            <p>Completed</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <!-- Failed -->
                <div class="stat-card" style="background: linear-gradient(135deg, #ef4444 0%, #ec4899 100%);">
                    <div class="stat-content">
                        <div class="stat-info">
                            <h3>{{ $statistics['failed'] ?? 0 }}</h3>
                            <p>Failed</p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax File Exports Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white p-4 border-t border-gray-200">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Tax File Exports</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">New Export</span>
                    <span class="md:hidden">New</span>
                </button>
            </div>
        </div>

        <!-- Filter Row -->
        <div class="flex flex-row justify-end items-center w-full p-6 bg-white border-b border-gray-200 rounded-b-[10px] mb-4 shadow-sm">
            <div class="flex flex-row items-center gap-8">
                <!-- Date Filter Group -->
                <div class="flex flex-row items-center gap-6">
                    <!-- Date From -->
                    <div class="flex items-center gap-4">
                        <span style="font-size: 14px; font-weight: 600; color: #374151;">Dari</span>
                        <input type="text" id="dateFromFilter" 
                               class="cursor-pointer"
                               style="background: #f9fafb; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px 16px; font-size: 14px; font-weight: 600; color: #374151; width: 160px; outline: none;"
                               data-date="{{ request('start_date', now()->toDateString()) }}"
                               placeholder="Start Date"
                               readonly>
                    </div>

                    <!-- Date To -->
                    <div class="flex items-center gap-4">
                        <span style="font-size: 14px; font-weight: 600; color: #374151;">Sampai</span>
                        <input type="text" id="dateToFilter" 
                               class="cursor-pointer"
                               style="background: #f9fafb; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px 16px; font-size: 14px; font-weight: 600; color: #374151; width: 160px; outline: none;"
                               data-date="{{ request('end_date', now()->addDays(14)->toDateString()) }}"
                               placeholder="End Date"
                               readonly>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-4 ml-2">
                        <button class="btn btn-primary" style="background: #214589; color: white; padding: 12px 30px; border-radius: 12px; font-weight: 700; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);" onclick="applyFilters()">
                            Apply
                        </button>
                        <button class="btn btn-secondary" style="background: #3b82f6; color: white; border: none; padding: 12px 30px; border-radius: 12px; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);" onclick="clearFilters()">
                            Reset
                        </button>
                    </div>
                </div>
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
                        <th data-no-filter>Export Number</th>
                        <th data-column="export_date" data-type="date">Export Date</th>
                        <th data-no-filter>Export Type</th>
                        <th data-no-filter>Period From</th>
                        <th data-no-filter>Period To</th>
                        <th data-no-filter>Included Invoices</th>
                        <th data-no-filter>File Format</th>
                        <th data-column="status">Status</th>
                        <th data-no-filter>Total Records</th>
                        <th data-no-filter>File Size</th>
                        <th data-column="creator.name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updater.name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                        <th data-no-filter>Actions</th>
                    </tr>
                </thead>
                <!-- Table Body -->
                <tbody>
                    @forelse($exports ?? [] as $export)
                    <tr data-id="{{ $export->id }}" onclick="openViewModal({{ $export->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $export->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td class="font-medium">{{ $export->export_number ?? '-' }}</td>
                        <td>{{ $export->formatted_export_date ?? '-' }}</td>
                        <td>
                            <span class="badge badge-info">
                                {{ ucfirst($export->export_type ?? 'Monthly') }}
                            </span>
                        </td>
                        <td>{{ $export->formatted_period_from ?? '-' }}</td>
                        <td>{{ $export->formatted_period_to ?? '-' }}</td>
                        <td>
                            <span style="font-size: 0.875rem; color: #6b7280;">
                                {{ $export->formatted_invoice_list }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-secondary">
                                {{ strtoupper($export->file_format ?? 'CSV') }}
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $export->status ?? 'pending' }}">
                                {{ ucfirst($export->status ?? 'Pending') }}
                            </span>
                        </td>
                        <td>{{ number_format($export->total_records ?? 0) }}</td>
                        <td>{{ $export->formatted_file_size ?? '-' }}</td>
                        <td>{{ $export->createdBy->name ?? '-' }}</td>
                        <td>
                            @if($export->created_at)
                                {{ \Carbon\Carbon::parse($export->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($export->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $export->updatedBy->name ?? '-' }}</td>
                        <td>
                            @if($export->updated_at)
                                {{ \Carbon\Carbon::parse($export->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($export->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openViewModal({{ $export->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($export->status === 'pending')
                                <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openEditModal({{ $export->id }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endif
                                @if($export->status === 'completed' && $export->file_path)
                                <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); downloadExport({{ $export->id }})">
                                    <i class="fas fa-download"></i>
                                </button>
                                @endif
                                @if($export->status === 'pending')
                                <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); generateESPTExport({{ $export->id }})">
                                    <i class="fas fa-file-csv"></i>
                                </button>
                                @endif
                                @if(in_array($export->status, ['pending', 'failed']))
                                <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openDeleteModal({{ $export->id }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-file-export text-4xl mb-3"></i>
                                <p class="text-lg">No tax file exports found</p>
                                <button class="btn btn-primary mt-2" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Add First Export
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($exports->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $exports->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Tax File Export Details</h2>
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
        <h3 class="delete-modal-title">Delete Tax File Export</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to delete this export? This action cannot be undone.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Yes, Delete</button>
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
        <p class="delete-modal-description" id="errorMessage">We couldn't delete the export. Please try again.</p>
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
        <p class="delete-modal-description" id="successMessage">The export has been successfully deleted.</p>
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
    openModal('Create New Tax File Export');
    
    // Get available invoices from the server
    fetch('/finance/tax-file-exports/create', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        const invoices = result.invoices || [];
        
        document.getElementById('modalBody').innerHTML = `
            <form id="form" onsubmit="submitForm(event)">
                <div class="modal-section">
                    <div class="modal-section-title">Export Mode</div>
                    <div class="form-group">
                        <label class="form-label">Select Export Mode *</label>
                        <div style="display: flex; gap: 20px; margin-top: 8px;">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="radio" name="selection_mode" value="date_range" checked onchange="toggleExportMode()" style="margin-right: 8px;">
                                <span>By Date Range</span>
                            </label>
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="radio" name="selection_mode" value="specific_invoices" onchange="toggleExportMode()" style="margin-right: 8px;">
                                <span>By Specific Invoices</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Export Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Export Date *</label>
                            <input type="text" id="modal_export_date" name="export_date" class="form-input" value="${new Date().toLocaleDateString('en-CA')}" required readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Export Type *</label>
                            <select name="export_type" class="form-input" required>
                                <option value="">Select Type</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                                <option value="custom" selected>Custom Period</option>
                            </select>
                        </div>
                        <div class="form-group" id="period_from_group">
                            <label class="form-label">Period From *</label>
                            <input type="text" id="modal_period_from" name="period_from" class="form-input" readonly>
                        </div>
                        <div class="form-group" id="period_to_group">
                            <label class="form-label">Period To *</label>
                            <input type="text" id="modal_period_to" name="period_to" class="form-input" readonly>
                        </div>
                    </div>
                    
                    <!-- Invoice Selection Section -->
                    <div id="invoice_selection_section" style="display: none; margin-top: 20px;">
                        <label class="form-label">Select Invoices *</label>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-top: 8px;">
                            ${invoices.length > 0 ? invoices.map(inv => `
                                <label style="display: flex; align-items: center; padding: 8px; cursor: pointer; border-bottom: 1px solid #f3f4f6;">
                                    <input type="checkbox" name="invoice_ids[]" value="${inv.id}" style="margin-right: 12px;">
                                    <div style="flex: 1;">
                                        <strong>${inv.invoice_number}</strong> - ${inv.customer_name || 'N/A'}
                                        <br><small style="color: #6b7280;">${new Date(inv.invoice_date).toLocaleDateString('id-ID')} | ${inv.formatted_total_amount || 'Rp 0'}</small>
                                    </div>
                                </label>
                            `).join('') : '<p style="text-align: center; color: #9ca3af; padding: 20px;">No invoices available</p>'}
                        </div>
                        <small style="color: #6b7280; margin-top: 8px; display: block;">Period dates will be auto-calculated from selected invoices</small>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Export Settings</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">File Format *</label>
                            <select name="file_format" class="form-input" required>
                                <option value="">Select Format</option>
                                <option value="csv">CSV (e-SPT Compatible)</option>
                                <option value="xlsx">Excel (XLSX)</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Include Details</label>
                            <select name="include_details" class="form-input">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-input form-textarea" placeholder="Additional notes about this export"></textarea>
                        </div>
                    </div>
                </div>
            </form>
        `;
        
        // Add modal footer
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" form="form" class="btn btn-primary">Create Export</button>
        `;
        
        initModalFlatpickr();
    })
    .catch(error => {
        console.error('Error loading invoices:', error);
        // Fallback to simple form without invoices
        openCreateModalFallback();
    });
}

function toggleExportMode() {
    const mode = document.querySelector('input[name="selection_mode"]:checked').value;
    const invoiceSection = document.getElementById('invoice_selection_section');
    const periodFromGroup = document.getElementById('period_from_group');
    const periodToGroup = document.getElementById('period_to_group');
    const periodFromInput = document.getElementById('modal_period_from');
    const periodToInput = document.getElementById('modal_period_to');
    
    if (mode === 'specific_invoices') {
        invoiceSection.style.display = 'block';
        periodFromGroup.style.display = 'none';
        periodToGroup.style.display = 'none';
        periodFromInput.removeAttribute('required');
        periodToInput.removeAttribute('required');
    } else {
        invoiceSection.style.display = 'none';
        periodFromGroup.style.display = 'block';
        periodToGroup.style.display = 'block';
        periodFromInput.setAttribute('required', 'required');
        periodToInput.setAttribute('required', 'required');
    }
}

function openCreateModalFallback() {
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <input type="hidden" name="selection_mode" value="date_range">
            <div class="modal-section">
                <div class="modal-section-title">Export Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Export Date *</label>
                        <input type="text" id="modal_export_date" name="export_date" class="form-input" value="${new Date().toLocaleDateString('en-CA')}" required readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Export Type *</label>
                        <select name="export_type" class="form-input" required>
                            <option value="">Select Type</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                            <option value="custom">Custom Period</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Period From *</label>
                        <input type="text" id="modal_period_from" name="period_from" class="form-input" required readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Period To *</label>
                        <input type="text" id="modal_period_to" name="period_to" class="form-input" required readonly>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Export Settings</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">File Format *</label>
                        <select name="file-format" class="form-input" required>
                            <option value="">Select Format</option>
                            <option value="csv">CSV (e-SPT Compatible)</option>
                            <option value="xlsx">Excel (XLSX)</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Include Details</label>
                        <select name="include_details" class="form-input">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input form-textarea" placeholder="Additional notes about this export"></textarea>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    initModalFlatpickr();
}

function initModalFlatpickr() {
    const commonConfig = {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/M/Y',
        allowInput: false
    };

    const exportDate = document.getElementById('modal_export_date');
    if (exportDate) flatpickr(exportDate, commonConfig);

    const periodFrom = document.getElementById('modal_period_from');
    if (periodFrom) flatpickr(periodFrom, commonConfig);

    const periodTo = document.getElementById('modal_period_to');
    if (periodTo) flatpickr(periodTo, commonConfig);
}

function openViewModal(id) {
    openModal('View Tax File Export');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/finance/tax-file-exports/${id}`, {
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
                    <div class="modal-section-title">Export Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Export Number</label>
                            <p class="detail-value">${data.data.export_number || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Export Date</label>
                            <p class="detail-value">${data.data.export_date ? new Date(data.data.export_date).toLocaleDateString('id-ID') : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Export Type</label>
                            <p class="detail-value">${data.data.export_type || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Period From</label>
                            <p class="detail-value">${data.data.period_from ? new Date(data.data.period_from).toLocaleDateString('id-ID') : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Period To</label>
                            <p class="detail-value">${data.data.period_to ? new Date(data.data.period_to).toLocaleDateString('id-ID') : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value">
                                <span class="status-badge status-${data.data.status || 'pending'}">${data.data.status ? data.data.status.charAt(0).toUpperCase() + data.data.status.slice(1) : 'Pending'}</span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Export Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">File Format</label>
                            <p class="detail-value">${data.data.file_format || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Total Records</label>
                            <p class="detail-value">${data.data.total_records ? parseInt(data.data.total_records).toLocaleString('id-ID') : '0'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">File Size</label>
                            <p class="detail-value">${data.data.formatted_file_size || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Include Details</label>
                            <p class="detail-value">${data.data.include_details ? 'Yes' : 'No'}</p>
                        </div>
                        <div class="detail-item" style="grid-column: 1 / -1;">
                            <label class="form-label">Notes</label>
                            <p class="detail-value">${data.data.notes || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Timestamps</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Created By</label>
                            <p class="detail-value">${data.data.created_by?.name || data.data.createdBy?.name || '-'}</p>
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
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit Export</button>
        `;
        })
        .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
        `;
        });
}

function openEditModal(id) {
    openModal('Edit Tax File Export');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/finance/tax-file-exports/${id}/edit`, {
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
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <input type="hidden" name="id" value="${data.data.id}">
                    <div class="modal-section">
                        <div class="modal-section-title">Export Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Export Date *</label>
                                <input type="text" id="modal_export_date" name="export_date" class="form-input" value="${data.data.export_date ? new Date(data.data.export_date).toLocaleDateString('en-CA') : ''}" required readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Export Type *</label>
                                <select name="export_type" class="form-input" required>
                                    <option value="">Select Type</option>
                                    <option value="monthly" ${data.data.export_type === 'monthly' ? 'selected' : ''}>Monthly</option>
                                    <option value="quarterly" ${data.data.export_type === 'quarterly' ? 'selected' : ''}>Quarterly</option>
                                    <option value="yearly" ${data.data.export_type === 'yearly' ? 'selected' : ''}>Yearly</option>
                                    <option value="custom" ${data.data.export_type === 'custom' ? 'selected' : ''}>Custom Period</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Period From *</label>
                                <input type="text" id="modal_period_from" name="period_from" class="form-input" value="${data.data.period_from ? new Date(data.data.period_from).toLocaleDateString('en-CA') : ''}" required readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Period To *</label>
                                <input type="text" id="modal_period_to" name="period_to" class="form-input" value="${data.data.period_to ? new Date(data.data.period_to).toLocaleDateString('en-CA') : ''}" required readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Export Settings</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">File Format *</label>
                                <select name="file_format" class="form-input" required>
                                    <option value="">Select Format</option>
                                    <option value="csv" ${data.data.file_format === 'csv' ? 'selected' : ''}>CSV</option>
                                    <option value="xlsx" ${data.data.file_format === 'xlsx' ? 'selected' : ''}>Excel (XLSX)</option>
                                    <option value="pdf" ${data.data.file_format === 'pdf' ? 'selected' : ''}>PDF</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Include Details</label>
                                <select name="include_details" class="form-input">
                                    <option value="0" ${!data.data.include_details ? 'selected' : ''}>No</option>
                                    <option value="1" ${data.data.include_details ? 'selected' : ''}>Yes</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-input form-textarea" placeholder="Additional notes about this export">${data.data.notes || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Export</button>
            `;

            initModalFlatpickr();
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            `;
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/finance/tax-file-exports/${id}` : '/finance/tax-file-exports';
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
            showErrorModal(result.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan.');
    });
}

// Delete Modal functions
function openDeleteModal(id = null) {
    if (id) {
        selectedIdsForRetry = [id];
    }
    
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Apakah Anda yakin ingin menghapus export ini? Tindakan ini tidak dapat dibatalkan.'
        : `Apakah Anda yakin ingin menghapus ${count} export? Tindakan ini tidak dapat dibatalkan.`;
    
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
    
    fetch('/finance/tax-file-exports/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ export_ids: selectedIdsForRetry })
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
        showErrorModal('Terjadi kesalahan jaringan.');
    });
}

// Bulk operations
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu export yang ingin dihapus.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Download export function
function downloadExport(id) {
    window.open(`/finance/tax-file-exports/${id}/download`, '_blank');
}

// Generate e-SPT export function
function generateESPTExport(id) {
    showConfirmDialog(
        'Generate export e-SPT?',
        'File CSV yang kompatibel dengan e-SPT akan dibuat.'
    ).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        fetch(`/finance/tax-file-exports/${id}/generate-espt`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                showSuccessModal('Export e-SPT berhasil dibuat.');
                location.reload();
            } else {
                showErrorModal('Gagal membuat export e-SPT: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Gagal membuat export e-SPT.');
        });
    });
}

function applyFilters() {
    const filterFromEl = document.getElementById('dateFromFilter');
    const filterToEl = document.getElementById('dateToFilter');
    
    // Get date values from data-date attribute (set by Flatpickr)
    const dateFrom = filterFromEl ? (filterFromEl.getAttribute('data-date') || filterFromEl.value || '') : '';
    const dateTo = filterToEl ? (filterToEl.getAttribute('data-date') || filterToEl.value || '') : '';
    
    const params = new URLSearchParams(window.location.search);
    
    // Always clear search since it's removed
    params.delete('search');
    params.delete('status');
    params.delete('file_format');
    params.delete('export_type');

    if (dateFrom) {
        params.set('start_date', dateFrom);
    } else {
        params.delete('start_date');
    }

    if (dateTo) {
        params.set('end_date', dateTo);
    } else {
        params.delete('end_date');
    }
    
    // Reset to page 1 on filter
    params.set('page', '1');
    
    window.location.href = window.location.pathname + '?' + params.toString();
}

function clearFilters() {
    window.location.href = window.location.pathname;
}

// Success Modal functions
function showSuccessModal(message) {
    const defaultMessage = typeof message === 'number' 
        ? (message === 1 ? 'Export berhasil dihapus.' : `${message} export berhasil dihapus.`)
        : message;
    
    document.getElementById('successMessage').textContent = defaultMessage;
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
    document.getElementById('errorMessage').textContent = message || 'Export gagal dihapus. Silakan coba lagi.';
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
    const filterFromEl = document.getElementById('dateFromFilter');
    if (filterFromEl) {
        flatpickr(filterFromEl, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/M/Y',
            defaultDate: filterFromEl.getAttribute('data-date'),
            onChange: function(selectedDates, dateStr) {
                filterFromEl.setAttribute('data-date', dateStr);
            }
        });
    }
    
    // Filter Date To
    const filterToEl = document.getElementById('dateToFilter');
    if (filterToEl) {
        flatpickr(filterToEl, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/M/Y',
            defaultDate: filterToEl.getAttribute('data-date'),
            onChange: function(selectedDates, dateStr) {
                filterToEl.setAttribute('data-date', dateStr);
            }
        });
    }
});
</script>
@endsection
