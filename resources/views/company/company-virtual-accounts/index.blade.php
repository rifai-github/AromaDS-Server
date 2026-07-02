@extends('layouts.app')

@section('title', 'Company Virtual Accounts')
@section('breadcrumb', 'Home / Company / Company Virtual Accounts')

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
        min-width: 1600px;
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
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 120px; min-width: 120px; }

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
    input[type="date"], input[type="text"], select, textarea {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    input[type="date"]:focus, input[type="text"]:focus, select:focus, textarea:focus {
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
        
        <!-- Company Virtual Accounts Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Company Virtual Accounts</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <button class="btn btn-secondary" onclick="openSetCompanyCodeModal()">
                    <i class="fas fa-cog"></i>
                    <span class="hidden md:inline">Set VA Company</span>
                    <span class="md:hidden">Set</span>
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Virtual Account</span>
                    <span class="md:hidden">Add</span>
                </button>
            </div>
        </div>
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row justify-start items-center w-full">
                <!-- Search Input -->
                <div class="flex flex-row justify-start items-center w-auto mr-4">
                    <div class="relative">
                        <input type="text" id="searchInput" class="form-input pl-10 pr-4 py-2 w-64" placeholder="Search virtual accounts..." value="{{ request('search') }}" onkeyup="handleSearch(event)">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
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
                        <th data-column="account_number">Account Number</th>
                        <th data-column="account_name">Alias Name</th>
                <th data-column="customer__name">Customer</th>
                        <th data-column="bank__name">Bank</th>
                        <th data-column="is_active">Status</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                <!-- Table Body -->
                <tbody>
                    @forelse($virtualAccounts ?? [] as $account)
                    <tr data-id="{{ $account->id }}" onclick="openViewModal({{ $account->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $account->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $account->account_number ?? '-' }}</td>
                        <td>{{ $account->account_name ?? '-' }}</td>
                        <td>{{ $account->customer->name ?? ($account->company->name ?? '-') }}</td>
                        <td>{{ $account->bankPayment->account_name ?? '-' }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $account->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $account->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $account->createdBy->name ?? '-' }}</td>
                        <td>
                            @if($account->created_at)
                                {{ \Carbon\Carbon::parse($account->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($account->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $account->updatedBy->name ?? '-' }}</td>
                        <td>
                            @if($account->updated_at)
                                {{ \Carbon\Carbon::parse($account->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($account->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="p-8 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4"></i>
                                <p class="text-lg font-medium">No virtual accounts found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white rounded-b-[10px] border-t border-gray-100">
             {{ $virtualAccounts->links() }} 
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Create New Virtual Account</h3>
            <button class="modal-close" onclick="closeCreateModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="createForm">
            @csrf
            <div class="modal-body">
                <!-- Customer Information -->
                <div class="modal-section">
                    <h4 class="modal-section-title">Customer Information</h4>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="form-group">
                            <label class="form-label">Master Customer <span class="text-red-500">*</span></label>
                            <select name="customer_id" id="createCustomerId" class="form-input" required onchange="handleCustomerChange(this)">
                                <option value="">Select Customer</option>
                                @foreach($customers ?? [] as $customer)
                                    <option value="{{ $customer->id }}" 
                                        data-default-bank="{{ $customer->default_bank_payment_id }}">
                                        {{ $customer->name }} - {{ $customer->customer_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alias Name</label>
                            <input type="text" name="account_name" id="createAccountName" class="form-input" placeholder="Enter alias name for this Virtual Account">
                        </div>
                    </div>
                </div>

                <!-- Bank & Account Information -->
                <div class="modal-section">
                    <h4 class="modal-section-title">Bank & Account Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Bank Payment *</label>
                            <select name="bank_payment_id" id="createBankPaymentId" class="form-input" required onchange="handleBankChange(this)">
                                <option value="">Select Bank Payment</option>
                                @foreach($banks ?? [] as $bank)
                                    <option value="{{ $bank->id }}" 
                                        data-prefix="{{ $bank->bank_va_number }}"
                                        data-length="{{ $bank->length }}"
                                        {{ (isset($defaultBank) && $defaultBank->id == $bank->id) ? 'selected' : '' }}>
                                        {{ $bank->bank->name ?? 'Bank' }} - {{ $bank->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">VA Number Suffix <span class="text-red-500">*</span></label>
                            <div class="flex items-center">
                                <span id="vaPrefixDisplay" class="bg-gray-100 border border-r-0 border-gray-300 rounded-l px-3 py-2 text-gray-600 font-mono text-sm min-w-[60px] text-center">
                                    {{ isset($defaultBank) ? $defaultBank->bank_va_number : '-' }}
                                </span>
                                <input type="text" name="account_number" id="createAccountNumber" class="form-input rounded-l-none" 
                                    placeholder="e.g. 000001" required 
                                    data-target-length="{{ isset($defaultBank) ? $defaultBank->length : 0 }}"
                                    oninput="this.value = this.value.replace(/\D/g, ''); updateVAPreview()"
                                    onkeyup="updateVAPreview()">
                            </div>
                            <p class="text-xs text-gray-500 mt-1" id="vaHelpText">Enter unique suffix number</p>
                            <p class="text-sm font-semibold text-blue-800 mt-1" id="vaPreviewText">Preview: {{ isset($defaultBank) ? $defaultBank->bank_va_number : '-' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Limits & Settings -->
                <div class="modal-section">
                    <h4 class="modal-section-title">Additional Information</h4>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input form-textarea" placeholder="Additional notes..."></textarea>
                    </div>
                     <!-- Hidden fields for required defaults -->
                    <input type="hidden" name="is_active" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSave" onclick="submitForm('create')">Create Virtual Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Virtual Account</h2>
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
        <h3 class="delete-modal-title">Hide Virtual Account</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this virtual account? This action can be undone later.</p>
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
        <p class="delete-modal-description" id="errorMessage">We couldn't hide the virtual account. Please try again.</p>
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
        <p class="delete-modal-description" id="successMessage">The virtual account has been successfully hidden.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    function handleSearch(event) {
        if (event.key === 'Enter') {
            performSearch();
        }
    }

    function performSearch() {
        const searchTerm = document.getElementById('searchInput').value;
        const currentUrl = new URL(window.location);
        
        if (searchTerm.trim()) {
            currentUrl.searchParams.set('search', searchTerm);
        } else {
            currentUrl.searchParams.delete('search');
        }
        
        window.location.href = currentUrl.toString();
    }

    // Filter functionality
    const filterButtons = document.querySelectorAll('.btn-group button');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const filter = this.getAttribute('data-filter');
            window.location.href = '{{ route("company.company-virtual-accounts.index") }}?filter=' + filter;
        });
    });

    // Create form submission - Handled by submitForm('create')
    /*
    const createForm = document.getElementById('createForm');
    ...
    */

    // Edit functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-btn')) {
            const id = e.target.getAttribute('data-id');
            
            fetch(`{{ route("company.company-virtual-accounts.index") }}/${id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_account_number').value = data.account_number;
                document.getElementById('edit_account_name').value = data.account_name;
                document.getElementById('edit_description').value = data.description;
                document.getElementById('edit_notes').value = data.notes;
                document.getElementById('edit_company_id').value = data.company_id;
                document.getElementById('edit_bank_payment_id').value = data.bank_payment_id;
                document.getElementById('edit_is_active').checked = data.is_active;
                document.getElementById('editModal').style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorDialog('Gagal', 'Gagal memuat data virtual account.');
            });
        }
    });

    // Edit form submission
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('edit_id').value;
            const formData = new FormData(this);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch(`{{ route("company.company-virtual-accounts.index") }}/${id}`, {
                method: 'PUT',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('editModal').style.display = 'none';
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorDialog('Gagal', 'Gagal memperbarui virtual account.');
            });
        });
    }

    // Toggle status functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('toggle-btn')) {
            const id = e.target.getAttribute('data-id');
            const status = e.target.getAttribute('data-status');
            
            showConfirmDialog(
                `${status === 'activate' ? 'Aktifkan' : 'Nonaktifkan'} virtual account ini?`,
                `Virtual account ini akan ${status === 'activate' ? 'diaktifkan' : 'dinonaktifkan'}.`
            ).then((confirmed) => {
                if (!confirmed) {
                    return;
                }
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                fetch(`{{ route("company.company-virtual-accounts.index") }}/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorDialog('Gagal', 'Gagal mengubah status virtual account.');
                });
            });
        }
    });

    // Delete functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-btn')) {
            const id = e.target.getAttribute('data-id');
            
            showConfirmDialog(
                'Hapus virtual account ini?',
                'Data virtual account ini akan dihapus.'
            ).then((confirmed) => {
                if (!confirmed) {
                    return;
                }
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                fetch(`{{ route("company.company-virtual-accounts.index") }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorDialog('Gagal', 'Gagal menghapus virtual account.');
                });
            });
        }
    });
});

// Modal Functions
// Modal Functions
function openCreateModal() {
    document.getElementById('createModal').classList.add('show');
    // Request legacy company code check if needed, or just rely on static inputs
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('show');
}

function openViewModal(id) {
    fetch(`{{ route("company.company-virtual-accounts.index") }}/${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const account = data.data;
                document.getElementById('modalTitle').textContent = 'View Virtual Account';
                document.getElementById('modalBody').innerHTML = `
                    <div class="space-y-6">
                        <div class="modal-section">
                            <h3 class="modal-section-title">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="detail-item">
                                    <label class="form-label">Customer</label>
                                    <div class="detail-value">${account.customer ? account.customer.name : '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Bank Payment</label>
                                    <div class="detail-value">${account.bank_payment ? account.bank_payment.account_name : '-'}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-section">
                            <h3 class="modal-section-title">Account Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="detail-item">
                                    <label class="form-label">Account Number</label>
                                    <div class="detail-value">${account.account_number || '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Alias Name</label>
                                    <div class="detail-value">${account.account_name || '-'}</div>
                                </div>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Description</label>
                                <div class="detail-value">${account.description || '-'}</div>
                            </div>
                        </div>
                        
                        <div class="modal-section">
                            <h3 class="modal-section-title">Status & Notes</h3>
                            <div class="detail-item">
                                <label class="form-label">Status</label>
                                <div class="detail-value">
                                    <span class="px-2 py-1 text-xs rounded-full ${account.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                        ${account.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <label class="form-label">Notes</label>
                                <div class="detail-value">${account.notes || '-'}</div>
                            </div>
                        </div>
                        
                        <div class="modal-section">
                            <h3 class="modal-section-title">Audit Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="detail-item">
                                    <label class="form-label">Created By</label>
                                    <div class="detail-value">${account.created_by ? account.created_by.name : '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Updated By</label>
                                    <div class="detail-value">${account.updated_by ? account.updated_by.name : '-'}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="detail-item">
                                    <label class="form-label">Created At</label>
                                    <div class="detail-value">${account.created_at ? new Date(account.created_at).toLocaleString('id-ID') : '-'}</div>
                                </div>
                                <div class="detail-item">
                                    <label class="form-label">Updated At</label>
                                    <div class="detail-value">${account.updated_at ? new Date(account.updated_at).toLocaleString('id-ID') : '-'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('modalFooter').innerHTML = `
                    <button class="btn btn-secondary" onclick="closeModal()">Tutup</button>
                    <button class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                `;
                document.getElementById('modalOverlay').classList.add('show');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal memuat data virtual account.');
        });
}

function openEditModal(id) {
    fetch(`{{ route("company.company-virtual-accounts.index") }}/${id}/edit`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const account = data.data;
                document.getElementById('modalTitle').textContent = 'Edit Virtual Account';
                document.getElementById('modalBody').innerHTML = `
                    <form id="editForm" class="space-y-6">
                        <input type="hidden" name="id" value="${account.id}">
                        <div class="modal-section">
                            <h3 class="modal-section-title">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="form-label">Customer *</label>
                                    <select name="customer_id" class="form-input" required>
                                        <option value="">Pilih Customer</option>
                                        ${data.customers.map(customer => 
                                            `<option value="${customer.id}" ${customer.id == account.customer_id ? 'selected' : ''}>${customer.name}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Bank Payment *</label>
                                    <select name="bank_payment_id" class="form-input" required>
                                        <option value="">Pilih Bank Payment</option>
                                        ${data.banks.map(bank => 
                                            `<option value="${bank.id}" ${bank.id == account.bank_payment_id ? 'selected' : ''}>${bank.account_name}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-section">
                            <h3 class="modal-section-title">Account Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="form-label">Account Number *</label>
                                    <input type="text" name="account_number" class="form-input" value="${account.account_number || ''}" required oninput="this.value = this.value.replace(/\\D/g, '')">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Alias Name</label>
                                    <input type="text" name="account_name" class="form-input" value="${account.account_name || ''}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-input form-textarea">${account.description || ''}</textarea>
                            </div>
                        </div>
                        
                        <div class="modal-section">
                            <h3 class="modal-section-title">Status & Notes</h3>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-input">
                                    <option value="1" ${account.is_active ? 'selected' : ''}>Active</option>
                                    <option value="0" ${!account.is_active ? 'selected' : ''}>Inactive</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-input form-textarea">${account.notes || ''}</textarea>
                            </div>
                        </div>
                    </form>
                `;
                document.getElementById('modalFooter').innerHTML = `
                    <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button class="btn btn-primary" onclick="submitForm('edit')">Perbarui Virtual Account</button>
                `;
                document.getElementById('modalOverlay').classList.add('show');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal memuat data virtual account.');
        });
}

function submitForm(type) {
    const form = document.getElementById(type + 'Form');
    const formData = new FormData(form);
    
    let url, method;
    if (type === 'create') {
        url = '{{ route("company.company-virtual-accounts.store") }}';
        method = 'POST';
    } else {
        const id = formData.get('id');
        url = `{{ route("company.company-virtual-accounts.index") }}/${id}`;
        method = 'PUT';
        formData.append('_method', 'PUT');
    }
    
    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message || 'Gagal menyimpan virtual account.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal menyimpan virtual account.');
    });
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

function openDeleteModal(id) {
    document.getElementById('deleteModalOverlay').classList.add('show');
    window.deleteAccountId = id;
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    window.deleteAccountId = null;
}

function confirmDelete() {
    if (window.deleteAccountId) {
        fetch(`{{ route("company.company-virtual-accounts.index") }}/${window.deleteAccountId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                closeDeleteModal();
                location.reload();
            } else {
                showErrorDialog('Gagal', data.message || 'Gagal menghapus virtual account.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal menghapus virtual account.');
        });
    }
}

function toggleStatus(id, status) {
    showConfirmDialog(
        `${status === 'true' ? 'Aktifkan' : 'Nonaktifkan'} virtual account ini?`,
        `Virtual account ini akan ${status === 'true' ? 'diaktifkan' : 'dinonaktifkan'}.`
    ).then((confirmed) => {
        if (!confirmed) {
            return;
        }
        fetch(`{{ route("company.company-virtual-accounts.index") }}/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                showErrorDialog('Gagal', data.message || 'Gagal mengubah status virtual account.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal mengubah status virtual account.');
        });
    });
}

function deleteSelected() {
    const selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) {
        showWarningDialog('Pilih minimal satu virtual account yang ingin dihapus.');
        return;
    }
    
    showConfirmDialog(
        'Hapus virtual account yang dipilih?',
        `${selectedIds.length} virtual account akan dihapus.`
    ).then((confirmed) => {
        if (!confirmed) {
            return;
        }
        fetch('{{ route("company.company-virtual-accounts.bulk-delete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ ids: selectedIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                showErrorDialog('Gagal', data.message || 'Gagal menghapus virtual account.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal menghapus virtual account.');
        });
    });
}

// Set VA Company Code Modal
function openSetCompanyCodeModal() {
    // Get current company code - use route helper or construct URL
    const getCodeUrl = '{{ url("/company/company-virtual-accounts/get-company-code") }}';
    
    fetch(getCodeUrl, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
        .then(async response => {
            if (!response.ok) {
                // If 404 or other error, use default
                if (response.status === 404) {
                    console.warn('Route not found, using default company code');
                    return { company_code: '88997' };
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                const text = await response.text();
                console.warn('Expected JSON but got:', text.substring(0, 100));
                return { company_code: '88997' };
            }
        })
        .then(data => {
            const currentCode = data.company_code || '88997';
            showCompanyCodeModal(currentCode);
        })
        .catch(error => {
            console.error('Error loading company code:', error);
            // Use default if fetch fails
            const currentCode = '88997';
            showCompanyCodeModal(currentCode);
        });
}

function showCompanyCodeModal(currentCode) {
    document.getElementById('modalTitle').textContent = 'Set VA Company Code';
    document.getElementById('modalBody').innerHTML = `
        <form id="setCompanyCodeForm" class="space-y-6">
            <div class="modal-section">
                <h3 class="modal-section-title">Company Code Configuration</h3>
                <div class="form-group">
                    <label class="form-label">Company Code (5 digits) *</label>
                    <input type="text" 
                           id="companyCodeInput" 
                           name="company_code" 
                           class="form-input" 
                           pattern="[0-9]{5}" 
                           maxlength="5" 
                           value="${currentCode}" 
                           required
                           placeholder="88997">
                    <small class="text-gray-500 mt-1 block">Format: 5 digit number (e.g., 88997)</small>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded p-4">
                    <p class="text-sm text-blue-800">
                        <strong>Note:</strong> VA number format is <strong>5 digit company code + 6 digit free digits = 11 digits total</strong>.
                        Changing this code will affect all future VA number generation.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Perbarui Company Code</button>
            </div>
        </form>
    `;
    document.getElementById('modalOverlay').classList.add('show');
    
    // Add form submit handler
    const form = document.getElementById('setCompanyCodeForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitCompanyCode();
        });
    }
}

function submitCompanyCode() {
    const companyCode = document.getElementById('companyCodeInput').value;
    
    // Validate
    if (!companyCode || companyCode.length !== 5 || !/^\d+$/.test(companyCode)) {
        showWarningDialog('Company code harus tepat 5 digit.');
        return;
    }
    
    // Use full URL
    const setCodeUrl = '{{ url("/company/company-virtual-accounts/set-company-code") }}';
    
    fetch(setCodeUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ company_code: companyCode })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessDialog('Company code berhasil diperbarui.');
            closeModal();
        } else {
            showErrorDialog('Gagal', data.message || 'Gagal memperbarui company code.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal memperbarui company code.');
    });
}

// Helper Functions for Create Modal
function handleCustomerChange(select) {
    // No longer need to select bank, maybe just focus input?
    // We can keep specific customer logic here if needed in future
    document.getElementById('createAccountNumber').focus();
}

// Deprecated or Used for Edit Modal Only? If Create doesn't use it, we can simplify.
// For now, let's keep handleBankChange in case Edit uses it, but Create uses static.
function handleBankChange(select) {
    const option = select.options[select.selectedIndex];
    if (!option || !option.value) {
        const prefixDisplay = document.getElementById('vaPrefixDisplay');
        if (prefixDisplay) prefixDisplay.innerText = '-';
        
        const suffixInput = document.getElementById('createAccountNumber');
        if (suffixInput) suffixInput.setAttribute('data-target-length', 0);
        
        updateVAPreview();
        return;
    }
    
    const prefix = option.getAttribute('data-prefix') || '-';
    const length = option.getAttribute('data-length') || 0;
    
    const prefixDisplay = document.getElementById('vaPrefixDisplay');
    if (prefixDisplay) prefixDisplay.innerText = prefix;
    
    const suffixInput = document.getElementById('createAccountNumber');
    if (suffixInput) {
        suffixInput.setAttribute('data-target-length', length);
        updateVAPreview();
    }
}

function updateVAPreview() {
    const prefixDisplay = document.getElementById('vaPrefixDisplay');
    const suffixInput = document.getElementById('createAccountNumber');
    
    if (!prefixDisplay || !suffixInput) return;
    
    const prefix = prefixDisplay.innerText.trim();
    const suffix = suffixInput.value;
    const cleanPrefix = prefix === '-' ? '' : prefix;
    
    const preview = cleanPrefix + suffix;
    
    // Get Length from Data Attribute. 
    // User Interpretation: This IS the Suffix Length.
    let suffixTargetLength = suffixInput.getAttribute('data-target-length');
    
    // If not on input, try to find bank selector (Edit Mode fallback)
    if (!suffixTargetLength) {
        const bankSelect = document.getElementById('createBankPaymentId'); // Might exist in Edit
        if (bankSelect) {
            suffixTargetLength = bankSelect.options[bankSelect.selectedIndex]?.getAttribute('data-length');
        }
    }
    
    suffixTargetLength = parseInt(suffixTargetLength) || 0;
    
    const currentInputLength = suffix.length;
    // Required Input is exactly the configured length (Suffix Length)
    const requiredInputLength = suffixTargetLength;
    
    let helpText = `Input Length: ${currentInputLength}`;
    const previewTextElem = document.getElementById('vaPreviewText');
    const helpTextElem = document.getElementById('vaHelpText');
    
    if (suffixTargetLength > 0) {
        helpText += ` / Required: ${requiredInputLength} digits`;
        
        if (previewTextElem) {
            if (currentInputLength !== requiredInputLength) {
                previewTextElem.className = "text-sm font-semibold text-red-600 mt-1";
                // Add specific warning if too long
                if (currentInputLength > requiredInputLength) {
                     helpText += ` (Too long!)`;
                }
            } else {
                previewTextElem.className = "text-sm font-semibold text-green-600 mt-1";
            }
        }
    } else {
        if (previewTextElem) previewTextElem.className = "text-sm font-semibold text-blue-800 mt-1";
    }
    
    if (previewTextElem) previewTextElem.innerText = "Preview: " + preview;
    if (helpTextElem) helpTextElem.innerText = helpText;
}

// Ensure global access if needed
window.handleCustomerChange = handleCustomerChange;
window.handleBankChange = handleBankChange;
window.updateVAPreview = updateVAPreview;

// Search functions
window.handleSearch = function(event) {
    if (event.key === 'Enter') {
        performSearch();
    }
};

window.performSearch = function() {
    const searchTerm = document.getElementById('searchInput').value;
    const currentUrl = new URL(window.location);
    
    if (searchTerm.trim()) {
        currentUrl.searchParams.set('search', searchTerm);
    } else {
        currentUrl.searchParams.delete('search');
    }
    
    window.location.href = currentUrl.toString();
};

</script>
@endpush
