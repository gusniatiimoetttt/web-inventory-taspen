<?php
// pinjaman_input.php - File ini menolak semua akses untuk input pinjaman manual
require_once 'config.php';
requireLogin();

// Cek role dan redirect sesuai kebijakan
if (isAdmin()) {
    $_SESSION['access_error'] = 'Administrator tidak dapat menginput data pinjaman secara manual. Silakan kelola berkas melalui sistem inventory.';
    header('Location: inventory_input.php');
    exit;
} else {
    $_SESSION['access_error'] = 'Input data pinjaman manual tidak diizinkan. Gunakan fitur "Cari & Pinjam Berkas" untuk melakukan peminjaman.';
    header('Location: check_dosir.php');
    exit;
}

// File ini tidak akan pernah menampilkan konten karena semua akses diredirect
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak</title>
</head>
<body>
    <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
        <h1>Akses Ditolak</h1>
        <p>Halaman ini tidak dapat diakses.</p>
    </div>
</body>
</html>