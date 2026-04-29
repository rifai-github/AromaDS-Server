@extends('layouts.app')

@section('title', 'Backup Logs')
@section('breadcrumb', 'Home / System / Backup Logs')

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
    
    .status-pending {
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
        
        <!-- Backup Logs Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Backup Logs</h1>
            </div>
            
            <div class="flex gap-2">
                <button class="btn btn-secondary" onclick="exportBackupLogs()">
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
                        <th class="w-[200px]" data-column="backup_type">Type</th>
                        <th class="w-[300px]" data-column="file_path">File Path</th>
                        <th class="w-[150px]" data-column="file_size" data-type="numeric">File Size</th>
                        <th class="w-[100px]" data-column="status">Status</th>
                        <th class="w-[200px]" data-column="created_at" data-type="date">Created At</th>
                        <th class="w-[200px]" data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($backupLogs as $backupLog)
                    <tr onclick="openViewModal({{ $backupLog->id }})" data-id="{{ $backupLog->id }}">
                        <td>{{ $backupLog->type_text }}</td>
                        <td>{{ $backupLog->file_path }}</td>
                        <td>{{ $backupLog->formatted_file_size }}</td>
                        <td>
                            <span class="status-badge status-{{ $backupLog->status }}">
                                {{ $backupLog->status_text }}
                            </span>
                        </td>
                        <td>{{ $backupLog->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $backupLog->updated_at ? $backupLog->updated_at->format('d M Y H:i') : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No backup logs found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if(isset($backupLogs) && $backupLogs->currentPage() > 1)
                    <a href="{{ $backupLogs->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if(isset($backupLogs) && $backupLogs->lastPage() > 0)
                    @php
                        $start = max(1, $backupLogs->currentPage() - 2);
                        $end = min($backupLogs->lastPage(), $backupLogs->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $backupLogs->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $backupLogs->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $backupLogs->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $backupLogs->lastPage())
                            @if($end < $backupLogs->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $backupLogs->url($backupLogs->lastPage()) }}" class="page-number">{{ $backupLogs->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if(isset($backupLogs) && $backupLogs->currentPage() < $backupLogs->lastPage())
                    <a href="{{ $backupLogs->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $backupLogs->currentPage() ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $backupLogs->lastPage() ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Backup Log Details</h2>
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
    fetch(`/system/backup-logs/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const backupLog = data.data;
                document.getElementById('modalTitle').textContent = 'Backup Log Details';
                document.getElementById('modalBody').innerHTML = `
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <p class="text-gray-700">${backupLog.type_text}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">File Path</label>
                            <p class="text-gray-700 font-mono text-sm">${backupLog.file_path}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">File Size</label>
                            <p class="text-gray-700">${backupLog.formatted_file_size}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <span class="status-badge status-${backupLog.status}">
                                ${backupLog.status_text}
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Created At</label>
                            <p class="text-gray-700">${new Date(backupLog.created_at).toLocaleString()}</p>
                        </div>
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
            console.error('Error loading backup log data:', error);
            alert('Gagal memuat data backup log');
        });
}

function applyFilters() {
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    const dateTo = document.getElementById('dateToFilter').value;
    
    const params = new URLSearchParams();
    if (type) params.append('backup_type', type);
    if (status) params.append('status', status);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    
    window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
}

function exportBackupLogs() {
    const type = document.getElementById('typeFilter').value;
    const status = document.getElementById('statusFilter').value;
    const dateFrom = document.getElementById('dateFromFilter').value;
    const dateTo = document.getElementById('dateToFilter').value;
    
    const params = new URLSearchParams();
    if (type) params.append('backup_type', type);
    if (status) params.append('status', status);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    
    window.open('/system/backup-logs/export?' + params.toString(), '_blank');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
@endsection
