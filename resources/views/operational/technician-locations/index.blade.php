@extends('layouts.app')

@section('title', 'Technician Locations')
@section('breadcrumb', 'Home / Operational / Technician Locations')

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

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Delete Button Hover - Blue */
    .btn-secondary:hover {
        background-color: #214589 !important;
        color: white !important;
        border-color: #214589 !important;
    }

    /* Specific delete button styling */
    .btn-delete:hover {
        background-color: #214589 !important;
        color: white !important;
        border-color: #214589 !important;
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
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }

    /* Custom scrollbar */
    .table-container::-webkit-scrollbar {
        height: 8px;
    }

    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .table-container::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Scroll indicator */
    .table-container::after {
        content: '← Scroll horizontally to see more →';
        position: absolute;
        bottom: -25px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 12px;
        color: #666;
        opacity: 0.7;
    }

    /* Responsive Table */
    .responsive-table {
        min-width: 1600px;
        table-layout: auto;
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
        height: auto;
    }

    .responsive-table th,
    .responsive-table td {
        padding: 12px 8px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
        font-size: 14px;
        line-height: 1.4;
    }

    .responsive-table th {
        background-color: #214589;
        color: white;
        font-weight: 600;
        font-size: 13px;
        position: sticky;
        top: 0;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: visible;
        text-overflow: unset;
    }

    .responsive-table td {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }

    .responsive-table tbody tr {
        cursor: pointer;
    }

    .responsive-table tbody {
        height: auto;
    }

    /* Column widths for better layout */
    .responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 50px; min-width: 50px; }
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 180px; min-width: 180px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 120px; min-width: 120px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(11), .responsive-table td:nth-child(11) { width: 120px; min-width: 120px; }

    /* Pagination Specific Styles */
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: nowrap;
        white-space: nowrap;
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
    }

    .pagination-btn {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid #d1d5db;
        background-color: #f9fafb;
        color: #374151;
    }

    .pagination-btn:hover:not(:disabled) {
        background-color: #f3f4f6;
        border-color: #9ca3af;
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        backdrop-filter: blur(2px);
    }

    .modal-overlay.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 90vw;
        max-height: 90vh;
        width: 600px;
        overflow: hidden;
        position: relative;
    }

    .modal-header {
        background: #214589;
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 20;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s ease;
    }

    .modal-close:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .modal-body {
        padding: 20px;
        overflow-y: auto;
        max-height: calc(90vh - 140px);
    }

    .modal-footer {
        padding: 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: center;
        gap: 20px;
        position: sticky;
        bottom: 0;
    }

    /* Delete Confirmation Modal */
    .delete-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }

    .delete-modal-overlay.show {
        display: flex;
    }

    .delete-modal-container {
        background: #f0f9ff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    .delete-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }

    .delete-icon {
        width: 80px;
        height: 80px;
    }

    .delete-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }

    .delete-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    .delete-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }

    .btn-cancel {
        background-color: white;
        color: #1e40af;
        border: 2px solid #1e40af;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-cancel:hover {
        background-color: #f8fafc;
        border-color: #1e3a8a;
        color: #1e3a8a;
    }

    .btn-hide {
        background-color: #1e40af;
        color: white;
        border: 2px solid #1e40af;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-hide:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
    }

    /* Error Modal */
    .error-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 3000;
        align-items: center;
        justify-content: center;
    }

    .error-modal-overlay.show {
        display: flex;
    }

    .error-modal-container {
        background: #f0fdf4;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    /* Success Modal */
    .success-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 4000;
        align-items: center;
        justify-content: center;
    }

    .success-modal-overlay.show {
        display: flex;
    }

    .success-modal-container {
        background: #f0fdf4;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    /* Form Input Styling */
    input[type="date"], input[type="text"], select, textarea {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    input[type="date"]:focus, input[type="text"]:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .space-y-4 > * + * {
        margin-top: 1rem;
    }

    .space-y-6 > * + * {
        margin-top: 1.5rem;
    }

    /* Grid Layout for Modal */
    .grid {
        display: grid;
    }

    .grid-cols-1 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .md\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .gap-6 {
        gap: 1.5rem;
    }

    /* Modal Section Styles */
    .modal-section {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        background: #f9fafb;
    }

    .modal-section:last-child {
        margin-bottom: 0;
    }

    .modal-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 1px solid #d1d5db;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }
    
    .form-input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
        box-sizing: border-box;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }
    
    .form-textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    /* Detail View Styles */
    .detail-item {
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .detail-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .detail-value {
        color: #6b7280;
        font-size: 14px;
        line-height: 1.5;
        margin-top: 4px;
        word-wrap: break-word;
    }

    /* Status Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .badge-warning {
        background-color: #fef3c7;
        color: #92400e;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-info {
        background-color: #dbeafe;
        color: #1e40af;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 1600px;
        }
        
        .controls-row {
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
        }
        
        .controls-left {
            justify-content: space-between;
        }
        
        .pagination-controls {
            justify-content: center;
            flex-wrap: wrap;
            gap: 5px;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        .page-dropdown-container {
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Header responsive */
        .flex.flex-row.justify-between {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .flex.flex-row.justify-between > div:first-child {
            width: 100%;
        }
        
        .flex.flex-row.justify-between > div:last-child {
            width: 100%;
            justify-content: flex-start;
        }
    }

    /* Tablet and small screen responsive */
    @media (max-width: 1024px) and (min-width: 769px) {
        .flex-wrap {
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .flex-wrap > div {
            flex: 1 1 calc(50% - 0.5rem);
            min-width: 200px;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Module Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Technician Locations</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Location</span>
                    <span class="md:hidden">Add New</span>
                </button>
            </div>
        </div>

        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row justify-start items-center w-full">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center w-auto">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        <div class="flex flex-row justify-start items-center w-full px-2">
                            <p class="text-sm font-normal text-gray-700 w-auto ml-2 cursor-pointer" onclick="document.getElementById('selectAll').click()">Select all</p>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Button -->
                <button class="btn btn-secondary btn-sm ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
            
        </div>

        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="technician.name">Technician</th>
                        <th data-column="jobSchedule.job_number">Job Schedule</th>
                        <th data-column="activity_type">Activity Type</th>
                        <th data-column="location">Location</th>
                        <th data-column="coordinates">Coordinates</th>
                        <th data-column="movement_type">Movement</th>
                        <th data-column="accuracy" data-type="numeric">Accuracy</th>
                        <th data-column="timestamp" data-type="date">Timestamp</th>
                        <th data-column="status">Status</th>
                         
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($locations ?? [] as $location)
                    <tr data-id="{{ $location->id }}" onclick="openViewModal({{ $location->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $location->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-blue-600 text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $location->technician->name ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-500">{{ $location->technician->email ?? 'No email' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($location->jobSchedule)
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900">{{ $location->jobSchedule->job_number ?? 'No job number' }}</div>
                                    <div class="text-gray-500">{{ $location->jobSchedule->building_name ?? 'No building' }}</div>
                                </div>
                            @else
                                <span class="text-gray-400">No Job</span>
                            @endif
                        </td>
                        <td>
                            @if($location->activity_type)
                                <span class="badge badge-info">{{ $location->activity_type_label ?? ucfirst($location->activity_type) }}</span>
                            @else
                                <span class="text-gray-400">Unknown</span>
                            @endif
                        </td>
                        <td>
                            @if($location->location_address)
                                <div class="text-sm">
                                    <i class="fas fa-map-marker-alt text-red-500 mr-1"></i>
                                    <span class="truncate max-w-32">{{ $location->location_address }}</span>
                                </div>
                            @else
                                <span class="text-gray-400">No address</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-sm font-mono">
                                <div>Lat: {{ number_format($location->latitude, 6) }}</div>
                                <div>Lng: {{ number_format($location->longitude, 6) }}</div>
                            </div>
                        </td>
                        <td>
                            @if($location->is_moving)
                                <span class="badge badge-warning">
                                    <i class="fas fa-walking mr-1"></i>Moving
                                </span>
                            @else
                                <span class="badge badge-info">
                                    <i class="fas fa-pause mr-1"></i>Stationary
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($location->accuracy)
                                <span class="badge badge-info">{{ $location->accuracy }}m</span>
                            @else
                                <span class="text-gray-400">Unknown</span>
                            @endif
                        </td>
                        <td>
                            <div class="text-sm">
                                <div>{{ $location->timestamp->format('d/mmm/Y') }}</div>
                                <div class="text-gray-500">{{ $location->timestamp->format('H:i:s') }}</div>
                            </div>
                        </td>
                        <td>
                            @php
                                $isRecent = $location->timestamp->diffInMinutes(now()) <= 5;
                                $isToday = $location->timestamp->isToday();
                            @endphp
                            @if($isRecent)
                                <span class="badge badge-success">Online</span>
                            @elseif($isToday)
                                <span class="badge badge-warning">Today</span>
                            @else
                                <span class="badge badge-danger">Offline</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <button class="btn btn-primary btn-sm" onclick="openViewModal({{ $location->id }})" title="View Details" onclick="event.stopPropagation()">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm" onclick="openEditModal({{ $location->id }})" title="Edit" onclick="event.stopPropagation()">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No technician locations found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if(isset($locations) && $locations->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            {{ $locations->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Lihat Lokasi Teknisi</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content will be loaded here -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Modal footer content will be loaded here -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h3 class="delete-modal-title">Sembunyikan Lokasi Teknisi</h3>
        <p class="delete-modal-description" id="deleteMessage">Apakah kamu yakin ingin menyembunyikan lokasi ini? Aksi ini masih bisa dibatalkan nanti.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Ya, Sembunyikan</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h3 class="delete-modal-title">Ups, Terjadi Kesalahan</h3>
        <p class="delete-modal-description" id="errorMessage">Lokasi tidak dapat disembunyikan. Silakan coba lagi.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeErrorModal()">Tutup</button>
            <button class="btn btn-hide" onclick="retryDelete()">Coba Lagi</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h3 class="delete-modal-title">Berhasil</h3>
        <p class="delete-modal-description" id="successMessage">Lokasi berhasil disembunyikan.</p>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;

// Modal functions
function openModal(title) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
    document.getElementById('modalBody').innerHTML = '';
    document.getElementById('modalFooter').innerHTML = '';
}

// CRUD Modal functions
function openCreateModal() {
    openModal('Tambah Lokasi Teknisi');
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Basic Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Technician *</label>
                        <select name="technician_id" class="form-input" required id="technicianSelect">
                            <option value="">Memuat teknisi...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Job Schedule</label>
                        <select name="job_schedule_id" class="form-input" id="jobScheduleSelect">
                            <option value="">Memuat jadwal kerja...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Activity Type</label>
                        <select name="activity_type" class="form-input">
                            <option value="">Pilih Aktivitas</option>
                            <option value="start_work">Start Work</option>
                            <option value="traveling">Traveling</option>
                            <option value="on_site">On Site</option>
                            <option value="working">Working</option>
                            <option value="break">Break</option>
                            <option value="end_work">End Work</option>
                            <option value="emergency">Emergency</option>
                            <option value="location_update">Location Update</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Timestamp</label>
                        <input type="datetime-local" name="timestamp" class="form-input" value="${new Date().toISOString().slice(0, 16)}">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Location Details</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Latitude *</label>
                        <input type="number" name="latitude" class="form-input" step="any" placeholder="e.g. -6.200000" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude *</label>
                        <input type="number" name="longitude" class="form-input" step="any" placeholder="e.g. 106.816666" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location Address</label>
                        <input type="text" name="location_address" class="form-input" placeholder="e.g. Jl. Sudirman No. 1, Jakarta">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Accuracy (meters)</label>
                        <input type="number" name="accuracy" class="form-input" step="any" placeholder="e.g. 5.0">
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Device Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Battery Level (%)</label>
                        <input type="number" name="battery_level" class="form-input" min="0" max="100" placeholder="e.g. 85">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Network Type</label>
                        <select name="network_type" class="form-input">
                            <option value="">Select Network</option>
                            <option value="wifi">WiFi</option>
                            <option value="4g">4G</option>
                            <option value="5g">5G</option>
                            <option value="3g">3G</option>
                            <option value="2g">2G</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Speed (km/h)</label>
                        <input type="number" name="speed" class="form-input" step="any" placeholder="e.g. 25.5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Heading (degrees)</label>
                        <input type="number" name="heading" class="form-input" min="0" max="360" placeholder="e.g. 180">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Altitude (meters)</label>
                        <input type="number" name="altitude" class="form-input" step="any" placeholder="e.g. 10.5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Is Moving</label>
                        <select name="is_moving" class="form-input">
                            <option value="false">Stationary</option>
                            <option value="true">Moving</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <div class="modal-section-title">Additional Data</div>
                <div class="form-group">
                    <label class="form-label">Device Info (JSON)</label>
                    <textarea name="device_info" class="form-input form-textarea" rows="3" placeholder='{"device_model": "iPhone 12", "os_version": "iOS 15.0"}'></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Metadata (JSON)</label>
                    <textarea name="metadata" class="form-input form-textarea" rows="3" placeholder='{"app_version": "1.0.0", "gps_provider": "GPS"}'></textarea>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('form').dispatchEvent(new Event('submit'))">
            <i class="fas fa-save"></i>
            Simpan Lokasi
        </button>
    `;
    
    // Load technicians and job schedules dynamically
    loadTechnicians();
    loadJobSchedules();
}

// Function to load technicians dynamically
function loadTechnicians() {
    console.log('Loading technicians...');
    fetch('/operational/technician-locations/technicians', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Technicians data:', data);
            const select = document.getElementById('technicianSelect');
            if (select) {
                select.innerHTML = '<option value="">Pilih Teknisi</option>';
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    console.log('Found technicians:', data.data.length);
                    data.data.forEach(technician => {
                        const option = document.createElement('option');
                        option.value = technician.id;
                        option.textContent = technician.name;
                        select.appendChild(option);
                    });
                } else {
                    console.log('No technicians found or invalid data structure');
                    select.innerHTML = '<option value="">Tidak ada teknisi</option>';
                }
            } else {
                console.error('Technician select element not found');
            }
        })
        .catch(error => {
            console.error('Error loading technicians:', error);
            const select = document.getElementById('technicianSelect');
            if (select) {
                select.innerHTML = '<option value="">Gagal memuat teknisi</option>';
            }
        });
}

// Function to load job schedules dynamically
function loadJobSchedules() {
    console.log('Loading job schedules...');
    fetch('/operational/job-schedules', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
        .then(response => {
            console.log('Job schedules response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Job schedules data:', data);
            const select = document.getElementById('jobScheduleSelect');
            if (select) {
                select.innerHTML = '<option value="">Tanpa Jadwal Kerja</option>';
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    console.log('Found job schedules:', data.data.length);
                    data.data.forEach(jobSchedule => {
                        const option = document.createElement('option');
                        option.value = jobSchedule.id;
                        option.textContent = `${jobSchedule.job_number} - ${jobSchedule.building?.nama_gedung || jobSchedule.company_name || 'Unknown Building'}`;
                        select.appendChild(option);
                    });
                } else {
                    console.log('No job schedules found or invalid data structure');
                    select.innerHTML = '<option value="">Tidak ada jadwal kerja</option>';
                }
            } else {
                console.error('Job schedule select element not found');
            }
        })
        .catch(error => {
            console.error('Error loading job schedules:', error);
            const select = document.getElementById('jobScheduleSelect');
            if (select) {
                select.innerHTML = '<option value="">Gagal memuat jadwal kerja</option>';
            }
        });
}

function openViewModal(id) {
    openModal('Lihat Lokasi Teknisi');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/operational/technician-locations/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Basic Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Technician</label>
                            <p class="detail-value">${data.technician?.name || 'Unknown'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Timestamp</label>
                            <p class="detail-value">${new Date(data.timestamp).toLocaleString()}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Activity Type</label>
                            <p class="detail-value">${data.activity_type_label || data.activity_type || 'Unknown'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Job Schedule</label>
                            <p class="detail-value">${data.job_schedule?.job_number || 'No job'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Location Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Latitude</label>
                            <p class="detail-value">${data.latitude}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Longitude</label>
                            <p class="detail-value">${data.longitude}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Accuracy</label>
                            <p class="detail-value">${data.accuracy || 'Unknown'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Movement Status</label>
                            <p class="detail-value">${data.is_moving ? 'Moving' : 'Stationary'}</p>
                        </div>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Location Address</label>
                        <p class="detail-value">${data.location_address || 'No address'}</p>
                    </div>
                </div>
                
                <div class="modal-section">
                    <div class="modal-section-title">Device Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Battery Level</label>
                            <p class="detail-value">${data.battery_level ? data.battery_level + '%' : 'Unknown'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Network Type</label>
                            <p class="detail-value">${data.network_type || 'Unknown'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Speed</label>
                            <p class="detail-value">${data.speed ? data.speed + ' km/h' : 'Unknown'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Heading</label>
                            <p class="detail-value">${data.heading ? data.heading + '°' : 'Unknown'}</p>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">
                    <i class="fas fa-edit"></i>
                    Edit
                </button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Gagal memuat detail.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            `;
        });
}

function openEditModal(id) {
    openModal('Edit Lokasi Teknisi');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/operational/technician-locations/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <form id="form" onsubmit="submitForm(event, ${data.id})">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="modal-section">
                        <div class="modal-section-title">Basic Information</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Technician *</label>
                                <select name="technician_id" class="form-input" required id="editTechnicianSelect">
                            <option value="">Memuat teknisi...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Timestamp *</label>
                                <input type="datetime-local" name="timestamp" class="form-input" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <div class="modal-section-title">Location Details</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Latitude *</label>
                                <input type="number" name="latitude" class="form-input" step="any" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Longitude *</label>
                                <input type="number" name="longitude" class="form-input" step="any" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Accuracy (meters)</label>
                                <input type="number" name="accuracy" class="form-input" step="any">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Location Address</label>
                                <input type="text" name="location_address" class="form-input">
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Populate form data
            document.querySelector('select[name="technician_id"]').value = data.technician_id;
            document.querySelector('input[name="timestamp"]').value = new Date(data.timestamp).toISOString().slice(0, 16);
            document.querySelector('input[name="latitude"]').value = data.latitude;
            document.querySelector('input[name="longitude"]').value = data.longitude;
            document.querySelector('input[name="accuracy"]').value = data.accuracy || '';
            document.querySelector('input[name="location_address"]').value = data.location_address || '';
            
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('form').dispatchEvent(new Event('submit'))">
                    <i class="fas fa-save"></i>
                    Perbarui Lokasi
                </button>
            `;
            
            // Load technicians for edit modal
            loadTechniciansForEdit(data.technician_id);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Gagal memuat data lokasi.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            `;
        });
}

// Function to load technicians for edit modal
function loadTechniciansForEdit(selectedTechnicianId) {
    fetch('/operational/technician-locations/technicians')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('editTechnicianSelect');
            if (select) {
                select.innerHTML = '<option value="">Pilih Teknisi</option>';
                if (data.status === 'success' && data.data) {
                    data.data.forEach(technician => {
                        const option = document.createElement('option');
                        option.value = technician.id;
                        option.textContent = technician.name;
                        if (technician.id == selectedTechnicianId) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                } else {
                    select.innerHTML = '<option value="">Tidak ada teknisi</option>';
                }
            }
        })
        .catch(error => {
            console.error('Error loading technicians:', error);
            const select = document.getElementById('editTechnicianSelect');
            if (select) {
                select.innerHTML = '<option value="">Gagal memuat teknisi</option>';
            }
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // Add CSRF token
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    if (id) {
        formData.append('_method', 'PUT');
    }
    
    const url = id ? `/operational/technician-locations/${id}` : '/operational/technician-locations';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid JSON response');
            }
        });
    })
    .then(result => {
        if (result.status === 'success') {
            closeModal();
            location.reload();
        } else {
            // Handle validation errors
            if (result.errors) {
                let errorMessage = 'Kesalahan validasi:\n';
                for (const [field, messages] of Object.entries(result.errors)) {
                    errorMessage += `${field}: ${messages.join(', ')}\n`;
                }
                alert(errorMessage);
            } else {
                alert('Gagal: ' + (result.message || 'Terjadi kesalahan'));
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal: ' + error.message);
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Apakah kamu yakin ingin menyembunyikan lokasi ini? Aksi ini masih bisa dibatalkan nanti.'
        : `Apakah kamu yakin ingin menyembunyikan ${count} lokasi? Aksi ini masih bisa dibatalkan nanti.`;
    
    document.getElementById('deleteMessage').textContent = message;
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function confirmDelete() {
    closeDeleteModal();
    
        fetch('/operational/technician-locations/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessModal(result.count);
        } else {
            showErrorModal(result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan jaringan.');
    });
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'Lokasi berhasil disembunyikan.'
        : `${count} lokasi berhasil disembunyikan.`;
    
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Auto close after 3 seconds
    successModalTimer = setTimeout(() => {
        closeSuccessModal();
        location.reload();
    }, 3000);
}

function closeSuccessModal() {
    if (successModalTimer) {
        clearTimeout(successModalTimer);
        successModalTimer = null;
    }
    document.getElementById('successModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Error Modal functions
function showErrorModal(message) {
    document.getElementById('errorMessage').textContent = message || 'Lokasi tidak dapat disembunyikan. Silakan coba lagi.';
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retryDelete() {
    closeErrorModal();
    confirmDelete();
}

// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    document.getElementById('headerSelectAll').checked = this.checked;
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
        alert('Silakan pilih minimal satu lokasi yang ingin disembunyikan');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Event listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
        closeErrorModal();
        closeSuccessModal();
    }
});

// Click outside to close modals
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('deleteModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

document.getElementById('errorModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeErrorModal();
    }
});

document.getElementById('successModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuccessModal();
        location.reload();
    }
});

// Add CSS for loading spinner
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>
@endsection
