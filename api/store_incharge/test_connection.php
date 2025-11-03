<?php
/**
 * Test API Connection for Store In-charge
 * Access: http://localhost/trackit/api/store_incharge/test_connection.php
 */

ob_start();
require_once '../../config/config.php';
ob_clean();

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    
    // Check user role
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'] ?? 'Unknown';
    $user_name = $_SESSION['user_name'] ?? 'Unknown';
    $owner_id = getOwnerId();
    
    // Get user details from database
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        throw new Exception('User not found in database');
    }
    
    // Check if user is Store In-charge or Owner
    if (!in_array($user['role'], ['Store In-charge', 'Owner'])) {
        throw new Exception('User does not have Store In-charge or Owner role. Current role: ' . $user['role']);
    }
    
    // Get pending bookings count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM bookings 
        WHERE owner_id = ? AND status = 'Pending'
    ");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $pendingBookings = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get products count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE owner_id = ?");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $productsCount = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get customers count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM customers WHERE owner_id = ?");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $customersCount = $stmt->get_result()->fetch_assoc()['total'];
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Connection successful!',
        'session' => [
            'user_id' => $user_id,
            'user_name' => $user_name,
            'user_role' => $user_role,
            'owner_id' => $owner_id
        ],
        'database' => [
            'user_found' => true,
            'actual_role' => $user['role'],
            'user_status' => $user['status'],
            'pending_bookings' => $pendingBookings,
            'products_count' => $productsCount,
            'customers_count' => $customersCount
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'session_data' => [
            'logged_in' => isset($_SESSION['user_id']),
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null,
            'user_name' => $_SESSION['user_name'] ?? null
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

ob_end_flush();
