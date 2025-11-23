<?php
// log_penghapusan.php - Log Penghapusan (Admin Only) with logout protection
require_once 'config.php';
requireAdmin();

// Set headers untuk mencegah caching halaman
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");

// Get log data
$stmt = $pdo->prepare("
    SELECT lp.*, u.nama as deleted_by_name 
    FROM log_penghapusan lp 
    LEFT JOIN users u ON lp.deleted_by = u.id 
    ORDER BY lp.created_at DESC
");
$stmt->execute();
$log_data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Penghapusan - Sistem Pencatatan Berkas Dosir</title>
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
        
        .table-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
            <h1>Log Penghapusan Data</h1>
        </div>
        
        <?php if ($log_data): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Log</th>
                            <th>No. Berkas</th>
                            <th>Dihapus Oleh</th>
                            <th>Alasan</th>
                            <th>Tanggal & Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($log_data as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['id']); ?></td>
                                <td><?php echo htmlspecialchars($log['berkas_id']); ?></td>
                                <td><?php echo htmlspecialchars($log['deleted_by_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($log['alasan']); ?></td>
                                <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="message error">Tidak ada log penghapusan yang ditemukan.</div>
        <?php endif; ?>
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