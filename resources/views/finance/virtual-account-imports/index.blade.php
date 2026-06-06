@extends('layouts.app')

@section('title', 'Virtual Account Imports')
@section('breadcrumb', 'Home / Finance / Virtual Account Imports')

@section('content')
@include('finance.shared.responsive-table-styles')

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Virtual Account Imports</h1>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Import Data</span>
            </button>
        </div>
        
        <!-- Search and Filter Controls -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center w-full bg-white p-4 border-b">
            <div class="flex flex-col md:flex-row gap-4 w-full">
                <!-- Search -->
                <div class="flex-1">
                    <input type="text" id="searchInput" placeholder="Search by import number, file name, or description..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ request('search') }}" onkeyup="handleSearch(event)">
                </div>
                
                <!-- Bank Filter -->
                <div class="w-full md:w-48">
                    <select id="bankFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Banks</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->bank_name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Status Filter -->
                <div class="w-full md:w-48">
                    <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                
                <!-- Date Filter -->
                <div class="w-full md:w-48">
                    <input type="date" id="dateFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <button onclick="applyFilters()" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <button onclick="clearFilters()" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
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
                        <th class="w-[150px]" data-column="import_number">Import Number</th>
                        <th class="w-[120px]" data-column="bank__name">Bank</th>
                        <th class="w-[150px]" data-column="file_name">File Name</th>
                        <th class="w-[100px]" data-column="file_size" data-type="numeric">File Size</th>
                        <th class="w-[100px]" data-column="total_records" data-type="numeric">Total Records</th>
                        <th class="w-[100px]" data-column="processed_count" data-type="numeric">Processed</th>
                        <th class="w-[100px]" data-column="success_count" data-type="numeric">Success</th>
                        <th class="w-[100px]" data-column="status">Status</th>
                        <th class="w-[120px]" data-column="creator.name">Created By</th>
                        <th class="w-[120px]" data-column="created_at" data-type="date">Created At</th>
                        <th class="w-[120px]" data-column="updater.name">Last Updated By</th>
                        <th class="w-[150px]" data-column="updated_at" data-type="date">Last Updated At</th>
                        <th class="w-[100px]" data-no-filter>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($imports as $import)
                    <tr onclick="openViewModal({{ $import->id }})" data-id="{{ $import->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $import->id }}">
                        </td>
                        <td class="font-semibold">{{ $import->import_number ?? 'N/A' }}</td>
                        <td>{{ $import->bank ? $import->bank->bank_name : 'N/A' }}</td>
                        <td>{{ $import->file_name ?? 'N/A' }}</td>
                        <td>{{ $import->formatted_file_size ?? 'N/A' }}</td>
                        <td class="font-semibold">{{ $import->total_records ?? 0 }}</td>
                        <td class="font-semibold">{{ $import->processed_records ?? 0 }}</td>
                        <td class="font-semibold text-green-600">{{ $import->success_count ?? 0 }}</td>
                        <td>
                            <span class="badge badge-{{ $import->status }}">
                                {{ ucfirst($import->status ?? 'N/A') }}
                            </span>
                        </td>
                        <td>{{ $import->creator->name ?? 'N/A' }}</td>
                        <td>{{ $import->created_at_formatted ?? 'N/A' }}</td>
                        <td>{{ $import->updater->name ?? 'N/A' }}</td>
                        <td>{{ $import->updated_at ? \Carbon\Carbon::parse($import->updated_at)->format('d/M/Y H:i') : 'N/A' }}</td>
                        <td class="text-center">
                            <div class="flex gap-1 justify-center">
                                <button class="btn btn-sm btn-outline" onclick="event.stopPropagation(); openEditModal({{ $import->id }})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @if($import->canProcess())
                                <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); processImport({{ $import->id }})" title="Process">
                                    <i class="fas fa-play"></i>
                                </button>
                                @endif
                                @if($import->canRetry())
                                <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); retryImport({{ $import->id }})" title="Retry">
                                    <i class="fas fa-redo"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No imports found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('finance.shared.modal-overlay')

<script>
@include('finance.shared.table-scripts')

