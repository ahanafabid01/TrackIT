// Store In-charge Dashboard JavaScript

const navItems = document.querySelectorAll('.nav-item[data-page]');
let dashboardStats = {
    pendingRequests: 0,
    deliveriesToday: 0,
    inTransit: 0,
    returns: 0
};

// API Helper Functions
async function fetchAPI(endpoint, options = {}) {
    console.log('🌐 API Request:', {
        endpoint,
        method: options.method || 'GET',
        hasBody: !!options.body
    });
    
    if (options.body) {
        console.log('📤 Request body:', options.body);
    }
    
    try {
        const response = await fetch(endpoint, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });
        
        console.log('📥 Response status:', response.status, response.statusText);
        console.log('📥 Response headers:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            console.error('❌ HTTP Error:', response.status, response.statusText);
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const responseText = await response.text();
        console.log('📥 Raw response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
            console.log('📥 Parsed response data:', data);
        } catch (parseError) {
            console.error('❌ JSON Parse Error:', parseError);
            console.error('📄 Response content was:', responseText);
            throw new Error('Invalid JSON response from server');
        }
        
        if (!data.success) {
            console.error('❌ API returned error:', data.error);
            throw new Error(data.error || 'API request failed');
        }
        
        console.log('✅ API request successful');
        return data;
    } catch (error) {
        console.error('❌ API Error details:', {
            message: error.message,
            stack: error.stack,
            endpoint: endpoint,
            options: options
        });
        throw error;
    }
}

// Load Dashboard Statistics
async function loadDashboardStats() {
    try {
        console.log('Loading dashboard stats...');
        
        // Load pending booking requests
        const bookings = await fetchAPI('../../api/store_incharge/booking_requests.php?status=Pending');
        console.log('Bookings response:', bookings);
        dashboardStats.pendingRequests = bookings.bookings?.length || 0;
        
        // Load today's deliveries
        const today = new Date().toISOString().split('T')[0];
        const deliveries = await fetchAPI(`../../api/store_incharge/deliveries.php?limit=100`);
        console.log('Deliveries response:', deliveries);
        const todayDeliveries = deliveries.deliveries?.filter(d => 
            d.dispatch_date === today || d.expected_delivery_date === today
        ) || [];
        dashboardStats.deliveriesToday = todayDeliveries.length;
        
        // Load in transit deliveries
        const inTransit = deliveries.deliveries?.filter(d => 
            d.delivery_status === 'In Transit' || d.delivery_status === 'Out for Delivery'
        ) || [];
        dashboardStats.inTransit = inTransit.length;
        
        // Load active returns
        const returns = await fetchAPI('../../api/store_incharge/returns.php?status=Pending');
        console.log('Returns response:', returns);
        dashboardStats.returns = returns.returns?.length || 0;
        
        // Update UI
        updateDashboardUI();
        console.log('Dashboard stats updated:', dashboardStats);
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        showNotification('Failed to load dashboard statistics', 'error');
    }
}

// Load Deliveries
async function loadDeliveries() {
    try {
        console.log('Loading deliveries...');
        const data = await fetchAPI('../../api/store_incharge/deliveries.php');
        console.log('Deliveries data:', data);
        renderDeliveries(data.deliveries || []);
    } catch (error) {
        console.error('Error loading deliveries:', error);
        const tbody = document.querySelector('#deliveriesTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: #ef4444;">Error loading deliveries: ' + error.message + '</td></tr>';
        }
        showNotification('Failed to load deliveries: ' + error.message, 'error');
    }
}

// Render Deliveries Table
function renderDeliveries(deliveries) {
    const tbody = document.querySelector('#deliveriesTableBody');
    if (!tbody) return;
    
    if (deliveries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px;">No deliveries found</td></tr>';
        return;
    }
    
    tbody.innerHTML = deliveries.map(delivery => {
        const statusClass = delivery.delivery_status?.toLowerCase().replace(' ', '-') || 'pending';
        
        let actionButtons = '';
        switch(delivery.delivery_status) {
            case 'Dispatched':
                actionButtons = `
                    <button class="btn btn-sm btn-primary" onclick="updateDeliveryStatus(${delivery.id}, 'In Transit')">
                        <i class="fas fa-truck-moving"></i> In Transit
                    </button>
                `;
                break;
            case 'In Transit':
                actionButtons = `
                    <button class="btn btn-sm btn-warning" onclick="updateDeliveryStatus(${delivery.id}, 'Out for Delivery')">
                        <i class="fas fa-shipping-fast"></i> Out for Delivery
                    </button>
                `;
                break;
            case 'Out for Delivery':
                actionButtons = `
                    <button class="btn btn-sm btn-success" onclick="updateDeliveryStatus(${delivery.id}, 'Delivered')">
                        <i class="fas fa-check-circle"></i> Delivered
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="updateDeliveryStatus(${delivery.id}, 'Failed')">
                        <i class="fas fa-times-circle"></i> Failed
                    </button>
                `;
                break;
            case 'Delivered':
                actionButtons = `<span class="text-success"><i class="fas fa-check-circle"></i> Delivered</span>`;
                break;
            case 'Failed':
                actionButtons = `
                    <button class="btn btn-sm btn-warning" onclick="updateDeliveryStatus(${delivery.id}, 'Out for Delivery')">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                `;
                break;
            default:
                actionButtons = `
                    <button class="btn btn-sm btn-info" onclick="showUpdateDeliveryModal(${delivery.id})" title="Update Delivery Status">
                        <i class="fas fa-edit"></i>
                    </button>
                `;
        }
        
        return `
            <tr>
                <td>
                    <strong>${delivery.tracking_number || 'N/A'}</strong>
                    ${delivery.tracking_number ? `<br><small class="text-muted">Track: ${delivery.tracking_number}</small>` : ''}
                </td>
                <td>#${delivery.booking_number}</td>
                <td>${escapeHtml(delivery.customer_name)}</td>
                <td>${escapeHtml(delivery.product_name)} (${delivery.quantity})</td>
                <td>${delivery.courier_name || 'N/A'}</td>
                <td>${formatDate(delivery.dispatch_date)}</td>
                <td>${formatDate(delivery.expected_delivery_date)}</td>
                <td><span class="badge badge-${statusClass}">${delivery.delivery_status || 'Pending'}</span></td>
                <td style="min-width: 150px;">
                    ${actionButtons}
                </td>
            </tr>
        `;
    }).join('');
}

// Load Returns
async function loadReturns() {
    try {
        console.log('Loading returns...');
        const data = await fetchAPI('../../api/store_incharge/returns.php');
        console.log('Returns data:', data);
        renderReturns(data.returns || []);
    } catch (error) {
        console.error('Error loading returns:', error);
        const tbody = document.querySelector('#returnsTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: #ef4444;">Error loading returns: ' + error.message + '</td></tr>';
        }
        showNotification('Failed to load returns: ' + error.message, 'error');
    }
}

