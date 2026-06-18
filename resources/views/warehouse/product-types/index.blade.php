@extends('layouts.app')

@section('title', 'Product Types')
@section('breadcrumb', 'Home / Warehouse / Product Types')

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

    .btn-warning {
        background-color: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background-color: #d97706;
    }

    .btn-success {
        background-color: #10b981;
        color: white;
    }

    .btn-success:hover {
        background-color: #059669;
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

    .responsive-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        font-size: 13px;
        min-width: 1800px;
        table-layout: auto;
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
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 80px; min-width: 80px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 120px; min-width: 120px; }

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
        
        <!-- Product Types Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Product Types</h1>
                @php
                    $productTypesWithoutCategory = \App\Models\ProductType::whereNull('product_category_id')->count();
                @endphp
                @if($productTypesWithoutCategory > 0)
                    <div class="ml-4 px-3 py-1 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <span class="text-yellow-800 text-sm">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ $productTypesWithoutCategory }} ProductType(s) without Category
                            <button class="ml-2 text-yellow-600 underline hover:text-yellow-800" onclick="showUpdateCategoriesModal()">
                                Fix Now
                            </button>
                        </span>
                    </div>
                @endif
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Product Type</span>
                    <span class="md:hidden">Add</span>
                </button>
            </div>
        </div>
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row justify-start items-center w-full">
                <div class="flex flex-row justify-start items-center w-auto mr-4">
                    <input type="text" id="searchInput" class="form-input" placeholder="Search product types..." value="{{ request('search') }}" onkeyup="handleSearch()">
                </div>
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center w-auto">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        <div class="flex flex-row justify-start items-center w-full px-2">
                            <p class="text-sm font-normal text-gray-700 w-auto ml-2 cursor-pointer" onclick="document.getElementById('selectAll').click()">Select all</p>
                        </div>
                    </div>
                </div>
                
                <!-- Bulk Delete Button -->
                <button class="btn btn-secondary btn-sm ml-4" onclick="bulkDeleteProductTypes()">
                    <i class="fas fa-trash"></i>
                    <span>Bulk Delete</span>
                </button>
            </div>
            
        </div>

        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table" id="productTypesTable">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="name">Name</th>
                        <th data-column="product_category__name">Category</th>
                        <th data-column="sku_prefix">SKU Prefix</th>
                        <th data-column="unit">Unit</th>
                        <th data-column="has_serial_number">Has Serial Number</th>
                        <th data-column="is_unit">Is Unit</th>
                        <th data-column="is_active">Status</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                <!-- Table Body -->
                <tbody>
                    @forelse($productTypes ?? [] as $productType)
                    <tr data-id="{{ $productType->id }}" onclick="openViewModal({{ $productType->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $productType->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $productType->name ?? '-' }}</td>
                        <td>
                            @if($productType->productCategory)
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                    {{ $productType->productCategory->name }}
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800" title="Category not assigned">
                                    <i class="fas fa-exclamation-triangle"></i> No Category
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ $productType->sku_prefix ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $productType->unit ?? '-' }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $productType->has_serial_number ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $productType->has_serial_number ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $productType->is_unit ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $productType->is_unit ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $productType->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $productType->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $productType->createdBy->name ?? '-' }}</td>
                        <td>
                            @if($productType->created_at)
                                {{ \Carbon\Carbon::parse($productType->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($productType->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $productType->updatedBy->name ?? '-' }}</td>
                        <td>
                            @if($productType->updated_at)
                                {{ \Carbon\Carbon::parse($productType->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($productType->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-box-open text-4xl mb-3"></i>
                                <p class="text-lg">No product types found</p>
                                <button class="btn btn-primary mt-2" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Add First Product Type
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($productTypes->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $productTypes->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Product Type</h2>
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
        <h3 class="delete-modal-title">Hide Product Type</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this product type? This action can be undone later.</p>
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
        <p class="delete-modal-description" id="errorMessage">We couldn't hide the product type. Please try again.</p>
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
        <p class="delete-modal-description" id="successMessage">The product type has been successfully hidden.</p>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;

// Update Categories Modal
function showUpdateCategoriesModal() {
    const message = 'This will automatically assign categories to ProductTypes that don\'t have one.\n\nStrategy:\n1. Use category from existing MasterProducts\n2. Match by name pattern\n3. Assign to default "Uncategorized" category\n\nContinue?';
    if (!confirm(message)) {
        return;
    }

    const btn = event.target;
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

    fetch('/warehouse/product-types/update-categories', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            alert('✅ ' + result.message + '\n\nUpdated: ' + result.stats.updated + '\nCreated default category: ' + result.stats.created_default);
            window.location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to update categories'));
            btn.disabled = false;
            btn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

// Select All functionality
if (document.getElementById('selectAll')) {
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        if (document.getElementById('headerSelectAll')) {
            document.getElementById('headerSelectAll').checked = this.checked;
        }
    });
}

if (document.getElementById('headerSelectAll')) {
    document.getElementById('headerSelectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        if (document.getElementById('selectAll')) {
            document.getElementById('selectAll').checked = this.checked;
        }
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
        
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = anyChecked && !allChecked;
        }
        if (headerSelectAllCheckbox) {
            headerSelectAllCheckbox.checked = allChecked;
            headerSelectAllCheckbox.indeterminate = anyChecked && !allChecked;
        }
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
    openModal('Create New Product Type');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch('/warehouse/product-types/create', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const productCategories = data.productCategories || [];
            const unitOptions = data.unitOptions || null;
            
            // Build category options
            let categoryOptions = '<option value="">Select Product Category</option>';
            productCategories.forEach(function(category) {
                categoryOptions += '<option value="' + category.id + '">' + category.name + '</option>';
            });
            
            // Build unit options from master option 'Product Units'
            let unitOptionsHtml = '<option value="">Select Unit</option>';
            if (unitOptions && unitOptions.option_details) {
                unitOptions.option_details.forEach(function(unit) {
                    unitOptionsHtml += '<option value="' + unit.option_name + '">' + unit.option_name + '</option>';
                });
            }
            
            // Build form HTML using string concatenation
            const formHtml = 
                '<form id="form" onsubmit="submitForm(event)">' +
                '<div class="modal-section">' +
                '<div class="modal-section-title">Basic Information</div>' +
                '<div class="grid grid-cols-1 gap-6">' +
                '<div class="form-group">' +
                '<label class="form-label">Product Category *</label>' +
                '<select name="product_category_id" class="form-input" required>' +
                categoryOptions +
                '</select>' +
                '<small class="text-muted">Product Type must be associated with a Category</small>' +
                '</div>' +
                '<div class="form-group">' +
                '<label class="form-label">Name *</label>' +
                '<input type="text" name="name" class="form-input" placeholder="Enter product type name" required>' +
                '</div>' +
                '<div class="form-group">' +
                '<label class="form-label">SKU Prefix *</label>' +
                '<input type="text" name="sku_prefix" class="form-input" placeholder="Enter SKU prefix (e.g., PRD)" required maxlength="10">' +
                '</div>' +
                '<div class="form-group">' +
                '<label class="form-label">Unit *</label>' +
                '<select name="unit" class="form-input" required>' +
                unitOptionsHtml +
                '</select>' +
                '</div>' +
                '<div class="form-group">' +
                '<label class="form-label">Description</label>' +
                '<textarea name="description" class="form-input form-textarea" placeholder="Enter product type description"></textarea>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="modal-section">' +
                '<div class="modal-section-title">Settings</div>' +
                '<div class="grid grid-cols-1 gap-6">' +
                '<div class="form-group">' +
                '<label class="form-label">Has Serial Number</label>' +
                '<select name="has_serial_number" class="form-input">' +
                '<option value="0">No</option>' +
                '<option value="1">Yes</option>' +
                '</select>' +
                '</div>' +
                '<div class="form-group">' +
                '<label class="form-label">Is Unit</label>' +
                '<select name="is_unit" class="form-input">' +
                '<option value="0">No</option>' +
                '<option value="1">Yes</option>' +
                '</select>' +
                '</div>' +
                '<div class="form-group">' +
                '<label class="form-label">Status</label>' +
                '<select name="is_active" class="form-input">' +
                '<option value="1">Active</option>' +
                '<option value="0">Inactive</option>' +
                '</select>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</form>';
            
            document.getElementById('modalBody').innerHTML = formHtml;
    
            // Add modal footer
            document.getElementById('modalFooter').innerHTML = 
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>' +
                '<button type="submit" form="form" class="btn btn-primary">Create Product Type</button>';
        }
    })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading form data.</div>';
            document.getElementById('modalFooter').innerHTML = 
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>';
        });
}

// View Modal function - must be defined before onclick handlers
function openViewModal(id) {
    if (!id) {
        console.error('openViewModal: id is required');
        return;
    }
    
    if (typeof openModal !== 'function') {
        console.error('openViewModal: openModal is not defined');
        return;
    }
    
    openModal('View Product Type');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch('/warehouse/product-types/' + id, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Helper function to escape HTML
            const escapeHtml = function(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };
            
            // Helper function to format date
            const formatDate = function(dateString) {
                if (!dateString) return '-';
                try {
                    return new Date(dateString).toLocaleString('id-ID');
                } catch (e) {
                    return dateString;
                }
            };
            
            // Build view modal HTML
            const categoryName = data.data.product_category ? escapeHtml(data.data.product_category.name) : '-';
            const productName = escapeHtml(data.data.name || '-');
            const skuPrefix = escapeHtml(data.data.sku_prefix || '-');
            const unit = escapeHtml(data.data.unit || '-');
            const description = escapeHtml(data.data.description || '-');
            
            const hasSerialNumber = data.data.has_serial_number;
            const serialNumberClass = hasSerialNumber ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
            const serialNumberText = hasSerialNumber ? 'Yes' : 'No';
            
            const isUnit = data.data.is_unit;
            const isUnitClass = isUnit ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
            const isUnitText = isUnit ? 'Yes' : 'No';
            
            const isActive = data.data.is_active;
            const statusClass = isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            const statusText = isActive ? 'Active' : 'Inactive';
            
            const createdBy = data.data.created_by ? escapeHtml(data.data.created_by.name) : '-';
            const updatedBy = data.data.updated_by ? escapeHtml(data.data.updated_by.name) : '-';
            const createdAt = formatDate(data.data.created_at);
            const updatedAt = formatDate(data.data.updated_at);
            
            const viewHtml = 
                '<div class="modal-section">' +
                '<div class="modal-section-title">Basic Information</div>' +
                '<div class="grid grid-cols-1 gap-6">' +
                '<div class="detail-item">' +
                '<label class="form-label">Product Category</label>' +
                '<p class="detail-value">' + categoryName + '</p>' +
                '</div>' +
                '<div class="detail-item">' +
                '<label class="form-label">Name</label>' +
                '<p class="detail-value">' + productName + '</p>' +
                '</div>' +
                '<div class="detail-item">' +
                '<label class="form-label">SKU Prefix</label>' +
                '<p class="detail-value">' +
                '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">' + skuPrefix + '</span>' +
                '</p>' +
                '</div>' +
                '<div class="detail-item">' +
                '<label class="form-label">Unit</label>' +
                '<p class="detail-value">' + unit + '</p>' +
                '</div>' +
                '<div class="detail-item">' +
                '<label class="form-label">Description</label>' +
                '<p class="detail-value">' + description + '</p>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="modal-section">' +
                '<div class="modal-section-title">Settings</div>' +
                '<div class="grid grid-cols-1 gap-6">' +
                '<div class="detail-item">' +
                '<label class="form-label">Has Serial Number</label>' +
                '<p class="detail-value">' +
                '<span class="px-2 py-1 text-xs rounded-full ' + serialNumberClass + '">' + serialNumberText + '</span>' +
                '</p>' +
                '</div>' +
                '<div class="detail-item">' +
                '<label class="form-label">Is Unit</label>' +
                '<p class="detail-value">' +
                '<span class="px-2 py-1 text-xs rounded-full ' + isUnitClass + '">' + isUnitText + '</span>' +
                '</p>' +
                '</div>' +
                '<div class="detail-item">' +
                '<label class="form-label">Status</label>' +
                '<p class="detail-value">' +
                '<span class="px-2 py-1 text-xs rounded-full ' + statusClass + '">' + statusText + '</span>' +
                '</p>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="modal-section">' +
                '<div class="modal-section-title">Audit Information</div>' +
                '<div class="grid grid-cols-1 gap-6">' +
                '<div class="detail-item">' +
                '<label class="form-label">Created By</label>' +
                '<p class="detail-value">' + createdBy + '</p>' +
                '</div>' +
                '<div class="detail-item">' +
                '<label class="form-label">Updated By</label>' +
                '<p class="detail-value">' + updatedBy + '</p>' +
                '</div>' +
                '<div class="detail-item">' +
                '<label class="form-label">Created At</label>' +
                '<p class="detail-value">' + createdAt + '</p>' +
                '</div>' +
                '<div class="detail-item">' +
                '<label class="form-label">Updated At</label>' +
                '<p class="detail-value">' + updatedAt + '</p>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            document.getElementById('modalBody').innerHTML = viewHtml;
        
            // Add modal footer for view modal
            document.getElementById('modalFooter').innerHTML = 
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>' +
                '<button type="button" class="btn btn-primary" onclick="openEditModal(' + id + ')">Edit Product Type</button>';
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            document.getElementById('modalFooter').innerHTML = '<button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>';
        });
}

function openEditModal(id) {
    openModal('Edit Product Type');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch('/warehouse/product-types/' + id + '/edit', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                const productType = data.data;
                const productCategories = data.productCategories || [];
                
                // Build category options
                let categoryOptions = '<option value="">Select Product Category</option>';
                productCategories.forEach(function(category) {
                    const selected = productType.product_category_id == category.id ? ' selected' : '';
                    categoryOptions += '<option value="' + category.id + '"' + selected + '>' + category.name + '</option>';
                });
                
                // Build unit options from master option 'Product Units'
                const unitOptionsData = data.unitOptions || null;
                let unitOptionsHtml = '<option value="">Select Unit</option>';
                if (unitOptionsData && unitOptionsData.option_details) {
                    unitOptionsData.option_details.forEach(function(unit) {
                        const selected = productType.unit === unit.option_name ? ' selected' : '';
                        unitOptionsHtml += '<option value="' + unit.option_name + '"' + selected + '>' + unit.option_name + '</option>';
                    });
                }
                
                // Escape HTML
                const escapeHtml = function(text) {
                    if (!text) return '';
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                };
                
                // Build form HTML
                const formHtml = 
                    '<form id="form" onsubmit="submitForm(event, ' + id + ')">' +
                    '<input type="hidden" name="id" value="' + (productType.id || '') + '">' +
                    '<div class="modal-section">' +
                    '<div class="modal-section-title">Basic Information</div>' +
                    '<div class="grid grid-cols-1 gap-6">' +
                    '<div class="form-group">' +
                    '<label class="form-label">Product Category *</label>' +
                    '<select name="product_category_id" class="form-input" required>' + categoryOptions + '</select>' +
                    '<small class="text-muted">Product Type must be associated with a Category</small>' +
                    '</div>' +
                    '<div class="form-group">' +
                    '<label class="form-label">Name *</label>' +
                    '<input type="text" name="name" class="form-input" value="' + escapeHtml(productType.name || '') + '" placeholder="Enter product type name" required>' +
                    '</div>' +
                    '<div class="form-group">' +
                    '<label class="form-label">SKU Prefix *</label>' +
                    '<input type="text" name="sku_prefix" class="form-input" value="' + escapeHtml(productType.sku_prefix || '') + '" placeholder="Enter SKU prefix (e.g., PRD)" required maxlength="10">' +
                    '</div>' +
                    '<div class="form-group">' +
                    '<label class="form-label">Unit *</label>' +
                    '<select name="unit" class="form-input" required>' + unitOptionsHtml + '</select>' +
                    '</div>' +
                    '<div class="form-group">' +
                    '<label class="form-label">Description</label>' +
                    '<textarea name="description" class="form-input form-textarea" placeholder="Enter product type description">' + escapeHtml(productType.description || '') + '</textarea>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="modal-section">' +
                    '<div class="modal-section-title">Settings</div>' +
                    '<div class="grid grid-cols-1 gap-6">' +
                    '<div class="form-group">' +
                    '<label class="form-label">Has Serial Number</label>' +
                    '<select name="has_serial_number" class="form-input">' +
                    '<option value="0"' + (!productType.has_serial_number ? ' selected' : '') + '>No</option>' +
                    '<option value="1"' + (productType.has_serial_number ? ' selected' : '') + '>Yes</option>' +
                    '</select>' +
                    '</div>' +
                    '<div class="form-group">' +
                    '<label class="form-label">Is Unit</label>' +
                    '<select name="is_unit" class="form-input">' +
                    '<option value="0"' + (!productType.is_unit ? ' selected' : '') + '>No</option>' +
                    '<option value="1"' + (productType.is_unit ? ' selected' : '') + '>Yes</option>' +
                    '</select>' +
                    '</div>' +
                    '<div class="form-group">' +
                    '<label class="form-label">Status</label>' +
                    '<select name="is_active" class="form-input">' +
                    '<option value="1"' + (productType.is_active ? ' selected' : '') + '>Active</option>' +
                    '<option value="0"' + (!productType.is_active ? ' selected' : '') + '>Inactive</option>' +
                    '</select>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</form>';
                
                document.getElementById('modalBody').innerHTML = formHtml;
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = 
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>' +
                '<button type="submit" form="form" class="btn btn-primary">Update Product Type</button>';
        }
    })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            document.getElementById('modalFooter').innerHTML = 
                '<button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>';
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? '/warehouse/product-types/' + id : '/warehouse/product-types';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
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
        alert('Error: Something went wrong');
    });
}

