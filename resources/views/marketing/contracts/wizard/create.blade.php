@extends('layouts.app')

@section('title', 'Create Contract Wizard')
@section('breadcrumb')
<a href="{{ route('marketing.contracts.index') }}" style="color: #214589; text-decoration: none;">
    <i class="fas fa-arrow-left" style="margin-right: 5px;"></i>Back to Contract
</a>
/ Home / Marketing / Contract / Create
@endsection
@section('content')
<style>
    .wizard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .wizard-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .wizard-steps {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .step {
        flex: 1;
        text-align: center;
        padding: 15px;
        position: relative;
    }
    
    .step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 50%;
        right: -50%;
        width: 100%;
        height: 2px;
        background: #e5e7eb;
        z-index: 1;
    }
    
    .step.active::after {
        background: #3b82f6;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-weight: bold;
        transition: all 0.3s ease;
    }
    
    .step.active .step-number {
        background: #3b82f6;
        color: white;
    }
    
    .step.completed .step-number {
        background: #10b981;
        color: white;
    }
    
    .step-title {
        font-weight: 600;
        color: #374151;
        margin-bottom: 5px;
    }
    
    .step.active .step-title {
        color: #3b82f6;
    }
    
    .step-description {
        font-size: 12px;
        color: #6b7280;
    }
    
    .wizard-content {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        min-height: 500px;
    }
    
    .wizard-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn-wizard {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    
    .btn-prev {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .btn-prev:hover {
        background: #e5e7eb;
    }
    
    .btn-next {
        background: #3b82f6;
        color: white;
    }
    
    .btn-next:hover {
        background: #2563eb;
    }
    
    .btn-save {
        background: #10b981;
        color: white;
    }
    
    .btn-save:hover {
        background: #059669;
    }
    
    .form-section {
        margin-bottom: 25px;
    }
    
    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 6px;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .quotation-info {
        background: #f0f9ff;
        border: 1px solid #0ea5e9;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .quotation-info h4 {
        color: #0c4a6e;
        margin-bottom: 15px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .info-item {
        background: white;
        padding: 12px;
        border-radius: 6px;
        border: 1px solid #e0f2fe;
    }
    
    .info-label {
        font-size: 12px;
        color: #0369a1;
        font-weight: 500;
        margin-bottom: 4px;
    }
    
    .info-value {
        font-size: 14px;
        color: #0c4a6e;
        font-weight: 600;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    
    .table th,
    .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .table th {
        background: #f8fafc;
        font-weight: 600;
        color: #374151;
    }
    
    .billing-address {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .billing-address h5 {
        color: #1f2937;
        margin-bottom: 15px;
    }
    
    .btn-add {
        background: #10b981;
        color: white;
        padding: 8px 16px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }
    
    .btn-add:hover {
        background: #059669;
    }
    
    .btn-remove {
        background: #ef4444;
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        font-size: 12px;
    }
    
    .btn-remove:hover {
        background: #dc2626;
    }
    /* Client Contact Modal Styling */
    #clientContactModal {
        display: none !important;
        align-items: center;
        justify-content: center;
        overflow-y: auto;
    }
    
    #clientContactModal.show {
        display: flex !important;
    }
    
    #clientContactModal .modal-content {
        margin: 20px auto !important;
        max-height: 90vh !important;
        overflow-y: auto !important;
        display: flex !important;
        flex-direction: column !important;
    }
    
    #clientContactModal .modal-body {
        flex: 1;
        overflow-y: auto;
        max-height: calc(90vh - 150px);
    }
    
    #clientContactModal .modal-footer {
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid #dee2e6;
        position: sticky;
        bottom: 0;
        background: #fefefe;
    }
    
    /* Select2 in Client Contact Modal */
    #clientContactModal .select2-container {
        width: 100% !important;
        z-index: 10060 !important;
    }
    
    #clientContactModal .select2-dropdown {
        z-index: 10061 !important;
        pointer-events: auto !important;
    }
    
    #clientContactModal .select2-selection {
        pointer-events: auto !important;
        cursor: pointer !important;
    }
    
    #clientContactModal .select2-results__option {
        pointer-events: auto !important;
        cursor: pointer !important;
    }
    
    #clientContactModal .select2-selection__arrow {
        pointer-events: auto !important;
    }
    </style>

