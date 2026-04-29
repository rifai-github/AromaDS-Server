@extends('layouts.app')

@section('title', 'Detail Role - System')
@section('breadcrumb', 'Home / System / Master Role / Detail Role')

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
</style>

<div class="role-info-header">
    <h1>{{ $role->name }}</h1>
    <div class="role-info-meta">
        <div class="role-info-meta-item">
            <i class="fas fa-info-circle"></i>
            <span>{{ $role->description ?? 'No description' }}</span>
        </div>
        <div class="role-info-meta-item">
            <i class="fas fa-users"></i>
            <span>{{ $role->users->count() }} User(s)</span>
        </div>
        <div class="role-info-meta-item">
            <i class="fas fa-shield-alt"></i>
            <span>{{ $role->rolePermissions->count() }} Permission(s)</span>
        </div>
        <div class="role-info-meta-item">
            @if($role->is_active)
                <span class="badge badge-success">Active</span>
            @else
                <span class="badge badge-secondary">Inactive</span>
            @endif
        </div>
        @if($role->system_reserved)
        <div class="role-info-meta-item">
            <i class="fas fa-lock"></i>
            <span>System Reserved</span>
        </div>
        @endif
    </div>
    <div style="margin-top: 15px;">
        <a href="{{ route('system.roles.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
        <a href="{{ route('system.roles.edit', $role->id) }}" class="btn btn-primary" style="margin-left: 10px;">
            <i class="fas fa-edit"></i> Edit Role
        </a>
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
                        <th>Aktif</th>
                        <th>Buat</th>
                        <th>Ubah</th>
                        <th>Hapus</th>
                        <th>Lihat</th>
                        <th>Approve</th>
                        <th>Download</th>
                        <th>Print</th>
                    </tr>
                </thead>
                <tbody id="permissionTableBody">
                    {{-- Logic moved to Controller --}}
                    @foreach($menuItems as $module => $items)
                        @foreach($items as $resourceKey => $menuData)
                        <tr class="permission-row" data-module="{{ strtolower($module) }}" data-menu="{{ strtolower($menuData['name']) }}">
                            <td>
                                <strong>{{ $menuData['name'] }}</strong>
                                @if($menuData['permissions']['active'])
                                    <span class="badge badge-success" style="margin-left: 8px; font-size: 10px;">Active</span>
                                @endif
                            </td>
                            <td>
                                <input type="checkbox" class="permission-checkbox" 
                                       {{ $menuData['permissions']['active'] ? 'checked' : '' }} 
                                       disabled>
                            </td>
                            <td>
                                <input type="checkbox" class="permission-checkbox" 
                                       {{ $menuData['permissions']['create'] ? 'checked' : '' }} 
                                       disabled>
                            </td>
                            <td>
                                <input type="checkbox" class="permission-checkbox" 
                                       {{ $menuData['permissions']['update'] ? 'checked' : '' }} 
                                       disabled>
                            </td>
                            <td>
                                <input type="checkbox" class="permission-checkbox" 
                                       {{ $menuData['permissions']['delete'] ? 'checked' : '' }} 
                                       disabled>
                            </td>
                            <td>
                                <input type="checkbox" class="permission-checkbox" 
                                       {{ $menuData['permissions']['view'] ? 'checked' : '' }} 
                                       disabled>
                            </td>
                            <td>
                                <input type="checkbox" class="permission-checkbox" 
                                       {{ $menuData['permissions']['approve'] ?? false ? 'checked' : '' }} 
                                       disabled>
                            </td>
                            <td>
                                <input type="checkbox" class="permission-checkbox" 
                                       {{ $menuData['permissions']['download'] ?? false ? 'checked' : '' }} 
                                       disabled>
                            </td>
                            <td>
                                <input type="checkbox" class="permission-checkbox" 
                                       {{ $menuData['permissions']['print'] ?? false ? 'checked' : '' }} 
                                       disabled>
                            </td>
                        </tr>
                        @endforeach
                    @endforeach
                    
                    @if(empty($menuItems))
                    <tr>
                        <td colspan="8" class="empty-state">
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

<script>
    let currentModule = document.querySelector('.module-item.active')?.getAttribute('data-module') || 'marketing';

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
    }
    // Run filter on load to default to Marketing
    document.addEventListener('DOMContentLoaded', function() {
        filterPermissions();
    });
</script>
@endsection

