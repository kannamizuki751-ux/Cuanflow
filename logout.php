<?php
// logout.php
session_start();
session_destroy(); // Menghapus semua data session
header("Location: login.php"); // Mengarahkan kembali ke halaman login
exit;
?>