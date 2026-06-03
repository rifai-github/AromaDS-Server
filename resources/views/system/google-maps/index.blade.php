@extends('layouts.app')

@section('title', 'Google Maps Settings')
@section('breadcrumb', 'Home / System / Google Maps Settings')

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

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
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

    /* Card Styles */
    .card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        padding: 24px;
        margin-bottom: 24px;
    }

    .card-header {
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 16px;
        margin-bottom: 20px;
    }

    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }

    .card-description {
        color: #6b7280;
        font-size: 14px;
        margin-top: 4px;
    }

    /* Status Badge */
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .status-active {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-inactive {
        background-color: #fee2e2;
        color: #991b1b;
    }

    /* Grid Layout */
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

    /* Alert Styles */
    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 16px;
        border: 1px solid;
    }

    .alert-info {
        background-color: #eff6ff;
        border-color: #bfdbfe;
        color: #1e40af;
    }

    .alert-warning {
        background-color: #fffbeb;
        border-color: #fed7aa;
        color: #92400e;
    }

    .alert-success {
        background-color: #f0fdf4;
        border-color: #bbf7d0;
        color: #166534;
    }

    /* Test Button */
    .btn-test {
        background-color: #10b981;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-test:hover {
        background-color: #059669;
    }

    .btn-test:disabled {
        background-color: #9ca3af;
        cursor: not-allowed;
    }
</style>

