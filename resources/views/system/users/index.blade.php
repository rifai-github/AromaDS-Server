@extends('layouts.app')

@section('title', 'Master User - System')
@section('breadcrumb', 'Home / System / Master User')

@section('content')
<style>
    /* Premium Pipeline-style Table */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 10px 10px;
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }
    
    .responsive-table {
        min-width: 1200px;
        width: 100%;
        border-collapse: collapse;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: normal; /* Changed from nowrap to allow wrapping */
        word-break: break-word;
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

    /* Modal Styles from Pipeline */
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
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        max-width: 90vw;
        max-height: 90vh;
        width: 800px;
        overflow: visible;
        position: relative;
        display: flex;
        flex-direction: column;
    }
    
    .modal-header {
        background: #214589;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 12px 12px 0 0;
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
    }

    .modal-body {
        padding: 20px;
        overflow-y: auto;
    }

    .modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: center;
        gap: 16px;
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

    /* Filter Row Styling - Tighten and Color Fix */
    .responsive-table thead tr:first-child th {
        height: 40px !important; /* Match top: 40px in app.blade.php */
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    .responsive-table thead tr:nth-child(2) th {
        background-color: #f9fafb !important;
        color: #4b5563 !important;
        padding: 4px 8px !important;
        border-bottom: 1px solid #e5e7eb !important;
    }
    
    .table-filter {
        height: 30px !important;
        font-size: 12px !important;
        padding: 4px 10px !important;
        background-color: white !important;
        border: 1px solid #d1d5db !important;
        border-radius: 4px !important;
        width: 100% !important;
        color: #374151 !important;
    }

    .table-filter::placeholder {
        color: #9ca3af !important;
        opacity: 1;
        font-size: 11px;
    }

    .modal-section {
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 18px;
        padding-bottom: 18px;
    }

    .modal-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .modal-section-title {
        color: #214589;
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .form-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .form-group {
        margin-bottom: 12px;
    }

    .form-label {
        color: #374151;
        display: block;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .form-label.required::after {
        color: #ef4444;
        content: ' *';
    }

    .form-input,
    .form-control {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        padding: 9px 11px;
        width: 100%;
    }

    .form-input:focus,
    .form-control:focus {
        border-color: #214589;
        box-shadow: 0 0 0 2px rgba(33, 69, 137, 0.12);
        outline: none;
    }

    .detail-grid {
        display: grid;
        gap: 10px 18px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .detail-item {
        border-bottom: 1px dashed #e5e7eb;
        padding-bottom: 8px;
    }

    .detail-label {
        color: #6b7280;
        font-size: 12px;
        font-weight: 600;
    }

    .detail-value {
        color: #111827;
        font-size: 14px;
        margin-top: 2px;
    }

    @media (max-width: 768px) {
        .form-grid,
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Pagination Specific Styles from Pipeline */
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
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Header from Pipeline -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4 border-bottom">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master User</h1>
            </div>
            <div class="flex flex-row justify-end items-center gap-2">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span>Add New User</span>
                </button>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container w-full" style="border-radius: 0 0 10px 10px; border: 1px solid #e5e7eb; border-top: none;">
            <table class="responsive-table" id="usersTable" style="border-collapse: collapse !important; width: 100%; table-layout: fixed;">
                <thead>
                    <tr style="height: 40px !important;">
                        <th class="text-center" style="width: 40px; background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0;">
                            <input type="checkbox" id="headerSelectAll">
                        </th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 140px;">NIK</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 200px;">Name</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 200px;">Email</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 150px;">Branch</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 150px;">Department</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 150px;">Position</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 150px;">Role</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 100px;">Status</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 100px;">Commission</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 120px;">Phone</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 100px;">Actions</th>
                    </tr>
                    <tr style="background-color: #f9fafb !important; height: 40px !important;">
                        <th class="text-center" style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 10px;">
                            <button onclick="resetFilters()" class="btn btn-secondary btn-sm" style="padding: 0; height: 26px; width: 26px; min-width: 26px; display: flex; align-items: center; justify-content: center; background: white; border: 1px solid #d1d5db;">
                                <i class="fas fa-undo" style="font-size: 10px; color: #6b7280;"></i>
                            </button>
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <input type="text" class="form-control form-control-sm table-filter" name="filter[nik]" value="{{ request('filter.nik') }}" placeholder="Filter..." style="height: 30px !important; font-size: 12px !important; padding: 0 8px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <input type="text" class="form-control form-control-sm table-filter" name="filter[name]" value="{{ request('filter.name') }}" placeholder="Filter..." style="height: 30px !important; font-size: 12px !important; padding: 0 8px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <input type="text" class="form-control form-control-sm table-filter" name="filter[email]" value="{{ request('filter.email') }}" placeholder="Filter..." style="height: 30px !important; font-size: 12px !important; padding: 0 8px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <select class="form-control form-control-sm table-filter" name="filter[branch_id]" onchange="applyFilters()" style="height: 30px !important; font-size: 11px !important; padding: 0 4px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                                <option value="">All Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('filter.branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <select class="form-control form-control-sm table-filter" name="filter[department_name]" onchange="applyFilters()" style="height: 30px !important; font-size: 11px !important; padding: 0 4px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                                <option value="">All Dept</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->name }}" {{ request('filter.department_name') == $dept->name ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <select class="form-control form-control-sm table-filter" name="filter[position_name]" onchange="applyFilters()" style="height: 30px !important; font-size: 11px !important; padding: 0 4px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                                <option value="">All Pos</option>
                                @foreach($positions as $pos)
                                    <option value="{{ $pos->option_name }}" {{ request('filter.position_name') == $pos->option_name ? 'selected' : '' }}>{{ $pos->option_name }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <select class="form-control form-control-sm table-filter" name="filter[roles]" onchange="applyFilters()" style="height: 30px !important; font-size: 11px !important; padding: 0 4px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                                <option value="">All Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ request('filter.roles') == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <select class="form-control form-control-sm table-filter" name="filter[is_active]" onchange="applyFilters()" style="height: 30px !important; font-size: 11px !important; padding: 0 4px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                                <option value="">All Status</option>
                                <option value="1" {{ request('filter.is_active', '1') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('filter.is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                             <select class="form-control form-control-sm table-filter" name="filter[is_commission_achiever]" onchange="applyFilters()" style="height: 30px !important; font-size: 11px !important; padding: 0 4px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                                <option value="">All Comms</option>
                                <option value="1" {{ request('filter.is_commission_achiever') === '1' ? 'selected' : '' }}>Ya</option>
                                <option value="0" {{ request('filter.is_commission_achiever') === '0' ? 'selected' : '' }}>Tidak</option>
                            </select>
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <input type="text" class="form-control form-control-sm table-filter" name="filter[phone]" value="{{ request('filter.phone') }}" placeholder="Filter..." style="height: 30px !important; font-size: 12px !important; padding: 0 8px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 10px; width: 100px;"></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($users ?? [] as $user)
                    <tr onclick="openViewModal({{ $user->id }})">
                        <td class="text-center" onclick="event.stopPropagation()">
                            <input type="checkbox" class="row-checkbox" value="{{ $user->id }}">
                        </td>
                        <td>{{ $user->nik ?? '-' }}</td>
                        <td class="font-weight-medium" style="color: #214589;">
                            {{ $user->salutation }} {{ $user->name ?? '-' }}
                        </td>
                        <td>{{ $user->email ?? '-' }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @if($user->assignedBranches && $user->assignedBranches->count() > 0)
                                    @foreach($user->assignedBranches as $branch)
                                        <span class="px-2 py-0.5 rounded text-[10px] {{ $branch->pivot->is_primary ? 'bg-blue-100 text-blue-700 font-bold' : 'bg-gray-100 text-gray-700' }} border">
                                            {{ $branch->name }}{{ $branch->pivot->is_primary ? ' ★' : '' }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted text-xs">No branch</span>
                                @endif
                            </div>
                        </td>
                        <td>{{ $user->department_name ?? '-' }}</td>
                        <td>{{ $user->position_name ?? '-' }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->roles as $role)
                                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded text-[10px] border border-indigo-100">{{ $role->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <span class="px-2 py-0.5 rounded text-[10px] {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} border">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            @if($user->is_commission_achiever)
                                <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-[10px] border border-blue-100">
                                    <i class="fas fa-check me-1"></i> Ya
                                </span>
                            @else
                                <span class="text-muted text-[10px]">Tidak</span>
                            @endif
                        </td>
                        <td>{{ $user->phone ?? '-' }}</td>
                        <td class="text-center" onclick="event.stopPropagation()">
                            <div class="flex gap-2 justify-center">
                                <button class="btn btn-secondary btn-sm" onclick="openEditModal({{ $user->id }})" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm text-danger" onclick="initiateDelete(event, {{ $user->id }})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center py-10 text-muted">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer Bar from Pipeline Style -->
        <div class="flex flex-row justify-center items-center w-full bg-white rounded-b-[10px] p-4 border-t">
            <div class="pagination-controls">
                @if($users->onFirstPage())
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @else
                    <a href="{{ $users->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @endif

                <div class="flex items-center gap-2">
                    @php
                        $start = max(1, $users->currentPage() - 2);
                        $end = min($users->lastPage(), $users->currentPage() + 2);
                    @endphp

                    @if($start > 1)
                        <a href="{{ $users->url(1) }}" class="page-number">1</a>
                        @if($start > 2)
                            <span class="text-sm text-gray-500">...</span>
                        @endif
                    @endif

                    @for($i = $start; $i <= $end; $i++)
                        @if($i == $users->currentPage())
                            <span class="page-number active">{{ $i }}</span>
                        @else
                            <a href="{{ $users->url($i) }}" class="page-number">{{ $i }}</a>
                        @endif
                    @endfor

                    @if($end < $users->lastPage())
                        @if($end < $users->lastPage() - 1)
                            <span class="text-sm text-gray-500">...</span>
                        @endif
                        <a href="{{ $users->url($users->lastPage()) }}" class="page-number">{{ $users->lastPage() }}</a>
                    @endif
                </div>

                @if($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif

                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]" onchange="window.location.href = this.value">
                        @for($i = 1; $i <= $users->lastPage(); $i++)
                            <option value="{{ $users->url($i) }}" {{ $i == $users->currentPage() ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $users->lastPage() }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modals from Pipeline Style --}}
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Detail User</h2>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Loaded via JS -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Loaded via JS -->
        </div>
    </div>
</div>

{{-- Scripts --}}
@push('scripts')
<script>
// Filter logic
function applyFilters() {
    const url = new URL(window.location.href);
    document.querySelectorAll('.table-filter').forEach(input => {
        if (input.value) url.searchParams.set(input.name, input.value);
        else url.searchParams.delete(input.name);
    });
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

function resetFilters() {
    window.location.href = window.location.pathname;
}

$(document).ready(function() {
    $('.table-filter').on('keypress', function(e) {
        if (e.key === 'Enter') applyFilters();
    });
    // For selects, they already have onchange="applyFilters()" but we add this for backup
    $('select.table-filter').on('change', function() {
        applyFilters();
    });
});

const userFormOptions = {
    branches: @json($branches->values()),
    departments: @json($departments->values()),
    roles: @json($roles->values()),
    banks: @json($banks->values()),
    salutations: @json($salutations->values()),
    positions: @json($positions->values()),
};

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function showNotification(type, message) {
    const old = document.querySelector('.user-toast-notification');
    if (old) old.remove();

    const toast = document.createElement('div');
    toast.className = 'user-toast-notification';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10050;
        max-width: 440px;
        padding: 14px 18px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.22);
        background: ${type === 'success' ? '#059669' : '#dc2626'};
        transform: translateX(110%);
        transition: transform .25s ease;
        white-space: pre-line;
    `;
    toast.innerHTML = `
        <div style="display:flex;gap:10px;align-items:flex-start;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}" style="margin-top:2px;"></i>
            <div>${escapeHtml(message)}</div>
        </div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.style.transform = 'translateX(0)', 20);
    setTimeout(() => {
        toast.style.transform = 'translateX(110%)';
        setTimeout(() => toast.remove(), 300);
    }, 4500);
}

function notifyAfterReload(type, message) {
    sessionStorage.setItem('systemUserNotification', JSON.stringify({ type, message }));
    window.location.reload();
}

function setModalSubmitState(isSubmitting, label = 'Simpan') {
    const submitButton = document.querySelector('#modalFooter button[form="userForm"][type="submit"]');
    if (!submitButton) return;

    if (!submitButton.dataset.originalLabel) {
        submitButton.dataset.originalLabel = submitButton.innerHTML;
    }

    submitButton.disabled = isSubmitting;
    submitButton.style.opacity = isSubmitting ? '0.7' : '1';
    submitButton.style.cursor = isSubmitting ? 'not-allowed' : 'pointer';
    submitButton.innerHTML = isSubmitting
        ? `<i class="fas fa-spinner fa-spin"></i> ${escapeHtml(label)}`
        : submitButton.dataset.originalLabel;
}

document.addEventListener('DOMContentLoaded', () => {
    const pending = sessionStorage.getItem('systemUserNotification');
    if (!pending) return;

    sessionStorage.removeItem('systemUserNotification');
    try {
        const notification = JSON.parse(pending);
        showNotification(notification.type || 'success', notification.message || 'Berhasil');
    } catch (error) {
        showNotification('success', 'Berhasil');
    }
});

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

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

function optionList(items, selectedValue = null, labelKey = 'name', valueKey = 'id') {
    return items.map(item => {
        const selected = String(selectedValue ?? '') === String(item[valueKey]) ? 'selected' : '';
        return `<option value="${escapeHtml(item[valueKey])}" ${selected}>${escapeHtml(item[labelKey])}</option>`;
    }).join('');
}

function selectedRoleIds(user) {
    if (!user || !Array.isArray(user.roles)) return [];
    return user.roles.map(role => String(typeof role === 'object' ? role.id : role));
}

function renderBranchPicker(user = null) {
    const assigned = Array.isArray(user?.assigned_branches) ? user.assigned_branches : [];
    const fallbackPrimary = user?.branch_id ? String(user.branch_id) : '';
    return userFormOptions.branches.map(branch => {
        const isAssigned = assigned.some(item => String(item.id) === String(branch.id));
        const isPrimary = assigned.some(item => String(item.id) === String(branch.id) && item.pivot && item.pivot.is_primary)
            || (!assigned.length && fallbackPrimary === String(branch.id));
        return `
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;padding:7px;border-radius:6px;background:#f9fafb;">
                <input type="checkbox" name="branch_ids[]" value="${branch.id}" ${isAssigned || isPrimary ? 'checked' : ''}>
                <span style="flex:1;">${escapeHtml(branch.name)}</span>
                <span style="font-size:12px;color:#6b7280;">
                    <input type="radio" name="primary_branch_id" value="${branch.id}" ${isPrimary ? 'checked' : ''}> Utama
                </span>
            </label>
        `;
    }).join('');
}

function renderUserForm(user = null) {
    const isEdit = !!user;
    const rolesSelected = selectedRoleIds(user);
    return `
        <form id="userForm" onsubmit="submitForm(event, ${isEdit ? user.id : 'null'})" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="modal-section">
                    <div class="modal-section-title">Informasi Personal</div>
                    <div class="form-group">
                        <label class="form-label required">NIK</label>
                        <input type="text" name="nik" class="form-input" required value="${escapeHtml(user?.nik)}" placeholder="Masukkan NIK">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Nama Lengkap</label>
                        <input type="text" name="name" class="form-input" required value="${escapeHtml(user?.name)}" placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Salutation</label>
                        <select name="salutation" class="form-input">
                            <option value="">Pilih Salutation</option>
                            ${userFormOptions.salutations.map(item => `<option value="${escapeHtml(item.option_name)}" ${user?.salutation === item.option_name ? 'selected' : ''}>${escapeHtml(item.option_name)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-input" required value="${escapeHtml(user?.email)}" placeholder="Masukkan email">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Username</label>
                        <input type="text" name="username" class="form-input" required value="${escapeHtml(user?.username)}" placeholder="Masukkan username">
                    </div>
                    <div class="form-group">
                        <label class="form-label ${isEdit ? '' : 'required'}">${isEdit ? 'Password Baru' : 'Password'}</label>
                        <input type="password" name="password" class="form-input" ${isEdit ? '' : 'required'} placeholder="${isEdit ? 'Kosongkan jika tidak diubah' : 'Masukkan password'}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-input" value="${escapeHtml(user?.phone)}" placeholder="Nomor telepon">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Handphone</label>
                        <input type="text" name="handphone_1" class="form-input" value="${escapeHtml(user?.handphone_1)}" placeholder="Nomor handphone">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kontak Darurat</label>
                        <input type="number" name="emergency_contact_number" class="form-input" value="${escapeHtml(user?.emergency_contact_number)}" placeholder="Nomor kontak darurat">
                    </div>
                </div>

                <div class="modal-section">
                    <div class="modal-section-title">Informasi Kerja</div>
                    <div class="form-group">
                        <label class="form-label">Branches</label>
                        <div style="max-height:150px;overflow-y:auto;border:1px solid #d1d5db;border-radius:6px;padding:10px;">
                            ${renderBranchPicker(user)}
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-input">
                            <option value="">Pilih Department</option>
                            ${optionList(userFormOptions.departments, user?.department_id)}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <select name="position_name" class="form-input">
                            <option value="">Pilih Position</option>
                            ${userFormOptions.positions.map(position => `<option value="${escapeHtml(position.option_name)}" ${user?.position_name === position.option_name ? 'selected' : ''}>${escapeHtml(position.option_name)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Roles</label>
                        <select name="roles[]" class="form-input" multiple required style="min-height:110px;">
                            ${userFormOptions.roles.map(role => `<option value="${role.id}" ${rolesSelected.includes(String(role.id)) ? 'selected' : ''}>${escapeHtml(role.name)}</option>`).join('')}
                        </select>
                        <small class="text-gray-500">Gunakan Ctrl/Cmd untuk memilih lebih dari satu role.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Join Date</label>
                        <input type="date" name="join_date" class="form-input" value="${(user?.join_date || '').toString().split('T')[0]}">
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title">Data Bank, BPJS, NPWP</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Bank</label>
                        <select name="bank_name" class="form-input">
                            <option value="">Pilih Bank</option>
                            ${userFormOptions.banks.map(bank => `<option value="${escapeHtml(bank.bank_name)}" ${user?.bank_name === bank.bank_name ? 'selected' : ''}>${escapeHtml(bank.bank_name)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" name="bank_account_number" class="form-input" value="${escapeHtml(user?.bank_account_number)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Atas Nama Rekening</label>
                        <input type="text" name="bank_account_holder" class="form-input" value="${escapeHtml(user?.bank_account_holder)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor BPJS</label>
                        <input type="text" name="bpjs_number" class="form-input" value="${escapeHtml(user?.bpjs_number)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal BPJS</label>
                        <input type="date" name="bpjs_date" class="form-input" value="${(user?.bpjs_date || '').toString().split('T')[0]}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor NPWP</label>
                        <input type="text" name="npwp_number" class="form-input" value="${escapeHtml(user?.npwp_number)}" placeholder="12.345.678.9-123.456">
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title">Upload File</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">File KTP</label>
                        <input type="file" name="ktp_file" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="form-group">
                        <label class="form-label">File NPWP</label>
                        <input type="file" name="npwp_file" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Foto</label>
                        <input type="file" name="photo_file" class="form-input" accept=".jpg,.jpeg,.png">
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title">Status</div>
                <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <input type="checkbox" name="is_active" value="1" ${user ? (user.is_active ? 'checked' : '') : 'checked'}> Aktif
                </label>
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="is_commission_achiever" value="1" ${user?.is_commission_achiever ? 'checked' : ''}> Commission Achiever
                </label>
            </div>
        </form>
    `;
}

function openCreateModal() {
    openModal('Tambah User Baru');
    document.getElementById('modalBody').innerHTML = renderUserForm();
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" form="userForm" class="btn btn-primary">Simpan</button>
    `;
}

function openEditModal(id) {
    openModal('Edit User');
    document.getElementById('modalBody').innerHTML = '<div class="text-center p-5"><i class="fas fa-sync fa-spin"></i> Memuat data...</div>';
    fetch(`/system/users/${id}/edit`, { headers: { 'Accept': 'application/json' } })
        .then(response => response.json())
        .then(response => {
            if (!response.success) throw new Error(response.message || 'Gagal memuat data user');
            document.getElementById('modalBody').innerHTML = renderUserForm(response.data);
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" form="userForm" class="btn btn-primary">Update</button>
            `;
        })
        .catch(error => {
            document.getElementById('modalBody').innerHTML = `<div class="text-red-600">${escapeHtml(error.message)}</div>`;
            document.getElementById('modalFooter').innerHTML = `<button class="btn btn-secondary" onclick="closeModal()">Tutup</button>`;
        });
}

function roleBadges(user) {
    if (!Array.isArray(user.roles) || user.roles.length === 0) return '-';
    return user.roles.map(role => `<span class="px-2 py-1 rounded bg-indigo-50 text-indigo-700 text-xs border">${escapeHtml(role.name || role)}</span>`).join(' ');
}

function fileLink(path, label) {
    if (!path) return '-';
    return `<a href="{{ url('storage') }}/${escapeHtml(path)}" target="_blank" class="text-blue-600 hover:underline">${label}</a>`;
}

function detailItem(label, value) {
    return `<div class="detail-item"><div class="detail-label">${label}</div><div class="detail-value">${value || '-'}</div></div>`;
}

function openViewModal(id) {
    openModal('Detail User');
    document.getElementById('modalBody').innerHTML = '<div class="text-center p-5"><i class="fas fa-sync fa-spin"></i> Memuat data...</div>';
    fetch(`/system/users/${id}`, { headers: { 'Accept': 'application/json' } })
        .then(response => response.json())
        .then(response => {
            if (!response.success) throw new Error(response.message || 'Gagal memuat detail user');
            const user = response.data;
            const branches = Array.isArray(user.assigned_branches) && user.assigned_branches.length
                ? user.assigned_branches.map(branch => `${escapeHtml(branch.name)}${branch.pivot?.is_primary ? ' (Utama)' : ''}`).join(', ')
                : escapeHtml(user.branch?.name || user.branch_name || '-');
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Informasi Personal</div>
                    <div class="detail-grid">
                        ${detailItem('NIK', escapeHtml(user.nik))}
                        ${detailItem('Nama', `${escapeHtml(user.salutation)} ${escapeHtml(user.name)}`)}
                        ${detailItem('Email', escapeHtml(user.email))}
                        ${detailItem('Username', escapeHtml(user.username))}
                        ${detailItem('Phone', escapeHtml(user.phone))}
                        ${detailItem('Handphone', escapeHtml(user.handphone_1))}
                        ${detailItem('Kontak Darurat', escapeHtml(user.emergency_contact_number))}
                    </div>
                </div>
                <div class="modal-section">
                    <div class="modal-section-title">Informasi Kerja</div>
                    <div class="detail-grid">
                        ${detailItem('Branches', branches)}
                        ${detailItem('Department', escapeHtml(user.department_name || user.department?.name))}
                        ${detailItem('Position', escapeHtml(user.position_name))}
                        ${detailItem('Roles', roleBadges(user))}
                        ${detailItem('Join Date', escapeHtml((user.join_date || '').toString().split('T')[0]))}
                        ${detailItem('Status', user.is_active ? 'Active' : 'Inactive')}
                        ${detailItem('Commission Achiever', user.is_commission_achiever ? 'Ya' : 'Tidak')}
                    </div>
                </div>
                <div class="modal-section">
                    <div class="modal-section-title">Data Bank, BPJS, NPWP</div>
                    <div class="detail-grid">
                        ${detailItem('Bank', escapeHtml(user.bank_name))}
                        ${detailItem('Nomor Rekening', escapeHtml(user.bank_account_number))}
                        ${detailItem('Atas Nama Rekening', escapeHtml(user.bank_account_holder))}
                        ${detailItem('Nomor BPJS', escapeHtml(user.bpjs_number))}
                        ${detailItem('Tanggal BPJS', escapeHtml((user.bpjs_date || '').toString().split('T')[0]))}
                        ${detailItem('Nomor NPWP', escapeHtml(user.npwp_number))}
                    </div>
                </div>
                <div class="modal-section">
                    <div class="modal-section-title">File Upload</div>
                    <div class="detail-grid">
                        ${detailItem('File KTP', fileLink(user.ktp_file_path, 'Lihat KTP'))}
                        ${detailItem('File NPWP', fileLink(user.npwp_file_path, 'Lihat NPWP'))}
                        ${detailItem('Foto', fileLink(user.photo_file_path, 'Lihat Foto'))}
                    </div>
                </div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <button class="btn btn-secondary" onclick="closeModal()">Tutup</button>
                <button class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
            `;
        })
        .catch(error => {
            document.getElementById('modalBody').innerHTML = `<div class="text-red-600">${escapeHtml(error.message)}</div>`;
            document.getElementById('modalFooter').innerHTML = `<button class="btn btn-secondary" onclick="closeModal()">Tutup</button>`;
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const selectedBranches = Array.from(form.querySelectorAll('input[name="branch_ids[]"]:checked'));
    const primaryBranch = form.querySelector('input[name="primary_branch_id"]:checked');

    if (selectedBranches.length > 0 && !primaryBranch) {
        formData.set('primary_branch_id', selectedBranches[0].value);
    }

    if (id) formData.append('_method', 'PUT');
    formData.set('is_active', formData.has('is_active') ? '1' : '0');
    formData.set('is_commission_achiever', formData.has('is_commission_achiever') ? '1' : '0');
    setModalSubmitState(true, id ? 'Mengupdate...' : 'Menyimpan...');

    fetch(id ? `/system/users/${id}` : '/system/users', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken()
        },
        body: formData
    })
    .then(async response => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            const errors = data.errors ? Object.values(data.errors).flat().join('\\n') : '';
            throw new Error(errors || data.message || 'Gagal menyimpan user');
        }
        return data;
    })
    .then(data => {
        notifyAfterReload('success', data.message || (id ? 'User berhasil diupdate' : 'User berhasil disimpan'));
    })
    .catch(error => {
        setModalSubmitState(false);
        showNotification('error', error.message);
    });
}

function initiateDelete(event, id) {
    if (event) event.stopPropagation();
    if (!confirm('Nonaktifkan user ini?')) return;

    fetch(`/system/users/${id}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken()
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success || data.status === 'success') {
            notifyAfterReload('success', data.message || 'User berhasil dinonaktifkan');
            return;
        }
        throw new Error(data.message || 'Gagal menonaktifkan user');
    })
    .catch(error => showNotification('error', error.message));
}

document.addEventListener('change', (event) => {
    if (event.target.matches('input[name="primary_branch_id"]')) {
        const checkbox = event.target.closest('label')?.querySelector('input[name="branch_ids[]"]');
        if (checkbox) checkbox.checked = true;
    }

    if (event.target.matches('input[name="branch_ids[]"]') && !event.target.checked) {
        const wrapper = event.target.closest('label');
        const radio = wrapper?.querySelector('input[name="primary_branch_id"]');
        if (radio?.checked) radio.checked = false;
    }
});
</script>
@endpush
@endsection
