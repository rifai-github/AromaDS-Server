@extends('layouts.app')

@section('title', 'System Settings')
@section('breadcrumb', 'Home / Settings / System Settings')

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
        padding: 20px;
    }

    .modal-content {
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    }

    .modal-header {
        background: linear-gradient(135deg, #214589 0%, #1e3a8a 100%);
        color: white;
        padding: 20px;
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-footer {
        padding: 20px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        position: sticky;
        bottom: 0;
        background: white;
        border-radius: 0 0 10px 10px;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #214589 0%, #1e3a8a 100%);
        color: white;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .btn-danger {
        background: #dc2626;
        color: white;
    }

    .btn-danger:hover {
        background: #b91c1c;
    }

    .btn-success {
        background: #059669;
        color: white;
    }

    .btn-success:hover {
        background: #047857;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .status-active {
        background: #dcfce7;
        color: #166534;
    }

    .status-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .type-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        background: #e0e7ff;
        color: #3730a3;
    }

    .search-filters {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #374151;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
    }

    .form-control:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .checkbox-group input[type="checkbox"] {
        width: 16px;
        height: 16px;
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .bulk-actions {
        background: #f3f4f6;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: none;
    }

    .bulk-actions.show {
        display: block;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        text-decoration: none;
        color: #374151;
        font-size: 14px;
    }

    .pagination a:hover {
        background: #f3f4f6;
    }

    .pagination .active {
        background: #214589;
        color: white;
        border-color: #214589;
    }

    .pagination .disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">

        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
            <div class="flex flex-row justify-start items-center w-full">
                <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">System Settings</p>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Add Setting
                </button>
                <button class="btn btn-secondary btn-sm" onclick="exportSettings()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="search-filters">
            <form id="searchForm" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="form-group">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search settings..." value="{{ request('search') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="string" {{ request('type') == 'string' ? 'selected' : '' }}>String</option>
                            <option value="integer" {{ request('type') == 'integer' ? 'selected' : '' }}>Integer</option>
                            <option value="boolean" {{ request('type') == 'boolean' ? 'selected' : '' }}>Boolean</option>
                            <option value="json" {{ request('type') == 'json' ? 'selected' : '' }}>JSON</option>
                            <option value="array" {{ request('type') == 'array' ? 'selected' : '' }}>Array</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">&nbsp;</label>
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="{{ route('settings.system.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <div class="flex justify-between items-center">
                <span id="selectedCount">0 items selected</span>
                <div class="flex gap-2">
                    <button class="btn btn-success btn-sm" onclick="bulkActivate()">
                        <i class="fas fa-check"></i> Activate
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="bulkDeactivate()">
                        <i class="fas fa-times"></i> Deactivate
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="bulkDelete()">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="w-full bg-white rounded-b-[10px] p-[16px] md:p-[20px] lg:p-[24px]">
            <div class="table-container">
                <table class="responsive-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>Setting Key</th>
                            <th>Setting Value</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Updated By</th>
                            <th>Updated At</th>
                            <th>Actions</th>
                    </thead>
                    <tbody>
                        @forelse($settings as $setting)
                        <tr>
                            <td>
                                <input type="checkbox" class="item-checkbox" value="{{ $setting->id }}" onchange="updateBulkActions()">
                            </td>
                            <td>{{ $setting->setting_key }}</td>
                            <td>
                                @if(is_array($setting->setting_value))
                                    <code>{{ json_encode($setting->setting_value) }}</code>
                                @else
                                    {{ Str::limit($setting->setting_value, 50) }}
                                @endif
                            </td>
                            <td><span class="type-badge">{{ $setting->setting_type }}</span></td>
                            <td>{{ Str::limit($setting->description, 50) }}</td>
                            <td>
                                <span class="status-badge {{ $setting->is_active ? 'status-active' : 'status-inactive' }}">
                                    {{ $setting->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $setting->creator->name ?? 'System' }}</td>
                            <td>{{ $setting->created_at ? $setting->created_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') : '-' }}</td>
                            <td>{{ $setting->updater->name ?? 'System' }}</td>
                            <td>{{ $setting->updated_at ? $setting->updated_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') : '-' }}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="viewSetting({{ $setting->id }})" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-secondary" onclick="editSetting({{ $setting->id }})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @if($setting->is_active)
                                        <button class="btn btn-sm btn-secondary" onclick="deactivateSetting({{ $setting->id }})" title="Deactivate">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-success" onclick="activateSetting({{ $setting->id }})" title="Activate">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                    <button class="btn btn-sm btn-danger" onclick="deleteSetting({{ $setting->id }})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4"></i>
                                <p>No system settings found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($settings->hasPages())
            <div class="pagination">
                @if($settings->onFirstPage())
                    <span class="disabled">Previous</span>
                @else
                    <a href="{{ $settings->previousPageUrl() }}">Previous</a>
                @endif

                @foreach($settings->getUrlRange(1, $settings->lastPage()) as $page => $url)
                    @if($page == $settings->currentPage())
                        <span class="active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if($settings->hasMorePages())
                    <a href="{{ $settings->nextPageUrl() }}">Next</a>
                @else
                    <span class="disabled">Next</span>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal-overlay" id="settingModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Create System Setting</h3>
            <button onclick="closeModal()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="settingForm">
            <div class="modal-body">
                <input type="hidden" id="settingId" name="id">
                
                <div class="form-group">
                    <label class="form-label">Setting Key *</label>
                    <input type="text" id="settingKey" name="setting_key" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Setting Type *</label>
                    <select id="settingType" name="setting_type" class="form-control" required onchange="updateValueField()">
                        <option value="">Select Type</option>
                        <option value="string">String</option>
                        <option value="integer">Integer</option>
                        <option value="boolean">Boolean</option>
                        <option value="json">JSON</option>
                        <option value="array">Array</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Setting Value *</label>
                    <div id="valueFieldContainer">
                        <input type="text" id="settingValue" name="setting_value" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="isActive" name="is_active" checked>
                        <label for="isActive">Active</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Setting</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3>Confirm Delete</h3>
            <button onclick="closeDeleteModal()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this system setting? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<script>
let currentSettingId = null;
let deleteSettingId = null;

// Modal functions
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create System Setting';
    document.getElementById('settingForm').reset();
    document.getElementById('settingId').value = '';
    document.getElementById('settingModal').classList.add('show');
}

function editSetting(id) {
    // Fetch setting data and populate form
    fetch(`/settings/system/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit System Setting';
            document.getElementById('settingId').value = data.id;
            document.getElementById('settingKey').value = data.setting_key;
            document.getElementById('settingType').value = data.setting_type;
            document.getElementById('settingValue').value = typeof data.setting_value === 'object' ? JSON.stringify(data.setting_value) : data.setting_value;
            document.getElementById('description').value = data.description || '';
            document.getElementById('isActive').checked = data.is_active;
            updateValueField();
            document.getElementById('settingModal').classList.add('show');
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading setting data', 'error');
        });
}

function closeModal() {
    document.getElementById('settingModal').classList.remove('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    deleteSettingId = null;
}

function updateValueField() {
    const type = document.getElementById('settingType').value;
    const container = document.getElementById('valueFieldContainer');
    const currentValue = document.getElementById('settingValue').value;
    
    let html = '';
    
    switch(type) {
        case 'boolean':
            html = `
                <select id="settingValue" name="setting_value" class="form-control" required>
                    <option value="true" ${currentValue === 'true' ? 'selected' : ''}>True</option>
                    <option value="false" ${currentValue === 'false' ? 'selected' : ''}>False</option>
                </select>
            `;
            break;
        case 'json':
        case 'array':
            html = `
                <textarea id="settingValue" name="setting_value" class="form-control" rows="4" required placeholder="Enter valid JSON">${currentValue}</textarea>
            `;
            break;
        default:
            html = `
                <input type="${type === 'integer' ? 'number' : 'text'}" id="settingValue" name="setting_value" class="form-control" required value="${currentValue}">
            `;
    }
    
    container.innerHTML = html;
}

// Form submission
document.getElementById('settingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const settingId = formData.get('id');
    const url = settingId ? `/settings/system/${settingId}` : '/settings/system';
    const method = settingId ? 'PUT' : 'POST';
    
    // Convert form data to JSON
    const data = {};
    for (let [key, value] of formData.entries()) {
        if (key === 'is_active') {
            data[key] = true;
        } else {
            data[key] = value;
        }
    }
    
    // Handle JSON/Array values
    if (data.setting_type === 'json' || data.setting_type === 'array') {
        try {
            data.setting_value = JSON.parse(data.setting_value);
        } catch (e) {
            showAlert('Invalid JSON format', 'error');
            return;
        }
    }
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showAlert(data.message, 'success');
            closeModal();
            location.reload();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred', 'error');
    });
});

// Delete functions
function deleteSetting(id) {
    deleteSettingId = id;
    document.getElementById('deleteModal').classList.add('show');
}

function confirmDelete() {
    if (!deleteSettingId) return;
    
    fetch(`/settings/system/${deleteSettingId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showAlert(data.message, 'success');
            closeDeleteModal();
            location.reload();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred', 'error');
    });
}

// Bulk operations
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (checkboxes.length > 0) {
        bulkActions.classList.add('show');
        selectedCount.textContent = `${checkboxes.length} items selected`;
    } else {
        bulkActions.classList.remove('show');
    }
}

function bulkDelete() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (ids.length === 0) return;
    
    if (confirm(`Are you sure you want to delete ${ids.length} settings?`)) {
        fetch('/settings/system/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert(data.message, 'success');
                location.reload();
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred', 'error');
        });
    }
}

// Status toggle functions
function activateSetting(id) {
    fetch(`/settings/system/${id}/activate`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showAlert(data.message, 'success');
            location.reload();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred', 'error');
    });
}

function deactivateSetting(id) {
    fetch(`/settings/system/${id}/deactivate`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showAlert(data.message, 'success');
            location.reload();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred', 'error');
    });
}

// Export function
function exportSettings() {
    const params = new URLSearchParams(window.location.search);
    window.open(`/settings/system/export?${params.toString()}`, '_blank');
}

// Alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 6px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        max-width: 400px;
        ${type === 'success' ? 'background: #059669;' : 'background: #dc2626;'}
    `;
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endsection
