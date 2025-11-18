<?php
require_once 'config.php';

// === CEK ROLE ADMIN ===
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/* ===========================================================
   ACTION HANDLERS ADMIN
   =========================================================== */

// APPROVE KARYA
if (isset($_POST['approve_art'])) {
    $id = intval($_POST['art_id']);

    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE artworks SET status='approved' WHERE id=?");
        $stmt->execute([$id]);
        header("Location: admin.php?msg=approved");
        exit;
    } catch (PDOException $e) {
        error_log("Error approving artwork: " . $e->getMessage());
    }
}

// REJECT / DELETE KARYA
if (isset($_POST['reject_art'])) {
    $id = intval($_POST['art_id']);

    try {
        $pdo = getConnection();
        
        // Hapus file gambar jika ada
        $stmt = $pdo->prepare("SELECT image_path FROM artworks WHERE id=?");
        $stmt->execute([$id]);
        $artwork = $stmt->fetch();
        
        if ($artwork && !empty($artwork['image_path']) && file_exists($artwork['image_path'])) {
            unlink($artwork['image_path']);
        }

        $stmt = $pdo->prepare("DELETE FROM artworks WHERE id=?");
        $stmt->execute([$id]);

        header("Location: admin.php?msg=rejected");
        exit;
    } catch (PDOException $e) {
        error_log("Error deleting artwork: " . $e->getMessage());
    }
}

// TOGGLE FEATURED
if (isset($_POST['toggle_featured'])) {
    $id = intval($_POST['art_id']);

    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT featured FROM artworks WHERE id=?");
        $stmt->execute([$id]);
        $featured = $stmt->fetchColumn();

        $newStatus = ($featured == 1) ? 0 : 1;

        $stmt = $pdo->prepare("UPDATE artworks SET featured=? WHERE id=?");
        $stmt->execute([$newStatus, $id]);

        header("Location: admin.php?msg=featured_changed");
        exit;
    } catch (PDOException $e) {
        error_log("Error toggling featured: " . $e->getMessage());
    }
}

// DELETE KOMENTAR
if (isset($_POST['delete_comment'])) {
    $id = intval($_POST['comment_id']);

    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id=?");
        $stmt->execute([$id]);

        header("Location: admin.php?msg=comment_deleted");
        exit;
    } catch (PDOException $e) {
        error_log("Error deleting comment: " . $e->getMessage());
    }
}

// APPROVE KOMENTAR
if (isset($_POST['approve_comment'])) {
    $id = intval($_POST['comment_id']);

    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE comments SET status='approved' WHERE id=?");
        $stmt->execute([$id]);

        header("Location: admin.php?msg=comment_approved");
        exit;
    } catch (PDOException $e) {
        error_log("Error approving comment: " . $e->getMessage());
    }
}

/* ===========================================================
   AMBIL DATA UNTUK DASHBOARD
   =========================================================== */

