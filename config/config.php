<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'trackit');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'your-google-client-id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'your-google-client-secret');
define('GOOGLE_REDIRECT_URI', 'http://yourdomain.com/google-callback.php');

// Facebook OAuth Configuration
define('FACEBOOK_APP_ID', 'your-facebook-app-id');
define('FACEBOOK_APP_SECRET', 'your-facebook-app-secret');
define('FACEBOOK_REDIRECT_URI', 'http://yourdomain.com/facebook-callback.php');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getOwnerId() {
    // If user is Owner, return their own ID, else return their owner_id
    return $_SESSION['role'] === 'Owner' ? $_SESSION['user_id'] : $_SESSION['owner_id'];
}

function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: ../auth/auth.php");
        exit();
    }
}

function requireRole($allowedRoles) {
    requireAuth();
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: ../auth/auth.php?error=unauthorized");
        exit();
    }
}

function redirectToDashboard($role) {
    switch($role) {
        case 'Owner':
            return '../main/owner_dashboard.php';
        case 'Moderator':
            return '../main/pages/moderator.php';
        case 'Accountant':
            return '../main/pages/accountant.php';
        case 'Admin In-charge':
            return '../main/pages/admin_in-charge.php';
        case 'Store In-charge':
            return '../main/pages/store_in-charge.php';
        default:
            return '../auth/auth.php';
    }
}

// Database table creation SQL (run once)
/*
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255),
    role ENUM('Owner','Moderator','Accountant','Admin In-charge','Store In-charge') NOT NULL DEFAULT 'Owner',
    owner_id INT(11) DEFAULT NULL COMMENT 'References the owner user_id. NULL for Owners, set for role-based users',
    status ENUM('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
    oauth_provider VARCHAR(50) DEFAULT NULL,
    oauth_uid VARCHAR(100) DEFAULT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    KEY idx_owner_id (owner_id),
    KEY idx_email (email),
    KEY idx_role (role),
    CONSTRAINT fk_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE
);
*/
?>