<?php
/**
 * Moderator API - Booking Management
 * Handles all booking-related operations
 */

// Start output buffering to prevent any whitespace issues
ob_start();

require_once '../../config/config.php';

// Check if user is logged in and has proper role
requireRole(['Moderator', 'Owner', 'Store In-charge']);

// Clean any output that may have leaked
ob_clean();

header('Content-Type: application/json');

$owner_id = getOwnerId();
$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($conn, $owner_id);
            break;
        case 'POST':
            handlePost($conn, $owner_id, $user_id);
            break;
        case 'PUT':
            handlePut($conn, $owner_id, $user_id);
            break;
        case 'DELETE':
            handleDelete($conn, $owner_id);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    ob_clean(); // Clear any output before sending error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Flush output buffer
ob_end_flush();


/**
 * GET - Fetch bookings
 */
function handleGet($conn, $owner_id) {
    if (isset($_GET['id'])) {
        getBookingById($conn, $_GET['id'], $owner_id);
    } elseif (isset($_GET['stats'])) {
        getBookingStats($conn, $owner_id);
    } else {
        getAllBookings($conn, $owner_id);
    }
}

/**
 * POST - Create new booking
 */
function handlePost($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['customer_id']) || empty($data['product_id']) || empty($data['quantity'])) {
        throw new Exception('Customer, product, and quantity are required');
    }
    
    // Get product details and check availability
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['product_id'], $owner_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    if ($product['status'] !== 'Active') {
        throw new Exception('Product is not available');
    }
    
    if ($product['stock_quantity'] < $data['quantity']) {
        throw new Exception('Insufficient stock. Available: ' . $product['stock_quantity']);
    }
    
    // Verify customer belongs to this owner
    $stmt = $conn->prepare("SELECT id FROM customers WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['customer_id'], $owner_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Customer not found');
    }
    
    // Generate booking number
    $stmt = $conn->prepare("SELECT booking_number FROM bookings WHERE owner_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $lastNumber = intval(substr($result['booking_number'], 3));
        $booking_number = 'BK-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $booking_number = 'BK-001';
    }
    
    // Calculate totals
    $unit_price = isset($data['unit_price']) ? $data['unit_price'] : $product['price'];
    $total_amount = $unit_price * $data['quantity'];
    
    // Insert booking
    $stmt = $conn->prepare("
        INSERT INTO bookings 
        (booking_number, owner_id, customer_id, product_id, quantity, unit_price, total_amount, 
         status, priority, booking_date, delivery_date, notes, internal_notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Prepare variables for bind_param (cannot use ?? operator directly)
    $status = $data['status'] ?? 'Pending';
    $priority = $data['priority'] ?? 'Normal';
    $booking_date = $data['booking_date'] ?? date('Y-m-d');
    $delivery_date = $data['delivery_date'] ?? null;
    $notes = $data['notes'] ?? null;
    $internal_notes = $data['internal_notes'] ?? null;
    $customer_id = $data['customer_id'];
    $product_id = $data['product_id'];
    $quantity = $data['quantity'];
    
    $stmt->bind_param(
        "siiiddsssssssi",
        $booking_number,
        $owner_id,
        $customer_id,
        $product_id,
        $quantity,
        $unit_price,
        $total_amount,
        $status,
        $priority,
        $booking_date,
        $delivery_date,
        $notes,
        $internal_notes,
        $user_id
    );
    
    if ($stmt->execute()) {
        $booking_id = $conn->insert_id;
        
        // Update product stock - deduct the booked quantity
        $stmt = $conn->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity - ? 
            WHERE id = ? AND owner_id = ?
        ");
        $stmt->bind_param("iii", $quantity, $product_id, $owner_id);
        $stmt->execute();
        
        // Add to booking history
        $stmt = $conn->prepare("
            INSERT INTO booking_history (booking_id, new_status, changed_by, notes)
            VALUES (?, ?, ?, ?)
        ");
        $historyNote = "Booking created";
        $stmt->bind_param("isis", $booking_id, $status, $user_id, $historyNote);
        $stmt->execute();
        
        // Update customer statistics
        updateCustomerStats($conn, $customer_id);
        
        // Create automatic reminder if pending
        if ($status === 'Pending') {
            $reminderDate = date('Y-m-d H:i:s', strtotime('+1 day'));
            $stmt = $conn->prepare("
                INSERT INTO booking_reminders (booking_id, reminder_type, reminder_date, message, status)
                VALUES (?, 'Follow-up', ?, 'Follow up on pending booking', 'Pending')
            ");
            $stmt->bind_param("is", $booking_id, $reminderDate);
            $stmt->execute();
        }
        
        // Fetch the created booking
        $stmt = $conn->prepare("
            SELECT b.*, c.name as customer_name, c.phone as customer_phone,
                   p.name as product_name, p.sku, u.name as created_by_name
            FROM bookings b
            LEFT JOIN customers c ON b.customer_id = c.id
            LEFT JOIN products p ON b.product_id = p.id
            LEFT JOIN users u ON b.created_by = u.id
            WHERE b.id = ?
        ");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking created successfully',
            'booking' => $booking
        ]);
    } else {
        throw new Exception('Failed to create booking');
    }
}

/**
 * PUT - Update booking
 */
function handlePut($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('Booking ID is required');
    }
    
    // Get current booking
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['id'], $owner_id);
    $stmt->execute();
    $currentBooking = $stmt->get_result()->fetch_assoc();
    
    if (!$currentBooking) {
        throw new Exception('Booking not found');
    }
    
    $previousStatus = $currentBooking['status'];
    
    // If changing product or quantity, check stock
    if (isset($data['product_id']) || isset($data['quantity'])) {
        $product_id = $data['product_id'] ?? $currentBooking['product_id'];
        $quantity = $data['quantity'] ?? $currentBooking['quantity'];
        
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND owner_id = ?");
        $stmt->bind_param("ii", $product_id, $owner_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        if ($product['stock_quantity'] < $quantity) {
            throw new Exception('Insufficient stock. Available: ' . $product['stock_quantity']);
        }
        
        // Recalculate total if quantity changed
        if (isset($data['quantity'])) {
            $data['total_amount'] = ($data['unit_price'] ?? $currentBooking['unit_price']) * $data['quantity'];
        }
    }
    
    // Build update query dynamically
    $updateFields = [];
    $types = "";
    $values = [];
    
    $allowedFields = ['customer_id', 'product_id', 'quantity', 'unit_price', 'total_amount', 
                      'status', 'priority', 'booking_date', 'delivery_date', 'notes', 
                      'internal_notes', 'assigned_to'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            
            if (in_array($field, ['unit_price', 'total_amount'])) {
                $types .= "d";
            } elseif (in_array($field, ['customer_id', 'product_id', 'quantity', 'assigned_to'])) {
                $types .= "i";
            } else {
                $types .= "s";
            }
            
            $values[] = $data[$field];
        }
    }
    
    if (empty($updateFields)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE bookings SET " . implode(", ", $updateFields) . " WHERE id = ? AND owner_id = ?";
    $types .= "ii";
    $values[] = $data['id'];
    $values[] = $owner_id;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        // Add to booking history if status changed
        if (isset($data['status']) && $data['status'] !== $previousStatus) {
            $stmt = $conn->prepare("
                INSERT INTO booking_history (booking_id, previous_status, new_status, changed_by, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $historyNote = $data['history_note'] ?? "Status changed from $previousStatus to {$data['status']}";
            $stmt->bind_param("issis", $data['id'], $previousStatus, $data['status'], $user_id, $historyNote);
            $stmt->execute();
            
            // Restore stock if booking is cancelled or rejected
            if (in_array($data['status'], ['Cancelled', 'Rejected']) && 
                !in_array($previousStatus, ['Cancelled', 'Rejected'])) {
                $stmt = $conn->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity + ? 
                    WHERE id = ? AND owner_id = ?
                ");
                $stmt->bind_param("iii", $currentBooking['quantity'], $currentBooking['product_id'], $owner_id);
                $stmt->execute();
            }
            
            // Update customer statistics when status changes (especially for Delivered status)
            updateCustomerStats($conn, $currentBooking['customer_id']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update booking');
    }
}

/**
 * DELETE - Delete booking
 */
function handleDelete($conn, $owner_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('Booking ID is required');
    }
    
    // Only allow deletion of cancelled or rejected bookings
    $stmt = $conn->prepare("
        SELECT b.*, c.id as customer_id 
        FROM bookings b
        LEFT JOIN customers c ON b.customer_id = c.id
        WHERE b.id = ? AND b.owner_id = ?
    ");
    $stmt->bind_param("ii", $data['id'], $owner_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    if (!in_array($booking['status'], ['Cancelled', 'Rejected'])) {
        throw new Exception('Only cancelled or rejected bookings can be deleted');
    }
    
    // Note: Stock was already restored when status changed to Cancelled/Rejected
    // No need to restore again unless the status was never properly changed
    
    // Delete booking (will cascade delete reminders and history)
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['id'], $owner_id);
    
    if ($stmt->execute()) {
        // Update customer statistics after deletion
        updateCustomerStats($conn, $booking['customer_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete booking');
    }
}

/**
 * Get all bookings with pagination and filters
 */
function getAllBookings($conn, $owner_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    
    // Build query
    $whereClause = "WHERE b.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND b.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($search) {
        $whereClause .= " AND (b.booking_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR p.name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ssss";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM bookings b 
                   LEFT JOIN customers c ON b.customer_id = c.id
                   LEFT JOIN products p ON b.product_id = p.id
                   $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get bookings
    $query = "
        SELECT b.*, 
               c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
               p.name as product_name, p.sku, p.stock_quantity,
               u.name as created_by_name
        FROM bookings b
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        LEFT JOIN users u ON b.created_by = u.id
        $whereClause
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get booking by ID
 */
function getBookingById($conn, $id, $owner_id) {
    $stmt = $conn->prepare("
        SELECT b.*, 
               c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
               c.address as customer_address, c.city as customer_city,
               p.name as product_name, p.sku, p.stock_quantity, p.price as product_price,
               u.name as created_by_name,
               a.name as assigned_to_name
        FROM bookings b
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        LEFT JOIN users u ON b.created_by = u.id
        LEFT JOIN users a ON b.assigned_to = a.id
        WHERE b.id = ? AND b.owner_id = ?
    ");
    $stmt->bind_param("ii", $id, $owner_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Get booking history
    $stmt = $conn->prepare("
        SELECT bh.*, u.name as changed_by_name
        FROM booking_history bh
        LEFT JOIN users u ON bh.changed_by = u.id
        WHERE bh.booking_id = ?
        ORDER BY bh.created_at DESC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $booking['history'] = $history;
    
    echo json_encode([
        'success' => true,
        'booking' => $booking
    ]);
}

/**
 * Get booking statistics
 */
function getBookingStats($conn, $owner_id) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'Ready' THEN 1 ELSE 0 END) as ready,
            SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = 'Delivered' THEN total_amount ELSE 0 END) as total_revenue
        FROM bookings
        WHERE owner_id = ?
    ");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    
    // Get customer count
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT customer_id) as customer_count FROM bookings WHERE owner_id = ?");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $customerCount = $stmt->get_result()->fetch_assoc();
    $stats['customer_count'] = $customerCount['customer_count'];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}

/**
 * Update customer statistics (total_orders and total_spent)
 * Call this whenever a booking is created, updated, or deleted
 */
function updateCustomerStats($conn, $customer_id) {
    // Count total bookings (all statuses)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_orders,
               SUM(CASE WHEN status = 'Delivered' THEN total_amount ELSE 0 END) as total_spent
        FROM bookings 
        WHERE customer_id = ?
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    
    // Update customer record
    $stmt = $conn->prepare("
        UPDATE customers 
        SET total_orders = ?, total_spent = ?
        WHERE id = ?
    ");
    $total_orders = $stats['total_orders'];
    $total_spent = $stats['total_spent'] ?? 0;
    $stmt->bind_param("idi", $total_orders, $total_spent, $customer_id);
    $stmt->execute();
}
