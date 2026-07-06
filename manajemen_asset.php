<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. KONFIGURASI KONEKSI DATABASE UTAMA
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Inisialisasi array kosong agar halaman tidak crash jika database kosong
$brands = [];
$categories = [];
$statuses = [];

try {
    // Berikan batas waktu tunggu 2 detik agar jika jaringan drop tidak loading selamanya
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password, [
        PDO::ATTR_TIMEOUT => 2,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // =========================================================================
    // 2. LOGIKA INTERSEPTOR CRUD (DIPINDAHKAN LANGSUNG KE SINI)
    // =========================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        // --- KELOMPOK: ASSET BRANDS ---
        if ($action === 'add_brand') {
            $nama = trim($_POST['nama'] ?? '');
            $status_raw = isset($_POST['status']) ? trim($_POST['status']) : '1';
            $status = ($status_raw === 'Aktif' || $status_raw === '1') ? 1 : 0;

            if ($nama !== '') {
                $stmt = $conn->prepare("INSERT INTO asset_brands (nama, status) VALUES (?, ?)");
                $sukses = $stmt->execute([$nama, $status]);
                if ($sukses) {
                    $new_id = $conn->lastInsertId();
                    write_log($conn, "Menambahkan merek brand baru: " . $nama, "asset_brands", $new_id);
                }
            }
        }
        if ($action === 'edit_brand') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $status_raw = isset($_POST['status']) ? trim($_POST['status']) : '1';
            $status = ($status_raw === 'Aktif' || $status_raw === '1') ? 1 : 0;

            if ($nama !== '') {
                $stmt = $conn->prepare("UPDATE asset_brands SET nama = ?, status = ? WHERE id = ?");
                $sukses = $stmt->execute([$nama, $status, $id]);
                if ($sukses) {
                    write_log($conn, "Mengubah informasi data merek brand menjadi: " . $nama, "asset_brands", $id);
                }
            }
        }
        if ($action === 'delete_brand') {
            $id = (int)$_POST['id'];
            $get_name = $conn->prepare("SELECT nama FROM asset_brands WHERE id = ?");
            $get_name->execute([$id]);
            $nama_brand = $get_name->fetchColumn() ?: 'Unknown';

            $sukses = $conn->prepare("DELETE FROM asset_brands WHERE id = ?")->execute([$id]);
            if ($sukses) {
                write_log($conn, "Menghapus data merek brand: " . $nama_brand, "asset_brands", $id);
            }
        }

        // --- KELOMPOK: ASSET CATEGORIES ---
        if ($action === 'add_category') {
            $nama = trim($_POST['nama'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $warna = trim($_POST['warna'] ?? '');
            $status_raw = isset($_POST['status']) ? trim($_POST['status']) : '1';
            $status = ($status_raw === 'Aktif' || $status_raw === '1') ? 1 : 0;

            if ($nama !== '') {
                $stmt = $conn->prepare("INSERT INTO asset_categories (nama, icon, warna, status) VALUES (?, ?, ?, ?)");
                $sukses = $stmt->execute([$nama, $icon, $warna, $status]);
                if ($sukses) {
                    $new_id = $conn->lastInsertId();
                    write_log($conn, "Menambahkan kategori aset baru: " . $nama, "asset_categories", $new_id);
                }
            }
        }
        if ($action === 'edit_category') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $warna = trim($_POST['warna'] ?? '');
            $status_raw = isset($_POST['status']) ? trim($_POST['status']) : '1';
            $status = ($status_raw === 'Aktif' || $status_raw === '1') ? 1 : 0;

            if ($nama !== '') {
                $stmt = $conn->prepare("UPDATE asset_categories SET nama = ?, icon = ?, warna = ?, status = ? WHERE id = ?");
                $sukses = $stmt->execute([$nama, $icon, $warna, $status, $id]);
                if ($sukses) {
                    write_log($conn, "Mengubah data kategori aset menjadi: " . $nama, "asset_categories", $id);
                }
            }
        }
        if ($action === 'delete_category') {
            $id = (int)$_POST['id'];
            $get_name = $conn->prepare("SELECT nama FROM asset_categories WHERE id = ?");
            $get_name->execute([$id]);
            $nama_kategori = $get_name->fetchColumn() ?: 'Unknown';

            $sukses = $conn->prepare("DELETE FROM asset_categories WHERE id = ?")->execute([$id]);
            if ($sukses) {
                write_log($conn, "Menghapus data kategori aset: " . $nama_kategori, "asset_categories", $id);
            }
        }

        // --- KELOMPOK: ASSET STATUSES ---
        if ($action === 'add_status') {
            $nama = trim($_POST['nama'] ?? '');
            if ($nama !== '') {
                $stmt = $conn->prepare("INSERT INTO asset_statuses (nama) VALUES (?)");
                $sukses = $stmt->execute([$nama]);
                if ($sukses) {
                    $new_id = $conn->lastInsertId();
                    write_log($conn, "Menambahkan status operasional asset baru: " . $nama, "asset_statuses", $new_id);
                }
            }
        }
        if ($action === 'edit_status') {
            $id = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            if ($nama !== '') {
                $stmt = $conn->prepare("UPDATE asset_statuses SET nama = ? WHERE id = ?");
                $sukses = $stmt->execute([$nama, $id]);
                if ($sukses) {
                    write_log($conn, "Mengubah status operasional asset menjadi: " . $nama, "asset_statuses", $id);
                }
            }
        }
        if ($action === 'delete_status') {
            $id = (int)$_POST['id'];
            $get_name = $conn->prepare("SELECT nama FROM asset_statuses WHERE id = ?");
            $get_name->execute([$id]);
            $nama_status = $get_name->fetchColumn() ?: 'Unknown';

            $sukses = $conn->prepare("DELETE FROM asset_statuses WHERE id = ?")->execute([$id]);
            if ($sukses) {
                write_log($conn, "Menghapus status operasional asset: " . $nama_status, "asset_statuses", $id);
            }
        }

        // KIRIM RESPONS SUKSES LANGSUNG KEMBALI KE AJAX MODAL JAVASCRIPT
        http_response_code(200);
        echo "Sukses";
        exit;
    }

    // TRIGGER LOG OTOMATIS GLOBAL: Mencatat log kunjungan halaman Master Data Asset
    if (!isset($_GET['ajax'])) {
        write_log($conn, "Membuka halaman Master Data Asset (Brands, Categories, Statuses)", "asset_brands", null);
    }

    // =========================================================================
    // 3. EKSEKUSI PEMBACAAN DATA SEPERTI SEMULA (READ)
    // =========================================================================
    $stmtBrand = $conn->prepare("SELECT id, nama, status FROM asset_brands ORDER BY id DESC LIMIT 50");
    $stmtBrand->execute();
    $brands = $stmtBrand->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtCat = $conn->prepare("SELECT id, nama, icon, warna FROM asset_categories ORDER BY id DESC LIMIT 50");
    $stmtCat->execute();
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtStatus = $conn->prepare("SELECT id, nama FROM asset_statuses ORDER BY id DESC LIMIT 50");
    $stmtStatus->execute();
    $statuses = $stmtStatus->fetchAll(PDO::FETCH_ASSOC) ?: [];

} catch (PDOException $e) {
    echo "<div style='background:#fff3cd; color:#664d03; padding:15px; border:1px solid #ffecb5; margin:15px; border-radius:5px; font-family:sans-serif; font-size:14px;'>
            <b>⚠️ Gagal Memuat Data Master Asset:</b> " . htmlspecialchars($e->getMessage()) . "
          </div>";
}
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
                display: block !important; 
            }
            .offcanvas-backdrop, .offcanvas-backdrop.show {
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }
        }

        /* ==================================================== */
        /* CONFIG LAYOUT MOBILE / HP (Lebar Layar < 768px)      */
        /* ==================================================== */
        @media (max-width: 767.98px) {
            body {
                overflow: auto !important;
                position: relative !important;
                display: block !important; 
            }
            .d-md-flex {
                display: block !important; 
            }
            
            /* SOLUSI UTAMA SCROLL MOBILE: Paksa buka akses overflow-y scroll di tingkat paling luar laci */
            .offcanvas-md.sidebar-fixed {
                position: fixed !important;
                width: 280px !important;
                height: 100vh !important;
                max-height: 100vh !important;
                overflow-y: scroll !important; 
                overflow-x: hidden !important;
                -webkit-overflow-scrolling: touch !important; 
            }
            
            /* Biarkan kontainer dalam memanjang alami mengikuti scroll luar */
            .menu-scroll-container {
                max-height: none !important;
                height: auto !important;
                overflow: visible !important;
                display: block !important;
            }
            
            .main-content {
                width: 100% !important;
                margin-left: 0 !important;
                min-height: calc(100vh - 70px) !important; 
                display: block !important;
                height: auto !important; 
            }
            .offcanvas-backdrop.show {
                display: block !important;
                opacity: 0.5 !important;
                background-color: #000000 !important;
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

    <style>
    .card-clickable {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-clickable:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
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
  
  <!-- 3. AREA MENU TENGAH -->
  <?php
  $currentFile = basename($_SERVER['PHP_SELF']);

  if (!function_exists('checkActiveMenu')) {
      function checkActiveMenu($targetFile, $currentFile) {
          return ($currentFile === $targetFile) ? 'active-style' : '';
      }
  }
  ?>
  
  <div class="menu-scroll-container flex-grow-1 w-100">
      <ul class="nav flex-column mb-auto list-unstyled w-100">
          <?php include __DIR__ . '/sidebar.php'; ?>
      </ul>
  </div>

</div>

<!-- AREA UTAMA KONTEN (Gunakan pembungkus ini agar susunan halaman tidak bergeser) -->
    <main class="col-12 col-md-8 col-lg-9 ms-sm-auto ms-md-auto px-md-4 pt-4 offset-md-4 offset-lg-3">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-6">
            <h1 class="h3 fw-bold text-dark m-0 fs-4 fs-md-3">Master Data Asset</h1>
            <p class="text-muted small m-0 d-none d-sm-block">Kelola informasi merek, kategori, dan status operasional aset sistem ITAKMS.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="nav nav-pills d-inline-flex flex-wrap gap-1 bg-white p-1 rounded-3 shadow-sm" id="masterDataTabs" role="tablist">
                <button class="nav-link active rounded-3 px-2 py-1.5 px-md-3 py-md-2 fw-bold small" id="tab-brand" data-bs-toggle="tab" data-bs-target="#content-brand" type="button" role="tab">
                    <i class="bi bi-tag me-1"></i> Brands
                </button>
                <button class="nav-link rounded-3 px-2 py-1.5 px-md-3 py-md-2 fw-bold small" id="tab-category" data-bs-toggle="tab" data-bs-target="#content-category" type="button" role="tab">
                    <i class="bi bi-grid me-1"></i> Categories
                </button>
                <button class="nav-link rounded-3 px-2 py-1.5 px-md-3 py-md-2 fw-bold small" id="tab-status" data-bs-toggle="tab" data-bs-target="#content-status" type="button" role="tab">
                    <i class="bi bi-info-circle me-1"></i> Statuses
                </button>
            </div>
        </div>
    </div>
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="tab-content">
                <div class="tab-pane fade show active" id="content-brand" role="tabpanel">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 text-dark fw-bold fs-6 fs-md-5"><i class="bi bi-tag-fill me-2 text-primary"></i> Daftar Komponen Merek</h5>
                    
                    <!-- Fleksibel Otomatis: Hanya tampil jika Role memiliki izin Create ('C') di file ini -->
                    <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'C', $userRole)): ?>
                    <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 d-flex align-items-center gap-2" onclick="bukaModalPaksa('modalAddBrand')">
                        <i class="bi bi-plus-lg"></i> Tambah Brand
                    </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive w-100" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 80px;">No</th>
                                <th>Nama Brand</th>
                                <th>Status Operasional</th>
                                <th class="text-end pe-4" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($brands)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-5" style="white-space: normal;">Belum ada data brand.</td></tr>
                            <?php else: $no=1; foreach ($brands as $b): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-muted"><?= $no++; ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($b['nama']); ?></td>
                                    <td>
                                        <span class="badge <?= ($b['status'] ?? 0) == 1 ? 'bg-success-subtle text-success border-success' : 'bg-danger-subtle text-danger border-danger'; ?> border px-3 py-2 rounded-pill">
                                            <?= ($b['status'] ?? 0) == 1 ? 'Aktif' : 'Non-Aktif'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-warning border-0" onclick="prosesEditBrand(<?= $b['id']; ?>, '<?= addslashes($b['nama']); ?>', <?= $b['status']; ?>)">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger border-0" onclick="prosesHapusAssetCrud('delete_brand', <?= $b['id']; ?>, 'Hapus brand ini?')">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="content-category" role="tabpanel">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 text-dark fw-bold fs-6 fs-md-5"><i class="bi bi-grid-fill me-2 text-success"></i> Daftar Kategori Asset</h5>
                    
                    <!-- Fleksibel Otomatis: Hanya tampil jika Role memiliki izin Create ('C') di file ini -->
                    <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'C', $userRole)): ?>
                    <button type="button" class="btn btn-success btn-sm rounded-3 px-3 d-flex align-items-center gap-2" onclick="bukaModalPaksa('modalAddCategory')">
                        <i class="bi bi-plus-lg"></i> Tambah Kategori
                    </button>
                    <?php endif; ?>
                </div>
                <div class="table-responsive w-100" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 80px;">No</th>
                                <th>Nama Kategori</th>
                                <th>Aksen Warna</th>
                                <th class="text-end pe-4" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-5" style="white-space: normal;">Belum ada data kategori.</td></tr>
                            <?php else: $no=1; foreach ($categories as $c): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-muted"><?= $no++; ?></td>
                                    <td class="fw-semibold text-dark">
                                        <?php if(!empty($c['icon'])): ?><i class="<?= htmlspecialchars($c['icon']); ?> me-2 text-success"></i><?php endif; ?>
                                        <?= htmlspecialchars($c['nama']); ?>
                                    </td>
                                    <td>
                                        <span class="badge text-dark border bg-light px-3 py-2 rounded-3" style="border-left: 5px solid <?= htmlspecialchars($c['warna'] ?? '#000'); ?> !important;">
                                            <?= htmlspecialchars($c['warna'] ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-warning border-0" onclick="prosesEditCategory(<?= $c['id']; ?>, '<?= addslashes($c['nama']); ?>', '<?= addslashes($c['icon'] ?? ''); ?>', '<?= addslashes($c['warna'] ?? ''); ?>')">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger border-0" onclick="prosesHapusAssetCrud('delete_category', <?= $c['id']; ?>, 'Hapus kategori ini?')">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="content-status" role="tabpanel">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-info-circle-fill me-2 text-info"></i> Daftar Status Operasional</h5>
                    <?php if (hasCrudAccess(basename($_SERVER['PHP_SELF']), 'C', $userRole)): ?>
                    <button type="button" class="btn btn-info btn-sm text-white rounded-3 px-3 d-flex align-items-center gap-2" onclick="bukaModalPaksa('modalAddStatus')">
                        <i class="bi bi-plus-lg"></i> Tambah Status
                    </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive w-100" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 80px;">No</th>
                                <th>Nama Status</th>
                                <th>Tipe Indikator</th>
                                <th class="text-end pe-4" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($statuses)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5" style="white-space: normal;">
                                        Belum ada data status operasional.
                                    </td>
                                </tr>
                            <?php else: $no=1; foreach ($statuses as $s): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-muted"><?= $no++; ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($s['nama']); ?></td>
                                    <td>
                                        <!-- Visualisasi badge tipe status berdasarkan warna komponen bootstrap -->
                                        <span class="badge bg-<?= htmlspecialchars($s['tipe_badge'] ?? 'secondary'); ?>-subtle text-<?= htmlspecialchars($s['tipe_badge'] ?? 'secondary'); ?> border border-<?= htmlspecialchars($s['tipe_badge'] ?? 'secondary'); ?> px-3 py-2 rounded-pill">
                                            <?= htmlspecialchars($s['label_indikator'] ?? 'Default'); ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-warning border-0" onclick="prosesEditStatus(<?= $s['id']; ?>, '<?= addslashes($s['nama']); ?>', '<?= addslashes($s['tipe_badge'] ?? ''); ?>')">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger border-0" onclick="prosesHapusAssetCrud('delete_status', <?= $s['id']; ?>, 'Hapus status operasional ini?')">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
<!-- ========================================================================= -->
<!-- TAHAP 5: MODAL POPUP INPUT DATA (TAMBAH DATA MASTER ASSET)                -->
<!-- ========================================================================= -->

<!-- 1. MODAL TAMBAH BRAND -->
<div class="modal fade" id="modalAddBrand" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-tag me-2"></i> Tambah Brand Baru</h5>
                <button type="button" class="btn-close btn-close-white" onclick="tutupModalPaksa('modalAddBrand')"></button>
            </div>
            <form onsubmit="prosesTambahAssetCrud(event, 'add_brand')">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Brand / Merk</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: ASUS, Apple, Logitech" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Status Awal</label>
                        <select name="status" class="form-select" required>
                            <option value="1">Aktif</option>
                            <option value="0">Non-Aktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary px-3" onclick="tutupModalPaksa('modalAddBrand')">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. MODAL TAMBAH KATEGORI -->
<div class="modal fade" id="modalAddCategory" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-grid me-2"></i> Tambah Kategori Baru</h5>
                <button type="button" class="btn-close btn-close-white" onclick="tutupModalPaksa('modalAddCategory')"></button>
            </div>
            <form onsubmit="prosesTambahAssetCrud(event, 'add_category')">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kategori</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Laptop, Smartphone, Kursi Kerja" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Icon Class (Bootstrap Icons)</label>
                        <input type="text" name="icon" class="form-control" placeholder="Contoh: bi bi-laptop">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Warna Identifikasi (Format HEX / Nama Warna)</label>
                        <input type="text" name="warna" class="form-control" placeholder="Contoh: #0d6efd atau blue">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary px-3" onclick="tutupModalPaksa('modalAddCategory')">Batal</button>
                    <button type="submit" class="btn btn-success px-4">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. MODAL TAMBAH STATUS -->
<div class="modal fade" id="modalAddStatus" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i> Tambah Status Baru</h5>
                <button type="button" class="btn-close btn-close-white" onclick="tutupModalPaksa('modalAddStatus')"></button>
            </div>
            <form onsubmit="prosesTambahAssetCrud(event, 'add_status')">
                <div class="modal-body p-4">
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Nama Kondisi / Status</label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Tersedia, Dipinjam, Rusak" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary px-3" onclick="tutupModalPaksa('modalAddStatus')">Batal</button>
                    <button type="submit" class="btn btn-danger px-4">Simpan Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- TAHAP 6: MODAL POPUP EDIT DATA (UBAH DATA MASTER ASSET)                   -->
<!-- ========================================================================= -->

<!-- 1. MODAL EDIT BRAND -->
<div class="modal fade" id="modalEditBrand" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Brand</h5>
                <button type="button" class="btn-close" onclick="tutupModalPaksa('modalEditBrand')"></button>
            </div>
            <form onsubmit="simpanEditAssetCrud(event, 'edit_brand')">
                <input type="hidden" name="id" id="edit_b_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Brand / Merk</label>
                        <input type="text" name="nama" id="edit_b_nama" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Status Keaktifan</label>
                        <select name="status" id="edit_b_status" class="form-select" required>
                            <option value="1">Aktif</option>
                            <option value="0">Non-Aktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" onclick="tutupModalPaksa('modalEditBrand')">Batal</button>
                    <button type="submit" class="btn btn-warning px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. MODAL EDIT KATEGORI -->
<div class="modal fade" id="modalEditCategory" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Kategori</h5>
                <button type="button" class="btn-close" onclick="tutupModalPaksa('modalEditCategory')"></button>
            </div>
            <form onsubmit="simpanEditAssetCrud(event, 'edit_category')">
                <input type="hidden" name="id" id="edit_c_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kategori</label>
                        <input type="text" name="nama" id="edit_c_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Icon Class (Bootstrap Icons)</label>
                        <input type="text" name="icon" id="edit_c_icon" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Warna Identifikasi</label>
                        <input type="text" name="warna" id="edit_c_warna" class="form-control">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" onclick="tutupModalPaksa('modalEditCategory')">Batal</button>
                    <button type="submit" class="btn btn-warning px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. MODAL EDIT STATUS -->
<div class="modal fade" id="modalEditStatus" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Status</h5>
                <button type="button" class="btn-close" onclick="tutupModalPaksa('modalEditStatus')"></button>
            </div>
            <form onsubmit="simpanEditAssetCrud(event, 'edit_status')">
                <input type="hidden" name="id" id="edit_s_id">
                <div class="modal-body p-4">
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Nama Kondisi / Status</label>
                        <input type="text" name="nama" id="edit_s_nama" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" onclick="tutupModalPaksa('modalEditStatus')">Batal</button>
                    <button type="submit" class="btn btn-warning px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// 1. Fungsi Membuka Modal secara Paksa
function bukaModalPaksa(idModal) {
    const modalTarget = document.getElementById(idModal);
    if (modalTarget) {
        modalTarget.classList.add('show');
        modalTarget.style.display = 'block';
        modalTarget.removeAttribute('aria-hidden');
        modalTarget.setAttribute('aria-modal', 'true');
        modalTarget.setAttribute('role', 'dialog');
        
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'backdrop-' + idModal;
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
    }
}

// 2. Fungsi Menutup Modal secara Paksa
function tutupModalPaksa(idModal) {
    const modalTarget = document.getElementById(idModal);
    if (modalTarget) {
        modalTarget.classList.remove('show');
        modalTarget.style.display = 'none';
        modalTarget.setAttribute('aria-hidden', 'true');
        modalTarget.removeAttribute('aria-modal');
        modalTarget.removeAttribute('role');
        
        const backdrop = document.getElementById('backdrop-' + idModal);
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
    }
}

// Otomatisasi deteksi klik tombol Close bawaan Bootstrap
document.addEventListener("click", function(e) {
    if (e.target.classList.contains('btn-close') || e.target.getAttribute('data-bs-dismiss') === 'modal') {
        const modalTerbuka = e.target.closest('.modal');
        if (modalTerbuka) {
            tutupModalPaksa(modalTerbuka.id);
        }
    }
});

// 3. Fungsi Tambah Data (Create) - DIUBAH MENEMBAK KE manajemen_asset.php
function prosesTambahAssetCrud(event, aksi) {
    event.preventDefault(); 
    let payload = new FormData(event.target);
    payload.append('action', aksi); 

    fetch('manajemen_asset.php', {
        method: 'POST',
        body: payload
    })
    .then(res => {
        if (!res.ok) throw new Error('Gagal memproses data');
        location.reload(); 
    })
    .catch(err => {
        alert('Gagal menyimpan data baru.');
        console.error(err);
    });
}

// 4. Fungsi Hapus Data (Delete) - DIUBAH MENEMBAK KE manajemen_asset.php
function prosesHapusAssetCrud(aksi, idTarget, teksKonfirmasi) {
    if (!confirm(teksKonfirmasi)) return;
    let payload = new FormData();
    payload.append('action', aksi);
    payload.append('id', idTarget);

    fetch('manajemen_asset.php', {
        method: 'POST',
        body: payload
    })
    .then(res => {
        if (!res.ok) throw new Error('Gagal menghapus data');
        location.reload();
    })
    .catch(err => {
        alert('Gagal terhubung dengan server.');
    });
}

// 5. Pemicu Pengisian Data Lama ke Input Form Modal Edit (Pencocokan ID)
function prosesEditBrand(id, namaLama, statusLama) {
    document.getElementById('edit_b_id').value = id;
    document.getElementById('edit_b_nama').value = namaLama;
    document.getElementById('edit_b_status').value = statusLama;
    bukaModalPaksa('modalEditBrand');
}

function prosesEditCategory(id, namaLama, iconLama, warnaLama) {
    document.getElementById('edit_c_id').value = id;
    document.getElementById('edit_c_nama').value = namaLama;
    document.getElementById('edit_c_icon').value = iconLama;
    document.getElementById('edit_c_warna').value = warnaLama;
    bukaModalPaksa('modalEditCategory');
}

// 6. Fungsi Eksekusi Simpan Perubahan Data (Update) - DIUBAH MENEMBAK KE manajemen_asset.php
function simpanEditAssetCrud(event, aksi) {
    event.preventDefault();
    let payload = new FormData(event.target);
    payload.append('action', aksi);

    fetch('manajemen_asset.php', {
        method: 'POST',
        body: payload
    })
    .then(res => {
        if (!res.ok) throw new Error('Gagal memperbarui data');
        location.reload();
    })
    .catch(err => {
        alert('Gagal menyimpan perubahan data.');
    });
}
</script>

<?php include 'footer-admin.php'; ?>
