<?php
// profile.php - Enhanced Profile page with photo upload
require_once 'config.php';

// Require login
requireLogin();

$user = getCurrentUser();
$error = '';
$success = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action']);
    
    if ($action === 'update_profile') {
        $nama = sanitizeInput($_POST['nama']);
        $bio = sanitizeInput($_POST['bio']);
        
        if (empty($nama)) {
            $error = 'Nama tidak boleh kosong!';
        } else {
            try {
                $pdo = getConnection();
                
                // Handle profile photo upload
                $photoPath = $user['profile_image'];
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $newPhotoPath = uploadProfilePhoto($_FILES['profile_photo'], $user['id']);
                    
                    if ($newPhotoPath) {
                        // Delete old photo if exists
                        if (!empty($user['profile_image'])) {
                            deleteOldProfilePhoto($user['profile_image']);
                        }
                        $photoPath = $newPhotoPath;
                    } else {
                        $error = 'Gagal upload foto! Pastikan file berformat JPG/PNG dan ukuran maksimal 5MB.';
                    }
                }
                
                if (empty($error)) {
                    $stmt = $pdo->prepare("UPDATE users SET nama = ?, bio = ?, profile_image = ? WHERE id = ?");
                    $stmt->execute([$nama, $bio, $photoPath, $user['id']]);
                    
                    // Update session
                    $_SESSION['user_nama'] = $nama;
                    $_SESSION['user_bio'] = $bio;
                    $_SESSION['user_profile_image'] = $photoPath;
                    
                    $success = 'Profile berhasil diperbarui!';
                    $user['nama'] = $nama;
                    $user['bio'] = $bio;
                    $user['profile_image'] = $photoPath;
                    $activeTab = 'profile';
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
        }
        
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Semua field password wajib diisi!';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password baru minimal 6 karakter!';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Konfirmasi password tidak cocok!';
        } else {
            try {
                $pdo = getConnection();
                
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                $currentHash = $stmt->fetchColumn();
                
                if (!verifyPassword($currentPassword, $currentHash)) {
                    $error = 'Password lama tidak benar!';
                } else {
                    // Update password
                    $newHash = hashPassword($newPassword);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$newHash, $user['id']]);
                    
                    $success = 'Password berhasil diubah!';
                    $activeTab = 'security';
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
        }
        
    } elseif ($action === 'delete_artwork') {
        $artworkId = (int)$_POST['artwork_id'];
        
        try {
            $pdo = getConnection();
            
            // Verify ownership
            $stmt = $pdo->prepare("SELECT image_path FROM artworks WHERE id = ? AND user_id = ?");
            $stmt->execute([$artworkId, $user['id']]);
            $artwork = $stmt->fetch();
            
            if ($artwork) {
                // Delete file
                if (file_exists($artwork['image_path'])) {
                    unlink($artwork['image_path']);
                }
                
                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM artworks WHERE id = ? AND user_id = ?");
                $stmt->execute([$artworkId, $user['id']]);
                
                $success = 'Karya berhasil dihapus!';
                $activeTab = 'artworks';
            } else {
                $error = 'Karya tidak ditemukan atau tidak memiliki izin!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

try {
    $pdo = getConnection();
    
    // Get user's artwork count (only for seniman)
    $artworkCount = 0;
    $totalViews = 0;
    $totalLikes = 0;
    $artworks = [];
    
    if ($user['role'] === 'seniman') {
        $artworkStmt = $pdo->prepare("SELECT COUNT(*) as total FROM artworks WHERE user_id = ?");
        $artworkStmt->execute([$user['id']]);
        $artworkCount = $artworkStmt->fetch()['total'];
        
        // Get user's total views and likes
        $statsStmt = $pdo->prepare("SELECT SUM(views) as total_views, SUM(likes) as total_likes FROM artworks WHERE user_id = ?");
        $statsStmt->execute([$user['id']]);
        $stats = $statsStmt->fetch();
        $totalViews = $stats['total_views'] ?: 0;
        $totalLikes = $stats['total_likes'] ?: 0;
        
        // Get user's artworks
        $artworksStmt = $pdo->prepare("
            SELECT a.*, c.nama_kategori, c.icon
            FROM artworks a
            JOIN categories c ON a.category_id = c.id
            WHERE a.user_id = ?
            ORDER BY a.created_at DESC
        ");
        $artworksStmt->execute([$user['id']]);
        $artworks = $artworksStmt->fetchAll();
    }
    
    // Get user's recent comments
    $commentsStmt = $pdo->prepare("
        SELECT c.*, a.judul as artwork_title
        FROM comments c
        JOIN artworks a ON c.artwork_id = a.id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $commentsStmt->execute([$user['id']]);
    $recentComments = $commentsStmt->fetchAll();
    
} catch (PDOException $e) {
    $artworkCount = 0;
    $totalViews = 0;
    $totalLikes = 0;
    $artworks = [];
    $recentComments = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Galeri Art</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Fix navbar overlap */
        body {
            padding-top: 70px;
        }
        
        /* Profile Image Styles */
        .profile-avatar {
            position: relative;
            margin-bottom: 20px;
        }
        
        .avatar-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 700;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            overflow: hidden;
        }
        
        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-photo-upload {
            margin-top: 15px;
        }
        
        .photo-upload-wrapper {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .file-input-label:hover {
            transform: translateY(-2px);
        }
        
        input[type="file"] {
            display: none;
        }
        
        .file-name-display {
            font-size: 0.85rem;
            color: #6b7280;
            font-style: italic;
        }
        
        .profile-photo-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 10px auto;
            overflow: hidden;
            border: 3px solid #e2e8f0;
            display: none;
        }
        
        .profile-photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">🎨 Galeri Art</div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="galeri.php">Galeri</a></li>
                <li><a href="about.php">About</a></li>
                <li class="user-dropdown">
                    <a href="#" class="user-name"><?php echo htmlspecialchars($user['nama']); ?> <span class="dropdown-arrow">▼</span></a>
                    <div class="dropdown-menu">
                        <a href="profile.php" class="active">Profile</a>
                        <?php if ($user['role'] === 'seniman'): ?>
                            <a href="upload.php">Upload Karya</a>
                        <?php endif; ?>
                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="admin.php">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <section class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <div class="avatar-circle">
                    <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Photo">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['nama'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-basic-info">
                <h1><?php echo htmlspecialchars($user['nama']); ?></h1>
                <p class="profile-role"><?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                <?php if (!empty($user['bio'])): ?>
                    <p class="profile-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="alert-icon">⚠️</span>
                <span class="alert-text"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <span class="alert-text"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <!-- Profile Stats -->
        <div class="profile-stats">
            <?php if ($user['role'] === 'seniman'): ?>
                <div class="stat-card stat-artworks">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon">🎨</div>
                        <div class="stat-bg"></div>
                    </div>
            
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $artworkCount; ?></div>
                        <div class="stat-label">Karya</div>
                        <div class="stat-trend"><?php echo $artworkCount > 0 ? 'Aktif' : 'Belum ada'; ?></div>
                    </div>
                </div>
                <div class="stat-card stat-views">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon">👁️</div>
                        <div class="stat-bg"></div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($totalViews); ?></div>
                        <div class="stat-label">Views</div>
                        <div class="stat-trend">Total</div>
                    </div>
                </div>
                <div class="stat-card stat-likes">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon">❤️</div>
                        <div class="stat-bg"></div>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($totalLikes); ?></div>
                        <div class="stat-label">Likes</div>
                        <div class="stat-trend">Diterima</div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="stat-card stat-comments">
                <div class="stat-icon-wrapper">
                    <div class="stat-icon">💬</div>
                    <div class="stat-bg"></div>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo count($recentComments); ?></div>
                    <div class="stat-label">Komentar</div>
                    <div class="stat-trend">Terbaru</div>
                </div>
            </div>
        </div>

        <!-- Profile Tabs -->
        <div class="profile-tabs">
            <div class="tab-navigation">
                <button class="tab-btn <?php echo $activeTab === 'overview' ? 'active' : ''; ?>" data-tab="overview">
                    <span class="tab-icon">📊</span>
                    <span class="tab-text">Overview</span>
                </button>
                <button class="tab-btn <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" data-tab="profile">
                    <span class="tab-icon">✏️</span>
                    <span class="tab-text">Edit Profile</span>
                </button>
                <button class="tab-btn <?php echo $activeTab === 'security' ? 'active' : ''; ?>" data-tab="security">
                    <span class="tab-icon">🔒</span>
                    <span class="tab-text">Keamanan</span>
                </button>
                <?php if ($user['role'] === 'seniman'): ?>
                    <button class="tab-btn <?php echo $activeTab === 'artworks' ? 'active' : ''; ?>" data-tab="artworks">
                        <span class="tab-icon">🎨</span>
                        <span class="tab-text">Karya Saya</span>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Overview Tab -->
                <div class="tab-panel <?php echo $activeTab === 'overview' ? 'active' : ''; ?>" id="overview">
                    <div class="overview-grid">
                        <div class="overview-card">
                            <h3>📈 Aktivitas Terbaru</h3>
                            <div class="activity-list">
                                <?php if (empty($recentComments)): ?>
                                    <p class="no-activity">Belum ada aktivitas komentar</p>
                                <?php else: ?>
                                    <?php foreach ($recentComments as $comment): ?>
                                        <div class="activity-item">
                                            <div class="activity-content">
                                                <strong>Komentar pada:</strong> <?php echo htmlspecialchars($comment['artwork_title']); ?>
                                                <br>
                                                <span class="activity-text"><?php echo htmlspecialchars(substr($comment['comment'], 0, 100)) . (strlen($comment['comment']) > 100 ? '...' : ''); ?></span>
                                            </div>
                                            <div class="activity-date">
                                                <?php echo date('d M Y', strtotime($comment['created_at'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($user['role'] === 'seniman'): ?>
                            <div class="overview-card">
                                <h3>🎯 Pencapaian</h3>
                                <div class="achievements">
                                    <div class="achievement-item <?php echo $artworkCount >= 1 ? 'unlocked' : ''; ?>">
                                        <span class="achievement-icon">🎨</span>
                                        <span class="achievement-text">Upload Karya Pertama</span>
                                    </div>
                                    <div class="achievement-item <?php echo $artworkCount >= 5 ? 'unlocked' : ''; ?>">
                                        <span class="achievement-icon">⭐</span>
                                        <span class="achievement-text">5 Karya</span>
                                    </div>
                                    <div class="achievement-item <?php echo $totalViews >= 100 ? 'unlocked' : ''; ?>">
                                        <span class="achievement-icon">👁️</span>
                                        <span class="achievement-text">100 Views</span>
                                    </div>
                                    <div class="achievement-item <?php echo $totalLikes >= 50 ? 'unlocked' : ''; ?>">
                                        <span class="achievement-icon">❤️</span>
                                        <span class="achievement-text">50 Likes</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Edit Profile Tab -->
                <div class="tab-panel <?php echo $activeTab === 'profile' ? 'active' : ''; ?>" id="profile">
                    <div class="form-container">
                        <h3>Edit Profile</h3>
                        <form method="POST" enctype="multipart/form-data" class="profile-form">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <!-- Profile Photo Upload -->
                            <div class="form-group profile-photo-upload">
                                <label>Foto Profile</label>
                                <div class="photo-upload-wrapper">
                                    <div class="profile-photo-preview" id="photoPreview">
                                        <img src="" alt="Preview" id="previewImage">
                                    </div>
                                    <label for="profile_photo" class="file-input-label">
                                        📷 Pilih Foto
                                    </label>
                                    <input type="file" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png">
                                    <span class="file-name-display" id="fileName">Belum ada file dipilih</span>
                                    <small>Format: JPG, PNG. Maksimal 5MB</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="nama">Nama Lengkap</label>
                                <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small>Email tidak dapat diubah</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="bio">Bio</label>
                                <textarea id="bio" name="bio" rows="4" placeholder="Ceritakan tentang diri Anda..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div class="tab-panel <?php echo $activeTab === 'security' ? 'active' : ''; ?>" id="security">
                    <div class="form-container">
                        <h3>Ubah Password</h3>
                        <form method="POST" class="profile-form">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password">Password Lama</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">Password Baru</label>
                                <input type="password" id="new_password" name="new_password" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Konfirmasi Password Baru</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Ubah Password</button>
                        </form>
                    </div>
                </div>

                <!-- My Artworks Tab (Only for Seniman) -->
                <?php if ($user['role'] === 'seniman'): ?>
                    <div class="tab-panel <?php echo $activeTab === 'artworks' ? 'active' : ''; ?>" id="artworks">
                        <div class="artworks-header">
                            <h3>Karya Saya</h3>
                            <a href="upload.php" class="btn btn-primary">+ Upload Karya Baru</a>
                        </div>
                        
                        <?php if (empty($artworks)): ?>
                            <div class="no-artworks">
                                <div class="no-artworks-icon">🎨</div>
                                <h4>Belum ada karya</h4>
                                <p>Mulai unggah karya pertama Anda!</p>
                                <a href="upload.php" class="btn btn-primary">Upload Karya</a>
                            </div>
                        <?php else: ?>
                            <div class="artworks-grid">
                                <?php foreach ($artworks as $artwork): ?>
                                    <div class="artwork-card">
                                        <div class="artwork-image">
                                            <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                                                    alt="<?php echo htmlspecialchars($artwork['judul']); ?>"
                                                    onerror="this.src='lukisan.jpeg'">
                                            <div class="artwork-status status-<?php echo $artwork['status']; ?>">
                                                <?php echo ucfirst($artwork['status']); ?>
                                            </div>
                                        </div>
                                        <div class="artwork-info">
                                            <h4><?php echo htmlspecialchars($artwork['judul']); ?></h4>
                                            <p class="artwork-category">
                                                <?php echo htmlspecialchars($artwork['icon']); ?> <?php echo ucfirst(htmlspecialchars($artwork['nama_kategori'])); ?>
                                            </p>
                                            <div class="artwork-stats">
                                                <span>👁️ <?php echo $artwork['views']; ?></span>
                                                <span>❤️ <?php echo $artwork['likes']; ?></span>
                                            </div>
                                            <div class="artwork-actions">
                                                <a href="artwork_detail.php?id=<?php echo $artwork['id']; ?>" class="btn btn-sm">Lihat</a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus karya ini?')">
                                                    <input type="hidden" name="action" value="delete_artwork">
                                                    <input type="hidden" name="artwork_id" value="<?php echo $artwork['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

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
                
                dropdownMenu.querySelectorAll('a').forEach(function(link) {
                    link.addEventListener('click', function() {
                        dropdownMenu.classList.remove('show');
                    });
                });
            }

            // Tab functionality
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabPanels = document.querySelectorAll('.tab-panel');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabPanels.forEach(p => p.classList.remove('active'));
                    
                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');
                });
            });

            // Profile photo preview
            const profilePhotoInput = document.getElementById('profile_photo');
            const photoPreview = document.getElementById('photoPreview');
            const previewImage = document.getElementById('previewImage');
            const fileName = document.getElementById('fileName');
            
            if (profilePhotoInput) {
                profilePhotoInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    
                    if (file) {
                        fileName.textContent = file.name;
                        
                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImage.src = e.target.result;
                            photoPreview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        fileName.textContent = 'Belum ada file dipilih';
                        photoPreview.style.display = 'none';
                    }
                });
            }

            // Password confirmation validation
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                function validatePassword() {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Password tidak cocok');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                }
                
                newPassword.addEventListener('change', validatePassword);
                confirmPassword.addEventListener('keyup', validatePassword);
            }

            // Auto-hide alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>