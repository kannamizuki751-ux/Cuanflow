<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CuanFlow — Sistem Kasir & Manajemen Toko</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg:       #09090f;
            --surface:  #111118;
            --card:     #16161f;
            --border:   #242432;
            --gold:     #d4a017;
            --gold-lt:  #f0c040;
            --gold-dim: rgba(212,160,23,0.12);
            --cream:    #f0ece3;
            --muted:    #7a7a8e;
            --white:    #fafaf8;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--cream);
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }

        /* — Grain overlay — */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none; z-index: 9999; opacity: 0.6;
        }

        /* ============ NAV ============ */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 6%;
            position: sticky; top: 0; z-index: 100;
            background: rgba(9,9,15,0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 22px; font-weight: 700;
            color: var(--gold);
            text-decoration: none;
            display: flex; align-items: center; gap: 10px;
            letter-spacing: 0.02em;
        }
        .logo-icon {
            width: 36px; height: 36px;
            background: var(--gold-dim);
            border: 1px solid rgba(212,160,23,0.3);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; color: var(--gold);
        }

        .btn-login {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px; font-weight: 600;
            color: var(--gold);
            border: 1px solid rgba(212,160,23,0.4);
            padding: 9px 22px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.25s;
            background: var(--gold-dim);
            letter-spacing: 0.03em;
        }
        .btn-login:hover {
            background: var(--gold);
            color: var(--bg);
            box-shadow: 0 0 20px rgba(212,160,23,0.25);
        }

        /* ============ HERO ============ */
        .hero {
            min-height: 92vh;
            display: flex;
            align-items: center;
            padding: 80px 6%;
            gap: 60px;
            position: relative;
            overflow: hidden;
        }

        /* Decorative circles */
        .hero::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(212,160,23,0.07) 0%, transparent 70%);
            right: -100px; top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .hero::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(212,160,23,0.05) 0%, transparent 70%);
            left: 10%; bottom: 5%;
            pointer-events: none;
        }

        .hero-text {
            flex: 1;
            max-width: 560px;
            animation: fadeUp 0.9s cubic-bezier(0.22,1,0.36,1) both;
        }

        .hero-label {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 11px; font-weight: 600;
            letter-spacing: 0.15em; text-transform: uppercase;
            color: var(--gold);
            background: var(--gold-dim);
            border: 1px solid rgba(212,160,23,0.2);
            padding: 6px 14px; border-radius: 20px;
            margin-bottom: 28px;
        }
        .hero-label::before {
            content: ''; display: inline-block;
            width: 6px; height: 6px;
            background: var(--gold); border-radius: 50%;
            animation: blink 1.8s infinite;
        }

        @keyframes blink {
            0%,100% { opacity:1 } 50% { opacity:0.3 }
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 54px; line-height: 1.15;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 22px;
        }
        h1 em {
            font-style: italic;
            color: var(--gold);
        }

        .hero-desc {
            font-size: 15px; line-height: 1.75;
            color: var(--muted);
            margin-bottom: 42px;
            max-width: 480px;
        }

        .action-buttons {
            display: flex; gap: 14px; flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--gold);
            color: var(--bg);
            padding: 14px 28px;
            border-radius: 7px;
            font-weight: 700; font-size: 14px;
            text-decoration: none;
            display: flex; align-items: center; gap: 9px;
            transition: all 0.25s;
            box-shadow: 0 4px 20px rgba(212,160,23,0.3);
            letter-spacing: 0.02em;
        }
        .btn-primary:hover {
            background: var(--gold-lt);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(212,160,23,0.4);
        }

        .btn-ghost {
            background: transparent;
            color: var(--cream);
            padding: 14px 28px;
            border-radius: 7px;
            font-weight: 500; font-size: 14px;
            text-decoration: none;
            display: flex; align-items: center; gap: 9px;
            transition: all 0.25s;
            border: 1px solid var(--border);
        }
        .btn-ghost:hover {
            border-color: rgba(212,160,23,0.3);
            color: var(--gold);
            background: var(--gold-dim);
        }

        /* ============ MOCKUP ============ */
        .hero-visual {
            flex: 1;
            display: flex; justify-content: flex-end;
            animation: fadeIn 1.1s cubic-bezier(0.22,1,0.36,1) 0.2s both;
        }

        .mockup {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            width: 100%; max-width: 420px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
        }

        .mockup-top {
            display: flex; justify-content: space-between; align-items: flex-start;
            margin-bottom: 24px;
        }
        .mockup-top small {
            font-size: 11px; color: var(--muted); letter-spacing: 0.08em; text-transform: uppercase;
        }
        .mockup-top h3 {
            font-family: 'Playfair Display', serif;
            font-size: 22px; color: var(--white); margin-top: 4px;
        }
        .badge-up {
            background: rgba(16,185,129,0.12);
            color: #10b981;
            font-size: 11px; font-weight: 600;
            padding: 4px 10px; border-radius: 20px;
            border: 1px solid rgba(16,185,129,0.2);
        }

        .chart {
            display: flex; align-items: flex-end; gap: 10px;
            height: 120px; margin-bottom: 22px;
        }
        .bar {
            flex: 1; border-radius: 4px 4px 0 0;
            background: rgba(212,160,23,0.12);
            transition: all 0.5s;
        }
        .bar:nth-child(1){ height:38% }
        .bar:nth-child(2){ height:62% }
        .bar:nth-child(3){ height:48% }
        .bar:nth-child(4){ height:100%; background: var(--gold); box-shadow: 0 0 16px rgba(212,160,23,0.3); }
        .bar:nth-child(5){ height:75% }
        .bar:nth-child(6){ height:55% }

        .mock-stats {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
        }
        .mock-stat {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
        }
        .mock-stat i { font-size: 14px; margin-bottom: 6px; display: block; }
        .mock-stat span { font-size: 10px; color: var(--muted); display: block; margin-bottom: 2px; }
        .mock-stat strong { font-size: 15px; color: var(--white); font-weight: 600; }

        /* ============ DIVIDER ============ */
        .divider {
            padding: 0 6%; 
            display: flex; align-items: center; gap: 20px;
        }
        .divider-line { flex: 1; height: 1px; background: var(--border); }
        .divider-label {
            font-size: 11px; color: var(--muted); letter-spacing: 0.15em; text-transform: uppercase;
        }

        /* ============ FEATURES ============ */
        .features {
            padding: 100px 6%;
        }

        .section-head {
            text-align: center;
            max-width: 560px;
            margin: 0 auto 60px;
        }
        .section-tag {
            display: inline-block;
            font-size: 11px; font-weight: 600; letter-spacing: 0.15em; text-transform: uppercase;
            color: var(--gold); margin-bottom: 16px;
        }
        .section-head h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px; font-weight: 700; color: var(--white);
            line-height: 1.25; margin-bottom: 14px;
        }
        .section-head p { font-size: 14px; color: var(--muted); line-height: 1.7; }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3,1fr);
            gap: 22px;
            max-width: 1100px; margin: 0 auto;
        }

        .feat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 36px 28px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .feat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(212,160,23,0), transparent);
            transition: all 0.4s;
        }
        .feat-card:hover {
            border-color: rgba(212,160,23,0.25);
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .feat-card:hover::before {
            background: linear-gradient(90deg, transparent, rgba(212,160,23,0.6), transparent);
        }

        .feat-icon {
            width: 52px; height: 52px;
            background: var(--gold-dim);
            border: 1px solid rgba(212,160,23,0.2);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: var(--gold);
            margin-bottom: 22px;
        }
        .feat-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 18px; color: var(--white); margin-bottom: 12px;
        }
        .feat-card p { font-size: 13px; color: var(--muted); line-height: 1.7; }

        /* ============ FOOTER ============ */
        footer {
            border-top: 1px solid var(--border);
            padding: 40px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .footer-logo {
            font-family: 'Playfair Display', serif;
            font-size: 18px; color: var(--gold);
            display: flex; align-items: center; gap: 8px;
            text-decoration: none;
        }
        footer p { font-size: 12px; color: var(--muted); }

        /* ============ ANIMATIONS ============ */
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(24px) }
            to   { opacity:1; transform:translateY(0) }
        }
        @keyframes fadeIn {
            from { opacity:0; transform:scale(0.97) }
            to   { opacity:1; transform:scale(1) }
        }

        /* ============ RESPONSIVE ============ */
        @media(max-width:900px) {
            .features-grid { grid-template-columns: 1fr 1fr; }
        }
        @media(max-width:768px) {
            .hero { flex-direction:column; padding:50px 5%; gap:40px; }
            h1 { font-size:36px; }
            .hero-visual { justify-content:center; width:100%; }
            .features-grid { grid-template-columns:1fr; }
            footer { justify-content:center; text-align:center; }
        }
    </style>
