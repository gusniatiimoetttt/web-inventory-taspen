<?php
// inventory_input.php - Halaman tunggal untuk Manajemen Inventory (Add, Edit, Delete, List)
require_once 'config.php';
requireAdmin();

// Set headers untuk mencegah caching halaman
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");

$success = '';
$error = '';
$edit_data = null;
$search_query = $_GET['search'] ?? '';

// --- LOGIKA PHP TERGABUNG ---

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
                $stmt = $pdo->prepare("INSERT INTO berkas_inventory (no_dosir, nama_peserta, no_rak, no_box, status) VALUES (?, ?, ?, ?, 'tersedia')");
                $stmt->execute([$no_dosir, $nama_peserta, $no_rak, $no_box]);
                $success = 'Berkas baru berhasil ditambahkan!';
            }
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $pdo->beginTransaction();

        // 1. Dapatkan informasi berkas sebelum dihapus
        $stmt = $pdo->prepare("SELECT * FROM berkas_inventory WHERE id = ?");
        $stmt->execute([$delete_id]);
        $berkas_data = $stmt->fetch();

        if (!$berkas_data) {
            $error = 'Data berkas tidak ditemukan!';
        } else {
            // Check if berkas is being borrowed
            if ($berkas_data['status'] == 'dipinjam') {
                $error = 'Tidak dapat menghapus berkas yang sedang dipinjam!';
            } else {
                // 2. Catat ke log_penghapusan
                $stmt = $pdo->prepare("INSERT INTO log_penghapusan (berkas_id, no_dosir, nama_peserta, no_rak, no_box, deleted_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $berkas_data['id'],
                    $berkas_data['no_dosir'],
                    $berkas_data['nama_peserta'],
                    $berkas_data['no_rak'],
                    $berkas_data['no_box'],
                    $_SESSION['user_id']
                ]);

                // 3. Hapus berkas dari tabel inventory
                $stmt = $pdo->prepare("DELETE FROM berkas_inventory WHERE id = ?");
                $stmt->execute([$delete_id]);
                
                $success = 'Data berkas berhasil dihapus dan dicatat ke log!';
            }
        }
        $pdo->commit();

    } catch(PDOException $e) {
        $pdo->rollBack();
        $error = 'Terjadi kesalahan saat menghapus: ' . $e->getMessage();
    }
}

// Fetch data for editing
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM berkas_inventory WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
    if (!$edit_data) {
        $error = "Data tidak ditemukan.";
    }
}

// Search and filter logic
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

