@extends('layouts.app')

@section('title', 'Master Location Management')
@section('breadcrumb', 'Home / System / Master Location')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }
    
    /* Override main-content padding for full width */
    .main-content {
        padding: 0 !important;
        margin-left: 220px !important;
    }
    
    .main-content.sidebar-collapsed {
        margin-left: 70px !important;
    }
    
    /* Full width layout */
    .content-wrapper {
        width: 100%;
        padding: 0 1rem;
        margin: 0;
    }
    
    @media (min-width: 768px) {
        .content-wrapper {
            padding: 0 2rem;
        }
    }
    
    @media (min-width: 1024px) {
        .content-wrapper {
            padding: 0 3rem;
        }
    }
    
    /* Ensure all child elements use full width */
    .content-wrapper > * {
        width: 100%;
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
        min-width: 1200px;
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

    /* Column widths for provinces */
    .responsive-table th:nth-child(1), .responsive-table td:nth-child(1) { width: 50px; min-width: 50px; }
    .responsive-table th:nth-child(2), .responsive-table td:nth-child(2) { width: 50px; min-width: 50px; }
    .responsive-table th:nth-child(3), .responsive-table td:nth-child(3) { width: 200px; min-width: 200px; }
    .responsive-table th:nth-child(4), .responsive-table td:nth-child(4) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(5), .responsive-table td:nth-child(5) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(6), .responsive-table td:nth-child(6) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(7), .responsive-table td:nth-child(7) { width: 100px; min-width: 100px; }
    .responsive-table th:nth-child(8), .responsive-table td:nth-child(8) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(9), .responsive-table td:nth-child(9) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(10), .responsive-table td:nth-child(10) { width: 150px; min-width: 150px; }
    .responsive-table th:nth-child(11), .responsive-table td:nth-child(11) { width: 150px; min-width: 150px; }

    /* Pagination styles removed - using hierarchical tree instead */

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 1200px;
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

    /* Modal Section Styles */
    .modal-section {
        margin-bottom: 24px;
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f9fafb;
    }

    .modal-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #214589;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #214589;
    }

    /* Hierarchical Tree Styles */
    .location-tree {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .tree-level {
        border-bottom: 1px solid #e5e7eb;
    }

    .tree-level:last-child {
        border-bottom: none;
    }

    .tree-header {
        background: #f8fafc;
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: between;
        gap: 12px;
    }

    .tree-title {
        font-size: 16px;
        font-weight: 600;
        color: #214589;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .tree-icon {
        width: 20px;
        height: 20px;
        color: #214589;
    }

    .tree-content {
        padding: 16px;
    }

    .tree-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        margin-bottom: 8px;
        background: white;
        transition: all 0.2s ease;
    }

    .tree-item:hover {
        background: #f8fafc;
        border-color: #214589;
    }

    .tree-item:last-child {
        margin-bottom: 0;
    }

    .tree-item-info {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
    }

    .tree-item-name {
        font-weight: 500;
        color: #374151;
    }

    .tree-item-counts {
        display: flex;
        gap: 16px;
        font-size: 12px;
        color: #6b7280;
    }

    .tree-item-actions {
        display: flex;
        gap: 8px;
    }

    .expand-btn {
        background: none;
        border: none;
        color: #214589;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .expand-btn:hover {
        background: #e5e7eb;
    }

    .expand-icon {
        width: 16px;
        height: 16px;
        transition: transform 0.2s ease;
    }

    .expand-icon.expanded {
        transform: rotate(90deg);
    }

    .level-indicator {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 8px;
    }

    .level-badge {
        background: #214589;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 500;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6b7280;
    }

    .empty-state-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 16px;
        color: #d1d5db;
    }

    .empty-state-text {
        font-size: 14px;
        margin-bottom: 16px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }

    .form-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    .form-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .detail-item {
        margin-bottom: 16px;
    }

    .detail-value {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
        padding: 8px 0;
    }

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
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 90vw;
        width: 500px;
        overflow: hidden;
        position: relative;
        padding: 40px 30px 30px;
        text-align: center;
    }

    .error-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }

    .error-icon {
        width: 80px;
        height: 80px;
        color: #dc2626;
    }

    .error-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #dc2626;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }

    .error-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }

    .error-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }

    .btn-error-close {
        background-color: white;
        color: #dc2626;
        border: 2px solid #dc2626;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-error-close:hover {
        background-color: #fef2f2;
        border-color: #b91c1c;
        color: #b91c1c;
    }

    .btn-error-retry {
        background-color: #dc2626;
        color: white;
        border: 2px solid #dc2626;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
    }

    .btn-error-retry:hover {
        background-color: #b91c1c;
        border-color: #b91c1c;
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

    .success-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }

    .success-icon {
        width: 80px;
        height: 80px;
        color: #16a34a;
    }

    .success-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #16a34a;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }

    .success-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
</style>