// Render Returns Table
function renderReturns(returns) {
    const tbody = document.querySelector('#returnsTableBody');
    if (!tbody) return;
    
    if (returns.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px;">No returns found</td></tr>';
        return;
    }
    
    tbody.innerHTML = returns.map(returnItem => {
        const statusClass = returnItem.status?.toLowerCase().replace(' ', '-') || 'pending';
        const reasonClass = returnItem.return_reason?.toLowerCase().replace(' ', '-') || '';
        
        let actionButtons = '';
        switch(returnItem.status) {
            case 'Pending':
                actionButtons = `
                    <button class="btn btn-sm btn-success" onclick="updateReturnStatus(${returnItem.id}, 'Approved')">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="updateReturnStatus(${returnItem.id}, 'Rejected')">
                        <i class="fas fa-times"></i> Reject
                    </button>
                `;
                break;
            case 'Approved':
                actionButtons = `
                    <button class="btn btn-sm btn-primary" onclick="updateReturnStatus(${returnItem.id}, 'Restocked')">
                        <i class="fas fa-warehouse"></i> Restock
                    </button>
                    <button class="btn btn-sm btn-info" onclick="updateReturnStatus(${returnItem.id}, 'Refunded')">
                        <i class="fas fa-money-bill-wave"></i> Refund
                    </button>
                `;
                break;
            case 'Restocked':
                actionButtons = `<span class="text-success"><i class="fas fa-warehouse"></i> Restocked</span>`;
                break;
            case 'Refunded':
                actionButtons = `<span class="text-info"><i class="fas fa-money-bill-wave"></i> Refunded</span>`;
                break;
            case 'Rejected':
                actionButtons = `<span class="text-danger"><i class="fas fa-times-circle"></i> Rejected</span>`;
                break;
            default:
                actionButtons = `
                    <button class="btn btn-sm btn-info" onclick="viewReturnDetails(${returnItem.id})">
                        <i class="fas fa-eye"></i> View
                    </button>
                `;
        }
        
        return `
            <tr>
                <td>#${returnItem.return_number}</td>
                <td>#${returnItem.booking_number}</td>
                <td>${escapeHtml(returnItem.customer_name)}</td>
                <td>${escapeHtml(returnItem.product_name)}</td>
                <td><span class="badge badge-${reasonClass}">${returnItem.return_reason}</span></td>
                <td>${returnItem.quantity_returned}</td>
                <td>${formatDate(returnItem.return_date)}</td>
                <td><span class="badge badge-${statusClass}">${returnItem.status}</span></td>
                <td style="min-width: 180px;">
                    ${actionButtons}
                </td>
            </tr>
        `;
    }).join('');
}

// Update Dashboard UI
function updateDashboardUI() {
    const statCards = document.querySelectorAll('.stat-card');
    if (statCards.length >= 4) {
        statCards[0].querySelector('.stat-value').textContent = dashboardStats.pendingRequests;
        statCards[1].querySelector('.stat-value').textContent = dashboardStats.deliveriesToday;
        statCards[2].querySelector('.stat-value').textContent = dashboardStats.inTransit;
        statCards[3].querySelector('.stat-value').textContent = dashboardStats.returns;
    }
}

// Load Booking Requests
async function loadBookingRequests() {
    try {
        console.log('Loading booking requests...');
        // Load all statuses except Delivered and Rejected
        const data = await fetchAPI('../../api/store_incharge/booking_requests.php?status=Pending,Confirmed,Processing,Ready,Cancelled');
        console.log('Booking requests data:', data);
        renderBookingRequests(data.bookings || []);
    } catch (error) {
        console.error('Error loading booking requests:', error);
        const tbody = document.querySelector('#bookingsPage tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #ef4444;">Error loading bookings: ' + error.message + '</td></tr>';
        }
        showNotification('Failed to load booking requests: ' + error.message, 'error');
    }
}

// Render Booking Requests Table
function renderBookingRequests(bookings) {
    const tbody = document.querySelector('#bookingsPage tbody');
    if (!tbody) return;
    
    if (bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px;">No booking requests</td></tr>';
        return;
    }
    
    tbody.innerHTML = bookings.map(booking => {
        const priorityClass = booking.priority.toLowerCase();
        const statusClass = booking.status.toLowerCase().replace(' ', '-');
        
        // Define valid status transitions
        const validTransitions = {
            'Pending': ['Pending', 'Confirmed', 'Rejected'],
            'Confirmed': ['Confirmed', 'Processing', 'Cancelled'],
            'Processing': ['Processing', 'Ready', 'Cancelled'],
            'Ready': ['Ready', 'Delivered', 'Cancelled'],
            'Delivered': ['Delivered', 'Return'], // Can move to Return if customer returns product
            'Return': ['Return'], // Final state - return completed
            'Cancelled': ['Cancelled'], // Final state - no changes allowed
            'Rejected': ['Rejected']  // Final state - no changes allowed
        };
        
        // Get allowed statuses for current booking status
        const allowedStatuses = validTransitions[booking.status] || [booking.status];
        
        // Create status dropdown with all statuses, but disable invalid ones
        const statuses = ['Pending', 'Confirmed', 'Processing', 'Ready', 'Delivered', 'Return', 'Cancelled', 'Rejected'];
        const statusOptions = statuses.map(status => {
            const isAllowed = allowedStatuses.includes(status);
            const isSelected = status === booking.status;
            return `<option value="${status}" 
                            ${isSelected ? 'selected' : ''} 
                            ${!isAllowed ? 'disabled style="background: #f3f4f6; color: #9ca3af;"' : ''}>
                        ${status}${!isAllowed ? ' 🚫' : ''}
                    </option>`;
        }).join('');
        
        const actionButtons = `
            <select class="status-dropdown" 
                    data-booking-id="${booking.id}" 
                    data-booking-number="${booking.booking_number}"
                    data-current-status="${booking.status}"
                    onchange="handleBookingStatusChange(this)"
                    style="padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; cursor: pointer; background: white; min-width: 130px;">
                ${statusOptions}
            </select>
        `;
        
        return `
            <tr>
                <td>#${booking.booking_number}</td>
                <td>${escapeHtml(booking.customer_name)}</td>
                <td>${escapeHtml(booking.product_name)}</td>
                <td>${booking.quantity}</td>
                <td><span class="badge badge-${priorityClass}">${booking.priority}</span></td>
                <td><span class="badge badge-${statusClass}">${booking.status}</span></td>
                <td>${formatDate(booking.booking_date)}</td>
                <td style="min-width: 180px;">
                    ${actionButtons}
                </td>
            </tr>
        `;
    }).join('');
}