// Fetch unique Rak and Box numbers for filters
$rak_list = $pdo->query("SELECT DISTINCT no_rak FROM berkas_inventory ORDER BY no_rak")->fetchAll(PDO::FETCH_COLUMN);
$box_list = $pdo->query("SELECT DISTINCT no_box FROM berkas_inventory ORDER BY no_box")->fetchAll(PDO::FETCH_COLUMN);
$status_list = ['tersedia', 'dipinjam'];

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Inventaris - Sistem Pencatatan Berkas Dosir</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        /* Gaya CSS yang digabungkan dan disederhanakan */
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

        .form-container {
            margin-bottom: 30px;
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

        .form-group input[type="text"], .search-controls input, .search-controls select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        .search-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            align-items: flex-end; /* Align items to the bottom */
        }
        
        .search-controls > div {
            flex: 1;
            min-width: 150px;
        }

        .search-controls input:focus, .search-controls select:focus {
            outline: none;
            border-color: #3498db;
        }

        .submit-btn, .search-controls button {
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
        
        .submit-btn:hover, .search-controls button:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .search-controls button {
            background-color: #3498db;
        }
        .search-controls button:hover {
            background-color: #2980b9;
        }

        .search-controls .btn-add {
            background-color: #28a745;
            text-align: center;
            text-decoration: none;
            color: white;
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #f2f2f2;
            color: #555;
            font-weight: bold;
        }

        table tr:hover {
            background-color: #f9f9f9;
        }
        
        .table-actions {
            white-space: nowrap;
        }
        
        .btn {
            padding: 8px 12px;
            text-decoration: none;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 0.9em;
        }
        
        .btn-primary {
            background-color: #3498db;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-danger {
            background-color: #e74c3c;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-warning {
            background-color: #ffc107;
            color: black;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        
        .status-tersedia {
            background-color: #28a745;
        }
        
        .status-dipinjam {
            background-color: #dc3545;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #777;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .search-controls {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['nama']); ?>&background=random" alt="User Profile">
            <h4><?php echo htmlspecialchars($_SESSION['nama']); ?></h4>
            <p><?php echo htmlspecialchars(strtoupper($_SESSION['role'])); ?></p>
        </div>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <?php if (isAdmin()): ?>
                <li><a href="inventory_input.php" class="active">Kelola Inventaris</a></li>
            <?php endif; ?>
            <li><a href="check_dosir.php">Cari & Pinjam Berkas</a></li>
            <li><a href="data_pinjaman.php">Riwayat Peminjaman</a></li>
            <?php if (isAdmin()): ?>
                <li><a href="log_penghapusan.php">Log Penghapusan</a></li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-logout">
            <a href="logout.php" onclick="return confirmLogout();">Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <h1>Kelola Inventaris Berkas</h1>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?php echo $edit_data ? 'Edit Berkas' : 'Tambah Berkas Baru'; ?></h2>
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

        <hr>

        <div class="table-container">
            <h2>Daftar Berkas</h2>
            <form method="GET" action="inventory_input.php" class="search-controls">
                <div>
                    <label for="search">Cari</label>
                    <input type="text" id="search" name="search" placeholder="No. Dosir / Nama Peserta" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div>
                    <label for="filter_rak">Filter Rak</label>
                    <select id="filter_rak" name="filter_rak">
                        <option value="">Semua Rak</option>
                        <?php foreach ($rak_list as $rak): ?>
                            <option value="<?php echo htmlspecialchars($rak); ?>" <?php echo $filter_rak == $rak ? 'selected' : ''; ?>>Rak <?php echo htmlspecialchars($rak); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_box">Filter Box</label>
                    <select id="filter_box" name="filter_box">
                        <option value="">Semua Box</option>
                        <?php foreach ($box_list as $box): ?>
                            <option value="<?php echo htmlspecialchars($box); ?>" <?php echo $filter_box == $box ? 'selected' : ''; ?>>Box <?php echo htmlspecialchars($box); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_status">Filter Status</label>
                    <select id="filter_status" name="filter_status">
                        <option value="">Semua Status</option>
                        <?php foreach ($status_list as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" <?php echo $filter_status == $status ? 'selected' : ''; ?>>
                                <?php echo ucfirst($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit">Cari & Filter</button>
                </div>
            </form>
            
            <?php if ($inventory_data): ?>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No. Dosir</th>
                                <th>Nama Peserta</th>
                                <th>Rak</th>
                                <th>Box</th>
                                <th>Status</th>
                                <th>Tanggal Ditambahkan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php foreach ($inventory_data as $item): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($item['no_dosir']); ?></td>
                                    <td><?php echo htmlspecialchars($item['nama_peserta']); ?></td>
                                    <td><?php echo htmlspecialchars($item['no_rak']); ?></td>
                                    <td><?php echo htmlspecialchars($item['no_box']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($item['status']); ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d-m-Y', strtotime($item['created_at'])); ?></td>
                                    <td class="table-actions">
                                        <a href="inventory_input.php?edit_id=<?php echo $item['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <?php if ($item['status'] == 'tersedia'): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus berkas ini?')">
                                                <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <h3>Tidak ada data berkas yang ditemukan</h3>
                    <p>Silakan tambah data berkas baru atau ubah kriteria pencarian</p>
                </div>
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