@extends('layouts.app')

@section('title', 'Survey')
@section('breadcrumb', 'Home / Marketing / Survey')

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
    
    /* Custom scrollbar styling */
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
        padding: 12px 8px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
        font-size: 14px;
        line-height: 1.4;
        overflow: visible;
        text-overflow: unset;
        max-width: 200px;
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
    
    /* Column width adjustments for better responsive behavior */
    .responsive-table th:nth-child(1),
    .responsive-table td:nth-child(1) {
        width: 50px;
        min-width: 50px;
        max-width: 50px;
    }
    
    .responsive-table th:nth-child(2),
    .responsive-table td:nth-child(2) {
        width: 120px;
        min-width: 120px;
    }
    
    .responsive-table th:nth-child(3),
    .responsive-table td:nth-child(3) {
        width: 150px;
        min-width: 150px;
    }
    
    .responsive-table th:nth-child(4),
    .responsive-table td:nth-child(4) {
        width: 200px;
        min-width: 200px;
    }
    
    .responsive-table th:nth-child(5),
    .responsive-table td:nth-child(5) {
        width: 200px;
        min-width: 200px;
    }
    
    .responsive-table th:nth-child(6),
    .responsive-table td:nth-child(6) {
        width: 200px;
        min-width: 200px;
        max-width: 200px;
    }
    
    .responsive-table th:nth-child(7),
    .responsive-table td:nth-child(7) {
        width: 120px;
        min-width: 120px;
    }
    
    .responsive-table th:nth-child(8),
    .responsive-table td:nth-child(8) {
        width: 150px;
        min-width: 150px;
    }
    
    .responsive-table th:nth-child(9),
    .responsive-table td:nth-child(9) {
        width: 150px;
        min-width: 150px;
    }
    
    .responsive-table th:nth-child(10),
    .responsive-table td:nth-child(10) {
        width: 120px;
        min-width: 120px;
    }
    
    .responsive-table th:nth-child(11),
    .responsive-table td:nth-child(11) {
        width: 150px;
        min-width: 150px;
    }
    
    .responsive-table th:nth-child(12),
    .responsive-table td:nth-child(12) {
        width: 180px;
        min-width: 180px;
    }
    
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
        
        /* Adjust column widths for mobile */
        .responsive-table th:nth-child(1),
        .responsive-table td:nth-child(1) {
            width: 40px;
            min-width: 40px;
            max-width: 40px;
        }
        
        .responsive-table th:nth-child(2),
        .responsive-table td:nth-child(2) {
            width: 100px;
            min-width: 100px;
        }
        
        .responsive-table th:nth-child(3),
        .responsive-table td:nth-child(3) {
            width: 120px;
            min-width: 120px;
        }
        
        .responsive-table th:nth-child(4),
        .responsive-table td:nth-child(4) {
            width: 150px;
            min-width: 150px;
        }
        
        .responsive-table th:nth-child(5),
        .responsive-table td:nth-child(5) {
            width: 150px;
            min-width: 150px;
        }
        
        .responsive-table th:nth-child(6),
        .responsive-table td:nth-child(6) {
            width: 80px;
            min-width: 80px;
        }
        
        .responsive-table th:nth-child(7),
        .responsive-table td:nth-child(7) {
            width: 100px;
            min-width: 100px;
        }
        
        .responsive-table th:nth-child(8),
        .responsive-table td:nth-child(8) {
            width: 120px;
            min-width: 120px;
        }
        
        .responsive-table th:nth-child(9),
        .responsive-table td:nth-child(9) {
            width: 120px;
            min-width: 120px;
        }
        
        .responsive-table th:nth-child(10),
        .responsive-table td:nth-child(10) {
            width: 100px;
            min-width: 100px;
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
        
        /* Hide scroll indicator on mobile */
        .table-container::after {
            display: none;
        }
    }
    
    /* Tablet Responsive */
    @media (max-width: 1024px) and (min-width: 769px) {
        .responsive-table {
            min-width: 1300px;
        }
        
        .responsive-table th,
        .responsive-table td {
            padding: 10px 7px;
            font-size: 13px;
        }
    }
    
    /* Small mobile devices */
    @media (max-width: 480px) {
        .responsive-table {
            min-width: 1000px;
        }
        
        .responsive-table th,
        .responsive-table td {
            padding: 6px 4px;
            font-size: 11px;
        }
        
        /* Further reduce column widths for very small screens */
        .responsive-table th:nth-child(1),
        .responsive-table td:nth-child(1) {
            width: 35px;
            min-width: 35px;
            max-width: 35px;
        }
        
        .responsive-table th:nth-child(2),
        .responsive-table td:nth-child(2) {
            width: 80px;
            min-width: 80px;
        }
        
        .responsive-table th:nth-child(3),
        .responsive-table td:nth-child(3) {
            width: 100px;
            min-width: 100px;
        }
        
        .responsive-table th:nth-child(4),
        .responsive-table td:nth-child(4) {
            width: 120px;
            min-width: 120px;
        }
        
        .responsive-table th:nth-child(5),
        .responsive-table td:nth-child(5) {
            width: 120px;
            min-width: 120px;
        }
        
        .responsive-table th:nth-child(6),
        .responsive-table td:nth-child(6) {
            width: 70px;
            min-width: 70px;
        }
        
        .responsive-table th:nth-child(7),
        .responsive-table td:nth-child(7) {
            width: 80px;
            min-width: 80px;
        }
        
        .responsive-table th:nth-child(8),
        .responsive-table td:nth-child(8) {
            width: 100px;
            min-width: 100px;
        }
        
        .responsive-table th:nth-child(9),
        .responsive-table td:nth-child(9) {
            width: 100px;
            min-width: 100px;
        }
        
        .responsive-table th:nth-child(10),
        .responsive-table td:nth-child(10) {
            width: 80px;
            min-width: 80px;
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
        z-index: 1001;
        pointer-events: auto;
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
        position: relative;
        z-index: 1;
        pointer-events: auto;
    }
    
    .form-group * {
        pointer-events: auto;
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
        background-color: white;
        color: #1f2937;
        pointer-events: auto;
        cursor: text;
        position: relative;
        z-index: 10;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
        background-color: white;
    }
    
    .form-input:hover {
        border-color: #9ca3af;
    }
    
    .form-input:disabled {
        background-color: #f9fafb;
        color: #6b7280;
        cursor: not-allowed;
    }
    
    .form-textarea {
        min-height: 100px;
        resize: vertical;
        background-color: white;
        color: #1f2937;
        pointer-events: auto;
        cursor: text;
        position: relative;
        z-index: 10;
    }
    
    .form-textarea:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
        background-color: white;
    }
    
    .form-textarea:hover {
        border-color: #9ca3af;
    }
    
    /* Ensure all form elements are interactive */
    input, textarea {
        pointer-events: auto !important;
        cursor: text !important;
    }
    
    select {
        pointer-events: auto !important;
        cursor: pointer !important;
    }
    
    select.form-input {
        cursor: pointer !important;
    }
    
    select:hover {
        cursor: pointer !important;
    }
    
    select:focus {
        cursor: pointer !important;
    }
    
    /* Additional select cursor fixes */
    .form-input[type="select"],
    .form-input select,
    select.form-input,
    .modal-body select,
    .modal-body .form-input[type="select"] {
        cursor: pointer !important;
    }
    
    .modal-body select:hover,
    .modal-body select:focus,
    .modal-body select:active {
        cursor: pointer !important;
    }
    
    /* Fix any potential overlay issues */
    .modal-body {
        pointer-events: auto;
        position: relative;
        z-index: 1;
    }
    
    .modal-body * {
        pointer-events: auto;
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
       /* width: 500px; */
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
        text-align: center;
    }
    
    .delete-modal-description {
        font-size: 16px;
        color: #6b7280;
        margin: 0 0 32px 0;
        line-height: 1.5;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
        text-align: center;
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
        
        <!-- Survey Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Survey</h1>
            </div>
            
            <!--<button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Add New Survey</span>
            </button> -->
            
            <div class="flex flex-row justify-end items-center gap-2">
                <x-filter-status />
                <a href="{{ route('marketing.surveys.wizard.create') }}" class="btn btn-success">
                    <i class="fas fa-magic"></i>
                    <span>Add New Survey</span>
                </a>
            </div>
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
                    <i class="fas fa-ban"></i>
                    <span>Cancel</span>
                </button>
            </div>
            
        </div>
        
        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table" id="surveysTable">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th data-column="survey_number">Survey Number</th>
                        <th data-column="survey_date" data-type="date">Survey Date</th>
                        <th data-column="company_name">Company Name</th>
                        <th data-column="building_name">Building Name</th>
                        <th data-column="survey_location">Survey Location</th>
                        <th data-column="building_location_detail">Lokasi Detail</th>
                        <th data-column="status">Status</th>
                        {{-- <th data-column="surveyor.name" data-relation="surveyor">Surveyor</th> --}}
                        <th data-column="marketing.name" data-relation="marketing">Marketing</th>
                        {{-- <th data-column="latitude|longitude">Location (Lat/Lng)</th> --}}
                        <th data-column="creator.name" data-relation="creator">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updater.name" data-relation="updater">Last Updated By</th>
                        <th class="w-[150px]" data-column="surveys.updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($surveys ?? [] as $survey)
                    <tr data-id="{{ $survey->id }}" onclick="window.location.href='{{ route('marketing.surveys.show', $survey->id) }}'">
                        <td class="text-center">
                            @if($survey->status !== 'approved')
                                <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" value="{{ $survey->id }}" onclick="event.stopPropagation()">
                            @else
                                <input type="checkbox" disabled class="w-4 h-4 bg-gray-100 border border-gray-300 rounded cursor-not-allowed" title="Approved surveys cannot be deleted">
                            @endif
                        </td>
                        <td>{{ $survey->survey_number ?? 'N/A' }}</td>
                        <td>
                            @if($survey->survey_date)
                                {{ $survey->survey_date->format('d/M/Y') }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $survey->display_company_name }}</td>
                        <td>{{ $survey->display_building_name }}</td>
                        <td>
                            <div class="font-medium">{{ $survey->survey_location ?? 'N/A' }}</div>
                            @if($survey->display_address_one && $survey->display_address_one !== $survey->survey_location)
                                <div class="text-xs text-gray-500 mt-0.5">{{ $survey->display_address_one }}</div>
                            @endif
                            @if($survey->display_address_two)
                                <div class="text-xs text-gray-500">{{ $survey->display_address_two }}</div>
                            @endif
                        </td>
                        <td>{{ $survey->building_location_detail ?? '-' }}</td>
                        <td>
                            @php
                                $statusColors = [
                                    'approved' => 'background-color: #dcfce7; color: #166534;',
                                    'rejected' => 'background-color: #fef2f2; color: #991b1b;',
                                    'cancelled' => 'background-color: #fef2f2; color: #991b1b;',
                                    'submitted' => 'background-color: #ffedd5; color: #c2410c;',
                                    'pending' => 'background-color: #ffedd5; color: #c2410c;',
                                    'draft' => 'background-color: #e5e7eb; color: #4b5563;',
                                ];
                                $style = $statusColors[$survey->status] ?? 'background-color: #f3f4f6; color: #374151;';
                            @endphp
                            <span style="padding: 4px 8px; font-size: 12px; border-radius: 9999px; {{ $style }}">
                                {{ $survey->status_text ?? ucfirst($survey->status ?? 'N/A') }}
                            </span>
                        </td>
                        {{-- <td>{{ $survey->surveyor->name ?? 'N/A' }}</td> --}}
                        <td>{{ $survey->marketing->name ?? 'N/A' }}</td>
                        {{-- <td>
                            @if($survey->latitude && $survey->longitude)
                                {{ $survey->latitude }}, {{ $survey->longitude }}
                            @else
                                N/A
                            @endif
                        </td> --}}
                        <td>{{ $survey->creator->name ?? 'N/A' }}</td>
                        <td>
                            @if($survey->created_at)
                                {{ $survey->created_at->format('d/M/Y') }}<br>
                                <small class="text-gray-500">at {{ $survey->created_at->format('H.i') }} WIB</small>
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $survey->updater->name ?? 'N/A' }}</td>
                        <td>
                            @if($survey->updated_at)
                                {{ $survey->updated_at->format('d/M/Y') }}<br>
                                <small class="text-gray-500">at {{ $survey->updated_at->format('H.i') }} WIB</small>
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="p-8 text-center">
                            <p class="text-lg text-gray-500">No surveys found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($surveys->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $surveys->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Modal Title</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Modal content will be loaded here -->
        </div>
        <div class="modal-footer" id="modalFooter">
            <!-- Modal footer will be loaded here -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay" onclick="closeDeleteModal()">
    <div class="delete-modal-container">
        <div class="delete-modal-container" onclick="event.stopPropagation()">
            <div class="delete-icon-container">
                <svg class="delete-icon" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Trash can body -->
                    <rect x="25" y="35" width="50" height="45" rx="3" fill="#1e40af" stroke="#1e3a8a" stroke-width="2"/>
                    <!-- Trash can lid (slightly open) -->
                    <rect x="20" y="30" width="60" height="8" rx="4" fill="#1e40af" stroke="#1e3a8a" stroke-width="2"/>
                    <rect x="22" y="32" width="56" height="4" rx="2" fill="#3b82f6"/>
                    <!-- Trash can handle -->
                    <rect x="45" y="25" width="10" height="8" rx="2" fill="#1e40af" stroke="#1e3a8a" stroke-width="2"/>
                    <!-- Trash can lines -->
                    <line x1="35" y1="45" x2="35" y2="70" stroke="#3b82f6" stroke-width="2"/>
                    <line x1="50" y1="45" x2="50" y2="70" stroke="#3b82f6" stroke-width="2"/>
                    <line x1="65" y1="45" x2="65" y2="70" stroke="#3b82f6" stroke-width="2"/>
                </svg>
            </div>
            <h3 id="deleteModalTitle" class="delete-modal-title">Cancel Survey?</h3>
            <p class="delete-modal-description">Are you sure you want to cancel this survey? It will be marked as cancelled.</p>
            <div class="delete-modal-buttons">
                <button class="btn-cancel" onclick="closeDeleteModal()">No, Keep It</button>
                <button class="btn-hide" onclick="confirmDelete()">Yes, Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay" onclick="closeErrorModal()">
    <div class="error-modal-container">
        <div class="error-modal-container" onclick="event.stopPropagation()">
            <div class="error-icon-container">
                <svg class="error-icon" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Trash can body -->
                    <rect x="25" y="35" width="50" height="45" rx="3" fill="#1e40af" stroke="#1e3a8a" stroke-width="2"/>
                    <!-- Trash can lid -->
                    <rect x="20" y="30" width="60" height="8" rx="4" fill="#1e40af" stroke="#1e3a8a" stroke-width="2"/>
                    <!-- Trash can handle -->
                    <rect x="45" y="25" width="10" height="8" rx="2" fill="#1e40af" stroke="#1e3a8a" stroke-width="2"/>
                    <!-- Trash can lines -->
                    <line x1="35" y1="45" x2="35" y2="70" stroke="#3b82f6" stroke-width="2"/>
                    <line x1="50" y1="45" x2="50" y2="70" stroke="#3b82f6" stroke-width="2"/>
                    <line x1="65" y1="45" x2="65" y2="70" stroke="#3b82f6" stroke-width="2"/>
                    <!-- Red X circle -->
                    <circle cx="75" cy="25" r="15" fill="#ef4444" stroke="#dc2626" stroke-width="2"/>
                    <line x1="68" y1="18" x2="82" y2="32" stroke="white" stroke-width="3" stroke-linecap="round"/>
                    <line x1="82" y1="18" x2="68" y2="32" stroke="white" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>
            <h3 class="error-modal-title">Ups... Terjadi Kendala</h3>
            <p class="error-modal-description">Data tidak berhasil diproses saat ini. Silakan periksa koneksi Anda dan coba lagi.</p>
            <div class="error-modal-buttons">
                <button class="btn-error-close" onclick="closeErrorModal()">Tutup</button>
                <button class="btn-error-retry" onclick="retryDelete()">Coba Lagi</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay" onclick="closeSuccessModal()">
    <div class="success-modal-container">
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
            <h2 class="success-modal-title">Survey Berhasil Ditambahkan</h2>
            
            <!-- Description -->
            <p id="successModalDescription" class="success-modal-description">
                Survey berhasil disimpan dan siap diproses lebih lanjut.<br>
                Data sudah tersimpan dengan aman.
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
            <h2 class="connection-error-modal-title">Ups! Koneksi Terputus.</h2>
            
            <!-- Description -->
            <p class="connection-error-modal-description">
                Survey tidak bisa disimpan saat ini.<br>
                Sambungkan kembali koneksi lalu coba lagi.
            </p>
            
            <!-- Buttons -->
            <div class="connection-error-modal-buttons">
                <button class="btn btn-connection-close" onclick="closeConnectionErrorModal()">Tutup</button>
                <button class="btn btn-connection-retry" onclick="retryLastAction()">Coba Lagi</button>
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
            <h2 class="update-error-modal-title">Ups... Belum Berhasil.</h2>
            
            <!-- Description -->
            <p class="update-error-modal-description">
                Perubahan belum berhasil disimpan saat ini.<br>
                Silakan periksa kembali lalu coba lagi.
            </p>
            
            <!-- Buttons -->
            <div class="update-error-modal-buttons">
                <button class="btn btn-update-error-close" onclick="closeUpdateErrorModal()">Tutup</button>
                <button class="btn btn-update-error-retry" onclick="retryLastAction()">Coba Lagi</button>
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
    fetch(`/marketing/surveys/${id}`)
        .then(response => {
            console.log('View modal response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Survey Details';
            document.getElementById('modalBody').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div class="detail-item">
                            <label class="form-label">Survey Number</label>
                            <p class="detail-value">${data.survey_number || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Survey Date</label>
                            <p class="detail-value">${data.survey_date ? formatDateWithThreeDigitMonth(new Date(data.survey_date)) : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Company Name</label>
                            <p class="detail-value">${data.company_name || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Contact Person</label>
                            <p class="detail-value">${data.contact_person || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Customer Type</label>
                            <p class="detail-value">${data.customer_type || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Position</label>
                            <p class="detail-value">${data.position || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Surveyor</label>
                            <p class="detail-value">${data.surveyor ? data.surveyor.name : 'N/A'}</p>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="space-y-4">
                        <div class="detail-item">
                            <label class="form-label">Marketing</label>
                            <p class="detail-value">${data.marketing ? data.marketing.name : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Phone 1</label>
                            <p class="detail-value">${data.phone_1 || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Phone 2</label>
                            <p class="detail-value">${data.phone_2 || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Email</label>
                            <p class="detail-value">${data.email || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Survey Location</label>
                            <p class="detail-value">${data.survey_location || 'N/A'}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Location Details Section -->
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Location Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Building Name</label>
                            <p class="detail-value">${data.building_name || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Address 1</label>
                            <p class="detail-value">${data.address_1 || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Address 2</label>
                            <p class="detail-value">${data.address_2 || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Province</label>
                            <p class="detail-value">${data.province || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">City</label>
                            <p class="detail-value">${data.city || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">District</label>
                            <p class="detail-value">${data.district || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Village</label>
                            <p class="detail-value">${data.village || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Postal Code</label>
                            <p class="detail-value">${data.postal_code || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Latitude</label>
                            <p class="detail-value">${data.latitude || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Longitude</label>
                            <p class="detail-value">${data.longitude || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Survey Outcome Section -->
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Survey Outcome</h3>
                    <div class="grid grid-cols-1 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Survey Result</label>
                            <p class="detail-value">${data.survey_result || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Recommendations</label>
                            <p class="detail-value">${data.recommendations || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- System Info Section -->
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">System Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="detail-item">
                            <label class="form-label">Created By</label>
                            <p class="detail-value">${data.creator ? data.creator.name : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated By</label>
                            <p class="detail-value">${data.updater ? data.updater.name : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Created At</label>
                            <p class="detail-value">${data.created_at ? new Date(data.created_at).toLocaleString() : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Updated At</label>
                            <p class="detail-value">${data.updated_at ? new Date(data.updated_at).toLocaleString() : 'N/A'}</p>
                        </div>
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
            console.error('Error loading survey data:', error);
            if (error.message.includes('500')) {
                showErrorDialog('Gagal', 'Terjadi kesalahan server. Endpoint survey tidak berjalan dengan baik. Silakan periksa backend route.');
            } else if (error.message.includes('404')) {
                showErrorDialog('Gagal', 'Survey tidak ditemukan. Data yang diminta tidak tersedia.');
            } else {
                showErrorDialog('Gagal', error.message || 'Terjadi kesalahan saat memuat data survey.');
            }
        });
}

function openEditModal(id) {
    // Load data via AJAX
    fetch(`/marketing/surveys/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Survey';
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update your survey details and make sure nothing gets missed.</p>
                <form id="editForm">
                    <input type="hidden" name="id" value="${data.id}">
                    
                    <!-- Survey Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Survey Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Survey Number *</label>
                                <input type="text" name="survey_number" class="form-input" value="${data.survey_number || ''}" readonly style="background-color: #f9fafb; color: #6b7280;" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-input" required>
                                    <option value="draft" ${data.status === 'draft' ? 'selected' : ''}>Draft</option>
                                    <option value="submitted" ${data.status === 'submitted' ? 'selected' : ''}>Submitted</option>
                                    <option value="approved" ${data.status === 'approved' ? 'selected' : ''}>Approved</option>
                                    <option value="rejected" ${data.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Survey Date *</label>
                                <input type="date" name="survey_date" class="form-input" value="${data.survey_date ? data.survey_date.substring(0, 10) : ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Surveyor *</label>
                                <select name="surveyor_id" class="form-input" required>
                                    <option value="">Select Surveyor</option>
                                    @if(isset($surveyors) && count($surveyors) > 0)
                                        @foreach($surveyors as $surveyor)
                                            <option value="{{ $surveyor->id }}" ${data.surveyor_id == {{ $surveyor->id }} ? 'selected' : ''}>{{ $surveyor->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Survey Location *</label>
                                <input type="text" name="survey_location" class="form-input" value="${data.survey_location || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Latitude</label>
                                <input type="text" name="latitude" class="form-input" value="${data.latitude || ''}" placeholder="e.g., -6.2088">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Longitude</label>
                                <input type="text" name="longitude" class="form-input" value="${data.longitude || ''}" placeholder="e.g., 106.8456">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pipeline Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Pipeline Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label">Pipeline *</label>
                                <select name="prospect_id" class="form-input" required>
                                    <option value="">Select Pipeline</option>
                                    @if(isset($prospects) && count($prospects) > 0)
                                        @foreach($prospects as $prospect)
                                            <option value="{{ $prospect->id }}" ${data.prospect_id == {{ $prospect->id }} ? 'selected' : ''}>{{ $prospect->company_name }} - {{ $prospect->contact_person }}</option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>No pipeline available</option>
                                    @endif
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Name *</label>
                                <input type="text" name="company_name" class="form-input" value="${data.company_name || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Customer Type</label>
                                <select name="customer_type" class="form-input">
                                    <option value="">Select Customer Type</option>
                                    <option value="individual" ${data.customer_type === 'individual' ? 'selected' : ''}>Individual</option>
                                    <option value="corporate" ${data.customer_type === 'corporate' ? 'selected' : ''}>Corporate</option>
                                    <option value="government" ${data.customer_type === 'government' ? 'selected' : ''}>Government</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Person *</label>
                                <input type="text" name="contact_person" class="form-input" value="${data.contact_person || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-input" value="${data.email || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone 1 *</label>
                                <input type="text" name="phone_1" class="form-input" value="${data.phone_1 || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone 2</label>
                                <input type="text" name="phone_2" class="form-input" value="${data.phone_2 || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Jabatan/Posisi</label>
                                <input type="text" name="position" class="form-input" value="${data.position || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nama Gedung</label>
                                <input type="text" name="building_name" class="form-input" value="${data.building_name || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Alamat 1 *</label>
                                <textarea name="address_1" class="form-input form-textarea" rows="2" required>${data.address_1 || ''}</textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Alamat 2</label>
                                <textarea name="address_2" class="form-input form-textarea" rows="2">${data.address_2 || ''}</textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Provinsi</label>
                                <select name="province" class="form-input">
                                    <option value="">Select Province</option>
                                    <option value="DKI Jakarta" ${data.province === 'DKI Jakarta' ? 'selected' : ''}>DKI Jakarta</option>
                                    <option value="Jawa Barat" ${data.province === 'Jawa Barat' ? 'selected' : ''}>Jawa Barat</option>
                                    <option value="Jawa Tengah" ${data.province === 'Jawa Tengah' ? 'selected' : ''}>Jawa Tengah</option>
                                    <option value="Jawa Timur" ${data.province === 'Jawa Timur' ? 'selected' : ''}>Jawa Timur</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kota/Kabupaten</label>
                                <select name="city" class="form-input">
                                    <option value="">Select City</option>
                                    <option value="Jakarta Selatan" ${data.city === 'Jakarta Selatan' ? 'selected' : ''}>Jakarta Selatan</option>
                                    <option value="Jakarta Pusat" ${data.city === 'Jakarta Pusat' ? 'selected' : ''}>Jakarta Pusat</option>
                                    <option value="Jakarta Utara" ${data.city === 'Jakarta Utara' ? 'selected' : ''}>Jakarta Utara</option>
                                    <option value="Jakarta Timur" ${data.city === 'Jakarta Timur' ? 'selected' : ''}>Jakarta Timur</option>
                                    <option value="Jakarta Barat" ${data.city === 'Jakarta Barat' ? 'selected' : ''}>Jakarta Barat</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kecamatan</label>
                                <select name="district" class="form-input">
                                    <option value="">Select District</option>
                                    <option value="Kebayoran Baru" ${data.district === 'Kebayoran Baru' ? 'selected' : ''}>Kebayoran Baru</option>
                                    <option value="Kebayoran Lama" ${data.district === 'Kebayoran Lama' ? 'selected' : ''}>Kebayoran Lama</option>
                                    <option value="Pancoran" ${data.district === 'Pancoran' ? 'selected' : ''}>Pancoran</option>
                                    <option value="Cilandak" ${data.district === 'Cilandak' ? 'selected' : ''}>Cilandak</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kelurahan</label>
                                <select name="village" class="form-input">
                                    <option value="">Select Village</option>
                                    <option value="Kramat Pela" ${data.village === 'Kramat Pela' ? 'selected' : ''}>Kramat Pela</option>
                                    <option value="Gandaria Utara" ${data.village === 'Gandaria Utara' ? 'selected' : ''}>Gandaria Utara</option>
                                    <option value="Cipete Utara" ${data.village === 'Cipete Utara' ? 'selected' : ''}>Cipete Utara</option>
                                    <option value="Pulo" ${data.village === 'Pulo' ? 'selected' : ''}>Pulo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Kode Pos</label>
                                <input type="text" name="postal_code" class="form-input" value="${data.postal_code || ''}" placeholder="Enter postal code">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Survey Results -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Survey Results</h3>
                        <div class="grid grid-cols-1 gap-6">
                            <div class="form-group">
                                <label class="form-label">Survey Result</label>
                                <textarea name="survey_result" class="form-input" rows="4" placeholder="Enter survey results and findings">${data.survey_result || ''}</textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Recommendations</label>
                                <textarea name="recommendations" class="form-input" rows="4" placeholder="Enter recommendations based on survey">${data.recommendations || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Update Survey</button>
                </div>
            `;
            openModal();
        })
        .catch(error => {
            console.error('Error loading survey data:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan saat memuat data survey.');
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

function loadSurveyorsData() {
    fetch('/marketing/surveys/surveyors', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(surveyors => {
            const marketingSelect = document.getElementById('marketingSelect');
            const surveyorSelect = document.getElementById('surveyorSelect');
            
            if (marketingSelect) {
                marketingSelect.innerHTML = '<option value="">Select Marketing</option>';
                surveyors.forEach(surveyor => {
                    const option = document.createElement('option');
                    option.value = surveyor.id;
                    option.textContent = surveyor.name;
                    marketingSelect.appendChild(option);
                });
            }
            
            if (surveyorSelect) {
                surveyorSelect.innerHTML = '<option value="">Select Surveyor</option>';
                surveyors.forEach(surveyor => {
                    const option = document.createElement('option');
                    option.value = surveyor.id;
                    option.textContent = surveyor.name;
                    surveyorSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading surveyors:', error);
            // Fallback: show hardcoded options if API fails
            const marketingSelect = document.getElementById('marketingSelect');
            const surveyorSelect = document.getElementById('surveyorSelect');
            
            if (marketingSelect) {
                marketingSelect.innerHTML = `
                    <option value="">Select Marketing</option>
                    <option value="2">Marketing 1</option>
                    <option value="14">Marketing 2</option>
                    <option value="15">Marketing 3</option>
                    <option value="16">Marketing 4</option>
                    <option value="17">Marketing 5</option>
                    <option value="18">Marketing 6</option>
                `;
            }
            
            if (surveyorSelect) {
                surveyorSelect.innerHTML = `
                    <option value="">Select Surveyor</option>
                    <option value="2">Marketing Manager</option>
                    <option value="14">Surveyor 1</option>
                    <option value="15">Surveyor 2</option>
                    <option value="16">Surveyor 3</option>
                    <option value="17">Surveyor 4</option>
                    <option value="18">Surveyor 5</option>
                `;
            }
        });
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add New Survey';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Let's add your new survey details and make sure nothing gets missed.</p>
        <form id="createForm">
            <!-- Basic Info Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Info</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">No Survey *</label>
                        <input type="text" name="survey_number" class="form-input" id="surveyNumberInput" readonly style="background-color: #f9fafb; color: #6b7280;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Survey *</label>
                        <select name="status" class="form-input" required>
                            <option value="">Select Status</option>
                            <option value="draft">Draft</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Marketing *</label>
                        <select name="marketing_id" class="form-input" required id="marketingSelect">
                            <option value="">Select Marketing</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Survey *</label>
                        <input type="date" name="survey_date" class="form-input" value="${new Date().toISOString().split('T')[0]}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Surveyor *</label>
                        <select name="surveyor_id" class="form-input" required id="surveyorSelect">
                            <option value="">Select Surveyor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Survey Location *</label>
                        <input type="text" name="survey_location" class="form-input" placeholder="Enter survey location" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Latitude</label>
                        <input type="text" name="latitude" class="form-input" placeholder="Enter latitude">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Longitude</label>
                        <input type="text" name="longitude" class="form-input" placeholder="Enter longitude">
                    </div>
                </div>
            </div>
            
            <!-- Data Company & Lokasi Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Data Company & Lokasi</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Pipeline *</label>
                        <select name="prospect_id" class="form-input" required>
                            <option value="">Select Pipeline</option>
                            @if(isset($prospects) && count($prospects) > 0)
                                @foreach($prospects as $prospect)
                                    <option value="{{ $prospect->id }}">{{ $prospect->company_name }} - {{ $prospect->contact_person }}</option>
                                @endforeach
                            @else
                                <option value="" disabled>No pipeline available</option>
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Building *</label>
                        <select name="building_id" class="form-input" id="buildingSelect" required onchange="loadFloorsForSurvey(this.value)">
                            <option value="">Select Building</option>
                            @if(isset($buildings) && count($buildings) > 0)
                                @foreach($buildings as $building)
                                    <option value="{{ $building->id }}">{{ $building->nama_gedung ?? $building->name }} - {{ $building->customer ? $building->customer->name : 'No Customer' }}</option>
                                @endforeach
                            @else
                                <option value="" disabled>No buildings available</option>
                            @endif
                        </select>
                        <small class="text-gray-500 text-xs">Select a building for this survey (required for JobSchedule creation)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Floor</label>
                        <select name="floor_id" class="form-input" id="floorSelect" onchange="loadUnitsForSurvey(this.value)">
                            <option value="">Select Floor</option>
                        </select>
                        <small class="text-gray-500 text-xs">Select a floor for this survey (optional)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit</label>
                        <select name="unit_id" class="form-input" id="unitSelect" onchange="loadRoomsForSurvey(this.value)">
                            <option value="">Select Unit (Optional)</option>
                        </select>
                        <small class="text-gray-500 text-xs">Select a unit for this survey (optional)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room</label>
                        <select name="room_id" class="form-input" id="roomSelect">
                            <option value="">Select Room (Optional)</option>
                        </select>
                        <small class="text-gray-500 text-xs">Select a room for this survey (optional)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Company *</label>
                        <input type="text" name="company_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Customer</label>
                        <select name="customer_type" class="form-input">
                            <option value="">Select Customer Type</option>
                            <option value="corporate">Corporate</option>
                            <option value="individual">Individual</option>
                            <option value="government">Government</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">PIC *</label>
                        <input type="text" name="contact_person" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone 1 *</label>
                        <input type="text" name="phone_1" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone 2</label>
                        <input type="text" name="phone_2" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jabatan/Posisi</label>
                        <input type="text" name="position" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat 1 *</label>
                        <textarea name="address_1" class="form-input form-textarea" rows="2" required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat 2</label>
                        <textarea name="address_2" class="form-input form-textarea" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Provinsi</label>
                        <select name="province_id" id="provinceSelect" class="form-input" onchange="loadCities(this.value)">
                            <option value="">Select Province</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kota/Kabupaten</label>
                        <select name="city_id" id="citySelect" class="form-input" onchange="loadDistricts(this.value)">
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kecamatan</label>
                        <select name="district_id" id="districtSelect" class="form-input" onchange="loadSubdistricts(this.value); clearPostalCode();">
                            <option value="">Select District</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kelurahan</label>
                        <select name="subdistrict_id" id="subdistrictSelect" class="form-input" onchange="loadPostalCode(this.value)">
                            <option value="">Select Subdistrict</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kode Pos</label>
                        <input type="text" name="postal_code" class="form-input" placeholder="Enter postal code" readonly>
                    </div>
                </div>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Create Survey</button>
        </div>
    `;
    openModal();
    
    // Ensure form inputs are focusable after modal opens
    setTimeout(() => {
        const formInputs = document.querySelectorAll('#createForm input, #createForm select, #createForm textarea');
        formInputs.forEach(input => {
            input.style.pointerEvents = 'auto';
            input.style.cursor = input.tagName === 'SELECT' ? 'pointer' : 'text';
            if (input.name !== 'survey_number') {
                input.removeAttribute('readonly');
            }
            input.removeAttribute('disabled');
        });
        
        // Auto-generate survey number
        const surveyNumberInput = document.getElementById('surveyNumberInput');
        if (surveyNumberInput) {
            const today = new Date();
            const dateStr = today.getFullYear() + 
                           String(today.getMonth() + 1).padStart(2, '0') + 
                           String(today.getDate()).padStart(2, '0');
            const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            const surveyNumber = 'SUR-' + dateStr + '-' + randomNum;
            surveyNumberInput.value = surveyNumber;
        }
        
        // Load surveyors data
        loadSurveyorsData();
        
        // Add prospect auto-fill functionality
        const prospectSelect = document.querySelector('select[name="prospect_id"]');
        if (prospectSelect) {
            prospectSelect.addEventListener('change', function(e) {
                const selectedProspectId = e.target.value;
                if (selectedProspectId) {
                    // Fetch prospect data and auto-fill form
                    fetch(`/marketing/prospects/${selectedProspectId}`)
                        .then(response => response.json())
                        .then(prospect => {
                            // Auto-fill form fields from prospect data
                            const companyNameInput = document.querySelector('input[name="company_name"]');
                            const contactPersonInput = document.querySelector('input[name="contact_person"]');
                            const emailInput = document.querySelector('input[name="email"]');
                            const phone1Input = document.querySelector('input[name="phone_1"]');
                            const address1Input = document.querySelector('textarea[name="address_1"]');
                            
                            if (companyNameInput) companyNameInput.value = prospect.company_name || '';
                            if (contactPersonInput) contactPersonInput.value = prospect.contact_person || '';
                            if (emailInput) emailInput.value = prospect.contact_email || '';
                            if (phone1Input) phone1Input.value = prospect.contact_phone || '';
                            if (address1Input) address1Input.value = prospect.company_address || '';
                        })
                        .catch(error => {
                            console.error('Error fetching prospect data:', error);
                        });
                }
            });
        }
        
        // Load provinces
        loadProvinces();
    }, 100);
}

// Location loading functions for surveys
function loadProvinces() {
    fetch('/api/v1/location/provinces')
        .then(response => response.json())
        .then(data => {
            const provinceSelect = document.getElementById('provinceSelect');
            if (provinceSelect) {
                provinceSelect.innerHTML = '<option value="">Select Province</option>';
                const provinces = Array.isArray(data) ? data : (data.data || []);
                if (provinces.length > 0) {
                    provinces.forEach(province => {
                        const option = document.createElement('option');
                        option.value = province.id;
                        option.textContent = province.name;
                        provinceSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading provinces:', error);
        });
}

function loadCities(provinceId) {
    if (!provinceId) return;
    
    fetch(`/api/v1/location/cities?province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            const citySelect = document.getElementById('citySelect');
            if (citySelect) {
                citySelect.innerHTML = '<option value="">Select City</option>';
                const cities = Array.isArray(data) ? data : (data.data || []);
                if (cities.length > 0) {
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.name;
                        citySelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading cities:', error);
        });
}

function loadDistricts(cityId) {
    if (!cityId) return;
    
    fetch(`/api/v1/location/districts?city_id=${cityId}`)
        .then(response => response.json())
        .then(data => {
            const districtSelect = document.getElementById('districtSelect');
            if (districtSelect) {
                districtSelect.innerHTML = '<option value="">Select District</option>';
                const districts = Array.isArray(data) ? data : (data.data || []);
                if (districts.length > 0) {
                    districts.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district.id;
                        option.textContent = district.name;
                        districtSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading districts:', error);
        });
}

function loadSubdistricts(districtId) {
    if (!districtId) return;
    
    fetch(`/api/v1/location/subdistricts?district_id=${districtId}`)
        .then(response => response.json())
        .then(data => {
            const subdistrictSelect = document.getElementById('subdistrictSelect');
            if (subdistrictSelect) {
                subdistrictSelect.innerHTML = '<option value="">Select Subdistrict</option>';
                const subdistricts = Array.isArray(data) ? data : (data.data || []);
                if (subdistricts.length > 0) {
                    subdistricts.forEach(subdistrict => {
                        const option = document.createElement('option');
                        option.value = subdistrict.id;
                        option.textContent = subdistrict.name;
                        if (subdistrict.postal_code) {
                            option.setAttribute('data-postal-code', subdistrict.postal_code);
                        }
                        subdistrictSelect.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading subdistricts:', error);
        });
}

function loadPostalCode(subdistrictId) {
    if (!subdistrictId) return;
    
    // Get postal code from the selected option
    const subdistrictSelect = document.getElementById('subdistrictSelect');
    if (subdistrictSelect) {
        const selectedOption = subdistrictSelect.options[subdistrictSelect.selectedIndex];
        const postalCode = selectedOption.getAttribute('data-postal-code');
        
        const postalCodeInput = document.querySelector('input[name="postal_code"]');
        if (postalCodeInput && postalCode) {
            postalCodeInput.value = postalCode;
        }
    }
}

function clearPostalCode() {
    const postalCodeInput = document.querySelector('input[name="postal_code"]');
    if (postalCodeInput) {
        postalCodeInput.value = '';
    }
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
    
    fetch('/marketing/surveys', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        if (!response.ok) {
            // Try to get error details
            return response.json().then(errorData => {
                console.log('Error response:', errorData);
                throw new Error(JSON.stringify(errorData));
            });
        }
        
        // Try to parse JSON response
        return response.json().catch(parseError => {
            console.log('JSON parse error:', parseError);
            console.log('Response text:', response.text());
            throw new Error('Invalid JSON response from server');
        });
    })
    .then(data => {
        console.log('Success response:', data);
        console.log('Data success value:', data.success);
        console.log('Data status value:', data.status);
        
        if (data.success === true || data.status === 'success' || data.success === 'true') {
            console.log('Showing success modal');
            closeModal();
            showSuccessModal(1, 'created');
        } else {
            console.log('Showing error - success was false or undefined');
            closeModal();
            // Show validation errors if available
            if (data.errors) {
                console.log('Validation errors:', data.errors);
                let errorMessage = data.message || 'Silakan perbaiki kesalahan berikut:\n\n';
                if (Array.isArray(data.errors)) {
                    errorMessage += data.errors.join('\n');
                } else {
                    errorMessage += JSON.stringify(data.errors);
                }
                showErrorDialog('Gagal', errorMessage);
            } else {
                showErrorDialog('Gagal', data.message || 'Survey tidak berhasil dibuat.');
            }
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
                    let errorMessage = errorData.message || 'Silakan perbaiki kesalahan berikut:\n\n';
                    if (Array.isArray(errorData.errors)) {
                        errorMessage += errorData.errors.join('\n');
                    } else {
                        errorMessage += JSON.stringify(errorData.errors);
                    }
                    showErrorDialog('Gagal', errorMessage);
                } else {
                    showErrorDialog('Gagal', errorData.message || 'Survey tidak berhasil dibuat.');
                }
            } catch (e) {
                showErrorDialog('Gagal', error.message || 'Survey tidak berhasil dibuat.');
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
    
    fetch(`/marketing/surveys/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        if (!response.ok) {
            // Try to get error details
            return response.json().then(errorData => {
                console.log('Error response:', errorData);
                throw new Error(JSON.stringify(errorData));
            });
        }
        
        // Try to parse JSON response
        return response.json().catch(parseError => {
            console.log('JSON parse error:', parseError);
            console.log('Response text:', response.text());
            throw new Error('Invalid JSON response from server');
        });
    })
    .then(data => {
        if (data.success || data.status === 'success') {
            closeModal();
            showSuccessModal(1, 'updated');
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
            showErrorDialog('Gagal', 'Survey tidak berhasil diperbarui.');
        }
    });
}

function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Perhatian', 'Silakan pilih minimal satu data yang ingin dibatalkan.');
        return;
    }
    
    // Update modal title with count
    const count = checkboxes.length;
    document.getElementById('deleteModalTitle').textContent = `Batalkan ${count} Survey?`;
    
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



// Connection Error Modal Functions
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


// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;
let lastAction = null; // Store the last action for retry functionality

// Global functions that need to be accessible from onclick
function confirmDelete() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    let selectedIds = [];

    // Use selectedIdsForRetry if checkboxes are empty (retry case)
    if (checkboxes.length === 0 && selectedIdsForRetry.length > 0) {
        selectedIds = selectedIdsForRetry;
    } else {
        selectedIds = Array.from(checkboxes).map(checkbox => {
            return parseInt(checkbox.value); // Convert to integer
        });
    }
    
    if (selectedIds.length === 0) {
        showWarningDialog('Perhatian', 'Tidak ada data yang dipilih.');
        return;
    }
    
    // Store IDs for potential retry
    selectedIdsForRetry = selectedIds;
    
    // Show loading state
    const hideButton = document.querySelector('.btn-hide');
    const originalText = hideButton.textContent;
    hideButton.textContent = 'Membatalkan...';
    hideButton.disabled = true;
    
    // Send delete request
    fetch('/marketing/surveys/bulk-delete', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json',
             'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ ids: selectedIds })
    })
    .then(async response => {
        const isJson = response.headers.get('content-type')?.includes('application/json');
        const data = isJson ? await response.json() : null;
        
        if (!response.ok) {
            const errorMessage = data?.message || `Error ${response.status}: ${response.statusText}`;
            const errors = data?.errors || [];
            throw { message: errorMessage, errors: errors };
        }
        
        return data;   
    })
    .then(data => {
        console.log('Success response:', data);
        if (data.status === 'success' || data.success === true) {
            closeDeleteModal();
            showSuccessModal(selectedIds.length);
        } else {
            closeDeleteModal();
            // Handle partial success or explicit failure in success response (200 OK but status error)
            const errorMessage = data.message || 'Error deleting surveys';
            const errors = data.errors || [];
            showErrorModal(errorMessage, errors);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        closeDeleteModal();
        
        let errorMessage = error.message || 'Unknown error';
        let errors = error.errors || [];
        
        // Handle case where error is a JSON string (from previous implementation logic)
        if (typeof error.message === 'string' && error.message.startsWith('{')) {
             try {
                 const parsed = JSON.parse(error.message);
                 errorMessage = parsed.message || errorMessage;
                 errors = parsed.errors || errors;
             } catch(e) {}
        }

        showErrorModal(errorMessage, errors);
    })
    .finally(() => {
        hideButton.textContent = originalText;
        hideButton.disabled = false;
    });
}

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {

// Enhanced table scroll functionality
function initTableScroll() {
    const tableContainer = document.querySelector('.table-container');
    if (!tableContainer) return;
    
    // Add scroll event listener to show/hide scroll indicator
    tableContainer.addEventListener('scroll', function() {
        const scrollLeft = this.scrollLeft;
        const scrollWidth = this.scrollWidth;
        const clientWidth = this.clientWidth;
        
        // Show scroll indicator when there's more content to scroll
        if (scrollWidth > clientWidth) {
            this.style.setProperty('--scroll-indicator-opacity', '1');
        } else {
            this.style.setProperty('--scroll-indicator-opacity', '0');
        }
    });
    
    // Initial check
    const scrollWidth = tableContainer.scrollWidth;
    const clientWidth = tableContainer.clientWidth;
    if (scrollWidth > clientWidth) {
        tableContainer.style.setProperty('--scroll-indicator-opacity', '1');
    }
}

// Initialize table scroll functionality
initTableScroll();

// Select All functionality
const selectAllElement = document.getElementById('selectAll');
const headerSelectAllElement = document.getElementById('headerSelectAll');

if (selectAllElement) {
    selectAllElement.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        if (headerSelectAllElement) {
            headerSelectAllElement.checked = this.checked;
        }
    });
}

if (headerSelectAllElement) {
    headerSelectAllElement.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        if (selectAllElement) {
            selectAllElement.checked = this.checked;
        }
    });
}

// Individual checkbox functionality
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('row-checkbox')) {
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

// Modal functions
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


function openEditModal(id) {
    fetch(`/marketing/surveys/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Survey';
            document.getElementById('modalBody').innerHTML = `
                <form id="surveyForm" class="space-y-6">
                    <input type="hidden" name="id" value="${data.id}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Survey Number *</label>
                            <input type="text" name="survey_number" class="form-input" value="${data.survey_number || ''}" readonly style="background-color: #f9fafb; color: #6b7280;" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Survey Date *</label>
                            <input type="date" name="survey_date" class="form-input" value="${data.survey_date ? data.survey_date.substring(0, 10) : ''}" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Building *</label>
                            <select name="building_id" class="form-input" id="editBuildingSelect" required>
                                <option value="">Select Building</option>
                            </select>
                            <small class="text-gray-500 text-xs">Select a building for this survey (required for JobSchedule creation)</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company Name *</label>
                            <input type="text" name="company_name" class="form-input" value="${data.company_name || ''}" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Contact Person *</label>
                            <input type="text" name="contact_person" class="form-input" value="${data.contact_person || ''}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number *</label>
                            <input type="text" name="phone" class="form-input" value="${data.phone || ''}" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-input" value="${data.email || ''}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone 2</label>
                            <input type="text" name="phone_2" class="form-input" value="${data.phone_2 || ''}">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group">
                            <label class="form-label">Building Type</label>
                            <select name="building_type" class="form-input">
                                <option value="">Select Building Type</option>
                                <option value="Office" ${data.building_type === 'Office' ? 'selected' : ''}>Office</option>
                                <option value="Hotel" ${data.building_type === 'Hotel' ? 'selected' : ''}>Hotel</option>
                                <option value="Restaurant" ${data.building_type === 'Restaurant' ? 'selected' : ''}>Restaurant</option>
                                <option value="Mall" ${data.building_type === 'Mall' ? 'selected' : ''}>Mall</option>
                                <option value="Hospital" ${data.building_type === 'Hospital' ? 'selected' : ''}>Hospital</option>
                                <option value="School" ${data.building_type === 'School' ? 'selected' : ''}>School</option>
                                <option value="Factory" ${data.building_type === 'Factory' ? 'selected' : ''}>Factory</option>
                                <option value="Other" ${data.building_type === 'Other' ? 'selected' : ''}>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Survey Type *</label>
                            <select name="survey_type" class="form-input" required>
                                <option value="">Select Survey Type</option>
                                <option value="initial" ${data.survey_type === 'initial' ? 'selected' : ''}>Initial Survey</option>
                                <option value="follow_up" ${data.survey_type === 'follow_up' ? 'selected' : ''}>Follow Up Survey</option>
                                <option value="final" ${data.survey_type === 'final' ? 'selected' : ''}>Final Survey</option>
                                <option value="maintenance" ${data.survey_type === 'maintenance' ? 'selected' : ''}>Maintenance Survey</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-textarea" rows="3">${data.address || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <option value="draft" ${data.status === 'draft' ? 'selected' : ''}>Draft</option>
                            <option value="in_progress" ${data.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                            <option value="completed" ${data.status === 'completed' ? 'selected' : ''}>Completed</option>
                            <option value="cancelled" ${data.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-textarea" rows="4">${data.notes || ''}</textarea>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="submitForm()">Update Survey</button>
            `;
            openModal();
            
            // Load all buildings for edit modal
            loadAllBuildingsForEdit(data.building_id);
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan saat memuat data survey.');
        });
}

function submitForm() {
    const form = document.getElementById('surveyForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    const url = data.id ? `/marketing/surveys/${data.id}` : '/marketing/surveys';
    const method = data.id ? 'PUT' : 'POST';
    
    // Show loading state
    const submitButton = document.querySelector('.btn-primary');
    const originalText = submitButton.textContent;
    submitButton.textContent = data.id ? 'Memperbarui...' : 'Membuat...';
    submitButton.disabled = true;
    
    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success || data.status === 'success') {
            closeModal();
            showSuccessModal(1, data.id ? 'updated' : 'created');
        } else {
            showErrorDialog('Gagal', data.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorDialog('Gagal', 'Form tidak berhasil dikirim.');
    })
    .finally(() => {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    });
}


function openDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}



// Error modal functions
function showErrorModal(message = null, errors = null) {
    const errorModal = document.getElementById('errorModalOverlay');
    const descriptionElement = errorModal.querySelector('.error-modal-description');
    
    // Reset to default if no message provided, or set custom message
    if (message) {
        descriptionElement.textContent = message;
    } else {
        descriptionElement.textContent = 'Data tidak berhasil diproses saat ini. Silakan periksa koneksi Anda dan coba lagi.';
    }
    
    // Remove existing error list if any
    const existingList = errorModal.querySelector('.error-list');
    if (existingList) {
        existingList.remove();
    }
    
    // If detailed errors provided, append them
    if (errors && Array.isArray(errors) && errors.length > 0) {
        const list = document.createElement('ul');
        list.className = 'error-list';
        list.style.textAlign = 'left';
        list.style.marginTop = '10px';
        list.style.fontSize = '14px';
        list.style.listStyleType = 'disc';
        list.style.paddingLeft = '20px';
        list.style.color = '#ef4444';
        
        errors.forEach(err => {
            const item = document.createElement('li');
            item.textContent = err;
            list.appendChild(item);
        });
        
        descriptionElement.after(list);
    }
    
    errorModal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retryDelete() {
    closeErrorModal();
    
    if (selectedIdsForRetry.length === 0) {
        showWarningDialog('Perhatian', 'Tidak ada data untuk dicoba ulang.');
        return;
    }
    
    // Show loading state
    const hideButton = document.querySelector('.btn-hide');
    const originalText = hideButton.textContent;
    hideButton.textContent = 'Menyembunyikan...';
    hideButton.disabled = true;
    
    // Retry delete logic
    fetch('/marketing/surveys/bulk-delete', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal(selectedIdsForRetry.length);
        } else {
            showErrorModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal();
    })
    .finally(() => {
        hideButton.textContent = originalText;
        hideButton.disabled = false;
    });
}

    // Close modals on escape key
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

    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeModal();
        }
        if (e.target.classList.contains('delete-modal-overlay')) {
            closeDeleteModal();
        }
        if (e.target.classList.contains('error-modal-overlay')) {
            closeErrorModal();
        }
        if (e.target.classList.contains('success-modal-overlay')) {
            closeSuccessModal();
        }
        if (e.target.classList.contains('connection-error-modal-overlay')) {
            closeConnectionErrorModal();
        }
        if (e.target.classList.contains('update-error-modal-overlay')) {
            closeUpdateErrorModal();
        }
    });

}); // End of DOMContentLoaded event listener

// Success modal functions (moved outside DOMContentLoaded for global access)
function showSuccessModal(count, action = 'hidden') {
    const successModal = document.getElementById('successModalOverlay');
    const titleElement = successModal.querySelector('.success-modal-title');
    const descriptionElement = document.getElementById('successModalDescription');
    
    let title = '';
    let message = '';
    
    if (action === 'hidden') {
        title = 'Data Berhasil Disembunyikan';
        message = `${count} data berhasil disembunyikan. Datanya tetap aman dan masih tersimpan di database.`;
    } else if (action === 'created') {
        title = 'Survey Berhasil Ditambahkan';
        message = 'Survey berhasil disimpan dan siap diproses lebih lanjut.';
    } else if (action === 'updated') {
        title = 'Survey Berhasil Diperbarui';
        message = 'Detail survey berhasil diperbarui dan sudah tersimpan.';
    } else {
        title = 'Berhasil';
        message = 'Operasi berhasil diselesaikan.';
    }
    
    titleElement.textContent = title;
    descriptionElement.textContent = message;
    
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Auto close after 3 seconds
    successModalTimer = setTimeout(() => {
        closeSuccessModal();
        location.reload();
    }, 3000);
}

function closeSuccessModal() {
    document.getElementById('successModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
    
    if (successModalTimer) {
        clearTimeout(successModalTimer);
        successModalTimer = null;
    }
}

// Add missing navigateTo function for sidebar navigation
function navigateTo(url) {
    window.location.href = url;
}

// Load buildings by customer (deprecated - now showing all buildings)
function loadBuildingsByCustomer(prospectId) {
    // This function is no longer needed as we show all buildings in create modal
    // Buildings are populated from server-side data
    console.log('loadBuildingsByCustomer called but not needed for create modal');
}

// Load all buildings for edit modal
function loadAllBuildingsForEdit(selectedBuildingId = null) {
    const buildingSelect = document.getElementById('editBuildingSelect');
    if (!buildingSelect) return;
    
    // Clear existing options
    buildingSelect.innerHTML = '<option value="">Select Building</option>';
    
    // Use buildings data from server-side
    @if(isset($buildings) && count($buildings) > 0)
        @foreach($buildings as $building)
            const option{{ $building->id }} = document.createElement('option');
            option{{ $building->id }}.value = '{{ $building->id }}';
            option{{ $building->id }}.textContent = '{{ $building->nama_gedung ?? $building->name }} - {{ $building->customer->name ?? "Unknown Customer" }}';
            @if($building->id == (isset($data) ? $data->building_id : null))
                option{{ $building->id }}.selected = true;
            @endif
            buildingSelect.appendChild(option{{ $building->id }});
        @endforeach
    @endif
    
    // Set selected building if provided
    if (selectedBuildingId) {
        buildingSelect.value = selectedBuildingId;
    }
}

// Load buildings for edit modal (legacy function for customer-specific)
function loadBuildingsForEdit(customerId, selectedBuildingId = null) {
    const buildingSelect = document.getElementById('editBuildingSelect');
    if (!buildingSelect) return;
    
    // Clear existing options
    buildingSelect.innerHTML = '<option value="">Select Building</option>';
    
    if (!customerId) {
        return;
    }
    
    // Fetch buildings for this customer
    fetch(`/marketing/surveys/buildings/${customerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data.length > 0) {
                data.data.forEach(building => {
                    const option = document.createElement('option');
                    option.value = building.id;
                    option.textContent = building.nama_gedung || building.name || `Building ${building.id}`;
                    if (selectedBuildingId && building.id == selectedBuildingId) {
                        option.selected = true;
                    }
                    buildingSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading buildings:', error);
        });
}

// Load floors for Survey
function loadFloorsForSurvey(buildingId, selectedFloorId = null) {
    const floorSelect = document.getElementById('floorSelect');
    const unitSelect = document.getElementById('unitSelect');
    const roomSelect = document.getElementById('roomSelect');
    
    // Reset dependent dropdowns
    if (floorSelect) floorSelect.innerHTML = '<option value="">Select Floor</option>';
    if (unitSelect) unitSelect.innerHTML = '<option value="">Select Unit (Optional)</option>';
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room (Optional)</option>';
    
    if (!buildingId) return;
    
    fetch(`/api/buildings/${buildingId}/floors`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && floorSelect) {
                data.data.forEach(floor => {
                    const option = document.createElement('option');
                    option.value = floor.id;
                    option.textContent = `Floor ${floor.floor_number} - ${floor.floor_name || 'No Name'}`;
                    if (selectedFloorId && floor.id == selectedFloorId) {
                        option.selected = true;
                    }
                    floorSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading floors:', error));
}

// Load units for Survey
function loadUnitsForSurvey(floorId, selectedUnitId = null) {
    const unitSelect = document.getElementById('unitSelect');
    const roomSelect = document.getElementById('roomSelect');
    
    // Reset dependent dropdowns
    if (unitSelect) unitSelect.innerHTML = '<option value="">Select Unit (Optional)</option>';
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room (Optional)</option>';
    
    if (!floorId) return;
    
    fetch(`/api/floors/${floorId}/units`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && unitSelect) {
                data.data.forEach(unit => {
                    const option = document.createElement('option');
                    option.value = unit.id;
                    option.textContent = `Unit ${unit.unit_number} - ${unit.unit_name}`;
                    if (selectedUnitId && unit.id == selectedUnitId) {
                        option.selected = true;
                    }
                    unitSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading units:', error));
}

// Load rooms for Survey
function loadRoomsForSurvey(unitId, selectedRoomId = null) {
    const roomSelect = document.getElementById('roomSelect');
    
    // Reset room dropdown
    if (roomSelect) roomSelect.innerHTML = '<option value="">Select Room (Optional)</option>';
    
    if (!unitId) return;
    
    fetch(`/api/units/${unitId}/rooms`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && roomSelect) {
                data.data.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = `${room.room_name} - ${room.room_code || ''}`;
                    if (selectedRoomId && room.id == selectedRoomId) {
                        option.selected = true;
                    }
                    roomSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading rooms:', error));
}
</script>
@endsection
