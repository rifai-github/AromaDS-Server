@extends('layouts.app')

@section('title', 'Master Room Detail - Operational')
@section('breadcrumb', 'Home / Operational / Master Room / Detail')

@section('content')

<style>
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .row {
        margin: 0 !important;
        display: flex !important;
        flex-wrap: wrap !important;
        width: 100% !important;
    }
    
    .col-12 {
        padding: 15px !important;
        width: 100% !important;
    }
    
    .card {
        width: 100% !important;
        margin-bottom: 1rem !important;
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        border: 1px solid rgba(0, 0, 0, 0.125) !important;
        background: white;
    }
    
    .card-header {
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125) !important;
    }
    
    .card-body {
        padding: 1.5rem !important;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
        display: block;
    }

    .info-value-box {
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
        padding: 0.5rem 0.75rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        width: 100%;
        min-height: 42px;
        display: flex;
        align-items: center;
    }

    .editable-input {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.2s;
        background-color: white;
    }

    .editable-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    /* Tab Styles */
    .tab-nav {
        display: flex;
        gap: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 1.5rem;
    }

    .tab-btn {
        padding: 0.75rem 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        color: #64748b;
        border: none;
        background: none;
        cursor: pointer;
        position: relative;
        text-transform: uppercase;
        transition: color 0.2s;
    }

    .tab-btn:hover {
        color: #1e3a8a;
    }

    .tab-btn.active {
        color: #1e3a8a;
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 2px;
        background: #1e3a8a;
    }

    .tab-panel {
        display: none;
    }

    .tab-panel.active {
        display: block;
    }

    /* Table Styles */
    .table-container {
        overflow-x: auto;
    }

    .table th {
        background-color: #f8f9fa !important;
        font-weight: 600 !important;
        color: #475569 !important;
        padding: 12px !important;
        white-space: nowrap;
    }

    .table td {
        padding: 12px !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }

    /* Toast Notification */
    .auto-save-toast {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: #1e293b;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        display: none;
        align-items: center;
        gap: 0.75rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        z-index: 5000;
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    .toast-spinner {
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header Card (Inventory Request Style) -->
            <div class="card mb-3" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
                <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1rem 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('operational.master-rooms.index') }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <h3 class="card-title mb-0" id="headerRoomName" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                {{ $masterRoom->room_name }}
                            </h3>
                        </div>
                        <div id="saveStatus" style="font-size: 0.875rem; color: #cbd5e1;">
                            <!-- Saving status will appear here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Card -->
            <div class="card">
                <div class="card-body">
                    <!-- Nav Tabs -->
                    <div class="tab-nav">
                        <button class="tab-btn active" onclick="switchTab(event, 'room')">ROOM</button>
                        <button class="tab-btn" onclick="switchTab(event, 'rentals')">RENTAL(S)</button>
                    </div>

                    <form id="roomDetailForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" value="{{ $masterRoom->id }}">
                        <input type="hidden" name="is_active" value="{{ $masterRoom->is_active }}">

                        <!-- Tab: Room Details -->
                        <div class="tab-panel active" id="room">
                            <!-- Basic Info Section -->
                            <div class="mb-4">
                                <h5 class="mb-4" style="color: #1e3a8a; font-weight: 700;">
                                    <i class="fas fa-info-circle me-2"></i>Unit Basic Information
                                </h5>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                                    <div>
                                        <label class="info-label">Customer</label>
                                        <div class="info-value-box" style="background: #f1f5f9; color: #1e293b;">
                                            {{ $masterRoom->customer->name ?? '-' }}
                                        </div>
                                    </div>
                                    <div>
                                        <label class="info-label">Gedung</label>
                                        <div class="info-value-box" style="background: #f1f5f9; color: #1e293b;">
                                            {{ $masterRoom->building->building_name ?? '-' }}
                                        </div>
                                    </div>
                                    <div>
                                        <label class="info-label">ID Ruangan</label>
                                        <div class="info-value-box" style="background: #f1f5f9; color: #1e293b;">
                                            #{{ $masterRoom->id }}
                                        </div>
                                    </div>
                                    <div>
                                        <label class="info-label">Nama Ruangan *</label>
                                        <input type="text" name="room_name" class="editable-input auto-save" value="{{ $masterRoom->room_name }}" required>
                                    </div>
                                </div>

                                <div class="mt-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                                    <div>
                                        <label class="info-label">Jenis Ruangan *</label>
                                        <select name="room_type" class="editable-input auto-save" required>
                                            <option value="">-- Choose Room Type --</option>
                                            @foreach($roomTypes as $option)
                                                <option value="{{ $option->option_name }}" {{ $masterRoom->room_type == $option->option_name ? 'selected' : '' }}>
                                                    {{ $option->option_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="info-label">Lantai *</label>
                                        <select name="room_floor" class="editable-input auto-save" required>
                                            <option value="">-- Choose Floor --</option>
                                            @foreach($floors as $option)
                                                <option value="{{ $option->option_name }}" {{ $masterRoom->room_floor == $option->option_name ? 'selected' : '' }}>
                                                    {{ $option->option_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="info-label">Installation Type *</label>
                                        <select name="room_installation_type" class="editable-input auto-save" required>
                                            <option value="">-- Choose Installation Type --</option>
                                            @foreach($installationTypes as $option)
                                                <option value="{{ $option->option_name }}" {{ $masterRoom->room_installation_type == $option->option_name ? 'selected' : '' }}>
                                                    {{ $option->option_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="info-label">Qty *</label>
                                        <input type="number" name="room_qty" class="editable-input auto-save" value="{{ $masterRoom->room_qty }}" required>
                                    </div>
                                </div>

                                <div class="mt-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                                    <div>
                                        <label class="info-label">Lebar (m)</label>
                                        <input type="number" step="0.01" name="room_width" class="editable-input auto-save" value="{{ $masterRoom->room_width }}">
                                    </div>
                                    <div>
                                        <label class="info-label">Panjang (m)</label>
                                        <input type="number" step="0.01" name="room_length" class="editable-input auto-save" value="{{ $masterRoom->room_length }}">
                                    </div>
                                    <div>
                                        <label class="info-label">Tinggi (m)</label>
                                        <input type="number" step="0.01" name="room_height" class="editable-input auto-save" value="{{ $masterRoom->room_height }}">
                                    </div>
                                    <div>
                                        <label class="info-label">Suhu (°C)</label>
                                        <input type="number" step="0.1" name="room_temperature" class="editable-input auto-save" value="{{ $masterRoom->room_temperature }}">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="info-label">Remark</label>
                                    <textarea name="room_remark" class="editable-input auto-save" rows="3">{{ $masterRoom->room_remark }}</textarea>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Tab: Rentals -->
                    <div class="tab-panel" id="rentals">
                        <div class="mb-4">
                            <h5 class="mb-4" style="color: #1e3a8a; font-weight: 700;">
                                <i class="fas fa-list-alt me-2"></i>Rental(s) History & Current Status
                            </h5>
                        </div>
                        <div class="table-container">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 40px; text-align: center;"><input type="checkbox"></th>
                                        <th>Company</th>
                                        <th>Building</th>
                                        <th>Room</th>
                                        <th>Rental Item</th>
                                        <th>Ref No</th>
                                        <th>Plan Install</th>
                                        <th>Install Date</th>
                                        <th>Remove Date</th>
                                        <th>Last Service</th>
                                        <th>Remark</th>
                                        <th>Updated At</th>
                                        <th>Updater</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rentalUnits as $rental)
                                    <tr>
                                        <td style="text-align: center;"><input type="checkbox"></td>
                                        <td>{{ $rental->company_name }}</td>
                                        <td>{{ $rental->building_name }}</td>
                                        <td>{{ $rental->room_name }}</td>
                                        <td>{{ $rental->rental_name }}</td>
                                        <td>{{ $rental->reference_number }}</td>
                                        <td>{{ $rental->expected_install_date ? \Carbon\Carbon::parse($rental->expected_install_date)->format('d/M/Y') : '-' }}</td>
                                        <td>{{ $rental->install_date ? \Carbon\Carbon::parse($rental->install_date)->format('d/M/Y') : '-' }}</td>
                                        <td>{{ $rental->remove_date ? \Carbon\Carbon::parse($rental->remove_date)->format('d/M/Y') : '-' }}</td>
                                        <td>{{ $rental->last_service_date ? \Carbon\Carbon::parse($rental->last_service_date)->format('d/M/Y') : '-' }}</td>
                                        <td>
                                            <span title="{{ $rental->remarks ?? '' }}">
                                                {{ Str::limit($rental->remarks ?: '-', 20) }}
                                            </span>
                                        </td>
                                        <td>{{ $rental->updated_at ? \Carbon\Carbon::parse($rental->updated_at)->format('d/M/Y - H:i') : '-' }}</td>
                                        <td>{{ $rental->updater_name }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="13" class="text-center py-8 text-muted">
                                            <i class="fas fa-info-circle me-2"></i>No rental records found for this room.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto Save Toast -->
<div id="autoSaveToast" class="auto-save-toast">
    <div class="toast-spinner"></div>
    <span id="toastMessage">Saving changes...</span>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Setup Auto-Save for all fields with 'auto-save' class
        const autoSaveFields = document.querySelectorAll('.auto-save');
        autoSaveFields.forEach(field => {
            field.addEventListener('change', function() {
                performAutoSave();
            });
        });
    });

    function switchTab(evt, tabName) {
        // Hide all panels
        document.querySelectorAll('.tab-panel').forEach(panel => {
            panel.classList.remove('active');
        });
        // Deactivate all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show current panel and activate button
        document.getElementById(tabName).classList.add('active');
        evt.currentTarget.classList.add('active');
    }

    function performAutoSave() {
        const form = document.getElementById('roomDetailForm');
        const formData = new FormData(form);
        const id = formData.get('id');
        const roomName = formData.get('room_name');
        
        showToast('Saving changes...', true);
        
        // Update header name in real-time
        if (roomName) {
            document.getElementById('headerRoomName').textContent = roomName;
        }

        fetch(`/operational/master-rooms/${id}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData))
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Changes saved successfully!', false, 'success');
                // Optional: No reload needed for auto-save unless specialized logic required
            } else {
                showToast('Error saving changes: ' + (data.message || 'Unknown error'), false, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to save changes. Please check connection.', false, 'error');
        });
    }

    function showToast(message, showSpinner = false, type = 'info') {
        const toast = document.getElementById('autoSaveToast');
        const toastMessage = document.getElementById('toastMessage');
        const spinner = toast.querySelector('.toast-spinner');
        
        toastMessage.textContent = message;
        spinner.style.display = showSpinner ? 'block' : 'none';
        
        // Change color based on type
        if (type === 'success') {
            toast.style.background = '#059669'; // Emerald-600
        } else if (type === 'error') {
            toast.style.background = '#dc2626'; // Red-600
        } else {
            toast.style.background = '#1e293b'; // Slate-800
        }

        toast.style.display = 'flex';
        
        if (!showSpinner) {
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
    }
</script>
@endpush
