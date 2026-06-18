@extends('layouts.app')

@section('title', 'Theme Settings')
@section('breadcrumb', 'Home / Settings / Theme Settings')

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
        max-width: 800px;
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

    .btn-warning {
        background: #d97706;
        color: white;
    }

    .btn-warning:hover {
        background: #b45309;
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

    .default-badge {
        background: #fef3c7;
        color: #92400e;
    }

    .color-preview {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
        border: 2px solid #e5e7eb;
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

    .theme-preview {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .color-swatches {
        display: flex;
        gap: 5px;
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">

        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
            <div class="flex flex-row justify-start items-center w-full">
                <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Theme Settings</p>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Add Theme
                </button>
                <button class="btn btn-secondary btn-sm" onclick="exportThemes()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="search-filters">
            <form id="searchForm" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search themes..." value="{{ request('search') }}">
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
                            <a href="{{ route('settings.theme') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Content -->
        <div class="w-full bg-white rounded-b-[10px] p-[16px] md:p-[20px] lg:p-[24px]">
            <div class="table-container">
                <table class="responsive-table">
                    <thead>
                        <tr>
                            <th>Theme Name</th>
                            <th>Description</th>
                            <th>Colors</th>
                            <th>Font</th>
                            <th>Status</th>
                            <th>Default</th>
                            <th>Created</th>
                             
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($themes as $theme)
                        <tr>
                            <td>
                                <div class="font-medium">{{ $theme->theme_name }}</div>
                            </td>
                            <td>{{ Str::limit($theme->theme_description, 50) }}</td>
                            <td>
                                <div class="color-swatches">
                                    <span class="color-preview" style="background-color: {{ $theme->color_primary }}" title="Primary: {{ $theme->color_primary }}"></span>
                                    <span class="color-preview" style="background-color: {{ $theme->color_secondary }}" title="Secondary: {{ $theme->color_secondary }}"></span>
                                    <span class="color-preview" style="background-color: {{ $theme->color_accent }}" title="Accent: {{ $theme->color_accent }}"></span>
                                </div>
                            </td>
                            <td>
                                <div class="text-sm">
                                    <div>{{ $theme->font_family }}</div>
                                    <div class="text-gray-500">{{ $theme->font_size }}px</div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge {{ $theme->is_active ? 'status-active' : 'status-inactive' }}">
                                    {{ $theme->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @if($theme->is_default)
                                    <span class="status-badge default-badge">Default</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td>{{ $theme->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="previewTheme({{ $theme->id }})" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-secondary" onclick="editTheme({{ $theme->id }})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @if(!$theme->is_default)
                                        <button class="btn btn-sm btn-warning" onclick="setDefaultTheme({{ $theme->id }})" title="Set as Default">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    @endif
                                    @if($theme->is_active && !$theme->is_default)
                                        <button class="btn btn-sm btn-secondary" onclick="deactivateTheme({{ $theme->id }})" title="Deactivate">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @elseif(!$theme->is_active)
                                        <button class="btn btn-sm btn-success" onclick="activateTheme({{ $theme->id }})" title="Activate">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                    <button class="btn btn-sm btn-primary" onclick="applyTheme({{ $theme->id }})" title="Apply">
                                        <i class="fas fa-paint-brush"></i>
                                    </button>
                                    @if(!$theme->is_default)
                                        <button class="btn btn-sm btn-danger" onclick="deleteTheme({{ $theme->id }})" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-500">
                                <i class="fas fa-palette text-4xl mb-4"></i>
                                <p>No themes found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($themes->hasPages())
            <div class="flex flex-row justify-center items-center w-full p-4">
                {{ $themes->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal-overlay" id="themeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Create Theme</h3>
            <button onclick="closeModal()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="themeForm">
            <div class="modal-body">
                <input type="hidden" id="themeId" name="id">
                
                <div class="form-group">
                    <label class="form-label">Theme Name *</label>
                    <input type="text" id="themeName" name="theme_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea id="themeDescription" name="theme_description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label">Primary Color *</label>
                        <input type="color" id="colorPrimary" name="color_primary" class="form-control" value="#214589" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Secondary Color *</label>
                        <input type="color" id="colorSecondary" name="color_secondary" class="form-control" value="#6b7280" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Accent Color *</label>
                        <input type="color" id="colorAccent" name="color_accent" class="form-control" value="#059669" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Font Family *</label>
                        <select id="fontFamily" name="font_family" class="form-control" required>
                            <option value="Arial">Arial</option>
                            <option value="Helvetica">Helvetica</option>
                            <option value="Times New Roman">Times New Roman</option>
                            <option value="Courier New">Courier New</option>
                            <option value="Verdana">Verdana</option>
                            <option value="Georgia">Georgia</option>
                            <option value="Palatino">Palatino</option>
                            <option value="Garamond">Garamond</option>
                            <option value="Bookman">Bookman</option>
                            <option value="Comic Sans MS">Comic Sans MS</option>
                            <option value="Trebuchet MS">Trebuchet MS</option>
                            <option value="Arial Black">Arial Black</option>
                            <option value="Impact">Impact</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Font Size (px) *</label>
                        <input type="number" id="fontSize" name="font_size" class="form-control" min="8" max="72" value="14" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="isDefault" name="is_default">
                        <label for="isDefault">Set as Default Theme</label>
                    </div>
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
                <button type="submit" class="btn btn-primary">Save Theme</button>
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
            <p>Are you sure you want to delete this theme? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<script>
let currentThemeId = null;
let deleteThemeId = null;

// Modal functions
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Theme';
    document.getElementById('themeForm').reset();
    document.getElementById('themeId').value = '';
    document.getElementById('themeModal').classList.add('show');
}

function editTheme(id) {
    // Fetch theme data and populate form
    fetch(`/settings/themes/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Theme';
            document.getElementById('themeId').value = data.id;
            document.getElementById('themeName').value = data.theme_name;
            document.getElementById('themeDescription').value = data.theme_description || '';
            document.getElementById('colorPrimary').value = data.color_primary;
            document.getElementById('colorSecondary').value = data.color_secondary;
            document.getElementById('colorAccent').value = data.color_accent;
            document.getElementById('fontFamily').value = data.font_family;
            document.getElementById('fontSize').value = data.font_size;
            document.getElementById('isDefault').checked = data.is_default;
            document.getElementById('isActive').checked = data.is_active;
            document.getElementById('themeModal').classList.add('show');
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading theme data', 'error');
        });
}

function closeModal() {
    document.getElementById('themeModal').classList.remove('show');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    deleteThemeId = null;
}

// Form submission
document.getElementById('themeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const themeId = formData.get('id');
    const url = themeId ? `/settings/themes/${themeId}` : '/settings/themes';
    const method = themeId ? 'PUT' : 'POST';
    
    // Convert form data to JSON
    const data = {};
    for (let [key, value] of formData.entries()) {
        if (key === 'is_default' || key === 'is_active') {
            data[key] = true;
        } else {
            data[key] = value;
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
function deleteTheme(id) {
    deleteThemeId = id;
    document.getElementById('deleteModal').classList.add('show');
}

function confirmDelete() {
    if (!deleteThemeId) return;
    
    fetch(`/settings/themes/${deleteThemeId}`, {
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

// Theme actions
function setDefaultTheme(id) {
    if (confirm('Are you sure you want to set this theme as default?')) {
        fetch(`/settings/themes/${id}/set-default`, {
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
}

function activateTheme(id) {
    fetch(`/settings/themes/${id}/activate`, {
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

function deactivateTheme(id) {
    fetch(`/settings/themes/${id}/deactivate`, {
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

function applyTheme(id) {
    if (confirm('Are you sure you want to apply this theme?')) {
        fetch(`/settings/themes/${id}/apply`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAlert(data.message, 'success');
                // Optionally reload the page to apply the theme
                setTimeout(() => location.reload(), 1000);
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

function previewTheme(id) {
    fetch(`/settings/themes/${id}/preview`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Show theme preview in a modal or alert
                const theme = data.data;
                const preview = `
                    Theme: ${theme.name}
                    Primary: ${theme.colors.primary}
                    Secondary: ${theme.colors.secondary}
                    Accent: ${theme.colors.accent}
                    Font: ${theme.typography.font_family} (${theme.typography.font_size}px)
                `;
                alert(preview);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading theme preview', 'error');
        });
}

// Export function
function exportThemes() {
    const params = new URLSearchParams(window.location.search);
    window.open(`/settings/themes/export?${params.toString()}`, '_blank');
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