</head>
<body>

<nav>
    <a href="index.php" class="logo">
        <div class="logo-icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
        CuanFlow
    </a>
    <a href="login.php" class="btn-login">
        <i class="fa-solid fa-right-to-bracket"></i> Masuk
    </a>
</nav>

<header class="hero">
    <div class="hero-text">
        <div class="hero-label">Point of Sale — UMKM Indonesia</div>
        <h1>Kelola Toko dengan Cara yang <em>Lebih Cerdas</em></h1>
        <p class="hero-desc">Aplikasi kasir dan manajemen stok berbasis web untuk pedagang modern. Pantau transaksi, stok, dan omset harian — semua dalam satu dasbor yang bersih.</p>
        <div class="action-buttons">
            <a href="register.php" class="btn-primary">
                <i class="fa-solid fa-rocket"></i> Mulai Gratis
            </a>
            <a href="#fitur" class="btn-ghost">
                Lihat Fitur <i class="fa-solid fa-arrow-down"></i>
            </a>
        </div>
    </div>

    <div class="hero-visual">
        <div class="mockup">
            <div class="mockup-top">
                <div>
                    <small>Omset Tertinggi Bulan Ini</small>
                    <h3>Rp 8.000.000</h3>
                </div>
                <div class="badge-up"><i class="fa-solid fa-arrow-trend-up"></i> +15%</div>
            </div>
            <div class="chart">
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
            </div>
            <div class="mock-stats">
                <div class="mock-stat">
                    <i class="fa-solid fa-boxes-stacked" style="color:var(--gold)"></i>
                    <span>Item Terjual</span>
                    <strong>500+</strong>
                </div>
                <div class="mock-stat">
                    <i class="fa-solid fa-users" style="color:#10b981"></i>
                    <span>Pedagang Aktif</span>
                    <strong>200+</strong>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="divider">
    <div class="divider-line"></div>
    <div class="divider-label">Fitur Unggulan</div>
    <div class="divider-line"></div>
