<?php
// api_likes.php - API untuk menangani like/unlike (Login Required)
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action']);
    
    if ($action === 'toggle_like') {
        // CHECK LOGIN - Wajib login untuk like
        if (!isLoggedIn()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Anda harus login terlebih dahulu untuk memberikan like',
                'require_login' => true
            ]);
            exit;
        }
        
        $user = getCurrentUser();
        $artworkId = (int)$_POST['artwork_id'];
        
        if (empty($artworkId)) {
            echo json_encode(['success' => false, 'message' => 'Artwork ID wajib diisi']);
            exit;
        }
        
        try {
            $pdo = getConnection();
            
            // Check if artwork exists
            $artworkStmt = $pdo->prepare("SELECT id, likes FROM artworks WHERE id = ? AND status = 'approved'");
            $artworkStmt->execute([$artworkId]);
            $artwork = $artworkStmt->fetch();
            
            if (!$artwork) {
                echo json_encode(['success' => false, 'message' => 'Karya tidak ditemukan']);
                exit;
            }
            
            // Check if user already liked this artwork
            $likeStmt = $pdo->prepare("SELECT id FROM likes WHERE artwork_id = ? AND user_id = ?");
            $likeStmt->execute([$artworkId, $user['id']]);
            $existingLike = $likeStmt->fetch();
            
            if ($existingLike) {
                // Unlike - remove like
                $deleteStmt = $pdo->prepare("DELETE FROM likes WHERE artwork_id = ? AND user_id = ?");
                $deleteStmt->execute([$artworkId, $user['id']]);
                
                // Decrement like count
                $updateStmt = $pdo->prepare("UPDATE artworks SET likes = likes - 1 WHERE id = ?");
                $updateStmt->execute([$artworkId]);
                
                $newLikeCount = $artwork['likes'] - 1;
                $message = 'Like dihapus';
                $liked = false;
            } else {
                // Like - add like
                $insertStmt = $pdo->prepare("INSERT INTO likes (artwork_id, user_id) VALUES (?, ?)");
                $insertStmt->execute([$artworkId, $user['id']]);
                
                // Increment like count
                $updateStmt = $pdo->prepare("UPDATE artworks SET likes = likes + 1 WHERE id = ?");
                $updateStmt->execute([$artworkId]);
                
                $newLikeCount = $artwork['likes'] + 1;
                $message = 'Like berhasil ditambahkan';
                $liked = true;
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'liked' => $liked,
                'total_likes' => $newLikeCount
            ]);
            
        } catch (PDOException $e) {
            error_log("Like toggle error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
        }
        
    } elseif ($action === 'check_like') {
        // Check if user has liked an artwork
        if (!isLoggedIn()) {
            echo json_encode(['success' => true, 'liked' => false, 'is_logged_in' => false]);
            exit;
        }
        
        $user = getCurrentUser();
        $artworkId = (int)$_GET['artwork_id'];
        
        try {
            $pdo = getConnection();
            
            $stmt = $pdo->prepare("SELECT id FROM likes WHERE artwork_id = ? AND user_id = ?");
            $stmt->execute([$artworkId, $user['id']]);
            $liked = $stmt->fetch() ? true : false;
            
            echo json_encode([
                'success' => true, 
                'liked' => $liked,
                'is_logged_in' => true
            ]);
            
        } catch (PDOException $e) {
            error_log("Check like error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}
?>