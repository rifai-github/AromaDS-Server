@extends('layouts.app')

@section('title', 'Master Bank')
@section('breadcrumb', 'Home / Master Data / Master Bank')

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
        min-width: 1200px;
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
        word-break: break-word;
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
        width: 500px;
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
                <h1 class="text-xl font-semibold text-[#214589]">Master Bank</h1>
            </div>
            <div class="flex flex-row justify-end items-center gap-2">
                <button class="btn btn-secondary" id="bulkDeleteBtn" onclick="deleteSelected()" style="display: none; color: #dc2626; border-color: #fca5a5; background-color: #fef2f2;">
                    <i class="fas fa-trash"></i>
                    <span>Hapus Terpilih</span>
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span>Add Bank</span>
                </button>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container w-full" style="border: 1px solid #e5e7eb; border-top: none;">
            <table class="responsive-table" id="banksTable">
                <thead>
                    <tr style="height: 40px !important;">
                        <th style="width: 50px;" class="text-center">
                            <input type="checkbox" id="headerSelectAll">
                        </th>
                        <th style="width: 60px;" class="text-center">No</th>
                        <th style="width: 150px;">Bank Code</th>
                        <th>Bank Name</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 200px;">Created By</th>
                        <th style="width: 150px;">Created At</th>
                    </tr>
                    <tr class="filter-row" style="height: 40px !important;">
                        <th class="text-center">
                            <button onclick="resetFilters()" class="btn btn-secondary btn-sm" style="padding: 0; height: 26px; width: 26px; min-width: 26px; display: flex; align-items: center; justify-content: center; background: white; border: 1px solid #d1d5db;">
                                <i class="fas fa-undo" style="font-size: 10px; color: #6b7280;"></i>
                            </button>
                        </th>
                        <th></th>
                        <th><input type="text" class="table-filter" name="filter[bank_code]" value="{{ request('filter.bank_code') }}" placeholder="Filter..."></th>
                        <th><input type="text" class="table-filter" name="filter[bank_name]" value="{{ request('filter.bank_name') }}" placeholder="Filter..."></th>
                        <th>
                            <select class="table-filter" name="filter[is_active]" onchange="applyFilters()">
                                <option value="">All</option>
                                <option value="1" {{ request('filter.is_active') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('filter.is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </th>
                        <th><input type="text" class="table-filter" name="filter[createdBy__name]" value="{{ request('filter.createdBy__name') }}" placeholder="Filter..."></th>
                        <th><input type="text" class="table-filter" name="filter[created_at]" value="{{ request('filter.created_at') }}" placeholder="YYYY-MM-DD"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banks as $index => $bank)
                    <tr onclick="openViewModal({{ $bank->id }})">
                        <td class="text-center" onclick="event.stopPropagation()">
                            <input type="checkbox" class="bank-checkbox" value="{{ $bank->id }}" onchange="toggleBulkDeleteBtn()">
                        </td>
                        <td class="text-center">{{ $banks->firstItem() + $index }}</td>
                        <td>{{ $bank->bank_code }}</td>
                        <td class="font-medium" style="color: #214589;">{{ $bank->bank_name }}</td>
                        <td>
                            @if($bank->is_active)
                                <span class="status-badge bg-green-100 text-green-700 border border-green-200 text-xs">Active</span>
                            @else
                                <span class="status-badge bg-red-100 text-red-700 border border-red-200 text-xs">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $bank->createdBy->name ?? '-' }}</td>
                        <td>{{ $bank->created_at ? $bank->created_at->format('d/M/Y') : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-10 text-gray-500">No banks found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        @if($banks->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $banks->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Modals from Pipeline Style --}}
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Bank Details</h2>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div id="modalBody" class="modal-body"></div>
        <div id="modalFooter" class="modal-footer"></div>
    </div>
</div>

@push('scripts')
<script>
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

    // Select All logic
    $('#headerSelectAll').on('change', function() {
        $('.bank-checkbox').prop('checked', this.checked);
        toggleBulkDeleteBtn();
    });
});

