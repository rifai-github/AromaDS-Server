@extends('layouts.app')

@section('title', 'Marketing Targets')
@section('breadcrumb', 'Home / Finance / Marketing Targets')

@section('content')
<style>
    html, body { overflow-x: hidden; max-width: 100vw; }
    .btn { padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; border: none; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-primary { background-color: #214589; color: white; }
    .btn-secondary { background-color: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }
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
    .badge-warning { background-color: #fef3c7; color: #d97706; }
    .badge-info { background-color: #dbeafe; color: #2563eb; }
    .action-buttons { display: flex; gap: 8px; }
</style>

<div class="container-fluid">
    <div class="page-header">
        <div class="container-fluid">
            <h1 class="page-title"><i class="fas fa-bullseye"></i> Marketing Targets</h1>
            <p class="page-subtitle">Manage marketing targets and achievements</p>
        </div>
    </div>

    <div class="container-fluid">
        <div class="mb-4">
            <a href="{{ route('finance.marketing-targets.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Target
            </a>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">Marketing Targets List</h3>
            </div>
            <div class="table-wrapper">
                <table class="data-table" id="marketingTargetsTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Period</th>
                            <th>Target Type</th>
                            <th>Target Amount</th>
                            <th>Achieved Amount</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Last Updated By</th>
                            <th>Last Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($marketingTargets as $target)
                        <tr>
                            <td><strong>{{ $target->user->name ?? 'N/A' }}</strong></td>
                            <td>{{ $target->achievementPeriod->period_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-info">
                                    {{ ucfirst($target->target_type) }}
                                </span>
                            </td>
                            <td>Rp {{ number_format($target->target_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($target->achieved_amount, 0, ',', '.') }}</td>
                            <td>
                                @php
                                    $progress = $target->target_amount > 0 ? ($target->achieved_amount / $target->target_amount) * 100 : 0;
                                @endphp
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ min($progress, 100) }}%">
                                        {{ number_format($progress, 1) }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($target->is_locked)
                                    <span class="badge badge-warning">Locked</span>
                                @else
                                    <span class="badge badge-success">Active</span>
                                @endif
                            </td>
                            <td class="text-sm text-gray-500">{{ $target->createdBy->name ?? '-' }}</td>
                            <td class="text-sm text-gray-500">{!! $target->created_at ? $target->created_at->format('d/M/Y<br>at H.i') . ' WIB' : '-' !!}</td>
                            <td class="text-sm text-gray-500">{{ $target->updatedBy->name ?? '-' }}</td>
                            <td class="text-sm text-gray-500">{!! $target->updated_at ? $target->updated_at->format('d/M/Y<br>at H.i') . ' WIB' : '-' !!}</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('finance.marketing-targets.show', $target) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('finance.marketing-targets.edit', $target) }}" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if(!$target->is_locked)
                                    <form action="{{ route('finance.marketing-targets.lock', $target) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary" title="Lock Target">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    </form>
                                    @else
                                    <form action="{{ route('finance.marketing-targets.unlock', $target) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary" title="Unlock Target">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    </form>
                                    @endif
                                    <form action="{{ route('finance.marketing-targets.destroy', $target) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah kamu yakin?');">
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
                            <td colspan="12" style="text-align: center; padding: 40px;">
                                <p>No marketing targets found. <a href="{{ route('finance.marketing-targets.create') }}">Create one</a></p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($marketingTargets->hasPages())
        <div class="mt-4">
            {{ $marketingTargets->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#marketingTargetsTable').DataTable({
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true
    });
});
</script>
@endpush
@endsection

