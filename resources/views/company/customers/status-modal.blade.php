<!-- Customer Status Update Modal -->
<div id="statusModalOverlay" class="modal-overlay" onclick="closeStatusModal()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 class="modal-title" id="statusModalTitle">Update Customer Status</h3>
            <button type="button" class="modal-close" onclick="closeStatusModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="statusForm">
                <input type="hidden" id="status_customer_id" name="customer_id">
                <input type="hidden" id="status_field_type" name="field_type">
                
                <div class="form-group">
                    <label class="form-label">Customer</label>
                    <input type="text" id="status_customer_name" class="form-input" readonly>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Current Status</label>
                    <div id="current_status_display" class="status-display"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Status</label>
                    <div class="status-options">
                        <label class="status-option">
                            <input type="radio" name="new_status" value="1" class="status-radio">
                            <span class="status-label status-yes">Yes</span>
                        </label>
                        <label class="status-option">
                            <input type="radio" name="new_status" value="0" class="status-radio">
                            <span class="status-label status-no">No</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason for Change</label>
                    <textarea id="status_reason" name="reason" class="form-input" rows="3" placeholder="Enter reason for status change..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="submitStatusUpdate()">Update Status</button>
        </div>
    </div>
</div>

<style>
/* Modal Styles - Same as Master Teams */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    backdrop-filter: blur(2px);
}

.modal-overlay.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    max-width: 90vw;
    max-height: 90vh;
    width: 500px;
    overflow: hidden;
    position: relative;
}

.modal-header {
    background: #214589;
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 20;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s ease;
}

.modal-close:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    max-height: calc(90vh - 140px);
}

.modal-footer {
    padding: 20px;
    background: #f9fafb;
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
    margin-bottom: 8px;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-input:focus {
    outline: none;
    border-color: #214589;
    box-shadow: 0 0 0 3px rgba(33, 69, 137, 0.1);
}

/* Status Display Styles */
.status-display {
    padding: 8px 12px;
    border-radius: 4px;
    font-weight: 500;
    display: inline-block;
}

.status-display.pkp-yes, .status-display.active-yes {
    background-color: #d1fae5;
    color: #065f46;
}

.status-display.pkp-no, .status-display.active-no {
    background-color: #fee2e2;
    color: #991b1b;
}

.status-options {
    display: flex;
    gap: 16px;
}

.status-option {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.status-radio {
    margin-right: 8px;
}

.status-label {
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.status-yes {
    background-color: #d1fae5;
    color: #065f46;
}

.status-no {
    background-color: #fee2e2;
    color: #991b1b;
}

.status-option:hover .status-label {
    opacity: 0.8;
}

.status-radio:checked + .status-label {
    opacity: 1;
    transform: scale(1.05);
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
</style>

<script>
function openStatusModal(customerId, customerName, fieldType, currentValue) {
    document.getElementById('status_customer_id').value = customerId;
    document.getElementById('status_customer_name').value = customerName;
    document.getElementById('status_field_type').value = fieldType;
    
    // Set modal title
    const title = fieldType === 'is_pkp' ? 'Update PKP Status' : 'Update Active Status';
    document.getElementById('statusModalTitle').textContent = title;
    
    // Display current status
    const statusDisplay = document.getElementById('current_status_display');
    const currentText = currentValue ? 'Yes' : 'No';
    const statusClass = fieldType === 'is_pkp' ? 
        (currentValue ? 'pkp-yes' : 'pkp-no') : 
        (currentValue ? 'active-yes' : 'active-no');
    
    statusDisplay.textContent = currentText;
    statusDisplay.className = `status-display ${statusClass}`;
    
    // Reset form
    document.querySelectorAll('input[name="new_status"]').forEach(radio => {
        radio.checked = false;
    });
    document.getElementById('status_reason').value = '';
    
    // Show modal
    document.getElementById('statusModalOverlay').classList.add('show');
}

function closeStatusModal() {
    document.getElementById('statusModalOverlay').classList.remove('show');
}

function submitStatusUpdate() {
    const customerId = document.getElementById('status_customer_id').value;
    const fieldType = document.getElementById('status_field_type').value;
    const newStatus = document.querySelector('input[name="new_status"]:checked');
    const reason = document.getElementById('status_reason').value;
    
    if (!newStatus) {
        alert('Silakan pilih status baru');
        return;
    }
    
    const isNewStatus = newStatus.value === '1';
    
    // Determine endpoint
    const endpoint = fieldType === 'is_pkp' ? 
        `/company/customers/${customerId}/update-pkp-status` :
        `/company/customers/${customerId}/update-active-status`;
    
    // Prepare data
    const data = {
        [fieldType]: isNewStatus,
        reason: reason
    };
    
    // Show loading
    const submitBtn = document.querySelector('.modal-footer .btn-primary');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Memperbarui...';
    submitBtn.disabled = true;
    
    // Send request
    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Show success message
            showNotification('success', data.message);
            
            // Close modal
            closeStatusModal();
            
            // Refresh customer list or update specific row
            if (typeof refreshCustomerList === 'function') {
                refreshCustomerList();
            } else {
                location.reload();
            }
        } else {
            showNotification('error', data.message || 'Gagal memperbarui status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Terjadi kesalahan saat memperbarui status');
    })
    .finally(() => {
        // Reset button
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function showNotification(type, message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 4px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    if (type === 'success') {
        notification.style.backgroundColor = '#10b981';
    } else {
        notification.style.backgroundColor = '#ef4444';
    }
    
    // Add animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
        style.remove();
    }, 3000);
}
</script>
