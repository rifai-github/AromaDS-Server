@extends('layouts.app')

@section('title', 'Stock Opnames')
@section('breadcrumb', 'Home / Warehouse / Stock Opnames')

@section('content')
@php
    $canCreateStockOpname = auth()->user()->hasPermission('warehouse.stock-opnames.create');
    $canDeleteStockOpname = auth()->user()->hasPermission('warehouse.stock-opnames.delete');
    $canApprove = auth()->user()->hasPermission('warehouse.stock-opnames.approve');
    $canUpdate = auth()->user()->hasPermission('warehouse.stock-opnames.update');
    $canViewSystemStock = auth()->user()->hasPermission('warehouse.stock-opnames.view-system-stock')
        || auth()->user()->hasRole('Admin')
        || auth()->user()->hasRole('super_admin')
        || auth()->user()->hasRoleStartingWith('Management');
@endphp
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
        min-width: 1000px;
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
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 120px; min-width: 120px; }
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
    
    .status-draft {
        background-color: #f3f4f6;
        color: #6b7280;
    }
    
    .status-in-progress {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-completed {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-approved {
        background-color: #dbeafe;
        color: #1e40af;
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
        
        <!-- Stock Opnames Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Stock Opnames</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                @if($canCreateStockOpname)
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Opname</span>
                    <span class="md:hidden">Add</span>
                </button>
                @endif
            </div>
        </div>

        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row justify-start items-center w-full">
                @if($canDeleteStockOpname)
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
                @endif
            </div>
            
        </div>

        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        @if($canDeleteStockOpname)
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        @endif
                        <th data-column="opname_no">Opname Number</th>
                        <th data-column="branch__name">Branch</th>
                        <th data-column="warehouse__name">Warehouse</th>
                        <th data-column="personResponsible__name">Person Responsible</th>
                        <th data-column="opname_date" data-type="date">Opname Date</th>
                        <th data-column="status">Status</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="createdBy.name">Created By</th>
                        <th data-column="updated_at" data-type="date">Updated At</th>
                        <th data-column="updatedBy.name">Updated By</th>
                        <th data-no-filter>Actions</th>
                    </tr>
                </thead>
                <!-- Table Body -->
                <tbody>
                    @forelse($stockOpnames ?? [] as $opname)
                    <tr data-id="{{ $opname->id }}" onclick="window.location.href='{{ route('warehouse.stock-opnames.show', $opname->id) }}'" class="cursor-pointer hover:bg-gray-50">
                        @if($canDeleteStockOpname)
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $opname->id }}" onclick="event.stopPropagation()">
                        </td>
                        @endif
                        <td class="font-medium">{{ $opname->opname_no ?? '-' }}</td>
                        <td>{{ $opname->branch->name ?? '-' }}</td>
                        <td>{{ $opname->warehouse->name ?? '-' }}</td>
                        <td>{{ $opname->personResponsible->name ?? '-' }}</td>
                        <td>{{ $opname->opname_date ? \Carbon\Carbon::parse($opname->opname_date)->format('d/M/Y') : '-' }}</td>
                        <td>
                            <span class="status-badge status-{{ str_replace(' ', '-', strtolower($opname->status ?? 'draft')) }}">
                                {{ ucfirst($opname->status ?? 'Draft') }}
                            </span>
                        </td>
                        <td>{{ $opname->created_at ? \Carbon\Carbon::parse($opname->created_at)->format('d/M/Y H:i') : '-' }}</td>
                        <td>{{ $opname->createdBy->name ?? '-' }}</td>
                        <td>{{ $opname->updated_at ? \Carbon\Carbon::parse($opname->updated_at)->format('d/M/Y H:i') : '-' }}</td>
                        <td>{{ $opname->updatedBy->name ?? '-' }}</td>
                        <td onclick="event.stopPropagation()" class="text-center" style="white-space:nowrap;">
                            @if($canUpdate && in_array($opname->status, ['draft', 'in-progress', 'completed']))
                            <button type="button"
                                class="btn btn-primary btn-sm"
                                style="font-size:11px;padding:3px 8px;margin:1px;"
                                onclick="event.stopPropagation(); submitForApproval({{ $opname->id }})">
                                Get Approved
                            </button>
                            @endif
                            @if($canApprove && $opname->status === 'waiting for approval')
                            <button type="button"
                                class="btn btn-success btn-sm"
                                style="font-size:11px;padding:3px 8px;margin:1px;"
                                onclick="event.stopPropagation(); postOpname({{ $opname->id }})">
                                Post
                            </button>
                            @endif
                            @if($canApprove && $opname->status === 'approved')
                            <button type="button"
                                class="btn btn-warning btn-sm"
                                style="font-size:11px;padding:3px 8px;margin:1px;"
                                onclick="event.stopPropagation(); unpostOpname({{ $opname->id }})">
                                Unpost
                            </button>
                            @endif
                            @if($canDeleteStockOpname)
                            <button type="button"
                                class="btn btn-danger btn-sm"
                                style="font-size:11px;padding:3px 8px;margin:1px;"
                                onclick="event.stopPropagation(); openDeleteModal({{ $opname->id }})">
                                Delete
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $canDeleteStockOpname ? 13 : 12 }}" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-inbox text-4xl mb-3"></i>
                                <p class="text-lg">No stock opnames found</p>
                                <button class="btn btn-primary mt-2" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Add First Opname
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($stockOpnames->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $stockOpnames->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Stock Opname</h2>
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
        <h3 class="delete-modal-title">Sembunyikan Stock Opname</h3>
        <p class="delete-modal-description" id="deleteMessage">Apakah kamu yakin ingin menyembunyikan stock opname ini? Tindakan ini masih bisa dibatalkan nanti.</p>
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
        <p class="delete-modal-description" id="errorMessage">Stock opname belum berhasil disembunyikan. Silakan coba lagi.</p>
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
        <p class="delete-modal-description" id="successMessage">Stock opname berhasil disembunyikan.</p>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;
const canDeleteStockOpname = @json($canDeleteStockOpname);

// Select All functionality
const selectAll = document.getElementById('selectAll');
if (selectAll) {
    selectAll.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        const headerSelectAll = document.getElementById('headerSelectAll');
        if (headerSelectAll) {
            headerSelectAll.checked = this.checked;
        }
    });
}

