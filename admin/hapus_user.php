<?php
session_start();
require '../config/database.php';

// Keamanan: Pastikan yang mengakses adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Cek apakah ada ID yang dikirim dari tombol
if (isset($_GET['id']) && $_GET['id'] != '') {
    $id_target = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Hapus user dengan ID tersebut (Pastikan yang dihapus BUKAN admin)
    // Ubah tulisan 'id_user' di bawah ini jika nama kolom di databasemu berbeda!
    $query_hapus = "DELETE FROM users WHERE id_user = '$id_target' AND role = 'pedagang'";
    
    if (mysqli_query($conn, $query_hapus)) {
        $_SESSION['flash_msg'] = "Sip! Akun pedagang berhasil dihapus secara permanen.";
    } else {
        $_SESSION['flash_msg'] = "Gagal menghapus: " . mysqli_error($conn);
    }
}

// Kembalikan ke halaman manajemen
header("Location: manajemen_user.php");
exit;
?>