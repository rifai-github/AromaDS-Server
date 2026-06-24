@extends('layouts.app')

@section('title', 'Serial Number - ' . ($serialNumber->serial_number ?? 'SN'))
@section('breadcrumb', 'Home / Warehouse / Serial Numbers / ' . ($serialNumber->serial_number ?? 'SN'))

@section('content')
<style>
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .card {
        border-radius: 8px !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        border: 1px solid rgba(0, 0, 0, 0.125) !important;
        margin-bottom: 1rem !important;
    }

    .nav-tabs {
        border-bottom: 2px solid #1e3a8a !important;
        display: flex !important;
    }
    .nav-tabs .nav-item { flex: 1; }
    .nav-tabs .nav-link {
        border: none !important;
        border-radius: 0 !important;
        padding: 12px 10px !important;
        width: 100% !important;
        text-align: center !important;
        color: #6c757d;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .nav-tabs .nav-link:hover {
        background-color: #f8f9fa !important;
    }
    .nav-tabs .nav-link.active {
        background-color: white !important;
        border-bottom: 3px solid #1e3a8a !important;
        color: #1e3a8a !important;
        font-weight: bold !important;
    }
    .tab-pane {
        display: none !important;
    }
    .tab-pane.active.show {
        display: block !important;
    }
    
    /* Timeline styles */
    .timeline {
        position: relative;
        padding: 20px 0;
    }
    .timeline-item {
        padding: 1rem;
        border-left: 3px solid #e9ecef;
        margin-left: 1.5rem;
        position: relative;
        padding-bottom: 2rem;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -10px;
        top: 1.2rem;
        width: 17px;
        height: 17px;
        background: #fff;
        border: 4px solid #1e3a8a;
        border-radius: 50%;
        z-index: 2;
    }
    .timeline-item:last-child {
        border-left: 3px solid transparent;
    }
    
    .refresh-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #28a745;
        margin-right: 0.5rem;
        animation: pulse 2s infinite;
    }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
</style>

