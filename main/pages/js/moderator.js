// Moderator Dashboard JavaScript - Complete Implementation
// API Base URLs
const API_BASE = '../../api/moderator';

// Global State
let currentPage = {
    bookings: 1,
    customers: 1,
    products: 1
};

let currentFilters = {
    bookings: {},
    customers: {},
    products: {}
};

// ========================================
// INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('Moderator dashboard loaded successfully');
    
    // Initialize dashboard
    loadDashboardData();
    
    // Set up event listeners
    setupEventListeners();
});

function setupEventListeners() {
    // Sidebar Toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
    
    // Navigation
    const navItems = document.querySelectorAll('.nav-item[data-page]');
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const page = item.getAttribute('data-page');
            showPage(page);
        });
    });
    
    // Global search
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keyup', debounce(function(e) {
            const query = e.target.value.trim();
            if (query.length >= 2) {
                performGlobalSearch(query);
            }
        }, 500));
    }
}

// ========================================
// PAGE NAVIGATION
// ========================================
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
    const navItems = document.querySelectorAll('.nav-item[data-page]');
    navItems.forEach(item => {
        if (item.getAttribute('data-page') === pageName) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
    
    // Load page-specific data
    loadPageData(pageName);
    
    // Close sidebar on mobile
    if (window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.remove('active');
    }
}

function loadPageData(pageName) {
    switch(pageName) {
        case 'bookings':
            loadBookings();
            break;
        case 'customers':
            loadCustomers();
            break;
        case 'products':
            loadProducts();
            break;
        case 'reports':
            loadReminders();
            break;
    }
}

function loadDashboardData() {
    // Dashboard is loaded with PHP, but we can refresh stats if needed
    console.log('Dashboard loaded');
}

// ========================================
// BOOKINGS MANAGEMENT
// ========================================
async function loadBookings(page = 1) {
    const tbody = document.getElementById('bookingsTableBody');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i><p style="margin-top: 10px;">Loading...</p></td></tr>';
    
    try {
        const status = currentFilters.bookings.status || '';
        const search = currentFilters.bookings.search || '';
        
        const url = `${API_BASE}/bookings.php?page=${page}&limit=10${status ? `&status=${status}` : ''}${search ? `&search=${encodeURIComponent(search)}` : ''}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            displayBookings(data.bookings);
            displayPagination('bookings', data.pagination);
            currentPage.bookings = page;
        } else {
            throw new Error(data.error || 'Failed to load bookings');
        }
    } catch (error) {
        console.error('Error loading bookings:', error);
        tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; padding: 40px; color: #ef4444;">
            <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i>
            <p style="margin-top: 10px;">Error loading bookings: ${error.message}</p>
        </td></tr>`;
    }
}

function displayBookings(bookings) {
    const tbody = document.getElementById('bookingsTableBody');
    
    if (bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #64748b;">No bookings found</td></tr>';
        return;
    }
    
    tbody.innerHTML = bookings.map(booking => `
        <tr>
            <td><strong>${booking.booking_number}</strong></td>
            <td>
                <div class="user-cell">
                    <div class="user-cell-avatar">${getInitials(booking.customer_name)}</div>
                    <div class="user-cell-info">
                        <div class="user-cell-name">${escapeHtml(booking.customer_name)}</div>
                        <div class="user-cell-email">${escapeHtml(booking.customer_phone)}</div>
                    </div>
                </div>
            </td>
            <td>${escapeHtml(booking.product_name)}</td>
            <td>${booking.quantity}</td>
            <td>৳${parseFloat(booking.total_amount).toFixed(2)}</td>
            <td><span class="badge badge-${getStatusColor(booking.status)}">${booking.status}</span></td>
            <td style="color: #64748b;">${formatDate(booking.booking_date)}</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn" onclick="viewBooking(${booking.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn" onclick="editBooking(${booking.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    ${booking.status !== 'Delivered' ? `
                    <button class="action-btn" onclick="updateBookingStatus(${booking.id}, '${booking.status}')" title="Update Status">
                        <i class="fas fa-sync"></i>
                    </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

function filterBookings() {
    const status = document.getElementById('bookingStatusFilter').value;
    currentFilters.bookings.status = status;
    loadBookings(1);
}

function searchBookings() {
    const search = document.getElementById('bookingSearch').value;
    currentFilters.bookings.search = search;
    clearTimeout(searchBookings.timeout);
    searchBookings.timeout = setTimeout(() => loadBookings(1), 500);
}

async function openBookingModal(bookingId = null) {
    // Load customers and products for dropdown
    const [customers, products] = await Promise.all([
        fetch(`${API_BASE}/customers.php?limit=100`).then(r => r.json()),
        fetch(`${API_BASE}/products.php?status=Active&limit=100`).then(r => r.json())
    ]);
    
    const booking = bookingId ? await loadBookingDetails(bookingId) : null;
    
    const modal = createModal({
        title: bookingId ? 'Edit Booking' : 'New Booking',
        size: 'large',
        content: `
            <form id="bookingForm">
                <input type="hidden" id="booking_id" value="${bookingId || ''}">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Customer *</label>
                        <select id="customer_id" required>
                            <option value="">Select Customer</option>
                            ${customers.success ? customers.customers.map(c => `
                                <option value="${c.id}" ${booking && booking.customer_id == c.id ? 'selected' : ''}>
                                    ${escapeHtml(c.name)} - ${escapeHtml(c.phone)}
                                </option>
                            `).join('') : ''}
                        </select>
                        <button type="button" class="btn-link" onclick="openCustomerModal()">
                            <i class="fas fa-plus"></i> Add New Customer
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label>Product *</label>
                        <select id="product_id" required onchange="checkProductAvailability()">
                            <option value="">Select Product</option>
                            ${products.success ? products.products.map(p => `
                                <option value="${p.id}" 
                                    data-price="${p.price}" 
                                    data-stock="${p.stock_quantity}"
                                    ${booking && booking.product_id == p.id ? 'selected' : ''}>
                                    ${escapeHtml(p.name)} - Stock: ${p.stock_quantity} (${p.stock_status})
                                </option>
                            `).join('') : ''}
                        </select>
                        <div id="availabilityMessage" class="availability-message"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Quantity *</label>
                        <input type="number" id="quantity" min="1" required 
                               value="${booking ? booking.quantity : 1}" 
                               onchange="calculateTotal()">
                    </div>
                    
                    <div class="form-group">
                        <label>Unit Price *</label>
                        <input type="number" id="unit_price" step="0.01" required 
                               value="${booking ? booking.unit_price : ''}" 
                               onchange="calculateTotal()">
                    </div>
                    
                    <div class="form-group">
                        <label>Total Amount</label>
                        <input type="number" id="total_amount" step="0.01" readonly 
                               value="${booking ? booking.total_amount : ''}">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select id="status">
                            <option value="Pending" ${booking && booking.status == 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="Confirmed" ${booking && booking.status == 'Confirmed' ? 'selected' : ''}>Confirmed</option>
                            <option value="Processing" ${booking && booking.status == 'Processing' ? 'selected' : ''}>Processing</option>
                            <option value="Ready" ${booking && booking.status == 'Ready' ? 'selected' : ''}>Ready</option>
                            <option value="Delivered" ${booking && booking.status == 'Delivered' ? 'selected' : ''}>Delivered</option>
                            <option value="Cancelled" ${booking && booking.status == 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Priority</label>
                        <select id="priority">
                            <option value="Low" ${booking && booking.priority == 'Low' ? 'selected' : ''}>Low</option>
                            <option value="Normal" ${booking && booking.priority == 'Normal' ? 'selected' : ''}>Normal</option>
                            <option value="High" ${booking && booking.priority == 'High' ? 'selected' : ''}>High</option>
                            <option value="Urgent" ${booking && booking.priority == 'Urgent' ? 'selected' : ''}>Urgent</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Booking Date</label>
                        <input type="date" id="booking_date" value="${booking ? booking.booking_date : new Date().toISOString().split('T')[0]}">
                    </div>
                    
                    <div class="form-group">
                        <label>Delivery Date</label>
                        <input type="date" id="delivery_date" value="${booking ? booking.delivery_date || '' : ''}">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Customer Notes</label>
                    <textarea id="notes" rows="3">${booking ? booking.notes || '' : ''}</textarea>
                </div>
                
                <div class="form-group">
                    <label>Internal Notes (Not visible to customer)</label>
                    <textarea id="internal_notes" rows="2">${booking ? booking.internal_notes || '' : ''}</textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> ${bookingId ? 'Update' : 'Create'} Booking
                    </button>
                </div>
            </form>
        `,
        onOpen: () => {
            document.getElementById('bookingForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                await saveBooking();
            });
            
            // If editing, calculate total
            if (booking) {
                calculateTotal();
            }
        }
    });
    
    showModal(modal);
}

function checkProductAvailability() {
    const productSelect = document.getElementById('product_id');
    const selected = productSelect.options[productSelect.selectedIndex];
    const messageDiv = document.getElementById('availabilityMessage');
    const priceInput = document.getElementById('unit_price');
    
    if (!selected.value) {
        messageDiv.innerHTML = '';
        priceInput.value = '';
        return;
    }
    
    const stock = parseInt(selected.dataset.stock);
    const price = parseFloat(selected.dataset.price);
    
    priceInput.value = price;
    
    if (stock === 0) {
        messageDiv.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-times-circle"></i> Out of Stock</span>';
    } else if (stock <= 10) {
        messageDiv.innerHTML = `<span style="color: #f59e0b;"><i class="fas fa-exclamation-triangle"></i> Low Stock (${stock} available)</span>`;
    } else {
        messageDiv.innerHTML = `<span style="color: #10b981;"><i class="fas fa-check-circle"></i> In Stock (${stock} available)</span>`;
    }
    
    calculateTotal();
}

function calculateTotal() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
    const total = quantity * unitPrice;
    document.getElementById('total_amount').value = total.toFixed(2);
}

async function saveBooking() {
    const form = document.getElementById('bookingForm');
    const formData = {
        id: document.getElementById('booking_id').value || undefined,
        customer_id: parseInt(document.getElementById('customer_id').value),
        product_id: parseInt(document.getElementById('product_id').value),
        quantity: parseInt(document.getElementById('quantity').value),
        unit_price: parseFloat(document.getElementById('unit_price').value),
        total_amount: parseFloat(document.getElementById('total_amount').value),
        status: document.getElementById('status').value,
        priority: document.getElementById('priority').value,
        booking_date: document.getElementById('booking_date').value,
        delivery_date: document.getElementById('delivery_date').value || null,
        notes: document.getElementById('notes').value,
        internal_notes: document.getElementById('internal_notes').value
    };
    
    try {
        const method = formData.id ? 'PUT' : 'POST';
        const response = await fetch(`${API_BASE}/bookings.php`, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message || 'Booking saved successfully', 'success');
            closeModal();
            loadBookings(currentPage.bookings);
        } else {
            throw new Error(data.error || 'Failed to save booking');
        }
    } catch (error) {
        console.error('Error saving booking:', error);
        showToast(error.message, 'error');
    }
}

