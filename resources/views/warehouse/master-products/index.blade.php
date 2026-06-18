@extends('layouts.app')

@section('title', 'Master Products')
@section('breadcrumb', 'Home / Warehouse / Master Products')

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
        min-width: 2000px;
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
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 80px; min-width: 80px; }
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 80px; min-width: 80px; }
    .responsive-table th:nth-child(11), .responsive-table td:nth-child(11) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(12), .responsive-table td:nth-child(12) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(13), .responsive-table td:nth-child(13) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(14), .responsive-table td:nth-child(14) { width: 100px; min-width: 100px; }
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
        white-space: pre-line; /* Allow line breaks */
        word-wrap: break-word; /* Break long words */
        max-height: 400px; /* Limit height */
        overflow-y: auto; /* Allow scrolling if too long */
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
            min-width: 2000px;
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
        
        <!-- Master Products Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Products</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <x-filter-status />
                <button class="btn btn-secondary mr-3" onclick="window.location.href='/warehouse/packaging-sizes'">
                    <i class="fas fa-box"></i>
                    <span class="hidden md:inline">Packaging Size</span>
                    <span class="md:hidden">Packaging Size</span>
                </button>
                <button class="btn btn-secondary mr-3" onclick="openImportModal()">
                    <i class="fas fa-file-import"></i>
                    <span class="hidden md:inline">Import CSV</span>
                    <span class="md:hidden">Import</span>
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add Product</span>
                    <span class="md:hidden">Add Product</span>
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
            <table class="responsive-table" id="productsTable">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="sku">SKU Code</th>
                        <th data-column="name">Name</th>
                        <th data-column="productCategory__name">Category</th>
                        <th data-column="variant_name">Variant</th>
                        <th data-column="brand_line">Brand Line</th>
                        <th data-column="packaging_size">Package Size</th>
                        <th data-column="unit">Unit</th>
                        <th data-column="minimum_stock" data-type="numeric">Min Stock</th>
                        <th data-column="maximum_stock" data-type="numeric">Max Stock</th>
                        <th data-column="unit_price" data-type="numeric">Unit Price</th>
                        <th data-column="is_active">Status</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="creator__name">Created By</th>
                        <th data-column="updater__name">Updated By</th>
                        <th data-column="updated_at" data-type="date">Updated At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <!-- Table Body -->
                <tbody>
                    @forelse($products ?? [] as $product)
                    <tr data-id="{{ $product->id }}" onclick="openViewModal({{ $product->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $product->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ $product->sku ?? $product->sku_code ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <div>
                                <strong>{{ $product->name ?? '-' }}</strong>
                                @if($product->description)
                                    <br><small class="text-gray-500">{{ Str::limit($product->description, 50) }}</small>
                                @endif
                            </div>
                        </td>
                        <td>@if($product->productCategory){{ $product->productCategory->name }}@else-@endif</td>
                        <td>{{ $product->variant_name ?? '-' }}</td>
                        <td>{{ $product->brand_line ?? '-' }}</td>
                        <td>@if($product->packagingSize){{ $product->packagingSize->name }}@else-@endif</td>
                        <td>{{ $product->unit ?? ($product->packagingSize->code ?? '-') }}</td>
                        <td>{{ number_format($product->minimum_stock ?? 0) }}</td>
                        <td>{{ number_format($product->maximum_stock ?? 0) }}</td>
                        <td>{{ $product->unit_price ? 'Rp ' . number_format($product->unit_price, 2, ',', '.') : '-' }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            @if($product->created_at)
                                {{ \Carbon\Carbon::parse($product->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($product->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $product->createdBy->name ?? '-' }}</td>
                        <td>{{ $product->updatedBy->name ?? '-' }}</td>
                        <td>
                            @if($product->updated_at)
                                {{ \Carbon\Carbon::parse($product->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($product->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openDuplicateModal({{ $product->id }})">
                                <i class="fas fa-copy"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="15" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-box-open text-4xl mb-3"></i>
                                <p class="text-lg">No products found</p>
                                <button class="btn btn-primary mt-2" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Add First Product
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($products->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $products->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Product</h2>
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
        <h3 class="delete-modal-title">Deactivate Product</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to deactivate this product? This action can be undone later.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Yes, Deactivate</button>
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
        <p class="delete-modal-description" id="errorMessage">Produk tidak berhasil dinonaktifkan. Silakan coba lagi.</p>
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
        <p class="delete-modal-description" id="successMessage">Produk berhasil dinonaktifkan.</p>
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

// Preview photo before upload
function previewPhoto(input, previewContainerId) {
    const container = document.getElementById(previewContainerId);
    const img = container.querySelector('img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        container.style.display = 'none';
        img.src = '';
    }
}

// Update unit field when product category changes
function updateUnitFromProductCategory(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const unitValue = selectedOption ? selectedOption.getAttribute('data-unit') : '';
    
    // Find unit display field - try both create and edit modal field IDs
    let unitDisplay = document.getElementById('edit_unit_display');
    if (!unitDisplay) {
        unitDisplay = document.getElementById('unit_display');
    }
    
    if (unitDisplay) {
        unitDisplay.value = unitValue || '';
        console.log('Unit updated from category to:', unitValue);
    }
    
    // Also update unit_order if exists
    let unitOrderDisplay = document.getElementById('edit_unit_order_display');
    if (!unitOrderDisplay) {
        unitOrderDisplay = document.getElementById('unit_order_display');
    }
    
    if (unitOrderDisplay) {
        unitOrderDisplay.value = unitValue || '';
    }
}



// Submit form with FormData to support file uploads
function submitForm(event, productId = null) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Debug: Log form data contents
    console.log('Form data before processing:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}:`, value);
    }
    
    // Explicitly get file input and append if exists (in case FormData didn't capture it)
    const fileInputs = form.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        if (input.files && input.files.length > 0) {
            console.log('File found:', input.name, input.files[0].name, input.files[0].size);
            // Remove existing entry if any
            formData.delete(input.name);
            // Add the file
            formData.append(input.name, input.files[0]);
        }
    });
    
    // Debug: Log after file handling
    console.log('Form data after file handling:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}:`, value instanceof File ? `File: ${value.name}` : value);
    }
    
    // Handle checkboxes properly - if unchecked, they're not in FormData, so we need to add them as false
    ['is_active', 'is_trading', 'is_stock_substitute'].forEach(field => {
        if (!formData.has(field)) {
            formData.append(field, '0');
        }
    });
    
    // Handle number fields that might be empty - ensure they're sent
    // For required fields (minimum_stock, maximum_stock), use 0 if empty
    // For optional fields (bom_quantity, etc.), use empty string or 0
    const requiredNumberFields = ['minimum_stock', 'maximum_stock'];
    const optionalNumberFields = ['bom_quantity', 'unit_price', 'last_unit_price', 'net_weight', 'gross_weight', 'lifetime'];
    
    requiredNumberFields.forEach(field => {
        const value = formData.get(field);
        if (!value || value === '' || value === null) {
            formData.set(field, '0');
        }
    });
    
    optionalNumberFields.forEach(field => {
        const value = formData.get(field);
        if (!formData.has(field) || value === '' || value === null) {
            formData.set(field, '');
        }
    });
    
    // Determine URL based on whether we're creating or updating
    let url = '/warehouse/master-products';
    let method = 'POST';
    
    if (productId) {
        url = `/warehouse/master-products/${productId}`;
        formData.append('_method', 'PUT');
    }
    
    // Show loading state on submit button
    const submitBtn = document.querySelector('#modalFooter button[type="submit"]');
    const originalText = submitBtn ? submitBtn.textContent : 'Submit';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';
    }
    
    console.log('Sending to URL:', url);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        console.log('Server response:', result);
        if (result.status === 'success') {
            closeModal();
            showSuccessModal(1, result.message || 'Produk berhasil disimpan.');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            let errorMessage = result.message || 'Produk tidak berhasil disimpan.';
            if (result.errors) {
                const errorList = Object.values(result.errors).flat().join('\n');
                errorMessage += '\n\n' + errorList;
            }
            showErrorModal(errorMessage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal(error.message || 'Terjadi kesalahan jaringan.');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

// Global handler for category change
function handleCategoryChange(selectElement) {
    const categoryId = selectElement.value;
    console.log('handleCategoryChange called with categoryId:', categoryId);
    
    updateUnitFromProductCategory(selectElement);

    const form = selectElement.closest('form') || document;
    const refillSpecsSection = form.querySelector('[data-refill-specs-section]')
        || document.getElementById('refill-specs-section')
        || document.getElementById('edit-refill-specs-section')
        || document.getElementById('dup-refill-specs-section');
    toggleRefillSpecsSection(selectElement, refillSpecsSection);
}

function setSelectValueAndRefresh(select, value = '') {
    if (!select) return;

    select.value = value;
    if (window.NiceSelect) {
        const instance = NiceSelect.get(select);
        if (instance) instance.update();
    }
}

function setRefillSpecsEnabled(refillSpecsSection, enabled) {
    if (!refillSpecsSection) return;

    refillSpecsSection.querySelectorAll('select, input, textarea').forEach(field => {
        field.disabled = !enabled;

        if (!enabled) {
            if (field.tagName === 'SELECT') {
                setSelectValueAndRefresh(field, '');
            } else {
                field.value = '';
            }
        }

        if (window.NiceSelect && field.tagName === 'SELECT') {
            const instance = NiceSelect.get(field);
            if (instance) instance.update();
        }
    });
}

// Toggle Refill Specifications section visibility based on Product Category's is_unit flag
function toggleRefillSpecsSection(productCategorySelect, refillSpecsSection) {
    console.log('toggleRefillSpecsSection called', { 
        hasProductCategorySelect: !!productCategorySelect, 
        hasRefillSpecsSection: !!refillSpecsSection 
    });
    
    if (!productCategorySelect || !refillSpecsSection) return;
    
    const selectedOption = productCategorySelect.options[productCategorySelect.selectedIndex];
    console.log('Selected option in toggleRefillSpecsSection:', selectedOption ? {
        value: selectedOption.value,
        text: selectedOption.textContent,
        isUnit: selectedOption.getAttribute('data-is-unit')
    } : 'no selection');
    
    const isUnit = selectedOption && selectedOption.value
        ? selectedOption.getAttribute('data-is-unit') === '1'
        : false;

    refillSpecsSection.style.display = isUnit ? 'none' : 'block';
    setRefillSpecsEnabled(refillSpecsSection, !isUnit);
}

// Global handler for brand line change - cascading variants
function handleBrandLineChange(selectElement) {
    console.log('handleBrandLineChange called');
    
    // Find the variant select element in the same scope (could be create or edit modal)
    // We try to find by ID first (works if standard IDs are used)
    let variantNameSelect = document.getElementById('variant_name_id');
    
    // If inside duplicate modal or edit modal where IDs might differ, try better scoping
    // Check if we are in 'edit' or 'duplicate' context if needed, but standard IDs seem used
    // If specific ID logic needed:
    if (selectElement.id === 'dup_brand_line_id') {
        variantNameSelect = document.getElementById('dup_variant_name_id') || document.getElementById('variant_name_id');
    } else if (selectElement.id === 'edit_brand_line_id') {
        variantNameSelect = document.getElementById('edit_variant_name_id');
    }
    
    // Fallback: try to find in correct container section
    if (!variantNameSelect) {
        console.warn('Variant select not found by ID, searching siblings');
        // This logic depends on HTML structure, fallback to global ID is safer for now
        variantNameSelect = document.getElementById('variant_name_id'); 
    }
    
    if (!variantNameSelect) {
        console.error('Variant select element not found');
        return;
    }

    const selectedBrandLine = selectElement.value;
    const currentVariantValue = variantNameSelect.getAttribute('data-current-value'); // For edit mode
    
    console.log('Brand line changed to:', selectedBrandLine, 'Target variant select:', variantNameSelect.id);
    
    // Reset variant dropdown
    variantNameSelect.innerHTML = '<option value="">Select Variant Name</option>';
    
    if (!selectedBrandLine) {
        if (window.NiceSelect) {
            const ns = NiceSelect.get(variantNameSelect);
            if (ns) ns.update();
        }
        variantNameSelect.dispatchEvent(new Event('change'));
        return;
    }
    
    // Show loading state
    variantNameSelect.innerHTML = '<option value="">Memuat...</option>';
    if (window.NiceSelect) {
        const ns = NiceSelect.get(variantNameSelect);
        if (ns) ns.update();
    }

    // Fetch variants
    fetch(`/warehouse/brand-variants/by-brand-line?brand_line_id=${selectedBrandLine}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Variants API response:', data);
        
        variantNameSelect.innerHTML = '<option value="">Select Variant Name</option>';
        
        if (data.status === 'success' && data.variants && data.variants.length > 0) {
            data.variants.forEach(variant => {
                const option = document.createElement('option');
                option.value = variant.name;
                option.textContent = variant.name;
                // Re-select if it matches current value (edit mode)
                if (currentVariantValue && variant.name === currentVariantValue) {
                    option.selected = true;
                }
                variantNameSelect.appendChild(option);
            });
        }
        
        variantNameSelect.dispatchEvent(new Event('change')); // Trigger standard event
        
        if (window.NiceSelect) {
            const ns = NiceSelect.get(variantNameSelect);
            if (ns) ns.update();
        }
    })
    .catch(err => {
        console.error('Error fetching variants:', err);
        variantNameSelect.innerHTML = '<option value="">Error loading variants</option>';
    });
}


// CRUD Modal functions
function openCreateModal() {
    openModal('Create New Product');
    
    // Load data from controller
    fetch('/warehouse/master-products/create', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const productCategories = data.productCategories || [];
            const packagingSizes = data.packagingSizes || [];
            const brandLines = data.brandLines || null;
            const brandVariants = data.brandVariants || [];
            const unitOptions = data.unitOptions || null;
            const serviceFrequencies = data.serviceFrequencies || [];
            const unitHelperOptions = data.unitHelperOptions || {};
            
            // Build units object from unitOptions
            const units = {};
            if (unitOptions && unitOptions.option_details) {
                unitOptions.option_details.forEach(unit => {
                    units[unit.option_name] = unit.option_name;
                });
            }
            
            // Build service frequencies options
            let serviceFrequenciesOptions = '<option value="">Select Frequency Service</option>';
            if (serviceFrequencies && serviceFrequencies.length > 0) {
                serviceFrequencies.forEach(freq => {
                    serviceFrequenciesOptions += `<option value="${freq.code}">${freq.name} (${freq.frequency_description})</option>`;
                });
            }
            
            // Build unit order options from UnitHelper
            let unitOrderOptions = '<option value="">Select Unit Order</option>';
            if (unitHelperOptions && Object.keys(unitHelperOptions).length > 0) {
                Object.entries(unitHelperOptions).forEach(([value, label]) => {
                    if (value !== '') {
                        unitOrderOptions += `<option value="${value}">${label}</option>`;
                    }
                });
            }
            
            // Build brand lines options
            let brandLinesOptions = '<option value="">Select Brand Line</option>';
            if (brandLines && brandLines.option_details) {
                brandLines.option_details.forEach(brandLine => {
                    brandLinesOptions += `<option value="${brandLine.option_name}">${brandLine.option_name}</option>`;
                });
            }
            
            // Build brand variants options - using new brandVariants collection
            let brandVariantsOptions = '<option value="">Select Variant Name</option>';
            if (brandVariants && brandVariants.length > 0) {
                brandVariants.forEach(variant => {
                    brandVariantsOptions += `<option value="${variant.name}">${variant.name}</option>`;
                });
            }
            
            // Build packaging sizes options
            let packagingSizesOptions = '<option value="">Select Packaging Size</option>';
            if (packagingSizes && packagingSizes.length > 0) {
                packagingSizes.forEach(packagingSize => {
                    packagingSizesOptions += `<option value="${packagingSize.id}">${packagingSize.name} (${packagingSize.code})</option>`;
                });
            }
        
        document.getElementById('modalBody').innerHTML = `
        <form id="form" enctype="multipart/form-data">
            <div class="modal-section">
                <div class="modal-section-title">Basic Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" class="form-input" placeholder="Enter product name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SKU Code</label>
                        <input type="text" name="sku" class="form-input" placeholder="Auto-generated if empty">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Part Number <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="part_no" class="form-input" placeholder="Part number">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input form-textarea" placeholder="Enter product description"></textarea>
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Description 2 <small class="text-muted">(Optional)</small></label>
                        <textarea name="description_2" class="form-input form-textarea" placeholder="Enter additional description"></textarea>
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Product Photo <small class="text-muted">(Optional)</small></label>
                        <div class="photo-upload-container">
                            <input type="file" name="product_photo" id="product_photo_input" class="form-input" accept="image/*" onchange="previewPhoto(this, 'photo_preview')">
                            <div id="photo_preview" class="photo-preview-box" style="margin-top: 10px; display: none;">
                                <img id="photo_preview_img" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Category</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Product Category *</label>
                        <select name="product_category_id" class="form-input" id="product_category_id" required onchange="handleCategoryChange(this)">
                            <option value="">Select Product Category</option>
                            ${productCategories.map(category => 
                                `<option value="${category.id}" data-is-unit="${category.is_unit ? 1 : 0}" data-unit="${category.unit || ''}">${category.name}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" class="form-input" id="unit_display" value="" readonly placeholder="Auto-filled from Category">
                        <small class="text-muted">Unit diambil otomatis dari Category yang dipilih</small>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Dimensions & Weight</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-group">
                        <label class="form-label">Dimensions (P × L × T) <small class="text-muted">cm</small></label>
                        <input type="text" name="dimensions" class="form-input" placeholder="10 × 20 × 30">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Net Weight <small class="text-muted">kg</small></label>
                        <input type="number" name="net_weight" class="form-input" step="0.001" min="0" placeholder="0.000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gross Weight <small class="text-muted">kg</small></label>
                        <input type="number" name="gross_weight" class="form-input" step="0.001" min="0" placeholder="0.000">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Stock & Ordering</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Minimum Stock *</label>
                        <input type="number" name="minimum_stock" class="form-input" min="0" placeholder="Enter minimum stock" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Maximum Stock *</label>
                        <input type="number" name="maximum_stock" class="form-input" min="0" placeholder="Enter maximum stock" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">BOM Qty *</label>
                        <input type="number" name="bom_quantity" class="form-input" min="0" step="0.01" placeholder="Enter BOM Quantity" required>
                    </div>
                </div>
            </div>
            
            <div class="modal-section" style="display:none;">
                <div class="modal-section-title">Service & Lifetime</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Frequency Service <small class="text-muted">(Optional)</small></label>
                        <select name="frequency_service" class="form-input">
                            ${serviceFrequenciesOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lifetime <small class="text-muted">(Optional)</small></label>
                        <div class="flex gap-2">
                            <input type="number" name="lifetime" class="form-input flex-1" min="0" placeholder="0">
                            <select name="lifetime_unit" class="form-input" style="width: 120px;">
                                <option value="days">Days</option>
                                <option value="months">Months</option>
                                <option value="years">Years</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-section" id="refill-specs-section" data-refill-specs-section>
                <div class="modal-section-title">Product Refill Specifications</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Brand Line <small class="text-muted">(Optional)</small></label>
                        <select name="brand_line" class="form-input" id="brand_line_id" onchange="handleBrandLineChange(this)">
                            ${brandLinesOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Variant Name <small class="text-muted">(Optional)</small></label>
                        <select name="variant_name" class="form-input" id="variant_name_id">
                            ${brandVariantsOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Packaging Size</label>
                        <select name="packaging_size_id" class="form-input" id="packaging_size_id">
                            ${packagingSizesOptions}
                        </select>
                        <input type="hidden" name="packaging_size" id="packaging_size_hidden">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Status & Flags</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>Is Active</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="is_trading" value="1">
                            <span>Is Trading</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="is_stock_substitute" value="1">
                            <span>Is Stock Substitute</span>
                        </label>
                    </div>
                </div>
            </div>
        </form>
        `;
        
        // Add modal footer - use onclick instead of form attribute
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" id="createSubmitBtn">Create Product</button>
        `;
        
        // Attach submit handler to button
        document.getElementById('createSubmitBtn').addEventListener('click', function() {
            const form = document.getElementById('form');
            const formData = new FormData(form);
            
            // Debug log
            console.log('=== Create Modal Submit ===');
            console.log('Form found:', !!form);
            
            // Explicitly capture file input
            const fileInput = form.querySelector('input[name="product_photo"]');
            console.log('File input found:', !!fileInput);
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                console.log('File selected:', fileInput.files[0].name, fileInput.files[0].size);
                formData.delete('product_photo');
                formData.append('product_photo', fileInput.files[0]);
            } else {
                console.log('No file selected');
            }
            
            // Log all form data
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(key + ': File - ' + value.name + ' (' + value.size + ' bytes)');
                } else {
                    console.log(key + ':', value);
                }
            }
            
            // Handle checkboxes
            ['is_active', 'is_trading', 'is_stock_substitute'].forEach(field => {
                if (!formData.has(field)) {
                    formData.append(field, '0');
                }
            });
            
            // Show loading
            this.disabled = true;
            this.textContent = 'Saving...';
            
            fetch('/warehouse/master-products', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                console.log('Server response:', result);
                if (result.status === 'success') {
                    closeModal();
                    showSuccessModal(1, result.message || 'Produk berhasil dibuat.');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    let errorMessage = result.message || 'Produk tidak berhasil dibuat.';
                    if (result.errors) {
                        errorMessage += '\n\n' + Object.values(result.errors).flat().join('\n');
                    }
                    showErrorModal(errorMessage);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal(error.message || 'Terjadi kesalahan jaringan.');
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Create Product';
            });
        });
        
        // Add event listener for product category change to filter product types
        const productCategorySelect = document.getElementById('product_category_id');
        const unitDisplay = document.getElementById('unit_display');
        const refillSpecsSection = document.getElementById('refill-specs-section');
        
        console.log('Elements found:', {
            productCategorySelect: !!productCategorySelect,
            unitDisplay: !!unitDisplay,
            refillSpecsSection: !!refillSpecsSection
        });
        
        if (productCategorySelect) {
            productCategorySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const unitValue = selectedOption ? selectedOption.getAttribute('data-unit') : '';
                if (unitDisplay) unitDisplay.value = unitValue || '';
                
                toggleRefillSpecsSection(this, refillSpecsSection);
            });
            // Trigger initial state for refill specs section
            toggleRefillSpecsSection(productCategorySelect, refillSpecsSection);
        }
        
        // Initialize custom selects (e.g. NiceSelect) for newly injected HTML
        if (window.NiceSelect) {
            document.querySelectorAll('#modalBody select').forEach(select => {
                try {
                    // Only bind if it hasn't been initialized yet
                    if (!NiceSelect.get(select)) {
                        NiceSelect.bind(select, { searchable: true });
                    } else {
                        NiceSelect.get(select).update();
                    }
                } catch (e) {
                    console.warn('NiceSelect bind error:', e);
                }
            });
        }
        
        const brandLineSelect = document.getElementById('brand_line_id');
        const variantNameSelect = document.getElementById('variant_name_id');
        
        if (brandLineSelect && variantNameSelect) {
            console.log('Brand line listener handled via global function handleBrandLineChange');
        }
    }
})
.catch(error => {
    console.error('Error loading form data:', error);
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading form data.</div>';
});
}

function openViewModal(id) {
    openModal('View Product');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/warehouse/master-products/${id}`, {
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
                    <div class="modal-section-title">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Product Name</label>
                            <p class="detail-value">${data.data.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">SKU Code</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    ${data.data.sku || data.data.sku_code || '-'}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Part Number</label>
                            <p class="detail-value">${data.data.part_no || '-'}</p>
                        </div>
                        <div class="detail-item md:col-span-2">
                            <label class="form-label">Description</label>
                            <p class="detail-value">${data.data.description || '-'}</p>
                        </div>
                        <div class="detail-item md:col-span-2">
                            <label class="form-label">Description 2</label>
                            <p class="detail-value">${data.data.description_2 || '-'}</p>
                        </div>
                        <div class="detail-item md:col-span-2">
                            <label class="form-label">Product Photo</label>
                            ${data.data.product_photo ? `
                                <div class="product-photo-display">
                                    <img src="${data.data.product_photo}" alt="Product Photo" style="max-width: 300px; max-height: 300px; border-radius: 8px; border: 1px solid #ddd;">
                                </div>
                            ` : '<p class="detail-value">-</p>'}
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Category</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Product Category</label>
                            <p class="detail-value">${data.data.product_category?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Unit</label>
                            <p class="detail-value">${data.data.unit || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Dimensions & Weight</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Dimensions (P × L × T)</label>
                            <p class="detail-value">${data.data.dimensions ? data.data.dimensions + ' cm' : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Net Weight</label>
                            <p class="detail-value">${data.data.net_weight ? data.data.net_weight.toLocaleString('id-ID', {minimumFractionDigits: 3}) + ' kg' : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Gross Weight</label>
                            <p class="detail-value">${data.data.gross_weight ? data.data.gross_weight.toLocaleString('id-ID', {minimumFractionDigits: 3}) + ' kg' : '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Stock & Ordering</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Minimum Stock</label>
                            <p class="detail-value">${data.data.minimum_stock ? data.data.minimum_stock.toLocaleString() : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Maximum Stock</label>
                            <p class="detail-value">${data.data.maximum_stock ? data.data.maximum_stock.toLocaleString() : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">BOM Qty</label>
                            <p class="detail-value">${data.data.bom_quantity ? data.data.bom_quantity.toLocaleString() : '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section" style="display:none;">
                    <div class="modal-section-title">Service & Lifetime</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Frequency Service</label>
                            <p class="detail-value">${data.data.frequency_service || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Lifetime</label>
                            <p class="detail-value">${data.data.lifetime ? data.data.lifetime + ' days' : '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Product Hierarchy</div>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Category</label>
                            <p class="detail-value">${data.data.product_category?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Variant</label>
                            <p class="detail-value">${data.data.variant_name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Dimensi</label>
                            <p class="detail-value">${data.data.dimensions ? data.data.dimensions + ' cm' : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Brand Line</label>
                            <p class="detail-value">${data.data.brand_line || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Packaging Size</label>
                            <p class="detail-value">${data.data.packagingSize?.name || data.data.packaging_size?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">SKU Code</label>
                            <p class="detail-value">${data.data.sku || data.data.sku_code || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Warehouse Information</div>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Available in Warehouses</label>
                            <div class="detail-value">
                                ${data.data.warehouse_products && data.data.warehouse_products.length > 0 ? 
                                    data.data.warehouse_products.map(wp => 
                                        `<div class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded-lg mb-2">
                                            <div>
                                                <strong>${wp.warehouse?.name || 'Unknown Warehouse'}</strong>
                                                <br><small class="text-gray-500">${wp.warehouse?.branch?.name || 'Central Warehouse'}</small>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-lg font-semibold text-blue-600">${wp.quantity || 0}</span>
                                                <br><small class="text-gray-500">${data.data.unit || 'pcs'}</small>
                                                ${data.data.packaging_size?.name ? `<br><small class="text-gray-500">Size: ${data.data.packaging_size.name}</small>` : ''}
                                            </div>
                                        </div>`
                                    ).join('') : 
                                    '<div class="text-center py-4 text-gray-500">No warehouse information available</div>'
                                }
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Status & Flags</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Active Status</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full ${data.data.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${data.data.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Is Trading</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full ${data.data.is_trading ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}">
                                    ${data.data.is_trading ? 'Yes' : 'No'}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Is Stock Substitute</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full ${data.data.is_stock_substitute ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'}">
                                    ${data.data.is_stock_substitute ? 'Yes' : 'No'}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Additional Information</div>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Created By</label>
                            <p class="detail-value">${data.data.created_by?.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated By</label>
                            <p class="detail-value">${data.data.updated_by?.name || '-'}</p>
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
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit Product</button>
        `;
        })
        .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Gagal memuat detail.</div>';
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
        `;
        });
}

function openEditModal(id) {
    openModal('Edit Product');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    Promise.all([
        fetch(`/warehouse/master-products/${id}/edit`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        }),
        fetch('/warehouse/product-categories', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => response.json())
    ])
        .then(([editResponse, categoriesResponse]) => {
            if (!editResponse.status || editResponse.status !== 'success') {
                throw new Error('Data tidak berhasil dimuat');
            }
            const data = editResponse.data;
            const productCategories = categoriesResponse.data || [];
            const packagingSizes = editResponse.packagingSizes || [];
            const brandLines = editResponse.brandLines || null;
            const brandVariants = editResponse.brandVariants || [];
            const unitOptions = editResponse.unitOptions || null;
            const serviceFrequencies = editResponse.serviceFrequencies || [];
            const unitHelperOptions = editResponse.unitHelperOptions || {};
            
            // Build units object from unitOptions
            const units = {};
            if (unitOptions && unitOptions.option_details) {
                unitOptions.option_details.forEach(unit => {
                    units[unit.option_name] = unit.option_name;
                });
            }
            
            // Build service frequencies options
            let serviceFrequenciesOptionsEdit = '<option value="">Select Frequency Service</option>';
            if (serviceFrequencies && serviceFrequencies.length > 0) {
                serviceFrequencies.forEach(freq => {
                    const selected = data.frequency_service === freq.code ? 'selected' : '';
                    serviceFrequenciesOptionsEdit += `<option value="${freq.code}" ${selected}>${freq.name} (${freq.frequency_description})</option>`;
                });
            }
            
            // Build unit order options from UnitHelper
            let unitOrderOptionsEdit = '<option value="">Select Unit Order</option>';
            if (unitHelperOptions && Object.keys(unitHelperOptions).length > 0) {
                Object.entries(unitHelperOptions).forEach(([value, label]) => {
                    if (value !== '') {
                        const selected = data.unit_order === value ? 'selected' : '';
                        unitOrderOptionsEdit += `<option value="${value}" ${selected}>${label}</option>`;
                    }
                });
            }
            
            // Parse lifetime for edit
            let lifetimeValue = data.lifetime || '';
            let lifetimeUnit = 'days';
            if (lifetimeValue && lifetimeValue > 0) {
                if (lifetimeValue >= 365) {
                    lifetimeUnit = 'years';
                    lifetimeValue = Math.round(lifetimeValue / 365);
                } else if (lifetimeValue >= 30) {
                    lifetimeUnit = 'months';
                    lifetimeValue = Math.round(lifetimeValue / 30);
                }
            }
            
            // Build brand lines options for edit
            let brandLinesOptionsEdit = '<option value="">Select Brand Line</option>';
            let brandLineFound = false;
            if (brandLines && brandLines.option_details) {
                brandLines.option_details.forEach(brandLine => {
                    const isSelected = data.brand_line === brandLine.option_name;
                    if (isSelected) brandLineFound = true;
                    const selected = isSelected ? 'selected' : '';
                    brandLinesOptionsEdit += `<option value="${brandLine.option_name}" ${selected}>${brandLine.option_name}</option>`;
                });
            }
            
            // Preserve existing Brand Line if not in list
            if (data.brand_line && !brandLineFound) {
                console.log('Preserving existing Brand Line:', data.brand_line);
                brandLinesOptionsEdit += `<option value="${data.brand_line}" selected>${data.brand_line} (Current)</option>`;
            }
            
            // Build product variants options for edit
            let productVariantsOptionsEdit = '<option value="">Select Variant Name</option>';
            let variantFound = false;
            
            // Initial filter: only show variants for the current brand line
            if (brandVariants && brandVariants.length > 0) {
                brandVariants.forEach(variant => {
                    // Only show if it matches current brand line OR it is the currently selected variant
                    const matchesBrandLine = data.brand_line && (variant.brand_line_name === data.brand_line);
                    const isSelected = data.variant_name === variant.name;
                    
                    if (matchesBrandLine || isSelected) {
                        if (isSelected) variantFound = true;
                        const selected = isSelected ? 'selected' : '';
                        productVariantsOptionsEdit += `<option value="${variant.name}" ${selected}>${variant.name}</option>`;
                    }
                });
            }
            
            // Preserve existing Variant Name if not in list
            if (data.variant_name && !variantFound) {
                console.log('Preserving existing Variant Name:', data.variant_name);
                productVariantsOptionsEdit += `<option value="${data.variant_name}" selected>${data.variant_name} (Current)</option>`;
            }
            
            // Build packaging sizes options for edit
            let packagingSizesOptionsEdit = '<option value="">Select Packaging Size</option>';
            if (packagingSizes && packagingSizes.length > 0) {
                packagingSizes.forEach(packagingSize => {
                    const selected = data.packaging_size_id == packagingSize.id ? 'selected' : '';
                    packagingSizesOptionsEdit += `<option value="${packagingSize.id}" ${selected}>${packagingSize.name} (${packagingSize.code})</option>`;
                });
            }
            
            document.getElementById('modalBody').innerHTML = `
                <form id="form" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="modal-section">
                        <div class="modal-section-title">Basic Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Product Name *</label>
                                <input type="text" name="name" class="form-input" value="${data.name || ''}" placeholder="Enter product name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">SKU Code</label>
                                <input type="text" name="sku" class="form-input" value="${data.sku || ''}" placeholder="Enter SKU code">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Part Number <small class="text-muted">(Optional)</small></label>
                                <input type="text" name="part_no" class="form-input" value="${data.part_no || ''}" placeholder="Part number">
                            </div>
                            <div class="form-group md:col-span-2">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-input form-textarea" placeholder="Enter product description">${data.description || ''}</textarea>
                            </div>
                            <div class="form-group md:col-span-2">
                                <label class="form-label">Description 2 <small class="text-muted">(Optional)</small></label>
                                <textarea name="description_2" class="form-input form-textarea" placeholder="Enter additional description">${data.description_2 || ''}</textarea>
                            </div>
                            <div class="form-group md:col-span-2">
                                <label class="form-label">Product Photo <small class="text-muted">(Optional)</small></label>
                                <div class="photo-upload-container">
                                    ${data.product_photo ? `
                                        <div class="current-photo mb-2">
                                            <img src="${data.product_photo}" alt="Current Photo" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                                            <p class="text-muted text-sm mt-1">Current photo</p>
                                        </div>
                                    ` : ''}
                                    <input type="file" name="product_photo" id="edit_product_photo_input" class="form-input" accept="image/*" onchange="previewPhoto(this, 'edit_photo_preview')">
                                    <div id="edit_photo_preview" class="photo-preview-box" style="margin-top: 10px; display: none;">
                                        <img id="edit_photo_preview_img" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                                        <p class="text-muted text-sm mt-1">New photo preview</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Category & Type</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Product Category *</label>
                                <select name="product_category_id" class="form-input" id="edit_product_category_id" required onchange="handleCategoryChange(this)">
                                    <option value="">Select Product Category</option>
                                    ${productCategories.map(category => 
                                        `<option value="${category.id}" data-is-unit="${category.is_unit ? 1 : 0}" data-unit="${category.unit || ''}" ${data.product_category_id == category.id ? 'selected' : ''}>${category.name}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Unit</label>
                                <input type="text" name="unit" class="form-input" id="edit_unit_display" value="${data.unit || ''}" readonly placeholder="Auto-filled from Category">
                                <small class="text-muted">Unit diambil otomatis dari Category yang dipilih</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Dimensions & Weight</div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="form-group">
                                <label class="form-label">Dimensions (P × L × T) <small class="text-muted">cm</small></label>
                                <input type="text" name="dimensions" class="form-input" value="${data.dimensions || ''}" placeholder="10 × 20 × 30">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Net Weight <small class="text-muted">kg</small></label>
                                <input type="number" name="net_weight" class="form-input" step="0.001" min="0" value="${data.net_weight || ''}" placeholder="0.000">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Gross Weight <small class="text-muted">kg</small></label>
                                <input type="number" name="gross_weight" class="form-input" step="0.001" min="0" value="${data.gross_weight || ''}" placeholder="0.000">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Stock & Ordering</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Minimum Stock *</label>
                                <input type="number" name="minimum_stock" class="form-input" value="${data.minimum_stock || ''}" min="0" placeholder="Enter minimum stock" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Maximum Stock *</label>
                                <input type="number" name="maximum_stock" class="form-input" value="${data.maximum_stock || ''}" min="0" placeholder="Enter maximum stock" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">BOM Qty *</label>
                                <input type="number" name="bom_quantity" class="form-input" value="${data.bom_quantity || ''}" min="0" step="0.01" placeholder="Enter BOM Quantity" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section" style="display:none;">
                        <div class="modal-section-title">Service & Lifetime</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Frequency Service <small class="text-muted">(Optional)</small></label>
                                <select name="frequency_service" class="form-input">
                                    ${serviceFrequenciesOptionsEdit}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Lifetime <small class="text-muted">(Optional)</small></label>
                                <div class="flex gap-2">
                                    <input type="number" name="lifetime" class="form-input flex-1" min="0" value="${lifetimeValue}" placeholder="0">
                                    <select name="lifetime_unit" class="form-input" style="width: 120px;">
                                        <option value="days" ${lifetimeUnit === 'days' ? 'selected' : ''}>Days</option>
                                        <option value="months" ${lifetimeUnit === 'months' ? 'selected' : ''}>Months</option>
                                        <option value="years" ${lifetimeUnit === 'years' ? 'selected' : ''}>Years</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section" id="edit-refill-specs-section" data-refill-specs-section>
                <div class="modal-section-title">Product Refill Specifications</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Brand Line <small class="text-muted">(Optional)</small></label>
                                <select name="brand_line" class="form-input" id="edit_brand_line_id" onchange="handleBrandLineChange(this)">
                                    ${brandLinesOptionsEdit}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Variant Name <small class="text-muted">(Optional)</small></label>
                                <select name="variant_name" class="form-input" id="edit_variant_name_id">
                                    ${productVariantsOptionsEdit}
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Packaging Size</label>
                                <select name="packaging_size_id" class="form-input" id="edit_packaging_size_id">
                                    ${packagingSizesOptionsEdit}
                                </select>
                                <input type="hidden" name="packaging_size" id="edit_packaging_size_hidden">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Status & Flags</div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="form-group">
                                <label class="form-checkbox">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" ${data.is_active ? 'checked' : ''}>
                                    <span>Is Active</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="form-checkbox">
                                    <input type="hidden" name="is_trading" value="0">
                                    <input type="checkbox" name="is_trading" value="1" ${data.is_trading ? 'checked' : ''}>
                                    <span>Is Trading</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="form-checkbox">
                                    <input type="hidden" name="is_stock_substitute" value="0">
                                    <input type="checkbox" name="is_stock_substitute" value="1" ${data.is_stock_substitute ? 'checked' : ''}>
                                    <span>Is Stock Substitute</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer for edit modal - use onclick instead of form attribute
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="editSubmitBtn">Update Product</button>
            `;
            
            // Attach submit handler to button
            document.getElementById('editSubmitBtn').addEventListener('click', function() {
                const form = document.getElementById('form');
                const formData = new FormData(form);
                
                // Debug log
                console.log('=== Edit Modal Submit ===');
                console.log('Form found:', !!form);
                
                // Explicitly capture file input
                const fileInput = form.querySelector('input[name="product_photo"]');
                console.log('File input found:', !!fileInput);
                if (fileInput && fileInput.files && fileInput.files.length > 0) {
                    console.log('File selected:', fileInput.files[0].name, fileInput.files[0].size);
                    formData.delete('product_photo');
                    formData.append('product_photo', fileInput.files[0]);
                } else {
                    console.log('No file selected');
                }
                
                // Log all form data
                for (let [key, value] of formData.entries()) {
                    if (value instanceof File) {
                        console.log(key + ': File - ' + value.name + ' (' + value.size + ' bytes)');
                    } else {
                        console.log(key + ':', value);
                    }
                }
                
                // Handle checkboxes
                ['is_active', 'is_trading', 'is_stock_substitute'].forEach(field => {
                    if (!formData.has(field)) {
                        formData.append(field, '0');
                    }
                });
                
                // Get product ID from hidden field
                const productId = formData.get('id');
                
                formData.append('_method', 'PUT');
                
                // Show loading
                this.disabled = true;
                this.textContent = 'Saving...';
                
                fetch('/warehouse/master-products/' + productId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    console.log('Server response:', result);
                    if (result.status === 'success') {
                        closeModal();
                        showSuccessModal(1, result.message || 'Produk berhasil diperbarui.');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        let errorMessage = result.message || 'Produk tidak berhasil diperbarui.';
                        if (result.errors) {
                            errorMessage += '\\n\\n' + Object.values(result.errors).flat().join('\\n');
                        }
                        showErrorModal(errorMessage);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal(error.message || 'Terjadi kesalahan jaringan.');
                })
                .finally(() => {
                    this.disabled = false;
                    this.textContent = 'Update Product';
                });
            });

            // Initialize custom selects (e.g. NiceSelect) for newly injected HTML
            if (window.NiceSelect) {
                document.querySelectorAll('#modalBody select').forEach(select => {
                    try {
                        if (!NiceSelect.get(select)) {
                            NiceSelect.bind(select, { searchable: true });
                        } else {
                            NiceSelect.get(select).update();
                        }
                    } catch (e) {
                        console.warn('NiceSelect bind error for edit:', e);
                    }
                });
            }

            const editUnitDisplay = document.getElementById('edit_unit_display');
            const editRefillSpecsSection = document.getElementById('edit-refill-specs-section');
            const editProductCategorySelect = document.getElementById('edit_product_category_id');
            
            if (editProductCategorySelect && editUnitDisplay) {
                // Set initial unit value - prioritize existing data, fallback to product type
                if (data.unit) {
                    editUnitDisplay.value = data.unit;
                } else {
                    // If no unit saved, try to get from selected product category
                    const selectedOption = editProductCategorySelect.options[editProductCategorySelect.selectedIndex];
                    if (selectedOption && selectedOption.value) {
                        const unitFromCategory = selectedOption.getAttribute('data-unit');
                        if (unitFromCategory) {
                            editUnitDisplay.value = unitFromCategory;
                        }
                    }
                }
                    
                // Set unit_order from unit if not set
                const editUnitOrderDisplay = document.getElementById('edit_unit_order_display');
                if (editUnitOrderDisplay) {
                    editUnitOrderDisplay.value = data.unit_order || editUnitDisplay.value || '';
                }
                
                // Set up cascading variant dropdown for edit modal
                const editBrandLineSelect = document.getElementById('edit_brand_line_id');
                const editVariantNameSelect = document.getElementById('edit_variant_name_id');
                
                if (editBrandLineSelect && editVariantNameSelect) {
                    console.log('Edit Modal: Brand line listener handled via global function handleBrandLineChange');
                }
                
                // Check initial state for refill specs
                toggleRefillSpecsSection(editProductCategorySelect, editRefillSpecsSection);
                
                // Add change event listener
                editProductCategorySelect.addEventListener('change', function() {
                    toggleRefillSpecsSection(this, editRefillSpecsSection);
                    
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption && selectedOption.value) {
                        const unitValue = selectedOption.getAttribute('data-unit');
                        if (unitValue) {
                            // Auto-fill unit from product category
                            editUnitDisplay.value = unitValue;
                            
                            // Auto-fill unit_order from product category unit
                            const editUnitOrderDisplay = document.getElementById('edit_unit_order_display');
                            if (editUnitOrderDisplay) {
                                editUnitOrderDisplay.value = unitValue;
                            }
                        }
                    } else {
                        editUnitDisplay.value = '';
                    }
                });
            }
            
            // Set variant name dropdown value
            const editVariantNameSelect = document.getElementById('edit_variant_name_id');
            if (editVariantNameSelect && data.variant_name) {
                editVariantNameSelect.value = data.variant_name;
            }
            
            // Set packaging size dropdown value
            const editPackagingSizeSelect = document.getElementById('edit_packaging_size_id');
            const editPackagingSizeHidden = document.getElementById('edit_packaging_size_hidden');
            
            if (editPackagingSizeSelect && data.packaging_size_id) {
                editPackagingSizeSelect.value = data.packaging_size_id;
                // Set hidden field with packaging size name
                const selectedOption = editPackagingSizeSelect.options[editPackagingSizeSelect.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    editPackagingSizeHidden.value = selectedOption.text.split(' (')[0];
                }
            }
            
            // Add change event listener for edit packaging size dropdown
            if (editPackagingSizeSelect) {
                editPackagingSizeSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption.value) {
                        editPackagingSizeHidden.value = selectedOption.text.split(' (')[0];
                    } else {
                        editPackagingSizeHidden.value = '';
                    }
                });
            }
            
            // If product category is already selected, filter product types on load
            if (editProductCategorySelect && data.product_category_id) {
                // Update refill specs section visibility
                toggleRefillSpecsSection(editProductCategorySelect, editRefillSpecsSection);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Gagal memuat detail.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            `;
        });
}

function openDuplicateModal(id) {
    console.log('openDuplicateModal called with id:', id);
    openModal('Duplicate Product');
    console.log('openModal called');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    Promise.all([
        fetch(`/warehouse/master-products/${id}/edit`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        }),
        fetch('/warehouse/product-categories', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => response.json())
    ])
    .then(([editResponse, categoriesResponse]) => {
        if (!editResponse.status || editResponse.status !== 'success') {
            throw new Error('Data tidak berhasil dimuat');
        }
        const data = editResponse.data;
        // Clear specific fields for duplication
        data.sku = ''; // Clear SKU to auto-generate
        data.product_photo = null; // Don't copy photo for now
        
        const productCategories = categoriesResponse.data || [];
        const packagingSizes = editResponse.packagingSizes || [];
        const brandLines = editResponse.brandLines || null;
        const productVariants = editResponse.productVariants || null;
        const unitOptions = editResponse.unitOptions || null;
        const serviceFrequencies = editResponse.serviceFrequencies || [];
        const unitHelperOptions = editResponse.unitHelperOptions || {};
        
        // Build units object
        const units = {};
        if (unitOptions && unitOptions.option_details) {
            unitOptions.option_details.forEach(unit => {
                units[unit.option_name] = unit.option_name;
            });
        }
        
        // Build options (reuse logic from edit)
        let serviceFrequenciesOptions = '<option value="">Select Frequency Service</option>';
        if (serviceFrequencies && serviceFrequencies.length > 0) {
            serviceFrequencies.forEach(freq => {
                const selected = data.frequency_service === freq.code ? 'selected' : '';
                serviceFrequenciesOptions += `<option value="${freq.code}" ${selected}>${freq.name} (${freq.frequency_description})</option>`;
            });
        }
        
        // Build brand lines options
        let brandLinesOptions = '<option value="">Select Brand Line</option>';
        if (brandLines && brandLines.option_details) {
            brandLines.option_details.forEach(brandLine => {
                const selected = data.brand_line === brandLine.option_name ? 'selected' : '';
                brandLinesOptions += `<option value="${brandLine.option_name}" ${selected}>${brandLine.option_name}</option>`;
            });
        }
        
        // Build product variants options
        let productVariantsOptions = '<option value="">Select Variant Name</option>';
        if (productVariants && productVariants.option_details) {
            productVariants.option_details.forEach(variant => {
                const selected = data.variant_name === variant.option_name ? 'selected' : '';
                productVariantsOptions += `<option value="${variant.option_name}" ${selected}>${variant.option_name}</option>`;
            });
        }
        
        // Build packaging sizes options
        let packagingSizesOptions = '<option value="">Select Packaging Size</option>';
        if (packagingSizes && packagingSizes.length > 0) {
            packagingSizes.forEach(packagingSize => {
                const selected = data.packaging_size_id == packagingSize.id ? 'selected' : '';
                packagingSizesOptions += `<option value="${packagingSize.id}" ${selected}>${packagingSize.name} (${packagingSize.code})</option>`;
            });
        }
        
        // Parse lifetime
        let lifetimeValue = data.lifetime || '';
        let lifetimeUnit = 'days';
        if (lifetimeValue && lifetimeValue > 0) {
            if (lifetimeValue >= 365) {
                lifetimeUnit = 'years';
                lifetimeValue = Math.round(lifetimeValue / 365);
            } else if (lifetimeValue >= 30) {
                lifetimeUnit = 'months';
                lifetimeValue = Math.round(lifetimeValue / 30);
            }
        }

        document.getElementById('modalBody').innerHTML = `
            <form id="form" enctype="multipart/form-data">
                <div class="modal-section">
                    <div class="modal-section-title">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="name" class="form-input" value="${data.name || ''} (Copy)" placeholder="Enter product name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">SKU Code</label>
                            <input type="text" name="sku" class="form-input" value="" placeholder="Auto-generated">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Part Number <small class="text-muted">(Optional)</small></label>
                            <input type="text" name="part_no" class="form-input" value="${data.part_no || ''}" placeholder="Part number">
                        </div>
                        <div class="form-group md:col-span-2">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input form-textarea" placeholder="Enter product description">${data.description || ''}</textarea>
                        </div>
                        <div class="form-group md:col-span-2">
                            <label class="form-label">Description 2 <small class="text-muted">(Optional)</small></label>
                            <textarea name="description_2" class="form-input form-textarea" placeholder="Enter additional description">${data.description_2 || ''}</textarea>
                        </div>
                        <div class="form-group md:col-span-2">
                            <label class="form-label">Product Photo <small class="text-muted">(Optional)</small></label>
                            <div class="photo-upload-container">
                                <input type="file" name="product_photo" id="dup_product_photo_input" class="form-input" accept="image/*" onchange="previewPhoto(this, 'dup_photo_preview')">
                                <div id="dup_photo_preview" class="photo-preview-box" style="margin-top: 10px; display: none;">
                                    <img id="dup_photo_preview_img" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Category</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Product Category *</label>
                            <select name="product_category_id" class="form-input" id="dup_product_category_id" required onchange="handleCategoryChange(this)">
                                <option value="">Select Product Category</option>
                                ${productCategories.map(category => 
                                    `<option value="${category.id}" data-is-unit="${category.is_unit ? 1 : 0}" data-unit="${category.unit || ''}" ${data.product_category_id == category.id ? 'selected' : ''}>${category.name}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-input" id="dup_unit_display" value="${data.unit || ''}" readonly placeholder="Auto-filled from Category">
                            <small class="text-muted">Unit diambil otomatis dari Category yang dipilih</small>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Dimensions & Weight</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="form-group">
                            <label class="form-label">Dimensions (P × L × T) <small class="text-muted">cm</small></label>
                            <input type="text" name="dimensions" class="form-input" value="${data.dimensions || ''}" placeholder="10 × 20 × 30">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Net Weight <small class="text-muted">kg</small></label>
                            <input type="number" name="net_weight" class="form-input" step="0.001" min="0" value="${data.net_weight || ''}" placeholder="0.000">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gross Weight <small class="text-muted">kg</small></label>
                            <input type="number" name="gross_weight" class="form-input" step="0.001" min="0" value="${data.gross_weight || ''}" placeholder="0.000">
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Stock & Ordering</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Minimum Stock *</label>
                            <input type="number" name="minimum_stock" class="form-input" value="${data.minimum_stock || ''}" min="0" placeholder="Enter minimum stock" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Maximum Stock *</label>
                            <input type="number" name="maximum_stock" class="form-input" value="${data.maximum_stock || ''}" min="0" placeholder="Enter maximum stock" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">BOM Qty</label>
                            <input type="number" name="bom_quantity" class="form-input" value="${data.bom_quantity || ''}" min="0" step="0.01" placeholder="Enter BOM Quantity">
                        </div>
                    </div>
                </div>
                
                <div class="modal-section" style="display:none;">
                    <div class="modal-section-title">Service & Lifetime</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Frequency Service <small class="text-muted">(Optional)</small></label>
                            <select name="frequency_service" class="form-input">
                                ${serviceFrequenciesOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Lifetime <small class="text-muted">(Optional)</small></label>
                            <div class="flex gap-2">
                                <input type="number" name="lifetime" class="form-input flex-1" min="0" value="${lifetimeValue}" placeholder="0">
                                <select name="lifetime_unit" class="form-input" style="width: 120px;">
                                    <option value="days" ${lifetimeUnit === 'days' ? 'selected' : ''}>Days</option>
                                    <option value="months" ${lifetimeUnit === 'months' ? 'selected' : ''}>Months</option>
                                    <option value="years" ${lifetimeUnit === 'years' ? 'selected' : ''}>Years</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section" id="dup-refill-specs-section" data-refill-specs-section>
                    <div class="modal-section-title">Product Refill Specifications</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Brand Line <small class="text-muted">(Optional)</small></label>
                            <select name="brand_line" class="form-input" id="dup_brand_line_id" onchange="handleBrandLineChange(this)">
                                ${brandLinesOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Variant Name <small class="text-muted">(Optional)</small></label>
                            <select name="variant_name" class="form-input" id="dup_variant_name_id">
                                ${productVariantsOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Packaging Size</label>
                            <select name="packaging_size_id" class="form-input" id="dup_packaging_size_id">
                                ${packagingSizesOptions}
                            </select>
                            <input type="hidden" name="packaging_size" id="dup_packaging_size_hidden">
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Status & Flags</div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="form-group">
                            <label class="form-checkbox">
                                <input type="checkbox" name="is_active" value="1" ${data.is_active ? 'checked' : ''}>
                                <span>Is Active</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="form-checkbox">
                                <input type="checkbox" name="is_trading" value="1" ${data.is_trading ? 'checked' : ''}>
                                <span>Is Trading</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="form-checkbox">
                                <input type="checkbox" name="is_stock_substitute" value="1" ${data.is_stock_substitute ? 'checked' : ''}>
                                <span>Is Stock Substitute</span>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        `;
        
        // Add modal footer
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" id="dupSubmitBtn">Create Product</button>
        `;
        
        // Attach event listeners for product type change
        const productTypeSelect = document.getElementById('dup_product_type_id');
        const unitDisplay = document.getElementById('dup_unit_display');
        
        if (productTypeSelect && unitDisplay) {
            productTypeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const defaultUnit = selectedOption.getAttribute('data-unit');
                
                console.log('Duplicate modal product type changed, setting unit to:', defaultUnit);
                unitDisplay.value = defaultUnit || '';
            });
            
            // Trigger initial change to auto-fill unit
            productTypeSelect.dispatchEvent(new Event('change'));
        }

        const dupProductCategorySelect = document.getElementById('dup_product_category_id');
        const dupRefillSpecsSection = document.getElementById('dup-refill-specs-section');
        if (dupProductCategorySelect) {
            toggleRefillSpecsSection(dupProductCategorySelect, dupRefillSpecsSection);
            dupProductCategorySelect.addEventListener('change', function() {
                toggleRefillSpecsSection(this, dupRefillSpecsSection);
            });
        }
        
        // Attach submit handler
        document.getElementById('dupSubmitBtn').addEventListener('click', function() {
            const form = document.getElementById('form');
            const formData = new FormData(form);
            
            // Explicitly capture file input
            const fileInput = form.querySelector('input[name="product_photo"]');
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                formData.delete('product_photo');
                formData.append('product_photo', fileInput.files[0]);
            }
            
            // Handle checkboxes
            ['is_active', 'is_trading', 'is_stock_substitute'].forEach(field => {
                if (!formData.has(field)) {
                    formData.append(field, '0');
                }
            });
            
            // Show loading
            this.disabled = true;
            this.textContent = 'Creating...';
            
            fetch('/warehouse/master-products', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    closeModal();
                    showSuccessModal(1, result.message || 'Produk berhasil diduplikasi.');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    let errorMessage = result.message || 'Produk tidak berhasil diduplikasi.';
                    if (result.errors) {
                        errorMessage += '\\n\\n' + Object.values(result.errors).flat().join('\\n');
                    }
                    showErrorModal(errorMessage);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal(error.message || 'Terjadi kesalahan jaringan.');
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = 'Create Product';
            });
        });
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading data for duplication.</div>';
    });
}



// Delete Modal functions
function openDeleteModal(id = null) {
    if (id) {
        selectedIdsForRetry = [id];
    }
    
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to deactivate this product? This action can be undone later.'
        : `Are you sure you want to deactivate ${count} products? This action can be undone later.`;
    
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
    
    if (!selectedIdsForRetry || selectedIdsForRetry.length === 0) {
        showErrorModal('Tidak ada produk yang dipilih.');
        return;
    }
    
    console.log('Sending bulk delete request with IDs:', selectedIdsForRetry);
    console.log('Request body:', JSON.stringify({ product_ids: selectedIdsForRetry }));
    
    fetch('/warehouse/master-products/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ product_ids: selectedIdsForRetry })
    })
    .then(async response => {
        // Get response text first to handle both JSON and non-JSON responses
        const responseText = await response.text();
        console.log('Response status:', response.status);
        console.log('Response text:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            // If JSON parse fails, throw error with response text
            console.error('JSON parse error:', parseError);
            throw new Error(`Server returned non-JSON response (${response.status}): ${responseText.substring(0, 200)}`);
        }
        
        // If response is not OK, throw error with server message
        if (!response.ok) {
            let errorMessage = data.message || 'Produk tidak berhasil dinonaktifkan.';
            console.error('Error response data:', data);
            
            // Include errors array if available
            if (data.errors) {
                if (Array.isArray(data.errors)) {
                    const errorList = data.errors.map((err, idx) => {
                        // Handle both string errors and object errors (validation)
                        const errText = typeof err === 'string' ? err : (err[0] || JSON.stringify(err));
                        return `${idx + 1}. ${errText}`;
                    }).join('\n');
                    errorMessage += '\n\nDetail:\n' + errorList;
                } else if (typeof data.errors === 'object') {
                    // Handle Laravel validation errors format
                    const validationErrors = Object.keys(data.errors).map(key => {
                        return `${key}: ${Array.isArray(data.errors[key]) ? data.errors[key].join(', ') : data.errors[key]}`;
                    }).join('\n');
                    errorMessage += '\n\nValidasi Gagal:\n' + validationErrors;
                }
            }
            
            throw new Error(errorMessage);
        }
        return data;
    })
    .then(result => {
        if (result.status === 'success' || result.status === 'partial') {
            showSuccessModal(result.count, result.message);
            // Reload page after success to show updated list
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        } else {
            let errorMessage = result.message || 'Produk tidak berhasil dinonaktifkan.';
            if (result.errors && Array.isArray(result.errors)) {
                // Show all errors, not just first 3
                const errorList = result.errors.map((err, idx) => `${idx + 1}. ${err}`).join('\n');
                errorMessage += '\n\nDetail:\n' + errorList;
            }
            showErrorModal(errorMessage);
        }
    })
    .catch(error => {
        console.error('Error details:', {
            message: error.message,
            error: error,
            stack: error.stack
        });
        
        // Show detailed error message
        let errorMessage = error.message || 'Terjadi kesalahan jaringan.';
        showErrorModal(errorMessage);
    });
}

// Bulk operations
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Perhatian', 'Silakan pilih minimal satu produk yang ingin dinonaktifkan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => {
        const value = cb.value;
        // Ensure value is a number, not string
        const numValue = parseInt(value, 10);
        if (isNaN(numValue)) {
            console.warn('Invalid checkbox value:', value);
            return null;
        }
        return numValue;
    }).filter(id => id !== null);
    
    if (selectedIdsForRetry.length === 0) {
        showWarningDialog('Perhatian', 'Tidak ada ID produk valid yang ditemukan. Silakan coba lagi.');
        return;
    }
    
    console.log('Selected product IDs for deletion:', selectedIdsForRetry);
    openDeleteModal();
}

// Success Modal functions
function showSuccessModal(count, customMessage = null) {
    const message = customMessage || (count === 1 
        ? 'Produk berhasil dinonaktifkan.'
        : `${count} produk berhasil dinonaktifkan.`);
    
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Remove deleted items from the table immediately
    selectedIdsForRetry.forEach(id => {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) {
            row.remove();
        }
    });
    
    // Reset checkboxes
    document.querySelectorAll('.row-checkbox:checked').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    document.getElementById('headerSelectAll').checked = false;
    
    // Auto close after 3 seconds
    successModalTimer = setTimeout(() => {
        closeSuccessModal();
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
    const errorMsg = message || 'Produk tidak berhasil dinonaktifkan. Silakan coba lagi.';
    // Replace newline characters with <br> for better display
    const errorElement = document.getElementById('errorMessage');
    // Use textContent and CSS white-space: pre-line to preserve line breaks
    errorElement.textContent = errorMsg;
    errorElement.style.whiteSpace = 'pre-line';
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

// Handle packaging size dropdown
document.addEventListener('DOMContentLoaded', function() {
    // For create modal
    const packagingSizeSelect = document.getElementById('packaging_size_id');
    const packagingSizeHidden = document.getElementById('packaging_size_hidden');
    
    if (packagingSizeSelect) {
        packagingSizeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                packagingSizeHidden.value = selectedOption.text.split(' (')[0]; // Get name without code
            } else {
                packagingSizeHidden.value = '';
            }
        });
    }
    
    // For edit modal - this will be handled when modal is opened
});

// Import Modal functions
function openImportModal() {
    openModal('Import Products from CSV');
    document.getElementById('modalBody').innerHTML = `
        <form id="importForm" onsubmit="previewImportForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">CSV File Import</div>
                <div class="grid grid-cols-1 gap-6">
                    <div class="form-group">
                        <label class="form-label">Select CSV File *</label>
                        <input type="file" name="file" id="csvFileInput" class="form-input" accept=".csv,.txt" required onchange="handleFileSelect(event)">
                        <small class="text-muted">Silakan pilih file CSV berisi data produk. Ukuran file maksimum 10MB.</small>
                    </div>
                    <div id="previewSection" style="display: none;">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold text-blue-900 mb-2">Hasil Preview:</h4>
                            <div id="previewContent"></div>
                        </div>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-900 mb-2">📋 CSV Format Requirements:</h4>
                        <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                            <li>File must contain header row with column names</li>
                            <li><strong>Required columns:</strong> ProductCode, ProductName, ProductType, ProductCategory, Unit</li>
                            <li><strong>Optional columns:</strong> PartNo, NetWeight, GrossWeight, LifeTime, UnitOrder, FrequencyService, FgTrading, FgStockSubstitute</li>
                            <li>Data will be imported in batches of 50 rows</li>
                            <li><strong>Products with existing SKU will be automatically skipped</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="button" class="btn btn-info" onclick="previewImportForm(event)" id="previewBtn" disabled>Preview</button>
        <button type="button" class="btn btn-primary" onclick="confirmImport()" id="confirmImportBtn" style="display: none;">Confirm Import</button>
    `;
}

function handleFileSelect(event) {
    const fileInput = event.target;
    const previewBtn = document.getElementById('previewBtn');
    
    if (fileInput.files && fileInput.files.length > 0) {
        previewBtn.disabled = false;
        // Clear previous preview
        document.getElementById('previewSection').style.display = 'none';
        document.getElementById('confirmImportBtn').style.display = 'none';
    } else {
        previewBtn.disabled = true;
    }
}

function previewImportForm(event) {
    event.preventDefault();
    
    const form = document.getElementById('importForm');
    const formData = new FormData(form);
    const previewBtn = document.getElementById('previewBtn');
    const confirmBtn = document.getElementById('confirmImportBtn');
    const previewSection = document.getElementById('previewSection');
    const previewContent = document.getElementById('previewContent');
    
    if (!formData.get('file')) {
        showWarningDialog('Perhatian', 'Silakan pilih file CSV terlebih dahulu.');
        return;
    }
    
    // Show loading
    previewBtn.disabled = true;
    previewBtn.textContent = 'Memuat...';
    previewContent.innerHTML = '<div class="text-center py-4"><div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><p class="mt-2 text-sm">Menganalisis file CSV...</p></div>';
    previewSection.style.display = 'block';
    
    fetch('/warehouse/master-products/import-preview', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        previewBtn.disabled = false;
        previewBtn.textContent = 'Preview';
        
        if (result.status === 'success') {
            const preview = result.preview;
            
            let previewHtml = `
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="bg-white rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-gray-700">${preview.total_rows}</div>
                        <div class="text-sm text-gray-600">Total Baris</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 text-center border border-green-200">
                        <div class="text-2xl font-bold text-green-600">${preview.new}</div>
                        <div class="text-sm text-green-700">Produk Baru</div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-3 text-center border border-yellow-200">
                        <div class="text-2xl font-bold text-yellow-600">${preview.existing}</div>
                        <div class="text-sm text-yellow-700">Sudah Ada (Dilewati)</div>
                    </div>
                </div>
            `;
            
            if (preview.preview_data && preview.preview_data.length > 0) {
                previewHtml += `
                    <div class="mt-4">
                        <h5 class="font-semibold mb-2">Preview Contoh (10 baris pertama):</h5>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs border border-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-2 py-1 border">Baris</th>
                                        <th class="px-2 py-1 border">SKU</th>
                                        <th class="px-2 py-1 border">Nama Produk</th>
                                        <th class="px-2 py-1 border">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                
                preview.preview_data.forEach(item => {
                    const statusClass = item.exists ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
                    const statusText = item.exists ? 'Sudah Ada' : 'Baru';
                    previewHtml += `
                        <tr>
                            <td class="px-2 py-1 border">${item.row}</td>
                            <td class="px-2 py-1 border font-mono">${item.sku}</td>
                            <td class="px-2 py-1 border">${item.name}</td>
                            <td class="px-2 py-1 border"><span class="px-2 py-1 rounded text-xs ${statusClass}">${statusText}</span></td>
                        </tr>
                    `;
                });
                
                previewHtml += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }
            
            if (preview.errors && preview.errors.length > 0) {
                previewHtml += `
                    <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3">
                        <h5 class="font-semibold text-red-900 mb-2">Error Ditemukan:</h5>
                        <ul class="text-sm text-red-800 space-y-1 list-disc list-inside">
                `;
                preview.errors.forEach(error => {
                    previewHtml += `<li>${error}</li>`;
                });
                previewHtml += `</ul></div>`;
            }
            
            previewContent.innerHTML = previewHtml;
            
            // Show confirm button if there are new products
            if (preview.new > 0) {
                confirmBtn.style.display = 'inline-block';
                window.importFormData = formData; // Store for later use
            } else {
                confirmBtn.style.display = 'none';
                previewContent.innerHTML += '<div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center"><p class="text-yellow-800">Tidak ada produk baru untuk diimpor. Semua produk sudah ada di database.</p></div>';
            }
        } else {
            previewContent.innerHTML = `<div class="text-red-600">Gagal: ${result.message || 'Preview file tidak berhasil.'}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        previewBtn.disabled = false;
        previewBtn.textContent = 'Preview';
        previewContent.innerHTML = `<div class="text-red-600">Gagal: ${error.message}</div>`;
    });
}

function confirmImport() {
    if (!window.importFormData) {
        showWarningDialog('Perhatian', 'Silakan preview file terlebih dahulu.');
        return;
    }
    
    const confirmBtn = document.getElementById('confirmImportBtn');
    const originalText = confirmBtn.textContent;
    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Mengimpor...';
    
    fetch('/warehouse/master-products/import', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: window.importFormData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            closeModal();
            console.log('v1.1 - Import success');
            showSuccessDialog('Berhasil', 'Import selesai.\n\nBerhasil: ' + result.stats.success + '\nDilewati (sudah ada): ' + result.stats.failed + '\nTotal diproses: ' + result.stats.total);
            window.location.reload();
        } else {
            showErrorDialog('Gagal', result.message || 'Import tidak berhasil.');
            confirmBtn.disabled = false;
            confirmBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', error.message || 'Import tidak berhasil.');
        confirmBtn.disabled = false;
        confirmBtn.textContent = originalText;
    });
}

function submitImportForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Mengimpor...';
    
    fetch('/warehouse/master-products/import', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            closeModal();
            showSuccessDialog('Berhasil', `Import selesai.\nBerhasil: ${result.stats.success}\nGagal: ${result.stats.failed}\nTotal: ${result.stats.total}`);
            window.location.reload();
        } else {
            showErrorDialog('Gagal', result.message || 'Import tidak berhasil.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', error.message || 'Import tidak berhasil.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

// Function to handle packaging size in edit modal
function setPackagingSizeForEdit(packagingSizeId, packagingSizeName) {
    const editSelect = document.getElementById('edit_packaging_size_id');
    const editHidden = document.getElementById('edit_packaging_size_hidden');
    
    if (editSelect && editHidden) {
        editSelect.value = packagingSizeId || '';
        editHidden.value = packagingSizeName || '';
    }
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

