<?php
require_once '../../config/config.php';

// Check if user is logged in and is a Moderator
requireRole(['Moderator']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$owner_id = getOwnerId();

// Moderator-specific queries go here
// For now, showing basic structure
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
                    <input type="text" placeholder="Search bookings, customers...">
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
                            <span class="stat-change positive">+15%</span>
                        </div>
                        <div class="stat-title">Total Bookings</div>
                        <div class="stat-value">156</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <span class="stat-change positive">+8%</span>
                        </div>
                        <div class="stat-title">Confirmed</div>
                        <div class="stat-value">124</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon orange">
                                <i class="fas fa-clock"></i>
                            </div>
                            <span class="stat-change">0%</span>
                        </div>
                        <div class="stat-title">Pending</div>
                        <div class="stat-value">18</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="fas fa-users"></i>
                            </div>
                            <span class="stat-change positive">+23%</span>
                        </div>
                        <div class="stat-title">Customers</div>
                        <div class="stat-value">89</div>
                    </div>
                </div>

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

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Status</th>
                                <th>Date</th>
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
                                <td><span class="badge badge-green">Confirmed</span></td>
                                <td style="color: #64748b;">Nov 3, 2025</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="action-btn" title="Edit"><i class="fas fa-edit"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#BK-002</strong></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-cell-avatar">SM</div>
                                        <div class="user-cell-info">
                                            <div class="user-cell-name">Sarah Miller</div>
                                            <div class="user-cell-email">sarah@example.com</div>
                                        </div>
                                    </div>
                                </td>
                                <td>Product B</td>
                                <td><span class="badge badge-orange">Pending</span></td>
                                <td style="color: #64748b;">Nov 2, 2025</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="action-btn" title="Edit"><i class="fas fa-edit"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Customers Page -->
            <div id="customersPage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Customer Management</h1>
                        <p class="page-subtitle">View and manage customer information</p>
                    </div>
                    <button class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Add Customer
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Total Orders</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-cell-avatar">JD</div>
                                        <div class="user-cell-info">
                                            <div class="user-cell-name">John Doe</div>
                                            <div class="user-cell-email">john@example.com</div>
                                        </div>
                                    </div>
                                </td>
                                <td>+880 1234567890</td>
                                <td>12</td>
                                <td><span class="badge badge-green">Active</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="action-btn" title="Edit"><i class="fas fa-edit"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="../css/dashboard-common.js"></script>
    <script src="js/moderator.js"></script>
</body>
</html>
