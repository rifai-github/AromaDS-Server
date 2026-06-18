@extends('layouts.app')

@section('title', 'Master Building')
@section('breadcrumb', 'Home / Operational / Master Building')

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
        white-space: normal;
        word-wrap: break-word;
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
        word-break: break-word;
        max-width: 250px;
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
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 280px; min-width: 280px; } /* Building Name */
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 150px; min-width: 150px; } /* Jenis Alamat */
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 350px; min-width: 350px; } /* Address */
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 180px; min-width: 180px; } /* City */
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 220px; min-width: 220px; } /* Province */
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 100px; min-width: 100px; } /* Floors */
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 120px; min-width: 120px; } /* Area */
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 120px; min-width: 120px; } /* Status */
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 150px; min-width: 150px; } /* Created By */
    .responsive-table th:nth-child(11), .responsive-table td:nth-child(11) { width: 150px; min-width: 150px; } /* Created At */
    .responsive-table th:nth-child(12), .responsive-table td:nth-child(12) { width: 150px; min-width: 150px; } /* Updated By */
    .responsive-table th:nth-child(13), .responsive-table td:nth-child(13) { width: 150px; min-width: 150px; } /* Updated At */

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

    /* Modal scrollbar styling */
    .modal-body::-webkit-scrollbar {
        width: 8px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }


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
        background: #fef2f2;
        border: 2px solid #fecaca;
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
        border: 2px solid #bbf7d0;
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
    input[type="date"], input[type="text"], select {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }
    
    input[type="date"]:focus, input[type="text"]:focus, select:focus {
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
    
    /* Validation Error Style */
    .is-invalid {
        border-color: #ef4444 !important;
        background-color: #fef2f2;
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Module Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Building</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Building</span>
                    <span class="md:hidden">Add New</span>
            </button>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white controls-row">
            <div class="flex flex-row justify-start items-center w-full controls-left">
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
            <table class="responsive-table" id="buildingsTable">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <!-- Customer column removed: Building tidak relasi langsung ke Customer (many-to-many via building_customers) -->
                        <th data-column="nama_gedung|name">Building Name</th>
                        <th data-column="building_type">Jenis Alamat</th>
                        <th data-column="alamat_1|address">Address</th>
                        <th data-column="city.name">City</th>
                        <th data-column="province.name">Province</th>
                        <th data-column="total_floors" data-type="numeric">Floors</th>
                        <th data-column="total_area" data-type="numeric">Area (m²)</th>
                        <th data-column="status_update">Status</th>
                        <th data-column="createdBy__name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updatedBy__name">Last Updated By</th>
                        <th data-column="updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($buildings ?? [] as $building)
                    <tr data-id="{{ $building->id }}" onclick="window.location='{{ route('operational.buildings.show', $building->id) }}'" class="cursor-pointer">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $building->id }}" onclick="event.stopPropagation()">
                        </td>
                        <!-- Building Name -->
                        <td>{{ $building->nama_gedung ?? $building->name ?? '-' }}</td>
                        <!-- Jenis Alamat (stored in building_type column) -->
                        <td>{{ $building->building_type ?? '-' }}</td>
                        <!-- Address -->
                        <td>{{ ($building->alamat_1 ?? $building->address) ?: '-' }}</td>
                        <!-- City -->
                        <td>{{ optional($building->city)->name ?? '-' }}</td>
                        <!-- Province -->
                        <td>{{ optional($building->province)->name ?? '-' }}</td>
                        <!-- Floors -->
                        <td>{{ $building->total_floors ?? '-' }}</td>
                        <!-- Area -->
                        <td>{{ $building->total_area ?? '-' }}</td>
                        <!-- Status -->
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full {{ $building->status_update ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $building->status_update ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <!-- Created By -->
                        <td>{{ $building->createdBy->name ?? '-' }}</td>
                        <!-- Created At -->
                        <td>
                            @if($building->created_at)
                                {{ \Carbon\Carbon::parse($building->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($building->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <!-- Last Updated By -->
                        <td>{{ $building->updatedBy->name ?? '-' }}</td>
                        <!-- Last Updated At -->
                        <td>
                            @if($building->updated_at)
                                {{ \Carbon\Carbon::parse($building->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($building->updated_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No buildings found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if(isset($buildings) && $buildings->hasPages())
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            {{ $buildings->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Building</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalBody" class="modal-body">
            <!-- Modal content will be loaded here -->
        </div>
        <div id="modalFooter" class="modal-footer">
            <!-- Modal footer buttons will be loaded here -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Hide Building</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this building? This action can be undone later.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Yes, Hide</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">Hmm... Something Went Wrong</h3>
        <p class="delete-modal-description" id="errorMessage">We couldn't hide the building. Please try again.</p>
        <div class="delete-modal-buttons">
            <button class="btn btn-cancel" onclick="closeErrorModal()">Close</button>
            <button class="btn btn-hide" onclick="retryDelete()">Try Again</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="delete-icon-container">
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title">All Set!</h3>
        <p class="delete-modal-description" id="successMessage">The building has been successfully hidden.</p>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;

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
        showWarningDialog('Pilih minimal satu gedung yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

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
    window.initialLocationValues = null;
}

// CRUD Modal functions
function openCreateModal() {
    window.initialLocationValues = null;
    openModal('Create New Building');
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Let's add your new building details and make sure nothing gets missed.</p>
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <h3 class="modal-section-title">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <!-- Customer field removed: Building tidak relasi langsung ke Customer -->
                        <!-- Customer association now managed via building_customers pivot table -->
                        <div class="form-group">
                            <label class="form-label">Building Name *</label>
                            <input type="text" name="nama_gedung" class="form-input" placeholder="Enter building name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-input" placeholder="Enter name">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status_update" class="form-input">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Total Floors</label>
                            <input type="number" name="total_floors" class="form-input" placeholder="Enter total floors" min="1" value="1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Total Area (m²)</label>
                            <input type="number" name="total_area" class="form-input" placeholder="Enter total area" step="0.01">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
            <div class="modal-section">
                <h3 class="modal-section-title">Location Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">Province *</label>
                            <select name="province_id" class="form-input" required>
                                <option value="">Select Province</option>
                                @foreach($provinces ?? [] as $province)
                                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">City *</label>
                            <select name="city_id" class="form-input" required>
                                <option value="">Select City</option>
                                @foreach($cities ?? [] as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">District *</label>
                            <select name="district_id" class="form-input" required>
                                <option value="">Select District</option>
                                @foreach($districts ?? [] as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Subdistrict *</label>
                            <select name="subdistrict_id" class="form-input" required>
                                <option value="">Select Subdistrict</option>
                                @foreach($subdistricts ?? [] as $subdistrict)
                                    <option value="{{ $subdistrict->id }}">{{ $subdistrict->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <h3 class="modal-section-title">Address Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">Address 1 *</label>
                            <input type="text" name="alamat_1" class="form-input" placeholder="Enter address" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address (English)</label>
                            <input type="text" name="address" class="form-input" placeholder="Enter address">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address 2</label>
                            <input type="text" name="alamat_2" class="form-input" placeholder="Enter address 2">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="kode_pos" class="form-input" placeholder="Auto-fill dari kelurahan" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Postal Code (English)</label>
                            <input type="text" name="postal_code" class="form-input" placeholder="Enter postal code">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <h3 class="modal-section-title">Contact Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">Phone 1</label>
                            <input type="text" name="phone_1" class="form-input" placeholder="Enter phone number">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone 2</label>
                            <input type="text" name="phone_2" class="form-input" placeholder="Enter phone number 2">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="form-group">
                            <label class="form-label">Fax</label>
                            <input type="text" name="fax" class="form-input" placeholder="Enter fax number">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" placeholder="Enter email address">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-section">
                <h3 class="modal-section-title">Additional Information</h3>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input form-textarea" placeholder="Enter description"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input form-textarea" placeholder="Enter additional notes"></textarea>
                </div>
            </div>
        </form>
    `;
    
    // Set modal footer for create
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" form="form" class="btn btn-primary">Create Building</button>
    `;
    
    // Setup location cascade - wait briefly
    setTimeout(function() {
        setupLocationCascade();
    }, 50);
}

function openViewModal(id) {
    openModal('View Building');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/operational/buildings/${id}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(response => {
            const data = response.data; // Extract data from response
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <h3 class="modal-section-title">Basic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Customer field removed: Building tidak relasi langsung ke Customer -->
                        <div class="detail-item">
                            <label class="form-label">Building Name</label>
                            <p class="detail-value">${data.nama_gedung || data.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Building ID</label>
                            <p class="detail-value">${data.id || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value">${data.status_update ? 'Active' : 'Inactive'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Total Floors</label>
                            <p class="detail-value">${data.total_floors || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Total Area</label>
                            <p class="detail-value">${data.total_area ? data.total_area + ' m²' : '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h3 class="modal-section-title">Address Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Address 1</label>
                            <p class="detail-value">${data.alamat_1 || data.address || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Address 2</label>
                            <p class="detail-value">${data.alamat_2 || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Province</label>
                            <p class="detail-value">${data.province ? data.province.name : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">City</label>
                            <p class="detail-value">${data.city ? data.city.name : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">District</label>
                            <p class="detail-value">${data.district ? data.district.name : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Subdistrict</label>
                            <p class="detail-value">${data.subdistrict ? data.subdistrict.name : '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Postal Code</label>
                            <p class="detail-value">${data.kode_pos || data.postal_code || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h3 class="modal-section-title">Contact Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Phone 1</label>
                            <p class="detail-value">${data.phone_1 || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Phone 2</label>
                            <p class="detail-value">${data.phone_2 || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Fax</label>
                            <p class="detail-value">${data.fax || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Email</label>
                            <p class="detail-value">${data.email || '-'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h3 class="modal-section-title">Additional Information</h3>
                    <div class="detail-item">
                        <label class="form-label">Description</label>
                        <p class="detail-value">${data.description || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Notes</label>
                        <p class="detail-value">${data.notes || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Created At</label>
                        <p class="detail-value">${data.created_at ? new Date(data.created_at).toLocaleString('id-ID') : '-'}</p>
                    </div>
                </div>
            `;
            
            // Set modal footer for view
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit Building</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            `;
        });
}

function openEditModal(id) {
    openModal('Edit Building');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/operational/buildings/${id}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(response => {
            const data = response.data; // Extract data from response
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update your building details and make sure nothing gets missed.</p>
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <div class="modal-section">
                        <h3 class="modal-section-title">Basic Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <!-- Customer field removed: Building tidak relasi langsung ke Customer -->
                                <!-- Customer association now managed via building_customers pivot table -->
                                <div class="form-group">
                                    <label class="form-label">Building Name *</label>
                                    <input type="text" name="nama_gedung" class="form-input" value="${data.nama_gedung || ''}" placeholder="Enter building name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Name  </label>
                                    <input type="text" name="name" class="form-input" value="${data.name || ''}" placeholder="Enter name  ">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select name="status_update" class="form-input">
                                        <option value="1" ${data.status_update ? 'selected' : ''}>Active</option>
                                        <option value="0" ${!data.status_update ? 'selected' : ''}>Inactive</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Total Floors</label>
                                    <input type="number" name="total_floors" class="form-input" value="${data.total_floors || 1}" placeholder="Enter total floors" min="1">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Total Area (m²)</label>
                                    <input type="number" name="total_area" class="form-input" value="${data.total_area || ''}" placeholder="Enter total area" step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <h3 class="modal-section-title">Location Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="form-label">Province *</label>
                                    <select name="province_id" class="form-input" required>
                                        <option value="">Select Province</option>
                                        @foreach($provinces ?? [] as $province)
                                            <option value="{{ $province->id }}" ${data.province_id == '{{ $province->id }}' ? 'selected' : ''}>{{ $province->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">City *</label>
                                    <select name="city_id" class="form-input" required>
                                        <option value="">Select City</option>
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="form-label">District *</label>
                                    <select name="district_id" class="form-input" required>
                                        <option value="">Select District</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Subdistrict *</label>
                                    <select name="subdistrict_id" class="form-input" required>
                                        <option value="">Select Subdistrict</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h3 class="modal-section-title">Address Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="form-label">Address 1 *</label>
                                    <input type="text" name="alamat_1" class="form-input" value="${data.alamat_1 || ''}" placeholder="Enter address" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Address  </label>
                                    <input type="text" name="address" class="form-input" value="${data.address || ''}" placeholder="Enter address  ">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Address 2</label>
                                    <input type="text" name="alamat_2" class="form-input" value="${data.alamat_2 || ''}" placeholder="Enter address 2">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" name="kode_pos" class="form-input" value="${data.kode_pos || ''}" placeholder="Auto-fill dari kelurahan" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Postal Code (English)</label>
                                    <input type="text" name="postal_code" class="form-input" value="${data.postal_code || ''}" placeholder="Enter postal code">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <h3 class="modal-section-title">Contact Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="form-label">Phone 1</label>
                                    <input type="text" name="phone_1" class="form-input" value="${data.phone_1 || ''}" placeholder="Enter phone number">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone 2</label>
                                    <input type="text" name="phone_2" class="form-input" value="${data.phone_2 || ''}" placeholder="Enter phone number 2">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="form-label">Fax</label>
                                    <input type="text" name="fax" class="form-input" value="${data.fax || ''}" placeholder="Enter fax number">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-input" value="${data.email || ''}" placeholder="Enter email address">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-section">
                        <h3 class="modal-section-title">Additional Information</h3>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-input form-textarea" placeholder="Enter description">${data.description || ''}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-input form-textarea" placeholder="Enter additional notes">${data.notes || ''}</textarea>
                        </div>
                    </div>
                </form>
            `;
            
            // Set modal footer for edit
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" form="form" class="btn btn-primary">Update Building</button>
            `;
            
            // Setup location cascade for edit modal - wait a bit for DOM to be ready
            setTimeout(async function() {
                // Initialize Select2 and Listeners FIRST
                setupLocationCascade();
                
                // Block cascade during initial load
                window.skipCascadeEvents = true;
                
                // Data for selects
                const provinceId = data.province_id || (data.province ? data.province.id : null);
                const cityId = data.city_id || (data.city ? data.city.id : null);
                const districtId = data.district_id || (data.district ? data.district.id : null);
                const subdistrictId = data.subdistrict_id || (data.subdistrict ? data.subdistrict.id : null);
                
                try {
                    // Load cities if province is selected
                    if (provinceId) {
                        const citySelect = document.querySelector('select[name="city_id"]');
                        if (citySelect) {
                            citySelect.disabled = true;
                            citySelect.innerHTML = '<option value="">Loading cities...</option>';
                            
                            const citiesResponse = await fetch(`/api/cities/${provinceId}`);
                            const citiesData = await citiesResponse.json();
                            let citiesArray = Array.isArray(citiesData) ? citiesData : (citiesData.data || []);
                            
                            citySelect.innerHTML = '<option value="">Select City</option>';
                            citiesArray.forEach(city => {
                                const option = document.createElement('option');
                                option.value = city.id;
                                option.textContent = city.name;
                                if (city.id == cityId) option.selected = true;
                                citySelect.appendChild(option);
                            });
                            citySelect.disabled = false;
                            
                            // SYNC SELECT2
                            if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                                jQuery(citySelect).trigger('change');
                            }
                        }
                    }
                    
                    // Load districts if city is selected
                    if (cityId) {
                        const districtSelect = document.querySelector('select[name="district_id"]');
                        if (districtSelect) {
                            districtSelect.disabled = true;
                            districtSelect.innerHTML = '<option value="">Loading districts...</option>';
                            
                            const districtsResponse = await fetch(`/api/districts/${cityId}`);
                            const districtsData = await districtsResponse.json();
                            let districtsArray = Array.isArray(districtsData) ? districtsData : (districtsData.data || []);
                            
                            districtSelect.innerHTML = '<option value="">Select District</option>';
                            districtsArray.forEach(district => {
                                const option = document.createElement('option');
                                option.value = district.id;
                                option.textContent = district.name;
                                if (district.id == districtId) option.selected = true;
                                districtSelect.appendChild(option);
                            });
                            districtSelect.disabled = false;

                            // SYNC SELECT2
                            if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                                jQuery(districtSelect).trigger('change');
                            }
                        }
                    }
                    
                    // Load subdistricts if district is selected
                    if (districtId) {
                        const subdistrictSelect = document.querySelector('select[name="subdistrict_id"]');
                        if (subdistrictSelect) {
                            subdistrictSelect.disabled = true;
                            subdistrictSelect.innerHTML = '<option value="">Loading subdistricts...</option>';
                            
                            console.log('Fetching subdistricts for district:', districtId);
                            const subdistrictsResponse = await fetch(`/api/subdistricts/${districtId}`);
                            const subdistrictsData = await subdistrictsResponse.json();
                            let subdistrictsArray = Array.isArray(subdistrictsData) ? subdistrictsData : (subdistrictsData.data || []);
                            
                            console.log('Loaded subdistricts:', subdistrictsArray.length, 'items, selecting:', subdistrictId);
                            
                            subdistrictSelect.innerHTML = '<option value="">Select Subdistrict</option>';
                            subdistrictsArray.forEach(subdistrict => {
                                const option = document.createElement('option');
                                option.value = subdistrict.id;
                                option.textContent = subdistrict.name;
                                if (subdistrict.id == subdistrictId) {
                                    option.selected = true;
                                    console.log('Selected subdistrict:', subdistrict.name);
                                }
                                subdistrictSelect.appendChild(option);
                            });
                            subdistrictSelect.disabled = false;
                            
                            // SYNC SELECT2
                            if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                                const $subdistrict = jQuery(subdistrictSelect);
                                if (!$subdistrict.hasClass('select2-hidden-accessible')) {
                                     $subdistrict.select2({
                                        dropdownParent: jQuery('#modalOverlay'),
                                        width: '100%'
                                    });
                                } else {
                                    $subdistrict.trigger('change');
                                }
                            }
                        }
                    }
                    
                    // Load postal code if subdistrict is selected
                    if (subdistrictId) {
                        try {
                            const postalResponse = await fetch(`/api/subdistricts/${subdistrictId}/postal-code`);
                            const postalData = await postalResponse.json();
                            const postalInput = document.querySelector('input[name="kode_pos"]');
                            if (postalInput) {
                                postalInput.value = postalData.postal_code || postalData.kode_pos || data.kode_pos || '';
                            }
                        } catch (e) {
                            console.error('Error loading postal code:', e);
                        }
                    }
                    
                } catch (e) {
                    console.error('Error loading location data in edit:', e);
                } finally {
                    setTimeout(() => {
                        window.skipCascadeEvents = false;
                        console.log('Cascade events enabled after edit modal load complete');
                    }, 300);
                }
            }, 100);
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading details.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            `;
        });
}

function submitForm(event, id = null) {
    event.preventDefault();
    
    const form = event.target;
    
    // ---------------------------------------------------------
    // Client-side Validation
    // ---------------------------------------------------------
    
    // 1. Reset previous errors
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    
    // 2. Define required fields
    const requiredFields = [
        'nama_gedung',      // Building Name
        'province_id',      // Province
        'city_id',          // City
        'district_id',      // District
        'subdistrict_id',   // Subdistrict
        'alamat_1'          // Address 1
    ];
    
    let isValid = true;
    
    // 3. Check each field
    requiredFields.forEach(fieldName => {
        const input = form.querySelector(`[name="${fieldName}"]`);
        if (input) {
            if (!input.value || input.value.trim() === '') {
                input.classList.add('is-invalid');
                isValid = false;
            }
        }
    });

    // 4. Stop if invalid
    if (!isValid) {
        // Optional: Focus the first invalid field
        const firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) {
            firstInvalid.focus();
        }
        return; // Stop submission
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/operational/buildings/${id}` : '/operational/buildings';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    console.log('Submitting form data:', data);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            closeModal();
            location.reload();
        } else {
            showErrorDialog('Gagal', result.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Terjadi kesalahan.');
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this building? This action can be undone later.'
        : `Are you sure you want to hide ${count} buildings? This action can be undone later.`;
    
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
    
    fetch('/operational/buildings/bulk-delete', {
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
        showErrorModal('Network error occurred');
    });
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'The building has been successfully hidden.'
        : `${count} buildings have been successfully hidden.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t hide the building. Please try again.';
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

// Location cascade dropdown functionality
function setupLocationCascade() {
    console.log('Setting up location cascade (optimized)...');
    
    // Brief delay to ensure modal DOM is stable
    setTimeout(function() {
        const $modal = jQuery('#modalOverlay');
        if ($modal.length === 0) return;
        
        const $province = $modal.find('select[name="province_id"]');
        const $city = $modal.find('select[name="city_id"]');
        const $district = $modal.find('select[name="district_id"]');
        const $subdistrict = $modal.find('select[name="subdistrict_id"]');
        const $postalCode = $modal.find('input[name="kode_pos"]');
        
        if ($province.length === 0) return;

        // --- Select2 Initialization ---
        const select2Opts = { dropdownParent: $modal, width: '100%' };
        [$province, $city, $district, $subdistrict].forEach($el => {
            if (!$el.hasClass('select2-hidden-accessible')) {
                $el.select2(select2Opts);
            }
            $el.addClass('no-select2'); // Prevent auto-init from other scripts
        });

        // --- State Management ---
        let isUpdating = false;

        // --- Handlers ---
        
        const updateDropdown = async ($el, url, placeholder, selectedId = null) => {
            $el.prop('disabled', true);
            $el.html(`<option value="">Loading ${placeholder}...</option>`).trigger('change');
            
            try {
                const response = await fetch(url);
                const result = await response.json();
                const items = Array.isArray(result) ? result : (result.data || []);
                
                let html = `<option value="">Select ${placeholder}</option>`;
                items.forEach(item => {
                    const isSelected = item.id == selectedId ? 'selected' : '';
                    html += `<option value="${item.id}" ${isSelected}>${item.name}</option>`;
                });
                
                $el.html(html).prop('disabled', false);
                
                // Set value explicitly to be sure
                if (selectedId) $el.val(selectedId);
                
                // Force Select2 and Cascade update
                $el.trigger('change');
            } catch (e) {
                console.error(`Error loading ${placeholder}:`, e);
                $el.html(`<option value="">Error loading ${placeholder}</option>`).prop('disabled', false).trigger('change');
            }
        };

        // --- Event Binding ---
        
        let activeRequests = {}; // To prevent race conditions

        const bindCascade = ($el, $child, urlPath, label) => {
            $el.off('change.locCascade').on('change.locCascade', function(e) {
                if (window.skipCascadeEvents) return;
                
                const id = $(this).val();
                console.log(`Cascade: ${label} changed to ${id}`);
                
                // Clear all deeper children
                if ($child === $city) {
                    $district.html('<option value="">Select District</option>').trigger('change');
                    $subdistrict.html('<option value="">Select Subdistrict</option>').trigger('change');
                    $postalCode.val('');
                } else if ($child === $district) {
                    $subdistrict.html('<option value="">Select Subdistrict</option>').trigger('change');
                    $postalCode.val('');
                } else if ($child === $subdistrict) {
                    $postalCode.val('');
                }

                if (id) {
                    updateDropdown($child, `${urlPath}/${id}`, label);
                } else {
                    $child.html(`<option value="">Select ${label}</option>`).prop('disabled', true).trigger('change');
                }
            });
        };

        bindCascade($province, $city, '/api/cities', 'City');
        bindCascade($city, $district, '/api/districts', 'District');
        bindCascade($district, $subdistrict, '/api/subdistricts', 'Subdistrict');

        // Subdistrict -> Postal Code Auto-fill
        $subdistrict.off('change.locCascade').on('change.locCascade', function() {
            if (window.skipCascadeEvents) return;
            const id = $(this).val();
            if (id) {
                console.log('Fetching postal code for subdistrict:', id);
                fetch(`/api/subdistricts/${id}/postal-code`)
                    .then(res => res.json())
                    .then(data => {
                        $postalCode.val(data.postal_code || data.kode_pos || '');
                    })
                    .catch(e => console.error('Error:', e));
            } else {
                $postalCode.val('');
            }
        });

        console.log('Location cascade setup completed (jQuery Optimized)');
        
        // Final Sync: If there's an initial value (e.g. from Edit modal), make sure it's visible
        // However, Edit modal handles its own sequential loading, so we just enable events at the end.
        setTimeout(() => {
            window.skipCascadeEvents = false;
        }, 100);

    }, 50);
}


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
