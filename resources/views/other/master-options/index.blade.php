@extends('layouts.app')

@section('title', 'Master Options')
@section('breadcrumb', 'Home / Other / Master Options')

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
    
    /* Delete Confirmation Modal */
    .delete-modal-overlay, .error-modal-overlay, .success-modal-overlay {
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

    .delete-modal-overlay.show, .error-modal-overlay.show, .success-modal-overlay.show {
        display: flex;
    }

    .delete-modal-container, .error-modal-container, .success-modal-container {
        background: white;
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
</style>

<div class="flex flex-col w-full">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Master Options Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4 border-bottom">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Options</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <button class="btn btn-secondary" id="bulkDeleteBtn" onclick="deleteSelected()" style="display: none; color: #dc2626; border-color: #fca5a5; background-color: #fef2f2;">
                    <i class="fas fa-trash"></i>
                    <span>Hapus Terpilih</span>
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span>Add Master Option</span>
                </button>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container w-full" style="border: 1px solid #e5e7eb; border-top: none;">
            <table class="responsive-table" id="optionsTable">
                <thead>
                    <tr style="height: 40px !important;">
                        <th style="width: 50px;" class="text-center">
                            <input type="checkbox" id="headerSelectAll">
                        </th>
                        <th style="width: 60px;" class="text-center">No</th>
                        <th style="width: 200px;">Nama</th>
                        <th>Description</th>
                        <th style="width: 150px;">System Reserved</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 150px;">Created At</th>
                        <th style="width: 180px;">Created By</th>
                    </tr>
                    <tr class="filter-row" style="height: 40px !important;">
                        <th class="text-center">
                            <button onclick="resetFilters()" class="btn btn-secondary btn-sm" style="padding: 0; height: 26px; width: 26px; min-width: 26px; display: flex; align-items: center; justify-content: center; background: white; border: 1px solid #d1d5db;">
                                <i class="fas fa-undo" style="font-size: 10px; color: #6b7280;"></i>
                            </button>
                        </th>
                        <th></th>
                        <th><input type="text" class="table-filter" name="filter[name]" value="{{ request('filter.name') }}" placeholder="Filter..."></th>
                        <th><input type="text" class="table-filter" name="filter[description]" value="{{ request('filter.description') }}" placeholder="Filter..."></th>
                        <th>
                            <select class="table-filter" name="filter[system_reserved]" onchange="applyFilters()">
                                <option value="">All</option>
                                <option value="1" {{ request('filter.system_reserved') === '1' ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ request('filter.system_reserved') === '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </th>
                        <th>
                            <select class="table-filter" name="filter[is_active]" onchange="applyFilters()">
                                <option value="">All</option>
                                <option value="1" {{ request('filter.is_active') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('filter.is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </th>
                        <th><input type="text" class="table-filter" name="filter[created_at]" value="{{ request('filter.created_at') }}" placeholder="YYYY-MM-DD"></th>
                        <th><input type="text" class="table-filter" name="filter[createdBy__name]" value="{{ request('filter.createdBy__name') }}" placeholder="Filter..."></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($masterOptions as $index => $option)
                    <tr onclick="window.location.href='{{ route('other.master-options.show', $option->id) }}'">
                        <td class="text-center" onclick="event.stopPropagation()">
                            <input type="checkbox" class="row-checkbox" value="{{ $option->id }}" onchange="toggleBulkDeleteBtn()">
                        </td>
                        <td class="text-center">{{ $masterOptions->firstItem() + $index }}</td>
                        <td class="font-medium" style="color: #214589;">{{ $option->name }}</td>
                        <td class="text-gray-600">{{ $option->description ?? '-' }}</td>
                        <td>
                            @if($option->system_reserved)
                                <span class="status-badge bg-blue-100 text-blue-700 border border-blue-200 text-xs">System</span>
                            @else
                                <span class="status-badge bg-gray-100 text-gray-700 border border-gray-200 text-xs">User</span>
                            @endif
                        </td>
                        <td>
                            @if($option->is_active)
                                <span class="status-badge bg-green-100 text-green-700 border border-green-200 text-xs">Active</span>
                            @else
                                <span class="status-badge bg-red-100 text-red-700 border border-red-200 text-xs">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $option->created_at ? $option->created_at->format('d/M/Y') : '-' }}</td>
                        <td>{{ $option->createdBy->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-10 text-gray-500">No master options found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        @if($masterOptions->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $masterOptions->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title font-semibold uppercase tracking-wider">Master Option</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content loaded via JS -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Footer content loaded via JS -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
               <i class="fas fa-exclamation-triangle text-red-500 text-6xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Hapus Master Option?</h3>
        <p class="text-gray-600 mb-8" id="deleteMessage">Apakah kamu yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="flex justify-center gap-4">
            <button class="btn btn-secondary px-8" onclick="closeDeleteModal()">Batal</button>
            <button class="btn bg-red-600 text-white px-8 hover:bg-red-700" onclick="confirmDelete()">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <i class="fas fa-check-circle text-green-500 text-6xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Berhasil!</h3>
        <p class="text-gray-600 mb-4" id="successMessage">Data berhasil disimpan.</p>
    </div>
</div>

<script>
// Filter functions
function applyFilters() {
    const filters = document.querySelectorAll('.table-filter');
    const params = new URLSearchParams(window.location.search);
    
    filters.forEach(filter => {
        if (filter.value) {
            params.set(filter.name, filter.value);
        } else {
            params.delete(filter.name);
        }
    });
    
    params.delete('page');
    window.location.href = `?${params.toString()}`;
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

// Event listener for Enter key on filters
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.classList.contains('table-filter')) {
        applyFilters();
    }
});

// Bulk Select toggle
const headerSelectAll = document.getElementById('headerSelectAll');
if (headerSelectAll) {
    headerSelectAll.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
        });
        toggleBulkDeleteBtn();
    });
}

function toggleBulkDeleteBtn() {
    const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
    const btn = document.getElementById('bulkDeleteBtn');
    if (btn) {
        btn.style.display = checkedCount > 0 ? 'inline-flex' : 'none';
        btn.querySelector('span').textContent = `Hapus Terpilih (${checkedCount})`;
    }
}

// Modal functions
function openModal(title) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalOverlay').classList.add('show');
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

function openCreateModal() {
    openModal('Tambah Master Option');
    document.getElementById('modalBody').innerHTML = `
        <form id="masterOptionForm">
            <div class="grid grid-cols-1 gap-4 text-left">
                <div class="form-group mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Option</label>
                    <input type="text" name="name" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Contoh: Bank, Position, etc" required>
                </div>
                <div class="form-group mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi</label>
                    <textarea name="description" class="w-full px-4 py-2 border rounded-lg h-24 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Keterangan option..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                   <div class="form-group mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">System Reserved</label>
                        <select name="system_reserved" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="form-group mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <select name="is_active" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button class="btn btn-primary" onclick="submitCreate()">Simpan Master Option</button>
    `;
}

function submitCreate() {
    const form = document.getElementById('masterOptionForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    fetch('{{ route("other.master-options.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            closeModal();
            showSuccessModal('Data berhasil ditambahkan');
            setTimeout(() => location.reload(), 1500);
        } else {
            alert(res.message || 'Gagal menyimpan data');
        }
    });
}

// Delete functions
let selectedIds = [];
function deleteSelected() {
    selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    document.getElementById('deleteMessage').textContent = `Apakah kamu yakin ingin menghapus ${selectedIds.length} data terpilih?`;
    document.getElementById('deleteModalOverlay').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
}

function confirmDelete() {
    fetch('{{ route("other.master-options.bulk-delete") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ option_ids: selectedIds })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            closeDeleteModal();
            showSuccessModal(res.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            alert(res.message || 'Gagal menghapus data');
        }
    });
}

function showSuccessModal(msg) {
    document.getElementById('successMessage').textContent = msg;
    document.getElementById('successModalOverlay').classList.add('show');
    setTimeout(() => {
        document.getElementById('successModalOverlay').classList.remove('show');
    }, 2000);
}

function closeSuccessModal() {
    document.getElementById('successModalOverlay').classList.remove('show');
}
</script>
@endsection
