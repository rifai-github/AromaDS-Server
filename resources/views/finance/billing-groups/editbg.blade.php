@extends('layouts.app')

@section('title', 'Edit Billing Group')
@section('breadcrumb', 'Home / Marketing / Contract / Edit Billing Group')

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
                                <i class="fas fa-edit me-2"></i>Edit Billing Group
                            </h3>
                            <p class="mb-0 mt-1" style="font-size: 0.9rem; opacity: 0.9;">
                                {{ $billingGroup->billing_group_name }}
                            </p>
                        </div>
                        <div style="width: 120px;"></div> <!-- Spacer for centering -->
                    </div>
                </div>
            </div>

            <form action="{{ route('finance.billing-groups.update', $billingGroup->id) }}" method="POST">
                @csrf
                @method('PUT')
                @php
                    // Ensure contract is available
                    $contract = $billingGroup->contract;
                @endphp
                <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                
                @php
                    // Parse virtual_account_number untuk Account Name dan Account No
                    $accountName = '';
                    $accountNo = '';
                    if ($billingGroup->virtual_account_number) {
                        if (preg_match('/^(.+?)\s*-\s*(.+?)\s*\((.+?)\)$/', $billingGroup->virtual_account_number, $matches)) {
                            $accountName = $matches[2] ?? '';
                            $accountNo = $matches[3] ?? '';
                        } else {
                            $accountNo = $billingGroup->virtual_account_number;
                        }
                    }
                @endphp

                <!-- PIC Finance Information Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color: #1e3a8a; color: white; padding: 1rem 1.5rem;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>PIC Finance Information</h5>
                            <!-- Auto-fill button removed, replaced with dropdown below -->
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
                                    if($contract->customer->customerContacts) { // Handle legacy relation if exists
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
                                            data-position="{{ $contact->position ?? '' }}"
                                            {{ (old('pic_name', $billingGroup->pic_name) == $contact->name) ? 'selected' : '' }}>
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
                                           id="pic_name" name="pic_name" 
                                           value="{{ old('pic_name', $billingGroup->pic_name) }}">
                                    @error('pic_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="pic_email" class="form-label">E-Mail</label>
                                    <input type="email" class="form-control @error('pic_email') is-invalid @enderror" 
                                           id="pic_email" name="pic_email" 
                                           value="{{ old('pic_email', $billingGroup->pic_email) }}">
                                    @error('pic_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="pic_phone" class="form-label">Phone 1</label>
                                    <input type="text" class="form-control @error('pic_phone') is-invalid @enderror" 
                                           id="pic_phone" name="pic_phone" 
                                           value="{{ old('pic_phone', $billingGroup->pic_phone) }}">
                                    @error('pic_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Building Coverage & Address Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color: #1e3a8a; color: white; padding: 1rem 1.5rem;">
                        <h5 class="mb-0"><i class="fas fa-building me-2"></i>Building Coverage & Address</h5>
                    </div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Select the buildings covered by this billing group. The address details will be automatically pulled from the selected buildings.
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
                                    @php
                                        // Filter buildings that are assigned to OTHER billing groups in the same contract
                                        $availableBuildings = collect();
                                        
                                        // Combine buildings from rooms and surveys
                                        foreach($contract->contractRooms as $room) {
                                            if ($room->room && $room->room->building) {
                                                $availableBuildings->push($room->room->building);
                                            }
                                        }
                                        foreach($contract->contractSurveys as $survey) {
                                            if ($survey->survey && $survey->survey->building) {
                                                $availableBuildings->push($survey->survey->building);
                                            }
                                        }
                                        
                                        $availableBuildings = $availableBuildings->unique('id');
                                        
                                        // Get buildings already assigned to THIS group
                                        $currentAssignedIds = $billingGroup->buildings->pluck('id')->toArray();
                                    @endphp

                                    @forelse($availableBuildings as $building)
                                        @php
                                            $isAssignedToOther = in_array($building->id, $otherAssignedBuildingIds ?? []);
                                            $isAssignedToThis = in_array($building->id, $currentAssignedIds);
                                        @endphp
                                        <tr class="{{ $isAssignedToOther ? 'table-secondary opacity-50' : '' }}">
                                            <td class="text-center">
                                                <input type="checkbox" name="building_ids[]" value="{{ $building->id }}" 
                                                       class="form-check-input building-checkbox"
                                                       {{ $isAssignedToThis ? 'checked' : '' }}
                                                       {{ $isAssignedToOther ? 'disabled' : '' }}>
                                            </td>
                                            <td>
                                                <strong>{{ $building->name }}</strong>
                                                @if($isAssignedToOther)
                                                    <span class="badge bg-warning text-dark ms-2"><i class="fas fa-exclamation-triangle me-1"></i>Assigned to other group</span>
                                                @endif
                                            </td>
                                            <td>{{ $building->address }}</td>
                                            <td>{{ $building->city->name ?? '-' }}</td>
                                            <td>{{ $building->kode_pos ?? $building->postal_code }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No buildings found linked to this contract.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tax Information Section (Dropdown Selection) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color: #1e3a8a; color: white; padding: 1rem 1.5rem;">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Tax Information</h5>
                    </div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Select the applicable tax configuration for this billing group.
                        </div>

                        <div class="mb-3">
                            <label for="tax_configuration" class="form-label">Select Tax Configuration</label>
                            <select class="form-select @error('tax_configuration') is-invalid @enderror" id="tax_configuration" onchange="handleEditTaxSelection()">
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
                                    
                                    // Current stored values for matching
                                    $storedNpwp = old('npwp', $billingGroup->npwp);
                                    $storedNitku = old('nitku', $billingGroup->nitku);
                                    $storedNik = old('nik', $billingGroup->nik);
                                    
                                    // Try to parse stored tax_number for backward compatibility if new columns are empty
                                    if (!$storedNpwp && !$storedNitku && !$storedNik && $billingGroup->tax_number) {
                                        $parts = explode(', ', $billingGroup->tax_number);
                                        foreach ($parts as $part) {
                                            $part = trim($part);
                                            // NITKU is typically 22 digits
                                            if (strlen($part) === 22) { 
                                                $storedNitku = $part;
                                            } elseif (strlen($part) === 15 || strlen($part) === 16) {
                                                if (empty($storedNpwp)) {
                                                    $storedNpwp = $part;
                                                }
                                            }
                                        }
                                    }
                                    
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
                                        
                                        // Check if this option matches stored values OR if stored values are empty (Default to Induk)
                                        $hasStoredData = !empty($storedNpwp) || !empty($billingGroup->tax_number);
                                        $isMatch = ($storedNpwp === $indukNpwp && $storedNitku === $nitku);
                                        $isSelected = ($isMatch || !$hasStoredData) ? 'selected' : '';
                                        
                                        echo "<option value='" . htmlspecialchars($valueData, ENT_QUOTES) . "' {$isSelected} 
                                            data-source='induk'
                                            data-npwp='{$indukNpwp}' 
                                            data-nitku='{$nitku}' 
                                            data-nik='{$indukNik}' 
                                            data-address='{$indukAddress}'>{$label}</option>";
                                    }
                                    
                                    // ===== Options 2+: CABANG (CustomerTax entries) =====
                                    $taxSettings = $customer->customerTaxSettings ?? $customer->customer_tax_settings ?? [];
                                    foreach ($taxSettings as $setting) {
                                        $settingType = strtoupper($setting->tax_type ?? '');
                                        $settingNumber = trim($setting->tax_number ?? '');
                                        $settingAddress = trim($setting->tax_address ?? '');
                                        
                                        $labelParts = [];
                                        
                                        if ($settingType === 'NITKU' || $settingType === '01') {
                                            // Cabang NITKU: Bisa full 22 digit atau 6 digit
                                            // Fallback: Jika NPWP Induk kosong, ambil dari tax_number setting ini
                                            $npwp = $indukNpwp ?: (strlen($settingNumber) <= 16 ? $settingNumber : substr($settingNumber, 0, 16));
                                            $rawNitku = trim($setting->nitku ?? '');
                                            
                                            // Jika kolom nitku kosong, cek apakah settingNumber adalah NITKU lengkap (22 digit)
                                            if (empty($rawNitku) && strlen($settingNumber) == 22) {
                                                $rawNitku = substr($settingNumber, -6);
                                                $npwp = substr($settingNumber, 0, 16);
                                            } elseif (empty($rawNitku)) {
                                                $rawNitku = '000000';
                                            }

                                            // Jika NITKU hanya 6 digit, gabungkan dengan NPWP (Induk/Setting)
                                            if (strlen($rawNitku) <= 6 && $npwp) {
                                                $nitku = $npwp . str_pad($rawNitku, 6, '0', STR_PAD_LEFT);
                                            } else {
                                                $nitku = $rawNitku;
                                            }
                                            $labelParts[] = "NPWP: " . ($npwp ?: 'N/A');
                                            $labelParts[] = "NITKU: {$nitku}";
                                        } elseif ($settingType === 'NPWP') {
                                            // Cabang NPWP: use Cabang NPWP + auto-generate NITKU if only 15/16 digits
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
                                            'nik' => $indukNik, // NIK always from Induk
                                            'address' => $address
                                        ]);
                                        
                                        // Check if this option matches stored values
                                        $isSelected = ($storedNpwp === $npwp && $storedNitku === $nitku) ? 'selected' : '';
                                        
                                        echo "<option value='" . htmlspecialchars($valueData, ENT_QUOTES) . "' {$isSelected}
                                            data-source='cabang'
                                            data-setting-id='{$setting->id}'
                                            data-npwp='{$npwp}' 
                                            data-nitku='{$nitku}' 
                                            data-nik='{$indukNik}' 
                                            data-address='{$address}'>{$label}</option>";
                                    }
                                @endphp
                            </select>
                        </div>

                        <!-- Hidden fields to store individual tax values -->
                        <input type="hidden" name="npwp" id="tax_npwp" value="{{ $storedNpwp }}">
                        <input type="hidden" name="nitku" id="tax_nitku" value="{{ $storedNitku }}">
                        <input type="hidden" name="nik" id="tax_nik" value="{{ $storedNik ?? $indukNik }}">
                        <input type="hidden" name="tax_type" id="tax_type" value="{{ old('tax_type', $billingGroup->tax_type) }}">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tax Number (NPWP + NITKU)</label>
                                    <input type="text" class="form-control" name="tax_number" id="tax_number" value="{{ old('tax_number', $billingGroup->tax_number) }}" readonly>
                                    <small class="text-muted">Auto-filled based on selection</small>
                                </div>
                            </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Tax Address</label>
                                    <textarea class="form-control" name="npwp_address" id="npwp_address" rows="1" readonly>{{ old('npwp_address', $billingGroup->npwp_address) }}</textarea>
                                    <small class="text-muted">Auto-filled based on selection</small>
                                </div>
                            </div>
                        </div> 
                    </div>
                </div>

                <!-- Invoice Type Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color: #1e3a8a; color: white; padding: 1rem 1.5rem;">
                        <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Invoice Type</h5>
                    </div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <div class="mb-3">
                            <label for="invoice_type" class="form-label">Invoice Type</label>
                            <select class="form-select @error('invoice_type') is-invalid @enderror" 
                                    id="invoice_type" name="invoice_type">
                                <option value="">Select Invoice Type</option>
                                <option value="hard_copy" {{ old('invoice_type', $billingGroup->invoice_type) == 'hard_copy' ? 'selected' : '' }}>Hard Copy</option>
                                <option value="soft_copy" {{ old('invoice_type', $billingGroup->invoice_type) == 'soft_copy' ? 'selected' : '' }}>Soft Copy</option>
                                <option value="both" {{ old('invoice_type', $billingGroup->invoice_type) == 'both' ? 'selected' : '' }}>Both</option>
                                <option value="manual" {{ old('invoice_type', $billingGroup->invoice_type) == 'manual' ? 'selected' : '' }}>Manual</option>
                            </select>
                            @error('invoice_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Payment Method Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header" style="background-color: #1e3a8a; color: white; padding: 1rem 1.5rem;">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Method</h5>
                    </div>
                    <div class="card-body" style="padding: 1.5rem;">
                        <!-- Dynamic Dropdown at Top -->
                        <div class="mb-3">
                            <label for="bank_payment_select" class="form-label fw-bold text-primary">Select Bank Payment</label>
                            <select class="form-select" id="bank_payment_select" onchange="populateBankPayment(this)">
                                <option value="">-- Choose Bank Payment --</option>
                                @foreach($bankPayments as $payment)
                                    <option value="{{ $payment->id }}" 
                                        data-bank-name="{{ $payment->bank->name ?? '' }}"
                                        data-account-name="{{ $payment->account_name }}"
                                        data-account-no="{{ $payment->account_number }}"
                                        data-is-va="{{ $payment->is_default_va ? '1' : '0' }}"
                                        {{-- Try to match existing data if possible, though strict matching might be hard if data was manual --}}
                                        {{ ($billingGroup->bank_name == ($payment->bank->name ?? '') && $billingGroup->virtual_account_number == $payment->account_number) ? 'selected' : '' }}
                                    >
                                        {{ $payment->bank->name ?? 'Unknown' }} - {{ $payment->account_name }} ({{ $payment->account_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control @error('bank_name') is-invalid @enderror" 
                                           id="bank_name" name="bank_name" 
                                           value="{{ old('bank_name', $billingGroup->bank_name) }}" readonly>
                                    @error('bank_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_name" class="form-label">Account Name</label>
                                    @php
                                        // Try to parse Account Name from VA string if needed, or use a field if exists
                                        // The controller doesn't seem to save account_name in billing_groups table (only pic_name, bank_name, etc)?
                                        // Wait, the store method has 'account_name' => 'nullable' validation but does it save it?
                                        // Checking Migration/Model might be needed, but assuming for now we just show what we have.
                                        // Reviewing earlier `editbg` content (step 1289), there isn't an `account_name` column in `BillingGroup::create`.
                                        // It saves `pic_name`, `bank_name`, `virtual_account_number`.
                                        // So where is Account Name stored? Maybe it's NOT stored, just displayed?
                                        // User request: "Auto fill di fieldnya".
                                        // If backend doesn't save it, we can just show it in a readonly input for display.
                                    @endphp
                                    <input type="text" class="form-control" 
                                           id="account_name" name="account_name" 
                                           value="{{ old('account_name', $accountName ?? '') }}" readonly>
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Account holder name</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="account_no" class="form-label">Account Number</label>
                                    <input type="text" class="form-control" 
                                           id="account_no" name="account_no" 
                                           value="{{ old('account_no', $accountNo ?? $billingGroup->virtual_account_number) }}" readonly>
                                    <!-- Use hidden field for the actual value sent to DB as 'virtual_account_number' -->
                                    <input type="hidden" id="virtual_account_number" name="virtual_account_number" value="{{ $billingGroup->virtual_account_number }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <input type="hidden" class="form-control" id="payment_method_display" readonly 
                                           value="{{ $billingGroup->payment_method == 'va_bca' ? 'Virtual Account BCA' : ($billingGroup->payment_method == 'va_mandiri' ? 'Virtual Account Mandiri' : ($billingGroup->payment_method == 'transfer' ? 'Bank Transfer' : ($billingGroup->payment_method == 'cash' ? 'Cash' : $billingGroup->payment_method))) }}">
                                    <!-- Actual field for DB -->
                                    <input type="hidden" id="payment_method" name="payment_method" value="{{ $billingGroup->payment_method }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow-sm">
                    <div class="card-body" style="padding: 1.5rem;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Update Billing Group
                                </button>
                                <a href="{{ route('marketing.contracts.show', $contract->id) }}" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                            <div class="text-muted">
                                <small>
                                    <i class="fas fa-asterisk text-danger me-1" style="font-size: 0.5rem;"></i>Required fields
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-clock me-1"></i>Last updated: {{ $billingGroup->updated_at ? $billingGroup->updated_at->format('d M Y H:i') : '-' }}
                                </small>
                            </div>
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
    // Global function for Bank Payment Dropdown
    window.populateBankPayment = function(select) {
        const option = select.options[select.selectedIndex];
        if (option.value) {
            const bankName = option.getAttribute('data-bank-name');
            const accountName = option.getAttribute('data-account-name');
            const accountNo = option.getAttribute('data-account-no');
            const isVa = option.getAttribute('data-is-va') === '1';
            
            // Set fields
            document.getElementById('bank_name').value = bankName;
            document.getElementById('account_name').value = accountName;
            document.getElementById('account_no').value = accountNo;
            
            // Format for legacy support (Show view parses this: "Bank - Name (No)")
            // If we don't do this, account name won't show up in the view
            const compositeValue = `${bankName} - ${accountName} (${accountNo})`;
            document.getElementById('virtual_account_number').value = compositeValue;

            // Determine Payment Method Type (for backend)
            // Logic: if IS_VA -> check bank name for mandiri vs bca vs others
            // default to 'transfer' if not VA
            
            let type = 'transfer';
            let display = 'Bank Transfer';
            
            if (isVa) {
                // heuristic for specific VA types requested by backend enum
                // 'va_bca', 'va_mandiri'
                // If bank name contains BCA -> va_bca
                // If bank name contains Mandiri -> va_mandiri
                
                const lowerBank = bankName.toLowerCase();
                if (lowerBank.includes('bca')) {
                    type = 'va_bca';
                    display = 'Virtual Account BCA';
                } else if (lowerBank.includes('mandiri')) {
                    type = 'va_mandiri';
                    display = 'Virtual Account Mandiri';
                } else {
                    type = 'va_bca'; // Default fallback or generic VA?
                    display = 'Virtual Account';
                }
            } else if (bankName.toLowerCase().includes('cash')) {
                 type = 'cash';
                 display = 'Cash';
            }
            
            document.getElementById('payment_method').value = type;
            document.getElementById('payment_method_display').value = display;
        } else {
            // Clear fields if deselect
            document.getElementById('bank_name').value = '';
            document.getElementById('account_name').value = '';
            document.getElementById('account_no').value = '';
            document.getElementById('virtual_account_number').value = '';
            document.getElementById('payment_method').value = '';
            document.getElementById('payment_method_display').value = '';
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const accountNameInput = document.getElementById('account_name');
    const accountNoInput = document.getElementById('account_no');
    const bankNameInput = document.getElementById('bank_name');
    const virtualAccountNumberInput = document.getElementById('virtual_account_number');
    
    // Old duplicated code removed

    // PIC Dropdown Change Handler
    const picSelect = document.getElementById('pic_contact_select');
    if (picSelect) {
        picSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (option.value) {
                // Get data attributes
                const name = option.getAttribute('data-name') || '';
                const email = option.getAttribute('data-email') || '';
                const phone = option.getAttribute('data-phone') || '';
                
                // Update fields
                document.getElementById('pic_name').value = name;
                document.getElementById('pic_email').value = email;
                document.getElementById('pic_phone').value = phone;
            }
        });
    }
    
    // Tax Configuration Dropdown Logic - NEW: NPWP+NITKU paired options with JSON values
    // Declare globally so it can be called from inline onchange
    window.handleEditTaxSelection = function() {
        const taxConfigSelect = document.getElementById('tax_configuration');
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
                
                // Build combined tax_type and tax_number
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
                
                // If NITKU exists and ends with 000001, ensure it's prioritized in display
                if (taxTypeInput) taxTypeInput.value = taxTypes.join(', ');
                if (taxNumberDisplay) taxNumberDisplay.value = taxNumbers.join(', ');
                if (taxAddressDisplay) taxAddressDisplay.value = taxData.address || '';
                
                console.log('Edit Tax selection updated:', taxData);
            } catch (e) {
                // Fallback to data attributes if JSON parsing fails
                const optNpwp = selectedOption.getAttribute('data-npwp') || '';
                const optNitku = selectedOption.getAttribute('data-nitku') || '';
                const optNik = selectedOption.getAttribute('data-nik') || '';
                
                if (npwpInput) npwpInput.value = optNpwp;
                if (nitkuInput) nitkuInput.value = optNitku;
                if (nikInput) nikInput.value = optNik;
                
                const fTypes = [];
                const fNumbers = [];
                if (optNpwp) { fTypes.push('NPWP'); fNumbers.push(optNpwp); }
                if (optNitku) { fTypes.push('NITKU'); fNumbers.push(optNitku); }
                if (optNik) { fTypes.push('NIK'); fNumbers.push(optNik); }
                
                if (taxTypeInput) taxTypeInput.value = fTypes.join(', ');
                if (taxNumberDisplay) taxNumberDisplay.value = fNumbers.join(', ');
                if (taxAddressDisplay) taxAddressDisplay.value = selectedOption.getAttribute('data-address') || '';
                
                console.log('Edit Tax selection (from attributes):', selectedOption.value);
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
    }
    
    // Initialize tax selection immediately on page load
    // Only run if we actually have an option selected (index > 0) to avoid clearing legacy data
    if (window.handleEditTaxSelection) {
        const taxConfigSelect = document.getElementById('tax_configuration');
        if (taxConfigSelect && taxConfigSelect.selectedIndex > 0) {
            window.handleEditTaxSelection();
        }
    }

    // Auto-fill phone/email if empty but contact is selected
    const picSelectRef = document.getElementById('pic_contact_select');
    const phoneInputRef = document.getElementById('pic_phone');
    const emailInputRef = document.getElementById('pic_email');
    
    if (picSelectRef && picSelectRef.value) {
        const option = picSelectRef.options[picSelectRef.selectedIndex];
        
        if (phoneInputRef && !phoneInputRef.value) {
            const phone = option.getAttribute('data-phone');
            if (phone) phoneInputRef.value = phone;
        }
        
        if (emailInputRef && !emailInputRef.value) {
            const email = option.getAttribute('data-email');
            if (email) emailInputRef.value = email;
        }
    }

    form.addEventListener('submit', function(e) {
        // Combine account name and account no into virtual_account_number format
        const accountName = accountNameInput.value.trim();
        const accountNo = accountNoInput.value.trim();
        const bankName = bankNameInput.value.trim();
        
        if (accountName && accountNo) {
            // Format: "Bank Name - Account Name (Account Number)"
            if (bankName) {
                virtualAccountNumberInput.value = bankName + ' - ' + accountName + ' (' + accountNo + ')';
            } else {
                virtualAccountNumberInput.value = accountName + ' (' + accountNo + ')';
            }
        } else if (accountNo) {
            // If only account number, use it directly
            virtualAccountNumberInput.value = accountNo;
        }
    });
});
</script>
@endpush
