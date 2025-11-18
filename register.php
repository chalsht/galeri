<?php
// register.php - Registration page dengan admin role
require_once 'config.php';

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitizeInput($_POST['nama']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $role = sanitizeInput($_POST['role']);
    $ageCategory = sanitizeInput($_POST['age_category']);
    
    // Validation
    if (empty($nama) || empty($email) || empty($password) || empty($role) || empty($ageCategory)) {
        $error = 'Semua field wajib diisi!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif (!in_array($role, ['seniman', 'pengunjung', 'admin'])) {
        $error = 'Role tidak valid!';
    } elseif (!in_array($ageCategory, ['under_17', '17_plus'])) {
        $error = 'Kategori usia tidak valid!';
    } else {
        try {
            $pdo = getConnection();
            
            // Check if email already exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch()) {
                $error = 'Email sudah terdaftar!';
            } else {
                // Insert new user
                $hashedPassword = hashPassword($password);
                $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nama, $email, $hashedPassword, $role]);
                
                $userId = $pdo->lastInsertId();
                
                // Store age category in session for immediate use
                setAgeVerification($ageCategory);
                
                $success = 'Registrasi berhasil! Silakan login.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Galeri Art</title>
    <link rel="stylesheet" href="styles.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow-y: auto !important;
        }
        
        .register-container {
            min-height: 100vh !important;
            height: auto !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #8b5cf6 100%) !important;
            padding: 120px 20px 50px !important;
            overflow-y: auto !important;
            box-sizing: border-box;
        }
        
        .form-box {
            background: white !important;
            padding: 40px !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
            width: 100% !important;
            max-width: 450px !important;
            text-align: center !important;
            margin: 20px auto !important;
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
        
        .form-box h2 {
            margin-bottom: 25px !important;
            color: #1e3a8a !important;
            font-size: 28px !important;
            font-weight: 700 !important;
            position: relative;
            padding-bottom: 15px;
        }
        
        .form-box h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            border-radius: 2px;
        }
        
        .input-group {
            text-align: left !important;
            margin-bottom: 20px !important;
        }
        
        .input-group label {
            display: block !important;
            margin-bottom: 8px !important;
            font-weight: 600 !important;
            color: #374151 !important;
            font-size: 14px !important;
        }
        
        .input-group input,
        .input-group select {
            width: 100% !important;
            padding: 12px 15px !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 10px !important;
            outline: none !important;
            transition: all 0.3s ease !important;
            font-size: 15px !important;
            background: #f9fafb !important;
            box-sizing: border-box;
        }
        
        .input-group input:focus,
        .input-group select:focus {
            border-color: #3b82f6 !important;
            background: white !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            transform: translateY(-1px);
        }
        
        /* Highlight admin option */
        .input-group select option[value="admin"] {
            background: #fef3c7;
            color: #92400e;
            font-weight: 600;
        }
        
        .btn.full-width {
            width: 100% !important;
            padding: 14px !important;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6) !important;
            border-radius: 10px !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            margin-top: 10px !important;
            box-shadow: 0 4px 15px rgba(30, 58, 138, 0.3) !important;
            transition: all 0.3s ease !important;
            border: none !important;
            color: white !important;
            cursor: pointer;
        }
        
        .btn.full-width:hover {
            background: linear-gradient(135deg, #3b82f6, #1e3a8a) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 58, 138, 0.4) !important;
        }
        
        .login-link {
            margin-top: 20px !important;
            font-size: 14px !important;
            color: #6b7280 !important;
        }
        
        .login-link a {
            color: #1e3a8a !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
        }
        
        .login-link a:hover {
            color: #3b82f6 !important;
            text-decoration: underline !important;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
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
                <li><a href="login.php">Masuk</a></li>
                <li><a href="register.php" class="active">Daftar</a></li>
            </ul>
        </nav>
    </header>

    <section class="register-container">
        <div class="form-box">
            <h2>Buat Akun Baru</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <br><a href="login.php" style="color: #1e3a8a;">Klik di sini untuk login</a>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" placeholder="Masukkan nama" 
                            value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Masukkan email" 
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Buat password" required>
                </div>
                <div class="input-group">
                    <label for="role">Daftar sebagai</label>
                    <select id="role" name="role" required>
                        <option value="">--Pilih--</option>
                        <option value="seniman" <?php echo (isset($_POST['role']) && $_POST['role'] == 'seniman') ? 'selected' : ''; ?>>🎨 Seniman</option>
                        <option value="pengunjung" <?php echo (isset($_POST['role']) && $_POST['role'] == 'pengunjung') ? 'selected' : ''; ?>>👤 Pengunjung</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>🔐 Admin</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="age_category">Kategori Usia</label>
                    <select id="age_category" name="age_category" required>
                        <option value="">--Pilih Kategori Usia--</option>
                        <option value="under_17" <?php echo (isset($_POST['age_category']) && $_POST['age_category'] == 'under_17') ? 'selected' : ''; ?>>Di bawah 17 tahun</option>
                        <option value="17_plus" <?php echo (isset($_POST['age_category']) && $_POST['age_category'] == '17_plus') ? 'selected' : ''; ?>>17 tahun ke atas</option>
                    </select>
                </div>
                <button type="submit" class="btn full-width">Daftar</button>
                <p class="login-link">Sudah punya akun? <a href="login.php">Masuk</a></p>
            </form>
        </div>
    </section>
</body>
</html>