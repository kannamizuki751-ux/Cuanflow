<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }

$admin_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';

if (isset($_POST['submit_balasan'])) {
    $id_pengaduan = (int)$_POST['id_pengaduan'];
    $balasan_admin = mysqli_real_escape_string($conn, $_POST['balasan_admin']);
    $status_baru   = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE pengaduan SET status = '$status_baru', balasan_admin = '$balasan_admin', is_read_user = 0 WHERE id_pengaduan = $id_pengaduan");
    header("Location: kelola_pengaduan.php?pesan=diperbarui"); exit;
}

if (isset($_POST['hapus_semua_pengaduan'])) {
    $result_foto = mysqli_query($conn, "SELECT foto FROM pengaduan WHERE foto IS NOT NULL AND foto != ''");
    while ($row_foto = mysqli_fetch_assoc($result_foto)) {
        $fp = "../uploads/pengaduan/" . $row_foto['foto'];
        if (file_exists($fp)) unlink($fp);
    }
    mysqli_query($conn, "DELETE FROM pengaduan");
    header("Location: kelola_pengaduan.php?pesan=dibersihkan"); exit;
}

$query = mysqli_query($conn, "
    SELECT p.*, u.username FROM pengaduan p
    JOIN users u ON p.id_user = u.id_user
    ORDER BY p.tanggal DESC
");
$total = $query ? mysqli_num_rows($query) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengaduan — CuanFlow Admin</title>
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
        <a href="laporan_aktivitas.php"><i class="fa-solid fa-clock-rotate-left"></i> Laporan Aktivitas</a>
        <a href="kelola_pengaduan.php" class="active"><i class="fa-solid fa-headset"></i> Kelola Pengaduan</a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="user-chip-icon"><i class="fa-solid fa-user-tie"></i></div>
            <div class="user-chip-info">
                <small>Admin</small>
                <strong><?php echo htmlspecialchars($admin_username); ?></strong>
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
            <h1>Kelola Pengaduan</h1>
            <p>Pantau dan tangani kendala yang dilaporkan oleh pedagang/kasir.</p>
        </div>
        <?php if($total > 0): ?>
            <form method="POST" onsubmit="return confirm('PERINGATAN: Seluruh data pengaduan dan file lampiran akan dihapus permanen. Yakin?')">
                <button type="submit" name="hapus_semua_pengaduan" class="btn-clear">
                    <i class="fa-solid fa-trash-can"></i> Bersihkan Semua Log
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'diperbarui'): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Status dan tanggapan pengaduan berhasil diperbarui.</div>
    <?php endif; ?>
    <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'dibersihkan'): ?>
        <div class="alert alert-warn"><i class="fa-solid fa-dumpster-fire"></i> Seluruh data log pengaduan dan lampiran telah dibersihkan secara permanen.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-inbox" style="color:var(--gold)"></i> Daftar Pengaduan Masuk
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Waktu & Pengirim</th>
                        <th>Subjek</th>
                        <th style="width:28%">Pesan Singkat</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($total > 0): ?>
                        <?php mysqli_data_seek($query, 0); ?>
                        <?php while($row = mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td>
                                <span style="font-weight:600; color:var(--cream)">@<?php echo htmlspecialchars($row['username']); ?></span>
                                <small style="color:var(--muted); display:block; font-size:11px; margin-top:2px"><?php echo date('d M Y, H:i', strtotime($row['tanggal'])); ?></small>
                            </td>
                            <td style="font-weight:500; color:var(--cream)"><?php echo htmlspecialchars($row['subjek']); ?></td>
                            <td style="color:var(--muted); font-size:12px; line-height:1.5">
                                <?php echo nl2br(htmlspecialchars(substr($row['pesan'], 0, 55))) . (strlen($row['pesan']) > 55 ? '…' : ''); ?>
                            </td>
                            <td>
                                <?php $st = $row['status']; ?>
                                <span class="badge badge-<?php echo $st; ?>">
                                    <?php echo ucfirst($st); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-row">
                                    <button class="btn-icon detail" onclick="bukaModal('detail',<?php echo $row['id_pengaduan']; ?>)" title="Detail">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <?php if($st != 'selesai' && $st != 'ditolak'): ?>
                                        <button class="btn-icon reply" onclick="bukaModal('balas',<?php echo $row['id_pengaduan']; ?>)" title="Tanggapi">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size:11px; color:var(--muted); display:flex; align-items:center; gap:4px">
                                            <i class="fa-solid fa-check-double"></i> Selesai
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- MODAL DETAIL -->
                        <div id="modal-detail-<?php echo $row['id_pengaduan']; ?>" class="modal">
                            <div class="modal-box">
                                <button class="close-modal" onclick="tutupModal('detail',<?php echo $row['id_pengaduan']; ?>)"><i class="fa-solid fa-xmark"></i></button>
                                <div class="modal-title"><i class="fa-solid fa-circle-info" style="color:var(--blue)"></i> Detail Pengaduan</div>
                                <div class="detail-row"><strong>Pengirim</strong><p>@<?php echo htmlspecialchars($row['username']); ?> — <?php echo date('d M Y, H:i', strtotime($row['tanggal'])); ?></p></div>
                                <div class="detail-row"><strong>Subjek</strong><p><?php echo htmlspecialchars($row['subjek']); ?></p></div>
                                <div class="detail-row"><strong>Pesan Lengkap</strong><p><?php echo nl2br(htmlspecialchars($row['pesan'])); ?></p></div>
                                <?php if(!empty($row['balasan_admin'])): ?>
                                    <div class="admin-reply-box">
                                        <strong>Tanggapan Admin</strong>
                                        <p><?php echo nl2br(htmlspecialchars($row['balasan_admin'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-row" style="margin-top:14px"><strong>Lampiran Foto</strong>
                                    <?php if(!empty($row['foto'])): ?>
                                        <img src="../uploads/pengaduan/<?php echo htmlspecialchars($row['foto']); ?>" alt="Bukti" class="foto-bukti">
                                        <a href="../uploads/pengaduan/<?php echo htmlspecialchars($row['foto']); ?>" target="_blank" class="link-blue">Buka di tab baru</a>
                                    <?php else: ?>
                                        <div class="no-foto"><i class="fa-solid fa-image-slash"></i> Tidak ada lampiran foto.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- MODAL BALAS -->
                        <div id="modal-balas-<?php echo $row['id_pengaduan']; ?>" class="modal">
                            <div class="modal-box">
                                <button class="close-modal" onclick="tutupModal('balas',<?php echo $row['id_pengaduan']; ?>)"><i class="fa-solid fa-xmark"></i></button>
                                <div class="modal-title"><i class="fa-solid fa-pen-to-square" style="color:var(--amber)"></i> Tanggapi Pengaduan</div>
                                <div class="detail-row"><strong>Ke</strong><p>@<?php echo htmlspecialchars($row['username']); ?></p></div>
                                <div class="detail-row"><strong>Masalah</strong><p><?php echo htmlspecialchars($row['subjek']); ?></p></div>
                                <div class="modal-divider"></div>
                                <form method="POST">
                                    <input type="hidden" name="id_pengaduan" value="<?php echo $row['id_pengaduan']; ?>">
                                    <div class="form-group" style="margin-bottom:14px">
                                        <label style="display:block;font-size:11px;font-weight:600;color:var(--muted);letter-spacing:0.07em;text-transform:uppercase;margin-bottom:7px">Update Status</label>
                                        <select name="status" style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:'Plus Jakarta Sans',sans-serif;color:var(--white);outline:none;cursor:pointer;" required>
                                            <option value="pending"   <?php echo($row['status']=='pending')   ?'selected':''; ?>>Pending</option>
                                            <option value="diproses"  <?php echo($row['status']=='diproses')  ?'selected':''; ?>>Sedang Diproses</option>
                                            <option value="selesai"   <?php echo($row['status']=='selesai')   ?'selected':''; ?>>Selesai</option>
                                            <option value="ditolak"   <?php echo($row['status']=='ditolak')   ?'selected':''; ?>>Ditolak</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom:14px">
                                        <label style="display:block;font-size:11px;font-weight:600;color:var(--muted);letter-spacing:0.07em;text-transform:uppercase;margin-bottom:7px">Balasan / Komentar</label>
                                        <textarea name="balasan_admin" style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:'Plus Jakarta Sans',sans-serif;color:var(--white);outline:none;resize:vertical;min-height:100px;" placeholder="Tulis balasan atau update progres..." required><?php echo htmlspecialchars($row['balasan_admin'] ?? ''); ?></textarea>
                                    </div>
                                    <button type="submit" name="submit_balasan" style="width:100%;background:var(--gold);color:var(--bg);border:none;border-radius:8px;padding:11px;font-size:13px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.2s;box-shadow:0 4px 14px rgba(212,160,23,0.2);" onmouseover="this.style.background='var(--gold-lt)'" onmouseout="this.style.background='var(--gold)'">
                                        <i class="fa-solid fa-save"></i> Simpan Tanggapan
                                    </button>
                                </form>
                            </div>
                        </div>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <p>Belum ada pengaduan masuk saat ini.</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    function bukaModal(tipe, id) { document.getElementById('modal-' + tipe + '-' + id).style.display = 'flex'; }
    function tutupModal(tipe, id){ document.getElementById('modal-' + tipe + '-' + id).style.display = 'none'; }
    window.onclick = e => { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; };
</script>
</body>
</html>