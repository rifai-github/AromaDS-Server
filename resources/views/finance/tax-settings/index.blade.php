@extends('layouts.app')

@section('title', 'Tax Settings')
@section('breadcrumb', 'Home / Finance / Tax Settings')

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
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(11), .responsive-table td:nth-child(11) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(12), .responsive-table td:nth-child(12) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(13), .responsive-table td:nth-child(13) { width: 120px; min-width: 120px; }

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
    input[type="date"], input[type="text"], input[type="number"], input[type="email"], select, textarea {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    input[type="date"]:focus, input[type="text"]:focus, input[type="number"]:focus, input[type="email"]:focus, select:focus, textarea:focus {
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
        
        <!-- Tax Settings Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Tax Settings</h1>
                
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add Tax Setting</span>
                    <span class="md:hidden">Add</span>
                </button>
            </div>
        </div>

        <!-- Search and Filter Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white border-b border-gray-200">
            <div class="flex flex-row justify-start items-center w-full gap-4">
                <!-- Search Input -->
                <div class="flex flex-row items-center gap-2">
                    <i class="fas fa-search text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="Search by name, tax code, description, or notes..." 
                           class="form-input" style="min-width: 300px;" 
                           value="{{ request('search') }}" onkeyup="handleSearch(event)">
                </div>
                
                <!-- Tax Type Filter -->
                <select id="taxTypeFilter" class="form-input" onchange="applyFilters()">
                    <option value="">All Tax Types</option>
                    <option value="income" {{ request('tax_type') == 'income' ? 'selected' : '' }}>Income Tax</option>
                    <option value="sales" {{ request('tax_type') == 'sales' ? 'selected' : '' }}>Sales Tax</option>
                    <option value="vat" {{ request('tax_type') == 'vat' ? 'selected' : '' }}>VAT</option>
                    <option value="withholding" {{ request('tax_type') == 'withholding' ? 'selected' : '' }}>Withholding Tax</option>
                    <option value="other" {{ request('tax_type') == 'other' ? 'selected' : '' }}>Other</option>
                </select>
                
                <!-- Status Filter -->
                <select id="statusFilter" class="form-input" onchange="applyFilters()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                
                <!-- Clear Filters -->
                <button class="btn btn-secondary btn-sm" onclick="clearFilters()">
                    <i class="fas fa-times"></i>
                    <span>Clear</span>
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
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="name">Name</th>
                        <th data-column="tax_code">Tax Code</th>
                        <th data-column="tax_type">Tax Type</th>
                        <th data-column="tax_rate" data-type="numeric">Tax Rate (%)</th>
                        <th data-column="is_default">Default PPN</th>
                        <th data-column="status">Status</th>
                        <th data-column="effective_date" data-type="date">Effective Date</th>
                        <th data-column="end_date" data-type="date">End Date</th>
                        <th data-column="calculation_method">Calculation Method</th>
                        <th data-column="creator.name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updater.name">Updated By</th>
                        <th data-column="updated_at" data-type="date">Updated At</th>
                    </tr>
                </thead>
                <!-- Table Body -->
                <tbody>
                    @forelse($taxSettings ?? [] as $taxSetting)
                    <tr data-id="{{ $taxSetting->id }}" onclick="openViewModal({{ $taxSetting->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $taxSetting->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td class="font-medium">{{ $taxSetting->name ?? '-' }}</td>
                        <td class="font-mono text-sm">{{ $taxSetting->tax_code ?? '-' }}</td>
                        <td>{{ $taxSetting->tax_type_label ?? '-' }}</td>
                        <td class="font-semibold text-blue-600">{{ $taxSetting->formatted_tax_rate ?? '0%' }}</td>
                        <td>
                            @if($taxSetting->is_default)
                                <span class="status-badge status-active">Default</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td>{!! $taxSetting->status_badge !!}</td>
                        <td>{{ $taxSetting->formatted_effective_date ?? '-' }}</td>
                        <td>{{ $taxSetting->formatted_end_date ?? '-' }}</td>
                        <td>{{ $taxSetting->calculation_method_label ?? '-' }}</td>
                        <td>{{ $taxSetting->createdBy->name ?? '-' }}</td>
                        <td>{{ $taxSetting->formatted_created_at ?? '-' }}</td>
                        <td>{{ $taxSetting->updatedBy->name ?? '-' }}</td>
                        <td>{{ $taxSetting->formatted_updated_at ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-percent text-4xl mb-3"></i>
                                <p class="text-lg">No tax settings found</p>
                                <button class="btn btn-primary mt-2" onclick="openCreateModal()">
                                    <i class="fas fa-plus"></i> Add First Tax Setting
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($taxSettings->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $taxSettings->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Tax Setting Details</h2>
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
        <h3 class="delete-modal-title">Hapus Tax Setting</h3>
        <p class="delete-modal-description" id="deleteMessage">Apakah Anda yakin ingin menghapus tax setting ini? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Ya, hapus</button>
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
        <h3 class="delete-modal-title">Ups, Terjadi Kesalahan</h3>
        <p class="delete-modal-description" id="errorMessage">Tax setting tidak berhasil dihapus. Silakan coba lagi.</p>
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
        <p class="delete-modal-description" id="successMessage">Tax setting berhasil dihapus.</p>
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
    openModal('Create New Tax Setting');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Basic Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-input" placeholder="Enter tax setting name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tax Code *</label>
                        <input type="text" name="tax_code" class="form-input" placeholder="Enter unique tax code" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tax Type *</label>
                        <select name="tax_type" class="form-input" required>
                            <option value="">Select Tax Type</option>
                            <option value="income">Income Tax</option>
                            <option value="sales">Sales Tax</option>
                            <option value="vat">VAT</option>
                            <option value="withholding">Withholding Tax</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tax Rate (%) *</label>
                        <input type="number" name="tax_rate" class="form-input" placeholder="Enter tax rate" step="0.01" min="0" max="100" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Default PPN</label>
                        <label style="display:flex;align-items:center;gap:10px;margin-top:10px;">
                            <input type="checkbox" name="is_default" value="1">
                            <span>Gunakan sebagai tarif PPN default invoice</span>
                        </label>
                        <small class="text-gray-500 text-xs">Hanya berlaku untuk Tax Type VAT.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-input" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Is Compound</label>
                        <select name="is_compound" class="form-input">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Date Range</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Effective Date *</label>
                        <input type="date" name="effective_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-input">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Calculation Settings</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Calculation Method *</label>
                        <select name="calculation_method" class="form-input" required>
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                            <option value="tiered">Tiered</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rounding Method *</label>
                        <select name="rounding_method" class="form-input" required>
                            <option value="nearest">Nearest</option>
                            <option value="up">Round Up</option>
                            <option value="down">Round Down</option>
                            <option value="none">No Rounding</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Decimal Places *</label>
                        <input type="number" name="decimal_places" class="form-input" min="0" max="4" value="2" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Minimum Amount</label>
                        <input type="number" name="minimum_amount" class="form-input" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Maximum Amount</label>
                        <input type="number" name="maximum_amount" class="form-input" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Additional Information</div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input form-textarea" placeholder="Enter tax description"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input form-textarea" placeholder="Additional notes about this tax setting"></textarea>
                </div>
            </div>
        </form>
    `;
    
    // Add modal footer
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" form="form" class="btn btn-primary">Create Tax Setting</button>
    `;
}

function openViewModal(id) {
    openModal('View Tax Setting');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/finance/tax-settings/${id}`, {
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
                            <label class="form-label">Name</label>
                            <p class="detail-value">${data.data.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Tax Code</label>
                            <p class="detail-value font-mono">${data.data.tax_code || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Tax Type</label>
                            <p class="detail-value">${data.data.tax_type_label || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Tax Rate</label>
                            <p class="detail-value font-semibold text-blue-600">${data.data.formatted_tax_rate || '0%'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Default PPN</label>
                            <p class="detail-value">${data.data.is_default ? 'Ya' : 'Tidak'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value">${data.data.status_badge || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Is Compound</label>
                            <p class="detail-value">${data.data.is_compound ? 'Yes' : 'No'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Date Range</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Effective Date</label>
                            <p class="detail-value">${data.data.formatted_effective_date || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">End Date</label>
                            <p class="detail-value">${data.data.formatted_end_date || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Calculation Settings</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Calculation Method</label>
                            <p class="detail-value">${data.data.calculation_method_label || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Rounding Method</label>
                            <p class="detail-value">${data.data.rounding_method_label || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Decimal Places</label>
                            <p class="detail-value">${data.data.decimal_places || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Minimum Amount</label>
                            <p class="detail-value">${data.data.formatted_minimum_amount || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Maximum Amount</label>
                            <p class="detail-value">${data.data.formatted_maximum_amount || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Additional Information</div>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Description</label>
                            <p class="detail-value">${data.data.description || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Notes</label>
                            <p class="detail-value">${data.data.notes || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Created By</label>
                            <p class="detail-value">${data.data.created_by ? data.data.created_by.name : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Created At</label>
                            <p class="detail-value">${data.data.formatted_created_at || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated By</label>
                            <p class="detail-value">${data.data.updated_by ? data.data.updated_by.name : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated At</label>
                            <p class="detail-value">${data.data.formatted_updated_at || '-'}</p>
                        </div>
                    </div>
                </div>
            `;
        
        // Add modal footer for view modal
            document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit Tax Setting</button>
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
    openModal('Edit Tax Setting');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/finance/tax-settings/${id}/edit`, {
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
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-input" placeholder="Enter tax setting name" value="${data.data.name || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tax Code *</label>
                                <input type="text" name="tax_code" class="form-input" placeholder="Enter unique tax code" value="${data.data.tax_code || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tax Type *</label>
                                <select name="tax_type" class="form-input" required>
                                    <option value="">Select Tax Type</option>
                                    <option value="income" ${data.data.tax_type === 'income' ? 'selected' : ''}>Income Tax</option>
                                    <option value="sales" ${data.data.tax_type === 'sales' ? 'selected' : ''}>Sales Tax</option>
                                    <option value="vat" ${data.data.tax_type === 'vat' ? 'selected' : ''}>VAT</option>
                                    <option value="withholding" ${data.data.tax_type === 'withholding' ? 'selected' : ''}>Withholding Tax</option>
                                    <option value="other" ${data.data.tax_type === 'other' ? 'selected' : ''}>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tax Rate (%) *</label>
                                <input type="number" name="tax_rate" class="form-input" placeholder="Enter tax rate" step="0.01" min="0" max="100" value="${data.data.tax_rate || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Default PPN</label>
                                <label style="display:flex;align-items:center;gap:10px;margin-top:10px;">
                                    <input type="checkbox" name="is_default" value="1" ${data.data.is_default ? 'checked' : ''}>
                                    <span>Gunakan sebagai tarif PPN default invoice</span>
                                </label>
                                <small class="text-gray-500 text-xs">Hanya berlaku untuk Tax Type VAT.</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-input" required>
                                    <option value="active" ${data.data.status === 'active' ? 'selected' : ''}>Active</option>
                                    <option value="inactive" ${data.data.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Is Compound</label>
                                <select name="is_compound" class="form-input">
                                    <option value="0" ${!data.data.is_compound ? 'selected' : ''}>No</option>
                                    <option value="1" ${data.data.is_compound ? 'selected' : ''}>Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Date Range</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Effective Date *</label>
                                <input type="date" name="effective_date" class="form-input" value="${data.data.effective_date ? new Date(data.data.effective_date).toISOString().split('T')[0] : ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-input" value="${data.data.end_date ? new Date(data.data.end_date).toISOString().split('T')[0] : ''}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Calculation Settings</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Calculation Method *</label>
                                <select name="calculation_method" class="form-input" required>
                                    <option value="percentage" ${data.data.calculation_method === 'percentage' ? 'selected' : ''}>Percentage</option>
                                    <option value="fixed" ${data.data.calculation_method === 'fixed' ? 'selected' : ''}>Fixed Amount</option>
                                    <option value="tiered" ${data.data.calculation_method === 'tiered' ? 'selected' : ''}>Tiered</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rounding Method *</label>
                                <select name="rounding_method" class="form-input" required>
                                    <option value="nearest" ${data.data.rounding_method === 'nearest' ? 'selected' : ''}>Nearest</option>
                                    <option value="up" ${data.data.rounding_method === 'up' ? 'selected' : ''}>Round Up</option>
                                    <option value="down" ${data.data.rounding_method === 'down' ? 'selected' : ''}>Round Down</option>
                                    <option value="none" ${data.data.rounding_method === 'none' ? 'selected' : ''}>No Rounding</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Decimal Places *</label>
                                <input type="number" name="decimal_places" class="form-input" value="${data.data.decimal_places || 2}" min="0" max="4" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Minimum Amount</label>
                                <input type="number" name="minimum_amount" class="form-input" value="${data.data.minimum_amount || ''}" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Maximum Amount</label>
                                <input type="number" name="maximum_amount" class="form-input" value="${data.data.maximum_amount || ''}" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Additional Information</div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input form-textarea" placeholder="Enter tax description">${data.data.description || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-input form-textarea" placeholder="Additional notes about this tax setting">${data.data.notes || ''}</textarea>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Tax Setting</button>
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
    
    const url = id ? `/finance/tax-settings/${id}` : '/finance/tax-settings';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token and method
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    if (id) {
        formData.append('_method', 'PUT');
    }
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
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
            showErrorDialog('Gagal', result.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', error.message);
    });
}

// Delete Modal functions
function openDeleteModal(id = null) {
    if (id) {
        selectedIdsForRetry = [id];
    }
    
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to delete this tax setting? This action cannot be undone.'
        : `Are you sure you want to delete ${count} tax settings? This action cannot be undone.`;
    
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
    
    fetch('/finance/tax-settings/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ tax_setting_ids: selectedIdsForRetry })
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 422) {
                return response.json().then(data => {
                    throw new Error('Validation failed: ' + JSON.stringify(data));
                });
            }
            return response.text().then(text => {
                throw new Error('Server error: ' + text);
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.status === 'success') {
            showSuccessModal(result.count);
        } else {
            showErrorModal(result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Network error occurred: ' + error.message);
    });
}

// Search and Filter functions
function handleSearch(event) {
    if (event.key === 'Enter') {
        const searchValue = event.target.value;
        const url = new URL(window.location);
        if (searchValue.trim()) {
            url.searchParams.set('search', searchValue);
        } else {
            url.searchParams.delete('search');
        }
        window.location.href = url.toString();
    }
}

function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('search');
    url.searchParams.delete('tax_type');
    url.searchParams.delete('status');
    window.location.href = url.toString();
}


// Bulk operations
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu tax setting yang ingin dihapus.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Success Modal functions
function showSuccessModal(message) {
    const defaultMessage = typeof message === 'number' 
        ? (message === 1 ? 'The tax setting has been successfully deleted.' : `${message} tax settings have been successfully deleted.`)
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
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t delete the tax setting. Please try again.';
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
