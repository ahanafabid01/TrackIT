<?php
/**
 * Store In-charge API - Booking Request Management
 * Handles booking verification, confirmation, and rejection
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
            handleGet($conn, $owner_id, $user_id);
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
 * GET - Fetch pending booking requests
 */
function handleGet($conn, $owner_id, $user_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : 'Pending,Confirmed';
    
    // Get bookings assigned to this store in-charge or unassigned pending bookings
    $statusArray = explode(',', $status);
    $placeholders = implode(',', array_fill(0, count($statusArray), '?'));
    
    $whereClause = "WHERE b.owner_id = ? AND b.status IN ($placeholders) AND (b.assigned_to = ? OR b.assigned_to IS NULL)";
    $params = array_merge([$owner_id], $statusArray, [$user_id]);
    $types = str_repeat('s', count($statusArray) + 1) . 'i';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM bookings b $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get bookings
    $query = "
        SELECT b.*, 
               c.name as customer_name, c.phone as customer_phone, c.address as customer_address,
               p.name as product_name, p.sku, p.stock_quantity, p.low_stock_threshold,
               u.name as created_by_name
        FROM bookings b
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        LEFT JOIN users u ON b.created_by = u.id
        $whereClause
        ORDER BY 
            CASE b.priority 
                WHEN 'Urgent' THEN 1
                WHEN 'High' THEN 2
                WHEN 'Normal' THEN 3
                WHEN 'Low' THEN 4
            END,
            b.created_at ASC
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
 * PUT - Update booking status
 * Supports all status transitions: Pending → Confirmed → Processing → Ready → Delivered
 * Also supports: Cancelled, Rejected
 */
function handlePut($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['booking_id'])) {
        throw new Exception('Booking ID is required');
    }
    
    $booking_id = $data['booking_id'];
    $new_status = $data['status'] ?? null;
    $action = $data['action'] ?? null; // For backward compatibility
    
    // Valid status transitions
    $valid_statuses = ['Pending', 'Confirmed', 'Processing', 'Ready', 'Delivered', 'Cancelled', 'Rejected'];
    
    // Get current booking
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $booking_id, $owner_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Determine target status (support both 'action' and 'status' parameters)
    if ($action) {
        // Backward compatibility
        if ($action === 'confirm') {
            $new_status = 'Confirmed';
        } elseif ($action === 'reject') {
            $new_status = 'Rejected';
        } else {
            throw new Exception('Invalid action');
        }
    }
    
    if (!$new_status || !in_array($new_status, $valid_statuses)) {
        throw new Exception('Invalid or missing status. Valid: ' . implode(', ', $valid_statuses));
    }
    
    // Validate status transition
    $current_status = $booking['status'];
    if ($current_status === $new_status) {
        throw new Exception('Booking is already in ' . $new_status . ' status');
    }
    
    // Status-specific validations and actions
    $conn->begin_transaction();
    
    try {
        switch ($new_status) {
            case 'Confirmed':
                // Can only confirm from Pending
                if ($current_status !== 'Pending') {
                    throw new Exception('Can only confirm bookings in Pending status');
                }
                
                // Verify stock availability
                $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ? AND owner_id = ?");
                $stmt->bind_param("ii", $booking['product_id'], $owner_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                if (!$product || $product['stock_quantity'] < $booking['quantity']) {
                    throw new Exception('Insufficient stock to confirm booking. Available: ' . ($product['stock_quantity'] ?? 0));
                }
                
                $message = 'Booking confirmed successfully';
                break;
                
            case 'Processing':
                // Can only process from Confirmed
                if ($current_status !== 'Confirmed') {
                    throw new Exception('Can only process bookings in Confirmed status');
                }
                
                $message = 'Booking moved to Processing';
                break;
                
            case 'Ready':
                // Can only mark ready from Processing
                if ($current_status !== 'Processing') {
                    throw new Exception('Can only mark ready bookings in Processing status');
                }
                
                $message = 'Booking is Ready for delivery';
                break;
                
            case 'Delivered':
                // Can only deliver from Ready or Out for Delivery
                if (!in_array($current_status, ['Ready', 'Processing'])) {
                    throw new Exception('Can only deliver bookings in Ready or Processing status');
                }
                
                // Update delivery date
                $stmt = $conn->prepare("UPDATE bookings SET delivery_date = NOW() WHERE id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                
                $message = 'Booking delivered successfully';
                break;
                
            case 'Cancelled':
                // Can cancel from any status except Delivered or Rejected
                if (in_array($current_status, ['Delivered', 'Rejected'])) {
                    throw new Exception('Cannot cancel bookings that are Delivered or Rejected');
                }
                
                // Restore stock if it was deducted
                if (in_array($current_status, ['Pending', 'Confirmed', 'Processing', 'Ready'])) {
                    $stmt = $conn->prepare("
                        UPDATE products 
                        SET stock_quantity = stock_quantity + ? 
                        WHERE id = ? AND owner_id = ?
                    ");
                    $stmt->bind_param("iii", $booking['quantity'], $booking['product_id'], $owner_id);
                    $stmt->execute();
                }
                
                $message = 'Booking cancelled successfully. Stock restored.';
                break;
                
            case 'Rejected':
                // Can only reject from Pending
                if ($current_status !== 'Pending') {
                    throw new Exception('Can only reject bookings in Pending status');
                }
                
                // Restore stock (it was deducted when booking was created)
                $stmt = $conn->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity + ? 
                    WHERE id = ? AND owner_id = ?
                ");
                $stmt->bind_param("iii", $booking['quantity'], $booking['product_id'], $owner_id);
                $stmt->execute();
                
                $message = 'Booking rejected successfully. Stock restored.';
                break;
                
            default:
                throw new Exception('Invalid status transition');
        }
        
        // Update booking status and assign to this store in-charge if not already assigned
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET status = ?, 
                assigned_to = COALESCE(assigned_to, ?),
                updated_at = NOW()
            WHERE id = ? AND owner_id = ?
        ");
        $stmt->bind_param("siii", $new_status, $user_id, $booking_id, $owner_id);
        $stmt->execute();
        
        // Add to booking history
        $notes = $data['notes'] ?? $data['rejection_reason'] ?? ('Status changed to ' . $new_status);
        $stmt = $conn->prepare("
            INSERT INTO booking_history (booking_id, previous_status, new_status, changed_by, notes, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("issis", $booking_id, $current_status, $new_status, $user_id, $notes);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'booking_id' => $booking_id,
            'previous_status' => $current_status,
            'new_status' => $new_status
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
