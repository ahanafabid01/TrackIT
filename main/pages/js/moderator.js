/**
 * Moderator Dashboard JavaScript
 * Handles all interactions, modals, and API calls for the Moderator page
 */

// Global variables
let currentPage = 1;
let currentFilter = {};
let allBookings = [];
let allCustomers = [];
let allProducts = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize navigation
    initNavigation();
    
    // Load initial data
    loadBookings();
    loadCustomers();
    loadProducts();
    loadReminders();
    
    // Setup global search
    setupGlobalSearch();
});

// ==================== NAVIGATION ====================
function initNavigation() {
    const navItems = document.querySelectorAll('.nav-item[data-page]');
    
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const page = item.getAttribute('data-page');
            showPage(page);
            
            // Update active state
            navItems.forEach(nav => nav.classList.remove('active'));
            item.classList.add('active');
        });
    });
}

function showPage(page) {
    // Hide all pages
    const pages = document.querySelectorAll('.page-content');
    pages.forEach(p => p.style.display = 'none');
    
    // Show selected page
    const selectedPage = document.getElementById(page + 'Page');
    if (selectedPage) {
        selectedPage.style.display = 'block';
    }
}

// ==================== BOOKINGS ====================
async function loadBookings(page = 1, status = '', limit = 10) {
    try {
        // Show loading state
        const tbody = document.getElementById('bookingsTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #3b82f6;"></i>
                    <p style="margin-top: 10px; color: #64748b;">Loading bookings...</p>
                </td>
            </tr>
        `;
        
        const params = new URLSearchParams({
            page: page,
            limit: limit
        });
        
        if (status) params.append('status', status);
        
        const response = await fetch(`../../api/moderator/bookings.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            allBookings = data.bookings;
            renderBookingsTable(data.bookings);
            renderPagination('bookings', data.pagination);
        } else {
            showError('Failed to load bookings');
        }
    } catch (error) {
        console.error('Error loading bookings:', error);
        showError('Error loading bookings');
    }
}

