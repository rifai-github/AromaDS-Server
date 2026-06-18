@extends('layouts.app')

@section('title', 'Master Role - System')
@section('breadcrumb', 'Home / System / Master Role')

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

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Delete Button Hover - Blue */
    .btn-secondary:hover {
        background-color: #214589 !important;
        color: white !important;
        border-color: #214589 !important;
    }

    /* Specific delete button styling */
    .btn-delete:hover {
        background-color: #214589 !important;
        color: white !important;
        border-color: #214589 !important;
    }

    /* Table Container */
    .table-container {
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border-radius: 0 0 10px 10px;
        position: relative;
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }

    /* Custom scrollbar */
    .table-container::-webkit-scrollbar {
        height: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Scroll indicator */
    .table-container::after {
        content: '← Scroll horizontally to see more →';
        position: absolute;
        bottom: -25px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 12px;
        color: #666;
        opacity: 0.7;
    }

    /* Responsive Table */
    .responsive-table {
        min-width: 1600px;
        table-layout: auto;
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
        height: auto;
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
        background-color: #214589;
        color: white;
        font-weight: 600;
        font-size: 13px;
        position: sticky;
        top: 0;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: visible;
        text-overflow: unset;
    }

    .filter-row th {
        background-color: #f9fafb !important;
        padding: 8px 4px !important;
    }

    .filter-input {
        width: 100%;
        padding: 6px 10px;
        font-size: 13px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        color: #374151;
        background-color: white;
        transition: all 0.2s ease;
    }

    .filter-input:focus {
        border-color: #214589;
        outline: none;
        box-shadow: 0 0 0 2px rgba(33, 69, 137, 0.1);
    }

    select.filter-input {
        cursor: pointer;
        padding-right: 24px;
    }

    .responsive-table td {
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
    }
    
    .responsive-table tbody {
        height: auto;
    }

    /* Column widths for better layout */
    .responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 50px; min-width: 50px; }
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 150px; min-width: 150px; }

    /* Pagination Specific Styles */
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: nowrap;
        white-space: nowrap;
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
    }

    .pagination-btn {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid #d1d5db;
        background-color: #f9fafb;
        color: #374151;
    }

    .pagination-btn:hover:not(:disabled) {
        background-color: #f3f4f6;
        border-color: #9ca3af;
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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
    
    /* Delete Confirmation Modal */
    .delete-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }

    .delete-modal-overlay.show {
        display: flex;
    }

    .delete-modal-container {
        background: #f0f9ff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    .delete-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }

    .delete-icon {
        width: 80px;
        height: 80px;
    }

    .delete-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }

    .delete-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    .delete-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }

    .btn-cancel {
        background-color: white;
        color: #1e40af;
        border: 2px solid #1e40af;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-cancel:hover {
        background-color: #f8fafc;
        border-color: #1e3a8a;
        color: #1e3a8a;
    }

    .btn-hide {
        background-color: #1e40af;
        color: white;
        border: 2px solid #1e40af;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-hide:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
    }

    /* Error Modal */
    .error-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 3000;
        align-items: center;
        justify-content: center;
    }
    
    .error-modal-overlay.show {
        display: flex;
    }

    .error-modal-container {
        background: #f0fdf4;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    /* Success Modal */
    .success-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 4000;
        align-items: center;
        justify-content: center;
    }

    .success-modal-overlay.show {
        display: flex;
    }

    .success-modal-container {
        background: #f0fdf4;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    /* Form Input Styling */
    input[type="date"], input[type="text"], select, textarea {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    input[type="date"]:focus, input[type="text"]:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .space-y-4 > * + * {
        margin-top: 1rem;
    }

    .space-y-6 > * + * {
        margin-top: 1.5rem;
    }

    /* Grid Layout for Modal */
    .grid {
        display: grid;
    }

    .grid-cols-1 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .md\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .gap-6 {
        gap: 1.5rem;
    }

    /* Modal Section Styles */
    .modal-section {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        background: #f9fafb;
    }

    .modal-section:last-child {
        margin-bottom: 0;
    }

    .modal-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 1px solid #d1d5db;
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
    
    /* Detail View Styles */
    .detail-item {
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .detail-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .detail-value {
        color: #6b7280;
        font-size: 14px;
        line-height: 1.5;
        margin-top: 4px;
        word-wrap: break-word;
    }
    
    /* Status Badge Styles */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .status-active {
        background-color: #d1fae5;
        color: #065f46;
    }
    
    .status-inactive {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .status-system {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .permissions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 8px;
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 12px;
    }

    .permission-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
    }

    .permission-checkbox {
        margin: 0;
    }

    .permission-label {
        font-size: 12px;
        color: #374151;
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
            min-width: 1600px;
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
            max-width: 100%;
            overflow-x: hidden;
        }
        
        .page-dropdown-container {
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Header responsive */
        .flex.flex-row.justify-between {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .flex.flex-row.justify-between > div:first-child {
            width: 100%;
        }
        
        .flex.flex-row.justify-between > div:last-child {
            width: 100%;
            justify-content: flex-start;
        }
    }

    /* Tablet and small screen responsive */
    @media (max-width: 1024px) and (min-width: 769px) {
        .flex-wrap {
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .flex-wrap > div {
            flex: 1 1 calc(50% - 0.5rem);
            min-width: 200px;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Master Role Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Role</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Role</span>
                    <span class="md:hidden">Add New</span>
                </button>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row justify-start items-center w-full">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center w-auto">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        <div class="flex flex-row justify-start items-center w-full px-2">
                            <p class="text-sm font-normal text-gray-700 w-auto ml-2 cursor-pointer" onclick="document.getElementById('selectAll').click()">Select all</p>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Button -->
                <button class="btn btn-secondary btn-sm ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
            
        </div>
        
        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="name">Role Name</th>
                        <th data-column="description">Description</th>
                        <th data-no-filter>Permissions</th>
                        <th data-column="is_active">Status</th>
                        <th data-column="system_reserved">Type</th>
                        <th data-no-filter>User Count</th>
                        <th data-column="created_by">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updated_by">Updated By</th>
                        <th data-column="updated_at" data-type="date">Updated At</th>
                        <th data-no-filter>Action</th>
                    </tr>
                    <!-- Filter Row -->
                    <tr class="filter-row">
                        <th></th>
                        <th>
                            <input type="text" name="filter[name]" value="{{ request('filter.name') }}" class="filter-input" placeholder="Search name...">
                        </th>
                        <th>
                            <input type="text" name="filter[description]" value="{{ request('filter.description') }}" class="filter-input" placeholder="Search desc...">
                        </th>
                        <th></th>
                        <th>
                            <select name="filter[is_active]" class="filter-input">
                                <option value="">All</option>
                                <option value="active" {{ request('filter.is_active') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('filter.is_active') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </th>
                        <th>
                            <select name="filter[system_reserved]" class="filter-input">
                                <option value="">All</option>
                                <option value="1" {{ request('filter.system_reserved') == '1' ? 'selected' : '' }}>System</option>
                                <option value="0" {{ request('filter.system_reserved') == '0' ? 'selected' : '' }}>User</option>
                            </select>
                        </th>
                        <th></th>
                        <th>
                            <input type="text" name="filter[created_by]" value="{{ request('filter.created_by') }}" class="filter-input" placeholder="Creator...">
                        </th>
                        <th></th>
                        <th>
                            <input type="text" name="filter[updated_by]" value="{{ request('filter.updated_by') }}" class="filter-input" placeholder="Updator...">
                        </th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($roles ?? [] as $role)
                    <tr data-id="{{ $role->id }}" onclick="window.location.href='{{ route('system.roles.show', $role->id) }}'" style="cursor: pointer;">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $role->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $role->name ?? '-' }}</td>
                        <td>{{ $role->description ?? '-' }}</td>
                        <td>{{ count($role->permissions ?? []) }} permissions</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $role->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $role->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            @if($role->system_reserved)
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">System</span>
                            @else
                                <span class="text-gray-500">User</span>
                            @endif
                        </td>
                        <td>{{ $role->users_count ?? 0 }}</td>
                        <td>{{ $role->createdBy->name ?? 'N/A' }}</td>
                        <td>
                            @if($role->created_at)
                                {{ \Carbon\Carbon::parse($role->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($role->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $role->updatedBy->name ?? 'N/A' }}</td>
                        <td>
                            @if($role->updated_at)
                                {{ \Carbon\Carbon::parse($role->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($role->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center ActionColumn">
                                <!-- Edit Button -->
                                <a href="{{ route('system.roles.edit', $role->id) }}" class="p-2 text-[#214589] hover:text-[#1e3a8a] transition-colors" title="Edit Role" style="color: #214589;" onclick="event.stopPropagation()">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- Duplicate Button -->
                                <form action="{{ route('system.roles.duplicate', $role->id) }}" method="POST" class="inline-block" onclick="event.stopPropagation()" onsubmit="return submitDuplicateRole(event, this)">
                                    @csrf
                                    <button type="submit" class="p-2 text-[#214589] hover:text-[#1e3a8a] transition-colors" title="Duplicate Role" style="color: #214589;">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No roles found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($roles->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $roles->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Role</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content will be loaded here -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Modal footer content will be loaded here -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Hide Role</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this role? This action can be undone later.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Yes, Hide</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Hmm... Something Went Wrong</h3>
        <p class="delete-modal-description" id="errorMessage">We couldn't hide the role. Please try again.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeErrorModal()">Close</button>
            <button class="btn btn-hide" onclick="retryDelete()">Try Again</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">All Set!</h3>
        <p class="delete-modal-description" id="successMessage">The role has been successfully hidden.</p>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;

// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    document.getElementById('headerSelectAll').checked = this.checked;
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

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu role yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Modal functions
function openModal(title) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
    document.getElementById('modalBody').innerHTML = '';
    document.getElementById('modalFooter').innerHTML = '';
}

// CRUD Modal functions
function openCreateModal() {
    openModal('Tambah Role Baru');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="createForm" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Informasi Role</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Nama Role *</label>
                        <input type="text" name="name" class="form-input" placeholder="Masukkan nama role" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-input" rows="3" placeholder="Masukkan deskripsi role"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Konfigurasi Role</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Cadangan Sistem</label>
                        <select name="system_reserved" class="form-input">
                            <option value="0">Dibuat User</option>
                            <option value="1">Cadangan Sistem</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-input">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    // Add modal footer
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" form="createForm" class="btn btn-primary">Simpan Role</button>
    `;
}

function openViewModal(id) {
    openModal('View Role');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/system/roles/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Role Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Role Name</label>
                            <p class="detail-value">${data.data.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Description</label>
                            <p class="detail-value">${data.data.description || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">System Reserved</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full ${data.data.system_reserved ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}">
                                    ${data.data.system_reserved ? 'System' : 'User'}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value">
                                <span class="px-2 py-1 text-xs rounded-full ${data.data.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${data.data.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">User Count</label>
                            <p class="detail-value">${data.data.users_count || 0}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Created By</label>
                            <p class="detail-value">${data.data.created_by ? data.data.created_by.name : 'System'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Created At</label>
                            <p class="detail-value">${data.data.created_at ? new Date(data.data.created_at).toLocaleString('id-ID') : '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Permissions</div>
                    <div class="permissions-grid">
                        ${data.data.permissions ? data.data.permissions.map(p => `
                            <div class="permission-item">
                                <span class="permission-label">${p.name}</span>
                            </div>
                        `).join('') : '<p class="text-gray-500">No permissions assigned</p>'}
                    </div>
                </div>
            `;
        
        // Add modal footer for view modal
            document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit Role</button>
        `;
        })
        .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
        `;
        });
}

function openEditModal(id) {
    openModal('Edit Role');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/system/roles/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <form id="editForm" onsubmit="submitForm(event, ${id})">
                    <input type="hidden" name="id" value="${data.data.id}">
                    <div class="modal-section">
                        <div class="modal-section-title">Role Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Role Name *</label>
                                <input type="text" name="name" class="form-input" value="${data.data.name || ''}" placeholder="Enter role name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-input" rows="3" placeholder="Enter role description">${data.data.description || ''}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Role Configuration</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">System Reserved</label>
                                <select name="system_reserved" class="form-input">
                                    <option value="0" ${!data.data.system_reserved ? 'selected' : ''}>User Created</option>
                                    <option value="1" ${data.data.system_reserved ? 'selected' : ''}>System Reserved</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-input">
                                    <option value="1" ${data.data.is_active ? 'selected' : ''}>Active</option>
                                    <option value="0" ${!data.data.is_active ? 'selected' : ''}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Permissions</div>
                        <div class="permissions-grid" id="permissionsGrid">
                            <!-- Permissions will be loaded here -->
                        </div>
                    </div>
                </form>
            `;
            
            // Load permissions with current selections
            loadPermissions(data.data.permissions || []);
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="editForm" class="btn btn-primary">Update Role</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            `;
        });
}

function loadPermissions(selectedPermissions = []) {
    const permissionsGrid = document.getElementById('permissionsGrid');
    if (!permissionsGrid) return;
    
    // Get permissions from Laravel
    const permissions = @json($permissions ?? []);
    
    let html = '';
    permissions.forEach(permission => {
        const isChecked = selectedPermissions.some(p => p.id === permission.id);
        html += `
            <div class="permission-item">
                <input type="checkbox" name="permissions[]" value="${permission.id}" 
                       class="permission-checkbox" ${isChecked ? 'checked' : ''}>
                <label class="permission-label">${permission.name}</label>
            </div>
        `;
    });
    
    permissionsGrid.innerHTML = html;
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Get form values directly from the form elements
    const name = form.querySelector('input[name="name"]')?.value;
    const description = form.querySelector('textarea[name="description"]')?.value || null;
    const systemReserved = form.querySelector('select[name="system_reserved"]')?.value || '0';
    const isActive = form.querySelector('select[name="is_active"]')?.value || '1';
    
    // Validate required fields
    if (!name || name.trim() === '') {
        showWarningDialog('Nama role wajib diisi.');
        return;
    }
    
    // Create JSON data instead of FormData
    const submitData = {
        name: name.trim(),
        description: description,
        system_reserved: systemReserved,
        is_active: isActive,
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    };
    
    // Only include permissions if editing (not creating)
    if (id) {
        const permissionCheckboxes = form.querySelectorAll('input[name="permissions[]"]:checked');
        const permissions = Array.from(permissionCheckboxes).map(cb => cb.value);
        submitData.permissions = permissions;
    }
    
    const url = id ? `/system/roles/${id}` : '/system/roles';
    const method = id ? 'PUT' : 'POST';
    
    if (id) {
        submitData._method = 'PUT';
    }
    
    fetch(url, {
        method: 'POST', // Always use POST with _method override
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify(submitData)
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 422) {
                return response.json().then(data => {
                    console.error('Validation error:', data);
                    throw new Error('Validation failed: ' + JSON.stringify(data));
                });
            }
            return response.text().then(text => {
                throw new Error('Server error: ' + text);
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.status === 'success') {
            closeModal();
            if (id) {
                // Editing: reload page
                showSuccessDialog('Berhasil', 'Role berhasil diperbarui.');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                // Creating: redirect to edit page
                showSuccessDialog('Berhasil', 'Role berhasil dibuat.');
                if (result.redirect) {
                    window.location.href = result.redirect;
                } else if (result.data && result.data.id) {
                    window.location.href = `/system/roles/${result.data.id}/edit`;
                } else {
                    location.reload();
                }
            }
        } else {
            showErrorDialog('Gagal', result.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', error.message);
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this role? This action can be undone later.'
        : `Are you sure you want to hide ${count} roles? This action can be undone later.`;
    
    document.getElementById('deleteMessage').textContent = message;
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function confirmDelete() {
    closeDeleteModal();
    
    fetch('/system/roles/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessModal(result.count);
        } else {
            showErrorModal(result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Network error occurred');
    });
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'The role has been successfully hidden.'
        : `${count} roles have been successfully hidden.`;
    
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Auto close after 3 seconds
    successModalTimer = setTimeout(() => {
        closeSuccessModal();
        location.reload();
    }, 3000);
}

function closeSuccessModal() {
    if (successModalTimer) {
        clearTimeout(successModalTimer);
        successModalTimer = null;
    }
    document.getElementById('successModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Error Modal functions
function showErrorModal(message) {
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t hide the role. Please try again.';
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retryDelete() {
    closeErrorModal();
    confirmDelete();
}

// Event listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
        closeErrorModal();
        closeSuccessModal();
    }
});

// Click outside to close modals
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('deleteModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

document.getElementById('errorModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeErrorModal();
    }
});

document.getElementById('successModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuccessModal();
        location.reload();
    }
});

// Add CSS for loading spinner
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Check if edit parameter exists in URL and open edit modal
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');
    if (editId) {
        // Remove edit parameter from URL
        window.history.replaceState({}, document.title, window.location.pathname);
        // Open edit modal
        setTimeout(() => {
            openEditModal(editId);
        }, 100);
    }

    // Initialize filter event listeners
    document.querySelectorAll('.filter-input').forEach(input => {
        input.addEventListener('change', applyFilters);
        if (input.tagName === 'INPUT') {
            input.addEventListener('keyup', e => {
                if (e.key === 'Enter') applyFilters();
            });
        }
    });
});

function applyFilters() {
    const filters = {};
    document.querySelectorAll('.filter-input').forEach(input => {
        if (input.value !== '') {
            filters[input.name] = input.value;
        }
    });

    const url = new URL(window.location.href);
    
    // Clear existing filters from URL
    const paramsToDelete = [];
    for (const key of url.searchParams.keys()) {
        if (key.startsWith('filter[')) {
            paramsToDelete.push(key);
        }
    }
    paramsToDelete.forEach(key => url.searchParams.delete(key));

    // Add new filters
    Object.entries(filters).forEach(([name, value]) => {
        url.searchParams.set(name, value);
    });

    // Reset pagination to page 1 if filters changed
    if (Object.keys(filters).length > 0) {
        url.searchParams.set('page', 1);
    }

    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location.href);
    const paramsToDelete = [];
    for (const key of url.searchParams.keys()) {
        if (key.startsWith('filter[')) {
            paramsToDelete.push(key);
        }
    }
    paramsToDelete.forEach(key => url.searchParams.delete(key));
    window.location.href = url.origin + url.pathname;
}

function submitDuplicateRole(event, form) {
    event.preventDefault();

    showConfirmDialog(
        'Duplikasi Role?',
        'Apakah Anda yakin ingin menduplikasi role ini?',
        'Ya, duplikasi',
        'Batal'
    ).then((confirmed) => {
        if (confirmed) {
            form.submit();
        }
    });

    return false;
}
</script>
@endsection