// Modal functions
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Import Virtual Account Data';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Upload a file to import virtual account data. Import number will be auto-generated.</p>
        <form id="createForm" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">File *</label>
                <input type="file" name="file" class="form-input" accept=".csv,.xlsx,.xls" required>
                <small class="text-gray-500">Supported formats: CSV, XLSX, XLS (Max 10MB)</small>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3" placeholder="Enter import description (optional)"></textarea>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Import Data</button>
        </div>
    `;
    openModal();
}

function openViewModal(id) {
    fetch(`/finance/virtual-account-imports/${id}/data`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Import Details';
            document.getElementById('modalBody').innerHTML = `
                <div class="space-y-4">
                    <div class="detail-item">
                        <label class="form-label">Import Number</label>
                        <p class="detail-value font-semibold">${data.import_number || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Bank</label>
                        <p class="detail-value">${data.bank_name || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">File Name</label>
                        <p class="detail-value">${data.file_name || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">File Size</label>
                        <p class="detail-value">${data.file_size || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">File Type</label>
                        <p class="detail-value">${data.file_type || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Total Records</label>
                        <p class="detail-value font-semibold">${data.total_records || 0}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Processed Records</label>
                        <p class="detail-value font-semibold">${data.processed_records || 0}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Success Count</label>
                        <p class="detail-value font-semibold text-green-600">${data.success_count || 0}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Failed Count</label>
                        <p class="detail-value font-semibold text-red-600">${data.failed_count || 0}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Status</label>
                        <p class="detail-value">
                            <span class="badge badge-${data.status}">${data.status_text || 'N/A'}</span>
                        </p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Description</label>
                        <p class="detail-value">${data.description || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Created At</label>
                        <p class="detail-value">${data.created_at || 'N/A'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Created By</label>
                        <p class="detail-value">${data.created_by || 'N/A'}</p>
                    </div>
                </div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Close</button>
                    <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                </div>
            `;
            openModal();
        })
        .catch(error => {
            console.error('Error loading import data:', error);
            showErrorDialog('Gagal', 'Data import tidak berhasil dimuat.');
        });
}

function openEditModal(id) {
    fetch(`/finance/virtual-account-imports/${id}/data`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Import';
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update the import details. Import number cannot be changed.</p>
                <form id="editForm">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="form-group">
                        <label class="form-label">Import Number</label>
                        <input type="text" name="import_number" class="form-input" value="${data.import_number || ''}" readonly>
                        <small class="text-gray-500">Import number is auto-generated and cannot be changed</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input" rows="3" placeholder="Enter import description">${data.description || ''}</textarea>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Update Import</button>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error loading import data:', error);
            showErrorDialog('Gagal', 'Data import tidak berhasil dimuat.');
        });
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    fetch('/finance/virtual-account-imports/api', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', 'Import tidak berhasil dibuat: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Import tidak berhasil dibuat.');
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    fetch(`/finance/virtual-account-imports/${id}/api`, {
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
            showErrorDialog('Gagal', 'Import tidak berhasil diperbarui: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Import tidak berhasil diperbarui.');
    });
}

// Search and Filter Functions
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const bankId = document.getElementById('bankFilter').value;
    const status = document.getElementById('statusFilter').value;
    const date = document.getElementById('dateFilter').value;
    
    const params = window.AromaTableState.paramsWithCurrentSort();
    if (search) params.append('search', search);
    if (bankId) params.append('bank_id', bankId);
    if (status) params.append('status', status);
    if (date) params.append('import_date', date);
    
    const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.location.href = url;
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('bankFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('dateFilter').value = '';
    window.location.href = window.location.pathname;
}

// Process and Retry Functions
function processImport(id) {
    showConfirmDialog(
        'Proses Import?',
        'Apakah Anda yakin ingin memproses import ini?',
        'Ya, proses',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;
        fetch(`/finance/virtual-account-imports/${id}/process`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showErrorDialog('Gagal', 'Import tidak berhasil diproses: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Import tidak berhasil diproses.');
        });
    });
}

function retryImport(id) {
    showConfirmDialog(
        'Ulangi Import?',
        'Apakah Anda yakin ingin mengulangi import ini?',
        'Ya, ulangi',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;
        fetch(`/finance/virtual-account-imports/${id}/retry`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showErrorDialog('Gagal', 'Import tidak berhasil diulang: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Import tidak berhasil diulang.');
        });
    });
}

// Search functionality
function handleSearch(event) {
    if (event.key === 'Enter') {
        applyFilters();
    }
}

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const bank = document.getElementById('bankFilter').value;
    const status = document.getElementById('statusFilter').value;
    const date = document.getElementById('dateFilter').value;
    
    const params = window.AromaTableState.paramsWithCurrentSort();
    
    if (search) params.append('search', search);
    if (bank) params.append('bank_id', bank);
    if (status) params.append('status', status);
    if (date) params.append('import_date', date);
    
    const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.location.href = url;
}

// Initialize filters from URL parameters
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('search')) {
        document.getElementById('searchInput').value = urlParams.get('search');
    }
    if (urlParams.get('bank_id')) {
        document.getElementById('bankFilter').value = urlParams.get('bank_id');
    }
    if (urlParams.get('status')) {
        document.getElementById('statusFilter').value = urlParams.get('status');
    }
    if (urlParams.get('import_date')) {
        document.getElementById('dateFilter').value = urlParams.get('import_date');
    }
});
</script>
@endsection
