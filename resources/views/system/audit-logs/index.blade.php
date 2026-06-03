@extends('layouts.app')

@section('title', 'Audit Logs')
@section('breadcrumb', 'Home / System / Audit Logs')

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
    
    /* Action Badge */
    .action-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .action-created {
        background-color: #dcfce7;
        color: #166534;
    }
    
    .action-updated {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .action-deleted {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .action-login {
        background-color: #f0f9ff;
        color: #0369a1;
    }
    
    .action-logout {
        background-color: #fef3c7;
        color: #92400e;
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
        
        <!-- Audit Logs Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Audit Logs</h1>
            </div>
            
            <div class="flex gap-2">
                <button class="btn btn-secondary" onclick="exportAuditLogs()">
                    <i class="fas fa-download"></i>
                    <span>Export</span>
                </button>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white controls-row">
            <div class="flex flex-row justify-start items-center w-full controls-left">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center gap-4">
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
                            <label class="text-sm text-gray-700">Action:</label>
                            <select id="actionFilter" class="form-input" style="width: auto; min-width: 150px;" onchange="applyFilters()">
                                <option value="">All Actions</option>
                                @foreach($actions as $action)
                                    <option value="{{ $action }}">{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-700">Model:</label>
                            <select id="modelFilter" class="form-input" style="width: auto; min-width: 150px;" onchange="applyFilters()">
                                <option value="">All Models</option>
                                @foreach($modelTypes as $modelType)
                                    <option value="{{ $modelType }}">{{ class_basename($modelType) }}</option>
                                @endforeach
                            </select>
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
                        <th class="w-[150px]" data-column="created_at" data-type="date">Date & Time</th>
                        <th class="w-[150px]" data-column="user__name">User</th>
                        <th class="w-[120px]" data-column="action">Action</th>
                        <th class="w-[150px]" data-column="model_type">Model</th>
                        <th class="w-[100px]" data-column="model_id">Model ID</th>
                        <th class="w-[150px]" data-column="ip_address">IP Address</th>
                        <th class="w-[200px]" data-no-filter>Changes</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($auditLogs as $auditLog)
                    <tr onclick="openViewModal({{ $auditLog->id }})" data-id="{{ $auditLog->id }}">
                        <td>{{ $auditLog->created_at->format('d/M/Y H:i') }}</td>
                        <td>{{ $auditLog->user->name ?? 'N/A' }}</td>
                        <td>
                            <span class="action-badge action-{{ $auditLog->action }}">
                                {{ ucfirst(str_replace('_', ' ', $auditLog->action)) }}
                            </span>
                        </td>
                        <td>{{ $auditLog->model_name }}</td>
                        <td>{{ $auditLog->model_id ?? 'N/A' }}</td>
                        <td>{{ $auditLog->ip_address }}</td>
                        <td>
                            @if($auditLog->hasChanges())
                                <span class="text-sm text-blue-600">{{ count($auditLog->changes) }} change(s)</span>
                            @else
                                <span class="text-sm text-gray-500">No changes</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No audit logs found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if(isset($auditLogs) && $auditLogs->currentPage() > 1)
                    <a href="{{ $auditLogs->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if(isset($auditLogs) && $auditLogs->lastPage() > 0)
                    @php
                        $start = max(1, $auditLogs->currentPage() - 2);
                        $end = min($auditLogs->lastPage(), $auditLogs->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $auditLogs->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $auditLogs->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $auditLogs->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $auditLogs->lastPage())
                            @if($end < $auditLogs->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $auditLogs->url($auditLogs->lastPage()) }}" class="page-number">{{ $auditLogs->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if(isset($auditLogs) && $auditLogs->currentPage() < $auditLogs->lastPage())
                    <a href="{{ $auditLogs->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $auditLogs->currentPage() ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $auditLogs->lastPage() ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Audit Log Details</h2>
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

function openViewModal(id) {
    // Load data via AJAX
    fetch(`/system/audit-logs/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const auditLog = data.data;
                document.getElementById('modalTitle').textContent = 'Audit Log Details';
                document.getElementById('modalBody').innerHTML = `
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">Date & Time</label>
                            <p class="text-gray-700">${new Date(auditLog.created_at).toLocaleString()}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">User</label>
                            <p class="text-gray-700">${auditLog.user ? auditLog.user.name : 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Action</label>
                            <span class="action-badge action-${auditLog.action}">
                                ${auditLog.action_description}
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Model</label>
                            <p class="text-gray-700">${auditLog.model_name}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Model ID</label>
                            <p class="text-gray-700">${auditLog.model_id || 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">IP Address</label>
                            <p class="text-gray-700">${auditLog.ip_address}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">User Agent</label>
                            <p class="text-gray-700 text-sm">${auditLog.user_agent || 'N/A'}</p>
                        </div>
                        ${auditLog.changes && Object.keys(auditLog.changes).length > 0 ? `
                        <div class="form-group">
                            <label class="form-label">Changes</label>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                ${Object.entries(auditLog.changes).map(([field, change]) => `
                                    <div class="mb-2">
                                        <strong>${field}:</strong><br>
                                        <span class="text-red-600">${change.old || 'N/A'}</span> → 
                                        <span class="text-green-600">${change.new || 'N/A'}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;
                document.getElementById('modalFooter').innerHTML = `
                    <div class="flex justify-center gap-6">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Tutup</button>
                    </div>
                `;
                openModal();
            }
        })
        .catch(error => {
            console.error('Error loading audit log data:', error);
            alert('Gagal memuat data audit log');
        });
}

function applyFilters() {
    const userId = document.getElementById('userFilter').value;
    const action = document.getElementById('actionFilter').value;
    const modelType = document.getElementById('modelFilter').value;
    
    const params = new URLSearchParams();
    if (userId) params.append('user_id', userId);
    if (action) params.append('action', action);
    if (modelType) params.append('model_type', modelType);
    
    window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
}

function exportAuditLogs() {
    const userId = document.getElementById('userFilter').value;
    const action = document.getElementById('actionFilter').value;
    const modelType = document.getElementById('modelFilter').value;
    
    const params = new URLSearchParams();
    if (userId) params.append('user_id', userId);
    if (action) params.append('action', action);
    if (modelType) params.append('model_type', modelType);
    
    window.open('/system/audit-logs/export?' + params.toString(), '_blank');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
@endsection
