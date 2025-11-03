-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2025 at 10:16 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trackit`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_number` varchar(50) NOT NULL COMMENT 'Unique booking identifier like BK-001',
  `owner_id` int(11) NOT NULL COMMENT 'References the owner user_id',
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Confirmed','Processing','Ready','Delivered','Cancelled','Rejected') NOT NULL DEFAULT 'Pending',
  `priority` enum('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  `booking_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL COMMENT 'Notes visible only to staff',
  `confirmation_sent` tinyint(1) NOT NULL DEFAULT 0,
  `reminder_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL COMMENT 'User ID (Moderator) who created this booking',
  `assigned_to` int(11) DEFAULT NULL COMMENT 'Store In-charge assigned to fulfill this booking',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_number`, `owner_id`, `customer_id`, `product_id`, `quantity`, `unit_price`, `total_amount`, `status`, `priority`, `booking_date`, `delivery_date`, `notes`, `internal_notes`, `confirmation_sent`, `reminder_sent`, `created_by`, `assigned_to`, `created_at`, `updated_at`) VALUES
(1, 'BK-001', 1, 1, 1, 2, 2500.00, 5000.00, 'Confirmed', 'Normal', '2025-11-03', '2025-11-10', 'Customer wants delivery before 5 PM', NULL, 0, 0, 10, NULL, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(2, 'BK-002', 1, 2, 2, 5, 1200.00, 6000.00, 'Pending', 'High', '2025-11-02', '2025-11-08', 'Urgent order for office setup', NULL, 0, 0, 10, NULL, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(3, 'BK-003', 1, 1, 3, 3, 3500.00, 10500.00, 'Processing', 'Normal', '2025-11-01', '2025-11-07', NULL, NULL, 0, 0, 10, NULL, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(4, 'BK-004', 1, 3, 5, 1, 3200.00, 3200.00, 'Confirmed', 'Normal', '2025-10-30', '2025-11-05', 'Gift wrap requested', NULL, 0, 0, 10, NULL, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(5, 'BK-005', 1, 4, 6, 10, 800.00, 8000.00, 'Delivered', 'Low', '2025-10-28', '2025-11-02', 'Bulk order completed', NULL, 0, 0, 10, NULL, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(7, 'BK-007', 1, 4, 1, 4, 2500.00, 10000.00, 'Ready', 'High', '2025-11-03', '2025-11-06', 'Ready for pickup', NULL, 0, 0, 10, NULL, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(8, 'BK-008', 1, 8, 1, 24, 2500.00, 60000.00, 'Pending', 'Normal', '2025-11-03', '2025-11-14', '', NULL, 0, 0, 10, NULL, '2025-11-03 19:44:29', '2025-11-03 19:44:29'),
(9, 'BK-009', 1, 8, 6, 1, 800.00, 800.00, 'Pending', 'High', '2025-11-03', NULL, '', NULL, 0, 0, 10, NULL, '2025-11-03 20:16:50', '2025-11-03 20:24:23'),
(10, 'BK-010', 1, 8, 1, 1, 2500.00, 2500.00, 'Pending', 'Normal', '2025-11-03', NULL, '', NULL, 0, 0, 10, NULL, '2025-11-03 20:30:12', '2025-11-03 20:30:12'),
(11, 'BK-011', 1, 10, 1, 2, 2500.00, 5000.00, 'Pending', 'Normal', '2025-11-03', NULL, '', NULL, 0, 0, 10, NULL, '2025-11-03 20:35:20', '2025-11-03 20:35:20'),
(12, 'BK-012', 1, 8, 6, 10, 800.00, 8000.00, 'Pending', 'Normal', '2025-11-03', NULL, '', NULL, 0, 0, 10, NULL, '2025-11-03 20:47:00', '2025-11-03 20:47:00');

-- --------------------------------------------------------

--
-- Table structure for table `booking_history`
--

CREATE TABLE `booking_history` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL COMMENT 'User ID who made the change',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_history`
--

INSERT INTO `booking_history` (`id`, `booking_id`, `previous_status`, `new_status`, `changed_by`, `notes`, `created_at`) VALUES
(1, 1, 'Pending', 'Confirmed', 10, 'Customer confirmed via phone', '2025-11-03 16:55:26'),
(2, 3, 'Confirmed', 'Processing', 10, 'Sent to warehouse for processing', '2025-11-03 16:55:26'),
(3, 5, 'Ready', 'Delivered', 10, 'Successfully delivered to customer', '2025-11-03 16:55:26'),
(5, 8, NULL, 'Pending', 10, 'Booking created', '2025-11-03 19:44:29'),
(6, 9, NULL, 'Pending', 10, 'Booking created', '2025-11-03 20:16:50'),
(7, 10, NULL, 'Pending', 10, 'Booking created', '2025-11-03 20:30:13'),
(8, 11, NULL, 'Pending', 10, 'Booking created', '2025-11-03 20:35:20'),
(9, 12, NULL, 'Pending', 10, 'Booking created', '2025-11-03 20:47:00');

-- --------------------------------------------------------

--
-- Table structure for table `booking_reminders`
--

CREATE TABLE `booking_reminders` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `reminder_type` enum('Confirmation','Follow-up','Delivery','Payment') NOT NULL DEFAULT 'Follow-up',
  `reminder_date` datetime NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('Pending','Sent','Failed') NOT NULL DEFAULT 'Pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `sent_by` int(11) DEFAULT NULL COMMENT 'User ID who triggered the reminder',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_reminders`
--

INSERT INTO `booking_reminders` (`id`, `booking_id`, `reminder_type`, `reminder_date`, `message`, `status`, `sent_at`, `sent_by`, `created_at`) VALUES
(1, 1, 'Confirmation', '2025-11-04 10:00:00', 'Please confirm your booking for Premium Laptop Stand (2 units)', 'Sent', '2025-11-03 17:01:09', 10, '2025-11-03 16:55:26'),
(2, 2, 'Follow-up', '2025-11-04 14:00:00', 'Following up on your urgent order', 'Sent', '2025-11-03 17:01:27', 10, '2025-11-03 16:55:26'),
(3, 3, 'Delivery', '2025-11-07 09:00:00', 'Your order is scheduled for delivery today', 'Sent', '2025-11-03 17:01:26', 10, '2025-11-03 16:55:26'),
(4, 8, 'Follow-up', '2025-11-04 20:44:30', 'Follow up on pending booking', 'Pending', NULL, NULL, '2025-11-03 19:44:30'),
(5, 9, 'Follow-up', '2025-11-04 21:16:50', 'Follow up on pending booking', 'Pending', NULL, NULL, '2025-11-03 20:16:50'),
(6, 10, 'Follow-up', '2025-11-04 21:30:13', 'Follow up on pending booking', 'Pending', NULL, NULL, '2025-11-03 20:30:13'),
(7, 11, 'Follow-up', '2025-11-04 21:35:20', 'Follow up on pending booking', 'Pending', NULL, NULL, '2025-11-03 20:35:20'),
(8, 12, 'Follow-up', '2025-11-04 21:47:00', 'Follow up on pending booking', 'Pending', NULL, NULL, '2025-11-03 20:47:00');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL COMMENT 'References the owner user_id',
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_spent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL COMMENT 'User ID who created this customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `owner_id`, `name`, `email`, `phone`, `address`, `city`, `state`, `postal_code`, `company`, `status`, `total_orders`, `total_spent`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'John Doe', 'john.doe@example.com', '+880 1234567890', '123 Main St, Apt 4B', 'Dhaka', 'Dhaka', '1205', 'Tech Solutions Ltd', 'Active', 12, 45600.00, NULL, 10, '2025-11-03 16:55:25', '2025-11-03 16:55:25'),
(2, 1, 'Sarah Miller', 'sarah.miller@example.com', '+880 1987654321', '456 Oak Avenue', 'Chittagong', 'Chittagong', '4000', 'Miller Enterprises', 'Active', 8, 32400.00, NULL, 10, '2025-11-03 16:55:25', '2025-11-03 16:55:25'),
(3, 1, 'Ahmed Khan', 'ahmed.khan@example.com', '+880 1555123456', '789 Park Road', 'Dhaka', 'Dhaka', '1207', NULL, 'Active', 5, 18900.00, NULL, 10, '2025-11-03 16:55:25', '2025-11-03 16:55:25'),
(4, 1, 'Lisa Chen', 'lisa.chen@example.com', '+880 1666789012', '321 Hill Street', 'Sylhet', 'Sylhet', '3100', 'Chen Trading Co', 'Active', 15, 56700.00, NULL, 10, '2025-11-03 16:55:25', '2025-11-03 16:55:25'),
(5, 1, 'Robert Wilson', 'robert.wilson@example.com', '+880 1777345678', '654 River View', 'Dhaka', 'Dhaka', '1212', NULL, 'Inactive', 3, 9800.00, NULL, 10, '2025-11-03 16:55:25', '2025-11-03 16:55:25'),
(8, 1, 'Ahanaf Abid Sazid', 'srsrizon665@gmail.com', '01706941756', 'phulpur', 'Phulpur', 'Merul', NULL, NULL, 'Active', 4, 0.00, NULL, 10, '2025-11-03 19:38:43', '2025-11-03 20:47:00'),
(10, 1, 'Ahanaf Abid Sazid', '', '01701057395', 'Merul DIT', 'Dhaka', 'Merul', NULL, NULL, 'Active', 1, 0.00, NULL, 10, '2025-11-03 20:35:20', '2025-11-03 20:35:20');

-- --------------------------------------------------------

--
-- Table structure for table `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `rating` int(1) DEFAULT NULL COMMENT '1-5 stars',
  `feedback` text DEFAULT NULL,
  `feedback_type` enum('General','Product','Service','Delivery','Issue') NOT NULL DEFAULT 'General',
  `status` enum('New','Reviewed','Resolved') NOT NULL DEFAULT 'New',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_feedback`
--

INSERT INTO `customer_feedback` (`id`, `owner_id`, `customer_id`, `booking_id`, `rating`, `feedback`, `feedback_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 5, 'Excellent product quality and fast delivery!', 'Product', 'Reviewed', '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(2, 1, 4, 5, 5, 'Very professional service. Will order again.', 'Service', 'Reviewed', '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(3, 1, 2, NULL, 3, 'Product was out of stock. Should have been notified earlier.', 'Issue', 'Resolved', '2025-11-03 16:55:26', '2025-11-03 16:55:26');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `tracking_number` varchar(100) DEFAULT NULL COMMENT 'Courier tracking ID',
  `courier_name` varchar(100) DEFAULT NULL COMMENT 'Courier company name',
  `dispatch_date` date DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_delivery_date` date DEFAULT NULL,
  `delivery_status` enum('Pending','Dispatched','In Transit','Out for Delivery','Delivered','Failed','Returned') NOT NULL DEFAULT 'Pending',
  `delivery_address` text DEFAULT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `recipient_phone` varchar(20) DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `proof_of_delivery` varchar(255) DEFAULT NULL COMMENT 'Image URL of signed slip',
  `courier_api_response` text DEFAULT NULL COMMENT 'JSON response from courier API',
  `last_status_check` timestamp NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL COMMENT 'Store In-charge who created delivery',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_tracking_history`
--

CREATE TABLE `delivery_tracking_history` (
  `id` int(11) NOT NULL,
  `delivery_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL COMMENT 'User who updated status',
  `is_automated` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 if from courier API, 0 if manual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `low_stock_alerts`
--

CREATE TABLE `low_stock_alerts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `alert_type` enum('Low Stock','Out of Stock','Critical') NOT NULL DEFAULT 'Low Stock',
  `current_stock_level` int(11) NOT NULL,
  `threshold_level` int(11) NOT NULL,
  `alert_status` enum('Active','Acknowledged','Resolved') NOT NULL DEFAULT 'Active',
  `notified_users` text DEFAULT NULL COMMENT 'JSON array of user IDs notified',
  `resolution_notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL COMMENT 'References the owner user_id',
  `name` varchar(200) NOT NULL,
  `sku` varchar(100) DEFAULT NULL COMMENT 'Stock Keeping Unit',
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost` decimal(10,2) DEFAULT NULL COMMENT 'Cost price',
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 10,
  `unit` varchar(50) DEFAULT 'pieces' COMMENT 'Unit of measurement',
  `status` enum('Active','Inactive','Discontinued') NOT NULL DEFAULT 'Active',
  `image_url` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL COMMENT 'User ID who created this product',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `owner_id`, `name`, `sku`, `description`, `category`, `price`, `cost`, `stock_quantity`, `low_stock_threshold`, `unit`, `status`, `image_url`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Premium Laptop Stand', 'SKU-001', 'Ergonomic aluminum laptop stand with adjustable height', 'Office Accessories', 2500.00, 1800.00, 45, 10, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(2, 1, 'Wireless Mouse Pro', 'SKU-002', 'Bluetooth wireless mouse with ergonomic design', 'Computer Peripherals', 1200.00, 800.00, 8, 15, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(3, 1, 'USB-C Hub 7-in-1', 'SKU-003', 'Multi-port USB-C hub with HDMI, USB 3.0, SD card reader', 'Computer Peripherals', 3500.00, 2400.00, 120, 20, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(4, 1, 'Mechanical Keyboard RGB', 'SKU-004', 'Gaming mechanical keyboard with RGB backlighting', 'Computer Peripherals', 4500.00, 3200.00, 0, 10, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(5, 1, 'Webcam 1080p HD', 'SKU-005', 'Full HD webcam with built-in microphone', 'Computer Peripherals', 3200.00, 2100.00, 32, 15, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(6, 1, 'Monitor Screen Protector', 'SKU-006', 'Anti-glare screen protector for 24-inch monitors', 'Office Accessories', 800.00, 450.00, 68, 25, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 20:47:00');

-- --------------------------------------------------------

--
-- Table structure for table `product_barcodes`
--

CREATE TABLE `product_barcodes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `barcode` varchar(100) NOT NULL COMMENT 'Unique barcode value',
  `batch_number` varchar(50) DEFAULT NULL,
  `manufacturing_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quantity_per_batch` int(11) NOT NULL DEFAULT 0 COMMENT 'Quantity in this batch',
  `warehouse_location` varchar(100) DEFAULT NULL COMMENT 'Warehouse location',
  `status` enum('Active','Expired','Damaged','Recalled') NOT NULL DEFAULT 'Active',
  `auto_expired` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 if auto-expired by system',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `return_number` varchar(50) NOT NULL COMMENT 'Unique return identifier like RET-001',
  `return_reason` enum('Defective','Wrong Item','Damaged','Changed Mind','Quality Issue','Other') NOT NULL,
  `return_status` enum('Pending','Approved','Rejected','Inspected','Restocked','Completed') NOT NULL DEFAULT 'Pending',
  `condition_on_return` enum('Good','Acceptable','Damaged','Unusable') DEFAULT NULL,
  `quantity_returned` int(11) NOT NULL,
  `customer_comments` text DEFAULT NULL,
  `damage_images` text DEFAULT NULL COMMENT 'JSON array of image URLs',
  `inspection_notes` text DEFAULT NULL,
  `restocking_fee` decimal(10,2) DEFAULT 0.00,
  `restocked` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 if added back to inventory',
  `created_by` int(11) NOT NULL COMMENT 'User who initiated return',
  `approved_by` int(11) DEFAULT NULL COMMENT 'Store In-charge who approved',
  `approved_at` timestamp NULL DEFAULT NULL,
  `inspected_by` int(11) DEFAULT NULL COMMENT 'Store In-charge who inspected',
  `inspected_at` timestamp NULL DEFAULT NULL,
  `restocked_by` int(11) DEFAULT NULL COMMENT 'User who restocked',
  `restocked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('Owner','Moderator','Accountant','Admin In-charge','Store In-charge') NOT NULL DEFAULT 'Owner',
  `owner_id` int(11) DEFAULT NULL COMMENT 'References the owner user_id. NULL for Owners, set for role-based users',
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `owner_id`, `status`, `profile_picture`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Ahanaf Abid Sazid', 'srsrizon665@gmail.com', '$2y$10$JooP0F7doRrn5kXtJYQf9OPPkJmcNePu9ZYxG4YKRY4Kgm8tNsLta', NULL, 'Owner', NULL, 'Active', NULL, '2025-11-03 20:48:15', '2025-11-03 15:22:45', '2025-11-03 20:48:15'),
(8, 'Ahanaf Abid Sazid', 'ext.ahanaf@gmail.com', '$2y$10$vPCQquZNIqT6oqA6xJAG/uTsYqfXDTraPskkUwq340mrAiTdMdwmO', NULL, 'Owner', NULL, 'Active', NULL, NULL, '2025-11-03 16:20:59', '2025-11-03 16:20:59'),
(9, 'Ahanaf Abid Sazid', 'ahanaf.abid.sazid@g.bracu.ac.bd', '$2y$10$MBBka9MFn/ObK/owJSnB0.ZVEEoTNhXTnjaxeYFkfq1oHqRqFpLoi', NULL, 'Accountant', 1, 'Active', NULL, '2025-11-03 16:25:55', '2025-11-03 16:25:42', '2025-11-03 16:25:55'),
(10, 'Mr. accountant', 'ext.ahanaf.abid@gmail.com', '$2y$10$GUN1V8COnltfp4Gccz1k/e5b5ZSClsE0V1FOqd1nnzXtoMQnJrbJe', NULL, 'Moderator', 1, 'Active', NULL, '2025-11-03 18:19:23', '2025-11-03 16:27:28', '2025-11-03 18:19:23'),
(11, 'Mr. Store man', 'ext.ahan@gmail.com', '$2y$10$om82Uzx/YY5qYHaLjsggCOnRD1WzZfFhnP8RDnPtKOTqxlD/UUz9q', NULL, 'Store In-charge', 1, 'Active', NULL, '2025-11-03 21:15:59', '2025-11-03 20:48:45', '2025-11-03 21:15:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_number` (`booking_number`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_booking_date` (`booking_date`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_assigned_to` (`assigned_to`);

--
-- Indexes for table `booking_history`
--
ALTER TABLE `booking_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_changed_by` (`changed_by`);

--
-- Indexes for table `booking_reminders`
--
ALTER TABLE `booking_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_reminder_date` (`reminder_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_reminders_sent_by` (`sent_by`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_phone_owner` (`phone`,`owner_id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_tracking_number` (`tracking_number`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_delivery_status` (`delivery_status`),
  ADD KEY `idx_dispatch_date` (`dispatch_date`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `delivery_tracking_history`
--
ALTER TABLE `delivery_tracking_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_delivery_id` (`delivery_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_tracking_updated_by` (`updated_by`);

--
-- Indexes for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_alert_status` (`alert_status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_alerts_created_by` (`created_by`),
  ADD KEY `fk_alerts_acknowledged_by` (`acknowledged_by`),
  ADD KEY `fk_alerts_resolved_by` (`resolved_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_sku_owner` (`sku`,`owner_id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_stock` (`stock_quantity`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_barcode` (`barcode`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_batch_number` (`batch_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_barcodes_created_by` (`created_by`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_return_number` (`return_number`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_return_status` (`return_status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_approved_by` (`approved_by`),
  ADD KEY `idx_inspected_by` (`inspected_by`),
  ADD KEY `idx_restocked_by` (`restocked_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `booking_history`
--
ALTER TABLE `booking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `booking_reminders`
--
ALTER TABLE `booking_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_tracking_history`
--
ALTER TABLE `delivery_tracking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bookings_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_history`
--
ALTER TABLE `booking_history`
  ADD CONSTRAINT `fk_history_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_history_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_reminders`
--
ALTER TABLE `booking_reminders`
  ADD CONSTRAINT `fk_reminders_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reminders_sent_by` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_customers_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD CONSTRAINT `fk_feedback_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_feedback_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_feedback_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `fk_deliveries_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_deliveries_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_deliveries_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_tracking_history`
--
ALTER TABLE `delivery_tracking_history`
  ADD CONSTRAINT `fk_tracking_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tracking_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  ADD CONSTRAINT `fk_alerts_acknowledged_by` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_alerts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_alerts_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_alerts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_alerts_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_products_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_barcodes`
--
ALTER TABLE `product_barcodes`
  ADD CONSTRAINT `fk_barcodes_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_barcodes_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_barcodes_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `fk_returns_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_returns_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_returns_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_returns_inspected_by` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_returns_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_returns_restocked_by` FOREIGN KEY (`restocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
