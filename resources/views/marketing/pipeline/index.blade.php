@extends('layouts.app')

@section('title', 'Pipeline')
@section('breadcrumb', 'Home / Marketing / Pipeline')

@section('content')
<style>
    /* Responsive Table */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 0 0 10px 10px;
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
    
    .delete-modal-content {
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
    
    .error-modal-content {
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
    
    .success-modal-content {
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
        overflow: visible;
        position: relative;
        display: flex;
        flex-direction: column;
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
        overflow-x: visible;
        max-height: calc(90vh - 140px);
        flex: 1;
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
    
    .detail-value a.contract-link {
        color: #2563eb !important;
        text-decoration: underline !important;
        cursor: pointer !important;
        font-weight: 500;
    }
    
    .detail-value a.contract-link:hover {
        color: #1e40af !important;
        text-decoration: underline !important;
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
    
    /* Agenda and Contract List Styles */
    .agenda-item, .contract-item {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 8px;
        background-color: #f9fafb;
    }
    
    .agenda-item:hover, .contract-item:hover {
        border-color: #214589;
        background-color: #f0f9ff;
    }
    
    .agenda-item input, .contract-item input {
        border: none;
        background: transparent;
        box-shadow: none;
    }
    
    .agenda-item input:focus, .contract-item input:focus {
        outline: none;
        box-shadow: none;
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
    
    .delete-modal-content {
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
    
    /* Mobile Delete Modal Adjustments */
    @media (max-width: 768px) {
        .delete-modal-container {
            width: 95vw;
            margin: 20px;
        }
        
        .delete-modal-content {
            padding: 30px 20px 20px;
        }
        
        .delete-icon {
            width: 60px;
            height: 60px;
        }
        
        .delete-modal-title {
            font-size: 20px;
        }
        
        .delete-modal-description {
            font-size: 14px;
        }
        
        .delete-modal-buttons {
            flex-direction: column;
            gap: 12px;
        }
        
        .btn-cancel,
        .btn-hide {
            width: 100%;
            justify-content: center;
        }
    }
    
    .modal-container.large {
        width: 1200px;
        max-width: 98vw;
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
    
    .error-modal-content {
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
    
    /* Mobile Error Modal Adjustments */
    @media (max-width: 768px) {
        .error-modal-container {
            width: 95vw;
            margin: 20px;
        }
        
        .error-modal-content {
            padding: 30px 20px 20px;
        }
        
        .error-icon {
            width: 60px;
            height: 60px;
        }
        
        .error-modal-title {
            font-size: 20px;
        }
        
        .error-modal-description {
            font-size: 14px;
        }
        
        .error-modal-buttons {
            flex-direction: column;
            gap: 12px;
        }
        
        .btn-error-close,
        .btn-error-retry {
            width: 100%;
            justify-content: center;
        }
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
    }
    
    .success-modal-content {
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
    
    /* Mobile Success Modal Adjustments */
    @media (max-width: 768px) {
        .success-modal-container {
            width: 95vw;
            margin: 20px;
        }
        
        .success-modal-content {
            padding: 30px 20px 20px;
        }
        
        .success-icon {
            width: 60px;
            height: 60px;
        }
        
        .success-modal-title {
            font-size: 20px;
        }
        
        .success-modal-description {
            font-size: 14px;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Pipeline Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Pipeline</h1>
            </div>
            
            @if(auth()->user()->hasPermission('marketing.create') || auth()->user()->hasPermission('marketing.write') || auth()->user()->hasPermission('marketing.pipeline.create'))
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                <span>Add New Pipeline</span>
            </button>
            @endif
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
        <div class="w-full bg-white table-container">
            <table class="responsive-table">
                <!-- Table Header -->
                <thead>
                    <tr>
                        <th class="w-[50px]" data-no-filter>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                        </th>
                        <th class="w-[180px]" data-column="follow_up_date" data-type="date">Latest Follow-Up</th>
                        <th class="w-[150px]" data-column="company_name">Customer Name</th>
                        <th class="w-[200px]" data-column="company_address">Customer Address</th>
                        <th class="w-[120px]" data-column="pic_name">Contact Name</th>
                        <th class="w-[130px]" data-column="pic_phone">Contact Number</th>
                        <th class="w-[180px]" data-column="pic_email">Contact Email</th>
                        <th class="w-[250px]" data-column="visit_result">Kegiatan/Agenda</th>
                        <th class="w-[200px]" data-column="notes">Additional Notes</th>
                        <th class="w-[150px]" data-column="creator.name">Created By</th>
                        <th class="w-[150px]" data-column="marketing_pipelines.created_at" data-type="date">Created At</th>
                        <th class="w-[150px]" data-column="updater.name" data-relation="updater">Last Updated By</th>
                        <th class="w-[150px]" data-column="marketing_pipelines.updated_at" data-type="date">Last Updated At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($pipeline as $item)
                    <tr onclick="openViewModal({{ $item->id }})" data-id="{{ $item->id }}">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer" onclick="event.stopPropagation()" value="{{ $item->id }}">
                        </td>
                        <td>
                            @if($item->follow_up_date)
                                {{ \Carbon\Carbon::parse($item->follow_up_date)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($item->follow_up_date)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $item->company_name }}</td>
                        <td>{{ $item->company_address }}</td>
                        <td>{{ $item->pic_name ?? 'N/A' }}</td>
                        <td>{{ $item->pic_phone }}</td>
                        <td>{{ $item->pic_email }}</td>
                        <td>
                            @if($item->agenda_list && is_array($item->agenda_list) && count($item->agenda_list) > 0)
                                <ul class="list-disc list-inside">
                                    @foreach($item->agenda_list as $agenda)
                                        <li class="text-xs">{{ $agenda }}</li>
                                    @endforeach
                                </ul>
                            @else
                                {{ $item->visit_result ?? 'N/A' }}
                            @endif
                        </td>
                        <td>{{ $item->notes ?? '-' }}</td>
                        <td>{{ $item->createdBy->name ?? 'N/A' }}</td>
                        <td>
                            @if($item->created_at)
                                {{ \Carbon\Carbon::parse($item->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($item->created_at)->format('H.i') }} WIB
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $item->updatedBy->name ?? 'N/A' }}</td>
                        <td>
                            @if($item->updated_at)
                                {{ \Carbon\Carbon::parse($item->updated_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($item->updated_at)->format('H.i') }} WIB
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="p-8 text-center">
                            <p class="text-lg font-inter text-center text-[#666]">No pipeline data found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        @if($pipeline->total() > 0)
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
            {{ $pipeline->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">Pipeline Details</h2>
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
            <h2 id="deleteModalTitle" class="delete-modal-title">Sembunyikan 0 data?</h2>
            
            <!-- Description -->
            <p class="delete-modal-description">
                Data yang dipilih tidak akan tampil lagi di halaman ini, tetapi tetap aman tersimpan di database.
            </p>
            
            <!-- Buttons -->
            <div class="delete-modal-buttons">
                <button class="btn btn-cancel" onclick="closeDeleteModal()">Batal</button>
                <button class="btn btn-hide" onclick="confirmDelete()">Ya, Sembunyikan</button>
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
            <h2 class="error-modal-title">Ups... Terjadi Kendala</h2>
            
            <!-- Description -->
            <p class="error-modal-description">
                Data belum berhasil disembunyikan saat ini, tetapi tetap aman tersimpan. Silakan coba lagi sebentar.
            </p>
            
            <!-- Buttons -->
            <div class="error-modal-buttons">
                <button class="btn btn-error-close" onclick="closeErrorModal()">Tutup</button>
                <button class="btn btn-error-retry" onclick="retryDelete()">Coba Lagi</button>
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
                        <linearGradient id="successTrashGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1E40AF;stop-opacity:1" />
                        </linearGradient>
                        <filter id="successShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <!-- Trash Can Body -->
                    <rect x="25" y="35" width="50" height="45" rx="3" fill="url(#successTrashGradient)" filter="url(#successShadow)"/>
                    <!-- Trash Can Lid -->
                    <rect x="20" y="30" width="60" height="8" rx="4" fill="url(#successTrashGradient)" filter="url(#successShadow)"/>
                    <!-- Lid Handle -->
                    <rect x="45" y="25" width="10" height="8" rx="2" fill="url(#successTrashGradient)" filter="url(#successShadow)"/>
                    <!-- Lid Slightly Open -->
                    <rect x="20" y="32" width="60" height="2" rx="1" fill="#1E40AF" opacity="0.3"/>
                    <!-- Success Checkmark Circle -->
                    <circle cx="75" cy="65" r="12" fill="#10B981" filter="url(#successShadow)"/>
                    <path d="M70 65 L73 68 L80 61" stroke="white" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 class="success-modal-title">Berhasil</h2>
            
            <!-- Description -->
            <p id="successModalDescription" class="success-modal-description">
                0 data berhasil disembunyikan dari halaman ini dan tetap aman tersimpan di database.
            </p>
        </div>
    </div>
</div>

@include('company.customers.partials.create-modal')

<!-- Add Building Modal -->
<div id="addBuildingModal" class="modal-overlay" onclick="closeAddBuildingModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Add New Building</h2>
            <button class="modal-close" onclick="closeAddBuildingModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p class="text-gray-600 mb-6 text-center">Lengkapi informasi gedung di bawah ini</p>
            <form id="addBuildingForm">
                <div class="form-group">
                    <label class="form-label">Building Name *</label>
                    <input type="text" name="name" class="form-input" placeholder="Enter building name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Address *</label>
                    <textarea name="address_1" id="building_address_input" class="form-input form-textarea" placeholder="Enter building address" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Province *</label>
                    <select name="province_id" id="province_select" class="form-input no-select2" required>
                        <option value="">Select Province</option>
                        @foreach($provinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">City *</label>
                    <select name="city_id" id="city_select" class="form-input no-select2" required disabled>
                        <option value="">Select City</option>
                    </select>
                    <small class="text-gray-500">Silakan pilih provinsi terlebih dahulu</small>
                </div>
                <div class="form-group">
                    <label class="form-label">District *</label>
                    <select name="district_id" id="district_select" class="form-input no-select2" required disabled>
                        <option value="">Select District</option>
                    </select>
                    <small class="text-gray-500">Silakan pilih kota terlebih dahulu</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Subdistrict *</label>
                    <select name="subdistrict_id" id="subdistrict_select" class="form-input no-select2" required disabled>
                        <option value="">Select Subdistrict</option>
                    </select>
                    <small class="text-gray-500">Silakan pilih kecamatan terlebih dahulu</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Postal Code *</label>
                    <input type="text" name="postal_code" id="postal_code_input" class="form-input" placeholder="Auto-filled from subdistrict" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone_1" class="form-input" placeholder="Masukkan nomor telepon">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <div class="flex justify-center gap-6">
                <button type="button" class="btn btn-outline" onclick="closeAddBuildingModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitAddBuildingForm()">Simpan Gedung</button>
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
    fetch(`/marketing/pipeline/${id}`)
        .then(response => response.json())
        .then(response => {
            const data = response.data;
            document.getElementById('modalTitle').textContent = 'Pipeline Details';
            document.getElementById('modalBody').innerHTML = `
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="detail-item">
                            <label class="form-label">Nama Perusahaan (Customer)</label>
                            <p class="detail-value">${data.company_name || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Building</label>
                            <p class="detail-value">${data.building ? data.building.name : 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Alamat Perusahaan</label>
                            <p class="detail-value">${data.company_address || 'N/A'}</p>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="detail-item">
                            <label class="form-label">Nama PIC</label>
                            <p class="detail-value">${data.pic_name || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Nomor Kontak</label>
                            <p class="detail-value">${data.pic_phone || 'N/A'}</p>
                        </div>
                        <div class="detail-item">
                            <label class="form-label">Email Kontak</label>
                            <p class="detail-value">${data.pic_email || 'N/A'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="detail-item">
                    <label class="form-label">Visit Date</label>
                    <p class="detail-value">${data.visit_date ? formatDateWithThreeDigitMonth(new Date(data.visit_date)) : 'N/A'}</p>
                </div>
                
                <div class="detail-item">
                    <label class="form-label">Reference</label>
                    <p class="detail-value">${data.reference || data.reference_user?.name || 'N/A'}</p>
                </div>
                
                <div class="detail-item">
                    <label class="form-label">Follow-up Date</label>
                    <p class="detail-value">${data.follow_up_date ? formatDateWithThreeDigitMonth(new Date(data.follow_up_date)) : '-'}</p>
                </div>
                
                <div class="detail-item">
                    <label class="form-label">Kegiatan/Agenda List</label>
                    <div class="detail-value">
                        ${data.agenda_list && data.agenda_list.length > 0 ? 
                            `<ul class="list-disc list-inside space-y-1">
                                ${data.agenda_list.map(item => `<li>${item}</li>`).join('')}
                            </ul>` : 
                            'N/A'
                        }
                    </div>
                </div>
                <!-- Selected Documents Section -->
                ${data.quotation_details || data.survey_details || data.contract_details || data.job_advice_details ? `
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <label class="form-label" style="font-size: 18px; font-weight: 600; color: #214589; margin-bottom: 16px; display: block;">
                        📌 Selected Documents for Discussion
                    </label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                        
                        <!-- Selected Quotations -->
                        ${data.quotation_details && data.quotation_details.length > 0 ? `
                        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background-color: #f9fafb;">
                            <h6 style="font-weight: 600; color: #214589; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 20px;">💰</span> Quotations (${data.quotation_details.length})
                            </h6>
                            <ul class="list-disc list-inside space-y-1">
                                ${data.quotation_details.map(quotation => `
                                    <li>
                                        <a href="/marketing/quotations/${quotation.id}" class="contract-link" style="color: #2563eb; text-decoration: underline; cursor: pointer; font-weight: 500;" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();">
                                            ${quotation.quotation_number}
                                        </a>
                                        ${quotation.customer ? ` - ${quotation.customer.name}` : ''}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                        ` : ''}
                        
                        <!-- Selected Surveys -->
                        ${data.survey_details && data.survey_details.length > 0 ? `
                        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background-color: #f9fafb;">
                            <h6 style="font-weight: 600; color: #214589; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 20px;">📊</span> Surveys (${data.survey_details.length})
                            </h6>
                            <ul class="list-disc list-inside space-y-1">
                                ${data.survey_details.map(survey => `
                                    <li>
                                        <a href="/operational/surveys/${survey.id}" class="contract-link" style="color: #2563eb; text-decoration: underline; cursor: pointer; font-weight: 500;" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();">
                                            ${survey.survey_number}
                                        </a>
                                        ${survey.customer ? ` - ${survey.customer.name}` : ''}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                        ` : ''}
                        
                        <!-- Selected Contracts -->
                        ${data.contract_details && data.contract_details.length > 0 ? `
                        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background-color: #f9fafb;">
                            <h6 style="font-weight: 600; color: #214589; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 20px;">📝</span> Contracts (${data.contract_details.length})
                            </h6>
                            <ul class="list-disc list-inside space-y-1">
                                ${data.contract_details.map(contract => `
                                    <li>
                                        <a href="/marketing/contracts/${contract.id}" class="contract-link" style="color: #2563eb; text-decoration: underline; cursor: pointer; font-weight: 500;" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();">
                                            ${contract.contract_number}
                                        </a>
                                        ${contract.customer ? ` - ${contract.customer.name}` : ''}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                        ` : ''}
                        
                        <!-- Selected Job Advices -->
                        ${data.job_advice_details && data.job_advice_details.length > 0 ? `
                        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background-color: #f9fafb;">
                            <h6 style="font-weight: 600; color: #214589; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 20px;">🔧</span> Job Advices (${data.job_advice_details.length})
                            </h6>
                            <ul class="list-disc list-inside space-y-1">
                                ${data.job_advice_details.map(job => `
                                    <li>
                                        <a href="/operational/job-advices/${job.id}" class="contract-link" style="color: #2563eb; text-decoration: underline; cursor: pointer; font-weight: 500;" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();">
                                            ${job.job_advice_number}
                                        </a>
                                        ${job.customer ? ` - ${job.customer.name}` : ''}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                        ` : ''}
                        
                    </div>
                    <small style="color: #6b7280; display: block; margin-top: 12px; font-style: italic;">
                        ℹ️ These are specific documents selected for discussion in this meeting
                    </small>
                </div>
                ` : ''}
                
                <div class="detail-item">
                    <label class="form-label">Additional Notes</label>
                    <p class="detail-value">${data.notes || '-'}</p>
                </div>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
                </div>
            `;
            openModal();
        })
        .catch(error => {
            console.error('Error loading pipeline data:', error);
            showErrorDialog('Gagal memuat data pipeline.');
        });
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add New Pipeline';
    document.getElementById('modalBody').innerHTML = `
        <p class="text-gray-600 mb-6 text-center">Let's add your new pipeline company details and make sure nothing gets missed.</p>
        <form id="createForm">
            <!-- Customer Dropdown with Add New -->
            <div class="form-group">
                <label class="form-label">Nama Perusahaan (Customer) *</label>
                <div style="display: flex; gap: 8px;">
                    <div style="flex: 1; min-width: 0;">
                        <select class="form-input select2-customer" id="customer_id" name="customer_id" style="width: 100%;" required>
                            <option value="">Pilih atau ketik disini..</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-success btn-sm" onclick="openCreateCustomerModal()" style="height: 38px; width: 40px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <input type="hidden" name="company_name" id="company_name">
            </div>

            <!-- Nama PIC -->
            <div class="form-group">
                <label class="form-label">Nama PIC *</label>
                <div style="display: flex; gap: 8px;">
                    <div style="flex: 1; min-width: 0;">
                        <select class="form-input select2-contact" id="pic_name_select" name="pic_name_select" style="width: 100%;" required>
                            <option value="">Pilih customer dulu..</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-success btn-sm" onclick="openQuickContactModal()" style="height: 38px; width: 40px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <input type="hidden" name="pic_name" id="pic_name_hidden">
            </div>

            <!-- Nomor Kontak -->
            <div class="form-group">
                <label class="form-label">Nomor Kontak *</label>
                <input type="tel" name="pic_phone" id="pic_phone" class="form-input" placeholder="Phone Number of the contact person" required>
            </div>

            <!-- Email Kontak -->
            <div class="form-group">
                <label class="form-label">Email Kontak *</label>
                <input type="email" name="pic_email" id="pic_email" class="form-input" placeholder="Email of the contact person" required>
            </div>

            <!-- Building Dropdown with Add New -->
            <div class="form-group">
                <label class="form-label">Building</label>
                <div style="display: flex; gap: 8px;">
                    <div style="flex: 1; min-width: 0;">
                        <select class="form-input select2-building" id="building_id" name="building_id" style="width: 100%;">
                            <option value="">Pilih atau ketik disini..</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}" data-name="{{ $building->name }}" data-address="{{ $building->alamat_1 ?? $building->address }}" data-address2="{{ $building->alamat_2 }}">{{ $building->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-success btn-sm" onclick="showAddBuildingModal()" style="height: 38px; width: 40px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <small class="text-gray-500">Pilih building atau isi alamat manual di bawah</small>
            </div>

            <!-- Alamat Perusahaan -->
            <div class="form-group">
                <label class="form-label">Alamat Perusahaan *</label>
                <textarea name="company_address" id="company_address" class="form-input form-textarea" placeholder="Alamat akan terisi otomatis saat pilih building, atau isi manual" required></textarea>
            </div>
            
            <!-- Visit Date -->
            <div class="form-group">
                <label class="form-label">Visit Date *</label>
                <input type="date" name="visit_date" class="form-input" value="${new Date().toISOString().split('T')[0]}" required>
            </div>
            
            <!-- Reference Manual Input (changed from dropdown) -->
            <div class="form-group">
                <label class="form-label">Reference</label>
                <input type="text" name="reference" class="form-input" placeholder="Enter reference name (optional)">
                <small class="text-gray-500">Enter the name who referred this prospect</small>
            </div>
            
            <!-- Follow Up Date - Hidden saat create -->
            <div class="form-group" style="display: none;" id="followUpDateGroup">
                <label class="form-label">Follow Up Date</label>
                <input type="date" name="follow_up_date" class="form-input">
                <small class="text-gray-500">This field will be available after the pipeline is created</small>
            </div>
            
            <!-- Agenda List Field -->
            <div class="form-group">
                <label class="form-label">Kegiatan/Agenda List *</label>
                <div id="agendaListContainer">
                    <div class="agenda-item flex gap-2 mb-2">
                        <input type="text" name="agenda_list[]" class="form-input flex-1" placeholder="Enter agenda item" required>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeAgendaItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="addAgendaItem()">+ Add New Agenda</button>
                <small class="text-gray-500">Add multiple agenda items for this visit</small>
            </div>
            
            <!-- Document Lists Section -->
            <div class="form-group">
                <label class="form-label" style="font-size: 16px; font-weight: 600; color: #214589; margin-bottom: 8px;">📄 Related Documents (Optional)</label>
                <small class="text-gray-500" style="display: block; margin-bottom: 16px;">⚠️ Silakan pilih customer terlebih dahulu sebelum menambahkan dokumen</small>
                
                <!-- Quotation List -->
                <div class="document-section" style="margin-bottom: 16px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: #f9fafb;">
                    <label class="form-label" style="font-size: 14px; font-weight: 600; color: #214589; margin-bottom: 8px;">💰 Quotation List</label>
                    <div id="quotationListContainer">
                        <div class="quotation-item flex gap-2 mb-2">
                            <select name="quotation_list[]" class="form-input flex-1 quotation-select" disabled>
                                <option value="">Select customer first</option>
                                @foreach($quotations as $quotation)
                                    <option value="{{ $quotation->id }}" data-customer-id="{{ $quotation->customer_id }}">{{ $quotation->quotation_number }} - {{ $quotation->customer->name ?? 'N/A' }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="addQuotationItem()">+ Add Quotation</button>
                </div>
                
                <!-- Survey List -->
                <div class="document-section" style="margin-bottom: 16px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: #f9fafb;">
                    <label class="form-label" style="font-size: 14px; font-weight: 600; color: #214589; margin-bottom: 8px;">📊 Survey List</label>
                    <div id="surveyListContainer">
                        <div class="survey-item flex gap-2 mb-2">
                            <select name="survey_list[]" class="form-input flex-1 survey-select" disabled>
                                <option value="">Select customer first</option>
                                @foreach($surveys as $survey)
                                    <option value="{{ $survey->id }}" data-customer-id="{{ $survey->customer_id }}">{{ $survey->survey_number }} - {{ $survey->customer->name ?? 'N/A' }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="addSurveyItem()">+ Add Survey</button>
                </div>
                
                <!-- Contract List -->
                <div class="document-section" style="margin-bottom: 16px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: #f9fafb;">
                    <label class="form-label" style="font-size: 14px; font-weight: 600; color: #214589; margin-bottom: 8px;">📝 Contract List</label>
                    <div id="contractListContainer">
                        <div class="contract-item flex gap-2 mb-2">
                            <select name="contract_list[]" class="form-input flex-1 contract-select" disabled>
                                <option value="">Select customer first</option>
                                @foreach($contracts as $contract)
                                    <option value="{{ $contract->id }}" data-customer-id="{{ $contract->customer_id }}">{{ $contract->contract_number }} - {{ $contract->customer->name ?? 'N/A' }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="addContractItem()">+ Add Contract</button>
                </div>
                
                <!-- Job Advice List -->
                <div class="document-section" style="margin-bottom: 16px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: #f9fafb;">
                    <label class="form-label" style="font-size: 14px; font-weight: 600; color: #214589; margin-bottom: 8px;">🔧 Job Advice List</label>
                    <div id="jobAdviceListContainer">
                        <div class="job-advice-item flex gap-2 mb-2">
                            <select name="job_advice_list[]" class="form-input flex-1 job-advice-select" disabled>
                                <option value="">Select customer first</option>
                                @foreach($job_advices as $job_advice)
                                    <option value="{{ $job_advice->id }}" data-customer-id="{{ $job_advice->customer_id }}">{{ $job_advice->job_advice_number }} - {{ $job_advice->customer->name ?? 'N/A' }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" onclick="addJobAdviceItem()">+ Add Job Advice</button>
                </div>
            </div>
            
            <!-- Additional Notes -->
            <div class="form-group">
                <label class="form-label">Additional Notes</label>
                <textarea name="notes" class="form-input form-textarea" placeholder="Any additional notes or observations"></textarea>
            </div>
        </form>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <div class="flex justify-center gap-6">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Simpan Pipeline</button>
        </div>
    `;
    openModal();
    
    // Initialize Select2 after modal is opened
    setTimeout(() => {
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2-customer').select2({
                placeholder: 'Pilih atau ketik untuk mencari customer...',
                allowClear: true,
                minimumInputLength: 0,
                dropdownParent: $('body'),
                ajax: {
                    url: '{{ route("marketing.pipeline.customers.search") }}',
                    dataType: 'json',
                    delay: 250,
                    cache: false,
                    data: function(params) {
                        return { q: params.term || '' };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data || [], function(customer) {
                                return {
                                    id: customer.id,
                                    text: customer.text || customer.name,
                                    name: customer.name
                                };
                            })
                        };
                    }
                }
            });

            // Stash the plain customer name (without NPWP/address) onto the
            // selected <option> so the 'change' handler below can read it via
            // data('name') for both AJAX-searched and manually-created customers.
            $('#customer_id').on('select2:select', function(e) {
                const data = e.params.data;
                if (data && data.name) {
                    $(this).find('option[value="' + data.id + '"]').attr('data-name', data.name);
                }
            });
            
            $('.select2-building').select2({
                placeholder: 'Pilih atau ketik untuk mencari building...',
                allowClear: true,
                dropdownParent: $('body')
            });

            $('.select2-contact').select2({
                placeholder: 'Pilih kontak...',
                allowClear: true,
                dropdownParent: $('body')
            });
            
            // Apply filter if customer is already selected when modal opens
            const customerSelect = document.getElementById('customer_id');
            if (customerSelect) {
                filterDocumentsByCustomer(customerSelect.value);
                filterContactsByCustomer(customerSelect.value);
            }
            
            // Update hidden company_name when customer is selected
            $('#customer_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const customerName = selectedOption.data('name') || selectedOption.text() || '';
                const customerId = $(this).val();
                
                if (customerName && customerName !== 'Pilih atau ketik disini..') {
                    $('#company_name').val(customerName);
                    console.log('Customer selected, company_name set to:', customerName);
                } else {
                    $('#company_name').val('');
                }
                
                // ✅ FILTER ALL DOCUMENT DROPDOWNS BASED ON SELECTED CUSTOMER
                filterDocumentsByCustomer(customerId);
                // ✅ FILTER CONTACTS BASED ON SELECTED CUSTOMER
                filterContactsByCustomer(customerId);
            });

            // Update PIC details when contact is selected
            $('#pic_name_select').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const picName = $(this).val();
                const phone = selectedOption.data('phone') || '';
                const email = selectedOption.data('email') || '';
                
                $('#pic_name_hidden').val(picName);
                $('#pic_phone').val(phone);
                $('#pic_email').val(email);
            });
            
            // Update company_address textarea when building is selected
            $('#building_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const addr1 = selectedOption.attr('data-address') || '';
                const addr2 = selectedOption.attr('data-address2') || '';
                
                let fullAddress = addr1;
                if (addr2) {
                    fullAddress = addr1 ? (addr1 + ', ' + addr2) : addr2;
                }
                
                if (fullAddress) {
                    $('#company_address').val(fullAddress);
                    console.log('Building selected, address:', fullAddress);
                }
            });
        }
    }, 100);
}

function openEditModal(id) {
    // Load data via AJAX
    fetch(`/marketing/pipeline/${id}/edit`)
        .then(response => response.json())
        .then(response => {
            const data = response.data;
            document.getElementById('modalTitle').textContent = 'Edit Pipeline';
            document.getElementById('modalBody').innerHTML = `
                <p class="text-gray-600 mb-6 text-center">Update your pipeline company details and make sure nothing gets missed.</p>
                <form id="editForm">
                    <input type="hidden" name="id" value="${data.id}">
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <!-- Customer Name (read-only, just display) -->
                            <div class="form-group">
                                <label class="form-label">Nama Perusahaan (Customer) *</label>
                                <input type="text" name="company_name" class="form-input" value="${data.company_name || ''}" required readonly style="background-color: #f3f4f6;">
                                <input type="hidden" name="customer_id" value="${data.customer_id || ''}">
                            </div>
                            
                            <!-- Building Dropdown (editable) -->
                            <div class="form-group">
                                <label class="form-label">Building</label>
                                <div style="display: flex; gap: 8px;">
                                    <div style="flex: 1; min-width: 0;">
                                        <select class="form-input select2-building-edit" id="building_id_edit" name="building_id" style="width: 100%;">
                                            <option value="">Pilih atau ketik disini..</option>
                                            @foreach($buildings as $building)
                                                <option value="{{ $building->id }}" data-name="{{ $building->name }}" data-address="{{ $building->alamat_1 ?? $building->address }}" data-address2="{{ $building->alamat_2 }}" ${data.building_id == {{ $building->id }} ? 'selected' : ''}>{{ $building->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm" onclick="showAddBuildingModal()" style="height: 38px; width: 40px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <small class="text-gray-500">Pilih building atau isi alamat manual di bawah</small>
                            </div>
                            
                            <!-- Company Address (auto-filled or manual) -->
                            <div class="form-group">
                                <label class="form-label">Alamat Perusahaan *</label>
                                <textarea name="company_address" id="company_address_edit" class="form-input form-textarea" placeholder="Alamat akan terisi otomatis saat pilih building, atau isi manual" required>${data.company_address || ''}</textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <!-- PIC Name -->
                            <div class="form-group">
                                <label class="form-label">Nama PIC *</label>
                                <div style="display: flex; gap: 8px;">
                                    <div style="flex: 1; min-width: 0;">
                                        <select class="form-input select2-contact-edit" id="pic_name_select_edit" name="pic_name_select" style="width: 100%;" required>
                                            <option value="">Pilih kontak..</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm" onclick="openQuickContactModal('edit')" style="height: 38px; width: 40px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="pic_name" id="pic_name_hidden_edit" value="${data.pic_name || ''}">
                            </div>
                            
                            <!-- Nomor Kontak -->
                            <div class="form-group">
                                <label class="form-label">Nomor Kontak *</label>
                                <input type="tel" name="pic_phone" id="pic_phone_edit" class="form-input" placeholder="Phone Number of the contact person" value="${data.pic_phone || ''}" required>
                            </div>
                            
                            <!-- Email Kontak -->
                            <div class="form-group">
                                <label class="form-label">Email Kontak *</label>
                                <input type="email" name="pic_email" id="pic_email_edit" class="form-input" placeholder="Email of the contact person" value="${data.pic_email || ''}" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Visit Date -->
                    <div class="form-group">
                        <label class="form-label">Visit Date *</label>
                        <input type="date" name="visit_date" class="form-input" value="${data.visit_date ? data.visit_date.split('T')[0] : ''}" required>
                    </div>
                    
                    <!-- Reference Manual Input -->
                    <div class="form-group">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-input" placeholder="Enter reference name (optional)" value="${data.reference || (data.reference_user ? data.reference_user.name : '')}">
                        <small class="text-gray-500">Enter the name who referred this prospect</small>
                    </div>
                    
                    <!-- Follow Up Date (NOT HIDDEN in edit mode) -->
                    <div class="form-group">
                        <label class="form-label">Follow Up Date</label>
                        <input type="date" name="follow_up_date" class="form-input" value="${data.follow_up_date ? data.follow_up_date.split('T')[0] : ''}">
                        <small class="text-gray-500">Set the next follow-up date for this pipeline</small>
                    </div>
                    
                    <!-- Agenda List Field -->
                    <div class="form-group">
                        <label class="form-label">Kegiatan/Agenda List *</label>
                        <div id="agendaListContainer">
                            ${data.agenda_list && data.agenda_list.length > 0 ? 
                                data.agenda_list.map(item => `
                                    <div class="agenda-item flex gap-2 mb-2">
                                        <input type="text" name="agenda_list[]" class="form-input flex-1" placeholder="Enter agenda item" value="${item}" required>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeAgendaItem(this)">Remove</button>
                                    </div>
                                `).join('') :
                                `<div class="agenda-item flex gap-2 mb-2">
                                    <input type="text" name="agenda_list[]" class="form-input flex-1" placeholder="Enter agenda item" required>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="removeAgendaItem(this)">Remove</button>
                                </div>`
                            }
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="addAgendaItem()">+ Add New Agenda</button>
                        <small class="text-gray-500">Add multiple agenda items for this visit</small>
                    </div>
                    
                    <!-- Document Lists Section -->
                    <div class="form-group">
                        <label class="form-label" style="font-size: 16px; font-weight: 600; color: #214589; margin-bottom: 8px;">📄 Related Documents (Optional)</label>
                        <small class="text-gray-500" style="display: block; margin-bottom: 16px;">⚠️ Documents filtered for ${data.customer ? data.customer.name : 'this customer'}</small>
                        
                        <!-- Quotation List -->
                        <div class="document-section" style="margin-bottom: 16px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: #f9fafb;">
                            <label class="form-label" style="font-size: 14px; font-weight: 600; color: #214589; margin-bottom: 8px;">💰 Quotation List</label>
                            <div id="quotationListContainer">
                                ${data.quotation_list && data.quotation_list.length > 0 ? 
                                    data.quotation_list.map(item => `
                                        <div class="quotation-item flex gap-2 mb-2">
                                            <select name="quotation_list[]" class="form-input flex-1 quotation-select">
                                                <option value="">Select Quotation</option>
                                                @foreach($quotations as $quotation)
                                                    <option value="{{ $quotation->id }}" data-customer-id="{{ $quotation->customer_id }}" ${item == {{ $quotation->id }} ? 'selected' : ''}>{{ $quotation->quotation_number }} - {{ $quotation->customer->name ?? 'N/A' }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                                        </div>
                                    `).join('') :
                                    `<div class="quotation-item flex gap-2 mb-2">
                                        <select name="quotation_list[]" class="form-input flex-1 quotation-select">
                                            <option value="">Select Quotation</option>
                                            @foreach($quotations as $quotation)
                                                <option value="{{ $quotation->id }}" data-customer-id="{{ $quotation->customer_id }}">{{ $quotation->quotation_number }} - {{ $quotation->customer->name ?? 'N/A' }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                                    </div>`
                                }
                            </div>
                            <button type="button" class="btn btn-outline btn-sm" onclick="addQuotationItem()">+ Add Quotation</button>
                        </div>
                        
                        <!-- Survey List -->
                        <div class="document-section" style="margin-bottom: 16px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: #f9fafb;">
                            <label class="form-label" style="font-size: 14px; font-weight: 600; color: #214589; margin-bottom: 8px;">📊 Survey List</label>
                            <div id="surveyListContainer">
                                ${data.survey_list && data.survey_list.length > 0 ? 
                                    data.survey_list.map(item => `
                                        <div class="survey-item flex gap-2 mb-2">
                                            <select name="survey_list[]" class="form-input flex-1 survey-select">
                                                <option value="">Select Survey</option>
                                                @foreach($surveys as $survey)
                                                    <option value="{{ $survey->id }}" data-customer-id="{{ $survey->customer_id }}" ${item == {{ $survey->id }} ? 'selected' : ''}>{{ $survey->survey_number }} - {{ $survey->customer->name ?? 'N/A' }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                                        </div>
                                    `).join('') :
                                    `<div class="survey-item flex gap-2 mb-2">
                                        <select name="survey_list[]" class="form-input flex-1 survey-select">
                                            <option value="">Select Survey</option>
                                            @foreach($surveys as $survey)
                                                <option value="{{ $survey->id }}" data-customer-id="{{ $survey->customer_id }}">{{ $survey->survey_number }} - {{ $survey->customer->name ?? 'N/A' }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                                    </div>`
                                }
                            </div>
                            <button type="button" class="btn btn-outline btn-sm" onclick="addSurveyItem()">+ Add Survey</button>
                        </div>
                        
                        <!-- Contract List -->
                        <div class="document-section" style="margin-bottom: 16px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: #f9fafb;">
                            <label class="form-label" style="font-size: 14px; font-weight: 600; color: #214589; margin-bottom: 8px;">📝 Contract List</label>
                            <div id="contractListContainer">
                                ${data.contract_list && data.contract_list.length > 0 ? 
                                    data.contract_list.map(item => `
                                        <div class="contract-item flex gap-2 mb-2">
                                            <select name="contract_list[]" class="form-input flex-1 contract-select">
                                                <option value="">Select Contract</option>
                                                @foreach($contracts as $contract)
                                                    <option value="{{ $contract->id }}" data-customer-id="{{ $contract->customer_id }}" ${item == {{ $contract->id }} ? 'selected' : ''}>{{ $contract->contract_number }} - {{ $contract->customer->name ?? 'N/A' }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                                        </div>
                                    `).join('') :
                                    `<div class="contract-item flex gap-2 mb-2">
                                        <select name="contract_list[]" class="form-input flex-1 contract-select">
                                            <option value="">Select Contract</option>
                                            @foreach($contracts as $contract)
                                                <option value="{{ $contract->id }}" data-customer-id="{{ $contract->customer_id }}">{{ $contract->contract_number }} - {{ $contract->customer->name ?? 'N/A' }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                                    </div>`
                                }
                            </div>
                            <button type="button" class="btn btn-outline btn-sm" onclick="addContractItem()">+ Add Contract</button>
                        </div>
                        
                        <!-- Job Advice List -->
                        <div class="document-section" style="margin-bottom: 16px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: #f9fafb;">
                            <label class="form-label" style="font-size: 14px; font-weight: 600; color: #214589; margin-bottom: 8px;">🔧 Job Advice List</label>
                            <div id="jobAdviceListContainer">
                                ${data.job_advice_list && data.job_advice_list.length > 0 ? 
                                    data.job_advice_list.map(item => `
                                        <div class="job-advice-item flex gap-2 mb-2">
                                            <select name="job_advice_list[]" class="form-input flex-1 job-advice-select">
                                                <option value="">Select Job Advice</option>
                                                @foreach($job_advices as $job_advice)
                                                    <option value="{{ $job_advice->id }}" data-customer-id="{{ $job_advice->customer_id }}" ${item == {{ $job_advice->id }} ? 'selected' : ''}>{{ $job_advice->job_advice_number }} - {{ $job_advice->customer->name ?? 'N/A' }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                                        </div>
                                    `).join('') :
                                    `<div class="job-advice-item flex gap-2 mb-2">
                                        <select name="job_advice_list[]" class="form-input flex-1 job-advice-select">
                                            <option value="">Select Job Advice</option>
                                            @foreach($job_advices as $job_advice)
                                                <option value="{{ $job_advice->id }}" data-customer-id="{{ $job_advice->customer_id }}">{{ $job_advice->job_advice_number }} - {{ $job_advice->customer->name ?? 'N/A' }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
                                    </div>`
                                }
                            </div>
                            <button type="button" class="btn btn-outline btn-sm" onclick="addJobAdviceItem()">+ Add Job Advice</button>
                        </div>
                    </div>
                    
                    <!-- Additional Notes -->
                    <div class="form-group">
                        <label class="form-label">Additional Notes</label>
                        <textarea name="notes" class="form-input form-textarea" placeholder="Any additional notes or observations">${data.notes || ''}</textarea>
                    </div>
                </form>
            `;
            document.getElementById('modalFooter').innerHTML = `
                <div class="flex justify-center gap-6">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Batal</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Perbarui Pipeline</button>
                </div>
            `;
            openModal();
            
            // Initialize Select2 for building dropdown in edit modal
            setTimeout(() => {
                if ($('.select2-building-edit').length > 0) {
                    $('.select2-building-edit').select2({
                        dropdownParent: $('body'),
                        placeholder: 'Pilih atau ketik disini..',
                        allowClear: true
                    });
                    
                    // Event listener for building change in edit modal
                    $('#building_id_edit').on('change', function() {
                        const selectedOption = $(this).find('option:selected');
                        const addr1 = selectedOption.attr('data-address') || '';
                        const addr2 = selectedOption.attr('data-address2') || '';
                        
                        let fullAddress = addr1;
                        if (addr2) {
                            fullAddress = addr1 ? (addr1 + ', ' + addr2) : addr2;
                        }
                        
                        if (fullAddress) {
                            $('#company_address_edit').val(fullAddress);
                        }
                    });
                }

                if ($('.select2-contact-edit').length > 0) {
                    $('.select2-contact-edit').select2({
                        dropdownParent: $('body'),
                        placeholder: 'Pilih kontak..',
                        allowClear: true
                    });

                    // Update PIC details when contact is selected in edit modal
                    $('#pic_name_select_edit').on('change', function() {
                        const selectedOption = $(this).find('option:selected');
                        const picName = $(this).val();
                        const phone = selectedOption.data('phone') || '';
                        const email = selectedOption.data('email') || '';
                        
                        $('#pic_name_hidden_edit').val(picName);
                        $('#pic_phone_edit').val(phone);
                        $('#pic_email_edit').val(email);
                    });
                }
                
                // ✅ FILTER CONTRACTS & CONTACTS BY CUSTOMER IN EDIT MODAL
                if (data.customer_id) {
                    filterContractsByCustomer(data.customer_id);
                    filterContactsByCustomer(data.customer_id, true);
                }
            }, 100);
        })
        .catch(error => {
            console.error('Error loading pipeline data:', error);
            showErrorDialog('Gagal memuat data pipeline.');
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

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    // Debug: Log form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }
    
    // Ensure agenda_list is properly formatted
    const agendaInputs = form.querySelectorAll('input[name="agenda_list[]"]');
    const agendaList = Array.from(agendaInputs).map(input => input.value).filter(value => value.trim() !== '');
    console.log('Agenda list:', agendaList);
    
    // Validate Agenda List (Must have at least one)
    if (agendaList.length === 0) {
        showWarningDialog('Masukkan minimal satu agenda.');
        return;
    }

    // ✅ VALIDATE PIC FIELDS (Must not be empty)
    if (!formData.get('pic_name') || !formData.get('pic_phone') || !formData.get('pic_email')) {
        showWarningDialog('Detail PIC berupa nama, telepon, dan email wajib diisi. Silakan pilih kontak atau buat kontak baru.');
        return;
    }

    // Ensure contract_list is properly formatted
    const contractSelects = form.querySelectorAll('select[name="contract_list[]"]');
    const contractList = Array.from(contractSelects).map(select => select.value).filter(value => value !== '');
    console.log('Contract list:', contractList);
    
    // Reconstruct FormData to have clean array
    // Remove all existing array entries first to avoid duplicates or empty strings
    formData.delete('agenda_list[]');
    agendaList.forEach(item => {
        formData.append('agenda_list[]', item);
    });

    // Clean other lists too just in case
    formData.delete('contract_list[]');
    contractList.forEach(item => {
        formData.append('contract_list[]', item);
    });

    // Store the action for retry functionality
    lastAction = submitCreateForm;
    
    fetch('/marketing/pipeline', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));
        
        if (!response.ok) {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(errorData => {
                    console.log('Error response:', errorData);
                    throw new Error(JSON.stringify(errorData));
                });
            } else {
                // Response is HTML (error page)
                return response.text().then(html => {
                    console.log('HTML error response:', html);
                    throw new Error('Server returned HTML error page instead of JSON');
                });
            }
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // Response is HTML
            return response.text().then(html => {
                console.log('HTML response:', html);
                throw new Error('Server returned HTML instead of JSON');
            });
        }
    })
    .then(data => {
        console.log('Success response:', data);
        if (data.success) {
            closeModal();
            showSuccessModal('create');
            // Reload halaman untuk menampilkan data baru
            setTimeout(() => {
                location.reload();
            }, 2000); // Reload setelah 2 detik (setelah success modal ditampilkan)
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
                    showErrorDialog('Validasi gagal: ' + JSON.stringify(errorData.errors), 'Validasi Gagal');
                } else {
                    showErrorDialog('Gagal membuat pipeline: ' + errorData.message);
                }
            } catch (e) {
                showErrorDialog('Gagal membuat pipeline: ' + error.message);
            }
        }
    });
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    formData.append('_method', 'PUT');
    const id = formData.get('id');
    
    // ✅ VALIDATE PIC FIELDS (Must not be empty)
    if (!formData.get('pic_name') || !formData.get('pic_phone') || !formData.get('pic_email')) {
        showWarningDialog('Detail PIC berupa nama, telepon, dan email wajib diisi. Silakan pilih kontak atau buat kontak baru.');
        return;
    }

    // Store the action for retry functionality
    lastAction = submitEditForm;
    
    fetch(`/marketing/pipeline/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));
        
        if (!response.ok) {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(errorData => {
                    console.log('Error response:', errorData);
                    throw new Error(JSON.stringify(errorData));
                });
            } else {
                // Response is HTML (error page)
                return response.text().then(html => {
                    console.log('HTML error response:', html);
                    throw new Error('Server returned HTML error page instead of JSON');
                });
            }
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // Response is HTML
            return response.text().then(html => {
                console.log('HTML response:', html);
                throw new Error('Server returned HTML instead of JSON');
            });
        }
    })
    .then(data => {
        console.log('Success response:', data);
        if (data.success) {
            closeModal();
            showSuccessModal('update');
            // Reload halaman untuk menampilkan data yang diupdate
            setTimeout(() => {
                location.reload();
            }, 2000); // Reload setelah 2 detik (setelah success modal ditampilkan)
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
                    showErrorDialog('Validasi gagal: ' + JSON.stringify(errorData.errors), 'Validasi Gagal');
                } else {
                    showErrorDialog('Gagal memperbarui pipeline: ' + errorData.message);
                }
            } catch (e) {
                showErrorDialog('Gagal memperbarui pipeline: ' + error.message);
            }
        }
    });
}

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Select All functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
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
});

// Global variables
let selectedIdsForRetry = [];
let successModalTimer = null;
let lastAction = null;

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        showWarningDialog('Pilih minimal satu item yang ingin disembunyikan.');
        return;
    }
    
    // Update modal title with count
    const count = checkboxes.length;
    document.getElementById('deleteModalTitle').textContent = `Sembunyikan ${count} data?`;
    
    // Show delete modal
    openDeleteModal();
}

// Delete modal functions
function openDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function confirmDelete() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const selectedIds = Array.from(checkboxes).map(checkbox => {
        return checkbox.value;
    });
    
    if (selectedIds.length === 0) {
        showWarningDialog('Belum ada item yang dipilih.');
        return;
    }
    
    // Store IDs for potential retry
    selectedIdsForRetry = selectedIds;
    
    // Show loading state
    const hideButton = document.querySelector('.btn-hide');
    const originalText = hideButton.textContent;
    hideButton.textContent = 'Menyembunyikan...';
    hideButton.disabled = true;
    
    // Send delete request
    fetch('/marketing/pipeline/bulk-delete', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ ids: selectedIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeDeleteModal();
            showSuccessModal(selectedIds.length);
            // Reload halaman untuk menampilkan data yang terupdate
            setTimeout(() => {
                location.reload();
            }, 2000); // Reload setelah 2 detik (setelah success modal ditampilkan)
        } else {
            // Show error modal instead of alert
            closeDeleteModal();
            showErrorModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error modal instead of alert
        closeDeleteModal();
        showErrorModal();
    })
    .finally(() => {
        // Reset button state
        hideButton.textContent = originalText;
        hideButton.disabled = false;
    });
}

// Success modal functions
function showSuccessModal(type) {
    const title = document.querySelector('.success-modal-title');
    const description = document.getElementById('successModalDescription');
    
    if (typeof type === 'number') {
        // For delete operations
        description.textContent = `${type} data berhasil disembunyikan dari halaman ini dan tetap aman tersimpan di database.`;
    } else if (type === 'create') {
        title.textContent = 'Pipeline Berhasil Dibuat';
        description.textContent = 'Pipeline baru berhasil ditambahkan dan sekarang bisa dipantau progresnya.';
    } else if (type === 'update') {
        title.textContent = 'Pipeline Berhasil Diperbarui';
        description.textContent = 'Informasi pipeline berhasil diperbarui.';
    }
    
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
    
    // Clear timer if exists
    if (successModalTimer) {
        clearTimeout(successModalTimer);
        successModalTimer = null;
    }
}

// Connection Error Modal
function showConnectionErrorModal() {
    document.getElementById('connectionErrorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeConnectionErrorModal() {
    document.getElementById('connectionErrorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retryLastAction() {
    closeConnectionErrorModal();
    if (lastAction) {
        lastAction();
    }
}

// Update Error Modal
function showUpdateErrorModal() {
    document.getElementById('updateErrorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeUpdateErrorModal() {
    document.getElementById('updateErrorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Error modal functions
function showErrorModal() {
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retryDelete() {
    closeErrorModal();
    
    if (selectedIdsForRetry.length === 0) {
        showWarningDialog('Tidak ada item yang bisa dicoba ulang.');
        return;
    }
    
    // Show loading state
    const hideButton = document.querySelector('.btn-hide');
    const originalText = hideButton.textContent;
    hideButton.textContent = 'Menyembunyikan...';
    hideButton.disabled = true;
    
    // Send delete request again
    fetch('/marketing/pipeline/bulk-delete', {
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
            closeDeleteModal();
            showSuccessModal(selectedIdsForRetry.length);
            // Reload halaman untuk menampilkan data yang terupdate
            setTimeout(() => {
                location.reload();
            }, 2000); // Reload setelah 2 detik (setelah success modal ditampilkan)
        } else {
            // Show error modal again
            showErrorModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error modal again
        showErrorModal();
    })
    .finally(() => {
        // Reset button state
        hideButton.textContent = originalText;
        hideButton.disabled = false;
    });
}

// Agenda List Management Functions
function addAgendaItem() {
    console.log('Adding agenda item...');
    const container = document.getElementById('agendaListContainer');
    if (!container) {
        console.error('Agenda list container not found');
        return;
    }
    const newItem = document.createElement('div');
    newItem.className = 'agenda-item flex gap-2 mb-2';
    newItem.innerHTML = `
        <input type="text" name="agenda_list[]" class="form-input flex-1" placeholder="Enter agenda item" required>
        <button type="button" class="btn btn-secondary btn-sm" onclick="removeAgendaItem(this)">Remove</button>
    `;
    container.appendChild(newItem);
    console.log('Agenda item added');
}

function removeAgendaItem(button) {
    console.log('Removing agenda item...');
    const container = document.getElementById('agendaListContainer');
    if (!container) {
        console.error('Agenda list container not found');
        return;
    }
    if (container.children.length > 1) {
        button.parentElement.remove();
        console.log('Agenda item removed');
    } else {
        showWarningDialog('Minimal harus ada satu agenda.');
        console.log('Cannot remove last agenda item');
    }
}

// Document List Management Functions - Generic remove function
function removeDocumentItem(button) {
    console.log('Removing document item...');
    button.parentElement.remove();
    console.log('Document item removed');
}

// Quotation List Management
function addQuotationItem() {
    console.log('Adding quotation item...');
    const container = document.getElementById('quotationListContainer');
    if (!container) {
        console.error('Quotation list container not found');
        return;
    }
    
    // Get selected customer ID
    const selectedCustomer = document.getElementById('customer_id');
    const customerId = selectedCustomer ? selectedCustomer.value : null;
    
    const newItem = document.createElement('div');
    newItem.className = 'quotation-item flex gap-2 mb-2';
    newItem.innerHTML = `
        <select name="quotation_list[]" class="form-input flex-1 quotation-select">
            <option value="">Select Quotation</option>
            @foreach($quotations as $quotation)
                <option value="{{ $quotation->id }}" data-customer-id="{{ $quotation->customer_id }}">{{ $quotation->quotation_number }} - {{ $quotation->customer->name ?? 'N/A' }}</option>
            @endforeach
        </select>
        <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
    `;
    container.appendChild(newItem);
    
    // Store original HTML before filtering (for restoration when customer is cleared)
    const select = newItem.querySelector('.quotation-select');
    if (select) {
        const selectKey = `.quotation-select-${Date.now()}-${Math.random()}`;
        originalSelectHTML.set(selectKey, select.innerHTML);
        select.setAttribute('data-original-key', selectKey);
    }
    
    // Apply filter to remove options from other customers
    if (customerId) {
        filterDocumentsByCustomer(customerId);
    } else {
        // If no customer selected, disable the select
        if (select) {
            select.disabled = true;
            const emptyOption = select.querySelector('option[value=""]');
            if (emptyOption) {
                emptyOption.textContent = 'Select customer first';
            }
        }
    }
    console.log('Quotation item added');
}

// Survey List Management
function addSurveyItem() {
    console.log('Adding survey item...');
    const container = document.getElementById('surveyListContainer');
    if (!container) {
        console.error('Survey list container not found');
        return;
    }
    // Get selected customer ID
    const selectedCustomer = document.getElementById('customer_id');
    const customerId = selectedCustomer ? selectedCustomer.value : null;
    
    const newItem = document.createElement('div');
    newItem.className = 'survey-item flex gap-2 mb-2';
    
    // Build options HTML - only include options for selected customer
    let optionsHtml = '<option value="">Select Survey</option>';
    @foreach($surveys as $survey)
        @if($survey->customer_id)
            optionsHtml += `<option value="{{ $survey->id }}" data-customer-id="{{ $survey->customer_id }}">{{ $survey->survey_number }} - {{ $survey->customer->name ?? 'N/A' }}</option>`;
        @endif
    @endforeach
    
    newItem.innerHTML = `
        <select name="survey_list[]" class="form-input flex-1 survey-select">
            ${optionsHtml}
        </select>
        <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
    `;
    container.appendChild(newItem);
    
    // Store original HTML before filtering (for restoration when customer is cleared)
    const select = newItem.querySelector('.survey-select');
    if (select) {
        const selectKey = `.survey-select-${Date.now()}-${Math.random()}`;
        originalSelectHTML.set(selectKey, select.innerHTML);
        select.setAttribute('data-original-key', selectKey);
    }
    
    // Apply filter to remove options from other customers
    if (customerId) {
        filterDocumentsByCustomer(customerId);
    } else {
        // If no customer selected, disable the select
        if (select) {
            select.disabled = true;
            const emptyOption = select.querySelector('option[value=""]');
            if (emptyOption) {
                emptyOption.textContent = 'Select customer first';
            }
        }
    }
    console.log('Survey item added');
}

// Contract List Management
function addContractItem() {
    console.log('Adding contract item...');
    const container = document.getElementById('contractListContainer');
    if (!container) {
        console.error('Contract list container not found');
        return;
    }
    // Get selected customer ID
    const selectedCustomer = document.getElementById('customer_id');
    const customerId = selectedCustomer ? selectedCustomer.value : null;
    
    const newItem = document.createElement('div');
    newItem.className = 'contract-item flex gap-2 mb-2';
    
    // Build options HTML - only include options for selected customer
    let optionsHtml = '<option value="">Select Contract</option>';
    @foreach($contracts as $contract)
        @if($contract->customer_id)
            optionsHtml += `<option value="{{ $contract->id }}" data-customer-id="{{ $contract->customer_id }}">{{ $contract->contract_number }} - {{ $contract->customer->name ?? 'N/A' }}</option>`;
        @endif
    @endforeach
    
    newItem.innerHTML = `
        <select name="contract_list[]" class="form-input flex-1 contract-select">
            ${optionsHtml}
        </select>
        <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
    `;
    container.appendChild(newItem);
    
    // Apply filter to remove options from other customers
    if (customerId) {
        filterDocumentsByCustomer(customerId);
    } else {
        // If no customer selected, disable the select
        const select = newItem.querySelector('.contract-select');
        if (select) {
            select.disabled = true;
            const emptyOption = select.querySelector('option[value=""]');
            if (emptyOption) {
                emptyOption.textContent = 'Select customer first';
            }
        }
    }
    console.log('Contract item added');
}

// Job Advice List Management
function addJobAdviceItem() {
    console.log('Adding job advice item...');
    const container = document.getElementById('jobAdviceListContainer');
    if (!container) {
        console.error('Job advice list container not found');
        return;
    }
    // Get selected customer ID
    const selectedCustomer = document.getElementById('customer_id');
    const customerId = selectedCustomer ? selectedCustomer.value : null;
    
    const newItem = document.createElement('div');
    newItem.className = 'job-advice-item flex gap-2 mb-2';
    
    // Build options HTML - only include options for selected customer
    let optionsHtml = '<option value="">Select Job Advice</option>';
    @foreach($job_advices as $job_advice)
        @if($job_advice->customer_id)
            optionsHtml += `<option value="{{ $job_advice->id }}" data-customer-id="{{ $job_advice->customer_id }}">{{ $job_advice->job_advice_number }} - {{ $job_advice->customer->name ?? 'N/A' }}</option>`;
        @endif
    @endforeach
    
    newItem.innerHTML = `
        <select name="job_advice_list[]" class="form-input flex-1 job-advice-select">
            ${optionsHtml}
        </select>
        <button type="button" class="btn btn-secondary btn-sm" onclick="removeDocumentItem(this)">Remove</button>
    `;
    container.appendChild(newItem);
    
    // Apply filter to remove options from other customers
    if (customerId) {
        filterDocumentsByCustomer(customerId);
    } else {
        // If no customer selected, disable the select
        const select = newItem.querySelector('.job-advice-select');
        if (select) {
            select.disabled = true;
            const emptyOption = select.querySelector('option[value=""]');
            if (emptyOption) {
                emptyOption.textContent = 'Select customer first';
            }
        }
    }
    console.log('Job advice item added');
}

// Store original select HTML for restoration when customer is cleared
const originalSelectHTML = new Map();

// ✅ FILTER ALL DOCUMENTS BY CUSTOMER (Quotations, Surveys, Contracts, Job Advices)
function filterDocumentsByCustomer(customerId) {
    console.log('Filtering all documents for customer ID:', customerId);
    
    // Convert customerId to string for comparison
    const customerIdStr = customerId ? String(customerId) : null;
    
    // Define all document types and their configurations
    const documentTypes = [
        { selector: '.quotation-select', emptyText: 'Select Quotation', noDataText: 'No quotations for this customer' },
        { selector: '.survey-select', emptyText: 'Select Survey', noDataText: 'No surveys for this customer' },
        { selector: '.contract-select', emptyText: 'Select Contract', noDataText: 'No contracts for this customer' },
        { selector: '.job-advice-select', emptyText: 'Select Job Advice', noDataText: 'No job advices for this customer' }
    ];
    
    documentTypes.forEach(docType => {
        const selects = document.querySelectorAll(docType.selector);
        
        selects.forEach((select, index) => {
            // Create unique key for this select using a more reliable method
            const selectId = select.id || select.name || `select-${index}`;
            const containerId = select.closest('.quotation-item, .survey-item, .contract-item, .job-advice-item')?.parentElement?.id || '';
            const selectKey = `${docType.selector}-${selectId}-${containerId}`;
            
            // Store original HTML if not already stored (only once per select)
            // Only store if select has options (not empty)
            if (!originalSelectHTML.has(selectKey) && select.querySelectorAll('option').length > 1) {
                originalSelectHTML.set(selectKey, select.innerHTML);
                console.log(`Stored original options for ${selectKey}`);
            }
            
            // If customer is cleared, restore all original options
            if (!customerIdStr) {
                const originalHTML = originalSelectHTML.get(selectKey);
                if (originalHTML) {
                    // Get current selected value to preserve it if possible
                    const currentValue = select.value;
                    select.innerHTML = originalHTML;
                    
                    // Restore selection if it was valid
                    if (currentValue && select.querySelector(`option[value="${currentValue}"]`)) {
                        select.value = currentValue;
                    }
                    
                    // Update empty option text and disable
                    const emptyOption = select.querySelector('option[value=""]');
                    if (emptyOption) {
                        emptyOption.textContent = 'Select customer first';
                    }
                    select.disabled = true;
                    console.log(`Restored all options for ${selectKey}`);
                } else {
                    // If no original HTML stored, just disable the select
                    const emptyOption = select.querySelector('option[value=""]');
                    if (emptyOption) {
                        emptyOption.textContent = 'Select customer first';
                    }
                    select.disabled = true;
                }
                return;
            }
            
            // Filter: Remove options that don't match the selected customer
            const options = Array.from(select.querySelectorAll('option'));
            let visibleCount = 0;
            const optionsToRemove = [];
            
            options.forEach(option => {
                // Skip empty option
                if (option.value === '') {
                    return;
                }
                
                const optionCustomerId = option.getAttribute('data-customer-id');
                
                // Remove options that don't match the selected customer
                if (optionCustomerId !== customerIdStr) {
                    // Clear selection if currently selected option is being removed
                    if (option.selected) {
                        select.value = '';
                    }
                    // Mark for removal
                    optionsToRemove.push(option);
                } else {
                    visibleCount++;
                }
            });
            
            // Remove options that don't match the customer
            optionsToRemove.forEach(option => {
                option.remove();
                console.log(`Removed document option with customer ID ${option.getAttribute('data-customer-id')} from ${docType.selector}`);
            });
            
            // Update placeholder text and enable/disable dropdown
            const emptyOption = select.querySelector('option[value=""]');
            if (emptyOption) {
                if (visibleCount === 0) {
                    emptyOption.textContent = docType.noDataText;
                    select.disabled = true;
                } else {
                    emptyOption.textContent = docType.emptyText;
                    select.disabled = false;
                }
            }
        });
    });
    
    console.log('Document filtering complete - removed options from other customers');
}

// ✅ FILTER CONTACTS BY CUSTOMER
// Contacts are loaded from Master Customer Contacts (/api/customers/{id}/contacts),
// scoped server-side to the selected customer - not filtered from a preloaded list.
function filterContactsByCustomer(customerId, isEdit = false) {
    console.log('Loading contacts for customer ID:', customerId);
    const selector = isEdit ? '.select2-contact-edit' : '.select2-contact';
    const hiddenId = isEdit ? '#pic_name_hidden_edit' : '#pic_name_hidden';
    const phoneId = isEdit ? '#pic_phone_edit' : '#pic_phone';
    const emailId = isEdit ? '#pic_email_edit' : '#pic_email';
    const selects = document.querySelectorAll(selector);
    const customerIdStr = customerId ? String(customerId) : null;

    // Preserve the currently selected PIC (e.g. pre-filled when opening the edit
    // modal) so it can be re-selected once this customer's contacts have loaded.
    const previousPicName = $(hiddenId).val() || '';

    selects.forEach(select => {
        if (!customerIdStr) {
            select.innerHTML = '<option value="">Pilih customer dulu..</option>';
            select.disabled = true;
            $(select).trigger('change');
            return;
        }

        select.disabled = true;
        select.innerHTML = '<option value="">Memuat kontak...</option>';

        fetch(`/api/customers/${customerIdStr}/contacts`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(response => response.json())
            .then(payload => {
                const contacts = Array.isArray(payload) ? payload : (payload.data || []);

                select.innerHTML = '';
                select.appendChild(new Option(contacts.length ? 'Pilih kontak..' : 'No contacts for this customer', ''));

                contacts.forEach(contact => {
                    const displayName = (contact.salutation ? contact.salutation + ' ' : '') + contact.name;
                    const option = new Option(displayName, displayName, false, displayName === previousPicName);
                    option.setAttribute('data-customer-id', customerIdStr);
                    option.setAttribute('data-phone', contact.phone || '');
                    option.setAttribute('data-email', contact.email || '');
                    select.appendChild(option);
                });

                select.disabled = contacts.length === 0;
                $(select).trigger('change');
            })
            .catch(error => {
                console.error('Failed to load contacts for customer', customerIdStr, error);
                select.innerHTML = '<option value="">Gagal memuat kontak</option>';
                select.disabled = true;
                $(select).trigger('change');
            });
    });
}

// ✅ BACKWARDS COMPATIBILITY: Keep old function name for any existing references
function filterContractsByCustomer(customerId) {
    filterDocumentsByCustomer(customerId);
}

// Callback function when customer is created from the new modal
function onCustomerCreatedInPipeline(customerData) {
    // Add new customer to dropdown in the Pipeline Create/Edit Modal
    const customerSelect = $('#customer_id');
    
    // Check if the element exists (it should, as the modal must be open to trigger this)
    if (customerSelect.length > 0) {
        // Format: Name - NPWP - Address (max 30)
        let address = customerData.npwp_address || customerData.address || '';
        if (address.length > 30) address = address.substring(0, 30) + '...';
        const npwp = customerData.npwp || '-';
        const optionText = `${customerData.name} - ${npwp} - ${address}`;

        const newOption = new Option(optionText, customerData.id, true, true);
        $(newOption).attr('data-name', customerData.name);
        customerSelect.append(newOption);
        
        // Trigger change to update Select2 and populate hidden company_name
        customerSelect.trigger('change');
        
        // Also populate company_name hidden input if it exists
        const companyNameInput = document.getElementById('company_name');
        if (companyNameInput) {
            companyNameInput.value = customerData.name;
        }
        
        // Alert user
        showSuccessDialog('Customer "' + customerData.name + '" berhasil ditambahkan dan langsung dipilih.');
    } else {
        console.warn('Customer dropdown #customer_id not found. Cannot auto-select new customer.');
        showWarningDialog('Customer "' + customerData.name + '" berhasil dibuat, tetapi belum bisa dipilih otomatis. Silakan cari manual di dropdown.');
    }
}

// Global variable to keep track of where to add the new contact
let currentContactContext = 'create'; 

function openQuickContactModal(context = 'create') {
    currentContactContext = context;
    
    // Clear form
    document.getElementById('quickContactForm').reset();

    // Auto-fill customer ID based on context. 'customer-create' comes from the
    // "+" button inside the Create New Customer modal's Multi PIC field - the
    // customer doesn't have an ID yet, so it's left blank and the contact gets
    // linked once the customer is actually created (see CustomerController::store).
    if (context === 'customer-create') {
        $('#quick_contact_customer_id').val('');
    } else {
        const customerId = context === 'create' ? $('#customer_id').val() : $('input[name="customer_id"]').val();
        $('#quick_contact_customer_id').val(customerId);
    }
    
    // Open modal
    const modal = document.getElementById('quickContactModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeQuickContactModal() {
    const modal = document.getElementById('quickContactModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

function submitQuickContactForm(event) {
    if (event) event.preventDefault();
    const form = document.getElementById('quickContactForm');
    const formData = new FormData(form);
    
    // Validate required fields
    if (!formData.get('name')) {
        showWarningDialog('Nama wajib diisi.');
        return;
    }
    
    // Change button state
    const submitBtn = event.target;
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Menyimpan...';
    submitBtn.disabled = true;

    fetch('/company/customer-contacts', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeQuickContactModal();

            const contact = data.data;

            if (currentContactContext === 'customer-create') {
                // Contact was created for a not-yet-saved customer (no customer_id
                // yet). Add it to the Multi PIC select in the Create Customer modal;
                // it gets linked to the customer once that form is submitted
                // (see CustomerController::store's contact_ids handling).
                const multiPicSelect = document.getElementById('create_contact_ids');
                if (multiPicSelect) {
                    const label = `${contact.name} - ${contact.position || ''} (${contact.phone || ''})`;
                    const newOption = new Option(label, contact.id, true, true);
                    multiPicSelect.appendChild(newOption);
                    triggerChange(multiPicSelect);
                }
            } else {
                const isEdit = currentContactContext === 'edit';
                const selectId = isEdit ? '#pic_name_select_edit' : '#pic_name_select';
                const hiddenId = isEdit ? '#pic_name_hidden_edit' : '#pic_name_hidden';
                const phoneId = isEdit ? '#pic_phone_edit' : '#pic_phone';
                const emailId = isEdit ? '#pic_email_edit' : '#pic_email';

                // Format display name as "Salutation Name" if salutation exists
                const displayName = contact.salutation ? `${contact.salutation} ${contact.name}` : contact.name;

                const contactSelect = $(selectId);
                if (contactSelect.length) {
                    // ✅ ENAABLE THE SELECT DROPDOWN (in case it was disabled because no contacts existed)
                    contactSelect.prop('disabled', false);

                    const newOption = new Option(displayName, displayName, true, true);
                    $(newOption).attr('data-customer-id', contact.customer_id);
                    $(newOption).attr('data-phone', contact.phone);
                    $(newOption).attr('data-email', contact.email);
                    contactSelect.append(newOption);
                    contactSelect.trigger('change');
                }

                $(hiddenId).val(displayName);
                $(phoneId).val(contact.phone || '');
                $(emailId).val(contact.email || '');
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Kontak "' + contact.name + '" berhasil ditambahkan.', confirmButtonColor: '#1e3a8a' });
            } else {
                showSuccessDialog('Kontak "' + contact.name + '" berhasil ditambahkan.');
            }
        } else {
            let errorMsg = data.message || 'Gagal menambahkan kontak.';
            if (data.errors) {
                errorMsg = '';
                Object.keys(data.errors).forEach(key => {
                    errorMsg += `${data.errors[key].join(', ')}\n`;
                });
            }
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Validasi Gagal', text: errorMsg, confirmButtonColor: '#1e3a8a' });
            } else {
                showErrorDialog(errorMsg, 'Validasi Gagal');
            }
        }
    })
    .catch(error => {
        console.error('Error adding contact:', error);
        showErrorDialog('Gagal menambahkan kontak. Silakan coba lagi.');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function showAddBuildingModal() {
    // Clear form
    document.getElementById('addBuildingForm').reset();
    
    // Destroy Select2 on location dropdowns if already initialized
    $('#province_select, #city_select, #district_select, #subdistrict_select').each(function() {
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).select2('destroy');
        }
    });
    
    // Reset cascading dropdowns
    $('#city_select').html('<option value="">Select City</option>').prop('disabled', true);
    $('#district_select').html('<option value="">Select District</option>').prop('disabled', true);
    $('#subdistrict_select').html('<option value="">Select Subdistrict</option>').prop('disabled', true);
    $('#postal_code_input').val('');
    $('#province_select').val('').trigger('change'); // Reset province selection
    
    // Open modal
    const modal = document.getElementById('addBuildingModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    // Setup cascading dropdown event listeners - use setTimeout to ensure DOM is ready
    setTimeout(function() {
        setupBuildingLocationCascade();
    }, 100);
}

function setupBuildingLocationCascade() {
    // Remove all existing event listeners first to prevent duplicates
    const provinceSelect = document.getElementById('province_select');
    const citySelect = document.getElementById('city_select');
    const districtSelect = document.getElementById('district_select');
    const subdistrictSelect = document.getElementById('subdistrict_select');
    
    if (!provinceSelect || !citySelect || !districtSelect || !subdistrictSelect) {
        console.error('One or more select elements not found');
        return;
    }
    
    // Ensure Select2 is not initialized on these selects
    // Remove Select2 if already initialized
    if ($(provinceSelect).hasClass('select2-hidden-accessible')) {
        $(provinceSelect).select2('destroy');
    }
    if ($(citySelect).hasClass('select2-hidden-accessible')) {
        $(citySelect).select2('destroy');
    }
    if ($(districtSelect).hasClass('select2-hidden-accessible')) {
        $(districtSelect).select2('destroy');
    }
    if ($(subdistrictSelect).hasClass('select2-hidden-accessible')) {
        $(subdistrictSelect).select2('destroy');
    }
    
    // Province -> City
    provinceSelect.addEventListener('change', function() {
        const provinceId = this.value;
        console.log('Province selected:', provinceId);
        
        citySelect.innerHTML = '<option value="">Memuat...</option>';
        citySelect.disabled = true;
        districtSelect.innerHTML = '<option value="">Select District</option>';
        districtSelect.disabled = true;
        subdistrictSelect.innerHTML = '<option value="">Select Subdistrict</option>';
        subdistrictSelect.disabled = true;
        document.getElementById('postal_code_input').value = '';
        
        if (provinceId) {
            fetch(`/api/cities/${provinceId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(cities => {
                    console.log('Cities loaded:', cities);
                    let options = '<option value="">Select City</option>';
                    cities.forEach(city => {
                        options += `<option value="${city.id}">${city.name}</option>`;
                    });
                    citySelect.innerHTML = options;
                    citySelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading cities:', error);
                    citySelect.innerHTML = '<option value="">Gagal memuat kota</option>';
                    citySelect.disabled = true;
                });
        } else {
            citySelect.innerHTML = '<option value="">Select City</option>';
            citySelect.disabled = true;
        }
    });
    
    // City -> District
    citySelect.addEventListener('change', function() {
        const cityId = this.value;
        console.log('City selected:', cityId);
        
        districtSelect.innerHTML = '<option value="">Memuat...</option>';
        districtSelect.disabled = true;
        subdistrictSelect.innerHTML = '<option value="">Select Subdistrict</option>';
        subdistrictSelect.disabled = true;
        document.getElementById('postal_code_input').value = '';
        
        if (cityId) {
            fetch(`/api/districts/${cityId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(districts => {
                    console.log('Districts loaded:', districts);
                    let options = '<option value="">Select District</option>';
                    districts.forEach(district => {
                        options += `<option value="${district.id}">${district.name}</option>`;
                    });
                    districtSelect.innerHTML = options;
                    districtSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading districts:', error);
                    districtSelect.innerHTML = '<option value="">Gagal memuat kecamatan</option>';
                    districtSelect.disabled = true;
                });
        } else {
            districtSelect.innerHTML = '<option value="">Select District</option>';
            districtSelect.disabled = true;
        }
    });
    
    // District -> Subdistrict
    districtSelect.addEventListener('change', function() {
        const districtId = this.value;
        console.log('District selected:', districtId);
        
        subdistrictSelect.innerHTML = '<option value="">Memuat...</option>';
        subdistrictSelect.disabled = true;
        document.getElementById('postal_code_input').value = '';
        
        if (districtId) {
            fetch(`/api/subdistricts/${districtId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(subdistricts => {
                    console.log('Subdistricts loaded:', subdistricts);
                    let options = '<option value="">Select Subdistrict</option>';
                    subdistricts.forEach(subdistrict => {
                        options += `<option value="${subdistrict.id}">${subdistrict.name}</option>`;
                    });
                    subdistrictSelect.innerHTML = options;
                    subdistrictSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading subdistricts:', error);
                    subdistrictSelect.innerHTML = '<option value="">Gagal memuat kelurahan</option>';
                    subdistrictSelect.disabled = true;
                });
        } else {
            subdistrictSelect.innerHTML = '<option value="">Select Subdistrict</option>';
            subdistrictSelect.disabled = true;
        }
    });
    
    // Subdistrict -> Auto-fill Postal Code
    subdistrictSelect.addEventListener('change', function() {
        const subdistrictId = this.value;
        console.log('Subdistrict selected:', subdistrictId);
        
        if (subdistrictId) {
            fetch(`/api/subdistricts/${subdistrictId}/postal-code`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Postal code loaded:', data.postal_code);
                    document.getElementById('postal_code_input').value = data.postal_code || '';
                })
                .catch(error => {
                    console.error('Error loading postal code:', error);
                    document.getElementById('postal_code_input').value = '';
                });
        } else {
            document.getElementById('postal_code_input').value = '';
        }
    });
}

function closeAddBuildingModal() {
    const modal = document.getElementById('addBuildingModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }
}

function submitAddBuildingForm() {
    const form = document.getElementById('addBuildingForm');
    const formData = new FormData(form);
    
    // Validate required fields
    if (!formData.get('name')) {
        showWarningDialog('Nama gedung wajib diisi.');
        return;
    }
    if (!formData.get('address_1')) {
        showWarningDialog('Alamat gedung wajib diisi.');
        return;
    }
    if (!formData.get('province_id')) {
        showWarningDialog('Provinsi wajib diisi.');
        return;
    }
    if (!formData.get('city_id')) {
        showWarningDialog('Kota wajib diisi.');
        return;
    }
    if (!formData.get('district_id')) {
        showWarningDialog('Kecamatan wajib diisi.');
        return;
    }
    if (!formData.get('subdistrict_id')) {
        showWarningDialog('Kelurahan wajib diisi.');
        return;
    }
    if (!formData.get('postal_code')) {
        showWarningDialog('Kode pos wajib diisi.');
        return;
    }
    
    fetch('/operational/buildings', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Close the add building modal
            closeAddBuildingModal();
            
            // Add new building to create modal dropdown (if exists)
            const buildingSelect = $('#building_id');
            if (buildingSelect.length) {
                const newOption = new Option(data.data.name, data.data.id, true, true);
                $(newOption).attr('data-name', data.data.name);
                $(newOption).attr('data-address', data.data.alamat_1 || data.data.address);
                $(newOption).attr('data-address2', data.data.alamat_2 || '');
                buildingSelect.append(newOption);
                buildingSelect.trigger('change');
            }
            
            // Add new building to edit modal dropdown (if exists)
            const buildingSelectEdit = $('#building_id_edit');
            if (buildingSelectEdit.length) {
                const newOptionEdit = new Option(data.data.name, data.data.id, true, true);
                $(newOptionEdit).attr('data-name', data.data.name);
                $(newOptionEdit).attr('data-address', data.data.alamat_1 || data.data.address);
                $(newOptionEdit).attr('data-address2', data.data.alamat_2 || '');
                buildingSelectEdit.append(newOptionEdit);
                buildingSelectEdit.trigger('change');
            }
            
            // Show success message
            showSuccessDialog('Gedung "' + data.data.name + '" berhasil ditambahkan dan langsung dipilih.');
        } else {
            showErrorDialog(data.message || 'Gagal menambahkan gedung.');
        }
    })
    .catch(error => {
        console.error('Error adding building:', error);
        showErrorDialog('Gagal menambahkan gedung. Silakan coba lagi.');
    });
}

</script>

<!-- Quick Create Contact Modal -->
<div id="quickContactModal" class="modal-overlay" onclick="closeQuickContactModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Kontak Cepat</h2>
            <button class="modal-close" onclick="closeQuickContactModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p class="text-gray-600 mb-6 text-center">Lengkapi informasi kontak di bawah ini</p>
            <form id="quickContactForm">
                <input type="hidden" name="customer_id" id="quick_contact_customer_id">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Sapaan</label>
                            <select name="salutation" class="form-input">
                                <option value="">-</option>
                                @foreach($salutations as $salutation)
                                    <option value="{{ $salutation->option_name }}">{{ $salutation->option_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="form-label">Nama Kontak *</label>
                            <input type="text" name="name" class="form-input" placeholder="Masukkan nama kontak" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" placeholder="Masukkan alamat email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Telepon *</label>
                    <input type="tel" name="phone" class="form-input" placeholder="Masukkan nomor telepon" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jabatan</label>
                    <select name="position" class="form-input">
                        <option value="">Pilih jabatan..</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->option_name }}">{{ $pos->option_name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <div class="flex justify-center gap-6">
                <button type="button" class="btn btn-outline" onclick="closeQuickContactModal()">Batal</button>
                <button type="button" class="btn btn-primary" onclick="submitQuickContactForm(event)">Simpan Kontak</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModalOverlay" class="delete-modal-overlay">
    <div class="delete-modal-container">
        <div class="delete-modal-content">
            <div class="delete-icon-container">
                <svg class="delete-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 6H5H21" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10 11V17" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 11V17" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h2 id="deleteModalTitle" class="delete-modal-title">Sembunyikan data?</h2>
            <p class="delete-modal-description">Tindakan ini akan menyembunyikan data yang dipilih dari daftar. Data tetap aman di database dan bisa dipulihkan lagi bila diperlukan.</p>
            <div class="delete-modal-buttons">
                <button class="btn-cancel" onclick="closeDeleteModal()">Batal</button>
                <button class="btn-hide" onclick="confirmDelete()">Ya, Sembunyikan</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModalOverlay" class="error-modal-overlay">
    <div class="error-modal-container">
        <div class="error-modal-content">
            <div class="error-icon-container">
                <svg class="error-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 6H5H21" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10 11V17" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 11V17" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 8V8.01" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h2 class="error-modal-title">Ups... Terjadi Kendala</h2>
            <p class="error-modal-description">Data yang dipilih belum berhasil disembunyikan saat ini. Tenang, datanya tetap aman. Silakan coba lagi.</p>
            <div class="error-modal-buttons">
                <button class="btn-error-close" onclick="closeErrorModal()">Tutup</button>
                <button class="btn-error-retry" onclick="retryDelete()">Coba Lagi</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModalOverlay" class="success-modal-overlay">
    <div class="success-modal-container">
        <div class="success-modal-content">
            <div class="success-icon-container">
                <svg class="success-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 6H5H21" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9 12L11 14L15 10" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h2 class="success-modal-title">Berhasil</h2>
            <p id="successModalDescription" class="success-modal-description">Data berhasil disembunyikan dari halaman ini dan tetap aman tersimpan di database.</p>
            <div class="success-modal-buttons">
                <button class="btn-success-close" onclick="closeSuccessModal(); location.reload();">Mengerti</button>
            </div>
        </div>
    </div>
</div>

<!-- Connection Error Modal -->
<div id="connectionErrorModalOverlay" class="error-modal-overlay" onclick="closeConnectionErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="error-modal-content">
            <!-- Connection Error Icon -->
            <div class="error-icon-container">
                <svg class="error-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="connectionErrorGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1E40AF;stop-opacity:1" />
                        </linearGradient>
                        <filter id="connectionErrorShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <!-- WiFi Icon -->
                    <path d="M20 40 Q30 30 40 40 Q50 30 60 40 Q70 30 80 40" stroke="url(#connectionErrorGradient)" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <path d="M25 50 Q35 40 45 50 Q55 40 65 50 Q75 40 75 50" stroke="url(#connectionErrorGradient)" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <path d="M30 60 Q40 50 50 60 Q60 50 70 60" stroke="url(#connectionErrorGradient)" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <!-- Error X Circle -->
                    <circle cx="75" cy="25" r="12" fill="#EF4444" filter="url(#connectionErrorShadow)"/>
                    <text x="75" y="30" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="16" font-weight="bold">×</text>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 class="error-modal-title">Koneksi Terputus</h2>
            
            <!-- Description -->
            <p class="error-modal-description">
                Koneksi internet terlihat terputus. Tenang, data kamu tetap aman. Silakan periksa koneksi lalu coba lagi.
            </p>
            
            <!-- Buttons -->
            <div class="error-modal-buttons">
                <button class="btn-error-close" onclick="closeConnectionErrorModal()">Tutup</button>
                <button class="btn-error-retry" onclick="retryLastAction()">Coba Lagi</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Error Modal -->
<div id="updateErrorModalOverlay" class="error-modal-overlay" onclick="closeUpdateErrorModal()">
    <div class="error-modal-container" onclick="event.stopPropagation()">
        <div class="error-modal-content">
            <!-- Update Error Icon -->
            <div class="error-icon-container">
                <svg class="error-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="updateErrorGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1E40AF;stop-opacity:1" />
                        </linearGradient>
                        <filter id="updateErrorShadow" x="-50%" y="-50%" width="200%" height="200%">
                            <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.3)"/>
                        </filter>
                    </defs>
                    <!-- Edit Icon -->
                    <path d="M25 70 L75 70" stroke="url(#updateErrorGradient)" stroke-width="4" stroke-linecap="round"/>
                    <path d="M35 30 L65 30 L65 60 L35 60 Z" stroke="url(#updateErrorGradient)" stroke-width="4" fill="none"/>
                    <path d="M45 40 L55 40" stroke="url(#updateErrorGradient)" stroke-width="2" stroke-linecap="round"/>
                    <path d="M45 50 L55 50" stroke="url(#updateErrorGradient)" stroke-width="2" stroke-linecap="round"/>
                    <!-- Error X Circle -->
                    <circle cx="75" cy="25" r="12" fill="#EF4444" filter="url(#updateErrorShadow)"/>
                    <text x="75" y="30" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="16" font-weight="bold">×</text>
                </svg>
            </div>
            
            <!-- Title -->
            <h2 class="error-modal-title">Pembaruan Gagal</h2>
            
            <!-- Description -->
            <p class="error-modal-description">
                Informasi pipeline belum berhasil diperbarui saat ini. Silakan periksa kembali datanya lalu coba lagi.
            </p>
            
            <!-- Buttons -->
            <div class="error-modal-buttons">
                <button class="btn-error-close" onclick="closeUpdateErrorModal()">Tutup</button>
                <button class="btn-error-retry" onclick="retryLastAction()">Coba Lagi</button>
            </div>
        </div>
    </div>
</div>

@include('company.customers.partials.create-script')
@endsection
