@extends('layouts.app')

@section('title', 'Prospects')
@section('breadcrumb', 'Home / Marketing / Prospects')

@section('content')

<style>
    /* Responsive Table */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 10px 10px;
        min-height: 200px; /* Ensure minimum height */
        background-color: white; /* Ensure background is visible */
    }
    
    .responsive-table {
        min-width: 1200px;
        width: 100%;
        border-collapse: collapse;
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
    
    .responsive-table tbody tr {
        cursor: pointer;
        background-color: white; /* Ensure row background is visible */
    }
    
    /* Ensure table content is visible */
    .responsive-table tbody tr td {
        background-color: white;
        color: #333;
    }
    
    .responsive-table tbody tr:hover td {
        background-color: #eff6ff;
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
        display: flex !important;
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
    
    /* Mobile Modal Adjustments */
    @media (max-width: 768px) {
        .modal-container {
            width: 95vw;
            max-height: 95vh;
        }
        
        .modal-header {
            padding: 15px;
        }
        
        .modal-body {
            padding: 15px;
            max-height: calc(95vh - 120px);
        }
        
        .modal-footer {
            padding: 15px;
            flex-direction: column;
        }
        
        .modal-footer .btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    /* Delete Modal Styles */
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
    
    /* Error Modal Styles */
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
    
    /* Success Modal Styles */
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
        margin: 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Connection Error Modal Styles */
    .connection-error-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 5000;
        align-items: center;
        justify-content: center;
    }
    
    .connection-error-modal-overlay.show {
        display: flex;
    }
    
    .connection-error-modal-container {
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
    
    .connection-error-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }
    
    .connection-error-icon {
        width: 80px;
        height: 80px;
    }
    
    .connection-error-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }
    
    .connection-error-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .connection-error-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }
    
    .btn-connection-close {
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
    
    .btn-connection-close:hover {
        background-color: #f8fafc;
        border-color: #1e3a8a;
        color: #1e3a8a;
    }
    
    .btn-connection-retry {
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
    
    .btn-connection-retry:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
    }

    /* Update Error Modal Styles */
    .update-error-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 6000;
        align-items: center;
        justify-content: center;
    }
    
    .update-error-modal-overlay.show {
        display: flex;
    }
    
    .update-error-modal-container {
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
    
    .update-error-icon-container {
        margin-bottom: 24px;
        display: flex;
        justify-content: center;
    }
    
    .update-error-icon {
        width: 80px;
        height: 80px;
    }
    
    .update-error-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e40af;
        margin: 0 0 16px 0;
        line-height: 1.2;
    }
    
    .update-error-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .update-error-modal-buttons {
        display: flex;
        justify-content: center;
        gap: 16px;
    }
    
    .btn-update-error-close {
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
    
    .btn-update-error-close:hover {
        background-color: #f8fafc;
        border-color: #1e3a8a;
        color: #1e3a8a;
    }
    
    .btn-update-error-retry {
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
    
    .btn-update-error-retry:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
    }

    /* Mobile Delete Modal Adjustments */
    @media (max-width: 768px) {
        .delete-modal-container,
        .error-modal-container,
        .success-modal-container,
        .connection-error-modal-container,
        .update-error-modal-container {
            width: 95vw;
            margin: 20px;
        }
        
        .delete-modal-content,
        .error-modal-content,
        .success-modal-container,
        .connection-error-modal-container,
        .update-error-modal-container {
            padding: 30px 20px 20px;
        }
        
        .delete-icon,
        .error-icon,
        .success-icon,
        .connection-error-icon,
        .update-error-icon {
            width: 60px;
            height: 60px;
        }
        
        .delete-modal-title,
        .error-modal-title,
        .success-modal-title,
        .connection-error-modal-title,
        .update-error-modal-title {
            font-size: 20px;
        }
        
        .delete-modal-description,
        .error-modal-description,
        .success-modal-description,
        .connection-error-modal-description,
        .update-error-modal-description {
            font-size: 14px;
        }
        
        .delete-modal-buttons,
        .error-modal-buttons,
        .connection-error-modal-buttons,
        .update-error-modal-buttons {
            flex-direction: column;
            gap: 12px;
        }
        
        .btn-cancel,
        .btn-hide,
        .btn-error-close,
        .btn-error-retry,
        .btn-connection-close,
        .btn-connection-retry,
        .btn-update-error-close,
        .btn-update-error-retry {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Prospects Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Prospects</h1>
            </div>
            
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Add New Prospect</span>
            </button>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white controls-row">
            <div class="flex flex-row justify-start items-center w-full controls-left">
                <div class="flex flex-row justify-start items-center w-auto">
                    <div class="flex flex-row items-center">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        <label for="selectAll" class="ml-2 text-sm text-[#3d3d3d] cursor-pointer">Select all</label>
                    </div>
                </div>
                
                <button class="btn btn-secondary ml-4" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
                </button>
            </div>
            
        </div>
        
        <!-- Table Container -->
        <div class="w-full bg-white rounded-b-[10px] table-container">
            <table class="responsive-table" id="prospectsTable">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th class="w-[50px]" data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th class="w-[150px]" data-column="handled_by.name" data-relation="handledBy">Handled By</th>
                        <th class="w-[180px]" data-column="latest_follow_up_date" data-type="date">Latest Follow-Up</th>
                        <th class="w-[150px]" data-column="company_name">Company Name</th>
                        <th class="w-[200px]" data-column="address">Company Address</th>
                        <th class="w-[120px]" data-column="pic_name">Contact Name</th>
                        <th class="w-[130px]" data-column="contact_phone">Contact Number</th>
                        <th class="w-[180px]" data-column="contact_email">Contact Email</th>
                        <th class="w-[250px]" data-column="notes">Notes or Follow-up Details</th>
                        <th class="w-[150px]" data-column="updated_at" data-type="date">Latest Update</th>
                        <th class="w-[150px]" data-column="updater.name" data-relation="updater">Updated by</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($prospects as $prospect)
                    <tr onclick="openViewModal({{ $prospect->id }})" data-id="{{ $prospect->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $prospect->id }}">
                        </td>
                        <td>{{ $prospect->assignedTo->name ?? 'N/A' }}</td>
                        <td>
                            @if($prospect->follow_up_date)
                                {{ \Carbon\Carbon::parse($prospect->follow_up_date)->format('d F Y') }}<br>
                                at {{ \Carbon\Carbon::parse($prospect->follow_up_date)->format('H.i') }} WIB
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $prospect->company_name }}</td>
                        <td>{{ $prospect->company_address }}</td>
                        <td>{{ $prospect->contact_person ?? 'N/A' }}</td>
                        <td>{{ $prospect->contact_phone }}</td>
                        <td>{{ $prospect->contact_email }}</td>
                        <td>{{ $prospect->activity_notes ?? 'N/A' }}</td>
                        <td>
                            @if($prospect->updated_at)
                                {{ \Carbon\Carbon::parse($prospect->updated_at)->format('d F Y') }}<br>
                                at {{ \Carbon\Carbon::parse($prospect->updated_at)->format('H.i') }} WIB
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $prospect->assignedTo->name ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No prospects data found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if(isset($pagination) && $pagination['current_page'] > 1)
                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] - 1]) }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if(isset($pagination) && $pagination['last_page'] > 0)
                    @php
                        $start = max(1, $pagination['current_page'] - 2);
                        $end = min($pagination['last_page'], $pagination['current_page'] + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $pagination['current_page'])
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $pagination['last_page'])
                            @if($end < $pagination['last_page'] - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['last_page']]) }}" class="page-number">{{ $pagination['last_page'] }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if(isset($pagination) && $pagination['current_page'] < $pagination['last_page'])
                    <a href="{{ request()->fullUrlWithQuery(['page' => $pagination['current_page'] + 1]) }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $pagination['current_page'] ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $pagination['last_page'] ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Prospect Details</h2>
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
        <div class="delete-modal-content">
            <!-- Trash Icon -->
            <div class="delete-icon-container">
                <svg class="delete-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="trashGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1E40AF;stop-opacity:1" />
                        </linearGradient>
                        <filter id="shadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <!-- Trash Can Body -->
                    <rect x="25" y="35" width="50" height="45" rx="3" fill="url(#trashGradient)" filter="url(#shadow)"/>
                    <!-- Trash Can Lid -->
                    <rect x="20" y="30" width="60" height="8" rx="4" fill="url(#trashGradient)" filter="url(#shadow)"/>
                    <!-- Lid Handle -->
                    <rect x="45" y="25" width="10" height="8" rx="2" fill="url(#trashGradient)" filter="url(#shadow)"/>
                    <!-- Lid Slightly Open -->
                    <rect x="20" y="32" width="60" height="2" rx="1" fill="#1E40AF" opacity="0.3"/>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 id="deleteModalTitle" class="delete-modal-title">Hide 0 Records?</h2>
            
            <!-- Description -->
            <p class="delete-modal-description">
                These records won't show up on this page anymore, but don't worry—they'll stay safe in the database.
            </p>
            
            <!-- Buttons -->
            <div class="delete-modal-buttons">
                <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-hide" onclick="confirmDelete()">Yes, Hide</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="error-modal-content">
            <!-- Error Icon -->
            <div class="error-icon-container">
                <svg class="error-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="errorTrashGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1E40AF;stop-opacity:1" />
                        </linearGradient>
                        <filter id="errorShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <!-- Trash Can Body -->
                    <rect x="25" y="35" width="50" height="45" rx="3" fill="url(#errorTrashGradient)" filter="url(#errorShadow)"/>
                    <!-- Trash Can Lid -->
                    <rect x="20" y="30" width="60" height="8" rx="4" fill="url(#errorTrashGradient)" filter="url(#errorShadow)"/>
                    <!-- Lid Handle -->
                    <rect x="45" y="25" width="10" height="8" rx="2" fill="url(#errorTrashGradient)" filter="url(#errorShadow)"/>
                    <!-- Lid Slightly Open -->
                    <rect x="20" y="32" width="60" height="2" rx="1" fill="#1E40AF" opacity="0.3"/>
                    <!-- Error X Circle -->
                    <circle cx="75" cy="65" r="12" fill="#EF4444" filter="url(#errorShadow)"/>
                    <text x="75" y="70" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="16" font-weight="bold">×</text>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 class="error-modal-title">Hmm... Something Went Wrong</h2>
            
            <!-- Description -->
            <p class="error-modal-description">
                We couldn't hide the records just now, but your data's still safe. Give it another shot later.
            </p>
            
            <!-- Buttons -->
            <div class="error-modal-buttons">
                <button class="btn btn-error-close" onclick="closeErrorModal()">Close</button>
                <button class="btn btn-error-retry" onclick="retryDelete()">Try Again</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container" onclick="event.stopPropagation()">
        <div class="success-modal-content">
            <!-- Success Icon -->
            <div class="success-icon-container">
                <svg class="success-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="successGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#10B981;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#059669;stop-opacity:1" />
                        </linearGradient>
                        <filter id="successShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <!-- Success Checkmark Circle -->
                    <circle cx="50" cy="50" r="40" fill="url(#successGradient)" filter="url(#successShadow)"/>
                    <path d="M35 50 L45 60 L65 40" stroke="white" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 class="success-modal-title">Prospect Added!</h2>
            
            <!-- Description -->
            <p id="successModalDescription" class="success-modal-description">
                Your prospect is now saved and ready to track.<br>
                All set and safely stored in your records.
            </p>
        </div>
    </div>
