@extends('layouts.app')

@section('title', 'Bank Payments')
@section('breadcrumb', 'Home / Company / Bank Payments')

@section('content')
<style>
    /* Premium Pipeline-style Table from Master User */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 10px 10px;
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }
    
    .responsive-table {
        min-width: 1500px;
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: normal;
        word-break: break-all;
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
        border: none;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
    }

    /* Filter Row Styling - Tighten and Color Fix */
    .responsive-table thead tr:first-child th {
        background-color: #214589 !important;
        color: white !important;
        height: 40px !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    .responsive-table thead .filter-row th {
        background-color: #f9fafb !important;
        color: #4b5563 !important;
        padding: 4px 8px !important;
        border-bottom: 1px solid #e5e7eb !important;
        position: sticky;
        top: 40px;
        z-index: 9;
    }
    
    .table-filter {
        height: 30px !important;
        font-size: 12px !important;
        padding: 4px 10px !important;
        background-color: white !important;
        border: 1px solid #d1d5db !important;
        border-radius: 4px !important;
        width: 100% !important;
        color: #374151 !important;
    }

    .table-filter::placeholder {
        color: #9ca3af !important;
        opacity: 1;
        font-size: 11px;
    }

    /* Pagination Specific Styles from Pipeline */
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

    /* Button Styles from Pipeline */
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

    /* Modal Styles from Pipeline */
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
        overflow: visible;
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
        border-radius: 12px 12px 0 0;
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
    }

    .modal-body {
        padding: 20px;
        overflow-y: auto;
    }

    .modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: center;
        gap: 16px;
    }

    .status-badge {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
</style>

<div class="flex flex-col w-full">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header from Pipeline -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4 border-bottom">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Bank Payments</h1>
            </div>
            <div class="flex flex-row justify-end items-center gap-2">
                <button class="btn btn-secondary" onclick="deleteSelected()" style="color: #dc2626; border-color: #fca5a5; background-color: #fef2f2;">
                    <i class="fas fa-eye-slash"></i>
                    <span>Hide Selected</span>
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span>Add Bank Payment</span>
                </button>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container w-full" style="border: 1px solid #e5e7eb; border-top: none;">
            <table class="responsive-table" id="bankPaymentsTable">
                <thead>
                    <tr style="height: 40px !important;">
                        <th class="text-center" style="width: 50px;">
                            <input type="checkbox" id="headerSelectAll">
                        </th>
                        <th style="width: 200px;">Bank Payment Name</th>
                        <th style="width: 150px;">Account Number</th>
                        <th style="width: 150px;">Bank</th>
                        <th style="width: 150px;">KCP</th>
                        <th style="width: 150px;">Phone</th>
                        <th style="width: 120px;">VA Prefix</th>
                        <th style="width: 100px;">Length</th>
                        <th style="width: 150px;">Range</th>
                        <th style="width: 100px;">Default</th>
                        <th style="width: 120px;">Status</th>
                    </tr>
                    <tr class="filter-row" style="height: 40px !important;">
                        <th class="text-center">
                            <button onclick="resetFilters()" class="btn btn-secondary btn-sm" style="padding: 0; height: 26px; width: 26px; min-width: 26px; display: flex; align-items: center; justify-content: center; background: white; border: 1px solid #d1d5db;">
                                <i class="fas fa-undo" style="font-size: 10px; color: #6b7280;"></i>
                            </button>
                        </th>
                        <th><input type="text" class="table-filter" name="filter[account_name]" value="{{ request('filter.account_name') }}" placeholder="Filter..."></th>
                        <th><input type="text" class="table-filter" name="filter[account_number]" value="{{ request('filter.account_number') }}" placeholder="Filter..."></th>
                        <th><input type="text" class="table-filter" name="filter[bank__bank_name]" value="{{ request('filter.bank__bank_name') }}" placeholder="Filter..."></th>
                        <th><input type="text" class="table-filter" name="filter[branch_name]" value="{{ request('filter.branch_name') }}" placeholder="Filter..."></th>
                        <th><input type="text" class="table-filter" name="filter[phone]" value="{{ request('filter.phone') }}" placeholder="Filter..."></th>
                        <th><input type="text" class="table-filter" name="filter[bank_va_number]" value="{{ request('filter.bank_va_number') }}" placeholder="Filter..."></th>
                        <th><input type="text" class="table-filter" name="filter[length]" value="{{ request('filter.length') }}" placeholder="Filter..."></th>
                        <th></th>
                        <th>
                            <select class="table-filter" name="filter[is_default_va]" onchange="applyFilters()">
                                <option value="">All</option>
                                <option value="1" {{ request('filter.is_default_va') == '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ request('filter.is_default_va') == '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </th>
                        <th>
                            <select class="table-filter" name="filter[is_active]" onchange="applyFilters()">
                                <option value="">All</option>
                                <option value="1" {{ request('filter.is_active') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('filter.is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bankPayments as $payment)
                        <tr onclick="openViewModal({{ $payment->id }})">
                            <td class="text-center" onclick="event.stopPropagation()">
                                <input type="checkbox" class="row-checkbox" value="{{ $payment->id }}">
                            </td>
                            <td class="font-medium" style="color: #214589;">{{ $payment->account_name }}</td>
                            <td>{{ $payment->account_number }}</td>
                            <td>{{ $payment->bank->bank_name ?? '-' }}</td>
                            <td>{{ $payment->branch_name ?? '-' }}</td>
                            <td>{{ $payment->phone ?? '-' }}</td>
                            <td>{{ $payment->bank_va_number ?? '-' }}</td>
                            <td class="text-center">{{ $payment->length ?? '-' }}</td>
                            <td>
                                @if($payment->start_number || $payment->end_number)
                                    <span class="text-xs">{{ $payment->start_number }} - {{ $payment->end_number }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($payment->is_default_va)
                                    <span class="status-badge bg-blue-100 text-blue-700 border border-blue-200">Yes</span>
                                @else
                                    <span class="text-gray-400 text-xs">No</span>
                                @endif
                            </td>
                            <td>
                                @if($payment->is_active)
                                    <span class="status-badge bg-green-100 text-green-700 border border-green-200">Active</span>
                                @else
                                    <span class="status-badge bg-red-100 text-red-700 border border-red-200">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-10 text-gray-500">No bank payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer Bar Identik Master User -->
        <div class="flex flex-row justify-center items-center w-full bg-white rounded-b-[10px] p-4 border-t">
            <div class="pagination-controls">
                @if($bankPayments->onFirstPage())
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @else
                    <a href="{{ $bankPayments->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @endif

                <div class="flex items-center gap-2">
                    @php
                        $start = max(1, $bankPayments->currentPage() - 2);
                        $end = min($bankPayments->lastPage(), $bankPayments->currentPage() + 2);
                    @endphp

                    @if($start > 1)
                        <a href="{{ $bankPayments->url(1) }}" class="page-number">1</a>
                        @if($start > 2)
                            <span class="text-sm text-gray-500">...</span>
                        @endif
                    @endif

                    @for($i = $start; $i <= $end; $i++)
                        @if($i == $bankPayments->currentPage())
                            <span class="page-number active">{{ $i }}</span>
                        @else
                            <a href="{{ $bankPayments->url($i) }}" class="page-number">{{ $i }}</a>
                        @endif
                    @endfor

                    @if($end < $bankPayments->lastPage())
                        @if($end < $bankPayments->lastPage() - 1)
                            <span class="text-sm text-gray-500">...</span>
                        @endif
                        <a href="{{ $bankPayments->url($bankPayments->lastPage()) }}" class="page-number">{{ $bankPayments->lastPage() }}</a>
                    @endif
                </div>

                @if($bankPayments->hasMorePages())
                    <a href="{{ $bankPayments->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif

                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]" onchange="window.location.href = this.value">
                        @for($i = 1; $i <= $bankPayments->lastPage(); $i++)
                            <option value="{{ $bankPayments->url($i) }}" {{ $i == $bankPayments->currentPage() ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $bankPayments->lastPage() }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Bank Payment</h2>
            <button onclick="closeModal()" class="modal-close">×</button>
        </div>
        <div id="modalBody" class="modal-body"></div>
        <div id="modalFooter" class="modal-footer"></div>
    </div>
</div>

@push('scripts')
<script>
    // General Functions
    function applyFilters() {
        const url = new URL(window.location.href);
        document.querySelectorAll('.table-filter').forEach(input => {
            if (input.value) url.searchParams.set(input.name, input.value);
            else url.searchParams.delete(input.name);
        });
        url.searchParams.set('page', 1);
        window.location.href = url.toString();
    }

    function resetFilters() {
        window.location.href = window.location.pathname;
    }

    $(document).ready(function() {
        $('.table-filter').on('keypress', function(e) {
            if (e.key === 'Enter') applyFilters();
        });
    });

    // Select All logic
    document.getElementById('headerSelectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
    });

    // Modal Logic
    function openModal(title) {
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalOverlay').classList.add('show');
    }

    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('show');
    }

    function openViewModal(id) {
        openModal('Detail Bank Payment');
        document.getElementById('modalBody').innerHTML = '<div class="text-center py-10"><i class="fas fa-spinner fa-spin text-2xl text-[#214589]"></i></div>';
        
        fetch(`/company/bank-payments/${id}`)
            .then(res => res.json())
            .then(res => {
                const data = res.data;
                document.getElementById('modalBody').innerHTML = `
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Bank Name</label>
                            <p class="font-bold text-[#214589] border-b pb-1">${data.bank?.bank_name || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">KCP / Branch</label>
                            <p class="font-bold text-[#214589] border-b pb-1">${data.branch_name || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Account Name</label>
                            <p class="font-bold text-[#214589] border-b pb-1">${data.account_name}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Account Number</label>
                            <p class="font-bold text-[#214589] border-b pb-1">${data.account_number}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Phone</label>
                            <p class="font-bold text-gray-700 border-b pb-1">${data.phone || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Fax</label>
                            <p class="font-bold text-gray-700 border-b pb-1">${data.fax || '-'}</p>
                        </div>
                        <div class="col-span-2 mt-4">
                            <h4 class="font-bold text-sm text-[#214589] mb-3 bg-blue-50 p-2 rounded">Virtual Account Config</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-xs text-gray-400 mb-1">VA Prefix</label><p class="font-bold border-b pb-1">${data.bank_va_number || '-'}</p></div>
                                <div><label class="block text-xs text-gray-400 mb-1">VA Length</label><p class="font-bold border-b pb-1">${data.length || '-'}</p></div>
                                <div><label class="block text-xs text-gray-400 mb-1">Range Start</label><p class="font-bold border-b pb-1">${data.start_number || '-'}</p></div>
                                <div><label class="block text-xs text-gray-400 mb-1">Range End</label><p class="font-bold border-b pb-1">${data.end_number || '-'}</p></div>
                            </div>
                        </div>
                        <div class="col-span-2">
                             <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Address</label>
                             <p class="text-gray-700 border-b pb-1">${data.address || '-'}</p>
                        </div>
                    </div>
                `;
                document.getElementById('modalFooter').innerHTML = `
                    <button class="btn btn-secondary" onclick="closeModal()">Close</button>
                    <button class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                `;
            });
    }

    function openCreateModal() {
        openModal('Add Bank Payment');
        document.getElementById('modalBody').innerHTML = `
            <form id="bankForm" class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="block text-sm font-semibold mb-1">Bank *</label>
                    <select name="bank_id" class="w-full p-2 border rounded-md" required>
                        <option value="">Select Bank</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->bank_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="block text-sm font-semibold mb-1">KCP / Branch *</label>
                    <input type="text" name="branch_name" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="form-group">
                    <label class="block text-sm font-semibold mb-1">Account Name *</label>
                    <input type="text" name="account_name" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="form-group">
                    <label class="block text-sm font-semibold mb-1">Account Number *</label>
                    <input type="text" name="account_number" class="w-full p-2 border rounded-md" required>
                </div>
                <div class="form-group">
                    <label class="block text-sm font-semibold mb-1">Phone</label>
                    <input type="text" name="phone" class="w-full p-2 border rounded-md">
                </div>
                <div class="form-group">
                    <label class="block text-sm font-semibold mb-1">Fax</label>
                    <input type="text" name="fax" class="w-full p-2 border rounded-md">
                </div>
                <div class="col-span-2 border-t pt-2 mt-2">
                    <h4 class="font-bold text-sm text-[#214589] mb-3">VA Configuration</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="block text-xs text-gray-500 mb-1">VA Prefix</label>
                            <input type="text" name="bank_va_number" class="w-full p-2 border rounded-md">
                        </div>
                        <div class="form-group">
                            <label class="block text-xs text-gray-500 mb-1">VA Length</label>
                            <input type="number" name="length" class="w-full p-2 border rounded-md">
                        </div>
                        <div class="form-group">
                            <label class="block text-xs text-gray-500 mb-1">Range Start</label>
                            <input type="text" name="start_number" class="w-full p-2 border rounded-md">
                        </div>
                        <div class="form-group">
                            <label class="block text-xs text-gray-500 mb-1">Range End</label>
                            <input type="text" name="end_number" class="w-full p-2 border rounded-md">
                        </div>
                         <div class="form-group">
                            <label class="block text-xs text-gray-500 mb-1">Default VA?</label>
                            <select name="is_default_va" class="w-full p-2 border rounded-md">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-semibold mb-1">Address</label>
                    <textarea name="address" class="w-full p-2 border rounded-md" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="block text-sm font-semibold mb-1">Status</label>
                    <select name="is_active" class="w-full p-2 border rounded-md">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </form>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitForm()">Save Payment</button>
        `;
    }

    function openEditModal(id) {
        openModal('Edit Bank Payment');
        document.getElementById('modalBody').innerHTML = '<div class="text-center py-10"><i class="fas fa-spinner fa-spin text-2xl text-[#214589]"></i></div>';
        
        fetch(`/company/bank-payments/${id}/edit`)
            .then(res => res.json())
            .then(res => {
                const data = res.data;
                openCreateModal(); 
                document.getElementById('modalTitle').textContent = 'Edit Bank Payment';
                
                const form = document.getElementById('bankForm');
                form.bank_id.value = data.bank_id || '';
                form.branch_name.value = data.branch_name || '';
                form.account_name.value = data.account_name || '';
                form.account_number.value = data.account_number || '';
                form.phone.value = data.phone || '';
                form.fax.value = data.fax || '';
                form.bank_va_number.value = data.bank_va_number || '';
                form.length.value = data.length || '';
                form.start_number.value = data.start_number || '';
                form.end_number.value = data.end_number || '';
                form.is_default_va.value = data.is_default_va ? '1' : '0';
                form.address.value = data.address || '';
                form.is_active.value = data.is_active ? '1' : '0';

                document.getElementById('modalFooter').innerHTML = `
                    <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="submitForm(${id})">Update Payment</button>
                `;
            });
    }

    function submitForm(id = null) {
        const form = document.getElementById('bankForm');
        const formData = new FormData(form);
        const url = id ? `/company/bank-payments/${id}` : '/company/bank-payments';
        
        if (id) formData.append('_method', 'PUT');

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' || res.success) {
                window.location.reload();
            } else {
                alert(res.message || 'Something went wrong');
            }
        });
    }

    function deleteSelected() {
        const ids = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
        if (ids.length === 0) return alert('Select items to hide first');

        if (confirm('Are you sure you want to hide selected payments?')) {
            fetch('/company/bank-payments/bulk-delete', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) window.location.reload();
            });
        }
    }
</script>
@endpush
@endsection
