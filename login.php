<?php
session_start();
require 'config/database.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') { header("Location: admin/dashboard.php"); }
    else { header("Location: pedagang/stok_transaksi.php"); }
    exit;
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id']  = $row['id_user'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role'];

            $id_user_login = $row['id_user'];
            $waktu_sekarang = date('Y-m-d H:i:s');
            mysqli_query($conn, "INSERT INTO riwayat_login (id_user, waktu_login) VALUES ('$id_user_login', '$waktu_sekarang')");

            if ($row['role'] == 'admin') { header("Location: admin/dashboard.php"); }
            else { header("Location: pedagang/stok_transaksi.php"); }
            exit;
        } else {
            $error = "Password salah atau akun Anda telah dinonaktifkan.";
        }
    } else {
        $error = "Username tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — CuanFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg:        #09090f;
            --surface:   #111118;
            --card:      #16161f;
            --border:    #242432;
            --accent:    #d4a017;
            --accent-lt: #f0c040;
            --accent-dim:rgba(212,160,23,0.1);
            --cream:     #f0ece3;
            --muted:     #7a7a8e;
            --white:     #fafaf8;
        }

        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            background: var(--bg);
            color: var(--cream);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* Grain Effect */
        body::before {
            content:''; position:fixed; inset:0;
            background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events:none; z-index:9999; opacity:0.6;
        }

        /* LEFT PANEL */
        .left-panel {
            width: 42%;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            position: relative;
            overflow: hidden;
        }
        .left-panel::before {
            content:'';
            position:absolute;
            width:400px; height:400px;
            background: radial-gradient(circle, var(--accent-dim) 0%, transparent 70%);
            bottom:-80px; right:-80px;
            pointer-events:none;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 22px; font-weight: 600;
            color: var(--accent);
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }
        .brand-icon {
            width: 34px; height: 34px;
            background: var(--accent-dim);
            border: 1px solid rgba(212,160,23,0.2);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
        }

        .panel-content { flex: 1; display:flex; flex-direction:column; justify-content:center; }

        .panel-tag {
            font-size: 11px; font-weight: 600;
            letter-spacing: 0.15em; text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        .panel-tag::before {
            content:''; width:24px; height:1px; background:var(--accent);
        }

        .panel-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 38px; line-height: 1.2;
            color: var(--white);
            margin-bottom: 18px;
        }
        .panel-content h2 em { font-style:italic; color:var(--accent); }

        .panel-content p {
            font-size: 14px; color: var(--muted);
            line-height: 1.7; max-width: 320px;
        }

        /* --- DESIGN STYLE TOMBOL BAWAH (Sesuai Request Gambar) --- */
        .panel-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 10;
        }

        .panel-register {
            display: inline-flex; align-items: center; gap: 10px;
            font-size: 13px; font-weight: 500;
            padding: 10px 18px; border-radius: 8px;
            text-decoration: none; transition: all 0.25s ease;
            border: 1px solid var(--border);
            color: var(--muted);
            width: fit-content;
            background: transparent;
        }

        /* Hover — gold accent, konsisten dengan tema */
        .panel-register:hover {
            border-color: var(--accent);
            color: var(--white);
            background: var(--accent-dim);
            box-shadow: 0 4px 16px rgba(212,160,23,0.15);
            transform: translateY(-1px);
        }

        /* RIGHT PANEL — FORM */
        .right-panel {
            flex: 1;
            display: flex; align-items: center; justify-content: center;
            padding: 48px;
        }

        .form-box {
            width: 100%; max-width: 380px;
            animation: fadeUp 0.7s cubic-bezier(0.22,1,0.36,1) both;
        }

        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 26px; color: var(--white);
            margin-bottom: 6px;
        }
        .form-sub { font-size: 13px; color: var(--muted); margin-bottom: 32px; }

        .alert-err {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 22px;
            display: flex; align-items: center; gap: 9px;
        }

        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 12px; font-weight: 600;
            color: var(--muted); letter-spacing: 0.06em; text-transform: uppercase;
            margin-bottom: 8px;
        }
        .field input {
            width: 100%;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px; font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--white);
            outline: none;
            transition: all 0.25s;
        }
        .field input::placeholder { color: #3a3a50; }
        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-dim);
            background: var(--surface);
        }

        .forgot-link {
            display: block; text-align: right;
            font-size: 12px; color: var(--muted);
            text-decoration: none; margin-top: -10px; margin-bottom: 24px;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: var(--accent); }

        .btn-submit {
            width: 100%;
            background: var(--accent);
            color: var(--bg);
            border: none; border-radius: 8px;
            padding: 13px;
            font-size: 14px; font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 9px;
            transition: all 0.25s;
            letter-spacing: 0.02em;
            box-shadow: 0 4px 16px rgba(212,160,23,0.2);
        }
        .btn-submit:hover {
            background: var(--accent-lt);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(212,160,23,0.3);
        }

        .back-link {
            display: flex; align-items: center; justify-content: center; gap: 7px;
            margin-top: 26px;
            font-size: 13px; color: var(--muted);
            text-decoration: none; transition: color 0.2s;
        }
        .back-link:hover { color: var(--cream); }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(20px) }
            to   { opacity:1; transform:translateY(0) }
        }

        @media(max-width:768px) {
            body { flex-direction:column; overflow:auto; }
            .left-panel { width:100%; padding:32px; min-height:auto; gap: 24px; }
            .panel-content h2 { font-size:28px; margin-top: 40px; }
            .right-panel { padding:32px 24px; }
        }
    </style>
</head>
<body>

<div class="left-panel">
    <a href="index.php" class="brand">
        <div class="brand-icon">
            <i class="fa-solid fa-money-bill-trend-up"></i>
        </div>
        CuanFlow
    </a>

    <div class="panel-content">
        <div class="panel-tag">Portal Pedagang</div>
        <h2>Selamat Datang <em>Kembali</em></h2>
        <p>Masuk ke dasbor kasirmu dan mulai kelola transaksi serta stok toko hari ini.</p>
    </div>

    <div class="panel-actions">
        <a href="register.php" class="panel-register">
            <i class="fa-solid fa-user-plus"></i> Belum punya akun?
        </a>
    </div>
</div>

<div class="right-panel">
    <div class="form-box">
        <h2 class="form-title">Masuk ke Akun</h2>
        <p class="form-sub">Isi kredensial Anda di bawah ini</p>

        <?php if($error): ?>
            <div class="alert-err">
                <i class="fa-solid fa-circle-xmark"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required autocomplete="off">
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <a href="lupa_password.php" class="forgot-link">Lupa password?</a>
            <button type="submit" class="btn-submit">
                Masuk ke Sistem <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <a href="index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
        </a>
    </div>
</div>

</body>
</html>