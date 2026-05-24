<?php
session_start();
require '../config/database.php';

// Keamanan: Pastikan role pedagang
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pedagang') {
    header("Location: ../login.php");
    exit;
}

$id_pelaku = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Proses Simpan Pengaduan
if (isset($_POST['kirim_pengaduan'])) {
    $subjek = mysqli_real_escape_string($conn, $_POST['subjek']);
    $pesan = mysqli_real_escape_string($conn, $_POST['pesan']);
    
    $nama_foto = NULL; // Default jika tidak ada foto
    $upload_sukses = true;

    // Cek apakah ada file foto yang diunggah
    if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] == 0) {
        $ekstensi_diperbolehkan = array('png', 'jpg', 'jpeg');
        $nama_file = $_FILES['foto_bukti']['name'];
        $x = explode('.', $nama_file);
        $ekstensi = strtolower(end($x));
        $ukuran = $_FILES['foto_bukti']['size'];
        $file_tmp = $_FILES['foto_bukti']['tmp_name'];

        if (in_array($ekstensi, $ekstensi_diperbolehkan)) {
            if ($ukuran < 2048000) { // Maksimal 2MB
                $nama_foto = uniqid() . '_pengaduan.' . $ekstensi;
                $target_dir = '../uploads/pengaduan/';

                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true); 
                }

                $folder_tujuan = $target_dir . $nama_foto;

                if (!move_uploaded_file($file_tmp, $folder_tujuan)) {
                    $upload_sukses = false;
                    $error_msg = "Gagal mengunggah foto bukti. Pastikan folder memiliki izin akses (write permission).";
                }
            } else {
                $upload_sukses = false;
                $error_msg = "Ukuran foto terlalu besar. Maksimal 2MB.";
            }
        } else {
            $upload_sukses = false;
            $error_msg = "Ekstensi file tidak diperbolehkan. Gunakan JPG atau PNG.";
        }
    }

    // Jika upload tidak bermasalah (atau tidak ada upload sama sekali), simpan ke DB
    if ($upload_sukses) {
        $query = "INSERT INTO pengaduan (id_user, subjek, pesan, foto, status, tanggal) 
                  VALUES ('$id_pelaku', '$subjek', '$pesan', " . ($nama_foto ? "'$nama_foto'" : "NULL") . ", 'pending', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $success_msg = "Pengaduan Anda telah terkirim dan akan segera kami tinjau.";
        } else {
            $error_msg = "Gagal menyimpan pengaduan ke database. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan - CuanFlow</title>
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
            display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px;}
        body::before{content:'';position:fixed;inset:0;
            background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events:none;z-index:9999;opacity:0.5;}
        .pengaduan-card{background:var(--card);width:100%;max-width:500px;padding:40px;
            border-radius:20px;box-shadow:0 28px 56px rgba(0,0,0,0.4);text-align:center;
            border:1px solid var(--border);position:relative;overflow:hidden;
            animation:fadeUp 0.6s cubic-bezier(0.22,1,0.36,1);}
        .pengaduan-card::before{content:'';position:absolute;width:280px;height:280px;
            background:radial-gradient(circle,var(--gold-dim) 0%,transparent 70%);
            top:-80px;right:-60px;pointer-events:none;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}
        .icon-header{width:60px;height:60px;background:var(--gold-dim);color:var(--gold);
            border:1px solid rgba(212,160,23,0.25);border-radius:15px;display:flex;
            align-items:center;justify-content:center;font-size:24px;margin:0 auto 18px;}
        .pengaduan-card h2{font-family:'Playfair Display',serif;font-size:22px;margin-bottom:9px;color:var(--white);font-weight:600;}
        .pengaduan-card>p{color:var(--muted);font-size:13px;margin-bottom:28px;line-height:1.7;}
        .form-group{text-align:left;margin-bottom:18px;}
        .form-group label{display:block;font-size:11px;font-weight:600;color:var(--muted);
            letter-spacing:0.07em;text-transform:uppercase;margin-bottom:7px;}
        .form-control{width:100%;padding:11px 14px;border:1px solid var(--border);border-radius:8px;
            font-size:13px;background:var(--surface);outline:none;transition:all 0.2s;
            color:var(--white);font-family:'Plus Jakarta Sans',sans-serif;}
        .form-control::placeholder{color:#2e2e42;}
        .form-control:focus{border-color:var(--gold);background:var(--card);box-shadow:0 0 0 3px var(--gold-dim);}
        textarea.form-control{resize:vertical;min-height:110px;}
        
        /* Area Upload */
        .file-input-wrapper{position:relative;width:100%;border:1px dashed var(--border);
            border-radius:8px;background:var(--surface);padding:18px;text-align:center;
            transition:all 0.25s;}
        .file-input-wrapper:hover{border-color:var(--gold);background:var(--gold-dim);}
        
        /* Input file aslinya transparan, menutupi area upload */
        .file-input-wrapper input[type="file"]{position:absolute;left:0;top:0;opacity:0;
            width:100%;height:100%;cursor:pointer; z-index: 10;}
            
        .file-input-text{color:var(--muted);font-size:12px;pointer-events:none; position: relative; z-index: 1;}
        .file-input-text i{font-size:22px;color:var(--gold);margin-bottom:6px;display:block;}
        
        /* Container untuk Image & Tombol Silang */
        .preview-container {
            position: relative;
            display: none; /* Sembunyikan saat kosong */
            width: fit-content;
            margin: 0 auto;
            z-index: 5;
        }
        
        .preview-img {
            max-width: 100%;
            max-height: 140px;
            border-radius: 8px;
            object-fit: contain;
            display: block;
        }

        /* Tombol Silang Batal */
        .btn-remove-photo {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--red);
            color: white;
            border: none;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            z-index: 20; /* Harus lebih tinggi dari z-index input file */
            transition: all 0.2s;
        }
        .btn-remove-photo:hover {
            background: #f87171;
            transform: scale(1.1);
        }

        .btn-submit{background:var(--gold);color:var(--bg);width:100%;padding:12px;border:none;
            border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;transition:all 0.2s;
            margin-bottom:12px;display:flex;justify-content:center;align-items:center;gap:8px;
            font-family:'Plus Jakarta Sans',sans-serif;box-shadow:0 4px 14px rgba(212,160,23,0.25);}
        .btn-submit:hover{background:var(--gold-lt);transform:translateY(-1px);}
        .btn-back{display:inline-flex;align-items:center;gap:6px;color:var(--muted);
            text-decoration:none;font-size:13px;font-weight:500;transition:0.2s;}
        .btn-back:hover{color:var(--cream);}
        .alert{padding:13px 15px;border-radius:8px;margin-bottom:22px;font-size:13px;
            font-weight:500;text-align:left;display:flex;align-items:center;gap:10px;}
        .alert-success{background:var(--green-dim);color:#34d399;border:1px solid rgba(16,185,129,0.2);}
        .alert-danger{background:var(--red-dim);color:#f87171;border:1px solid rgba(239,68,68,0.2);}
    </style>
</head>
<body>

    <div class="pengaduan-card">
        <div class="icon-header">
            <i class="fa-solid fa-headset"></i>
        </div>
        <h2>Pusat Bantuan</h2>
        <p>Ada kendala dengan sistem atau transaksi? Laporkan kepada kami melalui form di bawah ini.</p>

        <?php if($success_msg): ?>
            <div class='alert alert-success'>
                <i class="fa-solid fa-circle-check" style="font-size: 18px;"></i>
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error_msg): ?>
            <div class='alert alert-danger'>
                <i class="fa-solid fa-circle-exclamation" style="font-size: 18px;"></i>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Subjek / Judul Masalah</label>
                <input type="text" name="subjek" class="form-control" placeholder="Contoh: Kesalahan Input Stok" required>
            </div>
            
            <div class="form-group">
                <label>Detail Pesan Pengaduan</label>
                <textarea name="pesan" class="form-control" placeholder="Jelaskan secara detail masalah yang Anda alami..." required></textarea>
            </div>

            <div class="form-group">
                <label>Lampirkan Bukti Foto (Opsional)</label>
                <div class="file-input-wrapper">
                    <input type="file" name="foto_bukti" id="foto_bukti" accept=".jpg, .jpeg, .png" onchange="previewImage(event)">
                    
                    <div class="file-input-text" id="file_name_display">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        Klik atau seret foto ke sini (Maks 2MB)
                    </div>
                    
                    <div class="preview-container" id="preview_container">
                        <img id="image_preview" class="preview-img" alt="Preview Foto">
                        <button type="button" class="btn-remove-photo" onclick="removePhoto(event)">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="kirim_pengaduan" class="btn-submit">
                <i class="fa-solid fa-paper-plane"></i> Kirim Laporan
            </button>
            <a href="stok_transaksi.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Sebelumnya
            </a>
        </form>
    </div>

    <script>
        function previewImage(event) {
            const input = event.target;
            const previewContainer = document.getElementById('preview_container');
            const preview = document.getElementById('image_preview');
            const display = document.getElementById('file_name_display');

            // Jika user memilih file
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result; // Tampilkan gambar
                    previewContainer.style.display = 'block'; // Munculkan gambar + tombol X
                    display.style.display = 'none';  // Sembunyikan ikon cloud & teks
                    
                    // Supaya input file transparan tidak menutupi tombol X
                    input.style.display = 'none'; 
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                removePhoto(); // Kalau dibatalkan, jalankan fungsi remove
            }
        }

        // Fungsi untuk menghapus foto & mengembalikan form ke semula
        function removePhoto(event) {
            if(event) event.preventDefault(); // Mencegah form ke-submit secara tidak sengaja
            
            const input = document.getElementById('foto_bukti');
            const previewContainer = document.getElementById('preview_container');
            const preview = document.getElementById('image_preview');
            const display = document.getElementById('file_name_display');

            input.value = ''; // Kosongkan file yang sudah terpilih
            preview.src = '';
            
            previewContainer.style.display = 'none'; // Sembunyikan gambar + X
            display.style.display = 'block'; // Tampilkan lagi ikon cloud
            input.style.display = 'block'; // Munculkan lagi input filenya
        }
    </script>
</body>
</html>