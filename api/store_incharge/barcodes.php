<?php
/**
 * Store In-charge API - Barcode Management
 * Handles barcode generation, scanning, and batch tracking
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
        case 'DELETE':
            handleDelete($conn, $owner_id);
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
 * GET - Fetch barcodes
 */
function handleGet($conn, $owner_id) {
    if (isset($_GET['scan'])) {
        // Scan barcode
        scanBarcode($conn, $_GET['scan'], $owner_id);
    } elseif (isset($_GET['product_id'])) {
        // Get barcodes for specific product
        getBarcodesByProduct($conn, $_GET['product_id'], $owner_id);
    } else {
        // Get all barcodes
        getAllBarcodes($conn, $owner_id);
    }
}

/**
 * POST - Generate new barcode
 */
function handlePost($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['product_id'])) {
        throw new Exception('Product ID is required');
    }
    
    // Verify product exists
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['product_id'], $owner_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Generate unique barcode
    $barcode = generateBarcode($conn, $product);
    
    // Check if barcode already exists
    $stmt = $conn->prepare("SELECT id FROM product_barcodes WHERE barcode = ?");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Barcode already exists. Please try again.');
    }
    
    // Create barcode record
    $stmt = $conn->prepare("
        INSERT INTO product_barcodes 
        (product_id, owner_id, barcode, batch_number, manufacturing_date, expiry_date,
         quantity_per_batch, warehouse_location, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $batch_number = $data['batch_number'] ?? 'BATCH-' . time();
    $manufacturing_date = $data['manufacturing_date'] ?? null;
    $expiry_date = $data['expiry_date'] ?? null;
    $quantity_per_batch = $data['quantity_per_batch'] ?? 1;
    $warehouse_location = $data['warehouse_location'] ?? null;
    $notes = $data['notes'] ?? null;
    
    $stmt->bind_param(
        "iissssissi",
        $data['product_id'],
        $owner_id,
        $barcode,
        $batch_number,
        $manufacturing_date,
        $expiry_date,
        $quantity_per_batch,
        $warehouse_location,
        $notes,
        $user_id
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Barcode generated successfully',
            'barcode_id' => $conn->insert_id,
            'barcode' => $barcode,
            'batch_number' => $batch_number
        ]);
    } else {
        throw new Exception('Failed to generate barcode');
    }
}

/**
 * PUT - Update barcode
 */
function handlePut($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['barcode_id'])) {
        throw new Exception('Barcode ID is required');
    }
    
    // Verify barcode exists
    $stmt = $conn->prepare("SELECT * FROM product_barcodes WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['barcode_id'], $owner_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        throw new Exception('Barcode not found');
    }
    
    $updates = [];
    $values = [];
    $types = "";
    
    // Update status
    if (isset($data['status'])) {
        $updates[] = "status = ?";
        $values[] = $data['status'];
        $types .= "s";
        
        // If marking as Expired, check if automatic
        if ($data['status'] === 'Expired' && !isset($data['manual_update'])) {
            $updates[] = "auto_expired = 1";
        }
    }
    
    // Update warehouse location
    if (isset($data['warehouse_location'])) {
        $updates[] = "warehouse_location = ?";
        $values[] = $data['warehouse_location'];
        $types .= "s";
    }
    
    // Update quantity per batch
    if (isset($data['quantity_per_batch'])) {
        $updates[] = "quantity_per_batch = ?";
        $values[] = $data['quantity_per_batch'];
        $types .= "i";
    }
    
    // Update notes
    if (isset($data['notes'])) {
        $updates[] = "notes = ?";
        $values[] = $data['notes'];
        $types .= "s";
    }
    
    if (empty($updates)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE product_barcodes SET " . implode(", ", $updates) . " WHERE id = ? AND owner_id = ?";
    $types .= "ii";
    $values[] = $data['barcode_id'];
    $values[] = $owner_id;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Barcode updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update barcode');
    }
}

/**
 * DELETE - Delete barcode
 */
function handleDelete($conn, $owner_id) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        throw new Exception('Barcode ID is required');
    }
    
    $stmt = $conn->prepare("DELETE FROM product_barcodes WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $id, $owner_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Barcode deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete barcode');
    }
}

/**
 * Scan barcode and get product info
 */
function scanBarcode($conn, $barcode, $owner_id) {
    $stmt = $conn->prepare("
        SELECT pb.*, 
               p.name as product_name, p.sku, p.description, p.price, p.stock_quantity,
               p.category, p.unit
        FROM product_barcodes pb
        LEFT JOIN products p ON pb.product_id = p.id
        WHERE pb.barcode = ? AND pb.owner_id = ?
    ");
    $stmt->bind_param("si", $barcode, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result) {
        throw new Exception('Barcode not found');
    }
    
    // Check if expired
    if ($result['expiry_date'] && strtotime($result['expiry_date']) < time() && $result['status'] === 'Active') {
        // Auto-mark as expired
        $stmt = $conn->prepare("UPDATE product_barcodes SET status = 'Expired', auto_expired = 1 WHERE id = ?");
        $stmt->bind_param("i", $result['id']);
        $stmt->execute();
        $result['status'] = 'Expired';
        $result['auto_expired'] = 1;
    }
    
    echo json_encode([
        'success' => true,
        'barcode' => $result
    ]);
}

/**
 * Get all barcodes for a product
 */
function getBarcodesByProduct($conn, $product_id, $owner_id) {
    $stmt = $conn->prepare("
        SELECT pb.*, 
               p.name as product_name, p.sku,
               u.name as created_by_name
        FROM product_barcodes pb
        LEFT JOIN products p ON pb.product_id = p.id
        LEFT JOIN users u ON pb.created_by = u.id
        WHERE pb.product_id = ? AND pb.owner_id = ?
        ORDER BY pb.created_at DESC
    ");
    $stmt->bind_param("ii", $product_id, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $barcodes = [];
    while ($row = $result->fetch_assoc()) {
        $barcodes[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'barcodes' => $barcodes
    ]);
}

/**
 * Get all barcodes with pagination
 */
function getAllBarcodes($conn, $owner_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $whereClause = "WHERE pb.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND pb.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM product_barcodes pb $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get barcodes
    $query = "
        SELECT pb.*, 
               p.name as product_name, p.sku, p.stock_quantity,
               u.name as created_by_name
        FROM product_barcodes pb
        LEFT JOIN products p ON pb.product_id = p.id
        LEFT JOIN users u ON pb.created_by = u.id
        $whereClause
        ORDER BY pb.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $barcodes = [];
    while ($row = $result->fetch_assoc()) {
        // Check if expired
        if ($row['expiry_date'] && strtotime($row['expiry_date']) < time() && $row['status'] === 'Active') {
            $row['status'] = 'Expired (pending update)';
        }
        $barcodes[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'barcodes' => $barcodes,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Generate unique barcode
 */
function generateBarcode($conn, $product) {
    // Format: PROD-{SKU}-{TIMESTAMP}-{RANDOM}
    // Example: PROD-SKU123-1704067200-9876
    $sku = preg_replace('/[^A-Z0-9]/', '', strtoupper($product['sku'] ?? 'UNKNOWN'));
    $timestamp = time();
    $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    
    return "PROD-{$sku}-{$timestamp}-{$random}";
}
