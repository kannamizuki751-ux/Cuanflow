<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id']) && $_GET['id'] != '') {
    $id_target = mysqli_real_escape_string($conn, $_GET['id']);
    
    $query_cek = mysqli_query($conn, "SELECT username, role, password_hash FROM users WHERE id_user = '$id_target'");
    $data_cek = mysqli_fetch_assoc($query_cek);
    
    if ($data_cek && $data_cek['role'] == 'pedagang') {
        $hash_sekarang = $data_cek['password_hash'];
        
        // Cek apakah ada tulisan 'BLOKIR-' di depannya
        if (strpos($hash_sekarang, 'BLOKIR-') === 0) {
            // Hapus tulisan 'BLOKIR-' untuk mengembalikan password aslinya
            $hash_asli = str_replace('BLOKIR-', '', $hash_sekarang);
            
            $query_unblokir = "UPDATE users SET password_hash = '$hash_asli' WHERE id_user = '$id_target'";
            if (mysqli_query($conn, $query_unblokir)) {
                $_SESSION['flash_msg'] = "Berhasil! Akses '" . $data_cek['username'] . "' DIBUKA. Mereka bisa login pakai password aslinya.";
            } else {
                $_SESSION['flash_msg'] = "Gagal membuka blokir: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['flash_msg'] = "Akun ini tidak sedang diblokir.";
        }
    } else {
        $_SESSION['flash_msg'] = "Aksi ditolak! Akun tidak valid.";
    }
}

header("Location: manajemen_user.php");
exit;
?>