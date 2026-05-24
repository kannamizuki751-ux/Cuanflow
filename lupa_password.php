<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — CuanFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg:       #09090f;
            --surface:  #111118;
            --card:     #16161f;
            --border:   #242432;
            --gold:     #d4a017;
            --gold-lt:  #f0c040;
            --gold-dim: rgba(212,160,23,0.1);
            --wa:       #25D366;
            --wa-dim:   rgba(37,211,102,0.1);
            --cream:    #f0ece3;
            --muted:    #7a7a8e;
            --white:    #fafaf8;
        }
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: var(--bg);
            color: var(--cream);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
        }
        body::before {
            content:''; position:fixed; inset:0;
            background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events:none; z-index:9999; opacity:0.6;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 48px 40px;
            width: 100%; max-width: 420px;
            position: relative; overflow: hidden;
            animation: fadeUp 0.7s cubic-bezier(0.22,1,0.36,1) both;
            box-shadow: 0 30px 60px rgba(0,0,0,0.4);
        }
        .card::before {
            content:''; position:absolute;
            width:300px; height:300px;
            background: radial-gradient(circle, var(--gold-dim) 0%, transparent 70%);
            top:-100px; right:-80px;
            pointer-events:none;
        }

        .card-head { text-align:center; margin-bottom:36px; position:relative; }
        
        .icon-wrap {
            width: 64px; height: 64px;
            background: var(--gold-dim);
            border: 1px solid rgba(212,160,23,0.25);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; color: var(--gold);
            margin: 0 auto 20px;
        }

        .card-head h2 {
            font-family: 'Playfair Display', serif;
            font-size: 24px; color: var(--white); margin-bottom: 10px;
        }
        .card-head p {
            font-size: 13px; color: var(--muted);
            line-height: 1.7;
        }

        .field { margin-bottom: 22px; }
        .field label {
            display:block; font-size:12px; font-weight:600;
            color:var(--muted); letter-spacing:0.06em; text-transform:uppercase;
            margin-bottom:8px;
        }
        .field input {
            width:100%;
            background: var(--surface);
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
            background: var(--card);
        }

        .btn-wa {
            width:100%;
            background: var(--wa);
            color: #fff;
            border:none; border-radius:8px;
            padding: 13px;
            font-size:14px; font-weight:700;
            font-family:'Plus Jakarta Sans',sans-serif;
            cursor:pointer;
            display:flex; align-items:center; justify-content:center; gap:9px;
            transition:all 0.25s;
            letter-spacing:0.02em;
            text-decoration:none;
            box-shadow: 0 4px 16px rgba(37,211,102,0.2);
            margin-bottom: 12px;
        }
        .btn-wa:hover {
            background: #20b559;
            transform:translateY(-2px);
            box-shadow:0 8px 24px rgba(37,211,102,0.3);
        }

        .note {
            background: rgba(212,160,23,0.06);
            border: 1px solid rgba(212,160,23,0.15);
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 12px; color: var(--muted);
            line-height: 1.6; margin-bottom: 24px;
            display: flex; gap: 10px; align-items: flex-start;
        }
        .note i { color:var(--gold); margin-top:1px; flex-shrink:0; }

        .back-link {
            display:flex; align-items:center; justify-content:center; gap:7px;
            font-size:13px; color:var(--muted);
            text-decoration:none; transition:color 0.2s;
        }
        .back-link:hover { color:var(--cream); }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(20px) }
            to   { opacity:1; transform:translateY(0) }
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-head">
        <div class="icon-wrap"><i class="fa-solid fa-lock-open"></i></div>
        <h2>Lupa Password?</h2>
        <p>Masukkan username Anda, lalu kami akan bantu kirim permintaan reset ke Admin melalui WhatsApp.</p>
    </div>

    <div class="field">
        <label>Username Anda</label>
        <input type="text" id="username_kasir" placeholder="Contoh: Kaiz">
    </div>

    <div class="note">
        <i class="fa-solid fa-circle-info"></i>
        <span>Setelah menekan tombol di bawah, WhatsApp akan terbuka dengan pesan otomatis ke Admin. Pastikan Admin merespons sebelum Anda bisa masuk kembali.</span>
    </div>

    <button onclick="hubungiAdmin()" class="btn-wa">
        <i class="fa-brands fa-whatsapp"></i> Hubungi Admin via WhatsApp
    </button>

    <a href="login.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Login
    </a>
</div>

<script>
    function hubungiAdmin() {
        var username = document.getElementById('username_kasir').value.trim();
        if (!username) {
            alert('Masukkan username Anda terlebih dahulu.');
            return;
        }
        var nomorAdmin = "6285123298876";
        var pesan = "Halo Admin CuanFlow, saya lupa password untuk akun dengan Username: *" + username + "*. Mohon bantu reset password saya. Terima kasih!";
        window.open("https://wa.me/" + nomorAdmin + "?text=" + encodeURIComponent(pesan), '_blank');
    }
</script>

</body>
</html>