<div class="w-full min-h-screen">
    <div class="content-wrapper w-full mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Master Location Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Master Location Management</h1>
                <div class="ml-4 text-sm text-gray-600">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs">Province → City → District → Subdistrict</span>
                </div>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <button class="btn btn-secondary" onclick="showHelpModal()" data-tooltip="How to use Master Location Management">
                    <i class="fas fa-question-circle"></i>
                    <span class="hidden md:inline">Help</span>
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
                            <p class="text-sm font-normal text-gray-700 w-auto ml-2 cursor-pointer" onclick="document.getElementById('selectAll').click()">Pilih semua</p>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Button -->
                <button class="btn btn-secondary btn-sm ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Hapus</span>
                </button>
            </div>
            
            <!-- Location Summary -->
            <div class="flex items-center gap-4 text-sm flex-wrap">
                <div class="flex items-center gap-2 bg-gradient-to-r from-blue-100 to-blue-200 px-4 py-3 rounded-xl border-2 border-blue-300 shadow-sm">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span class="text-blue-900 font-semibold">
                        Provinsi: {{ $totalProvinces ?? $provinces->total() }}
                        @if(isset($provinces) && $provinces->total() > $provinces->count())
                            <span class="text-xs">(Menampilkan {{ $provinces->count() }} dari {{ $provinces->total() }})</span>
                        @endif
                    </span>
                </div>
                
                <div class="flex items-center gap-2 bg-gradient-to-r from-purple-100 to-purple-200 px-4 py-3 rounded-xl border-2 border-purple-300 shadow-sm">
                    <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                    <span class="text-purple-900 font-semibold">Kota/Kab: {{ number_format($totalCities ?? 0) }}</span>
                </div>
                
                <div class="flex items-center gap-2 bg-gradient-to-r from-orange-100 to-orange-200 px-4 py-3 rounded-xl border-2 border-orange-300 shadow-sm">
                    <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                    <span class="text-orange-900 font-semibold">Kecamatan: {{ number_format($totalDistricts ?? 0) }}</span>
                </div>
                
                <div class="flex items-center gap-2 bg-gradient-to-r from-pink-100 to-pink-200 px-4 py-3 rounded-xl border-2 border-pink-300 shadow-sm">
                    <div class="w-3 h-3 bg-pink-500 rounded-full"></div>
                    <span class="text-pink-900 font-semibold">Kelurahan: {{ number_format($totalSubdistricts ?? 0) }}</span>
                </div>
                
                <div class="flex items-center gap-2 bg-gradient-to-r from-indigo-100 to-indigo-200 px-4 py-3 rounded-xl border-2 border-indigo-300 shadow-sm">
                    <div class="w-3 h-3 bg-indigo-500 rounded-full"></div>
                    <span class="text-indigo-900 font-semibold">Kode Pos: {{ number_format($totalPostalCodes ?? 0) }}</span>
                </div>
                
                <div class="flex items-center gap-3 bg-gradient-to-r from-emerald-100 via-teal-100 to-cyan-100 px-4 py-3 rounded-xl border-2 border-emerald-300 shadow-sm cursor-pointer hover:shadow-md hover:scale-105 transition-all duration-300" onclick="showHierarchyInfo()">
                    <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                    <span class="text-emerald-900 font-semibold">Hierarchical Tree View</span>
                    <div class="w-2 h-2 bg-emerald-400 rounded-full"></div>
                </div>
            </div>
        </div>
        
        <!-- Hierarchical Location Tree -->
        <div class="location-tree">
            <!-- Province Level -->
            <div class="tree-level">
                <div class="tree-header">
                    <div class="tree-title">
                        <svg class="tree-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Provinces
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Provinsi
                    </button>
                </div>
                <div class="tree-content">
                    @forelse($provinces ?? [] as $province)
                    <div class="tree-item" data-province-id="{{ $province->id }}">
                        <div class="tree-item-info">
                            <input type="checkbox" class="row-checkbox" value="{{ $province->id }}" onclick="event.stopPropagation()" style="width: 16px; height: 16px; margin-right: 8px; cursor: pointer;">
                            <button class="expand-btn" onclick="toggleProvince({{ $province->id }})">
                                <svg class="expand-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <div>
                                <div class="tree-item-name">{{ $province->name ?? '-' }}</div>
                                <div class="tree-item-counts">
                                    <span>Code: {{ $province->code ?? '-' }}</span>
                                    <span>Country: {{ $province->country ?? '-' }}</span>
                                    <span>Cities: {{ $province->cities_count ?? 0 }}</span>
                                    <span>Customers: {{ $province->customers_count ?? 0 }}</span>
                                    <span>Branches: {{ $province->branches_count ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="tree-item-actions">
                            <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openViewModal({{ $province->id }})">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openEditModal({{ $province->id }})">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openDeleteModal({{ $province->id }}, '{{ $province->name }}')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Hapus
                            </button>
                        </div>
                    </div>
                    
                    <!-- Cities Container (Hidden by default) -->
                    <div id="cities-{{ $province->id }}" class="cities-container" style="display: none; margin-left: 40px; margin-top: 8px;">
                        <div class="level-indicator">
                            <span class="level-badge">CITIES</span>
                            <span>Di bawah {{ $province->name }}</span>
                            <button class="btn btn-primary btn-sm ml-auto" onclick="openCreateCityModal({{ $province->id }})">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Tambah Kota
                            </button>
                        </div>
                        <div class="tree-content">
                            <div class="empty-state">
                                <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <div class="empty-state-text">Belum ada kota</div>
                                <button class="btn btn-primary btn-sm" onclick="openCreateCityModal({{ $province->id }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Tambah Kota
                                </button>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="empty-state-text">Belum ada provinsi</div>
                        <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Tambah Provinsi Pertama
                        </button>
                    </div>
                    @endforelse
                </div>
                
                <!-- Pagination -->
                @if($provinces->hasPages())
                <div class="pagination-wrapper" style="margin-top: 24px; padding: 16px; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                        <div style="color: #6b7280; font-size: 14px;">
                            Showing {{ $provinces->firstItem() ?? 0 }} to {{ $provinces->lastItem() ?? 0 }} of {{ $provinces->total() }} provinces
                        </div>
                        <div class="pagination-controls">
                            {{ $provinces->links() }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Lihat Provinsi</h2>
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
            <svg class="delete-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </div>
        <h3 class="delete-modal-title" id="deleteModalTitle">Sembunyikan Provinsi</h3>
        <p class="delete-modal-description" id="deleteModalDescription">Apakah kamu yakin ingin menyembunyikan provinsi ini? Tindakan ini masih bisa dibatalkan nanti.</p>
        <div class="delete-modal-buttons" id="deleteModalButtons">
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Ya, Sembunyikan</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="error-icon-container">
            <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="error-modal-title">Ups... Terjadi Kendala</h3>
        <p class="error-modal-description" id="errorMessage">Provinsi belum berhasil disembunyikan. Silakan coba lagi.</p>
        <div class="error-modal-buttons">
            <button class="btn btn-error-close" onclick="closeErrorModal()">Tutup</button>
            <button class="btn btn-error-retry" onclick="retryDelete()">Coba Lagi</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="success-icon-container">
            <svg class="success-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="success-modal-title">Berhasil</h3>
        <p class="success-modal-description" id="successMessage">Provinsi berhasil disembunyikan.</p>
    </div>
</div>

    </div> <!-- End content-wrapper -->
@endsection

@push('scripts')
<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;
let expandedProvinces = new Set();
let expandedCities = new Set();
let expandedDistricts = new Set();

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
    openModal('Tambah Provinsi Baru');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitForm(event)">
            <div class="modal-section">
                <div class="modal-section-title">Informasi Provinsi</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Nama Provinsi *</label>
                        <input type="text" name="name" class="form-input" placeholder="Masukkan nama provinsi" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kode Provinsi *</label>
                        <input type="text" name="code" class="form-input" placeholder="Masukkan kode provinsi" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Negara *</label>
                        <input type="text" name="country" class="form-input" placeholder="Masukkan negara" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input" rows="3" placeholder="Masukkan deskripsi provinsi"></textarea>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    // Add modal footer
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" form="form" class="btn btn-primary">Simpan Provinsi</button>
    `;
}

function openViewModal(id) {
    openModal('Lihat Provinsi');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/system/provinces/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-section">
                    <div class="modal-section-title">Informasi Provinsi</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Nama Provinsi</label>
                            <p class="detail-value">${data.data.name || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Kode Provinsi</label>
                            <p class="detail-value">${data.data.code || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Negara</label>
                            <p class="detail-value">${data.data.country || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Jumlah Kota</label>
                            <p class="detail-value">${data.data.cities_count || 0}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Deskripsi</label>
                            <p class="detail-value">${data.data.description || '-'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Dibuat Pada</label>
                            <p class="detail-value">${data.data.created_at ? new Date(data.data.created_at).toLocaleString('id-ID') : '-'}</p>
                        </div>
                    </div>
                </div>
            `;
        
        // Add modal footer for view modal
            document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Ubah Provinsi</button>
        `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div style="color: #dc2626; font-size: 48px; margin-bottom: 16px;">⚠️</div>
                    <h3 style="color: #dc2626; margin-bottom: 8px;">Gagal Memuat Provinsi</h3>
                    <p style="color: #6b7280; margin-bottom: 16px;">Data provinsi belum berhasil dimuat. Silakan coba lagi.</p>
                    <button class="btn btn-primary" onclick="openViewModal(${id})">Coba Lagi</button>
                </div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            `;
        });
}

