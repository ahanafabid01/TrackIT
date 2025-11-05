-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2025 at 07:45 PM
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
-- Table structure for table `barcode_generation_logs`
--

CREATE TABLE `barcode_generation_logs` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barcode_generation_logs`
--

INSERT INTO `barcode_generation_logs` (`id`, `owner_id`, `product_id`, `batch_number`, `grn_id`, `barcode_value`, `barcode_format`, `quantity_generated`, `label_size`, `printer_used`, `print_status`, `generated_by`, `printed_by`, `printed_at`, `created_at`) VALUES
(1, 1, 1, 'B25-LS-001', 1, 'PROD-000001-B25-LS-001', 'Code128', 50, '50x30mm', NULL, 'Printed', 1, NULL, NULL, '2025-11-05 18:33:21'),
(2, 1, 2, 'B25-WM-001', 1, 'PROD-000002-B25-WM-001', 'Code128', 98, '50x30mm', NULL, 'Printed', 1, NULL, NULL, '2025-11-05 18:33:21'),
(3, 1, 3, 'B25-UC-001', 2, 'PROD-000003-B25-UC-001', 'QR Code', 80, '40x40mm', NULL, 'Printed', 1, NULL, NULL, '2025-11-05 18:33:21');

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
  `status` enum('Pending','Confirmed','Processing','Ready','Delivered','Return','Cancelled','Rejected') NOT NULL DEFAULT 'Pending',
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
(2, 'BK-002', 1, 2, 2, 5, 1200.00, 6000.00, 'Delivered', 'High', '2025-11-02', '2025-11-04', 'Urgent order for office setup', NULL, 0, 0, 10, 11, '2025-11-03 16:55:26', '2025-11-03 22:57:45'),
(3, 'BK-003', 1, 1, 3, 3, 3500.00, 10500.00, 'Delivered', 'Normal', '2025-11-01', '2025-11-04', NULL, NULL, 0, 0, 10, NULL, '2025-11-03 16:55:26', '2025-11-03 23:23:43'),
(4, 'BK-004', 1, 3, 5, 1, 3200.00, 3200.00, 'Confirmed', 'Normal', '2025-10-30', '2025-11-05', 'Gift wrap requested', NULL, 0, 0, 10, NULL, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(5, 'BK-005', 1, 4, 6, 10, 800.00, 8000.00, 'Delivered', 'Low', '2025-10-28', '2025-11-02', 'Bulk order completed', NULL, 0, 0, 10, NULL, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(7, 'BK-007', 1, 4, 1, 4, 2500.00, 10000.00, 'Cancelled', 'High', '2025-11-03', '2025-11-06', 'Ready for pickup', NULL, 0, 0, 10, 11, '2025-11-03 16:55:26', '2025-11-03 23:05:53'),
(8, 'BK-008', 1, 8, 1, 24, 2500.00, 60000.00, 'Delivered', 'Normal', '2025-11-03', '2025-11-04', '', NULL, 0, 0, 10, 11, '2025-11-03 19:44:29', '2025-11-03 23:24:40'),
(9, 'BK-009', 1, 8, 6, 1, 800.00, 800.00, 'Confirmed', 'High', '2025-11-03', NULL, '', NULL, 0, 0, 10, 11, '2025-11-03 20:16:50', '2025-11-03 23:02:34'),
(10, 'BK-010', 1, 8, 1, 1, 2500.00, 2500.00, 'Delivered', 'Normal', '2025-11-03', '2025-11-04', '', NULL, 0, 0, 10, 11, '2025-11-03 20:30:12', '2025-11-03 23:18:20'),
(11, 'BK-011', 1, 10, 1, 2, 2500.00, 5000.00, 'Delivered', 'Normal', '2025-11-03', '2025-11-04', '', NULL, 0, 0, 10, 11, '2025-11-03 20:35:20', '2025-11-03 21:33:49'),
(12, 'BK-012', 1, 8, 6, 10, 800.00, 8000.00, 'Cancelled', 'Normal', '2025-11-03', NULL, '', NULL, 0, 0, 10, 11, '2025-11-03 20:47:00', '2025-11-03 21:35:13');

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
(9, 12, NULL, 'Pending', 10, 'Booking created', '2025-11-03 20:47:00'),
(10, 2, 'Pending', 'Confirmed', 11, 'Booking confirmed by Store In-charge', '2025-11-03 21:24:22'),
(11, 11, 'Pending', 'Confirmed', 11, 'Status changed to Confirmed', '2025-11-03 21:33:29'),
(12, 11, 'Confirmed', 'Processing', 11, 'Status changed to Processing', '2025-11-03 21:33:38'),
(13, 11, 'Processing', 'Ready', 11, 'Status changed to Ready', '2025-11-03 21:33:45'),
(14, 11, 'Ready', 'Delivered', 11, 'Status changed to Delivered', '2025-11-03 21:33:49'),
(15, 12, 'Pending', 'Confirmed', 11, 'Status changed to Confirmed', '2025-11-03 21:34:46'),
(16, 12, 'Confirmed', 'Processing', 11, 'Status changed to Processing', '2025-11-03 21:34:51'),
(17, 12, 'Processing', 'Ready', 11, 'Status changed to Ready', '2025-11-03 21:34:57'),
(18, 12, 'Ready', 'Cancelled', 11, 'return', '2025-11-03 21:35:13'),
(19, 10, 'Pending', 'Confirmed', 11, 'Status changed to Confirmed', '2025-11-03 21:35:33'),
(20, 10, 'Confirmed', 'Processing', 11, 'Status changed to Processing', '2025-11-03 21:35:36'),
(21, 10, 'Processing', 'Ready', 11, 'Status changed to Ready', '2025-11-03 21:35:47'),
(22, 2, 'Confirmed', 'Processing', 11, 'Status changed to Processing', '2025-11-03 22:57:03'),
(23, 2, 'Processing', 'Ready', 11, 'Status changed to Ready', '2025-11-03 22:57:07'),
(24, 2, 'Ready', 'Delivered', 11, 'Status changed to Delivered', '2025-11-03 22:57:45'),
(25, 9, 'Pending', 'Confirmed', 11, 'Status changed to Confirmed', '2025-11-03 23:02:34'),
(26, 7, 'Ready', 'Cancelled', 11, 'for some reason', '2025-11-03 23:05:53'),
(27, 10, 'Ready', 'Delivered', 11, 'Delivery created and dispatched', '2025-11-03 23:18:20'),
(28, 8, 'Pending', 'Confirmed', 11, 'Status changed to Confirmed', '2025-11-03 23:21:18'),
(29, 8, 'Confirmed', 'Processing', 11, 'Status changed to Processing', '2025-11-03 23:23:33'),
(30, 3, 'Processing', 'Delivered', 11, 'Delivery created and dispatched', '2025-11-03 23:23:43'),
(31, 8, 'Processing', 'Delivered', 11, 'Delivery created and dispatched', '2025-11-03 23:24:40');

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
(1, 1, 'John Doe', 'john.doe@example.com', '+880 1234567890', '123 Main St, Apt 4B', 'Dhaka', 'Dhaka', '1205', 'Tech Solutions Ltd', 'Active', 12, 10500.00, NULL, 10, '2025-11-03 16:55:25', '2025-11-03 23:23:43'),
(2, 1, 'Sarah Miller', 'sarah.miller@example.com', '+880 1987654321', '456 Oak Avenue', 'Chittagong', 'Chittagong', '4000', 'Miller Enterprises', 'Active', 8, 32400.00, NULL, 10, '2025-11-03 16:55:25', '2025-11-03 16:55:25'),
(3, 1, 'Ahmed Khan', 'ahmed.khan@example.com', '+880 1555123456', '789 Park Road', 'Dhaka', 'Dhaka', '1207', NULL, 'Active', 5, 18900.00, NULL, 10, '2025-11-03 16:55:25', '2025-11-03 16:55:25'),
(4, 1, 'Lisa Chen', 'lisa.chen@example.com', '+880 1666789012', '321 Hill Street', 'Sylhet', 'Sylhet', '3100', 'Chen Trading Co', 'Active', 15, 56700.00, NULL, 10, '2025-11-03 16:55:25', '2025-11-03 16:55:25'),
(5, 1, 'Robert Wilson', 'robert.wilson@example.com', '+880 1777345678', '654 River View', 'Dhaka', 'Dhaka', '1212', NULL, 'Inactive', 3, 9800.00, NULL, 10, '2025-11-03 16:55:25', '2025-11-03 16:55:25'),
(8, 1, 'Ahanaf Abid Sazid', 'srsrizon665@gmail.com', '01706941756', 'phulpur', 'Phulpur', 'Merul', NULL, NULL, 'Active', 4, 62500.00, NULL, 10, '2025-11-03 19:38:43', '2025-11-03 23:24:40'),
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

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`id`, `booking_id`, `owner_id`, `tracking_number`, `courier_name`, `dispatch_date`, `expected_delivery_date`, `actual_delivery_date`, `delivery_status`, `delivery_address`, `recipient_name`, `recipient_phone`, `delivery_notes`, `proof_of_delivery`, `courier_api_response`, `last_status_check`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 3, 1, 'SUNDARBAN-1762212223-2425', 'Sundarban', '2025-11-03', '2025-11-06', NULL, 'Pending', '123 Main St, Apt 4B', 'John Doe', '+880 1234567890', '', NULL, NULL, NULL, 11, '2025-11-03 23:23:43', '2025-11-03 23:23:43'),
(4, 8, 1, 'REDX-1762212280-4566', 'RedX', '2025-11-03', '2025-11-06', '2025-11-04', 'Delivered', 'phulpur', 'Ahanaf Abid Sazid', '01706941756', '', NULL, NULL, NULL, 11, '2025-11-03 23:24:40', '2025-11-03 23:33:31');

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

--
-- Dumping data for table `delivery_tracking_history`
--

INSERT INTO `delivery_tracking_history` (`id`, `delivery_id`, `status`, `location`, `description`, `updated_by`, `is_automated`, `created_at`) VALUES
(1, 3, 'Dispatched', NULL, 'Package dispatched from warehouse', 11, 0, '2025-11-03 23:23:43'),
(2, 4, 'Dispatched', NULL, 'Package dispatched from warehouse', 11, 0, '2025-11-03 23:24:40'),
(3, 4, 'Dispatched', NULL, 'hi', 11, 0, '2025-11-03 23:32:55'),
(4, 4, 'In Transit', NULL, 'Status updated to In Transit', 11, 0, '2025-11-03 23:33:12'),
(5, 4, 'Out for Delivery', NULL, 'Status updated to Out for Delivery', 11, 0, '2025-11-03 23:33:14'),
(6, 4, 'Failed', NULL, 'Status updated to Failed', 11, 0, '2025-11-03 23:33:17'),
(7, 4, 'Out for Delivery', NULL, 'Status updated to Out for Delivery', 11, 0, '2025-11-03 23:33:19'),
(8, 4, 'Delivered', 'Customer Location', 'Status updated to Delivered', 11, 0, '2025-11-03 23:33:30');

-- --------------------------------------------------------

--
-- Table structure for table `grn`
--

CREATE TABLE `grn` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grn`
--

