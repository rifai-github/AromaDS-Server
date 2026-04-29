@extends('layouts.app')

@section('title', 'Virtual Account Exports')
@section('breadcrumb', 'Home / Finance / Virtual Account Exports')

@section('content')
@include('finance.shared.responsive-table-styles')

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Virtual Account Exports</h1>
            </div>
            <div class="flex flex-row gap-2">
                <button class="btn btn-secondary btn-sm" onclick="exportData()">
                    <i class="fas fa-download"></i>
                    <span>Export CSV</span>
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span>Create Export</span>
                </button>
            </div>
        </div>
        
        <!-- Search and Filter Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white border-b border-gray-200">
            <div class="flex flex-row justify-start items-center w-full gap-4">
                <!-- Search Input -->
                <div class="flex flex-row items-center gap-2">
                    <i class="fas fa-search text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="Search by export number, file name, or notes..." 
                           class="form-input" style="min-width: 300px;" 
                           value="{{ request('search') }}" onkeyup="handleSearch(event)">
                </div>
                
                <!-- Bank Filter -->
                <select id="bankFilter" class="form-input" onchange="applyFilters()">
                    <option value="">All Banks</option>
                    @foreach($banks as $bank)
                        <option value="{{ $bank->id }}" {{ request('bank_id') == $bank->id ? 'selected' : '' }}>
                            {{ $bank->name }}
                        </option>
                    @endforeach
                </select>
                
                <!-- Status Filter -->
                <select id="statusFilter" class="form-input" onchange="applyFilters()">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
                
                <!-- File Type Filter -->
                <select id="fileTypeFilter" class="form-input" onchange="applyFilters()">
                    <option value="">All File Types</option>
                    <option value="csv" {{ request('file_type') == 'csv' ? 'selected' : '' }}>CSV</option>
                    <option value="xlsx" {{ request('file_type') == 'xlsx' ? 'selected' : '' }}>Excel</option>
                    <option value="txt" {{ request('file_type') == 'txt' ? 'selected' : '' }}>Text</option>
                </select>
                
                <!-- Clear Filters -->
                <button class="btn btn-secondary btn-sm" onclick="clearFilters()">
                    <i class="fas fa-times"></i>
                    <span>Clear</span>
                </button>
            </div>
        </div>
        
        <!-- Table Container -->
        <div class="w-full bg-white rounded-b-[10px] table-container">
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th class="w-[50px]" data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th class="w-[150px]" data-column="export_number">Export Number</th>
                        <th class="w-[120px]" data-column="bank__name">Bank</th>
                        <th class="w-[150px]" data-column="file_name">File Name</th>
                        <th class="w-[100px]" data-column="file_type">File Type</th>
                        <th class="w-[120px]" data-column="total_records" data-type="numeric">Total Records</th>
                        <th class="w-[100px]" data-column="status">Status</th>
                        <th class="w-[120px]" data-no-filter>Date Range</th>
                        <th class="w-[120px]" data-column="creator__name">Created By</th>
                        <th class="w-[150px]" data-column="created_at" data-type="date">Created At</th>
                        <th class="w-[100px]" data-no-filter>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exports as $export)
                    <tr onclick="openViewModal({{ $export->id }})" data-id="{{ $export->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $export->id }}">
                        </td>
                        <td class="font-mono">{{ $export->export_number ?? 'N/A' }}</td>
                        <td>{{ $export->bank ? $export->bank->name : '-' }}</td>
                        <td>{{ $export->file_name ?? 'N/A' }}</td>
                        <td>{{ $export->file_type_label }}</td>
                        <td class="font-semibold">{{ $export->total_records ?? 0 }}</td>
                        <td>{!! $export->status_badge !!}</td>
                        <td>
                            @if($export->date_from && $export->date_to)
                                {{ $export->formatted_date_from }} - {{ $export->formatted_date_to }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $export->createdBy ? $export->createdBy->name : '-' }}</td>
                        <td>{{ $export->formatted_created_at }}</td>
                        <td class="text-center">
                            <div class="flex flex-row gap-1 justify-center">
                                @if($export->canBeDownloaded())
                                    <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); downloadFile({{ $export->id }})" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                @endif
                                @if($export->canBeProcessed())
                                    <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); processExport({{ $export->id }})" title="Process">
                                        <i class="fas fa-play"></i>
                                    </button>
                                @endif
                                @if($export->canBeDeleted())
                                    <button class="btn btn-sm btn-outline" onclick="event.stopPropagation(); openEditModal({{ $export->id }})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deleteExport({{ $export->id }})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No exports found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="w-full bg-white p-4 border-t border-gray-200">
            {{ $exports->links() }}
        </div>
    </div>
</div>

@include('finance.shared.modal-overlay')

<script>
@include('finance.shared.table-scripts')