<div class="wizard-container">
    <!-- Wizard Header -->
    <div class="wizard-header">
        <h1 class="text-3xl font-bold mb-2">
            <i class="fas fa-file-contract mr-3"></i>
            Contract Creation Wizard
        </h1>
        <p class="text-lg opacity-90">Create a new contract step by step</p>
    </div>

    <!-- Wizard Steps -->
    <div class="wizard-steps">
        <div class="step active" data-step="1">
            <div class="step-number">1</div>
            <div class="step-title">Data Quotation</div>
            <div class="step-description">Select quotation</div>
        </div>
        <div class="step" data-step="2">
            <div class="step-number">2</div>
            <div class="step-title">Quotation Details</div>
            <div class="step-description">Review quotation info</div>
        </div>
        <div class="step" data-step="3">
            <div class="step-number">3</div>
            <div class="step-title">Data Contract</div>
            <div class="step-description">Contract information</div>
        </div>
        <div class="step" data-step="4">
            <div class="step-number">4</div>
            <div class="step-title">Billing & Payment</div>
            <div class="step-description">Billing addresses</div>
        </div>
        <div class="step" data-step="5">
            <div class="step-number">5</div>
            <div class="step-title">Summary Building</div>
            <div class="step-description">Review buildings</div>
        </div>
    </div>

    <!-- Wizard Content -->
    <div class="wizard-content">
        <form id="contractWizardForm">
            @csrf
            
            <!-- Step 1: Data Quotation -->
            <div class="wizard-step" id="step1" style="display: block;">
                <div class="section-title">Step 1: Data Quotation</div>
                <div class="form-group">
                    <label class="form-label">Select Quotation <span class="text-red-500">*</span></label>
                    <select name="quotation_id" id="quotationSelect" class="form-control" required>
                        <option value="">Choose a quotation...</option>
                        @foreach($quotations ?? [] as $quotation)
                            <option value="{{ $quotation->id }}" 
                                    data-quotation-number="{{ $quotation->quotation_number }}"
                                    data-customer-name="{{ $quotation->customer->name ?? 'N/A' }}"
                                    data-marketing="{{ $quotation->marketing->name ?? 'N/A' }}"
                                    data-status="{{ $quotation->status }}">
                                {{ $quotation->quotation_number }} - {{ $quotation->customer->name ?? 'N/A' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Step 2: Quotation Details -->
            <div class="wizard-step" id="step2" style="display: none;">
                <div class="section-title">Step 2: Quotation Details</div>
                <div id="quotationDetails">
                    <div class="quotation-info">
                        <h4><i class="fas fa-info-circle mr-2"></i>Quotation Information</h4>
                        <div class="info-grid" id="quotationInfoGrid">
                            <!-- Quotation details will be loaded here -->
                        </div>
                    </div>
                    
                    <div class="quotation-info">
                        <h4><i class="fas fa-building mr-2"></i>Customer Information</h4>
                        <div class="info-grid" id="customerInfoGrid">
                            <!-- Customer details will be loaded here -->
                        </div>
                    </div>
                    
                    <div class="quotation-info">
                        <h4><i class="fas fa-list mr-2"></i>List Penawaran</h4>
                        <div class="table-responsive">
                            <table class="table" id="quotationItemsTable">
                                <thead>
                                    <tr>
                                        <th>Deskripsi Item</th>
                                        <th>Qty</th>
                                        <th>Harga Rental / Bulan</th>
                                    </tr>
                                </thead>
                                <tbody id="quotationItemsBody">
                                    <!-- Quotation items will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Quotation Summary -->
                        <div class="quotation-summary mt-4" id="quotationSummary" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 offset-md-6">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td><strong>Sub Total:</strong></td>
                                                <td class="text-right" id="subTotal">-</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Discount:</strong></td>
                                                <td class="text-right" id="discountAmount">-</td>
                                            </tr>
                                            <tr class="table-active">
                                                <td><strong>Grand Total:</strong></td>
                                                <td class="text-right" id="grandTotal">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- [NEW] Merge Contracts Option -->
                    <div class="quotation-info mt-4" id="mergeContractsSection" style="display: none; border: 2px dashed #3b82f6; background: #f0f7ff; padding: 20px; border-radius: 8px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="m-0 text-blue-800" style="color: #1e40af;"><i class="fas fa-object-group mr-2"></i>Merge with Existing Contracts?</h4>
                            <span class="badge badge-info" style="background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;">OPTIONAL</span>
                        </div>
                        <p class="text-sm mb-3" style="font-size: 0.875rem; color: #1d4ed8;">
                            Pilih satu atau lebih kontrak aktif di bawah ini jika anda ingin menggabungkannya ke dalam kontrak baru ini. 
                            <br><small class="font-weight-bold tracking-tight" style="color: #dc2626;">* Kontrak yang dipilih akan otomatis di-terminate (Renew) setelah kontrak baru difinalisasi.</small>
                        </p>
                        
                        <div id="mergeCandidatesLoading" class="text-center py-4" style="display: none;">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Mencari kontrak aktif...
                        </div>
                        
                        <div id="mergeCandidatesList" class="row">
                            <!-- Kandidat merge akan dirender di sini via JS -->
                        </div>
                        
                        <div id="noMergeCandidates" class="text-center py-3 text-muted" style="display: none; font-size: 0.875rem;">
                            Tidak ada kontrak aktif lain yang bisa di-merge untuk customer ini.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Data Contract -->
            <div class="wizard-step" id="step3" style="display: none;">
                <div class="section-title">Step 3: Data Contract</div>
                
                <div class="form-section">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Nomor Induk Berusaha (NIB)</label>
                            <input type="text" name="nib" id="nib" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama Perusahaan</label>
                            <input type="text" name="company_name" id="companyName" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Kode PPN <span class="text-red-500">*</span></label>
                        <select name="ppn_code" id="ppnCode" class="form-control" required>
                            <option value="">Pilih Kode Transaksi PPN...</option>
                            <option value="01">01 - Penyerahan BKP/JKP yang PPN dipungut oleh PKP penyerah</option>
                            <option value="02">02 - Penyerahan kepada pemungut PPN instansi pemerintah</option>
                            <option value="03">03 - Penyerahan kepada pemungut PPN lainnya</option>
                            <option value="04">04 - Penyerahan dengan dasar pengenaan nilai lain</option>
                            <option value="05">05 - Penyerahan dengan PPN dipungut besaran tertentu</option>
                            <option value="06">06 - Penyerahan lainnya yang PPN dipungut PKP penyerah</option>
                            <option value="07">07 - Penyerahan yang mendapat fasilitas tidak dipungut</option>
                            <option value="08">08 - Penyerahan yang mendapat fasilitas dibebaskan</option>
                            <option value="09">09 - Penyerahan aktiva yang tidak untuk diperjualbelikan</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Contract Date <span class="text-red-500">*</span></label>
                            <input type="date" name="contract_date" id="contractDate" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Periode Sewa</label>
                            <input type="text" name="rental_period" id="rentalPeriod" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Satuan Sewa</label>
                            <input type="text" name="rental_unit" id="rentalUnit" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Terms of Payment (TOP)</label>
                            <input type="text" name="term_of_payment" id="termOfPayment" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Metode Pembayaran</label>
                            <input type="text" name="payment_method" id="paymentMethod" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Install Date <span class="text-red-500">*</span></label>
                            <input type="date" name="install_date" id="installDate" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">First Service <span class="text-red-500">*</span></label>
                            <input type="date" name="first_service" id="firstService" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">PIC Service (Email)</label>
                        <input type="email" name="pic_service_email" id="picServiceEmail" class="form-control" placeholder="Enter PIC Service email">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Remark External / To Company</label>
                            <textarea name="remark_external" id="remarkExternal" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Remark Internal</label>
                            <textarea name="remark_internal" id="remarkInternal" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">ADS Contract Signing <span class="text-red-500">*</span></label>
                        <select name="ads_signing" id="adsSigning" class="form-control" required>
                            <option value="">Pilih atau ketik disini...</option>
                            @foreach($users ?? [] as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->position_name ?? 'Staff' }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Company Contact Signing 1 <span class="text-red-500">*</span></label>
                            <div class="flex">
                                <select name="company_signing_1" id="companySigning1" class="form-control" required>
                                    <option value="">Pilih atau ketik disini...</option>
                                </select>
                                <button type="button" class="btn btn-add ml-2" onclick="openClientContactModal()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company Contact Signing 2</label>
                            <select name="company_signing_2" id="companySigning2" class="form-control">
                                <option value="">Pilih atau ketik disini...</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Company Contact Signing 3</label>
                            <select name="company_signing_3" id="companySigning3" class="form-control">
                                <option value="">Pilih atau ketik disini...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company Contact Signing 4</label>
                            <select name="company_signing_4" id="companySigning4" class="form-control">
                                <option value="">Pilih atau ketik disini...</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Billing Address & Payment -->
            <div class="wizard-step" id="step4" style="display: none;">
                <div class="section-title">Step 4: Billing Address & Payment</div>
                
                <div class="mb-4">
                    <p class="text-gray-600 mb-4">
                        Pada halaman ini kita menentukan berapa jumlah alamat penagihan yang diinginkan.
                    </p>
                    <p class="text-gray-600 mb-4">
                        Contoh: Quotation memuat 3 Survey dengan 3 lokasi yang berbeda (Gedung A, Gedung B dan Gedung C), 
                        maka kita dapat melakukan penggabungan tagihan:
                    </p>
                    <ul class="list-disc list-inside text-gray-600 mb-4">
                        <li>Billing Address 1: untuk Gedung A & Gedung C</li>
                        <li>Billing Address 2: untuk Gedung B</li>
                    </ul>
                </div>
                
                <div id="billingAddresses">
                    <!-- Billing addresses will be added here -->
                </div>
                
                <button type="button" class="btn btn-add" id="addBillingAddressBtn" onclick="addBillingAddress()" style="display: none;">
                    <i class="fas fa-plus mr-2"></i>Add Billing Address
                </button>
                <small class="text-gray-500 text-xs mt-2 block" id="billingAddressInfo" style="display: none;">
                    <i class="fas fa-info-circle mr-1"></i>Jika hanya ada 1 building, cukup 1 billing address. Jika ada lebih dari 1 building, Anda bisa menambah billing address untuk menggabungkan penagihan.
                </small>
            </div>

            <!-- Step 5: Summary Building -->
            <div class="wizard-step" id="step5" style="display: none;">
                <div class="section-title">Step 5: Summary Building</div>
                
                <div class="mb-4">
                    <p class="text-gray-600 mb-4">
                        Periksa kembali Gedung yang anda pilih untuk penagihan.
                    </p>
                    <p class="text-gray-600 mb-4">
                        Jika tidak sesuai, maka silahkan kembali ke Step 4.
                    </p>
                </div>
                
                <div id="buildingSelection">
                    <!-- Building selection will be shown here -->
                </div>

                <!-- [NEW] Merge Summary Review -->
                <div id="mergeSummaryReview" class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg" style="display: none;">
                    <h5 class="text-orange-800 font-bold mb-3"><i class="fas fa-exclamation-triangle mr-2"></i>Contract Merging Review</h5>
                    <p class="text-sm text-orange-700 mb-2">Kontrak berikut akan <strong>di-terminate (Renew)</strong> dan seluruh room/rentalnya akan dipindahkan ke kontrak baru ini:</p>
                    <ul id="selectedMergeContractsList" class="list-disc list-inside text-sm font-semibold text-orange-900">
                        <!-- List kontrak pilihan akan muncul di sini -->
                    </ul>
                </div>
            </div>
        </form>
    </div>

    <!-- Wizard Navigation -->
    <div class="wizard-navigation">
        <button type="button" class="btn-wizard btn-prev" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
            <i class="fas fa-arrow-left mr-2"></i>Previous
        </button>
        
        <div class="flex gap-2">
            <button type="button" class="btn-wizard btn-save" id="saveDraftBtn" onclick="saveDraft()" style="display: none;">
                <i class="fas fa-save mr-2"></i>Save Draft
            </button>
            <button type="button" class="btn-wizard bg-green-600 hover:bg-green-700 text-white" id="finalizeBtn" onclick="finalizeContract()" style="display: none;">
                <i class="fas fa-check-circle mr-2"></i>FINALIZE
            </button>
            <button type="button" class="btn-wizard btn-next" id="nextBtn" onclick="changeStep(1)">
                Next<i class="fas fa-arrow-right ml-2"></i>
            </button>
        </div>
    </div>
</div>

<!-- Add Tax Modal -->
<div id="addTaxModal" class="modal" style="display: none; position: fixed; z-index: 1040; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto;">
    <div class="modal-content" style="background-color: #fefefe; margin: 20px auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 700px; border-radius: 8px; position: relative; z-index: 1041; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #dee2e6;">
            <h3 style="margin: 0;">Add Tax Information</h3>
            <button type="button" onclick="closeAddTaxModal()" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #aaa; padding: 0; width: 30px; height: 30px; line-height: 1;">&times;</button>
        </div>
        <div class="modal-body" style="flex: 1; overflow-y: auto; overflow-x: hidden; max-height: calc(90vh - 200px); padding: 0 18px 10px 0;">
            <form id="addTaxForm">
                <input type="hidden" name="customer_id" id="taxModalCustomerId">
                <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Tax Name *</label>
                        <select id="modal_tax_name" name="tax_name" class="form-control" required onchange="updateTaxNumberMaxLength('modal')">
                            <option value="">Pilih Tax Name</option>
                            <option value="NPWP">NPWP</option>
                            <option value="NIK">NIK</option>
                            <option value="KITAS/PASSPORT/KTP WNA">KITAS/PASSPORT/KTP WNA</option>
                            <option value="NITKU">NITKU</option>
                        </select>
                        <small class="text-gray-600 mt-1 block">NPWP/NIK: 16 digit, NITKU: 22 digit, Other: 25 digit</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tax Number *</label>
                        <input type="text" id="modal_tax_number" name="tax_number" class="form-control" placeholder="Enter tax number" required oninput="updateTaxNumberCounter('modal'); this.value = this.value.replace(/[^0-9]/g, '');" maxlength="25">
                        <small class="text-gray-600 mt-1 block">
                            Length: <span id="modal_tax_number_counter" class="font-semibold text-blue-600">0</span> / <span id="modal_tax_number_max" class="font-semibold">25</span> characters
                        </small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kode Transaksi PPN *</label>
                        <select name="tax_type" id="modal_tax_type" class="form-control" required onchange="syncModalTaxRateFromCode()">
                            <option value="">Pilih Kode Transaksi</option>
                            @foreach(($financeTaxCodes ?? collect()) as $taxCode)
                                <option value="{{ $taxCode->code }}" data-zero-tax="{{ $taxCode->hasZeroTaxPrint() ? '1' : '0' }}">
                                    {{ $taxCode->code }} - {{ $taxCode->customer_status }}
                                </option>
                            @endforeach
                        </select>
                        <div id="modal_tax_code_description" class="text-gray-600 mt-2" style="font-size: 12px; line-height: 1.45; white-space: normal; overflow-wrap: anywhere;"></div>
                        <input type="hidden" name="ppn_code" id="modal_ppn_code">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tax Rate (%) *</label>
                        <input type="text" id="modal_tax_rate_display" class="form-control bg-gray-100 cursor-not-allowed" readonly tabindex="-1" value="{{ number_format((float) ($defaultVatSetting->tax_rate ?? 0), 2, '.', '') }}%">
                        <input type="hidden" name="tax_rate" id="modal_tax_rate" value="{{ number_format((float) ($defaultVatSetting->tax_rate ?? 0), 2, '.', '') }}">
                        <small class="text-gray-600 mt-1 block">Tax rate otomatis mengikuti Master Tax default dan kode transaksi PPN.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Effective Date *</label>
                        <input type="date" name="effective_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Tax Address *</label>
                    <textarea name="tax_address" class="form-control" style="min-height: 80px;" placeholder="Enter tax address" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 15px; margin-top: 15px; border-bottom: none; border-top: 1px solid #dee2e6; position: sticky; bottom: 0; background: #fefefe;">
            <button type="button" class="btn btn-secondary" onclick="closeAddTaxModal(); return false;" style="padding: 8px 20px; border: 1px solid #ccc; border-radius: 4px; background: #6c757d; color: white; cursor: pointer;">Cancel</button>
            <button type="button" id="saveTaxButton" class="btn btn-primary" onclick="saveTaxData(); return false;" style="padding: 8px 20px; border: 1px solid #007bff; border-radius: 4px; background: #007bff; color: white; cursor: pointer;">Save</button>
        </div>
    </div>
</div>
<div id="clientContactModal" class="modal" style="display: none; position: fixed; z-index: 1040; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto;">
    <div class="modal-content" style="background-color: #fefefe; margin: 20px auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 8px; position: relative; z-index: 1041; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #dee2e6;">
            <h3 style="margin: 0;">Add Client Contact</h3>
            <button type="button" onclick="closeClientContactModal()" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #aaa; padding: 0; width: 30px; height: 30px; line-height: 1;">&times;</button>
        </div>
        <div class="modal-body" style="flex: 1; overflow-y: auto; max-height: calc(90vh - 200px); padding-bottom: 10px;">
            <form id="clientContactForm">
                <input type="hidden" name="customer_id" id="clientContactCustomerId">
                <div class="form-group">
                    <label class="form-label">Jabatan / Posisi</label>
                    <select name="position" id="clientContactPosition" class="form-control no-select2">
                        <option value="">Pilih jabatan...</option>
                        @foreach($positions ?? [] as $position)
                            <option value="{{ $position->option_name ?? $position->name ?? $position }}">{{ $position->option_name ?? $position->name ?? $position }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Panggilan</label>
                    <select name="salutation" id="clientContactSalutation" class="form-control no-select2">
                        <option value="">Pilih panggilan...</option>
                        @foreach($salutations ?? [] as $salutation)
                            <option value="{{ $salutation->label ?? $salutation->option_name ?? $salutation->name ?? $salutation }}">{{ $salutation->label ?? $salutation->option_name ?? $salutation->name ?? $salutation }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Alt</label>
                    <input type="email" name="email_alt" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone 1</label>
                    <input type="tel" name="phone_1" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone 2</label>
                    <input type="tel" name="phone_2" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Status Karyawan</label>
                    <select name="employee_status" class="form-control">
                        <option value="">Pilih status...</option>
                        <option value="active">Aktif</option>
                        <option value="leave">Cuti</option>
                        <option value="resigned">Berhenti</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 15px; margin-top: 15px; border-top: 1px solid #dee2e6; position: sticky; bottom: 0; background: #fefefe;">
            <button type="button" id="cancelClientContactBtn" class="btn btn-secondary" onclick="closeClientContactModal(); return false;" style="padding: 8px 20px; border: 1px solid #ccc; border-radius: 4px; background: #6c757d; color: white; cursor: pointer;">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveClientContact(); return false;" style="padding: 8px 20px; border: 1px solid #007bff; border-radius: 4px; background: #007bff; color: white; cursor: pointer;">Save</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentStep = 1;
const totalSteps = 5;
let selectedQuotation = null;
let quotationData = null;
let quotationDetailsRequestId = 0;
const defaultVatTaxRate = Number(@json((float) ($defaultVatSetting->tax_rate ?? 0)));
const financeTaxCodeRules = @json($financeTaxCodeRules ?? []);
let billingAddresses = [];
let buildingSelections = [];
let currentTaxAddressIndex = null;

// Initialize wizard
document.addEventListener('DOMContentLoaded', function() {
    // Set current date for contract date
    document.getElementById('contractDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('installDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('firstService').value = new Date().toISOString().split('T')[0];
    
    // Initialize quotation select event listener
    const quotationSelectElement = document.getElementById('quotationSelect');
    if (quotationSelectElement) {
        // Set selectedQuotation if dropdown already has a value
        if (quotationSelectElement.value) {
            selectedQuotation = quotationSelectElement.value;
            console.log('DOMContentLoaded: Quotation already selected:', selectedQuotation);
        }
    }
});

// Step navigation
function changeStep(direction) {
    if (direction > 0 && currentStep < totalSteps) {
        if (validateCurrentStep()) {
            currentStep++;
            updateStepDisplay();
        }
    } else if (direction < 0 && currentStep > 1) {
        currentStep--;
        updateStepDisplay();
    }
}

function updateStepDisplay() {
    // Hide all steps
    document.querySelectorAll('.wizard-step').forEach(step => {
        step.style.display = 'none';
    });
    
    // Show current step
    document.getElementById(`step${currentStep}`).style.display = 'block';
    
    // Update step indicators
    document.querySelectorAll('.step').forEach((step, index) => {
        const stepNumber = index + 1;
        step.classList.remove('active', 'completed');
        
        if (stepNumber === currentStep) {
            step.classList.add('active');
        } else if (stepNumber < currentStep) {
            step.classList.add('completed');
            step.querySelector('.step-number').innerHTML = '<i class="fas fa-check"></i>';
        } else {
            step.querySelector('.step-number').textContent = stepNumber;
        }
    });
    
    // Update navigation buttons
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const finalizeBtn = document.getElementById('finalizeBtn');
    
    prevBtn.style.display = currentStep > 1 ? 'block' : 'none';
    
    if (currentStep === totalSteps) {
        nextBtn.style.display = 'none';
        saveDraftBtn.style.display = 'block';
        finalizeBtn.style.display = 'block';
    } else {
        nextBtn.style.display = 'block';
        saveDraftBtn.style.display = 'none';
        finalizeBtn.style.display = 'none';
    }
    
    // Load step-specific data after a short delay to ensure DOM is ready
    setTimeout(() => {
        loadStepData();
    }, 100);
}

function validateCurrentStep() {
    switch(currentStep) {
        case 1:
            const quotationSelect = document.getElementById('quotationSelect');
            if (!quotationSelect.value) {
                alert('Please select a quotation');
                return false;
            }
            return true;
        case 2:
            return true; // Step 2 is display only
        case 3:
            const requiredFields = ['ppnCode', 'contractDate', 'installDate', 'firstService', 'adsSigning', 'companySigning1'];
            for (let field of requiredFields) {
                const element = document.getElementById(field);
                if (!element || !element.value) {
                    const fieldName = field.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    alert(`Please fill in ${fieldName}`);
                    return false;
                }
            }
            return true;
        case 4:
            if (billingAddresses.length === 0) {
                alert('Please add at least one billing address');
                return false;
            }
            
            // Validate Tax Identity for each billing address
            updateBillingAddressData(); // Ensure JS objects are synced with DOM
            
            for (let i = 0; i < billingAddresses.length; i++) {
                const taxId = billingAddresses[i].tax_id;
                const taxSelect = document.getElementById(`taxSelect_${i}`);
                
                // Validate Tax ID
                if (!taxId && (!taxSelect || !taxSelect.value)) {
                    alert(`Mohon lengkapi Identitas Pajak (NPWP/NITKU) untuk Billing Group ${i + 1}`);
                    
                    // Highlight the empty field
                    if (taxSelect) {
                        taxSelect.focus();
                        taxSelect.style.borderColor = 'red';
                        // Reset border color on change
                        taxSelect.addEventListener('change', function() {
                            this.style.borderColor = '';
                        }, { once: true });
                    }
                    return false;
                }
            }
            return true;
        case 5:
            // Validate that each billing address has at least one building selected
            // updateBillingAddressDataWithSelectedBuildings(); // DEPRECATED: Consumes stale data
            updateBillingAddressData(); // Ensure we have latest data from Step 4 inputs

            for (let i = 0; i < billingAddresses.length; i++) {
                // Check buildings array directly from billingAddresses (populated from Step 4 inputs)
                const buildings = billingAddresses[i].buildings || [];
                if (buildings.length === 0) {
                    alert(`Please select at least one building for Billing Address ${i + 1}`);
                    return false;
                }
            }
            return true;
        default:
            return true;
    }
}

function loadStepData() {
    switch(currentStep) {
        case 2:
            // Ensure selectedQuotation is set from dropdown before loading details
            const quotationSelect = document.getElementById('quotationSelect');
            if (quotationSelect && quotationSelect.value) {
                selectedQuotation = quotationSelect.value;
                console.log('Step 2: Setting selectedQuotation from dropdown:', selectedQuotation);
            }
            loadQuotationDetails();
            break;
        case 3:
            // Populate contract data from quotation when step 3 is loaded
            console.log('=== STEP 3 LOADED ===');
            console.log('quotationData available:', quotationData ? 'yes' : 'no');
            console.log('selectedQuotation:', selectedQuotation);
            
            if (quotationData) {
                console.log('Step 3: Populating contract data from stored quotationData');
                // Use setTimeout to ensure form fields are rendered
                setTimeout(() => {
                    populateContractData(quotationData);
                }, 150);
            } else {
                // If quotationData is not set, try to load it from selectedQuotation
                const quotationSelect = document.getElementById('quotationSelect');
                if (quotationSelect && quotationSelect.value) {
                    selectedQuotation = quotationSelect.value;
                    console.log('Step 3: Loading quotation data for:', selectedQuotation);
                    loadQuotationData(selectedQuotation);
                } else {
                    console.warn('Step 3: No quotation data available and no quotation selected');
                }
            }
            break;
        case 4:
            loadBillingAddresses();
            break;
        case 5:
            loadBuildingSelection();
            updateMergeSummaryReview(); // [NEW] Update merge summary
            break;
    }
}

// Step 1: Load quotation data
const quotationSelectElement = document.getElementById('quotationSelect');
if (quotationSelectElement) {
    quotationSelectElement.addEventListener('change', function() {
        if (this.value) {
            selectedQuotation = this.value;
            console.log('Quotation selected:', selectedQuotation);
            loadQuotationData(this.value);
        } else {
            selectedQuotation = null;
            console.log('Quotation deselected');
        }
    });
    
    // Also set selectedQuotation if dropdown already has a value on page load
    if (quotationSelectElement.value) {
        selectedQuotation = quotationSelectElement.value;
        console.log('Quotation already selected on page load:', selectedQuotation);
    }
} else {
    console.error('quotationSelect element not found');
}

function loadQuotationData(quotationId) {
    console.log('=== LOADING QUOTATION DATA ===');
    console.log('Quotation ID:', quotationId);
    fetch(`/marketing/contracts/wizard/get-quotation-details?quotation_id=${quotationId}`)
        .then(response => {
            console.log('Quotation API response status:', response.status);
            if (!response.ok) {
                throw new Error(`Failed to load quotation data (${response.status})`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Quotation API response data:', data);
            if (data.success) {
                // Store quotation data globally
                quotationData = data.quotation;
                quotationData.customer = data.customer || quotationData.customer || null;
                quotationData.marketing = data.marketing || quotationData.marketing || null;
                quotationData.quotationDetails = Array.isArray(data.quotationDetails) ? data.quotationDetails : [];
                console.log('✓ Quotation data stored globally');
                console.log('Quotation data:', quotationData);
                console.log('Customer data:', quotationData.customer);
                console.log('Rental period:', quotationData.rental_period);
                console.log('Rental unit:', quotationData.rental_unit);
                console.log('Term of payment:', quotationData.term_of_payment);
                console.log('Payment method:', quotationData.billing_methods);
                
                // [NEW] Load Merge Candidates for the customer
                if (data.customer && data.customer.id) {
                    loadMergeCandidates(data.customer.id);
                }
                
                // Populate form fields with quotation data
                populateContractData(data.quotation);
            } else {
                console.error('Quotation API returned success: false');
            }
        })
        .catch(error => {
            console.error('Error loading quotation data:', error);
        });
}

// [NEW] Function to load merge candidates
let selectedMergeContractIds = [];
function loadMergeCandidates(customerId) {
    const section = document.getElementById('mergeContractsSection');
    const loading = document.getElementById('mergeCandidatesLoading');
    const list = document.getElementById('mergeCandidatesList');
    const noCand = document.getElementById('noMergeCandidates');
    
    if (!section || !list) return;
    
    section.style.display = 'block';
    loading.style.display = 'block';
    list.style.display = 'none';
    noCand.style.display = 'none';
    list.innerHTML = '';
    selectedMergeContractIds = []; // Reset selected

    fetch(`/marketing/contracts/merge-candidates?customer_id=${customerId}`)
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success && data.data.length > 0) {
                list.style.display = 'flex'; // Changed to flex for bootstrap row
                list.classList.add('flex-wrap');
                data.data.forEach(c => {
                    const card = `
                        <div class="col-md-6 mb-3">
                            <label class="d-block p-3 border rounded cursor-pointer transition-all merge-card" 
                                   id="merge-card-${c.id}" 
                                   style="background: #f9fafb; border-color: #e5e7eb; cursor: pointer;">
                                <div class="d-flex align-items-start">
                                    <input type="checkbox" class="mt-1 mr-3 merge-checkbox" value="${c.id}" 
                                           onchange="toggleMergeContract(${c.id}, '${c.contract_number}')">
                                    <div class="flex-grow-1">
                                        <div class="font-weight-bold text-primary" style="font-size: 0.9rem;">${c.contract_number}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            End: ${c.end_date} | ${c.rooms_count} Rooms | ${c.rentals_count} Items
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    `;
                    list.insertAdjacentHTML('beforeend', card);
                });
            } else {
                noCand.style.display = 'block';
            }
        })
        .catch(err => {
            console.error('Error loading merge candidates:', err);
            loading.style.display = 'none';
            noCand.style.display = 'block';
        });
}

function toggleMergeContract(id, number) {
    const card = document.getElementById(`merge-card-${id}`);
    const checkbox = card.querySelector('input');
    
    if (checkbox.checked) {
        if (!selectedMergeContractIds.some(item => item.id === id)) {
            selectedMergeContractIds.push({id: id, number: number});
        }
        card.style.borderColor = '#3b82f6';
        card.style.backgroundColor = '#eff6ff';
        card.style.boxShadow = '0 0 0 1px #3b82f6';
    } else {
        selectedMergeContractIds = selectedMergeContractIds.filter(item => item.id !== id);
        card.style.borderColor = '#e5e7eb';
        card.style.backgroundColor = '#f9fafb';
        card.style.boxShadow = 'none';
    }
    
    console.log('Selected merge IDs:', selectedMergeContractIds);
}

function updateMergeSummaryReview() {
    const reviewSection = document.getElementById('mergeSummaryReview');
    const reviewList = document.getElementById('selectedMergeContractsList');
    
    if (selectedMergeContractIds.length > 0) {
        reviewSection.style.display = 'block';
        reviewList.innerHTML = selectedMergeContractIds.map(item => `<li>${item.number}</li>`).join('');
    } else {
        reviewSection.style.display = 'none';
    }
}

function populateContractData(quotation) {
    console.log('=== POPULATING CONTRACT DATA ===');
    console.log('Quotation object:', quotation);
    
    // Populate readonly fields with correct data structure
    const nibField = document.getElementById('nib');
    const companyNameField = document.getElementById('companyName');
    const rentalPeriodField = document.getElementById('rentalPeriod');
    const rentalUnitField = document.getElementById('rentalUnit');
    const paymentMethodField = document.getElementById('paymentMethod');
    
    console.log('Form fields found:');
    console.log('  nibField:', nibField ? 'found' : 'not found');
    console.log('  companyNameField:', companyNameField ? 'found' : 'not found');
    console.log('  rentalPeriodField:', rentalPeriodField ? 'found' : 'not found');
    console.log('  rentalUnitField:', rentalUnitField ? 'found' : 'not found');
    console.log('  paymentMethodField:', paymentMethodField ? 'found' : 'not found');
    
    // Populate NIB from customer.nib field (master customer)
    // If company has NIB, fill it; otherwise leave it empty (readonly)
    if (nibField && quotation.customer) {
        // Get NIB directly from customer.nib field
        const nibValue = quotation.customer.nib || '';
        
        // Set NIB value (empty if no NIB found)
        nibField.value = nibValue;
        
        // Field is already readonly in HTML, so no need to change it
    }
    
    // Populate company name from customer data
    if (companyNameField && quotation.customer) {
        companyNameField.value = quotation.customer.name || '';
    }
    
    // Populate PPN Code from customer tax settings
    const ppnCodeField = document.getElementById('ppnCode');
    if (ppnCodeField && quotation.customer) {
        if (quotation.customer.ppn_code) {
            ppnCodeField.value = quotation.customer.ppn_code;
        } else if (quotation.customer.tax_code) {
            ppnCodeField.value = quotation.customer.tax_code;
        }
        // Trigger change event just in case
        ppnCodeField.dispatchEvent(new Event('change'));
    }
    
    // Populate rental period from quotation
    if (rentalPeriodField) {
        rentalPeriodField.value = quotation.rental_period || '';
    }
    
    // Populate rental unit from quotation.rental_unit (hari/bulan)
    // If rental_unit is empty, extract from rental_period (e.g., "10 bulan" -> "bulan")
    if (rentalUnitField) {
        let rentalUnit = quotation.rental_unit || '';
        
        // If rental_unit is empty but rental_period contains unit, extract it
        if (!rentalUnit && quotation.rental_period) {
            const rentalPeriod = quotation.rental_period.toString().toLowerCase().trim();
            
            // Extract unit from rental_period (e.g., "10 bulan", "5 hari", "12 bulan")
            if (rentalPeriod.includes('bulan') || rentalPeriod.includes('month')) {
                rentalUnit = 'bulan';
            } else if (rentalPeriod.includes('hari') || rentalPeriod.includes('day')) {
                rentalUnit = 'hari';
            } else if (rentalPeriod.includes('tahun') || rentalPeriod.includes('year')) {
                rentalUnit = 'tahun';
            }
        }
        
        rentalUnitField.value = rentalUnit;
    }
    
    // Populate Terms of Payment (TOP) from quotation
    const termOfPaymentField = document.getElementById('termOfPayment');
    if (termOfPaymentField) {
        termOfPaymentField.value = quotation.term_of_payment || quotation.terms_of_payment || '';
    }
    
    // Populate payment method from quotation (billing_methods)
    if (paymentMethodField) {
        paymentMethodField.value = quotation.billing_methods || '';
    }
    
    // Populate remark fields from quotation
    const remarkExternalField = document.getElementById('remarkExternal');
    const remarkInternalField = document.getElementById('remarkInternal');
    
    if (remarkExternalField) {
        remarkExternalField.value = quotation.additional_notes || '';
    }
    
    if (remarkInternalField) {
        remarkInternalField.value = quotation.internal_notes || '';
    }
    
    // Populate Company Contact Signing dropdowns with customer contacts
    if (quotation.customer && quotation.customer.customer_contacts) {
        loadCustomerContacts(quotation.customer.customer_contacts);
    } else if (quotation.customer && quotation.customer.id) {
        // If contacts not loaded, fetch them
        loadCustomerContactsFromAPI(quotation.customer.id);
    }
}

function loadCustomerContacts(contacts) {
    // Get all Company Contact Signing dropdowns
    const signingSelects = [
        document.getElementById('companySigning1'),
        document.getElementById('companySigning2'),
        document.getElementById('companySigning3'),
        document.getElementById('companySigning4')
    ];
    
    signingSelects.forEach(select => {
        if (!select) return;
        
        // Clear existing options except the first one
        select.innerHTML = '<option value="">Pilih atau ketik disini...</option>';
        
        // Add customer contacts
        if (contacts && contacts.length > 0) {
            contacts.forEach(contact => {
                // Only show active contacts
                if (contact.is_active !== false) {
                    const option = document.createElement('option');
                    option.value = contact.id;
                    option.textContent = contact.name + (contact.position ? ` - ${contact.position}` : '');
                    select.appendChild(option);
                }
            });
        } else {
            // If no contacts, show message
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Tidak ada contact untuk customer ini';
            option.disabled = true;
            select.appendChild(option);
        }
    });
}

const customerLookupCache = {
    contacts: new Map(),
    buildings: new Map(),
};

function fetchCustomerContactsCached(customerId) {
    if (!customerId) {
        return Promise.resolve([]);
    }

    if (customerLookupCache.contacts.has(customerId)) {
        return customerLookupCache.contacts.get(customerId);
    }

    const request = fetch(`/api/customers/${customerId}/contacts`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => Array.isArray(data) ? data : (data.data || []))
    .catch(error => {
        customerLookupCache.contacts.delete(customerId);
        throw error;
    });

    customerLookupCache.contacts.set(customerId, request);

    return request;
}

function fetchCustomerBuildingsCached(customerId) {
    if (!customerId) {
        return Promise.resolve([]);
    }

    if (customerLookupCache.buildings.has(customerId)) {
        return customerLookupCache.buildings.get(customerId);
    }

    const request = fetch(`/api/customers/${customerId}/buildings`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (Array.isArray(data)) {
            return data;
        }

        if (data.status === 'success' && Array.isArray(data.data)) {
            return data.data;
        }

        if (data.status === 'success' && data.data && Array.isArray(data.data.data)) {
            return data.data.data;
        }

        return [];
    })
    .catch(error => {
        customerLookupCache.buildings.delete(customerId);
        throw error;
    });

    customerLookupCache.buildings.set(customerId, request);

    return request;
}

function loadCustomerContactsFromAPI(customerId) {
    fetchCustomerContactsCached(customerId)
    .then(contacts => {
        loadCustomerContacts(contacts);
    })
    .catch(error => {
        console.error('Error loading customer contacts:', error);
    });
}

// Step 2: Load quotation details
function loadQuotationDetails() {
    const currentRequestId = ++quotationDetailsRequestId;
    // Get quotation ID from dropdown if not already set
    const quotationSelect = document.getElementById('quotationSelect');
    if (quotationSelect && quotationSelect.value) {
        selectedQuotation = quotationSelect.value;
        console.log('Quotation ID retrieved from dropdown:', selectedQuotation);
    }
    
    if (!selectedQuotation) {
        console.error('No quotation selected');
        console.error('quotationSelect element:', quotationSelect);
        console.error('quotationSelect value:', quotationSelect ? quotationSelect.value : 'element not found');
        return;
    }
    
    console.log('Loading quotation details for ID:', selectedQuotation);
    
    fetch(`/marketing/contracts/wizard/get-quotation-details?quotation_id=${selectedQuotation}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`Failed to load quotation details (${response.status})`);
            }
            return response.json();
        })
        .then(data => {
            if (currentRequestId !== quotationDetailsRequestId) {
                return;
            }
            console.log('Received data:', data);
            if (data && data.success) {
                quotationData = data.quotation || quotationData;
                if (quotationData) {
                    quotationData.customer = data.customer || quotationData.customer || null;
                    quotationData.marketing = data.marketing || quotationData.marketing || null;
                    quotationData.quotationDetails = Array.isArray(data.quotationDetails) ? data.quotationDetails : [];
                }
                console.log('Quotation details count:', data.quotationDetails ? data.quotationDetails.length : 'undefined');
                displayQuotationInfo(data.quotation);
                displayCustomerInfo(data.customer);
                displayQuotationItems(data.quotationDetails);
                displayQuotationSummary(data.quotation);

                // [NEW] Load Merge Candidates for the customer when step 2 is loaded
                if (data.customer && data.customer.id) {
                    loadMergeCandidates(data.customer.id);
                }
            } else {
                console.error('No data received from API or success is false');
                console.error('Data received:', data);
                renderQuotationDetailsFromCache();
            }
        })
        .catch(error => {
            console.error('Error loading quotation details:', error);
            renderQuotationDetailsFromCache();
        });
}

function renderQuotationDetailsFromCache() {
    if (!quotationData) {
        return;
    }

    displayQuotationInfo(quotationData);
    displayCustomerInfo(quotationData.customer || {});
    displayQuotationItems(Array.isArray(quotationData.quotationDetails) ? quotationData.quotationDetails : []);
    displayQuotationSummary(quotationData);
}

// Helper function to format date from ISO format to readable format
function formatDate(dateString) {
    if (!dateString || dateString === '-') {
        return '-';
    }
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return dateString; // Return original if invalid date
        }
        
        // Format: DD MMM YYYY (e.g., 03 Nov 2025)
        const day = String(date.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        
        return `${day} ${month} ${year}`;
    } catch (e) {
        return dateString; // Return original if error
    }
}

function displayQuotationInfo(quotation) {
    const grid = document.getElementById('quotationInfoGrid');
    grid.innerHTML = `
        <div class="info-item">
            <div class="info-label">Nomor Quotation</div>
            <div class="info-value">${quotation.quotation_number || '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Status Quotation</div>
            <div class="info-value">${quotation.status || '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Tanggal Quotation</div>
            <div class="info-value">${formatDate(quotation.quotation_date)}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Valid Until</div>
            <div class="info-value">${formatDate(quotation.valid_until)}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Approved By</div>
            <div class="info-value">${quotation.approver ? quotation.approver.name : '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Approved Date</div>
            <div class="info-value">${formatDate(quotation.date_approved)}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Created By</div>
            <div class="info-value">${quotation.creator ? quotation.creator.name : '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Nama Marketing</div>
            <div class="info-value">${quotation.marketing ? quotation.marketing.name : '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Jenis Penawaran</div>
            <div class="info-value">${quotation.quotation_type || '-'}</div>
        </div>
    `;
}

function displayCustomerInfo(customer) {
    const grid = document.getElementById('customerInfoGrid');
    grid.innerHTML = `
        <div class="info-item">
            <div class="info-label">Nama Customer</div>
            <div class="info-value">${customer.name || '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Jenis Customer</div>
            <div class="info-value">${customer.customer_type || '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Alamat</div>
            <div class="info-value">${customer.address || '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Telepon</div>
            <div class="info-value">${customer.phone || '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Email</div>
            <div class="info-value">${customer.email || '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Contact Person</div>
            <div class="info-value">${customer.customer_contacts && customer.customer_contacts.length > 0 ? customer.customer_contacts[0].name : '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Contact Phone</div>
            <div class="info-value">${customer.customer_contacts && customer.customer_contacts.length > 0 ? customer.customer_contacts[0].phone : '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Contact Email</div>
            <div class="info-value">${customer.customer_contacts && customer.customer_contacts.length > 0 ? customer.customer_contacts[0].email : '-'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Company Type</div>
            <div class="info-value">${customer.company_type || '-'}</div>
        </div>
    `;
}

function displayQuotationItems(items) {
    const tbody = document.getElementById('quotationItemsBody');
    tbody.innerHTML = '';
    
    console.log('Displaying quotation items:', items);
    
    if (!items || items.length === 0) {
        console.log('No items found or items is empty');
        tbody.innerHTML = '<tr><td colspan="3" class="text-center">No items found</td></tr>';
        return;
    }
    
    items.forEach(item => {
        const row = document.createElement('tr');
        
        // Get item description - use rental_alias if available, otherwise use rental_name
        let itemDescription = '-';
        if (item.rental_alias) {
            // Use rental alias if available (per item basis)
            itemDescription = item.rental_alias;
        } else if (item.master_rental && item.master_rental.rental_name) {
            itemDescription = item.master_rental.rental_name;
        } else if (item.master_rental && item.master_rental.name) {
            itemDescription = item.master_rental.name;
        } else if (item.product_name) {
            itemDescription = item.product_name;
        } else if (item.description) {
            itemDescription = item.description;
        } else if (item.rental_name) {
            itemDescription = item.rental_name;
        } else if (item.room_name) {
            itemDescription = `Room: ${item.room_name}`;
        }
        
        // Format price
        let formattedPrice = '-';
        const price = item.unit_price || item.price;
        if (price && price > 0) {
            formattedPrice = new Intl.NumberFormat('id-ID', { 
                style: 'currency', 
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(price);
        }
        
        row.innerHTML = `
            <td>${itemDescription}</td>
            <td>${item.quantity || 0}</td>
            <td>${formattedPrice}</td>
        `;
        tbody.appendChild(row);
    });
}

function displayQuotationSummary(quotation) {
    const summaryDiv = document.getElementById('quotationSummary');
    const subTotalEl = document.getElementById('subTotal');
    const discountAmountEl = document.getElementById('discountAmount');
    const grandTotalEl = document.getElementById('grandTotal');
    
    if (!quotation) {
        summaryDiv.style.display = 'none';
        return;
    }
    
    // Format currency
    const formatCurrency = (amount) => {
        if (!amount || amount === 0) return '-';
        return new Intl.NumberFormat('id-ID', { 
            style: 'currency', 
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    };
    
    // Display values
    subTotalEl.textContent = formatCurrency(quotation.total_amount);
    discountAmountEl.textContent = formatCurrency(quotation.discount_amount);
    // Grand Total is already calculated without PPN since PPN is removed
    grandTotalEl.textContent = formatCurrency(quotation.grand_total);
    
    // Show summary
    summaryDiv.style.display = 'block';
}

// Step 4: Billing Address functions
function normalizeTaxSettingForBilling(setting) {
    const taxName = (setting.tax_name || '').toUpperCase();
    const taxNumber = (setting.tax_number || '').replace(/\D/g, '');
    const savedNitku = (setting.nitku || '').replace(/\D/g, '');

    if (taxName === 'NITKU') {
        const fullNitku = taxNumber.length === 22 ? taxNumber : `${taxNumber}${savedNitku}`.replace(/\D/g, '');

        return {
            label: `NITKU: ${fullNitku || taxNumber}`,
            npwp: fullNitku.length >= 16 ? fullNitku.substring(0, 16) : '',
            nitku: fullNitku.length >= 22 ? fullNitku.substring(16, 22) : (savedNitku || ''),
            nik: '',
        };
    }

    if (taxName === 'NPWP') {
        return {
            label: `NPWP: ${taxNumber}`,
            npwp: taxNumber,
            nitku: savedNitku || '000000',
            nik: '',
        };
    }

    if (taxName === 'NIK') {
        return {
            label: `NIK: ${taxNumber}`,
            npwp: '',
            nitku: savedNitku || '000000',
            nik: taxNumber,
        };
    }

    return {
        label: `${taxName}: ${taxNumber}`,
        npwp: '',
        nitku: '',
        nik: taxNumber,
    };
}

// Populate Tax Dropdown - NPWP+NITKU Paired Options
function populateTaxDropdown(addressIndex) {
    const select = document.getElementById(`taxSelect_${addressIndex}`);
    if (!select) return;

    select.innerHTML = '<option value="">Pilih data pajak...</option>';

    if (!quotationData || !quotationData.customer) {
        return;
    }

    const customer = quotationData.customer;
    let hasTaxData = false;
    
    // Iterate over customer_tax_settings
    if (customer.customer_tax_settings && customer.customer_tax_settings.length > 0) {
        customer.customer_tax_settings.forEach(setting => {
            const taxAddress = (setting.tax_address || '').trim();
            const normalizedTax = normalizeTaxSettingForBilling(setting);
            let label = normalizedTax.label;
            
            if (taxAddress) label += ` - ${taxAddress}`;

            const valueData = JSON.stringify({
                source: 'Settings',
                setting_id: setting.id,
                npwp: normalizedTax.npwp,
                nitku: normalizedTax.nitku,
                nik: normalizedTax.nik,
                address: taxAddress,
                ppn_code: setting.ppn_code || (setting.tax_type ? setting.tax_type.slice(0, 2) : '')
            });

            const option = document.createElement('option');
            option.value = valueData;
            option.textContent = label;
            option.setAttribute('data-npwp', normalizedTax.npwp);
            option.setAttribute('data-nitku', normalizedTax.nitku);
            option.setAttribute('data-nik', normalizedTax.nik);
            option.setAttribute('data-ppn-code', setting.ppn_code || (setting.tax_type ? setting.tax_type.slice(0, 2) : ''));
            option.setAttribute('data-address', taxAddress);
            
            select.appendChild(option);
            hasTaxData = true;
        });
    }

    if (!hasTaxData) {
        const option = document.createElement('option');
        option.value = "";
        option.textContent = "Tidak ada data pajak tersedia";
        option.disabled = true;
        select.appendChild(option);
    }
}

function handleTaxSelection(addressIndex) {
    const select = document.getElementById(`taxSelect_${addressIndex}`);
    const displayInput = document.getElementById(`taxDisplay_${addressIndex}`);
    const npwpInput = document.getElementById(`taxNpwp_${addressIndex}`);
    const nitkuInput = document.getElementById(`taxNitku_${addressIndex}`);
    const nikInput = document.getElementById(`taxNik_${addressIndex}`);
    const addressInput = document.getElementById(`taxAddress_${addressIndex}`);
    
    if (select && select.selectedIndex > 0) {
        const selectedOption = select.options[select.selectedIndex];
        
        // Set display value
        if (displayInput) {
            displayInput.value = selectedOption.textContent;
        }
        
        // Parse JSON value and set hidden fields
        try {
            const taxData = JSON.parse(selectedOption.value);
            if (npwpInput) npwpInput.value = taxData.npwp || '';
            if (nitkuInput) nitkuInput.value = taxData.nitku || '';
            if (nikInput) nikInput.value = taxData.nik || '';
            if (addressInput) addressInput.value = taxData.address || '';
            
            console.log('Tax selection updated:', taxData);
        } catch (e) {
            // Fallback to data attributes if JSON parsing fails
            if (npwpInput) npwpInput.value = selectedOption.getAttribute('data-npwp') || '';
            if (nitkuInput) nitkuInput.value = selectedOption.getAttribute('data-nitku') || '';
            if (nikInput) nikInput.value = selectedOption.getAttribute('data-nik') || '';
            if (addressInput) addressInput.value = selectedOption.getAttribute('data-address') || '';
            console.log('Tax selection (from attributes):', selectedOption.value);
        }
    } else {
        // Clear all fields
        if (displayInput) displayInput.value = '';
        if (npwpInput) npwpInput.value = '';
        if (nitkuInput) nitkuInput.value = '';
        if (nikInput) nikInput.value = '';
        if (addressInput) addressInput.value = '';
    }
}

function closeTaxSelectModal() {
    const modal = document.getElementById('taxSelectModal');
    modal.style.display = 'none';
    currentTaxAddressIndex = null;
}

function saveTaxSelection() {
    if (currentTaxAddressIndex === null) return;

    const checkboxes = document.querySelectorAll('#taxSelectionList input[name="tax_selection"]:checked');
    const selectedIds = [];
    const selectedDisplays = [];

    checkboxes.forEach(cb => {
        selectedIds.push(cb.value);
        selectedDisplays.push(cb.getAttribute('data-display'));
    });

    // Update Input Fields
    const displayInput = document.getElementById(`taxDisplay_${currentTaxAddressIndex}`);
    const idInput = document.getElementById(`taxId_${currentTaxAddressIndex}`);

    if (displayInput) displayInput.value = selectedDisplays.join(' | ');
    if (idInput) idInput.value = selectedIds.join(',');
    
    // Update data object
    if (billingAddresses[currentTaxAddressIndex]) {
        billingAddresses[currentTaxAddressIndex].tax_display = selectedDisplays.join(' | ');
        billingAddresses[currentTaxAddressIndex].tax_id = selectedIds.join(',');
    }

    closeTaxSelectModal();
}

function openAddressModal(addressIndex) {
    console.log('openAddressModal called for addressIndex:', addressIndex);
    console.log('quotationData:', quotationData);
    
    const addressSelect = document.getElementById(`addressSelect_${addressIndex}`) || document.querySelector(`select[name="billing_addresses[${addressIndex}][address_id]"]`);
    console.log('addressSelect element:', addressSelect);
    
    if (!addressSelect) {
        console.error('Address select element not found');
        return;
    }
    
    // Skip if disabled (reusing existing billing group)
    if (addressSelect.disabled) {
        console.log('Address select is disabled (reusing existing billing group)');
        return;
    }
    
    // Destroy Select2 if it exists (jQuery Select2)
    if (typeof $ !== 'undefined' && $(addressSelect).hasClass('select2-hidden-accessible')) {
        try {
            $(addressSelect).select2('destroy');
            console.log('Destroyed Select2 for addressSelect_' + addressIndex);
        } catch (e) {
            console.warn('Could not destroy Select2:', e);
        }
    }
    
    if (quotationData && quotationData.customer) {
        console.log('Loading buildings for customer:', quotationData.customer.id);
        // Load buildings for specific customer
        fetchCustomerBuildingsCached(quotationData.customer.id)
        .then(buildings => {
            console.log('Customer buildings count:', buildings.length);
            if (buildings.length > 0) {
                // Get all selected building IDs from other billing addresses (to hide them)
                const allSelectedBuildingIds = [];
                
                console.log(`Checking for selected buildings in other billing addresses (current: ${addressIndex})...`);
                console.log(`Total billing addresses: ${billingAddresses.length}`);
                
                billingAddresses.forEach((addr, idx) => {
                    if (idx !== addressIndex && addr && addr.element) {
                        console.log(`Checking billing address ${idx}...`);
                        
                        // Try multiple selectors to find the address select
                        const otherAddressSelect = addr.element.querySelector(`select[name="billing_addresses[${idx}][address_id]"]`) 
                            || document.getElementById(`addressSelect_${idx}`)
                            || (addr.element.querySelector ? addr.element.querySelector(`#addressSelect_${idx}`) : null);
                        
                        if (otherAddressSelect) {
                            console.log(`Found addressSelect for billing address ${idx}, disabled: ${otherAddressSelect.disabled}`);
                            
                            if (!otherAddressSelect.disabled) {
                                // Get all selected options (multiple select)
                                const selectedOptions = Array.from(otherAddressSelect.selectedOptions || []);
                                console.log(`Billing address ${idx} has ${selectedOptions.length} selected options`);
                                
                                selectedOptions.forEach(opt => {
                                    if (opt && opt.value && opt.value !== '' && opt.value !== 'Pilih atau ketik disini...') {
                                        const buildingId = parseInt(opt.value);
                                        if (!isNaN(buildingId) && buildingId > 0) {
                                            if (!allSelectedBuildingIds.includes(buildingId)) {
                                                allSelectedBuildingIds.push(buildingId);
                                                console.log(`  - Found selected building ID: ${buildingId}`);
                                            }
                                        }
                                    }
                                });
                                
                                // Also check all options, not just selected ones (in case of pre-selected)
                                Array.from(otherAddressSelect.options || []).forEach(opt => {
                                    if (opt && opt.selected && opt.value && opt.value !== '' && opt.value !== 'Pilih atau ketik disini...') {
                                        const buildingId = parseInt(opt.value);
                                        if (!isNaN(buildingId) && buildingId > 0) {
                                            if (!allSelectedBuildingIds.includes(buildingId)) {
                                                allSelectedBuildingIds.push(buildingId);
                                                console.log(`  - Found pre-selected building ID: ${buildingId}`);
                                            }
                                        }
                                    }
                                });
                            }
                        } else {
                            console.log(`Could not find addressSelect for billing address ${idx}`);
                        }
                    } else {
                        if (idx !== addressIndex) {
                            console.log(`Skipping billing address ${idx} - no element or invalid`);
                        }
                    }
                });
                
                console.log(`Filtering buildings for address ${addressIndex}. Excluding building IDs:`, allSelectedBuildingIds);
                console.log(`Total billing addresses: ${billingAddresses.length}`);
                
                // Convert allSelectedBuildingIds to numbers for comparison
                const excludedIds = allSelectedBuildingIds.map(id => parseInt(id)).filter(id => !isNaN(id));
                console.log(`Excluded IDs (as numbers):`, excludedIds);
                
                // Capture current selection BEFORE destroying Select2 or clearing options
                let currentSelectedValues = [];
                if (typeof $ !== 'undefined' && $(addressSelect).hasClass('select2-hidden-accessible')) {
                    currentSelectedValues = $(addressSelect).val() || [];
                } else {
                     currentSelectedValues = Array.from(addressSelect.selectedOptions).map(opt => opt.value);
                }
                console.log(`Address ${addressIndex} current selection preserved:`, currentSelectedValues);

                // Destroy Select2 if it exists (Select2 might cache options)
                if (typeof $ !== 'undefined' && $(addressSelect).hasClass('select2-hidden-accessible')) {
                    try {
                        $(addressSelect).select2('destroy');
                        console.log(`Destroyed Select2 for addressSelect_${addressIndex}`);
                    } catch (e) {
                        console.warn('Could not destroy Select2:', e);
                    }
                }
                
                // Clear all existing options first - IMPORTANT: Do this BEFORE checking excludedIds
                addressSelect.innerHTML = '<option value="">Pilih atau ketik disini...</option>';
                
                let shownCount = 0;
                let hiddenCount = 0;
                
                buildings.forEach(building => {
                    const buildingId = parseInt(building.id);
                    // Skip building if it's already selected in another billing address
                    if (excludedIds.includes(buildingId)) {
                        hiddenCount++;
                        console.log(`Hiding building ${buildingId} (${building.nama_gedung || building.name}) - already selected in another billing group`);
                        return; // Don't show this building
                    }
                    
                    shownCount++;
                    
                    const option = document.createElement('option');
                    option.value = building.id;
                    
                    // Build full address string with all details
                    const addressParts = [];
                    
                    // Nama gedung
                    if (building.nama_gedung || building.name) {
                        addressParts.push(building.nama_gedung || building.name);
                    }
                    
                    // Alamat
                    if (building.alamat_1 || building.address) {
                        addressParts.push(building.alamat_1 || building.address);
                    }
                    if (building.alamat_2) {
                        addressParts.push(building.alamat_2);
                    }
                    
                    // Lokasi (provinsi, city, district, subdistrict)
                    // Backend already sends as string (building.city.name), but check if it's object or string
                    const locationParts = [];
                    if (building.city) {
                        const cityName = typeof building.city === 'string' ? building.city : (building.city.name || '');
                        // Only add if it's a valid string and not boolean
                        if (cityName && typeof cityName === 'string' && cityName !== 'true' && cityName !== 'false' && cityName.trim() !== '') {
                            locationParts.push(cityName);
                        }
                    }
                    if (building.district) {
                        const districtName = typeof building.district === 'string' ? building.district : (building.district.name || '');
                        if (districtName && typeof districtName === 'string' && districtName !== 'true' && districtName !== 'false' && districtName.trim() !== '') {
                            locationParts.push(districtName);
                        }
                    }
                    if (building.subdistrict) {
                        const subdistrictName = typeof building.subdistrict === 'string' ? building.subdistrict : (building.subdistrict.name || '');
                        if (subdistrictName && typeof subdistrictName === 'string' && subdistrictName !== 'true' && subdistrictName !== 'false' && subdistrictName.trim() !== '') {
                            locationParts.push(subdistrictName);
                        }
                    }
                    if (building.province) {
                        const provinceName = typeof building.province === 'string' ? building.province : (building.province.name || '');
                        if (provinceName && typeof provinceName === 'string' && provinceName !== 'true' && provinceName !== 'false' && provinceName.trim() !== '') {
                            locationParts.push(provinceName);
                        }
                    }
                    if (locationParts.length > 0) {
                        addressParts.push(locationParts.join(', '));
                    }
                    
                    // Kode pos - only add if it's a valid string and not boolean
                    const postalCode = building.kode_pos || building.postal_code;
                    if (postalCode && typeof postalCode === 'string' && postalCode !== 'true' && postalCode !== 'false' && postalCode.trim() !== '') {
                        addressParts.push(`Kode Pos: ${postalCode}`);
                    }
                    
                    // Phone
                    if (building.phone_1) {
                        addressParts.push(`Telp: ${building.phone_1}`);
                    }
                    if (building.phone_2) {
                        addressParts.push(`Telp 2: ${building.phone_2}`);
                    }
                    
                    // Email (if exists)
                    if (building.email) {
                        addressParts.push(`Email: ${building.email}`);
                    }
                    
                    // Set option text with full details
                    option.textContent = addressParts.join(' | ');
                    
                    // Store full building data as data attributes for later use
                    option.setAttribute('data-building-name', building.nama_gedung || building.name || '');
                    option.setAttribute('data-address', building.alamat_1 || building.address || '');
                    option.setAttribute('data-province', building.province || '');
                    option.setAttribute('data-city', building.city || '');
                    option.setAttribute('data-postal-code', building.kode_pos || building.postal_code || '');
                    option.setAttribute('data-phone', building.phone_1 || '');
                    option.setAttribute('data-email', building.email || '');
                    
                    // Restore selection state
                    if (currentSelectedValues.includes(building.id.toString())) {
                        option.selected = true;
                        option.setAttribute('selected', 'selected');
                        // Also Add to excludedIds for OTHER lists (Wait, no, if it's selected HERE, it should be excluded elsewhere, which is handled by the loop logic)
                    }

                    addressSelect.appendChild(option);
                });
                
                console.log(`Building filter result for address ${addressIndex}: ${shownCount} shown, ${hiddenCount} hidden`);
                
                // Double-check: Remove any options that somehow got through the filter
                // This is a safety measure in case options were added before filter executed
                // Run immediately and also after a delay to catch any race conditions
                const removeExcludedOptions = () => {
                    let removedCount = 0;
                    Array.from(addressSelect.options).forEach(opt => {
                        if (opt.value && opt.value !== '') {
                            const optBuildingId = parseInt(opt.value);
                            if (!isNaN(optBuildingId) && excludedIds.includes(optBuildingId)) {
                                opt.remove();
                                removedCount++;
                                console.log(`Removed existing option for building ${optBuildingId} - already selected in another billing group (safety check)`);
                            }
                        }
                    });
                    if (removedCount > 0) {
                        console.log(`Safety check: Removed ${removedCount} excluded options from address ${addressIndex}`);
                    }
                };
                
                // Run immediately
                removeExcludedOptions();
                
                // Also run after delays to catch any race conditions
                setTimeout(removeExcludedOptions, 50);
                setTimeout(removeExcludedOptions, 200);
                setTimeout(removeExcludedOptions, 500);
                setTimeout(removeExcludedOptions, 1000);
                
                // Remove existing change event listeners by cloning (if parentNode exists)
                // Otherwise, just remove old listeners and add new one
                let updatedAddressSelect = addressSelect;
                if (addressSelect.parentNode) {
                    try {
                        // Clone to remove all event listeners
                        const newAddressSelect = addressSelect.cloneNode(true);
                        addressSelect.parentNode.replaceChild(newAddressSelect, addressSelect);
                        // Get the updated reference
                        updatedAddressSelect = document.getElementById(`addressSelect_${addressIndex}`) || document.querySelector(`select[name="billing_addresses[${addressIndex}][address_id]"]`) || newAddressSelect;
                    } catch (e) {
                        console.warn('Could not replace addressSelect, using original:', e);
                        updatedAddressSelect = addressSelect;
                    }
                } else {
                    console.warn('addressSelect has no parentNode, using original element');
                    updatedAddressSelect = addressSelect;
                }
                
                // Remove any existing inline onchange handler
                if (updatedAddressSelect.hasAttribute('onchange')) {
                    updatedAddressSelect.removeAttribute('onchange');
                }
                
                // Add change event listener to validate duplicates and prevent selection of already selected buildings
                if (updatedAddressSelect) {
                    // Use { once: false } to allow multiple calls, but we'll check if listener already exists
                    const changeHandler = function(e) {
                        // Get currently selected building IDs from this address
                        const currentSelected = Array.from(this.selectedOptions)
                            .map(opt => parseInt(opt.value))
                            .filter(id => !isNaN(id) && id > 0);
                        
                        // Get all selected building IDs from other billing addresses
                        const allSelectedBuildingIds = [];
                        billingAddresses.forEach((addr, idx) => {
                            if (idx !== addressIndex && addr.element) {
                                const otherAddressSelect = addr.element.querySelector(`select[name="billing_addresses[${idx}][address_id]"]`);
                                if (otherAddressSelect && !otherAddressSelect.disabled) {
                                    Array.from(otherAddressSelect.selectedOptions).forEach(opt => {
                                        if (opt.value && opt.value !== '' && opt.value !== 'Pilih atau ketik disini...') {
                                            const buildingId = parseInt(opt.value);
                                            if (!isNaN(buildingId) && buildingId > 0) {
                                                allSelectedBuildingIds.push(buildingId);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                        
                        // Remove any buildings that are already selected in other addresses
                        let hasRemoved = false;
                        const removedBuildingIds = [];
                        
                        currentSelected.forEach(buildingId => {
                            if (allSelectedBuildingIds.includes(buildingId)) {
                                // Remove this option from selection AND from DOM
                                const optionToRemove = Array.from(this.options).find(opt => parseInt(opt.value) === buildingId);
                                if (optionToRemove) {
                                    optionToRemove.selected = false;
                                    // Also remove the option from DOM to prevent future selection
                                    optionToRemove.remove();
                                    hasRemoved = true;
                                    removedBuildingIds.push(buildingId);
                                    console.log(`Removed building ${buildingId} from address ${addressIndex} - already selected in another billing group`);
                                }
                            }
                        });
                        
                        if (hasRemoved) {
                            // Show warning
                            const duplicateWarning = document.getElementById(`duplicateWarning_${addressIndex}`);
                            if (duplicateWarning) {
                                duplicateWarning.textContent = `⚠️ Gedung dengan ID ${removedBuildingIds.join(', ')} sudah dipilih di billing group lain dan tidak bisa dipilih lagi.`;
                                duplicateWarning.style.display = 'block';
                                setTimeout(() => {
                                    duplicateWarning.style.display = 'none';
                                }, 5000);
                            }
                            
                            // Force UI update by triggering change event
                            setTimeout(() => {
                                this.dispatchEvent(new Event('change', { bubbles: true }));
                            }, 100);
                        }
                        
                        validateBuildingDuplicates(addressIndex);
                        
                        // Immediately remove any excluded buildings from DOM (double safety)
                        const currentExcludedIds = [];
                        billingAddresses.forEach((addr, idx) => {
                            if (idx !== addressIndex && addr && addr.element) {
                                const otherAddressSelect = addr.element.querySelector(`select[name="billing_addresses[${idx}][address_id]"]`) 
                                    || document.getElementById(`addressSelect_${idx}`);
                                if (otherAddressSelect && !otherAddressSelect.disabled) {
                                    Array.from(otherAddressSelect.selectedOptions || []).forEach(opt => {
                                        if (opt && opt.value && opt.value !== '' && opt.value !== 'Pilih atau ketik disini...') {
                                            const buildingId = parseInt(opt.value);
                                            if (!isNaN(buildingId) && buildingId > 0) {
                                                currentExcludedIds.push(buildingId);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                        
                        // Remove any excluded buildings from current select DOM
                        Array.from(this.options).forEach(opt => {
                            if (opt.value && opt.value !== '') {
                                const optBuildingId = parseInt(opt.value);
                                if (!isNaN(optBuildingId) && currentExcludedIds.includes(optBuildingId)) {
                                    opt.remove();
                                    console.log(`Removed building ${optBuildingId} from DOM - already selected in another billing group`);
                                }
                            }
                        });
                        
                        // Reload building lists for all other billing addresses to hide selected buildings
                        billingAddresses.forEach((addr, idx) => {
                            if (idx !== addressIndex && addr && addr.element) {
                                const otherAddressSelect = addr.element.querySelector(`select[name="billing_addresses[${idx}][address_id]"]`);
                                if (otherAddressSelect && !otherAddressSelect.disabled) {
                                    // Reload buildings for this address to hide selected buildings
                                    setTimeout(() => {
                                        openAddressModal(idx);
                                    }, 100);
                                }
                            }
                        });
                    };
                    
                    // Remove existing listener if any (by cloning)
                    if (updatedAddressSelect.parentNode) {
                        try {
                            const clonedSelect = updatedAddressSelect.cloneNode(true);
                            updatedAddressSelect.parentNode.replaceChild(clonedSelect, updatedAddressSelect);
                            updatedAddressSelect = document.getElementById(`addressSelect_${addressIndex}`) || document.querySelector(`select[name="billing_addresses[${addressIndex}][address_id]"]`) || clonedSelect;
                        } catch (e) {
                            // If replace fails, just use the original
                        }
                    }
                    
                    // Add the event listener
                    updatedAddressSelect.addEventListener('change', changeHandler);
                    
                    // Re-initialize Select2 if it was being used, but only after options are filtered
                    // Wait a bit to ensure all options are properly filtered
                    setTimeout(() => {
                        if (typeof $ !== 'undefined' && updatedAddressSelect && !updatedAddressSelect.disabled) {
                            // Check if Select2 was previously initialized
                            const wasSelect2 = addressSelect.classList.contains('select2-hidden-accessible') || 
                                             document.querySelector(`#select2-addressSelect_${addressIndex}-container`);
                            
                            // Re-run safety check one more time before initializing Select2
                            removeExcludedOptions();
                            
                            // Re-initialize Select2 if it was used before
                            if (wasSelect2 || document.querySelector(`#select2-addressSelect_${addressIndex}-container`)) {
                                try {
                                    $(updatedAddressSelect).select2({
                                        placeholder: 'Pilih atau ketik disini...',
                                        allowClear: true,
                                        width: '100%'
                                    });
                                    console.log('Re-initialized Select2 for addressSelect_' + addressIndex);
                                    
                                    // Add Select2 event listener to prevent selection of excluded buildings
                                    $(updatedAddressSelect).on('select2:select', function(e) {
                                        const selectedBuildingId = parseInt(e.params.data.id);
                                        if (!isNaN(selectedBuildingId)) {
                                            // Get all excluded building IDs
                                            const excludedIds = [];
                                            billingAddresses.forEach((addr, idx) => {
                                                if (idx !== addressIndex && addr && addr.element) {
                                                    const otherAddressSelect = addr.element.querySelector(`select[name="billing_addresses[${idx}][address_id]"]`) 
                                                        || document.getElementById(`addressSelect_${idx}`);
                                                    if (otherAddressSelect && !otherAddressSelect.disabled) {
                                                        Array.from(otherAddressSelect.selectedOptions || []).forEach(opt => {
                                                            if (opt && opt.value && opt.value !== '' && opt.value !== 'Pilih atau ketik disini...') {
                                                                const buildingId = parseInt(opt.value);
                                                                if (!isNaN(buildingId) && buildingId > 0) {
                                                                    excludedIds.push(buildingId);
                                                                }
                                                            }
                                                        });
                                                    }
                                                }
                                            });
                                            
                                            // If selected building is excluded, remove it
                                            if (excludedIds.includes(selectedBuildingId)) {
                                                // Remove the selection
                                                $(updatedAddressSelect).val($(updatedAddressSelect).val().filter(id => parseInt(id) !== selectedBuildingId)).trigger('change');
                                                
                                                // Remove the option from DOM
                                                const optionToRemove = Array.from(updatedAddressSelect.options).find(opt => parseInt(opt.value) === selectedBuildingId);
                                                if (optionToRemove) {
                                                    optionToRemove.remove();
                                                }
                                                
                                                console.log(`Prevented Select2 selection of building ${selectedBuildingId} - already selected in another billing group`);
                                                
                                                // Show warning
                                                const duplicateWarning = document.getElementById(`duplicateWarning_${addressIndex}`);
                                                if (duplicateWarning) {
                                                    duplicateWarning.textContent = `⚠️ Gedung ini sudah dipilih di billing group lain dan tidak bisa dipilih lagi.`;
                                                    duplicateWarning.style.display = 'block';
                                                    setTimeout(() => {
                                                        duplicateWarning.style.display = 'none';
                                                    }, 3000);
                                                }
                                            }
                                        }
                                    });
                                    
                                    // Final safety check after Select2 is initialized
                                    setTimeout(() => {
                                        removeExcludedOptions();
                                    }, 100);
                                } catch (e) {
                                    console.warn('Could not re-initialize Select2:', e);
                                }
                            }
                        }
                    }, 300);
                    
                    // Also add mousedown/click interceptor to prevent selection of excluded buildings
                    updatedAddressSelect.addEventListener('mousedown', function(e) {
                        // Get all excluded building IDs
                        const excludedIds = [];
                        billingAddresses.forEach((addr, idx) => {
                            if (idx !== addressIndex && addr && addr.element) {
                                const otherAddressSelect = addr.element.querySelector(`select[name="billing_addresses[${idx}][address_id]"]`) 
                                    || document.getElementById(`addressSelect_${idx}`);
                                if (otherAddressSelect && !otherAddressSelect.disabled) {
                                    Array.from(otherAddressSelect.selectedOptions || []).forEach(opt => {
                                        if (opt && opt.value && opt.value !== '' && opt.value !== 'Pilih atau ketik disini...') {
                                            const buildingId = parseInt(opt.value);
                                            if (!isNaN(buildingId) && buildingId > 0) {
                                                excludedIds.push(buildingId);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                        
                        // Check if the option being clicked is excluded
                        const target = e.target;
                        if (target.tagName === 'OPTION' && target.value) {
                            const clickedBuildingId = parseInt(target.value);
                            if (!isNaN(clickedBuildingId) && excludedIds.includes(clickedBuildingId)) {
                                e.preventDefault();
                                e.stopPropagation();
                                console.log(`Prevented selection of building ${clickedBuildingId} - already selected in another billing group`);
                                
                                // Show warning
                                const duplicateWarning = document.getElementById(`duplicateWarning_${addressIndex}`);
                                if (duplicateWarning) {
                                    duplicateWarning.textContent = `⚠️ Gedung ini sudah dipilih di billing group lain dan tidak bisa dipilih lagi.`;
                                    duplicateWarning.style.display = 'block';
                                    setTimeout(() => {
                                        duplicateWarning.style.display = 'none';
                                    }, 3000);
                                }
                                
                                return false;
                            }
                        }
                    });
                }
                
                // Update total available buildings count and button visibility
                totalAvailableBuildings = buildings.length;
                updateAddBillingAddressButton();
                
                console.log('Customer buildings loaded successfully with full details');
            } else {
                addressSelect.innerHTML = '<option value="">Tidak ada building tersedia untuk customer ini</option>';
                totalAvailableBuildings = 0;
                updateAddBillingAddressButton();
                console.log('No buildings found for customer');
            }
        })
        .catch(error => {
            console.error('Error loading customer buildings:', error);
            addressSelect.innerHTML = '<option value="">Error loading buildings</option>';
        });
    } else {
        console.log('No quotation data or customer found');
        addressSelect.innerHTML = '<option value="">Pilih quotation terlebih dahulu</option>';
    }
}

function addBillingAddress() {
    const billingAddressesDiv = document.getElementById('billingAddresses');
    const addressIndex = billingAddresses.length;
    
    const addressDiv = document.createElement('div');
    addressDiv.className = 'billing-address';
    addressDiv.innerHTML = `
        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
            <h5 class="text-primary font-weight-bold m-0"><i class="fas fa-layer-group mr-2"></i>Billing Group ${addressIndex + 1}</h5>
        </div>
        <div class="form-group mb-3" style="display: none;">
            <label class="form-label">Pilih Billing Group</label>
            <div class="flex gap-2">
                <select name="billing_addresses[${addressIndex}][billing_group_id]" id="billingGroupSelect_${addressIndex}" class="form-control" onchange="handleBillingGroupChange(${addressIndex})">
                    <option value="">Buat Billing Group Baru</option>
                </select>
                <button type="button" class="btn btn-add" onclick="loadReusableBillingGroups(${addressIndex})" title="Load Billing Groups dari Contract Lain">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <small class="text-gray-500 text-xs mt-1 block">Pilih billing group yang sudah ada (reuse) atau buat baru</small>
        </div>
        <div id="billingGroupInfo_${addressIndex}" style="display: none;" class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded">
            <div class="text-sm">
                <strong>Billing Group yang dipilih:</strong> <span id="selectedBillingGroupName_${addressIndex}"></span><br>
                <strong>Buildings:</strong> <span id="selectedBillingGroupBuildings_${addressIndex}"></span>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
                <label class="form-label">NIK/TAX/NPWP</label>
                <div class="flex">
                    <select name="billing_addresses[${addressIndex}][tax_id]" id="taxSelect_${addressIndex}" class="form-control" onchange="handleTaxSelection(${addressIndex})">
                        <option value="">Pilih data pajak...</option>
                    </select>
                    <button type="button" class="btn btn-add ml-2" onclick="openAddTaxModal(${addressIndex})" title="Add New Tax Data">
                        <i class="fas fa-plus"></i>
                    </button>
                    <input type="hidden" name="billing_addresses[${addressIndex}][tax_display]" id="taxDisplay_${addressIndex}">
                    <input type="hidden" name="billing_addresses[${addressIndex}][npwp]" id="taxNpwp_${addressIndex}">
                    <input type="hidden" name="billing_addresses[${addressIndex}][nitku]" id="taxNitku_${addressIndex}">
                    <input type="hidden" name="billing_addresses[${addressIndex}][nik]" id="taxNik_${addressIndex}">
                    <input type="hidden" name="billing_addresses[${addressIndex}][tax_address]" id="taxAddress_${addressIndex}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Billing Address (Buildings)</label>
                <div class="flex">
                    <select name="billing_addresses[${addressIndex}][address_id]" id="addressSelect_${addressIndex}" class="form-control" multiple onchange="validateBuildingDuplicates(${addressIndex})">
                        <option value="">Pilih atau ketik disini...</option>
                    </select>
                    <button type="button" class="btn btn-add ml-2" onclick="openAddressModal(${addressIndex})" title="Reload Building Data">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <small class="text-gray-500 text-xs mt-1 block">Pilih satu atau lebih building (Ctrl+Click untuk multiple selection)</small>
                <div id="duplicateWarning_${addressIndex}" class="text-red-500 text-xs mt-1" style="display: none;"></div>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
                <label class="form-label">PIC Finance</label>
                <div class="flex">
                    <select name="billing_addresses[${addressIndex}][pic_finance]" class="form-control" onchange="updateBillingEmailFromPICFinance(${addressIndex})">
                        <option value="">Pilih atau ketik disini...</option>
                    </select>
                    <button type="button" class="btn btn-add ml-2" onclick="openClientContactModal()" title="Add New Contact">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Bank Payment</label>
                <select name="billing_addresses[${addressIndex}][bank_payment]" class="form-control">
                    <option value="">Pilih atau ketik disini...</option>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
                <label class="form-label">Invoice Receipt By</label>
                <select name="billing_addresses[${addressIndex}][invoice_receipt]" class="form-control">
                    <option value="">Pilih...</option>
                    <option value="soft_copy">Soft copy</option>
                    <option value="hard_copy">Hard copy</option>
                    <option value="both">Both</option>
                    <option value="manual">Manual</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Wajib Pungut?</label>
                <select name="billing_addresses[${addressIndex}][mandatory_tax]" class="form-control">
                    <option value="no">No</option>
                    <option value="yes">Yes</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" class="btn btn-remove" onclick="removeBillingAddress(${addressIndex})">
                <i class="fas fa-trash mr-1"></i>Remove
            </button>
        </div>
    `;
    
    billingAddressesDiv.appendChild(addressDiv);
    
    // Create billing address object with form references
    const billingAddress = {
        company_name: '',
        address: '',
        billing_email: '',
        tax_id: '',
        address_id: '',
        pic_finance: '',
        bank_payment: '',
        pic_finance_name: '',
        bank_payment_name: '',
        element: addressDiv
    };
    
    billingAddresses.push(billingAddress);
    
    // Load data for the new billing address
    loadPICFinanceContacts(addressIndex, addressDiv.querySelector('select[name*="[pic_finance]"]'));
    populateTaxDropdown(addressIndex);
    
    // Auto-select Default Bank Payment
    const defaultBankPaymentId = quotationData?.customer?.default_bank_payment_id || '';
    loadBankPayments(addressDiv.querySelector('select[name*="[bank_payment]"]'), defaultBankPaymentId);
    
    // Load reusable billing groups if customer is available
    if (quotationData && quotationData.customer && quotationData.customer.id) {
        loadReusableBillingGroups(addressIndex);
        // Load buildings for the new billing address with filter (hide buildings already selected in other addresses)
        setTimeout(() => {
            openAddressModal(addressIndex);
        }, 200);
    }
    
    // Update button visibility after adding new billing address
    setTimeout(() => {
        updateAddBillingAddressButton();
    }, 100);
}

// Load reusable billing groups from other contracts (same customer)
function loadReusableBillingGroups(addressIndex) {
    if (!quotationData || !quotationData.customer || !quotationData.customer.id) {
        console.log('No customer data available');
        return;
    }
    
    const billingGroupSelect = document.getElementById(`billingGroupSelect_${addressIndex}`);
    if (!billingGroupSelect) return;
    
    fetch(`/marketing/contracts/wizard/get-reusable-billing-groups?customer_id=${quotationData.customer.id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(billingGroups => {
        // Clear existing options except the first one
        billingGroupSelect.innerHTML = '<option value="">Buat Billing Group Baru</option>';
        
        if (billingGroups && billingGroups.length > 0) {
            billingGroups.forEach(bg => {
                const option = document.createElement('option');
                option.value = bg.id;
                const buildingNames = bg.buildings.map(b => b.name).join(', ');
                option.textContent = `${bg.billing_group_name} (${bg.buildings.length} building${bg.buildings.length > 1 ? 's' : ''}: ${buildingNames})`;
                option.setAttribute('data-buildings', JSON.stringify(bg.buildings));
                billingGroupSelect.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Error loading reusable billing groups:', error);
    });
}

// Handle billing group selection change
function handleBillingGroupChange(addressIndex) {
    const billingGroupSelect = document.getElementById(`billingGroupSelect_${addressIndex}`);
    const billingGroupInfo = document.getElementById(`billingGroupInfo_${addressIndex}`);
    const addressSelect = document.getElementById(`addressSelect_${addressIndex}`);
    
    if (!billingGroupSelect || !billingGroupInfo || !addressSelect) return;
    
    const selectedOption = billingGroupSelect.options[billingGroupSelect.selectedIndex];
    
    if (billingGroupSelect.value && selectedOption) {
        // Reuse existing billing group
        const buildings = JSON.parse(selectedOption.getAttribute('data-buildings') || '[]');
        
        // Show billing group info
        document.getElementById(`selectedBillingGroupName_${addressIndex}`).textContent = selectedOption.textContent;
        document.getElementById(`selectedBillingGroupBuildings_${addressIndex}`).textContent = buildings.map(b => b.name).join(', ');
        billingGroupInfo.style.display = 'block';
        
        // Disable building selection (using existing billing group)
        addressSelect.disabled = true;
        addressSelect.innerHTML = '<option value="">Billing Group ini sudah memiliki buildings</option>';
    } else {
        // Create new billing group
        billingGroupInfo.style.display = 'none';
        addressSelect.disabled = false;
        addressSelect.innerHTML = '<option value="">Pilih atau ketik disini...</option>';
        // Reload buildings
        openAddressModal(addressIndex);
    }
    
    // Re-validate duplicates
    validateBuildingDuplicates(addressIndex);
}

// Validate building duplicates across billing groups
function validateBuildingDuplicates(addressIndex) {
    const addressSelect = document.getElementById(`addressSelect_${addressIndex}`);
    const duplicateWarning = document.getElementById(`duplicateWarning_${addressIndex}`);
    
    if (!addressSelect || !duplicateWarning) return;
    
    // Get all selected buildings from this billing address
    const selectedBuildings = Array.from(addressSelect.selectedOptions)
        .map(option => option.value)
        .filter(val => val !== '');
    
    if (selectedBuildings.length === 0) {
        duplicateWarning.style.display = 'none';
        return;
    }
    
    // Check for duplicates in other billing addresses
    const allSelectedBuildings = [];
    const duplicateBuildings = [];
    
    billingAddresses.forEach((address, idx) => {
        if (idx === addressIndex) return; // Skip current address
        
        const otherSelect = document.getElementById(`addressSelect_${idx}`);
        if (!otherSelect) return;
        
        const otherSelected = Array.from(otherSelect.selectedOptions)
            .map(option => option.value)
            .filter(val => val !== '');
        
        otherSelected.forEach(buildingId => {
            if (allSelectedBuildings.includes(buildingId)) {
                duplicateBuildings.push(buildingId);
            } else {
                allSelectedBuildings.push(buildingId);
            }
        });
    });
    
    // Check if current selection has duplicates
    selectedBuildings.forEach(buildingId => {
        if (allSelectedBuildings.includes(buildingId)) {
            duplicateBuildings.push(buildingId);
        }
    });
    
    if (duplicateBuildings.length > 0) {
        duplicateWarning.textContent = `⚠️ Building dengan ID ${duplicateBuildings.join(', ')} sudah dipilih di billing address lain. Satu building tidak boleh ada di lebih dari satu billing group.`;
        duplicateWarning.style.display = 'block';
    } else {
        duplicateWarning.style.display = 'none';
    }
}

function removeBillingAddress(index) {
    const billingAddressesDiv = document.getElementById('billingAddresses');
    const addressDiv = billingAddressesDiv.children[index];
    if (addressDiv) {
        addressDiv.remove();
        billingAddresses.splice(index, 1);
    }
    
    // Update button visibility after removing billing address
    updateAddBillingAddressButton();
    
    // If no billing addresses left, add one automatically
    if (billingAddresses.length === 0) {
        addBillingAddress();
    }
}

// Load PIC Finance contacts (customer contacts) for billing address
function loadPICFinanceContacts(addressIndex, selectElement) {
    if (!selectElement) return;
    
    // Get customer ID from quotation data
    if (!quotationData || !quotationData.customer || !quotationData.customer.id) {
        selectElement.innerHTML = '<option value="">Pilih quotation terlebih dahulu</option>';
        return;
    }
    
    const customerId = quotationData.customer.id;
    
    // Load customer contacts from API
    fetchCustomerContactsCached(customerId)
    .then(contacts => {
        selectElement.innerHTML = '<option value="">Pilih atau ketik disini...</option>';

        if (contacts.length > 0) {
            contacts.forEach(contact => {
                // Only show active contacts
                if (contact.is_active !== false) {
                    const option = document.createElement('option');
                    option.value = contact.id;
                    option.textContent = contact.name + (contact.position ? ` - ${contact.position}` : '');
                    // Store contact data as data attributes for later use
                    option.setAttribute('data-contact-email', contact.email || '');
                    option.setAttribute('data-contact-phone', contact.phone || '');
                    selectElement.appendChild(option);
                }
            });
            
            // Add change event listener to update billing email when PIC Finance is selected
            selectElement.addEventListener('change', function() {
                updateBillingEmailFromPICFinance(addressIndex);
            });
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Tidak ada contact untuk customer ini';
            option.disabled = true;
            selectElement.appendChild(option);
        }
    })
    .catch(error => {
        console.error('Error loading customer contacts:', error);
        selectElement.innerHTML = '<option value="">Error loading contacts</option>';
    });
}

// Open PIC Finance modal (reload customer contacts)
function openPICFinanceModal(addressIndex) {
    const selectElement = document.querySelector(`select[name="billing_addresses[${addressIndex}][pic_finance]"]`);
    if (selectElement) {
        loadPICFinanceContacts(addressIndex, selectElement);
    }
}

function loadBankPayments(selectElement, defaultId = null) {
    if (!selectElement) return;
    
    // Load bank payments from contract wizard API so it does not depend on Company menu permission.
    fetch('/marketing/contracts/wizard/get-bank-payments', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.bankPayments) {
                selectElement.innerHTML = '<option value="">Pilih atau ketik disini...</option>';
                data.bankPayments.forEach(payment => {
                    const option = document.createElement('option');
                    option.value = payment.id;
                    option.textContent = `${payment.bank?.name || 'Bank'} - ${payment.account_name} (${payment.account_number})`;
                    selectElement.appendChild(option);
                });
                
                // Auto-select default if provided
                if (defaultId) {
                    selectElement.value = defaultId;
                }
            } else {
                selectElement.innerHTML = '<option value="">No bank payments available</option>';
            }
        })
        .catch(error => {
            console.error('Error loading bank payments:', error);
            selectElement.innerHTML = '<option value="">Error loading bank payments</option>';
        });
}

// Legacy function - redirect to new function
function openFinanceUserModal() {
    // This is now handled by openPICFinanceModal per address index
    // But for backward compatibility, reload all PIC Finance contacts
    billingAddresses.forEach((address, index) => {
        const selectElement = address.element?.querySelector('select[name*="[pic_finance]"]');
        if (selectElement) {
            loadPICFinanceContacts(index, selectElement);
        }
    });
}

function loadBillingAddresses() {
    // Auto-create first billing address if none exists
    if (billingAddresses.length === 0) {
        addBillingAddress();
    }
    
    // Load and count available buildings, then update button visibility
    loadAndCountAvailableBuildings();
}

// Store total available buildings count
let totalAvailableBuildings = 0;

// Update "Add Billing Address" button visibility based on number of buildings
function updateAddBillingAddressButton() {
    const addButton = document.getElementById('addBillingAddressBtn');
    const infoText = document.getElementById('billingAddressInfo');
    
    if (!addButton || !infoText) return;
    
    // If we have the total available buildings count, use it
    // Otherwise, try to count from address selects
    let buildingCount = totalAvailableBuildings;
    
    if (buildingCount === 0) {
        // Fallback: Count from address selects if available
        billingAddresses.forEach((address, index) => {
            const addressSelect = document.getElementById(`addressSelect_${index}`);
            if (addressSelect && !addressSelect.disabled) {
                const availableOptions = Array.from(addressSelect.options)
                    .filter(option => option.value !== '');
                buildingCount = Math.max(buildingCount, availableOptions.length);
            }
        });
    }
    
    // Show button if:
    // 1. There are more than 1 building available, OR
    // 2. There are multiple billing addresses already created
    if (buildingCount > 1 || billingAddresses.length > 1) {
        addButton.style.display = 'inline-flex';
        infoText.style.display = 'block';
    } else {
        // Only 1 building and only 1 billing address - hide button
        addButton.style.display = 'none';
        infoText.style.display = 'none';
    }
}

// Load and count available buildings from customer
function loadAndCountAvailableBuildings() {
    if (!quotationData || !quotationData.customer || !quotationData.customer.id) {
        totalAvailableBuildings = 0;
        updateAddBillingAddressButton();
        return;
    }
    
    fetchCustomerBuildingsCached(quotationData.customer.id)
    .then(buildings => {
        totalAvailableBuildings = buildings && Array.isArray(buildings) ? buildings.length : 0;
        updateAddBillingAddressButton();
    })
    .catch(error => {
        console.error('Error loading buildings count:', error);
        totalAvailableBuildings = 0;
        updateAddBillingAddressButton();
    });
}

function updateBillingAddressData() {
    // Update billing addresses data from form inputs
    billingAddresses.forEach((address, index) => {
        const element = address.element;
        if (element) {
            // Company name from quotation data (always from quotation)
            address.company_name = quotationData?.customer?.name || '';
            
            // Billing group ID (for reuse) is hidden/removed, so we default to empty/new
            address.billing_group_id = '';
            
            let selectedBuildings = [];
            
            // ALWAYS read from building select input (address_id)
            // Priority 1: If Reuse Billing Group is select... REMOVED as per request.
            // Priority 2: Read from building select
            
            // Priority 2: If no buildings yet (New Group mode), read from building select
            if (selectedBuildings.length === 0) {
                const addressSelect = element.querySelector('select[name*="[address_id]"]');
                if (addressSelect && !addressSelect.disabled) {
                    // Get all selected building IDs
                    // Using jQuery to get Select2 values if available, otherwise native
                    if ($(addressSelect).hasClass('select2-hidden-accessible')) {
                        selectedBuildings = $(addressSelect).val() || [];
                    } else {
                         selectedBuildings = Array.from(addressSelect.selectedOptions)
                            .map(option => option.value)
                            .filter(val => val !== '');
                    }
                    
                    address.buildings = selectedBuildings;
                    console.log(`Address ${index} selected buildings from input:`, selectedBuildings);
                    
                    // Get building names for display
                    if (selectedBuildings.length > 0) {
                        const selectedOptions = Array.from(addressSelect.options)
                            .filter(opt => selectedBuildings.includes(opt.value));
                            
                         address.address = selectedOptions
                            .map(option => option.textContent.trim())
                            .filter(text => text !== 'Pilih atau ketik disini...')
                            .join(', ');
                    } else {
                        address.address = '';
                    }
                }
            }
            
            // Get tax display (NIK/NITKU/NPWP) from step 4
            const taxDisplayInput = element.querySelector('input[name*="[tax_display]"]');
            if (taxDisplayInput) {
                address.tax_display = taxDisplayInput.value || '';
            }
            
            address.tax_id = element.querySelector('input[name*="[tax_id]"]')?.value || '';
            
            // Capture PIC Finance and Email
            const picFinanceSelect = element.querySelector('select[name*="[pic_finance]"]');
            address.pic_finance = picFinanceSelect?.value || '';
            if (picFinanceSelect && picFinanceSelect.selectedOptions.length > 0) {
                address.pic_finance_name = picFinanceSelect.selectedOptions[0].textContent;
                // Capture email from data attribute
                const contactEmail = picFinanceSelect.selectedOptions[0].getAttribute('data-contact-email');
                address.billing_email = contactEmail || '';
            } else {
                address.pic_finance_name = '';
                address.billing_email = '';
            }

            // Capture Bank Payment
            const bankPaymentSelect = element.querySelector('select[name*="[bank_payment]"]');
            address.bank_payment = bankPaymentSelect?.value || '';
            if (bankPaymentSelect && bankPaymentSelect.selectedOptions.length > 0) {
                address.bank_payment_name = bankPaymentSelect.selectedOptions[0].textContent;
            } else {
                 address.bank_payment_name = '';
            }
        }
    });
}

// Step 5: Building selection (Now Summary View)
function loadBuildingSelection() {
    // Update billing address data to ensure we have latest from Step 4
    updateBillingAddressData();
    
    const buildingSelectionDiv = document.getElementById('buildingSelection');
    
    if (billingAddresses.length === 0) {
        buildingSelectionDiv.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                No billing addresses found. Please go back to Step 4 and add at least one billing address.
            </div>
        `;
        return;
    }
    
    // Check for customer data
    if (!quotationData || !quotationData.customer) {
        buildingSelectionDiv.innerHTML = `
             <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle mr-2"></i> No customer data found attached to quotation.
             </div>`;
         return;
    }
    
    let content = '';
    
    billingAddresses.forEach((address, index) => {
        // Display Summary Card
        content += `
            <div class="billing-address-section mb-6 p-4 border rounded-lg bg-white shadow-sm">
                <div class="bg-dark text-white p-3 mb-4 rounded flex justify-between items-center shadow-lg" style="background-color: #343a40 !important;">
                    <h5 class="font-bold text-lg m-0 text-white">Billing Group ${index + 1}</h5>
                    <span class="badge badge-light text-dark">Summary</span>
                </div>
                
                <!-- Billing Address Info (Readonly) -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200">
                    <div class="space-y-2">
                        <div class="flex items-start">
                            <div class="w-1/3 text-sm font-medium text-gray-700 pr-4">Nama Perusahaan</div>
                            <div class="w-2/3 text-sm text-gray-900 font-bold">: ${quotationData?.customer?.name || '-'}</div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-1/3 text-sm font-medium text-gray-700 pr-4">PIC Finance</div>
                            <div class="w-2/3 text-sm text-gray-900">: ${address.pic_finance_name || '-'}</div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-1/3 text-sm font-medium text-gray-700 pr-4">Billing E-mail</div>
                            <div class="w-2/3 text-sm text-gray-900">: ${address.billing_email || '-'}</div>
                        </div>
                        <div class="flex items-start">
                            <div class="w-1/3 text-sm font-medium text-gray-700 pr-4">Bank Payment</div>
                            <div class="w-2/3 text-sm text-gray-900">: ${address.bank_payment_name || '-'}</div>
                        </div>
                         <div class="flex items-start">
                            <div class="w-1/3 text-sm font-medium text-gray-700 pr-4">Tax Info</div>
                            <div class="w-2/3 text-sm text-gray-900">: ${address.tax_display || '-'}</div>
                        </div>
                    </div>
                </div>
                
                <!-- Selected Buildings List -->
                <div class="mt-4">
                    <label class="form-label font-semibold mb-2 border-b pb-1 block">Gedung / Cabang Terdaftar dalam Group Ini</label>
                    <div class="bg-blue-50 p-3 rounded border border-blue-100">
                        ${address.address ? 
                            `<div class="text-sm text-gray-800 leading-relaxed"><i class="fas fa-building mr-2 text-blue-500"></i>${address.address}</div>` : 
                            '<div class="text-gray-500 text-sm italic">Belum ada gedung yang dipilih di Step 4.</div>'
                        }
                    </div>
                     <div class="mt-2 text-xs text-gray-500 text-right">
                        *Untuk mengubah data, silakan kembali ke Step 4.
                    </div>
                </div>
            </div>
        `;
    });
    
    buildingSelectionDiv.innerHTML = content;
}

// Store selected buildings for each billing address
const selectedBuildingsByAddress = {};

function loadBuildingsForAddress(addressIndex) {
    const availableBuildingsDiv = document.getElementById(`availableBuildings_${addressIndex}`);
    if (!availableBuildingsDiv) return;
    
    // Initialize selected buildings array for this address
    if (!selectedBuildingsByAddress[addressIndex]) {
        selectedBuildingsByAddress[addressIndex] = [];
    }
    
    // Get buildings that were selected in step 4 for this billing address
    const address = billingAddresses[addressIndex];
    if (!address || !address.element) {
        availableBuildingsDiv.innerHTML = `
            <div class="col-span-full text-center py-4 text-red-500">
                <i class="fas fa-exclamation-circle mr-2"></i>Billing address data not found
            </div>
        `;
        return;
    }
    
    // Get selected buildings from step 4 (from addressSelect)
    const addressSelect = address.element.querySelector(`select[name="billing_addresses[${addressIndex}][address_id]"]`);
    if (!addressSelect) {
        availableBuildingsDiv.innerHTML = `
            <div class="col-span-full text-center py-4 text-red-500">
                <i class="fas fa-exclamation-circle mr-2"></i>Address select not found
            </div>
        `;
        return;
    }
    
    // Get selected building IDs from step 4
    const selectedBuildingIds = Array.from(addressSelect.selectedOptions)
        .map(opt => parseInt(opt.value))
        .filter(id => !isNaN(id) && id > 0);
    
    if (selectedBuildingIds.length === 0) {
        availableBuildingsDiv.innerHTML = `
            <div class="col-span-full text-center py-4 text-yellow-500">
                <i class="fas fa-exclamation-triangle mr-2"></i>Belum ada gedung yang dipilih di Step 4. Silakan kembali ke Step 4 untuk memilih gedung terlebih dahulu.
            </div>
        `;
        return;
    }
    
    // Load buildings only from customer (associated buildings)
    if (!quotationData || !quotationData.customer || !quotationData.customer.id) {
        availableBuildingsDiv.innerHTML = `
            <div class="col-span-full text-center py-4 text-red-500">
                <i class="fas fa-exclamation-circle mr-2"></i>Customer data not found
            </div>
        `;
        return;
    }
    
    fetchCustomerBuildingsCached(quotationData.customer.id)
    .then(buildings => {
        // Handle both array response and object response
        let allBuildings = [];
        if (Array.isArray(buildings)) {
            allBuildings = buildings;
        } else if (buildings.status === 'success' && buildings.data && Array.isArray(buildings.data)) {
            allBuildings = buildings.data;
        } else if (buildings.status === 'success' && buildings.data && buildings.data.data && Array.isArray(buildings.data.data)) {
            allBuildings = buildings.data.data;
        }
        
        // Filter to only show buildings that were selected in step 4
        const filteredBuildings = allBuildings.filter(building => selectedBuildingIds.includes(building.id));
        
        if (!filteredBuildings || filteredBuildings.length === 0) {
            availableBuildingsDiv.innerHTML = `
                <div class="col-span-full text-center py-4 text-yellow-500">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Belum ada gedung yang dipilih di Step 4 untuk billing address ini. Silakan kembali ke Step 4 untuk memilih gedung terlebih dahulu.
                </div>
            `;
            return;
        }
        
        availableBuildingsDiv.innerHTML = '';
        
        // Store full building data for selected buildings from step 4
        filteredBuildings.forEach(building => {
            // Initialize selectedBuildingsByAddress with buildings from step 4 if not already set
            if (!selectedBuildingsByAddress[addressIndex].some(b => b.id === building.id)) {
                selectedBuildingsByAddress[addressIndex].push(building);
            }
        });
        
        filteredBuildings.forEach(building => {
            // Check if building is already selected in this address (for toggle functionality)
            const isSelected = selectedBuildingsByAddress[addressIndex].some(b => b.id === building.id);
            
            // Build address parts
            const addressParts = [];
            if (building.alamat_1 || building.address) addressParts.push(building.alamat_1 || building.address);
            if (building.alamat_2) addressParts.push(building.alamat_2);
            
            const locationParts = [];
            if (building.city) {
                const cityName = typeof building.city === 'string' ? building.city : (building.city.name || '');
                if (cityName && typeof cityName === 'string' && cityName !== 'true' && cityName !== 'false' && cityName.trim() !== '') {
                    locationParts.push(cityName);
                }
            }
            if (building.district) {
                const districtName = typeof building.district === 'string' ? building.district : (building.district.name || '');
                if (districtName && typeof districtName === 'string' && districtName !== 'true' && districtName !== 'false' && districtName.trim() !== '') {
                    locationParts.push(districtName);
                }
            }
            if (building.subdistrict) {
                const subdistrictName = typeof building.subdistrict === 'string' ? building.subdistrict : (building.subdistrict.name || '');
                if (subdistrictName && typeof subdistrictName === 'string' && subdistrictName !== 'true' && subdistrictName !== 'false' && subdistrictName.trim() !== '') {
                    locationParts.push(subdistrictName);
                }
            }
            if (building.province) {
                const provinceName = typeof building.province === 'string' ? building.province : (building.province.name || '');
                if (provinceName && typeof provinceName === 'string' && provinceName !== 'true' && provinceName !== 'false' && provinceName.trim() !== '') {
                    locationParts.push(provinceName);
                }
            }
            if (locationParts.length > 0) addressParts.push(locationParts.join(', '));
            
            const postalCode = building.kode_pos || building.postal_code;
            if (postalCode && typeof postalCode === 'string' && postalCode !== 'true' && postalCode !== 'false' && postalCode.trim() !== '') {
                addressParts.push(postalCode);
            }
            
            const buildingCard = document.createElement('div');
            buildingCard.className = `building-card p-4 border rounded-lg cursor-pointer transition-all ${isSelected ? 'bg-blue-50 border-blue-300' : 'bg-white border-gray-200 hover:border-blue-300 hover:shadow-md'}`;
            buildingCard.innerHTML = `
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h6 class="font-semibold text-gray-800 mb-1">${building.nama_gedung || building.name || 'Unnamed Building'}</h6>
                        <p class="text-xs text-gray-600">${addressParts.join(', ')}</p>
                    </div>
                    ${isSelected ? '<i class="fas fa-check-circle text-blue-500 ml-2"></i>' : ''}
                </div>
            `;
            
            // Store building data in the card for later reference
            buildingCard.setAttribute('data-building-id', building.id);
            
            buildingCard.addEventListener('click', function(e) {
                e.stopPropagation();
                // Get current selection state
                const currentlySelected = selectedBuildingsByAddress[addressIndex]?.some(b => b.id === building.id) || false;
                
                // Toggle building selection
                if (currentlySelected) {
                    // Unselect building
                    removeBuildingFromAddress(addressIndex, building.id);
                } else {
                    // Select building
                    selectBuildingForAddress(addressIndex, building);
                }
            });
            
            availableBuildingsDiv.appendChild(buildingCard);
        });
    })
    .catch(error => {
        console.error('Error loading buildings:', error);
        availableBuildingsDiv.innerHTML = `
            <div class="col-span-full text-center py-4 text-red-500">
                <i class="fas fa-exclamation-circle mr-2"></i>Error loading buildings
            </div>
        `;
    });
}

function selectBuildingForAddress(addressIndex, building) {
    // Check if building is already selected
    if (selectedBuildingsByAddress[addressIndex].some(b => b.id === building.id)) {
        return;
    }
    
    // Add building to selected list
    selectedBuildingsByAddress[addressIndex].push(building);
    
    // Update billing address data with selected buildings
    updateBillingAddressDataWithSelectedBuildings();
    
    // Update billing email display in step 5 if already loaded
    updateBillingEmailDisplay(addressIndex);
    
    // Update UI for current address
    updateSelectedBuildingsDisplay(addressIndex);
    updateAvailableBuildingsDisplay(addressIndex);
    
    // Update all other addresses to hide this building
    billingAddresses.forEach((address, idx) => {
        if (idx !== addressIndex) {
            updateAvailableBuildingsDisplay(idx);
        }
    });
}

// Update billing email from step 4 building selection
// Update billing email from PIC Finance (customer contact) selection
function updateBillingEmailFromPICFinance(addressIndex) {
    const address = billingAddresses[addressIndex];
    if (!address || !address.element) return;
    
    const picFinanceSelect = address.element.querySelector('select[name*="[pic_finance]"]');
    if (picFinanceSelect && picFinanceSelect.selectedOptions.length > 0) {
        const selectedOption = picFinanceSelect.selectedOptions[0];
        const contactEmail = selectedOption.getAttribute('data-contact-email');
        if (contactEmail && contactEmail.trim() !== '') {
            address.billing_email = contactEmail;
        } else {
            address.billing_email = '';
        }
    } else {
        address.billing_email = '';
    }
    
    // Update billing email display in step 5 if already loaded
    updateBillingEmailDisplay(addressIndex);
}

// Update billing email display in step 5
function updateBillingEmailDisplay(addressIndex) {
    // Find the billing email display element in step 5
    const billingEmailValue = document.getElementById(`billing_email_value_${addressIndex}`);
    if (billingEmailValue) {
        const emailValue = billingAddresses[addressIndex]?.billing_email || '-';
        billingEmailValue.textContent = `: ${emailValue}`;
    }
}

function removeBuildingFromAddress(addressIndex, buildingId) {
    // Remove building from selected list
    selectedBuildingsByAddress[addressIndex] = selectedBuildingsByAddress[addressIndex].filter(b => b.id !== buildingId);
    
    // Update billing address data with selected buildings
    updateBillingAddressDataWithSelectedBuildings();
    
    // Update billing email display in step 5 if already loaded
    updateBillingEmailDisplay(addressIndex);
    
    // Update UI for current address
    updateSelectedBuildingsDisplay(addressIndex);
    updateAvailableBuildingsDisplay(addressIndex);
    
    // Update all other addresses to show this building again (if not selected elsewhere)
    billingAddresses.forEach((address, idx) => {
        if (idx !== addressIndex) {
            updateAvailableBuildingsDisplay(idx);
        }
    });
}

function updateSelectedBuildingsDisplay(addressIndex) {
    const selectedBuildingsDiv = document.getElementById(`selectedBuildings_${addressIndex}`);
    if (!selectedBuildingsDiv) return;
    
    const selectedBuildings = selectedBuildingsByAddress[addressIndex] || [];
    
    if (selectedBuildings.length === 0) {
        selectedBuildingsDiv.innerHTML = '<p class="text-gray-500 text-sm italic">Belum ada gedung yang dipilih</p>';
        return;
    }
    
    let content = '';
    selectedBuildings.forEach(building => {
        // Build full address
        const addressParts = [];
        if (building.alamat_1 || building.address) addressParts.push(building.alamat_1 || building.address);
        if (building.alamat_2) addressParts.push(building.alamat_2);
        
        const locationParts = [];
        if (building.city) {
            const cityName = typeof building.city === 'string' ? building.city : (building.city.name || '');
            if (cityName && typeof cityName === 'string' && cityName !== 'true' && cityName !== 'false' && cityName.trim() !== '') {
                locationParts.push(cityName);
            }
        }
        if (building.district) {
            const districtName = typeof building.district === 'string' ? building.district : (building.district.name || '');
            if (districtName && typeof districtName === 'string' && districtName !== 'true' && districtName !== 'false' && districtName.trim() !== '') {
                locationParts.push(districtName);
            }
        }
        if (building.subdistrict) {
            const subdistrictName = typeof building.subdistrict === 'string' ? building.subdistrict : (building.subdistrict.name || '');
            if (subdistrictName && typeof subdistrictName === 'string' && subdistrictName !== 'true' && subdistrictName !== 'false' && subdistrictName.trim() !== '') {
                locationParts.push(subdistrictName);
            }
        }
        if (building.province) {
            const provinceName = typeof building.province === 'string' ? building.province : (building.province.name || '');
            if (provinceName && typeof provinceName === 'string' && provinceName !== 'true' && provinceName !== 'false' && provinceName.trim() !== '') {
                locationParts.push(provinceName);
            }
        }
        if (locationParts.length > 0) addressParts.push(locationParts.join(', '));
        
        const postalCode = building.kode_pos || building.postal_code;
        if (postalCode && typeof postalCode === 'string' && postalCode !== 'true' && postalCode !== 'false' && postalCode.trim() !== '') {
            addressParts.push(postalCode);
        }
        
        content += `
            <div class="selected-building-card p-4 border border-gray-200 rounded-lg bg-white relative">
                <button type="button" 
                        onclick="removeBuildingFromAddress(${addressIndex}, ${building.id})" 
                        class="absolute top-2 right-2 w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition-colors"
                        title="Hapus Gedung">
                    <i class="fas fa-times text-xs"></i>
                </button>
                <h6 class="font-semibold text-gray-800 mb-2 pr-8">${building.nama_gedung || building.name || 'Unnamed Building'}</h6>
                <div class="text-sm text-gray-600 space-y-1">
                    ${addressParts.map(part => `<p>${part}</p>`).join('')}
                </div>
            </div>
        `;
    });
    
    selectedBuildingsDiv.innerHTML = content;
}

function updateAvailableBuildingsDisplay(addressIndex) {
    // Reload available buildings to update selected state
    loadBuildingsForAddress(addressIndex);
}

// Update billing address data with selected buildings
function updateBillingAddressDataWithSelectedBuildings() {
    billingAddresses.forEach((address, index) => {
        const selectedBuildings = selectedBuildingsByAddress[index] || [];
        address.buildings = selectedBuildings.map(b => b.id);
        
        // Update address display with full address details
        if (selectedBuildings.length > 0) {
            // Build full address string for each building
            const addressStrings = selectedBuildings.map(building => {
                const addressParts = [];
                
                // Building name
                const buildingName = building.nama_gedung || building.name || 'Unnamed Building';
                addressParts.push(buildingName);
                
                // Address
                if (building.alamat_1 || building.address) {
                    addressParts.push(building.alamat_1 || building.address);
                }
                if (building.alamat_2) {
                    addressParts.push(building.alamat_2);
                }
                
                // Location
                const locationParts = [];
                if (building.city) {
                    const cityName = typeof building.city === 'string' ? building.city : (building.city.name || '');
                    if (cityName && typeof cityName === 'string' && cityName !== 'true' && cityName !== 'false' && cityName.trim() !== '') {
                        locationParts.push(cityName);
                    }
                }
                if (building.district) {
                    const districtName = typeof building.district === 'string' ? building.district : (building.district.name || '');
                    if (districtName && typeof districtName === 'string' && districtName !== 'true' && districtName !== 'false' && districtName.trim() !== '') {
                        locationParts.push(districtName);
                    }
                }
                if (building.subdistrict) {
                    const subdistrictName = typeof building.subdistrict === 'string' ? building.subdistrict : (building.subdistrict.name || '');
                    if (subdistrictName && typeof subdistrictName === 'string' && subdistrictName !== 'true' && subdistrictName !== 'false' && subdistrictName.trim() !== '') {
                        locationParts.push(subdistrictName);
                    }
                }
                if (building.province) {
                    const provinceName = typeof building.province === 'string' ? building.province : (building.province.name || '');
                    if (provinceName && typeof provinceName === 'string' && provinceName !== 'true' && provinceName !== 'false' && provinceName.trim() !== '') {
                        locationParts.push(provinceName);
                    }
                }
                if (locationParts.length > 0) {
                    addressParts.push(locationParts.join(', '));
                }
                
                // Postal code
                const postalCode = building.kode_pos || building.postal_code;
                if (postalCode && typeof postalCode === 'string' && postalCode !== 'true' && postalCode !== 'false' && postalCode.trim() !== '') {
                    addressParts.push(`Kode Pos: ${postalCode}`);
                }
                
                return addressParts.join(' | ');
            });
            
            address.address = addressStrings.join(', ');
            
            // Don't update billing email here - it should come from step 4
            // Step 5 is only for selecting buildings, email should already be set from step 4
        } else {
            // If no buildings selected in step 5, try to preserve address and email from step 4
            if (!address.address || address.address === '') {
                const addressSelect = address.element?.querySelector('select[name*="[address_id]"]');
                if (addressSelect && addressSelect.selectedOptions.length > 0) {
                    const selectedOptions = Array.from(addressSelect.selectedOptions)
                        .map(option => option.textContent)
                        .filter(text => text !== 'Pilih atau ketik disini...');
                    if (selectedOptions.length > 0) {
                        address.address = selectedOptions.join(', ');
                    }
                }
            }
            if (!address.address || address.address === '') {
                address.address = '';
            }
            
            // Get email from PIC Finance (customer contact) di step 4
            const picFinanceSelect = address.element?.querySelector('select[name*="[pic_finance]"]');
            if (picFinanceSelect && picFinanceSelect.selectedOptions.length > 0) {
                const selectedOption = picFinanceSelect.selectedOptions[0];
                const contactEmail = selectedOption.getAttribute('data-contact-email');
                if (contactEmail && contactEmail.trim() !== '') {
                    address.billing_email = contactEmail;
                } else {
                    address.billing_email = '';
                }
            } else {
                address.billing_email = '';
            }
        }
        
        // Get tax display from step 4
        const element = address.element;
        if (element) {
            const taxDisplayInput = element.querySelector('input[name*="[tax_display]"]');
            if (taxDisplayInput) {
                address.tax_display = taxDisplayInput.value || '';
            }
        }
    });
}

// Client Contact Modal functions
function openClientContactModal() {
    const modal = document.getElementById('clientContactModal');
    modal.classList.add('show');
    modal.style.display = 'flex';
    
    // Check if options exist
    const positionSelect = document.getElementById('clientContactPosition');
    const salutationSelect = document.getElementById('clientContactSalutation');
    console.log('Position options count:', positionSelect ? positionSelect.options.length : 0);
    console.log('Salutation options count:', salutationSelect ? salutationSelect.options.length : 0);
    
    // Set customer_id
    const customerIdInput = document.getElementById('clientContactCustomerId');
    if (customerIdInput && quotationData && quotationData.customer) {
        customerIdInput.value = quotationData.customer.id;
        console.log('Set customer_id for contact:', quotationData.customer.id);
    } else {
        console.error('Cannot set customer_id: quotationData or customer missing');
        alert('Error: Data customer tidak ditemukan. Silakan pilih quotation terlebih dahulu.');
        closeClientContactModal();
        return;
    }
    
    // Initialize Select2 for position and salutation after modal is shown
    setTimeout(() => {
        // Destroy existing Select2 instances if any
        const $positionSelect = $('#clientContactPosition');
        const $salutationSelect = $('#clientContactSalutation');
        
        if ($positionSelect.hasClass('select2-hidden-accessible')) {
            $positionSelect.select2('destroy');
        }
        if ($salutationSelect.hasClass('select2-hidden-accessible')) {
            $salutationSelect.select2('destroy');
        }
        
        // Remove no-select2 class
        $positionSelect.removeClass('no-select2');
        $salutationSelect.removeClass('no-select2');
        
        // Check if jQuery Select2 is available
        if (typeof $.fn.select2 === 'undefined') {
            console.error('Select2 is not loaded!');
            return;
        }
        
        // Initialize Select2 with dropdownParent pointing to modal
        try {
            $positionSelect.select2({
                dropdownParent: $('#clientContactModal'),
                placeholder: 'Pilih jabatan...',
                allowClear: false,
                width: '100%'
            });
            console.log('Position Select2 initialized');
        } catch(e) {
            console.error('Error initializing Position Select2:', e);
        }
        
        try {
            $salutationSelect.select2({
                dropdownParent: $('#clientContactModal'),
                placeholder: 'Pilih panggilan...',
                allowClear: false,
                width: '100%'
            });
            console.log('Salutation Select2 initialized');
        } catch(e) {
            console.error('Error initializing Salutation Select2:', e);
        }
        
        // Prevent modal from closing when clicking on Select2 dropdown
        $(document).on('select2:open.clientContactModal', (e) => {
            if ($(e.target).attr('id') === 'clientContactPosition' || $(e.target).attr('id') === 'clientContactSalutation') {
                setTimeout(() => {
                    const select2Dropdown = $('#clientContactModal .select2-dropdown');
                    if (select2Dropdown.length) {
                        select2Dropdown.css({
                            'z-index': '10050',
                            'pointer-events': 'auto'
                        });
                    }
                    select2Dropdown.off('mousedown.clientContactModal click.clientContactModal').on('mousedown.clientContactModal click.clientContactModal', function(e) {
                        e.stopPropagation();
                    });
                }, 50);
            }
        });
        
        // Add event listener for cancel button
        $('#cancelClientContactBtn').off('click.clientContactModal').on('click.clientContactModal', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeClientContactModal();
            return false;
        });
        
        // Add event listener for close button (X)
        $('#clientContactModal .modal-header button[onclick="closeClientContactModal()"]').off('click.clientContactModal').on('click.clientContactModal', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeClientContactModal();
            return false;
        });
        
        // Prevent form submission on Enter key
        $('#clientContactForm').off('submit.clientContactModal').on('submit.clientContactModal', function(e) {
            e.preventDefault();
            return false;
        });
    }, 100);
}

function closeClientContactModal() {
    console.log('closeClientContactModal called');
    const modal = document.getElementById('clientContactModal');
    if (!modal) {
        console.error('Modal element not found!');
        return;
    }
    
    modal.classList.remove('show');
    modal.style.display = 'none';
    
    // Destroy Select2 instances
    const positionSelect = $('#clientContactPosition');
    const salutationSelect = $('#clientContactSalutation');
    
    if (positionSelect && positionSelect.hasClass('select2-hidden-accessible')) {
        try {
            positionSelect.select2('destroy');
        } catch(e) {
            console.warn('Error destroying position Select2:', e);
        }
    }
    if (salutationSelect && salutationSelect.hasClass('select2-hidden-accessible')) {
        try {
            salutationSelect.select2('destroy');
        } catch(e) {
            console.warn('Error destroying salutation Select2:', e);
        }
    }
    
    // Remove event handlers
    $(document).off('select2:open.clientContactModal');
    
    // Reset form
    const form = document.getElementById('clientContactForm');
    if (form) {
        form.reset();
    }
    
    console.log('Modal closed successfully');
}

function saveClientContact() {
    const form = document.getElementById('clientContactForm');
    const formData = new FormData(form);
    
    // Validasi customer_id sebelum submit
    const customerId = formData.get('customer_id');
    if (!customerId) {
        alert('⚠️ Error: Data customer tidak ditemukan.\n\nSilakan REFRESH halaman ini dan pilih quotation kembali sebelum menambah contact baru.');
        closeClientContactModal();
        return;
    }
    
    fetch('/marketing/contracts/save-client-contact', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add new contact to dropdowns
            addContactToDropdowns(data.contact);
            closeClientContactModal();
            alert('✅ Client contact saved successfully!');
        } else {
            // Tampilkan pesan error yang jelas
            let errorMessage = data.message || 'Terjadi kesalahan saat menyimpan contact.';
            if (errorMessage.includes('Customer ID')) {
                errorMessage += '\n\n💡 Tip: Silakan REFRESH halaman dan pilih quotation kembali.';
            }
            alert('❌ ' + errorMessage);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Gagal menyimpan contact.\n\nSilakan REFRESH halaman dan coba lagi.');
    });
}

function addContactToDropdowns(contact) {
    const option = `<option value="${contact.id}">${contact.name}</option>`;
    document.querySelectorAll('select[name*="company_signing"], select[name*="pic_finance"]').forEach(select => {
        select.insertAdjacentHTML('beforeend', option);
    });
}

// Save functions
function saveDraft() {
    if (!validateCurrentStep()) return;
    
    // Update billing address data before submission
    updateBillingAddressData();
    updateBillingAddressDataWithSelectedBuildings();
    
    // Validate building duplicates before submission
    let hasDuplicates = false;
    billingAddresses.forEach((address, index) => {
        const duplicateWarning = document.getElementById(`duplicateWarning_${index}`);
        if (duplicateWarning && duplicateWarning.style.display !== 'none') {
            hasDuplicates = true;
        }
    });
    
    if (hasDuplicates) {
        alert('⚠️ Ada building yang duplikat di billing address yang berbeda. Satu building tidak boleh ada di lebih dari satu billing group.');
        return;
    }
    
    // Update billing address data to ensure we have the latest inputs
    updateBillingAddressData();

    // Enable all address selects before creating FormData to ensure they are captured by native form submission
    document.querySelectorAll('.address-select').forEach(select => {
        select.disabled = false;
    });

    const formData = new FormData(document.getElementById('contractWizardForm'));
    formData.append('status', 'draft');
    
    // Ensure buildings are sent as arrays
    billingAddresses.forEach((address, index) => {
        if (address.buildings && Array.isArray(address.buildings)) {
            address.buildings.forEach((buildingId, buildingIndex) => {
                formData.append(`billing_addresses[${index}][buildings][${buildingIndex}]`, buildingId);
            });
        }
    });
    
    fetch('/marketing/contracts/wizard/save', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Contract saved as draft successfully!');
            // Redirect to contract detail page instead of index
            if (data.redirect_url) {
                window.location.href = data.redirect_url;
            } else if (data.contract_id) {
                window.location.href = `/marketing/contracts/${data.contract_id}`;
            } else {
                window.location.href = '/marketing/contracts';
            }
        } else {
            alert('Error saving contract: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving contract');
    });
}

async function finalizeContract() {
    if (!validateCurrentStep()) return;
    
    // Update billing address data before submission
    updateBillingAddressData();
    
    // Validate building duplicates before submission
    let hasDuplicates = false;
    billingAddresses.forEach((address, index) => {
        const duplicateWarning = document.getElementById(`duplicateWarning_${index}`);
        if (duplicateWarning && duplicateWarning.style.display !== 'none') {
            hasDuplicates = true;
        }
    });
    
    if (hasDuplicates) {
        alert('⚠️ Ada building yang duplikat di billing address yang berbeda. Satu building tidak boleh ada di lebih dari satu billing group.');
        return;
    }
    
    // --- Operational Area Validation ---
    // Collect all unique building IDs from billing addresses
    const allBuildingIds = new Set();
    billingAddresses.forEach(address => {
        if (address.buildings && Array.isArray(address.buildings)) {
            address.buildings.forEach(id => allBuildingIds.add(id));
        }
    });

    if (allBuildingIds.size > 0) {
        // Show loading
        Swal.fire({
            title: 'Memvalidasi...',
            text: 'Mengecek operational area untuk semua gedung',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const buildingIds = Array.from(allBuildingIds);
            const invalidBuildings = [];

            for (const buildingId of buildingIds) {
                const response = await fetch(`/operational/api/check-operational-area/${buildingId}`);
                const data = await response.json();
                if (!data.is_valid) {
                    invalidBuildings.push({
                        id: buildingId,
                        city_name: data.city_name,
                        message: data.message,
                        branches_url: data.branches_url
                    });
                }
            }

            if (invalidBuildings.length > 0) {
                const invalidListHtml = invalidBuildings.map(b => `<li>Gedung ID ${b.id}: <strong>${b.city_name}</strong></li>`).join('');
                
                Swal.fire({
                    title: 'Tidak Dapat Finalize',
                    html: `
                        <div class="text-start">
                            <p>${invalidBuildings[0].message}</p>
                            <p class="mb-2">Gedung dengan area tidak terdaftar:</p>
                            <ul class="mb-3">${invalidListHtml}</ul>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-map-marker-alt"></i> Ke Operational Areas',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open(invalidBuildings[0].branches_url, '_blank');
                    }
                });
                return;
            }
        } catch (error) {
            console.error('Error checking operational areas:', error);
            // On error, let user choose to proceed or stay
            const proceed = await new Promise(resolve => {
                Swal.fire({
                    title: 'Validation Error',
                    text: 'Gagal mengecek operational area. Apakah Anda ingin melanjutkan?',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal'
                }).then((result) => resolve(result.isConfirmed));
            });
            if (!proceed) return;
        }
    }

    // --- End Validation ---

    // Final confirmation before submission
    const confirmFinalize = await new Promise(resolve => {
        Swal.fire({
            title: 'Finalize Contract?',
            text: "Contract yang sudah difinalisasi tidak dapat diubah lagi. Pastikan semua data sudah benar.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Finalize!',
            cancelButtonText: 'Batal'
        }).then((result) => resolve(result.isConfirmed));
    });

    if (!confirmFinalize) return;

    Swal.fire({
        title: 'Memproses...',
        text: 'Menyimpan contract...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData(document.getElementById('contractWizardForm'));
    formData.append('status', 'final');
    
    // [NEW] Append source_contract_ids for merge
    if (selectedMergeContractIds.length > 0) {
        selectedMergeContractIds.forEach((item, index) => {
            formData.append(`source_contract_ids[${index}]`, item.id);
        });
        
        // Final sanity check message for merge
        const mergeNumbers = selectedMergeContractIds.map(i => i.number).join(', ');
        const confirmMerge = await new Promise(resolve => {
            Swal.fire({
                title: 'Konfirmasi Merger',
                html: `Anda memilih untuk menggabungkan kontrak: <br><strong class="text-primary">${mergeNumbers}</strong><br><br>Gedung dan Item dari kontrak tersebut akan dipindahkan ke kontrak baru ini. Lanjutkan?`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Ya, Gabungkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#3b82f6'
            }).then((result) => resolve(result.isConfirmed));
        });
        if (!confirmMerge) return;
    }
    
    // Ensure buildings are sent as arrays
    billingAddresses.forEach((address, index) => {
        if (address.buildings && Array.isArray(address.buildings)) {
            address.buildings.forEach((buildingId, buildingIndex) => {
                formData.append(`billing_addresses[${index}][buildings][${buildingIndex}]`, buildingId);
            });
        }
    });
    
    fetch('/marketing/contracts/wizard/save', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Contract finalized successfully!',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else if (data.contract_id) {
                    window.location.href = `/marketing/contracts/${data.contract_id}`;
                } else {
                    window.location.href = '/marketing/contracts';
                }
            });
        } else {
            Swal.fire('Error', 'Error finalizing contract: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Error finalizing contract', 'error');
    });
}

// Add Tax Modal Logic
let currentAddressIndexForTax = null;
let isSavingTaxData = false;

function openAddTaxModal(addressIndex) {
    currentAddressIndexForTax = addressIndex;
    const modal = document.getElementById('addTaxModal');
    if (!modal) return;

    if (!quotationData || !quotationData.customer) {
        alert('Data customer tidak ditemukan. Silakan pilih quotation terlebih dahulu.');
        return;
    }

    document.getElementById('taxModalCustomerId').value = quotationData.customer.id;
    document.getElementById('addTaxForm').reset();

    syncModalTaxRateFromCode();
    
    // Set default effective date to today
    const effectiveDateInput = document.querySelector('#addTaxForm input[name="effective_date"]');
    if (effectiveDateInput) effectiveDateInput.value = new Date().toISOString().split('T')[0];

    modal.style.display = 'flex';
    updateTaxNumberMaxLength('modal');
    updateTaxNumberCounter('modal');
}

function syncModalTaxRateFromCode() {
    const taxCodeSelect = document.getElementById('modal_tax_type');
    const ppnCodeInput = document.getElementById('modal_ppn_code');
    const taxRateInput = document.getElementById('modal_tax_rate');
    const taxRateDisplay = document.getElementById('modal_tax_rate_display');
    const taxCodeDescription = document.getElementById('modal_tax_code_description');
    const selectedCode = taxCodeSelect ? taxCodeSelect.value : '';
    const selectedRule = selectedCode ? financeTaxCodeRules[selectedCode] : null;
    const formattedTaxRate = Number((selectedRule && selectedRule.zero_tax ? 0 : defaultVatTaxRate) || 0).toFixed(2);

    if (ppnCodeInput) {
        ppnCodeInput.value = selectedCode;
    }

    if (taxRateInput) {
        taxRateInput.value = formattedTaxRate;
    }

    if (taxRateDisplay) {
        taxRateDisplay.value = `${formattedTaxRate}%`;
    }

    if (taxCodeDescription) {
        taxCodeDescription.textContent = selectedRule
            ? `${selectedRule.description} ${selectedRule.ppn_status ? '- ' + selectedRule.ppn_status : ''}`
            : '';
    }
}

function closeAddTaxModal() {
    const modal = document.getElementById('addTaxModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function updateTaxNumberCounter(mode) {
    const inputId = mode === 'modal' ? 'modal_tax_number' : 'edit_tax_number';
    const counterId = mode === 'modal' ? 'modal_tax_number_counter' : 'edit_tax_number_counter';
    const maxId = mode === 'modal' ? 'modal_tax_number_max' : 'edit_tax_number_max';
    
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    
    if (input && counter) {
        const length = input.value.length;
        const maxLength = parseInt(input.getAttribute('maxlength') || 25);
        counter.textContent = length;
        
        if (length === maxLength) {
            counter.className = 'font-semibold text-green-600';
        } else if (length > 0) {
            counter.className = 'font-semibold text-blue-600';
        } else {
            counter.className = 'font-semibold text-gray-600';
        }
    }
}

function updateTaxNumberMaxLength(mode) {
    const taxNameId = mode === 'modal' ? 'modal_tax_name' : 'edit_tax_name';
    const taxNumberId = mode === 'modal' ? 'modal_tax_number' : 'edit_tax_number';
    const maxDisplayId = mode === 'modal' ? 'modal_tax_number_max' : 'edit_tax_number_max';
    
    const taxNameSelect = document.getElementById(taxNameId);
    const taxNumberInput = document.getElementById(taxNumberId);
    const maxDisplay = document.getElementById(maxDisplayId);
    
    if (taxNameSelect && taxNumberInput) {
        const taxName = taxNameSelect.value;
        let maxLength = 25;
        
        switch(taxName) {
            case 'NPWP':
            case 'NIK':
                maxLength = 16;
                break;
            case 'NITKU':
                maxLength = 22;
                break;
            default:
                maxLength = 25;
                break;
        }
        
        taxNumberInput.setAttribute('maxlength', maxLength);
        if (maxDisplay) maxDisplay.textContent = maxLength;
        
        if (taxNumberInput.value.length > maxLength) {
            taxNumberInput.value = taxNumberInput.value.substring(0, maxLength);
        }
        updateTaxNumberCounter(mode);
    }
}

function saveTaxData() {
    if (isSavingTaxData) return;

    const form = document.getElementById('addTaxForm');
    const saveButton = document.getElementById('saveTaxButton');
    syncModalTaxRateFromCode();

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    isSavingTaxData = true;
    const originalButtonText = saveButton ? saveButton.innerHTML : 'Save';
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
    }

    Swal.fire({
        title: 'Menyimpan...',
        text: 'Sedang menyimpan data pajak...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Add status/is_active mapping
    data.is_active = data.status === 'active' ? '1' : '0';

    fetch('/company/customer-taxes', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(async response => {
        const responseText = await response.text();
        let result = {};

        try {
            result = responseText ? JSON.parse(responseText) : {};
        } catch (error) {
            throw new Error('Server mengembalikan response yang tidak valid. Silakan refresh halaman dan coba lagi.');
        }

        if (!response.ok) {
            const validationErrors = result.errors
                ? Object.values(result.errors).flat().join('\n')
                : null;
            throw new Error(validationErrors || result.message || 'Gagal menyimpan data pajak.');
        }

        return result;
    })
    .then(result => {
        if (result.status === 'success') {
            closeAddTaxModal();
            
            // Update global quotationData.customer.customer_tax_settings
            // We need to fetch latest customer tax settings or rely on the response
            if (result.data) {
                if (!quotationData.customer.customer_tax_settings) {
                    quotationData.customer.customer_tax_settings = [];
                }
                // Add the new setting to the global data
                quotationData.customer.customer_tax_settings.push(result.data);
                
                // Repopulate dropdown for the specific address index
                populateTaxDropdown(currentAddressIndexForTax);
                
                // Auto-select the newly added option
                // Since result.data has all info, we can find the matching option
                setTimeout(() => {
                    const select = document.getElementById(`taxSelect_${currentAddressIndexForTax}`);
                    if (select) {
                        // The value in dropdown is a stringified JSON
                        for (let i = 0; i < select.options.length; i++) {
                            const opt = select.options[i];
                            if (opt.value) {
                                try {
                                    const val = JSON.parse(opt.value);
                                    if (val.setting_id === result.data.id) {
                                        select.selectedIndex = i;
                                        handleTaxSelection(currentAddressIndexForTax);
                                        break;
                                    }
                                } catch(e) {}
                            }
                        }
                    }
                }, 100);
            }
            Swal.fire('Berhasil!', 'Data pajak berhasil disimpan.', 'success');
        } else {
            Swal.fire('Error', result.message || 'Gagal menyimpan data pajak.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'Terjadi kesalahan saat menyimpan data pajak.', 'error');
    })
    .finally(() => {
        isSavingTaxData = false;
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.innerHTML = originalButtonText;
        }
    });
}
</script>



@endsection
