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
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#BK-001</strong></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-cell-avatar">JD</div>
                                        <div class="user-cell-info">
                                            <div class="user-cell-name">John Doe</div>
                                            <div class="user-cell-email">john@example.com</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Product A</td>
                                <td><strong>5 units</strong></td>
                                <td><span class="badge badge-orange">Pending Verification</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn" title="Confirm" style="background: var(--success-color); color: white;">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="action-btn" title="Reject" style="background: var(--error-color); color: white;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
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

    <script src="../js/dashboard-common.js"></script>
    <script src="js/store_in-charge.js"></script>
</body>
</html>