function toggleBulkDeleteBtn() {
    const checkedCount = $('.bank-checkbox:checked').length;
    if (checkedCount > 0) {
        $('#bulkDeleteBtn').show();
    } else {
        $('#bulkDeleteBtn').hide();
        $('#headerSelectAll').prop('checked', false);
    }
}

function openModal(title) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalOverlay').classList.add('show');
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

function openCreateModal() {
    openModal('Tambah Bank');
    document.getElementById('modalBody').innerHTML = `
        <form id="bankForm" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Bank Code *</label>
                <input type="text" name="bank_code" class="w-full p-2 border rounded-md" placeholder="e.g. BCA">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Bank Name *</label>
                <input type="text" name="bank_name" class="w-full p-2 border rounded-md" placeholder="e.g. PT. Bank Central Asia">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Status</label>
                <select name="is_active" class="w-full p-2 border rounded-md">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button class="btn btn-primary" onclick="submitBank()">Simpan Bank</button>
    `;
}

function openViewModal(id) {
    openModal('Detail Bank');
    document.getElementById('modalBody').innerHTML = '<div class="text-center py-10"><i class="fas fa-spinner fa-spin text-2xl text-[#214589]"></i></div>';
    
    fetch(`/master/banks/${id}`)
        .then(r => r.json())
        .then(res => {
            const b = res.data;
            document.getElementById('modalBody').innerHTML = `
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase">Bank Code</label>
                            <p class="font-bold text-[#214589] border-b pb-1 text-lg">${b.bank_code}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase">Status</label>
                            <p class="mt-1">
                                <span class="status-badge ${b.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                    ${b.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase">Bank Name</label>
                        <p class="font-bold text-[#214589] border-b pb-1 text-lg">${b.bank_name}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 bg-gray-50 p-3 rounded-lg">
                        <div>
                            <label class="block text-xs text-gray-500">Created By</label>
                            <p class="text-sm font-medium">${b.created_by?.name || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Created At</label>
                            <p class="text-sm font-medium">${b.created_at || '-'}</p>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <button class="btn btn-secondary" onclick="closeModal()">Tutup</button>
                <button class="btn btn-secondary text-danger" onclick="initiateDelete(${id})">Hapus</button>
                <button class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
            `;
        });
}

function openEditModal(id) {
    fetch(`/master/banks/${id}/edit`)
        .then(r => r.json())
        .then(res => {
            const b = res.data;
            openCreateModal();
            document.getElementById('modalTitle').textContent = 'Edit Bank';
            const form = document.getElementById('bankForm');
            form.bank_code.value = b.bank_code;
            form.bank_name.value = b.bank_name;
            form.is_active.value = b.is_active ? '1' : '0';
            
            document.getElementById('modalFooter').innerHTML = `
                <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button class="btn btn-primary" onclick="submitBank(${id})">Update Bank</button>
            `;
        });
}

function submitBank(id = null) {
    const form = document.getElementById('bankForm');
    const formData = new FormData(form);
    const url = id ? `/master/banks/${id}` : '/master/banks';
    if (id) formData.append('_method', 'PUT');

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') window.location.reload();
        else alert(res.message || 'Error occurred');
    });
}

function initiateDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus bank ini?')) {
        fetch(`/master/banks/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') window.location.reload();
            else alert(res.message);
        });
    }
}

function deleteSelected() {
    const ids = Array.from(document.querySelectorAll('.bank-checkbox:checked')).map(cb => cb.value);
    if (ids.length === 0) return alert('Pilih bank yang ingin dihapus terlebih dahulu');

    if (confirm(`Apakah Anda yakin ingin menghapus ${ids.length} bank yang terpilih?`)) {
        fetch('/master/banks/bulk-delete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') window.location.reload();
            else alert(res.message);
        });
    }
}
</script>
@endpush
@endsection
