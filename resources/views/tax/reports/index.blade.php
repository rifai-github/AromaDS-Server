@extends('layouts.app')

@section('title', 'Tax Reports')
@section('breadcrumb', 'Home / Finance / Tax Reports')

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

    /* Responsive Table */
    .responsive-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
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
    }

    .responsive-table tbody tr:hover {
        background-color: #eff6ff;
        transition: background-color 0.2s ease;
    }

    .responsive-table tbody tr {
        cursor: pointer;
    }

    /* Pagination Styles */
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .page-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid #d1d5db;
        background-color: #f9fafb;
        color: #374151;
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
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }
    
    .modal-close:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .modal-body {
        padding: 20px;
        max-height: 60vh;
        overflow-y: auto;
    }
    
    .modal-footer {
        padding: 20px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background: #f9fafb;
    }

    /* Form Input Styling */
    input[type="date"], input[type="text"], input[type="number"], select, textarea {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        width: 100%;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    input[type="date"]:focus, input[type="text"]:focus, input[type="number"]:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    /* Form Sections */
    .form-section {
        margin-bottom: 20px;
    }

    .form-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #214589;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e5e7eb;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 5px;
    }

    .required {
        color: #ef4444;
    }

    /* Status Badge Styles */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .status-draft {
        background-color: #f3f4f6;
        color: #374151;
    }
    
    .status-generated {
        background-color: #dbeafe;
        color: #1e40af;
    }
    
    .status-submitted {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .status-approved {
        background-color: #d1fae5;
        color: #065f46;
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
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Tax Reports Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Tax Reports</h1>
            </div>
            
            <div class="flex flex-row justify-end items-center">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add New Tax Report</span>
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
                        <th>
                            <input type="checkbox" id="headerSelectAll" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                        </th>
                        <th>Report Number</th>
                        <th>Report Type</th>
                        <th>Period Start</th>
                        <th>Period End</th>
                        <th>Taxable Income</th>
                        <th>Tax Due</th>
                        <th>Tax Paid</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody>
                    @forelse($taxReports ?? [] as $report)
                    <tr data-id="{{ $report->id }}" onclick="openViewModal({{ $report->id }})">
                        <td class="text-center">
                            <input type="checkbox" class="row-checkbox w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer" value="{{ $report->id }}" onclick="event.stopPropagation()">
                        </td>
                        <td>{{ $report->report_number ?? '-' }}</td>
                        <td>{{ ucfirst($report->report_type) ?? '-' }}</td>
                        <td>{{ $report->tax_period_start ? \Carbon\Carbon::parse($report->tax_period_start)->format('d/M/Y') : '-' }}</td>
                        <td>{{ $report->tax_period_end ? \Carbon\Carbon::parse($report->tax_period_end)->format('d/M/Y') : '-' }}</td>
                        <td class="text-right">{{ $report->total_taxable_income ? number_format($report->total_taxable_income, 0, ',', '.') : '-' }}</td>
                        <td class="text-right">{{ $report->total_tax_due ? number_format($report->total_tax_due, 0, ',', '.') : '-' }}</td>
                        <td class="text-right">{{ $report->total_tax_paid ? number_format($report->total_tax_paid, 0, ',', '.') : '-' }}</td>
                        <td>
                            <span class="status-badge status-{{ $report->status }}">
                                {{ ucfirst($report->status) }}
                            </span>
                        </td>
                        <td>
                            @if($report->created_at)
                                {{ \Carbon\Carbon::parse($report->created_at)->format('d/M/Y') }}<br>
                                at {{ \Carbon\Carbon::parse($report->created_at)->format('H.i') }} WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="p-8 text-center">
                            <p class="text-lg text-gray-600">No tax reports found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px]">
            <div class="pagination-controls">
                @if(isset($taxReports) && $taxReports->currentPage() > 1)
                    <a href="{{ $taxReports->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
                @endif
                
                @if(isset($taxReports) && $taxReports->hasPages())
                    @php
                        $start = max(1, $taxReports->currentPage() - 2);
                        $end = min($taxReports->lastPage(), $taxReports->currentPage() + 2);
                    @endphp
                    
                    <div class="flex items-center gap-2">
                        @if($start > 1)
                            <a href="{{ $taxReports->url(1) }}" class="page-number">1</a>
                            @if($start > 2)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                        @endif
                        
                        @for($i = $start; $i <= $end; $i++)
                            @if($i == $taxReports->currentPage())
                                <span class="page-number active">{{ $i }}</span>
                            @else
                                <a href="{{ $taxReports->url($i) }}" class="page-number">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($end < $taxReports->lastPage())
                            @if($end < $taxReports->lastPage() - 1)
                                <span class="text-sm text-gray-500">...</span>
                            @endif
                            <a href="{{ $taxReports->url($taxReports->lastPage()) }}" class="page-number">{{ $taxReports->lastPage() }}</a>
                        @endif
                    </div>
                @else
                    <span class="page-number active">1</span>
                @endif
                
                @if(isset($taxReports) && $taxReports->hasMorePages())
                    <a href="{{ $taxReports->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                @else
                    <button class="btn btn-secondary btn-sm" disabled>Next</button>
                @endif
                
                <div class="page-dropdown-container">
                    <span class="text-sm text-gray-700">Page</span>
                    <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                        <option>{{ $taxReports->currentPage() ?? 1 }}</option>
                    </select>
                    <span class="text-sm text-gray-700">of <span class="inline">{{ $taxReports->lastPage() ?? 1 }}</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 id="modalTitle" class="modal-title">View Tax Report</h2>
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

<script>
// Global variables
let selectedIdsForRetry = [];

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
        showWarningDialog('Pilih minimal satu laporan pajak yang ingin dihapus.');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    showConfirmDialog(
        'Hapus Laporan Pajak Terpilih?',
        'Apakah Anda yakin ingin menghapus laporan pajak yang dipilih?',
        'Ya, hapus',
        'Batal'
    ).then((confirmed) => {
        if (!confirmed) return;
        console.log('Deleting:', selectedIdsForRetry);
    });
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
}

