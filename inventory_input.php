<?php
// inventory_input.php - Halaman tunggal untuk Manajemen Inventory
require_once 'config.php';
requireAdmin();

$success = '';
$error = '';
$edit_data = null;

// Handle delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    
    try {
        // Check if berkas is being borrowed
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pinjaman_dosir WHERE berkas_id = ? AND status = 'dipinjam'");
        $stmt->execute([$delete_id]);
        $is_borrowed = $stmt->fetchColumn();
        
        if ($is_borrowed > 0) {
            $error = 'Tidak dapat menghapus berkas yang sedang dipinjam!';
        } else {
            // Delete berkas
            $stmt = $pdo->prepare("DELETE FROM berkas_inventory WHERE id = ?");
            $stmt->execute([$delete_id]);
            $success = 'Data berkas berhasil dihapus!';
        }
    } catch(PDOException $e) {
        $error = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_berkas'])) {
    $no_dosir = $_POST['no_dosir'] ?? '';
    $nama_peserta = $_POST['nama_peserta'] ?? '';
    $no_rak = $_POST['no_rak'] ?? '';
    $no_box = $_POST['no_box'] ?? '';
    $berkas_id = $_POST['berkas_id'] ?? null;
    
    if (empty($no_dosir) || empty($nama_peserta) || empty($no_rak) || empty($no_box)) {
        $error = 'Semua field wajib diisi!';
    } else {
        try {
            if ($berkas_id) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE berkas_inventory SET no_dosir = ?, nama_peserta = ?, no_rak = ?, no_box = ? WHERE id = ?");
                $stmt->execute([$no_dosir, $nama_peserta, $no_rak, $no_box, $berkas_id]);
                $success = 'Data berkas berhasil diperbarui!';
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO berkas_inventory (no_dosir, nama_peserta, status, no_rak, no_box) VALUES (?, ?, 'tersedia', ?, ?)");
                $stmt->execute([$no_dosir, $nama_peserta, $no_rak, $no_box]);
                $success = 'Data berkas berhasil ditambahkan!';
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry error
                $error = 'No. Dosir sudah ada dalam database!';
            } else {
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}

// Check for edit request
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM berkas_inventory WHERE id = ? LIMIT 1");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
    if (!$edit_data) {
        $error = "Data berkas tidak ditemukan.";
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$filter_rak = $_GET['filter_rak'] ?? '';
$filter_box = $_GET['filter_box'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(no_dosir LIKE ? OR nama_peserta LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_rak)) {
    $where_clauses[] = "no_rak = ?";
    $params[] = $filter_rak;
}

if (!empty($filter_box)) {
    $where_clauses[] = "no_box = ?";
    $params[] = $filter_box;
}

if (!empty($filter_status)) {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
$sql = "SELECT * FROM berkas_inventory " . $where_sql . " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inventory_data = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Berkas Inventory - Sistem Pencatatan Berkas Dosir</title>
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

        .form-container, .table-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .form-container h2 {
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

        .submit-btn {
            width: 100%;
            padding: 12px;
            border: none;
            background-color: #2ecc71;
            color: white;
            font-size: 1.1em;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .submit-btn:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }
        
        .search-filter-container {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-filter-container input,
        .search-filter-container select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .search-filter-container input[type="text"] {
            flex: 1;
        }
        
        .search-filter-container button {
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .search-filter-container button:hover {
            background-color: #2980b9;
        }

        /* --- START Perubahan CSS untuk Tabel Hitam Putih --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border: 1px solid #333; /* Border luar hitam */
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ccc; /* Border dalam abu-abu */
        }

        table th {
            background-color: #747474ff; /* Kepala tabel Hitam */
            color: white; /* Teks Putih */
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background-color: #f2f2f2; /* Baris genap abu-abu sangat muda */
        }

        table tr:hover {
            background-color: #ddd; /* Hover abu-abu terang */
        }

        /* Mengganti warna tombol Aksi menjadi abu-abu/monokrom */
        .btn {
            /* Gaya dasar btn tetap */
            padding: 8px 12px;
            text-decoration: none;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 0.9em;
        }

        .btn-warning {
            background-color: #43ac29ff; /* Edit - Abu-abu sedang */
        }
        
        .btn-warning:hover {
            background-color: #555;
        }
        
        .btn-danger {
            background-color: #e49999ff; /* Hapus - Abu-abu terang */
            color: black; /* Ubah warna teks agar terlihat */
        }
        
        .btn-danger:hover {
            background-color: #777;
            color: white;
        }
        
        /* Mengganti warna Status Badge menjadi hitam putih */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 0.8em;
            border: 1px solid #28c086ff;
        }

        .status-badge.tersedia {
            background-color: #23bb3cff; /* Tersedia - Hitam */
            color: white;
        }
        .status-badge.dipinjam {
            background-color: #ccc; /* Dipinjam - Abu-abu terang */
            color: black;
        }
        /* --- END Perubahan CSS untuk Tabel Hitam Putih --- */

        /* Mengganti warna tombol Reset filter menjadi monokrom juga untuk konsistensi */
        .btn-secondary {
            padding: 10px 15px;
            background-color: #ccc;
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background-color: #aaa;
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
        <li><a href="check_dosir.php">Cari & Pinjam Berkas</a></li>
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
            <h1>Manajemen Berkas Inventaris</h1>
        </div>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?php echo $edit_data ? 'Edit Data Berkas' : 'Tambah Berkas Baru'; ?></h2>
            <form method="POST" action="inventory_input.php">
                <input type="hidden" name="berkas_id" value="<?php echo htmlspecialchars($edit_data['id'] ?? ''); ?>">
                <div class="form-group">
                    <label for="no_dosir">No. Dosir:</label>
                    <input type="text" id="no_dosir" name="no_dosir" placeholder="Masukkan nomor dosir" 
                            value="<?php echo htmlspecialchars($edit_data['no_dosir'] ?? ''); ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label for="nama_peserta">Nama Peserta:</label>
                    <input type="text" id="nama_peserta" name="nama_peserta" placeholder="Masukkan nama peserta" 
                            value="<?php echo htmlspecialchars($edit_data['nama_peserta'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="no_rak">Nomor Rak:</label>
                    <input type="text" id="no_rak" name="no_rak" placeholder="Masukkan nomor rak" 
                            value="<?php echo htmlspecialchars($edit_data['no_rak'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="no_box">Nomor Box:</label>
                    <input type="text" id="no_box" name="no_box" placeholder="Masukkan nomor box" 
                            value="<?php echo htmlspecialchars($edit_data['no_box'] ?? ''); ?>" required>
                </div>
                <button type="submit" name="submit_berkas" class="submit-btn">
                    <?php echo $edit_data ? 'Simpan Perubahan' : 'Tambah Berkas'; ?>
                </button>
            </form>
        </div>

        <div class="table-container" style="margin-top: 20px;">
            <h2>Daftar Berkas</h2>
            <div class="search-filter-container">
                <form method="GET" action="inventory_input.php" style="display: flex; gap: 15px; flex: 1;">
                    <input type="text" name="search" placeholder="Cari No. Dosir atau Nama Peserta..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="filter_rak">
                        <option value="">Semua Rak</option>
                        <?php
                            $stmt_rak = $pdo->query("SELECT DISTINCT no_rak FROM berkas_inventory ORDER BY no_rak");
                            while ($rak = $stmt_rak->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <option value="<?php echo htmlspecialchars($rak['no_rak']); ?>" <?php echo $filter_rak == $rak['no_rak'] ? 'selected' : ''; ?>>
                                Rak <?php echo htmlspecialchars($rak['no_rak']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <select name="filter_box">
                        <option value="">Semua Box</option>
                        <?php
                            $stmt_box = $pdo->query("SELECT DISTINCT no_box FROM berkas_inventory ORDER BY no_box");
                            while ($box = $stmt_box->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <option value="<?php echo htmlspecialchars($box['no_box']); ?>" <?php echo $filter_box == $box['no_box'] ? 'selected' : ''; ?>>
                                Box <?php echo htmlspecialchars($box['no_box']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <select name="filter_status">
                        <option value="">Semua Status</option>
                        <option value="tersedia" <?php echo $filter_status == 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                        <option value="dipinjam" <?php echo $filter_status == 'dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                    </select>
                    <button type="submit">Cari</button>
                    <?php if ($search || $filter_rak || $filter_box || $filter_status): ?>
                        <a href="inventory_input.php" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if ($inventory_data): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>No. Dosir</th>
                            <th>Nama Peserta</th>
                            <th>No. Rak</th>
                            <th>No. Box</th>
                            <th>Status</th>
                            <th>Terakhir Diperbarui</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory_data as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['id']); ?></td>
                                <td><?php echo htmlspecialchars($item['no_dosir']); ?></td>
                                <td><?php echo htmlspecialchars($item['nama_peserta']); ?></td>
                                <td><?php echo htmlspecialchars($item['no_rak']); ?></td>
                                <td><?php echo htmlspecialchars($item['no_box']); ?></td>
                                <td><span class="status-badge <?php echo strtolower($item['status']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                                <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($item['updated_at'] ?? $item['created_at']))); ?></td>
                                <td class="table-actions">
                                    <a href="inventory_input.php?edit=<?php echo htmlspecialchars($item['id']); ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <?php if ($item['status'] == 'tersedia'): ?>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas ini?')">
                                            <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="message error">Tidak ada data berkas yang ditemukan.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function confirmLogout() {
            return confirm('Apakah Anda yakin ingin logout?');
        }
        
        // Mencegah halaman di-cache dan back button protection
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
    </script>
</body>
</html>