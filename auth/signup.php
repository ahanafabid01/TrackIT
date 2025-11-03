<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    // Get form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Check if email already exists
    $check_email = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $check_email);
    
    if (mysqli_num_rows($result) > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into database as Owner with owner_id as NULL
    // After insertion, we'll update owner_id to be same as user id
    $query = "INSERT INTO users (name, email, password, role, owner_id, status) 
              VALUES ('$name', '$email', '$hashed_password', 'Owner', NULL, 'Active')";
    
    if (mysqli_query($conn, $query)) {
        $user_id = mysqli_insert_id($conn);
        
        // Update owner_id to NULL for Owner users (they are their own owner space)
        // Owner's don't need owner_id, they ARE the owner
        
        echo json_encode([
            'success' => true, 
            'message' => 'Owner account created successfully! Please login.'
        ]);
    } else {
        throw new Exception(mysqli_error($conn));
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred during registration. Please try again.'
    ]);
}

mysqli_close($conn);
?>