<?php
// user_add.php - Halaman untuk menambah pengguna baru (Admin Only)
require_once 'config.php';
requireAdmin();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_user'])) {
    $nama = $_POST['nama'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (empty($nama) || empty($username) || empty($password)) {
        $error = 'Semua field wajib diisi!';
    } else {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username sudah ada. Silakan gunakan username lain.';
            } else {
                // Insert new user into the database
                $stmt = $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nama, $username, $hashed_password, $role]);
                $success = 'Pengguna baru berhasil ditambahkan!';
            }
        } catch (PDOException $e) {
            $error = 'Gagal menambahkan pengguna: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna Baru</title>
    <style>
        /* Gaya CSS yang sama dengan file lainnya */
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

        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
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

        .form-group input[type="text"], .form-group input[type="password"], .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        .form-group input[type="text"]:focus, .form-group input[type="password"]:focus, .form-group select:focus {
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
        <h1>Tambah Pengguna Baru</h1>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" action="user_add.php">
                <div class="form-group">
                    <label for="nama">Nama Lengkap:</label>
                    <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap" required autofocus>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                </div>
                <button type="submit" name="submit_user" class="submit-btn">Tambah Pengguna</button>
            </form>
        </div>
    </div>
    
    <script>
        function confirmLogout() {
            return confirm('Apakah Anda yakin ingin logout?');
        }
    </script>
</body>
</html>