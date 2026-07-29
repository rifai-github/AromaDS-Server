<!-- Create Customer Modal -->
<div id="createCustomerModalOverlay" class="modal-overlay" onclick="closeCreateCustomerModal()">
    <div class="modal-container large" onclick="event.stopPropagation()">
        <div class="modal-header bg-white border-b border-gray-100 pb-4">
            <h2 class="modal-title text-xl font-bold text-gray-800">Add New Customer</h2>
            <button class="modal-close text-gray-400 hover:text-gray-600 transition-colors" onclick="closeCreateCustomerModal()">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="modal-body px-8 py-6 max-h-[75vh] overflow-y-auto custom-scrollbar">
            <!-- Custom Styles for Select2 & Modal Inputs -->
            <style>
                /* Force Select2 to match Input Height (42px) */
                .select2-container .select2-selection--single {
                    height: 42px !important;
                    display: flex !important;
                    align-items: center !important;
                    border-color: #d1d5db !important; /* gray-300 */
                    border-radius: 0.5rem !important; /* rounded-lg */
                }
                .select2-container--default .select2-selection--single .select2-selection__arrow {
                    height: 40px !important;
                    top: 1px !important;
                }
                /* Ensure focus ring matches standard inputs */
                .select2-container--default.select2-container--focus .select2-selection--single {
                    border-color: #3b82f6 !important; /* blue-500 */
                    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5) !important;
                }
                /* Placeholder styling */
                .select2-container--default .select2-selection--single .select2-selection__placeholder {
                    color: #9ca3af !important; /* gray-400 to match standard inputs */
                    font-size: 0.875rem !important; /* text-sm */
                }
                .select2-container--default .select2-selection--single .select2-selection__rendered {
                    font-size: 0.875rem !important; /* text-sm */
                    color: #1f2937 !important; /* gray-800 */
                    padding-left: 12px !important;
                }
                /* Ensure 100% width */
                .select2-container {
                    width: 100% !important;
                }
                
                /* FORCE STANDARD INPUTS TO MATCH SELECT2 EXACTLY */
                .form-input-styled {
                    height: 42px !important;
                    border-radius: 0.5rem !important; /* rounded-lg */
                    border: 1px solid #d1d5db !important; /* border-gray-300 */
                    padding-top: 0.5rem !important;
                    padding-bottom: 0.5rem !important;
                    line-height: normal !important;
                }
                .form-input-styled:read-only {
                    background-color: #f9fafb !important; /* bg-gray-50 */
                    color: #6b7280 !important; /* text-gray-500 */
                }
            </style>

            <p class="text-gray-500 mb-8 text-center text-sm">Please fill in the customer details below. Fields marked with <span class="text-red-500">*</span> are required.</p>
            <form id="createCustomerForm" class="space-y-8">
                
                <!-- Basic Information Section -->
                <div class="form-section">
                    <h3 class="section-title flex items-center text-sm font-semibold text-gray-800 uppercase tracking-wider mb-5 border-b border-gray-100 pb-2">
                        <span class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center mr-3 text-blue-600">
                            <i class="fas fa-building text-sm"></i>
                        </span>
                        Basic Information
                    </h3>
                    <div class="grid grid-cols-12 gap-6">
                        <!-- Row 1 -->
                        <div class="col-span-12 md:col-span-3">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Badan Hukum <span class="text-red-500">*</span></label>
                            <select id="create_company_type_input" name="company_type" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all h-[42px] select2" required>
                                <option value="">Select Type</option>
                                <option value="pt">PT</option>
                                <option value="cv">CV</option>
                                <option value="firma">Firma</option>
                                <option value="koperasi">Koperasi</option>
                                <option value="perorangan">Perorangan</option>
                                <option value="persero">Persero</option>
                                <option value="yayasan">Yayasan</option>
                                <option value="ud">UD</option>
                            </select>
                            <input type="text" id="create_other_company_type" name="other_company_type" class="form-input-styled w-full px-3 py-2.5 mt-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all hidden" placeholder="Specify Type">
                        </div>
                        <div class="col-span-12 md:col-span-9">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Customer Name <span class="text-red-500">*</span></label>
                            <input type="text" id="create_name" name="name" class="form-input-styled w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required placeholder="Enter official customer name">
                        </div>

                        <!-- Row 2 -->
                        <div class="col-span-12 md:col-span-5">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Label / Alias</label>
                            <input type="text" id="create_label_alias" name="label_alias" class="form-input-styled w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="Short name (e.g. Toko ABC)">
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Category</label>
                            <select id="create_category_id" name="customer_category_id" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all h-[42px] select2">
                                <option value="">Select Category</option>
                                @foreach($customerTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-3">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Code</label>
                            <input type="text" id="create_customer_code" name="customer_code" class="form-input-styled w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500" readonly placeholder="Auto-generated">
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="form-section">
                    <h3 class="section-title flex items-center text-sm font-semibold text-gray-800 uppercase tracking-wider mb-5 border-b border-gray-100 pb-2">
                        <span class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center mr-3 text-purple-600">
                            <i class="fas fa-address-book text-sm"></i>
                        </span>
                        Contact Information
                    </h3>
                    <div class="grid grid-cols-12 gap-6">
                        <div class="col-span-12 md:col-span-5">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Email <span class="text-red-500">*</span></label>
                            <input type="email" id="create_email" name="email" class="form-input-styled w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" required placeholder="company@example.com">
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Phone <span class="text-red-500">*</span></label>
                            <input type="text" id="create_phone" name="phone" class="form-input-styled w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" required placeholder="0812...">
                        </div>
                        <div class="col-span-12 md:col-span-3">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Status <span class="text-red-500">*</span></label>
                            <select id="create_status" name="status" class="form-input-styled w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Location Section (Improved Grid) -->
                <div class="form-section">
                    <h3 class="section-title flex items-center text-sm font-semibold text-gray-800 uppercase tracking-wider mb-5 border-b border-gray-100 pb-2">
                        <span class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center mr-3 text-red-600">
                            <i class="fas fa-map-marker-alt text-sm"></i>
                        </span>
                        Address & Location
                    </h3>
                    <div class="grid grid-cols-12 gap-6">
                        <!-- Row 1: Province & City -->
                        <div class="col-span-12 md:col-span-6">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Province</label>
                            <select id="create_province_id" name="province_id" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all h-[42px] select2" onchange="loadCitiesForCreate(this.value)">
                                <option value="">Select Province</option>
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">City</label>
                            <select id="create_city_id" name="city_id" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all h-[42px] select2" onchange="loadDistrictsForCreate(this.value)">
                                <option value="">Select City</option>
                            </select>
                        </div>

                        <!-- Row 2: District, Subdistrict, Postal Code -->
                        <div class="col-span-12 md:col-span-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">District</label>
                            <select id="create_district_id" name="district_id" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all h-[42px] select2" onchange="loadSubdistrictsForCreate(this.value); clearPostalCodeForCreate();">
                                <option value="">Select District</option>
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Subdistrict</label>
                            <select id="create_subdistrict_id" name="subdistrict_id" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all h-[42px] select2" onchange="loadPostalCodeForCreate(this.value)">
                                <option value="">Select Subdistrict</option>
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Postal Code</label>
                            <input type="text" id="create_postal_code" name="postal_code" class="form-input-styled w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600" readonly placeholder="Auto-filled">
                        </div>

                        <!-- Row 3: Address -->
                        <div class="col-span-12">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Full Address <span class="text-red-500">*</span></label>
                            <textarea id="create_address" name="address" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all" rows="2" required placeholder="Street name, building, unit number, etc."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Billing & Security Section -->
                <div class="form-section">
                    <h3 class="section-title flex items-center text-sm font-semibold text-gray-800 uppercase tracking-wider mb-5 border-b border-gray-100 pb-2">
                        <span class="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center mr-3 text-green-600">
                            <i class="fas fa-file-invoice-dollar text-sm"></i>
                        </span>
                        Billing & Configuration
                    </h3>
                    <div class="grid grid-cols-12 gap-6">
                        <div class="col-span-12 md:col-span-6">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">NIB</label>
                            <input type="text" id="create_nib" name="nib" maxlength="50" class="form-input-styled w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Nomor Induk Berusaha">
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Customer Group</label>
                            <input type="text" id="create_customer_group" name="customer_group" maxlength="50" class="form-input-styled w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="e.g. Group Name">
                        </div>
                        <div class="col-span-12">
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase">Default Bank Payment</label>
                            <select id="create_default_bank_payment_id" name="default_bank_payment_id" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all h-[42px] select2">
                                <option value="">Select Bank Payment</option>
                                @foreach($bankPayments as $bankPayment)
                                    <option value="{{ $bankPayment->id }}">{{ $bankPayment->bank->name ?? '' }} - {{ $bankPayment->account_name }} ({{ $bankPayment->account_number }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Multi PIC -->
                        <div class="col-span-12">
                            <div class="flex justify-between items-end mb-1.5">
                                <label class="block text-xs font-semibold text-gray-600 uppercase">Person In Charge (PIC)</label>
                                <span class="text-xs text-blue-600 italic"><i class="fas fa-info-circle mr-1"></i> Hold Ctrl/Cmd to select multiple</span>
                            </div>
                            <div class="flex" style="display: flex; gap: 8px;">
                                <div style="flex: 1; min-width: 0;">
                                    <select id="create_contact_ids" name="contact_ids[]" multiple class="bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all custom-scrollbar select2" style="width: 100%; height: 100px;">
                                        @foreach($allContacts as $contact)
                                            <option value="{{ $contact->id }}" class="py-1">{{ $contact->name }} - {{ $contact->position ?? 'No Position' }} ({{ $contact->phone }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" onclick="openCreateCustomerContactModal()" class="btn btn-success btn-sm" style="width: 42px; height: 42px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;" title="Add New Contact">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Settings Checkboxes -->
                        <div class="col-span-12 md:col-span-6">
                            <div class="flex items-center p-3 border border-gray-200 rounded-lg bg-gray-50 hover:bg-white hover:border-blue-300 transition-colors cursor-pointer">
                                <input type="checkbox" id="create_is_pkp" name="is_pkp" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3 cursor-pointer">
                                <label for="create_is_pkp" class="text-sm font-medium text-gray-700 cursor-pointer select-none flex-1">
                                    Is PKP (Pengusaha Kena Pajak)
                                </label>
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                             <div class="flex items-center p-3 border border-gray-200 rounded-lg bg-gray-50 hover:bg-white hover:border-blue-300 transition-colors cursor-pointer">
                                <input type="checkbox" id="create_is_active" name="is_active" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-3 cursor-pointer" checked>
                                <label for="create_is_active" class="text-sm font-medium text-gray-700 cursor-pointer select-none flex-1">
                                    Active Status
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer px-8 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-xl">
            <button type="button" class="btn btn-secondary px-6" onclick="closeCreateCustomerModal()">Cancel</button>
            <button type="button" class="btn btn-primary px-6" onclick="submitCreateCustomerForm()">
                <i class="fas fa-check mr-2"></i> Create Customer
            </button>
        </div>
    </div>
</div>

<!-- Create Contact Modal (Inline) -->
<div id="createCustomerContactModalOverlay" class="modal-overlay" onclick="closeCreateCustomerContactModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Add New Contact Person</h2>
            <button class="modal-close" onclick="closeCreateCustomerContactModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="createCustomerContactForm">
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-user text-purple-500"></i>
                        Contact Person Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Salutation</label>
                            <select id="contact_salutation_id" name="salutation" class="w-full px-4 py-3.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent select2">
                                <option value="">Select Salutation</option>
                                <!-- Options loaded dynamically from salutations API -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" id="contact_name" name="name" class="w-full px-4 py-3.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="Enter full name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" id="contact_email" name="email" class="w-full px-4 py-3.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="email@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="tel" id="contact_phone" name="phone" class="w-full px-4 py-3.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required placeholder="08123456789" pattern="[0-9\-\+\(\)\s]+" oninput="this.value = this.value.replace(/[^0-9\-\+\(\)\s]/g, '')">
                            <small class="text-gray-500 text-xs mt-1 block">Only numbers, +, -, (, ), and spaces allowed</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                            <select id="contact_position_id" name="position" class="w-full px-4 py-3.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent select2">
                                <option value="">Select Position</option>
                                <!-- Options loaded dynamically from positions API -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
                            <input type="text" id="contact_customer_name" class="w-full px-4 py-3.5 border border-gray-300 rounded-lg bg-gray-100" readonly placeholder="Will be linked after customer is created">
                            <input type="hidden" id="contact_customer_id" name="customer_id">
                            <small class="text-gray-500 text-xs mt-1 block">This contact will be automatically assigned to the new customer after creation</small>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCreateCustomerContactModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitCreateCustomerContactForm()">Add Contact</button>
        </div>
    </div>
</div>
