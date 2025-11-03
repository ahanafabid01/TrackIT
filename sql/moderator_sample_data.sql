-- Sample data for Moderator Module
-- Insert this after creating the tables

-- Sample Customers (for owner_id = 1)
INSERT INTO `customers` (`owner_id`, `name`, `email`, `phone`, `address`, `city`, `state`, `postal_code`, `company`, `status`, `total_orders`, `total_spent`, `created_by`) VALUES
(1, 'John Doe', 'john.doe@example.com', '+880 1234567890', '123 Main St, Apt 4B', 'Dhaka', 'Dhaka', '1205', 'Tech Solutions Ltd', 'Active', 12, 45600.00, 10),
(1, 'Sarah Miller', 'sarah.miller@example.com', '+880 1987654321', '456 Oak Avenue', 'Chittagong', 'Chittagong', '4000', 'Miller Enterprises', 'Active', 8, 32400.00, 10),
(1, 'Ahmed Khan', 'ahmed.khan@example.com', '+880 1555123456', '789 Park Road', 'Dhaka', 'Dhaka', '1207', NULL, 'Active', 5, 18900.00, 10),
(1, 'Lisa Chen', 'lisa.chen@example.com', '+880 1666789012', '321 Hill Street', 'Sylhet', 'Sylhet', '3100', 'Chen Trading Co', 'Active', 15, 56700.00, 10),
(1, 'Robert Wilson', 'robert.wilson@example.com', '+880 1777345678', '654 River View', 'Dhaka', 'Dhaka', '1212', NULL, 'Inactive', 3, 9800.00, 10);

-- Sample Products (for owner_id = 1)
INSERT INTO `products` (`owner_id`, `name`, `sku`, `description`, `category`, `price`, `cost`, `stock_quantity`, `low_stock_threshold`, `unit`, `status`, `created_by`) VALUES
(1, 'Premium Laptop Stand', 'SKU-001', 'Ergonomic aluminum laptop stand with adjustable height', 'Office Accessories', 2500.00, 1800.00, 45, 10, 'pieces', 'Active', 1),
(1, 'Wireless Mouse Pro', 'SKU-002', 'Bluetooth wireless mouse with ergonomic design', 'Computer Peripherals', 1200.00, 800.00, 8, 15, 'pieces', 'Active', 1),
(1, 'USB-C Hub 7-in-1', 'SKU-003', 'Multi-port USB-C hub with HDMI, USB 3.0, SD card reader', 'Computer Peripherals', 3500.00, 2400.00, 120, 20, 'pieces', 'Active', 1),
(1, 'Mechanical Keyboard RGB', 'SKU-004', 'Gaming mechanical keyboard with RGB backlighting', 'Computer Peripherals', 4500.00, 3200.00, 0, 10, 'pieces', 'Active', 1),
(1, 'Webcam 1080p HD', 'SKU-005', 'Full HD webcam with built-in microphone', 'Computer Peripherals', 3200.00, 2100.00, 32, 15, 'pieces', 'Active', 1),
(1, 'Monitor Screen Protector', 'SKU-006', 'Anti-glare screen protector for 24-inch monitors', 'Office Accessories', 800.00, 450.00, 78, 25, 'pieces', 'Active', 1);

-- Sample Bookings (for owner_id = 1, created by moderator user_id = 10)
INSERT INTO `bookings` (`booking_number`, `owner_id`, `customer_id`, `product_id`, `quantity`, `unit_price`, `total_amount`, `status`, `priority`, `booking_date`, `delivery_date`, `notes`, `created_by`, `assigned_to`) VALUES
('BK-001', 1, 1, 1, 2, 2500.00, 5000.00, 'Confirmed', 'Normal', '2025-11-03', '2025-11-10', 'Customer wants delivery before 5 PM', 10, NULL),
('BK-002', 1, 2, 2, 5, 1200.00, 6000.00, 'Pending', 'High', '2025-11-02', '2025-11-08', 'Urgent order for office setup', 10, NULL),
('BK-003', 1, 1, 3, 3, 3500.00, 10500.00, 'Processing', 'Normal', '2025-11-01', '2025-11-07', NULL, 10, NULL),
('BK-004', 1, 3, 5, 1, 3200.00, 3200.00, 'Confirmed', 'Normal', '2025-10-30', '2025-11-05', 'Gift wrap requested', 10, NULL),
('BK-005', 1, 4, 6, 10, 800.00, 8000.00, 'Delivered', 'Low', '2025-10-28', '2025-11-02', 'Bulk order completed', 10, NULL),
('BK-006', 1, 2, 4, 2, 4500.00, 9000.00, 'Cancelled', 'Normal', '2025-10-27', NULL, 'Customer cancelled - out of stock', 10, NULL),
('BK-007', 1, 4, 1, 4, 2500.00, 10000.00, 'Ready', 'High', '2025-11-03', '2025-11-06', 'Ready for pickup', 10, NULL);

-- Sample Booking Reminders
INSERT INTO `booking_reminders` (`booking_id`, `reminder_type`, `reminder_date`, `message`, `status`) VALUES
(1, 'Confirmation', '2025-11-04 10:00:00', 'Please confirm your booking for Premium Laptop Stand (2 units)', 'Pending'),
(2, 'Follow-up', '2025-11-04 14:00:00', 'Following up on your urgent order', 'Pending'),
(3, 'Delivery', '2025-11-07 09:00:00', 'Your order is scheduled for delivery today', 'Pending');

-- Sample Customer Feedback
INSERT INTO `customer_feedback` (`owner_id`, `customer_id`, `booking_id`, `rating`, `feedback`, `feedback_type`, `status`) VALUES
(1, 1, 1, 5, 'Excellent product quality and fast delivery!', 'Product', 'Reviewed'),
(1, 4, 5, 5, 'Very professional service. Will order again.', 'Service', 'Reviewed'),
(1, 2, 6, 3, 'Product was out of stock. Should have been notified earlier.', 'Issue', 'Resolved');

-- Sample Booking History
INSERT INTO `booking_history` (`booking_id`, `previous_status`, `new_status`, `changed_by`, `notes`) VALUES
(1, 'Pending', 'Confirmed', 10, 'Customer confirmed via phone'),
(3, 'Confirmed', 'Processing', 10, 'Sent to warehouse for processing'),
(5, 'Ready', 'Delivered', 10, 'Successfully delivered to customer'),
(6, 'Pending', 'Cancelled', 10, 'Product out of stock, customer informed');
