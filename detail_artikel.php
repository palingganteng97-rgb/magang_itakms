<?php
require_once __DIR__ . '/auth.php';
require_login();

// =========================================================================
// 1. KONFIGURASI DATABASE
// =========================================================================
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Ambil ID dari URL secara aman
$id = isset($_GET['id']) ? max(0, (int)$_GET['id']) : 0;

if ($id <= 0) {
    die("ID Artikel tidak valid.");
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // =========================================================================
    // 2. QUERY AMBIL DATA ARTIKEL BERDASARKAN ID
    // =========================================================================
    $stmt = $conn->prepare("SELECT * FROM knowledge_articles WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        die("Artikel tidak ditemukan atau telah dihapus.");
    }

} catch (PDOException $e) {
    die("Koneksi atau Query Bermasalah: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Artikel - <?php echo htmlspecialchars($row['judul'] ?? ''); ?></title>
    
    <!-- Memanggil font Nunito lengkap dengan varian ketebalan Bold (700) -->
    <link rel="preconnect" href="https://googleapis.com">
    <link rel="preconnect" href="https://gstatic.com" crossorigin>
    <link href="https://googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* Pengaturan Global Konten Artikel agar Rapi */
        .article-content {
            color: #4e515e; 
            font-size: 16px; 
            line-height: 1.8; 
            min-height: 150px;
            text-align: justify;
        }

        .article-content p {
            margin-top: 0;
            margin-bottom: 1.5rem;
        }

        /* 1. Format Teks Bolt (Hitam Pekat & Sangat Tebal) */
        b, strong {
            font-weight: 700 !important;
            color: #000000 !important; 
        }

        /* 2. Format Teks Italic / Miring (Hitam Pekat & Tegas) */
        i, em {
            font-style: italic !important;
            color: #000000 !important;
        }

        /* 3. Format Teks Underline / Garis Bawah (Solusi Pasti: Spasi Bersih dari Garis) */
        u {
            text-decoration: underline !important;
            text-decoration-color: #000000 !important;
            text-underline-offset: 2px !important;
            text-decoration-skip-ink: none !important;
            color: #000000 !important;
            
            /* Trik CSS: Memaksa pemisahan kata agar spasi &nbsp; tidak ikut kena garis bawah */
            word-spacing: normal !important;
            display: inline-block !important;
            line-height: normal !important;
        }

        /* 4. Format Teks Strikethrough / Coret Tengah (Coretan Hitam Tegas) */
        s, del {
            text-decoration: line-through !important;
            color: #000000 !important; /* Mengubah warna coretan menjadi hitam pekat sesuai gambar */
        }
    </style>
</head>

<body style="background-color: #f8f9fc; font-family: 'Nunito', sans-serif; margin: 0; padding: 40px 20px; color: #5a5c69;">

    <!-- Wadah Utama / Container Card -->
    <div style="max-width: 850px; margin: 0 auto; background-color: #fff; border-radius: 12px; border: 1px solid #e3e6f0; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05); overflow: hidden;">
        
        <!-- Bagian Kepala Kartu (Header) -->
        <div style="background-color: #f8f9fc; padding: 22px 35px; border-bottom: 1px solid #e3e6f0; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; color: #4e73df; font-weight: 700; font-size: 16px;">Detail Artikel ID: #<?php echo $row['id']; ?></h5>
            <!-- Tombol Kembali ke Halaman Sebelumnya -->
            <a href="javascript:history.back()" style="color: #858796; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                &larr; Kembali
            </a>
        </div>

        <!-- Bagian Isi Kartu (Body) -->
        <div style="padding: 35px 40px;">
            
            <!-- Baris Informasi Status & Kategori -->
            <div style="margin-bottom: 25px; display: flex; gap: 10px; align-items: center;">
                <!-- Status badge -->
                <?php if (($row['status'] ?? 1) == 1): ?>
                    <span style="background-color: #1cc88a; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Aktif</span>
                <?php else: ?>
                    <span style="background-color: #858796; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Draf</span>
                <?php endif; ?>

                <!-- Kategori badge -->
                <span style="background-color: #eaecf4; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; color: #4e73df;">
                    📁 <?php echo htmlspecialchars($row['category_name'] ?? 'Tanpa Kategori'); ?>
                </span>
            </div>

            <!-- Bagian Judul Artikel -->
            <h1 style="color: #2e59d9; font-size: 28px; font-weight: 700; margin: 0 0 20px 0; line-height: 1.3; letter-spacing: -0.5px;">
                <?php echo htmlspecialchars($row['judul'] ?? ''); ?>
            </h1>

            <hr style="border: 0; border-top: 1px solid #e3e6f0; margin-bottom: 30px;">

            <!-- Bagian Isi Konten Utama -->
            <div class="article-content">
                <?php 
                // Merender teks HTML secara langsung dan rapi
                echo $row['isi'] ?? ''; 
                ?>
            </div>

            <hr style="border: 0; border-top: 1px solid #e3e6f0; margin: 30px 0;">

            <!-- Bagian Lampiran Berkas -->
            <div style="background-color: #f8f9fc; padding: 18px 22px; border-radius: 8px; border: 1px solid #e3e6f0;">
                <span style="font-weight: 700; font-size: 12px; color: #4e73df; display: block; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Lampiran Dokumen:</span>
                <?php if (!empty($row['lampiran'])): ?>
                    <a href="uploads/<?php echo htmlspecialchars($row['lampiran']); ?>" target="_blank" style="color: #36b9cc; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
                        📄 <?php echo htmlspecialchars($row['lampiran']); ?> <span style="font-weight: 400; color: #858796;">(Klik untuk membuka berkas)</span>
                    </a>
                <?php else: ?>
                    <span style="color: #b7b9cc; font-style: italic; font-size: 14px;">Tidak ada lampiran berkas untuk artikel ini.</span>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>
