<?php
// upload.php - FIXED VERSION dengan navbar konsisten
require_once 'config.php';

// Require login dan role seniman
requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'seniman') {
    redirect('index.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = sanitizeInput($_POST['judul']);
    $deskripsi = sanitizeInput($_POST['deskripsi']);
    $kategori = isset($_POST['kategori']) ? $_POST['kategori'] : [];
    $ageRestricted = isset($_POST['age_restricted']) ? 1 : 0;
    
    // DEBUG: Log kategori yang dipilih
    error_log("=== UPLOAD DEBUG ===");
    error_log("Kategori yang dipilih: " . print_r($kategori, true));
    error_log("Jumlah kategori: " . count($kategori));
    
    // Validate
    if (empty($judul) || empty($deskripsi) || empty($kategori)) {
        $error = 'Semua field wajib diisi dan minimal pilih satu kategori!';
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Gambar wajib diupload!';
    } else {
        // Handle file upload
        $filename = uploadFile($_FILES['image'], 'artwork_');
        
        if (!$filename) {
            $error = 'Gagal upload gambar! Pastikan file berformat JPG/PNG/GIF dan ukuran maksimal 5MB.';
        } else {
            try {
                $pdo = getConnection();
                
                // Get first category ID (for primary category in artworks table)
                $catStmt = $pdo->prepare("SELECT id FROM categories WHERE nama_kategori = ?");
                $catStmt->execute([$kategori[0]]);
                $category = $catStmt->fetch();
                
                if (!$category) {
                    $error = 'Kategori tidak valid!';
                    error_log("Kategori tidak ditemukan: " . $kategori[0]);
                } else {
                    // Insert artwork with age restriction
                    $stmt = $pdo->prepare("INSERT INTO artworks (judul, deskripsi, image_path, user_id, category_id, age_restricted, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
                    $imagePath = UPLOAD_DIR . $filename;
                    $stmt->execute([$judul, $deskripsi, $imagePath, $user['id'], $category['id'], $ageRestricted]);
                    
                    $artworkId = $pdo->lastInsertId();
                    error_log("Artwork ID created: " . $artworkId);
                    
                    // Insert ALL selected categories into artwork_categories table
                    $catInsertStmt = $pdo->prepare("INSERT IGNORE INTO artwork_categories (artwork_id, category_id) VALUES (?, ?)");
                    
                    $successCount = 0;
                    $insertedCategories = [];
                    
                    foreach ($kategori as $katNama) {
                        $getCatStmt = $pdo->prepare("SELECT id FROM categories WHERE nama_kategori = ?");
                        $getCatStmt->execute([$katNama]);
                        $cat = $getCatStmt->fetch();
                        
                        if ($cat) {
                            try {
                                $result = $catInsertStmt->execute([$artworkId, $cat['id']]);
                                if ($result) {
                                    $successCount++;
                                    $insertedCategories[] = $katNama;
                                    error_log("✓ Berhasil insert kategori: $katNama (ID: {$cat['id']})");
                                }
                            } catch (PDOException $e) {
                                error_log("✗ Gagal insert kategori $katNama: " . $e->getMessage());
                            }
                        } else {
                            error_log("✗ Kategori tidak ditemukan di database: $katNama");
                        }
                    }
                    
                    error_log("Total kategori berhasil diinsert: $successCount dari " . count($kategori));
                    
                    // Success message with category list
                    $kategoriList = implode(', ', $insertedCategories);
                    $success = '✅ Karya berhasil diupload dengan ' . $successCount . ' kategori (' . $kategoriList . ')! Menunggu persetujuan admin.' . ($ageRestricted ? ' 🔞 (Konten 17+)' : '');
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
                error_log("Database Error: " . $e->getMessage());
            }
        }
    }
}

// Get categories
try {
    $pdo = getConnection();
    $catStmt = $pdo->prepare("SELECT * FROM categories ORDER BY nama_kategori");
    $catStmt->execute();
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    error_log("Error fetching categories: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Karya - Galeri Art</title>
    <link rel="stylesheet" href="styles.css">
    
    <style>
        /* Fix navbar overlap */
        body {
            padding-top: 70px;
        }
        
        /* Modern Form Container */
        .form-container {
            max-width: 700px !important;
            margin: 100px auto 60px !important;
            background: white !important;
            padding: 40px !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1) !important;
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Heading with gradient underline */
        .form-container h2 {
            color: #1e3a8a !important;
            margin-bottom: 30px !important;
            text-align: center !important;
            font-size: 32px !important;
            font-weight: 700 !important;
            position: relative;
            padding-bottom: 15px;
        }
        
        .form-container h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            border-radius: 2px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-error {
            color: #991b1b;
            background: #fee2e2;
            border-left: 4px solid #ef4444;
        }
        
        .alert-success {
            color: #065f46;
            background: #d1fae5;
            border-left: 4px solid #10b981;
        }
        
        /* Modern Form Elements */
        form input[type="text"],
        form textarea,
        form input[type="file"] {
            width: 100% !important;
            padding: 14px 18px !important;
            margin: 12px 0 !important;
            border-radius: 12px !important;
            border: 2px solid #e5e7eb !important;
            font-family: "Poppins", sans-serif !important;
            font-size: 15px !important;
            background: #f9fafb !important;
            transition: all 0.3s ease !important;
            box-sizing: border-box;
        }
        
        form input[type="text"]:focus,
        form textarea:focus {
            border-color: #3b82f6 !important;
            background: white !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            transform: translateY(-1px);
            outline: none !important;
        }
        
        form textarea {
            resize: vertical !important;
            min-height: 100px !important;
        }
        
        /* Checkbox Categories Section */
        .checkbox-categories {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) !important;
            gap: 12px !important;
            margin: 20px 0 !important;
            padding: 20px !important;
            background: #f8fafc !important;
            border-radius: 12px !important;
            border: 2px dashed #cbd5e1 !important;
        }
        
        .checkbox-categories > label {
            grid-column: 1 / -1 !important;
            margin-bottom: 10px !important;
            font-weight: 700 !important;
            color: #1e3a8a !important;
            font-size: 16px !important;
            text-align: center !important;
        }
        
        /* Checkbox Item Modern Style */
        .checkbox-item {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 12px 15px !important;
            background: white !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 10px !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .checkbox-item:hover {
            background: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 20px !important;
            height: 20px !important;
            accent-color: #1e3a8a !important;
            cursor: pointer !important;
            margin: 0 !important;
        }
        
        .checkbox-item label {
            cursor: pointer !important;
            font-weight: 500 !important;
            color: #374151 !important;
            font-size: 14px !important;
            user-select: none;
            margin: 0 !important;
            flex: 1;
        }
        
        .checkbox-item.checked {
            background: #dbeafe !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .checkbox-item.checked label {
            color: #1e40af !important;
            font-weight: 600 !important;
        }
        
        /* Age Restriction Checkbox */
        .age-restriction {
            margin: 25px 0 !important;
            padding: 20px !important;
            background: #fff8f0 !important;
            border: 2px solid #fbbf24 !important;
            border-radius: 12px !important;
            transition: all 0.3s ease !important;
        }
        
        .age-restriction:hover {
            background: #fef3c7 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
        }
        
        .age-restriction input[type="checkbox"] {
            width: 22px !important;
            height: 22px !important;
            accent-color: #dc2626 !important;
        }
        
        .age-restriction label {
            font-weight: 600 !important;
            color: #92400e !important;
            font-size: 16px !important;
        }
        
        .age-restriction input[type="checkbox"]:checked + label {
            color: #dc2626 !important;
            font-weight: 700 !important;
        }
        
        .age-restriction small {
            display: block !important;
            color: #92400e !important;
            margin-top: 10px !important;
            line-height: 1.5 !important;
            font-size: 13px !important;
        }
        
        /* File Input Styling */
        form input[type="file"] {
            padding: 12px !important;
            background: #f8fafc !important;
            border: 2px dashed #cbd5e1 !important;
            cursor: pointer;
        }
        
        form input[type="file"]:hover {
            background: #f1f5f9 !important;
            border-color: #3b82f6 !important;
        }
        
        form small {
            color: #6b7280 !important;
            display: block !important;
            margin-top: -8px !important;
            margin-bottom: 15px !important;
            font-size: 13px !important;
            font-style: italic;
        }
        
        /* Submit Button */
        form button[type="submit"] {
            width: 100% !important;
            padding: 16px !important;
            margin-top: 20px !important;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6) !important;
            color: white !important;
            border: none !important;
            border-radius: 12px !important;
            cursor: pointer !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 4px 15px rgba(30, 58, 138, 0.3) !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        form button[type="submit"]:hover {
            background: linear-gradient(135deg, #3b82f6, #1e3a8a) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 58, 138, 0.4) !important;
        }
        
        form button[type="submit"]:active {
            transform: translateY(0);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-container {
                margin: 100px 20px 40px !important;
                padding: 30px 20px !important;
            }
            
            .checkbox-categories {
                grid-template-columns: 1fr !important;
            }
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
                        <a href="profile.php">Profile</a>
                        <a href="upload.php" class="active">Upload Karya</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <div class="form-container">
        <h2>Upload Karya Seni</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="judul" placeholder="Judul Karya" 
                value="<?php echo isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : ''; ?>" required>
            
            <textarea name="deskripsi" placeholder="Deskripsi singkat karya Anda..." required><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
            
            <div class="checkbox-categories">
                <label>📂 Pilih Kategori Karya (Bisa pilih lebih dari 1)</label>
                <?php foreach ($categories as $cat): ?>
                    <div class="checkbox-item" data-category="<?php echo htmlspecialchars($cat['nama_kategori']); ?>">
                        <input type="checkbox" 
                            id="kategori_<?php echo $cat['id']; ?>" 
                            name="kategori[]" 
                            value="<?php echo htmlspecialchars($cat['nama_kategori']); ?>"
                            <?php echo (isset($_POST['kategori']) && in_array($cat['nama_kategori'], $_POST['kategori'])) ? 'checked' : ''; ?>>
                        <label for="kategori_<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['icon']); ?> <?php echo ucfirst(htmlspecialchars($cat['nama_kategori'])); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Age Restriction Checkbox -->
            <div class="checkbox-item age-restriction">
                <input type="checkbox" 
                    id="age_restricted" 
                    name="age_restricted" 
                    value="1"
                    <?php echo (isset($_POST['age_restricted']) && $_POST['age_restricted']) ? 'checked' : ''; ?>>
                <label for="age_restricted">
                    🔞 Konten untuk 17 tahun ke atas
                </label>
                <small>
                    Centang jika karya ini mengandung konten yang mungkin tidak sesuai untuk anak di bawah 17 tahun (misalnya: nudity artistik, tema mature, atau konten yang memerlukan kematangan emosional).
                </small>
            </div>
            
            <input type="file" name="image" accept="image/*" required>
            <small>
                📎 Format: JPG, PNG, GIF. Maksimal 5MB.
            </small>
            
            <button type="submit">🚀 Upload Karya</button>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== Upload Form Initialized ===');
            
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

            // Checkbox category functionality
            const checkboxItems = document.querySelectorAll('.checkbox-item:not(.age-restriction)');
            console.log('Found checkbox items:', checkboxItems.length);
            
            checkboxItems.forEach(function(item) {
                const checkbox = item.querySelector('input[type="checkbox"]');
                
                // Set initial checked class
                if (checkbox.checked) {
                    item.classList.add('checked');
                }
                
                // Toggle checked class when checkbox changes
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        item.classList.add('checked');
                        console.log('Checked:', this.value);
                    } else {
                        item.classList.remove('checked');
                        console.log('Unchecked:', this.value);
                    }
                });
                
                // Click on item to toggle checkbox
                item.addEventListener('click', function(e) {
                    if (e.target !== checkbox && e.target.tagName !== 'LABEL') {
                        checkbox.checked = !checkbox.checked;
                        checkbox.dispatchEvent(new Event('change'));
                    }
                });
            });

            // Age restriction confirmation
            const ageCheckbox = document.getElementById('age_restricted');
            if (ageCheckbox) {
                ageCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        const confirmed = confirm(
                            '⚠️ Anda yakin karya ini memerlukan age restriction 17+?\n\n' +
                            'Konten ini hanya akan bisa diakses oleh pengunjung yang sudah melakukan verifikasi usia.'
                        );
                        if (!confirmed) {
                            this.checked = false;
                            this.parentElement.classList.remove('checked');
                        }
                    }
                });
            }
            
            // Form submit validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const checkedCategories = document.querySelectorAll('.checkbox-item:not(.age-restriction) input[type="checkbox"]:checked');
                console.log('=== FORM SUBMIT ===');
                console.log('Categories selected:', checkedCategories.length);
                checkedCategories.forEach(function(cb) {
                    console.log('- Category:', cb.value);
                });
                
                if (checkedCategories.length === 0) {
                    e.preventDefault();
                    alert('⚠️ Minimal pilih 1 kategori!');
                    return false;
                }
            });
        });
    </script>
</body>
</html>