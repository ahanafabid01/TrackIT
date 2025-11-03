<?php
/**
 * Store In-charge API - Low Stock Alerts
 * Handles low stock alert generation and notifications
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
 * GET - Fetch alerts
 */
function handleGet($conn, $owner_id, $user_id) {
    if (isset($_GET['id'])) {
        getAlertById($conn, $_GET['id'], $owner_id);
    } elseif (isset($_GET['check'])) {
        // Check for low stock products and generate alerts
        checkLowStock($conn, $owner_id, $user_id);
    } else {
        getAllAlerts($conn, $owner_id);
    }
}

/**
 * POST - Create manual alert
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
    
    // Determine alert type
    $alert_type = 'Low Stock';
    if ($product['stock_quantity'] <= 0) {
        $alert_type = 'Out of Stock';
    } elseif ($product['stock_quantity'] <= ($product['low_stock_threshold'] ?? 10) * 0.5) {
        $alert_type = 'Critical';
    }
    
    // Check if alert already exists
    $stmt = $conn->prepare("
        SELECT id FROM low_stock_alerts 
        WHERE product_id = ? AND alert_status IN ('Active', 'Acknowledged')
    ");
    $stmt->bind_param("i", $data['product_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Alert already exists for this product');
    }
    
    // Create alert
    $stmt = $conn->prepare("
        INSERT INTO low_stock_alerts 
        (product_id, owner_id, alert_type, current_stock_level, threshold_level, 
         notified_users, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $threshold = $data['threshold_level'] ?? $product['low_stock_threshold'] ?? 10;
    $notified_users = isset($data['notified_users']) ? json_encode($data['notified_users']) : null;
    
    $stmt->bind_param(
        "iisiisi",
        $data['product_id'],
        $owner_id,
        $alert_type,
        $product['stock_quantity'],
        $threshold,
        $notified_users,
        $user_id
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Alert created successfully',
            'alert_id' => $conn->insert_id,
            'alert_type' => $alert_type
        ]);
    } else {
        throw new Exception('Failed to create alert');
    }
}

/**
 * PUT - Update alert status
 */
function handlePut($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['alert_id'])) {
        throw new Exception('Alert ID is required');
    }
    
    if (empty($data['action'])) {
        throw new Exception('Action is required');
    }
    
    // Get current alert
    $stmt = $conn->prepare("SELECT * FROM low_stock_alerts WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['alert_id'], $owner_id);
    $stmt->execute();
    $alert = $stmt->get_result()->fetch_assoc();
    
    if (!$alert) {
        throw new Exception('Alert not found');
    }
    
    switch ($data['action']) {
        case 'acknowledge':
            acknowledgeAlert($conn, $alert, $user_id);
            break;
        case 'resolve':
            resolveAlert($conn, $alert, $data, $user_id);
            break;
        default:
            throw new Exception('Invalid action');
    }
}

/**
 * DELETE - Delete alert
 */
function handleDelete($conn, $owner_id) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        throw new Exception('Alert ID is required');
    }
    
    $stmt = $conn->prepare("DELETE FROM low_stock_alerts WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $id, $owner_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Alert deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete alert');
    }
}

/**
 * Acknowledge alert
 */
function acknowledgeAlert($conn, $alert, $user_id) {
    $stmt = $conn->prepare("
        UPDATE low_stock_alerts 
        SET alert_status = 'Acknowledged',
            acknowledged_by = ?,
            acknowledged_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $user_id, $alert['id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Alert acknowledged'
        ]);
    } else {
        throw new Exception('Failed to acknowledge alert');
    }
}

/**
 * Resolve alert
 */
function resolveAlert($conn, $alert, $data, $user_id) {
    $resolution_notes = $data['resolution_notes'] ?? 'Stock replenished';
    
    $stmt = $conn->prepare("
        UPDATE low_stock_alerts 
        SET alert_status = 'Resolved',
            resolution_notes = ?,
            resolved_by = ?,
            resolved_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("sii", $resolution_notes, $user_id, $alert['id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Alert resolved'
        ]);
    } else {
        throw new Exception('Failed to resolve alert');
    }
}

/**
 * Check for low stock products and generate alerts
 */
