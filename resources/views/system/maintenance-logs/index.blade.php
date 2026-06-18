@extends('layouts.app')

@section('title', 'Maintenance Logs')
@section('breadcrumb', 'Home / System / Maintenance Logs')

@section('content')
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
    
    .status-scheduled {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .status-in-progress {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-completed {
        background-color: #dcfce7;
        color: #166534;
    }
    
    .status-failed {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .status-cancelled {
        background-color: #f3f4f6;
        color: #6b7280;
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
        
        <!-- Maintenance Logs Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Maintenance Logs</h1>
            </div>
            
            <div class="flex gap-2">
                <button class="btn btn-secondary" onclick="exportMaintenanceLogs()">
                    <i class="fas fa-download"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span>Add New Log</span>
                </button>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white controls-row">
            <div class="flex flex-row justify-start items-center w-full controls-left">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-700">Type:</label>
                            <select id="typeFilter" class="form-input" style="width: auto; min-width: 150px;" onchange="applyFilters()">
                                <option value="">All Types</option>
                                @foreach($types as $key => $type)
                                    <option value="{{ $key }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-700">Status:</label>
                            <select id="statusFilter" class="form-input" style="width: auto; min-width: 150px;" onchange="applyFilters()">
                                <option value="">All Status</option>
                                @foreach($statuses as $key => $status)
                                    <option value="{{ $key }}">{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-700">Date From:</label>
                            <input type="date" id="dateFromFilter" class="form-input" style="width: auto; min-width: 150px;" onchange="applyFilters()">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-700">Date To:</label>
                            <input type="date" id="dateToFilter" class="form-input" style="width: auto; min-width: 150px;" onchange="applyFilters()">
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Table Container -->
        <div class="w-full bg-white rounded-b-[10px] table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th class="w-[200px]" data-column="type">Type</th>
                        <th class="w-[300px]" data-column="description">Description</th>
                        <th class="w-[150px]" data-column="start_time" data-type="date">Start Time</th>
                        <th class="w-[150px]" data-column="end_time" data-type="date">End Time</th>
                        <th class="w-[100px]" data-column="duration" data-type="numeric">Duration</th>
                        <th class="w-[100px]" data-column="status">Status</th>
                        <th class="w-[150px]" data-column="creator.name">Created By</th>
                        <th class="w-[150px]" data-column="created_at" data-type="date">Created At</th>
                        <th class="w-[150px]" data-column="updated_at" data-type="date">Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($maintenanceLogs as $maintenanceLog)
                    <tr onclick="openViewModal({{ $maintenanceLog->id }})" data-id="{{ $maintenanceLog->id }}">
                        <td>{{ $maintenanceLog->type_text }}</td>
                        <td>{{ substr($maintenanceLog->description, 0, 50) }}{{ strlen($maintenanceLog->description) > 50 ? '...' : '' }}</td>
                        <td>{{ $maintenanceLog->start_time->format('d/M/Y H:i') }}</td>
                        <td>{{ $maintenanceLog->end_time ? $maintenanceLog->end_time->format('d/M/Y H:i') : 'N/A' }}</td>
                        <td>{{ $maintenanceLog->formatted_duration }}</td>
                        <td>
                            <span class="status-badge status-{{ $maintenanceLog->status }}">
                                {{ $maintenanceLog->status_text }}
                            </span>
                        </td>
                        <td>{{ $maintenanceLog->createdBy->name ?? 'N/A' }}</td>
                        <td>{{ $maintenanceLog->created_at ? $maintenanceLog->created_at->format('d/M/Y H:i') : 'N/A' }}</td>
                        <td>{{ $maintenanceLog->updated_at ? $maintenanceLog->updated_at->format('d/M/Y H:i') : 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No maintenance logs found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($maintenanceLogs->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $maintenanceLogs->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Maintenance Log Details</h2>
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
    document.getElementById('modalTitle').textContent = 'Add New Maintenance Log';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Create a new maintenance log entry.</p>
        <form id="createForm">
            <div class="form-group">
                <label class="form-label">Maintenance Type *</label>
                <select name="maintenance_type" class="form-input" required>
                    <option value="">Select Type</option>
                    @foreach($types as $key => $type)
                        <option value="{{ $key }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Description *</label>
                <textarea name="description" class="form-input form-textarea" placeholder="Enter maintenance description" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Start Time *</label>
                <input type="datetime-local" name="start_time" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">End Time</label>
                <input type="datetime-local" name="end_time" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-input" required>
                    @foreach($statuses as $key => $status)
                        <option value="{{ $key }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Create Log</button>
        </div>
    `;
    openModal();
}

function openViewModal(id) {
    // Load data via AJAX
    fetch(`/system/maintenance-logs/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const maintenanceLog = data.data;
                document.getElementById('modalTitle').textContent = 'Maintenance Log Details';
                document.getElementById('modalBody').innerHTML = `
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <p class="text-gray-700">${maintenanceLog.type_text}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-gray-700">${maintenanceLog.description}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Start Time</label>
                            <p class="text-gray-700">${new Date(maintenanceLog.start_time).toLocaleString()}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Time</label>
                            <p class="text-gray-700">${maintenanceLog.end_time ? new Date(maintenanceLog.end_time).toLocaleString() : 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Duration</label>
                            <p class="text-gray-700">${maintenanceLog.formatted_duration}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <span class="status-badge status-${maintenanceLog.status}">
                                ${maintenanceLog.status_text}
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Created By</label>
                            <p class="text-gray-700">${maintenanceLog.created_by ? maintenanceLog.created_by.name : 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Created At</label>
                            <p class="text-gray-700">${new Date(maintenanceLog.created_at).toLocaleString()}</p>
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
            console.error('Error loading maintenance log data:', error);
            showErrorDialog('Gagal', 'Gagal memuat data maintenance log.');
        });
}

function openEditModal(id) {
    // Load data via AJAX
    fetch(`/system/maintenance-logs/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const maintenanceLog = data.data;
                document.getElementById('modalTitle').textContent = 'Edit Maintenance Log';
                document.getElementById('modalBody').innerHTML = `
                    <p class="text-gray-600 mb-6 text-center">Update maintenance log information.</p>
                    <form id="editForm">
                        <input type="hidden" name="id" value="${maintenanceLog.id}">
                        <div class="form-group">
                            <label class="form-label">Maintenance Type *</label>
                            <select name="maintenance_type" class="form-input" required>
                                <option value="">Select Type</option>
                                ${Object.entries(data.types).map(([key, type]) => 
                                    `<option value="${key}" ${key == maintenanceLog.maintenance_type ? 'selected' : ''}>${type}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-input form-textarea" required>${maintenanceLog.description}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Start Time *</label>
                            <input type="datetime-local" name="start_time" class="form-input" value="${maintenanceLog.start_time.slice(0, 16)}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Time</label>
                            <input type="datetime-local" name="end_time" class="form-input" value="${maintenanceLog.end_time ? maintenanceLog.end_time.slice(0, 16) : ''}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-input" required>
                                ${Object.entries(data.statuses).map(([key, status]) => 
                                    `<option value="${key}" ${key == maintenanceLog.status ? 'selected' : ''}>${status}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </form>
                `;
                document.getElementById('modalFooter').innerHTML = `
                    <div class="flex justify-center gap-6">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
                        <button type="button" class="btn btn-primary" onclick="submitEditForm()">Perbarui Log</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading maintenance log data:', error);
            showErrorDialog('Gagal', 'Gagal memuat data maintenance log.');
        });
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    fetch('/system/maintenance-logs', {
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
            showErrorDialog('Gagal', 'Gagal membuat maintenance log: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal membuat maintenance log.');
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    fetch(`/system/maintenance-logs/${id}`, {
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
            showErrorDialog('Gagal', 'Gagal memperbarui maintenance log: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal memperbarui maintenance log.');
    });
}

function applyFilters() {
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    const dateTo = document.getElementById('dateToFilter').value;
    
    const params = window.AromaTableState.paramsWithCurrentSort();
    if (type) params.append('maintenance_type', type);
    if (status) params.append('status', status);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    
    window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
}

function exportMaintenanceLogs() {
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    const dateTo = document.getElementById('dateToFilter').value;
    
    const params = window.AromaTableState.paramsWithCurrentSort();
    if (type) params.append('maintenance_type', type);
    if (status) params.append('status', status);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    
    window.open('/system/maintenance-logs/export?' + params.toString(), '_blank');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
@endsection