// Search and filter functions
function handleSearch(event) {
    if (event.key === 'Enter') {
        applyFilters();
    }
}

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const bankId = document.getElementById('bankFilter').value;
    const status = document.getElementById('statusFilter').value;
    const fileType = document.getElementById('fileTypeFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (bankId) params.append('bank_id', bankId);
    if (status) params.append('status', status);
    if (fileType) params.append('file_type', fileType);
    
    window.location.href = '{{ route("finance.virtual-account-exports.index") }}?' + params.toString();
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('bankFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('fileTypeFilter').value = '';
    window.location.href = '{{ route("finance.virtual-account-exports.index") }}';
}

// Export data function
function exportData() {
    const params = new URLSearchParams(window.location.search);
    window.open('{{ route("finance.virtual-account-exports.export") }}?' + params.toString(), '_blank');
}

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu export yang ingin dihapus.');
        return;
    }
    
    showConfirmDialog(
        'Hapus Export Terpilih?',
        'Apakah Anda yakin ingin menghapus export yang dipilih?',
        'Ya, hapus',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;
        const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        fetch('/finance/virtual-account-exports/bulk-delete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: selectedIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                showErrorDialog('Gagal', 'Export tidak berhasil dihapus: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Export tidak berhasil dihapus.');
        });
    });
}

// Individual action functions
function downloadFile(id) {
    window.open(`/finance/virtual-account-exports/${id}/download`, '_blank');
}

function processExport(id) {
    showConfirmDialog(
        'Proses Export?',
        'Apakah Anda yakin ingin memproses export ini?',
        'Ya, proses',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;
        fetch(`/finance/virtual-account-exports/${id}/process`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                showErrorDialog('Gagal', 'Export tidak berhasil diproses: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Export tidak berhasil diproses.');
        });
    });
}

