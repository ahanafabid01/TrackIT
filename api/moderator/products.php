<?php
/**
 * Moderator API - Product Management
 * Handles product availability checks and product information
 */

require_once '../../config/config.php';

// Check if user is logged in
requireRole(['Moderator', 'Owner', 'Store In-charge', 'Admin In-charge']);

header('Content-Type: application/json');

$owner_id = getOwnerId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($conn, $owner_id);
            break;
        case 'POST':
            handlePost($conn, $owner_id);
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
 * GET - Fetch products
 */
function handleGet($conn, $owner_id) {
    if (isset($_GET['id'])) {
        getProductById($conn, $_GET['id'], $owner_id);
    } elseif (isset($_GET['availability'])) {
        checkAvailability($conn, $_GET['id'] ?? null, $owner_id);
    } elseif (isset($_GET['search'])) {
        searchProducts($conn, $_GET['search'], $owner_id);
    } else {
        getAllProducts($conn, $owner_id);
    }
}

/**
 * Get all products with pagination
 */
function getAllProducts($conn, $owner_id) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    
    // Build query
    $whereClause = "WHERE p.owner_id = ?";
    $params = [$owner_id];
    $types = "i";
    
    if ($status) {
        $whereClause .= " AND p.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($category) {
        $whereClause .= " AND p.category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM products p $whereClause";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get products with stock status
    $query = "
        SELECT p.*,
               CASE 
                   WHEN p.stock_quantity = 0 THEN 'Out of Stock'
                   WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
                   ELSE 'In Stock'
               END as stock_status,
               (SELECT COUNT(*) FROM bookings WHERE product_id = p.id AND status IN ('Pending', 'Confirmed', 'Processing')) as pending_orders
        FROM products p
        $whereClause
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
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get product by ID
 */
function getProductById($conn, $id, $owner_id) {
    $stmt = $conn->prepare("
        SELECT p.*,
               CASE 
                   WHEN p.stock_quantity = 0 THEN 'Out of Stock'
                   WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
                   ELSE 'In Stock'
               END as stock_status,
               (SELECT COUNT(*) FROM bookings WHERE product_id = p.id AND status IN ('Pending', 'Confirmed', 'Processing')) as pending_orders,
               (SELECT SUM(quantity) FROM bookings WHERE product_id = p.id AND status IN ('Pending', 'Confirmed', 'Processing')) as reserved_quantity
        FROM products p
        WHERE p.id = ? AND p.owner_id = ?
    ");
    $stmt->bind_param("ii", $id, $owner_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if ($product) {
        // Calculate available quantity (total - reserved)
        $product['available_quantity'] = $product['stock_quantity'] - ($product['reserved_quantity'] ?? 0);
        
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
    } else {
        throw new Exception('Product not found');
    }
}

/**
 * Check product availability
 */
function checkAvailability($conn, $product_id, $owner_id) {
    if ($product_id) {
        // Check specific product
        $stmt = $conn->prepare("
            SELECT p.id, p.name, p.sku, p.stock_quantity, p.low_stock_threshold, p.status,
                   CASE 
                       WHEN p.stock_quantity = 0 THEN 'Out of Stock'
                       WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
                       ELSE 'In Stock'
                   END as stock_status,
                   (SELECT SUM(quantity) FROM bookings WHERE product_id = p.id AND status IN ('Pending', 'Confirmed', 'Processing')) as reserved_quantity
            FROM products p
            WHERE p.id = ? AND p.owner_id = ?
        ");
        $stmt->bind_param("ii", $product_id, $owner_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        $product['available_quantity'] = $product['stock_quantity'] - ($product['reserved_quantity'] ?? 0);
        $product['can_order'] = $product['status'] === 'Active' && $product['available_quantity'] > 0;
        
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
    } else {
        // Get all products with low or out of stock
        $stmt = $conn->prepare("
            SELECT p.id, p.name, p.sku, p.stock_quantity, p.low_stock_threshold,
                   CASE 
                       WHEN p.stock_quantity = 0 THEN 'Out of Stock'
                       WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
                       ELSE 'In Stock'
                   END as stock_status
            FROM products p
            WHERE p.owner_id = ? AND p.status = 'Active' AND p.stock_quantity <= p.low_stock_threshold
            ORDER BY p.stock_quantity ASC
        ");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'low_stock_products' => $products
        ]);
    }
}

/**
 * Search products
 */
function searchProducts($conn, $search, $owner_id) {
    $searchTerm = "%$search%";
    
    $stmt = $conn->prepare("
        SELECT p.*,
               CASE 
                   WHEN p.stock_quantity = 0 THEN 'Out of Stock'
                   WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
                   ELSE 'In Stock'
               END as stock_status
        FROM products p
        WHERE p.owner_id = ? AND p.status = 'Active' AND (
            p.name LIKE ? OR 
            p.sku LIKE ? OR 
            p.category LIKE ?
        )
        ORDER BY p.name ASC
        LIMIT 20
    ");
    $stmt->bind_param("isss", $owner_id, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
}

/**
 * POST - Create a new product
 */
function handlePost($conn, $owner_id) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['name', 'sku', 'category', 'unit', 'cost_price', 'selling_price'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Check if SKU already exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE sku = ? AND owner_id = ?");
    $stmt->bind_param("si", $input['sku'], $owner_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("A product with this SKU already exists");
    }
    
    // Get user_id for created_by
    $user_id = $_SESSION['user_id'];
    
    // Insert product (note: database uses 'price' and 'cost', not 'selling_price' and 'cost_price')
    $query = "INSERT INTO products (
        owner_id, name, sku, description, category, unit,
        cost, price, stock_quantity, low_stock_threshold, status, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $description = $input['description'] ?? '';
    $stock_quantity = $input['stock_quantity'] ?? 0;
    $low_stock_threshold = $input['min_stock_level'] ?? 10;
    $status = $input['status'] ?? 'Active';
    
    $stmt->bind_param(
        "isssssddissi",
        $owner_id,
        $input['name'],
        $input['sku'],
        $description,
        $input['category'],
        $input['unit'],
        $input['cost_price'],        // maps to 'cost' column
        $input['selling_price'],     // maps to 'price' column
        $stock_quantity,
        $low_stock_threshold,
        $status,
        $user_id
    );
    
    if ($stmt->execute()) {
        $product_id = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Product created successfully',
            'product_id' => $product_id
        ]);
    } else {
        throw new Exception("Failed to create product: " . $stmt->error);
    }
}