function openEditModal(id) {
    openModal('Ubah Provinsi');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/system/provinces/${id}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <form id="form" onsubmit="submitForm(event, ${id})">
                    <input type="hidden" name="id" value="${data.data.id}">
                    <div class="modal-section">
                        <div class="modal-section-title">Informasi Provinsi</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Nama Provinsi *</label>
                                <input type="text" name="name" class="form-input" value="${data.data.name || ''}" placeholder="Masukkan nama provinsi" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kode Provinsi *</label>
                                <input type="text" name="code" class="form-input" value="${data.data.code || ''}" placeholder="Masukkan kode provinsi" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Negara *</label>
                                <input type="text" name="country" class="form-input" value="${data.data.country || ''}" placeholder="Masukkan negara" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="description" class="form-input" rows="3" placeholder="Masukkan deskripsi provinsi">${data.data.description || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            
            // Add modal footer for edit modal
            document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" form="form" class="btn btn-primary">Perbarui Provinsi</button>
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

function submitForm(event, id = null) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    const url = id ? `/system/provinces/${id}` : '/system/provinces';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
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
            alert('Gagal: ' + (result.message || 'Terjadi kesalahan'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal: Terjadi kesalahan');
    });
}

// Delete Modal functions
function openDeleteModal(provinceId = null, provinceName = null) {
    // If provinceId is provided, it's individual delete
    if (provinceId && provinceName) {
        console.log('Individual delete for province:', provinceId, provinceName);
        selectedIdsForRetry = [provinceId.toString()];
        
        // Get detailed delete info first
        fetch(`/system/provinces/${provinceId}/delete-info/province`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const info = data.data;
                let message = `Apakah kamu yakin ingin menyembunyikan provinsi <strong>"${info.name}"</strong>?`;
                
                if (info.related_data.length > 0) {
                    message += `<br><br><strong>⚠️ This province contains:</strong><br>`;
                    message += info.related_data.map(item => `• ${item}`).join('<br>');
                    
                    if (info.cascade_delete) {
                        message += `<br><br><span class="text-red-600">All related data will also be hidden!</span>`;
                    }
                }
                
                if (!info.can_delete) {
                    message += `<br><br><span class="text-red-600 font-bold">❌ Cannot hide: This province has customers or branches and cannot be hidden.</span>`;
                }
                
                document.getElementById('deleteModalDescription').innerHTML = message;
                
                if (info.can_delete) {
                    document.getElementById('deleteModalButtons').innerHTML = `
                        <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
                        <button class="btn btn-hide" onclick="confirmDelete()">Ya, Sembunyikan</button>
                    `;
                } else {
                    document.getElementById('deleteModalButtons').innerHTML = `
                        <button class="btn btn-cancel" onclick="closeDeleteModal()">Tutup</button>
                    `;
                }
            } else {
                showErrorModal('Gagal mengambil informasi penghapusan');
            }
        })
        .catch(error => {
            console.error('Error getting delete info:', error);
            showErrorModal('Gagal mengambil informasi penghapusan');
        });
    } else {
        // Bulk delete - use simple message for now
        const count = selectedIdsForRetry.length;
        console.log('Bulk delete - selectedIdsForRetry:', selectedIdsForRetry);
        console.log('Bulk delete - count:', count);
        
        const message = count === 1 
            ? 'Apakah kamu yakin ingin menyembunyikan provinsi ini? Tindakan ini masih bisa dibatalkan nanti.'
            : `Apakah kamu yakin ingin menyembunyikan ${count} provinsi? Tindakan ini masih bisa dibatalkan nanti.`;
        
        document.getElementById('deleteModalDescription').innerHTML = `<p>${message}</p>`;
        document.getElementById('deleteModalButtons').innerHTML = `
            <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
            <button class="btn btn-hide" onclick="confirmDelete()">Ya, Sembunyikan</button>
        `;
    }
    
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function confirmDelete() {
    closeDeleteModal();
    
    // Debug: Log the data being sent
    console.log('Deleting provinces with IDs:', selectedIdsForRetry);
    
    fetch('/system/provinces/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => {
        console.log('Delete response status:', response.status);
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Delete response error:', text);
                throw new Error(`Server error: ${response.status} - ${text}`);
            });
        }
        return response.json();
    })
    .then(result => {
        console.log('Delete result:', result);
        if (result.success) {
            showSuccessModal(result.count, result.cascade_messages);
            selectedIdsForRetry = [];
            // Reload the page to refresh the data
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showErrorModal(result.message || 'Terjadi kesalahan saat menyembunyikan provinsi');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        showErrorModal('Terjadi kesalahan saat menyembunyikan provinsi: ' + error.message);
    });
}

// Success Modal functions
function showSuccessModal(count, cascadeMessages = []) {
    let message = count === 1 
        ? 'Provinsi berhasil disembunyikan.'
        : `${count} provinsi berhasil disembunyikan.`;
    
    // Add cascade delete info if any
    if (cascadeMessages && cascadeMessages.length > 0) {
        message += '\n\nData terkait yang ikut terhapus:\n' + cascadeMessages.join('\n');
    }
    
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
    document.getElementById('errorMessage').textContent = message || 'Provinsi belum berhasil disembunyikan. Silakan coba lagi.';
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
const selectAllElement = document.getElementById('selectAll');
if (selectAllElement) {
    selectAllElement.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        const headerSelectAllElement = document.getElementById('headerSelectAll');
        if (headerSelectAllElement) {
            headerSelectAllElement.checked = this.checked;
        }
    });
}

const headerSelectAllElement = document.getElementById('headerSelectAll');
if (headerSelectAllElement) {
    headerSelectAllElement.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        const selectAllElement = document.getElementById('selectAll');
        if (selectAllElement) {
            selectAllElement.checked = this.checked;
        }
    });
}

// Individual checkbox functionality
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('row-checkbox')) {
        console.log('Checkbox changed:', {
            value: e.target.value,
            checked: e.target.checked
        });
        
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        const headerSelectAllCheckbox = document.getElementById('headerSelectAll');
        
        const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
        const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
        
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = anyChecked && !allChecked;
        }
        if (headerSelectAllCheckbox) {
            headerSelectAllCheckbox.checked = allChecked;
            headerSelectAllCheckbox.indeterminate = anyChecked && !allChecked;
        }
    }
});

