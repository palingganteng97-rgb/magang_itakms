<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. Konfigurasi Koneksi Database
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $status_msg = '';

    // ========================================================
    // A. LOGIKA TAMBAH DATA (CREATE)
    // ========================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
        $nama = trim($_POST['nama']);
        
        if (!empty($nama)) {
            $stmt = $conn->prepare("INSERT INTO password_categories (nama) VALUES (:nama)");
            $stmt->execute([':nama' => $nama]);
            header("Location: password_categories.php?status=success_add");
            exit;
        }
    }

    // ========================================================
    // B. LOGIKA UBAH DATA (UPDATE)
    // ========================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
        $id   = intval($_POST['id']);
        $nama = trim($_POST['nama']);
        
        if ($id > 0 && !empty($nama)) {
            $stmt = $conn->prepare("UPDATE password_categories SET nama = :nama WHERE id = :id");
            $stmt->execute([':nama' => $nama, ':id' => $id]);
            header("Location: password_categories.php?status=success_edit");
            exit;
        }
    }

    // ========================================================
    // C. LOGIKA HAPUS DATA (DELETE)
    // ========================================================
    if (isset($_GET['action']) && $_GET['action'] === 'delete') {
        $id = intval($_GET['id']);
        
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM password_categories WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header("Location: password_categories.php?status=success_delete");
            exit;
        }
    }

    // ========================================================
    // D. LOGIKA MENGAMBIL DATA (READ)
    // ========================================================
    $stmtSelect = $conn->query("SELECT * FROM password_categories ORDER BY id DESC");
    $categories = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Kesalahan Database: " . $e->getMessage());
}

