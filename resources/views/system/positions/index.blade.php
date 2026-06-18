@extends('layouts.app')

@section('title', 'Master Position - System')
@section('breadcrumb', 'Home / System / Master Position')

@section('content')
<style>
    /* Pipeline-style Table */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 10px 10px;
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }
    
    .responsive-table {
        min-width: 100%;
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

    /* Pagination Specific Styles */
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
        
        <!-- Header Section -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4 border-bottom">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Position</h1>
            </div>
            <div class="flex flex-row justify-end items-center gap-2">
                <a href="{{ route('system.positions.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Position
                </a>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container w-full" style="border-radius: 0 0 10px 10px; border: 1px solid #e5e7eb; border-top: none;">
            <table class="responsive-table" style="border-collapse: collapse !important; width: 100%;">
                <thead>
                    <tr style="height: 40px !important;">
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px; width: 50px;">No</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px;">Position Name</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px;">Description</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px;">Status</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px;">Created By</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px;">Created At</th>
                        <th style="background-color: #214589 !important; color: white !important; position: sticky; top: 0; z-index: 20; border: none; padding: 0 10px;">Actions</th>
                    </tr>
                    <tr style="background-color: #f9fafb !important; height: 40px !important;">
                        <th class="p-2 text-center" style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 10px;">
                            <button onclick="resetFilters()" class="btn btn-secondary btn-sm" style="padding: 0; height: 26px; width: 26px; min-width: 26px; display: flex; align-items: center; justify-content: center; background: white; border: 1px solid #d1d5db;">
                                <i class="fas fa-undo" style="font-size: 10px; color: #6b7280;"></i>
                            </button>
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <input type="text" class="table-filter" name="filter[option_name]" value="{{ request('filter.option_name') }}" placeholder="Filter..." onchange="applyFilters()" style="height: 30px !important; font-size: 12px !important; padding: 0 8px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <input type="text" class="table-filter" name="filter[option_description]" value="{{ request('filter.option_description') }}" placeholder="Filter..." onchange="applyFilters()" style="height: 30px !important; font-size: 12px !important; padding: 0 8px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <select class="table-filter" name="filter[is_active]" onchange="applyFilters()" style="height: 30px !important; font-size: 11px !important; padding: 0 4px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                                <option value="">All</option>
                                <option value="1" {{ request('filter.is_active') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('filter.is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <input type="text" class="table-filter" name="filter[createdBy__name]" value="{{ request('filter.createdBy__name') }}" placeholder="Filter..." onchange="applyFilters()" style="height: 30px !important; font-size: 12px !important; padding: 0 8px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 5px;">
                            <input type="date" class="table-filter" name="filter[created_at]" value="{{ request('filter.created_at') }}" onchange="applyFilters()" style="height: 30px !important; font-size: 11px !important; padding: 0 4px !important; background-color: white !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; width: 100%;">
                        </th>
                        <th style="background-color: #f9fafb !important; position: sticky; top: 40px; z-index: 19; border: none; padding: 0 10px;"></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($positions as $index => $position)
                    <tr>
                        <td>{{ $positions->firstItem() + $index }}</td>
                        <td class="font-weight-medium" style="color: #214589;">{{ $position->option_name }}</td>
                        <td class="text-wrap" style="max-width: 300px;">{{ $position->option_description ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $position->is_active ? 'bg-success' : 'bg-secondary' }} text-white text-xs">
                                {{ $position->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $position->createdBy->name ?? '-' }}</td>
                        <td>{{ $position->created_at ? $position->created_at->format('d/M/Y') : '-' }}</td>
                        <td>
                            <div class="flex gap-2">
                                <a href="{{ route('system.positions.edit', $position->id) }}" class="btn btn-secondary btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('system.positions.destroy', $position->id) }}" method="POST" onsubmit="return confirm('Delete this position?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary btn-sm text-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-10 text-gray-500">No positions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        @if($positions->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $positions->withQueryString()->links() }}
        </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
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

document.querySelectorAll('.table-filter').forEach(input => {
    input.addEventListener('keypress', e => { if (e.key === 'Enter') applyFilters(); });
    if (input.tagName === 'SELECT') input.addEventListener('change', applyFilters);
});
</script>
@endpush
@endsection