// Update Booking Status (Unified function)
async function updateBookingStatus(bookingId, newStatus, bookingNumber) {
    // Get additional info for certain status changes
    let notes = '';
    
    if (newStatus === 'Rejected') {
        notes = prompt(`Enter rejection reason for ${bookingNumber}:`);
        if (!notes) return; // User cancelled
    } else if (newStatus === 'Cancelled') {
        notes = prompt(`Enter cancellation reason for ${bookingNumber}:`);
        if (!notes) return;
    }
    
    // Confirmation message
    const confirmMsg = `Are you sure you want to change ${bookingNumber} to ${newStatus}?`;
    if (!confirm(confirmMsg)) return;
    
    try {
        const response = await fetchAPI('../../api/store_incharge/booking_requests.php', {
            method: 'PUT',
            body: JSON.stringify({
                booking_id: bookingId,
                status: newStatus,
                notes: notes || `Status changed to ${newStatus}`
            })
        });
        
        showNotification(response.message || `Booking ${newStatus.toLowerCase()} successfully`, 'success');
        loadBookingRequests();
        loadDashboardStats();
    } catch (error) {
        showNotification('Error: ' + error.message, 'error');
    }
}

// Handle Booking Status Change from Dropdown
function handleBookingStatusChange(selectElement) {
    const bookingId = selectElement.getAttribute('data-booking-id');
    const bookingNumber = selectElement.getAttribute('data-booking-number');
    const currentStatus = selectElement.getAttribute('data-current-status');
    const newStatus = selectElement.value;
    const oldStatus = selectElement.getAttribute('data-old-status') || currentStatus;
    
    // Store the old value in case user cancels
    if (!selectElement.getAttribute('data-old-status')) {
        selectElement.setAttribute('data-old-status', currentStatus);
    }
    
    // If status hasn't changed, do nothing
    if (newStatus === oldStatus) {
        return;
    }
    
    // Define valid transitions
    const validTransitions = {
        'Pending': ['Confirmed', 'Rejected'],
        'Confirmed': ['Processing', 'Cancelled'],
        'Processing': ['Ready', 'Cancelled'],
        'Ready': ['Delivered', 'Cancelled'],
        'Delivered': ['Return'], // Can only move to Return from Delivered
        'Return': [], // Final state - return completed, no further changes
        'Cancelled': [], // Final state - no changes allowed
        'Rejected': []  // Final state - no changes allowed
    };
    
    // Check if transition is valid
    const allowedTransitions = validTransitions[currentStatus] || [];
    if (!allowedTransitions.includes(newStatus)) {
        showNotification(`Cannot change status from ${currentStatus} to ${newStatus}. Invalid transition.`, 'error');
        selectElement.value = oldStatus;
        return;
    }
    
    // Update the status with proper error handling
    updateBookingStatus(bookingId, newStatus, bookingNumber).then(() => {
        // Update the stored old status on success
        selectElement.setAttribute('data-old-status', newStatus);
        selectElement.setAttribute('data-current-status', newStatus);
    }).catch(() => {
        // Revert dropdown on error
        selectElement.value = oldStatus;
    });
}

// Legacy functions for backward compatibility
async function confirmBooking(bookingId) {
    await updateBookingStatus(bookingId, 'Confirmed', 'this booking');
}

async function rejectBooking(bookingId) {
    await updateBookingStatus(bookingId, 'Rejected', 'this booking');
}

// Barcode Scanner Function
async function scanBarcode() {
    const barcodeInput = document.getElementById('barcodeInput');
    const barcodeResult = document.getElementById('barcodeResult');
    const barcode = barcodeInput.value.trim();
    
    if (!barcode) {
        alert('Please enter or scan a barcode');
        return;
    }
    
    try {
        const data = await fetchAPI(`../../api/store_incharge/barcodes.php?scan=${encodeURIComponent(barcode)}`);
        const barcodeData = data.barcode;
        
        // Update UI with real data
        document.getElementById('productName').textContent = barcodeData.product_name || 'Unknown';
        document.getElementById('productId').textContent = barcodeData.sku || 'N/A';
        document.getElementById('productStock').textContent = barcodeData.stock_quantity + ' units';
        document.getElementById('productStatus').textContent = barcodeData.status;
        
        // Show additional barcode info if available
        if (barcodeData.batch_number) {
            document.getElementById('productName').textContent += ` (Batch: ${barcodeData.batch_number})`;
        }
        if (barcodeData.expiry_date) {
            const expiryInfo = document.createElement('div');
            expiryInfo.className = 'info-item';
            expiryInfo.innerHTML = `
                <span class="info-label">Expiry Date</span>
                <span class="info-value">${formatDate(barcodeData.expiry_date)}</span>
            `;
            document.querySelector('.product-info-grid').appendChild(expiryInfo);
        }
        
        barcodeResult.style.display = 'block';
        barcodeInput.value = '';
        
        showNotification('Barcode scanned successfully', 'success');
    } catch (error) {
        alert('Product not found or barcode invalid');
        console.error('Barcode scan error:', error);
    }
}

// Navigation Functions
function showPage(pageName) {
    // Hide all pages
    document.querySelectorAll('.page-content').forEach(page => {
        page.style.display = 'none';
    });
    
    // Show selected page
    const selectedPage = document.getElementById(pageName + 'Page');
    if (selectedPage) {
        selectedPage.style.display = 'block';
    }
    
    // Update active nav
    navItems.forEach(item => {
        if (item.getAttribute('data-page') === pageName) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
    
    // Load data for the page
    switch(pageName) {
        case 'dashboard':
            loadDashboardStats();
            break;
        case 'bookings':
            loadBookingRequests();
            break;
        case 'delivery':
            loadDeliveries();
            break;
        case 'returns':
            loadReturns();
            break;
    }
    
    // Close sidebar on mobile
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth <= 768 && sidebar) {
        sidebar.classList.remove('active');
    }
}

navItems.forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        const page = item.getAttribute('data-page');
        showPage(page);
    });
});

