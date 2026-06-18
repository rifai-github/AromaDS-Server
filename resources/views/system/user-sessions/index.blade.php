@extends('layouts.app')

@section('title', 'User Sessions')
@section('breadcrumb', 'Home / System / User Sessions')

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
    
    .status-expired {
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
        
        <!-- User Sessions Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">User Sessions</h1>
            </div>
            
            <div class="flex gap-2">
                <button class="btn btn-secondary" onclick="exportUserSessions()">
                    <i class="fas fa-download"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-danger" onclick="cleanupExpiredSessions()">
                    <i class="fas fa-trash"></i>
                    <span>Cleanup Expired</span>
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
                
                <button class="btn btn-secondary ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Terminate</span>
                </button>
                
                <div class="flex flex-row items-center gap-4 ml-4">
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-700">User:</label>
                        <select id="userFilter" class="form-input" style="width: auto; min-width: 150px;" onchange="applyFilters()">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-700">Status:</label>
                        <select id="statusFilter" class="form-input" style="width: auto; min-width: 150px;" onchange="applyFilters()">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                        </select>
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
                        <th class="w-[50px]" data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th class="w-[200px]" data-column="user.name">User</th>
                        <th class="w-[200px]" data-column="session_id">Session ID</th>
                        <th class="w-[150px]" data-column="ip_address">IP Address</th>
                        <th class="w-[200px]" data-column="user_agent">User Agent</th>
                        <th class="w-[150px]" data-column="last_activity" data-type="date">Last Activity</th>
                        <th class="w-[100px]" data-column="duration" data-type="numeric">Duration</th>
                        <th class="w-[100px]" data-column="status">Status</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($userSessions as $userSession)
                    <tr onclick="openViewModal({{ $userSession->id }})" data-id="{{ $userSession->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $userSession->id }}">
                        </td>
                        <td>{{ $userSession->user->name ?? 'N/A' }}</td>
                        <td>{{ substr($userSession->session_id, 0, 20) }}...</td>
                        <td>{{ $userSession->ip_address }}</td>
                        <td>{{ substr($userSession->user_agent, 0, 30) }}...</td>
                        <td>{{ $userSession->last_activity_formatted }}</td>
                        <td>{{ $userSession->formatted_duration }}</td>
                        <td>
                            <span class="status-badge status-{{ $userSession->status }}">
                                {{ $userSession->status_text }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No user sessions found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($userSessions->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $userSessions->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Session Details</h2>
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

function openViewModal(id) {
    // Load data via AJAX
    fetch(`/system/user-sessions/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const userSession = data.data;
                document.getElementById('modalTitle').textContent = 'Session Details';
                document.getElementById('modalBody').innerHTML = `
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">User</label>
                            <p class="text-gray-700">${userSession.user ? userSession.user.name : 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Session ID</label>
                            <p class="text-gray-700 font-mono text-sm">${userSession.session_id}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">IP Address</label>
                            <p class="text-gray-700">${userSession.ip_address}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">User Agent</label>
                            <p class="text-gray-700 text-sm">${userSession.user_agent}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Activity</label>
                            <p class="text-gray-700">${userSession.last_activity_formatted}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Duration</label>
                            <p class="text-gray-700">${userSession.formatted_duration}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <span class="status-badge status-${userSession.status}">
                                ${userSession.status_text}
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Created At</label>
                            <p class="text-gray-700">${new Date(userSession.created_at).toLocaleString()}</p>
                        </div>
                    </div>
                `;
                document.getElementById('modalFooter').innerHTML = `
                    <div class="flex justify-center gap-6">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Tutup</button>
                        <button type="button" class="btn btn-danger" onclick="terminateSession(${id})">Akhiri Sesi</button>
                    </div>
                `;
                openModal();
            }
        })
        .catch(error => {
            console.error('Error loading session data:', error);
            alert('Gagal memuat data sesi');
        });
}

function terminateSession(id) {
    showConfirmDialog({
        title: 'Akhiri sesi ini?',
        text: 'Sesi yang dipilih akan langsung diakhiri.',
        confirmButtonText: 'Ya, akhiri'
    }).then((result) => {
        if (!result.isConfirmed) return;
        fetch(`/system/user-sessions/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal();
                location.reload();
            } else {
                alert('Gagal mengakhiri sesi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal mengakhiri sesi');
        });
    });
}

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Silakan pilih minimal satu sesi untuk diakhiri');
        return;
    }
    
    showConfirmDialog({
        title: `Akhiri ${checkboxes.length} sesi?`,
        text: 'Semua sesi yang dipilih akan langsung diakhiri.',
        confirmButtonText: 'Ya, akhiri'
    }).then((result) => {
        if (!result.isConfirmed) return;
        const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        fetch('/system/user-sessions/bulk-delete', {
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
                alert('Gagal mengakhiri sesi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal mengakhiri sesi');
        });
    });
}

function cleanupExpiredSessions() {
    showConfirmDialog({
        title: 'Bersihkan semua sesi kedaluwarsa?',
        text: 'Semua sesi yang sudah kedaluwarsa akan dibersihkan.',
        confirmButtonText: 'Ya, bersihkan'
    }).then((result) => {
        if (!result.isConfirmed) return;
        fetch('/system/user-sessions/cleanup', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Berhasil membersihkan ${data.count} sesi yang sudah kedaluwarsa`);
                location.reload();
            } else {
                alert('Gagal membersihkan sesi kedaluwarsa: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal membersihkan sesi kedaluwarsa');
        });
    });
}

function applyFilters() {
    const userId = document.getElementById('userFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = window.AromaTableState.paramsWithCurrentSort();
    if (userId) params.append('user_id', userId);
    if (status) params.append('status', status);
    
    window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
}

function exportUserSessions() {
    const userId = document.getElementById('userFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = window.AromaTableState.paramsWithCurrentSort();
    if (userId) params.append('user_id', userId);
    if (status) params.append('status', status);
    
    window.open('/system/user-sessions/export?' + params.toString(), '_blank');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
@endsection
