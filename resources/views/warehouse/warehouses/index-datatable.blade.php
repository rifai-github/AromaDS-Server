@extends('layouts.app')

@section('title', 'Master Warehouse')
@section('breadcrumb', 'Home / Warehouse / Master Warehouse')

@section('content')
<style>
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
    background-color: #214589 !important;
    color: white !important;
    border-color: #214589 !important;
}

/* Table Container */
.table-container {
    background: white;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    border-radius: 0 0 10px 10px;
    width: 100%;
    overflow-x: auto;
}

/* DataTables Filter Row Styling */
.dataTables_wrapper table thead tr.filter-row th {
    background-color: #f3f4f6;
    padding: 8px;
    border-bottom: 1px solid #e5e7eb;
}

.dataTables_wrapper table thead tr.filter-row input {
    width: 100%;
    padding: 6px 8px;
    font-size: 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background-color: white;
}

.dataTables_wrapper table thead tr.filter-row input:focus {
    outline: none;
    border-color: #214589;
    box-shadow: 0 0 0 2px rgba(33, 69, 137, 0.1);
}
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px]">
        
        <!-- Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <h1 class="text-xl font-semibold text-[#214589]">Master Warehouse</h1>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span class="hidden md:inline">Add New Warehouse</span>
                <span class="md:hidden">Add</span>
            </button>
        </div>
        
        <!-- Controls -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row items-center gap-3">
                <button class="btn btn-secondary" onclick="bulkDelete()">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
                <div class="text-sm text-gray-600">
                    <i class="fas fa-info-circle"></i>
                    <strong>Filter real-time:</strong> Ketik di kotak filter di bawah header
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <div class="table-container">
            <table id="warehousesTable" class="display responsive-table" style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 50px;">
                            <input type="checkbox" id="selectAll" class="w-4 h-4">
                        </th>
                        <th>Warehouse Code</th>
                        <th>Warehouse Name</th>
                        <th>Branch</th>
                        <th>Type</th>
                        <th>Address</th>
                        <th>Manager</th>
                        <th>Center</th>
                        <th>Status</th>
                    </tr>
                    <!-- Filter Row -->
                    <tr class="filter-row">
                        <th></th>
                        <th><input type="text" class="column-filter" placeholder="Filter..." data-column="1"></th>
                        <th><input type="text" class="column-filter" placeholder="Filter..." data-column="2"></th>
                        <th><input type="text" class="column-filter" placeholder="Filter..." data-column="3"></th>
                        <th><input type="text" class="column-filter" placeholder="Filter..." data-column="4"></th>
                        <th><input type="text" class="column-filter" placeholder="Filter..." data-column="5"></th>
                        <th><input type="text" class="column-filter" placeholder="Filter..." data-column="6"></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated by DataTables -->
                </tbody>
            </table>
        </div>
        
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    let table = $('#warehousesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("warehouse.warehouses.datatable") }}',
            type: 'GET',
            error: function(xhr, error, thrown) {
                console.error('DataTables Error:', error, thrown);
                alert('Error loading data. Please check console.');
            }
        },
        columns: [
            { 
                data: 'id',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return '<input type="checkbox" class="row-checkbox" value="' + data + '">';
                }
            },
            { 
                data: 'warehouse_code',
                render: function(data) {
                    return data ? '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">' + data + '</span>' : '-';
                }
            },
            { 
                data: 'name',  // FIXED: name bukan warehouse_name
                render: function(data, type, row) {
                    let html = '<strong>' + (data || '-') + '</strong>';
                    if (row.phone) {
                        html += '<br><small class="text-gray-500">' + row.phone + '</small>';
                    }
                    return html;
                }
            },
            { 
                data: 'branch',
                searchable: false,
                render: function(data, type, row) {
                    if (!row.branch) return '-';
                    return '<span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">' + row.branch.branch_name + '</span>';
                }
            },
            { 
                data: 'warehouse_type',
                searchable: false,
                render: function(data, type, row) {
                    if (!row.warehouse_type) return '-';
                    return '<span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800">' + row.warehouse_type.name + '</span>';
                }
            },
            { 
                data: 'address',
                render: function(data) {
                    if (!data) return '-';
                    return data.length > 50 ? data.substring(0, 50) + '...' : data;
                }
            },
            { 
                data: 'manager',  // FIXED: manager bukan manager_name
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'is_center',
                searchable: false,
                render: function(data) {
                    return data 
                        ? '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-star"></i> Center</span>'
                        : '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Branch</span>';
                }
            },
            { 
                data: 'is_active',
                searchable: false,
                render: function(data) {
                    return data 
                        ? '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>'
                        : '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>';
                }
            }
        ],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Loading...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        },
        createdRow: function(row, data) {
            $(row).css('cursor', 'pointer');
            $(row).on('click', function(e) {
                if (!$(e.target).is('input[type="checkbox"]')) {
                    window.location.href = '{{ route("warehouse.warehouses.show", ":id") }}'.replace(':id', data.id);
                }
            });
        }
    });
    
    // Column filtering
    $('.column-filter').on('keyup change', function() {
        let columnIndex = $(this).data('column');
        table.column(columnIndex).search(this.value).draw();
    });
    
    // Select all checkbox
    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', this.checked);
    });
    
    // Individual checkbox
    $('#warehousesTable').on('change', '.row-checkbox', function() {
        let allChecked = $('.row-checkbox').length === $('.row-checkbox:checked').length;
        $('#selectAll').prop('checked', allChecked);
    });
});

// Bulk delete
function bulkDelete() {
    let selectedIds = [];
    $('.row-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        alert('Pilih minimal satu warehouse');
        return;
    }
    
    if (confirm('Hapus ' + selectedIds.length + ' warehouse?')) {
        fetch('{{ route("warehouse.warehouses.bulk-delete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ ids: selectedIds })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message || 'Success');
            $('#warehousesTable').DataTable().ajax.reload();
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
}

// Create modal - replace with your actual modal
function openCreateModal() {
    alert('Implementasi modal Create - gunakan yang sudah ada di index-backup.blade.php');
}
</script>
@endpush
@endsection