// Utility Functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Modal Management Functions
function showCreateDeliveryModal() {
    console.log('🚚 Opening create delivery modal');
    
    const modal = document.getElementById('createDeliveryModal');
    if (!modal) {
        console.error('❌ Create delivery modal not found in DOM');
        alert('ERROR: Modal element not found! Check HTML.');
        return;
    }
    
    console.log('📋 Modal element found:', modal);
    console.log('📋 Current modal display style:', modal.style.display);
    console.log('📋 Current modal computed style:', window.getComputedStyle(modal).display);
    
    // Force display with proper styles
    modal.style.display = 'flex';
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    modal.style.zIndex = '9998';
    modal.style.pointerEvents = 'auto';
    
    // Ensure modal content has higher z-index and can receive events
    const modalContent = modal.querySelector('.modal-content');
    if (modalContent) {
        modalContent.style.zIndex = '9999';
        modalContent.style.pointerEvents = 'auto';
        modalContent.style.position = 'relative';
        console.log('✅ Modal content pointer events enabled');
    }
    
    console.log('✅ Modal display set to flex with pointer events');
    console.log('📋 New modal display style:', modal.style.display);
    console.log('📋 Modal offsetWidth:', modal.offsetWidth, 'offsetHeight:', modal.offsetHeight);
    
    // Check if modal is actually visible
    const rect = modal.getBoundingClientRect();
    console.log('📐 Modal position:', rect);
    
    // Reset and initialize form
    resetDeliveryForm();
    console.log('✅ Form reset completed');
    
    loadConfirmedBookings();
    console.log('📋 Loading confirmed bookings...');
    
    // Set default dispatch date to today
    const today = new Date().toISOString().split('T')[0];
    const dispatchInput = document.getElementById('dispatchDate');
    if (dispatchInput) {
        dispatchInput.value = today;
        console.log('📅 Default dispatch date set to:', today);
    } else {
        console.error('❌ Dispatch date input not found');
    }
    
    // Set up event listeners for auto-calculations
    setupDeliveryFormListeners();
    console.log('🔧 Form listeners setup completed');
    
    // Scroll modal into view
    setTimeout(() => {
        if (modalContent) {
            modalContent.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            modal.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        console.log('📜 Modal scrolled into view');
    }, 100);
}

// Reset delivery form to initial state
function resetDeliveryForm() {
    const form = document.getElementById('createDeliveryForm');
    form.reset();
    
    // Hide booking details
    const bookingDetails = document.getElementById('selectedBookingDetails');
    if (bookingDetails) {
        bookingDetails.style.display = 'none';
    }
    
    // Hide priority info
    const priorityInfo = document.getElementById('priorityInfo');
    if (priorityInfo) {
        priorityInfo.style.display = 'none';
    }
    
    // Disable form fields until booking is selected
    enableDeliveryFormFields(false);
    
    // Remove validation classes
    const fields = form.querySelectorAll('.valid, .error');
    fields.forEach(field => {
        field.classList.remove('valid', 'error');
    });
}

// Setup event listeners for delivery form
function setupDeliveryFormListeners() {
    // Auto-calculate expected delivery date when dispatch date changes
    const dispatchDateInput = document.getElementById('dispatchDate');
    dispatchDateInput.addEventListener('change', function() {
        const dispatchDate = new Date(this.value);
        if (dispatchDate) {
            const expectedDate = new Date(dispatchDate);
            expectedDate.setDate(expectedDate.getDate() + 3); // Default 3 days
            document.getElementById('expectedDeliveryDate').value = expectedDate.toISOString().split('T')[0];
        }
    });
    
    // Validate fields on blur
    const requiredFields = ['deliveryBookingSelect', 'courierName', 'dispatchDate', 'recipientName', 'deliveryAddress'];
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('blur', () => validateField(field));
            field.addEventListener('input', () => clearFieldValidation(field));
        }
    });
    
    // Phone number formatting and validation
    const phoneField = document.getElementById('recipientPhone');
    if (phoneField) {
        phoneField.addEventListener('input', formatPhoneNumber);
        phoneField.addEventListener('blur', () => validatePhoneField(phoneField));
    }
}

// Validate individual field
function validateField(field) {
    const value = field.value?.trim();
    let isValid = true;
    
    if (field.required && !value) {
        isValid = false;
    } else if (field.type === 'date' && value) {
        const today = new Date().toISOString().split('T')[0];
        if (field.id === 'dispatchDate' && value < today) {
            isValid = false;
        }
    }
    
    field.classList.toggle('valid', isValid && value);
    field.classList.toggle('error', !isValid && field.required);
    
    return isValid;
}

// Clear field validation
function clearFieldValidation(field) {
    field.classList.remove('valid', 'error');
}

// Validate phone field
function validatePhoneField(field) {
    const value = field.value?.trim();
    if (value && !isValidPhoneNumber(value)) {
        field.classList.add('error');
        return false;
    } else if (value) {
        field.classList.add('valid');
    }
    field.classList.remove('error');
    return true;
}

// Format phone number as user types
function formatPhoneNumber(event) {
    let value = event.target.value.replace(/\D/g, ''); // Remove non-digits
    
    if (value.length > 0) {
        // Format Bangladesh phone numbers
        if (value.startsWith('880')) {
            value = '+880 ' + value.slice(3);
        } else if (value.startsWith('01')) {
            value = '+880 ' + value.slice(1);
        } else if (value.length === 11 && value.startsWith('1')) {
            value = '+880 ' + value;
        }
        
        // Add spacing for readability
        if (value.startsWith('+880 ')) {
            const number = value.slice(5);
            if (number.length > 3) {
                value = '+880 ' + number.slice(0, 3) + ' ' + number.slice(3);
            }
            if (number.length > 6) {
                value = '+880 ' + number.slice(0, 3) + ' ' + number.slice(3, 6) + ' ' + number.slice(6);
            }
        }
    }
    
    event.target.value = value;
}

// Preview delivery before creation
function previewDelivery() {
    const formData = gatherDeliveryFormData();
    const summaryContainer = document.getElementById('deliverySummary');
    const summaryContent = document.getElementById('summaryContent');
    
    if (!formData.booking_id) {
        showNotification('Please select a booking first', 'error');
        return;
    }
    
    const booking = document.getElementById('deliveryBookingSelect');
    const selectedOption = booking.options[booking.selectedIndex];
    
    summaryContent.innerHTML = `
        <div class="summary-section">
            <h5>📋 Booking Information</h5>
            <div class="summary-item">
                <span>Booking Number:</span>
                <span>#${selectedOption.textContent.split(' - ')[0].replace('#', '')}</span>
            </div>
            <div class="summary-item">
                <span>Customer:</span>
                <span>${formData.recipient_name}</span>
            </div>
            <div class="summary-item">
                <span>Product:</span>
                <span>${selectedOption.dataset.productName} (${selectedOption.dataset.quantity} units)</span>
            </div>
        </div>
        
        <div class="summary-section">
            <h5>🚚 Delivery Details</h5>
            <div class="summary-item">
                <span>Courier:</span>
                <span>${formData.courier_name || 'Not specified'}</span>
            </div>
            <div class="summary-item">
                <span>Tracking Number:</span>
                <span>${formData.tracking_number || 'Auto-generate'}</span>
            </div>
            <div class="summary-item">
                <span>Dispatch Date:</span>
                <span>${formatDate(formData.dispatch_date)}</span>
            </div>
            <div class="summary-item">
                <span>Expected Delivery:</span>
                <span>${formData.expected_delivery_date ? formatDate(formData.expected_delivery_date) : 'Not set'}</span>
            </div>
        </div>
        
        <div class="summary-section">
            <h5>📍 Recipient Information</h5>
            <div class="summary-item">
                <span>Name:</span>
                <span>${formData.recipient_name}</span>
            </div>
            <div class="summary-item">
                <span>Phone:</span>
                <span>${formData.recipient_phone || 'Not provided'}</span>
            </div>
            <div class="summary-item address">
                <span>Address:</span>
                <span>${formData.delivery_address}</span>
            </div>
            ${formData.delivery_notes ? `
                <div class="summary-item notes">
                    <span>Instructions:</span>
                    <span>${formData.delivery_notes}</span>
                </div>
            ` : ''}
        </div>
    `;
    
    summaryContainer.style.display = 'block';
    summaryContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Gather form data for processing
function gatherDeliveryFormData() {
    console.log('📦 Gathering delivery form data...');
    
    const formData = {
        booking_id: document.getElementById('deliveryBookingSelect').value,
        courier_name: document.getElementById('courierName').value?.trim(),
        tracking_number: document.getElementById('trackingNumber').value?.trim(),
        dispatch_date: document.getElementById('dispatchDate').value,
        expected_delivery_date: document.getElementById('expectedDeliveryDate').value,
        delivery_address: document.getElementById('deliveryAddress').value?.trim(),
        recipient_name: document.getElementById('recipientName').value?.trim(),
        recipient_phone: document.getElementById('recipientPhone').value?.trim(),
        delivery_notes: document.getElementById('deliveryNotes').value?.trim()
    };
    
    console.log('📋 Form data collected:', JSON.stringify(formData, null, 2));
    
    // Validate that we have the critical data
    const criticalFields = ['booking_id', 'dispatch_date', 'recipient_name', 'delivery_address'];
    const missingCritical = criticalFields.filter(field => !formData[field]);
    
    if (missingCritical.length > 0) {
        console.error('❌ Missing critical fields:', missingCritical);
    } else {
        console.log('✅ All critical fields present');
    }
    
    return formData;
}

// Check form completion and show preview button
function checkFormCompletion() {
    const requiredFields = [
        'deliveryBookingSelect', 'courierName', 'dispatchDate', 
        'recipientName', 'deliveryAddress'
    ];
    
    const allFilled = requiredFields.every(fieldId => {
        const field = document.getElementById(fieldId);
        return field && field.value?.trim();
    });
    
    const previewBtn = document.getElementById('previewBtn');
    if (previewBtn) {
        previewBtn.style.display = allFilled ? 'inline-flex' : 'none';
    }
}

// Enhanced form listeners setup
function setupEnhancedDeliveryFormListeners() {
    // Track form completion for preview button
    const allFields = document.querySelectorAll('#createDeliveryForm input, #createDeliveryForm select, #createDeliveryForm textarea');
    allFields.forEach(field => {
        field.addEventListener('input', checkFormCompletion);
        field.addEventListener('change', checkFormCompletion);
    });
    
    // Auto-save form data to prevent data loss
    const formData = {};
    allFields.forEach(field => {
        field.addEventListener('input', () => {
            formData[field.id] = field.value;
            localStorage.setItem('deliveryFormDraft', JSON.stringify(formData));
        });
    });
    
    // Restore form data if available
    const savedData = localStorage.getItem('deliveryFormDraft');
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && data[fieldId]) {
                    field.value = data[fieldId];
                }
            });
        } catch (e) {
            console.log('Could not restore form data');
        }
    }
}

