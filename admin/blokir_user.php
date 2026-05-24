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
        
        // Cek apakah belum diblokir (biar tidak dobel tulisan BLOKIR- nya)
        if (strpos($hash_sekarang, 'BLOKIR-') === false) {
            $hash_blokir = 'BLOKIR-' . $hash_sekarang;
            
            $query_blokir = "UPDATE users SET password_hash = '$hash_blokir' WHERE id_user = '$id_target'";
            if (mysqli_query($conn, $query_blokir)) {
                $_SESSION['flash_msg'] = "Akses akun '" . $data_cek['username'] . "' berhasil DIBLOKIR sementara.";
            } else {
                $_SESSION['flash_msg'] = "Gagal memblokir akun: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['flash_msg'] = "Akun ini memang sudah dalam keadaan terblokir.";
        }
    } else {
        $_SESSION['flash_msg'] = "Aksi ditolak! Akun tidak valid.";
    }
}

header("Location: manajemen_user.php");
exit;
?>