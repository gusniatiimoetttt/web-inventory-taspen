<?php
// data_pinjaman.php - Updated for new inventory system with logout protection
require_once 'config.php';
requireLogin();

// Set headers untuk mencegah caching halaman
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");

$success = '';
$error = '';
$edit_data = null; 

// Handle return (pengembalian)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_id'])) {
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
    
    $return_id = $_POST['return_id'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT * FROM pinjaman_dosir WHERE id = ? AND status = 'dipinjam'");
        $stmt->execute([$return_id]);
        $loan_data = $stmt->fetch();
        
        if (!$loan_data) {
            throw new Exception('Data peminjaman tidak ditemukan atau sudah dikembalikan.');
        }
        
        $stmt = $pdo->prepare("UPDATE pinjaman_dosir SET status = 'dikembalikan', tanggal_pengembalian = CURDATE() WHERE id = ?");
        $stmt->execute([$return_id]);
        
        $stmt = $pdo->prepare("UPDATE berkas_inventory SET status = 'tersedia' WHERE id = ?");
        $stmt->execute([$loan_data['berkas_id']]);
        
        $pdo->commit();
        $success = "Berkas '{$loan_data['nama_peserta']}' berhasil dikembalikan!";
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = 'Gagal mengembalikan berkas: ' . $e->getMessage();
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_pinjaman'])) {
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }

    $edit_id = $_POST['edit_id'];
    $new_nama_peminjam = $_POST['new_nama_peminjam'] ?? '';
    $new_tanggal_peminjaman = $_POST['new_tanggal_peminjaman'] ?? '';
    $new_tanggal_pengembalian = $_POST['new_tanggal_pengembalian'] ?? null;

    if (empty($new_nama_peminjam) || empty($new_tanggal_peminjaman)) {
        $error = 'Nama peminjam dan tanggal peminjaman tidak boleh kosong!';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE pinjaman_dosir SET nama_peminjam = ?, tanggal_peminjaman = ?, tanggal_pengembalian = ? WHERE id = ?");
            $stmt->execute([$new_nama_peminjam, $new_tanggal_peminjaman, $new_tanggal_pengembalian, $edit_id]);
            $success = 'Data peminjaman berhasil diperbarui!';
        } catch(Exception $e) {
            $error = 'Gagal memperbarui data: ' . $e->getMessage();
        }
    }
}

// Handle permanent delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }

    $delete_id = $_POST['delete_id'];
    $alasan = $_POST['alasan'] ?? 'Tidak ada alasan';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM pinjaman_dosir WHERE id = ?");
        $stmt->execute([$delete_id]);
        $loan_data = $stmt->fetch();

        if ($loan_data) {
            $berkas_id = $loan_data['berkas_id'];
            
            // Log the deletion action
            $stmt_log = $pdo->prepare("INSERT INTO log_penghapusan (deleted_by, berkas_id, alasan, created_at) VALUES (?, ?, ?, NOW())");
            $stmt_log->execute([$_SESSION['user_id'], $berkas_id, $alasan]);

            // Permanently delete from pinjaman_dosir table
            $stmt = $pdo->prepare("DELETE FROM pinjaman_dosir WHERE id = ?");
            $stmt->execute([$delete_id]);
            
            // Mark berkas as available in inventory if it's no longer in any active loans
            $stmt_check_loan = $pdo->prepare("SELECT COUNT(*) FROM pinjaman_dosir WHERE berkas_id = ? AND status = 'dipinjam'");
            $stmt_check_loan->execute([$berkas_id]);
            $count_loans = $stmt_check_loan->fetchColumn();
            
            if ($count_loans == 0) {
                 $stmt = $pdo->prepare("UPDATE berkas_inventory SET status = 'tersedia' WHERE id = ?");
                 $stmt->execute([$berkas_id]);
            }

            $pdo->commit();
            $success = 'Data pinjaman berhasil dihapus permanen dan dicatat dalam log!';
        } else {
            throw new Exception('Data pinjaman tidak ditemukan.');
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Terjadi kesalahan saat menghapus: ' . $e->getMessage();
    }
}


// Search functionality
$search = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$filter_berkas = $_GET['filter_berkas'] ?? '';

$sql = "SELECT p.*, b.nama_peserta, b.no_dosir, b.no_rak, b.no_box
        FROM pinjaman_dosir p
        LEFT JOIN berkas_inventory b ON p.berkas_id = b.id
        WHERE 1=1";
$params = [];

if (!isAdmin()) {
    $sql .= " AND p.created_by = ?";
    $params[] = $_SESSION['user_id'];
}

if ($search) {
    $sql .= " AND (p.nama_peminjam LIKE ? OR b.no_dosir LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_status) {
    $sql .= " AND p.status = ?";
    $params[] = $filter_status;
}