try {
    $pdo = getConnection();
    
    // Jumlah statistik
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalArt = $pdo->query("SELECT COUNT(*) FROM artworks")->fetchColumn();
    $pendingArt = $pdo->query("SELECT COUNT(*) FROM artworks WHERE status='pending'")->fetchColumn();
    $totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();

    // Data karya pending dengan info user - FIXED: Menggunakan COALESCE untuk handle NULL
    $pendingStmt = $pdo->query("
        SELECT a.*, COALESCE(u.nama, 'Unknown User') as username 
        FROM artworks a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.status='pending' 
        ORDER BY a.created_at DESC
    ");
    $pendingList = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

    // Semua karya dengan info user - FIXED: Menggunakan COALESCE untuk handle NULL
    $allArtStmt = $pdo->query("
        SELECT a.*, COALESCE(u.nama, 'Unknown User') as username 
        FROM artworks a 
        LEFT JOIN users u ON a.user_id = u.id 
        ORDER BY a.created_at DESC
    ");
    $allArt = $allArtStmt->fetchAll(PDO::FETCH_ASSOC);

    // Semua komentar dengan info user dan artwork - FIXED: Menggunakan COALESCE untuk handle NULL
    $commentStmt = $pdo->query("
        SELECT c.*, 
               COALESCE(u.nama, 'Unknown User') as username, 
               COALESCE(a.judul, 'Unknown Artwork') as artwork_title 
        FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        LEFT JOIN artworks a ON c.artwork_id = a.id 
        ORDER BY c.created_at DESC
    ");
    $allComments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Semua user - FIXED: Menggunakan tanggal_daftar bukan created_at
    $userStmt = $pdo->query("SELECT * FROM users ORDER BY tanggal_daftar DESC");
    $allUsers = $userStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "<div style='background:red;color:white;padding:20px;margin:20px;border-radius:10px;'>";
    echo "<strong>Database Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    $totalUsers = $totalArt = $pendingArt = $totalComments = 0;
    $pendingList = $allArt = $allComments = $allUsers = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Galeri Art</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            padding-top: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .admin-header {
            background: white;
            padding: 25px 35px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            color: #667eea;
            font-size: 32px;
            margin: 0;
        }
        
        .alert {
            background: #2ecc71;
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px;
        }
        
        .stat-box { 
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
        }
        
        .stat-box .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .stat-box .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        
        .stat-box .label {
            color: #666;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
            font-size: 24px;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        table thead {
            background: #667eea;
            color: white;
        }
        
        th, td { 
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            margin: 2px;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-approve {
            background: #2ecc71;
            color: white;
        }
        
        .btn-approve:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }
        
        .btn-reject, .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-reject:hover, .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .btn-featured {
            background: #f39c12;
            color: white;
        }
        
        .btn-featured:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-pending {
            background: #ffeaa7;
            color: #d63031;
        }
        
        .badge-approved {
            background: #55efc4;
            color: #00b894;
        }
        
        .badge-featured {
            background: #fdcb6e;
            color: #e17055;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }
        
        .artwork-preview {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <!-- Header dengan Navbar -->
    <header>
        <div class="logo">🎨 Galeri Art</div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="galeri.php">Galeri</a></li>
                <li><a href="about.php">About</a></li>
                <li class="user-dropdown">
                    <a href="#" class="user-name"><?php echo htmlspecialchars($_SESSION['user_nama']); ?> <span class="dropdown-arrow">▼</span></a>
                    <div class="dropdown-menu">
                        <a href="profile.php">Profile</a>
                        <a href="admin.php" class="active">Admin Dashboard</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <div class="admin-container">
        <div class="admin-header">
            <h1>🛡️ ADMIN DASHBOARD</h1>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert">
                <?php
                $messages = [
                    'approved' => '✅ Karya berhasil diapprove!',
                    'rejected' => '🗑️ Karya berhasil dihapus!',
                    'featured_changed' => '⭐ Status featured berhasil diubah!',
                    'comment_deleted' => '🗑️ Komentar berhasil dihapus!',
                    'comment_approved' => '✅ Komentar berhasil diapprove!'
                ];
                echo $messages[$_GET['msg']] ?? 'Aksi berhasil!';
                ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-box">
                <div class="icon">👥</div>
                <div class="number"><?= $totalUsers ?></div>
                <div class="label">Total Users</div>
            </div>
            <div class="stat-box">
                <div class="icon">🎨</div>
                <div class="number"><?= $totalArt ?></div>
                <div class="label">Total Artworks</div>
            </div>
            <div class="stat-box">
                <div class="icon">⏳</div>
                <div class="number"><?= $pendingArt ?></div>
                <div class="label">Pending Approval</div>
            </div>
            <div class="stat-box">
                <div class="icon">💬</div>
                <div class="number"><?= $totalComments ?></div>
                <div class="label">Total Comments</div>
            </div>
        </div>

        <!-- Pending Approval -->
        <div class="section">
            <h2>⏳ Pending Approval (<?= count($pendingList) ?>)</h2>
            <?php if (count($pendingList) == 0): ?>
                <div class="no-data">Tidak ada karya yang menunggu approval</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Judul</th>
                        <th>Artist</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pendingList as $p): ?>
                <tr>
                    <td>
                        <?php if (!empty($p['image_path']) && file_exists($p['image_path'])): ?>
                            <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="Preview" class="artwork-preview">
                        <?php else: ?>
                            <div style="width:80px;height:80px;background:#ddd;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#999;">No Image</div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($p['judul']) ?></strong></td>
                    <td><?= htmlspecialchars($p['username'] ?? 'Unknown') ?></td>
                    <td><?= date('d M Y', strtotime($p['created_at'] ?? 'now')) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="art_id" value="<?= $p['id'] ?>">
                            <button name="approve_art" class="btn btn-approve">✅ Approve</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="art_id" value="<?= $p['id'] ?>">
                            <button name="reject_art" class="btn btn-reject" 
                                    onclick="return confirm('Yakin ingin menolak karya ini?')">❌ Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Semua Karya -->
        <div class="section">
            <h2>🎨 Semua Karya (<?= count($allArt) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Judul</th>
                        <th>Artist</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($allArt) == 0): ?>
                    <tr>
                        <td colspan="6" class="no-data">Tidak ada karya seni</td>
                    </tr>
                <?php else: ?>
                <?php foreach ($allArt as $a): ?>
                <tr>
                    <td><?= $a['id'] ?></td>
                    <td><strong><?= htmlspecialchars($a['judul']) ?></strong></td>
                    <td><?= htmlspecialchars($a['username'] ?? 'Unknown') ?></td>
                    <td>
                        <span class="badge badge-<?= $a['status'] ?>">
                            <?= ucfirst($a['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?= ($a['featured'] ?? 0) == 1 ? '<span class="badge badge-featured">⭐ Featured</span>' : '-' ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="art_id" value="<?= $a['id'] ?>">
                            <button name="toggle_featured" class="btn btn-featured">
                                <?= ($a['featured'] ?? 0) == 1 ? '★ Unfeature' : '☆ Feature' ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="art_id" value="<?= $a['id'] ?>">
                            <button name="reject_art" class="btn btn-delete" 
                                    onclick="return confirm('Yakin ingin menghapus karya ini?')">
                                🗑️ Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Semua Komentar -->
        <div class="section">
            <h2>💬 Semua Komentar (<?= count($allComments) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Karya</th>
                        <th>Komentar</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($allComments) == 0): ?>
                    <tr>
                        <td colspan="6" class="no-data">Tidak ada komentar</td>
                    </tr>
                <?php else: ?>
                <?php foreach ($allComments as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= htmlspecialchars($c['username'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($c['artwork_title'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars(substr($c['comment'], 0, 50)) ?><?= strlen($c['comment']) > 50 ? '...' : '' ?></td>
                    <td>
                        <span class="badge badge-<?= $c['status'] ?>">
                            <?= ucfirst($c['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($c['status'] !== 'approved'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                            <button name="approve_comment" class="btn btn-approve">✅ Approve</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                            <button name="delete_comment" class="btn btn-delete" 
                                    onclick="return confirm('Yakin ingin menghapus komentar ini?')">
                                🗑️ Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Semua User -->
        <div class="section">
            <h2>👥 Semua User (<?= count($allUsers) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tanggal Daftar</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($allUsers) == 0): ?>
                    <tr>
                        <td colspan="5" class="no-data">Tidak ada user</td>
                    </tr>
                <?php else: ?>
                <?php foreach ($allUsers as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['nama']) ?></strong></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="badge <?= $u['role'] === 'admin' ? 'badge-featured' : 'badge-approved' ?>">
                            <?= $u['role'] === 'admin' ? '🔐 Admin' : ($u['role'] === 'seniman' ? '🎨 Seniman' : '👤 Pengunjung') ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($u['tanggal_daftar'] ?? 'now')) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Dropdown functionality
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
            }
        });
    </script>
</body>
</html>