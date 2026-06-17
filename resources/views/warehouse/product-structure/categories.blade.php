@extends('layouts.app')

@section('title', 'Product Categories')
@section('breadcrumb', 'Home / Warehouse / Product Structure / Categories')

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

    /* Table Header */
    .table-header {
        background: linear-gradient(135deg, #214589 0%, #1e3a8a 100%);
        color: white;
        padding: 20px 24px;
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .table-title {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .table-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    /* Table Wrapper */
    .table-wrapper {
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }

    /* Data Table */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        font-size: 14px;
        min-width: 1800px;
        table-layout: auto;
    }

    .data-table th:nth-child(1) { width: 300px; min-width: 300px; }
    .data-table th:nth-child(2) { width: 120px; }

    .data-table thead th {
        background-color: #f8fafc;
        color: #374151;
        font-weight: 600;
        padding: 16px 12px;
        text-align: left;
        border-bottom: 2px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 10;
        white-space: nowrap;
    }

    .data-table tbody tr {
        border-bottom: 1px solid #e5e7eb;
        transition: background-color 0.2s ease;
    }

    .data-table tbody tr:hover {
        background-color: #f9fafb;
    }

    .data-table tbody tr:last-child {
        border-bottom: none;
    }

    .data-table td {
        padding: 16px 12px;
        vertical-align: top;
        white-space: nowrap;
    }

    /* Badge Styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-info {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .badge-secondary {
        background-color: #f3f4f6;
        color: #374151;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 48px;
        color: #d1d5db;
        margin-bottom: 16px;
    }

    .empty-state h5 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    }

    .empty-state p {
        font-size: 14px;
        margin-bottom: 24px;
    }

    /* Pagination */
    .pagination-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        background-color: #f8fafc;
        border-top: 1px solid #e5e7eb;
        border-radius: 0 0 10px 10px;
    }

    .pagination-info {
        color: #6b7280;
        font-size: 14px;
    }

    .pagination-controls {
        display: flex;
        gap: 8px;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        display: flex !important;
        flex-direction: column !important;
        transform: scale(0.9);
        transition: transform 0.3s ease;
        position: relative !important;
        overflow: hidden !important;
    }

    .modal-overlay.show .modal-content {
        transform: scale(1);
    }

    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
        color: #111827;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }

    .modal-close:hover {
        background-color: #f3f4f6;
        color: #374151;
    }

    .modal-body {
        padding: 24px !important;
        overflow-y: auto !important;
        flex: 1 !important;
        max-height: calc(90vh - 160px) !important;
        position: relative !important;
        z-index: 1 !important;
    }

    .modal-footer {
        padding: 20px 24px !important;
        border-top: 2px solid #e5e7eb !important;
        display: flex !important;
        justify-content: flex-end !important;
        gap: 12px !important;
        flex-shrink: 0 !important;
        background-color: #f8f9fa !important;
        border-radius: 0 0 8px 8px !important;
        min-height: 80px !important;
        position: relative !important;
        z-index: 999 !important;
        margin-top: 0 !important;
        clear: both !important;
    }

    .modal-footer .btn {
        min-width: 100px !important;
        font-weight: 500 !important;
        padding: 10px 20px !important;
        border-radius: 6px !important;
        font-size: 14px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
    }

    .modal-footer .btn-secondary {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        color: white !important;
    }

    .modal-footer .btn-secondary:hover {
        background-color: #5a6268 !important;
        border-color: #545b62 !important;
    }

    .modal-footer .btn-primary {
        background-color: #214589 !important;
        border-color: #214589 !important;
        color: white !important;
    }

    .modal-footer .btn-primary:hover {
        background-color: #1e3a8a !important;
        border-color: #1e3a8a !important;
    }

    /* Force modal footer visibility - SPECIFIC TARGETING */
    #categoryModal .modal-footer {
        position: relative !important;
        z-index: 9999 !important;
        margin-top: 0 !important;
        border-top: 3px solid #214589 !important;
        background-color: #ffffff !important;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1) !important;
    }

    /* Ensure buttons are visible */
    #categoryModal .modal-footer .btn {
        opacity: 1 !important;
        visibility: visible !important;
        display: inline-flex !important;
        position: relative !important;
        z-index: 10000 !important;
    }

    /* Fix color picker interference */
    #categoryModal .color-picker {
        z-index: 1 !important;
        position: relative !important;
    }

    /* Ensure modal content structure */
    #categoryModal .modal-content {
        display: flex !important;
        flex-direction: column !important;
        height: auto !important;
        max-height: 90vh !important;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    /* Fix form overflow issues */
    #categoryModal .modal-body .form-group:last-of-type {
        margin-bottom: 0 !important;
    }

    #categoryModal .form-check {
        margin-bottom: 0 !important;
        padding-bottom: 10px !important;
    }

    /* Ensure proper spacing before footer */
    #categoryModal .modal-body {
        padding-bottom: 10px !important;
    }

    .form-label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background-color: white;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 8px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 32px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-check-input {
        width: 16px;
        height: 16px;
        border: 1px solid #d1d5db;
        border-radius: 3px;
        cursor: pointer;
    }

    .form-check-input:checked {
        background-color: #214589;
        border-color: #214589;
    }

    .form-check-label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
    }

    /* Form Control Plaintext */
    .form-control-plaintext {
        display: block;
        width: 100%;
        padding: 0.375rem 0;
        margin-bottom: 0;
        line-height: 1.5;
        color: #495057;
        background-color: transparent;
        border: solid transparent;
        border-width: 1px 0;
        font-size: 14px;
        min-height: 38px;
        display: flex;
        align-items: center;
    }

    .form-control-plaintext:focus {
        outline: none;
    }

    /* Color Picker */
    .color-picker {
        width: 100% !important;
        height: 40px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        background: none !important;
        padding: 0 !important;
        position: relative !important;
        z-index: 1 !important;
        overflow: hidden !important;
    }

    /* Icon Picker */
    .icon-picker-container {
        display: flex;
        gap: 8px;
    }

    .icon-picker-container input {
        flex: 1;
    }

    .icon-picker-container button {
        white-space: nowrap;
    }

    .icon-picker-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .icon-picker-modal.show {
        opacity: 1;
        visibility: visible;
    }

    .icon-picker-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        display: flex;
        flex-direction: column;
    }

    .icon-picker-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .icon-picker-body {
        padding: 20px 24px;
        overflow-y: auto;
        flex: 1;
    }

    .icon-picker-footer {
        padding: 20px 24px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background-color: #f8f9fa;
    }

    .icon-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        gap: 12px;
        max-height: 400px;
        overflow-y: auto;
    }

    .icon-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 12px 8px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: white;
    }

    .icon-item:hover {
        border-color: #214589;
        background-color: #f0f4ff;
    }

    .icon-item.selected {
        border-color: #214589;
        background-color: #214589;
        color: white;
    }

    .icon-item i {
        font-size: 20px;
        margin-bottom: 4px;
    }

    .icon-item span {
        font-size: 10px;
        text-align: center;
        word-break: break-all;
    }

    /* Hierarchy Indentation */
    .hierarchy-indent {
        display: inline-block;
        width: 20px;
        height: 1px;
    }

    .hierarchy-indent.level-1 { margin-left: 20px; }
    .hierarchy-indent.level-2 { margin-left: 40px; }
    .hierarchy-indent.level-3 { margin-left: 60px; }
    .hierarchy-indent.level-4 { margin-left: 80px; }

    /* Icon Display */
    .category-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        font-size: 16px;
        margin-right: 8px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .table-header {
            flex-direction: column;
            align-items: stretch;
        }

        .table-actions {
            justify-content: center;
        }

        .pagination-wrapper {
            flex-direction: column;
            gap: 16px;
            text-align: center;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Table Container -->
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">
                        <i class="fas fa-sitemap"></i>
                        Product Categories
                    </h3>
                    <div class="table-actions">
                        <button class="btn btn-primary" onclick="openAddModal()">
                            <i class="fas fa-plus"></i>
                            Add Category
                        </button>
                    </div>
                </div>

                <div class="table-wrapper">
                    @if($categories->count() > 0)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th data-column="name">Category</th>
                                    <th data-column="code">Code</th>
                                    <th data-column="parent__name">Parent</th>
                                    <th data-column="sort_order" data-type="numeric">Sort Order</th>
                                    <th data-no-filter>Products</th>
                                    <th data-column="sku_prefix">SKU Prefix</th>
                                    <th data-column="unit">Unit</th>
                                    <th data-column="has_serial_number">Has SN</th>
                                    <th data-column="is_unit">Is Unit</th>
                                    <th data-column="is_active">Status</th>
                                    <th data-column="createdBy__name">Created By</th>
                                    <th data-column="created_at" data-type="date">Created At</th>
                                    <th data-column="updatedBy__name">Last Updated By</th>
                                    <th data-column="updated_at" data-type="date">Last Updated At</th>
                                    <th data-no-filter>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                <tr onclick="showCategory({{ $category->id }})" style="cursor: pointer;">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($category->icon)
                                                <div class="category-icon" style="background-color: {{ $category->color ?? '#e5e7eb' }}; color: white;">
                                                    <i class="{{ $category->icon }}"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-weight-bold">{{ $category->name }}</div>
                                                @if($category->description)
                                                    <small class="text-muted">{{ Str::limit($category->description, 50) }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $category->code }}</span>
                                    </td>
                                    <td>
                                        @if($category->parent)
                                            <span class="badge badge-secondary">{{ $category->parent->name }}</span>
                                        @else
                                            <span class="badge badge-primary">Parent</span>
                                        @endif
                                    </td>
                                    <td>{{ $category->sort_order }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $category->master_products_count }}</span>
                                    </td>
                                    <td>{{ $category->sku_prefix ?? '-' }}</td>
                                    <td>{{ $category->unit ?? '-' }}</td>
                                    <td>
                                        @if($category->effective_has_serial_number)
                                            <span class="badge badge-success">Yes</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($category->is_unit)
                                            <span class="badge badge-success">Yes</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($category->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $category->createdBy->name ?? '-' }}</td>
                                    <td>
                                        @if($category->created_at)
                                            {{ \Carbon\Carbon::parse($category->created_at)->format('d/M/Y') }}<br>
                                            <small class="text-muted">at {{ \Carbon\Carbon::parse($category->created_at)->format('H.i') }} WIB</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $category->updatedBy->name ?? '-' }}</td>
                                    <td>
                                        @if($category->updated_at)
                                            {{ \Carbon\Carbon::parse($category->updated_at)->format('d/M/Y') }}<br>
                                            <small class="text-muted">at {{ \Carbon\Carbon::parse($category->updated_at)->format('H.i') }} WIB</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCategory({{ $category->id }})" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-sitemap"></i>
                            <h5>No Product Categories Found</h5>
                            <p>Start by creating your first product category.</p>
                            <button class="btn btn-primary" onclick="openAddModal()">
                                <i class="fas fa-plus"></i>
                                Add First Category
                            </button>
                        </div>
                    @endif
                </div>

                @if($categories->count() > 0)
                <!-- Pagination -->
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        <span class="info-text">
                            Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} 
                            of {{ $categories->total() }} entries
                        </span>
                    </div>
                    <div class="pagination-controls">
                        {{ $categories->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div class="modal-overlay" id="categoryModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Add Product Category</h5>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="categoryForm" onsubmit="return submitCategoryForm(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="code" name="code" required>
                </div>
                
                <div class="form-group">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="parent_id" class="form-label">Parent Category</label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="">Select Parent Category</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sort_order" class="form-label">Sort Order <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" value="0" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="icon" class="form-label">Icon</label>
                            <div class="icon-picker-container">
                                <input type="text" class="form-control" id="icon" name="icon" placeholder="fas fa-tag" readonly>
                                <button type="button" class="btn btn-outline-secondary" onclick="openIconPicker()">
                                    <i class="fas fa-icons"></i> Choose Icon
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="color" class="form-label">Color</label>
                    <input type="color" class="color-picker" id="color" name="color" value="#e5e7eb">
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
                
                <!-- Technical Fields (Product Type merged) -->
                <hr class="mt-4 mb-3">
                <h6 class="text-muted mb-3"><i class="fas fa-cogs me-1"></i> Technical Info (Product Type)</h6>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sku_prefix" class="form-label">SKU Prefix</label>
                            <input type="text" class="form-control" id="sku_prefix" name="sku_prefix" placeholder="e.g. LXO, ART">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="unit" class="form-label">Unit</label>
                            <select class="form-control" id="unit" name="unit">
                                <option value="">Select Unit</option>
                                @foreach($unitOptions as $unitOption)
                                    <option value="{{ $unitOption->option_name }}">{{ $unitOption->option_name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Source: Master Option Unit (ID 46)</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="has_serial_number" name="has_serial_number">
                            <label class="form-check-label" for="has_serial_number">
                                Has Serial Number
                            </label>
                            <small id="has_serial_number_help" class="form-text text-muted"></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_unit" name="is_unit">
                            <label class="form-check-label" for="is_unit">
                                Is Unit (Mesin)
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times me-1"></i>
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    <span id="saveButtonText">Save Category</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Show Category Modal -->
<div class="modal-overlay" id="showCategoryModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Category Details</h5>
            <button type="button" class="modal-close" onclick="closeShowModal()">&times;</button>
        </div>
        <div class="modal-body" id="showCategoryContent">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeShowModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="editCategoryFromShow()">
                <i class="fas fa-edit me-1"></i>
                Edit Category
            </button>
        </div>
    </div>
</div>

<!-- Icon Picker Modal -->
<div class="icon-picker-modal" id="iconPickerModal">
    <div class="icon-picker-content">
        <div class="icon-picker-header">
            <h5 class="modal-title">Choose Icon</h5>
            <button type="button" class="modal-close" onclick="closeIconPicker()">&times;</button>
        </div>
        <div class="icon-picker-body">
            <div class="icon-grid" id="iconGrid">
                <!-- Icons will be loaded here -->
            </div>
        </div>
        <div class="icon-picker-footer">
            <button type="button" class="btn btn-secondary" onclick="closeIconPicker()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="selectIcon()">Select Icon</button>
        </div>
    </div>
</div>


<script>
let currentCategoryId = null;
let selectedIcon = null;

// Icon list for picker
const iconList = [
    'fas fa-tag', 'fas fa-tags', 'fas fa-box', 'fas fa-boxes', 'fas fa-cube', 'fas fa-cubes',
    'fas fa-leaf', 'fas fa-seedling', 'fas fa-tree', 'fas fa-spa', 'fas fa-spray-can',
    'fas fa-bottle-water', 'fas fa-wine-bottle', 'fas fa-flask', 'fas fa-vial',
    'fas fa-gem', 'fas fa-crown', 'fas fa-star', 'fas fa-heart', 'fas fa-fire',
    'fas fa-sun', 'fas fa-moon', 'fas fa-cloud', 'fas fa-rainbow', 'fas fa-snowflake',
    'fas fa-home', 'fas fa-building', 'fas fa-store', 'fas fa-warehouse', 'fas fa-factory',
    'fas fa-car', 'fas fa-truck', 'fas fa-ship', 'fas fa-plane', 'fas fa-train',
    'fas fa-utensils', 'fas fa-coffee', 'fas fa-birthday-cake', 'fas fa-pizza-slice',
    'fas fa-tshirt', 'fas fa-shoe-prints', 'fas fa-hat-cowboy', 'fas fa-glasses',
    'fas fa-mobile-alt', 'fas fa-laptop', 'fas fa-desktop', 'fas fa-tablet-alt',
    'fas fa-camera', 'fas fa-video', 'fas fa-music', 'fas fa-headphones',
    'fas fa-book', 'fas fa-newspaper', 'fas fa-pen', 'fas fa-pencil-alt',
    'fas fa-paint-brush', 'fas fa-palette', 'fas fa-image', 'fas fa-photo-video',
    'fas fa-gamepad', 'fas fa-dice', 'fas fa-puzzle-piece', 'fas fa-chess',
    'fas fa-football-ball', 'fas fa-basketball-ball', 'fas fa-baseball-ball', 'fas fa-volleyball-ball',
    'fas fa-bicycle', 'fas fa-running', 'fas fa-swimmer', 'fas fa-hiking',
    'fas fa-baby', 'fas fa-child', 'fas fa-user', 'fas fa-users', 'fas fa-user-friends',
    'fas fa-graduation-cap', 'fas fa-briefcase', 'fas fa-id-card', 'fas fa-passport',
    'fas fa-credit-card', 'fas fa-money-bill', 'fas fa-coins', 'fas fa-piggy-bank',
    'fas fa-chart-line', 'fas fa-chart-bar', 'fas fa-chart-pie', 'fas fa-chart-area',
    'fas fa-shopping-cart', 'fas fa-shopping-bag', 'fas fa-gift', 'fas fa-birthday-cake',
    'fas fa-calendar', 'fas fa-clock', 'fas fa-stopwatch', 'fas fa-hourglass-half',
    'fas fa-map', 'fas fa-map-marker-alt', 'fas fa-globe', 'fas fa-compass',
    'fas fa-phone', 'fas fa-envelope', 'fas fa-paper-plane', 'fas fa-inbox',
    'fas fa-lock', 'fas fa-key', 'fas fa-shield-alt', 'fas fa-user-shield',
    'fas fa-tools', 'fas fa-wrench', 'fas fa-hammer', 'fas fa-screwdriver',
    'fas fa-lightbulb', 'fas fa-bolt', 'fas fa-fire', 'fas fa-snowflake',
    'fas fa-umbrella', 'fas fa-sun', 'fas fa-cloud', 'fas fa-cloud-rain',
    'fas fa-star', 'fas fa-moon', 'fas fa-sun', 'fas fa-cloud-sun'
];

function isMandatorySerialPolicy(code, name) {
    const normalizedCode = (code || '').trim().toUpperCase();
    const normalizedName = (name || '').trim().toLowerCase();

    return ['AROMA', 'DIS'].includes(normalizedCode) || ['aroma', 'dispenser'].includes(normalizedName);
}

function updateHasSerialNumberLock(forcedMandatory = null) {
    const checkbox = document.getElementById('has_serial_number');
    const help = document.getElementById('has_serial_number_help');
    const mandatory = forcedMandatory ?? isMandatorySerialPolicy(
        document.getElementById('code').value,
        document.getElementById('name').value
    );

    checkbox.disabled = mandatory;
    if (mandatory) {
        checkbox.checked = true;
    }
    help.textContent = mandatory
        ? 'Aroma dan Dispenser wajib Has SN untuk menjaga alur serial/batch stock.'
        : '';
}

['code', 'name'].forEach(id => {
    document.getElementById(id).addEventListener('input', () => updateHasSerialNumberLock());
});

function openAddModal() {
    currentCategoryId = null;
    document.getElementById('modalTitle').textContent = 'Add Product Category';
    document.getElementById('saveButtonText').textContent = 'Save Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('is_active').checked = true;
    document.getElementById('color').value = '#e5e7eb';
    // Reset technical fields
    document.getElementById('sku_prefix').value = '';
    document.getElementById('unit').value = '';
    document.getElementById('has_serial_number').checked = false;
    document.getElementById('is_unit').checked = false;
    updateHasSerialNumberLock(false);
    document.getElementById('categoryModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function showCategory(id) {
    fetch(`/warehouse/product-structure/categories/${id}`)
        .then(response => {
            // Check if response is ok (status 200-299)
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error('Category not found. It may have been deleted.');
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                const category = data.data;
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Code</label>
                                <p class="form-control-plaintext">${category.code}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Name</label>
                                <p class="form-control-plaintext">${category.name}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <p class="form-control-plaintext">${category.description || 'No description'}</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Parent Category</label>
                                <p class="form-control-plaintext">${category.parent ? category.parent.name : '<span class="badge badge-primary">Parent Category</span>'}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Sort Order</label>
                                <p class="form-control-plaintext">${category.sort_order}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Icon</label>
                                <p class="form-control-plaintext">
                                    ${category.icon ? `<i class="${category.icon}"></i> ${category.icon}` : 'No icon'}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Color</label>
                                <div class="d-flex align-items-center">
                                    <div style="width: 30px; height: 30px; background-color: ${category.color}; border-radius: 4px; margin-right: 10px;"></div>
                                    <span>${category.color}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <p class="form-control-plaintext">
                            <span class="badge ${category.is_active ? 'badge-success' : 'badge-danger'}">
                                ${category.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Products Count</label>
                        <p class="form-control-plaintext">${category.products_count || 0} products</p>
                    </div>

                    <div style="margin-top: 24px; padding-bottom: 8px; border-bottom: 2px solid #f3f4f6; margin-bottom: 16px; font-weight: 600; color: #214589;">
                        <i class="fas fa-cogs me-1"></i> Technical Info (Product Type)
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">SKU Prefix</label>
                                <p class="form-control-plaintext">${category.sku_prefix || '-'}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Unit</label>
                                <p class="form-control-plaintext">${category.unit || '-'}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Has Serial Number</label>
                                <p class="form-control-plaintext">
                                    <span class="badge ${category.has_serial_number ? 'badge-success' : 'badge-secondary'}">
                                        ${category.has_serial_number ? 'Yes' : 'No'}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Is Unit (Mesin)</label>
                                <p class="form-control-plaintext">
                                    <span class="badge ${category.is_unit ? 'badge-success' : 'badge-secondary'}">
                                        ${category.is_unit ? 'Yes' : 'No'}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="modal-section-title mt-4">Audit Information</div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Created By</label>
                                <p class="form-control-plaintext">${category.created_by ? category.created_by.name : '-'}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Created At</label>
                                <p class="form-control-plaintext">${category.created_at ? new Date(category.created_at).toLocaleString('id-ID') : '-'}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Last Updated By</label>
                                <p class="form-control-plaintext">${category.updated_by ? category.updated_by.name : '-'}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Last Updated At</label>
                                <p class="form-control-plaintext">${category.updated_at ? new Date(category.updated_at).toLocaleString('id-ID') : '-'}</p>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('showCategoryContent').innerHTML = content;
                document.getElementById('showCategoryModal').classList.add('show');
                document.body.style.overflow = 'hidden';
                currentCategoryId = id;
            } else {
                alert('Error loading category details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // If category not found or deleted, just refresh the page
            if (error.message.includes('Category not found') || error.message.includes('deleted')) {
                location.reload();
            } else {
                alert('Error: ' + error.message);
                location.reload();
            }
        });
}

function editCategory(id) {
    fetch(`/warehouse/product-structure/categories/${id}`)
        .then(response => {
            // Check if response is ok (status 200-299)
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error('Category not found. It may have been deleted.');
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                const category = data.data;
                
                // Fill form with category data
                document.getElementById('code').value = category.code;
                document.getElementById('name').value = category.name;
                document.getElementById('description').value = category.description || '';
                document.getElementById('parent_id').value = category.parent_id || '';
                document.getElementById('sort_order').value = category.sort_order;
                document.getElementById('icon').value = category.icon || '';
                document.getElementById('color').value = category.color || '#e5e7eb';
                document.getElementById('is_active').checked = category.is_active;
                
                // Technical fields
                document.getElementById('sku_prefix').value = category.sku_prefix || '';
                document.getElementById('unit').value = category.unit || '';
                document.getElementById('has_serial_number').checked = category.mandatory_serial_policy
                    ? true
                    : (category.raw_has_serial_number ?? category.has_serial_number ?? false);
                updateHasSerialNumberLock(category.mandatory_serial_policy || false);
                document.getElementById('is_unit').checked = category.is_unit || false;
                
                // Update modal title and form action
                document.getElementById('modalTitle').textContent = 'Edit Product Category';
                document.getElementById('saveButtonText').textContent = 'Update Category';
                currentCategoryId = id;
                
                // Show edit modal
                document.getElementById('categoryModal').classList.add('show');
                document.body.style.overflow = 'hidden';
            } else {
                alert('Error loading category details: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // If category not found or deleted, just refresh the page
            if (error.message.includes('Category not found') || error.message.includes('deleted')) {
                location.reload();
            } else {
                alert('Error: ' + error.message);
                location.reload();
            }
        });
}

function editCategoryFromShow() {
    closeShowModal();
    editCategory(currentCategoryId);
}

function closeShowModal() {
    document.getElementById('showCategoryModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Icon Picker Functions
function openIconPicker() {
    const iconGrid = document.getElementById('iconGrid');
    iconGrid.innerHTML = '';
    
    iconList.forEach(iconClass => {
        const iconItem = document.createElement('div');
        iconItem.className = 'icon-item';
        iconItem.onclick = () => selectIconItem(iconClass);
        
        iconItem.innerHTML = `
            <i class="${iconClass}"></i>
            <span>${iconClass.replace('fas fa-', '')}</span>
        `;
        
        iconGrid.appendChild(iconItem);
    });
    
    document.getElementById('iconPickerModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function selectIconItem(iconClass) {
    // Remove previous selection
    document.querySelectorAll('.icon-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Add selection to clicked item
    event.target.closest('.icon-item').classList.add('selected');
    selectedIcon = iconClass;
}

function selectIcon() {
    if (selectedIcon) {
        document.getElementById('icon').value = selectedIcon;
    }
    closeIconPicker();
}

function closeIconPicker() {
    document.getElementById('iconPickerModal').classList.remove('show');
    document.body.style.overflow = 'auto';
    selectedIcon = null;
}

function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category?')) {
        // Show loading state
        const deleteButton = event.target;
        const originalText = deleteButton.innerHTML;
        deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
        deleteButton.disabled = true;
        
        fetch(`/warehouse/product-structure/categories/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            // Check if response is ok (status 200-299)
            if (!response.ok) {
                // Try to parse error response for better error messages
                return response.json().then(errorData => {
                    throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                }).catch(() => {
                    throw new Error(`HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Show success message before reload
                alert('Category deleted successfully!');
                location.reload();
            } else {
                // Show error message and don't reload
                alert('Error: ' + data.message);
                // Restore button state
                deleteButton.innerHTML = originalText;
                deleteButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // For delete operations, if there's any error, just refresh the page
            // This handles race conditions and other edge cases
            location.reload();
        });
    }
}

function closeModal() {
    document.getElementById('categoryModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Form submission - called via onsubmit
function submitCategoryForm(e) {
    e.preventDefault();
    
    const form = document.getElementById('categoryForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const code = data.code?.trim() || '';
    const name = data.name?.trim() || '';
    const mandatorySerialPolicy = isMandatorySerialPolicy(code, name);
    
    // Process form data
    const processedData = {
        code,
        name,
        description: data.description?.trim() || null,
        parent_id: data.parent_id || null,
        sort_order: parseInt(data.sort_order) || 0,
        icon: data.icon?.trim() || null,
        color: data.color || null,
        is_active: data.is_active === 'on' || data.is_active === 'true' || data.is_active === true,
        // Technical fields (Product Type merged)
        sku_prefix: data.sku_prefix?.trim() || null,
        unit: data.unit?.trim() || null,
        has_serial_number: mandatorySerialPolicy || data.has_serial_number === 'on' || data.has_serial_number === 'true' || data.has_serial_number === true,
        is_unit: data.is_unit === 'on' || data.is_unit === 'true' || data.is_unit === true
    };
    
    const url = currentCategoryId 
        ? `/warehouse/product-structure/categories/${currentCategoryId}`
        : '/warehouse/product-structure/categories';
    const method = currentCategoryId ? 'PUT' : 'POST';
    
    console.log('Submitting form:', { url, method, processedData });
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            ...processedData,
            _method: method
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            location.reload();
        } else {
            console.error('Validation errors:', data.errors);
            alert('Error: ' + data.message + (data.errors ? '\n\nValidation errors:\n' + JSON.stringify(data.errors, null, 2) : ''));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Something went wrong');
    });
    
    return false; // Prevent default form submission
}

// Close modal when clicking outside
document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endsection
