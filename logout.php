<?php
// logout.php - Enhanced logout handler
require_once 'config.php';

// Check if user is logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    
    try {
        $pdo = getConnection();
        
        // Update last logout time (if you have this field)
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Log logout activity (optional)
        error_log("User {$user['nama']} (ID: {$user['id']}) logged out at " . date('Y-m-d H:i:s'));
        
    } catch (PDOException $e) {
        // Log error but don't break logout process
        error_log("Logout error: " . $e->getMessage());
    }
}

// Clear all session data
$_SESSION = array();

// Destroy session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to home page with logout message
redirect('index.php?logout=success');
?>