<?php
// clear_age_session.php - Helper untuk reset age verification session
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear age verification from session
    unset($_SESSION['age_category']);
    unset($_SESSION['age_verified']);
    
    echo json_encode(['success' => true, 'message' => 'Age session cleared']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>