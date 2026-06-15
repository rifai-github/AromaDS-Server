    // Global variables & Safety output
    const isAdmin = @json(auth()->user()->hasRole('Administrator') || auth()->user()->hasRole('Admin') || auth()->user()->data_restriction === 'none');
    console.log('Job Advice JavaScript loading... isAdmin:', isAdmin);

    let selectedIdsForRetry = [];
    let successModalTimer = null;

    // --- Global Utility & Modal Functions (Defined at top for safety) ---
    window.openModal = function(title) {
        console.log('openModal called with title:', title);
        const modalTitle = document.getElementById('modalTitle');
        const modalOverlay = document.getElementById('modalOverlay');
        if (modalTitle) modalTitle.textContent = title;
        if (modalOverlay) {
            modalOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function() {
        const modalOverlay = document.getElementById('modalOverlay');
        const modalBody = document.getElementById('modalBody');
        if (modalOverlay) modalOverlay.classList.remove('show');
        document.body.style.overflow = 'auto';
        if (modalBody) modalBody.innerHTML = '';
    };

    window.openCreateModal = function() {
        console.log('openCreateModal initiating...');
        window.openModal('Create New Job Advice');
        const modalBody = document.getElementById('modalBody');
        if (!modalBody) return;
        
        modalBody.innerHTML = `
            <form id="jobAdviceForm" onkeydown="return event.key != 'Enter';">
                <div class="modal-form-container">
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Job Advice Information</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="request_by">Request By <span class="text-danger">*</span></label>
                                <select class="form-select" id="request_by" name="request_by" required>
                                    <option value="">Select User</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="type">Job Advice Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="modal_type" name="type" required onchange="toggleRemoveDate()">
                                    <option value="">Select Type</option>
                                    <option value="install_free">Install Free</option>
                                    <option value="Install">Install</option>
                                    <option value="Remove">Remove</option>
                                    <option value="Extra">Extra</option>
                                    <option value="change_rental">Change Rental</option>
                                    <option value="Complain">Complain</option>
                                </select>
                            </div>
                        </div>
                        <div style="display: none;">
                            <input type="radio" name="source_type" value="contract" id="source_contract" checked onchange="toggleSourceType()">
                            <input type="radio" name="source_type" value="quotation" id="source_quotation" onchange="toggleSourceType()">
                        </div>
                        <div class="form-row">
                            <div class="form-group" id="contract_group">
                                <label class="form-label" for="contract_id">Reference No (Contract) <span class="text-danger">*</span></label>
                                <select class="form-select" id="contract_id" name="contract_id" disabled>
                                    <option value="">Select Marketing First</option>
                                </select>
                            </div>
                            <div class="form-group" id="quotation_group" style="display: none;">
                                <label class="form-label" for="quotation_id">Reference No (Quotation) <span class="text-danger">*</span></label>
                                <select class="form-select" id="quotation_id" name="quotation_id" disabled>
                                    <option value="">Select Marketing First</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="expected_date">Expected Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-input" id="expected_date" name="expected_date" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label" for="customer_contact_id">PIC (Person In Charge)</label>
                                <div class="d-flex">
                                    <select class="form-select" id="customer_contact_id" name="customer_contact_id" style="width: 100%;">
                                        <option value="">Select Contract/Quotation first</option>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-primary ms-2" onclick="openAddPicModal()">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <small id="pic_details" class="form-text text-muted" style="display: none;"></small>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group" id="remove_date_group" style="display: none;">
                                <label class="form-label" for="remove_date">Remove Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-input" id="remove_date" name="remove_date">
                            </div>
                        </div>
                        <div class="form-row full-width">
                            <div class="form-group">
                                <label class="form-label" for="notes">Catatan Tambahan</label>
                                <textarea class="form-textarea" id="notes" name="notes" rows="3" placeholder="Masukkan catatan tambahan..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        `;
        const modalFooter = document.getElementById('modalFooter');
        if (modalFooter) {
            modalFooter.innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitForm(event)">Create Job Advice</button>
            `;
        }
        
        loadUsers();
    };

// Function to format date with time (3-digit month as requested: 010 for October)
function formatDateWithThreeDigitMonth(dateInput) {
    if (!dateInput) {
        return 'N/A';
    }
    
    // Convert to Date object if string
    const date = dateInput instanceof Date ? dateInput : new Date(dateInput);
    
    // Validate date
    if (isNaN(date.getTime())) {
        return 'N/A';
    }
    
    // Get date components in WIB timezone (Asia/Jakarta = UTC+7)
    // Use toLocaleString to get WIB time, then parse components
    const options = {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    };
    
    const formatter = new Intl.DateTimeFormat('en-GB', options);
    const parts = formatter.formatToParts(date);
    
    const day = parts.find(p => p.type === 'day').value;
    const monthNum = parts.find(p => p.type === 'month').value;
    const month = monthNum.padStart(3, '0'); // 3 digits: 010 for October
    const year = parts.find(p => p.type === 'year').value;
    const hour = parts.find(p => p.type === 'hour').value;
    const minute = parts.find(p => p.type === 'minute').value;
    
    return `${day}/${month}/${year} ${hour}:${minute}`;
}

function dateValueForInput(dateInput) {
    if (!dateInput) return '';

    if (typeof dateInput === 'string') {
        const match = dateInput.match(/^(\d{4}-\d{2}-\d{2})$/);
        if (match) return match[1];
    }

    const date = dateInput instanceof Date ? dateInput : new Date(dateInput);
    if (Number.isNaN(date.getTime())) return '';

    const parts = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).formatToParts(date);

    const year = parts.find(p => p.type === 'year').value;
    const month = parts.find(p => p.type === 'month').value;
    const day = parts.find(p => p.type === 'day').value;

    return `${year}-${month}-${day}`;
}

    // DOMContentLoaded wrapper for initial page listeners
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const selectAllHeader = document.getElementById('selectAllHeader');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');
                checkboxes.forEach(checkbox => { checkbox.checked = this.checked; });
                if (selectAllHeader) selectAllHeader.checked = this.checked;
            });
        }

        if (selectAllHeader) {
            selectAllHeader.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');
                checkboxes.forEach(checkbox => { checkbox.checked = this.checked; });
                if (selectAll) selectAll.checked = this.checked;
            });
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('row-checkbox')) {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                const selectAllCheckbox = document.getElementById('selectAll');
                const headerSelectAllCheckbox = document.getElementById('selectAllHeader');
                
                const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
                const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
                
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = anyChecked && !allChecked;
                }
                if (headerSelectAllCheckbox) {
                    headerSelectAllCheckbox.checked = allChecked;
                    headerSelectAllCheckbox.indeterminate = anyChecked && !allChecked;
                }
            }
        });
    });

// Delete selected function
function deleteSelected() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one job advice to hide');
        return;
    }
    
    selectedIdsForRetry = Array.from(checkboxes).map(cb => cb.value);
    openDeleteModal();
}

// Function to apply filters
function applyFilters() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    let url = '{{ route("marketing.job-advices.index") }}?';
    
    if (startDate) url += `start_date=${startDate}&`;
    if (endDate) url += `end_date=${endDate}&`;

    window.location.href = url;
}

function clearFilters() {
    // Clear Flatpickr inputs
    const startPicker = document.querySelector("#start_date")._flatpickr;
    const endPicker = document.querySelector("#end_date")._flatpickr;
    
    if (startPicker) startPicker.clear();
    if (endPicker) endPicker.clear();
    
    // Redirect to base URL (this will trigger auto-set logic in DOMContentLoaded)
    window.location.href = '{{ route("marketing.job-advices.index") }}';
}


// Function to load quotations
function loadQuotations(marketingId = null) {
    console.log('Loading quotations for marketing:', marketingId);
    
    const quotationSelect = document.getElementById('quotation_id');
    if (!quotationSelect) return;
    
    // Clear and disable if no marketing selected (unless Admin)
    if (!marketingId && !isAdmin) {
        quotationSelect.innerHTML = '<option value="">Select Marketing First</option>';
        quotationSelect.disabled = true;
        return;
    }
    
    quotationSelect.disabled = false;
    quotationSelect.innerHTML = '<option value="">Loading...</option>';
    
    // Build URL with marketing filter if provided
    let url = `/api/quotations/dropdown?status=approved`;
    if (marketingId) {
        url += `&marketing_id=${marketingId}`;
    }
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        console.log(`Quotations data:`, data);
        quotationSelect.innerHTML = '<option value="">Select Quotation</option>';
        
        const quotations = data.data || [];
        
        if (quotations.length > 0) {
            quotations.forEach(quotation => {
                const option = document.createElement('option');
                option.value = quotation.id;
                option.setAttribute('data-customer-id', quotation.customer_id || '');
                
                const customerName = quotation.customer_name || 'N/A';
                option.textContent = `${quotation.quotation_number} - ${customerName}`;
                quotationSelect.appendChild(option);
            });
        } else {
            quotationSelect.innerHTML = '<option value="">No Approved Quotations Found</option>';
        }

        // Initialize/Refresh Select2 for Quotations
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $(quotationSelect).select2({
                dropdownParent: $('#modalOverlay'),
                placeholder: 'Select Quotation',
                allowClear: true,
                width: '100%'
            }).trigger('change.select2'); // Refresh display
        }
    })
    .catch(error => {
        console.error(`Error loading quotations:`, error);
        quotationSelect.innerHTML = '<option value="">Error Loading Quotations</option>';
    });
}

// MOM9: Toggle between Contract and Quotation source
window.toggleSourceType = function() {
    const sourceType = document.querySelector('input[name="source_type"]:checked')?.value;
    const contractGroup = document.getElementById('contract_group');
    const quotationGroup = document.getElementById('quotation_group');
    const contractSelect = document.getElementById('contract_id');
    const quotationSelect = document.getElementById('quotation_id');
    const typeSelect = document.getElementById('modal_type');
    
    if (sourceType === 'quotation') {
        // Show quotation, hide contract
        if (contractGroup) contractGroup.style.display = 'none';
        if (quotationGroup) quotationGroup.style.display = 'block';
        if (contractSelect) {
            contractSelect.removeAttribute('required');
            contractSelect.value = '';
        }
        if (quotationSelect) quotationSelect.setAttribute('required', 'required');
        
        // Auto-set type to install_free if not set
        if (typeSelect && !typeSelect.value) {
            typeSelect.value = 'install_free';
            toggleRemoveDate();
        }
    } else {
        // Show contract, hide quotation
        if (contractGroup) contractGroup.style.display = 'block';
        if (quotationGroup) quotationGroup.style.display = 'none';
        if (quotationSelect) {
            quotationSelect.removeAttribute('required');
            quotationSelect.value = '';
        }
        if (contractSelect) contractSelect.setAttribute('required', 'required');
    }
}

// Function to toggle Remove Date visibility
window.toggleRemoveDate = function() {
    const typeSelect = document.getElementById('modal_type');
    const removeDateGroup = document.getElementById('remove_date_group');
    const removeDateInput = document.getElementById('remove_date');
    const sourceContractRadio = document.getElementById('source_contract');
    const sourceQuotationRadio = document.getElementById('source_quotation');
    
    if (typeSelect && removeDateGroup) {
        const type = typeSelect.value;
        if (type === 'install_free' || type === 'install free') {
            removeDateGroup.style.display = 'block';
            if (removeDateInput) removeDateInput.setAttribute('required', 'required');
            
            // Auto-select Quotation source for Install Free
            if (sourceQuotationRadio) {
                sourceQuotationRadio.checked = true;
                toggleSourceType(); // Switch to quotation view
            }
        } else {
            removeDateGroup.style.display = 'none';
            if (removeDateInput) {
                removeDateInput.value = '';
                removeDateInput.removeAttribute('required');
            }
            
            // Auto-select Contract source for non-Install Free types
            if (sourceContractRadio) {
                sourceContractRadio.checked = true;
                toggleSourceType(); // Switch to contract view
            }
        }

        // MOM: Reload contract rooms if contract is selected (to reload rooms filter based on new type)
        const contractSelect = document.getElementById('contract_id');
        if (contractSelect && contractSelect.value) {
            console.log('Type changed, reloading contract rooms...');
            contractSelect.dispatchEvent(new Event('change'));
        }
    }
}

// Function to load contracts
function loadContracts(marketingId = null) {
    console.log('Loading contracts for marketing:', marketingId);
    
    const contractSelect = document.getElementById('contract_id');
    if (!contractSelect) return;
    
    // Clear and disable if no marketing selected (unless Admin)
    if (!marketingId && !isAdmin) {
        contractSelect.innerHTML = '<option value="">Select Marketing First</option>';
        contractSelect.disabled = true;
        return;
    }
    
    contractSelect.disabled = false;
    contractSelect.innerHTML = '<option value="">Loading...</option>';
    
    // Build URL with marketing filter if provided
    const baseUrl = '/api/contracts/dropdown';
    let url = `${baseUrl}?status=active&for_job_advice=1`;
    if (marketingId) {
        url += `&marketing_id=${marketingId}`;
    }
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log(`Response status:`, response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log(`Contracts data:`, data);
        contractSelect.innerHTML = '<option value="">Select Contract</option>';
        
        // Handle different response formats
        let contracts = [];
        if (data.data && Array.isArray(data.data)) {
            contracts = data.data;
        } else if (Array.isArray(data)) {
            contracts = data;
        } else if (data.contracts && Array.isArray(data.contracts)) {
            contracts = data.contracts;
        }
        
        // Filter contracts by marketing_id and active status
        console.log('Raw contracts data:', contracts);
        console.log('Filtering for marketing_id:', marketingId);
        
        const filteredContracts = contracts.filter(function(contract) {
            const hasMarketing = contract.created_by == marketingId || contract.marketing_id == marketingId;
            const isActive = contract.status === 'active' || contract.contract_status === 'active';
            return hasMarketing && isActive;
        });
        
        if (filteredContracts.length > 0) {
            filteredContracts.forEach(contract => {
                const option = document.createElement('option');
                option.value = contract.id;
                // Store customer_id in data attribute
                option.setAttribute('data-customer-id', contract.customer_id || contract.customer?.id || '');
                
                const customerName = contract.customer?.name || contract.customer_name || 'N/A';
                option.textContent = `${contract.contract_number} - ${customerName}`;
                contractSelect.appendChild(option);
            });
            console.log(`Loaded ${filteredContracts.length} active contracts for marketing ${marketingId}`);
        } else {
            contractSelect.innerHTML = '<option value="">No Active Contracts Found</option>';
            console.log(`No active contracts found for marketing ${marketingId}`);
        }

        // Initialize/Refresh Select2 for Contracts
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            $(contractSelect).select2({
                dropdownParent: $('#modalOverlay'),
                placeholder: 'Select Contract',
                allowClear: true,
                width: '100%'
            }).trigger('change.select2'); // Refresh display
        }
    })
    .catch(error => {
        console.error(`Error loading contracts:`, error);
        contractSelect.innerHTML = '<option value="">Error Loading Contracts</option>';
    });
}

// Function to load users for Request By dropdown
function loadUsers() {
    console.log('Loading marketing users...');
    
    // Try to load from backend
    fetch('/marketing/users/marketing-list', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('API not available, using fallback');
        }
        return response.json();
    })
    .then(data => {
        console.log('Marketing users data:', data);
        populateMarketingUsers(data);
    })
    .catch(error => {
        console.log('Using hardcoded marketing users fallback');
        // Fallback: use current user only
        const userSelect = document.getElementById('request_by');
        if (userSelect) {
            userSelect.innerHTML = '<option value="">Select Marketing</option>';
            const currentUserName = @json(auth()->user()->name);
            userSelect.innerHTML += `<option value="{{ auth()->id() }}" selected>${currentUserName}</option>`;
            
            // Auto load contracts and quotations for current user
            loadContracts('{{ auth()->id() }}');
            loadQuotations('{{ auth()->id() }}');
        }
    });
}

function populateMarketingUsers(data) {
    const userSelect = document.getElementById('request_by');
    if (!userSelect) return;
    
    userSelect.innerHTML = '<option value="">Select Marketing</option>';
    
    // Handle different response formats
    let users = [];
    let currentUserIdFromApi = null;
    
    if (data.data && Array.isArray(data.data)) {
        users = data.data;
        currentUserIdFromApi = data.current_user_id || null;
    } else if (Array.isArray(data)) {
        users = data;
    } else if (data.users && Array.isArray(data.users)) {
        users = data.users;
    }
    
    const currentUserId = '{{ auth()->id() }}';
    
    // If no users returned from API, add current user as fallback
    if (users.length === 0) {
        console.log('No marketing users from API, using current user as fallback');
        users = [{
            id: currentUserId,
            name: @json(auth()->user()->name),
            role: 'Marketing',
            is_current_user: true
        }];
    }
    
    // Display all users (backend already filters to marketing users)
    users.forEach(user => {
        const option = document.createElement('option');
        option.value = user.id;
        // Show name with role
        const roleDisplay = user.role ? ` (${user.role})` : '';
        option.textContent = user.name + roleDisplay;
        
        // Select current user by default
        if (user.is_current_user || user.id == currentUserId) {
            option.selected = true;
        }
        userSelect.appendChild(option);
    });
    
    // Ensure current user is selected and load their contracts and quotations
    if (currentUserId) {
        userSelect.value = currentUserId;
        loadContracts(currentUserId);
        loadQuotations(currentUserId);
    }
    
    console.log(`Loaded ${users.length} marketing users, current user: ${currentUserId}`);
}

// MOM6: Global variables for rooms
let contractRooms = [];
let rentalProducts = [];
let roomRowCounter = 0;

// MOM6: Load contract rooms when contract is selected
// Revised to use jQuery for better compatibility with Select2 and PIC/Notes issues
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined') {
        $(document).on('change', '#contract_id', function(e) {
    const contractId = $(this).val();
    console.log('Contract selected (jQuery):', contractId);
    
    sourceType = 'contract'; // MOM9: Set source type
    
    const roomsSection = document.getElementById('roomsSection');
    const roomsContainer = document.getElementById('roomsContainer');
    
    if (!contractId) {
        if (roomsSection) roomsSection.style.display = 'none';
        if (roomsContainer) roomsContainer.innerHTML = '';
        contractRooms = [];
        return;
    }
    
    // Get customerId from selected option data attribute
    const selectedOption = $(this).find('option:selected');
    const customerId = selectedOption.attr('data-customer-id');
    console.log('Detected customerId:', customerId);
    
    // Load PIC immediately
    if (customerId) {
        loadCustomerContacts(customerId);
    }
    
    // MOM: Pass job advice type to allow filtering logic
    const typeSelect = document.getElementById('modal_type');
    const typeValue = typeSelect ? typeSelect.value : '';
    
    // Show partial loading for rooms
    if (roomsContainer) roomsContainer.innerHTML = '<p class="text-sm text-gray-500 py-2">Loading rooms...</p>';
    if (roomsSection) roomsSection.style.display = 'block';

    Promise.all([
        fetch(`/api/contracts/${contractId}/for-job-advice?type=${encodeURIComponent(typeValue)}`).then(r => r.json()),
        fetch('/warehouse/rental-products/dropdown').then(r => r.json()).catch(() => ({ data: [] }))
    ]).then(([contractData, rentalsData]) => {
        console.log('Contract data received:', contractData);
        
        // Show Sales Notes IMMEDIATELY!
        const showSalesNotes = () => {
            const notesSales = contractData.notes_sales || contractData.data?.notes_sales || contractData.sales_notes || contractData.data?.sales_notes;
            if (notesSales && notesSales.trim() !== '') {
                setTimeout(() => {
                    Swal.fire({
                        title: '<strong>Sales Note</strong>',
                        icon: 'info',
                        html: `<div class="text-left" style="white-space: pre-line;">${notesSales}</div>`,
                        showCloseButton: true,
                        focusConfirm: false,
                        allowOutsideClick: false,
                        confirmButtonText: '<i class="fa fa-thumbs-up"></i> Acknowledge',
                        confirmButtonAriaLabel: 'Thumbs up, acknowledge!',
                        customClass: {
                            container: 'my-swal-container',
                            popup: 'my-swal-popup',
                            content: 'text-left'
                        }
                    });
                }, 100);
            }
        };
        showSalesNotes();

        // Store contract rooms
        contractRooms = contractData.contract_rooms || contractData.contractRooms || contractData.data?.contract_rooms || contractData.data?.contractRooms || [];
        
        // MOM9: Clear quotation rooms when contract is selected
        quotationRooms = [];
        
        // Store rental products
        rentalProducts = rentalsData.data || rentalsData || [];
        
        // Format contract rooms
        if (contractRooms && contractRooms.length > 0) {
            contractRooms = contractRooms.map(cr => ({
                id: cr.id,
                contract_room_id: cr.id,
                room_id: cr.room_id || cr.room?.id,
                room_name: cr.room?.room_name || cr.room_name || 'Room ' + cr.id,
                building_name: cr.room?.building?.nama_gedung || cr.room?.building?.name || 'N/A',
                rental_product_id: cr.rental_product_id,
                has_active_unit: cr.has_active_unit,
                active_sn: cr.active_sn
            }));
        }
        
        if (contractRooms.length > 0) {
            if (roomsSection) roomsSection.style.display = 'block';
            if (roomsContainer) {
                roomsContainer.innerHTML = '';
                addRoomRow();
            }
        } else {
            if (roomsSection) roomsSection.style.display = 'none';
            if (roomsContainer) roomsContainer.innerHTML = '';
            
            const contractNumber = contractData.contract_number || contractData.data?.contract_number || '';
            Swal.fire({
                icon: 'warning',
                title: 'Contract Tidak Memiliki Rooms',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Contract ${contractNumber}</strong> belum memiliki ruangan (rooms) yang terdaftar.</p>
                        ${contractData.broken_rooms > 0 ? `<p style="color: #ef4444;"><strong>⚠️ Perhatian:</strong> ${contractData.message || `Contract memiliki ${contractData.broken_rooms} ruangan yang tidak valid.`}</p>` : ''}
                    </div>
                `,
                confirmButtonText: 'OK',
            });
        }
    }).catch(error => {
        console.error('Error loading contract data:', error);
        });
    });
}
});


