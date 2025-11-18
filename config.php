<?php
// config.php - Database Configuration untuk Galeri Art (Railway Compatible)

// Database configuration - Railway Environment Variables
define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
define('DB_USERNAME', getenv('MYSQLUSER') ?: getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'galeri_art');
define('DB_PORT', getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306');

// Start session untuk authentication
session_start();

// Database connection function
function getConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USERNAME,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // Log error untuk debugging di Railway
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please check your configuration.");
    }
}

// Helper function untuk redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function untuk check login status
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_nama']);
}

// Helper function untuk get current user with profile image
function getCurrentUser() {
    if (isLoggedIn()) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT id, nama, email, role, bio, profile_image FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user) {
                return $user;
            }
        } catch (PDOException $e) {
            // Fallback to session data
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'nama' => $_SESSION['user_nama'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'bio' => $_SESSION['user_bio'] ?? '',
            'profile_image' => $_SESSION['user_profile_image'] ?? null
        ];
    }
    return null;
}

// Helper function untuk require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Helper function untuk hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Helper function untuk verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Helper function untuk sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Upload configuration
define('UPLOAD_DIR', 'uploads/');
define('PROFILE_UPLOAD_DIR', 'uploads/profiles/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_PROFILE_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// Helper function untuk upload file
function uploadFile($file, $prefix = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    // Generate unique filename
    $filename = $prefix . uniqid() . '.' . $ext;
    $filepath = UPLOAD_DIR . $filename;
    
    // Create upload directory if not exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

// Helper function untuk upload profile photo
function uploadProfilePhoto($file, $userId) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_PROFILE_EXTENSIONS)) {
        return false;
    }
    
    // Create profile upload directory if not exists
    if (!is_dir(PROFILE_UPLOAD_DIR)) {
        mkdir(PROFILE_UPLOAD_DIR, 0777, true);
    }
    
    // Generate unique filename
    $filename = 'profile_' . $userId . '_' . uniqid() . '.' . $ext;
    $filepath = PROFILE_UPLOAD_DIR . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    }
    
    return false;
}

// Helper function untuk delete old profile photo
function deleteOldProfilePhoto($photoPath) {
    if (!empty($photoPath) && file_exists($photoPath)) {
        unlink($photoPath);
        return true;
    }
    return false;
}

// Helper function untuk get visitor IP
function getVisitorIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Helper function untuk create visitor session
function createVisitorSession() {
    if (!isset($_SESSION['visitor_session_id'])) {
        $_SESSION['visitor_session_id'] = uniqid('visitor_', true);
    }
    return $_SESSION['visitor_session_id'];
}

// Helper function untuk check age verification
function isAgeVerified() {
    return isset($_SESSION['age_verified']) && $_SESSION['age_verified'] === true;
}

// Helper function untuk get age category
function getAgeCategory() {
    return isset($_SESSION['age_category']) ? $_SESSION['age_category'] : null;
}

// Helper function untuk set age verification
function setAgeVerification($category) {
    $_SESSION['age_verified'] = true;
    $_SESSION['age_category'] = $category;
    
    // Store in database
    try {
        $pdo = getConnection();
        $sessionId = createVisitorSession();
        $ip = getVisitorIP();
        
        $stmt = $pdo->prepare("INSERT INTO visitor_sessions (session_id, ip_address, age_verified, age_category) VALUES (?, ?, 1, ?) ON DUPLICATE KEY UPDATE age_verified = 1, age_category = ?, last_activity = NOW()");
        $stmt->execute([$sessionId, $ip, $category, $category]);
    } catch (PDOException $e) {
        // Log error but don't break functionality
        error_log("Age verification storage error: " . $e->getMessage());
    }
}

// Helper function untuk check if content is age appropriate
function isContentAgeAppropriate($artworkId = null) {
    if (isLoggedIn()) {
        return true; // Logged in users see all content
    }
    
    if (!isAgeVerified()) {
        return false; // Not age verified
    }
    
    // Semua karya dianggap untuk semua usia karena tidak ada kolom age_restriction
    return true;
}
?>