async function viewBooking(id) {
    try {
        const response = await fetch(`${API_BASE}/bookings.php?id=${id}`);
        const data = await response.json();
        
        if (!data.success) throw new Error(data.error);
        
        const booking = data.booking;
        
        const modal = createModal({
            title: `Booking Details - ${booking.booking_number}`,
            size: 'large',
            content: `
                <div class="booking-details">
                    <div class="detail-section">
                        <h4>Customer Information</h4>
                        <p><strong>Name:</strong> ${escapeHtml(booking.customer_name)}</p>
                        <p><strong>Phone:</strong> ${escapeHtml(booking.customer_phone)}</p>
                        <p><strong>Email:</strong> ${escapeHtml(booking.customer_email || 'N/A')}</p>
                        ${booking.customer_address ? `<p><strong>Address:</strong> ${escapeHtml(booking.customer_address)}</p>` : ''}
                    </div>
                    
                    <div class="detail-section">
                        <h4>Product Information</h4>
                        <p><strong>Product:</strong> ${escapeHtml(booking.product_name)}</p>
                        <p><strong>SKU:</strong> ${escapeHtml(booking.sku)}</p>
                        <p><strong>Quantity:</strong> ${booking.quantity}</p>
                        <p><strong>Unit Price:</strong> ৳${parseFloat(booking.unit_price).toFixed(2)}</p>
                        <p><strong>Total Amount:</strong> ৳${parseFloat(booking.total_amount).toFixed(2)}</p>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Booking Information</h4>
                        <p><strong>Status:</strong> <span class="badge badge-${getStatusColor(booking.status)}">${booking.status}</span></p>
                        <p><strong>Priority:</strong> ${booking.priority}</p>
                        <p><strong>Booking Date:</strong> ${formatDate(booking.booking_date)}</p>
                        ${booking.delivery_date ? `<p><strong>Delivery Date:</strong> ${formatDate(booking.delivery_date)}</p>` : ''}
                        <p><strong>Created By:</strong> ${escapeHtml(booking.created_by_name)}</p>
                        ${booking.notes ? `<p><strong>Notes:</strong> ${escapeHtml(booking.notes)}</p>` : ''}
                    </div>
                    
                    ${booking.history && booking.history.length > 0 ? `
                    <div class="detail-section">
                        <h4>History</h4>
                        <div class="history-timeline">
                            ${booking.history.map(h => `
                                <div class="history-item">
                                    <div class="history-date">${formatDateTime(h.created_at)}</div>
                                    <div class="history-content">
                                        <strong>${h.previous_status ? `${h.previous_status} → ` : ''}${h.new_status}</strong>
                                        ${h.notes ? `<p>${escapeHtml(h.notes)}</p>` : ''}
                                        <small>by ${escapeHtml(h.changed_by_name)}</small>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                </div>
                
                <div class="form-actions">
                    <button class="btn btn-secondary" onclick="closeModal()">Close</button>
                    <button class="btn btn-primary" onclick="closeModal(); editBooking(${id});">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-success" onclick="viewCustomerHistory(${booking.customer_id})">
                        <i class="fas fa-history"></i> Customer History
                    </button>
                </div>
            `
        });
        
        showModal(modal);
    } catch (error) {
        console.error('Error loading booking details:', error);
        showToast(error.message, 'error');
    }
}

function editBooking(id) {
    openBookingModal(id);
}

async function loadBookingDetails(id) {
    try {
        const response = await fetch(`${API_BASE}/bookings.php?id=${id}`);
        const data = await response.json();
        return data.success ? data.booking : null;
    } catch (error) {
        console.error('Error loading booking details:', error);
        return null;
    }
}

// ========================================
// CUSTOMERS MANAGEMENT
// ========================================
async function loadCustomers(page = 1) {
    const tbody = document.getElementById('customersTableBody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i><p style="margin-top: 10px;">Loading...</p></td></tr>';
    
    try {
        const status = currentFilters.customers.status || '';
        const search = currentFilters.customers.search || '';
        
        const url = `${API_BASE}/customers.php?page=${page}&limit=10${status ? `&status=${status}` : ''}${search ? `&search=${encodeURIComponent(search)}` : ''}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            displayCustomers(data.customers);
            displayPagination('customers', data.pagination);
            currentPage.customers = page;
        } else {
            throw new Error(data.error || 'Failed to load customers');
        }
    } catch (error) {
        console.error('Error loading customers:', error);
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: #ef4444;">
            <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i>
            <p style="margin-top: 10px;">Error loading customers: ${error.message}</p>
        </td></tr>`;
    }
}

function displayCustomers(customers) {
    const tbody = document.getElementById('customersTableBody');
    
    if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">No customers found</td></tr>';
        return;
    }
    
    tbody.innerHTML = customers.map(customer => `
        <tr>
            <td>
                <div class="user-cell">
                    <div class="user-cell-avatar">${getInitials(customer.name)}</div>
                    <div class="user-cell-info">
                        <div class="user-cell-name">${escapeHtml(customer.name)}</div>
                        ${customer.company ? `<div class="user-cell-email">${escapeHtml(customer.company)}</div>` : ''}
                    </div>
                </div>
            </td>
            <td>${escapeHtml(customer.phone)}</td>
            <td>${escapeHtml(customer.email || 'N/A')}</td>
            <td>${customer.booking_count || 0}</td>
            <td><span class="badge badge-${customer.status === 'Active' ? 'green' : 'gray'}">${customer.status}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn" onclick="viewCustomerHistory(${customer.id})" title="View History">
                        <i class="fas fa-history"></i>
                    </button>
                    <button class="action-btn" onclick="editCustomer(${customer.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn" onclick="deleteCustomer(${customer.id}, '${escapeHtml(customer.name)}')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function filterCustomers() {
    const status = document.getElementById('customerStatusFilter').value;
    currentFilters.customers.status = status;
    loadCustomers(1);
}

function searchCustomers() {
    const search = document.getElementById('customerSearch').value;
    currentFilters.customers.search = search;
    clearTimeout(searchCustomers.timeout);
    searchCustomers.timeout = setTimeout(() => loadCustomers(1), 500);
}

async function openCustomerModal(customerId = null) {
    const customer = customerId ? await loadCustomerDetails(customerId) : null;
    
    const modal = createModal({
        title: customerId ? 'Edit Customer' : 'New Customer',
        size: 'large',
        content: `
            <form id="customerForm">
                <input type="hidden" id="customer_id" value="${customerId || ''}">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" id="customer_name" required value="${customer ? escapeHtml(customer.name) : ''}">
                    </div>
                    
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="tel" id="customer_phone" required value="${customer ? escapeHtml(customer.phone) : ''}">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="customer_email" value="${customer ? escapeHtml(customer.email || '') : ''}">
                    </div>
                    
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" id="customer_company" value="${customer ? escapeHtml(customer.company || '') : ''}">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea id="customer_address" rows="2">${customer ? escapeHtml(customer.address || '') : ''}</textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" id="customer_city" value="${customer ? escapeHtml(customer.city || '') : ''}">
                    </div>
                    
                    <div class="form-group">
                        <label>State/Division</label>
                        <input type="text" id="customer_state" value="${customer ? escapeHtml(customer.state || '') : ''}">
                    </div>
                    
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" id="customer_postal" value="${customer ? escapeHtml(customer.postal_code || '') : ''}">
                    </div>
                </div>
                
                ${customerId ? `
                <div class="form-group">
                    <label>Status</label>
                    <select id="customer_status">
                        <option value="Active" ${customer.status === 'Active' ? 'selected' : ''}>Active</option>
                        <option value="Inactive" ${customer.status === 'Inactive' ? 'selected' : ''}>Inactive</option>
                    </select>
                </div>
                ` : ''}
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="customer_notes" rows="3">${customer ? escapeHtml(customer.notes || '') : ''}</textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> ${customerId ? 'Update' : 'Create'} Customer
                    </button>
                </div>
            </form>
        `,
        onOpen: () => {
            document.getElementById('customerForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                await saveCustomer();
            });
        }
    });
    
    showModal(modal);
}

async function saveCustomer() {
    const formData = {
        id: document.getElementById('customer_id').value || undefined,
        name: document.getElementById('customer_name').value,
        phone: document.getElementById('customer_phone').value,
        email: document.getElementById('customer_email').value,
        company: document.getElementById('customer_company').value,
        address: document.getElementById('customer_address').value,
        city: document.getElementById('customer_city').value,
        state: document.getElementById('customer_state').value,
        postal_code: document.getElementById('customer_postal').value,
        notes: document.getElementById('customer_notes').value
    };
    
    if (formData.id) {
        formData.status = document.getElementById('customer_status').value;
    }
    
    try {
        const method = formData.id ? 'PUT' : 'POST';
        const response = await fetch(`${API_BASE}/customers.php`, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message || 'Customer saved successfully', 'success');
            closeModal();
            loadCustomers(currentPage.customers);
        } else {
            throw new Error(data.error || 'Failed to save customer');
        }
    } catch (error) {
        console.error('Error saving customer:', error);
        showToast(error.message, 'error');
    }
}

function editCustomer(id) {
    openCustomerModal(id);
}

async function loadCustomerDetails(id) {
    try {
        const response = await fetch(`${API_BASE}/customers.php?id=${id}`);
        const data = await response.json();
        return data.success ? data.customer : null;
    } catch (error) {
        console.error('Error loading customer details:', error);
        return null;
    }
}

async function viewCustomerHistory(customerId) {
    try {
        const response = await fetch(`${API_BASE}/customers.php?history=1&customer_id=${customerId}`);
        const data = await response.json();
        
        if (!data.success) throw new Error(data.error);
        
        const stats = data.stats;
        const bookings = data.bookings;
        const feedback = data.feedback;
        
        const modal = createModal({
            title: 'Customer History',
            size: 'large',
            content: `
                <div class="customer-history">
                    <div class="stats-row">
                        <div class="stat-box">
                            <div class="stat-label">Total Bookings</div>
                            <div class="stat-number">${stats.total_bookings || 0}</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Completed</div>
                            <div class="stat-number">${stats.completed_bookings || 0}</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Active</div>
                            <div class="stat-number">${stats.active_bookings || 0}</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Total Spent</div>
                            <div class="stat-number">৳${parseFloat(stats.total_spent || 0).toFixed(2)}</div>
                        </div>
                    </div>
                    
                    <h4 style="margin-top: 30px;">Booking History</h4>
                    ${bookings && bookings.length > 0 ? `
                        <div class="booking-history-list">
                            ${bookings.map(b => `
                                <div class="history-card">
                                    <div class="history-header">
                                        <strong>${b.booking_number}</strong>
                                        <span class="badge badge-${getStatusColor(b.status)}">${b.status}</span>
                                    </div>
                                    <div class="history-body">
                                        <p><strong>Product:</strong> ${escapeHtml(b.product_name)}</p>
                                        <p><strong>Quantity:</strong> ${b.quantity} | <strong>Amount:</strong> ৳${parseFloat(b.total_amount).toFixed(2)}</p>
                                        <p><strong>Date:</strong> ${formatDate(b.booking_date)}</p>
                                    </div>
                                    <div class="history-actions">
                                        <button class="btn-link" onclick="closeModal(); viewBooking(${b.id});">View Details</button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : '<p style="color: #64748b; text-align: center; padding: 20px;">No bookings found</p>'}
                    
                    ${feedback && feedback.length > 0 ? `
                        <h4 style="margin-top: 30px;">Customer Feedback</h4>
                        <div class="feedback-list">
                            ${feedback.map(f => `
                                <div class="feedback-card">
                                    ${f.rating ? `<div class="rating">${'⭐'.repeat(f.rating)}</div>` : ''}
                                    <p>${escapeHtml(f.feedback)}</p>
                                    <small>${formatDate(f.created_at)} - ${f.feedback_type}</small>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
                
                <div class="form-actions">
                    <button class="btn btn-secondary" onclick="closeModal()">Close</button>
                    <button class="btn btn-primary" onclick="exportCustomerHistory(${customerId})">
                        <i class="fas fa-download"></i> Export History
                    </button>
                </div>
            `
        });
        
        showModal(modal);
    } catch (error) {
        console.error('Error loading customer history:', error);
        showToast(error.message, 'error');
    }
}

async function deleteCustomer(id, name) {
    if (!confirm(`Are you sure you want to delete customer "${name}"? This action cannot be undone.`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/customers.php`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message || 'Customer deleted successfully', 'success');
            loadCustomers(currentPage.customers);
        } else {
            throw new Error(data.error || 'Failed to delete customer');
        }
    } catch (error) {
        console.error('Error deleting customer:', error);
        showToast(error.message, 'error');
    }
}

// ========================================
// PRODUCTS  MANAGEMENT
// ========================================
async function loadProducts(page = 1) {
    const tbody = document.getElementById('productsTableBody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i><p style="margin-top: 10px;">Loading...</p></td></tr>';
    
    try {
        const status = currentFilters.products.status || '';
        const search = currentFilters.products.search || '';
        
        const url = `${API_BASE}/products.php?page=${page}&limit=10${status ? `&status=${status}` : ''}${search ? `&search=${encodeURIComponent(search)}` : ''}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            displayProducts(data.products);
            displayPagination('products', data.pagination);
            currentPage.products = page;
        } else {
            throw new Error(data.error || 'Failed to load products');
        }
    } catch (error) {
        console.error('Error loading products:', error);
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 40px; color: #ef4444;">
            <i class="fas fa-exclamation-circle" style="font-size: 24px;"></i>
            <p style="margin-top: 10px;">Error loading products: ${error.message}</p>
        </td></tr>`;
    }
}

function displayProducts(products) {
    const tbody = document.getElementById('productsTableBody');
    
    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">No products found</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map(product => `
        <tr>
            <td>
                <strong>${escapeHtml(product.name)}</strong>
                ${product.description ? `<br><small style="color: #64748b;">${escapeHtml(product.description.substring(0, 50))}...</small>` : ''}
            </td>
            <td>${escapeHtml(product.sku || 'N/A')}</td>
            <td>${escapeHtml(product.category || 'N/A')}</td>
            <td>৳${parseFloat(product.price).toFixed(2)}</td>
            <td>${product.stock_quantity} ${product.unit || 'pcs'}</td>
            <td>
                <span class="badge badge-${getStockStatusColor(product.stock_status)}">${product.stock_status}</span>
            </td>
        </tr>
    `).join('');
}

function filterProducts() {
    const status = document.getElementById('productStatusFilter').value;
    currentFilters.products.status = status;
    loadProducts(1);
}

function searchProducts() {
    const search = document.getElementById('productSearch').value;
    currentFilters.products.search = search;
    clearTimeout(searchProducts.timeout);
    searchProducts.timeout = setTimeout(() => loadProducts(1), 500);
}

// ========================================
// REMINDERS MANAGEMENT
// ========================================
async function loadReminders() {
    const remindersList = document.getElementById('remindersList');
    remindersList.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i><p style="margin-top: 10px;">Loading...</p></div>';
    
    try {
        const response = await fetch(`${API_BASE}/reminders.php?pending=1`);
        const data = await response.json();
        
        if (data.success) {
            displayReminders(data.reminders);
        } else {
            throw new Error(data.error || 'Failed to load reminders');
        }
    } catch (error) {
        console.error('Error loading reminders:', error);
        remindersList.innerHTML = `<div style="text-align: center; padding: 40px; color: #ef4444;">
            <i class="fas fa-exclamation-circle"></i><p style="margin-top: 10px;">Error: ${error.message}</p>
        </div>`;
    }
}

function displayReminders(reminders) {
    const remindersList = document.getElementById('remindersList');
    
    if (reminders.length === 0) {
        remindersList.innerHTML = '<div style="text-align: center; padding: 40px; color: #64748b;"><i class="fas fa-check-circle" style="font-size: 48px;"></i><p style="margin-top: 10px;">No pending reminders</p></div>';
        return;
    }
    
    remindersList.innerHTML = reminders.map(reminder => `
        <div class="reminder-card ${reminder.urgency === 'Overdue' ? 'reminder-overdue' : ''}">
            <div class="reminder-header">
                <div>
                    <strong>${reminder.booking_number}</strong>
                    <span class="badge badge-${getReminderTypeColor(reminder.reminder_type)}">${reminder.reminder_type}</span>
                    ${reminder.urgency === 'Overdue' ? '<span class="badge badge-red">Overdue</span>' : ''}
                </div>
                <div class="reminder-actions">
                    <button class="btn-link" onclick="markReminderSent(${reminder.id})">
                        <i class="fas fa-check"></i> Mark Sent
                    </button>
                </div>
            </div>
            <div class="reminder-body">
                <p><strong>Customer:</strong> ${escapeHtml(reminder.customer_name)} - ${escapeHtml(reminder.customer_phone)}</p>
                <p><strong>Product:</strong> ${escapeHtml(reminder.product_name)}</p>
                <p><strong>Due:</strong> ${formatDateTime(reminder.reminder_date)}</p>
                <p><strong>Message:</strong> ${escapeHtml(reminder.message)}</p>
            </div>
        </div>
    `).join('');
}

async function markReminderSent(id) {
    try {
        const response = await fetch(`${API_BASE}/reminders.php`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status: 'Sent' })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Reminder marked as sent', 'success');
            loadReminders();
        } else {
            throw new Error(data.error);
        }
    } catch (error) {
        console.error('Error marking reminder:', error);
        showToast(error.message, 'error');
    }
}

function showReminders() {
    showPage('reports');
}

// ========================================
// EXPORT FUNCTIONS
// ========================================
function exportBookings() {
    const status = currentFilters.bookings.status || '';
    window.open(`${API_BASE}/export.php?type=bookings&format=csv${status ? `&status=${status}` : ''}`, '_blank');
}

function exportCustomers() {
    const status = currentFilters.customers.status || '';
    window.open(`${API_BASE}/export.php?type=customers&format=csv${status ? `&status=${status}` : ''}`, '_blank');
}

function exportCustomerHistory(customerId) {
    window.open(`${API_BASE}/export.php?type=customer_history&format=csv&customer_id=${customerId}`, '_blank');
}

// ========================================
// PAGINATION
// ========================================
function displayPagination(type, pagination) {
    const container = document.getElementById(`${type}Pagination`);
    
    if (!pagination || pagination.pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div class="pagination-buttons">';
    
    if (pagination.page > 1) {
        html += `<button onclick="load${capitalize(type)}(${pagination.page - 1})" class="pagination-btn">Previous</button>`;
    }
    
    for (let i = 1; i <= pagination.pages; i++) {
        if (i === 1 || i === pagination.pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
            html += `<button onclick="load${capitalize(type)}(${i})" class="pagination-btn ${i === pagination.page ? 'active' : ''}">${i}</button>`;
        } else if (i === pagination.page - 3 || i === pagination.page + 3) {
            html += '<span class="pagination-dots">...</span>';
        }
    }
    
    if (pagination.page < pagination.pages) {
        html += `<button onclick="load${capitalize(type)}(${pagination.page + 1})" class="pagination-btn">Next</button>`;
    }
    
    html += '</div>';
    html += `<div class="pagination-info">Showing page ${pagination.page} of ${pagination.pages} (Total: ${pagination.total} items)</div>`;
    
    container.innerHTML = html;
}

// ========================================
// MODAL SYSTEM
// ========================================
function createModal({ title, content, size = 'medium', onOpen = null }) {
    return { title, content, size, onOpen };
}

function showModal(modal) {
    const container = document.getElementById('modalContainer');
    
    container.innerHTML = `
        <div class="modal-overlay" onclick="closeModal()">
            <div class="modal modal-${modal.size}" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h3>${modal.title}</h3>
                    <button class="modal-close" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    ${modal.content}
                </div>
            </div>
        </div>
    `;
    
    container.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    if (modal.onOpen) {
        setTimeout(modal.onOpen, 100);
    }
}

function closeModal() {
    const container = document.getElementById('modalContainer');
    container.innerHTML = '';
    container.style.display = 'none';
    document.body.style.overflow = '';
}

// ========================================
// TOAST NOTIFICATIONS
// ========================================
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ========================================
// UTILITY FUNCTIONS
// ========================================
function getInitials(name) {
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
}

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

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

function getStatusColor(status) {
    const colors = {
        'Pending': 'orange',
        'Confirmed': 'blue',
        'Processing': 'purple',
        'Ready': 'cyan',
        'Delivered': 'green',
        'Cancelled': 'red',
        'Rejected': 'red'
    };
    return colors[status] || 'gray';
}

function getStockStatusColor(status) {
    const colors = {
        'In Stock': 'green',
        'Low Stock': 'orange',
        'Out of Stock': 'red'
    };
    return colors[status] || 'gray';
}

function getReminderTypeColor(type) {
    const colors = {
        'Confirmation': 'blue',
        'Follow-up': 'orange',
        'Delivery': 'green',
        'Payment': 'purple'
    };
    return colors[type] || 'gray';
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

async function performGlobalSearch(query) {
    // Implement global search across bookings and customers
    console.log('Global search:', query);
}

async function updateBookingStatus(id, currentStatus) {
    const statuses = ['Pending', 'Confirmed', 'Processing', 'Ready', 'Delivered'];
    const currentIndex = statuses.indexOf(currentStatus);
    const nextStatus = statuses[Math.min(currentIndex + 1, statuses.length - 1)];
    
    if (currentStatus === 'Delivered') {
        showToast('Booking already delivered', 'info');
        return;
    }
    
    if (confirm(`Update booking status to "${nextStatus}"?`)) {
        try {
            const response = await fetch(`${API_BASE}/bookings.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    id, 
                    status: nextStatus,
                    history_note: `Status updated to ${nextStatus}`
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(`Status updated to ${nextStatus}`, 'success');
                loadBookings(currentPage.bookings);
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error updating status:', error);
            showToast(error.message, 'error');
        }
    }
}