// Delete Modal functions
function openDeleteModal(id = null) {
    if (id) {
        selectedIdsForRetry = [id];
    }
    
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this product type? This action can be undone later.'
        : 'Are you sure you want to hide ' + count + ' product types? This action can be undone later.';
    
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
    
    fetch('/warehouse/product-types/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            showSuccessMessage(result.message);
        } else {
            showErrorMessage(result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Network error occurred');
    });
}

// Search functionality
function handleSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput.value.trim();
    
    if (searchTerm.length >= 2 || searchTerm.length === 0) {
        // Reload the page with search parameter
        const url = new URL(window.location);
        if (searchTerm) {
            url.searchParams.set('search', searchTerm);
        } else {
            url.searchParams.delete('search');
        }
        window.location.href = url.toString();
    }
}

// Bulk operations
function bulkDeleteProductTypes() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one product type to delete');
        return;
    }
    
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (confirm('Are you sure you want to delete ' + ids.length + ' product type(s)? This action cannot be undone.')) {
        fetch('/warehouse/product-types/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccessMessage(data.message);
            } else {
                showErrorMessage(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorMessage('Failed to delete product types');
        });
    }
}

function toggleStatus(id) {
    fetch('/warehouse/product-types/' + id + '/toggle-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessMessage(data.message);
        } else {
            showErrorMessage(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Failed to update product type status');
    });
}

function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one product type to hide');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Success and Error Message functions
function showSuccessMessage(message) {
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Auto close after 3 seconds
    setTimeout(() => {
        closeSuccessModal();
        location.reload();
    }, 3000);
}

function showErrorMessage(message) {
    document.getElementById('errorMessage').textContent = message || 'Something went wrong. Please try again.';
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'The product type has been successfully hidden.'
        : count + ' product types have been successfully hidden.';
    
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
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t hide the product type. Please try again.';
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
@endsection