// Add click event listener for checkboxes to ensure they work
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('row-checkbox')) {
        console.log('Checkbox clicked:', {
            value: e.target.value,
            checked: e.target.checked,
            beforeToggle: !e.target.checked
        });
    }
});

// Delete selected function
function deleteSelected() {
    // Debug: Check all checkboxes first
    const allCheckboxes = document.querySelectorAll('.row-checkbox');
    const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
    
    console.log('=== DELETE DEBUG ===');
    console.log('Total checkboxes found:', allCheckboxes.length);
    console.log('Checked checkboxes found:', checkedCheckboxes.length);
    
    // Log all checkbox states
    allCheckboxes.forEach((cb, index) => {
        console.log(`Checkbox ${index}:`, {
            value: cb.value,
            checked: cb.checked,
            visible: cb.offsetWidth > 0 && cb.offsetHeight > 0,
            element: cb
        });
    });
    
    if (checkedCheckboxes.length === 0) {
        alert('Silakan pilih minimal satu provinsi untuk disembunyikan. Pastikan checkbox di samping nama provinsi sudah dicentang.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkedCheckboxes).map(cb => cb.value);
    console.log('selectedIdsForRetry set to:', selectedIdsForRetry);
    openDeleteModal();
}

// ==================== HIERARCHICAL TREE FUNCTIONS ====================

// Toggle Province expansion
function toggleProvince(provinceId) {
    console.log('toggleProvince called with ID:', provinceId);
    
    const citiesContainer = document.getElementById(`cities-${provinceId}`);
    const provinceItem = document.querySelector(`[data-province-id="${provinceId}"]`);
    const expandBtn = provinceItem.querySelector('.expand-btn');
    const expandIcon = expandBtn.querySelector('.expand-icon');
    
    console.log('citiesContainer:', citiesContainer);
    console.log('provinceItem:', provinceItem);
    console.log('expandBtn:', expandBtn);
    console.log('expandIcon:', expandIcon);
    
    if (expandedProvinces.has(provinceId)) {
        // Collapse
        console.log('Collapsing province:', provinceId);
        citiesContainer.style.display = 'none';
        expandIcon.classList.remove('expanded');
        expandedProvinces.delete(provinceId);
    } else {
        // Expand
        console.log('Expanding province:', provinceId);
        citiesContainer.style.display = 'block';
        expandIcon.classList.add('expanded');
        expandedProvinces.add(provinceId);
        
        // Load cities if not already loaded
        loadCities(provinceId);
    }
}

// Load cities for a province
function loadCities(provinceId) {
    const citiesContainer = document.getElementById(`cities-${provinceId}`);
    const content = citiesContainer.querySelector('.tree-content');
    
    // Show loading
    content.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <div style="display: inline-block; width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 8px; color: #666; font-size: 12px;">Memuat kota...</p>
        </div>
    `;
    
    fetch(`/system/provinces/${provinceId}/cities`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            renderCities(data.data, provinceId, content);
        } else {
            content.innerHTML = `
                <div class="empty-state">
                    <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="empty-state-text">Belum ada kota</div>
                    <button class="btn btn-primary btn-sm" onclick="openCreateCityModal(${provinceId})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Kota
                    </button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading cities:', error);
        content.innerHTML = `
            <div class="empty-state">
                <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="empty-state-text">Gagal memuat kota</div>
            </div>
        `;
    });
}

// Render cities
function renderCities(cities, provinceId, container) {
    if (cities.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="empty-state-text">Belum ada kota</div>
                <button class="btn btn-primary btn-sm" onclick="openCreateCityModal(${provinceId})">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Kota
                </button>
            </div>
        `;
        return;
    }
    
    let html = '';
    cities.forEach(city => {
        html += `
            <div class="tree-item" data-city-id="${city.id}">
                <div class="tree-item-info">
                    <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="${city.id}" onclick="event.stopPropagation()">
                    <button class="expand-btn" onclick="toggleCity(${city.id})">
                        <svg class="expand-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <div>
                        <div class="tree-item-name">${city.name || '-'}</div>
                        <div class="tree-item-counts">
                            <span>Type: ${city.type || 'Kota'}</span>
                            <span>Districts: ${city.districts_count || 0}</span>
                            <span>Branches: ${city.branches_count || 0}</span>
                            <span>Surveys: ${city.surveys_count || 0}</span>
                        </div>
                    </div>
                </div>
                <div class="tree-item-actions">
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openViewCityModal(${city.id})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Lihat
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openEditCityModal(${city.id})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openDeleteCityModal(${city.id}, '${city.name}')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>
            
            <!-- Districts Container (Hidden by default) -->
            <div id="districts-${city.id}" class="districts-container" style="display: none; margin-left: 40px; margin-top: 8px;">
                <div class="level-indicator">
                    <span class="level-badge">DISTRICTS</span>
                    <span>Di bawah ${city.name}</span>
                    <button class="btn btn-primary btn-sm ml-auto" onclick="openCreateDistrictModal(${city.id})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Kecamatan
                    </button>
                </div>
                <div class="tree-content">
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="empty-state-text">Belum ada kecamatan</div>
                        <button class="btn btn-primary btn-sm" onclick="openCreateDistrictModal(${city.id})">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Tambah Kecamatan
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Toggle City expansion
function toggleCity(cityId) {
    const districtsContainer = document.getElementById(`districts-${cityId}`);
    const cityItem = document.querySelector(`[data-city-id="${cityId}"]`);
    const expandBtn = cityItem.querySelector('.expand-btn');
    const expandIcon = expandBtn.querySelector('.expand-icon');
    
    if (expandedCities.has(cityId)) {
        // Collapse
        districtsContainer.style.display = 'none';
        expandIcon.classList.remove('expanded');
        expandedCities.delete(cityId);
    } else {
        // Expand
        districtsContainer.style.display = 'block';
        expandIcon.classList.add('expanded');
        expandedCities.add(cityId);
        
        // Load districts if not already loaded
        loadDistricts(cityId);
    }
}

// Load districts for a city
function loadDistricts(cityId) {
    const districtsContainer = document.getElementById(`districts-${cityId}`);
    const content = districtsContainer.querySelector('.tree-content');
    
    // Show loading
    content.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <div style="display: inline-block; width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 8px; color: #666; font-size: 12px;">Memuat kecamatan...</p>
        </div>
    `;
    
    fetch(`/system/cities/${cityId}/districts`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            renderDistricts(data.data, cityId, content);
        } else {
            content.innerHTML = `
                <div class="empty-state">
                    <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="empty-state-text">Belum ada kecamatan</div>
                    <button class="btn btn-primary btn-sm" onclick="openCreateDistrictModal(${cityId})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Kecamatan
                    </button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading districts:', error);
        content.innerHTML = `
            <div class="empty-state">
                <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="empty-state-text">Gagal memuat kecamatan</div>
            </div>
        `;
    });
}