// MOM9: Load quotation rooms when quotation is selected (for Install Free)
let quotationRooms = [];
let sourceType = 'contract'; // Track if we're using contract or quotation

document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined') {
        $(document).on('change', '#quotation_id', function(e) {
    const quotationId = $(this).val();
    console.log('Quotation selected (jQuery):', quotationId);
    
    sourceType = 'quotation';
    
    const roomsSection = document.getElementById('roomsSection');
    const roomsContainer = document.getElementById('roomsContainer');
    
    if (!quotationId) {
        if (roomsSection) roomsSection.style.display = 'none';
        if (roomsContainer) roomsContainer.innerHTML = '';
        quotationRooms = [];
        return;
    }
    
    // Get customerId from selected option
    const selectedOption = $(this).find('option:selected');
    const customerId = selectedOption.attr('data-customer-id');
    console.log('Detected customerId (quotation):', customerId);
    
    // Load PIC immediately
    if (customerId) {
        loadCustomerContacts(customerId);
    }

    // Show partial loading
    if (roomsContainer) roomsContainer.innerHTML = '<p class="text-sm text-gray-500 py-2">Loading rooms...</p>';
    if (roomsSection) roomsSection.style.display = 'block';

    // Load quotation rooms and rental products
        Promise.all([
            fetch(`/api/quotations/${quotationId}/for-job-advice`).then(r => r.json()),
            fetch('/warehouse/rental-products/dropdown').then(r => r.json()).catch(() => ({ data: [] }))
        ]).then(([quotationData, rentalsData]) => {
            console.log('Quotation data:', quotationData);
            console.log('Rentals data:', rentalsData);
            
            // Store quotation rooms
            quotationRooms = quotationData.quotation_rooms || quotationData.quotationRooms || quotationData.data?.quotation_rooms || [];
            
            // Also update contractRooms to empty since we're using quotation
            contractRooms = [];
            
            // Store rental products
            rentalProducts = rentalsData.data || rentalsData || [];
            
            if (quotationRooms.length > 0) {
                if (roomsSection) roomsSection.style.display = 'block';
                // Clear existing room rows and add new ones
                if (roomsContainer) {
                    roomsContainer.innerHTML = '';
                    roomRowCounter = 0;
                    addRoomRow();
                }
            } else {
                if (roomsSection) roomsSection.style.display = 'none';
                const quotationNumber = quotationData.quotation_number || '';
                Swal.fire({
                    icon: 'warning',
                    title: 'Quotation Tidak Memiliki Rooms',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>Quotation ${quotationNumber}</strong> belum memiliki ruangan (rooms) yang dipilih.</p>
                            ${quotationData.broken_rooms > 0 ? `<p style="color: #ef4444;"><strong>⚠️ Perhatian:</strong> ${quotationData.message || `Quotation memiliki ${quotationData.broken_rooms} ruangan yang tidak valid.`}</p>` : ''}
                            <br>
                            <p><strong>Langkah selanjutnya:</strong></p>
                            <ol style="margin-left: 20px;">
                                <li>Buka menu <strong>Quotation</strong></li>
                                <li>Edit quotation tersebut</li>
                                <li>Pastikan ada <strong>Rooms</strong> yang dipilih</li>
                                <li>Simpan perubahan</li>
                                <li>Kembali ke halaman ini untuk membuat Job Advice</li>
                            </ol>
                        </div>
                    `,
                    confirmButtonText: 'OK, Mengerti',
                    confirmButtonColor: '#3085d6'
                });
            }
        }).catch(error => {
            console.error('Error loading quotation data:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error Loading Quotation',
                html: `
                    <p>Terjadi kesalahan saat memuat data quotation.</p>
                    <p class="text-muted">${error.message || 'Unknown error'}</p>
                `,
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            });
        });
    });
}
});


// Update source type when radio changes
document.addEventListener('change', function(e) {
    if (e.target && e.target.name === 'source_type') {
        sourceType = e.target.value;
        console.log('Source type changed to:', sourceType);
        
        // Clear rooms when switching source
        const roomsContainer = document.getElementById('roomsContainer');
        if (roomsContainer) roomsContainer.innerHTML = '';
        contractRooms = [];
        quotationRooms = [];
        roomRowCounter = 0;
    }
});

// MOM6 & MOM9: Add room row (updated to handle both contract and quotation rooms)
function addRoomRow() {
    roomRowCounter++;
    const rowId = `room-row-${roomRowCounter}`;
    
    // Determine which rooms to use based on source type
    const isQuotationSource = sourceType === 'quotation' || quotationRooms.length > 0;
    const roomsToUse = isQuotationSource ? quotationRooms : contractRooms;
    const roomFieldName = isQuotationSource ? 'quotation_room_id' : 'contract_room_id';
    const roomLabel = isQuotationSource ? 'Quotation Room' : 'Contract Room';
    
    let roomOptions = '<option value="">Select Room</option>';
    roomsToUse.forEach(room => {
        const roomName = room.room_name || room.room?.room_name || 'Room ' + room.id;
        const buildingName = room.room?.building?.nama_gedung || room.room?.building?.name || room.building_name || '';
        const displayName = buildingName ? `${roomName} (${buildingName})` : roomName;
        roomOptions += `<option value="${room.id}" data-rental-id="${room.rental_product_id || ''}">${displayName}</option>`;
    });
    
    let rentalOptions = '<option value="">Select Rental Product</option>';
    rentalProducts.forEach(rental => {
        rentalOptions += `<option value="${rental.id}">${rental.rental_name || rental.name}</option>`;
    });
    
    const roomRow = `
        <div class="room-row" id="${rowId}" style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px; margin-bottom: 15px; background-color: white;">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">${roomLabel} *</label>
                    <select class="form-select room-select" name="rooms[${roomRowCounter}][${roomFieldName}]" required onchange="loadRoomDetails(this, ${roomRowCounter})">
                        ${roomOptions}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Rental Product *</label>
                    <select class="form-select rental-product-select" name="rooms[${roomRowCounter}][rental_product_id]" required>
                        ${rentalOptions}
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Quantity *</label>
                    <input type="number" class="form-input" name="rooms[${roomRowCounter}][quantity]" value="1" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Is Trial?</label>
                    <select class="form-select" name="rooms[${roomRowCounter}][is_trial]">
                        <option value="0">No</option>
                        <option value="1" ${isQuotationSource ? 'selected' : ''}>Yes (Skip install if unit exists)</option>
                    </select>
                </div>
            </div>
            <div class="form-row full-width">
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-textarea" name="rooms[${roomRowCounter}][notes]" rows="2" placeholder="Optional notes for this room..."></textarea>
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeRoomRow('${rowId}')" style="margin-top: 10px;">
                <i class="fas fa-trash"></i> Remove Room
            </button>
        </div>
    `;
    
    const roomsContainer = document.getElementById('roomsContainer');
    if (roomsContainer) {
        roomsContainer.insertAdjacentHTML('beforeend', roomRow);
    }
}

// MOM6: Load room details
function loadRoomDetails(selectElement, rowId) {
    const roomId = selectElement.value;
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const rentalId = selectedOption.getAttribute('data-rental-id');
    
    if (rentalId) {
        // Auto-select rental product if available
        const rentalSelect = document.querySelector(`select[name="rooms[${rowId}][rental_product_id]"]`);
        if (rentalSelect) {
            rentalSelect.value = rentalId;
        }
    }

    // User Request: "satu ruangan bisa lebih dari 1 unit dari contract/quotation yg berbeda"
    // Validasi unit aktif dihapus agar ruangan tetap bisa dipilih.
}

// MOM6: Remove room row
function removeRoomRow(rowId) {
    const roomRow = document.getElementById(rowId);
    if (roomRow) {
        roomRow.remove();
    }
    
    // If no rooms left, hide section
    const roomsContainer = document.getElementById('roomsContainer');
    const roomsSection = document.getElementById('roomsSection');
    if (roomsContainer && roomsContainer.children.length === 0 && roomsSection) {
        roomsSection.style.display = 'none';
    }
}

function openViewModal(id) {
    openModal('View Job Advice');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/marketing/job-advices/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <div class="modal-form-container">
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Basic Information</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Job Advice Number</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.job_advice_number || 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Type</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">
                                    <span class="px-2 py-1 text-xs rounded-full ${data.type === 'install' ? 'bg-blue-100 text-blue-800' : (data.type === 'remove' ? 'bg-red-100 text-red-800' : (data.type === 'service' ? 'bg-green-100 text-green-800' : (data.type === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')))}">
                                        ${data.type ? data.type.charAt(0).toUpperCase() + data.type.slice(1) : 'N/A'}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Reference Number</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.reference_number || 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Name</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.contract && data.contract.customer ? data.contract.customer.name : (data.company_name || 'N/A')}</div>
                            </div>
                        </div>
                    </div>
                    
                    ${data.rooms && data.rooms.length > 0 ? `
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Rental Rooms (${data.rooms.length})</div>
                        ${data.rooms.map((room, index) => `
                            <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 10px; background-color: #f9fafb;">
                                <div style="font-weight: 600; color: #214589; margin-bottom: 8px;">
                                    <i class="fas fa-door-open"></i> ${room.room_name || 'Room ' + (index + 1)}
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 12px; color: #6b7280;">Rental Product</label>
                                        <div style="font-size: 14px;">${room.rental_name || 'N/A'}</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 12px; color: #6b7280;">Quantity</label>
                                        <div style="font-size: 14px;">${room.quantity || 1} unit(s)</div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 12px; color: #6b7280;">Status</label>
                                        <div style="font-size: 14px;">
                                            <span class="px-2 py-1 text-xs rounded-full ${room.status === 'completed' ? 'bg-green-100 text-green-800' : (room.status === 'scheduled' ? 'bg-blue-100 text-blue-800' : (room.status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'))}">
                                                ${room.status ? room.status.charAt(0).toUpperCase() + room.status.slice(1) : 'Pending'}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" style="font-size: 12px; color: #6b7280;">Trial</label>
                                        <div style="font-size: 14px;">${room.is_trial ? 'Yes' : 'No'}</div>
                                    </div>
                                </div>
                                ${room.notes ? `
                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb;">
                                    <label class="form-label" style="font-size: 12px; color: #6b7280;">Notes</label>
                                    <div style="font-size: 13px; color: #6b7280;">${room.notes}</div>
                                </div>
                                ` : ''}
                            </div>
                        `).join('')}
                    </div>
                    ` : ''}
                    
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Schedule & Status</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Expected Date</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.expected_date ? formatDateWithThreeDigitMonth(new Date(data.expected_date)) : 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Remove Date</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.remove_date ? formatDateWithThreeDigitMonth(new Date(data.remove_date)) : 'N/A'}</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">
                                    <span class="px-2 py-1 text-xs rounded-full ${data.status === 'draft' ? 'bg-yellow-100 text-yellow-800' : (data.status === 'submitted' ? 'bg-blue-100 text-blue-800' : (data.status === 'approved' ? 'bg-green-100 text-green-800' : (data.status === 'rejected' ? 'bg-red-100 text-red-800' : (data.status === 'completed' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'))))}">
                                        ${data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'N/A'}
                                    </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Submitted By</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.submitted_by ? data.submitted_by.name : 'N/A'}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Approval Information</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Date Approval</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.date_approval ? formatDateWithThreeDigitMonth(new Date(data.date_approval)) : 'N/A'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Approved By</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.approved_by ? data.approved_by.name : 'N/A'}</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">With Invoicing</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.with_invoicing ? 'Yes' : 'No'}</div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">With Materials</label>
                                <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.with_materials ? 'Yes' : 'No'}</div>
                            </div>
                        </div>
                    </div>
                    
                    ${data.notes ? `
                    <div class="modal-form-section">
                        <div class="modal-form-section-title">Additional Information</div>
                        <div class="form-row full-width">
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <div class="form-textarea" style="background-color: #f9fafb; color: #374151; min-height: 80px;">${data.notes}</div>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
            
            // Set modal footer - Dynamic buttons based on status
            let footerButtons = `<button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>`;
            
            // Status-based workflow buttons
            if (data.status === 'draft') {
                // Draft: Show Submit button
                footerButtons += `
                    <button type="button" class="btn btn-primary" onclick="submitJobAdvice(${id})" style="background-color: #3b82f6; border-color: #3b82f6;">
                        <i class="fas fa-paper-plane"></i> Submit for Approval
                    </button>
                `;
            } else if (data.status === 'submitted') {
                // Submitted: Show Approve & Reject buttons
                footerButtons += `
                    <button type="button" class="btn btn-success" onclick="approveJobAdvice(${id})" style="background-color: #10b981; border-color: #10b981;">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger" onclick="rejectJobAdvice(${id})" style="background-color: #ef4444; border-color: #ef4444;">
                        <i class="fas fa-times"></i> Reject
                    </button>
                `;
            }
            
            // Always show Edit button (except for approved/rejected status)
            if (data.status !== 'approved' && data.status !== 'rejected' && data.status !== 'completed') {
                footerButtons += `<button type="button" class="btn btn-primary" onclick="openEditModal(${id})">Edit</button>`;
            }
            
            document.getElementById('modalFooter').innerHTML = footerButtons;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading job advice details.</div>';
        });
}

// Submit Job Advice for Approval
function submitJobAdvice(id) {
    Swal.fire({
        title: 'Submit for Approval',
        html: `
            <p>Apakah Anda yakin ingin <strong>mengirim</strong> Job Advice ini untuk disetujui?</p>
            <p class="text-sm text-gray-600 mt-2">Setelah di-submit, Job Advice akan menunggu persetujuan.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-paper-plane"></i> Ya, Submit',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/marketing/job-advices/${id}/submit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    submitted_by: {{ auth()->id() }}
                })
            })
            .then(response => response.json().then(data => ({ status: response.status, ok: response.ok, data })))
            .then(({ status, ok, data }) => {
                if (!ok) {
                    throw new Error(data.message || `Server error: ${status}`);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message || error}`);
                throw error;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Submitted!',
                text: 'Job Advice telah dikirim untuk persetujuan.',
                confirmButtonColor: '#3085d6',
            }).then(() => {
                closeModal();
                location.reload(); // Refresh page to see updated status
            });
        }
    });
}