<div class="container-fluid" style="padding: 20px;">
    <!-- Header - Matching Inventory Request -->
    <div class="card mb-3" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
        <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1rem 1.5rem;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('warehouse.serial-numbers.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div>
                    <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                        <i class="fas fa-barcode me-2"></i>{{ $serialNumber->serial_number ?? 'N/A' }}
                    </h3>
                </div>
                <div>
                    <button onclick="openEditModal()" class="btn btn-light btn-sm">
                        <i class="fas fa-edit"></i> Edit Status
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Basic Info Section - Matching Inventory Request Style -->
    <div class="card mb-3">
        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a; padding: 0.75rem 1.5rem;">
            <h5 class="card-title mb-0" style="color: #1e3a8a;">
                <i class="fas fa-info-circle me-2"></i>Basic Info
            </h5>
        </div>
        <div class="card-body" style="padding: 1rem 1.5rem;">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Serial Number</div>
                    <div style="font-size: 1rem; font-weight: 600; color: #212529;">{{ $serialNumber->serial_number }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Product Name</div>
                    <div style="font-size: 1rem; color: #212529;">{{ $serialNumber->masterProduct->name ?? '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Status</div>
                    <div>
                        @php
                            $statusClass = match($serialNumber->status) {
                                'ready', 'available' => 'bg-success',
                                'broken', 'damaged' => 'bg-danger',
                                'on_service', 'maintenance' => 'bg-warning text-dark',
                                'in_use' => 'bg-info',
                                default => 'bg-secondary'
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                            {{ strtoupper($serialNumber->status_text) }}
                        </span>
                    </div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Kondisi Unit</div>
                    <div>
                        @php
                            $conditionClass = match($serialNumber->effective_condition_status) {
                                \App\Models\SerialNumber::CONDITION_DAMAGED => 'bg-danger',
                                \App\Models\SerialNumber::CONDITION_SECOND_READY => 'bg-warning text-dark',
                                default => 'bg-success',
                            };
                        @endphp
                        <span class="badge {{ $conditionClass }}" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                            {{ strtoupper($serialNumber->condition_label) }}
                        </span>
                    </div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Warehouse</div>
                    <div style="font-size: 1rem; color: #212529;">{{ $serialNumber->warehouse->name ?? '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">Location Type</div>
                    <div style="font-size: 1rem; color: #212529;">{{ $serialNumber->effective_location_type_text }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.5rem;">SKU</div>
                    <div style="font-size: 1rem; color: #212529;">{{ $serialNumber->masterProduct->sku ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Information Section - Matching Inventory Request Style -->
    <div class="card mb-3">
        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #1e3a8a; padding: 0.75rem 1.5rem;">
            <h5 class="card-title mb-0" style="color: #1e3a8a; font-size: 0.95rem;">
                <i class="fas fa-history me-2"></i>Audit Information
            </h5>
        </div>
        <div class="card-body" style="padding: 1rem 1.5rem;">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Created By</div>
                    <div style="font-size: 0.9rem; color: #212529;">{{ $serialNumber->createdBy->name ?? '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Created At</div>
                    <div style="font-size: 0.9rem; color: #212529;">{{ $serialNumber->created_at ? $serialNumber->created_at->format('d/M/Y H:i') : '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated By</div>
                    <div style="font-size: 0.9rem; color: #212529;">{{ $serialNumber->updatedBy->name ?? '-' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; margin-bottom: 0.25rem;">Last Updated At</div>
                    <div style="font-size: 0.9rem; color: #212529;">{{ $serialNumber->updated_at ? $serialNumber->updated_at->format('d/M/Y H:i') : '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs System -->
    <div class="card mb-3">
        <div class="card-body p-0">
            <ul class="nav nav-tabs" id="snTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="monitor-tab" data-bs-toggle="tab" data-bs-target="#monitor" type="button" role="tab">
                        <i class="fas fa-desktop me-2"></i>MONITORING
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="scan-tab" data-bs-toggle="tab" data-bs-target="#scan" type="button" role="tab">
                        <i class="fas fa-history me-2"></i>SCAN HISTORY
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="install-tab" data-bs-toggle="tab" data-bs-target="#install" type="button" role="tab">
                        <i class="fas fa-tools me-2"></i>INSTALLATION
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="service-tab" data-bs-toggle="tab" data-bs-target="#service" type="button" role="tab">
                        <i class="fas fa-sync me-2"></i>SERVICE
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="repair-tab" data-bs-toggle="tab" data-bs-target="#repair" type="button" role="tab">
                        <i class="fas fa-wrench me-2"></i>REPAIR
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="snTabsContent">
        <!-- Tab 1: MONITORING -->
        <div class="tab-pane fade show active" id="monitor" role="tabpanel" aria-labelledby="monitor-tab">
            <div class="card info-card">
                <div class="card-body">
                    @if($hasWifi)
                    <div class="text-center py-5">
                        <span class="refresh-indicator"></span> <strong class="text-success">Unit is Online</strong>
                        <p class="text-muted mt-2">Live monitoring data for SN: <strong>{{ $serialNumber->serial_number }}</strong></p>
                        <hr style="width: 50%; margin: 20px auto;">
                        <div class="row justify-content-center">
                            <div class="col-md-3">
                                <div class="p-3 border rounded bg-light">
                                    <small class="text-muted d-block uppercase" style="font-size: 0.7rem; font-weight: 700;">Current Temp</small>
                                    <h4 class="mb-0">{{ $unitOnWall->temperature ?? '-' }}°C</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded bg-light">
                                    <small class="text-muted d-block uppercase" style="font-size: 0.7rem; font-weight: 700;">Last Seen</small>
                                    <h4 class="mb-0" style="font-size: 1rem;">{{ $unitOnWall->last_seen_at ? $unitOnWall->last_seen_at->format('H:i:s') : '-' }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-wifi-slash fa-4x mb-3" style="opacity: 0.3;"></i>
                        <h5>No Monitoring System Connected</h5>
                        <p>This serial number is not associated with an active WiFi-enabled unit.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tab 2: SCAN HISTORY -->
        <div class="tab-pane fade" id="scan" role="tabpanel" aria-labelledby="scan-tab">
            <div class="card info-card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-barcode me-2"></i>Log Scan & Movement</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @forelse($movementHistories as $move)
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 font-weight-bold text-primary">{{ strtoupper($move->label) }}</h6>
                                <span class="badge bg-{{ $move->badge }}">{{ strtoupper($move->action) }}</span>
                            </div>
                            <div class="text-muted small mt-1">
                                <i class="fas fa-calendar-alt me-1"></i> {{ \Carbon\Carbon::parse($move->date)->format('d/M/Y H:i') }}
                                <i class="fas fa-user ms-3 me-1"></i> {{ $move->user }}
                            </div>
                            <div class="mt-2 text-dark">
                                <p class="mb-1"><strong>Reference:</strong> {{ $move->reference }}</p>
                                <p class="mb-0">{{ $move->notes }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No scan activities recorded yet.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: INSTALLATION -->
        <div class="tab-pane fade" id="install" role="tabpanel" aria-labelledby="install-tab">
            <div class="card info-card">
                <div class="card-header text-white" style="background-color: #6f42c1;">
                    <h5 class="mb-0"><i class="fas fa-home me-2"></i>Installation & Location History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Action</th>
                                    <th>Date</th>
                                    <th>Customer / Location</th>
                                    <th>Technician</th>
                                    <th>Ref</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($installHistories as $history)
                                <tr>
                                    <td><span class="badge bg-{{ $history->badge }}">{{ strtoupper($history->label) }}</span></td>
                                    <td>{{ $history->action_date ? \Carbon\Carbon::parse($history->action_date)->format('d/M/Y') : '-' }}</td>
                                    <td>
                                        <strong>{{ $history->customer_name }}</strong><br>
                                        <small class="text-muted">{{ $history->location }}</small>
                                    </td>
                                    <td>{{ $history->technician_name }}</td>
                                    <td>
                                        @if($history->job_schedule_id)
                                            <a href="{{ route('operational.job-schedules.show', $history->job_schedule_id) }}" target="_blank" rel="noopener noreferrer">
                                                {{ $history->job_schedule_number }}
                                            </a>
                                        @else
                                            {{ $history->job_schedule_number ?? '-' }}
                                        @endif
                                    </td>
                                    <td>{{ $history->notes }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center py-4">No installation history found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 4: SERVICE -->
        <div class="tab-pane fade" id="service" role="tabpanel" aria-labelledby="service-tab">
            <div class="card info-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-sync me-2"></i>Service Logs</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Service Date</th>
                                    <th>Customer</th>
                                    <th>Technician</th>
                                    <th>Ref</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($serviceHistories as $history)
                                <tr>
                                    <td><i class="fas fa-calendar-check me-1"></i> {{ $history->action_date ? \Carbon\Carbon::parse($history->action_date)->format('d/M/Y') : '-' }}</td>
                                    <td><strong>{{ $history->customer_name }}</strong></td>
                                    <td>{{ $history->technician_name }}</td>
                                    <td>{{ $history->job_schedule_number ?? '-' }}</td>
                                    <td>{{ $history->notes }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">No service history found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 5: REPAIR -->
        <div class="tab-pane fade" id="repair" role="tabpanel" aria-labelledby="repair-tab">
            <div class="card info-card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-wrench me-2"></i>Repair History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Repair No</th>
                                    <th>Date</th>
                                    <th>Problem Description</th>
                                    <th>Work Performed</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($repairHistories as $repair)
                                <tr>
                                    <td>{{ $repair->repair_number }}</td>
                                    <td>{{ $repair->reported_at->format('d/M/Y') }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($repair->problem_description, 50) }}</td>
                                    <td>{{ $repair->repair_work_performed ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $repair->repair_status === 'completed' ? 'success' : 'warning' }}">
                                            {{ strtoupper($repair->repair_status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">No repair history found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
    <div class="modal-container" style="background: white; border-radius: 8px; width: 500px; max-width: 90%; overflow: hidden;">
        <div class="modal-header d-flex justify-content-between align-items-center p-3 bg-primary text-white">
            <h5 class="modal-title mb-0">Update Status Serial Number</h5>
            <button type="button" class="btn-close btn-close-white" onclick="closeEditModal()"></button>
        </div>
        <form id="editForm" onsubmit="updateSerialNumber(event)">
            <div class="modal-body p-3">
                <div class="mb-3 text-dark">
                    <label class="form-label font-weight-bold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="ready" {{ in_array($serialNumber->status, ['ready', 'available']) ? 'selected' : '' }}>Ready</option>
                        <option value="on_hand" {{ $serialNumber->status === 'on_hand' ? 'selected' : '' }}>On Hand</option>
                        <option value="on_hand_remove" {{ $serialNumber->status === 'on_hand_remove' ? 'selected' : '' }}>On Hand Remove</option>
                        <option value="broken" {{ in_array($serialNumber->status, ['broken', 'damaged']) ? 'selected' : '' }}>Broken</option>
                        <option value="on_service" {{ in_array($serialNumber->status, ['on_service', 'maintenance']) ? 'selected' : '' }}>On Service</option>
                        <option value="in_use" {{ $serialNumber->status === 'in_use' ? 'selected' : '' }}>In Use</option>
                        <option value="retired" {{ $serialNumber->status === 'retired' ? 'selected' : '' }}>Retired</option>
                    </select>
                </div>
                <div class="mb-3 text-dark">
                    <label class="form-label font-weight-bold">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan jika diperlukan...">{{ $serialNumber->notes ?? '' }}</textarea>
                </div>
                <div class="mb-3 text-dark">
                    <label class="form-label font-weight-bold">Kondisi Unit</label>
                    <select name="condition_status" class="form-select">
                        <option value="new" {{ $serialNumber->effective_condition_status === \App\Models\SerialNumber::CONDITION_NEW ? 'selected' : '' }}>Baru</option>
                        <option value="second_ready" {{ $serialNumber->effective_condition_status === \App\Models\SerialNumber::CONDITION_SECOND_READY ? 'selected' : '' }}>Bekas / Siap Pakai</option>
                        <option value="damaged" {{ $serialNumber->effective_condition_status === \App\Models\SerialNumber::CONDITION_DAMAGED ? 'selected' : '' }}>Rusak</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer p-3 bg-light d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal() { document.getElementById('editModal').style.display = 'flex'; }
function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

function updateSerialNumber(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    fetch('{{ route("warehouse.serial-numbers.update", $serialNumber->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            status: formData.get('status'),
            condition_status: formData.get('condition_status'),
            notes: formData.get('notes'),
            _method: 'PUT'
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Berhasil', 'Status Serial Number diperbarui.', 'success').then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'Gagal memperbarui status.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
    });
}

// Ensure first tab is active on load
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-bs-target');
            const target = document.querySelector(targetId);
            
            // Hide all panes
            document.querySelectorAll('.tab-content .tab-pane').forEach(pane => {
                pane.classList.remove('active', 'show');
            });
            
            // Show target pane
            target.classList.add('active', 'show');
            
            // Toggle active class on buttons
            tabButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>

@endsection

