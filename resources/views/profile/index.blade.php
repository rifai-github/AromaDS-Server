@extends('layouts.app')

@section('title', 'My Profile')
@section('breadcrumb', 'Home / My Profile')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('content')
<style>
    .profile-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .profile-header {
        background: linear-gradient(135deg, #214589 0%, #1e3a8a 100%);
        color: white;
        padding: 20px;
        text-align: center;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        font-weight: bold;
        color: #214589;
        margin: 0 auto 15px;
        border: 4px solid white;
        overflow: hidden;
        position: relative;
    }
    
    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profile-avatar .upload-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        cursor: pointer;
    }
    
    .profile-avatar:hover .upload-overlay {
        opacity: 1;
    }
    
    .upload-overlay i {
        color: white;
        font-size: 20px;
    }

    .profile-name {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .profile-role {
        font-size: 14px;
        opacity: 0.9;
    }

    .profile-body {
        padding: 30px;
    }

    .form-section {
        margin-bottom: 30px;
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #214589;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e5e7eb;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }

    .form-control:focus {
        outline: none;
        border-color: #14ADD6;
        background: white;
        box-shadow: 0 0 0 3px rgba(20, 173, 214, 0.1);
    }

    .form-control:disabled {
        background: #f3f4f6;
        color: #6b7280;
        cursor: not-allowed;
    }

    .form-select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }

    .form-select:focus {
        outline: none;
        border-color: #14ADD6;
        background: white;
        box-shadow: 0 0 0 3px rgba(20, 173, 214, 0.1);
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #14ADD6 0%, #384295 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(20, 173, 214, 0.3);
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-success:hover {
        background: #059669;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 20px;
        display: none;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-active {
        background: #d4edda;
        color: #155724;
    }

    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }

    .text-muted {
        color: #6b7280;
        font-size: 12px;
        margin-top: 4px;
        display: block;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="profile-card">
    <div class="profile-header">
        <div class="profile-avatar" onclick="document.getElementById('photo_file').click()">
            @if($user->photo_file_path)
                <img src="{{ asset('uploads/' . $user->photo_file_path) }}" alt="{{ $user->name }}">
            @else
                {{ substr($user->name, 0, 1) }}
            @endif
            <div class="upload-overlay">
                <i class="fas fa-camera"></i>
            </div>
        </div>
        <div class="profile-name">{{ $user->name }}</div>
        <div class="profile-role">
            @php
                // Get role from relationship or column
                $role = 'Staff';
                if ($user->relationLoaded('roles')) {
                    $userRoles = $user->getRelation('roles');
                } else {
                    $userRoles = $user->roles()->get();
                }
                
                if ($userRoles->isNotEmpty()) {
                    // Get first role name
                    $role = $userRoles->first()->name ?? 'Staff';
                } else {
                    // Fallback to column value
                    $rolesColumn = $user->getAttributes()['roles'] ?? null;
                    if ($rolesColumn && is_string($rolesColumn)) {
                        $role = $rolesColumn;
                    } else {
                        // Try getEffectiveRole method
                        $role = $user->getEffectiveRole() ?? 'Staff';
                    }
                }
                
                // Get department name
                $deptName = $user->department_name;
                if (empty($deptName) && $user->department) {
                    $deptName = $user->department->name;
                }
                $deptName = $deptName ?? 'Department';
            @endphp
            {{ $role }} | {{ $deptName }}
            <span class="status-badge {{ $user->is_active ? 'status-active' : 'status-inactive' }}">
                {{ $user->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
    </div>

    <div class="profile-body">
        <div class="alert alert-success" id="success-alert"></div>
        <div class="alert alert-error" id="error-alert"></div>

        <!-- Personal Information -->
        <div class="form-section">
            <h3 class="section-title">Personal Information</h3>
            <form id="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Salutation</label>
                        <select class="form-select" id="salutation" name="salutation">
                            <option value="">Select Salutation</option>
                            <option value="Mr." {{ $user->salutation == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                            <option value="Mrs." {{ $user->salutation == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                            <option value="Ms." {{ $user->salutation == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                            <option value="Dr." {{ $user->salutation == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                            <option value="Prof." {{ $user->salutation == 'Prof.' ? 'selected' : '' }}>Prof.</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="{{ $user->username }}" disabled>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">NIK</label>
                        <input type="text" class="form-control" value="{{ $user->nik }}" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" {{ $user->gender == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ $user->gender == 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Marital Status</label>
                        <select class="form-select" id="marital_status" name="marital_status">
                            <option value="">Select Marital Status</option>
                            <option value="single" {{ $user->marital_status == 'single' ? 'selected' : '' }}>Single</option>
                            <option value="married" {{ $user->marital_status == 'married' ? 'selected' : '' }}>Married</option>
                            <option value="divorced" {{ $user->marital_status == 'divorced' ? 'selected' : '' }}>Divorced</option>
                            <option value="widowed" {{ $user->marital_status == 'widowed' ? 'selected' : '' }}>Widowed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Religion</label>
                        <input type="text" class="form-control" id="religion" name="religion" value="{{ $user->religion }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Blood Type</label>
                        <select class="form-select" id="blood_type" name="blood_type">
                            <option value="">Select Blood Type</option>
                            <option value="A" {{ $user->blood_type == 'A' ? 'selected' : '' }}>A</option>
                            <option value="B" {{ $user->blood_type == 'B' ? 'selected' : '' }}>B</option>
                            <option value="AB" {{ $user->blood_type == 'AB' ? 'selected' : '' }}>AB</option>
                            <option value="O" {{ $user->blood_type == 'O' ? 'selected' : '' }}>O</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rhesus</label>
                        <select class="form-select" id="rhesus" name="rhesus">
                            <option value="">Select Rhesus</option>
                            <option value="positive" {{ $user->rhesus == 'positive' ? 'selected' : '' }}>Positive</option>
                            <option value="negative" {{ $user->rhesus == 'negative' ? 'selected' : '' }}>Negative</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Address 1</label>
                    <textarea class="form-control" id="address_1" name="address_1" rows="3">{{ $user->address_1 }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Address 2</label>
                    <textarea class="form-control" id="address_2" name="address_2" rows="3">{{ $user->address_2 }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Profile
                </button>
                
                <!-- Hidden file input for photo upload -->
                <input type="file" id="photo_file" name="photo_file" accept=".jpg,.jpeg,.png" style="display: none;">
            </form>
        </div>

        <!-- Contact Information -->
        <div class="form-section">
            <h3 class="section-title">Contact Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="{{ $user->phone }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Handphone 1</label>
                    <input type="text" class="form-control" id="handphone_1" name="handphone_1" value="{{ $user->handphone_1 }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Handphone 2</label>
                    <input type="text" class="form-control" id="handphone_2" name="handphone_2" value="{{ $user->handphone_2 }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Emergency Contact Name</label>
                    <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" value="{{ $user->emergency_contact_name }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Emergency Contact Number</label>
                <input type="number" class="form-control" id="emergency_contact_number" name="emergency_contact_number" value="{{ $user->emergency_contact_number }}">
            </div>
        </div>

        <!-- Bank Information -->
        <div class="form-section">
            <h3 class="section-title">Bank Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Bank Name</label>
                    <select class="form-select" id="bank_name" name="bank_name">
                        <option value="">Select Bank</option>
                        @foreach($banks as $bank)
                            <option value="{{ $bank->bank_name }}" {{ $user->bank_name === $bank->bank_name ? 'selected' : '' }}>
                                {{ $bank->bank_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Account Number</label>
                    <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" value="{{ $user->bank_account_number }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Account Holder Name</label>
                <input type="text" class="form-control" id="bank_account_holder" name="bank_account_holder" value="{{ $user->bank_account_holder }}">
            </div>
        </div>

        <!-- File Upload Section -->
        <div class="form-section">
            <h3 class="section-title">Document Files</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">KTP File</label>
                    <input type="file" class="form-control" id="ktp_file" name="ktp_file" accept=".pdf,.jpg,.jpeg,.png">
                    @if($user->ktp_file_path)
                        <small class="text-success">
                            <i class="fas fa-file"></i> 
                            <a href="{{ asset('uploads/' . $user->ktp_file_path) }}" target="_blank" class="text-success">View Current KTP File</a>
                        </small>
                    @endif
                    <small class="text-muted">Format: PDF, JPG, PNG (Max: 5MB)</small>
                </div>
                <div class="form-group">
                    <label class="form-label">NPWP File</label>
                    <input type="file" class="form-control" id="npwp_file" name="npwp_file" accept=".pdf,.jpg,.jpeg,.png">
                    @if($user->npwp_file_path)
                        <small class="text-success">
                            <i class="fas fa-file"></i> 
                            <a href="{{ asset('uploads/' . $user->npwp_file_path) }}" target="_blank" class="text-success">View Current NPWP File</a>
                        </small>
                    @endif
                    <small class="text-muted">Format: PDF, JPG, PNG (Max: 5MB)</small>
                </div>
            </div>
        </div>

        <!-- Identity Information -->
        <div class="form-section">
            <h3 class="section-title">Identity Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Identity Type</label>
                    <select class="form-select" id="identity_type" name="identity_type">
                        <option value="">Select Identity Type</option>
                        <option value="ktp" {{ $user->identity_type == 'ktp' ? 'selected' : '' }}>KTP</option>
                        <option value="sim" {{ $user->identity_type == 'sim' ? 'selected' : '' }}>SIM</option>
                        <option value="passport" {{ $user->identity_type == 'passport' ? 'selected' : '' }}>Passport</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Identity Number</label>
                    <input type="text" class="form-control" id="identity_number" name="identity_number" value="{{ $user->identity_number }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">NPWP Number</label>
                    <input type="text" class="form-control" id="npwp_number" name="npwp_number" value="{{ $user->npwp_number }}">
                </div>
                <div class="form-group">
                    <label class="form-label">BPJS Number</label>
                    <input type="text" class="form-control" id="bpjs_number" name="bpjs_number" value="{{ $user->bpjs_number }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">BPJS Date</label>
                <input type="date" class="form-control" id="bpjs_date" name="bpjs_date" value="{{ $user->bpjs_date ? \Carbon\Carbon::parse($user->bpjs_date)->format('Y-m-d') : '' }}">
            </div>
        </div>

        <!-- Company Information -->
        <div class="form-section">
            <h3 class="section-title">Company Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" class="form-control" value="{{ $user->department_name ?? 'N/A' }}" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Position</label>
                    <input type="text" class="form-control" value="{{ $user->position_name ?? 'N/A' }}" disabled>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Employee Status</label>
                    <input type="text" class="form-control" value="{{ $user->employee_status ?? 'N/A' }}" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Join Date</label>
                    <input type="text" class="form-control" value="{{ $user->join_date ? \Carbon\Carbon::parse($user->join_date)->format('d/M/Y') : 'N/A' }}" disabled>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Price Category</label>
                    <input type="text" class="form-control" value="{{ $user->price_category_name ?? 'N/A' }}" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Code</label>
                    <input type="text" class="form-control" value="{{ $user->code ?? 'N/A' }}" disabled>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Roles</label>
                    <input type="text" class="form-control" value="{{ $user->roles ?? 'N/A' }}" disabled>
                </div>
                <div class="form-group">
                    <label class="form-label">Data Restriction</label>
                    <input type="text" class="form-control" value="{{ $user->data_restriction ?? 'N/A' }}" disabled>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="form-section">
            <h3 class="section-title">Change Password</h3>
            <form id="password-form">
                <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                    <small class="text-muted">Enter your current password to verify your identity</small>
                </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required>
                    <small class="text-muted">Re-enter your new password to confirm</small>
                </div>

                <button type="submit" class="btn btn-secondary" id="password-submit-btn">
                    <i class="fas fa-key"></i>
                    <span class="btn-text">Change Password</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Success notification function
    function showSuccessNotification(message) {
        // Create a more prominent success notification
        const notification = $(`
            <div class="success-notification" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: #10b981;
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 8px;
                animation: slideIn 0.3s ease-out;
            ">
                <i class="fas fa-check-circle"></i>
                ${message}
            </div>
        `);
        
        // Add CSS animation
        if (!$('#notification-styles').length) {
            $('head').append(`
                <style id="notification-styles">
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes slideOut {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(100%); opacity: 0; }
                    }
                </style>
            `);
        }
        
        $('body').append(notification);
        
        // Auto remove after 4 seconds
        setTimeout(function() {
            notification.css('animation', 'slideOut 0.3s ease-in');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 4000);
    }

    $(document).ready(function() {
        // AJAX setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Handle photo file selection
        $('#photo_file').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, JPEG, PNG)');
                    $(this).val('');
                    return;
                }
                
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    $(this).val('');
                    return;
                }
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatar = $('.profile-avatar');
                    avatar.html(`
                        <img src="${e.target.result}" alt="{{ $user->name }}">
                        <div class="upload-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                    `);
                };
                reader.readAsDataURL(file);
            }
        });

        // Handle KTP and NPWP file selection
        $('#ktp_file, #npwp_file').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid file (PDF, JPG, JPEG, PNG)');
                    $(this).val('');
                    return;
                }
                
                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    $(this).val('');
                    return;
                }
            }
        });

        // Profile form submission
        $('#profile-form').on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent global loading overlay
            
            // Use FormData for file upload support
            const formData = new FormData();
            formData.append('salutation', $('#salutation').val());
            formData.append('name', $('#name').val());
            formData.append('email', $('#email').val());
            formData.append('gender', $('#gender').val());
            formData.append('marital_status', $('#marital_status').val());
            formData.append('religion', $('#religion').val());
            formData.append('blood_type', $('#blood_type').val());
            formData.append('rhesus', $('#rhesus').val());
            formData.append('phone', $('#phone').val());
            formData.append('handphone_1', $('#handphone_1').val());
            formData.append('handphone_2', $('#handphone_2').val());
            formData.append('emergency_contact_name', $('#emergency_contact_name').val());
            formData.append('emergency_contact_number', $('#emergency_contact_number').val());
            formData.append('identity_type', $('#identity_type').val());
            formData.append('identity_number', $('#identity_number').val());
            formData.append('npwp_number', $('#npwp_number').val());
            formData.append('bpjs_number', $('#bpjs_number').val());
            formData.append('bpjs_date', $('#bpjs_date').val());
            formData.append('address_1', $('#address_1').val());
            formData.append('address_2', $('#address_2').val());
            // New fields
            formData.append('bank_name', $('#bank_name').val());
            formData.append('bank_account_number', $('#bank_account_number').val());
            formData.append('bank_account_holder', $('#bank_account_holder').val());
            
            // Add files if selected
            const photoFile = $('#photo_file')[0].files[0];
            if (photoFile) {
                formData.append('photo_file', photoFile);
            }
            
            const ktpFile = $('#ktp_file')[0].files[0];
            if (ktpFile) {
                formData.append('ktp_file', ktpFile);
            }
            
            const npwpFile = $('#npwp_file')[0].files[0];
            if (npwpFile) {
                formData.append('npwp_file', npwpFile);
            }

            // Hide any global loading overlay
            if (typeof hideLoading === 'function') {
                hideLoading();
            }
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            
            $.ajax({
                url: '{{ route("profile.update") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Ensure loading overlay is hidden
                    if (typeof hideLoading === 'function') {
                        hideLoading();
                    }
                    const loadingOverlay = document.getElementById('loadingOverlay');
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'none';
                    }
                    
                    if (response.status === 'success') {
                        $('#success-alert').text(response.message).show();
                        $('#error-alert').hide();
                        showSuccessNotification('Profile berhasil diperbarui!');
                        
                        // Reload page after 1 second to show updated data
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    // Ensure loading overlay is hidden
                    if (typeof hideLoading === 'function') {
                        hideLoading();
                    }
                    const loadingOverlay = document.getElementById('loadingOverlay');
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'none';
                    }
                    
                    let errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
                    
                    if (xhr.status === 422) {
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = '';
                            for (let field in errors) {
                                errorMessage += errors[field][0] + '\n';
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 0) {
                        errorMessage = 'Tidak dapat terhubung ke server. Silakan coba lagi.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Terjadi kesalahan server. Silakan hubungi administrator.';
                    }
                    
                    $('#error-alert').text(errorMessage).show();
                    $('#success-alert').hide();
                    
                    setTimeout(function() {
                        $('#error-alert').fadeOut();
                    }, 5000);
                }
            });
        });

        // Password form submission
        $('#password-form').on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent global loading overlay
            
            const formData = {
                current_password: $('#current_password').val(),
                new_password: $('#new_password').val(),
                new_password_confirmation: $('#new_password_confirmation').val()
            };

            // Disable button to prevent double submission
            const submitBtn = $('#password-submit-btn');
            submitBtn.prop('disabled', true);
            submitBtn.find('.btn-text').text('Processing...');
            
            // Hide any global loading overlay
            if (typeof hideLoading === 'function') {
                hideLoading();
            }
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            
            $.ajax({
                url: '{{ route("profile.change-password") }}',
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    // Ensure loading overlay is hidden
                    if (typeof hideLoading === 'function') {
                        hideLoading();
                    }
                    const loadingOverlay = document.getElementById('loadingOverlay');
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'none';
                    }
                    
                    if (response.status === 'success') {
                        // Show success message
                        $('#success-alert').text(response.message).show();
                        
                        // Reset form
                        $('#password-form')[0].reset();
                        
                        // Hide error alert if visible
                        $('#error-alert').hide();
                        
                        // Change button to success state temporarily
                        submitBtn.removeClass('btn-secondary').addClass('btn-success');
                        submitBtn.find('.btn-text').html('<i class="fas fa-check"></i> Password Changed!');
                        
                        // Show prominent success notification
                        showSuccessNotification('Password berhasil diubah!');
                        
                        // Show success message for longer
                        setTimeout(function() {
                            $('#success-alert').fadeOut();
                        }, 5000);
                        
                        // Reset button after 3 seconds
                        setTimeout(function() {
                            submitBtn.removeClass('btn-success').addClass('btn-secondary');
                            submitBtn.find('.btn-text').html('<i class="fas fa-key"></i> Change Password');
                            submitBtn.prop('disabled', false);
                        }, 3000);
                    }
                },
                error: function(xhr) {
                    // Ensure loading overlay is hidden
                    if (typeof hideLoading === 'function') {
                        hideLoading();
                    }
                    const loadingOverlay = document.getElementById('loadingOverlay');
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'none';
                    }
                    
                    // Reset button on error
                    submitBtn.prop('disabled', false);
                    submitBtn.find('.btn-text').html('<i class="fas fa-key"></i> Change Password');
                    
                    if (xhr.status === 422) {
                        // Check if it's a validation error or password mismatch
                        if (xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            let errorMessage = '';
                            for (let field in errors) {
                                errorMessage += errors[field][0] + '\n';
                            }
                            $('#error-alert').text(errorMessage).show();
                        } else if (xhr.responseJSON.message) {
                            // Handle password mismatch or other specific errors
                            $('#error-alert').text(xhr.responseJSON.message).show();
                        } else {
                            $('#error-alert').text('Terjadi kesalahan validasi. Silakan coba lagi.').show();
                        }
                    } else if (xhr.status === 0) {
                        $('#error-alert').text('Koneksi timeout. Silakan coba lagi.').show();
                    } else {
                        $('#error-alert').text(xhr.responseJSON.message || 'Terjadi kesalahan. Silakan coba lagi.').show();
                    }
                    setTimeout(function() {
                        $('#error-alert').fadeOut();
                    }, 5000);
                },
                complete: function() {
                    // Ensure button is always enabled after request completes
                    if (submitBtn.prop('disabled')) {
                        submitBtn.prop('disabled', false);
                        submitBtn.find('.btn-text').html('<i class="fas fa-key"></i> Change Password');
                    }
                }
            });
        });
    });
</script>
@endsection
