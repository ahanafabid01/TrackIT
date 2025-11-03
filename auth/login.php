<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    // Get form data
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Get user from database
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Update last login time
            mysqli_query($conn, "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = " . $user['id']);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Login successful! Redirecting...'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid password'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Email not found'
        ]);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred during login. Please try again.'
    ]);
}

mysqli_close($conn);
?>