// Approve Job Advice
function approveJobAdvice(id) {
    Swal.fire({
        title: 'Approve Job Advice',
        html: `
            <p>Apakah Anda yakin ingin <strong>menyetujui</strong> Job Advice ini?</p>
            <p class="text-sm text-gray-600 mt-2">Setelah disetujui, Job Schedule akan otomatis dibuat.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-check"></i> Ya, Approve',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(`/marketing/job-advices/${id}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    approved_by: {{ auth()->id() }}
                })
            })
            .then(response => response.json().then(data => ({ status: response.status, ok: response.ok, data })))
            .then(({ status, ok, data }) => {
                if (!ok) {
                    throw new Error(data.message || `Server error: ${status}`);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message || error}`);
                throw error;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Approved!',
                text: 'Job Advice telah disetujui dan Job Schedule sudah dibuat.',
                confirmButtonColor: '#3085d6',
            }).then(() => {
                closeModal();
                location.reload(); // Refresh page to see updated status
            });
        }
    });
}

// Reject Job Advice
function rejectJobAdvice(id) {
    Swal.fire({
        title: 'Reject Job Advice',
        html: `
            <p>Apakah Anda yakin ingin <strong>menolak</strong> Job Advice ini?</p>
            <div class="mt-4">
                <label class="block text-left text-sm font-medium text-gray-700 mb-2">Alasan Penolakan:</label>
                <textarea id="rejection_reason" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="3" placeholder="Masukkan alasan penolakan..."></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-times"></i> Ya, Reject',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const reason = document.getElementById('rejection_reason').value;
            if (!reason) {
                Swal.showValidationMessage('Alasan penolakan harus diisi!');
                return false;
            }
            
            return fetch(`/marketing/job-advices/${id}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    rejection_reason: reason,
                    rejected_by: {{ auth()->id() }}
                })
            })
            .then(response => response.json().then(data => ({ status: response.status, ok: response.ok, data })))
            .then(({ status, ok, data }) => {
                if (!ok) {
                    throw new Error(data.message || `Server error: ${status}`);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message || error}`);
                throw error;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Rejected!',
                text: 'Job Advice telah ditolak.',
                confirmButtonColor: '#3085d6',
            }).then(() => {
                closeModal();
                location.reload(); // Refresh page to see updated status
            });
        }
    });
}