// Clear saved form data after successful submission
function clearSavedFormData() {
    localStorage.removeItem('deliveryFormDraft');
}

function showCreateReturnModal() {
    document.getElementById('createReturnModal').style.display = 'flex';
    loadDeliveredBookings();
}

function showUpdateDeliveryModal(deliveryId) {
    const modal = document.getElementById('updateDeliveryModal');
    if (!modal) {
        console.error('❌ Update delivery modal not found');
        return;
    }
    
    // Force display with proper styles (same as create delivery modal)
    modal.style.display = 'flex';
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    modal.style.zIndex = '9998';
    modal.style.pointerEvents = 'auto';
    
    // Ensure modal content has higher z-index and can receive events
    const modalContent = modal.querySelector('.modal-content');
    if (modalContent) {
        modalContent.style.zIndex = '9999';
        modalContent.style.pointerEvents = 'auto';
    }
    
    // Set the delivery ID
    document.getElementById('updateDeliveryId').value = deliveryId;
    
    console.log('✅ Update delivery modal opened for delivery ID:', deliveryId);
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    // Reset forms
    const modal = document.getElementById(modalId);
    const forms = modal.querySelectorAll('form');
    forms.forEach(form => form.reset());
}

// Load Confirmed Bookings for Delivery Creation
async function loadConfirmedBookings() {
    try {
        const data = await fetchAPI('../../api/store_incharge/booking_requests.php?status=Processing,Ready');
        const select = document.getElementById('deliveryBookingSelect');
        select.innerHTML = '<option value="">Select a ready booking for delivery...</option>';
        
        if (data.bookings && data.bookings.length > 0) {
            data.bookings.forEach(booking => {
                const option = document.createElement('option');
                option.value = booking.id;
                option.textContent = `#${booking.booking_number} - ${booking.customer_name} (${booking.product_name} - Qty: ${booking.quantity})`;
                option.dataset.customer = booking.customer_name;
                option.dataset.phone = booking.customer_phone || '';
                option.dataset.address = booking.customer_address || '';
                option.dataset.productName = booking.product_name;
                option.dataset.quantity = booking.quantity;
                option.dataset.priority = booking.priority;
                option.dataset.bookingDate = booking.booking_date;
                option.dataset.totalAmount = booking.total_amount;
                select.appendChild(option);
            });
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No bookings available for delivery';
            option.disabled = true;
            select.appendChild(option);
        }
        
        // Auto-fill customer details when booking is selected
        select.addEventListener('change', handleBookingSelection);
    } catch (error) {
        console.error('Error loading bookings:', error);
        showNotification('Failed to load bookings', 'error');
        const select = document.getElementById('deliveryBookingSelect');
        select.innerHTML = '<option value="">Error loading bookings...</option>';
    }
}

// Handle booking selection and auto-fill form
function handleBookingSelection() {
    const select = document.getElementById('deliveryBookingSelect');
    const option = select.options[select.selectedIndex];
    
    if (option.value && option.dataset.customer) {
        // Fill customer details
        document.getElementById('recipientName').value = option.dataset.customer;
        document.getElementById('recipientPhone').value = option.dataset.phone;
        document.getElementById('deliveryAddress').value = option.dataset.address;
        
        // Show booking details
        const bookingDetails = document.getElementById('selectedBookingDetails');
        if (bookingDetails) {
            bookingDetails.innerHTML = `
                <div class="booking-info-card">
                    <h4>Booking Details</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Product:</span>
                            <span class="value">${escapeHtml(option.dataset.productName)}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Quantity:</span>
                            <span class="value">${option.dataset.quantity} units</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Priority:</span>
                            <span class="value priority-${option.dataset.priority?.toLowerCase()}">${option.dataset.priority}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Booking Date:</span>
                            <span class="value">${formatDate(option.dataset.bookingDate)}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Total Amount:</span>
                            <span class="value">${formatCurrency(option.dataset.totalAmount)}</span>
                        </div>
                    </div>
                </div>
            `;
            bookingDetails.style.display = 'block';
        }
        
        // Show priority warning for high priority bookings
        const priorityInfo = document.getElementById('priorityInfo');
        if (priorityInfo && option.dataset.priority?.toLowerCase() === 'high') {
            document.getElementById('priorityText').textContent = 'High Priority - Expedited Delivery Required';
            priorityInfo.style.display = 'flex';
            
            // Suggest next day delivery for high priority
            const dispatchDate = document.getElementById('dispatchDate').value;
            if (dispatchDate) {
                const expectedDate = new Date(dispatchDate);
                expectedDate.setDate(expectedDate.getDate() + 1); // Next day for high priority
                document.getElementById('expectedDeliveryDate').value = expectedDate.toISOString().split('T')[0];
            }
        } else {
            priorityInfo.style.display = 'none';
        }
        
        // Auto-generate expected delivery date (3 days from dispatch)
        const dispatchDate = document.getElementById('dispatchDate').value;
        if (dispatchDate) {
            const expectedDate = new Date(dispatchDate);
            expectedDate.setDate(expectedDate.getDate() + 3);
            document.getElementById('expectedDeliveryDate').value = expectedDate.toISOString().split('T')[0];
        }
        
        // Enable form fields
        enableDeliveryFormFields(true);
    } else {
        // Clear form if no booking selected
        clearDeliveryForm();
        enableDeliveryFormFields(false);
    }
}

