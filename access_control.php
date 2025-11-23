<?php
// dashboard.php - Role-based Dashboard
require_once 'config.php';
requireLogin();

// Redirect based on role
if (isAdmin()) {
    // Admin diarahkan ke inventory management
    header('Location: inventory_input.php');
    exit;
} else {
    // User tetap di dashboard user
    // Dashboard ini hanya untuk user, admin tidak bisa akses
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - Sistem Pencatatan Berkas Dosir</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .nav-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav-menu a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .user-info {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .welcome-card h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #666;
            font-size: 18px;
            margin-bottom: 20px;
        }
        
        .role-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }
        
        .feature-card h3 {
            color: #333;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .restriction-notice {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            color: #0c5aa6;
        }
        
        .restriction-notice h3 {
            margin-bottom: 10px;
            color: #0c5aa6;
        }
        
        .workflow-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 30px;
        }
        
        .workflow-section h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .workflow-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .workflow-step {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            position: relative;
        }
        
        .step-number {
            position: absolute;
            top: -10px;
            left: 15px;
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .workflow-step h4 {
            color: #333;
            margin-bottom: 10px;
            margin-top: 10px;
        }
        
        .workflow-step p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
                gap: 10px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-card h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo">Sistem Pencatatan Berkas Dosir</div>
            <div class="nav-menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="check_dosir.php">Cari & Pinjam Berkas</a>
                <a href="data_pinjaman.php">Riwayat Pinjaman</a>
                <div class="user-info">
                    <?php echo htmlspecialchars($_SESSION['nama']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)
                </div>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <div class="role-badge">USER - Peminjam Berkas</div>
            <h1>Dashboard User</h1>
            <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</p>
            <p>Sistem Pencarian dan Peminjaman Berkas Dosir</p>
        </div>
        
        <div class="restriction-notice">
            <h3>Akses User:</h3>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li><strong>Dapat:</strong> Mencari berkas, mengajukan peminjaman, melihat riwayat pinjaman</li>
                <li><strong>Tidak dapat:</strong> Input data berkas inventory, edit data berkas, akses log penghapusan</li>
                <li><strong>Catatan:</strong> Semua peminjaman harus melalui proses pencarian berkas terlebih dahulu</li>
            </ul>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3>Cari & Pinjam Berkas</h3>
                <p>Cari berkas dosir berdasarkan nomor dosir, periksa ketersediaan, dan ajukan peminjaman secara langsung.</p>
                <a href="check_dosir.php" class="btn btn-primary">Mulai Pencarian</a>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3>Riwayat Pinjaman</h3>
                <p>Lihat semua riwayat peminjaman berkas yang pernah Anda lakukan beserta status pengembaliannya.</p>
                <a href="data_pinjaman.php" class="btn btn-primary">Lihat Riwayat</a>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">❓</div>
                <h3>Bantuan</h3>
                <p>Butuh bantuan menggunakan sistem? Hubungi administrator untuk informasi lebih lanjut.</p>
                <button class="btn btn-secondary" onclick="alert('Silakan hubungi administrator untuk bantuan:\nEmail: admin@sistem.com\nTelepon: 123-456-789')">Hubungi Admin</button>
            </div>
        </div>
        
        <div class="workflow-section">
            <h2>Cara Meminjam Berkas</h2>
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
                    <p>Lihat status peminjaman di menu "Riwayat Pinjaman" dan tunggu proses pengembalian oleh admin.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>