@extends('layouts.app')

@section('title', 'Working Hours')
@section('breadcrumb', 'Home / System / Working Hours')

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
    
    /* Pagination Specific Styles */
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
    
    /* Status Badge */
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-active {
        background-color: #dcfce7;
        color: #166534;
    }
    
    .status-inactive {
        background-color: #fee2e2;
        color: #991b1b;
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
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Working Hours Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Working Hours</h1>
            </div>
            
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Add New Working Hours</span>
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
                        <th class="w-[50px]" data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th class="w-[200px]" data-column="user.name">User</th>
                        <th class="w-[150px]" data-column="day_of_week">Day</th>
                        <th class="w-[150px]" data-column="start_time">Start Time</th>
                        <th class="w-[150px]" data-column="end_time">End Time</th>
                        <th class="w-[100px]" data-column="status">Status</th>
                        <th class="w-[150px]" data-column="created_at" data-type="date">Created At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($workingHours as $workingHour)
                    <tr onclick="openViewModal({{ $workingHour->id }})" data-id="{{ $workingHour->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $workingHour->id }}">
                        </td>
                        <td>{{ $workingHour->user->name ?? 'N/A' }}</td>
                        <td>{{ $workingHour->day_name }}</td>
                        <td>{{ $workingHour->start_time->format('H:i') }}</td>
                        <td>{{ $workingHour->end_time->format('H:i') }}</td>
                        <td>
                            <span class="status-badge {{ $workingHour->is_active ? 'status-active' : 'status-inactive' }}">
                                {{ $workingHour->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $workingHour->created_at->format('d/M/Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No working hours found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($workingHours->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $workingHours->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Detail Jam Kerja</h2>
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
    document.getElementById('modalTitle').textContent = 'Add New Working Hours';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Create working hours for a specific user and day.</p>
        <form id="createForm">
            <div class="form-group">
                <label class="form-label">User *</label>
                <select name="user_id" class="form-input" required>
                    <option value="">Select User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Day of Week *</label>
                <select name="day_of_week" class="form-input" required>
                    <option value="">Select Day</option>
                    @foreach($daysOfWeek as $key => $day)
                        <option value="{{ $key }}">{{ $day }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Start Time *</label>
                <input type="time" name="start_time" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">End Time *</label>
                <input type="time" name="end_time" class="form-input" required>
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
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Tambah Jam Kerja</button>
        </div>
    `;
    openModal();
}

function openViewModal(id) {
    // Load data via AJAX
    fetch(`/system/working-hours/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const workingHour = data.data;
                document.getElementById('modalTitle').textContent = 'Detail Jam Kerja';
                document.getElementById('modalBody').innerHTML = `
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">User</label>
                            <p class="text-gray-700">${workingHour.user ? workingHour.user.name : 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Day of Week</label>
                            <p class="text-gray-700">${workingHour.day_name}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jam Mulai</label>
                            <p class="text-gray-700">${workingHour.start_time}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jam Selesai</label>
                            <p class="text-gray-700">${workingHour.end_time}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <span class="status-badge ${workingHour.is_active ? 'status-active' : 'status-inactive'}">
                                ${workingHour.is_active ? 'Aktif' : 'Tidak Aktif'}
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dibuat Pada</label>
                            <p class="text-gray-700">${new Date(workingHour.created_at).toLocaleDateString()}</p>
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
            }
        })
        .catch(error => {
            console.error('Error loading working hours data:', error);
            showErrorDialog('Gagal', 'Data jam kerja tidak berhasil dimuat.');
        });
}

function openEditModal(id) {
    // Load data via AJAX
    fetch(`/system/working-hours/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const workingHour = data.data;
                document.getElementById('modalTitle').textContent = 'Edit Jam Kerja';
                document.getElementById('modalBody').innerHTML = `
                    <p class="text-gray-600 mb-6 text-center">Perbarui informasi jam kerja.</p>
                    <form id="editForm">
                        <input type="hidden" name="id" value="${workingHour.id}">
                        <div class="form-group">
                            <label class="form-label">User *</label>
                            <select name="user_id" class="form-input" required>
                                <option value="">Pilih User</option>
                                ${data.users.map(user => 
                                    `<option value="${user.id}" ${user.id == workingHour.user_id ? 'selected' : ''}>${user.name}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Day of Week *</label>
                            <select name="day_of_week" class="form-input" required>
                                <option value="">Pilih Hari</option>
                                ${Object.entries(data.daysOfWeek).map(([key, day]) => 
                                    `<option value="${key}" ${key == workingHour.day_of_week ? 'selected' : ''}>${day}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Start Time *</label>
                            <input type="time" name="start_time" class="form-input" value="${workingHour.start_time}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Time *</label>
                            <input type="time" name="end_time" class="form-input" value="${workingHour.end_time}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-input">
                                    <option value="1" ${workingHour.is_active ? 'selected' : ''}>Aktif</option>
                                    <option value="0" ${!workingHour.is_active ? 'selected' : ''}>Nonaktif</option>
                            </select>
                        </div>
                    </form>
                `;
                document.getElementById('modalFooter').innerHTML = `
                    <div class="flex justify-center gap-6">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
                        <button type="button" class="btn btn-primary" onclick="submitEditForm()">Perbarui Jam Kerja</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading working hours data:', error);
            showErrorDialog('Gagal', 'Data jam kerja tidak berhasil dimuat.');
        });
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    fetch('/system/working-hours', {
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
            showErrorDialog('Gagal', 'Jam kerja tidak berhasil dibuat: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Jam kerja tidak berhasil dibuat.');
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    fetch(`/system/working-hours/${id}`, {
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
            showErrorDialog('Gagal', 'Jam kerja tidak berhasil diperbarui: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Jam kerja tidak berhasil diperbarui.');
    });
}

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu data jam kerja yang ingin dihapus.');
        return;
    }
    
    showConfirmDialog(
        'Hapus Jam Kerja?',
        `Apakah Anda yakin ingin menghapus ${checkboxes.length} data jam kerja?`,
        'Ya, hapus',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;

        const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        fetch('/system/working-hours/bulk-delete', {
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
                showErrorDialog('Gagal', 'Jam kerja tidak berhasil dihapus: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Jam kerja tidak berhasil dihapus.');
        });
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
