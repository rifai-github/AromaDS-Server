@extends('layouts.app')

@section('title', 'Activity Logs')
@section('breadcrumb', 'Home / Other / Activity Logs')

@section('content')
<style>
    .table-row-hover:hover {
        background-color: #eff6ff !important; /* Light blue background */
        transition: background-color 0.2s ease;
    }
    
    .delete-btn {
        background-color: #f3f4f6 !important; /* Light gray background */
        color: #6b7280 !important; /* Dark gray text */
        border: 1px solid #d1d5db !important; /* Light gray border */
        padding: 8px 16px !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        transition: background-color 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        outline: none !important;
        box-shadow: none !important;
    }
    
    .delete-btn:hover {
        background-color: #e5e7eb !important; /* Slightly darker gray on hover */
        color: #4b5563 !important;
    }
    
    .delete-btn:focus {
        background-color: #e5e7eb !important;
        color: #4b5563 !important;
        outline: none !important;
    }
    
    .delete-btn i {
        color: #6b7280 !important; /* Dark gray icon */
    }
    
    .delete-btn span {
        color: #6b7280 !important; /* Dark gray text */
    }
    
    .add-btn {
        background-color: #214589 !important;
        color: white !important;
        border: none !important;
        padding: 8px 16px !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        transition: background-color 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        outline: none !important;
        box-shadow: none !important;
    }
    
    .add-btn:hover {
        background-color: #1e3a8a !important;
        color: white !important;
    }
    
    .add-btn:focus {
        background-color: #1e3a8a !important;
        color: white !important;
        outline: none !important;
    }
    
    .add-btn i {
        color: white !important;
    }
    
    .add-btn span {
        color: white !important;
    }
</style>

