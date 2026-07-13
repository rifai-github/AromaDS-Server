@extends('layouts.app')

@section('title', 'Customer Categories Management')
@section('breadcrumb', 'Home / Marketing / Master Customer Category')

@section('content')
<style>
    /* Global scroll control */
    .content-body {
        overflow: visible !important;
    }

    /* Marketing Pipeline Styles Replicated */
    .table-header-box {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        background-color: white;
        border-radius: 10px 10px 0 0;
        padding: 1.25rem 1.5rem;
        border-bottom: 2px solid #f3f4f6;
    }

    .table-header-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e3a8a;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        background: white;
        width: 100%;
        position: relative;
    }
    
    .responsive-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .responsive-table th {
        background-color: #225fd3 !important; /* Pipeline Blue */
        color: white !important;
        font-weight: 600;
        font-size: 12px;
        position: sticky;
        top: 0;
        z-index: 100;
        height: 48px !important;
        vertical-align: middle;
        padding: 0 16px !important;
    }

    /* Filter Row Style - Pipeline matched */
    .responsive-table thead tr.filters th {
        background-color: #f8fafc !important;
        position: sticky;
        top: 48px !important; /* EXACTLY the height of the first row */
        z-index: 90;
        padding: 6px 10px !important;
        border-bottom: 2px solid #e2e8f0;
        height: 44px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .responsive-table thead tr.filters input {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 12px;
        background-color: white !important;
        color: #111827 !important; /* Force dark text */
        transition: all 0.2s;
        height: 32px;
    }

    .responsive-table thead tr.filters input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        background-color: #fff;
    }

    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }

    /* Custom Pagination - Marketing Pipeline Style */
    .pagination-wrapper {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        width: 100%;
        padding: 1rem;
        background-color: white;
        border-radius: 0 0 10px 10px;
        border-top: 1px solid #f3f4f6;
    }

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
        color: #6b7280;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .page-number.active {
        background-color: #214589;
        color: white;
    }

    .page-number:not(.active):hover {
        background-color: #f3f4f6;
        color: #214589;
    }

    .btn-pagination {
        padding: 6px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid #d1d5db;
        background-color: #f3f4f6;
        color: #4b5563;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-pagination:hover:not(:disabled) {
        background-color: #214589;
        color: white;
        border-color: #214589;
    }

    .btn-pagination:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Hide global DataTables default pagination and search */
    .dataTables_wrapper .dataTables_paginate,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info {
        display: none !important;
    }

    /* Ensure filter-row is visible but keep it clean */
    .responsive-table tr.filter-row {
        display: table-row !important;
    }

    /* Action Buttons Style */
    .action-btn-group {
        display: flex;
        gap: 8px;
    }
    
    .btn-icon {
        padding: 6px;
        border-radius: 6px;
        color: white !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: none;
        transition: transform 0.2s, background-color 0.2s;
    }

    .btn-icon:hover {
        transform: scale(1.1);
        filter: brightness(1.1);
    }

    .bg-view { background-color: #3b82f6 !important; }
    .bg-edit { background-color: #f59e0b !important; }
    .bg-delete { background-color: #ef4444 !important; }
</style>

<div class="container-fluid">
    <!-- Pipeline Style Header (Redundancy check - removed extra margin) -->
    <div class="table-header-box">
        <h1 class="table-header-title">List Categories</h1>
        <a href="{{ route('system.customer-types.create') }}" class="btn btn-primary shadow-sm" style="background-color: #1e3a8a; border: none;">
            <i class="fas fa-plus mr-2"></i>
            <span>Add Category</span>
        </a>
    </div>

    <!-- Table Container -->
    <div class="table-container shadow-sm">
        <table class="responsive-table" id="customerTypesTable" data-filter-enhanced="1">
            <thead>
                <tr class="main-header">
                    <th data-no-filter>ID</th>
                    <th data-column="name">Name</th>
                    <th data-column="description">Description</th>
                    <th data-column="is_active">Status</th>
                    <th data-column="created_at" data-type="date">Created At</th>
                    <th data-column="createdBy.name">Created By</th>
                    <th data-column="updated_at" data-type="date">Updated At</th>
                    <th data-column="updatedBy.name">Updated By</th>
                    <th data-no-filter>Actions</th>
                </tr>
                <tr class="filters filter-row">
                    <th></th>
                    <th><input type="text" placeholder="Filter Name" class="form-control form-control-sm"></th>
                    <th><input type="text" placeholder="Filter Description" class="form-control form-control-sm"></th>
                    <th><input type="text" placeholder="Filter Status" class="form-control form-control-sm"></th>
                    <th><input type="date" class="form-control form-control-sm"></th>
                    <th><input type="text" placeholder="Filter User" class="form-control form-control-sm"></th>
                    <th><input type="date" class="form-control form-control-sm"></th>
                    <th><input type="text" placeholder="Filter User" class="form-control form-control-sm"></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customerTypes as $type)
                <tr>
                    <td>{{ $type->id }}</td>
                    <td><strong>{{ $type->name }}</strong></td>
                    <td>{{ $type->description ?? '-' }}</td>
                    <td>
                        @if($type->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-secondary">Tidak Aktif</span>
                        @endif
                    </td>
                    <td>{{ $type->created_at->format('d/M/Y H:i') }}</td>
                    <td>{{ $type->createdBy->name ?? '-' }}</td>
                    <td>{{ $type->updated_at->format('d/M/Y H:i') }}</td>
                    <td>{{ $type->updatedBy->name ?? '-' }}</td>
                    <td>
                        <div class="action-btn-group" onclick="event.stopPropagation()">
                            <a href="{{ route('system.customer-types.show', $type) }}" class="btn-icon bg-view" title="Lihat">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('system.customer-types.edit', $type) }}" class="btn-icon bg-edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('system.customer-types.destroy', $type) }}" method="POST" onsubmit="return confirm('Apakah kamu yakin?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon bg-delete" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="p-8 text-center text-gray-500">Belum ada kategori customer.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pipeline Style Pagination -->
    <div class="pagination-wrapper shadow-sm mb-4">
        <div class="pagination-controls" id="customPagination">
            <!-- Will be populated by JS -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        var table = $('#customerTypesTable').DataTable({
            "order": [[1, "asc"]],
            "pageLength": 10,
            "responsive": false,
            "orderCellsTop": true,
            "fixedHeader": false,
            "dom": 'rtip', // Hide default controls
            "language": {
                "emptyTable": "Tidak ada data"
            },
            "drawCallback": function(settings) {
                updateCustomPagination(this.api());
            },
            "initComplete": function () {
                var api = this.api();
     
                // Attach listeners to the HARDCODED filter inputs
                $('#customerTypesTable thead tr.filters input').each(function (colIdx) {
                    // colIdx maps to the inputs in our row. 
                    // Column 0 is ID, Column 1 is Name (Input index 0)
                    // We need to shift index by 1 because Input 0 is for Column 1
                    var actualColIdx = colIdx + 1; 
                    
                    $(this).on('keyup change', function () {
                        if (api.column(actualColIdx).search() !== this.value) {
                            api.column(actualColIdx).search(this.value).draw();
                        }
                    });
                });
            }
        });

        function updateCustomPagination(api) {
            var info = api.page.info();
            var pages = [];
            var start = Math.max(0, info.page - 2);
            var end = Math.min(info.pages, info.page + 3);
            
            var html = '';
            
            // Previous Button
            html += `<button class="btn-pagination" ${info.page === 0 ? 'disabled' : ''} onclick="changePage('prev')">Previous</button>`;
            
            html += `<div class="flex items-center gap-2">`;
            
            // First Page
            if (start > 0) {
                html += `<a href="javascript:void(0)" class="page-number" onclick="goToPage(0)">1</a>`;
                if (start > 1) html += `<span class="text-gray-500">...</span>`;
            }
            
            // Numbered Pages
            for (var i = start; i < end; i++) {
                html += `<a href="javascript:void(0)" class="page-number ${i === info.page ? 'active' : ''}" onclick="goToPage(${i})">${i + 1}</a>`;
            }
            
            // Last Page
            if (end < info.pages) {
                if (end < info.pages - 1) html += `<span class="text-gray-500">...</span>`;
                html += `<a href="javascript:void(0)" class="page-number" onclick="goToPage(${info.pages - 1})">${info.pages}</a>`;
            }
            
            html += `</div>`;
            
            // Next Button
            html += `<button class="btn-pagination" ${info.page === info.pages - 1 || info.pages === 0 ? 'disabled' : ''} onclick="changePage('next')">Next</button>`;
            
            // Page of Total
            html += `<div class="flex items-center gap-2 ml-4">
                        <span class="text-sm text-gray-700">Page</span>
                        <select onchange="goToPage(this.value)" class="bg-gray-100 rounded px-2 py-1 text-sm border border-gray-300">
                            ${Array.from({length: info.pages}, (_, k) => `<option value="${k}" ${k === info.page ? 'selected' : ''}>${k + 1}</option>`).join('')}
                        </select>
                        <span class="text-sm text-gray-700">of ${info.pages || 1}</span>
                     </div>`;
            
            $('#customPagination').html(html);
        }

        window.goToPage = function(index) {
            table.page(parseInt(index)).draw('page');
        };

        window.changePage = function(dir) {
            if (dir === 'prev') table.page('previous').draw('page');
            else table.page('next').draw('page');
        };
    });
</script>
@endpush
