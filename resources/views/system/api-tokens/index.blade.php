@extends('layouts.app')

@section('title', 'API Tokens')
@section('breadcrumb', 'Home / System / API Tokens')

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
    
    .status-active {
        background-color: #dcfce7;
        color: #166534;
    }
    
    .status-expiring-soon {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-expired {
        background-color: #fee2e2;
        color: #991b1b;
    }
    
    .status-never-used {
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
        
        <!-- API Tokens Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">API Tokens</h1>
            </div>
            
            <div class="flex gap-2">
                <button class="btn btn-secondary" onclick="exportApiTokens()">
                    <i class="fas fa-download"></i>
                    <span>Export</span>
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span>Add New Token</span>
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
                    <span>Delete</span>
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
                            <option value="expiring_soon">Expiring Soon</option>
                            <option value="never_used">Never Used</option>
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
                        <th class="w-[200px]" data-column="tokenable.name">User</th>
                        <th class="w-[200px]" data-column="name">Name</th>
                        <th class="w-[200px]" data-column="abilities">Abilities</th>
                        <th class="w-[100px]" data-column="status">Status</th>
                        <th class="w-[150px]" data-column="last_used_at" data-type="date">Last Used</th>
                        <th class="w-[150px]" data-column="expires_at" data-type="date">Expires At</th>
                        <th class="w-[150px]" data-column="created_at" data-type="date">Created At</th>
                        <th class="w-[150px]" data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($apiTokens as $apiToken)
                    <tr onclick="openViewModal({{ $apiToken->id }})" data-id="{{ $apiToken->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $apiToken->id }}">
                        </td>
                        <td>{{ $apiToken->user->name ?? 'N/A' }}</td>
                        <td>{{ $apiToken->name }}</td>
                        <td>
                            <span class="text-sm text-blue-600">{{ count($apiToken->abilities) }} ability(ies)</span>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $apiToken->status }}">
                                {{ $apiToken->status_text }}
                            </span>
                        </td>
                        <td>{{ $apiToken->last_used_formatted }}</td>
                        <td>{{ $apiToken->expires_at ? $apiToken->expires_at->format('d/M/Y') : 'Never' }}</td>
                        <td>{{ $apiToken->created_at->format('d/M/Y') }}</td>
                        <td>{{ $apiToken->updated_at ? $apiToken->updated_at->format('d/M/Y') : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No API tokens found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if(isset($apiTokens) && $apiTokens->currentPage() > 1)
                    <a href="{{ $apiTokens->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if(isset($apiTokens) && $apiTokens->lastPage() > 0)
                    @php
                        $start = max(1, $apiTokens->currentPage() - 2);
                        $end = min($apiTokens->lastPage(), $apiTokens->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $apiTokens->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $apiTokens->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $apiTokens->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $apiTokens->lastPage())
                            @if($end < $apiTokens->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $apiTokens->url($apiTokens->lastPage()) }}" class="page-number">{{ $apiTokens->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if(isset($apiTokens) && $apiTokens->currentPage() < $apiTokens->lastPage())
                    <a href="{{ $apiTokens->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $apiTokens->currentPage() ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $apiTokens->lastPage() ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">API Token Details</h2>
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
    document.getElementById('modalTitle').textContent = 'Add New API Token';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Create a new API token with specific permissions.</p>
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
                <label class="form-label">Token Name *</label>
                <input type="text" name="name" class="form-input" placeholder="Enter token name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Abilities *</label>
                <div class="space-y-2">
                    @foreach($abilities as $key => $ability)
                        <label class="flex items-center">
                            <input type="checkbox" name="abilities[]" value="{{ $key }}" class="mr-2">
                            <span class="text-sm">{{ $ability }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Expires At</label>
                <input type="datetime-local" name="expires_at" class="form-input">
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Create Token</button>
        </div>
    `;
    openModal();
}

function openViewModal(id) {
    // Load data via AJAX
    fetch(`/system/api-tokens/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const apiToken = data.data;
                document.getElementById('modalTitle').textContent = 'API Token Details';
                document.getElementById('modalBody').innerHTML = `
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">User</label>
                            <p class="text-gray-700">${apiToken.user ? apiToken.user.name : 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Token Name</label>
                            <p class="text-gray-700">${apiToken.name}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Abilities</label>
                            <p class="text-gray-700">${apiToken.formatted_abilities}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <span class="status-badge status-${apiToken.status}">
                                ${apiToken.status_text}
                            </span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Used</label>
                            <p class="text-gray-700">${apiToken.last_used_formatted}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Expires At</label>
                            <p class="text-gray-700">${apiToken.expires_at ? new Date(apiToken.expires_at).toLocaleDateString() : 'Never'}</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Created At</label>
                            <p class="text-gray-700">${new Date(apiToken.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                `;
                document.getElementById('modalFooter').innerHTML = `
                    <div class="flex justify-center gap-6">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Tutup</button>
                        <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                        <button type="button" class="btn btn-secondary" onclick="regenerateToken(${id})">Regenerate</button>
                    </div>
                `;
                openModal();
            }
        })
        .catch(error => {
            console.error('Error loading API token data:', error);
            showErrorDialog('Gagal', 'Gagal memuat data API token.');
        });
}

