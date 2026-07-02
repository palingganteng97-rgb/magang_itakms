<?php
require_once __DIR__ . '/auth.php';
require_login();

// Menggunakan koneksi database terpusat proyek Anda
require_once __DIR__ . '/db.php'; 

// Konfigurasi batasan data per halaman
$perPage = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Menangkap kata kunci dari form pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // 1. Ambil daftar ID saja dari tabel assets untuk dropdown modal
    $stmtAllAssets = $conn->prepare("SELECT id FROM assets ORDER BY id ASC");
    $stmtAllAssets->execute();
    $assetsList = $stmtAllAssets->fetchAll(PDO::FETCH_ASSOC);

    // 2. Siapkan klausa kondisi SQL jika user melakukan pencarian
    $sqlSearch = "";
    if ($search !== "") {
        $sqlSearch = " WHERE os LIKE :search 
                          OR cpu LIKE :search 
                          OR ram LIKE :search 
                          OR storage LIKE :search 
                          OR rack LIKE :search 
                          OR fungsi LIKE :search";
    }

    // 3. Hitung total data hasil filter pencarian untuk membuat link halaman (pagination)
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM servers" . $sqlSearch);
    if ($search !== "") {
        $stmtCount->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmtCount->execute();
    $totalRows = $stmtCount->fetchColumn();
    $totalPages = ceil($totalRows / $perPage);

    // 4. Ambil data server secara mandiri tanpa LEFT JOIN kolom nama_asset yang error
    $sqlServers = "SELECT * FROM servers" . $sqlSearch . " LIMIT :limit OFFSET :offset";

    $stmtServers = $conn->prepare($sqlServers);
    if ($search !== "") {
        $stmtServers->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $stmtServers->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmtServers->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtServers->execute();
    $serversData = $stmtServers->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Koneksi gagal atau query bermasalah: " . $e->getMessage();
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
          <a href="server.php" class="nav-link active bg-primary text-white p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
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
          <li class="nav-item">
            <a href="manajemen_asset.php" class="nav-link text-white p-2 rounded"><i class="bi bi-boxes me-2"></i> Manajemen Asset</a>
          </li>
          <li class="nav-item">
            <a href="asset_movements.php" class="nav-link text-white p-2 rounded"><i class="bi bi-arrow-left-right me-2"></i> Log Perpindahan</a>
          </li>
          <li class="nav-item">
            <a href="server.php" class="nav-link  active bg-primary text-white p-2 rounded"><i class="bi bi-hdd-network me-2"></i> Server</a>
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

      <!-- HEADER HALAMAN -->
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-hdd-network me-2"></i> Manajemen Server</h1>
        <!-- Tombol Menu khusus tampilan Mobile jika Sidebar tertutup -->
        <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
          <i class="bi bi-list"></i> Menu
        </button>
      </div>

      <!-- NOTIFIKASI FLASH ALERT (OTOMATIS SINKRON DENGAN BACKEND PHP) -->
      <?php if (isset($_SESSION['msg_success'])): ?>
          <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
              <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i> 
                <div><?php echo $_SESSION['msg_success']; unset($_SESSION['msg_success']); ?></div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['msg_error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
              <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> 
                <div><?php echo $_SESSION['msg_error']; unset($_SESSION['msg_error']); ?></div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
      <?php endif; ?>

      <!-- KONTROL BAR: PENCARIAN & TOMBOL TAMBAH DATA -->
      <div class="row g-3 mb-4 align-items-center">
        <div class="col-12 col-md-6 col-lg-4">
          <form method="GET" action="" class="input-group shadow-sm">
            <input type="text" name="search" class="form-control" placeholder="Cari OS, CPU, atau Fungsi..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if(!empty($search)): ?>
              <a href="server.php" class="btn btn-outline-secondary" title="Reset Pencarian"><i class="bi bi-x-circle"></i></a>
            <?php endif; ?>
          </form>
        </div>
        <div class="col-12 col-md-6 col-lg-8 text-md-end">
          <button class="btn btn-success shadow-sm" type="button" data-bs-toggle="modal" data-bs-target="#modalTambahServer">
            <i class="bi bi-plus-lg me-1"></i> Tambah Server Baru
          </button>
        </div>
      </div>

<!-- TABEL DATA SERVER (SUDAH DIRAPIKAN JARAK KOLOMNYA) -->
<div class="card shadow-sm border-0 rounded-3 mb-4">
  <div class="card-body p-0">
    <div class="table-responsive">
      <!-- Menambahkan class table-bordered (opsional) untuk garis pembatas tipis antar kolom agar makin rapi -->
      <table class="table table-hover table-striped align-middle mb-0 text-nowrap table-bordered">
        <thead class="table-dark">
          <tr>
            <th scope="col" class="px-3 text-center" style="width: 50px;">ID</th>
            <th scope="col" class="px-3" style="min-width: 120px;">Asset ID</th>
            <th scope="col" class="px-3" style="min-width: 180px;">OS</th>
            <th scope="col" class="px-3" style="min-width: 220px;">CPU</th>
            <th scope="col" class="px-3" style="min-width: 120px;">RAM</th>
            <th scope="col" class="px-3" style="min-width: 140px;">Storage</th>
            <th scope="col" class="px-3" style="min-width: 110px;">Rack</th>
            <th scope="col" class="px-3" style="min-width: 250px;">Fungsi</th>
            <th scope="col" class="px-3 text-center" style="width: 100px;">Status</th>
            <th scope="col" class="px-3 text-center" style="width: 100px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($serversData)): ?>
            <?php foreach ($serversData as $server): ?>
              <tr>
                <td class="px-3 text-center fw-bold"><?php echo htmlspecialchars($server['id']); ?></td>
                <td class="px-3">
                  <!-- Memastikan badge tidak terlalu mepet dengan teks kolom lain -->
                  <span class="badge bg-secondary px-2 py-1.5">Asset ID: <?php echo htmlspecialchars($server['asset_id'] ?? '-'); ?></span>
                </td>
                <td class="px-3"><?php echo htmlspecialchars($server['os'] ?? '-'); ?></td>
                <td class="px-3 fw-mono text-secondary" style="font-size: 0.9rem;"><?php echo htmlspecialchars($server['cpu'] ?? '-'); ?></td>
                <td class="px-3"><?php echo htmlspecialchars($server['ram'] ?? '-'); ?></td>
                <td class="px-3"><?php echo htmlspecialchars($server['storage'] ?? '-'); ?></td>
                <td class="px-3"><i class="bi bi-layers text-muted me-1"></i> <?php echo htmlspecialchars($server['rack'] ?? '-'); ?></td>
                <td class="px-3 text-wrap-normal" style="max-width: 300px; white-space: normal;"><?php echo htmlspecialchars($server['fungsi'] ?? '-'); ?></td>
                <td class="px-3 text-center">
                  <?php if ((int)$server['status'] === 1): ?>
                    <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check-circle me-1"></i> Aktif</span>
                  <?php else: ?>
                    <span class="badge bg-danger rounded-pill px-3"><i class="bi bi-x-circle me-1"></i> Non-Aktif</span>
                  <?php endif; ?>
                </td>
                <td class="px-3 text-center">
                  <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-warning btn-edit-server" title="Ubah Data Server"
                            data-id="<?php echo $server['id']; ?>"
                            data-asset="<?php echo htmlspecialchars($server['asset_id'] ?? ''); ?>"
                            data-os="<?php echo htmlspecialchars($server['os'] ?? ''); ?>"
                            data-cpu="<?php echo htmlspecialchars($server['cpu'] ?? ''); ?>"
                            data-ram="<?php echo htmlspecialchars($server['ram'] ?? ''); ?>"
                            data-storage="<?php echo htmlspecialchars($server['storage'] ?? ''); ?>"
                            data-rack="<?php echo htmlspecialchars($server['rack'] ?? ''); ?>"
                            data-fungsi="<?php echo htmlspecialchars($server['fungsi'] ?? ''); ?>"
                            data-status="<?php echo $server['status']; ?>">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-delete-server" title="Hapus Server"
                            data-id="<?php echo $server['id']; ?>">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="10" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3 text-secondary"></i> Data server tidak ditemukan atau tabel kosong.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

      <!-- NAVIGASI HALAMAN (PAGINATION) -->
      <?php if (isset($totalPages) && $totalPages > 1): ?>
        <nav aria-label="Navigasi Halaman" class="mb-5">
          <ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
              <a class="page-link shadow-sm" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>" tabindex="-1">Sebelumnya</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                <a class="page-link shadow-sm" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
              <a class="page-link shadow-sm" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>">Selanjutnya</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </main>

  </div>
</div>

<!-- ==========================================
      MODAL POPUP: FORM TAMBAH DATA SERVER
=========================================== -->
<div class="modal fade" id="modalTambahServer" tabindex="-1" aria-labelledby="modalTambahServerLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      
      <!-- Header Modal -->
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title" id="modalTambahServerLabel">
          <i class="bi bi-hdd-rack me-2 text-warning"></i> Tambah Server Baru
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Form Input diarahkan ke proses_server.php -->
      <form action="proses_server.php" method="POST">
        <input type="hidden" name="action" value="tambah_server">
        
        <div class="modal-body py-2">
          <div class="row g-2">
            
            <!-- Baris 1: Dropdown Pilihan Asset & Operating System -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Asset ID (Hubungan Aset)</label>
<select name="asset_id" class="form-select form-select-sm">
  <option value="">-- Hubungkan ke Aset --</option>
  <?php if (!empty($assetsList)): ?>
    <?php foreach ($assetsList as $assetOption): ?>
      <option value="<?php echo $assetOption['id']; ?>">
        Asset ID: <?php echo $assetOption['id']; ?>
      </option>
    <?php endforeach; ?>
  <?php endif; ?>
</select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Operating System (OS)</label>
              <input type="text" name="os" class="form-control form-control-sm" placeholder="Contoh: Ubuntu Server 22.04" required>
            </div>
            
            <!-- Baris 2: CPU & RAM -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">CPU</label>
              <input type="text" name="cpu" class="form-control form-control-sm" placeholder="Intel Xeon..." required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">RAM</label>
              <input type="text" name="ram" class="form-control form-control-sm" placeholder="16 GB" required>
            </div>
            
            <!-- Baris 3: Storage & Nomor Rack -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Storage</label>
              <input type="text" name="storage" class="form-control form-control-sm" placeholder="500 GB SSD" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Rack No.</label>
              <input type="text" name="rack" class="form-control form-control-sm" placeholder="Rack A-01">
            </div>
            
            <!-- Baris 4: Fungsi Server & Status Server -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Fungsi Server</label>
              <input type="text" name="fungsi" class="form-control form-control-sm" placeholder="Web Server, Database, dll...">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Status Server</label>
              <select name="status" class="form-select form-select-sm">
                <option value="1">Aktif</option>
                <option value="0">Non-Aktif</option>
              </select>
            </div>

          </div>
        </div>
        
        <!-- Footer Modal -->
        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-success px-4">Simpan Data</button>
        </div>
      </form>
      
    </div>
  </div>
</div>

<!-- ==========================================
      MODAL POPUP: FORM EDIT DATA SERVER
=========================================== -->
<div class="modal fade" id="modalEditServer" tabindex="-1" aria-labelledby="modalEditServerLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content shadow-lg border-0">
      
      <!-- Header Modal -->
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title" id="modalEditServerLabel">
          <i class="bi bi-pencil-square me-2 text-warning"></i> Edit Data Server
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Form Input diarahkan ke proses_server.php -->
      <form action="proses_server.php" method="POST">
        <!-- Input Hidden Penanda Aksi & ID Primary Key -->
        <input type="hidden" name="action" value="edit_server">
        <input type="hidden" name="id" id="edit-id">
        
        <div class="modal-body py-2">
          <div class="row g-2">
            
            <!-- Baris 1: Dropdown Pilihan Asset & Operating System -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Asset ID (Hubungan Aset)</label>
              <select name="asset_id" id="edit-asset" class="form-select form-select-sm">
                <option value="">-- Hubungkan ke Aset (Opsional) --</option>
                <?php if (!empty($assetsList)): ?>
                  <?php foreach ($assetsList as $assetOption): ?>
                    <option value="<?php echo $assetOption['id']; ?>">
                      [ID: <?php echo $assetOption['id']; ?>] <?php echo htmlspecialchars($assetOption['nama_asset']); ?>
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Operating System (OS)</label>
              <input type="text" name="os" id="edit-os" class="form-control form-control-sm" required>
            </div>
            
            <!-- Baris 2: CPU & RAM -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">CPU</label>
              <input type="text" name="cpu" id="edit-cpu" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">RAM</label>
              <input type="text" name="ram" id="edit-ram" class="form-control form-control-sm" required>
            </div>
            
            <!-- Baris 3: Storage & Nomor Rack -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Storage</label>
              <input type="text" name="storage" id="edit-storage" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Rack No.</label>
              <input type="text" name="rack" id="edit-rack" class="form-control form-control-sm">
            </div>
            
            <!-- Baris 4: Fungsi Server & Status Server -->
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Fungsi Server</label>
              <input type="text" name="fungsi" id="edit-fungsi" class="form-control form-control-sm">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small mb-1">Status Server</label>
              <select name="status" id="edit-status" class="form-select form-select-sm">
                <option value="1">Aktif</option>
                <option value="0">Non-Aktif</option>
              </select>
            </div>

          </div>
        </div>
        
        <!-- Footer Modal -->
        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary px-4">Simpan Perubahan</button>
        </div>
      </form>
      
    </div>
  </div>
</div>

<!-- SCRIPT JS PENDUKUNG BOOTSTRAP & OPERASI CRUD -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    
    // ====================================================
    // 1. OTOMATIS MENUTUP NOTIFIKASI ALERTS (Setelah 5 Detik)
    // ====================================================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // ====================================================
    // 2. PARSING DATA TABEL KE DALAM MODAL FORM EDIT
    // ====================================================
    const editButtons = document.querySelectorAll('.btn-edit-server');
    const modalEditInstance = new bootstrap.Modal(document.getElementById('modalEditServer'));

    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Mengisi nilai input modal berdasarkan atribut data tombol tabel
            document.getElementById('edit-id').value = this.getAttribute('data-id');
            document.getElementById('edit-asset').value = this.getAttribute('data-asset');
            document.getElementById('edit-os').value = this.getAttribute('data-os');
            document.getElementById('edit-cpu').value = this.getAttribute('data-cpu');
            document.getElementById('edit-ram').value = this.getAttribute('data-ram');
            document.getElementById('edit-storage').value = this.getAttribute('data-storage');
            document.getElementById('edit-rack').value = this.getAttribute('data-rack');
            document.getElementById('edit-fungsi').value = this.getAttribute('data-fungsi');
            document.getElementById('edit-status').value = this.getAttribute('data-status');

            // Membuka jendela pop-up modal edit secara programatis
            modalEditInstance.show();
        });
    });

    // ====================================================
    // 3. KONFIRMASI AMAN SEBELUM MENGHAPUS DATA SERVER
    // ====================================================
    const deleteButtons = document.querySelectorAll('.btn-delete-server');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const serverId = this.getAttribute('data-id');
            // Menampilkan dialog konfirmasi bawaan browser untuk mencegah ketidaksengajaan
            if (confirm(`Apakah Anda yakin ingin menghapus data server dengan ID ${serverId}? Data yang terhapus tidak dapat dikembalikan.`)) {
                // Alihkan ke backend proses_server.php dengan parameter hapus
                window.location.href = `proses_server.php?action=hapus_server&id=${serverId}`;
            }
        });
    });

});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