function openEditModal(id) {
    openModal('Edit Job Advice');
    document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div><p style="margin-top: 16px; color: #666;">Loading...</p></div>';
    
    fetch(`/marketing/job-advices/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalBody').innerHTML = `
                <form id="jobAdviceForm" onsubmit="submitForm(event, ${id})">
                    <div class="modal-form-container">
                        <div class="modal-form-section">
                            <div class="modal-form-section-title">Basic Information</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="edit_job_advice_number">Job Advice Number</label>
                                    <input type="text" class="form-input" id="edit_job_advice_number" name="job_advice_number" value="${data.job_advice_number || ''}" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="edit_type">Type</label>
                                    <select class="form-select" id="edit_type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="install" ${data.type === 'install' ? 'selected' : ''}>Install</option>
                                        <option value="service" ${data.type === 'service' ? 'selected' : ''}>Service</option>
                                        <option value="remove" ${data.type === 'remove' ? 'selected' : ''}>Remove</option>
                                        <option value="maintenance" ${data.type === 'maintenance' ? 'selected' : ''}>Maintenance</option>
                                        <option value="change_rental" ${data.type === 'change_rental' ? 'selected' : ''}>Change Rental</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="edit_contract_id">Contract</label>
                                    <input type="text" class="form-input" id="edit_contract_id" name="contract_id" value="${data.contract_id || ''}" readonly style="background-color: #f9fafb; color: #6b7280;">
                                    <small class="text-gray-500 text-xs">Contract cannot be changed after Job Advice is created</small>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="edit_reference_number">Reference Number</label>
                                    <input type="text" class="form-input" id="edit_reference_number" name="reference_number" value="${data.reference_number || ''}">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="edit_expected_date">Expected Date</label>
                                    <input type="date" class="form-input" id="edit_expected_date" name="expected_date" value="${dateValueForInput(data.expected_date)}" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-form-section">
                            <div class="modal-form-section-title">Schedule & Status</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="edit_remove_date">Remove Date</label>
                                    <input type="date" class="form-input" id="edit_remove_date" name="remove_date" value="${dateValueForInput(data.remove_date)}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="edit_status">Status</label>
                                    <select class="form-select" id="edit_status" name="status" required>
                                        <option value="draft" ${data.status === 'draft' ? 'selected' : ''}>Draft</option>
                                        <option value="submitted" ${data.status === 'submitted' ? 'selected' : ''}>Submitted</option>
                                        <option value="approved" ${data.status === 'approved' ? 'selected' : ''}>Approved</option>
                                        <option value="rejected" ${data.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                                        <option value="completed" ${data.status === 'completed' ? 'selected' : ''}>Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="edit_with_invoicing">With Invoicing</label>
                                    <select class="form-select" id="edit_with_invoicing" name="with_invoicing">
                                        <option value="0" ${!data.with_invoicing ? 'selected' : ''}>No</option>
                                        <option value="1" ${data.with_invoicing ? 'selected' : ''}>Yes</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="edit_with_materials">With Materials</label>
                                    <select class="form-select" id="edit_with_materials" name="with_materials">
                                        <option value="0" ${!data.with_materials ? 'selected' : ''}>No</option>
                                        <option value="1" ${data.with_materials ? 'selected' : ''}>Yes</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-form-section">
                            <div class="modal-form-section-title">Approval Information</div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Date Approval</label>
                                    <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.date_approval ? formatDateWithThreeDigitMonth(new Date(data.date_approval)) : 'N/A'}</div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Approved By</label>
                                    <div class="form-input" style="background-color: #f9fafb; color: #374151;">${data.approved_by ? data.approved_by.name : 'N/A'}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-form-section">
                            <div class="modal-form-section-title">Additional Information</div>
                            <div class="form-row full-width">
                                <div class="form-group">
                                    <label class="form-label" for="edit_notes">Notes</label>
                                    <textarea class="form-textarea" id="edit_notes" name="notes" rows="3" placeholder="Enter notes...">${data.notes || ''}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            `;
    document.getElementById('modalFooter').innerHTML = `
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" form="jobAdviceForm">Update Job Advice</button>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('modalBody').innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">Error loading job advice details.</div>';
        });
}