const headerSelectAll = document.getElementById('headerSelectAll');
if (headerSelectAll) {
    headerSelectAll.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.checked = this.checked;
        }
    });
}

// Individual checkbox functionality
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('row-checkbox')) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        const headerSelectAllCheckbox = document.getElementById('headerSelectAll');
        if (!selectAllCheckbox || !headerSelectAllCheckbox) {
            return;
        }
        
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
    openModal('Create New Stock Opname');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <input type="hidden" name="status" value="draft">
            <div class="modal-section">
                <div class="modal-section-title">Stock Opname Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Warehouse *</label>
                        <select name="warehouse_id" id="warehouse_select" class="form-input" required onchange="lockWarehouseIfManager()">
                            <option value="">Select Warehouse</option>
                            <!-- Warehouses will be loaded here -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Opname Date *</label>
                        <input type="date" name="opname_date" class="form-input" value="${new Date().toISOString().split('T')[0]}" required>
                    </div>
                </div>
                <div class="form-group mt-4">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input form-textarea" placeholder="Pesan atau catatan tambahan..."></textarea>
                </div>
            </div>
            <p class="text-sm text-gray-500 italic mt-4">* Produk akan diinisialisasi otomatis dari stok gudang saat ini.</p>
        </form>
    `;
    
    loadWarehouses();
    
    // Add modal footer
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" form="form" class="btn btn-primary">Create Opname & Init Products</button>
    `;
}

function lockWarehouseIfManager() {
    // Logic to lock warehouse if user has specific role can be added here
    // For now we load all then check
}

