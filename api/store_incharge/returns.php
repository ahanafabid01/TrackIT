<?php
/**
 * Store In-charge API - Returns Management
 * Handles return requests, inspection, and restocking
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
 * GET - Fetch returns
 */
function handleGet($conn, $owner_id) {
    if (isset($_GET['id'])) {
        getReturnById($conn, $_GET['id'], $owner_id);
    } elseif (isset($_GET['return_number'])) {
        getReturnByNumber($conn, $_GET['return_number'], $owner_id);
    } else {
        getAllReturns($conn, $owner_id);
    }
}

/**
 * POST - Create new return request
 */
function handlePost($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['booking_id'])) {
        throw new Exception('Booking ID is required');
    }
    
    if (empty($data['return_reason'])) {
        throw new Exception('Return reason is required');
    }
    
    // Verify booking exists and is delivered
    $stmt = $conn->prepare("
        SELECT b.*, p.name as product_name
        FROM bookings b
        LEFT JOIN products p ON b.product_id = p.id
        WHERE b.id = ? AND b.owner_id = ? AND b.status = 'Delivered'
    ");
    $stmt->bind_param("ii", $data['booking_id'], $owner_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    if (!$booking) {
        throw new Exception('Booking not found or not in delivered status');
    }
    
    // Check if return already exists
    $stmt = $conn->prepare("SELECT id FROM returns WHERE booking_id = ?");
    $stmt->bind_param("i", $data['booking_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Return request already exists for this booking');
    }
    
    // Generate return number
    $return_number = 'RET-' . time() . '-' . rand(1000, 9999);
    
    // Create return
    $stmt = $conn->prepare("
        INSERT INTO returns 
        (booking_id, owner_id, return_number, return_reason, quantity_returned, 
         customer_comments, damage_images, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $quantity_returned = $data['quantity_returned'] ?? $booking['quantity'];
    $customer_comments = $data['customer_comments'] ?? null;
    $damage_images = isset($data['damage_images']) ? json_encode($data['damage_images']) : null;
    
    $stmt->bind_param(
        "iisssssi",
        $data['booking_id'],
        $owner_id,
        $return_number,
        $data['return_reason'],
        $quantity_returned,
        $customer_comments,
        $damage_images,
        $user_id
    );
    
    if ($stmt->execute()) {
        $return_id = $conn->insert_id;
        
        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Return Requested' WHERE id = ?");
        $stmt->bind_param("i", $data['booking_id']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Return request created successfully',
            'return_id' => $return_id,
            'return_number' => $return_number
        ]);
    } else {
        throw new Exception('Failed to create return request');
    }
}

/**
 * PUT - Update return status
 */
function handlePut($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['return_id'])) {
        throw new Exception('Return ID is required');
    }
    
    if (empty($data['action'])) {
        throw new Exception('Action is required');
    }
    
    // Get current return
    $stmt = $conn->prepare("
        SELECT r.*, b.product_id, b.quantity as booking_quantity, b.total_amount
        FROM returns r
        LEFT JOIN bookings b ON r.booking_id = b.id
        WHERE r.id = ? AND r.owner_id = ?
    ");
    $stmt->bind_param("ii", $data['return_id'], $owner_id);
    $stmt->execute();
    $return = $stmt->get_result()->fetch_assoc();
    
    if (!$return) {
        throw new Exception('Return not found');
    }
    
    switch ($data['action']) {
        case 'approve':
            approveReturn($conn, $return, $user_id);
            break;
        case 'reject':
            rejectReturn($conn, $return, $data, $user_id);
            break;
        case 'inspect':
            inspectReturn($conn, $return, $data, $user_id);
            break;
        case 'restock':
            restockReturn($conn, $return, $data, $user_id);
            break;
        case 'complete':
            completeReturn($conn, $return, $user_id);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * Approve return request
 */
function approveReturn($conn, $return, $user_id) {
    $stmt = $conn->prepare("
        UPDATE returns 
        SET return_status = 'Approved', approved_by = ?, approved_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $user_id, $return['id']);
    
    if ($stmt->execute()) {
        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Return Approved' WHERE id = ?");
        $stmt->bind_param("i", $return['booking_id']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Return approved successfully'
        ]);
    } else {
        throw new Exception('Failed to approve return');
    }
}

/**
 * Reject return request
 */
function rejectReturn($conn, $return, $data, $user_id) {
    $rejection_reason = $data['rejection_reason'] ?? 'No reason provided';
    
    $stmt = $conn->prepare("
        UPDATE returns 
        SET return_status = 'Rejected', 
            inspection_notes = CONCAT(COALESCE(inspection_notes, ''), '\n\nRejection: ', ?),
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("sii", $rejection_reason, $user_id, $return['id']);
    
    if ($stmt->execute()) {
        // Update booking status back to Delivered
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Delivered' WHERE id = ?");
        $stmt->bind_param("i", $return['booking_id']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Return rejected successfully'
        ]);
    } else {
        throw new Exception('Failed to reject return');
    }
}

/**
 * Inspect returned product
 */
function inspectReturn($conn, $return, $data, $user_id) {
    if (empty($data['condition_on_return'])) {
        throw new Exception('Product condition is required');
    }
    
    $updates = [];
    $values = [];
    $types = "";
    
    $updates[] = "return_status = 'Inspected'";
    $updates[] = "condition_on_return = ?";
    $values[] = $data['condition_on_return'];
    $types .= "s";
    
    if (isset($data['inspection_notes'])) {
        $updates[] = "inspection_notes = ?";
        $values[] = $data['inspection_notes'];
        $types .= "s";
    }
    
    if (isset($data['restocking_fee'])) {
        $updates[] = "restocking_fee = ?";
        $values[] = $data['restocking_fee'];
        $types .= "d";
    }
    
    if (isset($data['damage_images'])) {
        $updates[] = "damage_images = ?";
        $damage_json = json_encode($data['damage_images']);
        $values[] = $damage_json;
        $types .= "s";
    }
    
    $updates[] = "inspected_by = ?";
    $values[] = $user_id;
    $types .= "i";
    
    $updates[] = "inspected_at = NOW()";
    
    $sql = "UPDATE returns SET " . implode(", ", $updates) . " WHERE id = ?";
    $types .= "i";
    $values[] = $return['id'];
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Return inspection completed'
        ]);
    } else {
        throw new Exception('Failed to update inspection');
    }
}

