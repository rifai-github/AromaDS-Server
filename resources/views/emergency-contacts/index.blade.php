@extends('layouts.app')

@section('title', 'Emergency Contacts')
@section('breadcrumb', 'Home / System / Emergency Contacts')

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

    .btn-outline {
        background-color: transparent;
        border: 1px solid #d1d5db;
        color: #6b7280;
    }

    .btn-outline:hover {
        background-color: #f9fafb;
        color: #374151;
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
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .responsive-table tbody tr:hover {
        background-color: #f9fafb;
    }

    .responsive-table tbody tr:nth-child(even) {
        background-color: #fafafa;
    }

    .responsive-table tbody tr:nth-child(even):hover {
        background-color: #f3f4f6;
    }

    /* Custom Scrollbar */
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

    /* Statistics Cards */
    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border-left: 4px solid;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .stats-card.primary {
        border-left-color: #3b82f6;
    }

    .stats-card.success {
        border-left-color: #10b981;
    }

    .stats-card.warning {
        border-left-color: #f59e0b;
    }

    .stats-card.danger {
        border-left-color: #ef4444;
    }

    .stats-card.info {
        border-left-color: #06b6d4;
    }

    .stats-number {
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }

    .stats-label {
        font-size: 0.875rem;
        color: #6b7280;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin: 0;
    }

    .stats-icon {
        font-size: 2.5rem;
        opacity: 0.3;
        color: #6b7280;
    }

    /* Badge Styles */
    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-primary {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }

    .badge-warning {
        background-color: #fef3c7;
        color: #92400e;
    }

    .badge-danger {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-info {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .badge-secondary {
        background-color: #f3f4f6;
        color: #6b7280;
    }

    /* Avatar */
    .avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        color: white;
    }

    .avatar-primary {
        background-color: #3b82f6;
    }

    .avatar-success {
        background-color: #10b981;
    }

    .avatar-warning {
        background-color: #f59e0b;
    }

    .avatar-danger {
        background-color: #ef4444;
    }

    .avatar-info {
        background-color: #06b6d4;
    }

    /* Pagination */
    .pagination-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .page-number {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
        color: #374151;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .page-number:hover {
        background-color: #f3f4f6;
        border-color: #9ca3af;
    }

    .page-number.active {
        background-color: #214589;
        color: white;
        border-color: #214589;
    }

    .pagination-btn {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
        color: #374151;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .pagination-btn:hover:not(.disabled) {
        background-color: #f3f4f6;
        border-color: #9ca3af;
    }

    .pagination-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .modal-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 90vw;
        max-height: 90vh;
        width: 600px;
        overflow: hidden;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }

    .modal-overlay.show .modal-container {
        transform: scale(1);
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .modal-close:hover {
        background-color: #f3f4f6;
        color: #374151;
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
        gap: 12px;
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

    /* Detail View Styles */
    .detail-item {
        display: flex;
        margin-bottom: 12px;
        align-items: flex-start;
    }

    .detail-label {
        font-weight: 600;
        color: #374151;
        min-width: 120px;
        margin-right: 12px;
    }

    .detail-value {
        color: #6b7280;
        flex: 1;
    }

    /* Status Badge */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-active {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-inactive {
        background-color: #f3f4f6;
        color: #6b7280;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .stats-card {
            margin-bottom: 15px;
        }

        .responsive-table {
            min-width: 800px;
        }

        .modal-container {
            width: 95vw;
            margin: 20px;
        }

        .pagination-controls {
            flex-direction: column;
            gap: 12px;
        }

        .page-number, .pagination-btn {
            width: 100%;
            text-align: center;
        }
    }

    @media (max-width: 480px) {
        .btn {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
        }

        .stats-number {
            font-size: 1.5rem;
        }

        .stats-icon {
            font-size: 2rem;
        }
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Emergency Contacts Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">
                    <i class="fas fa-phone-alt text-danger me-2"></i>
                    Emergency Contacts
                </h1>
            </div>
            
            <div class="flex flex-row justify-end items-center gap-2">
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i>
                    <span class="hidden md:inline">Add Emergency Contact</span>
                    <span class="md:hidden">Add</span>
                </button>
                <a href="{{ route('emergency-contacts.create-emergency-log') }}" class="btn btn-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="hidden md:inline">Report Emergency</span>
                    <span class="md:hidden">Emergency</span>
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 w-full p-4 bg-white">
            <div class="stats-card primary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stats-label">Total Contacts</p>
                        <p class="stats-number">{{ $emergencyContacts->total() }}</p>
                    </div>
                    <i class="fas fa-address-book stats-icon"></i>
                </div>
            </div>
            
            <div class="stats-card success">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stats-label">Active Contacts</p>
                        <p class="stats-number">{{ $emergencyContacts->where('is_active', true)->count() }}</p>
                    </div>
                    <i class="fas fa-check-circle stats-icon"></i>
                </div>
            </div>
            
            <div class="stats-card warning">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stats-label">Primary Contacts</p>
                        <p class="stats-number">{{ $emergencyContacts->where('contact_type', 'primary')->count() }}</p>
                    </div>
                    <i class="fas fa-star stats-icon"></i>
                </div>
            </div>
            
            <div class="stats-card info">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="stats-label">SMS Enabled</p>
                        <p class="stats-number">{{ $emergencyContacts->where('can_receive_sms', true)->count() }}</p>
                    </div>
                    <i class="fas fa-sms stats-icon"></i>
                </div>
            </div>
        </div>
        
        <!-- Controls Row -->
        <div class="flex flex-row justify-between items-center w-full p-4 bg-white">
            <div class="flex flex-row items-center gap-4">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="selectAll" onchange="selectAll()">
                    <label for="selectAll" class="text-sm text-gray-600">Select All</label>
                </div>
                <button class="btn btn-sm btn-danger" onclick="deleteSelected()" disabled id="deleteSelectedBtn">
                    <i class="fas fa-trash"></i>
                    Delete Selected
                </button>
            </div>
            
            <div class="flex flex-row items-center gap-2">
                <input type="text" placeholder="Search contacts..." class="form-control" style="width: 200px;" id="searchInput">
                <button class="btn btn-secondary" onclick="filterContacts()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        
        <!-- Table Container -->
        <div class="table-container">
            @if($emergencyContacts->count() > 0)
                <table class="responsive-table">
                    <thead>
                        <tr>
                            <th data-no-filter>
                                <input type="checkbox" id="selectAllHeader" onchange="selectAll()">
                            </th>
                            <th data-column="name">Contact</th>
                            <th data-column="relationship">Relationship</th>
                            <th data-column="type">Type</th>
                            <th data-column="phone">Phone</th>
                            <th data-column="email">Email</th>
                            <th data-column="channels">Channels</th>
                            <th data-column="status">Status</th>
                            <th data-no-filter>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($emergencyContacts as $contact)
                            <tr>
                                <td>
                                    <input type="checkbox" class="contact-checkbox" value="{{ $contact->id }}">
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar avatar-primary">
                                            {{ strtoupper(substr($contact->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900">{{ $contact->name }}</div>
                                            @if($contact->notes)
                                                <div class="text-sm text-gray-500">{{ Str::limit($contact->notes, 30) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ ucfirst($contact->relationship) }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $contact->contact_type === 'primary' ? 'badge-warning' : 'badge-info' }}">
                                        {{ ucfirst($contact->contact_type) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="tel:{{ $contact->phone_number }}" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-phone me-1"></i>
                                        {{ $contact->formatted_phone ?? $contact->phone_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($contact->email)
                                        <a href="mailto:{{ $contact->email }}" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-envelope me-1"></i>
                                            {{ $contact->email }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex gap-1">
                                        @if($contact->can_receive_sms)
                                            <span class="badge badge-success" title="SMS">
                                                <i class="fas fa-sms"></i>
                                            </span>
                                        @endif
                                        @if($contact->can_receive_email)
                                            <span class="badge badge-info" title="Email">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                        @endif
                                        @if($contact->can_receive_whatsapp)
                                            <span class="badge badge-success" title="WhatsApp">
                                                <i class="fab fa-whatsapp"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($contact->is_active)
                                        <span class="status-badge status-active">
                                            <i class="fas fa-check-circle me-1"></i>
                                            Active
                                        </span>
                                    @else
                                        <span class="status-badge status-inactive">
                                            <i class="fas fa-pause-circle me-1"></i>
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary" onclick="openViewModal({{ $contact->id }})" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="{{ route('emergency-contacts.edit', $contact) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('emergency-contacts.toggle-status', $contact) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $contact->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }}" 
                                                    title="{{ $contact->is_active ? 'Deactivate' : 'Activate' }}"
                                                    onclick="return confirm('Apakah kamu yakin ingin {{ $contact->is_active ? 'menonaktifkan' : 'mengaktifkan' }} kontak ini?')">
                                                <i class="fas {{ $contact->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('emergency-contacts.destroy', $contact) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                                                    onclick="return confirm('Apakah kamu yakin ingin menghapus kontak darurat ini?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center py-12">
                    <div class="mb-4">
                        <i class="fas fa-phone-alt fa-4x text-gray-300"></i>
                    </div>
                    <h4 class="text-gray-600 mb-2">Belum Ada Kontak Darurat</h4>
                    <p class="text-gray-500 mb-6">Kamu belum menambahkan kontak darurat. Tambahkan kontak darurat pertamamu untuk mulai menggunakan fitur ini.</p>
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus me-2"></i>
                        Tambah Kontak Darurat Pertama
                    </button>
                </div>
            @endif
        </div>
        
        <!-- Pagination -->
        @if($emergencyContacts->total() > 0)
            <div class="flex flex-row justify-center items-center w-full p-4 bg-white rounded-b-[10px] border-t">
                {{ $emergencyContacts->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal -->
<div id="modalOverlay" class="modal-overlay" onclick="closeModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Modal Title</h3>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Modal content will be inserted here -->
        </div>
        <div class="modal-footer" id="modalFooter">
            <!-- Modal footer will be inserted here -->
        </div>
    </div>
</div>

<script>
// Modal functions
function openModal(title) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function openCreateModal() {
    openModal('Tambah Kontak Darurat');
    
    document.getElementById('modalBody').innerHTML = `
        <form id="createForm">
            <div class="form-section">
                <div class="section-title">Basic Information</div>
                <div class="form-group">
                    <label class="form-label">Name <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Relationship <span class="required">*</span></label>
                    <select name="relationship" class="form-control" required>
                        <option value="">Pilih Hubungan</option>
                        <option value="spouse">Pasangan</option>
                        <option value="parent">Orang Tua</option>
                        <option value="sibling">Saudara Kandung</option>
                        <option value="child">Anak</option>
                        <option value="friend">Teman</option>
                        <option value="colleague">Rekan Kerja</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Type <span class="required">*</span></label>
                    <select name="contact_type" class="form-control" required>
                        <option value="">Pilih Tipe</option>
                        <option value="primary">Utama</option>
                        <option value="secondary">Cadangan</option>
                        <option value="emergency">Darurat</option>
                    </select>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Contact Information</div>
                <div class="form-group">
                    <label class="form-label">Phone Number <span class="required">*</span></label>
                    <input type="tel" name="phone_number" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Notification Preferences</div>
                <div class="form-group">
                    <label class="form-label">Notification Channels</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="can_receive_sms" value="1">
                            <span>SMS</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="can_receive_email" value="1">
                            <span>Email</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="can_receive_whatsapp" value="1">
                            <span>WhatsApp</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-title">Additional Information</div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan tentang kontak ini..."></textarea>
                </div>
            </div>
        </form>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
        <button class="btn btn-primary" onclick="submitCreateForm()">Tambah Kontak Darurat</button>
    `;
}

function openViewModal(id) {
    openModal('Lihat Kontak Darurat');
    
    // Get contact data (this would typically come from an API call)
    const contact = {
        id: id,
        name: 'John Doe',
        relationship: 'spouse',
        contact_type: 'primary',
        phone_number: '+6281234567890',
        email: 'john.doe@example.com',
        address: '123 Main Street, City, Country',
        can_receive_sms: true,
        can_receive_email: true,
        can_receive_whatsapp: false,
        is_active: true,
        notes: 'Emergency contact for family matters'
    };
    
    document.getElementById('modalBody').innerHTML = `
        <div class="form-section">
            <div class="section-title">Basic Information</div>
            <div class="detail-item">
                <div class="detail-label">Name:</div>
                <div class="detail-value">${contact.name}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Relationship:</div>
                <div class="detail-value">${contact.relationship}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Type:</div>
                <div class="detail-value">${contact.contact_type}</div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title">Contact Information</div>
            <div class="detail-item">
                <div class="detail-label">Phone:</div>
                <div class="detail-value">${contact.phone_number}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Email:</div>
                <div class="detail-value">${contact.email}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Address:</div>
                <div class="detail-value">${contact.address}</div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title">Notification Preferences</div>
            <div class="detail-item">
                <div class="detail-label">SMS:</div>
                <div class="detail-value">${contact.can_receive_sms ? 'Ya' : 'Tidak'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Email:</div>
                <div class="detail-value">${contact.can_receive_email ? 'Ya' : 'Tidak'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">WhatsApp:</div>
                <div class="detail-value">${contact.can_receive_whatsapp ? 'Ya' : 'Tidak'}</div>
            </div>
        </div>
        
        <div class="form-section">
            <div class="section-title">Status & Notes</div>
            <div class="detail-item">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    <span class="status-badge ${contact.is_active ? 'status-active' : 'status-inactive'}">
                        ${contact.is_active ? 'Aktif' : 'Tidak Aktif'}
                    </span>
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Notes:</div>
                <div class="detail-value">${contact.notes || 'Tidak ada catatan'}</div>
            </div>
        </div>
    `;
    
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Tutup</button>
        <a href="/emergency-contacts/${contact.id}/edit" class="btn btn-primary">Edit Kontak</a>
    `;
}

function submitCreateForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    
    // Here you would typically send the data to the server
    console.log('Creating emergency contact:', Object.fromEntries(formData));
    
    // Close modal and refresh page
    closeModal();
    location.reload();
}

// Select all functionality
function selectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const contactCheckboxes = document.querySelectorAll('.contact-checkbox');
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    
    contactCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateDeleteButton();
}

function updateDeleteButton() {
    const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    
    if (checkedBoxes.length > 0) {
        deleteBtn.disabled = false;
        deleteBtn.textContent = `Hapus Terpilih (${checkedBoxes.length})`;
    } else {
        deleteBtn.disabled = true;
        deleteBtn.textContent = 'Hapus Terpilih';
    }
}

// Add event listeners to individual checkboxes
document.addEventListener('DOMContentLoaded', function() {
    const contactCheckboxes = document.querySelectorAll('.contact-checkbox');
    contactCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateDeleteButton);
    });
});

function deleteSelected() {
    const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
    if (checkedBoxes.length === 0) return;
    
    if (confirm(`Apakah kamu yakin ingin menghapus ${checkedBoxes.length} kontak terpilih?`)) {
        // Here you would typically send the delete request to the server
        console.log('Deleting contacts:', Array.from(checkedBoxes).map(cb => cb.value));
        location.reload();
    }
}

function filterContacts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Close modal when clicking outside
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endsection
