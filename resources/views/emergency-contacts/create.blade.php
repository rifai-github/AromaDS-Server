@extends('layouts.app')

@section('title', 'Add Emergency Contact')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-plus text-primary me-2"></i>
                        Add Emergency Contact
                    </h1>
                    <p class="text-muted mb-0">Add a new emergency contact to your list</p>
                </div>
                <div>
                    <a href="{{ route('emergency-contacts.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Contacts
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-plus me-2"></i>
                        Emergency Contact Information
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('emergency-contacts.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <!-- Name -->
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-1"></i>
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       placeholder="Enter full name"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Relationship -->
                            <div class="col-md-6 mb-3">
                                <label for="relationship" class="form-label">
                                    <i class="fas fa-heart me-1"></i>
                                    Relationship <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('relationship') is-invalid @enderror" 
                                        id="relationship" 
                                        name="relationship" 
                                        required>
                                    <option value="">Select relationship</option>
                                    <option value="spouse" {{ old('relationship') == 'spouse' ? 'selected' : '' }}>Spouse</option>
                                    <option value="parent" {{ old('relationship') == 'parent' ? 'selected' : '' }}>Parent</option>
                                    <option value="sibling" {{ old('relationship') == 'sibling' ? 'selected' : '' }}>Sibling</option>
                                    <option value="friend" {{ old('relationship') == 'friend' ? 'selected' : '' }}>Friend</option>
                                    <option value="colleague" {{ old('relationship') == 'colleague' ? 'selected' : '' }}>Colleague</option>
                                    <option value="other" {{ old('relationship') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('relationship')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Phone Number -->
                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label">
                                    <i class="fas fa-phone me-1"></i>
                                    Phone Number <span class="text-danger">*</span>
                                </label>
                                <input type="tel" 
                                       class="form-control @error('phone_number') is-invalid @enderror" 
                                       id="phone_number" 
                                       name="phone_number" 
                                       value="{{ old('phone_number') }}" 
                                       placeholder="Enter phone number"
                                       required>
                                @error('phone_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>
                                    Email Address
                                </label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       placeholder="Enter email address">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Contact Type -->
                            <div class="col-md-6 mb-3">
                                <label for="contact_type" class="form-label">
                                    <i class="fas fa-star me-1"></i>
                                    Contact Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('contact_type') is-invalid @enderror" 
                                        id="contact_type" 
                                        name="contact_type" 
                                        required>
                                    <option value="">Select contact type</option>
                                    <option value="primary" {{ old('contact_type') == 'primary' ? 'selected' : '' }}>Primary</option>
                                    <option value="secondary" {{ old('contact_type') == 'secondary' ? 'selected' : '' }}>Secondary</option>
                                    <option value="backup" {{ old('contact_type') == 'backup' ? 'selected' : '' }}>Backup</option>
                                </select>
                                @error('contact_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Address -->
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    Address
                                </label>
                                <input type="text" 
                                       class="form-control @error('address') is-invalid @enderror" 
                                       id="address" 
                                       name="address" 
                                       value="{{ old('address') }}" 
                                       placeholder="Enter address">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Notification Preferences -->
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-bell me-1"></i>
                                Notification Preferences
                            </label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="can_receive_sms" 
                                               name="can_receive_sms" 
                                               value="1" 
                                               {{ old('can_receive_sms', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="can_receive_sms">
                                            <i class="fas fa-sms text-success me-1"></i>
                                            SMS Notifications
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="can_receive_email" 
                                               name="can_receive_email" 
                                               value="1" 
                                               {{ old('can_receive_email', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="can_receive_email">
                                            <i class="fas fa-envelope text-info me-1"></i>
                                            Email Notifications
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="can_receive_whatsapp" 
                                               name="can_receive_whatsapp" 
                                               value="1" 
                                               {{ old('can_receive_whatsapp') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="can_receive_whatsapp">
                                            <i class="fab fa-whatsapp text-success me-1"></i>
                                            WhatsApp Notifications
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note me-1"></i>
                                Notes
                            </label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="Add any additional notes about this contact">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('emergency-contacts.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Save Emergency Contact
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-question-circle me-2"></i>
                        Emergency Contact Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Contact Types</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong>Primary:</strong> Main emergency contact, notified first
                                </li>
                                <li class="mb-2">
                                    <strong>Secondary:</strong> Backup contact, notified if primary doesn't respond
                                </li>
                                <li class="mb-0">
                                    <strong>Backup:</strong> Additional contact for critical emergencies
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Notification Channels</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong>SMS:</strong> Fast, reliable text messages
                                </li>
                                <li class="mb-2">
                                    <strong>Email:</strong> Detailed emergency information
                                </li>
                                <li class="mb-0">
                                    <strong>WhatsApp:</strong> Instant messaging for urgent alerts
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
    padding: 0.75rem;
}

.form-control:focus, .form-select:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
}

.form-check-input:checked {
    background-color: #4e73df;
    border-color: #4e73df;
}

.card {
    border: none;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.btn {
    border-radius: 0.35rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
}

.text-danger {
    color: #e74a3b !important;
}

.text-primary {
    color: #4e73df !important;
}
</style>
@endsection
