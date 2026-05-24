<?php
session_start();
require '../config/database.php';

// Keamanan: Pastikan role pedagang
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pedagang') {
    header("Location: ../login.php");
    exit;
}

$id_pelaku = $_SESSION['user_id'];
// Ambil data foto profil user
$q_foto = mysqli_query($conn, "SELECT foto_profil FROM users WHERE id_user = '$id_pelaku'");
$d_foto = mysqli_fetch_assoc($q_foto);
$foto_profil = $d_foto['foto_profil'] ? $d_foto['foto_profil'] : 'default.png';
$success_msg = '';
$error_msg = '';

// --- TANGKAP PESAN DARI URL AGAR AMAN SAAT DI-RELOAD ---
if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] == 'ditambah') $success_msg = "Barang berhasil ditambahkan!";
    if ($_GET['pesan'] == 'diedit') $success_msg = "Barang berhasil diperbarui!";
    if ($_GET['pesan'] == 'dihapus') $success_msg = "Barang berhasil dihapus.";
    if ($_GET['pesan'] == 'dijual') {
        $qty = isset($_GET['qty']) ? (int)$_GET['qty'] : '';
        $nama = isset($_GET['nama']) ? htmlspecialchars($_GET['nama']) : '';
        $success_msg = "Berhasil menjual $qty $nama!";
    }
}

// --- 1. LOGIKA SIMPAN & EDIT BARANG ---
if (isset($_POST['simpan_barang'])) {
    $id_barang = isset($_POST['id_barang']) ? (int)$_POST['id_barang'] : 0;
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    
    // [UPDATE] Hilangkan titik dari input harga sebelum masuk database
    $beli_str = str_replace('.', '', $_POST['harga_beli']);
    $jual_str = str_replace('.', '', $_POST['harga_jual']);
    
    $beli = (int)$beli_str;
    $jual = (int)$jual_str;
    
    $stok = (int)$_POST['stok'];
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);

    if ($id_barang > 0) {
        // MODE EDIT
        $query = "UPDATE stok_barang SET nama_barang='$nama', harga_beli=$beli, harga_jual=$jual, stok=$stok, kategori='$kategori' WHERE id_barang=$id_barang AND id_user='$id_pelaku'";
        if (mysqli_query($conn, $query)) {
            mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_pelaku', 'Mengedit data barang: $nama')");
            header("Location: stok_transaksi.php?pesan=diedit");
            exit;
        } else {
            $error_msg = "Gagal memperbarui barang.";
        }
    } else {
        // MODE TAMBAH BARU
        $query = "INSERT INTO stok_barang (id_user, nama_barang, harga_beli, harga_jual, stok, kategori) VALUES ('$id_pelaku', '$nama', $beli, $jual, $stok, '$kategori')";
        if (mysqli_query($conn, $query)) {
            mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_pelaku', 'Menambahkan stok barang baru: $nama')");
            header("Location: stok_transaksi.php?pesan=ditambah");
            exit;
        } else {
            $error_msg = "Gagal menambah barang.";
        }
    }
}

// --- 2. LOGIKA HAPUS BARANG ---
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $q_nama = mysqli_query($conn, "SELECT nama_barang FROM stok_barang WHERE id_barang = $id_hapus");
    $data_nama = mysqli_fetch_assoc($q_nama);
    $nama_dihapus = $data_nama ? $data_nama['nama_barang'] : 'Barang tidak diketahui';

    mysqli_query($conn, "DELETE FROM stok_barang WHERE id_barang = $id_hapus AND id_user = '$id_pelaku'");
    mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_pelaku', 'Menghapus barang: $nama_dihapus')");

    header("Location: stok_transaksi.php?pesan=dihapus");
    exit;
}

