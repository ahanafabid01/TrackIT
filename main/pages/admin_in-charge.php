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
                        <h2 class="section-title">Inventory Management</h2>
                        <p class="section-subtitle">Track and manage product stock levels</p>
                    </div>
                    <div class="section-actions">
                        <button class="btn btn-secondary btn-sm" onclick="alert('View Alerts feature coming soon!')">
                            <i class="fas fa-exclamation-triangle"></i>
                            View Alerts
                        </button>
                        <button class="btn btn-info" onclick="alert('Add New Product feature coming soon!')">
                            <i class="fas fa-box"></i>
                            Add New Product
                        </button>
                        <button class="btn btn-primary" onclick="showAddStockModal()">
                            <i class="fas fa-plus"></i>
                            Add Stock
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Stock</th>
                                <th>Batches</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="inventoryTableBody">
                            <tr>
                                <td colspan="6" style="text-align: center;">Loading inventory...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- GRN Page -->
            <div id="grnPage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Goods Received Notes (GRN)</h1>
                        <p class="page-subtitle">Manage incoming goods from suppliers</p>
                    </div>
                    <button class="btn btn-primary" onclick="showCreateGRNModal()">
                        <i class="fas fa-plus"></i>
                        Create GRN
                    </button>
                </div>

                <!-- GRN Filters -->
                <div class="filters-container">
                    <div class="filter-group">
                        <label><i class="fas fa-filter"></i> Status</label>
                        <select id="grnStatusFilter" onchange="filterGRNs()">
                            <option value="">All Status</option>
                            <option value="Draft">Draft</option>
                            <option value="Verified">Verified</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> Date</label>
                        <input type="date" id="grnDateFilter" onchange="filterGRNs()">
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="clearGRNFilters()">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>GRN Number</th>
                                <th>Supplier</th>
                                <th>Received Date</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="grnTableBody">
                            <tr>
                                <td colspan="7" style="text-align: center;">Loading GRNs...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Suppliers Page -->
            <div id="suppliersPage" class="page-content" style="display: none;">
                <div class="section-header">
                    <div>
                        <h1 class="page-title">Supplier Management</h1>
                        <p class="page-subtitle">Manage supplier relationships and performance</p>
                    </div>
                    <button class="btn btn-primary" onclick="showCreateSupplierModal()">
                        <i class="fas fa-user-plus"></i>
                        Add Supplier
                    </button>
                </div>

                <!-- Supplier Filters -->
                <div class="filters-container">
                    <div class="filter-group">
                        <label><i class="fas fa-filter"></i> Status</label>
                        <select id="supplierStatusFilter" onchange="filterSuppliers()">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Blocked">Blocked</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Search</label>
                        <input type="text" id="supplierSearchFilter" oninput="filterSuppliers()" placeholder="Search by name, code, contact...">
                    </div>
                    <button class="btn btn-secondary btn-sm" onclick="clearSupplierFilters()">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Supplier Code</th>
                                <th>Company Name</th>
                                <th>Contact</th>
                                <th>Payment Terms</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="suppliersTableBody">
                            <tr>
                                <td colspan="7" style="text-align: center;">Loading suppliers...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Stock Modal -->
    <div id="addStockModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 15px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 25px; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #1e293b;">
                    <i class="fas fa-plus" style="margin-right: 10px; color: #3b82f6;"></i>
                    Add Stock
                </h3>
                <span onclick="closeAddStockModal()" style="font-size: 28px; font-weight: bold; color: #9ca3af; cursor: pointer; line-height: 1;">&times;</span>
            </div>
            <form id="addStockForm" style="padding: 25px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Product <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="addStockProduct" required style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Quantity to Add <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="addStockQuantity" min="1" required placeholder="Enter quantity" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Unit Cost (BDT) <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="addStockUnitCost" min="0" step="0.01" required placeholder="Cost per unit" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Batch Number
                        </label>
                        <div style="position: relative;">
                            <input type="text" id="addStockBatch" placeholder="Auto-generated (or enter custom)" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                            <button type="button" onclick="generateBatchNumber()" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">
                                <i class="fas fa-sync-alt"></i> Generate
                            </button>
                        </div>
                        <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">Leave empty for auto-generation or click Generate</small>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Warehouse Location
                        </label>
                        <input type="text" id="addStockLocation" placeholder="e.g., A-12-03" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Manufacturing Date
                        </label>
                        <input type="date" id="addStockMfgDate" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Expiry Date
                        </label>
                        <input type="date" id="addStockExpiryDate" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Reason <span style="color: #ef4444;">*</span>
                        </label>
                        <textarea id="addStockReason" rows="3" required placeholder="Reason for adding stock (e.g., New purchase, GRN receipt, Restocking)" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; resize: vertical; box-sizing: border-box;"></textarea>
                    </div>
                </div>

                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: start; gap: 10px;">
                        <i class="fas fa-info-circle" style="color: #3b82f6; margin-top: 2px;"></i>
                        <div style="flex: 1;">
                            <strong style="color: #1e40af; font-size: 14px;">What is a Batch Number?</strong>
                            <p style="color: #1e40af; font-size: 13px; margin: 5px 0 0 0; line-height: 1.5;">
                                A batch number uniquely identifies a group of products received together. It helps track when items arrived, their cost, and expiry dates. 
                                <strong>Example:</strong> B25-LS-001 (Year-Product-Sequence). You can let the system auto-generate it or create your own.
                            </p>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 15px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                    <button type="button" onclick="closeAddStockModal()" style="padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; background: #6b7280; color: white;">
                        Cancel
                    </button>
                    <button type="submit" style="padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; background: #3b82f6; color: white;">
                        <i class="fas fa-plus"></i> Add Stock
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create GRN Modal -->
    <div id="createGRNModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; align-items: center; justify-content: center; overflow-y: auto;">
        <div style="background: white; border-radius: 15px; width: 95%; max-width: 900px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); margin: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 25px; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; background: white; z-index: 1; border-radius: 15px 15px 0 0;">
                <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #1e293b;">
                    <i class="fas fa-truck-loading" style="margin-right: 10px; color: #3b82f6;"></i>
                    Create Goods Received Note (GRN)
                </h3>
                <span onclick="closeGRNModal()" style="font-size: 28px; font-weight: bold; color: #9ca3af; cursor: pointer; line-height: 1;">&times;</span>
            </div>
            
            <form id="createGRNForm" style="padding: 25px;">
                <!-- Supplier & Invoice Info -->
                <div style="background: #f8fafc; border-radius: 10px; padding: 20px; margin-bottom: 25px;">
                    <h4 style="margin: 0 0 20px 0; color: #1e293b; font-size: 16px;">
                        <i class="fas fa-info-circle" style="color: #3b82f6;"></i> Supplier & Invoice Details
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="grid-column: 1 / -1;">
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Supplier <span style="color: #ef4444;">*</span>
                            </label>
                            <select id="grnSupplier" required style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                                <option value="">Select Supplier</option>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Invoice Number <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" id="grnInvoiceNumber" required placeholder="INV-2025-001" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Invoice Date <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="date" id="grnInvoiceDate" required style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Received Date <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="date" id="grnReceivedDate" required style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Purchase Order Number
                            </label>
                            <input type="text" id="grnPONumber" placeholder="Optional" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <div style="background: #f8fafc; border-radius: 10px; padding: 20px; margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 style="margin: 0; color: #1e293b; font-size: 16px;">
                            <i class="fas fa-boxes" style="color: #3b82f6;"></i> Items Received
                        </h4>
                        <div style="display: flex; gap: 10px;">
                            <button type="button" onclick="showQuickAddProductModal()" style="padding: 8px 16px; background: #8b5cf6; color: white; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                <i class="fas fa-plus-circle"></i> New Product
                            </button>
                            <button type="button" onclick="addGRNItem()" style="padding: 8px 16px; background: #10b981; color: white; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                    </div>
                    
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px; margin-bottom: 15px;">
                        <div style="display: flex; align-items: start; gap: 10px;">
                            <i class="fas fa-lightbulb" style="color: #3b82f6; margin-top: 2px;"></i>
                            <div style="flex: 1;">
                                <p style="color: #1e40af; font-size: 13px; margin: 0; line-height: 1.5;">
                                    <strong>Don't have the product in your store?</strong> Click "New Product" to quickly add it to your inventory, then it will appear in the dropdown for selection.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="grnItemsContainer">
                        <!-- Items will be added here dynamically -->
                    </div>
                </div>

                <!-- Financial Summary -->
                <div style="background: #f8fafc; border-radius: 10px; padding: 20px; margin-bottom: 25px;">
                    <h4 style="margin: 0 0 20px 0; color: #1e293b; font-size: 16px;">
                        <i class="fas fa-calculator" style="color: #3b82f6;"></i> Financial Summary
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Tax/VAT (%)
                            </label>
                            <input type="number" id="grnTaxPercent" min="0" max="100" step="0.01" value="15" placeholder="15" onchange="calculateGRNTotal()" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Discount Amount
                            </label>
                            <input type="number" id="grnDiscount" min="0" step="0.01" value="0" placeholder="0" onchange="calculateGRNTotal()" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 20px; background: white; border-radius: 8px; border: 2px solid #e5e7eb;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #64748b; font-size: 14px;">
                            <span>Subtotal:</span>
                            <span id="grnSubtotal" style="font-weight: 600;">0.00 BDT</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #64748b; font-size: 14px;">
                            <span>Tax:</span>
                            <span id="grnTaxAmount" style="font-weight: 600;">0.00 BDT</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #64748b; font-size: 14px;">
                            <span>Discount:</span>
                            <span id="grnDiscountAmount" style="font-weight: 600; color: #10b981;">- 0.00 BDT</span>
                        </div>
                        <div style="height: 1px; background: #e5e7eb; margin: 15px 0;"></div>
                        <div style="display: flex; justify-content: space-between; color: #1e293b; font-size: 18px; font-weight: 700;">
                            <span>Net Total:</span>
                            <span id="grnNetTotal" style="color: #3b82f6;">0.00 BDT</span>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div style="background: #f8fafc; border-radius: 10px; padding: 20px; margin-bottom: 25px;">
                    <h4 style="margin: 0 0 20px 0; color: #1e293b; font-size: 16px;">
                        <i class="fas fa-sticky-note" style="color: #3b82f6;"></i> Additional Information
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Payment Status
                            </label>
                            <select id="grnPaymentStatus" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                                <option value="Pending">Pending</option>
                                <option value="Paid">Paid</option>
                                <option value="Partial">Partial</option>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Warehouse Location
                            </label>
                            <input type="text" id="grnWarehouseLocation" placeholder="e.g., Warehouse A" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>
                        
                        <div style="grid-column: 1 / -1;">
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Notes
                            </label>
                            <textarea id="grnNotes" rows="3" placeholder="Any additional notes about this delivery..." style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; resize: vertical; box-sizing: border-box;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; justify-content: flex-end; gap: 15px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                    <button type="button" onclick="closeGRNModal()" style="padding: 12px 24px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; background: white; color: #64748b;">
                        Cancel
                    </button>
                    <button type="button" onclick="saveDraftGRN()" style="padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; background: #64748b; color: white;">
                        <i class="fas fa-save"></i> Save as Draft
                    </button>
                    <button type="submit" style="padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; background: #3b82f6; color: white;">
                        <i class="fas fa-check"></i> Create GRN
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Product Modal -->
    <div id="quickAddProductModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center;">
        <div style="background: white; border-radius: 15px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); padding: 25px; border-radius: 15px 15px 0 0; position: sticky; top: 0; z-index: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="color: white; margin: 0; font-size: 20px; font-weight: 700;">
                        <i class="fas fa-box-open"></i> Quick Add Product
                    </h3>
                    <button onclick="closeQuickAddProductModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.3s;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 13px;">Add a new product to use in your GRN</p>
            </div>

            <!-- Form -->
            <form id="quickAddProductForm" onsubmit="handleQuickAddProduct(event)" style="padding: 30px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div style="grid-column: span 2;">
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Product Name <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="quickProductName" required placeholder="Enter product name" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            SKU <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="quickProductSKU" required placeholder="Auto-generated" readonly style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; background: #f8fafc; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Category <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="quickProductCategory" required style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                            <option value="">Select Category</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Clothing">Clothing</option>
                            <option value="Food">Food & Beverages</option>
                            <option value="Books">Books</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Sports">Sports</option>
                            <option value="Toys">Toys</option>
                            <option value="Health">Health & Beauty</option>
                            <option value="Automotive">Automotive</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Unit <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="quickProductUnit" required style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                            <option value="">Select Unit</option>
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="kg">Kilograms (kg)</option>
                            <option value="ltr">Liters (ltr)</option>
                            <option value="box">Box</option>
                            <option value="pack">Pack</option>
                            <option value="set">Set</option>
                            <option value="dozen">Dozen</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Cost Price <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="quickProductCost" required step="0.01" min="0" placeholder="0.00" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Selling Price <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="quickProductPrice" required step="0.01" min="0" placeholder="0.00" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                    </div>

                    <div style="grid-column: span 2;">
                        <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                            Description
                        </label>
                        <textarea id="quickProductDescription" rows="3" placeholder="Optional product description" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; resize: vertical; box-sizing: border-box;"></textarea>
                    </div>
                </div>

                <!-- Info Box -->
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: start; gap: 10px;">
                        <i class="fas fa-info-circle" style="color: #16a34a; margin-top: 2px;"></i>
                        <p style="color: #166534; font-size: 13px; margin: 0; line-height: 1.5;">
                            SKU will be auto-generated. After adding this product, it will appear in your GRN item dropdown.
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closeQuickAddProductModal()" style="padding: 12px 24px; background: #f1f5f9; color: #64748b; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="padding: 12px 24px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-check"></i> Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Supplier Modal -->
    <div id="createSupplierModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center; overflow-y: auto;">
        <div style="background: white; border-radius: 15px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); margin: 20px;">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); padding: 25px; border-radius: 15px 15px 0 0; position: sticky; top: 0; z-index: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="color: white; margin: 0; font-size: 20px; font-weight: 700;">
                        <i class="fas fa-handshake"></i> Create New Supplier
                    </h3>
                    <button onclick="closeCreateSupplierModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.3s;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 13px;">Add a new supplier to your network</p>
            </div>

            <!-- Form -->
            <form id="createSupplierForm" onsubmit="handleCreateSupplier(event)" style="padding: 30px;">
                <!-- Company Information -->
                <div style="margin-bottom: 25px;">
                    <h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 16px; font-weight: 700; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">
                        <i class="fas fa-building"></i> Company Information
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Company Name <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" id="supplierCompanyName" required placeholder="Enter company name" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Supplier Code
                            </label>
                            <input type="text" id="supplierCode" placeholder="Auto-generated" readonly style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; background: #f8fafc; box-sizing: border-box; color: #6b7280;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Status
                            </label>
                            <select id="supplierStatus" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div style="margin-bottom: 25px;">
                    <h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 16px; font-weight: 700; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">
                        <i class="fas fa-user"></i> Contact Information
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Contact Person <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" id="supplierContactPerson" required placeholder="Mr./Ms. Name" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Email <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="email" id="supplierEmail" required placeholder="email@company.com" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Phone <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="tel" id="supplierPhone" required placeholder="+880 1XXXXXXXXX" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Tax ID / TIN
                            </label>
                            <input type="text" id="supplierTaxId" placeholder="Optional" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div style="margin-bottom: 25px;">
                    <h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 16px; font-weight: 700; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">
                        <i class="fas fa-map-marker-alt"></i> Address Information
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="grid-column: span 2;">
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Address
                            </label>
                            <textarea id="supplierAddress" rows="2" placeholder="Street address, building, floor" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; resize: vertical; box-sizing: border-box;"></textarea>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                City
                            </label>
                            <input type="text" id="supplierCity" placeholder="e.g., Dhaka" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                State/Division
                            </label>
                            <input type="text" id="supplierState" placeholder="e.g., Dhaka Division" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Country
                            </label>
                            <input type="text" id="supplierCountry" value="Bangladesh" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Postal Code
                            </label>
                            <input type="text" id="supplierPostalCode" placeholder="e.g., 1200" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>
                    </div>
                </div>

                <!-- Payment Terms -->
                <div style="margin-bottom: 25px;">
                    <h4 style="margin: 0 0 15px 0; color: #1e293b; font-size: 16px; font-weight: 700; border-bottom: 2px solid #f59e0b; padding-bottom: 8px;">
                        <i class="fas fa-money-bill-wave"></i> Payment Terms
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Payment Terms
                            </label>
                            <select id="supplierPaymentTerms" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                                <option value="Cash">Cash on Delivery</option>
                                <option value="Net 15">Net 15 Days</option>
                                <option value="Net 30" selected>Net 30 Days</option>
                                <option value="Net 60">Net 60 Days</option>
                                <option value="Net 90">Net 90 Days</option>
                                <option value="Custom">Custom Terms</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                                Credit Limit (BDT)
                            </label>
                            <input type="number" id="supplierCreditLimit" min="0" step="0.01" value="0" placeholder="0.00" style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        </div>
                    </div>
                </div>

                <!-- Additional Notes -->
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px;">
                        <i class="fas fa-sticky-note"></i> Notes
                    </label>
                    <textarea id="supplierNotes" rows="3" placeholder="Any additional information about the supplier..." style="width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; resize: vertical; box-sizing: border-box;"></textarea>
                </div>

                <!-- Info Box -->
                <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 12px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: start; gap: 10px;">
                        <i class="fas fa-info-circle" style="color: #f59e0b; margin-top: 2px;"></i>
                        <p style="color: #92400e; font-size: 13px; margin: 0; line-height: 1.5;">
                            Supplier code will be auto-generated. All suppliers start with "Active" status and can be managed later.
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="closeCreateSupplierModal()" style="padding: 12px 24px; background: #f1f5f9; color: #64748b; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="padding: 12px 24px; background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-check"></i> Create Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/dashboard-common.js"></script>
    <script src="js/admin_in-charge.js"></script>
</body>
</html>