INSERT INTO `grn` (`id`, `owner_id`, `grn_number`, `supplier_id`, `purchase_order_number`, `invoice_number`, `invoice_date`, `received_date`, `total_items`, `total_quantity`, `total_amount`, `tax_amount`, `discount_amount`, `net_amount`, `payment_status`, `payment_due_date`, `status`, `warehouse_location`, `received_by`, `verified_by`, `approved_by`, `notes`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 'GRN-2025-001', 1, NULL, 'INV-TW-2025-045', '2025-11-01', '2025-11-02', 3, 150, 270000.00, 40500.00, 0.00, 310500.00, 'Paid', NULL, 'Approved', NULL, 1, 1, NULL, NULL, NULL, '2025-11-05 18:33:16', '2025-11-05 18:33:16'),
(2, 1, 'GRN-2025-002', 2, NULL, 'INV-GE-2025-123', '2025-11-03', '2025-11-04', 2, 80, 192000.00, 28800.00, 0.00, 220800.00, 'Pending', NULL, 'Verified', NULL, 1, 1, NULL, NULL, NULL, '2025-11-05 18:33:16', '2025-11-05 18:33:16');

-- --------------------------------------------------------

--
-- Table structure for table `grn_items`
--

CREATE TABLE `grn_items` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grn_items`
--

INSERT INTO `grn_items` (`id`, `grn_id`, `product_id`, `batch_number`, `quantity_ordered`, `quantity_received`, `quantity_accepted`, `quantity_rejected`, `unit_cost`, `total_cost`, `manufacturing_date`, `expiry_date`, `condition_status`, `rejection_reason`, `notes`, `created_at`) VALUES
(1, 1, 1, 'B25-LS-001', NULL, 50, 50, 0, 1800.00, 90000.00, '2025-10-15', NULL, 'New', NULL, NULL, '2025-11-05 18:33:17'),
(2, 1, 2, 'B25-WM-001', NULL, 100, 98, 0, 800.00, 78400.00, '2025-10-20', NULL, 'New', NULL, NULL, '2025-11-05 18:33:17'),
(3, 2, 3, 'B25-UC-001', NULL, 80, 80, 0, 2400.00, 192000.00, '2025-10-25', NULL, 'New', NULL, NULL, '2025-11-05 18:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_audit_logs`
--

