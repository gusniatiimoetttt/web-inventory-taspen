<?php
// profile_settings.php - Halaman untuk mengunggah dan memperbarui foto profil
require_once 'config.php';
requireLogin();

$success = '';
$error = '';
$upload_dir = 'uploads/profiles/';

// Helper function to safely delete files, dealing with Windows file locking issues
function safe_unlink($file_path, $retries = 3, $delay = 50) {
    // Cek apakah file benar-benar ada
    if (!file_exists($file_path)) {
        return true; 
    }

    // Coba hapus file sebanyak 'retries' kali
    for ($i = 0; $i < $retries; $i++) {
        // Jika penghapusan berhasil
        if (unlink($file_path)) {
            return true;
        }
        // Jeda 50 milidetik (50 * 1000 mikrodetik) sebelum mencoba lagi
        usleep($delay * 1000); 
    }

    // Jika semua percobaan gagal, kembalikan false
    return false; 
}


// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_profile_picture'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['profile_picture'];

    // Check if a file was uploaded
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2 MB

        if (!in_array($file['type'], $allowed_types)) {
            $error = "Tipe file tidak valid. Hanya JPG, JPEG, dan PNG yang diperbolehkan.";
        } elseif ($file['size'] > $max_size) {
            $error = "Ukuran file terlalu besar. Maksimal 2 MB.";
        } else {
            // Get user's current profile picture to delete it later
            $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_pic = $stmt->fetchColumn();

            // Generate a unique file name
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_file_name = uniqid() . '.' . $file_extension;
            $destination_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file['tmp_name'], $destination_path)) {
                // Delete old file if it exists and is not the default
                if ($current_pic && file_exists($upload_dir . $current_pic)) {
                    // MENGGUNAKAN FUNGSI BARU safe_unlink() UNTUK MENGATASI ISU LOCKING
                    if (!safe_unlink($upload_dir . $current_pic)) {
                        // Jika penghapusan gagal, berikan peringatan, tapi lanjutkan update DB
                        error_log("Gagal menghapus file lama: " . $upload_dir . $current_pic);
                        // Kita tetap lanjutkan karena file baru sudah terunggah
                    }
                }

                // Update database
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$new_file_name, $user_id]);

                // Update session variable
                $_SESSION['profile_picture'] = $new_file_name;

                $success = "Foto profil berhasil diunggah!";
            } else {
                $error = "Gagal mengunggah file.";
            }
        }
    } else {
        $error = "Terjadi kesalahan saat mengunggah file. Kode error: " . $file['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Profil</title>
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

        .form-group input[type="file"] {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 6px;
            width: 100%;
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
        
        .current-profile-pic {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .current-profile-pic img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid #ddd;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php 
    // Get the full path for the profile picture
    $profile_picture_path = 'uploads/profiles/' . htmlspecialchars($_SESSION['profile_picture'] ?? '');
    $profile_picture_src = (isset($_SESSION['profile_picture']) && file_exists($profile_picture_path)) 
                           ? $profile_picture_path 
                           : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['nama']) . '&background=random';
    ?>
    <div class="sidebar">
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
            <li><a href="profile_settings.php" class="active">Pengaturan Profil</a></li>
        </ul>
        <div class="sidebar-logout">
            <a href="logout.php" onclick="return confirmLogout();">Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <h1>Pengaturan Profil</h1>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <div class="current-profile-pic">
                <p>Foto Profil Saat Ini:</p>
                <img src="<?php echo $profile_picture_src; ?>" alt="Current Profile Picture">
            </div>
            <form method="POST" action="profile_settings.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="profile_picture">Pilih Foto Baru (JPG, JPEG, PNG, maks 2MB):</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept=".jpg, .jpeg, .png" required>
                </div>
                <button type="submit" name="submit_profile_picture" class="submit-btn">Unggah Foto Profil</button>
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