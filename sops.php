<?php
// ====================================================================
// LOGIKA BACKEND UTAMA - INSTANT REDIRECT (TANPA ALERT POP-UP)
// ====================================================================

require_once __DIR__ . '/auth.php';
require_login(); 
require_once __DIR__ . '/db.php'; 

$currentPage = 'sops.php';
$upload_dir = 'uploads/sops/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$message = '';
$message_type = '';

// TRIGGER LOG OTOMATIS GLOBAL: Mencatat log kunjungan halaman
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['delete'])) {
    write_log($conn, "Membuka halaman Standard Operating Procedure (SOP)", "sops", null);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // === PROSES TAMBAH DATA (CREATE) ===
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $category_id = $_POST['category_id'];
        $judul = $_POST['judul'];
        $isi = $_POST['isi'];
        $status = isset($_POST['status']) ? $_POST['status'] : 1;
        $lampiran_name = null;

        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            $lampiran_name = time() . '_' . basename($_FILES['lampiran']['name']);
            move_uploaded_file($_FILES['lampiran']['tmp_name'], $upload_dir . $lampiran_name);
        }

        try {
            $sql = "INSERT INTO sops (category_id, judul, isi, lampiran, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $sukses_add = $stmt->execute([$category_id, $judul, $isi, $lampiran_name, $status]);
            
            // AMBIL ID BARU DAN TULIS LOG AKTIVITAS (CREATE)
            if ($sukses_add) {
                $new_sop_id = $conn->lastInsertId();
                write_log($conn, "Menambahkan data SOP baru: " . $judul, "sops", $new_sop_id);
            }

            // Pengalihan instan tanpa memunculkan alert teks
            echo "<script>window.location.href = 'sops.php';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal menambah SOP: " . $e->getMessage();
            $message_type = "danger";
        }
    }

    // === PROSES UBAH DATA (UPDATE) ===
    if (isset($_POST['action']) && $_POST['action'] == 'update') {
        $id = $_POST['id'];
        $category_id = $_POST['category_id'];
        $judul = $_POST['judul'];
        $isi = $_POST['isi'];
        $status = isset($_POST['status']) ? $_POST['status'] : 1;
        $lampiran_name = $_POST['old_lampiran'];

        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            $lampiran_name = time() . '_' . basename($_FILES['lampiran']['name']);
            move_uploaded_file($_FILES['lampiran']['tmp_name'], $upload_dir . $lampiran_name);
            if (!empty($_POST['old_lampiran']) && file_exists($upload_dir . $_POST['old_lampiran'])) {
                unlink($upload_dir . $_POST['old_lampiran']);
            }
        }

        try {
            $sql = "UPDATE sops SET category_id = ?, judul = ?, isi = ?, lampiran = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $sukses_edit = $stmt->execute([$category_id, $judul, $isi, $lampiran_name, $status, $id]);
            
            // TULIS LOG AKTIVITAS (UPDATE)
            if ($sukses_edit) {
                write_log($conn, "Mengubah informasi data SOP: " . $judul, "sops", $id);
            }

            echo "<script>window.location.href = 'sops.php';</script>";
            exit();
        } catch (Exception $e) {
            $message = "Gagal memperbarui SOP: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// === PROSES HAPUS DATA (DELETE) ===
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // 1. Ambil judul dan lampiran sebelum datanya terhapus permanen
        $stmt_file = $conn->prepare("SELECT judul, lampiran FROM sops WHERE id = ?");
        $stmt_file->execute([$id]);
        $sop = $stmt_file->fetch();
        
        if ($sop) {
            $judul_sop = $sop['judul'] ?? 'Unknown';

            // Hapus file fisik lampiran dari server jika ada
            if (!empty($sop['lampiran']) && file_exists($upload_dir . $sop['lampiran'])) {
                unlink($upload_dir . $sop['lampiran']);
            }
            
            $sql = "DELETE FROM sops WHERE id = ?";
            $sukses_delete = $conn->prepare($sql)->execute([$id]);
            
            // TULIS LOG AKTIVITAS (DELETE)
            if ($sukses_delete) {
                write_log($conn, "Menghapus data SOP: " . $judul_sop, "sops", $id);
            }

            echo "<script>window.location.href = 'sops.php';</script>";
            exit();
        }
    } catch (Exception $e) {
        $message = "Gagal menghapus SOP: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Pembacaan Data Akhir
$sops = $conn->query("SELECT sops.*, COALESCE(sop_categories.nama, 'Tanpa Kategori') as category_name 
                     FROM sops 
                     LEFT JOIN sop_categories ON sops.category_id = sop_categories.id 
                     ORDER BY sops.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$categories = $conn->query("SELECT * FROM sop_categories ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Itakms</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <link href="sidebar-admin.css" rel="stylesheet">

    <style>
        /* ==================================================== */
        /* CONFIG LAYOUT DESKTOP / LAPTOP (Lebar Layar >= 768px)*/
        /* ==================================================== */
        @media (min-width: 768px) {
            body {
                display: flex !important;
                overflow: auto !important;
            }
            .offcanvas-md.sidebar-fixed {
                position: fixed !important;
                top: 0;
                left: 0;
                min-height: 100vh !important;
                max-height: 100vh !important;
                width: 280px !important;
                z-index: 1040;
                transform: none !important;
                visibility: visible !important;
                overflow: hidden !important;
            }
            .menu-scroll-container {
                display: block !important;
                max-height: calc(100vh - 160px) !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                scrollbar-width: none;
                -ms-overflow-style: none;
            }
            .menu-scroll-container::-webkit-scrollbar {
                display: none !important;
            }
            /* Menghitung lebar konten di desktop */
            .main-content {
                margin-left: 280px !important;
                width: calc(100% - 280px) !important;
                min-height: 100vh !important;
                display: block !important; /* Kembalikan sifat block di laptop */
            }
            .offcanvas-backdrop, .offcanvas-backdrop.show {
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }
        }

        /* ==================================================== */
        /* CONFIG LAYOUT MOBILE / HP (Lebar Layar < 768px)     */
        /* ==================================================== */
        @media (max-width: 767.98px) {
            body {
                overflow: auto !important;
                position: relative !important;
                display: block !important; /* Hancurkan flexbox desktop di HP */
            }
            /* Kunci container induk luar agar di HP melepas kuncian flex */
            .d-md-flex {
                display: block !important; 
            }
            .sidebar-fixed {
                position: fixed !important;
                width: 280px !important;
                height: 100vh !important;
            }
            .menu-scroll-container {
                max-height: none !important;
                overflow-y: visible !important;
            }
            /* SOLUSI UTAMA: Hancurkan pembatasan tinggi pendek di HP */
            .main-content {
                width: 100% !important;
                margin-left: 0 !important;
                min-height: calc(100vh - 70px) !important; /* Ambil sisa tinggi layar HP */
                display: block !important;
                height: auto !important; /* Biarkan memanjang alami mengikuti jumlah data tabel */
            }
            .offcanvas-backdrop.show {
                display: block !important;
                opacity: 0 !important;
                background-color: transparent !important;
                visibility: visible !important;
            }
        }

        /* ==================================================== */
        /* KUSTOMISASI MODEREN MENU STYLE                      */
        /* ==================================================== */
        .sidebar-fixed .nav-link {
            color: #adb5bd !important;
            font-weight: 500;
            padding: 10px 16px !important;
            margin: 2px 0;
            transition: all 0.25s ease-in-out;
            border-left: 4px solid transparent;
        }
        .sidebar-fixed .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.06) !important;
            border-left-color: rgba(255, 255, 255, 0.3);
            padding-left: 20px !important;
        }
        .sidebar-fixed .nav-link.active-style {
            color: #ffffff !important;
            background: linear-gradient(90deg, rgba(13, 110, 253, 0.25) 0%, rgba(13, 110, 253, 0.05) 100%) !important;
            border-left: 4px solid #0d6efd !important;
            box-shadow: inset 0 0 8px rgba(13, 110, 253, 0.15);
            font-weight: 600;
        }
        .sidebar-fixed .nav-link i {
            font-size: 1.1rem;
            transition: transform 0.25s ease;
        }
        .sidebar-fixed .nav-link:hover i {
            transform: scale(1.15);
        }
        .sidebar-fixed .nav-link.active-style i {
            color: #0d6efd !important;
        }
    </style>

</head>
<body>

<!-- ==================================================================== -->
<!-- BAGIAN A: TOMBOL PEMICU SIDEBAR (HANYA MUNCUL DI MOBILE / HP)        -->
<!-- ==================================================================== -->
<div class="d-md-none p-3 bg-dark d-flex justify-content-between align-items-center w-100 position-sticky top-0 shadow-sm" style="z-index: 1050;">
    <h5 class="text-warning mb-0 fw-bold"><i class="bi bi-speedometer2 me-2"></i> ITAKMS</h5>
    <button class="btn btn-warning btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarFlexible" aria-controls="sidebarFlexible">
        <i class="bi bi-list fs-5"></i>
    </button>
</div>

<!-- ==================================================================== -->
<!-- BAGIAN B: INDUK KONTEN SIDEBAR RESPONSIVE (FLEKSIBEL HP & DESKTOP)   -->
<!-- ==================================================================== -->
<div class="offcanvas-md offcanvas-start d-flex flex-column sidebar-fixed p-3 text-bg-dark border-end border-secondary" 
     tabindex="-1" id="sidebarFlexible" aria-labelledby="sidebarFlexibleLabel">

  <!-- 1. Header Laci Menu (Hanya Muncul di Layar HP saat Laci Terbuka) -->
  <div class="offcanvas-header border-bottom border-secondary d-md-none">
    <h5 class="offcanvas-title text-warning fw-bold" id="sidebarFlexibleLabel">
        <i class="bi bi-speedometer2 me-2"></i> ITAKMS
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarFlexible" aria-label="Close"></button>
  </div>
  
  <!-- 2. Judul Utama Navigasi (Hanya Muncul di Desktop/Laptop) -->
  <h4 class="text-center mb-4 text-warning fw-bold pt-2 d-none d-md-block">
      <i class="bi bi-speedometer2 me-2"></i> ITAKMS
  </h4>
  
  <!-- 3. AREA MENU TENGAH (Otomatis & Fleksibel untuk Semua Opsi Berbasis Fungsi PHP) -->
  <?php
  // Ambil nama file yang sedang aktif saat ini secara real-time
  $currentFile = basename($_SERVER['PHP_SELF']);

  // Fungsi pintar untuk otomatis mencetak class 'active-style' jika nama file cocok
  if (!function_exists('checkActiveMenu')) {
      function checkActiveMenu($targetFile, $currentFile) {
          return ($currentFile === $targetFile) ? 'active-style' : '';
      }
  }
  ?>
  
    <?php include __DIR__ . '/sidebar.php'; ?>

<!-- AREA UTAMA KONTEN (Gunakan pembungkus ini agar susunan halaman tidak bergeser tertimpa sidebar) -->
<main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">
    <div class="card shadow-sm border rounded-3">
        <!-- Header Konten Utama -->
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="mb-0 text-dark fw-bold">
                <i class="bi bi-journal-text text-primary me-2"></i> Standard Operating Procedures (SOP)
            </h5>
            
            <!-- Fleksibel Otomatis: Hanya tampil jika Role memiliki izin Create ('C') di file ini -->
            <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'C', $userRole)): ?>
            <!-- Tombol pemicu Modal Tambah Data (Tahap 2) -->
            <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddSOP">
                <i class="bi bi-plus-lg me-1"></i> Tambah SOP
            </button>
            <?php endif; ?>
            
        </div>

