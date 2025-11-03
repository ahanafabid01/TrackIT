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
    try {
        const response = await fetch(endpoint, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'API request failed');
        }
        return data;
    } catch (error) {
        console.error('API Error:', error);
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
        
        // Determine next available actions based on current status
        let actionButtons = '';
        
        switch(booking.status) {
            case 'Pending':
                actionButtons = `
                    <button class="btn btn-sm btn-success" onclick="updateBookingStatus(${booking.id}, 'Confirmed', '${booking.booking_number}')">
                        <i class="fas fa-check"></i> Confirm
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="updateBookingStatus(${booking.id}, 'Rejected', '${booking.booking_number}')">
                        <i class="fas fa-times"></i> Reject
                    </button>
                `;
                break;
            case 'Confirmed':
                actionButtons = `
                    <button class="btn btn-sm btn-primary" onclick="updateBookingStatus(${booking.id}, 'Processing', '${booking.booking_number}')">
                        <i class="fas fa-cog"></i> Start Processing
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="updateBookingStatus(${booking.id}, 'Cancelled', '${booking.booking_number}')">
                        <i class="fas fa-ban"></i> Cancel
                    </button>
                `;
                break;
            case 'Processing':
                actionButtons = `
                    <button class="btn btn-sm btn-success" onclick="updateBookingStatus(${booking.id}, 'Ready', '${booking.booking_number}')">
                        <i class="fas fa-box-check"></i> Mark Ready
                    </button>
                    <button class="btn btn-sm btn-info" onclick="updateBookingStatus(${booking.id}, 'Delivered', '${booking.booking_number}')">
                        <i class="fas fa-truck"></i> Deliver
                    </button>
                `;
                break;
            case 'Ready':
                actionButtons = `
                    <button class="btn btn-sm btn-info" onclick="updateBookingStatus(${booking.id}, 'Delivered', '${booking.booking_number}')">
                        <i class="fas fa-truck"></i> Mark Delivered
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="updateBookingStatus(${booking.id}, 'Cancelled', '${booking.booking_number}')">
                        <i class="fas fa-ban"></i> Cancel
                    </button>
                `;
                break;
            case 'Cancelled':
                actionButtons = `<span class="text-muted"><i class="fas fa-ban"></i> Cancelled</span>`;
                break;
            default:
                actionButtons = `<span class="text-muted">${booking.status}</span>`;
        }
        
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
            // loadDeliveries(); // Implement when delivery page is ready
            break;
        case 'returns':
            // loadReturns(); // Implement when returns page is ready
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const barcodeInput = document.getElementById('barcodeInput');
    if (barcodeInput) {
        barcodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                scanBarcode();
            }
        });
    }
    
    // Load initial dashboard data
    loadDashboardStats();
    
    console.log('Store In-charge dashboard loaded successfully');
});
