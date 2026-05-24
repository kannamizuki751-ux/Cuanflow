<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pedagang') {
    header("Location: ../login.php");
    exit;
}

$success_msg = '';

// KUNCI UTAMA: Ambil ID pedagang yang sedang login
$id_pelaku = $_SESSION['user_id'];

// --- Ambil data foto profil user ---
$q_foto = mysqli_query($conn, "SELECT foto_profil FROM users WHERE id_user = '$id_pelaku'");
$d_foto = mysqli_fetch_assoc($q_foto);
$foto_profil = $d_foto['foto_profil'] ? $d_foto['foto_profil'] : 'default.png';

// 1. LOGIKA HAPUS TRANSAKSI (KOREKSI KESALAHAN) - Khusus milik user login
if (isset($_GET['hapus_id'])) {
    $id_hapus = (int)$_GET['hapus_id'];
    
    // Pastikan transaksi yang dihapus benar-benar milik user ini
    $cek = mysqli_query($conn, "SELECT id_barang, qty, tipe FROM transaksi WHERE id_transaksi = $id_hapus AND id_user = '$id_pelaku'");
    
    // Jika data ditemukan (valid milik user)
    if (mysqli_num_rows($cek) > 0) {
        $data_cek = mysqli_fetch_assoc($cek);
        
        // Kembalikan stoknya jika yang dihapus adalah penjualan
        if ($data_cek['tipe'] == 'jual') {
            $id_brg = $data_cek['id_barang'];
            $qty_brg = $data_cek['qty'];
            mysqli_query($conn, "UPDATE stok_barang SET stok = stok + $qty_brg WHERE id_barang = $id_brg");
        }

        // Hapus transaksi
        mysqli_query($conn, "DELETE FROM transaksi WHERE id_transaksi = $id_hapus AND id_user = '$id_pelaku'");
    }
    header("Location: omset_hari_ini.php?pesan=Data berhasil dihapus");
    exit;
}

// 2. LOGIKA AKHIRI SHIFT - Hanya tutup buku milik user ini saja
if (isset($_GET['akhiri_shift'])) {
    mysqli_query($conn, "UPDATE transaksi SET status_shift = 'ditutup' WHERE DATE(tanggal) = CURDATE() AND status_shift = 'aktif' AND id_user = '$id_pelaku'");
    header("Location: omset_hari_ini.php?sukses=1");
    exit;
}

// --- 3. [BARU] LOGIKA NOTIFIKASI PENGADUAN ---
if (isset($_POST['tandai_dibaca'])) {
    // Diubah supaya nandain semua pesan yg belum dibaca
    mysqli_query($conn, "UPDATE pengaduan SET is_read_user = 1 WHERE id_user = '$id_pelaku'");
    header("Location: omset_hari_ini.php");
    exit;
}

// Menghitung unread berdasar is_read_user = 0 (apapun statusnya)
$q_unread = mysqli_query($conn, "SELECT COUNT(*) as jml FROM pengaduan WHERE id_user = '$id_pelaku' AND is_read_user = 0");
$unread_data = mysqli_fetch_assoc($q_unread);
$unread_count = $unread_data['jml'];

$q_riwayat = mysqli_query($conn, "SELECT * FROM pengaduan WHERE id_user = '$id_pelaku' ORDER BY tanggal DESC");


