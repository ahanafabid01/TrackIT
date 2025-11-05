<?php
/**
 * Admin In-charge API - GRN (Goods Received Note) Management
 * Handles GRN CRUD operations, verification, approval
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
// Turn on display errors for development debugging (set to 0 in production)
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Register shutdown function to catch fatal errors and return JSON (helps with debugging 500s)
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        http_response_code(500);
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Fatal error: ' . ($err['message'] ?? 'Unknown')
        ]);
        error_log("FATAL ERROR in grn.php: " . print_r($err, true));
    }
});

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
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

/**
 * GET - Fetch GRN records
 */
function handleGet($conn, $owner_id) {
    if (isset($_GET['id'])) {
        getGRNById($conn, $_GET['id'], $owner_id);
    } elseif (isset($_GET['stats'])) {
        getGRNStats($conn, $owner_id);
    } else {
        getAllGRNs($conn, $owner_id);
    }
}

/**
 * POST - Create new GRN or perform actions (verify/approve)
 */
function handlePost($conn, $owner_id, $user_id) {
    $rawInput = file_get_contents('php://input');
    error_log("=== CREATE GRN REQUEST ===");
    error_log("Raw input: " . $rawInput);
    
    $data = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    error_log("Decoded data: " . print_r($data, true));
    
    // Check if this is an action request (verify/approve)
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'verify':
                verifyGRN($conn, $data, $owner_id, $user_id);
                break;
            case 'approve':
                approveGRN($conn, $data, $owner_id, $user_id);
                break;
            case 'update_payment':
                updatePaymentStatus($conn, $data, $owner_id);
                break;
            default:
                throw new Exception('Invalid action');
        }
    } else {
        // Create new GRN
        createGRN($conn, $data, $owner_id, $user_id);
    }
}

/**
 * PUT - Update GRN
 */
function handlePut($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['grn_id'])) {
        throw new Exception('GRN ID is required');
    }
    
    updateGRN($conn, $data, $owner_id, $user_id);
}

/**
 * DELETE - Delete GRN (only Draft status)
 */
function handleDelete($conn, $owner_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['grn_id'])) {
        throw new Exception('GRN ID is required');
    }
    
    deleteGRN($conn, $data['grn_id'], $owner_id);
}

/**
 * Create new GRN with items
 */
