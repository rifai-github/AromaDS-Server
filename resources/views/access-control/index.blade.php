@extends('layouts.app')

@section('title', 'Hirarki Data - System')
@section('breadcrumb', 'Home / System / Hirarki Data')

@section('content')
<style>
    /* Global overflow control */
    html, body {
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
        justify-content: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-primary { background-color: #214589; color: white; }
    .btn-primary:hover { background-color: #1e3a8a; }
    .btn-secondary { background-color: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }
    .btn-secondary:hover { background-color: #e5e7eb; color: #4b5563; }
    .btn-warning { background-color: #f59e0b; color: white; }
    .btn-warning:hover { background-color: #d97706; }
    .btn-info { background-color: #0ea5e9; color: white; }
    .btn-info:hover { background-color: #0284c7; }
    .btn-danger { background-color: #ef4444; color: white; }
    .btn-danger:hover { background-color: #dc2626; }
    .btn-success { background-color: #10b981; color: white; }
    .btn-success:hover { background-color: #059669; }
    .btn-sm { padding: 6px 12px; font-size: 12px; }

    /* Table Styles */
    .table-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .table-header {
        background: #f1f5f9;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .table-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .table-wrapper {
        position: relative;
        max-height: calc(100vh - 250px);
        overflow: auto;
        border: 1px solid #e2e8f0;
        background: white;
    }

    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 14px;
    }

    /* Column Headers Sticky */
    .data-table thead tr:nth-child(1) th {
        background-color: #214589 !important;
        color: white !important;
        font-weight: 600;
        padding: 12px 16px;
        position: sticky;
        top: 0;
        z-index: 30;
        border-bottom: 1px solid #1e4bb5;
        white-space: nowrap;
    }

    /* Filter Row Sticky */
    .data-table thead tr.filters th {
        background-color: #f8f9fa !important;
        padding: 6px 10px !important;
        position: sticky;
        top: 45px; /* Adjust based on primary header height */
        z-index: 20;
        border-bottom: 2px solid #e2e8f0;
    }

    /* Force hide the globally injected filter row if it appears */
    .filter-row { display: none !important; }

    /* Sticky Columns (Horizontal Scroll) */
    .data-table .sticky-col-1 {
        position: sticky;
        left: 0;
        background-color: white;
        z-index: 10;
        width: 300px;
        min-width: 300px;
        max-width: 300px;
        border-right: 1px solid #e2e8f0;
    }

    .data-table .sticky-col-2 {
        position: sticky;
        left: 300px;
        background-color: white;
        z-index: 10;
        width: 150px;
        min-width: 150px;
        max-width: 150px;
        border-right: 1px solid #e2e8f0;
    }

    /* Ensure headers stay on top of body sticky columns */
    .data-table thead tr th.sticky-col-1,
    .data-table thead tr th.sticky-col-2 {
        z-index: 40;
        background-color: #214589 !important;
    }
    
    .data-table thead tr.filters th.sticky-col-1,
    .data-table thead tr.filters th.sticky-col-2 {
        z-index: 40;
        background-color: #f8f9fa !important;
    }

    /* Fix background for sticky columns */
    .data-table tbody tr:hover .sticky-col-1,
    .data-table tbody tr:hover .sticky-col-2 { background-color: #f8fafc; }
    .data-table tbody tr:nth-child(even) .sticky-col-1,
    .data-table tbody tr:nth-child(even) .sticky-col-2 { background-color: #f9fafb; }
    .data-table tbody tr:nth-child(even):hover .sticky-col-1,
    .data-table tbody tr:nth-child(even):hover .sticky-col-2 { background-color: #f1f5f9; }

    /* DataTables Pagination & Controls (Warehouse Style Match) */
    .dataTables_wrapper { padding: 20px 0; }
    .dataTables_wrapper .dataTables_length { margin-bottom: 16px; float: left; }
    .dataTables_wrapper .dataTables_filter { margin-bottom: 16px; float: right; }
    .dataTables_wrapper .dataTables_info { padding-top: 10px; color: #6b7280; font-size: 13px; float: left; }
    .dataTables_wrapper .dataTables_paginate { padding-top: 10px; float: right; display: flex; align-items: center; }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 6px 12px !important;
        margin-left: 4px !important;
        border-radius: 6px !important;
        border: 1px solid #d1d5db !important;
        background: white !important;
        color: #374151 !important;
        font-size: 13px !important;
        cursor: pointer !important;
        text-decoration: none;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #f3f4f6 !important; color: #214589 !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #214589 !important; color: white !important; border-color: #214589 !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled { opacity: 0.5; cursor: not-allowed; }
    
    .dataTables_filter input {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 6px 12px;
        margin-left: 8px;
        width: 200px;
    }
    
    .data-table thead tr.filters input {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        font-size: 13px;
        background-color: white;
    }

    .data-table td { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }

    /* Badge & Card Styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        line-height: 1;
        white-space: nowrap;
        margin: 2px;
    }
    .badge-info { background-color: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .badge-warning { background-color: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
    .badge-secondary { background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .role-grid { display: flex; flex-wrap: wrap; gap: 6px; }
    .role-grid .badge { max-width: 100%; white-space: normal; text-align: left; align-items: flex-start; }

    .user-card { display: flex; align-items: center; gap: 12px; }
    .user-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        background: linear-gradient(135deg, #214589, #0ea5e9);
        color: white; display: flex; align-items: center; justify-content: center;
        font-weight: 600; font-size: 16px; flex-shrink: 0;
    }
    .user-name { font-weight: 600; color: #c52108 !important; margin-bottom: 2px; }
    .user-details { font-size: 12px; color: #64748b; }

    /* Modal & Form Styles */
    .h-modal {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5); z-index: 1000; align-items: center; justify-content: center;
    }
    .h-modal.show { display: flex; }
    .h-modal-dialog {
        background: white; border-radius: 8px; width: 90%; max-width: 900px;
        max-height: 85vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }
    .h-modal-header {
        background: #f1f5f9; padding: 16px 20px; border-bottom: 1px solid #e2e8f0;
        display: flex; justify-content: space-between; align-items: center;
    }
    .h-modal-body { padding: 20px; max-height: 65vh; overflow-y: auto; }
    .h-modal-footer {
        background: #f8fafc; padding: 16px 20px; border-top: 1px solid #e2e8f0;
        display: flex; justify-content: flex-end; gap: 10px;
    }

    .form-group { display: flex; flex-direction: column; margin-bottom: 15px; }
    .form-label { font-size: 13px; font-weight: 500; color: #475569; margin-bottom: 4px; }
    .form-control {
        padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;
        font-size: 14px; width: 100%; background-color: white;
    }
    .form-row {
        display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px;
        margin-bottom: 12px; align-items: flex-end; padding: 12px;
        background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0;
    }

    @media (max-width: 768px) {
        .h-modal-dialog { width: 95%; }
        .form-row { grid-template-columns: 1fr; }
        .user-card { flex-direction: column; text-align: center; }
    }
</style>

<div class="container-fluid">
    <div class="table-container">
        <div class="table-header">
            <h3 class="table-title">
                <i class="fas fa-user-shield"></i>
                Manajemen Hirarki Data
            </h3>
            <div class="table-actions">
                <span class="text-sm text-gray-600">
                    Mengatur hierarki data untuk {{ $users->count() }} users
                </span>
            </div>
        </div>

        <div class="table-wrapper">
            @if($users->count() > 0)
                <table class="data-table" id="accessControlTable">
                    <thead>
                        <tr>
                            <th class="sticky-col-1">User</th>
                            <th class="sticky-col-2">Role</th>
                            <th data-no-filter>Access Levels</th>
                            <th data-no-filter>Login Restrictions</th>
                            <th data-no-filter>Created At</th>
                            <th data-no-filter>Created By</th>
                            <th data-no-filter>Updated At</th>
                            <th data-no-filter>Updated By</th>
                            <th data-no-filter>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td class="sticky-col-1">
                                <div class="user-card">
                                    <div class="user-avatar">{{ substr($user->name, 0, 1) }}</div>
                                    <div class="user-info">
                                        <div class="user-name">{{ $user->name }}</div>
                                        <div class="user-details">
                                            <strong>{{ $user->username }}</strong>
                                            @if($user->email)<br>{{ $user->email }}@endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="sticky-col-2">
                                @php
                                    $userRoles = $userRolesMap[$user->id] ?? [];
                                @endphp
                                <div class="role-grid">
                                    @forelse($userRoles as $roleName)
                                        <span class="badge badge-info">
                                            <i class="fas fa-user-tag"></i>
                                            {{ $roleName }}
                                        </span>
                                    @empty
                                        <span class="badge badge-secondary">No Role</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <div class="access-grid">
                                    @forelse($user->accessLevels as $level)
                                        @php
                                            $details = $accessLevelDetailsMap[$user->id][$level->id] ?? '';
                                        @endphp
                                        <span class="badge badge-info" title="{{ json_encode($level->access_config) }}">
                                            <i class="fas fa-key"></i> {{ ucfirst($level->access_type) }}{{ $details }}
                                        </span>
                                    @empty
                                        <span class="badge badge-secondary">No Access Levels</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <div class="restriction-grid">
                                    @forelse($user->loginRestrictions as $restriction)
                                        @if($restriction->is_active)
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> Restricted
                                            </span>
                                        @endif
                                    @empty
                                        <span class="badge badge-secondary">No Restrictions</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>{{ $user->created_at ? $user->created_at->format('d/M/Y H:i') : '-' }}</td>
                            <td>{{ $user->createdBy->name ?? '-' }}</td>
                            <td>{{ $user->updated_at ? $user->updated_at->format('d/M/Y H:i') : '-' }}</td>
                            <td>{{ $user->updatedBy->name ?? '-' }}</td>
                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                    <button class="btn btn-primary btn-sm edit-access-levels" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" data-access-levels="{{ $user->accessLevels->toJson() }}">
                                        <i class="fas fa-user-shield"></i> Access
                                    </button>
                                    <button class="btn btn-warning btn-sm edit-login-restrictions" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" data-login-restrictions="{{ $user->loginRestrictions->toJson() }}">
                                        <i class="fas fa-clock"></i> Restrictions
                                    </button>
                                    <button class="btn btn-info btn-sm view-summary" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}">
                                        <i class="fas fa-info-circle"></i> Summary
                                    </button>
                                    
                                    @php
                                        $requiresMultiLogin = $user->requiresMultiLogin();
                                        $isAlwaysAllowedScreenshot = $user->isAlwaysAllowedScreenshot();
                                        $screenshotAllowed = $user->canTakeScreenshot();
                                    @endphp
                                    
                                    <button class="btn btn-{{ $user->multi_login ? 'info' : 'secondary' }} btn-sm toggle-multi-login {{ $requiresMultiLogin ? 'disabled' : '' }}" 
                                            data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" data-multi-login="{{ $user->multi_login ? '1' : '0' }}"
                                            {{ $requiresMultiLogin ? 'disabled' : '' }}>
                                        <i class="fas fa-{{ $user->multi_login ? 'users' : 'user' }}"></i> {{ $user->multi_login ? 'Multi' : 'Single' }}
                                    </button>
                                    
                                    <button class="btn btn-{{ $user->is_frozen ? 'success' : 'danger' }} btn-sm toggle-freeze" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" data-is-frozen="{{ $user->is_frozen ? '1' : '0' }}">
                                        <i class="fas fa-{{ $user->is_frozen ? 'unlock' : 'lock' }}"></i> {{ $user->is_frozen ? 'Unfreeze' : 'Freeze' }}
                                    </button>
                                    
                                    <button class="btn btn-{{ $screenshotAllowed ? 'success' : 'warning' }} btn-sm toggle-screenshot {{ $isAlwaysAllowedScreenshot ? 'disabled' : '' }}" 
                                            data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" data-screenshot-allowed="{{ $screenshotAllowed ? '1' : '0' }}"
                                            {{ $isAlwaysAllowedScreenshot ? 'disabled' : '' }}>
                                        <i class="fas fa-{{ $screenshotAllowed ? 'camera' : 'ban' }}"></i> {{ $screenshotAllowed ? 'Allow' : 'Disallow' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div style="text-align: center; padding: 40px; color: #64748b;">No Users Found</div>
            @endif
        </div>
    </div>
</div>

<!-- Modals -->
<div class="h-modal" id="accessLevelsModal">
    <div class="h-modal-dialog">
        <form id="accessLevelsForm" method="POST">
            @csrf
            <div class="h-modal-header">
                <h5 class="h-modal-title"><i class="fas fa-user-shield"></i> Set Access for <span id="accessUserDisplayName"></span></h5>
                <button type="button" class="btn btn-sm" onclick="closeModal('accessLevelsModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="h-modal-body">
                <div class="form-section">
                    <div class="form-section-title">Konfigurasi Tingkat Akses</div>
                    <div id="accessLevelsContainer"></div>
                    <button type="button" class="btn btn-secondary btn-sm" id="addAccessLevelField"><i class="fas fa-plus"></i> Tambah</button>
                </div>
            </div>
            <div class="h-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('accessLevelsModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="h-modal" id="loginRestrictionsModal">
    <div class="h-modal-dialog">
        <form id="loginRestrictionsForm" method="POST">
            @csrf
            <div class="h-modal-header">
                <h5 class="h-modal-title"><i class="fas fa-clock"></i> Restrictions for <span id="restrictionsUserDisplayName"></span></h5>
                <button type="button" class="btn btn-sm" onclick="closeModal('loginRestrictionsModal')"><i class="fas fa-times"></i></button>
            </div>
            <div class="h-modal-body">
                <div id="loginRestrictionsContainer"></div>
                <button type="button" class="btn btn-secondary btn-sm" id="addLoginRestrictionField"><i class="fas fa-plus"></i> Add</button>
            </div>
            <div class="h-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('loginRestrictionsModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="h-modal" id="accessSummaryModal">
    <div class="h-modal-dialog modal-lg">
        <div class="h-modal-header">
            <h5 class="h-modal-title"><i class="fas fa-info-circle"></i> User Access Summary</h5>
            <button type="button" class="btn btn-sm" onclick="closeModal('accessSummaryModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="h-modal-body" id="accessSummaryContent"></div>
        <div class="h-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('accessSummaryModal')">Close</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Custom Styles for Access Summary Modal */
    .user-summary-container { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .bg-soft-primary { background-color: rgba(59, 130, 246, 0.1) !important; color: #3b82f6 !important; }
    .bg-soft-success { background-color: rgba(16, 185, 129, 0.1) !important; color: #10b981 !important; }
    .bg-soft-danger { background-color: rgba(239, 68, 68, 0.1) !important; color: #ef4444 !important; }
    .bg-soft-warning { background-color: rgba(245, 158, 11, 0.1) !important; color: #f59e0b !important; }
    .bg-soft-info { background-color: rgba(6, 182, 212, 0.1) !important; color: #06b6d4 !important; }
    .bg-soft-secondary { background-color: rgba(107, 114, 128, 0.1) !important; color: #6b7280 !important; }
    
    .avatar-lg { width: 4.5rem; height: 4.5rem; font-size: 1.75rem; display: flex; align-items: center; justify-content: center; }
    .icon-square { width: 3rem; height: 3rem; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; font-size: 1.25rem; }
    .icon-circle { width: 2.5rem; height: 2.5rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 1rem; }
    
    .card-clean { border: 1px solid #e5e7eb; border-radius: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); overflow: hidden; height: 100%;background: #fff; }
    .card-clean .card-header { background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
    .card-clean .card-body { padding: 1rem; }
    
    .list-group-clean .list-group-item { border: none; border-bottom: 1px solid #f3f4f6; padding: 0.75rem 0; }
    .list-group-clean .list-group-item:last-child { border-bottom: none; }
    
    .badge-pill-custom { border-radius: 9999px; padding: 0.25em 0.8em; font-weight: 500; font-size: 0.75rem; }
</style>
@endpush

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
var allUsers = @json($allUsers ?? []);
var allBranches = @json($allBranches ?? []);
var accessControlRoutes = {
    accessLevel: @json(route('access-control.users.access-level', ['user' => '__USER_ID__'])),
    loginRestriction: @json(route('access-control.users.login-restriction', ['user' => '__USER_ID__'])),
    summary: @json(route('access-control.users.summary', ['user' => '__USER_ID__'])),
    toggleMultiLogin: @json(route('access-control.users.toggle-multi-login', ['user' => '__USER_ID__'])),
    toggleFreeze: @json(route('access-control.users.toggle-freeze', ['user' => '__USER_ID__'])),
    toggleScreenshot: @json(route('access-control.users.toggle-screenshot', ['user' => '__USER_ID__']))
};

function routeFor(template, userId) {
    return template.replace('__USER_ID__', userId);
}

$(document).ready(function() {
    // 1. Clone the header row for Search Filters
    $('#accessControlTable thead tr').clone(true).addClass('filters').appendTo('#accessControlTable thead');

    // 2. Initialize Access Control DataTable
    var table = $('#accessControlTable').DataTable({
        "responsive": false, 
        "lengthChange": true, 
        "autoWidth": false,
        "pageLength": 100, 
        "order": [[ 0, "asc" ]], 
        "orderCellsTop": true, 
        "fixedHeader": false,
        "language": {
            "search": "Search all:",
            "zeroRecords": "Tidak ada data yang cocok",
            "info": "Showing page _PAGE_ of _PAGES_",
            "infoEmpty": "Tidak ada data tersedia",
            "infoFiltered": "(difilter dari _MAX_ total data)"
        },
        "initComplete": function () {
            var api = this.api();
 
            // Bind instant filter logic to the cloned filters row
            api.columns().eq(0).each(function (colIdx) {
                var cell = $('.filters th').eq(api.column(colIdx).index());
                var title = $(api.column(colIdx).header()).text().trim();
                
                if ($(api.column(colIdx).header()).attr('data-no-filter') === undefined) {
                    $(cell).html('<input type="text" placeholder="Filter ' + title + '" />');
                    
                    $('input', cell).on('input keyup change', function (e) {
                        e.stopPropagation();
                        if (api.column(colIdx).search() !== this.value) {
                            api.column(colIdx).search(this.value).draw();
                        }
                    });
                } else {
                    $(cell).html('');
                }
            });
        }
    });

    // Access Levels Modal Logic
    $(document).on('click', '.edit-access-levels', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        var accessLevels = $(this).data('access-levels');

        $('#accessUserDisplayName').text(userName);
        $('#accessUserDisplayName').data('user-id', userId);
        $('#accessLevelsForm').attr('action', routeFor(accessControlRoutes.accessLevel, userId));
        $('#accessLevelsContainer').empty();

        if (accessLevels && accessLevels.length > 0) {
            accessLevels.forEach(function(level, index) {
                addAccessLevelField(level, index, userId);
            });
        } else {
            addAccessLevelField({}, 0, userId);
        }

        $('#accessLevelsModal').addClass('show');
    });

    $('#addAccessLevelField').on('click', function() {
        var currentUserId = $('#accessUserDisplayName').data('user-id') || null;
        addAccessLevelField({}, $('#accessLevelsContainer').children().length, currentUserId);
    });

    function addAccessLevelField(data, index, currentUserId) {
        var selectedSubordinates = data.access_config && (data.access_config.subordinate_ids || data.access_config.subordinates) ? (data.access_config.subordinate_ids || data.access_config.subordinates) : [];
        var selectedPeers = data.access_config && (data.access_config.peer_user_ids || data.access_config.peer_users) ? (data.access_config.peer_user_ids || data.access_config.peer_users) : [];
        var selectedBranches = data.access_config && (data.access_config.allowed_branch_ids || data.access_config.allowed_branches) ? (data.access_config.allowed_branch_ids || data.access_config.allowed_branches) : [];

        var availableUsers = allUsers.filter(function(user) {
            return user.id != currentUserId;
        });

        var hierarchicalOptions = availableUsers.map(function(user) {
            var deptName = user.department ? user.department.name : '-';
            var isSelected = selectedSubordinates.includes(user.id) ? 'selected' : '';
            return `<option value="${user.id}" ${isSelected}>${user.name} (${deptName})</option>`;
        }).join('');

        var peerOptions = availableUsers.map(function(user) {
            var deptName = user.department ? user.department.name : '-';
            var isSelected = selectedPeers.includes(user.id) ? 'selected' : '';
            return `<option value="${user.id}" ${isSelected}>${user.name} (${deptName})</option>`;
        }).join('');

        var branchOptions = allBranches.map(function(branch) {
            var branchLabel = branch.code ? `${branch.name} (${branch.code})` : branch.name;
            var isSelected = selectedBranches.includes(branch.id) ? 'selected' : '';
            return `<option value="${branch.id}" ${isSelected}>${branchLabel}</option>`;
        }).join('');

        var fieldHtml = `
            <div class="form-row access-level-item">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="access_type_${index}">Jenis Akses</label>
                    <select name="access_levels[${index}][access_type]" id="access_type_${index}" class="form-control access-type-select" required onchange="updateAccessConfigField(${index})">
                        <option value="">Pilih Jenis Akses</option>
                        <option value="hierarchical" ${data.access_type === 'hierarchical' ? 'selected' : ''}>Hierarki (Bisa lihat data bawahan)</option>
                        <option value="peer" ${data.access_type === 'peer' ? 'selected' : ''}>Peer (Bisa lihat data rekan terpilih)</option>
                        <option value="branch" ${data.access_type === 'branch' ? 'selected' : ''}>Cabang (Bisa lihat data seluruh cabang terpilih)</option>
                        <option value="company" ${data.access_type === 'company' ? 'selected' : ''}>Perusahaan (Bisa lihat seluruh data)</option>
                        <option value="none" ${data.access_type === 'none' ? 'selected' : ''}>Tidak Ada (Hanya data sendiri)</option>
                    </select>
                </div>
                
                <div id="access_config_container_${index}" style="grid-column: 1 / -1; display: none;">
                    <div id="hierarchical_config_${index}" class="config-section" style="display: none;">
                        <label class="form-label">Pilih Bawahan (Subordinates)</label>
                        <select id="subordinates_${index}" class="form-control multi-select" multiple style="height: 150px;">
                            ${hierarchicalOptions}
                        </select>
                        <small class="text-gray-500">Tahan Ctrl untuk memilih lebih dari satu.</small>
                    </div>
                    
                    <div id="peer_config_${index}" class="config-section" style="display: none;">
                        <label class="form-label">Pilih Rekan (Peer Users)</label>
                        <select id="peer_users_${index}" class="form-control multi-select" multiple style="height: 150px;">
                            ${peerOptions}
                        </select>
                        <small class="text-gray-500">Tahan Ctrl untuk memilih lebih dari satu.</small>
                    </div>
                    
                    <div id="branch_config_${index}" class="config-section" style="display: none;">
                        <label class="form-label">Pilih Cabang (Allowed Branches)</label>
                        <select id="allowed_branches_${index}" class="form-control multi-select" multiple style="height: 150px;">
                            ${branchOptions}
                        </select>
                        <small class="text-gray-500">Tahan Ctrl untuk memilih lebih dari satu.</small>
                    </div>
                </div>

                <div style="grid-column: 3; display: flex; align-items: flex-end; justify-content: flex-end;">
                    <button type="button" class="btn btn-danger btn-sm remove-item" onclick="$(this).closest('.form-row').remove()">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        `;

        $('#accessLevelsContainer').append(fieldHtml);
        updateAccessConfigField(index);
    }

    window.updateAccessConfigField = function(index) {
        var type = $('#access_type_' + index).val();
        var configContainer = $('#access_config_container_' + index);
        
        configContainer.find('.config-section').hide();
        
        if (type === 'hierarchical') {
            configContainer.show();
            $('#hierarchical_config_' + index).show();
        } else if (type === 'peer') {
            configContainer.show();
            $('#peer_config_' + index).show();
        } else if (type === 'branch') {
            configContainer.show();
            $('#branch_config_' + index).show();
        } else {
            configContainer.hide();
        }
    }

    // Modal close functionality
    window.closeModal = function(modalId) {
        $('#' + modalId).removeClass('show');
    };

    // Close modal when clicking outside
    $('.h-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).removeClass('show');
        }
    });

    // Form submission handling for Access Levels
    $('#accessLevelsForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var accessLevels = [];
        
        $('#accessLevelsContainer .access-level-item').each(function() {
            var select = $(this).find('.access-type-select');
            if (select.length === 0) return;
            
            var index = select.attr('id').replace('access_type_', '');
            var accessType = select.val();
            var accessConfig = {};
            
            if (accessType === 'hierarchical') {
                var subordinateIds = [];
                $(`#subordinates_${index} option:selected`).each(function() { subordinateIds.push(parseInt($(this).val())); });
                accessConfig = { subordinate_ids: subordinateIds };
            } else if (accessType === 'peer') {
                var peerIds = [];
                $(`#peer_users_${index} option:selected`).each(function() { peerIds.push(parseInt($(this).val())); });
                accessConfig = { peer_user_ids: peerIds };
            } else if (accessType === 'branch') {
                var branchIds = [];
                $(`#allowed_branches_${index} option:selected`).each(function() { branchIds.push(parseInt($(this).val())); });
                accessConfig = { allowed_branch_ids: branchIds };
            }
            
            if (accessType) {
                accessLevels.push({ access_type: accessType, access_config: accessConfig });
            }
        });
        
        var formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        
        accessLevels.forEach(function(level, index) {
            formData.append(`access_levels[${index}][access_type]`, level.access_type);
            if (level.access_config) {
                for (var key in level.access_config) {
                    if (Array.isArray(level.access_config[key])) {
                        level.access_config[key].forEach(function(id) {
                            formData.append(`access_levels[${index}][access_config][${key}][]`, id);
                        });
                    } else {
                        formData.append(`access_levels[${index}][access_config][${key}]`, level.access_config[key]);
                    }
                }
            }
        });
        
        form.find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                $('.h-modal').removeClass('show');
                showSuccessDialog('Berhasil', 'Akses berhasil diperbarui.');
                location.reload();
            },
            error: function(xhr) {
                showErrorDialog('Gagal', 'Terjadi kesalahan: ' + (xhr.responseJSON?.message || 'Gagal menyimpan data'));
                form.find('button[type="submit"]').prop('disabled', false).html('Simpan Perubahan');
            }
        });
    });

    // Login Restrictions Logic
    $(document).on('click', '.edit-login-restrictions', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        var restrictions = $(this).data('login-restrictions');

        $('#restrictionsUserDisplayName').text(userName);
        $('#loginRestrictionsForm').attr('action', routeFor(accessControlRoutes.loginRestriction, userId));
        $('#loginRestrictionsContainer').empty();

        if (restrictions && restrictions.length > 0) {
            restrictions.forEach(function(restriction, index) {
                addLoginRestrictionField(restriction, index);
            });
        } else {
            addLoginRestrictionField({}, 0);
        }

        $('#loginRestrictionsModal').addClass('show');
    });

    $('#addLoginRestrictionField').on('click', function() {
        addLoginRestrictionField({}, $('#loginRestrictionsContainer').children().length);
    });

    function addLoginRestrictionField(data, index) {
        var allowedDays = data.allowed_days || [];
        if (typeof allowedDays === 'string') {
            try { allowedDays = JSON.parse(allowedDays); } catch(e) { allowedDays = []; }
        }

        var dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        var daysHtml = dayNames.map(function(day, i) {
            var isChecked = allowedDays.includes(i) || allowedDays.includes(day) ? 'checked' : '';
            return `
                <div class="checkbox-item">
                    <input type="checkbox" name="login_restrictions[${index}][allowed_days][]" value="${day}" ${isChecked} id="day_${index}_${day}">
                    <label for="day_${index}_${day}">${day.charAt(0).toUpperCase() + day.slice(1)}</label>
                </div>
            `;
        }).join('');

        var fieldHtml = `
            <div class="form-row login-restriction-item">
                <div class="form-group">
                    <label class="form-label">Mulai Jam</label>
                    <input type="time" name="login_restrictions[${index}][start_time]" class="form-control" value="${data.start_time ? data.start_time.substring(0, 5) : ''}">
                </div>
                <div class="form-group">
                    <label class="form-label">Selesai Jam</label>
                    <input type="time" name="login_restrictions[${index}][end_time]" class="form-control" value="${data.end_time ? data.end_time.substring(0, 5) : ''}">
                </div>
                <div class="form-group">
                    <label class="form-label">Idle Timeout (Menit)</label>
                    <input type="number" name="login_restrictions[${index}][idle_timeout]" class="form-control" value="${data.idle_timeout || 30}">
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Hari yang Diizinkan (Kosongkan jika semua hari)</label>
                    <div class="checkbox-group">${daysHtml}</div>
                </div>
                <div style="grid-column: 3; display: flex; align-items: flex-end; justify-content: flex-end;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('.form-row').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#loginRestrictionsContainer').append(fieldHtml);
    }

    $('#loginRestrictionsForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var firstItem = $('.login-restriction-item').first();
        
        if (firstItem.length === 0) {
            showWarningDialog('Minimal satu pembatasan harus ada.');
            return;
        }

        var formData = new FormData(this);
        form.find('button[type="submit"]').prop('disabled', true).html('Menyimpan...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                $('.h-modal').removeClass('show');
                showSuccessDialog('Berhasil', 'Pembatasan login berhasil diperbarui.');
                location.reload();
            },
            error: function(xhr) {
                showErrorDialog('Gagal', 'Kesalahan: ' + (xhr.responseJSON?.message || 'Gagal menyimpan'));
                form.find('button[type="submit"]').prop('disabled', false).html('Simpan Perubahan');
            }
        });
    });

    // --- USER SUMMARY MODAL LOGIC WITH CUSTOM STYLES ---
    $(document).on('click', '.view-summary', function() {
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        $('#accessSummaryContent').html('<div style="text-align:center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#accessSummaryModal').addClass('show');

        // Clean up any existing backdrop artifacts if needed
        $('.h-modal-backdrop').remove();

        $.ajax({
            url: routeFor(accessControlRoutes.summary, userId),
            method: 'GET',
            success: function(response) {
                if (response && response.user) {
                    var user = response.user;
                    var accessLevels = response.access_levels || [];
                    var loginRestrictions = response.login_restrictions;
                    var recentLogins = response.recent_logins || [];
                    
                    // --- Safe Data Parsing ---
                    var initials = user.name ? (user.name.match(/\b\w/g) || []).slice(0, 2).join('').toUpperCase() : '??';
                    
                    var rolesHtml = user.roles && user.roles.length > 0 
                        ? user.roles.map(r => `<span class="badge badge-pill-custom bg-soft-info">${r.name}</span>`).join(' ') 
                        : '<span class="text-muted small">No Roles Assigned</span>';

                    var multiLoginBadge = user.multi_login 
                        ? '<span class="badge badge-pill-custom bg-soft-success"><i class="fas fa-check-circle mr-1"></i>Enabled</span>' 
                        : '<span class="badge badge-pill-custom bg-soft-secondary"><i class="fas fa-times-circle mr-1"></i>Disabled</span>';
                    
                    var statusBadge = user.is_active
                        ? '<span class="badge badge-pill-custom bg-soft-success"><i class="fas fa-check mr-1"></i>Active</span>'
                        : '<span class="badge badge-pill-custom bg-soft-danger"><i class="fas fa-ban mr-1"></i>Inactive</span>';

                    // --- Restrictions with Safety Checks ---
                    var timeStr = 'Any Time';
                    if (loginRestrictions && typeof loginRestrictions.start_time !== 'undefined' && typeof loginRestrictions.end_time !== 'undefined') {
                         var st = loginRestrictions.start_time ? String(loginRestrictions.start_time).substring(0,5) : '00:00';
                         var et = loginRestrictions.end_time ? String(loginRestrictions.end_time).substring(0,5) : '23:59';
                         timeStr = (loginRestrictions.start_time || loginRestrictions.end_time) ? `${st} - ${et}` : 'Any Time';
                    }
                        
                    var daysStr = 'All Days';
                    if (loginRestrictions && loginRestrictions.allowed_days) {
                         try {
                            // Handle if it's already an array or needs parsing
                            var d = Array.isArray(loginRestrictions.allowed_days) 
                                ? loginRestrictions.allowed_days 
                                : JSON.parse(loginRestrictions.allowed_days);
                                
                            if (Array.isArray(d) && d.length > 0) {
                                var dayMap = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                                // Filter valid indices
                                var validDays = d.filter(idx => idx >= 0 && idx <= 6).map(idx => dayMap[idx]);
                                if (validDays.length > 0) daysStr = validDays.join(', ');
                            }
                         } catch(e) { console.error('Day parse error', e); }
                    }
                    var idleStr = (loginRestrictions && loginRestrictions.idle_timeout) ? `${loginRestrictions.idle_timeout} mins` : '30 mins (Default)';

                    // --- Access Levels UI ---
                    var accessHtml = '';
                    if (accessLevels.length > 0) {
                        accessHtml = '<div class="list-group list-group-clean">';
                        accessLevels.forEach(function(level) {
                            var icon = 'fa-lock';
                            var bgClass = 'bg-soft-primary';
                            var detailHtml = '';

                            if (level.access_type === 'hierarchical') {
                                icon = 'fa-sitemap'; bgClass = 'bg-soft-info';
                                var count = level.access_config && level.access_config.subordinates ? level.access_config.subordinates.length : 0;
                                detailHtml = `Bawahan: <strong>${count} Users</strong>`;
                            } else if (level.access_type === 'peer') {
                                icon = 'fa-user-friends'; bgClass = 'bg-soft-success';
                                var count = level.access_config && level.access_config.peer_users ? level.access_config.peer_users.length : 0;
                                detailHtml = `Rekan: <strong>${count} Users</strong>`;
                            } else if (level.access_type === 'branch') {
                                icon = 'fa-building'; bgClass = 'bg-soft-warning';
                                var count = level.access_config && level.access_config.allowed_branches ? level.access_config.allowed_branches.length : 0;
                                detailHtml = `Cabang: <strong>${count} Branches</strong>`;
                            } else if (level.access_type === 'company') {
                                icon = 'fa-globe-asia'; bgClass = 'bg-soft-primary';
                                detailHtml = 'Full Access (All Branches)';
                            }

                            accessHtml += `
                                <div class="list-group-item d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-square ${bgClass} rounded mr-3">
                                            <i class="fas ${icon}"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 text-dark font-weight-bold text-capitalize" style="font-size:0.9rem">${level.access_type} Access</h6>
                                            <div class="small text-muted">${detailHtml}</div>
                                        </div>
                                    </div>
                                    <div class="text-muted"><i class="fas fa-check-circle text-success opacity-50"></i></div>
                                </div>
                            `;
                        });
                        accessHtml += '</div>';
                    } else {
                        accessHtml = `
                            <div class="text-center p-3 rounded" style="background:#f9fafb; border:1px dashed #e5e7eb;">
                                <i class="fas fa-info-circle text-muted mb-2"></i>
                                <p class="text-muted mb-0 small">Standard Access (Own Data Only)</p>
                            </div>
                        `;
                    }

                    // --- Login History ---
                    var loginHistoryHtml = '';
                    if (recentLogins.length > 0) {
                        loginHistoryHtml = '<div class="table-responsive"><table class="table table-sm table-borderless table-striped mb-0" style="font-size:0.8rem;">';
                        loginHistoryHtml += '<thead class="text-muted" style="background:#f9fafb;"><tr><th class="pl-3">IP Address</th><th>Recorded At</th><th>Status</th></tr></thead><tbody>';
                        recentLogins.forEach(function(login) {
                            var status = '<span class="text-success"><i class="fas fa-check small mr-1"></i>Ok</span>'; 
                            loginHistoryHtml += `<tr>
                                <td class="pl-3 font-family-monospace text-dark">${login.ip_address || 'Unknown'}</td>
                                <td>${login.login_at || '-'}</td>
                                <td>${status}</td>
                            </tr>`;
                        });
                        loginHistoryHtml += '</tbody></table></div>';
                    } else {
                         loginHistoryHtml = '<div class="text-center py-4"><span class="text-muted small">No recent login activity.</span></div>';
                    }

                    var content = `
                        <div class="user-summary-container">
                            <!-- Header -->
                            <div class="p-4" style="background: linear-gradient(to right, #ffffff, #f9fafb); border-bottom:1px solid #e5e7eb;">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-lg bg-soft-primary rounded-circle shadow-sm mr-4" style="border: 4px solid #fff;">
                                        ${initials}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h4 class="mb-1 font-weight-bold text-dark" style="font-size:1.25rem;">${user.name}</h4>
                                        <div class="d-flex align-items-center text-muted mb-2 small">
                                            <i class="fas fa-envelope mr-1"></i> ${user.email || 'No Email'}
                                            <span class="mx-2 text-gray-300">|</span>
                                            <i class="fas fa-user-tag mr-1"></i> ${user.username}
                                        </div>
                                        <div class="mt-2">${rolesHtml}</div>
                                    </div>
                                    <div class="text-right d-none d-sm-block">
                                        <div class="small text-uppercase text-muted font-weight-bold mb-1" style="font-size:0.7rem;letter-spacing:0.05em;">Member Since</div>
                                        <div class="font-weight-bold text-dark">${user.created_at ? user.created_at.substring(0,10) : '-'}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4" style="background-color: #f3f4f6;">
                                <div class="row">
                                    <div class="col-sm-5 mb-3 mb-sm-0">
                                        <!-- Account Status -->
                                        <div class="card-clean mb-3">
                                            <div class="card-header"><i class="fas fa-shield-alt mr-2 text-primary"></i>Account Status</div>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <span class="text-dark small font-weight-bold">Status</span>
                                                    ${statusBadge}
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-dark small font-weight-bold">Multi Login</span>
                                                    ${multiLoginBadge}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Login Constraints -->
                                        <div class="card-clean">
                                            <div class="card-header"><i class="fas fa-user-clock mr-2 text-warning"></i>Restrictions</div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="icon-circle bg-soft-warning mr-3"><i class="fas fa-clock"></i></div>
                                                    <div>
                                                        <div class="small text-muted font-weight-bold text-uppercase" style="font-size:0.65rem;">Allowed Time</div>
                                                        <div class="font-weight-bold text-dark small">${timeStr}</div>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="icon-circle bg-soft-info mr-3"><i class="fas fa-calendar-alt"></i></div>
                                                    <div>
                                                        <div class="small text-muted font-weight-bold text-uppercase" style="font-size:0.65rem;">Allowed Days</div>
                                                        <div class="font-weight-bold text-dark small" style="line-height:1.2">${daysStr}</div>
                                                    </div>
                                                </div>
                                                 <div class="d-flex align-items-center">
                                                    <div class="icon-circle bg-soft-danger mr-3"><i class="fas fa-hourglass-half"></i></div>
                                                    <div>
                                                        <div class="small text-muted font-weight-bold text-uppercase" style="font-size:0.65rem;">Idle Timeout</div>
                                                        <div class="font-weight-bold text-dark small">${idleStr}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-7">
                                        <!-- Access Levels -->
                                        <div class="card-clean mb-3">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-layer-group mr-2 text-info"></i>Access Hierarchy</span>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="px-3 py-2">${accessHtml}</div>
                                            </div>
                                        </div>

                                        <!-- History -->
                                        <div class="card-clean">
                                            <div class="card-header"><i class="fas fa-history mr-2 text-secondary"></i>Recent Activity</div>
                                            <div class="card-body p-0">
                                                ${loginHistoryHtml}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#accessSummaryContent').html(content);
                } else {
                     $('#accessSummaryContent').html('<div class="alert alert-danger m-3">Invalid response data received.</div>');
                }
            },
            error: function(xhr) { 
                console.error("Summary error:", xhr);
                $('#accessSummaryContent').html(`<div class="alert alert-danger m-3">Error loading summary: ${xhr.status} ${xhr.statusText}</div>`); 
            }
        });
    });

    // Toggle handlers
    $(document).on('click', '.toggle-multi-login:not(.disabled)', function() {
        var btn = $(this);
        var userId = btn.data('user-id');
        showConfirmDialog('Ubah Status Multi Login?', 'Apakah Anda yakin ingin mengubah status Multi Login?', 'Ya, ubah', 'Batal').then(function(confirmed) {
            if (!confirmed) return;
            
            $.post(routeFor(accessControlRoutes.toggleMultiLogin, userId), { _token: $('meta[name="csrf-token"]').attr('content') })
                .done(function(res) { if (res.success) location.reload(); })
                .fail(function() { showErrorDialog('Gagal', 'Status tidak berhasil diubah.'); });
        });
    });

    $(document).on('click', '.toggle-freeze', function() {
        var btn = $(this);
        var userId = btn.data('user-id');
        showConfirmDialog('Ubah Status Freeze Akun?', 'Apakah Anda yakin ingin mengubah status freeze akun?', 'Ya, ubah', 'Batal').then(function(confirmed) {
            if (!confirmed) return;
            
            $.post(routeFor(accessControlRoutes.toggleFreeze, userId), { _token: $('meta[name="csrf-token"]').attr('content') })
                .done(function(res) { if (res.success) location.reload(); })
                .fail(function() { showErrorDialog('Gagal', 'Status tidak berhasil diubah.'); });
        });
    });

    $(document).on('click', '.toggle-screenshot:not(.disabled)', function() {
        var btn = $(this);
        var userId = btn.data('user-id');
        showConfirmDialog('Ubah Izin Screenshot?', 'Apakah Anda yakin ingin mengubah status izin screenshot?', 'Ya, ubah', 'Batal').then(function(confirmed) {
            if (!confirmed) return;
            
            $.post(routeFor(accessControlRoutes.toggleScreenshot, userId), { _token: $('meta[name="csrf-token"]').attr('content') })
                .done(function(res) { if (res.success) location.reload(); })
                .fail(function() { showErrorDialog('Gagal', 'Status tidak berhasil diubah.'); });
        });
    });
});
</script>
@endpush
