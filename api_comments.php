<?php
// api_comments.php - API untuk menangani komentar (Login Required)
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action']);
    
    if ($action === 'add_comment') {
        // CHECK LOGIN - Wajib login untuk komentar
        if (!isLoggedIn()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Anda harus login terlebih dahulu untuk memberikan komentar',
                'require_login' => true
            ]);
            exit;
        }
        
        $user = getCurrentUser();
        $artworkId = (int)$_POST['artwork_id'];
        $comment = sanitizeInput($_POST['comment']);
        
        if (empty($artworkId) || empty($comment)) {
            echo json_encode(['success' => false, 'message' => 'Artwork ID dan komentar wajib diisi']);
            exit;
        }
        
        // Validate comment length
        if (strlen($comment) < 5) {
            echo json_encode(['success' => false, 'message' => 'Komentar terlalu pendek (minimal 5 karakter)']);
            exit;
        }
        
        if (strlen($comment) > 1000) {
            echo json_encode(['success' => false, 'message' => 'Komentar terlalu panjang (maksimal 1000 karakter)']);
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
            
            // Auto-approve untuk seniman atau admin
            $status = 'pending';
            if ($user['role'] === 'seniman' || $user['role'] === 'admin') {
                $status = 'approved';
            }
            
            // Insert comment
            $stmt = $pdo->prepare("INSERT INTO comments (artwork_id, user_id, comment, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$artworkId, $user['id'], $comment, $status]);
            
            $message = ($status === 'pending') ? 
                'Komentar berhasil dikirim dan sedang menunggu persetujuan moderator' : 
                'Komentar berhasil ditambahkan';
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'status' => $status
            ]);
            
        } catch (PDOException $e) {
            error_log("Comment submission error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
        }
        
    } elseif ($action === 'get_comments') {
        $artworkId = (int)$_GET['artwork_id'];
        
        if (empty($artworkId)) {
            echo json_encode(['success' => false, 'message' => 'Artwork ID wajib diisi']);
            exit;
        }
        
        try {
            $pdo = getConnection();
            
            // Only get APPROVED comments
            $stmt = $pdo->prepare("
                SELECT c.*, u.nama as author_name, u.role
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.artwork_id = ? AND c.status = 'approved'
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$artworkId]);
            $comments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'comments' => $comments]);
            
        } catch (PDOException $e) {
            error_log("Get comments error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
        }
        
    } elseif ($action === 'approve_comment') {
        // Admin only action
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Login diperlukan']);
            exit;
        }
        
        $user = getCurrentUser();
        if ($user['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            exit;
        }
        
        $commentId = (int)$_POST['comment_id'];
        
        try {
            $pdo = getConnection();
            
            $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
            $stmt->execute([$commentId]);
            
            echo json_encode(['success' => true, 'message' => 'Komentar berhasil disetujui']);
            
        } catch (PDOException $e) {
            error_log("Approve comment error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
        }
        
    } elseif ($action === 'reject_comment') {
        // Admin only action
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Login diperlukan']);
            exit;
        }
        
        $user = getCurrentUser();
        if ($user['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            exit;
        }
        
        $commentId = (int)$_POST['comment_id'];
        
        try {
            $pdo = getConnection();
            
            $stmt = $pdo->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$commentId]);
            
            echo json_encode(['success' => true, 'message' => 'Komentar berhasil ditolak']);
            
        } catch (PDOException $e) {
            error_log("Reject comment error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
        }
        
    } elseif ($action === 'get_pending_comments') {
        // Admin only action
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Login diperlukan']);
            exit;
        }
        
        $user = getCurrentUser();
        if ($user['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
            exit;
        }
        
        try {
            $pdo = getConnection();
            
            $stmt = $pdo->prepare("
                SELECT c.*, u.nama as author_name, u.role, a.judul as artwork_title
                FROM comments c
                JOIN users u ON c.user_id = u.id
                JOIN artworks a ON c.artwork_id = a.id
                WHERE c.status = 'pending'
                ORDER BY c.created_at ASC
            ");
            $stmt->execute();
            $comments = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'comments' => $comments]);
            
        } catch (PDOException $e) {
            error_log("Get pending comments error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}
?>