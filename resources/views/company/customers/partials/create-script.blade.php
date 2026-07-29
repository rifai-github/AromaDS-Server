<script>
    // Global variable for referencing customer ID in edit mode (though not used in create-only partial, kept for compatibility)
    let currentCustomerIdForPartial = null; // Renamed to avoid partial conflict if any

    /**
     * Trigger change event on element
     */
    function triggerChange(element) {
        if ("createEvent" in document) {
            var evt = document.createEvent("HTMLEvents");
            evt.initEvent("change", false, true);
            element.dispatchEvent(evt);
        } else {
            element.fireEvent("onchange");
        }
    }

    /* =========================================
       Create Modal Functions
       ========================================= */

    function openCreateCustomerModal() {
        // Reset form
        const form = document.getElementById('createCustomerForm');
        if (form) form.reset();
        
        // Reset dropdowns
        ['create_city_id', 'create_district_id', 'create_subdistrict_id'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '<option value="">Select option</option>';
        });
        
        document.getElementById('create_postal_code').value = '';
        
        // Load initial data
        loadProvincesForCreate();
        
        // Initialize Select2 for Category and Company Type
        // We need to destroy first in case it was already initialized
        if ($.fn.select2) {
            $('#create_category_id, #create_company_type_input').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });

            // Initialize with dropdownParent to fix z-index issues in modals
            $('#create_category_id').select2({
                placeholder: 'Pilih Category',
                allowClear: true,
                dropdownParent: $('#createCustomerModalOverlay .modal-container')
            });
            
            $('#create_company_type_input').select2({
                placeholder: 'Pilih Badan Hukum',
                allowClear: true,
                dropdownParent: $('#createCustomerModalOverlay .modal-container')
            });
            
            // Debug: Check if categories exist
            const categoryCount = $('#create_category_id option').length;
            console.log(`[CreateCustomer] Initialized category Select2 with ${categoryCount} options`);
        }
        
        // Show modal
        document.getElementById('createCustomerModalOverlay').classList.add('show');
    }

    function closeCreateCustomerModal() {
        document.getElementById('createCustomerModalOverlay').classList.remove('show');
    }

    function loadProvincesForCreate() {
        // If already populated (e.g. by previous call), maybe skip? 
        // But for fresh state, let's reload or check if empty.
        const select = document.getElementById('create_province_id');
        if (select && select.options.length > 1) return; // Already loaded

        fetch('/api/v1/location/provinces')
            .then(response => response.json())
            .then(data => {
                if (select) {
                    select.innerHTML = '<option value="">Select Province</option>';
                    const provinces = Array.isArray(data) ? data : (data.data || []);
                    provinces.forEach(province => {
                        const option = document.createElement('option');
                        option.value = province.id;
                        option.textContent = province.name;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading provinces:', error);
            });
    }

    function loadCitiesForCreate(provinceId) {
        if (!provinceId) return;
        
        const citySelect = document.getElementById('create_city_id');
        // Clear dependent dropdowns
        document.getElementById('create_district_id').innerHTML = '<option value="">Select District</option>';
        document.getElementById('create_subdistrict_id').innerHTML = '<option value="">Select Subdistrict</option>';
        clearPostalCodeForCreate();

        fetch(`/api/v1/location/cities?province_id=${provinceId}`)
            .then(response => response.json())
            .then(data => {
                if (citySelect) {
                    citySelect.innerHTML = '<option value="">Select City</option>';
                    const cities = Array.isArray(data) ? data : (data.data || []);
                    cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.name;
                        citySelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading cities:', error);
            });
    }

    function loadDistrictsForCreate(cityId) {
        if (!cityId) return;
        
        const districtSelect = document.getElementById('create_district_id');
        document.getElementById('create_subdistrict_id').innerHTML = '<option value="">Select Subdistrict</option>';
        clearPostalCodeForCreate();

        fetch(`/api/v1/location/districts?city_id=${cityId}`)
            .then(response => response.json())
            .then(data => {
                if (districtSelect) {
                    districtSelect.innerHTML = '<option value="">Select District</option>';
                    const districts = Array.isArray(data) ? data : (data.data || []);
                    districts.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district.id;
                        option.textContent = district.name;
                        districtSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading districts:', error);
            });
    }

    function loadSubdistrictsForCreate(districtId) {
        if (!districtId) return;
        
        const subdistrictSelect = document.getElementById('create_subdistrict_id');
        
        fetch(`/api/v1/location/subdistricts?district_id=${districtId}`)
            .then(response => response.json())
            .then(data => {
                if (subdistrictSelect) {
                    subdistrictSelect.innerHTML = '<option value="">Select Subdistrict</option>';
                    const subdistricts = Array.isArray(data) ? data : (data.data || []);
                    subdistricts.forEach(subdistrict => {
                        const option = document.createElement('option');
                        option.value = subdistrict.id;
                        option.textContent = subdistrict.name;
                        // Store postal_code in data attribute
                        if (subdistrict.postal_code) {
                            option.setAttribute('data-postal-code', subdistrict.postal_code);
                        }
                        subdistrictSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading subdistricts:', error);
            });
    }

    function loadPostalCodeForCreate(subdistrictId) {
        if (!subdistrictId) return;
        
        const subdistrictSelect = document.getElementById('create_subdistrict_id');
        if (subdistrictSelect) {
            const selectedOption = subdistrictSelect.options[subdistrictSelect.selectedIndex];
            const postalCode = selectedOption.getAttribute('data-postal-code');
            
            const postalCodeInput = document.getElementById('create_postal_code');
            if (postalCodeInput && postalCode) {
                postalCodeInput.value = postalCode;
            } else if (postalCodeInput) {
                postalCodeInput.value = '';
            }
        }
    }

    function clearPostalCodeForCreate() {
        const postalCodeInput = document.getElementById('create_postal_code');
        if (postalCodeInput) {
            postalCodeInput.value = '';
        }
    }

    /* =========================================
       Adding a PIC from the Multi PIC "+" button now reuses the
       "Tambah Kontak Cepat" modal (openQuickContactModal('customer-create'))
       defined in marketing/pipeline/index.blade.php, instead of a separate
       inline contact modal here.
       ========================================= */

    /* =========================================
       Submit Customer Form
       ========================================= */

    function submitCreateCustomerForm() {
        const form = document.getElementById('createCustomerForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        
        fetch('/company/customers', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    let errorMessage = '';
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            errorMessage += `${data.errors[key].join('\n')}\n`;
                        });
                    } else {
                        errorMessage = data.message || 'Server error';
                    }
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Validasi Gagal', text: errorMessage, confirmButtonColor: '#214589' });
                    } else if (typeof showError === 'function') {
                        showError(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                    throw new Error('Validation failed');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                if (typeof showSuccess === 'function') showSuccess(data.message || 'Customer created successfully!');
                else alert('Customer created successfully!');
                
                closeCreateCustomerModal();
                
                // CUSTOM HOOK: Check if we need to call a pipeline callback
                if (typeof onCustomerCreatedInPipeline === 'function') {
                    onCustomerCreatedInPipeline(data.data);
                } else {
                    window.location.reload(); 
                }
            } else {
                const msg = data.message || 'Failed to create customer';
                if (typeof showError === 'function') showError(msg);
                else alert(msg);
            }
        })
        .catch(error => {
            if (error.message !== 'Validation failed') {
                console.error('Error:', error);
                if (typeof showError === 'function') showError('An error occurred: ' + error.message);
            }
        });
    }
</script>