// Render districts
function renderDistricts(districts, cityId, container) {
    if (districts.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="empty-state-text">Belum ada kecamatan</div>
                <button class="btn btn-primary btn-sm" onclick="openCreateDistrictModal(${cityId})">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Kecamatan
                </button>
            </div>
        `;
        return;
    }
    
    let html = '';
    districts.forEach(district => {
        html += `
            <div class="tree-item" data-district-id="${district.id}">
                <div class="tree-item-info">
                    <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="${district.id}" onclick="event.stopPropagation()">
                    <button class="expand-btn" onclick="toggleDistrict(${district.id})">
                        <svg class="expand-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <div>
                        <div class="tree-item-name">${district.name || '-'}</div>
                        <div class="tree-item-counts">
                            <span>Subdistricts: ${district.subdistricts_count || 0}</span>
                            <span>Branches: ${district.branches_count || 0}</span>
                            <span>Surveys: ${district.surveys_count || 0}</span>
                        </div>
                    </div>
                </div>
                <div class="tree-item-actions">
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openViewDistrictModal(${district.id})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Lihat
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openEditDistrictModal(${district.id})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openDeleteDistrictModal(${district.id}, '${district.name}')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>
            
            <!-- Subdistricts Container (Hidden by default) -->
            <div id="subdistricts-${district.id}" class="subdistricts-container" style="display: none; margin-left: 40px; margin-top: 8px;">
                <div class="level-indicator">
                    <span class="level-badge">SUBDISTRICTS</span>
                    <span>Di bawah ${district.name}</span>
                    <button class="btn btn-primary btn-sm ml-auto" onclick="openCreateSubdistrictModal(${district.id})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Kelurahan
                    </button>
                </div>
                <div class="tree-content">
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="empty-state-text">Belum ada kelurahan</div>
                        <button class="btn btn-primary btn-sm" onclick="openCreateSubdistrictModal(${district.id})">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Tambah Kelurahan
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Toggle District expansion
function toggleDistrict(districtId) {
    const subdistrictsContainer = document.getElementById(`subdistricts-${districtId}`);
    const districtItem = document.querySelector(`[data-district-id="${districtId}"]`);
    const expandBtn = districtItem.querySelector('.expand-btn');
    const expandIcon = expandBtn.querySelector('.expand-icon');
    
    if (expandedDistricts.has(districtId)) {
        // Collapse
        subdistrictsContainer.style.display = 'none';
        expandIcon.classList.remove('expanded');
        expandedDistricts.delete(districtId);
    } else {
        // Expand
        subdistrictsContainer.style.display = 'block';
        expandIcon.classList.add('expanded');
        expandedDistricts.add(districtId);
        
        // Load subdistricts if not already loaded
        loadSubdistricts(districtId);
    }
}

// Load subdistricts for a district
function loadSubdistricts(districtId) {
    const subdistrictsContainer = document.getElementById(`subdistricts-${districtId}`);
    const content = subdistrictsContainer.querySelector('.tree-content');
    
    // Show loading
    content.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <div style="display: inline-block; width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 8px; color: #666; font-size: 12px;">Memuat kelurahan...</p>
        </div>
    `;
    
    fetch(`/system/districts/${districtId}/subdistricts`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            renderSubdistricts(data.data, districtId, content);
        } else {
            content.innerHTML = `
                <div class="empty-state">
                    <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="empty-state-text">Belum ada kelurahan</div>
                    <button class="btn btn-primary btn-sm" onclick="openCreateSubdistrictModal(${districtId})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Kelurahan
                    </button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading subdistricts:', error);
        content.innerHTML = `
            <div class="empty-state">
                <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="empty-state-text">Gagal memuat kelurahan</div>
            </div>
        `;
    });
}

