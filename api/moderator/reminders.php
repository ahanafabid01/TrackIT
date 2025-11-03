<?php
/**
 * Moderator API - Booking Reminders
 * Handles reminder creation, management, and automated follow-ups
 */

require_once '../../config/config.php';

// Check if user is logged in
requireRole(['Moderator', 'Owner']);

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
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * GET - Fetch reminders
 */
function handleGet($conn, $owner_id) {
    if (isset($_GET['id'])) {
        getReminderById($conn, $_GET['id'], $owner_id);
    } elseif (isset($_GET['pending'])) {
        getPendingReminders($conn, $owner_id);
    } elseif (isset($_GET['booking_id'])) {
        getRemindersByBooking($conn, $_GET['booking_id'], $owner_id);
    } else {
        getAllReminders($conn, $owner_id);
    }
}

/**
 * POST - Create reminder
 */
function handlePost($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['booking_id'])) {
        throw new Exception('Booking ID is required');
    }
    
    // Verify booking belongs to this owner
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['booking_id'], $owner_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Booking not found');
    }
    
    // Insert reminder
    $stmt = $conn->prepare("
        INSERT INTO booking_reminders (booking_id, reminder_type, reminder_date, message, status)
        VALUES (?, ?, ?, ?, 'Pending')
    ");
    
    $reminder_type = $data['reminder_type'] ?? 'Follow-up';
    $reminder_date = $data['reminder_date'] ?? date('Y-m-d H:i:s', strtotime('+1 day'));
    $message = $data['message'] ?? 'Reminder for booking follow-up';
    
    $stmt->bind_param(
        "isss",
        $data['booking_id'],
        $reminder_type,
        $reminder_date,
        $message
    );
    
    if ($stmt->execute()) {
        $reminder_id = $conn->insert_id;
        
        // Update booking reminder_sent flag if it's a confirmation or follow-up
        if (in_array($reminder_type, ['Confirmation', 'Follow-up'])) {
            $stmt = $conn->prepare("UPDATE bookings SET reminder_sent = 1 WHERE id = ?");
            $stmt->bind_param("i", $data['booking_id']);
            $stmt->execute();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Reminder created successfully',
            'reminder_id' => $reminder_id
        ]);
    } else {
        throw new Exception('Failed to create reminder');
    }
}

/**
 * PUT - Update reminder (mark as sent)
 */
function handlePut($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('Reminder ID is required');
    }
    
    // Verify reminder belongs to this owner's booking
    $stmt = $conn->prepare("
        SELECT br.id FROM booking_reminders br
        JOIN bookings b ON br.booking_id = b.id
        WHERE br.id = ? AND b.owner_id = ?
    ");
    $stmt->bind_param("ii", $data['id'], $owner_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Reminder not found');
    }
    
    // Update reminder status
    $stmt = $conn->prepare("
        UPDATE booking_reminders 
        SET status = ?, sent_at = NOW(), sent_by = ?
        WHERE id = ?
    ");
    
    $status = $data['status'] ?? 'Sent';
    $stmt->bind_param("sii", $status, $user_id, $data['id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Reminder updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update reminder');
    }
}

/**
 * DELETE - Delete reminder
 */
function handleDelete($conn, $owner_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('Reminder ID is required');
    }
    
    // Verify and delete
    $stmt = $conn->prepare("
        DELETE br FROM booking_reminders br
        JOIN bookings b ON br.booking_id = b.id
        WHERE br.id = ? AND b.owner_id = ?
    ");
    $stmt->bind_param("ii", $data['id'], $owner_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Reminder deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete reminder or reminder not found');
    }
}

/**
 * Get all reminders
 */
function getAllReminders($conn, $owner_id) {
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $whereClause = "WHERE b.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND br.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $query = "
        SELECT br.*, 
               b.booking_number, b.status as booking_status,
               c.name as customer_name, c.phone as customer_phone,
               p.name as product_name,
               u.name as sent_by_name
        FROM booking_reminders br
        JOIN bookings b ON br.booking_id = b.id
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        LEFT JOIN users u ON br.sent_by = u.id
        $whereClause
        ORDER BY br.reminder_date ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $reminders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reminders' => $reminders
    ]);
}

/**
 * Get pending reminders (due soon or overdue)
 */
function getPendingReminders($conn, $owner_id) {
    $stmt = $conn->prepare("
        SELECT br.*, 
               b.booking_number, b.status as booking_status,
               c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
               p.name as product_name,
               CASE 
                   WHEN br.reminder_date < NOW() THEN 'Overdue'
                   WHEN br.reminder_date <= DATE_ADD(NOW(), INTERVAL 24 HOUR) THEN 'Due Soon'
                   ELSE 'Upcoming'
               END as urgency
        FROM booking_reminders br
        JOIN bookings b ON br.booking_id = b.id
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        WHERE b.owner_id = ? AND br.status = 'Pending'
        ORDER BY br.reminder_date ASC
    ");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $reminders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reminders' => $reminders,
        'count' => count($reminders)
    ]);
}

/**
 * Get reminders by booking
 */
function getRemindersByBooking($conn, $booking_id, $owner_id) {
    // Verify booking belongs to this owner
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $booking_id, $owner_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Booking not found');
    }
    
    $stmt = $conn->prepare("
        SELECT br.*, u.name as sent_by_name
        FROM booking_reminders br
        LEFT JOIN users u ON br.sent_by = u.id
        WHERE br.booking_id = ?
        ORDER BY br.created_at DESC
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $reminders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reminders' => $reminders
    ]);
}

/**
 * Get reminder by ID
 */
function getReminderById($conn, $id, $owner_id) {
    $stmt = $conn->prepare("
        SELECT br.*, 
               b.booking_number, b.status as booking_status,
               c.name as customer_name, c.phone as customer_phone,
               p.name as product_name,
               u.name as sent_by_name
        FROM booking_reminders br
        JOIN bookings b ON br.booking_id = b.id
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN products p ON b.product_id = p.id
        LEFT JOIN users u ON br.sent_by = u.id
        WHERE br.id = ? AND b.owner_id = ?
    ");
    $stmt->bind_param("ii", $id, $owner_id);
    $stmt->execute();
    $reminder = $stmt->get_result()->fetch_assoc();
    
    if ($reminder) {
        echo json_encode([
            'success' => true,
            'reminder' => $reminder
        ]);
    } else {
        throw new Exception('Reminder not found');
    }
}
