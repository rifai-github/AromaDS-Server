@extends('layouts.app')

@section('title', 'Job Advice')
@section('breadcrumb', 'Home / Marketing / Job Advice')

@section('content')
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
    /* Custom Flatpickr Styling */
    .flatpickr-calendar {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 8px !important;
    }
    
    .flatpickr-day.selected {
        background: #214589 !important;
        border-color: #214589 !important;
    }
    
    .flatpickr-day:hover {
        background: #eff6ff !important;
    }

    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }
    
    /* Ensure all elements use border-box */
    *, *::before, *::after {
        box-sizing: border-box;
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
        background: #f8fafc;
        color: #374151;
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 10;
        white-space: nowrap;
        min-width: 120px;
        overflow: visible;
        text-overflow: unset;
    }
    
    .responsive-table td {
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
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
        text-align: center;
        padding: 12px 8px;
        border: 1px solid #ddd;
        white-space: nowrap;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }
    
    .responsive-table tbody tr:nth-child(odd) {
        background-color: #ffffff;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff !important;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 1000px;
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
    
    .btn-outline {
        background-color: white;
        color: #214589;
        border: 2px solid #214589;
        font-weight: 500;
    }
    
    .btn-outline:hover {
        background-color: #214589;
        color: white;
    }
    
    .btn-danger {
        background-color: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background-color: #dc2626;
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
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
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
    
    .page-dropdown-container span {
        display: inline;
        white-space: nowrap;
    }
    
    /* Pagination button styles */
    .pagination-btn {
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid #e5e7eb;
        background-color: #f9fafb;
        color: #374151;
    }
    
    .pagination-btn:hover {
        background-color: #f3f4f6;
        border-color: #d1d5db;
        color: #1f2937;
    }
    
    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .pagination-btn:disabled:hover {
        background-color: #f9fafb;
        border-color: #e5e7eb;
        color: #374151;
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
    }
    
    .delete-modal-container {
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
    }
    
    .error-modal-container {
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
    }
    
    .error-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
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
    
    .btn-error-close:hover {
        background-color: #f8fafc;
        border-color: #1e3a8a;
        color: #1e3a8a;
    }
    
    .btn-error-retry {
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
    
    .btn-error-retry:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
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
    }
    
    .success-modal-container {
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
    }
    
    .success-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
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
    
    .success-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }
    
    .btn-success-close {
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
    
    .btn-success-close:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
    }
    
    /* Form Input Styling */
    input[type="date"], input[type="text"], select, textarea {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
        width: 100%;
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

    .form-label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        color: #374151;
        font-size: 14px;
    }

    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background-color: white;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 80px;
    }

    /* Grid Layout for Modal */
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-row.single {
        grid-template-columns: 1fr;
    }

    .form-row.full-width {
        grid-template-columns: 1fr;
    }

    /* Modal Form Container */
    .modal-form-container {
        border: 2px solid #214589;
        border-radius: 8px;
        padding: 20px;
        background-color: #f8fafc;
        margin-bottom: 20px;
    }

    .modal-form-section {
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }

    .modal-form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .modal-form-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #214589;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #214589;
    }
    
    /* MOM6: Room row styles */
    .room-row {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    #roomsContainer:empty::after {
        content: 'No rooms added yet. Click "Add Room" to add a room.';
        display: block;
        text-align: center;
        padding: 20px;
        color: #6b7280;
        font-style: italic;
    }
    
    /* Tablet and Small Desktop Responsive */
    @media (max-width: 1200px) and (min-width: 769px) {
        .flex-wrap {
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .flex-wrap > div {
            flex-shrink: 0;
            min-width: 200px;
        }
    }
    
    /* Filter Row Responsive - Grid Layout */
    .grid {
        display: grid;
    }
    
    .grid-cols-1 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
    
    @media (min-width: 768px) {
        .grid.md\:grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    
    @media (min-width: 1024px) {
        .grid.lg\:grid-cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    
    @media (min-width: 1280px) {
        .grid.xl\:grid-cols-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
    
    @media (min-width: 1536px) {
        .grid.\32xl\:grid-cols-5 {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }
    }
    
    /* Header responsive */
    @media (max-width: 768px) {
        .flex.flex-row.justify-between {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .flex.flex-row.justify-between > div:last-child {
            width: 100%;
            justify-content: flex-start;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
    /* Select2 Custom Styles for Scroll and Modal consistency */
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .select2-dropdown {
        border: 1px solid #ced4da;
        z-index: 1060; /* Higher than modal */
    }
    .select2-results__options {
        max-height: 250px; /* Enable scroll if entries > ~8 */
        overflow-y: auto;
    }
    .select2-container {
        width: 100% !important;
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">

        <!-- Job Advice Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <h1 class="text-xl font-semibold text-[#214589]">Job Advice</h1>
            <div class="flex flex-row gap-2 items-center">
                <button class="btn btn-primary" onclick="console.log('Button clicked'); openCreateModal();">
                    <i class="fas fa-plus"></i>
                    <span>Add New Job Advice</span>
                </button>
            </div>
        </div>

        <!-- Filter Row -->
        <div class="w-full bg-white p-4 border-b">
            <div class="flex flex-row flex-wrap items-end gap-4">
                <!-- Date From -->
                <div class="flex flex-col gap-1 w-full md:w-48">
                    <label class="text-sm font-medium text-gray-700">Date From:</label>
                    <input type="text" id="start_date" name="start_date" class="flatpickr-date px-3 py-1.5 border border-gray-300 rounded text-sm w-full" readonly placeholder="Select date...">
                </div>
                
                <!-- Date To -->
                <div class="flex flex-col gap-1 w-full md:w-48">
                    <label class="text-sm font-medium text-gray-700">Date To:</label>
                    <input type="text" id="end_date" name="end_date" class="flatpickr-date px-3 py-1.5 border border-gray-300 rounded text-sm w-full" readonly placeholder="Select date...">
                </div>
                
                <!-- Filter Buttons -->
                <div class="flex flex-row gap-2">
                    <button onclick="applyFilters()" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter"></i>
                        <span>Apply Filter</span>
                    </button>
                    <button onclick="clearFilters()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-undo"></i>
                        <span>Clear</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full bg-white p-4">
            <div class="flex flex-row justify-start items-center">
                <div class="flex flex-row justify-start items-center">
                    <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                    <label for="selectAll" class="ml-2 text-sm text-gray-700 cursor-pointer">Select all</label>
                </div>
                
                <!-- Delete Button -->
                <button class="btn btn-secondary btn-sm ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>

        </div>

        <!-- Table Container -->
        <div class="table-container">
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="selectAllHeader" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="job_advice_number">Job Advice No</th>
                        <th data-column="type">Type</th>
                        <th data-column="reference_number">Reference No</th>
                        <th data-column="company_name">Customer</th>
                        <th data-column="submittedBy.name">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="expected_date" data-type="date">Expected Date</th>
                        <th data-column="remove_date" data-type="date">Remove Date</th>
                        <th data-column="status">Status</th>
                        <th data-column="date_approval" data-type="date">Approved At</th>
                        <th data-column="approvedBy.name">Approved By</th>
                        <th data-column="with_invoicing">With Invoicing?</th>
                        <th data-column="with_materials">With Material?</th>
                        <th data-column="notes">Notes</th>
                        <th data-column="updated_at" data-type="date">Last Update At</th>
                        <th data-column="updater.name">Last Update By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobAdvices ?? [] as $jobAdvice)
                        <tr class="table-row-hover border-b border-gray-200 cursor-pointer" data-id="{{ $jobAdvice->id }}" onclick="window.location.href='{{ route('marketing.job-advices.show', $jobAdvice->id) }}'">
                            <td>
                                <input type="checkbox" class="row-checkbox w-4 h-4 border border-gray-300 rounded cursor-pointer" 
                                    value="{{ $jobAdvice->id }}" 
                                    onclick="event.stopPropagation();"
                                    @if(!in_array($jobAdvice->status, ['cancelled', 'draft'])) disabled title="Only cancelled or draft Job Advices can be deleted" @endif>
                            </td>
                            <td>{{ $jobAdvice->job_advice_number ?? 'N/A' }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full 
                                    {{ $jobAdvice->type === 'install' ? 'bg-blue-100 text-blue-800' : 
                                       ($jobAdvice->type === 'remove' ? 'bg-red-100 text-red-800' : 
                                       ($jobAdvice->type === 'service' ? 'bg-green-100 text-green-800' : 
                                       ($jobAdvice->type === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'))) }}">
                                    {{ ucfirst($jobAdvice->type ?? 'N/A') }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $type = strtolower($jobAdvice->type ?? '');
                                    $refNo = $jobAdvice->reference_number;
                                    
                                    if (in_array($type, ['install free', 'install_free'])) {
                                        $refNo = $jobAdvice->quotation->quotation_number ?? ($jobAdvice->reference_number ?? 'N/A');
                                    } elseif (in_array($type, ['install'])) {
                                        $refNo = $jobAdvice->contract->contract_number ?? ($jobAdvice->reference_number ?? 'N/A');
                                    }
                                @endphp
                                {{ $refNo }}
                            </td>
                            <td>{{ $jobAdvice->company_name ?? ($jobAdvice->contract->customer->name ?? 'N/A') }}</td>
                            <td>{{ $jobAdvice->requestedBy->name ?? ($jobAdvice->submittedBy->name ?? 'N/A') }}</td>
                            <td>
                                @if($jobAdvice->created_at)
                                    @php
                                        $dateWIB = \Carbon\Carbon::parse($jobAdvice->created_at)->timezone('Asia/Jakarta');
                                        echo $dateWIB->format('d/m/Y H:i');
                                    @endphp
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                @if($jobAdvice->expected_date)
                                    @php
                                        $dateWIB = \Carbon\Carbon::parse($jobAdvice->expected_date)->timezone('Asia/Jakarta');
                                        $month = str_pad($dateWIB->month, 3, '0', STR_PAD_LEFT);
                                        echo $dateWIB->format('d') . '/' . $month . '/' . $dateWIB->format('Y H:i');
                                    @endphp
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                @if($jobAdvice->remove_date)
                                    @php
                                        $dateWIB = \Carbon\Carbon::parse($jobAdvice->remove_date)->timezone('Asia/Jakarta');
                                        $month = str_pad($dateWIB->month, 3, '0', STR_PAD_LEFT);
                                        echo $dateWIB->format('d') . '/' . $month . '/' . $dateWIB->format('Y H:i');
                                    @endphp
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'approved' => 'background-color: #dcfce7; color: #166534;',
                                        'completed' => 'background-color: #dcfce7; color: #166534;',
                                        'rejected' => 'background-color: #fef2f2; color: #991b1b;',
                                        'cancelled' => 'background-color: #fef2f2; color: #991b1b;',
                                        'submitted' => 'background-color: #ffedd5; color: #c2410c;',
                                        'pending' => 'background-color: #ffedd5; color: #c2410c;',
                                        'draft' => 'background-color: #e5e7eb; color: #4b5563;',
                                    ];
                                    $style = $statusColors[$jobAdvice->status] ?? 'background-color: #f3f4f6; color: #374151;';
                                @endphp
                                <span style="padding: 4px 8px; font-size: 12px; border-radius: 9999px; {{ $style }}">
                                    {{ ucfirst($jobAdvice->status ?? 'N/A') }}
                                </span>
                            </td>
                            <td>
                                @if($jobAdvice->date_approval)
                                    @php
                                        $dateWIB = \Carbon\Carbon::parse($jobAdvice->date_approval)->timezone('Asia/Jakarta');
                                        $day = $dateWIB->format('d');
                                        $month = str_pad($dateWIB->month, 3, '0', STR_PAD_LEFT); // 3 digits: 010 for October
                                        $year = $dateWIB->format('Y');
                                        $time = $dateWIB->format('H:i');
                                        echo "{$day}/{$month}/{$year} {$time}";
                                    @endphp
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $jobAdvice->approvedBy->name ?? 'N/A' }}</td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full {{ $jobAdvice->with_invoicing ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $jobAdvice->with_invoicing ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td>
                                <span class="px-2 py-1 text-xs rounded-full {{ $jobAdvice->with_materials ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $jobAdvice->with_materials ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td>{{ Str::limit($jobAdvice->notes ?? '-', 50) }}</td>
                            <td>
                                @if($jobAdvice->updated_at)
                                    @php
                                        $dateWIB = \Carbon\Carbon::parse($jobAdvice->updated_at)->timezone('Asia/Jakarta');
                                        $month = str_pad($dateWIB->month, 3, '0', STR_PAD_LEFT);
                                        echo $dateWIB->format('d') . '/' . $month . '/' . $dateWIB->format('Y H:i');
                                    @endphp
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $jobAdvice->updater->name ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" class="text-center py-8 text-gray-500">
                                No job advices found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if(isset($jobAdvices) && $jobAdvices->currentPage() > 1)
                    <a href="{{ $jobAdvices->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if(isset($jobAdvices) && $jobAdvices->lastPage() > 0)
                    @php
                        $start = max(1, $jobAdvices->currentPage() - 2);
                        $end = min($jobAdvices->lastPage(), $jobAdvices->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $jobAdvices->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $jobAdvices->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $jobAdvices->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $jobAdvices->lastPage())
                            @if($end < $jobAdvices->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $jobAdvices->url($jobAdvices->lastPage()) }}" class="page-number">{{ $jobAdvices->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if(isset($jobAdvices) && $jobAdvices->currentPage() < $jobAdvices->lastPage())
                    <a href="{{ $jobAdvices->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $jobAdvices->currentPage() ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $jobAdvices->lastPage() ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Job Advice Details</h2>
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
            <div class="delete-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 6H5H21" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10 11V17" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 11V17" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <h3 class="delete-modal-title">Hide Job Advice</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this job advice? This action can be undone later.</p>
        <div class="delete-modal-buttons">
            <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn-hide" onclick="confirmDelete()">Yes, Hide</button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="error-icon-container">
            <div class="error-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="#ef4444" stroke-width="2"/>
                    <line x1="15" y1="9" x2="9" y2="15" stroke="#ef4444" stroke-width="2"/>
                    <line x1="9" y1="9" x2="15" y2="15" stroke="#ef4444" stroke-width="2"/>
                </svg>
            </div>
        </div>
        <h3 class="error-modal-title">Hmm... Something Went Wrong</h3>
        <p class="error-modal-description" id="errorMessage">We couldn't hide the job advice. Please try again.</p>
        <div class="error-modal-buttons">
            <button class="btn-error-close" onclick="closeErrorModal()">Close</button>
            <button class="btn-error-retry" onclick="retryDelete()">Try Again</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="success-icon-container">
            <div class="success-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="#10b981" stroke-width="2"/>
                    <path d="m9 12 2 2 4-4" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <h3 class="success-modal-title">All Set!</h3>
        <p class="success-modal-description" id="successMessage">The job advice has been successfully hidden.</p>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Global variables & Safety output
    const isAdmin = @json(auth()->user()->hasRole('Administrator') || auth()->user()->hasRole('Admin') || auth()->user()->data_restriction === 'none');
    console.log('Job Advice JavaScript loading... isAdmin:', isAdmin);

    let selectedIdsForRetry = [];
    let successModalTimer = null;

    // --- Global Utility & Modal Functions (Defined at top for safety) ---
    window.openModal = function(title) {
        console.log('openModal called with title:', title);
        const modalTitle = document.getElementById('modalTitle');
        const modalOverlay = document.getElementById('modalOverlay');
        if (modalTitle) modalTitle.textContent = title;
        if (modalOverlay) {
            modalOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function() {
        const modalOverlay = document.getElementById('modalOverlay');
        const modalBody = document.getElementById('modalBody');
        if (modalOverlay) modalOverlay.classList.remove('show');
        document.body.style.overflow = 'auto';
        if (modalBody) modalBody.innerHTML = '';
    };

    window.openCreateModal = function() {
        console.log('openCreateModal initiating...');
        window.openModal('Create New Job Advice');
        const modalBody = document.getElementById('modalBody');
        if (!modalBody) return;
        
        modalBody.innerHTML = `
            <form id="jobAdviceForm" onkeydown="return event.key != 'Enter';">
                <div class="modal-form-container">
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Job Advice Information</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="request_by">Request By <span class="text-danger">*</span></label>
                                <select class="form-select" id="request_by" name="request_by" required>
                                    <option value="">Select User</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="type">Job Advice Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="modal_type" name="type" required onchange="toggleRemoveDate()">
                                    <option value="">Select Type</option>
                                    <option value="install_free">Install Free</option>
                                    <option value="Install">Install</option>
                                    <option value="service">Service</option>
                                    <option value="Remove">Remove</option>
                                    <option value="Extra">Extra</option>
                                    <option value="change_rental">Change Rental</option>
                                    <option value="Complain">Complain</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: none;">
                            <input type="radio" name="source_type" value="contract" id="source_contract" checked onchange="toggleSourceType()">
                            <input type="radio" name="source_type" value="quotation" id="source_quotation" onchange="toggleSourceType()">
                        </div>
                        <div class="form-row">
                            <div class="form-group" id="contract_group">
                                <label class="form-label" for="contract_id">Reference No (Contract) <span class="text-danger">*</span></label>
                                <select class="form-select" id="contract_id" name="contract_id" disabled>
                                    <option value="">Select Marketing First</option>
                                </select>
                            </div>
                            <div class="form-group" id="quotation_group" style="display: none;">
                                <label class="form-label" for="quotation_id">Reference No (Quotation) <span class="text-danger">*</span></label>
                                <select class="form-select" id="quotation_id" name="quotation_id" disabled>
                                    <option value="">Select Marketing First</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="expected_date">Expected Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-input" id="expected_date" name="expected_date" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label" for="customer_contact_id">PIC (Person In Charge)</label>
                                <div class="d-flex">
                                    <select class="form-select" id="customer_contact_id" name="customer_contact_id" style="width: 100%;">
                                        <option value="">Select Contract/Quotation first</option>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-primary ms-2" onclick="openAddPicModal()">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <small id="pic_details" class="form-text text-muted" style="display: none;"></small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group" id="remove_date_group" style="display: none;">
                                <label class="form-label" for="remove_date">Remove Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-input" id="remove_date" name="remove_date">
                            </div>
                        </div>
                        <div class="form-row full-width">
                            <div class="form-group">
                                <label class="form-label" for="notes">Catatan Tambahan</label>
                                <textarea class="form-textarea" id="notes" name="notes" rows="3" placeholder="Masukkan catatan tambahan..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        `;
        const modalFooter = document.getElementById('modalFooter');
        if (modalFooter) {
            modalFooter.innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitForm(event)">Create Job Advice</button>
            `;
        }
        
        // loadUsers returns a Promise - store it so callers can chain
        const usersPromise = loadUsers();
        
        // Initialize Select2 for PIC in the dynamic modal
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $('#customer_contact_id').select2({
                dropdownParent: $('#modalOverlay'),
                placeholder: 'Select PIC',
                allowClear: true,
                width: '100%'
            });

            // Handle PIC selection details display
            $('#customer_contact_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const email = selectedOption.data('email');
                const phone = selectedOption.data('phone');
                
                if (email || phone) {
                    let details = [];
                    if (phone) details.push('Phone: ' + phone);
                    if (email) details.push('Email: ' + email);
                    $('#pic_details').text(details.join(' | ')).show();
                } else {
                    $('#pic_details').hide();
                }
            });
        }

        setupJobAdviceDateGuards();

        return usersPromise;
    };

// Function to format date with time (3-digit month as requested: 010 for October)
function formatDateWithThreeDigitMonth(dateInput) {
    if (!dateInput) {
        return 'N/A';
    }
    
    // Convert to Date object if string
    const date = dateInput instanceof Date ? dateInput : new Date(dateInput);
    
    // Validate date
    if (isNaN(date.getTime())) {
        return 'N/A';
    }
    
    // Get date components in WIB timezone (Asia/Jakarta = UTC+7)
    // Use toLocaleString to get WIB time, then parse components
    const options = {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    };
    
    const formatter = new Intl.DateTimeFormat('en-GB', options);
    const parts = formatter.formatToParts(date);
    
    const day = parts.find(p => p.type === 'day').value;
    const monthNum = parts.find(p => p.type === 'month').value;
    const month = monthNum.padStart(3, '0'); // 3 digits: 010 for October
    const year = parts.find(p => p.type === 'year').value;
    const hour = parts.find(p => p.type === 'hour').value;
    const minute = parts.find(p => p.type === 'minute').value;
    
    return `${day}/${month}/${year} ${hour}:${minute}`;
}

function isInstallFreeType(type) {
    return ['install_free', 'install free'].includes(String(type || '').toLowerCase());
}

function addDaysToDateValue(dateValue, days) {
    if (!dateValue) return '';
    const date = new Date(`${dateValue}T00:00:00`);
    if (Number.isNaN(date.getTime())) return '';
    date.setDate(date.getDate() + days);
    return date.toISOString().split('T')[0];
}

function setupJobAdviceDateGuards() {
    const pairs = [
        {
            expected: document.getElementById('expected_date'),
            remove: document.getElementById('remove_date'),
            type: document.getElementById('modal_type')
        },
        {
            expected: document.getElementById('edit_expected_date'),
            remove: document.getElementById('edit_remove_date'),
            type: document.getElementById('edit_type')
        }
    ];

    pairs.forEach(({ expected, remove, type }) => {
        if (!expected || !remove) return;

        const syncMinDate = () => {
            const minRemoveDate = addDaysToDateValue(expected.value, 1);
            if (minRemoveDate) {
                remove.min = minRemoveDate;
            } else {
                remove.removeAttribute('min');
            }

            if (remove.value && expected.value && remove.value <= expected.value) {
                remove.setCustomValidity('Remove Date harus lebih tinggi dari Expected Date.');
            } else {
                remove.setCustomValidity('');
            }
        };

        expected.addEventListener('change', syncMinDate);
        remove.addEventListener('change', syncMinDate);
        if (type) {
            type.addEventListener('change', syncMinDate);
        }

        syncMinDate();
    });
}

function validateJobAdviceDates(data) {
    const expectedDate = data.expected_date;
    const removeDate = data.remove_date;

    if (!expectedDate || !removeDate) {
        return true;
    }

    if (removeDate <= expectedDate) {
        alert('Remove Date harus lebih tinggi dari Expected Date. Untuk Install Free, pemasangan harus terjadi sebelum remove.');
        return false;
    }

    return true;
}

    // DOMContentLoaded wrapper for initial page listeners
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const selectAllHeader = document.getElementById('selectAllHeader');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');
                checkboxes.forEach(checkbox => { checkbox.checked = this.checked; });
                if (selectAllHeader) selectAllHeader.checked = this.checked;
            });
        }

        if (selectAllHeader) {
            selectAllHeader.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');
                checkboxes.forEach(checkbox => { checkbox.checked = this.checked; });
                if (selectAll) selectAll.checked = this.checked;
            });
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('row-checkbox')) {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                const selectAllCheckbox = document.getElementById('selectAll');
                const headerSelectAllCheckbox = document.getElementById('selectAllHeader');
                
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
    });

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one job advice to hide');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Function to apply filters
function applyFilters() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    let url = '{{ route("marketing.job-advices.index") }}?';
    
    if (startDate) url += `start_date=${startDate}&`;
    if (endDate) url += `end_date=${endDate}&`;

    window.location.href = url;
}

function clearFilters() {
    // Clear Flatpickr inputs
    const startPicker = document.querySelector("#start_date")._flatpickr;
    const endPicker = document.querySelector("#end_date")._flatpickr;
    
    if (startPicker) startPicker.clear();
    if (endPicker) endPicker.clear();
    
    // Redirect to base URL (this will trigger auto-set logic in DOMContentLoaded)
    window.location.href = '{{ route("marketing.job-advices.index") }}';
}


// Function to load quotations
function loadQuotations(marketingId = null) {
    console.log('Loading quotations for marketing:', marketingId);
    
    const quotationSelect = document.getElementById('quotation_id');
    if (!quotationSelect) return Promise.resolve();
    
    // Clear and disable if no marketing selected (unless Admin)
    if (!marketingId && !isAdmin) {
        quotationSelect.innerHTML = '<option value="">Select Marketing First</option>';
        quotationSelect.disabled = true;
        return Promise.resolve();
    }
    
    quotationSelect.disabled = false;
    quotationSelect.innerHTML = '<option value="">Loading...</option>';
    
    // Build URL with marketing filter if provided
    let url = `/api/quotations/dropdown?status=approved`;
    if (marketingId) {
        url += `&marketing_id=${marketingId}`;
    }
    
    return fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        console.log(`Quotations data:`, data);
        quotationSelect.innerHTML = '<option value="">Select Quotation</option>';
        
        const quotations = data.data || [];
        
        if (quotations.length > 0) {
            quotations.forEach(quotation => {
                const option = document.createElement('option');
                option.value = quotation.id;
                option.setAttribute('data-customer-id', quotation.customer_id || '');
                
                const customerName = quotation.customer_name || 'N/A';
                option.textContent = `${quotation.quotation_number} - ${customerName}`;
                quotationSelect.appendChild(option);
            });
        } else {
            quotationSelect.innerHTML = '<option value="">No Approved Quotations Found</option>';
        }

        // Initialize/Refresh Select2 for Quotations
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $(quotationSelect).select2({
                dropdownParent: $('#modalOverlay'),
                placeholder: 'Select Quotation',
                allowClear: true,
                width: '100%'
            }).trigger('change.select2'); // Refresh display
        }
        return data;
    })
    .catch(error => {
        console.error(`Error loading quotations:`, error);
        quotationSelect.innerHTML = '<option value="">Error Loading Quotations</option>';
        throw error;
    });
}

// MOM9: Toggle between Contract and Quotation source
window.toggleSourceType = function() {
    const sourceType = document.querySelector('input[name="source_type"]:checked')?.value;
    const contractGroup = document.getElementById('contract_group');
    const quotationGroup = document.getElementById('quotation_group');
    const contractSelect = document.getElementById('contract_id');
    const quotationSelect = document.getElementById('quotation_id');
    const typeSelect = document.getElementById('modal_type');
    
    if (sourceType === 'quotation') {
        // Show quotation, hide contract
        if (contractGroup) contractGroup.style.display = 'none';
        if (quotationGroup) quotationGroup.style.display = 'block';
        if (contractSelect) {
            contractSelect.removeAttribute('required');
            contractSelect.value = '';
        }
        if (quotationSelect) quotationSelect.setAttribute('required', 'required');
        
        // Auto-set type to install_free if not set
        if (typeSelect && !typeSelect.value) {
            typeSelect.value = 'install_free';
            toggleRemoveDate();
        }
    } else {
        // Show contract, hide quotation
        if (contractGroup) contractGroup.style.display = 'block';
        if (quotationGroup) quotationGroup.style.display = 'none';
        if (quotationSelect) {
            quotationSelect.removeAttribute('required');
            quotationSelect.value = '';
        }
        if (contractSelect) contractSelect.setAttribute('required', 'required');
    }
}

// Function to toggle Remove Date visibility
window.toggleRemoveDate = function() {
    const typeSelect = document.getElementById('modal_type');
    const removeDateGroup = document.getElementById('remove_date_group');
    const removeDateInput = document.getElementById('remove_date');
    const sourceContractRadio = document.getElementById('source_contract');
    const sourceQuotationRadio = document.getElementById('source_quotation');
    
    if (typeSelect && removeDateGroup) {
        const type = typeSelect.value;
        if (type === 'install_free' || type === 'install free') {
            removeDateGroup.style.display = 'block';
            if (removeDateInput) removeDateInput.setAttribute('required', 'required');
            
            // Auto-select Quotation source for Install Free
            if (sourceQuotationRadio) {
                sourceQuotationRadio.checked = true;
                toggleSourceType(); // Switch to quotation view
            }
        } else {
            removeDateGroup.style.display = 'none';
            if (removeDateInput) {
                removeDateInput.value = '';
                removeDateInput.removeAttribute('required');
            }
            
            // Auto-select Contract source for non-Install Free types
            if (sourceContractRadio) {
                sourceContractRadio.checked = true;
                toggleSourceType(); // Switch to contract view
            }
        }

        // MOM: Reload contract rooms if contract is selected (to reload rooms filter based on new type)
        const contractSelect = document.getElementById('contract_id');
        if (contractSelect && contractSelect.value) {
            console.log('Type changed, reloading contract rooms...');
            contractSelect.dispatchEvent(new Event('change'));
        }
    }
}

// Function to load contracts
function loadContracts(marketingId = null) {
    console.log('Loading contracts for marketing:', marketingId);
    
    const contractSelect = document.getElementById('contract_id');
    if (!contractSelect) return;
    
    // Clear and disable if no marketing selected (unless Admin)
    if (!marketingId && !isAdmin) {
        contractSelect.innerHTML = '<option value="">Select Marketing First</option>';
        contractSelect.disabled = true;
        return;
    }
    
    contractSelect.disabled = false;
    contractSelect.innerHTML = '<option value="">Loading...</option>';
    
    // Build URL with marketing filter if provided
    const baseUrl = '/api/contracts/dropdown';
    let url = `${baseUrl}?status=active&for_job_advice=1`;
    if (marketingId) {
        url += `&marketing_id=${marketingId}`;
    }
    
    return fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log(`Response status:`, response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log(`Contracts data:`, data);
        contractSelect.innerHTML = '<option value="">Select Contract</option>';
        
        // Handle different response formats
        let contracts = [];
        if (data.data && Array.isArray(data.data)) {
            contracts = data.data;
        } else if (Array.isArray(data)) {
            contracts = data;
        } else if (data.contracts && Array.isArray(data.contracts)) {
            contracts = data.contracts;
        }
        
        // Filter contracts by marketing_id and active status
        console.log('Raw contracts data:', contracts);
        console.log('Filtering for marketing_id:', marketingId);
        
        const filteredContracts = contracts.filter(function(contract) {
            const hasMarketing = contract.created_by == marketingId || contract.marketing_id == marketingId;
            const isActive = contract.status === 'active' || contract.contract_status === 'active';
            return hasMarketing && isActive;
        });
        
        if (filteredContracts.length > 0) {
            filteredContracts.forEach(contract => {
                const option = document.createElement('option');
                option.value = contract.id;
                // Store customer_id in data attribute
                option.setAttribute('data-customer-id', contract.customer_id || '');
                
                const customerName = contract.customer?.name || contract.customer_name || 'N/A';
                option.textContent = (contract.contract_number || contract.id) + ' - ' + (customerName);
                contractSelect.appendChild(option);
            });
            console.log(`Loaded ${filteredContracts.length} active contracts for marketing ${marketingId}`);
        } else {
            contractSelect.innerHTML = '<option value="">No Active Contracts Found</option>';
            console.log(`No active contracts found for marketing ${marketingId}`);
        }

        // Initialize/Refresh Select2 for Contracts
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $(contractSelect).select2({
                dropdownParent: $('#modalOverlay'),
                placeholder: 'Select Contract',
                allowClear: true,
                width: '100%'
            }).trigger('change.select2'); // Refresh display
        }
        return data;
    })
    .catch(error => {
        console.error(`Error loading contracts:`, error);
        contractSelect.innerHTML = '<option value="">Error Loading Contracts</option>';
        throw error;
    });
}

// Function to load users for Request By dropdown
function loadUsers() {
    console.log('Loading marketing users...');
    
    // Try to load from backend
    return fetch('/marketing/users/marketing-list', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('API not available, using fallback');
        }
        return response.json();
    })
    .then(data => {
        console.log('Marketing users data:', data);
        return populateMarketingUsers(data);
    })
    .catch(error => {
        console.log('Using hardcoded marketing users fallback');
        // Fallback: use current user only
        const userSelect = document.getElementById('request_by');
        if (userSelect) {
            userSelect.innerHTML = '<option value="">Select Marketing</option>';
            const currentUserName = @json(auth()->user()->name);
            userSelect.innerHTML += `<option value="{{ auth()->id() }}" selected>${currentUserName}</option>`;
            
            // Auto load contracts and quotations for current user
            return Promise.all([
                loadContracts('{{ auth()->id() }}'),
                loadQuotations('{{ auth()->id() }}')
            ]);
        }
        return Promise.resolve();
    });
}

function populateMarketingUsers(data) {
    const userSelect = document.getElementById('request_by');
    if (!userSelect) return;
    
    userSelect.innerHTML = '<option value="">Select Marketing</option>';
    
    // Handle different response formats
    let users = [];
    
    if (data.data && Array.isArray(data.data)) {
        users = data.data;
    } else if (Array.isArray(data)) {
        users = data;
    } else if (data.users && Array.isArray(data.users)) {
        users = data.users;
    }
    
    const currentUserId = '{{ auth()->id() }}';
    
    // If no users returned from API, add current user as fallback
    if (users.length === 0) {
        console.log('No marketing users from API, using current user as fallback');
        users = [{
            id: currentUserId,
            name: @json(auth()->user()->name),
            role: 'Marketing',
            is_current_user: true
        }];
    }
    
    // Display all users (backend already filters to marketing users)
    users.forEach(user => {
        const option = document.createElement('option');
        option.value = user.id;
        // Show name with role
        const roleDisplay = user.role ? ` (${user.role})` : '';
        option.textContent = user.name + roleDisplay;
        
        // Select current user by default
        if (user.is_current_user || user.id == currentUserId) {
            option.selected = true;
        }
        userSelect.appendChild(option);
    });

    // Initialize Select2 for Request By
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#request_by').select2({
            dropdownParent: $('#modalBody'), // Important for modal
            placeholder: "Select Marketing",
            allowClear: true,
            width: '100%'
        });

        // Add change event listener using jQuery for Select2 support
        $('#request_by').on('change', function() {
            const selectedUserId = $(this).val();
            console.log('Marketing user changed to:', selectedUserId);
            
            if (selectedUserId) {
                // Clear previous options
                $('#contract_id').html('<option value="">Loading...</option>');
                $('#quotation_id').html('<option value="">Loading...</option>');
                
                // Load data for selected user
                loadContracts(selectedUserId);
                loadQuotations(selectedUserId);
            } else {
                // Reset if cleared
                $('#contract_id').html('<option value="">Select Marketing First</option>').attr('disabled', true);
                $('#quotation_id').html('<option value="">Select Marketing First</option>').attr('disabled', true);
            }
        });
    } else {
        // Fallback for native select
        userSelect.addEventListener('change', function() {
            const selectedUserId = this.value;
            if (selectedUserId) {
                 loadContracts(selectedUserId);
                 loadQuotations(selectedUserId);
            }
        });
    }
    
    // Ensure current user is selected and load their contracts and quotations
    // Only triggering if we have a value selected
    if (userSelect.value && !skipAutoLoadContractsQuotations) {
        // Explicitly load contracts and quotations (return Promise so callers can chain)
        console.log('Auto-loading contracts and quotations for user:', userSelect.value);
        return Promise.all([
            loadContracts(userSelect.value),
            loadQuotations(userSelect.value)
        ]).then(() => {
            console.log('Initial contracts and quotations loaded for user:', userSelect.value);
        });
    } else if (skipAutoLoadContractsQuotations) {
        console.log('Skipping auto-load contracts/quotations (URL auto-select active)');
    }
    
    console.log(`Loaded ${users.length} marketing users`);
    return Promise.resolve();
}

// MOM6: Global variables for rooms
let skipAutoLoadContractsQuotations = false; // Flag to skip auto-load when URL auto-select is active
let contractRooms = [];
let rentalProducts = [];
let roomRowCounter = 0;

// MOM6: Load contract rooms when contract is selected
// Revised to use jQuery for better compatibility with Select2 and PIC/Notes issues
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined') {
        $(document).on('change', '#contract_id', function(e) {
    const contractId = $(this).val();
    console.log('Contract selected (jQuery):', contractId);
    
    sourceType = 'contract'; // MOM9: Set source type
    
    const roomsSection = document.getElementById('roomsSection');
    const roomsContainer = document.getElementById('roomsContainer');
    
    if (!contractId) {
        if (roomsSection) roomsSection.style.display = 'none';
        if (roomsContainer) roomsContainer.innerHTML = '';
        contractRooms = [];
        return;
    }
    
    // Get customerId from selected option data attribute
    const selectedOption = $(this).find('option:selected');
    const customerId = selectedOption.attr('data-customer-id');
    console.log('Detected customerId:', customerId);
    
    // Load PIC immediately
    if (customerId) {
        loadCustomerContacts(customerId);
    }
    
    // MOM: Pass job advice type to allow filtering logic
    const typeSelect = document.getElementById('modal_type');
    const typeValue = typeSelect ? typeSelect.value : '';
    
    // Show partial loading for rooms
    if (roomsContainer) roomsContainer.innerHTML = '<p class="text-sm text-gray-500 py-2">Loading rooms...</p>';
    if (roomsSection) roomsSection.style.display = 'block';

    Promise.all([
        fetch(`/api/contracts/${contractId}/for-job-advice?type=${encodeURIComponent(typeValue)}`).then(r => r.json()),
        fetch('/warehouse/rental-products/dropdown').then(r => r.json()).catch(() => ({ data: [] }))
    ]).then(([contractData, rentalsData]) => {
        console.log('Contract data received:', contractData);
        
        // Show Sales Notes IMMEDIATELY!
        const showSalesNotes = () => {
            const notesSales = contractData.notes_sales || contractData.data?.notes_sales || contractData.sales_notes || contractData.data?.sales_notes;
            if (notesSales && notesSales.trim() !== '') {
                setTimeout(() => {
                    Swal.fire({
                        title: '<strong>Sales Note</strong>',
                        icon: 'info',
                        html: `<div class="text-left" style="white-space: pre-line;">${notesSales}</div>`,
                        showCloseButton: true,
                        focusConfirm: false,
                        allowOutsideClick: false,
                        confirmButtonText: '<i class="fa fa-thumbs-up"></i> Acknowledge',
                        confirmButtonAriaLabel: 'Thumbs up, acknowledge!',
                        customClass: {
                            container: 'my-swal-container',
                            popup: 'my-swal-popup',
                            content: 'text-left'
                        }
                    });
                }, 100);
            }
        };
        showSalesNotes();

        // Store contract rooms
        contractRooms = contractData.contract_rooms || contractData.contractRooms || contractData.data?.contract_rooms || contractData.data?.contractRooms || [];
        
        // MOM9: Clear quotation rooms when contract is selected
        quotationRooms = [];
        
        // Store rental products
        rentalProducts = rentalsData.data || rentalsData || [];
        
        // Format contract rooms
        if (contractRooms && contractRooms.length > 0) {
            contractRooms = contractRooms.map(cr => ({
                id: cr.id,
                contract_room_id: cr.id,
                room_id: cr.room_id || cr.room?.id,
                room_name: cr.room?.room_name || cr.room_name || 'Room ' + cr.id,
                building_name: cr.room?.building?.nama_gedung || cr.room?.building?.name || 'N/A',
                rental_product_id: cr.rental_product_id,
                has_active_unit: cr.has_active_unit,
                active_sn: cr.active_sn
            }));
        }
        
        if (contractRooms.length > 0) {
            if (roomsSection) roomsSection.style.display = 'block';
            if (roomsContainer) {
                roomsContainer.innerHTML = '';
                addRoomRow();
            }
        } else {
            if (roomsSection) roomsSection.style.display = 'none';
            if (roomsContainer) roomsContainer.innerHTML = '';
            
            const contractNumber = contractData.contract_number || contractData.data?.contract_number || '';
            Swal.fire({
                icon: 'warning',
                title: 'Contract Tidak Memiliki Rooms',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Contract ${contractNumber}</strong> belum memiliki ruangan (rooms) yang terdaftar.</p>
                        ${contractData.broken_rooms > 0 ? `<p style="color: #ef4444;"><strong>⚠️ Perhatian:</strong> ${contractData.message || `Contract memiliki ${contractData.broken_rooms} ruangan yang tidak valid.`}</p>` : ''}
                    </div>
                `,
                confirmButtonText: 'OK',
            });
        }
    }).catch(error => {
        console.error('Error loading contract data:', error);
        });
    });
}
});


// MOM9: Load quotation rooms when quotation is selected (for Install Free)
let quotationRooms = [];
let sourceType = 'contract'; // Track if we're using contract or quotation

document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined') {
        $(document).on('change', '#quotation_id', function(e) {
    const quotationId = $(this).val();
    console.log('Quotation selected (jQuery):', quotationId);
    
    sourceType = 'quotation';
    
    const roomsSection = document.getElementById('roomsSection');
    const roomsContainer = document.getElementById('roomsContainer');
    
    if (!quotationId) {
        if (roomsSection) roomsSection.style.display = 'none';
        if (roomsContainer) roomsContainer.innerHTML = '';
        quotationRooms = [];
        return;
    }
    
    // Get customerId from selected option
    const selectedOption = $(this).find('option:selected');
    const customerId = selectedOption.attr('data-customer-id');
    console.log('Detected customerId (quotation):', customerId);
    
    // Load PIC immediately
    if (customerId) {
        loadCustomerContacts(customerId);
    }

    // Show partial loading
    if (roomsContainer) roomsContainer.innerHTML = '<p class="text-sm text-gray-500 py-2">Loading rooms...</p>';
    if (roomsSection) roomsSection.style.display = 'block';

    // Load quotation rooms and rental products
        Promise.all([
            fetch(`/api/quotations/${quotationId}/for-job-advice`).then(async r => {
                const payload = await r.json();
                if (!r.ok) {
                    throw new Error(payload.message || 'Error loading quotation.');
                }
                return payload;
            }),
            fetch('/warehouse/rental-products/dropdown').then(r => r.json()).catch(() => ({ data: [] }))
        ]).then(([quotationData, rentalsData]) => {
            console.log('Quotation data:', quotationData);
            console.log('Rentals data:', rentalsData);
            
            // Store quotation rooms
            quotationRooms = quotationData.quotation_rooms || quotationData.quotationRooms || quotationData.data?.quotation_rooms || [];
            
            // Also update contractRooms to empty since we're using quotation
            contractRooms = [];
            
            // Store rental products
            rentalProducts = rentalsData.data || rentalsData || [];
            
            if (quotationRooms.length > 0) {
                if (roomsSection) roomsSection.style.display = 'block';
                // Clear existing room rows and add new ones
                if (roomsContainer) {
                    roomsContainer.innerHTML = '';
                    roomRowCounter = 0;
                    addRoomRow();
                }
            } else {
                if (roomsSection) roomsSection.style.display = 'none';
                const quotationNumber = quotationData.quotation_number || '';
                Swal.fire({
                    icon: 'warning',
                    title: 'Quotation Tidak Memiliki Rooms',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>Quotation ${quotationNumber}</strong> belum memiliki ruangan (rooms) yang dipilih.</p>
                            ${quotationData.broken_rooms > 0 ? `<p style="color: #ef4444;"><strong>⚠️ Perhatian:</strong> ${quotationData.message || `Quotation memiliki ${quotationData.broken_rooms} ruangan yang tidak valid.`}</p>` : ''}
                            <br>
                            <p><strong>Langkah selanjutnya:</strong></p>
                            <ol style="margin-left: 20px;">
                                <li>Buka menu <strong>Quotation</strong></li>
                                <li>Edit quotation tersebut</li>
                                <li>Pastikan ada <strong>Rooms</strong> yang dipilih</li>
                                <li>Simpan perubahan</li>
                                <li>Kembali ke halaman ini untuk membuat Job Advice</li>
                            </ol>
                        </div>
                    `,
                    confirmButtonText: 'OK, Mengerti',
                    confirmButtonColor: '#3085d6'
                });
            }
        }).catch(error => {
            console.error('Error loading quotation data:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error Loading Quotation',
                html: `
                    <p>Terjadi kesalahan saat memuat data quotation.</p>
                    <p class="text-muted">${error.message || 'Unknown error'}</p>
                `,
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            });
        });
    });
}
});


// Update source type when radio changes
document.addEventListener('change', function(e) {
    if (e.target && e.target.name === 'source_type') {
        sourceType = e.target.value;
        console.log('Source type changed to:', sourceType);
        
        // Clear rooms when switching source
        const roomsContainer = document.getElementById('roomsContainer');
        if (roomsContainer) roomsContainer.innerHTML = '';
        contractRooms = [];
        quotationRooms = [];
        roomRowCounter = 0;
    }
});

// MOM6 & MOM9: Add room row (updated to handle both contract and quotation rooms)
function addRoomRow() {
    roomRowCounter++;
    const rowId = `room-row-${roomRowCounter}`;
    
    // Determine which rooms to use based on source type
    const isQuotationSource = sourceType === 'quotation' || quotationRooms.length > 0;
    const roomsToUse = isQuotationSource ? quotationRooms : contractRooms;
    const roomFieldName = isQuotationSource ? 'quotation_room_id' : 'contract_room_id';
    const roomLabel = isQuotationSource ? 'Quotation Room' : 'Contract Room';
    
    let roomOptions = '<option value="">Select Room</option>';
    roomsToUse.forEach(room => {
        const roomName = room.room_name || room.room?.room_name || 'Room ' + room.id;
        const buildingName = room.room?.building?.nama_gedung || room.room?.building?.name || room.building_name || '';
        const displayName = buildingName ? `${roomName} (${buildingName})` : roomName;
        roomOptions += `<option value="${room.id}" data-rental-id="${room.rental_product_id || ''}">${displayName}</option>`;
    });
    
    let rentalOptions = '<option value="">Select Rental Product</option>';
    if (isQuotationSource) {
        rentalOptions = '<option value="">Select room first</option>';
    } else {
        rentalProducts.forEach(rental => {
            rentalOptions += `<option value="${rental.id}">${rental.rental_name || rental.name}</option>`;
        });
    }
    
    const roomRow = `
        <div class="room-row" id="${rowId}" style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px; margin-bottom: 15px; background-color: white;">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">${roomLabel} *</label>
                    <select class="form-select room-select" name="rooms[${roomRowCounter}][${roomFieldName}]" required onchange="loadRoomDetails(this, ${roomRowCounter})">
                        ${roomOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Rental Product *</label>
                    <select class="form-select rental-product-select" name="rooms[${roomRowCounter}][rental_product_id]" required onchange="syncRoomRentalSource(this, ${roomRowCounter})">
                        ${rentalOptions}
                    </select>
                    <input type="hidden" name="rooms[${roomRowCounter}][quotation_rental_id]" value="">
                    <input type="hidden" name="rooms[${roomRowCounter}][quotation_detail_id]" value="">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Quantity *</label>
                    <input type="number" class="form-input" name="rooms[${roomRowCounter}][quantity]" value="1" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Is Trial?</label>
                    <select class="form-select" name="rooms[${roomRowCounter}][is_trial]">
                        <option value="0">No</option>
                        <option value="1" ${isQuotationSource ? 'selected' : ''}>Yes (Skip install if unit exists)</option>
                    </select>
                </div>
            </div>
            <div class="form-row full-width">
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-textarea" name="rooms[${roomRowCounter}][notes]" rows="2" placeholder="Optional notes for this room..."></textarea>
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeRoomRow('${rowId}')" style="margin-top: 10px;">
                <i class="fas fa-trash"></i> Remove Room
            </button>
        </div>
    `;
    
    const roomsContainer = document.getElementById('roomsContainer');
    if (roomsContainer) {
        roomsContainer.insertAdjacentHTML('beforeend', roomRow);
    }
}

// MOM6: Load room details
function loadRoomDetails(selectElement, rowId) {
    const roomId = selectElement.value;
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const rentalId = selectedOption.getAttribute('data-rental-id');
    const rentalSelect = document.querySelector(`select[name="rooms[${rowId}][rental_product_id]"]`);
    const quotationRentalInput = document.querySelector(`input[name="rooms[${rowId}][quotation_rental_id]"]`);
    const quotationDetailInput = document.querySelector(`input[name="rooms[${rowId}][quotation_detail_id]"]`);

    if (quotationRentalInput) quotationRentalInput.value = '';
    if (quotationDetailInput) quotationDetailInput.value = '';

    const isQuotationSource = sourceType === 'quotation' || quotationRooms.length > 0;
    if (isQuotationSource && rentalSelect) {
        const selectedRoom = quotationRooms.find(room => String(room.id) === String(roomId));
        const roomRentals = selectedRoom?.rentals || [];

        rentalSelect.innerHTML = '<option value="">Select Rental Product</option>';
        roomRentals.forEach(rental => {
            const option = document.createElement('option');
            option.value = rental.master_rental_id || '';
            option.textContent = rental.rental_name || 'Unknown Rental';
            option.dataset.quotationRentalId = rental.id || '';
            option.dataset.quotationDetailId = rental.quotation_detail_id || '';
            rentalSelect.appendChild(option);
        });

        if (roomRentals.length === 1) {
            rentalSelect.selectedIndex = 1;
            syncRoomRentalSource(rentalSelect, rowId);
        }

        return;
    }
    
    if (rentalId) {
        // Auto-select rental product if available
        if (rentalSelect) {
            rentalSelect.value = rentalId;
        }
    }

    // User Request: "satu ruangan bisa lebih dari 1 unit dari contract/quotation yg berbeda"
    // Validasi unit aktif dihapus agar ruangan tetap bisa dipilih.
}

function syncRoomRentalSource(selectElement, rowId) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const quotationRentalInput = document.querySelector(`input[name="rooms[${rowId}][quotation_rental_id]"]`);
    const quotationDetailInput = document.querySelector(`input[name="rooms[${rowId}][quotation_detail_id]"]`);

    if (quotationRentalInput) {
        quotationRentalInput.value = selectedOption?.dataset?.quotationRentalId || '';
    }

    if (quotationDetailInput) {
        quotationDetailInput.value = selectedOption?.dataset?.quotationDetailId || '';
    }
}

// MOM6: Remove room row
function removeRoomRow(rowId) {
    const roomRow = document.getElementById(rowId);
    if (roomRow) {
        roomRow.remove();
    }
    
    // If no rooms left, hide section
    const roomsContainer = document.getElementById('roomsContainer');
    const roomsSection = document.getElementById('roomsSection');
    if (roomsContainer && roomsContainer.children.length === 0 && roomsSection) {
        roomsSection.style.display = 'none';
    }
}

function openViewModal(id) {
    openModal('View Job Advice');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/marketing/job-advices/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-form-container">
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Basic Information</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Job Advice Number</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.job_advice_number || 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Type</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">
                                    <span class="px-2 py-1 text-xs rounded-full ${data.type === 'install' ? 'bg-blue-100 text-blue-800' : (data.type === 'remove' ? 'bg-red-100 text-red-800' : (data.type === 'service' ? 'bg-green-100 text-green-800' : (data.type === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')))}">
                                        ${data.type ? data.type.charAt(0).toUpperCase() + data.type.slice(1) : 'N/A'}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Reference Number</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.reference_number || 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Name</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.contract && data.contract.customer ? data.contract.customer.name : (data.company_name || 'N/A')}</div>
                            </div>
                        </div>
                    </div>
                    
                    ${data.rooms && data.rooms.length > 0 ? `
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Rental Rooms (${data.rooms.length})</div>
                        ${data.rooms.map((room, index) => `
                            <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 10px; background-color: #f9fafb;">
                                <div style="font-weight: 600; color: #214589; margin-bottom: 8px;">
                                    <i class="fas fa-door-open"></i> ${room.room_name || 'Room ' + (index + 1)}
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 12px; color: #6b7280;">Rental Product</label>
                                        <div style="font-size: 14px;">${room.rental_name || 'N/A'}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 12px; color: #6b7280;">Quantity</label>
                                        <div style="font-size: 14px;">${room.quantity || 1} unit(s)</div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 12px; color: #6b7280;">Status</label>
                                        <div style="font-size: 14px;">
                                            <span class="px-2 py-1 text-xs rounded-full ${room.status === 'completed' ? 'bg-green-100 text-green-800' : (room.status === 'scheduled' ? 'bg-blue-100 text-blue-800' : (room.status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'))}">
                                                ${room.status ? room.status.charAt(0).toUpperCase() + room.status.slice(1) : 'Pending'}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 12px; color: #6b7280;">Trial</label>
                                        <div style="font-size: 14px;">${room.is_trial ? 'Yes' : 'No'}</div>
                                    </div>
                                </div>
                                ${room.notes ? `
                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb;">
                                    <label class="form-label" style="font-size: 12px; color: #6b7280;">Notes</label>
                                    <div style="font-size: 13px; color: #6b7280;">${room.notes}</div>
                                </div>
                                ` : ''}
                            </div>
                        `).join('')}
                    </div>
                    ` : ''}
                    
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Schedule & Status</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Expected Date</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.expected_date ? formatDateWithThreeDigitMonth(new Date(data.expected_date)) : 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Remove Date</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.remove_date ? formatDateWithThreeDigitMonth(new Date(data.remove_date)) : 'N/A'}</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">
                                    <span class="px-2 py-1 text-xs rounded-full ${data.status === 'draft' ? 'bg-yellow-100 text-yellow-800' : (data.status === 'submitted' ? 'bg-blue-100 text-blue-800' : (data.status === 'approved' ? 'bg-green-100 text-green-800' : (data.status === 'rejected' ? 'bg-red-100 text-red-800' : (data.status === 'completed' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'))))}">
                                        ${data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'N/A'}
                                    </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Submitted By</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.submitted_by ? data.submitted_by.name : 'N/A'}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Approval Information</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Date Approval</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.date_approval ? formatDateWithThreeDigitMonth(new Date(data.date_approval)) : 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Approved By</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.approved_by ? data.approved_by.name : 'N/A'}</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">With Invoicing</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.with_invoicing ? 'Yes' : 'No'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">With Materials</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.with_materials ? 'Yes' : 'No'}</div>
                            </div>
                        </div>
                    </div>
                    
                    ${data.notes ? `
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Additional Information</div>
                        <div class="form-row full-width">
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <div class="form-textarea" style="background-color: #f9fafb; color: #374151; min-height: 80px;">${data.notes}</div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
            
            // Set modal footer - Dynamic buttons based on status
            let footerButtons = `<button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>`;
            
            // Status-based workflow buttons
            if (data.status === 'draft') {
                // Draft: Show Submit button
                footerButtons += `
                    <button type="button" class="btn btn-primary" onclick="submitJobAdvice(${id})" style="background-color: #3b82f6; border-color: #3b82f6;">
                        <i class="fas fa-paper-plane"></i> Submit for Approval
                    </button>
                `;
            } else if (data.status === 'submitted') {
                // Submitted: Show Approve & Reject buttons
                footerButtons += `
                    <button type="button" class="btn btn-success" onclick="approveJobAdvice(${id})" style="background-color: #10b981; border-color: #10b981;">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger" onclick="rejectJobAdvice(${id})" style="background-color: #ef4444; border-color: #ef4444;">
                        <i class="fas fa-times"></i> Reject
                    </button>
                `;
            }
            
            // Always show Edit button (except for approved/rejected status)
            if (data.status !== 'approved' && data.status !== 'rejected' && data.status !== 'completed') {
                footerButtons += `<button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>`;
            }
            
            document.getElementById('modalFooter').innerHTML = footerButtons;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading job advice details.</div>';
        });
}

// Submit Job Advice for Approval
function submitJobAdvice(id) {
    Swal.fire({
        title: 'Submit for Approval',
        html: `
            <p>Apakah Anda yakin ingin <strong>mengirim</strong> Job Advice ini untuk disetujui?</p>
            <p class="text-sm text-gray-600 mt-2">Setelah di-submit, Job Advice akan menunggu persetujuan.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-paper-plane"></i> Ya, Submit',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/marketing/job-advices/${id}/submit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    submitted_by: {{ auth()->id() }}
                })
            })
            .then(response => response.json().then(data => ({ status: response.status, ok: response.ok, data })))
            .then(({ status, ok, data }) => {
                if (!ok) {
                    throw new Error(data.message || `Server error: ${status}`);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message || error}`);
                throw error;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Submitted!',
                text: 'Job Advice telah dikirim untuk persetujuan.',
                confirmButtonColor: '#3085d6',
            }).then(() => {
                closeModal();
                location.reload(); // Refresh page to see updated status
            });
        }
    });
}

// Approve Job Advice
function approveJobAdvice(id) {
    Swal.fire({
        title: 'Approve Job Advice',
        html: `
            <p>Apakah Anda yakin ingin <strong>menyetujui</strong> Job Advice ini?</p>
            <p class="text-sm text-gray-600 mt-2">Setelah disetujui, Job Schedule akan otomatis dibuat.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-check"></i> Ya, Approve',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/marketing/job-advices/${id}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    approved_by: {{ auth()->id() }}
                })
            })
            .then(response => response.json().then(data => ({ status: response.status, ok: response.ok, data })))
            .then(({ status, ok, data }) => {
                if (!ok) {
                    throw new Error(data.message || `Server error: ${status}`);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message || error}`);
                throw error;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Approved!',
                text: 'Job Advice telah disetujui dan Job Schedule sudah dibuat.',
                confirmButtonColor: '#3085d6',
            }).then(() => {
                closeModal();
                location.reload(); // Refresh page to see updated status
            });
        }
    });
}

// Reject Job Advice
function rejectJobAdvice(id) {
    Swal.fire({
        title: 'Reject Job Advice',
        html: `
            <p>Apakah Anda yakin ingin <strong>menolak</strong> Job Advice ini?</p>
            <div class="mt-4">
                <label class="block text-left text-sm font-medium text-gray-700 mb-2">Alasan Penolakan:</label>
                <textarea id="rejection_reason" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="3" placeholder="Masukkan alasan penolakan..."></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-times"></i> Ya, Reject',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const reason = document.getElementById('rejection_reason').value;
            if (!reason) {
                Swal.showValidationMessage('Alasan penolakan harus diisi!');
                return false;
            }
            
            return fetch(`/marketing/job-advices/${id}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    rejection_reason: reason,
                    rejected_by: {{ auth()->id() }}
                })
            })
            .then(response => response.json().then(data => ({ status: response.status, ok: response.ok, data })))
            .then(({ status, ok, data }) => {
                if (!ok) {
                    throw new Error(data.message || `Server error: ${status}`);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message || error}`);
                throw error;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Rejected!',
                text: 'Job Advice telah ditolak.',
                confirmButtonColor: '#3085d6',
            }).then(() => {
                closeModal();
                location.reload(); // Refresh page to see updated status
            });
        }
    });
}

function openEditModal(id) {
    openModal('Edit Job Advice');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/marketing/job-advices/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <form id="jobAdviceForm" onsubmit="submitForm(event, ${id})">
                    <div class="modal-form-container">
                        <div class="modal-form-section">
                            <div class="modal-form-section-title">Basic Information</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="edit_job_advice_number">Job Advice Number</label>
                                    <input type="text" class="form-input" id="edit_job_advice_number" name="job_advice_number" value="${data.job_advice_number || ''}" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="edit_type">Type</label>
                                    <select class="form-select" id="edit_type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="install" ${data.type === 'install' ? 'selected' : ''}>Install</option>
                                        <option value="service" ${data.type === 'service' ? 'selected' : ''}>Service</option>
                                        <option value="remove" ${data.type === 'remove' ? 'selected' : ''}>Remove</option>
                                        <option value="maintenance" ${data.type === 'maintenance' ? 'selected' : ''}>Maintenance</option>
                                        <option value="change_rental" ${data.type === 'change_rental' ? 'selected' : ''}>Change Rental</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="edit_contract_id">Contract</label>
                                    <input type="text" class="form-input" id="edit_contract_id" name="contract_id" value="${data.contract_id || ''}" readonly style="background-color: #f9fafb; color: #6b7280;">
                                    <small class="text-gray-500 text-xs">Contract cannot be changed after Job Advice is created</small>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="edit_reference_number">Reference Number</label>
                                    <input type="text" class="form-input" id="edit_reference_number" name="reference_number" value="${data.reference_number || ''}">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="edit_expected_date">Expected Date</label>
                                    <input type="date" class="form-input" id="edit_expected_date" name="expected_date" value="${data.expected_date ? new Date(data.expected_date).toISOString().split('T')[0] : ''}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-form-section">
                            <div class="modal-form-section-title">Schedule & Status</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="edit_remove_date">Remove Date</label>
                                    <input type="date" class="form-input" id="edit_remove_date" name="remove_date" value="${data.remove_date ? new Date(data.remove_date).toISOString().split('T')[0] : ''}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="edit_status">Status</label>
                                    <select class="form-select" id="edit_status" name="status" required>
                                        <option value="draft" ${data.status === 'draft' ? 'selected' : ''}>Draft</option>
                                        <option value="submitted" ${data.status === 'submitted' ? 'selected' : ''}>Submitted</option>
                                        <option value="approved" ${data.status === 'approved' ? 'selected' : ''}>Approved</option>
                                        <option value="rejected" ${data.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                                        <option value="completed" ${data.status === 'completed' ? 'selected' : ''}>Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="edit_with_invoicing">With Invoicing</label>
                                    <select class="form-select" id="edit_with_invoicing" name="with_invoicing">
                                        <option value="0" ${!data.with_invoicing ? 'selected' : ''}>No</option>
                                        <option value="1" ${data.with_invoicing ? 'selected' : ''}>Yes</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="edit_with_materials">With Materials</label>
                                    <select class="form-select" id="edit_with_materials" name="with_materials">
                                        <option value="0" ${!data.with_materials ? 'selected' : ''}>No</option>
                                        <option value="1" ${data.with_materials ? 'selected' : ''}>Yes</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-form-section">
                            <div class="modal-form-section-title">Approval Information</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Date Approval</label>
                                    <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.date_approval ? formatDateWithThreeDigitMonth(new Date(data.date_approval)) : 'N/A'}</div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Approved By</label>
                                    <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.approved_by ? data.approved_by.name : 'N/A'}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-form-section">
                            <div class="modal-form-section-title">Additional Information</div>
                            <div class="form-row full-width">
                                <div class="form-group">
                                    <label class="form-label" for="edit_notes">Notes</label>
                                    <textarea class="form-textarea" id="edit_notes" name="notes" rows="3" placeholder="Enter notes...">${data.notes || ''}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            `;
    document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" form="jobAdviceForm">Update Job Advice</button>
            `;
            setupJobAdviceDateGuards();
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading job advice details.</div>';
        });
}

window.submitForm = function(event, id = null) {
    event.preventDefault();
    
    // Fix: Get form by ID since event target might be the button
    const form = document.getElementById('jobAdviceForm');
    if (!form) {
        console.error('Job Advice form not found!');
        return;
    }
    
    // Check form validity (since we removed onsubmit default validation)
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // MOM9: Handle source type (Contract or Quotation)
    const sourceType = document.querySelector('input[name="source_type"]:checked')?.value;
    if (sourceType === 'quotation') {
        // Clear contract_id if quotation is selected
        data.contract_id = '';
        // Ensure quotation_id is set
        if (!data.quotation_id) {
            alert('Please select a quotation.');
            return;
        }
        // Auto-set type to install_free if from quotation
        if (!data.type || data.type === '') {
            data.type = 'install_free';
        }
    } else {
        // Clear quotation_id if contract is selected
        data.quotation_id = '';
        // Ensure contract_id is set
        if (!data.contract_id) {
            alert('Please select a contract.');
            return;
        }
    }

    if (String(data.type || '').toLowerCase() === 'service' && sourceType !== 'contract') {
        alert('Job Advice type Service wajib dibuat dari Contract.');
        return;
    }

    if (!validateJobAdviceDates(data)) {
        return;
    }
    
    // Set default values for fields not in create modal
    if (!id) {
        data.status = 'draft'; // Always draft when created
        data.with_invoicing = false;
        data.with_materials = false;
    }
    
    // MOM6: Collect rooms data as array
    const rooms = [];
    const roomInputs = document.querySelectorAll('.room-row');
    roomInputs.forEach((roomRow, index) => {
        const contractRoomId = roomRow.querySelector('[name*="[contract_room_id]"]')?.value;
        const quotationRoomId = roomRow.querySelector('[name*="[quotation_room_id]"]')?.value;
        const quotationRentalId = roomRow.querySelector('[name*="[quotation_rental_id]"]')?.value;
        const quotationDetailId = roomRow.querySelector('[name*="[quotation_detail_id]"]')?.value;
        const rentalProductId = roomRow.querySelector('[name*="[rental_product_id]"]')?.value;
        const quantity = roomRow.querySelector('[name*="[quantity]"]')?.value;
        const isTrial = roomRow.querySelector('[name*="[is_trial]"]')?.value;
        const notes = roomRow.querySelector('[name*="[notes]"]')?.value;
        
        if ((contractRoomId || quotationRoomId) && rentalProductId) {
            rooms.push({
                contract_room_id: contractRoomId || null,
                quotation_room_id: quotationRoomId || null,
                quotation_rental_id: quotationRentalId || null,
                quotation_detail_id: quotationDetailId || null,
                rental_product_id: rentalProductId,
                quantity: quantity || 1,
                is_trial: isTrial === '1' ? true : false,
                notes: notes || ''
            });
        }
    });
    
    // Remove individual room fields and add as array
    Object.keys(data).forEach(key => {
        if (key.startsWith('rooms[')) {
            delete data[key];
        }
    });
    
    if (rooms.length > 0) {
        data.rooms = rooms;
    }
    
    const url = id ? `/marketing/job-advices/${id}` : '/marketing/job-advices';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    console.log('Submitting data:', data);
    console.log('Rooms:', rooms);
    console.log('URL:', url);
    console.log('Method:', method);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(async response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Get response text first to check if it's JSON
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            // If not JSON, it's probably an HTML error page
            console.error('Response is not JSON:', responseText);
            if (!response.ok) {
                throw new Error(`Server error (${response.status}): ${responseText.substring(0, 200)}`);
            }
            // If response is OK but not JSON, treat as success
            result = { status: 'success', message: 'Job Advice berhasil dibuat.' };
        }
        
        // Check if response is OK
        if (!response.ok) {
            // Extract error message from result
            let errorMessage = 'Terjadi kesalahan saat membuat Job Advice.';
            if (result.message) {
                errorMessage = result.message;
            } else if (result.errors) {
                // Validation errors
                const errorList = Object.entries(result.errors)
                    .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
                    .join('\n');
                errorMessage = `Validasi error:\n${errorList}`;
            }
            throw new Error(errorMessage);
        }
        
        return result;
    })
    .then(result => {
        console.log('Response result:', result);
        
        if (result.status === 'success' || result.success) {
            closeModal();
            // Show success message
            if (result.message) {
                alert(result.message);
            }
            
            // Redirect to detail page if ID is available
            if (result.data && result.data.id) {
                window.location.href = `/marketing/job-advices/${result.data.id}`;
            } else {
                location.reload();
            }
        } else {
            alert('Error: ' + (result.message || 'Something went wrong'));
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        
        // Show user-friendly error message
        let errorMessage = error.message || 'Terjadi kesalahan saat membuat Job Advice.';
        if (errorMessage.length > 200) {
            errorMessage = errorMessage.substring(0, 200) + '...';
        }
        alert('Error: ' + errorMessage);
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this job advice? This action can be undone later.'
        : `Are you sure you want to hide ${count} job advices? This action can be undone later.`;
    
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
    
    fetch('/marketing/job-advices/bulk-delete', {
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
        ? 'The job advice has been successfully hidden.'
        : `${count} job advices have been successfully hidden.`;
    
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
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t hide the job advice. Please try again.';
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

// Add CSS for loading spinner
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Global variable to store current customer ID for PIC modal
let currentCustomerIdForPic = null;

// Function to load customer contacts (vanilla JS version)
function loadCustomerContacts(customerId) {
    currentCustomerIdForPic = customerId;
    
    const picSelect = document.getElementById('customer_contact_id');
    if (!picSelect) return;

    if (!customerId) {
        picSelect.innerHTML = '<option value="">Select PIC</option>';
        return;
    }

    // Use fetch instead of jQuery ajax
    fetch('/company/customer-contacts/by-customer/' + customerId)
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            let options = '<option value="">Select PIC</option>';
            const contacts = data.data || data;
            
            if (Array.isArray(contacts)) {
                contacts.forEach(function(contact) {
                    options += '<option value="' + contact.id + '" data-email="' + (contact.email || '') + '" data-phone="' + (contact.phone || '') + '">' + contact.name + '</option>';
                });
            }
            
            console.log('PIC contacts loaded:', Array.isArray(contacts) ? contacts.length : 0, 'contacts for customer', customerId);
            
            // Destroy Select2 first, update options, then re-initialize
            if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                try { $(picSelect).select2('destroy'); } catch(e) { /* ignore if not initialized */ }
            }
            
            // Remove any stale Select2 containers to prevent duplicates
            const picParent = picSelect.parentElement;
            if (picParent) {
                picParent.querySelectorAll('.select2-container').forEach(el => el.remove());
            }
            
            picSelect.innerHTML = options;
            // Ensure the select is visible (Select2 destroy should handle this, but just in case)
            picSelect.style.display = '';
            
            // Re-initialize Select2 with fresh options
            if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                $(picSelect).select2({
                    dropdownParent: $('#modalOverlay'),
                    placeholder: 'Select PIC',
                    allowClear: true,
                    width: '100%'
                });
            }
        })
        .catch(function(error) {
            console.error('Error loading contacts:', error);
        });
}

window.openAddPicModal = function() {
    // Use global variable instead of jQuery data
    let customerId = currentCustomerIdForPic;
    
    if (!customerId) {
        // Fallback: try to get from selected quotation (checking both native and Select2)
        const quotationSelect = document.getElementById('quotation_id');
        if (quotationSelect && quotationSelect.value) {
            const selectedOption = quotationSelect.options[quotationSelect.selectedIndex] || $(quotationSelect).find(':selected')[0];
            if (selectedOption) {
                customerId = selectedOption.getAttribute('data-customer-id');
                console.log('Fallback customerId from quotation:', customerId);
            }
        }
    }
    
    if (!customerId) {
        // Fallback: try to get from selected contract
        const contractSelect = document.getElementById('contract_id');
        if (contractSelect && contractSelect.value) {
            const selectedOption = contractSelect.options[contractSelect.selectedIndex] || $(contractSelect).find(':selected')[0];
            if (selectedOption) {
                customerId = selectedOption.getAttribute('data-customer-id');
                console.log('Fallback customerId from contract:', customerId);
            }
        }
    }
        
    if (!customerId) {
        console.warn('PIC modal opened but no customer ID available yet');
        alert('Please select a Contract or Quotation first.');
        return;
    }

    // Get salutation and position options from Blade
    const salutations = @json($salutations ?? []);
    const positions = @json($positions ?? []);
    
    // Build salutation options
    let salutationOptions = '<option value="">Select Salutation</option>';
    salutations.forEach(function(s) {
        salutationOptions += '<option value="' + s + '">' + s + '</option>';
    });
    
    // Build position options
    let positionOptions = '<option value="">Select Position</option>';
    positions.forEach(function(p) {
        positionOptions += '<option value="' + p + '">' + p + '</option>';
    });

    let modalHtml = '';
    modalHtml += '<div id="addPicModal" style="z-index: 9999; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%;">';
    modalHtml += '    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); width: 100%; max-width: 500px;" onclick="event.stopPropagation();">';
    modalHtml += '        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">';
    modalHtml += '            <h5 style="margin: 0; font-size: 18px; font-weight: 600;">Add New PIC</h5>';
    modalHtml += '            <button type="button" style="border: none; background: none; font-size: 20px; cursor: pointer;" onclick="closeAddPicModal()">&times;</button>';
    modalHtml += '        </div>';
    modalHtml += '        <form id="addPicForm">';
    modalHtml += '            <input type="hidden" name="customer_id" value="' + customerId + '">';
    
    // Salutation and Name row
    modalHtml += '            <div style="display: flex; gap: 10px; margin-bottom: 15px;">';
    modalHtml += '                <div style="flex: 0 0 120px;">';
    modalHtml += '                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Salutation</label>';
    modalHtml += '                    <select class="form-control" name="salutation" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;">' + salutationOptions + '</select>';
    modalHtml += '                </div>';
    modalHtml += '                <div style="flex: 1;">';
    modalHtml += '                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Name <span style="color: red;">*</span></label>';
    modalHtml += '                    <input type="text" class="form-control" name="name" required placeholder="e.g. John Doe" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;">';
    modalHtml += '                </div>';
    modalHtml += '            </div>';
    
    // Position dropdown
    modalHtml += '            <div style="margin-bottom: 15px;">';
    modalHtml += '                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Job Position</label>';
    modalHtml += '                <select class="form-control" name="position" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;">' + positionOptions + '</select>';
    modalHtml += '            </div>';
    
    modalHtml += '            <div style="margin-bottom: 15px;">';
    modalHtml += '                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Phone</label>';
    modalHtml += '                <input type="text" class="form-control" name="phone" placeholder="e.g. 08123456789" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;">';
    modalHtml += '            </div>';
    modalHtml += '            <div style="margin-bottom: 15px;">';
    modalHtml += '                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Email</label>';
    modalHtml += '                <input type="email" class="form-control" name="email" placeholder="e.g. john@example.com" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;">';
    modalHtml += '            </div>';
    modalHtml += '            <div style="text-align: right;">';
    modalHtml += '                <button type="button" style="padding: 8px 16px; margin-right: 10px; border: 1px solid #ccc; background: #f8f8f8; border-radius: 4px; cursor: pointer;" onclick="closeAddPicModal()">Cancel</button>';
    modalHtml += '                <button type="button" style="padding: 8px 16px; border: none; background: #214589; color: white; border-radius: 4px; cursor: pointer;" onclick="submitNewPic()">Save</button>';
    modalHtml += '            </div>';
    modalHtml += '        </form>';
    modalHtml += '    </div>';
    modalHtml += '</div>';
    
    // Remove existing modal if any, append new one (vanilla JS)
    const existingModal = document.getElementById('addPicModal');
    if (existingModal) existingModal.remove();
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function closeAddPicModal() {
    const modal = document.getElementById('addPicModal');
    if (modal) modal.remove();
}

function submitNewPic() {
    const form = document.getElementById('addPicForm');
    const formData = new FormData(form);
    
    if (!formData.get('name')) {
        alert('Name is required');
        return;
    }
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Use fetch instead of jQuery ajax
    fetch('{{ route("company.customer-contacts.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) {
        if (!response.ok) {
            return response.text().then(function(text) {
                throw new Error('Server error: ' + response.status);
            });
        }
        return response.json();
    })
    .then(function(data) {
        if (data.error) {
            alert(data.error);
            return;
        }
        
        const newContact = data.data || data.contact || data;
        
        // Add to dropdown using vanilla JS
        const picSelect = document.getElementById('customer_contact_id');
        if (picSelect) {
            const option = document.createElement('option');
            option.value = newContact.id;
            option.textContent = newContact.name;
            option.setAttribute('data-email', newContact.email || '');
            option.setAttribute('data-phone', newContact.phone || '');
            option.selected = true;
            picSelect.appendChild(option);
            
            // Sync with Select2
            if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                $(picSelect).trigger('change');
            }
        }
        
        closeAddPicModal();
        
        // Try to show success notification (with fallback)
        if (typeof toastr !== 'undefined') {
            toastr.success('PIC added successfully');
        } else {
            alert('PIC added successfully');
        }
    })
    .catch(function(error) {
        console.error('Error adding PIC:', error);
        alert('Error adding PIC');
    });
}

// Flatpickr Initialization
document.addEventListener('DOMContentLoaded', function() {
    const flatpickrConfig = {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d M Y",
        allowInput: false,
    };

    const startPicker = flatpickr("#start_date", flatpickrConfig);
    const endPicker = flatpickr("#end_date", flatpickrConfig);

    // Auto-set dates if not present in URL
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('start_date') && !urlParams.has('end_date')) {
        const today = new Date();
        const next14Days = new Date();
        next14Days.setDate(today.getDate() + 14);

        startPicker.setDate(today);
        endPicker.setDate(next14Days);
        
        console.log('Auto-set filter dates:', {
            from: today.toISOString().split('T')[0],
            to: next14Days.toISOString().split('T')[0]
        });
    }

    // MOM9: Auto-open modal if quotation_id or contract_id is provided in URL
    const quotationId = urlParams.get('quotation_id');
    const contractId = urlParams.get('contract_id');
    const openModalParam = urlParams.get('open_modal');
    const typeParam = urlParams.get('type');
    
    // Check if we have session-based IDs too
    const sessionQuotationId = '{{ session("selected_quotation_id") ?? $selectedQuotationId ?? "" }}';
    const effectiveQuotationId = quotationId || sessionQuotationId;
    
    if (openModalParam === 'true' && effectiveQuotationId) {
        // Set flag to prevent race condition with populateMarketingUsers auto-loading
        skipAutoLoadContractsQuotations = true;
        
        // STEP 1: Open modal and wait for users to be loaded
        const usersPromise = openCreateModal();
        
        // STEP 2: Fetch quotation info (parallel with users loading)
        const quotationInfoPromise = fetch(`/api/quotations/dropdown?status=approved`)
            .then(r => r.json())
            .then(allData => {
                const allQuotations = allData.data || [];
                const targetQuotation = allQuotations.find(q => q.id == effectiveQuotationId);
                let targetMarketingId = null;
                if (targetQuotation) {
                    targetMarketingId = targetQuotation.marketing_id || targetQuotation.created_by;
                    console.log('Found quotation owner marketing_id:', targetMarketingId);
                }
                return targetMarketingId;
            });
        
        // STEP 3: Wait for BOTH users and quotation info to be ready
        Promise.all([usersPromise, quotationInfoPromise])
            .then(([_, targetMarketingId]) => {
                console.log('Users loaded and quotation info ready. Marketing:', targetMarketingId);
                
                // STEP 4: Set the correct marketing user (options are now populated)
                if (targetMarketingId) {
                    if (typeof $ !== 'undefined' && $.fn.select2) {
                        $('#request_by').val(String(targetMarketingId)).trigger('change.select2');
                    } else {
                        const requestBySelect = document.getElementById('request_by');
                        if (requestBySelect) requestBySelect.value = targetMarketingId;
                    }
                    console.log('Request By set to:', targetMarketingId);
                }
                
                // STEP 5: Switch to quotation source
                const sourceQuotation = document.getElementById('source_quotation');
                if (sourceQuotation) {
                    sourceQuotation.checked = true;
                    const contractGroup = document.getElementById('contract_group');
                    const quotationGroup = document.getElementById('quotation_group');
                    if (contractGroup) contractGroup.style.display = 'none';
                    if (quotationGroup) quotationGroup.style.display = 'block';
                }
                
                // STEP 6: Load quotations for the correct marketing user
                return loadQuotations(targetMarketingId).then(() => targetMarketingId);
            })
            .then((targetMarketingId) => {
                // STEP 7: Select the quotation in the dropdown
                console.log('Quotations for user loaded, selecting quotation:', effectiveQuotationId);
                
                if (typeof $ !== 'undefined' && $.fn.select2) {
                    $('#quotation_id').val(effectiveQuotationId).trigger('change');
                } else {
                    const quotationSelect = document.getElementById('quotation_id');
                    if (quotationSelect) {
                        quotationSelect.value = effectiveQuotationId;
                        quotationSelect.dispatchEvent(new Event('change'));
                    }
                }
                console.log('Auto-selected Quotation:', effectiveQuotationId);
                
                // STEP 8: Set type AFTER everything (use setTimeout to let DOM settle)
                setTimeout(() => {
                    if (typeParam) {
                        const typeSelect = document.getElementById('modal_type');
                        if (typeSelect) {
                            // Force set via selectedIndex for reliable visual update
                            for (let i = 0; i < typeSelect.options.length; i++) {
                                if (typeSelect.options[i].value === typeParam) {
                                    typeSelect.selectedIndex = i;
                                    break;
                                }
                            }
                            // Also set value as backup
                            typeSelect.value = typeParam;
                            // Trigger native change event so onchange handler fires
                            typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            console.log('Type set to:', typeParam, 'selectedIndex:', typeSelect.selectedIndex, 'display:', typeSelect.options[typeSelect.selectedIndex]?.text);
                        }
                    }
                    // Reset flag
                    skipAutoLoadContractsQuotations = false;
                }, 300);
            })
            .catch(error => {
                console.error('Error during auto-select quotation:', error);
                skipAutoLoadContractsQuotations = false;
            });
    } else if (openModalParam === 'true' && contractId) {
        // Set flag to prevent race condition
        skipAutoLoadContractsQuotations = true;
        
        // STEP 1: Open modal and wait for users to be loaded
        const usersPromise2 = openCreateModal();
        
        // STEP 2: Fetch contract info (parallel with users loading)
        const contractInfoPromise = fetch(`/api/contracts/dropdown?status=active&for_job_advice=1`)
            .then(r => r.json())
            .then(allData => {
                const allContracts = allData.data || [];
                const targetContract = allContracts.find(c => c.id == contractId);
                let targetMarketingId = null;
                if (targetContract) {
                    targetMarketingId = targetContract.marketing_id || targetContract.created_by;
                    console.log('Found contract owner marketing_id:', targetMarketingId);
                }
                return targetMarketingId;
            });
        
        // STEP 3: Wait for BOTH users and contract info
        Promise.all([usersPromise2, contractInfoPromise])
            .then(([_, targetMarketingId]) => {
                console.log('Users loaded and contract info ready. Marketing:', targetMarketingId);
                
                // STEP 4: Set the correct marketing user
                if (targetMarketingId) {
                    if (typeof $ !== 'undefined' && $.fn.select2) {
                        $('#request_by').val(String(targetMarketingId)).trigger('change.select2');
                    } else {
                        const requestBySelect = document.getElementById('request_by');
                        if (requestBySelect) requestBySelect.value = targetMarketingId;
                    }
                    console.log('Request By set to:', targetMarketingId);
                }
                
                // STEP 5: Switch to contract source
                const sourceContract = document.getElementById('source_contract');
                if (sourceContract) {
                    sourceContract.checked = true;
                    const contractGroup = document.getElementById('contract_group');
                    const quotationGroup = document.getElementById('quotation_group');
                    if (contractGroup) contractGroup.style.display = 'block';
                    if (quotationGroup) quotationGroup.style.display = 'none';
                }
                
                // STEP 6: Load contracts for the correct marketing user
                return loadContracts(targetMarketingId).then(() => targetMarketingId);
            })
            .then((targetMarketingId) => {
                // STEP 7: Select the contract
                console.log('Contracts for user loaded, selecting contract:', contractId);
                
                if (typeof $ !== 'undefined' && $.fn.select2) {
                    $('#contract_id').val(contractId).trigger('change');
                } else {
                    const contractSelect = document.getElementById('contract_id');
                    if (contractSelect) {
                        contractSelect.value = contractId;
                        contractSelect.dispatchEvent(new Event('change'));
                    }
                }
                console.log('Auto-selected Contract:', contractId);
                
                // STEP 8: Set type
                setTimeout(() => {
                    if (typeParam) {
                        const typeSelect = document.getElementById('modal_type');
                        if (typeSelect) {
                            for (let i = 0; i < typeSelect.options.length; i++) {
                                if (typeSelect.options[i].value === typeParam) {
                                    typeSelect.selectedIndex = i;
                                    break;
                                }
                            }
                            typeSelect.value = typeParam;
                            typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            console.log('Type set to:', typeParam, 'selectedIndex:', typeSelect.selectedIndex);
                        }
                    }
                    skipAutoLoadContractsQuotations = false;
                }, 300);
            })
            .catch(error => {
                console.error('Error during auto-select contract:', error);
                skipAutoLoadContractsQuotations = false;
            });
    } else {
        // Normal load (not from URL parameters)
        loadContracts();
        loadQuotations();
    }
});


</script>
@endpush