// Menangkap status operasi untuk notifikasi alert sukses Bootstrap
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success_add') $status_msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Kategori berhasil disimpan!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    if ($_GET['status'] === 'success_edit') $status_msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Kategori berhasil diperbarui!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    if ($_GET['status'] === 'success_delete') $status_msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Kategori berhasil dihapus!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
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

        /* KODE FIX: Menyembunyikan batang scrollbar untuk Chrome, Safari, dan Opera */
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
    <!-- Area Menu Tengah Mobile (Ditambahkan class hide-scrollbar) -->
    <div class="flex-grow-1 overflow-y-auto p-3 hide-scrollbar">
      <ul class="nav flex-column gap-2">
        <!-- Dashboard Aktif di Mobile -->
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
        <li class="nav-item">
          <a href="manajemen_asset.php" class="nav-link text-white p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
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
        <!-- VENDORS (Mobile) -->
        <li class="nav-item">
          <a href="vendors.php" class="nav-link text-white p-2 rounded">
            <i class="bi bi-building me-2"></i> Vendors
          </a>
        </li>
        <!-- Tambahkan di bawah menu Network Port -->
        <li class="nav-item">
          <a href="password_categories.php" class="nav-link active bg-primary text-white p-2 rounded">
            <i class="bi bi-grid-fill me-2"></i> Password Categories
          </a>
        </li>
        <li class="nav-item">
          <a href="password_vault.php" class="nav-link text-white p-2 rounded">
            <i class="bi bi-safe me-2"></i> Password Vault
          </a>
        </li>
        <!-- MENU TIKETS -->
        <li class="nav-item">
          <a href="tickets.php" class="nav-link <?= ($currentPage == 'tickets.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-ticket-perforated-fill me-2"></i> Tikets
          </a>
        </li>
        <!-- MAINTENANCE -->
        <li class="nav-item">
        <a href="maintenance.php" class="nav-link text-white p-2 rounded">
            <i class="bi bi-wrench-adjustable-circle me-2"></i> Maintenance
        </a>
        </li>
        <!-- KNOWLEDGE CATEGORIES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_categories.php" class="nav-link <?= ($currentPage == 'knowledge_categories.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-tags-fill me-2"></i> <span>Knowledge Categories</span>
            </a> 
        </li> 
        <!-- KNOWLEDGE ARTICLES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_articles.php" class="nav-link <?= ($currentPage == 'knowledge_articles.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-file-earmark-text-fill me-2"></i> <span>Knowledge Articles</span>
            </a> 
        </li> 
        <!-- SOFTWARE LICENSES (Khusus Mobile & Device Kecil) --> 
        <li class="nav-item d-md-none"> 
            <a href="software_licenses.php" class="nav-link <?= ($currentPage == 'software_licenses.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded d-flex align-items-center"> 
                <i class="bi bi-key-fill me-2"></i> 
                <span>Software Licenses</span> 
            </a> 
        </li> 
        <!-- USER PROFIL (Mobile) -->
        <li class="nav-item">
          <a href="user.php" class="nav-link text-white p-2 rounded">
            <i class="bi bi-person-fill me-2"></i> User Profil
          </a>
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
<!-- 2. SIDEBAR DESKTOP & MAIN CONTENT WRAPPER  -->
<!-- ========================================== -->
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar Desktop dengan Tinggi Layar Terkunci Permanen 100vh & Posisi Statis Fixed -->
    <nav class="col-md-4 col-lg-3 d-none d-md-flex flex-column sidebar p-3 text-bg-dark" style="min-height: 100vh; max-height: 100vh; position: fixed; z-index: 1000;">
      <!-- Judul Utama Dashboard Desktop (Tetap Diam) -->
      <h4 class="text-center mb-4 text-warning fw-bold pt-2"><i class="bi bi-speedometer2"></i> ITAKMS</h4>
      
      <!-- Area Menu Tengah Desktop -->
      <div class="flex-grow-1 overflow-y-auto hide-scrollbar" style="max-height: calc(100vh - 160px);">
        <ul class="nav flex-column gap-2">
          <!-- Dashboard -->
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
          <!-- USER PROFIL -->
          <li class="nav-item">
            <a href="assets.php" class="nav-link text-white p-2 rounded"><i class="bi bi-folder2-open me-2"></i> Assets</a>
          </li>
          <li class="nav-item">
            <a href="manajemen_asset.php" class="nav-link text-white p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
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
          <!-- VENDORS -->
          <li class="nav-item">
            <a href="vendors.php" class="nav-link text-white p-2 rounded"><i class="bi bi-building me-2"></i> Vendors</a>
          </li>
          <!-- PASSWORD CATEGORIES (AKTIF DI DESKTOP) -->
          <li class="nav-item">
            <a href="password_categories.php" class="nav-link active bg-primary text-white p-2 rounded">
              <i class="bi bi-grid-fill me-2"></i> Password Categories
            </a>
          </li>
          <!-- PASSWORD VAULT -->
          <li class="nav-item">
            <a href="password_vault.php" class="nav-link text-white p-2 rounded"><i class="bi bi-safe me-2"></i> Password Vault</a>
          </li>
          <!-- MENU TIKETS -->
        <li class="nav-item">
          <a href="tickets.php" class="nav-link <?= ($currentPage == 'tickets.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-ticket-perforated-fill me-2"></i> Tikets
          </a>
        </li>
        <!-- MAINTENANCE -->
        <li class="nav-item">
          <a href="maintenance.php" class="nav-link <?= ($currentPage == 'maintenance.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-wrench-adjustable-circle me-2"></i> Maintenance
          </a>
        </li>
        <!-- KNOWLEDGE CATEGORIES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_categories.php" class="nav-link <?= ($currentPage == 'knowledge_categories.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-tags-fill me-2"></i> <span>Knowledge Categories</span>
            </a> 
        </li> 
        <!-- KNOWLEDGE ARTICLES (Tampil di semua device) --> 
        <li class="nav-item"> 
            <a href="knowledge_articles.php" class="nav-link <?= ($currentPage == 'knowledge_articles.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded"> 
                <i class="bi bi-file-earmark-text-fill me-2"></i> <span>Knowledge Articles</span>
            </a> 
        </li> 
        <!-- SOFTWARE LICENSES (Langsung tampil di Desktop & Mobile) --> 
        <li class="nav-item"> 
            <a href="software_licenses.php" class="nav-link <?= ($currentPage == 'software_licenses.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded d-flex align-items-center"> 
                <i class="bi bi-key-fill me-2"></i> 
                <span>Software Licenses</span> 
            </a> 
        </li> 
          <!-- USER PROFIL -->
          <li class="nav-item">
            <a href="user.php" class="nav-link text-white p-2 rounded"><i class="bi bi-person-fill me-2"></i> User Profil</a>
          </li>
        </ul>
      </div>
      
      <!-- Tombol Logout Desktop (Mengunci di bagian dasar bawah sidebar) -->
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

    <!-- ========================================== -->
    <!-- 3. MAIN CONTENT KATEGORI (RESPONSIVE)     -->
    <!-- ========================================== -->
    <main class="col-12 col-md-8 col-lg-9 ms-md-auto px-2 px-md-4 py-4" style="min-height: 100vh; background-color: #ffffff !important;">
      
      <!-- Header Konten Utama -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 border-bottom border-light-subtle pb-3">
        <div>
          <h3 class="fw-bold mb-1 text-dark fs-4 fs-md-3">
            <i class="bi bi-grid-fill text-primary me-2"></i> Password Categories
          </h3>
          <small class="text-secondary d-block">Menampilkan daftar pengelompokan kategori kata sandi sistem</small>
        </div>
        <!-- Badge Informasi Total Data Kategori -->
        <span class="badge bg-primary px-3 py-2 fs-6">
          Total: <?= count($categories); ?> Kategori
        </span>
      </div>

      <!-- Menampilkan Alert Status CRUD Jika Ada -->
      <?php if (!empty($status_msg)) echo $status_msg; ?>

      <!-- Tombol Tambah Data -->
      <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
          <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
      </button>

      <!-- Card Box untuk Tabel Data -->
      <div class="card bg-white text-dark border-light-subtle shadow-sm mb-4">
        <div class="card-header bg-light border-light-subtle d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
          <h5 class="card-title mb-0 fw-semibold text-primary fs-6 fs-md-5">
            <i class="bi bi-table me-2"></i> Data Kategori Kata Sandi
          </h5>
          <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Mode CRUD Terintegrasi</span>
        </div>
        
        <div class="card-body p-0">
          <div class="table-responsive">
            <!-- text-nowrap menjaga agar seluruh data dan aksi berjajar horizontal lurus di layar HP -->
            <table class="table table-striped table-hover align-middle mb-0 table-sm table-md-normal text-nowrap" style="border-color: #dee2e6;">
              <thead class="table-light text-dark fw-bold">
                <tr>
                  <th scope="col" class="text-center" style="width: 70px;">No</th>
                  <th scope="col" style="width: 200px;">ID Kategori</th>
                  <th scope="col">Nama Kategori</th>
                  <th scope="col" class="text-center" style="width: 200px;">Aksi</th>
                </tr>
              </thead>
              <tbody class="text-dark">
                <?php if (empty($categories)): ?>
                  <tr>
                    <td colspan="4" class="text-center py-5 text-secondary text-wrap">
                      <i class="bi bi-folder-x fs-1 d-block mb-2 text-muted"></i>
                      Belum ada data kategori yang tersedia di database.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php $no = 1; foreach ($categories as $cat): ?>
                    <tr>
                      <td class="text-center fw-semibold text-secondary"><?= $no++; ?></td>
                      <td>
                        <code class="text-primary bg-primary-subtle bg-opacity-25 px-2 py-1 rounded">#<?= htmlspecialchars($cat['id']); ?></code>
                      </td>
                      <td class="fw-medium text-dark text-wrap">
                        <?= htmlspecialchars($cat['nama']); ?>
                      </td>
                      <td class="text-center">
                        <!-- Grouping tombol aksi agar sejajar rapi horizontal -->
                        <div class="d-inline-flex gap-1">
                          <!-- Tombol Edit -->
                          <button class="btn btn-warning btn-sm fw-medium" data-bs-toggle="modal" data-bs-target="#modalEditKategori<?= $cat['id']; ?>">
                            <i class="bi bi-pencil-square"></i> Edit
                          </button>
                          <!-- Tombol Hapus -->
                          <a href="password_categories.php?action=delete&id=<?= $cat['id']; ?>" class="btn btn-danger btn-sm fw-medium" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
                            <i class="bi bi-trash"></i> Hapus
                          </a>
                        </div>
                      </td>
                    </tr>

                    <!-- ========================================== -->
                    <!-- MODAL EDIT KATEGORI (DINAMIS PER BARIS)   -->
                    <!-- ========================================== -->
                    <div class="modal fade text-wrap" id="modalEditKategori<?= $cat['id']; ?>" tabindex="-1" aria-labelledby="modalEditKategoriLabel<?= $cat['id']; ?>" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-white text-dark shadow">
                          <div class="modal-header bg-light border-bottom">
                            <h5 class="modal-title fw-bold text-primary" id="modalEditKategoriLabel<?= $cat['id']; ?>">
                              <i class="bi bi-pencil-square me-2"></i>Edit Kategori
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <form action="password_categories.php" method="POST">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $cat['id']; ?>">
                            <div class="modal-body p-4">
                              <div class="mb-0">
                                <label class="form-label fw-semibold text-secondary small">Nama Kategori / Kelompok Kata Sandi <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($cat['nama']); ?>" required>
                              </div>
                            </div>
                            <div class="modal-footer bg-light border-top flex-nowrap">
                              <button type="button" class="btn btn-sm btn-secondary w-50" data-bs-dismiss="modal">Batal</button>
                              <button type="submit" class="btn btn-sm btn-warning w-50 fw-semibold text-dark"><i class="bi bi-check-circle me-1"></i>Simpan Perubahan</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>

                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </main>

    <!-- ========================================== -->
    <!-- MODAL POP-UP INPUT TAMBAH KATEGORI BARU   -->
    <!-- ========================================== -->
    <div class="modal fade text-wrap" id="modalTambahKategori" tabindex="-1" aria-labelledby="modalTambahKategoriLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white text-dark shadow">
          <div class="modal-header bg-light border-bottom">
            <h5 class="modal-title fw-bold text-primary" id="modalTambahKategoriLabel">
              <i class="bi bi-plus-square-fill me-2"></i>Tambah Kategori Baru
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form action="password_categories.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-body p-4">
              <div class="mb-0">
                <label class="form-label fw-semibold text-secondary small">Nama Kategori / Kelompok Kata Sandi <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control" placeholder="Contoh: Email Kantor, Server Cpanel, Winbox Router" required>
              </div>
            </div>
            <div class="modal-footer bg-light border-top flex-nowrap">
              <button type="button" class="btn btn-sm btn-secondary w-50" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-sm btn-primary w-50 fw-semibold"><i class="bi bi-check-circle me-1"></i>Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
<!-- MODAL EDIT KATEGORI (DINAMIS PER BARIS)   -->
<!-- ========================================== -->
<div class="modal fade text-wrap" id="modalEditKategori<?= $cat['id']; ?>" tabindex="-1" aria-labelledby="modalEditKategoriLabel<?= $cat['id']; ?>" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-white text-dark shadow">
      
      <!-- Header Modal -->
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold text-primary" id="modalEditKategoriLabel<?= $cat['id']; ?>">
          <i class="bi bi-pencil-square me-2"></i>Edit Kategori
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Form Input Kirim ke CRUD PHP -->
      <form action="password_categories.php" method="POST">
        <!-- Input hidden untuk penanda aksi UPDATE dan ID target -->
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $cat['id']; ?>">
        
        <div class="modal-body p-4">
          <div class="mb-0">
            <label class="form-label fw-semibold text-secondary small">Nama Kategori / Kelompok Kata Sandi <span class="text-danger">*</span></label>
            <!-- Form otomatis terisi nama kategori lama dari database -->
            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($cat['nama']); ?>" required>
          </div>
        </div>
        
        <!-- Footer Tombol Aksi Modal -->
        <div class="modal-footer bg-light border-top flex-nowrap">
          <button type="button" class="btn btn-sm btn-secondary w-50" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-warning w-50 fw-semibold text-dark"><i class="bi bi-check-circle me-1"></i>Simpan Perubahan</button>
        </div>
      </form>

    </div>
  </div>
</div>

    <!-- ========================================== -->
    <!-- SCRIPT JAVASCRIPT UTAMA KATEGORI           -->
    <!-- ========================================== -->
    <script>
    // 1. MEMBERSIHKAN PARAMETER NOTIFIKASI DI URL SAAT HALAMAN DI-REFRESH
    // Berfungsi agar alert tidak terus muncul berulang kali ketika user me-refresh browser
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.pathname);
    }

    // BONUS: FUNGSI LIVE SEARCH UNTUK MENYARING DATA TABEL SECARA INSTAN
    // Jika Anda ingin mengaktifkannya, cukup tambahkan sebuah input search bar di atas tabel dengan id="searchKategori"
    const searchInput = document.getElementById('searchKategori');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                // Memeriksa kolom Nama Kategori (kolom ke-3 atau index 2)
                const categoryCell = row.cells[2];
                if (categoryCell) {
                    const textValue = categoryCell.textContent || categoryCell.innerText;
                    if (textValue.toLowerCase().indexOf(filter) > -1) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                }
            });
        });
    }
    </script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
