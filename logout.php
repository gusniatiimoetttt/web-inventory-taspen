<?php
// logout.php - Proses Logout dan Redirect
session_start();

// Hapus semua variabel sesi
$_SESSION = array();

// Hancurkan sesi
session_destroy();

// Redirect ke halaman login
header('Location: index.php');
exit;

?>