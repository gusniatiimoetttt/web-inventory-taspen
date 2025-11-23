<?php
require_once 'config.php';
requireLogin();

// Inisialisasi variabel untuk menghindari warning
$access_error = '';

$message = '';
$form_data = null;
$show_form = false;
$search_query = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_no_dosir'])) {
    $search_query = $_POST['search_no_dosir'];
    
    // Search in berkas_inventory table
    $stmt = $pdo->prepare("SELECT * FROM berkas_inventory WHERE no_dosir = ? AND status = 'tersedia' LIMIT 1");
    $stmt->execute([$search_query]);
    $dosir_exist = $stmt->fetch();
    
    if ($dosir_exist) {
        $show_form = true;
        $message = "Berkas dosir dengan nomor '$search_query' ditemukan dan tersedia untuk dipinjam.";
        $message .= "<br><strong>Lokasi:</strong> Rak {$dosir_exist['no_rak']}, Box {$dosir_exist['no_box']}";
        $message .= "<br><strong>Nama Peserta:</strong> {$dosir_exist['nama_peserta']}";
        $form_data = $dosir_exist;
    } else {
        // Check if dosir exists but is borrowed
        $stmt = $pdo->prepare("SELECT * FROM berkas_inventory WHERE no_dosir = ? LIMIT 1");
        $stmt->execute([$search_query]);
        $dosir_borrowed = $stmt->fetch();
        
        if ($dosir_borrowed && $dosir_borrowed['status'] == 'dipinjam') {
            $message = "Berkas dosir dengan nomor '$search_query' ditemukan tetapi sedang dipinjam.";
            $message .= "<br><strong>Lokasi:</strong> Rak {$dosir_borrowed['no_rak']}, Box {$dosir_borrowed['no_box']}";
            $message .= "<br><strong>Nama Peserta:</strong> {$dosir_borrowed['nama_peserta']}";
        } else {
            $message = "Berkas dosir dengan nomor '$search_query' tidak ditemukan dalam sistem.";
        }
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pinjaman'])) {
    $berkas_id = $_POST['berkas_id'] ?? '';
    $no_dosir = $_POST['no_dosir'] ?? '';
    
    // PERUBAHAN: Ambil nama peminjam otomatis dari sesi login
    $nama_peminjam = $_SESSION['nama'] ?? '';
    
    // Tanggal peminjaman akan selalu sesuai tanggal saat ini
    $tanggal_peminjaman = date('Y-m-d');

    // PERUBAHAN: Validasi tetap memeriksa nama peminjam, yang kini dari sesi
    if (!empty($berkas_id) && !empty($no_dosir) && !empty($nama_peminjam)) {
        try {
            $pdo->beginTransaction();
            
            // Get berkas info
            $stmt = $pdo->prepare("SELECT * FROM berkas_inventory WHERE id = ? AND status = 'tersedia'");
            $stmt->execute([$berkas_id]);
            $berkas_info = $stmt->fetch();
            
            if (!$berkas_info) {
                throw new Exception('Berkas sudah tidak tersedia atau tidak ditemukan.');
            }
            
            // Update berkas status to 'dipinjam'
            $stmt = $pdo->prepare("UPDATE berkas_inventory SET status = 'dipinjam' WHERE id = ?");
            $stmt->execute([$berkas_id]);
            
            // Insert into pinjaman_dosir
            // Kolom 'jenis_berkas' tidak disertakan di sini (sesuai kode lama)
            $stmt = $pdo->prepare("INSERT INTO pinjaman_dosir 
                                  (berkas_id, nama_peserta, no_dosir, nama_peminjam, tanggal_peminjaman, created_by) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $berkas_id, 
                $berkas_info['nama_peserta'], 
                $no_dosir, 
                $nama_peminjam, // Menggunakan nama dari sesi
                $tanggal_peminjaman, 
                $_SESSION['user_id']
            ]);
            
            $pdo->commit();
            $message = "Peminjaman berkas berhasil dicatat! Anda bisa melihatnya di <a href='data_pinjaman.php'>halaman data pinjaman</a>.";
            $show_form = false;
            $form_data = null;
            
        } catch(Exception $e) {
            $pdo->rollback();
            $message = "Terjadi kesalahan saat menyimpan data: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $message = "Data peminjaman tidak lengkap. Pastikan Anda sudah login dengan benar.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari & Pinjam Berkas Dosir</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sidebar {
            width: 250px;
            background-color: #34495e;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100%;
            overflow: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.5em;
        }
        
        .profile {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #fff;
            margin-bottom: 10px;
            object-fit: cover; 
        }
        
        .profile h4 {
            margin: 0;
            font-size: 1.1em;
        }

        .profile p {
            margin: 5px 0 0;
            font-size: 0.9em;
            color: #bdc3c7;
        }

        .sidebar ul {
            list-style: none;
            width: 100%;
            flex-grow: 1;
        }
        
        .sidebar ul li {
            margin: 10px 0;
        }
        
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            display: block;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #2c3e50;
            transform: translateX(5px);
        }

        .sidebar-logout {
            margin-top: auto;
            width: 100%;
        }

        .sidebar-logout a {
            background-color: #e74c3c;
            text-align: center;
        }

        .sidebar-logout a:hover {
            background-color: #c0392b;
        }

        .main-content {
            margin-left: 250px;
            padding: 40px;
            width: calc(100% - 250px);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .main-content h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-left: 5px solid #3498db;
            padding-left: 10px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-container, .result-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .form-container h2, .result-container h2 {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #3498db;
        }
        
        /* Gaya untuk input yang disabled/otomatis */
        .form-group input:disabled {
            background-color: #ecf0f1;
            color: #7f8c8d;
            border: 1px solid #bdc3c7;
            cursor: not-allowed;
            font-style: italic;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            border: none;
            background-color: #3498db;
            color: white;
            font-size: 1.1em;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .submit-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .result-info {
            background-color: #e8f4f8;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #3498db;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #ccc;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #444;
        }
        
    </style>
</head>
<body>
    <div class="sidebar">
    <?php 
    // Get the full path for the profile picture
    $profile_picture_path = 'uploads/profiles/' . htmlspecialchars($_SESSION['profile_picture'] ?? '');
    $profile_picture_src = (isset($_SESSION['profile_picture']) && file_exists($profile_picture_path)) 
                           ? $profile_picture_path 
                           : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['nama']) . '&background=random';
    ?>
    <div class="profile">
        <img src="<?php echo $profile_picture_src; ?>" alt="User Profile">
        <h4><?php echo htmlspecialchars($_SESSION['nama']); ?></h4>
        <p><?php echo htmlspecialchars(strtoupper($_SESSION['role'])); ?></p>
    </div>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <?php if (isAdmin()): ?>
            <li><a href="inventory_input.php">Kelola Inventaris</a></li>
            <li><a href="user_add.php">Tambah Pengguna</a></li>
            <li><a href="user_list.php">Kelola Pengguna</a></li>
        <?php endif; ?>
        <li><a href="check_dosir.php" class="active">Cari & Pinjam Berkas</a></li>
        <li><a href="data_pinjaman.php">Riwayat Peminjaman</a></li>
        <?php if (isAdmin()): ?>
            <li><a href="log_penghapusan.php">Log Penghapusan</a></li>
        <?php endif; ?>
        <li><a href="profile_settings.php">Pengaturan Profil</a></li>
    </ul>
    <div class="sidebar-logout">
        <a href="logout.php" onclick="return confirmLogout();">Logout</a>
    </div>
</div>

    <div class="main-content">
        <div class="page-header">
            <h1>Cari & Pinjam Berkas Dosir</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'berhasil') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="check_dosir.php">
                <h2>Cari Berkas</h2>
                <div class="form-group">
                    <label for="no_dosir">No. Dosir:</label>
                    <input type="text" id="no_dosir" name="search_no_dosir" 
                           placeholder="Masukkan nomor dosir" value="<?php echo htmlspecialchars($search_query); ?>" required autofocus>
                </div>
                <button type="submit" class="submit-btn">🔎 Cari Berkas</button>
            </form>
        </div>
        
        <?php if ($show_form && $form_data): ?>
            <div class="result-container" style="margin-top: 20px;">
                <h2>Informasi Berkas</h2>
                <div class="result-info">
                    <div class="info-item">
                        <span class="info-label">Nama Peserta</span>
                        <span class="info-value"><?php echo htmlspecialchars($form_data['nama_peserta']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">No. Dosir</span>
                        <span class="info-value"><?php echo htmlspecialchars($form_data['no_dosir']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Lokasi</span>
                        <span class="info-value">Rak <?php echo htmlspecialchars($form_data['no_rak']); ?>, Box <?php echo htmlspecialchars($form_data['no_box']); ?></span>
                    </div>
                </div>
                
                <form method="POST" action="check_dosir.php">
                    <input type="hidden" name="berkas_id" value="<?php echo htmlspecialchars($form_data['id']); ?>">
                    <input type="hidden" name="no_dosir" value="<?php echo htmlspecialchars($form_data['no_dosir']); ?>">
                    <input type="hidden" name="submit_pinjaman" value="1">
                    
                    <div class="form-group">
                        <label for="nama_peminjam_display">* Nama Peminjam (Otomatis):</label>
                        <input type="text" id="nama_peminjam_display" 
                               value="<?php echo htmlspecialchars($_SESSION['nama'] ?? 'Nama tidak tersedia'); ?>" 
                               disabled>
                               
                        <input type="hidden" name="nama_peminjam" 
                               value="<?php echo htmlspecialchars($_SESSION['nama'] ?? ''); ?>">
                        
                        <p style="font-size: 0.8em; color: #95a5a6; margin-top: 5px;">Nama diambil otomatis dari akun yang sedang login.</p>
                    </div>
                    
                    <button type="submit" class="submit-btn">✅ Simpan Peminjaman</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function confirmLogout() {
            return confirm('Apakah Anda yakin ingin logout?');
        }
        
        // Mencegah halaman di-cache
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        window.addEventListener('load', function() {
            window.history.pushState(null, null, window.location.href);
            window.addEventListener('popstate', function(event) {
                window.history.pushState(null, null, window.location.href);
            });
        });
        
        // Session check
        function checkSession() {
            fetch('check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.logged_in) {
                        window.location.href = 'index.php';
                    }
                })
                .catch(error => {
                    console.log('Session check failed:', error);
                });
        }
        
        setInterval(checkSession, 30000);
        
        // Disable browser back/forward cache
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>