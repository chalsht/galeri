<?php
// api_ratings.php - API untuk menangani rating (Login Required)
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action']);
    
    if ($action === 'add_rating') {
        // CHECK LOGIN - Wajib login untuk rating
        if (!isLoggedIn()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Anda harus login terlebih dahulu untuk memberikan rating',
                'require_login' => true
            ]);
            exit;
        }
        
        $user = getCurrentUser();
        $artworkId = (int)$_POST['artwork_id'];
        $rating = (int)$_POST['rating'];
        
        if (empty($artworkId) || $rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Artwork ID dan rating (1-5) wajib diisi']);
            exit;
        }
        
        try {
            $pdo = getConnection();
            
            // Check if artwork exists
            $artworkStmt = $pdo->prepare("SELECT id FROM artworks WHERE id = ? AND status = 'approved'");
            $artworkStmt->execute([$artworkId]);
            if (!$artworkStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Karya tidak ditemukan']);
                exit;
            }
            
            // Check if user already rated this artwork
            $existingRatingStmt = $pdo->prepare("SELECT id FROM ratings WHERE artwork_id = ? AND user_id = ?");
            $existingRatingStmt->execute([$artworkId, $user['id']]);
            
            if ($existingRatingStmt->fetch()) {
                // Update existing rating
                $stmt = $pdo->prepare("UPDATE ratings SET rating = ? WHERE artwork_id = ? AND user_id = ?");
                $stmt->execute([$rating, $artworkId, $user['id']]);
                $message = 'Rating berhasil diperbarui';
            } else {
                // Insert new rating
                $stmt = $pdo->prepare("INSERT INTO ratings (artwork_id, user_id, rating) VALUES (?, ?, ?)");
                $stmt->execute([$artworkId, $user['id'], $rating]);
                $message = 'Rating berhasil ditambahkan';
            }
            
            // Calculate average rating
            $avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM ratings WHERE artwork_id = ?");
            $avgStmt->execute([$artworkId]);
            $ratingStats = $avgStmt->fetch();
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'average_rating' => round($ratingStats['avg_rating'], 1),
                'total_ratings' => $ratingStats['total_ratings']
            ]);
            
        } catch (PDOException $e) {
            error_log("Rating submission error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
        }
        
    } elseif ($action === 'get_rating') {
        $artworkId = (int)$_GET['artwork_id'];
        
        if (empty($artworkId)) {
            echo json_encode(['success' => false, 'message' => 'Artwork ID wajib diisi']);
            exit;
        }
        
        try {
            $pdo = getConnection();
            
            // Get rating statistics
            $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM ratings WHERE artwork_id = ?");
            $stmt->execute([$artworkId]);
            $ratingStats = $stmt->fetch();
            
            // Get user's rating if logged in
            $userRating = null;
            if (isLoggedIn()) {
                $user = getCurrentUser();
                $userRatingStmt = $pdo->prepare("SELECT rating FROM ratings WHERE artwork_id = ? AND user_id = ?");
                $userRatingStmt->execute([$artworkId, $user['id']]);
                $userRating = $userRatingStmt->fetchColumn();
            }
            
            echo json_encode([
                'success' => true,
                'average_rating' => $ratingStats['avg_rating'] ? round($ratingStats['avg_rating'], 1) : 0,
                'total_ratings' => $ratingStats['total_ratings'],
                'user_rating' => $userRating,
                'is_logged_in' => isLoggedIn()
            ]);
            
        } catch (PDOException $e) {
            error_log("Get rating error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}
?>