CREATE TABLE `inventory_audit_logs` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_audit_logs`
--

INSERT INTO `inventory_audit_logs` (`id`, `owner_id`, `product_id`, `batch_number`, `action_type`, `reference_type`, `reference_id`, `quantity_before`, `quantity_change`, `quantity_after`, `cost_per_unit`, `location_from`, `location_to`, `reason`, `performed_by`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 1, 'B25-LS-001', 'Stock In', 'GRN', NULL, 0, 50, 50, 1800.00, NULL, NULL, 'GRN-2025-001 processed', 1, NULL, NULL, '2025-11-05 18:33:18'),
(2, 1, 1, 'B25-LS-001', 'Stock Out', 'Booking', NULL, 50, -1, 49, 1800.00, NULL, NULL, 'Sold via booking BK-001', 11, NULL, NULL, '2025-11-05 18:33:18'),
(3, 1, 2, 'B25-WM-001', 'Stock In', 'GRN', NULL, 0, 98, 98, 800.00, NULL, NULL, 'GRN-2025-001 processed', 1, NULL, NULL, '2025-11-05 18:33:18');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_forecasts`
--

CREATE TABLE `inventory_forecasts` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_forecasts`
--

INSERT INTO `inventory_forecasts` (`id`, `owner_id`, `product_id`, `forecast_date`, `forecast_period`, `predicted_demand`, `current_stock`, `recommended_order_quantity`, `confidence_level`, `based_on_data_points`, `trend`, `seasonality_factor`, `notes`, `generated_by`, `created_at`) VALUES
(1, 1, 1, '2025-12-01', 'Monthly', 75, 49, 50, 85.50, 120, 'Increasing', 1.00, NULL, NULL, '2025-11-05 18:33:19'),
(2, 1, 2, '2025-12-01', 'Monthly', 120, 90, 80, 78.30, 98, 'Stable', 1.00, NULL, NULL, '2025-11-05 18:33:19'),
(3, 1, 4, '2025-12-01', 'Monthly', 45, 0, 60, 92.10, 156, 'Increasing', 1.00, NULL, NULL, '2025-11-05 18:33:19');

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
(1, 1, 'Premium Laptop Stand', 'SKU-001', 'Ergonomic aluminum laptop stand with adjustable height', 'Office Accessories', 2500.00, 1800.00, 49, 10, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 23:05:53'),
(2, 1, 'Wireless Mouse Pro', 'SKU-002', 'Bluetooth wireless mouse with ergonomic design', 'Computer Peripherals', 1200.00, 800.00, 8, 15, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(3, 1, 'USB-C Hub 7-in-1', 'SKU-003', 'Multi-port USB-C hub with HDMI, USB 3.0, SD card reader', 'Computer Peripherals', 3500.00, 2400.00, 120, 20, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(4, 1, 'Mechanical Keyboard RGB', 'SKU-004', 'Gaming mechanical keyboard with RGB backlighting', 'Computer Peripherals', 4500.00, 3200.00, 0, 10, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(5, 1, 'Webcam 1080p HD', 'SKU-005', 'Full HD webcam with built-in microphone', 'Computer Peripherals', 3200.00, 2100.00, 32, 15, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 16:55:26'),
(6, 1, 'Monitor Screen Protector', 'SKU-006', 'Anti-glare screen protector for 24-inch monitors', 'Office Accessories', 800.00, 450.00, 78, 25, 'pieces', 'Active', NULL, 1, '2025-11-03 16:55:26', '2025-11-03 21:35:13');

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
-- Table structure for table `product_batches`
--

CREATE TABLE `product_batches` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_batches`
--

