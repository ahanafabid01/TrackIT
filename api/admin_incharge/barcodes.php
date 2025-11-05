<?php
/**
 * Admin In-charge API - Barcode Generation
 * Handles barcode generation and printing logs
 */

ob_start();
require_once '../../config/config.php';
requireRole(['Admin In-charge', 'Store In-charge', 'Owner']);
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
 * GET - Fetch barcode info
 */
function handleGet($conn, $owner_id) {
    if (isset($_GET['product_id'])) {
        getBarcodeForProduct($conn, $_GET['product_id'], $owner_id);
    } elseif (isset($_GET['logs'])) {
        getBarcodeLogs($conn, $owner_id);
    } else {
        throw new Exception('Invalid request');
    }
}

/**
 * POST - Generate barcode
 */
function handlePost($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['product_id'])) {
        throw new Exception('Product ID is required');
    }
    
    // Get product details
    $stmt = $conn->prepare("
        SELECT id, name, sku, price 
        FROM products 
        WHERE id = ? AND owner_id = ?
    ");
    $stmt->bind_param("ii", $data['product_id'], $owner_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Generate barcode value
    $batch_number = $data['batch_number'] ?? null;
    if ($batch_number) {
        $barcode_value = "PROD-" . str_pad($product['id'], 6, '0', STR_PAD_LEFT) . "-" . $batch_number;
    } else {
        $barcode_value = "PROD-" . str_pad($product['id'], 6, '0', STR_PAD_LEFT);
    }
    
    // Log barcode generation
    $stmt = $conn->prepare("
        INSERT INTO barcode_generation_logs 
        (owner_id, product_id, batch_number, grn_id, barcode_value, barcode_format, 
         quantity_generated, label_size, print_status, generated_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
    ");
    
    $barcode_format = $data['barcode_format'] ?? 'Code128';
    $quantity = $data['quantity'] ?? 1;
    $label_size = $data['label_size'] ?? '50x30mm';
    
    $stmt->bind_param(
        "isissisisi",
        $owner_id,
        $data['product_id'],
        $batch_number,
        $data['grn_id'] ?? null,
        $barcode_value,
        $barcode_format,
        $quantity,
        $label_size,
        $user_id
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Barcode generated successfully',
            'barcode' => [
                'value' => $barcode_value,
                'format' => $barcode_format,
                'product_name' => $product['name'],
                'sku' => $product['sku'],
                'price' => $product['price'],
                'batch_number' => $batch_number,
                'log_id' => $conn->insert_id
            ]
        ]);
    } else {
        throw new Exception('Failed to generate barcode');
    }
}

/**
 * Get barcode for product
 */
function getBarcodeForProduct($conn, $product_id, $owner_id) {
    $stmt = $conn->prepare("
        SELECT p.*, 
               pb.batch_number,
               pb.manufacturing_date,
               pb.expiry_date
        FROM products p
        LEFT JOIN product_batches pb ON p.id = pb.product_id AND pb.status = 'Active'
        WHERE p.id = ? AND p.owner_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $product_id, $owner_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Generate barcode value
    if ($product['batch_number']) {
        $barcode_value = "PROD-" . str_pad($product['id'], 6, '0', STR_PAD_LEFT) . "-" . $product['batch_number'];
    } else {
        $barcode_value = "PROD-" . str_pad($product['id'], 6, '0', STR_PAD_LEFT);
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product,
        'barcode_value' => $barcode_value
    ]);
}

/**
 * Get barcode generation logs
 */
function getBarcodeLogs($conn, $owner_id) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    $stmt = $conn->prepare("
        SELECT bgl.*,
               p.name as product_name,
               p.sku,
               u.name as generated_by_name
        FROM barcode_generation_logs bgl
        LEFT JOIN products p ON bgl.product_id = p.id
        LEFT JOIN users u ON bgl.generated_by = u.id
        WHERE bgl.owner_id = ?
        ORDER BY bgl.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $owner_id, $limit);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'logs' => $logs
    ]);
}