// CRUD Modal functions
function openCreateModal() {
    openModal('Create New Tax Report');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="createForm">
            <div class="form-section">
                <div class="section-title">Report Information</div>
                <div class="form-group">
                    <label class="form-label">Report Number <span class="required">*</span></label>
                    <input type="text" name="report_number" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Report Type <span class="required">*</span></label>
                    <select name="report_type" required>
                        <option value="">Select Report Type</option>
                        <option value="monthly">Monthly Report</option>
                        <option value="quarterly">Quarterly Report</option>
                        <option value="annual">Annual Report</option>
                        <option value="custom">Custom Period Report</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="generated">Generated</option>
                        <option value="submitted">Submitted</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Period Information</div>
                <div class="form-group">
                    <label class="form-label">Period Start Date <span class="required">*</span></label>
                    <input type="date" name="tax_period_start" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Period End Date <span class="required">*</span></label>
                    <input type="date" name="tax_period_end" required>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Financial Summary</div>
                <div class="form-group">
                    <label class="form-label">Total Taxable Income</label>
                    <input type="number" name="total_taxable_income" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Total Tax Due</label>
                    <input type="number" name="total_tax_due" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Total Tax Paid</label>
                    <input type="number" name="total_tax_paid" step="0.01" min="0">
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Additional Information</div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="4" placeholder="Additional notes about this tax report..."></textarea>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" onclick="submitCreateForm()">Create Tax Report</button>
    `;
    
    // Auto-generate report number
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const timestamp = now.getTime().toString().slice(-6);
    document.querySelector('input[name="report_number"]').value = `TR-${year}${month}-${timestamp}`;
    
    // Set default period based on report type
    const reportTypeSelect = document.querySelector('select[name="report_type"]');
    const startDateInput = document.querySelector('input[name="tax_period_start"]');
    const endDateInput = document.querySelector('input[name="tax_period_end"]');
    
    reportTypeSelect.addEventListener('change', function() {
        const reportType = this.value;
        let startDate, endDate;
        
        switch(reportType) {
            case 'monthly':
                startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                break;
            case 'quarterly':
                const quarter = Math.floor(now.getMonth() / 3);
                startDate = new Date(now.getFullYear(), quarter * 3, 1);
                endDate = new Date(now.getFullYear(), quarter * 3 + 3, 0);
                break;
            case 'annual':
                startDate = new Date(now.getFullYear(), 0, 1);
                endDate = new Date(now.getFullYear(), 11, 31);
                break;
            default:
                return;
        }
        
        startDateInput.value = startDate.toISOString().split('T')[0];
        endDateInput.value = endDate.toISOString().split('T')[0];
    });
}

function openViewModal(id) {
    openModal('View Tax Report');
    
    // Get tax report data (this would typically come from an API call)
    const taxReport = {
        id: id,
        report_number: 'TR-202409-123456',
        report_type: 'monthly',
        tax_period_start: '2024-09-01',
        tax_period_end: '2024-09-30',
        total_taxable_income: 5000000,
        total_tax_due: 550000,
        total_tax_paid: 550000,
        status: 'approved',
        notes: 'Monthly tax report for September 2024'
    };
    
    document.getElementById('modalBody').innerHTML = `
        <div class="form-section">
            <div class="section-title">Report Information</div>
            <div class="form-group">
                <label class="form-label">Report Number</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${taxReport.report_number}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Report Type</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${taxReport.report_type}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${taxReport.status}</div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title">Period Information</div>
            <div class="form-group">
                <label class="form-label">Period Start</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${taxReport.tax_period_start}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Period End</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">${taxReport.tax_period_end}</div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title">Financial Summary</div>
            <div class="form-group">
                <label class="form-label">Total Taxable Income</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">Rp ${taxReport.total_taxable_income.toLocaleString('id-ID')}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Total Tax Due</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">Rp ${taxReport.total_tax_due.toLocaleString('id-ID')}</div>
            </div>
            <div class="form-group">
                <label class="form-label">Total Tax Paid</label>
                <div style="padding: 8px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; color: #374151;">Rp ${taxReport.total_tax_paid.toLocaleString('id-ID')}</div>
            </div>
        </div>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Close</button>
        <button class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>
    `;
}

function openEditModal(id) {
    openModal('Edit Tax Report');
    
    // Similar to create modal but with pre-filled data
    document.getElementById('modalBody').innerHTML = `
        <form id="editForm">
            <div class="form-section">
                <div class="section-title">Report Information</div>
                <div class="form-group">
                    <label class="form-label">Report Number <span class="required">*</span></label>
                    <input type="text" name="report_number" value="TR-202409-123456" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Report Type <span class="required">*</span></label>
                    <select name="report_type" required>
                        <option value="monthly" selected>Monthly Report</option>
                        <option value="quarterly">Quarterly Report</option>
                        <option value="annual">Annual Report</option>
                        <option value="custom">Custom Period Report</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="generated">Generated</option>
                        <option value="submitted">Submitted</option>
                        <option value="approved" selected>Approved</option>
                    </select>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn btn-primary" onclick="submitEditForm()">Update Tax Report</button>
    `;
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    // Here you would typically send the data to the server
    console.log('Creating tax report:', Object.fromEntries(formData));
    
    // Close modal and refresh page
    closeModal();
    location.reload();
}

function submitEditForm() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    
    // Here you would typically send the data to the server
    console.log('Updating tax report:', Object.fromEntries(formData));
    
    // Close modal and refresh page
    closeModal();
    location.reload();
}
</script>
@endsection
