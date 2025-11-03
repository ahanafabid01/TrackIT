<?php
require_once '../config/config.php';
header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get user_id from JSON body
$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

// Get the role of the user to be deleted
$stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $role);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$role) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

if ($role === 'User') {
    // If deleting main User, delete all sub-users with parent_id = this user_id
    $del_sub = mysqli_prepare($conn, "DELETE FROM users WHERE parent_id = ?");
    mysqli_stmt_bind_param($del_sub, 'i', $user_id);
    mysqli_stmt_execute($del_sub);
    mysqli_stmt_close($del_sub);
}

// Delete the user
$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
$success = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete user.']);
}

mysqli_close($conn);
