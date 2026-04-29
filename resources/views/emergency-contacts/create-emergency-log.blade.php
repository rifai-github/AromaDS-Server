@extends('layouts.app')

@section('title', 'Report Emergency')
@section('breadcrumb', 'Home / System / Emergency Contacts / Report Emergency')

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

    .btn-danger {
        background-color: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background-color: #dc2626;
    }

    .btn-success {
        background-color: #10b981;
        color: white;
    }

    .btn-success:hover {
        background-color: #059669;
    }

    .btn-warning {
        background-color: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background-color: #d97706;
    }

    .btn-info {
        background-color: #3b82f6;
        color: white;
    }

    .btn-info:hover {
        background-color: #2563eb;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Form Styles */
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

    .form-label .required {
        color: #ef4444;
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: #214589;
        box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
    }

    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #214589;
    }

    /* Card Styles */
    .card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }

    .card-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        padding: 20px;
    }

    .card-body {
        padding: 20px;
    }

    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }

    .card-subtitle {
        font-size: 14px;
        color: #6b7280;
        margin: 4px 0 0 0;
    }

    /* Alert Styles */
    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 20px;
        border: 1px solid;
    }

    .alert-danger {
        background-color: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
    }

    .alert-warning {
        background-color: #fffbeb;
        border-color: #fed7aa;
        color: #92400e;
    }

    .alert-info {
        background-color: #eff6ff;
        border-color: #bfdbfe;
        color: #1e40af;
    }

    /* Emergency Contact Selection */
    .contact-option {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .contact-option:hover {
        border-color: #214589;
        background-color: #f8fafc;
    }

    .contact-option.selected {
        border-color: #214589;
        background-color: #eff6ff;
    }

    .contact-option input[type="radio"] {
        margin-right: 8px;
    }

    .contact-name {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .contact-details {
        font-size: 12px;
        color: #6b7280;
    }

    /* Priority Badge */
    .priority-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .priority-high {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .priority-medium {
        background-color: #fef3c7;
        color: #92400e;
    }

    .priority-low {
        background-color: #d1fae5;
        color: #065f46;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .card-body {
            padding: 15px;
        }

        .form-section {
            margin-bottom: 20px;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Emergency Report Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Report Emergency
                </h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <a href="{{ route('emergency-contacts.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span class="hidden md:inline">Back to Contacts</span>
                    <span class="md:hidden">Back</span>
                </a>
            </div>
        </div>
        
        <!-- Emergency Alert -->
        <div class="w-full p-4 bg-white">
            <div class="alert alert-danger">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle me-3"></i>
                    <div>
                        <strong>Emergency Alert:</strong> This form is for reporting genuine emergencies only. 
                        False reports may result in disciplinary action.
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Emergency Report Form -->
        <div class="card w-full">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Emergency Report Form
                </h2>
                <p class="card-subtitle">Please provide detailed information about the emergency situation</p>
            </div>
            
            <div class="card-body">
                <form action="{{ route('emergency-contacts.store-emergency-log') }}" method="POST" id="emergencyForm">
                    @csrf
                    
                    <!-- Emergency Type & Priority -->
                    <div class="form-section">
                        <div class="section-title">Emergency Information</div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Emergency Type <span class="required">*</span></label>
                                <select name="emergency_type" class="form-control" required>
                                    <option value="">Select Emergency Type</option>
                                    <option value="medical">Medical Emergency</option>
                                    <option value="safety">Safety Hazard</option>
                                    <option value="security">Security Threat</option>
                                    <option value="technical">Technical Issue</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Priority Level <span class="required">*</span></label>
                                <select name="priority" class="form-control" required>
                                    <option value="">Select Priority</option>
                                    <option value="high">High - Immediate Response Required</option>
                                    <option value="medium">Medium - Response Within 1 Hour</option>
                                    <option value="low">Low - Response Within 24 Hours</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Emergency Title <span class="required">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="Brief description of the emergency" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Detailed Description <span class="required">*</span></label>
                            <textarea name="description" class="form-control" rows="4" 
                                      placeholder="Provide detailed information about the emergency situation, including location, time, and any relevant details..." required></textarea>
                        </div>
                    </div>
                    
                    <!-- Location Information -->
                    <div class="form-section">
                        <div class="section-title">Location Information</div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Location <span class="required">*</span></label>
                                <input type="text" name="location" class="form-control" placeholder="Building, floor, room number, etc." required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" placeholder="City name">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Full Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Complete address details"></textarea>
                        </div>
                    </div>
                    
                    <!-- Emergency Contacts -->
                    <div class="form-section">
                        <div class="section-title">Notify Emergency Contacts</div>
                        
                        @if($emergencyContacts->count() > 0)
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Select emergency contacts to notify about this emergency. You can select multiple contacts.
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Emergency Contacts</label>
                                <div class="space-y-2">
                                    @foreach($emergencyContacts as $contact)
                                        <div class="contact-option">
                                            <label class="flex items-center cursor-pointer">
                                                <input type="checkbox" name="notify_contacts[]" value="{{ $contact->id }}" class="me-3">
                                                <div class="flex-1">
                                                    <div class="contact-name">{{ $contact->name }}</div>
                                                    <div class="contact-details">
                                                        {{ ucfirst($contact->relationship) }} • 
                                                        {{ $contact->phone_number }} • 
                                                        @if($contact->email){{ $contact->email }}@endif
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Belum ada kontak darurat. Silakan tambahkan kontak darurat terlebih dahulu.
                                <a href="{{ route('emergency-contacts.create') }}" class="btn btn-sm btn-primary ms-2">Tambah Kontak Darurat</a>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Additional Information -->
                    <div class="form-section">
                        <div class="section-title">Informasi Tambahan</div>
                        
                        <div class="form-group">
                            <label class="form-label">Cedera atau Korban</label>
                            <textarea name="injuries" class="form-control" rows="2" 
                                      placeholder="Jelaskan cedera atau korban yang terjadi (jika ada)"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Tindakan yang Sudah Dilakukan</label>
                            <textarea name="actions_taken" class="form-control" rows="2" 
                                      placeholder="Jelaskan tindakan yang sudah dilakukan untuk menangani keadaan darurat"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="notes" class="form-control" rows="2" 
                                      placeholder="Informasi tambahan lain yang mungkin membantu"></textarea>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('emergency-contacts.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Batal
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Laporkan Keadaan Darurat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-select high priority for certain emergency types
    const emergencyTypeSelect = document.querySelector('select[name="emergency_type"]');
    const prioritySelect = document.querySelector('select[name="priority"]');
    
    emergencyTypeSelect.addEventListener('change', function() {
        if (this.value === 'medical' || this.value === 'security') {
            prioritySelect.value = 'high';
        }
    });
    
    // Form validation
    const form = document.getElementById('emergencyForm');
    form.addEventListener('submit', function(e) {
        const emergencyType = document.querySelector('select[name="emergency_type"]').value;
        const priority = document.querySelector('select[name="priority"]').value;
        const title = document.querySelector('input[name="title"]').value;
        const description = document.querySelector('textarea[name="description"]').value;
        const location = document.querySelector('input[name="location"]').value;
        
        if (!emergencyType || !priority || !title || !description || !location) {
            e.preventDefault();
            alert('Silakan lengkapi semua field yang wajib diisi.');
            return;
        }
        
        // Confirm emergency report submission
        e.preventDefault();
        showConfirmDialog({
            title: 'Kirim laporan darurat?',
            text: 'Apakah Anda yakin ingin mengirim laporan darurat ini? Tindakan ini tidak dapat dibatalkan.',
            confirmButtonText: 'Ya, Kirim',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
    
    // Contact selection handling
    const contactOptions = document.querySelectorAll('.contact-option');
    contactOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox' && e.target.type !== 'radio') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('selected', checkbox.checked);
            }
        });
        
        const checkbox = option.querySelector('input[type="checkbox"]');
        checkbox.addEventListener('change', function() {
            option.classList.toggle('selected', this.checked);
        });
    });
});
</script>
@endsection
