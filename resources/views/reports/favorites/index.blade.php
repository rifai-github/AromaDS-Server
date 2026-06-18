@extends('layouts.app')

@section('title', 'Report Favorites')
@section('breadcrumb', 'Home / Reports / Report Favorites')

@section('content')
<style>
    /* Responsive Table */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 10px 10px;
    }
    
    .responsive-table {
        min-width: 1200px;
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
            min-width: 1000px;
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
        background-color: #214589 !important;
        color: white !important;
        border-color: #214589 !important;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
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
    
    .form-textarea {
        min-height: 100px;
        resize: vertical;
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
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Favorites Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Report Favorites</h1>
            </div>
            
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Add New Favorite</span>
            </button>
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
                
                <button class="btn btn-secondary ml-4" onclick="deleteSelected()">
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
                        <th class="w-[50px]">
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th class="w-[200px]">Report Name</th>
                        <th class="w-[200px]">Type</th>
                        <th class="w-[100px]">Category</th>
                        <th class="w-[100px]">Status</th>
                        <th class="w-[150px]">User</th>
                        <th class="w-[150px]">Created At</th>
                        <th class="w-[150px]">Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($favorites as $favorite)
                    <tr onclick="openViewModal({{ $favorite->id }})" data-id="{{ $favorite->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $favorite->id }}">
                        </td>
                        <td>{{ $favorite->report_name }}</td>
                        <td>{{ $favorite->report_type }}</td>
                        <td>{{ $favorite->category }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $favorite->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $favorite->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $favorite->user->name ?? 'N/A' }}</td>
                        <td>{{ $favorite->created_at->format('d/M/Y H:i') }}</td>
                        <td>{{ $favorite->updated_at->format('d/M/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No favorites data found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($favorites->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $favorites->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Favorite Details</h2>
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

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Silakan pilih minimal satu item untuk dihapus');
        return;
    }
    
    showConfirmDialog({
        title: 'Hapus favorit yang dipilih?',
        text: 'Semua favorit yang dipilih akan dihapus.',
        confirmButtonText: 'Ya, hapus'
    }).then((result) => {
        if (!result.isConfirmed) return;
        const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        fetch('/reports/favorites/bulk-delete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: selectedIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal menghapus favorit: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal menghapus favorit');
        });
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

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Favorit Baru';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Tambahkan laporan baru ke daftar favorit kamu.</p>
        <form id="createForm">
            <div class="form-group">
                <label class="form-label">Nama Report *</label>
                <input type="text" name="report_name" class="form-input" placeholder="Masukkan nama report" required>
            </div>
            <div class="form-group">
                <label class="form-label">Tipe Report *</label>
                <select name="report_type" class="form-input" required>
                    <option value="">Pilih Tipe</option>
                    <option value="financial">Keuangan</option>
                    <option value="operational">Operasional</option>
                    <option value="inventory">Inventory</option>
                    <option value="customer">Customer</option>
                    <option value="hr">HR</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Kategori *</label>
                <select name="category" class="form-input" required>
                    <option value="">Pilih Kategori</option>
                    <option value="summary">Ringkasan</option>
                    <option value="detailed">Detail</option>
                    <option value="analytical">Analitis</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-input form-textarea" placeholder="Masukkan deskripsi"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-input">
                    <option value="1">Aktif</option>
                    <option value="0">Tidak Aktif</option>
                </select>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Simpan Favorit</button>
        </div>
    `;
    openModal();
}

function openViewModal(id) {
    fetch(`/reports/favorites/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Detail Favorit';
            document.getElementById('modalBody').innerHTML = `
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Nama Report</label>
                        <p class="detail-value">${data.report_name || 'N/A'}</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe Report</label>
                        <p class="detail-value">${data.report_type || 'N/A'}</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <p class="detail-value">${data.category || 'N/A'}</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <p class="detail-value">${data.description || 'N/A'}</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <p class="detail-value">${data.is_active ? 'Aktif' : 'Tidak Aktif'}</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">User</label>
                        <p class="detail-value">${data.user ? data.user.name : 'N/A'}</p>
                    </div>
                </div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                </div>
            `;
            openModal();
        })
        .catch(error => {
            console.error('Error loading favorite data:', error);
            alert('Gagal memuat data favorit');
        });
}

function openEditModal(id) {
    fetch(`/reports/favorites/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Ubah Favorit';
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Perbarui informasi favorit.</p>
                <form id="editForm">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="form-group">
                        <label class="form-label">Nama Report *</label>
                        <input type="text" name="report_name" class="form-input" value="${data.report_name || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe Report *</label>
                        <select name="report_type" class="form-input" required>
                            <option value="">Pilih Tipe</option>
                            <option value="financial" ${data.report_type === 'financial' ? 'selected' : ''}>Keuangan</option>
                            <option value="operational" ${data.report_type === 'operational' ? 'selected' : ''}>Operasional</option>
                            <option value="inventory" ${data.report_type === 'inventory' ? 'selected' : ''}>Inventory</option>
                            <option value="customer" ${data.report_type === 'customer' ? 'selected' : ''}>Customer</option>
                            <option value="hr" ${data.report_type === 'hr' ? 'selected' : ''}>HR</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori *</label>
                        <select name="category" class="form-input" required>
                            <option value="">Pilih Kategori</option>
                            <option value="summary" ${data.category === 'summary' ? 'selected' : ''}>Ringkasan</option>
                            <option value="detailed" ${data.category === 'detailed' ? 'selected' : ''}>Detail</option>
                            <option value="analytical" ${data.category === 'analytical' ? 'selected' : ''}>Analitis</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-input form-textarea">${data.description || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-input">
                            <option value="1" ${data.is_active ? 'selected' : ''}>Aktif</option>
                            <option value="0" ${!data.is_active ? 'selected' : ''}>Tidak Aktif</option>
                        </select>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Perbarui Favorit</button>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error loading favorite data:', error);
            alert('Gagal memuat data favorit');
        });
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    fetch('/reports/favorites', {
        method: 'POST',
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
            location.reload();
        } else {
            alert('Gagal membuat favorit: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal membuat favorit');
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    fetch(`/reports/favorites/${id}`, {
        method: 'PUT',
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
            location.reload();
        } else {
            alert('Gagal memperbarui favorit: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal memperbarui favorit');
    });
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
@endsection