window.submitForm = function(event, id = null) {
    event.preventDefault();
    
    // Fix: Get form by ID since event target might be the button
    const form = document.getElementById('jobAdviceForm');
    if (!form) {
        console.error('Job Advice form not found!');
        return;
    }
    
    // Check form validity (since we removed onsubmit default validation)
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // MOM9: Handle source type (Contract or Quotation)
    const sourceType = document.querySelector('input[name="source_type"]:checked')?.value;
    if (sourceType === 'quotation') {
        // Clear contract_id if quotation is selected
        data.contract_id = '';
        // Ensure quotation_id is set
        if (!data.quotation_id) {
            alert('Please select a quotation.');
            return;
        }
        // Auto-set type to install_free if from quotation
        if (!data.type || data.type === '') {
            data.type = 'install_free';
        }
    } else {
        // Clear quotation_id if contract is selected
        data.quotation_id = '';
        // Ensure contract_id is set
        if (!data.contract_id) {
            alert('Please select a contract.');
            return;
        }
    }

    if (String(data.type || '').toLowerCase() === 'service' && sourceType !== 'contract') {
        alert('Job Advice type Service wajib dibuat dari Contract.');
        return;
    }
    
    // Set default values for fields not in create modal
    if (!id) {
        data.status = 'draft'; // Always draft when created
        data.with_invoicing = false;
        data.with_materials = false;
    }
    
    // MOM6: Collect rooms data as array
    const rooms = [];
    const roomInputs = document.querySelectorAll('.room-row');
    roomInputs.forEach((roomRow, index) => {
        const contractRoomId = roomRow.querySelector('[name*="[contract_room_id]"]')?.value;
        const quotationRoomId = roomRow.querySelector('[name*="[quotation_room_id]"]')?.value;
        const rentalProductId = roomRow.querySelector('[name*="[rental_product_id]"]')?.value;
        const quantity = roomRow.querySelector('[name*="[quantity]"]')?.value;
        const isTrial = roomRow.querySelector('[name*="[is_trial]"]')?.value;
        const notes = roomRow.querySelector('[name*="[notes]"]')?.value;
        
        if ((contractRoomId || quotationRoomId) && rentalProductId) {
            rooms.push({
                contract_room_id: contractRoomId || null,
                quotation_room_id: quotationRoomId || null,
                rental_product_id: rentalProductId,
                quantity: quantity || 1,
                is_trial: isTrial === '1' ? true : false,
                notes: notes || ''
            });
        }
    });
    
    // Remove individual room fields and add as array
    Object.keys(data).forEach(key => {
        if (key.startsWith('rooms[')) {
            delete data[key];
        }
    });
    
    if (rooms.length > 0) {
        data.rooms = rooms;
    }
    
    const url = id ? `/marketing/job-advices/${id}` : '/marketing/job-advices';
    const method = id ? 'PUT' : 'POST';
    
    // Add CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    data._method = method;
    
    console.log('Submitting data:', data);
    console.log('Rooms:', rooms);
    console.log('URL:', url);
    console.log('Method:', method);
    
    fetch(url, {
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
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Get response text first to check if it's JSON
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            // If not JSON, it's probably an HTML error page
            console.error('Response is not JSON:', responseText);
            if (!response.ok) {
                throw new Error(`Server error (${response.status}): ${responseText.substring(0, 200)}`);
            }
            // If response is OK but not JSON, treat as success
            result = { status: 'success', message: 'Job Advice berhasil dibuat.' };
        }
        
        // Check if response is OK
        if (!response.ok) {
            // Extract error message from result
            let errorMessage = 'Terjadi kesalahan saat membuat Job Advice.';
            if (result.message) {
                errorMessage = result.message;
            } else if (result.errors) {
                // Validation errors
                const errorList = Object.entries(result.errors)
                    .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
                    .join('\n');
                errorMessage = `Validasi error:\n${errorList}`;
            }
            throw new Error(errorMessage);
        }
        
        return result;
    })
    .then(result => {
        console.log('Response result:', result);
        
        if (result.status === 'success' || result.success) {
            closeModal();
            // Show success message
            if (result.message) {
                alert(result.message);
            }
            
            // Redirect to detail page if ID is available
            if (result.data && result.data.id) {
                window.location.href = `/marketing/job-advices/${result.data.id}`;
            } else {
                location.reload();
            }
        } else {
            alert('Error: ' + (result.message || 'Something went wrong'));
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        
        // Show user-friendly error message
        let errorMessage = error.message || 'Terjadi kesalahan saat membuat Job Advice.';
        if (errorMessage.length > 200) {
            errorMessage = errorMessage.substring(0, 200) + '...';
        }
        alert('Error: ' + errorMessage);
    });
}

