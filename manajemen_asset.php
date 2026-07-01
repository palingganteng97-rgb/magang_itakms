<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. KONFIGURASI DATABASE UTAMA (MANDIRI & INSTAN)
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

    // 2. EKSEKUSI QUERY TUNGGAL DENGAN LIMIT (SANGAT RINGAN)
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
    // Peringatan jika ada masalah query/jaringan tanpa membuat halaman menjadi blank putih
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
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #212529; color: white; }
        .sidebar a { color: #adb5bd; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #343a40; }

        /* Menyembunyikan batang scrollbar untuk Chrome, Safari, dan Opera */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        /* Menyembunyikan batang scrollbar untuk Firefox dan IE/Edge */
        .hide-scrollbar {
            -ms-overflow-style: none;  /* IE dan Edge */
            scrollbar-width: none;  /* Firefox */
        }
    </style>
</head>
<body>

<!-- TOPBAR MOBILE -->
<nav class="navbar navbar-dark bg-dark d-md-none px-3 shadow">
    <div class="d-flex align-items-center justify-content-between w-100">
        <span class="navbar-brand text-warning fw-bold"><i class="bi bi-speedometer2"></i> ITAKMS</span>
        <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
            <i class="bi bi-list"></i>
        </button>
    </div>
</nav>

<!-- ========================================== -->
<!-- 1. SIDEBAR MOBILE (OFFCANVAS)              -->
<!-- ========================================== -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
  <!-- Header Mobile (Tetap Diam di Atas) -->
  <div class="offcanvas-header border-bottom border-secondary">
    <h5 class="offcanvas-title" id="mobileSidebarLabel"><i class="bi bi-speedometer2 text-warning me-2"></i> ITAKMS</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  
  <!-- Body Offcanvas (Pembagian Layout Flex Vertikal) -->
  <div class="offcanvas-body p-0 d-flex flex-column" style="height: calc(100vh - 56px);">
    <!-- Area Menu Tengah Mobile (Scrollbar Tersembunyi) -->
    <div class="flex-grow-1 overflow-y-auto p-3 hide-scrollbar">
      <ul class="nav flex-column gap-2">
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link text-white p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a href="roles.php" class="nav-link text-white p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
        </li>
        <li class="nav-item">
          <a href="relasi.php" class="nav-link text-white p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;">
            <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
          </a>
        </li>
        <li class="nav-item">
          <a href="assets.php" class="nav-link text-white p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
        </li>
        <!-- Manajemen Asset Aktif di Mobile -->
        <li class="nav-item">
          <a href="manajemen_asset.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
        </li>
        <li class="nav-item">
          <a href="asset_movements.php" class="nav-link text-white p-2 rounded"><i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan</a>
        </li>
        <li class="nav-item">
          <a href="server.php" class="nav-link text-white p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
        </li>
        <li class="nav-item">
          <a href="network_device.php" class="nav-link text-white p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
        </li>
        <li class="nav-item">
          <a href="network_port.php" class="nav-link text-white p-2 rounded"><i class="bi bi-ethernet me-2"></i> Network Port</a>
        </li>
        <li class="nav-item">
          <a href="user.php" class="nav-link text-white p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
        </li>
      </ul>
    </div>
    
    <!-- Tombol Logout Mobile -->
    <div class="mt-auto p-3 border-top border-secondary bg-dark w-100">
      <ul class="nav flex-column gap-2">
        <li class="nav-item">
          <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
            <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 2. SIDEBAR DESKTOP                         -->
<!-- ========================================== -->
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar Desktop dengan Tinggi Layar Terkunci Permanen 100vh & Posisi Statis Fixed -->
    <nav class="col-md-4 col-lg-3 d-none d-md-flex flex-column sidebar p-3 text-bg-dark" style="min-height: 100vh; max-height: 100vh; position: fixed; z-index: 1000;">
      <!-- Judul Utama Dashboard Desktop (Tetap Diam) -->
      <h4 class="text-center mb-4 text-warning fw-bold pt-2"><i class="bi bi-speedometer2"></i> ITAKMS</h4>
      
      <!-- Area Menu Tengah Desktop (Scrollbar Tersembunyi) -->
      <div class="flex-grow-1 overflow-y-auto hide-scrollbar" style="max-height: calc(100vh - 160px);">
        <ul class="nav flex-column gap-2">
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link text-white p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a href="roles.php" class="nav-link text-white p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
          </li>
          <li class="nav-item">
            <a href="relasi.php" class="nav-link text-white p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;" title="Manajemen Bangunan & Ruang">
              <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
            </a>
          </li>
          <li class="nav-item">
            <a href="assets.php" class="nav-link text-white p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
          </li>
          <!-- Manajemen Asset Aktif di Desktop -->
          <li class="nav-item">
            <a href="manajemen_asset.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
          </li>
          <li class="nav-item">
            <a href="asset_movements.php" class="nav-link text-white p-2 rounded"><i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan</a>
          </li>
          <li class="nav-item">
            <a href="server.php" class="nav-link text-white p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
          </li>
          <li class="nav-item">
            <a href="network_device.php" class="nav-link text-white p-2 rounded"><i class="bi bi-router me-2"></i> Network Device</a>
          </li>
          <li class="nav-item">
            <a href="network_port.php" class="nav-link text-white p-2 rounded"><i class="bi bi-ethernet me-2"></i> Network Port</a>
          </li>
          <li class="nav-item">
            <a href="user.php" class="nav-link text-white p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
          </li>
        </ul>
      </div>
      
      <!-- Tombol Logout Desktop -->
      <div class="mt-auto pt-3 border-top border-secondary w-100 bg-dark">
        <ul class="nav flex-column gap-2">
          <li class="nav-item">
            <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
              <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </nav>

<!-- AREA UTAMA KONTEN (Gunakan pembungkus ini agar susunan halaman tidak bergeser) -->
    <!-- PERBAIKAN: Menggunakan col-12 agar lebar konten penuh 100% pada tampilan ponsel pintar -->
    <main class="col-12 col-md-8 col-lg-9 ms-sm-auto ms-md-auto px-md-4 pt-4 offset-md-4 offset-lg-3">

    <!-- 1. Banner Header Halaman & Tombol Tab -->
    <!-- PERBAIKAN: Mengatur gap dan susunan kolom agar rapi saat bertumpuk vertikal di HP -->
    <div class="row align-items-center mb-4 g-3">
        <div class="col-md-6">
            <h1 class="h3 fw-bold text-dark m-0 fs-4 fs-md-3">Master Data Asset</h1>
            <p class="text-muted small m-0 d-none d-sm-block">Kelola informasi merek, kategori, dan status operasional aset sistem ITAKMS.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <!-- Navigasi Tab Gaya Modern -->
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

    <!-- 2. Wadah Konten Tabel Utama -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="tab-content">
            
            <!-- ========================================== -->
            <!-- PANEL TAB 1: ASSET BRANDS                  -->
            <!-- ========================================== -->
            <div class="tab-pane fade show active" id="content-brand" role="tabpanel">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 text-dark fw-bold fs-6 fs-md-5"><i class="bi bi-tag-fill me-2 text-primary"></i> Daftar Komponen Merek</h5>
                    <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 d-flex align-items-center gap-2" onclick="bukaModalPaksa('modalAddBrand')">
                        <i class="bi bi-plus-lg"></i> Tambah Brand
                    </button>
                </div>
                <!-- PERBAIKAN: Menambahkan aturan lebar penuh dan overflow-x agar tabel brand bisa di-scroll horizontal -->
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

            <!-- ========================================== -->
            <!-- PANEL TAB 2: ASSET CATEGORIES (SAMBUNGAN)  -->
            <!-- ========================================== -->
            <div class="tab-pane fade" id="content-category" role="tabpanel">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 text-dark fw-bold fs-6 fs-md-5"><i class="bi bi-grid-fill me-2 text-success"></i> Daftar Kategori Asset</h5>
                    <button type="button" class="btn btn-success btn-sm rounded-3 px-3 d-flex align-items-center gap-2" onclick="bukaModalPaksa('modalAddCategory')">
                        <i class="bi bi-plus-lg"></i> Tambah Kategori
                    </button>
                </div>
                <!-- PERBAIKAN: Menambahkan aturan lebar penuh dan overflow-x agar tabel kategori bisa di-scroll horizontal -->
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
                                            <!-- BERHASIL DISAMBUNGKAN -->
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

            <!-- ========================================== -->
            <!-- PANEL TAB 3: ASSET STATUSES                -->
            <!-- ========================================== -->
            <div class="tab-pane fade" id="content-status" role="tabpanel">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-info-circle-fill me-2 text-info"></i> Daftar Status Operasional</h5>
                    <button type="button" class="btn btn-info btn-sm text-white rounded-3 px-3 d-flex align-items-center gap-2" onclick="bukaModalPaksa('modalAddStatus')">
                        <i class="bi bi-plus-lg"></i> Tambah Status
                    </button>
                </div>
                <!-- PERBAIKAN RESPONSIVE MOBILE: Ditambahkan text-nowrap agar kolom stabil saat di-scroll -->
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

        </div> <!-- /.tab-content -->
    </div> <!-- /.card -->

</main> <!-- /PERBAIKAN: Tag penutup area utama konten -->
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

<!-- ========================================================================= -->
<!-- TAHAP 7: LOGIKA JAVASCRIPT UTAMA AJAX CRUD ASSETS                         -->
<!-- ========================================================================= -->
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

// 3. Fungsi Tambah Data (Create) - Mengarah ke proses_asset.php
function prosesTambahAssetCrud(event, aksi) {
    event.preventDefault(); 
    let payload = new FormData(event.target);
    payload.append('action', aksi); 

    fetch('proses_asset.php', {
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

// 4. Fungsi Hapus Data (Delete)
function prosesHapusAssetCrud(aksi, idTarget, teksKonfirmasi) {
    if (!confirm(teksKonfirmasi)) return;
    let payload = new FormData();
    payload.append('action', aksi);
    payload.append('id', idTarget);

    fetch('proses_asset.php', {
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

// 6. Fungsi Eksekusi Simpan Perubahan Data (Update)
function simpanEditAssetCrud(event, aksi) {
    event.preventDefault();
    let payload = new FormData(event.target);
    payload.append('action', aksi);

    fetch('proses_asset.php', {
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
