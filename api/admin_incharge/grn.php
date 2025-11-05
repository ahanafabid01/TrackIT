<?php
/**
 * Admin In-charge API - GRN (Goods Received Note) Management
 * Handles GRN creation, verification, approval workflow
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

ob_start();
try {
    require_once '../../config/config.php';
    requireRole(['Admin In-charge', 'Owner']);
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Auth error: ' . $e->getMessage()]);
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
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
}

/**
 * GET - Fetch GRNs
 */
function handleGet($conn, $owner_id) {
    if (isset($_GET['id'])) {
        getGrnById($conn, $_GET['id'], $owner_id);
    } elseif (isset($_GET['stats'])) {
        getGrnStats($conn, $owner_id);
    } else {
        getAllGrns($conn, $owner_id);
    }
}

/**
 * POST - Create new GRN
 */
function handlePost($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Log received data for debugging
    error_log("GRN POST Data: " . json_encode($data));
    
    // Validate required fields
    if (empty($data['supplier_id']) || empty($data['items'])) {
        throw new Exception('Supplier and items are required');
    }
    
    // Validate supplier exists
    $stmt = $conn->prepare("SELECT id FROM suppliers WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $data['supplier_id'], $owner_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Invalid supplier selected');
    }
    
    $conn->begin_transaction();
    
    try {
        // Generate GRN number
        $year = date('Y');
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM grn 
            WHERE owner_id = ? AND YEAR(created_at) = ?
        ");
        $stmt->bind_param("ii", $owner_id, $year);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $grn_number = 'GRN-' . $year . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        
        // Calculate totals
        $total_items = count($data['items']);
        $total_quantity = 0;
        $total_amount = 0;
        
        foreach ($data['items'] as $item) {
            $total_quantity += $item['quantity_accepted'] ?? $item['quantity_received'];
            $total_amount += $item['total_cost'];
        }
        
        $tax_amount = $data['tax_amount'] ?? 0;
        $discount_amount = $data['discount_amount'] ?? 0;
        $net_amount = $total_amount + $tax_amount - $discount_amount;
        
        // Insert GRN
        $stmt = $conn->prepare("
            INSERT INTO grn 
            (owner_id, grn_number, supplier_id, purchase_order_number, invoice_number, 
             invoice_date, received_date, total_items, total_quantity, total_amount, 
             tax_amount, discount_amount, net_amount, payment_status, payment_due_date,
             status, warehouse_location, received_by, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $received_date = $data['received_date'] ?? date('Y-m-d');
        $status = $data['status'] ?? 'Draft';
        $payment_status = $data['payment_status'] ?? 'Pending';
        
        $stmt->bind_param(
            "issssssiiidddsssis",
            $owner_id,
            $grn_number,
            $data['supplier_id'],
            $data['purchase_order_number'] ?? null,
            $data['invoice_number'] ?? null,
            $data['invoice_date'] ?? null,
            $received_date,
            $total_items,
            $total_quantity,
            $total_amount,
            $tax_amount,
            $discount_amount,
            $net_amount,
            $payment_status,
            $data['payment_due_date'] ?? null,
            $status,
            $data['warehouse_location'] ?? null,
            $user_id,
            $data['notes'] ?? null
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create GRN');
        }
        
        $grn_id = $conn->insert_id;
        
        // Insert GRN items and create batches
        foreach ($data['items'] as $item) {
            $quantity_accepted = $item['quantity_accepted'] ?? $item['quantity_received'];
            $quantity_rejected = $item['quantity_rejected'] ?? 0;
            $condition_status = $item['condition_status'] ?? 'New';
            
            // Auto-generate batch number if not provided
            $batch_number = $item['batch_number'] ?? null;
            if (empty($batch_number) && $quantity_accepted > 0) {
                $batch_number = generateBatchNumber($conn, $item['product_id'], $grn_id);
                error_log("Auto-generated batch number: $batch_number for product_id: {$item['product_id']}");
            }
            
            // Insert GRN item
            $stmt = $conn->prepare("
                INSERT INTO grn_items 
                (grn_id, product_id, batch_number, quantity_ordered, quantity_received, 
                 quantity_accepted, quantity_rejected, unit_cost, total_cost, 
                 manufacturing_date, expiry_date, condition_status, rejection_reason, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "iisiiiiddssss",
                $grn_id,
                $item['product_id'],
                $batch_number,
                $item['quantity_ordered'] ?? null,
                $item['quantity_received'],
                $quantity_accepted,
                $quantity_rejected,
                $item['unit_cost'],
                $item['total_cost'],
                $item['manufacturing_date'] ?? null,
                $item['expiry_date'] ?? null,
                $condition_status,
                $item['rejection_reason'] ?? null,
                $item['notes'] ?? null
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert GRN item: ' . $stmt->error);
            }
            
            // Create or update product batch
            if ($quantity_accepted > 0) {
                createOrUpdateBatch(
                    $conn,
                    $owner_id,
                    $item['product_id'],
                    $batch_number,
                    $grn_id,
                    $quantity_accepted,
                    $item['unit_cost'],
                    $item['manufacturing_date'] ?? null,
                    $item['expiry_date'] ?? null,
                    $item['warehouse_location'] ?? $data['warehouse_location'] ?? null
                );
                
                // Update product stock
                $stmt = $conn->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity + ? 
                    WHERE id = ? AND owner_id = ?
                ");
                $stmt->bind_param("iii", $quantity_accepted, $item['product_id'], $owner_id);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update product stock: ' . $stmt->error);
                }
                
                // Log inventory audit
                logInventoryAudit(
                    $conn,
                    $owner_id,
                    $item['product_id'],
                    $batch_number,
                    'Stock In',
                    'GRN',
                    $grn_id,
                    $quantity_accepted,
                    $item['unit_cost'],
                    "GRN $grn_number processed",
                    $user_id
                );
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'GRN created successfully',
            'grn_id' => $grn_id,
            'grn_number' => $grn_number
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * PUT - Update GRN status (verify/approve/reject)
 */
function handlePut($conn, $owner_id, $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['grn_id'])) {
        throw new Exception('GRN ID is required');
    }
    
    $updates = [];
    $values = [];
    $types = "";
    
    // Update status
    if (isset($data['status'])) {
        $updates[] = "status = ?";
        $values[] = $data['status'];
        $types .= "s";
        
        // Track who verified/approved
        if ($data['status'] === 'Verified') {
            $updates[] = "verified_by = ?";
            $values[] = $user_id;
            $types .= "i";
        } elseif ($data['status'] === 'Approved') {
            $updates[] = "approved_by = ?";
            $values[] = $user_id;
            $types .= "i";
        } elseif ($data['status'] === 'Rejected' && !empty($data['rejection_reason'])) {
            $updates[] = "rejection_reason = ?";
            $values[] = $data['rejection_reason'];
            $types .= "s";
        }
    }
    
    // Update payment status
    if (isset($data['payment_status'])) {
        $updates[] = "payment_status = ?";
        $values[] = $data['payment_status'];
        $types .= "s";
    }
    
    if (empty($updates)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE grn SET " . implode(", ", $updates) . " WHERE id = ? AND owner_id = ?";
    $types .= "ii";
    $values[] = $data['grn_id'];
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
 * Get all GRNs with pagination
 */
function getAllGrns($conn, $owner_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $whereClause = "WHERE g.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND g.status = ?";
        $params[] = $status;
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
               u.name as received_by_name
        FROM grn g
        LEFT JOIN suppliers s ON g.supplier_id = s.id
        LEFT JOIN users u ON g.received_by = u.id
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
function getGrnById($conn, $id, $owner_id) {
    $stmt = $conn->prepare("
        SELECT g.*,
               s.company_name as supplier_name,
               s.contact_person,
               s.phone as supplier_phone,
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
               p.category
        FROM grn_items gi
        LEFT JOIN products p ON gi.product_id = p.id
        WHERE gi.grn_id = ?
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
function getGrnStats($conn, $owner_id) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_grns,
            SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as draft_grns,
            SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) as verified_grns,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_grns,
            SUM(CASE WHEN MONTH(received_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(received_date) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as this_month_grns,
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
 * Helper: Generate batch number
 */
function generateBatchNumber($conn, $product_id, $grn_id = null) {
    $year = date('y');
    $month = date('m');
    $timestamp = time();
    
    // Format: B{year}{month}-PROD{product_id}-{grn_id}-{timestamp_last4}
    $batch = "B$year$month-P" . str_pad($product_id, 4, '0', STR_PAD_LEFT);
    
    if ($grn_id) {
        $batch .= "-G" . str_pad($grn_id, 3, '0', STR_PAD_LEFT);
    }
    
    $batch .= "-" . substr($timestamp, -4);
    
    // Ensure uniqueness
    $stmt = $conn->prepare("SELECT id FROM product_batches WHERE batch_number = ?");
    $stmt->bind_param("s", $batch);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        // If duplicate, add random suffix
        $batch .= rand(10, 99);
    }
    
    return $batch;
}

/**
 * Helper: Create or update product batch
 */
function createOrUpdateBatch($conn, $owner_id, $product_id, $batch_number, $grn_id, $quantity, $unit_cost, $mfg_date, $exp_date, $location) {
    // Check if batch exists
    $stmt = $conn->prepare("SELECT id, quantity_available FROM product_batches WHERE batch_number = ? AND owner_id = ?");
    $stmt->bind_param("si", $batch_number, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing batch
        $batch = $result->fetch_assoc();
        $stmt = $conn->prepare("
            UPDATE product_batches 
            SET quantity_received = quantity_received + ?,
                quantity_available = quantity_available + ?
            WHERE id = ?
        ");
        $stmt->bind_param("iii", $quantity, $quantity, $batch['id']);
        $stmt->execute();
    } else {
        // Create new batch
        $stmt = $conn->prepare("
            INSERT INTO product_batches 
            (owner_id, product_id, batch_number, grn_id, quantity_received, quantity_available, 
             unit_cost, manufacturing_date, expiry_date, warehouse_location, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
        ");
        $stmt->bind_param(
            "iisiiidsss",
            $owner_id,
            $product_id,
            $batch_number,
            $grn_id,
            $quantity,
            $quantity,
            $unit_cost,
            $mfg_date,
            $exp_date,
            $location
        );
        $stmt->execute();
    }
}

/**
 * Helper: Log inventory audit
 */
function logInventoryAudit($conn, $owner_id, $product_id, $batch_number, $action_type, $reference_type, $reference_id, $quantity_change, $cost, $reason, $user_id) {
    $stmt = $conn->prepare("
        INSERT INTO inventory_audit_logs 
        (owner_id, product_id, batch_number, action_type, reference_type, reference_id, 
         quantity_change, cost_per_unit, reason, performed_by, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt->bind_param(
        "issssiidsss",
        $owner_id,
        $product_id,
        $batch_number,
        $action_type,
        $reference_type,
        $reference_id,
        $quantity_change,
        $cost,
        $reason,
        $user_id,
        $ip_address
    );
    
    $stmt->execute();
}
