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
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar a { color: #adb5bd; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: #343a40; }
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

<!-- SIDEBAR MOBILE (OFFCANVAS) -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileSidebarLabel"><i class="bi bi-speedometer2"></i> ITAKMS</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="sidebar p-3 d-flex flex-column" style="min-height: calc(100vh - 56px);">
            <ul class="nav flex-column gap-2">
                <!-- 1. Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
                </li>
                <!-- 2. Manajemen Roles -->
                <li class="nav-item">
                    <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
                </li>
                <!-- 3. Manajemen Bangunan & Ruang -->
                <li class="nav-item">
                    <a href="relasi.php" class="nav-link p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;">
                        <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
                    </a>
                </li>
                <!-- 4. Assets (Opsi Baru) -->
                <li class="nav-item">
                    <a href="asset.php" class="nav-link active p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
                </li>
                <!-- 5. User Profil -->
                <li class="nav-item">
                    <a href="user.php" class="nav-link p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
                </li>
            </ul>
            <div class="mt-auto pt-3">
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
                            <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>

<!-- SIDEBAR DESKTOP -->
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-4 col-lg-3 d-none d-md-flex flex-column sidebar p-3">
            <h4 class="text-center mb-4 text-warning"><i class="bi bi-speedometer2"></i> ITAKMS</h4>
            <ul class="nav flex-column gap-2">
                <!-- 1. Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
                </li>
                <!-- 2. Manajemen Roles -->
                <li class="nav-item">
                    <a href="roles.php" class="nav-link p-2 rounded"><i class="bi bi-shield-lock me-2"></i> Manajemen Roles</a>
                </li>
                <!-- 3. Manajemen Bangunan & Ruang -->
                <li class="nav-item">
                    <a href="relasi.php" class="nav-link p-2 rounded text-nowrap" style="overflow: hidden; text-overflow: ellipsis;" title="Manajemen Bangunan & Ruang">
                        <i class="bi bi-diagram-3 me-2"></i> Manajemen Bangunan & Ruang
                    </a>
                </li>
                <!-- 4. Assets (Opsi Baru) -->
                <li class="nav-item">
                    <a href="asset.php" class="nav-link active p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Assets</a>
                </li>
                <!-- 5. User Profil -->
                <li class="nav-item">
                    <a href="user.php" class="nav-link p-2 rounded"><i class="bi bi-person-lines-fill me-2"></i> User Profil</a>
                </li>
            </ul>
            <div class="mt-auto pt-3 border-top border-secondary w-100">
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link p-2 rounded" style="color:#dc3545 !important;">
                            <i class="bi bi-box-arrow-right me-2" style="color:#dc3545 !important;"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <!-- ========================================================================= -->
        <!-- TAHAP 1: PONDASI PEMBUNGKUS KONTEN UTAMA KANAN                           -->
        <!-- ========================================================================= -->
        <main class="col-md-8 col-lg-9 px-md-4 pt-4">
            
            <!-- Judul Halaman Atas -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 fw-bold text-dark">Manajemen Data Master Asset</h1>
            </div>

            <!-- Baris Utama untuk menampung 3 Kolom Master Data ke samping -->
            <div class="row g-4 mt-2">

                <!-- ========================================== -->
                <!-- TAHAP 2: KOLOM 1 - ASSET BRANDS (MERK)     -->
                <!-- ========================================== -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                            <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-tag me-2 text-primary"></i> Asset Brands</h5>
                            <button type="button" class="btn btn-primary btn-sm rounded-circle px-2 py-1" onclick="bukaModalPaksa('modalAddBrand')">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        <div class="card-body p-3 overflow-auto" style="max-height: 500px;">
                            <?php if (empty($brands)): ?>
                                <div class="text-center text-muted py-4">Belum ada data brand.</div>
                            <?php else: ?>
                                <?php foreach ($brands as $b): ?>
                                    <div class="p-3 mb-3 bg-white rounded border d-flex justify-content-between align-items-center shadow-sm">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($b['nama']); ?></h6>
                                            <small class="text-muted">
                                                Status: 
                                                <span class="badge <?= ($b['status'] ?? 0) == 1 ? 'bg-success-subtle text-success border-success' : 'bg-danger-subtle text-danger border-danger'; ?> border px-2">
                                                    <?= ($b['status'] ?? 0) == 1 ? 'Aktif' : 'Non-Aktif'; ?>
                                                </span>
                                            </small>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-warning border-0" onclick="prosesEditBrand(<?= $b['id']; ?>, '<?= addslashes($b['nama']); ?>', <?= $b['status']; ?>)"><i class="bi bi-pencil-square"></i></button>
                                            <button type="button" class="btn btn-outline-danger border-0" onclick="prosesHapusAssetCrud('delete_brand', <?= $b['id']; ?>, 'Hapus brand ini?')"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- TAHAP 3: KOLOM 2 - ASSET CATEGORIES        -->
                <!-- ========================================== -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                            <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-grid me-2 text-success"></i> Asset Categories</h5>
                            <button type="button" class="btn btn-success btn-sm rounded-circle px-2 py-1" onclick="bukaModalPaksa('modalAddCategory')">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        <div class="card-body p-3 overflow-auto" style="max-height: 500px;">
                            <?php if (empty($categories)): ?>
                                <div class="text-center text-muted py-4">Belum ada data kategori.</div>
                            <?php else: ?>
                                <?php foreach ($categories as $c): ?>
                                    <div class="p-3 mb-3 bg-white rounded border d-flex justify-content-between align-items-center shadow-sm">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-dark">
                                                <?php if(!empty($c['icon'])): ?><i class="<?= htmlspecialchars($c['icon']); ?> me-1"></i><?php endif; ?>
                                                <?= htmlspecialchars($c['nama']); ?>
                                            </h6>
                                            <small class="text-muted d-block">
                                                Warna: <span class="badge text-dark border bg-light" style="border-left: 5px solid <?= htmlspecialchars($c['warna'] ?? '#000'); ?> !important;"><?= htmlspecialchars($c['warna'] ?? '-'); ?></span>
                                            </small>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-warning border-0" onclick="prosesEditCategory(<?= $c['id']; ?>, '<?= addslashes($c['nama']); ?>', '<?= addslashes($c['icon'] ?? ''); ?>', '<?= addslashes($c['warna'] ?? ''); ?>')"><i class="bi bi-pencil-square"></i></button>
                                            <button type="button" class="btn btn-outline-danger border-0" onclick="prosesHapusAssetCrud('delete_category', <?= $c['id']; ?>, 'Hapus kategori ini?')"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ========================================================== -->
                <!-- TAHAP 4: KOLOM 3 - ASSET STATUSES (STATUS)                 -->
                <!-- ========================================================== -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                            <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-info-circle me-2 text-danger"></i> Asset Statuses</h5>
                            <button type="button" class="btn btn-danger btn-sm rounded-circle px-2 py-1" onclick="bukaModalPaksa('modalAddStatus')">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        <div class="card-body p-3 overflow-auto" style="max-height: 500px;">
                            <?php if (empty($statuses)): ?>
                                <div class="text-center text-muted py-4">Belum ada data status.</div>
                            <?php else: ?>
                                <?php foreach ($statuses as $s): ?>
                                    <div class="p-3 mb-3 bg-white rounded border d-flex justify-content-between align-items-center shadow-sm">
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($s['nama']); ?></h6>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-warning border-0" onclick="prosesEditStatus(<?= $s['id']; ?>, '<?= addslashes($s['nama']); ?>')"><i class="bi bi-pencil-square"></i></button>
                                            <button type="button" class="btn btn-outline-danger border-0" onclick="prosesHapusAssetCrud('delete_status', <?= $s['id']; ?>, 'Hapus status ini?')"><i class="bi bi-trash3"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div> <!-- Penutup Row Grid Utama -->
        </main> <!-- Penutup Konten Utama Kanan -->

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