// Enable/disable form fields based on booking selection
function enableDeliveryFormFields(enabled) {
    const fields = ['courierName', 'trackingNumber', 'expectedDeliveryDate', 'deliveryNotes'];
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.disabled = !enabled;
        }
    });
}

// Clear delivery form
function clearDeliveryForm() {
    const fields = ['recipientName', 'recipientPhone', 'deliveryAddress', 'courierName', 
                   'trackingNumber', 'expectedDeliveryDate', 'deliveryNotes'];
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = '';
        }
    });
    
    const bookingDetails = document.getElementById('selectedBookingDetails');
    if (bookingDetails) {
        bookingDetails.style.display = 'none';
    }
}

// Load Delivered Bookings for Return Creation
async function loadDeliveredBookings() {
    try {
        const data = await fetchAPI('../../api/store_incharge/booking_requests.php?status=Delivered');
        const select = document.getElementById('returnBookingSelect');
        select.innerHTML = '<option value="">Select a delivered booking...</option>';
        
        data.bookings?.forEach(booking => {
            const option = document.createElement('option');
            option.value = booking.id;
            option.textContent = `#${booking.booking_number} - ${booking.customer_name} (${booking.product_name})`;
            option.dataset.quantity = booking.quantity;
            select.appendChild(option);
        });
        
        // Set max return quantity when booking is selected
        select.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (option.dataset.quantity) {
                const quantityInput = document.getElementById('returnQuantity');
                quantityInput.max = option.dataset.quantity;
                quantityInput.value = 1;
            }
        });
    } catch (error) {
        console.error('Error loading delivered bookings:', error);
        showNotification('Failed to load delivered bookings', 'error');
    }
}

