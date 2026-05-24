<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }

$pesan_error = "";
if (!isset($_GET['id']) || empty($_GET['id'])) { header("Location: manajemen_user.php"); exit; }

$id_target = mysqli_real_escape_string($conn, $_GET['id']);
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id_user = '$id_target' AND role = 'pedagang'");
$data_user  = mysqli_fetch_assoc($query_user);
if (!$data_user) { $_SESSION['flash_msg'] = "Data pedagang tidak ditemukan!"; header("Location: manajemen_user.php"); exit; }

if (isset($_POST['update_user'])) {
    $username_baru = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password_baru = trim($_POST['password']);
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username_baru' AND id_user != '$id_target'");
    if (mysqli_num_rows($cek) > 0) {
        $pesan_error = "Username '$username_baru' sudah dipakai oleh pengguna lain.";
    } else {
        if (!empty($password_baru)) {
            $pw_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $q = "UPDATE users SET username = '$username_baru', password_hash = '$pw_hash' WHERE id_user = '$id_target'";
        } else {
            $q = "UPDATE users SET username = '$username_baru' WHERE id_user = '$id_target'";
        }
        if (mysqli_query($conn, $q)) {
            $_SESSION['flash_msg'] = "Data akun '$username_baru' berhasil diperbarui.";
            header("Location: manajemen_user.php"); exit;
        } else { $pesan_error = "Terjadi kesalahan: " . mysqli_error($conn); }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pedagang — CuanFlow Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg:#09090f; --surface:#111118; --card:#16161f; --border:#242432;
            --gold:#d4a017; --gold-lt:#f0c040; --gold-dim:rgba(212,160,23,0.10);
            --red:#ef4444; --red-dim:rgba(239,68,68,0.10);
            --blue:#3b82f6;
            --cream:#f0ece3; --muted:#7a7a8e; --muted-lt:#3a3a50; --white:#fafaf8;
        }
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        body {
            background:var(--bg); color:var(--cream);
            font-family:'Plus Jakarta Sans',sans-serif; font-size:14px;
            min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px;
        }
        body::before {
            content:''; position:fixed; inset:0;
            background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.035'/%3E%3C/svg%3E");
            pointer-events:none; z-index:9998; opacity:0.5;
        }

        .edit-card {
            background:var(--card); border:1px solid var(--border);
            border-radius:16px; padding:40px 36px;
            width:100%; max-width:440px;
            box-shadow:0 8px 32px rgba(0,0,0,0.5);
            animation:fadeUp 0.6s cubic-bezier(0.22,1,0.36,1) both;
            position:relative; overflow:hidden;
        }
        .edit-card::before {
            content:''; position:absolute; width:260px; height:260px;
            background:radial-gradient(circle, var(--gold-dim) 0%, transparent 70%);
            top:-80px; right:-60px; pointer-events:none;
        }
        @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}

        .card-head {text-align:center; margin-bottom:28px; position:relative;}
        .head-icon {
            width:54px; height:54px; margin:0 auto 16px;
            background:var(--gold-dim); border:1px solid rgba(212,160,23,0.2);
            border-radius:12px; display:flex; align-items:center; justify-content:center;
            font-size:22px; color:var(--gold);
        }
        .card-head h1 {
            font-family:'Playfair Display',serif;
            font-size:22px; color:var(--white); margin-bottom:6px;
        }
        .card-head p {font-size:13px; color:var(--muted);}

        .gold-rule {height:1px; background:linear-gradient(90deg,transparent,rgba(212,160,23,0.35),transparent); margin-bottom:24px;}

        .alert-err {
            background:var(--red-dim); border:1px solid rgba(239,68,68,0.2);
            color:#f87171; padding:11px 14px; border-radius:8px;
            font-size:13px; margin-bottom:20px;
            display:flex; align-items:center; gap:9px;
        }

        .field {margin-bottom:16px;}
        .field label {
            display:block; font-size:11px; font-weight:600;
            color:var(--muted); letter-spacing:0.07em; text-transform:uppercase; margin-bottom:7px;
        }
        .field input {
            width:100%; background:var(--surface); border:1px solid var(--border);
            border-radius:8px; padding:10px 14px;
            font-size:14px; font-family:'Plus Jakarta Sans',sans-serif;
            color:var(--white); outline:none; transition:all 0.2s;
        }
        .field input::placeholder{color:var(--muted-lt);}
        .field input:focus{border-color:var(--gold); box-shadow:0 0 0 3px var(--gold-dim); background:var(--card);}
        .field .hint {font-size:11px; color:var(--muted); margin-top:5px; font-style:italic;}

        .btn-row {display:flex; gap:12px; margin-top:24px;}
        .btn-save {
            flex:1; background:var(--gold); color:var(--bg);
            border:none; border-radius:8px; padding:11px;
            font-size:13px; font-weight:700; font-family:'Plus Jakarta Sans',sans-serif;
            cursor:pointer; transition:all 0.2s;
            display:flex; align-items:center; justify-content:center; gap:7px;
            box-shadow:0 4px 14px rgba(212,160,23,0.2);
        }
        .btn-save:hover{background:var(--gold-lt); transform:translateY(-1px); box-shadow:0 6px 18px rgba(212,160,23,0.3);}
        .btn-back {
            background:var(--surface); color:var(--muted);
            border:1px solid var(--border); border-radius:8px; padding:11px 16px;
            font-size:13px; font-weight:500; text-decoration:none; transition:all 0.2s;
            display:flex; align-items:center; gap:7px;
        }
        .btn-back:hover{border-color:#2e2e46; color:var(--cream);}
    </style>
</head>
<body>
<div class="edit-card">
    <div class="card-head">
        <div class="head-icon"><i class="fa-solid fa-user-pen"></i></div>
        <h1>Edit Data Pedagang</h1>
        <p>Perbarui informasi akun kasir <strong style="color:var(--cream)">@<?php echo htmlspecialchars($data_user['username']); ?></strong></p>
    </div>
    <div class="gold-rule"></div>

    <?php if($pesan_error): ?>
        <div class="alert-err"><i class="fa-solid fa-circle-xmark"></i> <?php echo $pesan_error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="field">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($data_user['username']); ?>" required autocomplete="off">
        </div>
        <div class="field">
            <label>Password Baru</label>
            <input type="password" name="password" placeholder="Ketik password baru...">
            <p class="hint">Kosongkan jika tidak ingin mengubah password.</p>
        </div>
        <div class="btn-row">
            <a href="manajemen_user.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Batal</a>
            <button type="submit" name="update_user" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
        </div>
    </form>
</div>
</body>
</html>