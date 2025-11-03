<?php
require_once '../../config/config.php';

// Check if user is logged in and is a Moderator
requireRole(['Moderator']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$owner_id = getOwnerId();

// Fetch dashboard statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered
    FROM bookings WHERE owner_id = ?
";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get customer count
$customer_query = "SELECT COUNT(*) as customer_count FROM customers WHERE owner_id = ? AND status = 'Active'";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$customer_stats = $stmt->get_result()->fetch_assoc();

// Get pending reminders count
$reminder_query = "
    SELECT COUNT(*) as pending_reminders 
    FROM booking_reminders br
    JOIN bookings b ON br.booking_id = b.id
    WHERE b.owner_id = ? AND br.status = 'Pending' AND br.reminder_date <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
";
$stmt = $conn->prepare($reminder_query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$reminder_stats = $stmt->get_result()->fetch_assoc();

// Get low stock products count
$low_stock_query = "
    SELECT COUNT(*) as low_stock_count 
    FROM products 
    WHERE owner_id = ? AND status = 'Active' AND stock_quantity <= low_stock_threshold
";
$stmt = $conn->prepare($low_stock_query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$low_stock_stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Dashboard - TrackIt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="css/moderator.css">
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
                <i class="fas fa-clipboard-list"></i>
                <span>Bookings</span>
            </a>
            <a href="#" class="nav-item" data-page="customers">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </a>
            <a href="#" class="nav-item" data-page="products">
                <i class="fas fa-box"></i>
                <span>Products</span>
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
                    <input type="text" id="globalSearch" placeholder="Search bookings, customers...">
                </div>
            </div>
            <div class="header-right">
                <button class="notification-btn" onclick="showReminders()">
                    <i class="fas fa-bell"></i>
                    <?php if (($reminder_stats['pending_reminders'] ?? 0) > 0): ?>
                    <span class="notification-badge"><?php echo $reminder_stats['pending_reminders']; ?></span>
                    <?php endif; ?>
                </button>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="user-role">Moderator</div>
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
                    <h1 class="page-title">Moderator Dashboard</h1>
                    <p class="page-subtitle">Manage customer bookings and inquiries</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                        </div>
                        <div class="stat-title">Total Bookings</div>
                        <div class="stat-value"><?php echo $stats['total_bookings'] ?? 0; ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-title">Confirmed</div>
                        <div class="stat-value"><?php echo $stats['confirmed'] ?? 0; ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon orange">
                                <i class="fas fa-clock"></i>
                            </div>
                            <?php if (($reminder_stats['pending_reminders'] ?? 0) > 0): ?>
                            <span class="stat-badge"><?php echo $reminder_stats['pending_reminders']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="stat-title">Pending</div>
                        <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-title">Customers</div>
                        <div class="stat-value"><?php echo $customer_stats['customer_count'] ?? 0; ?></div>
                    </div>
                </div>
                
                <!-- Alerts Section -->
                <?php if (($low_stock_stats['low_stock_count'] ?? 0) > 0 || ($reminder_stats['pending_reminders'] ?? 0) > 0): ?>
                <div class="alerts-section">
                    <?php if (($low_stock_stats['low_stock_count'] ?? 0) > 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><strong><?php echo $low_stock_stats['low_stock_count']; ?></strong> products are low on stock or out of stock</span>
                        <button onclick="showPage('products')" class="alert-btn">View Products</button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (($reminder_stats['pending_reminders'] ?? 0) > 0): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-bell"></i>
                        <span><strong><?php echo $reminder_stats['pending_reminders']; ?></strong> booking reminders are pending</span>
                        <button onclick="showReminders()" class="alert-btn">View Reminders</button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-cards">
                        <div class="action-card" onclick="showPage('bookings')">
                            <div class="action-icon blue">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="action-title">New Booking</div>
                            <div class="action-desc">Create a new customer booking</div>
                        </div>
                        <div class="action-card" onclick="showPage('customers')">
                            <div class="action-icon green">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="action-title">Add Customer</div>
                            <div class="action-desc">Register new customer</div>
                        </div>
                        <div class="action-card" onclick="showPage('products')">
                            <div class="action-icon purple">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="action-title">Check Stock</div>
                            <div class="action-desc">View product availability</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bookings Page -->
            <div id="bookingsPage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Booking Management</h1>
                        <p class="page-subtitle">Manage customer bookings and requests</p>
                    </div>
                    <button class="btn btn-primary" onclick="openBookingModal()">
                        <i class="fas fa-plus"></i>
                        New Booking
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="filters-bar">
                    <div class="filter-group">
                        <select id="bookingStatusFilter" onchange="filterBookings()">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Processing">Processing</option>
                            <option value="Ready">Ready</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <input type="text" id="bookingSearch" placeholder="Search bookings..." onkeyup="searchBookings()">
                    </div>
                    <div class="filter-actions">
                        <button class="btn btn-secondary" onclick="exportBookings()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table id="bookingsTable">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="bookingsTableBody">
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #3b82f6;"></i>
                                    <p style="margin-top: 10px; color: #64748b;">Loading bookings...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="bookingsPagination" class="pagination"></div>
            </div>

            <!-- Customers Page -->
            <div id="customersPage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Customer Management</h1>
                        <p class="page-subtitle">View and manage customer information</p>
                    </div>
                    <button class="btn btn-primary" onclick="openCustomerModal()">
                        <i class="fas fa-user-plus"></i>
                        Add Customer
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="filters-bar">
                    <div class="filter-group">
                        <select id="customerStatusFilter" onchange="filterCustomers()">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <input type="text" id="customerSearch" placeholder="Search customers..." onkeyup="searchCustomers()">
                    </div>
                    <div class="filter-actions">
                        <button class="btn btn-secondary" onclick="exportCustomers()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table id="customersTable">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Total Orders</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="customersTableBody">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #3b82f6;"></i>
                                    <p style="margin-top: 10px; color: #64748b;">Loading customers...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="customersPagination" class="pagination"></div>
            </div>
            
            <!-- Products Page -->
            <div id="productsPage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Product Availability</h1>
                        <p class="page-subtitle">Check product stock and availability</p>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filters-bar">
                    <div class="filter-group">
                        <select id="productStatusFilter" onchange="filterProducts()">
                            <option value="">All Products</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <input type="text" id="productSearch" placeholder="Search products..." onkeyup="searchProducts()">
                    </div>
                </div>

                <div class="table-container">
                    <table id="productsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Availability</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #3b82f6;"></i>
                                    <p style="margin-top: 10px; color: #64748b;">Loading products...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="productsPagination" class="pagination"></div>
            </div>
            
            <!-- Reports Page -->
            <div id="reportsPage" class="page-content" style="display: none;">
                <div class="page-header">
                    <h1 class="page-title">Reports & Reminders</h1>
                    <p class="page-subtitle">View booking reminders and generate reports</p>
                </div>
                
                <div class="reminders-section">
                    <h3>Pending Reminders</h3>
                    <div id="remindersList" class="reminders-list">
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #3b82f6;"></i>
                            <p style="margin-top: 10px; color: #64748b;">Loading reminders...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals will be dynamically created by JavaScript -->
    <div id="modalContainer"></div>

    <script src="../css/dashboard-common.js"></script>
    <script>
        // Pass PHP variables to JavaScript
        const OWNER_ID = <?php echo $owner_id; ?>;
        const USER_ID = <?php echo $user_id; ?>;
    </script>
    <script src="js/moderator.js"></script>
</body>
</html>
