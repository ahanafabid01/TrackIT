-- ============================================================================
-- Admin In-Charge Tables for TrackIt System
-- Created: November 6, 2025
-- Purpose: Support GRN, Supplier Management, Inventory Forecasting, 
--          Batch/Expiry Tracking, Discounts, and Audit Logs
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================================
-- 1. SUPPLIERS TABLE
-- Manage supplier details, payment terms, and history
-- ============================================================================

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL COMMENT 'References the owner user_id',
  `supplier_code` varchar(50) NOT NULL COMMENT 'Unique supplier code like SUP-001',
  `company_name` varchar(200) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Bangladesh',
  `postal_code` varchar(20) DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL COMMENT 'VAT/TIN number',
  `payment_terms` enum('Cash','Net 15','Net 30','Net 60','Net 90','Custom') DEFAULT 'Net 30',
  `credit_limit` decimal(12,2) DEFAULT 0.00,
  `current_balance` decimal(12,2) DEFAULT 0.00 COMMENT 'Outstanding amount owed',
  `rating` decimal(3,2) DEFAULT NULL COMMENT 'Supplier rating out of 5.00',
  `status` enum('Active','Inactive','Blocked') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL COMMENT 'Admin In-charge who added supplier',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_code` (`supplier_code`,`owner_id`),
  KEY `owner_id` (`owner_id`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample Data
INSERT INTO `suppliers` (`owner_id`, `supplier_code`, `company_name`, `contact_person`, `email`, `phone`, `address`, `city`, `state`, `payment_terms`, `credit_limit`, `rating`, `status`, `created_by`) VALUES
(1, 'SUP-001', 'Tech World Ltd', 'Mr. Karim Ahmed', 'karim@techworld.com', '+880 1712345678', 'House 45, Road 12, Gulshan', 'Dhaka', 'Dhaka', 'Net 30', 500000.00, 4.50, 'Active', 1),
(1, 'SUP-002', 'Global Electronics BD', 'Ms. Fatima Khan', 'fatima@globalelec.com', '+880 1823456789', 'Plot 23, Sector 7, Uttara', 'Dhaka', 'Dhaka', 'Net 60', 750000.00, 4.80, 'Active', 1),
(1, 'SUP-003', 'Prime Imports', 'Mr. Rajesh Kumar', 'rajesh@primeimports.com', '+880 1934567890', 'CDA Avenue, GEC Circle', 'Chittagong', 'Chittagong', 'Net 15', 300000.00, 3.90, 'Active', 1);

-- ============================================================================
-- 2. GRN (Goods Received Notes) TABLE
-- Track all goods received from suppliers
-- ============================================================================

CREATE TABLE `grn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `grn_number` varchar(50) NOT NULL COMMENT 'Unique GRN number like GRN-2025-001',
  `supplier_id` int(11) NOT NULL,
  `purchase_order_number` varchar(50) DEFAULT NULL COMMENT 'Reference PO if exists',
  `invoice_number` varchar(50) DEFAULT NULL COMMENT 'Supplier invoice number',
  `invoice_date` date DEFAULT NULL,
  `received_date` date NOT NULL,
  `total_items` int(11) DEFAULT 0,
  `total_quantity` int(11) DEFAULT 0,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `net_amount` decimal(12,2) DEFAULT 0.00 COMMENT 'After tax and discount',
  `payment_status` enum('Pending','Partial','Paid') DEFAULT 'Pending',
  `payment_due_date` date DEFAULT NULL,
  `status` enum('Draft','Verified','Approved','Rejected','Cancelled') DEFAULT 'Draft',
  `warehouse_location` varchar(100) DEFAULT NULL,
  `received_by` int(11) NOT NULL COMMENT 'Admin In-charge who received goods',
  `verified_by` int(11) DEFAULT NULL COMMENT 'User who verified the GRN',
  `approved_by` int(11) DEFAULT NULL COMMENT 'User who approved the GRN',
  `notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `grn_number` (`grn_number`,`owner_id`),
  KEY `owner_id` (`owner_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `status` (`status`),
  KEY `received_date` (`received_date`),
  KEY `received_by` (`received_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample Data
INSERT INTO `grn` (`owner_id`, `grn_number`, `supplier_id`, `invoice_number`, `invoice_date`, `received_date`, `total_items`, `total_quantity`, `total_amount`, `tax_amount`, `net_amount`, `payment_status`, `status`, `received_by`, `verified_by`) VALUES
(1, 'GRN-2025-001', 1, 'INV-TW-2025-045', '2025-11-01', '2025-11-02', 3, 150, 270000.00, 40500.00, 310500.00, 'Paid', 'Approved', 1, 1),
(1, 'GRN-2025-002', 2, 'INV-GE-2025-123', '2025-11-03', '2025-11-04', 2, 80, 192000.00, 28800.00, 220800.00, 'Pending', 'Verified', 1, 1);

-- ============================================================================
-- 3. GRN ITEMS TABLE
-- Individual items in each GRN
-- ============================================================================

CREATE TABLE `grn_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grn_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `quantity_ordered` int(11) DEFAULT NULL COMMENT 'Quantity in PO if exists',
  `quantity_received` int(11) NOT NULL,
  `quantity_accepted` int(11) DEFAULT NULL,
  `quantity_rejected` int(11) DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(12,2) NOT NULL,
  `manufacturing_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `condition_status` enum('New','Good','Damaged','Defective') DEFAULT 'New',
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `grn_id` (`grn_id`),
  KEY `product_id` (`product_id`),
  KEY `batch_number` (`batch_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample Data
INSERT INTO `grn_items` (`grn_id`, `product_id`, `batch_number`, `quantity_received`, `quantity_accepted`, `unit_cost`, `total_cost`, `manufacturing_date`, `expiry_date`, `condition_status`) VALUES
(1, 1, 'B25-LS-001', 50, 50, 1800.00, 90000.00, '2025-10-15', NULL, 'New'),
(1, 2, 'B25-WM-001', 100, 98, 800.00, 78400.00, '2025-10-20', NULL, 'New'),
(2, 3, 'B25-UC-001', 80, 80, 2400.00, 192000.00, '2025-10-25', NULL, 'New');

-- ============================================================================
-- 4. PRODUCT BATCHES TABLE
-- Track batch-wise inventory with expiry dates
-- ============================================================================

CREATE TABLE `product_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_number` varchar(50) NOT NULL,
  `grn_id` int(11) DEFAULT NULL COMMENT 'GRN that created this batch',
  `quantity_received` int(11) NOT NULL,
  `quantity_available` int(11) NOT NULL COMMENT 'Current available stock',
  `quantity_sold` int(11) DEFAULT 0,
  `quantity_damaged` int(11) DEFAULT 0,
  `quantity_returned` int(11) DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL,
  `manufacturing_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `warehouse_location` varchar(100) DEFAULT NULL COMMENT 'Shelf/Bin location',
  `status` enum('Active','Low Stock','Expired','Depleted','Recalled') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_number` (`batch_number`,`owner_id`),
  KEY `owner_id` (`owner_id`),
  KEY `product_id` (`product_id`),
  KEY `grn_id` (`grn_id`),
  KEY `expiry_date` (`expiry_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample Data
INSERT INTO `product_batches` (`owner_id`, `product_id`, `batch_number`, `grn_id`, `quantity_received`, `quantity_available`, `quantity_sold`, `unit_cost`, `manufacturing_date`, `warehouse_location`, `status`) VALUES
(1, 1, 'B25-LS-001', 1, 50, 49, 1, 1800.00, '2025-10-15', 'A-12-03', 'Active'),
(1, 2, 'B25-WM-001', 1, 98, 90, 8, 800.00, '2025-10-20', 'B-05-11', 'Low Stock'),
(1, 3, 'B25-UC-001', 2, 80, 77, 3, 2400.00, '2025-10-25', 'A-18-07', 'Active');

-- ============================================================================
-- 5. PRODUCT DISCOUNTS TABLE
-- Manage product-level discounts and offers
-- ============================================================================

CREATE TABLE `product_discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `discount_name` varchar(150) NOT NULL,
  `discount_type` enum('Percentage','Fixed Amount','Buy X Get Y') DEFAULT 'Percentage',
  `discount_value` decimal(10,2) NOT NULL COMMENT 'Percentage or fixed amount',
  `min_quantity` int(11) DEFAULT 1 COMMENT 'Minimum quantity to qualify',
  `max_discount_amount` decimal(10,2) DEFAULT NULL COMMENT 'Cap on discount',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `priority` int(11) DEFAULT 0 COMMENT 'Higher priority applies first',
  `applicable_to` enum('All Customers','New Customers','Loyalty Customers','Bulk Orders') DEFAULT 'All Customers',
  `usage_limit` int(11) DEFAULT NULL COMMENT 'Max times this offer can be used',
  `usage_count` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `product_id` (`product_id`),
  KEY `is_active` (`is_active`),
  KEY `start_date` (`start_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample Data
INSERT INTO `product_discounts` (`owner_id`, `product_id`, `discount_name`, `discount_type`, `discount_value`, `min_quantity`, `start_date`, `end_date`, `is_active`, `created_by`) VALUES
(1, 1, 'Black Friday - Laptop Stand', 'Percentage', 15.00, 1, '2025-11-20', '2025-11-30', 1, 1),
(1, 2, 'Bulk Discount - Wireless Mouse', 'Percentage', 20.00, 10, '2025-11-01', '2025-12-31', 1, 1),
(1, 3, 'New Year Sale - USB Hub', 'Fixed Amount', 500.00, 1, '2025-12-25', '2026-01-05', 1, 1);

-- ============================================================================
-- 6. INVENTORY AUDIT LOGS TABLE
-- Full change-tracking for all inventory operations
-- ============================================================================

CREATE TABLE `inventory_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `action_type` enum('Stock In','Stock Out','Adjustment','Transfer','Return','Damage','GRN','Sale','Manual Update') NOT NULL,
  `reference_type` enum('GRN','Booking','Delivery','Return','Manual','Transfer') DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID of related record (grn_id, booking_id, etc)',
  `quantity_before` int(11) DEFAULT NULL,
  `quantity_change` int(11) NOT NULL COMMENT 'Positive for increase, negative for decrease',
  `quantity_after` int(11) DEFAULT NULL,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `location_from` varchar(100) DEFAULT NULL,
  `location_to` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `performed_by` int(11) NOT NULL COMMENT 'User who performed the action',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `product_id` (`product_id`),
  KEY `action_type` (`action_type`),
  KEY `performed_by` (`performed_by`),
  KEY `created_at` (`created_at`),
  KEY `reference_type` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample Data
INSERT INTO `inventory_audit_logs` (`owner_id`, `product_id`, `batch_number`, `action_type`, `reference_type`, `quantity_before`, `quantity_change`, `quantity_after`, `cost_per_unit`, `reason`, `performed_by`) VALUES
(1, 1, 'B25-LS-001', 'Stock In', 'GRN', 0, 50, 50, 1800.00, 'GRN-2025-001 processed', 1),
(1, 1, 'B25-LS-001', 'Stock Out', 'Booking', 50, -1, 49, 1800.00, 'Sold via booking BK-001', 11),
(1, 2, 'B25-WM-001', 'Stock In', 'GRN', 0, 98, 98, 800.00, 'GRN-2025-001 processed', 1);

-- ============================================================================
-- 7. INVENTORY FORECASTING TABLE
-- AI-based predictions for stock requirements
-- ============================================================================

CREATE TABLE `inventory_forecasts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `forecast_date` date NOT NULL,
  `forecast_period` enum('Weekly','Monthly','Quarterly') DEFAULT 'Monthly',
  `predicted_demand` int(11) NOT NULL COMMENT 'Predicted units needed',
  `current_stock` int(11) NOT NULL,
  `recommended_order_quantity` int(11) NOT NULL,
  `confidence_level` decimal(5,2) DEFAULT NULL COMMENT 'Percentage 0-100',
  `based_on_data_points` int(11) DEFAULT NULL COMMENT 'Number of historical records used',
  `trend` enum('Increasing','Decreasing','Stable','Volatile') DEFAULT 'Stable',
  `seasonality_factor` decimal(5,2) DEFAULT 1.00,
  `notes` text DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL COMMENT 'System = NULL, Manual = user_id',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `product_id` (`product_id`),
  KEY `forecast_date` (`forecast_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample Data
INSERT INTO `inventory_forecasts` (`owner_id`, `product_id`, `forecast_date`, `forecast_period`, `predicted_demand`, `current_stock`, `recommended_order_quantity`, `confidence_level`, `based_on_data_points`, `trend`) VALUES
(1, 1, '2025-12-01', 'Monthly', 75, 49, 50, 85.50, 120, 'Increasing'),
(1, 2, '2025-12-01', 'Monthly', 120, 90, 80, 78.30, 98, 'Stable'),
(1, 4, '2025-12-01', 'Monthly', 45, 0, 60, 92.10, 156, 'Increasing');

-- ============================================================================
-- 8. SUPPLIER PERFORMANCE TABLE
-- Track supplier delivery times, quality, and reliability
-- ============================================================================

CREATE TABLE `supplier_performance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `grn_id` int(11) NOT NULL,
  `on_time_delivery` tinyint(1) DEFAULT 1 COMMENT '1 = On time, 0 = Delayed',
  `days_delayed` int(11) DEFAULT 0,
  `quality_score` decimal(3,2) DEFAULT NULL COMMENT 'Out of 5.00',
  `defect_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'Percentage of defective items',
  `packaging_quality` enum('Excellent','Good','Fair','Poor') DEFAULT 'Good',
  `documentation_accuracy` tinyint(1) DEFAULT 1 COMMENT '1 = Accurate, 0 = Issues',
  `communication_rating` decimal(3,2) DEFAULT NULL COMMENT 'Out of 5.00',
  `overall_rating` decimal(3,2) DEFAULT NULL COMMENT 'Out of 5.00',
  `issues_reported` text DEFAULT NULL,
  `positive_feedback` text DEFAULT NULL,
  `would_recommend` tinyint(1) DEFAULT 1,
  `evaluated_by` int(11) NOT NULL,
  `evaluation_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `grn_id` (`grn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample Data
INSERT INTO `supplier_performance` (`owner_id`, `supplier_id`, `grn_id`, `on_time_delivery`, `days_delayed`, `quality_score`, `defect_rate`, `packaging_quality`, `documentation_accuracy`, `communication_rating`, `overall_rating`, `would_recommend`, `evaluated_by`, `evaluation_date`) VALUES
(1, 1, 1, 1, 0, 4.50, 0.00, 'Excellent', 1, 4.80, 4.70, 1, 1, '2025-11-02'),
(1, 2, 2, 1, 0, 5.00, 0.00, 'Excellent', 1, 5.00, 5.00, 1, 1, '2025-11-04');

-- ============================================================================
-- 9. STOCK ALERTS TABLE
-- Automated alerts for low stock, expiry, and other inventory issues
-- ============================================================================

CREATE TABLE `stock_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `alert_type` enum('Low Stock','Out of Stock','Expiring Soon','Expired','Overstocked','Damaged') NOT NULL,
  `alert_level` enum('Info','Warning','Critical','Urgent') DEFAULT 'Warning',
  `current_quantity` int(11) NOT NULL,
  `threshold_quantity` int(11) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `days_until_expiry` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('New','Acknowledged','Resolved','Ignored') DEFAULT 'New',
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `product_id` (`product_id`),
  KEY `alert_type` (`alert_type`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample Data
INSERT INTO `stock_alerts` (`owner_id`, `product_id`, `batch_number`, `alert_type`, `alert_level`, `current_quantity`, `threshold_quantity`, `message`, `status`) VALUES
(1, 2, 'B25-WM-001', 'Low Stock', 'Warning', 90, 100, 'Wireless Mouse Pro stock is below threshold. Consider reordering.', 'New'),
(1, 4, NULL, 'Out of Stock', 'Critical', 0, 10, 'Mechanical Keyboard RGB is completely out of stock!', 'New');

-- ============================================================================
-- 10. BARCODE GENERATION LOG TABLE
-- Track all barcode generation activities
-- ============================================================================

CREATE TABLE `barcode_generation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `grn_id` int(11) DEFAULT NULL,
  `barcode_value` varchar(100) NOT NULL,
  `barcode_format` enum('Code128','EAN13','QR Code','Code39','UPC') DEFAULT 'Code128',
  `quantity_generated` int(11) DEFAULT 1,
  `label_size` varchar(50) DEFAULT NULL COMMENT 'e.g., 40x30mm',
  `printer_used` varchar(100) DEFAULT NULL,
  `print_status` enum('Pending','Printed','Failed','Reprinted') DEFAULT 'Pending',
  `generated_by` int(11) NOT NULL,
  `printed_by` int(11) DEFAULT NULL,
  `printed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `product_id` (`product_id`),
  KEY `barcode_value` (`barcode_value`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample Data
INSERT INTO `barcode_generation_logs` (`owner_id`, `product_id`, `batch_number`, `grn_id`, `barcode_value`, `barcode_format`, `quantity_generated`, `label_size`, `print_status`, `generated_by`) VALUES
(1, 1, 'B25-LS-001', 1, 'PROD-000001-B25-LS-001', 'Code128', 50, '50x30mm', 'Printed', 1),
(1, 2, 'B25-WM-001', 1, 'PROD-000002-B25-WM-001', 'Code128', 98, '50x30mm', 'Printed', 1),
(1, 3, 'B25-UC-001', 2, 'PROD-000003-B25-UC-001', 'QR Code', 80, '40x40mm', 'Printed', 1);

-- ============================================================================
-- Add Foreign Key Constraints
-- ============================================================================

ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_suppliers_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_suppliers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `grn`
  ADD CONSTRAINT `fk_grn_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grn_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `fk_grn_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`);

ALTER TABLE `grn_items`
  ADD CONSTRAINT `fk_grn_items_grn` FOREIGN KEY (`grn_id`) REFERENCES `grn` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grn_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

ALTER TABLE `product_batches`
  ADD CONSTRAINT `fk_batches_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_batches_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_batches_grn` FOREIGN KEY (`grn_id`) REFERENCES `grn` (`id`);

ALTER TABLE `product_discounts`
  ADD CONSTRAINT `fk_discounts_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_discounts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_discounts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `inventory_audit_logs`
  ADD CONSTRAINT `fk_audit_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_audit_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_audit_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`);

ALTER TABLE `inventory_forecasts`
  ADD CONSTRAINT `fk_forecast_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_forecast_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

ALTER TABLE `supplier_performance`
  ADD CONSTRAINT `fk_performance_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_performance_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_performance_grn` FOREIGN KEY (`grn_id`) REFERENCES `grn` (`id`);

ALTER TABLE `stock_alerts`
  ADD CONSTRAINT `fk_alerts_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_alerts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

ALTER TABLE `barcode_generation_logs`
  ADD CONSTRAINT `fk_barcode_logs_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_barcode_logs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_barcode_logs_grn` FOREIGN KEY (`grn_id`) REFERENCES `grn` (`id`);

COMMIT;

-- ============================================================================
-- END OF ADMIN IN-CHARGE TABLES
-- ============================================================================

-- Summary:
-- 1. suppliers - Manage supplier information and payment terms
-- 2. grn - Goods Received Notes with verification workflow
-- 3. grn_items - Line items in each GRN
-- 4. product_batches - Batch-wise inventory with expiry tracking
-- 5. product_discounts - Product-level discount management
-- 6. inventory_audit_logs - Complete audit trail for all inventory changes
-- 7. inventory_forecasts - AI-based demand predictions
-- 8. supplier_performance - Track supplier reliability and quality
-- 9. stock_alerts - Automated alerts for inventory issues
-- 10. barcode_generation_logs - Track barcode printing activities