<!-- Body Utama Tempat Tabel Data -->
        <div class="card-body p-4">
            
            <!-- Notifikasi Status Berhasil/Gagal -->
            <?php if(!empty($message)): ?>
                <div class="alert alert-<?= $message_type; ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi <?= $message_type == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
                    <?= $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Tabel Bergaris Pemisah Jelas (table-bordered) -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle m-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th width="5%" class="text-center">ID</th>
                            <th width="25%">Judul SOP</th>
                            <th width="15%">Kategori</th>
                            <th width="<?= (!in_array($userRole, ['Teknisi', 'Viewer'])) ? '30%' : '40%'; ?>">Isi Prosedur</th>
                            <th width="10%">Lampiran</th>
                            <th width="5%">Status</th>
                            
                            <!-- Fleksibel Otomatis: Hanya tampilkan kolom header Aksi jika user bukan Teknisi & Viewer -->
                            <?php if (!in_array($userRole, ['Teknisi', 'Viewer'])): ?>
                            <th width="10%" class="text-center">Aksi</th>
                            <?php endif; ?>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($sops) == 0): ?>
                            <tr>
                                <td colspan="<?= (!in_array($userRole, ['Teknisi', 'Viewer'])) ? '7' : '6'; ?>" class="text-center text-muted py-5">
                                    <i class="bi bi-folder-x display-6 d-block mb-2 text-secondary"></i>
                                    Belum ada data SOP yang tersimpan di sistem.
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach($sops as $row): ?>
                        <tr>
                            <td class="text-center"><span class="text-muted fw-bold">#<?= $row['id']; ?></span></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['judul']); ?></td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1.5">
                                    <?= htmlspecialchars($row['category_name']); ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted d-block text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($row['isi']); ?>">
                                    <?= htmlspecialchars($row['isi']); ?>
                                </small>
                            </td>
                            <td>
                                <?php if(!empty($row['lampiran'])): ?>
                                    <a href="<?= $upload_dir . $row['lampiran']; ?>" target="_blank" class="btn btn-sm btn-link text-decoration-none p-0 fw-semibold text-info">
                                        <i class="bi bi-file-earmark-arrow-down-fill me-1"></i>Buka File
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-dash"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $row['status'] == 1 
                                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>' 
                                    : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Non-Aktif</span>'; 
                                ?>
                            </td>
                            
                            <!-- Fleksibel Otomatis: Blok kolom aksi disembunyikan total dari Teknisi & Viewer (X) -->
                            <?php if (!in_array($userRole, ['Teknisi', 'Viewer'])): ?>
                            <td class="text-center">
                                <div class="btn-group shadow-sm border rounded bg-white">
                                    
                                    <!-- Fleksibel Otomatis: Cek Akses Update ('U') untuk tombol Edit -->
                                    <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'U', $userRole)): ?>
                                    <!-- Tombol Pemicu Modal Edit (Tahap 3) -->
                                    <button class="btn btn-sm text-warning border-0" data-bs-toggle="modal" data-bs-target="#modalEditSOP<?= $row['id']; ?>" title="Ubah Data">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <!-- Fleksibel Otomatis: Cek Akses Delete ('D') untuk tombol Hapus -->
                                    <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'D', $userRole)): ?>
                                    <!-- Tombol Pemicu Modal Hapus (Tahap 3) -->
                                    <button class="btn btn-sm text-danger border-0 border-start" data-bs-toggle="modal" data-bs-target="#modalDeleteSOP<?= $row['id']; ?>" title="Hapus Data">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                </div>
                            </td>
                            <?php endif; ?>
                            
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- ==================================================================== -->
<!-- TAHAP 2: MODAL TAMBAH DATA (SEJAJAR HORIZONTAL & DINAMIS KATEGORI)  -->
<!-- ==================================================================== -->
<div class="modal fade" id="modalAddSOP" tabindex="-1" aria-labelledby="modalAddSOPLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form action="sops.php" method="POST" enctype="multipart/form-data">
          <!-- Hidden Input Action untuk Handler CRUD PHP -->
          <input type="hidden" name="action" value="create">
          
          <!-- Header Modal -->
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-primary" id="modalAddSOPLabel">
              <i class="bi bi-plus-circle-fill me-2"></i>Registrasi SOP Baru
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <!-- Body Modal (Sejajar Kesamping) -->
          <div class="modal-body pt-3">
            
            <!-- Baris Judul SOP -->
            <div class="row mb-3 align-items-center">
                <label for="judul" class="col-4 col-form-label small fw-bold text-secondary text-end">Judul Prosedur</label>
                <div class="col-8">
                    <input type="text" id="judul" name="judul" class="form-control form-control-sm text-dark" placeholder="Contoh: Prosedur restart server" required>
                </div>
            </div>
            
            <!-- Baris Kategori Dinamis (Mengambil dari database sop_categories) -->
            <div class="row mb-3 align-items-center">
                <label for="category_id" class="col-4 col-form-label small fw-bold text-secondary text-end">Kategori</label>
                <div class="col-8">
                    <select id="category_id" name="category_id" class="form-select form-select-sm" required>
                        <option value="" disabled selected>-- Pilih Kategori --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>">
                                <!-- Fleksibel mendeteksi nama kolom database: nama atau nama_kategori -->
                                <?= htmlspecialchars($cat['nama'] ?? $cat['nama_kategori'] ?? 'Kategori #' . $cat['id']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Baris Detail Urutan Langkah Kerja -->
            <div class="row mb-3 align-items-start">
                <label for="isi" class="col-4 col-form-label small fw-bold text-secondary text-end pt-1">Langkah Kerja</label>
                <div class="col-8">
                    <textarea id="isi" name="isi" class="form-control form-control-sm text-dark" rows="5" placeholder="Tuliskan petunjuk teknis pelaksanaan tugas..." required></textarea>
                </div>
            </div>
            
            <!-- Baris Upload File Pendukung -->
            <div class="row mb-3 align-items-center">
                <label for="lampiran" class="col-4 col-form-label small fw-bold text-secondary text-end">File Pendukung</label>
                <div class="col-8">
                    <input type="file" id="lampiran" name="lampiran" class="form-control form-control-sm">
                </div>
            </div>
            
            <!-- Baris Status Regulasi Awal -->
            <div class="row mb-2 align-items-center">
                <label for="status" class="col-4 col-form-label small fw-bold text-secondary text-end">Status Awal</label>
                <div class="col-8">
                    <select id="status" name="status" class="form-select form-select-sm" style="max-width: 200px;">
                        <option value="1">Aktif (Langsung Terbitkan)</option>
                        <option value="0">Non-Aktif (Draft)</option>
                    </select>
                </div>
            </div>
          </div>
          
          <!-- Tombol Aksi Mandiri -->
          <div class="modal-footer border-top-0 pt-0">
            <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-sm btn-primary fw-bold px-4 shadow-sm">Simpan Sistem</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- ==================================================================== -->
<!-- TAHAP 3: MODAL EDIT DATA (SEJAJAR HORIZONTAL & DINAMIS TERISI DATA) -->
<!-- ==================================================================== -->
<div class="modal fade" id="modalEditSOP<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalEditSOPLabel<?= $row['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
        <form action="sops.php" method="POST" enctype="multipart/form-data">
            <!-- Hidden Input Keperluan Handler CRUD PHP -->
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $row['id']; ?>">
            <input type="hidden" name="old_lampiran" value="<?= $row['lampiran']; ?>">
            
            <!-- Header Modal Edit -->
            <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold text-dark" id="modalEditSOPLabel<?= $row['id']; ?>">
                <i class="bi bi-pencil-square text-warning me-2"></i> Ubah Data SOP
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Body Modal Edit (Selalu Sejajar Kesamping di Mobile & Desktop) -->
            <div class="modal-body pt-3">
            
            <!-- Baris Ubah Judul SOP -->
            <div class="row mb-3 align-items-center">
                <label for="judul_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Judul SOP</label>
                <div class="col-8">
                    <input type="text" id="judul_<?= $row['id']; ?>" name="judul" class="form-control form-control-sm text-dark" value="<?= htmlspecialchars($row['judul']); ?>" required>
                </div>
            </div>
            
            <!-- Baris Ubah Kategori Terpilih -->
            <div class="row mb-3 align-items-center">
                <label for="category_id_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Kategori</label>
                <div class="col-8">
                    <select id="category_id_<?= $row['id']; ?>" name="category_id" class="form-select form-select-sm" required>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id']; ?>" <?= $cat['id'] == $row['category_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($cat['nama'] ?? $cat['nama_kategori'] ?? 'Kategori #' . $cat['id']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Baris Ubah Isi Prosedur Langkah Kerja -->
            <div class="row mb-3 align-items-start">
                <label for="isi_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end pt-1">Isi Prosedur</label>
                <div class="col-8">
                    <textarea id="isi_<?= $row['id']; ?>" name="isi" class="form-control form-control-sm text-dark" rows="5" required><?= htmlspecialchars($row['isi']); ?></textarea>
                </div>
            </div>
            
            <!-- Baris Kelola Berkas Lampiran Aktif -->
            <div class="row mb-3 align-items-start">
                <label for="lampiran_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end pt-1">Lampiran</label>
                <div class="col-8">
                    <input type="file" id="lampiran_<?= $row['id']; ?>" name="lampiran" class="form-control form-control-sm mb-1">
                    <?php if(!empty($row['lampiran'])): ?>
                        <div class="form-text text-muted bg-light p-1 px-2 rounded border d-inline-block small text-truncate" style="max-width: 100%;">
                            <i class="bi bi-file-earmark-check-fill text-success me-1"></i> File saat ini: 
                            <a href="<?= $upload_dir . $row['lampiran']; ?>" target="_blank" class="text-decoration-none text-info fw-semibold"><?= htmlspecialchars($row['lampiran']); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Baris Ubah Status Regulasi -->
            <div class="row mb-2 align-items-center">
                <label for="status_<?= $row['id']; ?>" class="col-4 col-form-label small fw-bold text-secondary text-end">Status</label>
                <div class="col-8">
                    <select id="status_<?= $row['id']; ?>" name="status" class="form-select form-select-sm" style="max-width: 200px;">
                        <option value="1" <?= $row['status'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                        <option value="0" <?= $row['status'] == 0 ? 'selected' : ''; ?>>Non-Aktif</option>
                    </select>
                </div>
            </div>
            </div>
            
            <!-- Footer Tombol Aksi -->
            <div class="modal-footer border-top-0 pt-0">
            <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Tutup</button>
            <button type="submit" class="btn btn-sm btn-warning fw-bold text-white px-4 shadow-sm">Update SOP</button>
            </div>
        </form>
    </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL HAPUS DATA (SOP - HORIZONTAL)        -->
<!-- ========================================== -->
<div class="modal fade" id="modalDeleteSOP<?= $row['id']; ?>" tabindex="-1" aria-labelledby="modalDeleteSOPLabel<?= $row['id']; ?>" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      
      <!-- Header Modal -->
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-danger" id="modalDeleteSOPLabel<?= $row['id']; ?>">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Body Modal Dengan Format Memanjang Kanan -->
      <div class="modal-body pt-3">
        <div class="row align-items-center">
            <!-- Sisi Kiri: Ikon Peringatan -->
            <div class="col-sm-2 text-center text-sm-end mb-3 mb-sm-0">
                <i class="bi bi-trash3 text-danger display-6"></i>
            </div>
            <!-- Sisi Kanan: Teks Penjelasan Data -->
            <div class="col-sm-10">
                <p class="mb-1 text-secondary small fw-bold">Anda akan menghapus dokumen berikut:</p>
                <h6 class="fw-bold text-dark mb-0">"<?= htmlspecialchars($row['judul']); ?>"</h6>
                <p class="text-muted small mt-2 mb-0">Tindakan ini bersifat permanen. Data beserta file lampiran yang tersimpan di server akan dihapus sepenuhnya.</p>
            </div>
        </div>
      </div>
      
      <!-- Footer Tombol Aksi -->
      <div class="modal-footer border-top-0 pt-2">
        <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Batal</button>
        <a href="sops.php?delete=<?= $row['id']; ?>" class="btn btn-sm btn-danger fw-bold px-4 shadow-sm">
            <i class="bi bi-trash me-1"></i>Ya, Hapus Data
        </a>
      </div>
      
    </div>
  </div>
</div>

<!-- INTERAKSI DOM DAN SINKRONISASI POSISI SCROLL SIDEBAR UTAMA (KUNCI MUTLAK) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const menuContainer = document.querySelector('.menu-scroll-container');
    const activeMenu = document.querySelector('.menu-scroll-container .active-style');
    
    // FIX MUTLAK: Selalu menetap mengunci fokus menu aktif saat halaman selesai dimuat (klik / reload)
    if (menuContainer && activeMenu) {
        const activeOffsetTop = activeMenu.offsetTop;
        menuContainer.scrollTop = activeOffsetTop - 20;
    }
});

// MODUL PENUTUP OTOMATIS ALERT & PEMBERSIH PARAMETER URL
document.addEventListener("click", function(t) {
    let alertBtn = t.target.closest('[data-bs-dismiss="alert"]');
    if (alertBtn) {
        let alertBox = t.target.closest('.alert');
        if (alertBox) {
            t.preventDefault();
            alertBox.remove(); 
            window.location.href = window.location.pathname; 
        }
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
