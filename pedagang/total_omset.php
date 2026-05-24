<?php
session_start();
require '../config/database.php';

// Keamanan: Pastikan role pedagang
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pedagang') {
    header("Location: ../login.php");
    exit;
}

// KUNCI UTAMA: Ambil ID pedagang yang sedang login
$id_pelaku = $_SESSION['user_id'];

// --- Ambil data foto profil user ---
$q_foto = mysqli_query($conn, "SELECT foto_profil FROM users WHERE id_user = '$id_pelaku'");
$d_foto = mysqli_fetch_assoc($q_foto);
$foto_profil = $d_foto['foto_profil'] ? $d_foto['foto_profil'] : 'default.png';

// --- LOGIKA CRUD (TAMBAH & HAPUS KAS DOMPET) ---
if (isset($_POST['simpan_kas'])) {
    $tipe = $_POST['tipe']; // 'pemasukan' atau 'pengeluaran'
    
    // [UPDATE] Hilangkan titik dari input nominal sebelum masuk database
    $nominal_str = str_replace('.', '', $_POST['nominal']);
    $nominal = (int)$nominal_str;
    
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    $tgl_sekarang = date('Y-m-d H:i:s');
    
    // Coba insert dengan id_barang bernilai NULL (karena ini bukan transaksi barang)
    $query_insert = "INSERT INTO transaksi (id_user, id_barang, qty, total_harga, tipe, keterangan, tanggal) 
                     VALUES ('$id_pelaku', NULL, 0, $nominal, '$tipe', '$keterangan', '$tgl_sekarang')";
    
    if (!mysqli_query($conn, $query_insert)) {
        // JIKA GAGAL, TAMPILKAN ERROR ASLI DARI DATABASE
        echo "<div style='background: #fee2e2; color: #ef4444; padding: 20px; text-align: center; font-family: sans-serif; margin: 20px; border-radius: 8px;'>";
        echo "<h3><i class='fa-solid fa-triangle-exclamation'></i> GAGAL MENYIMPAN KE DATABASE!</h3>";
        echo "<b>Pesan Error MySQL:</b><br><br> " . mysqli_error($conn);
        echo "</div>";
        exit; 
    } else {
        header("Location: total_omset.php?pesan=disimpan");
        exit;
    }
}

if (isset($_GET['hapus']) && isset($_GET['col'])) {
    $id_hapus = (int)$_GET['hapus'];
    $col = $_GET['col'] == 'id' ? 'id' : 'id_transaksi'; 
    mysqli_query($conn, "DELETE FROM transaksi WHERE $col = $id_hapus AND id_user = '$id_pelaku'");
    header("Location: total_omset.php?pesan=dihapus");
    exit;
}

// --- [UPDATE] LOGIKA NOTIFIKASI PENGADUAN ---
if (isset($_POST['tandai_dibaca'])) {
    // Ubah status is_read_user jadi 1 untuk semua pesan
    mysqli_query($conn, "UPDATE pengaduan SET is_read_user = 1 WHERE id_user = '$id_pelaku'");
    header("Location: total_omset.php");
    exit;
}

// Hitung unread: dihitung berdasarkan is_read_user = 0
$q_unread = mysqli_query($conn, "SELECT COUNT(*) as jml FROM pengaduan WHERE id_user = '$id_pelaku' AND is_read_user = 0");
$unread_data = mysqli_fetch_assoc($q_unread);
$unread_count = $unread_data['jml'];

$q_riwayat_notif = mysqli_query($conn, "SELECT * FROM pengaduan WHERE id_user = '$id_pelaku' ORDER BY tanggal DESC");


$total_omset_keseluruhan = 0;
$total_keuntungan_keseluruhan = 0;
$total_pemasukan_lain = 0;
$total_pengeluaran = 0;

