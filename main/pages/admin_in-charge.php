<?php
require_once '../../config/config.php';

// Check if user is logged in and is an Admin In-charge
requireRole(['Admin In-charge']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$owner_id = getOwnerId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin In-charge Dashboard - TrackIt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="css/admin_in-charge.css">
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
            <a href="#" class="nav-item" data-page="inventory">
                <i class="fas fa-boxes"></i>
                <span>Inventory</span>
            </a>
            <a href="#" class="nav-item" data-page="grn">
                <i class="fas fa-truck-loading"></i>
                <span>GRN</span>
            </a>
            <a href="#" class="nav-item" data-page="suppliers">
                <i class="fas fa-handshake"></i>
                <span>Suppliers</span>
            </a>
            <a href="#" class="nav-item" data-page="returns">
                <i class="fas fa-undo-alt"></i>
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
                    <input type="text" placeholder="Search inventory, products...">
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
                        <div class="user-role">Admin In-charge</div>
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
                    <h1 class="page-title">Admin In-charge Dashboard</h1>
                    <p class="page-subtitle">Manage inventory, GRN, and supplier relations</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <span class="stat-change positive">+5%</span>
                        </div>
                        <div class="stat-title">Total Inventory</div>
                        <div class="stat-value">1,429</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue">
                                <i class="fas fa-truck-loading"></i>
                            </div>
                            <span class="stat-change positive">+12%</span>
                        </div>
                        <div class="stat-title">GRN This Month</div>
                        <div class="stat-value">67</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon orange">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <span class="stat-change negative">-8%</span>
                        </div>
                        <div class="stat-title">Low Stock Items</div>
                        <div class="stat-value">23</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <span class="stat-change positive">+3</span>
                        </div>
                        <div class="stat-title">Active Suppliers</div>
                        <div class="stat-value">34</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-cards">
                        <div class="action-card" onclick="showPage('grn')">
                            <div class="action-icon blue">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="action-title">Create GRN</div>
                            <div class="action-desc">Add goods received note</div>
                        </div>
                        <div class="action-card" onclick="showPage('inventory')">
                            <div class="action-icon purple">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="action-title">Update Stock</div>
                            <div class="action-desc">Adjust inventory levels</div>
                        </div>
                        <div class="action-card" onclick="showPage('suppliers')">
                            <div class="action-icon green">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="action-title">Add Supplier</div>
                            <div class="action-desc">Register new supplier</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Page -->
            <div id="inventoryPage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Inventory Management</h1>
                        <p class="page-subtitle">Track and manage product stock levels</p>
                    </div>
                    <button class="btn btn-primary">
                        <i class="fas fa-barcode"></i>
                        Generate Barcode
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Stock</th>
                                <th>Batch No</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>PROD-001</strong></td>
                                <td>Product A</td>
                                <td><strong>150 units</strong></td>
                                <td>B24-001</td>
                                <td><span class="badge badge-green">In Stock</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="action-btn" title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="action-btn" title="Barcode"><i class="fas fa-barcode"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>PROD-002</strong></td>
                                <td>Product B</td>
                                <td><strong style="color: var(--error-color);">8 units</strong></td>
                                <td>B24-002</td>
                                <td><span class="badge badge-orange">Low Stock</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="action-btn" title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="action-btn" title="Barcode"><i class="fas fa-barcode"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/dashboard-common.js"></script>
    <script src="js/admin_in-charge.js"></script>
</body>
</html>
