<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }

$pesan_sukses = ""; $pesan_error = "";
if (isset($_SESSION['flash_msg'])) { $pesan_sukses = $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); }

if (isset($_POST['tambah_user'])) {
    $username_baru = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password_baru = trim($_POST['password']);
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username_baru'");
    if (mysqli_num_rows($cek) > 0) {
        $pesan_error = "Username '$username_baru' sudah dipakai. Silakan gunakan nama lain.";
    } else {
        $pw_hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $q = "INSERT INTO users (username, password_hash, role) VALUES ('$username_baru', '$pw_hash', 'pedagang')";
        if (mysqli_query($conn, $q)) {
            $pesan_sukses = "Akun pedagang '$username_baru' berhasil dibuat.";
            $id_pelaku = $_SESSION['user_id'];
            mysqli_query($conn, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_pelaku', 'Menambahkan akun pedagang baru: $username_baru')");
        } else { $pesan_error = "Terjadi kesalahan sistem: " . mysqli_error($conn); }
    }
}

$query_semua_user = mysqli_query($conn, "SELECT * FROM users ORDER BY role ASC, username ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pedagang — CuanFlow Admin</title>
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
        <a href="manajemen_user.php" class="active"><i class="fa-solid fa-users"></i> Kelola Pedagang</a>
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
        <h1>Manajemen Akun Pedagang</h1>
        <p>Tambah, edit, blokir, atau hapus akses kasir pedagang.</p>
    </div>

    <?php if($pesan_sukses): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $pesan_sukses; ?></div><?php endif; ?>
    <?php if($pesan_error): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $pesan_error; ?></div><?php endif; ?>

    <div class="content-grid">

        <!-- FORM TAMBAH -->
        <div class="card sticky-form">
            <div class="card-title">
                <i class="fa-solid fa-user-plus" style="color:var(--gold)"></i> Tambah Akun Baru
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Username Pedagang</label>
                    <input type="text" name="username" class="form-control" required autocomplete="off" placeholder="Nama akun kasir">
                </div>
                <div class="form-group">
                    <label>Password Akun</label>
                    <input type="password" name="password" class="form-control" required placeholder="Min. 8 karakter">
                </div>
                <button type="submit" name="tambah_user" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Akun
                </button>
            </form>
        </div>

        <!-- TABEL USER -->
        <div class="card">
            <div class="card-title">
                <i class="fa-solid fa-list-ul" style="color:var(--gold)"></i> Daftar Pengguna
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Hak Akses</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = mysqli_fetch_assoc($query_semua_user)): ?>
                        <tr>
                            <td style="font-weight:500; color:var(--cream)">
                                <i class="fa-solid fa-circle-user" style="color:var(--muted); margin-right:7px"></i>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </td>
                            <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo $user['role']; ?></span></td>
                            <td>
                                <?php if($user['role'] == 'admin'): ?>
                                    <span class="admin-protect"><i class="fa-solid fa-shield-halved"></i> Dilindungi</span>
                                <?php else: ?>
                                    <div class="action-row">
                                        <a href="edit_user.php?id=<?php echo $user['id_user']; ?>" class="btn-icon edit" title="Edit Akun"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <a href="blokir_user.php?id=<?php echo $user['id_user']; ?>" class="btn-icon block" title="Blokir Akses"
                                           onclick="return confirm('Blokir akses pedagang ini? Mereka tidak akan bisa login.')"><i class="fa-solid fa-lock"></i></a>
                                        <a href="unblokir_user.php?id=<?php echo $user['id_user']; ?>" class="btn-icon unblock" title="Buka Blokir"
                                           onclick="return confirm('Buka blokir pedagang ini?')"><i class="fa-solid fa-unlock-keyhole"></i></a>
                                        <a href="hapus_user.php?id=<?php echo $user['id_user']; ?>" class="btn-icon delete" title="Hapus Akun"
                                           onclick="return confirm('Hapus akun ini secara permanen?')"><i class="fa-solid fa-trash-can"></i></a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>
</body>
</html>