<div class="flex flex-col w-full min-h-screen">
    <div class="flex flex-col justify-center items-start w-full max-w-[96%] mx-auto mb-[20px] md:mb-[30px] lg:mb-[40px]">
        
        <!-- Google Maps Settings Header -->
        <div class="flex flex-row justify-between items-center w-full bg-white rounded-t-[10px] p-4">
            <div class="flex flex-row justify-start items-center w-full">
                <h1 class="text-xl font-semibold text-[#214589]">Google Maps Settings</h1>
            </div>
        </div>

        <!-- Settings Form -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">API Configuration</h2>
                <p class="card-description">Configure Google Maps API settings for location services</p>
            </div>

            <form id="googleMapsForm" onsubmit="saveSettings(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="form-label">Google Maps API Key *</label>
                        <input type="text" name="google_maps_api_key" class="form-input" 
                               value="{{ $settings['google_maps_api_key'] ?? '' }}" 
                               placeholder="Enter your Google Maps API Key" required>
                        <small class="text-gray-500">Required for map display and geocoding</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Default Map Center Latitude</label>
                        <input type="number" name="default_latitude" class="form-input" 
                               value="{{ $settings['default_latitude'] ?? '-6.2088' }}" 
                               step="0.000001" placeholder="Default latitude">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Default Map Center Longitude</label>
                        <input type="number" name="default_longitude" class="form-input" 
                               value="{{ $settings['default_longitude'] ?? '106.8456' }}" 
                               step="0.000001" placeholder="Default longitude">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Default Zoom Level</label>
                        <select name="default_zoom" class="form-input">
                            <option value="1" {{ ($settings['default_zoom'] ?? '15') == '1' ? 'selected' : '' }}>1 - World</option>
                            <option value="5" {{ ($settings['default_zoom'] ?? '15') == '5' ? 'selected' : '' }}>5 - Landmass/continent</option>
                            <option value="10" {{ ($settings['default_zoom'] ?? '15') == '10' ? 'selected' : '' }}>10 - City</option>
                            <option value="15" {{ ($settings['default_zoom'] ?? '15') == '15' ? 'selected' : '' }}>15 - Streets</option>
                            <option value="20" {{ ($settings['default_zoom'] ?? '15') == '20' ? 'selected' : '' }}>20 - Buildings</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Map Type</label>
                    <select name="map_type" class="form-input">
                        <option value="roadmap" {{ ($settings['map_type'] ?? 'roadmap') == 'roadmap' ? 'selected' : '' }}>Roadmap</option>
                        <option value="satellite" {{ ($settings['map_type'] ?? 'roadmap') == 'satellite' ? 'selected' : '' }}>Satellite</option>
                        <option value="hybrid" {{ ($settings['map_type'] ?? 'roadmap') == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                        <option value="terrain" {{ ($settings['map_type'] ?? 'roadmap') == 'terrain' ? 'selected' : '' }}>Terrain</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">API Restrictions (Optional)</label>
                    <textarea name="api_restrictions" class="form-input form-textarea" 
                              placeholder="Describe any API restrictions or usage limits">{{ $settings['api_restrictions'] ?? '' }}</textarea>
                </div>

                <div class="flex flex-row justify-between items-center mt-6">
                    <button type="button" class="btn btn-test" onclick="testApiKey()">
                        <i class="fas fa-vial"></i>
                        Test API Key
                    </button>
                    
                    <div class="flex gap-3">
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i>
                            Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- API Status Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">API Status</h2>
                <p class="card-description">Current status of Google Maps API integration</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="status-badge {{ ($settings['google_maps_api_key'] ?? '') ? 'status-active' : 'status-inactive' }}">
                        {{ ($settings['google_maps_api_key'] ?? '') ? 'Active' : 'Inactive' }}
                    </div>
                    <p class="text-sm text-gray-600 mt-2">API Key Status</p>
                </div>

                <div class="text-center">
                    <div class="status-badge status-system">
                        {{ $settings['updated_by_name'] ?? 'System' }}
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Last Updated By</p>
                </div>

                <div class="text-center">
                    <div class="status-badge status-system">
                        {{ isset($settings['updated_at']) ? \Carbon\Carbon::parse($settings['updated_at'])->format('d/M/Y H:i') : 'Never' }}
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Last Updated At</p>
                </div>

                <div class="text-center">
                    <div class="status-badge status-active">
                        Enabled
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Location Services</p>
                </div>

                <div class="text-center">
                    <div class="status-badge status-active">
                        Ready
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Map Integration</p>
                </div>
            </div>
        </div>

        <!-- Usage Information -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Usage Information</h2>
                <p class="card-description">How Google Maps API is used in the system</p>
            </div>

            <div class="alert alert-info">
                <strong>Location Display:</strong> When admin clicks on latitude/longitude coordinates in Job Reports, 
                a new tab will open showing the exact location on Google Maps.
            </div>

            <div class="alert alert-warning">
                <strong>API Usage:</strong> Each map view counts as one API request. Monitor your usage in Google Cloud Console 
                to avoid exceeding quotas.
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Features Enabled:</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Location pin display</li>
                        <li>• Address reverse geocoding</li>
                        <li>• Map zoom and pan controls</li>
                        <li>• Street view integration</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">API Endpoints Used:</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Maps JavaScript API</li>
                        <li>• Geocoding API</li>
                        <li>• Places API (if enabled)</li>
                        <li>• Static Maps API</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let isTesting = false;

// Save settings
function saveSettings(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    fetch('/api/v1/system/google-maps/settings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            showAlert('Settings saved successfully!', 'success');
            // Reload page to update status
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('Error saving settings: ' + (result.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving settings', 'error');
    });
}

// Test API key
function testApiKey() {
    if (isTesting) return;
    
    const apiKey = document.querySelector('input[name="google_maps_api_key"]').value;
    if (!apiKey) {
        showAlert('Please enter an API key first', 'warning');
        return;
    }
    
    isTesting = true;
    const testBtn = document.querySelector('.btn-test');
    testBtn.disabled = true;
    testBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    
    fetch('/api/v1/system/google-maps/test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ api_key: apiKey })
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            showAlert('API key is valid and working!', 'success');
        } else {
            showAlert('API key test failed: ' + (result.message || 'Invalid key'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error testing API key', 'error');
    })
    .finally(() => {
        isTesting = false;
        testBtn.disabled = false;
        testBtn.innerHTML = '<i class="fas fa-vial"></i> Test API Key';
    });
}

// Reset form
function resetForm() {
    showConfirmDialog({
        title: 'Reset semua pengaturan?',
        text: 'Semua pengaturan akan dikembalikan ke nilai default.',
        confirmButtonText: 'Ya, reset'
    }).then((result) => {
        if (!result.isConfirmed) return;
        document.getElementById('googleMapsForm').reset();
    });
}

// Show alert
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.card');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
@endsection
