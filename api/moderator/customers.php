<?php
/**
 * Moderator API - Customer Management
 * Handles all customer-related operations
 */

// Start output buffering to prevent any whitespace issues
ob_start();

require_once '../../config/config.php';

// Check if user is logged in and has proper role
requireRole(['Moderator', 'Owner']);

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
            handlePut($conn, $owner_id);
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
        'error' => $e->getMessage(),
        'message' => $e->getMessage()
    ]);
}

// Flush output buffer
ob_end_flush();


/**
 * GET - Fetch customers
 */
function handleGet($conn, $owner_id) {
    // Get single customer or list
    if (isset($_GET['id'])) {
        getCustomerById($conn, $_GET['id'], $owner_id);
    } elseif (isset($_GET['history']) && isset($_GET['customer_id'])) {
        getCustomerHistory($conn, $_GET['customer_id'], $owner_id);
    } elseif (isset($_GET['search'])) {
        searchCustomers($conn, $_GET['search'], $owner_id);
    } else {
        getAllCustomers($conn, $owner_id);
    }
}

/**
 * POST - Create new customer
 */
function handlePost($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['name']) || empty($data['phone'])) {
        throw new Exception('Name and phone are required');
    }
    
    // Check if phone already exists for this owner
    $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ? AND owner_id = ?");
    $stmt->bind_param("si", $data['phone'], $owner_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Customer with this phone number already exists');
    }
    
    // Insert new customer
    $stmt = $conn->prepare("
        INSERT INTO customers (owner_id, name, email, phone, address, city, state, postal_code, company, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Prepare variables for bind_param (cannot use ?? operator directly)
    $name = $data['name'];
    $email = $data['email'] ?? null;
    $phone = $data['phone'];
    $address = $data['address'] ?? null;
    $city = $data['city'] ?? null;
    $state = $data['state'] ?? null;
    $postal_code = $data['postal_code'] ?? null;
    $company = $data['company'] ?? null;
    $notes = $data['notes'] ?? null;
    
    $stmt->bind_param(
        "isssssssssi",
        $owner_id,
        $name,
        $email,
        $phone,
        $address,
        $city,
        $state,
        $postal_code,
        $company,
        $notes,
        $user_id
    );
    
    if ($stmt->execute()) {
        $customer_id = $conn->insert_id;
        
        // Fetch the created customer
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Customer created successfully',
            'customer' => $customer
        ]);
    } else {
        throw new Exception('Failed to create customer');
    }
}

/**
 * PUT - Update customer
 */
function handlePut($conn, $owner_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('Customer ID is required');
    }
    
    // Verify customer belongs to this owner
    $stmt = $conn->prepare("SELECT id FROM customers WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['id'], $owner_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Customer not found');
    }
    
    // Update customer
    $stmt = $conn->prepare("
        UPDATE customers 
        SET name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, 
            postal_code = ?, company = ?, status = ?, notes = ?
        WHERE id = ? AND owner_id = ?
    ");
    
    // Prepare variables for bind_param
    $name = $data['name'];
    $email = $data['email'] ?? null;
    $phone = $data['phone'];
    $address = $data['address'] ?? null;
    $city = $data['city'] ?? null;
    $state = $data['state'] ?? null;
    $postal_code = $data['postal_code'] ?? null;
    $company = $data['company'] ?? null;
    $status = $data['status'] ?? 'Active';
    $notes = $data['notes'] ?? null;
    $customer_id = $data['id'];
    
    $stmt->bind_param(
        "ssssssssssii",
        $name,
        $email,
        $phone,
        $address,
        $city,
        $state,
        $postal_code,
        $company,
        $status,
        $notes,
        $customer_id,
        $owner_id
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Customer updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update customer');
    }
}

/**
 * DELETE - Delete customer
 */
function handleDelete($conn, $owner_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('Customer ID is required');
    }
    
    // Check if customer has active bookings
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM bookings 
        WHERE customer_id = ? AND status IN ('Pending', 'Confirmed', 'Processing', 'Ready')
    ");
    $stmt->bind_param("i", $data['id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        throw new Exception('Cannot delete customer with active bookings');
    }
    
    // Delete customer
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['id'], $owner_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete customer');
    }
}

/**
 * Get all customers with pagination
 */
function getAllCustomers($conn, $owner_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Build query
    $whereClause = "WHERE c.owner_id = ?";
    if ($status) {
        $whereClause .= " AND c.status = ?";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM customers c $whereClause";
    $stmt = $conn->prepare($countQuery);
    if ($status) {
        $stmt->bind_param("is", $owner_id, $status);
    } else {
        $stmt->bind_param("i", $owner_id);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get customers
    $query = "
        SELECT c.*, u.name as created_by_name,
               (SELECT COUNT(*) FROM bookings WHERE customer_id = c.id) as booking_count
        FROM customers c
        LEFT JOIN users u ON c.created_by = u.id
        $whereClause
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    if ($status) {
        $stmt->bind_param("isii", $owner_id, $status, $limit, $offset);
    } else {
        $stmt->bind_param("iii", $owner_id, $limit, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'customers' => $customers,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get customer by ID
 */
function getCustomerById($conn, $id, $owner_id) {
    $stmt = $conn->prepare("
        SELECT c.*, u.name as created_by_name
        FROM customers c
        LEFT JOIN users u ON c.created_by = u.id
        WHERE c.id = ? AND c.owner_id = ?
    ");
    $stmt->bind_param("ii", $id, $owner_id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    
    if ($customer) {
        echo json_encode([
            'success' => true,
            'customer' => $customer
        ]);
    } else {
        throw new Exception('Customer not found');
    }
}

/**
 * Get customer history (bookings and feedback)
 */
function getCustomerHistory($conn, $customer_id, $owner_id) {
    // Verify customer belongs to this owner
    $stmt = $conn->prepare("SELECT id FROM customers WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $customer_id, $owner_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Customer not found');
    }
    
    // Get bookings
    $stmt = $conn->prepare("
        SELECT b.*, p.name as product_name, p.sku, u.name as created_by_name
        FROM bookings b
        LEFT JOIN products p ON b.product_id = p.id
        LEFT JOIN users u ON b.created_by = u.id
        WHERE b.customer_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get feedback
    $stmt = $conn->prepare("
        SELECT f.*, b.booking_number
        FROM customer_feedback f
        LEFT JOIN bookings b ON f.booking_id = b.id
        WHERE f.customer_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $feedback = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END) as completed_bookings,
            SUM(CASE WHEN status IN ('Pending', 'Confirmed', 'Processing', 'Ready') THEN 1 ELSE 0 END) as active_bookings,
            SUM(CASE WHEN status = 'Delivered' THEN total_amount ELSE 0 END) as total_spent
        FROM bookings
        WHERE customer_id = ?
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'feedback' => $feedback,
        'stats' => $stats
    ]);
}

/**
 * Search customers
 */
function searchCustomers($conn, $search, $owner_id) {
    $searchTerm = "%$search%";
    
    $stmt = $conn->prepare("
        SELECT c.*, u.name as created_by_name
        FROM customers c
        LEFT JOIN users u ON c.created_by = u.id
        WHERE c.owner_id = ? AND (
            c.name LIKE ? OR 
            c.email LIKE ? OR 
            c.phone LIKE ? OR 
            c.company LIKE ?
        )
        ORDER BY c.name ASC
        LIMIT 20
    ");
    $stmt->bind_param("issss", $owner_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'customers' => $customers
    ]);
}
