<?php
/**
 * Store In-charge API - Delivery Management
 * Handles delivery creation, tracking, and status updates
 */

ob_start();
require_once '../../config/config.php';
requireRole(['Store In-charge', 'Owner']);
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
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

ob_end_flush();

/**
 * GET - Fetch deliveries
 */
function handleGet($conn, $owner_id) {
    if (isset($_GET['id'])) {
        getDeliveryById($conn, $_GET['id'], $owner_id);
    } elseif (isset($_GET['tracking'])) {
        getDeliveryByTracking($conn, $_GET['tracking'], $owner_id);
    } else {
        getAllDeliveries($conn, $owner_id);
    }
}

/**
 * POST - Create new delivery
 */
function handlePost($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['booking_id'])) {
        throw new Exception('Booking ID is required');
    }
    
    // Verify booking exists and is confirmed
    $stmt = $conn->prepare("
        SELECT b.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address
        FROM bookings b
        LEFT JOIN customers c ON b.customer_id = c.id
        WHERE b.id = ? AND b.owner_id = ? AND b.status IN ('Confirmed', 'Processing')
    ");
    $stmt->bind_param("ii", $data['booking_id'], $owner_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    if (!$booking) {
        throw new Exception('Booking not found or not in deliverable status');
    }
    
    // Check if delivery already exists
    $stmt = $conn->prepare("SELECT id FROM deliveries WHERE booking_id = ?");
    $stmt->bind_param("i", $data['booking_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Delivery already exists for this booking');
    }
    
    // Generate tracking number if not provided
    $tracking_number = $data['tracking_number'] ?? null;
    if (!$tracking_number && !empty($data['courier_name'])) {
        $tracking_number = strtoupper($data['courier_name']) . '-' . time() . '-' . rand(1000, 9999);
    }
    
    // Create delivery
    $stmt = $conn->prepare("
        INSERT INTO deliveries 
        (booking_id, owner_id, tracking_number, courier_name, dispatch_date, expected_delivery_date,
         delivery_address, recipient_name, recipient_phone, delivery_notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $dispatch_date = $data['dispatch_date'] ?? date('Y-m-d');
    $expected_delivery_date = $data['expected_delivery_date'] ?? null;
    $delivery_address = $data['delivery_address'] ?? $booking['customer_address'];
    $recipient_name = $data['recipient_name'] ?? $booking['customer_name'];
    $recipient_phone = $data['recipient_phone'] ?? $booking['customer_phone'];
    $courier_name = $data['courier_name'] ?? null;
    $delivery_notes = $data['delivery_notes'] ?? null;
    
    $stmt->bind_param(
        "iissssssssi",
        $data['booking_id'],
        $owner_id,
        $tracking_number,
        $courier_name,
        $dispatch_date,
        $expected_delivery_date,
        $delivery_address,
        $recipient_name,
        $recipient_phone,
        $delivery_notes,
        $user_id
    );
    
    if ($stmt->execute()) {
        $delivery_id = $conn->insert_id;
        
        // Update booking status to Processing
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Processing' WHERE id = ?");
        $stmt->bind_param("i", $data['booking_id']);
        $stmt->execute();
        
        // Add tracking history
        $stmt = $conn->prepare("
            INSERT INTO delivery_tracking_history (delivery_id, status, description, updated_by)
            VALUES (?, 'Dispatched', 'Package dispatched from warehouse', ?)
        ");
        $dispatched = 'Dispatched';
        $stmt->bind_param("isi", $delivery_id, $dispatched, $user_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Delivery created successfully',
            'delivery_id' => $delivery_id,
            'tracking_number' => $tracking_number
        ]);
    } else {
        throw new Exception('Failed to create delivery');
    }
}

/**
 * PUT - Update delivery status
 */
function handlePut($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['delivery_id'])) {
        throw new Exception('Delivery ID is required');
    }
    
    // Get current delivery
    $stmt = $conn->prepare("SELECT * FROM deliveries WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['delivery_id'], $owner_id);
    $stmt->execute();
    $delivery = $stmt->get_result()->fetch_assoc();
    
    if (!$delivery) {
        throw new Exception('Delivery not found');
    }
    
    $updates = [];
    $values = [];
    $types = "";
    
    // Update delivery status
    if (isset($data['delivery_status'])) {
        $updates[] = "delivery_status = ?";
        $values[] = $data['delivery_status'];
        $types .= "s";
        
        // Add to tracking history
        $stmt = $conn->prepare("
            INSERT INTO delivery_tracking_history (delivery_id, status, location, description, updated_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $location = $data['location'] ?? null;
        $description = $data['status_description'] ?? "Status updated to {$data['delivery_status']}";
        $stmt->bind_param("isssi", $data['delivery_id'], $data['delivery_status'], $location, $description, $user_id);
        $stmt->execute();
        
        // If delivered, update booking and set actual delivery date
        if ($data['delivery_status'] === 'Delivered') {
            $updates[] = "actual_delivery_date = ?";
            $actual_date = date('Y-m-d');
            $values[] = $actual_date;
            $types .= "s";
            
            // Update booking status
            $stmt = $conn->prepare("UPDATE bookings SET status = 'Delivered' WHERE id = ?");
            $stmt->bind_param("i", $delivery['booking_id']);
            $stmt->execute();
            
            // Update customer statistics
            updateCustomerStatsFromBooking($conn, $delivery['booking_id']);
        }
    }
    
    // Update proof of delivery
    if (isset($data['proof_of_delivery'])) {
        $updates[] = "proof_of_delivery = ?";
        $values[] = $data['proof_of_delivery'];
        $types .= "s";
    }
    
    if (empty($updates)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE deliveries SET " . implode(", ", $updates) . " WHERE id = ? AND owner_id = ?";
    $types .= "ii";
    $values[] = $data['delivery_id'];
    $values[] = $owner_id;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Delivery updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update delivery');
    }
}

/**
 * Get all deliveries with pagination
 */
function getAllDeliveries($conn, $owner_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $whereClause = "WHERE d.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND d.delivery_status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM deliveries d $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get deliveries
    $query = "
        SELECT d.*, 
               b.booking_number, b.status as booking_status,
               c.name as customer_name, c.phone as customer_phone,
               p.name as product_name, b.quantity,
               u.name as created_by_name
        FROM deliveries d
        LEFT JOIN bookings b ON d.booking_id = b.id
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        LEFT JOIN users u ON d.created_by = u.id
        $whereClause
        ORDER BY d.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $deliveries = [];
    while ($row = $result->fetch_assoc()) {
        $deliveries[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'deliveries' => $deliveries,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get delivery by ID
 */
function getDeliveryById($conn, $id, $owner_id) {
    $stmt = $conn->prepare("
        SELECT d.*, 
               b.booking_number, b.status as booking_status, b.total_amount,
               c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
               p.name as product_name, p.sku, b.quantity, b.unit_price,
               u.name as created_by_name
        FROM deliveries d
        LEFT JOIN bookings b ON d.booking_id = b.id
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        LEFT JOIN users u ON d.created_by = u.id
        WHERE d.id = ? AND d.owner_id = ?
    ");
    $stmt->bind_param("ii", $id, $owner_id);
    $stmt->execute();
    $delivery = $stmt->get_result()->fetch_assoc();
    
    if (!$delivery) {
        throw new Exception('Delivery not found');
    }
    
    // Get tracking history
    $stmt = $conn->prepare("
        SELECT th.*, u.name as updated_by_name
        FROM delivery_tracking_history th
        LEFT JOIN users u ON th.updated_by = u.id
        WHERE th.delivery_id = ?
        ORDER BY th.created_at ASC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $delivery['tracking_history'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'delivery' => $delivery
    ]);
}

/**
 * Get delivery by tracking number
 */
function getDeliveryByTracking($conn, $tracking, $owner_id) {
    $stmt = $conn->prepare("
        SELECT d.*, 
               b.booking_number,
               c.name as customer_name,
               p.name as product_name
        FROM deliveries d
        LEFT JOIN bookings b ON d.booking_id = b.id
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        WHERE d.tracking_number = ? AND d.owner_id = ?
    ");
    $stmt->bind_param("si", $tracking, $owner_id);
    $stmt->execute();
    $delivery = $stmt->get_result()->fetch_assoc();
    
    if (!$delivery) {
        throw new Exception('Delivery not found');
    }
    
    // Get tracking history
    $stmt = $conn->prepare("
        SELECT * FROM delivery_tracking_history 
        WHERE delivery_id = ? 
        ORDER BY created_at ASC
    ");
    $stmt->bind_param("i", $delivery['id']);
    $stmt->execute();
    $delivery['tracking_history'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'delivery' => $delivery
    ]);
}

/**
 * Helper function to update customer stats
 */
function updateCustomerStatsFromBooking($conn, $booking_id) {
    $stmt = $conn->prepare("SELECT customer_id FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        // Update customer total_spent
        $stmt = $conn->prepare("
            UPDATE customers c
            SET total_spent = (
                SELECT COALESCE(SUM(total_amount), 0)
                FROM bookings
                WHERE customer_id = c.id AND status = 'Delivered'
            )
            WHERE id = ?
        ");
        $stmt->bind_param("i", $result['customer_id']);
        $stmt->execute();
    }
}
