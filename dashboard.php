<?php
require_once __DIR__ . '/auth.php';
require_login();

// 1. Konfigurasi Database
$host = "10.10.6.59";
$username = "root_host";
$password = "password";
$database = "magang_itakms";

// Pagination sederhana untuk tabel
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Statistik (ambil agregat saja, bukan fetchAll semua data)
    $stmtStats = $conn->prepare("
        SELECT
            COUNT(*) AS total_users,
            SUM(status = 1) AS total_aktif
        FROM users
    ");
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

    $total_users = (int)($stats['total_users'] ?? 0);
    $total_aktif = (int)($stats['total_aktif'] ?? 0);
    $total_non_aktif = $total_users - $total_aktif;

// 3. (Tidak ada query tabel users, dashboard hanya menampilkan statistik)

} catch(PDOException $e) {
    echo "Koneksi gagal: " . $e->getMessage();
    die();
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
          <a href="dashboard.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
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
          <a href="vendors.php" class="nav-link <?= ($currentPage == 'vendors.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-building me-2"></i> Vendors <!-- Tetap ikon gedung mitra bisnis -->
          </a>
        </li>
        <!-- Tambahkan di bawah menu Network Port atau di posisi yang Anda inginkan -->
        <li class="nav-item">
          <a href="password_categories.php" class="nav-link text-white p-2 rounded">
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
          <a href="user.php" class="nav-link <?= ($currentPage == 'user.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
            <i class="bi bi-person-fill me-2"></i> User Profil <!-- PERBAIKAN: Menggunakan ikon orang murni sesuai keinginan Anda -->
          </a>
        </li>
      </ul>
    </div>
    
    <!-- Tombol Logout Mobile (Mengunci di Posisi Dasar Bawah) -->
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
      
      <!-- Area Menu Tengah Desktop (Ditambahkan class hide-scrollbar dan menghapus padding kanan pr-1) -->
      <div class="flex-grow-1 overflow-y-auto hide-scrollbar" style="max-height: calc(100vh - 160px);">
        <ul class="nav flex-column gap-2">
          <!-- Dashboard Aktif di Desktop -->
          <li class="nav-item">
            <a href="dashboard.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-house-door me-2"></i> Dashboard</a>
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
          <!-- VENDORS (Desktop) -->
          <li class="nav-item">
            <a href="vendors.php" class="nav-link <?= ($currentPage == 'vendors.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
              <i class="bi bi-building me-2"></i> Vendors
            </a>
          </li>
          <!-- Tambahkan di bawah menu Network Port atau di posisi yang Anda inginkan -->
        <li class="nav-item">
          <a href="password_categories.php" class="nav-link text-white p-2 rounded">
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
          <!-- USER PROFIL (Desktop) -->
          <li class="nav-item">
            <a href="user.php" class="nav-link <?= ($currentPage == 'user.php') ? 'active bg-primary text-white' : 'text-white'; ?> p-2 rounded">
              <i class="bi bi-person-fill me-2"></i> User Profil
            </a>
          </li>
        </ul>
      </div>
      
      <!-- Tombol Logout Desktop (Mengunci Mengikuti Batas Layar Bawah) -->
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

    <!-- AREA UTAMA KONTEN (Gunakan pembungkus ini agar susunan halaman tidak bergeser tertimpa sidebar) -->
    <main class="col-md-8 ms-sm-auto col-lg-9 px-md-4 pt-4 offset-md-4 offset-lg-3">

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard Sistem</h1>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary p-2">Sesi Admin</span>
                </div>
            </div>

            <!-- STATISTIC CARDS (Ringkasan Data) -->
            <div class="row mb-4 gx-2">
                <div class="col-md-4">
                    <div class="card bg-primary text-white mb-3 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">Total Users</h6>
                                <h2 class="card-text fw-bold"><?= $total_users ?></h2>
                            </div>
                            <i class="bi bi-people fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-success text-white mb-3 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">User Aktif</h6>
                                <h2 class="card-text fw-bold"><?= $total_aktif ?></h2>
                            </div>
                            <i class="bi bi-person-check fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-danger text-white mb-3 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white-50">User Non-Aktif</h6>
                                <h2 class="card-text fw-bold"><?= $total_non_aktif ?></h2>
                            </div>
                            <i class="bi bi-person-x fs-1 text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>


</main>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
