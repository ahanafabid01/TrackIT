<?php
// Capture any unexpected output (warnings/notices) so JSON stays valid
ob_start();

// Don't display PHP errors to the client; log them instead
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Start session early
session_start();

require_once '../config/config.php';
header('Content-Type: application/json; charset=utf-8');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // flush any buffered output to the log and discard it
    $buf = ob_get_clean();
    if (!empty($buf)) error_log("add_user.php unexpected output: " . $buf);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Validate required fields
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');
$password = $_POST['password'] ?? '';

if (!$name || !$email || !$role || !$password) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

// Check if email already exists
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    $buf = ob_get_clean();
    if (!empty($buf)) error_log("add_user.php unexpected output: " . $buf);
    $dberr = mysqli_error($conn);
    error_log('add_user prepare error: ' . $dberr);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    $buf = ob_get_clean();
    if (!empty($buf)) error_log("add_user.php unexpected output: " . $buf);
    echo json_encode(['success' => false, 'message' => 'Email already exists.']);
    mysqli_stmt_close($stmt);
    exit;
}
mysqli_stmt_close($stmt);

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Set parent_id to the current user's id (from session), except for main 'User' role
// session already started above
$parent_id = null;
if (isset($_SESSION['user_id']) && $role !== 'User') {
    $parent_id = $_SESSION['user_id'];
}

// Insert user with parent_id
// Prepare insert; validate prepare() result
$stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, status, parent_id) VALUES (?, ?, ?, ?, ?, ?)");
$status = 'Active';
if (!$stmt) {
    $buf = ob_get_clean();
    if (!empty($buf)) error_log("add_user.php unexpected output: " . $buf);
    $dberr = mysqli_error($conn);
    error_log('add_user prepare error: ' . $dberr);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit;
}
mysqli_stmt_bind_param($stmt, 'sssssi', $name, $email, $hashed_password, $role, $status, $parent_id);
$success = mysqli_stmt_execute($stmt);

// Clean and log any buffered unexpected output before returning JSON
$buf = ob_get_clean();
if (!empty($buf)) {
    error_log("add_user.php unexpected output: " . $buf);
}

if ($success) {
    echo json_encode(['success' => true, 'message' => 'User created successfully.']);
} else {
    $error = mysqli_stmt_error($stmt);
    error_log('Add user error: ' . $error);
    echo json_encode(['success' => false, 'message' => 'Failed to create user. Error: ' . $error]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
