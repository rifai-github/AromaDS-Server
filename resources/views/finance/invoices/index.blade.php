@extends('layouts.app')

@section('title', 'Invoices')
@section('breadcrumb', 'Home / Finance / Invoices')

@section('content')
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">

<style>
    /* Responsive Table */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 10px 10px;
    }
    
    .responsive-table {
        min-width: 1400px;
        width: 100%;
        border-collapse: collapse;
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
        background-color: #225fd3;
        color: white;
        font-weight: 600;
        font-size: 13px;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
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
        }
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
    
    .btn-outline {
        background-color: white;
        color: #214589;
        border: 2px solid #214589;
        font-weight: 500;
    }
    
    .btn-outline:hover {
        background-color: #214589;
        color: white;
    }
    
    .btn-danger {
        background-color: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background-color: #dc2626;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    /* Status Badges */
    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .badge-draft {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .badge-sent {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .badge-paid {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .badge-overdue {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .badge-cancelled {
        background-color: #f3f4f6;
        color: #6b7280;
    }
    
    .badge-partial {
        background-color: #fed7aa;
        color: #9a3412;
    }
    
    .badge-blue { background-color: #dbeafe; color: #1e40af; }
    .badge-green { background-color: #d1fae5; color: #065f46; }
    .badge-yellow { background-color: #fef3c7; color: #92400e; }
    
    /* New Status Badges for Rule 47 */
    .status-badge.status-approved { background-color: #dcfce7; color: #166534; }
    .status-badge.status-tax-approved { background-color: #f0f9ff; color: #075985; border: 1px solid #bae6fd; }
    .status-badge.status-tax-exported { background-color: #ede9fe; color: #5b21b6; }
    .badge-gray { background-color: #f3f4f6; color: #374151; }
    
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-draft { background-color: #f3f4f6; color: #374151; }
    .status-sent { background-color: #dbeafe; color: #1e40af; }
    .status-paid { background-color: #d1fae5; color: #065f46; }
    .status-overdue { background-color: #fee2e2; color: #991b1b; }
    .status-cancelled { background-color: #fef3c7; color: #92400e; }
    
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

    .regenerate-contract-toolbar {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 16px;
    }

    .regenerate-contract-list {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        max-height: 340px;
        overflow-y: auto;
    }

    .regenerate-contract-option {
        display: grid;
        grid-template-columns: 24px 1fr;
        gap: 10px;
        padding: 12px 14px;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
    }

    .regenerate-contract-option:last-child {
        border-bottom: none;
    }

    .regenerate-contract-option:hover {
        background: #f9fafb;
    }

    .regenerate-contract-option.is-disabled {
        cursor: not-allowed;
        color: #9ca3af;
        background: #f9fafb;
    }

    .regenerate-contract-result {
        display: none;
        margin-top: 16px;
        padding: 12px 14px;
        border-radius: 8px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1e3a8a;
        font-size: 13px;
    }

    .regenerate-contract-result.show {
        display: block;
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
        padding: 40px 30px 30px;
        text-align: center;
    }

    .delete-icon-container {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    .delete-icon {
        width: 48px;
        height: 48px;
        color: #10b981;
    }

    .delete-modal-title {
        font-size: 24px;
        font-weight: 600;
        color: #065f46;
        margin: 0 0 12px 0;
    }

    .delete-modal-description {
        font-size: 16px;
        color: #047857;
        margin: 0;
        line-height: 1.5;
    }

    /* Hide Confirmation Modal */
    .hide-modal-overlay {
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

    .hide-modal-overlay.show {
        display: flex;
    }

    .hide-modal-container {
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

    .delete-modal-buttons {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-top: 24px;
    }

    .btn-cancel {
        background-color: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-cancel:hover {
        background-color: #e5e7eb;
    }

    .btn-hide {
        background-color: #dc2626;
        color: white;
        border: 1px solid #dc2626;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-hide:hover {
        background-color: #b91c1c;
        border-color: #b91c1c;
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
    
    /* Pagination Styles */
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 1.5rem;
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
        white-space: nowrap;
    }
    
    .page-dropdown-container span {
        display: inline;
        white-space: nowrap;
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
    
    /* Mobile Modal Adjustments */
    @media (max-width: 768px) {
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

        .regenerate-contract-toolbar {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Invoices Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Invoices</h1>
               <!-- <div class="ml-4 flex gap-2">
                    <a href="{{ route('finance.tax-invoices.index') }}" class="btn btn-outline btn-sm">
                        <i class="fas fa-receipt"></i>
                        <span>Tax Invoices</span>
                    </a>
                    <a href="{{ route('finance.tax-reports.index') }}" class="btn btn-outline btn-sm">
                        <i class="fas fa-chart-line"></i>
                        <span>Tax Reports</span>
                    </a>
                    <a href="{{ route('finance.e-materai-transactions.index') }}" class="btn btn-outline btn-sm">
                        <i class="fas fa-stamp"></i>
                        <span>e-Materai</span>
                    </a>
                </div> -->
            </div>
            
            <button type="button" class="btn btn-primary" onclick="openRegenerateMissingModal()">
                <i class="fas fa-sync-alt"></i>
                <span>Regenerate Invoice</span>
            </button>

           <!-- <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Create New Invoice</span>
            </button> -->
        </div>
        
        <!-- Date Range Filter -->
        <div style="background: white; border-radius: 16px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; align-items: center; gap: 32px; flex-wrap: wrap;">
                <!-- Date From -->
                <div class="flex items-center" style="gap: 20px;">
                    <span style="font-size: 14px; font-weight: 600; color: #374151;">Dari</span>
                    <input type="text" id="filterDateFrom" 
                        class="cursor-pointer"
                        style="background: #f9fafb; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #374151; width: 160px; outline: none;"
                        data-date="{{ request('date_from', now()->toDateString()) }}"
                        readonly>
                </div>

                <!-- Date To -->
                <div class="flex items-center" style="gap: 20px;">
                    <span style="font-size: 14px; font-weight: 600; color: #374151;">Sampai</span>
                    <input type="text" id="filterDateTo" 
                        class="cursor-pointer"
                        style="background: #f9fafb; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #374151; width: 160px; outline: none;"
                        data-date="{{ request('date_to', now()->addDays(14)->toDateString()) }}"
                        readonly>
                </div>

                <!-- Search Box -->
                <div class="flex items-center" style="gap: 20px;">
                    <span style="font-size: 14px; font-weight: 600; color: #374151;">Search</span>
                    <input type="text" id="searchInput" 
                        placeholder="Invoice, Contract, Customer, Job No..."
                        style="background: #f9fafb; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #374151; width: 280px; outline: none;"
                        value="{{ request('search', '') }}">
                </div>

                <!-- Print Status Filter -->
                <div class="flex items-center" style="gap: 20px;">
                    <span style="font-size: 14px; font-weight: 600; color: #374151;">Status Print</span>
                    <select id="filterPrintStatus" onchange="applyPrintStatusFilter()"
                        style="background: #f9fafb; border: 1px solid #d1d5db; border-radius: 12px; padding: 12px 20px; font-size: 14px; font-weight: 600; color: #374151; width: 140px; outline: none; appearance: none; cursor: pointer;">
                        <option value="belum" {{ request('print_status') === 'belum' || !request()->has('print_status') ? 'selected' : '' }}>Belum</option>
                        <option value="sudah" {{ request('print_status') === 'sudah' ? 'selected' : '' }}>Sudah</option>
                        <option value="semua" {{ request('print_status') === 'semua' ? 'selected' : '' }}>Semua</option>
                    </select>
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
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white controls-row">
            <div class="flex flex-row justify-start items-center w-full controls-left">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        <label for="selectAll" class="ml-2 text-sm text-[#3d3d3d] cursor-pointer">Select all</label>
                    </div>
                </div>
                
                <button class="btn btn-secondary ml-4" onclick="openHideModal()">
                    <i class="fas fa-eye-slash"></i>
                    <span>Hide</span>
                </button>
            </div>
            
        </div>
        
        <!-- Table Container -->
        <div class="w-full bg-white rounded-b-[10px] table-container">
            <table class="responsive-table" id="invoicesTable">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th class="w-[50px]" data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th class="w-[120px]" data-column="invoice_number">Invoice No</th>
                        <th class="w-[120px]" data-column="contract_number">Contract No</th>
                        <th class="w-[200px]" data-column="customer__name">Customer</th>
                        <th class="w-[150px]" data-column="billing_group">Billing Group</th>
                        <th class="w-[150px]" data-no-filter style="display:none">Jobs</th>
                        <th class="w-[100px]" data-column="ba_date" data-type="date">BA Date</th>
                        <th class="w-[100px]" data-column="kirim">Kirim</th>
                        <th class="w-[150px]" data-column="diterima_oleh">Diterima Oleh</th>
                        <th class="w-[150px]" data-column="pada" data-type="date">Pada</th>
                        <th class="w-[120px]" data-column="invoice_date" data-type="date">Tanggal Invoice</th>
                        <th class="w-[100px]" data-column="is_printed">Status Print</th>

                        <th class="w-[120px]" data-column="invoice_status">Status</th>
                        <th class="w-[100px]" data-column="period_invoice">Periode Invoice</th>
                        <th class="w-[100px]" data-column="tax_obligation">Wajib Pungut ?</th>
                        <th class="w-[150px]" data-column="total_invoice" data-type="numeric">Total Invoice</th>
                        <th class="w-[150px]" data-column="outstanding" data-type="numeric">Outstanding</th>
                        <th class="w-[100px]" data-column="umur_invoice" data-type="numeric">Umur Invoice</th>
                        <th class="w-[150px]" data-column="pic_finance">PIC Finance</th>
                        <th class="w-[200px]" data-column="email">E-Mail</th>
                        <th class="w-[100px]" data-column="tax_code">Tax Code</th>
                        <th class="w-[150px]" data-column="faktur_pajak">Faktur Pajak</th>
                        <th class="w-[200px]" data-column="gedung">Gedung</th>
                        <th class="w-[250px]" data-column="alamat_1">Alamat 1</th>
                        <th class="w-[250px]" data-column="alamat_2">Alamat 2</th>
                        <th class="w-[150px]" data-column="catatan_internal">Catatan Internal</th>
                        <th class="w-[150px]" data-column="catatan_customer">Catatan Customer / Invoice</th>
                        <th class="w-[150px]" data-column="updated_at" data-type="date">Terakhir Update</th>
                        <th class="w-[150px]" data-column="updater__name">Oleh</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr onclick="openViewModal({{ $invoice->id }})" data-id="{{ $invoice->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $invoice->id }}">
                        </td>
                        <td class="font-medium">{{ $invoice->invoice_number ?? '-' }}</td>
                        <td>{{ $invoice->contract_number ?? '-' }}</td>
                        <td>{{ $invoice->customer->name ?? '-' }}</td>
                        <td>{{ $invoice->billingGroup->billing_group_name ?? $invoice->contract->billingGroup->billing_group_name ?? '-' }}</td>
                        <td class="text-xs" style="display:none">
                            {{ $invoice->jobSchedules->pluck('job_number')->filter()->implode(', ') ?: '-' }}
                        </td>
                        <td>{{ $invoice->ba_date ? \Carbon\Carbon::parse($invoice->ba_date)->format('d M Y') : '-' }}</td>
                        <td class="text-center">
                            @php
                                $kirimMap = [
                                    'hard_copy' => ['label' => 'HARD', 'class' => 'bg-secondary text-white'],
                                    'soft_copy' => ['label' => 'SOFT', 'class' => 'bg-light text-dark border'],
                                    'both'      => ['label' => 'BOTH', 'class' => 'bg-info text-dark'],
                                    'manual'    => ['label' => 'MANUAL', 'class' => 'bg-warning text-dark'],
                                ];
                                $status = $invoice->kirim ?? 'soft_copy';
                                $badge = $kirimMap[$status] ?? ['label' => strtoupper(str_replace('_', ' ', $status)), 'class' => 'bg-light text-dark border'];
                            @endphp
                            <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        </td>
                        <td>{{ $invoice->diterima_oleh ?? '-' }}</td>
                        <td>{{ $invoice->pada ? \Carbon\Carbon::parse($invoice->pada)->format('d M Y - H:i') : '-' }}</td>
                        <td>{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') : '-' }}</td>
                        <td class="text-center">
                            @if($invoice->is_printed)
                                <span class="badge bg-green text-white">SUDAH</span>
                            @else
                                <span class="badge bg-secondary text-white">BELUM</span>
                            @endif
                        </td>

                        <td>
                            @if($invoice->invoice_status == 'draft')
                                <span class="status-badge status-draft">Draft</span>
                            @elseif($invoice->invoice_status == 'approved')
                                <span class="status-badge status-approved">Approved</span>
                            @elseif($invoice->invoice_status == 'tax_approved')
                                <span class="status-badge status-tax-approved">Tax Approved</span>
                            @elseif($invoice->invoice_status == 'sent')
                                <span class="status-badge status-sent">Sent</span>
                            @elseif($invoice->invoice_status == 'paid')
                                <span class="status-badge status-paid">Paid</span>
                            @elseif($invoice->invoice_status == 'cancelled')
                                <span class="status-badge status-cancelled">Cancelled</span>
                            @else
                                <span class="status-badge">{{ ucfirst($invoice->invoice_status) }}</span>
                            @endif

                            @if($invoice->is_tax_exported)
                                <span class="status-badge status-tax-exported ms-1">Tax Exported</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $invoice->period_invoice ?? '-' }}</td>
                        <td class="text-center">
                            <span class="px-2 py-1 text-xs rounded-full {{ $invoice->tax_obligation ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $invoice->tax_obligation ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td class="text-right font-semibold">
                            @php
                                $displayTotalInvoice = $invoice->total_invoice > 0
                                    ? $invoice->total_invoice
                                    : ($invoice->grand_total ?: $invoice->total_amount);
                            @endphp
                            {{ number_format($displayTotalInvoice ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-right">
                            @php
                                $outstanding = $invoice->outstanding > 0 ? $invoice->outstanding : ($invoice->total_amount - $invoice->total_paid);
                                // Ensure no negative outstanding if paid > total (shouldn't happen but safe to handle)
                                $outstanding = max(0, $outstanding);
                            @endphp
                            <span class="font-bold {{ $outstanding > 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ number_format($outstanding, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="text-center">{{ $invoice->umur_invoice ?? '0' }}</td>
                        <td>{{ $invoice->pic_finance ?: ($invoice->billingGroup->pic_name ?? $invoice->contract->billingGroup->pic_name ?? '-') }}</td>
                        <td>{{ $invoice->email ?: ($invoice->billingGroup->pic_email ?? $invoice->contract->billingGroup->pic_email ?? $invoice->customer->email ?? '-') }}</td>
                        <td class="text-center">{{ $invoice->tax_code ?: ($invoice->contract->ppn_code ?? '-') }}</td>
                        <td>{{ $invoice->faktur_pajak ?? '-' }}</td>
                        <td>{{ $invoice->contract->quotation->survey->building_name ?? '-' }}</td>
                        <td>{{ $invoice->billing_address ?: ($invoice->customer->address ?? '-') }}</td>
                        <td>{{ $invoice->city_name ?: ($invoice->customer->city ?? '-') }}</td>
                        <td>{{ $invoice->internal_notes ?: ($invoice->contract->notes_finance ?? $invoice->contract->internal_remark ?? '-') }}</td>
                        <td>{{ $invoice->additional_notes ?: ($invoice->contract->notes ?? '-') }}</td>
                        <td>{{ $invoice->updated_at ? \Carbon\Carbon::parse($invoice->updated_at)->format('d M Y - H:i') : '-' }}</td>
                        <td>{{ $invoice->updater->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="28" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No invoices found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if($invoices->currentPage() > 1)
                    <a href="{{ $invoices->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if($invoices->lastPage() > 0)
                    @php
                        $start = max(1, $invoices->currentPage() - 2);
                        $end = min($invoices->lastPage(), $invoices->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $invoices->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $invoices->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $invoices->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $invoices->lastPage())
                            @if($end < $invoices->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $invoices->url($invoices->lastPage()) }}" class="page-number">{{ $invoices->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if($invoices->currentPage() < $invoices->lastPage())
                    <a href="{{ $invoices->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $invoices->currentPage() }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $invoices->lastPage() }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Regenerate Missing Invoice Modal -->
<div id="regenerateMissingModalOverlay" class="modal-overlay" onclick="closeRegenerateMissingModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Regenerate Invoice</h2>
            <button class="modal-close" onclick="closeRegenerateMissingModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="regenerate-contract-toolbar">
                <label class="form-label" style="display: flex; align-items: center; gap: 10px; margin: 0;">
                    <input type="checkbox" id="regenerateSelectAllContracts" class="w-4 h-4" onchange="toggleRegenerateSelectAllContracts()">
                    <span>Select All Contract</span>
                </label>
                <input type="text" id="regenerateContractSearch" class="form-input" placeholder="Search contract/customer..." oninput="filterRegenerateContracts()">
            </div>

            <div id="regenerateContractList" class="regenerate-contract-list">
                @forelse($invoiceRegenerationContracts as $contract)
                    @php
                        $contractSearch = strtolower(($contract['contract_number'] ?? '') . ' ' . ($contract['customer_name'] ?? '') . ' ' . ($contract['payment_method'] ?? ''));
                    @endphp
                    <label class="regenerate-contract-option" data-search="{{ $contractSearch }}">
                        <input type="checkbox" class="regenerate-contract-checkbox" value="{{ $contract['id'] }}">
                        <span>
                            <strong>{{ $contract['contract_number'] }}</strong>
                            <span style="display: block; color: #6b7280; font-size: 12px;">
                                {{ $contract['customer_name'] }} - {{ $contract['payment_method'] }}
                            </span>
                        </span>
                    </label>
                @empty
                    <div style="padding: 18px; color: #6b7280;">
                        Tidak ada contract active/current dengan job dan BA yang sudah selesai.
                    </div>
                @endforelse
            </div>

            <div id="regenerateInvoiceResult" class="regenerate-contract-result"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeRegenerateMissingModal()">Cancel</button>
            <button type="button" id="regenerateProceedBtn" class="btn btn-primary" onclick="proceedRegenerateMissingInvoices()">
                <i class="fas fa-play"></i>
                <span>Proceed</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Invoice Details</h2>
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

<!-- Hide Confirmation Modal -->
<div id="hideModalOverlay" class="hide-modal-overlay" onclick="closeHideModal()">
    <div class="hide-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Hide Invoice</h3>
        <p class="delete-modal-description" id="hideMessage">Are you sure you want to hide this invoice? This action can be undone later.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeHideModal()">Cancel</button>
            <button class="btn btn-hide" onclick="confirmHide()">Yes, Hide</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" fill="#10b981"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">All Set!</h3>
        <p class="delete-modal-description" id="successMessage">The invoice has been successfully created.</p>
    </div>
</div>

@php
    $defaultVatSettingData = $defaultVatSetting ? [
        'id' => $defaultVatSetting->id,
        'name' => $defaultVatSetting->name,
        'tax_rate' => (float) $defaultVatSetting->tax_rate,
    ] : null;
@endphp
<script>
const defaultVatSetting = @json($defaultVatSettingData);
const financeTaxCodeRules = @json($financeTaxCodeRules ?? []);
</script>

<script>
// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
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

function openRegenerateMissingModal() {
    const result = document.getElementById('regenerateInvoiceResult');
    result.classList.remove('show');
    result.innerHTML = '';
    document.getElementById('regenerateMissingModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeRegenerateMissingModal() {
    document.getElementById('regenerateMissingModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function toggleRegenerateSelectAllContracts() {
    const selectAll = document.getElementById('regenerateSelectAllContracts').checked;
    document.querySelectorAll('.regenerate-contract-checkbox').forEach(checkbox => {
        checkbox.checked = false;
        checkbox.disabled = selectAll;
        checkbox.closest('.regenerate-contract-option')?.classList.toggle('is-disabled', selectAll);
    });
}

function filterRegenerateContracts() {
    const keyword = document.getElementById('regenerateContractSearch').value.toLowerCase().trim();
    document.querySelectorAll('.regenerate-contract-option').forEach(option => {
        const searchableText = option.dataset.search || '';
        option.style.display = searchableText.includes(keyword) ? 'grid' : 'none';
    });
}

function getRegenerateSelectedContractIds() {
    return Array.from(document.querySelectorAll('.regenerate-contract-checkbox:checked'))
        .map(checkbox => parseInt(checkbox.value, 10))
        .filter(Number.isInteger);
}

function escapeRegenerateHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function renderRegenerateResult(data) {
    const result = document.getElementById('regenerateInvoiceResult');
    const summary = data.summary || {};
    const details = Array.isArray(data.details) ? data.details.slice(0, 20) : [];
    const detailRows = details.map(item => {
        const status = escapeRegenerateHtml(item.status || '-');
        const contract = escapeRegenerateHtml(item.contract_number || (item.contract_id ? `Contract #${item.contract_id}` : '-'));
        const period = item.period ? ` / ${escapeRegenerateHtml(item.period)}` : '';
        const message = escapeRegenerateHtml(item.message || '-');
        return `<li><strong>${contract}${period}</strong>: ${status} - ${message}</li>`;
    }).join('');

    result.innerHTML = `
        <strong>${escapeRegenerateHtml(data.message || 'Scan selesai.')}</strong>
        <div style="margin-top: 8px;">
            Checked: ${summary.contracts_checked || 0},
            Generated: ${summary.generated || 0},
            Skipped: ${summary.skipped || 0},
            Failed: ${summary.failed || 0},
            Blocked: ${summary.blocked || 0}
        </div>
        ${detailRows ? `<ul style="margin-top: 8px; padding-left: 18px;">${detailRows}</ul>` : ''}
        ${summary.generated > 0 ? '<div style="margin-top: 8px;">Refresh halaman untuk melihat invoice baru di list.</div>' : ''}
    `;
    result.classList.add('show');
}

function proceedRegenerateMissingInvoices() {
    const selectAll = document.getElementById('regenerateSelectAllContracts').checked;
    const contractIds = getRegenerateSelectedContractIds();

    if (!selectAll && contractIds.length === 0) {
        showWarningDialog('Pilih minimal satu contract atau gunakan Select All Contract.');
        return;
    }

    const button = document.getElementById('regenerateProceedBtn');
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Processing...</span>';

    fetch("{{ route('finance.invoices.regenerate-missing') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            select_all: selectAll,
            contract_ids: contractIds
        })
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || `HTTP error! status: ${response.status}`);
        }
        return data;
    })
    .then(data => {
        renderRegenerateResult(data);
        if ((data.summary?.generated || 0) > 0) {
            showSuccessDialog(data.message || 'Invoice berhasil digenerate.');
        }
    })
    .catch(error => {
        showErrorDialog(error.message || 'Gagal regenerate invoice.');
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = originalContent;
    });
}


// Modal functions
function openModal() {
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function getTaxFieldId(prefix, field) {
    return prefix ? `${prefix}_${field}` : field;
}

function getTaxField(prefix, field) {
    return document.getElementById(getTaxFieldId(prefix, field));
}

function getTaxRule(code) {
    return code ? (financeTaxCodeRules[code] || null) : null;
}

function getDefaultVatLabel() {
    if (!defaultVatSetting) {
        return 'Default PPN belum diatur';
    }

    return `${defaultVatSetting.name} (${parseFloat(defaultVatSetting.tax_rate || 0).toFixed(2)}%)`;
}

function buildTaxRuleNote(rule) {
    if (!rule) {
        return 'Kode pajak customer belum ditemukan di master Kode Pajak.';
    }

    const behavior = rule.applies_ppn_to_invoice
        ? 'PPN akan dibebankan di invoice.'
        : 'PPN tidak dibebankan sebagai PPN normal di invoice.';

    return `${behavior} ${rule.customer_status || ''}`;
}

function updateTaxPreview(prefix = '') {
    const subtotal = parseFloat(getTaxField(prefix, 'subtotal')?.value) || 0;
    const discount = parseFloat(getTaxField(prefix, 'discount_amount')?.value) || 0;
    const taxCode = getTaxField(prefix, 'tax_code')?.value || '';
    const rule = getTaxRule(taxCode);
    const subtotalAfterDiscount = Math.max(subtotal - discount, 0);
    const taxRate = (rule && rule.applies_ppn_to_invoice && defaultVatSetting)
        ? parseFloat(defaultVatSetting.tax_rate || 0)
        : 0;
    const taxAmount = subtotalAfterDiscount * taxRate / 100;
    const grandTotal = subtotalAfterDiscount + taxAmount;

    const taxSettingField = getTaxField(prefix, 'tax_setting_id');
    const taxRateField = getTaxField(prefix, 'tax_rate');
    const taxAmountField = getTaxField(prefix, 'tax_amount');
    const totalAmountField = getTaxField(prefix, 'total_amount');
    const noteField = getTaxField(prefix, 'tax_rule_note');
    const taxSettingDisplayField = getTaxField(prefix, 'tax_setting_display');

    if (taxSettingField) {
        taxSettingField.value = defaultVatSetting?.id || '';
    }

    if (taxRateField) {
        taxRateField.value = taxRate.toFixed(2);
    }

    if (taxAmountField) {
        taxAmountField.value = taxAmount.toFixed(2);
    }

    if (totalAmountField) {
        totalAmountField.value = grandTotal.toFixed(2);
    }

    if (taxSettingDisplayField) {
        taxSettingDisplayField.value = getDefaultVatLabel();
    }

    if (noteField) {
        noteField.value = buildTaxRuleNote(rule);
    }
}

function calculateTotal() {
    updateTaxPreview('');
}

function calculateEditTotal() {
    updateTaxPreview('edit');
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create New Invoice';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Create a new invoice for your customer.</p>
        <form id="createForm">
            <div class="form-group">
                <label class="form-label">Invoice Number</label>
                <input type="text" name="invoice_number" class="form-input" placeholder="Auto-generated" readonly style="background-color: #f9fafb; color: #6b7280;">
                <small class="text-gray-500 text-xs">Invoice number will be automatically generated</small>
            </div>
            <div class="form-group">
                <label class="form-label">Customer *</label>
                <select name="customer_id" id="customer_id" class="form-select" required onchange="loadCustomerTaxNumber()">
                    <option value="">Select Customer</option>
                    @forelse($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @empty
                        <option value="" disabled>No customers available</option>
                    @endforelse
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Invoice Date *</label>
                <input type="date" name="invoice_date" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Due Date *</label>
                <input type="date" name="due_date" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Subtotal *</label>
                <input type="number" name="subtotal" id="subtotal" class="form-input" placeholder="Enter subtotal" step="0.01" required onchange="calculateTotal()">
            </div>
            <div class="form-group">
                <label class="form-label">Tax Code</label>
                <input type="text" name="tax_code" id="tax_code" class="form-input" placeholder="Akan terisi dari customer" readonly style="background-color: #f9fafb; color: #6b7280;">
                <small class="text-gray-500 text-xs">Diambil dari tax customer aktif / ppn code customer.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Tax Number</label>
                <input type="text" name="tax_number" id="tax_number" class="form-input" placeholder="Will auto-fill from customer (snapshot)" readonly style="background-color: #f9fafb; color: #6b7280;">
                <small class="text-gray-500 text-xs">Snapshot at time of invoice creation (auto-filled from customer_taxes)</small>
            </div>
            <div class="form-group">
                <label class="form-label">NPWP Number</label>
                <input type="text" name="npwp_number" id="npwp_number" class="form-input" placeholder="Will auto-fill from customer (snapshot)" readonly style="background-color: #f9fafb; color: #6b7280;">
                <small class="text-gray-500 text-xs">Snapshot at time of invoice creation</small>
            </div>
            <div class="form-group">
                <label class="form-label">Default PPN</label>
                <input type="text" id="tax_setting_display" class="form-input" value="${getDefaultVatLabel()}" readonly style="background-color: #f9fafb; color: #6b7280;">
                <small class="text-gray-500 text-xs">Tarif PPN diambil dari Tax Setting default yang aktif.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Rule Pajak</label>
                <textarea id="tax_rule_note" class="form-input" rows="3" readonly style="background-color: #f9fafb; color: #6b7280;"></textarea>
            </div>
            <input type="hidden" name="tax_setting_id" id="tax_setting_id" value="${defaultVatSetting?.id || ''}">
            <input type="hidden" name="tax_rate" id="tax_rate" value="">
            <input type="hidden" name="tax_address" id="tax_address" value="">
            <div class="form-group">
                <label class="form-label">Tax Amount</label>
                <input type="number" name="tax_amount" id="tax_amount" class="form-input" step="0.01" readonly style="background-color: #f9fafb; color: #6b7280;">
            </div>
            <div class="form-group">
                <label class="form-label">Total Amount</label>
                <input type="number" name="grand_total" id="total_amount" class="form-input" step="0.01" readonly style="background-color: #f9fafb; color: #6b7280;">
            </div>
            <div class="form-group">
                <label class="form-label">Invoice Status *</label>
                <select name="invoice_status" class="form-select" required>
                    <option value="">Select Status</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Method *</label>
                <select name="payment_method" class="form-select" required>
                    <option value="">Select Payment Method</option>
                    <option value="virtual_account">Virtual Account</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tax Obligation</label>
                <select name="tax_obligation" class="form-select">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="additional_notes" class="form-input" rows="3" placeholder="Additional notes (optional)"></textarea>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Create Invoice</button>
        </div>
    `;
    openModal();
    calculateTotal();
}

function openViewModal(id) {
    // Load data via AJAX
    fetch(`/finance/invoices/${id}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
        .then(response => {
            if (response.success) {
                const data = response.data;
                const meta = response.meta || {};
                document.getElementById('modalTitle').textContent = 'Invoice Details';
            document.getElementById('modalBody').innerHTML = `
                <div class="space-y-4">
                    <div class="detail-item">
                        <label class="form-label">Invoice Number</label>
                        <p class="detail-value">${data.invoice_number || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Customer</label>
                        <p class="detail-value">${data.customer?.name || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Invoice Date</label>
                        <p class="detail-value">${data.invoice_date ? new Date(data.invoice_date).toLocaleDateString('id-ID') : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Due Date</label>
                        <p class="detail-value">${data.due_date ? new Date(data.due_date).toLocaleDateString('id-ID') : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Subtotal</label>
                        <p class="detail-value">${data.subtotal ? 'Rp ' + new Intl.NumberFormat('id-ID').format(data.subtotal) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Tax Amount</label>
                        <p class="detail-value">${data.tax_amount ? 'Rp ' + new Intl.NumberFormat('id-ID').format(data.tax_amount) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Total Amount</label>
                        <p class="detail-value font-semibold">${data.total_amount ? 'Rp ' + new Intl.NumberFormat('id-ID').format(data.total_amount) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Invoice Status</label>
                        <p class="detail-value">${data.invoice_status ? data.invoice_status.charAt(0).toUpperCase() + data.invoice_status.slice(1) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Payment Method</label>
                        <p class="detail-value">${data.payment_method ? data.payment_method.charAt(0).toUpperCase() + data.payment_method.slice(1) : 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Tax Code</label>
                        <p class="detail-value">${data.tax_code || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Tax Setting</label>
                        <p class="detail-value">${getTaxSettingDisplay(data)}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Tax Address (Snapshot)</label>
                        <p class="detail-value">${data.tax_address || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Tax Number (Snapshot)</label>
                        <p class="detail-value">${data.tax_number || 'N/A'}</p>
                        <small class="text-gray-500 text-xs">Tax number at time of invoice creation</small>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">NPWP Number (Snapshot)</label>
                        <p class="detail-value">${data.npwp_number || 'N/A'}</p>
                        <small class="text-gray-500 text-xs">NPWP at time of invoice creation</small>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Created At</label>
                        <p class="detail-value">${data.created_at ? new Date(data.created_at).toLocaleString('id-ID') : 'N/A'}</p>
                    </div>
                    ${renderFilesSupport(data.files_support)}
                </div>
            `;
                document.getElementById('modalFooter').innerHTML = renderViewModalFooter(id, data, meta);
            openModal();

            // Check for notes_finance in the contract and display alert
            if (data.contract && data.contract.notes_finance && data.contract.notes_finance.trim() !== '') {
                // Use setTimeout to ensure modal is fully open before showing alert (or show on top)
                setTimeout(() => {
                    Swal.fire({
                        title: '<strong>Finance Note</strong>',
                        icon: 'info',
                        html: `<div class="text-left" style="white-space: pre-line;">${data.contract.notes_finance}</div>`,
                        showCloseButton: true,
                        focusConfirm: false,
                        allowOutsideClick: false, // Prevent dismissing by clicking outside
                        confirmButtonText: '<i class="fa fa-thumbs-up"></i> Acknowledge',
                        confirmButtonAriaLabel: 'Thumbs up, acknowledge!',
                        customClass: {
                            container: 'swal-on-top', // Ensure it appears above the modal
                            content: 'text-left'
                        },
                        zIndex: 1060 // Bootstrap modal is 1050, ensure this is higher
                    });
                }, 300);
            }
            } else {
                console.error('Invalid response format:', response);
                showErrorDialog('Format respons invoice tidak valid.');
            }
        })
        .catch(error => {
            console.error('Error loading invoice data:', error);
            showErrorDialog('Gagal memuat data invoice: ' + error.message);
        });
}

function openEditModal(id) {
    // Load data via AJAX
    fetch(`/finance/invoices/${id}/edit`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
        .then(response => {
            if (response.success) {
                const data = response.data;
                const customers = response.customers;
                const taxSettings = response.taxSettings;
                
                document.getElementById('modalTitle').textContent = 'Edit Invoice';
                document.getElementById('modalBody').innerHTML = `
                    <p class="text-gray-600 mb-6 text-center">Update the invoice details.</p>
                    <form id="editForm">
                        <input type="hidden" name="id" value="${data.id}">
                        <div class="form-group">
                            <label class="form-label">Invoice Number *</label>
                            <input type="text" name="invoice_number" class="form-input" value="${data.invoice_number || ''}" readonly style="background-color: #f9fafb; color: #6b7280;">
                            <small class="text-gray-500 text-xs">Invoice number cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Customer *</label>
                            <select name="customer_id" id="edit_customer_id" class="form-select" required onchange="loadEditCustomerTaxNumber()">
                                <option value="">Select Customer</option>
                                ${customers.map(customer => 
                                    `<option value="${customer.id}" ${data.customer_id == customer.id ? 'selected' : ''}>${customer.name}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tax Code</label>
                            <input type="text" name="tax_code" id="edit_tax_code" class="form-input" value="${data.tax_code || ''}" readonly style="background-color: #f9fafb; color: #6b7280;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Default PPN</label>
                            <input type="text" id="edit_tax_setting_display" class="form-input" value="${getDefaultVatLabel()}" readonly style="background-color: #f9fafb; color: #6b7280;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rule Pajak</label>
                            <textarea id="edit_tax_rule_note" class="form-input" rows="3" readonly style="background-color: #f9fafb; color: #6b7280;"></textarea>
                        </div>
                        <input type="hidden" name="tax_setting_id" id="edit_tax_setting_id" value="${defaultVatSetting?.id || data.tax_setting_id || ''}">
                        <input type="hidden" name="tax_rate" id="edit_tax_rate" value="${defaultVatSetting?.tax_rate || data.tax_setting?.tax_rate || data.tax_rate || ''}">
                        <input type="hidden" name="tax_address" id="edit_tax_address" value="${data.tax_address || ''}">
                        <div class="form-group">
                            <label class="form-label">Tax Number</label>
                            <input type="text" name="tax_number" id="edit_tax_number" class="form-input" value="${data.tax_number || ''}" placeholder="Tax number (snapshot)" readonly style="background-color: #f9fafb; color: #6b7280;">
                            <small class="text-gray-500 text-xs">Snapshot at time of invoice creation (usually from customer_taxes)</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">NPWP Number</label>
                            <input type="text" name="npwp_number" id="edit_npwp_number" class="form-input" value="${data.npwp_number || ''}" placeholder="NPWP number (snapshot)" readonly style="background-color: #f9fafb; color: #6b7280;">
                            <small class="text-gray-500 text-xs">Snapshot at time of invoice creation</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Invoice Date *</label>
                            <input type="date" name="invoice_date" class="form-input" value="${data.invoice_date ? data.invoice_date.split('T')[0] : ''}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Due Date *</label>
                            <input type="date" name="due_date" class="form-input" value="${data.due_date ? data.due_date.split('T')[0] : ''}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subtotal *</label>
                            <input type="number" name="subtotal" id="edit_subtotal" class="form-input" value="${data.subtotal || ''}" placeholder="Enter subtotal" step="0.01" required onchange="calculateEditTotal()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tax Amount</label>
                            <input type="number" name="tax_amount" id="edit_tax_amount" class="form-input" value="${data.tax_amount || ''}" placeholder="Enter tax amount" step="0.01" readonly style="background-color: #f9fafb; color: #6b7280;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Total Amount *</label>
                            <input type="number" name="grand_total" id="edit_total_amount" class="form-input" value="${data.grand_total || ''}" placeholder="Enter total amount" step="0.01" required readonly style="background-color: #f9fafb; color: #6b7280;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Invoice Status *</label>
                            <select name="invoice_status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="draft" ${data.invoice_status == 'draft' ? 'selected' : ''}>Draft</option>
                                <option value="sent" ${data.invoice_status == 'sent' ? 'selected' : ''}>Sent</option>
                                <option value="paid" ${data.invoice_status == 'paid' ? 'selected' : ''}>Paid</option>
                                <option value="overdue" ${data.invoice_status == 'overdue' ? 'selected' : ''}>Overdue</option>
                                <option value="cancelled" ${data.invoice_status == 'cancelled' ? 'selected' : ''}>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Payment Method *</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">Select Payment Method</option>
                                <option value="virtual_account" ${data.payment_method == 'virtual_account' ? 'selected' : ''}>Virtual Account</option>
                                <option value="bank_transfer" ${data.payment_method == 'bank_transfer' ? 'selected' : ''}>Bank Transfer</option>
                                <option value="cash" ${data.payment_method == 'cash' ? 'selected' : ''}>Cash</option>
                            </select>
                        </div>
                    </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Update Invoice</button>
                </div>
            `;
            openModal();
            calculateEditTotal();
            } else {
                console.error('Invalid response format:', response);
                showErrorDialog('Format respons invoice tidak valid.');
            }
        })
        .catch(error => {
            console.error('Error loading invoice data:', error);
            showErrorDialog('Gagal memuat data invoice.');
        });
}

function renderViewModalFooter(id, data, meta = {}) {
    const canRegenerate = Boolean(meta.can_regenerate) && data.invoice_status === 'cancelled';
    const regenerateButton = canRegenerate
        ? `<button type="button" class="btn btn-secondary" onclick="regenerateInvoice(${id})">Regenerate</button>`
        : '';

    return `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Close</button>
            ${regenerateButton}
            <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
        </div>
    `;
}

function regenerateInvoice(id) {
    Swal.fire({
        title: 'Regenerate invoice?',
        text: 'Invoice cancelled ini akan dibuatkan invoice baru berdasarkan data job/invoice terbaru.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, regenerate',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        fetch(`/finance/invoices/${id}/regenerate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            return data;
        })
        .then(data => {
            closeModal();
            showSuccessDialog(data.message || 'Invoice berhasil diregenerate.').then(() => location.reload());
        })
        .catch(error => {
            console.error('Error regenerating invoice:', error);
            showErrorDialog(error.message || 'Gagal regenerate invoice.');
        });
    });
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    fetch('/finance/invoices', {
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
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeModal();
            showSuccessModal('The invoice has been successfully created.');
        } else {
            console.error('Server response:', data);
            if (data.errors) {
                let errorMessage = 'Validasi gagal:\n';
                for (const field in data.errors) {
                    errorMessage += `- ${field}: ${data.errors[field].join(', ')}\n`;
                }
                showErrorDialog(errorMessage, 'Validasi Gagal');
            } else {
                showErrorDialog('Gagal membuat invoice: ' + (data.message || 'Terjadi kesalahan yang tidak diketahui.'));
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal membuat invoice: ' + error.message);
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    fetch(`/finance/invoices/${id}`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeModal();
            showSuccessDialog('Invoice berhasil diperbarui.').then(() => location.reload());
        } else {
            showErrorDialog('Gagal memperbarui invoice: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal memperbarui invoice.');
    });
}

// Helper function to get tax setting display
function getTaxSettingDisplay(data) {
    // Try different possible data structures
    if (data.tax_setting && data.tax_setting.name) {
        const taxSetting = data.tax_setting;
        const taxType = taxSetting.tax_type ? taxSetting.tax_type.toUpperCase() : '';
        const taxRate = taxSetting.tax_rate ? parseFloat(taxSetting.tax_rate).toFixed(2) : '0';
        return `${taxSetting.name} (${taxType} ${taxRate}%)`;
    }
    if (data.tax_setting && data.tax_setting.tax_setting_name) {
        const taxSetting = data.tax_setting;
        const taxType = taxSetting.tax_type ? taxSetting.tax_type.toUpperCase() : '';
        const taxRate = taxSetting.tax_rate ? parseFloat(taxSetting.tax_rate).toFixed(2) : '0';
        return `${taxSetting.tax_setting_name} (${taxType} ${taxRate}%)`;
    }
    if (data.tax_setting_name) {
        return data.tax_setting_name;
    }
    if (data.tax_setting_id) {
        return 'Tax Setting ID: ' + data.tax_setting_id;
    }
    return 'N/A';
}

// Hide Modal functions
let selectedIdsForHide = [];

function openHideModal() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    selectedIdsForHide = Array.from(checkboxes).map(checkbox => checkbox.value);
    
    if (selectedIdsForHide.length === 0) {
        showWarningDialog('Pilih minimal satu invoice untuk disembunyikan.');
        return;
    }
    
    const count = selectedIdsForHide.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this invoice? This action can be undone later.'
        : `Are you sure you want to hide ${count} invoices? This action can be undone later.`;
    
    document.getElementById('hideMessage').textContent = message;
    document.getElementById('hideModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeHideModal() {
    document.getElementById('hideModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function confirmHide() {
    closeHideModal();
    
    fetch('/finance/invoices/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: selectedIdsForHide })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessModal(result.count);
        } else {
            showErrorDialog('Gagal menyembunyikan invoice: ' + (result.message || 'Terjadi kesalahan yang tidak diketahui.'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Terjadi kesalahan jaringan.');
    });
}

// Auto-fill Tax Number from Customer Taxes (per report-mom5.md)
function loadCustomerTaxNumber() {
    const customerId = document.getElementById('customer_id').value;
    
    if (!customerId) {
        document.getElementById('tax_number').value = '';
        document.getElementById('npwp_number').value = '';
        document.getElementById('tax_code').value = '';
        document.getElementById('tax_address').value = '';
        calculateTotal();
        return;
    }
    
    fetch(`/api/customers/${customerId}/active-tax-number`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.data) {
            document.getElementById('tax_number').value = data.data.tax_number || '';
            document.getElementById('npwp_number').value = data.data.npwp_number || data.data.tax_number || '';
            document.getElementById('tax_code').value = data.data.ppn_code || '';
            document.getElementById('tax_address').value = data.data.tax_address || '';
            calculateTotal();
        } else {
            document.getElementById('tax_number').value = '';
            document.getElementById('npwp_number').value = '';
            document.getElementById('tax_code').value = '';
            document.getElementById('tax_address').value = '';
            calculateTotal();
        }
    })
    .catch(error => {
        console.error('Error loading customer tax number:', error);
    });
}

function loadEditCustomerTaxNumber() {
    const customerId = document.getElementById('edit_customer_id').value;

    if (!customerId) {
        document.getElementById('edit_tax_number').value = '';
        document.getElementById('edit_npwp_number').value = '';
        document.getElementById('edit_tax_code').value = '';
        document.getElementById('edit_tax_address').value = '';
        calculateEditTotal();
        return;
    }

    fetch(`/api/customers/${customerId}/active-tax-number`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.data) {
            document.getElementById('edit_tax_number').value = data.data.tax_number || '';
            document.getElementById('edit_npwp_number').value = data.data.npwp_number || data.data.tax_number || '';
            document.getElementById('edit_tax_code').value = data.data.ppn_code || '';
            document.getElementById('edit_tax_address').value = data.data.tax_address || '';
            calculateEditTotal();
        } else {
            document.getElementById('edit_tax_number').value = '';
            document.getElementById('edit_npwp_number').value = '';
            document.getElementById('edit_tax_code').value = '';
            document.getElementById('edit_tax_address').value = '';
            calculateEditTotal();
        }
    })
    .catch(error => {
        console.error('Error loading customer tax number:', error);
    });
}

// Helper function to display tax setting info
function getTaxSettingDisplay(data) {
    if (data.tax_setting) {
        return `${data.tax_setting.name} (${data.tax_setting.tax_type_label || data.tax_setting.tax_type} - ${parseFloat(data.tax_setting.tax_rate).toFixed(2)}%)`;
    }
    return 'N/A';
}

// Helper function to render Files Support section
function renderFilesSupport(filesSupport) {
    if (!filesSupport || filesSupport.length === 0) {
        return '';
    }
    
    let html = `
        <div class="detail-item" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid #e5e7eb;">
            <label class="form-label" style="font-size: 1rem; color: #214589; font-weight: 600;">
                <i class="fas fa-file-alt me-2"></i>Files Support (Verified Contract Files)
            </label>
            <div class="mt-3">
    `;
    
    filesSupport.forEach((file, index) => {
        const fileIcon = getFileIcon(file.file_type);
        const fileSize = file.file_size ? (file.file_size / 1024).toFixed(2) + ' KB' : '-';
        
        html += `
            <div class="d-flex align-items-center justify-content-between p-2 mb-2" style="background-color: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas ${fileIcon} text-primary" style="font-size: 1.25rem;"></i>
                    <div>
                        <div style="font-weight: 500; color: #374151;">${file.file_name}</div>
                        <div style="font-size: 0.75rem; color: #6b7280;">
                            <span class="badge bg-secondary me-1">${file.file_type}</span>
                            <span>${fileSize}</span>
                            <span class="mx-1">•</span>
                            <span>Verified by ${file.verified_by}</span>
                            <span class="mx-1">•</span>
                            <span>${file.verified_at}</span>
                        </div>
                    </div>
                </div>
                <a href="${file.file_path}" target="_blank" class="btn btn-sm btn-info" title="Download">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        `;
    });
    
    html += `
            </div>
        </div>
    `;
    
    return html;
}

// Helper function to get file icon based on file type
function getFileIcon(fileType) {
    const type = (fileType || '').toLowerCase();
    if (type.includes('pdf') || type === 'contract_scan' || type === 'tax_scan') {
        return 'fa-file-pdf';
    } else if (type.includes('doc')) {
        return 'fa-file-word';
    } else if (type.includes('xls')) {
        return 'fa-file-excel';
    } else if (type.includes('image') || type === 'npwp_scan') {
        return 'fa-file-image';
    } else {
        return 'fa-file';
    }
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'The invoice has been successfully hidden.'
        : `${count} invoices have been successfully hidden.`;
    
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Auto close after 3 seconds
    setTimeout(() => {
        closeSuccessModal();
        location.reload();
    }, 3000);
}

function closeSuccessModal() {
    document.getElementById('successModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Function to open invoice view page
function openViewModal(id) {
    window.location.href = "{{ route('finance.invoices.show', ['invoice' => 'PLACEHOLDER']) }}".replace('PLACEHOLDER', id);
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRegenerateMissingModal();
        closeModal();
        closeHideModal();
        closeSuccessModal();
    }
});

// Click outside to close modals
document.getElementById('hideModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeHideModal();
    }
});

document.getElementById('successModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuccessModal();
        location.reload();
    }
});

// Apply Print Status Filter: Independent of dates as requested
function applyPrintStatusFilter() {
    const status = document.getElementById('filterPrintStatus').value;
    const searchInput = document.getElementById('searchInput');
    const searchValue = searchInput ? searchInput.value.trim() : '';
    
    const params = new URLSearchParams();
    params.set('print_status', status);
    if (searchValue) {
        params.set('search', searchValue);
    }
    
    window.location.href = '?' + params.toString();
}

// Flatpickr Initialization
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#filterDateFrom", {
        altInput: true,
        altFormat: "d M Y",
        dateFormat: "Y-m-d",
        defaultDate: "{{ request('date_from', now()->toDateString()) }}"
    });

    flatpickr("#filterDateTo", {
        altInput: true,
        altFormat: "d M Y",
        dateFormat: "Y-m-d",
        defaultDate: "{{ request('date_to', now()->addDays(14)->toDateString()) }}"
    });
    
    // Search on Enter - independent search without date filter
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    }
});

// Independent search function (only search, no date filter)
function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchValue = searchInput ? searchInput.value.trim() : '';
    
    if (searchValue) {
        window.location.href = '?search=' + encodeURIComponent(searchValue);
    } else {
        window.location.href = window.location.pathname;
    }
}

// Apply filters: combine date + search
function applyFilters() {
    const dateFromInput = document.getElementById('filterDateFrom');
    const dateToInput = document.getElementById('filterDateTo');
    const searchInput = document.getElementById('searchInput');
    
    const dateFrom = dateFromInput._flatpickr ? dateFromInput._flatpickr.selectedDates[0] : null;
    const dateTo = dateToInput._flatpickr ? dateToInput._flatpickr.selectedDates[0] : null;
    const searchValue = searchInput ? searchInput.value.trim() : '';
    
    const params = new URLSearchParams();
    
    if (dateFrom) {
        params.set('date_from', dateFrom.toISOString().split('T')[0]);
    }
    if (dateTo) {
        params.set('date_to', dateTo.toISOString().split('T')[0]);
    }
    if (searchValue) {
        params.set('search', searchValue);
    }

    const printStatusInput = document.getElementById('filterPrintStatus');
    if (printStatusInput) {
        params.set('print_status', printStatusInput.value);
    }
    
    window.location.href = '?' + params.toString();
}

function resetFilters() {
    window.location.href = window.location.pathname;
}
</script>

<!-- Flatpickr Script -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endsection
