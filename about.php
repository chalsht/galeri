<?php
// about.php - About page dengan PHP backend
require_once 'config.php';

$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang - Galeri Art</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="logo">🎨 Galeri Art</div>
        <nav>
            <ul>
                <?php if (isLoggedIn()): ?>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="galeri.php">Galeri</a></li>
                    <li><a href="about.php" class="active">About</a></li>
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
                    <li><a href="about.php" class="active">About</a></li>
                    <li><a href="login.php">Masuk</a></li>
                    <li><a href="register.php">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <section class="about">
        <div class="about-hero">
            <div class="hero-background">
                <div class="floating-shapes">
                    <div class="shape shape-1">🎨</div>
                    <div class="shape shape-2">🖼️</div>
                    <div class="shape shape-3">🎭</div>
                    <div class="shape shape-4">✨</div>
                    <div class="shape shape-5">🌟</div>
                </div>
            </div>
            <div class="about-hero-content">
                <div class="hero-badge">
                    <span class="badge-icon">🎨</span>
                    <span class="badge-text">Platform Seni Digital</span>
                </div>
                <h1 class="hero-title">
                    <span class="title-line-1">Tentang</span>
                    <span class="title-line-2">Galeri Art</span>
                </h1>
                <p class="about-hero-text">
                    Platform digital yang menghadirkan karya seni terbaik dari berbagai seniman di seluruh dunia. 
                    Kami berkomitmen untuk menyediakan ruang bagi seniman untuk memamerkan karya mereka dan 
                    bagi pecinta seni untuk menikmati keindahan karya seni yang beragam.
                </p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="stat-number">1000+</span>
                        <span class="stat-label">Karya Seni</span>
                    </div>
                    <div class="hero-stat">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Seniman</span>
                    </div>
                    <div class="hero-stat">
                        <span class="stat-number">50K+</span>
                        <span class="stat-label">Pengunjung</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="about-content">
            <!-- Mission & Vision -->
            <div class="about-section mission-vision-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="title-icon">🎯</span>
                        Misi & Visi Kami
                    </h2>
                    <p class="section-subtitle">Dedikasi kami untuk menghubungkan seniman dengan dunia</p>
                </div>
                <div class="about-grid">
                    <div class="about-card mission-card">
                        <div class="card-header">
                            <div class="about-card-icon">🎯</div>
                            <h3>Misi Kami</h3>
                        </div>
                        <div class="card-content">
                            <p>
                                Menyediakan platform yang inklusif dan mudah diakses bagi semua seniman untuk 
                                memamerkan karya mereka, sambil memberikan pengalaman yang menyenangkan bagi 
                                pengunjung untuk menikmati dan berinteraksi dengan seni.
                            </p>
                        </div>
                        <div class="card-footer">
                            <div class="mission-points">
                                <div class="point">✨ Inklusif</div>
                                <div class="point">🌍 Global</div>
                                <div class="point">🎨 Kreatif</div>
                            </div>
                        </div>
                    </div>
                    <div class="about-card vision-card">
                        <div class="card-header">
                            <div class="about-card-icon">👁️</div>
                            <h3>Visi Kami</h3>
                        </div>
                        <div class="card-content">
                            <p>
                                Menjadi platform seni digital terdepan yang menghubungkan seniman dengan 
                                komunitas global, mendorong kreativitas, dan mempromosikan apresiasi seni 
                                di era digital.
                            </p>
                        </div>
                        <div class="card-footer">
                            <div class="vision-points">
                                <div class="point">🚀 Terdepan</div>
                                <div class="point">🤝 Komunitas</div>
                                <div class="point">💡 Inovasi</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="about-section features-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="title-icon">✨</span>
                        Fitur Unggulan
                    </h2>
                    <p class="section-subtitle">Teknologi terdepan untuk pengalaman seni yang luar biasa</p>
                </div>
                <div class="features-grid">
                    <div class="feature-item feature-search">
                        <div class="feature-icon-wrapper">
                            <div class="feature-icon">🔍</div>
                            <div class="feature-bg"></div>
                        </div>
                        <div class="feature-content">
                            <h4>Pencarian Cerdas</h4>
                            <p>Pencarian yang mudah dan cepat untuk menemukan karya seni favorit Anda</p>
                            <div class="feature-tag">AI Powered</div>
                        </div>
                    </div>
                    <div class="feature-item feature-comments">
                        <div class="feature-icon-wrapper">
                            <div class="feature-icon">💬</div>
                            <div class="feature-bg"></div>
                        </div>
                        <div class="feature-content">
                            <h4>Komentar & Rating</h4>
                            <p>Berikan feedback dan rating untuk karya seni yang Anda sukai</p>
                            <div class="feature-tag">Interactive</div>
                        </div>
                    </div>
                    <div class="feature-item feature-upload">
                        <div class="feature-icon-wrapper">
                            <div class="feature-icon">🎨</div>
                            <div class="feature-bg"></div>
                        </div>
                        <div class="feature-content">
                            <h4>Upload Mudah</h4>
                            <p>Seniman dapat dengan mudah mengupload dan mengelola karya mereka</p>
                            <div class="feature-tag">User Friendly</div>
                        </div>
                    </div>
                    <div class="feature-item feature-security">
                        <div class="feature-icon-wrapper">
                            <div class="feature-icon">🔒</div>
                            <div class="feature-bg"></div>
                        </div>
                        <div class="feature-content">
                            <h4>Keamanan Data</h4>
                            <p>Data dan karya seni Anda aman dengan sistem keamanan terbaik</p>
                            <div class="feature-tag">Secure</div>
                        </div>
                    </div>
                    <div class="feature-item feature-responsive">
                        <div class="feature-icon-wrapper">
                            <div class="feature-icon">📱</div>
                            <div class="feature-bg"></div>
                        </div>
                        <div class="feature-content">
                            <h4>Responsive Design</h4>
                            <p>Akses dari berbagai perangkat dengan tampilan yang optimal</p>
                            <div class="feature-tag">Cross Platform</div>
                        </div>
                    </div>
                    <div class="feature-item feature-community">
                        <div class="feature-icon-wrapper">
                            <div class="feature-icon">🌍</div>
                            <div class="feature-bg"></div>
                        </div>
                        <div class="feature-content">
                            <h4>Komunitas Global</h4>
                            <p>Bergabung dengan komunitas seniman dan pecinta seni dari seluruh dunia</p>
                            <div class="feature-tag">Global</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="about-section">
                <h2>🎭 Kategori Karya Seni</h2>
                <div class="categories-showcase">
                    <?php
                    try {
                        $pdo = getConnection();
                        $catStmt = $pdo->prepare("SELECT * FROM categories ORDER BY nama_kategori");
                        $catStmt->execute();
                        $categories = $catStmt->fetchAll();
                        
                        foreach ($categories as $category):
                    ?>
                        <div class="category-showcase-item">
                            <div class="category-icon"><?php echo htmlspecialchars($category['icon']); ?></div>
                            <h4><?php echo ucfirst(htmlspecialchars($category['nama_kategori'])); ?></h4>
                            <p><?php echo htmlspecialchars($category['deskripsi']); ?></p>
                        </div>
                    <?php 
                        endforeach;
                    } catch (PDOException $e) {
                        // Fallback categories
                        $fallbackCategories = [
                            ['icon' => '🎨', 'nama' => 'Painting', 'desc' => 'Lukisan tradisional dan modern'],
                            ['icon' => '💻', 'nama' => 'Digital', 'desc' => 'Karya seni digital dan ilustrasi'],
                            ['icon' => '📷', 'nama' => 'Photography', 'desc' => 'Fotografi artistik dan dokumenter'],
                            ['icon' => '🗿', 'nama' => 'Sculpture', 'desc' => 'Patung dan karya tiga dimensi'],
                            ['icon' => '🌀', 'nama' => 'Abstract', 'desc' => 'Seni abstrak dan eksperimental'],
                            ['icon' => '👤', 'nama' => 'Portrait', 'desc' => 'Potret dan karya figuratif']
                        ];
                        
                        foreach ($fallbackCategories as $category):
                    ?>
                        <div class="category-showcase-item">
                            <div class="category-icon"><?php echo $category['icon']; ?></div>
                            <h4><?php echo $category['nama']; ?></h4>
                            <p><?php echo $category['desc']; ?></p>
                        </div>
                    <?php 
                        endforeach;
                    }
                    ?>
                </div>
            </div>

            <!-- Statistics -->
            <div class="about-section">
                <h2>📊 Statistik Platform</h2>
                <div class="stats-grid">
                    <?php
                    try {
                        $pdo = getConnection();
                        
                        // Get total artworks
                        $artworkStmt = $pdo->prepare("SELECT COUNT(*) as total FROM artworks WHERE status = 'approved'");
                        $artworkStmt->execute();
                        $totalArtworks = $artworkStmt->fetchColumn();
                        
                        // Get total artists
                        $artistStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'seniman'");
                        $artistStmt->execute();
                        $totalArtists = $artistStmt->fetchColumn();
                        
                        // Get total views
                        $viewsStmt = $pdo->prepare("SELECT SUM(views) as total FROM artworks WHERE status = 'approved'");
                        $viewsStmt->execute();
                        $totalViews = $viewsStmt->fetchColumn() ?: 0;
                        
                        // Get total comments
                        $commentsStmt = $pdo->prepare("SELECT COUNT(*) as total FROM comments WHERE status = 'approved'");
                        $commentsStmt->execute();
                        $totalComments = $commentsStmt->fetchColumn();
                        
                    } catch (PDOException $e) {
                        $totalArtworks = 0;
                        $totalArtists = 0;
                        $totalViews = 0;
                        $totalComments = 0;
                    }
                    ?>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($totalArtworks); ?></div>
                        <div class="stat-label">Karya Seni</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($totalArtists); ?></div>
                        <div class="stat-label">Seniman</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($totalViews); ?></div>
                        <div class="stat-label">Total Views</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($totalComments); ?></div>
                        <div class="stat-label">Komentar</div>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="about-cta">
                <h2>🚀 Bergabunglah dengan Kami!</h2>
                <p>
                    Apakah Anda seorang seniman yang ingin memamerkan karya, atau pecinta seni yang ingin 
                    menikmati keindahan karya seni? Bergabunglah dengan komunitas Galeri Art sekarang juga!
                </p>
                <div class="cta-buttons">
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-primary">Daftar Sekarang</a>
                        <a href="login.php" class="btn btn-secondary">Masuk</a>
                    <?php else: ?>
                        <a href="galeri.php" class="btn btn-primary">Jelajahi Galeri</a>
                        <?php if ($user['role'] === 'seniman'): ?>
                            <a href="upload.php" class="btn btn-secondary">Upload Karya</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script>
        // JavaScript untuk dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
</body>
</html>