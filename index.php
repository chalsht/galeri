<?php
// index.php - Homepage dengan PHP
require_once 'config.php';

$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri Art</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">🎨 Galeri Art</div>
        <nav>
            <ul>
                <?php if (isLoggedIn()): ?>
                    <li><a href="index.php" class="active">Home</a></li>
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
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="galeri.php">Galeri</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="login.php">Masuk</a></li>
                    <li><a href="register.php">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- Age Verification Modal -->
    <?php if (!isLoggedIn() && !isAgeVerified()): ?>
    <div id="ageVerificationModal" class="modal-overlay">
        <div class="modal-content">
            <h3>🔞 Verifikasi Usia</h3>
            <p>Untuk mengakses konten galeri, mohon konfirmasi kategori usia Anda:</p>
            <div class="age-buttons">
                <button onclick="setAgeCategory('under_17')" class="age-btn under-17">
                    Di bawah 17 tahun
                </button>
                <button onclick="setAgeCategory('17_plus')" class="age-btn over-17">
                    17 tahun ke atas
                </button>
            </div>
            <p class="age-note">Informasi ini akan digunakan untuk menampilkan konten yang sesuai dengan usia Anda.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Logout Success Message -->
    <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
    <div class="success-message">
        <div class="message-content">
            <span class="message-icon">✅</span>
            <span class="message-text">Anda telah berhasil logout. Terima kasih telah mengunjungi Galeri Art!</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-text">
            <h1>Welcome To Galeri Art</h1>
            <p>GaleriArt adalah platform digital yang mewadahi karya seni dari berbagai seniman,
                baik pemula maupun profesional.<br> Kami percaya bahwa setiap karya memiliki cerita,<br>
                dan platform ini adalah jembatan untuk memperkenalkan seni kepada dunia.</p>
            <a href="galeri.php" class="btn">Lihat Galeri</a>
        </div>
    </section>

    <script>
        // JavaScript untuk dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const userDropdown = document.querySelector('.user-dropdown');
            
            if (userDropdown) {
                const userNameLink = userDropdown.querySelector('.user-name');
                const dropdownMenu = userDropdown.querySelector('.dropdown-menu');
                
                // Toggle dropdown
                userNameLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
                
                // Close dropdown when clicking menu items
                dropdownMenu.querySelectorAll('a').forEach(function(link) {
                    link.addEventListener('click', function() {
                        dropdownMenu.classList.remove('show');
                    });
                });
            }
        });

        // Age verification function
        function setAgeCategory(category) {
            // Send AJAX request to set age verification
            fetch('age_verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'age_category=' + encodeURIComponent(category)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide modal
                    const modal = document.getElementById('ageVerificationModal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                    // Reload page to apply age filtering
                    window.location.reload();
                } else {
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            });
        }

        // Auto-hide success message
        const successMessage = document.querySelector('.success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                successMessage.style.transform = 'translateX(-50%) translateY(-20px)';
                setTimeout(() => {
                    successMessage.remove();
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>