// --- 3. LOGIKA KASIR (TRANSAKSI JUAL) ---
if (isset($_POST['jual_barang'])) {
    $id_jual = (int)$_POST['id_barang_jual'];
    $qty_jual = (int)$_POST['qty'];
    $harga_jual = (int)$_POST['harga_jual_satuan'];
    
    $cek_stok = mysqli_query($conn, "SELECT stok, nama_barang FROM stok_barang WHERE id_barang = $id_jual AND id_user = '$id_pelaku'");
    $data_stok = mysqli_fetch_assoc($cek_stok);

    if ($data_stok['stok'] >= $qty_jual) {
        $total_harga = $qty_jual * $harga_jual;
        $nama_brg = mysqli_real_escape_string($conn, $data_stok['nama_barang']);
        
        mysqli_query($conn, "UPDATE stok_barang SET stok = stok - $qty_jual WHERE id_barang = $id_jual");
        
        $ket = "Terjual: " . $nama_brg;
        mysqli_query($conn, "INSERT INTO transaksi (id_user, id_barang, qty, total_harga, tipe, keterangan, status_shift) VALUES ('$id_pelaku', $id_jual, $qty_jual, $total_harga, 'jual', '$ket', 'aktif')");
        
        $format_harga = number_format($total_harga, 0, ',', '.');
        $log_jual = "Menjual $qty_jual unit $nama_brg (Total: Rp $format_harga)";
        mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_pelaku', '$log_jual')");

        header("Location: stok_transaksi.php?pesan=dijual&qty=$qty_jual&nama=" . urlencode($nama_brg));
        exit;
    } else {
        $error_msg = "Stok tidak cukup untuk penjualan ini!";
    }
}

// --- 4. LOGIKA NOTIFIKASI PENGADUAN ---
if (isset($_POST['tandai_dibaca'])) {
    mysqli_query($conn, "UPDATE pengaduan SET is_read_user = 1 WHERE id_user = '$id_pelaku'");
    header("Location: stok_transaksi.php");
    exit;
}

$q_unread = mysqli_query($conn, "SELECT COUNT(*) as jml FROM pengaduan WHERE id_user = '$id_pelaku' AND is_read_user = 0");
$unread_data = mysqli_fetch_assoc($q_unread);
$unread_count = $unread_data['jml'];

$q_riwayat = mysqli_query($conn, "SELECT * FROM pengaduan WHERE id_user = '$id_pelaku' ORDER BY tanggal DESC");