function checkLowStock($conn, $owner_id, $user_id) {
    // Find products with low stock
    $stmt = $conn->prepare("
        SELECT * FROM products 
        WHERE owner_id = ? 
        AND stock_quantity <= COALESCE(low_stock_threshold, 10)
        AND stock_quantity > 0
    ");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $alerts_created = 0;
    
    foreach ($products as $product) {
        // Check if alert already exists
        $stmt = $conn->prepare("
            SELECT id FROM low_stock_alerts 
            WHERE product_id = ? AND alert_status IN ('Active', 'Acknowledged')
        ");
        $stmt->bind_param("i", $product['id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            continue; // Alert already exists
        }
        
        // Determine alert type
        $alert_type = 'Low Stock';
        $threshold = $product['low_stock_threshold'] ?? 10;
        
        if ($product['stock_quantity'] <= $threshold * 0.5) {
            $alert_type = 'Critical';
        }
        
        // Create alert
        $stmt = $conn->prepare("
            INSERT INTO low_stock_alerts 
            (product_id, owner_id, alert_type, current_stock_level, threshold_level, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iisiii",
            $product['id'],
            $owner_id,
            $alert_type,
            $product['stock_quantity'],
            $threshold,
            $user_id
        );
        
        if ($stmt->execute()) {
            $alerts_created++;
        }
    }
    
    // Check for out of stock
    $stmt = $conn->prepare("
        SELECT * FROM products 
        WHERE owner_id = ? AND stock_quantity <= 0
    ");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $out_of_stock = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($out_of_stock as $product) {
        // Check if alert already exists
        $stmt = $conn->prepare("
            SELECT id FROM low_stock_alerts 
            WHERE product_id = ? AND alert_status IN ('Active', 'Acknowledged')
        ");
        $stmt->bind_param("i", $product['id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            continue;
        }
        
        // Create alert
        $stmt = $conn->prepare("
            INSERT INTO low_stock_alerts 
            (product_id, owner_id, alert_type, current_stock_level, threshold_level, created_by)
            VALUES (?, ?, 'Out of Stock', 0, ?, ?)
        ");
        $threshold = $product['low_stock_threshold'] ?? 10;
        $stmt->bind_param("iiii", $product['id'], $owner_id, $threshold, $user_id);
        
        if ($stmt->execute()) {
            $alerts_created++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "$alerts_created new alerts created",
        'alerts_created' => $alerts_created
    ]);
}

/**
 * Get all alerts with pagination
 */
function getAllAlerts($conn, $owner_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $type = isset($_GET['type']) ? $_GET['type'] : null;
    
    $whereClause = "WHERE a.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND a.alert_status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($type) {
        $whereClause .= " AND a.alert_type = ?";
        $params[] = $type;
        $types .= "s";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM low_stock_alerts a $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get alerts
    $query = "
        SELECT a.*, 
               p.name as product_name, p.sku, p.stock_quantity as current_quantity, p.category,
               u1.name as created_by_name,
               u2.name as acknowledged_by_name,
               u3.name as resolved_by_name
        FROM low_stock_alerts a
        LEFT JOIN products p ON a.product_id = p.id
        LEFT JOIN users u1 ON a.created_by = u1.id
        LEFT JOIN users u2 ON a.acknowledged_by = u2.id
        LEFT JOIN users u3 ON a.resolved_by = u3.id
        $whereClause
        ORDER BY 
            CASE a.alert_type
                WHEN 'Out of Stock' THEN 1
                WHEN 'Critical' THEN 2
                WHEN 'Low Stock' THEN 3
            END,
            a.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $alerts = [];
    while ($row = $result->fetch_assoc()) {
        // Parse notified users JSON
        if ($row['notified_users']) {
            $row['notified_users'] = json_decode($row['notified_users'], true);
        }
        $alerts[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'alerts' => $alerts,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get alert by ID
 */
function getAlertById($conn, $id, $owner_id) {
    $stmt = $conn->prepare("
        SELECT a.*, 
               p.name as product_name, p.sku, p.stock_quantity as current_quantity, 
               p.category, p.description, p.low_stock_threshold,
               u1.name as created_by_name,
               u2.name as acknowledged_by_name,
               u3.name as resolved_by_name
        FROM low_stock_alerts a
        LEFT JOIN products p ON a.product_id = p.id
        LEFT JOIN users u1 ON a.created_by = u1.id
        LEFT JOIN users u2 ON a.acknowledged_by = u2.id
        LEFT JOIN users u3 ON a.resolved_by = u3.id
        WHERE a.id = ? AND a.owner_id = ?
    ");
    $stmt->bind_param("ii", $id, $owner_id);
    $stmt->execute();
    $alert = $stmt->get_result()->fetch_assoc();
    
    if (!$alert) {
        throw new Exception('Alert not found');
    }
    
    // Parse notified users JSON
    if ($alert['notified_users']) {
        $alert['notified_users'] = json_decode($alert['notified_users'], true);
    }
    
    echo json_encode([
        'success' => true,
        'alert' => $alert
    ]);
}