// Ambil data transaksi aktif - HANYA MILIK USER YANG LOGIN
$query_riwayat = mysqli_query($conn, "
    SELECT t.*, b.nama_barang, b.harga_beli, b.harga_jual 
    FROM transaksi t 
    LEFT JOIN stok_barang b ON t.id_barang = b.id_barang 
    WHERE DATE(t.tanggal) = CURDATE() AND t.status_shift = 'aktif' AND t.id_user = '$id_pelaku'
    ORDER BY t.id_transaksi DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omset Hari Ini - CuanFlow</title>
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
        .container{max-width:1100px;margin:40px auto;padding:0 24px;}
        .header-flex{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;}
        .header-flex h1{font-family:'Playfair Display',serif;font-size:22px;color:var(--white);margin-bottom:4px;}
        .header-flex p{font-size:13px;color:var(--muted);}
        .btn-add{background:var(--red-dim);color:var(--red);border:1px solid rgba(239,68,68,0.2);
            padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;
            display:inline-flex;align-items:center;gap:7px;transition:all 0.2s;font-family:'Plus Jakarta Sans',sans-serif;}
        .btn-add:hover{background:var(--red);color:white;}
        .summary-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:28px;}
        .card-stat{background:var(--card);border:1px solid var(--border);border-radius:14px;
            padding:20px 22px;display:flex;align-items:center;gap:16px;}
        .icon-circle{width:46px;height:46px;border-radius:11px;display:flex;align-items:center;
            justify-content:center;font-size:18px;flex-shrink:0;}
        .chart-card{background:var(--card);border:1px solid var(--border);border-radius:14px;
            padding:24px;margin-bottom:24px;}
        .chart-card h3{font-family:'Playfair Display',serif;font-size:16px;color:var(--white);
            margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border);}
        .table-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:24px;}
        .table-card h3{font-family:'Playfair Display',serif;font-size:16px;color:var(--white);
            margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border);}
        table{width:100%;border-collapse:collapse;}
        thead tr{border-bottom:1px solid var(--border);}
        th{text-align:left;padding:11px 12px;font-size:11px;color:var(--muted);font-weight:600;
            letter-spacing:0.07em;text-transform:uppercase;}
        td{padding:14px 12px;font-size:13px;border-bottom:1px solid rgba(36,36,50,0.6);color:var(--text);vertical-align:middle;}
        tbody tr:hover{background:rgba(255,255,255,0.015);}
        tbody tr:last-child td{border-bottom:none;}
        .text-green{color:var(--green);font-weight:600;}
        .badge{padding:3px 8px;border-radius:4px;font-size:10px;font-weight:700;text-transform:uppercase;}
        .badge-in{background:var(--green-dim);color:var(--green);border:1px solid rgba(16,185,129,0.2);}
        .btn-del{color:#3a3a55;transition:0.2s;font-size:14px;}
        .btn-del:hover{color:var(--red);}
        @media(max-width:768px){.summary-grid{grid-template-columns:1fr 1fr;}.nav-menu{display:none;}}
    </style>
</head>
<body>

    <nav class="modern-navbar">
        <a href="#" class="nav-logo">
            <i class="fa-solid fa-money-bill-trend-up"></i> CuanFlow
        </a>

        <div class="nav-menu">
            <a href="stok_transaksi.php"> Stok & Kasir</a>
            <a href="omset_hari_ini.php" class="active">Omset Hari Ini</a>
            <a href="total_omset.php">Dompet & Omset</a>
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
        
        <div class="header-flex">
            <div>
                <h1>Ringkasan Penjualan Hari Ini</h1>
                <p style="color: #64748b; font-size: 14px;">Pantau performa penjualan dan keuntungan bersih shift ini.</p>
            </div>
            <div>
                <a href="?akhiri_shift=true" class="btn-add" style="background:#1e293b; text-decoration:none; display:inline-block;" onclick="return confirm('Akhiri Kerja Untuk Hari Ini?')">
                    <i class="fa-solid fa-power-off"></i> Akhiri Kerja
                </a>
            </div>
        </div>

        <?php
        $data_transaksi = [];
        $total_omset_kotor = 0;
        $total_keuntungan_bersih = 0;
        $total_item_terjual = 0;
        
        // Array untuk menyimpan rekap item yang terjual (untuk Grafik)
        $rekap_grafik = [];

        // Memproses hitungan dari data di database
        while ($row = mysqli_fetch_assoc($query_riwayat)) {
            $data_transaksi[] = $row;
            if ($row['tipe'] == 'jual') {
                $total_omset_kotor += $row['total_harga'];
                $total_item_terjual += $row['qty'];
                
                // Menghitung profit: (Harga Jual - Harga Modal) x Jumlah Barang Terjual
                $profit_per_transaksi = ($row['harga_jual'] - $row['harga_beli']) * $row['qty'];
                $total_keuntungan_bersih += $profit_per_transaksi;

                // Memasukkan data ke array rekap grafik
                $nama_barang = $row['nama_barang'];
                if (!isset($rekap_grafik[$nama_barang])) {
                    $rekap_grafik[$nama_barang] = 0;
                }
                $rekap_grafik[$nama_barang] += $row['qty'];
            }
        }

        // Siapkan data JSON untuk dibaca oleh Chart.js (Javascript)
        $label_grafik = json_encode(array_keys($rekap_grafik));
        $data_grafik = json_encode(array_values($rekap_grafik));
        ?>

        <div class="summary-grid">
            <div class="card-stat">
                <div class="icon-circle" style="background:#e0e7ff; color:#4f46e5;"><i class="fa-solid fa-sack-dollar"></i></div>
                <div>
                    <small style="color:#64748b">Omset Kotor</small>
                    <p style="font-weight:700; font-size:18px">Rp <?php echo number_format($total_omset_kotor, 0, ',', '.'); ?></p>
                </div>
            </div>
            <div class="card-stat">
                <div class="icon-circle" style="background:#dcfce7; color:#10b981;"><i class="fa-solid fa-vault"></i></div>
                <div>
                    <small style="color:#64748b">Keuntungan Bersih</small>
                    <p style="font-weight:700; font-size:18px">Rp <?php echo number_format($total_keuntungan_bersih, 0, ',', '.'); ?></p>
                </div>
            </div>
            <div class="card-stat" style="background:#4f46e5; color:white; border:none;">
                <div class="icon-circle" style="background:rgba(255,255,255,0.2);"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div>
                    <small style="opacity:0.8">Total Item Terjual</small>
                    <p style="font-weight:700; font-size:18px"><?php echo $total_item_terjual; ?> Pcs</p>
                </div>
            </div>
        </div>

        <div class="chart-card">
            <h3 style="margin-bottom: 20px; font-size: 16px; color:#1e293b;"><i class="fa-solid fa-chart-simple"></i> Grafik Item Terjual Shift Ini</h3>
            <?php if(empty($rekap_grafik)): ?>
                <p style="text-align:center; color:#94a3b8; padding:20px;">Belum ada data barang terjual untuk ditampilkan di grafik.</p>
            <?php else: ?>
                <canvas id="itemChart" height="80"></canvas>
            <?php endif; ?>
        </div>

        <div class="table-card">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Penjualan</h3>
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Tipe</th>
                        <th>Item Terjual</th>
                        <th>Nominal Omset</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_transaksi)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;"><i class="fa-solid fa-folder-open" style="font-size:24px; margin-bottom:10px;"></i><br>Belum ada penjualan di shift ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_transaksi as $trx): 
                            if ($trx['tipe'] !== 'jual') continue; 
                        ?>
                        <tr>
                            <td style="color:#64748b"><?php echo date('H:i', strtotime($trx['tanggal'])); ?></td>
                            <td>
                                <span class="badge badge-in">Jual Barang</span>
                            </td>
                            <td>
                                <b><?php echo htmlspecialchars($trx['nama_barang']); ?></b>
                                <br><small style='color:#94a3b8'>Qty: <?php echo $trx['qty']; ?> Pcs</small>
                            </td>
                            <td class="text-green">
                                + Rp <?php echo number_format($trx['total_harga'], 0, ',', '.'); ?>
                            </td>
                            <td>
                                <a href="?hapus_id=<?php echo $trx['id_transaksi']; ?>" class="btn-del" onclick="return confirm('Batalkan transaksi ini? Stok akan dikembalikan ke etalase.')" title="Batalkan Transaksi">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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
                        // Update logika is_new
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
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.querySelector('#realtime-clock span').textContent = `${hours}:${minutes}:${seconds}`;
        }
        setInterval(updateClock, 1000);
        updateClock(); 

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
    </script>

    <?php if(!empty($rekap_grafik)): ?>
    <script>
        const ctx = document.getElementById('itemChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo $label_grafik; ?>, // Nama barang otomatis
                datasets: [{
                    label: 'Jumlah Terjual (Pcs)',
                    data: <?php echo $data_grafik; ?>, // Jumlah qty otomatis
                    backgroundColor: 'rgba(212,160,23,0.5)',
                    borderColor: '#d4a017',
                    borderWidth: 1,
                    borderRadius: 6,
                    barThickness: 40 // Lebar batang
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false } // Sembunyikan tulisan legend di atas
                },
                scales: {
                    x: { ticks: { color:'#7a7a8e',font:{family:'Plus Jakarta Sans',size:11}}, grid:{color:'#242432'}},
                    y: { beginAtZero:true, ticks:{stepSize:1,color:'#7a7a8e',font:{family:'Plus Jakarta Sans',size:11}}, grid:{color:'#242432'}}
                }
            }
        });
    </script>
    <?php endif; ?>

    <a href="pengaduan.php" class="btn-floating-help" title="Laporkan Kendala">
        <i class="fa-solid fa-headset"></i>
    </a>
</body>
</html>