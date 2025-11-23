<?php
// user_list.php - Halaman untuk menampilkan daftar dan mengelola pengguna (Admin Only)
require_once 'config.php';
requireAdmin();

$success = '';
$error = '';

// Handle permanent delete user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user_id'])) {
    $delete_id = $_POST['delete_user_id'];
    
    // Pastikan admin tidak bisa menghapus akunnya sendiri
    if ($delete_id == $_SESSION['user_id']) {
        $error = "Anda tidak bisa menghapus akun Anda sendiri!";
    } else {
        try {
            // Hapus pengguna dari database
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            $success = "Pengguna berhasil dihapus permanen.";
        } catch (PDOException $e) {
            $error = "Gagal menghapus pengguna: " . $e->getMessage();
        }
    }
}

// Handle edit user form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user_id'])) {
    $edit_id = $_POST['edit_user_id'];
    $new_nama = $_POST['new_nama'] ?? '';
    $new_role = $_POST['new_role'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // Pastikan admin tidak bisa mengedit role-nya sendiri
    if ($edit_id == $_SESSION['user_id'] && $_POST['new_role'] != $_SESSION['role']) {
        $error = "Anda tidak bisa mengubah role akun Anda sendiri!";
    } else {
        try {
            if (!empty($new_password)) {
                // Perubahan: Simpan password tanpa hashing
                $stmt = $pdo->prepare("UPDATE users SET nama = ?, role = ?, password = ? WHERE id = ?");
                $stmt->execute([$new_nama, $new_role, $new_password, $edit_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nama = ?, role = ? WHERE id = ?");
                $stmt->execute([$new_nama, $new_role, $edit_id]);
            }
            $success = "Data pengguna berhasil diperbarui.";
        } catch (PDOException $e) {
            $error = "Gagal memperbarui data pengguna: " . $e->getMessage();
        }
    }
}


// Fetch all users from the database, including password
$stmt = $pdo->prepare("SELECT id, nama, username, password, role, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Sistem Pencatatan Berkas Dosir</title>
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
        
        .btn-warning {
            background-color: #f39c12;
        }
        
        .btn-warning:hover {
            background-color: #d35400;
        }
        
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
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
            <li><a href="user_list.php" class="active">Kelola Pengguna</a></li>
        <?php endif; ?>
        <li><a href="check_dosir.php">Cari & Pinjam Berkas</a></li>
        <li><a href="data_pinjaman.php">Riwayat Peminjaman</a></li>
        <?php if (isAdmin()): ?>
            <li><a href="log_penghapusan.php">Log Penghapusan</a></li>
        <?php endif; ?>
        <li><a href="profile_settings.php">Pengaturan Profil</a></li>
    </ul>
    <div class="sidebar-logout">
        <a href="#" onclick="showLogoutModal(); return false;">Logout</a>
    </div>
</div>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Kelola Pengguna</h1>
        </div>

        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="table-container">
            <?php if ($users): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Hak Akses</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['nama']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['password']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                <td><?php echo date('d-m-Y H:i', strtotime($user['created_at'])); ?></td>
                                <td class="table-actions">
                                    <button type="button" class="btn btn-warning btn-sm"
                                        onclick="openEditModal('<?php echo htmlspecialchars($user['id']); ?>', '<?php echo htmlspecialchars($user['nama']); ?>', '<?php echo htmlspecialchars($user['role']); ?>')">
                                        Edit
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="confirmPermanentDelete(<?php echo htmlspecialchars($user['id']); ?>, '<?php echo htmlspecialchars($user['nama']); ?>')">
                                                Hapus
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="message error">Tidak ada data pengguna yang ditemukan.</div>
            <?php endif; ?>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Pengguna</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editForm" method="POST" action="">
                    <input type="hidden" name="edit_user_id" id="editUserId">
                    <div class="form-group">
                        <label for="new_nama">Nama Lengkap:</label>
                        <input type="text" id="new_nama" name="new_nama" required>
                    </div>
                    <div class="form-group">
                        <label for="new_role">Hak Akses:</label>
                        <select id="new_role" name="new_role" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Password Baru (kosongkan jika tidak ingin diubah):</label>
                        <input type="text" id="new_password" name="new_password">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
                        <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Konfirmasi Hapus Pengguna</h2>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p id="deleteMessage"></p>
                <form id="deleteForm" method="POST" action="">
                    <input type="hidden" name="delete_user_id" id="deleteUserId">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus Permanen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Konfirmasi Logout</h2>
                <span class="close-btn" onclick="closeLogoutModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin keluar dari akun ini?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeLogoutModal()">Batal</button>
                <a href="logout.php" class="btn btn-danger">Keluar</a>
            </div>
        </div>
    </div>
    
    <script>
        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'block';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function confirmPermanentDelete(id, nama) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteMessage').textContent = `Apakah Anda yakin ingin menghapus pengguna "${nama}"? Data akan dihapus permanen dari database.`;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function openEditModal(id, nama, role) {
            document.getElementById('editUserId').value = id;
            document.getElementById('new_nama').value = nama;
            document.getElementById('new_role').value = role;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            const deleteModal = document.getElementById('deleteModal');
            const editModal = document.getElementById('editModal');
            const logoutModal = document.getElementById('logoutModal');
            if (event.target == deleteModal) {
                closeModal('deleteModal');
            }
            if (event.target == editModal) {
                closeModal('editModal');
            }
            if (event.target == logoutModal) {
                closeLogoutModal();
            }
        }
    </script>
</body>
</html>