// Render subdistricts
function renderSubdistricts(subdistricts, districtId, container) {
    if (subdistricts.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <svg class="empty-state-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="empty-state-text">Belum ada kelurahan</div>
                <button class="btn btn-primary btn-sm" onclick="openCreateSubdistrictModal(${districtId})">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Kelurahan
                </button>
            </div>
        `;
        return;
    }
    
    let html = '';
    subdistricts.forEach(subdistrict => {
        html += `
            <div class="tree-item" data-subdistrict-id="${subdistrict.id}">
                <div class="tree-item-info">
                    <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="${subdistrict.id}" onclick="event.stopPropagation()">
                    <div>
                        <div class="tree-item-name">${subdistrict.name || '-'}</div>
                        <div class="tree-item-counts">
                            <span>Postal Code: ${subdistrict.postal_code || '-'}</span>
                            <span>Branches: ${subdistrict.branches_count || 0}</span>
                            <span>Surveys: ${subdistrict.surveys_count || 0}</span>
                        </div>
                    </div>
                </div>
                <div class="tree-item-actions">
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openViewSubdistrictModal(${subdistrict.id})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openEditSubdistrictModal(${subdistrict.id})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); openDeleteSubdistrictModal(${subdistrict.id}, '${subdistrict.name}')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// ==================== CREATE MODAL FUNCTIONS ====================

// Create City Modal
function openCreateCityModal(provinceId) {
    openModal('Create New City');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitCityForm(event, ${provinceId})">
            <div class="modal-section">
                <div class="modal-section-title">City Information</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">City Name *</label>
                        <input type="text" name="name" class="form-input" placeholder="Masukkan nama kota" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">City Type</label>
                        <select name="type" class="form-input">
                            <option value="Kota">Kota</option>
                            <option value="Kabupaten">Kabupaten</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" form="form" class="btn btn-primary">Simpan Kota</button>
    `;
}

// Create District Modal
function openCreateDistrictModal(cityId) {
    openModal('Tambah Kecamatan Baru');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitDistrictForm(event, ${cityId})">
            <div class="modal-section">
                <div class="modal-section-title">Informasi Kecamatan</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Nama Kecamatan *</label>
                        <input type="text" name="name" class="form-input" placeholder="Masukkan nama kecamatan" required>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" form="form" class="btn btn-primary">Simpan Kecamatan</button>
    `;
}

// Create Subdistrict Modal
function openCreateSubdistrictModal(districtId) {
    openModal('Tambah Kelurahan Baru');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="form" onsubmit="submitSubdistrictForm(event, ${districtId})">
            <div class="modal-section">
                <div class="modal-section-title">Informasi Kelurahan</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Nama Kelurahan *</label>
                        <input type="text" name="name" class="form-input" placeholder="Masukkan nama kelurahan" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kode Pos</label>
                        <input type="text" name="postal_code" class="form-input" placeholder="Masukkan kode pos">
                    </div>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button type="submit" form="form" class="btn btn-primary">Simpan Kelurahan</button>
    `;
}

// ==================== VIEW MODAL FUNCTIONS ====================

// View City Modal
function openViewCityModal(cityId) {
    openModal('Lihat Kota');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/system/cities/${cityId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Gagal memuat data kota');
        }
        return response.json();
    })
    .then(data => {
        if (data.status !== 'success') {
            throw new Error('Gagal memuat data kota');
        }
        document.getElementById('modalBody').innerHTML = `
            <div class="modal-section">
                <div class="modal-section-title">Informasi Kota</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="detail-item">
                        <label class="form-label">Nama Kota</label>
                        <p class="detail-value">${data.data.name || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Tipe Kota</label>
                        <p class="detail-value">${data.data.type || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Provinsi</label>
                        <p class="detail-value">${data.data.province?.name || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Jumlah Kecamatan</label>
                        <p class="detail-value">${data.data.districts_count || 0}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Jumlah Branch</label>
                        <p class="detail-value">${data.data.branches_count || 0}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Jumlah Survey</label>
                        <p class="detail-value">${data.data.surveys_count || 0}</p>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="openEditCityModal(${cityId})">Ubah Kota</button>
        `;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div style="color: #dc2626; font-size: 48px; margin-bottom: 16px;">⚠️</div>
                <h3 style="color: #dc2626; margin-bottom: 8px;">Gagal Memuat Kota</h3>
                <p style="color: #6b7280; margin-bottom: 16px;">Data kota belum berhasil dimuat. Silakan coba lagi.</p>
                <button class="btn btn-primary" onclick="openViewCityModal(${cityId})">Coba Lagi</button>
            </div>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
        `;
    });
}

// View District Modal
function openViewDistrictModal(districtId) {
    openModal('Lihat Kecamatan');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/system/districts/${districtId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Gagal memuat data kecamatan');
        }
        return response.json();
    })
    .then(data => {
        if (data.status !== 'success') {
            throw new Error('Gagal memuat data kecamatan');
        }
        document.getElementById('modalBody').innerHTML = `
            <div class="modal-section">
                <div class="modal-section-title">Informasi Kecamatan</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="detail-item">
                        <label class="form-label">Nama Kecamatan</label>
                        <p class="detail-value">${data.data.name || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Kota</label>
                        <p class="detail-value">${data.data.city?.name || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Provinsi</label>
                        <p class="detail-value">${data.data.city?.province?.name || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Jumlah Kelurahan</label>
                        <p class="detail-value">${data.data.subdistricts_count || 0}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Jumlah Branch</label>
                        <p class="detail-value">${data.data.branches_count || 0}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Jumlah Survey</label>
                        <p class="detail-value">${data.data.surveys_count || 0}</p>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="openEditDistrictModal(${districtId})">Ubah Kecamatan</button>
        `;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div style="color: #dc2626; font-size: 48px; margin-bottom: 16px;">⚠️</div>
                <h3 style="color: #dc2626; margin-bottom: 8px;">Gagal Memuat Kecamatan</h3>
                <p style="color: #6b7280; margin-bottom: 16px;">Data kecamatan belum berhasil dimuat. Silakan coba lagi.</p>
                <button class="btn btn-primary" onclick="openViewDistrictModal(${districtId})">Coba Lagi</button>
            </div>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
        `;
    });
}

// View Subdistrict Modal
function openViewSubdistrictModal(subdistrictId) {
    openModal('Lihat Kelurahan');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/system/subdistricts/${subdistrictId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Gagal memuat data kelurahan');
        }
        return response.json();
    })
    .then(data => {
        if (data.status !== 'success') {
            throw new Error('Gagal memuat data kelurahan');
        }
        document.getElementById('modalBody').innerHTML = `
            <div class="modal-section">
                <div class="modal-section-title">Informasi Kelurahan</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="detail-item">
                        <label class="form-label">Nama Kelurahan</label>
                        <p class="detail-value">${data.data.name || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Kode Pos</label>
                        <p class="detail-value">${data.data.postal_code || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Kecamatan</label>
                        <p class="detail-value">${data.data.district?.name || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Kota</label>
                        <p class="detail-value">${data.data.district?.city?.name || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Provinsi</label>
                        <p class="detail-value">${data.data.district?.city?.province?.name || '-'}</p>
                    </div>
                    <div class="detail-item">
                        <label class="form-label">Jumlah Branch</label>
                        <p class="detail-value">${data.data.branches_count || 0}</p>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="openEditSubdistrictModal(${subdistrictId})">Ubah Kelurahan</button>
        `;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div style="color: #dc2626; font-size: 48px; margin-bottom: 16px;">⚠️</div>
                <h3 style="color: #dc2626; margin-bottom: 8px;">Gagal Memuat Kelurahan</h3>
                <p style="color: #6b7280; margin-bottom: 16px;">Data kelurahan belum berhasil dimuat. Silakan coba lagi.</p>
                <button class="btn btn-primary" onclick="openViewSubdistrictModal(${subdistrictId})">Coba Lagi</button>
            </div>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
        `;
    });
}

// ==================== EDIT MODAL FUNCTIONS ====================

// Edit City Modal
function openEditCityModal(cityId) {
    openModal('Ubah Kota');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/system/cities/${cityId}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('modalBody').innerHTML = `
            <form id="form" onsubmit="submitCityEditForm(event, ${cityId})">
                <div class="modal-section">
                    <div class="modal-section-title">Informasi Kota</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Nama Kota *</label>
                            <input type="text" name="name" class="form-input" value="${data.data.name || ''}" placeholder="Masukkan nama kota" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tipe Kota</label>
                            <select name="type" class="form-input">
                                <option value="Kota" ${data.data.type === 'Kota' ? 'selected' : ''}>Kota</option>
                                <option value="Kabupaten" ${data.data.type === 'Kabupaten' ? 'selected' : ''}>Kabupaten</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button type="submit" form="form" class="btn btn-primary">Perbarui Kota</button>
        `;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div style="color: #dc2626; font-size: 48px; margin-bottom: 16px;">⚠️</div>
                <h3 style="color: #dc2626; margin-bottom: 8px;">Gagal Memuat Kota</h3>
                <p style="color: #6b7280; margin-bottom: 16px;">Data kota untuk diedit belum berhasil dimuat.</p>
            </div>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
        `;
    });
}

// Edit District Modal
function openEditDistrictModal(districtId) {
    openModal('Ubah Kecamatan');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/system/districts/${districtId}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('modalBody').innerHTML = `
            <form id="form" onsubmit="submitDistrictEditForm(event, ${districtId})">
                <div class="modal-section">
                    <div class="modal-section-title">Informasi Kecamatan</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Nama Kecamatan *</label>
                            <input type="text" name="name" class="form-input" value="${data.data.name || ''}" placeholder="Masukkan nama kecamatan" required>
                        </div>
                    </div>
                </div>
            </form>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button type="submit" form="form" class="btn btn-primary">Perbarui Kecamatan</button>
        `;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div style="color: #dc2626; font-size: 48px; margin-bottom: 16px;">⚠️</div>
                <h3 style="color: #dc2626; margin-bottom: 8px;">Gagal Memuat Kecamatan</h3>
                <p style="color: #6b7280; margin-bottom: 16px;">Data kecamatan untuk diedit belum berhasil dimuat.</p>
            </div>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
        `;
    });
}

// Edit Subdistrict Modal
function openEditSubdistrictModal(subdistrictId) {
    openModal('Ubah Kelurahan');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Memuat...</p></div>';
    
    fetch(`/system/subdistricts/${subdistrictId}/edit`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('modalBody').innerHTML = `
            <form id="form" onsubmit="submitSubdistrictEditForm(event, ${subdistrictId})">
                <div class="modal-section">
                    <div class="modal-section-title">Informasi Kelurahan</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Nama Kelurahan *</label>
                            <input type="text" name="name" class="form-input" value="${data.data.name || ''}" placeholder="Masukkan nama kelurahan" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kode Pos</label>
                            <input type="text" name="postal_code" class="form-input" value="${data.data.postal_code || ''}" placeholder="Masukkan kode pos">
                        </div>
                    </div>
                </div>
            </form>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            <button type="submit" form="form" class="btn btn-primary">Perbarui Kelurahan</button>
        `;
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('modalBody').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div style="color: #dc2626; font-size: 48px; margin-bottom: 16px;">⚠️</div>
                <h3 style="color: #dc2626; margin-bottom: 8px;">Gagal Memuat Kelurahan</h3>
                <p style="color: #6b7280; margin-bottom: 16px;">Data kelurahan untuk diedit belum berhasil dimuat.</p>
            </div>
        `;
        document.getElementById('modalFooter').innerHTML = `
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Tutup</button>
        `;
    });
}

// ==================== DELETE MODAL FUNCTIONS ====================

// Delete City Modal
function openDeleteCityModal(cityId, cityName) {
    // Get detailed delete info first
    fetch(`/system/provinces/${cityId}/delete-info/city`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const info = data.data;
            let message = `Apakah kamu yakin ingin menghapus kota <strong>"${info.name}"</strong>?`;
            
            if (info.related_data.length > 0) {
                message += `<br><br><strong>Kota ini memiliki:</strong><br>`;
                message += info.related_data.map(item => `• ${item}`).join('<br>');
                
                if (info.cascade_delete) {
                    message += `<br><br><span class="text-red-600">Semua data terkait juga akan ikut dihapus.</span>`;
                }
            }
            
            if (!info.can_delete) {
                message += `<br><br><span class="text-red-600 font-bold">Tidak bisa dihapus: kota ini masih memiliki branch.</span>`;
            }
            
            openDeleteModal();
            document.getElementById('deleteModalTitle').textContent = 'Hapus Kota';
            document.getElementById('deleteModalDescription').innerHTML = message;
            
            if (info.can_delete) {
                document.getElementById('deleteModalButtons').innerHTML = `
                    <button class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
                    <button class="btn btn-primary" onclick="confirmDeleteCity(${cityId})">Hapus Kota</button>
                `;
            } else {
                document.getElementById('deleteModalButtons').innerHTML = `
                    <button class="btn btn-secondary" onclick="closeDeleteModal()">Tutup</button>
                `;
            }
        } else {
            showErrorModal('Gagal mengambil informasi penghapusan');
        }
    })
    .catch(error => {
        console.error('Error getting delete info:', error);
        showErrorModal('Gagal mengambil informasi penghapusan');
    });
}

// Delete District Modal
function openDeleteDistrictModal(districtId, districtName) {
    // Get detailed delete info first
    fetch(`/system/provinces/${districtId}/delete-info/district`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const info = data.data;
            let message = `Apakah kamu yakin ingin menghapus kecamatan <strong>"${info.name}"</strong>?`;
            
            if (info.related_data.length > 0) {
                message += `<br><br><strong>Kecamatan ini memiliki:</strong><br>`;
                message += info.related_data.map(item => `• ${item}`).join('<br>');
                
                if (info.cascade_delete) {
                    message += `<br><br><span class="text-red-600">Semua data terkait juga akan ikut dihapus.</span>`;
                }
            }
            
            if (!info.can_delete) {
                message += `<br><br><span class="text-red-600 font-bold">Tidak bisa dihapus: kecamatan ini masih memiliki branch.</span>`;
            }
            
            openDeleteModal();
            document.getElementById('deleteModalTitle').textContent = 'Hapus Kecamatan';
            document.getElementById('deleteModalDescription').innerHTML = message;
            
            if (info.can_delete) {
                document.getElementById('deleteModalButtons').innerHTML = `
                    <button class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
                    <button class="btn btn-primary" onclick="confirmDeleteDistrict(${districtId})">Hapus Kecamatan</button>
                `;
            } else {
                document.getElementById('deleteModalButtons').innerHTML = `
                    <button class="btn btn-secondary" onclick="closeDeleteModal()">Tutup</button>
                `;
            }
        } else {
            showErrorModal('Gagal mengambil informasi penghapusan');
        }
    })
    .catch(error => {
        console.error('Error getting delete info:', error);
        showErrorModal('Gagal mengambil informasi penghapusan');
    });
}

// Delete Subdistrict Modal
function openDeleteSubdistrictModal(subdistrictId, subdistrictName) {
    // Get detailed delete info first
    fetch(`/system/provinces/${subdistrictId}/delete-info/subdistrict`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const info = data.data;
            let message = `Apakah kamu yakin ingin menghapus kelurahan <strong>"${info.name}"</strong>?`;
            
            if (info.related_data.length > 0) {
                message += `<br><br><strong>Kelurahan ini memiliki:</strong><br>`;
                message += info.related_data.map(item => `• ${item}`).join('<br>');
            }
            
            if (!info.can_delete) {
                message += `<br><br><span class="text-red-600 font-bold">Tidak bisa dihapus: kelurahan ini masih memiliki data terkait.</span>`;
            }
            
            openDeleteModal();
            document.getElementById('deleteModalTitle').textContent = 'Hapus Kelurahan';
            document.getElementById('deleteModalDescription').innerHTML = message;
            
            if (info.can_delete) {
                document.getElementById('deleteModalButtons').innerHTML = `
                    <button class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
                    <button class="btn btn-primary" onclick="confirmDeleteSubdistrict(${subdistrictId})">Hapus Kelurahan</button>
                `;
            } else {
                document.getElementById('deleteModalButtons').innerHTML = `
                    <button class="btn btn-secondary" onclick="closeDeleteModal()">Tutup</button>
                `;
            }
        } else {
            showErrorModal('Gagal mengambil informasi penghapusan');
        }
    })
    .catch(error => {
        console.error('Error getting delete info:', error);
        showErrorModal('Gagal mengambil informasi penghapusan');
    });
}

// ==================== FORM SUBMISSION FUNCTIONS ====================

// Submit City Form
function submitCityForm(event, provinceId) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('province_id', provinceId);
    
    fetch('/system/cities', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            showSuccessModal('Kota berhasil dibuat!');
            // Reload cities for this province
            loadCities(provinceId);
        } else {
            showErrorModal(data.message || 'Gagal membuat kota');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan saat membuat kota');
    });
}

// Submit District Form
function submitDistrictForm(event, cityId) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('city_id', cityId);
    
    fetch('/system/districts', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            showSuccessModal('Kecamatan berhasil dibuat!');
            // Reload districts for this city
            loadDistricts(cityId);
        } else {
            showErrorModal(data.message || 'Gagal membuat kecamatan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan saat membuat kecamatan');
    });
}

// Submit Subdistrict Form
function submitSubdistrictForm(event, districtId) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('district_id', districtId);
    
    fetch('/system/subdistricts', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            showSuccessModal('Kelurahan berhasil dibuat!');
            // Reload subdistricts for this district
            loadSubdistricts(districtId);
        } else {
            showErrorModal(data.message || 'Gagal membuat kelurahan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan saat membuat kelurahan');
    });
}

// ==================== EDIT FORM SUBMISSION FUNCTIONS ====================

// Submit City Edit Form
function submitCityEditForm(event, cityId) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    fetch(`/system/cities/${cityId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            showSuccessModal('Kota berhasil diperbarui!');
            // Reload the tree to reflect changes
            location.reload();
        } else {
            showErrorModal(data.message || 'Gagal memperbarui kota');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan saat memperbarui kota');
    });
}

// Submit District Edit Form
function submitDistrictEditForm(event, districtId) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    fetch(`/system/districts/${districtId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            showSuccessModal('Kecamatan berhasil diperbarui!');
            // Reload the tree to reflect changes
            location.reload();
        } else {
            showErrorModal(data.message || 'Gagal memperbarui kecamatan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan saat memperbarui kecamatan');
    });
}

// Submit Subdistrict Edit Form
function submitSubdistrictEditForm(event, subdistrictId) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    fetch(`/system/subdistricts/${subdistrictId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal();
            showSuccessModal('Kelurahan berhasil diperbarui!');
            // Reload the tree to reflect changes
            location.reload();
        } else {
            showErrorModal(data.message || 'Gagal memperbarui kelurahan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan saat memperbarui kelurahan');
    });
}

// ==================== DELETE CONFIRMATION FUNCTIONS ====================

// Confirm Delete City
function confirmDeleteCity(cityId) {
    closeDeleteModal();
    
    fetch(`/system/cities/${cityId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            let message = 'Kota berhasil dihapus!';
            if (data.cascade_deleted && data.cascade_deleted.length > 0) {
                message += '\n\nData terkait yang ikut terhapus: ' + data.cascade_deleted.join(', ');
            }
            showSuccessModal(message);
            // Reload the tree to reflect changes
            location.reload();
        } else {
            showErrorModal(data.message || 'Gagal menghapus kota');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan saat menghapus kota');
    });
}

// Confirm Delete District
function confirmDeleteDistrict(districtId) {
    closeDeleteModal();
    
    fetch(`/system/districts/${districtId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            let message = 'Kecamatan berhasil dihapus!';
            if (data.cascade_deleted && data.cascade_deleted.length > 0) {
                message += '\n\nData terkait yang ikut terhapus: ' + data.cascade_deleted.join(', ');
            }
            showSuccessModal(message);
            // Reload the tree to reflect changes
            location.reload();
        } else {
            showErrorModal(data.message || 'Gagal menghapus kecamatan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan saat menghapus kecamatan');
    });
}

// Confirm Delete Subdistrict
function confirmDeleteSubdistrict(subdistrictId) {
    closeDeleteModal();
    
    fetch(`/system/subdistricts/${subdistrictId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showSuccessModal('Kelurahan berhasil dihapus!');
            // Reload the tree to reflect changes
            location.reload();
        } else {
            showErrorModal(data.message || 'Gagal menghapus kelurahan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Terjadi kesalahan saat menghapus kelurahan');
    });
}

// ==================== HELP MODAL FUNCTION ====================

function showHelpModal() {
    openModal('Cara Menggunakan Master Lokasi');
    document.getElementById('modalBody').innerHTML = `
        <div class="modal-section">
            <div class="modal-section-title">Pengelolaan Lokasi Bertingkat</div>
            <div class="space-y-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-900 mb-2">Level Provinsi</h3>
                    <p class="text-blue-800 text-sm">Buat provinsi terlebih dahulu. Klik "Tambah Provinsi" untuk membuat provinsi baru.</p>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-green-900 mb-2">City Level</h3>
                    <p class="text-green-800 text-sm">Buka provinsi lalu klik "Tambah Kota" pada header atau area kosong untuk membuat kota di dalam provinsi tersebut.</p>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-yellow-900 mb-2">District Level</h3>
                    <p class="text-yellow-800 text-sm">Buka kota lalu klik "Tambah Kecamatan" pada header atau area kosong untuk membuat kecamatan di dalam kota tersebut.</p>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-purple-900 mb-2">Subdistrict Level</h3>
                    <p class="text-purple-800 text-sm">Buka kecamatan lalu klik "Tambah Kelurahan" pada header atau area kosong untuk membuat kelurahan di dalam kecamatan tersebut.</p>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-gray-900 mb-2">Tips</h3>
                    <ul class="text-gray-800 text-sm space-y-1">
                        <li>• Use the expand/collapse arrows to navigate the hierarchy</li>
                        <li>• Each level shows counts of related data</li>
                        <li>• You can view, edit, or delete any item using the action buttons</li>
                        <li>• The system prevents deletion if there are related records</li>
                    </ul>
                </div>
            </div>
        </div>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Mengerti</button>
    `;
}

function showHierarchyInfo() {
    openModal('Informasi Tampilan Pohon Hierarki');
    document.getElementById('modalBody').innerHTML = `
        <div class="modal-section">
            <div class="modal-section-title">Ringkasan Struktur Pohon</div>
            <div class="space-y-4">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-200">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <h3 class="font-semibold text-blue-900">Apa itu Tampilan Pohon Hierarki?</h3>
                    </div>
                    <p class="text-blue-800 text-sm">This view displays your location data in a tree structure, showing the relationship between provinces, cities, districts, and subdistricts. Each level can be expanded or collapsed to show its children.</p>
                </div>
                
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg border border-green-200">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <h3 class="font-semibold text-green-900">Fitur Navigasi</h3>
                    </div>
                    <ul class="text-green-800 text-sm space-y-1">
                        <li>• Click the arrow icons to expand/collapse levels</li>
                        <li>• Each level shows count of related items</li>
                        <li>• Use "Add" buttons to create new items at each level</li>
                        <li>• View, edit, or delete items using action buttons</li>
                    </ul>
                </div>
                
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-4 rounded-lg border border-purple-200">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                        <h3 class="font-semibold text-purple-900">Benefits</h3>
                    </div>
                    <ul class="text-purple-800 text-sm space-y-1">
                        <li>• Visual representation of location hierarchy</li>
                        <li>• Easy navigation between levels</li>
                        <li>• Clear parent-child relationships</li>
                        <li>• Efficient data management</li>
                    </ul>
                </div>
            </div>
        </div>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Understood!</button>
    `;
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
@endpush