if ($filter_berkas) {
    $sql .= " AND p.berkas_id = ?";
    $params[] = $filter_berkas;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$loan_data = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Sistem Pencatatan Berkas Dosir</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
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
        object-fit: cover; /* Tambahkan baris ini */
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

        .main-content h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-left: 5px solid #3498db;
            padding-left: 10px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-header .action-buttons a {
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .page-header .action-buttons .btn-add {
            background-color: #2ecc71;
        }
        
        .page-header .action-buttons .btn-add:hover {
            background-color: #27ae60;
        }
        
        .search-filter-container {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
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

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
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
        
        .btn-success {
            background-color: #27ae60;
        }
        
        .btn-success:hover {
            background-color: #229954;
        }
        
        .btn-danger {
            background-color: #e74c3c;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .btn-warning {
            background-color: #f39c12;
        }
        
        .btn-warning:hover {
            background-color: #d35400;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 0.8em;
            color: white;
        }

        .status-badge.dipinjam {
            background-color: #f39c12;
        }

        .status-badge.dikembalikan {
            background-color: #2ecc71;
        }

        .status-badge.dihapus {
            background-color: #e74c3c;
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

        /* Modal styling */
        .modal {
            display: none; 
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.4); 
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px 0;
        }
        
        .modal-body p {
            margin-bottom: 15px;
            font-size: 1.1em;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group textarea, .form-group input[type="text"], .form-group input[type="date"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            resize: vertical;
        }

        .modal-footer {
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: right;
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
</div>

    <div class="main-content">
        <div class="page-header">
            <h1>Riwayat Peminjaman Berkas</h1>
        </div>

        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="search-filter-container">
            <form method="GET" style="display: flex; gap: 15px; flex: 1;">
                <input type="text" name="search" placeholder="Cari No. Dosir atau Nama Berkas..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="filter_status">
                    <option value="">Semua Status</option>
                    <option value="dipinjam" <?php echo $filter_status == 'dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                    <option value="dikembalikan" <?php echo $filter_status == 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                    <option value="dihapus" <?php echo $filter_status == 'dihapus' ? 'selected' : ''; ?>>Dihapus</option>
                </select>
                <button type="submit">Cari</button>
            </form>
        </div>

        <div class="table-container">
            <?php if ($loan_data): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>No. Dosir</th>
                            <th>Nama Peserta</th>
                            <th>Nama Peminjam</th>
                            <th>Tanggal Pinjam</th>
                            <th>Tanggal Pengembalian</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loan_data as $i => $loan): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($loan['no_dosir']); ?></td>
                                <td><?php echo htmlspecialchars($loan['nama_peserta']); ?></td>
                                <td><?php echo htmlspecialchars($loan['nama_peminjam'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($loan['tanggal_peminjaman']); ?></td>
                                <td><?php echo htmlspecialchars($loan['tanggal_pengembalian'] ?? 'Belum Dikembalikan'); ?></td>
                                <td><span class="status-badge <?php echo strtolower(htmlspecialchars($loan['status'])); ?>"><?php echo htmlspecialchars($loan['status']); ?></span></td>
                                <td class="table-actions">
                                    <?php if (isAdmin()): ?>
                                        <?php if ($loan['status'] == 'dipinjam'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin mengembalikan berkas ini?');">
                                                <input type="hidden" name="return_id" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                                <button type="submit" class="btn btn-success btn-sm">Kembalikan</button>
                                            </form>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-warning btn-sm"
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($loan['id'])); ?>, <?php echo htmlspecialchars(json_encode($loan['nama_peminjam'])); ?>, <?php echo htmlspecialchars(json_encode($loan['tanggal_peminjaman'])); ?>, <?php echo htmlspecialchars(json_encode($loan['tanggal_pengembalian'])); ?>)">
                                            Edit
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="confirmPermanentDelete(<?php echo htmlspecialchars($loan['id']); ?>, '<?php echo htmlspecialchars($loan['nama_peserta']); ?>')">
                                            Hapus Permanen
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="message error">Tidak ada data peminjaman yang ditemukan.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Konfirmasi Hapus Permanen</h2>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">Apakah Anda yakin ingin menghapus data ini secara permanen? Data tidak dapat dikembalikan.</p>
                <form id="deleteForm" method="POST" action="">
                    <input type="hidden" name="delete_id" id="deleteId">
                    <div class="form-group">
                        <label for="alasan">Alasan Penghapusan (Opsional):</label>
                        <textarea id="alasan" name="alasan"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus Permanen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Data Peminjaman</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editForm" method="POST" action="">
                    <input type="hidden" name="edit_id" id="editId">
                    <input type="hidden" name="edit_pinjaman" value="1">
                    <div class="form-group">
                        <label for="new_nama_peminjam">Nama Peminjam Baru:</label>
                        <input type="text" id="new_nama_peminjam" name="new_nama_peminjam" required>
                    </div>
                    <div class="form-group">
                        <label for="new_tanggal_peminjaman">Tanggal Peminjaman Baru:</label>
                        <input type="date" id="new_tanggal_peminjaman" name="new_tanggal_peminjaman" required>
                    </div>
                    <div class="form-group">
                        <label for="new_tanggal_pengembalian">Tanggal Pengembalian Baru:</label>
                        <input type="date" id="new_tanggal_pengembalian" name="new_tanggal_pengembalian">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
                        <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
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
        
        // Disable browser back/forward cache
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
        
        function confirmLogout() {
            return confirm('Apakah Anda yakin ingin logout?');
        }
        
        function confirmPermanentDelete(id, nama) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus PERMANEN data "${nama}"? Data akan benar-benar dihapus dari database dan tidak dapat dikembalikan.`;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function openEditModal(id, namaPeminjam, tglPeminjaman, tglPengembalian) {
            document.getElementById('editId').value = id;
            document.getElementById('new_nama_peminjam').value = namaPeminjam;
            document.getElementById('new_tanggal_peminjaman').value = tglPeminjaman;
            document.getElementById('new_tanggal_pengembalian').value = tglPengembalian;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const deleteModal = document.getElementById('deleteModal');
            const editModal = document.getElementById('editModal');
            if (event.target == deleteModal) {
                closeModal('deleteModal');
            }
            if (event.target == editModal) {
                closeModal('editModal');
            }
        }
    </script>
</body>
</html>