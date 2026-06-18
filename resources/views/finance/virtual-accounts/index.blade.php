@extends('layouts.app')

@section('title', 'Virtual Accounts')
@section('breadcrumb', 'Home / Finance / Virtual Accounts')

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

    .btn-danger {
        background-color: #dc2626;
        color: white;
    }

    .btn-danger:hover {
        background-color: #b91c1c;
    }

    .btn-success {
        background-color: #10b981;
        color: white;
    }

    .btn-success:hover {
        background-color: #059669;
    }

    .btn-info {
        background-color: #3b82f6;
        color: white;
    }

    .btn-info:hover {
        background-color: #2563eb;
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
        min-width: 1400px;
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
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 180px; min-width: 180px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(11), .responsive-table td:nth-child(11) { width: 150px; min-width: 150px; }
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
    
    .status-suspended {
        background-color: #fef3c7;
        color: #92400e;
    }

    .account-type-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        background-color: #dbeafe;
        color: #1e40af;
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
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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

    /* Delete Modal Styles */
    .delete-modal-overlay {
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
    
    .delete-modal-overlay.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .delete-modal-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        max-width: 90vw;
        width: 400px;
        overflow: hidden;
        position: relative;
    }
    
    .delete-modal-header {
        background: #dc2626;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .delete-modal-body {
        padding: 20px;
        text-align: center;
    }
    
    .delete-modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: center;
        gap: 20px;
    }

    /* Success Modal Styles */
    .success-modal-overlay {
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
    
    .success-modal-overlay.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .success-modal-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        max-width: 90vw;
        width: 400px;
        overflow: hidden;
        position: relative;
    }
    
    .success-modal-header {
        background: #10b981;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .success-modal-body {
        padding: 20px;
        text-align: center;
    }
    
    .success-modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: center;
        gap: 20px;
    }

    /* Error Modal Styles */
    .error-modal-overlay {
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
    
    .error-modal-overlay.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .error-modal-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        max-width: 90vw;
        width: 400px;
        overflow: hidden;
        position: relative;
    }
    
    .error-modal-header {
        background: #dc2626;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .error-modal-body {
        padding: 20px;
        text-align: center;
    }
    
    .error-modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: center;
        gap: 20px;
    }
    
    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
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
    
    .form-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
        box-sizing: border-box;
        background-color: white;
    }
    
    .form-select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }
    
    .form-checkbox {
        width: 16px;
        height: 16px;
        margin-right: 8px;
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
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 1400px;
        }
        
        .controls-row {
            flex-direction: column;
            gap: 12px;
            align-items: stretch;
        }
        
        .controls-left {
            justify-content: space-between;
        }
        
        .pagination-controls {
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
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
        .controls-row {
            flex-direction: column;
            gap: 12px;
            align-items: stretch;
        }
        
        .controls-left {
            justify-content: space-between;
        }
        
        .pagination-controls {
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Virtual Accounts Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Virtual Accounts</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <button class="btn btn-info" onclick="openImportModal()">
                    <i class="fas fa-upload"></i>
                    <span class="hidden md:inline">Import</span>
                </button>
                <button class="btn btn-success" onclick="openExportModal()">
                    <i class="fas fa-download"></i>
                    <span class="hidden md:inline">Export</span>
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">New Virtual Account</span>
                    <span class="md:hidden">New</span>
                </button>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white controls-row">
            <div class="flex flex-row justify-start items-center w-full controls-left">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        <label for="selectAll" class="ml-2 text-sm text-[#3d3d3d] cursor-pointer">Select all</label>
                    </div>
                </div>
                
                <button class="btn btn-danger ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
            
        </div>
        
        <!-- Table Container -->
        <div class="w-full bg-white rounded-b-[10px] table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th class="w-[50px]" data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th class="w-[180px]" data-column="va_number">Virtual Account Number</th>
                        <th class="w-[150px]" data-column="customer.name">Customer Name</th>
                        <th class="w-[120px]" data-column="bank.name">Bank</th>
                        <th class="w-[120px]" data-column="account_type">Account Type</th>
                        <th class="w-[120px]" data-column="balance" data-type="numeric">Balance</th>
                        <th class="w-[100px]" data-column="status">Status</th>
                        <th class="w-[120px]" data-column="last_transaction_at" data-type="date">Last Transaction</th>
                        <th class="w-[120px]" data-column="creator.name">Created By</th>
                        <th class="w-[150px]" data-column="created_at" data-type="date">Created At</th>
                        <th class="w-[120px]" data-column="updater.name">Last Updated By</th>
                        <th class="w-[150px]" data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($virtualAccounts ?? [] as $va)
                    <tr onclick="openViewModal({{ $va->id }})" data-id="{{ $va->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $va->id }}">
                        </td>
                        <td class="font-medium">{{ $va->virtual_account_number ?? '-' }}</td>
                        <td>{{ $va->customer_name ?? '-' }}</td>
                        <td>{{ $va->bank->name ?? '-' }}</td>
                        <td>
                            <span class="account-type-badge">
                                {{ ucfirst($va->account_type ?? 'standard') }}
                            </span>
                        </td>
                        <td class="text-right">{{ number_format($va->balance ?? 0, 2) }}</td>
                        <td>
                            <span class="status-badge status-{{ $va->status ?? 'inactive' }}">
                                {{ ucfirst($va->status ?? 'inactive') }}
                            </span>
                        </td>
                        <td>{{ $va->last_transaction_date ? \Carbon\Carbon::parse($va->last_transaction_date)->format('d/M/Y') : '-' }}</td>
                        <td>{{ $va->creator->name ?? '-' }}</td>
                        <td>
                            @if($va->created_at)
                                {{ \Carbon\Carbon::parse($va->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($va->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $va->updater->name ?? '-' }}</td>
                        <td>
                            @if($va->updated_at)
                                {{ \Carbon\Carbon::parse($va->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($va->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="p-8 text-center">
                            <div class="text-gray-600">
                                <i class="fas fa-credit-card text-4xl mb-3"></i>
                                <p class="text-lg">No virtual accounts found</p>
                                <p class="text-sm mt-2">Create your first virtual account to get started.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($virtualAccounts->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $virtualAccounts->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Virtual Account Details</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content will be loaded here -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Modal footer buttons will be loaded here -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container" onclick="event.stopPropagation()">
        <div class="delete-modal-header">
            <h2 class="modal-title">Delete Virtual Account</h2>
            <button class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="delete-modal-body">
            <div class="text-center">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-3"></i>
                </div>
                <p class="text-lg font-medium text-gray-900 mb-2">Are you sure you want to delete this virtual account?</p>
                <p class="text-sm text-gray-600 mb-4">This action cannot be undone.</p>
                <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <p class="text-sm text-red-800 font-medium" id="deleteItemName"></p>
                </div>
            </div>
        </div>
        <div class="delete-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                <i class="fas fa-trash mr-2"></i>
                Delete
            </button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="success-modal-header">
            <h2 class="modal-title">Success</h2>
            <button class="modal-close" onclick="closeSuccessModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="success-modal-body">
            <div class="text-center">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                </div>
                <p class="text-lg font-medium text-gray-900 mb-2" id="successMessage">Operation completed successfully!</p>
                <p class="text-sm text-gray-600" id="successDetails"></p>
            </div>
        </div>
        <div class="success-modal-footer">
            <button type="button" class="btn btn-primary" onclick="closeSuccessModal()">OK</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="error-modal-header">
            <h2 class="modal-title">Error</h2>
            <button class="modal-close" onclick="closeErrorModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="error-modal-body">
            <div class="text-center">
                <div class="mb-4">
                    <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-3"></i>
                </div>
                <p class="text-lg font-medium text-gray-900 mb-2" id="errorMessage">An error occurred!</p>
                <p class="text-sm text-gray-600" id="errorDetails"></p>
            </div>
        </div>
        <div class="error-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeErrorModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="retryDelete()" id="retryButton" style="display: none;">
                <i class="fas fa-redo mr-2"></i>
                Retry
            </button>
        </div>
    </div>
</div>

<script>
// Global variables
let currentDeleteId = null;
let currentDeleteName = '';

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
function openModal() {
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// CRUD Modal functions
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add New Virtual Account';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Create a new virtual account for customer payments.</p>
        <form id="createForm">
            <div class="form-group">
                <label class="form-label">Virtual Account Number *</label>
                <input type="text" name="virtual_account_number" class="form-input" placeholder="Enter virtual account number" required>
            </div>
            <div class="form-group">
                <label class="form-label">Customer Name *</label>
                <input type="text" name="customer_name" class="form-input" placeholder="Enter customer name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Bank *</label>
                <select name="bank_id" class="form-select" required>
                    <option value="">Select Bank</option>
                    @foreach($banks ?? [] as $bank)
                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Account Type *</label>
                <select name="account_type" class="form-select" required>
                    <option value="">Select Account Type</option>
                    <option value="standard">Standard</option>
                    <option value="premium">Premium</option>
                    <option value="enterprise">Enterprise</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Initial Balance</label>
                <input type="number" name="balance" class="form-input" placeholder="Enter initial balance" step="0.01" value="0">
            </div>
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <option value="">Select Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="submitForm('create')">Create Virtual Account</button>
        </div>
    `;
    openModal();
}

function openViewModal(id) {
    fetch(`/finance/virtual-accounts/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Virtual Account Details';
            document.getElementById('modalBody').innerHTML = `
                <div class="space-y-4">
                    <div class="detail-item">
                        <label class="form-label">Virtual Account Number</label>
                        <p class="detail-value">${data.virtual_account_number || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Customer Name</label>
                        <p class="detail-value">${data.customer_name || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Bank</label>
                        <p class="detail-value">${data.bank?.name || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Account Type</label>
                        <p class="detail-value">${data.account_type ? data.account_type.charAt(0).toUpperCase() + data.account_type.slice(1) : '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Balance</label>
                        <p class="detail-value">${data.balance ? parseFloat(data.balance).toLocaleString('en-US', {minimumFractionDigits: 2}) : '0.00'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Status</label>
                        <p class="detail-value">${data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Created Date</label>
                        <p class="detail-value">${data.created_at ? new Date(data.created_at).toLocaleString('en-GB') : '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Last Transaction</label>
                        <p class="detail-value">${data.last_transaction_date ? new Date(data.last_transaction_date).toLocaleString('en-GB') : '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Created By</label>
                        <p class="detail-value">${data.createdBy?.name || '-'}</p>
                    </div>
                </div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                </div>
            `;
            openModal();
        })
        .catch(error => {
            console.error('Error loading virtual account data:', error);
            showErrorModal('Gagal memuat data virtual account', 'Silakan coba lagi nanti.');
        });
}

function openEditModal(id) {
    fetch(`/finance/virtual-accounts/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Virtual Account';
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update the virtual account details.</p>
                <form id="editForm">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="form-group">
                        <label class="form-label">Virtual Account Number *</label>
                        <input type="text" name="virtual_account_number" class="form-input" placeholder="Enter virtual account number" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Customer Name *</label>
                        <input type="text" name="customer_name" class="form-input" placeholder="Enter customer name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bank *</label>
                        <select name="bank_id" class="form-select" required>
                            <option value="">Select Bank</option>
                            @foreach($banks ?? [] as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Type *</label>
                        <select name="account_type" class="form-select" required>
                            <option value="">Select Account Type</option>
                            <option value="standard">Standard</option>
                            <option value="premium">Premium</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Balance</label>
                        <input type="number" name="balance" class="form-input" placeholder="Enter balance" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="submitForm('edit')">Update Virtual Account</button>
                </div>
            `;
            
            // Populate form with existing data
            const form = document.getElementById('editForm');
            form.virtual_account_number.value = data.virtual_account_number || '';
            form.customer_name.value = data.customer_name || '';
            form.bank_id.value = data.bank_id || '';
            form.account_type.value = data.account_type || '';
            form.balance.value = data.balance || '';
            form.status.value = data.status || '';
            openModal();
        })
        .catch(error => {
            console.error('Error loading virtual account data:', error);
            showErrorModal('Gagal memuat data virtual account', 'Silakan coba lagi nanti.');
        });
}

function submitForm(type) {
    const form = document.getElementById(type === 'create' ? 'createForm' : 'editForm');
    const formData = new FormData(form);
    const url = type === 'create' ? '/finance/virtual-accounts' : `/finance/virtual-accounts/${formData.get('id')}`;
    const method = type === 'create' ? 'POST' : 'PUT';
    
    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            showSuccessModal(
                `${type === 'create' ? 'Virtual account berhasil dibuat' : 'Virtual account berhasil diperbarui'}.`,
                'Perubahan berhasil disimpan.'
            );
        } else {
            showErrorModal('Gagal', data.message || `Gagal ${type === 'create' ? 'membuat' : 'memperbarui'} virtual account.`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Gagal', `Gagal ${type === 'create' ? 'membuat' : 'memperbarui'} virtual account. Silakan coba lagi.`);
    });
}

// Delete functions
function openDeleteModal(id, name) {
    currentDeleteId = id;
    currentDeleteName = name;
    document.getElementById('deleteItemName').textContent = name;
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
    currentDeleteId = null;
    currentDeleteName = '';
}

function confirmDelete() {
    if (!currentDeleteId) return;
    
    fetch(`/finance/virtual-accounts/${currentDeleteId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        closeDeleteModal();
        if (data.success) {
            showSuccessModal('Virtual account berhasil dihapus.', 'Data virtual account sudah dihapus.');
        } else {
            showErrorModal('Gagal', data.message || 'Gagal menghapus virtual account.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        closeDeleteModal();
        showErrorModal('Gagal', 'Gagal menghapus virtual account. Silakan coba lagi.');
    });
}

// Bulk delete function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu virtual account yang ingin dihapus.');
        return;
    }
    
    const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
    const selectedNames = Array.from(checkboxes).map(checkbox => {
        const row = checkbox.closest('tr');
        return row.querySelector('td:nth-child(2)').textContent.trim();
    });
    
    currentDeleteId = selectedIds;
    currentDeleteName = selectedNames.join(', ');
    
    document.getElementById('deleteItemName').textContent = `${selectedNames.length} virtual account(s): ${selectedNames.join(', ')}`;
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Success/Error modal functions
function showSuccessModal(message, details = '') {
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successDetails').textContent = details;
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSuccessModal() {
    document.getElementById('successModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
    location.reload();
}

function showErrorModal(message, details = '') {
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('errorDetails').textContent = details;
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retryDelete() {
    closeErrorModal();
    if (Array.isArray(currentDeleteId)) {
        // Bulk delete retry
        deleteSelected();
    } else if (currentDeleteId) {
        // Single delete retry
        openDeleteModal(currentDeleteId, currentDeleteName);
    }
}

// Specialized functions
function openImportModal() {
    document.getElementById('modalTitle').textContent = 'Import Virtual Account';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Import virtual account dari file CSV.</p>
        <form id="importForm" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">CSV File *</label>
                <input type="file" name="csv_file" class="form-input" accept=".csv" required>
            </div>
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" name="skip_header" class="form-checkbox" checked>
                    Lewati Baris Header
                </label>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="submitImportForm()">Import</button>
        </div>
    `;
    openModal();
}

function openExportModal() {
    document.getElementById('modalTitle').textContent = 'Export Virtual Account';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Export virtual account ke file CSV.</p>
        <form id="exportForm">
            <div class="form-group">
                <label class="form-label">Format Export</label>
                <select name="format" class="form-select">
                    <option value="csv">CSV</option>
                    <option value="excel">Excel</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Kolom yang Disertakan</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="fields[]" value="virtual_account_number" class="form-checkbox" checked>
                        <span class="ml-2">Virtual Account Number</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="fields[]" value="customer_name" class="form-checkbox" checked>
                        <span class="ml-2">Customer Name</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="fields[]" value="bank" class="form-checkbox" checked>
                        <span class="ml-2">Bank</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="fields[]" value="balance" class="form-checkbox" checked>
                        <span class="ml-2">Balance</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="fields[]" value="status" class="form-checkbox" checked>
                        <span class="ml-2">Status</span>
                    </label>
                </div>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="submitExportForm()">Export</button>
        </div>
    `;
    openModal();
}

function submitImportForm() {
    const form = document.getElementById('importForm');
    const formData = new FormData(form);
    
    fetch('/finance/virtual-accounts/import', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            showSuccessModal('Import berhasil diselesaikan.', `${data.imported_count} virtual account berhasil diimpor.`);
        } else {
            showErrorModal('Gagal Import', data.message || 'Gagal mengimpor virtual account.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Gagal Import', 'Gagal mengimpor virtual account. Silakan coba lagi.');
    });
}

function submitExportForm() {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    window.open(`/finance/virtual-accounts/export?${params.toString()}`, '_blank');
    closeModal();
}

function viewTransactions(id) {
    window.open(`/finance/virtual-accounts/${id}/transactions`, '_blank');
}

function generateStatement(id) {
    window.open(`/finance/virtual-accounts/${id}/statement`, '_blank');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
        closeSuccessModal();
        closeErrorModal();
    }
});
</script>
@endsection
