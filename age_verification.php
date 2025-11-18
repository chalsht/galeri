<?php
// age_verification.php - Handle age verification for anonymous visitors
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ageCategory = sanitizeInput($_POST['age_category']);
    
    if (in_array($ageCategory, ['under_17', '17_plus'])) {
        setAgeVerification($ageCategory);
        echo json_encode(['success' => true, 'message' => 'Age verification set successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid age category']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>

