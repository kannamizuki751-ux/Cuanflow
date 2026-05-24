<?php
session_start();
require 'config/database.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') { header("Location: admin/dashboard.php"); }
    else { header("Location: pedagang/stok_transaksi.php"); }
    exit;
}

$error = ''; $success = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Password dan konfirmasi tidak cocok.";
    } else {
        $cek = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Username sudah terdaftar. Silakan pilih yang lain.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $query  = "INSERT INTO users (username, password_hash, role) VALUES ('$username', '$hashed', 'pedagang')";
            if (mysqli_query($conn, $query)) {
                $success = "Akun berhasil dibuat! Silakan masuk.";
            } else {
                $error = "Terjadi kesalahan: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — CuanFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg:        #09090f;
            --surface:   #111118;
            --card:      #16161f;
            --border:    #242432;
            --gold:      #d4a017;
            --gold-lt:   #f0c040;
            --gold-dim:  rgba(212,160,23,0.1);
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
            display: flex; overflow: hidden;
        }
        body::before {
            content:''; position:fixed; inset:0;
            background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events:none; z-index:9999; opacity:0.6;
        }

        /* LEFT */
        .left-panel {
            width: 42%;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            position: relative; overflow: hidden;
        }
        .left-panel::before {
            content:''; position:absolute;
            width:400px; height:400px;
            background: radial-gradient(circle, var(--gold-dim) 0%, transparent 70%);
            bottom:-100px; right:-100px;
            pointer-events:none;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 22px; font-weight: 600;
            color: var(--gold); text-decoration: none;
            display: flex; align-items: center; gap: 10px;
        }
        .brand-icon {
            width: 34px; height: 34px;
            background: var(--gold-dim);
            border: 1px solid rgba(212,160,23,0.2);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
        }

        .panel-content { flex:1; display:flex; flex-direction:column; justify-content:center; }

        .panel-tag {
            font-size: 11px; font-weight: 600;
            letter-spacing: 0.15em; text-transform: uppercase;
            color: var(--gold); margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        .panel-tag::before { content:''; width:24px; height:1px; background:var(--gold); }

        .panel-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 38px; line-height: 1.2; color: var(--white); margin-bottom: 18px;
        }
        .panel-content h2 em { font-style:italic; color:var(--gold); }
        .panel-content p { font-size:14px; color:var(--muted); line-height:1.7; max-width:320px; }

        .steps {
            display: flex; flex-direction: column; gap: 16px;
            margin-top: 32px;
        }
        .step {
            display: flex; align-items: center; gap: 14px;
        }
        .step-num {
            width: 28px; height: 28px;
            background: var(--gold-dim);
            border: 1px solid rgba(212,160,23,0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; color: var(--gold);
            flex-shrink: 0;
        }
        .step p { font-size:13px; color:var(--muted); }

        /* RIGHT */
        .right-panel {
            flex:1;
            display:flex; align-items:center; justify-content:center;
            padding: 48px;
        }

        .form-box {
            width:100%; max-width:380px;
            animation: fadeUp 0.7s cubic-bezier(0.22,1,0.36,1) both;
        }
        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 26px; color: var(--white); margin-bottom: 6px;
        }
        .form-sub { font-size:13px; color:var(--muted); margin-bottom:32px; }

        .alert-msg {
            padding: 12px 16px; border-radius: 8px;
            font-size: 13px; margin-bottom: 22px;
            display: flex; align-items: center; gap: 9px;
        }
        .alert-err {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #f87171;
        }
        .alert-ok {
            background: var(--gold-dim);
            border: 1px solid rgba(212,160,23,0.25);
            color: var(--gold-lt);
        }

        .field { margin-bottom: 18px; }
        .field label {
            display:block; font-size:12px; font-weight:600;
            color:var(--muted); letter-spacing:0.06em; text-transform:uppercase;
            margin-bottom:8px;
        }
        .field input {
            width:100%;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            font-size:14px; font-family:'Plus Jakarta Sans',sans-serif;
            color: var(--white); outline:none; transition:all 0.25s;
        }
        .field input::placeholder { color:#3a3a50; }
        .field input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-dim);
            background: var(--surface);
        }

        .btn-submit {
            width:100%;
            background: var(--gold);
            color: var(--bg);
            border:none; border-radius:8px;
            padding:13px;
            font-size:14px; font-weight:700;
            font-family:'Plus Jakarta Sans',sans-serif;
            cursor:pointer;
            display:flex; align-items:center; justify-content:center; gap:9px;
            transition:all 0.25s;
            letter-spacing:0.02em;
            box-shadow: 0 4px 16px rgba(212,160,23,0.2);
        }
        .btn-submit:hover {
            background: var(--gold-lt);
            transform:translateY(-2px);
            box-shadow:0 8px 24px rgba(212,160,23,0.3);
        }

        .login-link {
            display:block; text-align:center;
            margin-top:26px; font-size:13px; color:var(--muted);
        }
        .login-link a { color:var(--gold); font-weight:600; text-decoration:none; }
        .login-link a:hover { text-decoration:underline; }

        /* Tombol sudah punya akun — gold hover, konsisten */
        .btn-login-link {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 12px; font-weight: 600;
            padding: 9px 18px; border-radius: 6px;
            text-decoration: none; transition: all 0.25s;
            border: 1px solid var(--border);
            color: var(--muted);
            width: fit-content;
            background: transparent;
        }
        .btn-login-link:hover {
            border-color: var(--gold);
            color: var(--gold);
            background: var(--gold-dim);
        }
            from { opacity:0; transform:translateY(20px) }
            to   { opacity:1; transform:translateY(0) }
        }

        @media(max-width:768px) {
            body { flex-direction:column; overflow:auto; }
            .left-panel { width:100%; padding:32px; }
            .panel-content h2 { font-size:28px; }
            .steps { display:none; }
            .right-panel { padding:32px 24px; }
        }
    </style>
</head>
<body>

<div class="left-panel">
    <a href="index.php" class="brand">
        <div class="brand-icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
        CuanFlow
    </a>

    <div class="panel-content">
        <div class="panel-tag">Buka Toko Baru</div>
        <h2>Mulai Perjalanan <em>Bisnismu</em></h2>
        <p>Daftar sekarang dan kelola toko, stok, serta omset harianmu dalam satu platform.</p>

        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <p>Buat akun dengan username unik</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <p>Masuk dan tambahkan produk tokomu</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <p>Mulai catat transaksi dan pantau profit</p>
            </div>
        </div>
    </div>

    <a href="login.php" class="btn-login-link">
        <i class="fa-solid fa-right-to-bracket"></i> Sudah punya akun?
    </a>
</div>

<div class="right-panel">
    <div class="form-box">
        <h2 class="form-title">Buat Akun</h2>
        <p class="form-sub">Isi detail di bawah untuk mendaftar</p>

        <?php if($error): ?>
            <div class="alert-msg alert-err">
                <i class="fa-solid fa-circle-xmark"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert-msg alert-ok">
                <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" placeholder="Buat username unik" required autocomplete="off">
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="Minimal 8 karakter" required>
            </div>
            <div class="field" style="margin-bottom:24px;">
                <label>Konfirmasi Password</label>
                <input type="password" name="confirm_password" placeholder="Ulangi password" required>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-user-plus"></i> Daftar Sekarang
            </button>
        </form>

        <p class="login-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </p>
    </div>
</div>

</body>
</html> 