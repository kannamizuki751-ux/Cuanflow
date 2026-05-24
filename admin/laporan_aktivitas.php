<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }

if (isset($_POST['hapus_semua_aktivitas'])) {
    mysqli_query($conn, "DELETE FROM log_aktivitas");
    header("Location: laporan_aktivitas.php?pesan=dibersihkan"); exit;
}

$query_logs = mysqli_query($conn, "
    SELECT log_aktivitas.*, users.username, users.role
    FROM log_aktivitas
    LEFT JOIN users ON log_aktivitas.id_user = users.id_user
    ORDER BY log_aktivitas.waktu DESC
");
$total_logs = $query_logs ? mysqli_num_rows($query_logs) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Aktivitas — CuanFlow Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <a href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
        <a href="manajemen_user.php"><i class="fa-solid fa-users"></i> Kelola Pedagang</a>
        <a href="laporan_aktivitas.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> Laporan Aktivitas</a>
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
    <div class="page-header-row">
        <div class="page-header" style="margin-bottom:0">
            <h1>Laporan Aktivitas Sistem</h1>
            <p>Pantau semua jejak tindakan yang dilakukan admin dan pedagang.</p>
        </div>
        <?php if($total_logs > 0): ?>
            <form method="POST" onsubmit="return confirm('PERINGATAN: Tindakan ini akan menghapus SELURUH catatan aktivitas secara permanen. Yakin lanjutkan?')">
                <button type="submit" name="hapus_semua_aktivitas" class="btn-clear">
                    <i class="fa-solid fa-trash-can"></i> Bersihkan Semua Log
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'dibersihkan'): ?>
        <div class="alert alert-warn"><i class="fa-solid fa-dumpster-fire"></i> Seluruh log aktivitas telah dibersihkan secara permanen.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-list-check" style="color:var(--gold)"></i> Riwayat Aktivitas
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Hak Akses</th>
                        <th>Detail Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($total_logs > 0): ?>
                        <?php mysqli_data_seek($query_logs, 0); ?>
                        <?php while($log = mysqli_fetch_assoc($query_logs)): ?>
                        <tr>
                            <td class="log-time">
                                <strong><?php echo date('d M Y', strtotime($log['waktu'])); ?></strong>
                                <small><?php echo date('H:i:s', strtotime($log['waktu'])); ?> WIB</small>
                            </td>
                            <td style="font-weight:500; color:var(--cream)">
                                <i class="fa-solid fa-circle-user" style="color:var(--muted); margin-right:6px"></i>
                                <?php echo $log['username'] ? htmlspecialchars($log['username']) : '<i style="color:var(--muted)">Dihapus</i>'; ?>
                            </td>
                            <td>
                                <?php if($log['role']): ?>
                                    <span class="badge badge-<?php echo $log['role']; ?>"><?php echo $log['role']; ?></span>
                                <?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?>
                            </td>
                            <td style="color:var(--cream); font-weight:500"><?php echo htmlspecialchars($log['aktivitas']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4">
                            <div class="empty-state">
                                <i class="fa-solid fa-clipboard-list"></i>
                                <p>Belum ada catatan aktivitas di sistem.</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>