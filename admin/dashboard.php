<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }

$query_barang = mysqli_query($conn, "SELECT COUNT(*) as total FROM stok_barang");
$total_item   = mysqli_fetch_assoc($query_barang)['total'] ?? 0;

$query_user  = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'pedagang'");
$total_kasir = mysqli_fetch_assoc($query_user)['total'] ?? 0;

$query_kunjungan = mysqli_query($conn, "
    SELECT DATE(waktu_login) as tanggal, COUNT(id_log) as total_login 
    FROM riwayat_login WHERE DATE(waktu_login) >= CURDATE() - INTERVAL 1 DAY
    GROUP BY DATE(waktu_login)
");
$hari_ini_raw = date('Y-m-d'); $kemarin_raw = date('Y-m-d', strtotime('-1 day'));
$label_kemarin = date('d M Y', strtotime($kemarin_raw));
$label_hari_ini = date('d M Y');
$data_kunjungan = [$label_kemarin => 0, $label_hari_ini => 0];
if ($query_kunjungan) {
    while ($row = mysqli_fetch_assoc($query_kunjungan)) {
        if ($row['tanggal'] == $hari_ini_raw) $data_kunjungan[$label_hari_ini] = (int)$row['total_login'];
        elseif ($row['tanggal'] == $kemarin_raw) $data_kunjungan[$label_kemarin] = (int)$row['total_login'];
    }
}
$json_labels = json_encode(array_keys($data_kunjungan));
$json_data   = json_encode(array_values($data_kunjungan));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — CuanFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="admin-theme.css">
</head>
<body>

<aside class="sidebar">
    <a href="dashboard.php" class="sidebar-brand">
        <div class="brand-icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
        CuanFlow
    </a>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Panel Admin</div>
        <a href="dashboard.php" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
        <a href="manajemen_user.php"><i class="fa-solid fa-users"></i> Kelola Pedagang</a>
        <a href="laporan_aktivitas.php"><i class="fa-solid fa-clock-rotate-left"></i> Laporan Aktivitas</a>
        <a href="kelola_pengaduan.php"><i class="fa-solid fa-headset"></i> Kelola Pengaduan</a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="user-chip-icon"><i class="fa-solid fa-user-tie"></i></div>
            <div class="user-chip-info">
                <small>Admin</small>
                <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            </div>
        </div>
        <a href="../logout.php" class="btn-logout" onclick="return confirm('Keluar dari panel Admin?')">
            <i class="fa-solid fa-power-off"></i> Keluar
        </a>
    </div>
</aside>

<main class="main-content">
    <div class="page-header">
        <h1>Ikhtisar Toko</h1>
        <p>Pantau seluruh aktivitas dan performa sistem dari pusat kendali ini.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon si-gold"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div class="stat-info">
                <small>Item di Gudang</small>
                <p><?php echo $total_item; ?> <span>Jenis</span></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-blue"><i class="fa-solid fa-user-tie"></i></div>
            <div class="stat-info">
                <small>Total Pedagang</small>
                <p><?php echo $total_kasir; ?> <span>Akun</span></p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-chart-simple" style="color:var(--gold)"></i> Total Kunjungan / Login
        </div>
        <div class="chart-wrap"><canvas id="kunjunganChart"></canvas></div>
    </div>
</main>

<script>
new Chart(document.getElementById('kunjunganChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?php echo $json_labels; ?>,
        datasets: [{
            label: 'Total Login',
            data: <?php echo $json_data; ?>,
            backgroundColor: ['rgba(212,160,23,0.18)', 'rgba(212,160,23,0.55)'],
            hoverBackgroundColor: ['rgba(212,160,23,0.35)', 'rgba(212,160,23,0.85)'],
            borderColor: ['rgba(212,160,23,0.3)', 'rgba(212,160,23,0.7)'],
            borderWidth: 1,
            borderRadius: 6,
            barPercentage: 0.55
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ctx.parsed.y + ' kunjungan' } }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, color: '#7a7a8e', font: { family: 'Plus Jakarta Sans', size: 12 } }, grid: { color: '#242432' } },
            x: { ticks: { color: '#7a7a8e', font: { family: 'Plus Jakarta Sans', size: 12 } }, grid: { display: false } }
        }
    }
});
</script>
</body>
</html>