// Create Delivery
async function createDelivery(event) {
    console.log('🚚 Create delivery function called');
    console.log('📝 Event object:', event);
    
    event.preventDefault();
    console.log('✅ Default form submission prevented');
    
    // Validate form
    console.log('🔍 Starting form validation...');
    if (!validateDeliveryForm()) {
        console.error('❌ Form validation failed');
        return;
    }
    console.log('✅ Form validation passed');
    
    // Show loading state
    const submitButton = event.target.querySelector('button[type="submit"]');
    console.log('🎯 Submit button found:', submitButton);
    
    if (!submitButton) {
        console.error('❌ Submit button not found in form');
        return;
    }
    
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Delivery...';
    submitButton.disabled = true;
    submitButton.classList.add('loading');
    console.log('🔄 Loading state applied to submit button');
    
    // Disable other buttons
    const cancelButton = event.target.querySelector('button[type="button"]');
    const previewButton = document.getElementById('previewBtn');
    if (cancelButton) {
        cancelButton.disabled = true;
        console.log('🚫 Cancel button disabled');
    }
    if (previewButton) {
        previewButton.disabled = true;
        console.log('🚫 Preview button disabled');
    }
    
    const formData = gatherDeliveryFormData();
    console.log('📦 Form data gathered:', formData);
    
    try {
        console.log('🌐 Sending API request to create delivery...');
        const response = await fetchAPI('../../api/store_incharge/deliveries.php', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        console.log('✅ API Response received:', response);
        
        // Show success with tracking number if provided
        const successMessage = response.tracking_number 
            ? `Delivery created successfully! Tracking Number: ${response.tracking_number}`
            : response.message || 'Delivery created successfully';
            
        console.log('📢 Success message:', successMessage);
        showNotification(successMessage, 'success');
        
        // Clear saved form data
        clearSavedFormData();
        console.log('🧹 Saved form data cleared');
        
        // Close modal and refresh data
        closeModal('createDeliveryModal');
        console.log('❌ Modal closed');
        
        // Refresh data
        const deliveryPage = document.getElementById('deliveryPage');
        if (deliveryPage && deliveryPage.style.display !== 'none') {
            console.log('🔄 Refreshing deliveries page...');
            loadDeliveries();
        }
        console.log('🔄 Refreshing dashboard stats...');
        loadDashboardStats();
        
        // Optional: Show success modal with tracking details
        if (response.tracking_number) {
            console.log('🏷️ Showing tracking success modal');
            showTrackingSuccessModal(response);
        }
        
    } catch (error) {
        console.error('❌ Error creating delivery:', error);
        console.error('📋 Error details:', {
            message: error.message,
            stack: error.stack
        });
        
        showNotification('Error creating delivery: ' + error.message, 'error');
        
        // Re-enable form for correction
        if (cancelButton) {
            cancelButton.disabled = false;
            console.log('✅ Cancel button re-enabled');
        }
        if (previewButton) {
            previewButton.disabled = false;
            console.log('✅ Preview button re-enabled');
        }
    } finally {
        // Reset button state
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
        submitButton.classList.remove('loading');
        console.log('🔄 Submit button state reset');
    }
}

// Show success modal with tracking information
function showTrackingSuccessModal(response) {
    const trackingInfo = `
        <div class="success-modal">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Delivery Created Successfully!</h3>
            <div class="tracking-info">
                <p><strong>Tracking Number:</strong> ${response.tracking_number}</p>
                <p><strong>Delivery ID:</strong> #${response.delivery_id}</p>
                <p class="note">The customer will be notified about the dispatch.</p>
            </div>
            <button class="btn btn-primary" onclick="closeSuccessModal()">
                <i class="fas fa-thumbs-up"></i> Got it!
            </button>
        </div>
    `;
    
    // This could be implemented as a separate modal or notification
    // For now, we'll use the existing notification system
    console.log('Tracking success info:', response);
}

// Validate delivery form
function validateDeliveryForm() {
    console.log('🔍 Starting delivery form validation...');
    const errors = [];
    
    // Check required fields
    const bookingId = document.getElementById('deliveryBookingSelect').value;
    console.log('📋 Booking ID:', bookingId);
    if (!bookingId) {
        errors.push('Please select a booking');
        console.log('❌ Booking ID missing');
    }
    
    const dispatchDate = document.getElementById('dispatchDate').value;
    console.log('📅 Dispatch date:', dispatchDate);
    if (!dispatchDate) {
        errors.push('Dispatch date is required');
        console.log('❌ Dispatch date missing');
    }
    
    const recipientName = document.getElementById('recipientName').value?.trim();
    console.log('👤 Recipient name:', recipientName);
    if (!recipientName) {
        errors.push('Recipient name is required');
        console.log('❌ Recipient name missing');
    }
    
    const deliveryAddress = document.getElementById('deliveryAddress').value?.trim();
    console.log('📍 Delivery address:', deliveryAddress);
    if (!deliveryAddress) {
        errors.push('Delivery address is required');
        console.log('❌ Delivery address missing');
    }
    
    // Validate dates
    if (dispatchDate) {
        const today = new Date().toISOString().split('T')[0];
        console.log('📅 Today:', today, 'Dispatch:', dispatchDate);
        if (dispatchDate < today) {
            errors.push('Dispatch date cannot be in the past');
            console.log('❌ Dispatch date is in the past');
        }
    }
    
    const expectedDate = document.getElementById('expectedDeliveryDate').value;
    console.log('📅 Expected delivery date:', expectedDate);
    if (expectedDate && dispatchDate && expectedDate < dispatchDate) {
        errors.push('Expected delivery date must be after dispatch date');
        console.log('❌ Expected date is before dispatch date');
    }
    
    // Validate phone number if provided
    const phone = document.getElementById('recipientPhone').value?.trim();
    console.log('📞 Phone number:', phone);
    if (phone && !isValidPhoneNumber(phone)) {
        errors.push('Please enter a valid phone number');
        console.log('❌ Invalid phone number format');
    }
    
    // Show errors if any
    if (errors.length > 0) {
        console.error('❌ Validation errors found:', errors);
        showNotification('Please fix the following errors:\n• ' + errors.join('\n• '), 'error');
        return false;
    }
    
    console.log('✅ Form validation passed');
    return true;
}

// Validate phone number format
function isValidPhoneNumber(phone) {
    // Basic phone validation - adjust regex as needed for your region
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
    return phoneRegex.test(phone);
}

// Create Return
async function createReturn(event) {
    event.preventDefault();
    
    const formData = {
        booking_id: document.getElementById('returnBookingSelect').value,
        return_reason: document.getElementById('returnReason').value,
        quantity_returned: document.getElementById('returnQuantity').value,
        customer_description: document.getElementById('returnDescription').value,
        notification_preference: document.getElementById('customerNotification').value
    };
    
    try {
        const response = await fetchAPI('../../api/store_incharge/returns.php', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        showNotification(response.message || 'Return request created successfully', 'success');
        closeModal('createReturnModal');
        loadReturns();
        loadDashboardStats();
    } catch (error) {
        showNotification('Error creating return: ' + error.message, 'error');
    }
}

// Update Delivery Status
async function updateDeliveryStatus(deliveryId, newStatus) {
    try {
        const response = await fetchAPI('../../api/store_incharge/deliveries.php', {
            method: 'PUT',
            body: JSON.stringify({
                delivery_id: deliveryId,
                delivery_status: newStatus,
                status_description: `Status updated to ${newStatus}`,
                location: newStatus === 'Delivered' ? 'Customer Location' : null
            })
        });
        
        showNotification(response.message || 'Delivery status updated successfully', 'success');
        loadDeliveries();
        loadDashboardStats();
    } catch (error) {
        showNotification('Error updating delivery status: ' + error.message, 'error');
    }
}

// Update Delivery Status from Modal Form
async function updateDeliveryFromModal(event) {
    event.preventDefault();
    
    const deliveryId = document.getElementById('updateDeliveryId').value;
    const newStatus = document.getElementById('newDeliveryStatus').value;
    const location = document.getElementById('deliveryLocation').value?.trim();
    const description = document.getElementById('statusDescription').value?.trim();
    
    if (!deliveryId || !newStatus) {
        showNotification('Please select a delivery status', 'error');
        return;
    }
    
    // Show loading state
    const submitButton = event.target.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    submitButton.disabled = true;
    
    try {
        const response = await fetchAPI('../../api/store_incharge/deliveries.php', {
            method: 'PUT',
            body: JSON.stringify({
                delivery_id: deliveryId,
                delivery_status: newStatus,
                status_description: description,
                location: location || null
            })
        });
        
        showNotification(response.message || 'Delivery status updated successfully', 'success');
        closeModal('updateDeliveryModal');
        loadDeliveries();
        loadDashboardStats();
    } catch (error) {
        showNotification('Error updating delivery status: ' + error.message, 'error');
    } finally {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }
}

// Update Return Status
async function updateReturnStatus(returnId, newStatus) {
    const confirmMsg = `Are you sure you want to ${newStatus.toLowerCase()} this return?`;
    if (!confirm(confirmMsg)) return;
    
    try {
        const response = await fetchAPI('../../api/store_incharge/returns.php', {
            method: 'PUT',
            body: JSON.stringify({
                return_id: returnId,
                status: newStatus,
                inspection_notes: `Return ${newStatus.toLowerCase()} by store in-charge`
            })
        });
        
        showNotification(response.message || `Return ${newStatus.toLowerCase()} successfully`, 'success');
        loadReturns();
        loadDashboardStats();
    } catch (error) {
        showNotification('Error updating return status: ' + error.message, 'error');
    }
}

// Filter Functions
function filterDeliveries() {
    const statusFilter = document.getElementById('deliveryStatusFilter').value;
    const dateFilter = document.getElementById('deliveryDateFilter').value;
    const trackingFilter = document.getElementById('trackingSearchFilter').value;
    
    let url = '../../api/store_incharge/deliveries.php?';
    const params = new URLSearchParams();
    
    if (statusFilter) params.append('status', statusFilter);
    if (dateFilter) params.append('date', dateFilter);
    if (trackingFilter) params.append('tracking', trackingFilter);
    
    loadDeliveriesWithFilters(url + params.toString());
}

function filterReturns() {
    const statusFilter = document.getElementById('returnStatusFilter').value;
    const reasonFilter = document.getElementById('returnReasonFilter').value;
    const dateFilter = document.getElementById('returnDateFilter').value;
    
    let url = '../../api/store_incharge/returns.php?';
    const params = new URLSearchParams();
    
    if (statusFilter) params.append('status', statusFilter);
    if (reasonFilter) params.append('reason', reasonFilter);
    if (dateFilter) params.append('date', dateFilter);
    
    loadReturnsWithFilters(url + params.toString());
}

async function loadDeliveriesWithFilters(url) {
    try {
        const data = await fetchAPI(url);
        renderDeliveries(data.deliveries || []);
    } catch (error) {
        console.error('Error filtering deliveries:', error);
        showNotification('Failed to filter deliveries', 'error');
    }
}

async function loadReturnsWithFilters(url) {
    try {
        const data = await fetchAPI(url);
        renderReturns(data.returns || []);
    } catch (error) {
        console.error('Error filtering returns:', error);
        showNotification('Failed to filter returns', 'error');
    }
}

// Refresh Functions
function refreshDeliveries() {
    loadDeliveries();
    showNotification('Deliveries refreshed', 'success');
}

function refreshReturns() {
    loadReturns();
    showNotification('Returns refreshed', 'success');
}

// View Return Details
function viewReturnDetails(returnId) {
    // This could open a detailed modal with return information
    console.log('Viewing return details for ID:', returnId);
    // Implementation depends on specific requirements
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Store In-charge dashboard initializing...');
    
    // Check if all required elements exist
    const requiredElements = [
        'createDeliveryModal',
        'createDeliveryForm', 
        'deliveryBookingSelect',
        'dispatchDate',
        'courierName'
    ];
    
    const missingElements = [];
    requiredElements.forEach(elementId => {
        const element = document.getElementById(elementId);
        if (!element) {
            missingElements.push(elementId);
        } else {
            console.log(`✅ Found element: ${elementId}`);
        }
    });
    
    if (missingElements.length > 0) {
        console.error('❌ Missing required elements:', missingElements);
    } else {
        console.log('✅ All required elements found');
    }
    
    const barcodeInput = document.getElementById('barcodeInput');
    if (barcodeInput) {
        barcodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                scanBarcode();
            }
        });
        console.log('✅ Barcode input listener added');
    }
    
    // Initialize form event listeners
    const createDeliveryForm = document.getElementById('createDeliveryForm');
    if (createDeliveryForm) {
        console.log('🚚 Setting up create delivery form...');
        
        // Remove any existing listeners to prevent duplicates
        const newForm = createDeliveryForm.cloneNode(true);
        createDeliveryForm.parentNode.replaceChild(newForm, createDeliveryForm);
        
        // Add fresh event listener
        const freshForm = document.getElementById('createDeliveryForm');
        freshForm.addEventListener('submit', function(event) {
            console.log('📝 Form submit event triggered');
            createDelivery(event);
        });
        
        setupEnhancedDeliveryFormListeners();
        console.log('✅ Create delivery form listeners setup complete');
    } else {
        console.error('❌ Create delivery form not found');
    }
    
    const createReturnForm = document.getElementById('createReturnForm');
    if (createReturnForm) {
        createReturnForm.addEventListener('submit', createReturn);
        console.log('✅ Create return form listener added');
    }
    
    // Add event listener for update delivery form
    const updateDeliveryForm = document.getElementById('updateDeliveryForm');
    if (updateDeliveryForm) {
        updateDeliveryForm.addEventListener('submit', updateDeliveryFromModal);
        console.log('✅ Update delivery form listener added');
    }
    
    // Show proof of delivery field when status is 'Delivered'
    const newDeliveryStatus = document.getElementById('newDeliveryStatus');
    if (newDeliveryStatus) {
        newDeliveryStatus.addEventListener('change', function() {
            const proofGroup = document.getElementById('proofOfDeliveryGroup');
            if (this.value === 'Delivered') {
                proofGroup.style.display = 'block';
            } else {
                proofGroup.style.display = 'none';
            }
        });
        console.log('✅ Delivery status change listener added');
    }
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                // User clicked on backdrop, close the modal
                const modalId = modal.id;
                if (modalId) {
                    closeModal(modalId);
                    console.log('🚪 Modal closed by backdrop click:', modalId);
                }
            }
        });
    });
    console.log('✅ Modal close listeners added');
    
    // Test button click directly
    setTimeout(() => {
        const testButton = document.querySelector('button[onclick="showCreateDeliveryModal()"]');
        if (testButton) {
            console.log('🔧 Create delivery button found:', testButton);
            console.log('🔧 Button onclick attribute:', testButton.getAttribute('onclick'));
            
            // Add click listener as backup
            testButton.addEventListener('click', function(e) {
                console.log('🖱️ Create delivery button clicked via event listener');
                e.preventDefault();
                showCreateDeliveryModal();
            });
            console.log('✅ Backup click listener added to create delivery button');
        } else {
            console.error('❌ Create delivery button not found');
            
            // Try to find any create delivery buttons
            const allButtons = document.querySelectorAll('button');
            console.log('🔍 All buttons found:', allButtons.length);
            allButtons.forEach((btn, index) => {
                if (btn.textContent.includes('Create Delivery') || btn.innerHTML.includes('Create Delivery')) {
                    console.log(`🔧 Found create delivery button ${index}:`, btn);
                }
            });
        }
    }, 1000);
    
    // Load initial dashboard data
    loadDashboardStats();
    
    console.log('🎉 Store In-charge dashboard loaded successfully');
});

