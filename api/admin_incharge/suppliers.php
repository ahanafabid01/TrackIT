<?php
/**
 * Admin In-charge API - Supplier Management
 * Handles supplier CRUD operations, performance tracking
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, we'll log instead
ini_set('log_errors', 1);

ob_start();
try {
    require_once '../../config/config.php';
    requireRole(['Admin In-charge', 'Owner']);
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Authentication error: ' . $e->getMessage()
    ]);
    exit;
}
ob_end_clean();

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
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

ob_end_flush();

/**
 * GET - Fetch suppliers
 */
function handleGet($conn, $owner_id) {
    if (isset($_GET['id'])) {
        getSupplierById($conn, $_GET['id'], $owner_id);
    } elseif (isset($_GET['stats'])) {
        getSupplierStats($conn, $owner_id);
    } else {
        getAllSuppliers($conn, $owner_id);
    }
}

/**
 * POST - Create new supplier
 */
function handlePost($conn, $owner_id, $user_id) {
    error_log("=== CREATE SUPPLIER STARTED ===");
    
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput);
    
    $data = json_decode($rawInput, true);
    error_log("Decoded data: " . print_r($data, true));
    
    // Validate required fields
    if (empty($data['company_name'])) {
        error_log("ERROR: Company name is empty");
        throw new Exception('Company name is required');
    }
    
    error_log("Owner ID: $owner_id, User ID: $user_id");
    
    // Use provided supplier code or generate one
    if (!empty($data['supplier_code'])) {
        $supplier_code = $data['supplier_code'];
        error_log("Using provided supplier code: $supplier_code");
        
        // Check if code already exists for this owner
        $stmt = $conn->prepare("SELECT id FROM suppliers WHERE owner_id = ? AND supplier_code = ?");
        $stmt->bind_param("is", $owner_id, $supplier_code);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            error_log("ERROR: Supplier code already exists");
            throw new Exception('Supplier code already exists');
        }
    } else {
        // Generate supplier code if not provided
        error_log("Generating supplier code");
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM suppliers WHERE owner_id = ?");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $supplier_code = 'SUP-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        error_log("Generated supplier code: $supplier_code");
    }
    
    $country = $data['country'] ?? 'Bangladesh';
    $payment_terms = $data['payment_terms'] ?? 'Net 30';
    $credit_limit = $data['credit_limit'] ?? 0.00;
    $rating = $data['rating'] ?? null;
    $status = $data['status'] ?? 'Active';
    
    // Prepare values for bind_param (must be variables, not inline expressions)
    $contact_person = $data['contact_person'] ?? null;
    $email = $data['email'] ?? null;
    $phone = $data['phone'] ?? null;
    $address = $data['address'] ?? null;
    $city = $data['city'] ?? null;
    $state = $data['state'] ?? null;
    $postal_code = $data['postal_code'] ?? null;
    $tax_id = $data['tax_id'] ?? null;
    $notes = $data['notes'] ?? null;
    
    error_log("Preparing INSERT statement");
    
    // Insert supplier
    $stmt = $conn->prepare("
        INSERT INTO suppliers 
        (owner_id, supplier_code, company_name, contact_person, email, phone, address, 
         city, state, country, postal_code, tax_id, payment_terms, credit_limit, 
         rating, status, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        error_log("ERROR: Failed to prepare statement: " . $conn->error);
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param(
        "issssssssssssddssi",
        $owner_id,
        $supplier_code,
        $data['company_name'],
        $contact_person,
        $email,
        $phone,
        $address,
        $city,
        $state,
        $country,
        $postal_code,
        $tax_id,
        $payment_terms,
        $credit_limit,
        $rating,
        $status,
        $notes,
        $user_id
    );
    
    error_log("Executing INSERT");
    
    if ($stmt->execute()) {
        $supplier_id = $conn->insert_id;
        error_log("SUCCESS: Supplier created with ID: $supplier_id");
        
        echo json_encode([
            'success' => true,
            'message' => 'Supplier created successfully',
            'supplier_id' => $supplier_id,
            'supplier_code' => $supplier_code
        ]);
    } else {
        error_log("ERROR: Execute failed: " . $stmt->error);
        throw new Exception('Failed to create supplier: ' . $stmt->error);
    }
}

/**
 * PUT - Update supplier
 */
function handlePut($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['supplier_id'])) {
        throw new Exception('Supplier ID is required');
    }
    
    $updates = [];
    $values = [];
    $types = "";
    
    $allowedFields = [
        'company_name' => 's',
        'contact_person' => 's',
        'email' => 's',
        'phone' => 's',
        'address' => 's',
        'city' => 's',
        'state' => 's',
        'country' => 's',
        'postal_code' => 's',
        'tax_id' => 's',
        'payment_terms' => 's',
        'credit_limit' => 'd',
        'rating' => 'd',
        'status' => 's',
        'notes' => 's'
    ];
    
    foreach ($allowedFields as $field => $type) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $values[] = $data[$field];
            $types .= $type;
        }
    }
    
    if (empty($updates)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE suppliers SET " . implode(", ", $updates) . " WHERE id = ? AND owner_id = ?";
    $types .= "ii";
    $values[] = $data['supplier_id'];
    $values[] = $owner_id;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Supplier updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update supplier');
    }
}

