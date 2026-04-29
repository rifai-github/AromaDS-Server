@extends('layouts.app')

@section('title', 'Marketing Levels')
@section('breadcrumb', 'Home / Finance / Marketing Levels')

@section('content')
<style>
    html, body { overflow-x: hidden; max-width: 100vw; }
    *, *::before, *::after { box-sizing: border-box; }
    .btn { padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-primary { background-color: #214589; color: white; }
    .btn-primary:hover { background-color: #1e3a8a; }
    .btn-secondary { background-color: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }
    .btn-secondary:hover { background-color: #e5e7eb; color: #4b5563; }
    .btn-success { background-color: #10b981; color: white; }
    .btn-warning { background-color: #f59e0b; color: white; }
    .btn-danger { background-color: #ef4444; color: white; }
    .btn-info { background-color: #3b82f6; color: white; }
    .btn-sm { padding: 6px 12px; font-size: 12px; }
    .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 0; margin-bottom: 30px; }
    .page-title { font-size: 28px; font-weight: 700; margin: 0; }
    .page-subtitle { font-size: 16px; opacity: 0.9; margin: 8px 0 0 0; }
    .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; }
    .table-header { background: #f8fafc; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
    .table-title { font-size: 18px; font-weight: 600; color: #1f2937; margin: 0; }
    .table-wrapper { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .data-table th { background: #f1f5f9; color: #475569; font-weight: 600; padding: 12px 16px; text-align: left; border-bottom: 2px solid #e2e8f0; }
    .data-table td { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; color: #1f2937; }
    .data-table tbody tr:hover { background-color: #f8fafc; }
    .badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
    .badge-success { background-color: #dcfce7; color: #16a34a; }
    .badge-danger { background-color: #fee2e2; color: #dc2626; }
    .action-buttons { display: flex; gap: 8px; }
</style>

<div class="container-fluid">
    <div class="page-header">
        <div class="container-fluid">
            <h1 class="page-title"><i class="fas fa-users"></i> Marketing Levels</h1>
            <p class="page-subtitle">Manage marketing level configurations</p>
        </div>
    </div>

    <div class="container-fluid">
        <div class="mb-4">
            <a href="{{ route('finance.marketing-levels.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Marketing Level
            </a>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">Marketing Levels List</h3>
            </div>
            <div class="table-wrapper">
                <table class="data-table" id="marketingLevelsTable">
                    <thead>
                        <tr>
                            <th>Level Code</th>
                            <th>Level Name</th>
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Last Updated By</th>
                            <th>Last Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($marketingLevels as $level)
                        <tr>
                            <td><strong>{{ $level->level_code }}</strong></td>
                            <td>{{ $level->level_name }}</td>
                            <td>{{ $level->sort_order }}</td>
                            <td>
                                @if($level->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="text-sm text-gray-500">{{ $level->createdBy->name ?? '-' }}</td>
                            <td class="text-sm text-gray-500">{!! $level->created_at ? $level->created_at->format('d M Y<br>at H.i') . ' WIB' : '-' !!}</td>
                            <td class="text-sm text-gray-500">{{ $level->updatedBy->name ?? '-' }}</td>
                            <td class="text-sm text-gray-500">{!! $level->updated_at ? $level->updated_at->format('d M Y<br>at H.i') . ' WIB' : '-' !!}</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('finance.marketing-levels.show', $level) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('finance.marketing-levels.edit', $level) }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('finance.marketing-levels.destroy', $level) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah kamu yakin?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <p>No marketing levels found. <a href="{{ route('finance.marketing-levels.create') }}">Create one</a></p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($marketingLevels->hasPages())
        <div class="mt-4">
            {{ $marketingLevels->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#marketingLevelsTable').DataTable({
        order: [[2, 'asc']],
        pageLength: 25,
        responsive: true
    });
});
</script>
@endpush
@endsection