function deleteExport(id) {
    showConfirmDialog(
        'Hapus Export?',
        'Apakah Anda yakin ingin menghapus export ini?',
        'Ya, hapus',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;
        fetch(`/finance/virtual-account-exports/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                showErrorDialog('Gagal', 'Export tidak berhasil dihapus: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Export tidak berhasil dihapus.');
        });
    });
}

// Modal functions
function openCreateModal() {
    openModal('Create New Virtual Account Export');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Basic Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Export Number</label>
                        <input type="text" name="export_number" class="form-input" placeholder="Auto-generated if empty">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Export Date *</label>
                        <input type="date" name="export_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bank *</label>
                        <select name="bank_id" class="form-input" required>
                            <option value="">Select Bank</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">File Type *</label>
                        <select name="file_type" class="form-input" required>
                            <option value="">Select File Type</option>
                            <option value="csv">CSV</option>
                            <option value="xlsx">Excel</option>
                            <option value="txt">Text</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Date Range Filter</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Filter</label>
                        <select name="status_filter" class="form-input">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Limit Records</label>
                        <input type="number" name="limit_records" class="form-input" min="1" max="10000" placeholder="No limit">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Export Settings</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Include Header</label>
                        <select name="include_header" class="form-input">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CSV Delimiter</label>
                        <select name="delimiter" class="form-input">
                            <option value=",">Comma (,)</option>
                            <option value=";">Semicolon (;)</option>
                            <option value="|">Pipe (|)</option>
                            <option value="\t">Tab</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Auto Process</label>
                        <select name="auto_process" class="form-input">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Include Columns *</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="include_columns[]" value="va_number" class="mr-2">
                            VA Number
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="include_columns[]" value="customer_name" class="mr-2">
                            Customer Name
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="include_columns[]" value="amount" class="mr-2">
                            Amount
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="include_columns[]" value="due_date" class="mr-2">
                            Due Date
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="include_columns[]" value="status" class="mr-2">
                            Status
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="include_columns[]" value="created_at" class="mr-2">
                            Created At
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="include_columns[]" value="updated_at" class="mr-2">
                            Updated At
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="include_columns[]" value="notes" class="mr-2">
                            Notes
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Additional Information</div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input form-textarea" placeholder="Additional notes about this export"></textarea>
                </div>
            </div>
        </form>
    `;
    
    // Add modal footer
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" form="form" class="btn btn-primary">Create Export</button>
    `;
}

function openViewModal(id) {
    fetch(`/finance/virtual-account-exports/${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
            openModal('Export Details');
            
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Export Number</label>
                            <p class="detail-value font-mono">${data.data.export_number || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Export Date</label>
                            <p class="detail-value">${data.data.formatted_export_date || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Bank</label>
                            <p class="detail-value">${data.data.bank ? data.data.bank.name : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">File Name</label>
                            <p class="detail-value">${data.data.file_name || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">File Type</label>
                            <p class="detail-value">${data.data.file_type_label || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">File Size</label>
                            <p class="detail-value">${data.data.formatted_file_size || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Total Records</label>
                            <p class="detail-value font-semibold">${data.data.total_records || 0}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value">${data.data.status_label || 'N/A'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Filter Settings</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Date Range</label>
                            <p class="detail-value">${data.data.formatted_date_from || 'N/A'} - ${data.data.formatted_date_to || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status Filter</label>
                            <p class="detail-value">${data.data.status_filter_label || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Limit Records</label>
                            <p class="detail-value">${data.data.limit_records || 'No limit'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Include Header</label>
                            <p class="detail-value">${data.data.include_header ? 'Yes' : 'No'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Export Settings</div>
                    <div class="detail-item">
                        <label class="form-label">Include Columns</label>
                        <p class="detail-value">${data.data.include_columns_label || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Auto Process</label>
                        <p class="detail-value">${data.data.auto_process ? 'Yes' : 'No'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Notes</label>
                        <p class="detail-value">${data.data.notes || 'N/A'}</p>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Audit Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Created By</label>
                            <p class="detail-value">${data.data.created_by ? data.data.created_by.name : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Created At</label>
                            <p class="detail-value">${data.data.formatted_created_at || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated By</label>
                            <p class="detail-value">${data.data.updated_by ? data.data.updated_by.name : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated At</label>
                            <p class="detail-value">${data.data.formatted_updated_at || 'N/A'}</p>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
            `;
        })
        .catch(error => {
            console.error('Error loading export data:', error);
            showErrorDialog('Gagal', 'Data export tidak berhasil dimuat: ' + error.message);
        });
}

function openEditModal(id) {
    fetch(`/finance/virtual-account-exports/${id}/edit`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
            openModal('Edit Export');
            
            document.getElementById('modalBody').innerHTML = `
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <div class="modal-section">
                        <div class="modal-section-title">Basic Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Export Number</label>
                                <input type="text" name="export_number" class="form-input" placeholder="Auto-generated if empty">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Export Date *</label>
                                <input type="date" name="export_date" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bank *</label>
                                <select name="bank_id" class="form-input" required>
                                    <option value="">Select Bank</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">File Type *</label>
                                <select name="file_type" class="form-input" required>
                                    <option value="">Select File Type</option>
                                    <option value="csv">CSV</option>
                                    <option value="xlsx">Excel</option>
                                    <option value="txt">Text</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Date Range Filter</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status Filter</label>
                                <select name="status_filter" class="form-input">
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Limit Records</label>
                                <input type="number" name="limit_records" class="form-input" min="1" max="10000" placeholder="No limit">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Export Settings</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Include Header</label>
                                <select name="include_header" class="form-input">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">CSV Delimiter</label>
                                <select name="delimiter" class="form-input">
                                    <option value=",">Comma (,)</option>
                                    <option value=";">Semicolon (;)</option>
                                    <option value="|">Pipe (|)</option>
                                    <option value="\t">Tab</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Auto Process</label>
                                <select name="auto_process" class="form-input">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Include Columns *</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_columns[]" value="va_number" class="mr-2">
                                    VA Number
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_columns[]" value="customer_name" class="mr-2">
                                    Customer Name
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_columns[]" value="amount" class="mr-2">
                                    Amount
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_columns[]" value="due_date" class="mr-2">
                                    Due Date
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_columns[]" value="status" class="mr-2">
                                    Status
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_columns[]" value="created_at" class="mr-2">
                                    Created At
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_columns[]" value="updated_at" class="mr-2">
                                    Updated At
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="include_columns[]" value="notes" class="mr-2">
                                    Notes
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Additional Information</div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-input form-textarea" placeholder="Additional notes about this export"></textarea>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Export</button>
            `;
            
            // Populate form with existing data
            const form = document.getElementById('form');
            form.export_number.value = data.data.export_number || '';
            form.export_date.value = data.data.export_date || '';
            form.bank_id.value = data.data.bank_id || '';
            form.file_type.value = data.data.file_type || '';
            form.date_from.value = data.data.date_from || '';
            form.date_to.value = data.data.date_to || '';
            form.status_filter.value = data.data.status_filter || 'all';
            form.limit_records.value = data.data.limit_records || '';
            form.include_header.value = data.data.include_header ? '1' : '0';
            form.delimiter.value = data.data.delimiter || ',';
            form.auto_process.value = data.data.auto_process ? '1' : '0';
            form.notes.value = data.data.notes || '';
            
            // Set checkboxes for include_columns
            if (data.data.include_columns && Array.isArray(data.data.include_columns)) {
                data.data.include_columns.forEach(column => {
                    const checkbox = form.querySelector(`input[name="include_columns[]"][value="${column}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            }
        })
        .catch(error => {
            console.error('Error loading export data:', error);
            showErrorDialog('Gagal', 'Data export tidak berhasil dimuat.');
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const form = document.getElementById('form');
    const formData = new FormData(form);
    
    // Convert FormData to object
    const data = {};
    for (let [key, value] of formData.entries()) {
        if (key === 'include_columns[]') {
            if (!data.include_columns) data.include_columns = [];
            data.include_columns.push(value);
        } else {
            data[key] = value;
        }
    }
    
    const url = id ? `/finance/virtual-account-exports/${id}` : '/finance/virtual-account-exports';
    const method = id ? 'PUT' : 'POST';
    
    // Add method spoofing for PUT requests
    if (id) {
        data._method = 'PUT';
    }
    
    fetch(url, {
        method: 'POST', // Always use POST with method spoofing
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 422) {
                return response.json().then(data => {
                    throw new Error('Validation failed: ' + JSON.stringify(data.errors));
                });
            }
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Export tidak berhasil disimpan: ' + error.message);
    });
}
</script>
@endsection
