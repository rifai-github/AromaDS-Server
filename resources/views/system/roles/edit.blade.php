@extends('layouts.app')

@section('title', 'Edit Role - System')
@section('breadcrumb', 'Home / System / Master Role / Edit Role')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }

    *, *::before, *::after {
        box-sizing: border-box;
    }

    /* Main Container */
    .role-detail-container {
        display: flex;
        gap: 20px;
        margin-top: 20px;
        min-height: calc(100vh - 200px);
    }

    /* Left Sidebar - Module List */
    .module-sidebar {
        width: 250px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        padding: 20px 0;
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }

    .module-sidebar-title {
        padding: 0 20px 15px;
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 10px;
    }

    .module-item {
        padding: 12px 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .module-item:hover {
        background-color: #f9fafb;
    }

    .module-item.active {
        background-color: #fef3f2;
        border-left-color: #ef4444;
        color: #ef4444;
        font-weight: 600;
    }

    .module-item i {
        width: 20px;
        text-align: center;
    }

    /* Right Panel - Permission Table */
    .permission-panel {
        flex: 1;
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .permission-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e5e7eb;
    }

    .permission-panel-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
    }

    .search-box {
        position: relative;
        width: 300px;
    }

    .search-box input {
        width: 100%;
        padding: 8px 12px 8px 35px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
    }

    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }

    /* Permission Table */
    .permission-table-container {
        overflow-x: auto;
    }

    .permission-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .permission-table thead {
        background-color: #f9fafb;
    }

    .permission-table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
        white-space: nowrap;
    }

    .permission-table th:first-child {
        width: 40%;
    }

    .permission-table th:not(:first-child) {
        text-align: center;
        width: 12%;
    }

    .permission-table td {
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
    }

    .permission-table td:first-child {
        font-weight: 500;
        color: #1f2937;
    }

    .permission-table td:not(:first-child) {
        text-align: center;
    }

    .permission-table tbody tr:hover {
        background-color: #f9fafb;
    }

    /* Checkbox Styling */
    .permission-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #214589;
    }

    /* Role Info Header */
    .role-info-header {
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 20px;
    }

    .role-info-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 10px;
    }

    .role-info-meta {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
        color: #6b7280;
        font-size: 14px;
    }

    .role-info-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .role-info-meta-item i {
        color: #9ca3af;
    }

    /* Form Input Styles */
    .form-group {
        margin-bottom: 15px;
    }

    .form-label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #374151;
        font-size: 14px;
    }

    .form-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s;
    }

    .form-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        resize: vertical;
        min-height: 80px;
    }

    .form-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background-color: white;
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

    .btn-success {
        background-color: #10b981;
        color: white;
    }

    .btn-success:hover {
        background-color: #059669;
    }

    /* Badge */
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .badge-secondary {
        background-color: #e5e7eb;
        color: #374151;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 48px;
        color: #d1d5db;
        margin-bottom: 16px;
    }

    .empty-state p {
        font-size: 16px;
    }

    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<form id="editRoleForm" action="{{ route('system.roles.update', $role->id) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="role-info-header">
        <div class="form-group">
            <label class="form-label">Role Name *</label>
            <input type="text" name="name" class="form-input" value="{{ $role->name }}" placeholder="Enter role name" required>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea" placeholder="Enter role description">{{ $role->description ?? '' }}</textarea>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">System Reserved</label>
                <select name="system_reserved" class="form-select">
                    <option value="0" {{ !$role->system_reserved ? 'selected' : '' }}>User Created</option>
                    <option value="1" {{ $role->system_reserved ? 'selected' : '' }}>System Reserved</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-select">
                    <option value="1" {{ $role->is_active ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ !$role->is_active ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
        <div style="margin-top: 15px;">
            <a href="{{ route('system.roles.show', $role->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancel
            </a>
            <button type="submit" class="btn btn-success" style="margin-left: 10px;">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </div>

    <div class="role-detail-container">
        <!-- Left Sidebar - Module List -->
        <div class="module-sidebar">
            <div class="module-sidebar-title">Modul</div>
            @foreach(array_keys($menuItems) as $index => $moduleName)
            @if($moduleName !== 'Other' && !empty($menuItems[$moduleName]))
            <div class="module-item {{ $index === 0 ? 'active' : '' }}" data-module="{{ strtolower($moduleName) }}" onclick="filterByModule('{{ strtolower($moduleName) }}')">
                <i class="{{ $moduleIcons[strtolower($moduleName)] ?? 'fas fa-cube' }}"></i>
                <span>{{ $moduleName }}</span>
            </div>
            @endif
            @endforeach
            @if(isset($menuItems['Other']) && count($menuItems['Other']) > 0)
            <div class="module-item" data-module="other" onclick="filterByModule('other')">
                <i class="fas fa-ellipsis-h"></i>
                <span>Other</span>
            </div>
            @endif
        </div>

        <!-- Right Panel - Permission Table -->
        <div class="permission-panel">
            <div class="permission-panel-header">
                <div class="permission-panel-title">Akses Menu</div>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="permissionSearch" placeholder="Cari..." onkeyup="filterPermissions()">
                </div>
            </div>

            <div class="permission-table-container">
                <table class="permission-table">
                    <thead>
                        <tr>
                            <th>Akses Menu</th>
                            <th>
                                <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                    <input type="checkbox" id="checkAllActive" onchange="checkAllPermissions('active')" style="cursor: pointer;">
                                    <label for="checkAllActive" style="cursor: pointer; margin: 0;">Aktif</label>
                                </div>
                            </th>
                            <th>
                                <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                    <input type="checkbox" id="checkAllCreate" onchange="checkAllPermissions('create')" style="cursor: pointer;">
                                    <label for="checkAllCreate" style="cursor: pointer; margin: 0;">Buat</label>
                                </div>
                            </th>
                            <th>
                                <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                    <input type="checkbox" id="checkAllUpdate" onchange="checkAllPermissions('update')" style="cursor: pointer;">
                                    <label for="checkAllUpdate" style="cursor: pointer; margin: 0;">Ubah</label>
                                </div>
                            </th>
                            <th>
                                <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                    <input type="checkbox" id="checkAllDelete" onchange="checkAllPermissions('delete')" style="cursor: pointer;">
                                    <label for="checkAllDelete" style="cursor: pointer; margin: 0;">Hapus</label>
                                </div>
                            </th>
                            <th>
                                <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                    <input type="checkbox" id="checkAllView" onchange="checkAllPermissions('view')" style="cursor: pointer;">
                                    <label for="checkAllView" style="cursor: pointer; margin: 0;">Lihat</label>
                                </div>
                            </th>
                            <th>
                                <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                    <input type="checkbox" id="checkAllApprove" onchange="checkAllPermissions('approve')" style="cursor: pointer;">
                                    <label for="checkAllApprove" style="cursor: pointer; margin: 0;">Approve</label>
                                </div>
                            </th>
                            <th>
                                <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                    <input type="checkbox" id="checkAllDownload" onchange="checkAllPermissions('download')" style="cursor: pointer;">
                                    <label for="checkAllDownload" style="cursor: pointer; margin: 0;">Download</label>
                                </div>
                            </th>
                            <th>
                                <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                    <input type="checkbox" id="checkAllPrint" onchange="checkAllPermissions('print')" style="cursor: pointer;">
                                    <label for="checkAllPrint" style="cursor: pointer; margin: 0;">Print</label>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="permissionTableBody">
                        @foreach($menuItems as $module => $items)
                            @foreach($items as $resourceKey => $menuData)
                            <tr class="permission-row" data-row-id="{{ $resourceKey }}" data-module="{{ strtolower($module) }}" data-menu="{{ strtolower($menuData['name']) }}">
                                <td>
                                    <strong>{{ $menuData['name'] }}</strong>
                                    @if($menuData['permissions']['active'])
                                        <span class="badge badge-success" style="margin-left: 8px; font-size: 10px;">Active</span>
                                    @endif
                                </td>
                                <td>
                                    <input type="checkbox" class="permission-checkbox permission-active" 
                                           data-row-id="{{ $resourceKey }}"
                                           onchange="toggleAllPermissions(this, '{{ $resourceKey }}', '{{ strtolower($module) }}')"
                                           {{ $menuData['permissions']['active'] ? 'checked' : '' }}>
                                </td>
                                {{-- Create --}}
                                 <td>
                                    @if(!$menuData['is_action_only'])
                                    <input type="checkbox" class="permission-checkbox permission-create" 
                                           value="1"
                                           data-row="{{ $resourceKey }}"
                                           data-module="{{ strtolower($module) }}"
                                           {{ $menuData['permissions']['create'] ? 'checked' : '' }}
                                           onchange="updateActiveCheckbox(this)">
                                    @endif
                                </td>
                                {{-- Update --}}
                                <td>
                                    @if(!$menuData['is_action_only'])
                                    <input type="checkbox" class="permission-checkbox permission-update" 
                                           value="1"
                                           data-row="{{ $resourceKey }}"
                                           data-module="{{ strtolower($module) }}"
                                           {{ $menuData['permissions']['update'] ? 'checked' : '' }}
                                           onchange="updateActiveCheckbox(this)">
                                    @endif
                                </td>
                                {{-- Delete --}}
                                <td>
                                    @if(!$menuData['is_action_only'])
                                    <input type="checkbox" class="permission-checkbox permission-delete" 
                                           value="1"
                                           data-row="{{ $resourceKey }}"
                                           data-module="{{ strtolower($module) }}"
                                           {{ $menuData['permissions']['delete'] ? 'checked' : '' }}
                                           onchange="updateActiveCheckbox(this)">
                                    @endif
                                </td>
                                {{-- View --}}
                                <td>
                                    <input type="checkbox" class="permission-checkbox permission-view" 
                                           value="1"
                                           data-row="{{ $resourceKey }}"
                                           data-module="{{ strtolower($module) }}"
                                           {{ $menuData['permissions']['view'] ? 'checked' : '' }}
                                           onchange="updateActiveCheckbox(this)">
                                </td>
                                {{-- Approve --}}
                                <td>
                                    @if(!$menuData['is_action_only'])
                                    <input type="checkbox" class="permission-checkbox permission-approve" 
                                           value="1"
                                           data-row="{{ $resourceKey }}"
                                           data-module="{{ strtolower($module) }}"
                                           {{ $menuData['permissions']['approve'] ? 'checked' : '' }}
                                           onchange="updateActiveCheckbox(this)">
                                    @endif
                                </td>
                                {{-- Download --}}
                                <td>
                                    @if(!$menuData['is_action_only'])
                                    <input type="checkbox" class="permission-checkbox permission-download" 
                                           value="1"
                                           data-row="{{ $resourceKey }}"
                                           data-module="{{ strtolower($module) }}"
                                           {{ $menuData['permissions']['download'] ? 'checked' : '' }}
                                           onchange="updateActiveCheckbox(this)">
                                    @endif
                                </td>
                                {{-- Print --}}
                                <td>
                                    @if(!$menuData['is_action_only'])
                                    <input type="checkbox" class="permission-checkbox permission-print" 
                                           value="1"
                                           data-row="{{ $resourceKey }}"
                                           data-module="{{ strtolower($module) }}"
                                           {{ $menuData['permissions']['print'] ? 'checked' : '' }}
                                           onchange="updateActiveCheckbox(this)">
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        @endforeach
                        
                        @if(empty($menuItems))
                        <tr>
                            <td colspan="9" class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No permissions found</p>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

<script>
    let currentModule = document.querySelector('.module-sidebar .module-item.active')?.getAttribute('data-module') || 'marketing';

    function filterByModule(module) {
        currentModule = module;
        
        // Update active state
        document.querySelectorAll('.module-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[data-module="${module}"]`).classList.add('active');
        
        // Filter table rows
        filterPermissions();
    }
    
    // Initialize filter on page load
    document.addEventListener('DOMContentLoaded', function() {
        filterPermissions();
        updateCheckAllCheckboxes();
    });

    function filterPermissions() {
        const searchTerm = document.getElementById('permissionSearch').value.toLowerCase();
        const rows = document.querySelectorAll('.permission-row');
        
        rows.forEach(row => {
            const module = row.getAttribute('data-module');
            const menu = row.getAttribute('data-menu');
            const menuText = row.querySelector('td:first-child').textContent.toLowerCase();
            
            const matchesModule = currentModule === 'all' || module === currentModule;
            const matchesSearch = menuText.includes(searchTerm) || menu.includes(searchTerm);
            
            if (matchesModule && matchesSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update check all checkboxes after filtering
        updateCheckAllCheckboxes();
    }

    // Toggle all permissions when "Aktif" checkbox is checked
    function toggleAllPermissions(activeCheckbox, resource, module) {
        const row = activeCheckbox.closest('tr');
        const checkboxes = row.querySelectorAll('.permission-checkbox:not(.permission-active)');
        
        checkboxes.forEach(cb => {
            cb.checked = activeCheckbox.checked;
        });
        
        updateCheckAllCheckboxes();
    }

    // Update "Aktif" checkbox based on other checkboxes
    function updateActiveCheckbox(checkbox) {
        const row = checkbox.closest('tr');
        const activeCheckbox = row.querySelector('.permission-active');
        const checkboxes = Array.from(row.querySelectorAll('.permission-checkbox:not(.permission-active)'));
        
        const hasAnyChecked = checkboxes.some(cb => cb.checked);
        if (activeCheckbox) {
            activeCheckbox.checked = hasAnyChecked;
        }
        
        updateCheckAllCheckboxes();
    }
    
    function checkAllPermissions(type) {
        const checkAllCheckbox = document.getElementById(`checkAll${type.charAt(0).toUpperCase() + type.slice(1)}`);
        const isChecked = checkAllCheckbox.checked;
        
        // Get all visible rows (respecting current filter)
        const visibleRows = Array.from(document.querySelectorAll('.permission-row:not([style*="display: none"])'));
        
        visibleRows.forEach(row => {
            let checkbox;
            if (type === 'active') {
                checkbox = row.querySelector('.permission-active');
            } else {
                checkbox = row.querySelector(`.permission-${type}`);
            }
            
            if (checkbox) {
                checkbox.checked = isChecked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        
        updateCheckAllCheckboxes();
    }
    
    function updateCheckAllCheckboxes() {
        const visibleRows = Array.from(document.querySelectorAll('.permission-row:not([style*="display: none"])'));
        const types = ['active', 'create', 'update', 'delete', 'view', 'approve', 'download', 'print'];
        
        types.forEach(type => {
            const selector = type === 'active' ? '.permission-active' : `.permission-${type}`;
            const checkboxes = visibleRows.map(row => row.querySelector(selector)).filter(cb => cb);
            const checkAll = document.getElementById(`checkAll${type.charAt(0).toUpperCase() + type.slice(1)}`);
            
            if (checkAll && checkboxes.length > 0) {
                const checkedCount = checkboxes.filter(cb => cb.checked).length;
                checkAll.checked = checkedCount === checkboxes.length;
                checkAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
            }
        });
    }

    // Handle form submission - collect permissions
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('#editRoleForm');
        if (!form) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const checkboxStates = {};
            const allCheckboxes = Array.from(document.querySelectorAll('.permission-checkbox:not(.permission-active)'));
            
            allCheckboxes.forEach(function(checkbox) {
                const row = checkbox.closest('tr');
                if (!row) return;
                
                const module = row.getAttribute('data-module');
                const menuName = row.querySelector('td:first-child strong')?.textContent?.trim();
                const action = checkbox.className.split(' ').find(c => c.startsWith('permission-') && c !== 'permission-checkbox' && c !== 'permission-active')?.replace('permission-', '');
                const resourceKey = row.getAttribute('data-row-id') || row.getAttribute('data-menu').replace(/\s+/g, '-');
                
                // Construct specific key like marketing.surveys.create
                const permissionKey = `${module}.${resourceKey}.${action}`;
                
                checkboxStates[permissionKey] = {
                    checked: checkbox.checked,
                    module: module,
                    resource: resourceKey,
                    action: action,
                    menu_name: menuName,
                    existing_id: checkbox.value !== '1' ? checkbox.value : null
                };
            });
            
            
            // Enable JSON injection to rely on robust JSON submission (bypassing max_input_vars)
            const checkboxStatesInput = document.createElement('input');
            checkboxStatesInput.type = 'hidden';
            checkboxStatesInput.name = 'checkbox_states';
            checkboxStatesInput.value = JSON.stringify(checkboxStates);
            form.appendChild(checkboxStatesInput);
            
            form.submit();
        });
    });
</script>
@endsection