/**
 * DELETE - Delete supplier
 */
function handleDelete($conn, $owner_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['supplier_id'])) {
        throw new Exception('Supplier ID is required');
    }
    
    // Check if supplier has GRNs
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM grn WHERE supplier_id = ?");
    $stmt->bind_param("i", $data['supplier_id']);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($count > 0) {
        throw new Exception('Cannot delete supplier with existing GRN records. Set status to Inactive instead.');
    }
    
    $stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['supplier_id'], $owner_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Supplier deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete supplier');
    }
}

/**
 * Get all suppliers with pagination
 */
function getAllSuppliers($conn, $owner_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    
    $whereClause = "WHERE s.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND s.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($search) {
        $whereClause .= " AND (s.company_name LIKE ? OR s.supplier_code LIKE ? OR s.contact_person LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM suppliers s $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get suppliers with performance data
    $query = "
        SELECT s.*,
               u.name as created_by_name,
               COUNT(DISTINCT g.id) as total_grns,
               SUM(g.net_amount) as total_purchases,
               AVG(sp.overall_rating) as avg_rating
        FROM suppliers s
        LEFT JOIN users u ON s.created_by = u.id
        LEFT JOIN grn g ON s.id = g.supplier_id AND g.status = 'Approved'
        LEFT JOIN supplier_performance sp ON s.id = sp.supplier_id
        $whereClause
        GROUP BY s.id
        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $suppliers = [];
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'suppliers' => $suppliers,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get supplier by ID with detailed info
 */
function getSupplierById($conn, $id, $owner_id) {
    $stmt = $conn->prepare("
        SELECT s.*,
               u.name as created_by_name,
               COUNT(DISTINCT g.id) as total_grns,
               SUM(g.net_amount) as total_purchases,
               AVG(sp.overall_rating) as avg_performance_rating
        FROM suppliers s
        LEFT JOIN users u ON s.created_by = u.id
        LEFT JOIN grn g ON s.id = g.supplier_id
        LEFT JOIN supplier_performance sp ON s.id = sp.supplier_id
        WHERE s.id = ? AND s.owner_id = ?
        GROUP BY s.id
    ");
    $stmt->bind_param("ii", $id, $owner_id);
    $stmt->execute();
    $supplier = $stmt->get_result()->fetch_assoc();
    
    if (!$supplier) {
        throw new Exception('Supplier not found');
    }
    
    // Get recent GRNs
    $stmt = $conn->prepare("
        SELECT id, grn_number, received_date, net_amount, status
        FROM grn
        WHERE supplier_id = ?
        ORDER BY received_date DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $supplier['recent_grns'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get performance history
    $stmt = $conn->prepare("
        SELECT sp.*, g.grn_number, g.received_date
        FROM supplier_performance sp
        LEFT JOIN grn g ON sp.grn_id = g.id
        WHERE sp.supplier_id = ?
        ORDER BY sp.evaluation_date DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $supplier['performance_history'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'supplier' => $supplier
    ]);
}

/**
 * Get supplier statistics
 */
function getSupplierStats($conn, $owner_id) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_suppliers,
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_suppliers,
            SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) as inactive_suppliers,
            SUM(CASE WHEN status = 'Blocked' THEN 1 ELSE 0 END) as blocked_suppliers,
            AVG(rating) as avg_rating,
            SUM(current_balance) as total_outstanding
        FROM suppliers
        WHERE owner_id = ?
    ");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}
