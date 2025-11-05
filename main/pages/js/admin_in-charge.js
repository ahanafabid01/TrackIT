// Admin In-charge Dashboard JavaScript

// Global variables
const navItems = document.querySelectorAll('.nav-item[data-page]');
let dashboardStats = {
    totalInventory: 0,
    grnThisMonth: 0,
    lowStockItems: 0,
    activeSuppliers: 0
};
let grnItemCount = 0;
let suppliersData = [];
let productsData = [];

// API Helper Functions
async function fetchAPI(endpoint, options = {}) {
    console.log('🌐 API Request:', {
        endpoint,
        method: options.method || 'GET',
        hasBody: !!options.body
    });
    
    if (options.body && typeof options.body === 'object') {
        options.body = JSON.stringify(options.body);
        options.headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };
    }
    
    try {
        const response = await fetch(endpoint, options);
        
        // Get response as text first to check if it's valid JSON
        const responseText = await response.text();
        console.log('📄 Raw Response:', responseText.substring(0, 500));
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ JSON Parse Error:', parseError);
            console.error('Response was:', responseText);
            throw new Error('Server returned invalid response. Check console for details.');
        }
        
        console.log('📡 API Response:', {
            endpoint,
            success: data.success,
            data
        });
        
        if (!data.success) {
            throw new Error(data.error || 'API request failed');
        }
        
        return data;
    } catch (error) {
        console.error('❌ API Error:', error);
        showNotification(error.message || 'An error occurred', 'error');
        throw error;
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
        case 'inventory':
            loadInventory();
            break;
        case 'grn':
            loadGRNs();
            break;
        case 'suppliers':
            loadSuppliers();
            break;
        default:
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

// Load Dashboard Statistics
async function loadDashboardStats() {
    try {
        const [inventoryStats, grnStats, supplierStats, alerts] = await Promise.all([
            fetchAPI('../../api/admin_incharge/inventory.php?stats=true'),
            fetchAPI('../../api/admin_incharge/grn.php?stats=true'),
            fetchAPI('../../api/admin_incharge/suppliers.php?stats=true'),
            fetchAPI('../../api/admin_incharge/inventory.php?alerts=true')
        ]);
        
        // Update dashboard stats
        dashboardStats = {
            totalInventory: inventoryStats.stats.total_products,
            grnThisMonth: grnStats.stats.this_month_grns,
            lowStockItems: inventoryStats.stats.low_stock + inventoryStats.stats.out_of_stock,
            activeSuppliers: supplierStats.stats.active_suppliers
        };
        
        updateDashboardUI();
        
        // Show alerts if any
        if (alerts.alerts && alerts.alerts.length > 0) {
            displayStockAlerts(alerts.alerts);
        }
    } catch (error) {
        console.error('Failed to load dashboard stats:', error);
    }
}

// Update Dashboard UI
function updateDashboardUI() {
    const statCards = document.querySelectorAll('.stat-card');
    if (statCards.length >= 4) {
        statCards[0].querySelector('.stat-value').textContent = dashboardStats.totalInventory.toLocaleString();
        statCards[1].querySelector('.stat-value').textContent = dashboardStats.grnThisMonth;
        statCards[2].querySelector('.stat-value').textContent = dashboardStats.lowStockItems;
        statCards[3].querySelector('.stat-value').textContent = dashboardStats.activeSuppliers;
    }
}

// Load Inventory
async function loadInventory() {
    try {
        const data = await fetchAPI('../../api/admin_incharge/inventory.php');
        renderInventoryTable(data.inventory);
    } catch (error) {
        console.error('Failed to load inventory:', error);
    }
}

// Render Inventory Table
function renderInventoryTable(inventory) {
    const tbody = document.querySelector('#inventoryPage tbody');
    if (!tbody) return;
    
    if (inventory.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No inventory items found</td></tr>';
        return;
    }
    
    tbody.innerHTML = inventory.map(item => `
        <tr>
            <td><strong>PROD-${String(item.id).padStart(6, '0')}</strong></td>
            <td>${escapeHtml(item.name)}</td>
            <td><strong style="color: ${getStockColor(item.stock_quantity, item.low_stock_threshold)}">${item.stock_quantity} ${item.unit}</strong></td>
            <td>${item.total_batches || 'No batches'}</td>
            <td><span class="badge badge-${getStatusBadgeColor(item.stock_status)}">${item.stock_status}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn" title="View Details" onclick="viewInventoryDetails(${item.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn" title="Adjust Stock" onclick="showStockAdjustModal(${item.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn" title="Generate Barcode" onclick="generateBarcode(${item.id})">
                        <i class="fas fa-barcode"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Load GRNs
async function loadGRNs() {
    try {
        const data = await fetchAPI('../../api/admin_incharge/grn.php');
        if (data.success && (data.data || data.grns)) {
            renderGRNTable(data.data || data.grns);
        } else {
            renderGRNTable([]);
        }
    } catch (error) {
        console.error('Failed to load GRNs:', error);
        const tbody = document.getElementById('grnTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #ef4444;">Failed to load GRNs. Please try again.</td></tr>';
        }
    }
}

// Render GRN Table
function renderGRNTable(grns) {
    const tbody = document.getElementById('grnTableBody');
    if (!tbody) return;
    
    if (!grns || grns.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 30px; color: #6b7280;">No GRNs found. Click "Create GRN" to add your first goods receipt.</td></tr>';
        return;
    }
    
    tbody.innerHTML = grns.map(grn => {
        const statusClass = grn.status === 'Approved' ? 'badge-green' :
                           grn.status === 'Verified' ? 'badge-blue' :
                           grn.status === 'Rejected' ? 'badge-red' : 'badge-gray';
        
        const paymentStatusClass = grn.payment_status === 'Paid' ? 'badge-green' :
                                   grn.payment_status === 'Partial' ? 'badge-orange' :
                                   grn.payment_status === 'Pending' ? 'badge-red' : 'badge-gray';
        
        const receivedDate = grn.received_date ? new Date(grn.received_date).toLocaleDateString('en-GB') : 'N/A';
        const totalAmount = parseFloat(grn.total_amount || 0);
        
        return `
            <tr>
                <td><strong style="color: var(--primary-color);">${grn.grn_number || 'N/A'}</strong></td>
                <td>${grn.supplier_name || grn.company_name || 'N/A'}</td>
                <td>${receivedDate}</td>
                <td><span class="badge badge-blue">${grn.total_items || grn.item_count || 0} items</span></td>
                <td><strong>৳${totalAmount.toLocaleString('en-BD', {minimumFractionDigits: 2})}</strong></td>
                <td>
                    <select class="payment-status-dropdown" data-grn-id="${grn.grn_id || grn.id}" onchange="updatePaymentStatus(${grn.grn_id || grn.id}, this.value)" style="padding: 6px 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; background: white;">
                        <option value="Pending" ${grn.payment_status === 'Pending' ? 'selected' : ''} style="color: #dc2626;">Pending</option>
                        <option value="Partial" ${grn.payment_status === 'Partial' ? 'selected' : ''} style="color: #ea580c;">Partial</option>
                        <option value="Paid" ${grn.payment_status === 'Paid' ? 'selected' : ''} style="color: #059669;">Paid</option>
                    </select>
                </td>
                <td><span class="badge ${statusClass}">${grn.status || 'Draft'}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn" onclick="viewGRNDetails(${grn.grn_id || grn.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${grn.status === 'Draft' ? `<button class="action-btn" onclick="verifyGRN(${grn.grn_id || grn.id})" title="Verify" style="color: #10b981;"><i class="fas fa-check"></i></button>` : ''}
                        ${grn.status === 'Verified' ? `<button class="action-btn" onclick="approveGRN(${grn.grn_id || grn.id})" title="Approve" style="color: #059669;"><i class="fas fa-check-double"></i></button>` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

async function viewGRNDetails(grnId) {
    try {
        console.log('Loading GRN details for ID:', grnId);
        const response = await fetchAPI(`../../api/admin_incharge/grn.php?id=${grnId}`);
        
        if (response.success && response.grn) {
            const grn = response.grn;
            console.log('GRN data:', grn);
            
            // Populate header info
            document.getElementById('grnDetailNumber').textContent = grn.grn_number || 'N/A';
            document.getElementById('grnModalSubtitle').textContent = `GRN: ${grn.grn_number || 'N/A'}`;
            
            // Status badge
            const statusBadge = document.getElementById('grnDetailStatus');
            const statusClass = grn.status === 'Approved' ? 'badge-green' :
                               grn.status === 'Verified' ? 'badge-blue' :
                               grn.status === 'Rejected' ? 'badge-red' : 'badge-gray';
            statusBadge.className = `badge ${statusClass}`;
            statusBadge.textContent = grn.status || 'Draft';
            
            // Dates
            document.getElementById('grnDetailReceivedDate').textContent = 
                grn.received_date ? new Date(grn.received_date).toLocaleDateString('en-GB') : 'N/A';
            document.getElementById('grnDetailInvoiceDate').textContent = 
                grn.invoice_date ? new Date(grn.invoice_date).toLocaleDateString('en-GB') : 'N/A';
            document.getElementById('grnDetailInvoiceNumber').textContent = grn.invoice_number || 'N/A';
            document.getElementById('grnDetailPONumber').textContent = grn.purchase_order_number || 'N/A';
            
            // Supplier information
            document.getElementById('grnDetailSupplierName').textContent = grn.supplier_name || grn.company_name || 'N/A';
            document.getElementById('grnDetailSupplierContact').textContent = grn.contact_person || 'N/A';
            document.getElementById('grnDetailSupplierPhone').textContent = grn.phone || 'N/A';
            document.getElementById('grnDetailSupplierEmail').textContent = grn.email || 'N/A';
            
            // Warehouse and payment
            document.getElementById('grnDetailWarehouse').textContent = grn.warehouse_location || 'Default Warehouse';
            
            const paymentStatusBadge = document.getElementById('grnDetailPaymentStatus');
            const paymentClass = grn.payment_status === 'Paid' ? 'badge-green' :
                                grn.payment_status === 'Partial' ? 'badge-orange' : 'badge-red';
            paymentStatusBadge.className = `badge ${paymentClass}`;
            paymentStatusBadge.textContent = grn.payment_status || 'Pending';
            
            // Financial summary
            const subtotal = parseFloat(grn.total_amount || 0);
            const taxAmount = parseFloat(grn.tax_amount || 0);
            const discountAmount = parseFloat(grn.discount_amount || 0);
            const netAmount = parseFloat(grn.net_amount || 0);
            
            document.getElementById('grnDetailSubtotal').textContent = 
                '৳' + subtotal.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('grnDetailTax').textContent = 
                '৳' + taxAmount.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('grnDetailDiscount').textContent = 
                '৳' + discountAmount.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('grnDetailNetAmount').textContent = 
                '৳' + netAmount.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            // Additional info
            document.getElementById('grnDetailNotes').textContent = grn.notes || 'No notes provided';
            document.getElementById('grnDetailReceivedBy').textContent = grn.received_by_name || 'N/A';
            document.getElementById('grnDetailVerifiedBy').textContent = grn.verified_by_name || 'Not verified';
            document.getElementById('grnDetailApprovedBy').textContent = grn.approved_by_name || 'Not approved';
            
            // Populate items table
            const itemsBody = document.getElementById('grnDetailItemsBody');
            if (grn.items && grn.items.length > 0) {
                itemsBody.innerHTML = grn.items.map((item, index) => {
                    // Use the actual fields from grn_items table
                    const quantityReceived = parseFloat(item.quantity_received || 0);
                    const quantityAccepted = parseFloat(item.quantity_accepted || 0);
                    const quantityRejected = parseFloat(item.quantity_rejected || 0);
                    const unitCost = parseFloat(item.unit_cost || 0);
                    const itemTotal = parseFloat(item.total_cost || 0); // Use the stored total_cost
                    const expiryDate = item.expiry_date ? new Date(item.expiry_date).toLocaleDateString('en-GB') : 'N/A';
                    
                    return `
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;">${index + 1}</td>
                            <td style="padding: 12px;">
                                <div style="font-weight: 600; color: #1e293b;">${escapeHtml(item.product_name || 'N/A')}</div>
                                ${item.sku ? `<div style="font-size: 12px; color: #6b7280; margin-top: 2px;">SKU: ${escapeHtml(item.sku)}</div>` : ''}
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <span style="background: #f1f5f9; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    ${escapeHtml(item.batch_number || 'N/A')}
                                </span>
                            </td>
                            <td style="padding: 12px; text-align: center; font-weight: 600;">
                                <div style="color: #1e293b;">${quantityReceived.toLocaleString()} ${item.unit || 'pcs'}</div>
                                ${quantityRejected > 0 ? `
                                    <div style="font-size: 11px; color: #6b7280; margin-top: 3px;">
                                        <span style="color: #10b981;">✓ ${quantityAccepted}</span> / 
                                        <span style="color: #ef4444;">✗ ${quantityRejected}</span>
                                    </div>
                                ` : ''}
                            </td>
                            <td style="padding: 12px; text-align: right; font-weight: 600;">
                                ৳${unitCost.toLocaleString('en-BD', {minimumFractionDigits: 2})}
                            </td>
                            <td style="padding: 12px; text-align: right; font-weight: 700; color: #3b82f6;">
                                ৳${itemTotal.toLocaleString('en-BD', {minimumFractionDigits: 2})}
                            </td>
                            <td style="padding: 12px; text-align: center; font-size: 13px;">
                                ${expiryDate}
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                itemsBody.innerHTML = '<tr><td colspan="7" style="padding: 30px; text-align: center; color: #6b7280;">No items found</td></tr>';
            }
            
            // Show the modal
            const modal = document.getElementById('viewGRNModal');
            if (modal) {
                modal.style.display = 'flex';
            }
            
            showNotification('GRN details loaded successfully', 'success');
        } else {
            showNotification('Failed to load GRN details', 'error');
        }
    } catch (error) {
        console.error('Failed to load GRN details:', error);
        showNotification('Error loading GRN details: ' + error.message, 'error');
    }
}

function closeViewGRNModal() {
    const modal = document.getElementById('viewGRNModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function printGRN() {
    showNotification('Print functionality coming soon', 'info');
    // TODO: Implement print functionality
}

async function verifyGRN(grnId) {
    if (!confirm('Are you sure you want to verify this GRN?')) return;
    
    try {
        const response = await fetchAPI('../../api/admin_incharge/grn.php?action=verify', {
            method: 'POST',
            body: JSON.stringify({ grn_id: grnId })
        });
        
        if (response.success) {
            showNotification('GRN verified successfully', 'success');
            loadGRNs();
        }
    } catch (error) {
        console.error('Failed to verify GRN:', error);
        showNotification('Failed to verify GRN: ' + error.message, 'error');
    }
}

async function approveGRN(grnId) {
    if (!confirm('Are you sure you want to approve this GRN? This will update stock levels.')) return;
    
    try {
        const response = await fetchAPI('../../api/admin_incharge/grn.php?action=approve', {
            method: 'POST',
            body: JSON.stringify({ grn_id: grnId })
        });
        
        if (response.success) {
            showNotification('GRN approved and stock updated successfully', 'success');
            loadGRNs();
            loadInventory();
            loadDashboardStats();
        }
    } catch (error) {
        console.error('Failed to approve GRN:', error);
        showNotification('Failed to approve GRN: ' + error.message, 'error');
    }
}

async function updatePaymentStatus(grnId, newStatus) {
    try {
        const response = await fetchAPI('../../api/admin_incharge/grn.php?action=update_payment', {
            method: 'POST',
            body: JSON.stringify({ 
                grn_id: grnId,
                payment_status: newStatus
            })
        });
        
        if (response.success) {
            showNotification(`Payment status updated to ${newStatus}`, 'success');
            loadGRNs();
        }
    } catch (error) {
        console.error('Failed to update payment status:', error);
        showNotification('Failed to update payment status: ' + error.message, 'error');
        // Reload to revert the dropdown
        loadGRNs();
    }
}

function filterGRNs() {
    const statusFilter = document.getElementById('grnStatusFilter')?.value.toLowerCase();
    const dateFilter = document.getElementById('grnDateFilter')?.value;
    
    const rows = document.querySelectorAll('#grnTableBody tr');
    rows.forEach(row => {
        const status = row.querySelector('.badge')?.textContent.toLowerCase();
        const date = row.cells[2]?.textContent;
        
        let showRow = true;
        
        if (statusFilter && !status?.includes(statusFilter)) {
            showRow = false;
        }
        
        if (dateFilter && date && date !== 'N/A') {
            const rowDate = new Date(date.split('/').reverse().join('-'));
            const filterDate = new Date(dateFilter);
            if (rowDate.toDateString() !== filterDate.toDateString()) {
                showRow = false;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

function clearGRNFilters() {
    document.getElementById('grnStatusFilter').value = '';
    document.getElementById('grnDateFilter').value = '';
    filterGRNs();
}

// Load Suppliers
async function loadSuppliers() {
    try {
        const data = await fetchAPI('../../api/admin_incharge/suppliers.php');
        if (data.success && (data.data || data.suppliers)) {
            renderSuppliersTable(data.data || data.suppliers);
        } else {
            renderSuppliersTable([]);
        }
    } catch (error) {
        console.error('Failed to load suppliers:', error);
        const tbody = document.getElementById('suppliersTableBody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #ef4444;">Failed to load suppliers. Please try again.</td></tr>';
        }
    }
}

// Render Suppliers Table
function renderSuppliersTable(suppliers) {
    const tbody = document.getElementById('suppliersTableBody');
    if (!tbody) return;
    
    if (!suppliers || suppliers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 30px; color: #6b7280;">No suppliers found. Click "Add Supplier" to add your first supplier.</td></tr>';
        return;
    }
    
    tbody.innerHTML = suppliers.map(supplier => {
        const statusClass = supplier.status === 'Active' ? 'badge-green' :
                           supplier.status === 'Inactive' ? 'badge-gray' : 'badge-red';
        
        const rating = supplier.overall_rating ? parseFloat(supplier.overall_rating) : 0;
        const stars = '⭐'.repeat(Math.floor(rating));
        const ratingText = rating > 0 ? `${stars} (${rating.toFixed(1)})` : 'No rating';
        
        return `
            <tr>
                <td><strong style="color: var(--primary-color);">${supplier.supplier_code || 'N/A'}</strong></td>
                <td>
                    <div style="font-weight: 600;">${supplier.company_name}</div>
                    <div style="font-size: 12px; color: #6b7280;">${supplier.email || 'No email'}</div>
                </td>
                <td>
                    <div>${supplier.contact_person || 'N/A'}</div>
                    <div style="font-size: 12px; color: #6b7280;">${supplier.phone || 'No phone'}</div>
                </td>
                <td><span class="badge badge-purple">${supplier.payment_terms || 'N/A'}</span></td>
                <td>${ratingText}</td>
                <td><span class="badge ${statusClass}">${supplier.status || 'Active'}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn" onclick="viewSupplierDetails(${supplier.supplier_id || supplier.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn" onclick="editSupplier(${supplier.supplier_id || supplier.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn" onclick="viewSupplierPerformance(${supplier.supplier_id || supplier.id})" title="Performance" style="color: #8b5cf6;">
                            <i class="fas fa-chart-line"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

async function viewSupplierDetails(supplierId) {
    try {
        const response = await fetchAPI(`../../api/admin_incharge/suppliers.php?id=${supplierId}`);
        if (response.success && response.data) {
            showNotification('Supplier details loaded', 'info');
            // TODO: Show details modal
        }
    } catch (error) {
        console.error('Failed to load supplier details:', error);
    }
}

async function editSupplier(supplierId) {
    showNotification('Edit supplier feature coming soon', 'info');
}

async function viewSupplierPerformance(supplierId) {
    showNotification('Supplier performance view coming soon', 'info');
}

function filterSuppliers() {
    const statusFilter = document.getElementById('supplierStatusFilter')?.value.toLowerCase();
    const searchFilter = document.getElementById('supplierSearchFilter')?.value.toLowerCase();
    
    const rows = document.querySelectorAll('#suppliersTableBody tr');
    rows.forEach(row => {
        if (row.cells.length < 7) return; // Skip "no data" rows
        
        const code = row.cells[0]?.textContent.toLowerCase();
        const company = row.cells[1]?.textContent.toLowerCase();
        const contact = row.cells[2]?.textContent.toLowerCase();
        const status = row.querySelector('.badge:last-of-type')?.textContent.toLowerCase();
        
        let showRow = true;
        
        if (statusFilter && !status?.includes(statusFilter)) {
            showRow = false;
        }
        
        if (searchFilter) {
            const matchesSearch = code?.includes(searchFilter) || 
                                 company?.includes(searchFilter) || 
                                 contact?.includes(searchFilter);
            if (!matchesSearch) {
                showRow = false;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

function clearSupplierFilters() {
    document.getElementById('supplierStatusFilter').value = '';
    document.getElementById('supplierSearchFilter').value = '';
    filterSuppliers();
}

// Generate Barcode
async function generateBarcode(productId) {
    try {
        const data = await fetchAPI('../../api/admin_incharge/barcodes.php', {
            method: 'POST',
            body: { product_id: productId }
        });
        
        showNotification('Barcode generated successfully!', 'success');
        
        // Show barcode in modal (you can implement this)
        console.log('Barcode data:', data.barcode);
        
    } catch (error) {
        showNotification('Failed to generate barcode', 'error');
    }
}

// Helper Functions
function getStockColor(quantity, threshold) {
    if (quantity === 0) return 'var(--error-color)';
    if (quantity <= threshold) return '#ea580c';
    return '#059669';
}

function getStatusBadgeColor(status) {
    const colorMap = {
        'In Stock': 'green',
        'Low Stock': 'orange',
        'Out of Stock': 'red'
    };
    return colorMap[status] || 'gray';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, type = 'info') {
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
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Display Stock Alerts
function displayStockAlerts(alerts) {
    if (alerts.length === 0) return;
    
    const criticalAlerts = alerts.filter(a => a.alert_level === 'Critical' || a.alert_level === 'Urgent');
    
    if (criticalAlerts.length > 0) {
        showNotification(`${criticalAlerts.length} critical stock alerts require attention!`, 'error');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin In-charge dashboard loaded successfully');
    loadDashboardStats();
    
    // Attach Add Stock form handler
    const addStockForm = document.getElementById('addStockForm');
    if (addStockForm) {
        addStockForm.addEventListener('submit', handleAddStock);
        console.log('Add Stock form listener attached');
    }
});

// ========================================
// QUICK ADD PRODUCT MODAL
// ========================================

function showQuickAddProductModal() {
    const modal = document.getElementById('quickAddProductModal');
    if (modal) {
        // Generate SKU
        const timestamp = Date.now();
        const sku = `SKU-${timestamp}`;
        document.getElementById('quickProductSKU').value = sku;
        
        // Reset form
        document.getElementById('quickAddProductForm').reset();
        document.getElementById('quickProductSKU').value = sku; // Set again after reset
        
        modal.style.display = 'flex';
    }
}

function closeQuickAddProductModal() {
    const modal = document.getElementById('quickAddProductModal');
    if (modal) {
        modal.style.display = 'none';
        document.getElementById('quickAddProductForm').reset();
    }
}

async function handleQuickAddProduct(event) {
    event.preventDefault();
    
    const name = document.getElementById('quickProductName').value.trim();
    const sku = document.getElementById('quickProductSKU').value.trim();
    const category = document.getElementById('quickProductCategory').value;
    const unit = document.getElementById('quickProductUnit').value;
    const cost = parseFloat(document.getElementById('quickProductCost').value);
    const price = parseFloat(document.getElementById('quickProductPrice').value);
    const description = document.getElementById('quickProductDescription').value.trim();
    
    // Validation
    if (!name || !sku || !category || !unit || isNaN(cost) || isNaN(price)) {
        alert('Please fill in all required fields');
        return;
    }
    
    if (price < cost) {
        if (!confirm('Selling price is lower than cost price. Continue anyway?')) {
            return;
        }
    }
    
    try {
        const response = await fetch('../../api/moderator/products.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: name,
                sku: sku,
                category: category,
                unit: unit,
                cost_price: cost,
                selling_price: price,
                description: description,
                stock_quantity: 0,
                min_stock_level: 0,
                status: 'active'
            })
        });
        
        console.log('Response status:', response.status);
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        const data = JSON.parse(responseText);
        console.log('Parsed data:', data);
        
        if (data.success) {
            // Add to products array
            const newProduct = {
                id: data.product_id,
                name: name,
                sku: sku,
                category: category,
                unit: unit,
                cost_price: cost,
                selling_price: price,
                stock_quantity: 0
            };
            productsData.push(newProduct);
            
            // Update all product dropdowns in GRN items
            updateAllProductDropdowns();
            
            // Show success message
            alert(`Product "${name}" added successfully! You can now select it in your GRN items.`);
            
            // Close modal
            closeQuickAddProductModal();
        } else {
            console.error('API Error:', data);
            alert('Error: ' + (data.message || data.error || 'Failed to add product'));
        }
    } catch (error) {
        console.error('Error adding product:', error);
        alert('Error adding product. Please check console for details.');
    }
}

function updateAllProductDropdowns() {
    // Update all existing GRN item product dropdowns
    const productSelects = document.querySelectorAll('.grn-item-product');
    productSelects.forEach(select => {
        const currentValue = select.value;
        
        // Rebuild options
        select.innerHTML = '<option value="">Select Product</option>';
        productsData.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = `${product.name} - ${product.sku}`;
            if (product.id == currentValue) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    });
}

// ========================================
// ADD STOCK MODAL FUNCTIONS
// ========================================

async function showAddStockModal() {
    console.log('Opening Add Stock modal...');
    
    // Load products if not already loaded
    if (!productsData || productsData.length === 0) {
        try {
            const response = await fetchAPI('../../api/admin_incharge/inventory.php');
            if (response.success && (response.data || response.inventory)) {
                productsData = response.data || response.inventory;
                console.log('Products loaded for Add Stock:', productsData.length);
            }
        } catch (error) {
            console.error('Failed to load products:', error);
            showNotification('Failed to load products', 'error');
            return;
        }
    }
    
    // Populate product dropdown
    const productSelect = document.getElementById('addStockProduct');
    if (productSelect) {
        if (!productsData || productsData.length === 0) {
            productSelect.innerHTML = '<option value="">No products available - Add products first</option>';
            showNotification('⚠️ No products found. Please add products to your inventory first.', 'info');
        } else {
            let options = '<option value="">Select Product</option>';
            productsData.forEach(p => {
                const id = p.id || p.product_id;
                const name = p.name || p.product_name;
                const stock = p.stock_quantity || 0;
                const unit = p.unit || '';
                options += `<option value="${id}">${name} (Current Stock: ${stock} ${unit})</option>`;
            });
            productSelect.innerHTML = options;
        }
    }
    
    // Reset form and auto-generate batch number
    const form = document.getElementById('addStockForm');
    if (form) form.reset();
    generateBatchNumber();
    
    // Show modal
    const modal = document.getElementById('addStockModal');
    if (modal) {
        modal.style.display = 'flex';
        console.log('Add Stock modal opened');
    } else {
        console.warn('addStockModal element not found');
    }
}

function closeAddStockModal() {
    console.log('Closing Add Stock modal');
    const modal = document.getElementById('addStockModal');
    if (modal) {
        modal.style.display = 'none';
    }
    document.getElementById('addStockForm').reset();
}

// Generate batch number automatically
function generateBatchNumber() {
    const productSelect = document.getElementById('addStockProduct');
    const batchInput = document.getElementById('addStockBatch');
    
    if (!batchInput) return;
    
    const year = new Date().getFullYear().toString().slice(-2); // Last 2 digits of year
    const timestamp = Date.now().toString().slice(-6); // Last 6 digits of timestamp for uniqueness
    
    let productCode = 'PROD';
    
    // If product is selected, use first 2-3 letters of product name
    if (productSelect && productSelect.value && productsData) {
        const selectedProduct = productsData.find(p => (p.id || p.product_id) == productSelect.value);
        if (selectedProduct) {
            const name = selectedProduct.name || selectedProduct.product_name;
            const words = name.split(' ');
            productCode = words.map(w => w.charAt(0)).join('').toUpperCase().slice(0, 3);
            if (productCode.length < 2) {
                productCode = name.slice(0, 3).toUpperCase();
            }
        }
    }
    
    const batchNumber = `B${year}-${productCode}-${timestamp}`;
    batchInput.value = batchNumber;
    
    console.log('✅ Generated batch number:', batchNumber);
    return batchNumber;
}

async function handleAddStock(event) {
    event.preventDefault();
    console.log('Submitting Add Stock form...');
    
    const productId = document.getElementById('addStockProduct').value;
    const quantity = document.getElementById('addStockQuantity').value;
    const unitCost = document.getElementById('addStockUnitCost').value;
    let batchNumber = document.getElementById('addStockBatch').value.trim();
    const warehouseLocation = document.getElementById('addStockLocation').value;
    const mfgDate = document.getElementById('addStockMfgDate').value;
    const expiryDate = document.getElementById('addStockExpiryDate').value;
    const reason = document.getElementById('addStockReason').value;
    
    // Validation
    if (!productId) {
        showNotification('Please select a product', 'error');
        return;
    }
    
    if (!quantity || quantity <= 0) {
        showNotification('Please enter a valid quantity', 'error');
        return;
    }
    
    if (!unitCost || unitCost <= 0) {
        showNotification('Please enter a valid unit cost', 'error');
        return;
    }
    
    // Auto-generate batch number if not provided
    if (!batchNumber) {
        batchNumber = generateBatchNumber();
        console.log('Auto-generated batch number:', batchNumber);
    }
    
    if (!reason.trim()) {
        showNotification('Please provide a reason', 'error');
        return;
    }
    
    // Validate dates if provided
    if (expiryDate && mfgDate) {
        const expiry = new Date(expiryDate);
        const manufacturing = new Date(mfgDate);
        
        if (expiry <= manufacturing) {
            showNotification('Expiry date must be after manufacturing date', 'error');
            return;
        }
    }
    
    const addStockData = {
        product_id: parseInt(productId),
        quantity_change: parseInt(quantity),
        batch_number: batchNumber,
        unit_cost: parseFloat(unitCost),
        warehouse_location: warehouseLocation.trim() || null,
        manufacturing_date: mfgDate || null,
        expiry_date: expiryDate || null,
        reason: reason.trim()
    };
    
    console.log('📦 Sending add stock data:', addStockData);
    
    try {
        const response = await fetchAPI('../../api/admin_incharge/inventory.php', {
            method: 'POST',
            body: addStockData
        });
        
        if (response.success) {
            showNotification('✅ Stock added successfully!', 'success');
            closeAddStockModal();
            document.getElementById('addStockForm').reset();
            
            // Reload data
            if (typeof loadInventory === 'function') loadInventory();
            if (typeof loadDashboardStats === 'function') loadDashboardStats();
            
            console.log(`Stock updated: ${response.quantity_before || 0} → ${response.quantity_after || 0}`);
            console.log(`Batch created/updated: ${response.batch_number}`);
        } else {
            showNotification(response.error || 'Failed to add stock', 'error');
        }
    } catch (error) {
        console.error('❌ Failed to add stock:', error);
        showNotification('Failed to add stock: ' + error.message, 'error');
    }
}

// Expose modal functions to window so inline onclick handlers work reliably
(function exposeGlobals() {
    try {
        window.showAddStockModal = showAddStockModal;
        window.closeAddStockModal = closeAddStockModal;
        window.generateBatchNumber = generateBatchNumber;
        window.showCreateGRNModal = showCreateGRNModal;
        window.closeGRNModal = closeGRNModal;
        window.addGRNItem = addGRNItem;
        window.removeGRNItem = removeGRNItem;
        window.updateGRNItemTotal = updateGRNItemTotal;
        window.calculateGRNTotal = calculateGRNTotal;
        window.saveDraftGRN = saveDraftGRN;
        window.showCreateSupplierModal = showCreateSupplierModal;
        window.showStockAdjustModal = showStockAdjustModal;
        window.closeModal = closeModal;
        window.showCurrentStock = showCurrentStock;
        window.viewGRNDetails = viewGRNDetails;
        window.closeViewGRNModal = closeViewGRNModal;
        window.printGRN = printGRN;
        console.log('Modal functions exposed on window for debugging');
    } catch (e) {
        console.warn('Failed to expose modal globals', e);
    }
})();

// ========================================
// GRN MODAL FUNCTIONS
// ========================================

async function showCreateGRNModal() {
    console.log('Opening Create GRN modal...');
    
    // Load suppliers if not already loaded
    if (!suppliersData || suppliersData.length === 0) {
        try {
            const response = await fetchAPI('../../api/admin_incharge/suppliers.php');
            if (response.success && response.suppliers) {
                suppliersData = response.suppliers;
            }
        } catch (error) {
            console.error('Failed to load suppliers:', error);
        }
    }
    
    // Load products if not already loaded
    if (!productsData || productsData.length === 0) {
        try {
            const response = await fetchAPI('../../api/admin_incharge/inventory.php');
            if (response.success && (response.data || response.inventory)) {
                productsData = response.data || response.inventory;
            }
        } catch (error) {
            console.error('Failed to load products:', error);
        }
    }
    
    // Populate supplier dropdown
    const supplierSelect = document.getElementById('grnSupplier');
    if (supplierSelect) {
        if (!suppliersData || suppliersData.length === 0) {
            supplierSelect.innerHTML = '<option value="">No suppliers available - Add suppliers first</option>';
            showNotification('⚠️ Please add suppliers first before creating GRN', 'info');
        } else {
            let options = '<option value="">Select Supplier</option>';
            suppliersData.forEach(s => {
                const id = s.id || s.supplier_id;
                const name = s.company_name || s.name;
                options += `<option value="${id}">${name}</option>`;
            });
            supplierSelect.innerHTML = options;
        }
    }
    
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('grnReceivedDate').value = today;
    document.getElementById('grnInvoiceDate').value = today;
    
    // Reset form and items
    grnItemCount = 0;
    document.getElementById('grnItemsContainer').innerHTML = '';
    document.getElementById('createGRNForm').reset();
    
    // Add first item by default
    addGRNItem();
    
    // Show modal
    const modal = document.getElementById('createGRNModal');
    if (modal) {
        modal.style.display = 'flex';
        console.log('Create GRN modal opened');
    }
}

function closeGRNModal() {
    const modal = document.getElementById('createGRNModal');
    if (modal) {
        modal.style.display = 'none';
    }
    grnItemCount = 0;
    document.getElementById('grnItemsContainer').innerHTML = '';
}

function addGRNItem() {
    grnItemCount++;
    const container = document.getElementById('grnItemsContainer');
    
    const itemDiv = document.createElement('div');
    itemDiv.className = 'grn-item';
    itemDiv.id = `grnItem_${grnItemCount}`;
    
    let productOptions = '<option value="">Select Product</option>';
    if (productsData && productsData.length > 0) {
        productsData.forEach(p => {
            const id = p.id || p.product_id;
            const name = p.name || p.product_name;
            productOptions += `<option value="${id}">${name}</option>`;
        });
    }
    
    itemDiv.innerHTML = `
        <div class="grn-item-header">
            <span class="grn-item-number">
                <i class="fas fa-box"></i> Item #${grnItemCount}
            </span>
            <button type="button" class="grn-item-remove" onclick="removeGRNItem(${grnItemCount})">
                <i class="fas fa-trash"></i> Remove
            </button>
        </div>
        <div class="grn-item-fields">
            <div style="grid-column: 1 / -1;">
                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 13px;">
                    Product <span style="color: #ef4444;">*</span>
                </label>
                <select class="grn-item-product" required style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px;">
                    ${productOptions}
                </select>
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 13px;">
                    Quantity Received <span style="color: #ef4444;">*</span>
                </label>
                <input type="number" class="grn-item-qty-received" min="0" required placeholder="0" onchange="updateGRNItemTotal(${grnItemCount})" style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; box-sizing: border-box;">
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 13px;">
                    Quantity Accepted <span style="color: #ef4444;">*</span>
                </label>
                <input type="number" class="grn-item-qty-accepted" min="0" required placeholder="0" onchange="updateGRNItemTotal(${grnItemCount})" style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; box-sizing: border-box;">
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 13px;">
                    Unit Cost (BDT) <span style="color: #ef4444;">*</span>
                </label>
                <input type="number" class="grn-item-unit-cost" min="0" step="0.01" required placeholder="0.00" onchange="updateGRNItemTotal(${grnItemCount})" style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; box-sizing: border-box;">
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 13px;">
                    Total Cost <span style="color: #64748b; font-weight: normal;">(Auto)</span>
                </label>
                <input type="number" class="grn-item-total-cost" readonly style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; background: #f8fafc; box-sizing: border-box;">
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 13px;">
                    Batch Number <span style="color: #10b981; font-weight: normal; font-size: 11px;">(Auto-generated)</span>
                </label>
                <input type="text" class="grn-item-batch" placeholder="Will be auto-generated" readonly style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; background: #f9fafb; box-sizing: border-box; color: #6b7280;">
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 13px;">
                    Manufacturing Date
                </label>
                <input type="date" class="grn-item-mfg-date" style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; box-sizing: border-box;">
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 13px;">
                    Expiry Date
                </label>
                <input type="date" class="grn-item-expiry-date" style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; box-sizing: border-box;">
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 13px;">
                    Warehouse Location
                </label>
                <input type="text" class="grn-item-location" placeholder="e.g., A-12-03" style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px; box-sizing: border-box;">
            </div>
            
            <div style="grid-column: 1 / -1;">
                <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 13px;">
                    Condition
                </label>
                <select class="grn-item-condition" style="width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 13px;">
                    <option value="New">New</option>
                    <option value="Used">Used</option>
                    <option value="Refurbished">Refurbished</option>
                    <option value="Damaged">Damaged</option>
                </select>
            </div>
        </div>
    `;
    
    container.appendChild(itemDiv);
    calculateGRNTotal();
}

function removeGRNItem(itemId) {
    const item = document.getElementById(`grnItem_${itemId}`);
    if (item) {
        item.remove();
        calculateGRNTotal();
    }
}

function updateGRNItemTotal(itemId) {
    const item = document.getElementById(`grnItem_${itemId}`);
    if (!item) return;
    
    const qtyAccepted = parseFloat(item.querySelector('.grn-item-qty-accepted').value) || 0;
    const unitCost = parseFloat(item.querySelector('.grn-item-unit-cost').value) || 0;
    const totalCost = qtyAccepted * unitCost;
    
    item.querySelector('.grn-item-total-cost').value = totalCost.toFixed(2);
    
    calculateGRNTotal();
}

function calculateGRNTotal() {
    const items = document.querySelectorAll('.grn-item');
    let subtotal = 0;
    
    items.forEach(item => {
        const totalCost = parseFloat(item.querySelector('.grn-item-total-cost').value) || 0;
        subtotal += totalCost;
    });
    
    const taxPercent = parseFloat(document.getElementById('grnTaxPercent').value) || 0;
    const discountAmount = parseFloat(document.getElementById('grnDiscount').value) || 0;
    
    const taxAmount = (subtotal * taxPercent) / 100;
    const netTotal = subtotal + taxAmount - discountAmount;
    
    document.getElementById('grnSubtotal').textContent = subtotal.toFixed(2) + ' BDT';
    document.getElementById('grnTaxAmount').textContent = taxAmount.toFixed(2) + ' BDT';
    document.getElementById('grnDiscountAmount').textContent = '- ' + discountAmount.toFixed(2) + ' BDT';
    document.getElementById('grnNetTotal').textContent = netTotal.toFixed(2) + ' BDT';
}

async function createGRN(event) {
    event.preventDefault();
    console.log('Creating GRN...');
    
    // Collect form data
    const supplierId = document.getElementById('grnSupplier').value;
    const invoiceNumber = document.getElementById('grnInvoiceNumber').value;
    const invoiceDate = document.getElementById('grnInvoiceDate').value;
    const receivedDate = document.getElementById('grnReceivedDate').value;
    const poNumber = document.getElementById('grnPONumber').value;
    const taxPercent = parseFloat(document.getElementById('grnTaxPercent').value) || 0;
    const discountAmount = parseFloat(document.getElementById('grnDiscount').value) || 0;
    const paymentStatus = document.getElementById('grnPaymentStatus').value;
    const warehouseLocation = document.getElementById('grnWarehouseLocation').value;
    const notes = document.getElementById('grnNotes').value;
    
    // Validation
    if (!supplierId) {
        showNotification('Please select a supplier', 'error');
        return;
    }
    
    if (!invoiceNumber || !invoiceDate || !receivedDate) {
        showNotification('Please fill in all required invoice details', 'error');
        return;
    }
    
    // Collect items
    const items = [];
    const itemDivs = document.querySelectorAll('.grn-item');
    
    if (itemDivs.length === 0) {
        showNotification('Please add at least one item', 'error');
        return;
    }
    
    itemDivs.forEach((itemDiv, index) => {
        const productId = itemDiv.querySelector('.grn-item-product').value;
        const qtyReceived = parseInt(itemDiv.querySelector('.grn-item-qty-received').value) || 0;
        const qtyAccepted = parseInt(itemDiv.querySelector('.grn-item-qty-accepted').value) || 0;
        const unitCost = parseFloat(itemDiv.querySelector('.grn-item-unit-cost').value) || 0;
        const batch = itemDiv.querySelector('.grn-item-batch').value.trim();
        const mfgDate = itemDiv.querySelector('.grn-item-mfg-date').value;
        const expiryDate = itemDiv.querySelector('.grn-item-expiry-date').value;
        const location = itemDiv.querySelector('.grn-item-location').value;
        const condition = itemDiv.querySelector('.grn-item-condition').value;
        
        if (!productId || qtyReceived <= 0 || qtyAccepted <= 0 || unitCost <= 0) {
            showNotification(`Item #${index + 1}: Please fill in all required fields`, 'error');
            return;
        }
        
        const itemData = {
            product_id: productId,
            quantity_received: qtyReceived,
            quantity_accepted: qtyAccepted,
            quantity_rejected: qtyReceived - qtyAccepted,
            unit_cost: unitCost,
            total_cost: qtyAccepted * unitCost,
            manufacturing_date: mfgDate || null,
            expiry_date: expiryDate || null,
            warehouse_location: location || null,
            condition_status: condition
        };
        
        // Only include batch number if user manually entered one (not auto-generated)
        if (batch && batch !== 'Will be auto-generated') {
            itemData.batch_number = batch;
        }
        
        items.push(itemData);
    });
    
    if (items.length === 0) {
        return;
    }
    
    // Calculate totals
    const subtotal = items.reduce((sum, item) => sum + item.total_cost, 0);
    const taxAmount = (subtotal * taxPercent) / 100;
    const netTotal = subtotal + taxAmount - discountAmount;
    
    const grnData = {
        supplier_id: supplierId,
        invoice_number: invoiceNumber,
        invoice_date: invoiceDate,
        received_date: receivedDate,
        purchase_order_number: poNumber || null,
        total_items: items.length,
        total_quantity: items.reduce((sum, item) => sum + item.quantity_accepted, 0),
        total_amount: subtotal,
        tax_amount: taxAmount,
        discount_amount: discountAmount,
        net_amount: netTotal,
        payment_status: paymentStatus,
        warehouse_location: warehouseLocation || null,
        notes: notes || null,
        items: items
    };
    
    console.log('📦 GRN Data:', grnData);
    
    try {
        const response = await fetchAPI('../../api/admin_incharge/grn.php', {
            method: 'POST',
            body: JSON.stringify(grnData)
        });
        
        if (response.success) {
            showNotification('✅ GRN created successfully!', 'success');
            closeGRNModal();
            
            // Reload GRN data
            if (typeof loadGRNs === 'function') loadGRNs();
            if (typeof loadDashboardStats === 'function') loadDashboardStats();
            
            console.log(`GRN created: ${response.grn_number || 'New GRN'}`);
        } else {
            showNotification(response.error || 'Failed to create GRN', 'error');
        }
    } catch (error) {
        console.error('❌ Failed to create GRN:', error);
        showNotification('Failed to create GRN: ' + error.message, 'error');
    }
}

async function saveDraftGRN() {
    showNotification('💾 Save as draft feature coming soon!', 'info');
}

// Attach form submit handler
document.addEventListener('DOMContentLoaded', function() {
    const grnForm = document.getElementById('createGRNForm');
    if (grnForm) {
        grnForm.addEventListener('submit', createGRN);
    }
});

// ========================================
// OLD GRN MODAL FUNCTIONS (Legacy - will be removed)
// ========================================

// GRN Modal Functions
async function showCreateGRNModal() {
    console.log('Opening GRN modal...');
    
    // Load suppliers for dropdown
    try {
        const response = await fetchAPI('../../api/admin_incharge/suppliers.php');
        if (response.success && (response.suppliers || response.data)) {
            suppliersData = response.suppliers || response.data;
            const supplierSelect = document.getElementById('grnSupplier');
            if (supplierSelect) {
                let options = '<option value="">Select Supplier</option>';
                suppliersData.forEach(supplier => {
                    const id = supplier.id || supplier.supplier_id;
                    const code = supplier.supplier_code || supplier.code || '';
                    options += `<option value="${id}">${supplier.company_name} ${code ? '(' + code + ')' : ''}</option>`;
                });
                supplierSelect.innerHTML = options;
                console.log('Suppliers loaded:', suppliersData.length);
            }
        }
        
        // Load products for item dropdown
        const inventoryResponse = await fetchAPI('../../api/admin_incharge/inventory.php');
        if (inventoryResponse.success && (inventoryResponse.inventory || inventoryResponse.data)) {
            productsData = inventoryResponse.inventory || inventoryResponse.data;
            console.log('Products loaded:', productsData.length);
        }
    } catch (error) {
        console.error('Failed to load data for GRN modal:', error);
        showNotification('Failed to load suppliers and products', 'error');
    }
    
    // Set default date to today
    const dateInput = document.getElementById('grnReceivedDate');
    if (dateInput) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // Reset form
    grnItemCount = 0;
    const form = document.getElementById('createGRNForm');
    if (form) form.reset();
    const container = document.getElementById('grnItemsContainer');
    if (container) container.innerHTML = '';
    calculateGRNTotal(); // Reset totals
    
    // Show modal
    const createModal = document.getElementById('createGRNModal');
    if (createModal) {
        // Force modal to show with aggressive styling
        createModal.style.cssText = `
            display: flex !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.5) !important;
            z-index: 99999 !important;
            align-items: center !important;
            justify-content: center !important;
        `;
        console.log('GRN modal opened, display set to:', createModal.style.display);
        console.log('GRN modal computed display:', window.getComputedStyle(createModal).display);
        console.log('GRN modal element:', createModal);
    } else {
        console.warn('createGRNModal element not found');
    }
}

// Supplier Modal Functions
window.showCreateSupplierModal = function() {
    console.log('Opening Create Supplier modal...');
    const supplierModal = document.getElementById('createSupplierModal');
    if (supplierModal) {
        // Reset form
        const form = document.getElementById('createSupplierForm');
        if (form) {
            form.reset();
            // Reset to default values
            document.getElementById('supplierCountry').value = 'Bangladesh';
            document.getElementById('supplierPaymentTerms').value = 'Net 30';
            document.getElementById('supplierStatus').value = 'Active';
            document.getElementById('supplierCreditLimit').value = '0';
        }
        
        // Generate supplier code
        generateSupplierCode();
        
        // Force modal to show
        supplierModal.style.cssText = `
            display: flex !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.5) !important;
            z-index: 99999 !important;
            align-items: center !important;
            justify-content: center !important;
        `;
        console.log('Create Supplier modal opened');
    } else {
        console.warn('createSupplierModal element not found');
    }
}

window.closeCreateSupplierModal = function() {
    const modal = document.getElementById('createSupplierModal');
    if (!modal) return;
    
    // Clear form
    const form = document.getElementById('createSupplierForm');
    if (form) {
        form.reset();
    }
    
    // Hide modal
    modal.style.display = 'none';
}

function generateSupplierCode() {
    // Generate code in format: SUP-YYYYMMDD-XXXX
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    
    const code = `SUP-${year}${month}${day}-${random}`;
    
    const codeInput = document.getElementById('supplierCode');
    if (codeInput) {
        codeInput.value = code;
    }
}

window.handleCreateSupplier = async function(event) {
    event.preventDefault();
    
    console.log('Creating supplier...');
    
    // Get form values
    const supplierData = {
        supplier_code: document.getElementById('supplierCode').value,
        company_name: document.getElementById('supplierCompanyName').value,
        contact_person: document.getElementById('supplierContactPerson').value,
        email: document.getElementById('supplierEmail').value,
        phone: document.getElementById('supplierPhone').value,
        tax_id: document.getElementById('supplierTaxId').value || null,
        address: document.getElementById('supplierAddress').value || null,
        city: document.getElementById('supplierCity').value || null,
        state: document.getElementById('supplierState').value || null,
        country: document.getElementById('supplierCountry').value,
        postal_code: document.getElementById('supplierPostalCode').value || null,
        payment_terms: document.getElementById('supplierPaymentTerms').value,
        credit_limit: parseFloat(document.getElementById('supplierCreditLimit').value || 0),
        status: document.getElementById('supplierStatus').value,
        notes: document.getElementById('supplierNotes').value || null
    };
    
    console.log('Supplier data:', supplierData);
    
    try {
        const result = await fetchAPI('../../api/admin_incharge/suppliers.php', {
            method: 'POST',
            body: JSON.stringify(supplierData)
        });
        
        console.log('Supplier created successfully:', result);
        
        // Show success message
        alert('Supplier created successfully!');
        
        // Refresh suppliers data for dropdowns
        await loadSuppliersData();
        
        // Close modal
        closeCreateSupplierModal();
        
        // Reload suppliers table if on that tab
        if (typeof loadSuppliers === 'function') {
            loadSuppliers();
        }
        
    } catch (error) {
        console.error('Error creating supplier:', error);
        alert('Error creating supplier: ' + error.message);
    }
}

// Function to load suppliers data for dropdowns
async function loadSuppliersData() {
    try {
        const result = await fetchAPI('../../api/admin_incharge/suppliers.php');
        if (result.success && Array.isArray(result.data)) {
            suppliersData = result.data;
            console.log('Suppliers data refreshed:', suppliersData.length, 'suppliers');
            
            // Update all supplier dropdowns in the page
            updateAllSupplierDropdowns();
        }
    } catch (error) {
        console.error('Error loading suppliers:', error);
    }
}

// Function to update all supplier dropdowns
function updateAllSupplierDropdowns() {
    // Update GRN supplier dropdown
    const grnSupplierSelect = document.getElementById('grnSupplier');
    if (grnSupplierSelect && suppliersData.length > 0) {
        // Store current value
        const currentValue = grnSupplierSelect.value;
        
        // Clear and rebuild
        grnSupplierSelect.innerHTML = '<option value="">Select Supplier</option>';
        suppliersData.forEach(supplier => {
            const option = document.createElement('option');
            option.value = supplier.id;
            option.textContent = `${supplier.company_name} (${supplier.supplier_code})`;
            grnSupplierSelect.appendChild(option);
        });
        
        // Restore value if it still exists
        if (currentValue) {
            grnSupplierSelect.value = currentValue;
        }
    }
    
    // Add other supplier dropdown updates here if needed
}

// Removed duplicate createSupplier function - using handleCreateSupplier instead

// Stock Adjustment Modal
async function showStockAdjustModal(productId) {
    // Load products if not already loaded
    if (!productsData || productsData.length === 0) {
        try {
            const response = await fetchAPI('../../api/admin_incharge/inventory.php');
            if (response.success && (response.data || response.inventory)) {
                productsData = response.data || response.inventory;
            }
        } catch (error) {
            console.error('Failed to load products:', error);
            showNotification('Failed to load products', 'error');
            return;
        }
    }
    
    const productSelect = document.getElementById('adjustProductSelect');
    if (productSelect && productsData && productsData.length > 0) {
        let options = '<option value="">Select Product</option>';
        productsData.forEach(p => {
            const id = p.id || p.product_id;
            const name = p.name || p.product_name;
            const stock = p.stock_quantity || 0;
            options += `<option value="${id}">${name} (Current: ${stock})</option>`;
        });
        productSelect.innerHTML = options;
        
        if (productId) {
            productSelect.value = productId;
            showCurrentStock(productId);
        }
    }
    
    // Reset form
    const form = document.getElementById('stockAdjustForm');
    if (form) form.reset();
    
    if (productId) {
        document.getElementById('adjustProductId').value = productId;
        if (productSelect) productSelect.value = productId;
    }
    
    // Hide stock display initially
    const stockDisplay = document.getElementById('currentStockDisplay');
    if (stockDisplay && !productId) {
        stockDisplay.style.display = 'none';
    }
    
    const stockModal = document.getElementById('stockAdjustModal');
    if (stockModal) {
        // Force modal to show with aggressive styling
        stockModal.style.cssText = `
            display: flex !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.5) !important;
            z-index: 99999 !important;
            align-items: center !important;
            justify-content: center !important;
        `;
        console.log('Stock adjust modal opened', { productId, display: stockModal.style.display });
    } else {
        console.warn('stockAdjustModal element not found');
    }
}

function showCurrentStock(productId) {
    if (!productId) {
        const stockDisplay = document.getElementById('currentStockDisplay');
        if (stockDisplay) stockDisplay.style.display = 'none';
        return;
    }
    
    const product = productsData.find(p => (p.id || p.product_id) == productId);
    const stockDisplay = document.getElementById('currentStockDisplay');
    const stockElement = document.getElementById('currentStock');
    
    if (product && stockDisplay && stockElement) {
        stockElement.textContent = product.stock_quantity || 0;
        stockDisplay.style.display = 'block';
    }
}

function updateAdjustmentSign() {
    const type = document.getElementById('adjustmentType').value;
    const label = document.querySelector('label[for="adjustQuantity"]');
    if (label) {
        const required = label.querySelector('.required');
        label.textContent = `Quantity ${type === 'increase' ? '(+)' : '(-)'} `;
        if (required) label.appendChild(required);
    }
}

async function adjustStock(event) {
    event.preventDefault();
    
    const productId = document.getElementById('adjustProductSelect').value;
    const adjustmentType = document.getElementById('adjustmentType').value;
    const quantity = document.getElementById('adjustQuantity').value;
    const reason = document.getElementById('adjustReason').value;
    
    if (!productId) {
        showNotification('Please select a product', 'error');
        return;
    }
    
    if (!quantity || quantity <= 0) {
        showNotification('Please enter a valid quantity', 'error');
        return;
    }
    
    if (!reason.trim()) {
        showNotification('Please provide a reason for adjustment', 'error');
        return;
    }
    
    const adjustmentData = {
        action: 'adjust',
        product_id: parseInt(productId),
        adjustment_type: adjustmentType,
        quantity: parseInt(quantity),
        batch_number: document.getElementById('adjustBatchNumber').value || null,
        reason: reason.trim()
    };
    
    console.log('Sending stock adjustment data:', adjustmentData);
    
    try {
        const response = await fetchAPI('../../api/admin_incharge/inventory.php', {
            method: 'POST',
            body: adjustmentData
        });
        
        if (response.success) {
            showNotification('Stock adjusted successfully', 'success');
            closeModal('stockAdjustModal');
            if (typeof loadInventory === 'function') loadInventory();
            if (typeof loadDashboardStats === 'function') loadDashboardStats();
        }
    } catch (error) {
        console.error('Failed to adjust stock:', error);
        showNotification('Failed to adjust stock', 'error');
    }
}

// Modal Utility Functions
function closeModal(modalId) {
    console.log('Closing modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    } else {
        console.warn('closeModal: element not found for id', modalId);
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
    
    // Close modals when clicking on the modal background
    if (event.target.id === 'viewGRNModal') {
        closeViewGRNModal();
    }
    if (event.target.id === 'addStockModal') {
        closeAddStockModal();
    }
    if (event.target.id === 'createGRNModal') {
        closeGRNModal();
    }
    if (event.target.id === 'quickAddProductModal') {
        closeQuickAddProductModal();
    }
    if (event.target.id === 'createSupplierModal') {
        closeCreateSupplierModal();
    }
}

// Attach form submissions when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Attaching form event listeners...');
    
    const grnForm = document.getElementById('createGRNForm');
    if (grnForm) {
        grnForm.addEventListener('submit', createGRN);
        console.log('GRN form listener attached');
    } else {
        console.log('GRN form not found');
    }
    
    // Supplier form uses onsubmit="handleCreateSupplier(event)" in HTML
    // No need to attach listener here to avoid duplicate submissions
    
    const stockForm = document.getElementById('stockAdjustForm');
    if (stockForm) {
        stockForm.addEventListener('submit', adjustStock);
        console.log('Stock form listener attached');
    } else {
        console.log('Stock form not found');
    }
});
