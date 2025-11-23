<?php
// dashboard.php - Role-based Dashboard dengan proteksi back button
require_once 'config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Pencatatan Berkas Dosir</title>

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
            display: flex;
            align-items: center;
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

        .welcome-card {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .welcome-card h2 {
            font-size: 2em;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            font-size: 1.1em;
            color: #555;
            line-height: 1.6;
        }
        
        .welcome-card a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .welcome-card a:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .workflow-section {
            margin-top: 40px;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .workflow-section h2 {
            font-size: 1.8em;
            color: #2c3e50;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        .workflow-steps {
            display: flex;
            justify-content: space-around;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .workflow-step {
            flex: 1;
            min-width: 200px;
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .workflow-step:hover {
            transform: translateY(-5px);
        }
        
        .step-number {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background-color: #3498db;
            color: white;
            border-radius: 50%;
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 15px;
        }
        
        .workflow-step h4 {
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .workflow-step p {
            font-size: 0.9em;
            color: #777;
        }
        
        .stats-cards {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .stat-card {
            flex: 1;
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            min-width: 200px;
        }
        
        .stat-card h3 {
            font-size: 1.5em;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .stat-card p {
            font-size: 1em;
            color: #777;
        }
        
        /* Gaya untuk Modal (Popup) */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6); /* Lebih gelap */
            padding-top: 50px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 450px; /* Lebih lebar untuk pop-up notif */
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            animation: slideInTop 0.4s;
            position: relative;
        }
        
        /* Efek animasi modal */
        @keyframes slideInTop {
            from {top: -300px; opacity: 0;}
            to {top: 0; opacity: 1;}
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .modal-header h2 {
            font-size: 1.5em;
            color: #34495e;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .modal-header.success h2 {
            color: #27ae60;
        }
        
        .modal-header.error h2 {
            color: #e74c3c;
        }

        .close-btn {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            line-height: 1;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-body p {
            font-size: 1em;
            line-height: 1.6;
            color: #555;
        }
        
        .modal-icon {
            margin-right: 10px;
            font-size: 24px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-primary-success {
            background-color: #2ecc71;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-primary-success:hover {
            background-color: #27ae60;
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
        <a href="#" onclick="showLogoutModal(); return false;">Logout</a>
    </div>
</div>
    <div class="main-content">
        <div class="page-header">
            <h1>Selamat Datang di Sistem Pencatatan Berkas Dosir</h1>
        </div>
        
        <?php if (isset($_SESSION['access_error'])): ?>
            <div class="message error">
                <span class="modal-icon">&#9888;</span> 
                <?php echo htmlspecialchars($_SESSION['access_error']); ?>
                <?php unset($_SESSION['access_error']); ?>
            </div>
        <?php endif; ?>
        
        <?php 
        $login_success_message = '';
        if (isset($_SESSION['login_success'])) {
            $login_success_message = htmlspecialchars($_SESSION['login_success']);
            unset($_SESSION['login_success']); 
        }
        ?>
        
        <?php if (!isAdmin()): ?>
            <div class="welcome-card">
                <h2>Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h2>
                <p>
                    Anda dapat menggunakan sistem ini untuk melacak dan mengelola peminjaman berkas dosir. Gunakan menu di sisi kiri untuk navigasi.
                </p>
                <a href="check_dosir.php">Mulai Cari Berkas</a>
            </div>
            
            <div class="workflow-section">
                <h2>Cara Menggunakan Sistem</h2>
                <div class="workflow-steps">
                    <div class="workflow-step">
                        <div class="step-number">1</div>
                        <h4>Cari Berkas</h4>
                        <p>Klik menu "Cari & Pinjam Berkas" dan masukkan nomor dosir yang ingin dipinjam.</p>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">2</div>
                        <h4>Periksa Ketersediaan</h4>
                        <p>Sistem akan menampilkan informasi berkas dan ketersediaannya beserta lokasi penyimpanan.</p>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">3</div>
                        <h4>Isi Form Peminjaman</h4>
                        <p>Jika berkas tersedia, isi form peminjaman dengan nama peminjam dan tanggal peminjaman.</p>
                    </div>
                    <div class="workflow-step">
                        <div class="step-number">4</div>
                        <h4>Pantau Status</h4>
                        <p>Lihat status peminjaman di menu "Riwayat Peminjaman" dan tunggu proses pengembalian oleh admin.</p>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
        
            <?php
                // Ambil data untuk Admin
                try {
                    $stmt_total_berkas = $pdo->query("SELECT COUNT(*) FROM berkas_inventory");
                    $total_berkas = $stmt_total_berkas->fetchColumn();
                    
                    $stmt_tersedia = $pdo->query("SELECT COUNT(*) FROM berkas_inventory WHERE status = 'tersedia'");
                    $total_tersedia = $stmt_tersedia->fetchColumn();
                    
                    $stmt_dipinjam = $pdo->query("SELECT COUNT(*) FROM berkas_inventory WHERE status = 'dipinjam'");
                    $total_dipinjam = $stmt_dipinjam->fetchColumn();
                    
                    $stmt_loans = $pdo->query("SELECT COUNT(*) FROM pinjaman_dosir WHERE status = 'dipinjam'");
                    $active_loans = $stmt_loans->fetchColumn();
                } catch (PDOException $e) {
                    // Handle error database jika diperlukan
                    $total_berkas = $total_tersedia = $total_dipinjam = $active_loans = 'N/A';
                    error_log("Database Error on Dashboard: " . $e->getMessage());
                }
            ?>
            <div class="stats-cards">
                <div class="stat-card">
                    <h3><?php echo $total_berkas; ?></h3>
                    <p>Total Berkas</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $total_tersedia; ?></h3>
                    <p>Berkas Tersedia</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $total_dipinjam; ?></h3>
                    <p>Berkas Dipinjam</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $active_loans; ?></h3>
                    <p>Peminjaman Aktif</p>
                </div>
            </div>
            <div class="welcome-card" style="margin-top: 20px;">
                <h2>Selamat Datang, Admin!</h2>
                <p>
                    Gunakan menu "Kelola Inventaris" untuk menambah, mengedit, atau menghapus data berkas. Cek "Riwayat Peminjaman" untuk mengelola berkas yang dipinjam.
                </p>
            </div>
        <?php endif; ?>
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
    
    <div id="successModal" class="modal">
        <div class="modal-content">
            <div class="modal-header success">
                <h2 style="font-size: 1.8em;"><span class="modal-icon">&#9989;</span> Berhasil Login!</h2>
                <span class="close-btn" onclick="closeSuccessModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p id="success-message-content">Pesan sukses akan muncul di sini.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary-success" onclick="closeSuccessModal()">Lanjutkan</button>
            </div>
        </div>
    </div>
    <script>
        // Data dari PHP untuk JavaScript
        const loginSuccessMessage = '<?php echo $login_success_message; ?>';

        // Fungsi untuk menampilkan Modal Logout
        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'block';
        }

        // Fungsi untuk menutup Modal Logout
        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        // Fungsi untuk menutup Modal Sukses
        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        // Tampilkan Modal Sukses jika ada pesan
        if (loginSuccessMessage) {
            document.getElementById('success-message-content').textContent = loginSuccessMessage;
            document.getElementById('successModal').style.display = 'block';
        }
        
        // Menutup modal jika user klik di luar area modal
        window.onclick = function(event) {
            const logoutModal = document.getElementById('logoutModal');
            const successModal = document.getElementById('successModal');
            if (event.target === logoutModal) {
                logoutModal.style.display = "none";
            }
            if (event.target === successModal) {
                successModal.style.display = "none";
            }
        }
        
        // Mencegah halaman di-cache dan back button protection (DIPERTAHANKAN)
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        window.addEventListener('load', function() {
            window.history.pushState(null, null, window.location.href);
            window.addEventListener('popstate', function(event) {
                window.history.pushState(null, null, window.location.href);
            });
        });
        
        // Session check (DIPERTAHANKAN)
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