@extends('layouts.app')

@section('title', 'Inventory Requests')
@section('breadcrumb', 'Home / Warehouse / Inventory Requests')

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
        min-width: 2400px;
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
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 200px; min-width: 200px; }
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
        max-width: 90vw;
        max-height: 85vh;
        width: 600px;
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
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
        flex-shrink: 0;
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
        padding: 20px 20px 40px 20px;
        overflow-y: auto;
        overflow-x: hidden;
        flex: 1;
        min-height: 0;
        max-height: calc(85vh - 160px);
        -webkit-overflow-scrolling: touch;
    }
    
    .modal-body::-webkit-scrollbar {
        width: 8px;
    }
    
    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .modal-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
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

    .modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: center;
        gap: 20px;
        position: sticky;
        bottom: 0;
        flex-shrink: 0;
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

    /* Statistics Cards Enhanced Styling */
    .statistics-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1.5rem;
        margin-bottom: 2rem;
        width: 100%;
    }
    
    .stat-card {
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        padding: 2rem 1.5rem;
        color: white;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    
    .stat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 16px 48px rgba(0, 0, 0, 0.2);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: -20px;
        right: -20px;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover::before {
        transform: scale(1.2);
        opacity: 0.8;
    }
    
    .stat-content {
        position: relative;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 100%;
    }
    
    .stat-info {
        flex: 1;
    }
    
    .stat-info h3 {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0 0 0.5rem 0;
        line-height: 1;
    }
    
    .stat-info p {
        font-size: 0.95rem;
        font-weight: 600;
        margin: 0;
        opacity: 0.95;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-icon {
        font-size: 3rem;
        opacity: 0.15;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover .stat-icon {
        opacity: 0.25;
        transform: scale(1.1);
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
    
    /* Statistics Cards Responsive */
    @media (max-width: 640px) {
        .statistics-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            padding: 1.5rem 1rem;
            min-height: 100px;
        }
        
        .stat-info h3 {
            font-size: 2rem;
        }
        
        .stat-info p {
            font-size: 0.85rem;
        }
        
        .stat-icon {
            font-size: 2.5rem;
        }
    }
    
    @media (min-width: 641px) and (max-width: 768px) {
        .statistics-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .stat-card {
            padding: 1.5rem 1rem;
            min-height: 110px;
        }
        
        .stat-info h3 {
            font-size: 2.2rem;
        }
        
        .stat-icon {
            font-size: 2.8rem;
        }
    }
    
    @media (min-width: 769px) and (max-width: 1024px) {
        .statistics-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }
        
        .stat-card {
            padding: 1.75rem 1.25rem;
            min-height: 115px;
        }
        
        .stat-info h3 {
            font-size: 2.3rem;
        }
        
        .stat-icon {
            font-size: 2.9rem;
        }
    }
    
    @media (min-width: 1025px) and (max-width: 1280px) {
        .statistics-grid {
            grid-template-columns: repeat(5, 1fr);
            gap: 1.5rem;
        }
        
        .stat-card {
            padding: 2rem 1.5rem;
            min-height: 120px;
        }
        
        .stat-info h3 {
            font-size: 2.5rem;
        }
        
        .stat-icon {
            font-size: 3rem;
        }
    }
    
    @media (min-width: 1281px) {
        .statistics-grid {
            grid-template-columns: repeat(5, 1fr);
            gap: 2rem;
        }
        
        .stat-card {
            padding: 2.5rem 2rem;
            min-height: 140px;
        }
        
        .stat-info h3 {
            font-size: 3rem;
        }
        
        .stat-info p {
            font-size: 1rem;
        }
        
        .stat-icon {
            font-size: 3.5rem;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Module Header - Improved -->
        <div class="w-full bg-gradient-to-r from-[#214589] to-[#1e3a8a] rounded-t-[10px] shadow-lg">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-6">
                <div class="flex items-center gap-3 flex-1">
                    <div class="bg-white/10 backdrop-blur-sm p-3 rounded-lg">
                        <i class="fas fa-clipboard-list text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Inventory Requests</h1>
                        <p class="text-blue-100 text-sm mt-1">Manage inventory requests from branches</p>
                    </div>
                </div>
                <div class="flex flex-row justify-end items-center gap-2">
                    @if(auth()->user() && auth()->user()->hasPermission('warehouse.inventory-requests.create'))
                    <button class="btn btn-secondary shadow-md hover:shadow-lg transition-all duration-200 whitespace-nowrap" onclick="openImportModal()">
                        <i class="fas fa-file-import"></i>
                        <span class="hidden md:inline">Import Excel/CSV</span>
                        <span class="md:hidden">Import</span>
                    </button>
                    @endif
                    <button class="btn btn-primary shadow-md hover:shadow-lg transition-all duration-200 whitespace-nowrap" onclick="openCreateModal()">
                        <i class="fas fa-plus me-2"></i>
                        <span>Add New Request</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Clean Filter Section -->
        <div id="filterSection" class="w-full bg-white border-b border-gray-200" style="padding: 24px 40px;">
            <div class="flex flex-wrap items-center justify-end" style="gap: 48px;">
                <!-- Date From Group -->
                <div class="flex items-center" style="gap: 20px;">
                    <span style="font-size: 14px; font-weight: 600; color: #374151;">Dari</span>
                    <input type="text" id="filterDateFrom" 
                        class="cursor-pointer"
                        style="background: #f9fafb; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #374151; width: 160px; outline: none;"
                        data-date="{{ request('date_from', now()->toDateString()) }}"
                        readonly>
                </div>

                <!-- Date To Group -->
                <div class="flex items-center" style="gap: 20px;">
                    <span style="font-size: 14px; font-weight: 600; color: #374151;">Sampai</span>
                    <input type="text" id="filterDateTo" 
                        class="cursor-pointer"
                        style="background: #f9fafb; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #374151; width: 160px; outline: none;"
                        data-date="{{ request('date_to', now()->addDays(14)->toDateString()) }}"
                        readonly>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center" style="gap: 24px;">
                    <button type="button" 
                        style="background: #214589; color: white; padding: 14px 40px; font-size: 14px; font-weight: 700; border-radius: 16px; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);"
                        onclick="applyFilters()">
                        Apply
                    </button>
                    
                    <button type="button" 
                        style="background: #3b82f6; color: white; padding: 14px 40px; font-size: 14px; font-weight: 700; border-radius: 16px; border: none; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);"
                        onclick="resetFilters()">
                        Reset
                    </button>
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
        <div class="w-full table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="request_number">Nomor Request</th>
                        <th data-column="warehouse__branch__name">Cabang</th>
                        <th data-column="status">Status</th>
                        <th data-column="required_date" data-type="date">Tanggal Keperluan</th>
                        <th data-column="requestedBy__name">Diajukan Oleh</th>
                        <th data-column="approved_at" data-type="date">Disetujui Pada</th>
                        <th data-column="approvedBy__name">Oleh</th>
                        <th data-column="processed_date" data-type="date">Dikeluarkan Pada</th>
                        <th data-column="issued_by">Oleh</th>
                        <th data-column="shipped_at" data-type="date">Dikirim</th>
                        <th data-column="shipping_tracking_number">Nomor Tracking</th>
                        <th data-column="received_at" data-type="date">Diterima Pada</th>
                        <th data-column="received_by">Oleh</th>
                        <th data-column="notes">Catatan Tambahan</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($requests ?? [] as $request)
                    <tr data-id="{{ $request->id }}" onclick="window.location='{{ route('warehouse.inventory-requests.show', $request->id) }}'">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $request->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $request->request_number ?? 'N/A' }}</td>
                        <td>{{ $request->branch_name }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full 
                                {{ $request->status == 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $request->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $request->status == 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $request->status == 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $request->status == 'shipped' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $request->status == 'completed' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ !in_array($request->status, ['draft', 'pending', 'approved', 'rejected', 'shipped', 'completed']) ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ ucfirst($request->status ?? 'N/A') }}
                            </span>
                        </td>
                        <td>{{ $request->required_date ? $request->required_date->format('d/M/Y') : '-' }}</td>
                        <td>{{ $request->requestedBy?->name ?? 'N/A' }}</td>
                        <td>{{ $request->approved_at ? $request->approved_at->format('d/M/Y H:i') : '-' }}</td>
                        <td>{{ $request->approvedBy?->name ?? '-' }}</td>
                        <td>{{ $request->processed_date ? $request->processed_date->format('d/M/Y H:i') : '-' }}</td>
                        <td>{{ $request->processed_date ? ($request->updatedBy?->name ?? 'System') : '-' }}</td>
                        <td>{{ $request->shipped_at ? $request->shipped_at->format('d/M/Y H:i') : '-' }}</td>
                        <td>{{ $request->shipping_tracking_number ?? '-' }}</td>
                        <td>
                            @if($request->inventoryReceivings && $request->inventoryReceivings->isNotEmpty())
                                {{ $request->inventoryReceivings->first()->receive_date ? $request->inventoryReceivings->first()->receive_date->format('d/M/Y H:i') : '-' }}
                            @elseif($request->inventoryIssuing && $request->inventoryIssuing->received_at)
                                {{ $request->inventoryIssuing->received_at->format('d/M/Y H:i') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($request->inventoryReceivings && $request->inventoryReceivings->isNotEmpty() && $request->inventoryReceivings->first()->receivedBy)
                                {{ $request->inventoryReceivings->first()->receivedBy->name }}
                            @elseif($request->inventoryIssuing && $request->inventoryIssuing->receivedBy)
                                {{ $request->inventoryIssuing->receivedBy->name }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="max-w-xs truncate">{{ $request->notes ?? '-' }}</td>
                        <td>{{ $request->createdBy?->name ?? '-' }}</td>
                        <td>
                            @if($request->created_at)
                                {{ \Carbon\Carbon::parse($request->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($request->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $request->updatedBy?->name ?? '-' }}</td>
                        <td>
                            @if($request->updated_at)
                                {{ \Carbon\Carbon::parse($request->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($request->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No inventory requests found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($requests->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $requests->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Inventory Request</h2>
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
        <h2 id="deleteModalTitle" class="delete-modal-title">Hide 0 Records?</h2>
        
        <!-- Description -->
        <p class="delete-modal-description" id="deleteMessage">
            Are you sure you want to hide this inventory request? This action can be undone later.
        </p>
        
        <!-- Buttons -->
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Yes, Hide</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
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
        <h2 class="error-modal-title">Hmm... Something Went Wrong</h2>
        
        <!-- Description -->
        <p class="error-modal-description" id="errorMessage">
            We couldn't hide the inventory request. Please try again.
        </p>
        
        <!-- Buttons -->
        <div class="error-modal-buttons">
            <button class="btn btn-error-close" onclick="closeErrorModal()">Close</button>
            <button class="btn btn-error-retry" onclick="retryDelete()">Try Again</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="success-icon-container">
            <svg class="success-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="successTrashGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#1E40AF;stop-opacity:1" />
                    </linearGradient>
                    <filter id="successShadow" x="-50%" y="-50%" width="200%" height="200%">
                        <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                    </filter>
                </defs>
                <!-- Trash Can Body -->
                <rect x="25" y="35" width="50" height="45" rx="3" fill="url(#successTrashGradient)" filter="url(#successShadow)"/>
                <!-- Trash Can Lid -->
                <rect x="20" y="30" width="60" height="8" rx="4" fill="url(#successTrashGradient)" filter="url(#successShadow)"/>
                <!-- Lid Handle -->
                <rect x="45" y="25" width="10" height="8" rx="2" fill="url(#successTrashGradient)" filter="url(#successShadow)"/>
                <!-- Lid Slightly Open -->
                <rect x="20" y="32" width="60" height="2" rx="1" fill="#1E40AF" opacity="0.3"/>
                <!-- Success Checkmark Circle -->
                <circle cx="75" cy="65" r="12" fill="#10B981" filter="url(#successShadow)"/>
                <path d="M70 65 L73 68 L80 61" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        
        <!-- Title -->
        <h2 class="success-modal-title">All Set!</h2>
        
        <!-- Description -->
        <p class="success-modal-description" id="successMessage">
            The inventory request has been successfully hidden.
        </p>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;

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

// ===== Import Excel/CSV functions =====
function openImportModal() {
    openModal('Import Inventory Request dari Excel/CSV');
    document.getElementById('modalBody').innerHTML = `
        <form id="inventoryRequestImportForm" onsubmit="inventoryRequestPreviewImport(event)">
            <div class="form-group">
                <label class="form-label">Pilih File Excel / CSV *</label>
                <input type="file" name="file" id="inventoryRequestImportFile" class="form-input" accept=".csv,.txt,.xlsx,.xls" required onchange="inventoryRequestHandleFileSelect(event)">
                <small class="text-muted">Format: .xlsx, .xls, atau .csv. Maksimum 10MB.</small>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 my-3">
                <div class="font-semibold text-blue-900 mb-1">Unduh contoh format:</div>
                <a href="/warehouse/inventory-requests/import-template?format=xlsx" class="text-blue-700 underline mr-3">Template Excel (.xlsx)</a>
                <a href="/warehouse/inventory-requests/import-template?format=csv" class="text-blue-700 underline">Template CSV</a>
            </div>
            <div id="inventoryRequestPreviewSection" style="display:none;">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 my-3">
                    <div class="font-semibold mb-2">Hasil Preview:</div>
                    <div id="inventoryRequestPreviewContent"></div>
                </div>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <div class="font-semibold text-yellow-900 mb-1">Ketentuan kolom:</div>
                <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                    <li><strong>Wajib:</strong> required_date, reason, quantity, dan salah satu dari product_sku / product_name</li>
                    <li><strong>Branch:</strong> isi branch_code atau branch_name. Jika kosong, sistem memakai branch utama user.</li>
                    <li><strong>request_group:</strong> baris dengan nilai yang sama akan dibuat menjadi satu Inventory Request.</li>
                    <li>Request hasil import dibuat sebagai <strong>draft</strong> seperti create manual.</li>
                </ul>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="button" class="btn btn-info" onclick="inventoryRequestPreviewImport(event)" id="inventoryRequestPreviewBtn" disabled>Preview</button>
        <button type="button" class="btn btn-primary" onclick="inventoryRequestConfirmImport()" id="inventoryRequestConfirmBtn" style="display:none;">Mulai Import</button>
    `;
}

function inventoryRequestHandleFileSelect(event) {
    const btn = document.getElementById('inventoryRequestPreviewBtn');
    btn.disabled = !(event.target.files && event.target.files.length > 0);
    document.getElementById('inventoryRequestPreviewSection').style.display = 'none';
    document.getElementById('inventoryRequestConfirmBtn').style.display = 'none';
}

function inventoryRequestPreviewImport(event) {
    if (event) event.preventDefault();
    const form = document.getElementById('inventoryRequestImportForm');
    const formData = new FormData(form);
    const previewBtn = document.getElementById('inventoryRequestPreviewBtn');
    const section = document.getElementById('inventoryRequestPreviewSection');
    const content = document.getElementById('inventoryRequestPreviewContent');

    if (!formData.get('file') || !formData.get('file').name) {
        alert('Silakan pilih file terlebih dahulu.');
        return;
    }

    previewBtn.disabled = true;
    previewBtn.textContent = 'Memuat...';
    section.style.display = 'block';
    content.innerHTML = '<div class="text-center py-3 text-sm text-gray-500">Menganalisis file...</div>';

    fetch('/warehouse/inventory-requests/import-preview', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(result => {
        previewBtn.disabled = false;
        previewBtn.textContent = 'Preview';
        if (result.status !== 'success') {
            content.innerHTML = '<div class="text-red-600 text-sm">' + (result.message || 'Gagal membaca file.') + '</div>';
            return;
        }

        const p = result.preview;
        let html = '<div class="text-sm space-y-1">';
        html += '<div>Total baris item: <strong>' + p.total_rows + '</strong></div>';
        html += '<div>Request akan dibuat: <strong class="text-green-700">' + p.new + '</strong></div>';
        if (p.errors && p.errors.length) {
            html += '<div class="mt-2 text-red-600"><strong>' + p.errors.length + ' peringatan:</strong><ul class="list-disc list-inside">';
            p.errors.slice(0, 10).forEach(e => { html += '<li>' + e + '</li>'; });
            if (p.errors.length > 10) html += '<li>...dan ' + (p.errors.length - 10) + ' lainnya</li>';
            html += '</ul></div>';
        }
        html += '</div>';
        content.innerHTML = html;
        document.getElementById('inventoryRequestConfirmBtn').style.display = (p.new > 0) ? 'inline-flex' : 'none';
    })
    .catch(() => {
        previewBtn.disabled = false;
        previewBtn.textContent = 'Preview';
        content.innerHTML = '<div class="text-red-600 text-sm">Terjadi kesalahan saat memproses file.</div>';
    });
}

function inventoryRequestConfirmImport() {
    const form = document.getElementById('inventoryRequestImportForm');
    const formData = new FormData(form);
    const confirmBtn = document.getElementById('inventoryRequestConfirmBtn');
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Mengimpor...';

    fetch('/warehouse/inventory-requests/import', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(result => {
        const content = document.getElementById('inventoryRequestPreviewContent');
        const s = result.stats || {};
        let html = '<div class="text-sm space-y-1"><div class="font-semibold text-green-700">' + (result.message || 'Import selesai') + '</div>';
        if (typeof s.success !== 'undefined') {
            html += '<div>Baris berhasil: <strong>' + s.success + '</strong></div>';
        }
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
    .catch(() => {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Mulai Import';
        alert('Terjadi kesalahan saat mengimpor.');
    });
}

// CRUD Modal functions
function openCreateModal() {
    console.log('Opening create modal...');
    openModal('Create New Inventory Request');
    document.getElementById('modalBody').innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 16px; color: #666;">Loading...</p>
        </div>
    `;
    
    fetch('/warehouse/inventory-requests/create')
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);
            if (data.status === 'success') {
                document.getElementById('modalBody').innerHTML = `
                    <form id="form" onsubmit="submitForm(event)">
                        <div class="modal-section">
                            <div class="modal-section-title">Location & Priority</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="form-label">Branch *</label>
                                    ${data.data.can_select_branch ? `
                                        <select name="branch_id" class="form-input" required>
                                            <option value="">Select Branch</option>
                                            ${data.data.branches ? data.data.branches.map(branch => 
                                                `<option value="${branch.id}" ${branch.id == data.data.current_user_branch_id ? 'selected' : ''}>${branch.name}</option>`
                                            ).join('') : ''}
                                        </select>
                                    ` : `
                                        <input type="hidden" name="branch_id" value="${data.data.current_user_branch_id || ''}">
                                        <select class="form-input" disabled>
                                            ${data.data.branches ? data.data.branches.map(branch => 
                                                `<option value="${branch.id}" ${branch.id == data.data.current_user_branch_id ? 'selected' : ''}>${branch.name}</option>`
                                            ).join('') : '<option value="">Branch User</option>'}
                                        </select>
                                        <small style="display: block; margin-top: 6px; color: #6b7280;">Branch mengikuti branch user. Pilihan branch lain hanya muncul untuk user multi branch.</small>
                                    `}
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Required Date *</label>
                                    <input type="date" name="required_date" id="requiredDateInput" class="form-input" required 
                                        style="cursor: pointer;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-section">
                            <div class="modal-section-title">Request Items</div>
                            <div id="itemsContainer">
                                <div class="item-row" style="margin-bottom: 16px;">
                                    <div style="display: grid; grid-template-columns: 1fr 120px 40px; gap: 12px; align-items: end;">
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label class="form-label" style="display: block; margin-bottom: 6px; font-weight: 500;">Product</label>
                                            <select name="items[0][master_product_id]" class="form-input" required style="width: 100%;">
                                                <option value="">Select Product</option>
                                                ${data.data.products.map(product => {
                                                    return `<option value="${product.id}">${product.name}</option>`;
                                                }).join('')}
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 0;">
                                            <label class="form-label" style="display: block; margin-bottom: 6px; font-weight: 500;">Quantity</label>
                                            <input type="number" name="items[0][quantity]" class="form-input" min="1" step="1" value="1" required style="width: 100%;">
                                        </div>
                                        <div style="padding-top: 28px;">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeItem(this)" style="visibility: hidden; width: 40px; height: 38px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addItem()" style="margin-top: 12px;">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                        
                        <div class="modal-section">
                            <div class="modal-section-title">Additional Information</div>
                            <div class="form-group">
                                <label class="form-label">Reason</label>
                                <textarea name="reason" class="form-input" rows="3" placeholder="Enter reason for this request" required style="width: 100%; resize: vertical;"></textarea>
                            </div>
                        </div>
                    </form>
                `;
                
                // Store products data globally for add item function
                window.productsData = data.data.products;
                window.itemCounter = 1;
                
                // Initialize Flatpickr for date input
                initModalDatePicker();
                
                // Add modal footer
                document.getElementById('modalFooter').innerHTML = `
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" form="form" class="btn btn-primary">Create Request</button>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading create form:', error);
            document.getElementById('modalBody').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #ef4444;">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 16px;"></i>
                    <p style="font-weight: 600; margin-bottom: 8px;">Error loading form</p>
                    <p style="font-size: 14px; color: #666;">${error.message}</p>
                    <button onclick="openCreateModal()" class="btn btn-primary" style="margin-top: 16px;">Try Again</button>
                </div>
            `;
        });
}

function openEditModal(id) {
    openModal('Edit Inventory Request');
    document.getElementById('modalBody').innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 16px; color: #666;">Loading...</p>
        </div>
    `;
    
    fetch(`/warehouse/inventory-requests/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const request = data.data.request;
                document.getElementById('modalBody').innerHTML = `
                    <form id="form" onsubmit="submitEditForm(event, ${id})">
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="form-label">Request Number</label>
                                    <input type="text" name="request_number" class="form-input" value="${request.request_number || ''}" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Warehouse</label>
                                    <select name="warehouse_id" class="form-input" required>
                                        <option value="">Select Warehouse</option>
                                        ${data.data.warehouses.map(warehouse => 
                                            `<option value="${warehouse.id}" ${warehouse.id == request.warehouse_id ? 'selected' : ''}>${warehouse.name}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Branch</label>
                                    ${data.data.can_select_branch ? `
                                        <select name="branch_id" class="form-input">
                                            <option value="">Select Branch (Optional)</option>
                                            ${data.data.branches ? data.data.branches.map(branch => 
                                                `<option value="${branch.id}" ${branch.id == request.branch_id ? 'selected' : ''}>${branch.name}</option>`
                                            ).join('') : ''}
                                        </select>
                                    ` : `
                                        <input type="hidden" name="branch_id" value="${request.branch_id || data.data.current_user_branch_id || ''}">
                                        <select class="form-input" disabled>
                                            ${data.data.branches ? data.data.branches.map(branch => 
                                                `<option value="${branch.id}" ${branch.id == (request.branch_id || data.data.current_user_branch_id) ? 'selected' : ''}>${branch.name}</option>`
                                            ).join('') : '<option value="">Branch User</option>'}
                                        </select>
                                    `}
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Request Date</label>
                                    <input type="date" name="request_date" class="form-input" value="${request.request_date || ''}" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Required Date</label>
                                    <input type="date" name="required_date" class="form-input" value="${request.required_date || ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Priority</label>
                                    <select name="priority" class="form-input" required>
                                        <option value="">Select Priority</option>
                                        <option value="low" ${request.priority == 'low' ? 'selected' : ''}>Low</option>
                                        <option value="medium" ${request.priority == 'medium' ? 'selected' : ''}>Medium</option>
                                        <option value="high" ${request.priority == 'high' ? 'selected' : ''}>High</option>
                                        <option value="urgent" ${request.priority == 'urgent' ? 'selected' : ''}>Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Reason</label>
                                <textarea name="reason" class="form-input form-textarea" required>${request.reason || ''}</textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-input form-textarea">${request.notes || ''}</textarea>
                            </div>
                        </div>
                    </form>
                `;
                
                // Add modal footer for edit modal
                document.getElementById('modalFooter').innerHTML = `
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" form="form" class="btn btn-primary">Update Request</button>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading form.</div>';
        });
}

function openViewModal(id) {
    openModal('View Inventory Request');
    document.getElementById('modalBody').innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 16px; color: #666;">Loading...</p>
        </div>
    `;
    
    fetch(`/warehouse/inventory-requests/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const request = data.data;
                document.getElementById('modalBody').innerHTML = `
                    <div class="space-y-4">
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Request Number</h3>
                            <p class="detail-value">${request.request_number || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Warehouse</h3>
                            <p class="detail-value">${request.warehouse_name || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Branch</h3>
                            <p class="detail-value">${request.branch_name || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Requested By</h3>
                            <p class="detail-value">${request.requested_by_name || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Request Date</h3>
                            <p class="detail-value">${request.request_date || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Required Date</h3>
                            <p class="detail-value">${request.required_date || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Priority</h3>
                            <p class="detail-value">${request.priority ? request.priority.charAt(0).toUpperCase() + request.priority.slice(1) : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Status</h3>
                            <p class="detail-value">${request.status ? request.status.charAt(0).toUpperCase() + request.status.slice(1) : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Reason</h3>
                            <p class="detail-value">${request.reason || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Notes</h3>
                            <p class="detail-value">${request.notes || 'N/A'}</p>
                        </div>
                        ${request.approved_by_name ? `
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Approved By</h3>
                            <p class="detail-value">${request.approved_by_name}</p>
                        </div>
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Approved At</h3>
                            <p class="detail-value">${request.approved_at || 'N/A'}</p>
                        </div>
                        ` : ''}
                        ${request.rejection_reason ? `
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Rejection Reason</h3>
                            <p class="detail-value">${request.rejection_reason}</p>
                        </div>
                        ` : ''}
                        ${request.completed_at ? `
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Completed At</h3>
                            <p class="detail-value">${request.completed_at}</p>
                        </div>
                        ` : ''}
                        ${request.processed_date ? `
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Processed Date</h3>
                            <p class="detail-value">${request.processed_date}</p>
                        </div>
                        ` : ''}
                        ${request.items && request.items.length > 0 ? `
                        <div class="detail-item">
                            <h3 class="font-semibold text-gray-900">Items (${request.total_items})</h3>
                            <div class="mt-2 space-y-2">
                                ${request.items.map(item => `
                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <p class="font-medium">${item.product_name || 'N/A'}</p>
                                        <p class="text-sm text-gray-600">Quantity: ${item.quantity || 'N/A'}</p>
                                        ${item.notes ? `<p class="text-sm text-gray-600">Notes: ${item.notes}</p>` : ''}
                                        <p class="text-sm text-gray-600">Status: ${item.status ? item.status.charAt(0).toUpperCase() + item.status.slice(1) : 'N/A'}</p>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
        });
}

// Form submission functions
function submitForm(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('/warehouse/inventory-requests', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(text);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            showSuccessModal('created', data.message || 'Inventory request created successfully');
            closeModal();
        } else {
            showErrorModal(data.message || 'Failed to create request');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Network error occurred: ' + error.message);
    });
}

function submitEditForm(event, id) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('_method', 'PUT');
    
    fetch(`/warehouse/inventory-requests/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(text);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            showSuccessModal('updated', data.message || 'Inventory request updated successfully');
            closeModal();
        } else {
            showErrorModal(data.message || 'Failed to update request');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Network error occurred: ' + error.message);
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this inventory request? This action can be undone later.'
        : `Are you sure you want to hide ${count} inventory requests? This action can be undone later.`;
    
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
    
    fetch('/warehouse/inventory-requests/bulk-delete', {
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
        showErrorModal('Terjadi kesalahan jaringan.');
    });
}

// Success Modal functions
function showSuccessModal(action, customMessage) {
    let message;
    
    if (customMessage) {
        message = customMessage;
    } else if (action === 'created') {
        message = 'Inventory request berhasil dibuat.';
    } else if (action === 'updated') {
        message = 'Inventory request berhasil diperbarui.';
    } else if (typeof action === 'number') {
        // For bulk delete (backward compatibility)
        message = action === 1 
            ? 'Inventory request berhasil disembunyikan.'
            : `${action} inventory request berhasil disembunyikan.`;
    } else {
        message = 'Operasi berhasil dilakukan.';
    }
    
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

function applyFilters() {
    const branchId = document.getElementById('filterBranch').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    
    const params = window.AromaTableState.paramsWithCurrentSort();
    if (branchId) params.append('branch_id', branchId);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    
    window.location.href = '{{ route("warehouse.inventory-requests.index") }}' + (params.toString() ? '?' + params.toString() : '');
}

// Error Modal functions
function showErrorModal(message) {
    document.getElementById('errorMessage').textContent = message || 'Inventory request gagal disembunyikan. Silakan coba lagi.';
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
        showWarningDialog('Pilih minimal satu inventory request yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

function deleteSingle(id) {
    selectedIdsForRetry = [id];
    openDeleteModal();
}

// Add/Remove Items Functions
function addItem() {
    const container = document.getElementById('itemsContainer');
    const itemIndex = window.itemCounter++;
    
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.style.cssText = 'margin-bottom: 16px;';
    
    const productOptions = window.productsData.map(product => {
        return `<option value="${product.id}">${product.name}</option>`;
    }).join('');
    
    newRow.innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr 120px 40px; gap: 12px; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <select name="items[${itemIndex}][master_product_id]" class="form-input" required style="width: 100%;">
                    <option value="">Select Product</option>
                    ${productOptions}
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <input type="number" name="items[${itemIndex}][quantity]" class="form-input" min="1" step="1" value="1" required style="width: 100%;">
            </div>
            <div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="removeItem(this)" style="width: 40px; height: 38px; padding: 0; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(newRow);
}

function removeItem(button) {
    const row = button.closest('.item-row');
    row.remove();
}

// Filter functions
function applyFilters() {
    const filterFromEl = document.getElementById('filterDateFrom');
    const filterToEl = document.getElementById('filterDateTo');
    
    // Get date values from data-date attribute (set by Flatpickr)
    const dateFrom = filterFromEl ? (filterFromEl.getAttribute('data-date') || filterFromEl.value || '') : '';
    const dateTo = filterToEl ? (filterToEl.getAttribute('data-date') || filterToEl.value || '') : '';
    
    // Use existing URLSearchParams to preserve column filters and sorting
    const params = new URLSearchParams(window.location.search);
    
    if (dateFrom) {
        params.set('date_from', dateFrom);
    } else {
        params.delete('date_from');
    }

    if (dateTo) {
        params.set('date_to', dateTo);
    } else {
        params.delete('date_to');
    }
    
    // Always reset to page 1 when changing filters
    params.set('page', '1');
    
    window.location.href = window.location.pathname + '?' + params.toString();
}

function resetFilters() {
    // Navigate to plain index to clear all filters
    window.location.href = window.location.pathname;
}

// Update filter date display
function updateFilterDateDisplay(type, dateValue) {
    if (dateValue) {
        const date = new Date(dateValue);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const day = date.getDate();
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        const formattedDate = `${day} ${month} ${year}`;
        
        if (type === 'from') {
            document.getElementById('filterDateFromDisplay').textContent = formattedDate;
        } else {
            document.getElementById('filterDateToDisplay').textContent = formattedDate;
        }
    }
}

// Format date display function for modal
function updateDateDisplay(input) {
    const dateValue = input.value;
    if (dateValue) {
        const date = new Date(dateValue);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const day = date.getDate();
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        const formattedDate = `${day} ${month} ${year}`;
        document.getElementById('requiredDateDisplay').value = formattedDate;
    } else {
        document.getElementById('requiredDateDisplay').value = '';
    }
}

// Format date for display (used in various places)
function formatDateDisplay(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const day = date.getDate();
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    return `${day} ${month} ${year}`;
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

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// Initialize Flatpickr for filter dates
document.addEventListener('DOMContentLoaded', function() {
    // Filter Date From
    const filterFromEl = document.getElementById('filterDateFrom');
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
    const filterToEl = document.getElementById('filterDateTo');
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

// Initialize Flatpickr for modal date input
function initModalDatePicker() {
    setTimeout(function() {
        const dateInput = document.getElementById('requiredDateInput');
        if (dateInput && !dateInput._flatpickr) {
            flatpickr(dateInput, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/M/Y',
                allowInput: false
            });
        }
    }, 100);
}
</script>
@endsection