</div>

<!-- Connection Error Modal -->
<div id="connectionErrorModalOverlay" class="connection-error-modal-overlay" onclick="closeConnectionErrorModal()">
    <div class="connection-error-modal-container" onclick="event.stopPropagation()">
        <div class="connection-error-modal-content">
            <!-- Error Icon -->
            <div class="connection-error-icon-container">
                <svg class="connection-error-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="connectionErrorGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#EF4444;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#DC2626;stop-opacity:1" />
                        </linearGradient>
                        <filter id="connectionErrorShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <!-- Error X Circle -->
                    <circle cx="50" cy="50" r="40" fill="url(#connectionErrorGradient)" filter="url(#connectionErrorShadow)"/>
                    <path d="M35 35 L65 65 M65 35 L35 65" stroke="white" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 class="connection-error-modal-title">Whoops! Lost connection.</h2>
            
            <!-- Description -->
            <p class="connection-error-modal-description">
                Can't save your prospect right now.<br>
                Reconnect and give it another shot.
            </p>
            
            <!-- Buttons -->
            <div class="connection-error-modal-buttons">
                <button class="btn btn-connection-close" onclick="closeConnectionErrorModal()">Close</button>
                <button class="btn btn-connection-retry" onclick="retryLastAction()">Try Again</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Error Modal -->