INSERT INTO `product_batches` (`id`, `owner_id`, `product_id`, `batch_number`, `grn_id`, `quantity_received`, `quantity_available`, `quantity_sold`, `quantity_damaged`, `quantity_returned`, `unit_cost`, `manufacturing_date`, `expiry_date`, `warehouse_location`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'B25-LS-001', 1, 50, 49, 1, 0, 0, 1800.00, '2025-10-15', NULL, 'A-12-03', 'Active', NULL, '2025-11-05 18:33:17', '2025-11-05 18:33:17'),
(2, 1, 2, 'B25-WM-001', 1, 98, 90, 8, 0, 0, 800.00, '2025-10-20', NULL, 'B-05-11', 'Low Stock', NULL, '2025-11-05 18:33:17', '2025-11-05 18:33:17'),
(3, 1, 3, 'B25-UC-001', 2, 80, 77, 3, 0, 0, 2400.00, '2025-10-25', NULL, 'A-18-07', 'Active', NULL, '2025-11-05 18:33:17', '2025-11-05 18:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `product_discounts`
--

CREATE TABLE `product_discounts` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_discounts`
--

INSERT INTO `product_discounts` (`id`, `owner_id`, `product_id`, `discount_name`, `discount_type`, `discount_value`, `min_quantity`, `max_discount_amount`, `start_date`, `end_date`, `is_active`, `priority`, `applicable_to`, `usage_limit`, `usage_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Black Friday - Laptop Stand', 'Percentage', 15.00, 1, NULL, '2025-11-20', '2025-11-30', 1, 0, 'All Customers', NULL, 0, 1, '2025-11-05 18:33:18', '2025-11-05 18:33:18'),
(2, 1, 2, 'Bulk Discount - Wireless Mouse', 'Percentage', 20.00, 10, NULL, '2025-11-01', '2025-12-31', 1, 0, 'All Customers', NULL, 0, 1, '2025-11-05 18:33:18', '2025-11-05 18:33:18'),
(3, 1, 3, 'New Year Sale - USB Hub', 'Fixed Amount', 500.00, 1, NULL, '2025-12-25', '2026-01-05', 1, 0, 'All Customers', NULL, 0, 1, '2025-11-05 18:33:18', '2025-11-05 18:33:18');

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
-- Table structure for table `stock_alerts`
--

CREATE TABLE `stock_alerts` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_alerts`
--

INSERT INTO `stock_alerts` (`id`, `owner_id`, `product_id`, `batch_number`, `alert_type`, `alert_level`, `current_quantity`, `threshold_quantity`, `expiry_date`, `days_until_expiry`, `message`, `status`, `acknowledged_by`, `acknowledged_at`, `resolved_by`, `resolved_at`, `resolution_notes`, `created_at`) VALUES
(1, 1, 2, 'B25-WM-001', 'Low Stock', 'Warning', 90, 100, NULL, NULL, 'Wireless Mouse Pro stock is below threshold. Consider reordering.', 'New', NULL, NULL, NULL, NULL, NULL, '2025-11-05 18:33:20'),
(2, 1, 4, NULL, 'Out of Stock', 'Critical', 0, 10, NULL, NULL, 'Mechanical Keyboard RGB is completely out of stock!', 'New', NULL, NULL, NULL, NULL, NULL, '2025-11-05 18:33:20');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `owner_id`, `supplier_code`, `company_name`, `contact_person`, `email`, `phone`, `address`, `city`, `state`, `country`, `postal_code`, `tax_id`, `payment_terms`, `credit_limit`, `current_balance`, `rating`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'SUP-001', 'Tech World Ltd', 'Mr. Karim Ahmed', 'karim@techworld.com', '+880 1712345678', 'House 45, Road 12, Gulshan', 'Dhaka', 'Dhaka', 'Bangladesh', NULL, NULL, 'Net 30', 500000.00, 0.00, 4.50, 'Active', NULL, 1, '2025-11-05 18:33:16', '2025-11-05 18:33:16'),
(2, 1, 'SUP-002', 'Global Electronics BD', 'Ms. Fatima Khan', 'fatima@globalelec.com', '+880 1823456789', 'Plot 23, Sector 7, Uttara', 'Dhaka', 'Dhaka', 'Bangladesh', NULL, NULL, 'Net 60', 750000.00, 0.00, 4.80, 'Active', NULL, 1, '2025-11-05 18:33:16', '2025-11-05 18:33:16'),
(3, 1, 'SUP-003', 'Prime Imports', 'Mr. Rajesh Kumar', 'rajesh@primeimports.com', '+880 1934567890', 'CDA Avenue, GEC Circle', 'Chittagong', 'Chittagong', 'Bangladesh', NULL, NULL, 'Net 15', 300000.00, 0.00, 3.90, 'Active', NULL, 1, '2025-11-05 18:33:16', '2025-11-05 18:33:16');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_performance`
--

CREATE TABLE `supplier_performance` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_performance`
--

INSERT INTO `supplier_performance` (`id`, `owner_id`, `supplier_id`, `grn_id`, `on_time_delivery`, `days_delayed`, `quality_score`, `defect_rate`, `packaging_quality`, `documentation_accuracy`, `communication_rating`, `overall_rating`, `issues_reported`, `positive_feedback`, `would_recommend`, `evaluated_by`, `evaluation_date`, `created_at`) VALUES
(1, 1, 1, 1, 1, 0, 4.50, 0.00, 'Excellent', 1, 4.80, 4.70, NULL, NULL, 1, 1, '2025-11-02', '2025-11-05 18:33:19'),
(2, 1, 2, 2, 1, 0, 5.00, 0.00, 'Excellent', 1, 5.00, 5.00, NULL, NULL, 1, 1, '2025-11-04', '2025-11-05 18:33:19');

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
(1, 'Ahanaf Abid Sazid', 'srsrizon665@gmail.com', '$2y$10$JooP0F7doRrn5kXtJYQf9OPPkJmcNePu9ZYxG4YKRY4Kgm8tNsLta', NULL, 'Owner', NULL, 'Active', NULL, '2025-11-05 18:13:55', '2025-11-03 15:22:45', '2025-11-05 18:13:55'),
(8, 'Ahanaf Abid Sazid', 'ext.ahanaf@gmail.com', '$2y$10$vPCQquZNIqT6oqA6xJAG/uTsYqfXDTraPskkUwq340mrAiTdMdwmO', NULL, 'Owner', NULL, 'Active', NULL, NULL, '2025-11-03 16:20:59', '2025-11-03 16:20:59'),
(9, 'Ahanaf Abid Sazid', 'ahanaf.abid.sazid@g.bracu.ac.bd', '$2y$10$MBBka9MFn/ObK/owJSnB0.ZVEEoTNhXTnjaxeYFkfq1oHqRqFpLoi', NULL, 'Accountant', 1, 'Active', NULL, '2025-11-03 21:37:45', '2025-11-03 16:25:42', '2025-11-03 21:37:45'),
(10, 'Mr. accountant', 'ext.ahanaf.abid@gmail.com', '$2y$10$GUN1V8COnltfp4Gccz1k/e5b5ZSClsE0V1FOqd1nnzXtoMQnJrbJe', NULL, 'Moderator', 1, 'Active', NULL, '2025-11-03 21:37:54', '2025-11-03 16:27:28', '2025-11-03 21:37:54'),
(11, 'Mr. Store man', 'ext.ahan@gmail.com', '$2y$10$om82Uzx/YY5qYHaLjsggCOnRD1WzZfFhnP8RDnPtKOTqxlD/UUz9q', NULL, 'Store In-charge', 1, 'Active', NULL, '2025-11-03 21:52:59', '2025-11-03 20:48:45', '2025-11-03 21:52:59'),
(12, 'mr. admin in charge', 'ext.admin@gmail.com', '$2y$10$auCrlv.rs0c668wheszaKewFlVTFwXqKbqQYyFK6K8LqYPrARsGrO', NULL, 'Admin In-charge', 1, 'Active', NULL, '2025-11-05 18:15:25', '2025-11-05 18:15:05', '2025-11-05 18:15:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barcode_generation_logs`
--
ALTER TABLE `barcode_generation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `barcode_value` (`barcode_value`),
  ADD KEY `created_at` (`created_at`);

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
-- Indexes for table `grn`
--
ALTER TABLE `grn`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grn_number` (`grn_number`,`owner_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `status` (`status`),
  ADD KEY `received_date` (`received_date`),
  ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `grn_items`
--
ALTER TABLE `grn_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grn_id` (`grn_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `batch_number` (`batch_number`);

--
-- Indexes for table `inventory_audit_logs`
--
ALTER TABLE `inventory_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `action_type` (`action_type`),
  ADD KEY `performed_by` (`performed_by`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `reference_type` (`reference_type`,`reference_id`);

--
-- Indexes for table `inventory_forecasts`
--
ALTER TABLE `inventory_forecasts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `forecast_date` (`forecast_date`);

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
-- Indexes for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `batch_number` (`batch_number`,`owner_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `grn_id` (`grn_id`),
  ADD KEY `expiry_date` (`expiry_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `product_discounts`
--
ALTER TABLE `product_discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `start_date` (`start_date`,`end_date`),
  ADD KEY `fk_discounts_created_by` (`created_by`);

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
-- Indexes for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `alert_type` (`alert_type`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `supplier_code` (`supplier_code`,`owner_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `supplier_performance`
--
ALTER TABLE `supplier_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `grn_id` (`grn_id`);

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
-- AUTO_INCREMENT for table `barcode_generation_logs`
--
ALTER TABLE `barcode_generation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `booking_history`
--
ALTER TABLE `booking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `delivery_tracking_history`
--
ALTER TABLE `delivery_tracking_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `grn`
--
ALTER TABLE `grn`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `grn_items`
--
ALTER TABLE `grn_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_audit_logs`
--
ALTER TABLE `inventory_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_forecasts`
--
ALTER TABLE `inventory_forecasts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `product_batches`
--
ALTER TABLE `product_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_discounts`
--
ALTER TABLE `product_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `supplier_performance`
--
ALTER TABLE `supplier_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- Constraints for table `grn`
--
ALTER TABLE `grn`
  ADD CONSTRAINT `fk_grn_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grn_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_grn_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `grn_items`
--
ALTER TABLE `grn_items`
  ADD CONSTRAINT `fk_grn_items_grn` FOREIGN KEY (`grn_id`) REFERENCES `grn` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grn_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `inventory_audit_logs`
--
ALTER TABLE `inventory_audit_logs`
  ADD CONSTRAINT `fk_audit_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_audit_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_audit_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_forecasts`
--
ALTER TABLE `inventory_forecasts`
  ADD CONSTRAINT `fk_forecast_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_forecast_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD CONSTRAINT `fk_batches_grn` FOREIGN KEY (`grn_id`) REFERENCES `grn` (`id`),
  ADD CONSTRAINT `fk_batches_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_batches_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_discounts`
--
ALTER TABLE `product_discounts`
  ADD CONSTRAINT `fk_discounts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_discounts_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_discounts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_suppliers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_suppliers_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_performance`
--
ALTER TABLE `supplier_performance`
  ADD CONSTRAINT `fk_performance_grn` FOREIGN KEY (`grn_id`) REFERENCES `grn` (`id`),
  ADD CONSTRAINT `fk_performance_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_performance_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
