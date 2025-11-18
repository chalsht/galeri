<?php
// galeri.php - Gallery page dengan FIX icon matching issue
require_once 'config.php';

$user = getCurrentUser();
$showAgeModal = false;
$ageCategory = null;

// Get search parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';

// Age verification handling
if (!isLoggedIn()) {
    if (isset($_POST['age_category'])) {
        $ageCategory = sanitizeInput($_POST['age_category']);
        $_SESSION['age_category'] = $ageCategory;
        $_SESSION['age_verified'] = true;
    } elseif (isset($_SESSION['age_category'])) {
        $ageCategory = $_SESSION['age_category'];
    } else {
        $showAgeModal = true;
    }
} else {
    $ageCategory = '17_plus';
}

try {
    $pdo = getConnection();
    
    // ✅ FIXED: Gabungkan icon dan nama kategori dalam 1 GROUP_CONCAT untuk maintain urutan
    $sql = "SELECT a.*, u.nama as artist_name, 
            GROUP_CONCAT(DISTINCT CONCAT(c.icon, ':::', c.nama_kategori) 
                         ORDER BY c.nama_kategori SEPARATOR '|||') as category_data
            FROM artworks a 
            JOIN users u ON a.user_id = u.id 
            LEFT JOIN artwork_categories ac ON a.id = ac.artwork_id
            LEFT JOIN categories c ON ac.category_id = c.id 
            WHERE a.status = 'approved'";
    
    $params = [];
    
    // Add age restriction filter
    if (!$showAgeModal && $ageCategory === 'under_17') {
        $sql .= " AND a.age_restricted = 0";
    }
    
    if (!empty($search)) {
        $sql .= " AND (a.judul LIKE ? OR a.deskripsi LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($category)) {
        $sql .= " AND EXISTS (
            SELECT 1 FROM artwork_categories ac2 
            JOIN categories c2 ON ac2.category_id = c2.id 
            WHERE ac2.artwork_id = a.id 
            AND c2.nama_kategori = ?
        )";
        $params[] = $category;
    }
    
    $sql .= " GROUP BY a.id ORDER BY a.featured DESC, a.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $artworks = $stmt->fetchAll();
    
    // Get all categories for filter
    $catStmt = $pdo->prepare("SELECT * FROM categories ORDER BY nama_kategori");
    $catStmt->execute();
    $categories = $catStmt->fetchAll();
    
    // Get statistics
    $totalArtworks = count($artworks);
    $restrictedCount = 0;
    if (!$showAgeModal && $ageCategory === 'under_17') {
        $restrictedStmt = $pdo->prepare("SELECT COUNT(*) FROM artworks WHERE status = 'approved' AND age_restricted = 1");
        $restrictedStmt->execute();
        $restrictedCount = $restrictedStmt->fetchColumn();
    }
    
} catch (PDOException $e) {
    $artworks = [];
    $categories = [];
    $totalArtworks = 0;
    $restrictedCount = 0;
    error_log("Gallery Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri Art - Galeri</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="logo">🎨 Galeri Art</div>
        <nav>
            <ul>
                <?php if (isLoggedIn()): ?>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="galeri.php" class="active">Galeri</a></li>
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
                    <li><a href="galeri.php" class="active">Galeri</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="login.php">Masuk</a></li>
                    <li><a href="register.php">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Age Verification Modal -->
    <?php if ($showAgeModal): ?>
    <div id="ageVerificationModal" class="modal-overlay">
        <div class="modal-content">
            <h3>🔞 Verifikasi Usia</h3>
            <p>Galeri kami memiliki berbagai jenis karya seni. Beberapa karya mungkin mengandung konten yang memerlukan kematangan usia.</p>
            <p><strong>Mohon konfirmasi usia Anda:</strong></p>
            <div class="age-buttons">
                <button onclick="setAgeCategory('under_17')" class="age-btn under-17">
                    Di bawah 17 tahun
                </button>
                <button onclick="setAgeCategory('17_plus')" class="age-btn over-17">
                    17 tahun ke atas
                </button>
            </div>
            <div class="age-info">
                <div class="age-option-info">
                    <div class="info-item">
                        <span class="info-icon">👶</span>
                        <div>
                            <strong>Di bawah 17 tahun:</strong><br>
                            <small>Hanya menampilkan karya yang sesuai untuk semua usia</small>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-icon">🔞</span>
                        <div>
                            <strong>17 tahun ke atas:</strong><br>
                            <small>Menampilkan semua karya termasuk konten mature</small>
                        </div>
                    </div>
                </div>
            </div>
            <p class="age-note">Pilihan ini akan berlaku selama sesi browsing Anda.</p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Age Category Info -->
    <?php if (!$showAgeModal && !isLoggedIn()): ?>
    <div class="age-info-bar">
        <div class="age-status">
            <?php if ($ageCategory === 'under_17'): ?>
                <span class="age-indicator safe">👶 Mode Aman: Konten untuk semua usia</span>
                <?php if ($restrictedCount > 0): ?>
                    <small><?php echo $restrictedCount; ?> karya mature disembunyikan</small>
                <?php endif; ?>
            <?php else: ?>
                <span class="age-indicator full">🔞 Mode Lengkap: Semua konten ditampilkan</span>
            <?php endif; ?>
            <button onclick="changeAgeCategory()" class="change-age-btn">Ubah</button>
        </div>
    </div>
    <?php endif; ?>
    
    <section class="gallery">
        <h2>
            Koleksi Karya 
            <small>(<?php echo $totalArtworks; ?> karya)</small>
        </h2>
        
        <!-- Search Container -->
        <div class="search-container">
            <div class="search-wrapper">
                <input type="text" id="searchInput" placeholder="Search All Art Works" class="search-input">
                <select id="categorySelect" class="category-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['nama_kategori']); ?>">
                            <?php echo ucfirst(htmlspecialchars($cat['nama_kategori'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="search-btn" id="searchBtn">🔍</button>
            </div>
        </div>
        
        <!-- Gallery Grid -->
        <div class="grid" style="<?php echo $showAgeModal ? 'display: none;' : ''; ?>">
            <?php if (empty($artworks)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                    <h3>Tidak ada karya ditemukan</h3>
                    <?php if (!$showAgeModal && $ageCategory === 'under_17' && $restrictedCount > 0): ?>
                        <p>Ada <?php echo $restrictedCount; ?> karya mature yang disembunyikan untuk mode aman.</p>
                        <button onclick="changeAgeCategory()" class="btn">Lihat Semua Konten</button>
                    <?php else: ?>
                        <p>Coba ubah kata kunci pencarian atau kategori.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($artworks as $artwork): ?>
                    <?php 
                    // ✅ FIXED: Parse category_data yang sudah digabung icon:::nama
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
                    
                    $first_category = !empty($categories_list) ? $categories_list[0] : '';
                    $all_categories_str = implode(',', $categories_list);
                    ?>
                    <div class="card" data-category="<?php echo htmlspecialchars($first_category); ?>" data-all-categories="<?php echo htmlspecialchars($all_categories_str); ?>">
                        <a href="artwork_detail.php?id=<?php echo $artwork['id']; ?>" class="card-link">
                            <div class="card-image-container">
                                <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($artwork['judul']); ?>"
                                     onerror="this.src='lukisan.jpeg'">
                                <?php if ($artwork['age_restricted'] && $ageCategory === '17_plus'): ?>
                                    <div class="age-indicator-card">🔞</div>
                                <?php endif; ?>
                            </div>
                            <h3><?php echo htmlspecialchars($artwork['judul']); ?></h3>
                            <p><strong>Oleh:</strong> <?php echo htmlspecialchars($artwork['artist_name']); ?></p>
                            
                            <!-- ✅ FIXED: Display kategori dengan icon yang BENAR -->
                            <p style="margin: 10px 0;">
                                <strong>Kategori:</strong><br>
                                <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px;">
                                    <?php 
                                    foreach ($categories_list as $index => $cat): 
                                        $icon = isset($icons_list[$index]) ? $icons_list[$index] : '🎨';
                                    ?>
                                        <span style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; border: 1px solid #93c5fd;">
                                            <?php echo htmlspecialchars($icon) . ' ' . ucfirst(htmlspecialchars($cat)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </p>
                            
                            <p style="color: #6b7280; font-size: 13px; line-height: 1.5;">
                                <?php echo htmlspecialchars(substr($artwork['deskripsi'], 0, 100)) . (strlen($artwork['deskripsi']) > 100 ? '...' : ''); ?>
                            </p>
                            
                            <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 5px;">
                                <small>👁️ <?php echo $artwork['views']; ?> views</small>
                                <small>❤️ <?php echo $artwork['likes']; ?> likes</small>
                                <?php if ($artwork['featured']): ?>
                                    <small style="background: #60a5fa; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">FEATURED</small>
                                <?php endif; ?>
                                <?php if ($artwork['age_restricted'] && $ageCategory === '17_plus'): ?>
                                    <small style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">17+</small>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 16px;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .modal-content h3 {
            color: #dc2626;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .age-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 30px 0;
        }
        .age-btn {
            padding: 15px 25px;
            border: none;
            border-radius: 12px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 160px;
        }
        .age-btn.under-17 {
            background: #10b981;
            color: white;
        }
        .age-btn.over-17 {
            background: #dc2626;
            color: white;
        }
        .age-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .age-info {
            margin: 20px 0;
            text-align: left;
        }
        .age-option-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-icon {
            font-size: 20px;
        }
        .age-note {
            color: #6b7280;
            font-size: 14px;
            margin-top: 20px;
        }
        .age-info-bar {
            background: #f3f4f6;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            margin-top: 60px;
            position: relative;
            z-index: 10;
        }
        .age-status {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .age-status {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .age-info-bar {
                margin-top: 70px;
            }
        }
        .age-indicator.safe {
            color: #10b981;
            font-weight: bold;
        }
        .age-indicator.full {
            color: #dc2626;
            font-weight: bold;
        }
        .change-age-btn {
            background: #6b7280;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: background-color 0.2s;
            white-space: nowrap;
        }
        .change-age-btn:hover {
            background: #4b5563;
        }
        
        .gallery {
            margin-top: 20px;
        }
        .card-image-container {
            position: relative;
            overflow: hidden;
        }
        .age-indicator-card {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #ef4444;
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: bold;
            z-index: 2;
        }
    </style>

    <!-- Form tersembunyi untuk age category submission -->
    <form id="ageForm" method="POST" style="display: none;">
        <input type="hidden" id="ageCategoryInput" name="age_category" value="">
    </form>

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

            // Search functionality dengan support multiple categories
            const searchInput = document.getElementById('searchInput');
            const categorySelect = document.getElementById('categorySelect');
            const searchBtn = document.getElementById('searchBtn');
            const cards = document.querySelectorAll('.card');

            function filterGaleri() {
                const searchText = searchInput ? searchInput.value.toLowerCase() : '';
                const categoryVal = categorySelect ? categorySelect.value : '';

                cards.forEach(card => {
                    const title = card.querySelector('h3').innerText.toLowerCase();
                    const description = card.querySelector('p:last-of-type').innerText.toLowerCase();
                    
                    const allCategories = card.getAttribute('data-all-categories') || '';
                    const categoryArray = allCategories.split(',').map(c => c.trim().toLowerCase());

                    const matchesSearch = title.includes(searchText) || description.includes(searchText);
                    const matchesCategory = categoryVal === '' || categoryArray.includes(categoryVal.toLowerCase());

                    if (matchesSearch && matchesCategory) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', filterGaleri);
            }

            if (categorySelect) {
                categorySelect.addEventListener('change', filterGaleri);
            }

            if (searchBtn) {
                searchBtn.addEventListener('click', filterGaleri);
            }

            filterGaleri();
        });

        // Age verification functions
        function setAgeCategory(category) {
            document.getElementById('ageCategoryInput').value = category;
            document.getElementById('ageForm').submit();
        }

        function changeAgeCategory() {
            fetch('clear_age_session.php', { method: 'POST' })
                .then(() => {
                    window.location.reload();
                })
                .catch(() => {
                    window.location.reload();
                });
        }
    </script>
</body>
</html>