// Delete Modal functions
function openDeleteModal() {
    const count = selectedIdsForRetry.length;
    const message = count === 1 
        ? 'Are you sure you want to hide this job advice? This action can be undone later.'
        : `Are you sure you want to hide ${count} job advices? This action can be undone later.`;
    
    document.getElementById('deleteMessage').textContent = message;
    document.getElementById('deleteModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function confirmDelete() {
    closeDeleteModal();
    
    fetch('/marketing/job-advices/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ ids: selectedIdsForRetry })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessModal(result.count);
        } else {
            showErrorModal(result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('Network error occurred');
    });
}

// Success Modal functions
function showSuccessModal(count) {
    const message = count === 1 
        ? 'The job advice has been successfully hidden.'
        : `${count} job advices have been successfully hidden.`;
    
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Auto close after 3 seconds
    successModalTimer = setTimeout(() => {
        closeSuccessModal();
        location.reload();
    }, 3000);
}

function closeSuccessModal() {
    if (successModalTimer) {
        clearTimeout(successModalTimer);
        successModalTimer = null;
    }
    document.getElementById('successModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Error Modal functions
function showErrorModal(message) {
    document.getElementById('errorMessage').textContent = message || 'We couldn\'t hide the job advice. Please try again.';
    document.getElementById('errorModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeErrorModal() {
    document.getElementById('errorModalOverlay').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function retryDelete() {
    closeErrorModal();
    confirmDelete();
}

// Event listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
        closeErrorModal();
        closeSuccessModal();
    }
});

// Click outside to close modals
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('deleteModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

document.getElementById('errorModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeErrorModal();
    }
});

document.getElementById('successModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuccessModal();
        location.reload();
    }
});

