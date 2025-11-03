<?php
/**
 * Moderator API - Data Export
 * Handles exporting customer and booking data in various formats
 */

require_once '../../config/config.php';

// Check if user is logged in
requireRole(['Moderator', 'Owner', 'Accountant']);

$owner_id = getOwnerId();
$user_id = $_SESSION['user_id'];

try {
    if (!isset($_GET['type'])) {
        throw new Exception('Export type is required');
    }
    
    $type = $_GET['type'];
    $format = $_GET['format'] ?? 'csv'; // csv, excel, pdf
    
    switch ($type) {
        case 'customers':
            exportCustomers($conn, $owner_id, $format);
            break;
        case 'bookings':
            exportBookings($conn, $owner_id, $format);
            break;
        case 'customer_history':
            if (!isset($_GET['customer_id'])) {
                throw new Exception('Customer ID is required');
            }
            exportCustomerHistory($conn, $owner_id, $_GET['customer_id'], $format);
            break;
        default:
            throw new Exception('Invalid export type');
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Export customers data
 */
function exportCustomers($conn, $owner_id, $format) {
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $whereClause = "WHERE c.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND c.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $query = "
        SELECT c.id, c.name, c.email, c.phone, c.address, c.city, c.state, 
               c.postal_code, c.company, c.status, c.total_orders, c.total_spent,
               c.created_at,
               (SELECT COUNT(*) FROM bookings WHERE customer_id = c.id) as booking_count,
               (SELECT COUNT(*) FROM bookings WHERE customer_id = c.id AND status = 'Delivered') as completed_bookings
        FROM customers c
        $whereClause
        ORDER BY c.name ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if ($format === 'csv') {
        exportToCSV($customers, 'customers_' . date('Y-m-d'), [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'postal_code' => 'Postal Code',
            'company' => 'Company',
            'status' => 'Status',
            'total_orders' => 'Total Orders',
            'total_spent' => 'Total Spent',
            'booking_count' => 'Booking Count',
            'completed_bookings' => 'Completed Bookings',
            'created_at' => 'Created At'
        ]);
    } else {
        throw new Exception('Format not supported yet');
    }
}

/**
 * Export bookings data
 */
function exportBookings($conn, $owner_id, $format) {
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    
    $whereClause = "WHERE b.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND b.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($startDate) {
        $whereClause .= " AND b.booking_date >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate) {
        $whereClause .= " AND b.booking_date <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    
    $query = "
        SELECT b.booking_number, b.booking_date, b.delivery_date,
               c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
               p.name as product_name, p.sku,
               b.quantity, b.unit_price, b.total_amount,
               b.status, b.priority, b.notes,
               u.name as created_by
        FROM bookings b
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        LEFT JOIN users u ON b.created_by = u.id
        $whereClause
        ORDER BY b.booking_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if ($format === 'csv') {
        exportToCSV($bookings, 'bookings_' . date('Y-m-d'), [
            'booking_number' => 'Booking #',
            'booking_date' => 'Booking Date',
            'delivery_date' => 'Delivery Date',
            'customer_name' => 'Customer Name',
            'customer_phone' => 'Customer Phone',
            'customer_email' => 'Customer Email',
            'product_name' => 'Product Name',
            'sku' => 'SKU',
            'quantity' => 'Quantity',
            'unit_price' => 'Unit Price',
            'total_amount' => 'Total Amount',
            'status' => 'Status',
            'priority' => 'Priority',
            'notes' => 'Notes',
            'created_by' => 'Created By'
        ]);
    } else {
        throw new Exception('Format not supported yet');
    }
}

/**
 * Export customer history
 */
function exportCustomerHistory($conn, $owner_id, $customer_id, $format) {
    // Verify customer belongs to this owner
    $stmt = $conn->prepare("SELECT name FROM customers WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $customer_id, $owner_id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    
    if (!$customer) {
        throw new Exception('Customer not found');
    }
    
    $query = "
        SELECT b.booking_number, b.booking_date, b.delivery_date,
               p.name as product_name, p.sku,
               b.quantity, b.unit_price, b.total_amount,
               b.status, b.priority, b.notes
        FROM bookings b
        LEFT JOIN products p ON b.product_id = p.id
        WHERE b.customer_id = ?
        ORDER BY b.booking_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if ($format === 'csv') {
        exportToCSV($bookings, $customer['name'] . '_history_' . date('Y-m-d'), [
            'booking_number' => 'Booking #',
            'booking_date' => 'Booking Date',
            'delivery_date' => 'Delivery Date',
            'product_name' => 'Product Name',
            'sku' => 'SKU',
            'quantity' => 'Quantity',
            'unit_price' => 'Unit Price',
            'total_amount' => 'Total Amount',
            'status' => 'Status',
            'priority' => 'Priority',
            'notes' => 'Notes'
        ]);
    } else {
        throw new Exception('Format not supported yet');
    }
}

/**
 * Export data to CSV format
 */
function exportToCSV($data, $filename, $columns) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write header
    fputcsv($output, array_values($columns));
    
    // Write data
    foreach ($data as $row) {
        $csvRow = [];
        foreach (array_keys($columns) as $key) {
            $csvRow[] = $row[$key] ?? '';
        }
        fputcsv($output, $csvRow);
    }
    
    fclose($output);
    exit();
}