function createGRN($conn, $data, $owner_id, $user_id) {
    error_log("=== CREATE GRN STARTED ===");
    error_log("Owner ID: $owner_id, User ID: $user_id");
    
    // Validate required fields
    if (empty($data['supplier_id'])) {
        throw new Exception('Supplier is required');
    }
    
    if (empty($data['invoice_number']) || empty($data['invoice_date']) || empty($data['received_date'])) {
        throw new Exception('Invoice number, invoice date, and received date are required');
    }
    
    if (empty($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
        throw new Exception('At least one item is required');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Generate GRN number
        $grn_number = generateGRNNumber($conn, $owner_id);
        error_log("Generated GRN number: $grn_number");
        
        // Prepare GRN data with safe defaults
        $supplier_id = (int)$data['supplier_id'];
        $invoice_number = $data['invoice_number'];
        $invoice_date = $data['invoice_date'];
        $received_date = $data['received_date'];
        $po_number = $data['purchase_order_number'] ?? null;
        $total_items = (int)$data['total_items'];
        $total_quantity = (int)$data['total_quantity'];
        $total_amount = (float)$data['total_amount'];
        $tax_amount = (float)($data['tax_amount'] ?? 0);
        $discount_amount = (float)($data['discount_amount'] ?? 0);
        $net_amount = (float)$data['net_amount'];
        $payment_status = $data['payment_status'] ?? 'Pending';
        $warehouse_location = $data['warehouse_location'] ?? null;
        $notes = $data['notes'] ?? null;
        
        // Insert GRN
        $stmt = $conn->prepare("
            INSERT INTO grn 
            (owner_id, grn_number, supplier_id, purchase_order_number, invoice_number, 
             invoice_date, received_date, total_items, total_quantity, total_amount, 
             tax_amount, discount_amount, net_amount, payment_status, warehouse_location, 
             status, received_by, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft', ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception('Failed to prepare GRN statement: ' . $conn->error);
        }
        
        $stmt->bind_param(
            "isissssiiddddssis",
            $owner_id,
            $grn_number,
            $supplier_id,
            $po_number,
            $invoice_number,
            $invoice_date,
            $received_date,
            $total_items,
            $total_quantity,
            $total_amount,
            $tax_amount,
            $discount_amount,
            $net_amount,
            $payment_status,
            $warehouse_location,
            $user_id,
            $notes
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create GRN: ' . $stmt->error);
        }
        
        $grn_id = $conn->insert_id;
        error_log("✅ GRN created with ID: $grn_id");
        
        // Insert GRN items
        $stmt_item = $conn->prepare("
            INSERT INTO grn_items 
            (owner_id, grn_id, product_id, batch_number, quantity_received, 
             quantity_accepted, quantity_rejected, unit_cost, total_cost, 
             manufacturing_date, expiry_date, condition_status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt_item) {
            throw new Exception('Failed to prepare GRN items statement: ' . $conn->error);
        }
        
        foreach ($data['items'] as $index => $item) {
            error_log("Processing item #" . ($index + 1) . ": " . print_r($item, true));
            
            // Generate batch number if not provided
            $batch_number = $item['batch_number'] ?? null;
            if (empty($batch_number)) {
                $batch_number = generateBatchNumber($conn, $owner_id, $item['product_id']);
                error_log("Generated batch number: $batch_number");
            }
            
            $product_id = (int)$item['product_id'];
            $qty_received = (int)$item['quantity_received'];
            $qty_accepted = (int)$item['quantity_accepted'];
            $qty_rejected = (int)($item['quantity_rejected'] ?? 0);
            $unit_cost = (float)$item['unit_cost'];
            $total_cost = (float)$item['total_cost'];
            $mfg_date = $item['manufacturing_date'] ?? null;
            $expiry_date = $item['expiry_date'] ?? null;
            $condition = $item['condition_status'] ?? 'New';
            $item_notes = $item['notes'] ?? null;
            
            $stmt_item->bind_param(
                "iiisiiiddssss",
                $owner_id,
                $grn_id,
                $product_id,
                $batch_number,
                $qty_received,
                $qty_accepted,
                $qty_rejected,
                $unit_cost,
                $total_cost,
                $mfg_date,
                $expiry_date,
                $condition,
                $item_notes
            );
            
            if (!$stmt_item->execute()) {
                throw new Exception("Failed to add item #" . ($index + 1) . ": " . $stmt_item->error);
            }
            
            error_log("✅ Item #" . ($index + 1) . " added successfully");
        }
        
        // Commit transaction
        $conn->commit();
        
        error_log("=== GRN CREATION COMPLETED SUCCESSFULLY ===");
        
        echo json_encode([
            'success' => true,
            'message' => 'GRN created successfully',
            'grn_id' => $grn_id,
            'grn_number' => $grn_number
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("❌ GRN creation failed: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Generate unique GRN number
 */
function generateGRNNumber($conn, $owner_id) {
    $year = date('Y');
    
    // Get the count of GRNs for this owner in current year
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM grn 
        WHERE owner_id = ? AND YEAR(created_at) = ?
    ");
    $stmt->bind_param("ii", $owner_id, $year);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    
    $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    return "GRN-{$year}-{$sequence}";
}

/**
 * Generate batch number for product
 */
function generateBatchNumber($conn, $owner_id, $product_id) {
    // Get product details
    $stmt = $conn->prepare("SELECT name FROM products WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $product_id, $owner_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    $year = date('y'); // Last 2 digits of year
    $timestamp = substr(time(), -6); // Last 6 digits of timestamp
    
    // Get first 2-3 letters of product name
    $productCode = 'PROD';
    if ($product && !empty($product['name'])) {
        $words = explode(' ', strtoupper($product['name']));
        if (count($words) >= 2) {
            $productCode = substr($words[0], 0, 1) . substr($words[1], 0, 1);
        } else {
            $productCode = substr($words[0], 0, 3);
        }
    }
    
    return "B{$year}-{$productCode}-{$timestamp}";
}

/**
 * Get all GRNs with pagination and filters
 */
function getAllGRNs($conn, $owner_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
    
    $whereClause = "WHERE g.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND g.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($supplier_id) {
        $whereClause .= " AND g.supplier_id = ?";
        $params[] = $supplier_id;
        $types .= "i";
    }
    
    if ($date_from) {
        $whereClause .= " AND g.received_date >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    
    if ($date_to) {
        $whereClause .= " AND g.received_date <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM grn g $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get GRNs
    $query = "
        SELECT g.*,
               s.company_name as supplier_name,
               s.supplier_code,
               u1.name as received_by_name,
               u2.name as verified_by_name,
               u3.name as approved_by_name
        FROM grn g
        LEFT JOIN suppliers s ON g.supplier_id = s.id
        LEFT JOIN users u1 ON g.received_by = u1.id
        LEFT JOIN users u2 ON g.verified_by = u2.id
        LEFT JOIN users u3 ON g.approved_by = u3.id
        $whereClause
        ORDER BY g.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $grns = [];
    while ($row = $result->fetch_assoc()) {
        $grns[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'grns' => $grns,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get GRN by ID with items
 */
function getGRNById($conn, $id, $owner_id) {
    // Get GRN details
    $stmt = $conn->prepare("
        SELECT g.*,
               s.company_name as supplier_name,
               s.supplier_code,
               s.contact_person,
               s.email,
               s.phone,
               u1.name as received_by_name,
               u2.name as verified_by_name,
               u3.name as approved_by_name
        FROM grn g
        LEFT JOIN suppliers s ON g.supplier_id = s.id
        LEFT JOIN users u1 ON g.received_by = u1.id
        LEFT JOIN users u2 ON g.verified_by = u2.id
        LEFT JOIN users u3 ON g.approved_by = u3.id
        WHERE g.id = ? AND g.owner_id = ?
    ");
    $stmt->bind_param("ii", $id, $owner_id);
    $stmt->execute();
    $grn = $stmt->get_result()->fetch_assoc();
    
    if (!$grn) {
        throw new Exception('GRN not found');
    }
    
    // Get GRN items
    $stmt = $conn->prepare("
        SELECT gi.*,
               p.name as product_name,
               p.sku,
               p.unit
        FROM grn_items gi
        LEFT JOIN products p ON gi.product_id = p.id
        WHERE gi.grn_id = ?
        ORDER BY gi.id
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $grn['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'grn' => $grn
    ]);
}

/**
 * Get GRN statistics
 */
function getGRNStats($conn, $owner_id) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_grns,
            SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as draft_grns,
            SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) as verified_grns,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_grns,
            SUM(CASE WHEN MONTH(received_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(received_date) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as grns_this_month,
            SUM(net_amount) as total_value,
            SUM(CASE WHEN payment_status = 'Pending' THEN net_amount ELSE 0 END) as pending_payments
        FROM grn
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

/**
 * Verify GRN
 */
function verifyGRN($conn, $data, $owner_id, $user_id) {
    if (empty($data['grn_id'])) {
        throw new Exception('GRN ID is required');
    }
    
    $grn_id = (int)$data['grn_id'];
    
    // Check if GRN exists and is in Draft status
    $stmt = $conn->prepare("
        SELECT status FROM grn 
        WHERE id = ? AND owner_id = ?
    ");
    $stmt->bind_param("ii", $grn_id, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        throw new Exception('GRN not found');
    }
    
    if ($result['status'] !== 'Draft') {
        throw new Exception('Only Draft GRNs can be verified');
    }
    
    // Update GRN status
    $stmt = $conn->prepare("
        UPDATE grn 
        SET status = 'Verified', 
            verified_by = ?,
            updated_at = NOW()
        WHERE id = ? AND owner_id = ?
    ");
    $stmt->bind_param("iii", $user_id, $grn_id, $owner_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to verify GRN');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'GRN verified successfully'
    ]);
    exit;
}

/**
 * Approve GRN and update inventory
 */
function approveGRN($conn, $data, $owner_id, $user_id) {
    if (empty($data['grn_id'])) {
        throw new Exception('GRN ID is required');
    }
    
    $grn_id = (int)$data['grn_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if GRN exists and is in Verified status
        $stmt = $conn->prepare("
            SELECT status FROM grn 
            WHERE id = ? AND owner_id = ?
        ");
        $stmt->bind_param("ii", $grn_id, $owner_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            throw new Exception('GRN not found');
        }
        
        if ($result['status'] !== 'Verified') {
            throw new Exception('Only Verified GRNs can be approved');
        }
        
        // Get GRN items
        $stmt = $conn->prepare("
            SELECT product_id, quantity_accepted, unit_cost, 
                   batch_number, manufacturing_date, expiry_date
            FROM grn_items
            WHERE grn_id = ?
        ");
        $stmt->bind_param("i", $grn_id);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Update inventory for each item
        foreach ($items as $item) {
            // Update product stock
            $stmt = $conn->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity + ?
                WHERE id = ? AND owner_id = ?
            ");
            $stmt->bind_param("iii", $item['quantity_accepted'], $item['product_id'], $owner_id);
            $stmt->execute();
            
            // Add to product batches
            $stmt = $conn->prepare("
                INSERT INTO product_batches 
                (owner_id, product_id, batch_number, grn_id, quantity_received, 
                 quantity_available, unit_cost, manufacturing_date, expiry_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iisiiiiss",
                $owner_id,
                $item['product_id'],
                $item['batch_number'],
                $grn_id,
                $item['quantity_accepted'],
                $item['quantity_accepted'],
                $item['unit_cost'],
                $item['manufacturing_date'],
                $item['expiry_date']
            );
            $stmt->execute();
        }
        
        // Update GRN status
        $stmt = $conn->prepare("
            UPDATE grn 
            SET status = 'Approved', 
                approved_by = ?,
                updated_at = NOW()
            WHERE id = ? AND owner_id = ?
        ");
        $stmt->bind_param("iii", $user_id, $grn_id, $owner_id);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'GRN approved and inventory updated successfully'
        ]);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Update GRN (only Draft status)
 */
function updateGRN($conn, $data, $owner_id, $user_id) {
    $grn_id = (int)$data['grn_id'];
    
    // Check if GRN is in Draft status
    $stmt = $conn->prepare("
        SELECT status FROM grn 
        WHERE id = ? AND owner_id = ?
    ");
    $stmt->bind_param("ii", $grn_id, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        throw new Exception('GRN not found');
    }
    
    if ($result['status'] !== 'Draft') {
        throw new Exception('Only Draft GRNs can be updated');
    }
    
    // Update allowed fields
    $updates = [];
    $values = [];
    $types = "";
    
    $allowedFields = [
        'invoice_number' => 's',
        'invoice_date' => 's',
        'received_date' => 's',
        'purchase_order_number' => 's',
        'warehouse_location' => 's',
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
    
    $sql = "UPDATE grn SET " . implode(", ", $updates) . " WHERE id = ? AND owner_id = ?";
    $types .= "ii";
    $values[] = $grn_id;
    $values[] = $owner_id;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'GRN updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update GRN');
    }
}

/**
 * Delete GRN (only Draft status)
 */
function deleteGRN($conn, $grn_id, $owner_id) {
    // Check if GRN is in Draft status
    $stmt = $conn->prepare("
        SELECT status FROM grn 
        WHERE id = ? AND owner_id = ?
    ");
    $stmt->bind_param("ii", $grn_id, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        throw new Exception('GRN not found');
    }
    
    if ($result['status'] !== 'Draft') {
        throw new Exception('Only Draft GRNs can be deleted');
    }
    
    // Delete GRN (items will be deleted automatically due to CASCADE)
    $stmt = $conn->prepare("DELETE FROM grn WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $grn_id, $owner_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'GRN deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete GRN');
    }
}

/**
 * Update Payment Status
 */
function updatePaymentStatus($conn, $data, $owner_id) {
    if (empty($data['grn_id'])) {
        throw new Exception('GRN ID is required');
    }
    
    if (empty($data['payment_status'])) {
        throw new Exception('Payment status is required');
    }
    
    $grn_id = (int)$data['grn_id'];
    $payment_status = $data['payment_status'];
    
    // Validate payment status
    $valid_statuses = ['Pending', 'Partial', 'Paid'];
    if (!in_array($payment_status, $valid_statuses)) {
        throw new Exception('Invalid payment status');
    }
    
    // Check if GRN exists
    $stmt = $conn->prepare("SELECT id FROM grn WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $grn_id, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        throw new Exception('GRN not found');
    }
    
    // Update payment status
    $stmt = $conn->prepare("
        UPDATE grn 
        SET payment_status = ?,
            updated_at = NOW()
        WHERE id = ? AND owner_id = ?
    ");
    $stmt->bind_param("sii", $payment_status, $grn_id, $owner_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update payment status');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment status updated successfully'
    ]);
    exit;
}