// Add CSS for loading spinner
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Global variable to store current customer ID for PIC modal
let currentCustomerIdForPic = null;

// Function to load customer contacts (vanilla JS version)
function loadCustomerContacts(customerId) {
    currentCustomerIdForPic = customerId;
    
    const picSelect = document.getElementById('customer_contact_id');
    if (!picSelect) return;

    if (!customerId) {
        picSelect.innerHTML = '<option value="">Select PIC</option>';
        return;
    }

    // Use fetch instead of jQuery ajax
    fetch('/company/customer-contacts/by-customer/' + customerId)
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            let options = '<option value="">Select PIC</option>';
            const contacts = data.data || data;
            
            if (Array.isArray(contacts)) {
                contacts.forEach(function(contact) {
                    options += '<option value="' + contact.id + '" data-email="' + (contact.email || '') + '" data-phone="' + (contact.phone || '') + '">' + contact.name + '</option>';
                });
            }
            
            picSelect.innerHTML = options;
        })
        .catch(function(error) {
            console.error('Error loading contacts:', error);
        });
}

window.openAddPicModal = function() {
    // Use global variable instead of jQuery data
    let customerId = currentCustomerIdForPic;
    
    if (!customerId) {
        // Fallback: try to get from selected quotation/contract
        const quotationSelect = document.getElementById('quotation_id');
        if (quotationSelect && quotationSelect.value) {
            const selectedOption = quotationSelect.options[quotationSelect.selectedIndex];
            customerId = selectedOption.getAttribute('data-customer-id');
        }
        
        if (!customerId) {
            const contractSelect = document.getElementById('contract_id');
            if (contractSelect && contractSelect.value) {
                const selectedOption = contractSelect.options[contractSelect.selectedIndex];
                customerId = selectedOption.getAttribute('data-customer-id');
            }
        }
        
        if (!customerId) {
            alert('Please select a Contract or Quotation first.');
            return;
        }
    }

    // Get salutation and position options from Blade
    const salutations = @json($salutations ?? []);
    const positions = @json($positions ?? []);
    
    // Build salutation options
    let salutationOptions = '<option value="">Select Salutation</option>';
    salutations.forEach(function(s) {
        salutationOptions += '<option value="' + s + '">' + s + '</option>';
    });
    
    // Build position options
    let positionOptions = '<option value="">Select Position</option>';
    positions.forEach(function(p) {
        positionOptions += '<option value="' + p + '">' + p + '</option>';
    });

    let modalHtml = '';
    modalHtml += '<div id="addPicModal" style="z-index: 9999; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%;">';
    modalHtml += '    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); width: 100%; max-width: 500px;" onclick="event.stopPropagation();">';
    modalHtml += '        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">';
    modalHtml += '            <h5 style="margin: 0; font-size: 18px; font-weight: 600;">Add New PIC</h5>';
    modalHtml += '            <button type="button" style="border: none; background: none; font-size: 20px; cursor: pointer;" onclick="closeAddPicModal()">&times;</button>';
    modalHtml += '        </div>';
    modalHtml += '        <form id="addPicForm">';
    modalHtml += '            <input type="hidden" name="customer_id" value="' + customerId + '">';
    
    // Salutation and Name row
    modalHtml += '            <div style="display: flex; gap: 10px; margin-bottom: 15px;">';
    modalHtml += '                <div style="flex: 0 0 120px;">';
    modalHtml += '                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Salutation</label>';
    modalHtml += '                    <select class="form-control" name="salutation" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;">' + salutationOptions + '</select>';
    modalHtml += '                </div>';
    modalHtml += '                <div style="flex: 1;">';
    modalHtml += '                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Name <span style="color: red;">*</span></label>';
    modalHtml += '                    <input type="text" class="form-control" name="name" required placeholder="e.g. John Doe" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;">';
    modalHtml += '                </div>';
    modalHtml += '            </div>';
    
    // Position dropdown
    modalHtml += '            <div style="margin-bottom: 15px;">';
    modalHtml += '                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Job Position</label>';
    modalHtml += '                <select class="form-control" name="position" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;">' + positionOptions + '</select>';
    modalHtml += '            </div>';
    
    modalHtml += '            <div style="margin-bottom: 15px;">';
    modalHtml += '                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Phone</label>';
    modalHtml += '                <input type="text" class="form-control" name="phone" placeholder="e.g. 08123456789" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;">';
    modalHtml += '            </div>';
    modalHtml += '            <div style="margin-bottom: 15px;">';
    modalHtml += '                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Email</label>';
    modalHtml += '                <input type="email" class="form-control" name="email" placeholder="e.g. john@example.com" style="width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;">';
    modalHtml += '            </div>';
    modalHtml += '            <div style="text-align: right;">';
    modalHtml += '                <button type="button" style="padding: 8px 16px; margin-right: 10px; border: 1px solid #ccc; background: #f8f8f8; border-radius: 4px; cursor: pointer;" onclick="closeAddPicModal()">Cancel</button>';
    modalHtml += '                <button type="button" style="padding: 8px 16px; border: none; background: #214589; color: white; border-radius: 4px; cursor: pointer;" onclick="submitNewPic()">Save</button>';
    modalHtml += '            </div>';
    modalHtml += '        </form>';
    modalHtml += '    </div>';
    modalHtml += '</div>';
    
    // Remove existing modal if any, append new one (vanilla JS)
    const existingModal = document.getElementById('addPicModal');
    if (existingModal) existingModal.remove();
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function closeAddPicModal() {
    const modal = document.getElementById('addPicModal');
    if (modal) modal.remove();
}

function submitNewPic() {
    const form = document.getElementById('addPicForm');
    const formData = new FormData(form);
    
    if (!formData.get('name')) {
        alert('Name is required');
        return;
    }
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Use fetch instead of jQuery ajax
    fetch('{{ route("company.customer-contacts.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) {
        if (!response.ok) {
            return response.text().then(function(text) {
                throw new Error('Server error: ' + response.status);
            });
        }
        return response.json();
    })
    .then(function(data) {
        if (data.error) {
            alert(data.error);
            return;
        }
        
        const newContact = data.data || data.contact || data;
        
        // Add to dropdown using vanilla JS
        const picSelect = document.getElementById('customer_contact_id');
        if (picSelect) {
            const option = document.createElement('option');
            option.value = newContact.id;
            option.textContent = newContact.name;
            option.setAttribute('data-email', newContact.email || '');
            option.setAttribute('data-phone', newContact.phone || '');
            option.selected = true;
            picSelect.appendChild(option);
        }
        
        closeAddPicModal();
        
        // Try to show success notification (with fallback)
        if (typeof toastr !== 'undefined') {
            toastr.success('PIC added successfully');
        } else {
            alert('PIC added successfully');
        }
    })
    .catch(function(error) {
        console.error('Error adding PIC:', error);
        alert('Error adding PIC');
    });
}