<div id="updateErrorModalOverlay" class="update-error-modal-overlay" onclick="closeUpdateErrorModal()">
    <div class="update-error-modal-container" onclick="event.stopPropagation()">
        <div class="update-error-modal-content">
            <!-- Error Icon -->
            <div class="update-error-icon-container">
                <svg class="update-error-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="updateErrorGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#EF4444;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#DC2626;stop-opacity:1" />
                        </linearGradient>
                        <filter id="updateErrorShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <!-- Error X Circle -->
                    <circle cx="50" cy="50" r="40" fill="url(#updateErrorGradient)" filter="url(#updateErrorShadow)"/>
                    <path d="M35 35 L65 65 M65 35 L35 65" stroke="white" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 class="update-error-modal-title">Hmm... didn't work.</h2>
            
            <!-- Description -->
            <p class="update-error-modal-description">
                We couldn't save your edits right now.<br>
                Please check again and try once more soon.
            </p>
            
            <!-- Buttons -->
            <div class="update-error-modal-buttons">
                <button class="btn btn-update-error-close" onclick="closeUpdateErrorModal()">Close</button>
                <button class="btn btn-update-error-retry" onclick="retryLastAction()">Try Again</button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to format date with 3-digit month
function formatDateWithThreeDigitMonth(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(3, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Global functions that need to be accessible from HTML onclick handlers
function openViewModal(id) {
    console.log('Global openViewModal called with ID:', id);
    // Load data via AJAX
    fetch(`/marketing/prospects/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Prospect Details';
            document.getElementById('modalBody').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div class="detail-item">
                            <label class="form-label">Company Name</label>
                            <p class="detail-value">${data.company_name || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Contact Number</label>
                            <p class="detail-value">${data.contact_phone || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Company Address</label>
                            <p class="detail-value">${data.company_address || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Contact Email</label>
                            <p class="detail-value">${data.contact_email || 'N/A'}</p>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="space-y-4">
                        <div class="detail-item">
                            <label class="form-label">Contact Person</label>
                            <p class="detail-value">${data.contact_person || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Follow-up Date</label>
                            <p class="detail-value">${data.follow_up_date ? formatDateWithThreeDigitMonth(new Date(data.follow_up_date)) : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Status</label>
                            <p class="detail-value">${data.status || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Assigned To</label>
                            <p class="detail-value">${data.assigned_to_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Full Width Notes -->
                <div class="mt-6">
                    <div class="detail-item">
                        <label class="form-label">Activity Notes</label>
                        <p class="detail-value">${data.activity_notes || 'N/A'}</p>
                    </div>
                </div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Close</button>
                    <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                </div>
            `;
            openModal();
        })
        .catch(error => {
            console.error('Error loading prospect data:', error);
            alert('Error loading prospect data');
        });
}

function openEditModal(id) {
    // Load data via AJAX
    fetch(`/marketing/prospects/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Prospect';
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update your prospect company details and make sure nothing gets missed.</p>
                <form id="editForm">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Latest Follow-Up *</label>
                                <input type="date" name="follow_up_date" class="form-input" placeholder="dd/mmm/yyyy" value="${data.follow_up_date || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Name *</label>
                                <input type="text" name="company_name" class="form-input" placeholder="Enter the company's official name" value="${data.company_name || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Address</label>
                                <textarea name="company_address" class="form-input form-textarea" placeholder="Provide the company's address">${data.company_address || ''}</textarea>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Contact Name *</label>
                                <input type="text" name="contact_person" class="form-input" placeholder="Full name of the contact person" value="${data.contact_person || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Number *</label>
                                <input type="tel" name="contact_phone" class="form-input" placeholder="Phone Number of the contact person" value="${data.contact_phone || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Email</label>
                                <input type="email" name="contact_email" class="form-input" placeholder="Email of the contact person" value="${data.contact_email || ''}">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Full Width Notes Field -->
                    <div class="form-group">
                        <label class="form-label">Notes or Follow-up Details *</label>
                        <textarea name="activity_notes" class="form-input form-textarea" placeholder="Notes or details about the follow-up activity" required>${data.activity_notes || ''}</textarea>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Update Prospect</button>
                </div>
            `;
            openModal();
        })
        .catch(error => {
            console.error('Error loading prospect data:', error);
            alert('Error loading prospect data');
        });
}

