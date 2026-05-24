<?php
session_start();
require '../config/database.php';

// Pastikan yang akses adalah pedagang
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pedagang') {
    header("Location: ../login.php");
    exit;
}

$id_pelaku = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// --- PROSES UPLOAD FOTO ---
if (isset($_POST['upload_foto'])) {
    $ekstensi_diperbolehkan = array('png', 'jpg', 'jpeg');
    $nama_file = $_FILES['foto']['name'];
    $x = explode('.', $nama_file);
    $ekstensi = strtolower(end($x));
    $ukuran = $_FILES['foto']['size'];
    $file_tmp = $_FILES['foto']['tmp_name'];

    if (in_array($ekstensi, $ekstensi_diperbolehkan) === true) {
        if ($ukuran < 2048000) { // Maksimal 2MB
            // Buat nama file unik agar tidak bentrok
            $nama_file_baru = uniqid() . '.' . $ekstensi;
            $folder_tujuan = '../uploads/' . $nama_file_baru;

            // Ambil foto lama untuk dihapus (opsional, agar server tidak penuh)
            $q_lama = mysqli_query($conn, "SELECT foto_profil FROM users WHERE id_user = '$id_pelaku'");
            $data_lama = mysqli_fetch_assoc($q_lama);
            $foto_lama = $data_lama['foto_profil'];

            // Pindahkan file ke folder uploads
            if (move_uploaded_file($file_tmp, $folder_tujuan)) {
                // Update database
                mysqli_query($conn, "UPDATE users SET foto_profil = '$nama_file_baru' WHERE id_user = '$id_pelaku'");
                
                // Hapus foto lama jika bukan default.png
                if ($foto_lama != 'default.png' && file_exists('../uploads/' . $foto_lama)) {
                    unlink('../uploads/' . $foto_lama);
                }

                $success_msg = "Foto profil berhasil diperbarui!";
            } else {
                $error_msg = "Gagal mengunggah foto.";
            }
        } else {
            $error_msg = "Ukuran file terlalu besar. Maksimal 2MB.";
        }
    } else {
        $error_msg = "Ekstensi file tidak diperbolehkan. Gunakan JPG atau PNG.";
    }
}

// Ambil data user saat ini
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id_user = '$id_pelaku'");
$user_data = mysqli_fetch_assoc($query_user);
$foto_sekarang = $user_data['foto_profil'] ? $user_data['foto_profil'] : 'default.png';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - CuanFlow</title>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>

        :root{--bg:#09090f;--surface:#111118;--card:#16161f;--border:#242432;
            --gold:#d4a017;--gold-lt:#f0c040;--gold-dim:rgba(212,160,23,0.1);
            --green:#10b981;--green-dim:rgba(16,185,129,0.1);
            --red:#ef4444;--red-dim:rgba(239,68,68,0.1);
            --cream:#f0ece3;--muted:#7a7a8e;--white:#fafaf8;}
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        body{background:var(--bg);color:var(--cream);font-family:'Plus Jakarta Sans',sans-serif;
            display:flex;justify-content:center;align-items:center;min-height:100vh;}
        body::before{content:'';position:fixed;inset:0;
            background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events:none;z-index:9999;opacity:0.5;}
        .profile-card{background:var(--card);width:100%;max-width:450px;padding:40px;
            border-radius:20px;box-shadow:0 28px 56px rgba(0,0,0,0.4);text-align:center;
            border:1px solid var(--border);position:relative;overflow:hidden;
            animation:fadeUp 0.6s cubic-bezier(0.22,1,0.36,1);}
        .profile-card::before{content:'';position:absolute;width:300px;height:300px;
            background:radial-gradient(circle,var(--gold-dim) 0%,transparent 70%);
            top:-80px;left:-80px;pointer-events:none;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}
        .profile-card h2{font-family:'Playfair Display',serif;font-size:20px;margin-bottom:5px;color:var(--white);}
        .profile-card>p{color:var(--muted);font-size:13px;margin-bottom:24px;}
        .img-preview{width:120px;height:120px;border-radius:50%;object-fit:cover;
            border:3px solid var(--gold);margin:0 auto 16px;display:block;
            box-shadow:0 0 0 4px var(--gold-dim),0 8px 20px rgba(0,0,0,0.3);}
        .profile-card h3{font-family:'Playfair Display',serif;font-size:16px;
            margin-bottom:22px;color:var(--cream);}
        .form-group{text-align:left;margin-bottom:18px;}
        .form-group label{display:block;font-size:11px;font-weight:600;color:var(--muted);
            letter-spacing:0.07em;text-transform:uppercase;margin-bottom:7px;}
        .form-control{width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;
            font-size:13px;background:var(--surface);color:var(--muted);
            font-family:'Plus Jakarta Sans',sans-serif;cursor:pointer;outline:none;transition:all 0.2s;}
        .form-control:focus{border-color:var(--gold);box-shadow:0 0 0 3px var(--gold-dim);}
        .btn-upload{background:var(--gold);color:var(--bg);width:100%;padding:12px;border:none;
            border-radius:8px;font-weight:700;cursor:pointer;transition:all 0.2s;margin-bottom:12px;
            font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;
            box-shadow:0 4px 14px rgba(212,160,23,0.25);}
        .btn-upload:hover{background:var(--gold-lt);transform:translateY(-1px);}
        .btn-back{display:flex;align-items:center;justify-content:center;gap:6px;color:var(--muted);
            text-decoration:none;font-size:13px;font-weight:500;transition:0.2s;margin-top:4px;}
        .btn-back:hover{color:var(--cream);}
        .alert{padding:11px 14px;border-radius:8px;margin-bottom:18px;font-size:13px;font-weight:500;}
        .alert-success{background:var(--green-dim);color:#34d399;border:1px solid rgba(16,185,129,0.2);}
        .alert-danger{background:var(--red-dim);color:#f87171;border:1px solid rgba(239,68,68,0.2);}
    </style>
</head>
<body>

    <div class="profile-card">
        <h2>Profil Pengguna</h2>
        <p>Sesuaikan foto profil akun kasir Anda.</p>

        <?php if($success_msg) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
        <?php if($error_msg) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>

        <img src="../uploads/<?php echo $foto_sekarang; ?>" alt="Foto Profil" class="img-preview">
        
        <h3 style="font-size: 18px; margin-bottom: 20px; color: #334155;">@<?php echo htmlspecialchars($user_data['username']); ?></h3>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Ganti Foto Profil (Max 2MB)</label>
                <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg, image/jpg" required>
            </div>
            
            <button type="submit" name="upload_foto" class="btn-upload">
                <i class="fa-solid fa-cloud-arrow-up"></i> Simpan Foto
            </button>
            <a href="stok_transaksi.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
        </form>
    </div>

</body>
</html>