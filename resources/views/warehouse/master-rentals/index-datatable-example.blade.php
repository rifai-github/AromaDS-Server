@extends('layouts.app')

@section('title', 'Master Rentals with DataTables')
@section('breadcrumb', 'Home / Warehouse / Master Rentals')

@section('content')
<style>
    /* Custom Styles for DataTables */
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
        
        <!-- Master Rentals Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Rentals (DataTables Version)</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-3">
                <a href="{{ route('warehouse.rental-management.service-frequencies') }}" class="btn btn-secondary">
                    <i class="fas fa-cog"></i>
                    <span class="hidden md:inline">Manage Freq</span>
                    <span class="md:hidden">Freq</span>
                </a>
                <button class="btn btn-primary" onclick="openAddRentalModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Rental</span>
                    <span class="md:hidden">Add</span>
                </button>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row justify-start items-center w-full">
                <!-- Delete Button -->
                <button class="btn btn-secondary btn-sm" onclick="bulkDeleteRentals()">
                    <i class="fas fa-trash"></i>
                    <span>Delete Selected</span>
                </button>
                
                <div class="ml-4 text-sm text-gray-600">
                    <i class="fas fa-info-circle"></i>
                    Ketik di kotak filter untuk mencari data secara real-time (server-side)
                </div>
            </div>
        </div>

        <!-- DataTable Component -->
        <x-datatable 
            id="masterRentalsTable"
            :endpoint="route('warehouse.master-rentals.datatable')"
            :hasCheckbox="true"
            :hasActions="false"
            :rowClickRoute="route('warehouse.master-rentals.show', ':id')"
            :columns="[
                [
                    'data' => 'rental_code', 
                    'label' => 'Rental Code', 
                    'searchable' => true,
                    'render' => 'function(data, type, row) {
                        return data ? \"<span class=\'px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800\'>\" + data + \"</span>\" : \"-\";
                    }'
                ],
                [
                    'data' => 'rental_name', 
                    'label' => 'Rental Name', 
                    'searchable' => true,
                    'render' => 'function(data, type, row) {
                        let html = \"<div><strong>\" + (data || \"-\") + \"</strong>\";
                        if (row.description) {
                            html += \"<br><small class=\'text-gray-500\'>\" + row.description.substring(0, 50) + \"...</small>\";
                        }
                        html += \"</div>\";
                        return html;
                    }'
                ],
                [
                    'data' => 'category', 
                    'label' => 'Category', 
                    'searchable' => true,
                    'render' => 'function(data, type, row) {
                        return data ? \"<span class=\'px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800\'>\" + data.toUpperCase() + \"</span>\" : \"-\";
                    }'
                ],
                [
                    'data' => 'service_frequency', 
                    'label' => 'Service Frequency', 
                    'searchable' => false,
                    'render' => 'function(data, type, row) {
                        if (!row.service_frequency) return \"-\";
                        let sf = row.service_frequency;
                        return sf.name + \" (\" + sf.frequency_months + \" months, \" + sf.frequency_times_per_month + \" times)\";
                    }'
                ],
                [
                    'data' => 'daily_price', 
                    'label' => 'Daily Price', 
                    'searchable' => true,
                    'render' => 'function(data, type, row) {
                        return data ? \"Rp \" + new Intl.NumberFormat(\"id-ID\", {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(data) : \"-\";
                    }'
                ],
                [
                    'data' => 'monthly_price', 
                    'label' => 'Monthly Price', 
                    'searchable' => true,
                    'render' => 'function(data, type, row) {
                        return data ? \"Rp \" + new Intl.NumberFormat(\"id-ID\", {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(data) : \"-\";
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
                ],
                [
                    'data' => 'created_by', 
                    'label' => 'Created By', 
                    'searchable' => false,
                    'render' => 'function(data, type, row) {
                        return row.created_by ? row.created_by.name : \"-\";
                    }'
                ],
                [
                    'data' => 'updated_at', 
                    'label' => 'Updated', 
                    'searchable' => false,
                    'render' => 'function(data, type, row) {
                        if (!data) return \"-\";
                        let date = new Date(data);
                        let day = date.getDate().toString().padStart(2, \"0\");
                        let month = (date.getMonth() + 1).toString().padStart(3, \"0\");
                        let year = date.getFullYear();
                        let hours = date.getHours().toString().padStart(2, \"0\");
                        let minutes = date.getMinutes().toString().padStart(2, \"0\");
                        return day + \"/\" + month + \"/\" + year + \"<br>at \" + hours + \".\" + minutes + \" WIB\";
                    }'
                ]
            ]"
        />
        
    </div>
</div>

@push('scripts')
<script>
// Bulk delete function
function bulkDeleteRentals() {
    let selectedIds = [];
    $('.row-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    if (selectedIds.length === 0) {
        alert('Pilih minimal satu rental untuk dihapus');
        return;
    }
    
    if (confirm(`Apakah Anda yakin ingin menghapus ${selectedIds.length} rental?`)) {
        // Implement delete logic here
        console.log('Deleting IDs:', selectedIds);
        
        fetch('{{ route("warehouse.master-rentals.bulk-delete") }}', {
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
                alert('Rentals berhasil dihapus');
                $('#masterRentalsTable').DataTable().ajax.reload();
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

// Add rental modal placeholder
function openAddRentalModal() {
    alert('Implementasi modal Add Rental - gunakan modal yang sudah ada di file index.blade.php original');
}
</script>
@endpush
@endsection

