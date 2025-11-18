<?php
// artwork_detail.php - FIXED VERSION dengan icon matching yang benar
require_once 'config.php';

$artworkId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = getCurrentUser();

if (!$artworkId) {
    redirect('galeri.php');
}

try {
    $pdo = getConnection();
    
    // ✅ FIXED: Gabungkan icon dan nama kategori dalam 1 GROUP_CONCAT
    $stmt = $pdo->prepare("
        SELECT a.*, u.nama as artist_name, u.email as artist_email, u.bio as artist_bio,
               GROUP_CONCAT(DISTINCT CONCAT(c.icon, ':::', c.nama_kategori) 
                            ORDER BY c.nama_kategori SEPARATOR '|||') as category_data
        FROM artworks a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN artwork_categories ac ON a.id = ac.artwork_id
        LEFT JOIN categories c ON ac.category_id = c.id
        WHERE a.id = ? AND a.status = 'approved'
        GROUP BY a.id
    ");
    $stmt->execute([$artworkId]);
    $artwork = $stmt->fetch();
    
    if (!$artwork) {
        redirect('galeri.php');
    }
    
    // Check if user has liked this artwork
    $userLiked = false;
    if (isLoggedIn()) {
        $likeStmt = $pdo->prepare("SELECT id FROM likes WHERE artwork_id = ? AND user_id = ?");
        $likeStmt->execute([$artworkId, $user['id']]);
        $userLiked = $likeStmt->fetch() ? true : false;
    }
    
    // Update view count
    $updateViewStmt = $pdo->prepare("UPDATE artworks SET views = views + 1 WHERE id = ?");
    $updateViewStmt->execute([$artworkId]);
    $artwork['views'] = $artwork['views'] + 1;
    
    // Get APPROVED comments only
    $commentsStmt = $pdo->prepare("
        SELECT c.*, u.nama as author_name, u.role
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.artwork_id = ? AND c.status = 'approved'
        ORDER BY c.created_at DESC
    ");
    $commentsStmt->execute([$artworkId]);
    $comments = $commentsStmt->fetchAll();
    
    // Get rating statistics
    $ratingStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM ratings WHERE artwork_id = ?");
    $ratingStmt->execute([$artworkId]);
    $ratingStats = $ratingStmt->fetch();
    
    // Get user's rating if exists
    $userRating = null;
    if (isLoggedIn()) {
        $userRatingStmt = $pdo->prepare("SELECT rating FROM ratings WHERE artwork_id = ? AND user_id = ?");
        $userRatingStmt->execute([$artworkId, $user['id']]);
        $userRating = $userRatingStmt->fetchColumn();
    }
    
    // ✅ FIXED: Get related artworks dengan icon matching yang benar
    $relatedStmt = $pdo->prepare("
        SELECT a.*, 
               GROUP_CONCAT(DISTINCT CONCAT(c.icon, ':::', c.nama_kategori) 
                            ORDER BY c.nama_kategori SEPARATOR '|||') as category_data
        FROM artworks a
        LEFT JOIN artwork_categories ac ON a.id = ac.artwork_id
        LEFT JOIN categories c ON ac.category_id = c.id
        WHERE a.user_id = ? AND a.id != ? AND a.status = 'approved'
        GROUP BY a.id
        ORDER BY a.created_at DESC
        LIMIT 4
    ");
    $relatedStmt->execute([$artwork['user_id'], $artworkId]);
    $relatedArtworks = $relatedStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Artwork Detail Error: " . $e->getMessage());
    redirect('galeri.php');
}

// ✅ Parse categories untuk main artwork
$category_pairs = !empty($artwork['category_data']) ? explode('|||', $artwork['category_data']) : [];
$categories_list = [];
$icons_list = [];

foreach ($category_pairs as $pair) {
    if (!empty(trim($pair))) {
        $parts = explode(':::', $pair);
        if (count($parts) == 2) {
            $icons_list[] = $parts[0];      // Icon
            $categories_list[] = $parts[1];  // Nama kategori
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($artwork['judul']); ?> - Galeri Art</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Fix navbar overlap */
        body {
            padding-top: 70px;
        }
        
        .artwork-detail-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        /* Like Button Styles */
        .like-button-container {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .like-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        
        .like-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        
        .like-button:active {
            transform: translateY(0);
        }
        
        .like-button.liked {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
        }
        
        .like-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        .like-button .heart-icon {
            font-size: 1.3rem;
            animation: heartBeat 1s ease-in-out infinite;
        }
        
        .like-button.liked .heart-icon {
            animation: heartPulse 0.5s ease-in-out;
        }
        
        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes heartPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        
        .like-count {
            font-size: 1rem;
            font-weight: 600;
        }
        
        .like-success-notice {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 0.9rem;
            display: none;
            animation: slideIn 0.3s ease;
        }
        
        .login-required-notice {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .login-required-notice a {
            color: #dc2626;
            font-weight: 600;
            text-decoration: underline;
        }
        
        .artwork-hero {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .artwork-main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
        
        .artwork-image-section {
            position: relative;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 600px;
            padding: 30px;
        }
        
        .artwork-image-wrapper {
            position: relative;
            max-width: 100%;
            max-height: 100%;
        }
        
        .artwork-main-image {
            max-width: 100%;
            max-height: 550px;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .artwork-info-section {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .artwork-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        
        .artwork-subtitle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .artist-info {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .artist-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }
        
        .artist-bio {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            padding: 6px 14px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 0.85rem;
            border: 1px solid #93c5fd;
            transition: all 0.2s;
        }
        
        .category-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2);
        }
        
        .age-badge {
            background: #fee2e2;
            color: #dc2626;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .artwork-description {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
            line-height: 1.7;
            color: #374151;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            display: block;
            color: #ffffff !important;
        }
        
        .stat-label {
            font-size: 0.85rem;
            opacity: 1;
            color: #ffffff !important;
            font-weight: 500;
        }
        
        .rating-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }
        
        .rating-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #1e293b;
        }
        
        .star-rating {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .star {
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
            color: #d1d5db;
        }
        
        .star:hover,
        .star.active {
            color: #fbbf24;
            transform: scale(1.1);
        }
        
        .star.disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .rating-text {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .submit-rating-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .submit-rating-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.4);
        }
        
        .submit-rating-btn:active {
            transform: translateY(0);
        }
        
        .submit-rating-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        .rating-success-notice {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 0.9rem;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .comments-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        
        .comments-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 25px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .comment-form {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        
        .comment-form textarea {
            width: 100%;
            min-height: 100px;
            padding: 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            resize: vertical;
            font-family: inherit;
            margin-bottom: 15px;
        }
        
        .comment-form button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .comment-form button:hover {
            transform: translateY(-2px);
        }
        
        .comment-item {
            background: #fefefe;
            border: 1px solid #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            transition: box-shadow 0.2s;
        }
        
        .comment-item:hover {
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .comment-author {
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .comment-date {
            color: #9ca3af;
            font-size: 0.85rem;
        }
        
        .comment-text {
            line-height: 1.6;
            color: #4b5563;
        }
        
        .artist-badge {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .related-artworks {
            margin-top: 40px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        
        .related-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1e293b;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .related-item {
            background: #f8fafc;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s;
            text-decoration: none;
        }
        
        .related-item:hover {
            transform: translateY(-5px);
        }
        
        .related-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .related-info {
            padding: 15px;
        }
        
        .related-info h4 {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        
        .related-info small {
            color: #6b7280;
            font-size: 0.8rem;
        }
        
        .related-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 5px;
        }
        
        .related-category-badge {
            background: #e0e7ff;
            color: #4338ca;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .back-nav {
            margin-bottom: 20px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            color: #6b7280;
            font-weight: 500;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        
        .back-btn:hover {
            color: #374151;
            transform: translateY(-2px);
        }
        
        .comment-pending-notice {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 80px;
            }
            
            .artwork-main-content {
                grid-template-columns: 1fr;
            }
            
            .artwork-image-section {
                min-height: 400px;
            }
            
            .artwork-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .related-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">🎨 Galeri Art</div>
        <nav>
            <ul>
                <?php if (isLoggedIn()): ?>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="galeri.php">Galeri</a></li>
                    <li><a href="about.php">About</a></li>
                    <li class="user-dropdown">
                        <a href="#" class="user-name"><?php echo htmlspecialchars($user['nama']); ?> <span class="dropdown-arrow">▼</span></a>
                        <div class="dropdown-menu">
                            <a href="profile.php">Profile</a>
                            <?php if ($user['role'] === 'seniman'): ?>
                                <a href="upload.php">Upload Karya</a>
                            <?php endif; ?>
                            <?php if ($user['role'] === 'admin'): ?>
                                <a href="admin.php">Admin Dashboard</a>
                            <?php endif; ?>
                            <a href="logout.php">Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="galeri.php">Galeri</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="login.php">Masuk</a></li>
                    <li><a href="register.php">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="artwork-detail-container">
        <!-- Back Navigation -->
        <div class="back-nav">
            <a href="galeri.php" class="back-btn">
                ← Kembali ke Galeri
            </a>
        </div>

        <!-- Artwork Hero Section -->
        <div class="artwork-hero">
            <div class="artwork-main-content">
                <!-- Image Section -->
                <div class="artwork-image-section">
                    <div class="artwork-image-wrapper">
                        <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($artwork['judul']); ?>"
                             class="artwork-main-image"
                             onerror="this.src='lukisan.jpeg'">
                    </div>
                </div>
                
                <!-- Info Section -->
                <div class="artwork-info-section">
                    <div>
                        <h1 class="artwork-title"><?php echo htmlspecialchars($artwork['judul']); ?></h1>
                        
                        <!-- ✅ FIXED: Display SEMUA kategori dengan icon yang benar -->
                        <div class="artwork-subtitle">
                            <?php 
                            foreach ($categories_list as $index => $cat): 
                                if (empty(trim($cat))) continue;
                                $icon = isset($icons_list[$index]) ? $icons_list[$index] : '🎨';
                            ?>
                                <span class="category-badge">
                                    <?php echo htmlspecialchars($icon) . ' ' . ucfirst(htmlspecialchars($cat)); ?>
                                </span>
                            <?php endforeach; ?>
                            
                            <?php if ($artwork['age_restricted']): ?>
                                <span class="age-badge">17+</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="artist-info">
                            <div class="artist-name">Oleh: <?php echo htmlspecialchars($artwork['artist_name']); ?></div>
                            <?php if ($artwork['artist_bio']): ?>
                                <div class="artist-bio"><?php echo htmlspecialchars($artwork['artist_bio']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="artwork-description">
                            <?php echo nl2br(htmlspecialchars($artwork['deskripsi'])); ?>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-value"><?php echo number_format($artwork['views']); ?></span>
                            <span class="stat-label">Views</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-value" id="likeCount"><?php echo number_format($artwork['likes']); ?></span>
                            <span class="stat-label">Likes</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-value"><?php echo $ratingStats['avg_rating'] ? round($ratingStats['avg_rating'], 1) : '0.0'; ?></span>
                            <span class="stat-label">Rating (<?php echo $ratingStats['total_ratings']; ?>)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Like Button Section -->
        <div class="like-button-container">
            <h3 class="rating-title">Apakah Anda menyukai karya ini?</h3>
            
            <?php if (isLoggedIn()): ?>
                <button id="likeButton" class="like-button <?php echo $userLiked ? 'liked' : ''; ?>">
                    <span class="heart-icon"><?php echo $userLiked ? '❤️' : '🤍'; ?></span>
                    <span class="like-text"><?php echo $userLiked ? 'Liked' : 'Like'; ?></span>
                    <span class="like-count">(<?php echo number_format($artwork['likes']); ?>)</span>
                </button>
                <div class="like-success-notice" id="likeSuccessNotice"></div>
            <?php else: ?>
                <div class="login-required-notice">
                    <span>🔒</span>
                    <span>Anda harus <a href="login.php">login</a> terlebih dahulu untuk memberikan like</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rating Section -->
        <div class="rating-section">
            <h3 class="rating-title">Berikan Rating</h3>
            
            <?php if (isLoggedIn()): ?>
                <div class="star-rating" id="starRating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-rating="<?php echo $i; ?>" 
                              <?php echo ($userRating && $i <= $userRating) ? 'class="star active"' : 'class="star"'; ?>>
                            ⭐
                        </span>
                    <?php endfor; ?>
                </div>
                <div class="rating-text" id="ratingText">
                    <?php echo $userRating ? "Rating Anda: $userRating/5" : "Pilih bintang untuk memberikan rating"; ?>
                </div>
                <button id="submitRatingBtn" class="submit-rating-btn" style="display: none;">
                    Kirim Rating
                </button>
                <div class="rating-success-notice" style="display: none;" id="ratingSuccessNotice">
                    ✅ Rating berhasil dikirim! Terima kasih atas penilaian Anda.
                </div>
            <?php else: ?>
                <div class="star-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star disabled">⭐</span>
                    <?php endfor; ?>
                </div>
                <div class="login-required-notice">
                    <span>🔒</span>
                    <span>Anda harus <a href="login.php">login</a> terlebih dahulu untuk memberikan rating</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Comments Section -->
        <div class="comments-section">
            <h3 class="comments-title">
                💬 Komentar (<?php echo count($comments); ?>)
            </h3>
            
            <?php if (isLoggedIn()): ?>
                <div class="comment-form">
                    <form id="commentForm">
                        <input type="hidden" name="artwork_id" value="<?php echo $artworkId; ?>">
                        <textarea name="comment" placeholder="Tulis komentar Anda tentang karya ini..." required></textarea>
                        <button type="submit">Kirim Komentar</button>
                    </form>
                    <div class="comment-pending-notice" style="display: none;" id="pendingNotice">
                        ⏳ Komentar Anda telah dikirim dan sedang menunggu persetujuan moderator. Terima kasih!
                    </div>
                </div>
            <?php else: ?>
                <div class="login-required-notice" style="margin-bottom: 25px;">
                    <span>🔒</span>
                    <span>Anda harus <a href="login.php">login</a> terlebih dahulu untuk memberikan komentar</span>
                </div>
            <?php endif; ?>
            
            <!-- Comments List -->
            <div class="comments-list" id="commentsList">
                <?php if (empty($comments)): ?>
                    <div style="text-align: center; padding: 40px; color: #9ca3af;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">💭</div>
                        <p>Belum ada komentar yang disetujui. Jadilah yang pertama berkomentar!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <div class="comment-author">
                                    <?php echo htmlspecialchars($comment['author_name']); ?>
                                    <?php if ($comment['role'] === 'seniman'): ?>
                                        <span class="artist-badge">🎨 Seniman</span>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-date">
                                    <?php echo date('d M Y, H:i', strtotime($comment['created_at'])); ?>
                                </div>
                            </div>
                            <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Artworks -->
        <?php if (!empty($relatedArtworks)): ?>
        <div class="related-artworks">
            <h3 class="related-title">Karya Lain dari <?php echo htmlspecialchars($artwork['artist_name']); ?></h3>
            <div class="related-grid">
                <?php foreach ($relatedArtworks as $related): ?>
                    <?php 
                    // ✅ FIXED: Parse categories untuk related artworks dengan icon matching yang benar
                    $related_pairs = !empty($related['category_data']) ? explode('|||', $related['category_data']) : [];
                    $related_categories = [];
                    $related_icons = [];
                    
                    foreach ($related_pairs as $rpair) {
                        if (!empty(trim($rpair))) {
                            $rparts = explode(':::', $rpair);
                            if (count($rparts) == 2) {
                                $related_icons[] = $rparts[0];
                                $related_categories[] = $rparts[1];
                            }
                        }
                    }
                    ?>
                    <a href="artwork_detail.php?id=<?php echo $related['id']; ?>" class="related-item">
                        <img src="<?php echo htmlspecialchars($related['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($related['judul']); ?>"
                             class="related-image"
                             onerror="this.src='lukisan.jpeg'">
                        <div class="related-info">
                            <h4><?php echo htmlspecialchars($related['judul']); ?></h4>
                            <!-- ✅ FIXED: Display multiple categories dengan icon yang benar -->
                            <div class="related-categories">
                                <?php 
                                foreach ($related_categories as $idx => $rcat): 
                                    if (empty(trim($rcat))) continue;
                                    $ricon = isset($related_icons[$idx]) ? $related_icons[$idx] : '🎨';
                                ?>
                                    <span class="related-category-badge">
                                        <?php echo htmlspecialchars($ricon . ' ' . ucfirst($rcat)); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown functionality
            const userDropdown = document.querySelector('.user-dropdown');
            if (userDropdown) {
                const userNameLink = userDropdown.querySelector('.user-name');
                const dropdownMenu = userDropdown.querySelector('.dropdown-menu');
                
                userNameLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });
                
                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }

            <?php if (isLoggedIn()): ?>
            // Like button functionality
            const likeButton = document.getElementById('likeButton');
            const likeCount = document.getElementById('likeCount');
            const likeSuccessNotice = document.getElementById('likeSuccessNotice');
            let isLiked = <?php echo $userLiked ? 'true' : 'false'; ?>;
            
            if (likeButton) {
                likeButton.addEventListener('click', function() {
                    this.disabled = true;
                    
                    const formData = new FormData();
                    formData.append('action', 'toggle_like');
                    formData.append('artwork_id', <?php echo $artworkId; ?>);
                    
                    fetch('api_likes.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            isLiked = data.liked;
                            
                            const heartIcon = this.querySelector('.heart-icon');
                            const likeText = this.querySelector('.like-text');
                            const likeCountSpan = this.querySelector('.like-count');
                            
                            if (isLiked) {
                                this.classList.add('liked');
                                heartIcon.textContent = '❤️';
                                likeText.textContent = 'Liked';
                            } else {
                                this.classList.remove('liked');
                                heartIcon.textContent = '🤍';
                                likeText.textContent = 'Like';
                            }
                            
                            likeCountSpan.textContent = '(' + data.total_likes.toLocaleString() + ')';
                            likeCount.textContent = data.total_likes.toLocaleString();
                            
                            likeSuccessNotice.textContent = data.message;
                            likeSuccessNotice.style.display = 'block';
                            
                            setTimeout(() => {
                                likeSuccessNotice.style.display = 'none';
                            }, 3000);
                        } else {
                            if (data.require_login) {
                                alert(data.message);
                                window.location.href = 'login.php';
                            } else {
                                alert('Gagal: ' + data.message);
                            }
                        }
                        
                        this.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat memproses like');
                        this.disabled = false;
                    });
                });
            }

            // Star rating functionality
            const stars = document.querySelectorAll('.star:not(.disabled)');
            const ratingText = document.getElementById('ratingText');
            const submitRatingBtn = document.getElementById('submitRatingBtn');
            const ratingSuccessNotice = document.getElementById('ratingSuccessNotice');
            let currentRating = <?php echo $userRating ?: 0; ?>;
            let selectedRating = currentRating;
            
            stars.forEach(function(star, index) {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    selectedRating = rating;
                    highlightStars(rating);
                    updateRatingText(rating);
                    
                    if (selectedRating !== currentRating) {
                        submitRatingBtn.style.display = 'inline-flex';
                    } else {
                        submitRatingBtn.style.display = 'none';
                    }
                });
                
                star.addEventListener('mouseover', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    highlightStars(rating);
                });
            });
            
            if (document.getElementById('starRating')) {
                document.getElementById('starRating').addEventListener('mouseleave', function() {
                    highlightStars(selectedRating);
                });
            }
            
            if (submitRatingBtn) {
                submitRatingBtn.addEventListener('click', function() {
                    submitRating(selectedRating);
                });
            }
            
            function highlightStars(rating) {
                stars.forEach(function(star, index) {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            }
            
            function updateRatingText(rating) {
                if (ratingText) {
                    if (rating === currentRating) {
                        ratingText.textContent = `Rating Anda: ${rating}/5`;
                    } else {
                        ratingText.textContent = `Rating yang dipilih: ${rating}/5 (Klik "Kirim Rating" untuk menyimpan)`;
                    }
                }
            }
            
            function submitRating(rating) {
                submitRatingBtn.disabled = true;
                submitRatingBtn.textContent = 'Mengirim...';
                
                const formData = new FormData();
                formData.append('action', 'add_rating');
                formData.append('artwork_id', <?php echo $artworkId; ?>);
                formData.append('rating', rating);
                
                fetch('api_ratings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentRating = rating;
                        selectedRating = rating;
                        
                        const statCards = document.querySelectorAll('.stat-card');
                        if (statCards[2]) {
                            statCards[2].querySelector('.stat-value').textContent = data.average_rating;
                            statCards[2].querySelector('.stat-label').textContent = `Rating (${data.total_ratings})`;
                        }
                        
                        submitRatingBtn.style.display = 'none';
                        ratingSuccessNotice.style.display = 'block';
                        updateRatingText(rating);
                        
                        setTimeout(() => {
                            ratingSuccessNotice.style.display = 'none';
                        }, 3000);
                        
                        submitRatingBtn.disabled = false;
                        submitRatingBtn.textContent = 'Kirim Rating';
                    } else {
                        if (data.require_login) {
                            alert(data.message);
                            window.location.href = 'login.php';
                        } else {
                            alert('Gagal mengirim rating: ' + data.message);
                        }
                        
                        submitRatingBtn.disabled = false;
                        submitRatingBtn.textContent = 'Kirim Rating';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengirim rating');
                    
                    submitRatingBtn.disabled = false;
                    submitRatingBtn.textContent = 'Kirim Rating';
                });
            }

            // Comment form functionality
            const commentForm = document.getElementById('commentForm');
            if (commentForm) {
                commentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    formData.append('action', 'add_comment');
                    
                    fetch('api_comments.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('pendingNotice').style.display = 'block';
                            this.reset();
                            
                            setTimeout(() => {
                                document.getElementById('pendingNotice').style.display = 'none';
                            }, 5000);
                        } else {
                            if (data.require_login) {
                                alert(data.message);
                                window.location.href = 'login.php';
                            } else {
                                alert('Gagal mengirim komentar: ' + data.message);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat mengirim komentar');
                    });
                });
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>