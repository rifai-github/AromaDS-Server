@extends('layouts.app')

@section('title', 'My Activity')
@section('breadcrumb', 'Home / Marketing / My Activity')

@section('content')
<style>
    /* Global overflow control */
    html, body {
        overflow-x: hidden;
        max-width: 100vw;
    }
    
    /* Ensure all elements use border-box */
    *, *::before, *::after {
        box-sizing: border-box;
    }
    
    /* Responsive Table */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 10px 10px;
        background: white;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        position: relative;
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
    }
    
    /* Scroll indicator */
    .table-container::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 20px;
        background: linear-gradient(to left, rgba(255,255,255,0.8), transparent);
        pointer-events: none;
        z-index: 5;
        opacity: var(--scroll-indicator-opacity, 0);
        transition: opacity 0.3s ease;
    }
    
    /* Show scroll indicator when content is scrollable */
    .table-container:hover::after {
        opacity: var(--scroll-indicator-opacity, 0);
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
    
    .responsive-table {
        min-width: 2000px;
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 0;
        padding: 0;
    }
    
    .responsive-table th,
    .responsive-table td {
        padding: 12px 10px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
        font-size: 14px;
        line-height: 1.4;
        vertical-align: top;
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
    }
    
    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }
    
    .responsive-table tbody tr {
        cursor: pointer;
    }
    
    /* Column widths - Improved for better readability */
    .responsive-table th:nth-child(1),
    .responsive-table td:nth-child(1) {
        width: 60px;
        min-width: 60px;
    }
    
    .responsive-table th:nth-child(2),
    .responsive-table td:nth-child(2) {
        width: 140px;
        min-width: 140px;
    }
    
    .responsive-table th:nth-child(3),
    .responsive-table td:nth-child(3) {
        width: 120px;
        min-width: 120px;
    }
    
    .responsive-table th:nth-child(4),
    .responsive-table td:nth-child(4) {
        width: 100px;
        min-width: 100px;
    }
    
    .responsive-table th:nth-child(5),
    .responsive-table td:nth-child(5) {
        width: 120px;
        min-width: 120px;
    }
    
    .responsive-table th:nth-child(6),
    .responsive-table td:nth-child(6) {
        width: 100px;
        min-width: 100px;
    }
    
    .responsive-table th:nth-child(7),
    .responsive-table td:nth-child(7) {
        width: 100px;
        min-width: 100px;
    }
    
    .responsive-table th:nth-child(8),
    .responsive-table td:nth-child(8) {
        width: 180px;
        min-width: 180px;
    }
    
    .responsive-table th:nth-child(9),
    .responsive-table td:nth-child(9) {
        width: 250px;
        min-width: 250px;
    }
    
    .responsive-table th:nth-child(10),
    .responsive-table td:nth-child(10) {
        width: 150px;
        min-width: 150px;
    }
    
    .responsive-table th:nth-child(11),
    .responsive-table td:nth-child(11) {
        width: 120px;
        min-width: 120px;
    }
    
    .responsive-table th:nth-child(12),
    .responsive-table td:nth-child(12) {
        width: 200px;
        min-width: 200px;
    }
    
    .responsive-table th:nth-child(13),
    .responsive-table td:nth-child(13) {
        width: 100px;
        min-width: 100px;
    }
    
    .responsive-table th:nth-child(14),
    .responsive-table td:nth-child(14) {
        width: 150px;
        min-width: 150px;
    }
    
    .responsive-table th:nth-child(15),
    .responsive-table td:nth-child(15) {
        width: 120px;
        min-width: 120px;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .responsive-table th,
        .responsive-table td {
            padding: 8px 6px;
            font-size: 12px;
        }
        
        .responsive-table {
            min-width: 2000px;
        }
        
        /* Ensure parent containers don't overflow */
        .flex.flex-col.w-full.min-h-screen {
            overflow-x: hidden;
            max-width: 100vw;
        }
        
        .flex.flex-col.justify-center.items-start.w-full.max-w-\[96\%\].mx-auto {
            max-width: 96vw;
            overflow-x: hidden;
        }
        
        .table-container {
            max-width: 100%;
            overflow-x: auto;
        }
        
        /* Ensure all parent divs don't overflow */
        div {
            max-width: 100%;
            box-sizing: border-box;
        }
        
        /* Flex container fixes */
        .flex {
            max-width: 100%;
            min-width: 0;
        }
        
        .flex-row {
            max-width: 100%;
            min-width: 0;
        }
        
        .flex-col {
            max-width: 100%;
            min-width: 0;
        }
        
        /* Ensure all flex items can shrink */
        .flex > * {
            min-width: 0;
            max-width: 100%;
        }
        
        /* Specific fixes for common overflow issues */
        .bg-white {
            max-width: 100%;
            overflow-x: hidden;
        }
        
        .controls-row {
            max-width: 100%;
            overflow-x: hidden;
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
        }
        
        /* Header section fixes */
        .flex.flex-row.justify-between.items-center.w-full.bg-white.rounded-t-\[10px\].p-4 {
            max-width: 100%;
            overflow-x: hidden;
            flex-wrap: wrap;
        }
        
        /* Button container fixes */
        .flex.flex-row.justify-end.items-center.w-auto {
            max-width: 100%;
            flex-shrink: 0;
        }
        
        /* Button and text fixes */
        .btn {
            max-width: 100%;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        h1, h2, h3, p, span {
            max-width: 100%;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        /* Ensure text doesn't cause overflow */
        .text-xl, .text-lg, .text-sm {
            max-width: 100%;
            word-wrap: break-word;
        }
        
        .controls-left {
            justify-content: space-between;
        }
        
        .pagination-controls {
            justify-content: center;
            flex-wrap: nowrap;
            gap: 5px;
            max-width: 100%;
            overflow-x: auto;
        }
        
        .page-dropdown-container {
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Hide scroll indicator on mobile */
        .table-container::after {
            display: none;
        }
        
        /* Adjust column widths for mobile */
        .responsive-table th:nth-child(1),
        .responsive-table td:nth-child(1) {
            width: 50px;
            min-width: 50px;
        }
        
        .responsive-table th:nth-child(2),
        .responsive-table td:nth-child(2) {
            width: 120px;
            min-width: 120px;
        }
        
        .responsive-table th:nth-child(3),
        .responsive-table td:nth-child(3) {
            width: 100px;
            min-width: 100px;
        }
        
        .responsive-table th:nth-child(4),
        .responsive-table td:nth-child(4) {
            width: 80px;
            min-width: 80px;
        }
        
        .responsive-table th:nth-child(5),
        .responsive-table td:nth-child(5) {
            width: 100px;
            min-width: 100px;
        }
        
        .responsive-table th:nth-child(6),
        .responsive-table td:nth-child(6) {
            width: 80px;
            min-width: 80px;
        }
        
        .responsive-table th:nth-child(7),
        .responsive-table td:nth-child(7) {
            width: 80px;
            min-width: 80px;
        }
        
        .responsive-table th:nth-child(8),
        .responsive-table td:nth-child(8) {
            width: 150px;
            min-width: 150px;
        }
        
        .responsive-table th:nth-child(9),
        .responsive-table td:nth-child(9) {
            width: 200px;
            min-width: 200px;
        }
        
        .responsive-table th:nth-child(10),
        .responsive-table td:nth-child(10) {
            width: 120px;
            min-width: 120px;
        }
        
        .responsive-table th:nth-child(11),
        .responsive-table td:nth-child(11) {
            width: 100px;
            min-width: 100px;
        }
        
        .responsive-table th:nth-child(12),
        .responsive-table td:nth-child(12) {
            width: 150px;
            min-width: 150px;
        }
        
        .responsive-table th:nth-child(13),
        .responsive-table td:nth-child(13) {
            width: 80px;
            min-width: 80px;
        }
        
        .responsive-table th:nth-child(14),
        .responsive-table td:nth-child(14) {
            width: 120px;
            min-width: 120px;
        }
        
        .responsive-table th:nth-child(15),
        .responsive-table td:nth-child(15) {
            width: 100px;
            min-width: 100px;
        }
    }
    
    /* Tablet Responsive */
    @media (max-width: 1024px) and (min-width: 769px) {
        .responsive-table th,
        .responsive-table td {
            padding: 10px 8px;
            font-size: 13px;
        }
        
        .responsive-table {
            min-width: 2000px;
        }
    }
    
    /* Small Mobile */
    @media (max-width: 480px) {
        .responsive-table th,
        .responsive-table td {
            padding: 6px 4px;
            font-size: 11px;
        }
        
        .responsive-table {
            min-width: 2000px;
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
    
    /* Delete button in controls row */
    .delete-btn:hover {
        background-color: #214589 !important;
        color: white !important;
        border-color: #214589 !important;
    }
    
    .delete-btn:hover i {
        color: white !important;
    }
    
    .delete-btn:hover span {
        color: white !important;
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
    
    .form-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background-color: white;
        transition: border-color 0.2s ease;
        box-sizing: border-box;
    }
    
    .form-select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }
    
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
        cursor: pointer;
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
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- My Activity Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">My Activity</h1>
            </div>
            
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Add New Activity</span>
            </button>
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
        
        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th>Activity Number</th>
                        <th>Staff</th>
                        <th>Location</th>
                        <th>Activity Date</th>
                        <th>Start Hour</th>
                        <th>End Hour</th>
                        <th>Company Name</th>
                        <th>Company Address</th>
                        <th>Contact Person</th>
                        <th>Contact Phone</th>
                        <th>Activity Result</th>
                        <th>Status</th>
                        <th>Latest Update</th>
                        <th>Update By</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($activities ?? [] as $activity)
                    <tr class="cursor-pointer" data-id="{{ $activity->id }}" onclick="openViewModal({{ $activity->id }})">
                        <td>
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $activity->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $activity->activity_number ?? 'N/A' }}</td>
                        <td>{{ $activity->staff->name ?? 'N/A' }}</td>
                        <td>{{ $activity->location ?? 'N/A' }}</td>
                        <td>{{ $activity->activity_date ? $activity->activity_date->format('d/mmm/Y') : 'N/A' }}</td>
                        <td>{{ $activity->start_hour ? \Carbon\Carbon::parse($activity->start_hour)->format('H:i') : 'N/A' }}</td>
                        <td>{{ $activity->end_hour ? \Carbon\Carbon::parse($activity->end_hour)->format('H:i') : 'N/A' }}</td>
                        <td title="{{ $activity->company_name ?? 'N/A' }}">{{ Str::limit($activity->company_name ?? 'N/A', 20) }}</td>
                        <td title="{{ $activity->company_address ?? 'N/A' }}">{{ Str::limit($activity->company_address ?? 'N/A', 30) }}</td>
                        <td>{{ $activity->contact_person ?? 'N/A' }}</td>
                        <td>{{ $activity->contact_phone ?? 'N/A' }}</td>
                        <td title="{{ $activity->activity_result ?? 'N/A' }}">{{ Str::limit($activity->activity_result ?? 'N/A', 25) }}</td>
                        <td>
                            <span class="px-2 py-1 text-xs rounded-full 
                                @if($activity->status === 'completed') bg-green-100 text-green-800
                                @elseif($activity->status === 'cancelled') bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ ucfirst($activity->status ?? 'N/A') }}
                            </span>
                        </td>
                        <td>
                                {!! $activity->updated_at ? $activity->updated_at->format('d F Y') . '<br />at ' . $activity->updated_at->format('H.i') . ' WIB' : 'N/A' !!}
                        </td>
                        <td>{{ $activity->staff->name ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="15" class="p-8 text-center">
                            <p class="text-gray-500">No activities found</p>
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
            <h2 id="modalTitle" class="modal-title">Sales Activity Details</h2>
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
                    <circle cx="12" cy="12" r="10" fill="#1e40af" stroke="#1e40af" stroke-width="2"/>
                <path d="M3 6H5H21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 11V17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 11V17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        </div>
        <h3 class="delete-modal-title" id="deleteModalTitle">Hide Sales Activity</h3>
        <p class="delete-modal-description" id="deleteMessage">Are you sure you want to hide this sales activity? This action can be undone later.</p>
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
                    <circle cx="12" cy="12" r="10" fill="#ef4444" stroke="#ef4444" stroke-width="2"/>
                    <path d="M3 6H5H21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10 11V17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 11V17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M15 9L9 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <h3 class="error-modal-title">Hmm... Something Went Wrong</h3>
        <p class="error-modal-description" id="errorMessage">We couldn't hide the sales activity. Please try again.</p>
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
                    <circle cx="12" cy="12" r="10" fill="#10b981" stroke="#10b981" stroke-width="2"/>
                    <path d="M9 12L11 14L15 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <h3 class="success-modal-title">Activity Added!</h3>
        <p class="success-modal-description" id="successMessage">Your sales activity is now saved and ready to track.<br>All set and safely stored in your records.</p>
    </div>
</div>

<!-- Connection Error Modal -->
<div id="connectionErrorModalOverlay" class="connection-error-modal-overlay" onclick="closeConnectionErrorModal()">
    <div class="connection-error-modal-container" onclick="event.stopPropagation()">
        <div class="connection-error-icon-container">
            <div class="connection-error-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" fill="#ef4444" stroke="#ef4444" stroke-width="2"/>
                    <path d="M15 9L9 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <h3 class="connection-error-modal-title">Whoops! Lost connection.</h3>
        <p class="connection-error-modal-description">
            Can't save your activity right now.<br>
            Reconnect and give it another shot.
        </p>
        <div class="connection-error-modal-buttons">
            <button class="btn-connection-close" onclick="closeConnectionErrorModal()">Close</button>
            <button class="btn-connection-retry" onclick="retryLastAction()">Try Again</button>
        </div>
    </div>
</div>

<!-- Update Error Modal -->
<div id="updateErrorModalOverlay" class="update-error-modal-overlay" onclick="closeUpdateErrorModal()">
    <div class="update-error-modal-container" onclick="event.stopPropagation()">
        <div class="update-error-icon-container">
            <div class="update-error-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" fill="#ef4444" stroke="#ef4444" stroke-width="2"/>
                    <path d="M15 9L9 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9 9L15 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <h3 class="update-error-modal-title">Hmm... didn't work.</h3>
        <p class="update-error-modal-description">
            We couldn't save your edits right now.<br>
            Please check again and try once more soon.
        </p>
        <div class="update-error-modal-buttons">
            <button class="btn-update-error-close" onclick="closeUpdateErrorModal()">Close</button>
            <button class="btn-update-error-retry" onclick="retryLastAction()">Try Again</button>
        </div>
    </div>
</div>

<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;

// Function to format date with 3-digit month
function formatDateWithThreeDigitMonth(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(3, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Function to populate staff dropdown
function populateStaffDropdown() {
    const staffSelect = document.querySelector('select[name="staff_id"]');
    if (staffSelect) {
        // Clear existing options except the first one
        staffSelect.innerHTML = '<option value="">Select Staff</option>';
        
        // Add staff options - using IDs 1-10 since we know there are 10 users
        for (let i = 1; i <= 10; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `User ${i}`;
            staffSelect.appendChild(option);
        }
    }
}

function populateProspectDropdown() {
    const prospectSelect = document.querySelector('select[name="prospect_id"]');
    if (prospectSelect) {
        // Clear existing options except the first one
        prospectSelect.innerHTML = '<option value="">Select a prospect to auto-fill data</option>';
        
        // Fetch prospects from API
        fetch('/marketing/prospects-list')
            .then(response => response.json())
            .then(prospects => {
                prospects.forEach(prospect => {
                    const option = document.createElement('option');
                    option.value = prospect.id;
                    option.textContent = `${prospect.company_name} - ${prospect.contact_person}`;
                    prospectSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading prospects:', error);
            });
    }
}

function loadProspectData() {
    const prospectSelect = document.querySelector('select[name="prospect_id"]');
    const prospectId = prospectSelect.value;
    
    if (!prospectId) {
        // Clear form fields if no prospect selected
        clearProspectFields();
        return;
    }
    
    // Fetch prospect data
    fetch(`/marketing/prospects/${prospectId}/data`)
        .then(response => response.json())
        .then(prospect => {
            // Auto-fill form fields
            const companyNameField = document.querySelector('input[name="company_name"]');
            const companyAddressField = document.querySelector('textarea[name="company_address"]');
            const contactPersonField = document.querySelector('input[name="contact_person"]');
            const contactPhoneField = document.querySelector('input[name="contact_phone"]');
            const companyEmailField = document.querySelector('input[name="company_email"]');
            
            if (companyNameField) companyNameField.value = prospect.company_name || '';
            if (companyAddressField) companyAddressField.value = prospect.company_address || '';
            if (contactPersonField) contactPersonField.value = prospect.contact_person || '';
            if (contactPhoneField) contactPhoneField.value = prospect.contact_phone || '';
            if (companyEmailField) companyEmailField.value = prospect.contact_email || '';
        })
        .catch(error => {
            console.error('Error loading prospect data:', error);
        });
}

function clearProspectFields() {
    const companyNameField = document.querySelector('input[name="company_name"]');
    const companyAddressField = document.querySelector('textarea[name="company_address"]');
    const contactPersonField = document.querySelector('input[name="contact_person"]');
    const contactPhoneField = document.querySelector('input[name="contact_phone"]');
    const companyEmailField = document.querySelector('input[name="company_email"]');
    
    if (companyNameField) companyNameField.value = '';
    if (companyAddressField) companyAddressField.value = '';
    if (contactPersonField) contactPersonField.value = '';
    if (contactPhoneField) contactPhoneField.value = '';
    if (companyEmailField) companyEmailField.value = '';
}

function loadProspectDataEdit() {
    const prospectSelect = document.querySelector('select[name="prospect_id"]');
    const prospectId = prospectSelect.value;
    
    if (!prospectId) {
        return; // Don't clear fields in edit mode, just don't auto-fill
    }
    
    // Fetch prospect data
    fetch(`/marketing/prospects/${prospectId}/data`)
        .then(response => response.json())
        .then(prospect => {
            // Auto-fill form fields
            const companyNameField = document.querySelector('input[name="company_name"]');
            const companyAddressField = document.querySelector('textarea[name="company_address"]');
            const contactPersonField = document.querySelector('input[name="contact_person"]');
            const contactPhoneField = document.querySelector('input[name="contact_phone"]');
            const companyEmailField = document.querySelector('input[name="company_email"]');
            
            if (companyNameField) companyNameField.value = prospect.company_name || '';
            if (companyAddressField) companyAddressField.value = prospect.company_address || '';
            if (contactPersonField) contactPersonField.value = prospect.contact_person || '';
            if (contactPhoneField) contactPhoneField.value = prospect.contact_phone || '';
            if (companyEmailField) companyEmailField.value = prospect.contact_email || '';
        })
        .catch(error => {
            console.error('Error loading prospect data:', error);
        });
}

// Initialize table scroll functionality
function initTableScroll() {
    const tableContainer = document.querySelector('.table-container');
    if (tableContainer) {
        const checkScroll = () => {
            if (tableContainer.scrollWidth > tableContainer.clientWidth) {
                tableContainer.classList.add('scrollable');
            } else {
                tableContainer.classList.remove('scrollable');
            }
        };
        
        checkScroll();
        window.addEventListener('resize', checkScroll);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initTableScroll();
});

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
        alert('Please select at least one sales activity to hide');
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
}

// CRUD Modal functions
function openCreateModal() {
    // Generate activity number
    const now = new Date();
    const dateStr = now.getFullYear().toString() + 
                   (now.getMonth() + 1).toString().padStart(2, '0') + 
                   now.getDate().toString().padStart(2, '0');
    const randomNum = Math.floor(Math.random() * 999).toString().padStart(3, '0');
    const activityNumber = `ACT-${dateStr}-${randomNum}`;
    
    document.getElementById('modalTitle').textContent = 'Add New Sales Activity';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Let's add your new sales activity details and make sure nothing gets missed.</p>
        <form id="createForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Activity Number *</label>
                        <input type="text" name="activity_number" class="form-input" value="${activityNumber}" readonly style="background-color: #f9fafb; color: #6b7280;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Staff *</label>
                        <select name="staff_id" class="form-select" required>
                            <option value="">Select Staff</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Select Prospect (Optional)</label>
                        <select name="prospect_id" id="prospectSelect" class="form-select" onchange="loadProspectData()">
                            <option value="">Select a prospect to auto-fill data</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location *</label>
                        <input type="text" name="location" class="form-input" required placeholder="Enter activity location">
                    </div>
                    <!-- Activity Date field hidden - auto-generated on creation -->
                </div>
                
                <!-- Right Column -->
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Start Hour *</label>
                        <input type="time" name="start_hour" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Hour *</label>
                        <input type="time" name="end_hour" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="company_name" class="form-input" required placeholder="Enter company name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PIC Name *</label>
                        <input type="text" name="pic_name" class="form-input" required placeholder="Enter PIC name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person *</label>
                        <input type="text" name="contact_person" class="form-input" required placeholder="Enter contact person name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Phone</label>
                        <input type="tel" name="contact_phone" class="form-input" placeholder="Enter contact phone number">
                    </div>
                </div>
            </div>
            
            <!-- Full Width Fields -->
            <div class="form-group">
                <label class="form-label">Company Address</label>
                <textarea name="company_address" class="form-input form-textarea" placeholder="Enter company address"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Company Email</label>
                <input type="email" name="company_email" class="form-input" placeholder="Enter company email">
            </div>
            
            <div class="form-group">
                <label class="form-label">Activity Description *</label>
                <textarea name="activity" class="form-input form-textarea" required placeholder="Describe the activity..."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Activity Result</label>
                <textarea name="activity_result" class="form-input form-textarea" placeholder="Enter the result of this activity..."></textarea>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Create Activity</button>
        </div>
    `;
    openModal();
    
    // Populate staff and prospect dropdowns
    populateStaffDropdown();
    populateProspectDropdown();
}

function openViewModal(id) {
    // Load data via AJAX
    fetch(`/marketing/sales-activities/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Sales Activity Details';
            document.getElementById('modalBody').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div class="detail-item">
                            <label class="form-label">Activity Number</label>
                            <p class="detail-value">${data.activity_number || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Staff</label>
                            <p class="detail-value">${data.staff ? data.staff.name : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Location</label>
                            <p class="detail-value">${data.location || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Activity Date</label>
                            <p class="detail-value">${data.activity_date ? formatDateWithThreeDigitMonth(new Date(data.activity_date)) : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Start Hour</label>
                            <p class="detail-value">${data.start_hour ? (data.start_hour.includes('T') ? new Date(data.start_hour).toTimeString().slice(0,5) : data.start_hour) : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">End Hour</label>
                            <p class="detail-value">${data.end_hour ? (data.end_hour.includes('T') ? new Date(data.end_hour).toTimeString().slice(0,5) : data.end_hour) : 'N/A'}</p>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="space-y-4">
                        <div class="detail-item">
                            <label class="form-label">Company Name</label>
                            <p class="detail-value">${data.company_name || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">PIC Name</label>
                            <p class="detail-value">${data.pic_name || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Company Email</label>
                            <p class="detail-value">${data.company_email || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Contact Person</label>
                            <p class="detail-value">${data.contact_person || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Contact Phone</label>
                            <p class="detail-value">${data.contact_phone || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Latest Update</label>
                            <p class="detail-value">${data.updated_at ? formatDateWithThreeDigitMonth(new Date(data.updated_at)) : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated By</label>
                            <p class="detail-value">${data.updater ? data.updater.name : 'N/A'}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Full Width Fields -->
                <div class="mt-6">
                    <div class="detail-item">
                        <label class="form-label">Company Address</label>
                        <p class="detail-value">${data.company_address || 'N/A'}</p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <div class="detail-item">
                        <label class="form-label">Activity Description</label>
                        <p class="detail-value">${data.activity || 'N/A'}</p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <div class="detail-item">
                        <label class="form-label">Activity Result</label>
                        <p class="detail-value">${data.activity_result || 'N/A'}</p>
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
            console.error('Error loading activity data:', error);
            alert('Error loading activity data');
        });
}

function openEditModal(id) {
    // Load data via AJAX
    fetch(`/marketing/sales-activities/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Sales Activity';
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update your sales activity details and make sure nothing gets missed.</p>
                <form id="editForm">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Activity Number *</label>
                                <input type="text" name="activity_number" class="form-input" value="${data.activity_number || ''}" readonly style="background-color: #f9fafb; color: #6b7280;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Staff *</label>
                                <select name="staff_id" class="form-select" required>
                                    <option value="">Select Staff</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Select Prospect (Optional)</label>
                                <select name="prospect_id" id="prospectSelectEdit" class="form-select" onchange="loadProspectDataEdit()">
                                    <option value="">Select a prospect to auto-fill data</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Location *</label>
                                <input type="text" name="location" class="form-input" value="${data.location || ''}" required placeholder="Enter activity location">
                            </div>
                            <!-- Activity Date field hidden - auto-generated on creation -->
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Start Hour *</label>
                                <input type="time" name="start_hour" class="form-input" value="${data.start_hour ? (data.start_hour.includes('T') ? new Date(data.start_hour).toTimeString().slice(0,5) : data.start_hour) : ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Hour *</label>
                                <input type="time" name="end_hour" class="form-input" value="${data.end_hour ? (data.end_hour.includes('T') ? new Date(data.end_hour).toTimeString().slice(0,5) : data.end_hour) : ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Name *</label>
                                <input type="text" name="company_name" class="form-input" value="${data.company_name || ''}" required placeholder="Enter company name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">PIC Name *</label>
                                <input type="text" name="pic_name" class="form-input" value="${data.pic_name || ''}" required placeholder="Enter PIC name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Person *</label>
                                <input type="text" name="contact_person" class="form-input" value="${data.contact_person || ''}" required placeholder="Enter contact person name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" name="contact_phone" class="form-input" value="${data.contact_phone || ''}" placeholder="Enter contact phone number">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Full Width Fields -->
                    <div class="form-group">
                        <label class="form-label">Company Address</label>
                        <textarea name="company_address" class="form-input form-textarea" placeholder="Enter company address">${data.company_address || ''}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Company Email</label>
                        <input type="email" name="company_email" class="form-input" value="${data.company_email || ''}" placeholder="Enter company email">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Activity Description *</label>
                        <textarea name="activity" class="form-input form-textarea" required placeholder="Describe the activity...">${data.activity || ''}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Activity Result</label>
                        <textarea name="activity_result" class="form-input form-textarea" placeholder="Enter the result of this activity...">${data.activity_result || ''}</textarea>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Update Activity</button>
                </div>
            `;
            openModal();
            
            // Populate staff and prospect dropdowns and set selected values
            populateStaffDropdown();
            populateProspectDropdown();
            setTimeout(() => {
                const staffSelect = document.querySelector('select[name="staff_id"]');
                if (staffSelect && data.staff_id) {
                    staffSelect.value = data.staff_id;
                }
                const prospectSelect = document.querySelector('select[name="prospect_id"]');
                if (prospectSelect && data.prospect_id) {
                    prospectSelect.value = data.prospect_id;
                }
            }, 100);
        })
        .catch(error => {
            console.error('Error loading activity data:', error);
            alert('Error loading activity data');
        });
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
    
    fetch('/marketing/sales-activities', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(errorData => {
                    console.log('Error response:', errorData);
                    throw new Error(JSON.stringify(errorData));
                });
            } else {
                // Response is not JSON (likely HTML error page)
                return response.text().then(html => {
                    console.log('Non-JSON error response:', html);
                    throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                });
            }
        }
        
        // Check if successful response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(html => {
                console.log('Non-JSON success response:', html);
                throw new Error('Server returned non-JSON response');
            });
        }
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
                    alert('Error creating activity: ' + errorData.message);
                }
            } catch (e) {
                alert('Error creating activity: ' + error.message);
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
    
    fetch(`/marketing/sales-activities/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
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
            alert('Error updating activity');
        }
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const title = count === 1 
        ? 'Hide Sales Activity?'
        : `Hide ${count} Sales Activities?`;
    const message = count === 1 
        ? 'Are you sure you want to hide this sales activity? This action can be undone later.'
        : `Are you sure you want to hide ${count} sales activities? This action can be undone later.`;
    
    document.getElementById('deleteModalTitle').textContent = title;
    document.getElementById('deleteMessage').textContent = message;
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function confirmDelete() {
    // Show loading state
    const hideButton = document.querySelector('.btn-hide');
    const originalText = hideButton.textContent;
    hideButton.textContent = 'Hiding...';
    hideButton.disabled = true;
    
    closeDeleteModal();
    
    fetch('/marketing/sales-activities/bulk-delete', {
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
    })
    .finally(() => {
        hideButton.textContent = originalText;
        hideButton.disabled = false;
    });
}

// Success Modal functions
function showSuccessModal(type = 'create') {
    const successModal = document.getElementById('successModalOverlay');
    const titleElement = successModal.querySelector('.success-modal-title');
    const descriptionElement = successModal.querySelector('#successMessage');
    
    if (type === 'create') {
        titleElement.textContent = 'Activity Added!';
        descriptionElement.innerHTML = 'Your sales activity is now saved and ready to track.<br>All set and safely stored in your records.';
    } else if (type === 'update') {
        titleElement.textContent = 'Activity Updated!';
        descriptionElement.innerHTML = 'Your sales activity details are now updated successfully.<br>Everything\'s saved and ready to track.';
    } else {
        // For delete/hide operations
        const count = arguments[1] || 1;
        titleElement.textContent = 'Activity Hidden!';
        descriptionElement.innerHTML = count === 1 
            ? 'The sales activity is now hidden here. No worries—it\'s still safe and sound in the database.'
            : `${count} sales activities are now hidden here. No worries—they're still safe and sound in the database.`;
    }
    
    successModal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Auto close after 3 seconds
    setTimeout(() => {
        closeSuccessModal();
        location.reload(); // Reload to show the updated activity
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
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t hide the sales activity. Please try again.';
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

// Event listeners
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
