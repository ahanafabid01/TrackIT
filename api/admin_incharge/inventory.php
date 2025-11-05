<?php
/**
 * Admin In-charge API - Inventory Management
 * Handles stock levels, batches, alerts, and forecasting
 */

ob_start();
require_once '../../config/config.php';
requireRole(['Admin In-charge', 'Owner']);
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
 * GET - Fetch inventory data
 */
function handleGet($conn, $owner_id) {
    if (isset($_GET['alerts'])) {
        getStockAlerts($conn, $owner_id);
    } elseif (isset($_GET['batches'])) {
        getProductBatches($conn, $owner_id);
    } elseif (isset($_GET['forecast'])) {
        getInventoryForecasts($conn, $owner_id);
    } elseif (isset($_GET['audit'])) {
        getAuditLogs($conn, $owner_id);
    } elseif (isset($_GET['stats'])) {
        getInventoryStats($conn, $owner_id);
    } else {
        getAllInventory($conn, $owner_id);
    }
}

/**
 * POST - Create manual stock adjustment
 */
function handlePost($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['product_id']) || !isset($data['quantity_change'])) {
        throw new Exception('Product ID and quantity change are required');
    }
    
    if (empty($data['unit_cost'])) {
        throw new Exception('Unit cost is required');
    }
    
    // Auto-generate batch number if not provided
    if (empty($data['batch_number'])) {
        $year = date('y'); // Last 2 digits of year
        $timestamp = substr(time(), -6); // Last 6 digits of timestamp
        $data['batch_number'] = "B{$year}-PROD-{$timestamp}";
    }
    
    $conn->begin_transaction();
    
    try {
        // Get current stock
        $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ? AND owner_id = ?");
        $stmt->bind_param("ii", $data['product_id'], $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Product not found');
        }
        
        $product = $result->fetch_assoc();
        $quantity_before = $product['stock_quantity'];
        $quantity_change = (int)$data['quantity_change'];
        $quantity_after = $quantity_before + $quantity_change;
        
        if ($quantity_after < 0) {
            throw new Exception('Cannot adjust stock below zero');
        }
        
        // Update product stock
        $stmt = $conn->prepare("
            UPDATE products 
            SET stock_quantity = ? 
            WHERE id = ? AND owner_id = ?
        ");
        $stmt->bind_param("iii", $quantity_after, $data['product_id'], $owner_id);
        $stmt->execute();
        
        // Create or update product batch
        $batch_number = $data['batch_number'];
        $unit_cost = (float)$data['unit_cost'];
        $warehouse_location = $data['warehouse_location'] ?? null;
        $manufacturing_date = !empty($data['manufacturing_date']) ? $data['manufacturing_date'] : null;
        $expiry_date = !empty($data['expiry_date']) ? $data['expiry_date'] : null;
        
        // Check if batch already exists
        $stmt = $conn->prepare("
            SELECT id, quantity_received, quantity_available, quantity_sold 
            FROM product_batches 
            WHERE batch_number = ? AND owner_id = ?
        ");
        $stmt->bind_param("si", $batch_number, $owner_id);
        $stmt->execute();
        $batch_result = $stmt->get_result();
        
        if ($batch_result->num_rows > 0) {
            // Update existing batch
            $batch = $batch_result->fetch_assoc();
            $new_quantity_received = $batch['quantity_received'] + $quantity_change;
            $new_quantity_available = $batch['quantity_available'] + $quantity_change;
            
            $stmt = $conn->prepare("
                UPDATE product_batches 
                SET quantity_received = ?,
                    quantity_available = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE batch_number = ? AND owner_id = ?
            ");
            $stmt->bind_param("iisi", $new_quantity_received, $new_quantity_available, $batch_number, $owner_id);
            $stmt->execute();
        } else {
            // Create new batch
            $stmt = $conn->prepare("
                INSERT INTO product_batches 
                (owner_id, product_id, batch_number, quantity_received, quantity_available, 
                 unit_cost, manufacturing_date, expiry_date, warehouse_location, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
            ");
            $stmt->bind_param(
                "iisiidsss",
                $owner_id,
                $data['product_id'],
                $batch_number,
                $quantity_change,
                $quantity_change,
                $unit_cost,
                $manufacturing_date,
                $expiry_date,
                $warehouse_location
            );
            $stmt->execute();
        }
        
        // Log audit with cost per unit
        $stmt = $conn->prepare("
            INSERT INTO inventory_audit_logs 
            (owner_id, product_id, batch_number, action_type, reference_type, 
             quantity_before, quantity_change, quantity_after, cost_per_unit, 
             location_to, reason, performed_by, ip_address)
            VALUES (?, ?, ?, 'Stock In', 'Manual', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $reason = $data['reason'] ?? 'Manual stock addition';
        
        $stmt->bind_param(
            "issiiidssis",
            $owner_id,
            $data['product_id'],
            $batch_number,
            $quantity_before,
            $quantity_change,
            $quantity_after,
            $unit_cost,
            $warehouse_location,
            $reason,
            $user_id,
            $ip_address
        );
        $stmt->execute();
        
        // Check for alerts
        checkAndCreateStockAlerts($conn, $owner_id, $data['product_id']);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Stock added successfully',
            'quantity_before' => $quantity_before,
            'quantity_after' => $quantity_after,
            'batch_number' => $batch_number
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * PUT - Update inventory settings
 */
function handlePut($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['acknowledge_alert'])) {
        acknowledgeAlert($conn, $data['alert_id'], $user_id);
    } elseif (isset($data['resolve_alert'])) {
        resolveAlert($conn, $data['alert_id'], $user_id, $data['resolution_notes'] ?? null);
    } else {
        throw new Exception('Invalid operation');
    }
}

/**
 * Get all inventory with batch details
 */
function getAllInventory($conn, $owner_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $whereClause = "WHERE p.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($search) {
        $whereClause .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.category LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }
    
    if ($status) {
        $whereClause .= " AND p.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM products p $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get inventory with batch info
    $query = "
        SELECT p.*,
               COUNT(DISTINCT pb.id) as total_batches,
               SUM(pb.quantity_available) as batch_stock,
               MIN(pb.expiry_date) as nearest_expiry,
               (SELECT COUNT(*) FROM stock_alerts sa 
                WHERE sa.product_id = p.id AND sa.status = 'New') as active_alerts
        FROM products p
        LEFT JOIN product_batches pb ON p.id = pb.product_id AND pb.status = 'Active'
        $whereClause
        GROUP BY p.id
        ORDER BY p.name ASC
        LIMIT ? OFFSET ?
    ";
    
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $inventory = [];
    while ($row = $result->fetch_assoc()) {
        // Determine stock status
        if ($row['stock_quantity'] == 0) {
            $row['stock_status'] = 'Out of Stock';
        } elseif ($row['stock_quantity'] <= $row['low_stock_threshold']) {
            $row['stock_status'] = 'Low Stock';
        } else {
            $row['stock_status'] = 'In Stock';
        }
        
        $inventory[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'inventory' => $inventory,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get product batches
 */
function getProductBatches($conn, $owner_id) {
    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    
    $whereClause = "WHERE pb.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($product_id) {
        $whereClause .= " AND pb.product_id = ?";
        $params[] = $product_id;
        $types .= "i";
    }
    
    $query = "
        SELECT pb.*,
               p.name as product_name,
               p.sku,
               g.grn_number,
               DATEDIFF(pb.expiry_date, CURRENT_DATE()) as days_until_expiry
        FROM product_batches pb
        LEFT JOIN products p ON pb.product_id = p.id
        LEFT JOIN grn g ON pb.grn_id = g.id
        $whereClause
        ORDER BY pb.expiry_date ASC, pb.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $batches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'batches' => $batches
    ]);
}

/**
 * Get stock alerts
 */
function getStockAlerts($conn, $owner_id) {
    $status = isset($_GET['alert_status']) ? $_GET['alert_status'] : 'New';
    
    $stmt = $conn->prepare("
        SELECT sa.*,
               p.name as product_name,
               p.sku,
               p.category
        FROM stock_alerts sa
        LEFT JOIN products p ON sa.product_id = p.id
        WHERE sa.owner_id = ? AND sa.status = ?
        ORDER BY 
            CASE sa.alert_level
                WHEN 'Urgent' THEN 1
                WHEN 'Critical' THEN 2
                WHEN 'Warning' THEN 3
                ELSE 4
            END,
            sa.created_at DESC
    ");
    $stmt->bind_param("is", $owner_id, $status);
    $stmt->execute();
    $alerts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'alerts' => $alerts
    ]);
}

/**
 * Get inventory forecasts
 */
function getInventoryForecasts($conn, $owner_id) {
    $stmt = $conn->prepare("
        SELECT f.*,
               p.name as product_name,
               p.sku,
               p.stock_quantity,
               p.low_stock_threshold
        FROM inventory_forecasts f
        LEFT JOIN products p ON f.product_id = p.id
        WHERE f.owner_id = ?
        ORDER BY f.forecast_date DESC, f.confidence_level DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $forecasts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'forecasts' => $forecasts
    ]);
}

/**
 * Get audit logs
 */
function getAuditLogs($conn, $owner_id) {
    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    $whereClause = "WHERE ial.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($product_id) {
        $whereClause .= " AND ial.product_id = ?";
        $params[] = $product_id;
        $types .= "i";
    }
    
    $query = "
        SELECT ial.*,
               p.name as product_name,
               p.sku,
               u.name as performed_by_name
        FROM inventory_audit_logs ial
        LEFT JOIN products p ON ial.product_id = p.id
        LEFT JOIN users u ON ial.performed_by = u.id
        $whereClause
        ORDER BY ial.created_at DESC
        LIMIT ?
    ";
    
    $types .= "i";
    $params[] = $limit;
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'logs' => $logs
    ]);
}

/**
 * Get inventory statistics
 */
function getInventoryStats($conn, $owner_id) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_products,
            SUM(stock_quantity) as total_stock,
            SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(CASE WHEN stock_quantity <= low_stock_threshold AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
            SUM(stock_quantity * cost) as total_inventory_value,
            (SELECT COUNT(*) FROM stock_alerts WHERE owner_id = ? AND status = 'New') as active_alerts,
            (SELECT COUNT(*) FROM product_batches WHERE owner_id = ? AND expiry_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY)) as expiring_soon
        FROM products
        WHERE owner_id = ?
    ");
    $stmt->bind_param("iii", $owner_id, $owner_id, $owner_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}

/**
 * Helper: Check and create stock alerts
 */
function checkAndCreateStockAlerts($conn, $owner_id, $product_id) {
    $stmt = $conn->prepare("
        SELECT id, name, sku, stock_quantity, low_stock_threshold
        FROM products
        WHERE id = ? AND owner_id = ?
    ");
    $stmt->bind_param("ii", $product_id, $owner_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) return;
    
    // Check if alert already exists
    $stmt = $conn->prepare("
        SELECT id FROM stock_alerts 
        WHERE product_id = ? AND status = 'New' 
        AND alert_type IN ('Low Stock', 'Out of Stock')
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        return; // Alert already exists
    }
    
    // Create alert if needed
    if ($product['stock_quantity'] == 0) {
        createAlert($conn, $owner_id, $product, 'Out of Stock', 'Critical');
    } elseif ($product['stock_quantity'] <= $product['low_stock_threshold']) {
        createAlert($conn, $owner_id, $product, 'Low Stock', 'Warning');
    }
}

/**
 * Helper: Create stock alert
 */
function createAlert($conn, $owner_id, $product, $alert_type, $alert_level) {
    $message = "{$product['name']} ({$product['sku']}) is " . 
               ($alert_type == 'Out of Stock' ? 'completely out of stock!' : 'running low. Consider reordering.');
    
    $stmt = $conn->prepare("
        INSERT INTO stock_alerts 
        (owner_id, product_id, alert_type, alert_level, current_quantity, 
         threshold_quantity, message, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'New')
    ");
    $stmt->bind_param(
        "iissiis",
        $owner_id,
        $product['id'],
        $alert_type,
        $alert_level,
        $product['stock_quantity'],
        $product['low_stock_threshold'],
        $message
    );
    $stmt->execute();
}

/**
 * Helper: Acknowledge alert
 */
function acknowledgeAlert($conn, $alert_id, $user_id) {
    $stmt = $conn->prepare("
        UPDATE stock_alerts 
        SET status = 'Acknowledged', acknowledged_by = ?, acknowledged_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $user_id, $alert_id);
    
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
 * Helper: Resolve alert
 */
function resolveAlert($conn, $alert_id, $user_id, $notes) {
    $stmt = $conn->prepare("
        UPDATE stock_alerts 
        SET status = 'Resolved', resolved_by = ?, resolved_at = NOW(), resolution_notes = ?
        WHERE id = ?
    ");
    $stmt->bind_param("isi", $user_id, $notes, $alert_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Alert resolved'
        ]);
    } else {
        throw new Exception('Failed to resolve alert');
    }
}