<div class="flex flex-col   w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Activity Logs Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] pt-[7px] pr-[7px] pb-[7px] pl-[7px] md:pt-[10px] md:pr-[10px] md:pb-[10px] md:pl-[10px] lg:pt-[14px] lg:pr-[14px] lg:pb-[14px] lg:pl-[14px]">
            <div class="flex flex-row justify-start items-center w-full">
                <p class="text-[8px] md:text-[12px] lg:text-[16px] font-inter font-medium leading-[10px] md:leading-[15px] lg:leading-[20px] text-left text-[#214589] w-auto">Activity Logs</p>
            </div>
            
            <div class="flex flex-row justify-end items-center w-auto">
                <button class="add-btn" onclick="window.location.href='{{ route('system.activity-logs.create') }}'">
                    <i class="fas fa-plus text-white text-[7px] md:text-[10px] lg:text-[14px]"></i>
                    <span class="text-[7px] md:text-[10px] lg:text-[14px] font-inter font-medium leading-[8px] md:leading-[12px] lg:leading-[17px] text-center text-white">Create Log</span>
                </button>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full pt-[4px] pr-[4px] pb-[4px] pl-[4px] md:pt-[6px] md:pr-[6px] md:pb-[6px] md:pl-[6px] bg-white">
            <div class="flex flex-row justify-start items-center w-full">
                <div class="flex flex-row justify-start items-center w-auto ml-[10px] md:ml-[14px] lg:ml-[19px]">
                    <div class="flex flex-row   w-auto">
                        <input type="checkbox" id="selectAll" class="w-[10px] h-[10px] md:w-[15px] md:h-[15px] lg:w-[20px] lg:h-[20px] bg-white border border-[#888888] rounded-[4px] cursor-pointer">
                        <div class="flex flex-row justify-start items-center align-self-start w-full pr-[5px] pl-[5px] md:pr-[7px] md:pl-[7px] lg:pr-[10px] lg:pl-[10px]">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] w-auto ml-[5px] md:ml-[7px] lg:ml-[10px] cursor-pointer" onclick="document.getElementById('selectAll').click()">Select all</p>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Button - Gray styling -->
                <button class="delete-btn ml-4" onclick="deleteSelected()" style="background-color: #f3f4f6 !important; color: #6b7280 !important; border: 1px solid #d1d5db !important; padding: 8px 16px !important; border-radius: 6px !important; cursor: pointer !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; font-size: 12px !important; font-weight: 500 !important;">
                    <i class="fas fa-trash" style="color: #6b7280 !important;"></i>
                    <span style="color: #6b7280 !important;">Delete</span>
                </button>
            </div>
            
        </div>
        
        <!-- Table Container with Horizontal Scroll -->
        <div class="w-full bg-white rounded-b-[10px] overflow-x-auto">
            <table class="w-full min-w-[1600px] border-collapse">
                <!-- Table Header -->
                <thead>
                    <tr class="bg-[#225fd3]">
                        <th class="w-[50px] p-2 text-left" data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-[10px] h-[10px] md:w-[15px] md:h-[15px] lg:w-[20px] lg:h-[20px] bg-white border border-[#888888] rounded-[4px] cursor-pointer">
                        </th>
                        <th class="w-[150px] p-2 text-left" data-column="user.name">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-bold leading-[7px] md:leading-[11px] lg:leading-[15px] text-white">User</p>
                        </th>
                        <th class="w-[120px] p-2 text-left" data-column="action">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-bold leading-[7px] md:leading-[11px] lg:leading-[15px] text-white">Action</p>
                        </th>
                        <th class="w-[150px] p-2 text-left" data-column="module">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-bold leading-[7px] md:leading-[11px] lg:leading-[15px] text-white">Module</p>
                        </th>
                        <th class="w-[200px] p-2 text-left" data-column="description">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-bold leading-[7px] md:leading-[11px] lg:leading-[15px] text-white">Description</p>
                        </th>
                        <th class="w-[120px] p-2 text-left" data-column="ip_address">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-bold leading-[7px] md:leading-[11px] lg:leading-[15px] text-white">IP Address</p>
                        </th>
                        <th class="w-[120px] p-2 text-left" data-column="user_agent">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-bold leading-[7px] md:leading-[11px] lg:leading-[15px] text-white">User Agent</p>
                        </th>
                        <th class="w-[150px] p-2 text-left" data-column="created_at" data-type="date">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-bold leading-[7px] md:leading-[11px] lg:leading-[15px] text-white">Created At</p>
                        </th>
                        <th class="w-[100px] p-2 text-left" data-column="status">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-bold leading-[7px] md:leading-[11px] lg:leading-[15px] text-white">Status</p>
                        </th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($activityLogs as $log)
                    <tr class="table-row-hover cursor-pointer border-b border-gray-200" onclick="window.location.href='{{ route('system.activity-logs.show', $log->id) }}'">
                        <td class="w-[50px] p-2 text-center">
                            <input type="checkbox" class="row-checkbox w-[10px] h-[10px] md:w-[15px] md:h-[15px] lg:w-[20px] lg:h-[20px] bg-white border border-[#888888] rounded-[4px] cursor-pointer" onclick="event.stopPropagation()">
                        </td>
                        <td class="w-[150px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $log->user->name ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[120px] p-2">
                            @php
                                $actionColors = [
                                    'CREATE' => 'badge-success',
                                    'UPDATE' => 'badge-warning',
                                    'DELETE' => 'badge-danger',
                                    'LOGIN' => 'badge-info',
                                    'LOGOUT' => 'badge-secondary',
                                    'VIEW' => 'badge-primary'
                                ];
                                $actionColor = $actionColors[strtoupper($log->action)] ?? 'badge-secondary';
                            @endphp
                            <span class="badge {{ $actionColor }}">{{ $log->action ?? 'N/A' }}</span>
                        </td>
                        <td class="w-[150px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $log->module ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[200px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $log->description ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[120px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $log->ip_address ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[120px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ Str::limit($log->user_agent, 30) ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[150px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">
                                {!! $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('d/M/Y') . '<br />at ' . \Carbon\Carbon::parse($log->created_at)->format('H.i') . ' WIB' : 'N/A' !!}
                            </p>
                        </td>
                        <td class="w-[100px] p-2">
                            @if($log->status == 'success')
                                <span class="badge badge-success">Success</span>
                            @elseif($log->status == 'error')
                                <span class="badge badge-danger">Error</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($log->status) ?? 'N/A' }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="p-8 text-center">
                            <p class="text-[14px] md:text-[16px] lg:text-[18px] font-inter font-normal text-center text-[#666]">No activity logs found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if($activityLogs->currentPage() > 1)
                    <a href="{{ $activityLogs->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if($activityLogs->lastPage() > 0)
                    @php
                        $start = max(1, $activityLogs->currentPage() - 2);
                        $end = min($activityLogs->lastPage(), $activityLogs->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $activityLogs->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $activityLogs->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $activityLogs->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $activityLogs->lastPage())
                            @if($end < $activityLogs->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $activityLogs->url($activityLogs->lastPage()) }}" class="page-number">{{ $activityLogs->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if($activityLogs->currentPage() < $activityLogs->lastPage())
                    <a href="{{ $activityLogs->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $activityLogs->currentPage() }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $activityLogs->lastPage() }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
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
        showWarningDialog('Pilih minimal satu item yang ingin dihapus.');
        return;
    }
    
    showConfirmDialog(
        'Hapus Item Terpilih?',
        'Apakah Anda yakin ingin menghapus item yang dipilih?',
        'Ya, hapus',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;
        console.log('Deleting selected items...');
    });
}

// Ensure rows are clickable
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function(e) {
            if (!e.target.classList.contains('row-checkbox')) {
                const showUrl = this.getAttribute('onclick').match(/window\.location\.href='([^']+)'/)[1];
                window.location.href = showUrl;
            }
        });
    });
});
</script>
@endsection
