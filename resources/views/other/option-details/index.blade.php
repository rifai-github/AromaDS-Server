@extends('layouts.app')

@section('title', 'Option Details - ' . $masterOption->name)
@section('breadcrumb', 'Home / Other / Master Options / ' . $masterOption->name . ' / Details')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
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
        background-color: #e5e7eb;
        color: #4b5563;
    }

    .btn-danger {
        background-color: #dc2626;
        color: white;
    }

    .btn-danger:hover {
        background-color: #b91c1c;
    }

    /* Table Styles */
    .table-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th,
    .table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }

    .table th {
        background-color: #f9fafb;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }

    .table td {
        color: #6b7280;
        font-size: 14px;
    }

    .table tbody tr:hover {
        background-color: #f9fafb;
    }

    /* Status Badge */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-active {
        background-color: #dcfce7;
        color: #166534;
    }

    .status-inactive {
        background-color: #fee2e2;
        color: #dc2626;
    }

    /* Header */
    .page-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 24px;
        padding: 0 24px;
    }

    .page-title {
        font-size: 24px;
        font-weight: 600;
        color: #111827;
        margin: 0;
    }

    .page-subtitle {
        font-size: 14px;
        color: #6b7280;
        margin-top: 4px;
    }

    /* Actions */
    .actions {
        display: flex;
        gap: 8px;
    }

    .action-btn {
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .action-edit {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .action-edit:hover {
        background-color: #bfdbfe;
    }

    .action-delete {
        background-color: #fee2e2;
        color: #dc2626;
    }

    .action-delete:hover {
        background-color: #fecaca;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 48px 24px;
        color: #6b7280;
    }

    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    }

    .empty-state-description {
        font-size: 14px;
        margin-bottom: 24px;
    }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $masterOption->name }} - Option Details</h1>
        <p class="page-subtitle">Manage option details for {{ $masterOption->name }}</p>
    </div>
    <div class="actions">
        <a href="{{ route('other.option-details.create', $masterOption) }}" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Add New Detail
        </a>
        <a href="{{ route('other.master-options.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back to Master Options
        </a>
    </div>
</div>

<div class="table-container">
    @if($optionDetails->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Option Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($optionDetails as $detail)
                    <tr>
                        <td>
                            <div class="font-medium text-gray-900">{{ $detail->option_name }}</div>
                        </td>
                        <td>
                            <div class="text-sm text-gray-600">
                                {{ $detail->option_description ?: 'No description' }}
                            </div>
                        </td>
                        <td>
                            <span class="status-badge {{ $detail->is_active ? 'status-active' : 'status-inactive' }}">
                                {{ $detail->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('other.option-details.edit', $detail) }}" 
                                   class="action-btn action-edit">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>
                                <form action="{{ route('other.option-details.destroy', $detail) }}" 
                                      method="POST" 
                                      style="display: inline;"
                                      onsubmit="return confirm('Apakah kamu yakin ingin menghapus option detail ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn action-delete">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="empty-state-title">No Option Details Found</div>
            <div class="empty-state-description">
                This master option doesn't have any details yet. Add some option details to get started.
            </div>
            <a href="{{ route('other.option-details.create', $masterOption) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add First Detail
            </a>
        </div>
    @endif
</div>
@endsection