// Flatpickr Initialization
document.addEventListener('DOMContentLoaded', function() {
    const flatpickrConfig = {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d M Y",
        allowInput: false,
    };

    const startPicker = flatpickr("#start_date", flatpickrConfig);
    const endPicker = flatpickr("#end_date", flatpickrConfig);

    // Auto-set dates if not present in URL
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('start_date') && !urlParams.has('end_date')) {
        const today = new Date();
        const next14Days = new Date();
        next14Days.setDate(today.getDate() + 14);

        startPicker.setDate(today);
        endPicker.setDate(next14Days);
        
        console.log('Auto-set filter dates:', {
            from: dateValueForInput(today),
            to: dateValueForInput(next14Days)
        });
    }

    // MOM9: Auto-open modal if quotation_id is provided in URL
    const quotationId = urlParams.get('quotation_id');
    const openModalParam = urlParams.get('open_modal');
    const typeParam = urlParams.get('type');
    
    if (openModalParam === 'true' && quotationId) {
        // Open create modal
        openCreateModal();
        loadUsers();
    }
    
    // Initialize Select2 for PIC only if jQuery and Select2 are available
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        $('#customer_contact_id').select2({
            dropdownParent: $('#modalOverlay'),
            placeholder: 'Select PIC',
            allowClear: true,
            width: 'resolve'
        });

        // Handle PIC selection
        $('#customer_contact_id').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const email = selectedOption.data('email');
            const phone = selectedOption.data('phone');
            
            if (email || phone) {
                let details = [];
                if (phone) details.push('Phone: ' + phone);
                if (email) details.push('Email: ' + email);
                $('#pic_details').text(details.join(' | ')).show();
            } else {
                $('#pic_details').hide();
            }
        });
        
        // Check if we need to auto-trigger contract/quotation loading
        const selectedQuotationId = '{{ session("selected_quotation_id") ?? $selectedQuotationId ?? "" }}';
        if (selectedQuotationId) {
            document.getElementById('source_quotation').click();
            // Wait for dropdown to populate then select
            setTimeout(function() {
                $('#quotation_id').val(selectedQuotationId).trigger('change');
            }, 1000);
        } else {
            loadContracts();
            loadQuotations();
        }
    } else {
        // Fallback: just load contracts and quotations without Select2
        console.log('jQuery or Select2 not available, using native select');
        loadContracts();
        loadQuotations();
    }
});


</script>
@endsection
