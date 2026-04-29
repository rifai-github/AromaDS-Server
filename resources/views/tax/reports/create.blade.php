@extends('layouts.app')

@section('title', 'Create Tax Report')
@section('breadcrumb', 'Home / Finance / Tax Reports / Create')

@section('content')
<style>
    .form-section {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .section-title {
        color: #214589;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        font-weight: 500;
        color: #374151;
        margin-bottom: 5px;
    }
    
    .form-control {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 14px;
    }
    
    .form-control:focus {
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }
    
    .btn-primary {
        background-color: #214589;
        border-color: #214589;
        padding: 10px 20px;
        font-weight: 500;
    }
    
    .btn-primary:hover {
        background-color: #1e3a8a;
        border-color: #1e3a8a;
    }
    
    .btn-secondary {
        background-color: #6b7280;
        border-color: #6b7280;
        padding: 10px 20px;
        font-weight: 500;
    }
    
    .btn-secondary:hover {
        background-color: #4b5563;
        border-color: #4b5563;
    }
    
    .required {
        color: #ef4444;
    }
    
    .help-text {
        font-size: 12px;
        color: #6b7280;
        margin-top: 5px;
    }
    
    .row {
        margin-left: -10px;
        margin-right: -10px;
    }
    
    .col-md-6 {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .col-md-4 {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .col-md-3 {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .period-info {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 15px;
        margin-top: 10px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Create Tax Report</h1>
                    <p class="text-muted">Generate a new tax report for specific period</p>
                </div>
                <div>
                    <a href="{{ route('finance.tax-reports.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <form action="{{ route('finance.tax-reports.store') }}" method="POST" id="taxReportForm">
                @csrf
                
                <!-- Report Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-chart-line"></i> Report Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="report_number" class="form-label">
                                    Report Number <span class="required">*</span>
                                </label>
                                <input type="text" class="form-control" id="report_number" name="report_number" 
                                       value="{{ old('report_number') }}" required>
                                @error('report_number')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="help-text">Auto-generated if left empty</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="report_type" class="form-label">
                                    Report Type <span class="required">*</span>
                                </label>
                                <select class="form-control" id="report_type" name="report_type" required>
                                    <option value="">Select Report Type</option>
                                    <option value="monthly" {{ old('report_type') == 'monthly' ? 'selected' : '' }}>Monthly Report</option>
                                    <option value="quarterly" {{ old('report_type') == 'quarterly' ? 'selected' : '' }}>Quarterly Report</option>
                                    <option value="annual" {{ old('report_type') == 'annual' ? 'selected' : '' }}>Annual Report</option>
                                    <option value="custom" {{ old('report_type') == 'custom' ? 'selected' : '' }}>Custom Period Report</option>
                                </select>
                                @error('report_type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="generated" {{ old('status') == 'generated' ? 'selected' : '' }}>Generated</option>
                                    <option value="submitted" {{ old('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                                    <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                </select>
                                @error('status')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="file_path" class="form-label">File Path</label>
                                <input type="text" class="form-control" id="file_path" name="file_path" 
                                       value="{{ old('file_path') }}" placeholder="Path to generated report file">
                                @error('file_path')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="help-text">Will be auto-generated when report is created</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Period Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-calendar"></i> Period Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tax_period_start" class="form-label">
                                    Period Start Date <span class="required">*</span>
                                </label>
                                <input type="date" class="form-control" id="tax_period_start" name="tax_period_start" 
                                       value="{{ old('tax_period_start') }}" required>
                                @error('tax_period_start')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tax_period_end" class="form-label">
                                    Period End Date <span class="required">*</span>
                                </label>
                                <input type="date" class="form-control" id="tax_period_end" name="tax_period_end" 
                                       value="{{ old('tax_period_end') }}" required>
                                @error('tax_period_end')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="period-info">
                        <h6 class="mb-2">Period Information</h6>
                        <div id="period-info-content">
                            <p class="text-muted mb-0">Select start and end dates to see period information</p>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-calculator"></i> Financial Summary
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_taxable_income" class="form-label">
                                    Total Taxable Income
                                </label>
                                <input type="number" class="form-control" id="total_taxable_income" name="total_taxable_income" 
                                       value="{{ old('total_taxable_income') }}" step="0.01" min="0">
                                @error('total_taxable_income')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_tax_due" class="form-label">
                                    Total Tax Due
                                </label>
                                <input type="number" class="form-control" id="total_tax_due" name="total_tax_due" 
                                       value="{{ old('total_tax_due') }}" step="0.01" min="0">
                                @error('total_tax_due')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_tax_paid" class="form-label">
                                    Total Tax Paid
                                </label>
                                <input type="number" class="form-control" id="total_tax_paid" name="total_tax_paid" 
                                       value="{{ old('total_tax_paid') }}" step="0.01" min="0">
                                @error('total_tax_paid')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-sticky-note"></i> Additional Information
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                          placeholder="Additional notes about this tax report...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-section">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('finance.tax-reports.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <div>
                            <button type="button" class="btn btn-info me-2" onclick="generateReport()">
                                <i class="fas fa-cogs"></i> Generate Report
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Tax Report
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate report number if empty
    const reportNumberInput = document.getElementById('report_number');
    if (!reportNumberInput.value) {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const timestamp = now.getTime().toString().slice(-6);
        reportNumberInput.value = `TR-${year}${month}-${timestamp}`;
    }
    
    // Update period information
    const startDateInput = document.getElementById('tax_period_start');
    const endDateInput = document.getElementById('tax_period_end');
    const periodInfoContent = document.getElementById('period-info-content');
    
    function updatePeriodInfo() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            periodInfoContent.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <strong>Start Date:</strong><br>
                        ${start.toLocaleDateString('id-ID', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        })}
                    </div>
                    <div class="col-md-4">
                        <strong>End Date:</strong><br>
                        ${end.toLocaleDateString('id-ID', { 
                            weekday: 'long', 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        })}
                    </div>
                    <div class="col-md-4">
                        <strong>Duration:</strong><br>
                        ${diffDays} days
                    </div>
                </div>
            `;
        } else {
            periodInfoContent.innerHTML = '<p class="text-muted mb-0">Select start and end dates to see period information</p>';
        }
    }
    
    startDateInput.addEventListener('change', updatePeriodInfo);
    endDateInput.addEventListener('change', updatePeriodInfo);
    
    // Set default period based on report type
    const reportTypeSelect = document.getElementById('report_type');
    const now = new Date();
    
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
        updatePeriodInfo();
    });
});

function generateReport() {
    // This would typically make an AJAX call to generate the report
    alert('Report generation feature will be implemented in the controller');
}
</script>
@endsection