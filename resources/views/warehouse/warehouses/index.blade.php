@extends('layouts.app')

@section('title', 'Master Warehouse')
@section('breadcrumb', 'Home / Warehouse / Master Warehouse')

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
.responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 150px; min-width: 150px; }
.responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 200px; min-width: 200px; }
.responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 150px; min-width: 150px; }
.responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 100px; min-width: 100px; }
.responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 200px; min-width: 200px; }
.responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 150px; min-width: 150px; }
.responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 100px; min-width: 100px; }
.responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 100px; min-width: 100px; }
.responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 200px; min-width: 200px; }
.responsive-table th:nth-child(11), .responsive-table td:nth-child(11) { width: 150px; min-width: 150px; }

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
        max-height: calc(95vh - 140px);
    }
    
    /* Stock table responsive */
    .stock-table-container {
        max-width: 100%;
        overflow-x: auto;
    }
    
    .stock-table {
        min-width: 600px;
        width: 100%;
    }
    
    .stock-table th,
    .stock-table td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px;
    }
    
    .stock-table th:first-child,
    .stock-table td:first-child {
        max-width: 200px;
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
    font-size: 14px;
    color: #374151;
    margin: 0;
    padding: 8px 0;
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
    gap: 2rem;
}

.gap-4 {
    gap: 1.5rem;
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
    
    /* Modal responsive */
    .modal-container {
        width: 95vw;
        max-width: 95vw;
        margin: 20px;
    }
    
    .modal-body {
        padding: 15px;
    }
    
    .modal-footer {
        padding: 15px;
        flex-direction: column;
        gap: 12px;
    }
    
    .grid-cols-1.md\:grid-cols-2 {
        grid-template-columns: 1fr;
    }
    
    .gap-6 {
        gap: 1rem;
    }
    
    .modal-section {
        padding: 15px;
        margin-bottom: 15px;
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
    
    /* Modal tablet responsive */
    .modal-container {
        width: 90vw;
        max-width: 90vw;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .gap-6 {
        gap: 1.5rem;
    }
    
    .modal-section {
        padding: 18px;
    }
}
</style>

<div class="flex flex-col   w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Master Warehouse Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Warehouse</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <!-- Status Filter -->
                <x-filter-status />
                
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Warehouse</span>
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
                        <th data-column="warehouse_code">Warehouse Code</th>
                        <th data-column="name">Warehouse Name</th>
                        <th data-column="branch__name">Branch</th>
                        <th data-column="warehouseType__name">Type</th>
                        <th data-column="address">Address</th>
                        <th data-column="manager">Manager</th>
                        <th data-column="is_center">Center</th>
                        <th data-column="is_active">Status</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($warehouses as $warehouse)
                    <tr data-id="{{ $warehouse->id }}" onclick="window.location.href='{{ route('warehouse.warehouses.show', $warehouse->id) }}'">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $warehouse->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $warehouse->warehouse_code ?: '-' }}</td>
                        <td>{{ $warehouse->name ?: '-' }}</td>
                        <td>{{ $warehouse->branch->name ?? '-' }}</td>
                        <td>
                            @if($warehouse->warehouseType)
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                    {{ $warehouse->warehouseType->name }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $warehouse->address ?: '-' }}</td>
                        <td>{{ $warehouse->managerUser->name ?? '-' }}</td>
                        <td>
                            @if($warehouse->is_center)
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    Center
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                    Branch
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $warehouse->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $warehouse->createdBy->name ?? '-' }}</td>
                        <td>
                            @if($warehouse->created_at)
                                {{ \Carbon\Carbon::parse($warehouse->created_at)->format('d M Y') }}<br>
                                at {{ \Carbon\Carbon::parse($warehouse->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $warehouse->updatedBy->name ?? '-' }}</td>
                        <td>
                            @if($warehouse->updated_at)
                                {{ \Carbon\Carbon::parse($warehouse->updated_at)->format('d M Y') }}<br>
                                at {{ \Carbon\Carbon::parse($warehouse->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No warehouses found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if($warehouses->currentPage() > 1)
                    <a href="{{ $warehouses->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if($warehouses->hasPages())
                    @php
                        $start = max(1, $warehouses->currentPage() - 2);
                        $end = min($warehouses->lastPage(), $warehouses->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $warehouses->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $warehouses->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $warehouses->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $warehouses->lastPage())
                            @if($end < $warehouses->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $warehouses->url($warehouses->lastPage()) }}" class="page-number">{{ $warehouses->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if($warehouses->hasMorePages())
                    <a href="{{ $warehouses->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $warehouses->currentPage() ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $warehouses->lastPage() ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Warehouse</h2>
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
            
        <h3 class="delete-modal-title">Nonaktifkan Warehouse</h3>
        <p class="delete-modal-description" id="deleteMessage">Apakah kamu yakin ingin menonaktifkan warehouse ini? Statusnya akan dipindahkan menjadi tidak aktif.</p>
            <div class="delete-modal-buttons">
                <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
                <button class="btn btn-hide" onclick="confirmDelete()">Ya, Nonaktifkan</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
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
            
        <h3 class="delete-modal-title">Ups... Terjadi Kendala</h3>
        <p class="delete-modal-description" id="errorMessage">Warehouse belum berhasil dinonaktifkan. Silakan coba lagi.</p>
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
            <svg class="delete-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
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
            
        <h3 class="delete-modal-title">Berhasil</h3>
        <p class="delete-modal-description" id="successMessage">Warehouse berhasil dinonaktifkan.</p>
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

// CRUD Modal functions
function openCreateModal() {
    openModal('Create New Warehouse');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    // Load branches data
    fetch('/warehouse/warehouses/create', {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(result => {
            const branches = result.data.branches;
            const warehouseTypes = result.data.warehouse_types;
            const users = result.data.users || [];
            const hasCenterWarehouse = result.data.has_center_warehouse;
            
            document.getElementById('modalBody').innerHTML = `
                <form id="form" onsubmit="submitForm(event)">
                    <div class="modal-section">
                        <div class="modal-section-title">Warehouse Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Warehouse Code *</label>
                                <input type="text" name="warehouse_code" class="form-input" placeholder="Enter warehouse code" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Warehouse Name *</label>
                                <input type="text" name="name" class="form-input" placeholder="Enter warehouse name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Branch *</label>
                                <select name="branch_id" class="form-input" required>
                                    <option value="">Select Branch</option>
                                    ${branches.map(branch => `<option value="${branch.id}">${branch.name}</option>`).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Warehouse Type *</label>
                                <select name="warehouse_type_id" class="form-input" required>
                                    <option value="">Select Type</option>
                                    ${warehouseTypes.map(type => `<option value="${type.id}">${type.name}</option>`).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Manager</label>
                                <select name="manager" class="form-input">
                                    <option value="">Select Manager</option>
                                    ${users.map(user => `<option value="${user.id}">${user.name}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Warehouse Details</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Center Warehouse</label>
                                ${hasCenterWarehouse ? 
                                    '<div class="p-3 bg-gray-100 rounded-lg text-gray-600 text-sm">Center warehouse already exists</div><input type="hidden" name="is_center" value="0">' :
                                    '<select name="is_center" class="form-input" required><option value="0" selected>No</option><option value="1">Yes</option></select>'
                                }
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-input" required>
                                    <option value="1" selected>Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-input" placeholder="Enter phone number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Location Information</div>
                        <div class="grid grid-cols-1 gap-6">
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-input form-textarea" placeholder="Enter warehouse address"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" form="form" class="btn btn-primary">Create Warehouse</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Gagal memuat form.</div>';
        });
}

function openViewModal(id) {
    openModal('View Warehouse');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/warehouse/warehouses/${id}`, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(result => {
            const data = result.data;
            const warehouseProducts = data.warehouse_products || [];
            const totalProducts = warehouseProducts.length;
            const totalStock = warehouseProducts.reduce((sum, wp) => sum + (parseInt(wp.quantity) || 0), 0);
            const avgStock = totalProducts > 0 ? Math.round(totalStock / totalProducts) : 0;
            
            // Build stock table
            let stockTable = '';
            if (warehouseProducts.length > 0) {
                stockTable = `
                    <div class="stock-table-container">
                        <table class="stock-table border-collapse border border-gray-200 text-sm">
                            <thead class="sticky top-0 bg-gray-50">
                                <tr>
                                    <th class="border border-gray-200 px-3 py-2 text-left font-medium text-gray-700">Product</th>
                                    <th class="border border-gray-200 px-3 py-2 text-left font-medium text-gray-700">Category</th>
                                    <th class="border border-gray-200 px-3 py-2 text-left font-medium text-gray-700">Package Size</th>
                                    <th class="border border-gray-200 px-3 py-2 text-center font-medium text-gray-700">Stock</th>
                                    <th class="border border-gray-200 px-3 py-2 text-center font-medium text-gray-700">Min</th>
                                    <th class="border border-gray-200 px-3 py-2 text-center font-medium text-gray-700">Max</th>
                                    <th class="border border-gray-200 px-3 py-2 text-center font-medium text-gray-700">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                ${warehouseProducts.map(wp => {
                                    const quantity = parseInt(wp.quantity) || 0;
                                    const minStock = parseInt(wp.minimum_stock) || 0;
                                    const maxStock = parseInt(wp.maximum_stock) || 0;
                                    
                                    const isLowStock = quantity <= minStock;
                                    const isOverStock = quantity >= maxStock;
                                    let statusClass = 'bg-green-100 text-green-800';
                                    let statusText = 'Normal';
                                    
                                    if (isLowStock && minStock > 0) {
                                        statusClass = 'bg-red-100 text-red-800';
                                        statusText = 'Low';
                                    } else if (isOverStock && maxStock > 0) {
                                        statusClass = 'bg-yellow-100 text-yellow-800';
                                        statusText = 'Over';
                                    }
                                    
                                    return `
                                        <tr class="hover:bg-gray-50">
                                            <td class="border border-gray-200 px-3 py-2 text-gray-900 font-medium" title="${wp.master_product?.name || '-'}">${wp.master_product?.name || '-'}</td>
                                            <td class="border border-gray-200 px-3 py-2 text-gray-600" title="${wp.master_product?.product_category?.name || '-'}">${wp.master_product?.product_category?.name || '-'}</td>
                                            <td class="border border-gray-200 px-3 py-2 text-gray-600" title="${wp.master_product?.packaging_size?.name || '-'}">${wp.master_product?.packaging_size?.name || '-'}</td>
                                            <td class="border border-gray-200 px-3 py-2 text-center font-semibold text-gray-900">${quantity.toLocaleString()}</td>
                                            <td class="border border-gray-200 px-3 py-2 text-center text-gray-600">${minStock.toLocaleString()}</td>
                                            <td class="border border-gray-200 px-3 py-2 text-center text-gray-600">${maxStock.toLocaleString()}</td>
                                            <td class="border border-gray-200 px-3 py-2 text-center">
                                                <span class="px-2 py-1 text-xs rounded-full ${statusClass}">${statusText}</span>
                                            </td>
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                stockTable = `
                    <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                        <i class="fas fa-box-open text-5xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-600 mb-2">No Products Found</h3>
                        <p class="text-gray-500">This warehouse doesn't have any products yet.</p>
                    </div>
                `;
            }
            
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Warehouse Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Warehouse Code</label>
                            <p class="detail-value">${data.warehouse_code || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Warehouse Name</label>
                            <p class="detail-value">${data.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Branch</label>
                            <p class="detail-value">${data.branch?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Warehouse Type</label>
                            <p class="detail-value">${data.warehouse_type?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Manager</label>
                            <p class="detail-value">${data.manager_user ? data.manager_user.name : (data.manager || '-')}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Warehouse Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Center Warehouse</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full ${data.is_center ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}">
                                    ${data.is_center ? 'Center' : 'Branch'}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full ${data.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${data.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Phone</label>
                            <p class="detail-value">${data.phone || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Location Information</div>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Address</label>
                            <p class="detail-value">${data.address || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Stock Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-xl border border-blue-200">
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-500 rounded-full mb-3">
                                    <i class="fas fa-boxes text-white text-lg"></i>
                                </div>
                                <p class="text-3xl font-bold text-blue-800 mb-1">${totalProducts}</p>
                                <p class="text-sm font-medium text-blue-600">Total Products</p>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-xl border border-green-200">
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 bg-green-500 rounded-full mb-3">
                                    <i class="fas fa-cubes text-white text-lg"></i>
                                </div>
                                <p class="text-3xl font-bold text-green-800 mb-1">${totalStock.toLocaleString()}</p>
                                <p class="text-sm font-medium text-green-600">Total Stock</p>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-xl border border-purple-200">
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-500 rounded-full mb-3">
                                    <i class="fas fa-chart-line text-white text-lg"></i>
                                </div>
                                <p class="text-3xl font-bold text-purple-800 mb-1">${avgStock.toLocaleString()}</p>
                                <p class="text-sm font-medium text-purple-600">Avg Stock/Product</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-list-ul text-gray-600 mr-2"></i>
                                Product Stock Details
                            </h4>
                        </div>
                        <div class="p-0">
                            ${stockTable}
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal footer for view modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit Warehouse</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Gagal memuat detail.</div>';
        });
}

function openEditModal(id) {
    openModal('Edit Warehouse');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/warehouse/warehouses/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(result => {
            const data = result.data;
            const warehouse = data.warehouse;
            const branches = data.branches;
            const warehouseTypes = data.warehouse_types;
            const users = data.users || [];
            const hasCenterWarehouse = data.has_center_warehouse;
            
            document.getElementById('modalBody').innerHTML = `
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <div class="modal-section">
                        <div class="modal-section-title">Warehouse Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Warehouse Code *</label>
                                <input type="text" name="warehouse_code" class="form-input" value="${warehouse.warehouse_code || ''}" placeholder="Enter warehouse code" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Warehouse Name *</label>
                                <input type="text" name="name" class="form-input" value="${warehouse.name || ''}" placeholder="Enter warehouse name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Branch *</label>
                                <select name="branch_id" class="form-input" required>
                                    <option value="">Select Branch</option>
                                    ${branches.map(branch => `<option value="${branch.id}" ${branch.id == warehouse.branch_id ? 'selected' : ''}>${branch.name}</option>`).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Warehouse Type *</label>
                                <select name="warehouse_type_id" class="form-input" required>
                                    <option value="">Select Type</option>
                                    ${warehouseTypes.map(type => `<option value="${type.id}" ${type.id == warehouse.warehouse_type_id ? 'selected' : ''}>${type.name}</option>`).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Manager</label>
                                <select name="manager" class="form-input">
                                    <option value="">Select Manager</option>
                                    ${users.map(user => `<option value="${user.id}" ${user.id == warehouse.manager ? 'selected' : ''}>${user.name}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Warehouse Details</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Center Warehouse</label>
                                ${hasCenterWarehouse && !warehouse.is_center ? 
                                    '<div class="p-3 bg-gray-100 rounded-lg text-gray-600 text-sm">Center warehouse already exists</div><input type="hidden" name="is_center" value="0">' :
                                    `<select name="is_center" class="form-input" required>
                                        <option value="0" ${!warehouse.is_center ? 'selected' : ''}>No</option>
                                        <option value="1" ${warehouse.is_center ? 'selected' : ''}>Yes</option>
                                    </select>`
                                }
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-input" required>
                                    <option value="1" ${warehouse.is_active ? 'selected' : ''}>Active</option>
                                    <option value="0" ${!warehouse.is_active ? 'selected' : ''}>Inactive</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-input" value="${warehouse.phone || ''}" placeholder="Enter phone number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Location Information</div>
                        <div class="grid grid-cols-1 gap-6">
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-input form-textarea" placeholder="Enter warehouse address">${warehouse.address || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" form="form" class="btn btn-primary">Update Warehouse</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Gagal memuat form.</div>';
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    // Convert boolean fields
    if (data.is_active !== undefined) {
        data.is_active = data.is_active === '1' || data.is_active === 'true';
    }
    if (data.is_center !== undefined) {
        data.is_center = data.is_center === '1' || data.is_center === 'true';
    }
    
    const url = id ? `/warehouse/warehouses/${id}` : '/warehouse/warehouses';
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
    .then(response => {
        if (!response.ok) {
            return response.json().then(errorData => {
                throw new Error(JSON.stringify(errorData));
            });
        }
        return response.json();
    })
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
        try {
            const errorData = JSON.parse(error.message);
            if (errorData.errors) {
                const errorMessages = Object.values(errorData.errors).flat().join('\n');
                showErrorDialog('Validasi Gagal', errorMessages);
            } else {
                showErrorDialog('Gagal', errorData.message || 'Terjadi kesalahan.');
            }
        } catch (e) {
            showErrorDialog('Gagal', 'Terjadi kesalahan.');
        }
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to deactivate this warehouse? It will be moved to Inactive status.'
        : `Are you sure you want to deactivate ${count} warehouses? They will be moved to Inactive status.`;
    
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
    
    fetch('/warehouse/warehouses/bulk-delete', {
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
function showSuccessModal(count) {
    const message = count === 1 
        ? 'Warehouse berhasil dinonaktifkan.'
        : `${count} warehouse berhasil dinonaktifkan.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'Warehouse tidak berhasil dinonaktifkan. Silakan coba lagi.';
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
        showWarningDialog('Pilih minimal satu warehouse yang ingin dinonaktifkan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
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
