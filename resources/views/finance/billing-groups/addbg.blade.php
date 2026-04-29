@extends('layouts.app')

@section('title', 'Add Billing Group')
@section('breadcrumb', 'Home / Marketing / Contract / Add Billing Group')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Alert Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Header -->
            <div class="card mb-4 shadow-sm" style="background-color: #1e3a8a; color: white; border: none; border-radius: 10px;">
                <div class="card-header" style="background-color: #1e3a8a; border: none; padding: 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('marketing.contracts.show', $contract->id) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left me-1"></i> Back to Contract
                            </a>
                        </div>
                        <div class="text-center flex-grow-1">
                            <h3 class="card-title mb-0" style="color: white; font-size: 1.5rem; font-weight: bold;">
                                <i class="fas fa-plus-circle me-2"></i>Add Billing Group
                            </h3>
                            <p class="mb-0 mt-1" style="font-size: 0.9rem; opacity: 0.9;">
                                Contract: {{ $contract->contract_number }} | Customer: {{ $contract->customer->name }}
                            </p>
                        </div>
                        <div style="width: 120px;"></div>
                    </div>
                </div>
            </div>

            <form action="{{ route('finance.billing-groups.store-for-contract', $contract->id) }}" method="POST">
                @csrf
                <input type="hidden" name="contract_id" value="{{ $contract->id }}">

                <!-- PIC Finance Information Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color: #1e3a8a; color: white; padding: 1rem 1.5rem;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>PIC Finance Information</h5>
                            <!-- Removed auto-fill button, replaced with dropdown below -->
                        </div>
                    </div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <!-- PIC Selection Dropdown -->
                        <div class="mb-3">
                            <label for="pic_contact_select" class="form-label fw-bold text-primary">Select PIC from Customer Contacts</label>
                            <select class="form-select" id="pic_contact_select">
                                <option value="">-- Select Contact --</option>
                                @php
                                    $allContacts = collect();
                                    if($contract->customer->contacts) {
                                        $allContacts = $allContacts->merge($contract->customer->contacts);
                                    }
                                    if($contract->customer->customer_contacts) { // Handle legacy relation if exists
                                        $allContacts = $allContacts->merge($contract->customer->customerContacts ?? []);
                                    }
                                    // Unique by ID/Name to avoid dupes if logic overlaps
                                    $allContacts = $allContacts->unique('id');
                                @endphp
                                @foreach($allContacts as $contact)
                                    @php
                                        // Robust phone detection
                                        $phone = $contact->phone ?? $contact->phone_1 ?? $contact->office_phone ?? $contact->mobile ?? $contact->telp ?? '';
                                    @endphp
                                    <option value="{{ $contact->id }}"
                                            data-name="{{ $contact->name }}"
                                            data-email="{{ $contact->email }}"
                                            data-phone="{{ $phone }}"
                                            data-position="{{ $contact->position ?? '' }}">
                                        {{ $contact->name }} - {{ $contact->position ?? 'No Position' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="pic_name" class="form-label">PIC Finance</label>
                                    <input type="text" class="form-control @error('pic_name') is-invalid @enderror" 
                                           id="pic_name" name="pic_name" value="{{ old('pic_name') }}">
                                    @error('pic_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="pic_email" class="form-label">E-Mail</label>
                                    <input type="email" class="form-control @error('pic_email') is-invalid @enderror" 
                                           id="pic_email" name="pic_email" value="{{ old('pic_email') }}">
                                    @error('pic_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="pic_phone" class="form-label">Phone 1</label>
                                    <input type="text" class="form-control @error('pic_phone') is-invalid @enderror" 
                                           id="pic_phone" name="pic_phone" value="{{ old('pic_phone') }}">
                                    @error('pic_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Building Coverage Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color: #1e3a8a; color: white; padding: 1rem 1.5rem;">
                        <h5 class="mb-0"><i class="fas fa-building me-2"></i>Building Coverage</h5>
                    </div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Select the buildings covered by this billing group. Only buildings <strong>not yet assigned</strong> to other billing groups are shown.
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">Select</th>
                                        <th>Building Name</th>
                                        <th>Full Address</th>
                                        <th>City / Area</th>
                                        <th>Postal Code</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($buildings as $building)
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" name="building_ids[]" value="{{ $building->id }}" 
                                                       class="form-check-input building-checkbox"
                                                       {{ collect(old('building_ids'))->contains($building->id) ? 'checked' : '' }}>
                                            </td>
                                            <td><strong>{{ $building->name }}</strong></td>
                                            <td>{{ $building->address }}</td>
                                            <td>{{ $building->city->name ?? '-' }}</td>
                                            <td>{{ $building->postal_code ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="fas fa-info-circle me-1"></i> No unassigned buildings available for this customer.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @error('building_ids')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Tax Information Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color: #1e3a8a; color: white; padding: 1rem 1.5rem;">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Tax Information</h5>
                    </div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <div class="mb-3">
                            <label for="tax_configuration_select" class="form-label">Select Tax Configuration</label>
                            <!-- Replicating exact logic from editbg.blade.php -->
                            <select class="form-select @error('tax_configuration_select') is-invalid @enderror" 
                                    id="tax_configuration_select" 
                                    onchange="handleEditTaxSelection()">
                                <option value="">Select tax configuration...</option>
                                @php
                                    $customer = $contract->customer;
                                    
                                    // Get Induk (Master Customer) data
                                    $indukNpwp = trim($customer->npwp ?? '');
                                    $indukNitku = trim($customer->nitku ?? '');
                                    $indukNik = trim($customer->nik ?? '');
                                    $indukAddress = trim($customer->npwp_address ?? '');
                                    
                                    // Auto-generate NITKU if missing: NPWP + "000000"
                                    $autoNitkuFromInduk = $indukNpwp ? $indukNpwp . '000000' : '';
                                    
                                    // ===== Option 1: INDUK (Master Customer) =====
                                    if ($indukNpwp) {
                                        $nitku = $indukNitku ?: $autoNitkuFromInduk;
                                        $nitkuLabel = $indukNitku ? $nitku : "{$nitku} (auto)";
                                        $label = "NPWP: {$indukNpwp} - NITKU: {$nitkuLabel}" . ($indukAddress ? " - {$indukAddress}" : '');
                                        
                                        $valueData = json_encode([
                                            'source' => 'induk',
                                            'npwp' => $indukNpwp,
                                            'nitku' => $nitku,
                                            'nik' => $indukNik,
                                            'address' => $indukAddress
                                        ]);
                                        
                                        echo "<option value='" . htmlspecialchars($valueData, ENT_QUOTES) . "'>{$label}</option>";
                                    }
                                    
                                    // ===== Options 2+: CABANG (CustomerTax entries) =====
                                    $taxSettings = $customer->customerTaxSettings ?? $customer->customer_tax_settings ?? [];
                                    foreach ($taxSettings as $setting) {
                                        $settingType = strtoupper($setting->tax_type ?? '');
                                        $settingNumber = trim($setting->tax_number ?? '');
                                        $settingAddress = trim($setting->tax_address ?? '');
                                        
                                        $labelParts = [];
                                        
                                        if ($settingType === 'NITKU') {
                                            $npwp = $indukNpwp;
                                            $nitku = $settingNumber;
                                            $labelParts[] = "NPWP (induk): " . ($npwp ?: 'N/A');
                                            $labelParts[] = "NITKU (cabang): {$nitku}";
                                        } elseif ($settingType === 'NPWP') {
                                            $npwp = $settingNumber;
                                            if (strlen($settingNumber) <= 16) {
                                                $nitku = $settingNumber . '000000';
                                                $labelParts[] = "NPWP (cabang): {$npwp}";
                                                $labelParts[] = "NITKU (auto): {$nitku}";
                                            } else {
                                                $nitku = $settingNumber;
                                                $labelParts[] = "NPWP/NITKU: {$nitku}";
                                            }
                                        } else {
                                            // NIK, KITAS, etc.
                                            $npwp = $indukNpwp ?: $settingNumber;
                                            $nitku = (strlen($settingNumber) <= 16) ? $settingNumber . '000000' : $settingNumber;
                                            
                                            $typeName = $settingType === 'OTHER' ? 'KITAS/PASSPORT/KTP WNA' : $settingType;
                                            $labelParts[] = "{$typeName}: {$settingNumber}";
                                            if ($indukNpwp && $settingType !== 'NIK') {
                                                $labelParts[] = "NPWP (induk): {$indukNpwp}";
                                            }
                                        }
                                        
                                        $address = $settingAddress ?: $indukAddress;
                                        $label = implode(' - ', $labelParts) . ($address ? " - {$address}" : '');
                                        
                                        $valueData = json_encode([
                                            'source' => 'cabang',
                                            'setting_id' => $setting->id,
                                            'npwp' => $npwp,
                                            'nitku' => $nitku,
                                            'nik' => $indukNik,
                                            'address' => $address
                                        ]);
                                        
                                        echo "<option value='" . htmlspecialchars($valueData, ENT_QUOTES) . "'>{$label}</option>";
                                    }
                                @endphp
                            </select>
                        </div>

                        <!-- Hidden fields to store individual tax values (Matches editbg.blade.php) -->
                        <input type="hidden" name="npwp" id="tax_npwp">
                        <input type="hidden" name="nitku" id="tax_nitku">
                        <input type="hidden" name="nik" id="tax_nik">
                        <input type="hidden" name="tax_type" id="tax_type">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tax Number (NPWP + NITKU)</label>
                                    <input type="text" class="form-control" name="tax_number" id="tax_number" readonly placeholder="Auto-filled based on selection">
                                    <small class="text-muted">Auto-filled based on selection</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tax Address</label>
                                    <textarea class="form-control" name="npwp_address" id="npwp_address" rows="1" readonly placeholder="Auto-filled based on selection">{{ old('npwp_address') }}</textarea>
                                    <small class="text-muted">Auto-filled based on selection</small>
                                </div>
                            </div>
                        </div> 
                                    <textarea class="form-control" name="npwp_address" id="npwp_address" rows="1" readonly placeholder="Auto-filled based on selection">{{ old('npwp_address') }}</textarea>
                                    @error('npwp_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="tax_id" id="tax_id">
                    </div>
                </div>

                <!-- Payment Method Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color: #1e3a8a; color: white; padding: 1rem 1.5rem;">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Method</h5>
                    </div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <div class="mb-3">
                            <label for="bank_payment_select" class="form-label">Select Bank Payment</label>
                            <select class="form-select" id="bank_payment_select" onchange="populateBankPayment(this)">
                                <option value="">-- Choose Bank Payment --</option>
                                @foreach($bankPayments as $payment)
                                    <option value="{{ $payment->id }}" 
                                        data-bank-name="{{ $payment->bank->name ?? '' }}"
                                        data-account-name="{{ $payment->account_name }}"
                                        data-account-no="{{ $payment->account_number }}"
                                        data-is-va="{{ $payment->is_default_va ? '1' : '0' }}">
                                        {{ $payment->bank->name ?? 'Unknown Bank' }} - {{ $payment->account_name }} ({{ $payment->account_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_name" class="form-label">Account Name</label>
                                    <input type="text" class="form-control" id="account_name" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_no" class="form-label">Account Number</label>
                                    <input type="text" class="form-control" id="account_no" readonly>
                                    <input type="hidden" id="virtual_account_number" name="virtual_account_number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <input type="hidden" class="form-control" id="payment_method_display" readonly>
                                    <input type="hidden" id="payment_method_type" name="payment_method_type">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow-sm mb-5">
                    <div class="card-body" style="padding: 1.5rem;">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('marketing.contracts.show', $contract->id) }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Save Billing Group
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Tax Configuration Dropdown Logic (Exactly matching editbg.blade.php)
    window.handleEditTaxSelection = function() {
        const taxConfigSelect = document.getElementById('tax_configuration_select');
        const npwpInput = document.getElementById('tax_npwp');
        const nitkuInput = document.getElementById('tax_nitku');
        const nikInput = document.getElementById('tax_nik');
        const taxTypeInput = document.getElementById('tax_type');
        const taxNumberDisplay = document.getElementById('tax_number');
        const taxAddressDisplay = document.getElementById('npwp_address');

        if (taxConfigSelect && taxConfigSelect.selectedIndex > 0) {
            const selectedOption = taxConfigSelect.options[taxConfigSelect.selectedIndex];
            
            // Parse JSON value and set hidden fields
            try {
                const taxData = JSON.parse(selectedOption.value);
                
                // Set individual hidden fields
                if (npwpInput) npwpInput.value = taxData.npwp || '';
                if (nitkuInput) nitkuInput.value = taxData.nitku || '';
                if (nikInput) nikInput.value = taxData.nik || '';
                
                // Build combined tax_type and tax_number (display purpose)
                const taxTypes = [];
                const taxNumbers = [];
                
                if (taxData.npwp) {
                    taxTypes.push('NPWP');
                    taxNumbers.push(taxData.npwp);
                }
                if (taxData.nitku) {
                    taxTypes.push('NITKU');
                    taxNumbers.push(taxData.nitku);
                }
                if (taxData.nik) {
                    taxTypes.push('NIK');
                    taxNumbers.push(taxData.nik);
                }
                
                if (taxTypeInput) taxTypeInput.value = taxTypes.join(', ');
                if (taxNumberDisplay) taxNumberDisplay.value = taxNumbers.join(', ');
                if (taxAddressDisplay) taxAddressDisplay.value = taxData.address || '';
                
            } catch (e) {
                console.error('Failed to parse tax data', e);
            }
        } else {
            // Clear all fields
            if (npwpInput) npwpInput.value = '';
            if (nitkuInput) nitkuInput.value = '';
            if (nikInput) nikInput.value = '';
            if (ppnCodeInput) ppnCodeInput.value = '';
            if (ppnCodeDisplay) ppnCodeDisplay.value = '';
            if (taxTypeInput) taxTypeInput.value = '';
            if (taxNumberDisplay) taxNumberDisplay.value = '';
            if (taxAddressDisplay) taxAddressDisplay.value = '';
        }
    };

$(document).ready(function() {
    // PIC Dropdown Change Handler
    $('#pic_contact_select').change(function() {
        const option = $(this).find(':selected');
        if (option.val()) {
            $('#pic_name').val(option.data('name'));
            $('#pic_email').val(option.data('email'));
            $('#pic_phone').val(option.data('phone'));
        } else {
            // Optional: clear fields or leave as is?
            // Usually clearing is annoying if user accidentally deselects. 
            // I'll leave it, or clear if desired. Users prefer no data loss.
        }
    });

    // Validasi PIC Manual vs Dropdown
    // If they type manually, it's fine. Dropdown is just a helper.
});

function populateBankPayment(select) {
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const bankName = option.getAttribute('data-bank-name');
        const accountName = option.getAttribute('data-account-name');
        const accountNo = option.getAttribute('data-account-no');
        const isVa = option.getAttribute('data-is-va') === '1';
        
        document.getElementById('bank_name').value = bankName;
        document.getElementById('account_name').value = accountName;
        document.getElementById('account_no').value = accountNo;
        
        // Format for legacy support (Show view parses this: "Bank - Name (No)")
        const compositeValue = `${bankName} - ${accountName} (${accountNo})`;
        document.getElementById('virtual_account_number').value = compositeValue;

        let type = 'transfer';
        let display = 'Bank Transfer';
        if (isVa) {
            type = 'va_bca';
            if (bankName.toLowerCase().includes('mandiri')) type = 'va_mandiri';
            display = 'Virtual Account';
        }
        
        document.getElementById('payment_method_type').value = type;
        document.getElementById('payment_method_display').value = display;
    }
}

$(document).ready(function() {
    // Auto-fill from Customer PIC logic
    $('#btnAutoFillCustomer').click(function() {
        const customer = @json($contract->customer);
        if (customer) {
            const legacyContacts = customer.customer_contacts || [];
            const multiContacts = customer.contacts || [];
            const contacts = [...legacyContacts, ...multiContacts];
            
            const financeContact = contacts.find(c => {
                const type = (c.type || c.pivot?.type || '').toLowerCase();
                const position = (c.position || '').toLowerCase();
                const name = (c.name || '').toLowerCase();
                return type.includes('finance') || position.includes('finance') || name.includes('finance');
            }) || contacts[0];
            
            if (financeContact) {
                // Robust phone detection
                let phone = financeContact.phone || financeContact.phone_1 || financeContact.office_phone || financeContact.telp || financeContact.mobile || financeContact.pivot?.phone || '';
                document.getElementById('pic_name').value = financeContact.name || '';
                document.getElementById('pic_email').value = financeContact.email || '';
                document.getElementById('pic_phone').value = phone;
            } else {
                // Fallback to customer general phone
                let phone = customer.phone_1 || customer.phone || customer.office_phone || customer.telp || '';
                document.getElementById('pic_name').value = customer.name || '';
                document.getElementById('pic_email').value = customer.email || '';
                document.getElementById('pic_phone').value = phone;
            }
            alert('PIC fields updated from Customer data.');
        }
    });

    // Initialize default tax if available
    const customer = @json($contract->customer);
    if (customer && customer.default_bank_payment_id) {
        $('#bank_payment_select').val(customer.default_bank_payment_id).change();
    }
});
</script>
@endpush