function openEditModal(id) {
    // Load data via AJAX
    fetch(`/system/api-tokens/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const apiToken = data.data;
                document.getElementById('modalTitle').textContent = 'Edit API Token';
                document.getElementById('modalBody').innerHTML = `
                    <p class="text-gray-600 mb-6 text-center">Update API token information and permissions.</p>
                    <form id="editForm">
                        <input type="hidden" name="id" value="${apiToken.id}">
                        <div class="form-group">
                            <label class="form-label">User *</label>
                            <select name="user_id" class="form-input" required>
                                <option value="">Select User</option>
                                ${data.users.map(user => 
                                    `<option value="${user.id}" ${user.id == apiToken.user_id ? 'selected' : ''}>${user.name}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Token Name *</label>
                            <input type="text" name="name" class="form-input" value="${apiToken.name}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Abilities *</label>
                            <div class="space-y-2">
                                ${Object.entries(data.abilities).map(([key, ability]) => `
                                    <label class="flex items-center">
                                        <input type="checkbox" name="abilities[]" value="${key}" ${apiToken.abilities && apiToken.abilities.includes(key) ? 'checked' : ''} class="mr-2">
                                        <span class="text-sm">${ability}</span>
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Expires At</label>
                            <input type="datetime-local" name="expires_at" class="form-input" value="${apiToken.expires_at ? apiToken.expires_at.slice(0, 16) : ''}">
                        </div>
                    </form>
                `;
                document.getElementById('modalFooter').innerHTML = `
                    <div class="flex justify-center gap-6">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
                        <button type="button" class="btn btn-primary" onclick="submitEditForm()">Perbarui Token</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading API token data:', error);
            showErrorDialog('Gagal', 'Gagal memuat data API token.');
        });
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    // Get selected abilities
    const abilities = Array.from(document.querySelectorAll('input[name="abilities[]"]:checked')).map(cb => cb.value);
    formData.set('abilities', JSON.stringify(abilities));
    
    fetch('/system/api-tokens', {
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
            if (data.token) {
                showInfoDialog(`API token berhasil dibuat.\n\nToken: ${data.token}\n\nSilakan simpan token ini sekarang karena tidak akan ditampilkan lagi.`);
            }
            location.reload();
        } else {
            showErrorDialog('Gagal', 'Gagal membuat API token: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal membuat API token.');
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    // Get selected abilities
    const abilities = Array.from(document.querySelectorAll('input[name="abilities[]"]:checked')).map(cb => cb.value);
    formData.set('abilities', JSON.stringify(abilities));
    
    fetch(`/system/api-tokens/${id}`, {
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
            showErrorDialog('Gagal', 'Gagal memperbarui API token: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Gagal memperbarui API token.');
    });
}

function regenerateToken(id) {
    showConfirmDialog(
        'Regenerate API token ini?',
        'Token lama tidak akan bisa digunakan lagi.'
    ).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        fetch(`/system/api-tokens/${id}/regenerate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showInfoDialog(`API token berhasil di-regenerate.\n\nToken baru: ${data.token}\n\nSilakan simpan token ini sekarang karena tidak akan ditampilkan lagi.`);
                location.reload();
            } else {
                showErrorDialog('Gagal', 'Gagal regenerate API token: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal regenerate API token.');
        });
    });
}

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu API token yang ingin dihapus.');
        return;
    }
    
    showConfirmDialog(
        'Hapus API token yang dipilih?',
        `${checkboxes.length} API token akan dihapus.`
    ).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
        
        fetch('/system/api-tokens/bulk-delete', {
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
                showErrorDialog('Gagal', 'Gagal menghapus API token: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Gagal menghapus API token.');
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

function exportApiTokens() {
    const userId = document.getElementById('userFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    const params = window.AromaTableState.paramsWithCurrentSort();
    if (userId) params.append('user_id', userId);
    if (status) params.append('status', status);
    
    window.open('/system/api-tokens/export?' + params.toString(), '_blank');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
@endsection
