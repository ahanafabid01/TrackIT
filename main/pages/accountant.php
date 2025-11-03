<?php
require_once '../../config/config.php';

// Check if user is logged in and is an Accountant
requireRole(['Accountant']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$owner_id = getOwnerId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Dashboard - TrackIt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="css/accountant.css">
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
            <a href="#" class="nav-item" data-page="payments">
                <i class="fas fa-dollar-sign"></i>
                <span>Payments</span>
            </a>
            <a href="#" class="nav-item" data-page="refunds">
                <i class="fas fa-undo"></i>
                <span>Refunds</span>
            </a>
            <a href="#" class="nav-item" data-page="ledger">
                <i class="fas fa-book"></i>
                <span>Ledger</span>
            </a>
            <a href="#" class="nav-item" data-page="invoices">
                <i class="fas fa-file-invoice"></i>
                <span>Invoices</span>
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
                    <input type="text" placeholder="Search transactions, invoices...">
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
                        <div class="user-role">Accountant</div>
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
                    <h1 class="page-title">Accountant Dashboard</h1>
                    <p class="page-subtitle">Manage payments, refunds, and financial records</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <span class="stat-change positive">+12%</span>
                        </div>
                        <div class="stat-title">Total Revenue</div>
                        <div class="stat-value">BDT 54,239</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <span class="stat-change positive">+8%</span>
                        </div>
                        <div class="stat-title">Payments Received</div>
                        <div class="stat-value">342</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon orange">
                                <i class="fas fa-undo"></i>
                            </div>
                            <span class="stat-change negative">-3%</span>
                        </div>
                        <div class="stat-title">Refunds</div>
                        <div class="stat-value">BDT 2,140</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="fas fa-clock"></i>
                            </div>
                            <span class="stat-change">0%</span>
                        </div>
                        <div class="stat-title">Pending</div>
                        <div class="stat-value">18</div>
                    </div>
                </div>

                <!-- Revenue Chart Placeholder -->
                <div class="card">
                    <h3>Revenue Overview</h3>
                    <div class="chart-placeholder">
                        <i class="fas fa-chart-area" style="font-size: 48px; color: var(--text-secondary);"></i>
                        <p>Chart visualization coming soon</p>
                    </div>
                </div>
            </div>

            <!-- Payments Page -->
            <div id="paymentsPage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Payment Records</h1>
                        <p class="page-subtitle">Track all payment transactions</p>
                    </div>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Record Payment
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#TXN-001</strong></td>
                                <td>John Doe</td>
                                <td><strong>BDT 5,000</strong></td>
                                <td>Credit Card</td>
                                <td><span class="badge badge-green">Completed</span></td>
                                <td style="color: #64748b;">Nov 3, 2025</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="action-btn" title="Invoice"><i class="fas fa-file-invoice"></i></button>
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
    <script src="js/accountant.js"></script>
</body>
</html>
