<?php
require_once '../../config/config.php';

// Check if user is logged in and is a Store In-charge
requireRole(['Store In-charge']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$owner_id = getOwnerId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store In-charge Dashboard - TrackIt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="css/store_in-charge.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon">YC</div>
                <div class="logo-text">TRACKIT</div>
            </div>
        </div>
        <nav class="nav-menu">
            <a href="#" class="nav-item active" data-page="dashboard">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item" data-page="bookings">
                <i class="fas fa-clipboard-check"></i>
                <span>Booking Requests</span>
            </a>
            <a href="#" class="nav-item" data-page="delivery">
                <i class="fas fa-shipping-fast"></i>
                <span>Delivery</span>
            </a>
            <a href="#" class="nav-item" data-page="barcode">
                <i class="fas fa-barcode"></i>
                <span>Barcode Scanner</span>
            </a>
            <a href="#" class="nav-item" data-page="returns">
                <i class="fas fa-undo"></i>
                <span>Returns</span>
            </a>
            <a href="#" class="nav-item" data-page="reports">
                <i class="fas fa-file-alt"></i>
                <span>Reports</span>
            </a>
            <a href="../logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search deliveries, tracking...">
                </div>
            </div>
            <div class="header-right">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"></span>
                </button>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="user-role">Store In-charge</div>
                    </div>
                    <i class="fas fa-chevron-down" style="color: #94a3b8; font-size: 12px;"></i>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Dashboard Page -->
            <div id="dashboardPage" class="page-content">
                <div class="page-header">
                    <h1 class="page-title">Store In-charge Dashboard</h1>
                    <p class="page-subtitle">Manage inventory verification, delivery, and logistics</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <span class="stat-change positive">+10%</span>
                        </div>
                        <div class="stat-title">Pending Requests</div>
                        <div class="stat-value">42</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <span class="stat-change positive">+15%</span>
                        </div>
                        <div class="stat-title">Deliveries Today</div>
                        <div class="stat-value">28</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon orange">
                                <i class="fas fa-clock"></i>
                            </div>
                            <span class="stat-change">0%</span>
                        </div>
                        <div class="stat-title">In Transit</div>
                        <div class="stat-value">17</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="fas fa-undo"></i>
                            </div>
                            <span class="stat-change negative">-5%</span>
                        </div>
                        <div class="stat-title">Returns</div>
                        <div class="stat-value">5</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-cards">
                        <div class="action-card" onclick="showPage('bookings')">
                            <div class="action-icon blue">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="action-title">Verify Booking</div>
                            <div class="action-desc">Confirm booking requests</div>
                        </div>
                        <div class="action-card" onclick="showPage('delivery')">
                            <div class="action-icon green">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="action-title">Add Delivery</div>
                            <div class="action-desc">Create delivery record</div>
                        </div>
                        <div class="action-card" onclick="showPage('barcode')">
                            <div class="action-icon purple">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="action-title">Scan Barcode</div>
                            <div class="action-desc">Quick product lookup</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Requests Page -->
            <div id="bookingsPage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Booking Requests</h1>
                        <p class="page-subtitle">Verify and confirm booking requests</p>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary-color);"></i>
                                    <p style="margin-top: 10px; color: var(--text-secondary);">Loading booking requests...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Delivery Management Page -->
            <div id="deliveryPage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Delivery Management</h1>
                        <p class="page-subtitle">Create and track deliveries</p>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="showCreateDeliveryModal()">
                            <i class="fas fa-plus"></i>
                            Create Delivery
                        </button>
                        <button class="btn btn-secondary" onclick="refreshDeliveries()">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>

                <!-- Delivery Filters -->
                <div class="filters-container">
                    <select id="deliveryStatusFilter" onchange="filterDeliveries()">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Dispatched">Dispatched</option>
                        <option value="In Transit">In Transit</option>
                        <option value="Out for Delivery">Out for Delivery</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Failed">Failed</option>
                    </select>
                    <input type="date" id="deliveryDateFilter" onchange="filterDeliveries()" placeholder="Filter by date">
                    <input type="text" id="trackingSearchFilter" onchange="filterDeliveries()" placeholder="Search by tracking number">
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Tracking Number</th>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Courier</th>
                                <th>Dispatch Date</th>
                                <th>Expected Delivery</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="deliveriesTableBody">
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary-color);"></i>
                                    <p style="margin-top: 10px; color: var(--text-secondary);">Loading deliveries...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Returns Management Page -->
            <div id="returnsPage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Returns Management</h1>
                        <p class="page-subtitle">Process return requests and inspections</p>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="showCreateReturnModal()">
                            <i class="fas fa-plus"></i>
                            Create Return
                        </button>
                        <button class="btn btn-secondary" onclick="refreshReturns()">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>

                <!-- Return Filters -->
                <div class="filters-container">
                    <select id="returnStatusFilter" onchange="filterReturns()">
                        <option value="">All Status</option>
                        <option value="Pending">Pending Inspection</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Restocked">Restocked</option>
                        <option value="Refunded">Refunded</option>
                    </select>
                    <select id="returnReasonFilter" onchange="filterReturns()">
                        <option value="">All Reasons</option>
                        <option value="Defective">Defective</option>
                        <option value="Wrong Item">Wrong Item</option>
                        <option value="Damaged">Damaged</option>
                        <option value="Not as Described">Not as Described</option>
                        <option value="Customer Request">Customer Request</option>
                    </select>
                    <input type="date" id="returnDateFilter" onchange="filterReturns()" placeholder="Filter by return date">
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Return Number</th>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Reason</th>
                                <th>Quantity</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="returnsTableBody">
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary-color);"></i>
                                    <p style="margin-top: 10px; color: var(--text-secondary);">Loading returns...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Barcode Scanner Page -->
            <div id="barcodePage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Barcode Scanner</h1>
                        <p class="page-subtitle">Scan barcodes to retrieve product information</p>
                    </div>
                </div>

                <div class="barcode-scanner">
                    <div class="scanner-box">
                        <i class="fas fa-barcode" style="font-size: 64px; color: var(--primary-color);"></i>
                        <h3>Ready to Scan</h3>
                        <p>Enter barcode or use scanner device</p>
                        <input type="text" id="barcodeInput" class="barcode-input" placeholder="Scan or enter barcode..." autofocus>
                        <button class="btn btn-primary" onclick="scanBarcode()">
                            <i class="fas fa-search"></i>
                            Search
                        </button>
                    </div>
                    
                    <div id="barcodeResult" class="barcode-result" style="display: none;">
                        <h3>Product Information</h3>
                        <div class="product-info-grid">
                            <div class="info-item">
                                <span class="info-label">Product Name:</span>
                                <span class="info-value" id="productName">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Product ID:</span>
                                <span class="info-value" id="productId">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Stock:</span>
                                <span class="info-value" id="productStock">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status:</span>
                                <span class="info-value" id="productStatus">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Create Delivery Modal -->
    <div id="createDeliveryModal" class="modal" style="display: none;">
        <div class="modal-content delivery-modal">
            <div class="modal-header">
                <h3><i class="fas fa-truck"></i> Create New Delivery</h3>
                <span class="close" onclick="closeModal('createDeliveryModal')">&times;</span>
            </div>
            <form id="createDeliveryForm">
                <!-- Booking Selection -->
                <div class="form-section">
                    <h4 class="section-title">1. Select Booking</h4>
                    <div class="form-group">
                        <label>Choose Confirmed Booking *</label>
                        <select id="deliveryBookingSelect" required>
                            <option value="">Loading bookings...</option>
                        </select>
                        <small>Only confirmed/processing bookings are shown</small>
                    </div>
                    
                    <!-- Booking Details Display -->
                    <div id="selectedBookingDetails" class="booking-details" style="display: none;">
                        <!-- Auto-filled by JavaScript -->
                    </div>
                </div>

                <!-- Courier Information -->
                <div class="form-section">
                    <h4 class="section-title">2. Courier Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Courier Service *</label>
                            <select id="courierName" required>
                                <option value="">Select courier...</option>
                                <option value="Pathao">Pathao</option>
                                <option value="Steadfast">Steadfast</option>
                                <option value="RedX">RedX</option>
                                <option value="Sundarban">Sundarban</option>
                                <option value="SA Paribahan">SA Paribahan</option>
                                <option value="eCourier">eCourier</option>
                                <option value="Paperfly">Paperfly</option>
                                <option value="Own Delivery">Own Delivery</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tracking Number</label>
                            <input type="text" id="trackingNumber" placeholder="Auto-generated if empty">
                            <small>Leave empty to auto-generate</small>
                        </div>
                    </div>
                </div>

                <!-- Schedule Information -->
                <div class="form-section">
                    <h4 class="section-title">3. Schedule</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Dispatch Date *</label>
                            <input type="date" id="dispatchDate" required>
                            <small>Date when package will be dispatched</small>
                        </div>
                        <div class="form-group">
                            <label>Expected Delivery</label>
                            <input type="date" id="expectedDeliveryDate">
                            <small>Auto-calculated from dispatch date</small>
                        </div>
                    </div>
                </div>

                <!-- Delivery Details -->
                <div class="form-section">
                    <h4 class="section-title">4. Delivery Details</h4>
                    <div class="form-group">
                        <label>Delivery Address *</label>
                        <textarea id="deliveryAddress" rows="3" placeholder="Complete delivery address" required></textarea>
                        <small>Auto-filled from customer data</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Recipient Name *</label>
                            <input type="text" id="recipientName" placeholder="Recipient's full name" required>
                        </div>
                        <div class="form-group">
                            <label>Recipient Phone</label>
                            <input type="tel" id="recipientPhone" placeholder="e.g., +880 1700000000">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Special Instructions</label>
                        <textarea id="deliveryNotes" rows="3" placeholder="Special delivery instructions, preferred time, landmarks, etc."></textarea>
                    </div>
                </div>

                <!-- Priority Indicators -->
                <div class="delivery-priority-info" id="priorityInfo" style="display: none;">
                    <div class="priority-badge">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span id="priorityText">High Priority Delivery</span>
                    </div>
                </div>

                <!-- Delivery Summary Preview -->
                <div class="delivery-summary" id="deliverySummary" style="display: none;">
                    <h4 class="section-title">📦 Delivery Summary</h4>
                    <div class="summary-grid" id="summaryContent">
                        <!-- Auto-filled by JavaScript -->
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createDeliveryModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-info" onclick="previewDelivery()" id="previewBtn" style="display: none;">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-truck"></i> Create Delivery
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Return Modal -->
    <div id="createReturnModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create Return Request</h3>
                <span class="close" onclick="closeModal('createReturnModal')">&times;</span>
            </div>
            <form id="createReturnForm">
                <div class="form-group">
                    <label>Select Delivered Booking</label>
                    <select id="returnBookingSelect" required>
                        <option value="">Select a delivered booking...</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Return Reason</label>
                        <select id="returnReason" required>
                            <option value="">Select reason...</option>
                            <option value="Defective">Defective Product</option>
                            <option value="Wrong Item">Wrong Item Sent</option>
                            <option value="Damaged">Damaged in Transit</option>
                            <option value="Not as Described">Not as Described</option>
                            <option value="Customer Request">Customer Change of Mind</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Return Quantity</label>
                        <input type="number" id="returnQuantity" min="1" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Return Description</label>
                    <textarea id="returnDescription" rows="3" placeholder="Detailed description of the issue..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Customer Notification</label>
                    <select id="customerNotification">
                        <option value="email">Email Customer</option>
                        <option value="phone">Call Customer</option>
                        <option value="both">Email & Call</option>
                        <option value="none">No Notification</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Damage Evidence (Optional)</label>
                    <input type="file" id="damageEvidence" accept="image/*" multiple>
                    <small>Upload photos of damaged/defective items</small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createReturnModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Return</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Delivery Status Modal -->
    <div id="updateDeliveryModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Delivery Status</h3>
                <span class="close" onclick="closeModal('updateDeliveryModal')">&times;</span>
            </div>
            <form id="updateDeliveryForm">
                <input type="hidden" id="updateDeliveryId">
                <div class="form-group">
                    <label>New Status</label>
                    <select id="newDeliveryStatus" required>
                        <option value="Dispatched">Dispatched</option>
                        <option value="In Transit">In Transit</option>
                        <option value="Out for Delivery">Out for Delivery</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Failed">Failed Delivery</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Location (Optional)</label>
                    <input type="text" id="deliveryLocation" placeholder="Current location or last checkpoint">
                </div>
                <div class="form-group">
                    <label>Status Description</label>
                    <textarea id="statusDescription" rows="3" placeholder="Additional details about status change..." required></textarea>
                </div>
                <div class="form-group" id="proofOfDeliveryGroup" style="display: none;">
                    <label>Proof of Delivery</label>
                    <input type="file" id="proofOfDelivery" accept="image/*">
                    <small>Upload signed delivery slip or photo confirmation</small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('updateDeliveryModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/dashboard-common.js"></script>
    <script src="js/store_in-charge.js"></script>
</body>
</html>