</div>

<section id="fitur" class="features">
    <div class="section-head">
        <div class="section-tag">Mengapa CuanFlow?</div>
        <h2>Satu Platform untuk Semua Kebutuhan Toko</h2>
        <p>Tinggalkan buku catatan manual. Semua arus kas, stok, dan keuntungan bisa dipantau dalam satu layar.</p>
    </div>

    <div class="features-grid">
        <div class="feat-card">
            <div class="feat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            <h3>Stok Real-Time</h3>
            <p>Setiap transaksi otomatis memotong jumlah stok. Tidak ada lagi pencatatan ganda atau barang hilang tak tercatat.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon"><i class="fa-solid fa-chart-pie"></i></div>
            <h3>Pantau Omset & Profit</h3>
            <p>Lihat omset kotor dan keuntungan bersih harian secara langsung, dilengkapi grafik penjualan interaktif.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon"><i class="fa-solid fa-user-shield"></i></div>
            <h3>Akses Berbasis Peran</h3>
            <p>Pemisahan hak akses antara Admin dan Kasir. Riwayat kerja dicatat berdasarkan shift setiap pengguna.</p>
        </div>
    </div>
</section>

<footer>
    <a href="index.php" class="footer-logo">
        <i class="fa-solid fa-money-bill-trend-up"></i> CuanFlow
    </a>
    <p>&copy; 2026 CuanFlow Point of Sale. Dirancang untuk UMKM Indonesia.</p>
</footer>

</body>
</html>