function openViewModal(id) {
    openModal('View Stock Opname');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/warehouse/stock-opnames/${id}`, {
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
            const items = data.data.stock_opname_details || [];
            const isAdmin = {{ auth()->user()->canViewAllData() ? 'true' : 'false' }};
            const canViewSystemStockPermission = {{ $canViewSystemStock ? 'true' : 'false' }};
            const status = data.data.status;
            const canEdit = status === 'draft' || status === 'in-progress';
            const canViewSystemStock = canViewSystemStockPermission;

            let itemsHtml = `
                <div class="modal-section mt-6">
                    <div class="modal-section-title">Stock Details</div>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="border p-2 text-left">Product Code</th>
                                    <th class="border p-2 text-left">Product Name</th>
                                    ${canViewSystemStock ? '<th class="border p-2 text-right">System Stock</th>' : ''}
                                    <th class="border p-2 text-right">Physical Stock</th>
                                    ${canViewSystemStock ? '<th class="border p-2 text-right">Variance</th>' : ''}
                                    <th class="border p-2 text-center" style="width: 50px;"><i class="fas fa-qrcode"></i></th>
                                    <th class="border p-2 text-left">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${items.map(item => {
                                    const hasVariance = item.variance != 0;
                                    const rowClass = hasVariance && canViewSystemStock ? 'bg-red-50' : '';
                                    return `
                                        <tr class="${rowClass}">
                                            <td class="border p-2">${item.master_product?.sku || '-'}</td>
                                            <td class="border p-2">${item.master_product?.name || '-'}</td>
                                            ${canViewSystemStock ? `<td class="border p-2 text-right">${item.system_stock}</td>` : ''}
                                            <td class="border p-2 text-right">
                                                ${canEdit ? 
                                                    `<input type="number" class="w-24 border rounded px-2 py-1 text-right" 
                                                        value="${item.physical_stock ?? ''}" 
                                                        onchange="updateOpnameDetail(${item.id}, this.value)">` : 
                                                    (item.physical_stock ?? '-')
                                                }
                                            </td>
                                            ${canViewSystemStock ? 
                                                `<td class="border p-2 text-right font-bold ${hasVariance ? 'text-red-600' : 'text-green-600'}" id="variance-${item.id}">
                                                    ${item.variance}
                                                </td>` : ''
                                            }
                                            <td class="border p-2 text-center text-gray-400">
                                                <i class="fas fa-camera cursor-pointer hover:text-blue-500" title="QR Scan (Optional)"></i>
                                            </td>
                                            <td class="border p-2">
                                                ${canEdit ? 
                                                    `<input type="text" class="w-full border rounded px-2 py-1" 
                                                        value="${item.notes ?? ''}" 
                                                        onchange="updateOpnameDetailNotes(${item.id}, this.value)">` : 
                                                    (item.notes ?? '-')
                                                }
                                            </td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Opname Number</label>
                            <p class="detail-value">${data.data.opname_no || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Warehouse</label>
                            <p class="detail-value">${data.data.warehouse?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value">
                                <span class="status-badge status-${data.data.status?.replace(/ /g, '-') || 'draft'}">
                                    ${data.data.status_text || data.data.status.toUpperCase()}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Opname Date</label>
                            <p class="detail-value">${data.data.opname_date ? new Date(data.data.opname_date).toLocaleDateString('id-ID') : '-'}</p>
                        </div>
                    </div>
                </div>
                ${itemsHtml}
            `;
        
            let footerHtml = `<button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>`;
            
            if (canEdit) {
                footerHtml += `<button type="button" class="btn btn-primary" onclick="submitForApproval(${id})">Submit for Approval</button>`;
            }

            if (status === 'waiting for approval' && isApprover) {
                footerHtml += `<button type="button" class="btn btn-success" onclick="approveOpname(${id})">Approve & Finalize</button>`;
            }

            if (status === 'approved' && isApprover) {
                footerHtml += `<button type="button" class="btn btn-primary" onclick="createStockAdjustment(${id})">Create Stock Adjustment</button>`;
            }

            document.getElementById('modalFooter').innerHTML = footerHtml;
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
    openModal('Edit Stock Opname');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/warehouse/stock-opnames/${id}/edit`, {
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
                        <div class="modal-section-title">Basic Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Opname Number</label>
                                <input type="text" name="opname_no" class="form-input" value="${data.data.opname_no || ''}" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Branch</label>
                                <select name="branch_id" class="form-input">
                                    <option value="">Select Branch</option>
                                    <option value="${data.data.branch_id}" selected>${data.data.branch?.name || 'Current Branch'}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Warehouse *</label>
                                <select name="warehouse_id" class="form-input" required>
                                    <option value="">Select Warehouse</option>
                                    <option value="${data.data.warehouse_id}" selected>${data.data.warehouse?.name || 'Current Warehouse'}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Person Responsible</label>
                                <select name="person_responsible" class="form-input">
                                    <option value="">Select Person</option>
                                    <option value="${data.data.person_responsible}" selected>${data.data.person_responsible?.name || 'Current Person'}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Opname Date *</label>
                                <input type="date" name="opname_date" class="form-input" value="${data.data.opname_date ? new Date(data.data.opname_date).toISOString().slice(0, 10) : ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-input">
                                    <option value="draft" ${data.data.status === 'draft' ? 'selected' : ''}>Draft</option>
                                    <option value="in-progress" ${data.data.status === 'in-progress' ? 'selected' : ''}>In Progress</option>
                                    <option value="completed" ${data.data.status === 'completed' ? 'selected' : ''}>Completed</option>
                                    <option value="approved" ${data.data.status === 'approved' ? 'selected' : ''}>Approved</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-input form-textarea" placeholder="Enter notes...">${data.data.notes || ''}</textarea>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Schedule Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Start Date</label>
                                <input type="datetime-local" name="started_at" class="form-input" value="${data.data.started_at ? new Date(data.data.started_at).toISOString().slice(0, 16) : ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Date</label>
                                <input type="datetime-local" name="completed_at" class="form-input" value="${data.data.completed_at ? new Date(data.data.completed_at).toISOString().slice(0, 16) : ''}">
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Opname</button>
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

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/warehouse/stock-opnames/${id}` : '/warehouse/stock-opnames';
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

// Delete Modal functions
function openDeleteModal(id = null) {
    if (id) {
        selectedIdsForRetry = [id];
    }
    
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Apakah Anda yakin ingin menyembunyikan stock opname ini? Tindakan ini masih bisa dibatalkan nanti.'
        : `Apakah Anda yakin ingin menyembunyikan ${count} stock opname? Tindakan ini masih bisa dibatalkan nanti.`;
    
    document.getElementById('deleteMessage').textContent = message;
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

async function parseJsonResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        return response.json();
    }

    const text = await response.text();
    if (response.status === 403) {
        return { success: false, message: 'Anda tidak memiliki akses untuk menghapus/menyembunyikan stock opname.' };
    }
    if (response.status === 419) {
        return { success: false, message: 'Sesi halaman sudah berakhir. Silakan refresh halaman lalu coba lagi.' };
    }

    return {
        success: false,
        message: text ? `Stock opname belum berhasil disembunyikan. Server mengembalikan status ${response.status}.` : 'Stock opname belum berhasil disembunyikan.'
    };
}

function confirmDelete() {
    closeDeleteModal();

    if (!canDeleteStockOpname) {
        showErrorModal('Anda tidak memiliki akses untuk menghapus/menyembunyikan stock opname.');
        return;
    }
    
    fetch('/warehouse/stock-opnames/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => parseJsonResponse(response))
    .then(result => {
        if (result.success) {
            showSuccessModal(result.count);
        } else {
            showErrorModal(result.message || 'Stock opname belum berhasil disembunyikan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Koneksi ke server bermasalah atau response tidak bisa dibaca. Silakan refresh halaman lalu coba lagi.');
    });
}

// Bulk operations
function deleteSelected() {
    if (!canDeleteStockOpname) {
        showErrorModal('Anda tidak memiliki akses untuk menghapus/menyembunyikan stock opname.');
        return;
    }

    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu stock opname yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'Stock opname berhasil disembunyikan.'
        : `${count} stock opname berhasil disembunyikan.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'Stock opname tidak berhasil disembunyikan. Silakan coba lagi.';
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function updateOpnameDetail(detailId, value) {
    const varianceEl = document.getElementById(`variance-${detailId}`);
    if (varianceEl) {
        varianceEl.textContent = '...';
    }

    fetch(`/warehouse/stock-opnames/details/${detailId}/update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ physical_stock: value })
    })
    .then(response => response.json())
    .then(result => {
        if (result.status !== 'success') {
            showErrorDialog('Gagal', 'Data tidak berhasil diperbarui: ' + result.message);
            return;
        }

        if (varianceEl && result.data?.variance !== null && result.data?.variance !== undefined) {
            const variance = result.data.variance;
            varianceEl.textContent = variance;
            varianceEl.className = `border p-2 text-right font-bold ${variance != 0 ? 'text-red-600' : 'text-green-600'}`;
            varianceEl.closest('tr').className = (variance != 0) ? 'bg-red-50' : '';
        }
    });
}

function updateOpnameDetailNotes(detailId, notes) {
    fetch(`/warehouse/stock-opnames/details/${detailId}/update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ notes: notes })
    })
    .then(response => response.json());
}

function stockOpnameActionUrl(template, id) {
    return template.replace('__ID__', id);
}

async function parseStockOpnameActionResponse(response, fallbackMessage) {
    const contentType = response.headers.get('content-type') || '';
    let result = null;

    if (contentType.includes('application/json')) {
        result = await response.json();
    } else {
        const text = await response.text();
        result = {
            status: 'error',
            message: text || fallbackMessage
        };
    }

    if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || fallbackMessage);
    }

    return result;
}

function submitForApproval(id) {
    confirmStockOpnameAction(
        'Ajukan untuk Persetujuan?',
        'Setelah diajukan, item opname tidak bisa diedit lagi.',
        'Ya, ajukan',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;

        fetch(stockOpnameActionUrl(@json(route('warehouse.stock-opnames.submit', ['stockOpname' => '__ID__'])), id), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                location.reload();
            } else {
                showErrorDialog('Gagal', result.message);
            }
        });
    });
}

function postOpname(id) {
    confirmStockOpnameAction(
        'Post Stock Opname?',
        'Stock opname akan diposting dan distok akan difinalkan.',
        'Ya, Post',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;

        fetch(stockOpnameActionUrl(@json(route('warehouse.stock-opnames.approve', ['stockOpname' => '__ID__'])), id), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                location.reload();
            } else {
                showErrorDialog('Gagal', result.message);
            }
        });
    });
}

function unpostOpname(id) {
    confirmStockOpnameAction(
        'Unpost Stock Opname?',
        'Status opname akan dikembalikan ke Draft.',
        'Ya, Unpost',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;

        fetch(stockOpnameActionUrl(@json(route('warehouse.stock-opnames.unpost', ['stockOpname' => '__ID__'])), id), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => parseStockOpnameActionResponse(response, 'Stock opname tidak berhasil di-unpost.'))
        .then(result => {
            location.reload();
        })
        .catch(error => {
            showErrorDialog(error.message || 'Stock opname tidak berhasil di-unpost.', 'Gagal');
        });
    });
}

function approveOpname(id) {
    confirmStockOpnameAction(
        'Setujui Stock Opname?',
        'Tindakan ini akan memfinalkan stok.',
        'Ya, setujui',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;

        fetch(stockOpnameActionUrl(@json(route('warehouse.stock-opnames.approve', ['stockOpname' => '__ID__'])), id), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                location.reload();
            } else {
                showErrorDialog('Gagal', result.message);
            }
        });
    });
}

const pendingStockAdjustmentRequests = new Set();

function createStockAdjustment(id) {
    if (pendingStockAdjustmentRequests.has(id)) {
        return;
    }

    pendingStockAdjustmentRequests.add(id);
    confirmStockOpnameAction(
        'Buat Stock Adjustment?',
        'Item yang memiliki selisih stok akan ditambahkan otomatis.',
        'Ya, buat',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) {
            pendingStockAdjustmentRequests.delete(id);
            return;
        }

        fetch(stockOpnameActionUrl(@json(route('warehouse.stock-opnames.create-adjustment', ['stockOpname' => '__ID__'])), id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                showSuccessDialog('Berhasil', 'Stock Adjustment berhasil dibuat. Anda akan diarahkan ke halaman stock adjustment.');
                window.location.href = '/warehouse/stock-adjustments';
            } else {
                showErrorDialog('Gagal', result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan saat membuat stock adjustment.');
        })
        .finally(() => {
            pendingStockAdjustmentRequests.delete(id);
        });
    });
}

function confirmStockOpnameAction(title, text, confirmButtonText, cancelButtonText) {
    return showConfirmDialog({
        title,
        text,
        confirmButtonText,
        cancelButtonText
    }).then((result) => result === true || result?.isConfirmed === true);
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

// Dynamic loading functions
function loadBranches() {
    fetch('/api/branches')
        .then(response => response.json())
        .then(data => {
            const select = document.querySelector('select[name="branch_id"]');
            if (select && data) {
                // BranchController returns data directly
                data.forEach(branch => {
                    const option = document.createElement('option');
                    option.value = branch.id;
                    option.textContent = branch.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading branches:', error));
}

function loadWarehouses() {
    fetch('/api/warehouses')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('warehouse_select');
            if (select && data.data) {
                const managedWarehouseId = {{ $managedWarehouse ? $managedWarehouse->id : 'null' }};
                
                // Clear existing options except default
                select.innerHTML = '<option value="">Select Warehouse</option>';
                
                // Auto-select manager's warehouse
                
                data.data.forEach(warehouse => {
                    const option = document.createElement('option');
                    option.value = warehouse.id;
                    option.textContent = warehouse.name;
                    if (managedWarehouseId && warehouse.id == managedWarehouseId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });

                // If specialized manager, lock it
                if (managedWarehouseId) {
                    select.disabled = true;
                    // Remove existing hidden input if any
                    const existingHidden = select.parentNode.querySelector('input[name="warehouse_id"]');
                    if(existingHidden) existingHidden.remove();
                    
                    // Add hidden input because disabled select doesn't submit
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'warehouse_id';
                    hiddenInput.value = managedWarehouseId;
                    select.parentNode.appendChild(hiddenInput);
                }
            }
        })
        .catch(error => console.error('Error loading warehouses:', error));
}

function loadUsers() {
    fetch('/api/users')
        .then(response => response.json())
        .then(data => {
            const select = document.querySelector('select[name="person_responsible"]');
            if (select && data.data) {
                data.data.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading users:', error));
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
</script>
@endsection