function openModal() {
    const modalOverlay = document.getElementById('modalOverlay');
    if (modalOverlay) {
        modalOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    const modalOverlay = document.getElementById('modalOverlay');
    if (modalOverlay) {
        modalOverlay.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add New Prospect';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Let's add your new prospect company details and make sure nothing gets missed.</p>
        <form id="createForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Latest Follow-Up *</label>
                        <input type="date" name="follow_up_date" class="form-input" placeholder="dd/mmm/yyyy" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" class="form-input" placeholder="Enter the company's official name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Address</label>
                        <textarea name="company_address" class="form-input form-textarea" placeholder="Provide the company's address"></textarea>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Contact Name *</label>
                        <input type="text" name="contact_person" class="form-input" placeholder="Full name of the contact person" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Number *</label>
                        <input type="tel" name="contact_phone" class="form-input" placeholder="Phone Number of the contact person" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="contact_email" class="form-input" placeholder="Email of the contact person">
                    </div>
                </div>
            </div>
            
            <!-- Full Width Notes Field -->
            <div class="form-group">
                <label class="form-label">Notes or Follow-up Details *</label>
                <textarea name="activity_notes" class="form-input form-textarea" placeholder="Notes or details about the follow-up activity" required></textarea>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Create Prospect</button>
        </div>
    `;
    openModal();
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    // Debug: Log form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }
    
    // Store the action for retry functionality
    lastAction = submitCreateForm;
    
    fetch('/marketing/prospects', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            // Try to get error details
            return response.json().then(errorData => {
                console.log('Error response:', errorData);
                throw new Error(JSON.stringify(errorData));
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Success response:', data);
        if (data.success) {
            closeModal();
            showSuccessModal();
        } else {
            closeModal();
            showUpdateErrorModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Check if it's a network error
        if (error.name === 'TypeError' || error.message.includes('fetch') || error.message.includes('Network')) {
            showConnectionErrorModal();
        } else {
            // Show validation errors if available
            try {
                const errorData = JSON.parse(error.message);
                if (errorData.errors) {
                    console.log('Validation errors:', errorData.errors);
                    alert('Validation errors: ' + JSON.stringify(errorData.errors));
                } else {
                    alert('Error creating prospect: ' + errorData.message);
                }
            } catch (e) {
                alert('Error creating prospect: ' + error.message);
            }
        }
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    // Add method override for PUT request
    formData.append('_method', 'PUT');
    
    // Store the action for retry functionality
    lastAction = submitEditForm;
    
    fetch(`/marketing/prospects/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeModal();
            showSuccessModal('update');
        } else {
            closeModal();
            showUpdateErrorModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Check if it's a network error
        if (error.name === 'TypeError' || error.message.includes('fetch') || error.message.includes('Network')) {
            showConnectionErrorModal();
        } else {
            alert('Error updating prospect');
        }
    });
}