function renderBookingsTable(bookings) {
    const tbody = document.getElementById('bookingsTableBody');
    
    if (!bookings || bookings.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #64748b;">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p>No bookings found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = bookings.map(booking => `
        <tr>
            <td><strong>${booking.booking_number}</strong></td>
            <td>
                <div class="user-cell">
                    <div class="user-cell-avatar">${booking.customer_name ? booking.customer_name.substring(0, 2).toUpperCase() : 'NA'}</div>
                    <div>
                        <div class="user-cell-name">${booking.customer_name || 'Unknown'}</div>
                        <div class="user-cell-email">${booking.customer_phone || ''}</div>
                    </div>
                </div>
            </td>
            <td>${booking.product_name || 'N/A'}</td>
            <td>${booking.quantity}</td>
            <td>₹${parseFloat(booking.total_amount).toFixed(2)}</td>
            <td><span class="badge badge-${getStatusColor(booking.status)}">${booking.status}</span></td>
            <td>${formatDate(booking.booking_date)}</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn" onclick="viewBooking(${booking.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn" onclick="editBooking(${booking.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete" onclick="deleteBooking(${booking.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function filterBookings() {
    const status = document.getElementById('bookingStatusFilter').value;
    const perPageSelect = document.getElementById('bookingsPerPage');
    const limit = perPageSelect ? parseInt(perPageSelect.value) : 10;
    loadBookings(1, status, limit);
}

function searchBookings() {
    const searchTerm = document.getElementById('bookingSearch').value.toLowerCase();
    
    if (!searchTerm) {
        renderBookingsTable(allBookings);
        return;
    }
    
    const filtered = allBookings.filter(booking => 
        booking.booking_number.toLowerCase().includes(searchTerm) ||
        booking.customer_name.toLowerCase().includes(searchTerm) ||
        booking.product_name.toLowerCase().includes(searchTerm)
    );
    
    renderBookingsTable(filtered);
}

function openBookingModal(bookingId = null) {
    const modal = createBookingModal(bookingId);
    showModal(modal);
    
    console.log('Booking modal opened');
    
    // Load products and customers data
    loadCustomersForSelect();
    loadProductsForSelect().then(() => {
        console.log('Products loaded for modal:', window.allProductsData?.length || 0);
    });
    
    if (bookingId) {
        loadBookingData(bookingId);
    }
    
    // Add event listener for product search after modal is rendered
    setTimeout(() => {
        const productSearchInput = document.getElementById('productSearch');
        if (productSearchInput) {
            console.log('Product search input found and ready');
            // Test the input
            productSearchInput.addEventListener('focus', () => {
                console.log('Product search input focused');
            });
        } else {
            console.error('Product search input NOT found in modal');
        }
    }, 100);
}

function createBookingModal(bookingId = null) {
    const isEdit = bookingId !== null;
    
    return `
        <div class="modal active" id="bookingModal">
            <div class="modal-content" style="max-width: 700px;">
                <div class="modal-header">
                    <h2 class="modal-title">${isEdit ? 'Edit Booking' : 'New Booking'}</h2>
                    <button class="modal-close" onclick="closeModal('bookingModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="bookingForm">
                        <input type="hidden" id="bookingId" value="${bookingId || ''}">
                        <input type="hidden" id="selectedCustomerId" value="">
                        
                        <!-- Customer Search Section -->
                        <div class="form-section">
                            <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-user"></i> Customer Information
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label">Search Customer by Phone *</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="tel" class="form-control" id="customerPhone" placeholder="Enter phone number" onkeyup="searchCustomerByPhone()" required>
                                    <button type="button" class="btn btn-secondary" onclick="toggleNewCustomerForm()" id="toggleCustomerBtn">
                                        <i class="fas fa-plus"></i> New
                                    </button>
                                </div>
                                <small id="customerSearchStatus" style="display: block; margin-top: 5px; color: #64748b;"></small>
                            </div>
                            
                            <!-- Existing Customer Display -->
                            <div id="existingCustomerInfo" style="display: none; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <div style="font-weight: 600; color: #0c4a6e; margin-bottom: 5px;" id="existingCustomerName"></div>
                                        <div style="font-size: 13px; color: #075985;" id="existingCustomerDetails"></div>
                                    </div>
                                    <button type="button" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" onclick="clearCustomerSelection()">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                            </div>
                            
                            <!-- New Customer Form -->
                            <div id="newCustomerForm" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 15px;">
                                    <h4 style="margin: 0; font-size: 14px; color: #1e293b;">New Customer Details</h4>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="newCustomerName">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" id="newCustomerEmail">
                                    </div>
                                </div>
                                
                                <div class="form-group" style="margin-top: 15px; margin-bottom: 0;">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" id="newCustomerAddress">
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" id="newCustomerCity">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label">State</label>
                                        <input type="text" class="form-control" id="newCustomerState">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">
                        
                        <!-- Product Selection Section -->
                        <div class="form-section">
                            <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-box"></i> Product Details
                            </h3>
                        
                        <div class="form-group">
                            <label class="form-label">Search Product *</label>
                            <div class="product-search-wrapper">
                                <input type="text" 
                                       class="form-control" 
                                       id="productSearch" 
                                       placeholder="Type product name or SKU..." 
                                       onkeyup="searchProductsLive()" 
                                       oninput="searchProductsLive()"
                                       autocomplete="off"
                                       style="padding-right: 40px;">
                                <i class="fas fa-search" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;"></i>
                                <div id="productSearchResults"></div>
                            </div>
                            <input type="hidden" id="productId" required>
                            <small style="color: #64748b; font-size: 12px; display: block; margin-top: 4px;">
                                <i class="fas fa-info-circle"></i> Start typing to search products
                            </small>
                        </div>
                        
                        <div id="productDetails" style="display: none; padding: 12px; background: #dbeafe; border: 1px solid #93c5fd; border-radius: 6px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="color: #64748b;">Available Stock:</span>
                                <strong id="availableStock">0</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #64748b;">Unit Price:</span>
                                <strong id="unitPriceDisplay">₹0.00</strong>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="quantity" min="1" required onchange="calculateTotal()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Unit Price *</label>
                            <input type="number" class="form-control" id="unitPrice" step="0.01" required onchange="calculateTotal()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Total Amount</label>
                            <input type="number" class="form-control" id="totalAmount" step="0.01" readonly style="background: #f1f5f9; font-weight: bold;">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select class="form-control" id="priority">
                                <option value="Normal">Normal</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Booking Date</label>
                            <input type="date" class="form-control" id="bookingDate" value="${new Date().toISOString().split('T')[0]}">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" rows="3" placeholder="Additional notes..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('bookingModal')">Cancel</button>
                    <button class="btn btn-primary" onclick="saveBooking()">${isEdit ? 'Update' : 'Create'} Booking</button>
                </div>
            </div>
        </div>
    `;
}

async function loadCustomersForSelect() {
    try {
        const response = await fetch('../../api/moderator/customers.php?limit=1000&status=Active');
        const data = await response.json();
        
        if (data.success) {
            // Store customers globally for search
            window.allCustomersData = data.customers;
        }
    } catch (error) {
        console.error('Error loading customers:', error);
    }
}

// Search customer by phone number
let customerSearchTimeout;
async function searchCustomerByPhone() {
    const phoneInput = document.getElementById('customerPhone');
    const phone = phoneInput.value.trim();
    const statusEl = document.getElementById('customerSearchStatus');
    const existingInfoEl = document.getElementById('existingCustomerInfo');
    const newFormEl = document.getElementById('newCustomerForm');
    
    // Clear any existing timeout
    clearTimeout(customerSearchTimeout);
    
    if (phone.length < 3) {
        statusEl.textContent = '';
        existingInfoEl.style.display = 'none';
        newFormEl.style.display = 'none';
        return;
    }
    
    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
    
    // Debounce search
    customerSearchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`../../api/moderator/customers.php?search=${encodeURIComponent(phone)}`);
            const data = await response.json();
            
            if (data.success && data.customers.length > 0) {
                // Find exact or partial match
                const exactMatch = data.customers.find(c => c.phone === phone);
                const customer = exactMatch || data.customers[0];
                
                // Auto-fill customer data
                document.getElementById('selectedCustomerId').value = customer.id;
                document.getElementById('existingCustomerName').textContent = customer.name;
                document.getElementById('existingCustomerDetails').innerHTML = `
                    <div><i class="fas fa-phone"></i> ${customer.phone}</div>
                    ${customer.email ? `<div><i class="fas fa-envelope"></i> ${customer.email}</div>` : ''}
                    ${customer.city ? `<div><i class="fas fa-map-marker-alt"></i> ${customer.city}</div>` : ''}
                `;
                
                existingInfoEl.style.display = 'block';
                newFormEl.style.display = 'none';
                statusEl.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Customer found!';
                
                // If exact match, lock the phone input
                if (exactMatch) {
                    phoneInput.style.borderColor = '#10b981';
                }
            } else {
                // No customer found - show new customer form
                document.getElementById('selectedCustomerId').value = '';
                existingInfoEl.style.display = 'none';
                newFormEl.style.display = 'block'; // Show the new customer form
                statusEl.innerHTML = '<i class="fas fa-info-circle" style="color: #f59e0b;"></i> Customer not found. Fill details below to create new.';
                phoneInput.style.borderColor = '#f59e0b';
                
                console.log('📝 New customer form displayed - phone not found in database');
            }
        } catch (error) {
            console.error('Error searching customer:', error);
            statusEl.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #ef4444;"></i> Error searching customer';
        }
    }, 500);
}

function toggleNewCustomerForm() {
    const newFormEl = document.getElementById('newCustomerForm');
    const existingInfoEl = document.getElementById('existingCustomerInfo');
    const toggleBtn = document.getElementById('toggleCustomerBtn');
    
    if (newFormEl.style.display === 'none') {
        newFormEl.style.display = 'block';
        existingInfoEl.style.display = 'none';
        toggleBtn.innerHTML = '<i class="fas fa-minus"></i> Cancel';
        document.getElementById('selectedCustomerId').value = '';
        document.getElementById('customerSearchStatus').innerHTML = '<i class="fas fa-info-circle" style="color: #3b82f6;"></i> Fill in the details to create new customer';
    } else {
        newFormEl.style.display = 'none';
        toggleBtn.innerHTML = '<i class="fas fa-plus"></i> New';
        // Clear new customer form
        document.getElementById('newCustomerName').value = '';
        document.getElementById('newCustomerEmail').value = '';
        document.getElementById('newCustomerAddress').value = '';
        document.getElementById('newCustomerCity').value = '';
        document.getElementById('newCustomerState').value = '';
        document.getElementById('customerSearchStatus').textContent = '';
    }
}

function clearCustomerSelection() {
    document.getElementById('selectedCustomerId').value = '';
    document.getElementById('existingCustomerInfo').style.display = 'none';
    document.getElementById('customerPhone').value = '';
    document.getElementById('customerPhone').style.borderColor = '#e2e8f0';
    document.getElementById('customerSearchStatus').textContent = '';
}

async function loadProductsForSelect() {
    try {
        console.log('Loading products for select...');
        const response = await fetch('../../api/moderator/products.php?limit=1000&status=Active');
        const data = await response.json();
        
        if (data.success) {
            // Store products globally for search
            window.allProductsData = data.products;
            console.log(`Successfully loaded ${data.products.length} products`);
        } else {
            console.error('Failed to load products:', data.message);
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

// Live product search
let productSearchTimeout;
function searchProductsLive() {
    console.log('🔍 searchProductsLive() called');
    
    const searchInput = document.getElementById('productSearch');
    if (!searchInput) {
        console.error('❌ Product search input NOT found with ID: productSearch');
        return;
    }
    
    console.log('✅ Search input element found:', searchInput);
    console.log('📍 Input element details:', {
        id: searchInput.id,
        value: searchInput.value,
        type: searchInput.type,
        placeholder: searchInput.placeholder,
        parentElement: searchInput.parentElement.className,
        isInModal: searchInput.closest('.modal') !== null
    });
    
    const searchTerm = searchInput.value.trim();
    const resultsContainer = document.getElementById('productSearchResults');
    
    if (!resultsContainer) {
        console.error('❌ Product search results container NOT found with ID: productSearchResults');
        return;
    }
    
    console.log(`📝 Search term: "${searchTerm}" (length: ${searchTerm.length})`);
    
    clearTimeout(productSearchTimeout);
    
    // Hide if empty
    if (searchTerm.length < 1) {
        resultsContainer.style.display = 'none';
        resultsContainer.innerHTML = '';
        console.log('✅ Search cleared - dropdown hidden');
        return;
    }
    
    // Show loading indicator immediately
    resultsContainer.innerHTML = '<div class="product-search-item" style="padding: 12px; text-align: center; color: #64748b;"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
    resultsContainer.style.display = 'block';
    console.log('⏳ Loading indicator shown');
    
    productSearchTimeout = setTimeout(async () => {
        console.log('⚡ Search timeout triggered - fetching results...');
        
        // Load products if not already loaded
        if (!window.allProductsData || window.allProductsData.length === 0) {
            console.log('📦 Products not loaded, fetching from API...');
            try {
                const response = await fetch('../../api/moderator/products.php?limit=1000&status=Active');
                const data = await response.json();
                
                if (data.success && data.products) {
                    window.allProductsData = data.products;
                    console.log(`✅ Loaded ${data.products.length} products from API`);
                } else {
                    console.error('❌ API returned error:', data.message);
                    resultsContainer.innerHTML = '<div class="product-search-item" style="padding: 12px; text-align: center; color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Error loading products</div>';
                    resultsContainer.style.display = 'block';
                    return;
                }
            } catch (error) {
                console.error('❌ Network error loading products:', error);
                resultsContainer.innerHTML = '<div class="product-search-item" style="padding: 12px; text-align: center; color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Network error</div>';
                resultsContainer.style.display = 'block';
                return;
            }
        } else {
            console.log(`✅ Using cached products: ${window.allProductsData.length} items`);
        }
        
        // Filter products
        const searchLower = searchTerm.toLowerCase();
        const filtered = window.allProductsData.filter(product => 
            product.name.toLowerCase().includes(searchLower) ||
            (product.sku && product.sku.toLowerCase().includes(searchLower)) ||
            (product.category && product.category.toLowerCase().includes(searchLower))
        );
        
        console.log(`🔎 Found ${filtered.length} matching products`);
        
        if (filtered.length === 0) {
            resultsContainer.innerHTML = '<div class="product-search-item" style="padding: 12px; text-align: center; color: #64748b;"><i class="fas fa-search"></i> No products found</div>';
            resultsContainer.style.display = 'block';
            console.log('📭 No results - showing empty state');
            return;
        }
        
        // Show up to 5 results
        const displayCount = Math.min(filtered.length, 5);
        resultsContainer.innerHTML = filtered.slice(0, 5).map(product => {
            const safeName = escapeHtml(product.name);
            const safeSku = product.sku ? escapeHtml(product.sku) : 'N/A';
            const safeCategory = product.category ? escapeHtml(product.category) : '';
            
            return `
            <div class="product-search-item" onclick="selectProduct(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price}, ${product.stock_quantity})">
                <div class="product-name">${safeName}</div>
                <div class="product-sku">SKU: ${safeSku}${safeCategory ? ' | ' + safeCategory : ''}</div>
                <div class="product-details">
                    <span><i class="fas fa-box"></i> Stock: <strong>${product.stock_quantity}</strong></span>
                    <span><i class="fas fa-rupee-sign"></i> Price: <strong>₹${parseFloat(product.price).toFixed(2)}</strong></span>
                </div>
            </div>
        `}).join('');
        
        // Ensure dropdown is visible
        resultsContainer.style.display = 'block';
        console.log(`✅ Dropdown displayed with ${displayCount} results`);
    }, 200); // Fast response
}

function selectProduct(id, name, price, stock) {
    console.log('Product selected:', { id, name, price, stock });
    
    const productIdInput = document.getElementById('productId');
    const productSearchInput = document.getElementById('productSearch');
    const resultsContainer = document.getElementById('productSearchResults');
    const productDetails = document.getElementById('productDetails');
    
    if (!productIdInput || !productSearchInput) {
        console.error('Product input fields not found');
        return;
    }
    
    productIdInput.value = id;
    productSearchInput.value = name;
    
    // Hide and clear dropdown
    if (resultsContainer) {
        resultsContainer.style.display = 'none';
        resultsContainer.innerHTML = '';
    }
    
    // Show product details section
    if (productDetails) {
        productDetails.style.display = 'block';
    }
    
    // Update product details
    const availableStockEl = document.getElementById('availableStock');
    const unitPriceDisplayEl = document.getElementById('unitPriceDisplay');
    const unitPriceEl = document.getElementById('unitPrice');
    
    if (availableStockEl) availableStockEl.textContent = stock;
    if (unitPriceDisplayEl) unitPriceDisplayEl.textContent = `₹${parseFloat(price).toFixed(2)}`;
    if (unitPriceEl) unitPriceEl.value = price;
    
    // Show success feedback with green border flash
    productSearchInput.style.borderColor = '#10b981';
    productSearchInput.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.2)';
    
    setTimeout(() => {
        productSearchInput.style.borderColor = '#e2e8f0';
        productSearchInput.style.boxShadow = 'none';
    }, 1500);
    
    // Calculate total if quantity is already filled
    calculateTotal();
    
    console.log('Product selected successfully');
}

// Helper function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    const searchInput = document.getElementById('productSearch');
    const resultsContainer = document.getElementById('productSearchResults');
    
    // Only close if both elements exist and click is outside both
    if (searchInput && resultsContainer) {
        const isClickInside = searchInput.contains(e.target) || resultsContainer.contains(e.target);
        if (!isClickInside && resultsContainer.style.display === 'block') {
            resultsContainer.style.display = 'none';
            console.log('Dropdown closed (clicked outside)');
        }
    }
});

function calculateTotal() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitPrice = parseFloat(document.getElementById('unitPrice').value) || 0;
    const total = quantity * unitPrice;
    
    document.getElementById('totalAmount').value = total.toFixed(2);
}

async function saveBooking() {
    const form = document.getElementById('bookingForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const bookingId = document.getElementById('bookingId').value;
    let customerId = document.getElementById('selectedCustomerId').value;
    const productId = document.getElementById('productId').value;
    const customerPhone = document.getElementById('customerPhone').value;
    
    // Validate required fields
    if (!customerPhone) {
        showError('Customer phone number is required');
        return;
    }
    
    if (!productId) {
        showError('Please select a product');
        return;
    }
    
    // If no existing customer selected, create new customer
    if (!customerId) {
        const newCustomerName = document.getElementById('newCustomerName').value;
        const newCustomerEmail = document.getElementById('newCustomerEmail').value;
        const newCustomerAddress = document.getElementById('newCustomerAddress').value;
        const newCustomerCity = document.getElementById('newCustomerCity').value;
        const newCustomerState = document.getElementById('newCustomerState').value;
        
        console.log('📋 New customer form values:', {
            name: newCustomerName,
            phone: customerPhone,
            email: newCustomerEmail,
            address: newCustomerAddress,
            city: newCustomerCity,
            state: newCustomerState
        });
        
        if (!newCustomerName || newCustomerName.trim() === '') {
            showError('Please enter customer name');
            document.getElementById('newCustomerName').focus();
            return;
        }
        
        if (!customerPhone || customerPhone.trim() === '') {
            showError('Please enter customer phone number');
            document.getElementById('customerPhone').focus();
            return;
        }
        
        try {
            // Create new customer
            const customerData = {
                name: newCustomerName.trim(),
                phone: customerPhone.trim(),
                email: newCustomerEmail ? newCustomerEmail.trim() : '',
                address: newCustomerAddress ? newCustomerAddress.trim() : '',
                city: newCustomerCity ? newCustomerCity.trim() : '',
                state: newCustomerState ? newCustomerState.trim() : ''
            };
            
            console.log('🚀 Sending customer data to API:', customerData);
            
            const response = await fetch('../../api/moderator/customers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(customerData)
            });
            
            console.log('Customer API response status:', response.status);
            console.log('Customer API response headers:', response.headers.get('content-type'));
            
            // Get response text first to check if it's JSON
            const responseText = await response.text();
            console.log('Customer API raw response:', responseText.substring(0, 500));
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ Failed to parse JSON response:', parseError);
                console.error('Response was:', responseText);
                showError('Server error: Invalid response format. Check console for details.');
                return;
            }
            
            console.log('Customer API response data:', data);
            
            if (data.success) {
                customerId = data.customer.id;
                console.log('✅ New customer created with ID:', customerId);
                showSuccess('New customer created successfully!');
            } else {
                console.error('❌ Customer creation failed:', data);
                showError(data.message || data.error || 'Failed to create customer');
                return;
            }
        } catch (error) {
            console.error('❌ Error creating customer:', error);
            showError('Network error: ' + error.message);
            return;
        }
    }
    
    // Now create/update booking
    const bookingData = {
        customer_id: customerId,
        product_id: productId,
        quantity: document.getElementById('quantity').value,
        unit_price: document.getElementById('unitPrice').value,
        total_amount: document.getElementById('totalAmount').value,
        status: 'Pending', // Moderator can only create pending bookings
        priority: document.getElementById('priority').value,
        booking_date: document.getElementById('bookingDate').value,
        notes: document.getElementById('notes').value
    };
    
    if (bookingId) {
        bookingData.id = bookingId;
    }
    
    try {
        const method = bookingId ? 'PUT' : 'POST';
        const response = await fetch('../../api/moderator/bookings.php', {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookingData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(bookingId ? 'Booking updated successfully!' : 'Booking created successfully!');
            closeModal('bookingModal');
            loadBookings();
        } else {
            showError(data.error || 'Failed to save booking');
        }
    } catch (error) {
        console.error('Error saving booking:', error);
        showError('Error saving booking');
    }
}

async function viewBooking(id) {
    try {
        const response = await fetch(`../../api/moderator/bookings.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const booking = data.booking;
            const modal = createBookingViewModal(booking);
            showModal(modal);
        }
    } catch (error) {
        console.error('Error loading booking:', error);
        showError('Error loading booking details');
    }
}

function createBookingViewModal(booking) {
    return `
        <div class="modal active" id="viewBookingModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Booking Details - ${booking.booking_number}</h2>
                    <button class="modal-close" onclick="closeModal('viewBookingModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h3 style="margin-bottom: 15px; color: #1e293b;">Customer Information</h3>
                            <p><strong>Name:</strong> ${booking.customer_name}</p>
                            <p><strong>Phone:</strong> ${booking.customer_phone}</p>
                            <p><strong>Email:</strong> ${booking.customer_email || 'N/A'}</p>
                        </div>
                        <div>
                            <h3 style="margin-bottom: 15px; color: #1e293b;">Product Details</h3>
                            <p><strong>Product:</strong> ${booking.product_name}</p>
                            <p><strong>SKU:</strong> ${booking.product_sku || 'N/A'}</p>
                            <p><strong>Quantity:</strong> ${booking.quantity}</p>
                        </div>
                    </div>
                    
                    <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h3 style="margin-bottom: 15px; color: #1e293b;">Booking Information</h3>
                            <p><strong>Status:</strong> <span class="badge badge-${getStatusColor(booking.status)}">${booking.status}</span></p>
                            <p><strong>Priority:</strong> ${booking.priority}</p>
                            <p><strong>Booking Date:</strong> ${formatDate(booking.booking_date)}</p>
                            <p><strong>Delivery Date:</strong> ${booking.delivery_date ? formatDate(booking.delivery_date) : 'Not set'}</p>
                        </div>
                        <div>
                            <h3 style="margin-bottom: 15px; color: #1e293b;">Payment Details</h3>
                            <p><strong>Unit Price:</strong> ₹${parseFloat(booking.unit_price).toFixed(2)}</p>
                            <p><strong>Quantity:</strong> ${booking.quantity}</p>
                            <p><strong>Total Amount:</strong> <span style="font-size: 20px; color: #3b82f6;">₹${parseFloat(booking.total_amount).toFixed(2)}</span></p>
                        </div>
                    </div>
                    
                    ${booking.notes ? `
                        <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">
                        <div>
                            <h3 style="margin-bottom: 10px; color: #1e293b;">Notes</h3>
                            <p style="color: #64748b;">${booking.notes}</p>
                        </div>
                    ` : ''}
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('viewBookingModal')">Close</button>
                    <button class="btn btn-primary" onclick="closeModal('viewBookingModal'); editBooking(${booking.id});">Edit Booking</button>
                </div>
            </div>
        </div>
    `;
}

function editBooking(id) {
    openBookingModal(id);
}

async function loadBookingData(id) {
    try {
        const response = await fetch(`../../api/moderator/bookings.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const booking = data.booking;
            
            // Load customers and products first
            await loadCustomersForSelect();
            await loadProductsForSelect();
            
            // Fill customer data
            document.getElementById('customerPhone').value = booking.customer_phone || '';
            document.getElementById('selectedCustomerId').value = booking.customer_id;
            
            if (booking.customer_id) {
                document.getElementById('existingCustomerName').textContent = booking.customer_name;
                document.getElementById('existingCustomerDetails').innerHTML = `
                    <div><i class="fas fa-phone"></i> ${booking.customer_phone}</div>
                    ${booking.customer_email ? `<div><i class="fas fa-envelope"></i> ${booking.customer_email}</div>` : ''}
                `;
                document.getElementById('existingCustomerInfo').style.display = 'block';
            }
            
            // Fill product data
            document.getElementById('productId').value = booking.product_id;
            document.getElementById('productSearch').value = booking.product_name || '';
            
            // Show product details
            if (booking.product_id) {
                const product = window.allProductsData?.find(p => p.id == booking.product_id);
                if (product) {
                    document.getElementById('productDetails').style.display = 'block';
                    document.getElementById('availableStock').textContent = product.stock_quantity;
                    document.getElementById('unitPriceDisplay').textContent = `₹${parseFloat(product.price).toFixed(2)}`;
                }
            }
            
            // Fill other form fields
            document.getElementById('quantity').value = booking.quantity;
            document.getElementById('unitPrice').value = booking.unit_price;
            document.getElementById('totalAmount').value = booking.total_amount;
            document.getElementById('status').value = booking.status;
            document.getElementById('priority').value = booking.priority;
            document.getElementById('bookingDate').value = booking.booking_date;
            document.getElementById('deliveryDate').value = booking.delivery_date || '';
            document.getElementById('notes').value = booking.notes || '';
        }
    } catch (error) {
        console.error('Error loading booking data:', error);
    }
}

async function deleteBooking(id) {
    if (!confirm('Are you sure you want to delete this booking?')) {
        return;
    }
    
    try {
        const response = await fetch('../../api/moderator/bookings.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Booking deleted successfully!');
            loadBookings();
        } else {
            showError(data.error || 'Failed to delete booking');
        }
    } catch (error) {
        console.error('Error deleting booking:', error);
        showError('Error deleting booking');
    }
}

async function exportBookings() {
    const status = document.getElementById('bookingStatusFilter').value;
    const params = new URLSearchParams({ type: 'bookings', format: 'csv' });
    
    if (status) params.append('status', status);
    
    window.location.href = `../../api/moderator/export.php?${params}`;
}

// ==================== CUSTOMERS ====================
async function loadCustomers(page = 1, status = '', limit = 10) {
    try {
        // Show loading state
        const tbody = document.getElementById('customersTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #3b82f6;"></i>
                    <p style="margin-top: 10px; color: #64748b;">Loading customers...</p>
                </td>
            </tr>
        `;
        
        const params = new URLSearchParams({
            page: page,
            limit: limit
        });
        
        if (status) params.append('status', status);
        
        const response = await fetch(`../../api/moderator/customers.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            allCustomers = data.customers;
            renderCustomersTable(data.customers);
            renderPagination('customers', data.pagination);
        } else {
            showError('Failed to load customers');
        }
    } catch (error) {
        console.error('Error loading customers:', error);
        showError('Error loading customers');
    }
}

function renderCustomersTable(customers) {
    const tbody = document.getElementById('customersTableBody');
    
    if (!customers || customers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                    <i class="fas fa-user-slash" style="font-size: 48px; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p>No customers found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = customers.map(customer => `
        <tr>
            <td>
                <div class="user-cell">
                    <div class="user-cell-avatar">${customer.name.substring(0, 2).toUpperCase()}</div>
                    <div>
                        <div class="user-cell-name">${customer.name}</div>
                        <div class="user-cell-email">${customer.city || ''}</div>
                    </div>
                </div>
            </td>
            <td>${customer.phone}</td>
            <td>${customer.email || 'N/A'}</td>
            <td>${customer.total_orders || 0}</td>
            <td><strong>₹${parseFloat(customer.total_spent || 0).toFixed(2)}</strong></td>
            <td><span class="badge badge-${customer.status === 'Active' ? 'green' : 'red'}">${customer.status}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn" onclick="viewCustomer(${customer.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn" onclick="editCustomer(${customer.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete" onclick="deleteCustomer(${customer.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function filterCustomers() {
    const status = document.getElementById('customerStatusFilter').value;
    const perPageSelect = document.getElementById('customersPerPage');
    const limit = perPageSelect ? parseInt(perPageSelect.value) : 10;
    loadCustomers(1, status, limit);
}

function searchCustomers() {
    const searchTerm = document.getElementById('customerSearch').value.toLowerCase();
    
    if (!searchTerm) {
        renderCustomersTable(allCustomers);
        return;
    }
    
    const filtered = allCustomers.filter(customer => 
        customer.name.toLowerCase().includes(searchTerm) ||
        customer.phone.includes(searchTerm) ||
        (customer.email && customer.email.toLowerCase().includes(searchTerm))
    );
    
    renderCustomersTable(filtered);
}

function openCustomerModal(customerId = null) {
    const modal = createCustomerModal(customerId);
    showModal(modal);
    
    if (customerId) {
        loadCustomerData(customerId);
    }
}

function createCustomerModal(customerId = null) {
    const isEdit = customerId !== null;
    
    return `
        <div class="modal active" id="customerModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">${isEdit ? 'Edit Customer' : 'Add New Customer'}</h2>
                    <button class="modal-close" onclick="closeModal('customerModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="customerForm">
                        <input type="hidden" id="customerId" value="${customerId || ''}">
                        
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="customerName" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="customerPhone" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="customerEmail">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" id="customerAddress" rows="2"></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" id="customerCity">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" id="customerState">
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label">PIN Code</label>
                                <input type="text" class="form-control" id="customerPincode">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select class="form-control" id="customerStatus">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="customerNotes" rows="2" placeholder="Additional notes about the customer..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('customerModal')">Cancel</button>
                    <button class="btn btn-primary" onclick="saveCustomer()">${isEdit ? 'Update' : 'Add'} Customer</button>
                </div>
            </div>
        </div>
    `;
}

async function saveCustomer() {
    const form = document.getElementById('customerForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const customerId = document.getElementById('customerId').value;
    const customerData = {
        name: document.getElementById('customerName').value,
        phone: document.getElementById('customerPhone').value,
        email: document.getElementById('customerEmail').value,
        address: document.getElementById('customerAddress').value,
        city: document.getElementById('customerCity').value,
        state: document.getElementById('customerState').value,
        pincode: document.getElementById('customerPincode').value,
        status: document.getElementById('customerStatus').value,
        notes: document.getElementById('customerNotes').value
    };
    
    if (customerId) {
        customerData.id = customerId;
    }
    
    try {
        const method = customerId ? 'PUT' : 'POST';
        const response = await fetch('../../api/moderator/customers.php', {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(customerData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(customerId ? 'Customer updated successfully!' : 'Customer added successfully!');
            closeModal('customerModal');
            loadCustomers();
        } else {
            showError(data.error || 'Failed to save customer');
        }
    } catch (error) {
        console.error('Error saving customer:', error);
        showError('Error saving customer');
    }
}

async function viewCustomer(id) {
    try {
        const response = await fetch(`../../api/moderator/customers.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const customer = data.customer;
            const modal = createCustomerViewModal(customer);
            showModal(modal);
        }
    } catch (error) {
        console.error('Error loading customer:', error);
        showError('Error loading customer details');
    }
}

function createCustomerViewModal(customer) {
    return `
        <div class="modal active" id="viewCustomerModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Customer Details</h2>
                    <button class="modal-close" onclick="closeModal('viewCustomerModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <h3 style="margin-bottom: 15px; color: #1e293b;">Contact Information</h3>
                            <p><strong>Name:</strong> ${customer.name}</p>
                            <p><strong>Phone:</strong> ${customer.phone}</p>
                            <p><strong>Email:</strong> ${customer.email || 'N/A'}</p>
                            <p><strong>Status:</strong> <span class="badge badge-${customer.status === 'Active' ? 'green' : 'red'}">${customer.status}</span></p>
                        </div>
                        <div>
                            <h3 style="margin-bottom: 15px; color: #1e293b;">Address</h3>
                            <p>${customer.address || 'N/A'}</p>
                            <p>${customer.city || ''} ${customer.state || ''}</p>
                            <p>${customer.pincode || ''}</p>
                        </div>
                    </div>
                    
                    <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">
                    
                    <div>
                        <h3 style="margin-bottom: 15px; color: #1e293b;">Booking Statistics</h3>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                            <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; font-weight: bold; color: #3b82f6;">${customer.total_orders || 0}</div>
                                <div style="color: #64748b; font-size: 14px;">Total Orders</div>
                            </div>
                            <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 20px; font-weight: bold; color: #059669;">₹${parseFloat(customer.total_spent || 0).toFixed(2)}</div>
                                <div style="color: #64748b; font-size: 14px;">Total Spent</div>
                            </div>
                            <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; font-weight: bold; color: #10b981;">${customer.completed_bookings || 0}</div>
                                <div style="color: #64748b; font-size: 14px;">Completed</div>
                            </div>
                            <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 24px; font-weight: bold; color: #f59e0b;">${customer.pending_bookings || 0}</div>
                                <div style="color: #64748b; font-size: 14px;">Pending</div>
                            </div>
                        </div>
                    </div>
                    
                    ${customer.notes ? `
                        <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">
                        <div>
                            <h3 style="margin-bottom: 10px; color: #1e293b;">Notes</h3>
                            <p style="color: #64748b;">${customer.notes}</p>
                        </div>
                    ` : ''}
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('viewCustomerModal')">Close</button>
                    <button class="btn btn-primary" onclick="closeModal('viewCustomerModal'); editCustomer(${customer.id});">Edit Customer</button>
                </div>
            </div>
        </div>
    `;
}

function editCustomer(id) {
    openCustomerModal(id);
}

async function loadCustomerData(id) {
    try {
        const response = await fetch(`../../api/moderator/customers.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const customer = data.customer;
            
            document.getElementById('customerName').value = customer.name;
            document.getElementById('customerPhone').value = customer.phone;
            document.getElementById('customerEmail').value = customer.email || '';
            document.getElementById('customerAddress').value = customer.address || '';
            document.getElementById('customerCity').value = customer.city || '';
            document.getElementById('customerState').value = customer.state || '';
            document.getElementById('customerPincode').value = customer.pincode || '';
            document.getElementById('customerStatus').value = customer.status;
            document.getElementById('customerNotes').value = customer.notes || '';
        }
    } catch (error) {
        console.error('Error loading customer data:', error);
    }
}

async function deleteCustomer(id) {
    if (!confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch('../../api/moderator/customers.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Customer deleted successfully!');
            loadCustomers();
        } else {
            showError(data.error || 'Failed to delete customer');
        }
    } catch (error) {
        console.error('Error deleting customer:', error);
        showError('Error deleting customer');
    }
}

async function exportCustomers() {
    const status = document.getElementById('customerStatusFilter').value;
    const params = new URLSearchParams({ type: 'customers', format: 'csv' });
    
    if (status) params.append('status', status);
    
    window.location.href = `../../api/moderator/export.php?${params}`;
}

// ==================== PRODUCTS ====================
async function loadProducts(page = 1, status = '', limit = 20) {
    try {
        // Show loading state
        const tbody = document.getElementById('productsTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #3b82f6;"></i>
                    <p style="margin-top: 10px; color: #64748b;">Loading products...</p>
                </td>
            </tr>
        `;
        
        const params = new URLSearchParams({
            page: page,
            limit: limit
        });
        
        if (status) params.append('status', status);
        
        const response = await fetch(`../../api/moderator/products.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            allProducts = data.products;
            renderProductsTable(data.products);
            renderPagination('products', data.pagination);
        } else {
            showError('Failed to load products');
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showError('Error loading products');
    }
}

function renderProductsTable(products) {
    const tbody = document.getElementById('productsTableBody');
    
    if (!products || products.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                    <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 10px; opacity: 0.5;"></i>
                    <p>No products found</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = products.map(product => {
        const stockStatus = getStockStatus(product);
        return `
            <tr>
                <td>
                    <div style="font-weight: 600;">${product.name}</div>
                    <div style="font-size: 12px; color: #64748b;">${product.description || ''}</div>
                </td>
                <td>${product.sku || 'N/A'}</td>
                <td>${product.category || 'N/A'}</td>
                <td>₹${parseFloat(product.price).toFixed(2)}</td>
                <td>
                    <div style="font-weight: 600;">${product.stock_quantity}</div>
                    <div style="font-size: 12px; color: #64748b;">Min: ${product.low_stock_threshold || 0}</div>
                </td>
                <td>
                    <span class="badge badge-${stockStatus.color}">${stockStatus.text}</span>
                </td>
            </tr>
        `;
    }).join('');
}

function getStockStatus(product) {
    if (product.stock_quantity === 0) {
        return { text: 'Out of Stock', color: 'red' };
    } else if (product.stock_quantity <= product.low_stock_threshold) {
        return { text: 'Low Stock', color: 'orange' };
    } else {
        return { text: 'In Stock', color: 'green' };
    }
}

function filterProducts() {
    const status = document.getElementById('productStatusFilter').value;
    const perPageSelect = document.getElementById('productsPerPage');
    const limit = perPageSelect ? parseInt(perPageSelect.value) : 20;
    loadProducts(1, status, limit);
}

function searchProducts() {
    const searchTerm = document.getElementById('productSearchFilter').value.toLowerCase();
    
    if (!searchTerm) {
        renderProductsTable(allProducts);
        return;
    }
    
    const filtered = allProducts.filter(product => 
        product.name.toLowerCase().includes(searchTerm) ||
        product.sku.toLowerCase().includes(searchTerm) ||
        (product.category && product.category.toLowerCase().includes(searchTerm))
    );
    
    renderProductsTable(filtered);
}

// ==================== REMINDERS ====================
async function loadReminders() {
    try {
        const response = await fetch('../../api/moderator/reminders.php?pending=true');
        const data = await response.json();
        
        if (data.success) {
            renderRemindersList(data.reminders);
        }
    } catch (error) {
        console.error('Error loading reminders:', error);
    }
}

function renderRemindersList(reminders) {
    const container = document.getElementById('remindersList');
    
    if (!reminders || reminders.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #64748b;">
                <i class="fas fa-bell-slash" style="font-size: 48px; margin-bottom: 10px; opacity: 0.5;"></i>
                <p>No pending reminders</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = reminders.map(reminder => `
        <div class="reminder-card" style="background: white; padding: 15px; margin-bottom: 10px; border-radius: 8px; border-left: 4px solid #f59e0b;">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <div style="font-weight: 600; margin-bottom: 5px;">${reminder.booking_number} - ${reminder.customer_name}</div>
                    <div style="color: #64748b; font-size: 14px; margin-bottom: 5px;">${reminder.message}</div>
                    <div style="font-size: 12px; color: #f59e0b;">
                        <i class="fas fa-clock"></i> ${formatDate(reminder.reminder_date)}
                    </div>
                </div>
                <div style="display: flex; gap: 5px;">
                    <button class="action-btn" onclick="markReminderSent(${reminder.id})" title="Mark as Sent">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="action-btn delete" onclick="deleteReminder(${reminder.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

async function markReminderSent(id) {
    try {
        const response = await fetch('../../api/moderator/reminders.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id, status: 'Sent' })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Reminder marked as sent');
            loadReminders();
        }
    } catch (error) {
        console.error('Error updating reminder:', error);
    }
}

async function deleteReminder(id) {
    if (!confirm('Delete this reminder?')) return;
    
    try {
        const response = await fetch('../../api/moderator/reminders.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Reminder deleted');
            loadReminders();
        }
    } catch (error) {
        console.error('Error deleting reminder:', error);
    }
}

function showReminders() {
    showPage('reports');
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(nav => nav.classList.remove('active'));
    document.querySelector('.nav-item[data-page="reports"]').classList.add('active');
}

// ==================== UTILITY FUNCTIONS ====================
function showModal(modalHTML) {
    const container = document.getElementById('modalContainer');
    container.innerHTML = modalHTML;
    
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = 'auto';
        }, 300);
    }
}

function renderPagination(type, pagination) {
    const container = document.getElementById(`${type}Pagination`);
    
    if (!pagination || pagination.totalPages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    const currentPage = pagination.currentPage;
    const totalPages = pagination.totalPages;
    const total = pagination.total;
    const limit = pagination.limit;
    
    // Calculate showing range
    const startItem = (currentPage - 1) * limit + 1;
    const endItem = Math.min(currentPage * limit, total);
    
    let html = '<div class="pagination-wrapper">';
    
    // Showing info
    html += `<div class="pagination-info">Showing ${startItem} to ${endItem} of ${total} entries</div>`;
    
    html += '<div class="pagination-controls">';
    
    // First button
    if (currentPage > 1) {
        html += `<button class="pagination-btn" onclick="loadPage('${type}', 1)" title="First Page">
            <i class="fas fa-angle-double-left"></i>
        </button>`;
    }
    
    // Previous button
    if (currentPage > 1) {
        html += `<button class="pagination-btn" onclick="loadPage('${type}', ${currentPage - 1})" title="Previous Page">
            <i class="fas fa-angle-left"></i>
        </button>`;
    }
    
    // Page numbers with smart ellipsis
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    // Adjust start if we're near the end
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    // First page if not in range
    if (startPage > 1) {
        html += `<button class="pagination-btn" onclick="loadPage('${type}', 1)">1</button>`;
        if (startPage > 2) {
            html += '<span class="pagination-ellipsis">...</span>';
        }
    }
    
    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            html += `<button class="pagination-btn active">${i}</button>`;
        } else {
            html += `<button class="pagination-btn" onclick="loadPage('${type}', ${i})">${i}</button>`;
        }
    }
    
    // Last page if not in range
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += '<span class="pagination-ellipsis">...</span>';
        }
        html += `<button class="pagination-btn" onclick="loadPage('${type}', ${totalPages})">${totalPages}</button>`;
    }
    
    // Next button
    if (currentPage < totalPages) {
        html += `<button class="pagination-btn" onclick="loadPage('${type}', ${currentPage + 1})" title="Next Page">
            <i class="fas fa-angle-right"></i>
        </button>`;
    }
    
    // Last button
    if (currentPage < totalPages) {
        html += `<button class="pagination-btn" onclick="loadPage('${type}', ${totalPages})" title="Last Page">
            <i class="fas fa-angle-double-right"></i>
        </button>`;
    }
    
    html += '</div>'; // Close pagination-controls
    
    // Items per page selector
    html += `
        <div class="pagination-per-page">
            <label>Items per page:</label>
            <select onchange="changeItemsPerPage('${type}', this.value)" id="${type}PerPage">
                <option value="10" ${limit === 10 ? 'selected' : ''}>10</option>
                <option value="25" ${limit === 25 ? 'selected' : ''}>25</option>
                <option value="50" ${limit === 50 ? 'selected' : ''}>50</option>
                <option value="100" ${limit === 100 ? 'selected' : ''}>100</option>
            </select>
        </div>
    `;
    
    html += '</div>'; // Close pagination-wrapper
    container.innerHTML = html;
}

function loadPage(type, page) {
    const perPageSelect = document.getElementById(`${type}PerPage`);
    const limit = perPageSelect ? parseInt(perPageSelect.value) : 10;
    
    // Scroll to top of the table
    const tableContainer = document.querySelector('.table-container');
    if (tableContainer) {
        tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    switch(type) {
        case 'bookings':
            loadBookings(page, '', limit);
            break;
        case 'customers':
            loadCustomers(page, '', limit);
            break;
        case 'products':
            loadProducts(page, '', limit);
            break;
    }
}

function changeItemsPerPage(type, limit) {
    loadPage(type, 1); // Reset to first page when changing items per page
}

function setupGlobalSearch() {
    const searchInput = document.getElementById('globalSearch');
    
    if (searchInput) {
        searchInput.addEventListener('keyup', (e) => {
            const term = e.target.value.toLowerCase();
            
            // This could be enhanced to search across all data
            console.log('Global search:', term);
        });
    }
}

function getStatusColor(status) {
    const colors = {
        'Pending': 'orange',
        'Confirmed': 'blue',
        'Processing': 'purple',
        'Ready': 'green',
        'Delivered': 'green',
        'Cancelled': 'red',
        'Rejected': 'red'
    };
    return colors[status] || 'blue';
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function showSuccess(message) {
    showNotification(message, 'success');
}

function showError(message) {
    showNotification(message, 'error');
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        max-width: 300px;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    .pagination-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 5px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    
    .pagination-btn {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #64748b;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .pagination-btn:hover {
        background: #f1f5f9;
        border-color: #3b82f6;
    }
    
    .pagination-btn.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    .pagination-ellipsis {
        padding: 8px;
        color: #64748b;
    }
    
    .pagination-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .pagination-info {
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
    }
    
    .pagination-per-page {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #64748b;
        font-size: 14px;
    }
    
    .pagination-per-page label {
        font-weight: 500;
    }
    
    .pagination-per-page select {
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        font-size: 14px;
        color: #1e293b;
    }
    
    .pagination-per-page select:focus {
        outline: none;
        border-color: #3b82f6;
    }
    
    .pagination-controls .fas {
        font-size: 12px;
    }
    
    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    @media (max-width: 768px) {
        .pagination-wrapper {
            flex-direction: column;
            align-items: stretch;
        }
        
        .pagination-info {
            text-align: center;
        }
        
        .pagination-controls {
            justify-content: center;
        }
        
        .pagination-per-page {
            justify-content: center;
        }
    }
    
    .filters-bar {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    
    .filter-group select,
    .filter-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 14px;
    }
    
    .filter-actions {
        display: flex;
        gap: 10px;
    }
    
    .quick-actions {
        margin-top: 30px;
    }
    
    .quick-actions h3 {
        margin-bottom: 15px;
        color: #1e293b;
    }
    
    .action-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .action-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
    }
    
    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .action-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 20px;
        color: white;
    }
    
    .action-icon.blue {
        background: #3b82f6;
    }
    
    .action-icon.green {
        background: #10b981;
    }
    
    .action-icon.purple {
        background: #8b5cf6;
    }
    
    .action-title {
        font-weight: 600;
        margin-bottom: 5px;
        color: #1e293b;
    }
    
    .action-desc {
        font-size: 13px;
        color: #64748b;
    }
    
    .alerts-section {
        margin: 20px 0;
    }
    
    .alert {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    
    .alert-warning {
        background: #fef3c7;
        color: #92400e;
        border-left: 4px solid #f59e0b;
    }
    
    .alert-info {
        background: #dbeafe;
        color: #1e40af;
        border-left: 4px solid #3b82f6;
    }
    
    .alert i {
        font-size: 20px;
    }
    
    .alert span {
        flex: 1;
    }
    
    .alert-btn {
        padding: 8px 16px;
        background: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .alert-btn:hover {
        transform: scale(1.05);
    }
    
    .reminders-section {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .reminders-section h3 {
        margin-bottom: 15px;
        color: #1e293b;
    }
    
    .stat-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 10px;
        font-weight: 600;
    }
    
    .form-section {
        margin-bottom: 20px;
    }
    
    .form-section h3 {
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 8px;
    }
`;
document.head.appendChild(style);
