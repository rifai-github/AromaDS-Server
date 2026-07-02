@extends('layouts.app')

@section('title', 'Quotation')
@section('breadcrumb', 'Home / Marketing / Quotation')

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
        min-width: 1600px;
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
        margin: 0;
        padding: 0;
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
        overflow: visible;
        text-overflow: unset;
    }
    
    .responsive-table td {
        overflow: hidden;
        text-overflow: ellipsis;
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
    
    /* Copy button should be clickable above the row */
    .copy-quotation-btn {
        position: relative;
        z-index: 20;
        pointer-events: auto !important;
        cursor: pointer !important;
    }
    
    .copy-quotation-btn:hover {
        background-color: #1e3a8a !important;
        transform: scale(1.05);
    }
    
    /* Column width adjustments for better header display */
    .responsive-table th:nth-child(1),
    .responsive-table td:nth-child(1) {
        width: 50px;
        min-width: 50px;
        max-width: 50px;
    }
    
    .responsive-table th:nth-child(2),
    .responsive-table td:nth-child(2) {
        width: 150px;
        min-width: 150px;
    }
    
    .responsive-table th:nth-child(3),
    .responsive-table td:nth-child(3) {
        width: 120px;
        min-width: 120px;
    }
    
    .responsive-table th:nth-child(4),
    .responsive-table td:nth-child(4) {
        width: 200px;
        min-width: 200px;
    }
    
    .responsive-table th:nth-child(5),
    .responsive-table td:nth-child(5) {
        width: 120px;
        min-width: 120px;
    }
    
    .responsive-table th:nth-child(6),
    .responsive-table td:nth-child(6) {
        width: 120px;
        min-width: 120px;
    }
    
    .responsive-table th:nth-child(7),
    .responsive-table td:nth-child(7) {
        width: 100px;
        min-width: 100px;
    }
    
    .responsive-table th:nth-child(8),
    .responsive-table td:nth-child(8) {
        width: 100px;
        min-width: 100px;
    }

    .responsive-table th:nth-child(9),
    .responsive-table td:nth-child(9) {
        width: 120px;
        min-width: 120px;
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
        width: 120px;
        min-width: 120px;
    }
    
    .responsive-table th:nth-child(13),
    .responsive-table td:nth-child(13) {
        width: 120px;
        min-width: 120px;
    }
    
    .responsive-table th:nth-child(14),
    .responsive-table td:nth-child(14) {
        width: 200px;
        min-width: 200px;
    }
    
    .responsive-table th:nth-child(15),
    .responsive-table td:nth-child(15) {
        width: 200px;
        min-width: 200px;
    }
    
    .responsive-table th:nth-child(16),
    .responsive-table td:nth-child(16) {
        width: 150px;
        min-width: 150px;
    }
    
    .responsive-table th:nth-child(17),
    .responsive-table td:nth-child(17) {
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
            flex-wrap: nowrap;
            gap: 5px;
            max-width: 100%;
            overflow-x: auto;
        }
        
        /* Modal responsive */
        .modal-container {
            width: 95vw;
            max-height: 95vh;
            margin: 10px;
        }
        
        .modal-header {
            padding: 20px;
        }
        
        .modal-body {
            padding: 20px;
            max-height: calc(95vh - 140px);
        }
        
        .modal-footer {
            padding: 20px;
            flex-direction: column;
        }
        
        .modal-footer .btn {
            width: 100%;
            justify-content: center;
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
        max-width: 85vw;
        max-height: 85vh;
        width: 600px;
        overflow: hidden;
        position: relative;
        margin: 20px;
        pointer-events: auto !important;
        z-index: 1001 !important;
    }
    
    .modal-header {
        background: #214589;
        color: white;
        padding: 24px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 20;
    }
    
    .modal-title {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
        color: white;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
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
        padding: 30px !important;
        overflow-y: auto;
        max-height: calc(85vh - 180px);
        pointer-events: auto !important;
    }
    
    .modal-body * {
        pointer-events: auto !important;
    }
    
    .modal-body input, .modal-body select, .modal-body textarea {
        pointer-events: auto !important;
        user-select: text !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
        cursor: text !important;
        position: relative !important;
        z-index: 10 !important;
        background-color: white !important;
        border: 1px solid #d1d5db !important;
    }
    
    .modal-body select {
        cursor: pointer !important;
    }
    
    .modal-body input:focus, .modal-body select:focus, .modal-body textarea:focus {
        outline: none !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }

    
    
    /* Custom scrollbar for modal */
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
    
    .modal-footer {
        padding: 24px 30px;
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
        margin-bottom: 24px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
    }
    
    .form-input {
        width: 100%;
        padding: 12px 16px;
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
    
    .form-input, .form-select, .form-textarea {
        pointer-events: auto !important;
        user-select: text !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
        cursor: text !important;
        position: relative !important;
        z-index: 10 !important;
        background-color: white !important;
        border: 1px solid #d1d5db !important;
    }
    
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }
    
    .form-select {
        cursor: pointer !important;
    }
    
    input, select, textarea {
        pointer-events: auto !important;
        user-select: text !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
        cursor: text !important;
        position: relative !important;
        z-index: 10 !important;
        background-color: white !important;
        border: 1px solid #d1d5db !important;
    }
    
    select {
        cursor: pointer !important;
    }
    
    input:focus, select:focus, textarea:focus {
        outline: none !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }
    
    .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
        box-sizing: border-box;
        background-color: white;
    }
    
    .form-select:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }
    
    .form-textarea {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
        box-sizing: border-box;
        min-height: 100px;
        resize: vertical;
    }
    
    .form-textarea:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }
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
    
    .gap-4 {
        gap: 1rem;
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
        background-color: #1e40af;
        color: white;
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
        background-color: #1e40af;
        color: white;
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

    /* Mobile Modal Adjustments */
    @media (max-width: 768px) {
        .delete-modal-container,
        .error-modal-container,
        .success-modal-container {
            width: 95vw;
            margin: 20px;
        }
        
        .delete-modal-container,
        .error-modal-container,
        .success-modal-container {
            padding: 30px 20px 20px;
        }
        
        .delete-icon,
        .error-icon,
        .success-icon {
            width: 60px;
            height: 60px;
        }
        
        .delete-modal-title,
        .error-modal-title,
        .success-modal-title {
            font-size: 20px;
        }
        
        .delete-modal-description,
        .error-modal-description,
        .success-modal-description {
            font-size: 14px;
        }
        
        .delete-modal-buttons,
        .error-modal-buttons {
            flex-direction: column;
            gap: 12px;
        }
        
        .btn-cancel,
        .btn-hide,
        .btn-error-close,
        .btn-error-retry {
            width: 100%;
            padding: 14px 24px;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Quotation Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Quotation</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <x-filter-status />
                <a href="{{ route('marketing.quotations.wizard.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    <span>Add New Quote</span>
                </a>
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
                    <i class="fas fa-ban"></i>
                    <span>Cancel</span>
                </button>
            </div>
        </div>
        
        <!-- Table Container with Horizontal Scroll -->
        <div class="table-container">
            <table class="responsive-table" id="quotationsTable">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th data-column="quotation_number">Quotation Number</th>
                        <th data-column="quotation_date" data-type="date">Quotation Date</th>
                        <th data-column="company_name">Company Name</th>
                        <th data-column="valid_until" data-type="date">Valid Until</th>
                        <th data-column="grand_total" data-type="numeric">Total Amount</th>
                        <th data-column="status">Status</th>
                        <th data-column="quotation_type">Jenis SQ</th>
                        <th data-column="branch.name" data-relation="branch">Branch</th>
                        <th data-column="rental_period">Rental Period</th>
                        <th data-column="terms_of_payment">Terms of Payment</th>
                        <th data-column="marketing.name" data-relation="marketing">Marketing Name</th>
                        <th data-column="approver.name" data-relation="approver">Approved By</th>
                        <th data-column="date_approved" data-type="date">Date Approved</th>
                        <th data-column="internal_notes">Internal Notes</th>
                        <th data-column="additional_notes">Additional Notes</th>
                        <th data-column="survey.survey_location">Survey Location</th>
                        <th data-column="goal_sq">Goal SQ</th>
                        <th data-column="creator.name" data-relation="creator">Created By</th>
                        <th data-column="created_at" data-type="date">Created At</th>
                        <th data-column="updater.name" data-relation="updater">Last Updated By</th>
                        <th data-column="quotations.updated_at" data-type="date">Last Updated At</th>
                        <th data-column="revision_number" data-type="numeric">Revisi</th>
                        <th data-no-filter>Aksi</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($quotations ?? [] as $quotation)
                    <tr class="table-row-hover cursor-pointer border-b border-gray-200" data-id="{{ $quotation->id }}" onclick="openQuotationDetail({{ $quotation->id }})">
                        <td class="w-[50px] p-2 text-center">
                            <input type="checkbox" class="row-checkbox w-[10px] h-[10px] md:w-[15px] md:h-[15px] lg:w-[20px] lg:h-[20px] bg-white border border-[#888888] rounded-[4px] cursor-pointer" value="{{ $quotation->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td class="w-[150px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->quotation_number ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[120px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->quotation_date ? \Carbon\Carbon::parse($quotation->quotation_date)->format('d/M/Y') : 'N/A' }}</p>
                        </td>
                        <td class="w-[200px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->customer->name ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[120px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d/M/Y') : 'N/A' }}</p>
                        </td>
                        <td class="w-[120px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->formatted_grand_total ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[100px] p-2">
                            @php
                                $statusColors = [
                                    'approved' => 'background-color: #dcfce7; color: #166534;',
                                    'accepted' => 'background-color: #dcfce7; color: #166534;',
                                    'rejected' => 'background-color: #fef2f2; color: #991b1b;',
                                    'cancelled' => 'background-color: #fef2f2; color: #991b1b;',
                                    'contract' => 'background-color: #dbeafe; color: #1e40af;',
                                    'sent' => 'background-color: #dbeafe; color: #1e40af;',
                                    'expired' => 'background-color: #f3f4f6; color: #374151;',
                                    'waiting_for_approval' => 'background-color: #ffedd5; color: #c2410c;',
                                    'pending' => 'background-color: #ffedd5; color: #c2410c;',
                                    'draft' => 'background-color: #e5e7eb; color: #4b5563;',
                                ];
                                $style = $statusColors[$quotation->status] ?? 'background-color: #f3f4f6; color: #374151;';
                            @endphp
                            <span style="padding: 4px 8px; font-size: 12px; border-radius: 9999px; {{ $style }}">
                                @if($quotation->status === 'waiting_for_approval')
                                    Waiting For Approval
                                @elseif($quotation->status === 'draft')
                                    Draft
                                @elseif($quotation->status === 'approved')
                                    Approved
                                @elseif($quotation->status === 'rejected')
                                    Rejected
                                @elseif($quotation->status === 'sent')
                                    Sent
                                @elseif($quotation->status === 'accepted')
                                    Accepted
                                @elseif($quotation->status === 'expired')
                                    Expired
                                @else
                                    {{ ucfirst(str_replace('_', ' ', $quotation->status)) }}
                                @endif
                            </span>
                        </td>
                        <td class="w-[100px] p-2">
                             <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">
                                {{ ucfirst($quotation->quotation_type ?? 'New') }}
                             </p>
                        </td>
                        <td class="w-[100px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">
                                {{ $quotation->branch->name ?? '-' }}
                            </p>
                        </td>
                        <td class="w-[120px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->rental_period ? $quotation->rental_period . ' ' . ucfirst($quotation->rental_unit ?? '') : 'N/A' }}</p>
                        </td>
                        <td class="w-[120px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->terms_of_payment_label }}</p>
                        </td>
                        <td class="w-[150px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->marketing->name ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[120px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->approved_by_display_name }}</p>
                        </td>
                        <td class="w-[120px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->date_approved ? \Carbon\Carbon::parse($quotation->date_approved)->format('d/M/Y') : 'N/A' }}</p>
                        </td>
                        <td class="w-[200px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->internal_notes ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[200px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->additional_notes ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[200px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->survey->survey_location ?? 'N/A' }}</p>
                        </td>
                        <td class="w-[80px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->goal_sq ? $quotation->goal_sq . '%' : '-' }}</p>
                        </td>
                        <td class="w-[120px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->creator->name ?? ($quotation->created_by ? 'User #' . $quotation->created_by : 'N/A') }}</p>
                        </td>
                        <td class="w-[150px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">
                                {!! $quotation->created_at ? \Carbon\Carbon::parse($quotation->created_at)->format('d/M/Y') . '<br />at ' . \Carbon\Carbon::parse($quotation->created_at)->format('H.i') . ' WIB' : 'N/A' !!}
                            </p>
                        </td>
                        <td class="w-[120px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">{{ $quotation->updater->name ?? ($quotation->updated_by ? 'User #' . $quotation->updated_by : 'N/A') }}</p>
                        </td>
                        <td class="w-[150px] p-2">
                            <p class="text-[6px] md:text-[9px] lg:text-[12px] font-inter font-normal leading-[7px] md:leading-[11px] lg:leading-[15px] text-left text-[#3d3d3d] break-words">
                                {!! $quotation->updated_at ? \Carbon\Carbon::parse($quotation->updated_at)->format('d/M/Y') . '<br />at ' . \Carbon\Carbon::parse($quotation->updated_at)->format('H.i') . ' WIB' : 'N/A' !!}
                            </p>
                        </td>
                        <td class="w-[60px] p-2 text-center">
                            <span class="text-[10px] md:text-[12px] lg:text-[14px] font-bold text-[#3d3d3d]">{{ $quotation->revision_number ?? 0 }}</span>
                        </td>
                        <td class="w-[80px] p-2 text-center">
                            @if($quotation->status === 'approved' && ($quotation->is_latest_revision ?? true))
                                <button type="button" class="btn btn-sm btn-primary" onclick="event.stopPropagation(); copyQuotation({{ $quotation->id }})" title="Copy sebagai Revisi">
                                    <i class="fas fa-copy"></i>
                                </button>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="23" class="p-8 text-center">
                            <p class="text-[14px] md:text-[16px] lg:text-[18px] font-inter font-normal text-center text-[#666]">No quotations found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($quotations->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $quotations->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Quotation</h2>
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



<script>
// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;

function formatTermsOfPaymentLabel(value) {
    return value === 'Tahunan' ? '1x Advance' : (value || 'N/A');
}

// Function to format date with 3-digit month
function formatDateWithThreeDigitMonth(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(3, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
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

    // Event listeners for keyboard
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeDeleteModal();
            closeErrorModal();
            closeSuccessModal();
        }
    });

    // Click outside to close modals
    const modalOverlay = document.getElementById('modalOverlay');
    const deleteModalOverlay = document.getElementById('deleteModalOverlay');
    const errorModalOverlay = document.getElementById('errorModalOverlay');
    const successModalOverlay = document.getElementById('successModalOverlay');
    
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    }
    
    if (deleteModalOverlay) {
        deleteModalOverlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    }
    
    if (errorModalOverlay) {
        errorModalOverlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeErrorModal();
            }
        });
    }
    
    if (successModalOverlay) {
        successModalOverlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeSuccessModal();
                location.reload();
            }
        });
    }
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
        showWarningDialog('Perhatian', 'Silakan pilih minimal satu quotation yang ingin disembunyikan.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Modal functions
function openModal(title) {
    const modalTitleElement = document.getElementById('modalTitle');
    if (modalTitleElement) {
        modalTitleElement.textContent = title;
    } else {
        console.error('Modal title element not found');
    }
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
    openModal('📝 New Quotation');
    
    // Generate quotation number immediately
    const generatedNumber = generateQuotationNumber('new');
    
    document.getElementById('modalBody').innerHTML = `
        <div class="mb-6">
            <p class="text-gray-600 text-center text-sm">Let's create a new quotation with all the necessary details.</p>
        </div>
        <div class="max-h-[70vh] overflow-y-auto pr-2">
            <form id="quotationForm" onsubmit="submitForm(event)">
            <!-- Basic Information Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Quotation Number *</label>
                        <input type="text" class="form-input" name="quotation_number" value="${generatedNumber}" readonly style="background-color: #f9fafb; color: #6b7280;">
                        <small class="text-gray-500 text-xs">This number is automatically generated</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quotation Date *</label>
                        <input type="date" class="form-input" name="quotation_date" value="${new Date().toISOString().split('T')[0]}" readonly style="background-color: #f9fafb; color: #6b7280;">
                        <small class="text-gray-500 text-xs">This date is automatically set to today's date</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valid Until *</label>
                        <input type="date" class="form-input" name="valid_until" value="${new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]}" required>
                        <small class="text-gray-500 text-xs">Quotation validity period</small>
                    </div>
                </div>
            </div>

            <!-- Company Information Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Company Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Pipeline *</label>
                        <select class="form-select" name="prospect_id" required onchange="loadProspectData(this.value)">
                            <option value="">Select Pipeline</option>
                            @foreach($prospects ?? [] as $prospect)
                                <option value="{{ $prospect->id }}">{{ $prospect->company_name }} - {{ $prospect->contact_person }}</option>
                            @endforeach
                        </select>
                        <small class="text-gray-500 text-xs">Select the pipeline this quotation is for</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Survey *</label>
                        <select class="form-select" name="survey_id" required onchange="loadSurveyDataForCreate(this.value)">
                            <option value="">Select Survey</option>
                            @foreach($surveys ?? [] as $survey)
                                <option value="{{ $survey->id }}">{{ $survey->survey_number }} - {{ $survey->surveyor->name ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                        <small class="text-gray-500 text-xs">Select the survey this quotation is based on</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Name *</label>
                        <input type="text" class="form-input" name="company_name" placeholder="Enter company name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">PIC Name *</label>
                        <input type="text" class="form-input" name="pic_name" placeholder="Enter PIC name" required>
                    </div>
                </div>
            </div>

            <!-- Quotation Details Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Quotation Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Billing Methods *</label>
                        <select class="form-select" name="billing_methods" required>
                            <option value="">Select Billing Method</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annually">Annually</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status" required>
                            <option value="">Select Status</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rental Period</label>
                        <select class="form-select" name="rental_period" required>
                            <option value="">Select Rental Period</option>
                            <option value="1 day">1 Day</option>
                            <option value="3 days">3 Days</option>
                            <option value="5 days">5 Days</option>
                            <option value="1 week">1 Week</option>
                            <option value="2 weeks">2 Weeks</option>
                            <option value="1 month">1 Month</option>
                            <option value="3 months">3 Months</option>
                            <option value="6 months">6 Months</option>
                            <option value="12 months">12 Months</option>
                            <option value="24 months">24 Months</option>
                            <option value="36 months">36 Months</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Terms of Payment</label>
                        <select class="form-select" name="terms_of_payment" required>
                            <option value="">Select Payment Terms</option>
                            <option value="cash">Cash</option>
                            <option value="credit_30">Credit 30 Days</option>
                            <option value="credit_60">Credit 60 Days</option>
                            <option value="credit_90">Credit 90 Days</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Marketing Name *</label>
                        <input type="text" name="marketing_name" class="form-input" placeholder="Will be auto-filled from survey" readonly>
                        <input type="hidden" name="marketing_id" value="">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Approved By</label>
                        <select class="form-select" name="approved_by">
                            <option value="">Select Approver</option>
                            @foreach($approvers ?? [] as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date Approved</label>
                        <input type="date" class="form-input" name="date_approved">
                    </div>
                </div>
            </div>
            
            <!-- Rental Components Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Rental Components</h3>
                <div id="componentsContainer">
                    <div class="component-item border border-gray-200 rounded-lg p-4 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="form-group">
                                <label class="form-label">Component Type *</label>
                                <select class="form-select component-type" required onchange="updateComponentPrice(this)">
                                    <option value="">Select Component</option>
                                    @foreach($masterRentals ?? [] as $rental)
                                        <option value="{{ $rental->id }}" 
                                                data-price="{{ $rental->rentalPrices->first()->monthly_price ?? 0 }}"
                                                data-name="{{ $rental->rental_name }}"
                                                data-category="{{ $rental->category }}">
                                            {{ $rental->rental_name }} ({{ ucfirst($rental->category) }})
                                        </option>
                                    @endforeach
                                    <option value="custom" data-price="0" data-name="" data-category="custom">Custom Item</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Item Name *</label>
                                <input type="text" class="form-input component-name" placeholder="Enter item name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Quantity *</label>
                                <input type="number" class="form-input component-qty" placeholder="1" min="1" value="1" required onchange="calculateComponentTotal(this)">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Unit Price *</label>
                                <input type="number" class="form-input component-price" placeholder="0" step="0.01" min="0" required onchange="calculateComponentTotal(this)">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div class="form-group">
                                <label class="form-label">Total Price</label>
                                <input type="number" class="form-input component-total" placeholder="0" step="0.01" readonly style="background-color: #f9fafb; color: #6b7280; font-weight: bold;">
                            </div>
                            <div class="form-group flex items-end">
                                <button type="button" class="btn btn-outline btn-sm text-red-600 border-red-600 hover:bg-red-600 hover:text-white" onclick="removeComponent(this)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center mt-4">
                    <button type="button" class="btn btn-outline" onclick="addComponent()">
                        <i class="fas fa-plus"></i> Add Component
                    </button>
                </div>
            </div>

            <!-- Pricing Information Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Pricing Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Subtotal *</label>
                        <input type="number" class="form-input" name="total_amount" placeholder="0" step="0.01" min="0" required readonly style="background-color: #f9fafb; color: #6b7280; font-weight: bold;">
                        <small class="text-gray-500 text-xs">Auto-calculated from components</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount Amount</label>
                        <input type="number" class="form-input" name="discount_amount" placeholder="0" step="0.01" min="0" onchange="calculateGrandTotal()">
                        <small class="text-gray-500 text-xs">Discount amount (if any)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tax Amount</label>
                        <input type="number" class="form-input" name="tax_amount" placeholder="0" step="0.01" min="0" onchange="calculateGrandTotal()">
                        <small class="text-gray-500 text-xs">Tax amount (if any)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grand Total *</label>
                        <input type="number" class="form-input" name="grand_total" placeholder="0" step="0.01" min="0" required readonly style="background-color: #f9fafb; color: #6b7280; font-weight: bold;">
                        <small class="text-gray-500 text-xs">Final amount (auto-calculated)</small>
                    </div>
                </div>
            </div>
            
            <!-- Notes Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Additional Information</h3>
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Terms & Conditions</label>
                        <textarea class="form-textarea" name="terms_conditions" placeholder="Enter terms and conditions" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Internal Notes</label>
                        <textarea class="form-textarea" name="internal_notes" placeholder="Enter internal notes for team reference" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Additional Notes</label>
                        <textarea class="form-textarea" name="additional_notes" placeholder="Enter additional notes for customer" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </form>
        </div>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitForm()">Create Quotation</button>
        </div>
    `;
    openModal();
}

function openRenewalModal() {
    openModal('🔄 SQ Renewal Quotation');
    
    // Generate renewal quotation number immediately
    const generatedRenewalNumber = generateQuotationNumber('renewal');
    
    document.getElementById('modalBody').innerHTML = `
        <div class="mb-6">
            <p class="text-gray-600 text-center text-sm">Create a renewal quotation for an existing contract.</p>
        </div>
        <form id="renewalForm" onsubmit="submitRenewalForm(event)">
            <!-- Contract Selection Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Select Existing Contract</h3>
                <div class="form-group">
                    <label class="form-label">Existing Contract</label>
                    <select class="form-select" name="existing_contract_id">
                        <option value="">New Contract (No existing contract to renew)</option>
                        <option value="1">CON-2024-001 - PT Example Company (Expires: 2024-12-31)</option>
                        <option value="2">CON-2024-002 - CV Sample Corp (Expires: 2024-11-15)</option>
                        <option value="3">CON-2024-003 - PT Demo Ltd (Expires: 2024-10-20)</option>
                    </select>
                    <small class="text-gray-500 text-xs">Select an existing contract to renew, or leave empty for new contract</small>
                </div>
            </div>

            <!-- Basic Information Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Renewal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Quotation Number *</label>
                        <input type="text" class="form-input" name="quotation_number" value="${generatedRenewalNumber}" readonly style="background-color: #f9fafb; color: #6b7280;">
                        <small class="text-gray-500 text-xs">This renewal number is automatically generated</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quotation Date *</label>
                        <input type="date" class="form-input" name="quotation_date" value="${new Date().toISOString().split('T')[0]}" readonly style="background-color: #f9fafb; color: #6b7280;">
                        <small class="text-gray-500 text-xs">This date is automatically set to today's date</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valid Until *</label>
                        <input type="date" class="form-input" name="valid_until" required>
                        <small class="text-gray-500 text-xs">Quotation validity period</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pipeline *</label>
                        <select class="form-select" name="prospect_id" required onchange="loadProspectDataForRenewal(this.value)">
                            <option value="">Select Pipeline</option>
                            @foreach($prospects ?? [] as $prospect)
                                <option value="{{ $prospect->id }}">{{ $prospect->company_name }} - {{ $prospect->contact_person }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Survey *</label>
                        <select class="form-select" name="survey_id" required onchange="loadSurveyDataForRenewal(this.value)">
                            <option value="">Select Survey</option>
                            @foreach($surveys ?? [] as $survey)
                                <option value="{{ $survey->id }}">{{ $survey->survey_number }} - {{ $survey->surveyor->name ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Name *</label>
                        <input type="text" class="form-input" name="company_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">PIC Name *</label>
                        <input type="text" class="form-input" name="pic_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Marketing Name *</label>
                        <input type="text" name="marketing_name" class="form-input" placeholder="Will be auto-filled from survey" readonly>
                        <input type="hidden" name="marketing_id" value="">
                    </div>
                </div>
            </div>

            <!-- Renewal Details Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Renewal Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">New Rental Period *</label>
                        <select class="form-select" name="rental_period" required>
                            <option value="">Select Rental Period</option>
                            <option value="1 day">1 Day</option>
                            <option value="3 days">3 Days</option>
                            <option value="5 days">5 Days</option>
                            <option value="1 week">1 Week</option>
                            <option value="2 weeks">2 Weeks</option>
                            <option value="1 month">1 Month</option>
                            <option value="3 months">3 Months</option>
                            <option value="6 months">6 Months</option>
                            <option value="12 months">12 Months</option>
                            <option value="24 months">24 Months</option>
                            <option value="36 months">36 Months</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price Adjustment (%)</label>
                        <input type="number" class="form-input" name="price_adjustment" placeholder="e.g., 5 (for 5% increase)" step="0.1">
                        <small class="text-gray-500 text-xs">Leave empty for no price change</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Billing Methods *</label>
                        <select class="form-select" name="billing_methods" required>
                            <option value="">Select Billing Method</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annually">Annually</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status" required>
                            <option value="">Select Status</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="approved">Approved</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Rental Components Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Rental Components</h3>
                <div id="renewalComponentsContainer">
                    <div class="component-item border border-gray-200 rounded-lg p-4 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="form-group">
                                <label class="form-label">Component Type *</label>
                                <select class="form-select component-type" required onchange="updateComponentPriceForRenewal(this)">
                                    <option value="">Select Component</option>
                                    @foreach($masterRentals ?? [] as $rental)
                                        <option value="{{ $rental->id }}" 
                                                data-price="{{ $rental->rentalPrices->first()->monthly_price ?? 0 }}"
                                                data-name="{{ $rental->rental_name }}"
                                                data-category="{{ $rental->category }}">
                                            {{ $rental->rental_name }} ({{ ucfirst($rental->category) }})
                                        </option>
                                    @endforeach
                                    <option value="custom" data-price="0" data-name="" data-category="custom">Custom Item</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Item Name *</label>
                                <input type="text" class="form-input component-name" placeholder="Enter item name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Quantity *</label>
                                <input type="number" class="form-input component-qty" placeholder="1" min="1" value="1" required onchange="calculateComponentTotalForRenewal(this)">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Unit Price *</label>
                                <input type="number" class="form-input component-price" placeholder="0" step="0.01" min="0" required onchange="calculateComponentTotalForRenewal(this)">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div class="form-group">
                                <label class="form-label">Total Price</label>
                                <input type="number" class="form-input component-total" placeholder="0" step="0.01" readonly style="background-color: #f9fafb; color: #6b7280; font-weight: bold;">
                            </div>
                            <div class="form-group flex items-end">
                                <button type="button" class="btn btn-outline btn-sm text-red-600 border-red-600 hover:bg-red-600 hover:text-white" onclick="removeComponentForRenewal(this)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center mt-4">
                    <button type="button" class="btn btn-outline" onclick="addComponentForRenewal()">
                        <i class="fas fa-plus"></i> Add Component
                    </button>
                </div>
            </div>

            <!-- Pricing Information Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Pricing Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Subtotal *</label>
                        <input type="number" class="form-input" name="total_amount" placeholder="0" step="0.01" min="0" required readonly style="background-color: #f9fafb; color: #6b7280; font-weight: bold;">
                        <small class="text-gray-500 text-xs">Auto-calculated from components</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount Amount</label>
                        <input type="number" class="form-input" name="discount_amount" placeholder="0" step="0.01" min="0" onchange="calculateGrandTotalForRenewal()">
                        <small class="text-gray-500 text-xs">Discount amount (if any)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tax Amount</label>
                        <input type="number" class="form-input" name="tax_amount" placeholder="0" step="0.01" min="0" onchange="calculateGrandTotalForRenewal()">
                        <small class="text-gray-500 text-xs">Tax amount (if any)</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grand Total *</label>
                        <input type="number" class="form-input" name="grand_total" placeholder="0" step="0.01" min="0" required readonly style="background-color: #f9fafb; color: #6b7280; font-weight: bold;">
                        <small class="text-gray-500 text-xs">Final amount (auto-calculated)</small>
                    </div>
                </div>
            </div>
            
            <!-- Notes Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Additional Information</h3>
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">Terms & Conditions</label>
                        <textarea class="form-textarea" name="terms_conditions" placeholder="Enter terms and conditions" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Renewal Reason</label>
                        <textarea class="form-textarea" name="renewal_reason" placeholder="Explain the reason for renewal and any changes" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Internal Notes</label>
                        <textarea class="form-textarea" name="internal_notes" placeholder="Enter internal notes for team reference" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitRenewalForm(null)">Create Renewal Quotation</button>
        </div>
    `;
    openModal();
}

function openViewModal(id) {
    openModal('View Quotation');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/marketing/quotations/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <div class="max-h-[60vh] overflow-y-auto pr-2">
                    <!-- Basic Information Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Basic Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Quotation Number</div>
                                <div class="text-gray-900 font-semibold">${data.quotation_number || 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Quotation Date</div>
                                <div class="text-gray-900">${data.quotation_date ? formatDateWithThreeDigitMonth(new Date(data.quotation_date)) : 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Valid Until</div>
                                <div class="text-gray-900">${data.valid_until ? formatDateWithThreeDigitMonth(new Date(data.valid_until)) : 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Status</div>
                                <div class="text-gray-900">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${data.status === 'approved' ? 'bg-green-100 text-green-800' : data.status === 'draft' ? 'bg-yellow-100 text-yellow-800' : data.status === 'waiting_for_approval' ? 'bg-yellow-100 text-yellow-800' : data.status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'}">
                                        ${data.status === 'waiting_for_approval' ? 'Waiting For Approval' : 
                                          data.status === 'draft' ? 'Draft' :
                                          data.status === 'approved' ? 'Approved' :
                                          data.status === 'rejected' ? 'Rejected' :
                                          data.status === 'sent' ? 'Sent' :
                                          data.status === 'accepted' ? 'Accepted' :
                                          data.status === 'expired' ? 'Expired' :
                                          data.status || 'N/A'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Company Information Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Company Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Company Name</div>
                                <div class="text-gray-900">${data.company_name || 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">PIC Name</div>
                                <div class="text-gray-900">${data.pic_name || 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Pipeline</div>
                                <div class="text-gray-900">${data.prospect ? data.prospect.company_name : 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Survey</div>
                                <div class="text-gray-900">${data.survey ? data.survey.survey_number : 'N/A'}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quotation Details Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Quotation Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Billing Methods</div>
                                <div class="text-gray-900">${data.billing_methods || 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Rental Period</div>
                                <div class="text-gray-900">${data.rental_period || 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Terms of Payment</div>
                                <div class="text-gray-900">${formatTermsOfPaymentLabel(data.terms_of_payment)}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Marketing Staff</div>
                                <div class="text-gray-900">${data.marketing ? data.marketing.name : 'N/A'}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Information Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Pricing Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Total Amount</div>
                                <div class="text-gray-900">${data.total_amount ? 'Rp ' + parseFloat(data.total_amount).toLocaleString('id-ID') : 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Discount Amount</div>
                                <div class="text-gray-900">${data.discount_amount ? 'Rp ' + parseFloat(data.discount_amount).toLocaleString('id-ID') : 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Tax Amount</div>
                                <div class="text-gray-900">${data.tax_amount ? 'Rp ' + parseFloat(data.tax_amount).toLocaleString('id-ID') : 'N/A'}</div>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg border-2 border-blue-200">
                                <div class="text-sm font-medium text-blue-600 mb-1">Grand Total</div>
                                <div class="text-blue-900 font-bold text-lg">${data.grand_total ? 'Rp ' + parseFloat(data.grand_total).toLocaleString('id-ID') : 'N/A'}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Information Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Approval Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Approved By</div>
                                <div class="text-gray-900">${data.approved_by_display_name || 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-1">Date Approved</div>
                                <div class="text-gray-900">${data.date_approved ? formatDateWithThreeDigitMonth(new Date(data.date_approved)) : 'N/A'}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Additional Information</h3>
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-2">Internal Notes</div>
                                <div class="text-gray-900 whitespace-pre-wrap">${data.internal_notes || 'N/A'}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-gray-600 mb-2">Additional Notes</div>
                                <div class="text-gray-900 whitespace-pre-wrap">${data.additional_notes || 'N/A'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Set footer buttons
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-end gap-4">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                    <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading quotation details.</div>';
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-end gap-4">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                </div>
            `;
        });
}

function openEditModal(id) {
    openModal('Edit Quotation');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/marketing/quotations/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update the quotation details below.</p>
                <div class="max-h-[70vh] overflow-y-auto pr-2">
                    <form id="quotationForm" onsubmit="submitForm(event, ${id})">
                        <!-- Basic Information Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Quotation Number *</label>
                                    <input type="text" class="form-input" name="quotation_number" value="${data.quotation_number || ''}" readonly style="background-color: #f9fafb; color: #6b7280;" placeholder="Auto-generated" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Quotation Date *</label>
                                    <input type="date" class="form-input" name="quotation_date" value="${data.quotation_date ? new Date(data.quotation_date).toISOString().split('T')[0] : ''}" readonly style="background-color: #f9fafb; color: #6b7280;" required>
                                    <small class="text-gray-500 text-xs">This date was set when quotation was created</small>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Valid Until *</label>
                                    <input type="date" class="form-input" name="valid_until" value="${data.valid_until ? new Date(data.valid_until).toISOString().split('T')[0] : ''}" required>
                                </div>
                            </div>
                        </div>

                        <!-- Company Information Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Company Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Pipeline *</label>
                                    <select class="form-select" name="prospect_id" required>
                                        <option value="">Select Pipeline</option>
                                        @foreach($prospects ?? [] as $prospect)
                                            <option value="{{ $prospect->id }}" ${data.prospect_id == {{ $prospect->id }} ? 'selected' : ''}>{{ $prospect->company_name }} - {{ $prospect->contact_person }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Survey *</label>
                                    <select class="form-select" name="survey_id" required>
                                        <option value="">Select Survey</option>
                                        @foreach($surveys ?? [] as $survey)
                                            <option value="{{ $survey->id }}" ${data.survey_id == {{ $survey->id }} ? 'selected' : ''}>{{ $survey->survey_number }} - {{ $survey->surveyor->name ?? 'N/A' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Company Name *</label>
                                    <input type="text" class="form-input" name="company_name" value="${data.company_name || ''}" placeholder="Enter company name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">PIC Name *</label>
                                    <input type="text" class="form-input" name="pic_name" value="${data.pic_name || ''}" placeholder="Enter PIC name" required>
                                </div>
                            </div>
                        </div>

                        <!-- Quotation Details Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Quotation Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                                <div class="form-group">
                                    <label class="form-label">Billing Methods *</label>
                                    <select class="form-select" name="billing_methods" required>
                                        <option value="">Select Billing Method</option>
                                        <option value="monthly" ${data.billing_methods === 'monthly' ? 'selected' : ''}>Monthly</option>
                                        <option value="quarterly" ${data.billing_methods === 'quarterly' ? 'selected' : ''}>Quarterly</option>
                                        <option value="annually" ${data.billing_methods === 'annually' ? 'selected' : ''}>Annually</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Status *</label>
                                    <select class="form-select" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="draft" ${data.status === 'draft' ? 'selected' : ''}>Draft</option>
                                        <option value="sent" ${data.status === 'sent' ? 'selected' : ''}>Sent</option>
                                        <option value="approved" ${data.status === 'approved' ? 'selected' : ''}>Approved</option>
                                        <option value="rejected" ${data.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Rental Period</label>
                                    <select class="form-select" name="rental_period" required>
                                        <option value="">Select Rental Period</option>
                                        <option value="1 day" ${data.rental_period === '1 day' ? 'selected' : ''}>1 Day</option>
                                        <option value="3 days" ${data.rental_period === '3 days' ? 'selected' : ''}>3 Days</option>
                                        <option value="5 days" ${data.rental_period === '5 days' ? 'selected' : ''}>5 Days</option>
                                        <option value="1 week" ${data.rental_period === '1 week' ? 'selected' : ''}>1 Week</option>
                                        <option value="2 weeks" ${data.rental_period === '2 weeks' ? 'selected' : ''}>2 Weeks</option>
                                        <option value="1 month" ${data.rental_period === '1 month' ? 'selected' : ''}>1 Month</option>
                                        <option value="3 months" ${data.rental_period === '3 months' ? 'selected' : ''}>3 Months</option>
                                        <option value="6 months" ${data.rental_period === '6 months' ? 'selected' : ''}>6 Months</option>
                                        <option value="12 months" ${data.rental_period === '12 months' ? 'selected' : ''}>12 Months</option>
                                        <option value="24 months" ${data.rental_period === '24 months' ? 'selected' : ''}>24 Months</option>
                                        <option value="36 months" ${data.rental_period === '36 months' ? 'selected' : ''}>36 Months</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Terms of Payment</label>
                                    <select class="form-select" name="terms_of_payment" required>
                                        <option value="">Select Payment Terms</option>
                                        <option value="cash" ${data.terms_of_payment === 'cash' ? 'selected' : ''}>Cash</option>
                                        <option value="credit_30" ${data.terms_of_payment === 'credit_30' ? 'selected' : ''}>Credit 30 Days</option>
                                        <option value="credit_60" ${data.terms_of_payment === 'credit_60' ? 'selected' : ''}>Credit 60 Days</option>
                                        <option value="credit_90" ${data.terms_of_payment === 'credit_90' ? 'selected' : ''}>Credit 90 Days</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Marketing Name *</label>
                                    <input type="text" class="form-input" name="marketing_name" value="${data.marketing ? data.marketing.name : 'Not Assigned'}" readonly style="background-color: #f9fafb; color: #6b7280;" required>
                                    <input type="hidden" name="marketing_id" value="${data.marketing_id || ''}">
                                    <small class="text-gray-500 text-xs">Marketing staff is inherited from Pipeline. To change, edit the Pipeline first.</small>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Approved By</label>
                                    <select class="form-select" name="approved_by">
                                        <option value="">Select Approver</option>
                                        @foreach($approvers ?? [] as $user)
                                            <option value="{{ $user->id }}" ${data.approved_by == {{ $user->id }} ? 'selected' : ''}>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date Approved</label>
                                    <input type="date" class="form-input" name="date_approved" value="${data.date_approved ? new Date(data.date_approved).toISOString().split('T')[0] : ''}">
                                </div>
                            </div>
                        </div>
                    
                    <!-- Pricing Information Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b border-gray-200 pb-2">Pricing Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Total Amount *</label>
                                <input type="number" class="form-input" name="total_amount" value="${data.total_amount || 0}" step="0.01" min="0" required onchange="calculateGrandTotalForEdit()">
                                <small class="text-gray-500 text-xs">Base amount before discount and tax</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Discount Amount</label>
                                <input type="number" class="form-input" name="discount_amount" value="${data.discount_amount || 0}" step="0.01" min="0" onchange="calculateGrandTotalForEdit()">
                                <small class="text-gray-500 text-xs">Discount amount (if any)</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tax Amount</label>
                                <input type="number" class="form-input" name="tax_amount" value="${data.tax_amount || 0}" step="0.01" min="0" onchange="calculateGrandTotalForEdit()">
                                <small class="text-gray-500 text-xs">Tax amount (if any)</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Grand Total *</label>
                                <input type="number" class="form-input" name="grand_total" value="${data.grand_total || 0}" step="0.01" min="0" required readonly style="background-color: #f9fafb; color: #6b7280; font-weight: bold;">
                                <small class="text-gray-500 text-xs">Final amount (auto-calculated)</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Full Width Notes Fields -->
                    <div class="form-group">
                        <label class="form-label">Terms & Conditions</label>
                        <textarea class="form-textarea" name="terms_conditions" placeholder="Enter terms and conditions" rows="3">${data.terms_conditions || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Internal Notes</label>
                        <textarea class="form-textarea" name="internal_notes" placeholder="Enter internal notes" rows="3">${data.internal_notes || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Additional Notes</label>
                        <textarea class="form-textarea" name="additional_notes" placeholder="Enter additional notes" rows="3">${data.additional_notes || ''}</textarea>
                    </div>
                </form>
                </div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitForm(null, ${id})">Update Quotation</button>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading quotation details.</div>';
        });
}

// Auto generate quotation number
function generateQuotationNumber(type = 'new') {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const timestamp = now.getTime().toString().slice(-4);
    
    const prefix = type === 'renewal' ? 'REN' : 'QUO';
    return `${prefix}-${year}${month}${day}-${timestamp}`;
}

// Function to autofill prospect data
function loadProspectData(prospectId) {
    if (!prospectId) {
        // Clear fields if no prospect selected
        document.querySelector('input[name="company_name"]').value = '';
        document.querySelector('input[name="pic_name"]').value = '';
        return;
    }

    fetch(`/marketing/prospects/${prospectId}/data`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Autofill company name and PIC name
                const companyNameField = document.querySelector('input[name="company_name"]');
                const picNameField = document.querySelector('input[name="pic_name"]');
                
                if (companyNameField) {
                    companyNameField.value = data.data.company_name || '';
                }
                if (picNameField) {
                    picNameField.value = data.data.contact_person || '';
                }
            } else {
                console.error('Error loading prospect data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function loadProspectDataForRenewal(prospectId) {
    if (!prospectId) {
        // Clear fields if no prospect selected
        const renewalForm = document.getElementById('renewalForm');
        if (renewalForm) {
            const companyNameField = renewalForm.querySelector('input[name="company_name"]');
            const picNameField = renewalForm.querySelector('input[name="pic_name"]');
            if (companyNameField) companyNameField.value = '';
            if (picNameField) picNameField.value = '';
        }
        return;
    }

    fetch(`/marketing/prospects/${prospectId}/data`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Autofill company name and PIC name in renewal form
                const renewalForm = document.getElementById('renewalForm');
                if (renewalForm) {
                    const companyNameField = renewalForm.querySelector('input[name="company_name"]');
                    const picNameField = renewalForm.querySelector('input[name="pic_name"]');
                    
                    if (companyNameField) {
                        companyNameField.value = data.data.company_name || '';
                    }
                    if (picNameField) {
                        picNameField.value = data.data.contact_person || '';
                    }
                }
            } else {
                console.error('Error loading prospect data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Component Management Functions
function addComponent() {
    const container = document.getElementById('componentsContainer');
    const firstComponent = container.querySelector('.component-item');
    const componentOptions = firstComponent ? firstComponent.querySelector('.component-type').innerHTML : '';
    
    const componentHtml = `
        <div class="component-item border border-gray-200 rounded-lg p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="form-group">
                    <label class="form-label">Component Type *</label>
                    <select class="form-select component-type" required onchange="updateComponentPrice(this)">
                        ${componentOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Item Name *</label>
                    <input type="text" class="form-input component-name" placeholder="Enter item name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity *</label>
                    <input type="number" class="form-input component-qty" placeholder="1" min="1" value="1" required onchange="calculateComponentTotal(this)">
                </div>
                <div class="form-group">
                    <label class="form-label">Unit Price *</label>
                    <input type="number" class="form-input component-price" placeholder="0" step="0.01" min="0" required onchange="calculateComponentTotal(this)">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="form-group">
                    <label class="form-label">Total Price</label>
                    <input type="number" class="form-input component-total" placeholder="0" step="0.01" readonly style="background-color: #f9fafb; color: #6b7280; font-weight: bold;">
                </div>
                <div class="form-group flex items-end">
                    <button type="button" class="btn btn-outline btn-sm text-red-600 border-red-600 hover:bg-red-600 hover:text-white" onclick="removeComponent(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', componentHtml);
}

function removeComponent(button) {
    const componentItem = button.closest('.component-item');
    componentItem.remove();
    calculateSubtotal();
}

function updateComponentPrice(select) {
    const price = select.options[select.selectedIndex].getAttribute('data-price');
    const name = select.options[select.selectedIndex].getAttribute('data-name');
    const priceInput = select.closest('.component-item').querySelector('.component-price');
    const nameInput = select.closest('.component-item').querySelector('.component-name');
    
    if (price && price !== '0') {
        priceInput.value = price;
        if (name) nameInput.value = name;
    } else {
        priceInput.value = '';
        nameInput.value = '';
    }
    
    calculateComponentTotal(priceInput);
}

function calculateComponentTotal(input) {
    const componentItem = input.closest('.component-item');
    const qty = parseFloat(componentItem.querySelector('.component-qty').value) || 0;
    const price = parseFloat(componentItem.querySelector('.component-price').value) || 0;
    const total = qty * price;
    
    componentItem.querySelector('.component-total').value = total.toFixed(2);
    calculateSubtotal();
}

function calculateSubtotal() {
    let subtotal = 0;
    document.querySelectorAll('.component-total').forEach(input => {
        subtotal += parseFloat(input.value) || 0;
    });
    
    document.querySelector('input[name="total_amount"]').value = subtotal.toFixed(2);
    calculateGrandTotal();
}

// Calculate Grand Total for Create Form
function calculateGrandTotal() {
    const totalAmount = parseFloat(document.querySelector('input[name="total_amount"]').value) || 0;
    const discountAmount = parseFloat(document.querySelector('input[name="discount_amount"]').value) || 0;
    const taxAmount = parseFloat(document.querySelector('input[name="tax_amount"]').value) || 0;
    
    const grandTotal = totalAmount - discountAmount + taxAmount;
    document.querySelector('input[name="grand_total"]').value = grandTotal.toFixed(2);
}

// Component Management Functions for Renewal Form
function addComponentForRenewal() {
    const container = document.getElementById('renewalComponentsContainer');
    const firstComponent = container.querySelector('.component-item');
    const componentOptions = firstComponent ? firstComponent.querySelector('.component-type').innerHTML : '';
    
    const componentHtml = `
        <div class="component-item border border-gray-200 rounded-lg p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="form-group">
                    <label class="form-label">Component Type *</label>
                    <select class="form-select component-type" required onchange="updateComponentPriceForRenewal(this)">
                        ${componentOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Item Name *</label>
                    <input type="text" class="form-input component-name" placeholder="Enter item name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity *</label>
                    <input type="number" class="form-input component-qty" placeholder="1" min="1" value="1" required onchange="calculateComponentTotalForRenewal(this)">
                </div>
                <div class="form-group">
                    <label class="form-label">Unit Price *</label>
                    <input type="number" class="form-input component-price" placeholder="0" step="0.01" min="0" required onchange="calculateComponentTotalForRenewal(this)">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="form-group">
                    <label class="form-label">Total Price</label>
                    <input type="number" class="form-input component-total" placeholder="0" step="0.01" readonly style="background-color: #f9fafb; color: #6b7280; font-weight: bold;">
                </div>
                <div class="form-group flex items-end">
                    <button type="button" class="btn btn-outline btn-sm text-red-600 border-red-600 hover:bg-red-600 hover:text-white" onclick="removeComponentForRenewal(this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', componentHtml);
}

function removeComponentForRenewal(button) {
    const componentItem = button.closest('.component-item');
    componentItem.remove();
    calculateSubtotalForRenewal();
}

function updateComponentPriceForRenewal(select) {
    const price = select.options[select.selectedIndex].getAttribute('data-price');
    const name = select.options[select.selectedIndex].getAttribute('data-name');
    const priceInput = select.closest('.component-item').querySelector('.component-price');
    const nameInput = select.closest('.component-item').querySelector('.component-name');
    
    if (price && price !== '0') {
        priceInput.value = price;
        if (name) nameInput.value = name;
    } else {
        priceInput.value = '';
        nameInput.value = '';
    }
    
    calculateComponentTotalForRenewal(priceInput);
}

function calculateComponentTotalForRenewal(input) {
    const componentItem = input.closest('.component-item');
    const qty = parseFloat(componentItem.querySelector('.component-qty').value) || 0;
    const price = parseFloat(componentItem.querySelector('.component-price').value) || 0;
    const total = qty * price;
    
    componentItem.querySelector('.component-total').value = total.toFixed(2);
    calculateSubtotalForRenewal();
}

function calculateSubtotalForRenewal() {
    let subtotal = 0;
    document.querySelectorAll('#renewalComponentsContainer .component-total').forEach(input => {
        subtotal += parseFloat(input.value) || 0;
    });
    
    const form = document.getElementById('renewalForm');
    if (form) {
        form.querySelector('input[name="total_amount"]').value = subtotal.toFixed(2);
        calculateGrandTotalForRenewal();
    }
}

// Calculate Grand Total for Renewal Form
function calculateGrandTotalForRenewal() {
    const form = document.getElementById('renewalForm');
    if (!form) return;
    
    const totalAmount = parseFloat(form.querySelector('input[name="total_amount"]').value) || 0;
    const discountAmount = parseFloat(form.querySelector('input[name="discount_amount"]').value) || 0;
    const taxAmount = parseFloat(form.querySelector('input[name="tax_amount"]').value) || 0;
    
    const grandTotal = totalAmount - discountAmount + taxAmount;
    form.querySelector('input[name="grand_total"]').value = grandTotal.toFixed(2);
}

// Calculate Grand Total for Edit Form
function calculateGrandTotalForEdit() {
    const form = document.getElementById('quotationForm');
    if (!form) return;
    
    const totalAmount = parseFloat(form.querySelector('input[name="total_amount"]').value) || 0;
    const discountAmount = parseFloat(form.querySelector('input[name="discount_amount"]').value) || 0;
    const taxAmount = parseFloat(form.querySelector('input[name="tax_amount"]').value) || 0;
    
    const grandTotal = totalAmount - discountAmount + taxAmount;
    form.querySelector('input[name="grand_total"]').value = grandTotal.toFixed(2);
}

function loadSurveyDataForCreate(surveyId) {
    if (!surveyId) {
        // Clear marketing fields if no survey selected
        const createForm = document.getElementById('quotationForm');
        if (createForm) {
            const marketingNameField = createForm.querySelector('input[name="marketing_name"]');
            const marketingIdField = createForm.querySelector('input[name="marketing_id"]');
            if (marketingNameField) marketingNameField.value = '';
            if (marketingIdField) marketingIdField.value = '';
        }
        return;
    }

    fetch(`/marketing/surveys/${surveyId}/data`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Autofill marketing fields in create form
                const createForm = document.getElementById('quotationForm');
                if (createForm) {
                    const marketingNameField = createForm.querySelector('input[name="marketing_name"]');
                    const marketingIdField = createForm.querySelector('input[name="marketing_id"]');
                    if (marketingNameField && data.data.marketing_name) {
                        marketingNameField.value = data.data.marketing_name;
                    }
                    if (marketingIdField && data.data.marketing_id) {
                        marketingIdField.value = data.data.marketing_id;
                    }
                }
            } else {
                console.error('Error loading survey data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function loadSurveyDataForRenewal(surveyId) {
    if (!surveyId) {
        // Clear marketing fields if no survey selected
        const renewalForm = document.getElementById('renewalForm');
        if (renewalForm) {
            const marketingNameField = renewalForm.querySelector('input[name="marketing_name"]');
            const marketingIdField = renewalForm.querySelector('input[name="marketing_id"]');
            if (marketingNameField) marketingNameField.value = '';
            if (marketingIdField) marketingIdField.value = '';
        }
        return;
    }

    fetch(`/marketing/surveys/${surveyId}/data`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Autofill marketing fields in renewal form
                const renewalForm = document.getElementById('renewalForm');
                if (renewalForm) {
                    const marketingNameField = renewalForm.querySelector('input[name="marketing_name"]');
                    const marketingIdField = renewalForm.querySelector('input[name="marketing_id"]');
                    if (marketingNameField && data.data.marketing_name) {
                        marketingNameField.value = data.data.marketing_name;
                    }
                    if (marketingIdField && data.data.marketing_id) {
                        marketingIdField.value = data.data.marketing_id;
                    }
                }
            } else {
                console.error('Error loading survey data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function submitForm(event = null, id = null) {
    if (event) {
        event.preventDefault();
    }
    
    // Get the form element, not the button
    const form = document.getElementById('quotationForm');
    if (!form) {
        console.error('Quotation form not found');
        return;
    }

    const formData = new FormData(form);
    
    // Add components data
    const components = [];
    document.querySelectorAll('.component-item').forEach((item, index) => {
        const component = {
            type: item.querySelector('.component-type').value,
            name: item.querySelector('.component-name').value,
            quantity: item.querySelector('.component-qty').value,
            unit_price: item.querySelector('.component-price').value,
            total_price: item.querySelector('.component-total').value
        };
        if (component.type && component.name && component.quantity && component.unit_price) {
            components.push(component);
        }
    });
    
    // Add components as JSON string
    formData.append('components', JSON.stringify(components));
    const data = Object.fromEntries(formData.entries());
    
    // Auto generate quotation number if not provided
    if (!data.quotation_number || data.quotation_number === 'Auto-generated') {
        data.quotation_number = generateQuotationNumber('new');
    }
    
    const url = id ? `/marketing/quotations/${id}` : '/marketing/quotations';
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
    .then(response => {
        if (!response.ok) {
            return response.json().then(errorData => {
                console.error('Validation errors:', errorData);
                if (errorData.errors) {
                    showValidationErrorModal(errorData.errors);
                } else {
                    showErrorModal('Gagal', errorData.message || 'Terjadi kesalahan.');
                }
                throw new Error(errorData.message || 'Validation failed');
            });
        }
        return response.json();
    })
    .then(result => {
        if (result.status === 'success') {
            closeModal();
            showSuccessModal('Quotation Berhasil Dibuat', 'Quotation berhasil dibuat dan sudah tersimpan.');
        } else {
            showErrorModal('Gagal', result.message || 'Terjadi kesalahan.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Don't show alert here as it's already handled above
    });
}

function submitRenewalForm(event = null) {
    // Prevent default form submission if event is provided
    if (event) {
        event.preventDefault();
    }
    
    const form = document.getElementById('renewalForm');
    if (!form) {
        console.error('Renewal form not found');
        showErrorDialog('Gagal', 'Form tidak ditemukan. Silakan coba lagi.');
        return;
    }
    
    // Validate form element
    if (!(form instanceof HTMLFormElement)) {
        console.error('Invalid form element:', form);
        showErrorDialog('Gagal', 'Form tidak valid. Silakan coba lagi.');
        return;
    }
    
    const formData = new FormData(form);
    
    // Add components data for renewal
    const components = [];
    document.querySelectorAll('#renewalComponentsContainer .component-item').forEach((item, index) => {
        const component = {
            type: item.querySelector('.component-type').value,
            name: item.querySelector('.component-name').value,
            quantity: item.querySelector('.component-qty').value,
            unit_price: item.querySelector('.component-price').value,
            total_price: item.querySelector('.component-total').value
        };
        if (component.type && component.name && component.quantity && component.unit_price) {
            components.push(component);
        }
    });
    
    // Add components as JSON string
    formData.append('components', JSON.stringify(components));
    
    const data = Object.fromEntries(formData.entries());
    
    // Auto generate renewal quotation number
    if (!data.quotation_number || data.quotation_number === 'Auto-generated') {
        data.quotation_number = generateQuotationNumber('renewal');
    }
    
    // Add quotation type
    data.quotation_type = 'renewal';
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    console.log('Sending data:', data);
    
    fetch('/marketing/quotations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(result => {
        console.log('Response data:', result);
        if (result.status === 'success') {
            closeModal();
            showSuccessModal('Quotation Renewal Berhasil Dibuat', 'Quotation renewal berhasil dibuat dan sudah tersimpan.');
        } else {
            // Handle validation errors
            if (result.errors) {
                let errorMessage = 'Silakan perbaiki kesalahan berikut:\n';
                for (const field in result.errors) {
                    errorMessage += `${field}: ${result.errors[field].join(', ')}\n`;
                }
                showErrorDialog('Gagal', errorMessage);
            } else {
                showErrorDialog('Gagal', result.message || 'Terjadi kesalahan.');
            }
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
    const title = count === 1 ? 'Batalkan 1 Quotation?' : `Batalkan ${count} Quotation?`;
    
    document.getElementById('deleteModalTitle').textContent = title;
    const deleteDescription = document.querySelector('.delete-modal-description');
    if (deleteDescription) {
        deleteDescription.textContent = 'Data ini tidak akan tampil lagi di halaman ini, tetapi tetap aman dan masih tersimpan di database.';
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
    
    fetch('/marketing/quotations/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
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
    .then(result => {
        if (result.status === 'success' || result.success === true) {
            showSuccessModal('Quotation Berhasil Dibatalkan', `${result.count} quotation berhasil dibatalkan.`);
        } else {
            // Handle partial success or explicit failure in success response
            showErrorModal('Gagal', result.message || 'Terjadi kesalahan.', result.errors || []);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        let errorMessage = error.message || 'Terjadi kesalahan jaringan. Silakan periksa koneksi Anda lalu coba lagi.';
        let errors = error.errors || [];
        
        // Handle case where error is a JSON string
        if (typeof error.message === 'string' && error.message.startsWith('{')) {
             try {
                 const parsed = JSON.parse(error.message);
                 errorMessage = parsed.message || errorMessage;
                 errors = parsed.errors || errors;
             } catch(e) {}
        }
        
        showErrorModal('Gagal', errorMessage, errors);
    });
}

// Success Modal functions
function showSuccessModal(title, description) {
    document.querySelector('.success-modal-title').textContent = title || 'Berhasil';
    document.getElementById('successModalDescription').innerHTML = description || 'Operasi berhasil diselesaikan.';
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
// Error Modal functions
function showErrorModal(title, message, errors = []) {
    document.querySelector('.error-modal-title').textContent = title || 'Gagal';
    
    let content = message || 'Terjadi kesalahan. Silakan coba lagi.';
    
    // Append detailed errors if provided
    if (errors && Array.isArray(errors) && errors.length > 0) {
        content += '<div class="mt-3 bg-red-50 p-3 rounded-md border border-red-100">';
        content += '<p class="text-xs font-semibold text-red-800 mb-1">Detail:</p>';
        content += '<ul class="list-disc list-inside text-sm text-red-600 space-y-1">';
        errors.forEach(err => {
            content += `<li>${err}</li>`;
        });
        content += '</ul>';
        content += '</div>';
    }
    
    document.querySelector('.error-modal-description').innerHTML = content;
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

document.addEventListener('DOMContentLoaded', function () {
    const deleteDescription = document.querySelector('.delete-modal-description');
    if (deleteDescription) {
        deleteDescription.textContent = 'Data ini tidak akan tampil lagi di halaman ini, tetapi tetap aman dan masih tersimpan di database.';
    }

    const successDescription = document.getElementById('successModalDescription');
    if (successDescription) {
        successDescription.innerHTML = 'Quotation berhasil disimpan dan siap diproses lebih lanjut.<br>Data sudah tersimpan dengan aman.';
    }

    const errorDescription = document.querySelector('.error-modal-description');
    if (errorDescription) {
        errorDescription.textContent = 'Permintaan Anda belum berhasil diproses saat ini, tetapi data tetap aman. Silakan coba lagi nanti.';
    }
});

function showValidationErrorModal(errors) {
    document.querySelector('.error-modal-title').textContent = 'Validasi Gagal';
    
    let errorHtml = '<div class="space-y-3">';
    for (const [field, messages] of Object.entries(errors)) {
        const fieldName = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        const messageList = Array.isArray(messages) ? messages : [messages];
        
        errorHtml += `
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                <h4 class="font-semibold text-red-800 mb-1">${fieldName}</h4>
                <ul class="text-red-700 text-sm space-y-1">
                    ${messageList.map(msg => `<li>• ${msg}</li>`).join('')}
                </ul>
            </div>
        `;
    }
    errorHtml += '</div>';
    
    document.querySelector('.error-modal-description').innerHTML = errorHtml;
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retryAction() {
    closeErrorModal();
    confirmDelete();
}

function retryDelete() {
    closeErrorModal();
    confirmDelete();
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

// Auto-fill marketing staff when prospect changes
function onProspectChange(prospectId) {
    if (!prospectId) {
        // Clear marketing field if no prospect selected
        const marketingInput = document.querySelector('input[name="marketing_name"]');
        const marketingHidden = document.querySelector('input[name="marketing_id"]');
        if (marketingInput) marketingInput.value = '';
        if (marketingHidden) marketingHidden.value = '';
        return;
    }
    
    // Fetch prospect data to auto-fill marketing staff
    fetch(`/marketing/prospects/${prospectId}/data`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const marketingInput = document.querySelector('input[name="marketing_name"]');
            const marketingHidden = document.querySelector('input[name="marketing_id"]');
            
            if (marketingInput) {
                marketingInput.value = data.data.marketing_staff.name;
            }
            if (marketingHidden) {
                marketingHidden.value = data.data.marketing_staff.id || '';
            }
        }
    })
    .catch(error => {
        console.error('Error fetching prospect data:', error);
    });
}

// Add event listener for prospect dropdown changes
document.addEventListener('DOMContentLoaded', function() {
    // Add change event to prospect dropdown in create modal
    const prospectSelect = document.querySelector('select[name="prospect_id"]');
    if (prospectSelect) {
        prospectSelect.addEventListener('change', function() {
            onProspectChange(this.value);
        });
    }
});
</script>

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
            <h2 id="deleteModalTitle" class="delete-modal-title">Batalkan 0 Quotation?</h2>
            
            <!-- Description -->
            <p class="delete-modal-description">
                These records won't show up on this page anymore, but don't worry—they'll stay safe in the database.
            </p>
            
            <!-- Buttons -->
            <div class="delete-modal-buttons">
                <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
                <button class="btn btn-hide" onclick="confirmDelete()">Ya, Batalkan</button>
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
            <h2 class="success-modal-title">Quotation Berhasil Dibuat</h2>
            
            <!-- Description -->
            <p id="successModalDescription" class="success-modal-description">
                Your quotation is now saved and ready to track.<br>
                All set and safely stored in your records.
            </p>
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
            <h2 class="error-modal-title">Ups... Terjadi Kendala</h2>
            
            <!-- Description -->
            <p class="error-modal-description">
                We couldn't process your request just now, but your data's still safe. Give it another shot later.
            </p>
            
            <!-- Buttons -->
            <div class="error-modal-buttons">
                <button class="btn btn-error-close" onclick="closeErrorModal()">Tutup</button>
                <button class="btn btn-error-retry" onclick="retryAction()">Coba Lagi</button>
            </div>
        </div>
    </div>
</div>

<script>
// Approval Workflow Functions
function openPendingApprovalsModal() {
    openModal('⏰ Pending Approvals');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading pending approvals...</p></div>';
    
    fetch('/marketing/quotations/pending-approvals', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            displayPendingApprovals(data.data);
        } else {
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading pending approvals</div>';
        }
    })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = `
                <div style="text-align: center; padding: 40px; color: #ef4444;">
                    <h3>Error Loading Pending Approvals</h3>
                    <p>Status: ${error.message}</p>
                    <p>Please check your connection and try again.</p>
                    <button onclick="openPendingApprovalsModal()" class="btn btn-primary mt-2">Retry</button>
                </div>
            `;
        });
}

function displayPendingApprovals(quotations) {
    let html = `
        <div style="max-height: 400px; overflow-y: auto;">
            <div style="display: grid; gap: 12px;">
    `;
    
    if (quotations.data && quotations.data.length > 0) {
        quotations.data.forEach(quotation => {
            html += `
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #f9fafb;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                        <div>
                            <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">${quotation.quotation_number}</h4>
                            <p style="margin: 4px 0 0 0; font-size: 14px; color: #6b7280;">${quotation.company_name}</p>
                        </div>
                        <span style="background: #f59e0b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">${quotation.status === 'waiting_for_approval' ? 'Waiting For Approval' : quotation.status}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; font-size: 14px; color: #6b7280;">
                        <div>Marketing: ${quotation.marketing?.name || 'N/A'}</div>
                        <div>Created: ${new Date(quotation.created_at).toLocaleDateString()}</div>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="viewQuotation(${quotation.id})" style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer;">View</button>
                        <button onclick="approveQuotation(${quotation.id})" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer;">Approve</button>
                        <button onclick="rejectQuotation(${quotation.id})" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer;">Reject</button>
                    </div>
                </div>
            `;
        });
    } else {
        html += `
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 16px; color: #10b981;"></i>
                <p style="margin: 0; font-size: 16px;">No pending approvals</p>
                <p style="margin: 8px 0 0 0; font-size: 14px;">All quotations have been processed</p>
            </div>
        `;
    }
    
    html += `
            </div>
        </div>
    `;
    
    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Close</button>
        </div>
    `;
}

function approveQuotation(quotationId) {
    showConfirmDialog(
        'Approve Quotation',
        'Apakah Anda yakin ingin menyetujui quotation ini?',
        'Ya, Approve',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) {
            return;
        }

        fetch(`/marketing/quotations/${quotationId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccessDialog('Berhasil', 'Quotation berhasil disetujui.');
                openPendingApprovalsModal();
            } else {
                showErrorDialog('Gagal', data.message || 'Quotation tidak berhasil disetujui.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan saat menyetujui quotation.');
        });
    });
}

function rejectQuotation(quotationId) {
    Swal.fire({
        title: 'Reject Quotation',
        text: 'Masukkan alasan penolakan quotation.',
        input: 'text',
        inputPlaceholder: 'Alasan penolakan',
        showCancelButton: true,
        confirmButtonText: 'Kirim',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return 'Alasan penolakan wajib diisi.';
            }
            return null;
        }
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        fetch(`/marketing/quotations/${quotationId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                rejection_reason: result.value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccessDialog('Berhasil', 'Quotation berhasil ditolak.');
                openPendingApprovalsModal();
            } else {
                showErrorDialog('Gagal', data.message || 'Quotation tidak berhasil ditolak.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan saat menolak quotation.');
        });
    });
}

function requestApproval(quotationId) {
    showConfirmDialog(
        'Kirim Approval',
        'Apakah Anda yakin ingin mengirim quotation ini untuk approval?',
        'Ya, Kirim',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) {
            return;
        }

        fetch(`/marketing/quotations/${quotationId}/request-approval`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccessDialog('Berhasil', 'Quotation berhasil dikirim untuk approval.');
                loadQuotations();
            } else {
                showErrorDialog('Gagal', data.message || 'Quotation tidak berhasil dikirim untuk approval.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan saat mengirim quotation untuk approval.');
        });
    });
}

// Copy Quotation as Revision - Direct function for onclick
function copyQuotation(quotationId) {
    showConfirmDialog(
        'Copy Revisi',
        'Copy quotation ini sebagai revisi baru dengan status draft?',
        'Ya, Copy',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) {
            return;
        }
        
        fetch(`/marketing/quotations/${quotationId}/copy`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccessDialog('Berhasil', 'Quotation berhasil di-copy sebagai revisi #' + data.data.revision_number + ' (Draft).');
                window.location.href = data.data.redirect_url;
            } else {
                showErrorDialog('Gagal', data.message || 'Quotation tidak berhasil di-copy.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorDialog('Gagal', 'Terjadi kesalahan saat meng-copy quotation.');
        });
    });
}

function openQuotationDetail(quotationId) {
    const listUrl = window.location.href;
    const detailUrl = new URL(@json(route('marketing.quotations.show', ':id')).replace(':id', quotationId), window.location.origin);

    sessionStorage.setItem('aroma:list:marketing.quotations', listUrl);
    detailUrl.searchParams.set('return_url', listUrl);

    window.location.href = detailUrl.toString();
}
</script>

@endsection