// --- CEK APAKAH SEDANG MODE EDIT ---
$data_edit = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $ambil_edit = mysqli_query($conn, "SELECT * FROM stok_barang WHERE id_barang = $id_edit AND id_user = '$id_pelaku'");
    if(mysqli_num_rows($ambil_edit) > 0){
        $data_edit = mysqli_fetch_assoc($ambil_edit);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok & Kasir - CuanFlow</title>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>

        :root {
            --bg:       #09090f; --surface:  #111118; --card:    #16161f; --border:  #242432;
            --gold:     #d4a017; --gold-lt:  #f0c040; --gold-dim:rgba(212,160,23,0.1);
            --green:    #10b981; --green-dim:rgba(16,185,129,0.1);
            --red:      #ef4444; --red-dim:  rgba(239,68,68,0.1);
            --blue:     #3b82f6; --blue-dim: rgba(59,130,246,0.1);
            --cream:    #f0ece3; --muted:    #7a7a8e; --white:   #fafaf8; --text:#c8c8d4;
        }
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        body{background:var(--bg);color:var(--text);font-family:'Plus Jakarta Sans',sans-serif;}
        body::before{content:'';position:fixed;inset:0;
            background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events:none;z-index:9999;opacity:0.5;}
        .modern-navbar{background:rgba(17,17,24,0.96);backdrop-filter:blur(20px);height:68px;display:flex;
            justify-content:space-between;align-items:center;padding:0 36px;position:sticky;top:0;z-index:1000;
            border-bottom:1px solid var(--border);box-shadow:0 4px 24px rgba(0,0,0,0.3);}
        .nav-logo{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--gold);
            text-decoration:none;display:flex;align-items:center;gap:10px;}
        .nav-menu{display:flex;gap:4px;}
        .nav-menu a{text-decoration:none;color:var(--muted);font-size:13px;font-weight:500;
            display:flex;align-items:center;gap:7px;padding:8px 14px;border-radius:7px;
            transition:all 0.2s;border:1px solid transparent;}
        .nav-menu a:hover{background:rgba(255,255,255,0.04);color:var(--cream);}
        .nav-menu a.active{background:var(--gold-dim);color:var(--gold);border-color:rgba(212,160,23,0.18);font-weight:600;}
        .nav-right-tools{display:flex;align-items:center;gap:14px;}
        .btn-notif-nav{background:var(--surface);border:1px solid var(--border);color:var(--muted);
            width:36px;height:36px;border-radius:8px;font-size:15px;cursor:pointer;position:relative;
            display:inline-flex;align-items:center;justify-content:center;transition:all 0.2s;}
        .btn-notif-nav:hover{color:var(--gold);border-color:rgba(212,160,23,0.3);background:var(--gold-dim);}
        .badge-notif{position:absolute;top:-4px;right:-4px;background:var(--red);color:white;font-size:9px;
            font-weight:700;width:16px;height:16px;display:flex;align-items:center;justify-content:center;
            border-radius:50%;border:2px solid var(--bg);animation:pulse 2s infinite;}
        @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(239,68,68,0.7);}70%{box-shadow:0 0 0 5px rgba(239,68,68,0);}100%{box-shadow:0 0 0 0 rgba(239,68,68,0);}}
        .live-clock{display:flex;align-items:center;gap:7px;background:var(--surface);border:1px solid var(--border);
            color:var(--muted);padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;
            font-family:monospace;letter-spacing:0.06em;}
        .user-pill{display:flex;align-items:center;gap:10px;background:var(--surface);
            border:1px solid var(--border);padding:4px 4px 4px 12px;border-radius:24px;}
        .user-info{font-size:13px;color:var(--muted);font-weight:500;}
        .user-info span{font-weight:600;color:var(--cream);}
        .btn-logout{background:var(--red-dim);border:1px solid rgba(239,68,68,0.2);color:var(--red);
            text-decoration:none;padding:7px 13px;border-radius:20px;font-size:12px;font-weight:600;
            transition:all 0.2s;display:flex;align-items:center;gap:5px;}
        .btn-logout:hover{background:var(--red);color:white;}
        .modal{display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;
            background:rgba(0,0,0,0.7);align-items:center;justify-content:center;backdrop-filter:blur(6px);}
        .modal-content{background:var(--card);border:1px solid var(--border);padding:28px;
            border-radius:16px;width:90%;max-width:480px;max-height:85vh;overflow-y:auto;
            box-shadow:0 24px 48px rgba(0,0,0,0.5);animation:modalFadeIn 0.25s cubic-bezier(0.22,1,0.36,1);}
        @keyframes modalFadeIn{from{opacity:0;transform:translateY(-16px) scale(0.98)}to{opacity:1;transform:none}}
        .modal-title{font-family:'Playfair Display',serif;font-size:16px;color:var(--white);
            margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border);
            display:flex;align-items:center;gap:9px;}
        .riwayat-list{display:flex;flex-direction:column;gap:10px;margin-bottom:18px;}
        .riwayat-item{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px;}
        .riwayat-item.unread{background:rgba(16,185,129,0.05);border-color:rgba(16,185,129,0.2);border-left:3px solid var(--green);}
        .r-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;}
        .r-subjek{font-weight:600;font-size:13px;color:var(--white);}
        .r-tanggal{font-size:10px;color:var(--muted);margin-top:3px;display:block;}
        .r-status{font-size:10px;font-weight:700;padding:3px 8px;border-radius:4px;text-transform:uppercase;letter-spacing:0.05em;}
        .st-pending{background:rgba(234,179,8,0.15);color:#fbbf24;border:1px solid rgba(234,179,8,0.2);}
        .st-diproses{background:var(--blue-dim);color:var(--blue);border:1px solid rgba(59,130,246,0.2);}
        .st-selesai{background:var(--green-dim);color:var(--green);border:1px solid rgba(16,185,129,0.2);}
        .st-ditolak{background:var(--red-dim);color:var(--red);border:1px solid rgba(239,68,68,0.2);}
        .r-pesan{font-size:12px;color:var(--muted);line-height:1.6;}
        .empty-state{text-align:center;padding:28px;color:var(--muted);font-size:13px;}
        .empty-state i{font-size:32px;color:#2a2a3e;margin-bottom:8px;display:block;}
        .btn-tandai{background:var(--gold);color:var(--bg);width:100%;padding:11px;border:none;
            border-radius:8px;font-weight:700;cursor:pointer;transition:all 0.2s;
            font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;}
        .btn-tandai:hover{background:var(--gold-lt);}
        .btn-floating-help{position:fixed;bottom:28px;right:28px;background:var(--gold);color:var(--bg);
            width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;
            font-size:20px;box-shadow:0 4px 16px rgba(212,160,23,0.35);text-decoration:none;
            transition:all 0.3s ease;z-index:1000;}
        .btn-floating-help:hover{background:var(--gold-lt);transform:translateY(-4px);box-shadow:0 8px 24px rgba(212,160,23,0.45);}
        .btn-floating-help::after{content:"Pusat Bantuan";position:absolute;right:64px;
            background:var(--card);color:var(--cream);border:1px solid var(--border);
            padding:5px 11px;border-radius:6px;font-size:12px;font-weight:600;
            opacity:0;pointer-events:none;transition:0.25s;white-space:nowrap;font-family:'Plus Jakarta Sans',sans-serif;}
        .btn-floating-help:hover::after{opacity:1;}
        .container{max-width:1200px;margin:36px auto;padding:0 24px;}
        .header-title{margin-bottom:28px;}
        .header-title h1{font-family:'Playfair Display',serif;font-size:22px;color:var(--white);font-weight:600;margin-bottom:4px;}
        .header-title p{font-size:13px;color:var(--muted);}
        .main-grid{display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start;}
        .card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px;
            box-shadow:none;}
        .card h3{font-family:'Playfair Display',serif;font-size:16px;color:var(--white);margin-bottom:20px;
            display:flex;align-items:center;gap:9px;padding-bottom:14px;border-bottom:1px solid var(--border);}
        .form-group{margin-bottom:16px;}
        .form-group label{display:block;font-size:11px;font-weight:600;color:var(--muted);
            letter-spacing:0.07em;text-transform:uppercase;margin-bottom:7px;}
        .form-control{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;
            padding:10px 14px;font-size:13px;font-family:'Plus Jakarta Sans',sans-serif;
            color:var(--white);outline:none;transition:all 0.2s;}
        .form-control::placeholder{color:#2e2e42;}
        .form-control:focus{border-color:var(--gold);box-shadow:0 0 0 3px var(--gold-dim);background:var(--card);}
        select.form-control{cursor:pointer;}
        select.form-control option{background:var(--card);color:var(--white);}
        .btn-primary{background:var(--gold);color:var(--bg);width:100%;padding:11px;border:none;
            border-radius:8px;font-size:13px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;
            cursor:pointer;transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:8px;
            letter-spacing:0.02em;box-shadow:0 4px 14px rgba(212,160,23,0.2);margin-top:8px;}
        .btn-primary:hover{background:var(--gold-lt);transform:translateY(-1px);box-shadow:0 6px 18px rgba(212,160,23,0.3);}
        .btn-cancel{display:block;text-align:center;margin-top:10px;font-size:12px;color:var(--red);
            text-decoration:none;font-weight:500;}
        .alert{padding:12px 16px;border-radius:8px;font-size:13px;font-weight:500;margin-bottom:20px;
            display:flex;align-items:center;gap:9px;}
        .alert-success{background:var(--green-dim);border:1px solid rgba(16,185,129,0.2);color:#34d399;}
        .alert-danger{background:var(--red-dim);border:1px solid rgba(239,68,68,0.2);color:#f87171;}
        .search-box{position:relative;margin-bottom:14px;}
        .search-box i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;}
        .search-box input{width:100%;padding:10px 14px 10px 36px;background:var(--surface);
            border:1px solid var(--border);border-radius:8px;font-size:13px;color:var(--white);
            outline:none;transition:all 0.2s;font-family:'Plus Jakarta Sans',sans-serif;}
        .search-box input::placeholder{color:#2e2e42;}
        .search-box input:focus{border-color:var(--gold);background:var(--card);box-shadow:0 0 0 3px var(--gold-dim);}
        .filter-buttons{display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;}
        .btn-filter{background:var(--surface);border:1px solid var(--border);color:var(--muted);
            padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600;cursor:pointer;
            transition:all 0.2s;display:flex;align-items:center;gap:5px;font-family:'Plus Jakarta Sans',sans-serif;}
        .btn-filter:hover{border-color:rgba(212,160,23,0.3);color:var(--gold);}
        .btn-filter.active{background:var(--gold-dim);color:var(--gold);border-color:rgba(212,160,23,0.35);}
        table{width:100%;border-collapse:collapse;}
        thead tr{border-bottom:1px solid var(--border);}
        th{text-align:left;padding:11px 12px;font-size:11px;color:var(--muted);font-weight:600;
            letter-spacing:0.07em;text-transform:uppercase;white-space:nowrap;}
        td{padding:14px 12px;font-size:13px;border-bottom:1px solid rgba(36,36,50,0.6);
            color:var(--text);vertical-align:middle;}
        tbody tr:hover{background:rgba(255,255,255,0.015);}
        tbody tr:last-child td{border-bottom:none;}
        .badge-stok{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;
            background:var(--blue-dim);color:var(--blue);border:1px solid rgba(59,130,246,0.2);}
        .badge-stok.habis{background:var(--red-dim);color:var(--red);border-color:rgba(239,68,68,0.2);}
        .badge-cat{padding:3px 8px;border-radius:4px;font-size:10px;font-weight:700;text-transform:uppercase;
            letter-spacing:0.05em;display:inline-block;margin-top:4px;}
        .cat-makanan{background:rgba(234,88,12,0.15);color:#fb923c;border:1px solid rgba(234,88,12,0.2);}
        .cat-minuman{background:rgba(37,99,235,0.12);color:#60a5fa;border:1px solid rgba(37,99,235,0.2);}
        .cat-peralatan{background:rgba(100,116,139,0.15);color:#94a3b8;border:1px solid rgba(100,116,139,0.2);}
        .cat-kebutuhan{background:rgba(16,185,129,0.12);color:#34d399;border:1px solid rgba(16,185,129,0.2);}
        .cat-diskon{background:rgba(139,92,246,0.12);color:#a78bfa;border:1px solid rgba(139,92,246,0.2);}
        .action-flex{display:flex;gap:10px;}
        .btn-icon{color:#3a3a55;transition:all 0.2s;text-decoration:none;font-size:15px;}
        .btn-icon.edit:hover{color:var(--blue);}
        .btn-icon.delete:hover{color:var(--red);}
        .kasir-form{display:flex;gap:7px;align-items:center;justify-content:center;}
        .kasir-form input[type="number"]{width:64px;padding:7px;background:var(--surface);
            border:1px solid var(--border);border-radius:6px;text-align:center;outline:none;
            color:var(--white);font-size:13px;font-family:'Plus Jakarta Sans',sans-serif;}
        .kasir-form input[type="number"]:focus{border-color:var(--gold);}
        .btn-jual{background:var(--green);color:var(--bg);border:none;padding:7px 14px;border-radius:6px;
            font-size:12px;font-weight:700;cursor:pointer;transition:all 0.2s;
            display:flex;align-items:center;gap:5px;font-family:'Plus Jakarta Sans',sans-serif;}
        .btn-jual:hover{background:#0ea573;}
        .btn-jual:disabled{background:#1e1e2a;color:#3a3a55;cursor:not-allowed;}
        @media(max-width:900px){.main-grid{grid-template-columns:1fr;}.nav-menu{display:none;}}
    </style>
</head>
<body>

    <nav class="modern-navbar">
        <a href="#" class="nav-logo">
            <i class="fa-solid fa-money-bill-trend-up"></i> CuanFlow
        </a>

        <div class="nav-menu">
            <a href="stok_transaksi.php" class="active"> Stok & Kasir</a>
            <a href="omset_hari_ini.php"> Omset Hari Ini</a>
            <a href="total_omset.php"> Dompet & Omset</a>
        </div>

        <div class="nav-right-tools">
            <button type="button" class="btn-notif-nav" onclick="bukaModalNotif()" title="Riwayat Laporan">
                <i class="fa-regular fa-bell"></i>
                <?php if($unread_count > 0): ?>
                    <span class="badge-notif"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </button>

            <div class="live-clock" id="realtime-clock">
                <i class="fa-regular fa-clock"></i> <span>00:00:00</span>
            </div>

            <div class="user-pill">
                <a href="profil.php" title="Edit Profil" style="display: flex; align-items: center;">
                <img src="../uploads/<?php echo htmlspecialchars($foto_profil); ?>" alt="Profil" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid var(--gold);">
                </a>

                <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
                <a href="../logout.php" class="btn-logout" onclick="return confirm('Sebelum Log Out, Pastikan Untuk mengakhiri kerja terlebih dahulu. Yakin ingin keluar?');">
                    <i class="fa-solid fa-power-off"></i> Keluar
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="header-title">
            <h1>Panel Manajemen Toko</h1>
            <p>Kelola persediaan barang, kategori, dan proses transaksi penjualan dengan cepat.</p>
        </div>

        <?php if($success_msg) echo "<div class='alert alert-success'><i class='fa-solid fa-circle-check'></i> $success_msg</div>"; ?>
        <?php if($error_msg) echo "<div class='alert alert-danger'><i class='fa-solid fa-circle-exclamation'></i> $error_msg</div>"; ?>

        <div class="main-grid">
            
            <div class="card" style="position: sticky; top: 90px;">
                <?php if($data_edit): ?>
                    <h3><i class="fa-solid fa-pen-to-square" style="color: #3b82f6;"></i> Edit Barang</h3>
                <?php else: ?>
                    <h3><i class="fa-solid fa-circle-plus" style="color: #4f46e5;"></i> Barang Baru</h3>
                <?php endif; ?>

                <form method="POST" id="form-barang">
                    <?php if($data_edit): ?>
                        <input type="hidden" name="id_barang" value="<?php echo $data_edit['id_barang']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Nama Produk</label>
                        <input type="text" name="nama_barang" class="form-control" placeholder="Contoh: Kopi Susu" required value="<?php echo $data_edit ? htmlspecialchars($data_edit['nama_barang']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori" class="form-control" required>
                            <option value="Makanan" <?php echo ($data_edit && $data_edit['kategori'] == 'Makanan') ? 'selected' : ''; ?>>Makanan</option>
                            <option value="Minuman" <?php echo ($data_edit && $data_edit['kategori'] == 'Minuman') ? 'selected' : ''; ?>>Minuman</option>
                            <option value="Peralatan" <?php echo ($data_edit && $data_edit['kategori'] == 'Peralatan') ? 'selected' : ''; ?>>Peralatan</option>
                            <option value="Kebutuhan" <?php echo ($data_edit && $data_edit['kategori'] == 'Kebutuhan') ? 'selected' : ''; ?>>Kebutuhan</option>
                            <option value="Diskon" <?php echo ($data_edit && $data_edit['kategori'] == 'Diskon') ? 'selected' : ''; ?>>Diskon</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Harga Modal (Rp)</label>
                        <input type="text" name="harga_beli" id="input_harga_beli" class="form-control format-rupiah" required value="<?php echo $data_edit ? number_format($data_edit['harga_beli'], 0, '', '.') : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Harga Jual (Rp)</label>
                        <input type="text" name="harga_jual" id="input_harga_jual" class="form-control format-rupiah" required value="<?php echo $data_edit ? number_format($data_edit['harga_jual'], 0, '', '.') : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Sisa Stok</label>
                        <input type="number" name="stok" class="form-control" required value="<?php echo $data_edit ? $data_edit['stok'] : ''; ?>">
                    </div>

                    <button type="submit" name="simpan_barang" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> <?php echo $data_edit ? 'Simpan Perubahan' : 'Simpan ke Stok'; ?>
                    </button>

                    <?php if($data_edit): ?>
                        <a href="stok_transaksi.php" class="btn-cancel">Batal Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card">
                <h3><i class="fa-solid fa-list-check" style="color: #4f46e5;"></i> Daftar Inventaris & Kasir</h3>
                
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" onkeyup="applyFilters()" placeholder="Cari nama produk atau kategori...">
                </div>

                <div class="filter-buttons">
                    <button class="btn-filter active" onclick="setCategoryFilter('all', this)"><i class="fa-solid fa-layer-group"></i> Semua</button>
                    <button class="btn-filter" onclick="setCategoryFilter('Makanan', this)"><i class="fa-solid fa-burger" style="color: #f59e0b;"></i> Makanan</button>
                    <button class="btn-filter" onclick="setCategoryFilter('Minuman', this)"><i class="fa-solid fa-mug-hot" style="color: #0ea5e9;"></i> Minuman</button>
                    <button class="btn-filter" onclick="setCategoryFilter('Peralatan', this)"><i class="fa-solid fa-screwdriver-wrench" style="color: #64748b;"></i> Peralatan</button>
                    <button class="btn-filter" onclick="setCategoryFilter('Kebutuhan', this)"><i class="fa-solid fa-basket-shopping" style="color: #10b981;"></i> Kebutuhan</button>
                    <button class="btn-filter" onclick="setCategoryFilter('Diskon', this)"><i class="fa-solid fa-tag" style="color: #8b5cf6;"></i> Diskon</button>
                </div>

                <div style="overflow-x: auto;">
                    <table id="inventoryTable">
                        <thead>
                            <tr>
                                <th>Produk & Kategori</th>
                                <th>Sisa Stok</th>
                                <th>Harga Jual</th>
                                <th>Aksi</th>
                                <th style="text-align: center;">Proses Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT * FROM stok_barang WHERE id_user = '$id_pelaku' ORDER BY id_barang DESC");
                            while($row = mysqli_fetch_assoc($res)):
                                // Logika Warna Kategori
                                $kategori = isset($row['kategori']) ? $row['kategori'] : 'Lainnya';
                                $cat_class = '';
                                if($kategori == 'Makanan') $cat_class = 'cat-makanan';
                                elseif($kategori == 'Minuman') $cat_class = 'cat-minuman';
                                elseif($kategori == 'Peralatan') $cat_class = 'cat-peralatan';
                                elseif($kategori == 'Kebutuhan') $cat_class = 'cat-kebutuhan';
                                elseif($kategori == 'Diskon') $cat_class = 'cat-diskon';
                            ?>
                            <tr data-kategori="<?php echo htmlspecialchars($kategori); ?>">
                                <td>
                                    <strong style="color: #1e293b;"><?php echo htmlspecialchars($row['nama_barang']); ?></strong><br>
                                    <span class="badge-cat <?php echo $cat_class; ?>"><?php echo $kategori; ?></span>
                                </td>
                                <td>
                                    <span class="badge-stok <?php echo ($row['stok'] <= 0) ? 'habis' : ''; ?>">
                                        <?php echo $row['stok']; ?> Unit
                                    </span>
                                </td>
                                <td style="font-weight: 500;">Rp <?php echo number_format($row['harga_jual'],0,',','.'); ?></td>
                                <td>
                                    <div class="action-flex">
                                        <a href="?edit=<?php echo $row['id_barang']; ?>" class="btn-icon edit" title="Edit Barang">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="?hapus=<?php echo $row['id_barang']; ?>" class="btn-icon delete" onclick="return confirm('Hapus barang ini secara permanen?')" title="Hapus Barang">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" class="kasir-form">
                                        <input type="hidden" name="id_barang_jual" value="<?php echo $row['id_barang']; ?>">
                                        <input type="hidden" name="harga_jual_satuan" value="<?php echo $row['harga_jual']; ?>">
                                        
                                        <input type="number" name="qty" min="1" max="<?php echo $row['stok']; ?>" value="1" <?php echo ($row['stok'] <= 0) ? 'disabled' : ''; ?>>
                                        
                                        <button type="submit" name="jual_barang" class="btn-jual" <?php echo ($row['stok'] <= 0) ? 'disabled' : ''; ?>>
                                            <i class="fa-solid fa-cart-arrow-down"></i> Jual
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <div id="modal-notif" class="modal">
        <div class="modal-content">
            <div class="modal-title">
                <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Laporan Pengaduan
            </div>

            <div class="riwayat-list">
                <?php if(mysqli_num_rows($q_riwayat) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($q_riwayat)): 
                        $is_new = ($row['is_read_user'] == 0) ? 'unread' : '';
                    ?>
                    <div class="riwayat-item <?php echo $is_new; ?>">
                        <div class="r-header">
                            <div>
                                <span class="r-subjek"><?php echo htmlspecialchars($row['subjek']); ?></span>
                                <span class="r-tanggal"><?php echo date('d M Y, H:i', strtotime($row['tanggal'])); ?></span>
                            </div>
                            
                            <?php if($row['status'] == 'pending'): ?>
                                <span class="r-status st-pending">Menunggu</span>
                            <?php elseif($row['status'] == 'diproses'): ?>
                                <span class="r-status st-diproses">Diproses</span>
                            <?php elseif($row['status'] == 'ditolak'): ?>
                                <span class="r-status st-ditolak">Ditolak</span>
                            <?php else: ?>
                                <span class="r-status st-selesai">Selesai</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="r-pesan">
                            <?php echo htmlspecialchars(substr($row['pesan'], 0, 70)) . (strlen($row['pesan']) > 70 ? '...' : ''); ?>
                        </div>

                        <?php if (!empty($row['balasan_admin'])): ?>
                            <button onclick="toggleBalasanAdmin(<?php echo $row['id_pengaduan']; ?>)" style="background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; width: 100%; display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                                <span><i class="fa-solid fa-envelope-open-text" style="margin-right: 5px;"></i> Lihat Tanggapan Admin</span>
                                <i class="fa-solid fa-chevron-down" id="icon-balasan-<?php echo $row['id_pengaduan']; ?>"></i>
                            </button>

                            <div id="box-balasan-<?php echo $row['id_pengaduan']; ?>" style="display: none; background: #f8fafc; border-left: 4px solid #3b82f6; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 12px 15px; margin-top: 10px; border-radius: 0 8px 8px 0;">
                                <strong style="font-size: 12px; color: #0f172a; display: block; margin-bottom: 5px;">Pesan Admin:</strong>
                                <p style="font-size: 13px; color: #475569; margin: 0; line-height: 1.6;">
                                    <?php echo nl2br(htmlspecialchars($row['balasan_admin'])); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-folder-open"></i><br>
                        Belum ada riwayat laporan.
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST">
                <button type="submit" name="tandai_dibaca" class="btn-tandai">
                    Tutup & Tandai Semua Dibaca
                </button>
            </form>
        </div>
    </div>

    <script>
        // Script Jam Digital Kasir
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.querySelector('#realtime-clock span').textContent = `${hours}:${minutes}:${seconds}`;
        }
        setInterval(updateClock, 1000);
        updateClock(); 

        let currentCategory = 'all'; 

        function setCategoryFilter(kategori, btnElement) {
            currentCategory = kategori;
            let buttons = document.getElementsByClassName("btn-filter");
            for (let i = 0; i < buttons.length; i++) {
                buttons[i].classList.remove("active");
            }
            btnElement.classList.add("active");
            applyFilters();
        }

        function applyFilters() {
            let searchInput = document.getElementById("searchInput").value.toLowerCase();
            let table = document.getElementById("inventoryTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let tdName = tr[i].getElementsByTagName("td")[0];
                let rowCategory = tr[i].getAttribute("data-kategori");

                if (tdName) {
                    let textValue = tdName.textContent || tdName.innerText;
                    let matchSearch = textValue.toLowerCase().indexOf(searchInput) > -1;
                    let matchCategory = (currentCategory === 'all' || rowCategory === currentCategory);

                    if (matchSearch && matchCategory) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // --- SCRIPT BUKA TUTUP MODAL NOTIFIKASI ---
        function bukaModalNotif() {
            document.getElementById('modal-notif').style.display = 'flex';
        }

        // --- SCRIPT TOGGLE AKORDEON TANGGAPAN ADMIN ---
        function toggleBalasanAdmin(id) {
            var boxBalasan = document.getElementById('box-balasan-' + id);
            var icon = document.getElementById('icon-balasan-' + id);
            
            if (boxBalasan.style.display === 'none') {
                boxBalasan.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                boxBalasan.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }

        // Tutup modal jika user klik di luar area putih modal
        window.onclick = function(event) {
            var modal = document.getElementById('modal-notif');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        // --- [BARU] FUNGSI FORMAT RIBUAN OTOMATIS ---
        function formatRupiah(angka) {
            let number_string = angka.replace(/[^,\d]/g, '').toString(),
            split   		= number_string.split(','),
            sisa     		= split[0].length % 3,
            rupiah     		= split[0].substr(0, sisa),
            ribuan     		= split[0].substr(sisa).match(/\d{3}/gi);

            if(ribuan){
                separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            return rupiah;
        }

        // Attach event listener ke semua input dengan class format-rupiah
        const inputsRupiah = document.querySelectorAll('.format-rupiah');
        inputsRupiah.forEach(function(input) {
            input.addEventListener('keyup', function(e) {
                this.value = formatRupiah(this.value);
            });
        });
    </script>

    <a href="pengaduan.php" class="btn-floating-help" title="Laporkan Kendala">
        <i class="fa-solid fa-headset"></i>
    </a>
</body>
</html>