// Global test functions for debugging (available in console)
window.testCreateDeliveryModal = function() {
    console.log('🧪 Testing create delivery modal...');
    try {
        showCreateDeliveryModal();
        console.log('✅ Modal function executed successfully');
    } catch (error) {
        console.error('❌ Error testing modal:', error);
    }
};

window.forceShowModal = function() {
    console.log('🧪 Force showing modal...');
    const modal = document.getElementById('createDeliveryModal');
    if (modal) {
        modal.style.cssText = 'display: flex !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background: rgba(0,0,0,0.7) !important; z-index: 99999 !important; align-items: center !important; justify-content: center !important;';
        console.log('✅ Modal forced to display');
        console.log('📋 Modal styles:', modal.style.cssText);
    } else {
        console.error('❌ Modal not found');
    }
};

window.testCreateDeliveryForm = function() {
    console.log('🧪 Testing create delivery form submission...');
    const form = document.getElementById('createDeliveryForm');
    if (form) {
        // Fill form with test data
        document.getElementById('deliveryBookingSelect').value = '1'; // Assuming booking ID 1 exists
        document.getElementById('courierName').value = 'Pathao';
        document.getElementById('dispatchDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('recipientName').value = 'Test Recipient';
        document.getElementById('deliveryAddress').value = 'Test Address';
        
        console.log('📝 Test data filled');
        
        // Trigger form submission
        const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
        form.dispatchEvent(submitEvent);
        console.log('📤 Form submission triggered');
    } else {
        console.error('❌ Form not found');
    }
};

window.debugDeliveryForm = function() {
    console.log('🔍 Debugging delivery form elements...');
    
    const elements = [
        'createDeliveryModal',
        'createDeliveryForm',
        'deliveryBookingSelect',
        'courierName',
        'dispatchDate',
        'recipientName',
        'deliveryAddress'
    ];
    
    elements.forEach(id => {
        const element = document.getElementById(id);
        console.log(`${id}:`, element ? '✅ Found' : '❌ Missing', element);
    });
    
    const form = document.getElementById('createDeliveryForm');
    if (form) {
        console.log('Form event listeners:', getEventListeners ? getEventListeners(form) : 'getEventListeners not available');
    }
};

console.log('🧪 Debug functions available:');
console.log('  - testCreateDeliveryModal() - Test modal opening');
console.log('  - forceShowModal() - Force modal to display');
console.log('  - testCreateDeliveryForm() - Test form submission');  
console.log('  - debugDeliveryForm() - Debug form elements');
console.log('💡 Call these functions from the console to test functionality');
