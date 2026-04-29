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

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
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
    -webkit-overflow-scrolling: touch;
}
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Warehouses Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Warehouse</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-3">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Warehouse</span>
                    <span class="md:hidden">Add</span>
                </button>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row justify-start items-center w-full gap-3">
                <!-- Delete Button -->
                <button class="btn btn-secondary btn-sm" onclick="bulkDeleteWarehouses()">
                    <i class="fas fa-trash"></i>
                    <span>Delete Selected</span>
                </button>
                
                <div class="ml-4 text-sm text-gray-600">
                    <i class="fas fa-info-circle"></i>
                    <strong>Filter real-time:</strong> Ketik di kotak filter untuk mencari data (server-side)
                </div>
            </div>
        </div>

        <!-- DataTable Component -->
        <x-datatable 
            id="warehousesTable"
            :endpoint="route('warehouse.warehouses.datatable')"
            :hasCheckbox="true"
            :hasActions="false"
            :rowClickRoute="route('warehouse.warehouses.show', ':id')"
            :columns="[
                [
                    'data' => 'warehouse_code', 
                    'label' => 'Warehouse Code', 
                    'searchable' => true,
                    'render' => 'function(data, type, row) {
                        return data ? \"<span class=\'px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 font-mono\'>\" + data + \"</span>\" : \"-\";
                    }'
                ],
                [
                    'data' => 'warehouse_name', 
                    'label' => 'Warehouse Name', 
                    'searchable' => true,
                    'render' => 'function(data, type, row) {
                        let html = \"<div><strong>\" + (data || \"-\") + \"</strong>\";
                        if (row.phone) {
                            html += \"<br><small class=\'text-gray-500\'><i class=\'fas fa-phone\'></i> \" + row.phone + \"</small>\";
                        }
                        html += \"</div>\";
                        return html;
                    }'
                ],
                [
                    'data' => 'branch', 
                    'label' => 'Branch', 
                    'searchable' => false,
                    'render' => 'function(data, type, row) {
                        if (!row.branch) return \"-\";
                        return \"<span class=\'px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800\'>\" + row.branch.branch_name + \"</span>\";
                    }'
                ],
                [
                    'data' => 'warehouse_type', 
                    'label' => 'Type', 
                    'searchable' => false,
                    'render' => 'function(data, type, row) {
                        if (!row.warehouse_type) return \"-\";
                        return \"<span class=\'px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800\'>\" + row.warehouse_type.name + \"</span>\";
                    }'
                ],
                [
                    'data' => 'address', 
                    'label' => 'Address', 
                    'searchable' => true,
                    'render' => 'function(data, type, row) {
                        if (!data) return \"-\";
                        let shortAddr = data.length > 50 ? data.substring(0, 50) + \"...\" : data;
                        return \"<small>\" + shortAddr + \"</small>\";
                    }'
                ],
                [
                    'data' => 'manager_name', 
                    'label' => 'Manager', 
                    'searchable' => true,
                    'render' => 'function(data, type, row) {
                        return data ? \"<span class=\'text-sm\'>\" + data + \"</span>\" : \"-\";
                    }'
                ],
                [
                    'data' => 'is_center', 
                    'label' => 'Center', 
                    'searchable' => false,
                    'render' => 'function(data, type, row) {
                        return data 
                            ? \"<span class=\'px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800\'><i class=\'fas fa-star\'></i> Center</span>\"
                            : \"<span class=\'px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600\'>Branch</span>\";
                    }'
                ],
                [
                    'data' => 'is_active', 
                    'label' => 'Status', 
                    'searchable' => false,
                    'render' => 'function(data, type, row) {
                        return data 
                            ? \"<span class=\'px-2 py-1 text-xs rounded-full bg-green-100 text-green-800\'>Active</span>\"
                            : \"<span class=\'px-2 py-1 text-xs rounded-full bg-red-100 text-red-800\'>Inactive</span>\";
                    }'
                ]
            ]"
        />
        
    </div>
</div>

@push('scripts')
<script>
// Bulk delete function
function bulkDeleteWarehouses() {
    let selectedIds = [];
    $('.row-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        alert('Pilih minimal satu warehouse untuk dihapus');
        return;
    }
    
    if (confirm(`Apakah Anda yakin ingin menghapus ${selectedIds.length} warehouse?`)) {
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
            if (data.status === 'success') {
                alert('Warehouses berhasil dihapus');
                $('#warehousesTable').DataTable().ajax.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus data');
        });
    }
}

// Create modal placeholder - replace with your actual modal function
function openCreateModal() {
    alert('Implementasi modal Create Warehouse - gunakan modal yang sudah ada di file index.blade.php original');
}
</script>
@endpush
@endsection