// 1. MENGAMBIL DATA UNTUK KARTU SUMMARY DAN TABEL
$query_riwayat = mysqli_query($conn, "
    SELECT t.*, b.nama_barang, b.harga_beli, b.harga_jual 
    FROM transaksi t 
    LEFT JOIN stok_barang b ON t.id_barang = b.id_barang 
    WHERE t.id_user = '$id_pelaku'
    ORDER BY t.tanggal DESC
");

// 2. MENGAMBIL DATA KHUSUS UNTUK GRAFIK
$query_chart = mysqli_query($conn, "
    SELECT DATE(tanggal) as tanggal_transaksi, SUM(total_harga) as total_harian 
    FROM transaksi 
    WHERE tipe = 'jual' AND id_user = '$id_pelaku'
    GROUP BY DATE(tanggal) 
    ORDER BY DATE(tanggal) ASC
    LIMIT 30 
");

$label_tanggal = [];
$data_omset_harian = [];

if($query_chart) {
    while ($row = mysqli_fetch_assoc($query_chart)) {
        $label_tanggal[] = date('d M', strtotime($row['tanggal_transaksi']));
        $data_omset_harian[] = $row['total_harian'];
    }
}

$json_labels = json_encode($label_tanggal);
$json_data = json_encode($data_omset_harian);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Omset & Dompet - CuanFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .alert{padding:12px 16px;border-radius:8px;font-size:13px;font-weight:500;margin-bottom:20px;
            display:flex;align-items:center;gap:9px;}
        .alert-success{background:var(--green-dim);border:1px solid rgba(16,185,129,0.2);color:#34d399;}
        .alert-danger{background:var(--red-dim);border:1px solid rgba(239,68,68,0.2);color:#f87171;}
        .container{max-width:1100px;margin:40px auto;padding:0 24px;}
        .header-section{margin-bottom:28px;}
        .welcome-text h1{font-family:'Playfair Display',serif;font-size:22px;color:var(--white);margin-bottom:4px;}
        .welcome-text p{font-size:13px;color:var(--muted);}
        .summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;}
        .summary-card{background:var(--card);border:1px solid var(--border);border-radius:14px;
            padding:20px 22px;display:flex;align-items:center;gap:16px;}
        .icon-box{width:46px;height:46px;border-radius:11px;display:flex;align-items:center;
            justify-content:center;font-size:18px;}
        .icon-purple{background:rgba(147,51,234,0.12);color:#c084fc;border:1px solid rgba(147,51,234,0.2);}
        .icon-gold{background:var(--gold-dim);color:var(--gold);border:1px solid rgba(212,160,23,0.2);}
        .icon-green{background:var(--green-dim);color:var(--green);border:1px solid rgba(16,185,129,0.2);}
        .summary-info h3{font-size:11px;color:var(--muted);font-weight:600;letter-spacing:0.07em;text-transform:uppercase;margin-bottom:4px;}
        .summary-info p{font-size:20px;font-weight:700;color:var(--white);}
        .grid-layout{display:grid;grid-template-columns:320px 1fr;gap:24px;margin-bottom:28px;}
        .card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px;margin-bottom:24px;}
        .card h3{font-family:'Playfair Display',serif;font-size:16px;color:var(--white);margin-bottom:18px;
            padding-bottom:14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:9px;}
        .form-group{margin-bottom:16px;}
        .form-group label{display:block;font-size:11px;font-weight:600;color:var(--muted);
            letter-spacing:0.07em;text-transform:uppercase;margin-bottom:7px;}
        .form-control{width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;
            padding:10px 14px;font-size:13px;font-family:'Plus Jakarta Sans',sans-serif;
            color:var(--white);outline:none;transition:all 0.2s;}
        .form-control::placeholder{color:#2e2e42;}
        .form-control:focus{border-color:var(--gold);box-shadow:0 0 0 3px var(--gold-dim);background:var(--card);}
        select.form-control option{background:var(--card);color:var(--white);}
        .btn-primary{background:var(--gold);color:var(--bg);padding:11px;width:100%;border:none;
            border-radius:8px;font-weight:700;cursor:pointer;transition:all 0.2s;
            font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;}
        .btn-primary:hover{background:var(--gold-lt);}
        .chart-container{position:relative;height:300px;width:100%;}
        table{width:100%;border-collapse:collapse;}
        thead tr{border-bottom:1px solid var(--border);}
        th{text-align:left;padding:11px 12px;font-size:11px;color:var(--muted);font-weight:600;
            letter-spacing:0.07em;text-transform:uppercase;background:transparent;}
        td{padding:12px;font-size:13px;border-bottom:1px solid rgba(36,36,50,0.6);color:var(--text);}
        tbody tr:hover{background:rgba(255,255,255,0.015);}
        tbody tr:last-child td{border-bottom:none;}
        .date-badge{background:var(--surface);border:1px solid var(--border);color:var(--muted);
            padding:3px 8px;border-radius:5px;font-size:11px;font-weight:500;}
        .badge{padding:3px 8px;border-radius:4px;font-size:10px;font-weight:700;text-transform:uppercase;}
        .badge-out{background:var(--red-dim);color:var(--red);border:1px solid rgba(239,68,68,0.2);}
        .badge-in{background:var(--green-dim);color:var(--green);border:1px solid rgba(16,185,129,0.2);}
        .badge-sale{background:var(--gold-dim);color:var(--gold);border:1px solid rgba(212,160,23,0.2);}
        .btn-hapus{color:#3a3a55;text-decoration:none;font-size:14px;transition:0.2s;}
        .btn-hapus:hover{color:var(--red);}
        .empty-state{text-align:center;padding:30px;color:var(--muted);font-size:13px;}
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
        @media(max-width:900px){.grid-layout{grid-template-columns:1fr;}.summary-grid{grid-template-columns:1fr 1fr;}.nav-menu{display:none;}}
    </style>
</head>
<body>

    <nav class="modern-navbar">
        <a href="#" class="nav-logo">
            <i class="fa-solid fa-money-bill-trend-up"></i> CuanFlow
        </a>

        <div class="nav-menu">
            <a href="stok_transaksi.php"> Stok & Kasir</a>
            <a href="omset_hari_ini.php"> Omset Hari Ini</a>
            <a href="total_omset.php" class="active"> Dompet & Omset</a>
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
                <a href="profil.php" title="Edit Profil">
                <img src="../uploads/<?php echo htmlspecialchars($foto_profil); ?>" alt="Profil" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid var(--gold);">
                </a>
                <div class="user-info">
                <span ><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
                <a href="../logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar?');">
                    <i class="fa-solid fa-power-off"></i> Keluar
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="header-section">
            <div class="welcome-text">
                <h1>Dompet & Analisis Keuangan</h1>
                <p>Kelola kas masuk/keluar, dan pantau grafik omset tokomu di sini.</p>
            </div>
        </div>

        <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'disimpan') echo "<div class='alert alert-success'><i class='fa-solid fa-check'></i> Catatan dompet berhasil disimpan!</div>"; ?>
        <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'dihapus') echo "<div class='alert alert-success'><i class='fa-solid fa-trash'></i> Riwayat berhasil dihapus!</div>"; ?>

        <?php
        $data_transaksi = [];
        if($query_riwayat) {
            while ($row = mysqli_fetch_assoc($query_riwayat)) {
                $data_transaksi[] = $row;
                if ($row['tipe'] == 'jual') {
                    $total_omset_keseluruhan += $row['total_harga'];
                    $keuntungan = ($row['harga_jual'] - $row['harga_beli']) * $row['qty'];
                    $total_keuntungan_keseluruhan += $keuntungan;
                } elseif ($row['tipe'] == 'pemasukan') {
                    $total_pemasukan_lain += $row['total_harga'];
                } elseif ($row['tipe'] == 'pengeluaran') {
                    $total_pengeluaran += $row['total_harga'];
                }
            }
        }
        $saldo_akhir_dompet = $total_omset_keseluruhan + $total_pemasukan_lain - $total_pengeluaran;
        ?>

        <div class="summary-grid">
            <div class="summary-card" style="border-left: 4px solid #10b981;">
                <div class="icon-box icon-green"><i class="fa-solid fa-wallet"></i></div>
                <div class="summary-info">
                    <h3>Saldo Total Dompet</h3>
                    <p>Rp <?php echo number_format($saldo_akhir_dompet, 0, ',', '.'); ?></p>
                </div>
            </div>
            <div class="summary-card">
                <div class="icon-box icon-purple"><i class="fa-solid fa-sack-dollar"></i></div>
                <div class="summary-info">
                    <h3>Total Omset Barang</h3>
                    <p>Rp <?php echo number_format($total_omset_keseluruhan, 0, ',', '.'); ?></p>
                </div>
            </div>
            <div class="summary-card">
                <div class="icon-box icon-gold"><i class="fa-solid fa-vault"></i></div>
                <div class="summary-info">
                    <h3>Estimasi Cuan Bersih</h3>
                    <p>Rp <?php echo number_format($total_keuntungan_keseluruhan, 0, ',', '.'); ?></p>
                </div>
            </div>
        </div>

        <div class="grid-layout">
            <div>
                <div class="card" style="position: sticky; top: 90px;">
                    <h3><i class="fa-solid fa-pen-to-square" style="color: var(--gold);"></i> Catat Transaksi Kas</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Jenis Transaksi</label>
                            <select name="tipe" class="form-control" required>
                                <option value="pemasukan">Uang Masuk / Tambahan Kas</option>
                                <option value="pengeluaran">Uang Keluar / Pengeluaran</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nominal (Rp)</label>
                            <input type="text" name="nominal" class="form-control format-rupiah" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <input type="text" name="keterangan" class="form-control" required autocomplete="off">
                        </div>
                        <button type="submit" name="simpan_kas" class="btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan ke Dompet
                        </button>
                    </form>
                </div>
            </div>

            <div>
                <div class="card">
                    <h3><i class="fa-solid fa-chart-simple" style="color: var(--gold);"></i> Grafik Penjualan</h3>
                    <div class="chart-container">
                        <canvas id="omsetChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fa-solid fa-clock-rotate-left" style="color: var(--gold);"></i> Riwayat Arus Keuangan</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Tipe</th>
                                <th>Keterangan</th>
                                <th>Nominal</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($data_transaksi) > 0): ?>
                                <?php foreach ($data_transaksi as $trx): 
                                    $col_id_name = isset($trx['id_transaksi']) ? 'id_transaksi' : 'id';
                                ?>
                                <tr>
                                    <td><span class="date-badge"><?php echo date('d M Y, H:i', strtotime($trx['tanggal'])); ?></span></td>
                                    <td>
                                        <?php if($trx['tipe'] == 'jual'): ?>
                                            <span class="badge badge-sale">Jual Barang</span>
                                        <?php elseif($trx['tipe'] == 'pemasukan'): ?>
                                            <span class="badge badge-in">Uang Masuk</span>
                                        <?php else: ?>
                                            <span class="badge badge-out">Pengeluaran</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight: 500;">
                                        <?php echo ($trx['tipe'] == 'jual') ? htmlspecialchars($trx['nama_barang']) : htmlspecialchars($trx['keterangan']); ?>
                                    </td>
                                    <td style="font-weight: 600; color: <?php echo ($trx['tipe'] == 'pengeluaran') ? '#ef4444' : '#10b981'; ?>;">
                                        <?php echo ($trx['tipe'] == 'pengeluaran') ? '-' : '+'; ?> Rp <?php echo number_format($trx['total_harga'], 0, ',', '.'); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="?hapus=<?php echo $trx[$col_id_name]; ?>&col=<?php echo $col_id_name; ?>" class="btn-hapus" onclick="return confirm('Hapus riwayat ini?')"><i class="fa-solid fa-trash-can"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="empty-state">Belum ada data keuangan.</td></tr>
                            <?php endif; ?>
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
                <?php if(mysqli_num_rows($q_riwayat_notif) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($q_riwayat_notif)): 
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
                            <?php elseif($row['status'] == 'diproses' || $row['status'] == 'proses'): ?>
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
                            <button type="button" onclick="toggleBalasanAdmin(<?php echo $row['id_pengaduan']; ?>)" style="background: rgba(59,130,246,0.1); color: var(--blue); border: 1px solid rgba(59,130,246,0.2); padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; width: 100%; display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                                <span><i class="fa-solid fa-envelope-open-text" style="margin-right: 5px;"></i> Lihat Tanggapan Admin</span>
                                <i class="fa-solid fa-chevron-down" id="icon-balasan-<?php echo $row['id_pengaduan']; ?>"></i>
                            </button>

                            <div id="box-balasan-<?php echo $row['id_pengaduan']; ?>" style="display: none; background: var(--surface); border-left: 3px solid var(--blue); border-top: 1px solid var(--border); border-right: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 12px 15px; margin-top: 10px; border-radius: 0 8px 8px 0;">
                                <strong style="font-size: 12px; color: var(--white); display: block; margin-bottom: 5px;">Pesan Admin:</strong>
                                <p style="font-size: 13px; color: var(--muted); margin: 0; line-height: 1.6;">
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
        // 1. Script Jam
        function updateClock() {
            const now = new Date();
            const time = now.getHours().toString().padStart(2, '0') + ":" + now.getMinutes().toString().padStart(2, '0') + ":" + now.getSeconds().toString().padStart(2, '0');
            document.querySelector('#realtime-clock span').textContent = time;
        }
        setInterval(updateClock, 1000);
        updateClock(); 

        // 2. Script Buka Tutup Modal Notifikasi
        function bukaModalNotif() {
            document.getElementById('modal-notif').style.display = 'flex';
        }

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

        window.onclick = function(event) {
            var modal = document.getElementById('modal-notif');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // 3. Script Chart.js (Grafik Penjualan)
        const ctx = document.getElementById('omsetChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar', 
            data: {
                labels: <?php echo $json_labels; ?>, 
                datasets: [{
                    label: 'Omset (Rp)',
                    data: <?php echo $json_data; ?>, 
                    backgroundColor: 'rgba(212,160,23,0.45)',
                    borderColor: '#d4a017',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: { responsive: true, maintainAspectRatio: false,
            plugins:{legend:{display:false}},
            scales: {
                x: { ticks: { color:'#7a7a8e',font:{family:'Plus Jakarta Sans',size:11}}, grid:{color:'#242432'}},
                y: { ticks: { color:'#7a7a8e',font:{family:'Plus Jakarta Sans',size:11}}, grid:{color:'#242432'}}
            }}
        });

        // 4. Script Auto-Format Ribuan
        function formatRupiah(angka) {
            let number_string = angka.replace(/[^,\d]/g, '').toString(),
            split           = number_string.split(','),
            sisa            = split[0].length % 3,
            rupiah          = split[0].substr(0, sisa),
            ribuan          = split[0].substr(sisa).match(/\d{3}/gi);

            if(ribuan){
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            return rupiah;
        }

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