function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one item to delete');
        return;
    }
    
    // Update modal title with count
    const count = checkboxes.length;
    document.getElementById('deleteModalTitle').textContent = `Hide ${count} Record${count > 1 ? 's' : ''}?`;
    
    // Show delete modal
    openDeleteModal();
}

function openDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function showSuccessModal(type = 'create') {
    const successModal = document.getElementById('successModalOverlay');
    const titleElement = successModal.querySelector('.success-modal-title');
    const descriptionElement = successModal.querySelector('#successModalDescription');
    
    if (type === 'create') {
        titleElement.textContent = 'Prospect Added!';
        descriptionElement.innerHTML = 'Your prospect is now saved and ready to track.<br>All set and safely stored in your records.';
    } else if (type === 'update') {
        titleElement.textContent = 'Prospect Updated!';
        descriptionElement.innerHTML = 'Your prospect details are now updated successfully.<br>Everything\'s saved and ready to track.';
    }
    
    successModal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Auto close after 3 seconds
    setTimeout(() => {
        closeSuccessModal();
        location.reload(); // Reload to show the updated prospect
    }, 3000);
}

function closeSuccessModal() {
    document.getElementById('successModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Connection Error Modal Functions
let lastAction = null; // Store the last action for retry functionality

function showConnectionErrorModal() {
    const connectionErrorModal = document.getElementById('connectionErrorModalOverlay');
    connectionErrorModal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeConnectionErrorModal() {
    document.getElementById('connectionErrorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retryLastAction() {
    closeConnectionErrorModal();
    closeUpdateErrorModal();
    if (lastAction) {
        lastAction();
    }
}

// Update Error Modal Functions
function showUpdateErrorModal() {
    const updateErrorModal = document.getElementById('updateErrorModalOverlay');
    updateErrorModal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeUpdateErrorModal() {
    document.getElementById('updateErrorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retryDelete() {
    closeErrorModal();
    confirmDelete();
}

function confirmDelete() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
    
    if (selectedIds.length === 0) {
        alert('No items selected');
        return;
    }
    
    // Store the action for retry functionality
    lastAction = confirmDelete;
    
    // Send delete request
    const deleteData = new FormData();
    deleteData.append('ids', JSON.stringify(selectedIds));
    
    fetch('/marketing/prospects/bulk-delete', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: deleteData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeDeleteModal();
            location.reload();
        } else {
            alert('Error deleting prospects: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Check if it's a network error
        if (error.name === 'TypeError' || error.message.includes('fetch') || error.message.includes('Network')) {
            closeDeleteModal();
            showConnectionErrorModal();
        } else {
            alert('Error deleting prospects');
        }
    });
}

// Initialize prospects functionality
function initializeProspects() {
    // Initialize prospects functionality
}

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeProspects();
    
    // Select All functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    const headerSelectAllCheckbox = document.getElementById('headerSelectAll');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    if (headerSelectAllCheckbox) {
        headerSelectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = this.checked;
            }
        });
    }
    
    // Individual checkbox functionality
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            const headerSelectAllCheckbox = document.getElementById('headerSelectAll');
            
            if (checkboxes.length > 0 && selectAllCheckbox && headerSelectAllCheckbox) {
                const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
                const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
                
                selectAllCheckbox.checked = allChecked;
                headerSelectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = anyChecked && !allChecked;
                headerSelectAllCheckbox.indeterminate = anyChecked && !allChecked;
            }
        }
    });
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeDeleteModal();
            closeErrorModal();
            closeSuccessModal();
            closeConnectionErrorModal();
            closeUpdateErrorModal();
        }
    });
}); // End of DOMContentLoaded event listener

// Add missing navigateTo function for sidebar navigation
function navigateTo(url) {
    window.location.href = url;
}
</script>
@endsection