/**
 * Restock returned product
 */
function restockReturn($conn, $return, $data, $user_id) {
    // Check if already restocked
    if ($return['restocked'] == 1) {
        throw new Exception('Product already restocked');
    }
    
    // Check if inspected
    if ($return['return_status'] !== 'Inspected') {
        throw new Exception('Return must be inspected before restocking');
    }
    
    // Only restock if condition is Good or Acceptable
    if (!in_array($return['condition_on_return'], ['Good', 'Acceptable'])) {
        throw new Exception('Product condition does not allow restocking');
    }
    
    // Update product stock
    $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
    $stmt->bind_param("ii", $return['quantity_returned'], $return['product_id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update product stock');
    }
    
    // Update return
    $stmt = $conn->prepare("
        UPDATE returns 
        SET return_status = 'Restocked', 
            restocked = 1,
            restocked_by = ?,
            restocked_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $user_id, $return['id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Product restocked successfully',
            'quantity_added' => $return['quantity_returned']
        ]);
    } else {
        throw new Exception('Failed to update return status');
    }
}

/**
 * Complete return (refund processed)
 */
function completeReturn($conn, $return, $user_id) {
    $stmt = $conn->prepare("
        UPDATE returns 
        SET return_status = 'Completed'
        WHERE id = ?
    ");
    $stmt->bind_param("i", $return['id']);
    
    if ($stmt->execute()) {
        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET status = 'Returned' WHERE id = ?");
        $stmt->bind_param("i", $return['booking_id']);
        $stmt->execute();
        
        // Update customer statistics (reduce total_spent if refunded)
        $stmt = $conn->prepare("
            UPDATE customers c
            SET total_spent = (
                SELECT COALESCE(SUM(total_amount), 0)
                FROM bookings
                WHERE customer_id = c.id AND status = 'Delivered'
            )
            WHERE id = (SELECT customer_id FROM bookings WHERE id = ?)
        ");
        $stmt->bind_param("i", $return['booking_id']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Return completed successfully'
        ]);
    } else {
        throw new Exception('Failed to complete return');
    }
}

/**
 * Get all returns with pagination
 */
function getAllReturns($conn, $owner_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $whereClause = "WHERE r.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND r.return_status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM returns r $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get returns
    $query = "
        SELECT r.*, 
               b.booking_number, b.total_amount,
               c.name as customer_name, c.phone as customer_phone,
               p.name as product_name, p.sku,
               u1.name as created_by_name,
               u2.name as approved_by_name,
               u3.name as inspected_by_name,
               u4.name as restocked_by_name
        FROM returns r
        LEFT JOIN bookings b ON r.booking_id = b.id
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        LEFT JOIN users u1 ON r.created_by = u1.id
        LEFT JOIN users u2 ON r.approved_by = u2.id
        LEFT JOIN users u3 ON r.inspected_by = u3.id
        LEFT JOIN users u4 ON r.restocked_by = u4.id
        $whereClause
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $returns = [];
    while ($row = $result->fetch_assoc()) {
        // Parse damage images JSON
        if ($row['damage_images']) {
            $row['damage_images'] = json_decode($row['damage_images'], true);
        }
        $returns[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'returns' => $returns,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get return by ID
 */
function getReturnById($conn, $id, $owner_id) {
    $stmt = $conn->prepare("
        SELECT r.*, 
               b.booking_number, b.total_amount, b.quantity as booking_quantity,
               c.name as customer_name, c.phone as customer_phone, c.email as customer_email, c.address,
               p.name as product_name, p.sku, p.description,
               u1.name as created_by_name,
               u2.name as approved_by_name,
               u3.name as inspected_by_name,
               u4.name as restocked_by_name
        FROM returns r
        LEFT JOIN bookings b ON r.booking_id = b.id
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        LEFT JOIN users u1 ON r.created_by = u1.id
        LEFT JOIN users u2 ON r.approved_by = u2.id
        LEFT JOIN users u3 ON r.inspected_by = u3.id
        LEFT JOIN users u4 ON r.restocked_by = u4.id
        WHERE r.id = ? AND r.owner_id = ?
    ");
    $stmt->bind_param("ii", $id, $owner_id);
    $stmt->execute();
    $return = $stmt->get_result()->fetch_assoc();
    
    if (!$return) {
        throw new Exception('Return not found');
    }
    
    // Parse damage images JSON
    if ($return['damage_images']) {
        $return['damage_images'] = json_decode($return['damage_images'], true);
    }
    
    echo json_encode([
        'success' => true,
        'return' => $return
    ]);
}

/**
 * Get return by return number
 */
function getReturnByNumber($conn, $return_number, $owner_id) {
    $stmt = $conn->prepare("
        SELECT r.*, 
               b.booking_number,
               c.name as customer_name,
               p.name as product_name
        FROM returns r
        LEFT JOIN bookings b ON r.booking_id = b.id
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        WHERE r.return_number = ? AND r.owner_id = ?
    ");
    $stmt->bind_param("si", $return_number, $owner_id);
    $stmt->execute();
    $return = $stmt->get_result()->fetch_assoc();
    
    if (!$return) {
        throw new Exception('Return not found');
    }
    
    // Parse damage images JSON
    if ($return['damage_images']) {
        $return['damage_images'] = json_decode($return['damage_images'], true);
    }
    
    echo json_encode([
        'success' => true,
        'return' => $return
    ]);
}
